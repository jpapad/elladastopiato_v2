#!/usr/bin/env node
/**
 * Πακετάρει το WordPress plugin «ellada-map» μετά το `vite build`:
 *   1. Ελαφραίνει τα GeoJSON (4 δεκαδικά ≈ 11 μ., μόνο τα απαραίτητα properties, σωστό winding)
 *      και τα αποθηκεύει ως .json (ώστε ο server να τα σερβίρει συμπιεσμένα).
 *   2. Παράγει το acf-import.json (post types + field groups) από τα GeoJSON.
 *   3. Φτιάχνει το wp-embed/dist/ellada-map.zip, έτοιμο για «Πρόσθετα → Ανέβασμα».
 *
 * Χρήση: npm run wp-map:build   (τρέχει πρώτα το vite build και μετά αυτό)
 */
import fs from 'node:fs'
import path from 'node:path'
import zlib from 'node:zlib'
import { fileURLToPath } from 'node:url'
import { geoArea } from 'd3-geo'
import { buildAcfImport } from './acf-definitions.mjs'

const here = path.dirname(fileURLToPath(import.meta.url))
const embedDir = path.resolve(here, '..')
const repoRoot = path.resolve(embedDir, '..')
const pluginDir = path.join(embedDir, 'wordpress-plugin', 'ellada-map')
const geoOut = path.join(pluginDir, 'assets', 'geo')
const distDir = path.join(embedDir, 'dist')

const readJson = (p) => JSON.parse(fs.readFileSync(p, 'utf8'))
const kb = (n) => `${(n / 1024).toFixed(0)} KB`

/* ══════════════ 1. GeoJSON ══════════════ */

const PRECISION = 1e4
const round = (n) => Math.round(n * PRECISION) / PRECISION

function simplifyRing(ring) {
  const out = []
  for (const [x, y] of ring) {
    const p = [round(x), round(y)]
    const last = out[out.length - 1]
    if (!last || last[0] !== p[0] || last[1] !== p[1]) out.push(p)
  }
  // ένας δακτύλιος χρειάζεται τουλάχιστον 4 σημεία (κλειστός)
  return out.length >= 4 ? out : ring.map(([x, y]) => [round(x), round(y)])
}

function simplifyFeature(f, keep) {
  const { type, coordinates } = f.geometry
  const coords =
    type === 'Polygon'
      ? coordinates.map(simplifyRing)
      : coordinates.map((poly) => poly.map(simplifyRing))
  let feature = {
    type: 'Feature',
    properties: Object.fromEntries(keep.map((k) => [k, f.properties[k] ?? ''])),
    geometry: { type, coordinates: coords },
  }
  // d3-geo: πολύγωνο με εμβαδόν > ημισφαίριο έχει ανάποδη φορά → αντιστροφή δακτυλίων
  if (geoArea(feature) > 2 * Math.PI) {
    const rev = (ring) => [...ring].reverse()
    feature.geometry.coordinates =
      type === 'Polygon' ? coords.map(rev) : coords.map((poly) => poly.map(rev))
  }
  return feature
}

const GEO_FILES = [
  { src: 'greece-regions.geojson', out: 'regions.json', keep: ['name', 'name_greek'] },
  {
    src: 'greece-prefectures.geojson',
    out: 'prefectures.json',
    keep: ['name', 'name_greek', 'periphery_greek'],
  },
  {
    src: 'greece-municipalities.geojson',
    out: 'municipalities.json',
    keep: ['name', 'name_greek', 'prefecture_greek'],
  },
]

fs.mkdirSync(geoOut, { recursive: true })
const geo = {}
for (const { src, out, keep } of GEO_FILES) {
  const srcPath = path.join(repoRoot, 'public', src)
  const raw = readJson(srcPath)
  const slim = {
    type: 'FeatureCollection',
    features: raw.features.map((f) => simplifyFeature(f, keep)),
  }
  const json = JSON.stringify(slim)
  fs.writeFileSync(path.join(geoOut, out), json)
  geo[out.replace('.json', '')] = slim
  console.log(
    `  geo  ${out.padEnd(20)} ${kb(fs.statSync(srcPath).size).padStart(7)} → ${kb(json.length)}`,
  )
}

// Οικισμοί (προαιρετικοί, data-settlements="true")
{
  const raw = readJson(path.join(repoRoot, 'public', 'greece-settlements.json'))
  const slim = Object.fromEntries(
    Object.entries(raw).map(([k, list]) => [
      k,
      list.map((s) => ({ n: s.n, lat: round(s.lat), lon: round(s.lon) })),
    ]),
  )
  const json = JSON.stringify(slim)
  fs.writeFileSync(path.join(geoOut, 'settlements.json'), json)
  console.log(`  geo  settlements.json     → ${kb(json.length)}`)
}

/* ══════════════ 2. ACF ══════════════ */

const acf = buildAcfImport(geo)
fs.writeFileSync(path.join(pluginDir, 'acf-import.json'), JSON.stringify(acf, null, 2) + '\n')
console.log(`  acf  acf-import.json       ${acf.length} αντικείμενα (2 post types, 2 field groups)`)

/* ══════════════ 2β. Leaflet (χάρτης επιλογής θέσης στο διαχειριστικό) ══════════════ */

const leafletSrc = path.join(repoRoot, 'node_modules', 'leaflet', 'dist')
const leafletOut = path.join(pluginDir, 'assets', 'vendor', 'leaflet')
fs.mkdirSync(path.join(leafletOut, 'images'), { recursive: true })
for (const f of ['leaflet.js', 'leaflet.css'])
  fs.copyFileSync(path.join(leafletSrc, f), path.join(leafletOut, f))
for (const f of fs.readdirSync(path.join(leafletSrc, 'images')))
  fs.copyFileSync(path.join(leafletSrc, 'images', f), path.join(leafletOut, 'images', f))
fs.copyFileSync(path.join(repoRoot, 'node_modules', 'leaflet', 'LICENSE'), path.join(leafletOut, 'LICENSE'))

/* ══════════════ 3. ZIP ══════════════ */

const CRC_TABLE = new Uint32Array(256).map((_, n) => {
  let c = n
  for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1
  return c >>> 0
})
const crc32 = (buf) => {
  let c = 0xffffffff
  for (let i = 0; i < buf.length; i++) c = CRC_TABLE[(c ^ buf[i]) & 0xff] ^ (c >>> 8)
  return (c ^ 0xffffffff) >>> 0
}

function listFiles(dir, prefix = '') {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
    const rel = prefix ? `${prefix}/${e.name}` : e.name
    if (e.name.startsWith('.')) return []
    return e.isDirectory() ? listFiles(path.join(dir, e.name), rel) : [rel]
  })
}

/** Ελάχιστος ZIP writer (deflate) — forward slashes, όπως απαιτεί το WordPress σε Linux servers. */
function createZip(rootDir, folderName, outFile) {
  const locals = []
  const centrals = []
  let offset = 0
  for (const rel of listFiles(rootDir).sort()) {
    const data = fs.readFileSync(path.join(rootDir, rel))
    const deflated = zlib.deflateRawSync(data, { level: 9 })
    const name = Buffer.from(`${folderName}/${rel}`, 'utf8')
    const crc = crc32(data)

    const local = Buffer.alloc(30)
    local.writeUInt32LE(0x04034b50, 0)
    local.writeUInt16LE(20, 4) // version needed
    local.writeUInt16LE(0x0800, 6) // UTF-8 filenames
    local.writeUInt16LE(8, 8) // deflate
    local.writeUInt16LE(0, 10) // time
    local.writeUInt16LE(0x21, 12) // date 1980-01-01
    local.writeUInt32LE(crc, 14)
    local.writeUInt32LE(deflated.length, 18)
    local.writeUInt32LE(data.length, 22)
    local.writeUInt16LE(name.length, 26)
    local.writeUInt16LE(0, 28)
    locals.push(local, name, deflated)

    const central = Buffer.alloc(46)
    central.writeUInt32LE(0x02014b50, 0)
    central.writeUInt16LE(20, 4)
    central.writeUInt16LE(20, 6)
    central.writeUInt16LE(0x0800, 8)
    central.writeUInt16LE(8, 10)
    central.writeUInt16LE(0, 12)
    central.writeUInt16LE(0x21, 14)
    central.writeUInt32LE(crc, 16)
    central.writeUInt32LE(deflated.length, 20)
    central.writeUInt32LE(data.length, 24)
    central.writeUInt16LE(name.length, 28)
    central.writeUInt32LE(offset, 42)
    centrals.push(central, name)

    offset += local.length + name.length + deflated.length
  }
  const centralSize = centrals.reduce((s, b) => s + b.length, 0)
  const end = Buffer.alloc(22)
  end.writeUInt32LE(0x06054b50, 0)
  end.writeUInt16LE(centrals.length / 2, 8)
  end.writeUInt16LE(centrals.length / 2, 10)
  end.writeUInt32LE(centralSize, 12)
  end.writeUInt32LE(offset, 16)
  fs.mkdirSync(path.dirname(outFile), { recursive: true })
  fs.writeFileSync(outFile, Buffer.concat([...locals, ...centrals, end]))
  return centrals.length / 2
}

const jsPath = path.join(pluginDir, 'assets', 'js', 'ellada-map.js')
if (!fs.existsSync(jsPath)) {
  console.error('\n✗ Λείπει το assets/js/ellada-map.js — τρέξε πρώτα: npm run wp-map:build')
  process.exit(1)
}
console.log(
  `  js   ellada-map.js         ${kb(fs.statSync(jsPath).size)} (gzip ≈ ${kb(zlib.gzipSync(fs.readFileSync(jsPath)).length)})`,
)

const zipPath = path.join(distDir, 'ellada-map.zip')
const count = createZip(pluginDir, 'ellada-map', zipPath)
console.log(
  `\n✓ ${path.relative(repoRoot, zipPath)} (${count} αρχεία, ${kb(fs.statSync(zipPath).size)})`,
)
