import React from 'react'

export const RichText: React.FC<{ content: any }> = ({ content }) => {
  if (!content) return null

  // Απλοποιημένος parser για να ξεκινήσουμε
  return (
    <div className="prose prose-invert max-w-none prose-orange">
      {content.root?.children?.map((node: any, i: number) => {
        if (node.type === 'paragraph') {
          return <p key={i} className="mb-4 text-white/80">{node.children?.[0]?.text}</p>
        }
        if (node.type === 'list') {
          return (
            <ul key={i} className="list-disc pl-5 mb-4 text-white/70">
              {node.children?.map((li: any, j: number) => (
                <li key={j}>{li.children?.[0]?.text}</li>
              ))}
            </ul>
          )
        }
        return null
      })}
    </div>
  )
}