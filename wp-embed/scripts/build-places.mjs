/**
 * Οικισμοί (πόλεις, κωμοπόλεις, χωριά, μικροί οικισμοί) για τον χάρτη και τον τουριστικό οδηγό.
 *
 * Πηγή: wp-embed/data/osm-places.json (© OpenStreetMap contributors, ODbL).
 * Ανανέωση της πηγής: node wp-embed/scripts/build-places.mjs --fetch
 *
 * Παράγει στο assets/geo/:
 *   settlements/index.json   { "ΧΑΝΙΩΝ": "p52.json", … }  (νομός → αρχείο)
 *   settlements/pNN.json     { "Χανίων": [ {i,n,t,p,x,y}, … ] }  ανά δήμο, φορτώνεται όταν ανοίγει ο νομός
 *   places.json              όλοι οι οικισμοί σε γραμμές, για την αναζήτηση και τις σελίδες του οδηγού
 *   areas.json               στοιχεία «με μια ματιά» για περιφέρειες, νομούς, δήμους
 */
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { geoArea, geoBounds, geoCentroid, geoContains, geoDistance } from 'd3-geo'

const here = path.dirname(fileURLToPath(import.meta.url))
const embedDir = path.resolve(here, '..')
const SOURCE = path.join(embedDir, 'data', 'osm-places.json')

/** Είδος οικισμού: c = πόλη, t = κωμόπολη, v = χωριό, h = μικρός οικισμός. */
const TYPE = { city: 'c', town: 't', village: 'v', hamlet: 'h' }
const RANK = { c: 0, t: 1, v: 2, h: 3 }
const EARTH_R_KM = 6371.0088
const COAST_TOLERANCE_DEG = 0.15

export async function fetchPlaces() {
  const query =
    '[out:json][timeout:240];area["ISO3166-1"="GR"][admin_level=2]->.gr;node["place"~"^(city|town|village|hamlet)$"](area.gr);out body qt;'
  const res = await fetch('https://overpass-api.de/api/interpreter', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'User-Agent': 'ellada-map-build/1.0' },
    body: 'data=' + encodeURIComponent(query),
  })
  if (!res.ok) throw new Error(`Overpass HTTP ${res.status}`)
  const { elements } = await res.json()
  const places = []
  for (const e of elements) {
    const t = e.tags || {}
    const n = (t['name:el'] || t.name || '').trim()
    if (!n) continue
    const pop = String(t.population || '').replace(/[.\s]/g, '')
    places.push({
      id: e.id,
      n,
      en: t['name:en'] || t.int_name || '',
      place: t.place,
      pop: /^\d+$/.test(pop) ? +pop : null,
      popYear: (t['population:date'] || '').slice(0, 4) || null,
      lat: Math.round(e.lat * 1e5) / 1e5,
      lon: Math.round(e.lon * 1e5) / 1e5,
      wd: t.wikidata || null,
    })
  }
  const out = {
    source: '© OpenStreetMap contributors (ODbL), Overpass API',
    fetched: new Date().toISOString().slice(0, 10),
    places,
  }
  fs.mkdirSync(path.dirname(SOURCE), { recursive: true })
  fs.writeFileSync(SOURCE, JSON.stringify(out))
  return out
}

const km2 = (f) => Math.round(geoArea(f) * EARTH_R_KM * EARTH_R_KM)

/** geo = { regions, prefectures, municipalities } (ήδη ελαφριά και με σωστό winding). */
export function buildPlaces(geo, geoOut, log = console.log) {
  const src = JSON.parse(fs.readFileSync(SOURCE, 'utf8'))
  const prefToRegion = new Map(
    geo.prefectures.features.map((f) => [f.properties.name_greek, f.properties.periphery_greek]),
  )
  const munis = geo.municipalities.features.map((f) => ({
    f,
    name: f.properties.name_greek,
    pref: f.properties.prefecture_greek,
    bbox: geoBounds(f),
    centroid: geoCentroid(f),
  }))

  /* ── κάθε οικισμός → δήμος ── */
  const rows = []
  let lost = 0
  for (const p of src.places) {
    const c = [p.lon, p.lat]
    const near = munis.filter(
      ({ bbox: [[x0, y0], [x1, y1]] }) =>
        c[0] >= x0 - COAST_TOLERANCE_DEG &&
        c[0] <= x1 + COAST_TOLERANCE_DEG &&
        c[1] >= y0 - COAST_TOLERANCE_DEG &&
        c[1] <= y1 + COAST_TOLERANCE_DEG,
    )
    const hit =
      near.find(({ f }) => geoContains(f, c)) ??
      near.sort((a, b) => geoDistance(a.centroid, c) - geoDistance(b.centroid, c))[0]
    if (!hit) {
      lost++
      continue
    }
    rows.push({
      i: p.id,
      n: p.n,
      en: p.en,
      t: TYPE[p.place] || 'h',
      p: p.pop || 0,
      y: p.popYear || '',
      x: p.lon,
      yy: p.lat,
      muni: hit.name,
      pref: hit.pref,
      region: prefToRegion.get(hit.pref) || '',
    })
  }
  const order = (a, b) => RANK[a.t] - RANK[b.t] || b.p - a.p || a.n.localeCompare(b.n, 'el')
  rows.sort(order)

  /* ── αρχεία ανά νομό, για τον χάρτη ── */
  const dir = path.join(geoOut, 'settlements')
  fs.rmSync(dir, { recursive: true, force: true })
  fs.mkdirSync(dir, { recursive: true })
  const prefs = [...new Set(geo.prefectures.features.map((f) => f.properties.name_greek))].sort((a, b) =>
    a.localeCompare(b, 'el'),
  )
  const index = {}
  let total = 0
  prefs.forEach((pref, k) => {
    const file = `p${String(k + 1).padStart(2, '0')}.json`
    const byMuni = {}
    for (const r of rows) {
      if (r.pref !== pref) continue
      ;(byMuni[r.muni] ||= []).push({ i: r.i, n: r.n, t: r.t, p: r.p, x: r.x, y: r.yy })
    }
    index[pref] = file
    const json = JSON.stringify(byMuni)
    total += json.length
    fs.writeFileSync(path.join(dir, file), json)
  })
  fs.writeFileSync(path.join(dir, 'index.json'), JSON.stringify(index))

  /* ── όλοι οι οικισμοί σε γραμμές (PHP: αναζήτηση, σελίδες οδηγού) ── */
  const placesJson = JSON.stringify({
    source: src.source,
    fetched: src.fetched,
    cols: ['id', 'name', 'name_en', 'type', 'population', 'population_year', 'municipality', 'prefecture', 'region', 'lat', 'lon'],
    rows: rows.map((r) => [r.i, r.n, r.en, r.t, r.p, r.y, r.muni, r.pref, r.region, r.yy, r.x]),
  })
  fs.writeFileSync(path.join(geoOut, 'places.json'), placesJson)

  /* ── «Με μια ματιά» ── */
  const largest = (list) => (list.length ? list[0].n : '')
  const areas = { regions: {}, prefectures: {}, municipalities: {} }
  for (const f of geo.regions.features) {
    const name = f.properties.name_greek
    const list = rows.filter((r) => r.region === name)
    areas.regions[name] = {
      km2: km2(f),
      prefectures: geo.prefectures.features.filter((x) => x.properties.periphery_greek === name).length,
      municipalities: munis.filter((m) => prefToRegion.get(m.pref) === name).length,
      settlements: list.length,
      largest: largest(list),
    }
  }
  for (const f of geo.prefectures.features) {
    const name = f.properties.name_greek
    const list = rows.filter((r) => r.pref === name)
    const prev = areas.prefectures[name]
    areas.prefectures[name] = {
      km2: (prev?.km2 || 0) + km2(f),
      region: f.properties.periphery_greek,
      municipalities: munis.filter((m) => m.pref === name).length,
      settlements: list.length,
      largest: largest(list),
    }
  }
  for (const m of munis) {
    const list = rows.filter((r) => r.pref === m.pref && r.muni === m.name)
    areas.municipalities[`${m.pref}|${m.name}`] = {
      km2: km2(m.f),
      settlements: list.length,
      largest: largest(list),
    }
  }
  fs.writeFileSync(path.join(geoOut, 'areas.json'), JSON.stringify(areas))

  const kb = (n) => `${(n / 1024).toFixed(0)} KB`
  log(`  geo  settlements/          ${rows.length} οικισμοί σε ${prefs.length} αρχεία (${kb(total)})${lost ? `, ${lost} εκτός ορίων` : ''}`)
  log(`  geo  places.json           → ${kb(placesJson.length)}`)
  return rows
}

if (process.argv[1] === fileURLToPath(import.meta.url) && process.argv.includes('--fetch')) {
  const out = await fetchPlaces()
  console.log(`✓ ${out.places.length} οικισμοί από το OpenStreetMap → ${path.relative(process.cwd(), SOURCE)}`)
}
