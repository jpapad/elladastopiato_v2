'use client'
import React, { useEffect, useState } from "react"
import { ComposableMap, Geographies, Geography } from "react-simple-maps"

export default function MapSection({ onLocationSelect }: { onLocationSelect: (id: string) => void }) {
  const [geoData, setGeoData] = useState(null);

  // Φορτώνουμε το τοπικό αρχείο από τον φάκελο public
  useEffect(() => {
    fetch("/regions_simplified_0.01deg.geojson")
      .then((response) => response.json())
      .then((data) => setGeoData(data))
      .catch((err) => console.error("Error loading map data:", err));
  }, []);

  if (!geoData) {
    return (
      <div className="bg-[#0a0a0a] rounded-[2.5rem] border border-white/5 p-8 min-h-[600px] flex items-center justify-center">
        <div className="text-orange-500 animate-pulse font-bold tracking-widest uppercase text-xs">
          Φορτωση Γεωγραφικων Δεδομενων...
        </div>
      </div>
    );
  }

  return (
    <div className="bg-[#0a0a0a] rounded-[2.5rem] border border-white/5 p-8 shadow-2xl relative min-h-[600px] flex flex-col items-center justify-center overflow-hidden group">
      
      <div className="absolute top-8 left-8 z-10">
        <h2 className="text-3xl font-black tracking-tighter uppercase italic drop-shadow-2xl">Εξερεύνηση</h2>
        <p className="text-orange-500 text-[10px] font-bold tracking-[0.2em] flex items-center gap-2 uppercase">
          <span className="w-2 h-2 rounded-full bg-orange-500 animate-pulse" />
          ΤΟΠΙΚΟΙ ΧΑΡΤΕΣ ΥΨΗΛΗΣ ΑΚΡΙΒΕΙΑΣ
        </p>
      </div>

      <div className="w-full h-full flex items-center justify-center scale-[1.3] md:scale-[1.5] transition-transform duration-700 translate-y-4">
        <ComposableMap
          projection="geoMercator"
          projectionConfig={{
            scale: 3000,
            center: [24.5, 38.0] 
          }}
          style={{ width: "100%", height: "100%" }}
        >
          <Geographies geography={geoData}>
            {({ geographies }: { geographies: any[] }) =>
              geographies.map((geo: any) => (
                <Geography
                  key={geo.rsmKey}
                  geography={geo}
                  onClick={() => {
  console.log("Περιοχή GeoJSON:", geo.properties); // <--- Αυτό θα σου δείξει το όνομα
  onLocationSelect(geo.properties.name || geo.properties.NAME_1);
}}
                  style={{
                    default: {
                      fill: "rgba(255, 255, 255, 0.04)",
                      stroke: "rgba(255, 255, 255, 0.15)",
                      strokeWidth: 0.4,
                      outline: "none",
                    },
                    hover: {
                      fill: "rgba(249, 115, 22, 0.35)", 
                      stroke: "#f97316",
                      strokeWidth: 1,
                      outline: "none",
                      cursor: "pointer",
                    },
                    pressed: {
                      fill: "#f97316",
                      outline: "none",
                    },
                  }}
                />
              ))
            }
          </Geographies>
        </ComposableMap>
      </div>

      <div className="absolute bottom-8 flex items-center gap-4 border-t border-white/5 pt-6 w-[80%] justify-center">
        <div className="text-white/20 text-[8px] uppercase tracking-[0.4em] font-bold italic">
          Localized GeoJSON Architecture
        </div>
      </div>
    </div>
  )
}