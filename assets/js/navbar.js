/**
 * Skate Navbar — vanilla JS
 * Handles: desktop dropdown, hamburger/mobile panel, mobile accordion, Escape key
 */
(function () {
    'use strict';

    var navbar    = document.getElementById('skate-navbar');
    var linkItems = document.querySelectorAll('.skate-navbar__link-item[data-submenu]');

    // ── Set --skate-navbar-bottom so submenus align flush below the bar ───────

    function updateNavbarBottom() {
        if (!navbar) return;
        var rect = navbar.getBoundingClientRect();
        navbar.style.setProperty('--skate-navbar-bottom', rect.bottom + 'px');
    }

    updateNavbarBottom();
    window.addEventListener('resize', updateNavbarBottom, { passive: true });
    window.addEventListener('scroll', updateNavbarBottom, { passive: true });

    // Re-calculate after hide/show transition ends (transform changes getBoundingClientRect)
    if (navbar) {
        navbar.addEventListener('transitionend', function (e) {
            if (e.propertyName === 'transform') updateNavbarBottom();
        });
    }

    // ── Transparent variant: fade to white on scroll ─────────────────────────

    var isTransparent  = navbar && navbar.classList.contains('skate-navbar--transparent');
    var navbarHovered  = false;

    function updateTransparentState() {
        if (!isTransparent || !navbar) return;
        navbar.classList.toggle('skate-navbar--scrolled', window.scrollY > 0 || navbarHovered);
    }

    if (isTransparent && navbar) {
        navbar.addEventListener('mouseenter', function () { navbarHovered = true;  updateTransparentState(); });
        navbar.addEventListener('mouseleave', function () { navbarHovered = false; updateTransparentState(); });
    }

    updateTransparentState();
    window.addEventListener('scroll', updateTransparentState, { passive: true });

    // ── Hide on scroll down / show on scroll up ──────────────────────────────

    var HIDE_THRESHOLD = window.innerWidth < 992 ? 200 : 700; // px from top before hide kicks in
    var lastScrollY    = window.scrollY;

    window.addEventListener('scroll', function () {
        // Don't hide navbar while mobile menu is open
        if (mobileMenu && mobileMenu.classList.contains('is-open')) return;

        var currentY = window.scrollY;
        var diff     = currentY - lastScrollY;

        if (currentY > HIDE_THRESHOLD) {
            if (diff > 0) {
                // Scrolling down — hide navbar
                navbar && navbar.classList.add('skate-navbar--hidden');
                closeAllDropdowns();
            } else {
                // Scrolling up — show navbar
                navbar && navbar.classList.remove('skate-navbar--hidden');
            }
        } else {
            // Above threshold — always visible
            navbar && navbar.classList.remove('skate-navbar--hidden');
        }

        lastScrollY = currentY;
    }, { passive: true });

    // ── Desktop dropdown ─────────────────────────────────────────────────────

    function setOverlay(open) {
        if (navbar) navbar.classList.toggle('skate-navbar--submenu-open', open);
    }

    function anyOpen() {
        return !!document.querySelector('.skate-navbar__link-item[data-submenu].is-open');
    }

    linkItems.forEach(function (item) {
        var closeTimer;

        item.addEventListener('mouseenter', function () {
            clearTimeout(closeTimer);
            closeAllDropdowns(item);
            item.classList.add('is-open');
            setOverlay(true);
        });

        item.addEventListener('mouseleave', function () {
            closeTimer = setTimeout(function () {
                item.classList.remove('is-open');
                if (!anyOpen()) setOverlay(false);
            }, 150);
        });
    });

    function closeAllDropdowns(except) {
        linkItems.forEach(function (item) {
            if (item !== except) {
                item.classList.remove('is-open');
                var trigger = item.querySelector('.skate-navbar__link');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            }
        });
        if (!except) setOverlay(false);
    }

    // Click on overlay closes all
    var desktopOverlay = navbar && navbar.querySelector('.skate-navbar__overlay');
    if (desktopOverlay) {
        desktopOverlay.addEventListener('click', function () {
            closeAllDropdowns();
        });
    }

    // Submenu close buttons
    document.querySelectorAll('.skate-navbar__submenu-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeAllDropdowns();
        });
    });

    // ── Mobile panel ─────────────────────────────────────────────────────────

    var hamburger      = document.querySelector('.skate-navbar__hamburger');
    var mobileMenu     = document.getElementById('skate-mobile-menu');
    var overlay        = mobileMenu && mobileMenu.querySelector('.skate-navbar__mobile-overlay');
    var closeBtn       = mobileMenu && mobileMenu.querySelector('.skate-navbar__mobile-close');
    var fullscreenMenu = document.getElementById('skate-fullscreen-menu');
    var fullscreenClose = fullscreenMenu && fullscreenMenu.querySelector('.skate-navbar__fullscreen-close');

    var savedScrollY = 0;
    var lastFocusedBeforeMenu = null;

    function getFocusableElements(container) {
        return Array.from(container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter(function (el) { return !el.closest('[hidden]'); });
    }

    function openMobileMenu() {
        if (!mobileMenu) return;
        lastFocusedBeforeMenu = document.activeElement;
        savedScrollY = window.scrollY;
        document.body.style.top = '-' + savedScrollY + 'px';
        document.body.classList.add('skate-menu-open');
        mobileMenu.classList.add('is-open');
        mobileMenu.setAttribute('aria-hidden', 'false');
        if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
        // Move focus into panel
        var panel = mobileMenu.querySelector('.skate-navbar__mobile-panel');
        if (closeBtn) closeBtn.focus();
        else if (panel) { var first = getFocusableElements(panel)[0]; if (first) first.focus(); }
    }

    function closeMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.remove('is-open');
        mobileMenu.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('skate-menu-open');
        document.body.style.top = '';
        window.scrollTo(0, savedScrollY);
        if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
        // Return focus to trigger
        if (lastFocusedBeforeMenu) { lastFocusedBeforeMenu.focus(); lastFocusedBeforeMenu = null; }
    }

    function openFullscreenMenu() {
        if (!fullscreenMenu) return;
        lastFocusedBeforeMenu = document.activeElement;
        savedScrollY = window.scrollY;
        document.body.style.top = '-' + savedScrollY + 'px';
        document.body.classList.add('skate-menu-open');
        fullscreenMenu.classList.add('is-open');
        fullscreenMenu.setAttribute('aria-hidden', 'false');
        if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
        if (fullscreenClose) fullscreenClose.focus();
    }

    function closeFullscreenMenu() {
        if (!fullscreenMenu) return;
        fullscreenMenu.classList.remove('is-open');
        fullscreenMenu.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('skate-menu-open');
        document.body.style.top = '';
        window.scrollTo(0, savedScrollY);
        if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
        if (lastFocusedBeforeMenu) { lastFocusedBeforeMenu.focus(); lastFocusedBeforeMenu = null; }
    }

    if (hamburger) {
        hamburger.addEventListener('click', function () {
            if (fullscreenMenu) {
                var isOpen = fullscreenMenu.classList.contains('is-open');
                isOpen ? closeFullscreenMenu() : openFullscreenMenu();
            } else {
                var isOpen = mobileMenu && mobileMenu.classList.contains('is-open');
                isOpen ? closeMobileMenu() : openMobileMenu();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeMobileMenu);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeMobileMenu);
    }

    if (fullscreenClose) {
        fullscreenClose.addEventListener('click', closeFullscreenMenu);
    }

    // ── Mobile accordion ─────────────────────────────────────────────────────

    var mobileHeaders = document.querySelectorAll('.skate-navbar__mobile-link-header');

    mobileHeaders.forEach(function (header) {
        header.addEventListener('click', function () {
            var parent = header.closest('.skate-navbar__mobile-link-item');
            if (!parent) return;
            var isExpanded = parent.classList.contains('is-expanded');
            // Close all others
            document.querySelectorAll('.skate-navbar__mobile-link-item.is-expanded').forEach(function (el) {
                el.classList.remove('is-expanded');
                var btn = el.querySelector('.skate-navbar__mobile-link-header');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
            if (!isExpanded) {
                parent.classList.add('is-expanded');
                header.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // ── Focus trap inside mobile panel ───────────────────────────────────────

    if (mobileMenu) {
        mobileMenu.addEventListener('keydown', function (e) {
            if (!mobileMenu.classList.contains('is-open')) return;
            if (e.key !== 'Tab') return;
            var panel = mobileMenu.querySelector('.skate-navbar__mobile-panel');
            var focusable = getFocusableElements(panel);
            if (!focusable.length) return;
            var first = focusable[0];
            var last  = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last)  { e.preventDefault(); first.focus(); }
            }
        });
    }

    // ── Desktop dropdown — keyboard navigation ────────────────────────────────

    function openDropdown(item) {
        closeAllDropdowns(item);
        item.classList.add('is-open');
        setOverlay(true);
        var trigger = item.querySelector('.skate-navbar__link');
        if (trigger) trigger.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown(item) {
        item.classList.remove('is-open');
        var trigger = item.querySelector('.skate-navbar__link');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
        if (!anyOpen()) setOverlay(false);
    }

    function getSubmenuLinks(item) {
        var sub = item.querySelector('.skate-navbar__submenu');
        return sub ? Array.from(sub.querySelectorAll('a[href], button:not([disabled])')) : [];
    }

    linkItems.forEach(function (item) {
        var trigger = item.querySelector('.skate-navbar__link');
        if (!trigger) return;

        // Enter / Space — open dropdown and focus first item
        trigger.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ' && e.key !== 'ArrowDown') return;
            e.preventDefault();
            if (!item.classList.contains('is-open')) {
                openDropdown(item);
            }
            var items = getSubmenuLinks(item);
            if (items.length) items[0].focus();
        });

        // Close when focus leaves the entire link-item
        item.addEventListener('focusout', function (e) {
            setTimeout(function () {
                if (!item.contains(document.activeElement)) closeDropdown(item);
            }, 0);
        });
    });

    // Arrow up/down inside open submenu
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
        var openItem = document.querySelector('.skate-navbar__link-item[data-submenu].is-open');
        if (!openItem) return;
        var items = getSubmenuLinks(openItem);
        if (!items.length) return;
        var idx = items.indexOf(document.activeElement);
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            items[idx < items.length - 1 ? idx + 1 : 0].focus();
        } else {
            e.preventDefault();
            if (idx <= 0) {
                // Return to trigger
                var trigger = openItem.querySelector('.skate-navbar__link');
                closeDropdown(openItem);
                if (trigger) trigger.focus();
            } else {
                items[idx - 1].focus();
            }
        }
    });

    // ── Close mobile menu on resize above breakpoint ─────────────────────────

    window.addEventListener('resize', function () {
        if (window.innerWidth > 991) closeMobileMenu();
    }, { passive: true });

    // ── Escape key ───────────────────────────────────────────────────────────

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (fullscreenMenu && fullscreenMenu.classList.contains('is-open')) {
            closeFullscreenMenu();
            return;
        }
        var openItem = document.querySelector('.skate-navbar__link-item[data-submenu].is-open');
        if (openItem) {
            var trigger = openItem.querySelector('.skate-navbar__link');
            closeAllDropdowns();
            if (trigger) trigger.focus();
        }
    });

    // ── WP admin bar offset on mobile (≤600px, admin bar scrolls with page) ────
    var adminBar = document.getElementById('wpadminbar');
    if (adminBar && navbar) {
        function syncAdminBarOffset() {
            if (window.innerWidth > 600) {
                navbar.style.top = '';
                return;
            }
            var h      = adminBar.offsetHeight;
            var offset = Math.max(0, h - window.scrollY);
            navbar.style.top = offset + 'px';
        }
        window.addEventListener('scroll', syncAdminBarOffset, { passive: true });
        window.addEventListener('resize', syncAdminBarOffset, { passive: true });
        syncAdminBarOffset();
    }

}());
