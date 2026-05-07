import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { notFound } from 'next/navigation'
import { RenderBlocks } from '@/components/Blocks'

export const dynamic = 'force-dynamic'

export default async function DynamicPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params
  
  // Προστασία: Αν κάποιος προσπαθήσει να μπει στο /home, τον αφήνουμε 
  // ή τον στέλνουμε στην πραγματική αρχική, αλλά συνήθως το slug 'home' 
  // το κρατάμε μόνο για την αρχική μας σελίδα.
  if (slug === 'home') return notFound()

  const payload = await getPayload({ config: configPromise })

  const result = await payload.find({
    collection: 'pages',
    where: {
      slug: { equals: slug },
    },
    limit: 1,
  })

  const page = result.docs[0] as any

  if (!page) return notFound()

  return (
    <main className="min-h-screen bg-[#050505]">
      {/* Εδώ ο Page Builder κάνει όλη τη δουλειά! */}
      <RenderBlocks layout={page.layout} />
    </main>
  )
}