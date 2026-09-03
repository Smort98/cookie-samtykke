<?php
/**
 * Selve banneret — enqueue af assets, render i wp_footer, og en
 * kortkode til at genåbne indstillingerne senere (fx et link i footeren).
 */

defined( 'ABSPATH' ) || exit;

function cookie_samtykke_assets() {
	wp_enqueue_style( 'cookie-samtykke', COOKIE_SAMTYKKE_URL . 'assets/consent.css', array(), cookie_samtykke_asset_version( 'assets/consent.css' ) );
	wp_enqueue_script( 'cookie-samtykke', COOKIE_SAMTYKKE_URL . 'assets/consent.js', array(), cookie_samtykke_asset_version( 'assets/consent.js' ), true );

	$farver     = cookie_samtykke_hent_farver();
	$kategorier = wp_parse_args( get_option( 'cookie_samtykke_kategorier', array() ), array( 'statistik' => true, 'marketing' => true ) );

	wp_localize_script(
		'cookie-samtykke',
		'cookieSamtykkeData',
		array(
			'cookieNavn'   => 'cookie_samtykke',
			'cookieDage'   => 365,
			'kategorier'   => array(
				'statistik' => (bool) $kategorier['statistik'],
				'marketing' => (bool) $kategorier['marketing'],
			),
		)
	);

	$inline_css = sprintf(
		':root{--cs-bg:%1$s;--cs-text:%2$s;--cs-accent:%3$s;--cs-accent-text:%4$s;}',
		esc_attr( $farver['baggrund'] ),
		esc_attr( $farver['tekst'] ),
		esc_attr( $farver['accent'] ),
		esc_attr( $farver['accent_tekst'] )
	);
	wp_add_inline_style( 'cookie-samtykke', $inline_css );
}
add_action( 'wp_enqueue_scripts', 'cookie_samtykke_assets' );

/**
 * consent.css indeholder også styling af den genererede privatlivs-/
 * cookiepolitik (tabeller, indholdsbredde m.m.). Den styling skal også være
 * synlig inde i blok-editorens forhåndsvisning, ikke kun på den gemte side —
 * ellers ser redaktøren råt, ustylet indhold mens der redigeres. wp_enqueue_
 * scripts fyrer ikke inde i editoren, så det kræver sit eget hook.
 */
function cookie_samtykke_editor_assets() {
	wp_enqueue_style( 'cookie-samtykke', COOKIE_SAMTYKKE_URL . 'assets/consent.css', array(), cookie_samtykke_asset_version( 'assets/consent.css' ) );
}
add_action( 'enqueue_block_editor_assets', 'cookie_samtykke_editor_assets' );

function cookie_samtykke_render_banner() {
	$tekster    = cookie_samtykke_hent_tekster();
	$position   = get_option( 'cookie_samtykke_position', 'bjaelke' );
	$kategorier = wp_parse_args( get_option( 'cookie_samtykke_kategorier', array() ), array( 'statistik' => true, 'marketing' => true ) );
	$privatliv  = (int) get_option( 'cookie_samtykke_privatliv_side', 0 );
	$privatliv_url = $privatliv ? get_permalink( $privatliv ) : '';
	?>
	<div id="cookie-samtykke" class="cookie-samtykke cookie-samtykke--<?php echo esc_attr( $position ); ?>" hidden aria-live="polite">
		<div class="cookie-samtykke__boks" role="dialog" aria-modal="false" aria-labelledby="cs-titel">
			<div class="cookie-samtykke__hoved">
				<p id="cs-titel" class="cookie-samtykke__titel"><?php echo esc_html( $tekster['titel'] ); ?></p>
				<p class="cookie-samtykke__tekst">
					<?php echo esc_html( $tekster['tekst'] ); ?>
					<?php if ( $privatliv_url ) : ?>
						<a href="<?php echo esc_url( $privatliv_url ); ?>">Læs vores privatlivspolitik</a>
					<?php endif; ?>
				</p>
			</div>

			<div class="cookie-samtykke__detaljer" hidden>
				<label class="cookie-samtykke__kategori">
					<input type="checkbox" checked disabled>
					<span>
						<strong><?php echo esc_html( $tekster['nodvendige_navn'] ); ?></strong>
						<small><?php echo esc_html( $tekster['nodvendige_tekst'] ); ?></small>
					</span>
				</label>
				<?php if ( $kategorier['statistik'] ) : ?>
					<label class="cookie-samtykke__kategori">
						<input type="checkbox" data-cs-kategori="statistik">
						<span>
							<strong><?php echo esc_html( $tekster['statistik_navn'] ); ?></strong>
							<small><?php echo esc_html( $tekster['statistik_tekst'] ); ?></small>
						</span>
					</label>
				<?php endif; ?>
				<?php if ( $kategorier['marketing'] ) : ?>
					<label class="cookie-samtykke__kategori">
						<input type="checkbox" data-cs-kategori="marketing">
						<span>
							<strong><?php echo esc_html( $tekster['marketing_navn'] ); ?></strong>
							<small><?php echo esc_html( $tekster['marketing_tekst'] ); ?></small>
						</span>
					</label>
				<?php endif; ?>
			</div>

			<div class="cookie-samtykke__knapper">
				<button type="button" class="cookie-samtykke__knap cookie-samtykke__knap--tekst" data-cs-action="tilpas"><?php echo esc_html( $tekster['tilpas'] ); ?></button>
				<button type="button" class="cookie-samtykke__knap cookie-samtykke__knap--tekst" data-cs-action="gem" hidden><?php echo esc_html( $tekster['gem_valg'] ); ?></button>
				<button type="button" class="cookie-samtykke__knap cookie-samtykke__knap--ghost" data-cs-action="afvis"><?php echo esc_html( $tekster['afvis_ikke_nodv'] ); ?></button>
				<button type="button" class="cookie-samtykke__knap cookie-samtykke__knap--primaer" data-cs-action="accepter"><?php echo esc_html( $tekster['accepter_alle'] ); ?></button>
			</div>
		</div>
	</div>
	<?php
	if ( get_option( 'cookie_samtykke_flydende_ikon', true ) ) :
		?>
		<button type="button" id="cookie-samtykke-flydende" class="cookie-samtykke-flydende" data-cs-genaabn hidden aria-label="Cookie-indstillinger" title="Cookie-indstillinger">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<circle cx="12" cy="12" r="3"></circle>
				<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
			</svg>
		</button>
		<?php
	endif;
}
add_action( 'wp_footer', 'cookie_samtykke_render_banner' );

/**
 * [cookie_samtykke_link]Cookie-indstillinger[/cookie_samtykke_link]
 * — genåbner banneret, så besøgende kan ændre deres valg. Virker i alle
 * temaer (klassiske og blok), da den bare outputter et almindeligt link.
 */
function cookie_samtykke_shortcode( $atts, $content = '' ): string {
	$tekst = $content ? $content : 'Cookie-indstillinger';
	return '<a href="#" data-cs-genaabn>' . esc_html( $tekst ) . '</a>';
}
add_shortcode( 'cookie_samtykke_link', 'cookie_samtykke_shortcode' );
