'use client'
import React, { useEffect, useState, useRef } from 'react'
import Link from 'next/link'
import Image from 'next/image'
import { useRouter } from 'next/navigation'
import { User, LogOut, ChefHat, Settings } from 'lucide-react'

export default function UserMenu() {
  const router = useRouter()
  const [user, setUser] = useState<any>(null)
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    fetch('/api/users/me', { credentials: 'include' })
      .then(r => r.json())
      .then(d => setUser(d?.user || null))
      .catch(() => {})
  }, [])

  useEffect(() => {
    function handler(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [])

  async function logout() {
    await fetch('/api/users/logout', { method: 'POST', credentials: 'include' })
    setUser(null)
    router.push('/')
    router.refresh()
  }

  if (!user) {
    return (
      <div className="flex items-center gap-2">
        <Link href="/login" className="text-[10px] font-black uppercase tracking-widest text-black/50 dark:text-white/50 hover:text-orange-500 transition-colors px-4 py-2">
          Σύνδεση
        </Link>
        <Link href="/register" className="bg-orange-500 text-black text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-full hover:bg-orange-400 transition-colors">
          Εγγραφή
        </Link>
      </div>
    )
  }

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen(v => !v)}
        className="flex items-center gap-2 group"
      >
        <div className="w-9 h-9 rounded-full bg-orange-500/10 border border-orange-500/30 flex items-center justify-center overflow-hidden group-hover:border-orange-500 transition-colors">
          {user.avatar?.url ? (
            <Image src={user.avatar.url} alt={user.name || ''} width={36} height={36} className="object-cover w-full h-full" />
          ) : (
            <User size={16} className="text-orange-500" />
          )}
        </div>
        <span className="text-[10px] font-black uppercase tracking-widest hidden md:block text-black/60 dark:text-white/60 group-hover:text-orange-500 transition-colors max-w-[80px] truncate">
          {user.name?.split(' ')[0] || 'Προφίλ'}
        </span>
      </button>

      {open && (
        <div className="absolute right-0 top-full mt-3 w-52 bg-white dark:bg-zinc-900 border border-black/10 dark:border-white/10 rounded-2xl shadow-xl overflow-hidden z-50">
          <div className="px-5 py-4 border-b border-black/5 dark:border-white/5">
            <p className="font-black text-sm truncate">{user.name || 'Χρήστης'}</p>
            <p className="text-[10px] text-black/40 dark:text-white/40 truncate">{user.email}</p>
          </div>
          <div className="py-2">
            <Link href="/profile" onClick={() => setOpen(false)} className="flex items-center gap-3 px-5 py-3 text-xs font-black uppercase tracking-widest hover:bg-orange-500/5 hover:text-orange-500 transition-colors">
              <User size={14} /> Προφίλ
            </Link>
            <Link href="/recipes/submit" onClick={() => setOpen(false)} className="flex items-center gap-3 px-5 py-3 text-xs font-black uppercase tracking-widest hover:bg-orange-500/5 hover:text-orange-500 transition-colors">
              <ChefHat size={14} /> Υποβολή Συνταγής
            </Link>
            {user.role === 'admin' && (
              <Link href="/admin" onClick={() => setOpen(false)} className="flex items-center gap-3 px-5 py-3 text-xs font-black uppercase tracking-widest hover:bg-orange-500/5 hover:text-orange-500 transition-colors">
                <Settings size={14} /> Admin Panel
              </Link>
            )}
          </div>
          <div className="border-t border-black/5 dark:border-white/5 py-2">
            <button onClick={logout} className="w-full flex items-center gap-3 px-5 py-3 text-xs font-black uppercase tracking-widest hover:bg-red-500/5 hover:text-red-500 transition-colors">
              <LogOut size={14} /> Αποσύνδεση
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
