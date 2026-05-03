'use client'
import React, { useState, useMemo } from 'react'
import { RichText } from '@payloadcms/richtext-lexical/react'
import ServingsAdjuster from './ServingsAdjuster'

// Walk Lexical JSON and scale all numbers in text nodes
function scaleNode(node: any, multiplier: number): any {
  if (node.type === 'text' && typeof node.text === 'string') {
    const scaled = node.text.replace(/(\d+(?:[.,]\d+)?)/g, (match: string) => {
      const num = parseFloat(match.replace(',', '.'))
      const result = num * multiplier
      if (Number.isInteger(result)) return String(result)
      const fixed = result.toFixed(1)
      return fixed.endsWith('.0') ? fixed.slice(0, -2) : fixed
    })
    return { ...node, text: scaled }
  }
  if (Array.isArray(node.children)) {
    return { ...node, children: node.children.map((c: any) => scaleNode(c, multiplier)) }
  }
  return node
}

interface Props {
  data: any
  defaultServings: number
}

export default function IngredientsWithScaling({ data, defaultServings }: Props) {
  const [multiplier, setMultiplier] = useState(1)

  const scaledData = useMemo(() => {
    if (multiplier === 1) return data
    return scaleNode(data, multiplier)
  }, [data, multiplier])

  return (
    <div>
      {defaultServings > 0 && (
        <div className="mb-6">
          <ServingsAdjuster defaultServings={defaultServings} onChange={setMultiplier} />
        </div>
      )}
      <div className="prose dark:prose-invert max-w-none leading-relaxed">
        <RichText data={scaledData} />
      </div>
    </div>
  )
}
