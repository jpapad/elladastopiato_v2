'use client'
import React, { useState } from 'react'
import { Minus, Plus, Users } from 'lucide-react'

interface Props {
  defaultServings: number
  onChange: (multiplier: number) => void
}

export default function ServingsAdjuster({ defaultServings, onChange }: Props) {
  const [servings, setServings] = useState(defaultServings)

  const update = (next: number) => {
    if (next < 1 || next > 99) return
    setServings(next)
    onChange(next / defaultServings)
  }

  const ratio = servings / defaultServings
  const changed = ratio !== 1

  return (
    <div className="flex items-center gap-4 bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 px-5 py-3 rounded-2xl w-fit">
      <Users size={14} className="text-black/40 dark:text-white/40" />
      <span className="text-[10px] font-black uppercase tracking-widest text-black/40 dark:text-white/40">Μερίδες</span>
      <div className="flex items-center gap-2">
        <button
          onClick={() => update(servings - 1)}
          className="w-7 h-7 rounded-full bg-black/10 dark:bg-white/10 flex items-center justify-center hover:bg-orange-500 hover:text-black transition-all"
        >
          <Minus size={12} />
        </button>
        <span className={`text-xl font-black w-8 text-center transition-colors ${changed ? 'text-orange-500' : ''}`}>
          {servings}
        </span>
        <button
          onClick={() => update(servings + 1)}
          className="w-7 h-7 rounded-full bg-black/10 dark:bg-white/10 flex items-center justify-center hover:bg-orange-500 hover:text-black transition-all"
        >
          <Plus size={12} />
        </button>
      </div>
      {changed && (
        <button
          onClick={() => update(defaultServings)}
          className="text-[9px] font-black uppercase tracking-widest text-orange-500 hover:text-orange-400 transition-colors"
        >
          Reset
        </button>
      )}
    </div>
  )
}
