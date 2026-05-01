<?php

/**
 * Theme Presets - Skate
 *
 * Allows switching visual presets (shape, spacing, color, weight) via the admin.
 * The active preset is stored in wp_options and applied at runtime via two mechanisms:
 *   1. wp_theme_json_data_theme filter  — merges theme.json overrides (frontend + editor)
 *   2. wp_head / enqueue_block_editor_assets — injects CSS for things outside theme.json scope
 *
 * Border-radius system:
 *   - Each preset defines a default `radius` value.
 *   - `skate_get_active_radius()` returns the effective radius (custom override or preset default).
 *   - `--pace-radius` CSS custom property is always injected on :root.
 *   - `.pace-radius` utility class lets any block adopt the preset radius.
 *   - Core blocks receive radius via theme.json overrides (generated dynamically).
 *
 * Gradient system:
 *   - Stops stored as JSON in `skate_gradient_stops` option: [{"c":"#hex","p":int},...]
 *   - `skate_gradient_angle` stored separately.
 *   - Dynamic: 2–8 stops, add/remove in admin UI.
 *
 * Shadow system:
 *   - Individual options: skate_shadow_x/y/blur/spread/color/alpha
 *   - `--pace-shadow` CSS custom property injected on :root.
 *   - `.pace-shadow` utility class applies it as box-shadow.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ----------------------------------------
// Color & Gradient defaults
// ----------------------------------------
define( 'SKATE_DEFAULT_COLOR_MAIN',      '#17263a' );
define( 'SKATE_DEFAULT_COLOR_SECONDARY', '#d6b36d' );
define( 'SKATE_DEFAULT_COLOR_BLACK',      '#17263A' );
define( 'SKATE_DEFAULT_COLOR_LIGHT_GRAY', '#F2F4F6' );
define( 'SKATE_DEFAULT_GRADIENT_ANGLE',   180 );
define( 'SKATE_DEFAULT_GRADIENT_C1',     '#17263A' );
define( 'SKATE_DEFAULT_GRADIENT_P1',     0 );
define( 'SKATE_DEFAULT_GRADIENT_C2',     '#2F4568' );
define( 'SKATE_DEFAULT_GRADIENT_P2',     50 );
define( 'SKATE_DEFAULT_GRADIENT_C3',     '#17263A' );
define( 'SKATE_DEFAULT_GRADIENT_P3',     100 );

// Shadow defaults
define( 'SKATE_DEFAULT_SHADOW_X',      0 );
define( 'SKATE_DEFAULT_SHADOW_Y',      4 );
define( 'SKATE_DEFAULT_SHADOW_BLUR',   16 );
define( 'SKATE_DEFAULT_SHADOW_SPREAD', 0 );
define( 'SKATE_DEFAULT_SHADOW_COLOR',  '#000000' );
define( 'SKATE_DEFAULT_SHADOW_ALPHA',  12 ); // 0–100 → 0.00–1.00 opacity

// Spacer defaults
define( 'SKATE_DEFAULT_SPACER_SIZE', 'm' ); // s | m | l | xl

/**
 * Reads the raw theme.json file as an array (cached in a static).
 * Used to get dynamic defaults so constants stay in sync automatically.
 * NOTE: reads the file directly to avoid recursion with wp_theme_json_data_theme filter.
 */
function skate_read_theme_json(): array {
	static $data = null;
	if ( $data === null ) {
		$path = get_template_directory() . '/theme.json';
		$raw  = file_exists( $path ) ? file_get_contents( $path ) : '';
		$data = $raw ? ( json_decode( $raw, true ) ?? [] ) : [];
	}
	return $data;
}

/**
 * Returns the color value for a palette slug from theme.json, or empty string if not found.
 */
function skate_theme_json_color( string $slug ): string {
	$palette = skate_read_theme_json()['settings']['color']['palette'] ?? [];
	foreach ( $palette as $entry ) {
		if ( ( $entry['slug'] ?? '' ) === $slug ) return $entry['color'] ?? '';
	}
	return '';
}

function skate_get_active_color_main(): string {
	return get_option( 'skate_color_main', '' )
		?: skate_theme_json_color( 'main-color' )
		?: SKATE_DEFAULT_COLOR_MAIN;
}

function skate_get_active_color_secondary(): string {
	return get_option( 'skate_color_secondary', '' )
		?: skate_theme_json_color( 'secondary-color' )
		?: SKATE_DEFAULT_COLOR_SECONDARY;
}

function skate_get_active_color_black(): string {
	return get_option( 'skate_color_black', '' )
		?: skate_theme_json_color( 'black' )
		?: SKATE_DEFAULT_COLOR_BLACK;
}

function skate_get_active_color_light_gray(): string {
	return get_option( 'skate_color_light_gray', '' )
		?: skate_theme_json_color( 'light-gray' )
		?: SKATE_DEFAULT_COLOR_LIGHT_GRAY;
}

// ── Button style getters ─────────────────────────────────────────────────────

function skate_get_active_btn_fill_bg(): string {
	return get_option( 'skate_btn_fill_bg', '' ) ?: skate_get_active_color_secondary();
}
function skate_get_active_btn_fill_text(): string {
	return get_option( 'skate_btn_fill_text', '' ) ?: skate_get_active_color_black();
}
function skate_get_active_btn_fill_hover_bg(): string {
	return get_option( 'skate_btn_fill_hover_bg', '' ) ?: skate_get_active_color_main();
}
function skate_get_active_btn_fill_hover_text(): string {
	return get_option( 'skate_btn_fill_hover_text', '' ) ?: '#ffffff';
}
function skate_get_active_btn_outline_color(): string {
	return get_option( 'skate_btn_outline_color', '' ) ?: skate_get_active_color_secondary();
}
function skate_get_active_btn_outline_border_width(): int {
	$v = (int) get_option( 'skate_btn_outline_border_width', 0 );
	return ( $v >= 1 && $v <= 4 ) ? $v : 2;
}
function skate_get_active_btn_outline_hover_bg(): string {
	return get_option( 'skate_btn_outline_hover_bg', '' ) ?: skate_get_active_color_secondary();
}
function skate_get_active_btn_outline_hover_text(): string {
	return get_option( 'skate_btn_outline_hover_text', '' ) ?: '#ffffff';
}

function skate_has_custom_button_styles(): bool {
	return get_option( 'skate_btn_fill_bg', '' ) !== ''
		|| get_option( 'skate_btn_fill_text', '' ) !== ''
		|| get_option( 'skate_btn_outline_color', '' ) !== ''
		|| get_option( 'skate_btn_outline_border_width', '' ) !== '';
}

function skate_default_gradient_stops(): array {
	return [
		[ 'c' => SKATE_DEFAULT_GRADIENT_C1, 'p' => SKATE_DEFAULT_GRADIENT_P1 ],
		[ 'c' => SKATE_DEFAULT_GRADIENT_C2, 'p' => SKATE_DEFAULT_GRADIENT_P2 ],
		[ 'c' => SKATE_DEFAULT_GRADIENT_C3, 'p' => SKATE_DEFAULT_GRADIENT_P3 ],
	];
}

function skate_get_active_gradient_data(): array {
	$angle_raw = get_option( 'skate_gradient_angle', '' );
	$angle     = $angle_raw !== '' ? (int) $angle_raw : SKATE_DEFAULT_GRADIENT_ANGLE;

	$stops_json = get_option( 'skate_gradient_stops', '' );
	$stops = $stops_json ? ( json_decode( $stops_json, true ) ?: skate_default_gradient_stops() ) : skate_default_gradient_stops();

	return [ 'angle' => $angle, 'stops' => $stops ];
}

function skate_build_gradient_string( array $d ): string {
	$stops_css = implode( ',', array_map(
		fn( $s ) => $s['c'] . ' ' . $s['p'] . '%',
		$d['stops']
	) );
	return sprintf( 'linear-gradient(%ddeg,%s)', $d['angle'], $stops_css );
}

function skate_has_custom_colors(): bool {
	return get_option( 'skate_color_main', '' ) !== ''
		|| get_option( 'skate_color_secondary', '' ) !== ''
		|| get_option( 'skate_color_black', '' ) !== ''
		|| get_option( 'skate_color_light_gray', '' ) !== '';
}

function skate_has_custom_gradient(): bool {
	return get_option( 'skate_gradient_stops', '' ) !== ''
		|| get_option( 'skate_gradient_angle', '' ) !== '';
}

function skate_get_wp_shadow_presets(): array {
	return [
		[ 'slug' => 'natural',  'name' => 'Natural', 'shadow' => '6px 6px 9px rgba(0,0,0,0.2)' ],
		[ 'slug' => 'deep',     'name' => 'Deep',    'shadow' => '12px 12px 50px rgba(0,0,0,0.4)' ],
		[ 'slug' => 'sharp',    'name' => 'Sharp',   'shadow' => '6px 6px 0px rgba(0,0,0,0.2)' ],
		[ 'slug' => 'outlined', 'name' => 'Outline', 'shadow' => '6px 6px 0px -3px rgba(255,255,255,1), 6px 6px rgba(0,0,0,1)' ],
		[ 'slug' => 'crisp',    'name' => 'Crisp',   'shadow' => '6px 6px 0px rgba(0,0,0,1)' ],
	];
}

function skate_get_active_shadow(): string {
	$mode = get_option( 'skate_shadow_mode', 'preset' );
	if ( $mode !== 'custom' ) {
		$slug  = get_option( 'skate_shadow_preset_slug', 'natural' );
		$valid = array_column( skate_get_wp_shadow_presets(), 'slug' );
		if ( ! in_array( $slug, $valid, true ) ) $slug = 'natural';
		return "var(--wp--preset--shadow--{$slug})";
	}
	$x      = (int) ( get_option( 'skate_shadow_x',      '' ) !== '' ? get_option( 'skate_shadow_x' )      : SKATE_DEFAULT_SHADOW_X );
	$y      = (int) ( get_option( 'skate_shadow_y',      '' ) !== '' ? get_option( 'skate_shadow_y' )      : SKATE_DEFAULT_SHADOW_Y );
	$blur   = (int) ( get_option( 'skate_shadow_blur',   '' ) !== '' ? get_option( 'skate_shadow_blur' )   : SKATE_DEFAULT_SHADOW_BLUR );
	$spread = (int) ( get_option( 'skate_shadow_spread', '' ) !== '' ? get_option( 'skate_shadow_spread' ) : SKATE_DEFAULT_SHADOW_SPREAD );
	$color  = get_option( 'skate_shadow_color', '' ) ?: SKATE_DEFAULT_SHADOW_COLOR;
	$alpha  = (int) ( get_option( 'skate_shadow_alpha',  '' ) !== '' ? get_option( 'skate_shadow_alpha' )  : SKATE_DEFAULT_SHADOW_ALPHA );

	$r = hexdec( substr( $color, 1, 2 ) );
	$g = hexdec( substr( $color, 3, 2 ) );
	$b = hexdec( substr( $color, 5, 2 ) );
	$a = round( $alpha / 100, 2 );

	return "{$x}px {$y}px {$blur}px {$spread}px rgba({$r},{$g},{$b},{$a})";
}

function skate_has_custom_shadow(): bool {
	foreach ( [ 'skate_shadow_x', 'skate_shadow_y', 'skate_shadow_blur', 'skate_shadow_spread', 'skate_shadow_color', 'skate_shadow_alpha' ] as $k ) {
		if ( get_option( $k, '' ) !== '' ) return true;
	}
	return false;
}

function skate_get_spacer_sizes(): array {
	return [
		's'  => [ 'mobile' => 20,  'desktop' => 40  ],
		'm'  => [ 'mobile' => 40,  'desktop' => 80  ],
		'l'  => [ 'mobile' => 60,  'desktop' => 120 ],
		'xl' => [ 'mobile' => 80,  'desktop' => 160 ],
	];
}

function skate_get_active_spacer(): string {
	$size  = get_option( 'skate_spacer_size', SKATE_DEFAULT_SPACER_SIZE );
	$sizes = skate_get_spacer_sizes();
	$vals  = $sizes[ $size ] ?? $sizes[ SKATE_DEFAULT_SPACER_SIZE ];
	$vw    = round( $vals['desktop'] / 1340 * 100, 2 );
	return "clamp({$vals['mobile']}px,{$vw}vw,{$vals['desktop']}px)";
}

/**
 * Preset definitions.
 *
 * Each preset may define:
 *   'radius'    (string)     — default border-radius for this preset (e.g. '15px')
 *   'overrides' (array|null) — partial theme.json-compatible array, merged at runtime
 *   'css'       (string|null) — raw CSS injected on frontend and in the block editor
 */
function skate_get_presets(): array {
	return [

		'eckig' => [
			'label'       => __( 'Square', 'skate' ),
			'description' => __( 'Clean, sharp shapes — modern and precise.', 'skate' ),
			'icon'        => '⬜',
			'radius'      => '0px',
			'overrides'   => null,
			'css'         => null,
		],

		'rund' => [
			'label'       => __( 'Rounded', 'skate' ),
			'description' => __( 'Soft, round shapes — friendly and inviting.', 'skate' ),
			'icon'        => '🔵',
			'radius'      => '15px',
			'overrides'   => null, // radius applied dynamically via skate_build_radius_overrides()
			'css' => '
				/* Images */
				.wp-block-image img   { border-radius: var(--pace-radius); box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
				figure.wp-block-image { overflow: hidden; border-radius: var(--pace-radius); }

				/* Buttons */
				.wp-block-button__link { box-shadow: 0 2px 8px rgba(0,0,0,0.10); }

				/* Form inputs */
				input, select, textarea { border-radius: var(--pace-radius) !important; }
				.wp-block-search__input { border-radius: var(--pace-radius) !important; }

				/* Group blocks with background (cards) — skip full-width layout wrappers */
				.wp-block-group:not(.alignfull):not(.alignwide)[style*="background"] { border-radius: var(--pace-radius); overflow: hidden; }

				/* Cover blocks */
				.wp-block-cover { border-radius: var(--pace-radius); overflow: hidden; }

				/* Greenshift containers with explicit background */
				.gspb_container[style*="background"] { border-radius: var(--pace-radius); overflow: hidden; }

				/* Subtle softening */
				body { letter-spacing: 0.1px; }
			',
		],

		'luftig' => [
			'label'       => __( 'Airy', 'skate' ),
			'description' => __( 'More whitespace — generous and premium.', 'skate' ),
			'icon'        => '🌬️',
			'radius'      => '0px',
			'overrides'   => [
				'version' => 3,
				'styles'  => [
					'spacing' => [ 'blockGap' => '1.8rem' ],
				],
			],
			'css' => '
				:root { --gs-row-column-padding: 20px min(4vw, 30px); }
			',
		],

		'warm' => [
			'label'       => __( 'Warm', 'skate' ),
			'description' => __( 'Richer gold tones — warm and inviting.', 'skate' ),
			'icon'        => '🌅',
			'radius'      => '0px',
			'overrides'   => [
				'version'  => 3,
				'settings' => [
					'color' => [
						'palette' => [
							[ 'color' => '#b8892a', 'name' => 'Secondary Color', 'slug' => 'secondary-color' ],
						],
					],
				],
			],
			'css'         => null,
		],

		'mutig' => [
			'label'       => __( 'Bold', 'skate' ),
			'description' => __( 'Strong typography and deep shadows — confident.', 'skate' ),
			'icon'        => '⚡',
			'radius'      => '0px',
			'overrides'   => [
				'version' => 3,
				'styles'  => [
					'typography' => [
						'fontWeight'     => '400',
						'letterSpacing'  => '0px',
					],
					'blocks' => [
						'core/button' => [
							'spacing' => [
								'padding' => [
									'top'    => '14px',
									'bottom' => '14px',
									'left'   => '50px',
									'right'  => '50px',
								],
							],
						],
					],
				],
			],
			'css' => '
				h1, h2, h3, h4 { font-weight: 800; letter-spacing: -0.5px; }
				.wp-block-image img { box-shadow: 0 8px 30px rgba(0,0,0,0.15); }
				.wp-block-button__link { box-shadow: 0 4px 16px rgba(62,207,202,0.35); }
			',
		],

	];
}

// ----------------------------------------
// Helper: get the 4 border-radius corners as an array [tl, tr, br, bl].
// Reads JSON from skate_border_radius, falls back to preset default (uniform).
// ----------------------------------------
function skate_get_active_radius_corners(): array {
	$raw = get_option( 'skate_border_radius', '' );
	if ( $raw !== '' ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) && isset( $decoded['tl'] ) ) {
			return [
				'tl' => (int) $decoded['tl'],
				'tr' => (int) $decoded['tr'],
				'br' => (int) $decoded['br'],
				'bl' => (int) $decoded['bl'],
			];
		}
		// Legacy: plain integer stored before 4-corner support
		$px = (int) $raw;
		return [ 'tl' => $px, 'tr' => $px, 'br' => $px, 'bl' => $px ];
	}
	$key     = get_option( 'skate_active_preset', 'eckig' );
	$presets = skate_get_presets();
	$px      = (int) ( $presets[ $key ]['radius'] ?? '0px' );
	return [ 'tl' => $px, 'tr' => $px, 'br' => $px, 'bl' => $px ];
}

// ----------------------------------------
// Helper: get the effective border-radius as a CSS shorthand string.
// ----------------------------------------
function skate_get_active_radius(): string {
	$c = skate_get_active_radius_corners();
	if ( $c['tl'] === $c['tr'] && $c['tr'] === $c['br'] && $c['br'] === $c['bl'] ) {
		return $c['tl'] . 'px';
	}
	return $c['tl'] . 'px ' . $c['tr'] . 'px ' . $c['br'] . 'px ' . $c['bl'] . 'px';
}

// ----------------------------------------
// Helper: build theme.json block overrides for the current border-radius corners.
// ----------------------------------------
function skate_build_radius_overrides( array $corners ): array {
	$all_same = $corners['tl'] === $corners['tr']
		&& $corners['tr'] === $corners['br']
		&& $corners['br'] === $corners['bl'];
	$r = $all_same
		? $corners['tl'] . 'px'
		: [
			'topLeft'     => $corners['tl'] . 'px',
			'topRight'    => $corners['tr'] . 'px',
			'bottomRight' => $corners['br'] . 'px',
			'bottomLeft'  => $corners['bl'] . 'px',
		];
	return [
		'version' => 3,
		'styles'  => [
			'blocks' => [
				'core/image'     => [ 'border' => [ 'radius' => $r ] ],
				'core/button'    => [
					'border'     => [ 'radius' => $r ],
					'variations' => [ 'outline' => [ 'border' => [ 'radius' => $r ] ] ],
				],
				'core/buttons'   => [ 'border' => [ 'radius' => $r ] ],
				'core/cover'     => [ 'border' => [ 'radius' => $r ] ],
				'core/group'     => [ 'border' => [ 'radius' => $r ] ],
				'core/pullquote' => [ 'border' => [ 'radius' => $r ] ],
			],
		],
	];
}

// ----------------------------------------
// Helper: get the active preset's CSS
// ----------------------------------------
function skate_get_preset_css(): string {
	$key     = get_option( 'skate_active_preset', 'eckig' );
	$presets = skate_get_presets();
	return trim( $presets[ $key ]['css'] ?? '' );
}

// ----------------------------------------
// Helper: shadow enabled?
// ----------------------------------------
function skate_is_shadow_enabled(): bool {
	return get_option( 'skate_shadow_enabled', '' ) === '1';
}

function skate_is_secondary_disabled(): bool {
	return get_option( 'skate_secondary_disabled', '' ) === '1';
}

function skate_is_mark_disabled(): bool {
	return get_option( 'skate_mark_disabled', '' ) === '1';
}

// ----------------------------------------
// Color generation helpers (HSL → HEX + random pair)
// ----------------------------------------
function skate_hue_to_rgb( float $p, float $q, float $t ): float {
	if ( $t < 0 ) $t += 1;
	if ( $t > 1 ) $t -= 1;
	if ( $t < 1/6 ) return $p + ( $q - $p ) * 6 * $t;
	if ( $t < 1/2 ) return $q;
	if ( $t < 2/3 ) return $p + ( $q - $p ) * ( 2/3 - $t ) * 6;
	return $p;
}

function skate_hsl_to_hex( float $h, float $s, float $l ): string {
	$h /= 360; $s /= 100; $l /= 100;
	if ( $s == 0 ) {
		$v = (int) round( $l * 255 );
		return sprintf( '#%02x%02x%02x', $v, $v, $v );
	}
	$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
	$p = 2 * $l - $q;
	return sprintf( '#%02x%02x%02x',
		(int) round( skate_hue_to_rgb( $p, $q, $h + 1/3 ) * 255 ),
		(int) round( skate_hue_to_rgb( $p, $q, $h       ) * 255 ),
		(int) round( skate_hue_to_rgb( $p, $q, $h - 1/3 ) * 255 )
	);
}

function skate_random_color_pair(): array {
	$main_h = random_int( 0, 359 );
	$main_s = random_int( 30, 55 );
	$main_l = random_int( 14, 26 );
	$sec_h  = ( $main_h + random_int( 150, 210 ) ) % 360;
	$sec_s  = random_int( 65, 85 );
	$sec_l  = random_int( 52, 65 );
	$mid_l  = min( 42, $main_l + random_int( 10, 18 ) );
	$main   = skate_hsl_to_hex( $main_h, $main_s, $main_l );
	$sec    = skate_hsl_to_hex( $sec_h,  $sec_s,  $sec_l  );
	$mid    = skate_hsl_to_hex( $main_h, $main_s, $mid_l  );
	return [
		'main'      => $main,
		'secondary' => $sec,
		'gradient'  => [
			[ 'c' => strtoupper( $main ), 'p' => 0   ],
			[ 'c' => strtoupper( $mid  ), 'p' => 50  ],
			[ 'c' => strtoupper( $main ), 'p' => 100 ],
		],
	];
}

function skate_random_border_corners(): array {
	$pool = [
		[ 'tl' => 0,  'tr' => 0,  'br' => 0,  'bl' => 0  ],
		[ 'tl' => 10, 'tr' => 10, 'br' => 10, 'bl' => 10 ],
		[ 'tl' => 0,  'tr' => 20, 'br' => 0,  'bl' => 20 ],
		[ 'tl' => 0,  'tr' => 0,  'br' => 15, 'bl' => 15 ],
		[ 'tl' => 15, 'tr' => 0,  'br' => 0,  'bl' => 15 ],
		[ 'tl' => 0,  'tr' => 15, 'br' => 15, 'bl' => 0  ],
		[ 'tl' => 20, 'tr' => 0,  'br' => 20, 'bl' => 0  ],
	];
	return $pool[ random_int( 0, count( $pool ) - 1 ) ];
}

function skate_random_shadow_preset(): array {
	$enabled = array_values( array_filter(
		skate_get_shadow_presets(),
		fn( $s ) => $s['enabled']
	) );
	return $enabled[ random_int( 0, count( $enabled ) - 1 ) ];
}

function skate_get_shadow_presets(): array {
	return [
		[ 'slug' => 'none',     'name' => 'None',    'enabled' => false ],
		[ 'slug' => 'suave',    'name' => 'Soft',    'enabled' => true, 'x' => 0, 'y' => 3,  'blur' => 10, 'spread' => 0,  'color' => '#000000', 'alpha' => 8  ],
		[ 'slug' => 'clasico',  'name' => 'Classic', 'enabled' => true, 'x' => 0, 'y' => 4,  'blur' => 16, 'spread' => 0,  'color' => '#000000', 'alpha' => 12 ],
		[ 'slug' => 'profundo', 'name' => 'Deep',    'enabled' => true, 'x' => 0, 'y' => 8,  'blur' => 28, 'spread' => -2, 'color' => '#000000', 'alpha' => 20 ],
		[ 'slug' => 'nitido',   'name' => 'Sharp',   'enabled' => true, 'x' => 4, 'y' => 4,  'blur' => 0,  'spread' => 0,  'color' => '#000000', 'alpha' => 20 ],
	];
}

function skate_shadow_preset_is_active( array $sp ): bool {
	if ( ! $sp['enabled'] ) return ! skate_is_shadow_enabled();
	if ( ! skate_is_shadow_enabled() ) return false;
	if ( get_option( 'skate_shadow_mode', 'preset' ) !== 'custom' ) return false;
	return (int) get_option( 'skate_shadow_x',      SKATE_DEFAULT_SHADOW_X )      === $sp['x']
		&& (int) get_option( 'skate_shadow_y',      SKATE_DEFAULT_SHADOW_Y )      === $sp['y']
		&& (int) get_option( 'skate_shadow_blur',   SKATE_DEFAULT_SHADOW_BLUR )   === $sp['blur']
		&& (int) get_option( 'skate_shadow_spread', SKATE_DEFAULT_SHADOW_SPREAD ) === $sp['spread']
		&& strtolower( get_option( 'skate_shadow_color', SKATE_DEFAULT_SHADOW_COLOR ) ) === strtolower( $sp['color'] )
		&& (int) get_option( 'skate_shadow_alpha',  SKATE_DEFAULT_SHADOW_ALPHA )  === $sp['alpha'];
}

function skate_get_border_presets(): array {
	return [
		[ 'slug' => 'sharp',     'name' => 'Sharp',     'corners' => [ 'tl' => 0,  'tr' => 0,  'br' => 0,  'bl' => 0  ] ],
		[ 'slug' => 'rounded',   'name' => 'Rounded',   'corners' => [ 'tl' => 10, 'tr' => 10, 'br' => 10, 'bl' => 10 ] ],
		[ 'slug' => 'irregular', 'name' => 'Irregular', 'corners' => [ 'tl' => 0,  'tr' => 20, 'br' => 0,  'bl' => 20 ] ],
	];
}

function skate_get_luminance( string $hex ): float {
	$hex = ltrim( $hex, '#' );
	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
	$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
	$b = hexdec( substr( $hex, 4, 2 ) ) / 255;
	$to_lin = fn( float $c ): float => $c <= 0.04045 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
	return 0.2126 * $to_lin( $r ) + 0.7152 * $to_lin( $g ) + 0.0722 * $to_lin( $b );
}

function skate_contrast_text( string $bg_hex ): string {
	return skate_get_luminance( $bg_hex ) > 0.35
		? skate_get_active_color_main()
		: '#ffffff';
}

function skate_get_secondary_contrast_css(): string {
	$sec = skate_get_active_color_secondary();
	if ( skate_get_luminance( $sec ) <= 0.35 ) {
		return '';
	}
	$main = skate_get_active_color_main();
	return ".wp-block-button .wp-block-button__link.has-secondary-color-background-color,.p-button.p-component{color:{$main}!important}";
}

function skate_get_mono_presets(): array {
	return [
		[
			'slug' => 'ozean',    'name' => 'Ocean',
			'main' => '#1a3050', 'secondary' => '#3a6494',
			'gradient' => [ ['c'=>'#1A3050','p'=>0], ['c'=>'#2A4A7A','p'=>50], ['c'=>'#1A3050','p'=>100] ],
		],
		[
			'slug' => 'wald',     'name' => 'Forest',
			'main' => '#1b3a28', 'secondary' => '#2f6547',
			'gradient' => [ ['c'=>'#1B3A28','p'=>0], ['c'=>'#2A5438','p'=>50], ['c'=>'#1B3A28','p'=>100] ],
		],
		[
			'slug' => 'amethyst', 'name' => 'Amethyst',
			'main' => '#2e1a5e', 'secondary' => '#5c3aaa',
			'gradient' => [ ['c'=>'#2E1A5E','p'=>0], ['c'=>'#452880','p'=>50], ['c'=>'#2E1A5E','p'=>100] ],
		],
		[
			'slug' => 'schiefer', 'name' => 'Slate',
			'main' => '#2a3038', 'secondary' => '#4a5568',
			'gradient' => [ ['c'=>'#2A3038','p'=>0], ['c'=>'#3A4350','p'=>50], ['c'=>'#2A3038','p'=>100] ],
		],
		[
			'slug' => 'rubin',    'name' => 'Ruby',
			'main' => '#4a1020', 'secondary' => '#8b2a40',
			'gradient' => [ ['c'=>'#4A1020','p'=>0], ['c'=>'#6A1A30','p'=>50], ['c'=>'#4A1020','p'=>100] ],
		],
	];
}

function skate_get_color_presets(): array {
	return [
		[
			'slug' => 'klassik',  'name' => 'Classic',
			'main' => '#17263a', 'secondary' => '#d6b36d',
			'gradient' => [ ['c'=>'#17263A','p'=>0], ['c'=>'#2F4568','p'=>50], ['c'=>'#17263A','p'=>100] ],
		],
		[
			'slug' => 'koralle',  'name' => 'Coral',
			'main' => '#1a2e3a', 'secondary' => '#e07a5f',
			'gradient' => [ ['c'=>'#1a2e3a','p'=>0], ['c'=>'#2a4a5a','p'=>50], ['c'=>'#1a2e3a','p'=>100] ],
		],
		[
			'slug' => 'smaragd',  'name' => 'Emerald',
			'main' => '#1b4332', 'secondary' => '#e05c3a',
			'gradient' => [ ['c'=>'#1b4332','p'=>0], ['c'=>'#2d6a4f','p'=>50], ['c'=>'#1b4332','p'=>100] ],
		],
		[
			'slug' => 'violett',  'name' => 'Violet',
			'main' => '#2d1b69', 'secondary' => '#e879a0',
			'gradient' => [ ['c'=>'#2d1b69','p'=>0], ['c'=>'#4a2f9e','p'=>50], ['c'=>'#2d1b69','p'=>100] ],
		],
		[
			'slug' => 'graphit',  'name' => 'Graphite',
			'main' => '#2b2b2b', 'secondary' => '#4cc9f0',
			'gradient' => [ ['c'=>'#2b2b2b','p'=>0], ['c'=>'#444444','p'=>50], ['c'=>'#2b2b2b','p'=>100] ],
		],
	];
}

function skate_get_style_bundles(): array {
	$sh_soft    = [ 'enabled' => true,  'x' => 0, 'y' => 3, 'blur' => 10, 'spread' => 0,  'color' => '#000000', 'alpha' => 8  ];
	$sh_classic = [ 'enabled' => true,  'x' => 0, 'y' => 4, 'blur' => 16, 'spread' => 0,  'color' => '#000000', 'alpha' => 12 ];
	$sh_deep    = [ 'enabled' => true,  'x' => 0, 'y' => 8, 'blur' => 28, 'spread' => -2, 'color' => '#000000', 'alpha' => 20 ];
	$sh_sharp   = [ 'enabled' => true,  'x' => 4, 'y' => 4, 'blur' => 0,  'spread' => 0,  'color' => '#000000', 'alpha' => 20 ];
	return [
		[
			'slug'     => 'professional', 'name' => 'Professional', 'icon' => '💼',
			'main'     => '#17263a',      'secondary' => '#d6b36d',
			'gradient' => [ ['c'=>'#17263A','p'=>0], ['c'=>'#2F4568','p'=>50], ['c'=>'#17263A','p'=>100] ],
			'corners'  => [ 'tl' => 0,  'tr' => 0,  'br' => 0,  'bl' => 0  ],
			'shadow'   => $sh_soft,
		],
		[
			'slug'     => 'modern',       'name' => 'Modern',       'icon' => '⚡',
			'main'     => '#1a2e3a',      'secondary' => '#e07a5f',
			'gradient' => [ ['c'=>'#1A2E3A','p'=>0], ['c'=>'#2A4A5A','p'=>50], ['c'=>'#1A2E3A','p'=>100] ],
			'corners'  => [ 'tl' => 10, 'tr' => 10, 'br' => 10, 'bl' => 10 ],
			'shadow'   => $sh_classic,
		],
		[
			'slug'     => 'elegant',      'name' => 'Elegant',      'icon' => '💎',
			'main'     => '#2e1a5e',      'secondary' => '#e879a0',
			'gradient' => [ ['c'=>'#2E1A5E','p'=>0], ['c'=>'#45289E','p'=>50], ['c'=>'#2E1A5E','p'=>100] ],
			'corners'  => [ 'tl' => 0,  'tr' => 15, 'br' => 0,  'bl' => 15 ],
			'shadow'   => $sh_deep,
		],
		[
			'slug'     => 'urban',        'name' => 'Urban',        'icon' => '🏙️',
			'main'     => '#2b2b2b',      'secondary' => '#4cc9f0',
			'gradient' => [ ['c'=>'#2B2B2B','p'=>0], ['c'=>'#444444','p'=>50], ['c'=>'#2B2B2B','p'=>100] ],
			'corners'  => [ 'tl' => 0,  'tr' => 0,  'br' => 0,  'bl' => 0  ],
			'shadow'   => $sh_sharp,
		],
		[
			'slug'     => 'natural',      'name' => 'Natural',      'icon' => '🌿',
			'main'     => '#1b3a28',      'secondary' => '#7ec86e',
			'gradient' => [ ['c'=>'#1B3A28','p'=>0], ['c'=>'#2D6A4F','p'=>50], ['c'=>'#1B3A28','p'=>100] ],
			'corners'  => [ 'tl' => 15, 'tr' => 15, 'br' => 15, 'bl' => 15 ],
			'shadow'   => $sh_soft,
		],
	];
}

function skate_bundle_is_active( array $bundle ): bool {
	$cur_main    = strtolower( skate_get_active_color_main() );
	$cur_sec     = strtolower( skate_get_active_color_secondary() );
	$cur_corners = skate_get_active_radius_corners();
	return strtolower( $bundle['main'] )      === $cur_main
		&& strtolower( $bundle['secondary'] ) === $cur_sec
		&& $bundle['corners']                 === $cur_corners;
}

// ----------------------------------------
// 1. Merge theme.json overrides at runtime
//    (frontend + Gutenberg editor)
// ----------------------------------------
add_filter( 'wp_theme_json_data_theme', function ( WP_Theme_JSON_Data $theme_json ): WP_Theme_JSON_Data {
	// Apply border-radius to core blocks whenever any corner > 0
	$corners = skate_get_active_radius_corners();
	if ( max( $corners ) > 0 ) {
		$theme_json->update_with( skate_build_radius_overrides( $corners ) );
	}

	// Override palette colors (custom wins over preset; secondary can be removed entirely)
	if ( skate_has_custom_colors() || skate_is_secondary_disabled() ) {
		$palette = [
			[ 'color' => skate_get_active_color_main(), 'name' => 'Main Color', 'slug' => 'main-color' ],
		];
		if ( ! skate_is_secondary_disabled() ) {
			$palette[] = [ 'color' => skate_get_active_color_secondary(), 'name' => 'Secondary Color', 'slug' => 'secondary-color' ];
		}
		if ( get_option( 'skate_color_black', '' ) !== '' ) {
			$palette[] = [ 'color' => skate_get_active_color_black(), 'name' => 'Black', 'slug' => 'black' ];
		}
		if ( get_option( 'skate_color_light_gray', '' ) !== '' ) {
			$palette[] = [ 'color' => skate_get_active_color_light_gray(), 'name' => 'Light Gray', 'slug' => 'light-gray' ];
		}
		$theme_json->update_with( [
			'version'  => 3,
			'settings' => [ 'color' => [ 'palette' => $palette ] ],
		] );
	}

	// Override gradient
	if ( skate_has_custom_gradient() ) {
		$theme_json->update_with( [
			'version'  => 3,
			'settings' => [
				'color' => [
					'gradients' => [
						[
							'gradient' => skate_build_gradient_string( skate_get_active_gradient_data() ),
							'name'     => 'Main Gradient',
							'slug'     => 'main-gradient',
						],
					],
				],
			],
		] );
	}

	// Override button styles (Fill BG/text, Outline border/text)
	if ( skate_has_custom_button_styles() ) {
		$bw = skate_get_active_btn_outline_border_width() . 'px';
		$oc = skate_get_active_btn_outline_color();
		$theme_json->update_with( [
			'version' => 3,
			'styles'  => [
				'blocks' => [
					'core/button' => [
						'color'      => [
							'background' => skate_get_active_btn_fill_bg(),
							'text'       => skate_get_active_btn_fill_text(),
						],
						'variations' => [
							'outline' => [
								'color'  => [ 'text' => $oc ],
								'border' => [
									'top'    => [ 'color' => $oc, 'width' => $bw, 'style' => 'solid' ],
									'bottom' => [ 'color' => $oc, 'width' => $bw, 'style' => 'solid' ],
									'left'   => [ 'color' => $oc, 'width' => $bw, 'style' => 'solid' ],
									'right'  => [ 'color' => $oc, 'width' => $bw, 'style' => 'solid' ],
								],
							],
						],
					],
				],
			],
		] );
	}

	// Register shadow preset + apply to core blocks when shadow is enabled
	if ( skate_is_shadow_enabled() ) {
		$sh_mode = get_option( 'skate_shadow_mode', 'preset' );
		$sh_slug = get_option( 'skate_shadow_preset_slug', 'natural' );

		if ( $sh_mode === 'custom' ) {
			$theme_json->update_with( [
				'version'  => 3,
				'settings' => [
					'shadow' => [
						'defaultPresets' => false,
						'presets'        => [
							[
								'shadow' => skate_get_active_shadow(),
								'name'   => 'Main Shadow',
								'slug'   => 'main-shadow',
							],
						],
					],
				],
			] );
			$shadow_ref = 'var:preset|shadow|main-shadow';
		} else {
			// Keep WP default presets, reference the selected one directly
			$shadow_ref = "var:preset|shadow|{$sh_slug}";
		}

		$theme_json->update_with( [
			'version' => 3,
			'styles'  => [
				'blocks' => [
					'core/button' => [ 'shadow' => $shadow_ref ],
					'core/image'  => [ 'shadow' => $shadow_ref ],
				],
			],
		] );
	}

	return $theme_json;
} );

// ----------------------------------------
// 2. Inject CSS on the frontend
//    Always outputs CSS variables and .pace-radius / .pace-gradient utility classes,
//    then the preset-specific CSS.
// ----------------------------------------
add_action( 'wp_head', function () {
	$radius         = skate_get_active_radius();
	$gradient       = skate_build_gradient_string( skate_get_active_gradient_data() );
	$shadow         = skate_get_active_shadow();
	$shadow_enabled = skate_is_shadow_enabled();
	$spacer         = skate_get_active_spacer();
	echo '<style id="skate-preset-vars">' .
		':root{' .
			'--pace-radius:' . esc_attr( $radius ) . ';' .
			'--pace-gradient:' . esc_attr( $gradient ) . ';' .
			'--pace-spacer:' . esc_attr( $spacer ) . ';' .
			( $shadow_enabled ? '--pace-shadow:' . esc_attr( $shadow ) . ';' : '' ) .
		'}' .
		'.pace-radius{border-radius:var(--pace-radius);overflow:hidden}' .
		'.pace-gradient{background:var(--pace-gradient)}' .
		( $shadow_enabled
			? '.pace-shadow{box-shadow:var(--pace-shadow)}'
			  . '.wp-block-button__link,.p-button.p-component{box-shadow:var(--pace-shadow)}'
			: '' ) .
		( skate_is_mark_disabled()
			? 'mark,mark.has-secondary-color-color{background:transparent!important;color:var(--wp--preset--color--black)!important}'
			: '' ) .
		skate_get_secondary_contrast_css() .
	'</style>' . "\n";

	echo '<style id="skate-btn-vars">' .
		':root{' .
			'--skate-btn-fill-bg:'            . esc_attr( skate_get_active_btn_fill_bg() )            . ';' .
			'--skate-btn-fill-text:'          . esc_attr( skate_get_active_btn_fill_text() )          . ';' .
			'--skate-btn-fill-hover-bg:'      . esc_attr( skate_get_active_btn_fill_hover_bg() )      . ';' .
			'--skate-btn-fill-hover-text:'    . esc_attr( skate_get_active_btn_fill_hover_text() )    . ';' .
			'--skate-btn-outline-color:'      . esc_attr( skate_get_active_btn_outline_color() )      . ';' .
			'--skate-btn-outline-bw:'         . esc_attr( skate_get_active_btn_outline_border_width() ) . 'px;' .
			'--skate-btn-outline-hover-bg:'   . esc_attr( skate_get_active_btn_outline_hover_bg() )   . ';' .
			'--skate-btn-outline-hover-text:' . esc_attr( skate_get_active_btn_outline_hover_text() ) . ';' .
		'}' .
	'</style>' . "\n";
} );

// ----------------------------------------
// 2b. Button block CSS override — wp_footer guarantees it loads AFTER
//     WordPress global-styles-inline-css, so it wins even if user-level
//     Global Styles override the theme.json filter value.
// ----------------------------------------
add_action( 'wp_footer', function () {
	if ( ! skate_has_custom_button_styles() ) return;
	$fill_bg   = esc_attr( skate_get_active_btn_fill_bg() );
	$fill_text = esc_attr( skate_get_active_btn_fill_text() );
	$oc        = esc_attr( skate_get_active_btn_outline_color() );
	$bw        = esc_attr( skate_get_active_btn_outline_border_width() );
	echo '<style id="skate-btn-block-css">' .
		'.wp-block-button:not(.is-style-outline) .wp-block-button__link{' .
			'background-color:' . $fill_bg . ';' .
			'color:' . $fill_text . ';' .
		'}' .
		'.wp-block-button.is-style-outline .wp-block-button__link{' .
			'background-color:transparent;' .
			'color:' . $oc . ';' .
			'border-color:' . $oc . ';' .
			'border-width:' . $bw . 'px;' .
			'border-style:solid;' .
		'}' .
	'</style>' . "\n";
}, 20 );

// ----------------------------------------
// 3. Inject CSS inside the block editor
// ----------------------------------------
add_action( 'enqueue_block_editor_assets', function () {
	$radius         = skate_get_active_radius();
	$gradient       = skate_build_gradient_string( skate_get_active_gradient_data() );
	$shadow         = skate_get_active_shadow();
	$shadow_enabled = skate_is_shadow_enabled();
	$spacer         = skate_get_active_spacer();
	wp_add_inline_style(
		'wp-edit-blocks',
		':root{' .
			'--pace-radius:' . esc_attr( $radius ) . ';' .
			'--pace-gradient:' . esc_attr( $gradient ) . ';' .
			'--pace-spacer:' . esc_attr( $spacer ) . ';' .
			( $shadow_enabled ? '--pace-shadow:' . esc_attr( $shadow ) . ';' : '' ) .
		'}' .
		'.pace-radius{border-radius:var(--pace-radius);overflow:hidden}' .
		'.pace-gradient{background:var(--pace-gradient)}' .
		( $shadow_enabled ? '.pace-shadow{box-shadow:var(--pace-shadow)}' : '' ) .
		( skate_is_mark_disabled()
			? 'mark,mark.has-secondary-color-color{background:transparent!important;color:var(--wp--preset--color--black)!important}'
			: '' ) .
		skate_get_secondary_contrast_css() .
		':root{' .
			'--skate-btn-fill-bg:'            . esc_attr( skate_get_active_btn_fill_bg() )            . ';' .
			'--skate-btn-fill-text:'          . esc_attr( skate_get_active_btn_fill_text() )          . ';' .
			'--skate-btn-fill-hover-bg:'      . esc_attr( skate_get_active_btn_fill_hover_bg() )      . ';' .
			'--skate-btn-fill-hover-text:'    . esc_attr( skate_get_active_btn_fill_hover_text() )    . ';' .
			'--skate-btn-outline-color:'      . esc_attr( skate_get_active_btn_outline_color() )      . ';' .
			'--skate-btn-outline-bw:'         . esc_attr( skate_get_active_btn_outline_border_width() ) . 'px;' .
			'--skate-btn-outline-hover-bg:'   . esc_attr( skate_get_active_btn_outline_hover_bg() )   . ';' .
			'--skate-btn-outline-hover-text:' . esc_attr( skate_get_active_btn_outline_hover_text() ) . ';' .
		'}' .
		// Direct block selectors override Global Styles in the editor
		( skate_has_custom_button_styles()
			? '.wp-block-button:not(.is-style-outline) .wp-block-button__link{' .
				'background-color:' . esc_attr( skate_get_active_btn_fill_bg() ) . ';' .
				'color:' . esc_attr( skate_get_active_btn_fill_text() ) . ';' .
			'}' .
			'.wp-block-button.is-style-outline .wp-block-button__link{' .
				'background-color:transparent;' .
				'color:' . esc_attr( skate_get_active_btn_outline_color() ) . ';' .
				'border-color:' . esc_attr( skate_get_active_btn_outline_color() ) . ';' .
				'border-width:' . esc_attr( skate_get_active_btn_outline_border_width() ) . 'px;' .
				'border-style:solid;' .
			'}'
			: '' )
	);
} );

// ----------------------------------------
// Admin UI (only in admin area)
// ----------------------------------------
if ( ! is_admin() ) return;

add_action( 'admin_menu', function () {
	add_submenu_page(
		'skate',
		__( 'Skate – Design', 'skate' ),
		__( 'Design', 'skate' ),
		'edit_theme_options',
		'skate-design',
		'skate_render_design_page'
	);
} );

// ----------------------------------------
// Handle all preset POSTs early (admin_init) so we can redirect (PRG)
// ----------------------------------------
add_action( 'admin_init', function () {
	if ( ( $_GET['page'] ?? '' ) !== 'skate-design' ) return;
	if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
	if ( ! current_user_can( 'edit_theme_options' ) ) return;

	$redirect = admin_url( 'admin.php?page=skate-design&tab=presets&saved=1' );

	// Style bundle
	if ( isset( $_POST['skate_bundle_nonce'] ) && wp_verify_nonce( $_POST['skate_bundle_nonce'], 'skate_apply_bundle' ) ) {
		$slug       = sanitize_key( $_POST['skate_bundle_slug'] ?? '' );
		$bundle_map = array_column( skate_get_style_bundles(), null, 'slug' );
		if ( isset( $bundle_map[ $slug ] ) ) {
			$b = $bundle_map[ $slug ];
			$default_main = skate_theme_json_color( 'main-color' ) ?: SKATE_DEFAULT_COLOR_MAIN;
			strtolower( $b['main'] ) !== strtolower( $default_main )
				? update_option( 'skate_color_main', $b['main'] )
				: delete_option( 'skate_color_main' );
			$default_sec = skate_theme_json_color( 'secondary-color' ) ?: SKATE_DEFAULT_COLOR_SECONDARY;
			strtolower( $b['secondary'] ) !== strtolower( $default_sec )
				? update_option( 'skate_color_secondary', $b['secondary'] )
				: delete_option( 'skate_color_secondary' );
			update_option( 'skate_gradient_stops', wp_json_encode( $b['gradient'] ) );
			delete_option( 'skate_gradient_angle' );
			max( $b['corners'] ) > 0
				? update_option( 'skate_border_radius', wp_json_encode( $b['corners'] ) )
				: delete_option( 'skate_border_radius' );
			$sp = $b['shadow'];
			if ( ! $sp['enabled'] ) {
				delete_option( 'skate_shadow_enabled' );
			} else {
				update_option( 'skate_shadow_enabled', '1' );
				update_option( 'skate_shadow_mode',    'custom' );
				update_option( 'skate_shadow_x',       $sp['x'] );
				update_option( 'skate_shadow_y',       $sp['y'] );
				update_option( 'skate_shadow_blur',    $sp['blur'] );
				update_option( 'skate_shadow_spread',  $sp['spread'] );
				update_option( 'skate_shadow_color',   $sp['color'] );
				update_option( 'skate_shadow_alpha',   $sp['alpha'] );
			}
		}
		wp_safe_redirect( $redirect ); exit;
	}

	// Color preset
	if ( isset( $_POST['skate_presets_nonce'] ) && wp_verify_nonce( $_POST['skate_presets_nonce'], 'skate_apply_color_preset' ) ) {
		$slug       = sanitize_key( $_POST['skate_preset_slug'] ?? '' );
		$preset_map = array_column( skate_get_color_presets(), null, 'slug' );
		if ( isset( $preset_map[ $slug ] ) ) {
			$p = $preset_map[ $slug ];
			$default_main = skate_theme_json_color( 'main-color' ) ?: SKATE_DEFAULT_COLOR_MAIN;
			strtolower( $p['main'] ) !== strtolower( $default_main )
				? update_option( 'skate_color_main', $p['main'] )
				: delete_option( 'skate_color_main' );
			$default_sec = skate_theme_json_color( 'secondary-color' ) ?: SKATE_DEFAULT_COLOR_SECONDARY;
			strtolower( $p['secondary'] ) !== strtolower( $default_sec )
				? update_option( 'skate_color_secondary', $p['secondary'] )
				: delete_option( 'skate_color_secondary' );
			update_option( 'skate_gradient_stops', wp_json_encode( $p['gradient'] ) );
			delete_option( 'skate_gradient_angle' );
		}
		wp_safe_redirect( $redirect ); exit;
	}

	// Mono preset
	if ( isset( $_POST['skate_mono_nonce'] ) && wp_verify_nonce( $_POST['skate_mono_nonce'], 'skate_apply_mono_preset' ) ) {
		$slug     = sanitize_key( $_POST['skate_mono_slug'] ?? '' );
		$mono_map = array_column( skate_get_mono_presets(), null, 'slug' );
		if ( isset( $mono_map[ $slug ] ) ) {
			$p = $mono_map[ $slug ];
			$default_main = skate_theme_json_color( 'main-color' ) ?: SKATE_DEFAULT_COLOR_MAIN;
			strtolower( $p['main'] ) !== strtolower( $default_main )
				? update_option( 'skate_color_main', $p['main'] )
				: delete_option( 'skate_color_main' );
			$default_sec = skate_theme_json_color( 'secondary-color' ) ?: SKATE_DEFAULT_COLOR_SECONDARY;
			strtolower( $p['secondary'] ) !== strtolower( $default_sec )
				? update_option( 'skate_color_secondary', $p['secondary'] )
				: delete_option( 'skate_color_secondary' );
			update_option( 'skate_gradient_stops', wp_json_encode( $p['gradient'] ) );
			delete_option( 'skate_gradient_angle' );
		}
		wp_safe_redirect( $redirect ); exit;
	}

	// Border preset
	if ( isset( $_POST['skate_border_preset_nonce'] ) && wp_verify_nonce( $_POST['skate_border_preset_nonce'], 'skate_apply_border_preset' ) ) {
		$slug       = sanitize_key( $_POST['skate_border_preset_slug'] ?? '' );
		$border_map = array_column( skate_get_border_presets(), null, 'slug' );
		if ( isset( $border_map[ $slug ] ) ) {
			$corners = $border_map[ $slug ]['corners'];
			max( $corners ) > 0
				? update_option( 'skate_border_radius', wp_json_encode( $corners ) )
				: delete_option( 'skate_border_radius' );
		}
		wp_safe_redirect( $redirect ); exit;
	}

	// Shadow preset
	if ( isset( $_POST['skate_shadow_preset_nonce'] ) && wp_verify_nonce( $_POST['skate_shadow_preset_nonce'], 'skate_apply_shadow_preset' ) ) {
		$slug       = sanitize_key( $_POST['skate_shadow_preset_slug'] ?? '' );
		$shadow_map = array_column( skate_get_shadow_presets(), null, 'slug' );
		if ( isset( $shadow_map[ $slug ] ) ) {
			$sp = $shadow_map[ $slug ];
			if ( ! $sp['enabled'] ) {
				delete_option( 'skate_shadow_enabled' );
			} else {
				update_option( 'skate_shadow_enabled', '1' );
				update_option( 'skate_shadow_mode',    'custom' );
				update_option( 'skate_shadow_x',       $sp['x'] );
				update_option( 'skate_shadow_y',       $sp['y'] );
				update_option( 'skate_shadow_blur',    $sp['blur'] );
				update_option( 'skate_shadow_spread',  $sp['spread'] );
				update_option( 'skate_shadow_color',   $sp['color'] );
				update_option( 'skate_shadow_alpha',   $sp['alpha'] );
			}
		}
		wp_safe_redirect( $redirect ); exit;
	}

	// Randomize
	if ( isset( $_POST['skate_randomize_nonce'] ) && wp_verify_nonce( $_POST['skate_randomize_nonce'], 'skate_apply_randomize' ) ) {
		$pair         = skate_random_color_pair();
		$default_main = skate_theme_json_color( 'main-color' ) ?: SKATE_DEFAULT_COLOR_MAIN;
		strtolower( $pair['main'] ) !== strtolower( $default_main )
			? update_option( 'skate_color_main', $pair['main'] )
			: delete_option( 'skate_color_main' );
		$default_sec = skate_theme_json_color( 'secondary-color' ) ?: SKATE_DEFAULT_COLOR_SECONDARY;
		strtolower( $pair['secondary'] ) !== strtolower( $default_sec )
			? update_option( 'skate_color_secondary', $pair['secondary'] )
			: delete_option( 'skate_color_secondary' );
		update_option( 'skate_gradient_stops', wp_json_encode( $pair['gradient'] ) );
		delete_option( 'skate_gradient_angle' );
		$corners = skate_random_border_corners();
		max( $corners ) > 0
			? update_option( 'skate_border_radius', wp_json_encode( $corners ) )
			: delete_option( 'skate_border_radius' );
		$sp = skate_random_shadow_preset();
		update_option( 'skate_shadow_enabled', '1' );
		update_option( 'skate_shadow_mode',    'custom' );
		update_option( 'skate_shadow_x',       $sp['x'] );
		update_option( 'skate_shadow_y',       $sp['y'] );
		update_option( 'skate_shadow_blur',    $sp['blur'] );
		update_option( 'skate_shadow_spread',  $sp['spread'] );
		update_option( 'skate_shadow_color',   $sp['color'] );
		update_option( 'skate_shadow_alpha',   $sp['alpha'] );
		wp_safe_redirect( $redirect ); exit;
	}
} );

// ----------------------------------------
// Design page shell — tabs: Presets | Tuner
// ----------------------------------------
function skate_render_design_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) return;
	$valid_tabs = [ 'presets', 'tuner', 'buttons' ];
	$active_tab = in_array( $_GET['tab'] ?? '', $valid_tabs, true ) ? $_GET['tab'] : 'presets';
	?>
	<style>
	.skate-design-tabs {
		display: flex;
		gap: 0;
		border-bottom: 2px solid #dcdcde;
		margin: 0 0 20px;
	}
	.skate-design-tab {
		display: inline-flex;
		align-items: center;
		padding: 10px 20px;
		font-size: 14px;
		font-weight: 500;
		color: #50575e;
		text-decoration: none;
		border-bottom: 2px solid transparent;
		margin-bottom: -2px;
		transition: color .15s, border-color .15s;
	}
	.skate-design-tab:hover { color: #1d2327; }
	.skate-design-tab.is-active { color: var(--skate-accent); border-bottom-color: var(--skate-accent); font-weight: 600; }
	</style>
	<div class="wrap skate-design-wrap">
		<h1>Design</h1>
		<nav class="skate-design-tabs">
			<a href="<?= esc_url( admin_url( 'admin.php?page=skate-design&tab=presets' ) ) ?>"
			   class="skate-design-tab<?= $active_tab === 'presets' ? ' is-active' : '' ?>">Presets</a>
			<a href="<?= esc_url( admin_url( 'admin.php?page=skate-design&tab=tuner' ) ) ?>"
			   class="skate-design-tab<?= $active_tab === 'tuner' ? ' is-active' : '' ?>">Tuner</a>
			<a href="<?= esc_url( admin_url( 'admin.php?page=skate-design&tab=buttons' ) ) ?>"
			   class="skate-design-tab<?= $active_tab === 'buttons' ? ' is-active' : '' ?>">Buttons</a>
		</nav>
	<?php
	if ( $active_tab === 'presets' ) {
		skate_render_color_presets_page();
	} elseif ( $active_tab === 'buttons' ) {
		skate_render_buttons_tuner_page();
	} else {
		skate_render_master_tuner_page();
	}
	?>
	</div>
	<?php
}

// ----------------------------------------
// Presets (beta) page — color preset swatches
// ----------------------------------------
function skate_render_color_presets_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) return;

	$saved = ! empty( $_GET['saved'] );

	$cur_main = strtolower( skate_get_active_color_main() );
	$cur_sec  = strtolower( skate_get_active_color_secondary() );
	$cur_corners = skate_get_active_radius_corners();

	echo '<div class="skate-presets-wrap">';
	echo '<p class="description" style="color:#8c8f94;margin:16px 0 0">'
		. esc_html__( 'One-click combinations. Fine-tune individual values in Tuner.', 'skate' )
		. '</p>';

	if ( $saved ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Preset applied.', 'skate' ) . '</p></div>';
	}

	// ── Style Bundles ────────────────────────────────────────
	echo '<h2 class="skate-presets-section-title">' . esc_html__( 'Style Bundles', 'skate' ) . '</h2>';

	echo '<div class="skate-color-presets-page">';
	foreach ( skate_get_style_bundles() as $bundle ) {
		$active = skate_bundle_is_active( $bundle );

		echo '<form method="post" class="skate-preset-form">';
		wp_nonce_field( 'skate_apply_bundle', 'skate_bundle_nonce' );
		echo '<input type="hidden" name="skate_bundle_slug" value="' . esc_attr( $bundle['slug'] ) . '">';
		echo '<button type="submit" class="skate-color-preset-card' . ( $active ? ' is-active' : '' ) . '">';
		$c  = $bundle['corners'];
		$br = esc_attr( "{$c['tl']}px {$c['tr']}px {$c['br']}px {$c['bl']}px" );
		$bm = esc_attr( $bundle['main'] );
		$bs = esc_attr( $bundle['secondary'] );
		echo '<span class="skate-bundle-preview" style="border-radius:' . $br . '">'
			. '<span class="skate-bp-header" style="background:' . $bm . '"></span>'
			. '<span class="skate-bp-body">'
			. '<span class="skate-bp-btn" style="background:' . $bs . '; border-radius:' . $br . '"></span>'
			. '<span class="skate-bp-line"></span>'
			. '<span class="skate-bp-line skate-bp-line--short"></span>'
			. '</span>'
			. '</span>';
		echo '<span class="skate-preset-name">' . esc_html( $bundle['name'] ) . '</span>';
		echo '</button>';
		echo '</form>';
	}
	echo '</div>';

	// ── Randomizer ──────────────────────────────────────────
	echo '<h2 class="skate-presets-section-title">' . esc_html__( 'Randomizer', 'skate' ) . '</h2>';
	echo '<div class="skate-randomizer-row">';
	echo '<form method="post" class="skate-preset-form">';
	wp_nonce_field( 'skate_apply_randomize', 'skate_randomize_nonce' );
	echo '<button type="submit" class="skate-randomize-card">';
	echo '<span class="skate-randomize-icon">🎲</span>';
	echo '<span class="skate-preset-name">' . esc_html__( 'Randomize', 'skate' ) . '</span>';
	echo '</button>';
	echo '</form>';
	echo '</div>';

	echo '<h2 class="skate-presets-section-title">' . esc_html__( 'Color Presets', 'skate' ) . '</h2>';

	echo '<div class="skate-color-presets-page">';
	foreach ( skate_get_color_presets() as $preset ) {
		$active = strtolower( $preset['main'] ) === $cur_main
			&& strtolower( $preset['secondary'] ) === $cur_sec;

		echo '<form method="post" class="skate-preset-form">';
		wp_nonce_field( 'skate_apply_color_preset', 'skate_presets_nonce' );
		echo '<input type="hidden" name="skate_preset_slug" value="' . esc_attr( $preset['slug'] ) . '">';
		echo '<button type="submit" class="skate-color-preset-card' . ( $active ? ' is-active' : '' ) . '">';
		echo '<span class="skate-preset-swatch">'
			. '<span style="background:' . esc_attr( $preset['main'] ) . '"></span>'
			. '<span style="background:' . esc_attr( $preset['secondary'] ) . '"></span>'
			. '</span>';
		echo '<span class="skate-preset-name">' . esc_html( $preset['name'] ) . '</span>';
		echo '</button>';
		echo '</form>';
	}
	echo '</div>';

	// ── Monochromatic Presets ────────────────────────────────
	echo '<h2 class="skate-presets-section-title">' . esc_html__( 'Monochromatic', 'skate' ) . '</h2>';

	echo '<div class="skate-color-presets-page">';
	foreach ( skate_get_mono_presets() as $preset ) {
		$active = strtolower( $preset['main'] ) === $cur_main
			&& strtolower( $preset['secondary'] ) === $cur_sec;

		echo '<form method="post" class="skate-preset-form">';
		wp_nonce_field( 'skate_apply_mono_preset', 'skate_mono_nonce' );
		echo '<input type="hidden" name="skate_mono_slug" value="' . esc_attr( $preset['slug'] ) . '">';
		echo '<button type="submit" class="skate-color-preset-card' . ( $active ? ' is-active' : '' ) . '">';
		echo '<span class="skate-preset-swatch">'
			. '<span style="background:' . esc_attr( $preset['main'] ) . '"></span>'
			. '<span style="background:' . esc_attr( $preset['secondary'] ) . '"></span>'
			. '</span>';
		echo '<span class="skate-preset-name">' . esc_html( $preset['name'] ) . '</span>';
		echo '</button>';
		echo '</form>';
	}
	echo '</div>';

	// ── Border Presets ──────────────────────────────────────
	echo '<h2 class="skate-presets-section-title">' . esc_html__( 'Border Presets', 'skate' ) . '</h2>';

	echo '<div class="skate-border-presets-page">';
	foreach ( skate_get_border_presets() as $bp ) {
		$c = $bp['corners'];
		$active = $c === $cur_corners;
		$radius_css = $c['tl'] . 'px ' . $c['tr'] . 'px ' . $c['br'] . 'px ' . $c['bl'] . 'px';

		echo '<form method="post" class="skate-preset-form">';
		wp_nonce_field( 'skate_apply_border_preset', 'skate_border_preset_nonce' );
		echo '<input type="hidden" name="skate_border_preset_slug" value="' . esc_attr( $bp['slug'] ) . '">';
		echo '<button type="submit" class="skate-border-preset-card' . ( $active ? ' is-active' : '' ) . '">';
		echo '<span class="skate-border-swatch" style="border-radius:' . esc_attr( $radius_css ) . '"></span>';
		echo '<span class="skate-preset-name">' . esc_html( $bp['name'] ) . '</span>';
		echo '</button>';
		echo '</form>';
	}
	echo '</div>';

	// ── Shadow Presets ──────────────────────────────────────
	echo '<h2 class="skate-presets-section-title">' . esc_html__( 'Shadow Presets', 'skate' ) . '</h2>';

	echo '<div class="skate-shadow-presets-page">';
	foreach ( skate_get_shadow_presets() as $sp ) {
		$active = skate_shadow_preset_is_active( $sp );

		$shadow_css = $sp['enabled']
			? sprintf( '%dpx %dpx %dpx %dpx rgba(0,0,0,%s)',
				$sp['x'], $sp['y'], $sp['blur'], $sp['spread'],
				rtrim( rtrim( number_format( $sp['alpha'] / 100, 2 ), '0' ), '.' ) )
			: 'none';

		echo '<form method="post" class="skate-preset-form">';
		wp_nonce_field( 'skate_apply_shadow_preset', 'skate_shadow_preset_nonce' );
		echo '<input type="hidden" name="skate_shadow_preset_slug" value="' . esc_attr( $sp['slug'] ) . '">';
		echo '<button type="submit" class="skate-shadow-preset-card' . ( $active ? ' is-active' : '' ) . '">';
		echo '<span class="skate-shadow-swatch" style="box-shadow:' . esc_attr( $shadow_css ) . '"></span>';
		echo '<span class="skate-preset-name">' . esc_html( $sp['name'] ) . '</span>';
		echo '</button>';
		echo '</form>';
	}
	echo '</div>';

	echo '</div>';

	add_action( 'admin_print_footer_scripts', function () {
		?>
		<style>
			.skate-preset-form { margin: 0; padding: 0; }
			.skate-presets-section-title {
				font-size: 13px; font-weight: 700; text-transform: uppercase;
				letter-spacing: .08em; color: #1d2327; margin: 24px 0 12px;
			}

			/* Randomizer card */
			.skate-randomizer-row { display: flex; margin-top: 8px; }
			.skate-randomize-card {
				display: flex; flex-direction: column; align-items: center; gap: 8px;
				background: #fff; border: 2px solid #e2e4e7; border-radius: 12px;
				padding: 16px 14px 12px; cursor: pointer;
				transition: border-color .15s, box-shadow .15s;
			}
			.skate-randomize-card:hover { border-color: var(--skate-accent); box-shadow: 0 2px 8px rgba(0,0,0,.07); }
			.skate-randomize-icon { font-size: 32px; line-height: 1; display: block; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; }
			.skate-randomize-card .skate-preset-name { font-size: 12px; font-weight: 600; color: #1d2327; }

			/* Color preset cards */
			.skate-color-presets-page { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; }
			.skate-color-preset-card {
				display: flex; flex-direction: column; align-items: center; gap: 8px;
				background: #fff; border: 2px solid #e2e4e7; border-radius: 12px;
				padding: 16px 14px 12px; cursor: pointer;
				transition: border-color .15s, box-shadow .15s;
			}
			.skate-color-preset-card:hover { border-color: var(--skate-accent); box-shadow: 0 2px 8px rgba(0,0,0,.07); }
			.skate-color-preset-card.is-active {
				border-color: var(--skate-accent);
				box-shadow: 0 0 0 3px rgba(62,207,202,.25);
			}
			.skate-color-preset-card .skate-preset-swatch {
				width: 60px; height: 60px; border-radius: 10px; overflow: hidden;
				display: flex; border: 1px solid rgba(0,0,0,.08);
			}
			.skate-color-preset-card .skate-preset-swatch > span { flex: 1; height: 100%; display: block; }
			.skate-color-preset-card .skate-preset-name {
				font-size: 12px; font-weight: 600; color: #1d2327;
			}

			/* Border preset cards */
			.skate-border-presets-page { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; }
			.skate-border-preset-card {
				display: flex; flex-direction: column; align-items: center; gap: 8px;
				background: #fff; border: 2px solid #e2e4e7; border-radius: 12px;
				padding: 16px 14px 12px; cursor: pointer;
				transition: border-color .15s, box-shadow .15s;
			}
			.skate-border-preset-card:hover { border-color: var(--skate-accent); box-shadow: 0 2px 8px rgba(0,0,0,.07); }
			.skate-border-preset-card.is-active {
				border-color: var(--skate-accent);
				box-shadow: 0 0 0 3px rgba(62,207,202,.25);
			}
			.skate-border-swatch {
				width: 60px; height: 60px;
				background: #eef0f3;
				border: 2px solid #c5cad3;
				display: block;
			}
			.skate-border-preset-card .skate-preset-name {
				font-size: 12px; font-weight: 600; color: #1d2327;
			}

			/* Bundle mini-preview */
			.skate-bundle-preview {
				width: 64px; height: 52px;
				overflow: hidden;
				display: flex; flex-direction: column;
				border: 1px solid rgba(0,0,0,.10);
				flex-shrink: 0;
			}
			.skate-bp-header { height: 18px; flex-shrink: 0; }
			.skate-bp-body {
				flex: 1; background: #f0f2f4;
				padding: 5px 6px;
				display: flex; flex-direction: column; gap: 3px;
			}
			.skate-bp-btn { height: 7px; width: 65%; }
			.skate-bp-line { height: 3px; background: #d0d3d8; border-radius: 2px; }
			.skate-bp-line--short { width: 55%; }

			/* Shadow preset cards */
			.skate-shadow-presets-page { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; }
			.skate-shadow-preset-card {
				display: flex; flex-direction: column; align-items: center; gap: 10px;
				background: #fff; border: 2px solid #e2e4e7; border-radius: 12px;
				padding: 16px 14px 12px; cursor: pointer;
				transition: border-color .15s, box-shadow .15s;
			}
			.skate-shadow-preset-card:hover { border-color: var(--skate-accent); box-shadow: 0 2px 8px rgba(0,0,0,.07); }
			.skate-shadow-preset-card.is-active {
				border-color: var(--skate-accent);
				box-shadow: 0 0 0 3px rgba(62,207,202,.25);
			}
			.skate-shadow-swatch {
				width: 44px; height: 44px;
				background: #fff;
				border: 1px solid #e2e4e7;
				border-radius: 6px;
				display: block;
				margin: 8px;
			}
			.skate-shadow-preset-card .skate-preset-name {
				font-size: 12px; font-weight: 600; color: #1d2327;
			}


		</style>
		<?php
	} );
}

// ----------------------------------------
// Master Tuner page (was: skate_render_presets_page)
// ----------------------------------------
function skate_render_master_tuner_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) return;

	// Handle save
	$saved = false;
	if (
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset( $_POST['skate_preset_nonce'] ) &&
		wp_verify_nonce( $_POST['skate_preset_nonce'], 'skate_save_preset' )
	) {
		// Border-radius: 4 corners stored as JSON, delete if all zero
		$br = [
			'tl' => max( 0, (int) ( $_POST['skate_br_tl'] ?? 0 ) ),
			'tr' => max( 0, (int) ( $_POST['skate_br_tr'] ?? 0 ) ),
			'br' => max( 0, (int) ( $_POST['skate_br_br'] ?? 0 ) ),
			'bl' => max( 0, (int) ( $_POST['skate_br_bl'] ?? 0 ) ),
		];
		if ( max( $br ) > 0 ) {
			update_option( 'skate_border_radius', wp_json_encode( $br ) );
		} else {
			delete_option( 'skate_border_radius' );
		}

		// Colors: save if different from default, delete if matches default (= no override needed)
		// Compare against theme.json values (not hardcoded constants) so updates stay in sync.
		$color_main = sanitize_hex_color( $_POST['skate_color_main'] ?? '' );
		$default_main = skate_theme_json_color( 'main-color' ) ?: SKATE_DEFAULT_COLOR_MAIN;
		if ( $color_main && strtolower( $color_main ) !== strtolower( $default_main ) ) {
			update_option( 'skate_color_main', $color_main );
		} else {
			delete_option( 'skate_color_main' );
		}

		$color_secondary = sanitize_hex_color( $_POST['skate_color_secondary'] ?? '' );
		$default_secondary = skate_theme_json_color( 'secondary-color' ) ?: SKATE_DEFAULT_COLOR_SECONDARY;
		if ( $color_secondary && strtolower( $color_secondary ) !== strtolower( $default_secondary ) ) {
			update_option( 'skate_color_secondary', $color_secondary );
		} else {
			delete_option( 'skate_color_secondary' );
		}

		$color_black = sanitize_hex_color( $_POST['skate_color_black'] ?? '' );
		$default_black = skate_theme_json_color( 'black' ) ?: SKATE_DEFAULT_COLOR_BLACK;
		if ( $color_black && strtolower( $color_black ) !== strtolower( $default_black ) ) {
			update_option( 'skate_color_black', $color_black );
		} else {
			delete_option( 'skate_color_black' );
		}

		$color_light_gray = sanitize_hex_color( $_POST['skate_color_light_gray'] ?? '' );
		$default_light_gray = skate_theme_json_color( 'light-gray' ) ?: SKATE_DEFAULT_COLOR_LIGHT_GRAY;
		if ( $color_light_gray && strtolower( $color_light_gray ) !== strtolower( $default_light_gray ) ) {
			update_option( 'skate_color_light_gray', $color_light_gray );
		} else {
			delete_option( 'skate_color_light_gray' );
		}

		// Secondary color toggle: skate_secondary_show=1 means enabled, 0 means disabled
		if ( isset( $_POST['skate_secondary_show'] ) && $_POST['skate_secondary_show'] === '1' ) {
			delete_option( 'skate_secondary_disabled' );
		} else {
			update_option( 'skate_secondary_disabled', '1' );
		}

		// Mark headings toggle: skate_mark_show=1 means enabled, 0 means disabled
		if ( isset( $_POST['skate_mark_show'] ) && $_POST['skate_mark_show'] === '1' ) {
			delete_option( 'skate_mark_disabled' );
		} else {
			update_option( 'skate_mark_disabled', '1' );
		}

		// Gradient angle
		$angle_raw = $_POST['skate_gradient_angle'] ?? '';
		$angle_val = $angle_raw !== '' ? absint( $angle_raw ) : null;
		if ( $angle_val !== null && $angle_val !== SKATE_DEFAULT_GRADIENT_ANGLE ) {
			update_option( 'skate_gradient_angle', $angle_val );
		} else {
			delete_option( 'skate_gradient_angle' );
		}

		// Gradient stops: receive JSON from hidden input, sanitize each stop
		$stops_json_raw = stripslashes( $_POST['skate_gradient_stops_json'] ?? '' );
		$stops_decoded  = json_decode( $stops_json_raw, true );
		$stops_valid    = [];

		if ( is_array( $stops_decoded ) ) {
			foreach ( $stops_decoded as $stop ) {
				$c = sanitize_hex_color( $stop['c'] ?? '' );
				$p = isset( $stop['p'] ) ? max( 0, min( 100, (int) $stop['p'] ) ) : null;
				if ( $c && $p !== null ) {
					$stops_valid[] = [ 'c' => $c, 'p' => $p ];
				}
			}
		}

		// Compare to defaults; if identical, delete option (no override needed)
		$defaults = skate_default_gradient_stops();
		$is_default = count( $stops_valid ) === count( $defaults );
		if ( $is_default ) {
			foreach ( $stops_valid as $i => $stop ) {
				if ( strtolower( $stop['c'] ) !== strtolower( $defaults[ $i ]['c'] )
					|| $stop['p'] !== $defaults[ $i ]['p'] ) {
					$is_default = false;
					break;
				}
			}
		}

		if ( $stops_valid && ! $is_default ) {
			update_option( 'skate_gradient_stops', wp_json_encode( $stops_valid ) );
		} else {
			delete_option( 'skate_gradient_stops' );
		}

		// Clean up legacy flat options
		foreach ( [ 'skate_gradient_c1', 'skate_gradient_p1', 'skate_gradient_c2', 'skate_gradient_p2', 'skate_gradient_c3', 'skate_gradient_p3' ] as $old_key ) {
			delete_option( $old_key );
		}

		// Shadow enabled toggle
		if ( isset( $_POST['skate_shadow_enabled'] ) && $_POST['skate_shadow_enabled'] === '1' ) {
			update_option( 'skate_shadow_enabled', '1' );
		} else {
			delete_option( 'skate_shadow_enabled' );
		}

		// Shadow mode (preset | custom)
		$sh_mode = ( isset( $_POST['skate_shadow_mode'] ) && $_POST['skate_shadow_mode'] === 'custom' ) ? 'custom' : 'preset';
		if ( $sh_mode === 'custom' ) {
			update_option( 'skate_shadow_mode', 'custom' );
		} else {
			delete_option( 'skate_shadow_mode' );
		}

		// Shadow preset slug
		$sh_preset   = sanitize_key( $_POST['skate_shadow_preset_slug'] ?? '' );
		$valid_slugs = array_column( skate_get_wp_shadow_presets(), 'slug' );
		if ( in_array( $sh_preset, $valid_slugs, true ) && $sh_preset !== 'natural' ) {
			update_option( 'skate_shadow_preset_slug', $sh_preset );
		} else {
			delete_option( 'skate_shadow_preset_slug' );
		}

		// Shadow custom params
		$shadow_defaults = [
			'skate_shadow_x'      => SKATE_DEFAULT_SHADOW_X,
			'skate_shadow_y'      => SKATE_DEFAULT_SHADOW_Y,
			'skate_shadow_blur'   => SKATE_DEFAULT_SHADOW_BLUR,
			'skate_shadow_spread' => SKATE_DEFAULT_SHADOW_SPREAD,
			'skate_shadow_alpha'  => SKATE_DEFAULT_SHADOW_ALPHA,
		];
		foreach ( $shadow_defaults as $opt_key => $default ) {
			$raw = $_POST[ $opt_key ] ?? '';
			$val = $raw !== '' ? (int) $raw : null;
			if ( $val !== null && $val !== (int) $default ) {
				update_option( $opt_key, $val );
			} else {
				delete_option( $opt_key );
			}
		}
		$shadow_color = sanitize_hex_color( $_POST['skate_shadow_color'] ?? '' );
		if ( $shadow_color && strtolower( $shadow_color ) !== strtolower( SKATE_DEFAULT_SHADOW_COLOR ) ) {
			update_option( 'skate_shadow_color', $shadow_color );
		} else {
			delete_option( 'skate_shadow_color' );
		}

		// Spacer
		$spacer_size = sanitize_key( $_POST['skate_spacer_size'] ?? '' );
		$valid_sizes = array_keys( skate_get_spacer_sizes() );
		if ( in_array( $spacer_size, $valid_sizes, true ) && $spacer_size !== SKATE_DEFAULT_SPACER_SIZE ) {
			update_option( 'skate_spacer_size', $spacer_size );
		} else {
			delete_option( 'skate_spacer_size' );
		}
		delete_option( 'skate_spacer_mobile' );
		delete_option( 'skate_spacer_desktop' );

		$saved = true;
	}

	echo '<div class="skate-presets-wrap">';

	if ( $saved ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'skate' ) . '</p></div>';
	}

	echo '<form method="post" action="">';
	wp_nonce_field( 'skate_save_preset', 'skate_preset_nonce' );

	// Fine-tuning section
	$br_corners     = skate_get_active_radius_corners();
	$default_radius = 0;
	$br_locked      = $br_corners['tl'] === $br_corners['tr']
		&& $br_corners['tr'] === $br_corners['br']
		&& $br_corners['br'] === $br_corners['bl'];

	echo '<div class="skate-tune-section">';
	echo '<div class="skate-tune-head">';
	echo '<h3 class="skate-tune-title">' . esc_html__( 'Fine-tuning', 'skate' ) . '</h3>';
	echo '<p class="skate-tune-desc">' . esc_html__( 'Optional overrides for the active preset.', 'skate' ) . '</p>';
	echo '</div>';

	// Border radius — 4 corners with lock
	echo '<div class="skate-tune-row">';
	echo '<label class="skate-tune-label">' . esc_html__( 'Border-Radius', 'skate' ) . '</label>';
	echo '<div class="skate-tune-control">';
	echo '<div class="skate-br-corners" id="skate-br-corners">';
	foreach ( [
		[ 'key' => 'tl', 'label' => 'TL' ],
		[ 'key' => 'tr', 'label' => 'TR' ],
		[ 'key' => 'br', 'label' => 'BR' ],
		[ 'key' => 'bl', 'label' => 'BL' ],
	] as $corner ) {
		echo '<div class="skate-br-corner">';
		echo '<input type="number" class="skate-br-input" id="skate-br-' . esc_attr( $corner['key'] ) . '" name="skate_br_' . esc_attr( $corner['key'] ) . '"'
			. ' value="' . esc_attr( $br_corners[ $corner['key'] ] ) . '"'
			. ' min="0" max="100" placeholder="' . esc_attr( $default_radius ) . '">';
		echo '<span class="skate-tune-unit">px</span>';
		echo '<span class="skate-br-corner-label">' . esc_html( $corner['label'] ) . '</span>';
		echo '</div>';
	}
	echo '<button type="button" class="skate-br-lock' . ( $br_locked ? ' is-locked' : '' ) . '" id="skate-br-lock" title="Link corners">'
		. ( $br_locked ? '🔒' : '🔓' ) . '</button>';
	echo '</div>';
	echo '<span class="skate-var-hint"><code>--pace-radius</code> · class <code>.pace-radius</code></span>';
	echo '</div>';
	echo '</div>';

	// Main Color
	$cur_main = skate_get_active_color_main();

	echo '<div class="skate-tune-row">';
	echo '<label class="skate-tune-label">' . esc_html__( 'Main Color', 'skate' ) . '</label>';
	echo '<div class="skate-tune-control">';
	echo '<div class="skate-color-pair">';
	echo '<input type="color" name="skate_color_main" id="skate-color-main" value="' . esc_attr( $cur_main ) . '">';
	echo '<input type="text" class="skate-hex-input" data-color-id="skate-color-main" value="' . esc_attr( $cur_main ) . '" maxlength="7" placeholder="#rrggbb">';
	echo '</div>';
	echo '<span class="skate-tune-hint description"><code>--wp--preset--color--main-color</code></span>';
	echo '</div>';
	echo '</div>';

	// Secondary Color
	$cur_secondary = skate_get_active_color_secondary();
	$sec_disabled  = skate_is_secondary_disabled();

	echo '<div class="skate-tune-row">';

	echo '<div class="skate-tune-label skate-tune-label--secondary">';
	echo '<span>' . esc_html__( 'Secondary Color', 'skate' ) . '</span>';
	echo '<label class="skate-secondary-toggle-label" for="skate-secondary-toggle">';
	echo '<input type="hidden" name="skate_secondary_show" value="0">';
	echo '<input type="checkbox" id="skate-secondary-toggle" name="skate_secondary_show" value="1"' . ( ! $sec_disabled ? ' checked' : '' ) . '>';
	echo '<span class="skate-toggle-track"><span class="skate-toggle-thumb"></span></span>';
	echo '</label>';
	if ( $sec_disabled ) {
		echo '<span class="skate-tune-hint">' . esc_html__( 'Disabled', 'skate' ) . '</span>';
	}
	echo '</div>';

	echo '<div class="skate-tune-control">';
	echo '<div id="skate-secondary-color-fields" class="skate-color-fields' . ( $sec_disabled ? ' is-disabled' : '' ) . '">';
	echo '<div class="skate-color-pair">';
	echo '<input type="color" name="skate_color_secondary" id="skate-color-secondary" value="' . esc_attr( $cur_secondary ) . '">';
	echo '<input type="text" class="skate-hex-input" data-color-id="skate-color-secondary" value="' . esc_attr( $cur_secondary ) . '" maxlength="7" placeholder="#rrggbb">';
	echo '</div>';
	echo '</div>';
	echo '<span class="skate-tune-hint description"><code>--wp--preset--color--secondary-color</code></span>';
	echo '</div>';

	echo '</div>'; // .skate-tune-row

	// General text color (--wp--preset--color--black)
	$cur_black = skate_get_active_color_black();

	echo '<div class="skate-tune-row">';
	echo '<label class="skate-tune-label">' . esc_html__( 'Text Color', 'skate' ) . '</label>';
	echo '<div class="skate-tune-control">';
	echo '<div class="skate-color-pair">';
	echo '<input type="color" name="skate_color_black" id="skate-color-black" value="' . esc_attr( $cur_black ) . '">';
	echo '<input type="text" class="skate-hex-input" data-color-id="skate-color-black" value="' . esc_attr( $cur_black ) . '" maxlength="7" placeholder="#rrggbb">';
	echo '</div>';
	echo '<span class="skate-tune-hint description"><code>--wp--preset--color--black</code></span>';
	echo '</div>';
	echo '</div>'; // .skate-tune-row

	// Light Gray color (--wp--preset--color--light-gray)
	$cur_light_gray = skate_get_active_color_light_gray();

	echo '<div class="skate-tune-row">';
	echo '<label class="skate-tune-label">' . esc_html__( 'Light Gray', 'skate' ) . '</label>';
	echo '<div class="skate-tune-control">';
	echo '<div class="skate-color-pair">';
	echo '<input type="color" name="skate_color_light_gray" id="skate-color-light-gray" value="' . esc_attr( $cur_light_gray ) . '">';
	echo '<input type="text" class="skate-hex-input" data-color-id="skate-color-light-gray" value="' . esc_attr( $cur_light_gray ) . '" maxlength="7" placeholder="#rrggbb">';
	echo '</div>';
	echo '<span class="skate-tune-hint description"><code>--wp--preset--color--light-gray</code></span>';
	echo '</div>';
	echo '</div>'; // .skate-tune-row

	// Mark headings toggle
	$mark_disabled = skate_is_mark_disabled();

	echo '<div class="skate-tune-row">';
	echo '<div class="skate-tune-label skate-tune-label--mark">';
	echo '<span>' . esc_html__( 'Mark headings', 'skate' ) . '</span>';
	echo '<label class="skate-mark-toggle-label" for="skate-mark-toggle">';
	echo '<input type="hidden" name="skate_mark_show" value="0">';
	echo '<input type="checkbox" id="skate-mark-toggle" name="skate_mark_show" value="1"' . ( ! $mark_disabled ? ' checked' : '' ) . '>';
	echo '<span class="skate-toggle-track"><span class="skate-toggle-thumb"></span></span>';
	echo '</label>';
	echo '</div>';
	echo '<div class="skate-tune-control">';
	echo '<span class="skate-tune-hint">' . esc_html__( 'Two-tone headings via &lt;mark&gt;.', 'skate' ) . '</span>';
	echo '</div>';
	echo '</div>'; // .skate-tune-row

	// Spacer
	$sp_size = get_option( 'skate_spacer_size', SKATE_DEFAULT_SPACER_SIZE );

	echo '<div class="skate-tune-row">';
	echo '<label class="skate-tune-label">' . esc_html__( 'Spacer', 'skate' ) . '</label>';
	echo '<div class="skate-tune-control">';
	echo '<div class="skate-spacer-slider">';
	foreach ( [ 's' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL' ] as $val => $label ) {
		$checked = $sp_size === $val ? ' checked' : '';
		echo '<label class="skate-spacer-option">';
		echo '<input type="radio" name="skate_spacer_size" value="' . esc_attr( $val ) . '"' . $checked . '>';
		echo '<span>' . esc_html( $label ) . '</span>';
		echo '</label>';
	}
	echo '</div>';
	echo '<span class="skate-var-hint"><code>--pace-spacer</code></span>';
	echo '</div>';
	echo '</div>';

	// Shadow
	$sh_enabled = skate_is_shadow_enabled();
	$sh_mode    = get_option( 'skate_shadow_mode', 'preset' );
	$sh_preset  = get_option( 'skate_shadow_preset_slug', 'natural' );
	$sh_x      = (int) ( get_option( 'skate_shadow_x',      '' ) !== '' ? get_option( 'skate_shadow_x' )      : SKATE_DEFAULT_SHADOW_X );
	$sh_y      = (int) ( get_option( 'skate_shadow_y',      '' ) !== '' ? get_option( 'skate_shadow_y' )      : SKATE_DEFAULT_SHADOW_Y );
	$sh_blur   = (int) ( get_option( 'skate_shadow_blur',   '' ) !== '' ? get_option( 'skate_shadow_blur' )   : SKATE_DEFAULT_SHADOW_BLUR );
	$sh_spread = (int) ( get_option( 'skate_shadow_spread', '' ) !== '' ? get_option( 'skate_shadow_spread' ) : SKATE_DEFAULT_SHADOW_SPREAD );
	$sh_color  = get_option( 'skate_shadow_color', '' ) ?: SKATE_DEFAULT_SHADOW_COLOR;
	$sh_alpha  = (int) ( get_option( 'skate_shadow_alpha',  '' ) !== '' ? get_option( 'skate_shadow_alpha' )  : SKATE_DEFAULT_SHADOW_ALPHA );

	echo '<script>var skateShadowPresets=' . wp_json_encode( array_column( skate_get_wp_shadow_presets(), 'shadow', 'slug' ) ) . ';</script>';

	echo '<div class="skate-tune-row skate-tune-row--shadow">';

	// Left column: Shadow toggle + Custom toggle
	echo '<div class="skate-tune-label skate-tune-label--shadow">';
	echo '<div class="skate-shadow-label-row">';
	echo '<span class="skate-tune-hint">Shadow</span>';
	echo '<label class="skate-shadow-toggle-label" for="skate-shadow-toggle">';
	echo '<input type="hidden" name="skate_shadow_enabled" value="0">';
	echo '<input type="checkbox" id="skate-shadow-toggle" name="skate_shadow_enabled" value="1"' . ( $sh_enabled ? ' checked' : '' ) . '>';
	echo '<span class="skate-toggle-track"><span class="skate-toggle-thumb"></span></span>';
	echo '</label>';
	echo '</div>';
	echo '<div class="skate-shadow-label-row">';
	echo '<span class="skate-tune-hint">Custom</span>';
	echo '<label class="skate-shadow-toggle-label" for="skate-shadow-custom">';
	echo '<input type="checkbox" id="skate-shadow-custom" name="skate_shadow_mode" value="custom"' . ( $sh_mode === 'custom' ? ' checked' : '' ) . '>';
	echo '<span class="skate-toggle-track"><span class="skate-toggle-thumb"></span></span>';
	echo '</label>';
	echo '</div>';
	echo '</div>';

	// Right column
	echo '<div class="skate-tune-control skate-tune-control--shadow">';
	echo '<div id="skate-shadow-fields" class="skate-shadow-fields' . ( $sh_enabled ? '' : ' is-disabled' ) . '">';

	// Preset pills
	echo '<div class="skate-shadow-presets' . ( $sh_mode === 'custom' ? ' is-disabled' : '' ) . '" id="skate-shadow-presets">';
	foreach ( skate_get_wp_shadow_presets() as $preset ) {
		$checked = $sh_preset === $preset['slug'] ? ' checked' : '';
		echo '<label class="skate-shadow-preset-option">';
		echo '<input type="radio" name="skate_shadow_preset_slug" value="' . esc_attr( $preset['slug'] ) . '"' . $checked . '>';
		echo '<span>' . esc_html( $preset['name'] ) . '</span>';
		echo '</label>';
	}
	echo '</div>';

	// Custom inputs (hidden in preset mode)
	echo '<div id="skate-shadow-custom-fields" class="skate-shadow-custom-fields' . ( $sh_mode === 'custom' ? '' : ' is-hidden' ) . '">';
	echo '<div class="skate-shadow-inputs-row">';

	foreach ( [
		[ 'id' => 'skate-shadow-x',      'name' => 'skate_shadow_x',      'val' => $sh_x,      'label' => 'X',      'min' => -100, 'max' => 100 ],
		[ 'id' => 'skate-shadow-y',      'name' => 'skate_shadow_y',      'val' => $sh_y,      'label' => 'Y',      'min' => -100, 'max' => 100 ],
		[ 'id' => 'skate-shadow-blur',   'name' => 'skate_shadow_blur',   'val' => $sh_blur,   'label' => 'Blur',   'min' => 0,    'max' => 100 ],
		[ 'id' => 'skate-shadow-spread', 'name' => 'skate_shadow_spread', 'val' => $sh_spread, 'label' => 'Spread', 'min' => -50,  'max' => 100 ],
	] as $f ) {
		echo '<div class="skate-shadow-field">';
		echo '<span class="skate-tune-hint">' . esc_html( $f['label'] ) . '</span>';
		echo '<div class="skate-tune-input-wrap">';
		echo '<input type="number" id="' . esc_attr( $f['id'] ) . '" name="' . esc_attr( $f['name'] ) . '" value="' . esc_attr( $f['val'] ) . '" min="' . esc_attr( $f['min'] ) . '" max="' . esc_attr( $f['max'] ) . '" class="skate-shadow-input">';
		echo '<span class="skate-tune-unit">px</span>';
		echo '</div>';
		echo '</div>';
	}

	echo '<div class="skate-shadow-field">';
	echo '<span class="skate-tune-hint">' . esc_html__( 'Color', 'skate' ) . '</span>';
	echo '<div class="skate-color-pair">';
	echo '<input type="color" id="skate-shadow-color" name="skate_shadow_color" value="' . esc_attr( $sh_color ) . '" class="skate-shadow-input">';
	echo '<input type="text" class="skate-hex-input" data-color-id="skate-shadow-color" value="' . esc_attr( $sh_color ) . '" maxlength="7" placeholder="#rrggbb">';
	echo '</div>';
	echo '</div>';

	echo '<div class="skate-shadow-field">';
	echo '<span class="skate-tune-hint">' . esc_html__( 'Opacity', 'skate' ) . '</span>';
	echo '<div class="skate-tune-input-wrap">';
	echo '<input type="number" id="skate-shadow-alpha" name="skate_shadow_alpha" value="' . esc_attr( $sh_alpha ) . '" min="0" max="100" class="skate-shadow-input">';
	echo '<span class="skate-tune-unit">%</span>';
	echo '</div>';
	echo '</div>';

	echo '</div>'; // .skate-shadow-inputs-row
	echo '</div>'; // .skate-shadow-custom-fields

	echo '<div class="skate-shadow-preview-wrap">';
	echo '<div id="skate-shadow-preview" class="skate-shadow-preview"></div>';
	echo '</div>';

	echo '<span class="skate-var-hint"><code>--wp--preset--shadow--{slug}</code> · <code>--pace-shadow</code> · class <code>.pace-shadow</code></span>';
	echo '<span class="skate-tune-hint" style="margin-top:6px;display:block;">To exclude a block (e.g. an image) from the global shadow, add the CSS class <code>.pace-no-shadow</code> to it in the block\'s Advanced settings.</span>';

	echo '</div>'; // .skate-shadow-fields
	echo '</div>'; // .skate-tune-control--shadow
	echo '</div>'; // .skate-tune-row--shadow

	// Gradient
	$gd              = skate_get_active_gradient_data();
	$default_stops   = skate_default_gradient_stops();
	$default_stops_j = wp_json_encode( $default_stops );

	echo '<div class="skate-tune-row skate-tune-row--gradient">';
	echo '<label class="skate-tune-label">' . esc_html__( 'Main Gradient', 'skate' ) . '</label>';
	echo '<div class="skate-tune-control skate-tune-control--gradient">';

	// Angle
	echo '<div class="skate-gradient-angle">';
	echo '<span class="skate-tune-hint">' . esc_html__( 'Angle', 'skate' ) . '</span>';
	echo '<div class="skate-tune-input-wrap">';
	echo '<input type="number" name="skate_gradient_angle" id="skate-gradient-angle" value="' . esc_attr( $gd['angle'] ) . '" min="0" max="360" class="skate-angle-input">';
	echo '<span class="skate-tune-unit">°</span>';
	echo '</div>';
	echo '</div>';

	// Dynamic stops container
	echo '<div id="skate-gradient-stops">';
	foreach ( $gd['stops'] as $i => $stop ) {
		$idx = $i + 1;
		$c   = esc_attr( $stop['c'] );
		$p   = esc_attr( $stop['p'] );
		echo '<div class="skate-gradient-stop">';
		echo '<span class="skate-tune-hint">Stop ' . $idx . '</span>';
		echo '<div class="skate-color-pair">';
		echo '<input type="color" class="skate-stop-color" value="' . $c . '">';
		echo '<input type="text" class="skate-hex-input" data-color-id="" value="' . $c . '" maxlength="7" placeholder="#rrggbb">';
		echo '</div>';
		echo '<div class="skate-tune-input-wrap">';
		echo '<input type="number" class="skate-stop-pos" value="' . $p . '" min="0" max="100">';
		echo '<span class="skate-tune-unit">%</span>';
		echo '</div>';
		echo '<button type="button" class="skate-stop-remove button-link" aria-label="Remove stop">✕</button>';
		echo '</div>';
	}
	echo '</div>'; // #skate-gradient-stops

	// Hidden input for serialized stops
	echo '<input type="hidden" name="skate_gradient_stops_json" id="skate-gradient-stops-json" value="">';

	// Add stop button
	echo '<button type="button" class="button skate-add-stop" id="skate-add-stop">＋ Add stop</button>';

	// Preview
	echo '<div class="skate-gradient-preview-wrap">';
	echo '<div id="skate-gradient-preview" class="skate-gradient-preview"></div>';
	echo '</div>';

	echo '<span class="skate-var-hint"><code>--pace-gradient</code> · class <code>.pace-gradient</code></span>';

	echo '</div>'; // .skate-tune-control--gradient
	echo '</div>'; // .skate-tune-row--gradient

	echo '</div>'; // .skate-tune-section

	submit_button( __( 'Save preset', 'skate' ), 'primary', 'submit', true );

	echo '</form>';
	echo '</div>';

	skate_print_preset_assets();
}

function skate_print_preset_assets(): void {
	add_action( 'admin_print_footer_scripts', function () {
		?>
		<style>
			/* ── Wrap ── */
			.skate-presets-wrap { max-width: 980px; }
			.skate-presets-wrap > .description { margin-bottom: 20px; color: #8c8f94; }

			/* ── Preset grid ── */
			.skate-preset-grid {
				display: flex;
				gap: 12px;
				flex-wrap: wrap;
				margin-bottom: 28px;
			}
			.skate-preset-card {
				display: flex;
				flex-direction: column;
				align-items: center;
				gap: 6px;
				width: 148px;
				padding: 20px 14px 16px;
				border: 2px solid #e2e4e7;
				border-radius: 10px;
				cursor: pointer;
				background: #fff;
				transition: border-color .15s, box-shadow .15s;
				text-align: center;
			}
			.skate-preset-card:hover { border-color: var(--skate-accent); box-shadow: 0 2px 8px rgba(0,0,0,.07); }
			.skate-preset-card--active {
				border-color: var(--skate-accent);
				box-shadow: 0 0 0 3px rgba(62,207,202,.22);
			}
			.skate-preset-card input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
			.skate-preset-icon { font-size: 36px; line-height: 1; }
			.skate-preset-label { font-size: 13px; font-weight: 600; color: #1d2327; }
			.skate-preset-desc { font-size: 11px; color: #8c8f94; line-height: 1.4; }

			/* ── Fine-tuning section ── */
			.skate-tune-section {
				margin-bottom: 24px;
				background: #fff;
				border: 1px solid #dcdcdc;
				border-radius: 10px;
				max-width: 820px;
				overflow: hidden;
			}

			/* Section header */
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
			.skate-tune-desc {
				margin: 0;
				font-size: 12px;
				color: #8c8f94;
			}

			/* Rows: 2-column grid */
			.skate-tune-row {
				display: grid;
				grid-template-columns: 148px 1fr;
				gap: 8px 20px;
				align-items: center;
				padding: 13px 24px;
				border-top: 1px solid #f4f4f5;
			}
			.skate-tune-row--shadow,
			.skate-tune-row--gradient { align-items: start; padding-top: 16px; padding-bottom: 16px; }

			.skate-tune-label {
				font-size: 13px;
				font-weight: 500;
				color: #1d2327;
				padding-top: 2px;
			}
			.skate-tune-control {
				display: flex;
				align-items: center;
				gap: 10px;
				flex-wrap: wrap;
			}
			.skate-tune-input-wrap { display: flex; align-items: center; gap: 5px; }
			.skate-tune-input-wrap input[type=number] { width: 62px; }
			.skate-tune-unit { font-size: 12px; color: #a8aaac; }
			.skate-tune-hint { font-size: 12px; color: #a8aaac; line-height: 1.4; }
			.skate-tune-hint code {
				background: #f0f0f1;
				padding: 2px 5px;
				border-radius: 3px;
				font-size: 11px;
				color: #646970;
			}

			/* ── Color inputs ── */
			input[type=color] {
				width: 34px; height: 34px;
				padding: 2px;
				border: 1px solid #dcdcdc;
				border-radius: 6px;
				cursor: pointer;
				background: none;
				flex-shrink: 0;
			}
			.skate-color-pair { display: flex; align-items: center; gap: 6px; }
			.skate-hex-input {
				width: 80px;
				height: 32px;
				font-family: ui-monospace, monospace;
				font-size: 12px;
				padding: 0 8px;
				border: 1px solid #dcdcdc;
				border-radius: 5px;
				color: #1d2327;
				letter-spacing: .04em;
			}
			.skate-hex-input:focus {
				border-color: var(--skate-accent);
				outline: none;
				box-shadow: 0 0 0 2px rgba(62,207,202,.25);
			}

			/* ── Spacer segmented slider ── */
			.skate-spacer-slider { display: flex; background: #f0f0f1; border-radius: 20px; padding: 3px; gap: 0; width: 200px; position: relative; }
			.skate-spacer-option { flex: 1; display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer; z-index: 1; }
			.skate-spacer-option input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
			.skate-spacer-option span { display: block; padding: 4px 0; font-size: 12px; font-weight: 600; color: #8c8f94; text-align: center; width: 100%; border-radius: 16px; transition: color .15s; user-select: none; }
			.skate-spacer-option input:checked + span { background: var(--skate-accent); color: #fff; box-shadow: 0 1px 4px var(--skate-accent-glow); }

			/* ── Shadow builder ── */
			/* Border-radius 4-corner picker */
			.skate-br-corners {
				display: flex;
				align-items: flex-end;
				gap: 6px;
			}
			.skate-br-corner {
				display: flex;
				flex-direction: column;
				align-items: center;
				gap: 3px;
			}
			.skate-br-corner input[type=number] { width: 52px; }
			.skate-br-corner-label { font-size: 10px; font-weight: 600; color: #bbbcbd; text-transform: uppercase; }
			.skate-br-lock {
				background: none; border: 1px solid #e0e0e0; border-radius: 6px;
				cursor: pointer; font-size: 14px; line-height: 1;
				padding: 5px 7px; margin-bottom: 18px;
				transition: border-color .15s;
			}
			.skate-br-lock:hover, .skate-br-lock.is-locked { border-color: var(--skate-accent); }
			/* Shadow fields inline */
			.skate-tune-control--shadow { display: block; }
			.skate-shadow-fields { display: flex; flex-direction: column; gap: 0; }
			.skate-shadow-inputs-row {
				display: flex;
				flex-wrap: nowrap;
				gap: 10px 14px;
				align-items: flex-end;
			}
			.skate-shadow-field { display: flex; flex-direction: column; gap: 4px; }
			.skate-shadow-field > .skate-tune-hint {
				font-size: 10px;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: .06em;
				color: #bbbcbd;
			}
			.skate-shadow-field input[type=number] { width: 54px; }
			.skate-shadow-preview-wrap {
				display: flex;
				flex-direction: column;
				gap: 6px;
				align-items: flex-start;
				margin-top: 14px;
				margin-bottom: 2px;
				flex-basis: 100%;
			}
			.skate-shadow-preview {
				width: 100px;
				height: 60px;
				border-radius: 8px;
				background: #fff;
				border: 1px solid #f0f0f1;
			}
			/* ── Shadow preset pills (horizontal, same style as spacer) ── */
			.skate-shadow-presets { display: flex; background: #f0f0f1; border-radius: 20px; padding: 3px; gap: 0; margin-bottom: 10px; }
			.skate-shadow-preset-option { flex: 1; display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer; z-index: 1; }
			.skate-shadow-preset-option input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
			.skate-shadow-preset-option span {
				display: block; padding: 4px 6px; font-size: 12px; font-weight: 600;
				color: #8c8f94; text-align: center; width: 100%;
				border-radius: 16px; transition: color .15s; user-select: none; white-space: nowrap;
			}
			.skate-shadow-preset-option input:checked + span { background: var(--skate-accent); color: #fff; box-shadow: 0 1px 4px var(--skate-accent-glow); }
			.skate-shadow-presets.is-disabled { opacity: .4; pointer-events: none; }
			/* ── Shadow label rows ── */
			.skate-shadow-label-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%; }
			.is-hidden { display: none !important; }
			/* Shadow toggle */
			.skate-tune-row--shadow .skate-tune-label { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; }
			.skate-shadow-toggle-label { display: flex; align-items: center; cursor: pointer; }
			.skate-shadow-toggle-label input[type=checkbox] { position: absolute; opacity: 0; width: 0; height: 0; }
			.skate-toggle-track {
				display: inline-flex; align-items: center;
				width: 36px; height: 20px; border-radius: 10px;
				background: #ddd; transition: background .2s;
				padding: 2px;
			}
			.skate-shadow-toggle-label input:checked + .skate-toggle-track { background: var(--skate-accent); }
			.skate-toggle-thumb {
				width: 16px; height: 16px; border-radius: 50%;
				background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2);
				transition: transform .2s;
			}
			.skate-shadow-toggle-label input:checked + .skate-toggle-track .skate-toggle-thumb { transform: translateX(16px); }
			/* Disabled state for shadow fields */
			.skate-shadow-fields { transition: opacity .2s; }
			.skate-shadow-fields.is-disabled { opacity: 0.35; pointer-events: none; }

			/* Secondary color toggle */
			.skate-tune-label--secondary { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; }
			/* Mark headings toggle */
			.skate-tune-label--mark { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; }
			.skate-mark-toggle-label { display: flex; align-items: center; cursor: pointer; }
			.skate-mark-toggle-label input[type=checkbox] { position: absolute; opacity: 0; width: 0; height: 0; }
			.skate-mark-toggle-label input:checked + .skate-toggle-track { background: var(--skate-accent); }
			.skate-mark-toggle-label input:checked + .skate-toggle-track .skate-toggle-thumb { transform: translateX(16px); }

			/* ── Color presets ── */
			.skate-color-presets { display: flex; gap: 8px; flex-wrap: wrap; }
			.skate-color-preset {
				display: flex; flex-direction: column; align-items: center; gap: 5px;
				background: none; border: none; padding: 0; cursor: pointer;
			}
			.skate-preset-swatch {
				width: 40px; height: 40px; border-radius: 8px; overflow: hidden;
				display: flex; border: 2px solid #e2e4e7;
				transition: border-color .15s, box-shadow .15s;
			}
			.skate-preset-swatch > span { flex: 1; height: 100%; display: block; }
			.skate-color-preset:hover .skate-preset-swatch { border-color: var(--skate-accent); }
			.skate-color-preset.is-active .skate-preset-swatch {
				border-color: var(--skate-accent);
				box-shadow: 0 0 0 2px rgba(62,207,202,.35);
			}
			.skate-preset-name { font-size: 10px; font-weight: 600; color: #8c8f94; }
			.skate-color-preset.is-active .skate-preset-name { color: #1d2327; }

			.skate-secondary-toggle-label { display: flex; align-items: center; cursor: pointer; }
			.skate-secondary-toggle-label input[type=checkbox] { position: absolute; opacity: 0; width: 0; height: 0; }
			.skate-secondary-toggle-label input:checked + .skate-toggle-track { background: var(--skate-accent); }
			.skate-secondary-toggle-label input:checked + .skate-toggle-track .skate-toggle-thumb { transform: translateX(16px); }
			/* Disabled state for secondary color fields */
			.skate-color-fields { transition: opacity .2s; }
			.skate-color-fields.is-disabled { opacity: 0.35; pointer-events: none; }
			.skate-var-hint {
				display: block;
				font-size: 11px;
				color: #a8aaac;
				margin-top: 6px;
			}
			.skate-var-hint code {
				background: #f0f0f1;
				padding: 2px 5px;
				border-radius: 3px;
				font-size: 11px;
				color: #646970;
			}

			/* ── Gradient builder ── */
			.skate-tune-control--gradient {
				display: flex;
				flex-direction: column;
				gap: 10px;
				align-items: flex-start;
				width: 100%;
			}
			.skate-gradient-angle { display: flex; align-items: center; gap: 8px; }
			.skate-gradient-angle input[type=number] { width: 60px; }

			#skate-gradient-stops { display: flex; flex-direction: column; gap: 6px; width: 100%; max-width: 460px; }
			.skate-gradient-stop {
				display: flex;
				align-items: center;
				gap: 8px;
				background: #f8f9fa;
				border: 1px solid #eaecee;
				border-radius: 7px;
				padding: 7px 10px;
			}
			.skate-gradient-stop .skate-tune-hint {
				min-width: 42px;
				font-size: 10px;
				font-weight: 700;
				text-transform: uppercase;
				letter-spacing: .06em;
				color: #bbbcbd;
			}
			.skate-gradient-stop input[type=number] { width: 50px; }
			.skate-stop-remove {
				margin-left: auto;
				color: #d0d2d4;
				font-size: 12px;
				line-height: 1;
				padding: 3px 5px;
				border-radius: 4px;
				text-decoration: none;
				transition: color .1s, background .1s;
			}
			.skate-stop-remove:hover { color: #c0392b; background: #fdf2f2; }
			.skate-stop-remove:disabled { opacity: .3; cursor: default; pointer-events: none; }

			#skate-add-stop {
				font-size: 12px;
				padding: 5px 12px;
				height: auto;
				border-radius: 5px;
				color: #50575e;
				border-color: #c3c4c7;
			}
			#skate-add-stop:hover { border-color: var(--skate-accent); color: var(--skate-accent); }

			.skate-gradient-preview-wrap {
				width: 100%;
				max-width: 460px;
				display: flex;
				flex-direction: column;
				gap: 5px;
			}
			.skate-gradient-preview {
				width: 100%;
				height: 36px;
				border-radius: 6px;
				border: 1px solid #e2e4e7;
			}

		</style>
		<script>
		(function () {

			// ── Preset card selection ─────────────────────────────────────
			var radiusInput  = document.getElementById('skate-border-radius');
			var radiusHint   = document.querySelector('.skate-radius-hint');
			var hintTemplate = radiusHint ? radiusHint.innerHTML : '';

			document.querySelectorAll('.skate-preset-card').forEach(function (card) {
				card.addEventListener('click', function () {
					document.querySelectorAll('.skate-preset-card').forEach(function (c) {
						c.classList.remove('skate-preset-card--active');
					});
					card.classList.add('skate-preset-card--active');

					var radio = card.querySelector('input[type=radio]');
					if (radio && radiusInput) {
						var presetDefault = radio.getAttribute('data-radius') || '0';
						radiusInput.value = '';
						radiusInput.placeholder = presetDefault;
						if (radiusHint) {
							radiusHint.innerHTML = hintTemplate.replace(/\(\d+px\)/, '(' + presetDefault + 'px)');
						}
					}
				});
			});

			// ── Hex input ↔ color picker sync ────────────────────────────
			var HEX_RE = /^#[0-9a-fA-F]{6}$/;

			function initHexSync(colorInput, hexInput) {
				// color → hex
				colorInput.addEventListener('input', function () {
					hexInput.value = colorInput.value;
					updatePreview();
				});
				// hex → color
				hexInput.addEventListener('input', function () {
					var v = hexInput.value.trim();
					if (!v.startsWith('#')) v = '#' + v;
					if (HEX_RE.test(v)) {
						colorInput.value = v;
						hexInput.value = v;
						updatePreview();
					}
				});
			}

			// Main / Secondary color pickers
			document.querySelectorAll('.skate-hex-input[data-color-id]').forEach(function (hexInput) {
				var id = hexInput.getAttribute('data-color-id');
				if (!id) return; // stop-level hex inputs handled separately
				var colorInput = document.getElementById(id);
				if (colorInput) initHexSync(colorInput, hexInput);
			});

			// ── Gradient builder ──────────────────────────────────────────
			var stopsContainer = document.getElementById('skate-gradient-stops');
			var stopsJsonInput = document.getElementById('skate-gradient-stops-json');
			var addStopBtn     = document.getElementById('skate-add-stop');
			var preview        = document.getElementById('skate-gradient-preview');
			var angleInput     = document.getElementById('skate-gradient-angle');

			var MIN_STOPS = 2;
			var MAX_STOPS = 8;

			function getStops() {
				var rows = stopsContainer.querySelectorAll('.skate-gradient-stop');
				var stops = [];
				rows.forEach(function (row) {
					var c = row.querySelector('.skate-stop-color').value;
					var p = parseInt(row.querySelector('.skate-stop-pos').value, 10);
					stops.push({ c: c, p: isNaN(p) ? 0 : p });
				});
				return stops;
			}

			function buildGradientCSS() {
				var angle = parseInt(angleInput.value, 10) || 180;
				var stops = getStops();
				var stopStr = stops.map(function (s) { return s.c + ' ' + s.p + '%'; }).join(',');
				return 'linear-gradient(' + angle + 'deg,' + stopStr + ')';
			}

			function updatePreview() {
				if (preview) preview.style.background = buildGradientCSS();
			}

			function updateStopLabels() {
				stopsContainer.querySelectorAll('.skate-gradient-stop').forEach(function (row, i) {
					var label = row.querySelector('.skate-tune-hint');
					if (label) label.textContent = 'Stop ' + (i + 1);
				});
			}

			function updateRemoveButtons() {
				var rows = stopsContainer.querySelectorAll('.skate-gradient-stop');
				var tooFew = rows.length <= MIN_STOPS;
				rows.forEach(function (row) {
					var btn = row.querySelector('.skate-stop-remove');
					if (btn) btn.disabled = tooFew;
				});
				if (addStopBtn) addStopBtn.disabled = rows.length >= MAX_STOPS;
			}

			function initStopRow(row) {
				var colorInput = row.querySelector('.skate-stop-color');
				var hexInput   = row.querySelector('.skate-hex-input');
				var posInput   = row.querySelector('.skate-stop-pos');
				var removeBtn  = row.querySelector('.skate-stop-remove');

				if (colorInput && hexInput) {
					// color → hex
					colorInput.addEventListener('input', function () {
						hexInput.value = colorInput.value;
						updatePreview();
					});
					// hex → color
					hexInput.addEventListener('input', function () {
						var v = hexInput.value.trim();
						if (!v.startsWith('#')) v = '#' + v;
						if (HEX_RE.test(v)) {
							colorInput.value = v;
							hexInput.value = v;
						}
						updatePreview();
					});
				}

				if (posInput) {
					posInput.addEventListener('input', updatePreview);
				}

				if (removeBtn) {
					removeBtn.addEventListener('click', function () {
						row.remove();
						updateStopLabels();
						updateRemoveButtons();
						updatePreview();
					});
				}
			}

			// Init existing stop rows
			stopsContainer.querySelectorAll('.skate-gradient-stop').forEach(initStopRow);
			updateRemoveButtons();

			// Add stop button
			if (addStopBtn) {
				addStopBtn.addEventListener('click', function () {
					var rows = stopsContainer.querySelectorAll('.skate-gradient-stop');
					if (rows.length >= MAX_STOPS) return;

					var last = rows[rows.length - 1];
					var lastC = last ? last.querySelector('.skate-stop-color').value : '#2F4568';
					var lastP = last ? parseInt(last.querySelector('.skate-stop-pos').value, 10) : 50;
					var newP  = Math.min(100, lastP + 10);

					var row = document.createElement('div');
					row.className = 'skate-gradient-stop';
					row.innerHTML =
						'<span class="skate-tune-hint">Stop ' + (rows.length + 1) + '</span>' +
						'<div class="skate-color-pair">' +
							'<input type="color" class="skate-stop-color" value="' + lastC + '">' +
							'<input type="text" class="skate-hex-input" value="' + lastC + '" maxlength="7" placeholder="#rrggbb">' +
						'</div>' +
						'<div class="skate-tune-input-wrap">' +
							'<input type="number" class="skate-stop-pos" value="' + newP + '" min="0" max="100">' +
							'<span class="skate-tune-unit">%</span>' +
						'</div>' +
						'<button type="button" class="skate-stop-remove button-link" aria-label="Remove stop">✕</button>';

					stopsContainer.appendChild(row);
					initStopRow(row);
					updateStopLabels();
					updateRemoveButtons();
					updatePreview();
				});
			}

			// Angle input
			if (angleInput) {
				angleInput.addEventListener('input', updatePreview);
			}

			// Serialize stops to JSON before form submit
			var form = stopsContainer.closest('form');
			if (form) {
				form.addEventListener('submit', function () {
					if (stopsJsonInput) {
						stopsJsonInput.value = JSON.stringify(getStops());
					}
				});
			}

			// Initial preview
			updatePreview();

			// ── Border-radius lock ───────────────────────────────────
			(function () {
				var lockBtn  = document.getElementById('skate-br-lock');
				var inputs   = document.querySelectorAll('.skate-br-input');
				var locked   = lockBtn && lockBtn.classList.contains('is-locked');

				function setLocked(on) {
					locked = on;
					if (lockBtn) {
						lockBtn.textContent = on ? '🔒' : '🔓';
						lockBtn.classList.toggle('is-locked', on);
					}
					// If locking, sync all to first value
					if (on && inputs.length) {
						var v = inputs[0].value;
						inputs.forEach(function (i) { i.value = v; });
					}
				}

				if (lockBtn) {
					lockBtn.addEventListener('click', function () { setLocked(!locked); });
				}

				inputs.forEach(function (input) {
					input.addEventListener('input', function () {
						if (locked) {
							var v = this.value;
							inputs.forEach(function (i) { i.value = v; });
						}
					});
				});
			})();


			// ── Shadow toggle ────────────────────────────────────────
			(function () {
				var toggle = document.getElementById('skate-shadow-toggle');
				var fields = document.getElementById('skate-shadow-fields');
				if (toggle && fields) {
					toggle.addEventListener('change', function () {
						fields.classList.toggle('is-disabled', !this.checked);
					});
				}
			})();

			// ── Color preset picker ───────────────────────────────────
			(function () {
				var mainInput = document.getElementById('skate-color-main');
				var mainHex   = document.querySelector('.skate-hex-input[data-color-id="skate-color-main"]');
				var secInput  = document.getElementById('skate-color-secondary');
				var secHex    = document.querySelector('.skate-hex-input[data-color-id="skate-color-secondary"]');

				document.querySelectorAll('.skate-color-preset').forEach(function (btn) {
					btn.addEventListener('click', function () {
						var main = btn.getAttribute('data-main');
						var sec  = btn.getAttribute('data-secondary');

						// Colors
						if (mainInput) { mainInput.value = main; }
						if (mainHex)   { mainHex.value   = main; }
						if (secInput)  { secInput.value  = sec;  }
						if (secHex)    { secHex.value    = sec;  }

						// Gradient: update stop rows and fire input events to refresh preview
						var gradientJson = btn.getAttribute('data-gradient');
						if (gradientJson) {
							try {
								var stops    = JSON.parse(gradientJson);
								var stopRows = document.querySelectorAll('#skate-gradient-stops .skate-gradient-stop');
								stops.forEach(function (stop, i) {
									if (!stopRows[i]) return;
									var colorEl = stopRows[i].querySelector('.skate-stop-color');
									var hexEl   = stopRows[i].querySelector('.skate-hex-input');
									var posEl   = stopRows[i].querySelector('.skate-stop-pos');
									if (colorEl) { colorEl.value = stop.c; colorEl.dispatchEvent(new Event('input')); }
									if (hexEl)   { hexEl.value   = stop.c; }
									if (posEl)   { posEl.value   = stop.p; posEl.dispatchEvent(new Event('input')); }
								});
							} catch (e) {}
						}

						document.querySelectorAll('.skate-color-preset').forEach(function (b) {
							b.classList.remove('is-active');
						});
						btn.classList.add('is-active');
					});
				});
			})();

			// ── Secondary color toggle ────────────────────────────────
			(function () {
				var toggle = document.getElementById('skate-secondary-toggle');
				var fields = document.getElementById('skate-secondary-color-fields');
				if (toggle && fields) {
					toggle.addEventListener('change', function () {
						fields.classList.toggle('is-disabled', !this.checked);
					});
				}
			})();

			// ── Shadow preset / custom mode ──────────────────────────
			(function () {
				var customChk  = document.getElementById('skate-shadow-custom');
				var presetsEl  = document.getElementById('skate-shadow-presets');
				var customFlds = document.getElementById('skate-shadow-custom-fields');
				var previewEl  = document.getElementById('skate-shadow-preview');
				var presetVals = (typeof skateShadowPresets !== 'undefined') ? skateShadowPresets : {};

				function updatePresetPreview() {
					if (!previewEl) return;
					var checked = document.querySelector('input[name="skate_shadow_preset_slug"]:checked');
					var slug = checked ? checked.value : 'natural';
					previewEl.style.boxShadow = presetVals[slug] || '';
				}

				function syncMode() {
					var isCustom = customChk && customChk.checked;
					if (presetsEl)  presetsEl.classList.toggle('is-disabled', isCustom);
					if (customFlds) customFlds.classList.toggle('is-hidden', !isCustom);
					if (!isCustom) updatePresetPreview();
				}

				if (customChk) customChk.addEventListener('change', syncMode);

				document.querySelectorAll('input[name="skate_shadow_preset_slug"]').forEach(function (r) {
					r.addEventListener('change', updatePresetPreview);
				});

				syncMode();
			})();

			// ── Shadow builder (custom mode) ─────────────────────────
			(function () {
				var shadowPreview    = document.getElementById('skate-shadow-preview');
				var shadowColorInput = document.getElementById('skate-shadow-color');
				var shadowHexInput   = shadowColorInput
					? shadowColorInput.closest('.skate-color-pair').querySelector('.skate-hex-input')
					: null;

				function hexToRgba(hex, alpha) {
					var r = parseInt(hex.slice(1, 3), 16);
					var g = parseInt(hex.slice(3, 5), 16);
					var b = parseInt(hex.slice(5, 7), 16);
					return 'rgba(' + r + ',' + g + ',' + b + ',' + (Math.round(alpha) / 100) + ')';
				}

				function getVal(id, fallback) {
					var el = document.getElementById(id);
					return el ? (parseInt(el.value, 10) || fallback) : fallback;
				}

				function buildShadowCSS() {
					var x      = getVal('skate-shadow-x', 0);
					var y      = getVal('skate-shadow-y', 4);
					var blur   = getVal('skate-shadow-blur', 16);
					var spread = getVal('skate-shadow-spread', 0);
					var color  = shadowColorInput ? shadowColorInput.value : '#000000';
					var alpha  = getVal('skate-shadow-alpha', 12);
					return x + 'px ' + y + 'px ' + blur + 'px ' + spread + 'px ' + hexToRgba(color, alpha);
				}

				function updateShadowPreview() {
					var customChk = document.getElementById('skate-shadow-custom');
					if (!customChk || !customChk.checked) return;
					if (shadowPreview) shadowPreview.style.boxShadow = buildShadowCSS();
				}

				document.querySelectorAll('.skate-shadow-input').forEach(function (input) {
					input.addEventListener('input', updateShadowPreview);
				});

				updateShadowPreview();
			})();

		})();
		</script>
		<?php
	} );
}

// ----------------------------------------
// Buttons tuner page
// ----------------------------------------
function skate_render_buttons_tuner_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) return;

	$saved = false;
	if (
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset( $_POST['skate_buttons_nonce'] ) &&
		wp_verify_nonce( $_POST['skate_buttons_nonce'], 'skate_save_buttons' )
	) {
		if ( isset( $_POST['skate_btn_reset'] ) ) {
			foreach ( [
				'skate_btn_fill_bg', 'skate_btn_fill_text',
				'skate_btn_fill_hover_bg', 'skate_btn_fill_hover_text',
				'skate_btn_outline_color', 'skate_btn_outline_border_width',
				'skate_btn_outline_hover_bg', 'skate_btn_outline_hover_text',
			] as $key ) {
				delete_option( $key );
			}
			$saved = true;
		} else {
		$fields = [
			'skate_btn_fill_bg'            => skate_get_active_color_secondary(),
			'skate_btn_fill_text'          => skate_get_active_color_black(),
			'skate_btn_fill_hover_bg'      => skate_get_active_color_main(),
			'skate_btn_fill_hover_text'    => '#ffffff',
			'skate_btn_outline_color'      => skate_get_active_color_secondary(),
			'skate_btn_outline_hover_bg'   => skate_get_active_color_secondary(),
			'skate_btn_outline_hover_text' => '#ffffff',
		];
		foreach ( $fields as $key => $default ) {
			$val = sanitize_hex_color( $_POST[ $key ] ?? '' );
			if ( $val && strtolower( $val ) !== strtolower( $default ) ) {
				update_option( $key, $val );
			} else {
				delete_option( $key );
			}
		}
		// Border width: 1–4, default 2
		$bw = max( 1, min( 4, absint( $_POST['skate_btn_outline_border_width'] ?? 2 ) ) );
		if ( $bw !== 2 ) {
			update_option( 'skate_btn_outline_border_width', $bw );
		} else {
			delete_option( 'skate_btn_outline_border_width' );
		}
		$saved = true;
		} // end else (not reset)
	}

	// Current values for the form
	$fill_bg            = skate_get_active_btn_fill_bg();
	$fill_text          = skate_get_active_btn_fill_text();
	$fill_hover_bg      = skate_get_active_btn_fill_hover_bg();
	$fill_hover_text    = skate_get_active_btn_fill_hover_text();
	$outline_color      = skate_get_active_btn_outline_color();
	$outline_bw         = skate_get_active_btn_outline_border_width();
	$outline_hover_bg   = skate_get_active_btn_outline_hover_bg();
	$outline_hover_text = skate_get_active_btn_outline_hover_text();

	skate_print_preset_assets();

	add_action( 'admin_print_footer_scripts', function () {
		echo '<style>.skate-presets-wrap.skate-buttons-wrap { max-width: none; }</style>';
	}, 11 );

	echo '<div class="skate-presets-wrap skate-buttons-wrap">';

	if ( $saved ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'skate' ) . '</p></div>';
	}

	$preview_gradient  = esc_attr( skate_build_gradient_string( skate_get_active_gradient_data() ) );
	$preview_secondary = esc_attr( skate_get_active_color_secondary() );

	echo '<form method="post" action="">';
	wp_nonce_field( 'skate_save_buttons', 'skate_buttons_nonce' );
	?>
	<div class="skate-btn-layout">

		<!-- ── Fill column ── -->
		<div class="skate-tune-section skate-btn-col">
			<div class="skate-tune-head">
				<h3 class="skate-tune-title">Fill</h3>
			</div>

			<div class="skate-tune-row">
				<label class="skate-tune-label" for="skate-btn-fill-bg">Background</label>
				<div class="skate-tune-control">
					<div class="skate-color-pair">
						<input type="color" id="skate-btn-fill-bg" name="skate_btn_fill_bg" value="<?= esc_attr( $fill_bg ) ?>">
						<input type="text" class="skate-hex-input" data-btn-color="skate-btn-fill-bg" value="<?= esc_attr( $fill_bg ) ?>" maxlength="7" placeholder="#rrggbb">
					</div>
				</div>
			</div>

			<div class="skate-tune-row">
				<label class="skate-tune-label" for="skate-btn-fill-text">Text</label>
				<div class="skate-tune-control">
					<div class="skate-color-pair">
						<input type="color" id="skate-btn-fill-text" name="skate_btn_fill_text" value="<?= esc_attr( $fill_text ) ?>">
						<input type="text" class="skate-hex-input" data-btn-color="skate-btn-fill-text" value="<?= esc_attr( $fill_text ) ?>" maxlength="7" placeholder="#rrggbb">
					</div>
				</div>
			</div>

			<div class="skate-tune-row">
				<label class="skate-tune-label" for="skate-btn-fill-hover-bg">Hover BG</label>
				<div class="skate-tune-control">
					<div class="skate-color-pair">
						<input type="color" id="skate-btn-fill-hover-bg" name="skate_btn_fill_hover_bg" value="<?= esc_attr( $fill_hover_bg ) ?>">
						<input type="text" class="skate-hex-input" data-btn-color="skate-btn-fill-hover-bg" value="<?= esc_attr( $fill_hover_bg ) ?>" maxlength="7" placeholder="#rrggbb">
					</div>
				</div>
			</div>

			<div class="skate-tune-row">
				<label class="skate-tune-label" for="skate-btn-fill-hover-text">Hover Text</label>
				<div class="skate-tune-control">
					<div class="skate-color-pair">
						<input type="color" id="skate-btn-fill-hover-text" name="skate_btn_fill_hover_text" value="<?= esc_attr( $fill_hover_text ) ?>">
						<input type="text" class="skate-hex-input" data-btn-color="skate-btn-fill-hover-text" value="<?= esc_attr( $fill_hover_text ) ?>" maxlength="7" placeholder="#rrggbb">
					</div>
				</div>
			</div>
		</div>

		<!-- ── Outline column ── -->
		<div class="skate-tune-section skate-btn-col">
			<div class="skate-tune-head">
				<h3 class="skate-tune-title">Outline</h3>
			</div>

			<div class="skate-tune-row">
				<label class="skate-tune-label" for="skate-btn-outline-color">Border &amp; Text</label>
				<div class="skate-tune-control">
					<div class="skate-color-pair">
						<input type="color" id="skate-btn-outline-color" name="skate_btn_outline_color" value="<?= esc_attr( $outline_color ) ?>">
						<input type="text" class="skate-hex-input" data-btn-color="skate-btn-outline-color" value="<?= esc_attr( $outline_color ) ?>" maxlength="7" placeholder="#rrggbb">
					</div>
				</div>
			</div>

			<div class="skate-tune-row">
				<label class="skate-tune-label">Border Width</label>
				<div class="skate-tune-control">
					<div class="skate-spacer-slider skate-btn-bw-slider">
						<?php foreach ( [ 1, 2, 3, 4 ] as $bw_opt ) : ?>
						<label class="skate-spacer-option">
							<input type="radio" name="skate_btn_outline_border_width" value="<?= $bw_opt ?>"<?= checked( $outline_bw, $bw_opt, false ) ?>>
							<span><?= $bw_opt ?>px</span>
						</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="skate-tune-row">
				<label class="skate-tune-label" for="skate-btn-outline-hover-bg">Hover BG</label>
				<div class="skate-tune-control">
					<div class="skate-color-pair">
						<input type="color" id="skate-btn-outline-hover-bg" name="skate_btn_outline_hover_bg" value="<?= esc_attr( $outline_hover_bg ) ?>">
						<input type="text" class="skate-hex-input" data-btn-color="skate-btn-outline-hover-bg" value="<?= esc_attr( $outline_hover_bg ) ?>" maxlength="7" placeholder="#rrggbb">
					</div>
				</div>
			</div>

			<div class="skate-tune-row">
				<label class="skate-tune-label" for="skate-btn-outline-hover-text">Hover Text</label>
				<div class="skate-tune-control">
					<div class="skate-color-pair">
						<input type="color" id="skate-btn-outline-hover-text" name="skate_btn_outline_hover_text" value="<?= esc_attr( $outline_hover_text ) ?>">
						<input type="text" class="skate-hex-input" data-btn-color="skate-btn-outline-hover-text" value="<?= esc_attr( $outline_hover_text ) ?>" maxlength="7" placeholder="#rrggbb">
					</div>
				</div>
			</div>
		</div>

		<!-- ── Preview column ── -->
		<div class="skate-tune-section skate-btn-col skate-btn-col--preview">
			<div class="skate-tune-head">
				<h3 class="skate-tune-title">Preview</h3>
				<span class="skate-tune-hint">Hover to test</span>
			</div>

			<div class="skate-btn-stage" data-stage="white" style="background:#ffffff;">
				<span class="skate-btn-stage-label">White</span>
				<div class="skate-btn-stage-btns">
					<a class="skate-btn-preview skate-btn-preview--fill" href="#" onclick="return false">Button</a>
					<a class="skate-btn-preview skate-btn-preview--outline" href="#" onclick="return false">Button</a>
				</div>
			</div>

			<div class="skate-btn-stage" data-stage="primary" style="background:<?= $preview_gradient ?>;">
				<span class="skate-btn-stage-label" style="color:rgba(255,255,255,.7);">Gradient</span>
				<div class="skate-btn-stage-btns">
					<a class="skate-btn-preview skate-btn-preview--fill" href="#" onclick="return false">Button</a>
					<a class="skate-btn-preview skate-btn-preview--outline" href="#" onclick="return false">Button</a>
				</div>
			</div>
		</div>

	</div><!-- /.skate-btn-layout -->

	<div class="skate-btn-actions">
		<input type="submit" class="button-primary" value="<?= esc_attr__( 'Save', 'skate' ) ?>">
		<button type="submit" name="skate_btn_reset" value="1" class="button"
			onclick="return confirm('Reset all button styles to the current preset defaults?')">
			<?= esc_html__( 'Reset to preset', 'skate' ) ?>
		</button>
	</div>

	</form>
	</div><!-- /.skate-buttons-wrap -->

	<style>
	.skate-buttons-wrap .skate-tune-section { max-width: none; margin-bottom: 0; }
	.skate-btn-layout {
		display: flex;
		align-items: flex-start;
		gap: 20px;
	}
	.skate-btn-col {
		flex: 1;
		min-width: 0;
	}
	.skate-btn-col--preview {
		flex: 1.4;
	}
	.skate-btn-actions {
		display: flex;
		gap: 10px;
		align-items: center;
		margin-top: 24px;
	}
	/* Preview stages */
	.skate-btn-stage {
		border-radius: 6px;
		padding: 20px 16px 16px;
		margin-bottom: 8px;
		border: 1px solid rgba(0,0,0,.06);
	}
	.skate-btn-stage:last-child { margin-bottom: 0; }
	.skate-btn-stage[data-stage="white"] { padding-bottom: 0; border-bottom: none; border-radius: 6px 6px 0 0; }
	.skate-btn-stage[data-stage="primary"] { border-radius: 0 0 6px 6px; margin-top: 0; margin-bottom: 0; }
	.skate-btn-stage-label {
		display: block;
		font-size: 10px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: .08em;
		color: #a0a4a8;
		margin-bottom: 12px;
	}
	.skate-btn-stage-btns {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
		align-items: center;
	}
	.skate-btn-preview {
		display: inline-block;
		padding: 8px 20px;
		font-size: 13px;
		font-weight: 700;
		text-decoration: none;
		border-radius: var(--pace-radius, 0px);
		transition: background .15s, color .15s, border-color .15s;
		cursor: pointer;
		white-space: nowrap;
	}
	.skate-btn-bw-slider { width: auto; }
	</style>

	<script>
	(function () {
		var HEX_RE = /^#[0-9a-fA-F]{6}$/;

		// ── Hex ↔ color picker sync ───────────────────────────────────────────
		document.querySelectorAll('.skate-hex-input[data-btn-color]').forEach(function (hex) {
			var picker = document.getElementById(hex.getAttribute('data-btn-color'));
			if (!picker) return;
			picker.addEventListener('input', function () { hex.value = picker.value; updatePreview(); });
			hex.addEventListener('input', function () {
				var v = hex.value.trim();
				if (!v.startsWith('#')) v = '#' + v;
				if (HEX_RE.test(v)) { picker.value = v; hex.value = v; updatePreview(); }
			});
		});

		document.querySelectorAll('input[name="skate_btn_outline_border_width"]').forEach(function (r) {
			r.addEventListener('change', updatePreview);
		});

		// ── Helpers ───────────────────────────────────────────────────────────
		function val(id) { var el = document.getElementById(id); return el ? el.value : ''; }
		function bwVal() {
			var r = document.querySelector('input[name="skate_btn_outline_border_width"]:checked');
			return r ? r.value + 'px' : '2px';
		}

		// ── Apply styles to every preview instance ────────────────────────────
		function updatePreview() {
			var fillBg   = val('skate-btn-fill-bg');
			var fillText = val('skate-btn-fill-text');
			var oc       = val('skate-btn-outline-color');
			var bw       = bwVal();

			document.querySelectorAll('.skate-btn-preview--fill').forEach(function (btn) {
				btn.style.backgroundColor = fillBg;
				btn.style.color           = fillText;
				btn.style.border          = '2px solid ' + fillBg;
			});
			document.querySelectorAll('.skate-btn-preview--outline').forEach(function (btn) {
				btn.style.backgroundColor = 'transparent';
				btn.style.color           = oc;
				btn.style.border          = bw + ' solid ' + oc;
			});
		}

		// ── Hover simulation ──────────────────────────────────────────────────
		document.querySelectorAll('.skate-btn-preview--fill').forEach(function (btn) {
			var origBg, origColor, origBorder;
			btn.addEventListener('mouseenter', function () {
				origBg = btn.style.backgroundColor; origColor = btn.style.color; origBorder = btn.style.border;
				var hBg = val('skate-btn-fill-hover-bg');
				btn.style.backgroundColor = hBg;
				btn.style.color           = val('skate-btn-fill-hover-text');
				btn.style.border          = '2px solid ' + hBg;
			});
			btn.addEventListener('mouseleave', function () {
				btn.style.backgroundColor = origBg;
				btn.style.color           = origColor;
				btn.style.border          = origBorder;
			});
		});

		document.querySelectorAll('.skate-btn-preview--outline').forEach(function (btn) {
			var origColor, origBorder;
			btn.addEventListener('mouseenter', function () {
				origColor = btn.style.color; origBorder = btn.style.border;
				var hBg = val('skate-btn-outline-hover-bg');
				btn.style.backgroundColor = hBg;
				btn.style.color           = val('skate-btn-outline-hover-text');
				btn.style.border          = btn.style.borderWidth + ' solid ' + hBg;
			});
			btn.addEventListener('mouseleave', function () {
				btn.style.backgroundColor = 'transparent';
				btn.style.color           = origColor;
				btn.style.border          = origBorder;
			});
		});

		updatePreview();
	}());
	</script>
	<?php
}
