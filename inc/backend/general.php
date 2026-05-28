<?php
/* --------------------------
 * Top Toolbar Shortcuts
 * ------------------------ */
function genoa_admin_bar_links( $wp_admin_bar ) {
    $wp_admin_bar->add_node( array(
        'id'    => 'patterns_shortcut',
        'title' => 'SKATEWP',
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
        .skatewp_page_skate-design #wpcontent,
        .skatewp_page_skate-sitemap #wpcontent,
        .skatewp_page_skate-core-settings #wpcontent,
        .skatewp_page_skate-identity #wpcontent,
        .skatewp_page_skate-typography #wpcontent,
        .skatewp_page_skate-navbar #wpcontent {
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
 * Admin Sidebar on Skate pages
 * ------------------------ */
function skate_admin_sidebar() {
    $screen = get_current_screen();
    if ( ! $screen ) return;

    $skate_screens = [
        'toplevel_page_skate',
        'skatewp_page_skate-design',
        'skatewp_page_skate-core-settings',
        'skatewp_page_skate-identity',
        'skatewp_page_skate-sitemap',
        'skatewp_page_skate-navbar',
        'skatewp_page_skate-typography',
        'skatewp_page_skate-reviews',
    ];

    if ( ! in_array( $screen->id, $skate_screens, true ) ) return;

    $current = $screen->id;
    $version = wp_get_theme( get_template() )->get( 'Version' );

    $nav = [
        [ 'label' => 'Settings',    'url' => admin_url( 'admin.php?page=skate-core-settings' ),  'id' => 'skatewp_page_skate-core-settings' ],
        [ 'label' => 'Identity',    'url' => admin_url( 'admin.php?page=skate-identity' ),        'id' => 'skatewp_page_skate-identity' ],
        [ 'label' => 'Design',      'url' => admin_url( 'admin.php?page=skate-design' ),          'id' => 'skatewp_page_skate-design' ],
        [ 'label' => 'Typography',  'url' => admin_url( 'admin.php?page=skate-typography' ),      'id' => 'skatewp_page_skate-typography' ],
        [ 'label' => 'Navigation',  'url' => admin_url( 'admin.php?page=skate-navbar' ),          'id' => 'skatewp_page_skate-navbar' ],
        [ 'label' => 'Sitemap',     'url' => admin_url( 'admin.php?page=skate' ),                 'id' => 'toplevel_page_skate' ],
        [ 'label' => 'Reviews',     'url' => admin_url( 'admin.php?page=skate-reviews' ),         'id' => 'skatewp_page_skate-reviews' ],
    ];
    ?>
    <style>
        /* Hide Skate sub-items from WP admin menu — navigation is in the sidebar */
        #adminmenu .toplevel_page_skate ul.wp-submenu { display: none !important; }

        /* Push content past both WP menu + Skate sidebar */
        #wpcontent, #wpfooter { margin-left: 340px !important; } /* 160 WP + 180 Skate */
        body.folded #wpcontent, body.folded #wpfooter { margin-left: 216px !important; } /* 36 WP + 180 Skate */

        #skate-sidebar {
            position: fixed;
            top: 32px;
            left: 160px; /* default WP menu width; JS will override */
            width: 180px;
            height: calc(100vh - 32px);
            background: var(--skate-primary);
            z-index: 9990;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
        }
        body.folded #skate-sidebar { left: 36px; } /* collapsed WP menu */
        .skate-sidebar-brand {
            padding: 22px 18px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 0;
        }
        .skate-sidebar-logo {
            display: block;
            font-size: 18px;
            font-weight: 900;
            font-style: italic;
            letter-spacing: -0.5px;
            color: #fff;
            text-decoration: none;
            line-height: 1;
            transition: color .15s;
        }
        .skate-sidebar-logo:hover { color: var(--skate-accent); }
        .skate-sidebar-version {
            display: block;
            font-size: 10px;
            color: var(--skate-accent);
            font-weight: 500;
            letter-spacing: .03em;
            margin-top: 4px;
        }
        .skate-sidebar-nav {
            flex: 1;
            padding: 10px 0;
        }
        .skate-sidebar-nav a {
            display: block;
            padding: 10px 18px;
            font-size: 12.5px;
            font-weight: 500;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: color .15s, background .15s, border-color .15s;
            white-space: nowrap;
            box-sizing: border-box;
        }
        .skate-sidebar-nav a:hover {
            color: #fff;
            background: rgba(255,255,255,0.06);
        }
        .skate-sidebar-nav a.is-current {
            color: var(--skate-accent);
            border-left-color: var(--skate-accent);
            background: var(--skate-accent-glow);
        }
    </style>
    <div id="skate-sidebar">
        <div class="skate-sidebar-brand">
            <a class="skate-sidebar-logo" href="<?php echo esc_url( admin_url( 'admin.php?page=skate' ) ); ?>">SKATEWP</a>
            <span class="skate-sidebar-version">v<?php echo esc_html( $version ); ?></span>
        </div>
        <nav class="skate-sidebar-nav">
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
        var sidebar  = document.getElementById('skate-sidebar');
        var SIDEBAR_W = 180;
        if (!sidebar) return;

        function align() {
            var wrap    = document.getElementById('adminmenuwrap');
            var content = document.getElementById('wpcontent');
            if (!wrap) return;
            var menuW = wrap.offsetWidth;
            sidebar.style.left = menuW + 'px';
            if (content) content.style.marginLeft = (menuW + SIDEBAR_W) + 'px';
        }

        align();
        window.addEventListener('resize', align);

        // Handle WP menu collapse/expand (body gets .folded class)
        var observer = new MutationObserver(function() { setTimeout(align, 300); });
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

        var collapseBtn = document.getElementById('collapse-button');
        if (collapseBtn) collapseBtn.addEventListener('click', function() { setTimeout(align, 300); });
    });

    // Cmd/Ctrl+S → click the primary save button
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            var btn = document.querySelector('input[type="submit"].button-primary');
            if (btn) { e.preventDefault(); btn.click(); }
        }
    });
    </script>
    <?php
}
add_action( 'admin_notices', 'skate_admin_sidebar' );

/* --------------------------
 * Reorder WP sidebar submenu to match topbar order:
 * Settings → Identity → Design → Navigation → Sitemap
 * ------------------------ */
add_action( 'admin_menu', function () {
    global $submenu;
    if ( empty( $submenu['skate'] ) ) return;

    $order = [ 'skate-core-settings', 'skate-identity', 'skate-design', 'skate-typography', 'skate-navbar', 'skate' ];

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
        'skatewp_page_skate-design',
        'skatewp_page_skate-core-settings',
        'skatewp_page_skate-identity',
        'skatewp_page_skate-sitemap',
        'skatewp_page_skate-navbar',
        'skatewp_page_skate-typography',
        'skatewp_page_skate-reviews',
    ];

    if ( ! in_array( $screen->id, $skate_screens, true ) ) return;

    remove_all_actions( 'admin_notices' );
    remove_all_actions( 'all_admin_notices' );
    remove_all_actions( 'user_admin_notices' );

    // Re-add our own sidebar
    add_action( 'admin_notices', 'skate_admin_sidebar' );
}, PHP_INT_MAX );