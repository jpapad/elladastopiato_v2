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
 *   data-bg                  Χρώμα φόντου (hex ή var(--…))
 *   data-height-mobile       Ελάχιστο ύψος σε κινητά (px)
 *   data-radius              Στρογγυλάδα γωνιών (px)
 *   data-type-colors         JSON {"restaurant":"#e11d48",…}
 *   data-types               Τύποι σημείων που εμφανίζονται, με κόμμα (π.χ. "restaurant,market")
 *   data-legend="true"       Υπόμνημα/φίλτρο τύπων σημείων
 *   data-start-region        Περιφέρεια (name_greek) στην οποία ανοίγει ο χάρτης
 *   data-empty-areas         normal | dim | disabled
 *   data-texts               JSON με κείμενα (hintRegions, guideButton, pointsHeading, …)
 *
 * Global API (window.ElladaMap): mount(el, config?), unmount(el), mountAll(scope?)
 * Event: 'ellada-map:select' στο host element, με detail = { type, … }.
 */
import React from 'react'
import { createRoot, type Root } from 'react-dom/client'
import { GreeceMap, type MapSelectEvent } from '@/components/map'
import type { EmptyAreas, MapTexts } from '@/components/map/GreeceMap'
import type { LocalPointType } from '@/lib/wordpress/types'
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
  background?: string
  mobileMinHeight?: number
  radius?: number
  typeColors?: Partial<Record<LocalPointType, string>>
  pointTypes?: LocalPointType[]
  legend?: boolean
  startRegion?: string
  emptyAreas?: EmptyAreas
  texts?: MapTexts
}

const POINT_TYPES: LocalPointType[] = [
  'product',
  'producer',
  'restaurant',
  'market',
  'festival',
  'sight',
  'other',
]
/** Μόνο hex ή μεταβλητή CSS (π.χ. καθολικό χρώμα Elementor). */
const COLOR_RE = /^(#[0-9a-f]{3}([0-9a-f]{3})?|var\(--[\w-]+\))$/i
const isColor = (v: unknown): v is string => typeof v === 'string' && COLOR_RE.test(v)

function parseJson(value: string | undefined): Record<string, unknown> {
  if (!value) return {}
  try {
    const v = JSON.parse(value)
    return v && typeof v === 'object' && !Array.isArray(v) ? v : {}
  } catch {
    return {}
  }
}

const positive = (v: string | undefined) => {
  const n = Number(v)
  return Number.isFinite(n) && n > 0 ? n : undefined
}

export const version = '1.2.0'

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

  const typeColors: Partial<Record<LocalPointType, string>> = {}
  for (const [k, v] of Object.entries(parseJson(d.typeColors)))
    if (POINT_TYPES.includes(k as LocalPointType) && isColor(v)) typeColors[k as LocalPointType] = v

  const texts: MapTexts = {}
  for (const [k, v] of Object.entries(parseJson(d.texts)))
    if (typeof v === 'string' && v.trim()) (texts as Record<string, string>)[k] = v.trim()

  const pointTypes = (d.types ?? '')
    .split(',')
    .map((x) => x.trim())
    .filter((x): x is LocalPointType => POINT_TYPES.includes(x as LocalPointType))

  const emptyAreas = ['normal', 'dim', 'disabled'].includes(d.emptyAreas ?? '')
    ? (d.emptyAreas as EmptyAreas)
    : undefined

  const radius = Number(d.radius)
  return {
    apiRoot: d.api || undefined,
    geoBase: d.geo || undefined,
    settlements: d.settlements === 'true' || d.settlements === '1',
    title: d.title || undefined,
    minHeight: Number.isFinite(height) && height > 0 ? height : undefined,
    locationsEndpoint: d.locationsEndpoint || undefined,
    pointsEndpoint: d.pointsEndpoint || undefined,
    accent: d.accent || undefined,
    background: d.bg || undefined,
    mobileMinHeight: positive(d.heightMobile),
    radius: d.radius !== undefined && Number.isFinite(radius) && radius >= 0 ? radius : undefined,
    typeColors,
    pointTypes: pointTypes.length ? pointTypes : undefined,
    legend: d.legend === 'true' || d.legend === '1',
    startRegion: d.startRegion || undefined,
    emptyAreas,
    texts,
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

  // Μη έγκυρα χρώματα αγνοούνται και μένουν τα προεπιλεγμένα.
  if (isColor(config.accent)) el.style.setProperty('--ellada-accent', config.accent)
  else el.style.removeProperty('--ellada-accent')
  if (isColor(config.background)) el.style.setProperty('--ellada-bg', config.background)
  else el.style.removeProperty('--ellada-bg')

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
        mobileMinHeight={config.mobileMinHeight}
        radius={config.radius}
        typeColors={config.typeColors}
        pointTypes={config.pointTypes}
        legend={config.legend}
        startRegion={config.startRegion}
        emptyAreas={config.emptyAreas}
        texts={config.texts}
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
