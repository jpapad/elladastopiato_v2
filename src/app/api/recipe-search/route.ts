import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { NextRequest, NextResponse } from 'next/server'

export async function GET(req: NextRequest) {
  try {
    const { searchParams } = new URL(req.url)
    const query = searchParams.get('q')

    if (!query || query.length < 2) {
      return NextResponse.json({ docs: [] })
    }

    const payload = await getPayload({ config: configPromise })

    const result = await payload.find({
      collection: 'recipes',
      where: { title: { like: query } },
      limit: 8,
      depth: 1,
    })

    return NextResponse.json(result)
  } catch (error) {
    console.error('Search API Error:', error)
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 })
  }
}
