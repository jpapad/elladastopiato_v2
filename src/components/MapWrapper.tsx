'use client'
import { useRouter } from 'next/navigation'
import { useCallback } from 'react'
import MapSection from './MapSection'

export default function MapWrapper() {
  const router = useRouter()

  const handleSelect = useCallback(async (name: string) => {
    // find matching Payload location by name (exact or like)
    const res = await fetch(
      `/api/locations?where[name][equals]=${encodeURIComponent(name)}&limit=1&depth=0`
    )
    const json = await res.json()
    const loc = json.docs?.[0]
    if (loc) {
      router.push(`/regions/${loc.slug || loc.id}`)
      return
    }
    // fallback: fuzzy search
    const res2 = await fetch(
      `/api/locations?where[name][like]=${encodeURIComponent(name)}&limit=1&depth=0`
    )
    const json2 = await res2.json()
    const loc2 = json2.docs?.[0]
    if (loc2) router.push(`/regions/${loc2.slug || loc2.id}`)
  }, [router])

  return <MapSection onLocationSelect={handleSelect} />
}
