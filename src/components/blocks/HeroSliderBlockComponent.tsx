'use client'
import React, { useState, useEffect, useCallback } from 'react'
import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight, ChevronLeft, ChevronRight } from 'lucide-react'

interface Slide {
  badge?: string
  titlePrefix?: string
  titleHighlight?: string
  titleSuffix?: string
  subtitle?: string
  buttonText?: string
  buttonLink?: string
  backgroundImage?: { url: string }
}

interface HeroSliderProps {
  block: {
    slides: Slide[]
    autoplay?: boolean
    autoplayInterval?: string
  }
}

export function HeroSliderBlockComponent({ block }: HeroSliderProps) {
  const slides = block.slides || []
  const [current, setCurrent] = useState(0)
  const [animating, setAnimating] = useState(false)
  const interval = parseInt(block.autoplayInterval || '5000')

  const goTo = useCallback((index: number) => {
    if (animating) return
    setAnimating(true)
    setCurrent(index)
    setTimeout(() => setAnimating(false), 700)
  }, [animating])

  const next = useCallback(() => goTo((current + 1) % slides.length), [current, slides.length, goTo])
  const prev = useCallback(() => goTo((current - 1 + slides.length) % slides.length), [current, slides.length, goTo])

  useEffect(() => {
    if (!block.autoplay || slides.length <= 1) return
    const timer = setInterval(next, interval)
    return () => clearInterval(timer)
  }, [block.autoplay, slides.length, next, interval])

  if (slides.length === 0) return null

  const slide = slides[current]

  return (
    <section className="relative h-screen flex items-center overflow-hidden -mt-28">
      {/* Background images */}
      {slides.map((s, i) => (
        <div
          key={i}
          className={`absolute inset-0 transition-opacity duration-700 ${i === current ? 'opacity-100' : 'opacity-0'}`}
        >
          <div className="absolute inset-0 bg-gradient-to-r from-black via-black/60 to-transparent z-10" />
          <Image
            src={s.backgroundImage?.url || '/hero-bg.jpg'}
            alt={s.titlePrefix || 'Hero'}
            fill
            sizes="100vw"
            priority={i === 0}
            className="object-cover opacity-50 scale-110"
          />
        </div>
      ))}

      {/* Content */}
      <div className="max-w-7xl mx-auto px-6 relative z-20 w-full">
        <div
          key={current}
          className="max-w-3xl transition-all duration-500 animate-in fade-in slide-in-from-bottom-4"
        >
          {slide.badge && (
            <span className="inline-block px-4 py-1 rounded-full border border-orange-500/30 text-orange-500 text-[10px] font-black uppercase tracking-[0.3em] mb-6">
              {slide.badge}
            </span>
          )}
          <h1 className="text-5xl md:text-7xl font-black uppercase italic tracking-tighter leading-[0.85] mb-8 text-white">
            {slide.titlePrefix}
            {slide.titlePrefix && <br />}
            {slide.titleHighlight && <span className="text-orange-500">{slide.titleHighlight}</span>}
            {slide.titleSuffix && ` ${slide.titleSuffix}`}
          </h1>
          {slide.subtitle && (
            <p className="text-lg text-white/60 mb-12 max-w-xl leading-relaxed">{slide.subtitle}</p>
          )}
          {slide.buttonText && slide.buttonLink && (
            <Link
              href={slide.buttonLink}
              className="bg-orange-500 text-black px-10 py-5 rounded-full font-black uppercase tracking-widest hover:bg-white transition-all inline-flex items-center gap-3 group text-sm"
            >
              {slide.buttonText}
              <ArrowRight size={18} className="group-hover:translate-x-2 transition-transform" />
            </Link>
          )}
        </div>
      </div>

      {/* Navigation arrows — only if multiple slides */}
      {slides.length > 1 && (
        <>
          <button
            onClick={prev}
            className="absolute left-6 z-30 w-12 h-12 rounded-full bg-black/20 backdrop-blur-sm border border-white/10 flex items-center justify-center text-white hover:bg-orange-500 hover:border-orange-500 transition-all"
            aria-label="Προηγούμενο slide"
          >
            <ChevronLeft size={20} />
          </button>
          <button
            onClick={next}
            className="absolute right-6 z-30 w-12 h-12 rounded-full bg-black/20 backdrop-blur-sm border border-white/10 flex items-center justify-center text-white hover:bg-orange-500 hover:border-orange-500 transition-all"
            aria-label="Επόμενο slide"
          >
            <ChevronRight size={20} />
          </button>

          {/* Dots */}
          <div className="absolute bottom-10 left-1/2 -translate-x-1/2 z-30 flex items-center gap-3">
            {slides.map((_, i) => (
              <button
                key={i}
                onClick={() => goTo(i)}
                className={`transition-all duration-300 rounded-full ${
                  i === current
                    ? 'w-8 h-2 bg-orange-500'
                    : 'w-2 h-2 bg-white/30 hover:bg-white/60'
                }`}
                aria-label={`Slide ${i + 1}`}
              />
            ))}
          </div>

          {/* Progress bar */}
          {block.autoplay && (
            <div className="absolute bottom-0 left-0 right-0 z-30 h-0.5 bg-white/10">
              <div
                key={`${current}-progress`}
                className="h-full bg-orange-500 origin-left"
                style={{ animation: `progress ${interval}ms linear` }}
              />
            </div>
          )}
        </>
      )}

      <style>{`
        @keyframes progress {
          from { transform: scaleX(0); }
          to   { transform: scaleX(1); }
        }
      `}</style>
    </section>
  )
}
