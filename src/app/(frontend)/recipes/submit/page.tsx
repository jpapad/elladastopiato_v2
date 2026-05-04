'use client'
import React, { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { ChefHat, CheckCircle } from 'lucide-react'
import { CATEGORY_OPTIONS } from '@/lib/categories'

export default function SubmitRecipePage() {
  const router = useRouter()
  const [user, setUser] = useState<any>(null)
  const [locations, setLocations] = useState<any[]>([])
  const [submitted, setSubmitted] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const [form, setForm] = useState({
    title: '', location: '', recipeCategory: '',
    prepTime: '', cookTime: '', servings: '',
    description: '', ingredients: '', instructions: '', tips: '',
  })

  useEffect(() => {
    fetch('/api/users/me', { credentials: 'include' })
      .then(r => r.json())
      .then(d => { if (!d?.user) router.push('/login'); else setUser(d.user) })
      .catch(() => router.push('/login'))

    fetch('/api/locations?limit=200&sort=name')
      .then(r => r.json())
      .then(d => setLocations(d.docs || []))
  }, [router])

  function update(field: string, value: string) {
    setForm(f => ({ ...f, [field]: value }))
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const res = await fetch('/api/recipes/submit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...form, userId: user.id }),
        credentials: 'include',
      })
      const data = await res.json()
      if (!res.ok) {
        setError(data.error || 'Κάτι πήγε στραβά.')
      } else {
        setSubmitted(true)
      }
    } catch {
      setError('Κάτι πήγε στραβά. Δοκίμασε ξανά.')
    } finally {
      setLoading(false)
    }
  }

  if (submitted) {
    return (
      <div className="min-h-screen flex items-center justify-center px-6">
        <div className="text-center max-w-md">
          <CheckCircle size={64} className="text-orange-500 mx-auto mb-6" />
          <h2 className="text-4xl font-black uppercase italic mb-4">Υποβλήθηκε!</h2>
          <p className="text-black/50 dark:text-white/50 mb-8">
            Η συνταγή σου υποβλήθηκε για έλεγχο. Θα δημοσιευτεί μετά από έγκριση.
          </p>
          <button onClick={() => router.push('/profile')} className="bg-orange-500 text-black px-8 py-3 rounded-full font-black uppercase tracking-widest hover:bg-orange-400 transition-colors text-sm">
            Πίσω στο Προφίλ
          </button>
        </div>
      </div>
    )
  }

  const inputCls = "w-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-2xl px-5 py-3 text-sm outline-none focus:border-orange-500 transition-colors"
  const labelCls = "block text-[10px] font-black uppercase tracking-widest text-black/40 dark:text-white/40 mb-2"

  return (
    <div className="min-h-screen bg-white dark:bg-[#050505] pt-20 pb-24 px-6">
      <div className="max-w-2xl mx-auto">
        <div className="text-center mb-12">
          <ChefHat size={40} className="text-orange-500 mx-auto mb-4" />
          <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.5em] block mb-3">
            Μοιράσου τη γεύση σου
          </span>
          <h1 className="text-5xl font-black uppercase italic tracking-tighter">Υποβολή Συνταγής</h1>
          <p className="text-black/40 dark:text-white/40 text-sm mt-4">
            Η συνταγή θα ελεγχθεί και θα δημοσιευτεί μετά από έγκριση.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <label className={labelCls}>Τίτλος Συνταγής *</label>
            <input type="text" value={form.title} onChange={e => update('title', e.target.value)} required className={inputCls} placeholder="π.χ. Μουσακάς Θεσσαλονίκης" />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={labelCls}>Περιοχή *</label>
              <select value={form.location} onChange={e => update('location', e.target.value)} required className={inputCls}>
                <option value="">Επέλεξε...</option>
                {locations.map((l: any) => (
                  <option key={l.id} value={l.id}>{l.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className={labelCls}>Κατηγορία *</label>
              <select value={form.recipeCategory} onChange={e => update('recipeCategory', e.target.value)} required className={inputCls}>
                <option value="">Επέλεξε...</option>
                {CATEGORY_OPTIONS.map((c: any) => (
                  <option key={c.value} value={c.value}>{c.label}</option>
                ))}
              </select>
            </div>
          </div>

          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className={labelCls}>Προετοιμασία (λεπτά)</label>
              <input type="number" value={form.prepTime} onChange={e => update('prepTime', e.target.value)} className={inputCls} placeholder="30" />
            </div>
            <div>
              <label className={labelCls}>Μαγείρεμα (λεπτά)</label>
              <input type="number" value={form.cookTime} onChange={e => update('cookTime', e.target.value)} className={inputCls} placeholder="60" />
            </div>
            <div>
              <label className={labelCls}>Μερίδες</label>
              <input type="number" value={form.servings} onChange={e => update('servings', e.target.value)} className={inputCls} placeholder="4" />
            </div>
          </div>

          <div>
            <label className={labelCls}>Περιγραφή / Ιστορία</label>
            <textarea value={form.description} onChange={e => update('description', e.target.value)} rows={3} className={inputCls + ' resize-none'} placeholder="Λίγα λόγια για τη συνταγή..." />
          </div>

          <div>
            <label className={labelCls}>Υλικά *</label>
            <textarea value={form.ingredients} onChange={e => update('ingredients', e.target.value)} required rows={6} className={inputCls + ' resize-none'} placeholder="- 500γρ κιμάς&#10;- 2 μελιτζάνες&#10;- ..." />
          </div>

          <div>
            <label className={labelCls}>Εκτέλεση *</label>
            <textarea value={form.instructions} onChange={e => update('instructions', e.target.value)} required rows={8} className={inputCls + ' resize-none'} placeholder="Βήμα 1: ...&#10;Βήμα 2: ..." />
          </div>

          <div>
            <label className={labelCls}>Μυστικά Επιτυχίας</label>
            <textarea value={form.tips} onChange={e => update('tips', e.target.value)} rows={3} className={inputCls + ' resize-none'} placeholder="Συμβουλές για τέλειο αποτέλεσμα..." />
          </div>

          {error && (
            <p className="text-red-500 text-xs bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3">{error}</p>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-orange-500 text-black font-black uppercase tracking-widest rounded-2xl py-4 hover:bg-orange-400 transition-colors disabled:opacity-50 flex items-center justify-center gap-3"
          >
            <ChefHat size={18} />
            {loading ? 'Υποβολή...' : 'Υποβολή Συνταγής'}
          </button>
        </form>
      </div>
    </div>
  )
}
