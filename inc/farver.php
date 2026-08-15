<?php
/**
 * Farvestyring — automatisk uddrag fra det aktive temas theme.json
 * (virker for alle blok-temaer uden konfiguration), med manuel
 * override i indstillingerne som fallback for klassiske temaer eller
 * hvis den automatiske gætning ikke rammer rigtigt.
 */

defined( 'ABSPATH' ) || exit;

const COOKIE_SAMTYKKE_STANDARD_FARVER = array(
	'baggrund' => '#1B1B1B',
	'tekst'    => '#FFFFFF',
	'accent'   => '#F7C24B',
	'accent_tekst' => '#1B1B1B',
);

/**
 * Gætter en fornuftig "accent"-farve ud fra temaets palet: bruger
 * link-farven hvis den er sat (næsten altid temaets brand-/accentfarve
 * i praksis), ellers den mest mættede farve i paletten der ikke er
 * tæt på ren hvid/sort.
 */
function cookie_samtykke_gaet_accent( string $baggrund ): string {
	$link = wp_get_global_styles( array( 'elements', 'link', 'color', 'text' ) );
	if ( $link && cookie_samtykke_er_hex( $link ) ) {
		return cookie_samtykke_resolve_var( $link );
	}

	$palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
	if ( is_array( $palette ) && ! empty( $palette ) ) {
		$bedste     = '';
		$hoejeste_s = -1;
		foreach ( $palette as $farve ) {
			$hex = $farve['color'] ?? '';
			if ( ! cookie_samtykke_er_hex( $hex ) ) {
				continue;
			}
			list( , $s, $l ) = cookie_samtykke_hex_til_hsl( $hex );
			if ( $l > 92 || $l < 8 ) { // spring næsten-hvid/næsten-sort over
				continue;
			}
			if ( $s > $hoejeste_s ) {
				$hoejeste_s = $s;
				$bedste     = $hex;
			}
		}
		if ( $bedste ) {
			return $bedste;
		}
	}

	return COOKIE_SAMTYKKE_STANDARD_FARVER['accent'];
}

function cookie_samtykke_er_hex( $vaerdi ): bool {
	return is_string( $vaerdi ) && (bool) preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', trim( $vaerdi ) );
}

/**
 * theme.json kan indeholde "var(--wp--preset--color--noget)" i stedet
 * for en hex-værdi direkte. Prøv at slå den op i det aktive temas
 * palet; kan den ikke findes, returneres en fornuftig standardfarve.
 */
function cookie_samtykke_resolve_var( string $vaerdi, string $fallback = COOKIE_SAMTYKKE_STANDARD_FARVER['accent'] ): string {
	if ( cookie_samtykke_er_hex( $vaerdi ) ) {
		return $vaerdi;
	}
	if ( preg_match( '/--wp--preset--color--([a-z0-9-]+)/i', $vaerdi, $m ) ) {
		$slug    = $m[1];
		$palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
		if ( is_array( $palette ) ) {
			foreach ( $palette as $farve ) {
				if ( ( $farve['slug'] ?? '' ) === $slug && cookie_samtykke_er_hex( $farve['color'] ?? '' ) ) {
					return $farve['color'];
				}
			}
		}
	}
	return $fallback;
}

function cookie_samtykke_hex_til_hsl( string $hex ): array {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
	$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
	$b = hexdec( substr( $hex, 4, 2 ) ) / 255;

	$max = max( $r, $g, $b );
	$min = min( $r, $g, $b );
	$l   = ( $max + $min ) / 2;

	if ( $max === $min ) {
		$h = 0;
		$s = 0;
	} else {
		$d = $max - $min;
		$s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
		switch ( $max ) {
			case $r:
				$h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 );
				break;
			case $g:
				$h = ( $b - $r ) / $d + 2;
				break;
			default:
				$h = ( $r - $g ) / $d + 4;
		}
		$h /= 6;
	}

	return array( round( $h * 360 ), round( $s * 100 ), round( $l * 100 ) );
}

/**
 * Regner ud om sort eller hvid tekst læses bedst oven på en given
 * baggrundsfarve (simpel lysstyrke-tjek).
 */
function cookie_samtykke_kontrast_tekst( string $hex ): string {
	list( , , $l ) = cookie_samtykke_hex_til_hsl( $hex );
	return $l > 55 ? '#1B1B1B' : '#FFFFFF';
}

/**
 * De endelige farver banneret skal bruge — enten temaets egne
 * (automatisk) eller dem der er sat i indstillingerne (manuel).
 */
function cookie_samtykke_hent_farver(): array {
	$tilstand = get_option( 'cookie_samtykke_farvetilstand', 'auto' );

	if ( 'manuel' === $tilstand ) {
		$manuel = get_option( 'cookie_samtykke_manuelle_farver', array() );
		return wp_parse_args( is_array( $manuel ) ? $manuel : array(), COOKIE_SAMTYKKE_STANDARD_FARVER );
	}

	if ( ! function_exists( 'wp_get_global_styles' ) ) {
		return COOKIE_SAMTYKKE_STANDARD_FARVER;
	}

	$baggrund_raw = wp_get_global_styles( array( 'color', 'text' ) ); // banneret vender temaets farver om: mørk baggrund, lys tekst virker bedst som en bjælke der skiller sig ud
	$tekst_raw    = wp_get_global_styles( array( 'color', 'background' ) );

	// Klassiske temaer uden theme.json kan give et array tilbage (ingen leaf-værdi at hente), ikke en hex-streng
	$baggrund_raw = is_string( $baggrund_raw ) ? $baggrund_raw : '';
	$tekst_raw    = is_string( $tekst_raw ) ? $tekst_raw : '';

	$baggrund = cookie_samtykke_er_hex( $baggrund_raw ) ? $baggrund_raw : cookie_samtykke_resolve_var( $baggrund_raw, COOKIE_SAMTYKKE_STANDARD_FARVER['baggrund'] );
	$tekst    = cookie_samtykke_er_hex( $tekst_raw ) ? $tekst_raw : cookie_samtykke_resolve_var( $tekst_raw, COOKIE_SAMTYKKE_STANDARD_FARVER['tekst'] );

	if ( ! cookie_samtykke_er_hex( $baggrund ) ) {
		$baggrund = COOKIE_SAMTYKKE_STANDARD_FARVER['baggrund'];
	}
	if ( ! cookie_samtykke_er_hex( $tekst ) ) {
		$tekst = COOKIE_SAMTYKKE_STANDARD_FARVER['tekst'];
	}

	$accent = cookie_samtykke_gaet_accent( $baggrund );

	return array(
		'baggrund'     => $baggrund,
		'tekst'        => $tekst,
		'accent'       => $accent,
		'accent_tekst' => cookie_samtykke_kontrast_tekst( $accent ),
	);
}
