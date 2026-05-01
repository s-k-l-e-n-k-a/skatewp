<?php

/**
 * Custom Post Type: SEO Pages
 * - Root-level URLs (no prefix)
 * - Custom Taxonomies: SEO Categories, SEO Tags
 */

if (!defined('ABSPATH')) exit;

/**
 * === CPT: SEO Pages ===
 */
function skate_register_seo_pages_cpt()
{
    $labels = [
        'name' => __('SEO Pages', 'skate'),
        'singular_name' => __('SEO Page', 'skate'),
        'menu_name' => __('SEO Pages', 'skate'),
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-chart-area',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
        'has_archive' => false,
        'rewrite' => false, // root-level URLs
        'query_var' => true,
    ];

    register_post_type('seo_page', $args);
}

add_action('init', 'skate_register_seo_pages_cpt');

/**
 * === Root URL Handling ===
 */
function skate_seo_pages_post_type_link($post_link, $post)
{
    if ($post instanceof WP_Post && $post->post_type === 'seo_page') {
        return home_url(user_trailingslashit($post->post_name));
    }
    return $post_link;
}

add_filter('post_type_link', 'skate_seo_pages_post_type_link', 10, 2);

/**
 * === Build Rewrite Rules for SEO Pages ===
 */
function skate_seo_pages_build_rewrite_rules()
{
    $seo_pages = get_posts([
        'post_type' => 'seo_page',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
    ]);

    foreach ($seo_pages as $post_id) {
        $slug = get_post_field('post_name', $post_id);
        if (!$slug) continue;

        add_rewrite_rule(
            '^' . preg_quote($slug, '#') . '/?$',
            'index.php?post_type=seo_page&name=' . $slug,
            'top'
        );
    }
}

add_action('init', 'skate_seo_pages_build_rewrite_rules', 20);

/**
 * === Flush Rules When Slug Changes ===
 */
function skate_seo_pages_maybe_flush($post_id, $post, $update)
{
    if ($post->post_type !== 'seo_page') return;

    $old = get_post_meta($post_id, '_seo_page_last_slug', true);
    $new = $post->post_name;

    if ($old !== $new || (get_post_status($post_id) === 'publish' && !$update)) {
        update_post_meta($post_id, '_seo_page_last_slug', $new);
        flush_rewrite_rules(false);
    }
}

add_action('wp_insert_post', 'skate_seo_pages_maybe_flush', 10, 3);

/**
 * === Custom Taxonomy: SEO Categories ===
 */
function skate_register_seo_page_categories()
{
    $labels = [
        'name' => __('SEO Categories', 'skate'),
        'singular_name' => __('SEO Category', 'skate'),
        'menu_name' => __('SEO Categories', 'skate'),
    ];

    $args = [
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'rewrite' => ['slug' => 'seo-category'],
    ];

    register_taxonomy('seo_category', ['seo_page'], $args);
}

add_action('init', 'skate_register_seo_page_categories');

/**
 * === Custom Taxonomy: SEO Tags ===
 */
function skate_register_seo_page_tags()
{
    $labels = [
        'name' => __('SEO Tags', 'skate'),
        'singular_name' => __('SEO Tag', 'skate'),
        'menu_name' => __('SEO Tags', 'skate'),
    ];

    $args = [
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'update_count_callback' => '_update_post_term_count',
        'rewrite' => ['slug' => 'seo-tag'],
    ];

    register_taxonomy('seo_tag', ['seo_page'], $args);
}

add_action('init', 'skate_register_seo_page_tags');
