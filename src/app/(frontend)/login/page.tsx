'use client'
import React, { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { LogIn, Eye, EyeOff } from 'lucide-react'

export default function LoginPage() {
  const router = useRouter()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPass, setShowPass] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const res = await fetch('/api/users/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
        credentials: 'include',
      })
      const data = await res.json()
      if (!res.ok) {
        setError(data.errors?.[0]?.message || data.message || 'Λάθος email ή κωδικός.')
      } else {
        router.push('/profile')
        router.refresh()
      }
    } catch {
      setError('Κάτι πήγε στραβά. Δοκίμασε ξανά.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-white dark:bg-[#050505] flex items-center justify-center px-6 pt-20">
      <div className="w-full max-w-md">
        <div className="text-center mb-12">
          <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.5em] block mb-4">
            Καλωσόρισες πίσω
          </span>
          <h1 className="text-5xl font-black uppercase italic tracking-tighter">Σύνδεση</h1>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 dark:text-white/40 mb-2">
              Email
            </label>
            <input
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
              className="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-2xl px-6 py-4 text-sm outline-none focus:border-orange-500 transition-colors"
              placeholder="name@example.com"
            />
          </div>

          <div>
            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 dark:text-white/40 mb-2">
              Κωδικός
            </label>
            <div className="relative">
              <input
                type={showPass ? 'text' : 'password'}
                value={password}
                onChange={e => setPassword(e.target.value)}
                required
                className="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-2xl px-6 py-4 text-sm outline-none focus:border-orange-500 transition-colors pr-14"
                placeholder="••••••••"
              />
              <button
                type="button"
                onClick={() => setShowPass(v => !v)}
                className="absolute right-4 top-1/2 -translate-y-1/2 text-black/30 dark:text-white/30 hover:text-orange-500 transition-colors"
              >
                {showPass ? <EyeOff size={18} /> : <Eye size={18} />}
              </button>
            </div>
          </div>

          {error && (
            <p className="text-red-500 text-xs font-medium bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3">
              {error}
            </p>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-orange-500 text-black font-black uppercase tracking-widest rounded-2xl py-4 hover:bg-orange-400 transition-colors disabled:opacity-50 flex items-center justify-center gap-3 mt-6"
          >
            <LogIn size={18} />
            {loading ? 'Σύνδεση...' : 'Σύνδεση'}
          </button>
        </form>

        <p className="text-center text-black/40 dark:text-white/40 text-sm mt-8">
          Δεν έχεις λογαριασμό;{' '}
          <Link href="/register" className="text-orange-500 font-black hover:underline">
            Εγγραφή
          </Link>
        </p>
      </div>
    </div>
  )
}
