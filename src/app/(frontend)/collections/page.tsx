import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import Link from 'next/link'
import Image from 'next/image'
import { ArrowRight, Sparkles } from 'lucide-react'
import type { Metadata } from 'next'

export const dynamic = 'force-dynamic'

export const metadata: Metadata = {
  title: 'Συλλογές | Ελλάδα στο Πιάτο',
  description: 'Εποχιακές και θεματικές συλλογές παραδοσιακών ελληνικών συνταγών.',
}

const THEME_COLORS: Record<string, string> = {
  spring:    'from-green-400/20 to-emerald-300/10',
  summer:    'from-blue-400/20 to-cyan-300/10',
  autumn:    'from-orange-400/20 to-amber-300/10',
  winter:    'from-blue-800/20 to-indigo-900/10',
  easter:    'from-yellow-400/20 to-orange-300/10',
  christmas: 'from-red-500/20 to-green-600/10',
  fasting:   'from-purple-400/20 to-violet-300/10',
  general:   'from-orange-500/10 to-red-500/5',
}

export default async function CollectionsPage() {
  const payload = await getPayload({ config: configPromise })
  const { docs } = await payload.find({
    collection: 'collections',
    where: { active: { equals: true } },
    depth: 1,
    sort: 'title',
  })

  return (
    <div className="min-h-screen bg-white dark:bg-[#050505] pt-40 pb-24 px-6">
      <div className="max-w-7xl mx-auto">
        <header className="mb-20">
          <div className="flex items-center gap-3 text-orange-500 mb-4">
            <Sparkles size={20} />
            <span className="text-xs font-black uppercase tracking-[0.4em]">Επιμελημένες Συλλογές</span>
          </div>
          <h1 className="text-6xl md:text-9xl font-black uppercase italic tracking-tighter leading-[0.85]">
            Συλλογές
          </h1>
        </header>

        {docs.length === 0 ? (
          <div className="py-40 text-center border border-dashed border-black/10 dark:border-white/10 rounded-[3rem]">
            <p className="text-black/20 dark:text-white/20 uppercase tracking-widest text-xs italic">
              Δεν υπάρχουν ακόμα συλλογές.
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {docs.map((col: any) => {
              const imgUrl = (col.image as any)?.url
              const gradient = THEME_COLORS[col.theme] ?? THEME_COLORS.general
              const count = col.recipes?.length ?? 0

              return (
                <Link
                  key={col.id}
                  href={`/collections/${col.slug}`}
                  className="group relative block rounded-[2.5rem] overflow-hidden border border-black/5 dark:border-white/5 hover:border-orange-500/30 transition-all duration-700 min-h-[320px]"
                >
                  {imgUrl ? (
                    <Image src={imgUrl} alt={col.title} fill sizes="33vw" className="object-cover opacity-50 group-hover:opacity-70 group-hover:scale-105 transition-all duration-1000" />
                  ) : (
                    <div className={`absolute inset-0 bg-gradient-to-br ${gradient}`} />
                  )}
                  <div className="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent" />

                  <div className="absolute inset-0 flex flex-col justify-end p-10">
                    {col.emoji && (
                      <span className="text-4xl mb-4 block">{col.emoji}</span>
                    )}
                    <h2 className="text-3xl font-black uppercase italic tracking-tighter mb-3 text-white group-hover:text-orange-500 transition-colors">
                      {col.title}
                    </h2>
                    <div className="flex items-center justify-between border-t border-white/10 pt-4">
                      <span className="text-[10px] font-bold uppercase tracking-widest text-white/40">
                        {count > 0 ? `${count} Συνταγές` : 'Συλλογή'}
                      </span>
                      <ArrowRight size={16} className="text-orange-500 group-hover:translate-x-2 transition-transform" />
                    </div>
                  </div>
                </Link>
              )
            })}
          </div>
        )}
      </div>
    </div>
  )
}
