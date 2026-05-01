<?php

/**
 * Custom Taxonomies for Pages
 * - Adds default categories/tags to pages
 * - Registers "Zielgruppen" taxonomy
 */

if (!defined('ABSPATH')) exit;

/**
 * Add default Categories and Tags to Pages
 */
function skate_add_categories_to_pages()
{
    register_taxonomy_for_object_type('category', 'page');
    register_taxonomy_for_object_type('post_tag', 'page');
}

add_action('init', 'skate_add_categories_to_pages');

/**
 * Register custom taxonomy "Zielgruppen"
 */
function skate_create_zielgruppen_taxonomy()
{
    register_taxonomy(
        'zielgruppen',
        'page',
        [
            'labels' => [
                'name' => __('Target Audiences', 'skate'),
                'singular_name' => __('Target Audience', 'skate'),
            ],
            'hierarchical' => true, // Hierarchical like categories
            'rewrite' => ['slug' => 'zielgruppen'],
            'show_admin_column' => true,
            'show_in_rest' => true, // Important for Gutenberg support
        ]
    );
}

add_action('init', 'skate_create_zielgruppen_taxonomy');
