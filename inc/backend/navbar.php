<?php

/**
 * Navigation — Skate
 *
 * Manages the site navbar via wp_options repeaters.
 * Renders via [skate_navbar] shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ----------------------------------------
// Allowed SVG tags for wp_kses
// ----------------------------------------
function skate_navbar_allowed_svg(): array {
	static $tags;
	if ( $tags ) return $tags;
	$tags = [
		'svg'      => [ 'xmlns' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'aria-hidden' => true, 'class' => true, 'width' => true, 'height' => true, 'id' => true ],
		'path'     => [ 'd' => true, 'fill' => true, 'fill-rule' => true, 'clip-rule' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true ],
		'circle'   => [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ],
		'rect'     => [ 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true ],
		'line'     => [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true ],
		'polyline' => [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ],
		'polygon'  => [ 'points' => true, 'fill' => true ],
		'g'        => [ 'id' => true, 'fill' => true, 'stroke' => true, 'clip-path' => true ],
		'defs'     => [],
		'clipPath' => [ 'id' => true ],
	];
	return $tags;
}

// ----------------------------------------
// Helper: resolve URL from page_id or fallback url
// ----------------------------------------
function skate_resolve_url( int $page_id, string $url, string $anchor = '' ): string {
	$anchor = ltrim( $anchor, '#' );
	if ( $page_id ) {
		$permalink = get_permalink( $page_id );
		if ( $permalink ) return $anchor ? rtrim( $permalink, '/' ) . '/#' . $anchor : $permalink;
	}
	$base = $url ?: '#';
	if ( $anchor && strpos( $base, '#' ) === false ) {
		return rtrim( $base, '/' ) . '/#' . $anchor;
	}
	return $base;
}


// ----------------------------------------
// Default hamburger SVG
// ----------------------------------------
define( 'SKATE_HBG_LINES_SVG', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 30" fill="none"><path d="M9 24H41V27H9V24Z" fill="currentColor"/><path d="M9 13.5H41V16.5H9V13.5Z" fill="currentColor"/><path d="M9 3H41V6H9V3Z" fill="currentColor"/></svg>' );

define( 'SKATE_HBG_DEFAULT_SVG', '<svg width="52" height="50" viewBox="0 0 52 50" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_7547_23841)"><path d="M6.26367 49V37.72H8.07167L12.2157 47.048H11.7197L15.8637 37.72H17.6557V49H15.7837V40.632H16.3597L12.5677 49H11.3357L7.54367 40.632H8.13567V49H6.26367Z" fill="#17263A"/><path d="M23.9135 49.16C22.5589 49.16 21.4975 48.7867 20.7295 48.04C19.9615 47.2933 19.5775 46.2693 19.5775 44.968C19.5775 44.1253 19.7429 43.3893 20.0735 42.76C20.4042 42.1307 20.8629 41.64 21.4495 41.288C22.0469 40.936 22.7402 40.76 23.5295 40.76C24.3082 40.76 24.9589 40.9253 25.4815 41.256C26.0042 41.5867 26.3989 42.0507 26.6655 42.648C26.9429 43.2453 27.0815 43.944 27.0815 44.744V45.272H21.1935V44.216H25.6575L25.3855 44.44C25.3855 43.6933 25.2255 43.1227 24.9055 42.728C24.5962 42.3333 24.1429 42.136 23.5455 42.136C22.8842 42.136 22.3722 42.3707 22.0095 42.84C21.6575 43.3093 21.4815 43.9653 21.4815 44.808V45.016C21.4815 45.8907 21.6949 46.5467 22.1215 46.984C22.5589 47.4107 23.1722 47.624 23.9615 47.624C24.4202 47.624 24.8469 47.5653 25.2415 47.448C25.6469 47.32 26.0309 47.1173 26.3935 46.84L26.9855 48.184C26.6015 48.4933 26.1429 48.7333 25.6095 48.904C25.0762 49.0747 24.5109 49.16 23.9135 49.16Z" fill="#17263A"/><path d="M28.7752 49V42.936C28.7752 42.6053 28.7645 42.2693 28.7432 41.928C28.7218 41.5867 28.6898 41.2507 28.6472 40.92H30.5832L30.7432 42.52H30.5512C30.8072 41.9547 31.1858 41.5227 31.6872 41.224C32.1885 40.9147 32.7698 40.76 33.4312 40.76C34.3805 40.76 35.0952 41.0267 35.5752 41.56C36.0552 42.0933 36.2952 42.9253 36.2952 44.056V49H34.2952V44.152C34.2952 43.5013 34.1672 43.0373 33.9112 42.76C33.6658 42.472 33.2925 42.328 32.7912 42.328C32.1725 42.328 31.6818 42.52 31.3192 42.904C30.9565 43.288 30.7752 43.8 30.7752 44.44V49H28.7752Z" fill="#17263A"/><path d="M41.3278 49.16C40.3358 49.16 39.5945 48.888 39.1038 48.344C38.6131 47.8 38.3678 46.968 38.3678 45.848V40.92H40.3678V45.832C40.3678 46.4293 40.4905 46.872 40.7358 47.16C40.9811 47.4373 41.3545 47.576 41.8558 47.576C42.4211 47.576 42.8798 47.384 43.2318 47C43.5945 46.616 43.7758 46.1093 43.7758 45.48V40.92H45.7758V49H43.8238V47.352H44.0478C43.8131 47.928 43.4558 48.376 42.9758 48.696C42.5065 49.0053 41.9571 49.16 41.3278 49.16ZM42.6878 39.432V37.592H44.5278V39.432H42.6878ZM39.6638 39.432V37.592H41.5038V39.432H39.6638Z" fill="#17263A"/><path d="M8.99967 25H40.9997V26.1H8.99967V25Z" fill="#17263A"/><path d="M8.99967 15H40.9997V16.1H8.99967V15Z" fill="#17263A"/><path d="M8.99967 5H40.9997V6.1H8.99967V5Z" fill="#17263A"/></g><defs><clipPath id="clip0_7547_23841"><rect width="52" height="49" fill="white" transform="translate(0 0.199219)"/></clipPath></defs></svg>' );

// ----------------------------------------
// Preset default data
// ----------------------------------------
function skate_navbar_defaults(): array {
	return [
		'links'   => [
			[ 'label' => 'Homepage', 'url' => '/', 'page_id' => 0, 'icon_svg' => '', 'columns' => [] ],
		],
		'buttons' => [],
	];
}

function skate_navbar_seed( bool $force = false ): void {
	if ( ! $force && get_option( 'skate_navbar_links' ) !== false ) return;
	$d = skate_navbar_defaults();

	// Try to resolve URL slugs to page IDs so the picker shows the linked page name.
	$resolve = function ( array &$items ): void {
		foreach ( $items as &$item ) {
			if ( ! empty( $item['page_id'] ) || empty( $item['url'] ) ) continue;
			$path = trim( (string) parse_url( $item['url'], PHP_URL_PATH ), '/' );
			$page = get_page_by_path( $path );
			if ( $page ) {
				$item['page_id']    = $page->ID;
				$item['url']        = '';
				unset( $item['unresolved'] );
			} else {
				$item['unresolved'] = true;
			}
		}
	};
	$resolve( $d['links'] );
	$resolve( $d['buttons'] );
	if ( isset( $d['icons'] ) ) $resolve( $d['icons'] );

	update_option( 'skate_navbar_links',   wp_json_encode( $d['links'] ) );
	update_option( 'skate_navbar_buttons', wp_json_encode( $d['buttons'] ) );
	if ( isset( $d['icons'] ) ) update_option( 'skate_navbar_icons', wp_json_encode( $d['icons'] ) );
}
// Seed on first activation only — skip if called from within a theme update
// (upgrader_process_complete fires switch_theme, we don't want to touch configured data).
add_action( 'after_switch_theme', function () {
	if ( defined( 'SKATE_DOING_UPDATE' ) ) return;
	skate_navbar_seed();
} );

// ----------------------------------------
// Shortcode: [skate_navbar]
// ----------------------------------------
add_shortcode( 'skate_navbar', 'skate_render_navbar' );

function skate_render_navbar(): string {
	$variant           = get_option( 'skate_navbar_variant', 'standard' );
	$logo_size         = (int) get_option( 'skate_navbar_logo_size', 118 );
	$logo_size_mobile  = (int) get_option( 'skate_navbar_logo_size_mobile', 80 );
	$logo_light_url    = get_option( 'skate_navbar_logo_light', '' );
	$links         = json_decode( get_option( 'skate_navbar_links',   '[]' ), true ) ?: [];
	$buttons       = json_decode( get_option( 'skate_navbar_buttons', '[]' ), true ) ?: [];
	$col_bg_preset = get_option( 'skate_navbar_col_bg_preset', 'none' );
	$submenu_close   = (bool) get_option( 'skate_navbar_submenu_close', '0' );
	$fullscreen_menu           = (bool) get_option( 'skate_navbar_fullscreen_menu', '0' );
	$fullscreen_font           = get_option( 'skate_navbar_fullscreen_font', 'inherit' );
	$fullscreen_bg             = get_option( 'skate_navbar_fullscreen_bg', '#17263a' );
	$fullscreen_bg_opacity     = (int) get_option( 'skate_navbar_fullscreen_bg_opacity', 100 );

	// Logo from WP site_logo option
	$logo_id  = (int) get_option( 'site_logo', 0 );
	$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	$logo_alt = $logo_id ? (string) get_post_meta( $logo_id, '_wp_attachment_image_alt', true ) : get_bloginfo( 'name' );

	// Column background tints
	$col_bg_css = '';
	if ( $col_bg_preset !== 'none' ) {
		$palette  = wp_get_global_settings( [ 'color', 'palette', 'theme' ] );
		$base_hex = match( $col_bg_preset ) {
			'grey'      => 'b0b8c4',
			'primary'   => '17263a',
			'secondary' => 'd6b36d',
			default     => 'b0b8c4',
		};
		$target_slug = match( $col_bg_preset ) {
			'primary'   => 'main-color',
			'secondary' => 'secondary-color',
			default     => null,
		};
		if ( $target_slug ) {
			foreach ( (array) $palette as $item ) {
				if ( ( $item['slug'] ?? '' ) === $target_slug ) {
					$base_hex = ltrim( $item['color'], '#' );
					break;
				}
			}
		}
		$r = hexdec( substr( $base_hex, 0, 2 ) );
		$g = hexdec( substr( $base_hex, 2, 2 ) );
		$b = hexdec( substr( $base_hex, 4, 2 ) );
		$levels    = [ 0.10, 0.07, 0.05, 0.03, 0.02 ];
		$col_bg_css = '<style>';
		foreach ( $levels as $i => $alpha ) {
			$n = $i + 1;
			$col_bg_css .= ".skate-navbar__submenu-col:nth-child({$n}){background:rgba({$r},{$g},{$b},{$alpha});}";
		}
		$col_bg_css .= '</style>';
	}

	ob_start();
	echo $col_bg_css; // phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<header class="skate-navbar skate-navbar--<?= esc_attr( $variant ) ?><?= $fullscreen_menu ? ' skate-navbar--fullscreen-mode' : '' ?>" id="skate-navbar">
		<div class="skate-navbar__overlay" aria-hidden="true"></div>
		<div class="skate-navbar__inner">

		<!-- Logo -->
		<div class="skate-navbar__logo">
			<a href="<?= esc_url( home_url( '/' ) ) ?>" aria-label="<?= esc_attr( get_bloginfo( 'name' ) ) ?>">
				<?php if ( $logo_url ) : ?>
					<img src="<?= esc_url( $logo_url ) ?>"
					     alt="<?= esc_attr( $logo_alt ) ?>"
					     class="skate-navbar__logo-img"
					     style="width:<?= $logo_size ?>px;--skate-logo-mobile-w:<?= $logo_size_mobile ?>px">
					<?php if ( $logo_light_url ) : ?>
						<img src="<?= esc_url( $logo_light_url ) ?>"
						     alt="<?= esc_attr( $logo_alt ) ?>"
						     class="skate-navbar__logo-img skate-navbar__logo-light"
						     style="width:<?= $logo_size ?>px;--skate-logo-mobile-w:<?= $logo_size_mobile ?>px">
					<?php endif; ?>
				<?php else : ?>
					<span class="skate-navbar__logo-text"><?= esc_html( get_bloginfo( 'name' ) ) ?></span>
				<?php endif; ?>
			</a>
		</div>

		<?php if ( $links ) : ?>
		<!-- Links -->
		<div class="skate-navbar__links skate-navbar__desktop-item">
			<?php foreach ( $links as $link ) :
				// Normalize: migrate old separate 'featured' key into unified columns array
				$cols = $link['columns'] ?? [];
				if ( ! empty( $link['featured']['post_id'] ) ) {
					$has_feat_col = ! empty( array_filter( $cols, fn( $c ) => ( $c['type'] ?? 'regular' ) === 'featured' ) );
					if ( ! $has_feat_col ) $cols[] = [ 'type' => 'featured', 'post_id' => (int) $link['featured']['post_id'] ];
				}
				$has_sub = ! empty( $cols );
			?>
			<div class="skate-navbar__link-item<?= $has_sub ? ' skate-navbar__link-item--has-sub' : '' ?>"<?= $has_sub ? ' data-submenu' : '' ?>>
				<?php $link_url = skate_resolve_url( (int) ( $link['page_id'] ?? 0 ), $link['url'] ?? '' ); ?>
				<a href="<?= esc_url( $link_url ) ?>" class="skate-navbar__link"<?= $has_sub ? ' aria-haspopup="true" aria-expanded="false"' : '' ?>>
					<span class="skate-navbar__link-text"><?= esc_html( $link['label'] ?? '' ) ?></span>
				</a>
				<?php if ( $has_sub ) : ?>
				<div class="skate-navbar__submenu" role="region" aria-label="<?= esc_attr( $link['label'] ?? '' ) ?> menu">
					<?php if ( $submenu_close ) : ?>
					<button class="skate-navbar__submenu-close" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</button>
					<?php endif; ?>
					<div class="skate-navbar__submenu-inner">
						<?php foreach ( $cols as $col ) :
							if ( ( $col['type'] ?? 'regular' ) === 'featured' ) :
								$feat_post = get_post( (int) ( $col['post_id'] ?? 0 ) );
								if ( $feat_post && $feat_post->post_status === 'publish' && $feat_post->post_type === 'wp_block' ) :
									$feat_bg     = $col['bg_color'] ?? '';
									$feat_bg_css = $feat_bg ? esc_attr( $feat_bg ) : 'var(--wp--preset--color--light-gray,#F2F4F6)';
								?>
						<div class="skate-navbar__submenu-col skate-navbar__submenu-col--featured" style="background-color:<?= $feat_bg_css ?>">
							<?= do_shortcode( do_blocks( $feat_post->post_content ) ) // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
								<?php endif; ?>
							<?php else : ?>
						<div class="skate-navbar__submenu-col">
							<?php foreach ( ( $col['groups'] ?? [] ) as $grp ) : ?>
							<?php if ( $grp['title'] ?? '' ) : ?>
							<div class="skate-navbar__submenu-col-head"><?= esc_html( $grp['title'] ) ?></div>
							<?php endif; ?>
							<ul class="skate-navbar__submenu-col-links skate-navbar__submenu-col-links--<?= esc_attr( $submenu_icon_mode ) ?>">
								<?php foreach ( ( $grp['links'] ?? [] ) as $cl ) :
									$cl_url = skate_resolve_url( (int) ( $cl['page_id'] ?? 0 ), $cl['url'] ?? '', $cl['anchor'] ?? '' );
								?>
								<li><a href="<?= esc_url( $cl_url ) ?>"><?= esc_html( $cl['title'] ?? '' ) ?></a></li>
								<?php endforeach; ?>
							</ul>
							<?php endforeach; ?>
						</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<!-- Right group (buttons + mobile trigger) -->
		<div class="skate-navbar__right">
			<?php if ( $buttons ) : ?>
			<div class="skate-navbar__buttons skate-navbar__desktop-item">
				<?php foreach ( $buttons as $btn ) : ?>
				<?php $btn_url = skate_resolve_url( (int) ( $btn['page_id'] ?? 0 ), $btn['url'] ?? '' ); ?>
				<a href="<?= esc_url( $btn_url ) ?>" class="skate-navbar__btn skate-navbar__btn--<?= esc_attr( $btn['style'] ?? 'filled' ) ?>"><?= esc_html( $btn['label'] ?? '' ) ?></a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<!-- Mobile hamburger trigger -->
			<button class="skate-navbar__hamburger" aria-expanded="false" aria-controls="skate-mobile-menu" aria-label="Open menu"><span class="skate-navbar__icon-svg" aria-hidden="true"><?= SKATE_HBG_LINES_SVG ?></span></button>
		</div><!-- /.skate-navbar__right -->

		</div><!-- /.skate-navbar__inner -->

		<?php if ( $fullscreen_menu ) : ?>
		<!-- Fullscreen overlay -->
		<?php
		$_bg_hex = ltrim( $fullscreen_bg ?: '#17263a', '#' );
		$_bg_css = $fullscreen_bg_opacity < 100
			? 'rgba(' . hexdec( substr( $_bg_hex, 0, 2 ) ) . ',' . hexdec( substr( $_bg_hex, 2, 2 ) ) . ',' . hexdec( substr( $_bg_hex, 4, 2 ) ) . ',' . round( $fullscreen_bg_opacity / 100, 2 ) . ')'
			: '#' . $_bg_hex;
		$fs_styles = [ '--skate-fs-bg: ' . esc_attr( $_bg_css ) ];
		if ( $fullscreen_font && $fullscreen_font !== 'inherit' ) {
			$fs_styles[] = '--skate-fs-font: var(--wp--preset--font-family--' . esc_attr( $fullscreen_font ) . ')';
		}
		$fs_style_attr = ' style="' . implode( '; ', $fs_styles ) . '"';
		?>
		<div class="skate-navbar__fullscreen" id="skate-fullscreen-menu"<?= $fs_style_attr ?> aria-hidden="true" role="dialog" aria-modal="true" aria-label="Navigation">
			<button class="skate-navbar__fullscreen-close" aria-label="Close menu">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
			</button>
			<nav class="skate-navbar__fullscreen-nav">
				<?php foreach ( $links as $link ) :
					$link_url = skate_resolve_url( (int) ( $link['page_id'] ?? 0 ), $link['url'] ?? '' ); ?>
				<a href="<?= esc_url( $link_url ) ?>" class="skate-navbar__fullscreen-link"><?= esc_html( $link['label'] ?? '' ) ?></a>
				<?php endforeach; ?>
				<?php foreach ( $buttons as $btn ) :
					$btn_url = skate_resolve_url( (int) ( $btn['page_id'] ?? 0 ), $btn['url'] ?? '' ); ?>
				<a href="<?= esc_url( $btn_url ) ?>" class="skate-navbar__fullscreen-btn skate-navbar__btn--<?= esc_attr( $btn['style'] ?? 'filled' ) ?>"><?= esc_html( $btn['label'] ?? '' ) ?></a>
				<?php endforeach; ?>
			</nav>
		</div>
		<?php endif; ?>

		<!-- Mobile menu panel -->
		<div class="skate-navbar__mobile" id="skate-mobile-menu" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Navigation">
			<div class="skate-navbar__mobile-overlay"></div>
			<div class="skate-navbar__mobile-panel">

				<!-- Panel header: logo + close -->
				<div class="skate-navbar__mobile-header">
					<div class="skate-navbar__mobile-logo">
						<?php if ( $logo_url ) : ?>
						<img src="<?= esc_url( $logo_url ) ?>"
						     alt="<?= esc_attr( $logo_alt ) ?>"
						     style="width:<?= $logo_size_mobile ?>px;max-width:100%;">
						<?php endif; ?>
					</div>
					<button class="skate-navbar__mobile-close" aria-label="Close menu">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
							<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
						</svg>
					</button>
				</div>

				<?php if ( $links ) : ?>
				<!-- Mobile links -->
				<div class="skate-navbar__mobile-links">
					<?php foreach ( $links as $li => $link ) :
						$has_sub = ! empty( $link['columns'] );
					?>
					<div class="skate-navbar__mobile-link-item">
						<?php if ( $has_sub ) :
						$mob_sub_id = 'skate-mob-sub-' . $li; ?>
						<button class="skate-navbar__mobile-link-header" aria-expanded="false" aria-controls="<?= esc_attr( $mob_sub_id ) ?>">
							<?= esc_html( $link['label'] ?? '' ) ?>
						</button>
						<div class="skate-navbar__mobile-submenu" id="<?= esc_attr( $mob_sub_id ) ?>">
							<?php foreach ( ( $link['columns'] ?? [] ) as $col ) :
								if ( ( $col['type'] ?? 'regular' ) === 'featured' ) :
									$feat_post = get_post( (int) ( $col['post_id'] ?? 0 ) );
									if ( $feat_post && $feat_post->post_status === 'publish' && $feat_post->post_type === 'wp_block' ) :
										remove_filter( 'the_content', 'wpautop' );
										$feat_html = do_shortcode( do_blocks( $feat_post->post_content ) );
										add_filter( 'the_content', 'wpautop' );
										?>
							<div class="skate-navbar__mobile-featured">
								<?= $feat_html // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
									<?php endif; ?>
								<?php else : ?>
									<?php foreach ( ( $col['groups'] ?? [] ) as $grp ) : ?>
										<?php if ( ! empty( $grp['title'] ) ) : ?>
							<div class="skate-navbar__mobile-group-title"><?= esc_html( $grp['title'] ) ?></div>
										<?php endif; ?>
										<?php foreach ( ( $grp['links'] ?? [] ) as $cl ) :
											if ( empty( $cl['title'] ) ) continue;
											$cl_url = skate_resolve_url( (int) ( $cl['page_id'] ?? 0 ), $cl['url'] ?? '', $cl['anchor'] ?? '' ); ?>
							<a href="<?= esc_url( $cl_url ) ?>" class="skate-navbar__mobile-submenu-item">
								<?= esc_html( $cl['title'] ) ?>
							</a>
										<?php endforeach; ?>
									<?php endforeach; ?>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
						<?php else : ?>
						<?php $link_url = skate_resolve_url( (int) ( $link['page_id'] ?? 0 ), $link['url'] ?? '' ); ?>
						<a href="<?= esc_url( $link_url ) ?>" class="skate-navbar__mobile-link-header">
							<?= esc_html( $link['label'] ?? '' ) ?>
						</a>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php if ( $buttons ) : ?>
				<!-- Mobile buttons -->
				<div class="skate-navbar__mobile-buttons">
					<?php foreach ( $buttons as $btn ) : ?>
					<?php $btn_url = skate_resolve_url( (int) ( $btn['page_id'] ?? 0 ), $btn['url'] ?? '' ); ?>
					<a href="<?= esc_url( $btn_url ) ?>" class="skate-navbar__btn skate-navbar__btn--<?= esc_attr( $btn['style'] ?? 'filled' ) ?>"><?= esc_html( $btn['label'] ?? '' ) ?></a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

			</div>
		</div>

	</header>
	<?php
	$html = ob_get_clean();
	// Strip HTML comments so wpautop cannot inject <br> tags after them.
	return preg_replace( '/<!--.*?-->/s', '', $html );
}

// ----------------------------------------
// Body class for navbar variant
// ----------------------------------------
add_filter( 'body_class', function ( $classes ) {
	$v = get_option( 'skate_navbar_variant', 'standard' );
	if ( $v && $v !== 'standard' ) {
		$classes[] = 'skate-navbar--' . sanitize_html_class( $v );
	}
	if ( get_option( 'skate_navbar_link_text', 'inherit' ) === 'uppercase' ) {
		$classes[] = 'skate-navbar--links-upper';
	}
	if ( get_option( 'skate_navbar_submenu_text', 'inherit' ) === 'uppercase' ) {
		$classes[] = 'skate-navbar--submenus-upper';
	}
	return $classes;
} );

// ----------------------------------------
// CSS injection for navbar link font size
// ----------------------------------------
add_action( 'wp_head', function () {
	$allowed = [ 'small', 'medium', 'large', 'extra-large', 'extra-extra-large' ];
	$slug    = get_option( 'skate_navbar_link_font_size', 'inherit' );
	if ( ! in_array( $slug, $allowed, true ) ) return;
	echo '<style id="skate-navbar-link-fs">#skate-navbar{--skate-navbar-link-fs:var(--wp--preset--font-size--' . esc_attr( $slug ) . ')}</style>';
}, 20 );

add_action( 'wp_head', function () {
	$allowed = [ 'small', 'medium', 'large', 'extra-large', 'extra-extra-large' ];
	$slug    = get_option( 'skate_navbar_submenu_font_size', 'medium' );
	if ( ! in_array( $slug, $allowed, true ) ) return;
	echo '<style id="skate-navbar-submenu-fs">#skate-navbar{--skate-navbar-submenu-fs:var(--wp--preset--font-size--' . esc_attr( $slug ) . ')}</style>';
}, 20 );

add_action( 'wp_head', function () {
	$map = [ 's' => '5px', 'm' => '10px', 'l' => '16px' ];
	$val = get_option( 'skate_navbar_submenu_gap', 's' );
	if ( $val === 's' || ! isset( $map[ $val ] ) ) return; // 's' is the CSS default, no injection needed
	echo '<style id="skate-navbar-submenu-gap">#skate-navbar{--skate-navbar-submenu-gap:' . $map[ $val ] . '}</style>';
}, 20 );

// ----------------------------------------
// CSS injection for transparent variant (late, wins over any inline styles)
// ----------------------------------------
add_action( 'wp_head', function () {
	$v          = get_option( 'skate_navbar_variant', 'standard' );
	$logo_light = get_option( 'skate_navbar_logo_light', '' );

	// Dark variant: switch to light logo
	if ( $v === 'dark' && $logo_light ) {
		echo '<style id="skate-navbar-dark-logo">';
		echo 'body.skate-navbar--dark .skate-navbar .skate-navbar__logo-img:not(.skate-navbar__logo-light){display:none!important;}';
		echo 'body.skate-navbar--dark .skate-navbar .skate-navbar__logo-light{display:inline-block!important;}';
		echo '</style>';
	}
}, 999 );

add_action( 'wp_head', function () {
	$v = get_option( 'skate_navbar_variant', 'standard' );
	if ( $v !== 'transparent' ) return;
	$logo_light = get_option( 'skate_navbar_logo_light', '' );
	echo '<style id="skate-navbar-transparent">';
	// Base transparent state
	echo '.skate-navbar--transparent{background:transparent!important;box-shadow:none!important;}';
	// Scrolled state — white bg fades in
	echo '.skate-navbar--transparent.skate-navbar--scrolled{background:var(--wp--preset--color--white,#fff)!important;box-shadow:0 2px 12px rgba(0,0,0,.08)!important;}';
	// Links + hamburger: white when transparent, black when scrolled
	echo '.skate-navbar--transparent:not(.skate-navbar--scrolled) .skate-navbar__link,
		.skate-navbar--transparent:not(.skate-navbar--scrolled) .skate-navbar__hamburger{color:var(--wp--preset--color--white,#fff)!important;}';
	echo '.skate-navbar--transparent:not(.skate-navbar--scrolled) .skate-navbar__link:hover{background-color:rgba(255,255,255,.12)!important;}';
	echo '.skate-navbar--transparent:not(.skate-navbar--scrolled) .skate-navbar__link-item[data-submenu]>.skate-navbar__link::after{background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 10 6\' fill=\'none\' stroke=\'%23ffffff\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'1,1 5,5 9,1\'/%3E%3C/svg%3E")!important;}';
	echo '.skate-navbar--transparent.skate-navbar--scrolled .skate-navbar__link,
		.skate-navbar--transparent.skate-navbar--scrolled .skate-navbar__hamburger{color:var(--wp--preset--color--black,#17263a)!important;}';
	// Logo handling
	if ( $logo_light ) {
		// Transparent: hide normal logo, show light logo
		echo '.skate-navbar--transparent:not(.skate-navbar--scrolled) .skate-navbar__logo-img:not(.skate-navbar__logo-light){display:none!important;}';
		echo '.skate-navbar--transparent:not(.skate-navbar--scrolled) .skate-navbar__logo-light{display:inline-block!important;}';
		// Scrolled: show normal logo, hide light logo
		echo '.skate-navbar--transparent.skate-navbar--scrolled .skate-navbar__logo-light{display:none!important;}';
		echo '.skate-navbar--transparent.skate-navbar--scrolled .skate-navbar__logo-img:not(.skate-navbar__logo-light){display:block!important;}';
	} else {
		// No light logo: invert when transparent, restore when scrolled
		echo '.skate-navbar--transparent:not(.skate-navbar--scrolled) .skate-navbar__logo-img{filter:brightness(0) invert(1);}';
		echo '.skate-navbar--transparent.skate-navbar--scrolled .skate-navbar__logo-img{filter:none!important;}';
	}
	echo '</style>';
}, 999 );

// --skate-navbar-offset: used by templates to push content below the fixed navbar.
// Transparent variant = 0 (content intentionally starts behind the navbar).
add_action( 'wp_head', function () {
	$variant        = get_option( 'skate_navbar_variant', 'standard' );
	$desktop_offset = match ( $variant ) {
		'transparent' => '0px',
		'compact'     => '72px',
		default       => '96px',
	};
	$mobile_offset  = $variant === 'transparent' ? '0px' : '60px';
	echo '<style id="skate-navbar-offset">';
	echo ':root{--skate-navbar-offset:' . $desktop_offset . ';}';
	echo '@media(max-width:991px){:root{--skate-navbar-offset:' . $mobile_offset . ';}}';
	echo '</style>';
} );

// ----------------------------------------
// Admin only beyond this point
// ----------------------------------------
if ( ! is_admin() ) return;

// ----------------------------------------
// Admin menu registration
// ----------------------------------------
add_action( 'admin_menu', function () {
	add_submenu_page(
		'skate',
		__( 'SkateWP – Navigation', 'skate' ),
		__( 'Navigation', 'skate' ),
		'manage_options',
		'skate-navbar',
		'skate_render_navbar_admin'
	);
} );

// ── Preset option keys (shared between save/load handlers) ──────────────────
define( 'SKATE_NAVBAR_PRESET_KEYS', [
	'skate_navbar_links', 'skate_navbar_buttons', 'skate_navbar_icons',
	'skate_navbar_variant', 'skate_navbar_logo_size', 'skate_navbar_logo_size_mobile',
	'skate_navbar_logo_light', 'skate_navbar_submenu_icon_mode',
	'skate_navbar_submenu_icon_default_svg', 'skate_navbar_col_bg_preset',
	'skate_navbar_hamburger_enabled', 'skate_navbar_hamburger_svg',
	'skate_navbar_hamburger_columns', 'skate_navbar_hamburger_mobile_title',
	'skate_navbar_hamburger_mobile_label',
] );

// Handle named preset CRUD + export + legacy load
add_action( 'admin_init', function () {

	// ── Export presets (GET download) ────────────────────────────────────────
	if (
		isset( $_GET['skate_export_presets'] ) &&
		isset( $_GET['_wpnonce'] ) &&
		wp_verify_nonce( $_GET['_wpnonce'], 'skate_export_presets' ) &&
		current_user_can( 'manage_options' )
	) {
		$presets = json_decode( get_option( 'skate_navbar_presets', '[]' ), true ) ?: [];
		$json    = wp_json_encode( $presets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="skate-navbar-presets.json"' );
		header( 'Content-Length: ' . strlen( $json ) );
		header( 'Cache-Control: no-cache' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	$nonce_ok = isset( $_POST['skate_navbar_nonce'] )
		&& wp_verify_nonce( $_POST['skate_navbar_nonce'], 'skate_save_navbar' )
		&& current_user_can( 'manage_options' );
	if ( ! $nonce_ok ) return;

	// ── Save named preset ────────────────────────────────────────────────────
	if ( isset( $_POST['skate_navbar_save_named_preset'] ) ) {
		$name = sanitize_text_field( wp_unslash( $_POST['skate_navbar_preset_name'] ?? '' ) );
		if ( ! $name ) $name = wp_date( 'j M Y, H:i' );
		$data = [];
		foreach ( SKATE_NAVBAR_PRESET_KEYS as $k ) {
			$data[ $k ] = get_option( $k );
		}
		$presets   = json_decode( get_option( 'skate_navbar_presets', '[]' ), true ) ?: [];
		$presets[] = [ 'id' => uniqid( '', true ), 'name' => $name, 'saved_at' => time(), 'data' => $data ];
		update_option( 'skate_navbar_presets', wp_json_encode( $presets ) );
		wp_safe_redirect( add_query_arg( [ 'page' => 'skate-navbar', 'tab' => 'submenus', 'preset_saved' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	// ── Import presets from JSON ─────────────────────────────────────────────
	if ( isset( $_POST['skate_navbar_import_presets'] ) ) {
		$json     = wp_unslash( $_POST['skate_navbar_import_json'] ?? '' );
		$imported = json_decode( $json, true );
		if ( is_array( $imported ) ) {
			$existing = json_decode( get_option( 'skate_navbar_presets', '[]' ), true ) ?: [];
			foreach ( $imported as $p ) {
				if ( isset( $p['name'], $p['data'] ) && is_array( $p['data'] ) ) {
					$p['id'] = uniqid( '', true ); // fresh ID to avoid collisions
					$existing[] = $p;
				}
			}
			update_option( 'skate_navbar_presets', wp_json_encode( $existing ) );
		}
		wp_safe_redirect( add_query_arg( [ 'page' => 'skate-navbar', 'tab' => 'submenus', 'preset_imported' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	// ── Load named preset ────────────────────────────────────────────────────
	if ( isset( $_POST['skate_navbar_load_named_preset'] ) ) {
		$id      = sanitize_text_field( wp_unslash( $_POST['skate_navbar_preset_id'] ?? '' ) );
		$presets = json_decode( get_option( 'skate_navbar_presets', '[]' ), true ) ?: [];
		foreach ( $presets as $preset ) {
			if ( ( $preset['id'] ?? '' ) === $id ) {
				foreach ( (array) ( $preset['data'] ?? [] ) as $key => $val ) {
					if ( str_starts_with( $key, 'skate_navbar_' ) ) update_option( $key, $val );
				}
				break;
			}
		}
		wp_safe_redirect( add_query_arg( [ 'page' => 'skate-navbar', 'tab' => 'submenus', 'preset' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	// ── Delete preset ────────────────────────────────────────────────────────
	if ( isset( $_POST['skate_navbar_delete_preset'] ) ) {
		$id      = sanitize_text_field( wp_unslash( $_POST['skate_navbar_preset_id'] ?? '' ) );
		$presets = json_decode( get_option( 'skate_navbar_presets', '[]' ), true ) ?: [];
		$presets = array_values( array_filter( $presets, fn( $p ) => ( $p['id'] ?? '' ) !== $id ) );
		update_option( 'skate_navbar_presets', wp_json_encode( $presets ) );
		wp_safe_redirect( add_query_arg( [ 'page' => 'skate-navbar', 'tab' => 'submenus' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	// ── Legacy load (hardcoded PHP defaults) ─────────────────────────────────
	if ( isset( $_POST['skate_navbar_load_preset'] ) ) {
		skate_navbar_seed( true );
		wp_safe_redirect( add_query_arg( [ 'page' => 'skate-navbar', 'preset' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}
} );

// Enqueue WP media uploader on this page
add_action( 'admin_enqueue_scripts', function () {
	$screen = get_current_screen();
	if ( $screen && $screen->id === 'skatewp_page_skate-navbar' ) {
		wp_enqueue_media();
	}
} );

// ----------------------------------------
// Admin render
// ----------------------------------------
function skate_render_navbar_admin(): void {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$saved       = false;
	$allowed_svg = skate_navbar_allowed_svg();

	// ---- Save ----
	if (
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset( $_POST['skate_navbar_nonce'] ) &&
		wp_verify_nonce( $_POST['skate_navbar_nonce'], 'skate_save_navbar' )
	) {
		// Variant
		update_option( 'skate_navbar_variant', sanitize_key( $_POST['skate_navbar_variant'] ?? 'standard' ) );

		// Logo settings
		update_option( 'skate_navbar_logo_size',        absint( $_POST['skate_navbar_logo_size']        ?? 118 ) );
		update_option( 'skate_navbar_logo_size_mobile', absint( $_POST['skate_navbar_logo_size_mobile'] ?? 80  ) );
		update_option( 'skate_navbar_logo_light',       esc_url_raw( wp_unslash( $_POST['skate_navbar_logo_light'] ?? '' ) ) );

		update_option( 'skate_navbar_col_bg_preset',            sanitize_key( $_POST['skate_navbar_col_bg_preset'] ?? 'none' ) );
		update_option( 'skate_navbar_submenu_close',            isset( $_POST['skate_navbar_submenu_close'] ) ? '1' : '0' );
		update_option( 'skate_navbar_fullscreen_menu',          isset( $_POST['skate_navbar_fullscreen_menu'] ) ? '1' : '0' );
		$allowed_fonts = array_merge( [ 'inherit' ], array_column( wp_get_global_settings( [ 'typography', 'fontFamilies' ] )['theme'] ?? [], 'slug' ) );
		update_option( 'skate_navbar_fullscreen_font', in_array( $_POST['skate_navbar_fullscreen_font'] ?? 'inherit', $allowed_fonts, true ) ? $_POST['skate_navbar_fullscreen_font'] : 'inherit' );
		update_option( 'skate_navbar_fullscreen_bg',         sanitize_hex_color( $_POST['skate_navbar_fullscreen_bg'] ?? '#17263a' ) ?: '#17263a' );
		update_option( 'skate_navbar_fullscreen_bg_opacity', min( 100, max( 0, (int) ( $_POST['skate_navbar_fullscreen_bg_opacity'] ?? 100 ) ) ) );
		update_option( 'skate_navbar_link_text',                in_array( $_POST['skate_navbar_link_text'] ?? '', [ 'inherit', 'uppercase' ] ) ? $_POST['skate_navbar_link_text'] : 'inherit' );
		$fs_allowed = [ 'inherit', 'small', 'medium', 'large' ];
		update_option( 'skate_navbar_link_font_size',           in_array( $_POST['skate_navbar_link_font_size'] ?? '', $fs_allowed ) ? $_POST['skate_navbar_link_font_size'] : 'inherit' );
		$sfs_allowed = [ 'small', 'medium', 'large' ];
		update_option( 'skate_navbar_submenu_font_size',        in_array( $_POST['skate_navbar_submenu_font_size'] ?? '', $sfs_allowed ) ? $_POST['skate_navbar_submenu_font_size'] : 'medium' );
		update_option( 'skate_navbar_submenu_text',             in_array( $_POST['skate_navbar_submenu_text'] ?? '', [ 'inherit', 'uppercase' ] ) ? $_POST['skate_navbar_submenu_text'] : 'inherit' );
		update_option( 'skate_navbar_submenu_gap',              in_array( $_POST['skate_navbar_submenu_gap'] ?? '', [ 's', 'm', 'l' ] ) ? $_POST['skate_navbar_submenu_gap'] : 's' );

		// Links + icon SVGs + submenu columns → groups → links
		$link_labels      = (array) ( $_POST['skate_nav_link_label']        ?? [] );
		$link_urls        = (array) ( $_POST['skate_nav_link_url']          ?? [] );
		$link_page_ids    = array_map( 'absint', (array) ( $_POST['skate_nav_link_page_id'] ?? [] ) );
		$col_group_titles = (array) ( $_POST['skate_nav_col_group_title']   ?? [] );
		$col_link_titles   = (array) ( $_POST['skate_nav_col_link_title']   ?? [] );
		$col_link_urls     = (array) ( $_POST['skate_nav_col_link_url']     ?? [] );
		$col_link_pids     = (array) ( $_POST['skate_nav_col_link_pid']     ?? [] );
		$col_link_anchors  = (array) ( $_POST['skate_nav_col_link_anchor']  ?? [] );

		$links = [];
		foreach ( $link_labels as $i => $raw_label ) {
			$label = sanitize_text_field( wp_unslash( $raw_label ) );
			$url   = esc_url_raw( wp_unslash( $link_urls[ $i ] ?? '' ) );
			if ( ! $label && ! $url ) continue;

			// Build regular columns indexed by ci
			$reg_cols = [];
			foreach ( array_keys( $col_group_titles[ $i ] ?? [] ) as $ci ) {
				$ci     = (int) $ci;
				$groups = [];
				foreach ( array_keys( $col_group_titles[ $i ][ $ci ] ?? [] ) as $gi ) {
					$grp_title       = sanitize_text_field( wp_unslash( $col_group_titles[ $i ][ $ci ][ $gi ] ?? '' ) );
					$raw_link_titles = (array) ( $col_link_titles[ $i ][ $ci ][ $gi ] ?? [] );
					$grp_links = [];
					foreach ( $raw_link_titles as $k => $raw_lt ) {
						$lt   = sanitize_text_field( wp_unslash( $raw_lt ) );
						$lu  = esc_url_raw( wp_unslash( $col_link_urls[ $i ][ $ci ][ $gi ][ $k ] ?? '' ) );
						$pid = absint( $col_link_pids[ $i ][ $ci ][ $gi ][ $k ] ?? 0 );
						$la  = ltrim( sanitize_text_field( wp_unslash( $col_link_anchors[ $i ][ $ci ][ $gi ][ $k ] ?? '' ) ), '#' );
						if ( ! $lt && ! $lu && ! $pid ) continue;
						$entry = [ 'title' => $lt, 'url' => $lu, 'page_id' => $pid ];
						if ( $la !== '' ) $entry['anchor'] = $la;
						$grp_links[] = $entry;
					}
					if ( $grp_title || $grp_links ) {
						$groups[] = [ 'title' => $grp_title, 'links' => $grp_links ];
					}
				}
				if ( $groups ) $reg_cols[ $ci ] = [ 'type' => 'regular', 'groups' => $groups ];
			}

			// Build featured columns indexed by fci
			$feat_cols = [];
			foreach ( (array) ( $_POST['skate_nav_featured_post_id'][ $i ] ?? [] ) as $fci => $pid ) {
				$pid = (int) $pid;
				if ( $pid > 0 ) {
					$bg = sanitize_hex_color( $_POST['skate_nav_featured_bg_color'][ $i ][ $fci ] ?? '' );
					$feat_cols[ (int) $fci ] = [ 'type' => 'featured', 'post_id' => $pid, 'bg_color' => $bg ?: '' ];
				}
			}

			// Assemble columns in order declared by the JS order input
			$col_order_raw = wp_unslash( $_POST['skate_nav_col_order'][ $i ] ?? '[]' );
			$col_order     = json_decode( $col_order_raw, true );
			$columns       = [];
			if ( is_array( $col_order ) && $col_order ) {
				foreach ( $col_order as $item ) {
					if ( ( $item['type'] ?? '' ) === 'regular' ) {
						$ci = (int) ( $item['ci'] ?? -1 );
						if ( isset( $reg_cols[ $ci ] ) ) $columns[] = $reg_cols[ $ci ];
					} elseif ( ( $item['type'] ?? '' ) === 'featured' ) {
						$fci = (int) ( $item['fci'] ?? -1 );
						if ( isset( $feat_cols[ $fci ] ) ) $columns[] = $feat_cols[ $fci ];
					}
				}
			} else {
				$columns = array_values( $reg_cols ); // fallback: no order input
			}

			$links[] = [
				'label'   => $label,
				'url'     => $url,
				'page_id' => $link_page_ids[$i] ?? 0,
				'columns' => $columns,
			];
		}
		update_option( 'skate_navbar_links', wp_json_encode( $links ) );

		// Buttons
		$btn_labels   = (array) ( $_POST['skate_nav_btn_label'] ?? [] );
		$btn_urls     = (array) ( $_POST['skate_nav_btn_url']   ?? [] );
		$btn_page_ids = array_map( 'absint', (array) ( $_POST['skate_nav_btn_page_id'] ?? [] ) );
		$btn_styles   = (array) ( $_POST['skate_nav_btn_style'] ?? [] );
		$valid_styles = [ 'filled', 'outline', 'filled-dark' ];
		$buttons    = [];
		foreach ( $btn_labels as $i => $raw_label ) {
			$label = sanitize_text_field( wp_unslash( $raw_label ) );
			$url   = esc_url_raw( wp_unslash( $btn_urls[ $i ] ?? '' ) );
			if ( ! $label && ! $url ) continue;
			$style     = in_array( $btn_styles[ $i ] ?? '', $valid_styles, true ) ? $btn_styles[ $i ] : 'filled';
			$buttons[] = [ 'label' => $label, 'url' => $url, 'page_id' => $btn_page_ids[$i] ?? 0, 'style' => $style ];
		}
		update_option( 'skate_navbar_buttons', wp_json_encode( $buttons ) );

		$saved = true;
	}

	// ---- Current values ----
	$variant          = get_option( 'skate_navbar_variant', 'standard' );
	$logo_size        = (int) get_option( 'skate_navbar_logo_size', 118 );
	$logo_size_mobile = (int) get_option( 'skate_navbar_logo_size_mobile', 80 );
	$logo_light_url   = get_option( 'skate_navbar_logo_light', '' );
	$links                        = json_decode( get_option( 'skate_navbar_links',   '[]' ), true ) ?: [];
	$buttons                      = json_decode( get_option( 'skate_navbar_buttons', '[]' ), true ) ?: [];
	$col_bg_preset_opt            = get_option( 'skate_navbar_col_bg_preset', 'none' );
	$link_text_opt                = get_option( 'skate_navbar_link_text', 'inherit' );
	$link_font_size_opt           = get_option( 'skate_navbar_link_font_size', 'inherit' );
	$submenu_font_size_opt        = get_option( 'skate_navbar_submenu_font_size', 'medium' );
	$submenu_text_opt             = get_option( 'skate_navbar_submenu_text', 'inherit' );
	$submenu_gap_opt              = get_option( 'skate_navbar_submenu_gap', 's' );
	$fullscreen_menu_opt          = (bool) get_option( 'skate_navbar_fullscreen_menu', '0' );
	$fullscreen_font_opt          = get_option( 'skate_navbar_fullscreen_font', 'inherit' );
	$fullscreen_bg_opt            = get_option( 'skate_navbar_fullscreen_bg', '#17263a' );
	$fullscreen_bg_opacity_opt    = (int) get_option( 'skate_navbar_fullscreen_bg_opacity', 100 );
	$presets                      = json_decode( get_option( 'skate_navbar_presets', '[]' ), true ) ?: [];

	// Active tab from query string (default: main)
	$active_tab = isset( $_GET['tab'] ) && $_GET['tab'] === 'submenus' ? 'submenus' : 'main';

	// Get published pages for picker
	$picker_posts = get_posts( [
		'post_type'   => [ 'page', 'seo_page' ],
		'post_status' => 'publish',
		'numberposts' => 500,
		'orderby'     => 'title',
		'order'       => 'ASC',
	] );
	$picker_pages_json = wp_json_encode( array_map( function( $p ) {
		return [ 'id' => $p->ID, 'title' => $p->post_title, 'url' => get_permalink( $p->ID ) ];
	}, $picker_posts ) );

	// Page picker component builder
	$skate_url_picker = function( string $url_name, string $pid_name, string $url_val, int $pid_val, string $style = 'flex:1;min-width:140px;', bool $unresolved = false ): string {
		$page_title  = $pid_val ? get_the_title( $pid_val ) : '';
		$has_link    = $pid_val && $page_title;
		// Show the page permalink in the URL field when the page is linked but URL is empty
		if ( $has_link && $url_val === '' ) {
			$url_val = get_permalink( $pid_val ) ?: '';
		}
		ob_start();
		?>
		<div class="skate-url-wrap" style="<?= esc_attr( $style ) ?>">
			<input type="hidden" name="<?= esc_attr( $pid_name ) ?>" value="<?= $pid_val ?>" class="skate-pid">
			<div class="skate-pp-row">
				<input type="text" name="<?= esc_attr( $url_name ) ?>" value="<?= esc_attr( $url_val ) ?>" placeholder="URL" class="skate-url-input">
				<div class="skate-pp-wrap">
					<button type="button" class="button skate-pp-open">Page</button>
					<div class="skate-pp-panel" hidden>
						<input type="text" class="skate-pp-filter" placeholder="Search...">
						<div class="skate-pp-results"></div>
					</div>
				</div>
			</div>
			<div class="skate-pid-linked"<?= $has_link ? '' : ' hidden' ?>>
				<span class="skate-pid-icon">&#128196;</span>
				<span class="skate-pid-linked-title"><?= esc_html( $page_title ) ?></span>
				<button type="button" class="skate-pid-unlink" title="Remove link">&#x2715;</button>
			</div>
			<?php if ( $unresolved ) : ?>
			<div class="skate-url-unresolved">
				&#9888; Page not found — URL will be used as-is
			</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	};
	?>
	<div class="wrap skate-identity-wrap">
		<h1>Navigation</h1>

		<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?= __( 'Saved.', 'skate' ) ?></p></div>
		<?php endif; ?>

		<!-- Tabs -->
		<div class="skate-tune-tabs">
			<a href="?page=skate-navbar&tab=main"     class="skate-tune-tab<?= $active_tab === 'main'     ? ' is-active' : '' ?>">Navigation</a>
			<a href="?page=skate-navbar&tab=submenus" class="skate-tune-tab<?= $active_tab === 'submenus' ? ' is-active' : '' ?>">Submenus</a>
		</div>

		<form method="post" action="?page=skate-navbar&tab=<?= esc_attr( $active_tab ) ?>" id="skate-navbar-form">
			<?php wp_nonce_field( 'skate_save_navbar', 'skate_navbar_nonce' ); ?>
			<script>window.skatePages = <?= $picker_pages_json ?>;</script>

			<!-- ======================================================== -->
			<!-- TAB: Navigation                                           -->
			<!-- ======================================================== -->
			<div id="skate-tab-main"<?= $active_tab !== 'main' ? ' hidden' : '' ?>>
			<div class="skate-submenus-layout">
			<div class="skate-submenus-main">

				<!-- SECTION: Variant -->
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<h2 class="skate-tune-title">Style</h2>
					</div>
					<div class="skate-tune-row" style="align-items:flex-start;padding-top:16px;">
						<label class="skate-tune-label" style="padding-top:12px;">Variant</label>
						<div class="skate-tune-control">
							<div class="skate-variant-cards">
								<?php
								$variants = [
									'standard'    => 'Standard',
									'centered'    => 'Centered',
									'transparent' => 'Transparent',
									'compact'     => 'Kompakt',
									'dark'        => 'Dark',
								];
								foreach ( $variants as $val => $lbl ) : ?>
								<label class="skate-variant-card<?= $variant === $val ? ' is-active' : '' ?>">
									<input type="radio" name="skate_navbar_variant" value="<?= esc_attr( $val ) ?>"<?= checked( $variant, $val, false ) ?> hidden>
									<span class="skate-variant-preview skate-variant-preview--<?= esc_attr( $val ) ?>">
										<span class="pvp-bar">
											<span class="pvp-logo"></span>
											<span class="pvp-links">
												<span class="pvp-link"></span>
												<span class="pvp-link"></span>
												<span class="pvp-link"></span>
											</span>
											<span class="pvp-btn"></span>
										</span>
										<span class="pvp-content">
											<span class="pvp-line"></span>
											<span class="pvp-line pvp-line--short"></span>
											<span class="pvp-line"></span>
										</span>
									</span>
									<span class="skate-variant-name"><?= esc_html( $lbl ) ?></span>
								</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- SECTION: Logo -->
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<h2 class="skate-tune-title">Logo</h2>
					</div>
					<div class="skate-tune-row">
						<label class="skate-tune-label">Size Desktop (px)</label>
						<div class="skate-tune-control">
							<input type="number" name="skate_navbar_logo_size" value="<?= esc_attr( $logo_size ) ?>" min="40" max="400" style="width:80px;">
						</div>
					</div>
					<div class="skate-tune-row">
						<label class="skate-tune-label">Size Mobile (px)</label>
						<div class="skate-tune-control">
							<input type="number" name="skate_navbar_logo_size_mobile" value="<?= esc_attr( $logo_size_mobile ) ?>" min="30" max="200" style="width:80px;">
						</div>
					</div>
					<div class="skate-tune-row">
						<label class="skate-tune-label">Logo Light</label>
						<div class="skate-tune-control">
							<input type="text" name="skate_navbar_logo_light" id="skate_navbar_logo_light_url"
							       value="<?= esc_attr( $logo_light_url ) ?>" style="width:320px;max-width:100%;"
							       placeholder="https://...">
							<button type="button" class="button" id="skate-logo-light-btn">Choose image</button>
							<button type="button" class="button skate-media-remove" id="skate-logo-light-remove"<?= $logo_light_url ? '' : ' hidden' ?>>✕</button>
							<img id="skate-logo-light-preview" src="<?= esc_url( $logo_light_url ) ?>" style="height:36px;width:auto;border:1px solid #ddd;border-radius:4px;padding:2px;background:#999;<?= $logo_light_url ? '' : 'display:none;' ?>"><?php // phpcs:ignore ?>
						</div>
					</div>
				</div>

				<!-- SECTION: Links -->
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<h2 class="skate-tune-title">Links</h2>
					</div>
					<div id="skate-nav-links-container" class="skate-nb-repeater">
						<?php foreach ( $links as $li => $link ) : ?>
						<div class="skate-nav-link-row">
							<span class="skate-social-drag-handle" title="Reorder">⠿</span>
							<input type="text" name="skate_nav_link_label[]" value="<?= esc_attr( $link['label'] ?? '' ) ?>" placeholder="Label" style="width:150px;">
							<?= $skate_url_picker( 'skate_nav_link_url[]', 'skate_nav_link_page_id[]', $link['url'] ?? '', (int)($link['page_id'] ?? 0), 'flex:1;min-width:140px;', (bool)($link['unresolved'] ?? false) ) ?>
							<button type="button" class="button skate-nb-remove" data-cls="skate-nav-link-row">✕</button>
						</div>
						<?php endforeach; ?>
					</div>
					<div class="skate-nb-add-row">
						<button type="button" class="button" id="skate-add-link">+ Add link</button>
					</div>
				</div>

				<!-- SECTION: Buttons -->
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<h2 class="skate-tune-title">Buttons</h2>
					</div>
					<div id="skate-nav-buttons-container" class="skate-nb-repeater">
						<?php foreach ( $buttons as $btn ) : ?>
						<div class="skate-nav-btn-row">
							<span class="skate-social-drag-handle">⠿</span>
							<input type="text" name="skate_nav_btn_label[]" value="<?= esc_attr( $btn['label'] ?? '' ) ?>" placeholder="Label" style="width:150px;">
							<?= $skate_url_picker( 'skate_nav_btn_url[]', 'skate_nav_btn_page_id[]', $btn['url'] ?? '', (int)($btn['page_id'] ?? 0), 'flex:1;min-width:140px;', (bool)($btn['unresolved'] ?? false) ) ?>
							<select name="skate_nav_btn_style[]" style="width:130px;">
								<option value="filled"      <?= selected( $btn['style'] ?? '', 'filled',      false ) ?>>Filled Secondary</option>
								<option value="filled-dark" <?= selected( $btn['style'] ?? '', 'filled-dark', false ) ?>>Filled Primary</option>
								<option value="outline"     <?= selected( $btn['style'] ?? '', 'outline',     false ) ?>>Outline</option>
							</select>
							<button type="button" class="button skate-nb-remove" data-cls="skate-nav-btn-row">✕</button>
						</div>
						<?php endforeach; ?>
					</div>
					<div class="skate-nb-add-row">
						<button type="button" class="button" id="skate-add-btn">+ Add button</button>
					</div>
				</div>

			</div><!-- /.skate-submenus-main -->

			<!-- ── Right sidebar: fine tuning ── -->
			<div class="skate-submenus-sidebar">

				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<h2 class="skate-tune-title">Fine Tuning</h2>
					</div>

					<!-- Text transform -->
					<div class="skate-tune-row">
						<label class="skate-tune-label">Text</label>
						<div class="skate-tune-control">
							<div class="skate-icon-mode-cards">
								<label class="skate-icon-mode-card">
									<input type="radio" name="skate_navbar_link_text" value="inherit"<?= checked( $link_text_opt, 'inherit', false ) ?>>
									<span>Inherit</span>
								</label>
								<label class="skate-icon-mode-card">
									<input type="radio" name="skate_navbar_link_text" value="uppercase"<?= checked( $link_text_opt, 'uppercase', false ) ?>>
									<span>Mayus</span>
								</label>
							</div>
							<p class="description" style="margin-top:6px;">Link label text transform.</p>
						</div>
					</div>

					<!-- Font size -->
					<div class="skate-tune-row">
						<label class="skate-tune-label">Font size</label>
						<div class="skate-tune-control">
							<div class="skate-icon-mode-cards">
								<?php
								$fs_options = [
									'inherit' => 'Auto',
									'small'   => 'S',
									'medium'  => 'M',
									'large'   => 'L',
								];
								foreach ( $fs_options as $fs_val => $fs_label ) : ?>
								<label class="skate-icon-mode-card">
									<input type="radio" name="skate_navbar_link_font_size" value="<?= esc_attr( $fs_val ) ?>"<?= checked( $link_font_size_opt, $fs_val, false ) ?>>
									<span><?= esc_html( $fs_label ) ?></span>
								</label>
								<?php endforeach; ?>
							</div>
							<p class="description" style="margin-top:6px;">Applies to navbar link labels.</p>
						</div>
					</div>

				</div>

				<!-- Fullscreen Menu -->
				<div class="skate-tune-section">
					<div class="skate-tune-head" style="display:flex;align-items:center;justify-content:space-between;">
						<div>
							<h2 class="skate-tune-title">Fullscreen Menu</h2>
							<p class="skate-tune-desc">Hides nav links — burger always shows and opens a fullscreen overlay.</p>
						</div>
						<label class="skate-toggle" title="Enable / Disable">
							<input type="checkbox" name="skate_navbar_fullscreen_menu" value="1"<?= $fullscreen_menu_opt ? ' checked' : '' ?>>
							<span class="skate-toggle-track"></span>
						</label>
					</div>
					<div class="skate-tune-row" style="margin-top:12px;">
						<label class="skate-tune-label">Link font</label>
						<div class="skate-tune-control">
							<select name="skate_navbar_fullscreen_font" style="width:100%;">
								<option value="inherit"<?= selected( $fullscreen_font_opt, 'inherit', false ) ?>>— Theme default —</option>
								<?php
								$theme_fonts = wp_get_global_settings( [ 'typography', 'fontFamilies' ] )['theme'] ?? [];
								foreach ( $theme_fonts as $ff ) :
									$slug  = $ff['slug'] ?? '';
									$label = $ff['name'] ?? $slug;
									if ( ! $slug ) continue; ?>
								<option value="<?= esc_attr( $slug ) ?>"<?= selected( $fullscreen_font_opt, $slug, false ) ?>><?= esc_html( $label ) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="skate-tune-row" style="margin-top:12px;align-items:flex-start;">
						<label class="skate-tune-label" style="padding-top:4px;">Background</label>
						<div class="skate-tune-control">
							<div class="skate-fs-bg-group">
								<!-- Theme color swatches -->
								<div class="skate-fs-swatches">
									<?php foreach ( wp_get_global_settings( [ 'color', 'palette' ] )['theme'] ?? [] as $c ) :
										$chex = $c['color'] ?? '';
										if ( ! $chex ) continue; ?>
									<button type="button" class="skate-fs-swatch<?= ( strtolower( $fullscreen_bg_opt ) === strtolower( $chex ) ) ? ' is-active' : '' ?>" data-color="<?= esc_attr( $chex ) ?>" style="background:<?= esc_attr( $chex ) ?>;" title="<?= esc_attr( $c['name'] ?? '' ) ?>"></button>
									<?php endforeach; ?>
								</div>
								<!-- Color picker + hex -->
								<div class="skate-featured-bg-row" style="display:flex;align-items:center;gap:8px;">
									<input type="color" class="skate-featured-bg-color" name="skate_navbar_fullscreen_bg" value="<?= esc_attr( $fullscreen_bg_opt ?: '#17263a' ) ?>">
									<input type="text" class="skate-featured-bg-hex" value="<?= esc_attr( $fullscreen_bg_opt ?: '#17263a' ) ?>" maxlength="7" placeholder="#rrggbb">
								</div>
								<!-- Opacity -->
								<div class="skate-fs-opacity-row">
									<input type="range" name="skate_navbar_fullscreen_bg_opacity" min="0" max="100" value="<?= esc_attr( $fullscreen_bg_opacity_opt ) ?>">
									<span class="skate-fs-opacity-value"><?= esc_attr( $fullscreen_bg_opacity_opt ) ?>%</span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Save -->
				<div class="skate-sidebar-actions">
					<input type="submit" class="button-primary" value="Save" style="width:100%;">
				</div>

			</div><!-- /.skate-submenus-sidebar -->
			</div><!-- /.skate-submenus-layout -->

			</div><!-- /#skate-tab-main -->

			<!-- ======================================================== -->
			<!-- TAB: Submenus                                             -->
			<!-- ======================================================== -->
			<div id="skate-tab-submenus"<?= $active_tab !== 'submenus' ? ' hidden' : '' ?>>
			<div class="skate-submenus-layout">
			<div class="skate-submenus-main">

				<?php
				// Query featured-column patterns once (used for regular links and hamburger)
				$menu_patterns = get_posts( [
					'post_type'      => 'wp_block',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'orderby'        => 'title',
					'order'          => 'ASC',
					'tax_query'      => [ [
						'taxonomy' => 'wp_pattern_category',
						'field'    => 'slug',
						'terms'    => [ 'menu-featured-column' ],
					] ],
				] );
				?>

				<?php if ( empty( $links ) ) : ?>
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<p class="skate-tune-desc">No links configured yet. Add links in the "Navigation" tab first.</p>
					</div>
				</div>
				<?php else : ?>

				<?php foreach ( $links as $li => $link ) : ?>
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<h2 class="skate-tune-title"><?= esc_html( $link['label'] ?: '(Link ' . ( $li + 1 ) . ')' ) ?></h2>
						<p class="skate-tune-desc">Each column contains one or more groups. Each group has a title and links.</p>
					</div>
					<?php
					// Normalize: migrate old 'featured' key into unified columns array
					$all_cols = $link['columns'] ?? [];
					if ( ! empty( $link['featured']['post_id'] ) ) {
						$has_feat_col = ! empty( array_filter( $all_cols, fn( $c ) => ( $c['type'] ?? 'regular' ) === 'featured' ) );
						if ( ! $has_feat_col ) $all_cols[] = [ 'type' => 'featured', 'post_id' => (int) $link['featured']['post_id'] ];
					}

					// Separate ci/fci indices and build order array
					$ci = 0; $fci = 0;
					$order_arr   = [];
					$render_cols = [];
					foreach ( $all_cols as $col ) {
						if ( ( $col['type'] ?? 'regular' ) === 'featured' ) {
							$order_arr[]   = [ 'type' => 'featured', 'fci' => $fci ];
							$render_cols[] = [ 'type' => 'featured', 'fci' => $fci, 'post_id' => (int) ( $col['post_id'] ?? 0 ), 'bg_color' => $col['bg_color'] ?? '' ];
							$fci++;
						} else {
							$order_arr[]   = [ 'type' => 'regular', 'ci' => $ci ];
							$render_cols[] = [ 'type' => 'regular', 'ci' => $ci, 'col' => $col ];
							$ci++;
						}
					}
					$next_ci  = $ci;
					$next_fci = $fci;
					?>
					<input type="hidden" name="skate_nav_col_order[<?= $li ?>]" class="skate-col-order-input" value="<?= esc_attr( wp_json_encode( $order_arr ) ) ?>">
					<?php
					$chevron_svg = '<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 3.5L5 6.5L8 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
					$close_svg   = '<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 2L8 8M8 2L2 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
					?>
					<div class="skate-sub-col-wrap" id="skate-sub-col-wrap-<?= $li ?>" data-li="<?= $li ?>" data-next-ci="<?= $next_ci ?>" data-next-fci="<?= $next_fci ?>">
						<?php foreach ( $render_cols as $rcol ) :
							if ( $rcol['type'] === 'featured' ) :
								$fci_r          = $rcol['fci'];
								$feat_pid       = $rcol['post_id'];
								$feat_bg_color  = $rcol['bg_color'] ?? '';
								$eu_active      = $feat_pid ? admin_url( 'site-editor.php?postType=wp_block&postId=' . $feat_pid . '&canvas=edit' ) : '';
						?>
						<div class="skate-sub-col-card skate-featured-col-card is-collapsed" data-fci="<?= $fci_r ?>" draggable="true">
							<div class="skate-sub-col-card-head">
								<span class="skate-col-drag-handle" title="Drag to reorder">⠿</span>
								<span class="skate-sub-col-label">Featured</span>
								<button type="button" class="skate-col-collapse" title="Toggle"><?= $chevron_svg ?></button>
								<button type="button" class="skate-featured-col-remove" title="Remove"><?= $close_svg ?></button>
							</div>
							<div class="skate-featured-col-body">
								<?php if ( $menu_patterns ) : ?>
								<select name="skate_nav_featured_post_id[<?= $li ?>][<?= $fci_r ?>]" class="skate-featured-select">
									<option value="0">— None —</option>
									<?php foreach ( $menu_patterns as $pat_post ) :
										$eu = admin_url( 'site-editor.php?postType=wp_block&postId=' . $pat_post->ID . '&canvas=edit' );
									?>
									<option value="<?= $pat_post->ID ?>"
									        data-edit-url="<?= esc_url( $eu ) ?>"
									        <?= selected( $feat_pid, $pat_post->ID, false ) ?>><?= esc_html( $pat_post->post_title ) ?></option>
									<?php endforeach; ?>
								</select>
								<a href="<?= esc_url( $eu_active ?: '#' ) ?>"
								   target="_blank"
								   class="skate-featured-edit-link"
								   <?= $eu_active ? '' : 'hidden' ?>>Edit in Site Editor &#8599;</a>
								<?php else : ?>
								<span style="font-size:12px;color:#8c8f94;">No patterns in <em>Menu Featured Column</em> yet.</span>
								<?php endif; ?>
								<div class="skate-featured-bg-row">
									<label class="skate-featured-bg-label">BG Color</label>
									<input type="color" class="skate-featured-bg-color" name="skate_nav_featured_bg_color[<?= $li ?>][<?= $fci_r ?>]" value="<?= esc_attr( $feat_bg_color ?: '#F2F4F6' ) ?>">
									<input type="text" class="skate-featured-bg-hex" value="<?= esc_attr( $feat_bg_color ?: '#F2F4F6' ) ?>" maxlength="7" placeholder="#rrggbb">
								</div>
							</div>
						</div>
						<?php else :
							$ci_r    = $rcol['ci'];
							$col     = $rcol['col'];
							$next_gi = count( $col['groups'] ?? [] );
						?>
						<div class="skate-sub-col-card is-collapsed" data-ci="<?= $ci_r ?>" draggable="true">
							<div class="skate-sub-col-card-head">
								<span class="skate-col-drag-handle" title="Drag to reorder">⠿</span>
								<span class="skate-sub-col-label">Column <?= $ci_r + 1 ?></span>
								<button type="button" class="skate-col-collapse" title="Toggle"><?= $chevron_svg ?></button>
								<button type="button" class="skate-nb-remove" data-cls="skate-sub-col-card" title="Remove column"><?= $close_svg ?></button>
							</div>
							<div class="skate-sub-col-card-body">
								<div class="skate-sub-groups-wrap" id="skate-sub-groups-<?= $li ?>-<?= $ci_r ?>" data-li="<?= $li ?>" data-ci="<?= $ci_r ?>" data-next-gi="<?= $next_gi ?>">
									<?php foreach ( ( $col['groups'] ?? [] ) as $gi => $grp ) : ?>
									<div class="skate-sub-group-card">
										<div class="skate-sub-group-head">
											<input type="text"
												name="skate_nav_col_group_title[<?= $li ?>][<?= $ci_r ?>][<?= $gi ?>]"
												value="<?= esc_attr( $grp['title'] ?? '' ) ?>"
												placeholder="Group title">
											<button type="button" class="button skate-nb-remove" data-cls="skate-sub-group-card" title="Remove group">✕</button>
										</div>
										<div class="skate-sub-col-links" data-li="<?= $li ?>" data-ci="<?= $ci_r ?>" data-gi="<?= $gi ?>">
											<?php foreach ( ( $grp['links'] ?? [] ) as $cl ) : ?>
											<div class="skate-sub-link-row" draggable="true">
												<span class="skate-link-drag-handle" title="Drag to reorder">⠿</span>
												<input type="text"
													name="skate_nav_col_link_title[<?= $li ?>][<?= $ci_r ?>][<?= $gi ?>][]"
													value="<?= esc_attr( $cl['title'] ?? '' ) ?>"
													placeholder="Label" style="width:130px;">
												<?= $skate_url_picker(
													'skate_nav_col_link_url[' . $li . '][' . $ci_r . '][' . $gi . '][]',
													'skate_nav_col_link_pid[' . $li . '][' . $ci_r . '][' . $gi . '][]',
													$cl['url'] ?? '',
													(int) ( $cl['page_id'] ?? 0 )
												) ?>
												<input type="text"
													name="skate_nav_col_link_anchor[<?= $li ?>][<?= $ci_r ?>][<?= $gi ?>][]"
													value="<?= esc_attr( $cl['anchor'] ?? '' ) ?>"
													placeholder="#anchor" style="width:90px;" title="Anchor (e.g. section-id, without #)">
												<button type="button" class="button skate-nb-remove" data-cls="skate-sub-link-row">✕</button>
											</div>
											<?php endforeach; ?>
										</div>
										<div class="skate-sub-add-link-row">
											<button type="button" class="button skate-add-col-link" data-li="<?= $li ?>" data-ci="<?= $ci_r ?>" data-gi="<?= $gi ?>">+ Add link</button>
										</div>
									</div>
									<?php endforeach; ?>
								</div>
								<div class="skate-sub-add-group-row">
									<button type="button" class="button skate-add-group" data-li="<?= $li ?>" data-ci="<?= $ci_r ?>">+ Add group</button>
								</div>
							</div>
						</div>
						<?php endif; ?>
						<?php endforeach; ?>

						<template id="skate-feat-tpl-<?= $li ?>">
							<option value="0">— None —</option>
							<?php foreach ( $menu_patterns as $pat_post ) :
								$eu = admin_url( 'site-editor.php?postType=wp_block&postId=' . $pat_post->ID . '&canvas=edit' );
							?>
							<option value="<?= $pat_post->ID ?>" data-edit-url="<?= esc_url( $eu ) ?>"><?= esc_html( $pat_post->post_title ) ?></option>
							<?php endforeach; ?>
						</template>
					</div>
					<div style="padding:0 16px 16px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
						<button type="button" class="button skate-add-column" data-li="<?= $li ?>">+ Add column</button>
						<button type="button" class="button skate-add-featured-col" data-li="<?= $li ?>">+ Add featured column</button>
						<span style="font-size:12px;color:#8c8f94;">Featured columns use patterns from the <strong>Menu Featured Column</strong> category.</span>
					</div>
				</div>
				<?php endforeach; ?>

				<?php endif; ?>

			</div><!-- /.skate-submenus-main -->

			<!-- ── Right sidebar: icon settings ── -->
			<div class="skate-submenus-sidebar">
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<h2 class="skate-tune-title">Fine Tuning</h2>
					</div>
					<!-- Submenu font size -->
					<div class="skate-tune-row">
						<label class="skate-tune-label">Font size</label>
						<div class="skate-tune-control">
							<div class="skate-icon-mode-cards">
								<?php
								$sfs_options = [
									'small'  => 'S',
									'medium' => 'M',
									'large'  => 'L',
								];
								foreach ( $sfs_options as $sfs_val => $sfs_label ) : ?>
								<label class="skate-icon-mode-card">
									<input type="radio" name="skate_navbar_submenu_font_size" value="<?= esc_attr( $sfs_val ) ?>"<?= checked( $submenu_font_size_opt, $sfs_val, false ) ?>>
									<span><?= esc_html( $sfs_label ) ?></span>
								</label>
								<?php endforeach; ?>
							</div>
							<p class="description" style="margin-top:6px;">Applies to group titles and links.</p>
						</div>
					</div>

					<!-- Text transform -->
					<div class="skate-tune-row">
						<label class="skate-tune-label">Text</label>
						<div class="skate-tune-control">
							<div class="skate-icon-mode-cards">
								<label class="skate-icon-mode-card">
									<input type="radio" name="skate_navbar_submenu_text" value="inherit"<?= checked( $submenu_text_opt, 'inherit', false ) ?>>
									<span>Inherit</span>
								</label>
								<label class="skate-icon-mode-card">
									<input type="radio" name="skate_navbar_submenu_text" value="uppercase"<?= checked( $submenu_text_opt, 'uppercase', false ) ?>>
									<span>Mayus</span>
								</label>
							</div>
							<p class="description" style="margin-top:6px;">Text transform for titles and links.</p>
						</div>
					</div>

					<!-- Row gap -->
					<div class="skate-tune-row">
						<label class="skate-tune-label">Row gap</label>
						<div class="skate-tune-control">
							<div class="skate-icon-mode-cards">
								<label class="skate-icon-mode-card">
									<input type="radio" name="skate_navbar_submenu_gap" value="s"<?= checked( $submenu_gap_opt, 's', false ) ?>>
									<span>S</span>
								</label>
								<label class="skate-icon-mode-card">
									<input type="radio" name="skate_navbar_submenu_gap" value="m"<?= checked( $submenu_gap_opt, 'm', false ) ?>>
									<span>M</span>
								</label>
								<label class="skate-icon-mode-card">
									<input type="radio" name="skate_navbar_submenu_gap" value="l"<?= checked( $submenu_gap_opt, 'l', false ) ?>>
									<span>L</span>
								</label>
							</div>
							<p class="description" style="margin-top:6px;">Vertical spacing between links.</p>
						</div>
					</div>
					<p class="skate-tune-hint" style="margin-top:4px;padding:10px 14px 14px;">To apply Font size to a featured column heading, add the CSS class <code>skate-submenu-featured-title</code> to it in the Site Editor (Block → Advanced → Additional CSS class).</p>
				</div>

				<!-- Close Button -->
				<div class="skate-tune-section">
					<div class="skate-tune-head" style="display:flex;align-items:center;justify-content:space-between;">
						<div>
							<h2 class="skate-tune-title">Close Button</h2>
							<p class="skate-tune-desc">Show an × button inside the submenu panel.</p>
						</div>
						<label class="skate-toggle" title="Enable / Disable">
							<input type="checkbox" name="skate_navbar_submenu_close" value="1"<?= get_option( 'skate_navbar_submenu_close' ) ? ' checked' : '' ?>>
							<span class="skate-toggle-track"></span>
						</label>
					</div>
				</div>
				<!-- Column Backgrounds -->
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<h2 class="skate-tune-title">Column Backgrounds</h2>
						<p class="skate-tune-desc">Subtle tint per column — first is most opaque.</p>
					</div>
					<div style="padding:12px 16px 16px;">
						<div class="skate-icon-mode-cards">
							<?php
							$col_bg_options = [
								'none'      => [ 'label' => 'None',      'desc' => 'Transparent' ],
								'grey'      => [ 'label' => 'Greys',     'desc' => 'Neutral tones' ],
								'primary'   => [ 'label' => 'Primary',   'desc' => 'Main color' ],
								'secondary' => [ 'label' => 'Secondary', 'desc' => 'Brand accent' ],
							];
							foreach ( $col_bg_options as $bg_key => $bg ) : ?>
							<label class="skate-icon-mode-card">
								<input type="radio" name="skate_navbar_col_bg_preset" value="<?= $bg_key ?>"<?= checked( $col_bg_preset_opt, $bg_key, false ) ?>>
								<span><?= esc_html( $bg['label'] ) ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<!-- Presets -->
				<div class="skate-tune-section">
					<div class="skate-tune-head">
						<h2 class="skate-tune-title">Presets</h2>
						<p class="skate-tune-desc">Save and restore named configurations.</p>
					</div>
					<div class="skate-presets-body">
						<div class="skate-preset-save-row">
							<input type="text" name="skate_navbar_preset_name" class="skate-preset-name-input" placeholder="Preset name…">
							<button type="submit" name="skate_navbar_save_named_preset" value="1" class="button skate-preset-save-btn">Save</button>
						</div>
						<input type="hidden" name="skate_navbar_preset_id" value="">
						<input type="hidden" name="skate_navbar_import_json" id="skate-import-json-data" value="">
						<?php if ( $presets ) : ?>
						<ul class="skate-preset-list">
							<?php foreach ( array_reverse( $presets ) as $preset ) :
								$pid   = esc_js( $preset['id'] );
								$pname = esc_js( $preset['name'] );
							?>
							<li class="skate-preset-item">
								<span class="skate-preset-item-name"><?= esc_html( $preset['name'] ) ?></span>
								<span class="skate-preset-item-date"><?= esc_html( wp_date( 'j M Y', (int) $preset['saved_at'] ) ) ?></span>
								<button type="submit" name="skate_navbar_load_named_preset" value="1" class="skate-preset-action skate-preset-load"
									onclick="document.querySelector('[name=skate_navbar_preset_id]').value='<?= $pid ?>'; return confirm('Load «<?= $pname ?>»? This overwrites all current settings.');"
									title="Load">
									<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.5 1v7M2 5.5l3.5 3.5 3.5-3.5M1 10h9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
								</button>
								<button type="submit" name="skate_navbar_delete_preset" value="1" class="skate-preset-action skate-preset-delete"
									onclick="document.querySelector('[name=skate_navbar_preset_id]').value='<?= $pid ?>'; return confirm('Delete «<?= $pname ?>»?');"
									title="Delete">
									<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 2L8 8M8 2L2 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
								</button>
							</li>
							<?php endforeach; ?>
						</ul>
						<?php else : ?>
						<p class="skate-preset-empty">No presets saved yet.</p>
						<?php endif; ?>
						<!-- Export / Import -->
						<div class="skate-preset-transfer">
							<a href="<?= esc_url( wp_nonce_url( admin_url( 'admin.php?page=skate-navbar&skate_export_presets=1' ), 'skate_export_presets' ) ) ?>"
								class="button skate-preset-transfer-btn"
								<?= empty( $presets ) ? 'disabled aria-disabled="true"' : '' ?>>
								<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.5 1v7M2 5.5l3.5 3.5 3.5-3.5M1 10h9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
								Export
							</a>
							<label class="button skate-preset-transfer-btn skate-preset-import-label" title="Import presets from JSON file">
								<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.5 10V3M2 5.5L5.5 2l3.5 3.5M1 1h9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
								Import
								<input type="file" id="skate-import-file-picker" accept=".json" hidden>
							</label>
							<button type="submit" name="skate_navbar_import_presets" value="1" id="skate-import-submit-btn" class="button button-primary skate-preset-import-submit" hidden>
								Confirm import
							</button>
						</div>
					</div>
				</div>
				<!-- Save (bottom) -->
				<div class="skate-sidebar-actions">
					<input type="submit" class="button-primary" value="Save" style="width:100%;">
				</div>
			</div><!-- /.skate-submenus-sidebar -->

			</div><!-- /.skate-submenus-layout -->
			</div><!-- /#skate-tab-submenus -->

		</form>
	</div>

	<style>
	/* ── Base layout (mirrors site-identity.php) ── */
	.skate-identity-wrap { max-width: none; }
	/* ── Submenus 70/30 layout ── */
	.skate-submenus-layout {
		display: flex;
		align-items: flex-start;
		gap: 20px;
		padding: 0;
	}
	.skate-submenus-main { flex: 7; min-width: 0; }
	.skate-submenus-sidebar { flex: 3; min-width: 220px; position: sticky; top: 32px; }
	/* ── Icon mode card picker ── */
	.skate-icon-mode-cards { display: flex; background: #f0f0f1; border-radius: 20px; padding: 3px; gap: 0; }
	.skate-icon-mode-card { flex: 1; display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer; }
	.skate-icon-mode-card input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
	.skate-icon-mode-card span { display: block; padding: 4px 6px; font-size: 12px; font-weight: 600; color: #8c8f94; text-align: center; width: 100%; border-radius: 16px; transition: color .15s, background .15s; user-select: none; white-space: nowrap; }
	.skate-icon-mode-card input:checked + span { background: var(--skate-accent); color: #fff; box-shadow: 0 1px 4px var(--skate-accent-glow); }
	.skate-tab-main-actions {
		display: flex;
		gap: 8px;
		padding: 16px 0 8px;
	}
	/* ── Presets panel ── */
	.skate-presets-body { padding: 10px 16px 14px; display: flex; flex-direction: column; gap: 8px; }
	.skate-preset-save-row { display: flex; gap: 6px; }
	.skate-preset-name-input { flex: 1; min-width: 0; font-size: 12px !important; height: 30px !important; }
	.skate-preset-save-btn { flex-shrink: 0; height: 30px; line-height: 28px; padding: 0 10px; font-size: 12px; }
	.skate-preset-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px; }
	.skate-preset-item { display: flex; align-items: center; gap: 6px; padding: 5px 8px; background: #f0f0f1; border-radius: 6px; }
	.skate-preset-item-name { font-size: 12px; font-weight: 600; color: #1d2327; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
	.skate-preset-item-date { font-size: 11px; color: #8c8f94; white-space: nowrap; flex-shrink: 0; }
	.skate-preset-action { background: none; border: none; cursor: pointer; color: #8c8f94; padding: 0; display: flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 4px; flex-shrink: 0; transition: background .15s, color .15s; }
	.skate-preset-load:hover { background: rgba(0,0,0,.07); color: #1d2327; }
	.skate-preset-delete:hover { background: rgba(179,45,46,.1); color: #b32d2e; }
	.skate-preset-empty { font-size: 12px; color: #8c8f94; margin: 0; text-align: center; padding: 6px 0; }
	.skate-preset-transfer { display: flex; gap: 6px; flex-wrap: wrap; }
	.skate-preset-transfer-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; height: 28px; line-height: 26px; padding: 0 10px; }
	.skate-preset-transfer-btn[disabled] { opacity: .4; pointer-events: none; }
	.skate-preset-import-label { cursor: pointer; }
	.skate-preset-import-submit { flex-shrink: 0; }
	.skate-sidebar-actions {
		display: flex;
		flex-direction: column;
		gap: 8px;
		margin-top: 12px;
	}
	.skate-tune-section {
		background: #fff;
		border: 1px solid #c3c4c7;
		border-radius: 10px;
		overflow: hidden;
		margin-bottom: 20px;
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
	.skate-tune-desc { margin: 0; font-size: 12px; color: #8c8f94; }
	.skate-tune-row {
		display: grid;
		grid-template-columns: 148px 1fr;
		gap: 8px 20px;
		align-items: center;
		padding: 13px 24px;
		border-top: 1px solid #f4f4f5;
	}
	.skate-tune-label { font-size: 13px; font-weight: 500; color: #1d2327; padding-top: 2px; }
	.skate-tune-control { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

	/* ── Variant cards ── */
	.skate-variant-cards { display: flex; gap: 12px; flex-wrap: wrap; padding: 4px 0; }
	.skate-variant-card {
		display: flex; flex-direction: column; align-items: center; gap: 8px;
		background: #fff; border: 2px solid #dcdcde; border-radius: 10px;
		padding: 10px 10px 8px; cursor: pointer; width: 88px;
		transition: border-color .15s, box-shadow .15s;
	}
	.skate-variant-card:hover { border-color: var(--skate-accent); }
	.skate-variant-card.is-active { border-color: var(--skate-accent); box-shadow: 0 0 0 3px var(--skate-accent-glow); }
	.skate-variant-preview {
		width: 68px; height: 50px; border-radius: 5px; overflow: hidden;
		background: #f2f4f6; display: flex; flex-direction: column; flex-shrink: 0;
	}
	.skate-variant-name { font-size: 11px; font-weight: 600; color: #1d2327; white-space: nowrap; }
	/* mini bar */
	.pvp-bar {
		display: flex; align-items: center; gap: 3px; padding: 0 5px;
		height: 13px; flex-shrink: 0;
	}
	.pvp-logo  { width: 11px; height: 5px; border-radius: 1px; background: #17263a; flex-shrink: 0; }
	.pvp-links { display: flex; gap: 2px; flex: 1; align-items: center; }
	.pvp-link  { height: 3px; background: #17263a; border-radius: 1px; opacity: .35; }
	.pvp-link:nth-child(1) { width: 9px; }
	.pvp-link:nth-child(2) { width: 7px; }
	.pvp-link:nth-child(3) { width: 9px; }
	.pvp-btn   { width: 11px; height: 5px; border-radius: 1px; background: #d6b36d; flex-shrink: 0; }
	/* mini content */
	.pvp-content { flex: 1; padding: 5px 5px; display: flex; flex-direction: column; gap: 3px; justify-content: center; }
	.pvp-line  { height: 3px; background: rgba(0,0,0,.1); border-radius: 1px; }
	.pvp-line--short { width: 55%; }
	/* Standard */
	.skate-variant-preview--standard .pvp-bar { background: #fff; box-shadow: 0 1px 0 rgba(0,0,0,.08); }
	/* Transparent */
	.skate-variant-preview--transparent { background: linear-gradient(140deg, #17263a 0%, #2e5073 100%); }
	.skate-variant-preview--transparent .pvp-bar { background: transparent; }
	.skate-variant-preview--transparent .pvp-logo { background: #fff; }
	.skate-variant-preview--transparent .pvp-link { background: #fff; opacity: .55; }
	.skate-variant-preview--transparent .pvp-btn  { background: #d6b36d; }
	.skate-variant-preview--transparent .pvp-line { background: rgba(255,255,255,.18); }
	/* Compact */
	.skate-variant-preview--compact .pvp-bar { background: #fff; height: 9px; box-shadow: 0 1px 0 rgba(0,0,0,.08); }
	.skate-variant-preview--compact .pvp-logo { height: 4px; }
	.skate-variant-preview--compact .pvp-btn  { height: 4px; }
	.skate-variant-preview--compact .pvp-link { height: 2px; }
	/* Centered */
	.skate-variant-preview--centered .pvp-bar { background: #fff; box-shadow: 0 1px 0 rgba(0,0,0,.08); justify-content: space-between; }
	.skate-variant-preview--centered .pvp-bar .pvp-links { flex: 0 0 auto; }
	.skate-variant-preview--centered .pvp-bar .pvp-logo  { position: absolute; left: 50%; transform: translateX(-50%); }
	.skate-variant-preview--centered .pvp-bar { position: relative; }
	/* Dark */
	.skate-variant-preview--dark .pvp-bar { background: #17263a; }
	.skate-variant-preview--dark .pvp-logo { background: #fff; }
	.skate-variant-preview--dark .pvp-link { background: #fff; opacity: .45; }
	.skate-variant-preview--dark .pvp-btn  { background: #d6b36d; }

	/* ── Tabs ── */
	.skate-tune-tabs {
		display: flex;
		gap: 0;
		margin-bottom: 20px;
		border-bottom: 2px solid #dcdcde;
	}
	.skate-tune-tab {
		display: inline-flex;
		align-items: center;
		padding: 10px 20px;
		text-decoration: none;
		color: #50575e;
		font-weight: 500;
		font-size: 14px;
		border-bottom: 2px solid transparent;
		margin-bottom: -2px;
		transition: color .15s, border-color .15s;
	}
	.skate-tune-tab.is-active { color: var(--skate-accent); border-bottom-color: var(--skate-accent); font-weight: 600; }
	.skate-tune-tab:hover:not(.is-active) { color: #1d2327; }

	/* ── Drag handle ── */
	.skate-social-drag-handle {
		cursor: grab; color: #c5c8cc; font-size: 15px; flex-shrink: 0;
		padding: 0 2px; border-radius: 3px; user-select: none; line-height: 1;
	}
	.skate-social-drag-handle:hover { color: #50575e; }

	/* ── Repeater containers ── */
	.skate-nb-repeater { padding: 16px 24px 8px; }
	.skate-nb-add-row  { padding: 0 24px 16px; }

	/* ── Link / Button / Icon rows ── */
	.skate-nav-link-row,
	.skate-nav-btn-row,
	.skate-nav-icon-row {
		background: #f9f9f9;
		border: 1px solid #e0e0e0;
		border-radius: 6px;
		padding: 10px 14px;
		margin-bottom: 8px;
	}
	.skate-nav-link-row,
	.skate-nav-btn-row,
	.skate-nav-icon-row {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}

	/* ── Submenu rows ── */
	.skate-nav-sub-row {
		display: flex;
		align-items: flex-start;
		gap: 8px;
		padding: 10px 12px;
		background: #f9f9f9;
		border: 1px solid #e0e0e0;
		border-radius: 6px;
		margin-bottom: 8px;
		flex-wrap: wrap;
	}

	/* ── SVG field ── */
	.skate-nb-svg-wrap {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 4px;
	}
	.skate-nb-svg-wrap textarea {
		width: 100px;
		font-family: monospace;
		font-size: 10px;
		resize: vertical;
		border-radius: 3px;
	}
	.skate-nb-svg-preview {
		width: 36px;
		height: 36px;
		border: 1px solid #ddd;
		border-radius: 4px;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #fff;
	}
	.skate-nb-svg-preview svg { width: 26px; height: 26px; }

	/* ── Remove button ── */
	.skate-nb-remove.button {
		flex-shrink: 0;
		color: #b32d2e;
		border-color: transparent;
		background: none;
		box-shadow: none;
		padding: 0 6px;
	}
	.skate-nb-remove.button:hover { background: #fdf0f0; border-color: #b32d2e; }

	/* ── Page picker ── */
	.skate-url-wrap { display: flex; flex-direction: column; gap: 3px; }
	/* Toggle switch */
	.skate-toggle { display:flex; align-items:center; gap:6px; cursor:pointer; flex-shrink:0; }
	.skate-toggle input { position:absolute; opacity:0; width:0; height:0; }
	.skate-toggle-track {
		display:inline-block; width:36px; height:20px; border-radius:10px;
		background:#ccc; transition:background .2s; position:relative;
	}
	.skate-toggle-track::after {
		content:""; position:absolute; top:3px; left:3px;
		width:14px; height:14px; border-radius:50%; background:#fff;
		transition:transform .2s;
	}
	.skate-toggle input:checked + .skate-toggle-track { background:var(--skate-accent); }
	.skate-toggle input:checked + .skate-toggle-track::after { transform:translateX(16px); }
	.skate-url-unresolved {
		display: flex; align-items: center; gap: 5px;
		font-size: 11px; padding: 3px 8px; border-radius: 3px;
		background: #fff0f0; color: #c0392b; border-left: 3px solid #c0392b;
	}
	.skate-pp-row   { display: flex; align-items: center; gap: 4px; }
	.skate-url-input { flex: 1; }
	.skate-pp-wrap  { position: relative; flex-shrink: 0; }
	.skate-pp-open.button { padding: 0 8px; height: 28px; font-size: 12px; font-weight: 500; color: #50575e; }
	.skate-pp-panel {
		position: fixed; z-index: 99999;
		background: #fff; border: 1px solid #c3c4c7; border-radius: 6px;
		box-shadow: 0 4px 20px rgba(0,0,0,.14); width: 260px; padding: 8px;
	}
	.skate-pp-filter { width: 100%; box-sizing: border-box; margin-bottom: 6px; }
	.skate-pp-results { max-height: 180px; overflow-y: auto; }
	.skate-pp-result {
		padding: 5px 8px; cursor: pointer; border-radius: 4px;
		font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
		color: #1d2327;
	}
	.skate-pp-result:hover { background: #f0f4ff; color: #1d6ae5; }
	.skate-pp-empty { padding: 5px 8px; font-size: 12px; color: #8c8f94; font-style: italic; }
	.skate-pid-linked {
		display: flex; align-items: center; gap: 4px;
		font-size: 11px; color: #0a5c27; background: #edfaf2;
		border: 1px solid #b2dfc3; border-radius: 4px; padding: 2px 6px;
	}
	.skate-pid-linked[hidden] { display: none; }
	.skate-pid-icon { font-size: 11px; }
	.skate-pid-linked-title { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; }
	.skate-pid-unlink { border: none; background: none; cursor: pointer; color: #555; padding: 0 2px; font-size: 12px; line-height: 1; }
	.skate-pid-unlink:hover { color: #b32d2e; }

	/* ── Submenu column/group editor ── */
	.skate-sub-col-wrap {
		display: flex;
		flex-direction: column;
		gap: 6px;
		padding: 16px;
	}
	.skate-sub-col-card {
		background: #f0f0f1;
		border-radius: 8px;
		display: flex;
		flex-direction: column;
	}
	.skate-sub-col-card-head {
		display: flex;
		align-items: center;
		gap: 6px;
		padding: 8px 10px;
		border-radius: 8px;
		cursor: pointer;
		user-select: none;
	}
	.skate-sub-col-card:not(.is-collapsed) .skate-sub-col-card-head {
		border-bottom-left-radius: 0;
		border-bottom-right-radius: 0;
		border-bottom: 1px solid rgba(0,0,0,.08);
	}
	.skate-sub-col-card-body {
		padding: 10px;
		display: flex;
		flex-direction: column;
		gap: 8px;
	}
	/* Collapsed state */
	.skate-sub-col-card.is-collapsed .skate-sub-col-card-body,
	.skate-sub-col-card.is-collapsed .skate-featured-col-body { display: none; }
	/* Chevron toggle */
	.skate-col-collapse {
		background: none;
		border: none;
		cursor: pointer;
		color: #8c8f94;
		font-size: 14px;
		padding: 0;
		line-height: 1;
		margin-left: auto;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 20px;
		height: 20px;
		border-radius: 4px;
		transition: background .15s;
		flex-shrink: 0;
	}
	.skate-col-collapse:hover { background: rgba(0,0,0,.07); color: #1d2327; }
	.skate-col-collapse svg { transition: transform .18s; }
	.skate-sub-col-card.is-collapsed .skate-col-collapse svg { transform: rotate(-90deg); }
	/* Remove button */
	.skate-sub-col-card-head .skate-nb-remove,
	.skate-sub-col-card-head .skate-featured-col-remove {
		background: none;
		border: none;
		cursor: pointer;
		color: #8c8f94;
		font-size: 15px;
		padding: 0;
		line-height: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 20px;
		height: 20px;
		border-radius: 4px;
		transition: background .15s, color .15s;
		flex-shrink: 0;
	}
	.skate-sub-col-card-head .skate-nb-remove:hover,
	.skate-sub-col-card-head .skate-featured-col-remove:hover { background: rgba(179,45,46,.1); color: #b32d2e; }
	.skate-sub-col-label {
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: .07em;
		color: #50575e;
		flex: 1;
	}
	.skate-sub-groups-wrap {
		display: flex;
		flex-direction: column;
		gap: 8px;
	}
	.skate-sub-group-card {
		background: #fff;
		border: 1px solid #e0e0e0;
		border-radius: 6px;
		padding: 10px;
		display: flex;
		flex-direction: column;
		gap: 6px;
	}
	.skate-sub-group-head {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.skate-sub-group-head > input[type="text"] {
		flex: 1;
		font-weight: 600;
		box-sizing: border-box;
	}
	.skate-sub-add-group-row,
	.skate-sub-add-link-row {
		padding-top: 2px;
	}

	/* ── Column drag & drop ── */
	.skate-col-drag-handle {
		cursor: grab;
		color: #bbb;
		font-size: 15px;
		padding-right: 6px;
		user-select: none;
		flex-shrink: 0;
	}
	.skate-col-drag-handle:active { cursor: grabbing; }
	.skate-sub-col-card.is-dragging { opacity: .35; }
	.skate-sub-col-card.drag-over { outline: 2px dashed var(--skate-accent); outline-offset: -2px; }

	/* ── Featured column card ── */
	.skate-featured-col-card { background: var(--skate-accent-bg); }
	.skate-featured-col-card .skate-sub-col-card-head { background: transparent; }
	.skate-featured-col-body {
		display: flex;
		flex-direction: column;
		gap: 8px;
		padding: 12px;
	}
	.skate-featured-col-body select { width: 100%; }
	.skate-featured-edit-link {
		font-size: 11px;
		color: #2271b1;
		text-decoration: none;
	}
	.skate-featured-edit-link:hover { text-decoration: underline; }
	.skate-featured-bg-row {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-top: 4px;
	}
	.skate-featured-bg-label {
		font-size: 11px;
		color: #50575e;
		white-space: nowrap;
		min-width: 54px;
	}
	.skate-featured-bg-color {
		width: 32px;
		height: 28px;
		padding: 2px;
		border: 1px solid #dcdcde;
		border-radius: 4px;
		cursor: pointer;
		flex-shrink: 0;
	}
	.skate-featured-bg-hex {
		width: 80px;
		font-size: 12px;
		font-family: monospace;
		padding: 4px 6px;
		border: 1px solid #dcdcde;
		border-radius: 4px;
	}
	.skate-fs-swatches {
		display: flex;
		flex-wrap: wrap;
		gap: 5px;
		margin-bottom: 8px;
	}
	.skate-fs-swatch {
		width: 22px;
		height: 22px;
		border-radius: 50%;
		border: 2px solid transparent;
		cursor: pointer;
		padding: 0;
		outline-offset: 2px;
		transition: transform .15s;
		box-shadow: 0 0 0 1px rgba(0,0,0,.18);
	}
	.skate-fs-swatch:hover { transform: scale(1.2); }
	.skate-fs-swatch.is-active { border-color: #2271b1; }
	.skate-fs-opacity-row {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-top: 8px;
	}
	.skate-fs-opacity-row input[type="range"] { flex: 1; }
	.skate-fs-opacity-value { font-size: 12px; min-width: 32px; text-align: right; color: #50575e; }
	.skate-featured-col-remove {
		background: none;
		border: none;
		cursor: pointer;
		color: #cc1818;
		font-size: 14px;
		padding: 0 2px;
		line-height: 1;
	}
	.skate-sub-link-row {
		display: flex;
		align-items: center;
		gap: 6px;
		background: #f9f9f9;
		border: 1px solid #e8e8e8;
		border-radius: 4px;
		padding: 4px 6px;
		flex-wrap: wrap;
	}
	.skate-link-drag-handle {
		cursor: grab;
		color: #bbb;
		font-size: 13px;
		user-select: none;
		flex-shrink: 0;
		padding: 0 2px;
	}
	.skate-link-drag-handle:active { cursor: grabbing; }
	.skate-sub-link-row.is-dragging { opacity: .35; }
	.skate-sub-link-row.drag-over { outline: 2px dashed var(--skate-accent); outline-offset: -2px; border-radius: 4px; }
	.skate-sub-link-icon-wrap {
		flex-shrink: 0;
		width: 52px;
	}
	.skate-sub-link-icon-wrap .skate-nb-svg-preview {
		width: 24px;
		height: 24px;
	}
	.skate-sub-link-icon-wrap textarea {
		width: 100%;
		font-size: 10px;
		min-height: 36px;
	}
	</style>

	<script>
	(function () {
		// Variant cards: toggle is-active on click
		document.querySelectorAll('.skate-variant-card input[type="radio"]').forEach(function (radio) {
			radio.addEventListener('change', function () {
				var group = radio.closest('.skate-variant-cards');
				if ( ! group ) return;
				group.querySelectorAll('.skate-variant-card').forEach(function (card) { card.classList.remove('is-active'); });
				radio.closest('.skate-variant-card').classList.add('is-active');
			});
		});

		// Update all submenu parent indices before form submit (both tabs)
		document.getElementById('skate-navbar-form').addEventListener('submit', function () {
			// From links tab: no sub-containers anymore — handled via submenus tab hidden inputs directly
			// From submenus tab: re-index by group
			document.querySelectorAll('.skate-nav-sub-group').forEach(function (group) {
				const li = group.dataset.linkIndex;
				group.querySelectorAll('input[name="skate_nav_sub_parent[]"]').forEach(function (inp) {
					inp.value = li;
				});
			});
		});

		// Remove row
		document.addEventListener('click', function (e) {
			const btn = e.target.closest('.skate-nb-remove');
			if (!btn) return;
			const el = btn.closest('.' + btn.dataset.cls);
			if (!el) return;
			if (btn.dataset.cls === 'skate-sub-col-card') {
				const cwrap = el.closest('.skate-sub-col-wrap');
				el.remove();
				if (cwrap) skateUpdateColOrder(cwrap);
			} else {
				el.remove();
			}
		});

		// Live SVG preview on textarea change
		document.addEventListener('input', function (e) {
			const ta = e.target.closest('textarea[name="skate_nav_sub_svg[]"], textarea[name="skate_nav_icon_svg[]"], textarea[name="skate_nav_link_icon_svg[]"], textarea[name="skate_nav_hbg_sub_svg[]"]');
			if (!ta) return;
			const preview = ta.closest('.skate-nb-svg-wrap').querySelector('.skate-nb-svg-preview');
			if (preview) preview.innerHTML = ta.value;
		});

		// ── Page picker ──────────────────────────────────────────────────────────
		var skatePages = window.skatePages || [];

		function skateInitPicker(wrap) {
			var pidInput  = wrap.querySelector('.skate-pid');
			var urlInput  = wrap.querySelector('.skate-url-input');
			var openBtn   = wrap.querySelector('.skate-pp-open');
			var panel     = wrap.querySelector('.skate-pp-panel');
			var filter    = wrap.querySelector('.skate-pp-filter');
			var resultsCt = wrap.querySelector('.skate-pp-results');
			var linked    = wrap.querySelector('.skate-pid-linked');
			var titleEl   = wrap.querySelector('.skate-pid-linked-title');
			var unlinkBtn = wrap.querySelector('.skate-pid-unlink');

			if (!openBtn || !panel) return;

			function renderResults(q) {
				var list = q
					? skatePages.filter(function(p) { return p.title.toLowerCase().indexOf(q.toLowerCase()) !== -1; })
					: skatePages;
				if (!list.length) {
					resultsCt.innerHTML = '<div class="skate-pp-empty">No results</div>';
					return;
				}
				resultsCt.innerHTML = list.slice(0, 40).map(function(p) {
					return '<div class="skate-pp-result" data-id="' + p.id +
						   '" data-url="' + p.url.replace(/"/g,'&quot;') +
						   '" data-title="' + p.title.replace(/"/g,'&quot;') + '">' +
						   p.title + '</div>';
				}).join('');
			}

			openBtn.addEventListener('click', function(e) {
				e.stopPropagation();
				var wasHidden = panel.hidden;
				// Close all other open panels first
				document.querySelectorAll('.skate-pp-panel').forEach(function(p) { p.hidden = true; });
				panel.hidden = !wasHidden;
				if (!panel.hidden) {
					// Position below the button, fixed to viewport
					var rect = openBtn.getBoundingClientRect();
					var panelW = 260;
					panel.style.top  = (rect.bottom + 4) + 'px';
					panel.style.left = Math.max(4, rect.right - panelW) + 'px';
					renderResults(''); filter.value = ''; filter.focus();
				}
			});

			filter.addEventListener('input', function() { renderResults(filter.value); });

			resultsCt.addEventListener('click', function(e) {
				var item = e.target.closest('.skate-pp-result');
				if (!item) return;
				pidInput.value  = item.dataset.id;
				urlInput.value  = item.dataset.url;
				if (titleEl)  titleEl.textContent  = item.dataset.title;
				if (linked)   linked.hidden        = false;
				var unresolved = wrap.querySelector('.skate-url-unresolved');
				if (unresolved) unresolved.hidden = true;
				// Auto-fill label input if empty
				var row = wrap.closest('.skate-sub-link-row');
				if (row) {
					var labelInput = row.querySelector('input[type="text"][name*="_link_title"]');
					if (labelInput && !labelInput.value.trim()) {
						labelInput.value = item.dataset.title;
					}
				}
				panel.hidden = true;
			});

			if (unlinkBtn) {
				unlinkBtn.addEventListener('click', function() {
					pidInput.value  = '0';
					if (linked) linked.hidden = true;
				});
			}
		}

		document.addEventListener('click', function(e) {
			if (!e.target.closest('.skate-pp-wrap')) {
				document.querySelectorAll('.skate-pp-panel').forEach(function(p) { p.hidden = true; });
			}
		});

		// Init pickers on existing rows
		document.querySelectorAll('.skate-url-wrap').forEach(skateInitPicker);

		// ── Picker HTML template (used in add-row handlers) ──────────────────────
		function skatePickerHtml(urlName, pidName, style) {
			style = style || 'flex:1;min-width:140px;';
			return '<div class="skate-url-wrap" style="' + style + '">' +
				'<input type="hidden" name="' + pidName + '" value="0" class="skate-pid">' +
				'<div class="skate-pp-row">' +
				'<input type="text" name="' + urlName + '" placeholder="URL" class="skate-url-input">' +
				'<div class="skate-pp-wrap">' +
				'<button type="button" class="button skate-pp-open">Page</button>' +
				'<div class="skate-pp-panel" hidden>' +
				'<input type="text" class="skate-pp-filter" placeholder="Search...">' +
				'<div class="skate-pp-results"></div>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'<div class="skate-pid-linked" hidden>' +
				'<span class="skate-pid-icon">&#128196;</span>' +
				'<span class="skate-pid-linked-title"></span>' +
				'<button type="button" class="skate-pid-unlink" title="Remove link">&#x2715;</button>' +
				'</div>' +
				'</div>';
		}

		// Add link
		const addLinkBtn = document.getElementById('skate-add-link');
		if (addLinkBtn) {
			addLinkBtn.addEventListener('click', function () {
				const cont = document.getElementById('skate-nav-links-container');
				const div  = document.createElement('div');
				div.className = 'skate-nav-link-row';
				div.innerHTML =
					'<span class="skate-social-drag-handle">⠿</span>' +
					'<input type="text" name="skate_nav_link_label[]" placeholder="Label" style="width:150px;">' +
					skatePickerHtml('skate_nav_link_url[]', 'skate_nav_link_page_id[]') +
					'<button type="button" class="button skate-nb-remove" data-cls="skate-nav-link-row">✕</button>';
				cont.appendChild(div);
				div.querySelectorAll('.skate-url-wrap').forEach(skateInitPicker);
			});
		}

		// Add link inside a group (submenus tab)
		document.addEventListener('click', function (e) {
			const btn = e.target.closest('.skate-add-col-link');
			if (!btn) return;
			const li   = btn.dataset.li;
			const ci   = btn.dataset.ci;
			const gi   = btn.dataset.gi;
			const cont = btn.closest('.skate-sub-group-card').querySelector('.skate-sub-col-links');
			const div  = document.createElement('div');
			div.className  = 'skate-sub-link-row';
			div.draggable  = true;
			const uName = 'skate_nav_col_link_url['    + li + '][' + ci + '][' + gi + '][]';
			const pName = 'skate_nav_col_link_pid['    + li + '][' + ci + '][' + gi + '][]';
			const tName = 'skate_nav_col_link_title['  + li + '][' + ci + '][' + gi + '][]';
			const sName = 'skate_nav_col_link_svg['    + li + '][' + ci + '][' + gi + '][]';
			const aName = 'skate_nav_col_link_anchor[' + li + '][' + ci + '][' + gi + '][]';
			div.innerHTML =
				'<span class="skate-link-drag-handle" title="Drag to reorder">⠿</span>' +
				'<input type="hidden" name="' + sName + '" value="">' +
				'<input type="text" name="' + tName + '" placeholder="Label" style="width:130px;">' +
				skatePickerHtml(uName, pName) +
				'<input type="text" name="' + aName + '" placeholder="#anchor" style="width:90px;" title="Anchor (e.g. section-id, without #)">' +
				'<button type="button" class="button skate-nb-remove" data-cls="skate-sub-link-row">✕</button>';
			cont.appendChild(div);
			div.querySelectorAll('.skate-url-wrap').forEach(skateInitPicker);
		});

		// Add group inside a column (submenus tab)
		document.addEventListener('click', function (e) {
			const btn = e.target.closest('.skate-add-group');
			if (!btn) return;
			const li    = btn.dataset.li;
			const ci    = btn.dataset.ci;
			const gwrap = document.getElementById('skate-sub-groups-' + li + '-' + ci);
			if (!gwrap) return;
			const gi = parseInt(gwrap.dataset.nextGi, 10);
			gwrap.dataset.nextGi = gi + 1;

			const gTitle = 'skate_nav_col_group_title[' + li + '][' + ci + '][' + gi + ']';
			const div = document.createElement('div');
			div.className = 'skate-sub-group-card';
			div.innerHTML =
				'<div class="skate-sub-group-head">' +
				'<input type="text" name="' + gTitle + '" placeholder="Group title">' +
				'<button type="button" class="button skate-nb-remove" data-cls="skate-sub-group-card" title="Remove group">✕</button>' +
				'</div>' +
				'<div class="skate-sub-col-links" data-li="' + li + '" data-ci="' + ci + '" data-gi="' + gi + '"></div>' +
				'<div class="skate-sub-add-link-row">' +
				'<button type="button" class="button skate-add-col-link" data-li="' + li + '" data-ci="' + ci + '" data-gi="' + gi + '">+ Add link</button>' +
				'</div>';
			gwrap.appendChild(div);
		});

		// ── Column order helper ──────────────────────────────────────────────
		function skateUpdateColOrder(cwrap) {
			const section = cwrap.closest('.skate-tune-section');
			if (!section) return;
			const orderInput = section.querySelector('.skate-col-order-input');
			if (!orderInput) return;
			const order = [];
			cwrap.querySelectorAll(':scope > .skate-sub-col-card').forEach(function (card) {
				if (card.classList.contains('skate-featured-col-card')) {
					order.push({type: 'featured', fci: parseInt(card.dataset.fci, 10)});
				} else {
					order.push({type: 'regular', ci: parseInt(card.dataset.ci, 10)});
				}
			});
			orderInput.value = JSON.stringify(order);
		}

		// ── Drag & drop columns ───────────────────────────────────────────────
		var skateDragSrc = null;
		document.addEventListener('dragstart', function (e) {
			const card = e.target.closest('.skate-sub-col-wrap > .skate-sub-col-card');
			if (!card) return;
			skateDragSrc = card;
			e.dataTransfer.effectAllowed = 'move';
			setTimeout(function () { card.classList.add('is-dragging'); }, 0);
		});
		document.addEventListener('dragend', function (e) {
			const card = e.target.closest('.skate-sub-col-wrap > .skate-sub-col-card');
			if (card) card.classList.remove('is-dragging');
			document.querySelectorAll('.skate-sub-col-card.drag-over').forEach(function (c) { c.classList.remove('drag-over'); });
			skateDragSrc = null;
		});
		document.addEventListener('dragover', function (e) {
			const card = e.target.closest('.skate-sub-col-wrap > .skate-sub-col-card');
			if (!card || card === skateDragSrc) return;
			e.preventDefault();
			document.querySelectorAll('.skate-sub-col-card.drag-over').forEach(function (c) { c.classList.remove('drag-over'); });
			card.classList.add('drag-over');
		});
		document.addEventListener('dragleave', function (e) {
			const card = e.target.closest('.skate-sub-col-wrap > .skate-sub-col-card');
			if (card) card.classList.remove('drag-over');
		});
		document.addEventListener('drop', function (e) {
			const target = e.target.closest('.skate-sub-col-wrap > .skate-sub-col-card');
			if (!target || !skateDragSrc || target === skateDragSrc) return;
			const cwrap = target.closest('.skate-sub-col-wrap');
			if (!cwrap || !cwrap.contains(skateDragSrc)) return;
			e.preventDefault();
			target.classList.remove('drag-over');
			const rect = target.getBoundingClientRect();
			if (e.clientY < rect.top + rect.height / 2) {
				cwrap.insertBefore(skateDragSrc, target);
			} else {
				target.after(skateDragSrc);
			}
			skateUpdateColOrder(cwrap);
		});

		// ── Drag & drop link rows ────────────────────────────────────────────
		var skateLinkDragSrc = null;
		document.addEventListener('dragstart', function (e) {
			const row = e.target.closest('.skate-sub-col-links > .skate-sub-link-row');
			if (!row) return;
			skateLinkDragSrc = row;
			e.dataTransfer.effectAllowed = 'move';
			e.stopPropagation();
			setTimeout(function () { row.classList.add('is-dragging'); }, 0);
		}, true);
		document.addEventListener('dragend', function (e) {
			const row = e.target.closest('.skate-sub-col-links > .skate-sub-link-row');
			if (row) row.classList.remove('is-dragging');
			document.querySelectorAll('.skate-sub-link-row.drag-over').forEach(function (r) { r.classList.remove('drag-over'); });
			skateLinkDragSrc = null;
		}, true);
		document.addEventListener('dragover', function (e) {
			if (!skateLinkDragSrc) return;
			const row = e.target.closest('.skate-sub-col-links > .skate-sub-link-row');
			if (!row || row === skateLinkDragSrc) return;
			e.preventDefault();
			e.stopPropagation();
			document.querySelectorAll('.skate-sub-link-row.drag-over').forEach(function (r) { r.classList.remove('drag-over'); });
			row.classList.add('drag-over');
		}, true);
		document.addEventListener('dragleave', function (e) {
			const row = e.target.closest('.skate-sub-col-links > .skate-sub-link-row');
			if (row) row.classList.remove('drag-over');
		}, true);
		document.addEventListener('drop', function (e) {
			if (!skateLinkDragSrc) return;
			const target = e.target.closest('.skate-sub-col-links > .skate-sub-link-row');
			if (!target || target === skateLinkDragSrc) return;
			const cont = target.closest('.skate-sub-col-links');
			if (!cont || !cont.contains(skateLinkDragSrc)) return;
			e.preventDefault();
			e.stopPropagation();
			target.classList.remove('drag-over');
			const rect = target.getBoundingClientRect();
			if (e.clientY < rect.top + rect.height / 2) {
				cont.insertBefore(skateLinkDragSrc, target);
			} else {
				target.after(skateLinkDragSrc);
			}
		}, true);

		// ── Collapse / expand column card ────────────────────────────────────
		document.addEventListener('click', function (e) {
			const head = e.target.closest('.skate-sub-col-card-head');
			if (!head) return;
			// Don't collapse when clicking drag handle or remove button
			if (e.target.closest('.skate-col-drag-handle, .skate-nb-remove, .skate-featured-col-remove')) return;
			const card = head.closest('.skate-sub-col-card');
			if (card) card.classList.toggle('is-collapsed');
		});

		// Shared SVG icons for column cards
		const colSvgChevron = '<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 3.5L5 6.5L8 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		const colSvgClose   = '<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 2L8 8M8 2L2 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';

		// ── Add column ────────────────────────────────────────────────────────
		document.addEventListener('click', function (e) {
			const btn = e.target.closest('.skate-add-column');
			if (!btn) return;
			const li    = btn.dataset.li;
			const cwrap = document.getElementById('skate-sub-col-wrap-' + li);
			if (!cwrap) return;
			const ci = parseInt(cwrap.dataset.nextCi, 10);
			cwrap.dataset.nextCi = ci + 1;

			const div = document.createElement('div');
			div.className   = 'skate-sub-col-card';
			div.dataset.ci  = ci;
			div.draggable   = true;
			div.innerHTML =
				'<div class="skate-sub-col-card-head">' +
				'<span class="skate-col-drag-handle" title="Drag to reorder">⠿</span>' +
				'<span class="skate-sub-col-label">Column ' + (ci + 1) + '</span>' +
				'<button type="button" class="skate-col-collapse" title="Toggle">' + colSvgChevron + '</button>' +
				'<button type="button" class="skate-nb-remove" data-cls="skate-sub-col-card" title="Remove column">' + colSvgClose + '</button>' +
				'</div>' +
				'<div class="skate-sub-col-card-body">' +
				'<div class="skate-sub-groups-wrap" id="skate-sub-groups-' + li + '-' + ci + '" data-li="' + li + '" data-ci="' + ci + '" data-next-gi="0"></div>' +
				'<div class="skate-sub-add-group-row">' +
				'<button type="button" class="button skate-add-group" data-li="' + li + '" data-ci="' + ci + '">+ Add group</button>' +
				'</div>' +
				'</div>';
			// Insert before template element if present
			const tpl = cwrap.querySelector('template');
			cwrap.insertBefore(div, tpl || null);
			skateUpdateColOrder(cwrap);
		});

		// Add button
		const addBtnBtn = document.getElementById('skate-add-btn');
		if (addBtnBtn) {
			addBtnBtn.addEventListener('click', function () {
				const cont = document.getElementById('skate-nav-buttons-container');
				const div  = document.createElement('div');
				div.className = 'skate-nav-btn-row';
				div.innerHTML =
					'<span class="skate-social-drag-handle">⠿</span>' +
					'<input type="text" name="skate_nav_btn_label[]" placeholder="Label" style="width:150px;">' +
					skatePickerHtml('skate_nav_btn_url[]', 'skate_nav_btn_page_id[]') +
					'<select name="skate_nav_btn_style[]" style="width:130px;">' +
					'<option value="filled">Filled Secondary</option>' +
					'<option value="filled-dark">Filled Primary</option>' +
					'<option value="outline">Outline</option>' +
					'</select>' +
					'<button type="button" class="button skate-nb-remove" data-cls="skate-nav-btn-row">✕</button>';
				cont.appendChild(div);
				div.querySelectorAll('.skate-url-wrap').forEach(skateInitPicker);
			});
		}

		// Add icon
		const addIconBtn = document.getElementById('skate-add-icon');
		if (addIconBtn) {
			addIconBtn.addEventListener('click', function () {
				const cont = document.getElementById('skate-nav-icons-container');
				const div  = document.createElement('div');
				div.className = 'skate-nav-icon-row';
				div.innerHTML =
					'<span class="skate-social-drag-handle">⠿</span>' +
					'<div class="skate-nb-svg-wrap">' +
					'<div class="skate-nb-svg-preview"></div>' +
					'<textarea name="skate_nav_icon_svg[]" rows="3" placeholder="SVG"></textarea>' +
					'</div>' +
					skatePickerHtml('skate_nav_icon_url[]', 'skate_nav_icon_page_id[]') +
					'<button type="button" class="button skate-nb-remove" data-cls="skate-nav-icon-row">✕</button>';
				cont.appendChild(div);
				div.querySelectorAll('.skate-url-wrap').forEach(skateInitPicker);
			});
		}

		// Add hamburger sub-item
		const addHbgSubBtn = document.getElementById('skate-add-hbg-sub');
		if (addHbgSubBtn) {
			addHbgSubBtn.addEventListener('click', function () {
				const cont = document.getElementById('skate-nav-hbg-sub-container');
				const div  = document.createElement('div');
				div.className = 'skate-nav-sub-row';
				div.innerHTML =
					'<span class="skate-social-drag-handle">⠿</span>' +
					'<div class="skate-nb-svg-wrap">' +
					'<div class="skate-nb-svg-preview"></div>' +
					'<textarea name="skate_nav_hbg_sub_svg[]" rows="3" placeholder="SVG"></textarea>' +
					'</div>' +
					'<input type="text" name="skate_nav_hbg_sub_title[]" placeholder="Title" style="flex:1;">' +
					'<input type="text" name="skate_nav_hbg_sub_url[]" placeholder="URL" style="flex:1;">' +
					'<button type="button" class="button skate-nb-remove" data-cls="skate-nav-sub-row">✕</button>';
				cont.appendChild(div);
			});
		}

		// Hamburger enable toggle
		var hbgToggle = document.querySelector('input[name="skate_nav_hamburger_enabled"]');
		if (hbgToggle) {
			hbgToggle.addEventListener('change', function() {
				var body = document.querySelector('.skate-hbg-body');
				if (body) body.hidden = !this.checked;
			});
		}

		// ── Add featured column ──────────────────────────────────────────────
		document.addEventListener('click', function (e) {
			const btn = e.target.closest('.skate-add-featured-col');
			if (!btn) return;
			const li    = btn.dataset.li;
			const cwrap = document.getElementById('skate-sub-col-wrap-' + li);
			if (!cwrap) return;
			const fci = parseInt(cwrap.dataset.nextFci || '0', 10);
			cwrap.dataset.nextFci = fci + 1;

			// Clone options from the template
			const tpl = document.getElementById('skate-feat-tpl-' + li);
			const optHtml = tpl ? tpl.innerHTML : '<option value="0">— None —</option>';

			const div = document.createElement('div');
			div.className      = 'skate-sub-col-card skate-featured-col-card';
			div.dataset.fci    = fci;
			div.draggable      = true;
			div.innerHTML =
				'<div class="skate-sub-col-card-head">' +
				'<span class="skate-col-drag-handle" title="Drag to reorder">⠿</span>' +
				'<span class="skate-sub-col-label">Featured</span>' +
				'<button type="button" class="skate-col-collapse" title="Toggle">' + colSvgChevron + '</button>' +
				'<button type="button" class="skate-featured-col-remove" title="Remove">' + colSvgClose + '</button>' +
				'</div>' +
				'<div class="skate-featured-col-body">' +
				'<select name="skate_nav_featured_post_id[' + li + '][' + fci + ']" class="skate-featured-select">' +
				optHtml +
				'</select>' +
				'<a href="#" class="skate-featured-edit-link" hidden>Edit in Site Editor &#8599;</a>' +
				'<div class="skate-featured-bg-row">' +
				'<label class="skate-featured-bg-label">BG Color</label>' +
				'<input type="color" class="skate-featured-bg-color" name="skate_nav_featured_bg_color[' + li + '][' + fci + ']" value="#F2F4F6">' +
				'<input type="text" class="skate-featured-bg-hex" value="#F2F4F6" maxlength="7" placeholder="#rrggbb">' +
				'</div>' +
				'</div>';
			const tplEl = cwrap.querySelector('template');
			cwrap.insertBefore(div, tplEl || null);
			skateUpdateColOrder(cwrap);
		});

		// ── Remove featured column ────────────────────────────────────────────
		document.addEventListener('click', function (e) {
			const btn = e.target.closest('.skate-featured-col-remove');
			if (!btn) return;
			const card  = btn.closest('.skate-featured-col-card');
			if (!card) return;
			const cwrap = card.closest('.skate-sub-col-wrap');
			card.remove();
			if (cwrap) skateUpdateColOrder(cwrap);
		});

		// ── Featured pattern select → update edit link ────────────────────────
		document.addEventListener('change', function (e) {
			const select = e.target.closest('.skate-featured-select');
			if (!select) return;
			const card    = select.closest('.skate-featured-col-card');
			if (!card) return;
			const option  = select.options[select.selectedIndex];
			const editUrl = (option && option.dataset.editUrl) || '';
			const editLink = card.querySelector('.skate-featured-edit-link');
			if (editLink) {
				editLink.href   = editUrl || '#';
				editLink.hidden = !editUrl;
			}
		});

		// ── Featured BG color ↔ hex sync ─────────────────────────────────────
		document.addEventListener('input', function (e) {
			const colorPicker = e.target.closest('.skate-featured-bg-color');
			if (!colorPicker) return;
			const row = colorPicker.closest('.skate-featured-bg-row');
			if (row) row.querySelector('.skate-featured-bg-hex').value = colorPicker.value;
		});
		document.addEventListener('change', function (e) {
			const hexInput = e.target.closest('.skate-featured-bg-hex');
			if (!hexInput) return;
			const val = hexInput.value.trim();
			if (/^#[0-9a-fA-F]{6}$/.test(val)) {
				const row = hexInput.closest('.skate-featured-bg-row');
				if (row) row.querySelector('.skate-featured-bg-color').value = val;
			}
		});

		// ── Fullscreen bg: swatch click ──────────────────────────────────────
		document.addEventListener('click', function (e) {
			const swatch = e.target.closest('.skate-fs-swatch');
			if (!swatch) return;
			const group = swatch.closest('.skate-fs-bg-group');
			if (!group) return;
			const color = swatch.dataset.color;
			group.querySelector('.skate-featured-bg-color').value = color;
			group.querySelector('.skate-featured-bg-hex').value   = color;
			group.querySelectorAll('.skate-fs-swatch').forEach(function (s) {
				s.classList.toggle('is-active', s === swatch);
			});
		});

		// ── Fullscreen bg: opacity slider display ────────────────────────────
		document.addEventListener('input', function (e) {
			const slider = e.target.closest('input[name="skate_navbar_fullscreen_bg_opacity"]');
			if (!slider) return;
			const group = slider.closest('.skate-fs-bg-group');
			if (group) group.querySelector('.skate-fs-opacity-value').textContent = slider.value + '%';
		});

		// ── Icon mode card picker — show/hide default SVG field ──────────────
		document.querySelectorAll('input[name="skate_navbar_submenu_icon_mode"]').forEach(function (radio) {
			radio.addEventListener('change', function () {
				const defaultWrap = document.querySelector('.skate-icon-default-wrap');
				if (defaultWrap) defaultWrap.hidden = (radio.value !== 'default');
			});
		});

		// ── Import file picker ───────────────────────────────────────────────────
		const importPicker = document.getElementById('skate-import-file-picker');
		const importJson   = document.getElementById('skate-import-json-data');
		const importSubmit = document.getElementById('skate-import-submit-btn');
		if (importPicker) {
			importPicker.addEventListener('change', function () {
				const file = this.files[0];
				if (!file) return;
				const reader = new FileReader();
				reader.onload = function (e) {
					try {
						const parsed = JSON.parse(e.target.result);
						if (!Array.isArray(parsed)) { alert('Invalid preset file.'); return; }
						importJson.value = e.target.result;
						importSubmit.hidden = false;
						importSubmit.textContent = 'Import ' + parsed.length + ' preset' + (parsed.length !== 1 ? 's' : '') + ' from ' + file.name;
					} catch (err) {
						alert('Could not parse JSON file.');
					}
				};
				reader.readAsText(file);
			});
		}

		// Logo light media upload
		(function () {
			const btn     = document.getElementById('skate-logo-light-btn');
			const removeBtn = document.getElementById('skate-logo-light-remove');
			const urlInput  = document.getElementById('skate_navbar_logo_light_url');
			const preview   = document.getElementById('skate-logo-light-preview');
			if (!btn) return;

			function setLogoLight(url) {
				urlInput.value = url;
				if (url) {
					preview.src = url;
					preview.style.display = '';
					removeBtn.hidden = false;
				} else {
					preview.src = '';
					preview.style.display = 'none';
					removeBtn.hidden = true;
				}
			}

			btn.addEventListener('click', function () {
				if (!window.wp || !wp.media) return;
				const frame = wp.media({ title: 'Choose Logo Light', button: { text: 'Use this image' }, multiple: false });
				frame.on('select', function () {
					setLogoLight(frame.state().get('selection').first().toJSON().url);
				});
				frame.open();
			});

			if (removeBtn) {
				removeBtn.addEventListener('click', function () { setLogoLight(''); });
			}
		})();
	})();
	</script>
	<?php
}
