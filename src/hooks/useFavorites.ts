'use client'
import { useState, useEffect, useCallback } from 'react'

export interface FavoriteRecipe {
  id: string
  title: string
  slug: string
  imageUrl?: string
  location?: string
  recipeCategory?: string
  totalTime?: number
}

const KEY = 'elladastopiato_favorites'

function load(): FavoriteRecipe[] {
  if (typeof window === 'undefined') return []
  try { return JSON.parse(localStorage.getItem(KEY) || '[]') } catch { return [] }
}

function save(items: FavoriteRecipe[]) {
  localStorage.setItem(KEY, JSON.stringify(items))
}

export function useFavorites() {
  const [favorites, setFavorites] = useState<FavoriteRecipe[]>([])
  const [mounted, setMounted] = useState(false)

  useEffect(() => {
    setFavorites(load())
    setMounted(true)
  }, [])

  const isFavorite = useCallback((id: string) => favorites.some(f => f.id === id), [favorites])

  const toggle = useCallback((recipe: FavoriteRecipe) => {
    setFavorites(prev => {
      const next = prev.some(f => f.id === recipe.id)
        ? prev.filter(f => f.id !== recipe.id)
        : [recipe, ...prev]
      save(next)
      return next
    })
  }, [])

  const remove = useCallback((id: string) => {
    setFavorites(prev => {
      const next = prev.filter(f => f.id !== id)
      save(next)
      return next
    })
  }, [])

  return { favorites, isFavorite, toggle, remove, mounted }
}
