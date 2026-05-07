# Χάρτης «Ελλάδα στο Πιάτο» για WordPress

Ο διαδραστικός χάρτης (Περιφέρειες → Νομοί → Δήμοι → Τοπικά Σημεία) παίρνει πλέον το περιεχόμενό του
από το **WordPress REST API + ACF Pro**, όχι από το Payload CMS. Χτίζεται σε **ένα αυτόνομο JavaScript
αρχείο** μέσα σε ένα μικρό WordPress plugin με shortcode `[ellada_map]`.

```
src/lib/wordpress/        ← client για το WP REST API (σελιδοποίηση, ACF mapping, cache)
src/components/map/       ← ο χάρτης (κοινός για Next.js και WordPress)
wp-embed/
  src/main.tsx            ← entry του bundle: Shadow DOM mount, data-* config, global API
  vite.config.ts          ← build σε IIFE + dev server με ψεύτικο WordPress API
  scripts/                ← πακετάρισμα plugin, παραγωγή acf-import.json, zip
  wordpress-plugin/ellada-map/
    ellada-map.php        ← το plugin (shortcode + φόρτωση script)
    acf-import.json       ← post types + ACF πεδία για εισαγωγή στο ACF
  dist/ellada-map.zip     ← (παράγεται) έτοιμο για ανέβασμα στο WordPress
```

## Από πού έρχονται τα δεδομένα

| Τι | Πηγή |
| --- | --- |
| Περιγράμματα περιφερειών / νομών / δήμων | Στατικά GeoJSON μέσα στο plugin (`assets/geo/`) |
| Σελίδα + κάρτα κάθε περιοχής | CPT **Τοποθεσίες** → `GET /wp-json/wp/v2/locations?acf_format=standard` |
| Τοπικά σημεία (pins) | CPT **Τοπικά Σημεία** → `GET /wp-json/wp/v2/local-points?acf_format=standard` |

ACF πεδία που διαβάζει ο χάρτης:

- **Τοποθεσία** (`ellada_location`, ιεραρχικό): `map_level` (Περιφέρεια/Νομός/Δήμος), `geo_region` /
  `geo_prefecture` / `geo_municipality` (λίστα με όλες τις περιοχές του χάρτη), `short_description`,
  `cover_image`. Επιπλέον, για τη σελίδα της περιοχής: `highlights`, `best_time_to_visit`,
  `how_to_get_there`, `local_products`, `travel_tips`, `gallery`.
- **Τοπικό σημείο** (`ellada_point`): `point_type`, `lat`, `lng`, `short_description`, `cover_image`,
  `external_url`. Ο δήμος/νομός βρίσκεται **αυτόματα από τις συντεταγμένες**.

---

## Βήμα 1 — Ρύθμιση WordPress (μία φορά)

1. **ACF → Εργαλεία → Εισαγωγή** (Tools → Import), διάλεξε το
   `wp-embed/wordpress-plugin/ellada-map/acf-import.json` και πάτα **Εισαγωγή JSON**.
   Δημιουργούνται 2 post types (Τοποθεσίες, Τοπικά Σημεία) και 2 field groups, με ενεργό το
   «Show in REST API».
2. **Ρυθμίσεις → Μόνιμοι σύνδεσμοι → Αποθήκευση αλλαγών** (χωρίς αλλαγές), για να
   ενεργοποιηθούν τα URLs `/perioxes/…` και `/topika-simeia/…`.
3. Έλεγχος: άνοιξε στον browser
   `https://elladastopiato.gr/wp-json/wp/v2/locations?acf_format=standard`
   — πρέπει να δεις `[]` (ή τις τοποθεσίες σου) και όχι σφάλμα `rest_no_route`.

## Βήμα 2 — Περιεχόμενο

- **Τοποθεσίες → Προσθήκη**: π.χ. «Κρήτη» με Επίπεδο *Περιφέρεια* και «Περιφέρεια στον χάρτη» = Κρήτη.
  Μετά «Χανιά» με Επίπεδο *Νομός*, «Νομός στον χάρτη» = ΧΑΝΙΩΝ και **Γονέας** = Κρήτη.
  Η επιλογή «… στον χάρτη» είναι αυτή που συνδέει τη σελίδα με το πολύγωνο.
- **Τοπικά Σημεία → Προσθήκη**: τίτλος, είδος, `lat` / `lng` (Google Maps → δεξί κλικ στο σημείο →
  κλικ στις συντεταγμένες για αντιγραφή· η 1η τιμή είναι το lat), περιγραφή, φωτογραφία.

Περιοχές με περιεχόμενο φαίνονται πιο φωτεινές στον χάρτη. Περιοχές χωρίς σελίδα δουλεύουν κανονικά
(απλώς δεν έχουν κάρτα).

## Βήμα 3 — Build του plugin

Από τον φάκελο του project (Node 20+):

```bash
npm install
```

```bash
npm run wp-map:build
```

Αποτέλεσμα: **`wp-embed/dist/ellada-map.zip`** (~1 MB). Περιέχει το `ellada-map.js` (~150 KB gzip),
τα GeoJSON (ελαφρυμένα: οι νομοί από 3,4 MB σε 1,6 MB) και το `acf-import.json`.

## Βήμα 4 — Εγκατάσταση στο WordPress

1. **Πρόσθετα → Προσθήκη νέου → Ανέβασμα πρόσθετου** → διάλεξε το `ellada-map.zip` → **Εγκατάσταση** →
   **Ενεργοποίηση**.
2. Σε οποιαδήποτε σελίδα:
   - **Gutenberg**: block «Shortcode» → `[ellada_map]`
   - **Elementor**: widget «Shortcode» → `[ellada_map]` (το plugin φορτώνει το script και στο
     preview του Elementor editor)
3. Προαιρετικές παράμετροι:

   ```
   [ellada_map title="Εξερεύνησε ανά περιοχή" height="650" settlements="true"]
   ```

   | Παράμετρος | Default | Περιγραφή |
   | --- | --- | --- |
   | `title` | Εξερεύνηση | Τίτλος πάνω αριστερά |
   | `height` | 600 | Ελάχιστο ύψος (px) |
   | `settlements` | false | Οικισμοί ως γκρι κουκκίδες σε επίπεδο δήμου (+460 KB) |
   | `locations` / `points` | locations / local-points | REST base, αν τα αλλάξεις στο ACF |

Ο χάρτης αποδίδεται μέσα σε **Shadow DOM**: τα CSS του theme/Elementor δεν τον «χαλάνε» και το CSS του
χάρτη δεν επηρεάζει το site. Παίρνει μόνο τη γραμματοσειρά του theme.

## Ενημέρωση σε νέα έκδοση

1. `npm run wp-map:build`
2. **Πρόσθετα → Προσθήκη νέου → Ανέβασμα** το νέο zip → **Αντικατάσταση της τρέχουσας με τη νέα**.
3. Το script φορτώνεται με `?ver=<χρόνος build>`, οπότε η cache (LiteSpeed) ανανεώνεται μόνη της. Αν
   δεις παλιά έκδοση: **LiteSpeed Cache → Purge All**.

## Ανάπτυξη τοπικά (χωρίς WordPress)

```bash
npm run wp-map:dev
```

- http://localhost:5178/ — ο χάρτης από τον πηγαίο κώδικα, με **ψεύτικο WP REST API**
  (`wp-embed/dev/mock-wp/*.json`, 3 εγγραφές ανά σελίδα για να δοκιμάζεται η σελιδοποίηση) και ένα
  «εχθρικό» theme CSS για έλεγχο απομόνωσης.
- http://localhost:5178/preview-build.html — το **production** `ellada-map.js` (μετά από `wp-map:build`),
  ακριβώς όπως το φορτώνει το plugin.

## Χωρίς plugin (προχωρημένο)

Το bundle δουλεύει σε οποιαδήποτε σελίδα:

```html
<div data-ellada-map
     data-api="https://elladastopiato.gr/wp-json/"
     data-geo="https://elladastopiato.gr/wp-content/plugins/ellada-map/assets/geo/"></div>
<script src="https://elladastopiato.gr/wp-content/plugins/ellada-map/assets/js/ellada-map.js" defer></script>
```

JavaScript API και events:

```js
ElladaMap.mount(element, { apiRoot, geoBase, settlements: true, title: 'Χάρτης' })
ElladaMap.unmount(element)

document.addEventListener('ellada-map:select', (e) => {
  // e.detail = { type: 'region' | 'prefecture' | 'municipality', name, location }
  //          | { type: 'point', point }
})
```

## Next.js app

Το `MapWrapper` χρησιμοποιεί τον ίδιο χάρτη. Βάλε στο Vercel / `.env`:

```
NEXT_PUBLIC_WP_API_URL=https://elladastopiato.gr/wp-json/
```

Χωρίς τη μεταβλητή ο χάρτης δουλεύει, αλλά χωρίς περιεχόμενο από το WordPress. Οι σελίδες
`/regions/[slug]` του Next.js διαβάζουν ακόμα από το Payload· οι κάρτες του χάρτη οδηγούν πλέον στις
σελίδες του WordPress.

## Αντιμετώπιση προβλημάτων

Ο χάρτης γράφει προειδοποιήσεις στην κονσόλα του browser (F12) με πρόθεμα `[ΕλλάδαΣτοΠιάτο χάρτης]`.

| Σύμπτωμα | Λύση |
| --- | --- |
| «Το endpoint wp/v2/locations δεν υπάρχει» | Βήμα 1 (εισαγωγή ACF + αποθήκευση μόνιμων συνδέσμων) |
| «Λείπει το πεδίο `acf` από το REST» | ACF → Field Groups → ρυθμίσεις ομάδας → **Show in REST API** |
| Μια περιοχή δεν έχει κάρτα | Έλεγξε «Επίπεδο» και «… στον χάρτη» στη σελίδα της |
| «Τοπικά σημεία εκτός ορίων» | Λάθος lat/lng (π.χ. αντεστραμμένα) |
| Η κάρτα δεν ενημερώνεται μετά από αλλαγή | LiteSpeed Cache → Purge All |
| «Το περιεχόμενο δεν φορτώθηκε» | Plugin ασφαλείας που μπλοκάρει το REST API για επισκέπτες |
