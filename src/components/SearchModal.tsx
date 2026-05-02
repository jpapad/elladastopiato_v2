'use client'
import React, { useState, useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Search, X, Utensils } from 'lucide-react'
import Link from 'next/link'

export const SearchModal = ({ isOpen, onClose }: { isOpen: boolean, onClose: () => void }) => {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState([])

  useEffect(() => {
    if (query.length > 2) {
      fetch(`/api/recipe-search?q=${query}`)
        .then(res => res.json())
        .then(data => setResults(data.docs))
    } else {
      setResults([])
    }
  }, [query])

  return (
    <AnimatePresence>
      {isOpen && (
        <motion.div 
          initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
          className="fixed inset-0 z-[100] bg-black/90 backdrop-blur-xl flex items-start justify-center pt-20 px-6"
        >
          <motion.div 
            initial={{ y: -20 }} animate={{ y: 0 }}
            className="w-full max-w-2xl bg-[#0a0a0a] border border-white/10 rounded-[2rem] overflow-hidden shadow-2xl"
          >
            <div className="p-6 flex items-center gap-4 border-b border-white/5">
              <Search className="text-orange-500" size={24} />
              <input 
                autoFocus
                placeholder="Αναζήτηση συνταγής (π.χ. Μουσακάς)..."
                className="flex-1 bg-transparent border-none outline-none text-xl font-light italic"
                onChange={(e) => setQuery(e.target.value)}
              />
              <button onClick={onClose} className="p-2 hover:bg-white/5 rounded-full">
                <X size={20} className="text-white/20" />
              </button>
            </div>

            <div className="max-h-[60vh] overflow-y-auto p-4">
              {results.map((recipe: any) => (
                <Link 
                  key={recipe.id} 
                  href={`/recipes/${recipe.id}`}
                  onClick={onClose}
                  className="flex items-center gap-4 p-4 hover:bg-white/5 rounded-2xl transition-colors group"
                >
                  <div className="w-12 h-12 bg-orange-500/10 rounded-xl flex items-center justify-center text-orange-500">
                    <Utensils size={20} />
                  </div>
                  <div>
                    <h4 className="font-bold uppercase italic text-sm group-hover:text-orange-500 transition-colors">
                      {recipe.title}
                    </h4>
                    <span className="text-[10px] text-white/20 uppercase tracking-widest">
                      {recipe.location?.name || 'Ελλάδα'}
                    </span>
                  </div>
                </Link>
              ))}
              {query.length > 2 && results.length === 0 && (
                <p className="text-center py-10 text-white/20 italic text-sm">Δεν βρέθηκαν αποτελέσματα...</p>
              )}
            </div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  )
}