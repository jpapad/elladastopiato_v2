# elladastopiato.gr — οδηγίες για Claude

> Το `AGENTS.md` αφορά μόνο τον κώδικα Payload/Next.js στο `src/`. Η τρέχουσα δουλειά
> είναι το WordPress site και το plugin `ellada-map` — ισχύουν οι κανόνες παρακάτω.

## Γενικά
- Χρήστης: Giannis. **Απαντάς πάντα στα ελληνικά.**
- Site: elladastopiato.gr («Ελλάδα στο Πιάτο») — WordPress σε Hostinger (LiteSpeed),
  Elementor 4 Pro, ACF Pro, theme Hello Elementor.
- Κάθε δουλειά στο site γίνεται μέσω του **Novamira connector** (abilities
  `novamira/execute-php`, `novamira/create-upload-link`, `novamira/create-admin-access-link`).
  Αν λείπει, ζήτα από τον χρήστη να τον ενεργοποιήσει — χωρίς αυτόν δεν γίνεται δουλειά στο site.

## Git / branch
- **Όλη η δουλειά γίνεται στο branch `feat/wordpress-map`** — από οποιαδήποτε συσκευή ή
  συνεδρία (Mac, web, κινητό, cloud). Ο χρήστης έχει δώσει ρητή άδεια για commit & push εκεί,
  ακόμα κι αν η συνεδρία έχει οριστεί σε άλλο `claude/...` branch.
- Στην αρχή: `git fetch origin feat/wordpress-map && git checkout feat/wordpress-map && git pull`.
- Μετά από κάθε ολοκληρωμένη αλλαγή: commit (με γραμμή `Co-Authored-By`) και
  `git push origin feat/wordpress-map` — οι cloud συνεδρίες είναι προσωρινές.
- Ποτέ commit/push αρχείων `.env`.

## Cloud περιβάλλον
- Οι cloud συνεδρίες τρέχουν στο προσωπικό περιβάλλον «elladastopiato_v2»: επιτρέπει πρόσβαση στο
  `elladastopiato.gr` (upload zip, έλεγχος με Playwright) και κάνει `npm ci` στην αρχή.

## Κανόνες ασφαλείας για το live site
- **Ρώτα πριν από κάθε αλλαγή που επηρεάζει το live site.** Πρώτα drafts.
- **ΠΟΤΕ αρχεία `.php` μέσα στο `wp-content/novamira-sandbox/`** (εκτελεί αυτόματα PHP).
- **Μην ενημερώνεις τα plugins Elementor / Elementor Pro.**

## Plugin `ellada-map`
- Πηγή: `wp-embed/wordpress-plugin/ellada-map` (live έκδοση: δες header του plugin / τελευταίο commit).
- Build: `npm run wp-map:build` → `wp-embed/dist/ellada-map.zip`.
- Deploy:
  1. Upload του zip στο `wp-content/novamira-sandbox/<name>.zip` (`create-upload-link` με `path`).
  2. Έλεγχος sha256 + PHP `token_get_all` check + **διπλές συναρτήσεις** (`^function name` σε όλα τα .php του zip), backup της τρέχουσας έκδοσης στο
     `wp-content/ellada-map-backups/ellada-map-backup-<παλιά έκδοση>`.
  3. `require` των `wp-admin/includes/file.php`, `plugin.php`, `class-wp-upgrader.php` και
     `Plugin_Upgrader::install($zip, ['overwrite_package' => true])`.
  4. `opcache_reset()`, `do_action('litespeed_purge_all')`, `unlink` του zip.
  5. Loopback GET της αρχικής· αν δεν είναι 200 → επαναφορά του backup αμέσως.

## Τουριστικός οδηγός (LIVE)
- Feature flags: option `ellada_features` → `guide=1`, `recipe_tools=1`.
- 410 σελίδες τοποθεσιών (11 περιφέρειες, 73 νομοί, 326 δήμοι). IDs στο option
  `ellada_guide_published_ids` (για rollback).
- Hub: σελίδα 619 (`/odigos/`). Template 118 με shortcode widgets `loc-crumbs`, `loc-guidebox`.
- Σελίδες χωρίς κείμενο έχουν `_ellada_thin=1` → noindex, εκτός sitemap.
- Γράψιμο κειμένων: ACF πεδία `guide_intro` (παράγραφοι χωρισμένες με κενή γραμμή) και
  `why_go` (ένας λόγος ανά γραμμή) → `ellada_guide_mark_thin($id)` → delete transient
  `ellada_guide_pages` → LiteSpeed purge.
- Workflow κειμένων: γράφεις drafts, τα δείχνεις στον χρήστη στο chat, εφαρμόζεις **μόνο μετά
  από έγκριση**. Επιβεβαίωσε σε ποιο δήμο ανήκει κάθε οικισμός μέσω `ellada_guide_places()`
  (γραμμές: id, name, name_en, type, population, year, municipality, prefecture, region, lat, lon).

## Πρόοδος
- Κείμενα live: Κρήτη (140), Χανιά 292, Ρέθυμνο 291, Ηράκλειο 289, Λασίθι 290 και οι 24 δήμοι
  της Κρήτης — 32 URLs τοποθεσιών στο sitemap.
- ellada-map **1.22.0** live (backups έως 1.21.1). 1.22.0: `includes/seo-titles.php` — flag **`seo_titles`** (ΚΛΕΙΣΤΟ, περιμένει έγκριση):
  τίτλοι Google ανά τύπο (`ellada_seo_title_for`, ≤65 χαρ., «συνταγή από την <περιφέρεια>» μέσω `ellada_region_from`), περιγραφές για
  Χάρτη/Επικοινωνία/κατηγορίες/τόπους χωρίς κείμενο (filter `ellada_meta_data` στο seo.php), canonical σε κατηγορίες, H1 (screen-reader) στον Χάρτη.
  Εκκρεμεί: δεύτερο H1 αρχικής (`h3c-title` στο 163) → h2.
- ellada-map 1.21.1: 1.21.x: `includes/seo-app.php` — μενού **SEO** (admin): ανάλυση σελίδων του sitemap
  (loopback, βήματα, option `ellada_seo_pages`/`_last`/`_history`, cron `ellada_seo_daily` 04:10 + `ellada_seo_continue`), Ευκαιρίες
  (γιορτές μέσω `ellada_orthodox_easter()` του season.php, πιάτα χωρίς συνταγή, τόποι χωρίς οδηγό, ορφανές), Τεχνικά (404 log χωρίς IP:
  `ellada_404_log`), Google (Search Console API με service account: `define('ELLADA_GSC_KEY_FILE', …)` στο wp-config — ΟΧΙ ακόμα),
  εβδομαδιαία αναφορά `ellada_seo_weekly` (Δευτέρα 08:20, option `ellada_seo_settings`). Πρώτος βαθμός: 94/100 (45 σελίδες).
  **Μάθημα:** η 1.21.0 είχε διπλή `ellada_orthodox_easter` → 500 σε όλο το site (ο χρήστης έσβησε το require από File Manager).
  Deploy πλέον: έλεγχος διπλών `^function` σε όλο το zip + loopback GET της αρχικής μετά την εγκατάσταση και αυτόματο rollback αν ≠ 200.
- ellada-map 1.20.0: 1.20.0: `includes/lost.php` — «Οι γεύσεις που χάνονται»: CPT `ellada_testimony`
  (Μαρτυρίες, slug `martyries`, local ACF: testimony, person, age, year, place, place_text, recipes, recorded_by, **consent** — χωρίς consent
  δεν εμφανίζεται πουθενά), local ACF πλαϊνό σε recipe/point: `endangered`, `endangered_note`. Flag **`lost`** (ΚΛΕΙΣΤΟ): σήμα
  «Γεύση που χάνεται» στη διαδρομή (μέσα στο `.ellada-kinds`), σημείωση + «Μαρτυρίες» πριν την Εκτέλεση, σελίδα 659
  «Οι γεύσεις που χάνονται» (`/oi-geuseis-pou-xanontai/`, draft, `[ellada_lost]`). Μελλοντικά: ηχητικό/βίντεο (click-to-load).
- ellada-map 1.19.1: **Κεντρικό μήνυμα** (1.19.1): «Ανακαλύπτουμε την Ελλάδα μέσα από τις γεύσεις, τις παραδόσεις
  και τους ανθρώπους της» — blogdescription (παλιό στο option `ellada_blogdescription_backup_2026_11_17`), footer 624 (`footer-tagline`,
  backup `_elementor_data_backup_2026_11_17`), αρχική 163 `home2-lead` (backup `_elementor_data_backup_2026_11_17b`), meta description αρχικής (`seo.php`).
  1.19.0: `includes/trust.php` — ACF συνταγής `recipe_kind` (traditional|variation|modern),
  `sources` («Τίτλος | URL» ανά γραμμή), `verified_by`, `verified_on`· ACF τόπου `sources`. Flag **`trust`** ΑΝΟΙΧΤΟ: σήμα είδους
  δίπλα στη διαδρομή, κουτί «Σχετικά με τη συνταγή» μετά τα βήματα, «Πηγές» + «Βρήκατε λάθος; Πείτε μας» σε τόπους με κείμενο →
  Επικοινωνία `?thema=lathos&selida=<id>` (το μήνυμα γεμίζει με τίτλο + URL)· schema `citation`. «Τι λείπει»: Είδος/Πηγές.
  `best_time_to_visit` είναι SELECT (all_year|spring|summer|autumn|winter) — όχι ελεύθερο κείμενο.
- ellada-map 1.18.2: 1.18.x: `includes/recipes-v3.php` — διαχειριστικό (ενεργό): taxonomy `recipe_occasion`
  (περιστάσεις· συλλογές γεμίζουν από το «Θέμα»: christmas→xristougenna+protoxronia κ.λπ., filter `ellada_recipe_query_args`),
  Συνταγές → «Συνταγές που λείπουν» (πιάτα `dishes` χωρίς συνταγή, «Νέα συνταγή» με `?ellada_title=&ellada_location=`),
  «Ολόκληρη συνταγή» στη Γρήγορη συμπλήρωση (`ellada_quick_parse_full`), «Νηστίσιμη;» στο «Τι λείπει» (`ellada_fasting_hint`),
  μεγέθη ellada-1x1/4x3/16x9. Flag **`recipe_v3`** ΑΝΟΙΧΤΟ: «Το έφτιαξα!» (REST `ellada/v1/made/<id>`, meta `_ellada_made`),
  ♥ Αγαπημένα (localStorage `ellada-favs`, REST `ellada/v1/cards`), σελίδα 649 «Οι συνταγές μου» (`/oi-syntages-mou/`, δημοσιευμένη, `[ellada_my_recipes]`)· απόρρητο (3) ενημερωμένο (backup meta `_ellada_content_backup_2026_11_17`),
  schema image σε αναλογίες, αυτόματη OG εικόνα (GD, `uploads/ellada-og/`, fonts στο `public/fonts/`).
- ellada-map 1.17.1: 1.17.x: διαχειριστικό (`includes/admin-ux.php`): απλή οθόνη αντί για
  Gutenberg σε recipe/location/point/collection (πεδία ACF κάτω από τον τίτλο), χωρίς κουτί κειμένου στις συνταγές,
  οδηγία τίτλου ανά τύπο (priority 99, μετά το ACF), παραδείγματα στα άδεια πεδία, κουτί «Τι λείπει» στο πλάι.
- ellada-map 1.16.1: **`guide_v3` ΑΝΟΙΧΤΟ**. 1.16.x: σελίδα τόπου v3 πίσω από flag **`guide_v3`**, `includes/guide-v3.php`: hero = περιφέρεια σε μεγέθυνση με νομούς-links, μενού πλοήγησης,
  «Τι να φάτε» (νέο ACF `dishes`: «Πιάτο | φράση», link σε συνταγή), «Τοπικά προϊόντα» (`local_products`: «Όνομα | σημείωση»),
  «Πότε & πώς» (`best_time_to_visit`, νέο ACF `food_calendar`: «Εποχή | τι», `how_to_get_there`, `travel_tips` ανά γραμμή),
  νομοί σε κάρτες, CTA συνταγών, χάρτης χωρίς κάρτα (`data-hide-card`), TouristDestination. Πρόχειρα κείμενα:
  meta `_preview_<πεδίο>` (φαίνονται μόνο σε preview). Κρήτη (140) και νομοί 289/290/291/292: **live** dishes/local_products/best_time/
  food_calendar/how_to_get_there/travel_tips (παλιά: meta `_ellada_guide_backup_2026_11_17`).
- ellada-map 1.15.2: **`guide_v2` ΑΝΟΙΧΤΟ**· meta description τόπων = αρχή του `guide_intro`.
  1.15.x: οδηγός v2 πίσω από flag **`guide_v2`**, `includes/guide-v2.php`: `[ellada_guide_hub2]` (νέο /odigos/: hero + «Πού πάτε;» μέσω
  `ellada/v1/search` + χάρτης-links, «Ξεκινήστε από εδώ», 13 περιφέρειες σε details, CTA), `[ellada_guide_hero]` (widget
  `loc-ghero` στο template 118· backup meta `_elementor_data_backup_2026_11_17`), «… : ο οδηγός γράφεται» σε σελίδες
  χωρίς `guide_intro`, BreadcrumbList, `public/prefectures-mini.json`. **Νέο /odigos/ live** (619 με `[ellada_guide_hub2]`, template
  elementor_header_footer· backup meta `_ellada_content_backup_2026_11_17`).
- ellada-map 1.14.1: 1.14.x: αρχική — `includes/home-v2.php`: `[ellada_home_guide]`
  (περιφέρειες με `guide_intro`, μικρός χάρτης + αριθμοί οδηγού), `[ellada_home_submit]` (→ Επικοινωνία ?thema=),
  WebSite+SearchAction+Organization στην αρχική· μετρητές χάρτη = τόποι οδηγού όταν συνταγές < 20 (`home.php`)·
  χάρτης: πιο φωτεινά χρώματα, νομοί/δήμοι φορτώνουν μόνο όταν χρειαστούν (`src/components/map/useMapData.ts`).
  Συνταγή εβδομάδας = 141 (option `ellada_featured_recipe`). **Νέα αρχική live** (163· backup meta
  `_elementor_data_backup_2026_11_17`). Μενού «Κεντρικό μενού»: Αρχική · Συνταγές · **Οδηγός** (/odigos/) · Χάρτης.
- ellada-map 1.13.1: **`recipe_v2` ΑΝΟΙΧΤΟ** (ellada_features).
  1.13.x: σελίδα συνταγής v2 πίσω από flag **`recipe_v2`**, `includes/recipe-v2.php`:
  διαδρομή `[ellada_recipe_crumbs]` (widget `recipe-crumbs` στο template 109, κενό όσο είναι κλειστό) + BreadcrumbList,
  «Ο τόπος της συνταγής» με στατικό χάρτη (`public/regions-mini.json`), ACF `story` και `variations` (group_ellada_recipe),
  «Μαγειρεύω τώρα» (Wake Lock) και «Λίστα για ψώνια» στο `public/recipe.js`, φωτογραφία πρώτη στο κινητό, suitableForDiet.
  Backup template 109: meta `_elementor_data_backup_2026_11_17`. Κείμενα 141/142/143 ξαναγραμμένα (δικό μας ύφος,
  χωρίς όνομα σεφ)· παλιά στο meta `_ellada_text_backup_2026_11_17` κάθε συνταγής.
- ellada-map 1.12.0: 1.12.0: σελίδα Συνταγές — `includes/recipes-archive.php`:
  `[ellada_archive_intro]`, `[ellada_recipe_filters]` (κατηγορία + `?perioxi=` `?diatrofi=` `?xronos=30|60`, noindex όταν
  φιλτράρεται), `[ellada_recipe_discover min=12]`, meta `_ellada_total_time`. Κάρτες: σήμα διατροφής, δυσκολία,
  placeholder με το πιατάκι (`public/plate-mark.svg`). Προεπισκόπηση template: `?ellada_tpl=<id>` για editors
  (το template δημοσιευμένο **χωρίς conditions** + meta `_ellada_preview_of`). **Νέα σελίδα Συνταγές live** στο template 122
  (παλιό περιεχόμενο στο meta `_elementor_data_backup_2026_11_16` της 122).
- ellada-map 1.11.12: 1.11.11–12: `[ellada_contact_aside]` (`includes/contact.php`,
  κάρτες θεμάτων + email, `?thema=syntagi|topos|lathos|synergasia` επιλέγει το πεδίο `topic` της φόρμας)·
  απενεργοποιημένο το wp-emoji (φόρτωνε από s.w.org) στο `includes/privacy.php`.
- **Νέα σελίδα Επικοινωνία live** (137): θέμα (`topic`, μπαίνει στο subject του email), πορτοκαλί κουμπί,
  κάρτες, χρόνος απάντησης. Παλιό περιεχόμενο στο meta `_elementor_data_backup_2026_11_16` της 137.
  Email φόρμας: plain text (θέμα/όνομα/email/μήνυμα), metadata μόνο date/time/page_url — χωρίς IP, user agent,
  «Powered by Elementor» (spam + απόρρητο). From: info@ «Ελλάδα στο Πιάτο».
- ellada-map 1.11.10: **SMTP ενεργό και επιβεβαιωμένο** (τα email φτάνουν στα
  εισερχόμενα). 1.11.10: νέος κωδικός ενεργοποιεί αυτόματα το SMTP. 1.11.9: SMTP μέσω smtp.hostinger.com:465 ως info@
  (`includes/mail.php`, σελίδα Ρυθμίσεις → Email του site, option `ellada_smtp`, ή σταθερές ELLADA_SMTP_* στο wp-config).
  Χωρίς SMTP τα email πάνε στα spam (αποστολή από web server ως wordpress@, χωρίς DKIM).
- ellada-map 1.11.8: 1.11.8: μηνύματα φόρμας (Elementor `wp_e_submissions`)
  χωρίς IP/user agent και διαγραφή μετά από 12 μήνες — cron `ellada_submissions_cleanup` (daily, `includes/privacy.php`). 1.11.4–1.11.6: shortcodes footer
  `[ellada_footer_links]`, `[ellada_footer_legal]` (`includes/footer.php`) — editors βλέπουν και drafts.
- **Footer live** = Elementor template **624** (`include/general`). Το παλιό **71** είναι χωρίς conditions
  (rollback: conditions πίσω στο 71, άδειες στο 624, regenerate conditions cache). Νομικά **δημοσιευμένα**: σελίδα 3 «Απόρρητο & cookies» (`/politiki-aporritou/`),
  623 «Όροι χρήσης» (`/oroi-xrisis/`), 137 «Επικοινωνία» (φόρμα → info@elladastopiato.gr).
  Υπεύθυνος επεξεργασίας στο απόρρητο: Ιωάννης Παπαδημητρίου.
- ellada-map 1.11.3: ο χάρτης δείχνει ονόματα σε ονομαστική (πίνακας νομών στο
  `src/components/map/geo.ts`) και φορτώνει μόνο τα απαραίτητα ACF πεδία. Backup 1.11.2 υπάρχει.

## Εκκρεμότητες / επόμενα
- «Οι γεύσεις που χάνονται»: έγκριση για άνοιγμα + δημοσίευση 659 + μενού + πρόταση στο απόρρητο για μαρτυρίες. Πηγές/είδος για 141–143 όταν τα δώσει ο χρήστης.
- Κείμενα για άλλη περιφέρεια (η Κρήτη και οι 4 νομοί της είναι πλήρεις).
- Συνταγές από το πλάνο περιεχομένου (3 ανά περιφέρεια, πρώτα Χριστούγεννα/Σαρακοστή) — λίστα «Συνταγές που λείπουν» στο admin.
- Φωτογραφία εξωφύλλου (`cover_image`) για Κρήτη/νομούς όταν ο χρήστης δώσει δικές του.
- Χριστουγεννιάτικη συλλογή 197 χρειάζεται συνταγές (πρόταση: μελομακάρονα, κουραμπιέδες, χριστόψωμο,
  λαχανοντολμάδες, γαλοπούλα γεμιστή, βασιλόπιτα — ο χρήστης δεν έχει αποφασίσει ακόμα).
- Συνταγές 141, 142 χρειάζονται φωτογραφίες. Περιοχές 141 (Δυτική Ελλάδα) / 142 (Δράμα) να επιβεβαιωθούν·
  ιστορία για 142 αν ο χρήστης έχει.
- Νέες συνταγές από τον χρήστη: ξαναγράφονται στο δικό μας ύφος (όχι αντιγραφή κειμένου/φωτογραφιών τρίτων).
- Αργότερα: σελίδες οικισμών, διαδρομές, πανηγύρια, αγγλική έκδοση.
- Social links στο footer όταν ο χρήστης δώσει λογαριασμούς.
- Search Console: ιδιοκτησία επαληθευμένη + sitemap υποβλήθηκε (`https://elladastopiato.gr/wp-sitemap.xml`). **Εκκρεμεί** (ο χρήστης
  το άφησε για αργότερα): upload του service-account JSON ως `domains/elladastopiato.gr/gsc-key.json` (έξω από public_html) → εγώ
  προσθέτω `define( 'ELLADA_GSC_KEY_FILE', '/home/u543862105/domains/elladastopiato.gr/gsc-key.json' );` στο wp-config (με backup, μετά από έγκριση).
- Τίτλοι Google (flag `seo_titles`): περιμένουν έγκριση για άνοιγμα + δεύτερο H1 αρχικής → h2.
- Σε αναμονή: Reach/newsletter.

> Ενημέρωνε τις ενότητες «Πρόοδος» και «Εκκρεμότητες» όταν κάτι ολοκληρώνεται.
