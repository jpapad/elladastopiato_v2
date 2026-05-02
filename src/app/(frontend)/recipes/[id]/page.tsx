import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { notFound } from 'next/navigation'
import Link from 'next/link'
import Image from 'next/image'
import { Clock, Users, ChevronLeft, MapPin, Zap } from 'lucide-react'
import type { Metadata } from 'next'
import { RichText } from '@payloadcms/richtext-lexical/react'
import ShareButton from '@/components/ShareButton'

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }): Promise<Metadata> {
  const { id } = await params
  const payload = await getPayload({ config: configPromise })
  const recipe = await payload.findByID({ collection: 'recipes', id })
  if (!recipe) return { title: 'Recipe Not Found' }
  return { title: `${recipe.title} | Ελλάδα στο Πιάτο` }
}

export default async function RecipePage({ params }: { params: Promise<{ id: string }> }): Promise<React.ReactElement> {
  const { id } = await params
  const payload = await getPayload({ config: configPromise })
  const recipe = await payload.findByID({ collection: 'recipes', id, depth: 2 })

  if (!recipe) return notFound()

  return (
    <div className="min-h-screen bg-[#050505] text-white pb-24 font-sans antialiased">
      <div className="max-w-7xl mx-auto px-6">
        <div className="flex justify-between items-center mb-12">
            <Link href="/regions" className="inline-flex items-center gap-2 text-white/40 hover:text-orange-500 transition-all duration-300 uppercase text-[10px] font-black tracking-[0.3em] group">
                <ChevronLeft size={14} className="group-hover:-translate-x-1 transition-transform" /> Πισω στις περιοχες
            </Link>
            <ShareButton recipeTitle={recipe.title} />
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
          
          {/* LEFT COLUMN: IMAGE & TIPS */}
          <div className="flex flex-col gap-8">
            <div className="relative h-[500px] md:h-[700px] rounded-[3rem] overflow-hidden border border-white/10 group shadow-2xl">
              <Image 
                src={(recipe.image as any)?.url} 
                alt={recipe.title} 
                fill 
                priority
                sizes="(max-width: 1024px) 100vw, 50vw"
                className="object-cover transition-transform duration-1000 group-hover:scale-105"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent opacity-60" />
              <div className="absolute top-8 left-8 bg-orange-500 text-black px-6 py-2 rounded-full flex items-center gap-2 shadow-2xl z-10">
                  <Zap size={14} fill="black" />
                  <span className="text-[10px] font-black uppercase tracking-widest">{(recipe as any).recipeCategory}</span>
              </div>
            </div>

            {/* TIPS CARD */}
            {(recipe as any).tips && (
              <div className="bg-orange-500/5 border border-orange-500/10 p-10 rounded-[2.5rem] relative overflow-hidden group hover:bg-orange-500/[0.08] transition-colors duration-500">
                <div className="absolute -right-4 -top-4 opacity-[0.03] group-hover:opacity-[0.05] transition-opacity">
                   <Zap size={150} className="text-orange-500" />
                </div>
                <h3 className="text-xl font-black uppercase italic mb-6 text-orange-500 flex items-center gap-3 relative z-10">
                   <span className="w-2 h-2 rounded-full bg-orange-500 animate-pulse" />
                   Μυστικα Επιτυχιας
                </h3>
                <div className="prose prose-invert max-w-none text-white/60 text-sm leading-relaxed italic relative z-10">
                  <RichText data={(recipe as any).tips} />
                </div>
              </div>
            )}
          </div>

          {/* RIGHT COLUMN: CONTENT */}
          <div className="flex flex-col">
            <div className="flex items-center gap-3 text-orange-500 mb-6 font-black uppercase tracking-[0.4em] text-xs">
              <MapPin size={20} /> {(recipe.location as any)?.name}
            </div>

            <h1 className="text-6xl md:text-8xl font-black uppercase italic tracking-tighter leading-[0.9] mb-8">
              {recipe.title}
            </h1>

            {/* DESCRIPTION / STORY */}
            {(recipe as any).description && (
              <div className="prose prose-invert max-w-none text-white/50 mb-12 text-lg leading-relaxed italic border-l border-orange-500/20 pl-8">
                <RichText data={(recipe as any).description} />
              </div>
            )}

            {/* INFO GRID */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
              <div className="flex flex-col gap-1 bg-white/5 border border-white/10 px-6 py-4 rounded-3xl backdrop-blur-sm">
                <span className="text-[9px] font-black text-orange-500 uppercase tracking-widest">Προετοιμασια</span>
                <span className="text-lg font-bold">{(recipe as any).prepTime ? `${(recipe as any).prepTime}'` : '--'}</span>
              </div>
              <div className="flex flex-col gap-1 bg-white/5 border border-white/10 px-6 py-4 rounded-3xl backdrop-blur-sm">
                <span className="text-[9px] font-black text-orange-500 uppercase tracking-widest">Μαγειρεμα</span>
                <span className="text-lg font-bold">{(recipe as any).cookTime ? `${(recipe as any).cookTime}'` : '--'}</span>
              </div>
              <div className="flex flex-col gap-1 bg-orange-500/10 border border-orange-500/20 px-6 py-4 rounded-3xl backdrop-blur-sm text-orange-500">
                <span className="text-[9px] font-black uppercase tracking-widest">Συνολο</span>
                <span className="text-lg font-black">{((recipe as any).prepTime || 0) + ((recipe as any).cookTime || 0)}'</span>
              </div>
              <div className="flex flex-col gap-1 bg-white/5 border border-white/10 px-6 py-4 rounded-3xl backdrop-blur-sm">
                <span className="text-[9px] font-black text-orange-500 uppercase tracking-widest">Μεριδες</span>
                <span className="text-lg font-bold text-white">{(recipe as any).servings || '--'}</span>
              </div>
            </div>

            {/* TAGS & ALLERGENS */}
            <div className="flex flex-col gap-6 mb-12">
              {(recipe as any).tags && (
                <div className="flex flex-wrap gap-2">
                  {((recipe as any).tags as string[]).map((tag) => (
                    <span key={tag} className="flex items-center gap-2 bg-white/5 border border-white/10 px-4 py-2 rounded-xl text-[10px] font-black uppercase text-white/60">
                      <Zap size={10} className="text-orange-500" fill="#f97316" /> {tag}
                    </span>
                  ))}
                </div>
              )}
            </div>

            {/* INGREDIENTS & INSTRUCTIONS */}
            <div className="space-y-16 border-t border-white/10 pt-12">
              {recipe.ingredients && (
                <section>
                  <h3 className="text-2xl font-black uppercase italic mb-8 flex items-center gap-4">
                    <span className="w-8 h-[2px] bg-orange-500"></span> Υλικα
                  </h3>
                  <div className="prose prose-invert max-w-none text-white/70 leading-relaxed">
                    <RichText data={recipe.ingredients} />
                  </div>
                </section>
              )}
              {recipe.instructions && (
                <section>
                  <h3 className="text-2xl font-black uppercase italic mb-8 flex items-center gap-4">
                    <span className="w-8 h-[2px] bg-orange-500"></span> Εκτελεση
                  </h3>
                  <div className="prose prose-invert max-w-none text-white/70 leading-relaxed">
                    <RichText data={recipe.instructions} />
                  </div>
                </section>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}