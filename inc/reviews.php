<?php

/**
 * Skate Reviews
 *
 * Google Places API fetch + transient cache + [skate-reviews] shortcode.
 * Settings stored in skate_reviews_options (wp_options).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ----------------------------------------
// Shortcode [skate_reviews_url]
// Returns the Google reviews URL — use it as a button href in the editor.
// ----------------------------------------
add_shortcode( 'skate_reviews_url', function (): string {
	$opts = get_option( 'skate_reviews_options', [] );
	return esc_url( $opts['reviews_url'] ?? '' );
} );

// ----------------------------------------
// Register assets (enqueued only when shortcode renders)
// ----------------------------------------
add_action( 'wp_enqueue_scripts', function () {
	$dir = get_template_directory();
	$uri = get_template_directory_uri();

	wp_register_style(
		'skate-reviews',
		$uri . '/assets/css/skate-reviews.css',
		[],
		file_exists( $dir . '/assets/css/skate-reviews.css' ) ? (string) filemtime( $dir . '/assets/css/skate-reviews.css' ) : '1'
	);
	wp_register_script(
		'skate-reviews',
		$uri . '/assets/js/skate-reviews.js',
		[],
		file_exists( $dir . '/assets/js/skate-reviews.js' ) ? (string) filemtime( $dir . '/assets/js/skate-reviews.js' ) : '1',
		true
	);
} );

// ----------------------------------------
// API
// ----------------------------------------
function skate_reviews_fetch( string $placeId, string $apiKey, string $lang ): array {
	$cacheKey = 'skate_reviews_' . md5( $placeId . $lang );
	$cached   = get_transient( $cacheKey );
	if ( $cached !== false ) {
		return $cached;
	}

	$url = add_query_arg( [
		'place_id' => $placeId,
		'fields'   => 'name,rating,user_ratings_total,reviews',
		'key'      => $apiKey,
		'language' => $lang,
	], 'https://maps.googleapis.com/maps/api/place/details/json' );

	$res = wp_remote_get( $url, [ 'timeout' => 10 ] );

	if ( is_wp_error( $res ) ) {
		return [ 'error' => $res->get_error_message() ];
	}

	$body = json_decode( wp_remote_retrieve_body( $res ), true ) ?: [];

	if ( ! empty( $body['error_message'] ) ) {
		return [ 'error' => $body['error_message'] ];
	}

	$result = $body['result'] ?? [];
	skate_reviews_filter( $result );

	// Track cache keys for the "Clear cache" admin action
	$keys   = get_option( 'skate_reviews_cache_keys', [] );
	$keys[] = $cacheKey;
	update_option( 'skate_reviews_cache_keys', array_unique( $keys ), false );

	set_transient( $cacheKey, $result, WEEK_IN_SECONDS );

	return $result;
}

function skate_reviews_filter( array &$result ): void {
	if ( empty( $result['reviews'] ) || ! is_array( $result['reviews'] ) ) {
		return;
	}

	$result['reviews'] = array_values(
		array_filter( $result['reviews'], fn( $r ) => ( $r['rating'] ?? 0 ) >= 3 )
	);
}

function skate_reviews_get( string $placeId, string $apiKey, string $lang ): array {
	if ( empty( $placeId ) || empty( $apiKey ) ) {
		return [ 'error' => 'Missing API key or Place ID. Go to Reviews in the Skate admin to configure.' ];
	}

	return skate_reviews_fetch( $placeId, $apiKey, $lang );
}

// ----------------------------------------
// Shortcode [skate-reviews]
// ----------------------------------------
add_shortcode( 'skate-reviews', function ( array|string $atts ): string {
	$atts = shortcode_atts( [
		'place_id'    => '',
		'lang'        => 'en',
		'max'         => 10,
		'show_header' => '1',
	], $atts, 'skate-reviews' );

	$opts       = get_option( 'skate_reviews_options', [] );
	$apiKey     = $opts['api_key']     ?? '';
	$placeId    = ! empty( $atts['place_id'] ) ? $atts['place_id'] : ( $opts['place_id'] ?? '' );
	$reviewsUrl = $opts['reviews_url'] ?? '';
	$lang       = sanitize_text_field( $atts['lang'] );
	$max        = max( 1, (int) $atts['max'] );
	$showHeader = $atts['show_header'] !== '0';

	$data = skate_reviews_get( $placeId, $apiKey, $lang );

	if ( ! empty( $data['error'] ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p class="skate-reviews-error"><strong>SkateWP Reviews:</strong> ' . esc_html( $data['error'] ) . '</p>';
		}
		return '';
	}

	$reviews      = array_slice( $data['reviews'] ?? [], 0, $max );
	$rating       = round( (float) ( $data['rating'] ?? 0 ), 1 );
	$ratingsTotal = (int) ( $data['user_ratings_total'] ?? 0 );

	wp_enqueue_style( 'skate-reviews' );
	wp_enqueue_script( 'skate-reviews' );

	ob_start();
	?>
	<div class="skate-reviews">

		<?php if ( $showHeader && ( $rating > 0 || $reviewsUrl ) ) : ?>
		<div class="skate-reviews__header">
			<?php if ( $rating > 0 ) : ?>
			<div class="skate-reviews__score">
				<span class="skate-reviews__rating" aria-label="<?= esc_attr( $rating ) ?> out of 5"><?= esc_html( number_format( $rating, 1 ) ) ?></span>
				<div class="skate-reviews__score-meta">
					<div aria-label="<?= esc_attr( $rating ) ?> out of 5 stars">
						<?= skate_reviews_stars( $rating, 'lg' ) ?>
					</div>
					<?php if ( $ratingsTotal > 0 ) : ?>
					<span class="skate-reviews__count">Based on <?= esc_html( number_format( $ratingsTotal ) ) ?> reviews</span>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( $reviewsUrl ) : ?>
			<a href="<?= esc_url( $reviewsUrl ) ?>" class="skate-reviews__all-link" target="_blank" rel="noopener noreferrer">
				See all reviews on Google
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="13" height="13"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( empty( $reviews ) ) : ?>
		<p class="skate-reviews__empty">No reviews to display yet.</p>
		<?php else : ?>

		<div class="skate-reviews__viewport">
			<div class="skate-reviews__track">
				<?php foreach ( $reviews as $review ) :
					$author = $review['author_name']             ?? 'Anonymous';
					$avatar = $review['profile_photo_url']       ?? '';
					$stars  = (int) ( $review['rating']          ?? 5 );
					$text   = $review['text']                    ?? '';
					$time   = $review['relative_time_description'] ?? '';
				?>
				<div class="skate-reviews__card skate-radius skate-shadow">
					<div class="skate-reviews__card-head">
						<div class="skate-reviews__card-author">
							<?php if ( $avatar ) : ?>
							<img src="<?= esc_url( $avatar ) ?>" alt="<?= esc_attr( $author ) ?>" class="skate-reviews__avatar" width="42" height="42" loading="lazy">
							<?php else : ?>
							<span class="skate-reviews__avatar skate-reviews__avatar--initials" aria-hidden="true">
								<?= esc_html( mb_strtoupper( mb_substr( $author, 0, 1 ) ) ) ?>
							</span>
							<?php endif; ?>
							<div>
								<strong class="skate-reviews__author"><?= esc_html( $author ) ?></strong>
								<?php if ( $time ) : ?>
								<span class="skate-reviews__date"><?= esc_html( $time ) ?></span>
								<?php endif; ?>
							</div>
						</div>
						<?= skate_reviews_google_icon() ?>
					</div>

					<div class="skate-reviews__card-stars" aria-label="<?= esc_attr( $stars ) ?> out of 5 stars">
						<?= skate_reviews_stars( $stars, 'sm' ) ?>
					</div>

					<?php if ( $text ) : ?>
					<p class="skate-reviews__text"><?= esc_html( $text ) ?></p>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ( count( $reviews ) > 1 ) : ?>
		<div class="skate-reviews__nav" aria-label="Reviews navigation">
			<button class="skate-reviews__btn skate-reviews__btn--prev" aria-label="Previous review" disabled>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
			</button>
			<button class="skate-reviews__btn skate-reviews__btn--next" aria-label="Next review">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="18" height="18"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</button>
		</div>
		<?php endif; ?>

		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
} );

// ----------------------------------------
// Helpers
// ----------------------------------------
function skate_reviews_stars( float $rating, string $size = 'sm' ): string {
	$full  = (int) floor( $rating );
	$half  = ( $rating - $full ) >= 0.5;
	$empty = 5 - $full - ( $half ? 1 : 0 );
	$px    = $size === 'lg' ? 22 : 16;

	$fullPath  = 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z';
	$halfPath  = 'M12 2L8.91 8.26L2 9.27L7 14.14L5.82 21.02L12 17.77Z';

	$star = static function ( string $path, string $cls ) use ( $px ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $px . '" height="' . $px . '" class="' . $cls . '" aria-hidden="true"><path d="' . $path . '" fill="currentColor"/></svg>';
	};

	// Half star: full gray background + left half in accent color on top
	$halfStar = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="' . $px . '" height="' . $px . '" aria-hidden="true">'
		. '<path d="' . $fullPath . '" fill="currentColor" class="sr-star--empty"/>'
		. '<path d="' . $halfPath . '" fill="currentColor"/>'
		. '</svg>';

	$out = '<span class="skate-reviews__stars-wrap">';
	for ( $i = 0; $i < $full; $i++ )  { $out .= $star( $fullPath, '' ); }
	if ( $half )                        { $out .= $halfStar; }
	for ( $i = 0; $i < $empty; $i++ ) { $out .= $star( $fullPath, 'sr-star--empty' ); }
	$out .= '</span>';

	return $out;
}

function skate_reviews_google_icon(): string {
	return '<svg class="skate-reviews__google-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-label="Google" role="img">
		<path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
		<path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
		<path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
		<path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
	</svg>';
}
