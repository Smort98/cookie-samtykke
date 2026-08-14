<?php
/**
 * Indstillingsside: Indstillinger → Cookie-samtykke.
 */

defined( 'ABSPATH' ) || exit;

function cookie_samtykke_standard_tekster(): array {
	return array(
		'titel'              => 'Vi bruger cookies',
		'tekst'              => 'Vi bruger cookies til at få hjemmesiden til at fungere, forstå hvordan den bliver brugt, og vise relevant indhold. Du kan altid ændre dit valg senere.',
		'nodvendige_navn'    => 'Nødvendige',
		'nodvendige_tekst'   => 'Sikrer at hjemmesiden virker som den skal. Kan ikke fravælges.',
		'statistik_navn'     => 'Statistik',
		'statistik_tekst'    => 'Hjælper os med at forstå, hvordan hjemmesiden bliver brugt, så vi kan forbedre den.',
		'marketing_navn'     => 'Marketing',
		'marketing_tekst'    => 'Bruges til at vise relevante annoncer på tværs af hjemmesider.',
		'accepter_alle'      => 'Accepter alle',
		'afvis_ikke_nodv'    => 'Afvis ikke-nødvendige',
		'tilpas'             => 'Tilpas',
		'gem_valg'           => 'Gem valg',
	);
}

function cookie_samtykke_hent_tekster(): array {
	$gemt = get_option( 'cookie_samtykke_tekster', array() );
	return wp_parse_args( is_array( $gemt ) ? $gemt : array(), cookie_samtykke_standard_tekster() );
}

/**
 * Virksomhedsoplysninger til den genererede privatlivspolitik. Falder
 * tilbage til sitets navn, hvis der ikke er udfyldt et virksomhedsnavn.
 */
function cookie_samtykke_hent_virksomhed(): array {
	$standard = array( 'navn' => '', 'cvr' => '', 'adresse' => '', 'postnr_by' => '', 'telefon' => '' );
	$gemt     = get_option( 'cookie_samtykke_virksomhed', array() );
	$virk     = wp_parse_args( is_array( $gemt ) ? $gemt : array(), $standard );
	if ( '' === $virk['navn'] ) {
		$virk['navn'] = get_bloginfo( 'name' );
	}
	return $virk;
}

function cookie_samtykke_registrer_indstillinger() {
	register_setting(
		'cookie_samtykke',
		'cookie_samtykke_farvetilstand',
		array(
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				return 'manuel' === $v ? 'manuel' : 'auto';
			},
			'default'           => 'auto',
		)
	);
	register_setting(
		'cookie_samtykke',
		'cookie_samtykke_manuelle_farver',
		array(
			'type'              => 'array',
			'sanitize_callback' => function ( $input ) {
				$output = COOKIE_SAMTYKKE_STANDARD_FARVER;
				foreach ( $output as $key => $default ) {
					if ( ! empty( $input[ $key ] ) && cookie_samtykke_er_hex( $input[ $key ] ) ) {
						$output[ $key ] = sanitize_hex_color( $input[ $key ] );
					}
				}
				return $output;
			},
			'default'           => COOKIE_SAMTYKKE_STANDARD_FARVER,
		)
	);
	register_setting(
		'cookie_samtykke',
		'cookie_samtykke_position',
		array(
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				return 'kort' === $v ? 'kort' : 'bjaelke';
			},
			'default'           => 'bjaelke',
		)
	);
	register_setting(
		'cookie_samtykke',
		'cookie_samtykke_flydende_ikon',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => function ( $v ) {
				return ! empty( $v );
			},
			'default'           => true,
		)
	);
	register_setting(
		'cookie_samtykke',
		'cookie_samtykke_kategorier',
		array(
			'type'              => 'array',
			'sanitize_callback' => function ( $input ) {
				return array(
					'statistik' => ! empty( $input['statistik'] ),
					'marketing' => ! empty( $input['marketing'] ),
				);
			},
			'default'           => array( 'statistik' => true, 'marketing' => true ),
		)
	);
	register_setting(
		'cookie_samtykke',
		'cookie_samtykke_privatliv_side',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		)
	);
	register_setting(
		'cookie_samtykke',
		'cookie_samtykke_virksomhed',
		array(
			'type'              => 'array',
			'sanitize_callback' => function ( $input ) {
				$output = array( 'navn' => '', 'cvr' => '', 'adresse' => '', 'postnr_by' => '', 'telefon' => '' );
				foreach ( $output as $key => $default ) {
					if ( isset( $input[ $key ] ) ) {
						$output[ $key ] = sanitize_text_field( $input[ $key ] );
					}
				}
				return $output;
			},
			'default'           => array( 'navn' => '', 'cvr' => '', 'adresse' => '', 'postnr_by' => '', 'telefon' => '' ),
		)
	);
	register_setting(
		'cookie_samtykke',
		'cookie_samtykke_tekster',
		array(
			'type'              => 'array',
			'sanitize_callback' => function ( $input ) {
				$output = cookie_samtykke_standard_tekster();
				foreach ( $output as $key => $default ) {
					if ( isset( $input[ $key ] ) ) {
						$output[ $key ] = sanitize_text_field( $input[ $key ] );
					}
				}
				return $output;
			},
			'default'           => cookie_samtykke_standard_tekster(),
		)
	);
}
add_action( 'admin_init', 'cookie_samtykke_registrer_indstillinger' );

function cookie_samtykke_menu() {
	add_menu_page(
		'Cookie-samtykke',
		'Cookie-samtykke',
		'manage_options',
		'cookie-samtykke',
		'cookie_samtykke_render_side',
		'dashicons-privacy',
		80
	);
	// Omdøber det automatisk oprettede første undermenupunkt (som ellers
	// ville hedde det samme som topmenuen) til "Indstillinger".
	add_submenu_page(
		'cookie-samtykke',
		'Indstillinger',
		'Indstillinger',
		'manage_options',
		'cookie-samtykke',
		'cookie_samtykke_render_side'
	);
}
add_action( 'admin_menu', 'cookie_samtykke_menu' );

function cookie_samtykke_generer_besked() {
	if ( ! isset( $_GET['page'] ) || 'cookie-samtykke' !== $_GET['page'] ) {
		return;
	}
	if ( isset( $_GET['cs_genereret'] ) ) {
		$type  = sanitize_key( wp_unslash( $_GET['cs_genereret'] ) );
		$navne = array( 'privatliv' => 'Privatlivspolitik', 'cookiepolitik' => 'Cookiepolitik' );
		if ( isset( $navne[ $type ] ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s-siden er oprettet/opdateret.</p></div>', esc_html( $navne[ $type ] ) );
		}
	}
	if ( isset( $_GET['cs_scannet'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Scanning fuldført.</p></div>';
	}
}
add_action( 'admin_notices', 'cookie_samtykke_generer_besked' );

function cookie_samtykke_admin_assets( string $hook ) {
	if ( 'toplevel_page_cookie-samtykke' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script(
		'wp-color-picker',
		'jQuery(function($){$(".cookie-samtykke-farve").wpColorPicker();});'
	);
}
add_action( 'admin_enqueue_scripts', 'cookie_samtykke_admin_assets' );

function cookie_samtykke_render_side() {
	$farvetilstand = get_option( 'cookie_samtykke_farvetilstand', 'auto' );
	$manuel        = wp_parse_args( get_option( 'cookie_samtykke_manuelle_farver', array() ), COOKIE_SAMTYKKE_STANDARD_FARVER );
	$position      = get_option( 'cookie_samtykke_position', 'bjaelke' );
	$flydende_ikon = (bool) get_option( 'cookie_samtykke_flydende_ikon', true );
	$kategorier    = wp_parse_args( get_option( 'cookie_samtykke_kategorier', array() ), array( 'statistik' => true, 'marketing' => true ) );
	$privatliv     = (int) get_option( 'cookie_samtykke_privatliv_side', 0 );
	$virksomhed    = wp_parse_args( get_option( 'cookie_samtykke_virksomhed', array() ), array( 'navn' => '', 'cvr' => '', 'adresse' => '', 'postnr_by' => '', 'telefon' => '' ) );
	$tekster       = cookie_samtykke_hent_tekster();
	$auto_farver   = cookie_samtykke_hent_farver();
	?>
	<div class="wrap">
		<h1>Cookie-samtykke</h1>
		<p>Samtykkebanneret vises automatisk nederst på siden for besøgende der ikke allerede har taget stilling. Indsæt kortkoden <code>[cookie_samtykke_link]Cookie-indstillinger[/cookie_samtykke_link]</code> i footeren, hvis du vil give besøgende mulighed for at ændre deres valg senere.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'cookie_samtykke' ); ?>

			<h2 class="title">Farver</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Farvetilstand</th>
					<td>
						<label><input type="radio" name="cookie_samtykke_farvetilstand" value="auto" <?php checked( $farvetilstand, 'auto' ); ?>> Automatisk — hentes fra temaets stilart</label><br>
						<label><input type="radio" name="cookie_samtykke_farvetilstand" value="manuel" <?php checked( $farvetilstand, 'manuel' ); ?>> Manuel — vælg selv farverne nedenfor</label>
						<p class="description">
							Automatisk fundet lige nu:
							<span style="display:inline-block;width:14px;height:14px;border-radius:3px;vertical-align:middle;background:<?php echo esc_attr( $auto_farver['baggrund'] ); ?>;border:1px solid #ccc;"></span> baggrund,
							<span style="display:inline-block;width:14px;height:14px;border-radius:3px;vertical-align:middle;background:<?php echo esc_attr( $auto_farver['tekst'] ); ?>;border:1px solid #ccc;"></span> tekst,
							<span style="display:inline-block;width:14px;height:14px;border-radius:3px;vertical-align:middle;background:<?php echo esc_attr( $auto_farver['accent'] ); ?>;border:1px solid #ccc;"></span> accent
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Manuelle farver</th>
					<td>
						<p>
							<label>Baggrund<br><input type="text" class="cookie-samtykke-farve" name="cookie_samtykke_manuelle_farver[baggrund]" value="<?php echo esc_attr( $manuel['baggrund'] ); ?>"></label>
						</p>
						<p>
							<label>Tekst<br><input type="text" class="cookie-samtykke-farve" name="cookie_samtykke_manuelle_farver[tekst]" value="<?php echo esc_attr( $manuel['tekst'] ); ?>"></label>
						</p>
						<p>
							<label>Accent (knapper)<br><input type="text" class="cookie-samtykke-farve" name="cookie_samtykke_manuelle_farver[accent]" value="<?php echo esc_attr( $manuel['accent'] ); ?>"></label>
						</p>
						<p>
							<label>Tekst på accent-knap<br><input type="text" class="cookie-samtykke-farve" name="cookie_samtykke_manuelle_farver[accent_tekst]" value="<?php echo esc_attr( $manuel['accent_tekst'] ); ?>"></label>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Placering</th>
					<td>
						<select name="cookie_samtykke_position">
							<option value="bjaelke" <?php selected( $position, 'bjaelke' ); ?>>Fuld bredde-bjælke i bunden</option>
							<option value="kort" <?php selected( $position, 'kort' ); ?>>Lille kort i hjørnet</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">Flydende ikon</th>
					<td>
						<label><input type="checkbox" name="cookie_samtykke_flydende_ikon" value="1" <?php checked( $flydende_ikon ); ?>> Vis et lille flydende ikon nederst, så besøgende altid nemt kan genåbne cookie-indstillingerne</label>
					</td>
				</tr>
			</table>

			<h2 class="title">Kategorier</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Nødvendige</th>
					<td>Altid aktiv — kan ikke slås fra.</td>
				</tr>
				<tr>
					<th scope="row">Statistik</th>
					<td><label><input type="checkbox" name="cookie_samtykke_kategorier[statistik]" value="1" <?php checked( $kategorier['statistik'] ); ?>> Vis denne kategori i banneret</label></td>
				</tr>
				<tr>
					<th scope="row">Marketing</th>
					<td><label><input type="checkbox" name="cookie_samtykke_kategorier[marketing]" value="1" <?php checked( $kategorier['marketing'] ); ?>> Vis denne kategori i banneret</label></td>
				</tr>
			</table>

			<h2 class="title">Tekster</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="cs_titel">Titel</label></th>
					<td><input type="text" id="cs_titel" class="regular-text" name="cookie_samtykke_tekster[titel]" value="<?php echo esc_attr( $tekster['titel'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cs_tekst">Beskrivelse</label></th>
					<td><textarea id="cs_tekst" class="large-text" rows="3" name="cookie_samtykke_tekster[tekst]"><?php echo esc_textarea( $tekster['tekst'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="cs_statistik_tekst">Statistik — beskrivelse</label></th>
					<td><input type="text" id="cs_statistik_tekst" class="large-text" name="cookie_samtykke_tekster[statistik_tekst]" value="<?php echo esc_attr( $tekster['statistik_tekst'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cs_marketing_tekst">Marketing — beskrivelse</label></th>
					<td><input type="text" id="cs_marketing_tekst" class="large-text" name="cookie_samtykke_tekster[marketing_tekst]" value="<?php echo esc_attr( $tekster['marketing_tekst'] ); ?>"></td>
				</tr>
			</table>

			<h2 class="title">Virksomhedsoplysninger</h2>
			<p>Bruges til den automatisk genererede privatlivspolitik (afsnittet "Dataansvarlig"). Stå tomme felter over, falder den tilbage til sitets navn og admin-e-mail.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="cs_virk_navn">Virksomhedsnavn</label></th>
					<td><input type="text" id="cs_virk_navn" class="regular-text" name="cookie_samtykke_virksomhed[navn]" value="<?php echo esc_attr( $virksomhed['navn'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cs_virk_cvr">CVR-nummer</label></th>
					<td><input type="text" id="cs_virk_cvr" class="regular-text" name="cookie_samtykke_virksomhed[cvr]" value="<?php echo esc_attr( $virksomhed['cvr'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cs_virk_adresse">Adresse</label></th>
					<td><input type="text" id="cs_virk_adresse" class="regular-text" name="cookie_samtykke_virksomhed[adresse]" value="<?php echo esc_attr( $virksomhed['adresse'] ); ?>" placeholder="Vejnavn 1"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cs_virk_postnr_by">Postnr. og by</label></th>
					<td><input type="text" id="cs_virk_postnr_by" class="regular-text" name="cookie_samtykke_virksomhed[postnr_by]" value="<?php echo esc_attr( $virksomhed['postnr_by'] ); ?>" placeholder="5750 Ringe"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cs_virk_telefon">Telefon</label></th>
					<td><input type="text" id="cs_virk_telefon" class="regular-text" name="cookie_samtykke_virksomhed[telefon]" value="<?php echo esc_attr( $virksomhed['telefon'] ); ?>"></td>
				</tr>
			</table>

			<h2 class="title">Privatlivspolitik</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="cs_privatliv">Side med privatlivspolitik</label></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'cookie_samtykke_privatliv_side',
								'id'                => 'cs_privatliv',
								'selected'          => $privatliv,
								'show_option_none'  => '— Ingen valgt —',
								'option_none_value' => 0,
							)
						);
						?>
						<p class="description">Linkes til fra banneret, hvis valgt.</p>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Gem indstillinger' ); ?>
		</form>

		<h2 class="title">Tjenester fundet på sitet</h2>
		<p>Scanner temaets og de aktive plugins' kildekode samt indholdet af alle offentliggjorte sider/indlæg for kendte tredjepartstjenester (fx Google Analytics, Facebook Pixel, YouTube, Google Maps). Bruges automatisk til at udfylde cookiepolitikken med det, sitet faktisk bruger, i stedet for generisk tekst.</p>
		<?php
		$scan_tid       = (int) get_option( 'cookie_samtykke_scan_tidspunkt', 0 );
		$scan_resultat  = (array) get_option( 'cookie_samtykke_scan_resultat', array() );
		$scan_kode      = (array) get_option( 'cookie_samtykke_scan_kode_resultat', array() );
		$alle_tjenester = cookie_samtykke_kendte_tjenester();
		?>
		<?php if ( $scan_tid ) : ?>
			<p>Sidst scannet: <?php echo esc_html( date_i18n( 'j. F Y \k\l. H:i', $scan_tid ) ); ?></p>
			<p><strong>Fundet i sidernes indhold</strong> (bruges direkte i den genererede cookiepolitik):</p>
			<?php if ( $scan_resultat ) : ?>
				<ul style="list-style:disc;margin-left:20px;">
					<?php
					foreach ( $scan_resultat as $noegle ) :
						if ( ! isset( $alle_tjenester[ $noegle ] ) ) {
							continue;
						}
						$t = $alle_tjenester[ $noegle ];
						?>
						<li><strong><?php echo esc_html( $t['navn'] ); ?></strong> (<?php echo esc_html( cookie_samtykke_kategori_navn( $t['kategori'] ) ); ?>) — <?php echo esc_html( $t['cookies'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p>Ingen kendte tredjepartstjenester fundet i indholdet.</p>
			<?php endif; ?>
			<?php if ( $scan_kode ) : ?>
				<p><strong>Understøttet af temaets/pluginnernes kode</strong> (kun til info — betyder ikke nødvendigvis, at det er i brug på siden, så tilføjes ikke automatisk til cookiepolitikken):</p>
				<ul style="list-style:disc;margin-left:20px;color:#646970;">
					<?php
					foreach ( $scan_kode as $noegle ) :
						if ( ! isset( $alle_tjenester[ $noegle ] ) ) {
							continue;
						}
						$t = $alle_tjenester[ $noegle ];
						?>
						<li><?php echo esc_html( $t['navn'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php else : ?>
			<p><em>Ikke scannet endnu.</em></p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'cookie_samtykke_scan' ); ?>
			<input type="hidden" name="action" value="cookie_samtykke_scan">
			<?php submit_button( $scan_tid ? 'Scan igen' : 'Scan sitet nu', 'secondary', 'submit', false ); ?>
		</form>

		<h2 class="title">Opret sider automatisk</h2>
		<p>Opretter en side med udfyldt standardtekst om privatliv og cookies, baseret på indstillingerne ovenfor. Teksten er et generelt udgangspunkt — læs den igennem og tilpas den, så den passer til jer, inden siden bruges i praksis.</p>
		<table class="form-table" role="presentation">
			<?php cookie_samtykke_render_generer_raekke( 'privatliv', 'Privatlivspolitik', (int) get_option( 'cookie_samtykke_privatliv_side', 0 ) ); ?>
			<?php cookie_samtykke_render_generer_raekke( 'cookiepolitik', 'Cookiepolitik', (int) get_option( 'cookie_samtykke_cookiepolitik_side', 0 ) ); ?>
		</table>
	</div>
	<?php
}

function cookie_samtykke_render_generer_raekke( string $type, string $titel, int $side_id ) {
	$side = $side_id ? get_post( $side_id ) : null;
	?>
	<tr>
		<th scope="row"><?php echo esc_html( $titel ); ?></th>
		<td>
			<?php if ( $side ) : ?>
				<p>
					Side oprettet: <a href="<?php echo esc_url( get_edit_post_link( $side_id ) ); ?>"><?php echo esc_html( $side->post_title ); ?></a>
					· <a href="<?php echo esc_url( get_permalink( $side_id ) ); ?>" target="_blank" rel="noopener">Vis siden</a>
				</p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" <?php echo $side ? 'onsubmit="return confirm(\'Dette overskriver den nuværende tekst på siden med en ny standardtekst. Fortsæt?\');"' : ''; ?>>
				<?php wp_nonce_field( 'cookie_samtykke_generer_side' ); ?>
				<input type="hidden" name="action" value="cookie_samtykke_generer_side">
				<input type="hidden" name="cs_type" value="<?php echo esc_attr( $type ); ?>">
				<?php submit_button( $side ? 'Generér indhold igen' : 'Opret side automatisk', $side ? 'secondary' : 'primary', 'submit', false ); ?>
			</form>
		</td>
	</tr>
	<?php
}
