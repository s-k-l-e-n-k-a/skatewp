<?php

/**
 * Menu Panels — Skate
 *
 * Ensures the wp_pattern_category term "Menu Featured Column" exists
 * so patterns created in the Site Editor can be assigned to it and
 * appear in the navbar featured-column picker.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
	if ( ! term_exists( 'menu-featured-column', 'wp_pattern_category' ) ) {
		wp_insert_term( 'Menu Featured Column', 'wp_pattern_category', [
			'slug' => 'menu-featured-column',
		] );
	}
}, 20 );
