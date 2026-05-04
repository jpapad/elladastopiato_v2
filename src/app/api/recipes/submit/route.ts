import { NextRequest, NextResponse } from 'next/server'
import { getPayload } from 'payload'
import configPromise from '@payload-config'

function toPlainRichText(text: string) {
  if (!text) return undefined
  return {
    root: {
      type: 'root',
      children: text.split('\n').filter(Boolean).map(line => ({
        type: 'paragraph',
        version: 1,
        children: [{ type: 'text', text: line, version: 1 }],
      })),
      direction: 'ltr',
      format: '',
      indent: 0,
      version: 1,
    },
  }
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json()
    const { title, location, recipeCategory, prepTime, cookTime, servings,
            description, ingredients, instructions, tips, userId } = body

    if (!title || !location || !recipeCategory || !ingredients || !instructions) {
      return NextResponse.json({ error: 'Συμπλήρωσε όλα τα υποχρεωτικά πεδία.' }, { status: 400 })
    }

    const payload = await getPayload({ config: configPromise })

    const recipe = await payload.create({
      collection: 'recipes',
      data: {
        title,
        location,
        recipeCategory,
        status: 'pending',
        submittedBy: userId || null,
        prepTime: prepTime ? Number(prepTime) : undefined,
        cookTime: cookTime ? Number(cookTime) : undefined,
        servings: servings ? Number(servings) : undefined,
        description: toPlainRichText(description),
        ingredients: toPlainRichText(ingredients),
        instructions: toPlainRichText(instructions),
        tips: tips ? toPlainRichText(tips) : undefined,
      } as any,
    })

    return NextResponse.json({ ok: true, id: recipe.id })
  } catch (err: any) {
    console.error('Recipe submit error:', err)
    return NextResponse.json({ error: err.message || 'Server error' }, { status: 500 })
  }
}
