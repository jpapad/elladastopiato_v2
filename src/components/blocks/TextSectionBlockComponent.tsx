export function TextSectionBlockComponent({ block }: { block: any }) {
  return (
    <section className="py-20 px-6 max-w-7xl mx-auto">
      {(block.titlePrefix || block.titleHighlight) && (
        <h2 className="text-5xl font-black uppercase italic tracking-tighter mb-8">
          {block.titlePrefix}
          {block.titleHighlight && <> <br /><span className="text-orange-500">{block.titleHighlight}</span></>}
        </h2>
      )}
      {block.content && (
        <p className="text-lg text-gray-600 dark:text-white/60 max-w-2xl leading-relaxed">{block.content}</p>
      )}
    </section>
  )
}
