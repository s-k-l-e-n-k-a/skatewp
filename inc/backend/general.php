<?php
/* --------------------------
 * Top Toolbar Shortcuts
 * ------------------------ */
function genoa_admin_bar_links( $wp_admin_bar ) {
    $wp_admin_bar->add_node( array(
        'id'    => 'patterns_shortcut',
        'title' => 'SKATE',
        'href'  => admin_url( 'admin.php?page=skate-core-settings' ),
        'meta'  => array( 'class' => 'patterns-shortcut' ),
    ) );

    $wp_admin_bar->add_node( array(
        'id'    => 'patterns_link',
        'title' => 'Patterns',
        'href'  => admin_url( 'site-editor.php?path=%2Fpatterns' ),
        'meta'  => array( 'class' => 'skate-patterns-link' ),
    ) );

    $wp_admin_bar->add_node( array(
        'id'    => 'theme_editor_shortcut',
        'title' => 'Theme-Editor',
        'href'  => admin_url( 'theme-editor.php' ),
        'meta'  => array( 'class' => 'theme-editor-shortcut' ),
    ) );
}
add_action('admin_bar_menu', 'genoa_admin_bar_links', 100);

/* --------------------------
 * Highlight Site Editor Link
 * ------------------------ */
function genoa_admin_bar_styles() {
    echo '<style>
        #wpadminbar .patterns-shortcut > a.ab-item,
        #wpadminbar .skate-patterns-link > a.ab-item {
            color: var(--skate-accent) !important;
            font-weight: 600;
        }
        #wpadminbar .patterns-shortcut > a.ab-item:hover,
        #wpadminbar .skate-patterns-link > a.ab-item:hover {
            color: var(--skate-accent) !important;
        }
        .toplevel_page_skate #wpcontent,
        .skate_page_skate-design #wpcontent,
        .skate_page_skate-sitemap #wpcontent,
        .skate_page_skate-core-settings #wpcontent,
        .skate_page_skate-identity #wpcontent,
        .skate_page_skate-navbar #wpcontent {
            background: linear-gradient(to right, #f2f4f6 0%, var(--skate-accent-bg) 100%);
            min-height: 100vh;
        }
    </style>';
}
add_action('admin_head', 'genoa_admin_bar_styles');
add_action('wp_head', 'genoa_admin_bar_styles');

/* --------------------------
 * Accent CSS variable (secondary theme color)
 * ------------------------ */
$skate_accent_style_cb = function () {
    $palette = wp_get_global_settings( [ 'color', 'palette', 'theme' ] );
    $accent  = '#d6b36d'; // fallback
    $primary = '#17263a'; // fallback
    foreach ( (array) $palette as $item ) {
        $slug = $item['slug'] ?? '';
        if ( $slug === 'secondary-color' ) $accent  = $item['color'];
        if ( $slug === 'main-color' )      $primary = $item['color'];
    }
    $hex = ltrim( $accent, '#' );
    $r   = hexdec( substr( $hex, 0, 2 ) );
    $g   = hexdec( substr( $hex, 2, 2 ) );
    $b   = hexdec( substr( $hex, 4, 2 ) );
    echo '<style>:root{'
        . '--skate-primary:' . esc_attr( $primary ) . ';'
        . '--skate-accent:' . esc_attr( $accent ) . ';'
        . '--skate-accent-glow:rgba(' . (int) $r . ',' . (int) $g . ',' . (int) $b . ',.22);'
        . '--skate-accent-bg:rgba(' . (int) $r . ',' . (int) $g . ',' . (int) $b . ',.07);'
        . '}</style>' . "\n";
};
add_action( 'admin_head', $skate_accent_style_cb );
add_action( 'wp_head',    $skate_accent_style_cb );

/* --------------------------
 * Admin Topbar on Skate pages
 * ------------------------ */
function skate_admin_topbar() {
    $screen = get_current_screen();
    if ( ! $screen ) return;

    $skate_screens = [
        'toplevel_page_skate',
        'skate_page_skate-design',
        'skate_page_skate-core-settings',
        'skate_page_skate-identity',
        'skate_page_skate-sitemap',
        'skate_page_skate-navbar',
    ];

    if ( ! in_array( $screen->id, $skate_screens, true ) ) return;

    $current = $screen->id;
    $version = wp_get_theme( get_template() )->get( 'Version' );

    $nav = [
        [ 'label' => 'Settings',    'url' => admin_url( 'admin.php?page=skate-core-settings' ),  'id' => 'skate_page_skate-core-settings' ],
        [ 'label' => 'Identity',    'url' => admin_url( 'admin.php?page=skate-identity' ),        'id' => 'skate_page_skate-identity' ],
        [ 'label' => 'Design',      'url' => admin_url( 'admin.php?page=skate-design' ),          'id' => 'skate_page_skate-design' ],
        [ 'label' => 'Navigation',  'url' => admin_url( 'admin.php?page=skate-navbar' ),          'id' => 'skate_page_skate-navbar' ],
        [ 'label' => 'Sitemap',     'url' => admin_url( 'admin.php?page=skate' ),                 'id' => 'toplevel_page_skate' ],
    ];
    ?>
    <style>
        #skate-topbar {
            display: flex;
            align-items: stretch;
            background: var(--skate-primary);
            height: 84px;
            padding: 0 24px 0 16px;
            box-sizing: border-box;
            position: fixed;
            top: 32px;
            right: 0;
            z-index: 9990;
        }
        .skate-topbar-brand {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            gap: 2px;
            flex-shrink: 0;
            margin-right: 20px;
            height: auto;
        }
        .skate-topbar-logo {
            font-size: 20px;
            font-weight: 900;
            font-style: italic;
            letter-spacing: -1px;
            color: #fff;
            text-decoration: none;
            line-height: 1;
            transition: color .15s;
        }
        .skate-topbar-logo:hover { color: var(--skate-accent); }
        .skate-topbar-version {
            font-size: 10px;
            color: var(--skate-accent);
            font-weight: 500;
            letter-spacing: .03em;
        }
        .skate-topbar-sep {
            width: 1px;
            background: rgba(255,255,255,0.12);
            flex-shrink: 0;
            align-self: stretch;
        }
        .skate-topbar-nav {
            display: flex;
            align-items: stretch;
            height: 100%;
            margin-left: 8px;
        }
        .skate-topbar-nav a {
            display: flex;
            align-items: center;
            padding: 0 18px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            border-bottom: 3px solid transparent;
            border-top: 3px solid transparent;
            transition: color .15s, background .15s, border-color .15s;
            box-sizing: border-box;
            white-space: nowrap;
            border-radius: 0;
        }
        .skate-topbar-nav a:hover {
            color: #fff;
            background: rgba(255,255,255,0.06);
        }
        .skate-topbar-nav a.is-current {
            color: var(--skate-accent);
            border-bottom-color: var(--skate-accent);
            background: var(--skate-accent-glow);
        }
    </style>
    <div id="skate-topbar">
        <div class="skate-topbar-brand">
            <a class="skate-topbar-logo" href="<?php echo esc_url( admin_url( 'admin.php?page=skate' ) ); ?>">SKATE</a>
            <span class="skate-topbar-version">v<?php echo esc_html( $version ); ?></span>
        </div>
        <div class="skate-topbar-sep"></div>
        <nav class="skate-topbar-nav">
            <?php foreach ( $nav as $item ) : ?>
            <a href="<?php echo esc_url( $item['url'] ); ?>"
               class="<?php echo $item['id'] === $current ? 'is-current' : ''; ?>">
                <?php echo esc_html( $item['label'] ); ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var bar     = document.getElementById('skate-topbar');
        var content = document.getElementById('wpcontent');
        var wpbody  = document.getElementById('wpbody-content');
        if ( !bar ) return;

        document.body.appendChild(bar);

        function alignBar() {
            var left = content ? content.getBoundingClientRect().left : 0;
            bar.style.left = left + 'px';
        }
        alignBar();
        window.addEventListener('resize', alignBar);

        // Push content down so it isn't hidden under the fixed bar
        if ( wpbody ) wpbody.style.paddingTop = '94px'; // 84px bar + 10px original
    });

    // Ctrl+S / Cmd+S → click the primary save button
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            var btn = document.querySelector('input[type="submit"].button-primary');
            if (btn) {
                e.preventDefault();
                btn.click();
            }
        }
    });
    </script>
    <?php
}
add_action( 'admin_notices', 'skate_admin_topbar' );

/* --------------------------
 * Reorder WP sidebar submenu to match topbar order:
 * Settings → Identity → Design → Navigation → Sitemap
 * ------------------------ */
add_action( 'admin_menu', function () {
    global $submenu;
    if ( empty( $submenu['skate'] ) ) return;

    $order = [ 'skate-core-settings', 'skate-identity', 'skate-design', 'skate-navbar', 'skate' ];

    $indexed = [];
    foreach ( $submenu['skate'] as $item ) {
        $indexed[ $item[2] ] = $item;
    }

    $sorted = [];
    foreach ( $order as $slug ) {
        if ( isset( $indexed[ $slug ] ) ) {
            $sorted[] = $indexed[ $slug ];
            unset( $indexed[ $slug ] );
        }
    }
    foreach ( $indexed as $item ) {
        $sorted[] = $item;
    }

    $submenu['skate'] = $sorted;
}, 999 );

/* --------------------------
 * Suppress third-party notices on Skate pages
 * ------------------------ */
add_action( 'in_admin_header', function () {
    $screen = get_current_screen();
    if ( ! $screen ) return;

    $skate_screens = [
        'toplevel_page_skate',
        'skate_page_skate-design',
        'skate_page_skate-core-settings',
        'skate_page_skate-identity',
        'skate_page_skate-sitemap',
        'skate_page_skate-navbar',
    ];

    if ( ! in_array( $screen->id, $skate_screens, true ) ) return;

    remove_all_actions( 'admin_notices' );
    remove_all_actions( 'all_admin_notices' );
    remove_all_actions( 'user_admin_notices' );

    // Re-add our own topbar
    add_action( 'admin_notices', 'skate_admin_topbar' );
}, PHP_INT_MAX );