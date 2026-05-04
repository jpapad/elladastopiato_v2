import { postgresAdapter } from '@payloadcms/db-postgres'
import { lexicalEditor } from '@payloadcms/richtext-lexical'
import path from 'path'
import { buildConfig } from 'payload'
import { fileURLToPath } from 'url'
import sharp from 'sharp'

// Collections & Globals
import { Users } from './collections/Users'
import { Media } from './collections/Media'
import Locations from './collections/Locations'
import Recipes from './collections/Recipes'
import Pages from './collections/Pages'
import Subscribers from './collections/Subscribers'
import { Collections } from './collections/Collections'
import HeaderMenu from './globals/HeaderMenu'
import SiteSettings from './globals/SiteSettings'

const filename = fileURLToPath(import.meta.url)
const dirname = path.dirname(filename)

export default buildConfig({
  admin: {
    user: Users.slug,
    importMap: {
      baseDir: path.resolve(dirname),
    },
    components: {
      afterDashboard: ['@/components/admin/DashboardStats'],
      views: {
        importRecipe: {
          Component: '@/components/admin/ImportRecipePage',
          path: '/import-recipe',
        },
      },
    },
  },
  collections: [Users, Media, Locations, Recipes, Pages, Subscribers, Collections],
  globals: [HeaderMenu, SiteSettings],
  editor: lexicalEditor(),
  secret: process.env.PAYLOAD_SECRET || '',
  typescript: {
    outputFile: path.resolve(dirname, 'payload-types.ts'),
  },
  db: postgresAdapter({
    pool: {
      connectionString: process.env.DATABASE_URI || process.env.DATABASE_URL || '',
      max: 1,
    },
  }),
  sharp,
  plugins: [], // Αφαιρέθηκε το SEO plugin που προκαλούσε το σφάλμα
})