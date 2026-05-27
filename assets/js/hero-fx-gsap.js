(function () {
	'use strict';

	if (typeof gsap === 'undefined') return;

	if (typeof ScrollTrigger !== 'undefined') {
		gsap.registerPlugin(ScrollTrigger);
	}

	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	// ── Word split ─────────────────────────────────────────────────────────────
	// Wraps each word in a clip container (overflow:hidden) + inner span.
	// DOM traversal approach handles mixed HTML (<br>, <strong>, etc.) safely.
	function wrapWords(el) {
		Array.from(el.childNodes).forEach(function (node) {
			if (node.nodeType === Node.ELEMENT_NODE) {
				// Recurse into <strong>, <em>, <br>, etc. so text inside them gets wrapped too.
				wrapWords(node);
				return;
			}
			if (node.nodeType !== Node.TEXT_NODE) return;
			var frag = document.createDocumentFragment();
			node.textContent.split(/(\s+)/).forEach(function (part) {
				if (/^\s+$/.test(part)) {
					frag.appendChild(document.createTextNode(part));
					return;
				}
				if (!part) return;
				var clip = document.createElement('span');
				clip.style.cssText = 'display:inline-block;overflow:hidden;vertical-align:bottom;';
				var word = document.createElement('span');
				word.className = 'skate-word';
				word.style.cssText = 'display:inline-block;';
				word.textContent = part;
				clip.appendChild(word);
				frag.appendChild(clip);
			});
			node.parentNode.replaceChild(frag, node);
		});
	}

	// ── Entrance sequence ──────────────────────────────────────────────────────
	var heroes = document.querySelectorAll('.skate-hero-fx, .skate-breathe-fx');

	heroes.forEach(function (hero) {
		var eyebrow = hero.querySelector('.wp-block-paragraph');
		var h1      = hero.querySelector('h1');
		var h2      = hero.querySelector('h2');
		var buttons = hero.querySelector('.wp-block-buttons');

		if (h1) wrapWords(h1);

		// Hide elements before animating — done synchronously to avoid flash
		if (eyebrow) gsap.set(eyebrow, { autoAlpha: 0, y: 14 });
		if (h1)      gsap.set(h1.querySelectorAll('.skate-word'), { y: '115%' });
		if (h2)      gsap.set(h2, { autoAlpha: 0, y: 18 });
		if (buttons) gsap.set(buttons, { autoAlpha: 0, y: 14 });

		var tl = gsap.timeline({ defaults: { ease: 'power3.out' }, delay: 0.15 });

		if (eyebrow) {
			tl.to(eyebrow, { autoAlpha: 1, y: 0, duration: 0.55 });
		}

		if (h1) {
			tl.to(
				h1.querySelectorAll('.skate-word'),
				{ y: '0%', duration: 0.80, stagger: 0.06 },
				eyebrow ? '-=0.25' : '0'
			);
		}

		if (h2) {
			tl.to(h2, { autoAlpha: 1, y: 0, duration: 0.60 }, '-=0.40');
		}

		if (buttons) {
			tl.to(buttons, { autoAlpha: 1, y: 0, duration: 0.45 }, '-=0.30');
		}
	});

	// ── Magnetic button ────────────────────────────────────────────────────────
	// Move the whole .wp-block-button div (border + text together) toward cursor.
	var magnetBtns = document.querySelectorAll(
		'.skate-hero-fx .wp-block-button, .skate-breathe-fx .wp-block-button'
	);

	magnetBtns.forEach(function (btn) {
		var PULL = 0.28;

		btn.addEventListener('mousemove', function (e) {
			var r = btn.getBoundingClientRect();
			gsap.to(btn, {
				x: (e.clientX - r.left - r.width  / 2) * PULL,
				y: (e.clientY - r.top  - r.height / 2) * PULL,
				duration: 0.4,
				ease: 'power2.out',
				overwrite: true
			});
		}, { passive: true });

		btn.addEventListener('mouseleave', function () {
			gsap.to(btn, {
				x: 0, y: 0,
				duration: 0.7,
				ease: 'elastic.out(1, 0.5)',
				overwrite: true
			});
		}, { passive: true });
	});

	// ── Scroll scrub — content drifts up and fades as hero scrolls away ────────
	if (typeof ScrollTrigger !== 'undefined') {
		heroes.forEach(function (hero) {
			var content = hero.querySelector('[data-type="content-area-component"]');
			if (!content) return;

			gsap.to(content, {
				y: -55,
				autoAlpha: 0,
				ease: 'none',
				scrollTrigger: {
					trigger: hero,
					start: 'top top',
					end: '38% top',
					scrub: true
				}
			});
		});
	}

}());
