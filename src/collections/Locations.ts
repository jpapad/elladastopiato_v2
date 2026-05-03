import { CollectionConfig } from 'payload'

const GR: Record<string, string> = {
  α:'a',β:'v',γ:'g',δ:'d',ε:'e',ζ:'z',η:'i',θ:'th',ι:'i',κ:'k',λ:'l',
  μ:'m',ν:'n',ξ:'x',ο:'o',π:'p',ρ:'r',σ:'s',ς:'s',τ:'t',υ:'y',φ:'f',
  χ:'ch',ψ:'ps',ω:'o',ά:'a',έ:'e',ή:'i',ί:'i',ό:'o',ύ:'y',ώ:'o',
  ϊ:'i',ϋ:'y',ΐ:'i',ΰ:'y',
}

function toSlug(str: string): string {
  return str
    .toLowerCase()
    .split('').map(c => GR[c] ?? c).join('')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
}

const Locations: CollectionConfig = {
  slug: 'locations',
  admin: {
    useAsTitle: 'name',
    defaultColumns: ['name', 'level', 'parent'],
    listSearchableFields: ['name'],
    group: 'Τοποθεσίες',
    description: 'Διαχείριση γεωγραφικών περιοχών (Περιφέρειες → Νομοί → Δήμοι)',
    preview: (doc) => `${process.env.NEXT_PUBLIC_SERVER_URL}/regions/${doc.slug || doc.id}`,
  },
  access: { read: () => true },
  hooks: {
    beforeChange: [
      ({ data, operation }) => {
        if (operation === 'create' && !data.slug && data.name) {
          data.slug = toSlug(data.name)
        }
        return data
      },
    ],
  },
  fields: [
    {
      name: 'name',
      type: 'text',
      required: true,
      label: 'Όνομα Τοποθεσίας',
      admin: { description: 'π.χ. Κρήτη, Χανιά, Σφακιά', placeholder: 'Εισάγετε όνομα...' },
    },
    {
      name: 'slug',
      type: 'text',
      unique: true,
      label: 'URL Slug',
      admin: {
        position: 'sidebar',
        description: 'Αυτόματα από το όνομα. π.χ. "kriti"',
      },
    },
    {
      name: 'level',
      type: 'select',
      required: true,
      label: 'Επίπεδο Ιεραρχίας',
      defaultValue: '1',
      options: [
        { label: '1 — Περιφέρεια  (π.χ. Κρήτη)', value: '1' },
        { label: '2 — Νομός       (π.χ. Χανιά)',  value: '2' },
        { label: '3 — Δήμος       (π.χ. Σφακιά)', value: '3' },
      ],
      admin: { description: 'Επιλέξτε σε ποιο επίπεδο ανήκει η τοποθεσία.' },
    },
    {
      name: 'parent',
      type: 'relationship',
      relationTo: 'locations',
      label: 'Ανήκει σε (Γονέας)',
      filterOptions: ({ data }) => {
        if (data?.level === '2') return { level: { equals: '1' } }
        if (data?.level === '3') return { level: { equals: '2' } }
        return false
      },
      admin: {
        condition: (data) => data?.level === '2' || data?.level === '3',
        description: 'Επιλέξτε την ανώτερη περιοχή στην οποία ανήκει.',
      },
    },
    {
      name: 'image',
      type: 'upload',
      relationTo: 'media',
      label: 'Φωτογραφία Περιοχής',
      admin: { description: 'Εικόνα που εμφανίζεται στις κάρτες εξερεύνησης.' },
    },
    {
      name: 'description',
      type: 'textarea',
      label: 'Σύντομη Περιγραφή',
      admin: { placeholder: 'Μερικές γραμμές για την περιοχή...' },
    },
    {
      name: 'geoJSON',
      type: 'json',
      label: 'GeoJSON (Χάρτης)',
      admin: { condition: () => false },
    },
  ],
}

export default Locations
