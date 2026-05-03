import { NextRequest, NextResponse } from 'next/server'
import { getPayload } from 'payload'
import configPromise from '@payload-config'

export async function POST(req: NextRequest) {
  try {
    const { email, name } = await req.json()

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      return NextResponse.json({ error: 'Μη έγκυρο email.' }, { status: 400 })
    }

    const payload = await getPayload({ config: configPromise })

    const existing = await payload.find({
      collection: 'subscribers',
      where: { email: { equals: email.toLowerCase() } },
      limit: 1,
    })

    if (existing.docs.length > 0) {
      return NextResponse.json({ message: 'already_subscribed' }, { status: 200 })
    }

    await payload.create({
      collection: 'subscribers',
      data: { email: email.toLowerCase(), name: name || undefined, source: 'website', active: true },
    })

    return NextResponse.json({ message: 'subscribed' }, { status: 201 })
  } catch {
    return NextResponse.json({ error: 'Σφάλμα server.' }, { status: 500 })
  }
}
