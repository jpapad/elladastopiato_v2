import { CollectionConfig } from 'payload'

const Recipes: CollectionConfig = {
  slug: 'recipes',
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'recipeCategory', 'location'],
  },
  access: {
    read: () => true,
  },
  fields: [
    {
      name: 'title',
      type: 'text',
      required: true,
      label: 'Τίτλος Συνταγής',
    },
    {
      name: 'recipeCategory',
      type: 'select',
      required: true,
      label: 'Κατηγορία',
      options: [
        { label: 'Ορεκτικά', value: 'orektika' },
        { label: 'Κυρίως Πιάτα', value: 'kyrios' },
        { label: 'Θαλασσινά', value: 'thalassina' },
        { label: 'Γλυκά', value: 'glyka' },
        { label: 'Πίτες', value: 'pites' },
        { label: 'Σαλάτες', value: 'salates' },
      ],
    },
    {
      type: 'row',
      fields: [
        { name: 'prepTime', type: 'number', label: 'Προετοιμασία (λεπτά)', admin: { width: '33%' } },
        { name: 'cookTime', type: 'number', label: 'Μαγείρεμα (λεπτά)', admin: { width: '33%' } },
        { name: 'servings', type: 'number', label: 'Μερίδες', admin: { width: '33%' } },
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
            { label: 'Vegan', value: 'vegan' },
            { label: 'Vegetarian', value: 'vegetarian' },
            { label: 'Gluten-Free', value: 'gluten-free' },
            { label: 'Keto', value: 'keto' },
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
            { label: 'Γλουτένη', value: 'gluten' },
            { label: 'Λακτόζη', value: 'lactose' },
            { label: 'Αυγά', value: 'eggs' },
            { label: 'Ψάρια', value: 'fish' },
          ],
          admin: { width: '50%' },
        },
      ],
    },
    {
      name: 'location',
      type: 'relationship',
      relationTo: 'locations',
      required: true,
      label: 'Περιοχή',
    },
    {
      name: 'image',
      type: 'upload',
      relationTo: 'media',
      required: true,
      label: 'Κεντρική Φωτογραφία',
    },
    { 
      name: 'description', 
      type: 'richText', 
      label: 'Περιγραφή / Ιστορία Συνταγής' 
    },
    { name: 'ingredients', type: 'richText', label: 'Υλικά' },
    { name: 'instructions', type: 'richText', label: 'Εκτέλεση' },
    { name: 'tips', type: 'richText', label: 'Μυστικά Επιτυχίας' },
    {
      name: 'seo',
      type: 'group',
      label: 'Ρυθμίσεις SEO',
      fields: [
        { name: 'metaTitle', type: 'text', label: 'Meta Title' },
        { name: 'metaDesc', type: 'textarea', label: 'Meta Description' },
      ],
    },
  ],
}

export default Recipes