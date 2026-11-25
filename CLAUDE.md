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
- **ΝΕΑ ΠΑΛΕΤΑ LIVE (21/11)** — πετρόλ **#174C4B**, χρυσό **#D29A48**, φόντο **#F6F2E9**, φασκόμηλο **#D7DFD8**, ανθρακί **#343B39**
  (+ #58615E απαλό κείμενο, #DDB06C hover χρυσού, #123C3B σκούρο hover, #235B59/#2C6866 γραμμές σε σκούρο, #F9F6EF/#FAF8F2 ανοιχτές επιφάνειες).
  Αλλαγές: κώδικας (front.css, plate-mark.svg, PHP, χάρτης `GreeceMap.tsx` με CONTENT_FILL accent(0.72)) στην 1.24.0· Elementor kit 9
  `_elementor_page_settings` (backup meta `_ellada_palette_backup_2026_11_21`) και **`_elementor_global_variables`** (οι V4 μεταβλητές
  --Accent/--Dark/--Ink…· backup `_ellada_gvars_backup_2026_11_21`)· `_elementor_data` των 70/176/624/163/137 (backup `_ellada_palette_backup_2026_11_21`).
  **Λογότυπο live: «ΣΤΟ ΠΙΑΤΟ» + Κρήτη σε #D97745** (κεραμιδί, μόνο στο λογότυπο): 667 `ellada-brand/ellada-logo-header-d977@2x.png`
  (στα e-image των 70/176/624), site icon 668, OG 669. Προηγούμενα: χρυσό 664/665/666, αρχικό πορτοκαλί 157/159/160
  (option `ellada_brand_backup_2026_11_21`). Ανακαλύφθηκε με ξαναχρωματισμό των PNG (PIL)· δεν υπάρχει SVG πηγή. Το ροζ border του «Βρείτε συνταγή» διορθώθηκε. Το `palette-preview.php` μένει (μόνο editors).
- ellada-map **1.41.8** live (backups έως 1.41.7). 1.41.8: το λογότυπο εκτύπωσης φορτώνει ήδη στην οθόνη (κρυμμένο εκτός οθόνης, όχι display:none) — αλλιώς ο browser δεν προλάβαινε και τύπωνε κενό. 1.41.x: **εκτύπωση συνταγής = «κάρτα συνταγής»** (recipe-v6.php + τέλος front.css, `body.ellada-rp`) πίσω από flag
  **`recipe_print`** (**ΑΝΟΙΧΤΟ**, live 25/11· προβολή `?ellada_demo=print6`· χρειάζεται `recipe_v6`): κεφαλίδα με **λογότυπο** (`public/logo-print.svg` = το διανυσματικό
  οριζόντιο με «ΕΛΛΑΔΑ» σε πετρόλ #174C4B αντί για κρεμ) + διεύθυνση, χρυσή γραμμή, τίτλος πετρόλ + μικρή φωτογραφία δεξιά, Χρόνος/Μερίδες/Δυσκολία/kcal,
  υλικά (κουτάκια) | βήματα (χρυσοί κύκλοι), Μυστικά | Παραλλαγές σε λεπτά πλαίσια, Ιστορία, «1 / N». Κρυμμένα: κουμπιά, μπάρες, χρονόμετρα, τόπος/χάρτης,
  «Δείτε ακόμα», footer, άδειες σημειώσεις. Ντάκος/Πλεξούδες 1 σελίδα, Μακαρόνια 2.
- ellada-map 1.40.4 (backups έως 1.40.3). 1.40.4: κουτάκια v6 — αριστερά τα Μυστικά, δεξιά οι Παραλλαγές (και στον καμβά).
- ellada-map 1.40.3 (backups έως 1.40.2). 1.40.3: στο v6 τα κουμπιά της κάρτας «με μια ματιά» (✓/♡/Συλλογή/Εκτύπωση/Κοινοποίηση) γίνονται στρογγυλά εικονίδια με «ακορντεόν» λέξης στο hover/focus (`.r6-acc`, όπως το μενού· κρατούν τα δικά τους σενάρια)· οι θερμίδες κρύβονται από τα υλικά (`.ellada-kcal`) αφού είναι στην κάρτα.
- ellada-map 1.40.2 (backups έως 1.40.1). 1.40.x: `includes/recipe-v6.php` — **σελίδα συνταγής v6 = «Γ · Μιξ» από Claude Design**
  (καμβάς: artifact TjQNi4Lt2iPiQSJrU6QRJE, πίνακες Α/Β/Γ) πίσω από flag **`recipe_v6`** (**ΑΝΟΙΧΤΟ**, live 25/11· προβολή `?ellada_demo=recipe6`). Template 109 ΔΕΝ αλλάζει:
  JS (template `#r6-tpl`) βάζει νέο hero (φωτογραφία σε όλο το πλάτος + τίτλος/ετικέτες + λευκή κάρτα «με μια ματιά»: χρόνος/μερίδες/δυσκολία/kcal + ενέργειες
  `.ellada-ractions` μεταφερμένες + «✓ Την έφτιαξα;»), αφαιρεί το `[data-id=recipe-hero]`, πετρόλ μπάρα μαγειρέματος (το `[data-ellada-cook]` μεταφέρεται εκεί,
  «Βήμα N από M» + πρόοδος από τα `button.ellada-steps__n`, «Διάβασέ μου» → `.ellada-ra__start`), βήματα σε κάρτες (`li.is-next` «Τώρα», `li.is-done` σβηστά),
  σημειώσεις κάτω από τα υλικά (#recipe-aside), το κουτί `.ellada-made` κρυφό → ερώτηση «Καλή όρεξη! Το φτιάξατε;» όταν τελειώσουν τα βήματα/«Τέλος μαγειρέματος»,
  κουτάκια `.r6-bento` σε όλο το πλάτος (Παραλλαγές με εικονίδια ανά λέξη + Μυστικά σε αριθμημένες προτάσεις, από κάτω Ιστορία με πρώτο γράμμα + Τόπος),
  κινητό: κολλημένη λωρίδα «Υλικά · Βήματα · Ιστορία» (`.r6-jump`). Shortcode filters prio 99: ιστορία έξω από τα βήματα, extras → bento, αλλεργιογόνα στα υλικά.
  Χωρίς φωτογραφία: πετρόλ hero με πιατάκι. axe 0 (υπολογιστής/κινητό). Στη δοκιμή ο μετρητής «έφτιαξα» του 143 επανήλθε (1).
- ellada-map 1.39.1 (backups έως 1.39.0). 1.39.x: `includes/recipe-v5.php` — **συνταγή v5** (από τον έλεγχο της κριτικής για τον Ντάκο, PDF στάλθηκε)
  πίσω από flag **`recipe_v5`** (**ΑΝΟΙΧΤΟ**, live 24/11· προβολή `?ellada_demo=recipe5`): (1) το κρυμμένο footer Elementor δεν τυπώνεται (ob_start/ob_end_clean στα
  `elementor/theme/before|after_do_footer` priority 1, μόνο όταν footer_v2· όλο το site), (2) «Την έφτιαξαν» από 5+ (filter `ellada_made_min`, `window.elladaMadeMin`),
  (3) ⏱ χρονόμετρο: «N λεπτά/ώρα/δευτερόλεπτα» στα βήματα → `.ellada-tmr-btn` (έως 3, ήχος WebAudio + δόνηση, παύση/↻/×), (4) λίστα για ψώνια μόνο με όσα
  δεν είναι τσεκαρισμένα (`window.elladaShopMissing`· recipe.js + account-v2), (5) «Δείτε ακόμα»: περιφέρεια → κατηγορία → πρόσφατες (blocks.php),
  (6) γλωσσάρι `ellada_glossary()` (μιτάτο, κουκουβάγια, ξινομυζήθρα, παξιμάδι κριθαριού, ανθότυρο, γραβιέρα, μπούκοβο, κονκασέ· πρώτη εμφάνιση σε ιστορία/βήματα/
  μυστικά/παραλλαγές, ένα αναδυόμενο `#ellada-gl-pop` — όχι στα υλικά, για να μη μπαίνει στη λίστα/🔊), (7) κινητό: μπάρα `.ellada-cookbar` «Μαγειρεύω τώρα»
  όσο φαίνεται η εκτέλεση. + ACF `calories` (ομάδα «Διατροφή», πλάι) → «≈ N kcal ανά μερίδα (εκτίμηση)» στο τέλος των υλικών + schema `nutrition`.
  Τιμές: 141=720, 142=630, 143=570 kcal (εκτιμήσεις από τα υλικά). axe 0. **Έγινε:** τυρί ντάκου (143, γραμμή 3) «μυζήθρα | ή φέτα» → «ξινομυζήθρα | ή μαλακή
  μυζήθρα ή φέτα» (backup meta `_ellada_cheese_backup_2026_11_24`).
- ellada-map 1.38.1 (backups έως 1.38.0). 1.38.x: `includes/account-v3.php` — **σελίδα λογαριασμού v3** πίσω από flag **`acct_v3`** (**ΑΝΟΙΧΤΟ**, live 24/11·
  προβολή `?ellada_demo=account3`, μόνο συνδεδεμένοι· `ellada_acct3_html()` καλείται από το shortcode του account.php): πετρόλ κεφαλίδα με «Αποσύνδεση» πάνω δεξιά
  και 4 αριθμούς-κουμπιά (♥/✓/να αγοράσω/συλλογές → καρτέλα)· «Πρώτα βήματα» (όνομα/♥/συλλογή/πρόταση, «Απόκρυψη» → user meta `ellada_onb_hide`, REST
  `ellada/v1/me/onb-hide`)· «Το ταξίδι μου με το πιρούνι» (περιφέρειες από τις «Τις έφτιαξα», μπάρα + κουμπάκια, χωρίς χάρτη· κινητό: λωρίδα, οι μαγειρεμένες
  πρώτες)· καρτέλες ARIA (#agapimenes/#eftiaxa/#psonia/#syllogi/#protaseis/#rythmiseis, βελάκια)· συνταγές σε γραμμές `.ellada-arow` με «Αφαίρεση» (μέσω
  localStorage → συγχρονισμός)· άδειες καρτέλες με 3 προτάσεις και «♡ Αγαπημένη». Δοκιμή end-to-end πέτυχε.
- ellada-map 1.37.0 (backups έως 1.36.1). 1.37.0: κουμπί λογαριασμού **δίπλα στον φακό** αντί για στοιχείο μενού, πίσω από flag **`acct_header`**
  (**ΑΝΟΙΧΤΟ**, live 24/11· προβολή `?ellada_demo=acctbtn`· `ellada_acct_header()` στο account.php): `<template>` + JS βάζει `a.ellada-acct-btn` πριν από το widget του
  `.ellada-search-btn` (η κεφαλίδα Elementor μπορεί να είναι cached), ίδιο τετράγωνο 46px, 👤 ή κύκλος με αρχικό, tooltip στο hover· κινητό: λογότυπο · 👤 · 🔍 · ☰
  (42px, χωρίς πλαίσιο)· όταν είναι ανοιχτό, το στοιχείο του μενού δεν μπαίνει. CLS 0.
- ellada-map 1.36.1 (backups έως 1.36.0). 1.36.1: email «Δημοσιεύτηκε! — <τίτλος>» στο μέλος όταν δημοσιεύεται συνταγή/σημείο που ήρθε από πρότασή του
  (`transition_post_status` → `_ellada_inbox_id` → `_ellada_user`· μία φορά, meta `_ellada_notified`· στέλνεται στο `shutdown`)· μενού: συνδεδεμένοι βλέπουν κύκλο
  #D97745 με το αρχικό του ονόματος (ή του email) + «Ο λογαριασμός μου» (`.ellada-mi__avatar`· οι αποσυνδεδεμένοι το 👤 «Λογαριασμός»).
- ellada-map 1.36.0 (backups έως 1.35.1). 1.36.0: `includes/account-v2.php` — **λογαριασμός Βήμα 2** πίσω από flag **`accounts_v2`** (**ΑΝΟΙΧΤΟ**, live 24/11·
  προβολή `?ellada_demo=account2`, κρατιέται 1 μέρα με cookie `ellada_demo_acct2`· μόνο για συνδεδεμένους): 📝 «Οι σημειώσεις μου» μετά τα βήματα (user meta
  `ellada_notes`, autosave· σε αποσυνδεδεμένους γραμμή «Συνδεθείτε»), 🛒 «＋ Στη λίστα μου» δίπλα στη «Λίστα για ψώνια» (`ellada_shop`, ≤20 συνταγές, τσεκάρισμα/
  αντιγραφή/άδειασμα στο /logariasmos/#psonia), 📁 «＋ Συλλογή» στη μπάρα ενεργειών (αναδυόμενο, `ellada_lists` ≤20), «Οι προτάσεις μου» (inbox με meta
  `_ellada_user`: Ελήφθη/Το είδαμε/Σε επεξεργασία/Δημοσιεύτηκε). REST `ellada/v1/me/v2`· η εξαγωγή περιλαμβάνει σημειώσεις/λίστα/συλλογές/προτάσεις.
  Επικοινωνία v3: για συνδεδεμένους προσυμπληρωμένο όνομα/email + «να γράψετε το όνομά μου» (syntagi/topos) → `_ellada_credit` στο πρόχειρο →
  «Πρόταση αναγνώστη: …» μετά τα βήματα (χωρίς flag· μόνο αν υπάρχει το meta)· το JS της φόρμας στέλνει X-WP-Nonce. Στη διαγραφή χρήστη σβήνεται το `_ellada_user`.
  Δοκιμή end-to-end πέτυχε (δοκιμαστικά σβήστηκαν).
- ellada-map 1.35.1 (backups έως 1.35.0). 1.35.x: `includes/account.php` — **λογαριασμός επισκέπτη v1** πίσω από flag **`accounts`** (**ΑΝΟΙΧΤΟ**, live 24/11·
  προβολή χωρίς σύνδεση `?ellada_demo=account`): σύνδεση χωρίς κωδικό με σύνδεσμο στο email (20', μία φορά, σελίδα με κουμπί «Σύνδεση» = POST·
  REST `ellada/v1/account/link` με honeypot, υπογεγραμμένο χρόνο ≥3", όριο 3/ώρα ανά email + 60/ώρα συνολικά, ίδια απάντηση για όλους)· ρόλος **`ellada_member`**
  χωρίς δικαιώματα (όχι wp-admin/admin bar, όχι σύνδεση με κωδικό/reset)· ο σύνδεσμος **δεν** συνδέει ποτέ διαχειριστές/συντάκτες· user meta `ellada_favs`,
  `ellada_made_list`, `ellada_member_since`, `ellada_last_seen`· REST `/me` (GET/POST/DELETE με «ΔΙΑΓΡΑΦΗ»), `/me/export`· συγχρονισμός ♥/«Την έφτιαξα» με το
  localStorage σε κάθε σελίδα για συνδεδεμένους (πρώτη φορά ένωση, μετά ο server)· σελίδα **676 «Ο λογαριασμός μου»** (`/logariasmos/`, `[ellada_account]`,
  noindex, εκτός sitemap, χωρίς cache· όσο το flag είναι κλειστό → 302 στην αρχική)· μενού: 👤 «Λογαριασμός» με φίλτρο (`wp_nav_menu_items`, μενού 3)·
  «Οι συνταγές μου» → «Φτιάξτε λογαριασμό». Cron `ellada_members_cleanup`: διαγραφή μελών ανενεργών > 24 μήνες. Δοκιμή end-to-end πέτυχε (οι δοκιμαστικοί χρήστες σβήστηκαν).
  Απόρρητο (σελίδα 3): νέα ενότητα «Λογαριασμός» + cookie σύνδεσης 14 ημερών, ημερομηνία 24/11 (backup meta `_ellada_privacy_backup_2026_11_24`). Επόμενα (Βήμα 2): «Οι προτάσεις μου» (κατάσταση πρότασης), σημειώσεις, λίστα για ψώνια, συλλογές.
- ellada-map 1.34.0 (backups έως 1.33.1). **`read_aloud` ΑΝΟΙΧΤΟ** (live 24/11). 1.34.0: `includes/access.php` — ♿ ACF «Προσβασιμότητα»
  στα σημεία (`access` checkbox: step_free/wc/parking/sea/dogs/large, `access_note`, `access_checked`) → σήματα στην κάρτα σημείου + ενότητα στη σελίδα
  του (μόνο αν συμπληρωθεί· δεν υπάρχουν ακόμα δημοσιευμένα σημεία)· φόρμα v3: θέμα **`prosvasimotita`** (πού, δυσκολία, βοηθητική τεχνολογία)·
  footer νομικά: «Προσβασιμότητα» → σελίδα **674 «Δήλωση προσβασιμότητας»** (`/dilosi-prosvasimotitas/`, **ΔΗΜΟΣΙΕΥΜΕΝΗ** 24/11, σύνδεσμος στο footer).
- ellada-map 1.33.1 (backups έως 1.33.0). 1.33.0: `includes/read-aloud.php` — 🔊 «Διάβασέ μου τα βήματα» στις συνταγές πίσω από flag
  **`read_aloud`** (ΑΝΟΙΧΤΟ· προβολή χωρίς σύνδεση `?ellada_demo=read`): Web Speech API (ελληνική φωνή της συσκευής, τίποτα σε τρίτους), μπάρα με
  Προηγ./Παύση/Επόμ./Τέλος (sticky), τονισμός βήματος `li.is-reading`, «Τέλος. Καλή όρεξη!». 1.33.1 (live, χωρίς flag): **διόρθωση** — στο κινητό η
  πλαϊνή στήλη συνταγής `recipe-aside` (sticky από το Elementor 109) σκέπαζε τα βήματα· `position: static` ≤ 1024px.
- ellada-map 1.32.3 (backups έως 1.32.2). 1.32.1–3: διόρθωση ημιδιάφανων χρωμάτων στο script αντίθεσης, role=main και στον Οδηγό, aside μέσα σε main → region, pmeta #4A524F, η μάσκα της κορδέλας δεν θαμπώνει το ⏸· axe μετά (με ?ellada_demo=a11y): 0 παραβάσεις σε 8 σελίδες × 2 συσκευές. 1.32.0: `includes/a11y.php` — προσβασιμότητα v1 πίσω από flag **`a11y_v1`** (**ΑΝΟΙΧΤΟ**, live 24/11·
  προβολή χωρίς σύνδεση `?ellada_demo=a11y`): script αντίθεσης (σκουραίνει/ανοίγει την ίδια απόχρωση μόνο όπου < 4,5:1 ή < 3:1 στα μεγάλα,
  και μέσα στο shadow DOM του χάρτη), aria-label λογότυπου, role=main + στόχος «Μετάβαση στο περιεχόμενο», εσωτερικό header role=none,
  χάρτης: focus ring στο shadow DOM, «Παράλειψη χάρτη», «Οι περιφέρειες σε λίστα» (→ /odigos/#ellada-gtabs), footer ≥24px στο κινητό,
  κουμπί φόρμας #AE5F37. Χωρίς flag (live): ⏸ στην κορδέλα (home-closing), κρυφό H2 στις Συνταγές, μηνύματα λάθους φόρμας με aria-describedby.
  Έλεγχος axe 24/11 (πριν): color-contrast 117 κόμβοι, link-name, skip-link, landmarks, heading-order.
- ellada-map 1.31.0 (backups έως 1.30.2). **Επικοινωνία v3 ΑΝΟΙΧΤΗ** (flag **`contact_v3`**, live 23/11· `includes/contact-v3.php`): δική μας
  φόρμα (αντικαθιστά τη φόρμα του Elementor μέσω `elementor/widget/render_content`· η v2 απενεργοποιείται αυτόματα· η φόρμα Elementor μένει ως εφεδρεία)
  με πεδία ανά θέμα (syntagi/topos/lathos/synergasia/allo — ίδια slugs με ?thema=, κάρτες αριστερά το αλλάζουν, ?selida= γεμίζει τη Διόρθωση),
  φωτογραφία συνταγής (JPG/PNG/WebP ≤ 8 MB + «είναι δική μου»), συναίνεση απορρήτου. REST `ellada/v1/contact`: honeypot, υπογεγραμμένος χρόνος ≥ 4",
  όριο 5/ώρα (hash, όχι IP). Κάθε αποστολή → ιδιωτικό CPT **`ellada_inbox`** (μενού «Προτάσεις & μηνύματα», φουσκάκι νέων) + email στο info@
  (Reply-To αποστολέας) + **πρόχειρη συνταγή/σημείο** (draft, κουτί «Πρόταση αναγνώστη», φωτογραφία ως εικόνα, story/short_description).
  Cron `ellada_inbox_cleanup`: διαγραφή μηνυμάτων > 12 μηνών. Δοκιμή end-to-end πέτυχε (mailed=1, πρόχειρο + εικόνα)· τα δοκιμαστικά σβήστηκαν.
  Απόρρητο (σελίδα 3): νέα παράγραφος για προτάσεις/φωτογραφίες (backup meta `_ellada_privacy_backup_2026_11_23`).
- ellada-map 1.30.2 (backups έως 1.30.1). 1.30.1–2: μενού με **εικονίδια σε όλα** (σπιτάκι, κατσαρόλα=Συνταγές, pin=Εξερευνήστε· φάκελος/βιβλίο/♥/κουκκίδα για μελλοντικά, `ellada_menu_v2_kind()` μόνο στο μενού 3)· στον υπολογιστή μόνο εικονίδιο και «ακορντεόν» με τη λέξη στο hover/focus, η τρέχουσα σελίδα μένει ανοιχτή· στο κινητό εικονίδιο + λέξη. 1.30.0: `includes/menu-v2.php` — κεντρικό μενού v2 πίσω από flag **`menu_v2`** (**ΑΝΟΙΧΤΟ**, live 23/11·
  προβολή χωρίς σύνδεση `?ellada_demo=menu`): «Αρχική» → σπιτάκι (SVG, aria-label/title «Αρχική»· στο dropdown κινητού σπιτάκι + «Αρχική»),
  «Οδηγός» → «Εξερευνήστε» (φίλτρα `nav_menu_item_title`/`nav_menu_link_attributes`· το μενού 3 του WP δεν αλλάζει, η σελίδα /odigos/ μένει).
- ellada-map 1.29.1 (backups έως 1.29.0). 1.29.x: `includes/home-closing.php` — αρχική, ενότητα «Κάθε τόπος, ένα πιάτο» (h3c-hero) v2
  πίσω από flag **`home_closing`** (**ΑΝΟΙΧΤΟ**, live 23/11· προβολή χωρίς σύνδεση `?ellada_demo=closing`): φόντο #E7E2D5, τίτλος πετρόλ/κεραμιδί, δεξιά
  ζευγάρια «τόπος → πιάτο» από τις συνταγές (`ellada_hc_pairs`), κορδέλα με ονόματα πιάτων (συνταγές + `ellada_missing_recipes`), δεύτερο κουμπί
  «Ο οδηγός →». Με JS (template + `[data-id=h3c-…]`)· το Elementor δεν αλλάζει. **Έγινε** H1 → H2 του `h3c-title` (Elementor 163· backup meta `_ellada_h3c_backup_2026_11_23`) — η αρχική έχει πλέον ένα H1.
- ellada-map 1.28.1 (backups έως 1.28.0). 1.28.x: `includes/footer-v2.php` — **νέο footer** πίσω από flag **`footer_v2`** (**ΑΝΟΙΧΤΟ**, live 23/11· επαναφορά = κλείσιμο του flag):
  πετρόλ κάρτα με βαθιά σκιά, χρυσή-κεραμιδί γραμμή πάνω, φράση «Ανακαλύπτουμε… <γεύσεις>…» + 3 κάρτες δράσης (συνταγή/τόπος → Επικοινωνία ?thema=,
  Οι συνταγές μου), στήλες Εξερεύνησε/Συνταγές/Μιλήστε μας (+ info@), περιφέρειες ως chips (κουκκίδα = οδηγός· κινητό: «Περιφέρειες +»), ©/νομικά/«↑».
  Το Elementor footer 624 ΔΕΝ αλλάζει — κρύβεται με `body.ellada-fv2` και το νέο τυπώνεται στο `elementor/theme/after_do_footer`.
  Προβολή χωρίς σύνδεση: **`?ellada_demo=footer`**. Ο χρήστης δεν θέλει άλλους χάρτες (ούτε υδατογράφημα).
- ellada-map 1.27.0 (backups έως 1.26.4). **Σελίδα «Χάρτης» ανενεργή (22/11):** σελίδα 46 (`/xartis/`) σε **draft**, στοιχείο μενού 69
  «Χάρτης» διαγραμμένο από το «Κεντρικό μενού» (μενού: Αρχική · Συνταγές · Οδηγός). Backup: option `ellada_xartis_backup_2026_11_22`
  (μενού 3, θέση 4, page 46 publish). `/xartis/` → **301** στο `/#xartis` (κρατά το `?topos=`· `template_redirect` στο guide-tabs.php,
  μόνο όσο η σελίδα δεν είναι δημοσιευμένη). `ellada_guide_map_url()` → `/?topos=…#xartis`, footer «Χάρτης» → `/#xartis`, search → `/#xartis`,
  αρχική 163 «Ανοίξτε τον χάρτη» more_url → `#xartis`, 404 (189) κουμπί → `/#xartis` (backup meta `_ellada_xartis_backup_2026_11_22`).
  Επαναφορά: publish 46 + νέο στοιχείο μενού.
- ellada-map 1.26.4 (backups έως 1.26.3). 1.26.4: πιο έντονη σκιά στο πάνελ των tabs (διπλή, σκούρο πετρόλ). 1.26.3: και όλη η ενότητα των 13 περιφερειών σε #E7E2D5 (`.ellada-ghub__sec.is-tabs`). 1.26.2: πάνελ των tabs σε **#E7E2D5** (αντί για λευκό). 1.26.x: `includes/guide-tabs.php` — /odigos/ «Οι 13 περιφέρειες» ως **tabs** πίσω από flag
  **`guide_tabs`** (**ΑΝΟΙΧΤΟ**, live 22/11): αριστερά καρτέλες με εικόνα (`cover_image` της περιφέρειας ή το σχήμα της σε
  μεγέθυνση, κεραμιδί αν έχει οδηγό), δεξιά πάνελ με κείμενο/αριθμούς/CTA/νομούς + χάρτη με τους νομούς (links, `ellada_gtabs_bbox()`)·
  κινητό: οριζόντια λωρίδα· πλήκτρα βελών, `#perifereia-xxxxxx` στη διεύθυνση, χωρίς JS όλα ανοιχτά. Αν είναι κλειστό → τα παλιά `<details>`.
- ellada-map 1.25.4 (backups έως 1.25.3). 1.25.4: δοκιμή φόντου → **#F0E5D9**. 1.25.3: **δοκιμή φόντου #F3EFE5 μόνο στις λίστες συνταγών** (`body.ellada-rv4`, τέλος front.css)· το υπόλοιπο site μένει #F6F2E9 (αν εγκριθεί για όλο το site: kit 9 + V4 variables, προσοχή — το #F6F2E9 είναι και κρεμ κείμενο σε πετρόλ).
  1.25.2: **Συνταγές v4 ΑΝΟΙΧΤΟ** (flag `recipes_v4`, live 22/11· `includes/recipes-v4.php`):
  φίλτρα σε μία μπάρα πάνω από τις κάρτες (sticky σε υπολογιστή, κουμπί «Φίλτρα» στο κινητό· το `[ellada_recipe_filters]` στο hero επιστρέφει κενό),
  κάρτες 3 στηλών με περιφέρεια πάνω στη φωτογραφία, ♥ (`ellada-favs`), ζουμ, χάρτης περιφέρειας αντί για πιατάκι (`ellada_rv4_card()` από
  το `ellada_recipe_card`, μόνο σε λίστες συνταγών), πλαίσιο «Έχετε μια συνταγή του τόπου σας;» → Επικοινωνία `?thema=syntagi`.
  Ο χρήστης **απέρριψε** (1.25.0–1): νέο hero πετρόλ, «Στην εποχή τους», «Ανά περιφέρεια», «Έρχονται σύντομα», footer σε στήλες.
- **Κεφαλίδα (22/11):** αφαιρέθηκε το κουμπί «Ανοίξτε τον χάρτη» πάνω δεξιά (e-button `hdd-cta-btn` στο 70, `hdt-cta-btn` στο 176)· backup meta `_ellada_cta_backup_2026_11_22`. Μένει το «Ανοίξτε τον χάρτη →» στην ενότητα περιφερειών της αρχικής.
- ellada-map **1.24.22** live (backups έως 1.24.21). 1.24.22: χάρτης v2 — το κεραμιδί πιο διάφανο ανά επίπεδο: περιφέρειες 80%, νομοί 64%, δήμοι 50% (hover 95/85/75· `V2.contentAlpha` στο GreeceMap.tsx). 1.24.21: το σβήσιμο ξεκινά από την άκρη του μπολ/πιάτου (×.43) και κρύβει τη σκληρή άκρη της φωτεινής σανίδας της φωτογραφίας (×.535). 1.24.20: πλαϊνό σβήσιμο hero — η φωτογραφία είναι τετράγωνη (πλάτος = ύψος hero)· από το πιάτο/μπολάκι (×.48) → μαύρο (×.62–.56) → σκούρο πετρόλ (×.8) → πετρόλ στην αριστερή άκρη της φωτογραφίας (×1)· έφυγε η σκληρή κάθετη γραμμή σε φαρδιές οθόνες. 1.24.19: στο hero μένει αόρατο αντίγραφο (`.ellada-maplink-ph`) ώστε τίτλος/αναζήτηση να μένουν στη θέση τους (το περιεχόμενο είναι κεντραρισμένο κατακόρυφα). 1.24.18: το «Ή εξερευνήστε τον χάρτη» μεταφέρεται με JS (home-v2.php) από το hero στην αρχή της ενότητας χάρτη `h3-map` (class `ellada-has-maplink`), πλάτος όσο το περιεχόμενο (max 1200), χρυσή γραμμή σε όλο το πλάτος, βελάκι δεξιά. 1.24.17: hero αρχικής — ::before με 2 στρώσεις: (1) πλαϊνό: η φωτογραφία σβήνει
  προς τα αριστερά σε σκούρο #0B0B0B (διάφανο στο πιάτο/μπολάκι ×.48 του ύψους από δεξιά → σκούρο ×.58 → διάφανο ×.74 μέσα στο πετρόλ·
  `--ellada-hero-h` από JS στο home-v2.php), (2) μαύρο πάνω 11% μόνο πάνω από τη φωτογραφία. Κάτω (::after) πετρόλ 16%. Κινητό: πάνω πετρόλ 14%.
  (1.24.13–15: δοκιμή με συμπαγές πετρόλ στο πλάι — απορρίφθηκε.) 1.24.10–12: και από πάνω σβήσιμο σε **μαύρο #0B0B0B μόνο πάνω από τη φωτογραφία** (::before από left 55% με mask στην αριστερή άκρη, 11%, ως το μπολάκι με λάδι/ελιές· κινητό 14%· όχι #0A0A0A για να μην το πιάνει η προεπισκόπηση παλέτας). 1.24.7–9: η φωτογραφία του hero της αρχικής (`home2-hero`) σβήνει προς το πετρόλ στο κάτω 16% (ξεκινά στο τέλος του πιάτου, γρήγορη καμπύλη· κινητό 35%) (::after, front.css). 1.24.6: 1.24.6: πλαίσιο χάρτη (v2) με βαθιά σκιά σε σκούρο πετρόλ (`--ellada-map-shadow`) + λεπτό κρεμ περίγραμμα 16%. 1.24.5: 1.24.5: ενεργές περιοχές χάρτη στο 80%. 1.24.4: 1.24.4: ενεργές περιοχές χάρτη σε #D97745 (χρώμα λογότυπου). 1.24.3: 1.24.3: μικρή διαφάνεια στις ενεργές περιοχές του χάρτη (85%). 1.24.1–2: χρώματα χάρτη v2 flag **`map_v2`** ΑΝΟΙΧΤΟ (live 21/11· `data-map-colors="v2"` →
  prop `colorsV2` στο `GreeceMap.tsx`): ανενεργή #2C6866 (hover #357A77), ενεργή **#D97745 (όπως το λογότυπο) στο 80%** (hover #E39468 95%, σύνορα σκούρο πετρόλ),
  πάτημα #C2602F, υπόμνημα «Οδηγός & συνταγές / Έρχεται σύντομα», οδηγία #A9BDB8, κάρτα πληροφοριών σε σκούρο πετρόλ (`--ellada-map-card`).
- ellada-map 1.24.0:
- ellada-map 1.23.4: 1.23.4: δύο προεπισκοπήσεις για σύγκριση — `?ellada_palette=petrol2` (πετρόλ #1D514E)
  και `?ellada_palette=petrol3` (πετρόλ #174C4B), αλλιώς ίδιες (#D29A48 · #F6F2E9 · #D7DFD8 · #343B39).
  1.23.3: 1.23.3: πετρόλ προεπισκόπησης → **#1D514E** (hover #153E3C, γραμμές σε σκούρο #2A625F/#33706C).
  Τρέχουσα παλέτα χρήστη (petrol2): #1D514E · #D29A48 · #F6F2E9 · #D7DFD8 · #343B39. 1.23.x: `includes/contact-v2.php` + CSS `body.ellada-cv2` — σύγχρονη φόρμα Επικοινωνίας
  flag **`contact_v2`** ΑΝΟΙΧΤΟ (live 21/11): κεφαλίδα, «Θέμα» ως κουμπιά (το select μένει κρυφό), πεδία με γέμισμα, οδηγία/παράδειγμα
  ανά θέμα, μετρητής 3000, auto-grow, λάθη μετά το blur, κουμπί με βελάκι. Ίδια φόρμα Elementor (email/SMTP/submissions ίδια).
- ellada-map 1.22.5: 1.22.5: πετρόλ → **#28545A**, κρεμ φόντο → **#F6F2E9** (και στις δύο παλέτες· σκούρο hover #1F4247,
  γραμμές σε σκούρο #346670/#3D717B). Τρέχουσα επιλογή χρήστη: petrol2 (#28545A + #D29A48 + #F6F2E9 + #D7DFD8 + #343B39). 1.22.4: δεύτερη παλέτα `petrol2` = ίδια με ζεστό χρυσό **#D29A48** (`?ellada_palette=petrol2`),
  εναλλαγή από τη μπάρα κάτω αριστερά. Βρέθηκε: το κουμπί «Βρείτε συνταγή» (`.ellada-finder__btn`) έχει ροζ border #CC3366 από το Elementor kit (και live).
  1.22.1–3: `includes/palette-preview.php` — προεπισκόπηση παλέτας ΜΟΝΟ για editors:
  `?ellada_palette=petrol` (cookie 1 μέρα), `?ellada_palette=off`. Παλέτα «petrol» του χρήστη (αρχικά #174C4B/#F5F1E8, τώρα #28545A/#F6F2E9): #C5A46D χρυσό άμμου, #D7DFD8 φασκόμηλο, #343B39 ανθρακί (χάρτης παλιό→νέο στο `ellada_palettes()`). Μηχανισμός: kit vars + ίδιοι κανόνες CSS
  (χωρίς !important) στο τέλος + swap στο HTML + αντίγραφο του assets/js/ellada-map.js (shadow DOM) στο uploads/ellada-palette/.
  Δεν έχει εφαρμοστεί μόνιμα· το λογότυπο (PNG) είναι ακόμα πορτοκαλί. Σελίδα σύγκρισης: artifact 5ciieNHrArT5UaUxZZLbSk.
- ellada-map 1.22.0: 1.22.0: `includes/seo-titles.php` — flag **`seo_titles`** (ΚΛΕΙΣΤΟ, περιμένει έγκριση):
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
- **Από τις αναφορές 23/11 (7,4/10), 24/11 πρωί (8,1/10) και 24/11 απόγευμα (8,2/10· PDF στάλθηκαν· με τα 1–3 → ~8,7). axe 24/11 απόγ.: 1 εύρημα — link-in-text-block στο «Φτιάξτε λογαριασμό» (Οι συνταγές μου) → υπογράμμιση:**
  1. Καθάρισμα αρχικής: αφαίρεση (κρύψιμο) `h4-coll` (άδειο) και `h6-submit` (ίδιο με footer), συγχώνευση `h6-guide` + `h3-regions`,
     κρύψιμο `h3-latest` μέχρι 6+ συνταγές· «Συνταγή της εβδομάδας» να έχει φωτογραφία (τώρα 141 χωρίς).
  2. Ελάφρυνση /odigos/: HTML 1,2 MB (500 KB gz) — τα tabs ξαναγράφουν τον χάρτη-φόντο ×13 → ένα `<symbol>`/`<use>` ή χωρίς is-ctx.
  3. Άνοιγμα `seo_titles` (τίτλος αρχικής 103 χαρ., περιγραφή Επικοινωνίας, canonical Συνταγών) + alt «Ελλάδα στο Πιάτο» στο λογότυπο-link
     (hdt-logo/hdd-logo) + ItemList schema στις Συνταγές + μεγαλύτερο ύψος συνδέσμων footer στο κινητό (≥24px).
  4. Περιεχόμενο: φωτογραφίες 141/142, χριστουγεννιάτικες συνταγές, κείμενα 2ης περιφέρειας.
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
- Τίτλοι Google (flag `seo_titles`): περιμένουν έγκριση για άνοιγμα (το δεύτερο H1 της αρχικής διορθώθηκε 23/11).
- Λογαριασμός — επόμενα (όταν υπάρχουν 10–15+ συνταγές): ειδοποιήσεις/newsletter με opt-in, «Το έφτιαξα κι εγώ» με φωτογραφίες + έλεγχο, βαθμολογία ★/σχόλια, κοινοποίηση συλλογής. **Εκκρεμεί:** ο χρήστης να δοκιμάσει τον σύνδεσμο σύνδεσης με αληθινό email (όχι του admin) — έρχεται στα εισερχόμενα;
- **Λογότυπο σε υψηλή ανάλυση / ταπετσαρίες (25/11, στάλθηκαν στον χρήστη· σε αναμονή):** το λογότυπο ξαναφτιάχτηκε διανυσματικά
  (`wp-embed/brand/logo-wallpapers.py`: Ελλάδα από `public/greece-regions.geojson`, κείμενο ως paths από FiraSansExtraCondensed 800 με fontTools·
  `wp-embed/brand/render-svg.js` → PNG με Playwright `--disable-gpu`). Παραδόθηκαν: υπολογιστής 3840×2160 και κινητό 1290×2796 (πετρόλ/κρεμ),
  λογότυπο οριζόντιο + σήμα με διάφανο φόντο (PNG+SVG). Επόμενα αν ζητηθούν: tablet/διπλή οθόνη, χωρίς σλόγκαν, με φωτογραφία φόντο·
  πιθανή αντικατάσταση των μικρών PNG του site (667/668/669) με εκδόσεις από το SVG.
- Σε αναμονή: Reach/newsletter.

> Ενημέρωνε τις ενότητες «Πρόοδος» και «Εκκρεμότητες» όταν κάτι ολοκληρώνεται.
