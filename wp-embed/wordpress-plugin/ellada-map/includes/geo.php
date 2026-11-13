<?php
/**
 * Γεωγραφικά βοηθήματα από τα αρχεία assets/geo/*.json του plugin.
 * Τα αποτελέσματα κρατιούνται σε transients (ανανεώνονται όταν αλλάξουν τα αρχεία).
 */

defined( 'ABSPATH' ) || exit;

/** Διαβάζει ένα αρχείο geo (regions, prefectures, municipalities) μία φορά ανά αίτημα. */
function ellada_map_geo_file( $name ) {
	static $cache = array();
	if ( isset( $cache[ $name ] ) ) {
		return $cache[ $name ];
	}
	$path           = ELLADA_MAP_DIR . 'assets/geo/' . $name . '.json';
	$data           = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$cache[ $name ] = is_array( $data ) && ! empty( $data['features'] ) ? $data : array( 'features' => array() );
	return $cache[ $name ];
}

/** Κλειδί cache που αλλάζει μαζί με τα αρχεία geo (νέα έκδοση plugin). */
function ellada_map_geo_cache_key( $what ) {
	$path = ELLADA_MAP_DIR . 'assets/geo/regions.json';
	return 'ellada_map_' . $what . '_' . ( file_exists( $path ) ? filemtime( $path ) : 0 );
}

/** Τα ονόματα των 13 περιφερειών (name_greek), αλφαβητικά. */
function ellada_map_region_names() {
	static $names = null;
	if ( null !== $names ) {
		return $names;
	}
	$key   = ellada_map_geo_cache_key( 'region_names' );
	$names = get_transient( $key );
	if ( ! is_array( $names ) ) {
		$names = array();
		foreach ( ellada_map_geo_file( 'regions' )['features'] as $f ) {
			if ( ! empty( $f['properties']['name_greek'] ) ) {
				$names[] = $f['properties']['name_greek'];
			}
		}
		// Αλφαβητικά χωρίς τόνους, ώστε το «Ήπειρος» να μπει στο Η και όχι πρώτο.
		$plain = function ( $s ) {
			return strtr( mb_strtolower( $s ), array( 'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ό' => 'ο', 'ύ' => 'υ', 'ώ' => 'ω', 'ϊ' => 'ι', 'ϋ' => 'υ', 'ΐ' => 'ι', 'ΰ' => 'υ' ) );
		};
		usort(
			$names,
			function ( $a, $b ) use ( $plain ) {
				return strcmp( $plain( $a ), $plain( $b ) );
			}
		);
		set_transient( $key, $names, MONTH_IN_SECONDS );
	}
	return $names;
}

/** Χάρτης νομός (name_greek) => περιφέρεια (periphery_greek). */
function ellada_map_prefecture_regions() {
	$key = ellada_map_geo_cache_key( 'pref_regions' );
	$map = get_transient( $key );
	if ( ! is_array( $map ) ) {
		$map = array();
		foreach ( ellada_map_geo_file( 'prefectures' )['features'] as $f ) {
			$p = $f['properties'];
			if ( ! empty( $p['name_greek'] ) ) {
				$map[ $p['name_greek'] ] = isset( $p['periphery_greek'] ) ? $p['periphery_greek'] : '';
			}
		}
		set_transient( $key, $map, MONTH_IN_SECONDS );
	}
	return $map;
}

/** Ray casting: είναι το σημείο (x=lng, y=lat) μέσα στον δακτύλιο; */
function ellada_map_in_ring( $x, $y, array $ring ) {
	$inside = false;
	$n      = count( $ring );
	for ( $i = 0, $j = $n - 1; $i < $n; $j = $i++ ) {
		$xi = $ring[ $i ][0];
		$yi = $ring[ $i ][1];
		$xj = $ring[ $j ][0];
		$yj = $ring[ $j ][1];
		if ( ( $yi > $y ) !== ( $yj > $y ) && $x < ( $xj - $xi ) * ( $y - $yi ) / ( ( $yj - $yi ) ?: 1e-12 ) + $xi ) {
			$inside = ! $inside;
		}
	}
	return $inside;
}

/** Πολύγωνα ενός feature ως λίστα από [εξωτερικός, ...τρύπες]. */
function ellada_map_polygons( array $geometry ) {
	if ( 'Polygon' === $geometry['type'] ) {
		return array( $geometry['coordinates'] );
	}
	return 'MultiPolygon' === $geometry['type'] ? $geometry['coordinates'] : array();
}

/**
 * Σε ποια περιφέρεια πέφτει ένα σημείο. Όπως ο χάρτης: αν είναι λίγο έξω από τα
 * (απλοποιημένα) όρια, π.χ. ταβέρνα στην ακτή, παίρνει την κοντινότερη περιφέρεια.
 *
 * @return string|null name_greek της περιφέρειας ή null αν είναι εκτός Ελλάδας.
 */
function ellada_map_locate_region( $lat, $lng ) {
	static $index = null;
	if ( null === $index ) {
		$index = array();
		foreach ( ellada_map_geo_file( 'regions' )['features'] as $f ) {
			$polys = ellada_map_polygons( $f['geometry'] );
			$xs    = array();
			$ys    = array();
			foreach ( $polys as $poly ) {
				foreach ( $poly[0] as $pt ) {
					$xs[] = $pt[0];
					$ys[] = $pt[1];
				}
			}
			if ( ! $xs ) {
				continue;
			}
			$index[] = array(
				'name'  => $f['properties']['name_greek'],
				'polys' => $polys,
				'bbox'  => array( min( $xs ), min( $ys ), max( $xs ), max( $ys ) ),
			);
		}
	}

	$x   = (float) $lng;
	$y   = (float) $lat;
	$tol = 0.08;

	$near = array();
	foreach ( $index as $r ) {
		list( $x0, $y0, $x1, $y1 ) = $r['bbox'];
		if ( $x < $x0 - $tol || $x > $x1 + $tol || $y < $y0 - $tol || $y > $y1 + $tol ) {
			continue;
		}
		foreach ( $r['polys'] as $poly ) {
			if ( ellada_map_in_ring( $x, $y, $poly[0] ) ) {
				$in_hole = false;
				for ( $h = 1, $c = count( $poly ); $h < $c; $h++ ) {
					if ( ellada_map_in_ring( $x, $y, $poly[ $h ] ) ) {
						$in_hole = true;
						break;
					}
				}
				if ( ! $in_hole ) {
					return $r['name'];
				}
			}
		}
		$near[] = $r;
	}

	// Κοντινότερη περιφέρεια (απόσταση από τις κορυφές) μέσα στην ανοχή.
	$best      = null;
	$best_dist = $tol * $tol;
	foreach ( $near as $r ) {
		foreach ( $r['polys'] as $poly ) {
			foreach ( $poly[0] as $pt ) {
				$d = ( $pt[0] - $x ) * ( $pt[0] - $x ) + ( $pt[1] - $y ) * ( $pt[1] - $y );
				if ( $d < $best_dist ) {
					$best_dist = $d;
					$best      = $r['name'];
				}
			}
		}
	}
	return $best;
}

/**
 * Σε ποιον δήμο / νομό / περιφέρεια πέφτει ένα σημείο, με τον ίδιο κανόνα με τον χάρτη:
 * μέσα στο πολύγωνο, αλλιώς ο κοντινότερος δήμος σε απόσταση έως ~0.08° (σημεία στην ακτή).
 * Διαβάζει το municipalities.json (~1 MB), γι' αυτό καλείται μόνο κατά την αποθήκευση.
 *
 * @return array{region:string,prefecture:string,municipality:string}|null
 */
function ellada_map_locate_area( $lat, $lng ) {
	static $index = null;
	if ( null === $index ) {
		$index = array();
		foreach ( ellada_map_geo_file( 'municipalities' )['features'] as $f ) {
			$polys = ellada_map_polygons( $f['geometry'] );
			$x0    = INF;
			$y0    = INF;
			$x1    = -INF;
			$y1    = -INF;
			foreach ( $polys as $poly ) {
				foreach ( $poly[0] as $pt ) {
					$x0 = min( $x0, $pt[0] );
					$x1 = max( $x1, $pt[0] );
					$y0 = min( $y0, $pt[1] );
					$y1 = max( $y1, $pt[1] );
				}
			}
			if ( INF === $x0 ) {
				continue;
			}
			$index[] = array(
				'props' => $f['properties'],
				'polys' => $polys,
				'bbox'  => array( $x0, $y0, $x1, $y1 ),
			);
		}
	}
	if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
		return null;
	}

	$x   = (float) $lng;
	$y   = (float) $lat;
	$tol = 0.08;
	$hit = null;

	$near = array();
	foreach ( $index as $m ) {
		list( $x0, $y0, $x1, $y1 ) = $m['bbox'];
		if ( $x < $x0 - $tol || $x > $x1 + $tol || $y < $y0 - $tol || $y > $y1 + $tol ) {
			continue;
		}
		$near[] = $m;
		foreach ( $m['polys'] as $poly ) {
			if ( ! ellada_map_in_ring( $x, $y, $poly[0] ) ) {
				continue;
			}
			$in_hole = false;
			for ( $h = 1, $c = count( $poly ); $h < $c; $h++ ) {
				if ( ellada_map_in_ring( $x, $y, $poly[ $h ] ) ) {
					$in_hole = true;
					break;
				}
			}
			if ( ! $in_hole ) {
				$hit = $m;
				break 2;
			}
		}
	}

	if ( ! $hit ) {
		$best = $tol * $tol;
		foreach ( $near as $m ) {
			foreach ( $m['polys'] as $poly ) {
				foreach ( $poly[0] as $pt ) {
					$d = ( $pt[0] - $x ) * ( $pt[0] - $x ) + ( $pt[1] - $y ) * ( $pt[1] - $y );
					if ( $d < $best ) {
						$best = $d;
						$hit  = $m;
					}
				}
			}
		}
	}
	if ( ! $hit ) {
		return null;
	}

	$pref = isset( $hit['props']['prefecture_greek'] ) ? $hit['props']['prefecture_greek'] : '';
	$map  = ellada_map_prefecture_regions();
	return array(
		'region'       => isset( $map[ $pref ] ) ? $map[ $pref ] : '',
		'prefecture'   => $pref,
		'municipality' => $pref . '|' . $hit['props']['name_greek'],
	);
}
