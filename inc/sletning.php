<?php
/**
 * Selvbetjent sletteanmodning ("retten til at blive glemt"): besøgende
 * indtaster deres e-mail, bekræfter via et link i en mail (for at undgå
 * at man kan bede om at få andres data slettet), og ved bekræftelse
 * fyres et generisk action hook af, som det enkelte site selv kan hægte
 * sin oprydning på — pluginet ved ikke selv, hvor et givent sites
 * personoplysninger ligger (fx hvilke CPT'er/meta-felter), så det er
 * bevidst holdt uden for pluginet og overladt til temaet/sitet:
 *
 *   add_action( 'cookie_samtykke_slet_bruger_data', function( $email ) {
 *       // slet/anonymisér det, sitet selv gemmer på $email.
 *   } );
 */

defined( 'ABSPATH' ) || exit;

function cookie_samtykke_registrer_cpt_sletteanmodning() {
	register_post_type(
		'cs_sletteanmodning',
		array(
			'labels'       => array(
				'name'          => 'Sletteanmodninger',
				'singular_name' => 'Sletteanmodning',
				'all_items'     => 'Sletteanmodninger',
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
add_action( 'init', 'cookie_samtykke_registrer_cpt_sletteanmodning' );

function cookie_samtykke_sletteanmodning_admin_columns( array $columns ): array {
	unset( $columns['title'] );
	$columns['email']  = 'E-mail';
	$columns['status'] = 'Status';
	$columns['dato']   = 'Modtaget';
	$columns['udfoert'] = 'Udført';
	return $columns;
}
add_filter( 'manage_cs_sletteanmodning_posts_columns', 'cookie_samtykke_sletteanmodning_admin_columns' );

function cookie_samtykke_sletteanmodning_admin_column_content( string $column, int $post_id ): void {
	if ( 'email' === $column ) {
		echo esc_html( get_post_meta( $post_id, 'email', true ) );
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
add_action( 'manage_cs_sletteanmodning_posts_custom_column', 'cookie_samtykke_sletteanmodning_admin_column_content', 10, 2 );

function cookie_samtykke_slet_data_shortcode(): string {
	ob_start();
	if ( isset( $_GET['cs_anmodning'] ) && 'modtaget' === $_GET['cs_anmodning'] ) {
		echo '<p class="cookie-samtykke-melding">Tjek din e-mail — vi har sendt dig et link, du skal klikke på for at bekræfte sletningen.</p>';
	} elseif ( isset( $_GET['cs_anmodning'] ) && 'ugyldig' === $_GET['cs_anmodning'] ) {
		echo '<p class="cookie-samtykke-melding cookie-samtykke-melding--fejl">Det ligner ikke en gyldig e-mailadresse. Prøv igen.</p>';
	}
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cookie-samtykke-sletteformular">
		<input type="hidden" name="action" value="cookie_samtykke_anmod_sletning">
		<?php wp_nonce_field( 'cookie_samtykke_anmod_sletning' ); ?>
		<div class="cookie-samtykke-sletteformular__felt">
			<label for="cs-slet-email">Din e-mail</label>
			<input type="email" id="cs-slet-email" name="cs_email" required>
		</div>
		<p style="position:absolute;left:-9999px;" aria-hidden="true">
			<label>Lad dette felt stå tomt<input type="text" name="cs_website" tabindex="-1" autocomplete="off"></label>
		</p>
		<button type="submit" class="cookie-samtykke-sletteformular__knap">Anmod om sletning</button>
	</form>
	<?php
	return ob_get_clean();
}
add_shortcode( 'cookie_samtykke_slet_data', 'cookie_samtykke_slet_data_shortcode' );

function cookie_samtykke_haandter_anmod_sletning() {
	$redirect = wp_get_referer() ?: home_url( '/' );

	if ( ! empty( $_POST['cs_website'] ) ) {
		wp_safe_redirect( $redirect );
		exit;
	}
	check_admin_referer( 'cookie_samtykke_anmod_sletning' );

	$email = isset( $_POST['cs_email'] ) ? sanitize_email( wp_unslash( $_POST['cs_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'cs_anmodning', 'ugyldig', $redirect ) );
		exit;
	}

	$token   = wp_generate_password( 32, false, false );
	$post_id = wp_insert_post(
		array(
			'post_title'  => $email,
			'post_type'   => 'cs_sletteanmodning',
			'post_status' => 'publish',
		)
	);

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, 'email', $email );
		update_post_meta( $post_id, 'status', 'afventer' );
		update_post_meta( $post_id, 'token', $token );
		update_post_meta( $post_id, 'token_udloeber', time() + DAY_IN_SECONDS );

		$bekraeft_link = add_query_arg( 'cs_bekraeft_sletning', $token, home_url( '/' ) );
		$emne          = 'Bekræft sletning af dine oplysninger hos ' . get_bloginfo( 'name' );
		$krop          = "Hej\n\nDu (eller nogen der bruger din e-mail) har bedt om at få slettet de oplysninger, vi har liggende på {$email} hos " . get_bloginfo( 'name' ) . ".\n\nKlik på linket herunder for at bekræfte sletningen. Har du ikke selv anmodet om dette, kan du blot ignorere denne mail — der sker ikke noget, før linket bliver klikket.\n\n{$bekraeft_link}\n\nLinket udløber om 24 timer.";
		wp_mail( $email, $emne, $krop );
	}

	wp_safe_redirect( add_query_arg( 'cs_anmodning', 'modtaget', $redirect ) );
	exit;
}
add_action( 'admin_post_cookie_samtykke_anmod_sletning', 'cookie_samtykke_haandter_anmod_sletning' );
add_action( 'admin_post_nopriv_cookie_samtykke_anmod_sletning', 'cookie_samtykke_haandter_anmod_sletning' );

function cookie_samtykke_haandter_bekraeft_sletning() {
	if ( ! isset( $_GET['cs_bekraeft_sletning'] ) ) {
		return;
	}
	$token = sanitize_text_field( wp_unslash( $_GET['cs_bekraeft_sletning'] ) );

	$fundne = get_posts(
		array(
			'post_type'      => 'cs_sletteanmodning',
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
		cookie_samtykke_vis_sletteside( 'Linket er ugyldigt eller allerede brugt.', $farver );
		exit;
	}

	$post_id        = $fundne[0]->ID;
	$status         = get_post_meta( $post_id, 'status', true );
	$token_udloeber = (int) get_post_meta( $post_id, 'token_udloeber', true );
	$email          = get_post_meta( $post_id, 'email', true );

	if ( 'afventer' !== $status ) {
		cookie_samtykke_vis_sletteside( 'Denne anmodning er allerede behandlet.', $farver );
		exit;
	}
	if ( $token_udloeber && time() > $token_udloeber ) {
		update_post_meta( $post_id, 'status', 'udloebet' );
		cookie_samtykke_vis_sletteside( 'Linket er udløbet. Udfyld formularen igen for at få et nyt.', $farver );
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

	cookie_samtykke_vis_sletteside( 'Dine oplysninger er slettet.', $farver, true );
	exit;
}
add_action( 'template_redirect', 'cookie_samtykke_haandter_bekraeft_sletning' );

function cookie_samtykke_vis_sletteside( string $besked, array $farver, bool $success = false ): void {
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
