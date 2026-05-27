<?php
/**
 * Typography — Skate
 *
 * Font families and size scale managed from the admin panel.
 *
 * Strategy: native Gutenberg only. All values are written directly into
 * WP's theme.json at runtime via the wp_theme_json_data_theme filter:
 *   - fontSizes presets (s/m/l/xl/xxl) → var(--wp--preset--font-size--*)
 *   - body element fontFamily → controls wp:paragraph
 *   - heading element fontFamily → controls all wp:heading (h1–h6)
 *   - h1/h2/h3 element fontSize → maps to the size presets above
 *
 * No custom CSS vars are emitted. Core blocks inherit everything through
 * the standard Gutenberg typography pipeline.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ----------------------------------------
// Defaults
// ----------------------------------------
define( 'SKATE_DEFAULT_FONT_HEADING', 'var(--wp--preset--font-family--syne)' );
define( 'SKATE_DEFAULT_FONT_BODY',    'var(--wp--preset--font-family--dm-sans)' );
define( 'SKATE_DEFAULT_SIZE_S',   '13px' );
define( 'SKATE_DEFAULT_SIZE_M',   '16px' );
define( 'SKATE_DEFAULT_SIZE_L',   'clamp(17px,1.6vw,20px)' );
define( 'SKATE_DEFAULT_SIZE_XL',  'clamp(26px,3vw,38px)' );
define( 'SKATE_DEFAULT_SIZE_XXL', 'clamp(52px,6vw,80px)' );

// ----------------------------------------
// Helpers
// ----------------------------------------
function skate_typo_get( string $key, string $default ): string {
    return (string) get_option( 'skate_typo_' . $key, $default );
}

function skate_get_theme_fonts(): array {
    $path = get_template_directory() . '/theme.json';
    if ( ! file_exists( $path ) ) return [];
    $data = json_decode( file_get_contents( $path ), true );
    return $data['settings']['typography']['fontFamilies'] ?? [];
}

// ----------------------------------------
// Inject values into theme.json at runtime.
// Writes font family and size values directly into WP's native preset and
// element-level typography — no custom CSS vars needed. Core blocks
// (wp:heading, wp:paragraph) inherit everything automatically.
// ----------------------------------------
add_filter( 'wp_theme_json_data_theme', function ( $theme_json ) {
    $heading_font = skate_typo_get( 'font_heading', SKATE_DEFAULT_FONT_HEADING );
    $body_font    = skate_typo_get( 'font_body',    SKATE_DEFAULT_FONT_BODY );
    $size_s       = skate_typo_get( 'size_s',       SKATE_DEFAULT_SIZE_S );
    $size_m       = skate_typo_get( 'size_m',       SKATE_DEFAULT_SIZE_M );
    $size_l       = skate_typo_get( 'size_l',       SKATE_DEFAULT_SIZE_L );
    $size_xl      = skate_typo_get( 'size_xl',      SKATE_DEFAULT_SIZE_XL );
    $size_xxl     = skate_typo_get( 'size_xxl',     SKATE_DEFAULT_SIZE_XXL );

    $theme_json->update_with( [
        'version'  => 3,
        'settings' => [
            'typography' => [
                'fontSizes' => [
                    [ 'slug' => 's',   'name' => 'S',   'size' => $size_s,   'fluid' => false ],
                    [ 'slug' => 'm',   'name' => 'M',   'size' => $size_m,   'fluid' => false ],
                    [ 'slug' => 'l',   'name' => 'L',   'size' => $size_l,   'fluid' => false ],
                    [ 'slug' => 'xl',  'name' => 'XL',  'size' => $size_xl,  'fluid' => false ],
                    [ 'slug' => 'xxl', 'name' => 'XXL', 'size' => $size_xxl, 'fluid' => false ],
                ],
            ],
        ],
        'styles'   => [
            'typography' => [
                'fontFamily' => $body_font,
                'fontSize'   => 'var(--wp--preset--font-size--m)',
            ],
            'elements' => [
                'heading' => [
                    'typography' => [
                        'fontFamily' => $heading_font,
                    ],
                ],
                'h1' => [ 'typography' => [ 'fontSize' => 'var(--wp--preset--font-size--xxl)' ] ],
                'h2' => [ 'typography' => [ 'fontSize' => 'var(--wp--preset--font-size--xl)' ] ],
                'h3' => [ 'typography' => [ 'fontSize' => 'var(--wp--preset--font-size--l)' ] ],
            ],
            'blocks'   => [
                'core/heading' => [
                    'typography' => [
                        'fontFamily' => $heading_font,
                    ],
                ],
                'core/paragraph' => [
                    'typography' => [
                        'fontFamily' => $body_font,
                    ],
                ],
            ],
        ],
    ] );

    return $theme_json;
} );

// ----------------------------------------
// Admin menu
// ----------------------------------------
add_action( 'admin_menu', function () {
    add_submenu_page(
        'skate',
        'Typography',
        'Typography',
        'manage_options',
        'skate-typography',
        'skate_typography_page'
    );
}, 999 );

// ----------------------------------------
// Save handler
// ----------------------------------------
add_action( 'admin_post_skate_save_typography', function () {
    check_admin_referer( 'skate_typography_save' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    // Font families: only allow values matching a loaded font slug
    $allowed_fonts = array_map(
        fn( $f ) => 'var(--wp--preset--font-family--' . $f['slug'] . ')',
        skate_get_theme_fonts()
    );

    $font_heading = sanitize_text_field( wp_unslash( $_POST['font_heading'] ?? '' ) );
    $font_body    = sanitize_text_field( wp_unslash( $_POST['font_body']    ?? '' ) );

    if ( in_array( $font_heading, $allowed_fonts, true ) ) {
        update_option( 'skate_typo_font_heading', $font_heading );
    }
    if ( in_array( $font_body, $allowed_fonts, true ) ) {
        update_option( 'skate_typo_font_body', $font_body );
    }

    // Size values: allow CSS length / clamp expressions
    foreach ( [ 'size_s', 'size_m', 'size_l', 'size_xl', 'size_xxl' ] as $key ) {
        $val = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
        // Allow digits, letters, spaces, parens, commas, dots, %, -
        if ( preg_match( '/^[\d\w\s\(\)\.,% -]+$/', $val ) && strlen( $val ) <= 80 ) {
            update_option( 'skate_typo_' . $key, $val );
        }
    }

    // Flush WP's theme.json cache so the filter output takes effect immediately.
    if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
        WP_Theme_JSON_Resolver::clean_cached_data();
    }

    wp_safe_redirect( admin_url( 'admin.php?page=skate-typography&saved=1' ) );
    exit;
} );

// ----------------------------------------
// Admin page
// ----------------------------------------
function skate_typography_page(): void {
    skate_print_preset_assets();

    $saved   = isset( $_GET['saved'] );
    $fonts   = skate_get_theme_fonts();
    $heading = skate_typo_get( 'font_heading', SKATE_DEFAULT_FONT_HEADING );
    $body    = skate_typo_get( 'font_body',    SKATE_DEFAULT_FONT_BODY );

    $sizes = [
        'size_s'   => [ 'label' => 'S — Labels &amp; eyebrows', 'hint' => 'h2 eyebrows, small captions',           'default' => SKATE_DEFAULT_SIZE_S ],
        'size_m'   => [ 'label' => 'M — Body &amp; links',       'hint' => 'paragraphs, link text, body copy',      'default' => SKATE_DEFAULT_SIZE_M ],
        'size_l'   => [ 'label' => 'L — H3',                     'hint' => 'card titles, section sub-headings',     'default' => SKATE_DEFAULT_SIZE_L ],
        'size_xl'  => [ 'label' => 'XL — H2',                    'hint' => 'section headings',                      'default' => SKATE_DEFAULT_SIZE_XL ],
        'size_xxl' => [ 'label' => 'XXL — H1',                   'hint' => 'hero headings, large display text',     'default' => SKATE_DEFAULT_SIZE_XXL ],
    ];
    ?>
    <div class="wrap skate-identity-wrap">
        <h1>Typography</h1>

        <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'skate_typography_save' ); ?>
            <input type="hidden" name="action" value="skate_save_typography">

            <h2 style="margin-top:1.5rem">Fonts</h2>
            <p style="color:#666;margin-top:0">These font families are loaded from your <code>theme.json</code>. Add new families there to expand this list.</p>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="font_heading">Heading font</label></th>
                    <td>
                        <select name="font_heading" id="font_heading">
                            <?php foreach ( $fonts as $f ) :
                                $val = 'var(--wp--preset--font-family--' . esc_attr( $f['slug'] ) . ')';
                            ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $heading, $val ); ?>>
                                <?php echo esc_html( $f['name'] ); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Sets heading element typography in <code>theme.json</code> — all H1–H3 core blocks update automatically. Also available as <code>var(--skate-font-heading)</code> for GreenShift structural blocks.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="font_body">Body font</label></th>
                    <td>
                        <select name="font_body" id="font_body">
                            <?php foreach ( $fonts as $f ) :
                                $val = 'var(--wp--preset--font-family--' . esc_attr( $f['slug'] ) . ')';
                            ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $body, $val ); ?>>
                                <?php echo esc_html( $f['name'] ); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Sets body element typography in <code>theme.json</code> — all paragraph core blocks update automatically. Also available as <code>var(--skate-font-body)</code> for GreenShift structural blocks.</p>
                    </td>
                </tr>
            </table>

            <h2 style="margin-top:2rem">Size scale</h2>
            <p style="color:#666;margin-top:0">Values are written directly into the Gutenberg font size presets — all blocks using those presets update automatically. Any valid CSS value: <code>13px</code> &nbsp;·&nbsp; <code>0.875rem</code> &nbsp;·&nbsp; <code>clamp(14px,1.5vw,18px)</code></p>

            <table class="form-table" role="presentation">
                <?php foreach ( $sizes as $key => $info ) :
                    $current = skate_typo_get( $key, $info['default'] );
                    $var     = 'var(--wp--preset--font-size--' . str_replace( 'size_', '', $key ) . ')';
                ?>
                <tr>
                    <th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo $info['label']; ?></label></th>
                    <td>
                        <input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"
                               value="<?php echo esc_attr( $current ); ?>"
                               class="regular-text"
                               placeholder="<?php echo esc_attr( $info['default'] ); ?>" />
                        <p class="description"><code><?php echo esc_html( $var ); ?></code> &nbsp;·&nbsp; <?php echo esc_html( $info['hint'] ); ?></p>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <?php submit_button( 'Save typography' ); ?>
        </form>
    </div>
    <?php
}
