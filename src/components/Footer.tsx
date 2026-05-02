'use client'
import React from 'react'
import Link from 'next/link'
import { Instagram, Facebook, Youtube, ArrowUpRight, UtensilsCrossed } from 'lucide-react'

export const Footer = () => {
  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  return (
    <footer className="bg-[#050505] border-t border-white/5 pt-24 pb-12 overflow-hidden">
      <div className="max-w-7xl mx-auto px-6">
        
        {/* Top Section: Branding & Big Statement */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-20 mb-24">
          <div>
            <div className="flex items-center gap-3 mb-8 group cursor-pointer" onClick={scrollToTop}>
              <div className="w-12 h-12 bg-orange-500 rounded-2xl flex items-center justify-center rotate-6 group-hover:rotate-0 transition-transform duration-500">
                <UtensilsCrossed size={24} className="text-black" />
              </div>
              <span className="text-2xl font-black italic tracking-tighter uppercase">
                Ελλάδα <span className="text-orange-500">στο</span> Πιάτο
              </span>
            </div>
            <h2 className="text-4xl md:text-5xl font-bold tracking-tighter leading-tight max-w-md opacity-80">
              Η παράδοση συναντά την <span className="italic font-black text-white">ψηφιακή εποχή.</span>
            </h2>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-3 gap-10">
            <div className="space-y-6">
              <h4 className="text-orange-500 text-[10px] font-black uppercase tracking-[0.3em]">Πλοηγηση</h4>
              <ul className="space-y-4 text-sm font-medium text-white/40">
                <li><Link href="/" className="hover:text-white transition-colors">Αρχική</Link></li>
                <li><Link href="/recipes" className="hover:text-white transition-colors">Συνταγές</Link></li>
                <li><Link href="/regions" className="hover:text-white transition-colors">Περιοχές</Link></li>
              </ul>
            </div>
            <div className="space-y-6">
              <h4 className="text-orange-500 text-[10px] font-black uppercase tracking-[0.3em]">Social</h4>
              <ul className="space-y-4 text-sm font-medium text-white/40">
                <li className="flex items-center gap-2 hover:text-white transition-colors cursor-pointer">
                  <Instagram size={14} /> Instagram
                </li>
                <li className="flex items-center gap-2 hover:text-white transition-colors cursor-pointer">
                  <Facebook size={14} /> Facebook
                </li>
                <li className="flex items-center gap-2 hover:text-white transition-colors cursor-pointer">
                  <Youtube size={14} /> Youtube
                </li>
              </ul>
            </div>
            <div className="space-y-6 hidden md:block">
              <h4 className="text-orange-500 text-[10px] font-black uppercase tracking-[0.3em]">Admin</h4>
              <Link href="/admin" className="inline-flex items-center gap-2 text-sm font-medium text-white/40 hover:text-orange-500 transition-colors">
                Dashboard <ArrowUpRight size={14} />
              </Link>
            </div>
          </div>
        </div>

        {/* Middle Section: Animated Logo / Text Decor */}
        <div className="relative py-10 border-y border-white/5 group">
          <div className="flex whitespace-nowrap overflow-hidden py-4 opacity-[0.03] pointer-events-none select-none">
            <span className="text-[12rem] font-black uppercase italic leading-none tracking-tighter inline-block animate-marquee">
              GASTRONOMY • TRADITION • MODERN GREECE • GASTRONOMY • TRADITION • MODERN GREECE •
            </span>
          </div>
        </div>

        {/* Bottom Section: Copyright & Back to Top */}
        <div className="mt-12 flex flex-col md:row items-center justify-between gap-8 pt-8 border-t border-white/5">
          <p className="text-[10px] text-white/20 uppercase tracking-[0.2em] font-bold">
            © 2026 ΕΛΛΑΔΑ ΣΤΟ ΠΙΑΤΟ. ALL RIGHTS RESERVED.
          </p>
          
          <button 
            onClick={scrollToTop}
            className="group flex items-center gap-4 bg-white/5 px-6 py-3 rounded-full border border-white/10 hover:border-orange-500/50 transition-all duration-500"
          >
            <span className="text-[10px] font-black uppercase tracking-widest text-white/40 group-hover:text-white transition-colors">
              Επιστροφη στην κορυφη
            </span>
            <div className="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-black group-hover:-translate-y-1 transition-transform">
              <ArrowUpRight size={16} strokeWidth={3} />
            </div>
          </button>
        </div>
      </div>
    </footer>
  )
}