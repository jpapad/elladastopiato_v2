/**
 * Τύποι για τα δεδομένα του χάρτη που έρχονται από το WordPress REST API + ACF Pro.
 *
 * Το WordPress πρέπει να έχει (δες wp-embed/wordpress-plugin/ellada-map/acf-import.json):
 *  - CPT `location`    → REST: /wp-json/wp/v2/locations     (Περιφέρειες → Νομοί → Δήμοι, ιεραρχικό)
 *  - CPT `local_point` → REST: /wp-json/wp/v2/local-points  (Τοπικά Σημεία με lat/lng)
 *  - ACF field groups με «Show in REST API» ενεργό.
 */

export type GeoLevel = 'region' | 'prefecture' | 'municipality'

/* ── Raw σχήματα όπως τα επιστρέφει το WP REST με ?acf_format=standard ── */

export type WpRendered = { rendered: string }

/** ACF Image field με return format «Image Array». */
export type AcfImage = {
  ID?: number
  id?: number
  url: string
  alt?: string
  width?: number
  height?: number
  sizes?: Record<string, string | number>
}

/** Ένα select field μπορεί να επιστρέφει value ή {value,label}, ανάλογα με το return format. */
export type AcfSelect = string | { value: string; label: string } | null | undefined

export type WpLocationAcf = {
  map_level?: AcfSelect
  geo_region?: AcfSelect
  geo_prefecture?: AcfSelect
  geo_municipality?: AcfSelect
  short_description?: string | null
  cover_image?: AcfImage | number | false | null
  local_products?: string | null
  best_time_to_visit?: AcfSelect
}

export type WpLocationRaw = {
  id: number
  slug: string
  link: string
  parent?: number
  title: WpRendered
  acf?: WpLocationAcf | [] // το ACF επιστρέφει [] όταν δεν υπάρχουν πεδία
}

export type WpLocalPointAcf = {
  lat?: string | number | null
  lng?: string | number | null
  point_type?: AcfSelect
  short_description?: string | null
  cover_image?: AcfImage | number | false | null
  external_url?: string | null
}

export type WpLocalPointRaw = {
  id: number
  slug: string
  link: string
  title: WpRendered
  acf?: WpLocalPointAcf | []
}

/* ── Καθαρά (normalized) μοντέλα που χρησιμοποιεί ο χάρτης ── */

export type MapImage = { url: string; alt: string }

export type MapLocation = {
  id: number
  slug: string
  link: string
  title: string
  parentId: number
  /** Επίπεδο από το ACF `map_level` ή, αν λείπει, από το βάθος στην ιεραρχία του WP. */
  level: GeoLevel | null
  /** Το όνομα του GeoJSON feature (name_greek) με το οποίο ταιριάζει η τοποθεσία. */
  geoName: string | null
  /** Μόνο για δήμους: ο νομός (prefecture_greek) του GeoJSON, για να ξεχωρίζουν ομώνυμοι δήμοι. */
  geoPrefecture: string | null
  description: string
  image: MapImage | null
  localProducts: string
}

export type LocalPointType =
  | 'product'
  | 'producer'
  | 'restaurant'
  | 'market'
  | 'festival'
  | 'sight'
  | 'other'

export type LocalPoint = {
  id: number
  slug: string
  /** Σύνδεσμος προορισμού: external_url αν υπάρχει, αλλιώς το permalink του WP. */
  link: string
  title: string
  lat: number
  lng: number
  type: LocalPointType
  typeLabel: string
  description: string
  image: MapImage | null
}

export type WordPressMapData = {
  locations: MapLocation[]
  points: LocalPoint[]
}
