import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { Compass, ArrowRight } from 'lucide-react'
import Link from 'next/link'
import Image from 'next/image'
import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Περιοχές | Ελλάδα στο Πιάτο',
  description: 'Εξερευνήστε τις γαστρονομικές περιοχές της Ελλάδας και ανακαλύψτε τοπικές συνταγές.',
}

export default async function RegionsPage() {
  const payload = await getPayload({ config: configPromise })

  const [locationsRes, recipesRes] = await Promise.all([
    payload.find({ collection: 'locations', limit: 200, depth: 1, sort: 'name' }),
    payload.find({
      collection: 'recipes',
      limit: 0,
      depth: 0,
      where: { status: { equals: 'published' } },
    }),
  ])

  const locations = locationsRes.docs

  const recipeCountMap: Record<string, number> = {}
  for (const r of (recipesRes as any).docs ?? []) {
    const locId = typeof r.location === 'object' ? r.location?.id : r.location
    if (locId) recipeCountMap[locId] = (recipeCountMap[locId] || 0) + 1
  }

  const peripheries = locations.filter((l: any) => l.level === '1')

  return (
    <div className="min-h-screen bg-white dark:bg-[#050505] pt-40 pb-24 px-6 md:px-12">
      <div className="max-w-7xl mx-auto">
        <header className="mb-24">
          <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.5em] mb-6 block">
            Geographic Explorer
          </span>
          <h1 className="text-6xl md:text-9xl font-black uppercase italic tracking-tighter leading-[0.8] mb-8">
            ΟΙ ΤΟΠΟΙ <br /> <span className="text-black/10 dark:text-white/10">ΤΗΣ ΓΕΥΣΗΣ</span>
          </h1>
          <p className="text-black/30 dark:text-white/30 text-lg max-w-xl">
            Κάθε γωνιά της Ελλάδας κρύβει μοναδικές γεύσεις. Εξερευνήστε τις παραδοσιακές συνταγές κάθε περιφέρειας.
          </p>
        </header>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {peripheries.map((region: any) => {
            const recipeCount = recipeCountMap[region.id] || 0
            const imageUrl = (region.image as any)?.url

            return (
              <Link
                key={region.id}
                href={`/regions/${region.slug || region.id}`}
                className="group relative block h-[500px] overflow-hidden rounded-[3rem] bg-zinc-100 dark:bg-zinc-900 border border-black/5 dark:border-white/5 hover:border-orange-500/30 transition-all duration-700"
              >
                {imageUrl ? (
                  <Image
                    src={imageUrl}
                    alt={region.name}
                    fill
                    sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                    className="object-cover opacity-40 grayscale group-hover:grayscale-0 group-hover:scale-110 transition-all duration-1000"
                  />
                ) : (
                  <div className="absolute inset-0 bg-gradient-to-br from-orange-500/5 to-transparent" />
                )}

                <div className="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent z-10" />

                <div className="absolute inset-0 flex flex-col justify-end p-12 z-20">
                  <div className="flex items-center gap-3 text-orange-500 mb-4 opacity-0 group-hover:opacity-100 -translate-x-4 group-hover:translate-x-0 transition-all duration-300">
                    <Compass size={20} />
                    <span className="text-[10px] font-black uppercase tracking-widest">Εξερεύνηση</span>
                  </div>
                  <h3 className="text-5xl font-black uppercase italic tracking-tighter mb-4 text-white group-hover:text-orange-500 transition-colors">
                    {region.name}
                  </h3>
                  <div className="flex items-center justify-between border-t border-white/10 pt-6 mt-2">
                    <span className="text-[10px] font-bold uppercase tracking-widest text-white/40">
                      {recipeCount > 0 ? `${recipeCount} Συνταγές` : 'Τοπική Κουζίνα'}
                    </span>
                    <ArrowRight size={20} className="group-hover:translate-x-2 transition-transform text-orange-500" />
                  </div>
                </div>
              </Link>
            )
          })}
        </div>
      </div>
    </div>
  )
}
