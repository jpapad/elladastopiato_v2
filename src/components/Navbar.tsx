'use client'
import React, { useState, useEffect } from 'react'
import Link from 'next/link'
import { UtensilsCrossed, Menu, X, Search } from 'lucide-react'
import { ThemeToggle } from './ThemeToggle'
import { SearchOverlay } from './SearchOverlay'

export default function Navbar() {
  const [isScrolled, setIsScrolled] = useState(false)
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const [isSearchOpen, setIsSearchOpen] = useState(false)

  useEffect(() => {
    const handleScroll = () => setIsScrolled(window.scrollY > 20)
    window.addEventListener('scroll', handleScroll)
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  return (
    <nav className={`fixed top-0 left-0 right-0 z-50 transition-all duration-500 ${
      isScrolled ? 'py-4' : 'py-8'
    }`}>
      <div className="max-w-7xl mx-auto px-6">
        <div className={`relative flex items-center justify-between px-8 py-4 rounded-full border border-black/10 dark:border-white/10 transition-all duration-500 ${
          isScrolled ? 'bg-white/80 dark:bg-black/40 backdrop-blur-xl shadow-2xl' : 'bg-transparent'
        }`}>

          {/* Logo */}
          <Link href="/" className="flex items-center gap-2 group">
            <div className="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center group-hover:rotate-12 transition-transform">
              <UtensilsCrossed size={20} className="text-black" />
            </div>
            <span className="font-black uppercase italic tracking-tighter text-xl">
              Ελλαδα <span className="text-orange-500">στο Πιατο</span>
            </span>
          </Link>

          {/* Desktop Menu */}
          <div className="hidden md:flex items-center gap-6">
            {[
              { label: 'Περιοχές', href: '/regions' },
              { label: 'Συνταγές', href: '/recipes' },
              { label: 'Κατηγορίες', href: '/categories' },
              { label: 'Συλλογές', href: '/collections' },
              { label: 'Αποθηκευμένα', href: '/saved' },
            ].map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className="text-[10px] font-black uppercase tracking-[0.2em] text-black/60 dark:text-white/60 hover:text-orange-500 transition-colors"
              >
                {item.label}
              </Link>
            ))}
            <button
              onClick={() => setIsSearchOpen(true)}
              className="w-10 h-10 flex items-center justify-center rounded-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 hover:bg-orange-500 hover:text-black hover:border-orange-500 transition-all"
              aria-label="Αναζήτηση"
            >
              <Search size={16} />
            </button>
            <ThemeToggle />
          </div>

          {/* Mobile Toggle */}
          <button className="md:hidden" onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}>
            {isMobileMenuOpen ? <X /> : <Menu />}
          </button>
        </div>
      </div>

      {/* Mobile Menu Overlay */}
      {isMobileMenuOpen && (
        <div className="fixed inset-0 bg-white/95 dark:bg-black/95 backdrop-blur-2xl z-[60] flex flex-col items-center justify-center gap-8 md:hidden">
          <button onClick={() => setIsMobileMenuOpen(false)} className="absolute top-10 right-10 text-black/40 dark:text-white/40"><X size={32}/></button>
          {[
            { label: 'Περιοχές', href: '/regions' },
            { label: 'Συνταγές', href: '/recipes' },
            { label: 'Κατηγορίες', href: '/categories' },
            { label: 'Συλλογές', href: '/collections' },
            { label: 'Αποθηκευμένα', href: '/saved' },
          ].map((item) => (
            <Link
              key={item.href}
              href={item.href}
              onClick={() => setIsMobileMenuOpen(false)}
              className="text-4xl font-black uppercase italic tracking-tighter hover:text-orange-500 transition-colors"
            >
              {item.label}
            </Link>
          ))}
          <button
            onClick={() => { setIsMobileMenuOpen(false); setIsSearchOpen(true) }}
            className="flex items-center gap-2 text-black/40 dark:text-white/40 hover:text-orange-500 transition-colors text-[10px] font-black uppercase tracking-widest"
          >
            <Search size={16} /> Αναζήτηση
          </button>
          <ThemeToggle />
        </div>
      )}

      <SearchOverlay isOpen={isSearchOpen} onClose={() => setIsSearchOpen(false)} />
    </nav>
  )
}
