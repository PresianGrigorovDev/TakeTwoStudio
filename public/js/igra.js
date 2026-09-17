/* /igra – "The Mystery Protocol" (QR sticker mini-game)
 * Vanilla JS, one IIFE, ES2017 syntax (no optional chaining – iOS 13 support).
 * Modules: Store, Events, Sound, Connections, Seating, Voucher, Reveal, Story, App.
 * Server contract: #igra-config JSON (see GameController::show) and POST eventUrl (fire-and-forget).
 */
(function () {
  'use strict';

  /* ---------- feature gate ---------- */
  if (!window.fetch || !window.Promise || !window.URLSearchParams || !('content' in document.createElement('template'))) {
    var unsupported = document.getElementById('igra-unsupported');
    if (unsupported) { unsupported.hidden = false; }
    return;
  }

  /* ---------- helpers ---------- */
  function $(id) { return document.getElementById(id); }
  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function el(tag, cls, text) { var n = document.createElement(tag); if (cls) { n.className = cls; } if (text != null) { n.textContent = text; } return n; }
  function on(node, ev, fn, opts) { if (node) { node.addEventListener(ev, fn, opts || false); } }
  function pad2(n) { return (n < 10 ? '0' : '') + n; }
  function noop() {}
  function live(text) { var n = $('igra-live'); if (n) { n.textContent = ''; setTimeout(function () { n.textContent = text; }, 30); } }
  function setMsg(node, text, type) {
    if (!node) { return; }
    node.textContent = text || '';
    node.className = 'igra-msg mono' + (type ? ' is-' + type : '');
    if (text) { node.classList.remove('msg-in'); void node.offsetWidth; node.classList.add('msg-in'); live(text); }
  }
  var REDUCED = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

  /* ---------- config ---------- */
  function readConfig() {
    var d = {
      target: 'prom', loc: null, eventUrl: '/api/igra/event',
      instagramHandle: 'taketwostudio1603', instagramUrl: 'https://www.instagram.com/taketwostudio1603',
      logoUrl: '/css/img/logo-tts-white.webp', studioName: 'Take Two Studio 1603',
      discountPercent: 5, discountPercentShared: 15, validityHours: 72, seasonYear: new Date().getFullYear(), legalUrl: null,
      useUrl: '/proms'
    };
    try {
      var node = $('igra-config');
      var c = node ? JSON.parse(node.textContent) : {};
      for (var k in c) { if (Object.prototype.hasOwnProperty.call(c, k) && c[k] !== undefined) { d[k] = c[k]; } }
    } catch (e) { /* keep defaults */ }
    if (d.target !== 'wedding') { d.target = 'prom'; }
    if (!/^[a-z0-9-]{1,40}$/.test(d.loc || '')) { d.loc = null; }
    d.instagramHandle = String(d.instagramHandle || 'taketwostudio1603').replace(/^@/, '');
    return d;
  }
  var CONFIG = readConfig();
  var TARGET = CONFIG.target;
  var IS_PROM = TARGET === 'prom';

  /* ---------- strings (Bulgarian) ---------- */
  var STR = {
    wrong: function (n) { return 'Грешна хипотеза. ' + (n === 1 ? 'Остава 1 опит.' : 'Остават ' + n + ' опита.'); },
    oneOff: 'Една плочка разлика.',
    repeated: 'Вече опита тази комбинация.',
    solved: function (name) { return 'Връзка открита: ' + name; },
    livesLabel: function (n) { return 'Оставащи опити: ' + n; },
    pickFour: 'Избери точно 4 плочки.',
    seatAll: 'Настани всички гости, преди да потвърдиш.',
    conflict: 'Има конфликт на масата. Провери отбелязаните правила.',
    seatsOk: 'Всички правила са спазени.',
    seatFree: function (n) { return 'Място ' + n + ': свободно'; },
    seatWith: function (n, g) { return 'Място ' + n + ': ' + g; },
    moveHint: 'Избери място за госта.',
    copy: 'Копирай', copied: 'Копирано!', copyFail: 'Маркирай кода и го копирай ръчно.',
    validFor: function (hms) { return 'Валиден още ' + hms; },
    expired: 'Изтекъл', expiredLong: 'Кодът е изтекъл.',
    rendering: '> Рендиране на артефакта…', rendered: '> Артефактът е готов.',
    renderFail: 'Артефактът не можа да се генерира. Опитай отново.',
    holdToSave: 'Задръж снимката, за да я запазиш.',
    shareTitle: CONFIG.studioName, shareText: 'ДЕКРИПТИРАХ КОДА ЗА ВАРНА',
    boot: '> ДЕКРИПТИРАНЕ… OK',
    soundOn: 'Звук: включен', soundOff: 'Звук: изключен',
    badge: function (p) { return p + '% OFF VOUCHER'; },
    boostHint: function (p) { return '> Сподели Story артефакта и отстъпката става ' + p + '%.'; },
    boosted: function (p) { return '> Story споделено. Отстъпката ти е ' + p + '%.'; },
    syncing: '> Активиране на кода…',
    fileName: function () { return 'taketwo-story-varna.jpg'; }   // never put the voucher code in the file name
  };

  /* ---------- game data ---------- */
  var DATA = {
    prom: {
      groups: [
        { id: 'npc', name: 'NPC Бал Пози', tiles: ['Гледане на въображаем часовник', 'Оправяне копчето на сакото', 'Поглед в безкрая', 'Хванат за ревера'] },
        { id: 'may24', name: 'Кошмарите на 24 май', tiles: ['Счупен ток на паветата', 'Разтекъл се грим от жега', 'Дъжд на фотосесията', 'DJ пуска Бяла роза в 21:00'] },
        { id: 'cast', name: 'Персонажи от випуска', tiles: ['Крипто батка с тясно сако', 'Девойка с 3 смени на тоалета', 'Пич с кецове под костюма', 'Момчето наел кола за 1000 лв'] },
        { id: 'road', name: 'Шофьорски фолклор', tiles: ['Надуване на клаксон на Червения площад', 'Навеждане през шибидаха', 'Броене от 1 до 12 на светофара', 'Пътна полиция на изхода на Морската'] }
      ]
    },
    wedding: {
      guests: [
        { id: 'svekarva', name: 'Свекървата', mono: 'СВ', desc: 'Държи на протокола' },
        { id: 'maika', name: 'Майката на булката', mono: 'МБ', desc: 'Държи на своето' },
        { id: 'ivan', name: 'Купонджията Иван', mono: 'ИВ', desc: 'Ще танцува само до Елена' },
        { id: 'chicho', name: 'Чичото с политиката', mono: 'ЧП', desc: 'Има мнение за всичко' },
        { id: 'dj', name: 'DJ-ят', mono: 'DJ', desc: 'Иска пряк път до пулта' },
        { id: 'elena', name: 'Шаферката Елена', mono: 'ЕЛ', desc: 'Пази булката и Иван' }
      ],
      rules: [
        { id: 'r1', involves: ['svekarva', 'maika'], test: function (s) { return !adj(s.svekarva, s.maika) && !opp(s.svekarva, s.maika); } },
        { id: 'r2', involves: ['ivan', 'elena'], test: function (s) { return adj(s.ivan, s.elena); } },
        { id: 'r3', involves: ['dj'], test: function (s) { return s.dj === 0; } },
        { id: 'r4', involves: ['chicho', 'svekarva'], test: function (s) { return !adj(s.chicho, s.svekarva); } }
      ]
    }
  };
  /* 0-based seat indexes around a 6-seat table: neighbours differ by 1 (mod 6), opposite by 3. */
  function adj(a, b) { return a >= 0 && b >= 0 && ((a + 1) % 6 === b || (b + 1) % 6 === a); }
  function opp(a, b) { return a >= 0 && b >= 0 && (a + 3) % 6 === b; }

  /* ---------- deterministic RNG (tile order survives refresh) ---------- */
  function mulberry32(a) {
    return function () {
      a |= 0; a = a + 0x6D2B79F5 | 0;
      var t = Math.imul(a ^ a >>> 15, 1 | a);
      t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
      return ((t ^ t >>> 14) >>> 0) / 4294967296;
    };
  }
  function randomSeed() {
    try { var u = new Uint32Array(1); window.crypto.getRandomValues(u); return u[0]; } catch (e) { return Math.floor(Math.random() * 4294967296); }
  }
  function shuffle(arr, rnd) { for (var i = arr.length - 1; i > 0; i--) { var j = Math.floor(rnd() * (i + 1)); var t = arr[i]; arr[i] = arr[j]; arr[j] = t; } return arr; }

  /* ---------- Store (sessionStorage, memory fallback) ---------- */
  var Store = (function () {
    var KEY = 'igra:v1:' + TARGET;
    var MIRROR_KEY = 'igra:voucher:' + TARGET;   // localStorage mirror of the voucher only (no personal data)
    var mem = null;
    function storage(kind) { try { var s = window[kind]; s.setItem('__igra', '1'); s.removeItem('__igra'); return s; } catch (e) { return null; } }
    var session = storage('sessionStorage');
    var local = storage('localStorage');
    function blank() { return { v: 1, target: TARGET, loc: CONFIG.loc, screen: 'intro', startedAt: null, muted: false, logged: {}, game: null, voucher: null }; }
    function load() {
      if (mem) { return mem; }
      try { var raw = session ? session.getItem(KEY) : null; var o = raw ? JSON.parse(raw) : null; mem = (o && o.v === 1) ? o : blank(); } catch (e) { mem = blank(); }
      if (!mem.logged) { mem.logged = {}; }
      if (!mem.voucher && local) {
        try {
          var mirror = JSON.parse(local.getItem(MIRROR_KEY) || 'null');
          if (mirror && mirror.code && mirror.expiresAt > Date.now()) { mem.voucher = mirror; mem.screen = 'reveal'; mem.logged.voucher = 1; save(); }
        } catch (e) { /* ignore */ }
      }
      return mem;
    }
    function save() { try { if (session) { session.setItem(KEY, JSON.stringify(mem)); } } catch (e) { /* quota / private mode */ } }
    function patch(fn) { fn(load()); save(); }
    function mirrorVoucher(v) { try { if (local) { local.setItem(MIRROR_KEY, JSON.stringify(v)); } } catch (e) { /* ignore */ } }
    return { load: load, save: save, patch: patch, mirrorVoucher: mirrorVoucher };
  })();

  /* ---------- Events (fire-and-forget analytics, no cookies) ---------- */
  var Events = {
    /* Resolves true when the server accepted the event (2xx); never rejects, never throws. */
    send: function (event, meta, code) {
      try {
        var body = JSON.stringify({ target: TARGET, loc: CONFIG.loc, event: event, code: code || null, meta: meta || null });
        try {
          return fetch(CONFIG.eventUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: body, keepalive: true, credentials: 'omit' })
            .then(function (r) { return r.ok; }, function () { return false; });
        } catch (e) {
          var ok = !!(navigator.sendBeacon && navigator.sendBeacon(CONFIG.eventUrl, new Blob([body], { type: 'text/plain' })));
          return Promise.resolve(ok);
        }
      } catch (e) { return Promise.resolve(false); }
    },
    log: function (event, meta, code) { Events.send(event, meta, code); },
    once: function (event, meta, code) {
      var s = Store.load();
      if (s.logged[event]) { return; }
      s.logged[event] = 1; Store.save();
      Events.log(event, meta, code);
    }
  };

  /* ---------- Sound (Web Audio, synthesized) ---------- */
  var Sound = (function () {
    var ctx = null, master = null, muted = !!Store.load().muted;
    var T = IS_PROM ? { lead: 'square', pad: 'sawtooth', chord: 'square' } : { lead: 'sine', pad: 'triangle', chord: 'triangle' };
    function ctxOk() {
      try {
        var AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) { return null; }
        if (!ctx) { ctx = new AC(); master = ctx.createGain(); master.gain.value = muted ? 0 : 1; master.connect(ctx.destination); }
        if (ctx.state !== 'running' && ctx.resume) { ctx.resume().catch(noop); }
        return ctx;
      } catch (e) { return null; }
    }
    function unlock() { ctxOk(); ['touchend', 'pointerup', 'keydown'].forEach(function (e) { document.removeEventListener(e, unlock, true); }); }
    ['touchend', 'pointerup', 'keydown'].forEach(function (e) { document.addEventListener(e, unlock, true); });
    on(document, 'visibilitychange', function () { if (!document.hidden && ctx) { ctxOk(); } });
    on(window, 'pageshow', function () { if (ctx) { ctxOk(); } });
    function tone(type, f0, f1, dur, peak, filterHz, at) {
      try {
        var c = ctxOk(); if (!c || muted) { return; }
        var t = c.currentTime + (at || 0);
        var o = c.createOscillator(), g = c.createGain();
        o.type = type; o.frequency.setValueAtTime(f0, t);
        if (f1) { o.frequency.exponentialRampToValueAtTime(f1, t + dur * 0.8); }
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(peak, t + 0.008);
        g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
        var node = o;
        if (filterHz) { var lp = c.createBiquadFilter(); lp.type = 'lowpass'; lp.frequency.value = filterHz; o.connect(lp); node = lp; }
        node.connect(g); g.connect(master);
        o.start(t); o.stop(t + dur + 0.02);
        o.onended = function () { try { o.disconnect(); g.disconnect(); if (node !== o) { node.disconnect(); } } catch (e) { /* ignore */ } };
      } catch (e) { /* audio must never throw into game logic */ }
    }
    return {
      blip: function () { tone(T.lead, 880, 1320, 0.09, 0.16); },
      unblip: function () { tone(T.lead, 660, 520, 0.08, 0.12); },
      solve: function () { tone(T.lead, 523.25, 0, 0.12, 0.14); tone(T.lead, 783.99, 0, 0.16, 0.14, 0, 0.11); },
      error: function () { tone(T.pad, 110, 55, 0.42, 0.32, 420); if (T.pad === 'sawtooth') { tone('square', 82, 41, 0.4, 0.12, 300); } },
      victory: function () { [523.25, 659.25, 783.99, 1046.5].forEach(function (f, i) { tone(T.chord, f, 0, 1.7, 0.11, T.chord === 'square' ? 2200 : 0, i * 0.09); }); },
      toggle: function () { muted = !muted; Store.patch(function (s) { s.muted = muted; }); if (master) { master.gain.value = muted ? 0 : 1; } return muted; },
      muted: function () { return muted; }
    };
  })();

  /* ---------- Connections (Game A) ---------- */
  var Connections = {
    state: null, els: {},
    groupOf: function (id) { return Math.floor(id / 4); },
    tileText: function (id) { return DATA.prom.groups[Math.floor(id / 4)].tiles[id % 4]; },
    init: function () {
      var e = this.els;
      e.grid = $('cx-grid'); e.lives = $('cx-lives'); e.msg = $('cx-msg'); e.check = $('cx-check');
      e.clear = $('cx-clear'); e.shuffle = $('cx-shuffle'); e.lose = $('cx-lose'); e.retry = $('cx-retry');
      var self = this;
      on(e.check, 'click', function () { self.check(); });
      on(e.clear, 'click', function () { self.clear(); });
      on(e.shuffle, 'click', function () { self.shuffle(); });
      on(e.retry, 'click', function () { self.retry(); });
      on(e.grid, 'click', function (ev) { var btn = ev.target.closest('.cx-tile'); if (btn) { self.onTile(parseInt(btn.getAttribute('data-id'), 10), btn); } });
      var s = Store.load();
      this.state = (s.game && typeof s.game.seed === 'number') ? s.game : this.fresh(1);
      this.render();
      if (s.screen === 'lose') { this.showLosePanel(); }
    },
    fresh: function (attempt) {
      var g = { seed: randomSeed(), lives: 4, solved: [], picked: [], guesses: [], attempt: attempt || 1 };
      Store.patch(function (s) { s.game = g; });
      return g;
    },
    save: function () { var g = this.state; Store.patch(function (s) { s.game = g; }); },
    order: function () {
      var ids = []; for (var i = 0; i < 16; i++) { ids.push(i); }
      shuffle(ids, mulberry32(this.state.seed));
      var solved = this.state.solved, self = this;
      return ids.filter(function (id) { return solved.indexOf(self.groupOf(id)) < 0; });
    },
    bandEl: function (gid, i, revealed) {
      var g = DATA.prom.groups[gid];
      var band = el('div', 'cx-band cx-band--' + ((i % 4) + 1) + ' cx-band--enter' + (revealed ? ' is-revealed' : ''));
      band.setAttribute('role', 'listitem');
      band.appendChild(el('strong', 'cx-band__name', g.name));
      band.appendChild(el('span', 'cx-band__items', g.tiles.join(', ')));
      return band;
    },
    tileEl: function (id, i) {
      var text = this.tileText(id);
      var b = el('button', 'cx-tile' + (text.length >= 34 ? ' cx-tile--xl' : text.length >= 26 ? ' cx-tile--l' : ''), text);
      b.type = 'button'; b.setAttribute('data-id', String(id)); b.style.setProperty('--i', String(i));
      var sel = this.state.picked.indexOf(id) >= 0;
      b.setAttribute('aria-pressed', sel ? 'true' : 'false'); if (sel) { b.classList.add('is-selected'); }
      return b;
    },
    render: function () {
      var e = this.els, self = this;
      if (!e.grid) { return; }
      e.grid.innerHTML = '';
      this.state.solved.forEach(function (gid, i) { e.grid.appendChild(self.bandEl(gid, i, false)); });
      this.order().forEach(function (id, i) { e.grid.appendChild(self.tileEl(id, i)); });
      this.syncLives(); this.syncCheck();
    },
    syncLives: function () {
      var lives = this.state.lives;
      qsa('.cx-life', this.els.lives).forEach(function (dot, i) { dot.classList.toggle('is-lost', i >= lives); });
      if (this.els.lives) { this.els.lives.setAttribute('aria-label', STR.livesLabel(lives)); }
    },
    syncCheck: function () { if (this.els.check) { this.els.check.disabled = this.state.picked.length !== 4; } },
    onTile: function (id, btn) {
      var p = this.state.picked, at = p.indexOf(id);
      if (at >= 0) { p.splice(at, 1); btn.classList.remove('is-selected'); btn.setAttribute('aria-pressed', 'false'); Sound.unblip(); }
      else if (p.length >= 4) { setMsg(this.els.msg, STR.pickFour, 'error'); return; }
      else { p.push(id); btn.classList.add('is-selected'); btn.setAttribute('aria-pressed', 'true'); Sound.blip(); }
      this.save(); this.syncCheck();
    },
    check: function () {
      var st = this.state, self = this;
      if (st.picked.length !== 4) { return; }
      var key = st.picked.slice().sort(function (a, b) { return a - b; }).join('-');
      if (st.guesses.indexOf(key) >= 0) { setMsg(this.els.msg, STR.repeated, 'error'); return; }
      st.guesses.push(key);
      var counts = {}; st.picked.forEach(function (id) { var g = self.groupOf(id); counts[g] = (counts[g] || 0) + 1; });
      var full = null, best = 0;
      for (var g in counts) { if (counts[g] === 4) { full = parseInt(g, 10); } if (counts[g] > best) { best = counts[g]; } }
      var tiles = qsa('.cx-tile.is-selected', this.els.grid);
      if (full !== null) {
        tiles.forEach(function (t) { t.classList.add('is-correct'); });
        Sound.solve();
        st.solved.push(full); st.picked = []; this.save();
        setMsg(this.els.msg, STR.solved(DATA.prom.groups[full].name), 'ok');
        setTimeout(function () { self.render(); if (st.solved.length === 4) { self.win(); } }, REDUCED ? 0 : 280);
        return;
      }
      st.lives -= 1; this.save(); this.syncLives();
      tiles.forEach(function (t) { t.classList.add('is-wrong'); t.addEventListener('animationend', function () { t.classList.remove('is-wrong'); }, { once: true }); });
      Sound.error();
      setMsg(this.els.msg, STR.wrong(st.lives) + (best === 3 ? ' ' + STR.oneOff : ''), 'error');
      if (st.lives <= 0) { this.lose(); }
    },
    shuffle: function () { this.state.seed = randomSeed(); this.state.picked = []; this.save(); this.render(); setMsg(this.els.msg, ''); },
    clear: function () { this.state.picked = []; this.save(); this.render(); setMsg(this.els.msg, ''); },
    lose: function () {
      var st = this.state, self = this;
      Events.log('lose', { lives_left: 0, seconds: App.seconds(), attempts: st.guesses.length, solved: st.solved.length });
      Store.patch(function (s) { s.screen = 'lose'; });
      [this.els.check, this.els.clear, this.els.shuffle].forEach(function (b) { if (b) { b.disabled = true; } });
      setTimeout(function () { self.showLosePanel(); }, REDUCED ? 0 : 700);
    },
    showLosePanel: function () {
      var e = this.els, st = this.state, self = this;
      if (!e.grid) { return; }
      e.grid.innerHTML = '';
      st.solved.forEach(function (gid, i) { e.grid.appendChild(self.bandEl(gid, i, false)); });
      var i = st.solved.length;
      for (var gid = 0; gid < 4; gid++) { if (st.solved.indexOf(gid) < 0) { e.grid.appendChild(self.bandEl(gid, i++, true)); } }
      [e.check, e.clear, e.shuffle].forEach(function (b) { if (b) { b.disabled = true; } });
      this.syncLives();
      if (e.lose) { e.lose.hidden = false; if (e.retry) { e.retry.focus({ preventScroll: true }); } }
    },
    retry: function () {
      var attempt = (this.state.attempt || 1) + 1;
      this.state = this.fresh(attempt);
      Store.patch(function (s) { s.screen = 'game'; });
      if (this.els.lose) { this.els.lose.hidden = true; }
      [this.els.clear, this.els.shuffle].forEach(function (b) { if (b) { b.disabled = false; } });
      setMsg(this.els.msg, '');
      this.render();
      Sound.blip();
    },
    win: function () {
      var st = this.state;
      setTimeout(function () { App.win({ lives_left: st.lives, attempts: st.guesses.length }); }, REDUCED ? 100 : 650);
    }
  };

  /* ---------- Seating (Game B) ---------- */
  var Seating = {
    seats: [null, null, null, null, null, null], attempts: 0, selected: null, drag: null, suppressClick: false, els: {},
    guest: function (id) { for (var i = 0; i < DATA.wedding.guests.length; i++) { if (DATA.wedding.guests[i].id === id) { return DATA.wedding.guests[i]; } } return null; },
    init: function () {
      var e = this.els, self = this;
      e.tray = $('st-tray'); e.table = $('st-table'); e.disc = $('st-disc'); e.msg = $('st-msg'); e.check = $('st-check'); e.reset = $('st-reset');
      e.seats = qsa('.st-seat', e.table); e.rules = qsa('.st-rule');
      var s = Store.load();
      if (s.game && Array.isArray(s.game.seats) && s.game.seats.length === 6) { this.seats = s.game.seats.slice(); this.attempts = s.game.attempts || 0; }
      else { this.save(); }
      on(e.check, 'click', function () { self.check(); });
      on(e.reset, 'click', function () { self.reset(); });
      on(e.tray, 'click', function (ev) {
        if (self.suppressClick) { return; }
        var btn = ev.target.closest('.st-guest');
        if (btn) { self.select(btn.getAttribute('data-guest')); }
      });
      e.seats.forEach(function (seat, idx) {
        on(seat, 'click', function () { if (!self.suppressClick) { self.onSeatTap(idx); } });
      });
      if (window.PointerEvent) { this.bindDrag(); }
      this.renderTray(); this.renderSeats();
    },
    save: function () {
      var seats = this.seats.slice(), attempts = this.attempts;
      Store.patch(function (s) { s.game = { seats: seats, attempts: attempts }; });
    },
    seatOf: function (id) { return this.seats.indexOf(id); },
    renderTray: function () {
      var e = this.els, self = this;
      if (!e.tray) { return; }
      e.tray.innerHTML = '';
      DATA.wedding.guests.forEach(function (g) {
        var idx = self.seatOf(g.id);
        var b = el('button', 'st-guest' + (idx >= 0 ? ' is-seated' : '') + (self.selected === g.id ? ' is-selected' : ''));
        b.type = 'button'; b.setAttribute('data-guest', g.id); b.setAttribute('aria-pressed', self.selected === g.id ? 'true' : 'false');
        var mono = el('span', 'st-guest__mono mono', g.mono); mono.setAttribute('aria-hidden', 'true'); b.appendChild(mono);
        b.appendChild(el('span', 'st-guest__name', g.name));
        b.appendChild(el('span', 'st-guest__desc', g.desc));
        if (idx >= 0) { b.appendChild(el('span', 'st-guest__where mono', '→ място ' + (idx + 1))); }
        e.tray.appendChild(b);
      });
      e.tray.classList.toggle('is-dropzone', !!(self.selected && self.seatOf(self.selected) >= 0));
    },
    renderSeats: function () {
      var self = this;
      this.els.seats.forEach(function (seat, idx) {
        var id = self.seats[idx], g = id ? self.guest(id) : null;
        var chip = qs('.st-seat__chip', seat);
        if (chip) { chip.textContent = g ? g.mono : ''; }
        seat.classList.toggle('is-occupied', !!g);
        seat.classList.toggle('is-selected', !!g && self.selected === id);
        seat.classList.toggle('is-target', !!self.selected);
        seat.setAttribute('aria-label', g ? STR.seatWith(idx + 1, g.name) : STR.seatFree(idx + 1));
      });
      this.syncCheck();
    },
    syncCheck: function () { if (this.els.check) { this.els.check.disabled = this.seats.some(function (x) { return !x; }); } },
    clearRuleMarks: function () {
      this.els.rules.forEach(function (r) { r.classList.remove('is-pass', 'is-fail'); });
      this.els.seats.forEach(function (s) { s.classList.remove('is-conflict'); });
    },
    select: function (id) {
      this.selected = (this.selected === id) ? null : id;
      this.renderTray(); this.renderSeats();
      setMsg(this.els.msg, this.selected ? STR.moveHint : '');
      Sound.blip();
    },
    place: function (id, idx) {
      var prev = this.seats[idx], from = this.seatOf(id);
      if (prev === id) { this.unseat(id); return; }              // tapping the guest's own seat lifts them
      this.seats[idx] = id;
      if (from >= 0) { this.seats[from] = prev; }                 // swap (or vacate the old seat)
      this.selected = null;
      this.attemptsDirty(); this.save(); this.renderTray(); this.renderSeats();
      setMsg(this.els.msg, '');
      Sound.blip();
    },
    unseat: function (id) {
      var from = this.seatOf(id);
      if (from < 0) { return; }
      this.seats[from] = null; this.selected = null;
      this.attemptsDirty(); this.save(); this.renderTray(); this.renderSeats();
      setMsg(this.els.msg, '');
      Sound.unblip();
    },
    attemptsDirty: function () { this.clearRuleMarks(); },
    onSeatTap: function (idx) {
      if (this.selected) { this.place(this.selected, idx); }
      else if (this.seats[idx]) { this.select(this.seats[idx]); }
    },
    reset: function () {
      this.seats = [null, null, null, null, null, null]; this.selected = null;
      this.clearRuleMarks(); this.save(); this.renderTray(); this.renderSeats();
      setMsg(this.els.msg, ''); Sound.unblip();
    },
    evaluate: function () {
      var s = {}, self = this;
      DATA.wedding.guests.forEach(function (g) { s[g.id] = self.seatOf(g.id); });
      return DATA.wedding.rules.map(function (r) { return { id: r.id, pass: !!r.test(s), involves: r.involves }; });
    },
    check: function () {
      var self = this;
      if (this.seats.some(function (x) { return !x; })) { setMsg(this.els.msg, STR.seatAll, 'error'); return; }
      this.clearRuleMarks();
      var res = this.evaluate();
      this.attempts += 1; this.save();
      res.forEach(function (r, i) { var node = qs('.st-rule[data-rule="' + r.id + '"]'); if (node) { node.style.setProperty('--i', String(i)); node.classList.add(r.pass ? 'is-pass' : 'is-fail'); } });
      if (res.every(function (r) { return r.pass; })) { this.celebrate(); return; }
      var conflicts = {};
      res.forEach(function (r) { if (!r.pass) { r.involves.forEach(function (gid) { var idx = self.seatOf(gid); if (idx >= 0) { conflicts[idx] = 1; } if (r.id === 'r3') { conflicts[0] = 1; } }); } });
      this.els.seats.forEach(function (seat, idx) { if (conflicts[idx]) { seat.classList.add('is-conflict'); seat.addEventListener('animationend', function () { seat.classList.remove('is-conflict'); }, { once: true }); } });
      Sound.error();
      setMsg(this.els.msg, STR.conflict, 'error');
    },
    celebrate: function () {
      var self = this;
      [this.els.check, this.els.reset].forEach(function (b) { if (b) { b.disabled = true; } });
      Sound.victory();
      this.els.seats.forEach(function (seat, i) { seat.style.setProperty('--i', String(i)); seat.classList.remove('is-target'); seat.classList.add('is-lit'); });
      if (this.els.disc) { this.els.disc.classList.add('is-win'); }
      setMsg(this.els.msg, STR.seatsOk, 'ok');
      setTimeout(function () { App.win({ attempts: self.attempts }); }, REDUCED ? 300 : 1400);
    },
    /* Pointer Events drag (enhancement; the tap model above is always available). */
    bindDrag: function () {
      var self = this, root = $('igra-app');
      function guestIdFromSource(src) {
        if (src.classList.contains('st-guest')) { return src.getAttribute('data-guest'); }
        if (src.classList.contains('st-seat')) { var idx = parseInt(src.getAttribute('data-seat'), 10) - 1; return self.seats[idx] || null; }
        return null;
      }
      on(root, 'pointerdown', function (ev) {
        if (ev.button !== 0 && ev.pointerType === 'mouse') { return; }
        var src = ev.target.closest('.st-guest, .st-seat.is-occupied');
        if (!src) { return; }
        var id = guestIdFromSource(src); if (!id) { return; }
        self.drag = { id: id, src: src, x0: ev.clientX, y0: ev.clientY, pointerId: ev.pointerId, active: false, ghost: null, over: null };
      });
      on(root, 'pointermove', function (ev) {
        var d = self.drag; if (!d || ev.pointerId !== d.pointerId) { return; }
        if (!d.active) {
          if (Math.abs(ev.clientX - d.x0) < 8 && Math.abs(ev.clientY - d.y0) < 8) { return; }
          d.active = true;
          try { d.src.setPointerCapture(ev.pointerId); } catch (e) { /* ignore */ }
          var g = self.guest(d.id);
          d.ghost = el('div', 'st-ghost', g ? g.mono : ''); document.body.appendChild(d.ghost);
          self.els.seats.forEach(function (s) { s.classList.add('is-target'); });
        }
        d.ghost.style.transform = 'translate3d(' + (ev.clientX - 22) + 'px,' + (ev.clientY - 22) + 'px,0) scale(1.08)';
        var hit = document.elementFromPoint(ev.clientX, ev.clientY);
        var seat = hit && hit.closest ? hit.closest('.st-seat') : null;
        if (d.over && d.over !== seat) { d.over.classList.remove('is-over'); }
        if (seat) { seat.classList.add('is-over'); }
        d.over = seat;
        var overTray = hit && hit.closest ? !!hit.closest('#st-tray') : false;
        if (self.els.tray) { self.els.tray.classList.toggle('is-dropzone', overTray && self.seatOf(d.id) >= 0); }
        ev.preventDefault();
      });
      function end(ev, cancelled) {
        var d = self.drag; if (!d || ev.pointerId !== d.pointerId) { return; }
        self.drag = null;
        if (!d.active) { return; }
        try { d.src.releasePointerCapture(ev.pointerId); } catch (e) { /* ignore */ }
        if (d.ghost) { d.ghost.remove(); }
        if (d.over) { d.over.classList.remove('is-over'); }
        self.suppressClick = true; setTimeout(function () { self.suppressClick = false; }, 300);
        if (cancelled) { self.renderSeats(); self.renderTray(); return; }
        var hit = document.elementFromPoint(ev.clientX, ev.clientY);
        var seat = hit && hit.closest ? hit.closest('.st-seat') : null;
        if (seat) { self.selected = null; self.place(d.id, parseInt(seat.getAttribute('data-seat'), 10) - 1); return; }
        if (hit && hit.closest && hit.closest('#st-tray') && self.seatOf(d.id) >= 0) { self.unseat(d.id); return; }
        self.selected = null; self.renderSeats(); self.renderTray(); Sound.unblip();
      }
      on(root, 'pointerup', function (ev) { end(ev, false); });
      on(root, 'pointercancel', function (ev) { end(ev, true); });
    }
  };

  /* ---------- Voucher (client-only; validated manually by the studio) ---------- */
  var ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';   // 32 symbols, no 0/O/1/I → byte & 31 is bias-free
  function randomChars(n) {
    var buf = new Uint8Array(n), out = '', i;
    try { window.crypto.getRandomValues(buf); } catch (e) { for (i = 0; i < n; i++) { buf[i] = Math.floor(Math.random() * 256); } }
    for (i = 0; i < n; i++) { out += ALPHABET[buf[i] & 31]; }
    return out;
  }
  function dateChar(d) {   // 3-day bucket of the UTC day-of-year ≈ the 72h validity window (eyeball aid for staff)
    var start = Date.UTC(d.getUTCFullYear(), 0, 1), doy = Math.floor((d.getTime() - start) / 864e5);
    return ALPHABET[Math.floor(doy / 3) % 32];
  }
  var Voucher = {
    prefix: IS_PROM ? 'VN-PROM-' : 'VN-WED-',
    getOrCreate: function () {
      var s = Store.load();
      if (s.voucher && s.voucher.code) { if (!s.voucher.synced) { this.sync(s.voucher); } return s.voucher; }
      var now = Date.now();
      var v = {
        code: this.prefix + dateChar(new Date(now)) + randomChars(3), createdAt: now, expiresAt: now + CONFIG.validityHours * 3600e3,
        percent: CONFIG.discountPercent, percentShared: CONFIG.discountPercentShared, boosted: false, synced: false
      };
      s.voucher = v; Store.save(); Store.mirrorVoucher(v);
      this.sync(v);
      return v;
    },
    /* The server-side voucher row is what makes the code work in the site calculators: make sure it exists. */
    sync: function (v) {
      if (this.syncing) { return this.syncing; }
      var self = this;
      this.syncing = Events.send('voucher', null, v.code).then(function (ok) {
        self.syncing = null;
        if (ok) { Store.patch(function (s) { if (s.voucher) { s.voucher.synced = true; } }); Store.mirrorVoucher(Store.load().voucher); }
        return ok;
      });
      return this.syncing;
    },
    current: function () { return Store.load().voucher; },
    percent: function (v) { return v.boosted ? (v.percentShared || CONFIG.discountPercentShared) : (v.percent || CONFIG.discountPercent); },
    /* Sharing the Story (share sheet, download or the manual confirmation) unlocks the higher discount. */
    boost: function (method) {
      var v = this.current(); if (!v) { return; }
      var first = !v.boosted;
      if (first && (v.percentShared || CONFIG.discountPercentShared) > (v.percent || 0)) {
        Store.patch(function (s) { if (s.voucher) { s.voucher.boosted = true; } });
        Store.mirrorVoucher(Store.load().voucher);
        Reveal.renderVoucher(Store.load().voucher);
        Sound.solve();
      }
      Events.send('share', { method: method }, v.code);
    }
  };

  /* ---------- Reveal ---------- */
  function copyText(t) {
    if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
      return navigator.clipboard.writeText(t).then(function () { return true; }, function () { return legacyCopy(t); });
    }
    return Promise.resolve(legacyCopy(t));
  }
  function legacyCopy(t) {
    try {
      var ta = el('textarea'); ta.value = t; ta.setAttribute('readonly', ''); ta.className = 'visually-hidden';
      document.body.appendChild(ta); ta.select(); ta.setSelectionRange(0, t.length);
      var ok = document.execCommand('copy'); ta.remove(); return !!ok;
    } catch (e) { return false; }
  }
  function typewriter(node, text, ms) {
    if (!node) { return; }
    if (REDUCED) { node.textContent = text; return; }
    node.textContent = ''; var i = 0;
    (function step() { node.textContent = text.slice(0, ++i); if (i < text.length) { setTimeout(step, ms); } })();
  }
  var Reveal = {
    timer: null, bound: false,
    show: function () {
      var v = Voucher.getOrCreate(), self = this;
      var code = $('rv-code'); if (code) { code.textContent = v.code; }
      this.renderVoucher(v);
      App.go('reveal');
      typewriter($('rv-boot'), STR.boot, 18);
      if (IS_PROM) { var t = $('rv-title'); if (t) { t.classList.add('rv-glitch-once'); } }
      this.tick(v);
      if (this.timer) { clearInterval(this.timer); }
      this.timer = setInterval(function () { self.tick(v); }, 1000);
      Story.prepare(v.code);
      if (!this.bound) {
        this.bound = true;
        on($('rv-copy'), 'click', function () { self.copy(); });
        on($('rv-generate'), 'click', function () { Story.generate(v.code); });
        on($('rv-share'), 'click', function () { Story.share(v.code); });
        on($('rv-download'), 'click', function () { Story.download(v.code); });
        on($('rv-confirm'), 'click', function () { Voucher.boost('confirm'); var b = $('rv-confirm'); if (b) { b.hidden = true; } });
        on($('rv-use'), 'click', function (ev) { self.use(ev); });
      }
    },
    /* Badge, sub line, boost hint and the "use in the site" link reflect the voucher's current percent. */
    renderVoucher: function (v) {
      var p = Voucher.percent(v), badge = $('rv-badge'), sub = $('rv-sub'), boost = $('rv-boost'), use = $('rv-use');
      if (badge) { badge.textContent = STR.badge(p); }
      if (sub) { sub.textContent = sub.textContent.replace(/^\d+%/, p + '%'); }
      if (boost) {
        if (v.boosted) { boost.textContent = STR.boosted(p); boost.classList.add('is-done'); }
        else { boost.textContent = STR.boostHint(v.percentShared || CONFIG.discountPercentShared); boost.classList.remove('is-done'); }
      }
      if (use) { use.href = CONFIG.useUrl + '?promo=' + encodeURIComponent(v.code) + '#calculator'; }
    },
    /* "Използвай кода в сайта": make sure the server knows the voucher before the calculator asks about it. */
    use: function (ev) {
      var v = Voucher.current(), link = $('rv-use'); if (!v || !link) { return; }
      Events.log('use', null, v.code);
      if (v.synced) { return; }   // normal navigation
      ev.preventDefault();
      var href = link.href, done = false;
      function go() { if (!done) { done = true; window.location.href = href; } }
      setMsg($('rv-story-msg'), STR.syncing);
      Voucher.sync(v).then(go, go);
      setTimeout(go, 2500);
    },
    tick: function (v) {
      var left = v.expiresAt - Date.now(), timerEl = $('rv-timer'), card = $('rv-card');
      if (!timerEl) { return; }
      if (left <= 0) {
        clearInterval(this.timer); this.timer = null;
        timerEl.textContent = STR.expired; if (card) { card.classList.add('is-expired'); }
        var gen = $('rv-generate'); if (gen) { gen.disabled = true; }
        live(STR.expiredLong);
        return;
      }
      var h = Math.floor(left / 36e5), m = Math.floor(left % 36e5 / 6e4), s = Math.floor(left % 6e4 / 1e3);
      timerEl.textContent = STR.validFor(pad2(h) + ':' + pad2(m) + ':' + pad2(s));
    },
    copy: function () {
      var code = $('rv-code'), btn = $('rv-copy');
      copyText(code ? code.textContent : '').then(function (ok) {
        if (btn) { btn.textContent = ok ? STR.copied : STR.copyFail; setTimeout(function () { btn.textContent = STR.copy; }, 2200); }
        if (ok) { Sound.blip(); }
      });
    }
  };

  /* ---------- Story canvas (1080x1920 Instagram Story artefact) ---------- */
  var Story = {
    blob: null, file: null, url: null, ready: null, code: null,
    prepare: function (code) {
      if (this.ready && this.code === code) { return this.ready; }
      var self = this; this.code = code;
      this.ready = Promise.all([fontsReady(), loadImage(CONFIG.logoUrl, 2000)])
        .then(function (r) { return toBlob(self.draw(code, r[1]), 'image/jpeg', 0.92); })
        .then(function (b) {
          self.blob = b;
          try { self.file = new File([b], STR.fileName(code), { type: 'image/jpeg' }); } catch (e) { self.file = null; }
          if (self.url) { try { URL.revokeObjectURL(self.url); } catch (e) { /* ignore */ } }
          self.url = URL.createObjectURL(b);
          return self;
        });
      this.ready.catch(noop);
      return this.ready;
    },
    generate: function (code) {
      var self = this, msg = $('rv-story-msg'), gen = $('rv-generate'), t0 = Date.now();
      setMsg(msg, STR.rendering); if (gen) { gen.disabled = true; }
      this.prepare(code).then(function (s) {
        setTimeout(function () {
          var prev = $('rv-preview'), img = $('rv-preview-img');
          if (img) { img.src = s.url; } if (prev) { prev.hidden = false; }
          setMsg(msg, STR.rendered, 'ok');
          self.offerActions(code);
          if (gen) { gen.textContent = 'Генерирай отново'; gen.disabled = false; }
        }, Math.max(0, (REDUCED ? 0 : 450) - (Date.now() - t0)));
      }, function () { setMsg(msg, STR.renderFail, 'error'); if (gen) { gen.disabled = false; } self.ready = null; });
    },
    offerActions: function (code) {
      var ua = navigator.userAgent || '';
      var inApp = /Instagram|FBAN|FBAV|FB_IAB|Viber|TikTok|Bytedance|Line\//i.test(ua);
      var isIOS = /iP(hone|ad|od)/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
      var canShare = !!(this.file && navigator.canShare && navigator.share && (function (f) { try { return navigator.canShare({ files: [f] }); } catch (e) { return false; } })(this.file));
      var canDownload = ('download' in HTMLAnchorElement.prototype) && !inApp && !isIOS;
      var actions = $('rv-story-actions'), shareBtn = $('rv-share'), dlBtn = $('rv-download'), hint = $('rv-preview-hint'), confirm = $('rv-confirm');
      if (actions) { actions.hidden = !(canShare || canDownload); }
      if (shareBtn) { shareBtn.hidden = !canShare; }
      if (dlBtn) { dlBtn.hidden = canShare || !canDownload; }
      if (hint) { hint.textContent = (!canShare && !canDownload) ? STR.holdToSave : ''; }
      var v = Voucher.current();
      if (confirm) { confirm.hidden = !!(v && v.boosted); }   // manual "I posted it" for in-app browsers / long-press saves
      if (!canShare && !canDownload) { Events.log('share', { method: 'preview' }, code); }
    },
    share: function (code) {   // must stay synchronous up to navigator.share() – iOS requires the user gesture
      if (!this.file || this.busy) { return; }
      var self = this; this.busy = true;
      var p;
      try { p = navigator.share({ files: [this.file], title: STR.shareTitle, text: STR.shareText }); } catch (e) { this.busy = false; return; }
      p.then(function () { Voucher.boost('share'); var b = $('rv-confirm'); if (b) { b.hidden = true; } }, noop).then(function () { self.busy = false; });
    },
    download: function (code) {
      if (!this.url) { return; }
      var a = el('a'); a.href = this.url; a.download = STR.fileName(code); a.rel = 'noopener';
      document.body.appendChild(a); a.click(); a.remove();
      Voucher.boost('download'); var b = $('rv-confirm'); if (b) { b.hidden = true; }
    },
    draw: function (code, logo) {
      var W = 1080, H = 1920, P = IS_PROM;
      var c = document.createElement('canvas'); c.width = W; c.height = H;
      var ctx = c.getContext('2d');
      var accent = P ? '#39ff14' : '#D4AF37', secondary = P ? '#00e5ff' : '#f1e3b5';
      var FONT = 'Montserrat, sans-serif', MONO = 'ui-monospace, Menlo, "Roboto Mono", monospace';
      // 1. background gradient
      var bg = ctx.createLinearGradient(0, 0, 0, H);
      (P ? ['#050807', '#0b1a12', '#050807'] : ['#0b0b0f', '#1a1520', '#0b0b0f']).forEach(function (col, i) { bg.addColorStop([0, 0.55, 1][i], col); });
      ctx.fillStyle = bg; ctx.fillRect(0, 0, W, H);
      // 2. glow
      var glow = ctx.createRadialGradient(540, 720, 0, 540, 720, 720);
      glow.addColorStop(0, rgba(accent, 0.18)); glow.addColorStop(1, rgba(accent, 0));
      ctx.fillStyle = glow; ctx.fillRect(0, 0, W, H);
      // 3. texture
      var rnd = mulberry32(seedFrom(code));
      if (P) {
        ctx.fillStyle = 'rgba(255,255,255,0.028)'; for (var y = 0; y < H; y += 6) { ctx.fillRect(0, y, W, 2); }
        ctx.fillStyle = rgba(accent, 0.10); for (var k = 0; k < 300; k++) { ctx.fillRect(Math.floor(rnd() * W), Math.floor(rnd() * H), 2, 2); }
      } else {
        ctx.fillStyle = rgba(accent, 0.10);
        for (var gy = 48; gy < H; gy += 48) { for (var gx = 48; gx < W; gx += 48) { ctx.beginPath(); ctx.arc(gx, gy, 1.4, 0, Math.PI * 2); ctx.fill(); } }
        ctx.strokeStyle = rgba(accent, 0.35); ctx.lineWidth = 1; ctx.strokeRect(60, 60, 960, 1800);
        ctx.strokeStyle = accent; ctx.lineWidth = 2;
        [[60, 60, 1, 1], [1020, 60, -1, 1], [60, 1860, 1, -1], [1020, 1860, -1, -1]].forEach(function (t) {
          ctx.beginPath(); ctx.moveTo(t[0], t[1] + 28 * t[3]); ctx.lineTo(t[0], t[1]); ctx.lineTo(t[0] + 28 * t[2], t[1]); ctx.stroke();
        });
      }
      ctx.textAlign = 'center'; ctx.textBaseline = 'alphabetic';
      // 4. top label
      ctx.fillStyle = 'rgba(255,255,255,0.55)'; ctx.font = '500 30px ' + MONO;
      ctx.fillText(P ? '// PROTOCOL: VARNA · 1603' : '// СВАТБЕНАТА МАСА · 1603', 540, 300);
      // 5. headline
      var l1 = 'ДЕКРИПТИРАХ', l2 = 'КОДА ЗА ВАРНА';
      var f1 = fitFont(ctx, l1, 920, 118, '700', FONT), f2 = fitFont(ctx, l2, 920, 118, '700', FONT);
      var fh = Math.min(f1, f2); ctx.font = '700 ' + fh + 'px ' + FONT;
      if (P) {
        ctx.fillStyle = rgba(secondary, 0.85); ctx.fillText(l1, 534, 560); ctx.fillText(l2, 534, 700);
        ctx.fillStyle = rgba(accent, 0.85); ctx.fillText(l1, 546, 560); ctx.fillText(l2, 546, 700);
        ctx.fillStyle = '#ffffff'; ctx.fillText(l1, 540, 560); ctx.fillText(l2, 540, 700);
        ctx.save(); ctx.beginPath(); ctx.rect(0, 640, W, 22); ctx.clip(); ctx.fillStyle = secondary; ctx.fillText(l2, 554, 700); ctx.restore();
      } else {
        var hg = ctx.createLinearGradient(0, 460, 0, 720); hg.addColorStop(0, '#f1e3b5'); hg.addColorStop(1, '#D4AF37');
        ctx.fillStyle = hg; ctx.fillText(l1, 540, 560); ctx.fillText(l2, 540, 700);
      }
      // 6. rule line
      ctx.fillStyle = accent; ctx.fillRect(440, 790, 200, 2);
      // 7. badge
      ctx.fillStyle = P ? accent : '#D4AF37'; roundRect(ctx, 260, 860, 560, 110, 55); ctx.fill();
      ctx.fillStyle = P ? '#050807' : '#111111'; ctx.font = '700 44px ' + FONT;
      var storyPercent = Math.max(CONFIG.discountPercentShared || 0, CONFIG.discountPercent || 0);   // the Story advertises the shared level
      drawSpaced(ctx, storyPercent + '% OFF VOUCHER', 540, 931, 4);
      // 8. sub line
      ctx.fillStyle = 'rgba(255,255,255,0.75)'; ctx.font = '400 34px ' + FONT;
      wrapText(ctx, storyPercent + '% отстъпка за фото и видео заснемане', 540, 1040, 920, 44);
      // 9. code box – the real code never appears on the Story (only the prefix, the rest is masked)
      var masked = code.replace(/[^-]+$/, '••••');
      ctx.strokeStyle = rgba(accent, 0.7); ctx.lineWidth = 2; ctx.strokeRect(160, 1090, 760, 150);
      ctx.fillStyle = 'rgba(255,255,255,0.04)'; ctx.fillRect(160, 1090, 760, 150);
      var fc = fitFont(ctx, masked, 680, 84, '700', MONO); ctx.font = '700 ' + fc + 'px ' + MONO; ctx.fillStyle = '#ffffff';
      drawSpaced(ctx, masked, 540, 1195, 6);
      // 10. validity
      ctx.fillStyle = accent; ctx.font = '500 32px ' + FONT;
      drawSpaced(ctx, 'ВАЛИДЕН ' + CONFIG.validityHours + ' ЧАСА', 540, 1330, 5);
      // 11. CTA
      ctx.fillStyle = '#ffffff'; ctx.font = '600 36px ' + FONT;
      wrapText(ctx, 'Тагни @' + CONFIG.instagramHandle + ' за валидация', 540, 1440, 920, 46);
      // 12. logo (same-origin) or wordmark fallback
      var logoBottom = 1620;
      if (logo && logo.naturalWidth) {
        var lw = 300, lh = Math.round(lw * logo.naturalHeight / logo.naturalWidth);
        ctx.drawImage(logo, (W - lw) / 2, 1500, lw, lh); logoBottom = 1500 + lh;
      } else {
        ctx.fillStyle = '#ffffff'; ctx.font = '700 40px ' + FONT; ctx.fillText(CONFIG.studioName.toUpperCase(), 540, 1580); logoBottom = 1590;
      }
      // 13. footer
      if (logoBottom <= 1625) { ctx.fillStyle = 'rgba(255,255,255,0.4)'; ctx.font = '400 26px ' + MONO; ctx.fillText('taketwostudio1603.com', 540, 1665); }
      return c;
    }
  };
  function rgba(hex, a) { var n = parseInt(hex.slice(1), 16); return 'rgba(' + (n >> 16 & 255) + ',' + (n >> 8 & 255) + ',' + (n & 255) + ',' + a + ')'; }
  function seedFrom(str) { var h = 2166136261; for (var i = 0; i < str.length; i++) { h ^= str.charCodeAt(i); h = Math.imul(h, 16777619); } return h >>> 0; }
  function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath(); ctx.moveTo(x + r, y); ctx.lineTo(x + w - r, y); ctx.arcTo(x + w, y, x + w, y + r, r); ctx.lineTo(x + w, y + h - r);
    ctx.arcTo(x + w, y + h, x + w - r, y + h, r); ctx.lineTo(x + r, y + h); ctx.arcTo(x, y + h, x, y + h - r, r); ctx.lineTo(x, y + r); ctx.arcTo(x, y, x + r, y, r); ctx.closePath();
  }
  function fitFont(ctx, text, maxW, startPx, weight, family) {
    var px = startPx;
    for (; px > 40; px -= 4) { ctx.font = weight + ' ' + px + 'px ' + family; if (ctx.measureText(text).width <= maxW) { break; } }
    return px;
  }
  function drawSpaced(ctx, text, cx, y, spacing) {
    var chars = text.split(''), widths = chars.map(function (ch) { return ctx.measureText(ch).width; });
    var total = widths.reduce(function (a, b) { return a + b; }, 0) + spacing * (chars.length - 1);
    var x = cx - total / 2, align = ctx.textAlign; ctx.textAlign = 'left';
    chars.forEach(function (ch, i) { ctx.fillText(ch, x, y); x += widths[i] + spacing; });
    ctx.textAlign = align;
  }
  function wrapText(ctx, text, cx, y, maxW, lh) {
    var words = text.split(' '), line = '', lines = [];
    words.forEach(function (w) { var test = line ? line + ' ' + w : w; if (ctx.measureText(test).width > maxW && line) { lines.push(line); line = w; } else { line = test; } });
    if (line) { lines.push(line); }
    lines.forEach(function (l, i) { ctx.fillText(l, cx, y + i * lh); });
    return y + lines.length * lh;
  }
  function fontsReady() {
    if (!document.fonts || !document.fonts.load) { return new Promise(function (r) { setTimeout(r, 300); }); }
    var probes = [
      ['700 118px Montserrat', 'ДЕКРИПТИРАХ КОДА ЗА ВАРНА'],
      ['600 36px Montserrat', 'Тагни @taketwostudio1603 за валидация'],
      ['500 32px Montserrat', 'ВАЛИДЕН 72 ЧАСА 15% OFF VOUCHER'],
      ['400 34px Montserrat', '15% отстъпка за фото и видео заснемане']
    ];   // Cyrillic sample text is mandatory: montserrat.css is split by unicode-range
    return Promise.race([
      Promise.all(probes.map(function (p) { return document.fonts.load(p[0], p[1]); })),
      new Promise(function (r) { setTimeout(r, 1800); })
    ]).catch(noop);
  }
  function loadImage(src, ms) {
    return new Promise(function (res) {
      var im = new Image(), done = false;
      var t = setTimeout(function () { if (!done) { done = true; res(null); } }, ms);
      im.onload = function () { if (!done) { done = true; clearTimeout(t); res(im); } };
      im.onerror = function () { if (!done) { done = true; clearTimeout(t); res(null); } };
      im.decoding = 'async'; im.src = src;
    });
  }
  function toBlob(c, type, q) {
    return new Promise(function (res, rej) {
      try {
        if (c.toBlob) { c.toBlob(function (b) { if (b) { res(b); } else { rej(new Error('toBlob null')); } }, type, q); return; }
        var d = atob(c.toDataURL(type, q).split(',')[1]), u = new Uint8Array(d.length);
        for (var i = 0; i < d.length; i++) { u[i] = d.charCodeAt(i); }
        res(new Blob([u], { type: type }));
      } catch (e) { rej(e); }
    });
  }

  /* ---------- App (state machine) ---------- */
  var App = {
    screen: 'intro',
    go: function (name) {
      qsa('.igra-screen').forEach(function (s) { s.hidden = (s.getAttribute('data-screen') !== name); });
      document.body.setAttribute('data-screen', name);
      this.screen = name;
      Store.patch(function (s) { if (!(name === 'game' && s.screen === 'lose')) { s.screen = name; } });
      var h = qs('#screen-' + name + ' [tabindex="-1"]'); if (h) { try { h.focus({ preventScroll: true }); } catch (e) { /* ignore */ } }
      window.scrollTo(0, 0);
    },
    seconds: function () { var s = Store.load(); return s.startedAt ? Math.round((Date.now() - s.startedAt) / 1000) : null; },
    start: function () {
      Store.patch(function (s) { if (!s.startedAt) { s.startedAt = Date.now(); } });
      this.go('game');
      var pre = new Image(); pre.src = CONFIG.logoUrl;   // prefetch for the reveal
    },
    win: function (meta) {
      meta = meta || {}; meta.seconds = this.seconds();
      Events.once('win', meta);
      Store.patch(function (s) { s.screen = 'reveal'; });
      if (IS_PROM) { Sound.victory(); }
      Reveal.show();
    },
    bindMute: function () {
      var btn = $('btn-mute'); if (!btn) { return; }
      btn.setAttribute('aria-pressed', Sound.muted() ? 'true' : 'false');
      on(btn, 'click', function () { var m = Sound.toggle(); btn.setAttribute('aria-pressed', m ? 'true' : 'false'); live(m ? STR.soundOff : STR.soundOn); if (!m) { Sound.blip(); } });
    },
    boot: function () {
      var s = Store.load();
      this.bindMute();
      Events.once('scan');
      var Game = IS_PROM ? Connections : Seating;
      Game.init();
      var self = this;
      on($('btn-start'), 'click', function () { self.start(); });
      if (s.screen === 'reveal' && s.voucher) {
        Reveal.show();
      } else if (s.screen === 'game' || s.screen === 'lose') {
        this.go('game');
      } else {
        this.go('intro');
        if (IS_PROM) { var g = qs('.glitch'); if (g) { g.classList.add('is-burst'); setTimeout(function () { g.classList.remove('is-burst'); }, 700); } }
        else { var t = qs('.type'); if (t) { typewriter(t, t.getAttribute('data-text') || t.textContent, 28); } }
      }
      document.body.setAttribute('data-booted', '1');
    }
  };

  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { App.boot(); }); } else { App.boot(); }
})();
