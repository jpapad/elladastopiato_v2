import { NextRequest, NextResponse } from 'next/server'
import { getPayload } from 'payload'
import configPromise from '@payload-config'

export async function GET(req: NextRequest) {
  const q = req.nextUrl.searchParams.get('q')?.trim()
  if (!q || q.length < 2) return NextResponse.json({ docs: [] })

  const payload = await getPayload({ config: configPromise })

  const { docs } = await payload.find({
    collection: 'recipes',
    where: {
      and: [
        { status: { equals: 'published' } },
        { title: { like: q } },
      ],
    },
    limit: 8,
    depth: 1,
    select: {
      title: true,
      slug: true,
      image: true,
      recipeCategory: true,
      prepTime: true,
      cookTime: true,
      location: true,
    },
  })

  return NextResponse.json({ docs })
}
