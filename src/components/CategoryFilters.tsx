'use client'
import React from 'react'
import { Utensils, Salad, Fish, Cake, CircleDashed, Wheat } from 'lucide-react'
import { CATEGORY_OPTIONS } from '@/lib/categories'

export const CATEGORIES = [
  { id: 'all', label: 'Όλα', icon: <CircleDashed size={16} /> },
  ...CATEGORY_OPTIONS.map(c => ({
    id: c.value,
    label: c.label,
    icon: c.value === 'thalassina' ? <Fish size={16} />
        : c.value === 'glyka'      ? <Cake size={16} />
        : c.value === 'pites'      ? <Wheat size={16} />
        : c.value === 'salates'    ? <Salad size={16} />
        : c.value === 'kyrios'     ? <Utensils size={16} />
        : <Salad size={16} />,
  })),
]

interface Props {
  activeCategory: string
  onCategoryChange: (id: string) => void
}

export const CategoryFilters: React.FC<Props> = ({ activeCategory, onCategoryChange }) => {
  return (
    <div className="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-none">
      {CATEGORIES.map((cat) => (
        <button
          key={cat.id}
          onClick={() => onCategoryChange(cat.id)}
          className={`flex items-center gap-2 px-5 py-2.5 rounded-full border transition-all duration-300 whitespace-nowrap text-sm font-semibold ${
            activeCategory === cat.id
              ? 'bg-orange-500 border-orange-500 text-black shadow-lg shadow-orange-500/25'
              : 'bg-black/5 dark:bg-white/5 border-black/10 dark:border-white/10 text-black/60 dark:text-white/60 hover:border-orange-500/40 hover:text-gray-900 dark:hover:text-white'
          }`}
        >
          {cat.icon}
          {cat.label}
        </button>
      ))}
    </div>
  )
}
