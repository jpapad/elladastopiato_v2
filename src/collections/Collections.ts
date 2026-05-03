import { CollectionConfig } from 'payload'
import { lexicalEditor } from '@payloadcms/richtext-lexical'

export const Collections: CollectionConfig = {
  slug: 'collections',
  labels: { singular: 'Συλλογή', plural: 'Συλλογές' },
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'theme', 'active', 'updatedAt'],
  },
  fields: [
    {
      name: 'title',
      type: 'text',
      label: 'Τίτλος',
      required: true,
    },
    {
      name: 'slug',
      type: 'text',
      label: 'Slug',
      required: true,
      unique: true,
      admin: { description: 'π.χ. pasxa, xristougenna, kalokairi' },
    },
    {
      name: 'theme',
      type: 'select',
      label: 'Θέμα',
      options: [
        { label: 'Άνοιξη', value: 'spring' },
        { label: 'Καλοκαίρι', value: 'summer' },
        { label: 'Φθινόπωρο', value: 'autumn' },
        { label: 'Χειμώνας', value: 'winter' },
        { label: 'Πάσχα', value: 'easter' },
        { label: 'Χριστούγεννα', value: 'christmas' },
        { label: 'Νηστίσιμα', value: 'fasting' },
        { label: 'Γενική', value: 'general' },
      ],
    },
    {
      name: 'emoji',
      type: 'text',
      label: 'Emoji',
      admin: { description: 'π.χ. 🌸 🌊 🎄' },
    },
    {
      name: 'description',
      type: 'richText',
      label: 'Περιγραφή',
      editor: lexicalEditor({}),
    },
    {
      name: 'image',
      type: 'upload',
      label: 'Εικόνα',
      relationTo: 'media',
    },
    {
      name: 'recipes',
      type: 'relationship',
      label: 'Συνταγές',
      relationTo: 'recipes',
      hasMany: true,
    },
    {
      name: 'active',
      type: 'checkbox',
      label: 'Ενεργή',
      defaultValue: true,
    },
  ],
}
