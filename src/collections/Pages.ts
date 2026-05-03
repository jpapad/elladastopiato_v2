import { CollectionConfig } from 'payload'

const Pages: CollectionConfig = {
  slug: 'pages',
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'slug', 'status', 'updatedAt'],
    group: 'Περιεχόμενο',
    description: 'Δημιουργία και διαχείριση σελίδων με τον page builder.',
    preview: (doc) => `${process.env.NEXT_PUBLIC_SERVER_URL}/${doc.slug}`,
  },
  access: {
    read: ({ req }) => {
      if (req.user) return true
      return { status: { equals: 'published' } }
    },
  },
  fields: [
    {
      name: 'status',
      type: 'select',
      required: true,
      defaultValue: 'published',
      label: 'Κατάσταση',
      options: [
        { label: '✅ Δημοσιευμένη', value: 'published' },
        { label: '📝 Προσχέδιο',    value: 'draft' },
      ],
      admin: {
        position: 'sidebar',
        description: 'Μόνο οι δημοσιευμένες σελίδες είναι ορατές στους επισκέπτες.',
      },
    },
    {
      name: 'title',
      type: 'text',
      required: true,
      label: 'Τίτλος Σελίδας',
    },
    {
      name: 'slug',
      type: 'text',
      required: true,
      unique: true,
      label: 'URL Slug',
      admin: {
        position: 'sidebar',
        description: 'π.χ. "about" → elladastopiato.gr/about',
      },
    },
    {
      name: 'seo',
      type: 'group',
      label: 'SEO',
      admin: {
        position: 'sidebar',
        description: 'Προαιρετικό. Αν αφεθεί κενό χρησιμοποιείται ο τίτλος.',
      },
      fields: [
        { name: 'metaTitle',       type: 'text',     label: 'Meta Title' },
        { name: 'metaDescription', type: 'textarea', label: 'Meta Description' },
        { name: 'ogImage',         type: 'upload', relationTo: 'media', label: 'OG Image' },
      ],
    },
    {
      name: 'layout',
      label: 'Page Builder',
      type: 'blocks',
      blocks: [
        {
          slug: 'hero',
          labels: { singular: 'Hero Section', plural: 'Hero Sections' },
          fields: [
            { name: 'title',           type: 'text',   required: true, label: 'Τίτλος' },
            { name: 'subtitle',        type: 'text',   label: 'Υπότιτλος' },
            { name: 'backgroundImage', type: 'upload', relationTo: 'media', label: 'Φωτογραφία Φόντου' },
            {
              name: 'cta',
              type: 'group',
              label: 'Κουμπί (προαιρετικό)',
              fields: [
                { name: 'label', type: 'text', label: 'Κείμενο' },
                { name: 'href',  type: 'text', label: 'Link' },
              ],
            },
          ],
        },
        {
          slug: 'richTextBlock',
          labels: { singular: 'Κείμενο', plural: 'Κείμενα' },
          fields: [
            { name: 'content', type: 'richText', required: true, label: 'Περιεχόμενο' },
          ],
        },
        {
          slug: 'recipesBlock',
          labels: { singular: 'Λίστα Συνταγών', plural: 'Λίστες Συνταγών' },
          fields: [
            { name: 'title', type: 'text', label: 'Τίτλος Ενότητας' },
            { name: 'limit', type: 'number', defaultValue: 3, label: 'Αριθμός Συνταγών' },
            {
              name: 'filterByCategory',
              type: 'select',
              label: 'Φιλτράρισμα ανά Κατηγορία (προαιρετικό)',
              options: [
                { label: 'Όλες', value: '' },
                { label: 'Ορεκτικά',     value: 'orektika' },
                { label: 'Κυρίως Πιάτα', value: 'kyrios' },
                { label: 'Θαλασσινά',    value: 'thalassina' },
                { label: 'Γλυκά',        value: 'glyka' },
                { label: 'Πίτες',        value: 'pites' },
                { label: 'Σαλάτες',      value: 'salates' },
              ],
            },
          ],
        },
        {
          slug: 'mapBlock',
          labels: { singular: 'Χάρτης Ελλάδας', plural: 'Χάρτες' },
          fields: [
            { name: 'heading', type: 'text', label: 'Τίτλος πάνω από τον χάρτη' },
          ],
        },
        {
          slug: 'storyBlock',
          labels: { singular: 'Story Section', plural: 'Story Sections' },
          fields: [
            { name: 'title',     type: 'text',     required: true, label: 'Τίτλος' },
            { name: 'content',   type: 'textarea', required: true, label: 'Κείμενο' },
            { name: 'image',     type: 'upload',   relationTo: 'media', required: true, label: 'Εικόνα' },
            {
              name: 'imageSide',
              type: 'select',
              label: 'Θέση Εικόνας',
              defaultValue: 'right',
              options: [{ label: 'Αριστερά', value: 'left' }, { label: 'Δεξιά', value: 'right' }],
            },
          ],
        },
        {
          slug: 'featuresBlock',
          labels: { singular: 'Features (Κουτάκια)', plural: 'Features' },
          fields: [
            { name: 'tagline', type: 'text', label: 'Tagline' },
            { name: 'title',   type: 'text', label: 'Τίτλος' },
            {
              name: 'features',
              type: 'array',
              label: 'Χαρακτηριστικά',
              fields: [
                {
                  name: 'icon',
                  type: 'select',
                  label: 'Εικονίδιο',
                  options: [
                    { label: '❤️ Καρδιά',  value: 'Heart' },
                    { label: '🌍 Κόσμος',  value: 'Globe' },
                    { label: '🏆 Βραβείο', value: 'Award' },
                    { label: '🍽️ Πιάτο',  value: 'UtensilsCrossed' },
                    { label: '📍 Pin',     value: 'MapPin' },
                    { label: '⚡ Zap',     value: 'Zap' },
                  ],
                },
                { name: 'featureTitle',       type: 'text',     required: true, label: 'Τίτλος' },
                { name: 'featureDescription', type: 'textarea', required: true, label: 'Περιγραφή' },
              ],
            },
          ],
        },
        {
          slug: 'ctaBlock',
          labels: { singular: 'Call to Action', plural: 'CTA Sections' },
          fields: [
            { name: 'title',      type: 'text', required: true, label: 'Τίτλος' },
            { name: 'buttonText', type: 'text', required: true, label: 'Κείμενο Κουμπιού' },
            { name: 'link',       type: 'text', required: true, label: 'Link' },
          ],
        },
        {
          slug: 'quoteBlock',
          labels: { singular: 'Απόφθεγμα', plural: 'Αποφθέγματα' },
          fields: [
            { name: 'quote',  type: 'textarea', required: true, label: 'Κείμενο' },
            { name: 'author', type: 'text',                     label: 'Συγγραφέας' },
          ],
        },
        {
          slug: 'videoBlock',
          labels: { singular: 'Video', plural: 'Videos' },
          fields: [
            { name: 'title', type: 'text', label: 'Τίτλος Ενότητας' },
            {
              name: 'videoType',
              type: 'select',
              label: 'Τύπος',
              defaultValue: 'youtube',
              options: [{ label: 'YouTube URL', value: 'youtube' }, { label: 'Αρχείο Video', value: 'file' }],
            },
            {
              name: 'youtubeID',
              type: 'text',
              label: 'YouTube Video ID',
              admin: { condition: (_, d) => d?.videoType === 'youtube', description: 'Το ID μετά το ?v= στο URL' },
            },
            {
              name: 'videoFile',
              type: 'upload',
              relationTo: 'media',
              label: 'Αρχείο Video',
              admin: { condition: (_, d) => d?.videoType === 'file' },
            },
          ],
        },
        {
          slug: 'newsletterBlock',
          labels: { singular: 'Newsletter Signup', plural: 'Newsletter Signups' },
          fields: [
            { name: 'title',       type: 'text', label: 'Τίτλος',       defaultValue: 'Εγγραφή στο Newsletter' },
            { name: 'description', type: 'text', label: 'Περιγραφή',    defaultValue: 'Λάβε νέες συνταγές κάθε εβδομάδα.' },
            { name: 'buttonText',  type: 'text', label: 'Κείμενο Κουμπιού', defaultValue: 'Εγγραφή' },
          ],
        },
      ],
    },
  ],
}

export default Pages
