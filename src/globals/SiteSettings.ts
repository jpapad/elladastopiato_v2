import { GlobalConfig } from 'payload'

const SiteSettings: GlobalConfig = {
  slug: 'site-settings',
  label: 'Ρυθμίσεις Ιστοσελίδας',
  admin: {
    group: 'Ρυθμίσεις',
    description: 'Γενικές ρυθμίσεις της ιστοσελίδας — τίτλος, περιγραφή, social media, SEO.',
  },
  access: { read: () => true },
  fields: [
    {
      type: 'tabs',
      tabs: [
        {
          label: 'Γενικά',
          fields: [
            {
              name: 'siteName',
              type: 'text',
              label: 'Όνομα Ιστοσελίδας',
              defaultValue: 'Ελλάδα στο Πιάτο',
            },
            {
              name: 'tagline',
              type: 'text',
              label: 'Tagline',
              defaultValue: 'Αυθεντικές Ελληνικές Γεύσεις',
            },
            {
              name: 'description',
              type: 'textarea',
              label: 'Περιγραφή Ιστοσελίδας',
              admin: { description: 'Χρησιμοποιείται ως default meta description.' },
            },
            {
              name: 'logo',
              type: 'upload',
              relationTo: 'media',
              label: 'Λογότυπο',
            },
            {
              name: 'favicon',
              type: 'upload',
              relationTo: 'media',
              label: 'Favicon',
            },
          ],
        },
        {
          label: 'SEO & Social',
          fields: [
            {
              name: 'defaultOgImage',
              type: 'upload',
              relationTo: 'media',
              label: 'Default OG Image (Social Share)',
              admin: {
                description: 'Εμφανίζεται όταν μοιράζεστε τη σελίδα στα social media (1200×630px).',
              },
            },
            {
              name: 'googleVerification',
              type: 'text',
              label: 'Google Search Console Verification',
              admin: { description: 'Το content του meta tag verification.' },
            },
          ],
        },
        {
          label: 'Social Media',
          fields: [
            { name: 'instagram', type: 'text', label: 'Instagram URL' },
            { name: 'facebook',  type: 'text', label: 'Facebook URL' },
            { name: 'youtube',   type: 'text', label: 'YouTube URL' },
            { name: 'tiktok',    type: 'text', label: 'TikTok URL' },
          ],
        },
        {
          label: 'Footer',
          fields: [
            {
              name: 'footerTagline',
              type: 'text',
              label: 'Footer Tagline',
              defaultValue: 'Η γαστρονομική ψυχή της Ελλάδας',
            },
            {
              name: 'footerCopyright',
              type: 'text',
              label: 'Copyright Text',
              defaultValue: '© 2025 Ελλάδα στο Πιάτο. Όλα τα δικαιώματα διατηρούνται.',
            },
            {
              name: 'contactEmail',
              type: 'email',
              label: 'Email Επικοινωνίας',
            },
          ],
        },
      ],
    },
  ],
}

export default SiteSettings
