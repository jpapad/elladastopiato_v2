import { CollectionConfig } from 'payload'
import { CATEGORY_OPTIONS } from '../lib/categories'

const GR: Record<string, string> = {
  α:'a',β:'v',γ:'g',δ:'d',ε:'e',ζ:'z',η:'i',θ:'th',ι:'i',κ:'k',λ:'l',
  μ:'m',ν:'n',ξ:'x',ο:'o',π:'p',ρ:'r',σ:'s',ς:'s',τ:'t',υ:'y',φ:'f',
  χ:'ch',ψ:'ps',ω:'o',ά:'a',έ:'e',ή:'i',ί:'i',ό:'o',ύ:'y',ώ:'o',
  ϊ:'i',ϋ:'y',ΐ:'i',ΰ:'y',
}

function toSlug(str: string): string {
  return str
    .toLowerCase()
    .split('').map(c => GR[c] ?? c).join('')  // transliterate Greek
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')           // strip remaining diacritics
    .replace(/[^a-z0-9\s-]/g, '')             // strip non-ASCII
    .trim()
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
}

const Recipes: CollectionConfig = {
  slug: 'recipes',
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'status', 'recipeCategory', 'location', 'updatedAt'],
    group: 'Περιεχόμενο',
    description: 'Διαχείριση συνταγών. Μόνο οι "Δημοσιευμένες" εμφανίζονται στην ιστοσελίδα.',
    listSearchableFields: ['title'],
    preview: (doc) => `${process.env.NEXT_PUBLIC_SERVER_URL}/recipes/${doc.slug}`,
    components: {
      beforeListTable: ['@/components/admin/ImportButton'],
    },
  },
  access: {
    read: ({ req }) => {
      if (req.user) return true
      return { status: { equals: 'published' } }
    },
  },
  hooks: {
    beforeChange: [
      ({ data, operation }) => {
        if (operation === 'create' && !data.slug && data.title) {
          const generated = toSlug(data.title)
          // Only set slug if non-empty (Greek-only titles produce empty strings)
          if (generated) data.slug = generated
        }
        return data
      },
    ],
  },
  fields: [
    // ── Status bar at top ──
    {
      name: 'status',
      type: 'select',
      required: true,
      defaultValue: 'published',
      label: 'Κατάσταση',
      options: [
        { label: '✅ Δημοσιευμένη', value: 'published' },
        { label: '📝 Προσχέδιο',    value: 'draft' },
        { label: '⏳ Αναμονή Έγκρισης', value: 'pending' },
        { label: '❌ Απορρίφθηκε',  value: 'rejected' },
      ],
      admin: {
        position: 'sidebar',
        description: 'Μόνο οι δημοσιευμένες εμφανίζονται στην ιστοσελίδα.',
      },
    },
    {
      name: 'title',
      type: 'text',
      required: true,
      label: 'Τίτλος Συνταγής',
    },
    {
      name: 'slug',
      type: 'text',
      unique: true,
      label: 'URL Slug',
      admin: {
        position: 'sidebar',
        description: 'π.χ. "ntakos-kritis" → /recipes/ntakos-kritis. Αυτόματα από τον τίτλο.',
      },
    },
    {
      name: 'recipeCategory',
      type: 'select',
      required: true,
      label: 'Κατηγορία',
      options: CATEGORY_OPTIONS,
      admin: { position: 'sidebar' },
    },
    {
      name: 'location',
      type: 'relationship',
      relationTo: 'locations',
      required: true,
      label: 'Περιοχή',
      admin: { position: 'sidebar' },
    },
    {
      name: 'image',
      type: 'upload',
      relationTo: 'media',
      label: 'Κεντρική Φωτογραφία',
    },
    {
      type: 'row',
      fields: [
        { name: 'prepTime', type: 'number', label: 'Προετοιμασία (λεπτά)', admin: { width: '33%' } },
        { name: 'cookTime', type: 'number', label: 'Μαγείρεμα (λεπτά)',    admin: { width: '33%' } },
        { name: 'servings', type: 'number', label: 'Μερίδες',              admin: { width: '33%' } },
      ],
    },
    {
      type: 'row',
      fields: [
        {
          name: 'tags',
          type: 'select',
          hasMany: true,
          label: 'Διατροφικές Ετικέτες',
          options: [
            { label: 'Vegan',       value: 'vegan' },
            { label: 'Vegetarian',  value: 'vegetarian' },
            { label: 'Gluten-Free', value: 'gluten-free' },
            { label: 'Keto',        value: 'keto' },
          ],
          admin: { width: '50%' },
        },
        {
          name: 'allergens',
          type: 'select',
          hasMany: true,
          label: 'Αλλεργιογόνα',
          options: [
            { label: 'Ξηροί Καρποί', value: 'nuts' },
            { label: 'Γλουτένη',     value: 'gluten' },
            { label: 'Λακτόζη',      value: 'lactose' },
            { label: 'Αυγά',         value: 'eggs' },
            { label: 'Ψάρια',        value: 'fish' },
          ],
          admin: { width: '50%' },
        },
      ],
    },
    { name: 'description',  type: 'richText', label: 'Περιγραφή / Ιστορία Συνταγής' },
    { name: 'ingredients',  type: 'richText', label: 'Υλικά' },
    { name: 'instructions', type: 'richText', label: 'Εκτέλεση' },
    { name: 'tips',         type: 'richText', label: 'Μυστικά Επιτυχίας' },
    {
      name: 'submittedBy',
      type: 'relationship',
      relationTo: 'users',
      label: 'Υποβλήθηκε από',
      admin: {
        position: 'sidebar',
        readOnly: true,
        description: 'Συμπληρώνεται αυτόματα όταν υποβάλλεται από χρήστη.',
      },
    },
    {
      name: 'seo',
      type: 'group',
      label: 'SEO',
      admin: { description: 'Προαιρετικά. Αν αφεθούν κενά χρησιμοποιείται ο τίτλος και η φωτογραφία.' },
      fields: [
        { name: 'metaTitle', type: 'text',     label: 'Meta Title' },
        { name: 'metaDesc',  type: 'textarea', label: 'Meta Description' },
        { name: 'ogImage',   type: 'upload', relationTo: 'media', label: 'OG Image' },
      ],
    },
  ],
}

export default Recipes
