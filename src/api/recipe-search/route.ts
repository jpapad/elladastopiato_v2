import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { NextRequest, NextResponse } from 'next/server'

export async function GET(req: NextRequest) {
  try {
    // 1. Παίρνουμε το ερώτημα (q) από το URL (π.χ. /api/recipe-search?q=μουσακας)
    const { searchParams } = new URL(req.url)
    const query = searchParams.get('q')

    // Αν δεν υπάρχει κείμενο αναζήτησης, επιστρέφουμε άδεια λίστα
    if (!query || query.length < 2) {
      return NextResponse.json({ docs: [] })
    }

    const payload = await getPayload({ config: configPromise })

    // 2. Εκτελούμε την αναζήτηση στο Payload
    const result = await payload.find({
      collection: 'recipes',
      where: {
        // Ψάχνουμε αν το query περιέχεται στον τίτλο (case-insensitive από το Payload)
        title: {
          like: query, 
        },
      },
      limit: 8, // Επιστρέφουμε έως 8 αποτελέσματα για ταχύτητα
      depth: 1, // Φέρνουμε και τα δεδομένα του Location (π.χ. όνομα περιοχής)
    })

    return NextResponse.json(result)
  } catch (error) {
    console.error("Search API Error:", error)
    return NextResponse.json({ error: "Internal Server Error" }, { status: 500 })
  }
}