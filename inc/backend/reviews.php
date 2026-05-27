<?php

/**
 * Skate Reviews — Admin Page
 *
 * Google Places API settings + cache management.
 * Registered as a submenu under the main Skate menu.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! is_admin() ) return;

// ----------------------------------------
// Menu registration
// ----------------------------------------
add_action( 'admin_menu', function () {
	add_submenu_page(
		'skate',
		__( 'SkateWP – Reviews', 'skate' ),
		__( 'Reviews', 'skate' ),
		'manage_options',
		'skate-reviews',
		'skate_render_reviews_page'
	);
} );

// ----------------------------------------
// Save handler (POST → validate → redirect)
// ----------------------------------------
add_action( 'admin_init', function () {
	if ( ! isset( $_POST['skate_reviews_nonce'] ) ) return;
	if ( ! check_admin_referer( 'skate_save_reviews', 'skate_reviews_nonce' ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	$redirect = admin_url( 'admin.php?page=skate-reviews' );

	// Clear cache action
	if ( isset( $_POST['skate_reviews_clear_cache'] ) ) {
		$keys = get_option( 'skate_reviews_cache_keys', [] );
		foreach ( $keys as $key ) {
			delete_transient( $key );
		}
		delete_option( 'skate_reviews_cache_keys' );
		wp_safe_redirect( $redirect . '&cleared=1' );
		exit;
	}

	// Save settings
	$opts = [
		'api_key'     => sanitize_text_field( wp_unslash( $_POST['skate_reviews_api_key']     ?? '' ) ),
		'place_id'    => sanitize_text_field( wp_unslash( $_POST['skate_reviews_place_id']    ?? '' ) ),
		'reviews_url' => esc_url_raw(          wp_unslash( $_POST['skate_reviews_url']         ?? '' ) ),
	];

	update_option( 'skate_reviews_options', $opts );
	wp_safe_redirect( $redirect . '&saved=1' );
	exit;
} );

// ----------------------------------------
// Admin page renderer
// ----------------------------------------
function skate_render_reviews_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$opts       = get_option( 'skate_reviews_options', [] );
	$api_key    = $opts['api_key']     ?? '';
	$place_id   = $opts['place_id']    ?? '';
	$reviews_url = $opts['reviews_url'] ?? '';

	$saved   = ! empty( $_GET['saved'] );
	$cleared = ! empty( $_GET['cleared'] );

	skate_print_preset_assets();
	?>
	<div class="wrap skate-identity-wrap">
		<h1>Reviews</h1>

		<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?= esc_html__( 'Reviews settings saved.', 'skate' ) ?></p></div>
		<?php endif; ?>

		<?php if ( $cleared ) : ?>
		<div class="notice notice-success is-dismissible"><p><?= esc_html__( 'Cache cleared — reviews will re-fetch on next page load.', 'skate' ) ?></p></div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'skate_save_reviews', 'skate_reviews_nonce' ); ?>

			<div class="skate-tune-section">
				<div class="skate-tune-head">
					<h3 class="skate-tune-title">Google Places</h3>
					<p class="skate-tune-desc">Connect your Google Business profile to display reviews on your site.</p>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-reviews-api-key">API Key</label>
					<div class="skate-tune-control">
						<input type="password" id="skate-reviews-api-key" name="skate_reviews_api_key"
							value="<?= esc_attr( $api_key ) ?>"
							style="width:280px" autocomplete="off">
					</div>
					<span class="skate-tune-hint">
						Enable <em>Places API</em> in
						<a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a>
					</span>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-reviews-place-id">Place ID</label>
					<div class="skate-tune-control">
						<input type="text" id="skate-reviews-place-id" name="skate_reviews_place_id"
							value="<?= esc_attr( $place_id ) ?>"
							style="width:280px" placeholder="ChIJ...">
					</div>
					<span class="skate-tune-hint">
						<a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" rel="noopener">Find your Place ID →</a>
					</span>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-reviews-url">Reviews URL</label>
					<div class="skate-tune-control">
						<input type="url" id="skate-reviews-url" name="skate_reviews_url"
							value="<?= esc_attr( $reviews_url ) ?>"
							style="width:280px" placeholder="https://maps.google.com/?cid=...">
					</div>
					<span class="skate-tune-hint">Used by <code>[skate-reviews]</code> and available as <code>[skate_reviews_url]</code> to link any button in the editor</span>
				</div>
			</div><!-- /.skate-tune-section -->

			<div class="skate-tune-section" style="margin-top:16px">
				<div class="skate-tune-head">
					<h3 class="skate-tune-title">Shortcode</h3>
					<p class="skate-tune-desc">Insert reviews anywhere using the shortcode below.</p>
				</div>

				<div class="skate-tune-row" style="align-items:flex-start">
					<span class="skate-tune-label">Usage</span>
					<div class="skate-tune-control" style="flex-direction:column;align-items:flex-start;gap:8px">
						<code style="background:#f6f7f7;padding:4px 10px;border-radius:4px;font-size:13px">[skate-reviews]</code>
						<code style="background:#f6f7f7;padding:4px 10px;border-radius:4px;font-size:13px">[skate-reviews place_id="ChIJ..." lang="en" max="5" show_header="1"]</code>
					</div>
				</div>
			</div><!-- /.skate-tune-section -->

			<div style="display:flex;gap:10px;padding:16px 0 8px;align-items:center">
				<?php submit_button( __( 'Save Settings', 'skate' ), 'primary', 'submit', false ); ?>
			</div>
		</form>

		<!-- Cache -->
		<div class="skate-tune-section" style="margin-top:8px">
			<div class="skate-tune-head">
				<h3 class="skate-tune-title">Cache</h3>
				<p class="skate-tune-desc">Reviews are cached for one week to minimize API usage. Clear to force an immediate refresh.</p>
			</div>
			<div class="skate-tune-row">
				<span class="skate-tune-label">Cache TTL</span>
				<div class="skate-tune-control">
					<span style="font-size:13px;color:#1d2327">7 days per Place ID</span>
				</div>
				<span class="skate-tune-hint">~4 API requests / month per Place ID</span>
			</div>
			<div style="padding:0 24px 20px">
				<form method="post" action="">
					<?php wp_nonce_field( 'skate_save_reviews', 'skate_reviews_nonce' ); ?>
					<input type="hidden" name="skate_reviews_clear_cache" value="1">
					<?php submit_button( __( 'Clear Cache', 'skate' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</div><!-- /.skate-tune-section -->

	</div><!-- /.wrap -->
	<?php
}
