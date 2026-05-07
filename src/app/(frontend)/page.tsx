import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { HeroBlockComponent } from '@/components/blocks/HeroBlockComponent'
import { HeroSliderBlockComponent } from '@/components/blocks/HeroSliderBlockComponent'
import { TextSectionBlockComponent } from '@/components/blocks/TextSectionBlockComponent'
import { RecipesSectionBlockComponent } from '@/components/blocks/RecipesSectionBlockComponent'
import { MapSectionBlockComponent } from '@/components/blocks/MapSectionBlockComponent'
import { CtaSectionBlockComponent } from '@/components/blocks/CtaSectionBlockComponent'
// Keep old imports for fallback
import Link from 'next/link'
import Image from 'next/image'
import { ArrowRight, MapPin } from 'lucide-react'
import MapWrapper from '@/components/MapWrapper'

export const dynamic = 'force-dynamic'

export default async function HomePage() {
  const payload = await getPayload({ config: configPromise })

  const [homepageData, latestRecipes] = await Promise.all([
    payload.findGlobal({ slug: 'homepage' as any, depth: 2 }).catch(() => null),
    payload.find({ collection: 'recipes', limit: 3, sort: '-createdAt' }),
  ])

  const blocks = (homepageData as any)?.blocks

  // If blocks are configured, render them
  if (blocks && blocks.length > 0) {
    return (
      <main className="min-h-screen bg-white dark:bg-[#0E0C0A]">
        {blocks.map(async (block: any, i: number) => {
          if (block.blockType === 'hero') {
            return <HeroBlockComponent key={i} block={block} />
          }
          if (block.blockType === 'heroSlider') {
            return <HeroSliderBlockComponent key={i} block={block} />
          }
          if (block.blockType === 'textSection') {
            return <TextSectionBlockComponent key={i} block={block} />
          }
          if (block.blockType === 'mapSection') {
            return <MapSectionBlockComponent key={i} block={block} />
          }
          if (block.blockType === 'ctaSection') {
            return <CtaSectionBlockComponent key={i} block={block} />
          }
          if (block.blockType === 'recipesSection') {
            const recipes = await payload.find({
              collection: 'recipes',
              limit: block.limit || 3,
              sort: '-createdAt',
            })
            return <RecipesSectionBlockComponent key={i} block={block} recipes={recipes.docs} />
          }
          return null
        })}
      </main>
    )
  }

  // Fallback: hardcoded homepage (same as before)
  return (
    <main className="min-h-screen bg-white dark:bg-[#0E0C0A]">
      {/* HERO SECTION */}
      <section className="relative h-screen flex items-center overflow-hidden -mt-28">
        <div className="absolute inset-0 z-0">
          <div className="absolute inset-0 bg-gradient-to-r from-black via-black/60 to-transparent z-10" />
          <Image
            src="/hero-bg.jpg"
            alt="Greek Landscape"
            fill
            sizes="100vw"
            priority
            className="object-cover opacity-50 scale-110"
          />
        </div>
        <div className="max-w-7xl mx-auto px-6 relative z-20 w-full">
          <div className="max-w-3xl">
            <span className="inline-block px-4 py-1 rounded-full border border-orange-500/30 text-orange-500 text-[10px] font-black uppercase tracking-[0.3em] mb-6">
              Αυθεντικες Ελληνικες Γευσεις
            </span>
            <h1 className="text-5xl md:text-7xl font-black uppercase italic tracking-tighter leading-[0.85] mb-8 text-white">
              Η Ελλαδα <br /> <span className="text-orange-500">στο Πιατο</span> σου.
            </h1>
            <p className="text-lg text-white/60 mb-12 max-w-xl leading-relaxed">
              Εξερεύνησε την γαστρονομική κληρονομιά της Ελλάδας μέσα από παραδοσιακές συνταγές, τοπικά υλικά και μυστικά από κάθε γωνιά της χώρας.
            </p>
            <Link href="/recipes" className="bg-orange-500 text-black px-10 py-5 rounded-full font-black uppercase tracking-widest hover:bg-white transition-all inline-flex items-center gap-3 group text-sm">
              Ανακαλυψε Συνταγες <ArrowRight size={18} className="group-hover:translate-x-2 transition-transform" />
            </Link>
          </div>
        </div>
      </section>

      {/* MAP SECTION */}
      <section className="py-20 px-6 max-w-7xl mx-auto">
        <div className="mb-12">
          <h2 className="text-5xl font-black uppercase italic tracking-tighter">Εξερευνησε <br /> <span className="text-orange-500">Ανα Περιοχη</span></h2>
        </div>
        <MapWrapper />
      </section>

      {/* LATEST RECIPES */}
      <section className="py-32 px-6 max-w-7xl mx-auto">
        <div className="flex justify-between items-end mb-16">
          <h2 className="text-5xl font-black uppercase italic tracking-tighter">Προσφατες <br /> Συνταγες</h2>
          <Link href="/recipes" className="text-orange-500 font-black uppercase text-[10px] tracking-widest border-b border-orange-500/30 pb-2 hover:border-orange-500 transition-all">
            Δες Ολες τις συνταγες
          </Link>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {latestRecipes.docs.map((recipe: any) => (
            <Link key={recipe.id} href={`/recipes/${recipe.id}`} className="group">
              <div className="relative h-[500px] rounded-[3rem] overflow-hidden mb-6 border border-black/10 dark:border-white/10">
                <Image
                  src={recipe.image?.url}
                  alt={recipe.title}
                  fill
                  sizes="(max-width: 768px) 100vw, 33vw"
                  className="object-cover transition-transform duration-700 group-hover:scale-110"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent opacity-80" />
                <div className="absolute bottom-8 left-8 right-8">
                  <div className="flex items-center gap-2 text-orange-500 mb-2 font-black uppercase tracking-widest text-[10px]">
                    <MapPin size={12} /> {(recipe.location as any)?.name}
                  </div>
                  <h3 className="text-3xl font-black uppercase italic tracking-tighter leading-none text-white">{recipe.title}</h3>
                </div>
              </div>
            </Link>
          ))}
        </div>
      </section>
    </main>
  )
}
