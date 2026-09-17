/* Admin QR generator for the /igra sticker links (Filament page "QR Игра – статистика").
 * Depends on public/vendor/qrcode/qrcode.min.js (qrcode-generator, MIT) for the module matrix;
 * everything visual (colors, quiet zone, dots, "?" / logo knockout, SVG + PNG export) lives here.
 */
window.GameQr = (function () {
  'use strict';

  var QUIET = 4;   // quiet zone in modules (spec minimum)

  var STYLES = {
    dark: { bg: '#111111', fg: '#ffffff', label: 'Черен фон, бели модули', inverted: true },
    light: { bg: '#ffffff', fg: '#111111', label: 'Бял фон, черни модули (класически)', inverted: false },
    gold: { bg: '#111111', fg: '#D4AF37', label: 'Черен фон, златни модули', inverted: true }
  };

  function matrix(text) {
    var q = window.qrcode(0, 'H');   // version auto, error correction H (30%) so the centre can be covered
    q.addData(text);
    q.make();
    var n = q.getModuleCount(), m = [];
    for (var r = 0; r < n; r++) { m[r] = []; for (var c = 0; c < n; c++) { m[r][c] = q.isDark(r, c); } }
    return { n: n, cells: m, version: (n - 17) / 4 };
  }

  /* Centre knockout in module units (kept under ~7% of the area – safe with EC level H). */
  function knockout(n, center) {
    if (center === 'logo') {
      var w = Math.round(n * 0.36), h = Math.round(n * 0.20);
      return { x: (n - w) / 2, y: (n - h) / 2, w: w, h: h };
    }
    if (center === 'question') {
      var s = Math.round(n * 0.24);
      return { x: (n - s) / 2, y: (n - s) / 2, w: s, h: s };
    }
    return null;
  }
  function covered(k, r, c) {
    if (!k) { return false; }
    var pad = 0.5;   // keep a half-module margin around the knockout
    return c + 1 > k.x - pad && c < k.x + k.w + pad && r + 1 > k.y - pad && r < k.y + k.h + pad;
  }

  function esc(s) { return String(s).replace(/[&<>"']/g, function (ch) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]; }); }

  /* SVG string. opts: {style, center, dots, logoHref} – logoHref should be a data: URI for a portable file. */
  function svg(text, opts) {
    var st = STYLES[opts.style] || STYLES.dark, mx = matrix(text), n = mx.n, k = knockout(n, opts.center);
    var size = n + QUIET * 2, out = [];
    out.push('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 ' + size + ' ' + size + '" shape-rendering="crispEdges" width="' + (size * 12) + '" height="' + (size * 12) + '">');
    out.push('<rect width="' + size + '" height="' + size + '" fill="' + st.bg + '"/>');
    out.push('<g fill="' + st.fg + '">');
    for (var r = 0; r < n; r++) {
      for (var c = 0; c < n; c++) {
        if (!mx.cells[r][c] || covered(k, r, c)) { continue; }
        var x = c + QUIET, y = r + QUIET;
        if (opts.dots === 'round' && !isFinder(n, r, c)) {
          out.push('<circle cx="' + (x + 0.5) + '" cy="' + (y + 0.5) + '" r="0.46"/>');
        } else {
          out.push('<rect x="' + x + '" y="' + y + '" width="1" height="1"/>');
        }
      }
    }
    out.push('</g>');
    if (k) {
      var kx = k.x + QUIET, ky = k.y + QUIET;
      out.push('<rect x="' + (kx - 0.5) + '" y="' + (ky - 0.5) + '" width="' + (k.w + 1) + '" height="' + (k.h + 1) + '" rx="0.8" fill="' + st.bg + '"/>');
      if (opts.center === 'question') {
        out.push('<circle cx="' + (kx + k.w / 2) + '" cy="' + (ky + k.h / 2) + '" r="' + (k.w / 2 - 0.3) + '" fill="none" stroke="' + st.fg + '" stroke-width="0.35"/>');
        out.push('<text x="' + (kx + k.w / 2) + '" y="' + (ky + k.h / 2) + '" fill="' + st.fg + '" font-family="Montserrat, Arial, Helvetica, sans-serif" font-weight="800" font-size="' + (k.w * 0.72) + '" text-anchor="middle" dominant-baseline="central">?</text>');
      } else if (opts.center === 'logo' && opts.logoHref) {
        var pad = 0.6;
        out.push('<image xlink:href="' + esc(opts.logoHref) + '" href="' + esc(opts.logoHref) + '" x="' + (kx + pad) + '" y="' + (ky + pad) + '" width="' + (k.w - pad * 2) + '" height="' + (k.h - pad * 2) + '" preserveAspectRatio="xMidYMid meet"/>');
      }
    }
    out.push('</svg>');
    return out.join('');
  }

  function isFinder(n, r, c) {   // the three 7x7 finder patterns stay square so scanners lock on quickly
    return (r < 7 && c < 7) || (r < 7 && c >= n - 7) || (r >= n - 7 && c < 7);
  }

  /* PNG blob rendered on a canvas. opts as svg(); logoImg = loaded HTMLImageElement or null. */
  function png(text, opts, sizePx, logoImg) {
    var st = STYLES[opts.style] || STYLES.dark, mx = matrix(text), n = mx.n, k = knockout(n, opts.center);
    var total = n + QUIET * 2, px = sizePx / total;
    var canvas = document.createElement('canvas'); canvas.width = sizePx; canvas.height = sizePx;
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = st.bg; ctx.fillRect(0, 0, sizePx, sizePx);
    ctx.fillStyle = st.fg;
    for (var r = 0; r < n; r++) {
      for (var c = 0; c < n; c++) {
        if (!mx.cells[r][c] || covered(k, r, c)) { continue; }
        var x = (c + QUIET) * px, y = (r + QUIET) * px;
        if (opts.dots === 'round' && !isFinder(n, r, c)) {
          ctx.beginPath(); ctx.arc(x + px / 2, y + px / 2, px * 0.46, 0, Math.PI * 2); ctx.fill();
        } else {
          ctx.fillRect(Math.floor(x), Math.floor(y), Math.ceil(px), Math.ceil(px));
        }
      }
    }
    if (k) {
      var kx = (k.x + QUIET) * px, ky = (k.y + QUIET) * px, kw = k.w * px, kh = k.h * px;
      ctx.fillStyle = st.bg; roundRect(ctx, kx - px / 2, ky - px / 2, kw + px, kh + px, px * 0.8); ctx.fill();
      if (opts.center === 'question') {
        ctx.strokeStyle = st.fg; ctx.lineWidth = px * 0.35;
        ctx.beginPath(); ctx.arc(kx + kw / 2, ky + kh / 2, kw / 2 - px * 0.3, 0, Math.PI * 2); ctx.stroke();
        ctx.fillStyle = st.fg; ctx.font = '800 ' + (kw * 0.72) + 'px Montserrat, Arial, Helvetica, sans-serif';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText('?', kx + kw / 2, ky + kh / 2 + kw * 0.02);
      } else if (opts.center === 'logo' && logoImg && logoImg.naturalWidth) {
        var pad = px * 0.6, bw = kw - pad * 2, bh = kh - pad * 2;
        var scale = Math.min(bw / logoImg.naturalWidth, bh / logoImg.naturalHeight);
        var lw = logoImg.naturalWidth * scale, lh = logoImg.naturalHeight * scale;
        ctx.drawImage(logoImg, kx + (kw - lw) / 2, ky + (kh - lh) / 2, lw, lh);
      }
    }
    return new Promise(function (res, rej) { canvas.toBlob(function (b) { b ? res(b) : rej(new Error('toBlob')); }, 'image/png'); });
  }

  function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath(); ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
  }

  function loadImage(src) {
    return new Promise(function (res) {
      if (!src) { res(null); return; }
      var im = new Image(); im.onload = function () { res(im); }; im.onerror = function () { res(null); }; im.src = src;
    });
  }
  function toDataUri(img) {
    try {
      var c = document.createElement('canvas'); c.width = img.naturalWidth; c.height = img.naturalHeight;
      c.getContext('2d').drawImage(img, 0, 0); return c.toDataURL('image/png');
    } catch (e) { return null; }
  }
  function download(blob, name) {
    var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = name;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 5000);
  }
  function slug(s) { return String(s || '').toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '') || 'igra'; }

  /* Alpine component state for the admin panel. */
  function panel(init) {
    return {
      url: init.url || '', name: init.name || 'igra',
      style: 'dark', center: 'question', dots: 'square', size: 2048,
      styles: STYLES, svgMarkup: '', version: 0, busy: false,
      logos: init.logos || {}, logoImgs: {}, logoUris: {},
      init: function () {
        var self = this;
        ['dark', 'light'].forEach(function (key) {
          loadImage(self.logos[key]).then(function (img) { if (img) { self.logoImgs[key] = img; self.logoUris[key] = toDataUri(img); if (self.center === 'logo') { self.render(); } } });
        });
        this.render();
      },
      setUrl: function (url, name) {
        if (url && url !== this.url) { this.url = url; this.name = name || this.name; this.render(); }
        else if (name && name !== this.name) { this.name = name; }
      },
      logoKey: function () { return (STYLES[this.style] || STYLES.dark).inverted ? 'dark' : 'light'; },
      opts: function () { return { style: this.style, center: this.center, dots: this.dots, logoHref: this.logoUris[this.logoKey()] || this.logos[this.logoKey()] || null }; },
      render: function () {
        if (!this.url || !window.qrcode) { return; }
        try { this.svgMarkup = svg(this.url, this.opts()); this.version = matrix(this.url).version; } catch (e) { this.svgMarkup = ''; }
      },
      fileBase: function () { return 'qr-' + slug(this.name) + '-' + this.style + (this.center !== 'none' ? '-' + this.center : ''); },
      downloadSvg: function () {
        if (!this.url) { return; }
        download(new Blob([svg(this.url, this.opts())], { type: 'image/svg+xml' }), this.fileBase() + '.svg');
      },
      downloadPng: function () {
        if (!this.url || this.busy) { return; }
        var self = this; this.busy = true;
        png(this.url, this.opts(), parseInt(this.size, 10) || 2048, this.logoImgs[this.logoKey()] || null)
          .then(function (b) { download(b, self.fileBase() + '-' + self.size + '.png'); })
          .catch(function () { /* ignore */ })
          .then(function () { self.busy = false; });
      },
      isInverted: function () { return (STYLES[this.style] || STYLES.dark).inverted; }
    };
  }

  return { matrix: matrix, svg: svg, png: png, panel: panel, STYLES: STYLES, QUIET: QUIET, loadImage: loadImage };
})();
