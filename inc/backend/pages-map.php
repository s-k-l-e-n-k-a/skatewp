<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue jQuery UI Sortable only on the Skate admin page
 */
add_action('admin_enqueue_scripts', function ( $hook ) {
    if ( $hook === 'toplevel_page_skate' ) {
        wp_enqueue_script('jquery-ui-sortable');
    }
});

/**
 * Menü Skate
 */
add_action('admin_menu', function () {
    add_menu_page(
        __( 'SkateWP – Sitemap', 'skate' ),
        __( 'SkateWP', 'skate' ),
        'edit_pages',
        'skate',
        'skate_render_pages_by_zielgruppe',
        'dashicons-admin-multisite',
        58
    );

    // Rename the auto-generated first submenu item (WP duplicates the parent label by default)
    add_submenu_page(
        'skate',
        __( 'SkateWP – Sitemap', 'skate' ),
        __( 'Sitemap', 'skate' ),
        'edit_pages',
        'skate',
        null
    );
});

/**
 * Hauptansicht: Liste + Checkboxen + Massenlöschung
 */
function skate_render_pages_by_zielgruppe() {
    if ( ! current_user_can('edit_pages') ) return;

    // ---- Massenlöschung verarbeiten ----
    if ( isset($_POST['skate_action']) && $_POST['skate_action'] === 'bulk_delete' ) {
        skate_handle_bulk_delete();
    }

    $tax = taxonomy_exists('zielgruppen') ? 'zielgruppen' : 'category';

    // Begriffe (Zielgruppen)
    $terms = get_terms([
        'taxonomy'   => $tax,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'parent'     => 0,
    ]);


    echo '<div class="wrap skate-wrap">';
    echo '<h1>' . esc_html__('Sitemap', 'skate') . '</h1>';
    echo '<p class="description" style="margin:0 0 16px;">' .
        esc_html__('Select pages and use the button to delete them (selecting a parent automatically selects/deselects all subpages).', 'skate') .
        '</p>';

    echo '<form method="post" action="">';
    wp_nonce_field('skate_bulk_delete', 'skate_nonce');
    echo '<input type="hidden" name="skate_action" value="bulk_delete" />';

    echo '<div class="skate-view-toggle">';
    echo '<button type="button" class="skate-view-btn skate-view-btn--preview" data-view="preview">' . esc_html__('Preview', 'skate') . '</button>';
    echo '<button type="button" class="skate-view-btn skate-view-btn--develop" data-view="develop">' . esc_html__('Develop', 'skate') . '</button>';
    echo '</div>';

    // Nach Begriff
    foreach ( $terms as $term ) {
        $pages   = skate_get_pages_for_term( $tax, $term->term_id );
        $gkey    = 'term-' . $term->term_id;
        $summary = skate_qa_summary_html( $pages );
        echo '<h2 class="skate-group-header" data-group="' . esc_attr($gkey) . '" style="margin-top:24px;">';
        echo '<span class="skate-group-chevron">&#9660;</span> ';
        echo esc_html( $term->name );
        echo ' <span style="opacity:.7;font-weight:normal;">(' . intval( count($pages) ) . ')</span>';
        echo $summary;
        echo '</h2>';
        echo '<div class="skate-group-content" id="skate-group-' . esc_attr($gkey) . '">';
        if ( empty($pages) ) {
            echo '<p style="opacity:.7;">' . esc_html__('No pages in this group.', 'skate') . '</p>';
        } else {
            skate_render_pages_tree( $pages );
        }
        echo '</div>';
    }

    // Ohne Begriff
    $pages_without = skate_get_pages_without_term( $tax );
    $gkey_none     = 'no-term';
    $summary_none  = skate_qa_summary_html( $pages_without );
    echo '<h2 class="skate-group-header" data-group="' . esc_attr($gkey_none) . '" style="margin-top:32px;">';
    echo '<span class="skate-group-chevron">&#9660;</span> ';
    echo esc_html__('Without Target Audience', 'skate');
    echo ' <span style="opacity:.7;font-weight:normal;">(' . intval( count($pages_without) ) . ')</span>';
    echo $summary_none;
    echo '</h2>';
    echo '<div class="skate-group-content" id="skate-group-' . esc_attr($gkey_none) . '">';
    if ( empty($pages_without) ) {
        echo '<p style="opacity:.7;">' . esc_html__('All pages have a target audience.', 'skate') . '</p>';
    } else {
        skate_render_pages_tree( $pages_without );
    }
    echo '</div>';

    // Lösch-Kontrollen
    echo '<div style="margin-top:20px;display:flex;gap:16px;align-items:center;">';
    echo '<label><input type="checkbox" name="skate_force_delete" value="1"> ' . esc_html__('Permanently delete (do not move to trash)', 'skate') . '</label>';
    submit_button( __('Move to trash / Delete', 'skate'), 'delete', 'submit', false );
    echo '</div>';

    echo '</form>';
    echo '</div>';

    // CSS/JS nur einmal einfügen
    skate_print_footer_assets_once();
}

/**
 * Massenlöschung (Server-seitig inkl. Nachkommen)
 */
function skate_handle_bulk_delete() {
    if ( ! isset($_POST['skate_nonce']) || ! wp_verify_nonce( $_POST['skate_nonce'], 'skate_bulk_delete' ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__('Invalid security token.', 'skate') . '</p></div>';
        return;
    }
    if ( ! current_user_can('delete_pages') ) {
        echo '<div class="notice notice-error"><p>' . esc_html__('No permission to delete pages.', 'skate') . '</p></div>';
        return;
    }

    $ids = isset($_POST['skate_ids']) ? array_map('intval', (array) $_POST['skate_ids']) : [];
    if ( empty($ids) ) {
        echo '<div class="notice notice-warning"><p>' . esc_html__('No pages selected.', 'skate') . '</p></div>';
        return;
    }

    // Nachkommen hinzufügen
    $all = [];
    foreach ( $ids as $id ) {
        $all[] = $id;
        $desc = get_pages([
            'post_type'   => 'page',
            'post_status' => 'any',
            'child_of'    => $id,
            'fields'      => 'ids',
        ]);
        if ( $desc ) $all = array_merge($all, $desc);
    }
    $all = array_values(array_unique(array_map('intval', $all)));

    $force = ! empty($_POST['skate_force_delete']);
    $ok = 0; $err = 0;

    foreach ( $all as $post_id ) {
        if ( ! current_user_can('delete_post', $post_id) ) { $err++; continue; }
        $res = $force ? wp_delete_post($post_id, true) : wp_trash_post($post_id);
        if ( $res ) $ok++; else $err++;
    }

    $msg = $force ? __('Permanently deleted', 'skate') : __('Moved to trash', 'skate');
    $class = $err ? 'notice-warning' : 'notice-success';
    echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' .
        sprintf( esc_html__('%1$s: %2$d successful, %3$d errors.', 'skate'), $msg, intval($ok), intval($err) ) .
        '</p></div>';
}

/**
 * QA summary badges for a group header
 */
function skate_qa_summary_html( array $pages ): string {
    if ( empty($pages) ) return '';
    $ids = array_map( fn($p) => $p->ID, $pages );
    update_meta_cache( 'post', $ids );
    $counts = [ 'ok' => 0, 'corrections' => 0, 'blocked' => 0 ];
    foreach ( $pages as $p ) {
        $s = get_post_meta( $p->ID, '_skate_qs_status', true );
        if ( isset($counts[$s]) ) $counts[$s]++;
    }
    $parts = [];
    if ( $counts['ok'] )          $parts[] = '<span class="skate-summary skate-summary--ok">✓ ' . $counts['ok'] . '</span>';
    if ( $counts['corrections'] ) $parts[] = '<span class="skate-summary skate-summary--corrections">⚠ ' . $counts['corrections'] . '</span>';
    if ( $counts['blocked'] )     $parts[] = '<span class="skate-summary skate-summary--blocked">✗ ' . $counts['blocked'] . '</span>';
    return $parts ? ' ' . implode( ' ', $parts ) : '';
}

/**
 * Seiten nach Begriff
 */
function skate_get_pages_for_term( $taxonomy, $term_id ) {
    $q = new WP_Query([
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'post_status'    => ['publish','pending','draft','private'],
        'tax_query'      => [[
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => (array) $term_id,
            'operator' => 'IN',
        ]],
        'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'no_found_rows'  => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ]);
    return $q->posts;
}

/**
 * Seiten ohne Begriff
 */
function skate_get_pages_without_term( $taxonomy ) {
    $q = new WP_Query([
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'post_status'    => ['publish','pending','draft','private'],
        'tax_query'      => [[
            'taxonomy' => $taxonomy,
            'operator' => 'NOT EXISTS',
        ]],
        'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'no_found_rows'  => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ]);
    return $q->posts;
}

/**
 * Baum mit Checkboxen + Inline-Edit + Duplizieren
 * (ohne Assets — die werden einmal global gedruckt)
 */
function skate_render_pages_tree( $pages ) {
    if ( empty($pages) ) return;

    // Group of IDs present on this subset
    $ids_in_set = [];
    foreach ( $pages as $p ) {
        $ids_in_set[ (int) $p->ID ] = true;
    }

    // Index by parent
    $by_parent = [];
    foreach ( $pages as $p ) {
        $parent = (int) $p->post_parent;
        if ( $parent && ! isset($ids_in_set[$parent]) ) {
            $parent = 0; // parent is not on the subset → show as root
        }
        $by_parent[ $parent ][] = $p;
    }

    echo '<ul class="skate-tree" style="margin:8px 0 24px 0;">';
    skate_print_branch( 0, $by_parent );
    echo '</ul>';
}


/**
 * Rekursiver Zweig
 */
function skate_print_branch( $parent_id, $by_parent ) {
    if ( empty( $by_parent[ $parent_id ] ) ) return;

    foreach ( $by_parent[ $parent_id ] as $page ) {
        $view       = get_permalink( $page->ID );
        $status     = $page->post_status !== 'publish' ? ' — ' . ucfirst($page->post_status) : '';
        $dev_status  = get_post_meta( $page->ID, '_skate_dev_status',  true );
        $dev_comment = get_post_meta( $page->ID, '_skate_dev_comment', true );
        $qs_status   = get_post_meta( $page->ID, '_skate_qs_status',   true );
        $qs_comment  = get_post_meta( $page->ID, '_skate_qs_comment',  true );

        echo '<li data-id="' . esc_attr($page->ID) . '">';
        echo '<label class="skate-page">';
        echo '<span class="skate-grip" title="' . esc_attr__('Drag to sort', 'skate') . '">⠿</span>';
        $dot_class = $qs_status ? ' skate-qs--' . $qs_status : '';
        echo '<span class="skate-qa-dot' . esc_attr( $dot_class ) . '" aria-hidden="true">●</span>';
        echo '<input class="skate-check" type="checkbox" name="skate_ids[]" value="' . esc_attr($page->ID) . '">';

        // Link al Frontend (negrita) con data-id para actualizar tras renombrar título
        echo '<a href="' . esc_url($view) . '" target="_blank"><strong class="skate-title-view" data-id="' . esc_attr($page->ID) . '">' . esc_html( get_the_title($page) ) . '</strong></a>';

        // Meta: TÍTULO editable / SLUG editable
        echo '<span class="skate-meta"> ' .
            '<button type="button" class="skate-title-btn" data-id="' . esc_attr($page->ID) . '" data-title="' . esc_attr( get_the_title($page) ) . '">' . esc_html( get_the_title($page) ) . '</button>' .
            ' / ' .
            '<button type="button" class="skate-slug-btn" data-id="' . esc_attr($page->ID) . '" data-slug="' . esc_attr($page->post_name) . '">' . esc_html( $page->post_name ) . '</button>' .
            $status .
            '</span>';

        // Acciones: duplicar + editar
        echo '<span class="skate-actions">';
        echo '<button type="button" class="skate-action-link skate-dup-btn" data-id="' . esc_attr($page->ID) . '" title="' . esc_attr__('Duplicate', 'skate') . '">⧉</button>';
        echo '<a href="' . esc_url( get_edit_post_link($page->ID) ) . '" target="_blank" class="skate-action-link skate-edit-btn" title="' . esc_attr__('Edit page', 'skate') . '">✎</a>';
        echo '</span>';

        // QA controls
        $has_dev_comment = ! empty( $dev_comment );
        $show_dev_input  = ! $has_dev_comment && in_array( $dev_status, ['fragen', 'blocked'], true );
        $has_qs_comment  = ! empty( $qs_comment );
        $show_qs_input   = ! $has_qs_comment && in_array( $qs_status, ['corrections', 'blocked'], true );
        echo '<span class="skate-qa">';
        echo '<select class="skate-dev-select" data-id="' . esc_attr($page->ID) . '" data-state="' . esc_attr($dev_status) . '">';
        foreach ( [ '' => 'Dev —', 'done' => '✓ Done', 'fragen' => '? Questions', 'blocked' => '✗ Block.' ] as $val => $lbl ) {
            echo '<option value="' . esc_attr($val) . '"' . selected( $dev_status, $val, false ) . '>' . esc_html($lbl) . '</option>';
        }
        echo '</select>';
        echo '<input type="text" class="skate-dev-comment' . ( $show_dev_input ? ' skate-visible' : '' ) . '" data-id="' . esc_attr($page->ID) . '" value="' . esc_attr($dev_comment) . '" placeholder="' . esc_attr__('Comment…', 'skate') . '">';
        echo '<span class="skate-dev-comment-icon' . ( $has_dev_comment ? ' skate-visible' : '' ) . '" data-id="' . esc_attr($page->ID) . '" data-comment="' . esc_attr($dev_comment) . '">💬</span>';
        echo '<select class="skate-qs-select" data-id="' . esc_attr($page->ID) . '" data-state="' . esc_attr($qs_status) . '">';
        foreach ( [ '' => 'QS —', 'ok' => '✓ OK', 'corrections' => '⚠ Corr.', 'blocked' => '✗ Block.' ] as $val => $lbl ) {
            echo '<option value="' . esc_attr($val) . '"' . selected( $qs_status, $val, false ) . '>' . esc_html($lbl) . '</option>';
        }
        echo '</select>';
        echo '<input type="text" class="skate-qs-comment' . ( $show_qs_input ? ' skate-visible' : '' ) . '" data-id="' . esc_attr($page->ID) . '" value="' . esc_attr($qs_comment) . '" placeholder="' . esc_attr__('Comment…', 'skate') . '">';
        echo '<span class="skate-qs-comment-icon' . ( $has_qs_comment ? ' skate-visible' : '' ) . '" data-id="' . esc_attr($page->ID) . '" data-comment="' . esc_attr($qs_comment) . '">💬</span>';
        echo '</span>';

        echo '</label>';

        if ( ! empty( $by_parent[ $page->ID ] ) ) {
            echo '<ul>';
            skate_print_branch( $page->ID, $by_parent );
            echo '</ul>';
        }
        echo '</li>';
    }
}

/**
 * CSS/JS global: nur einmal einfügen, mit Delegation
 */
function skate_print_footer_assets_once() {
    static $done = false;
    if ( $done ) return;
    $done = true;

    $nonce_slug    = wp_create_nonce('skate_update_slug');
    $nonce_title   = wp_create_nonce('skate_update_title');
    $nonce_dup     = wp_create_nonce('skate_duplicate_page');
    $nonce_reorder = wp_create_nonce('skate_reorder_pages');
    $nonce_parent  = wp_create_nonce('skate_change_parent');
    $nonce_qa      = wp_create_nonce('skate_qa_status');

    add_action('admin_print_footer_scripts', function () use ($nonce_slug, $nonce_title, $nonce_dup, $nonce_reorder, $nonce_parent, $nonce_qa) {
        ?>
        <style>
            /* View toggle */
            .skate-view-toggle { display:flex; border-bottom:2px solid #dcdcde; margin-bottom:20px; }
            .skate-view-btn { display:inline-flex; align-items:center; padding:10px 20px; font-size:14px; font-weight:500; cursor:pointer; color:#50575e; background:transparent; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; transition:color .15s,border-color .15s; }
            .skate-view-btn:hover:not(.active) { color:#1d2327; }
            .skate-view-btn.active { color:var(--skate-accent); border-bottom-color:var(--skate-accent); font-weight:600; }
            /* Tree */
            .skate-tree { list-style:none; padding-left:0; }
            .skate-tree li { margin: 2px 0; }
            .skate-tree ul { list-style:none; margin-left:18px; padding-left:12px; border-left:1px solid #dcdcdc; }
            .skate-page { display:inline-flex; gap:5px; align-items:center; padding:2px 0; border-radius:6px; }
            .skate-page:hover { background: rgba(0,0,0,0.02); }
            .skate-meta { color:#777; font-size:11px; }
            .skate-check { margin-right:6px; }
            .skate-title-btn, .skate-slug-btn {
                background: transparent; border: 0; color: #2271b1; padding: 0 4px; cursor: pointer; border-radius: 3px;
            }
            .skate-title-btn:hover, .skate-slug-btn:hover { text-decoration: underline; }
            .skate-inline-input { font-size: 11px; padding: 1px 4px; min-width: 160px; }
            .skate-inline-saving { opacity: .6; }
            .skate-badge-ok { color: #198754; margin-left:6px; font-size:11px; }
            .skate-actions { margin-left:10px; display:inline-flex; gap:8px; }
            .skate-action-link { background: transparent; border:0; color:#646970; cursor:pointer; padding:0; font-size:11px; text-decoration: none; }
            .skate-action-link:hover { color:#1d2327; text-decoration: underline; }
            .skate-edit-btn, .skate-edit-btn:hover { text-decoration: none; }
            .skate-edit-btn:hover { color: var(--skate-accent); }
            .skate-dup-btn, .skate-dup-btn:hover { text-decoration: none; }
            .skate-dup-btn:hover { color: var(--skate-accent); }

            /* Drag & Drop */
            .skate-grip { cursor: grab; opacity: 0.35; font-size: 16px; line-height: 1; user-select: none; flex-shrink: 0; }
            .skate-grip:hover { opacity: 0.75; }
            .skate-tree li.ui-sortable-helper { background: #fff; box-shadow: 0 3px 12px rgba(0,0,0,0.15); border-radius: 4px; padding: 4px 6px; list-style: none; }
            .skate-tree li.ui-sortable-placeholder { background: #f0f6fc; border: 1px dashed #a8c4e0; border-radius: 4px; height: 32px; list-style: none; visibility: visible !important; }
            .skate-reorder-saving { opacity: 0.5; pointer-events: none; }
            .skate-leaf-drop { min-height: 28px; margin-left: 18px; padding: 2px 12px; border-left: 2px dashed var(--skate-accent); border-radius: 4px; background: var(--skate-accent-glow); list-style: none; display: none; }
            .skate-dragging .skate-leaf-drop { display: block; }
            .skate-actions .skate-dup-btn { border-left: 1px solid #dcdcdc; margin-left: 4px; padding-left: 8px; font-size: 14px; }

            /* QA status dot */
            .skate-qa-dot { font-size: 9px; color: #c8c8c8; margin-right: 2px; line-height: 1; flex-shrink: 0; }
            .skate-qa-dot.skate-qs--ok          { color: #46b450; }
            .skate-qa-dot.skate-qs--corrections { color: #f0b849; }
            .skate-qa-dot.skate-qs--blocked     { color: #dc3232; }
            /* QA controls group */
            .skate-qa { display: inline-flex; gap: 5px; align-items: center; border-left: 1px solid #dcdcdc; margin-left: 8px; padding-left: 10px; }
            /* Pill selects */
            .skate-dev-select, .skate-qs-select {
                appearance: none; -webkit-appearance: none;
                border: none; border-radius: 20px;
                padding: 2px 10px; font-size: 11px; font-weight: 500;
                cursor: pointer; outline: none;
                transition: background 0.15s, color 0.15s;
            }
            .skate-dev-select[data-state=""]         { background: #c8c8c8; color: #fff; }
            .skate-dev-select[data-state="done"]     { background: #dcfce7; color: #166534; }
            .skate-dev-select[data-state="fragen"]   { background: #fef9c3; color: #854d0e; }
            .skate-dev-select[data-state="blocked"]  { background: #fee2e2; color: #991b1b; }
            .skate-qs-select[data-state=""]            { background: #c8c8c8; color: #fff; }
            .skate-qs-select[data-state="ok"]          { background: #dcfce7; color: #166534; }
            .skate-qs-select[data-state="corrections"] { background: #fef9c3; color: #854d0e; }
            .skate-qs-select[data-state="blocked"]     { background: #fee2e2; color: #991b1b; }
            /* Preview mode — hide QA controls */
            .skate-mode-preview .skate-qa { display: none; }
            .skate-mode-preview .skate-qa-dot { display: none; }
            .skate-mode-preview .skate-summary { display: none; }
            /* Group collapse/expand */
            .skate-group-header { cursor: pointer; user-select: none; display: flex; align-items: center; gap: 8px; }
            .skate-group-header:hover { opacity: .8; }
            .skate-group-chevron { font-size: 11px; display: inline-block; transition: transform 0.2s; line-height: 1; }
            .skate-group-header.skate-collapsed .skate-group-chevron { transform: rotate(-90deg); }
            .skate-group-content.skate-collapsed { display: none; }
            /* QA summary badges */
            .skate-summary { font-size: 11px; font-weight: 500; padding: 1px 8px; border-radius: 20px; }
            .skate-summary--ok          { background: #dcfce7; color: #166534; }
            .skate-summary--corrections { background: #fef9c3; color: #854d0e; }
            .skate-summary--blocked     { background: #fee2e2; color: #991b1b; }
            /* Comment inputs */
            .skate-dev-comment, .skate-qs-comment { font-size: 11px; padding: 2px 6px; width: 130px; border-radius: 4px; display: none; }
            .skate-dev-comment.skate-visible, .skate-qs-comment.skate-visible { display: inline-block; }
            /* Comment icons + CSS tooltip */
            .skate-dev-comment-icon, .skate-qs-comment-icon { display: none; cursor: pointer; font-size: 13px; position: relative; }
            .skate-dev-comment-icon.skate-visible, .skate-qs-comment-icon.skate-visible { display: inline; }
            .skate-dev-comment-icon::after, .skate-qs-comment-icon::after {
                content: attr(data-comment);
                position: absolute; bottom: 130%; left: 50%; transform: translateX(-50%);
                background: #1d2327; color: #fff;
                padding: 4px 8px; border-radius: 4px;
                font-size: 11px; width: max-content; max-width: 220px; white-space: normal;
                pointer-events: none; opacity: 0; transition: opacity 0.15s;
            }
            .skate-dev-comment-icon:hover::after, .skate-qs-comment-icon:hover::after { opacity: 1; }
        </style>
        <script>
            (function(){
                // Nonces globales
                window.SKATE = {
                    nonceSlug:    <?php echo json_encode($nonce_slug); ?>,
                    nonceTitle:   <?php echo json_encode($nonce_title); ?>,
                    nonceDup:     <?php echo json_encode($nonce_dup); ?>,
                    nonceReorder: <?php echo json_encode($nonce_reorder); ?>,
                    nonceParent:  <?php echo json_encode($nonce_parent); ?>,
                    nonceQa:      <?php echo json_encode($nonce_qa); ?>
                };

                // ---------- Delegación: checkboxes (padre → hijos)
                document.addEventListener('change', function(e){
                    if(!e.target.matches('input.skate-check')) return;
                    var li = e.target.closest('li');
                    if(!li) return;
                    var boxes = li.querySelectorAll('ul input.skate-check');
                    boxes.forEach(function(b){ b.checked = e.target.checked; });
                });

                // ---------- Utilidad de edición inline
                function inlineEdit(btn, fieldKey, ajaxAction, dataAttr, nonce){
                    var current = btn.getAttribute('data-' + dataAttr) || '';
                    var input   = document.createElement('input');
                    input.type  = 'text';
                    input.value = current;
                    input.className = 'skate-inline-input';
                    input.setAttribute('data-id', btn.getAttribute('data-id'));
                    input.setAttribute('data-old', current);
                    btn.replaceWith(input);
                    input.focus(); input.select();

                    function submit(){
                        var id  = parseInt(input.getAttribute('data-id'), 10);
                        var val = input.value.trim();
                        if(!id || !val){ cancel(); return; }
                        input.classList.add('skate-inline-saving');

                        var form = new FormData();
                        form.append('action', ajaxAction);
                        form.append('nonce',  nonce);
                        form.append('id',     id);
                        form.append(fieldKey, val);

                        fetch(ajaxurl, { method:'POST', body: form })
                            .then(r => r.json())
                            .then(function(data){
                                input.classList.remove('skate-inline-saving');
                                if(!data || !data.success){
                                    alert((data && data.data && data.data.message) ? data.data.message : 'Error saving.');
                                    cancel();
                                    return;
                                }
                                var btnNew = document.createElement('button');
                                btnNew.type = 'button';
                                btnNew.className = (fieldKey === 'title') ? 'skate-title-btn' : 'skate-slug-btn';
                                btnNew.textContent = data.data[fieldKey];
                                btnNew.setAttribute('data-id', String(id));
                                btnNew.setAttribute('data-' + dataAttr, data.data[fieldKey]);
                                input.replaceWith(btnNew);

                                var ok = document.createElement('span');
                                ok.className = 'skate-badge-ok';
                                ok.textContent = '✓';
                                btnNew.after(ok);
                                setTimeout(function(){ ok.remove(); }, 1200);

                                // Si es TÍTULO, actualizamos también el enlace en negrita
                                if(fieldKey === 'title'){
                                    var viewEl = document.querySelector('.skate-title-view[data-id="' + id + '"]');
                                    if(viewEl){ viewEl.textContent = data.data.title; }
                                }
                            })
                            .catch(function(){
                                input.classList.remove('skate-inline-saving');
                                alert('Error saving.');
                                cancel();
                            });
                    }
                    function cancel(){
                        var btnNew = document.createElement('button');
                        btnNew.type = 'button';
                        btnNew.className = (fieldKey === 'title') ? 'skate-title-btn' : 'skate-slug-btn';
                        btnNew.textContent = input.getAttribute('data-old') || '';
                        btnNew.setAttribute('data-id', input.getAttribute('data-id'));
                        btnNew.setAttribute('data-' + dataAttr, input.getAttribute('data-old') || '');
                        input.replaceWith(btnNew);
                    }
                    input.addEventListener('keydown', function(e){
                        if(e.key === 'Enter') submit();
                        if(e.key === 'Escape') cancel();
                    });
                    input.addEventListener('blur', submit);
                }

                // ---------- Delegación: click para Título/Slug
                document.addEventListener('click', function(e){
                    if(e.target.matches('.skate-title-btn')){
                        e.preventDefault();
                        inlineEdit(e.target, 'title', 'skate_update_title', 'title', SKATE.nonceTitle);
                    }
                    if(e.target.matches('.skate-slug-btn')){
                        e.preventDefault();
                        inlineEdit(e.target, 'slug', 'skate_update_slug', 'slug', SKATE.nonceSlug);
                    }
                });

                // ---------- Delegación: Duplizieren
                document.addEventListener('click', function(e){
                    if(!e.target.matches('.skate-dup-btn')) return;
                    e.preventDefault();
                    var btn = e.target;
                    var li  = btn.closest('li');
                    if(!li) return;
                    var id = parseInt(li.getAttribute('data-id'),10);
                    if(!id) return;

                    btn.disabled = true;

                    var form = new FormData();
                    form.append('action','skate_duplicate_page');
                    form.append('nonce', SKATE.nonceDup);
                    form.append('id', id);

                    fetch(ajaxurl, { method:'POST', body: form })
                        .then(r => r.json())
                        .then(function(data){
                            if(!data || !data.success){
                                alert((data && data.data && data.data.message) ? data.data.message : 'Error duplicating.');
                                return;
                            }
                            var html = ''
                                + '<li data-id="'+ data.data.id +'">'
                                +   '<label class="skate-page">'
                                +     '<span class="skate-grip" title="<?php echo esc_js(__('Drag to sort', 'skate')); ?>">⠿</span>'
                                +     '<span class="skate-qa-dot" aria-hidden="true">●</span>'
                                +     '<input class="skate-check" type="checkbox" name="skate_ids[]" value="'+ data.data.id +'">'
                                +     '<a href="'+ data.data.permalink +'" target="_blank"><strong class="skate-title-view" data-id="'+ data.data.id +'">'+ data.data.title +'</strong></a>'
                                +     '<span class="skate-meta"> '
                                +       '<button type="button" class="skate-title-btn" data-id="'+ data.data.id +'" data-title="'+ data.data.title +'">'+ data.data.title +'</button>'
                                +       ' / '
                                +       '<button type="button" class="skate-slug-btn" data-id="'+ data.data.id +'" data-slug="'+ data.data.slug +'">'+ data.data.slug +'</button>'
                                +     '</span>'
                                +     '<span class="skate-actions">'
                                +       '<button type="button" class="skate-action-link skate-dup-btn" data-id="'+ data.data.id +'" title="<?php echo esc_js(__('Duplicate', 'skate')); ?>">⧉</button>'
                                +       '<a href="'+ data.data.edit_url +'" target="_blank" class="skate-action-link skate-edit-btn" title="<?php echo esc_js(__('Edit page', 'skate')); ?>">✎</a>'
                                +     '</span>'
                                +     '<span class="skate-qa">'
                                +       '<select class="skate-dev-select" data-id="'+ data.data.id +'" data-state=""><option value="">Dev —</option><option value="done">✓ Done</option><option value="fragen">? Questions</option><option value="blocked">✗ Block.</option></select>'
                                +       '<input type="text" class="skate-dev-comment" data-id="'+ data.data.id +'" placeholder="<?php echo esc_js(__('Comment…', 'skate')); ?>">'
                                +       '<span class="skate-dev-comment-icon" data-id="'+ data.data.id +'" data-comment="">💬</span>'
                                +       '<select class="skate-qs-select" data-id="'+ data.data.id +'" data-state=""><option value="">QS —</option><option value="ok">✓ OK</option><option value="corrections">⚠ Corr.</option><option value="blocked">✗ Block.</option></select>'
                                +       '<input type="text" class="skate-qs-comment" data-id="'+ data.data.id +'" placeholder="<?php echo esc_js(__('Comment…', 'skate')); ?>">'
                                +       '<span class="skate-qs-comment-icon" data-id="'+ data.data.id +'" data-comment="">💬</span>'
                                +     '</span>'
                                +   '</label>'
                                + '</li>';

                            var temp = document.createElement('div');
                            temp.innerHTML = html;
                            var newLi = temp.firstChild;

                            if(li.nextSibling){ li.parentNode.insertBefore(newLi, li.nextSibling); }
                            else { li.parentNode.appendChild(newLi); }
                            var gc = li.closest('.skate-group-content');
                            if (gc) updateGroupSummary(gc);

                            // Refresh sortable + button states for new item
                            if(typeof jQuery !== 'undefined'){
                                jQuery(li.parentNode).sortable('refresh');
                            }
                        })
                        .catch(function(){ alert('Error duplicating.'); })
                        .finally(function(){ btn.disabled = false; });
                });

                // ---------- Change parent: shared AJAX
                function changeParent(id, parentId, onSuccess, onError){
                    var form = new FormData();
                    form.append('action',    'skate_change_parent');
                    form.append('nonce',     SKATE.nonceParent);
                    form.append('id',        id);
                    form.append('parent_id', parentId);
                    fetch(ajaxurl, { method:'POST', body: form })
                        .then(function(r){ return r.json(); })
                        .then(function(data){
                            if(!data || !data.success){
                                alert('Error saving hierarchy.');
                                if(onError) onError();
                            } else {
                                if(onSuccess) onSuccess();
                            }
                            if(typeof jQuery !== 'undefined'){
                                jQuery('.skate-tree, .skate-tree ul').sortable('refresh');
                            }
                        })
                        .catch(function(){
                            alert('Error saving hierarchy.');
                            if(onError) onError();
                        });
                }

                // ---------- Drag & Drop: Sortable (jQuery UI)
                function makeSortable($els){
                    $els.not('.ui-sortable').sortable({
                        handle:               '.skate-grip',
                        connectWith:          '.skate-tree, .skate-tree ul, .skate-leaf-drop',
                        axis:                 'y',
                        tolerance:            'pointer',
                        placeholder:          'ui-sortable-placeholder',
                        forcePlaceholderSize: true,

                        start: function(event, ui){
                            // Store original position for DOM revert on AJAX error
                            ui.item.data('origUl',       ui.item.parent());
                            ui.item.data('origNext',     ui.item.next());
                            ui.item.data('origParentId', parseInt(
                                ui.item.parent().closest('li[data-id]').attr('data-id') || 0, 10
                            ));
                            // Inject leaf drop zones on every leaf <li> except the dragged item
                            jQuery('.skate-tree').find('li[data-id]').each(function(){
                                var $li = jQuery(this);
                                if(!$li.children('ul:not(.skate-leaf-drop)').length && !$li.is(ui.item)){
                                    var $drop = jQuery('<ul class="skate-leaf-drop"></ul>');
                                    $li.append($drop);
                                    makeSortable($drop);
                                }
                            });
                            jQuery('.skate-wrap').addClass('skate-dragging');
                        },

                        stop: function(event, ui){
                            jQuery('.skate-wrap').removeClass('skate-dragging');
                            var $ul          = ui.item.parent();
                            var newParentId  = parseInt($ul.closest('li[data-id]').attr('data-id') || 0, 10);
                            var origParentId = ui.item.data('origParentId') || 0;
                            var id           = parseInt(ui.item.attr('data-id'), 10);

                            // Promote leaf-drop to real <ul> if item landed there
                            if($ul.hasClass('skate-leaf-drop')){
                                $ul.removeClass('skate-leaf-drop');
                            }
                            // Remove all remaining empty leaf drops
                            jQuery('.skate-leaf-drop').each(function(){
                                if(!jQuery(this).children('li[data-id]').length){
                                    jQuery(this).sortable('destroy').remove();
                                }
                            });

                            $ul.addClass('skate-reorder-saving');

                            if(newParentId !== origParentId){
                                // Parent changed: update parent, then persist visual order
                                changeParent(id, newParentId,
                                    function(){
                                        var newOrder = [];
                                        $ul.children('li[data-id]').each(function(){
                                            newOrder.push(parseInt(jQuery(this).data('id'), 10));
                                        });
                                        jQuery.post(ajaxurl, {
                                            action: 'skate_reorder_pages',
                                            nonce:  SKATE.nonceReorder,
                                            ids:    newOrder
                                        }).always(function(){
                                            $ul.removeClass('skate-reorder-saving');
                                        });
                                    },
                                    function(){
                                        // Revert DOM
                                        var $origUl   = ui.item.data('origUl');
                                        var $origNext = ui.item.data('origNext');
                                        if($origNext && $origNext.length){
                                            ui.item.insertBefore($origNext);
                                        } else {
                                            $origUl.append(ui.item);
                                        }
                                        $ul.removeClass('skate-reorder-saving');
                                    }
                                );
                            } else {
                                // Same parent: reorder only
                                var ids = [];
                                $ul.children('li[data-id]').each(function(){
                                    ids.push(parseInt(jQuery(this).data('id'), 10));
                                });
                                jQuery.post(ajaxurl, {
                                    action: 'skate_reorder_pages',
                                    nonce:  SKATE.nonceReorder,
                                    ids:    ids
                                })
                                .done(function(r){
                                    if(!r || !r.success){
                                        alert('Error saving order.');
                                        jQuery('.skate-tree, .skate-tree ul').sortable('cancel');
                                    }
                                })
                                .fail(function(){ alert('Error saving order.'); })
                                .always(function(){
                                    $ul.removeClass('skate-reorder-saving');
                                });
                            }
                        }
                    });
                }

                function initSortable(){
                    if(typeof jQuery === 'undefined') return;
                    makeSortable(jQuery('.skate-tree, .skate-tree ul'));
                }

                initSortable();

                // ---------- View toggle: Preview / Develop
                (function(){
                    var wrap    = document.querySelector('.skate-wrap');
                    var stored  = localStorage.getItem('skate_view') || 'develop';
                    function applyView(view) {
                        wrap.classList.toggle('skate-mode-preview', view === 'preview');
                        document.querySelectorAll('.skate-view-btn').forEach(function(btn){
                            btn.classList.toggle('active', btn.getAttribute('data-view') === view);
                        });
                        localStorage.setItem('skate_view', view);
                    }
                    applyView(stored);
                    document.querySelectorAll('.skate-view-btn').forEach(function(btn){
                        btn.addEventListener('click', function(){
                            applyView(btn.getAttribute('data-view'));
                        });
                    });
                })();

                // ---------- Group collapse/expand
                (function(){
                    var LS_KEY   = 'skate_collapsed';
                    var collapsed = {};
                    try { collapsed = JSON.parse(localStorage.getItem(LS_KEY) || '{}'); } catch(e){}

                    document.querySelectorAll('.skate-group-header').forEach(function(h) {
                        var group   = h.getAttribute('data-group');
                        var content = document.getElementById('skate-group-' + group);
                        if (!content) return;

                        if (collapsed[group]) {
                            h.classList.add('skate-collapsed');
                            content.classList.add('skate-collapsed');
                        }

                        h.addEventListener('click', function() {
                            var isNow = !h.classList.contains('skate-collapsed');
                            h.classList.toggle('skate-collapsed', isNow);
                            content.classList.toggle('skate-collapsed', isNow);
                            if (isNow) collapsed[group] = true; else delete collapsed[group];
                            localStorage.setItem(LS_KEY, JSON.stringify(collapsed));
                        });
                    });
                })();

                // ---------- QA: helpers
                function saveQa(id, data) {
                    var form = new FormData();
                    form.append('action', 'skate_update_qa_status');
                    form.append('nonce',  SKATE.nonceQa);
                    form.append('id',     id);
                    for (var k in data) form.append(k, data[k]);
                    fetch(ajaxurl, { method: 'POST', body: form })
                        .then(function(r){ return r.json(); })
                        .then(function(res){ if (!res || !res.success) alert('Error saving QA status.'); })
                        .catch(function(){ alert('Error saving QA status.'); });
                }
                function updateGroupSummary(groupContent) {
                    if (!groupContent) return;
                    var groupId = groupContent.id.replace('skate-group-', '');
                    var header  = document.querySelector('.skate-group-header[data-group="' + groupId + '"]');
                    if (!header) return;
                    var counts = { ok: 0, corrections: 0, blocked: 0 };
                    groupContent.querySelectorAll('.skate-qs-select').forEach(function(sel) {
                        var s = sel.value;
                        if (Object.prototype.hasOwnProperty.call(counts, s)) counts[s]++;
                    });
                    header.querySelectorAll('.skate-summary').forEach(function(b) { b.remove(); });
                    var labels = { ok: '✓ ', corrections: '⚠ ', blocked: '✗ ' };
                    var classes = { ok: 'skate-summary--ok', corrections: 'skate-summary--corrections', blocked: 'skate-summary--blocked' };
                    ['ok','corrections','blocked'].forEach(function(k) {
                        if (!counts[k]) return;
                        var s = document.createElement('span');
                        s.className = 'skate-summary ' + classes[k];
                        s.textContent = labels[k] + counts[k];
                        header.appendChild(s);
                    });
                }

                function updateQaDot(li, status) {
                    var dot = li && li.querySelector(':scope > label > .skate-qa-dot');
                    if (!dot) return;
                    dot.className = 'skate-qa-dot' + (status ? ' skate-qs--' + status : '');
                }

                // Dev select
                document.addEventListener('change', function(e){
                    if (!e.target.matches('.skate-dev-select')) return;
                    var status  = e.target.value;
                    e.target.setAttribute('data-state', status);
                    var li      = e.target.closest('li[data-id]');
                    var cmt     = li && li.querySelector('.skate-dev-comment');
                    var icon    = li && li.querySelector('.skate-dev-comment-icon');
                    var showCmt = status === 'blocked' || status === 'fragen';
                    var hasIcon = icon && icon.classList.contains('skate-visible');
                    if (cmt)  cmt.classList.toggle('skate-visible', showCmt && !hasIcon);
                    if (icon && !showCmt) icon.classList.remove('skate-visible');
                    saveQa(e.target.getAttribute('data-id'), { dev_status: status });
                });

                // QS select
                document.addEventListener('change', function(e){
                    if (!e.target.matches('.skate-qs-select')) return;
                    var status  = e.target.value;
                    e.target.setAttribute('data-state', status);
                    var li      = e.target.closest('li[data-id]');
                    var cmt     = li && li.querySelector('.skate-qs-comment');
                    var icon    = li && li.querySelector('.skate-qs-comment-icon');
                    var showCmt = status === 'corrections' || status === 'blocked';
                    var hasIcon = icon && icon.classList.contains('skate-visible');
                    if (cmt)  cmt.classList.toggle('skate-visible', showCmt && !hasIcon);
                    if (icon && !showCmt) icon.classList.remove('skate-visible');
                    updateQaDot(li, status);
                    updateGroupSummary(e.target.closest('.skate-group-content'));
                    saveQa(e.target.getAttribute('data-id'), { qs_status: status });
                });

                // Comment blur — save + collapse to icon
                document.addEventListener('blur', function(e){
                    var isDev = e.target.matches('.skate-dev-comment');
                    var isQs  = e.target.matches('.skate-qs-comment');
                    if (!isDev && !isQs) return;
                    var val    = e.target.value.trim();
                    var id     = e.target.getAttribute('data-id');
                    var li     = e.target.closest('li[data-id]');
                    var iconSel = isDev ? '.skate-dev-comment-icon' : '.skate-qs-comment-icon';
                    var icon   = li && li.querySelector(iconSel);
                    var field  = isDev ? 'dev_comment' : 'qs_comment';
                    saveQa(id, { [field]: val });
                    if (val && icon) {
                        icon.setAttribute('data-comment', val);
                        icon.classList.add('skate-visible');
                        e.target.classList.remove('skate-visible');
                    } else if (!val && icon) {
                        icon.classList.remove('skate-visible');
                    }
                }, true);

                // Comment icon click — reopen input
                document.addEventListener('click', function(e){
                    var isDev = e.target.matches('.skate-dev-comment-icon');
                    var isQs  = e.target.matches('.skate-qs-comment-icon');
                    if (!isDev && !isQs) return;
                    var li     = e.target.closest('li[data-id]');
                    var cmtSel = isDev ? '.skate-dev-comment' : '.skate-qs-comment';
                    var cmt    = li && li.querySelector(cmtSel);
                    if (!cmt) return;
                    e.target.classList.remove('skate-visible');
                    cmt.classList.add('skate-visible');
                    cmt.focus();
                });

            })();
        </script>
        <?php
    });
}

/* --------------------------
 * AJAX: Slug aktualisieren
 * ------------------------ */
add_action('wp_ajax_skate_update_slug', 'skate_update_slug');
function skate_update_slug(){
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'skate_update_slug' ) ) {
        wp_send_json_error(['message' => __('Invalid security token.', 'skate')], 400);
    }
    $post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    $raw     = isset($_POST['slug']) ? wp_unslash($_POST['slug']) : '';
    if ( ! $post_id || $raw === '' ) {
        wp_send_json_error(['message' => __('Invalid data.', 'skate')], 400);
    }
    if ( ! current_user_can('edit_post', $post_id) ) {
        wp_send_json_error(['message' => __('No permission.', 'skate')], 403);
    }
    $post = get_post($post_id);
    if ( ! $post || $post->post_type !== 'page' ) {
        wp_send_json_error(['message' => __('Post not found or not a page type.', 'skate')], 404);
    }
    $sanitized = sanitize_title($raw);
    if ( $sanitized === '' ) {
        wp_send_json_error(['message' => __('Slug cannot be empty.', 'skate')], 400);
    }
    $unique = wp_unique_post_slug( $sanitized, $post_id, $post->post_status, $post->post_type, $post->post_parent );
    $res = wp_update_post( ['ID' => $post_id, 'post_name' => $unique], true );
    if ( is_wp_error($res) ) {
        wp_send_json_error(['message' => $res->get_error_message()], 500);
    }
    wp_send_json_success([
        'slug'      => $unique,
        'permalink' => get_permalink($post_id),
    ]);
}

/* --------------------------
 * AJAX: Titel aktualisieren
 * ------------------------ */
add_action('wp_ajax_skate_update_title', 'skate_update_title');
function skate_update_title(){
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'skate_update_title' ) ) {
        wp_send_json_error(['message' => __('Invalid security token.', 'skate')], 400);
    }
    $post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    $raw     = isset($_POST['title']) ? wp_unslash($_POST['title']) : '';
    if ( ! $post_id || $raw === '' ) {
        wp_send_json_error(['message' => __('Invalid data.', 'skate')], 400);
    }
    if ( ! current_user_can('edit_post', $post_id) ) {
        wp_send_json_error(['message' => __('No permission.', 'skate')], 403);
    }
    $post = get_post($post_id);
    if ( ! $post || $post->post_type !== 'page' ) {
        wp_send_json_error(['message' => __('Post not found or not a page type.', 'skate')], 404);
    }

    $title = sanitize_text_field($raw);
    if ( $title === '' ) {
        wp_send_json_error(['message' => __('Title cannot be empty.', 'skate')], 400);
    }

    $res = wp_update_post( ['ID' => $post_id, 'post_title' => $title], true );
    if ( is_wp_error($res) ) {
        wp_send_json_error(['message' => $res->get_error_message()], 500 );
    }

    wp_send_json_success([
        'title'     => $title,
        'permalink' => get_permalink($post_id),
    ]);
}

/* --------------------------
 * AJAX: Seite duplizieren (jetzt "publish" por defecto)
 * ------------------------ */
add_action('wp_ajax_skate_duplicate_page', 'skate_duplicate_page');
function skate_duplicate_page(){
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'skate_duplicate_page' ) ) {
        wp_send_json_error(['message' => __('Invalid security token.', 'skate')], 400);
    }
    $post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ( ! $post_id ) {
        wp_send_json_error(['message' => __('Invalid data.', 'skate')], 400);
    }
    if ( ! current_user_can('edit_post', $post_id) || ! current_user_can('edit_pages') ) {
        wp_send_json_error(['message' => __('No permission.', 'skate')], 403);
    }

    $orig = get_post($post_id);
    if ( ! $orig || $orig->post_type !== 'page' ) {
        wp_send_json_error(['message' => __('Post not found or not a page type.', 'skate')], 404);
    }

    // Titel & Slug der Kopie
    $new_title = sprintf(__('%s – Copy', 'skate'), $orig->post_title ? $orig->post_title : __('(no title)', 'skate'));
    $base_slug = sanitize_title($orig->post_name ? $orig->post_name : $orig->post_title);
    if ( $base_slug === '' ) $base_slug = 'page';
    // usar 'publish' para la unicidad acorde al estado final
    $new_slug  = wp_unique_post_slug( $base_slug . '-kopie', 0, 'publish', 'page', $orig->post_parent );

    // Menüreihenfolge: direkt unter dem Original
    $new_order = (int) $orig->menu_order + 1;

    // Platz schaffen: alle Geschwister mit >= new_order um +1 erhöhen
    $siblings = new WP_Query([
        'post_type'      => 'page',
        'post_parent'    => (int) $orig->post_parent,
        'post_status'    => ['publish','pending','draft','private'],
        'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ]);
    if ( $siblings->posts ) {
        foreach ( $siblings->posts as $sid ) {
            if ( $sid == $post_id ) continue;
            $mo = (int) get_post_field('menu_order', $sid);
            if ( $mo >= $new_order ) {
                wp_update_post(['ID' => $sid, 'menu_order' => $mo + 1]);
            }
        }
    }

    // Neue Seite anlegen — PUBLICADA
    $new_id = wp_insert_post([
        'post_type'      => 'page',
        'post_status'    => 'publish',                         // 👈 publicado por defecto
        'post_date'      => current_time('mysql'),
        'post_date_gmt'  => current_time('mysql', 1),
        'post_author'    => get_current_user_id(),
        'post_parent'    => (int) $orig->post_parent,
        'menu_order'     => $new_order,
        'post_title'     => $new_title,
        'post_name'      => $new_slug,
        'post_content'   => $orig->post_content,
        'post_excerpt'   => $orig->post_excerpt,
        'comment_status' => $orig->comment_status,
        'ping_status'    => $orig->ping_status,
    ], true);

    if ( is_wp_error($new_id) ) {
        wp_send_json_error(['message' => $new_id->get_error_message()], 500);
    }

    // Taxonomien kopieren (inkl. zielgruppen/category)
    $taxes = get_object_taxonomies('page');
    foreach ( $taxes as $tax ) {
        $terms = wp_get_object_terms( $post_id, $tax, ['fields' => 'ids'] );
        if ( ! is_wp_error($terms) ) {
            wp_set_object_terms( $new_id, $terms, $tax, false );
        }
    }

    // Metadaten kopieren (inkl. _wp_page_template, etc.)
    $meta = get_post_meta($post_id);
    foreach ( $meta as $key => $vals ) {
        if ( is_array($vals) ) {
            foreach ( $vals as $v ) {
                add_post_meta($new_id, $key, maybe_unserialize($v));
            }
        }
    }

    // Beitragsbild kopieren
    $thumb_id = get_post_thumbnail_id($post_id);
    if ( $thumb_id ) {
        set_post_thumbnail($new_id, $thumb_id);
    }

    wp_send_json_success([
        'id'        => $new_id,
        'title'     => get_the_title($new_id),
        'slug'      => get_post_field('post_name', $new_id),
        'status'    => get_post_status($new_id),
        'permalink' => get_permalink($new_id),
        'edit_url'  => get_edit_post_link($new_id, 'raw'),
    ]);
}

/* --------------------------
 * AJAX: Reihenfolge speichern (menu_order)
 * ------------------------ */
add_action('wp_ajax_skate_reorder_pages', 'skate_reorder_pages');
function skate_reorder_pages() {
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'skate_reorder_pages' ) ) {
        wp_send_json_error(['message' => __('Invalid security token.', 'skate')], 400);
    }
    if ( ! current_user_can('edit_pages') ) {
        wp_send_json_error(['message' => __('No permission.', 'skate')], 403);
    }

    $ids = isset($_POST['ids']) ? array_map('absint', (array) $_POST['ids']) : [];
    if ( empty($ids) ) {
        wp_send_json_error(['message' => __('No pages specified.', 'skate')], 400);
    }

    foreach ( $ids as $order => $id ) {
        if ( ! $id || ! current_user_can('edit_post', $id) ) continue;
        wp_update_post(['ID' => $id, 'menu_order' => $order]);
    }

    wp_send_json_success();
}

/* --------------------------
 * AJAX: QA-Status speichern
 * ------------------------ */
add_action( 'wp_ajax_skate_update_qa_status', 'skate_update_qa_status' );
function skate_update_qa_status(): void {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'skate_qa_status' ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'skate' ) ], 400 );
    }
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_send_json_error( [ 'message' => __( 'No permission.', 'skate' ) ], 403 );
    }

    $id = absint( $_POST['id'] ?? 0 );
    if ( ! $id || ! get_post( $id ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid page ID.', 'skate' ) ], 400 );
    }

    if ( array_key_exists( 'dev_status', $_POST ) ) {
        $allowed_dev = [ '', 'done', 'fragen', 'blocked' ];
        $dev_status  = in_array( $_POST['dev_status'], $allowed_dev, true ) ? $_POST['dev_status'] : '';
        update_post_meta( $id, '_skate_dev_status', $dev_status );
    }
    if ( array_key_exists( 'dev_comment', $_POST ) ) {
        update_post_meta( $id, '_skate_dev_comment', sanitize_text_field( $_POST['dev_comment'] ) );
    }
    if ( array_key_exists( 'qs_status', $_POST ) ) {
        $allowed = [ '', 'ok', 'corrections', 'blocked' ];
        $status  = in_array( $_POST['qs_status'], $allowed, true ) ? $_POST['qs_status'] : '';
        update_post_meta( $id, '_skate_qs_status', $status );
    }
    if ( array_key_exists( 'qs_comment', $_POST ) ) {
        update_post_meta( $id, '_skate_qs_comment', sanitize_text_field( $_POST['qs_comment'] ) );
    }

    wp_send_json_success();
}

/* --------------------------
 * AJAX: Hierarchie ändern (post_parent)
 * ------------------------ */
add_action('wp_ajax_skate_change_parent', 'skate_change_parent');
function skate_change_parent() {
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'skate_change_parent' ) ) {
        wp_send_json_error(['message' => __('Invalid security token.', 'skate')], 400);
    }
    if ( ! current_user_can('edit_pages') ) {
        wp_send_json_error(['message' => __('No permission.', 'skate')], 403);
    }

    $id        = absint( $_POST['id'] ?? 0 );
    $parent_id = absint( $_POST['parent_id'] ?? 0 );

    if ( ! $id ) {
        wp_send_json_error(['message' => __('Invalid page ID.', 'skate')], 400);
    }
    if ( ! current_user_can('edit_post', $id) ) {
        wp_send_json_error(['message' => __('No permission for this page.', 'skate')], 403);
    }

    $page = get_post($id);
    if ( ! $page || $page->post_type !== 'page' ) {
        wp_send_json_error(['message' => __('Page not found.', 'skate')], 404);
    }

    // Prevent circular hierarchy
    if ( $parent_id && $parent_id === $id ) {
        wp_send_json_error(['message' => __('A page cannot be its own parent.', 'skate')], 400);
    }

    // Append after the last sibling of the new parent
    $siblings  = get_pages([
        'post_parent' => $parent_id,
        'post_status' => 'any',
        'sort_column' => 'menu_order',
        'sort_order'  => 'DESC',
        'number'      => 1,
        'exclude'     => [$id],
    ]);
    $new_order = $siblings ? ( (int) $siblings[0]->menu_order + 1 ) : 0;

    $res = wp_update_post([
        'ID'          => $id,
        'post_parent' => $parent_id,
        'menu_order'  => $new_order,
    ], true);

    if ( is_wp_error($res) ) {
        wp_send_json_error(['message' => $res->get_error_message()], 500);
    }

    wp_send_json_success();
}