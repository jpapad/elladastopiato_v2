import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import Link from 'next/link'
import { ArrowRight } from 'lucide-react'
import type { Metadata } from 'next'
import { CATEGORY_OPTIONS } from '@/lib/categories'

export const dynamic = 'force-dynamic'

export const metadata: Metadata = {
  title: 'Κατηγορίες Συνταγών | Ελλάδα στο Πιάτο',
  description: 'Εξερεύνησε τις κατηγορίες παραδοσιακών ελληνικών συνταγών.',
}

export default async function CategoriesPage() {
  const payload = await getPayload({ config: configPromise })

  const counts = await Promise.all(
    CATEGORY_OPTIONS.map(async (cat) => {
      const res = await payload.find({
        collection: 'recipes',
        where: { and: [{ recipeCategory: { equals: cat.value } }, { status: { equals: 'published' } }] },
        limit: 0,
      })
      return { ...cat, count: res.totalDocs }
    })
  )

  const BG_CLASSES = [
    'from-orange-500/10 to-red-500/5',
    'from-blue-500/10 to-cyan-500/5',
    'from-green-500/10 to-emerald-500/5',
    'from-purple-500/10 to-pink-500/5',
    'from-yellow-500/10 to-amber-500/5',
    'from-teal-500/10 to-cyan-500/5',
  ]

  return (
    <div className="min-h-screen bg-white dark:bg-[#0E0C0A] pt-40 pb-24 px-6">
      <div className="max-w-7xl mx-auto">
        <header className="mb-20">
          <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.5em] block mb-4">
            Γαστρονομικές Κατηγορίες
          </span>
          <h1 className="text-6xl md:text-9xl font-black uppercase italic tracking-tighter leading-[0.85]">
            Κατηγορίες
          </h1>
        </header>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {counts.map((cat, i) => (
            <Link
              key={cat.value}
              href={`/categories/${cat.value}`}
              className={`group relative p-10 rounded-[2.5rem] bg-gradient-to-br ${BG_CLASSES[i % BG_CLASSES.length]} border border-black/5 dark:border-white/5 hover:border-orange-500/30 transition-all duration-500 overflow-hidden`}
            >
              <div className="absolute -right-6 -bottom-6 text-[8rem] font-black italic opacity-[0.04] leading-none pointer-events-none select-none">
                {cat.label.slice(0, 2)}
              </div>
              <h2 className="text-3xl font-black uppercase italic tracking-tighter mb-3 group-hover:text-orange-500 transition-colors">
                {cat.label}
              </h2>
              <p className="text-black/30 dark:text-white/30 text-xs uppercase tracking-widest font-bold mb-6">
                {cat.count > 0 ? `${cat.count} συνταγές` : 'Έρχεται σύντομα'}
              </p>
              <div className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-orange-500 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-300">
                Δες Συνταγές <ArrowRight size={12} />
              </div>
            </Link>
          ))}
        </div>
      </div>
    </div>
  )
}
