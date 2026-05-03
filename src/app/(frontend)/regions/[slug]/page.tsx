import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { notFound } from 'next/navigation'
import { MapPin, ChevronLeft } from 'lucide-react'
import Link from 'next/link'
import RecipeCard from '@/components/RecipeCard'
import type { Metadata } from 'next'

async function getLocation(slugOrId: string) {
  const payload = await getPayload({ config: configPromise })
  const bySlug = await payload.find({
    collection: 'locations',
    where: { slug: { equals: slugOrId } },
    limit: 1,
    depth: 1,
  })
  if (bySlug.docs[0]) return bySlug.docs[0]
  if (!isNaN(Number(slugOrId))) {
    return payload.findByID({ collection: 'locations', id: slugOrId, depth: 1 }).catch(() => null)
  }
  return null
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params
  const location = await getLocation(slug)
  if (!location) return { title: 'Περιοχή δεν βρέθηκε' }

  const imageUrl = (location.image as any)?.url || '/og-image.jpg'
  return {
    title: `${location.name} — Παραδοσιακές Συνταγές | Ελλάδα στο Πιάτο`,
    description: `Ανακαλύψτε τις αυθεντικές γεύσεις από την περιοχή ${location.name}. Δείτε όλες τις τοπικές συνταγές.`,
    openGraph: {
      title: location.name,
      description: `Παραδοσιακές συνταγές από ${location.name}`,
      images: [{ url: imageUrl }],
    },
  }
}

export default async function SingleRegionPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params
  const location = await getLocation(slug)
  if (!location) return notFound()

  const payload = await getPayload({ config: configPromise })
  const recipes = await payload.find({
    collection: 'recipes',
    where: {
      and: [
        { location: { equals: location.id } },
        { status: { equals: 'published' } },
      ],
    },
    depth: 1,
  })

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'CollectionPage',
    name: `Συνταγές από ${location.name}`,
    mainEntity: {
      '@type': 'ItemList',
      itemListElement: recipes.docs.map((recipe: any, index: number) => ({
        '@type': 'ListItem',
        position: index + 1,
        item: {
          '@type': 'Recipe',
          name: recipe.title,
          image: (recipe.image as any)?.url,
          prepTime: recipe.prepTime ? `PT${recipe.prepTime}M` : undefined,
          cookTime: recipe.cookTime ? `PT${recipe.cookTime}M` : undefined,
        },
      })),
    },
  }

  return (
    <div className="min-h-screen bg-white dark:bg-[#050505] pt-40 pb-24 px-6">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />

      <div className="max-w-7xl mx-auto">
        <Link
          href="/regions"
          className="inline-flex items-center gap-2 text-black/40 dark:text-white/40 hover:text-orange-500 transition-colors mb-12 uppercase text-[10px] font-black tracking-widest group"
        >
          <ChevronLeft size={14} className="group-hover:-translate-x-1 transition-transform" /> Πίσω στις Περιφέρειες
        </Link>

        <header className="mb-24 flex flex-col md:flex-row md:items-end justify-between gap-10">
          <div>
            <div className="flex items-center gap-3 text-orange-500 mb-4">
              <MapPin size={24} />
              <span className="text-xs font-black uppercase tracking-[0.4em]">Περιοχή</span>
            </div>
            <h1 className="text-7xl md:text-9xl font-black uppercase italic tracking-tighter leading-none">
              {location.name}
            </h1>
          </div>
          <div className="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 px-10 py-6 rounded-[2rem] backdrop-blur-xl">
            <span className="text-[10px] font-black uppercase tracking-widest text-black/40 dark:text-white/40 block mb-2">Συνταγές</span>
            <span className="text-4xl font-black italic text-orange-500">{recipes.totalDocs}</span>
          </div>
        </header>

        {recipes.totalDocs > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            {recipes.docs.map((recipe: any) => (
              <RecipeCard key={recipe.id} recipe={recipe} />
            ))}
          </div>
        ) : (
          <div className="py-40 text-center border border-dashed border-black/10 dark:border-white/10 rounded-[3rem]">
            <p className="text-black/20 dark:text-white/20 uppercase tracking-widest text-xs italic">
              Δεν έχουν προστεθεί ακόμα συνταγές για αυτή την περιοχή.
            </p>
          </div>
        )}
      </div>
    </div>
  )
}
