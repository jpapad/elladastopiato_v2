/**
 * Ελαφρύς client για το WordPress REST API (χωρίς εξαρτήσεις).
 *
 * - Δουλεύει και με «ωραία» permalinks (https://site.gr/wp-json/) και με
 *   απλά permalinks (https://site.gr/?rest_route=/) — δώσε ό,τι επιστρέφει το rest_url().
 * - Φέρνει αυτόματα όλες τις σελίδες (X-WP-TotalPages, max 100 ανά σελίδα).
 * - Κρατάει cache ανά URL ώστε πολλαπλά instances του χάρτη να μην ξαναφορτώνουν.
 */

import type {
  AcfImage,
  AcfSelect,
  GeoLevel,
  LocalPoint,
  LocalPointType,
  MapImage,
  MapLocation,
  WordPressMapData,
  WpLocalPointAcf,
  WpLocalPointRaw,
  WpLocationAcf,
  WpLocationRaw,
} from './types'

export type WordPressMapEndpoints = {
  /** REST base του CPT τοποθεσιών (default: `locations`). */
  locations?: string
  /** REST base του CPT τοπικών σημείων (default: `local-points`). */
  localPoints?: string
}

const DEFAULT_ENDPOINTS: Required<WordPressMapEndpoints> = {
  locations: 'locations',
  localPoints: 'local-points',
}

const PER_PAGE = 100
const MAX_PAGES = 50

/* ══════════════ URL helpers ══════════════ */

/**
 * Χτίζει URL για ένα REST route, π.χ. wpUrl('https://x.gr/wp-json/', 'wp/v2/locations', {page: 2}).
 */
export function wpUrl(
  apiRoot: string,
  route: string,
  params: Record<string, string | number | undefined> = {},
): string {
  const base = typeof window !== 'undefined' ? window.location.href : 'http://localhost/'
  const url = new URL(apiRoot, base)
  const cleanRoute = route.replace(/^\/+/, '')

  if (url.searchParams.has('rest_route')) {
    const current = (url.searchParams.get('rest_route') || '/').replace(/\/+$/, '')
    url.searchParams.set('rest_route', `${current}/${cleanRoute}`)
  } else {
    url.pathname = url.pathname.replace(/\/*$/, '/') + cleanRoute
  }

  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== '') url.searchParams.set(key, String(value))
  }
  return url.toString()
}

/* ══════════════ fetch helpers ══════════════ */

export class WordPressApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public code?: string,
  ) {
    super(message)
    this.name = 'WordPressApiError'
  }
}

const cache = new Map<string, Promise<unknown[]>>()

/** Φέρνει όλες τις σελίδες μιας συλλογής του WP REST API. */
export function fetchAllPages<T>(
  apiRoot: string,
  route: string,
  params: Record<string, string | number | undefined> = {},
): Promise<T[]> {
  const firstUrl = wpUrl(apiRoot, route, { ...params, per_page: PER_PAGE, page: 1 })
  const cached = cache.get(firstUrl)
  if (cached) return cached as Promise<T[]>

  const promise = (async () => {
    const items: T[] = []
    let page = 1
    let totalPages = 1

    do {
      const url = wpUrl(apiRoot, route, { ...params, per_page: PER_PAGE, page })
      const res = await fetch(url, { headers: { Accept: 'application/json' } })

      if (!res.ok) {
        let code: string | undefined
        let message = `WordPress REST ${res.status} για ${route}`
        try {
          const body = await res.json()
          code = body?.code
          if (body?.message) message = `${message}: ${body.message}`
        } catch {
          /* μη-JSON απάντηση */
        }
        throw new WordPressApiError(message, res.status, code)
      }

      const batch = (await res.json()) as T[]
      items.push(...batch)

      const header = Number(res.headers.get('X-WP-TotalPages'))
      // Αν το header δεν είναι διαθέσιμο (π.χ. proxy/CORS), συνέχισε όσο οι σελίδες είναι γεμάτες.
      totalPages =
        Number.isFinite(header) && header > 0 ? header : batch.length === PER_PAGE ? page + 1 : page
      page++
    } while (page <= totalPages && page <= MAX_PAGES)

    return items
  })()

  cache.set(firstUrl, promise)
  promise.catch(() => cache.delete(firstUrl)) // μην κρατάς αποτυχίες στην cache
  return promise
}

/* ══════════════ mapping helpers ══════════════ */

/** Αποκωδικοποιεί HTML entities από τα `title.rendered` (π.χ. &#8211; → –). */
export function decodeEntities(html: string): string {
  if (!html) return ''
  if (typeof DOMParser !== 'undefined') {
    return new DOMParser().parseFromString(html, 'text/html').documentElement.textContent || ''
  }
  return html
    .replace(/<[^>]*>/g, '')
    .replace(/&#(\d+);/g, (_, n) => String.fromCharCode(Number(n)))
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#039;|&apos;/g, "'")
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
}

function acfObject<T extends object>(acf: T | [] | undefined): Partial<T> {
  return acf && !Array.isArray(acf) ? acf : {}
}

function selectValue(v: AcfSelect): string | null {
  if (!v) return null
  if (typeof v === 'string') return v || null
  return v.value || null
}

function selectLabel(v: AcfSelect): string | null {
  if (!v || typeof v === 'string') return null
  return v.label || null
}

function toImage(img: AcfImage | number | false | null | undefined): MapImage | null {
  if (!img || typeof img !== 'object' || !img.url) return null
  const sizes = img.sizes || {}
  const pick = (k: string) => (typeof sizes[k] === 'string' ? (sizes[k] as string) : undefined)
  return {
    url: pick('medium_large') || pick('large') || pick('medium') || img.url,
    alt: img.alt || '',
  }
}

function toNumber(v: string | number | null | undefined): number | null {
  if (v === null || v === undefined || v === '') return null
  const n = typeof v === 'number' ? v : parseFloat(String(v).replace(',', '.'))
  return Number.isFinite(n) ? n : null
}

const LEVELS: GeoLevel[] = ['region', 'prefecture', 'municipality']

export const POINT_TYPE_LABELS: Record<LocalPointType, string> = {
  product: 'Τοπικό προϊόν',
  producer: 'Παραγωγός',
  restaurant: 'Εστιατόριο / Ταβέρνα',
  market: 'Αγορά',
  festival: 'Γιορτή / Πανηγύρι',
  sight: 'Αξιοθέατο',
  other: 'Τοπικό σημείο',
}

export function mapLocation(raw: WpLocationRaw): MapLocation {
  const acf = acfObject<WpLocationAcf>(raw.acf)
  const levelValue = selectValue(acf.map_level)
  const level = LEVELS.includes(levelValue as GeoLevel) ? (levelValue as GeoLevel) : null

  let geoName: string | null = null
  let geoPrefecture: string | null = null
  if (level === 'region') geoName = selectValue(acf.geo_region)
  if (level === 'prefecture') geoName = selectValue(acf.geo_prefecture)
  if (level === 'municipality') {
    // Οι τιμές δήμων αποθηκεύονται ως «ΝΟΜΟΣ|Δήμος» για να ξεχωρίζουν ομώνυμοι δήμοι.
    const value = selectValue(acf.geo_municipality)
    if (value) {
      const [pref, name] = value.includes('|') ? value.split('|') : ['', value]
      geoPrefecture = pref || null
      geoName = name || null
    }
  }

  return {
    id: raw.id,
    slug: raw.slug,
    link: raw.link,
    title: decodeEntities(raw.title?.rendered ?? ''),
    parentId: raw.parent ?? 0,
    level,
    geoName,
    geoPrefecture,
    description: acf.short_description?.trim() ?? '',
    image: toImage(acf.cover_image),
    localProducts: acf.local_products?.trim() ?? '',
  }
}

/** Αν λείπει το `map_level`, το συμπεραίνουμε από το βάθος στην ιεραρχία (0=περιφέρεια, 1=νομός, 2=δήμος). */
export function inferLevels(locations: MapLocation[]): MapLocation[] {
  const byId = new Map(locations.map((l) => [l.id, l]))
  const depthOf = (loc: MapLocation): number => {
    let depth = 0
    let current = loc
    const seen = new Set<number>()
    while (current.parentId && byId.has(current.parentId) && !seen.has(current.id)) {
      seen.add(current.id)
      current = byId.get(current.parentId)!
      depth++
    }
    return depth
  }
  return locations.map((loc) =>
    loc.level ? loc : { ...loc, level: LEVELS[Math.min(depthOf(loc), LEVELS.length - 1)] },
  )
}

export function mapLocalPoint(raw: WpLocalPointRaw): LocalPoint | null {
  const acf = acfObject<WpLocalPointAcf>(raw.acf)
  const lat = toNumber(acf.lat)
  const lng = toNumber(acf.lng)
  if (lat === null || lng === null) return null

  const typeValue = (selectValue(acf.point_type) || 'other') as LocalPointType
  const type: LocalPointType = typeValue in POINT_TYPE_LABELS ? typeValue : 'other'

  return {
    id: raw.id,
    slug: raw.slug,
    link: acf.external_url?.trim() || raw.link,
    title: decodeEntities(raw.title?.rendered ?? ''),
    lat,
    lng,
    type,
    typeLabel: selectLabel(acf.point_type) || POINT_TYPE_LABELS[type],
    description: acf.short_description?.trim() ?? '',
    image: toImage(acf.cover_image),
  }
}

/* ══════════════ public API ══════════════ */

const LOCATION_FIELDS = 'id,slug,link,parent,title,acf'
const POINT_FIELDS = 'id,slug,link,title,acf'

/**
 * Φέρνει τοποθεσίες και τοπικά σημεία από το WordPress.
 * Αν ένα endpoint δεν υπάρχει ακόμα (404 rest_no_route), επιστρέφει κενή λίστα
 * αντί να «σπάσει» ο χάρτης — η γεωγραφία δουλεύει και χωρίς CMS δεδομένα.
 */
export async function fetchWordPressMapData(
  apiRoot: string,
  endpoints: WordPressMapEndpoints = {},
): Promise<WordPressMapData & { warnings: string[] }> {
  const ep = { ...DEFAULT_ENDPOINTS, ...endpoints }
  const warnings: string[] = []
  const common = { acf_format: 'standard', status: 'publish' }

  const safe = async <T>(route: string, fields: string): Promise<T[]> => {
    try {
      return await fetchAllPages<T>(apiRoot, `wp/v2/${route}`, { ...common, _fields: fields })
    } catch (err) {
      if (err instanceof WordPressApiError && err.status === 404) {
        warnings.push(
          `Το endpoint wp/v2/${route} δεν υπάρχει. Έχεις εισάγει το acf-import.json στο ACF;`,
        )
        return []
      }
      throw err
    }
  }

  const [rawLocations, rawPoints] = await Promise.all([
    safe<WpLocationRaw>(ep.locations, LOCATION_FIELDS),
    safe<WpLocalPointRaw>(ep.localPoints, POINT_FIELDS),
  ])

  const missingAcf = [...rawLocations, ...rawPoints].some((r) => r.acf === undefined)
  if (missingAcf) {
    warnings.push(
      'Λείπει το πεδίο `acf` από το REST. Ενεργοποίησε «Show in REST API» στα ACF field groups.',
    )
  }

  const locations = inferLevels(rawLocations.map(mapLocation))
  const points: LocalPoint[] = []
  for (const raw of rawPoints) {
    const p = mapLocalPoint(raw)
    if (p) points.push(p)
    else
      warnings.push(
        `Το τοπικό σημείο «${decodeEntities(raw.title?.rendered ?? '')}» (#${raw.id}) δεν έχει έγκυρο lat/lng.`,
      )
  }

  return { locations, points, warnings }
}
