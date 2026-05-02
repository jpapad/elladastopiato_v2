'use client'
import React, { useState, useEffect, useRef } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Search, X, Utensils, ArrowRight } from 'lucide-react'
import Link from 'next/link'
import { Recipe } from '@/payload-types'

interface SearchOverlayProps {
  isOpen: boolean
  onClose: () => void
}

export const SearchOverlay = ({ isOpen, onClose }: SearchOverlayProps) => {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<Recipe[]>([])
  const [loading, setLoading] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)

  // Focus στο input όταν ανοίγει το overlay
  useEffect(() => {
    if (isOpen) {
      setTimeout(() => inputRef.current?.focus(), 100)
    } else {
      setQuery('')
      setResults([])
    }
  }, [isOpen])

  // Αναζήτηση στο API του Payload
  useEffect(() => {
    const searchRecipes = async () => {
      if (query.length < 2) {
        setResults([])
        return
      }
      setLoading(true)
      try {
        const res = await fetch(`/api/recipes?where[title][contains]=${query}&limit=5&depth=1`)
        const data = await res.json()
        setResults(data.docs || [])
      } catch (error) {
        console.error("Search error:", error)
      }
      setLoading(false)
    }

    const timer = setTimeout(searchRecipes, 300) // Debounce
    return () => clearTimeout(timer)
  }, [query])

  return (
    <AnimatePresence>
      {isOpen && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="fixed inset-0 z-[150] bg-[#050505]/95 backdrop-blur-2xl flex flex-col items-center pt-32 px-6"
        >
          <button 
            onClick={onClose}
            className="absolute top-10 right-10 p-4 text-white/20 hover:text-white transition-colors"
          >
            <X size={32} />
          </button>

          <div className="w-full max-w-3xl">
            <div className="relative mb-12">
              <Search className="absolute left-6 top-1/2 -translate-y-1/2 text-orange-500" size={24} />
              <input
                ref={inputRef}
                type="text"
                placeholder="Αναζήτηση συνταγής, υλικού..."
                className="w-full bg-white/5 border border-white/10 rounded-3xl py-8 pl-16 pr-8 text-2xl font-light focus:outline-none focus:border-orange-500/50 transition-all"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
              />
              {loading && (
                <div className="absolute right-6 top-1/2 -translate-y-1/2">
                  <div className="w-6 h-6 border-2 border-orange-500 border-t-transparent rounded-full animate-spin" />
                </div>
              )}
            </div>

            <div className="space-y-4">
              {results.length > 0 ? (
                results.map((recipe) => (
                  <motion.div
                    key={recipe.id}
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                  >
                    <Link 
                      href={`/recipes/${recipe.id}`}
                      onClick={onClose}
                      className="group flex items-center justify-between p-6 bg-white/[0.02] border border-white/5 rounded-2xl hover:bg-white/[0.05] hover:border-orange-500/30 transition-all"
                    >
                      <div className="flex items-center gap-6">
                        <div className="w-16 h-16 rounded-xl overflow-hidden bg-white/5">
                          {typeof recipe.image === 'object' && recipe.image?.url && (
                            <img src={recipe.image.url} alt="" className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                          )}
                        </div>
                        <div>
                          <h4 className="text-xl font-bold uppercase italic tracking-tight group-hover:text-orange-500 transition-colors">
                            {recipe.title}
                          </h4>
                          <span className="text-[10px] font-black uppercase tracking-widest text-white/30">
                            {recipe.category || 'Συνταγή'}
                          </span>
                        </div>
                      </div>
                      <ArrowRight className="text-white/20 group-hover:text-orange-500 group-hover:translate-x-2 transition-all" />
                    </Link>
                  </motion.div>
                ))
              ) : query.length > 1 && !loading ? (
                <p className="text-center text-white/20 italic tracking-widest uppercase text-xs">Δεν βρέθηκαν αποτελέσματα</p>
              ) : null}
            </div>
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  )
}