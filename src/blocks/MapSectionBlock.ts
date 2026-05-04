import type { Block } from 'payload'
export const MapSectionBlock: Block = {
  slug: 'mapSection',
  labels: { singular: 'Χάρτης', plural: 'Χάρτες' },
  fields: [
    { name: 'titlePrefix', type: 'text', label: 'Τίτλος', defaultValue: 'Εξερευνησε' },
    { name: 'titleHighlight', type: 'text', label: 'Τίτλος (highlight)', defaultValue: 'Ανα Περιοχη' },
  ],
}
