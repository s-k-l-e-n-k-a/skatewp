(function () {
	'use strict';

	var cfg = window.skateParallax;
	if (!cfg) return;

	var speed   = typeof cfg.speed   === 'number' ? cfg.speed   : 1.4;
	var fade    = cfg.fade === true;
	var fadeEnd = typeof cfg.fadeEnd === 'number' ? cfg.fadeEnd : 60;

	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	var elements = [];

	function collectElements() {
		elements = [];
		document.querySelectorAll('.skate-parallax').forEach(function (el) {
			var rect = el.getBoundingClientRect();
			elements.push({
				el:         el,
				initialTop: rect.top + window.scrollY,
			});
			el.style.willChange = 'transform, opacity';
		});
	}

	collectElements();
	if (!elements.length) return;

	var ticking = false;

	function applyParallax() {
		var scrollY = window.scrollY;
		var vh      = window.innerHeight;

		elements.forEach(function (item) {
			// Skip elements whose natural position is well below the fold
			if (item.initialTop > scrollY + vh * 2) return;

			// delta = how far past the element's natural entry point we have scrolled
			var delta = scrollY - Math.max(0, item.initialTop - vh);
			if (delta < 0) delta = 0;

			item.el.style.transform = 'translateY(' + (-(delta * (speed - 1))).toFixed(2) + 'px)';

			if (fade) {
				var fadeDistance = (fadeEnd / 100) * vh;
				item.el.style.opacity = fadeDistance > 0
					? Math.max(0, 1 - delta / fadeDistance).toFixed(3)
					: '1';
			}
		});

		ticking = false;
	}

	window.addEventListener('scroll', function () {
		if (!ticking) {
			window.requestAnimationFrame(applyParallax);
			ticking = true;
		}
	}, { passive: true });

	window.addEventListener('resize', function () {
		collectElements();
		window.requestAnimationFrame(applyParallax);
	}, { passive: true });

	window.requestAnimationFrame(applyParallax);
}());
