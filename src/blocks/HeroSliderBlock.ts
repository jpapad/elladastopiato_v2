import type { Block } from 'payload'

export const HeroSliderBlock: Block = {
  slug: 'heroSlider',
  labels: { singular: 'Hero Slider', plural: 'Hero Sliders' },
  fields: [
    {
      name: 'slides',
      type: 'array',
      label: 'Slides',
      minRows: 1,
      labels: { singular: 'Slide', plural: 'Slides' },
      fields: [
        { name: 'badge', type: 'text', label: 'Badge (πάνω από τίτλο)' },
        { name: 'titlePrefix', type: 'text', label: 'Τίτλος (πριν το highlight)' },
        { name: 'titleHighlight', type: 'text', label: 'Τίτλος highlight (πορτοκαλί)' },
        { name: 'titleSuffix', type: 'text', label: 'Τίτλος (μετά το highlight)' },
        { name: 'subtitle', type: 'textarea', label: 'Υπότιτλος' },
        { name: 'buttonText', type: 'text', label: 'Κείμενο κουμπιού' },
        { name: 'buttonLink', type: 'text', label: 'Link κουμπιού', defaultValue: '/recipes' },
        {
          name: 'backgroundImage',
          type: 'upload',
          relationTo: 'media',
          label: 'Εικόνα φόντου',
          required: true,
        },
      ],
    },
    {
      name: 'autoplay',
      type: 'checkbox',
      label: 'Αυτόματη εναλλαγή slides',
      defaultValue: true,
    },
    {
      name: 'autoplayInterval',
      type: 'select',
      label: 'Διάρκεια κάθε slide',
      defaultValue: '5000',
      options: [
        { label: '3 δευτερόλεπτα', value: '3000' },
        { label: '5 δευτερόλεπτα', value: '5000' },
        { label: '7 δευτερόλεπτα', value: '7000' },
        { label: '10 δευτερόλεπτα', value: '10000' },
      ],
      admin: {
        condition: (data) => !!data?.autoplay,
        description: 'Πόσο να μένει κάθε slide ορατό.',
      },
    },
  ],
}
