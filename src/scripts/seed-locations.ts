/**
 * Seed script: syncs Payload locations with GeoJSON names.
 * Run: npx tsx src/scripts/seed-locations.ts
 *
 * What it does:
 *  1. Updates/renames the 9 existing level-1 locations to match GeoJSON names
 *  2. Creates the 4 missing level-1 locations
 *  3. Creates all 74 level-2 (νομοί) with correct parent links
 */

import 'dotenv/config'
import { getPayload } from 'payload'
import configPromise from '../../src/payload.config'
import { readFileSync } from 'fs'
import { join } from 'path'

const prefecturesRaw = JSON.parse(
  readFileSync(join(process.cwd(), 'public/greece-prefectures.geojson'), 'utf8')
)

/* ── The 13 official GeoJSON periphery names ── */
const REGIONS_GEO = [
  'Ανατολική Μακεδονία και Θράκη',
  'Κεντρική Μακεδονία',
  'Δυτική Μακεδονία',
  'Ήπειρος',
  'Θεσσαλία',
  'Ιόνια Νησιά',
  'Δυτική Ελλάδα',
  'Κεντρική Ελλάδα',
  'Αττική',
  'Πελοπόννησος',
  'Βόρειο Αιγαίο',
  'Νότιο Αιγαίο',
  'Κρήτη',
]

/* ── Map old informal name → correct GeoJSON name ── */
const RENAME_MAP: Record<string, string> = {
  'Θράκη':        'Ανατολική Μακεδονία και Θράκη',
  'Μακεδονία':    'Κεντρική Μακεδονία',
  'Ήπειρος':      'Ήπειρος',
  'Θεσσαλία':     'Θεσσαλία',
  'Επτάνησα':     'Ιόνια Νησιά',
  'Στερεά Ελλάδα':'Κεντρική Ελλάδα',
  'Νησιά Αιγαίου':'Νότιο Αιγαίο',
  'Πελοπόννησος': 'Πελοπόννησος',
  'Κρήτη':        'Κρήτη',
}

async function main() {
  const payload = await getPayload({ config: configPromise })

  /* ── 1. Update existing level-1 locations ── */
  console.log('\n── Updating existing level-1 locations ──')
  const existing = await payload.find({ collection: 'locations', limit: 200, depth: 0 })
  const level1Map: Record<string, number> = {} // geoName → payload id

  for (const loc of existing.docs) {
    const correctName = RENAME_MAP[loc.name as string]
    if (!correctName) { console.log(`  SKIP unknown: ${loc.name}`); continue }

    if (loc.name !== correctName) {
      await payload.update({ collection: 'locations', id: loc.id, data: { name: correctName } })
      console.log(`  RENAMED: "${loc.name}" → "${correctName}" (id:${loc.id})`)
    } else {
      console.log(`  OK: "${loc.name}" (id:${loc.id})`)
    }
    level1Map[correctName] = loc.id as number
  }

  /* ── 2. Create missing level-1 locations ── */
  console.log('\n── Creating missing level-1 locations ──')
  for (const geoName of REGIONS_GEO) {
    if (level1Map[geoName]) continue
    const created = await payload.create({
      collection: 'locations',
      data: { name: geoName, level: '1' },
    })
    level1Map[geoName] = created.id as number
    console.log(`  CREATED: "${geoName}" (id:${created.id})`)
  }

  /* ── 3. Create level-2 (νομοί) ── */
  console.log('\n── Creating level-2 locations (νομοί) ──')

  // fetch existing level-2 to avoid duplicates
  const existingL2 = await payload.find({
    collection: 'locations',
    where: { level: { equals: '2' } },
    limit: 200,
    depth: 0,
  })
  const existingL2Names = new Set(existingL2.docs.map((d: any) => d.name as string))

  const prefectures = (prefecturesRaw as any).features as Array<{
    properties: { name_greek: string; periphery_greek: string }
  }>

  // deduplicate prefecture names per periphery
  const seen = new Set<string>()
  let created = 0, skipped = 0

  for (const feat of prefectures) {
    const { name_greek, periphery_greek } = feat.properties
    const key = `${periphery_greek}::${name_greek}`
    if (seen.has(key)) continue
    seen.add(key)

    if (existingL2Names.has(name_greek)) { skipped++; continue }

    const parentId = level1Map[periphery_greek]
    if (!parentId) {
      console.log(`  WARN: no parent for "${name_greek}" (periphery: "${periphery_greek}")`)
      continue
    }

    await payload.create({
      collection: 'locations',
      data: { name: name_greek, level: '2', parent: parentId },
    })
    created++
    process.stdout.write(`  + ${name_greek}\n`)
  }
  console.log(`  Created: ${created}, Skipped (existing): ${skipped}`)

  /* ── Summary ── */
  const total = await payload.find({ collection: 'locations', limit: 0 })
  console.log(`\n✓ Done. Total locations in DB: ${total.totalDocs}`)
  process.exit(0)
}

main().catch(err => { console.error(err); process.exit(1) })
