<?php
/**
 * «Θέση στον χάρτη» στην οθόνη επεξεργασίας Τοπικού Σημείου.
 *
 * Μικρός χάρτης (Leaflet + OpenStreetMap): κλικ ή σύρσιμο της πινέζας συμπληρώνει τα πεδία
 * lat/lng του ACF, και φαίνεται αμέσως σε ποιον δήμο/νομό/περιφέρεια θα μπει το σημείο.
 */

defined( 'ABSPATH' ) || exit;

function ellada_map_is_point_screen() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $screen && 'post' === $screen->base && 'ellada_point' === $screen->post_type;
}

add_action(
	'add_meta_boxes_ellada_point',
	function () {
		add_meta_box( 'ellada-map-picker', 'Θέση στον χάρτη', 'ellada_map_picker_box', 'ellada_point', 'normal', 'high' );
	}
);

function ellada_map_picker_box() {
	?>
	<div class="ellada-picker">
		<div class="ellada-picker-search">
			<input type="search" class="regular-text" placeholder="Αναζήτηση τόπου, π.χ. «Δημοτική Αγορά Χανίων»" aria-label="Αναζήτηση τόπου">
			<button type="button" class="button">Αναζήτηση</button>
			<span class="ellada-picker-status" aria-live="polite"></span>
		</div>
		<div class="ellada-picker-map" role="application" aria-label="Χάρτης επιλογής θέσης"></div>
		<p class="ellada-picker-area" aria-live="polite"></p>
		<p class="description">
			Κάντε κλικ στον χάρτη ή σύρετε την πινέζα — τα πεδία <strong>lat</strong> / <strong>lng</strong> συμπληρώνονται μόνα τους.
			Η αναζήτηση γίνεται μέσω OpenStreetMap.
		</p>
	</div>
	<?php
}

add_action(
	'admin_enqueue_scripts',
	function () {
		if ( ! ellada_map_is_point_screen() ) {
			return;
		}
		$vendor = plugins_url( 'assets/vendor/leaflet/', ELLADA_MAP_FILE );
		wp_enqueue_style( 'ellada-leaflet', $vendor . 'leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'ellada-leaflet', $vendor . 'leaflet.js', array(), '1.9.4', true );
		wp_enqueue_style( 'ellada-map-admin', plugins_url( 'admin/admin.css', ELLADA_MAP_FILE ), array(), ELLADA_MAP_VERSION );
		wp_enqueue_script( 'ellada-map-picker', plugins_url( 'admin/point-picker.js', ELLADA_MAP_FILE ), array( 'ellada-leaflet' ), ELLADA_MAP_VERSION, true );

		// Όμορφα ονόματα δήμων από τις επιλογές του ACF («ΝΟΜΟΣ|Όνομα» => «Όνομα — ΝΟΜΟΣ»).
		$muni_labels = array();
		if ( function_exists( 'acf_get_field' ) ) {
			$f = acf_get_field( 'geo_municipality' );
			if ( $f && ! empty( $f['choices'] ) ) {
				foreach ( $f['choices'] as $k => $label ) {
					$muni_labels[ $k ] = preg_replace( '/\s+—\s+.*$/u', '', (string) $label );
				}
			}
		}

		wp_localize_script(
			'ellada-map-picker',
			'elladaPicker',
			array(
				'municipalitiesUrl' => plugins_url( 'assets/geo/municipalities.json', ELLADA_MAP_FILE ),
				'leafletImages'     => $vendor . 'images/',
				'prefRegions'       => ellada_map_prefecture_regions(),
				'muniLabels'        => $muni_labels,
			)
		);
	}
);
