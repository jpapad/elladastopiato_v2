'use client'
import React, { useEffect, useState } from 'react'
import RecipeCard from './RecipeCard'
import { Recipe } from '@/payload-types'

export const RecipeGridBlock = ({ title, limit }: { title?: string, limit?: number }) => {
  const [recipes, setRecipes] = useState<Recipe[]>([])

  useEffect(() => {
    fetch(`/api/recipes?limit=${limit || 3}&depth=1`)
      .then(res => res.json())
      .then(data => setRecipes(data.docs || []))
  }, [limit])

  return (
    <section className="max-w-7xl mx-auto px-6 py-24">
      {title && <h2 className="text-4xl font-black uppercase italic mb-12">{title}</h2>}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
        {recipes.map(recipe => (
          <RecipeCard key={recipe.id} recipe={recipe} />
        ))}
      </div>
    </section>
  )
}