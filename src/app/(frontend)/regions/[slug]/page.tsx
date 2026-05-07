import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { notFound } from 'next/navigation'
import { MapPin, ChevronLeft, Calendar, Navigation, ShoppingBag, Lightbulb, Star } from 'lucide-react'
import Link from 'next/link'
import Image from 'next/image'
import RecipeCard from '@/components/RecipeCard'
import type { Metadata } from 'next'

export const dynamic = 'force-dynamic'

const SEASON_LABELS: Record<string, string> = {
  all_year: 'Όλο τον χρόνο',
  spring: 'Άνοιξη (Μάρτιος – Μάιος)',
  summer: 'Καλοκαίρι (Ιούνιος – Αύγουστος)',
  autumn: 'Φθινόπωρο (Σεπτέμβριος – Νοέμβριος)',
  winter: 'Χειμώνας (Δεκέμβριος – Φεβρουάριος)',
}

async function getLocation(slugOrId: string) {
  const payload = await getPayload({ config: configPromise })
  const bySlug = await payload.find({
    collection: 'locations',
    where: { slug: { equals: slugOrId } },
    limit: 1,
    depth: 2,
  })
  if (bySlug.docs[0]) return bySlug.docs[0]
  if (!isNaN(Number(slugOrId))) {
    return payload.findByID({ collection: 'locations', id: slugOrId, depth: 2 }).catch(() => null)
  }
  return null
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params
  const location = await getLocation(slug)
  if (!location) return { title: 'Περιοχή δεν βρέθηκε' }

  const imageUrl = (location.image as any)?.url || '/og-image.jpg'
  return {
    title: `${location.name} — Γαστρονομικός & Ταξιδιωτικός Οδηγός | Ελλάδα στο Πιάτο`,
    description: (location.description as string) || `Ανακαλύψτε τις αυθεντικές γεύσεις και τα αξιοθέατα από την περιοχή ${location.name}.`,
    openGraph: {
      title: location.name,
      description: `Γαστρονομικός οδηγός για ${location.name}`,
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

  const loc = location as any
  const gallery: any[] = loc.gallery || []
  const hasTravelInfo = loc.highlights || loc.howToGetThere || loc.localProducts || loc.travelTips || loc.bestTimeToVisit
  const heroImage = loc.image?.url

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'TouristDestination',
    name: location.name,
    description: loc.description,
    image: heroImage,
    mainEntity: {
      '@type': 'ItemList',
      itemListElement: recipes.docs.map((recipe: any, index: number) => ({
        '@type': 'ListItem',
        position: index + 1,
        item: { '@type': 'Recipe', name: recipe.title, image: (recipe.image as any)?.url },
      })),
    },
  }

  return (
    <div className="min-h-screen bg-white dark:bg-[#0E0C0A]">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />

      {/* Hero */}
      <div className="relative h-[70vh] flex items-end overflow-hidden">
        {heroImage ? (
          <>
            <div className="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-black/10 z-10" />
            <Image src={heroImage} alt={location.name} fill sizes="100vw" priority className="object-cover" />
          </>
        ) : (
          <div className="absolute inset-0 bg-gradient-to-br from-orange-500/20 to-black" />
        )}

        <div className="relative z-20 w-full max-w-7xl mx-auto px-6 pb-16">
          <Link
            href="/regions"
            className="inline-flex items-center gap-2 text-white/50 hover:text-orange-500 transition-colors mb-8 uppercase text-[10px] font-black tracking-widest group"
          >
            <ChevronLeft size={14} className="group-hover:-translate-x-1 transition-transform" /> Όλες οι Περιοχές
          </Link>

          <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
              <div className="flex items-center gap-2 text-orange-500 mb-3">
                <MapPin size={16} />
                <span className="text-[10px] font-black uppercase tracking-[0.4em]">
                  {loc.level === '1' ? 'Περιφέρεια' : loc.level === '2' ? 'Νομός' : 'Δήμος'}
                </span>
              </div>
              <h1 className="text-6xl md:text-8xl font-black uppercase italic tracking-tighter leading-none text-white">
                {location.name}
              </h1>
              {loc.description && (
                <p className="text-white/60 mt-4 max-w-xl text-base leading-relaxed">{loc.description}</p>
              )}
            </div>

            <div className="flex gap-4 shrink-0">
              <div className="bg-black/40 backdrop-blur-xl border border-white/10 px-6 py-4 rounded-2xl text-center">
                <span className="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Συνταγές</span>
                <span className="text-3xl font-black italic text-orange-500">{recipes.totalDocs}</span>
              </div>
              {gallery.length > 0 && (
                <div className="bg-black/40 backdrop-blur-xl border border-white/10 px-6 py-4 rounded-2xl text-center">
                  <span className="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Gallery</span>
                  <span className="text-3xl font-black italic text-orange-500">{gallery.length}</span>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-6 py-20">

        {/* Travel Info */}
        {hasTravelInfo && (
          <section className="mb-24">
            <h2 className="text-3xl font-black uppercase italic tracking-tighter mb-10">
              Ταξιδιωτικός <span className="text-orange-500">Οδηγός</span>
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">

              {loc.highlights && (
                <div className="bg-black/[0.03] dark:bg-white/[0.03] border border-black/5 dark:border-white/5 rounded-3xl p-8">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-10 h-10 bg-orange-500/10 rounded-full flex items-center justify-center">
                      <Star size={18} className="text-orange-500" />
                    </div>
                    <h3 className="font-black uppercase tracking-widest text-[11px]">Highlights</h3>
                  </div>
                  <p className="text-black/60 dark:text-white/60 leading-relaxed whitespace-pre-line">{loc.highlights}</p>
                </div>
              )}

              {loc.bestTimeToVisit && (
                <div className="bg-black/[0.03] dark:bg-white/[0.03] border border-black/5 dark:border-white/5 rounded-3xl p-8">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-10 h-10 bg-orange-500/10 rounded-full flex items-center justify-center">
                      <Calendar size={18} className="text-orange-500" />
                    </div>
                    <h3 className="font-black uppercase tracking-widest text-[11px]">Καλύτερη Εποχή</h3>
                  </div>
                  <p className="text-black/80 dark:text-white/80 font-bold text-lg">
                    {SEASON_LABELS[loc.bestTimeToVisit] || loc.bestTimeToVisit}
                  </p>
                </div>
              )}

              {loc.howToGetThere && (
                <div className="bg-black/[0.03] dark:bg-white/[0.03] border border-black/5 dark:border-white/5 rounded-3xl p-8">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-10 h-10 bg-orange-500/10 rounded-full flex items-center justify-center">
                      <Navigation size={18} className="text-orange-500" />
                    </div>
                    <h3 className="font-black uppercase tracking-widest text-[11px]">Πώς να Φτάσεις</h3>
                  </div>
                  <p className="text-black/60 dark:text-white/60 leading-relaxed whitespace-pre-line">{loc.howToGetThere}</p>
                </div>
              )}

              {loc.localProducts && (
                <div className="bg-black/[0.03] dark:bg-white/[0.03] border border-black/5 dark:border-white/5 rounded-3xl p-8">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-10 h-10 bg-orange-500/10 rounded-full flex items-center justify-center">
                      <ShoppingBag size={18} className="text-orange-500" />
                    </div>
                    <h3 className="font-black uppercase tracking-widest text-[11px]">Τοπικά Προϊόντα</h3>
                  </div>
                  <p className="text-black/60 dark:text-white/60 leading-relaxed whitespace-pre-line">{loc.localProducts}</p>
                </div>
              )}

              {loc.travelTips && (
                <div className="md:col-span-2 bg-orange-500/5 border border-orange-500/20 rounded-3xl p-8">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-10 h-10 bg-orange-500/10 rounded-full flex items-center justify-center">
                      <Lightbulb size={18} className="text-orange-500" />
                    </div>
                    <h3 className="font-black uppercase tracking-widest text-[11px]">Συμβουλές Ταξιδιού</h3>
                  </div>
                  <p className="text-black/60 dark:text-white/60 leading-relaxed whitespace-pre-line">{loc.travelTips}</p>
                </div>
              )}
            </div>
          </section>
        )}

        {/* Gallery */}
        {gallery.length > 0 && (
          <section className="mb-24">
            <h2 className="text-3xl font-black uppercase italic tracking-tighter mb-10">
              Gallery <span className="text-orange-500">Φωτογραφιών</span>
            </h2>
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              {gallery.map((item: any, i: number) => (
                <div key={i} className="relative aspect-square rounded-2xl overflow-hidden group">
                  <Image
                    src={item.image?.url}
                    alt={item.caption || location.name}
                    fill
                    sizes="(max-width: 768px) 50vw, 25vw"
                    className="object-cover group-hover:scale-110 transition-transform duration-700"
                  />
                  {item.caption && (
                    <div className="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                      <p className="text-white text-xs font-bold">{item.caption}</p>
                    </div>
                  )}
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Recipes */}
        <section>
          <div className="flex items-end justify-between mb-12">
            <h2 className="text-3xl font-black uppercase italic tracking-tighter">
              Τοπικές <span className="text-orange-500">Συνταγές</span>
            </h2>
            <span className="text-black/30 dark:text-white/30 text-[10px] font-black uppercase tracking-widest">
              {recipes.totalDocs} συνταγές
            </span>
          </div>

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
        </section>
      </div>
    </div>
  )
}
