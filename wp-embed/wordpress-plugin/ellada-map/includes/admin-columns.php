<?php
/**
 * Στήλες και φίλτρα στις λίστες «Τοποθεσίες» και «Τοπικά Σημεία» του διαχειριστικού.
 * Μόνο ανάγνωση: διαβάζει τα πεδία ACF (post meta), δεν αλλάζει τίποτα.
 */

defined( 'ABSPATH' ) || exit;

/** Οι επιλογές (value => label) ενός select του ACF, με βάση το όνομα του πεδίου. */
function ellada_map_choices( $field_name ) {
	static $cache = array();
	if ( isset( $cache[ $field_name ] ) ) {
		return $cache[ $field_name ];
	}
	$choices = array();
	if ( function_exists( 'acf_get_field' ) ) {
		$field = acf_get_field( $field_name );
		if ( $field && ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
			$choices = $field['choices'];
		}
	}
	$cache[ $field_name ] = $choices;
	return $choices;
}

function ellada_map_choice_label( $field_name, $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}
	$choices = ellada_map_choices( $field_name );
	return isset( $choices[ $value ] ) ? $choices[ $value ] : $value;
}

/* ───────────── Τοποθεσίες ───────────── */

add_filter(
	'manage_ellada_location_posts_columns',
	function ( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['ellada_level'] = 'Επίπεδο';
				$out['ellada_area']  = 'Περιοχή στον χάρτη';
			}
		}
		return $out;
	}
);

add_action(
	'manage_ellada_location_posts_custom_column',
	function ( $column, $post_id ) {
		$level = (string) get_post_meta( $post_id, 'map_level', true );
		if ( 'ellada_level' === $column ) {
			echo esc_html( ellada_map_choice_label( 'map_level', $level ) ?: '—' );
			return;
		}
		if ( 'ellada_area' !== $column ) {
			return;
		}
		$region = (string) get_post_meta( $post_id, 'geo_region', true );
		$pref   = (string) get_post_meta( $post_id, 'geo_prefecture', true );
		$muni   = (string) get_post_meta( $post_id, 'geo_municipality', true );

		$parts = array();
		if ( $region ) {
			$parts[] = $region;
		}
		if ( $pref && in_array( $level, array( 'prefecture', 'municipality' ), true ) ) {
			$parts[] = 'Ν. ' . $pref;
		}
		if ( $muni && 'municipality' === $level ) {
			$name    = false !== strpos( $muni, '|' ) ? substr( $muni, strpos( $muni, '|' ) + 1 ) : $muni;
			$parts[] = 'Δ. ' . $name;
		}
		echo $parts ? esc_html( implode( ' › ', $parts ) ) : '<span style="color:#b32d2e">Δεν έχει οριστεί</span>';
	},
	10,
	2
);

add_filter(
	'manage_edit-ellada_location_sortable_columns',
	function ( $columns ) {
		$columns['ellada_level'] = 'ellada_level';
		$columns['ellada_area']  = 'ellada_area';
		return $columns;
	}
);

/* ───────────── Τοπικά Σημεία ───────────── */

add_filter(
	'manage_ellada_point_posts_columns',
	function ( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['ellada_type']   = 'Τύπος';
				$out['ellada_coords'] = 'Θέση';
			}
		}
		return $out;
	}
);

add_action(
	'manage_ellada_point_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'ellada_type' === $column ) {
			$type = (string) get_post_meta( $post_id, 'point_type', true );
			echo esc_html( ellada_map_choice_label( 'point_type', $type ) ?: '—' );
			return;
		}
		if ( 'ellada_coords' !== $column ) {
			return;
		}
		$lat = get_post_meta( $post_id, 'lat', true );
		$lng = get_post_meta( $post_id, 'lng', true );
		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
			echo '<span style="color:#b32d2e">Χωρίς συντεταγμένες</span>';
			return;
		}
		printf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s, %s</a>',
			esc_url( sprintf( 'https://www.openstreetmap.org/?mlat=%1$F&mlon=%2$F#map=15/%1$F/%2$F', $lat, $lng ) ),
			esc_html( number_format( (float) $lat, 4, '.', '' ) ),
			esc_html( number_format( (float) $lng, 4, '.', '' ) )
		);
	},
	10,
	2
);

add_filter(
	'manage_edit-ellada_point_sortable_columns',
	function ( $columns ) {
		$columns['ellada_type'] = 'ellada_type';
		return $columns;
	}
);

/* ───────────── Φίλτρα ───────────── */

/** Φίλτρα ανά post type: query var => [πεδίο ACF, τίτλος «όλα»]. */
function ellada_map_filters( $post_type ) {
	$map = array(
		'ellada_location' => array(
			'ellada_level'  => array( 'map_level', 'Όλα τα επίπεδα' ),
			'ellada_region' => array( 'geo_region', 'Όλες οι περιφέρειες' ),
		),
		'ellada_point'    => array(
			'ellada_type' => array( 'point_type', 'Όλοι οι τύποι' ),
		),
	);
	return isset( $map[ $post_type ] ) ? $map[ $post_type ] : array();
}

add_action(
	'restrict_manage_posts',
	function ( $post_type ) {
		foreach ( ellada_map_filters( $post_type ) as $var => $def ) {
			list( $field, $all ) = $def;
			$choices             = ellada_map_choices( $field );
			if ( ! $choices ) {
				continue;
			}
			$current = isset( $_GET[ $var ] ) ? sanitize_text_field( wp_unslash( $_GET[ $var ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			printf( '<select name="%s"><option value="">%s</option>', esc_attr( $var ), esc_html( $all ) );
			foreach ( $choices as $value => $label ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( $current, (string) $value, false ), esc_html( $label ) );
			}
			echo '</select>';
		}
	}
);

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$post_type = $query->get( 'post_type' );
		if ( ! in_array( $post_type, array( 'ellada_location', 'ellada_point' ), true ) ) {
			return;
		}

		// Φίλτρα
		$existing   = $query->get( 'meta_query' );
		$meta_query = is_array( $existing ) ? $existing : array();
		$added      = false;
		foreach ( ellada_map_filters( $post_type ) as $var => $def ) {
			if ( empty( $_GET[ $var ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				continue;
			}
			$meta_query[] = array(
				'key'   => $def[0],
				'value' => sanitize_text_field( wp_unslash( $_GET[ $var ] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
			$added        = true;
		}
		if ( $added ) {
			$query->set( 'meta_query', $meta_query );
		}

		// Ταξινόμηση
		$sort = array(
			'ellada_level' => 'map_level',
			'ellada_area'  => 'geo_region',
			'ellada_type'  => 'point_type',
		);
		$orderby = $query->get( 'orderby' );
		if ( is_string( $orderby ) && isset( $sort[ $orderby ] ) ) {
			$query->set( 'meta_key', $sort[ $orderby ] );
			$query->set( 'orderby', 'meta_value' );
		}
	}
);
