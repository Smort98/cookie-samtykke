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
		$type = sanitize_key( wp_unslash( $_GET['cs_genereret'] ) );
		if ( 'begge' === $type ) {
			echo '<div class="notice notice-success is-dismissible"><p>Begge sider er oprettet/opdateret.</p></div>';
		} else {
			$navne = array( 'privatliv' => 'Privatlivspolitik', 'cookiepolitik' => 'Cookiepolitik' );
			if ( isset( $navne[ $type ] ) ) {
				printf( '<div class="notice notice-success is-dismissible"><p>%s-siden er oprettet/opdateret.</p></div>', esc_html( $navne[ $type ] ) );
			}
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
	wp_enqueue_style( 'cookie-samtykke-admin', COOKIE_SAMTYKKE_URL . 'assets/admin.css', array(), cookie_samtykke_asset_version( 'assets/admin.css' ) );
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script(
		'wp-color-picker',
		'jQuery(function($){$(".cookie-samtykke-farve").wpColorPicker();});'
	);
	wp_add_inline_script(
		'wp-color-picker',
		'jQuery(function($){
			$(document).on("click", ".sadmin-copy-btn", function(){
				var $btn = $(this);
				var tekst = $btn.data("copy");
				navigator.clipboard.writeText(String(tekst)).then(function(){
					var oprindelig = $btn.text();
					$btn.text("Kopieret!");
					setTimeout(function(){ $btn.text(oprindelig); }, 1500);
				});
			});
		});'
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
	$scan_tid      = (int) get_option( 'cookie_samtykke_scan_tidspunkt', 0 );

	$faner      = array(
		'indstillinger' => 'Indstillinger',
		'scanner'       => 'Tjenester & scanning',
		'sider'         => 'Sider',
		'anmodninger'   => 'Anmodninger',
	);
	$aktiv_fane = 'indstillinger';
	if ( isset( $_GET['fane'] ) ) {
		$oensket = sanitize_key( wp_unslash( $_GET['fane'] ) );
		if ( isset( $faner[ $oensket ] ) ) {
			$aktiv_fane = $oensket;
		}
	}
	?>
	<div class="wrap sadmin">
		<div class="sadmin-header">
			<div>
				<p class="sadmin-header__eyebrow">WordPress-plugin</p>
				<h1>Cookie-samtykke</h1>
				<p>Samtykkebanneret vises automatisk nederst på siden for besøgende, der ikke allerede har taget stilling.</p>
			</div>
			<div class="sadmin-header__badges">
				<span class="sadmin-header__badge"><?php echo $scan_tid ? 'Sidst scannet: ' . esc_html( date_i18n( 'j. F Y \k\l. H:i', $scan_tid ) ) : 'Ikke scannet endnu'; ?></span>
			</div>
		</div>

		<nav class="sadmin-tabs">
			<?php foreach ( $faner as $noegle => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'cookie-samtykke', 'fane' => $noegle ), admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $noegle === $aktiv_fane ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( 'indstillinger' === $aktiv_fane ) : ?>

			<?php
			$kortkoder = array(
				array(
					'kode'  => '[cookie_samtykke_privatlivspolitik]',
					'brug'  => 'Indsætter hele den genererede privatlivspolitik direkte på en side, du selv har valgt — som alternativ til at oprette en helt ny side automatisk.',
				),
				array(
					'kode'  => '[cookie_samtykke_cookiepolitik]',
					'brug'  => 'Indsætter hele den genererede cookiepolitik direkte på en side, du selv har valgt — som alternativ til at oprette en helt ny side automatisk.',
				),
				array(
					'kode'  => '[cookie_samtykke_link]Cookie-indstillinger[/cookie_samtykke_link]',
					'brug'  => 'Link der genåbner cookie-banneret, så en besøgende kan ændre sit valg senere. Sættes typisk i footeren.',
				),
				array(
					'kode'  => '[cookie_samtykke_download_data]',
					'brug'  => 'Knap hvor en besøgende kan anmode om at få tilsendt en oversigt over sine gemte oplysninger.',
				),
				array(
					'kode'  => '[cookie_samtykke_slet_data]',
					'brug'  => 'Knap hvor en besøgende kan anmode om at få sine gemte oplysninger slettet.',
				),
			);
			?>
			<div class="sadmin-card">
				<h2>Kortkoder</h2>
				<p class="sadmin-card__intro">Indsæt i en side, et indlæg eller en widget for at tilføje funktionen. Tryk "Kopiér" og sæt ind, hvor den skal bruges.</p>
				<div class="sadmin-table-wrap">
					<table class="sadmin-table">
						<thead>
							<tr>
								<th>Kortkode</th>
								<th>Bruges til</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $kortkoder as $k ) : ?>
								<tr>
									<td><code><?php echo esc_html( $k['kode'] ); ?></code></td>
									<td><?php echo esc_html( $k['brug'] ); ?></td>
									<td>
										<button type="button" class="button button-secondary sadmin-copy-btn" data-copy="<?php echo esc_attr( $k['kode'] ); ?>">Kopiér</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'cookie_samtykke' ); ?>

				<div class="sadmin-card">
					<h2>Farver</h2>
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
				</div>

				<div class="sadmin-card">
					<h2>Kategorier</h2>
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
				</div>

				<div class="sadmin-card">
					<h2>Tekster</h2>
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
				</div>

				<div class="sadmin-card">
					<h2>Virksomhedsoplysninger</h2>
					<p class="sadmin-card__intro">Bruges til den automatisk genererede privatlivspolitik (afsnittet "Dataansvarlig"). Stå tomme felter over, falder den tilbage til sitets navn og admin-e-mail.</p>
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
				</div>

				<div class="sadmin-card">
					<h2>Privatlivspolitik</h2>
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
				</div>

				<?php submit_button( 'Gem indstillinger' ); ?>
			</form>

		<?php elseif ( 'scanner' === $aktiv_fane ) : ?>

			<?php
			$scan_resultat  = (array) get_option( 'cookie_samtykke_scan_resultat', array() );
			$scan_kode      = (array) get_option( 'cookie_samtykke_scan_kode_resultat', array() );
			$alle_tjenester = cookie_samtykke_kendte_tjenester() + cookie_samtykke_platform_tjenester();

			$raekker = array();
			foreach ( $scan_resultat as $noegle ) {
				if ( isset( $alle_tjenester[ $noegle ] ) ) {
					$raekker[] = array( $alle_tjenester[ $noegle ], true );
				}
			}
			foreach ( $scan_kode as $noegle ) {
				if ( isset( $alle_tjenester[ $noegle ] ) ) {
					$raekker[] = array( $alle_tjenester[ $noegle ], false );
				}
			}
			?>
			<div class="sadmin-card">
				<h2>Tjenester fundet på sitet</h2>
				<p class="sadmin-card__intro">Scanner temaets og de aktive plugins' kildekode samt indholdet af alle offentliggjorte sider/indlæg for kendte tredjepartstjenester (fx Google Analytics, Facebook Pixel, YouTube, Google Maps), og tjekker samtidig om kendte platform-cookies (WordPress-login, WooCommerce) faktisk er i brug på sitet. Bruges automatisk til at udfylde cookiepolitikken med det, sitet faktisk bruger, i stedet for generisk tekst.</p>

				<?php if ( ! $scan_tid ) : ?>
					<p class="sadmin-empty">Ikke scannet endnu.</p>
				<?php elseif ( ! $raekker ) : ?>
					<p class="sadmin-empty">Ingen kendte tredjepartstjenester eller platform-cookies fundet.</p>
				<?php else : ?>
					<div class="sadmin-table-wrap">
						<table class="sadmin-table">
							<thead>
								<tr>
									<th>Navn</th>
									<th>Kategori</th>
									<th>Cookie(s)</th>
									<th>Kilde</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $raekker as $raekke ) : ?>
									<?php list( $t, $i_indhold ) = $raekke; ?>
									<tr>
										<td><strong><?php echo esc_html( $t['navn'] ); ?></strong></td>
										<td><?php echo esc_html( cookie_samtykke_kategori_navn( $t['kategori'] ) ); ?></td>
										<td><?php echo esc_html( $t['cookies'] ); ?></td>
										<td>
											<?php if ( $i_indhold ) : ?>
												<span class="sadmin-badge sadmin-badge--ok">Fundet i indhold</span>
											<?php else : ?>
												<span class="sadmin-badge sadmin-badge--neutral">Kun i kode</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>

			<div class="sadmin-card">
				<h2>Kør scanning</h2>
				<p class="sadmin-card__intro">"Fundet i indhold" har høj sikkerhed og bruges direkte i den genererede cookiepolitik. "Kun i kode" er kun til info — det er teknisk understøttet, men ikke nødvendigvis i brug på siden.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'cookie_samtykke_scan' ); ?>
					<input type="hidden" name="action" value="cookie_samtykke_scan">
					<?php submit_button( $scan_tid ? 'Scan igen' : 'Scan sitet nu', 'primary', 'submit', false ); ?>
				</form>
			</div>

		<?php elseif ( 'sider' === $aktiv_fane ) : ?>

			<?php
			$privatliv_id     = (int) get_option( 'cookie_samtykke_privatliv_side', 0 );
			$cookiepolitik_id = (int) get_option( 'cookie_samtykke_cookiepolitik_side', 0 );
			$noget_eksisterer = ( $privatliv_id && get_post( $privatliv_id ) ) || ( $cookiepolitik_id && get_post( $cookiepolitik_id ) );
			?>
			<div class="sadmin-card">
				<h2>Opret sider automatisk</h2>
				<p class="sadmin-card__intro">Opretter en side med udfyldt standardtekst om privatliv og cookies, baseret på indstillingerne. Teksten er et generelt udgangspunkt — læs den igennem og tilpas den, så den passer til jer, inden siden bruges i praksis.</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:16px;" <?php echo $noget_eksisterer ? 'onsubmit="return confirm(\'Dette overskriver den nuværende tekst på begge sider med ny standardtekst. Fortsæt?\');"' : ''; ?>>
					<?php wp_nonce_field( 'cookie_samtykke_generer_begge' ); ?>
					<input type="hidden" name="action" value="cookie_samtykke_generer_begge">
					<?php submit_button( 'Generér begge sider', 'primary', 'submit', false ); ?>
				</form>

				<?php cookie_samtykke_render_generer_raekke( 'privatliv', 'Privatlivspolitik', $privatliv_id ); ?>
				<?php cookie_samtykke_render_generer_raekke( 'cookiepolitik', 'Cookiepolitik', $cookiepolitik_id ); ?>
			</div>

		<?php else : ?>

			<?php
			$anmodninger  = get_posts(
				array(
					'post_type'      => 'cs_anmodning',
					'post_status'    => 'publish',
					'posts_per_page' => 50,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'no_found_rows'  => true,
				)
			);
			$status_navne = array(
				'afventer' => 'Afventer bekræftelse',
				'udfoert'  => 'Udført',
				'udloebet' => 'Udløbet',
			);
			?>
			<div class="sadmin-card">
				<h2>Data-anmodninger</h2>
				<p class="sadmin-card__intro">Besøgende der har anmodet om indsigt i eller sletning af deres oplysninger via <code>[cookie_samtykke_download_data]</code> eller <code>[cookie_samtykke_slet_data]</code>. En anmodning udføres først, når den besøgende selv har bekræftet via linket i mailen.</p>

				<?php if ( ! $anmodninger ) : ?>
					<p class="sadmin-empty">Ingen anmodninger endnu.</p>
				<?php else : ?>
					<div class="sadmin-table-wrap">
						<table class="sadmin-table">
							<thead>
								<tr>
									<th>E-mail</th>
									<th>Type</th>
									<th>Status</th>
									<th>Modtaget</th>
									<th>Udført</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $anmodninger as $a ) : ?>
									<?php
									$status      = get_post_meta( $a->ID, 'status', true );
									$type        = get_post_meta( $a->ID, 'type', true );
									$udfoert_tid = (int) get_post_meta( $a->ID, 'bekraeftet_dato', true );
									$badge_klasse = 'udfoert' === $status ? 'sadmin-badge--ok' : 'sadmin-badge--neutral';
									?>
									<tr>
										<td><?php echo esc_html( get_post_meta( $a->ID, 'email', true ) ); ?></td>
										<td><?php echo esc_html( 'indsigt' === $type ? 'Indsigt (download)' : 'Sletning' ); ?></td>
										<td><span class="sadmin-badge <?php echo esc_attr( $badge_klasse ); ?>"><?php echo esc_html( $status_navne[ $status ] ?? $status ); ?></span></td>
										<td><?php echo esc_html( get_the_date( 'j. F Y', $a ) ); ?></td>
										<td><?php echo esc_html( $udfoert_tid ? date_i18n( 'j. F Y', $udfoert_tid ) : '—' ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if ( count( $anmodninger ) >= 50 ) : ?>
						<p class="sadmin-card__intro" style="margin-top:12px;">Viser kun de 50 seneste.</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>

		<?php endif; ?>
	</div>
	<?php
}

function cookie_samtykke_render_generer_raekke( string $type, string $titel, int $side_id ) {
	$side = $side_id ? get_post( $side_id ) : null;
	?>
	<div class="sadmin-row-card">
		<div>
			<p class="sadmin-row-card__title">
				<?php echo esc_html( $titel ); ?>
				<?php if ( $side ) : ?>
					<span class="sadmin-badge sadmin-badge--ok">Oprettet</span>
				<?php else : ?>
					<span class="sadmin-badge sadmin-badge--neutral">Ikke oprettet</span>
				<?php endif; ?>
			</p>
			<?php if ( $side ) : ?>
				<p class="sadmin-row-card__meta">
					<a href="<?php echo esc_url( get_edit_post_link( $side_id ) ); ?>"><?php echo esc_html( $side->post_title ); ?></a>
					· <a href="<?php echo esc_url( get_permalink( $side_id ) ); ?>" target="_blank" rel="noopener">Vis siden</a>
				</p>
			<?php endif; ?>
		</div>
		<div class="sadmin-row-card__actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" <?php echo $side ? 'onsubmit="return confirm(\'Dette overskriver den nuværende tekst på siden med en ny standardtekst. Fortsæt?\');"' : ''; ?>>
				<?php wp_nonce_field( 'cookie_samtykke_generer_side' ); ?>
				<input type="hidden" name="action" value="cookie_samtykke_generer_side">
				<input type="hidden" name="cs_type" value="<?php echo esc_attr( $type ); ?>">
				<?php submit_button( $side ? 'Generér indhold igen' : 'Opret side automatisk', $side ? 'secondary' : 'primary', 'submit', false ); ?>
			</form>
		</div>
	</div>
	<?php
}
