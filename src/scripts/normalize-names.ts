import 'dotenv/config'
import { getPayload } from 'payload'
import configPromise from '../../src/payload.config'

function toTitleCase(str: string): string {
  // Only transform if all-caps
  if (str !== str.toUpperCase()) return str
  const exceptions = new Set(['ΚΑΙ', 'ΤΗΣ', 'ΤΟΥ', 'ΤΩΝ', 'ΤΗ', 'ΤΟ'])
  return str
    .split(' ')
    .map((w, i) => {
      if (i > 0 && exceptions.has(w)) return w.toLowerCase()
      return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()
    })
    .join(' ')
}

async function main() {
  const payload = await getPayload({ config: configPromise })
  const all = await payload.find({ collection: 'locations', limit: 200, depth: 0 })

  let updated = 0
  for (const loc of all.docs) {
    const fixed = toTitleCase(loc.name as string)
    if (fixed !== loc.name) {
      await payload.update({ collection: 'locations', id: loc.id, data: { name: fixed } })
      console.log(`  "${loc.name}" → "${fixed}"`)
      updated++
    }
  }
  console.log(`\n✓ Updated ${updated} names.`)
  process.exit(0)
}

main().catch(err => { console.error(err); process.exit(1) })
