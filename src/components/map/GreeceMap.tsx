'use client'
/**
 * Διαδραστικός χάρτης «Ελλάδα στο Πιάτο».
 *
 * Επίπεδα: Περιφέρειες → Νομοί → Δήμοι (+ Τοπικά Σημεία από το WordPress).
 * - Γεωγραφία: στατικά GeoJSON (geoUrls).
 * - Περιεχόμενο: WordPress REST API + ACF Pro (CPT `location` και `local_point`).
 *
 * Το ίδιο component χρησιμοποιείται από το Next.js app (MapWrapper) και από
 * το αυτόνομο bundle για WordPress (wp-embed/).
 */
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { ComposableMap, Geographies, Geography, Marker } from 'react-simple-maps'
import { AnimatePresence, motion } from 'framer-motion'
import { ArrowLeft, ChevronRight } from 'lucide-react'
import type { LocalPointType, MapLocation } from '@/lib/wordpress/types'
import {
  GREECE_PROJ,
  MAP_H,
  MAP_W,
  calcProjection,
  featureName,
  filterGeo,
  municipalityKey,
  prettifyName,
  type GeoFeature,
  type PlacedPoint,
  type ProjConfig,
} from './geo'
import InfoCard, { type InfoCardData } from './InfoCard'
import { useMapData, type MapDataConfig } from './useMapData'

export type MapSelectEvent =
  | { type: 'region' | 'prefecture' | 'municipality'; name: string; location: MapLocation | null }
  | { type: 'point'; point: PlacedPoint }

/** Κείμενα του χάρτη που μπορούν να αλλάξουν (π.χ. από τις ρυθμίσεις του WordPress). */
export type MapTexts = Partial<{
  hintRegions: string
  hintPrefectures: string
  hintMunicipalities: string
  hintPoints: string
  guideButton: string
  pointsHeading: string
}>

/** Πώς φαίνονται οι περιοχές χωρίς περιεχόμενο: κανονικά, αχνές ή αχνές και μη κλικαριστές. */
export type EmptyAreas = 'normal' | 'dim' | 'disabled'

export type GreeceMapProps = MapDataConfig & {
  /** Καλείται σε κάθε επιλογή περιοχής ή τοπικού σημείου. */
  onSelect?: (event: MapSelectEvent) => void
  /** Τίτλος πάνω αριστερά (default «Εξερεύνηση»). */
  title?: string
  /** Ελάχιστο ύψος του χάρτη σε px (default 600). */
  minHeight?: number
  /** Ελάχιστο ύψος σε κινητά (<640px). Default = minHeight. */
  mobileMinHeight?: number
  /** Στρογγυλάδα γωνιών σε px (default 40). */
  radius?: number
  /** Χρώμα πινέζας ανά τύπο σημείου· όσοι λείπουν παίρνουν το χρώμα τονισμού. */
  typeColors?: Partial<Record<LocalPointType, string>>
  /** Ποιοι τύποι σημείων εμφανίζονται (default όλοι). */
  pointTypes?: LocalPointType[]
  /** Υπόμνημα/φίλτρο τύπων σημείων για τον επισκέπτη (default false). */
  legend?: boolean
  /** Περιφέρεια (name_greek) στην οποία ανοίγει ο χάρτης αντί για όλη την Ελλάδα. */
  startRegion?: string
  /** Εμφάνιση περιοχών χωρίς περιεχόμενο (default 'normal'). */
  emptyAreas?: EmptyAreas
  /** Αλλαγές στα κείμενα του χάρτη. */
  texts?: MapTexts
}

/* ── constants ── */
const EASE: [number, number, number, number] = [0.22, 1, 0.36, 1]

const variants = {
  enter: (d: number) => ({ scale: d > 0 ? 0.55 : 1.5, opacity: 0 }),
  center: { scale: 1, opacity: 1, transition: { duration: 0.5, ease: EASE } },
  exit: (d: number) => ({
    scale: d > 0 ? 1.5 : 0.55,
    opacity: 0,
    transition: { duration: 0.32, ease: EASE },
  }),
}

/**
 * Χρώμα τονισμού. Ρυθμίζεται με τη μεταβλητή CSS --ellada-accent (π.χ. από το plugin του
 * WordPress)· αν δεν οριστεί, μένει το πορτοκαλί (#f97316) του αρχικού σχεδίου.
 */
const ACCENT = 'var(--ellada-accent, #f97316)'
const accent = (alpha: number) =>
  `color-mix(in srgb, ${ACCENT} ${Math.round(alpha * 100)}%, transparent)`
/** Φόντο χάρτη: --ellada-bg ή το αρχικό σχεδόν μαύρο. */
const BG = 'var(--ellada-bg, #0a0a0a)'

const LEVEL_STYLE = [
  { fill: 'rgba(255,255,255,0.04)', stroke: 'rgba(255,255,255,0.18)', strokeWidth: 0.5 },
  { fill: accent(0.06), stroke: accent(0.35), strokeWidth: 0.8 },
  { fill: accent(0.08), stroke: accent(0.45), strokeWidth: 1 },
  { fill: accent(0.05), stroke: accent(0.25), strokeWidth: 1.2 },
]
/** Περιοχές με περιεχόμενο στο WordPress (σελίδα ή τοπικά σημεία) φαίνονται λίγο πιο έντονα. */
const CONTENT_FILL = accent(0.16)

const DEFAULT_TEXTS: Required<MapTexts> = {
  hintRegions: 'Κάντε κλικ σε περιφέρεια για εξερεύνηση',
  hintPrefectures: 'Κάντε κλικ σε νομό για εξερεύνηση',
  hintMunicipalities: 'Κάντε κλικ σε δήμο ή σε τοπικό σημείο',
  hintPoints: 'Κάντε κλικ σε τοπικό σημείο για λεπτομέρειες',
  guideButton: 'Ο γαστρονομικός οδηγός',
  pointsHeading: 'Τοπικά σημεία',
}
/** Αχνό γέμισμα για περιοχές χωρίς περιεχόμενο (emptyAreas = 'dim' | 'disabled'). */
const EMPTY_STYLE = { fill: 'rgba(255,255,255,0.012)', stroke: 'rgba(255,255,255,0.07)' }
const DEFAULT_RADIUS = 40 // 2.5rem
const LEVEL_LABEL = ['Περιφέρειες', 'Νομοί', 'Δήμοι', 'Τοπικά σημεία']
const KIND_LABEL = ['Περιφέρεια', 'Νομός', 'Δήμος', 'Τοπικό σημείο']

const countLabel = (n: number) => (n === 1 ? '1 τοπικό σημείο' : `${n} τοπικά σημεία`)

const isExternalLink = (href: string) => {
  if (typeof window === 'undefined') return false
  try {
    return new URL(href, window.location.href).origin !== window.location.origin
  } catch {
    return false
  }
}

const clamp = (v: number, min: number, max: number) => Math.max(min, Math.min(max, v))

const countBy = (points: PlacedPoint[], key: (p: PlacedPoint) => string | null) => {
  const m = new Map<string, number>()
  for (const p of points) {
    const k = key(p)
    if (k) m.set(k, (m.get(k) ?? 0) + 1)
  }
  return m
}

/* ══════════════════════════════════════════════ */
export default function GreeceMap({
  apiRoot,
  endpoints,
  geoUrls,
  onSelect,
  title = 'Εξερεύνηση',
  minHeight = 600,
  mobileMinHeight,
  radius = DEFAULT_RADIUS,
  typeColors,
  pointTypes,
  legend = false,
  startRegion,
  emptyAreas = 'normal',
  texts,
}: GreeceMapProps) {
  const t = {
    ...DEFAULT_TEXTS,
    ...Object.fromEntries(Object.entries(texts ?? {}).filter(([, v]) => v)),
  }
  const levelHint = [t.hintRegions, t.hintPrefectures, t.hintMunicipalities, t.hintPoints]
  const typeColor = (type: LocalPointType) => typeColors?.[type] || ACCENT
  const mobileH = mobileMinHeight && mobileMinHeight > 0 ? mobileMinHeight : minHeight
  const sizeVars = {
    '--ellada-h': `${minHeight}px`,
    '--ellada-h-m': `${mobileH}px`,
    borderRadius: radius,
  } as React.CSSProperties

  const {
    regions,
    prefectures,
    municipalities,
    settlements,
    geoError,
    locationIndex,
    points: allPoints,
    wpStatus,
  } = useMapData({ apiRoot, endpoints, geoUrls })

  /** Μόνο οι τύποι σημείων που επιτρέπουν οι ρυθμίσεις. */
  const points = useMemo(
    () => (pointTypes?.length ? allPoints.filter((p) => pointTypes.includes(p.type)) : allPoints),
    [allPoints, pointTypes],
  )
  /** Τύποι που έκρυψε ο επισκέπτης από το υπόμνημα. */
  const [hiddenTypes, setHiddenTypes] = useState<Set<LocalPointType>>(() => new Set())
  const toggleType = (type: LocalPointType) =>
    setHiddenTypes((prev) => {
      const next = new Set(prev)
      if (next.has(type)) next.delete(type)
      else next.add(type)
      return next
    })

  const [level, setLevel] = useState(0)
  const [projConfig, setProjConfig] = useState<ProjConfig>(GREECE_PROJ)
  const [selectedRegion, setSelectedRegion] = useState<string | null>(null)
  const [selectedPref, setSelectedPref] = useState<string | null>(null)
  const [selectedMuni, setSelectedMuni] = useState<GeoFeature | null>(null)
  const [selectedPointId, setSelectedPointId] = useState<number | null>(null)
  const [cardDismissed, setCardDismissed] = useState(false)

  const [hovered, setHovered] = useState<{ kind: string; name: string; meta?: string } | null>(null)
  const [direction, setDirection] = useState(1)
  const [animKey, setAnimKey] = useState(0)

  /* ── παράγωγα δεδομένα ── */
  const countByRegion = useMemo(() => countBy(points, (p) => p.region), [points])
  const countByPref = useMemo(() => countBy(points, (p) => p.prefecture), [points])
  const countByMuni = useMemo(() => countBy(points, (p) => p.municipality), [points])

  const regionLoc = selectedRegion ? (locationIndex.region(selectedRegion) ?? null) : null
  const prefLoc = selectedPref ? (locationIndex.prefecture(selectedPref) ?? null) : null
  const muniLoc = selectedMuni
    ? (locationIndex.municipality(
        selectedMuni.properties.prefecture_greek,
        featureName(selectedMuni),
      ) ?? null)
    : null
  const muniKeyStr = selectedMuni
    ? municipalityKey(selectedMuni.properties.prefecture_greek, featureName(selectedMuni))
    : null

  /** Όνομα προς εμφάνιση: ο τίτλος του WordPress αν υπάρχει, αλλιώς το όνομα του GeoJSON. */
  const displayName = useCallback(
    (lvl: number, f: GeoFeature) => {
      const name = featureName(f)
      if (lvl === 0) return locationIndex.region(name)?.title || name
      if (lvl === 1) return locationIndex.prefecture(name)?.title || name
      return (
        locationIndex.municipality(f.properties.prefecture_greek, name)?.title || prettifyName(name)
      )
    },
    [locationIndex],
  )

  const featureCount = useCallback(
    (lvl: number, f: GeoFeature) => {
      const name = featureName(f)
      if (lvl === 0) return countByRegion.get(name) ?? 0
      if (lvl === 1) return countByPref.get(name) ?? 0
      return countByMuni.get(municipalityKey(f.properties.prefecture_greek, name)) ?? 0
    },
    [countByRegion, countByPref, countByMuni],
  )

  const featureLocation = useCallback(
    (lvl: number, f: GeoFeature) => {
      const name = featureName(f)
      if (lvl === 0) return locationIndex.region(name)
      if (lvl === 1) return locationIndex.prefecture(name)
      return locationIndex.municipality(f.properties.prefecture_greek, name)
    },
    [locationIndex],
  )

  /* ── navigation ── */
  const transition = (d: number) => {
    setDirection(d)
    setAnimKey((k) => k + 1)
    setSelectedPointId(null)
    setCardDismissed(false)
    setHovered(null)
  }

  const handleRegionClick = useCallback(
    (feat: GeoFeature) => {
      const name = featureName(feat)
      setProjConfig(calcProjection([feat]))
      setSelectedRegion(name)
      setLevel(1)
      transition(1)
      onSelect?.({ type: 'region', name, location: locationIndex.region(name) ?? null })
    },
    [locationIndex, onSelect],
  )

  /** Αρχική προβολή: άνοιγμα κατευθείαν σε μια περιφέρεια (μία φορά, όταν φορτώσουν τα όρια). */
  const startApplied = useRef(false)
  useEffect(() => {
    if (startApplied.current || !startRegion || !regions) return
    startApplied.current = true
    const feat = regions.features.find((f) => featureName(f) === startRegion)
    if (!feat) return
    setProjConfig(calcProjection([feat]))
    setSelectedRegion(startRegion)
    setLevel(1)
  }, [startRegion, regions])

  const handlePrefectureClick = useCallback(
    (feat: GeoFeature) => {
      const name = featureName(feat)
      const muniFeats =
        municipalities?.features.filter((f) => f.properties.prefecture_greek === name) ?? []
      setProjConfig(calcProjection(muniFeats.length ? muniFeats : [feat]))
      setSelectedPref(name)
      setLevel(2)
      transition(1)
      onSelect?.({ type: 'prefecture', name, location: locationIndex.prefecture(name) ?? null })
    },
    [municipalities, locationIndex, onSelect],
  )

  const handleMunicipalityClick = useCallback(
    (feat: GeoFeature) => {
      const name = featureName(feat)
      setProjConfig(calcProjection([feat], 1.3))
      setSelectedMuni(feat)
      setLevel(3)
      transition(1)
      onSelect?.({
        type: 'municipality',
        name,
        location: locationIndex.municipality(feat.properties.prefecture_greek, name) ?? null,
      })
    },
    [locationIndex, onSelect],
  )

  const handlePointClick = useCallback(
    (point: PlacedPoint) => {
      setSelectedPointId(point.id)
      setCardDismissed(false)
      onSelect?.({ type: 'point', point })
    },
    [onSelect],
  )

  const goTo = useCallback(
    (index: number) => {
      if (index >= level) return
      transition(-1)
      if (index === 0) {
        setProjConfig(GREECE_PROJ)
        setSelectedRegion(null)
        setSelectedPref(null)
        setSelectedMuni(null)
        setLevel(0)
      } else if (index === 1 && selectedRegion) {
        const feat = regions?.features.find((f) => featureName(f) === selectedRegion)
        setProjConfig(feat ? calcProjection([feat]) : GREECE_PROJ)
        setSelectedPref(null)
        setSelectedMuni(null)
        setLevel(1)
      } else if (index === 2 && selectedPref) {
        const muniFeats =
          municipalities?.features.filter((f) => f.properties.prefecture_greek === selectedPref) ??
          []
        setProjConfig(muniFeats.length ? calcProjection(muniFeats) : GREECE_PROJ)
        setSelectedMuni(null)
        setLevel(2)
      }
    },
    [level, selectedRegion, selectedPref, regions, municipalities],
  )

  /* ── τι σχεδιάζουμε σε κάθε επίπεδο ── */
  const currentGeo = (() => {
    if (level === 0) return regions
    if (level === 1 && prefectures && selectedRegion)
      return filterGeo(prefectures, 'periphery_greek', selectedRegion)
    if (level === 2 && municipalities && selectedPref)
      return filterGeo(municipalities, 'prefecture_greek', selectedPref)
    if (level === 3 && selectedMuni)
      return { type: 'FeatureCollection' as const, features: [selectedMuni] }
    return null
  })()

  /** Σημεία της τρέχουσας περιοχής (πριν το φίλτρο του υπομνήματος). */
  const areaPoints: PlacedPoint[] = useMemo(() => {
    if (level === 2) return points.filter((p) => p.prefecture === selectedPref)
    if (level === 3) return points.filter((p) => p.municipality === muniKeyStr)
    return []
  }, [level, points, selectedPref, muniKeyStr])

  const visiblePoints = useMemo(
    () => areaPoints.filter((p) => !hiddenTypes.has(p.type)),
    [areaPoints, hiddenTypes],
  )

  /** Τύποι που υπάρχουν στην περιοχή, για το υπόμνημα. */
  const legendTypes = useMemo(() => {
    const m = new Map<LocalPointType, { label: string; count: number }>()
    for (const p of areaPoints) {
      const e = m.get(p.type)
      if (e) e.count++
      else m.set(p.type, { label: p.typeLabel, count: 1 })
    }
    return [...m.entries()]
  }, [areaPoints])

  const visibleSettlements =
    level === 3 && selectedMuni ? (settlements[featureName(selectedMuni)] ?? []) : []

  const selectedPoint = visiblePoints.find((p) => p.id === selectedPointId) ?? null

  /* ── breadcrumbs ── */
  const breadcrumbs = [
    'Ελλάδα',
    ...(level >= 1 && selectedRegion ? [regionLoc?.title || selectedRegion] : []),
    ...(level >= 2 && selectedPref ? [prefLoc?.title || selectedPref] : []),
    ...(level >= 3 && selectedMuni
      ? [muniLoc?.title || prettifyName(featureName(selectedMuni))]
      : []),
  ]

  /* ── info card ── */
  const card: InfoCardData | null = (() => {
    if (cardDismissed) return null
    if (selectedPoint) {
      const muniName = selectedMuni ? breadcrumbs[3] : null
      return {
        key: `point-${selectedPoint.id}`,
        eyebrow: selectedPoint.typeLabel,
        title: selectedPoint.title,
        description: selectedPoint.description,
        meta: [muniName, breadcrumbs[2]].filter(Boolean).join(' · ') || undefined,
        image: selectedPoint.image,
        link: selectedPoint.link,
      }
    }
    const lvlLoc = level === 3 ? muniLoc : level === 2 ? prefLoc : level === 1 ? regionLoc : null
    if (!lvlLoc) return null
    const count =
      level === 1
        ? (countByRegion.get(selectedRegion!) ?? 0)
        : level === 2
          ? (countByPref.get(selectedPref!) ?? 0)
          : (countByMuni.get(muniKeyStr!) ?? 0)
    return {
      key: `loc-${lvlLoc.id}`,
      eyebrow: KIND_LABEL[level - 1],
      title: lvlLoc.title,
      description: lvlLoc.description,
      meta: count ? countLabel(count) : undefined,
      image: lvlLoc.image,
      link: lvlLoc.link,
      linkLabel: t.guideButton,
    }
  })()

  const style = LEVEL_STYLE[Math.min(level, 3)]
  const pointR = clamp(projConfig.scale / 3500, 3, 7)
  const settlementR = clamp(projConfig.scale / 6000, 1.2, 3)
  const labelSize = clamp(projConfig.scale / 5000, 5, 8)

  /* ── loading / error ── */
  if (geoError && !regions) {
    return (
      <div
        className="bg-[var(--ellada-bg,#0a0a0a)] border border-white/5 p-8 flex items-center justify-center min-h-[var(--ellada-h-m)] sm:min-h-[var(--ellada-h)]"
        style={sizeVars}
      >
        <p className="text-white/40 text-xs uppercase tracking-widest text-center">
          Δεν φορτώθηκαν τα γεωγραφικά δεδομένα
          <br />
          <span className="text-white/20 normal-case tracking-normal">{geoError}</span>
        </p>
      </div>
    )
  }

  if (!regions) {
    return (
      <div
        className="bg-[var(--ellada-bg,#0a0a0a)] border border-white/5 p-8 flex items-center justify-center min-h-[var(--ellada-h-m)] sm:min-h-[var(--ellada-h)]"
        style={sizeVars}
      >
        <div className="text-orange-500 animate-pulse font-bold tracking-widest uppercase text-xs">
          Φορτωση Γεωγραφικων Δεδομενων...
        </div>
      </div>
    )
  }

  return (
    <div
      className="bg-[var(--ellada-bg,#0a0a0a)] text-white border border-white/5 shadow-2xl relative flex flex-col overflow-hidden min-h-[var(--ellada-h-m)] sm:min-h-[var(--ellada-h)]"
      style={sizeVars}
    >
      {/* ── top bar ── */}
      <div className="flex items-center justify-between gap-4 px-8 pt-8 pb-3 z-10">
        <div>
          <h2 className="text-3xl font-black tracking-tighter uppercase italic">{title}</h2>
          <p className="text-orange-500 text-[10px] font-bold tracking-[0.2em] flex items-center gap-2 uppercase mt-1">
            <span className="w-2 h-2 rounded-full bg-orange-500 animate-pulse" />
            {LEVEL_LABEL[level]}
            {breadcrumbs.length > 1 && ` — ${breadcrumbs.slice(1).join(' › ')}`}
          </p>
        </div>

        <AnimatePresence>
          {hovered && (
            <motion.div
              key={hovered.kind + hovered.name}
              initial={{ opacity: 0, y: -6 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -6 }}
              transition={{ duration: 0.15 }}
              className="text-right max-w-[220px]"
            >
              <p className="text-white/30 text-[9px] uppercase tracking-widest">{hovered.kind}</p>
              <p className="text-white font-bold text-sm leading-tight">{hovered.name}</p>
              {hovered.meta && (
                <p className="text-orange-500/80 text-[10px] mt-0.5">{hovered.meta}</p>
              )}
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {/* ── breadcrumbs ── */}
      <nav className="flex items-center gap-1 px-8 pb-3 z-10 flex-wrap" aria-label="Διαδρομή χάρτη">
        {level > 0 && (
          <button
            type="button"
            onClick={() => goTo(level - 1)}
            aria-label="Πίσω"
            className="flex items-center gap-1 text-white/40 hover:text-orange-400 transition-colors mr-2"
          >
            <ArrowLeft className="w-3 h-3" />
          </button>
        )}
        {breadcrumbs.map((crumb, i) => (
          <React.Fragment key={i}>
            <button
              type="button"
              onClick={() => goTo(i)}
              aria-current={i === breadcrumbs.length - 1 ? 'location' : undefined}
              className={`text-xs font-bold uppercase tracking-wider transition-colors ${
                i === breadcrumbs.length - 1
                  ? 'text-orange-500 cursor-default'
                  : 'text-white/40 hover:text-white cursor-pointer'
              }`}
            >
              {crumb}
            </button>
            {i < breadcrumbs.length - 1 && (
              <ChevronRight className="w-3 h-3 text-white/20 flex-shrink-0" />
            )}
          </React.Fragment>
        ))}
      </nav>

      {/* ── map (+ κάρτα: overlay σε desktop, από κάτω σε κινητό) ── */}
      <div className="flex-1 relative flex flex-col">
        <div
          className="flex-1 relative overflow-hidden min-h-[var(--ellada-map-h-m)] sm:min-h-[var(--ellada-map-h)]"
          style={
            {
              '--ellada-map-h': `${minHeight - 140}px`,
              '--ellada-map-h-m': `${Math.max(280, mobileH - 320)}px`,
            } as React.CSSProperties
          }
        >
          <AnimatePresence mode="wait" custom={direction}>
            <motion.div
              key={animKey}
              custom={direction}
              variants={variants}
              initial="enter"
              animate="center"
              exit="exit"
              className="absolute inset-0 flex items-center justify-center"
            >
              {currentGeo ? (
                <ComposableMap
                  projection="geoMercator"
                  projectionConfig={projConfig}
                  width={MAP_W}
                  height={MAP_H}
                  style={{ width: '100%', height: '100%' }}
                >
                  {/* polygon layer */}
                  <Geographies geography={currentGeo}>
                    {({ geographies }: { geographies: GeoFeature[] }) =>
                      geographies.map((geo) => {
                        const clickableLevel = level < 3
                        const name = displayName(level, geo)
                        const count = clickableLevel ? featureCount(level, geo) : 0
                        const hasContent =
                          clickableLevel && (count > 0 || !!featureLocation(level, geo))
                        // Μόνο όταν έχει φορτώσει το περιεχόμενο, αλλιώς όλα θα φαίνονταν «άδεια».
                        const empty =
                          emptyAreas !== 'normal' &&
                          level < 3 &&
                          wpStatus === 'ready' &&
                          !hasContent
                        const interactive = level < 3 && !(empty && emptyAreas === 'disabled')
                        const activate = () => {
                          if (level === 0) handleRegionClick(geo)
                          else if (level === 1) handlePrefectureClick(geo)
                          else if (level === 2) handleMunicipalityClick(geo)
                        }
                        const base = {
                          fill: hasContent ? CONTENT_FILL : empty ? EMPTY_STYLE.fill : style.fill,
                          stroke: empty ? EMPTY_STYLE.stroke : style.stroke,
                          strokeWidth: style.strokeWidth,
                          outline: 'none',
                        }
                        return (
                          <Geography
                            key={geo.rsmKey}
                            geography={geo}
                            role={interactive ? 'button' : undefined}
                            tabIndex={interactive ? 0 : -1}
                            aria-label={
                              interactive
                                ? `${KIND_LABEL[level]} ${name}${count ? `, ${countLabel(count)}` : ''}`
                                : undefined
                            }
                            onMouseEnter={() =>
                              interactive &&
                              setHovered({
                                kind: KIND_LABEL[level],
                                name,
                                meta: count ? countLabel(count) : undefined,
                              })
                            }
                            onMouseLeave={() => interactive && setHovered(null)}
                            onClick={interactive ? activate : undefined}
                            onKeyDown={(e: React.KeyboardEvent) => {
                              if (interactive && (e.key === 'Enter' || e.key === ' ')) {
                                e.preventDefault()
                                activate()
                              }
                            }}
                            style={{
                              default: { ...base, cursor: interactive ? 'pointer' : 'default' },
                              hover: interactive
                                ? {
                                    fill: accent(0.4),
                                    stroke: ACCENT,
                                    strokeWidth: 1.5,
                                    outline: 'none',
                                    cursor: 'pointer',
                                    filter: `drop-shadow(0 0 8px ${accent(0.6)})`,
                                  }
                                : base,
                              pressed: interactive
                                ? {
                                    fill: ACCENT,
                                    outline: 'none',
                                    filter: `drop-shadow(0 0 14px ${accent(0.9)})`,
                                  }
                                : base,
                            }}
                          />
                        )
                      })
                    }
                  </Geographies>

                  {/* settlements (προαιρετικά, μόνο σε επίπεδο δήμου) */}
                  {visibleSettlements.map((s, i) => (
                    <Marker
                      key={`s-${i}`}
                      coordinates={[s.lon, s.lat]}
                      onMouseEnter={() => setHovered({ kind: 'Οικισμός', name: s.n })}
                      onMouseLeave={() => setHovered(null)}
                    >
                      <circle r={settlementR} fill="rgba(255,255,255,0.35)" />
                    </Marker>
                  ))}

                  {/* τοπικά σημεία από το WordPress */}
                  {visiblePoints.map((p, i) => {
                    const active = p.id === selectedPointId
                    return (
                      <Marker
                        key={`p-${p.id}`}
                        coordinates={[p.lng, p.lat]}
                        onMouseEnter={() => setHovered({ kind: p.typeLabel, name: p.title })}
                        onMouseLeave={() => setHovered(null)}
                        onClick={() => handlePointClick(p)}
                        style={{
                          default: { cursor: 'pointer' },
                          hover: { cursor: 'pointer' },
                          pressed: { cursor: 'pointer' },
                        }}
                      >
                        {active && (
                          <circle
                            r={pointR * 2.6}
                            style={{
                              fill: `color-mix(in srgb, ${typeColor(p.type)} 18%, transparent)`,
                              stroke: `color-mix(in srgb, ${typeColor(p.type)} 60%, transparent)`,
                            }}
                            strokeWidth={0.8}
                          />
                        )}
                        <circle
                          r={active ? pointR * 1.3 : pointR}
                          style={{ fill: typeColor(p.type), stroke: BG }}
                          strokeWidth={pointR * 0.45}
                        />
                        {(level === 3 || i < 15) && (
                          <text
                            textAnchor="middle"
                            y={-pointR - 4}
                            style={{
                              fontFamily: 'inherit',
                              fontSize: labelSize,
                              fontWeight: 700,
                              fill: 'rgba(255,255,255,0.85)',
                              pointerEvents: 'none',
                              userSelect: 'none',
                            }}
                          >
                            {p.title}
                          </text>
                        )}
                      </Marker>
                    )
                  })}
                </ComposableMap>
              ) : (
                <div className="text-white/20 text-sm uppercase tracking-widest animate-pulse">
                  Φόρτωση δεδομένων...
                </div>
              )}
            </motion.div>
          </AnimatePresence>
        </div>

        <AnimatePresence>
          {card && (
            <InfoCard
              key={card.key}
              data={card}
              external={card.link ? isExternalLink(card.link) : false}
              onClose={() => {
                setCardDismissed(true)
                setSelectedPointId(null)
              }}
            />
          )}
        </AnimatePresence>
      </div>

      {/* ── λίστα τοπικών σημείων ── */}
      <AnimatePresence>
        {areaPoints.length > 0 && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 20 }}
            transition={{ duration: 0.3, delay: 0.2 }}
            className="px-8 pb-6 pt-2 z-10"
          >
            <p className="text-white/30 text-[9px] uppercase tracking-widest mb-3 font-bold">
              {t.pointsHeading} ({visiblePoints.length})
            </p>
            {legend && legendTypes.length > 0 && (
              <div className="flex flex-wrap gap-2 mb-3" role="group" aria-label="Φίλτρο τύπων">
                {legendTypes.map(([type, { label, count }]) => {
                  const on = !hiddenTypes.has(type)
                  return (
                    <button
                      type="button"
                      key={type}
                      aria-pressed={on}
                      onClick={() => toggleType(type)}
                      className={`flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border transition-all ${
                        on
                          ? 'border-white/20 text-white/80'
                          : 'border-white/5 text-white/25 line-through'
                      }`}
                    >
                      <span
                        className="w-2 h-2 rounded-full"
                        style={{ background: typeColor(type), opacity: on ? 1 : 0.3 }}
                      />
                      {label} · {count}
                    </button>
                  )
                })}
              </div>
            )}
            <div className="flex flex-wrap gap-2 max-h-28 overflow-y-auto">
              {visiblePoints.map((p) => (
                <button
                  type="button"
                  key={p.id}
                  onClick={() => handlePointClick(p)}
                  className={`text-[10px] px-2 py-1 rounded-full border transition-all ${
                    p.id === selectedPointId
                      ? 'border-orange-500 text-orange-400'
                      : 'border-white/10 text-white/50 hover:border-orange-500/50 hover:text-orange-400'
                  }`}
                >
                  {p.title}
                </button>
              ))}
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* ── footer hint ── */}
      <div className="flex flex-col items-center gap-1 border-t border-white/5 py-3 z-10">
        <p className="px-4 text-center text-white/20 text-[8px] uppercase tracking-[0.25em] sm:tracking-[0.4em] font-bold italic">
          {levelHint[level]}
        </p>
        {wpStatus === 'loading' && (
          <p className="text-white/20 text-[8px] uppercase tracking-[0.3em] animate-pulse">
            Φόρτωση περιεχομένου…
          </p>
        )}
        {wpStatus === 'error' && (
          <p className="text-orange-500/60 text-[8px] uppercase tracking-[0.3em]">
            Το περιεχόμενο δεν φορτώθηκε
          </p>
        )}
      </div>
    </div>
  )
}
