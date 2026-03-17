<?php
require_once __DIR__ . '/auth.php';
kc2_auth_require_html();

header('Content-Type: text/html; charset=utf-8');
@ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Europe/Istanbul');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#000000" />
  <meta name="color-scheme" content="dark" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <link rel="manifest" href="manifest.json" />
  <link rel="icon" href="icon.svg" type="image/svg+xml" />
  <script defer src="app.js"></script>
  <title>Kindle Serveur Ekran</title>
  <style>
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    :root { --shift-x: 0px; --shift-y: 0px; }

    body {
      margin: 0;
      background: #000;
      color: #e6e6e6;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      overflow: hidden;
    }

    .screen {
      height: 100%;
      width: 100%;
      padding: 40px 48px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 18px;
      transform: translate3d(var(--shift-x), var(--shift-y), 0);
      transition: transform 700ms ease;
    }

    .clockBlock {
      display: grid;
      gap: 18px;
    }

    /* Layout: adapt by aspect ratio (set via JS: body[data-layout]). */
    body[data-layout="wide"] .screen {
      padding: 36px 44px;
      display: grid;
      grid-template-columns: minmax(360px, 0.95fr) 1.05fr;
      gap: 26px;
      align-items: center;
      justify-content: stretch;
    }
    body[data-layout="wide"] .clockBlock { gap: 14px; }
    body[data-layout="wide"] .time {
      font-size: clamp(160px, 14vw, 280px);
      text-align: left;
    }
    body[data-layout="wide"] .date {
      text-align: left;
      font-size: clamp(20px, 1.8vw, 34px);
    }
    body[data-layout="wide"] .overlay {
      width: 100%;
      margin: 0;
      max-width: none;
    }

    body[data-layout="mid"] .screen { padding: 34px 26px; }

    body[data-layout="square"] .screen { padding: 28px 18px; }
    body[data-layout="square"] .screen { justify-content: flex-start; }
    body[data-layout="square"] .time { font-size: clamp(170px, 26vw, 320px); }
    body[data-layout="square"] .date { font-size: clamp(22px, 4.2vw, 40px); }

    body[data-layout="tall"] .screen { padding: 22px 16px; gap: 14px; justify-content: flex-start; }
    body[data-layout="tall"] .time { font-size: clamp(120px, 20vh, 220px); }
    body[data-layout="tall"] .date { font-size: clamp(18px, 3.2vh, 32px); }
    body[data-layout="tall"] .overlay { width: 100%; }
    @media (prefers-reduced-motion: reduce) {
      .screen { transition: none; }
    }

    .time {
      font-family: "Courier New", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      font-weight: 900;
      letter-spacing: clamp(3px, 0.6vw, 10px);
      font-size: clamp(220px, 22vw, 360px);
      line-height: 1;
      text-align: center;
    }

    .date {
      font-size: clamp(26px, 2.4vw, 46px);
      font-weight: 800;
      text-align: center;
      opacity: 0.9;
    }

    .overlay {
      width: min(1200px, 92vw);
      margin: 0 auto;
      border: 2px solid #2e2e2e;
      background: rgba(0,0,0,0.72);
      backdrop-filter: blur(8px);
      border-radius: 18px;
      padding: 18px 22px;
      position: relative;
      overflow: hidden;
    }

    /* Optional "magical" refresh: a warm wand-sweep highlight. */
    .overlay::after {
      content: "";
      position: absolute;
      inset: -40% -40%;
      pointer-events: none;
      opacity: 0;
      transform: translateX(-35%) rotate(12deg);
      background:
        radial-gradient(closest-side, rgba(220,200,120,0.20), rgba(0,0,0,0) 60%),
        linear-gradient(120deg, rgba(0,0,0,0) 35%, rgba(220,200,120,0.22) 50%, rgba(0,0,0,0) 65%);
      filter: blur(6px);
      mix-blend-mode: screen;
    }
    .overlay.spell::after { animation: wandSweep 900ms ease forwards; }
    @keyframes wandSweep {
      0% { opacity: 0; transform: translateX(-45%) rotate(12deg); }
      18% { opacity: 1; }
      60% { opacity: 0.75; }
      100% { opacity: 0; transform: translateX(45%) rotate(12deg); }
    }

    .headline {
      font-size: clamp(28px, 2.8vw, 54px);
      font-weight: 900;
      line-height: 1.15;
      margin: 0;
    }

    .meta {
      margin-top: 10px;
      font-size: clamp(16px, 1.4vw, 22px);
      font-weight: 800;
      opacity: 0.85;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .tag {
      display: inline-block;
      border: 2px solid #2e2e2e;
      border-radius: 999px;
      padding: 6px 10px;
      background: rgba(0,0,0,0.4);
    }

    /* Calmer transitions: no blur/scale. */
    .fade { transition: opacity 200ms ease; opacity: 1; }
    .fade.out { opacity: 0; }

    .hint {
      position: fixed;
      left: 50%;
      bottom: 18px;
      transform: translateX(-50%);
      font-size: 14px;
      font-weight: 800;
      color: #bdbdbd;
      background: rgba(0,0,0,0.6);
      border: 2px solid #2e2e2e;
      padding: 8px 12px;
      border-radius: 999px;
      user-select: none;
    }
  </style>
</head>
<body>
  <div class="screen">
    <div class="clockBlock">
      <div id="time" class="time">00:00</div>
      <div id="date" class="date">-</div>
    </div>
    <div class="overlay fade" id="overlay" aria-live="polite">
      <p class="headline" id="headline">Haberler yukleniyor...</p>
      <div class="meta" id="meta"></div>
    </div>
  </div>

  <div id="hint" class="hint">Tam ekran icin tikla (F11)</div>

  <script>
    const timeEl = document.getElementById('time');
    const dateEl = document.getElementById('date');
    const headlineEl = document.getElementById('headline');
    const metaEl = document.getElementById('meta');
    const overlayEl = document.getElementById('overlay');
    const hintEl = document.getElementById('hint');

    const params = new URLSearchParams(location.search);

    function setLayoutMode() {
      const forced = String(params.get('layout') || '').trim().toLowerCase();
      if (forced === 'wide' || forced === 'mid' || forced === 'square' || forced === 'tall') {
        document.body.setAttribute('data-layout', forced);
        return forced;
      }
      const w = Math.max(1, window.innerWidth || 1);
      const h = Math.max(1, window.innerHeight || 1);
      const ar = w / h;
      const mode = ar >= 1.55 ? 'wide' : (ar >= 1.15 ? 'mid' : (ar >= 0.95 ? 'square' : 'tall'));
      document.body.setAttribute('data-layout', mode);
      return mode;
    }
    let resizeTimer = 0;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(setLayoutMode, 150);
    });
    setLayoutMode();

    // Calm defaults; enable HP-style typing with `?hp=1`.
    const hp = params.get('hp') === '1';
    const magicEnabled = hp || params.get('magic') === '1';
    const flickerEnabled = hp ? (params.get('flicker') !== '0') : (params.get('flicker') === '1');
    const timeTypeEnabled = hp || params.get('time_type') === '1';
    const typeOnRefresh = hp || params.get('type_refresh') === '1';
    const typeOnRotate = hp || params.get('type_rotate') === '1';
    const spellOnRefresh = hp || params.get('spell_refresh') === '1';
    const spellOnRotate = hp || params.get('spell_rotate') === '1';

    const rotateMs = Math.min(5 * 60_000, Math.max(5_000, parseInt(params.get('rotate_ms') || '30000', 10) || 30_000));
    const refreshMs = Math.min(30 * 60_000, Math.max(15_000, parseInt(params.get('refresh_ms') || '300000', 10) || 300_000));
    const typeMsHeadline = Math.min(120, Math.max(8, parseInt(params.get('type_ms') || '22', 10) || 22));
    const typeMsTime = Math.min(200, Math.max(8, parseInt(params.get('time_type_ms') || '60', 10) || 60));

    function pad2(n){ return n < 10 ? '0' + n : '' + n; }
    function splitChars(s){ return Array.from(String(s)); }
    function sleep(ms){ return new Promise((r) => setTimeout(r, ms)); }

    const TYPE_GLYPHS = splitChars("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789*+~");
    function randGlyph(){ return TYPE_GLYPHS[(Math.random() * TYPE_GLYPHS.length) | 0]; }

    function createTyper(){
      let token = 0;
      return {
        cancel(){ token++; },
        async type(el, text, msPerChar){
          const my = ++token;
          if (!el) return;
          const chars = splitChars(text);
          el.textContent = '';

          for (let i = 0; i < chars.length; i++) {
            if (my !== token) return;
            const ch = chars[i];
            if (flickerEnabled && magicEnabled && ch.trim() !== '') {
              for (let f = 0; f < 2; f++) {
                el.textContent = chars.slice(0, i).join('') + randGlyph();
                await sleep(Math.max(10, Math.floor(msPerChar / 3)));
                if (my !== token) return;
              }
            }
            el.textContent = chars.slice(0, i).join('') + ch;
            await sleep(msPerChar);
          }
        }
      };
    }

    const headlineTyper = createTyper();
    const timeTyper = createTyper();

    let lastTimeText = '';
    function tickClock(){
      const now = new Date();
      const hhmm = pad2(now.getHours()) + ':' + pad2(now.getMinutes());
      if (hhmm !== lastTimeText) {
        lastTimeText = hhmm;
        if (timeTypeEnabled) timeTyper.type(timeEl, hhmm, typeMsTime);
        else timeEl.textContent = hhmm;
      }

      try {
        const fmt = new Intl.DateTimeFormat('tr-TR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        dateEl.textContent = fmt.format(now);
      } catch (e) {
        dateEl.textContent = now.toLocaleDateString();
      }
    }
    tickClock();
    setInterval(tickClock, 1000);

    let items = [];
    let idx = 0;
    let rotateTimer = null;
    let spellTimer = null;

    function setTags(tags){
      metaEl.innerHTML = '';
      for (const t of tags) {
        const span = document.createElement('span');
        span.className = 'tag';
        span.textContent = t;
        metaEl.appendChild(span);
      }
    }

    function showItem(item, opts){
      if (!item) return;
      const o = opts || {};
      overlayEl.classList.add('out');
      if (spellTimer) clearTimeout(spellTimer);
      headlineTyper.cancel();

      if (o.spell && magicEnabled) overlayEl.classList.add('spell');

      setTimeout(() => {
        const title = item.title || '-';
        if (o.type) headlineTyper.type(headlineEl, title, typeMsHeadline);
        else headlineEl.textContent = title;

        const tags = [];
        if (item.source) tags.push(item.source);
        if (item.meta) tags.push(item.meta);
        setTags(tags);
        overlayEl.classList.remove('out');
      }, 200);

      if (o.spell && magicEnabled) {
        spellTimer = setTimeout(() => overlayEl.classList.remove('spell'), 950);
      }
    }

    function nextItem(){
      if (!items.length) return;
      idx = (idx + 1) % items.length;
      showItem(items[idx], { type: typeOnRotate, spell: spellOnRotate });
    }

    async function loadItems(){
      try {
        const res = await fetch('display_data.php?limit=80&per_feed=30&ts=' + Date.now(), { cache: 'no-store' });
        if (!res.ok) return;
        const data = await res.json();
        const list = Array.isArray(data.items) ? data.items : [];
        if (!list.length) return;
        items = list;
        idx = 0;
        showItem(items[0], { type: typeOnRefresh, spell: spellOnRefresh });
        if (rotateTimer) clearInterval(rotateTimer);
        rotateTimer = setInterval(nextItem, rotateMs);
      } catch (e) {}
    }
    loadItems();
    setInterval(loadItems, refreshMs);

    // Burn-in / image-retention protection (LCD/OLED): periodically shift whole layout a few pixels.
    // Controls (optional): `?burnin=0` to disable, `?shift=8` to set max pixels, `?shift_ms=60000`.
    (() => {
      const enabled = params.get('burnin') !== '0';
      if (!enabled) return;

      const maxPx = Math.min(24, Math.max(2, parseInt(params.get('shift') || '10', 10) || 10));
      const intervalMs = Math.min(10 * 60_000, Math.max(10_000, parseInt(params.get('shift_ms') || '60000', 10) || 60_000));
      const root = document.documentElement;
      let last = { x: 0, y: 0 };

      function setShift(x, y){
        root.style.setProperty('--shift-x', x + 'px');
        root.style.setProperty('--shift-y', y + 'px');
        last = { x, y };
      }

      function pickShift(){
        for (let tries = 0; tries < 10; tries++) {
          const x = Math.floor((Math.random() * (maxPx * 2 + 1)) - maxPx);
          const y = Math.floor((Math.random() * (maxPx * 2 + 1)) - maxPx);
          if (x !== last.x || y !== last.y) return { x, y };
        }
        return { x: -last.x, y: -last.y };
      }

      function apply(){
        const { x, y } = pickShift();
        setShift(x, y);
      }

      apply();
      setInterval(apply, intervalMs);
      document.addEventListener('visibilitychange', () => { if (!document.hidden) apply(); });
    })();

    async function tryFullscreen(){
      if (document.fullscreenElement) return;
      const el = document.documentElement;
      if (!el.requestFullscreen) return;
      try { await el.requestFullscreen({ navigationUI: 'hide' }); } catch (e) {}
    }

    async function tryWakeLock(){
      if (!('wakeLock' in navigator)) return;
      try { await navigator.wakeLock.request('screen'); } catch (e) {}
    }

    function hideHint(){
      if (!hintEl) return;
      hintEl.style.display = 'none';
    }

    document.addEventListener('fullscreenchange', () => { if (document.fullscreenElement) hideHint(); });
    document.addEventListener('click', () => { tryFullscreen(); tryWakeLock(); }, { once: true });
    document.addEventListener('touchstart', () => { tryFullscreen(); tryWakeLock(); }, { once: true, passive: true });
  </script>
</body>
</html>
