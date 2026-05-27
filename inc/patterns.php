<?php

declare(strict_types=1);

/*
 * Pattern workflow:
 * 1. Design in GreenLight VS (VS Code) → Pro push to WP as GreenShift blocks
 * 2. Review in WP block editor → select all → copy block markup
 * 3. Create /patterns/<name>.php using _template.php as the header
 * 4. WP auto-discovers on next load — no manual registration needed
 *
 * Slugs:      skate/<kebab-case>
 * Categories: skate-heroes | skate-sections | skate-cta
 */

add_action('init', 'skate_register_pattern_categories', 5);
add_action('init', 'skate_register_patterns', 10);

function skate_register_pattern_categories(): void {
	register_block_pattern_category('skate-heroes', [
		'label' => __('SkateWP: Heroes', 'skate'),
	]);
	register_block_pattern_category('skate-sections', [
		'label' => __('SkateWP: Sections', 'skate'),
	]);
	register_block_pattern_category('skate-cta', [
		'label' => __('SkateWP: CTAs', 'skate'),
	]);
}

function skate_register_patterns(): void {
	$pattern_dir = get_template_directory() . '/patterns';

	foreach ( glob( $pattern_dir . '/*.php' ) as $file ) {
		if ( str_starts_with( basename( $file ), '_' ) ) {
			continue;
		}

		$headers = get_file_data( $file, [
			'title'          => 'Title',
			'slug'           => 'Slug',
			'categories'     => 'Categories',
			'keywords'       => 'Keywords',
			'viewportWidth'  => 'Viewport Width',
		] );

		if ( empty( $headers['slug'] ) || empty( $headers['title'] ) ) {
			continue;
		}

		ob_start();
		include $file;
		$content = ob_get_clean();

		$args = [
			'title'   => $headers['title'],
			'content' => $content,
		];

		if ( ! empty( $headers['categories'] ) ) {
			$args['categories'] = array_map( 'trim', explode( ',', $headers['categories'] ) );
		}
		if ( ! empty( $headers['keywords'] ) ) {
			$args['keywords'] = array_map( 'trim', explode( ',', $headers['keywords'] ) );
		}
		if ( ! empty( $headers['viewportWidth'] ) ) {
			$args['viewportWidth'] = (int) $headers['viewportWidth'];
		}

		register_block_pattern( $headers['slug'], $args );
	}
}
