import Image from 'next/image'
import Link from 'next/link'
import { MapPin } from 'lucide-react'

export function RecipesSectionBlockComponent({ block, recipes }: { block: any; recipes: any[] }) {
  return (
    <section className="py-32 px-6 max-w-7xl mx-auto">
      <div className="flex justify-between items-end mb-16">
        <h2 className="text-5xl font-black uppercase italic tracking-tighter">
          {block.titlePrefix || 'Προσφατες'} <br />
          <span className="text-orange-500">{block.titleHighlight || 'Συνταγες'}</span>
        </h2>
        {block.showViewAll && (
          <Link href="/recipes" className="text-orange-500 font-black uppercase text-[10px] tracking-widest border-b border-orange-500/30 pb-2 hover:border-orange-500 transition-all">
            Δες Ολες τις συνταγες
          </Link>
        )}
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {recipes.map((recipe: any) => (
          <Link key={recipe.id} href={`/recipes/${recipe.id}`} className="group">
            <div className="relative h-[500px] rounded-[3rem] overflow-hidden mb-6 border border-black/10 dark:border-white/10">
              {recipe.image?.url && (
                <Image
                  src={recipe.image.url}
                  alt={recipe.title}
                  fill
                  sizes="(max-width: 768px) 100vw, 33vw"
                  className="object-cover transition-transform duration-700 group-hover:scale-110"
                />
              )}
              <div className="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent opacity-80" />
              <div className="absolute bottom-8 left-8 right-8">
                <div className="flex items-center gap-2 text-orange-500 mb-2 font-black uppercase tracking-widest text-[10px]">
                  <MapPin size={12} /> {(recipe.location as any)?.name}
                </div>
                <h3 className="text-3xl font-black uppercase italic tracking-tighter leading-none text-white">{recipe.title}</h3>
              </div>
            </div>
          </Link>
        ))}
      </div>
    </section>
  )
}
