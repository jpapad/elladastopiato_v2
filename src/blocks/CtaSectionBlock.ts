import type { Block } from 'payload'
export const CtaSectionBlock: Block = {
  slug: 'ctaSection',
  labels: { singular: 'Call to Action', plural: 'Calls to Action' },
  fields: [
    { name: 'title', type: 'text', label: 'Τίτλος', required: true },
    { name: 'subtitle', type: 'text', label: 'Υπότιτλος' },
    { name: 'buttonText', type: 'text', label: 'Κείμενο κουμπιού', required: true },
    { name: 'buttonLink', type: 'text', label: 'Link', required: true },
  ],
}
