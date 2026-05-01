<?php

/**
 * Site Identity - Skate
 *
 * Admin page for managing logo, favicon, site title and tagline.
 * Stores values in WordPress-native options so they work with FSE blocks.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ----------------------------------------
// Shortcodes — available on frontend and admin
// ----------------------------------------
add_shortcode( 'skate_phone',          fn() => esc_html( get_option( 'skate_phone',          '' ) ) );
add_shortcode( 'skate_fax',            fn() => esc_html( get_option( 'skate_fax',            '' ) ) );
add_shortcode( 'skate_email',          fn() => esc_html( get_option( 'skate_email',          '' ) ) );
add_shortcode( 'skate_website',        fn() => esc_html( get_option( 'skate_website',        '' ) ) );
add_shortcode( 'skate_unterschrift',   fn() => esc_html( get_option( 'skate_unterschrift',   '' ) ) );
add_shortcode( 'skate_address_street', fn() => esc_html( get_option( 'skate_address_street', '' ) ) );
add_shortcode( 'skate_address_city',   fn() => esc_html( get_option( 'skate_address_city',   '' ) ) );
add_shortcode( 'skate_intro_photo', function( $atts ) {
	$id = (int) get_option( 'skate_intro_photo_id', 0 );
	if ( ! $id ) return '';
	$a = shortcode_atts( [ 'size' => 'full', 'class' => '', 'alt' => '' ], $atts );
	return wp_get_attachment_image( $id, $a['size'], false, [
		'class' => trim( 'skate-intro-photo ' . $a['class'] ),
		'alt'   => $a['alt'] ?: get_post_meta( $id, '_wp_attachment_image_alt', true ),
	] );
} );
add_shortcode( 'skate_address', function() {
	$street = get_option( 'skate_address_street', '' );
	$city   = get_option( 'skate_address_city',   '' );
	if ( $street && $city ) return esc_html( $street ) . '<br>' . esc_html( $city );
	return esc_html( $street . $city );
} );
add_shortcode( 'skate_map', function() {
	$lat = get_option( 'skate_geo_lat', '' );
	$lng = get_option( 'skate_geo_lng', '' );
	if ( ! $lat || ! $lng ) return '';
	return do_shortcode( '[wlac-map-widget lng="' . esc_attr( $lng ) . '" lat="' . esc_attr( $lat ) . '"]' );
} );

// Social media SVG icons
function skate_social_svg( string $platform ): string {
	$svgs = [
		'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.313 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.884v2.27h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>',
		'x'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
		'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
		'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
		'linkedin'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
		'tiktok'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
		'whatsapp'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
		'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.372 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>',
	];
	return $svgs[ $platform ] ?? '';
}

// Social media shortcode
add_shortcode( 'skate_social', function( $atts ) {
	$atts   = shortcode_atts( [ 'class' => '' ], $atts );
	$order  = json_decode( get_option( 'skate_social_order', '[]' ), true ) ?: [];
	$custom = json_decode( get_option( 'skate_social_custom', '[]' ), true ) ?: [];
	$ci     = 0;
	$out    = '';

	$platform_labels = [
		'facebook'  => 'Facebook',
		'x'         => 'X (Twitter)',
		'instagram' => 'Instagram',
		'youtube'   => 'YouTube',
		'linkedin'  => 'LinkedIn',
		'tiktok'    => 'TikTok',
		'whatsapp'  => 'WhatsApp',
		'pinterest' => 'Pinterest',
	];

	if ( empty( $order ) ) {
		// Fallback: default preset order then custom
		foreach ( [ 'facebook', 'x', 'instagram', 'youtube', 'linkedin', 'tiktok', 'whatsapp', 'pinterest' ] as $key ) {
			$url = get_option( 'skate_social_' . $key, '' );
			if ( ! $url ) continue;
			$label = $platform_labels[ $key ] ?? ucfirst( $key );
			$out .= '<a href="' . esc_url( $url ) . '" class="skate-social__link skate-social__link--' . esc_attr( $key ) . '" aria-label="' . esc_attr( $label ) . '" target="_blank" rel="noopener noreferrer">' . skate_social_svg( $key ) . '</a>';
		}
		foreach ( $custom as $item ) {
			if ( empty( $item['url'] ) ) continue;
			$slug  = sanitize_title( $item['label'] ?? 'custom' );
			$label = $item['label'] ?? $slug;
			$out .= '<a href="' . esc_url( $item['url'] ) . '" class="skate-social__link skate-social__link--' . esc_attr( $slug ) . '" aria-label="' . esc_attr( $label ) . '" target="_blank" rel="noopener noreferrer">' . ( $item['svg'] ?? '' ) . '</a>';
		}
	} else {
		foreach ( $order as $key ) {
			if ( $key === 'custom' ) {
				while ( isset( $custom[ $ci ] ) && empty( $custom[ $ci ]['url'] ) ) { $ci++; }
				if ( ! isset( $custom[ $ci ] ) ) { $ci++; continue; }
				$item  = $custom[ $ci++ ];
				$slug  = sanitize_title( $item['label'] ?? 'custom' );
				$label = $item['label'] ?? $slug;
				$out .= '<a href="' . esc_url( $item['url'] ) . '" class="skate-social__link skate-social__link--' . esc_attr( $slug ) . '" aria-label="' . esc_attr( $label ) . '" target="_blank" rel="noopener noreferrer">' . ( $item['svg'] ?? '' ) . '</a>';
			} else {
				$url = get_option( 'skate_social_' . $key, '' );
				if ( ! $url ) continue;
				$label = $platform_labels[ $key ] ?? ucfirst( $key );
				$out .= '<a href="' . esc_url( $url ) . '" class="skate-social__link skate-social__link--' . esc_attr( $key ) . '" aria-label="' . esc_attr( $label ) . '" target="_blank" rel="noopener noreferrer">' . skate_social_svg( $key ) . '</a>';
			}
		}
	}

	if ( ! $out ) return '';
	$class = trim( 'skate-social ' . esc_attr( $atts['class'] ) );
	return '<div class="' . $class . '">' . $out . '</div>';
} );

// Admin-only beyond this point
if ( ! is_admin() ) return;

// ----------------------------------------
// Submenu registration
// ----------------------------------------
add_action( 'admin_menu', function () {
	add_submenu_page(
		'skate',
		__( 'Skate – Identity', 'skate' ),
		__( 'Identity', 'skate' ),
		'manage_options',
		'skate-identity',
		'skate_render_site_identity'
	);
} );

// ----------------------------------------
// Enqueue WP media uploader on this page
// ----------------------------------------
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook === 'skate_page_skate-identity' ) {
		wp_enqueue_media();
	}
} );

// ----------------------------------------
// Brand AI — AJAX handlers
// ----------------------------------------

add_action( 'wp_ajax_skate_brand_analyze', function () {
	check_ajax_referer( 'skate_brand_analyze', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_die();

	$api_key    = get_option( 'skate_claude_api_key', '' );
	$prompt     = sanitize_textarea_field( wp_unslash( $_POST['prompt']     ?? '' ) );
	$image_b64  = $_POST['image_data'] ?? '';
	$media_type = sanitize_text_field( wp_unslash( $_POST['media_type'] ?? 'image/png' ) );

	$system = 'You are a brand identity analyst for web themes. Analyze the logo and return ONLY a valid JSON object — no markdown, no explanation — with exactly these keys: skate_color_main (dominant brand hex color), skate_color_secondary (accent or complementary hex color), skate_gradient_stops (a JSON-encoded string of an array of {c,p} objects, e.g. "[{\"c\":\"#17263A\",\"p\":0},{\"c\":\"#2F4568\",\"p\":50},{\"c\":\"#17263A\",\"p\":100}]"), skate_border_radius (a JSON-encoded string of an object with keys tl/tr/br/bl each an integer 0-24, e.g. "{\"tl\":8,\"tr\":8,\"br\":8,\"bl\":8}"), skate_shadow_enabled ("1"), skate_shadow_mode ("custom"), skate_shadow_x ("0"), skate_shadow_y ("3"), skate_shadow_blur ("10"), skate_shadow_spread ("0"), skate_shadow_color ("#000000"), skate_shadow_alpha (integer 0-20 as a string).';

	$body = wp_json_encode( [
		'model'      => 'claude-opus-4-7',
		'max_tokens' => 1024,
		'system'     => $system,
		'messages'   => [ [
			'role'    => 'user',
			'content' => [
				[ 'type' => 'image', 'source' => [ 'type' => 'base64', 'media_type' => $media_type, 'data' => $image_b64 ] ],
				[ 'type' => 'text',  'text'   => $prompt ],
			],
		] ],
	] );

	$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
		'headers' => [
			'x-api-key'         => $api_key,
			'anthropic-version' => '2023-06-01',
			'content-type'      => 'application/json',
		],
		'body'    => $body,
		'timeout' => 30,
	] );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( $response->get_error_message() );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$text = $data['content'][0]['text'] ?? '';

	if ( preg_match( '/```json\s*([\s\S]+?)\s*```/', $text, $m ) ) {
		$text = $m[1];
	} elseif ( preg_match( '/\{[\s\S]+\}/', $text, $m ) ) {
		$text = $m[0];
	}

	wp_send_json_success( [ 'json' => $text ] );
} );

add_action( 'wp_ajax_skate_brand_apply', function () {
	check_ajax_referer( 'skate_brand_apply', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_die();

	$allowed = [
		'skate_color_main', 'skate_color_secondary', 'skate_gradient_stops',
		'skate_border_radius', 'skate_shadow_enabled', 'skate_shadow_mode',
		'skate_shadow_x', 'skate_shadow_y', 'skate_shadow_blur',
		'skate_shadow_spread', 'skate_shadow_color', 'skate_shadow_alpha',
	];

	$data = json_decode( stripslashes( $_POST['settings'] ?? '' ), true );
	if ( ! is_array( $data ) ) wp_send_json_error( 'Invalid JSON' );

	foreach ( $data as $key => $val ) {
		if ( in_array( $key, $allowed, true ) ) {
			update_option( sanitize_key( $key ), sanitize_text_field( (string) $val ) );
		}
	}

	// Save logo to media library and set as site logo
	$image_b64  = $_POST['image_data'] ?? '';
	$media_type = sanitize_text_field( wp_unslash( $_POST['media_type'] ?? '' ) );

	if ( $image_b64 && $media_type ) {
		$ext_map = [ 'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/svg+xml' => 'svg', 'image/webp' => 'webp', 'image/gif' => 'gif' ];
		$ext     = $ext_map[ $media_type ] ?? 'png';
		$decoded = base64_decode( $image_b64 );

		if ( $decoded ) {
			$filename = 'brand-ai-logo-' . time() . '.' . $ext;
			$upload   = wp_upload_bits( $filename, null, $decoded );

			if ( empty( $upload['error'] ) ) {
				$attachment_id = wp_insert_attachment( [
					'post_mime_type' => $media_type,
					'post_title'     => sanitize_file_name( $filename ),
					'post_status'    => 'inherit',
				], $upload['file'] );

				if ( ! is_wp_error( $attachment_id ) ) {
					require_once ABSPATH . 'wp-admin/includes/image.php';
					$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
					wp_update_attachment_metadata( $attachment_id, $metadata );
					update_option( 'site_logo', $attachment_id );
				}
			}
		}
	}

	wp_send_json_success();
} );

// ----------------------------------------
// Render function
// ----------------------------------------
function skate_render_site_identity(): void {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'identity';
	$saved      = false;

	// Handle API key save
	if (
		$active_tab === 'brand-ai' &&
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset( $_POST['skate_claude_key_nonce'] ) &&
		wp_verify_nonce( $_POST['skate_claude_key_nonce'], 'skate_save_claude_key' )
	) {
		update_option( 'skate_claude_api_key', sanitize_text_field( wp_unslash( $_POST['skate_claude_api_key'] ?? '' ) ) );
		$saved = true;
	}

	// Handle POST save
	if (
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset( $_POST['skate_identity_nonce'] ) &&
		wp_verify_nonce( $_POST['skate_identity_nonce'], 'skate_save_identity' )
	) {
		// Logo
		if ( isset( $_POST['skate_logo_remove'] ) ) {
			update_option( 'site_logo', 0 );
		} elseif ( isset( $_POST['skate_logo_id'] ) ) {
			$logo_id = absint( $_POST['skate_logo_id'] );
			if ( $logo_id > 0 ) {
				update_option( 'site_logo', $logo_id );
			}
		}

		// Favicon
		if ( isset( $_POST['skate_favicon_remove'] ) ) {
			update_option( 'site_icon', 0 );
		} elseif ( isset( $_POST['skate_favicon_id'] ) ) {
			$favicon_id = absint( $_POST['skate_favicon_id'] );
			if ( $favicon_id > 0 ) {
				update_option( 'site_icon', $favicon_id );
			}
		}

		// Intro photo
		if ( isset( $_POST['skate_intro_photo_remove'] ) ) {
			update_option( 'skate_intro_photo_id', 0 );
		} elseif ( isset( $_POST['skate_intro_photo_id'] ) ) {
			$intro_id = absint( $_POST['skate_intro_photo_id'] );
			if ( $intro_id > 0 ) {
				update_option( 'skate_intro_photo_id', $intro_id );
			}
		}

		// Title & subtitle
		update_option( 'blogname',        sanitize_text_field( wp_unslash( $_POST['skate_site_title']    ?? '' ) ) );
		update_option( 'blogdescription', sanitize_text_field( wp_unslash( $_POST['skate_site_subtitle'] ?? '' ) ) );

		// Contact details
		update_option( 'skate_address_street', sanitize_text_field( wp_unslash( $_POST['skate_address_street'] ?? '' ) ) );
		update_option( 'skate_address_city',   sanitize_text_field( wp_unslash( $_POST['skate_address_city']   ?? '' ) ) );
		update_option( 'skate_phone',          sanitize_text_field( wp_unslash( $_POST['skate_phone']          ?? '' ) ) );
		update_option( 'skate_fax',            sanitize_text_field( wp_unslash( $_POST['skate_fax']            ?? '' ) ) );
		update_option( 'skate_email',          sanitize_email(      wp_unslash( $_POST['skate_email']          ?? '' ) ) );
		update_option( 'skate_website',        sanitize_text_field( wp_unslash( $_POST['skate_website']        ?? '' ) ) );
		update_option( 'skate_unterschrift',   sanitize_text_field( wp_unslash( $_POST['skate_unterschrift']   ?? '' ) ) );
		update_option( 'skate_geo_lat',        sanitize_text_field( wp_unslash( $_POST['skate_geo_lat']        ?? '' ) ) );
		update_option( 'skate_geo_lng',        sanitize_text_field( wp_unslash( $_POST['skate_geo_lng']        ?? '' ) ) );

		// Social media presets
		foreach ( [ 'facebook', 'x', 'instagram', 'youtube', 'linkedin', 'tiktok', 'whatsapp', 'pinterest' ] as $key ) {
			update_option( 'skate_social_' . $key, esc_url_raw( wp_unslash( $_POST[ 'skate_social_' . $key ] ?? '' ) ) );
		}
		// Custom social platforms
		$allowed_svg = [
			'svg'    => [ 'xmlns' => true, 'viewbox' => true, 'fill' => true, 'aria-hidden' => true, 'class' => true, 'width' => true, 'height' => true ],
			'path'   => [ 'd' => true, 'fill' => true, 'fill-rule' => true, 'clip-rule' => true ],
			'circle' => [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ],
			'rect'   => [ 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true ],
			'g'      => [ 'fill' => true ],
		];
		$c_labels = (array) ( $_POST['skate_custom_social_label'] ?? [] );
		$c_urls   = (array) ( $_POST['skate_custom_social_url']   ?? [] );
		$c_svgs   = (array) ( $_POST['skate_custom_social_svg']   ?? [] );
		$custom_items = [];
		foreach ( $c_labels as $i => $raw_label ) {
			$label = sanitize_text_field( wp_unslash( $raw_label ) );
			$url   = esc_url_raw( wp_unslash( $c_urls[ $i ] ?? '' ) );
			$svg   = wp_kses( wp_unslash( $c_svgs[ $i ] ?? '' ), $allowed_svg );
			if ( ! $label && ! $url ) continue;
			$custom_items[] = [ 'label' => $label, 'url' => $url, 'svg' => $svg ];
		}
		update_option( 'skate_social_custom', wp_json_encode( $custom_items ) );

		// Social media order
		$raw_order = array_values( array_filter( array_map( 'sanitize_key', (array) ( $_POST['skate_social_order'] ?? [] ) ) ) );
		update_option( 'skate_social_order', wp_json_encode( $raw_order ) );

		$saved = true;
	}

	// Current values
	$logo_id    = (int) get_option( 'site_logo', 0 );
	$favicon_id = (int) get_option( 'site_icon', 0 );
	$site_title = get_option( 'blogname', '' );
	$site_sub   = get_option( 'blogdescription', '' );
	$address_street = get_option( 'skate_address_street', '' );
	$address_city   = get_option( 'skate_address_city',   '' );
	$phone          = get_option( 'skate_phone',          '' );
	$fax            = get_option( 'skate_fax',            '' );
	$email          = get_option( 'skate_email',          '' );
	$website        = get_option( 'skate_website',        '' );
	$unterschrift   = get_option( 'skate_unterschrift',   '' );
	$geo_lat        = get_option( 'skate_geo_lat',        '' );
	$geo_lng        = get_option( 'skate_geo_lng',        '' );

	$intro_photo_id = (int) get_option( 'skate_intro_photo_id', 0 );

	$social_platforms = [
		'facebook'  => 'Facebook',
		'x'         => 'X (Twitter)',
		'instagram' => 'Instagram',
		'youtube'   => 'YouTube',
		'linkedin'  => 'LinkedIn',
		'tiktok'    => 'TikTok',
		'whatsapp'  => 'WhatsApp',
		'pinterest' => 'Pinterest',
	];
	$social_always_visible = [ 'facebook', 'x', 'instagram' ];
	$social_values = [];
	foreach ( $social_platforms as $key => $label ) {
		$social_values[ $key ] = get_option( 'skate_social_' . $key, '' );
	}
	$social_custom = json_decode( get_option( 'skate_social_custom', '[]' ), true ) ?: [];
	$social_order  = json_decode( get_option( 'skate_social_order',  '[]' ), true ) ?: [];

	$logo_url        = $logo_id        ? wp_get_attachment_image_url( $logo_id,        'medium'    ) : '';
	$favicon_url     = $favicon_id     ? wp_get_attachment_image_url( $favicon_id,     'thumbnail' ) : '';
	$intro_photo_url = $intro_photo_id ? wp_get_attachment_image_url( $intro_photo_id, 'medium'    ) : '';

	?>
	<div class="wrap skate-identity-wrap">
		<h1><?php esc_html_e( 'Skate – Identity', 'skate' ); ?></h1>

		<nav class="skate-tune-tabs" style="margin-bottom:24px;">
			<a href="?page=skate-identity&tab=identity" class="skate-tune-tab<?= $active_tab === 'identity' ? ' is-active' : '' ?>">Identity</a>
			<a href="?page=skate-identity&tab=brand-ai" class="skate-tune-tab<?= $active_tab === 'brand-ai' ? ' is-active' : '' ?>">Brand AI <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;background:var(--skate-accent);color:#fff;padding:2px 6px;border-radius:4px;margin-left:4px;vertical-align:middle;opacity:.85;">Beta</span></a>
		</nav>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'skate' ); ?></p></div>
		<?php endif; ?>

		<div id="skate-tab-identity"<?= $active_tab !== 'identity' ? ' hidden' : '' ?>>
		<form method="post" action="">
			<?php wp_nonce_field( 'skate_save_identity', 'skate_identity_nonce' ); ?>

			<?php /* ══ Branding ══ */ ?>
			<div class="skate-tune-section">
				<div class="skate-tune-head">
					<h2 class="skate-tune-title"><?php esc_html_e( 'Branding', 'skate' ); ?></h2>
					<p class="skate-tune-desc"><?php esc_html_e( 'Logo, favicon and site name.', 'skate' ); ?></p>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label"><?php esc_html_e( 'Logo', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-media-row">
						<img id="skate-logo-preview"
							src="<?php echo esc_url( $logo_url ); ?>"
							class="skate-id-preview<?php echo $logo_url ? '' : ' skate-id-hidden'; ?>"
							alt="">
						<input type="hidden" id="skate-logo-id" name="skate_logo_id" value="<?php echo esc_attr( $logo_id ?: '' ); ?>">
						<input type="hidden" name="skate_logo_remove" id="skate-logo-remove" value="" disabled>
						<button type="button" class="button" id="skate-logo-btn">
							<?php echo $logo_url ? esc_html__( 'Change', 'skate' ) : esc_html__( 'Upload', 'skate' ); ?>
						</button>
						<button type="button" class="button skate-id-remove-btn<?php echo $logo_url ? '' : ' skate-id-hidden'; ?>" id="skate-logo-remove-btn">
							<?php esc_html_e( 'Remove', 'skate' ); ?>
						</button>
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label"><?php esc_html_e( 'Favicon', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-media-row">
						<img id="skate-favicon-preview"
							src="<?php echo esc_url( $favicon_url ); ?>"
							class="skate-id-preview skate-id-favicon<?php echo $favicon_url ? '' : ' skate-id-hidden'; ?>"
							alt="">
						<input type="hidden" id="skate-favicon-id" name="skate_favicon_id" value="<?php echo esc_attr( $favicon_id ?: '' ); ?>">
						<input type="hidden" name="skate_favicon_remove" id="skate-favicon-remove" value="" disabled>
						<button type="button" class="button" id="skate-favicon-btn">
							<?php echo $favicon_url ? esc_html__( 'Change', 'skate' ) : esc_html__( 'Upload', 'skate' ); ?>
						</button>
						<button type="button" class="button skate-id-remove-btn<?php echo $favicon_url ? '' : ' skate-id-hidden'; ?>" id="skate-favicon-remove-btn">
							<?php esc_html_e( 'Remove', 'skate' ); ?>
						</button>
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label"><?php esc_html_e( 'Intro photo', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-stack">
						<div class="skate-id-media-row">
							<img id="skate-intro-photo-preview"
								src="<?php echo esc_url( $intro_photo_url ); ?>"
								class="skate-id-preview skate-id-intro<?php echo $intro_photo_url ? '' : ' skate-id-hidden'; ?>"
								alt="">
							<input type="hidden" id="skate-intro-photo-id" name="skate_intro_photo_id" value="<?php echo esc_attr( $intro_photo_id ?: '' ); ?>">
							<input type="hidden" name="skate_intro_photo_remove" id="skate-intro-photo-remove" value="" disabled>
							<button type="button" class="button" id="skate-intro-photo-btn">
								<?php echo $intro_photo_url ? esc_html__( 'Change', 'skate' ) : esc_html__( 'Upload', 'skate' ); ?>
							</button>
							<button type="button" class="button skate-id-remove-btn<?php echo $intro_photo_url ? '' : ' skate-id-hidden'; ?>" id="skate-intro-photo-remove-btn">
								<?php esc_html_e( 'Remove', 'skate' ); ?>
							</button>
						</div>
						<span class="skate-tune-hint"><code>[skate_intro_photo]</code></span>
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-site-title"><?php esc_html_e( 'Site title', 'skate' ); ?></label>
					<div class="skate-tune-control">
						<input type="text" id="skate-site-title" name="skate_site_title"
							value="<?php echo esc_attr( $site_title ); ?>"
							class="regular-text">
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-site-subtitle"><?php esc_html_e( 'Tagline', 'skate' ); ?></label>
					<div class="skate-tune-control">
						<input type="text" id="skate-site-subtitle" name="skate_site_subtitle"
							value="<?php echo esc_attr( $site_sub ); ?>"
							class="regular-text">
					</div>
				</div>
			</div>

			<?php /* ══ Contact ══ */ ?>
			<div class="skate-tune-section">
				<div class="skate-tune-head">
					<h2 class="skate-tune-title"><?php esc_html_e( 'Contact', 'skate' ); ?></h2>
					<p class="skate-tune-desc"><?php esc_html_e( 'Used in footer, contact patterns and shortcodes.', 'skate' ); ?></p>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label"><?php esc_html_e( 'Address', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-stack">
						<input type="text" name="skate_address_street"
							value="<?php echo esc_attr( $address_street ); ?>"
							class="regular-text" placeholder="Musterstraße 2">
						<input type="text" name="skate_address_city"
							value="<?php echo esc_attr( $address_city ); ?>"
							class="regular-text" placeholder="12345 Musterstadt">
						<span class="skate-tune-hint">
							<code>[skate_address]</code> <?php esc_html_e( '(both lines)', 'skate' ); ?> &nbsp;·&nbsp;
							<code>[skate_address_street]</code> &nbsp;·&nbsp; <code>[skate_address_city]</code>
						</span>
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-phone"><?php esc_html_e( 'Phone', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-stack">
						<input type="text" id="skate-phone" name="skate_phone"
							value="<?php echo esc_attr( $phone ); ?>"
							class="regular-text" placeholder="+49 123 456789">
						<span class="skate-tune-hint"><code>[skate_phone]</code></span>
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-fax"><?php esc_html_e( 'Fax', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-stack">
						<input type="text" id="skate-fax" name="skate_fax"
							value="<?php echo esc_attr( $fax ); ?>"
							class="regular-text" placeholder="+49 123 456780">
						<span class="skate-tune-hint"><code>[skate_fax]</code></span>
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-email"><?php esc_html_e( 'Email', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-stack">
						<input type="email" id="skate-email" name="skate_email"
							value="<?php echo esc_attr( $email ); ?>"
							class="regular-text" placeholder="info@beispiel.de">
						<span class="skate-tune-hint"><code>[skate_email]</code></span>
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-website"><?php esc_html_e( 'Website', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-stack">
						<input type="text" id="skate-website" name="skate_website"
							value="<?php echo esc_attr( $website ); ?>"
							class="regular-text" placeholder="https://beispiel.de">
						<span class="skate-tune-hint"><code>[skate_website]</code></span>
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label" for="skate-unterschrift"><?php esc_html_e( 'Signature', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-stack">
						<input type="text" id="skate-unterschrift" name="skate_unterschrift"
							value="<?php echo esc_attr( $unterschrift ); ?>"
							class="regular-text" placeholder="Ihr Immobilienprofi – Max Mustermann">
						<span class="skate-tune-hint"><code>[skate_unterschrift]</code></span>
					</div>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label"><?php esc_html_e( 'Coordinates', 'skate' ); ?></label>
					<div class="skate-tune-control skate-id-stack">
						<div class="skate-id-geo-row">
							<input type="text" name="skate_geo_lat"
								value="<?php echo esc_attr( $geo_lat ); ?>"
								class="skate-id-geo-input" placeholder="48.1351">
							<input type="text" name="skate_geo_lng"
								value="<?php echo esc_attr( $geo_lng ); ?>"
								class="skate-id-geo-input" placeholder="11.5820">
						</div>
						<span class="skate-tune-hint">
							<?php esc_html_e( 'Latitude · Longitude — copy from Google Maps', 'skate' ); ?><br>
							<code>[skate_map]</code> → <code>[wlac-map-widget lat="…" lng="…"]</code>
						</span>
					</div>
				</div>
			</div>

			<?php /* ══ Social Media ══ */ ?>
			<div class="skate-tune-section skate-social-section">
				<div class="skate-tune-head">
					<h2 class="skate-tune-title"><?php esc_html_e( 'Social Media', 'skate' ); ?></h2>
					<p class="skate-tune-desc"><?php esc_html_e( 'Only platforms with a URL appear in [skate_social].', 'skate' ); ?></p>
				</div>

				<?php
				// Build ordered list for rendering
				$rows_to_render = [];
				if ( ! empty( $social_order ) ) {
					$custom_ci = 0;
					foreach ( $social_order as $okey ) {
						if ( $okey === 'custom' ) {
							if ( isset( $social_custom[ $custom_ci ] ) ) {
								$rows_to_render[] = [ 'type' => 'custom', 'item' => $social_custom[ $custom_ci++ ] ];
							}
						} elseif ( isset( $social_platforms[ $okey ] ) ) {
							$rows_to_render[] = [ 'type' => 'preset', 'key' => $okey, 'visible' => true ];
						}
					}
					while ( isset( $social_custom[ $custom_ci ] ) ) {
						$rows_to_render[] = [ 'type' => 'custom', 'item' => $social_custom[ $custom_ci++ ] ];
					}
					$in_order_keys = array_values( array_filter( $social_order, fn( $k ) => $k !== 'custom' ) );
					foreach ( $social_platforms as $key => $_ ) {
						if ( ! in_array( $key, $in_order_keys, true ) ) {
							$rows_to_render[] = [ 'type' => 'preset', 'key' => $key, 'visible' => false ];
						}
					}
				} else {
					foreach ( $social_platforms as $key => $_ ) {
						$always  = in_array( $key, $social_always_visible, true );
						$has_val = ! empty( $social_values[ $key ] );
						$rows_to_render[] = [ 'type' => 'preset', 'key' => $key, 'visible' => $always || $has_val ];
					}
					foreach ( $social_custom as $item ) {
						$rows_to_render[] = [ 'type' => 'custom', 'item' => $item ];
					}
				}
				$visible_preset_keys = array_map(
					fn( $r ) => $r['key'],
					array_filter( $rows_to_render, fn( $r ) => $r['type'] === 'preset' && $r['visible'] )
				);
				?>

				<div id="skate-social-rows">
				<?php foreach ( $rows_to_render as $row ) :
					if ( $row['type'] === 'preset' ) :
						$key     = $row['key'];
						$label   = $social_platforms[ $key ];
						$visible = $row['visible'];
				?>
				<div class="skate-tune-row skate-social-preset-row<?php echo $visible ? '' : ' skate-social-row--hidden'; ?>" draggable="true" data-platform="<?php echo esc_attr( $key ); ?>">
					<input type="hidden" name="skate_social_order[]" value="<?php echo $visible ? esc_attr( $key ) : ''; ?>">
					<label class="skate-tune-label skate-social-label">
						<span class="skate-social-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'skate' ); ?>">⠿</span>
						<span class="skate-social-icon"><?php echo skate_social_svg( $key ); ?></span>
						<?php echo esc_html( $label ); ?>
					</label>
					<div class="skate-tune-control skate-social-row-ctrl">
						<input type="url" name="skate_social_<?php echo esc_attr( $key ); ?>"
							value="<?php echo esc_attr( $social_values[ $key ] ); ?>"
							class="regular-text skate-social-url-input"
							placeholder="https://...">
						<button type="button" class="skate-social-remove-btn" title="<?php esc_attr_e( 'Remove', 'skate' ); ?>">×</button>
					</div>
				</div>
				<?php elseif ( $row['type'] === 'custom' ) :
					$item = $row['item'];
				?>
				<div class="skate-tune-row skate-social-custom-row" draggable="true">
					<input type="hidden" name="skate_social_order[]" value="custom">
					<div class="skate-tune-label skate-social-label">
						<span class="skate-social-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'skate' ); ?>">⠿</span>
						<span class="skate-social-custom-icon"><?php echo $item['svg'] ?? ''; ?></span>
						<input type="text" name="skate_custom_social_label[]"
							value="<?php echo esc_attr( $item['label'] ?? '' ); ?>"
							class="skate-social-custom-name"
							placeholder="<?php esc_attr_e( 'Name', 'skate' ); ?>">
					</div>
					<div class="skate-tune-control skate-social-custom-ctrl">
						<div class="skate-social-inline-row">
							<input type="url" name="skate_custom_social_url[]"
								value="<?php echo esc_attr( $item['url'] ?? '' ); ?>"
								class="regular-text"
								placeholder="https://...">
							<button type="button" class="skate-social-svg-toggle button-link"><?php esc_html_e( 'SVG', 'skate' ); ?></button>
							<button type="button" class="skate-social-remove-btn skate-social-custom-remove">×</button>
						</div>
						<textarea name="skate_custom_social_svg[]" class="skate-social-svg-input" hidden placeholder="<svg...>"><?php echo esc_textarea( $item['svg'] ?? '' ); ?></textarea>
					</div>
				</div>
				<?php endif; endforeach; ?>
				</div>

				<div class="skate-tune-row">
					<label class="skate-tune-label"></label>
					<div class="skate-tune-control skate-id-stack">
						<div class="skate-social-add-wrap">
							<button type="button" class="button" id="skate-social-add-btn">+ <?php esc_html_e( 'Add', 'skate' ); ?></button>
							<div class="skate-social-dropdown" id="skate-social-dropdown" hidden>
								<div class="skate-social-dd-head"><?php esc_html_e( 'Add platform', 'skate' ); ?></div>
								<div class="skate-social-dd-grid">
								<?php foreach ( $social_platforms as $key => $label ) : ?>
								<div class="skate-social-dd-item<?php echo in_array( $key, $visible_preset_keys, true ) ? ' skate-social-dd-item--hidden' : ''; ?>" data-platform="<?php echo esc_attr( $key ); ?>">
									<span class="skate-social-dd-badge skate-social-dd-badge--<?php echo esc_attr( $key ); ?>"><?php echo skate_social_svg( $key ); ?></span>
									<?php echo esc_html( $label ); ?>
								</div>
								<?php endforeach; ?>
								<div class="skate-social-dd-sep"></div>
								<div class="skate-social-dd-item skate-social-dd-custom">
									+ <?php esc_html_e( 'Custom…', 'skate' ); ?>
								</div>
								</div>
							</div>
						</div>
						<span class="skate-tune-hint"><code>[skate_social]</code></span>
					</div>
				</div>
			</div>

			<template id="skate-custom-row-tpl">
				<div class="skate-tune-row skate-social-custom-row" draggable="true">
					<input type="hidden" name="skate_social_order[]" value="custom">
					<div class="skate-tune-label skate-social-label">
						<span class="skate-social-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'skate' ); ?>">⠿</span>
						<span class="skate-social-custom-icon"></span>
						<input type="text" name="skate_custom_social_label[]"
							class="skate-social-custom-name"
							placeholder="<?php esc_attr_e( 'Name', 'skate' ); ?>">
					</div>
					<div class="skate-tune-control skate-social-custom-ctrl">
						<div class="skate-social-inline-row">
							<input type="url" name="skate_custom_social_url[]"
								class="regular-text"
								placeholder="https://...">
							<button type="button" class="skate-social-svg-toggle button-link"><?php esc_html_e( 'SVG', 'skate' ); ?></button>
							<button type="button" class="skate-social-remove-btn skate-social-custom-remove">×</button>
						</div>
						<textarea name="skate_custom_social_svg[]" class="skate-social-svg-input" hidden placeholder="<svg...>"></textarea>
					</div>
				</div>
			</template>

			<?php submit_button( __( 'Save', 'skate' ), 'primary', 'submit', true ); ?>

		</form>
		</div><!-- /#skate-tab-identity -->

		<div id="skate-tab-brand-ai"<?= $active_tab !== 'brand-ai' ? ' hidden' : '' ?>>

			<div class="skate-tune-section">
				<div class="skate-tune-head">
					<div class="skate-tune-title">Claude API Key</div>
					<div class="skate-tune-desc">Required to use the Brand AI analyzer. Your key is stored only in your WordPress database.</div>
				</div>
				<form method="post" action="?page=skate-identity&tab=brand-ai" style="padding:16px 24px;">
					<?php wp_nonce_field( 'skate_save_claude_key', 'skate_claude_key_nonce' ); ?>
					<div class="skate-tune-row">
						<div class="skate-tune-label">API Key</div>
						<div class="skate-tune-control" style="display:flex;gap:8px;align-items:center;">
							<input type="password" name="skate_claude_api_key"
								value="<?= esc_attr( get_option( 'skate_claude_api_key', '' ) ); ?>"
								style="width:340px;" autocomplete="new-password" placeholder="sk-ant-…">
							<button type="submit" class="button">Save key</button>
						</div>
					</div>
				</form>
			</div>

			<div class="skate-tune-section">
				<div class="skate-tune-head">
					<div class="skate-tune-title">Brand Analyzer</div>
					<div class="skate-tune-desc">Upload your logo and generate a matching color palette, gradient, shadow, and border radius for the Design settings.</div>
				</div>
				<div style="padding:16px 24px;">
					<div class="skate-tune-row">
						<div class="skate-tune-label">Logo</div>
						<div class="skate-tune-control">
							<input type="file" id="skate-brand-logo" accept="image/*">
							<div id="skate-brand-preview" style="margin-top:8px;"></div>
						</div>
					</div>
					<div class="skate-tune-row">
						<div class="skate-tune-label">Prompt</div>
						<div class="skate-tune-control">
							<textarea id="skate-brand-prompt" rows="3" style="width:100%;max-width:480px;"><?php esc_html_e( 'Analyze this logo and suggest a matching color palette, gradient, shadow style, and border radius for a modern real-estate website theme.', 'skate' ); ?></textarea>
						</div>
					</div>
					<div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
						<button type="button" id="skate-brand-generate" class="button button-primary"><?php esc_html_e( 'Generate', 'skate' ); ?></button>
						<span id="skate-brand-spinner" class="spinner" style="float:none;visibility:hidden;margin:0;"></span>
					</div>
				</div>
			</div>

			<div id="skate-brand-result-wrap" style="display:none;">
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<div class="skate-tune-title">Result</div>
						<div class="skate-tune-desc">Review the suggested settings before applying.</div>
					</div>
					<div style="padding:16px 24px;">
						<pre id="skate-brand-result" style="background:#f6f7f7;padding:12px;max-height:300px;overflow:auto;border:1px solid #ddd;border-radius:6px;font-size:12px;margin:0 0 16px;"></pre>
						<div style="display:flex;align-items:center;gap:10px;">
							<button type="button" id="skate-brand-apply" class="button button-primary"><?php esc_html_e( 'Apply to Design', 'skate' ); ?></button>
							<span id="skate-brand-apply-msg" style="color:green;display:none;"><?php esc_html_e( 'Applied! Redirecting…', 'skate' ); ?></span>
						</div>
					</div>
				</div>
			</div>

		</div><!-- /#skate-tab-brand-ai -->

	</div>

	<script>
	var skateBrandAi = {
		ajaxUrl:      '<?= esc_js( admin_url( 'admin-ajax.php' ) ) ?>',
		analyzeNonce: '<?= esc_js( wp_create_nonce( 'skate_brand_analyze' ) ) ?>',
		applyNonce:   '<?= esc_js( wp_create_nonce( 'skate_brand_apply' ) ) ?>',
		designUrl:    '<?= esc_js( admin_url( 'admin.php?page=skate-design' ) ) ?>',
	};
	(function () {
		var logoInput   = document.getElementById('skate-brand-logo');
		var preview     = document.getElementById('skate-brand-preview');
		var generateBtn = document.getElementById('skate-brand-generate');
		var spinner     = document.getElementById('skate-brand-spinner');
		var resultWrap  = document.getElementById('skate-brand-result-wrap');
		var resultPre   = document.getElementById('skate-brand-result');
		var applyBtn    = document.getElementById('skate-brand-apply');
		var applyMsg    = document.getElementById('skate-brand-apply-msg');

		if (!logoInput) return;

		var imageB64 = '', mediaType = '';

		logoInput.addEventListener('change', function () {
			var file = this.files[0];
			if (!file) return;
			mediaType = file.type;
			var reader = new FileReader();
			reader.onload = function (e) {
				imageB64 = e.target.result.split(',')[1];
				preview.innerHTML = '<img src="' + e.target.result + '" style="max-height:80px;max-width:200px;border-radius:4px;">';
			};
			reader.readAsDataURL(file);
		});

		generateBtn.addEventListener('click', function () {
			if (!imageB64) { alert('Please select a logo image first.'); return; }
			spinner.style.visibility = 'visible';
			generateBtn.disabled = true;
			resultWrap.style.display = 'none';

			var fd = new FormData();
			fd.append('action',     'skate_brand_analyze');
			fd.append('nonce',      skateBrandAi.analyzeNonce);
			fd.append('image_data', imageB64);
			fd.append('media_type', mediaType);
			fd.append('prompt',     document.getElementById('skate-brand-prompt').value);

			fetch(skateBrandAi.ajaxUrl, { method: 'POST', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					spinner.style.visibility = 'hidden';
					generateBtn.disabled = false;
					if (res.success) {
						try {
							resultPre.textContent = JSON.stringify(JSON.parse(res.data.json), null, 2);
						} catch(e) {
							resultPre.textContent = res.data.json;
						}
						resultWrap.style.display = 'block';
					} else {
						alert('Error: ' + res.data);
					}
				})
				.catch(function (e) {
					spinner.style.visibility = 'hidden';
					generateBtn.disabled = false;
					alert('Request failed: ' + e);
				});
		});

		applyBtn.addEventListener('click', function () {
			var fd = new FormData();
			fd.append('action',     'skate_brand_apply');
			fd.append('nonce',      skateBrandAi.applyNonce);
			fd.append('settings',   resultPre.textContent);
			fd.append('image_data', imageB64);
			fd.append('media_type', mediaType);

			fetch(skateBrandAi.ajaxUrl, { method: 'POST', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res.success) {
						applyMsg.style.display = 'inline';
						setTimeout(function () {
							window.location.href = skateBrandAi.designUrl;
						}, 900);
					} else {
						alert('Apply failed: ' + res.data);
					}
				});
		});
	}());
	</script>

	<style>
	.skate-identity-wrap { max-width: 820px; }
	.skate-identity-wrap > h1 { margin-bottom: 16px; }

	.skate-tune-tabs {
		display: flex;
		gap: 0;
		margin-bottom: 20px;
		border-bottom: 2px solid #dcdcde;
	}
	.skate-tune-tab {
		display: inline-flex;
		align-items: center;
		padding: 10px 20px;
		text-decoration: none;
		color: #50575e;
		font-weight: 500;
		font-size: 14px;
		border-bottom: 2px solid transparent;
		margin-bottom: -2px;
		transition: color .15s, border-color .15s;
	}
	.skate-tune-tab.is-active { color: var(--skate-accent); border-bottom-color: var(--skate-accent); font-weight: 600; }
	.skate-tune-tab:hover:not(.is-active) { color: #1d2327; }

	.skate-tune-section {
		margin-bottom: 24px;
		background: #fff;
		border: 1px solid #dcdcdc;
		border-radius: 10px;
		max-width: 820px;
		overflow: hidden;
	}
	.skate-tune-head {
		padding: 16px 24px 14px;
		border-bottom: 1px solid #f0f0f1;
	}
	.skate-tune-title {
		margin: 0 0 2px;
		font-size: 10px;
		font-weight: 700;
		color: #1d2327;
		text-transform: uppercase;
		letter-spacing: .1em;
	}
	.skate-tune-desc { margin: 0; font-size: 12px; color: #8c8f94; }
	.skate-tune-row {
		display: grid;
		grid-template-columns: 148px 1fr;
		gap: 8px 20px;
		align-items: center;
		padding: 13px 24px;
		border-top: 1px solid #f4f4f5;
	}
	.skate-tune-label { font-size: 13px; font-weight: 500; color: #1d2327; padding-top: 2px; }
	.skate-tune-control { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
	.skate-tune-hint { font-size: 12px; color: #a8aaac; line-height: 1.4; }
	.skate-tune-hint code { background: #f0f0f1; padding: 1px 5px; border-radius: 3px; font-size: 11px; color: #3c434a; }

	/* Identity-specific */
	.skate-id-stack { flex-direction: column; align-items: flex-start; gap: 5px; }
	.skate-id-media-row { align-items: center; }
	.skate-id-preview {
		width: 72px; height: 72px;
		object-fit: contain;
		border: 1px solid #ddd;
		border-radius: 4px;
		background: #f9f9f9;
		padding: 4px;
	}
	.skate-id-favicon { width: 36px; height: 36px; }
	.skate-id-intro  { width: 56px; height: 72px; object-fit: cover; border-radius: 6px; }
	.skate-id-hidden { display: none; }
	.skate-id-remove-btn { color: #b32d2e !important; }
	.skate-id-geo-row { display: flex; gap: 8px; }
	.skate-id-geo-input { width: 120px; }

	.skate-tune-hint code {
		cursor: pointer;
		transition: background .15s, color .15s;
	}
	.skate-tune-hint code:hover { background: #dde; color: #135e96; }
	.skate-tune-hint code.skate-copied { background: #d7f5e3 !important; color: #1a7942 !important; }

	/* Social media rows */
	.skate-social-label { display: flex; align-items: center; gap: 7px; }
	.skate-social-icon, .skate-social-custom-icon { display: flex; align-items: center; flex-shrink: 0; }
	.skate-social-icon svg, .skate-social-custom-icon svg { width: 18px; height: 18px; }
	.skate-social-custom-name { width: 90px; height: 28px; }
	.skate-social-row--hidden { display: none !important; }
	.skate-social-row-ctrl { flex-wrap: nowrap; align-items: center; gap: 6px; }
	.skate-social-drag-handle {
		cursor: grab; color: #c5c8cc; font-size: 15px; flex-shrink: 0;
		padding: 0 2px; border-radius: 3px; user-select: none; line-height: 1;
	}
	.skate-social-drag-handle:hover { color: #50575e; }
	.skate-social-preset-row.skate-dragging,
	.skate-social-custom-row.skate-dragging { opacity: .35; }
	.skate-social-preset-row.skate-drag-over,
	.skate-social-custom-row.skate-drag-over { box-shadow: inset 0 2px 0 #135e96; }
	.skate-social-custom-ctrl { flex-direction: column; align-items: flex-start; gap: 5px; }
	.skate-social-inline-row { display: flex; align-items: center; gap: 6px; width: 100%; }
	.skate-social-svg-toggle {
		flex-shrink: 0; font-size: 11px; font-weight: 500; color: #8c8f94;
		padding: 3px 7px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;
		background: #f9f9f9; line-height: 1.4; white-space: nowrap;
	}
	.skate-social-svg-toggle:hover, .skate-social-svg-toggle.active { color: #135e96; border-color: #135e96; background: #f0f6ff; }
	.skate-social-svg-input {
		width: 100%; min-height: 56px; font-family: monospace; font-size: 11px;
		border: 1px solid #ddd; border-radius: 3px; padding: 4px 6px; resize: vertical;
	}
	.skate-social-remove-btn {
		flex-shrink: 0; background: none; border: none;
		cursor: pointer; color: #c0c4ca; font-size: 18px; line-height: 1;
		width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;
		padding: 0; border-radius: 4px; transition: color .15s, background .15s;
	}
	.skate-social-remove-btn:hover { color: #b32d2e; background: #fdf0f0; }
	/* Brand colors on rows */
	.skate-social-preset-row[data-platform="facebook"]  .skate-social-icon { color: #1877F2; }
	.skate-social-preset-row[data-platform="x"]         .skate-social-icon { color: #000; }
	.skate-social-preset-row[data-platform="instagram"] .skate-social-icon { color: #E1306C; }
	.skate-social-preset-row[data-platform="youtube"]   .skate-social-icon { color: #FF0000; }
	.skate-social-preset-row[data-platform="linkedin"]  .skate-social-icon { color: #0A66C2; }
	.skate-social-preset-row[data-platform="tiktok"]    .skate-social-icon { color: #010101; }
	.skate-social-preset-row[data-platform="whatsapp"]  .skate-social-icon { color: #25D366; }
	.skate-social-preset-row[data-platform="pinterest"] .skate-social-icon { color: #E60023; }
	/* Dropdown */
	.skate-social-section { overflow: visible !important; }
	.skate-social-add-wrap { display: inline-block; position: relative; }
	.skate-social-dropdown {
		position: absolute; top: calc(100% + 4px); left: 0; z-index: 9999;
		background: #fff; border: 1px solid #dcdcdc; border-radius: 10px;
		box-shadow: 0 8px 24px rgba(0,0,0,.14); min-width: 210px; padding: 6px;
	}
	.skate-social-dd-head {
		padding: 6px 8px 8px; font-size: 10px; font-weight: 700; color: #8c8f94;
		text-transform: uppercase; letter-spacing: .08em;
	}
	.skate-social-dd-grid {
		display: grid; grid-template-columns: 1fr 1fr; gap: 2px;
	}
	.skate-social-dd-item {
		display: flex; align-items: center; gap: 8px;
		padding: 7px 9px; cursor: pointer; font-size: 12px; color: #1d2327;
		border-radius: 6px; transition: background .12s;
	}
	.skate-social-dd-item:hover { background: #f0f6ff; }
	.skate-social-dd-item--hidden { display: none; }
	.skate-social-dd-badge {
		width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0;
		display: flex; align-items: center; justify-content: center; color: #fff;
	}
	.skate-social-dd-badge svg { width: 14px; height: 14px; }
	.skate-social-dd-badge--facebook  { background: #1877F2; }
	.skate-social-dd-badge--x         { background: #000; }
	.skate-social-dd-badge--instagram { background: linear-gradient(135deg, #f09433, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888); }
	.skate-social-dd-badge--youtube   { background: #FF0000; }
	.skate-social-dd-badge--linkedin  { background: #0A66C2; }
	.skate-social-dd-badge--tiktok    { background: #010101; }
	.skate-social-dd-badge--whatsapp  { background: #25D366; }
	.skate-social-dd-badge--pinterest { background: #E60023; }
	.skate-social-dd-sep { border-top: 1px solid #f0f0f1; margin: 6px 0 4px; }
	.skate-social-dd-custom {
		grid-column: 1 / -1; color: #135e96; font-weight: 500;
		border: 1px dashed #c5d9f0; border-radius: 6px; justify-content: center;
	}
	.skate-social-dd-custom:hover { background: #f0f6ff; border-color: #135e96; }
	</style>

	<script>
	(function(){
		function makeFrame( title, btnId, previewId, inputId, removeInputId, removeBtnId ) {
			var frame;
			document.getElementById( btnId ).addEventListener( 'click', function() {
				if ( frame ) { frame.open(); return; }
				frame = wp.media({
					title:    title,
					button:   { text: title },
					multiple: false,
					library:  { type: 'image' }
				});
				frame.on( 'select', function() {
					var att = frame.state().get('selection').first().toJSON();
					var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
					var preview = document.getElementById( previewId );
					preview.src = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_=' + Date.now();
					preview.classList.remove( 'skate-id-hidden' );
					document.getElementById( inputId ).value = att.id;
					document.getElementById( removeInputId ).disabled = true;
					document.getElementById( removeBtnId ).classList.remove( 'skate-id-hidden' );
				});
				frame.open();
			});

			document.getElementById( removeBtnId ).addEventListener( 'click', function() {
				document.getElementById( previewId ).classList.add( 'skate-id-hidden' );
				document.getElementById( inputId ).value = '';
				document.getElementById( removeInputId ).disabled = false;
				this.classList.add( 'skate-id-hidden' );
			});
		}

		makeFrame(
			'<?php echo esc_js( __( 'Select logo', 'skate' ) ); ?>',
			'skate-logo-btn', 'skate-logo-preview', 'skate-logo-id', 'skate-logo-remove', 'skate-logo-remove-btn'
		);
		makeFrame(
			'<?php echo esc_js( __( 'Select favicon', 'skate' ) ); ?>',
			'skate-favicon-btn', 'skate-favicon-preview', 'skate-favicon-id', 'skate-favicon-remove', 'skate-favicon-remove-btn'
		);
		makeFrame(
			'<?php echo esc_js( __( 'Select intro photo', 'skate' ) ); ?>',
			'skate-intro-photo-btn', 'skate-intro-photo-preview', 'skate-intro-photo-id', 'skate-intro-photo-remove', 'skate-intro-photo-remove-btn'
		);
		// Click-to-copy shortcodes
		document.querySelectorAll('.skate-tune-hint code').forEach(function(el) {
			el.title = 'Click to copy';
			el.addEventListener('click', function() {
				navigator.clipboard.writeText(el.textContent).then(function() {
					el.classList.add('skate-copied');
					setTimeout(function() { el.classList.remove('skate-copied'); }, 1500);
				});
			});
		});

		// ── Social media dynamic rows ──
		var addBtn           = document.getElementById('skate-social-add-btn');
		var dropdown         = document.getElementById('skate-social-dropdown');
		var allRowsContainer = document.getElementById('skate-social-rows');

		// Toggle dropdown
		addBtn.addEventListener('click', function(e) {
			e.stopPropagation();
			dropdown.hidden = !dropdown.hidden;
		});
		document.addEventListener('click', function() { dropdown.hidden = true; });
		dropdown.addEventListener('click', function(e) { e.stopPropagation(); });

		// Dropdown item click
		dropdown.addEventListener('click', function(e) {
			var item = e.target.closest('.skate-social-dd-item');
			if (!item) return;
			if (item.classList.contains('skate-social-dd-custom')) {
				addCustomRow();
			} else {
				var platform = item.dataset.platform;
				var row = allRowsContainer.querySelector('.skate-social-preset-row[data-platform="' + platform + '"]');
				if (row) {
					row.classList.remove('skate-social-row--hidden');
					var orderInput = row.querySelector('input[name="skate_social_order[]"]');
					if (orderInput) orderInput.value = platform;
					row.querySelector('.skate-social-url-input').focus();
				}
				item.classList.add('skate-social-dd-item--hidden');
			}
			dropdown.hidden = true;
		});

		// Remove preset row (event delegation)
		document.addEventListener('click', function(e) {
			var btn = e.target.closest('.skate-social-preset-row .skate-social-remove-btn');
			if (!btn) return;
			var row = btn.closest('.skate-social-preset-row');
			row.querySelector('.skate-social-url-input').value = '';
			row.classList.add('skate-social-row--hidden');
			var orderInput = row.querySelector('input[name="skate_social_order[]"]');
			if (orderInput) orderInput.value = '';
			var ddItem = dropdown.querySelector('[data-platform="' + row.dataset.platform + '"]');
			if (ddItem) ddItem.classList.remove('skate-social-dd-item--hidden');
		});

		// Remove custom row
		document.addEventListener('click', function(e) {
			var btn = e.target.closest('.skate-social-custom-remove');
			if (!btn) return;
			btn.closest('.skate-social-custom-row').remove();
		});

		// Toggle SVG textarea
		document.addEventListener('click', function(e) {
			var btn = e.target.closest('.skate-social-svg-toggle');
			if (!btn) return;
			var row = btn.closest('.skate-social-custom-row');
			var textarea = row.querySelector('.skate-social-svg-input');
			textarea.hidden = !textarea.hidden;
			btn.classList.toggle('active', !textarea.hidden);
		});

		// Wire SVG live preview for a row
		function wireRow(row) {
			var svgArea = row.querySelector('.skate-social-svg-input');
			var iconEl  = row.querySelector('.skate-social-custom-icon');
			if (svgArea && iconEl) {
				svgArea.addEventListener('input', function() { iconEl.innerHTML = svgArea.value; });
			}
		}

		// Add custom row from template
		function addCustomRow() {
			var tpl = document.getElementById('skate-custom-row-tpl');
			var clone = tpl.content.cloneNode(true);
			allRowsContainer.appendChild(clone);
			var lastRow = allRowsContainer.lastElementChild;
			wireRow(lastRow);
			lastRow.querySelector('.skate-social-custom-name').focus();
		}

		// Wire existing custom rows on page load
		allRowsContainer.querySelectorAll('.skate-social-custom-row').forEach(wireRow);

		// Drag-to-reorder (all rows)
		var dragSrc = null;
		allRowsContainer.addEventListener('dragstart', function(e) {
			var row = e.target.closest('[draggable="true"]');
			if (!row || !allRowsContainer.contains(row)) return;
			dragSrc = row;
			row.classList.add('skate-dragging');
			e.dataTransfer.effectAllowed = 'move';
		});
		allRowsContainer.addEventListener('dragend', function() {
			if (dragSrc) dragSrc.classList.remove('skate-dragging');
			allRowsContainer.querySelectorAll('.skate-drag-over').forEach(function(el) {
				el.classList.remove('skate-drag-over');
			});
			dragSrc = null;
		});
		allRowsContainer.addEventListener('dragover', function(e) {
			e.preventDefault();
			var row = e.target.closest('[draggable="true"]');
			if (!row || row === dragSrc || !allRowsContainer.contains(row)) return;
			allRowsContainer.querySelectorAll('.skate-drag-over').forEach(function(el) { el.classList.remove('skate-drag-over'); });
			row.classList.add('skate-drag-over');
			e.dataTransfer.dropEffect = 'move';
		});
		allRowsContainer.addEventListener('drop', function(e) {
			e.preventDefault();
			var target = e.target.closest('[draggable="true"]');
			if (!target || target === dragSrc || !dragSrc) return;
			if (!allRowsContainer.contains(target)) return;
			var rows = Array.from(allRowsContainer.querySelectorAll('[draggable="true"]'));
			if (rows.indexOf(dragSrc) < rows.indexOf(target)) {
				target.after(dragSrc);
			} else {
				target.before(dragSrc);
			}
			target.classList.remove('skate-drag-over');
		});
	})();
	</script>
	<?php
}

// ----------------------------------------
// Reorder submenu: Core Settings → Site Identity → Presets → Sitemap
// ----------------------------------------
add_action( 'admin_menu', function () {
	global $submenu;
	if ( empty( $submenu['skate'] ) ) return;

	$desired = [ 'skate-core-settings', 'skate-identity', 'skate-presets', 'skate-master-tuner', 'skate' ];
	$indexed = [];
	foreach ( $submenu['skate'] as $item ) {
		$indexed[ $item[2] ] = $item;
	}

	$reordered = [];
	foreach ( $desired as $slug ) {
		if ( isset( $indexed[ $slug ] ) ) {
			$reordered[] = $indexed[ $slug ];
			unset( $indexed[ $slug ] );
		}
	}
	foreach ( $indexed as $item ) {
		$reordered[] = $item;
	}

	$submenu['skate'] = $reordered;
}, 99 );
