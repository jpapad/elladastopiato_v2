'use client'
import React, { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { UserPlus, Eye, EyeOff } from 'lucide-react'

export default function RegisterPage() {
  const router = useRouter()
  const [form, setForm] = useState({ name: '', email: '', password: '', confirm: '' })
  const [showPass, setShowPass] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  function update(field: string, value: string) {
    setForm(f => ({ ...f, [field]: value }))
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')
    if (form.password !== form.confirm) {
      setError('Οι κωδικοί δεν ταιριάζουν.')
      return
    }
    if (form.password.length < 8) {
      setError('Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.')
      return
    }
    setLoading(true)
    try {
      // Create account
      const res = await fetch('/api/users', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: form.name, email: form.email, password: form.password }),
        credentials: 'include',
      })
      const data = await res.json()
      if (!res.ok) {
        setError(data.errors?.[0]?.message || data.message || 'Κάτι πήγε στραβά.')
        return
      }
      // Auto-login after register
      await fetch('/api/users/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: form.email, password: form.password }),
        credentials: 'include',
      })
      router.push('/profile')
      router.refresh()
    } catch {
      setError('Κάτι πήγε στραβά. Δοκίμασε ξανά.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-white dark:bg-[#0E0C0A] flex items-center justify-center px-6 pt-20">
      <div className="w-full max-w-md">
        <div className="text-center mb-12">
          <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.5em] block mb-4">
            Δημιουργία Λογαριασμού
          </span>
          <h1 className="text-5xl font-black uppercase italic tracking-tighter">Εγγραφή</h1>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 dark:text-white/40 mb-2">
              Ονοματεπώνυμο
            </label>
            <input
              type="text"
              value={form.name}
              onChange={e => update('name', e.target.value)}
              required
              className="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-2xl px-6 py-4 text-sm outline-none focus:border-orange-500 transition-colors"
              placeholder="Γιάννης Παπαδόπουλος"
            />
          </div>

          <div>
            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 dark:text-white/40 mb-2">
              Email
            </label>
            <input
              type="email"
              value={form.email}
              onChange={e => update('email', e.target.value)}
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
                value={form.password}
                onChange={e => update('password', e.target.value)}
                required
                className="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-2xl px-6 py-4 text-sm outline-none focus:border-orange-500 transition-colors pr-14"
                placeholder="Τουλάχιστον 8 χαρακτήρες"
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

          <div>
            <label className="block text-[10px] font-black uppercase tracking-widest text-black/40 dark:text-white/40 mb-2">
              Επιβεβαίωση Κωδικού
            </label>
            <input
              type={showPass ? 'text' : 'password'}
              value={form.confirm}
              onChange={e => update('confirm', e.target.value)}
              required
              className="w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-2xl px-6 py-4 text-sm outline-none focus:border-orange-500 transition-colors"
              placeholder="••••••••"
            />
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
            <UserPlus size={18} />
            {loading ? 'Δημιουργία...' : 'Δημιουργία Λογαριασμού'}
          </button>
        </form>

        <p className="text-center text-black/40 dark:text-white/40 text-sm mt-8">
          Έχεις ήδη λογαριασμό;{' '}
          <Link href="/login" className="text-orange-500 font-black hover:underline">
            Σύνδεση
          </Link>
        </p>
      </div>
    </div>
  )
}
