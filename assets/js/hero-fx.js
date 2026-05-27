(function () {
	'use strict';

	var cfg = window.skateHeroFx;
	if (!cfg) return;

	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	var EFFECT       = cfg.effect    || 'distortion';
	var MAX_STRENGTH = (cfg.intensity / 100) * 0.12;
	var RADIUS       = (cfg.radius   / 100) * 0.55;
	var SPOT_SIZE    = 200 + (cfg.radius   / 100) * 500;
	var SPOT_OPACITY = 0.10 + (cfg.intensity / 100) * 0.45;

	// ── Blend-mode uniforms + GLSL function (shared across all fragment shaders) ─
	// Uses float for blend mode index to avoid GLSL ES int precision declaration issues.
	// Sequential if(m<N.5) checks: 0=none 1=multiply 2=screen 3=overlay 4=darken
	// 5=lighten 6=difference 7=exclusion 8=hard-light 9=soft-light
	var BLEND_SRC = [
		'uniform vec3 u_bg_color;',
		'uniform float u_bg_blend;',
		'vec3 bgBlend(vec3 img,vec3 c,float m){',
		'  if(m<0.5)return img;',                                                                            // normal/none
		'  if(m<1.5)return img*c;',                                                                          // multiply
		'  if(m<2.5)return vec3(1.0)-(vec3(1.0)-img)*(vec3(1.0)-c);',                                       // screen
		'  if(m<3.5){vec3 r;',                                                                               // overlay
		'    r.r=img.r<0.5?2.0*img.r*c.r:1.0-2.0*(1.0-img.r)*(1.0-c.r);',
		'    r.g=img.g<0.5?2.0*img.g*c.g:1.0-2.0*(1.0-img.g)*(1.0-c.g);',
		'    r.b=img.b<0.5?2.0*img.b*c.b:1.0-2.0*(1.0-img.b)*(1.0-c.b);return r;}',
		'  if(m<4.5)return min(img,c);',                                                                     // darken
		'  if(m<5.5)return max(img,c);',                                                                     // lighten
		'  if(m<6.5)return abs(img-c);',                                                                     // difference
		'  if(m<7.5)return img+c-2.0*img*c;',                                                               // exclusion
		'  if(m<8.5){vec3 r;',                                                                               // hard-light
		'    r.r=c.r<0.5?2.0*img.r*c.r:1.0-2.0*(1.0-img.r)*(1.0-c.r);',
		'    r.g=c.g<0.5?2.0*img.g*c.g:1.0-2.0*(1.0-img.g)*(1.0-c.g);',
		'    r.b=c.b<0.5?2.0*img.b*c.b:1.0-2.0*(1.0-img.b)*(1.0-c.b);return r;}',
		'  if(m<9.5){vec3 r;',                                                                               // soft-light
		'    r.r=(1.0-2.0*c.r)*img.r*img.r+2.0*c.r*img.r;',
		'    r.g=(1.0-2.0*c.g)*img.g*img.g+2.0*c.g*img.g;',
		'    r.b=(1.0-2.0*c.b)*img.b*img.b+2.0*c.b*img.b;return r;}',
		'  return img;}',                                                                                     // fallback
	].join('\n');

	// ── Shared vertex shader ─────────────────────────────────────────────────────
	var VERT_SRC = [
		'attribute vec2 a_pos;',
		'varying vec2 v_uv;',
		'void main(){',
		'  v_uv=a_pos*0.5+0.5;',
		'  gl_Position=vec4(a_pos,0.0,1.0);',
		'}'
	].join('\n');

	// ── Effect: Distortion ───────────────────────────────────────────────────────
	var FRAG_DISTORTION = [
		'precision mediump float;',
		'varying vec2 v_uv;',
		'uniform sampler2D u_tex;',
		'uniform vec2 u_mouse;',
		'uniform float u_strength;',
		'uniform float u_radius;',
		'uniform vec2 u_res;',
		'uniform vec2 u_img;',
		'void main(){',
		'  float sc=max(u_res.x/u_img.x,u_res.y/u_img.y);',
		'  vec2 covered=u_img*sc/u_res;',
		'  vec2 uv=(v_uv-0.5)/covered+0.5;',
		'  vec2 d=uv-u_mouse;',
		'  float w=exp(-dot(d,d)/(u_radius*u_radius));',
		'  vec2 warped=uv+d*w*u_strength;',
		'  vec4 texel=texture2D(u_tex,vec2(warped.x,1.0-warped.y));',
		'  gl_FragColor=vec4(bgBlend(texel.rgb,u_bg_color,u_bg_blend),texel.a);',
		'}'
	].join('\n');

	// ── Effect: Glitch ───────────────────────────────────────────────────────────
	// Premium mouse-reactive chromatic aberration.
	//
	// Three improvements over the original:
	//  1. Per-channel independent directions — R/G/B each derive a unique axis
	//     from u_seed (which changes with mouse position), so they split chaotically.
	//  2. Higher CA magnitude — 0.072 vs the old 0.022.
	//  3. Temporal gate — hash at 14 ticks/s creates a mix of clean + corrupted
	//     frames rather than a continuous smooth smear. ~65 % of ticks active.
	//
	// u_shock is set to 1.0 normally; hero-fx.js spikes it briefly on mouseenter
	// for the "shock flash" feel on entry.
	var FRAG_GLITCH = [
		'precision mediump float;',
		'varying vec2 v_uv;',
		'uniform sampler2D u_tex;',
		'uniform vec2 u_mouse;',
		'uniform float u_strength;',
		'uniform float u_radius;',
		'uniform vec2 u_res;',
		'uniform vec2 u_img;',
		'uniform float u_seed;',
		'uniform float u_time;',
		'uniform float u_shock;',    // 1.0 normally, briefly >1 on mouseenter

		'float hash(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}',
		'vec4 samp(vec2 uv){return texture2D(u_tex,vec2(uv.x,1.0-uv.y));}',

		'void main(){',
		'  float sc=max(u_res.x/u_img.x,u_res.y/u_img.y);',
		'  vec2 covered=u_img*sc/u_res;',
		'  vec2 uv=(v_uv-0.5)/covered+0.5;',

		'  vec2 delta=uv-u_mouse;',
		'  float weight=exp(-dot(delta,delta)/(u_radius*u_radius))*u_strength;',

		// ── Band shift — localized horizontal jitter, scaled by mouse proximity ─────
		'  float tick=floor(u_time*10.0);',
		'  float bandH=mix(0.07,0.022,weight);',
		'  float band=floor(uv.y/bandH);',
		'  float bh1=hash(vec2(band,tick));',
		'  float bh2=hash(vec2(band+0.3,tick));',
		'  float doLine=step(1.0-weight*0.80,bh1);',   // more bands fire near mouse center
		'  float xLine=(bh2-0.5)*weight*0.28*doLine;', // bigger shift than before but still local
		'  vec2 suv=vec2(uv.x+xLine,uv.y);',

		// ── Block quantization — snaps UV to a pixel grid → "pixelated" look ──────
		// blockPx is large (coarse) away from mouse, shrinks to fine at center.
		// Sampling at block-center (pUV) gives the chunky mosaic appearance.
		'  float blockPx=mix(20.0,5.0,weight);',
		'  vec2 bs=vec2(blockPx)/u_res;',
		'  vec2 pUV=floor(suv/bs)*bs+bs*0.5;',

		// ── Per-channel independent directions from u_seed ────────────────────────
		// Three channels always 120° apart; baseAngle rotates as mouse moves.
		'  float s=u_seed;',
		'  float baseAngle=s*0.05;',
		'  vec2 rDir=vec2(cos(baseAngle),         sin(baseAngle)        );',
		'  vec2 gDir=vec2(cos(baseAngle+2.094),   sin(baseAngle+2.094)  );',
		'  vec2 bDir=vec2(cos(baseAngle+4.189),   sin(baseAngle+4.189)  );',

		// CA applied to pUV so color fringing also looks pixelated
		'  float ca=u_shock*0.024;',
		'  float r=samp(pUV+rDir*ca).r;',
		'  float g=samp(pUV+gDir*ca).g;',
		'  float b=samp(pUV+bDir*ca).b;',

		// ── Temporal gate — flickering between clean and corrupted frames ──────────
		'  float gtick=floor(u_time*14.0);',
		'  float gate=step(0.35,hash(vec2(gtick,floor(s*0.01+0.5))));',

		'  vec4 normal=samp(suv);',
		'  vec4 raw=mix(normal,vec4(r,g,b,1.0),clamp(weight*u_shock*3.5,0.0,1.0)*gate);',
		'  gl_FragColor=vec4(bgBlend(raw.rgb,u_bg_color,u_bg_blend),raw.a);',
		'}'
	].join('\n');

	// ── Effect: RGB Split ────────────────────────────────────────────────────────
	var FRAG_RGB = [
		'precision mediump float;',
		'varying vec2 v_uv;',
		'uniform sampler2D u_tex;',
		'uniform vec2 u_mouse;',
		'uniform float u_strength;',
		'uniform float u_radius;',
		'uniform vec2 u_res;',
		'uniform vec2 u_img;',
		'uniform float u_time;',

		'vec4 samp(vec2 uv){return texture2D(u_tex,vec2(uv.x,1.0-uv.y));}',

		'void main(){',
		'  float sc=max(u_res.x/u_img.x,u_res.y/u_img.y);',
		'  vec2 covered=u_img*sc/u_res;',
		'  vec2 uv=(v_uv-0.5)/covered+0.5;',

		'  vec2 delta=uv-u_mouse;',
		'  float weight=exp(-dot(delta,delta)/(u_radius*u_radius))*u_strength;',

		'  float angle=u_time*0.7;',
		'  vec2 rd=vec2(cos(angle),           sin(angle));',
		'  vec2 gd=vec2(cos(angle+2.094),     sin(angle+2.094));',
		'  vec2 bd=vec2(cos(angle+4.189),     sin(angle+4.189));',

		'  float dist=length(uv-vec2(0.5));',
		'  float offset=dist*weight*0.18;',

		'  float r=samp(uv+rd*offset).r;',
		'  float g=samp(uv+gd*offset).g;',
		'  float b=samp(uv+bd*offset).b;',

		'  vec4 normal=samp(uv);',
		'  vec4 raw=mix(normal,vec4(r,g,b,1.0),clamp(weight*2.0,0.0,1.0));',
		'  gl_FragColor=vec4(bgBlend(raw.rgb,u_bg_color,u_bg_blend),raw.a);',
		'}'
	].join('\n');

	// ── WebGL helpers ────────────────────────────────────────────────────────────
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

	// ── Background detection ─────────────────────────────────────────────────────
	function detectBackground(el) {
		var coverImg = el.querySelector('.wp-block-cover__image-background');
		if (coverImg && coverImg.src) return { src: coverImg.src, type: 'img', imgEl: coverImg };

		var imgs = el.querySelectorAll('img');
		for (var i = 0; i < imgs.length; i++) {
			if (window.getComputedStyle(imgs[i]).objectFit === 'cover' && imgs[i].src)
				return { src: imgs[i].src, type: 'img', imgEl: imgs[i] };
		}

		var vids = el.querySelectorAll('video');
		for (var v = 0; v < vids.length; v++) {
			var vid = vids[v];
			var vs = window.getComputedStyle(vid);
			if (vs.position === 'absolute' || vs.position === 'fixed')
				return { type: 'video', videoEl: vid, zIdx: parseInt(vs.zIndex) || 1 };
		}

		var candidates = [el].concat(Array.prototype.slice.call(el.children));
		for (var j = 0; j < candidates.length; j++) {
			var bg = window.getComputedStyle(candidates[j]).backgroundImage;
			if (!bg || bg === 'none') continue;
			var m = bg.match(/url\(["']?([^"')]+)["']?\)/);
			if (m && m[1]) return { src: m[1], type: 'css', bgEl: candidates[j] };
		}
		return null;
	}

	// ── CSS spotlight (fallback for flat color / gradient backgrounds) ───────────
	function initSpotlight(el) {
		var half = SPOT_SIZE / 2;
		var spot = document.createElement('div');
		spot.style.cssText = [
			'position:absolute', 'pointer-events:none', 'border-radius:50%',
			'width:'  + SPOT_SIZE + 'px', 'height:' + SPOT_SIZE + 'px',
			'margin-left:-' + half + 'px', 'margin-top:-' + half + 'px',
			'background:radial-gradient(circle,rgba(255,255,255,' + SPOT_OPACITY + ') 0%,transparent 65%)',
			'mix-blend-mode:soft-light', 'opacity:0', 'top:0', 'left:0',
			'transition:opacity .4s ease', 'will-change:transform'
		].join(';');
		el.appendChild(spot);

		var mouseX = 0, mouseY = 0, lerpX = 0, lerpY = 0, active = false, rafId = null;

		function loop() {
			lerpX += (mouseX - lerpX) * 0.10;
			lerpY += (mouseY - lerpY) * 0.10;
			spot.style.left = lerpX + 'px';
			spot.style.top  = lerpY + 'px';
			var settling = Math.abs(lerpX - mouseX) > 0.5 || Math.abs(lerpY - mouseY) > 0.5;
			rafId = (active || settling) ? window.requestAnimationFrame(loop) : null;
		}

		el.addEventListener('mousemove', function (e) {
			var r = el.getBoundingClientRect();
			mouseX = e.clientX - r.left; mouseY = e.clientY - r.top;
			active = true; spot.style.opacity = '1';
			if (!rafId) rafId = window.requestAnimationFrame(loop);
		}, { passive: true });

		el.addEventListener('mouseleave', function () {
			active = false; spot.style.opacity = '0';
			if (!rafId) rafId = window.requestAnimationFrame(loop);
		}, { passive: true });
	}

	// ── Blend helpers ────────────────────────────────────────────────────────────
	function parseRgb(str) {
		var m = str.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
		if (!m) return [1.0, 1.0, 1.0];
		return [parseInt(m[1], 10) / 255, parseInt(m[2], 10) / 255, parseInt(m[3], 10) / 255];
	}

	var BLEND_MODES = {
		'multiply': 1, 'screen': 2, 'overlay': 3, 'darken': 4, 'lighten': 5,
		'difference': 6, 'exclusion': 7, 'hard-light': 8, 'soft-light': 9
	};

	// ── WebGL effect ─────────────────────────────────────────────────────────────
	function initWebGL(el, source) {
		var fragSrc = EFFECT === 'glitch' ? FRAG_GLITCH
		            : EFFECT === 'rgb'   ? FRAG_RGB
		            :                      FRAG_DISTORTION;
		// Inject blend declarations right after the precision line
		fragSrc = fragSrc.replace('precision mediump float;\n', 'precision mediump float;\n' + BLEND_SRC + '\n');

		// Read background-blend-mode and background-color before activate() removes the image
		var blendMode = 0, blendColor = [1.0, 1.0, 1.0];
		if (source.type === 'css' && source.bgEl) {
			var cs = window.getComputedStyle(source.bgEl);
			var bbm = (cs.backgroundBlendMode || '').trim();
			blendMode = BLEND_MODES[bbm] || 0;
			if (blendMode > 0) blendColor = parseRgb(cs.backgroundColor || '');
		}

		var canvas = document.createElement('canvas');
		// For video: z-index 0 puts the canvas above the video layer (-1) and the
		// cover-block colour overlay (also 0, but earlier in DOM → canvas wins by order),
		// while staying below the inner-container content (z-index 1).
		// Inheriting the video's own z-index breaks when WP sets it to -1 (-1 || 1 = -1).
		var canvasZ = source.type === 'video' ? 0 : -1;
		canvas.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;display:block;z-index:' + canvasZ + ';';
		el.appendChild(canvas);

		var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
		if (!gl) { el.removeChild(canvas); return; }

		var vert = mkShader(gl, gl.VERTEX_SHADER,   VERT_SRC);
		var frag = mkShader(gl, gl.FRAGMENT_SHADER, fragSrc);
		if (!vert || !frag) { el.removeChild(canvas); return; }

		var prog = mkProgram(gl, vert, frag);
		if (!prog) { el.removeChild(canvas); return; }

		var buf = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, buf);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1, 1,-1, -1,1, 1,1]), gl.STATIC_DRAW);

		var aPos      = gl.getAttribLocation(prog,  'a_pos');
		var uTex      = gl.getUniformLocation(prog,  'u_tex');
		var uMouse    = gl.getUniformLocation(prog,  'u_mouse');
		var uStrength = gl.getUniformLocation(prog,  'u_strength');
		var uRadius   = gl.getUniformLocation(prog,  'u_radius');
		var uRes      = gl.getUniformLocation(prog,  'u_res');
		var uImg      = gl.getUniformLocation(prog,  'u_img');
		var uSeed     = gl.getUniformLocation(prog,  'u_seed');    // glitch only
		var uTime     = gl.getUniformLocation(prog,  'u_time');    // glitch/rgb only
		var uShock    = gl.getUniformLocation(prog,  'u_shock');   // glitch only — enter flash
		var uBgColor  = gl.getUniformLocation(prog,  'u_bg_color');
		var uBgBlend  = gl.getUniformLocation(prog,  'u_bg_blend');

		var texture = gl.createTexture();
		gl.bindTexture(gl.TEXTURE_2D, texture);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);

		var imgW = 1, imgH = 1, rafId = null;
		var isHovered = false;
		// Shock multiplier — spikes to >1.0 on mouseenter, decays back to 1.0.
		// Gives a brief intense "flash" when the cursor first enters.
		var shockMul = 1.0;

		function activate() {
			if (source.type === 'img')        source.imgEl.style.opacity = '0';
			else if (source.type === 'video') source.videoEl.style.opacity = '0';
			else                              source.bgEl.style.backgroundImage = 'none';
		}

		function abort() {
			if (canvas.parentNode) canvas.parentNode.removeChild(canvas);
			if (source.type === 'img')        source.imgEl.style.opacity = '';
			else if (source.type === 'video') source.videoEl.style.opacity = '';
			else                              source.bgEl.style.backgroundImage = '';
			initSpotlight(el);
		}

		function loadTexture(src) {
			var image = new Image();
			image.crossOrigin = 'anonymous';
			image.onload = function () {
				imgW = image.naturalWidth;
				imgH = image.naturalHeight;
				gl.bindTexture(gl.TEXTURE_2D, texture);
				gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, image);
				activate();
				rafId = window.requestAnimationFrame(render);
			};
			image.onerror = abort;
			image.src = src;
		}

		function initVideo() {
			function start() {
				imgW = source.videoEl.videoWidth  || 1920;
				imgH = source.videoEl.videoHeight || 1080;
				activate();
				rafId = window.requestAnimationFrame(render);
			}
			if (source.videoEl.readyState >= 1) start();
			else source.videoEl.addEventListener('loadedmetadata', start, { once: true });
		}

		function resize() {
			var w = el.offsetWidth, h = el.offsetHeight;
			if (canvas.width !== w || canvas.height !== h) {
				canvas.width = w; canvas.height = h;
				gl.viewport(0, 0, w, h);
			}
		}

		var mouseX = 0.5, mouseY = 0.5;
		var lerpX  = 0.5, lerpY  = 0.5;
		var strength = 0, targetStrength = 0;
		var seed = 0;

		function render(timestamp) {
			lerpX    += (mouseX         - lerpX)    * 0.10;
			lerpY    += (mouseY         - lerpY)    * 0.10;
			strength += (targetStrength - strength) * 0.08;
			// Decay shock multiplier back to 1.0 each frame
			shockMul += (1.0 - shockMul) * 0.12;

			resize();
			// Re-upload the current video frame every render tick
			if (source.type === 'video' && source.videoEl.readyState >= 2) {
				imgW = source.videoEl.videoWidth  || imgW;
				imgH = source.videoEl.videoHeight || imgH;
				gl.bindTexture(gl.TEXTURE_2D, texture);
				gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, source.videoEl);
			}
			gl.useProgram(prog);
			gl.bindBuffer(gl.ARRAY_BUFFER, buf);
			gl.enableVertexAttribArray(aPos);
			gl.vertexAttribPointer(aPos, 2, gl.FLOAT, false, 0, 0);
			gl.activeTexture(gl.TEXTURE0);
			gl.bindTexture(gl.TEXTURE_2D, texture);
			gl.uniform1i(uTex, 0);
			gl.uniform2f(uMouse, lerpX, lerpY);
			gl.uniform1f(uStrength, strength);
			gl.uniform1f(uRadius, RADIUS);
			gl.uniform2f(uRes, canvas.width, canvas.height);
			gl.uniform2f(uImg, imgW, imgH);
			if (uSeed    !== null) gl.uniform1f(uSeed,  seed);
			if (uTime    !== null) gl.uniform1f(uTime,  (timestamp || 0) / 1000.0);
			if (uShock   !== null) gl.uniform1f(uShock, shockMul);
			if (uBgColor !== null) gl.uniform3fv(uBgColor, blendColor);
			if (uBgBlend !== null) gl.uniform1f(uBgBlend,  blendMode);
			gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);

			var settling = Math.abs(strength - targetStrength) > 0.0001
				|| Math.abs(lerpX - mouseX) > 0.0005
				|| Math.abs(lerpY - mouseY) > 0.0005
				|| Math.abs(shockMul - 1.0) > 0.005;  // keep running until shock fully decayed
			// Keep running while: settling, time-based effects hovered, or video is playing
			var keepRunning = settling
				|| ((EFFECT === 'glitch' || EFFECT === 'rgb') && isHovered)
				|| (source.type === 'video' && !source.videoEl.paused && !source.videoEl.ended);
			rafId = keepRunning ? window.requestAnimationFrame(render) : null;
		}

		el.addEventListener('mouseenter', function (e) {
			isHovered = true;
			shockMul  = 2.4;  // brief intensity spike — decays to 1.0 via render loop
			// Seed and position on enter so CA is visible immediately (not deferred to first mousemove)
			var r = el.getBoundingClientRect();
			mouseX         = (e.clientX - r.left) / r.width;
			mouseY         = 1.0 - (e.clientY - r.top) / r.height;
			seed           = mouseX * 37.0 + mouseY * 19.0;
			targetStrength = MAX_STRENGTH;
			if (!rafId) rafId = window.requestAnimationFrame(render);
		}, { passive: true });

		el.addEventListener('mousemove', function (e) {
			var r = el.getBoundingClientRect();
			mouseX = (e.clientX - r.left) / r.width;
			mouseY = 1.0 - (e.clientY - r.top) / r.height;
			seed   = mouseX * 37.0 + mouseY * 19.0;
			targetStrength = MAX_STRENGTH;
			if (!rafId) rafId = window.requestAnimationFrame(render);
		}, { passive: true });

		el.addEventListener('mouseleave', function () {
			isHovered = false;
			targetStrength = 0;
			if (!rafId) rafId = window.requestAnimationFrame(render);
		}, { passive: true });

		window.addEventListener('resize', function () {
			resize();
			if (!rafId) rafId = window.requestAnimationFrame(render);
		}, { passive: true });

		if (source.type === 'video') initVideo();
		else                         loadTexture(source.src);
	}

	// ── Entry point ──────────────────────────────────────────────────────────────
	document.querySelectorAll('.skate-hero-fx').forEach(function (el) {
		var source = detectBackground(el);
		if (source) initWebGL(el, source);
		else        initSpotlight(el);
	});
}());
