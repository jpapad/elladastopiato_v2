'use client'
import React from 'react'
import { Salad, Utensils, Cake, Coffee } from 'lucide-react'

const CATEGORIES = [
  { id: 'all', label: 'Όλα', icon: <Utensils size={18} /> },
  { id: 'orektika', label: 'Ορεκτικά', icon: <Salad size={18} /> },
  { id: 'kyrios', label: 'Κυρίως', icon: <Utensils size={18} /> },
  { id: 'glyka', label: 'Γλυκά', icon: <Cake size={18} /> },
  { id: 'rofimata', label: 'Ροφήματα', icon: <Coffee size={18} /> },
]

interface CategoryFiltersProps {
  activeCategory: string
  onCategoryChange: (id: string) => void
}

export const CategoryFilters: React.FC<CategoryFiltersProps> = ({ activeCategory, onCategoryChange }) => {
  return (
    <div className="flex items-center gap-3 overflow-x-auto pb-4 custom-scrollbar mb-8">
      {CATEGORIES.map((cat) => (
        <button
          key={cat.id}
          onClick={() => onCategoryChange(cat.id)}
          className={`flex items-center gap-2 px-5 py-2.5 rounded-full border transition-all duration-300 whitespace-nowrap ${
            activeCategory === cat.id
              ? 'bg-orange-500 border-orange-500 text-white shadow-lg shadow-orange-500/20'
              : 'bg-white/5 border-white/10 text-white/60 hover:border-white/30 hover:bg-white/10'
          }`}
        >
          {cat.icon}
          <span className="text-sm font-medium">{cat.label}</span>
        </button>
      ))}
    </div>
  )
}