(function () {
    // === CONFIG ===
    const GROUP_SELECTOR = '.flowchart-switcher';       // class in the Advanced Tabs block
    const ANCHOR_ID = 'flowchart-switcher-full';        // ID of the anchor for scroll behavior

    // === INTERNAL SELECTORS ===
    const BTN_SELECTOR = '.t-btn';
    const LABEL_SELECTOR = '.tabtitlelabel';

    // === STATE ===
    let groupEl = null;
    let suppressHashChange = false;   // ignore hashchange triggered by our own code

    // === UTILS ===
    const slugify = (str) =>
        (str || '')
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .toLowerCase().replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

    const hashSlug = () => (location.hash || '').replace('#', '').trim().toLowerCase();

    const getGroup = () => {
        const el = document.querySelector(GROUP_SELECTOR);
        if (el && el.querySelector(BTN_SELECTOR)) return el;

        // Fallback: near the anchor
        const anchor = document.getElementById(ANCHOR_ID);
        if (anchor) {
            const scope = anchor.closest('section, .gspb_row, .wp-block-greenshift-blocks-row') || document;
            const near = scope.querySelector('.gspb-tabs');
            if (near && near.querySelector(BTN_SELECTOR)) return near;
        }

        // Last fallback
        const candidates = document.querySelectorAll('.gspb-tabs, .gstabs-tabs');
        for (const c of candidates)
            if (c.querySelector && c.querySelector(BTN_SELECTOR)) return c;
        return null;
    };

    const getButtons = (g) => Array.from(g.querySelectorAll(BTN_SELECTOR));
    const getLabels = (g) => Array.from(g.querySelectorAll(LABEL_SELECTOR)).map(el => slugify(el.textContent));
    const getActiveSlug = (g) => {
        const btn = g.querySelector('.t-btn.active');
        return btn ? slugify((btn.querySelector(LABEL_SELECTOR) || btn).textContent) : null;
    };

    // Clean click: let the block handle its own logic
    function clickTabBySlug(g, slug) {
        if (!slug) return false;
        const labels = getLabels(g);
        const idx = labels.indexOf(slug);
        if (idx === -1) return false;

        const btn = getButtons(g)[idx];
        if (!btn) return false;

        if (getActiveSlug(g) === slug) return true; // already active
        btn.click();
        return true;
    }

    // Retry mechanism to handle delayed initialization (mobile-first, assets, etc.)
    function openBySlugWithRetries(g, slug) {
        if (!slug) return;
        const schedule = [0, 80, 200, 400, 900, 1600]; // total ~1.6s
        let i = 0;

        function attempt() {
            if (!g || !slug) return;
            if (getActiveSlug(g) === slug) return; // success
            clickTabBySlug(g, slug);
            i++;
            if (i < schedule.length) setTimeout(attempt, schedule[i]);
        }
        attempt();
    }

    // Offset calculation (admin bar + sticky/fixed header)
    function getStickyOffset() {
        let off = 0;
        const adminBar = document.getElementById('wpadminbar');
        if (adminBar) off += adminBar.offsetHeight;

        const header = document.querySelector('header.is-sticky, header.sticky, .site-header.is-sticky, .site-header.sticky, .sticky-header, [data-sticky-header]');
        if (header) {
            const pos = getComputedStyle(header).position;
            if (pos === 'sticky' || pos === 'fixed') off += header.offsetHeight;
        }
        return off;
    }

    function scrollToAnchor(behavior = 'smooth') {
        const validIds = ['kauf', 'verkauf', 'vermieten', 'mieten', 'investment'];
        const hash = location.hash.toLowerCase();

        // Scroll only if the hash matches one of the valid IDs
        if (!validIds.some(id => hash.includes(id))) return;

        const target = document.getElementById(ANCHOR_ID) || groupEl;
        if (!target) return;

        const y = target.getBoundingClientRect().top + window.pageYOffset - getStickyOffset();
        window.scrollTo({ top: Math.max(0, y), behavior });
    }

    function scheduleScrolls() {
        scrollToAnchor('auto');
        setTimeout(() => scrollToAnchor('smooth'), 0);
        setTimeout(() => scrollToAnchor('smooth'), 250);
    }

    // Wait until buttons exist
    function waitUntilButtons(cb) {
        const start = Date.now(), MAX_MS = 4000, TICK = 25;
        const timer = setInterval(() => {
            const g = getGroup();
            if (g && getButtons(g).length > 0) { clearInterval(timer); cb(g); return; }
            if (Date.now() - start > MAX_MS) { clearInterval(timer); const f = getGroup(); if (f) cb(f); }
        }, TICK);
    }

    // ——— Initialization ———
    function init() {
        waitUntilButtons((g) => {
            groupEl = g;

            // User click: only update hash (avoid re-triggering the same tab)
            const labels = getLabels(groupEl);
            getButtons(groupEl).forEach((btn, i) => {
                btn.addEventListener('click', () => {
                    const slug = labels[i];
                    if (!slug) return;
                    if (location.hash.toLowerCase() !== '#' + slug) {
                        suppressHashChange = true;
                        history.replaceState(null, '', '#' + slug);
                        setTimeout(() => { suppressHashChange = false; }, 250);
                    }
                }, { passive: true });
            });

            // On load with #tabname
            const initial = hashSlug();
            if (initial) {
                openBySlugWithRetries(groupEl, initial);
                scheduleScrolls();
            }

            // Internal links changing hash
            window.addEventListener('hashchange', () => {
                if (suppressHashChange) return;
                const slug = hashSlug();
                if (!slug) return;
                openBySlugWithRetries(groupEl, slug);
                scheduleScrolls();
            }, { passive: true });

            // Post-load reinforcement (for late block init)
            window.addEventListener('load', () => {
                const slug = hashSlug();
                if (slug) setTimeout(() => openBySlugWithRetries(groupEl, slug), 0);
            }, { once: true });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
