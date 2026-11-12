/**
 * Ορισμοί ACF Pro (post types + field groups) για τον χάρτη, σε μορφή εισαγωγής του
 * ACF → Εργαλεία → Εισαγωγή (JSON). Οι επιλογές περιφέρειας/νομού/δήμου παράγονται
 * από τα GeoJSON, ώστε οι συντάκτες να διαλέγουν από λίστα και να μην υπάρχουν τυπογραφικά.
 */

export const POST_TYPES = {
  location: { key: 'ellada_location', restBase: 'locations', slug: 'perioxes' },
  point: { key: 'ellada_point', restBase: 'local-points', slug: 'topika-simeia' },
}

const prettify = (s) => s.replace(/(\p{Ll})(\p{Lu})/gu, '$1 $2')
const collator = new Intl.Collator('el')

/* ── helpers για fields ── */
const base = (key, name, label, type, extra = {}) => ({
  key: `field_ellada_${key}`,
  label,
  name,
  'aria-label': '',
  type,
  instructions: '',
  required: 0,
  conditional_logic: 0,
  wrapper: { width: '', class: '', id: '' },
  ...extra,
})

const tab = (key, label) =>
  base(key, '', label, 'tab', { placement: 'top', endpoint: 0, selected: 0 })

const select = (key, name, label, choices, extra = {}) =>
  base(key, name, label, 'select', {
    choices,
    default_value: false,
    return_format: 'value',
    multiple: 0,
    allow_null: 0,
    ui: 0,
    ajax: 0,
    placeholder: '',
    allow_in_bindings: 0,
    create_options: 0,
    save_options: 0,
    ...extra,
  })

const textarea = (key, name, label, extra = {}) =>
  base(key, name, label, 'textarea', {
    default_value: '',
    maxlength: '',
    allow_in_bindings: 1,
    rows: 3,
    placeholder: '',
    new_lines: '', // απλό κείμενο στο REST (χωρίς <p>)
    ...extra,
  })

const image = (key, name, label, extra = {}) =>
  base(key, name, label, 'image', {
    return_format: 'array',
    library: 'all',
    min_width: '',
    min_height: '',
    min_size: '',
    max_width: '',
    max_height: '',
    max_size: '',
    mime_types: '',
    allow_in_bindings: 1,
    preview_size: 'medium',
    ...extra,
  })

const number = (key, name, label, extra = {}) =>
  base(key, name, label, 'number', {
    default_value: '',
    min: '',
    max: '',
    allow_in_bindings: 1,
    placeholder: '',
    step: 'any',
    prepend: '',
    append: '',
    ...extra,
  })

const showIf = (fieldKey, value) => [[{ field: `field_ellada_${fieldKey}`, operator: '==', value }]]

const group = (key, title, postType, fields, extra = {}) => ({
  key: `group_ellada_${key}`,
  title,
  fields,
  location: [[{ param: 'post_type', operator: '==', value: postType }]],
  menu_order: 0,
  position: 'acf_after_title',
  style: 'default',
  label_placement: 'top',
  instruction_placement: 'label',
  hide_on_screen: '',
  active: true,
  description: '',
  show_in_rest: 1, // ← απαραίτητο για να εμφανίζεται το `acf` στο REST API
  ...extra,
})

/** `forms`: ελληνικές ετικέτες με σωστό γένος/πτώση, π.χ. «Νέα τοποθεσία» / «Νέο τοπικό σημείο». */
const postType = ({ key, restBase, slug }, title, singular, forms, extra = {}) => ({
  key: `post_type_${key}`,
  title,
  menu_order: 0,
  active: true,
  post_type: key,
  advanced_configuration: true,
  import_source: '',
  import_date: '',
  labels: {
    name: title,
    singular_name: singular,
    menu_name: title,
    all_items: forms.all,
    add_new: 'Προσθήκη',
    add_new_item: forms.newItem,
    edit_item: forms.edit,
    new_item: forms.newItem,
    view_item: 'Προβολή',
    view_items: 'Προβολή',
    search_items: 'Αναζήτηση',
    not_found: 'Δεν βρέθηκαν',
    not_found_in_trash: 'Δεν βρέθηκαν στα διαγραμμένα',
  },
  description: '',
  public: true,
  hierarchical: false,
  exclude_from_search: false,
  publicly_queryable: true,
  show_ui: true,
  show_in_menu: true,
  admin_menu_parent: '',
  show_in_admin_bar: true,
  show_in_nav_menus: true,
  show_in_rest: true,
  rest_base: restBase,
  rest_namespace: 'wp/v2',
  rest_controller_class: 'WP_REST_Posts_Controller',
  menu_position: 21,
  menu_icon: 'dashicons-location-alt',
  rename_capabilities: false,
  singular_capability_name: 'post',
  plural_capability_name: 'posts',
  supports: ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
  taxonomies: [],
  has_archive: false,
  has_archive_slug: '',
  rewrite: {
    permalink_rewrite: 'custom_permalink',
    slug,
    feeds: false,
    pages: true,
    with_front: true,
  },
  query_var: 'post_type_key',
  query_var_name: '',
  can_export: true,
  delete_with_user: false,
  register_meta_box_cb: '',
  enter_title_here: '',
  ...extra,
})

/**
 * @param {{ regions: any, prefectures: any, municipalities: any }} geo  GeoJSON FeatureCollections
 */
export function buildAcfImport(geo) {
  /* ── επιλογές από τα GeoJSON ── */
  const regionChoices = Object.fromEntries(
    geo.regions.features
      .map((f) => f.properties.name_greek)
      .sort(collator.compare)
      .map((n) => [n, n]),
  )

  const prefectureChoices = Object.fromEntries(
    geo.prefectures.features
      .map((f) => f.properties)
      .sort((a, b) => collator.compare(a.name_greek, b.name_greek))
      .map((p) => [p.name_greek, `${p.name_greek} (${p.periphery_greek})`]),
  )

  // Τιμή «ΝΟΜΟΣ|Δήμος» γιατί υπάρχουν ομώνυμοι δήμοι (π.χ. Ηρακλείου Κρήτης / Αττικής).
  const municipalityChoices = Object.fromEntries(
    geo.municipalities.features
      .map((f) => f.properties)
      .sort((a, b) => collator.compare(a.name_greek, b.name_greek))
      .map((m) => [
        `${m.prefecture_greek}|${m.name_greek}`,
        `${prettify(m.name_greek)} — ${m.prefecture_greek || 'Άγιο Όρος'}`,
      ]),
  )

  const locationType = postType(
    POST_TYPES.location,
    'Τοποθεσίες',
    'Τοποθεσία',
    { all: 'Όλες οι τοποθεσίες', newItem: 'Νέα τοποθεσία', edit: 'Επεξεργασία τοποθεσίας' },
    {
      description: 'Περιφέρειες, νομοί και δήμοι του χάρτη «Ελλάδα στο Πιάτο».',
      hierarchical: true,
      supports: ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'custom-fields'],
      enter_title_here: 'π.χ. Χανιά',
    },
  )

  const pointType = postType(
    POST_TYPES.point,
    'Τοπικά Σημεία',
    'Τοπικό Σημείο',
    {
      all: 'Όλα τα τοπικά σημεία',
      newItem: 'Νέο τοπικό σημείο',
      edit: 'Επεξεργασία τοπικού σημείου',
    },
    {
      description:
        'Σημεία ενδιαφέροντος (προϊόντα, παραγωγοί, ταβέρνες, αγορές…) με συντεταγμένες.',
      menu_position: 22,
      menu_icon: 'dashicons-location',
      enter_title_here: 'π.χ. Ταβέρνα «Το Λιμανάκι»',
    },
  )

  const locationGroup = group('location', 'Χάρτης — Τοποθεσία', POST_TYPES.location.key, [
    tab('loc_tab_map', 'Χάρτης'),
    select(
      'loc_level',
      'map_level',
      'Επίπεδο',
      { region: 'Περιφέρεια', prefecture: 'Νομός', municipality: 'Δήμος' },
      {
        required: 1,
        default_value: 'region',
        instructions:
          'Σε ποιο επίπεδο του χάρτη αντιστοιχεί η σελίδα. Βάλε και «Γονέα» (Χαρακτηριστικά σελίδας) για την ιεραρχία.',
      },
    ),
    select('loc_region', 'geo_region', 'Περιφέρεια στον χάρτη', regionChoices, {
      required: 1,
      ui: 1,
      allow_null: 1,
      conditional_logic: showIf('loc_level', 'region'),
      instructions: 'Το πολύγωνο του χάρτη που αντιστοιχεί σε αυτή τη σελίδα.',
    }),
    select('loc_prefecture', 'geo_prefecture', 'Νομός στον χάρτη', prefectureChoices, {
      required: 1,
      ui: 1,
      allow_null: 1,
      conditional_logic: showIf('loc_level', 'prefecture'),
      instructions: 'Το πολύγωνο του χάρτη που αντιστοιχεί σε αυτή τη σελίδα.',
    }),
    select('loc_municipality', 'geo_municipality', 'Δήμος στον χάρτη', municipalityChoices, {
      required: 1,
      ui: 1,
      allow_null: 1,
      conditional_logic: showIf('loc_level', 'municipality'),
      instructions: 'Το πολύγωνο του χάρτη που αντιστοιχεί σε αυτή τη σελίδα.',
    }),
    textarea('loc_short_description', 'short_description', 'Σύντομη περιγραφή', {
      instructions: 'Εμφανίζεται στην κάρτα του χάρτη (1–3 προτάσεις).',
    }),
    image('loc_cover_image', 'cover_image', 'Φωτογραφία κάρτας'),
    tab('loc_tab_guide', 'Ταξιδιωτικός οδηγός'),
    textarea('loc_highlights', 'highlights', 'Highlights περιοχής'),
    select(
      'loc_best_time',
      'best_time_to_visit',
      'Καλύτερη εποχή επίσκεψης',
      {
        all_year: 'Όλο τον χρόνο',
        spring: 'Άνοιξη (Μάρτιος – Μάιος)',
        summer: 'Καλοκαίρι (Ιούνιος – Αύγουστος)',
        autumn: 'Φθινόπωρο (Σεπτέμβριος – Νοέμβριος)',
        winter: 'Χειμώνας (Δεκέμβριος – Φεβρουάριος)',
      },
      { allow_null: 1, return_format: 'array' },
    ),
    textarea('loc_how_to_get_there', 'how_to_get_there', 'Πώς να φτάσεις'),
    textarea('loc_local_products', 'local_products', 'Τοπικά προϊόντα & γεύσεις'),
    textarea('loc_travel_tips', 'travel_tips', 'Συμβουλές ταξιδιού'),
    base('loc_gallery', 'gallery', 'Gallery φωτογραφιών', 'gallery', {
      return_format: 'array',
      library: 'all',
      min: '',
      max: '',
      min_width: '',
      min_height: '',
      min_size: '',
      max_width: '',
      max_height: '',
      max_size: '',
      mime_types: '',
      insert: 'append',
      preview_size: 'medium',
    }),
  ])

  const pointGroup = group('point', 'Χάρτης — Τοπικό Σημείο', POST_TYPES.point.key, [
    select(
      'pt_type',
      'point_type',
      'Είδος σημείου',
      {
        product: 'Τοπικό προϊόν',
        producer: 'Παραγωγός',
        restaurant: 'Εστιατόριο / Ταβέρνα',
        market: 'Αγορά',
        festival: 'Γιορτή / Πανηγύρι',
        sight: 'Αξιοθέατο',
        other: 'Άλλο',
      },
      { required: 1, default_value: 'other', return_format: 'array' },
    ),
    number('pt_lat', 'lat', 'Γεωγραφικό πλάτος (lat)', {
      required: 1,
      min: 34,
      max: 42,
      placeholder: '35.5138',
      wrapper: { width: '50', class: '', id: '' },
      instructions:
        'Στο Google Maps κάνε δεξί κλικ στο σημείο και πάτησε τις συντεταγμένες για αντιγραφή. Η 1η τιμή είναι το lat, η 2η το lng.',
    }),
    number('pt_lng', 'lng', 'Γεωγραφικό μήκος (lng)', {
      required: 1,
      min: 19,
      max: 30,
      placeholder: '24.0180',
      wrapper: { width: '50', class: '', id: '' },
      instructions: 'Ο χάρτης βρίσκει μόνος του σε ποιον δήμο / νομό ανήκει το σημείο.',
    }),
    textarea('pt_short_description', 'short_description', 'Σύντομη περιγραφή', {
      instructions: 'Εμφανίζεται στην κάρτα του χάρτη (1–3 προτάσεις).',
    }),
    image('pt_cover_image', 'cover_image', 'Φωτογραφία κάρτας'),
    base('pt_external_url', 'external_url', 'Εξωτερικός σύνδεσμος (προαιρετικό)', 'url', {
      default_value: '',
      allow_in_bindings: 1,
      placeholder: 'https://',
      instructions: 'Αν συμπληρωθεί, η κάρτα του χάρτη οδηγεί εδώ αντί για τη σελίδα του σημείου.',
    }),
  ])

  return [locationType, pointType, locationGroup, pointGroup]
}
