'use client'
import React from 'react'
import { motion } from 'framer-motion'
import { ArrowUpRight, MapPin, X } from 'lucide-react'
import type { MapImage } from '@/lib/wordpress/types'

export type InfoCardData = {
  key: string
  eyebrow: string
  title: string
  description?: string
  meta?: string
  image?: MapImage | null
  link?: string
  linkLabel?: string
}

type Props = {
  data: InfoCardData
  onClose: () => void
  /** Αν ο σύνδεσμος οδηγεί σε άλλο domain, ανοίγει σε νέα καρτέλα. */
  external?: boolean
}

export default function InfoCard({ data, onClose, external }: Props) {
  return (
    <motion.aside
      key={data.key}
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: 16 }}
      transition={{ duration: 0.25 }}
      // Κινητό: κάτω από τον χάρτη (στη ροή). Desktop: πάνω στον χάρτη, κάτω αριστερά.
      className="relative mx-4 mb-2 sm:absolute sm:mx-0 sm:mb-0 sm:left-4 sm:bottom-4 sm:w-80 z-20 rounded-3xl border border-white/10 bg-black/80 backdrop-blur-xl text-white overflow-hidden shadow-2xl"
      aria-live="polite"
    >
      {data.image && (
        <div className="relative h-24 sm:h-32 w-full overflow-hidden">
          {/* eslint-disable-next-line @next/next/no-img-element -- ο χάρτης τρέχει και εκτός Next (WordPress bundle) */}
          <img
            src={data.image.url}
            alt={data.image.alt || data.title}
            className="h-full w-full object-cover"
            loading="lazy"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent" />
        </div>
      )}

      <button
        type="button"
        onClick={onClose}
        aria-label="Κλείσιμο"
        className="absolute top-3 right-3 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white/60 hover:text-white transition-colors"
      >
        <X className="h-3.5 w-3.5" />
      </button>

      <div className="p-5">
        <p className="flex items-center gap-1.5 text-orange-500 text-[9px] font-bold uppercase tracking-[0.25em] mb-1.5">
          <MapPin className="h-3 w-3" />
          {data.eyebrow}
        </p>
        <h3 className="text-lg font-black leading-tight pr-6">{data.title}</h3>
        {data.meta && <p className="mt-1 text-[11px] text-white/40">{data.meta}</p>}
        {data.description && (
          <p className="mt-2 text-xs leading-relaxed text-white/60 line-clamp-4 whitespace-pre-line">
            {data.description}
          </p>
        )}
        {data.link && (
          <a
            href={data.link}
            {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
            className="mt-4 inline-flex items-center gap-1.5 rounded-full bg-orange-500 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-black hover:bg-white transition-colors"
          >
            {data.linkLabel || 'Δες περισσότερα'}
            <ArrowUpRight className="h-3.5 w-3.5" />
          </a>
        )}
      </div>
    </motion.aside>
  )
}
