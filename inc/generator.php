<?php
/**
 * Automatisk oprettelse af Privatlivspolitik- og Cookiepolitik-sider med
 * udfyldt standardindhold, baseret på pluginets indstillinger. Indholdet er
 * et generelt udgangspunkt — bør altid gennemlæses og tilpasses det enkelte
 * site, inden det bruges i praksis.
 */

defined( 'ABSPATH' ) || exit;

function cookie_samtykke_pak_indhold( string $indre ): string {
	return '<!-- wp:group --><div class="wp-block-group cookie-samtykke-politik-wrap">' . "\n\n" . $indre . "\n\n" . '</div><!-- /wp:group -->';
}

/**
 * [cookie_samtykke_side_link type="cookiepolitik"]cookiepolitik[/cookie_samtykke_side_link]
 * — krydslinker mellem privatlivspolitikken og cookiepolitikken. Slår altid
 * det AKTUELLE link op, når siden vises, i stedet for at gemme et link, der
 * blev fastfrosset på det tidspunkt, siden blev genereret — så det er ligegyldigt,
 * hvilken rækkefølge de to sider bliver oprettet i. Findes den anden side
 * ikke (endnu), vises bare den rene tekst uden link.
 */
function cookie_samtykke_side_link_shortcode( $atts, $content = '' ): string {
	$atts       = shortcode_atts( array( 'type' => '' ), (array) $atts );
	$option_key = array(
		'privatliv'     => 'cookie_samtykke_privatliv_side',
		'cookiepolitik' => 'cookie_samtykke_cookiepolitik_side',
	);
	$tekst = $content ? $content : $atts['type'];

	if ( ! isset( $option_key[ $atts['type'] ] ) ) {
		return esc_html( $tekst );
	}

	$side_id = (int) get_option( $option_key[ $atts['type'] ], 0 );
	if ( ! $side_id || ! get_post( $side_id ) ) {
		return esc_html( $tekst );
	}

	return '<a href="' . esc_url( get_permalink( $side_id ) ) . '">' . esc_html( $tekst ) . '</a>';
}
add_shortcode( 'cookie_samtykke_side_link', 'cookie_samtykke_side_link_shortcode' );

function cookie_samtykke_generer_privatlivspolitik_indhold(): string {
	$virk  = cookie_samtykke_hent_virksomhed();
	$email = get_bloginfo( 'admin_email' );
	$url   = home_url( '/' );

	$dele   = array();
	$dele[] = '<!-- wp:paragraph --><p style="font-size:0.9em;opacity:0.7;">' . esc_html( 'Senest opdateret: ' . date_i18n( 'j. F Y' ) ) . '</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:paragraph --><p><em>Denne side er genereret som et generelt udgangspunkt og bør gennemgås, så den passer præcist til, hvordan ' . esc_html( $virk['navn'] ) . ' behandler personoplysninger.</em></p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Dataansvarlig</h2><!-- /wp:heading -->';
	$kontaktlinjer = array( '<li>' . esc_html( $virk['navn'] ) . '</li>' );
	if ( $virk['cvr'] ) {
		$kontaktlinjer[] = '<li>CVR: ' . esc_html( $virk['cvr'] ) . '</li>';
	}
	if ( $virk['adresse'] || $virk['postnr_by'] ) {
		$kontaktlinjer[] = '<li>' . esc_html( trim( $virk['adresse'] . ', ' . $virk['postnr_by'], ', ' ) ) . '</li>';
	}
	$kontaktlinjer[] = '<li>E-mail: <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></li>';
	if ( $virk['telefon'] ) {
		$kontaktlinjer[] = '<li>Telefon: ' . esc_html( $virk['telefon'] ) . '</li>';
	}
	$dele[] = '<!-- wp:list --><ul class="wp-block-list">' . implode( '', $kontaktlinjer ) . '</ul><!-- /wp:list -->';
	$dele[] = '<!-- wp:paragraph --><p>Har du spørgsmål til vores behandling af dine oplysninger, er du velkommen til at kontakte os på ovenstående.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Hvilke oplysninger behandler vi</h2><!-- /wp:heading -->';
	$dele[] = cookie_samtykke_byg_tabel_blok(
		array( 'Formål', 'Oplysninger', 'Retsgrundlag' ),
		array(
			array( 'Besvare henvendelser', 'Navn, e-mail, telefon og indholdet af din besked', 'Legitim interesse i at kunne besvare henvendelser (GDPR art. 6(1)(f))' ),
			array( 'Behandle tilmeldinger/formularer på hjemmesiden', 'De oplysninger, du selv angiver i den pågældende formular', 'Opfyldelse af aftale eller foranstaltninger forud for aftale (GDPR art. 6(1)(b))' ),
			array( 'Forbedre og analysere hjemmesiden', 'Cookie- og teknisk brugsdata (se cookiepolitik)', 'Samtykke (GDPR art. 6(1)(a))' ),
			array( 'Overholde lovkrav, fx bogføring', 'Fakturaoplysninger, hvis relevant', 'Retlig forpligtelse (GDPR art. 6(1)(c))' ),
		)
	);

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Cookies og lignende teknologier</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Vi bruger cookies til at få hjemmesiden til at fungere og til at forstå, hvordan den bliver brugt. Du kan læse mere om, hvilke cookies vi bruger, og hvordan du styrer dit samtykke, i vores [cookie_samtykke_side_link type="cookiepolitik"]cookiepolitik[/cookie_samtykke_side_link].</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Modtagere og databehandlere</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Vi videregiver ikke dine oplysninger til andre, medmindre det er nødvendigt for at levere vores ydelser. Vi bruger databehandlere til at drive hjemmesiden og vores forretning, fx en hosting-udbyder til at drifte hjemmesiden og en e-mail-udbyder til at sende og modtage mails. Vi har indgået databehandleraftaler med disse, som sikrer, at dine oplysninger behandles sikkert og fortroligt.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Opbevaring</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Vi opbevarer kun oplysninger, så længe det er nødvendigt for det formål, de er indsamlet til, medmindre vi er forpligtet til at opbevare dem længere, fx efter bogføringsloven.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Overførsel til lande uden for EU/EØS</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Vi bruger som udgangspunkt kun leverandører og databehandlere inden for EU/EØS. Anvender vi enkelte tjenester uden for EU/EØS (fx via cookies fra tredjepart, se cookiepolitikken), sker det altid under de fornødne garantier, herunder EU-Kommissionens standardkontraktbestemmelser.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Dine rettigheder</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Du har efter databeskyttelsesforordningen en række rettigheder i forhold til vores behandling af oplysninger om dig, herunder retten til indsigt, berigtigelse, sletning og indsigelse. Du kan få tilsendt en oversigt over dine oplysninger eller bede om at få dem slettet nedenfor. Ønsker du at gøre brug af andre rettigheder, fx berigtigelse eller indsigelse, er du velkommen til at kontakte os på <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>.</p><!-- /wp:paragraph -->';

	$dele = array_merge( $dele, cookie_samtykke_byg_dine_oplysninger_afsnit( false ) );

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Klage</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Du kan klage til Datatilsynet, <a href="https://www.datatilsynet.dk" target="_blank" rel="noopener">www.datatilsynet.dk</a>, hvis du er utilfreds med vores behandling af dine personoplysninger.</p><!-- /wp:paragraph -->';

	return implode( "\n\n", $dele );
}

/**
 * [cookie_samtykke_privatlivspolitik] — indsætter hele privatlivspolitikken
 * på en side, brugeren selv har valgt (som alternativ til at oprette en helt
 * ny side automatisk). Indholdet indeholder selv andre kortkoder (fx
 * [cookie_samtykke_slet_data]); do_shortcode() kører kun én omgang på det
 * oprindelige indhold, så indlejrede kortkoder skal udfoldes manuelt her —
 * ellers ville de bare stå som rå tekst.
 */
function cookie_samtykke_privatlivspolitik_shortcode(): string {
	return do_shortcode( cookie_samtykke_generer_privatlivspolitik_indhold() );
}
add_shortcode( 'cookie_samtykke_privatlivspolitik', 'cookie_samtykke_privatlivspolitik_shortcode' );

function cookie_samtykke_byg_tabel_blok( array $header, array $raekker, string $ekstra_klasse = '' ): string {
	$thead = '<tr>';
	foreach ( $header as $celle ) {
		$thead .= '<th>' . esc_html( $celle ) . '</th>';
	}
	$thead .= '</tr>';

	$tbody = '';
	foreach ( $raekker as $raekke ) {
		$tbody .= '<tr>';
		foreach ( $raekke as $celle ) {
			$tbody .= '<td>' . esc_html( $celle ) . '</td>';
		}
		$tbody .= '</tr>';
	}

	$tabel_klasse = trim( 'cookie-samtykke-politiktabel ' . $ekstra_klasse );

	// Bevidst UDEN klassen "wp-block-table": WordPress' egen kerne-CSS for
	// den klasse lægger ekstra kant-streger oven i vores styling, men kun på
	// sider hvor tabellen ligger direkte i det gemte indhold (ikke når den
	// indsættes via en kortkode) — det gav de to tabeller hvert sit udseende.
	return '<!-- wp:table --><figure class="cookie-samtykke-politiktabel-wrap"><table class="' . esc_attr( $tabel_klasse ) . '"><thead>' . $thead . '</thead><tbody>' . $tbody . '</tbody></table></figure><!-- /wp:table -->';
}

/**
 * [cookie_samtykke_cookie_tabel]
 * — indsætter altid den AKTUELLE "sidst scannet"-status og cookie-tabel, når
 * siden vises, i stedet for et øjebliksbillede fra det tidspunkt siden blev
 * genereret. Ellers ville en ny scanning ikke slå igennem på en allerede
 * genereret cookiepolitik, før man selv klikkede "Generér indhold igen".
 */
function cookie_samtykke_cookie_tabel_shortcode(): string {
	$tekster        = cookie_samtykke_hent_tekster();
	$kategorier     = wp_parse_args( get_option( 'cookie_samtykke_kategorier', array() ), array( 'statistik' => true, 'marketing' => true ) );
	$scan_tid       = (int) get_option( 'cookie_samtykke_scan_tidspunkt', 0 );
	$scan_noegler   = (array) get_option( 'cookie_samtykke_scan_resultat', array() );
	$alle_tjenester = cookie_samtykke_kendte_tjenester() + cookie_samtykke_platform_tjenester();
	$fundne         = array_intersect_key( $alle_tjenester, array_flip( $scan_noegler ) );

	$opdateret_tekst = $scan_tid
		? 'Sidst opdateret: ' . date_i18n( 'j. F Y', $scan_tid )
		: 'Endnu ikke scannet — se Indstillinger → Cookie-samtykke.';
	$html = '<p style="font-size:0.9em;opacity:0.7;">' . esc_html( $opdateret_tekst ) . '</p>';

	$html .= '<p><strong>' . esc_html( $tekster['nodvendige_navn'] ) . '</strong> — ' . esc_html( $tekster['nodvendige_tekst'] ) . '</p>';
	if ( $kategorier['statistik'] ) {
		$html .= '<p><strong>' . esc_html( $tekster['statistik_navn'] ) . '</strong> — ' . esc_html( $tekster['statistik_tekst'] ) . '</p>';
	}
	if ( $kategorier['marketing'] ) {
		$html .= '<p><strong>' . esc_html( $tekster['marketing_navn'] ) . '</strong> — ' . esc_html( $tekster['marketing_tekst'] ) . '</p>';
	}

	$raekker   = array();
	$raekker[] = array( 'Cookie-samtykke', cookie_samtykke_kategori_navn( 'nodvendige' ), 'Husker dit cookie-samtykke, så du ikke skal tage stilling igen ved hvert besøg.', 'cookie_samtykke', '365 dage', 'Os selv' );
	foreach ( $fundne as $tjeneste ) {
		$raekker[] = array(
			$tjeneste['navn'],
			cookie_samtykke_kategori_navn( $tjeneste['kategori'] ),
			$tjeneste['beskrivelse'],
			$tjeneste['cookies'],
			$tjeneste['varighed'],
			$tjeneste['databehandler'],
		);
	}
	$html .= cookie_samtykke_byg_tabel_blok( array( 'Navn', 'Kategori', 'Formål', 'Cookie(s)', 'Varighed', 'Udbyder' ), $raekker, 'cookie-samtykke-cookietabel' );

	if ( $scan_tid && ! $fundne ) {
		$html .= '<p>Vi har ikke fundet tegn på andre cookies i sitets indhold.</p>';
	} elseif ( ! $scan_tid ) {
		$html .= '<p>Listen er endnu ikke scannet og viser derfor kun den nødvendige funktionscookie. Scan sitet under Indstillinger → Cookie-samtykke for en mere præcis liste.</p>';
	}

	return $html;
}
add_shortcode( 'cookie_samtykke_cookie_tabel', 'cookie_samtykke_cookie_tabel_shortcode' );

function cookie_samtykke_generer_cookiepolitik_indhold(): string {
	$dele = array();

	$dele[] = '<!-- wp:paragraph --><p>En cookie er en lille tekstfil, som gemmes på din enhed, når du besøger en hjemmeside. Cookies bruges bl.a. til at få hjemmesiden til at fungere korrekt, til at huske dine valg, og til at forstå, hvordan hjemmesiden bliver brugt.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Hvilke cookies bruger vi</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:shortcode -->[cookie_samtykke_cookie_tabel]<!-- /wp:shortcode -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Sådan ændrer du dit valg</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Du kan til enhver tid ændre dit samtykke via ikonet nederst i venstre hjørne af siden, eller ved at [cookie_samtykke_link]klikke her[/cookie_samtykke_link].</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:paragraph --><p>Du kan læse mere om, hvordan vi generelt behandler personoplysninger, i vores [cookie_samtykke_side_link type="privatliv"]privatlivspolitik[/cookie_samtykke_side_link].</p><!-- /wp:paragraph -->';

	$dele = array_merge( $dele, cookie_samtykke_byg_dine_oplysninger_afsnit() );

	return implode( "\n\n", $dele );
}

/**
 * [cookie_samtykke_cookiepolitik] — samme princip som
 * [cookie_samtykke_privatlivspolitik] ovenfor: udfolder indlejrede
 * kortkoder (bl.a. [cookie_samtykke_cookie_tabel]) manuelt.
 */
function cookie_samtykke_cookiepolitik_shortcode(): string {
	return do_shortcode( cookie_samtykke_generer_cookiepolitik_indhold() );
}
add_shortcode( 'cookie_samtykke_cookiepolitik', 'cookie_samtykke_cookiepolitik_shortcode' );

/**
 * Fælles "Dine oplysninger"-afsnit med download- og sletteknap —
 * genbruges på både privatlivspolitikken og cookiepolitikken.
 */
function cookie_samtykke_byg_dine_oplysninger_afsnit( bool $med_overskrift = true ): array {
	if ( ! shortcode_exists( 'cookie_samtykke_slet_data' ) ) {
		return array();
	}
	$dele = array();
	if ( $med_overskrift ) {
		$dele[] = '<!-- wp:heading {"level":2} --><h2>Dine oplysninger</h2><!-- /wp:heading -->';
	}
	$dele[] = '<!-- wp:paragraph --><p>Har du tidligere skrevet til os via en formular på hjemmesiden, kan du få tilsendt en oversigt over de oplysninger, vi har liggende på dig, eller bede om at få dem slettet. Vi sender en bekræftelses-mail til den indtastede adresse, så vi er sikre på, at det er dig — klik på linket i mailen for at gennemføre anmodningen.</p><!-- /wp:paragraph -->';
	$dele[] = '<!-- wp:shortcode -->[cookie_samtykke_download_data]<!-- /wp:shortcode -->';
	$dele[] = '<!-- wp:shortcode -->[cookie_samtykke_slet_data]<!-- /wp:shortcode -->';
	return $dele;
}

/**
 * Genererer/opdaterer den ene af de to sider (privatliv eller cookiepolitik).
 * Fælles kerne for både enkelt- og "begge sider"-knapperne, så de altid gør
 * præcis det samme.
 */
function cookie_samtykke_generer_side( string $type ) {
	if ( 'privatliv' === $type ) {
		$option_key = 'cookie_samtykke_privatliv_side';
		$titel      = 'Privatlivspolitik';
		$indhold    = cookie_samtykke_generer_privatlivspolitik_indhold();
	} else {
		$option_key = 'cookie_samtykke_cookiepolitik_side';
		$titel      = 'Cookiepolitik';
		$indhold    = cookie_samtykke_generer_cookiepolitik_indhold();
	}
	$indhold = cookie_samtykke_pak_indhold( $indhold );

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

	cookie_samtykke_generer_side( $type );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'         => 'cookie-samtykke',
				'fane'         => 'sider',
				'cs_genereret' => $type,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_post_cookie_samtykke_generer_side', 'cookie_samtykke_haandter_generer_side' );

function cookie_samtykke_haandter_generer_begge() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Ingen adgang.' );
	}
	check_admin_referer( 'cookie_samtykke_generer_begge' );

	cookie_samtykke_generer_side( 'privatliv' );
	cookie_samtykke_generer_side( 'cookiepolitik' );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'         => 'cookie-samtykke',
				'fane'         => 'sider',
				'cs_genereret' => 'begge',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_post_cookie_samtykke_generer_begge', 'cookie_samtykke_haandter_generer_begge' );
