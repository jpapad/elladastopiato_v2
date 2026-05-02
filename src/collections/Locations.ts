import { CollectionConfig } from 'payload'

const Locations: CollectionConfig = {
  slug: 'locations',
  admin: {
    useAsTitle: 'name',
    defaultColumns: ['name', 'level', 'parent'],
  },
  access: {
    read: () => true,
  },
  fields: [
    {
      name: 'name',
      type: 'text',
      required: true,
      label: 'Όνομα Περιοχής',
    },
    {
      name: 'image', // <--- Το νέο πεδίο για τις cinematic κάρτες
      type: 'upload',
      relationTo: 'media',
      required: false,
      label: 'Φωτογραφία Περιοχής',
      admin: {
        description: 'Η εικόνα που θα εμφανίζεται στην εξερεύνηση περιοχών.',
      }
    },
    {
      name: 'level',
      type: 'select',
      required: true,
      label: 'Επίπεδο',
      options: [
        { label: 'Γεωγραφικό Διαμέρισμα (π.χ. Κρήτη)', value: '1' },
        { label: 'Νομός (π.χ. Χανιά)', value: '2' },
        { label: 'Πόλη / Χωριό (π.χ. Σφακιά)', value: '3' },
      ],
    },
    {
      name: 'parent',
      type: 'relationship',
      relationTo: 'locations',
      label: 'Ανήκει σε (Γονέας)',
      admin: {
        condition: (data) => data.level === '2' || data.level === '3',
      },
    },
    {
      name: 'geoJSON',
      type: 'json',
      label: 'GeoJSON Δεδομένα (Για τον χάρτη)',
      admin: {
        description: 'Εδώ θα μπαίνουν οι συντεταγμένες για να "ζωγραφίζεται" η περιοχή στον χάρτη.',
      }
    },
  ],
}

export default Locations