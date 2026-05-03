'use client'
import React, { useState } from 'react'
import { Flame } from 'lucide-react'
import { AnimatePresence } from 'framer-motion'
import CookingMode from './CookingMode'

interface Props {
  instructions: any
  title: string
  totalTime?: number
}

export default function CookingModeButton({ instructions, title, totalTime }: Props) {
  const [open, setOpen] = useState(false)

  return (
    <>
      <button
        onClick={() => setOpen(true)}
        className="no-print flex items-center gap-2 bg-orange-500 text-black px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-orange-400 hover:scale-105 transition-all"
      >
        <Flame size={14} /> Cooking Mode
      </button>

      <AnimatePresence>
        {open && (
          <CookingMode
            instructions={instructions}
            title={title}
            totalTime={totalTime}
            onClose={() => setOpen(false)}
          />
        )}
      </AnimatePresence>
    </>
  )
}
