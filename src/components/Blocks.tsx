'use client'

import React from 'react'
import { Hero } from './Hero'
import MapSection from './MapSection'
import { RecipeGridBlock } from './RecipeGridBlock'
import { CategoriesBlock, StoryBlock, QuoteBlock, FeaturesBlock, CtaBlock } from './ExtraBlocks'

interface RenderBlocksProps {
  layout: any[]
}

export const RenderBlocks = ({ layout }: RenderBlocksProps) => {
  if (!layout || !Array.isArray(layout)) return null

  const handleLocationSelect = (id: string) => {
    window.location.href = `/regions/${id}`
  }

  return (
    <>
      {layout.map((block: any, index: number) => {
        switch (block.blockType) {
          case 'hero':
            return <Hero key={index} {...block} />

          case 'mapBlock':
            return (
              <section key={index} className="max-w-7xl mx-auto px-6 py-24 bg-[#050505]">
                {block.heading && <h2 className="text-4xl md:text-6xl font-black uppercase italic tracking-tighter mb-12">{block.heading}</h2>}
                <div className="rounded-[3rem] overflow-hidden border border-white/10 bg-white/[0.02] p-4">
                  <MapSection onLocationSelect={handleLocationSelect} />
                </div>
              </section>
            )

          case 'recipesBlock':
            return <RecipeGridBlock key={index} title={block.title} limit={block.limit} />

          case 'categoriesBlock':
            return <CategoriesBlock key={index} {...block} />

          case 'storyBlock':
            return <StoryBlock key={index} {...block} />

          case 'quoteBlock':
            return <QuoteBlock key={index} {...block} />

          case 'featuresBlock':
            return <FeaturesBlock key={index} {...block} />

          case 'ctaBlock':
            return <CtaBlock key={index} {...block} />

          case 'newsletterBlock':
            return (
              <section key={index} className="max-w-5xl mx-auto px-6 py-24 bg-white/[0.02] border border-white/5 rounded-[3rem] text-center my-20">
                <h2 className="text-3xl font-black uppercase italic mb-4">{block.title}</h2>
                {block.description && <p className="text-white/40 mb-10">{block.description}</p>}
                <div className="flex flex-col sm:flex-row max-w-md mx-auto gap-3 px-4">
                  <input type="email" placeholder="Το email σας" className="flex-1 bg-black/50 border border-white/10 rounded-full px-6 py-3 text-sm focus:border-orange-500 outline-none transition-colors" />
                  <button className="bg-orange-500 text-black px-8 py-3 rounded-full font-black uppercase text-[10px] tracking-widest hover:bg-white transition-colors">ΕΓΓΡΑΦΗ</button>
                </div>
              </section>
            )

          default:
            return null
        }
      })}
    </>
  )
}