'use client'
import React, { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'

// ── Lexical helpers ───────────────────────────────────────────────────────────
function makeText(text: string) {
  return { type: 'text', format: 0, style: '', mode: 'normal', detail: 0, text, version: 1 }
}

function makeParagraph(text: string) {
  return {
    type: 'paragraph', format: '', indent: 0, version: 1,
    direction: 'ltr', textFormat: 0, textStyle: '',
    children: [makeText(text)],
  }
}

function stringsToLexical(items: string[], listType: 'bullet' | 'number') {
  return {
    root: {
      type: 'root', format: '', indent: 0, version: 1, direction: 'ltr',
      children: [{
        type: 'list', listType, start: 1,
        tag: listType === 'bullet' ? 'ul' : 'ol',
        format: '', indent: 0, version: 1, direction: 'ltr',
        children: items.map((text, i) => ({
          type: 'listitem', value: i + 1,
          format: '', indent: 0, version: 1, direction: 'ltr',
          children: [makeText(text.trim())],
        })),
      }],
    },
  }
}

function textToLexical(text: string) {
  const paras = text.split('\n').map(s => s.trim()).filter(Boolean)
  return {
    root: {
      type: 'root', format: '', indent: 0, version: 1, direction: 'ltr',
      children: paras.length ? paras.map(makeParagraph) : [makeParagraph('')],
    },
  }
}

// ── Types ─────────────────────────────────────────────────────────────────────
interface ExtractedRecipe {
  title: string
  description: string
  ingredients: string[]
  instructions: string[]
  prepTime: number
  cookTime: number
  servings: number | null
  imageUrl: string
  sourceUrl: string
}

interface Location { id: string; name: string }

const CATEGORIES = [
  { label: 'Ορεκτικά',     value: 'orektika' },
  { label: 'Κυρίως Πιάτα', value: 'kyrios' },
  { label: 'Θαλασσινά',    value: 'thalassina' },
  { label: 'Γλυκά',        value: 'glyka' },
  { label: 'Πίτες',        value: 'pites' },
  { label: 'Σαλάτες',      value: 'salates' },
]

// ── Component ─────────────────────────────────────────────────────────────────
export default function ImportRecipePage() {
  const router = useRouter()

  const [url, setUrl] = useState('')
  const [fetching, setFetching] = useState(false)
  const [creating, setCreating] = useState(false)
  const [error, setError] = useState('')
  const [data, setData] = useState<ExtractedRecipe | null>(null)
  const [category, setCategory] = useState('kyrios')
  const [locationId, setLocationId] = useState('')
  const [locations, setLocations] = useState<Location[]>([])

  useEffect(() => {
    fetch('/api/locations?limit=200&depth=0&sort=name')
      .then(r => r.json())
      .then(j => setLocations(j.docs || []))
  }, [])

  async function handleAnalyze() {
    if (!url.trim()) return
    setFetching(true)
    setError('')
    setData(null)
    try {
      const res = await fetch(`/api/import-recipe?url=${encodeURIComponent(url.trim())}`)
      const json = await res.json()
      if (res.status === 422) { setError(json.error || 'Σφάλμα'); return }
      if (res.status === 206) {
        // Partial result — set data with warning
        setError(`⚠️ ${json.error}`)
        setData(json)
        return
      }
      if (!res.ok) { setError(json.error || 'Σφάλμα'); return }
      setData(json)
    } catch {
      setError('Αδύνατη η σύνδεση.')
    } finally {
      setFetching(false)
    }
  }

  async function handleCreate() {
    if (!data || !category || !locationId) return
    setCreating(true)
    setError('')
    try {
      const recipePayload: any = {
        title: data.title,
        status: 'draft',
        recipeCategory: category,
        location: locationId,
        description: data.description ? textToLexical(data.description) : undefined,
        ingredients: data.ingredients.length ? stringsToLexical(data.ingredients, 'bullet') : undefined,
        instructions: data.instructions.length ? stringsToLexical(data.instructions, 'number') : undefined,
        prepTime: data.prepTime || undefined,
        cookTime: data.cookTime || undefined,
        servings: data.servings || undefined,
        imageUrl: data.imageUrl || undefined,
        seo: {
          metaTitle: data.title,
          metaDesc: data.description?.slice(0, 160) || undefined,
        },
      }

      const res = await fetch('/api/create-recipe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(recipePayload),
      })
      const json = await res.json()
      if (!res.ok) {
        const details = json.details
          ? Object.entries(json.details).map(([k, v]) => `${k}: ${v}`).join(', ')
          : ''
        setError(`${json.error || 'Σφάλμα'}${details ? ` — ${details}` : ''}`)
        return
      }
      router.push(`/admin/collections/recipes/${json.id}`)
    } catch {
      setError('Αδύνατη η δημιουργία.')
    } finally {
      setCreating(false)
    }
  }

  // ── Styles (Payload admin CSS vars) ───────────────────────────────────────
  const s = {
    page:    { padding: '40px 48px', maxWidth: 900, margin: '0 auto' } as React.CSSProperties,
    back:    { display: 'inline-flex', alignItems: 'center', gap: 6, fontSize: 11, fontWeight: 700, textTransform: 'uppercase' as const, letterSpacing: '0.1em', opacity: 0.4, cursor: 'pointer', marginBottom: 32, background: 'none', border: 'none', color: 'inherit', padding: 0 },
    heading: { fontSize: 28, fontWeight: 900, marginBottom: 8, letterSpacing: '-0.02em' } as React.CSSProperties,
    sub:     { fontSize: 13, opacity: 0.45, marginBottom: 40, lineHeight: 1.6 } as React.CSSProperties,
    row:     { display: 'flex', gap: 12, marginBottom: 32 } as React.CSSProperties,
    input:   { flex: 1, padding: '10px 16px', borderRadius: 8, border: '1px solid var(--theme-elevation-150, rgba(255,255,255,0.12))', background: 'var(--theme-elevation-50, rgba(255,255,255,0.04))', color: 'inherit', fontSize: 14 } as React.CSSProperties,
    btn:     { padding: '10px 24px', borderRadius: 8, border: 'none', background: 'var(--theme-success-500, #f97316)', color: '#000', fontWeight: 800, fontSize: 12, textTransform: 'uppercase' as const, letterSpacing: '0.08em', cursor: 'pointer' } as React.CSSProperties,
    btnSec:  { padding: '10px 24px', borderRadius: 8, border: '1px solid var(--theme-elevation-200)', background: 'transparent', color: 'inherit', fontWeight: 700, fontSize: 12, textTransform: 'uppercase' as const, letterSpacing: '0.08em', cursor: 'pointer' } as React.CSSProperties,
    card:    { background: 'var(--theme-elevation-50, rgba(255,255,255,0.03))', border: '1px solid var(--theme-elevation-150, rgba(255,255,255,0.1))', borderRadius: 12, padding: '28px 32px', marginBottom: 24 } as React.CSSProperties,
    label:   { fontSize: 11, fontWeight: 700, textTransform: 'uppercase' as const, letterSpacing: '0.1em', opacity: 0.5, marginBottom: 6, display: 'block' } as React.CSSProperties,
    select:  { width: '100%', padding: '8px 12px', borderRadius: 8, border: '1px solid var(--theme-elevation-150, rgba(255,255,255,0.12))', background: 'var(--theme-elevation-50)', color: 'inherit', fontSize: 13 } as React.CSSProperties,
    error:   { color: 'var(--theme-error-500, #ef4444)', fontSize: 13, marginBottom: 20, padding: '10px 16px', borderRadius: 8, background: 'rgba(239,68,68,0.08)', border: '1px solid rgba(239,68,68,0.2)' } as React.CSSProperties,
    badge:   { display: 'inline-block', padding: '2px 10px', borderRadius: 20, fontSize: 11, fontWeight: 700 } as React.CSSProperties,
    grid2:   { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 } as React.CSSProperties,
    stat:    { background: 'var(--theme-elevation-100, rgba(255,255,255,0.06))', borderRadius: 8, padding: '14px 18px' } as React.CSSProperties,
  }

  return (
    <div style={s.page}>
      {/* Back */}
      <button style={s.back} onClick={() => router.push('/admin/collections/recipes')}>
        ← Πίσω στις Συνταγές
      </button>

      <h1 style={s.heading}>Εισαγωγή Συνταγής από URL</h1>
      <p style={s.sub}>
        Επικολλήστε URL από Akis Petretzikis, Argiro, Gourmed, AllRecipes, κλπ.
        Η σελίδα πρέπει να περιέχει δομημένα δεδομένα Schema.org Recipe.
      </p>

      {/* URL input */}
      <div style={s.row}>
        <input
          style={s.input}
          type="url"
          placeholder="https://akispetretzikis.com/recipe/..."
          value={url}
          onChange={e => setUrl(e.target.value)}
          onKeyDown={e => e.key === 'Enter' && handleAnalyze()}
        />
        <button style={s.btn} onClick={handleAnalyze} disabled={fetching || !url.trim()}>
          {fetching ? 'Ανάλυση...' : 'Ανάλυση URL'}
        </button>
      </div>

      {error && <div style={s.error}>{error}</div>}

      {/* Preview */}
      {data && (
        <>
          <div style={s.card}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 20 }}>
              <h2 style={{ fontSize: 20, fontWeight: 900, margin: 0 }}>{data.title}</h2>
              <span style={{ ...s.badge, background: 'rgba(249,115,22,0.15)', color: '#f97316' }}>
                Βρέθηκε ✓
              </span>
            </div>

            {/* Stats row */}
            <div style={s.grid2}>
              <div style={s.stat}>
                <div style={{ ...s.label, marginBottom: 4 }}>Υλικά</div>
                <div style={{ fontSize: 20, fontWeight: 800, color: data.ingredients.length ? '#f97316' : 'inherit' }}>
                  {data.ingredients.length}
                  <span style={{ fontSize: 12, opacity: 0.5, fontWeight: 400, marginLeft: 6 }}>items</span>
                </div>
              </div>
              <div style={s.stat}>
                <div style={{ ...s.label, marginBottom: 4 }}>Βήματα Εκτέλεσης</div>
                <div style={{ fontSize: 20, fontWeight: 800, color: data.instructions.length ? '#f97316' : 'inherit' }}>
                  {data.instructions.length}
                  <span style={{ fontSize: 12, opacity: 0.5, fontWeight: 400, marginLeft: 6 }}>βήματα</span>
                </div>
              </div>
              <div style={s.stat}>
                <div style={{ ...s.label, marginBottom: 4 }}>Χρόνοι</div>
                <div style={{ fontSize: 13, fontWeight: 600 }}>
                  {data.prepTime > 0 ? `${data.prepTime}' προετ.` : '—'}
                  {data.cookTime > 0 ? ` · ${data.cookTime}' μαγ.` : ''}
                </div>
              </div>
              <div style={s.stat}>
                <div style={{ ...s.label, marginBottom: 4 }}>Μερίδες / Εικόνα</div>
                <div style={{ fontSize: 13, fontWeight: 600 }}>
                  {data.servings ? `${data.servings} μερίδες` : '—'}
                  {' · '}
                  <span style={{ color: data.imageUrl ? '#10b981' : '#ef4444' }}>
                    {data.imageUrl ? 'Εικόνα ✓' : 'Εικόνα ✗'}
                  </span>
                </div>
              </div>
            </div>

            {/* Scrollable preview of ingredients/instructions */}
            {data.ingredients.length > 0 && (
              <details style={{ marginTop: 16 }}>
                <summary style={{ ...s.label, cursor: 'pointer', marginBottom: 0 }}>
                  Προεπισκόπηση Υλικών ({data.ingredients.length})
                </summary>
                <ul style={{ fontSize: 13, opacity: 0.7, lineHeight: 1.8, paddingLeft: 20, marginTop: 10 }}>
                  {data.ingredients.map((ing, i) => <li key={i}>{ing}</li>)}
                </ul>
              </details>
            )}
            {data.instructions.length > 0 && (
              <details style={{ marginTop: 12 }}>
                <summary style={{ ...s.label, cursor: 'pointer', marginBottom: 0 }}>
                  Προεπισκόπηση Εκτέλεσης ({data.instructions.length} βήματα)
                </summary>
                <ol style={{ fontSize: 13, opacity: 0.7, lineHeight: 1.8, paddingLeft: 20, marginTop: 10 }}>
                  {data.instructions.map((step, i) => <li key={i}>{step}</li>)}
                </ol>
              </details>
            )}

            {data.imageUrl && (
              <div style={{ marginTop: 16, fontSize: 12, opacity: 0.4, wordBreak: 'break-all' }}>
                🖼 Εικόνα: {data.imageUrl.slice(0, 80)}...
                <br />
                <span style={{ opacity: 0.7 }}>Προσθέστε την εικόνα χειροκίνητα από το URL παραπάνω.</span>
              </div>
            )}
          </div>

          {/* Category + Location selects */}
          <div style={{ ...s.grid2, marginBottom: 28 }}>
            <div>
              <label style={s.label}>Κατηγορία *</label>
              <select style={s.select} value={category} onChange={e => setCategory(e.target.value)}>
                {CATEGORIES.map(c => (
                  <option key={c.value} value={c.value}>{c.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label style={s.label}>Περιοχή *</label>
              <select style={s.select} value={locationId} onChange={e => setLocationId(e.target.value)}>
                <option value="">— Επιλέξτε περιοχή —</option>
                {locations.map(l => (
                  <option key={l.id} value={l.id}>{l.name}</option>
                ))}
              </select>
            </div>
          </div>

          {/* Action buttons */}
          <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
            <button
              style={{ ...s.btn, opacity: (!locationId || creating) ? 0.5 : 1 }}
              disabled={!locationId || creating}
              onClick={handleCreate}
            >
              {creating ? 'Δημιουργία...' : 'Δημιουργία ως Προσχέδιο →'}
            </button>
            <button style={s.btnSec} onClick={() => { setData(null); setUrl(''); setError('') }}>
              Καθαρισμός
            </button>
            {!locationId && (
              <span style={{ fontSize: 12, opacity: 0.45 }}>Επιλέξτε περιοχή για να συνεχίσετε</span>
            )}
          </div>
        </>
      )}
    </div>
  )
}
