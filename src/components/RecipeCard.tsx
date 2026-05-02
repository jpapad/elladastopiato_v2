'use client'
import React from 'react'
import Image from 'next/image'
import Link from 'next/link'
import { motion } from 'framer-motion'
import { Clock, MapPin, ArrowRight } from 'lucide-react'
import { Recipe, Media, Location } from '@/payload-types'

export default function RecipeCard({ recipe }: { recipe: Recipe }) {
  const recipeImage = recipe.image as Media
  const recipeLocation = recipe.location as Location

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      className="group relative bg-white/[0.02] border border-white/5 rounded-[2.5rem] overflow-hidden hover:border-orange-500/30 transition-all duration-500"
    >
      <Link href={`/recipes/${recipe.id}`} className="block">
        {/* Image Container */}
        <div className="relative h-72 w-full overflow-hidden">
          {recipeImage?.url && (
            <Image
              src={recipeImage.url}
              alt={recipe.title}
              fill
              sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
              className="object-cover transition-transform duration-700 group-hover:scale-110"
              priority={false}
            />
          )}
          <div className="absolute inset-0 bg-gradient-to-t from-[#050505] via-transparent to-transparent opacity-60" />
          
          {/* Category Badge */}
          <div className="absolute top-6 left-6">
            <span className="px-4 py-2 bg-black/50 backdrop-blur-md border border-white/10 rounded-full text-[10px] font-black uppercase tracking-widest text-orange-500">
              {recipe.category || 'Συνταγή'}
            </span>
          </div>
        </div>

        {/* Content */}
        <div className="p-8">
          <div className="flex items-center gap-3 text-white/30 text-[10px] font-bold uppercase tracking-[0.2em] mb-4">
            <MapPin size={12} className="text-orange-500" />
            {recipeLocation?.name || 'Ελλάδα'}
          </div>
          
          <h3 className="text-2xl font-black uppercase italic tracking-tighter mb-6 group-hover:text-orange-500 transition-colors">
            {recipe.title}
          </h3>

          <div className="flex items-center justify-between pt-6 border-t border-white/5">
            <div className="flex items-center gap-4 text-white/40 text-[10px] font-bold uppercase tracking-widest">
              <span className="flex items-center gap-2"><Clock size={14} /> 20'</span>
            </div>
            <div className="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-orange-500 group-hover:text-black transition-all duration-500">
              <ArrowRight size={18} />
            </div>
          </div>
        </div>
      </Link>
    </motion.div>
  )
}