<?php
/**
 * Selvbetjente data-anmodninger ("retten til indsigt" og "retten til at
 * blive glemt"): besøgende indtaster deres e-mail, bekræfter via et link
 * i en mail (for at undgå at man kan få indsigt i eller slette andres
 * data), og ved bekræftelse enten sendes en tekstfil med det, sitet har
 * liggende, eller fyres et generisk action hook af, som det enkelte site
 * selv hægter sin oprydning på — pluginet ved ikke selv, hvor et givent
 * sites personoplysninger ligger (fx hvilke CPT'er/meta-felter), så det
 * er bevidst holdt uden for pluginet og overladt til temaet/sitet:
 *
 *   // Til indsigt — returnér det, sitet gemmer på $email:
 *   add_filter( 'cookie_samtykke_hent_bruger_data', function( $poster, $email ) {
 *       $poster[] = array(
 *           'titel'  => 'Kontaktformular (1. januar 2026)',
 *           'felter' => array( 'Navn' => 'Jane', 'E-mail' => $email ),
 *       );
 *       return $poster;
 *   }, 10, 2 );
 *
 *   // Til sletning — slet/anonymisér det, sitet gemmer på $email:
 *   add_action( 'cookie_samtykke_slet_bruger_data', function( $email ) {
 *       // ...
 *   } );
 */

defined( 'ABSPATH' ) || exit;

function cookie_samtykke_registrer_cpt_anmodning() {
	register_post_type(
		'cs_anmodning',
		array(
			'labels'             => array(
				'name'          => 'Anmodninger',
				'singular_name' => 'Anmodning',
				'all_items'     => 'Anmodninger',
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'cookie-samtykke',
			'show_in_rest'       => false,
			'supports'           => array( 'title' ),
			'has_archive'        => false,
			'capabilities'       => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'       => true,
		)
	);
}
add_action( 'init', 'cookie_samtykke_registrer_cpt_anmodning' );

function cookie_samtykke_anmodning_admin_columns( array $columns ): array {
	unset( $columns['title'] );
	$columns['email']   = 'E-mail';
	$columns['type']    = 'Type';
	$columns['status']  = 'Status';
	$columns['dato']    = 'Modtaget';
	$columns['udfoert'] = 'Udført';
	return $columns;
}
add_filter( 'manage_cs_anmodning_posts_columns', 'cookie_samtykke_anmodning_admin_columns' );

function cookie_samtykke_anmodning_admin_column_content( string $column, int $post_id ): void {
	if ( 'email' === $column ) {
		echo esc_html( get_post_meta( $post_id, 'email', true ) );
	} elseif ( 'type' === $column ) {
		echo esc_html( 'indsigt' === get_post_meta( $post_id, 'type', true ) ? 'Indsigt (download)' : 'Sletning' );
	} elseif ( 'status' === $column ) {
		$status = get_post_meta( $post_id, 'status', true );
		$navne  = array(
			'afventer' => 'Afventer bekræftelse',
			'udfoert'  => 'Udført',
			'udloebet' => 'Udløbet',
		);
		echo esc_html( $navne[ $status ] ?? $status );
	} elseif ( 'dato' === $column ) {
		echo esc_html( get_the_date( 'j. F Y', $post_id ) );
	} elseif ( 'udfoert' === $column ) {
		$tid = (int) get_post_meta( $post_id, 'bekraeftet_dato', true );
		echo esc_html( $tid ? date_i18n( 'j. F Y', $tid ) : '—' );
	}
}
add_action( 'manage_cs_anmodning_posts_custom_column', 'cookie_samtykke_anmodning_admin_column_content', 10, 2 );

/**
 * Fælles formular-markup til begge shortcodes — kun teksten og
 * action-feltet er forskellige.
 */
function cookie_samtykke_anmodning_formular( string $type, string $knap_tekst, string $felt_id ): string {
	ob_start();
	if ( isset( $_GET['cs_anmodning'] ) && $type . '_modtaget' === $_GET['cs_anmodning'] ) {
		echo '<p class="cookie-samtykke-melding">Tjek din e-mail — vi har sendt dig et link, du skal klikke på for at bekræfte.</p>';
	} elseif ( isset( $_GET['cs_anmodning'] ) && $type . '_ugyldig' === $_GET['cs_anmodning'] ) {
		echo '<p class="cookie-samtykke-melding cookie-samtykke-melding--fejl">Det ligner ikke en gyldig e-mailadresse. Prøv igen.</p>';
	}
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cookie-samtykke-sletteformular">
		<input type="hidden" name="action" value="cookie_samtykke_anmod_data">
		<input type="hidden" name="cs_anmodning_type" value="<?php echo esc_attr( $type ); ?>">
		<?php wp_nonce_field( 'cookie_samtykke_anmod_data' ); ?>
		<div class="cookie-samtykke-sletteformular__felt">
			<label for="<?php echo esc_attr( $felt_id ); ?>">Din e-mail</label>
			<input type="email" id="<?php echo esc_attr( $felt_id ); ?>" name="cs_email" required>
		</div>
		<p style="position:absolute;left:-9999px;" aria-hidden="true">
			<label>Lad dette felt stå tomt<input type="text" name="cs_website" tabindex="-1" autocomplete="off"></label>
		</p>
		<button type="submit" class="cookie-samtykke-sletteformular__knap"><?php echo esc_html( $knap_tekst ); ?></button>
	</form>
	<?php
	return ob_get_clean();
}

function cookie_samtykke_slet_data_shortcode(): string {
	return cookie_samtykke_anmodning_formular( 'sletning', 'Anmod om sletning', 'cs-slet-email' );
}
add_shortcode( 'cookie_samtykke_slet_data', 'cookie_samtykke_slet_data_shortcode' );

function cookie_samtykke_download_data_shortcode(): string {
	return cookie_samtykke_anmodning_formular( 'indsigt', 'Download mine oplysninger', 'cs-indsigt-email' );
}
add_shortcode( 'cookie_samtykke_download_data', 'cookie_samtykke_download_data_shortcode' );

function cookie_samtykke_haandter_anmod_data() {
	$redirect = wp_get_referer() ?: home_url( '/' );
	$type     = isset( $_POST['cs_anmodning_type'] ) && 'indsigt' === $_POST['cs_anmodning_type'] ? 'indsigt' : 'sletning';

	if ( ! empty( $_POST['cs_website'] ) ) {
		wp_safe_redirect( $redirect );
		exit;
	}
	check_admin_referer( 'cookie_samtykke_anmod_data' );

	$email = isset( $_POST['cs_email'] ) ? sanitize_email( wp_unslash( $_POST['cs_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'cs_anmodning', $type . '_ugyldig', $redirect ) );
		exit;
	}

	$token   = wp_generate_password( 32, false, false );
	$post_id = wp_insert_post(
		array(
			'post_title'  => $email,
			'post_type'   => 'cs_anmodning',
			'post_status' => 'publish',
		)
	);

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, 'email', $email );
		update_post_meta( $post_id, 'type', $type );
		update_post_meta( $post_id, 'status', 'afventer' );
		update_post_meta( $post_id, 'token', $token );
		update_post_meta( $post_id, 'token_udloeber', time() + DAY_IN_SECONDS );

		$bekraeft_link = add_query_arg( 'cs_bekraeft', $token, home_url( '/' ) );
		if ( 'indsigt' === $type ) {
			$emne = 'Bekræft anmodning om indsigt hos ' . get_bloginfo( 'name' );
			$krop = "Hej\n\nDu (eller nogen der bruger din e-mail) har bedt om at få tilsendt en oversigt over de oplysninger, vi har liggende på {$email} hos " . get_bloginfo( 'name' ) . ".\n\nKlik på linket herunder for at bekræfte og downloade oversigten. Har du ikke selv anmodet om dette, kan du blot ignorere denne mail.\n\n{$bekraeft_link}\n\nLinket udløber om 24 timer.";
		} else {
			$emne = 'Bekræft sletning af dine oplysninger hos ' . get_bloginfo( 'name' );
			$krop = "Hej\n\nDu (eller nogen der bruger din e-mail) har bedt om at få slettet de oplysninger, vi har liggende på {$email} hos " . get_bloginfo( 'name' ) . ".\n\nKlik på linket herunder for at bekræfte sletningen. Har du ikke selv anmodet om dette, kan du blot ignorere denne mail — der sker ikke noget, før linket bliver klikket.\n\n{$bekraeft_link}\n\nLinket udløber om 24 timer.";
		}
		wp_mail( $email, $emne, $krop );
	}

	wp_safe_redirect( add_query_arg( 'cs_anmodning', $type . '_modtaget', $redirect ) );
	exit;
}
add_action( 'admin_post_cookie_samtykke_anmod_data', 'cookie_samtykke_haandter_anmod_data' );
add_action( 'admin_post_nopriv_cookie_samtykke_anmod_data', 'cookie_samtykke_haandter_anmod_data' );

/**
 * Bygger en læsbar tekstfil ud fra det, sitet selv har fundet via
 * cookie_samtykke_hent_bruger_data-filteret.
 */
function cookie_samtykke_byg_dataeksport_tekst( string $email, array $poster ): string {
	$linjer   = array();
	$linjer[] = 'Oplysninger registreret på ' . $email . ' hos ' . get_bloginfo( 'name' );
	$linjer[] = 'Udtrukket: ' . date_i18n( 'j. F Y H:i' );
	$linjer[] = str_repeat( '-', 48 );

	if ( ! $poster ) {
		$linjer[] = '';
		$linjer[] = 'Vi har ikke fundet nogen registrerede oplysninger på denne e-mailadresse.';
	}

	foreach ( $poster as $post ) {
		$linjer[] = '';
		$linjer[] = '## ' . ( $post['titel'] ?? 'Registrering' );
		foreach ( (array) ( $post['felter'] ?? array() ) as $navn => $vaerdi ) {
			if ( '' === (string) $vaerdi ) {
				continue;
			}
			$linjer[] = $navn . ': ' . $vaerdi;
		}
	}

	return implode( "\n", $linjer );
}

function cookie_samtykke_haandter_bekraeft() {
	if ( ! isset( $_GET['cs_bekraeft'] ) ) {
		return;
	}
	$token = sanitize_text_field( wp_unslash( $_GET['cs_bekraeft'] ) );

	$fundne = get_posts(
		array(
			'post_type'      => 'cs_anmodning',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'token',
					'value' => $token,
				),
			),
		)
	);

	$farver = cookie_samtykke_hent_farver();

	if ( ! $fundne ) {
		cookie_samtykke_vis_anmodningsside( 'Linket er ugyldigt eller allerede brugt.', $farver );
		exit;
	}

	$post_id        = $fundne[0]->ID;
	$status         = get_post_meta( $post_id, 'status', true );
	$token_udloeber = (int) get_post_meta( $post_id, 'token_udloeber', true );
	$email          = get_post_meta( $post_id, 'email', true );
	$type           = get_post_meta( $post_id, 'type', true ) ?: 'sletning';

	if ( 'afventer' !== $status ) {
		cookie_samtykke_vis_anmodningsside( 'Denne anmodning er allerede behandlet.', $farver );
		exit;
	}
	if ( $token_udloeber && time() > $token_udloeber ) {
		update_post_meta( $post_id, 'status', 'udloebet' );
		cookie_samtykke_vis_anmodningsside( 'Linket er udløbet. Udfyld formularen igen for at få et nyt.', $farver );
		exit;
	}

	if ( 'indsigt' === $type ) {
		/**
		 * Selve dataindsamlingen — pluginet ved ikke selv, hvor sitets data
		 * ligger, så det enkelte site leverer sine egne fund her.
		 */
		$poster = apply_filters( 'cookie_samtykke_hent_bruger_data', array(), $email );

		update_post_meta( $post_id, 'status', 'udfoert' );
		update_post_meta( $post_id, 'bekraeftet_dato', time() );
		update_post_meta( $post_id, 'token', '' );

		$tekst = cookie_samtykke_byg_dataeksport_tekst( $email, $poster );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="mine-oplysninger.txt"' );
		echo $tekst; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Selve slettehandlingen — pluginet ved ikke selv, hvor sitets data
	 * ligger, så det enkelte site hægter sin egen oprydning på her.
	 */
	do_action( 'cookie_samtykke_slet_bruger_data', $email, $post_id );

	update_post_meta( $post_id, 'status', 'udfoert' );
	update_post_meta( $post_id, 'bekraeftet_dato', time() );
	update_post_meta( $post_id, 'token', '' );

	cookie_samtykke_vis_anmodningsside( 'Dine oplysninger er slettet.', $farver, true );
	exit;
}
add_action( 'template_redirect', 'cookie_samtykke_haandter_bekraeft' );

function cookie_samtykke_vis_anmodningsside( string $besked, array $farver, bool $success = false ): void {
	nocache_headers();
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
		<style>
			body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f4; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 24px; box-sizing: border-box; }
			.kort { background: #fff; border-radius: 14px; padding: 32px; max-width: 420px; text-align: center; box-shadow: 0 12px 32px -16px rgba(0,0,0,0.3); }
			.ikon { width: 48px; height: 48px; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; background: <?php echo esc_attr( $farver['accent'] ); ?>; color: <?php echo esc_attr( $farver['accent_tekst'] ); ?>; font-size: 24px; }
			p { color: #333; line-height: 1.5; }
			a { color: <?php echo esc_attr( $farver['accent'] ); ?>; }
		</style>
	</head>
	<body>
		<div class="kort">
			<div class="ikon"><?php echo $success ? '&#10003;' : '!'; ?></div>
			<p><?php echo esc_html( $besked ); ?></p>
			<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; Tilbage til hjemmesiden</a></p>
		</div>
	</body>
	</html>
	<?php
}
