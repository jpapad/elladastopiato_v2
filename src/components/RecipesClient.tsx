'use client'
import React, { useState, useMemo, useEffect } from 'react'
import { Search, X } from 'lucide-react'
import { CategoryFilters } from './CategoryFilters'
import RecipeCard from './RecipeCard'
import { motion, AnimatePresence } from 'framer-motion'

const PAGE_SIZE = 12

interface Props {
  recipes: any[]
}

export default function RecipesClient({ recipes }: Props) {
  const [activeCategory, setActiveCategory] = useState('all')
  const [search, setSearch] = useState('')
  const [visible, setVisible] = useState(PAGE_SIZE)

  useEffect(() => { setVisible(PAGE_SIZE) }, [activeCategory, search])

  const filtered = useMemo(() => {
    return recipes.filter((r) => {
      const matchCategory = activeCategory === 'all' || r.recipeCategory === activeCategory
      const matchSearch = !search || r.title.toLowerCase().includes(search.toLowerCase())
      return matchCategory && matchSearch
    })
  }, [recipes, activeCategory, search])

  const shown = filtered.slice(0, visible)
  const hasMore = visible < filtered.length

  return (
    <div>
      {/* Search + Filters bar */}
      <div className="flex flex-col md:flex-row gap-4 mb-12">
        <div className="relative flex-shrink-0 md:w-72">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-black/30 dark:text-white/30 pointer-events-none" />
          <input
            type="text"
            value={search}
            onChange={e => setSearch(e.target.value)}
            placeholder="Αναζήτηση συνταγής..."
            className="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-full pl-11 pr-10 py-3 text-sm placeholder:text-black/30 dark:placeholder:text-white/30 focus:outline-none focus:border-orange-500/50 transition-colors"
          />
          {search && (
            <button onClick={() => setSearch('')} className="absolute right-4 top-1/2 -translate-y-1/2 text-black/30 dark:text-white/30 hover:text-orange-500 transition-colors">
              <X className="w-4 h-4" />
            </button>
          )}
        </div>

        <div className="flex-1 overflow-hidden">
          <CategoryFilters activeCategory={activeCategory} onCategoryChange={setActiveCategory} />
        </div>
      </div>

      {/* Results count */}
      <p className="text-black/30 dark:text-white/30 text-xs uppercase tracking-widest font-bold mb-8">
        {filtered.length} {filtered.length === 1 ? 'συνταγή' : 'συνταγές'}
        {activeCategory !== 'all' && ` — ${activeCategory}`}
        {search && ` — "${search}"`}
      </p>

      {/* Grid */}
      <AnimatePresence mode="wait">
        {shown.length > 0 ? (
          <motion.div
            key={`${activeCategory}-${search}`}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.25 }}
            className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
          >
            {shown.map((recipe) => (
              <RecipeCard key={recipe.id} recipe={recipe} />
            ))}
          </motion.div>
        ) : (
          <motion.div
            key="empty"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            className="py-40 text-center border border-dashed border-black/10 dark:border-white/10 rounded-[3rem]"
          >
            <p className="text-black/20 dark:text-white/20 uppercase tracking-widest text-xs italic">
              Δεν βρέθηκαν συνταγές για αυτά τα κριτήρια.
            </p>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Load More */}
      {hasMore && (
        <div className="flex justify-center mt-16">
          <button
            onClick={() => setVisible(v => v + PAGE_SIZE)}
            className="group flex items-center gap-4 px-10 py-4 border border-black/10 dark:border-white/10 rounded-full text-[11px] font-black uppercase tracking-[0.3em] text-black/50 dark:text-white/50 hover:border-orange-500/50 hover:text-orange-500 transition-all duration-300"
          >
            Φόρτωσε Περισσότερες
            <span className="text-black/20 dark:text-white/20 group-hover:text-orange-500/60 transition-colors">
              {filtered.length - visible} ακόμα
            </span>
          </button>
        </div>
      )}
    </div>
  )
}
