<?php
/**
 * Offentligt API til at gate scripts efter samtykke-kategori — det
 * andre plugins/temaer skal bruge for at tilføje fx Google Analytics
 * eller en Facebook-pixel korrekt.
 *
 * Eksempel — GA4 der først indlæses efter samtykke til statistik:
 *
 *   add_action( 'wp_head', function () {
 *       cookie_samtykke_script( 'statistik', 'https://www.googletagmanager.com/gtag/js?id=G-XXXX' );
 *       cookie_samtykke_script( 'statistik', '', "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-XXXX');" );
 *   } );
 */

defined( 'ABSPATH' ) || exit;

/**
 * Har den besøgende givet samtykke til en given kategori? Læser
 * cookien direkte, så det kan bruges server-side (fx for slet ikke at
 * enqueue et script hvis der alligevel ikke er samtykke).
 */
function cookie_samtykke_har_samtykke( string $kategori ): bool {
	if ( 'nodvendige' === $kategori ) {
		return true;
	}
	if ( empty( $_COOKIE['cookie_samtykke'] ) ) {
		return false;
	}
	$data = json_decode( wp_unslash( $_COOKIE['cookie_samtykke'] ), true );
	return ! empty( $data[ $kategori ] );
}

/**
 * Udskriver et script-tag der er "spærret" indtil brugeren har givet
 * samtykke til $kategori. Nødvendige scripts køres altid med det
 * samme; alt andet får type="text/plain" og aktiveres først af
 * consent.js, når/hvis samtykke gives.
 */
function cookie_samtykke_script( string $kategori, string $src = '', string $inline = '', array $attributter = array() ): void {
	if ( 'nodvendige' === $kategori ) {
		if ( $src ) {
			printf( '<script src="%s"></script>' . "\n", esc_url( $src ) );
		} elseif ( $inline ) {
			echo '<script>' . $inline . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rå JS fra udvikleren, ikke brugerinput
		}
		return;
	}

	$attr_str = '';
	foreach ( $attributter as $key => $val ) {
		$attr_str .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $val ) );
	}

	if ( $src ) {
		printf(
			'<script type="text/plain" data-cs-kategori="%s" data-cs-src="%s"%s></script>' . "\n",
			esc_attr( $kategori ),
			esc_url( $src ),
			$attr_str // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- allerede escaped ovenfor
		);
		return;
	}

	printf(
		'<script type="text/plain" data-cs-kategori="%s"%s>%s</script>' . "\n",
		esc_attr( $kategori ),
		$attr_str, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- allerede escaped ovenfor
		$inline // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rå JS fra udvikleren, ikke brugerinput
	);
}
