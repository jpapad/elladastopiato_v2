/**
 * Vite config για το αυτόνομο bundle του χάρτη (WordPress).
 *
 *   npm run wp-map:dev     → dev server με ψεύτικο WordPress REST API (http://localhost:5178)
 *   npm run wp-map:build   → wp-embed/wordpress-plugin/ellada-map/assets/js/ellada-map.js + zip
 */
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig, type Plugin } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/postcss'

const here = path.dirname(fileURLToPath(import.meta.url))
const repoRoot = path.resolve(here, '..')
const pluginDir = path.join(here, 'wordpress-plugin', 'ellada-map')

/**
 * Μετατρέπει rem → px. Τα rem υπολογίζονται από το font-size του <html> του WordPress theme
 * (κάποια themes βάζουν 62.5%) — με px ο χάρτης έχει ίδιο μέγεθος σε κάθε theme.
 */
const remToPx = {
  postcssPlugin: 'ellada-rem-to-px',
  Declaration(decl: { value: string }) {
    if (decl.value.includes('rem')) {
      decl.value = decl.value.replace(
        /(-?\d*\.?\d+)rem\b/g,
        (_, n: string) => `${+(parseFloat(n) * 16).toFixed(3)}px`,
      )
    }
  },
}

/** Ψεύτικο WordPress REST API για ανάπτυξη χωρίς πραγματικό site (wp-embed/dev/mock-wp/*.json). */
function mockWordPress(): Plugin {
  const GEO: Record<string, string> = {
    '/geo/regions.json': 'greece-regions.geojson',
    '/geo/prefectures.json': 'greece-prefectures.geojson',
    '/geo/municipalities.json': 'greece-municipalities.geojson',
    '/geo/settlements.json': 'greece-settlements.json',
  }
  const MAX_PER_PAGE = 3 // μικρό, ώστε να δοκιμάζεται και η σελιδοποίηση (X-WP-TotalPages)

  return {
    name: 'ellada-mock-wordpress',
    apply: 'serve',
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        const url = new URL(req.url || '/', 'http://localhost')

        if (GEO[url.pathname]) {
          res.setHeader('Content-Type', 'application/json')
          fs.createReadStream(path.join(repoRoot, 'public', GEO[url.pathname])).pipe(res)
          return
        }

        const match = url.pathname.match(/^\/wp-json\/wp\/v2\/(locations|local-points)\/?$/)
        if (!match) return next()

        const items = JSON.parse(
          fs.readFileSync(path.join(here, 'dev', 'mock-wp', `${match[1]}.json`), 'utf8'),
        )
        const perPage = Math.min(Number(url.searchParams.get('per_page') || 10), MAX_PER_PAGE)
        const page = Number(url.searchParams.get('page') || 1)
        const totalPages = Math.max(1, Math.ceil(items.length / perPage))
        res.setHeader('Content-Type', 'application/json; charset=UTF-8')
        if (page > totalPages) {
          res.statusCode = 400
          res.end(
            JSON.stringify({ code: 'rest_post_invalid_page_number', message: 'Invalid page' }),
          )
          return
        }
        res.setHeader('X-WP-Total', String(items.length))
        res.setHeader('X-WP-TotalPages', String(totalPages))
        res.end(JSON.stringify(items.slice((page - 1) * perPage, page * perPage)))
      })
    },
  }
}

export default defineConfig(({ command }) => ({
  root: here,
  publicDir: false,
  plugins: [react(), mockWordPress()],
  resolve: {
    alias: { '@': path.join(repoRoot, 'src') },
  },
  css: {
    postcss: { plugins: [tailwindcss(), remToPx] },
  },
  // Σε library mode το Vite δεν αντικαθιστά το process.env.NODE_ENV — το χρειάζεται το React.
  define: command === 'build' ? { 'process.env.NODE_ENV': JSON.stringify('production') } : {},
  build: {
    outDir: path.join(pluginDir, 'assets', 'js'),
    emptyOutDir: true,
    target: 'es2020',
    sourcemap: false,
    reportCompressedSize: true,
    lib: {
      entry: path.join(here, 'src', 'main.tsx'),
      name: 'ElladaMap',
      formats: ['iife'],
      fileName: () => 'ellada-map.js',
    },
  },
  server: {
    port: 5178,
    fs: { allow: [repoRoot] },
  },
}))
