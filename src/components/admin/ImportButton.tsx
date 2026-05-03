'use client'
import React from 'react'
import { useRouter } from 'next/navigation'

export default function ImportButton() {
  const router = useRouter()

  return (
    <div style={{ padding: '0 0 16px', display: 'flex', justifyContent: 'flex-end' }}>
      <button
        onClick={() => router.push('/admin/import-recipe')}
        style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: 8,
          padding: '8px 20px',
          borderRadius: 6,
          border: '1px solid var(--theme-elevation-200, rgba(255,255,255,0.15))',
          background: 'transparent',
          color: 'inherit',
          fontSize: 12,
          fontWeight: 700,
          textTransform: 'uppercase',
          letterSpacing: '0.08em',
          cursor: 'pointer',
          opacity: 0.7,
          transition: 'opacity 0.2s',
        }}
        onMouseEnter={e => (e.currentTarget.style.opacity = '1')}
        onMouseLeave={e => (e.currentTarget.style.opacity = '0.7')}
      >
        ↓ Εισαγωγή από URL
      </button>
    </div>
  )
}
