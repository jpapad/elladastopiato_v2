'use client'
import React, { useEffect, useState } from 'react'
import { motion } from 'framer-motion'
import Link from 'next/link'
import Image from 'next/image' // <--- Import Image
import { Compass, ArrowRight } from 'lucide-react'

export default function RegionsPage() {
  const [regions, setRegions] = useState<any[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetch('/api/locations?limit=100')
      .then(res => res.json())
      .then(data => {
        setRegions(data.docs || [])
        setLoading(false)
      })
  }, [])

  return (
    <div className="min-h-screen bg-[#050505] pt-40 pb-24 px-6 md:px-12">
      <div className="max-w-7xl mx-auto">
        <header className="mb-24">
          <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.5em] mb-6 block">
            Geographic Explorer
          </span>
          <h1 className="text-6xl md:text-9xl font-black uppercase italic tracking-tighter leading-[0.8] mb-8">
            ΟΙ ΤΟΠΟΙ <br /> <span className="text-white/10">ΤΗΣ ΓΕΥΣΗΣ</span>
          </h1>
        </header>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {loading ? (
            [1, 2, 3].map(i => <div key={i} className="h-[500px] bg-white/5 animate-pulse rounded-[3rem]" />)
          ) : (
            regions.map((region, index) => (
              <motion.div
                key={region.id}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ delay: index * 0.1 }}
                viewport={{ once: true }}
              >
                <Link 
                  href={`/regions/${region.id}`}
                  className="group relative block h-[500px] overflow-hidden rounded-[3rem] bg-zinc-900 border border-white/5 transition-all duration-700"
                >
                  {/* Background Image */}
                  {region.image?.url && (
                    <Image 
                      src={region.image.url} 
                      alt={region.name}
                      fill
                      className="object-cover opacity-40 grayscale group-hover:grayscale-0 group-hover:scale-110 transition-all duration-1000"
                    />
                  )}
                  
                  {/* Gradient Overlay */}
                  <div className="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent z-10" />

                  <div className="absolute inset-0 flex flex-col justify-end p-12 z-20">
                    <div className="flex items-center gap-3 text-orange-500 mb-4 opacity-0 group-hover:opacity-100 -translate-x-4 group-hover:translate-x-0 transition-all">
                      <Compass size={20} />
                      <span className="text-[10px] font-black uppercase tracking-widest">Εξερευνηση</span>
                    </div>
                    <h3 className="text-5xl font-black uppercase italic tracking-tighter mb-4 group-hover:text-orange-500 transition-colors">
                      {region.name}
                    </h3>
                    <div className="flex items-center justify-between border-t border-white/10 pt-8 mt-4">
                      <span className="text-[10px] font-bold uppercase tracking-widest text-white/40">Τοπικη Κουζινα</span>
                      <ArrowRight size={20} className="group-hover:translate-x-2 transition-transform text-orange-500" />
                    </div>
                  </div>
                </Link>
              </motion.div>
            ))
          )}
        </div>
      </div>
    </div>
  )
}