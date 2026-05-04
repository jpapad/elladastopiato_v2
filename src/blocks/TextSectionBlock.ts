import type { Block } from 'payload'
export const TextSectionBlock: Block = {
  slug: 'textSection',
  labels: { singular: 'Ενότητα Κειμένου', plural: 'Ενότητες Κειμένου' },
  fields: [
    { name: 'titlePrefix', type: 'text', label: 'Τίτλος' },
    { name: 'titleHighlight', type: 'text', label: 'Τίτλος (highlight πορτοκαλί)' },
    { name: 'content', type: 'textarea', label: 'Κείμενο' },
  ],
}
