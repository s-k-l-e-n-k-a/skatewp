<?php

/**
 * Core Settings - Skate
 *
 * Admin page for site-level configuration: update key, etc.
 * Registered as a submenu under the main Skate menu, before Site Identity.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! is_admin() ) return;

// ----------------------------------------
// Submenu registration
// ----------------------------------------
add_action( 'admin_menu', function () {
	add_submenu_page(
		'skate',
		__( 'SkateWP – Settings', 'skate' ),
		__( 'Settings', 'skate' ),
		'manage_options',
		'skate-core-settings',
		'skate_render_core_settings'
	);
} );

// ----------------------------------------
// Export helper
// ----------------------------------------
function skate_get_export_data(): array {
	$keys = [
		'skate_color_main', 'skate_color_secondary', 'skate_color_black',
		'skate_gradient_angle', 'skate_gradient_stops', 'skate_border_radius',
		'skate_active_preset', 'skate_shadow_enabled', 'skate_shadow_mode',
		'skate_shadow_preset_slug', 'skate_shadow_x', 'skate_shadow_y',
		'skate_shadow_blur', 'skate_shadow_spread', 'skate_shadow_color',
		'skate_shadow_alpha', 'skate_spacer_size',
		'skate_secondary_disabled', 'skate_mark_disabled',
	];
	$data = [];
	foreach ( $keys as $key ) {
		$val = get_option( $key, null );
		if ( $val !== null && $val !== false && $val !== '' ) {
			$data[ $key ] = $val;
		}
	}
	return $data;
}

// ----------------------------------------
// Render function
// ----------------------------------------
function skate_render_core_settings(): void {
	if ( ! current_user_can( 'manage_options' ) ) return;

	// Import handler
	$imported     = false;
	$import_error = false;
	if (
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset( $_POST['skate_import_nonce'] ) &&
		wp_verify_nonce( $_POST['skate_import_nonce'], 'skate_import_config' )
	) {
		$raw  = sanitize_textarea_field( wp_unslash( $_POST['skate_import_json'] ?? '' ) );
		$data = json_decode( $raw, true );
		if ( is_array( $data ) ) {
			$allowed = [
				'skate_color_main', 'skate_color_secondary', 'skate_color_black',
				'skate_gradient_angle', 'skate_gradient_stops', 'skate_border_radius',
				'skate_active_preset', 'skate_shadow_enabled', 'skate_shadow_mode',
				'skate_shadow_preset_slug', 'skate_shadow_x', 'skate_shadow_y',
				'skate_shadow_blur', 'skate_shadow_spread', 'skate_shadow_color',
				'skate_shadow_alpha', 'skate_spacer_size',
				'skate_secondary_disabled', 'skate_mark_disabled',
			];
			foreach ( $allowed as $key ) {
				if ( array_key_exists( $key, $data ) ) {
					$val = $data[ $key ];
					( $val === '' || $val === null || $val === false )
						? delete_option( $key )
						: update_option( $key, sanitize_textarea_field( (string) $val ) );
				}
			}
			$imported = true;
		} else {
			$import_error = true;
		}
	}

	$saved = false;
	if (
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset( $_POST['skate_core_nonce'] ) &&
		wp_verify_nonce( $_POST['skate_core_nonce'], 'skate_save_core_settings' )
	) {
		// Update key: save if provided and matches, else delete (= locked)
		$update_key = sanitize_text_field( $_POST['skate_update_key'] ?? '' );
		if ( $update_key !== '' && $update_key === SKATE_UPDATE_KEY ) {
			update_option( 'skate_update_key', $update_key );
		} else {
			delete_option( 'skate_update_key' );
		}

		$saved = true;
	}

	$is_unlocked = get_option( 'skate_update_key', '' ) === SKATE_UPDATE_KEY;

	echo '<div class="wrap skate-core-wrap">';
	echo '<h1>' . esc_html__( 'SkateWP – Core Settings', 'skate' ) . '</h1>';

	if ( $saved ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'skate' ) . '</p></div>';
	}

	echo '<form method="post" action="">';
	wp_nonce_field( 'skate_save_core_settings', 'skate_core_nonce' );

	// ── Updates section ──
	echo '<div class="skate-tune-section">';
	echo '<div class="skate-tune-head">';
	echo '<h2 class="skate-tune-title">' . esc_html__( 'Updates', 'skate' ) . '</h2>';
	echo '<p class="skate-tune-desc">' . esc_html__( 'Enter the update key to allow the next theme update. It locks automatically after use.', 'skate' ) . '</p>';
	echo '</div>';

	echo '<div class="skate-tune-row">';
	echo '<label class="skate-tune-label" for="skate-update-key">' . esc_html__( 'Update key', 'skate' ) . '</label>';
	echo '<div class="skate-tune-control">';
	echo '<div class="skate-update-key-wrap">';
	echo '<input type="password" id="skate-update-key" name="skate_update_key" value="" placeholder="••••" autocomplete="off" class="regular-text">';
	if ( $is_unlocked ) {
		echo '<span class="skate-update-status skate-update-status--unlocked">&#x1F513; ' . esc_html__( 'Updates unlocked', 'skate' ) . '</span>';
	} else {
		echo '<span class="skate-update-status skate-update-status--locked">&#x1F512; ' . esc_html__( 'Updates locked', 'skate' ) . '</span>';
	}
	echo '</div>';
	echo '<span class="skate-tune-hint description">' . esc_html__( 'Leave blank to lock.', 'skate' ) . '</span>';
	echo '</div>';
	echo '</div>'; // .skate-tune-row
	echo '</div>'; // .skate-tune-section

	submit_button( __( 'Save', 'skate' ), 'primary', 'submit', true );

	echo '</form>';

	// ── Export / Import section ──
	$export_json = wp_json_encode( skate_get_export_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

	echo '<div class="skate-tune-section">';
	echo '<div class="skate-tune-head">';
	echo '<h2 class="skate-tune-title">' . esc_html__( 'Export / Import', 'skate' ) . '</h2>';
	echo '<p class="skate-tune-desc">' . esc_html__( 'Save or restore all SkateWP design settings.', 'skate' ) . '</p>';
	echo '</div>';

	// Export block
	echo '<div class="skate-ei-block">';
	echo '<div class="skate-ei-label">' . esc_html__( 'Export', 'skate' ) . '</div>';
	echo '<div class="skate-ei-content">';
	echo '<textarea id="skate-export-json" class="skate-ei-textarea" readonly>' . esc_textarea( $export_json ) . '</textarea>';
	echo '<div class="skate-ei-actions">';
	echo '<button type="button" class="button" id="skate-copy-btn">' . esc_html__( 'Copy JSON', 'skate' ) . '</button>';
	echo '<button type="button" class="button" id="skate-download-btn">' . esc_html__( 'Download .json', 'skate' ) . '</button>';
	echo '</div>';
	echo '</div>';
	echo '</div>';

	// Import block
	if ( $imported ) {
		echo '<div class="notice notice-success inline skate-ei-notice"><p>' . esc_html__( 'Config imported successfully.', 'skate' ) . '</p></div>';
	}
	if ( $import_error ) {
		echo '<div class="notice notice-error inline skate-ei-notice"><p>' . esc_html__( 'Invalid JSON — could not import.', 'skate' ) . '</p></div>';
	}

	echo '<form method="post" action="" class="skate-ei-block">';
	wp_nonce_field( 'skate_import_config', 'skate_import_nonce' );
	echo '<div class="skate-ei-label">' . esc_html__( 'Import', 'skate' ) . '</div>';
	echo '<div class="skate-ei-content">';
	echo '<textarea name="skate_import_json" class="skate-ei-textarea" placeholder="' . esc_attr__( 'Paste config JSON here…', 'skate' ) . '"></textarea>';
	echo '<div class="skate-ei-actions">';
	submit_button( __( 'Apply Config', 'skate' ), 'primary', 'submit', false );
	echo '</div>';
	echo '</div>';
	echo '</form>';

	echo '</div>'; // .skate-tune-section

	echo '</div>'; // .wrap

	skate_print_core_settings_assets();
}

function skate_print_core_settings_assets(): void {
	add_action( 'admin_print_footer_scripts', function () {
		?>
		<style>
			.skate-core-wrap { max-width: 820px; }
			.skate-core-wrap > h1 { margin-bottom: 16px; }

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
			.skate-tune-desc {
				margin: 0;
				font-size: 12px;
				color: #8c8f94;
			}
			.skate-tune-row {
				display: grid;
				grid-template-columns: 148px 1fr;
				gap: 8px 20px;
				align-items: center;
				padding: 13px 24px;
				border-top: 1px solid #f4f4f5;
			}
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
			.skate-tune-hint { font-size: 12px; color: #a8aaac; line-height: 1.4; }
			.skate-update-key-wrap { display: flex; align-items: center; gap: 10px; }
			.skate-update-status { font-size: 12px; font-weight: 500; }
			.skate-update-status--unlocked { color: #00a32a; }
			.skate-update-status--locked   { color: #8c8f94; }

			/* Export / Import */
			.skate-ei-block {
				display: grid;
				grid-template-columns: 148px 1fr;
				gap: 8px 20px;
				padding: 16px 24px;
				border-top: 1px solid #f4f4f5;
				margin: 0;
			}
			.skate-ei-label {
				font-size: 13px;
				font-weight: 500;
				color: #1d2327;
				padding-top: 6px;
			}
			.skate-ei-content { display: flex; flex-direction: column; gap: 8px; }
			.skate-ei-textarea {
				width: 100%;
				height: 96px;
				font-family: monospace;
				font-size: 11px;
				line-height: 1.5;
				color: #3c434a;
				background: #f6f7f7;
				border: 1px solid #dcdcdc;
				border-radius: 4px;
				resize: vertical;
				box-sizing: border-box;
				padding: 8px;
			}
			.skate-ei-actions { display: flex; gap: 8px; align-items: center; }
			.skate-ei-notice { margin: 4px 24px 0 !important; }
		</style>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var copyBtn = document.getElementById('skate-copy-btn');
			var dlBtn   = document.getElementById('skate-download-btn');
			if ( copyBtn ) {
				copyBtn.addEventListener('click', function() {
					var json = document.getElementById('skate-export-json').value;
					navigator.clipboard.writeText(json).then(function() {
						copyBtn.textContent = 'Copied!';
						setTimeout(function() { copyBtn.textContent = 'Copy JSON'; }, 2000);
					});
				});
			}
			if ( dlBtn ) {
				dlBtn.addEventListener('click', function() {
					var json = document.getElementById('skate-export-json').value;
					var blob = new Blob([json], { type: 'application/json' });
					var url  = URL.createObjectURL(blob);
					var a    = document.createElement('a');
					a.href = url; a.download = 'skate-config.json'; a.click();
					URL.revokeObjectURL(url);
				});
			}
		});
		</script>
		<?php
	} );
}
