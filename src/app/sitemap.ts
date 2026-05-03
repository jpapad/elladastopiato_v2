import { getPayload } from 'payload'
import configPromise from '@payload-config'
import type { MetadataRoute } from 'next'

export const dynamic = 'force-dynamic'

const BASE = process.env.NEXT_PUBLIC_SERVER_URL || 'https://elladastopiato.gr'

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  try {
  const payload = await getPayload({ config: configPromise })

  const [recipes, locations, pages] = await Promise.all([
    payload.find({ collection: 'recipes', limit: 500, depth: 0,
      where: { status: { equals: 'published' } } }),
    payload.find({ collection: 'locations', limit: 500, depth: 0 }),
    payload.find({ collection: 'pages', limit: 200, depth: 0,
      where: { status: { equals: 'published' } } }),
  ])

  const recipeUrls = recipes.docs.map((r: any) => ({
    url: `${BASE}/recipes/${r.slug || r.id}`,
    lastModified: new Date(r.updatedAt),
    changeFrequency: 'monthly' as const,
    priority: 0.8,
  }))

  const locationUrls = locations.docs.map((l: any) => ({
    url: `${BASE}/regions/${l.slug || l.id}`,
    lastModified: new Date(l.updatedAt),
    changeFrequency: 'monthly' as const,
    priority: 0.6,
  }))

  const pageUrls = pages.docs
    .filter((p: any) => p.slug !== 'home')
    .map((p: any) => ({
      url: `${BASE}/${p.slug}`,
      lastModified: new Date(p.updatedAt),
      changeFrequency: 'weekly' as const,
      priority: 0.7,
    }))

  return [
    { url: BASE, lastModified: new Date(), changeFrequency: 'weekly', priority: 1.0 },
    { url: `${BASE}/recipes`, lastModified: new Date(), changeFrequency: 'daily', priority: 0.9 },
    { url: `${BASE}/regions`, lastModified: new Date(), changeFrequency: 'weekly', priority: 0.8 },
    ...recipeUrls,
    ...locationUrls,
    ...pageUrls,
  ]
  } catch {
    return [
      { url: BASE, lastModified: new Date(), changeFrequency: 'weekly', priority: 1.0 },
      { url: `${BASE}/recipes`, lastModified: new Date(), changeFrequency: 'daily', priority: 0.9 },
      { url: `${BASE}/regions`, lastModified: new Date(), changeFrequency: 'weekly', priority: 0.8 },
    ]
  }
}
