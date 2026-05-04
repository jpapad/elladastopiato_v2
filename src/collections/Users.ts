import type { CollectionConfig } from 'payload'

export const Users: CollectionConfig = {
  slug: 'users',
  admin: {
    useAsTitle: 'email',
    group: 'Χρήστες',
    description: 'Διαχείριση χρηστών της πλατφόρμας.',
    defaultColumns: ['email', 'name', 'role', 'createdAt'],
  },
  auth: true,
  access: {
    admin: ({ req: { user } }) => (user as any)?.role === 'admin',
    create: () => true,
    read: ({ req: { user } }) => {
      if ((user as any)?.role === 'admin') return true
      return { id: { equals: (user as any)?.id } }
    },
    update: ({ req: { user }, id }) =>
      (user as any)?.role === 'admin' || String((user as any)?.id) === String(id),
    delete: ({ req: { user } }) => (user as any)?.role === 'admin',
  },
  fields: [
    {
      name: 'name',
      type: 'text',
      label: 'Ονοματεπώνυμο',
    },
    {
      name: 'role',
      type: 'select',
      label: 'Ρόλος',
      defaultValue: 'user',
      required: true,
      options: [
        { label: '👑 Admin', value: 'admin' },
        { label: '👤 Χρήστης', value: 'user' },
      ],
      access: {
        update: ({ req: { user } }) => (user as any)?.role === 'admin',
      },
      admin: { position: 'sidebar' },
    },
    {
      name: 'avatar',
      type: 'upload',
      relationTo: 'media',
      label: 'Φωτογραφία Προφίλ',
    },
    {
      name: 'bio',
      type: 'textarea',
      label: 'Βιογραφικό',
      admin: { description: 'Λίγα λόγια για σένα.' },
    },
  ],
}
