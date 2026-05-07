'use client'
import { GreeceMap, type GeoUrls } from '@/components/map'

/**
 * Ο χάρτης στο Next.js app. Τα δεδομένα (τοποθεσίες + τοπικά σημεία) έρχονται πλέον από
 * το WordPress REST API + ACF Pro αντί για το Payload CMS.
 *
 * Ρύθμιση: NEXT_PUBLIC_WP_API_URL=https://elladastopiato.gr/wp-json/
 * Χωρίς τη μεταβλητή ο χάρτης δουλεύει κανονικά, απλώς χωρίς περιεχόμενο από το CMS.
 */
const GEO_URLS: GeoUrls = {
  regions: '/greece-regions.geojson',
  prefectures: '/greece-prefectures.geojson',
  municipalities: '/greece-municipalities.geojson',
}

export default function MapWrapper() {
  return <GreeceMap apiRoot={process.env.NEXT_PUBLIC_WP_API_URL} geoUrls={GEO_URLS} />
}
