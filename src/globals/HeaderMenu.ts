import { GlobalConfig } from 'payload'

const HeaderMenu: GlobalConfig = {
  slug: 'header-menu',
  label: 'Μενού Πλοήγησης',
  // ΠΡΟΣΘΗΚΗ ΑΥΤΟΥ:
  access: {
    read: () => true,
    update: () => true,
  },
  fields: [
    {
      name: 'navLinks',
      type: 'array',
      label: 'Σύνδεσμοι Μενού',
      fields: [
        { name: 'label', type: 'text', required: true },
        { name: 'link', type: 'text', required: true },
      ],
    },
  ],
}
export default HeaderMenu