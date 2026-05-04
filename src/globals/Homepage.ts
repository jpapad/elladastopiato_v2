import type { GlobalConfig } from 'payload'
import { HeroBlock } from '../blocks/HeroBlock'
import { TextSectionBlock } from '../blocks/TextSectionBlock'
import { RecipesSectionBlock } from '../blocks/RecipesSectionBlock'
import { MapSectionBlock } from '../blocks/MapSectionBlock'
import { CtaSectionBlock } from '../blocks/CtaSectionBlock'

const Homepage: GlobalConfig = {
  slug: 'homepage',
  label: 'Αρχική Σελίδα',
  access: { read: () => true },
  fields: [
    {
      name: 'blocks',
      type: 'blocks',
      label: 'Ενότητες',
      blocks: [HeroBlock, TextSectionBlock, RecipesSectionBlock, MapSectionBlock, CtaSectionBlock],
    },
  ],
}

export default Homepage
