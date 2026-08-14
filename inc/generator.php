<?php
/**
 * Automatisk oprettelse af Privatlivspolitik- og Cookiepolitik-sider med
 * udfyldt standardindhold, baseret på pluginets indstillinger. Indholdet er
 * et generelt udgangspunkt — bør altid gennemlæses og tilpasses det enkelte
 * site, inden det bruges i praksis.
 */

defined( 'ABSPATH' ) || exit;

function cookie_samtykke_generer_privatlivspolitik_indhold(): string {
	$navn  = get_bloginfo( 'name' );
	$email = get_bloginfo( 'admin_email' );
	$url   = home_url( '/' );

	$cookiepolitik_id   = (int) get_option( 'cookie_samtykke_cookiepolitik_side', 0 );
	$cookiepolitik_link = ( $cookiepolitik_id && get_post( $cookiepolitik_id ) ) ? get_permalink( $cookiepolitik_id ) : '';
	$cookie_saetning     = $cookiepolitik_link
		? ' Du kan læse mere om, hvilke cookies vi bruger, og hvordan du styrer dit samtykke, i vores <a href="' . esc_url( $cookiepolitik_link ) . '">cookiepolitik</a>.'
		: ' Du kan læse mere om, hvilke cookies vi bruger, og hvordan du styrer dit samtykke, i vores cookiepolitik.';

	$dele   = array();
	$dele[] = '<!-- wp:paragraph --><p><em>Denne side er genereret som et generelt udgangspunkt og bør gennemgås, så den passer præcist til, hvordan ' . esc_html( $navn ) . ' behandler personoplysninger.</em></p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:heading {"level":2} --><h2>Dataansvarlig</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>' . esc_html( $navn ) . ' er dataansvarlig for behandlingen af de personoplysninger, vi indsamler via ' . esc_html( untrailingslashit( $url ) ) . '. Har du spørgsmål til vores behandling af dine oplysninger, kan du kontakte os på <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>.</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:heading {"level":2} --><h2>Hvilke oplysninger indsamler vi</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Når du udfylder en formular på hjemmesiden — fx en kontakt- eller tilmeldingsformular — indsamler vi de oplysninger, du selv angiver, såsom navn, e-mail, telefonnummer og eventuelle øvrige oplysninger, du skriver i beskeden.</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:paragraph --><p>Vi bruger desuden cookies til at få hjemmesiden til at fungere og til at forstå, hvordan den bliver brugt.' . $cookie_saetning . '</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:heading {"level":2} --><h2>Hvor længe opbevarer vi oplysninger</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Vi opbevarer kun oplysninger, så længe det er nødvendigt for det formål, de er indsamlet til, medmindre vi er forpligtet til at opbevare dem længere, fx efter bogføringsloven.</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:heading {"level":2} --><h2>Dine rettigheder</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Du har efter databeskyttelsesforordningen en række rettigheder i forhold til vores behandling af oplysninger om dig. Du kan bl.a. bede om indsigt i, berigtigelse af eller sletning af dine personoplysninger, og du kan gøre indsigelse mod behandlingen. Kontakt os på <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>, hvis du vil gøre brug af dine rettigheder.</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:paragraph --><p>Du kan også klage til Datatilsynet, <a href="https://www.datatilsynet.dk" target="_blank" rel="noopener">www.datatilsynet.dk</a>, hvis du er utilfreds med vores behandling af dine personoplysninger.</p><!-- /wp:paragraph -->';

	return implode( "\n\n", $dele );
}

function cookie_samtykke_generer_cookiepolitik_indhold(): string {
	$tekster    = cookie_samtykke_hent_tekster();
	$kategorier = wp_parse_args( get_option( 'cookie_samtykke_kategorier', array() ), array( 'statistik' => true, 'marketing' => true ) );

	$privatliv_id   = (int) get_option( 'cookie_samtykke_privatliv_side', 0 );
	$privatliv_link = ( $privatliv_id && get_post( $privatliv_id ) ) ? get_permalink( $privatliv_id ) : '';

	$scan_tid       = (int) get_option( 'cookie_samtykke_scan_tidspunkt', 0 );
	$scan_noegler   = (array) get_option( 'cookie_samtykke_scan_resultat', array() );
	$alle_tjenester = cookie_samtykke_kendte_tjenester();
	$fundne         = array_intersect_key( $alle_tjenester, array_flip( $scan_noegler ) );

	$dele = array();

	if ( $scan_tid ) {
		$dele[] = '<!-- wp:paragraph --><p><em>Denne side er sidst opdateret ud fra en automatisk scanning af sitets kode og indhold den ' . esc_html( date_i18n( 'j. F Y', $scan_tid ) ) . '. Tilføjer I nye tjenester senere (fx et nyt analyse- eller annonceværktøj), bør I scanne igen under Indstillinger → Cookie-samtykke og generere siden på ny.</em></p><!-- /wp:paragraph -->';
	} else {
		$dele[] = '<!-- wp:paragraph --><p><em>Denne side er genereret som et generelt udgangspunkt og bør gennemgås, så den passer præcist til de cookies, hjemmesiden faktisk bruger. Under Indstillinger → Cookie-samtykke kan I scanne sitet for kendte tredjepartstjenester og generere siden igen med et mere præcist indhold.</em></p><!-- /wp:paragraph -->';
	}

	$dele[] = '<!-- wp:paragraph --><p>En cookie er en lille tekstfil, som gemmes på din enhed, når du besøger en hjemmeside. Cookies bruges bl.a. til at få hjemmesiden til at fungere korrekt, til at huske dine valg, og til at forstå, hvordan hjemmesiden bliver brugt.</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:heading {"level":2} --><h2>Hvilke cookies bruger vi</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p><strong>' . esc_html( $tekster['nodvendige_navn'] ) . '</strong> — ' . esc_html( $tekster['nodvendige_tekst'] ) . '</p><!-- /wp:paragraph -->';
	if ( $kategorier['statistik'] ) {
		$dele[] = '<!-- wp:paragraph --><p><strong>' . esc_html( $tekster['statistik_navn'] ) . '</strong> — ' . esc_html( $tekster['statistik_tekst'] ) . '</p><!-- /wp:paragraph -->';
	}
	if ( $kategorier['marketing'] ) {
		$dele[] = '<!-- wp:paragraph --><p><strong>' . esc_html( $tekster['marketing_navn'] ) . '</strong> — ' . esc_html( $tekster['marketing_tekst'] ) . '</p><!-- /wp:paragraph -->';
	}

	if ( $scan_tid ) {
		$dele[] = '<!-- wp:heading {"level":2} --><h2>Tjenester vi har fundet på sitet</h2><!-- /wp:heading -->';
		if ( $fundne ) {
			foreach ( $fundne as $tjeneste ) {
				$dele[] = '<!-- wp:paragraph --><p><strong>' . esc_html( $tjeneste['navn'] ) . '</strong> (' . esc_html( cookie_samtykke_kategori_navn( $tjeneste['kategori'] ) ) . ') — ' . esc_html( $tjeneste['beskrivelse'] ) . ' Cookies: ' . esc_html( $tjeneste['cookies'] ) . '. Varighed: ' . esc_html( $tjeneste['varighed'] ) . '.</p><!-- /wp:paragraph -->';
			}
		} else {
			$dele[] = '<!-- wp:paragraph --><p>Vi har ikke fundet tegn på tredjeparts-cookies i sitets kode eller indhold ud over de nødvendige funktioner.</p><!-- /wp:paragraph -->';
		}
	}

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Hvor længe gemmes dit samtykke</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Dit valg gemmes i en cookie i op til 365 dage, hvorefter du bliver spurgt igen.</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:heading {"level":2} --><h2>Sådan ændrer du dit valg</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Du kan til enhver tid ændre dit samtykke ved at klikke på cookie-ikonet nederst på siden, eller via linket her: [cookie_samtykke_link]Ændr cookie-indstillinger[/cookie_samtykke_link]</p><!-- /wp:paragraph -->';
	if ( $privatliv_link ) {
		$dele[] = '<!-- wp:paragraph --><p>Du kan læse mere om, hvordan vi generelt behandler personoplysninger, i vores <a href="' . esc_url( $privatliv_link ) . '">privatlivspolitik</a>.</p><!-- /wp:paragraph -->';
	}

	return implode( "\n\n", $dele );
}

function cookie_samtykke_haandter_generer_side() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Ingen adgang.' );
	}
	check_admin_referer( 'cookie_samtykke_generer_side' );

	$type = isset( $_POST['cs_type'] ) ? sanitize_key( wp_unslash( $_POST['cs_type'] ) ) : '';
	if ( ! in_array( $type, array( 'privatliv', 'cookiepolitik' ), true ) ) {
		wp_die( 'Ugyldig type.' );
	}

	if ( 'privatliv' === $type ) {
		$option_key = 'cookie_samtykke_privatliv_side';
		$titel      = 'Privatlivspolitik';
		$indhold    = cookie_samtykke_generer_privatlivspolitik_indhold();
	} else {
		$option_key = 'cookie_samtykke_cookiepolitik_side';
		$titel      = 'Cookiepolitik';
		$indhold    = cookie_samtykke_generer_cookiepolitik_indhold();
	}

	$eksisterende_id = (int) get_option( $option_key, 0 );

	if ( $eksisterende_id && get_post( $eksisterende_id ) ) {
		wp_update_post(
			array(
				'ID'           => $eksisterende_id,
				'post_content' => $indhold,
			)
		);
	} else {
		$side_id = wp_insert_post(
			array(
				'post_title'   => $titel,
				'post_content' => $indhold,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
		if ( $side_id && ! is_wp_error( $side_id ) ) {
			update_option( $option_key, $side_id );
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'         => 'cookie-samtykke',
				'cs_genereret' => $type,
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_post_cookie_samtykke_generer_side', 'cookie_samtykke_haandter_generer_side' );
