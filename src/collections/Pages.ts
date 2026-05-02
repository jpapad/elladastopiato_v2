import { CollectionConfig } from 'payload'

const Pages: CollectionConfig = {
  slug: 'pages',
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'slug', 'updatedAt'],
  },
  access: {
    read: () => true,
  },
  fields: [
    { name: 'title', type: 'text', required: true, label: 'Τίτλος Σελίδας' },
    { name: 'slug', type: 'text', required: true, unique: true, label: 'URL Slug' },
    {
      name: 'layout',
      label: 'Page Builder (Layout)',
      type: 'blocks',
      blocks: [
        {
          slug: 'hero',
          labels: { singular: 'Hero Section', plural: 'Hero Sections' },
          fields: [
            { name: 'title', type: 'text', required: true },
            { name: 'subtitle', type: 'text' },
            { name: 'backgroundImage', type: 'upload', relationTo: 'media' },
          ],
        },
        {
          slug: 'videoBlock',
          labels: { singular: 'Video Section', plural: 'Video Sections' },
          fields: [
            { name: 'title', type: 'text', label: 'Τίτλος Ενότητας' },
            { 
              name: 'videoType', 
              type: 'select', 
              options: [{label: 'YouTube URL', value: 'youtube'}, {label: 'Αρχείο Video', value: 'file'}],
              defaultValue: 'youtube'
            },
            { name: 'youtubeID', type: 'text', label: 'YouTube Video ID', admin: { condition: (_, data) => data.videoType === 'youtube' } },
            { name: 'videoFile', type: 'upload', relationTo: 'media', label: 'Αρχείο Video', admin: { condition: (_, data) => data.videoType === 'file' } },
          ]
        },
        {
          slug: 'mapBlock',
          labels: { singular: 'Χάρτης', plural: 'Χάρτες' },
          fields: [{ name: 'heading', type: 'text' }],
        },
        {
          slug: 'recipesBlock',
          labels: { singular: 'Λίστα Συνταγών', plural: 'Λίστες Συνταγών' },
          fields: [
            { name: 'title', type: 'text' },
            { name: 'limit', type: 'number', defaultValue: 3 },
          ],
        },
        {
          slug: 'storyBlock',
          labels: { singular: 'Story Section', plural: 'Story Sections' },
          fields: [
            { name: 'title', type: 'text', required: true },
            { name: 'content', type: 'textarea', required: true },
            { name: 'image', type: 'upload', relationTo: 'media', required: true },
            { name: 'imageSide', type: 'select', options: [{label: 'Αριστερά', value: 'left'}, {label: 'Δεξιά', value: 'right'}], defaultValue: 'right' }
          ]
        },
        {
          slug: 'featuresBlock',
          labels: { singular: 'Χαρακτηριστικά (Κουτάκια)', plural: 'Ενότητες Χαρακτηριστικών' },
          fields: [
            { name: 'tagline', type: 'text' },
            { name: 'title', type: 'text' },
            {
              name: 'features',
              type: 'array',
              fields: [
                { name: 'icon', type: 'select', options: [{label: 'Καρδιά', value: 'Heart'}, {label: 'Κόσμος', value: 'Globe'}, {label: 'Βραβείο', value: 'Award'}] },
                { name: 'featureTitle', type: 'text', required: true },
                { name: 'featureDescription', type: 'textarea', required: true },
              ]
            }
          ]
        },
        {
          slug: 'ctaBlock',
          labels: { singular: 'Call to Action', plural: 'CTA Sections' },
          fields: [
            { name: 'title', type: 'text', required: true },
            { name: 'buttonText', type: 'text', required: true },
            { name: 'link', type: 'text', required: true }
          ]
        },
        {
          slug: 'quoteBlock',
          labels: { singular: 'Απόφθεγμα', plural: 'Αποφθέγματα' },
          fields: [
            { name: 'quote', type: 'textarea', required: true },
            { name: 'author', type: 'text' }
          ]
        }
      ],
    },
  ],
}

export default Pages