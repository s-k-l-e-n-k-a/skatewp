<?php
/**
 * Init Backend - Skate Theme
 *
 * This file centralizes all backend-related functions and modules
 * for the Skate theme (admin area only).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ----------------------------------------
// Load only in the admin area
// ----------------------------------------
if ( is_admin() ) {

    // Base path for backend includes
    $skate_backend_dir = get_template_directory() . '/inc/backend/';

    // Load Pages Map module (page management interface)
    if ( file_exists( $skate_backend_dir . 'pages-map.php' ) ) {
        require_once $skate_backend_dir . 'pages-map.php';
    }

    // Load custom taxonomies (backend logic for Pages only)
    if ( file_exists( $skate_backend_dir . 'taxonomies.php' ) ) {
        require_once $skate_backend_dir . 'taxonomies.php';
    }
}

// ----------------------------------------
// Load for both backend and frontend
// ----------------------------------------
$skate_backend_dir = get_template_directory() . '/inc/backend/';

// Load general backend utilities (shortcuts, styles, etc.)
if ( file_exists( $skate_backend_dir . 'general.php' ) ) {
    require_once $skate_backend_dir . 'general.php';
}

// Load theme presets (admin UI + runtime theme.json filter — needed on frontend too)
if ( file_exists( $skate_backend_dir . 'presets.php' ) ) {
    require_once $skate_backend_dir . 'presets.php';
}

// Load Core Settings (admin only — update key, etc.)
if ( is_admin() && file_exists( $skate_backend_dir . 'core-settings.php' ) ) {
    require_once $skate_backend_dir . 'core-settings.php';
}

// Load Site Identity (shortcodes needed on frontend too)
if ( file_exists( $skate_backend_dir . 'site-identity.php' ) ) {
    require_once $skate_backend_dir . 'site-identity.php';
}

// Load custom post types (needed on frontend too)
if ( file_exists( $skate_backend_dir . 'cpt-seo-pages.php' ) ) {
    require_once $skate_backend_dir . 'cpt-seo-pages.php';
}

// Load Navbar (shortcode needed on frontend too)
if ( file_exists( $skate_backend_dir . 'navbar.php' ) ) {
    require_once $skate_backend_dir . 'navbar.php';
}

// Load Menu Panels CPT (needed on frontend for do_blocks rendering)
if ( file_exists( $skate_backend_dir . 'menu-panels.php' ) ) {
    require_once $skate_backend_dir . 'menu-panels.php';
}
