import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import type { Metadata } from 'next'
import RecipesClient from '@/components/RecipesClient'

export const dynamic = 'force-dynamic'

export const metadata: Metadata = {
  title: 'Συνταγές | Ελλάδα στο Πιάτο',
  description: 'Εξερεύνησε εκατοντάδες αυθεντικές ελληνικές συνταγές από κάθε γωνιά της χώρας.',
}

export default async function RecipesPage() {
  const payload = await getPayload({ config: configPromise })

  const { docs: recipes } = await payload.find({
    collection: 'recipes',
    limit: 200,
    depth: 1,
    sort: '-createdAt',
  })

  return (
    <div className="min-h-screen bg-white dark:bg-[#050505] pt-40 pb-24 px-6">
      <div className="max-w-7xl mx-auto">

        {/* Header */}
        <header className="mb-20">
          <span className="inline-block px-4 py-1 rounded-full border border-orange-500/30 text-orange-500 text-[10px] font-black uppercase tracking-[0.3em] mb-6">
            Γαστρονομική Κληρονομιά
          </span>
          <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <h1 className="text-7xl md:text-[9rem] font-black uppercase italic tracking-tighter leading-[0.85]">
              Ολες οι <br />
              <span className="text-orange-500">Συνταγες</span>
            </h1>
            <p className="text-black/40 dark:text-white/40 max-w-xs text-sm leading-relaxed md:text-right mb-2">
              Παραδοσιακές γεύσεις από κάθε περιοχή της Ελλάδας, μαζεμένες σε ένα μέρος.
            </p>
          </div>
        </header>

        <RecipesClient recipes={recipes} />
      </div>
    </div>
  )
}
