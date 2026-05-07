'use client'
import { useEffect, useMemo, useState } from 'react'
import { fetchWordPressMapData, type WordPressMapEndpoints } from '@/lib/wordpress/client'
import type { MapLocation, LocalPoint } from '@/lib/wordpress/types'
import {
  buildLocationIndex,
  placePoints,
  rewindCollection,
  type GeoCollection,
  type LocationIndex,
  type PlacedPoint,
} from './geo'

export type Settlement = { n: string; lat: number; lon: number }

export type GeoUrls = {
  regions: string
  prefectures: string
  municipalities: string
  /** Προαιρετικό: οικισμοί ανά δήμο (greece-settlements.json). */
  settlements?: string
}

export type MapDataConfig = {
  /** Ρίζα του WP REST API, π.χ. https://elladastopiato.gr/wp-json/ — αν λείπει, ο χάρτης δουλεύει χωρίς CMS δεδομένα. */
  apiRoot?: string
  endpoints?: WordPressMapEndpoints
  geoUrls: GeoUrls
}

const geoCache = new Map<string, Promise<unknown>>()
function loadJson<T>(url: string): Promise<T> {
  let p = geoCache.get(url)
  if (!p) {
    p = fetch(url).then((r) => {
      if (!r.ok) throw new Error(`HTTP ${r.status} για ${url}`)
      return r.json()
    })
    geoCache.set(url, p)
    p.catch(() => geoCache.delete(url))
  }
  return p as Promise<T>
}

export function useMapData({ apiRoot, endpoints, geoUrls }: MapDataConfig) {
  const [regions, setRegions] = useState<GeoCollection | null>(null)
  const [prefectures, setPrefectures] = useState<GeoCollection | null>(null)
  const [municipalities, setMunicipalities] = useState<GeoCollection | null>(null)
  const [settlements, setSettlements] = useState<Record<string, Settlement[]>>({})
  const [geoError, setGeoError] = useState<string | null>(null)

  const [locations, setLocations] = useState<MapLocation[]>([])
  const [rawPoints, setRawPoints] = useState<LocalPoint[]>([])
  const [wpStatus, setWpStatus] = useState<'idle' | 'loading' | 'ready' | 'error'>(
    apiRoot ? 'loading' : 'idle',
  )
  const [wpError, setWpError] = useState<string | null>(null)

  /* ── γεωγραφία (στατικά αρχεία) ── */
  useEffect(() => {
    let alive = true
    const fail = (e: unknown) => alive && setGeoError(e instanceof Error ? e.message : String(e))
    loadJson<GeoCollection>(geoUrls.regions)
      .then((g) => alive && setRegions(rewindCollection(g)))
      .catch(fail)
    loadJson<GeoCollection>(geoUrls.prefectures)
      .then((g) => alive && setPrefectures(rewindCollection(g)))
      .catch(fail)
    loadJson<GeoCollection>(geoUrls.municipalities)
      .then((g) => alive && setMunicipalities(rewindCollection(g)))
      .catch(fail)
    if (geoUrls.settlements) {
      loadJson<Record<string, Settlement[]>>(geoUrls.settlements)
        .then((s) => alive && setSettlements(s))
        .catch(fail)
    }
    return () => {
      alive = false
    }
  }, [geoUrls.regions, geoUrls.prefectures, geoUrls.municipalities, geoUrls.settlements])

  /* ── WordPress REST + ACF ── */
  const endpointsKey = JSON.stringify(endpoints ?? {})
  useEffect(() => {
    if (!apiRoot) {
      setWpStatus('idle')
      return
    }
    let alive = true
    setWpStatus('loading')
    fetchWordPressMapData(apiRoot, JSON.parse(endpointsKey))
      .then(({ locations, points, warnings }) => {
        if (!alive) return
        warnings.forEach((w) => console.warn(`[ΕλλάδαΣτοΠιάτο χάρτης] ${w}`))
        setLocations(locations)
        setRawPoints(points)
        setWpStatus('ready')
      })
      .catch((err) => {
        if (!alive) return
        console.error('[ΕλλάδαΣτοΠιάτο χάρτης] Αποτυχία φόρτωσης από WordPress:', err)
        setWpError(err instanceof Error ? err.message : String(err))
        setWpStatus('error')
      })
    return () => {
      alive = false
    }
  }, [apiRoot, endpointsKey])

  const locationIndex: LocationIndex = useMemo(() => buildLocationIndex(locations), [locations])

  const points: PlacedPoint[] = useMemo(() => {
    if (!rawPoints.length || !municipalities || !prefectures) return []
    const placed = placePoints(rawPoints, municipalities, prefectures)
    const lost = placed.filter((p) => !p.municipality)
    if (lost.length) {
      console.warn(
        '[ΕλλάδαΣτοΠιάτο χάρτης] Τοπικά σημεία εκτός ορίων (έλεγξε lat/lng):',
        lost.map((p) => `${p.title} (#${p.id}: ${p.lat}, ${p.lng})`),
      )
    }
    return placed
  }, [rawPoints, municipalities, prefectures])

  return {
    regions,
    prefectures,
    municipalities,
    settlements,
    geoError,
    locationIndex,
    points,
    wpStatus,
    wpError,
  }
}
