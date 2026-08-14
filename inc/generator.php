<?php
/**
 * Automatisk oprettelse af Privatlivspolitik- og Cookiepolitik-sider med
 * udfyldt standardindhold, baseret på pluginets indstillinger. Indholdet er
 * et generelt udgangspunkt — bør altid gennemlæses og tilpasses det enkelte
 * site, inden det bruges i praksis.
 */

defined( 'ABSPATH' ) || exit;

function cookie_samtykke_pak_indhold( string $indre ): string {
	return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"56px","bottom":"72px"}}}} --><div class="wp-block-group" style="padding-top:56px;padding-bottom:72px">' . "\n\n" . $indre . "\n\n" . '</div><!-- /wp:group -->';
}

function cookie_samtykke_generer_privatlivspolitik_indhold(): string {
	$virk  = cookie_samtykke_hent_virksomhed();
	$email = get_bloginfo( 'admin_email' );
	$url   = home_url( '/' );

	$cookiepolitik_id   = (int) get_option( 'cookie_samtykke_cookiepolitik_side', 0 );
	$cookiepolitik_link = ( $cookiepolitik_id && get_post( $cookiepolitik_id ) ) ? get_permalink( $cookiepolitik_id ) : '';

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
	$dele[] = '<!-- wp:paragraph --><p>Vi bruger cookies til at få hjemmesiden til at fungere og til at forstå, hvordan den bliver brugt. Du kan læse mere om, hvilke cookies vi bruger, og hvordan du styrer dit samtykke, i vores' . ( $cookiepolitik_link ? ' <a href="' . esc_url( $cookiepolitik_link ) . '">cookiepolitik</a>.' : ' cookiepolitik.' ) . '</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Modtagere og databehandlere</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Vi videregiver ikke dine oplysninger til andre, medmindre det er nødvendigt for at levere vores ydelser. Vi bruger databehandlere til at drive hjemmesiden og vores forretning, fx en hosting-udbyder til at drifte hjemmesiden og en e-mail-udbyder til at sende og modtage mails. Vi har indgået databehandleraftaler med disse, som sikrer, at dine oplysninger behandles sikkert og fortroligt.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Opbevaring</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Vi opbevarer kun oplysninger, så længe det er nødvendigt for det formål, de er indsamlet til, medmindre vi er forpligtet til at opbevare dem længere, fx efter bogføringsloven.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Overførsel til lande uden for EU/EØS</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Vi bruger som udgangspunkt kun leverandører og databehandlere inden for EU/EØS. Anvender vi enkelte tjenester uden for EU/EØS (fx via cookies fra tredjepart, se cookiepolitikken), sker det altid under de fornødne garantier, herunder EU-Kommissionens standardkontraktbestemmelser.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Dine rettigheder</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Du har efter databeskyttelsesforordningen en række rettigheder i forhold til vores behandling af oplysninger om dig. Du kan bl.a. bede om indsigt i, berigtigelse af eller sletning af dine personoplysninger, og du kan gøre indsigelse mod behandlingen. Kontakt os på <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>, hvis du vil gøre brug af dine rettigheder.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Klage</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Du kan klage til Datatilsynet, <a href="https://www.datatilsynet.dk" target="_blank" rel="noopener">www.datatilsynet.dk</a>, hvis du er utilfreds med vores behandling af dine personoplysninger.</p><!-- /wp:paragraph -->';

	return cookie_samtykke_pak_indhold( implode( "\n\n", $dele ) );
}

function cookie_samtykke_byg_tabel_blok( array $header, array $raekker ): string {
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

	return '<!-- wp:table --><figure class="wp-block-table"><table><thead>' . $thead . '</thead><tbody>' . $tbody . '</tbody></table></figure><!-- /wp:table -->';
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

	$opdateret_tekst = $scan_tid
		? 'Sidst opdateret: ' . date_i18n( 'j. F Y', $scan_tid )
		: 'Endnu ikke scannet — se Indstillinger → Cookie-samtykke.';
	$dele[] = '<!-- wp:paragraph --><p style="font-size:0.9em;opacity:0.7;">' . esc_html( $opdateret_tekst ) . '</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:paragraph --><p>En cookie er en lille tekstfil, som gemmes på din enhed, når du besøger en hjemmeside. Cookies bruges bl.a. til at få hjemmesiden til at fungere korrekt, til at huske dine valg, og til at forstå, hvordan hjemmesiden bliver brugt.</p><!-- /wp:paragraph -->';

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Hvilke cookies bruger vi</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p><strong>' . esc_html( $tekster['nodvendige_navn'] ) . '</strong> — ' . esc_html( $tekster['nodvendige_tekst'] ) . '</p><!-- /wp:paragraph -->';
	if ( $kategorier['statistik'] ) {
		$dele[] = '<!-- wp:paragraph --><p><strong>' . esc_html( $tekster['statistik_navn'] ) . '</strong> — ' . esc_html( $tekster['statistik_tekst'] ) . '</p><!-- /wp:paragraph -->';
	}
	if ( $kategorier['marketing'] ) {
		$dele[] = '<!-- wp:paragraph --><p><strong>' . esc_html( $tekster['marketing_navn'] ) . '</strong> — ' . esc_html( $tekster['marketing_tekst'] ) . '</p><!-- /wp:paragraph -->';
	}

	$raekker   = array();
	$raekker[] = array( 'Nødvendig funktionscookie', cookie_samtykke_kategori_navn( 'nodvendige' ), 'cookie_samtykke', '365 dage', 'Kun os selv' );
	foreach ( $fundne as $tjeneste ) {
		$raekker[] = array(
			$tjeneste['navn'],
			cookie_samtykke_kategori_navn( $tjeneste['kategori'] ),
			$tjeneste['cookies'],
			$tjeneste['varighed'],
			$tjeneste['databehandler'],
		);
	}
	$dele[] = cookie_samtykke_byg_tabel_blok( array( 'Navn', 'Kategori', 'Cookie(s)', 'Varighed', 'Udbyder' ), $raekker );

	if ( $scan_tid && ! $fundne ) {
		$dele[] = '<!-- wp:paragraph --><p>Vi har ikke fundet tegn på andre cookies i sitets indhold.</p><!-- /wp:paragraph -->';
	} elseif ( ! $scan_tid ) {
		$dele[] = '<!-- wp:paragraph --><p>Listen er endnu ikke scannet og viser derfor kun den nødvendige funktionscookie. Scan sitet under Indstillinger → Cookie-samtykke for en mere præcis liste.</p><!-- /wp:paragraph -->';
	}

	$dele[] = '<!-- wp:heading {"level":2} --><h2>Sådan ændrer du dit valg</h2><!-- /wp:heading -->';
	$dele[] = '<!-- wp:paragraph --><p>Du kan til enhver tid ændre dit samtykke via ikonet nederst i venstre hjørne af siden, eller ved at [cookie_samtykke_link]klikke her[/cookie_samtykke_link].</p><!-- /wp:paragraph -->';
	if ( $privatliv_link ) {
		$dele[] = '<!-- wp:paragraph --><p>Du kan læse mere om, hvordan vi generelt behandler personoplysninger, i vores <a href="' . esc_url( $privatliv_link ) . '">privatlivspolitik</a>.</p><!-- /wp:paragraph -->';
	}

	if ( shortcode_exists( 'cookie_samtykke_slet_data' ) ) {
		$dele[] = '<!-- wp:heading {"level":2} --><h2>Anmod om sletning af dine oplysninger</h2><!-- /wp:heading -->';
		$dele[] = '<!-- wp:paragraph --><p>Har du tidligere skrevet til os via en formular på hjemmesiden, kan du bede om at få dine oplysninger slettet. Vi sender en bekræftelses-mail, så vi er sikre på, at det er dig — klik på linket i mailen, så slettes oplysningerne automatisk.</p><!-- /wp:paragraph -->';
		$dele[] = '<!-- wp:shortcode -->[cookie_samtykke_slet_data]<!-- /wp:shortcode -->';
	}

	return cookie_samtykke_pak_indhold( implode( "\n\n", $dele ) );
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
