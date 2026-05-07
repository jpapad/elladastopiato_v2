'use client'

import React from 'react'
import { Hero } from './Hero'
import MapWrapper from './MapWrapper'
import { RecipeGridBlock } from './RecipeGridBlock'
import { CategoriesBlock, StoryBlock, QuoteBlock, FeaturesBlock, CtaBlock } from './ExtraBlocks'
import NewsletterForm from './NewsletterForm'

interface RenderBlocksProps {
  layout: any[]
}

export const RenderBlocks = ({ layout }: RenderBlocksProps) => {
  if (!layout || !Array.isArray(layout)) return null

  return (
    <>
      {layout.map((block: any, index: number) => {
        switch (block.blockType) {
          case 'hero':
            return <Hero key={index} {...block} />

          case 'mapBlock':
            return (
              <section key={index} className="max-w-7xl mx-auto px-6 py-24 bg-[#0E0C0A]">
                {block.heading && <h2 className="text-4xl md:text-6xl font-black uppercase italic tracking-tighter mb-12">{block.heading}</h2>}
                <div className="rounded-[3rem] overflow-hidden border border-white/10 bg-white/[0.02] p-4">
                  <MapWrapper />
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
              <NewsletterForm
                key={index}
                title={block.title || 'Newsletter'}
                description={block.description}
                buttonText={block.buttonText || 'Εγγραφή'}
              />
            )

          default:
            return null
        }
      })}
    </>
  )
}