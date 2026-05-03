'use client'
import React, { useState } from 'react'

interface Props {
  title: string
  description?: string
  buttonText: string
}

export default function NewsletterForm({ title, description, buttonText }: Props) {
  const [email, setEmail] = useState('')
  const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'already' | 'error'>('idle')

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!email) return
    setStatus('loading')

    try {
      const res = await fetch('/api/newsletter', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email }),
      })
      const data = await res.json()

      if (data.message === 'subscribed') setStatus('success')
      else if (data.message === 'already_subscribed') setStatus('already')
      else setStatus('error')
    } catch {
      setStatus('error')
    }
  }

  return (
    <section className="max-w-5xl mx-auto px-6 py-24 bg-white/[0.02] border border-white/5 rounded-[3rem] text-center my-20">
      <h2 className="text-3xl font-black uppercase italic mb-4">{title}</h2>
      {description && <p className="text-white/40 mb-10">{description}</p>}

      {status === 'success' ? (
        <div className="text-orange-500 font-black uppercase tracking-widest text-sm py-4 animate-pulse">
          ✓ Εγγραφήκατε επιτυχώς!
        </div>
      ) : status === 'already' ? (
        <div className="text-white/50 font-black uppercase tracking-widest text-xs py-4">
          Είστε ήδη εγγεγραμμένοι.
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row max-w-md mx-auto gap-3 px-4">
          <input
            type="email"
            value={email}
            onChange={e => setEmail(e.target.value)}
            placeholder="Το email σας"
            required
            className="flex-1 bg-black/50 border border-white/10 rounded-full px-6 py-3 text-sm focus:border-orange-500 outline-none transition-colors"
          />
          <button
            type="submit"
            disabled={status === 'loading'}
            className="bg-orange-500 text-black px-8 py-3 rounded-full font-black uppercase text-[10px] tracking-widest hover:bg-white transition-colors disabled:opacity-50"
          >
            {status === 'loading' ? '...' : buttonText}
          </button>
        </form>
      )}

      {status === 'error' && (
        <p className="text-red-400 text-xs mt-4 uppercase tracking-widest">Σφάλμα. Δοκιμάστε ξανά.</p>
      )}
    </section>
  )
}
