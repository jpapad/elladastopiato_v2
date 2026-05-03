import { NextRequest, NextResponse } from 'next/server'

// ── Helpers ───────────────────────────────────────────────────────────────────
function parseDuration(iso: string): number {
  if (!iso) return 0
  const m = iso.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/)
  if (!m) return 0
  return (parseInt(m[1] || '0') * 60) + parseInt(m[2] || '0')
}

function extractInstructions(raw: any): string[] {
  if (!raw) return []
  if (typeof raw === 'string') return raw.split('\n').map(s => s.trim()).filter(Boolean)
  if (Array.isArray(raw)) {
    const steps: string[] = []
    for (const item of raw) {
      if (typeof item === 'string') { steps.push(item.trim()); continue }
      if (item['@type'] === 'HowToSection' || item['@type'] === 'https://schema.org/HowToSection') {
        const sub = item.itemListElement || []
        for (const s of sub) steps.push((s.text || s.name || '').trim())
      } else {
        steps.push((item.text || item.name || '').trim())
      }
    }
    return steps.filter(Boolean)
  }
  return []
}

function extractImage(img: any): string {
  if (!img) return ''
  if (typeof img === 'string') return img
  if (Array.isArray(img)) return extractImage(img[0])
  return img.url || img.contentUrl || img['@id'] || ''
}

function findRecipeSchema(data: any, depth = 0): any | null {
  if (!data || depth > 6) return null
  if (Array.isArray(data)) {
    for (const item of data) {
      const found = findRecipeSchema(item, depth)
      if (found) return found
    }
    return null
  }
  if (typeof data !== 'object') return null
  const type = data['@type']
  const isRecipeType = (t: string) =>
    t === 'Recipe' ||
    t === 'https://schema.org/Recipe' ||
    t === 'http://schema.org/Recipe'
  if (typeof type === 'string' && isRecipeType(type)) return data
  if (Array.isArray(type) && type.some(isRecipeType)) return data
  for (const key of ['@graph', 'mainEntity', 'mainEntityOfPage', 'about', 'hasPart', 'itemListElement']) {
    if (data[key]) {
      const found = findRecipeSchema(data[key], depth + 1)
      if (found) return found
    }
  }
  return null
}

function findInNextData(html: string): any | null {
  const m = html.match(/<script[^>]+id=["']__NEXT_DATA__["'][^>]*>([\s\S]*?)<\/script>/)
  if (!m) return null
  try {
    const nextData = JSON.parse(m[1])
    const pageProps = nextData?.props?.pageProps
    if (!pageProps) return null
    // Check known keys first, then scan all pageProps keys
    const keysToCheck = [
      'recipe', 'recipeData', 'post', 'article', 'content', 'data',
      ...Object.keys(pageProps).filter(k => !['recipe','recipeData','post','article','content','data'].includes(k)),
    ]
    for (const key of keysToCheck) {
      if (pageProps[key] && typeof pageProps[key] === 'object') {
        const obj = pageProps[key]
        if (obj.recipeIngredient || obj.recipeInstructions || obj.ingredients || obj.instructions) {
          return normalizeRecipe(obj)
        }
        const found = findRecipeSchema(obj)
        if (found) return found
      }
    }
    return findRecipeSchema(pageProps)
  } catch { return null }
}

function normalizeRecipe(obj: any): any {
  return {
    '@type': 'Recipe',
    name: obj.title || obj.name || '',
    description: obj.description || obj.summary || '',
    recipeIngredient: normalizeIngredients(obj.recipeIngredient || obj.ingredients || []),
    recipeInstructions: obj.recipeInstructions || obj.instructions || obj.steps || [],
    prepTime: obj.prepTime || obj.prep_time || '',
    cookTime: obj.cookTime || obj.cook_time || obj.totalTime || '',
    recipeYield: obj.recipeYield || obj.servings || obj.yield || null,
    image: obj.image?.url || obj.image || obj.thumbnail || obj.photo || '',
  }
}

// Ingredients can be strings or objects like { name: '...', amount: '...', unit: '...' }
function normalizeIngredients(raw: any[]): string[] {
  if (!Array.isArray(raw)) return []
  return raw.map(item => {
    if (typeof item === 'string') return item.trim()
    // WP Recipe Maker format
    if (item.amount !== undefined || item.unit !== undefined) {
      return [item.amount, item.unit, item.name].filter(Boolean).join(' ').trim()
    }
    return (item.name || item.text || item.ingredient || JSON.stringify(item)).trim()
  }).filter(Boolean)
}

// Normalize WP Recipe Maker instructions (array of groups with steps)
function normalizeWPRMInstructions(raw: any[]): string[] {
  if (!Array.isArray(raw)) return []
  const steps: string[] = []
  for (const group of raw) {
    const groupSteps = group.steps || []
    for (const step of groupSteps) {
      const text = step.text || step.name || ''
      if (text) steps.push(text.replace(/<[^>]+>/g, '').trim())
    }
  }
  return steps.filter(Boolean)
}

function extractFromMeta(html: string): Partial<{ title: string; imageUrl: string; description: string }> {
  const og = (prop: string) => {
    const m = html.match(new RegExp(`<meta[^>]+property=["']og:${prop}["'][^>]+content=["']([^"']+)["']`, 'i'))
      || html.match(new RegExp(`<meta[^>]+content=["']([^"']+)["'][^>]+property=["']og:${prop}["']`, 'i'))
    return m ? m[1] : ''
  }
  const title = og('title') || (html.match(/<title[^>]*>([^<]+)<\/title>/i)?.[1] || '').trim()
  return { title, imageUrl: og('image'), description: og('description') }
}

// ── CSS-class recipe parser (WPRM / Tasty Recipes rendered HTML) ─────────────
function extractFromContentHTML(content: string, post: any, isFullPage = false): any | null {
  function byClass(h: string, ...classes: string[]): string[] {
    for (const cls of classes) {
      const items: string[] = []
      const re = new RegExp(`<[^>]+class=["'][^"']*\\b${cls}\\b[^"']*["'][^>]*>((?:[^<]|<(?!\\/)(?:[^>]*)>)*?)<\\/[a-z0-9]+>`, 'gis')
      let m: RegExpExecArray | null
      while ((m = re.exec(h)) !== null) {
        const text = m[1].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
        if (text && text.length > 1) items.push(text)
      }
      if (items.length) return items
    }
    return []
  }

  // WPRM ingredient extraction (tries 3 strategies in order)
  function wprmIngredients(h: string): string[] {
    // Strategy 1: <li class="wprm-recipe-ingredient ..."> with amount/unit/name spans
    {
      const items: string[] = []
      const liRe = /<li[^>]+class=["'][^"']*wprm-recipe-ingredient(?!-group)[^"']*["'][^>]*>([\s\S]*?)<\/li>/gi
      let li: RegExpExecArray | null
      while ((li = liRe.exec(h)) !== null) {
        const inner = li[1]
        const get = (cls: string) => (inner.match(new RegExp(`class=["'][^"']*${cls}[^"']*["'][^>]*>([^<]+)<`, 'i')) || [])[1]?.trim() || ''
        const parts = [get('wprm-recipe-ingredient-amount'), get('wprm-recipe-ingredient-unit'), get('wprm-recipe-ingredient-name')].filter(Boolean)
        if (parts.length) items.push(parts.join(' '))
        else {
          const text = inner.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
          if (text && text.length > 1) items.push(text)
        }
      }
      if (items.length) return items
    }
    // Strategy 2: find wprm-recipe-ingredient-name spans directly (simpler, catches variations)
    {
      const items: string[] = []
      const re = /<[^>]+class=["'][^"']*wprm-recipe-ingredient-name[^"']*["'][^>]*>([\s\S]*?)<\/[a-z]+>/gi
      let m: RegExpExecArray | null
      while ((m = re.exec(h)) !== null) {
        const text = m[1].replace(/<[^>]+>/g, '').trim()
        if (text) items.push(text)
      }
      if (items.length) return items
    }
    // Strategy 3: <ul class="wprm-recipe-ingredients..."> → grab all <li> items
    {
      const items: string[] = []
      const ulRe = /<ul[^>]+class=["'][^"']*wprm-recipe-ingredients[^"']*["'][^>]*>([\s\S]*?)<\/ul>/gi
      let ul: RegExpExecArray | null
      while ((ul = ulRe.exec(h)) !== null) {
        const liRe2 = /<li[^>]*>([\s\S]*?)<\/li>/gi
        let li: RegExpExecArray | null
        while ((li = liRe2.exec(ul[1])) !== null) {
          const text = li[1].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
          if (text && text.length > 1) items.push(text)
        }
      }
      return items
    }
  }

  const name = post.title?.rendered?.replace(/<[^>]+>/g, '') || ''

  // Filter: skip ALL-CAPS short strings without digits (nav items, not ingredients)
  const parseList = (html: string) =>
    [...html.matchAll(/<li[^>]*>([\s\S]*?)<\/li>/gi)]
      .map(m => m[1].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim())
      .filter(s => s.length > 1 && !(s === s.toUpperCase() && s.length < 40 && !/\d/.test(s)))

  // ── Collect ingredients from all sources independently ─────────────────────
  let ingredients: string[] = []
  let instructions: string[] = []
  let description = ''
  let prepTime = '', cookTime = ''
  let recipeYield: string | null = null

  // 1. WPRM
  const wprmIng = wprmIngredients(content)
  const wprmSteps = byClass(content, 'wprm-recipe-instruction-text')
  if (wprmIng.length) ingredients = wprmIng
  if (wprmSteps.length) {
    instructions = wprmSteps
    description = byClass(content, 'wprm-recipe-summary')[0] || ''
    const prepM = content.match(/wprm-recipe-prep_time["'>]+\s*<[^>]+>(\d+)/)
    const cookM = content.match(/wprm-recipe-cook_time["'>]+\s*<[^>]+>(\d+)/)
    const servM = content.match(/wprm-recipe-servings["'>]+\s*<[^>]+>(\d+)/)
    prepTime = prepM ? `PT${prepM[1]}M` : ''
    cookTime = cookM ? `PT${cookM[1]}M` : ''
    recipeYield = servM ? servM[1] : null
  }

  // 2. Tasty Recipes
  if (!ingredients.length) {
    const tastyIng = byClass(content, 'tasty-recipes-ingredients-body')
      .flatMap(s => s.split('\n').map(l => l.trim()).filter(Boolean))
    if (tastyIng.length) ingredients = tastyIng
  }
  if (!instructions.length) {
    const tastySteps = byClass(content, 'tasty-recipes-instructions-body')
    if (tastySteps.length) {
      instructions = tastySteps
      description = byClass(content, 'tasty-recipes-description')[0] || ''
    }
  }

  // 2b. ingredients__item (argiro.gr and similar checkbox-style ingredient lists)
  if (!ingredients.length && content.includes('ingredients__item')) {
    const items: string[] = []
    const re = /class=["'][^"']*ingredients__quantity[^"']*["'][^>]*>([^<]*)<[\s\S]{0,300}?<p[^>]*>([^<]+)<\/p>/gi
    let m: RegExpExecArray | null
    while ((m = re.exec(content)) !== null) {
      const qty = m[1].trim()
      const name = m[2].trim()
      const full = [qty, name].filter(Boolean).join(' ')
      if (full) items.push(full)
    }
    if (items.length) ingredients = items
  }

  // 3. Greek heading regex (h1-h6, div, span, p, strong, b, em)
  const ING_WORD = /Υλικά/i
  const INS_WORD = /Εκτέλεση|Παρασκευή|Οδηγίες/i
  const HEADING_TAGS = 'h[1-6]|div|span|p|strong|b|em|label'
  const HEADING_RE = (word: RegExp) =>
    new RegExp(
      `<(?:${HEADING_TAGS})[^>]*>[^<]*(?:${word.source})[^<]*<\\/(?:${HEADING_TAGS})>[\\s\\S]{0,2000}?<(ul|ol)[^>]*>([\\s\\S]*?)<\\/\\1>`,
      'i'
    )

  if (!ingredients.length) {
    const ingSection = content.match(HEADING_RE(ING_WORD))
    if (ingSection) ingredients = parseList(ingSection[2])
  }
  if (!instructions.length) {
    const insSection = content.match(HEADING_RE(INS_WORD))
    if (insSection) instructions = parseList(insSection[2])
  }

  // 4. Region-based: content between "Υλικά" and "Εκτέλεση/Παρασκευή" markers
  if (!ingredients.length) {
    const region = content.match(/Υλικά([\s\S]{5,4000}?)(?:Εκτέλεση|Παρασκευή|Οδηγίες|ΕΚΤΕΛΕΣΗ)/i)
    if (region) {
      const regionHtml = region[1]
      const qualify = (s: string) =>
        s.length > 2 && s.length < 300 &&
        !(s === s.toUpperCase() && s.length < 40 && !/\d/.test(s))

      // Try structured elements: li, p, span, div (each tag = one ingredient)
      for (const tag of ['li', 'p', 'span', 'div']) {
        const items = [...regionHtml.matchAll(new RegExp(`<${tag}[^>]*>([\\s\\S]*?)<\\/${tag}>`, 'gi'))]
          .map(m => m[1].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim())
          .filter(qualify)
        if (items.length >= 2) { ingredients = items; break }
      }

      // Fallback: strip all tags, split by <br> or newlines
      if (!ingredients.length) {
        const plain = regionHtml.replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, ' ')
        const lines = plain.split('\n').map(l => l.replace(/\s+/g, ' ').trim()).filter(qualify)
        if (lines.length >= 2) ingredients = lines
      }
    }
  }

  // 5. Last-resort for post content (no nav): first <ul> = ingredients
  if (!ingredients.length && !isFullPage) {
    const m = content.match(/<ul[^>]*>([\s\S]*?)<\/ul>/i)
    if (m) ingredients = parseList(m[1])
  }

  if (ingredients.length || instructions.length) {
    return {
      '@type': 'Recipe', name, description,
      recipeIngredient: ingredients,
      recipeInstructions: instructions,
      prepTime, cookTime, recipeYield,
      image: post.featured_media_src_url || '',
    }
  }

  return null
}

// ── Custom API extractors for specific sites ──────────────────────────────────
async function tryCustomAPI(html: string, originalUrl: string): Promise<any | null> {
  const ogImg = extractFromMeta(html).imageUrl || ''

  // giorgostsoulis.com: 2-step — slug search → ID → detail
  if (originalUrl.includes('giorgostsoulis.com')) {
    const slug = new URL(originalUrl).pathname.replace(/\/$/, '').split('/').pop() || ''
    if (!slug) return null
    const headers = {
      'Accept': 'application/json',
      'User-Agent': 'Mozilla/5.0',
      'Origin': 'https://www.giorgostsoulis.com',
      'Referer': originalUrl,
    }

    // Step 1: search by slug to find the recipe ID
    let listRecipe: any = null
    try {
      const res = await fetch(`https://api.giorgostsoulis.com/api/recipes?slug=${slug}`, { headers, signal: AbortSignal.timeout(8_000) })
      if (res.ok) {
        const json = await res.json()
        const items: any[] = json?.data?.data || []
        listRecipe = items.find((r: any) => r.slug === slug) || items[0] || null
      }
    } catch { /* ignore */ }

    if (!listRecipe?.id) return null

    // Step 2: fetch detail for ingredients + instructions
    try {
      const res2 = await fetch(`https://api.giorgostsoulis.com/api/recipes/${listRecipe.id}`, { headers, signal: AbortSignal.timeout(8_000) })
      if (!res2.ok) return null
      const rawJson2 = await res2.json()
      const detail = rawJson2?.data ?? rawJson2
      // Ingredients: ingredients_raw is an HTML string with <br> separators
      let ingredients: string[] = []
      if (typeof detail.ingredients_raw === 'string' && detail.ingredients_raw.trim()) {
        ingredients = detail.ingredients_raw
          .replace(/<br\s*\/?>/gi, '\n')
          .replace(/<[^>]+>/g, '')
          .split('\n')
          .map((s: string) => s.trim())
          .filter(Boolean)
      } else {
        const rawIng: any[] = Array.isArray(detail.ingredient) ? detail.ingredient
          : Array.isArray(detail.ingredients) ? detail.ingredients : []
        ingredients = rawIng.flatMap((g: any) => {
          if (Array.isArray(g.ingredient)) return g.ingredient.map((i: any) => typeof i === 'string' ? i : [i.amount, i.unit, i.name].filter(Boolean).join(' '))
          if (typeof g === 'string') return [g]
          return [[g.amount, g.unit, g.name || g.title || g.description].filter(Boolean).join(' ')]
        }).map((s: string) => s.trim()).filter(Boolean)
      }

      // Instructions: array of { description, ... }
      const rawSteps: any[] = Array.isArray(detail.instructions) ? detail.instructions
        : Array.isArray(detail.instruction_parts) ? detail.instruction_parts
        : Array.isArray(detail.steps) ? detail.steps
        : Array.isArray(detail.execution) ? detail.execution : []
      const instructions: string[] = rawSteps.flatMap((g: any) => {
        if (Array.isArray(g.steps)) return g.steps.map((s: any) => typeof s === 'string' ? s : (s.description || s.text || ''))
        if (Array.isArray(g.instructions)) return g.instructions.map((s: any) => typeof s === 'string' ? s : (s.description || s.text || ''))
        return [typeof g === 'string' ? g : (g.description || g.text || g.step || g.body || '')]
      }).map((s: string) => s.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()).filter(Boolean)

      const t = listRecipe.time || {}
      const media = listRecipe.media || {}

      return {
        '@type': 'Recipe',
        name: detail.title || listRecipe.title || '',
        description: detail.excerpt || listRecipe.excerpt || '',
        recipeIngredient: ingredients,
        recipeInstructions: instructions,
        prepTime: t.preparation ? `PT${t.preparation}M` : '',
        cookTime: t.execution ? `PT${t.execution}M` : '',
        recipeYield: listRecipe.portions?.amount || null,
        image: media.large || media.original || media.medium || ogImg,
      }
    } catch { return null }
  }

  return null
}

// ── WordPress REST API extractor (generic — works for any WP site) ────────────
async function tryWordPress(url: string, html: string, debug = false): Promise<any | null> {
  const isWP = html.includes('/wp-content/') || html.includes('/wp-json/') || html.includes('wp-emoji')
  if (!isWP) return null

  const urlObj = new URL(url)
  const origin = urlObj.origin
  const parts = urlObj.pathname.replace(/\/$/, '').split('/').filter(Boolean)
  if (parts.length === 0) return null

  const slug = parts[parts.length - 1]
  const postTypeGuess = parts.length >= 2 ? parts[parts.length - 2] : 'posts'
  const headers = { 'Accept': 'application/json', 'User-Agent': 'Mozilla/5.0' }
  const signal = AbortSignal.timeout(8_000)

  // Look for WPRM recipe ID directly in the page HTML
  const wprmInPage = html.match(/(?:data-recipe-id=["'](\d+)|wprm-recipe-container-(\d+))/)
  if (wprmInPage) {
    const id = wprmInPage[1] || wprmInPage[2]
    const wprm = await fetchWPRM(origin, id)
    if (wprm?.recipeIngredient?.length || wprm?.recipeInstructions?.length) return wprm
  }

  // Try multiple endpoint patterns (include content for CSS-class parsing)
  const fields = 'id,title,content,acf,yoast_head_json,featured_media_src_url,meta'
  const endpoints = [
    `${origin}/wp-json/wp/v2/${postTypeGuess}?slug=${slug}&per_page=1&_fields=${fields}`,
    `${origin}/wp-json/wp/v2/posts?slug=${slug}&per_page=1&_fields=${fields}`,
    `${origin}/wp-json/wp/v2/${postTypeGuess}?slug=${slug}&per_page=1`,
    `${origin}/wp-json/wp/v2/posts?slug=${slug}&per_page=1`,
  ]

  for (const endpoint of endpoints) {
    try {
      const res = await fetch(endpoint, { headers, signal })
      if (!res.ok) continue
      const posts = await res.json()
      if (!Array.isArray(posts) || !posts[0]) continue
      const post = posts[0]

      // 1. Yoast SEO JSON-LD — only return if it actually has ingredients
      const yoast = post.yoast_head_json
      if (yoast?.schema) {
        const found = findRecipeSchema(yoast.schema)
        const yoastHasIng = found && Array.isArray(found.recipeIngredient) && found.recipeIngredient.length > 0
        if (yoastHasIng) return found
      }

      // 2. WP Recipe Maker via meta ID
      const wprm = post.meta?.wprm_recipe_id
        ? await fetchWPRM(origin, post.meta.wprm_recipe_id)
        : null
      if (wprm?.recipeIngredient?.length || wprm?.recipeInstructions?.length) return wprm

      // 3. JSON-LD inside content.rendered
      const contentHtml = post.content?.rendered || ''
      if (contentHtml) {
        const ldRegex = /<script[^>]+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi
        let ldMatch: RegExpExecArray | null
        while ((ldMatch = ldRegex.exec(contentHtml)) !== null) {
          try {
            const found = findRecipeSchema(JSON.parse(ldMatch[1]))
            if (found) {
              if (!found.image) found.image = post.featured_media_src_url || ''
              return found
            }
          } catch { /* skip */ }
        }

        // 4. CSS-class parsing of rendered HTML (WPRM, Tasty Recipes, Greek headings)
        const fromHTML = extractFromContentHTML(contentHtml, post)
        if (fromHTML?.recipeIngredient?.length || fromHTML?.recipeInstructions?.length) return fromHTML
      }

      // 5. ACF
      if (post.acf && typeof post.acf === 'object' && !Array.isArray(post.acf)) {
        const acf = post.acf
        const hasData = acf.ingredients || acf.instructions || acf.recipe_ingredients
          || acf.steps || acf.materials || acf.execution
        if (hasData) {
          const ingredients = normalizeIngredients(acf.ingredients || acf.recipe_ingredients || acf.materials || [])
          const instructions = acf.instructions || acf.steps || acf.execution || acf.recipe_instructions || []
          return {
            '@type': 'Recipe',
            name: post.title?.rendered?.replace(/<[^>]+>/g, '') || '',
            description: acf.description || acf.intro || acf.excerpt || '',
            recipeIngredient: ingredients,
            recipeInstructions: Array.isArray(instructions)
              ? instructions.map((s: any) => typeof s === 'string' ? s : (s.text || s.step || s.name || ''))
              : [String(instructions)],
            prepTime: acf.prep_time || acf.prepTime || '',
            cookTime: acf.cook_time || acf.cookTime || acf.time || '',
            recipeYield: acf.servings || acf.portions || acf.yield || null,
            image: post.featured_media_src_url || acf.image?.url || acf.photo || '',
          }
        }
      }

      // 6. Partial fallback — title only
      if (post.title?.rendered) {
        if (debug) {
          // Fetch full post (no _fields) to expose all custom fields
          try {
            const base = endpoint.split('?')[0]
            const slug2 = endpoint.match(/slug=([^&]+)/)?.[1] || ''
            const fullRes = await fetch(`${base}?slug=${slug2}&per_page=1`, { headers, signal: AbortSignal.timeout(8_000) })
            const fullPosts = fullRes.ok ? await fullRes.json() : null
            return { __debug: { endpoint, post: fullPosts?.[0] ?? post, contentPreview: (post.content?.rendered || '').slice(0, 2000) } }
          } catch {
            return { __debug: { endpoint, post, contentPreview: (post.content?.rendered || '').slice(0, 2000) } }
          }
        }
        return {
          '@type': 'Recipe',
          name: post.title.rendered.replace(/<[^>]+>/g, ''),
          description: '',
          recipeIngredient: [],
          recipeInstructions: [],
          image: post.featured_media_src_url || '',
        }
      }
    } catch { continue }
  }
  return null
}

async function fetchWPRM(origin: string, id: string): Promise<any | null> {
  try {
    const res = await fetch(`${origin}/wp-json/wprm/v3/recipe/${id}`, {
      headers: { 'Accept': 'application/json', 'User-Agent': 'Mozilla/5.0' },
      signal: AbortSignal.timeout(5_000),
    })
    if (!res.ok) return null
    const r = await res.json()
    return {
      '@type': 'Recipe',
      name: r.name || '',
      description: r.summary || r.description || '',
      recipeIngredient: normalizeIngredients(
        (r.ingredients || []).flatMap((g: any) => g.ingredients || [])
      ),
      recipeInstructions: normalizeWPRMInstructions(r.instructions || []),
      prepTime: r.prep_time ? `PT${r.prep_time}M` : '',
      cookTime: r.cook_time ? `PT${r.cook_time}M` : '',
      recipeYield: r.servings || null,
      image: r.image_url || '',
    }
  } catch { return null }
}

// ── HTML microdata extractor (itemprop="recipeIngredient" etc.) ───────────────
function extractFromMicrodata(html: string): any | null {
  if (!html.includes('schema.org/Recipe')) return null

  function getProps(h: string, prop: string): string[] {
    const out: string[] = []
    // Match <tag ... itemprop="prop" ... content="val"> or inner text
    const re = new RegExp(
      `<[a-z][^>]+itemprop=["']${prop}["'][^>]*(?:content=["']([^"']+)["']|>((?:[^<]|<(?!/?)(?:[a-z][^>]*)>)*?)<\\/[a-z]+)`,
      'gi'
    )
    let m: RegExpExecArray | null
    while ((m = re.exec(h)) !== null) {
      const val = (m[1] || m[2] || '').replace(/<[^>]+>/g, '').trim()
      if (val) out.push(val)
    }
    return out
  }

  const name = getProps(html, 'name')[0] || ''
  if (!name) return null

  const instructions = getProps(html, 'recipeInstructions')
  const parsedInstructions = instructions.length === 1 && instructions[0].length > 200
    ? instructions[0].split(/\n+/).map(s => s.trim()).filter(Boolean)
    : instructions

  return {
    '@type': 'Recipe',
    name,
    description: getProps(html, 'description')[0] || '',
    recipeIngredient: getProps(html, 'recipeIngredient'),
    recipeInstructions: parsedInstructions,
    prepTime: getProps(html, 'prepTime')[0] || '',
    cookTime: getProps(html, 'cookTime')[0] || '',
    recipeYield: getProps(html, 'recipeYield')[0] || null,
    image: getProps(html, 'image')[0] || '',
  }
}

// ── Main handler ──────────────────────────────────────────────────────────────
export async function GET(req: NextRequest) {
  const url = req.nextUrl.searchParams.get('url')
  const debug = req.nextUrl.searchParams.get('debug') === 'true'
  if (!url) return NextResponse.json({ error: 'Missing url param' }, { status: 400 })

  let html = ''
  try {
    const res = await fetch(url, {
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language': 'el-GR,el;q=0.9,en-US;q=0.8,en;q=0.7',
        'Accept-Encoding': 'gzip, deflate, br',
        'Cache-Control': 'no-cache',
        'Upgrade-Insecure-Requests': '1',
      },
      redirect: 'follow',
      signal: AbortSignal.timeout(15_000),
    })
    if (!res.ok) {
      const msg = res.status === 403
        ? `Το site απορρίπτει αυτόματες εισαγωγές (403 Forbidden). Το akispetretzikis.com και παρόμοια sites με Cloudflare δεν υποστηρίζονται.`
        : `Αδύνατη η πρόσβαση (${res.status})`
      return NextResponse.json({ error: msg }, { status: 400 })
    }
    html = await res.text()
  } catch (e: any) {
    return NextResponse.json({ error: `Σφάλμα σύνδεσης: ${e.message}` }, { status: 400 })
  }

  let schema: any = null

  // 1. JSON-LD in HTML (fastest, most reliable for compliant sites)
  {
    const regex = /<script[^>]+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi
    let match: RegExpExecArray | null
    while ((match = regex.exec(html)) !== null) {
      try {
        const found = findRecipeSchema(JSON.parse(match[1]))
        if (found) { schema = found; break }
      } catch { /* skip */ }
    }
  }

  function hasIngredients(s: any) {
    if (!s) return false
    const r = s.recipeIngredient
    return Array.isArray(r) ? r.length > 0 : (typeof r === 'string' && r.trim().length > 0)
  }

  // 2. HTML microdata (itemprop) — used by many WP recipe plugins
  if (!hasIngredients(schema)) {
    const micro = extractFromMicrodata(html)
    if (micro && (micro.recipeIngredient?.length || micro.recipeInstructions?.length)) {
      schema = micro
    }
  }

  // 3. Main-page HTML class/heading parsing (WPRM, Tasty, Greek Υλικά/Εκτέλεση)
  if (!hasIngredients(schema)) {
    const fromPage = extractFromContentHTML(html, {
      title: { rendered: schema?.name || '' },
      featured_media_src_url: schema ? extractImage(schema.image) : '',
    }, true)
    if (fromPage?.recipeIngredient?.length || fromPage?.recipeInstructions?.length) {
      if (schema) {
        // Merge: keep title/description/times from JSON-LD, add ingredients from HTML
        schema.recipeIngredient = fromPage.recipeIngredient
        schema.recipeInstructions = fromPage.recipeInstructions
      } else {
        schema = fromPage
      }
    }
  }

  // 4. __NEXT_DATA__ (Next.js SSR sites)
  if (!hasIngredients(schema)) {
    const nd = findInNextData(html)
    if (nd) schema = nd
  }

  // 5. Custom site APIs (giorgostsoulis.com etc.)
  if (!hasIngredients(schema)) {
    const custom = await tryCustomAPI(html, url)
    if (custom && (custom.recipeIngredient?.length || custom.recipeInstructions?.length)) schema = custom
  }

  // 6. WordPress REST API (any WP site — argiro.gr, cantinamag.gr, etc.)
  if (!hasIngredients(schema)) {
    const wpResult = await tryWordPress(url, html, debug)
    if (debug && wpResult?.__debug) {
      return NextResponse.json(wpResult.__debug)
    }
    if (wpResult && (wpResult.recipeIngredient?.length || wpResult.recipeInstructions?.length)) {
      schema = wpResult
    } else if (!schema && wpResult) {
      schema = wpResult
    }
  }

  // Fallback (OG meta tags)
  if (!schema) {
    const meta = extractFromMeta(html)
    if (meta.title) {
      return NextResponse.json({
        error: 'Βρέθηκε μόνο τίτλος — δεν υπάρχουν δομημένα δεδομένα συνταγής σε αυτή τη σελίδα.',
        partial: true,
        title: meta.title,
        description: meta.description || '',
        ingredients: [],
        instructions: [],
        prepTime: 0, cookTime: 0, servings: null,
        imageUrl: meta.imageUrl || '',
        sourceUrl: url,
      }, { status: 206 })
    }
    return NextResponse.json({ error: 'Δεν βρέθηκε συνταγή σε αυτή τη σελίδα.' }, { status: 422 })
  }

  // Parse
  const rawIng = Array.isArray(schema.recipeIngredient)
    ? schema.recipeIngredient
    : typeof schema.recipeIngredient === 'string'
      ? schema.recipeIngredient.split(/\n|<br\s*\/?>/i).map((s: string) => s.trim()).filter(Boolean)
      : []
  // Deduplicate (some sites render ingredients twice — e.g. visible + print section)
  const allIng = normalizeIngredients(rawIng)
  const seen = new Set<string>()
  const ingredients = allIng.filter(s => { const k = s.toLowerCase(); return seen.has(k) ? false : (seen.add(k), true) })

  const instructions = extractInstructions(schema.recipeInstructions)

  const servingsRaw = schema.recipeYield
  let servings: number | null = null
  if (typeof servingsRaw === 'number') servings = servingsRaw
  else if (typeof servingsRaw === 'string') servings = parseInt(servingsRaw) || null
  else if (Array.isArray(servingsRaw)) servings = parseInt(String(servingsRaw[0])) || null

  // Times: try schema first, then parse from page HTML (allows HTML tags between keyword and number)
  let prepTime = parseDuration(schema.prepTime || '')
  let cookTime = parseDuration(schema.cookTime || '') || parseDuration(schema.totalTime || '')
  if (!prepTime && !cookTime) {
    const tMin = (re: RegExp) => { const m = html.match(re); return m ? parseInt(m[1]) : 0 }
    prepTime = tMin(/(?:Προετοιμασία|prep.?time)[\s\S]{0,400}?(\d+)\s*(?:λεπτ|min|'(?!\w))/i)
    cookTime = tMin(/(?:Μαγείρεμα|Ψήσιμο|Βράσιμο|cook.?time)[\s\S]{0,400}?(\d+)\s*(?:λεπτ|min|'(?!\w))/i)
      || tMin(/(?:Συνολικ[όο]|total.?time)[\s\S]{0,400}?(\d+)\s*(?:λεπτ|min|'(?!\w))/i)
  }

  // Description: fall back to OG description
  const meta = extractFromMeta(html)
  const description = (typeof schema.description === 'string' ? schema.description.trim() : '') || meta.description || ''

  // Strip site-name suffixes (e.g. " - Argiro.gr - Argiro Barbarigou"); fall back to OG title
  const rawTitle = (schema.name || meta.title || '').trim()
  const cleanTitle = rawTitle.replace(/\s*[-|–]\s*[^-|–]{3,}\.(gr|com|net|org)\b.*/i, '').trim() || rawTitle

  const response: any = {
    title: cleanTitle,
    description,
    ingredients,
    instructions,
    prepTime,
    cookTime,
    servings,
    imageUrl: extractImage(schema.image) || meta.imageUrl || '',
    sourceUrl: url,
  }

  if (debug) {
    // Find first ~600 chars of HTML around "Προετοιμασία" for time debugging
    const prepIdx = html.search(/Προετοιμασία/i)
    response._debug = {
      rawPrepTime: schema.prepTime,
      rawCookTime: schema.cookTime,
      rawTotalTime: schema.totalTime,
      parsedPrepTime: prepTime,
      parsedCookTime: cookTime,
      prepTimeContext: prepIdx >= 0 ? html.slice(prepIdx, prepIdx + 400) : '(not found)',
      rawRecipeIngredient: schema.recipeIngredient,
      schemaKeys: Object.keys(schema),
    }
  }

  return NextResponse.json(response)
}
