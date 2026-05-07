import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'

export default async function DashboardStats() {
  const payload = await getPayload({ config: configPromise })

  const [recipes, locations, pages, subscribers] = await Promise.all([
    payload.find({ collection: 'recipes', limit: 0, depth: 0 }),
    payload.find({ collection: 'locations', limit: 0, depth: 0 }),
    payload.find({ collection: 'pages', limit: 0, depth: 0 }),
    payload.find({ collection: 'subscribers', limit: 0, depth: 0 }),
  ])

  const [published, drafts] = await Promise.all([
    payload.find({ collection: 'recipes', limit: 0, depth: 0, where: { status: { equals: 'published' } } }),
    payload.find({ collection: 'recipes', limit: 0, depth: 0, where: { status: { equals: 'draft' } } }),
  ])

  const stats = [
    { label: 'Συνταγές', value: recipes.totalDocs, sub: `${published.totalDocs} δημ. · ${drafts.totalDocs} σχέδ.`, color: '#f97316' },
    { label: 'Τοποθεσίες', value: locations.totalDocs, sub: 'Περιφ., Νομοί, Δήμοι', color: '#3b82f6' },
    { label: 'Σελίδες', value: pages.totalDocs, sub: 'Page builder', color: '#8b5cf6' },
    { label: 'Subscribers', value: subscribers.totalDocs, sub: 'Newsletter', color: '#10b981' },
  ]

  return (
    <div style={{ padding: '24px 0 8px' }}>
      <h2 style={{ fontSize: 13, fontWeight: 700, letterSpacing: '0.1em', textTransform: 'uppercase', opacity: 0.5, marginBottom: 16 }}>
        Στατιστικά
      </h2>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 16 }}>
        {stats.map(({ label, value, sub, color }) => (
          <div
            key={label}
            style={{
              background: 'var(--theme-elevation-50, rgba(255,255,255,0.04))',
              border: '1px solid var(--theme-elevation-100, rgba(255,255,255,0.08))',
              borderRadius: 12,
              padding: '20px 24px',
            }}
          >
            <div style={{ fontSize: 32, fontWeight: 900, color, lineHeight: 1, marginBottom: 6 }}>
              {value}
            </div>
            <div style={{ fontSize: 13, fontWeight: 700, marginBottom: 4 }}>{label}</div>
            <div style={{ fontSize: 11, opacity: 0.4, textTransform: 'uppercase', letterSpacing: '0.05em' }}>{sub}</div>
          </div>
        ))}
      </div>
    </div>
  )
}
