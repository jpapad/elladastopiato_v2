import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { notFound } from 'next/navigation'
import Link from 'next/link'
import { ChevronLeft, Sparkles } from 'lucide-react'
import RecipeCard from '@/components/RecipeCard'
import type { Metadata } from 'next'

export const dynamic = 'force-dynamic'

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params
  const payload = await getPayload({ config: configPromise })
  const { docs } = await payload.find({ collection: 'collections', where: { slug: { equals: slug } }, limit: 1 })
  const col = docs[0]
  if (!col) return { title: 'Συλλογή δεν βρέθηκε' }
  return {
    title: `${(col as any).title} | Ελλάδα στο Πιάτο`,
    description: `Επιμελημένη συλλογή παραδοσιακών ελληνικών συνταγών: ${(col as any).title}.`,
  }
}

export default async function CollectionPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params
  const payload = await getPayload({ config: configPromise })

  const { docs } = await payload.find({
    collection: 'collections',
    where: { and: [{ slug: { equals: slug } }, { active: { equals: true } }] },
    limit: 1,
    depth: 2,
  })

  const col = docs[0] as any
  if (!col) return notFound()

  const recipes = Array.isArray(col.recipes) ? col.recipes.filter(Boolean) : []

  return (
    <div className="min-h-screen bg-white dark:bg-[#050505] pt-40 pb-24 px-6">
      <div className="max-w-7xl mx-auto">
        <Link
          href="/collections"
          className="inline-flex items-center gap-2 text-black/40 dark:text-white/40 hover:text-orange-500 transition-colors mb-12 uppercase text-[10px] font-black tracking-widest group"
        >
          <ChevronLeft size={14} className="group-hover:-translate-x-1 transition-transform" /> Πίσω στις Συλλογές
        </Link>

        <header className="mb-20 flex flex-col md:flex-row md:items-end justify-between gap-8">
          <div>
            <div className="flex items-center gap-3 text-orange-500 mb-4">
              {col.emoji ? <span className="text-3xl">{col.emoji}</span> : <Sparkles size={24} />}
              <span className="text-xs font-black uppercase tracking-[0.4em]">Συλλογή</span>
            </div>
            <h1 className="text-7xl md:text-9xl font-black uppercase italic tracking-tighter leading-none">
              {col.title}
            </h1>
          </div>
          <div className="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 px-10 py-6 rounded-[2rem]">
            <span className="text-[10px] font-black uppercase tracking-widest text-black/40 dark:text-white/40 block mb-2">Συνταγές</span>
            <span className="text-4xl font-black italic text-orange-500">{recipes.length}</span>
          </div>
        </header>

        {recipes.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {recipes.map((recipe: any) => (
              <RecipeCard key={recipe.id} recipe={recipe} />
            ))}
          </div>
        ) : (
          <div className="py-40 text-center border border-dashed border-black/10 dark:border-white/10 rounded-[3rem]">
            <p className="text-black/20 dark:text-white/20 uppercase tracking-widest text-xs italic">
              Δεν έχουν προστεθεί ακόμα συνταγές σε αυτή τη συλλογή.
            </p>
          </div>
        )}
      </div>
    </div>
  )
}
