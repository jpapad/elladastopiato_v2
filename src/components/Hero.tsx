'use client'
import React from 'react'
import { motion } from 'framer-motion'
import Image from 'next/image'
import { Media } from '@/payload-types'

interface HeroProps {
  title?: string
  subtitle?: string
  backgroundImage?: string | Media | null
}

export const Hero = ({ title, subtitle, backgroundImage }: HeroProps) => {
  const imageUrl = typeof backgroundImage === 'object' ? backgroundImage?.url : backgroundImage

  return (
    <section className="relative h-[95vh] w-full flex items-center justify-center overflow-hidden bg-[#0E0C0A]">
      {imageUrl && (
        <div className="absolute inset-0 z-0">
          <Image 
            src={imageUrl} 
            alt={title || 'Hero'} 
            fill 
            className="object-cover opacity-40 grayscale"
            priority
          />
        </div>
      )}
      
      <div className="relative z-10 text-center px-6">
        <motion.div
          initial={{ opacity: 0, y: 40 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 1 }}
        >
          <h1 className="text-[12vw] md:text-[10vw] font-black uppercase italic tracking-tighter leading-[0.75] mb-10">
            {title || 'ΓΕΥΣΗ ΧΩΡΙΣ ΣΥΝΟΡΑ'}
          </h1>
          {subtitle && (
            <p className="max-w-2xl mx-auto text-white/40 text-xs md:text-sm uppercase tracking-[0.3em] leading-loose font-light">
              {subtitle}
            </p>
          )}
        </motion.div>
      </div>
    </section>
  )
}