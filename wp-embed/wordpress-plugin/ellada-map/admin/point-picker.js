/**
 * Χάρτης επιλογής θέσης για τα Τοπικά Σημεία (διαχειριστικό WordPress).
 * Συγχρονίζει την πινέζα με τα πεδία lat/lng του ACF και δείχνει σε ποια περιοχή πέφτει.
 */
;(function () {
  'use strict'

  var cfg = window.elladaPicker || {}
  var box = document.querySelector('#ellada-map-picker .ellada-picker')
  if (!box || !window.L) return

  var GREECE = [38.3, 23.8]
  var TOL = 0.08 // ίδια ανοχή με τον χάρτη, για σημεία λίγο έξω από τις ακτές

  function acfInput(name) {
    return document.querySelector('.acf-field[data-name="' + name + '"] input')
  }
  var latInput = acfInput('lat')
  var lngInput = acfInput('lng')
  var areaEl = box.querySelector('.ellada-picker-area')
  var statusEl = box.querySelector('.ellada-picker-status')

  if (!latInput || !lngInput) {
    areaEl.textContent = 'Δεν βρέθηκαν τα πεδία lat / lng του ACF σε αυτή τη σελίδα.'
    return
  }

  /* ── χάρτης ── */
  var map = L.map(box.querySelector('.ellada-picker-map'), { scrollWheelZoom: false })
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map)
  // Το scroll μεγεθύνει μόνο όταν ο χρήστης έχει κάνει κλικ στον χάρτη.
  map.on('focus click', function () {
    map.scrollWheelZoom.enable()
  })
  map.on('blur mouseout', function () {
    map.scrollWheelZoom.disable()
  })

  var icon = L.icon({
    iconUrl: cfg.leafletImages + 'marker-icon.png',
    iconRetinaUrl: cfg.leafletImages + 'marker-icon-2x.png',
    shadowUrl: cfg.leafletImages + 'marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    shadowSize: [41, 41],
  })
  var marker = null

  function current() {
    var lat = parseFloat(latInput.value)
    var lng = parseFloat(lngInput.value)
    return isFinite(lat) && isFinite(lng) ? [lat, lng] : null
  }

  function setInputs(lat, lng) {
    latInput.value = lat.toFixed(6)
    lngInput.value = lng.toFixed(6)
    ;[latInput, lngInput].forEach(function (el) {
      el.dispatchEvent(new Event('input', { bubbles: true }))
      el.dispatchEvent(new Event('change', { bubbles: true }))
    })
  }

  function placeMarker(latlng, pan) {
    if (!marker) {
      marker = L.marker(latlng, { draggable: true, icon: icon }).addTo(map)
      marker.on('dragend', function () {
        var p = marker.getLatLng()
        setInputs(p.lat, p.lng)
        describe(p.lat, p.lng)
      })
    } else {
      marker.setLatLng(latlng)
    }
    if (pan) map.setView(latlng, Math.max(map.getZoom(), 14))
  }

  map.on('click', function (e) {
    placeMarker(e.latlng, false)
    setInputs(e.latlng.lat, e.latlng.lng)
    describe(e.latlng.lat, e.latlng.lng)
  })

  function syncFromInputs() {
    var c = current()
    if (!c) return
    placeMarker(c, true)
    describe(c[0], c[1])
  }
  latInput.addEventListener('change', syncFromInputs)
  lngInput.addEventListener('change', syncFromInputs)

  var start = current()
  if (start) {
    map.setView(start, 14)
    placeMarker(start, false)
  } else {
    map.setView(GREECE, 6)
  }

  // Το metabox μπορεί να αλλάξει μέγεθος (άνοιγμα/κλείσιμο, πλευρική στήλη).
  setTimeout(function () {
    map.invalidateSize()
  }, 300)
  if (window.jQuery)
    window.jQuery(document).on('postbox-toggled', function () {
      map.invalidateSize()
    })
  if (window.ResizeObserver)
    new ResizeObserver(function () {
      map.invalidateSize()
    }).observe(box)

  /* ── σε ποια περιοχή πέφτει ── */
  var munis = null
  var munisLoading = null

  function loadMunis() {
    if (munis) return Promise.resolve(munis)
    if (!munisLoading) {
      munisLoading = fetch(cfg.municipalitiesUrl)
        .then(function (r) {
          return r.json()
        })
        .then(function (geo) {
          munis = geo.features.map(function (f) {
            var polys = f.geometry.type === 'Polygon' ? [f.geometry.coordinates] : f.geometry.coordinates
            var x0 = Infinity, y0 = Infinity, x1 = -Infinity, y1 = -Infinity
            polys.forEach(function (poly) {
              poly[0].forEach(function (pt) {
                if (pt[0] < x0) x0 = pt[0]
                if (pt[0] > x1) x1 = pt[0]
                if (pt[1] < y0) y0 = pt[1]
                if (pt[1] > y1) y1 = pt[1]
              })
            })
            return { props: f.properties, polys: polys, bbox: [x0, y0, x1, y1] }
          })
          return munis
        })
    }
    return munisLoading
  }

  function inRing(x, y, ring) {
    var inside = false
    for (var i = 0, j = ring.length - 1; i < ring.length; j = i++) {
      var xi = ring[i][0], yi = ring[i][1], xj = ring[j][0], yj = ring[j][1]
      if (yi > y !== yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi || 1e-12) + xi) inside = !inside
    }
    return inside
  }

  function inPolys(x, y, polys) {
    return polys.some(function (poly) {
      if (!inRing(x, y, poly[0])) return false
      for (var h = 1; h < poly.length; h++) if (inRing(x, y, poly[h])) return false
      return true
    })
  }

  function locate(lat, lng) {
    var x = lng, y = lat
    var near = munis.filter(function (m) {
      var b = m.bbox
      return x >= b[0] - TOL && x <= b[2] + TOL && y >= b[1] - TOL && y <= b[3] + TOL
    })
    for (var i = 0; i < near.length; i++) if (inPolys(x, y, near[i].polys)) return { m: near[i], exact: true }
    var best = null, bestD = TOL * TOL
    near.forEach(function (m) {
      m.polys.forEach(function (poly) {
        poly[0].forEach(function (pt) {
          var d = (pt[0] - x) * (pt[0] - x) + (pt[1] - y) * (pt[1] - y)
          if (d < bestD) {
            bestD = d
            best = m
          }
        })
      })
    })
    return best ? { m: best, exact: false } : null
  }

  function describe(lat, lng) {
    areaEl.className = 'ellada-picker-area'
    areaEl.textContent = 'Εντοπισμός περιοχής…'
    loadMunis()
      .then(function () {
        var hit = locate(lat, lng)
        if (!hit) {
          areaEl.className = 'ellada-picker-area is-error'
          areaEl.textContent = '✕ Εκτός Ελλάδας — το σημείο δεν θα εμφανιστεί στον χάρτη.'
          return
        }
        var p = hit.m.props
        var pref = p.prefecture_greek || ''
        var key = pref + '|' + p.name_greek
        // «ΠαλαιούΦαλήρου» → «Παλαιού Φαλήρου» αν δεν υπάρχει ετικέτα από το ACF.
        var name =
          (cfg.muniLabels && cfg.muniLabels[key]) ||
          String(p.name_greek).replace(/([a-zα-ωάέήίόύώϊϋΐΰ])([A-ZΑ-ΩΆΈΉΊΌΎΏ])/g, '$1 $2')
        var region = (cfg.prefRegions && cfg.prefRegions[pref]) || ''
        areaEl.className = 'ellada-picker-area is-ok'
        areaEl.textContent =
          '📍 Θα εμφανιστεί στον Δήμο ' + name + ' · Ν. ' + pref + (region ? ' · ' + region : '') +
          (hit.exact ? '' : ' (λίγο έξω από τα όρια — μπαίνει στον κοντινότερο δήμο)')
      })
      .catch(function () {
        areaEl.textContent = ''
      })
  }

  if (start) describe(start[0], start[1])

  /* ── αναζήτηση (OpenStreetMap Nominatim, μόνο Ελλάδα) ── */
  var searchInput = box.querySelector('.ellada-picker-search input')
  var searchBtn = box.querySelector('.ellada-picker-search button')

  function search() {
    var q = searchInput.value.trim()
    if (!q) return
    statusEl.textContent = 'Αναζήτηση…'
    var url =
      'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=gr&accept-language=el&q=' +
      encodeURIComponent(q)
    fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (r) {
        return r.json()
      })
      .then(function (res) {
        if (!res || !res.length) {
          statusEl.textContent = 'Δεν βρέθηκε. Δοκιμάστε πιο γενικό όρο ή κάντε κλικ στον χάρτη.'
          return
        }
        var lat = parseFloat(res[0].lat)
        var lng = parseFloat(res[0].lon)
        statusEl.textContent = res[0].display_name
        placeMarker([lat, lng], true)
        setInputs(lat, lng)
        describe(lat, lng)
      })
      .catch(function () {
        statusEl.textContent = 'Η αναζήτηση δεν είναι διαθέσιμη αυτή τη στιγμή.'
      })
  }
  searchBtn.addEventListener('click', search)
  searchInput.addEventListener('keydown', function (e) {
    // Το Enter δεν πρέπει να υποβάλει τη φόρμα του άρθρου.
    if (e.key === 'Enter') {
      e.preventDefault()
      search()
    }
  })
})()
