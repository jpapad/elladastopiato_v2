'use client'
import React from 'react'
import { Heart } from 'lucide-react'
import { useFavorites, type FavoriteRecipe } from '@/hooks/useFavorites'

interface Props {
  recipe: FavoriteRecipe
}

export default function FavoriteButton({ recipe }: Props) {
  const { isFavorite, toggle, mounted } = useFavorites()

  if (!mounted) return <div className="w-10 h-10" />

  const saved = isFavorite(recipe.id)

  return (
    <button
      onClick={() => toggle(recipe)}
      aria-label={saved ? 'Αφαίρεση από αγαπημένα' : 'Προσθήκη στα αγαπημένα'}
      className={`no-print flex items-center gap-2 px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border transition-all duration-300 ${
        saved
          ? 'bg-orange-500 text-black border-orange-500'
          : 'bg-black/5 dark:bg-white/5 border-black/10 dark:border-white/10 hover:bg-orange-500 hover:text-black hover:border-orange-500'
      }`}
    >
      <Heart size={14} fill={saved ? 'currentColor' : 'none'} />
      {saved ? 'Αποθηκεύτηκε' : 'Αποθήκευση'}
    </button>
  )
}
