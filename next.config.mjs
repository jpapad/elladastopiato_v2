import { withPayload } from '@payloadcms/next/withPayload'

/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    // Επιτρέπουμε στη Next.js να κάνει optimize εικόνες από αυτά τα domains
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
      },
      {
        protocol: 'https',
        hostname: 'elladastopiato.gr',
      },
      {
        protocol: 'https',
        hostname: '*.public.blob.vercel-storage.com',
      },
    ],
    // Σύγχρονα formats για μέγιστη ταχύτητα και μικρότερο μέγεθος αρχείου
    formats: ['image/avif', 'image/webp'],
    // Στάνταρ μεγέθη για να σερβίρονται οι εικόνες σωστά σε κάθε συσκευή
    deviceSizes: [640, 750, 828, 1080, 1200, 1920, 2048],
  },
  
  webpack: (webpackConfig) => {
    webpackConfig.resolve.extensionAlias = {
      '.cjs': ['.cts', '.cjs'],
      '.js': ['.ts', '.tsx', '.js', '.jsx'],
      '.mjs': ['.mts', '.mjs'],
    }

    return webpackConfig
  },
}

export default withPayload(nextConfig, { devBundleServerPackages: false })