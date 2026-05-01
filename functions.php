<?php

declare(strict_types=1);

/**
 * Enqueue main theme styles and scripts
 */
add_action('wp_enqueue_scripts', 'skate_enqueue_assets');

// Suppport for wide screen in Gutemberg
add_theme_support('align-wide');

// Custom Stlyes for editor
function skate_add_editor_styles() {
    add_editor_style(get_template_directory_uri() . '/assets/css/editor-style.css');
}
add_action('after_setup_theme', 'skate_add_editor_styles');

function skate_enqueue_assets(): void
{
    $theme_dir     = get_template_directory();
    $theme_uri     = get_template_directory_uri();
    $version       = wp_get_theme()->get('Version');

    // === CSS ===
    $main_css_path = $theme_dir . '/assets/css/main.css';
    $main_css_uri  = $theme_uri . '/assets/css/main.css';

    if (file_exists($main_css_path)) {
        wp_enqueue_style(
            'skate-main',
            $main_css_uri,
            [],
            filemtime($main_css_path)
        );
    } else {
        // Fallback: classic style.css
        wp_enqueue_style('skate-style', get_stylesheet_uri(), [], $version);
    }

    // === JS ===
    $scripts = [
        'flowchart'     => '/assets/js/flowchart.js',
        'reading-time'  => '/assets/js/reading-time.js',
        'smooth-scroll' => '/assets/js/smooth-scroll.js',
        'navbar'        => '/assets/js/navbar.js',
    ];

    foreach ($scripts as $handle => $relative_path) {
        $file_path = $theme_dir . $relative_path;
        if (file_exists($file_path)) {
            wp_enqueue_script(
                'skate-' . $handle,
                $theme_uri . $relative_path,
                [],
                filemtime($file_path),
                true // Load in footer
            );
        }
    }
}

/**
 * Backend initialization
 */

require_once get_template_directory() . '/inc/init-backend.php';


/**
 * Shortcode: Reading time for single seo_page
 */
function berechne_lesezeit_seo_page(): string
{
    if (!is_singular('seo_page')) {
        return '';
    }

    $post_id = get_the_ID();
    if (!$post_id) {
        return '';
    }

    $post_content = get_post_field('post_content', $post_id);

    // Clean and count words
    $text = wp_strip_all_tags(strip_shortcodes($post_content));

    $word_count = str_word_count(
        $text,
        0,
        'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'
    );

    $reading_time = (int) ceil($word_count / 200);

    return ($reading_time <= 1)
        ? __('less than 1 minute', 'skate')
        : sprintf(__('%d minutes', 'skate'), $reading_time);
}
add_shortcode('lesedauer', 'berechne_lesezeit_seo_page');

/**
 * Custom theme update check (remote metadata)
 * Protected by an update key — set skate_update_key in wp_options to unlock.
 */
define( 'SKATE_UPDATE_KEY', '1234' );

add_filter('pre_set_site_transient_update_themes', function ($transient) {
    // Require the correct update key to be set; otherwise hide available updates.
    if ( get_option( 'skate_update_key', '' ) !== SKATE_UPDATE_KEY ) return $transient;

    $remote = wp_remote_get('https://wordliner-wlac.s3.eu-central-1.amazonaws.com/skate/update-metadata.json');
    if (is_wp_error($remote)) return $transient;

    $remote_data = json_decode(wp_remote_retrieve_body($remote));
    if (
        !isset($remote_data->version) ||
        !isset($remote_data->download_url) ||
        !is_string($remote_data->version)
    ) return $transient;

    $theme = wp_get_theme( get_template() );
    $current_version = $theme->get('Version');

    if (is_string($current_version) && version_compare($remote_data->version, $current_version, '>')) {
        $transient->response['skate'] = [
            'theme'       => 'skate',
            'new_version' => $remote_data->version,
            'package'     => $remote_data->download_url,
        ];
    }

    return $transient;
});

/**
 * Block the actual theme download unless the update key is set.
 * This is the real gate — the transient filter above only hides the notification.
 * Auto-locks after a successful download (single-use).
 */
add_filter( 'upgrader_pre_download', function ( $reply, $package, $upgrader ) {
    if ( strpos( (string) $package, 'wordliner-wlac.s3' ) === false ) {
        return $reply; // not our theme package — don't interfere
    }

    if ( get_option( 'skate_update_key', '' ) !== SKATE_UPDATE_KEY ) {
        return new WP_Error(
            'skate_update_locked',
            sprintf(
                'Theme update locked. <a href="%s">Enter the update key</a> in Fine-tuning settings first.',
                esc_url( admin_url( 'admin.php?page=skate-presets' ) )
            )
        );
    }

    // Auto-lock after this download so the key is single-use
    delete_option( 'skate_update_key' );

    return $reply; // proceed normally
}, 10, 3 );

/**
 * Protect child theme during skate updates.
 *
 * Before: copy child theme files to wp-content/uploads/_skate_child_backup/
 * After:  if the child directory was deleted, restore it and reactivate.
 */
function skate_copy_dir( string $src, string $dst ): void {
    wp_mkdir_p( $dst );
    foreach ( scandir( $src ) as $item ) {
        if ( $item === '.' || $item === '..' ) continue;
        $s = $src . '/' . $item;
        $d = $dst . '/' . $item;
        is_dir( $s ) ? skate_copy_dir( $s, $d ) : copy( $s, $d );
    }
}

add_filter( 'upgrader_pre_install', function ( $return, $hook_extra ) {
    if ( ! isset( $hook_extra['theme'] ) || $hook_extra['theme'] !== 'skate' ) {
        return $return;
    }
    $stylesheet = get_option( 'stylesheet' );
    if ( $stylesheet === 'skate' ) return $return;

    $child_dir = get_theme_root() . '/' . $stylesheet;
    if ( ! is_dir( $child_dir ) ) return $return;

    $backup_dir = wp_upload_dir()['basedir'] . '/_skate_child_backup';
    update_option( '_skate_child_slug', $stylesheet );
    skate_copy_dir( $child_dir, $backup_dir );

    return $return;
}, 10, 2 );

add_action( 'upgrader_process_complete', function ( $upgrader, $hook_extra ) {
    if (
        ! isset( $hook_extra['type'], $hook_extra['themes'] ) ||
        $hook_extra['type'] !== 'theme' ||
        ! in_array( 'skate', (array) $hook_extra['themes'], true )
    ) {
        return;
    }

    $stylesheet = get_option( '_skate_child_slug' );
    delete_option( '_skate_child_slug' );
    if ( ! $stylesheet ) return;

    $child_dir  = get_theme_root() . '/' . $stylesheet;
    $backup_dir = wp_upload_dir()['basedir'] . '/_skate_child_backup';

    // Restore from backup if the child directory was deleted during the update.
    if ( ! is_dir( $child_dir ) && is_dir( $backup_dir ) ) {
        skate_copy_dir( $backup_dir, $child_dir );
    }

    // Reactivate child theme if it's on disk.
    if ( is_dir( $child_dir ) ) {
        switch_theme( $stylesheet );
    }

    // Remove backup.
    if ( is_dir( $backup_dir ) ) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $backup_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ( $files as $f ) {
            $f->isDir() ? rmdir( $f->getRealPath() ) : unlink( $f->getRealPath() );
        }
        rmdir( $backup_dir );
    }
}, 10, 2 );
