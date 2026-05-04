import type { Block } from 'payload'
export const HeroBlock: Block = {
  slug: 'hero',
  labels: { singular: 'Hero', plural: 'Heroes' },
  fields: [
    { name: 'badge', type: 'text', label: 'Badge κειμένου (πάνω από τίτλο)', defaultValue: 'Αυθεντικες Ελληνικες Γευσεις' },
    { name: 'titlePrefix', type: 'text', label: 'Τίτλος (πριν highlight)', defaultValue: 'Η Ελλαδα' },
    { name: 'titleHighlight', type: 'text', label: 'Τίτλος (highlight πορτοκαλί)', defaultValue: 'στο Πιατο' },
    { name: 'titleSuffix', type: 'text', label: 'Τίτλος (μετά highlight)', defaultValue: 'σου.' },
    { name: 'subtitle', type: 'textarea', label: 'Υπότιτλος', defaultValue: 'Εξερεύνησε την γαστρονομική κληρονομιά της Ελλάδας' },
    { name: 'buttonText', type: 'text', label: 'Κείμενο κουμπιού', defaultValue: 'Ανακαλυψε Συνταγες' },
    { name: 'buttonLink', type: 'text', label: 'Link κουμπιού', defaultValue: '/recipes' },
    { name: 'backgroundImage', type: 'upload', relationTo: 'media', label: 'Εικόνα φόντου (αφήστε κενό για default)' },
  ],
}
