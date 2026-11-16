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
  2. Έλεγχος sha256 + PHP `token_get_all` check, backup της τρέχουσας έκδοσης στο
     `wp-content/ellada-map-backups/ellada-map-backup-<παλιά έκδοση>`.
  3. `require` των `wp-admin/includes/file.php`, `plugin.php`, `class-wp-upgrader.php` και
     `Plugin_Upgrader::install($zip, ['overwrite_package' => true])`.
  4. `opcache_reset()`, `do_action('litespeed_purge_all')`, `unlink` του zip.

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
- ellada-map **1.12.0** live (backups έως 1.11.12). 1.12.0: σελίδα Συνταγές — `includes/recipes-archive.php`:
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
- Κείμενα για άλλη περιφέρεια.
- Προσθήκη `/odigos/` στο μενού «Κεντρικό μενού» (id 3).
- Χριστουγεννιάτικη συλλογή 197 χρειάζεται συνταγές (πρόταση: μελομακάρονα, κουραμπιέδες, χριστόψωμο,
  λαχανοντολμάδες, γαλοπούλα γεμιστή, βασιλόπιτα — ο χρήστης δεν έχει αποφασίσει ακόμα).
- Συνταγές 141, 142 χρειάζονται φωτογραφίες.
- Αργότερα: σελίδες οικισμών, διαδρομές, πανηγύρια, αγγλική έκδοση.
- Social links στο footer όταν ο χρήστης δώσει λογαριασμούς.
- Σε αναμονή: Search Console, Reach/newsletter.

> Ενημέρωνε τις ενότητες «Πρόοδος» και «Εκκρεμότητες» όταν κάτι ολοκληρώνεται.
