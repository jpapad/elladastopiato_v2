import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { notFound } from 'next/navigation'
import Link from 'next/link'
import Image from 'next/image'
import { ChevronLeft, MapPin, Zap, AlertTriangle } from 'lucide-react'
import type { Metadata } from 'next'
import { RichText } from '@payloadcms/richtext-lexical/react'
import ShareButton from '@/components/ShareButton'
import RecipeCard from '@/components/RecipeCard'
import { getCategoryLabel } from '@/lib/categories'

async function getRecipe(slugOrId: string) {
  const payload = await getPayload({ config: configPromise })
  const bySlug = await payload.find({
    collection: 'recipes',
    where: { slug: { equals: slugOrId } },
    limit: 1,
    depth: 2,
  })
  if (bySlug.docs[0]) return bySlug.docs[0]
  if (!isNaN(Number(slugOrId))) {
    return payload.findByID({ collection: 'recipes', id: slugOrId, depth: 2 }).catch(() => null)
  }
  return null
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params
  const recipe = await getRecipe(slug)
  if (!recipe) return { title: 'Συνταγή δεν βρέθηκε' }
  const ogImage = (recipe as any).seo?.ogImage?.url || (recipe.image as any)?.url
  return {
    title: (recipe as any).seo?.metaTitle || `${recipe.title} | Ελλάδα στο Πιάτο`,
    description: (recipe as any).seo?.metaDesc,
    openGraph: {
      title: recipe.title,
      images: ogImage ? [{ url: ogImage }] : [],
    },
  }
}

export default async function RecipePage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params
  const recipe = await getRecipe(slug)
  if (!recipe) return notFound()

  const payload = await getPayload({ config: configPromise })
  const locationId = (recipe.location as any)?.id
  const related = await payload.find({
    collection: 'recipes',
    where: {
      and: [
        { id:             { not_equals: recipe.id } },
        { status:         { equals: 'published' } },
        { or: [
          { location:       { equals: locationId } },
          { recipeCategory: { equals: (recipe as any).recipeCategory } },
        ]},
      ],
    },
    limit: 3,
    depth: 1,
  })

  return (
    <div className="min-h-screen bg-white dark:bg-[#050505] pb-24 font-sans antialiased">
      <div className="max-w-7xl mx-auto px-6">

        {/* Top nav */}
        <div className="flex justify-between items-center pt-40 mb-12">
          <Link href="/recipes" className="inline-flex items-center gap-2 text-black/40 dark:text-white/40 hover:text-orange-500 transition-all uppercase text-[10px] font-black tracking-[0.3em] group">
            <ChevronLeft size={14} className="group-hover:-translate-x-1 transition-transform" /> Πίσω στις Συνταγές
          </Link>
          <ShareButton recipeTitle={recipe.title} />
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">

          {/* LEFT: image + tips */}
          <div className="flex flex-col gap-8">
            <div className="relative h-[500px] md:h-[700px] rounded-[3rem] overflow-hidden border border-black/10 dark:border-white/10 group shadow-2xl">
              {(recipe.image as any)?.url && (
                <Image
                  src={(recipe.image as any).url}
                  alt={recipe.title}
                  fill priority
                  sizes="(max-width: 1024px) 100vw, 50vw"
                  className="object-cover transition-transform duration-1000 group-hover:scale-105"
                />
              )}
              <div className="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent opacity-60" />
              <div className="absolute top-8 left-8 bg-orange-500 text-black px-6 py-2 rounded-full flex items-center gap-2 shadow-2xl z-10">
                <Zap size={14} fill="black" />
                <span className="text-[10px] font-black uppercase tracking-widest">
                  {getCategoryLabel((recipe as any).recipeCategory)}
                </span>
              </div>
            </div>

            {(recipe as any).tips && (
              <div className="bg-orange-500/5 border border-orange-500/10 p-10 rounded-[2.5rem] relative overflow-hidden group hover:bg-orange-500/[0.08] transition-colors duration-500">
                <div className="absolute -right-4 -top-4 opacity-[0.03]">
                  <Zap size={150} className="text-orange-500" />
                </div>
                <h3 className="text-xl font-black uppercase italic mb-6 text-orange-500 flex items-center gap-3 relative z-10">
                  <span className="w-2 h-2 rounded-full bg-orange-500 animate-pulse" /> Μυστικά Επιτυχίας
                </h3>
                <div className="prose dark:prose-invert max-w-none text-black/60 dark:text-white/60 text-sm leading-relaxed italic relative z-10">
                  <RichText data={(recipe as any).tips} />
                </div>
              </div>
            )}
          </div>

          {/* RIGHT: content */}
          <div className="flex flex-col">
            <div className="flex items-center gap-3 text-orange-500 mb-6 font-black uppercase tracking-[0.4em] text-xs">
              <MapPin size={20} /> {(recipe.location as any)?.name}
            </div>

            <h1 className="text-6xl md:text-8xl font-black uppercase italic tracking-tighter leading-[0.9] mb-8">
              {recipe.title}
            </h1>

            {(recipe as any).description && (
              <div className="prose dark:prose-invert max-w-none text-black/50 dark:text-white/50 mb-12 text-lg leading-relaxed italic border-l border-orange-500/20 pl-8">
                <RichText data={(recipe as any).description} />
              </div>
            )}

            {/* Info grid */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
              {[
                { label: 'Προετοιμασία', value: (recipe as any).prepTime ? `${(recipe as any).prepTime}'` : '--' },
                { label: 'Μαγείρεμα',   value: (recipe as any).cookTime  ? `${(recipe as any).cookTime}'`  : '--' },
                { label: 'Σύνολο',      value: `${((recipe as any).prepTime||0)+((recipe as any).cookTime||0)}'`, highlight: true },
                { label: 'Μερίδες',     value: (recipe as any).servings || '--' },
              ].map(({ label, value, highlight }) => (
                <div key={label} className={`flex flex-col gap-1 px-6 py-4 rounded-3xl backdrop-blur-sm border ${highlight ? 'bg-orange-500/10 border-orange-500/20 text-orange-500' : 'bg-black/5 dark:bg-white/5 border-black/10 dark:border-white/10'}`}>
                  <span className="text-[9px] font-black uppercase tracking-widest opacity-70">{label}</span>
                  <span className="text-lg font-black">{value}</span>
                </div>
              ))}
            </div>

            {/* Tags & Allergens */}
            <div className="flex flex-col gap-4 mb-12">
              {(recipe as any).tags?.length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {((recipe as any).tags as string[]).map(tag => (
                    <span key={tag} className="flex items-center gap-2 bg-orange-500/10 border border-orange-500/20 px-4 py-2 rounded-xl text-[10px] font-black uppercase text-orange-400">
                      <Zap size={10} fill="#f97316" /> {tag}
                    </span>
                  ))}
                </div>
              )}
              {(recipe as any).allergens?.length > 0 && (
                <div className="flex flex-wrap gap-2 items-center">
                  <span className="flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-black/30 dark:text-white/30 mr-1">
                    <AlertTriangle size={10} /> Αλλεργιογόνα:
                  </span>
                  {((recipe as any).allergens as string[]).map(a => (
                    <span key={a} className="bg-red-500/10 border border-red-500/20 px-3 py-1 rounded-lg text-[10px] font-bold uppercase text-red-400">
                      {a}
                    </span>
                  ))}
                </div>
              )}
            </div>

            {/* Ingredients & Instructions */}
            <div className="space-y-16 border-t border-black/10 dark:border-white/10 pt-12">
              {recipe.ingredients && (
                <section>
                  <h3 className="text-2xl font-black uppercase italic mb-8 flex items-center gap-4">
                    <span className="w-8 h-[2px] bg-orange-500" /> Υλικά
                  </h3>
                  <div className="prose dark:prose-invert max-w-none leading-relaxed">
                    <RichText data={recipe.ingredients} />
                  </div>
                </section>
              )}
              {recipe.instructions && (
                <section>
                  <h3 className="text-2xl font-black uppercase italic mb-8 flex items-center gap-4">
                    <span className="w-8 h-[2px] bg-orange-500" /> Εκτέλεση
                  </h3>
                  <div className="prose dark:prose-invert max-w-none leading-relaxed">
                    <RichText data={recipe.instructions} />
                  </div>
                </section>
              )}
            </div>
          </div>
        </div>

        {/* Related recipes */}
        {related.docs.length > 0 && (
          <section className="mt-32 border-t border-black/5 dark:border-white/5 pt-24">
            <div className="flex items-end justify-between mb-12">
              <h2 className="text-4xl font-black uppercase italic tracking-tighter">
                Σχετικές <span className="text-orange-500">Συνταγές</span>
              </h2>
              <Link href="/recipes" className="text-orange-500 font-black uppercase text-[10px] tracking-widest border-b border-orange-500/30 pb-1 hover:border-orange-500 transition-all">
                Δες Όλες
              </Link>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {related.docs.map((r: any) => (
                <RecipeCard key={r.id} recipe={r} />
              ))}
            </div>
          </section>
        )}

      </div>
    </div>
  )
}
