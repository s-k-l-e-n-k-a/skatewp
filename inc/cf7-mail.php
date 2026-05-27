<?php

declare(strict_types=1);

if ( ! defined('WPCF7_VERSION') && ! function_exists('wpcf7') ) {
	return;
}

// Process WP shortcodes in all CF7 mail components after CF7 resolves its own tags.
add_filter( 'wpcf7_mail_components', function ( array $components ): array {
	$components['subject']  = do_shortcode( $components['subject'] );
	$components['sender']   = do_shortcode( $components['sender'] );
	$components['body']     = do_shortcode( $components['body'] );
	return $components;
} );

