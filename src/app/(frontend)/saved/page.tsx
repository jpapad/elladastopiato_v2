'use client'
import React from 'react'
import Link from 'next/link'
import Image from 'next/image'
import { Heart, Trash2, Clock, MapPin, ChevronLeft } from 'lucide-react'
import { useFavorites } from '@/hooks/useFavorites'
import { getCategoryLabel } from '@/lib/categories'
import { motion, AnimatePresence } from 'framer-motion'

export default function SavedPage() {
  const { favorites, remove, mounted } = useFavorites()

  return (
    <div className="min-h-screen bg-white dark:bg-[#0E0C0A] pt-40 pb-24 px-6">
      <div className="max-w-7xl mx-auto">

        <Link
          href="/recipes"
          className="inline-flex items-center gap-2 text-black/40 dark:text-white/40 hover:text-orange-500 transition-colors mb-12 uppercase text-[10px] font-black tracking-widest group"
        >
          <ChevronLeft size={14} className="group-hover:-translate-x-1 transition-transform" /> Πίσω στις Συνταγές
        </Link>

        <header className="mb-16">
          <div className="flex items-center gap-3 text-orange-500 mb-4">
            <Heart size={24} fill="currentColor" />
            <span className="text-xs font-black uppercase tracking-[0.4em]">Αγαπημένα</span>
          </div>
          <h1 className="text-7xl md:text-9xl font-black uppercase italic tracking-tighter leading-none">
            Αποθηκευμένα
          </h1>
        </header>

        {!mounted ? null : favorites.length === 0 ? (
          <div className="py-40 text-center border border-dashed border-black/10 dark:border-white/10 rounded-[3rem]">
            <Heart size={40} className="mx-auto mb-6 text-black/10 dark:text-white/10" />
            <p className="text-black/20 dark:text-white/20 uppercase tracking-widest text-xs italic mb-6">
              Δεν έχεις αποθηκεύσει ακόμα καμία συνταγή.
            </p>
            <Link
              href="/recipes"
              className="inline-flex items-center gap-2 bg-orange-500 text-black px-8 py-3 rounded-full text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-transform"
            >
              Εξερεύνησε Συνταγές
            </Link>
          </div>
        ) : (
          <>
            <p className="text-black/30 dark:text-white/30 text-xs uppercase tracking-widest font-bold mb-10">
              {favorites.length} {favorites.length === 1 ? 'συνταγή' : 'συνταγές'}
            </p>

            <AnimatePresence>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {favorites.map((recipe) => (
                  <motion.div
                    key={recipe.id}
                    layout
                    initial={{ opacity: 0, scale: 0.95 }}
                    animate={{ opacity: 1, scale: 1 }}
                    exit={{ opacity: 0, scale: 0.9 }}
                    transition={{ duration: 0.2 }}
                    className="group relative bg-black/[0.02] dark:bg-white/[0.02] border border-black/5 dark:border-white/5 rounded-[2.5rem] overflow-hidden hover:border-orange-500/30 transition-all duration-500"
                  >
                    <Link href={`/recipes/${recipe.slug}`}>
                      <div className="relative h-52 overflow-hidden">
                        {recipe.imageUrl ? (
                          <Image
                            src={recipe.imageUrl}
                            alt={recipe.title}
                            fill
                            sizes="(max-width: 768px) 100vw, 33vw"
                            className="object-cover grayscale group-hover:grayscale-0 group-hover:scale-105 transition-all duration-700"
                          />
                        ) : (
                          <div className="w-full h-full bg-orange-500/5 flex items-center justify-center">
                            <Heart size={32} className="text-orange-500/20" />
                          </div>
                        )}
                        <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
                        {recipe.recipeCategory && (
                          <span className="absolute top-4 left-4 bg-orange-500 text-black px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">
                            {getCategoryLabel(recipe.recipeCategory)}
                          </span>
                        )}
                      </div>

                      <div className="p-6">
                        <h3 className="text-lg font-black uppercase italic tracking-tight mb-3 group-hover:text-orange-500 transition-colors leading-tight">
                          {recipe.title}
                        </h3>
                        <div className="flex items-center gap-4 text-[10px] font-bold uppercase tracking-wider text-black/30 dark:text-white/30">
                          {recipe.location && (
                            <span className="flex items-center gap-1"><MapPin size={10} /> {recipe.location}</span>
                          )}
                          {recipe.totalTime ? (
                            <span className="flex items-center gap-1"><Clock size={10} /> {recipe.totalTime}'</span>
                          ) : null}
                        </div>
                      </div>
                    </Link>

                    <button
                      onClick={() => remove(recipe.id)}
                      className="absolute top-4 right-4 w-9 h-9 bg-black/60 backdrop-blur-sm rounded-full flex items-center justify-center text-white/60 hover:bg-red-500 hover:text-white transition-all duration-300 opacity-0 group-hover:opacity-100"
                      aria-label="Αφαίρεση"
                    >
                      <Trash2 size={14} />
                    </button>
                  </motion.div>
                ))}
              </div>
            </AnimatePresence>
          </>
        )}
      </div>
    </div>
  )
}
