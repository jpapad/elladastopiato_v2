/**
 * Γεωγραφικά helpers του χάρτη: προβολή/zoom, ονόματα, διόρθωση winding
 * και αντιστοίχιση των τοπικών σημείων (lat/lng) σε δήμο → νομό → περιφέρεια.
 */
import { geoArea, geoBounds, geoCentroid, geoContains, geoDistance } from 'd3-geo'
import type { MapLocation, LocalPoint } from '@/lib/wordpress/types'

/* eslint-disable @typescript-eslint/no-explicit-any */
export type GeoFeature = {
  type: 'Feature'
  geometry: { type: string; coordinates: any }
  properties: Record<string, any>
  rsmKey?: string
}
export type GeoCollection = { type: 'FeatureCollection'; features: GeoFeature[] }

export type ProjConfig = { scale: number; center: [number, number] }

export const GREECE_PROJ: ProjConfig = { scale: 2400, center: [24.51, 38.28] }
export const MAP_W = 800
export const MAP_H = 500

/* ══════════════ projection ══════════════ */

export function getBBox(features: GeoFeature[]) {
  let minLon = Infinity,
    maxLon = -Infinity,
    minLat = Infinity,
    maxLat = -Infinity
  const collect = (c: any) => {
    if (typeof c[0] === 'number') {
      if (c[0] < minLon) minLon = c[0]
      if (c[0] > maxLon) maxLon = c[0]
      if (c[1] < minLat) minLat = c[1]
      if (c[1] > maxLat) maxLat = c[1]
    } else c.forEach(collect)
  }
  features.forEach((f) => collect(f.geometry.coordinates))
  if (minLon === Infinity) return null
  return { minLon, maxLon, minLat, maxLat }
}

export function calcProjection(features: GeoFeature[], paddingFactor = 1.6): ProjConfig {
  const bb = getBBox(features)
  if (!bb) return GREECE_PROJ
  const { minLon, maxLon, minLat, maxLat } = bb
  const cx = (minLon + maxLon) / 2
  const cy = (minLat + maxLat) / 2
  const lonSpan = (maxLon - minLon) * paddingFactor
  const latSpan = (maxLat - minLat) * paddingFactor
  const effectiveSpan = Math.max(lonSpan, latSpan * (MAP_W / MAP_H), 0.05)
  const scale = Math.round((GREECE_PROJ.scale * 10.27) / effectiveSpan)
  return { scale, center: [cx, cy] }
}

export function filterGeo(geo: GeoCollection, key: string, value: string): GeoCollection {
  return {
    type: 'FeatureCollection',
    features: geo.features.filter((f) => f.properties[key] === value),
  }
}

/* ══════════════ winding fix ══════════════ */

/**
 * Το d3-geo θεωρεί ότι ένα πολύγωνο με «ανάποδη» φορά καλύπτει όλη τη σφαίρα εκτός από τον εαυτό του.
 * Το greece-prefectures.geojson έχει ακριβώς αυτό το πρόβλημα (RFC 7946 φορά), οπότε το hover/click και το
 * point-in-polygon χαλούσαν. Εδώ αντιστρέφουμε τους δακτυλίους όπου το εμβαδόν βγαίνει > ημισφαίριο.
 */
export function rewindCollection(geo: GeoCollection): GeoCollection {
  const reverseRings = (coords: any, type: string) =>
    type === 'Polygon'
      ? coords.map((ring: any[]) => [...ring].reverse())
      : coords.map((poly: any[]) => poly.map((ring: any[]) => [...ring].reverse()))

  return {
    ...geo,
    features: geo.features.map((f) => {
      if (!f.geometry || (f.geometry.type !== 'Polygon' && f.geometry.type !== 'MultiPolygon'))
        return f
      if (geoArea(f as any) <= 2 * Math.PI) return f
      return {
        ...f,
        geometry: {
          ...f.geometry,
          coordinates: reverseRings(f.geometry.coordinates, f.geometry.type),
        },
      }
    }),
  }
}

/* ══════════════ names ══════════════ */

export const featureName = (f: GeoFeature): string =>
  f.properties.name_greek || f.properties.name || ''

/** Κλειδί αναζήτησης: κεφαλαία, χωρίς τόνους/κενά/σημεία στίξης. «Χανιά» → «ΧΑΝΙΑ». */
export function normalizeGreek(value: string | null | undefined): string {
  return (value || '')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toUpperCase()
    .replace(/[^\p{L}\p{N}]+/gu, '')
}

/** Τα ονόματα δήμων στο GeoJSON είναι κολλητά («ΑγίουΕυστρατίου») — βάζουμε κενά για εμφάνιση. */
export function prettifyName(name: string): string {
  return name.replace(/(\p{Ll})(\p{Lu})/gu, '$1 $2')
}

export const municipalityKey = (prefecture: string, name: string) =>
  `${normalizeGreek(prefecture)}|${normalizeGreek(name)}`

/* ══════════════ WordPress location index ══════════════ */

export type LocationIndex = {
  region(name: string): MapLocation | undefined
  prefecture(name: string): MapLocation | undefined
  municipality(prefecture: string, name: string): MapLocation | undefined
}

export function buildLocationIndex(locations: MapLocation[]): LocationIndex {
  const regions = new Map<string, MapLocation>()
  const prefectures = new Map<string, MapLocation>()
  const munis = new Map<string, MapLocation>()
  const munisByName = new Map<string, MapLocation>()

  for (const loc of locations) {
    // Το ACF πεδίο έχει προτεραιότητα, αλλιώς δοκιμάζουμε τον τίτλο του post.
    const name = normalizeGreek(loc.geoName || loc.title)
    if (!name) continue
    if (loc.level === 'region') regions.set(name, loc)
    else if (loc.level === 'prefecture') prefectures.set(name, loc)
    else if (loc.level === 'municipality') {
      if (loc.geoPrefecture) munis.set(`${normalizeGreek(loc.geoPrefecture)}|${name}`, loc)
      munisByName.set(name, loc)
    }
  }

  return {
    region: (n) => regions.get(normalizeGreek(n)),
    prefecture: (n) => prefectures.get(normalizeGreek(n)),
    municipality: (p, n) => munis.get(municipalityKey(p, n)) ?? munisByName.get(normalizeGreek(n)),
  }
}

/* ══════════════ local points → areas ══════════════ */

export type PlacedPoint = LocalPoint & {
  region: string | null
  prefecture: string | null
  /** municipalityKey(prefecture, name) */
  municipality: string | null
}

type BBox = [[number, number], [number, number]]

/** Μέγιστη απόσταση (σε μοίρες) για σημεία λίγο έξω από τα —απλοποιημένα— όρια, π.χ. παραλιακές ταβέρνες. */
const COAST_TOLERANCE_DEG = 0.08

/**
 * Βρίσκει σε ποιον δήμο/νομό/περιφέρεια πέφτει κάθε σημείο, μόνο από τις συντεταγμένες του.
 * Έτσι ο συντάκτης στο WordPress συμπληρώνει μόνο lat/lng.
 */
export function placePoints(
  points: LocalPoint[],
  municipalities: GeoCollection,
  prefectures: GeoCollection,
): PlacedPoint[] {
  const prefToRegion = new Map<string, string>()
  for (const f of prefectures.features)
    prefToRegion.set(f.properties.name_greek, f.properties.periphery_greek)

  const munis = municipalities.features.map((f) => ({
    f,
    bbox: geoBounds(f as any) as BBox,
    centroid: geoCentroid(f as any) as [number, number],
  }))

  return points.map((p) => {
    const c: [number, number] = [p.lng, p.lat]
    const near = munis.filter(
      ({ bbox: [[x0, y0], [x1, y1]] }) =>
        c[0] >= x0 - COAST_TOLERANCE_DEG &&
        c[0] <= x1 + COAST_TOLERANCE_DEG &&
        c[1] >= y0 - COAST_TOLERANCE_DEG &&
        c[1] <= y1 + COAST_TOLERANCE_DEG,
    )
    const hit =
      near.find(({ f }) => geoContains(f as any, c)) ??
      near.sort((a, b) => geoDistance(a.centroid, c) - geoDistance(b.centroid, c))[0]

    if (!hit) return { ...p, region: null, prefecture: null, municipality: null }
    const pref: string = hit.f.properties.prefecture_greek || ''
    return {
      ...p,
      prefecture: pref || null,
      region: prefToRegion.get(pref) ?? null,
      municipality: municipalityKey(pref, hit.f.properties.name_greek),
    }
  })
}
