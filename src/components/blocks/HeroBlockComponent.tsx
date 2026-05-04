import Image from 'next/image'
import Link from 'next/link'
import { ArrowRight } from 'lucide-react'

export function HeroBlockComponent({ block }: { block: any }) {
  return (
    <section className="relative h-screen flex items-center overflow-hidden -mt-28">
      <div className="absolute inset-0 z-0">
        <div className="absolute inset-0 bg-gradient-to-r from-black via-black/60 to-transparent z-10" />
        <Image
          src={block.backgroundImage?.url || '/hero-bg.jpg'}
          alt="Hero"
          fill
          sizes="100vw"
          priority
          className="object-cover opacity-50 scale-110"
        />
      </div>
      <div className="max-w-7xl mx-auto px-6 relative z-20 w-full">
        <div className="max-w-3xl">
          {block.badge && (
            <span className="inline-block px-4 py-1 rounded-full border border-orange-500/30 text-orange-500 text-[10px] font-black uppercase tracking-[0.3em] mb-6">
              {block.badge}
            </span>
          )}
          <h1 className="text-5xl md:text-7xl font-black uppercase italic tracking-tighter leading-[0.85] mb-8 text-white">
            {block.titlePrefix}{' '}
            <br />
            {block.titleHighlight && <span className="text-orange-500">{block.titleHighlight}</span>}
            {block.titleSuffix && ` ${block.titleSuffix}`}
          </h1>
          {block.subtitle && (
            <p className="text-lg text-white/60 mb-12 max-w-xl leading-relaxed">{block.subtitle}</p>
          )}
          {block.buttonText && block.buttonLink && (
            <Link href={block.buttonLink} className="bg-orange-500 text-black px-10 py-5 rounded-full font-black uppercase tracking-widest hover:bg-white transition-all inline-flex items-center gap-3 group text-sm">
              {block.buttonText} <ArrowRight size={18} className="group-hover:translate-x-2 transition-transform" />
            </Link>
          )}
        </div>
      </div>
    </section>
  )
}
