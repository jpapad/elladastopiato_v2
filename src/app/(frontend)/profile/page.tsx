'use client'
import React, { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import Image from 'next/image'
import Link from 'next/link'
import { User, LogOut, ChefHat, Edit3, Check, X } from 'lucide-react'

export default function ProfilePage() {
  const router = useRouter()
  const [user, setUser] = useState<any>(null)
  const [recipes, setRecipes] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(false)
  const [name, setName] = useState('')
  const [bio, setBio] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    fetch('/api/users/me', { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        if (!data?.user) { router.push('/login'); return }
        setUser(data.user)
        setName(data.user.name || '')
        setBio(data.user.bio || '')
        // Load user's submitted recipes
        fetch(`/api/recipes?where[submittedBy][equals]=${data.user.id}&limit=20`, { credentials: 'include' })
          .then(r => r.json())
          .then(d => setRecipes(d.docs || []))
          .catch(() => {})
      })
      .catch(() => router.push('/login'))
      .finally(() => setLoading(false))
  }, [router])

  async function handleLogout() {
    await fetch('/api/users/logout', { method: 'POST', credentials: 'include' })
    router.push('/')
    router.refresh()
  }

  async function handleSave() {
    setSaving(true)
    try {
      const res = await fetch(`/api/users/${user.id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, bio }),
        credentials: 'include',
      })
      const data = await res.json()
      if (res.ok) {
        setUser({ ...user, name: data.doc?.name || name, bio: data.doc?.bio || bio })
        setEditing(false)
      }
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="w-8 h-8 border-2 border-orange-500 border-t-transparent rounded-full animate-spin" />
      </div>
    )
  }

  if (!user) return null

  return (
    <div className="min-h-screen bg-white dark:bg-[#0E0C0A] pt-20 pb-24 px-6">
      <div className="max-w-3xl mx-auto">

        {/* Profile header */}
        <div className="flex flex-col md:flex-row items-start md:items-center gap-8 mb-16 pb-16 border-b border-black/10 dark:border-white/10">
          <div className="w-24 h-24 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center flex-shrink-0 overflow-hidden">
            {user.avatar?.url ? (
              <Image src={user.avatar.url} alt={user.name || ''} width={96} height={96} className="object-cover w-full h-full" />
            ) : (
              <User size={36} className="text-orange-500" />
            )}
          </div>

          <div className="flex-grow">
            {editing ? (
              <div className="space-y-3">
                <input
                  value={name}
                  onChange={e => setName(e.target.value)}
                  className="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-xl px-4 py-2 text-sm w-full outline-none focus:border-orange-500"
                  placeholder="Ονοματεπώνυμο"
                />
                <textarea
                  value={bio}
                  onChange={e => setBio(e.target.value)}
                  rows={3}
                  className="bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-xl px-4 py-2 text-sm w-full outline-none focus:border-orange-500 resize-none"
                  placeholder="Λίγα λόγια για σένα..."
                />
                <div className="flex gap-2">
                  <button onClick={handleSave} disabled={saving} className="flex items-center gap-2 bg-orange-500 text-black px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-orange-400 transition-colors">
                    <Check size={14} /> {saving ? 'Αποθήκευση...' : 'Αποθήκευση'}
                  </button>
                  <button onClick={() => setEditing(false)} className="flex items-center gap-2 bg-black/5 dark:bg-white/5 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-black/10 dark:hover:bg-white/10 transition-colors">
                    <X size={14} /> Ακύρωση
                  </button>
                </div>
              </div>
            ) : (
              <>
                <div className="flex items-center gap-3 mb-1">
                  <h1 className="text-3xl font-black uppercase italic tracking-tighter">
                    {user.name || 'Χρήστης'}
                  </h1>
                  <button onClick={() => setEditing(true)} className="text-black/30 dark:text-white/30 hover:text-orange-500 transition-colors">
                    <Edit3 size={16} />
                  </button>
                </div>
                <p className="text-black/40 dark:text-white/40 text-sm mb-2">{user.email}</p>
                {user.bio && <p className="text-black/60 dark:text-white/60 text-sm italic">{user.bio}</p>}
              </>
            )}
          </div>

          <button
            onClick={handleLogout}
            className="flex items-center gap-2 text-black/30 dark:text-white/30 hover:text-red-500 transition-colors text-xs font-black uppercase tracking-widest"
          >
            <LogOut size={16} /> Αποσύνδεση
          </button>
        </div>

        {/* Submitted recipes */}
        <div>
          <div className="flex items-center justify-between mb-8">
            <h2 className="text-2xl font-black uppercase italic tracking-tighter flex items-center gap-3">
              <ChefHat size={24} className="text-orange-500" /> Οι Συνταγές μου
            </h2>
            <Link
              href="/recipes/submit"
              className="bg-orange-500 text-black px-6 py-3 rounded-full text-xs font-black uppercase tracking-widest hover:bg-orange-400 transition-colors"
            >
              + Νέα Συνταγή
            </Link>
          </div>

          {recipes.length === 0 ? (
            <div className="py-20 text-center border border-dashed border-black/10 dark:border-white/10 rounded-3xl">
              <ChefHat size={40} className="text-black/10 dark:text-white/10 mx-auto mb-4" />
              <p className="text-black/30 dark:text-white/30 text-sm">
                Δεν έχεις υποβάλει ακόμα συνταγές.
              </p>
              <Link href="/recipes/submit" className="text-orange-500 text-sm font-black hover:underline mt-2 inline-block">
                Υπόβαλε την πρώτη σου!
              </Link>
            </div>
          ) : (
            <div className="space-y-4">
              {recipes.map((r: any) => (
                <div key={r.id} className="flex items-center justify-between p-6 bg-black/5 dark:bg-white/5 rounded-2xl border border-black/10 dark:border-white/10">
                  <div>
                    <h3 className="font-black uppercase italic">{r.title}</h3>
                    <span className={`text-[10px] font-black uppercase tracking-widest mt-1 inline-block ${
                      r.status === 'published' ? 'text-green-500' :
                      r.status === 'pending' ? 'text-orange-500' :
                      r.status === 'rejected' ? 'text-red-500' : 'text-white/40'
                    }`}>
                      {r.status === 'published' ? '✅ Δημοσιευμένη' :
                       r.status === 'pending' ? '⏳ Αναμονή Έγκρισης' :
                       r.status === 'rejected' ? '❌ Απορρίφθηκε' : '📝 Προσχέδιο'}
                    </span>
                  </div>
                  {r.status === 'published' && (
                    <Link href={`/recipes/${r.slug || r.id}`} className="text-orange-500 text-xs font-black hover:underline">
                      Προβολή →
                    </Link>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
