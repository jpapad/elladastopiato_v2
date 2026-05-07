import { NextRequest, NextResponse } from 'next/server'
import Anthropic from '@anthropic-ai/sdk'

const client = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY })

const SYSTEM_PROMPT = `You are a recipe extraction assistant. Extract structured recipe data from the provided PDF document.
Return ONLY a valid JSON object with these exact fields (no markdown, no explanation):
{
  "title": "string — recipe title in the original language",
  "description": "string — brief description or history of the dish (1-3 sentences)",
  "ingredients": ["string", ...] — each ingredient as a separate string with quantity,
  "instructions": ["string", ...] — each step as a separate string,
  "prepTime": number — preparation time in minutes (0 if unknown),
  "cookTime": number — cooking time in minutes (0 if unknown),
  "servings": number | null — number of servings (null if unknown),
  "tips": "string — any tips or notes about the recipe (empty string if none)"
}
If the PDF contains multiple recipes, extract only the first/most prominent one.
Keep the original language (Greek text should remain in Greek).`

export async function POST(req: NextRequest) {
  try {
    const formData = await req.formData()
    const file = formData.get('pdf') as File | null

    if (!file) {
      return NextResponse.json({ error: 'Δεν στάλθηκε αρχείο PDF.' }, { status: 400 })
    }
    if (file.type !== 'application/pdf' && !file.name.endsWith('.pdf')) {
      return NextResponse.json({ error: 'Το αρχείο πρέπει να είναι PDF.' }, { status: 400 })
    }
    if (file.size > 10 * 1024 * 1024) {
      return NextResponse.json({ error: 'Το PDF δεν μπορεί να υπερβαίνει τα 10MB.' }, { status: 400 })
    }

    const arrayBuffer = await file.arrayBuffer()
    const base64Data = Buffer.from(arrayBuffer).toString('base64')

    const message = await client.messages.create({
      model: 'claude-opus-4-7',
      max_tokens: 4096,
      system: SYSTEM_PROMPT,
      messages: [
        {
          role: 'user',
          content: [
            {
              type: 'document',
              source: {
                type: 'base64',
                media_type: 'application/pdf',
                data: base64Data,
              },
            },
            {
              type: 'text',
              text: 'Extract the recipe from this PDF and return the JSON object.',
            },
          ],
        },
      ],
    })

    const rawText = message.content[0].type === 'text' ? message.content[0].text : ''
    const jsonMatch = rawText.match(/\{[\s\S]*\}/)
    if (!jsonMatch) {
      return NextResponse.json({ error: 'Δεν βρέθηκε συνταγή στο PDF.' }, { status: 422 })
    }

    const recipe = JSON.parse(jsonMatch[0])
    return NextResponse.json(recipe)
  } catch (err: any) {
    console.error('[import-recipe-pdf]', err)
    if (err?.status === 401) {
      return NextResponse.json({ error: 'Λάθος API key για Claude.' }, { status: 500 })
    }
    return NextResponse.json({ error: err?.message || 'Σφάλμα εξαγωγής συνταγής.' }, { status: 500 })
  }
}
