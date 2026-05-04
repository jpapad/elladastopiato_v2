# Ελλάδα στο Πιάτο — Project Specifications

## Τι είναι το project

**Ελλάδα στο Πιάτο** είναι μια διαδικτυακή πλατφόρμα ελληνικής γαστρονομίας. Στόχος της είναι να αναδείξει την παραδοσιακή ελληνική κουζίνα ανά γεωγραφική περιοχή, συνδέοντας κάθε συνταγή με την καταγωγή της μέσα από διαδραστικό χάρτη. Απευθύνεται σε χρήστες που θέλουν να εξερευνήσουν την τοπική γαστρονομική κληρονομιά της Ελλάδας.

---

## Tech Stack

| Κατηγορία | Τεχνολογία | Έκδοση |
|---|---|---|
| Framework | Next.js | 15.4.11 |
| UI Library | React | 19.2.1 |
| Language | TypeScript | 5.7.3 |
| CMS | Payload CMS | 3.74.0 |
| Database | PostgreSQL (via Payload adapter) | — |
| Rich Text | Payload Lexical Editor | 3.74.0 |
| Styling | Tailwind CSS | 4.1.18 |
| Animations | Framer Motion | 12.31.0 |
| Interactive Map | react-simple-maps | 3.0.0 |
| Icons | Lucide React | 0.563.0 |
| Image Processing | Sharp | 0.34.2 |
| Testing (E2E) | Playwright | 1.56.1 |
| Testing (Unit) | Vitest | 3.2.3 |
| Linting | ESLint | 9.16.0 |
| Formatting | Prettier | 3.4.2 |

---

## Αρχιτεκτονική

Το project ακολουθεί το **Headless CMS pattern**:

- **Payload CMS** — διαχείριση όλου του περιεχομένου μέσω admin dashboard
- **PostgreSQL** — αποθήκευση δεδομένων
- **Next.js** — server-side rendering & static generation για το frontend
- **Block-based Page Builder** — οι σελίδες χτίζονται από modular blocks μέσω του admin, χωρίς κώδικα
- **GraphQL + REST API** — auto-generated από το Payload για πρόσβαση στα δεδομένα

---

## Collections (Μοντέλα Δεδομένων)

### Recipes (Συνταγές)
Το κεντρικό collection του project.

| Πεδίο | Τύπος | Περιγραφή |
|---|---|---|
| title | Text | Τίτλος συνταγής |
| recipeCategory | Select | Ορεκτικά / Κυρίως Πιάτα / Θαλασσινά / Γλυκά / Πίτες / Σαλάτες |
| prepTime / cookTime | Number | Χρόνος προετοιμασίας / μαγειρέματος (λεπτά) |
| servings | Number | Μερίδες |
| tags | Multi-select | Vegan / Vegetarian / Gluten-Free / Keto |
| allergens | Multi-select | Ξηροί καρποί / Γλουτένη / Λακτόζη / Αυγά / Ψάρι |
| location | Relationship | Σύνδεση με Locations |
| image | Upload | Φωτογραφία συνταγής |
| description / ingredients / instructions / tips | Rich Text | Lexical editor |
| seo | Group | metaTitle + metaDescription |

### Locations (Τοποθεσίες)
Ιεραρχική γεωγραφική δομή 3 επιπέδων.

| Επίπεδο | Παράδειγμα |
|---|---|
| Γεωγραφικό Διαμέρισμα | Κρήτη |
| Νομός | Χανιά |
| Πόλη / Χωριό | Σφακιά |

Περιλαμβάνει `geoJSON` για rendering στον χάρτη και `parent` για την ιεραρχία.

### Pages (Σελίδες)
Page builder με blocks:

- `hero` — hero section με τίτλο, υπότιτλο, background image
- `videoBlock` — ενσωμάτωση YouTube ή αρχείου βίντεο
- `mapBlock` — διαδραστικός χάρτης
- `recipesBlock` — δυναμικό grid συνταγών
- `storyBlock` — αφηγηματικό τμήμα με εικόνα (αριστερά/δεξιά)
- `featuresBlock` — feature boxes με εικονίδια
- `ctaBlock` — call-to-action section
- `quoteBlock` — παράθεση / testimonial

### Categories
Απλό lookup collection: `name`, `slug`.

### Media
Uploads (εικόνες, βίντεο) με υποχρεωτικό `alt` text.

### Users
Authentication collection για το admin dashboard.

---

## Globals

### HeaderMenu
Επεξεργάσιμο μενού πλοήγησης αποθηκευμένο στο Payload. Επιτρέπει αλλαγές στο header χωρίς deployment.

---

## Routes

### Frontend
| Route | Περιγραφή |
|---|---|
| `/` | Αρχική σελίδα — τελευταίες συνταγές |
| `/recipes/[id]` | Σελίδα συνταγής — λεπτομέρειες, υλικά, οδηγίες |
| `/regions` | Όλες οι περιοχές — cinematic card grid |
| `/regions/[id]` | Περιοχή — εμφανίζει συνταγές από την τοποθεσία |
| `/[slug]` | Δυναμικές σελίδες από τον page builder |

### Admin
| Route | Περιγραφή |
|---|---|
| `/admin` | Payload CMS dashboard |

### API
| Endpoint | Περιγραφή |
|---|---|
| `GET /api/recipe-search?q=` | Αναζήτηση συνταγών (έως 8 αποτελέσματα) |
| `GET/POST /api/recipes` | REST CRUD για συνταγές |
| `GET/POST /api/locations` | REST CRUD για τοποθεσίες |
| `GET/POST /api/pages` | REST CRUD για σελίδες |
| `/api/graphql` | GraphQL endpoint |
| `/api/graphql-playground` | GraphQL IDE |

---

## Βασικά Components

| Component | Ρόλος |
|---|---|
| `Navbar` | Fixed header, logo, μενού, search toggle, mobile menu |
| `Footer` | Πληροφορίες, links, social media, marquee animation |
| `Hero` | Full-screen hero με animated text |
| `MapSection` | Διαδραστικός SVG χάρτης με GeoJSON δεδομένα |
| `RecipeCard` | Κάρτα προεπισκόπησης συνταγής |
| `RecipeGridBlock` | Dynamic grid renderer για συνταγές |
| `SearchOverlay` | Full-screen search με debounced API queries |
| `CategoryFilters` | Φιλτράρισμα συνταγών ανά κατηγορία |
| `RichText` | Renderer για Lexical rich text |
| `ShareButton` | Social sharing |
| `Blocks` / `ExtraBlocks` | Routers για page builder blocks |

---

## Design System

- **Θέμα:** Dark (#050505 background)
- **Accent color:** Orange (#f97316 / Tailwind orange-500)
- **Typography:** Tailwind Typography plugin (`prose`)
- **Animations:** Framer Motion για smooth transitions, marquee animation στο Footer
- **Images:** AVIF + WebP με Next.js Image optimization, target domain: `elladastopiato.gr`
- **Responsive:** Mobile-first με Tailwind breakpoints

---

## Environment Variables (απαιτούνται)

```env
DATABASE_URI=        # PostgreSQL connection string
PAYLOAD_SECRET=      # Secret key για Payload CMS
NEXT_PUBLIC_SERVER_URL=  # Public URL της εφαρμογής
```

---

## Testing

| Εργαλείο | Χρήση |
|---|---|
| Vitest | Unit tests |
| Playwright | End-to-end tests |

---

## Deployment Target

Domain: **elladastopiato.gr**
