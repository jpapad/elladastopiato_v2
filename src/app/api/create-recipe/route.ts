import { NextRequest, NextResponse } from 'next/server'
import { getPayload } from 'payload'
import configPromise from '@payload-config'

const GR: Record<string, string> = {
  α:'a',β:'v',γ:'g',δ:'d',ε:'e',ζ:'z',η:'i',θ:'th',ι:'i',κ:'k',λ:'l',
  μ:'m',ν:'n',ξ:'x',ο:'o',π:'p',ρ:'r',σ:'s',ς:'s',τ:'t',υ:'y',φ:'f',
  χ:'ch',ψ:'ps',ω:'o',ά:'a',έ:'e',ή:'i',ί:'i',ό:'o',ύ:'y',ώ:'o',
  ϊ:'i',ϋ:'y',ΐ:'i',ΰ:'y',
}
function toSlug(str: string): string {
  return str.toLowerCase().split('').map(c => GR[c] ?? c).join('')
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s-]/g, '')
    .trim().replace(/\s+/g, '-').replace(/-+/g, '-')
}

export async function POST(req: NextRequest) {
  try {
    const payload = await getPayload({ config: configPromise })
    const data = await req.json()

    // Coerce relationship IDs to numbers (Payload v3 + PostgreSQL uses integer PKs)
    if (data.location) {
      const n = Number(data.location)
      if (!isNaN(n)) data.location = n
    }

    // Auto-import image from URL if provided
    if (data.imageUrl) {
      try {
        const imgRes = await fetch(data.imageUrl, {
          headers: { 'User-Agent': 'Mozilla/5.0' },
          signal: AbortSignal.timeout(15_000),
        })
        if (imgRes.ok) {
          const buffer = Buffer.from(await imgRes.arrayBuffer())
          const ct = imgRes.headers.get('content-type') || 'image/jpeg'
          const ext = ct.includes('png') ? 'png' : ct.includes('webp') ? 'webp' : 'jpg'
          const media = await payload.create({
            collection: 'media',
            data: { alt: data.title || 'Imported recipe image' },
            file: { data: buffer, mimetype: ct, name: `recipe-${Date.now()}.${ext}`, size: buffer.length },
            overrideAccess: true,
          })
          data.image = media.id
        }
      } catch (e) {
        console.warn('[create-recipe] image import failed:', e)
      }
      delete data.imageUrl
    }

    // Don't pass empty slug — let the beforeChange hook generate it or leave it null
    if (!data.slug) delete data.slug

    let recipe: any
    try {
      recipe = await payload.create({ collection: 'recipes', data, overrideAccess: true })
    } catch (firstErr: any) {
      const errJson = JSON.stringify(firstErr?.data ?? '')
      const isSlugConflict = firstErr?.data?.errors?.some?.((e: any) => e.path === 'slug')
        || errJson.includes('"slug"')
      if (isSlugConflict) {
        const base = data.slug || toSlug(data.title || '')
        data.slug = base ? `${base}-${Date.now().toString(36)}` : undefined
        if (!data.slug) delete data.slug
        recipe = await payload.create({ collection: 'recipes', data, overrideAccess: true })
      } else {
        throw firstErr
      }
    }

    return NextResponse.json({ id: recipe.id, slug: (recipe as any).slug }, { status: 201 })
  } catch (err: any) {
    console.error('[create-recipe]', JSON.stringify(err?.data ?? err?.message ?? err, null, 2))
    return NextResponse.json({
      error: err?.message || 'Σφάλμα δημιουργίας',
      details: err?.data ?? null,
    }, { status: 400 })
  }
}
