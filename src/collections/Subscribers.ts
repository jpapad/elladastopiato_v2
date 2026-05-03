import { CollectionConfig } from 'payload'

const Subscribers: CollectionConfig = {
  slug: 'subscribers',
  admin: {
    useAsTitle: 'email',
    defaultColumns: ['email', 'createdAt', 'active'],
    group: 'Marketing',
    description: 'Εγγεγραμμένοι χρήστες για το newsletter.',
  },
  access: {
    read: ({ req }) => !!req.user,
    create: () => true, // public signup
    update: ({ req }) => !!req.user,
    delete: ({ req }) => !!req.user,
  },
  fields: [
    {
      name: 'email',
      type: 'email',
      required: true,
      unique: true,
      label: 'Email',
    },
    {
      name: 'name',
      type: 'text',
      label: 'Όνομα',
      admin: { placeholder: 'Προαιρετικό' },
    },
    {
      name: 'active',
      type: 'checkbox',
      defaultValue: true,
      label: 'Ενεργός',
      admin: {
        position: 'sidebar',
        description: 'Αποεπιλέξτε για διαγραφή από λίστα χωρίς να σβήσετε την εγγραφή.',
      },
    },
    {
      name: 'source',
      type: 'select',
      label: 'Πηγή Εγγραφής',
      defaultValue: 'website',
      admin: { position: 'sidebar' },
      options: [
        { label: 'Website', value: 'website' },
        { label: 'Admin', value: 'admin' },
      ],
    },
  ],
  timestamps: true,
}

export default Subscribers
