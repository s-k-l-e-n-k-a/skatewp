(function () {
    var cfg = window.skateScrollReveal;
    if (!cfg || !cfg.enabled) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var preset   = cfg.preset || 'fade-up';
    var duration = cfg.duration || 600;
    var delay    = cfg.delay || 0;
    var repeat   = cfg.repeat || false;

    var isMobile = window.innerWidth < 768;
    var rootMargin = isMobile ? '0px 0px -5% 0px' : '0px 0px -10% 0px';

    var style = document.createElement('style');
    style.textContent = [
        '.skate-reveal{opacity:0;transition:opacity ' + duration + 'ms ease, transform ' + duration + 'ms ease;',
        preset === 'fade-up'   ? 'transform:translate3d(0,40px,0)}'  : '',
        preset === 'slide-in'  ? 'transform:translate3d(-30px,0,0)}' : '',
        preset === 'fade-in'   ? 'transform:none}'                   : '',
        '.skate-reveal.is-visible{opacity:1;transform:none}'
    ].join('');
    document.head.appendChild(style);

    function observe() {
        var els = document.querySelectorAll('[data-type="content-area-component"]');
        if (!els.length) return;

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var el = entry.target;
                if (entry.isIntersecting) {
                    if (delay) {
                        setTimeout(function () { el.classList.add('is-visible'); }, delay);
                    } else {
                        el.classList.add('is-visible');
                    }
                    if (!repeat) io.unobserve(el);
                } else if (repeat) {
                    el.classList.remove('is-visible');
                }
            });
        }, { rootMargin: rootMargin, threshold: 0 });

        els.forEach(function (el) {
            el.classList.add('skate-reveal');
            io.observe(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', observe);
    } else {
        observe();
    }
})();
