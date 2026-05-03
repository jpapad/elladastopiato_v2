'use client'
import React from 'react'
import { Printer } from 'lucide-react'

export default function PrintButton() {
  return (
    <button
      onClick={() => window.print()}
      data-no-print
      className="no-print flex items-center gap-2 bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-orange-500 hover:text-black hover:border-orange-500 transition-all"
    >
      <Printer size={14} /> Εκτύπωση
    </button>
  )
}
