(function () {
	'use strict';

	var elements = document.querySelectorAll('.skate-breathe-fx');
	if (!elements.length) return;

	// ── Color helpers ──────────────────────────────────────────────────────────────
	function hexToRgb(hex) {
		hex = hex.trim().replace('#', '');
		if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
		return [
			parseInt(hex.substr(0, 2), 16) / 255,
			parseInt(hex.substr(2, 2), 16) / 255,
			parseInt(hex.substr(4, 2), 16) / 255
		];
	}

	function getThemeColors() {
		var cs = getComputedStyle(document.documentElement);
		var h1 = cs.getPropertyValue('--wp--preset--color--main-color').trim();
		var h2 = cs.getPropertyValue('--wp--preset--color--secondary-color').trim();
		var c1 = (h1 && h1.charAt(0) === '#') ? hexToRgb(h1) : [0.09, 0.15, 0.23]; // #17263a
		var c2 = (h2 && h2.charAt(0) === '#') ? hexToRgb(h2) : [0.84, 0.70, 0.43]; // #d6b36d
		return { c1: c1, c2: c2 };
	}

	// ── Reduced-motion fallback ────────────────────────────────────────────────────
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		var fc = getThemeColors();
		function toHex(c) {
			return '#' + c.map(function (v) {
				return ('0' + Math.round(v * 255).toString(16)).slice(-2);
			}).join('');
		}
		elements.forEach(function (el) {
			el.style.backgroundImage = 'linear-gradient(135deg,' + toHex(fc.c1) + ' 0%,' + toHex(fc.c2) + ' 100%)';
		});
		return;
	}

	// ── Vertex shader ──────────────────────────────────────────────────────────────
	var VERT_SRC = [
		'attribute vec2 a_pos;',
		'varying vec2 v_uv;',
		'void main(){',
		'  v_uv=a_pos*0.5+0.5;',
		'  gl_Position=vec4(a_pos,0.0,1.0);',
		'}'
	].join('\n');

	// ── Fragment shader — Gaussian aurora blobs (purely ambient) ─────────────────
	//
	// Each blob is a radial Gaussian: color * exp(-distance² * falloff)
	// Positions follow Lissajous curves — irrational freq pairs ensure the path
	// never exactly repeats, so motion feels organic indefinitely.
	// No mouse interaction — the effect lives on its own.
	var FRAG_BREATHE = [
		'precision mediump float;',
		'varying vec2 v_uv;',
		'uniform float u_time;',
		'uniform float u_aspect;',
		'uniform vec3  u_color1;',
		'uniform vec3  u_color2;',
		'uniform vec3  u_base;',

		'float hash(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}',
		'float blob(vec2 uv,vec2 c,float f){vec2 d=uv-c;return exp(-dot(d,d)*f);}',

		'void main(){',
		'  vec2 uv=vec2(v_uv.x*u_aspect,v_uv.y);',
		'  float A=u_aspect;',

		'  vec2 b1=vec2(',
		'    0.5*A + 0.40*A*sin(u_time*0.31),',
		'    0.48  + 0.32 *sin(u_time*0.27+1.10)',
		'  );',
		'  vec2 b2=vec2(',
		'    0.5*A + 0.35*A*sin(u_time*0.41+2.30),',
		'    0.52  + 0.34 *sin(u_time*0.37+0.70)',
		'  );',
		'  vec2 b3=vec2(',
		'    0.5*A + 0.28*A*sin(u_time*0.23+4.10),',
		'    0.50  + 0.26 *sin(u_time*0.29+3.50)',
		'  );',

		'  vec3 col=u_base;',
		'  col+=u_color1                   *blob(uv,b1,6.5)*0.95;',
		'  col+=u_color2                   *blob(uv,b2,5.5)*0.90;',
		'  col+=mix(u_color1,u_color2,0.55)*blob(uv,b3,8.0)*0.80;',

		'  vec2 cv=v_uv-0.5;',
		'  col*=clamp(1.0-dot(cv,cv)*2.4,0.0,1.0);',
		'  col=clamp(col+hash(v_uv+fract(u_time*13.7))*0.028-0.014,0.0,1.0);',

		'  gl_FragColor=vec4(col,1.0);',
		'}'
	].join('\n');

	// ── WebGL helpers ──────────────────────────────────────────────────────────────
	function mkShader(gl, type, src) {
		var s = gl.createShader(type);
		gl.shaderSource(s, src);
		gl.compileShader(s);
		if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) { gl.deleteShader(s); return null; }
		return s;
	}

	function mkProgram(gl, vert, frag) {
		var p = gl.createProgram();
		gl.attachShader(p, vert);
		gl.attachShader(p, frag);
		gl.linkProgram(p);
		if (!gl.getProgramParameter(p, gl.LINK_STATUS)) { gl.deleteProgram(p); return null; }
		return p;
	}

	// ── Init one element ───────────────────────────────────────────────────────────
	function initBreathe(el) {
		var colors = getThemeColors();
		var base   = [0.03, 0.03, 0.05];

		var canvas = document.createElement('canvas');
		canvas.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;'
		                      + 'pointer-events:none;display:block;z-index:-1;';
		el.appendChild(canvas);

		var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
		if (!gl) { el.removeChild(canvas); return; }

		var vert = mkShader(gl, gl.VERTEX_SHADER,   VERT_SRC);
		var frag = mkShader(gl, gl.FRAGMENT_SHADER, FRAG_BREATHE);
		if (!vert || !frag) { el.removeChild(canvas); return; }

		var prog = mkProgram(gl, vert, frag);
		if (!prog) { el.removeChild(canvas); return; }

		var buf = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, buf);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1, 1,-1, -1,1, 1,1]), gl.STATIC_DRAW);

		var aPos    = gl.getAttribLocation(prog,  'a_pos');
		var uTime   = gl.getUniformLocation(prog,  'u_time');
		var uAspect = gl.getUniformLocation(prog,  'u_aspect');
		var uC1     = gl.getUniformLocation(prog,  'u_color1');
		var uC2     = gl.getUniformLocation(prog,  'u_color2');
		var uBase   = gl.getUniformLocation(prog,  'u_base');

		var rafId     = null;
		var paused    = false;
		var startTime = null;

		function resize() {
			var w = el.offsetWidth, h = el.offsetHeight;
			if (canvas.width !== w || canvas.height !== h) {
				canvas.width = w; canvas.height = h;
				gl.viewport(0, 0, w, h);
			}
		}

		function render(timestamp) {
			if (paused) { rafId = null; return; }
			if (startTime === null) startTime = timestamp;
			var t = (timestamp - startTime) / 1000.0;

			resize();

			gl.useProgram(prog);
			gl.bindBuffer(gl.ARRAY_BUFFER, buf);
			gl.enableVertexAttribArray(aPos);
			gl.vertexAttribPointer(aPos, 2, gl.FLOAT, false, 0, 0);

			gl.uniform1f(uTime,   t);
			gl.uniform1f(uAspect, canvas.width / Math.max(canvas.height, 1));
			gl.uniform3fv(uC1,    colors.c1);
			gl.uniform3fv(uC2,    colors.c2);
			gl.uniform3fv(uBase,  base);

			gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);

			rafId = window.requestAnimationFrame(render);
		}

		function start()  { if (!rafId && !paused) rafId = window.requestAnimationFrame(render); }
		function pause()  { paused = true;  if (rafId) { window.cancelAnimationFrame(rafId); rafId = null; } }
		function resume() { paused = false; start(); }

		// Pause when tab is hidden
		document.addEventListener('visibilitychange', function () {
			document.hidden ? pause() : resume();
		});

		// Pause when scrolled off-screen
		if (window.IntersectionObserver) {
			var io = new IntersectionObserver(function (entries) {
				entries[0].isIntersecting ? resume() : pause();
			}, { threshold: 0 });
			io.observe(el);
		}

		window.addEventListener('resize', resize, { passive: true });

		start();
	}

	// ── Entry ──────────────────────────────────────────────────────────────────────
	elements.forEach(function (el) {
		initBreathe(el);
	});
}());
