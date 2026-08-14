<?php
/**
 * Scanner temaets og de aktive plugins' kildekode samt indholdet af alle
 * offentliggjorte sider/indlæg for kendte tredjepartstjenester (Google
 * Analytics, Facebook Pixel, YouTube m.fl.), så cookiepolitikken kan
 * udfyldes med det, sitet faktisk bruger — i stedet for generisk tekst.
 *
 * Fungerer bedst når man selv ejer temaet og de aktive plugins (og dermed
 * kender koden), da den ikke kan se cookies sat af rent klientside-kode,
 * den ikke genkender signaturen på.
 */

defined( 'ABSPATH' ) || exit;

function cookie_samtykke_kendte_tjenester(): array {
	return array(
		'ga4'       => array(
			'navn'         => 'Google Analytics (GA4)',
			'kategori'     => 'statistik',
			'cookies'      => '_ga, _ga_*, _gid',
			'varighed'     => 'op til 2 år',
			'beskrivelse'  => 'Bruges til at måle trafik og brugeradfærd på hjemmesiden.',
			'databehandler' => 'Google Ireland Limited',
			'signaturer'   => array( 'gtag(', 'googletagmanager.com/gtag/js', 'google-analytics.com/analytics.js', "ga('create'" ),
		),
		'gtm'       => array(
			'navn'         => 'Google Tag Manager',
			'kategori'     => 'statistik',
			'cookies'      => 'Ingen egne — bruges til at styre andre tags/scripts',
			'varighed'     => '—',
			'beskrivelse'  => 'Bruges til at styre, hvilke andre tredjeparts-scripts der indlæses på siden.',
			'databehandler' => 'Google Ireland Limited',
			'signaturer'   => array( 'googletagmanager.com/gtm.js' ),
		),
		'fbpixel'   => array(
			'navn'         => 'Meta/Facebook Pixel',
			'kategori'     => 'marketing',
			'cookies'      => '_fbp, _fbc',
			'varighed'     => 'op til 3 måneder',
			'beskrivelse'  => 'Bruges til at måle og målrette annoncer på Facebook og Instagram.',
			'databehandler' => 'Meta Platforms Ireland Limited',
			'signaturer'   => array( 'connect.facebook.net', "fbq('init'", 'fbevents.js' ),
		),
		'youtube'   => array(
			'navn'         => 'YouTube-video',
			'kategori'     => 'marketing',
			'cookies'      => 'VISITOR_INFO1_LIVE, YSC m.fl.',
			'varighed'     => 'op til flere måneder',
			'beskrivelse'  => 'Indlejret video fra YouTube. Bruges youtube-nocookie.com-varianten, sættes markant færre cookies.',
			'databehandler' => 'Google Ireland Limited (YouTube)',
			'signaturer'   => array( 'youtube.com/embed', 'youtube-nocookie.com', 'ytimg.com' ),
		),
		'gmaps'     => array(
			'navn'         => 'Google Maps',
			'kategori'     => 'marketing',
			'cookies'      => 'NID m.fl.',
			'varighed'     => 'op til 6 måneder',
			'beskrivelse'  => 'Indlejret kort fra Google Maps.',
			'databehandler' => 'Google Ireland Limited',
			'signaturer'   => array( 'maps.google.com/maps', 'maps.googleapis.com', 'google.com/maps/embed' ),
		),
		'matomo'    => array(
			'navn'         => 'Matomo (selv-hostet statistik)',
			'kategori'     => 'statistik',
			'cookies'      => '_pk_id, _pk_ses',
			'varighed'     => 'op til 13 måneder',
			'beskrivelse'  => 'Selv-hostet webstatistik-værktøj.',
			'databehandler' => 'Os selv (selv-hostet)',
			'signaturer'   => array( 'matomo.js', 'piwik.js', 'matomo.php' ),
		),
		'hotjar'    => array(
			'navn'         => 'Hotjar',
			'kategori'     => 'statistik',
			'cookies'      => '_hjSession*, _hjid',
			'varighed'     => 'op til 1 år',
			'beskrivelse'  => 'Bruges til at analysere, hvordan besøgende bruger hjemmesiden, fx via varmekort.',
			'databehandler' => 'Hotjar Ltd.',
			'signaturer'   => array( 'static.hotjar.com', 'hotjar.com/c/hotjar-' ),
		),
		'recaptcha' => array(
			'navn'         => 'Google reCAPTCHA',
			'kategori'     => 'nodvendige',
			'cookies'      => '_GRECAPTCHA',
			'varighed'     => 'op til 6 måneder',
			'beskrivelse'  => 'Bruges til at beskytte formularer mod spam og misbrug.',
			'databehandler' => 'Google Ireland Limited',
			'signaturer'   => array( 'google.com/recaptcha', 'gstatic.com/recaptcha' ),
		),
		'stripe'    => array(
			'navn'         => 'Stripe',
			'kategori'     => 'nodvendige',
			'cookies'      => '__stripe_mid, __stripe_sid',
			'varighed'     => 'op til 1 år',
			'beskrivelse'  => 'Bruges til at gennemføre betalinger og beskytte mod svindel.',
			'databehandler' => 'Stripe, Inc.',
			'signaturer'   => array( 'js.stripe.com' ),
		),
	);
}

function cookie_samtykke_kategori_navn( string $kategori ): string {
	$navne = array(
		'nodvendige' => 'Nødvendige',
		'statistik'  => 'Statistik',
		'marketing'  => 'Marketing',
	);
	return $navne[ $kategori ] ?? ucfirst( $kategori );
}

function cookie_samtykke_match_tjenester( string $indhold, array $tjenester ): array {
	$fundne = array();
	foreach ( $tjenester as $noegle => $data ) {
		foreach ( $data['signaturer'] as $signatur ) {
			if ( false !== stripos( $indhold, $signatur ) ) {
				$fundne[] = $noegle;
				break;
			}
		}
	}
	return $fundne;
}

/**
 * Temaets og de aktive plugins' mapper (ekskl. dette plugin selv).
 */
function cookie_samtykke_scan_mapper(): array {
	$mapper = array( get_template_directory() );
	$child  = get_stylesheet_directory();
	if ( ! in_array( $child, $mapper, true ) ) {
		$mapper[] = $child;
	}

	$egen_slug = strstr( plugin_basename( COOKIE_SAMTYKKE_FIL ), '/', true );

	foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
		$slug = strstr( $plugin, '/', true );
		if ( ! $slug || $egen_slug === $slug ) {
			continue;
		}
		$sti = WP_PLUGIN_DIR . '/' . $slug;
		if ( is_dir( $sti ) && ! in_array( $sti, $mapper, true ) ) {
			$mapper[] = $sti;
		}
	}
	return $mapper;
}

function cookie_samtykke_scan_kildefiler(): array {
	$fundne        = array();
	$tjenester     = cookie_samtykke_kendte_tjenester();
	$udelad_mapper = array( 'node_modules', 'vendor', '.git', 'build', 'dist' );
	$maks_filer    = 4000;
	$talt          = 0;

	foreach ( cookie_samtykke_scan_mapper() as $mappe ) {
		if ( ! is_dir( $mappe ) ) {
			continue;
		}
		try {
			$dir_iterator = new RecursiveDirectoryIterator( $mappe, FilesystemIterator::SKIP_DOTS );
			// Filtreres FØR iterator'en går ind i undermapper, så den aldrig
			// bruger tid på at gennemløbe fx node_modules (kan sagtens
			// indeholde titusindvis af filer i et tema med et build-setup).
			$filter = new RecursiveCallbackFilterIterator(
				$dir_iterator,
				function ( $current ) use ( $udelad_mapper ) {
					if ( $current->isDir() && in_array( $current->getFilename(), $udelad_mapper, true ) ) {
						return false;
					}
					return true;
				}
			);
			$iterator = new RecursiveIteratorIterator( $filter );
		} catch ( Exception $e ) {
			continue;
		}
		foreach ( $iterator as $fil ) {
			if ( $talt >= $maks_filer ) {
				break 2;
			}
			if ( ! $fil->isFile() ) {
				continue;
			}
			$ext = strtolower( $fil->getExtension() );
			if ( ! in_array( $ext, array( 'php', 'js' ), true ) ) {
				continue;
			}
			if ( $fil->getSize() > 1500000 ) {
				continue;
			}
			++$talt;
			$indhold = @file_get_contents( $fil->getPathname() );
			if ( false === $indhold ) {
				continue;
			}
			foreach ( cookie_samtykke_match_tjenester( $indhold, $tjenester ) as $noegle ) {
				$fundne[ $noegle ] = true;
			}
		}
	}
	return array_keys( $fundne );
}

function cookie_samtykke_scan_database(): array {
	$fundne    = array();
	$tjenester = cookie_samtykke_kendte_tjenester();

	// De sider, pluginet selv har genereret, må ikke scannes med — deres
	// egen forklarende tekst om fx "youtube-nocookie.com" ville ellers
	// blive fejltolket som bevis for, at siden selv bruger YouTube.
	$egne_sider = array_filter(
		array(
			(int) get_option( 'cookie_samtykke_privatliv_side', 0 ),
			(int) get_option( 'cookie_samtykke_cookiepolitik_side', 0 ),
		)
	);

	$post_ider = get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'post__not_in'   => $egne_sider,
		)
	);

	foreach ( $post_ider as $id ) {
		$indhold = get_post_field( 'post_content', $id );
		if ( ! $indhold ) {
			continue;
		}
		foreach ( cookie_samtykke_match_tjenester( $indhold, $tjenester ) as $noegle ) {
			$fundne[ $noegle ] = true;
		}
	}
	return array_keys( $fundne );
}

/**
 * Kører begge scans og gemmer dem hver for sig:
 * - "indhold" (fundet i sidernes faktiske tekst) er høj sikkerhed og
 *   bruges direkte i den genererede cookiepolitik.
 * - "kode" (fundet i temaets/pluginnernes kildekode) er kun en indikation
 *   af, hvad der er teknisk understøttet — fx kan et blok-plugin have
 *   indbygget Google Maps-understøttelse, selvom siten ikke bruger det.
 *   Vises kun til admin som info, ikke i den offentlige tekst.
 */
function cookie_samtykke_koer_scan(): array {
	$indhold = cookie_samtykke_scan_database();
	$kode    = array_values( array_diff( cookie_samtykke_scan_kildefiler(), $indhold ) );
	update_option( 'cookie_samtykke_scan_resultat', $indhold );
	update_option( 'cookie_samtykke_scan_kode_resultat', $kode );
	update_option( 'cookie_samtykke_scan_tidspunkt', time() );
	return $indhold;
}

function cookie_samtykke_haandter_scan() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Ingen adgang.' );
	}
	check_admin_referer( 'cookie_samtykke_scan' );

	cookie_samtykke_koer_scan();

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'       => 'cookie-samtykke',
				'cs_scannet' => 1,
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_post_cookie_samtykke_scan', 'cookie_samtykke_haandter_scan' );
