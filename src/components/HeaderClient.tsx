'use client'
import React, { useState, useEffect } from 'react'
import Link from 'next/link'
import { motion, AnimatePresence, Variants } from 'framer-motion'
import { UtensilsCrossed, Menu, X, ArrowRight, Search } from 'lucide-react'
import { SearchOverlay } from './SearchOverlay'

// Προσθήκη του Interface για τα links
interface NavLink {
  label: string
  link: string
}

// ΠΡΟΣΟΧΗ: Πρέπει να γράφει "export const HeaderClient"
export const HeaderClient = ({ navLinks }: { navLinks: NavLink[] }) => {
  const [isOpen, setIsOpen] = useState(false)
  const [isSearchOpen, setIsSearchOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 50)
    window.addEventListener('scroll', handleScroll)
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  const menuVariants: Variants = {
    closed: { opacity: 0, y: "-100%", transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] as any } },
    opened: { opacity: 1, y: 0, transition: { duration: 0.7, ease: [0.22, 1, 0.36, 1] as any } }
  }

  const linkVariants: Variants = {
    closed: { x: -20, opacity: 0 },
    opened: (i: number) => ({
      x: 0, opacity: 1,
      transition: { delay: 0.3 + i * 0.1, duration: 0.5, ease: "easeOut" }
    })
  }

  return (
    <>
      <header className={`fixed top-0 left-0 w-full z-[100] transition-all duration-500 px-6 md:px-12 py-6 ${
          scrolled ? 'bg-[#0E0C0A]/80 backdrop-blur-xl border-b border-white/5 py-4' : 'bg-transparent'
        }`}>
        <div className="max-w-7xl mx-auto flex items-center justify-between">
          <Link href="/" className="flex items-center gap-3 group relative z-[110]">
            <div className="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center rotate-6 group-hover:rotate-0 transition-transform duration-500">
              <UtensilsCrossed size={20} className="text-black" />
            </div>
            <span className="text-xl font-black italic tracking-tighter uppercase hidden sm:block">
              Ελλάδα <span className="text-orange-500">στο</span> Πιάτο
            </span>
          </Link>

          <div className="flex items-center gap-8">
            <nav className="hidden md:flex items-center gap-10">
              {navLinks?.map((link) => (
                <Link key={link.label} href={link.link} className="text-[10px] font-black uppercase tracking-[0.3em] text-white/50 hover:text-white transition-colors">
                  {link.label}
                </Link>
              ))}
            </nav>

            <button onClick={() => setIsSearchOpen(true)} className="p-3 text-white/50 hover:text-orange-500 transition-colors">
              <Search size={20} />
            </button>

            <button onClick={() => setIsOpen(!isOpen)} className="relative z-[110] p-3 bg-white/5 border border-white/10 rounded-full hover:border-orange-500/50 transition-all">
              {isOpen ? <X size={20} className="text-orange-500" /> : <Menu size={20} />}
            </button>
          </div>
        </div>
      </header>

      <SearchOverlay isOpen={isSearchOpen} onClose={() => setIsSearchOpen(false)} />

      <AnimatePresence>
        {isOpen && (
          <motion.div variants={menuVariants} initial="closed" animate="opened" exit="closed" className="fixed inset-0 z-[105] bg-[#0E0C0A] flex flex-col justify-center px-6 md:px-24">
            <div className="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
              <nav className="flex flex-col gap-6 md:gap-10">
                {navLinks?.map((link, i) => (
                  <motion.div key={link.label} custom={i} variants={linkVariants} initial="closed" animate="opened">
                    <Link href={link.link} onClick={() => setIsOpen(false)} className="group flex items-center gap-6">
                      <span className="text-orange-500 font-black italic text-xl opacity-20 group-hover:opacity-100 transition-opacity">0{i + 1}</span>
                      <span className="text-5xl md:text-8xl font-black uppercase italic tracking-tighter group-hover:text-orange-500 transition-all duration-500 group-hover:pl-4">{link.label}</span>
                      <ArrowRight className="opacity-0 group-hover:opacity-100 -translate-x-4 group-hover:translate-x-0 transition-all text-orange-500" size={40} />
                    </Link>
                  </motion.div>
                ))}
              </nav>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  )
}