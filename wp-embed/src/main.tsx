/// <reference types="vite/client" />
/**
 * Αυτόνομο bundle του χάρτη για WordPress.
 *
 * Βρίσκει κάθε <div data-ellada-map …> στη σελίδα (π.χ. από το shortcode [ellada_map])
 * και αποδίδει τον χάρτη μέσα σε Shadow DOM, ώστε τα CSS του theme / Elementor να μην
 * επηρεάζουν τον χάρτη και το Tailwind του χάρτη να μην επηρεάζει το site.
 *
 * data-* attributes:
 *   data-api                 Ρίζα REST API (rest_url()), π.χ. https://elladastopiato.gr/wp-json/
 *   data-geo                 URL φακέλου με regions.json / prefectures.json / municipalities.json
 *   data-settlements="true"  Εμφάνιση οικισμών σε επίπεδο δήμου (φορτώνει settlements.json)
 *   data-title               Τίτλος (default «Εξερεύνηση»)
 *   data-height              Ελάχιστο ύψος σε px (default 600)
 *   data-locations-endpoint  REST base τοποθεσιών (default locations)
 *   data-points-endpoint     REST base τοπικών σημείων (default local-points)
 *   data-accent              Χρώμα τονισμού: hex (#f97316) ή var(--…) (default πορτοκαλί)
 *
 * Global API (window.ElladaMap): mount(el, config?), unmount(el), mountAll(scope?)
 * Event: 'ellada-map:select' στο host element, με detail = { type, … }.
 */
import React from 'react'
import { createRoot, type Root } from 'react-dom/client'
import { GreeceMap, type MapSelectEvent } from '@/components/map'
import css from './styles.css?inline'

export type EmbedConfig = {
  apiRoot?: string
  geoBase?: string
  settlements?: boolean
  title?: string
  minHeight?: number
  locationsEndpoint?: string
  pointsEndpoint?: string
  accent?: string
}

export const version = '1.1.0'

const SELECTOR = '[data-ellada-map]'
const mounted = new Map<HTMLElement, Root>()

/* ── styles ── */

let sharedSheet: CSSStyleSheet | null = null

function applyStyles(shadow: ShadowRoot) {
  const supportsAdopted =
    'adoptedStyleSheets' in Document.prototype && 'replaceSync' in CSSStyleSheet.prototype
  if (supportsAdopted) {
    if (!sharedSheet) {
      sharedSheet = new CSSStyleSheet()
      sharedSheet.replaceSync(css)
    }
    shadow.adoptedStyleSheets = [sharedSheet]
  } else {
    const style = document.createElement('style')
    style.textContent = css
    shadow.appendChild(style)
  }
}

/**
 * Το Tailwind v4 δηλώνει μεταβλητές με @property, οι οποίοι αγνοούνται μέσα σε Shadow DOM
 * (χωρίς αυτούς χάνονται π.χ. borders/shadows). Τους καταχωρούμε μία φορά στο document·
 * επηρεάζουν μόνο τις μεταβλητές --tw-*, όχι το υπόλοιπο site.
 */
function registerPropertyRules() {
  if (document.getElementById('ellada-map-properties')) return
  const rules = css.match(/@property\s+--[\w-]+\s*\{[^}]*\}/g)
  if (!rules?.length) return
  const style = document.createElement('style')
  style.id = 'ellada-map-properties'
  style.textContent = rules.join('\n')
  document.head.appendChild(style)
}

/* ── config ── */

function readConfig(el: HTMLElement): EmbedConfig {
  const d = el.dataset
  const height = Number(d.height)
  return {
    apiRoot: d.api || undefined,
    geoBase: d.geo || undefined,
    settlements: d.settlements === 'true' || d.settlements === '1',
    title: d.title || undefined,
    minHeight: Number.isFinite(height) && height > 0 ? height : undefined,
    locationsEndpoint: d.locationsEndpoint || undefined,
    pointsEndpoint: d.pointsEndpoint || undefined,
    accent: d.accent || undefined,
  }
}

function defaultGeoBase(): string {
  // Ο φάκελος geo/ βρίσκεται δίπλα στο js/ του plugin: …/assets/js/ellada-map.js → …/assets/geo/
  const script = document.currentScript as HTMLScriptElement | null
  const src =
    script?.src || document.querySelector<HTMLScriptElement>('script[src*="ellada-map"]')?.src
  return src ? new URL('../geo/', src).toString() : '/geo/'
}
const fallbackGeoBase = defaultGeoBase() // πρέπει να υπολογιστεί όσο τρέχει το script

/* ── mount / unmount ── */

export function mount(el: HTMLElement, overrides: EmbedConfig = {}) {
  if (mounted.has(el)) return
  const config = { ...readConfig(el), ...overrides }
  const geoBase = (config.geoBase || fallbackGeoBase).replace(/\/?$/, '/')

  // Μόνο hex ή μεταβλητή CSS (π.χ. καθολικό χρώμα Elementor)· αλλιώς μένει το προεπιλεγμένο.
  if (config.accent && /^(#[0-9a-f]{3}([0-9a-f]{3})?|var\(--[\w-]+\))$/i.test(config.accent))
    el.style.setProperty('--ellada-accent', config.accent)
  else el.style.removeProperty('--ellada-accent')

  const shadow = el.shadowRoot ?? el.attachShadow({ mode: 'open' })
  shadow.replaceChildren()
  applyStyles(shadow)
  registerPropertyRules()

  const container = document.createElement('div')
  shadow.appendChild(container)

  const onSelect = (detail: MapSelectEvent) =>
    el.dispatchEvent(
      new CustomEvent('ellada-map:select', { detail, bubbles: true, composed: true }),
    )

  const root = createRoot(container)
  root.render(
    <React.StrictMode>
      <GreeceMap
        apiRoot={config.apiRoot}
        endpoints={{ locations: config.locationsEndpoint, localPoints: config.pointsEndpoint }}
        geoUrls={{
          regions: `${geoBase}regions.json`,
          prefectures: `${geoBase}prefectures.json`,
          municipalities: `${geoBase}municipalities.json`,
          settlements: config.settlements ? `${geoBase}settlements.json` : undefined,
        }}
        title={config.title}
        minHeight={config.minHeight}
        onSelect={onSelect}
      />
    </React.StrictMode>,
  )
  mounted.set(el, root)
  el.setAttribute('data-ellada-map-mounted', '')
}

export function unmount(el: HTMLElement) {
  const root = mounted.get(el)
  if (!root) return
  root.unmount()
  mounted.delete(el)
  el.removeAttribute('data-ellada-map-mounted')
}

export function mountAll(scope: ParentNode = document) {
  scope
    .querySelectorAll<HTMLElement>(`${SELECTOR}:not([data-ellada-map-mounted])`)
    .forEach((el) => mount(el))
}

/* ── auto-init ── */

if (typeof window !== 'undefined' && !(window as { __elladaMapInit?: boolean }).__elladaMapInit) {
  ;(window as { __elladaMapInit?: boolean }).__elladaMapInit = true

  if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', () => mountAll())
  else mountAll()

  // Δυναμικό περιεχόμενο (Elementor editor, AJAX, popups): mount σε νέα, unmount σε όσα αφαιρέθηκαν.
  new MutationObserver((records) => {
    let added = false
    for (const r of records) if (r.addedNodes.length) added = true
    if (added) mountAll()
    for (const el of [...mounted.keys()]) if (!el.isConnected) unmount(el)
  }).observe(document.documentElement, { childList: true, subtree: true })
}
