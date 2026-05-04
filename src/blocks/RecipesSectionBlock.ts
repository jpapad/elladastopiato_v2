import type { Block } from 'payload'
export const RecipesSectionBlock: Block = {
  slug: 'recipesSection',
  labels: { singular: 'Ενότητα Συνταγών', plural: 'Ενότητες Συνταγών' },
  fields: [
    { name: 'titlePrefix', type: 'text', label: 'Τίτλος', defaultValue: 'Προσφατες' },
    { name: 'titleHighlight', type: 'text', label: 'Τίτλος (highlight)', defaultValue: 'Συνταγες' },
    { name: 'limit', type: 'number', label: 'Αριθμός συνταγών', defaultValue: 3, min: 1, max: 12 },
    { name: 'showViewAll', type: 'checkbox', label: 'Εμφάνιση "Δες Ολες"', defaultValue: true },
  ],
}
