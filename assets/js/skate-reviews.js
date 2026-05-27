(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.skate-reviews').forEach(initReviews);
	});

	function initReviews(root) {
		var viewport = root.querySelector('.skate-reviews__viewport');
		var track    = root.querySelector('.skate-reviews__track');
		var btnPrev  = root.querySelector('.skate-reviews__btn--prev');
		var btnNext  = root.querySelector('.skate-reviews__btn--next');

		if (!track || !viewport) return;

		var cards   = Array.from(track.children);
		var total   = cards.length;
		var current = 0;

		function goTo(index) {
			// CSS (cqw) controls card width — read the actual rendered value.
			var cw   = cards[0] ? cards[0].getBoundingClientRect().width : 0;
			var gap  = parseFloat(getComputedStyle(track).columnGap) || 20;
			var vw   = viewport.getBoundingClientRect().width;
			var cols = cw > 0 ? Math.round(vw / cw) : 3;

			var maxIndex = Math.max(0, total - cols);
			current = Math.max(0, Math.min(index, maxIndex));

			track.style.transform = 'translateX(-' + (current * (cw + gap)) + 'px)';
			if (btnPrev) btnPrev.disabled = current === 0;
			if (btnNext) btnNext.disabled = current >= maxIndex;
		}

		// Wait for first paint so cqw widths are computed before reading them.
		requestAnimationFrame(function () { goTo(0); });

		if (btnPrev) btnPrev.addEventListener('click', function () { goTo(current - 1); });
		if (btnNext) btnNext.addEventListener('click', function () { goTo(current + 1); });

		var resizeTimer;
		window.addEventListener('resize', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				current = 0;
				goTo(0);
			}, 150);
		});
	}
}());
