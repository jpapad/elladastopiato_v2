'use client'
import React, { useState, useEffect, useRef, useCallback } from 'react'
import { Search, X, MapPin, Clock, ArrowRight, Loader2 } from 'lucide-react'
import Link from 'next/link'
import Image from 'next/image'
import { motion, AnimatePresence } from 'framer-motion'
import { getCategoryLabel } from '@/lib/categories'

interface SearchResult {
  id: string
  title: string
  slug: string
  image?: { url: string }
  recipeCategory?: string
  prepTime?: number
  cookTime?: number
  location?: { name: string; slug: string }
}

interface Props {
  isOpen: boolean
  onClose: () => void
}

function useDebounce<T>(value: T, delay: number): T {
  const [debounced, setDebounced] = useState(value)
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(t)
  }, [value, delay])
  return debounced
}

export const SearchOverlay = ({ isOpen, onClose }: Props) => {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<SearchResult[]>([])
  const [loading, setLoading] = useState(false)
  const [searched, setSearched] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)
  const debouncedQuery = useDebounce(query, 300)

  useEffect(() => {
    if (isOpen) {
      setTimeout(() => inputRef.current?.focus(), 50)
      setQuery('')
      setResults([])
      setSearched(false)
    }
  }, [isOpen])

  useEffect(() => {
    const handler = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose() }
    window.addEventListener('keydown', handler)
    return () => window.removeEventListener('keydown', handler)
  }, [onClose])

  useEffect(() => {
    if (debouncedQuery.length < 2) {
      setResults([])
      setSearched(false)
      return
    }
    let cancelled = false
    setLoading(true)
    fetch(`/api/search?q=${encodeURIComponent(debouncedQuery)}`)
      .then(r => r.json())
      .then(data => {
        if (!cancelled) {
          setResults(data.docs ?? [])
          setSearched(true)
          setLoading(false)
        }
      })
      .catch(() => { if (!cancelled) setLoading(false) })
    return () => { cancelled = true }
  }, [debouncedQuery])

  const handleResultClick = useCallback(() => {
    onClose()
    setQuery('')
    setResults([])
  }, [onClose])

  return (
    <AnimatePresence>
      {isOpen && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.2 }}
          className="fixed inset-0 z-[150] bg-black/85 backdrop-blur-2xl flex flex-col items-center pt-24 px-4"
          onClick={(e) => { if (e.target === e.currentTarget) onClose() }}
        >
          <motion.div
            initial={{ opacity: 0, y: -20, scale: 0.97 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.25, ease: [0.22, 1, 0.36, 1] }}
            className="w-full max-w-2xl"
          >
            {/* Search input */}
            <div className="relative flex items-center bg-white/10 border border-white/20 rounded-2xl overflow-hidden shadow-2xl">
              <div className="pl-6 pr-3 text-white/40">
                {loading
                  ? <Loader2 size={20} className="animate-spin text-orange-500" />
                  : <Search size={20} />
                }
              </div>
              <input
                ref={inputRef}
                type="text"
                value={query}
                onChange={e => setQuery(e.target.value)}
                placeholder="Αναζήτηση συνταγής..."
                className="flex-1 bg-transparent py-5 pr-4 text-white text-lg placeholder:text-white/30 focus:outline-none font-medium"
              />
              <button
                onClick={onClose}
                className="px-5 py-5 text-white/40 hover:text-white transition-colors"
              >
                <X size={20} />
              </button>
            </div>

            {/* Results */}
            <AnimatePresence mode="wait">
              {results.length > 0 && (
                <motion.div
                  key="results"
                  initial={{ opacity: 0, y: 8 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0 }}
                  transition={{ duration: 0.2 }}
                  className="mt-3 bg-[#111]/95 border border-white/10 rounded-2xl overflow-hidden shadow-2xl"
                >
                  {results.map((recipe, i) => (
                    <Link
                      key={recipe.id}
                      href={`/recipes/${recipe.slug || recipe.id}`}
                      onClick={handleResultClick}
                      className={`flex items-center gap-4 px-5 py-4 hover:bg-white/5 transition-colors group ${
                        i !== 0 ? 'border-t border-white/5' : ''
                      }`}
                    >
                      {/* Thumbnail */}
                      <div className="relative w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 bg-white/5">
                        {recipe.image?.url ? (
                          <Image
                            src={recipe.image.url}
                            alt={recipe.title}
                            fill
                            sizes="56px"
                            className="object-cover grayscale group-hover:grayscale-0 transition-all duration-500"
                          />
                        ) : (
                          <div className="w-full h-full bg-orange-500/10 flex items-center justify-center">
                            <Search size={14} className="text-orange-500/40" />
                          </div>
                        )}
                      </div>

                      {/* Info */}
                      <div className="flex-1 min-w-0">
                        <p className="text-white font-black uppercase italic tracking-tight truncate group-hover:text-orange-500 transition-colors">
                          {recipe.title}
                        </p>
                        <div className="flex items-center gap-3 mt-1 flex-wrap">
                          {recipe.location?.name && (
                            <span className="flex items-center gap-1 text-white/30 text-[10px] font-bold uppercase tracking-wider">
                              <MapPin size={9} /> {recipe.location.name}
                            </span>
                          )}
                          {(recipe.prepTime || recipe.cookTime) && (
                            <span className="flex items-center gap-1 text-white/30 text-[10px] font-bold uppercase tracking-wider">
                              <Clock size={9} /> {(recipe.prepTime || 0) + (recipe.cookTime || 0)}'
                            </span>
                          )}
                          {recipe.recipeCategory && (
                            <span className="text-orange-500/60 text-[10px] font-black uppercase tracking-wider">
                              {getCategoryLabel(recipe.recipeCategory)}
                            </span>
                          )}
                        </div>
                      </div>

                      <ArrowRight size={14} className="text-white/20 group-hover:text-orange-500 group-hover:translate-x-1 transition-all flex-shrink-0" />
                    </Link>
                  ))}

                  {/* View all */}
                  <Link
                    href={`/recipes`}
                    onClick={handleResultClick}
                    className="flex items-center justify-center gap-2 px-5 py-3 bg-orange-500/10 hover:bg-orange-500/20 border-t border-orange-500/20 text-orange-500 text-[10px] font-black uppercase tracking-widest transition-colors"
                  >
                    Δες όλες τις συνταγές <ArrowRight size={12} />
                  </Link>
                </motion.div>
              )}

              {searched && results.length === 0 && !loading && (
                <motion.div
                  key="empty"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  className="mt-3 bg-[#111]/95 border border-white/10 rounded-2xl px-6 py-10 text-center shadow-2xl"
                >
                  <p className="text-white/20 text-xs uppercase tracking-widest font-black italic">
                    Δεν βρέθηκαν αποτελέσματα για «{query}»
                  </p>
                </motion.div>
              )}
            </AnimatePresence>

            {!searched && query.length < 2 && (
              <p className="text-center text-white/20 text-[10px] uppercase tracking-widest font-bold mt-6">
                Πληκτρολόγησε τουλάχιστον 2 χαρακτήρες
              </p>
            )}
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  )
}
