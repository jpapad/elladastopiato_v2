'use client'

import React from 'react'
import { Share2 } from 'lucide-react'

// Ορίζουμε το interface για να ξέρει το TS τι περιμένουμε
interface ShareButtonProps {
  recipeTitle: string
}

export default function ShareButton({ recipeTitle }: ShareButtonProps) {
  const handleShare = () => {
    const shareData = {
      title: recipeTitle,
      text: `Δες αυτή τη συνταγή για ${recipeTitle} στο Ελλάδα στο Πιάτο!`,
      url: typeof window !== 'undefined' ? window.location.href : '',
    }

    if (navigator.share) {
      navigator.share(shareData).catch(() => {})
    } else {
      window.open(`viber://forward?text=${encodeURIComponent(shareData.text + ' ' + shareData.url)}`)
    }
  }

  return (
    <button
      onClick={handleShare}
      className="flex items-center gap-2 bg-white/5 border border-white/10 px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-orange-500 hover:text-black transition-all"
    >
      <Share2 size={14} /> Κοινοποιηση
    </button>
  )
}