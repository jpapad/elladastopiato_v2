'use client'
import React, { useState, useEffect, useRef } from 'react'
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
  tips?: string
  imageUrl?: string
  sourceUrl?: string
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

// ── Styles ────────────────────────────────────────────────────────────────────
const s = {
  page:    { padding: '40px 48px', maxWidth: 900, margin: '0 auto' } as React.CSSProperties,
  back:    { display: 'inline-flex', alignItems: 'center', gap: 6, fontSize: 11, fontWeight: 700, textTransform: 'uppercase' as const, letterSpacing: '0.1em', opacity: 0.4, cursor: 'pointer', marginBottom: 32, background: 'none', border: 'none', color: 'inherit', padding: 0 },
  heading: { fontSize: 28, fontWeight: 900, marginBottom: 8, letterSpacing: '-0.02em' } as React.CSSProperties,
  sub:     { fontSize: 13, opacity: 0.45, marginBottom: 32, lineHeight: 1.6 } as React.CSSProperties,
  tabs:    { display: 'flex', gap: 4, marginBottom: 32, borderBottom: '1px solid var(--theme-elevation-150, rgba(255,255,255,0.1))' } as React.CSSProperties,
  row:     { display: 'flex', gap: 12, marginBottom: 32 } as React.CSSProperties,
  input:   { flex: 1, padding: '10px 16px', borderRadius: 8, border: '1px solid var(--theme-elevation-150, rgba(255,255,255,0.12))', background: 'var(--theme-elevation-50, rgba(255,255,255,0.04))', color: 'inherit', fontSize: 14 } as React.CSSProperties,
  btn:     { padding: '10px 24px', borderRadius: 8, border: 'none', background: 'var(--theme-success-500, #E8700A)', color: '#000', fontWeight: 800, fontSize: 12, textTransform: 'uppercase' as const, letterSpacing: '0.08em', cursor: 'pointer' } as React.CSSProperties,
  btnSec:  { padding: '10px 24px', borderRadius: 8, border: '1px solid var(--theme-elevation-200)', background: 'transparent', color: 'inherit', fontWeight: 700, fontSize: 12, textTransform: 'uppercase' as const, letterSpacing: '0.08em', cursor: 'pointer' } as React.CSSProperties,
  card:    { background: 'var(--theme-elevation-50, rgba(255,255,255,0.03))', border: '1px solid var(--theme-elevation-150, rgba(255,255,255,0.1))', borderRadius: 12, padding: '28px 32px', marginBottom: 24 } as React.CSSProperties,
  label:   { fontSize: 11, fontWeight: 700, textTransform: 'uppercase' as const, letterSpacing: '0.1em', opacity: 0.5, marginBottom: 6, display: 'block' } as React.CSSProperties,
  select:  { width: '100%', padding: '8px 12px', borderRadius: 8, border: '1px solid var(--theme-elevation-150, rgba(255,255,255,0.12))', background: 'var(--theme-elevation-50)', color: 'inherit', fontSize: 13 } as React.CSSProperties,
  error:   { color: 'var(--theme-error-500, #ef4444)', fontSize: 13, marginBottom: 20, padding: '10px 16px', borderRadius: 8, background: 'rgba(239,68,68,0.08)', border: '1px solid rgba(239,68,68,0.2)' } as React.CSSProperties,
  badge:   { display: 'inline-block', padding: '2px 10px', borderRadius: 20, fontSize: 11, fontWeight: 700 } as React.CSSProperties,
  grid2:   { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 } as React.CSSProperties,
  stat:    { background: 'var(--theme-elevation-100, rgba(255,255,255,0.06))', borderRadius: 8, padding: '14px 18px' } as React.CSSProperties,
}

// ── Recipe preview + import shared UI ────────────────────────────────────────
function RecipePreview({
  data, category, setCategory, locationId, setLocationId, locations, creating, error, onCreate, onClear,
}: {
  data: ExtractedRecipe
  category: string
  setCategory: (v: string) => void
  locationId: string
  setLocationId: (v: string) => void
  locations: Location[]
  creating: boolean
  error: string
  onCreate: () => void
  onClear: () => void
}) {
  return (
    <>
      <div style={s.card}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 20 }}>
          <h2 style={{ fontSize: 20, fontWeight: 900, margin: 0 }}>{data.title}</h2>
          <span style={{ ...s.badge, background: 'rgba(249,115,22,0.15)', color: '#E8700A' }}>
            Βρέθηκε ✓
          </span>
        </div>

        <div style={s.grid2}>
          <div style={s.stat}>
            <div style={{ ...s.label, marginBottom: 4 }}>Υλικά</div>
            <div style={{ fontSize: 20, fontWeight: 800, color: data.ingredients.length ? '#E8700A' : 'inherit' }}>
              {data.ingredients.length}
              <span style={{ fontSize: 12, opacity: 0.5, fontWeight: 400, marginLeft: 6 }}>items</span>
            </div>
          </div>
          <div style={s.stat}>
            <div style={{ ...s.label, marginBottom: 4 }}>Βήματα Εκτέλεσης</div>
            <div style={{ fontSize: 20, fontWeight: 800, color: data.instructions.length ? '#E8700A' : 'inherit' }}>
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
            <div style={{ ...s.label, marginBottom: 4 }}>Μερίδες</div>
            <div style={{ fontSize: 13, fontWeight: 600 }}>
              {data.servings ? `${data.servings} μερίδες` : '—'}
            </div>
          </div>
        </div>

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
      </div>

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

      {error && <div style={s.error}>{error}</div>}

      <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
        <button
          style={{ ...s.btn, opacity: (!locationId || creating) ? 0.5 : 1 }}
          disabled={!locationId || creating}
          onClick={onCreate}
        >
          {creating ? 'Δημιουργία...' : 'Δημιουργία ως Προσχέδιο →'}
        </button>
        <button style={s.btnSec} onClick={onClear}>
          Καθαρισμός
        </button>
        {!locationId && (
          <span style={{ fontSize: 12, opacity: 0.45 }}>Επιλέξτε περιοχή για να συνεχίσετε</span>
        )}
      </div>
    </>
  )
}

// ── Main Component ────────────────────────────────────────────────────────────
export default function ImportRecipePage() {
  const router = useRouter()

  const [tab, setTab] = useState<'url' | 'pdf'>('url')
  const [locations, setLocations] = useState<Location[]>([])

  // URL tab state
  const [url, setUrl] = useState('')
  const [urlFetching, setUrlFetching] = useState(false)
  const [urlError, setUrlError] = useState('')
  const [urlData, setUrlData] = useState<ExtractedRecipe | null>(null)

  // PDF tab state
  const [pdfFile, setPdfFile] = useState<File | null>(null)
  const [pdfFetching, setPdfFetching] = useState(false)
  const [pdfError, setPdfError] = useState('')
  const [pdfData, setPdfData] = useState<ExtractedRecipe | null>(null)
  const fileInputRef = useRef<HTMLInputElement>(null)

  // Shared create state
  const [category, setCategory] = useState('kyrios')
  const [locationId, setLocationId] = useState('')
  const [creating, setCreating] = useState(false)
  const [createError, setCreateError] = useState('')

  useEffect(() => {
    fetch('/api/locations?limit=200&depth=0&sort=name')
      .then(r => r.json())
      .then(j => setLocations(j.docs || []))
  }, [])

  // ── URL import ──────────────────────────────────────────────────────────────
  async function handleAnalyzeUrl() {
    if (!url.trim()) return
    setUrlFetching(true)
    setUrlError('')
    setUrlData(null)
    try {
      const res = await fetch(`/api/import-recipe?url=${encodeURIComponent(url.trim())}`)
      const json = await res.json()
      if (res.status === 422) { setUrlError(json.error || 'Σφάλμα'); return }
      if (res.status === 206) { setUrlError(`⚠️ ${json.error}`); setUrlData(json); return }
      if (!res.ok) { setUrlError(json.error || 'Σφάλμα'); return }
      setUrlData(json)
    } catch {
      setUrlError('Αδύνατη η σύνδεση.')
    } finally {
      setUrlFetching(false)
    }
  }

  // ── PDF import ──────────────────────────────────────────────────────────────
  async function handleAnalyzePdf() {
    if (!pdfFile) return
    setPdfFetching(true)
    setPdfError('')
    setPdfData(null)
    try {
      const formData = new FormData()
      formData.append('pdf', pdfFile)
      const res = await fetch('/api/import-recipe-pdf', { method: 'POST', body: formData })
      const json = await res.json()
      if (!res.ok) { setPdfError(json.error || 'Σφάλμα ανάλυσης PDF.'); return }
      setPdfData(json)
    } catch {
      setPdfError('Αδύνατη η ανάλυση.')
    } finally {
      setPdfFetching(false)
    }
  }

  // ── Create recipe ───────────────────────────────────────────────────────────
  async function handleCreate() {
    const data = tab === 'url' ? urlData : pdfData
    if (!data || !category || !locationId) return
    setCreating(true)
    setCreateError('')
    try {
      const recipePayload: any = {
        title: data.title,
        status: 'draft',
        recipeCategory: category,
        location: locationId,
        description: data.description ? textToLexical(data.description) : undefined,
        ingredients: data.ingredients.length ? stringsToLexical(data.ingredients, 'bullet') : undefined,
        instructions: data.instructions.length ? stringsToLexical(data.instructions, 'number') : undefined,
        tips: data.tips ? textToLexical(data.tips) : undefined,
        prepTime: data.prepTime || undefined,
        cookTime: data.cookTime || undefined,
        servings: data.servings || undefined,
        seo: { metaTitle: data.title, metaDesc: data.description?.slice(0, 160) || undefined },
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
        setCreateError(`${json.error || 'Σφάλμα'}${details ? ` — ${details}` : ''}`)
        return
      }
      router.push(`/admin/collections/recipes/${json.id}`)
    } catch {
      setCreateError('Αδύνατη η δημιουργία.')
    } finally {
      setCreating(false)
    }
  }

  function clearUrl() { setUrlData(null); setUrl(''); setUrlError(''); setCreateError('') }
  function clearPdf() { setPdfData(null); setPdfFile(null); setPdfError(''); setCreateError(''); if (fileInputRef.current) fileInputRef.current.value = '' }

  const activeData = tab === 'url' ? urlData : pdfData

  return (
    <div style={s.page}>
      <button style={s.back} onClick={() => router.push('/admin/collections/recipes')}>
        ← Πίσω στις Συνταγές
      </button>

      <h1 style={s.heading}>Εισαγωγή Συνταγής</h1>
      <p style={s.sub}>
        Εισάγετε συνταγή από URL ιστοσελίδας ή από PDF αρχείο με τη βοήθεια AI (Claude).
      </p>

      {/* Tabs */}
      <div style={s.tabs}>
        {(['url', 'pdf'] as const).map(t => (
          <button
            key={t}
            onClick={() => setTab(t)}
            style={{
              padding: '8px 20px',
              background: 'none',
              border: 'none',
              borderBottom: tab === t ? '2px solid #E8700A' : '2px solid transparent',
              color: tab === t ? '#E8700A' : 'inherit',
              fontWeight: 700,
              fontSize: 12,
              textTransform: 'uppercase',
              letterSpacing: '0.08em',
              cursor: 'pointer',
              marginBottom: -1,
              opacity: tab === t ? 1 : 0.4,
            }}
          >
            {t === 'url' ? '🔗 Από URL' : '📄 Από PDF (AI)'}
          </button>
        ))}
      </div>

      {/* URL Tab */}
      {tab === 'url' && (
        <>
          <p style={{ ...s.sub, marginBottom: 24 }}>
            Επικολλήστε URL από Akis Petretzikis, Argiro, Gourmed, AllRecipes, κλπ.
          </p>
          <div style={s.row}>
            <input
              style={s.input}
              type="url"
              placeholder="https://akispetretzikis.com/recipe/..."
              value={url}
              onChange={e => setUrl(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && handleAnalyzeUrl()}
            />
            <button style={s.btn} onClick={handleAnalyzeUrl} disabled={urlFetching || !url.trim()}>
              {urlFetching ? 'Ανάλυση...' : 'Ανάλυση URL'}
            </button>
          </div>
          {urlError && <div style={s.error}>{urlError}</div>}
          {urlData && (
            <RecipePreview
              data={urlData}
              category={category} setCategory={setCategory}
              locationId={locationId} setLocationId={setLocationId}
              locations={locations}
              creating={creating} error={createError}
              onCreate={handleCreate} onClear={clearUrl}
            />
          )}
        </>
      )}

      {/* PDF Tab */}
      {tab === 'pdf' && (
        <>
          <p style={{ ...s.sub, marginBottom: 24 }}>
            Ανεβάστε ένα PDF με συνταγή (βιβλίο, περιοδικό, σκαναρισμένο έγγραφο).
            Το AI αναλύει το περιεχόμενο και εξάγει αυτόματα τη συνταγή.
          </p>

          {/* Drop zone */}
          <div
            onClick={() => fileInputRef.current?.click()}
            onDragOver={e => e.preventDefault()}
            onDrop={e => {
              e.preventDefault()
              const f = e.dataTransfer.files[0]
              if (f?.type === 'application/pdf' || f?.name.endsWith('.pdf')) {
                setPdfFile(f)
                setPdfData(null)
                setPdfError('')
              } else {
                setPdfError('Αποδεκτά μόνο αρχεία PDF.')
              }
            }}
            style={{
              border: '2px dashed var(--theme-elevation-200, rgba(255,255,255,0.15))',
              borderRadius: 12,
              padding: '48px 32px',
              textAlign: 'center',
              cursor: 'pointer',
              marginBottom: 24,
              background: pdfFile ? 'rgba(249,115,22,0.05)' : 'transparent',
              transition: 'background 0.2s',
            }}
          >
            <input
              ref={fileInputRef}
              type="file"
              accept="application/pdf,.pdf"
              style={{ display: 'none' }}
              onChange={e => {
                const f = e.target.files?.[0]
                if (f) { setPdfFile(f); setPdfData(null); setPdfError('') }
              }}
            />
            {pdfFile ? (
              <div>
                <div style={{ fontSize: 32, marginBottom: 8 }}>📄</div>
                <div style={{ fontWeight: 700, fontSize: 14 }}>{pdfFile.name}</div>
                <div style={{ fontSize: 12, opacity: 0.4, marginTop: 4 }}>
                  {(pdfFile.size / 1024).toFixed(0)} KB — Κάντε κλικ για αλλαγή
                </div>
              </div>
            ) : (
              <div>
                <div style={{ fontSize: 40, marginBottom: 12 }}>📎</div>
                <div style={{ fontWeight: 700, fontSize: 14, marginBottom: 6 }}>
                  Σύρετε PDF εδώ ή κάντε κλικ για επιλογή
                </div>
                <div style={{ fontSize: 12, opacity: 0.4 }}>Μέγιστο μέγεθος: 10MB</div>
              </div>
            )}
          </div>

          <div style={s.row}>
            <button
              style={{ ...s.btn, opacity: (!pdfFile || pdfFetching) ? 0.5 : 1 }}
              onClick={handleAnalyzePdf}
              disabled={!pdfFile || pdfFetching}
            >
              {pdfFetching ? '⏳ Ανάλυση AI...' : '🤖 Εξαγωγή Συνταγής με AI'}
            </button>
            {pdfFile && !pdfFetching && (
              <button style={s.btnSec} onClick={clearPdf}>Καθαρισμός</button>
            )}
          </div>

          {pdfFetching && (
            <div style={{ fontSize: 13, opacity: 0.5, marginBottom: 16, display: 'flex', alignItems: 'center', gap: 8 }}>
              <span style={{ display: 'inline-block', animation: 'spin 1s linear infinite' }}>⏳</span>
              Το AI διαβάζει το PDF... (10-30 δευτερόλεπτα)
            </div>
          )}

          {pdfError && <div style={s.error}>{pdfError}</div>}

          {pdfData && (
            <RecipePreview
              data={pdfData}
              category={category} setCategory={setCategory}
              locationId={locationId} setLocationId={setLocationId}
              locations={locations}
              creating={creating} error={createError}
              onCreate={handleCreate} onClear={clearPdf}
            />
          )}
        </>
      )}
    </div>
  )
}
