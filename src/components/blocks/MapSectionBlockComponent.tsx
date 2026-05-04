import MapWrapper from '@/components/MapWrapper'

export function MapSectionBlockComponent({ block }: { block: any }) {
  return (
    <section className="py-20 px-6 max-w-7xl mx-auto">
      <div className="mb-12">
        <h2 className="text-5xl font-black uppercase italic tracking-tighter">
          {block.titlePrefix || 'Εξερευνησε'} <br />
          <span className="text-orange-500">{block.titleHighlight || 'Ανα Περιοχη'}</span>
        </h2>
      </div>
      <MapWrapper />
    </section>
  )
}
