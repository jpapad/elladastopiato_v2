import Link from 'next/link'
import { ArrowRight } from 'lucide-react'

export function CtaSectionBlockComponent({ block }: { block: any }) {
  return (
    <section className="py-20 px-6 max-w-7xl mx-auto text-center">
      <h2 className="text-5xl font-black uppercase italic tracking-tighter mb-4">{block.title}</h2>
      {block.subtitle && <p className="text-lg text-gray-600 dark:text-white/60 mb-8">{block.subtitle}</p>}
      <Link href={block.buttonLink} className="bg-orange-500 text-black px-10 py-5 rounded-full font-black uppercase tracking-widest hover:bg-white transition-all inline-flex items-center gap-3 group text-sm">
        {block.buttonText} <ArrowRight size={18} className="group-hover:translate-x-2 transition-transform" />
      </Link>
    </section>
  )
}
