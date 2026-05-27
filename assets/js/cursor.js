(function () {
	'use strict';

	var cfg = window.skateCursor;
	if (!cfg) return;

	var STYLE = cfg.style;

	// ── Create elements ──────────────────────────────────────────────────────────
	var dot  = null;
	var ring = null;

	if (STYLE === 'circle') {
		dot = document.createElement('div');
		dot.className = 'skate-cur';
	} else if (STYLE === 'dot-ring') {
		dot  = document.createElement('div');
		dot.className = 'skate-cur-dot';
		ring = document.createElement('div');
		ring.className = 'skate-cur-ring';
	} else {
		return;
	}

	document.body.appendChild(dot);
	if (ring) document.body.appendChild(ring);

	// ── State ────────────────────────────────────────────────────────────────────
	var targetX = -300, targetY = -300;
	var dotX    = -300, dotY   = -300;
	var ringX   = -300, ringY  = -300;

	var dotScale  = 1, dotScaleTarget  = 1;
	var ringScale = 1, ringScaleTarget = 1;

	// ── RAF loop ─────────────────────────────────────────────────────────────────
	function tick() {
		dotScale  += (dotScaleTarget  - dotScale)  * 0.14;
		ringScale += (ringScaleTarget - ringScale) * 0.10;

		if (STYLE === 'circle') {
			dotX += (targetX - dotX) * 0.14;
			dotY += (targetY - dotY) * 0.14;
			dot.style.transform = 'translate3d(' + dotX.toFixed(1) + 'px,' + dotY.toFixed(1) + 'px,0) scale(' + dotScale.toFixed(3) + ')';

		} else if (STYLE === 'dot-ring') {
			// Dot: snaps instantly to cursor
			dot.style.transform  = 'translate3d(' + targetX.toFixed(1) + 'px,' + targetY.toFixed(1) + 'px,0) scale(' + dotScale.toFixed(3) + ')';
			// Ring: lerps behind
			ringX += (targetX - ringX) * 0.09;
			ringY += (targetY - ringY) * 0.09;
			ring.style.transform = 'translate3d(' + ringX.toFixed(1) + 'px,' + ringY.toFixed(1) + 'px,0) scale(' + ringScale.toFixed(3) + ')';
		}

		window.requestAnimationFrame(tick);
	}

	window.requestAnimationFrame(tick);

	// ── Mouse tracking ────────────────────────────────────────────────────────────
	document.addEventListener('mousemove', function (e) {
		targetX = e.clientX;
		targetY = e.clientY;
	}, { passive: true });

	document.addEventListener('mouseleave', function () {
		targetX = -300; targetY = -300;
	});

	// ── Hover on interactive elements ─────────────────────────────────────────────
	var HOVER_SEL = 'a, button, [role="button"], input[type="submit"], label[for], select';

	document.addEventListener('mouseover', function (e) {
		if (!e.target.closest) return;
		if (!e.target.closest(HOVER_SEL)) return;
		dotScaleTarget  = 0;
		ringScaleTarget = 0;
	});

	document.addEventListener('mouseout', function (e) {
		if (!e.target.closest) return;
		if (!e.target.closest(HOVER_SEL)) return;
		dotScaleTarget  = 1;
		ringScaleTarget = 1;
	});
}());
