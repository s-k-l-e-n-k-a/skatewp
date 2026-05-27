(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.skate-custom-controls').forEach(function (container) {
			var swiperEl = container.querySelector('.swiper');
			if (!swiperEl) return;

			var prevBtns = Array.from(container.querySelectorAll('.skate-prev'));
			var nextBtns = Array.from(container.querySelectorAll('.skate-next'));

			function updateEdge(s) {
				prevBtns.forEach(function (b) { b.classList.toggle('is-edge', s.isBeginning && !s.params.loop); });
				nextBtns.forEach(function (b) { b.classList.toggle('is-edge', s.isEnd      && !s.params.loop); });
			}

			function hookSwiper(s) {
				updateEdge(s);
				s.on('slideChange', function () { updateEdge(s); });
			}

			// Swiper adds class `swiper-initialized` after init — watch for it.
			if (swiperEl.swiper) {
				hookSwiper(swiperEl.swiper);
			} else {
				var obs = new MutationObserver(function (_, observer) {
					if (swiperEl.swiper) {
						observer.disconnect();
						hookSwiper(swiperEl.swiper);
					}
				});
				obs.observe(swiperEl, { attributes: true });
			}

			prevBtns.forEach(function (btn) {
				btn.addEventListener('click', function () {
					var s = swiperEl.swiper; if (s) s.slidePrev();
				});
			});

			nextBtns.forEach(function (btn) {
				btn.addEventListener('click', function () {
					var s = swiperEl.swiper; if (s) s.slideNext();
				});
			});
		});
	});
}());
