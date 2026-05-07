'use client'
import React, { useEffect, useState, useCallback, useRef } from "react"
import { ComposableMap, Geographies, Geography, Marker } from "react-simple-maps"
import { motion, AnimatePresence } from "framer-motion"
import { ChevronRight, ArrowLeft } from "lucide-react"

/* ── types ── */
type ProjConfig = { scale: number; center: [number, number] }
type BreadcrumbItem = { name: string }
type Settlement = { n: string; lat: number; lon: number }

type Props = {
  onLocationSelect?: (name: string) => void
}

/* ── constants ── */
const GREECE_PROJ: ProjConfig = { scale: 2400, center: [24.51, 38.28] }
const MAP_W = 800
const MAP_H = 500
const EASE: [number, number, number, number] = [0.22, 1, 0.36, 1]

/* ── helpers ── */
function getBBox(features: any[]) {
  let minLon = Infinity, maxLon = -Infinity, minLat = Infinity, maxLat = -Infinity
  const collect = (c: any) => {
    if (typeof c[0] === 'number') {
      if (c[0] < minLon) minLon = c[0]
      if (c[0] > maxLon) maxLon = c[0]
      if (c[1] < minLat) minLat = c[1]
      if (c[1] > maxLat) maxLat = c[1]
    } else c.forEach(collect)
  }
  features.forEach(f => collect(f.geometry.coordinates))
  if (minLon === Infinity) return null
  return { minLon, maxLon, minLat, maxLat }
}

function calcProjection(features: any[], paddingFactor = 1.6): ProjConfig {
  const bb = getBBox(features)
  if (!bb) return GREECE_PROJ
  const { minLon, maxLon, minLat, maxLat } = bb
  const cx = (minLon + maxLon) / 2
  const cy = (minLat + maxLat) / 2
  const lonSpan = (maxLon - minLon) * paddingFactor
  const latSpan = (maxLat - minLat) * paddingFactor
  const effectiveSpan = Math.max(lonSpan, latSpan * (MAP_W / MAP_H), 0.05)
  const scale = Math.round(GREECE_PROJ.scale * 10.27 / effectiveSpan)
  return { scale, center: [cx, cy] }
}

function filterGeo(geo: any, key: string, value: string) {
  return {
    type: 'FeatureCollection',
    features: geo.features.filter((f: any) => f.properties[key] === value),
  }
}

/* ── animation variants ── */
const variants = {
  enter: (d: number) => ({ scale: d > 0 ? 0.55 : 1.5, opacity: 0 }),
  center: { scale: 1, opacity: 1, transition: { duration: 0.5, ease: EASE } },
  exit: (d: number) => ({ scale: d > 0 ? 1.5 : 0.55, opacity: 0, transition: { duration: 0.32, ease: EASE } }),
}

/* ── per-level map style ── */
const LEVEL_STYLE = [
  { fill: 'rgba(255,255,255,0.04)', stroke: 'rgba(255,255,255,0.18)', strokeWidth: 0.5 },
  { fill: 'rgba(249,115,22,0.06)', stroke: 'rgba(249,115,22,0.35)', strokeWidth: 0.8 },
  { fill: 'rgba(249,115,22,0.08)', stroke: 'rgba(249,115,22,0.45)', strokeWidth: 1 },
  { fill: 'rgba(249,115,22,0.12)', stroke: 'rgba(249,115,22,0.5)',  strokeWidth: 1.2 },
]
const LEVEL_HINT = [
  'Κάντε κλικ σε περιφέρεια για εξερεύνηση',
  'Κάντε κλικ σε νομό για εξερεύνηση',
  'Κάντε κλικ σε δήμο για εξερεύνηση',
  'Κάντε κλικ σε οικισμό για επιλογή',
]
const LEVEL_LABEL = ['Περιφέρειες', 'Νομοί', 'Δήμοι', 'Οικισμοί']

/* ══════════════════════════════════════════════ */
export default function MapSection({ onLocationSelect }: Props) {
  const [regionsGeo, setRegionsGeo]         = useState<any>(null)
  const [prefecturesGeo, setPrefecturesGeo] = useState<any>(null)
  const [municipalitiesGeo, setMunicipalitiesGeo] = useState<any>(null)
  const [settlementsMap, setSettlementsMap] = useState<Record<string, Settlement[]>>({})

  const [level, setLevel]                     = useState(0)
  const [projConfig, setProjConfig]           = useState<ProjConfig>(GREECE_PROJ)
  const [breadcrumbs, setBreadcrumbs]         = useState<BreadcrumbItem[]>([{ name: 'Ελλάδα' }])
  const [selectedRegion, setSelectedRegion]   = useState<string | null>(null)
  const [selectedPref, setSelectedPref]       = useState<string | null>(null)
  const [selectedMuni, setSelectedMuni]       = useState<string | null>(null)
  const [muniGeoForL3, setMuniGeoForL3]       = useState<any>(null)

  const [hoveredName, setHoveredName]   = useState<string | null>(null)
  const [direction, setDirection]       = useState(1)
  const [animKey, setAnimKey]           = useState(0)
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    fetch("/greece-regions.geojson").then(r => r.json()).then(setRegionsGeo)
    fetch("/greece-prefectures.geojson").then(r => r.json()).then(setPrefecturesGeo)
    fetch("/greece-municipalities.geojson").then(r => r.json()).then(setMunicipalitiesGeo)
    fetch("/greece-settlements.json").then(r => r.json()).then(setSettlementsMap)
  }, [])

  /* ── helpers ── */
  const transition = (d: number) => { setDirection(d); setAnimKey(k => k + 1) }

  /* ── level 0→1: region click ── */
  const handleRegionClick = useCallback((name: string) => {
    if (!regionsGeo) return
    const feat = regionsGeo.features.find(
      (f: any) => (f.properties.name_greek || f.properties.name) === name
    )
    if (!feat) return
    setProjConfig(calcProjection([feat]))
    setSelectedRegion(name)
    setLevel(1)
    setBreadcrumbs([{ name: 'Ελλάδα' }, { name }])
    transition(1)
  }, [regionsGeo])

  /* ── level 1→2: prefecture click ── */
  const handlePrefectureClick = useCallback((name: string) => {
    if (!municipalitiesGeo) return
    const muniFeats = municipalitiesGeo.features.filter(
      (f: any) => f.properties.prefecture_greek === name
    )
    setProjConfig(muniFeats.length ? calcProjection(muniFeats) : GREECE_PROJ)
    setSelectedPref(name)
    setLevel(2)
    setBreadcrumbs(prev => [...prev, { name }])
    transition(1)
    onLocationSelect?.(name)
  }, [municipalitiesGeo, onLocationSelect])

  /* ── level 2→3: municipality click ── */
  const handleMunicipalityClick = useCallback((name: string) => {
    if (!municipalitiesGeo) return
    const feat = municipalitiesGeo.features.find(
      (f: any) => (f.properties.name_greek || f.properties.name) === name
    )
    if (!feat) return
    // zoom tighter into the single municipality polygon
    setProjConfig(calcProjection([feat], 1.3))
    setSelectedMuni(name)
    setMuniGeoForL3({ type: 'FeatureCollection', features: [feat] })
    setLevel(3)
    setBreadcrumbs(prev => [...prev, { name }])
    transition(1)
    onLocationSelect?.(name)
  }, [municipalitiesGeo, onLocationSelect])

  /* ── breadcrumb navigation ── */
  const goTo = useCallback((index: number) => {
    if (index === breadcrumbs.length - 1) return
    transition(-1)
    if (index === 0) {
      setProjConfig(GREECE_PROJ); setSelectedRegion(null)
      setSelectedPref(null); setSelectedMuni(null); setMuniGeoForL3(null)
      setLevel(0); setBreadcrumbs([{ name: 'Ελλάδα' }])
    } else if (index === 1 && selectedRegion) {
      const feat = regionsGeo?.features.find(
        (f: any) => (f.properties.name_greek || f.properties.name) === selectedRegion
      )
      setProjConfig(feat ? calcProjection([feat]) : GREECE_PROJ)
      setSelectedPref(null); setSelectedMuni(null); setMuniGeoForL3(null)
      setLevel(1); setBreadcrumbs(prev => prev.slice(0, 2))
    } else if (index === 2 && selectedPref) {
      const muniFeats = municipalitiesGeo?.features.filter(
        (f: any) => f.properties.prefecture_greek === selectedPref
      ) ?? []
      setProjConfig(muniFeats.length ? calcProjection(muniFeats) : GREECE_PROJ)
      setSelectedMuni(null); setMuniGeoForL3(null)
      setLevel(2); setBreadcrumbs(prev => prev.slice(0, 3))
    }
  }, [breadcrumbs.length, selectedRegion, selectedPref, regionsGeo, municipalitiesGeo])

  /* ── current geo to render ── */
  const currentGeo = (() => {
    if (level === 0) return regionsGeo
    if (level === 1 && prefecturesGeo && selectedRegion)
      return filterGeo(prefecturesGeo, 'periphery_greek', selectedRegion)
    if (level === 2 && municipalitiesGeo && selectedPref)
      return filterGeo(municipalitiesGeo, 'prefecture_greek', selectedPref)
    if (level === 3) return muniGeoForL3
    return null
  })()

  const settlements: Settlement[] = level === 3 && selectedMuni
    ? (settlementsMap[selectedMuni] ?? [])
    : []

  const style = LEVEL_STYLE[Math.min(level, 3)]

  /* ── marker size based on zoom ── */
  const dotR = level === 3 ? Math.max(2, Math.min(5, projConfig.scale / 4000)) : 0

  if (!regionsGeo) {
    return (
      <div className="bg-[#0a0a0a] rounded-[2.5rem] border border-white/5 p-8 min-h-[600px] flex items-center justify-center">
        <div className="text-orange-500 animate-pulse font-bold tracking-widest uppercase text-xs">
          Φορτωση Γεωγραφικων Δεδομενων...
        </div>
      </div>
    )
  }

  return (
    <div
      ref={containerRef}
      className="bg-[#0a0a0a] rounded-[2.5rem] border border-white/5 shadow-2xl relative flex flex-col overflow-hidden"
      style={{ minHeight: 600 }}
    >
      {/* ── top bar ── */}
      <div className="flex items-center justify-between px-8 pt-8 pb-3 z-10">
        <div>
          <h2 className="text-3xl font-black tracking-tighter uppercase italic">Εξερεύνηση</h2>
          <p className="text-orange-500 text-[10px] font-bold tracking-[0.2em] flex items-center gap-2 uppercase mt-1">
            <span className="w-2 h-2 rounded-full bg-orange-500 animate-pulse" />
            {LEVEL_LABEL[level]}
            {level >= 1 && selectedRegion && ` — ${selectedRegion}`}
            {level >= 2 && selectedPref && ` › ${selectedPref}`}
            {level === 3 && selectedMuni && ` › ${selectedMuni}`}
          </p>
        </div>

        <AnimatePresence>
          {hoveredName && (
            <motion.div
              key={hoveredName}
              initial={{ opacity: 0, y: -6 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -6 }}
              transition={{ duration: 0.15 }}
              className="text-right max-w-[200px]"
            >
              <p className="text-white/30 text-[9px] uppercase tracking-widest">
                {level === 0 ? 'Περιφέρεια' : level === 1 ? 'Νομός' : level === 2 ? 'Δήμος' : 'Οικισμός'}
              </p>
              <p className="text-white font-bold text-sm leading-tight">{hoveredName}</p>
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {/* ── breadcrumbs ── */}
      <nav className="flex items-center gap-1 px-8 pb-3 z-10 flex-wrap">
        {level > 0 && (
          <button
            onClick={() => goTo(level - 1)}
            className="flex items-center gap-1 text-white/40 hover:text-orange-400 transition-colors mr-2"
          >
            <ArrowLeft className="w-3 h-3" />
          </button>
        )}
        {breadcrumbs.map((crumb, i) => (
          <React.Fragment key={i}>
            <button
              onClick={() => goTo(i)}
              className={`text-xs font-bold uppercase tracking-wider transition-colors ${
                i === breadcrumbs.length - 1
                  ? 'text-orange-500 cursor-default'
                  : 'text-white/40 hover:text-white cursor-pointer'
              }`}
            >
              {crumb.name}
            </button>
            {i < breadcrumbs.length - 1 && (
              <ChevronRight className="w-3 h-3 text-white/20 flex-shrink-0" />
            )}
          </React.Fragment>
        ))}
      </nav>

      {/* ── map ── */}
      <div className="flex-1 relative overflow-hidden" style={{ minHeight: 460 }}>
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
                  {({ geographies }: { geographies: any[] }) =>
                    geographies.map((geo: any) => {
                      const name = geo.properties.name_greek || geo.properties.name || ''
                      const isSettlementLevel = level === 3
                      return (
                        <Geography
                          key={geo.rsmKey}
                          geography={geo}
                          onMouseEnter={() => !isSettlementLevel && setHoveredName(name)}
                          onMouseLeave={() => !isSettlementLevel && setHoveredName(null)}
                          onClick={() => {
                            if (level === 0) handleRegionClick(name)
                            else if (level === 1) handlePrefectureClick(geo.properties.name_greek || geo.properties.name)
                            else if (level === 2) handleMunicipalityClick(geo.properties.name_greek || geo.properties.name)
                          }}
                          style={{
                            default: {
                              fill: isSettlementLevel ? 'rgba(249,115,22,0.05)' : style.fill,
                              stroke: isSettlementLevel ? 'rgba(249,115,22,0.25)' : style.stroke,
                              strokeWidth: style.strokeWidth,
                              outline: 'none',
                              cursor: isSettlementLevel ? 'default' : 'pointer',
                            },
                            hover: isSettlementLevel ? {
                              fill: 'rgba(249,115,22,0.05)',
                              stroke: 'rgba(249,115,22,0.25)',
                              strokeWidth: style.strokeWidth,
                              outline: 'none',
                            } : {
                              fill: 'rgba(249,115,22,0.4)',
                              stroke: '#E8700A',
                              strokeWidth: 1.5,
                              outline: 'none',
                              cursor: 'pointer',
                              filter: 'drop-shadow(0 0 8px rgba(249,115,22,0.6))',
                            },
                            pressed: isSettlementLevel ? {
                              fill: 'rgba(249,115,22,0.05)', outline: 'none'
                            } : {
                              fill: '#E8700A', outline: 'none',
                              filter: 'drop-shadow(0 0 14px rgba(249,115,22,0.9))',
                            },
                          }}
                        />
                      )
                    })
                  }
                </Geographies>

                {/* settlement markers (level 3) */}
                {level === 3 && settlements.map((s, i) => (
                  <Marker
                    key={i}
                    coordinates={[s.lon, s.lat]}
                    onMouseEnter={() => setHoveredName(s.n)}
                    onMouseLeave={() => setHoveredName(null)}
                    onClick={() => onLocationSelect?.(s.n)}
                  >
                    <circle
                      r={dotR}
                      fill="#E8700A"
                      fillOpacity={0.85}
                      stroke="rgba(249,115,22,0.3)"
                      strokeWidth={dotR * 1.5}
                      style={{ cursor: 'pointer' }}
                    />
                    {i < 12 && (
                      <text
                        textAnchor="middle"
                        y={-dotR - 3}
                        style={{
                          fontFamily: 'sans-serif',
                          fontSize: Math.max(5, Math.min(8, projConfig.scale / 5000)),
                          fill: 'rgba(255,255,255,0.75)',
                          pointerEvents: 'none',
                          userSelect: 'none',
                        }}
                      >
                        {s.n}
                      </text>
                    )}
                  </Marker>
                ))}
              </ComposableMap>
            ) : (
              <div className="text-white/20 text-sm uppercase tracking-widest animate-pulse">
                Φόρτωση δεδομένων...
              </div>
            )}
          </motion.div>
        </AnimatePresence>
      </div>

      {/* ── settlement list (level 3 sidebar) ── */}
      <AnimatePresence>
        {level === 3 && settlements.length > 0 && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 20 }}
            transition={{ duration: 0.3, delay: 0.2 }}
            className="px-8 pb-6 z-10"
          >
            <p className="text-white/30 text-[9px] uppercase tracking-widest mb-3 font-bold">
              Οικισμοί δήμου ({settlements.length})
            </p>
            <div className="flex flex-wrap gap-2 max-h-28 overflow-y-auto">
              {settlements.map((s, i) => (
                <button
                  key={i}
                  onClick={() => onLocationSelect?.(s.n)}
                  className="text-[10px] px-2 py-1 rounded-full border border-white/10 text-white/50 hover:border-orange-500/50 hover:text-orange-400 transition-all"
                >
                  {s.n}
                </button>
              ))}
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* ── footer hint ── */}
      <div className="flex justify-center border-t border-white/5 py-3 z-10">
        <p className="text-white/20 text-[8px] uppercase tracking-[0.4em] font-bold italic">
          {LEVEL_HINT[level]}
        </p>
      </div>
    </div>
  )
}
