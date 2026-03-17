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
  <link rel="manifest" href="manifest.json" />
  <link rel="icon" href="icon.svg" type="image/svg+xml" />
  <script defer src="app.js"></script>
  <title>Deneysel</title>
  <style>
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
      margin: 0;
      background: #000;
      color: #e6e6e6;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      overflow: hidden;
    }
    :root { --shift-x: 0px; --shift-y: 0px; }

    .screen {
      position: relative;
      height: 100%;
      width: 100%;
      padding: 40px 48px;
    }

    .center {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      z-index: 2;
      pointer-events: none;
      transform: translate3d(var(--shift-x), var(--shift-y), 0) translateY(-8%);
      transition: transform 700ms ease;
    }

    .stack {
      width: min(980px, 92vw);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 10px;
      filter:
        drop-shadow(0 0 18px rgba(0,0,0,0.85))
        drop-shadow(0 0 1px rgba(189,189,189,0.10));
    }

    .clock {
      margin: 0;
      display: block;
      width: 100%;
      text-align: center;
      font-variant-numeric: tabular-nums;
      font-feature-settings: "tnum" 1;
      font-family: "Courier New", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      font-weight: 900;
      letter-spacing: clamp(1px, 0.35vw, 6px);
      font-size: clamp(180px, 18vw, 320px);
      line-height: 1;
    }
    .date {
      margin: 10px 0 0 0;
      font-size: clamp(18px, 1.6vw, 30px);
      font-weight: 800;
      color: #bdbdbd;
      opacity: 0.95;
      text-shadow: 0 2px 0 rgba(0,0,0,0.9);
    }

    .cloud-layer {
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 42%;
      z-index: 1;
      pointer-events: none;
      overflow: hidden;
    }

    .cloud {
      position: absolute;
      left: 0;
      white-space: nowrap;
      color: #f0f0f0;
      opacity: 0.48;
      font-weight: 800;
      letter-spacing: 0.02em;
      line-height: 1;
      text-shadow: 0 1px 0 rgba(0,0,0,0.7);
      will-change: transform;
    }

    .hint {
      position: fixed;
      right: 18px;
      bottom: 18px;
      z-index: 3;
      font-size: 14px;
      font-weight: 900;
      color: #bdbdbd;
      border: 2px solid #2e2e2e;
      border-radius: 999px;
      padding: 8px 12px;
      background: rgba(0,0,0,0.65);
      backdrop-filter: blur(8px);
      user-select: none;
    }

    @media (prefers-reduced-motion: reduce) {
      .center { transition: none; }
      .digit.draw .seg.on { transition: none; }
    }
  </style>
</head>
<body>
  <div class="screen">
    <div class="cloud-layer" id="cloudLayer" aria-hidden="true"></div>

    <div class="center">
      <div class="stack">
        <div class="clock" id="clock" aria-label="Saat">00:00</div>
        <div class="date" id="date" aria-label="Tarih">-</div>
      </div>
    </div>
  </div>

  <div class="hint" id="hint">Tam ekran icin tikla (F11)</div>

  <script>
    const clockEl = document.getElementById('clock');
    const dateEl = document.getElementById('date');
    const cloudLayer = document.getElementById('cloudLayer');
    const hintEl = document.getElementById('hint');

    const params = new URLSearchParams(location.search);
    const rotateMs = Math.min(120_000, Math.max(6_000, parseInt(params.get('cloud_gap_ms') || '14000', 10) || 14_000));
    const cloudPxPerSec = Math.min(240, Math.max(35, parseInt(params.get('cloud_px_s') || '70', 10) || 70));
    const cloudGapPx = Math.min(800, Math.max(120, parseInt(params.get('cloud_gap_px') || '260', 10) || 260));
    const refreshMs = Math.min(30 * 60_000, Math.max(15_000, parseInt(params.get('refresh_ms') || '300000', 10) || 300_000));

    function pad2(n){ return n < 10 ? '0' + n : '' + n; }

    const DIGIT_SEGMENTS = {
      '0': ['a','b','c','e','f','g'],
      '1': ['c','f'],
      '2': ['a','c','d','e','g'],
      '3': ['a','c','d','f','g'],
      '4': ['b','c','d','f'],
      '5': ['a','b','d','f','g'],
      '6': ['a','b','d','e','f','g'],
      '7': ['a','c','f'],
      '8': ['a','b','c','d','e','f','g'],
      '9': ['a','b','c','d','f','g'],
    };

    function createDigitSvg(d){
      const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('class', 'digit');
      svg.setAttribute('viewBox', '0 0 60 100');

      function seg(cls, dAttr){
        const p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        p.setAttribute('class', 'seg ' + cls);
        p.setAttribute('d', dAttr);
        p.setAttribute('pathLength', '100');
        return p;
      }

      // Outline first (behind), then main stroke.
      svg.appendChild(seg('outline a', 'M12 12 H48')); // top
      svg.appendChild(seg('outline b', 'M12 16 V46')); // upper-left
      svg.appendChild(seg('outline c', 'M48 16 V46')); // upper-right
      svg.appendChild(seg('outline d', 'M12 50 H48')); // middle
      svg.appendChild(seg('outline e', 'M12 54 V84')); // lower-left
      svg.appendChild(seg('outline f', 'M48 54 V84')); // lower-right
      svg.appendChild(seg('outline g', 'M12 88 H48')); // bottom

      svg.appendChild(seg('a', 'M12 12 H48')); // top
      svg.appendChild(seg('b', 'M12 16 V46')); // upper-left
      svg.appendChild(seg('c', 'M48 16 V46')); // upper-right
      svg.appendChild(seg('d', 'M12 50 H48')); // middle
      svg.appendChild(seg('e', 'M12 54 V84')); // lower-left
      svg.appendChild(seg('f', 'M48 54 V84')); // lower-right
      svg.appendChild(seg('g', 'M12 88 H48')); // bottom

      const on = DIGIT_SEGMENTS[d] || [];
      for (const s of on) {
        const segs = svg.querySelectorAll('.' + s);
        segs.forEach((el) => el.classList.add('on'));
      }

      return svg;
    }

    function createColonSvg(){
      const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('viewBox', '0 0 100 100');
      svg.setAttribute('class', 'digit');

      const top = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      top.setAttribute('cx', '50');
      top.setAttribute('cy', '36');
      top.setAttribute('r', '6');
      top.setAttribute('fill', '#e6e6e6');
      top.setAttribute('opacity', '0.9');

      const bottom = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      bottom.setAttribute('cx', '50');
      bottom.setAttribute('cy', '64');
      bottom.setAttribute('r', '6');
      bottom.setAttribute('fill', '#e6e6e6');
      bottom.setAttribute('opacity', '0.9');

      svg.appendChild(top);
      svg.appendChild(bottom);
      return svg;
    }

    function makeSlot(ch){
      const slot = document.createElement('span');
      slot.className = 'slot';
      if (ch === ':') {
        slot.classList.add('colon');
        slot.appendChild(createColonSvg());
      } else {
        slot.appendChild(createDigitSvg(ch));
      }
      slot.dataset.value = ch;
      return slot;
    }

    function setClockText(text){
      clockEl.innerHTML = '';
      for (const ch of Array.from(text)) {
        clockEl.appendChild(makeSlot(ch));
      }
      // Initial draw animation so kullanıcı ilk bakışta hareket görsün.
      const slots = Array.from(clockEl.querySelectorAll('.slot')).filter((s) => !s.classList.contains('colon'));
      slots.forEach((slot) => {
        const ch = slot.dataset.value || '';
        if (ch) animateDigit(slot, ch);
      });
    }

    function animateDigit(slot, newCh){
      const oldSvg = slot.querySelector('svg');
      if (oldSvg) oldSvg.classList.add('out');

      const incoming = createDigitSvg(newCh);
      slot.appendChild(incoming);

      // Prepare drawing state for "on" segments only.
      const onSegs = incoming.querySelectorAll('.seg.on');
      onSegs.forEach((p) => {
        let len = 140;
        try { len = Math.max(40, Math.ceil(p.getTotalLength())); } catch (e) {}
        p.style.strokeDasharray = String(len);
        p.style.strokeDashoffset = String(len);
      });
      requestAnimationFrame(() => {
        incoming.classList.add('draw');
        onSegs.forEach((p) => { p.style.strokeDashoffset = '0'; });
      });

      window.setTimeout(() => {
        if (oldSvg && oldSvg.parentNode) oldSvg.parentNode.removeChild(oldSvg);
      }, 520);
    }

    let lastTimeText = '';
    function updateClock(){
      const now = new Date();
      const text = pad2(now.getHours()) + ':' + pad2(now.getMinutes());
      if (text !== lastTimeText) {
        lastTimeText = text;
        clockEl.textContent = text;
      }

      try {
        const fmt = new Intl.DateTimeFormat('tr-TR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        dateEl.textContent = fmt.format(now);
      } catch (e) {
        dateEl.textContent = now.toLocaleDateString();
      }
    }

    updateClock();
    setInterval(updateClock, 1000);

    let items = [];
    let itemIdx = 0;

    async function loadItems(){
      try {
        const res = await fetch('display_data.php?limit=120&per_feed=40&ts=' + Date.now(), { cache: 'no-store' });
        if (!res.ok) return;
        const data = await res.json();
        const list = Array.isArray(data.items) ? data.items : [];
        if (!list.length) return;
        items = list;
        itemIdx = 0;
      } catch (e) {}
    }

    function nextTitle(){
      if (!items.length) return '';
      const t = items[itemIdx % items.length];
      itemIdx++;
      return (t && t.title) ? String(t.title) : '';
    }

    function randBetween(min, max){ return min + Math.random() * (max - min); }

    function createLanes(){
      const h = cloudLayer ? cloudLayer.clientHeight : 0;
      const topPad = 10;
      const bottomPad = 18;
      const laneHeight = 112;
      const usable = Math.max(0, h - topPad - bottomPad);
      let laneCount = Math.floor(usable / laneHeight);
      laneCount = Math.min(3, Math.max(2, laneCount));
      const lanes = [];
      for (let i = 0; i < laneCount; i++) {
        lanes.push({ top: Math.round(topPad + i * laneHeight), nextAt: 0 });
      }
      return lanes;
    }

    function scheduleClouds(){
      if (!cloudLayer) return;
      const lanes = createLanes().map((l, idx) => ({
        id: idx,
        top: l.top,
        busy: false,
        nextAt: 0,
        cooldownMs: 0,
      }));
      let globalNextAt = 0;

      function pickLane(now){
        const ready = [];
        for (const lane of lanes) {
          if (lane.busy) continue;
          if (now < lane.nextAt) continue;
          ready.push(lane);
        }
        if (!ready.length) return null;
        return ready[Math.floor(Math.random() * ready.length)];
      }

      function spawnOnLane(lane, now){
        const title = (nextTitle() || '').trim();
        if (!title) return;

        const speed = Math.max(24, randBetween(cloudPxPerSec * 0.55, cloudPxPerSec * 0.95));
        const fontSize = Math.round(randBetween(22, 32));
        const pixelGapMs = Math.round((cloudGapPx / speed) * 1000);
        const idleAfterMs = Math.round(Math.max(pixelGapMs, randBetween(2500, 9000)));
        lane.busy = true;
        lane.cooldownMs = idleAfterMs;

        const el = document.createElement('div');
        el.className = 'cloud';
        el.dataset.laneId = String(lane.id);
        el.textContent = title.length > 140 ? (title.slice(0, 140) + '...') : title;
        el.style.top = (lane.top + Math.round(randBetween(-6, 6))) + 'px';
        el.style.fontSize = fontSize + 'px';
        el.style.opacity = String(randBetween(0.42, 0.62));
        el.style.filter = 'blur(' + randBetween(0, 0.4).toFixed(1) + 'px)';
        cloudLayer.appendChild(el);

        const layerWidth = cloudLayer.clientWidth || window.innerWidth;
        const startX = layerWidth + 60;
        const w = Math.max(el.scrollWidth || 0, el.getBoundingClientRect().width || 0, 520);
        const endX = -(w + 80);
        const distance = startX - endX;
        const duration = Math.max(18_000, Math.round((distance / speed) * 1000));

        const anim = el.animate(
          [{ transform: 'translateX(' + startX + 'px)' }, { transform: 'translateX(' + endX + 'px)' }],
          { duration, easing: 'linear', fill: 'forwards' }
        );
        const release = () => {
          try { el.remove(); } catch (e) {}
          lane.busy = false;
          lane.nextAt = performance.now() + lane.cooldownMs;
        };
        anim.onfinish = release;
        window.setTimeout(() => {
          if (!lane.busy) return;
          release();
        }, duration + 4000);
      }

      function scheduleNextGlobal(now){
        const minMs = Math.max(2500, Math.round(rotateMs * 0.7));
        const maxMs = Math.max(minMs + 2000, Math.round(rotateMs * 1.4));
        globalNextAt = now + Math.round(randBetween(minMs, maxMs));
      }

      function tick(){
        const now = performance.now();
        if (now < globalNextAt) return;
        const lane = pickLane(now);
        if (!lane) { scheduleNextGlobal(now); return; }
        spawnOnLane(lane, now);
        scheduleNextGlobal(now);
      }

      const startNow = performance.now();
      for (const lane of lanes) {
        lane.busy = false;
        lane.nextAt = startNow + Math.round(randBetween(0, rotateMs));
      }
      globalNextAt = startNow + Math.round(randBetween(2500, 6000));

      // If the tab/screen sleeps and resumes, animations may pause; clear everything to avoid overlaps.
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) return;
        const now = performance.now();
        try { cloudLayer.innerHTML = ''; } catch (e) {}
        scheduleNextGlobal(now);
        for (const lane of lanes) {
          lane.busy = false;
          lane.nextAt = now + Math.round(randBetween(0, rotateMs));
        }
      });

      setInterval(tick, 1000);
    }

    loadItems().then(() => scheduleClouds());
    setInterval(loadItems, refreshMs);

    // Burn-in / image-retention protection (LCD/OLED): periodically shift the centered clock a few pixels.
    // Controls (optional): `?burnin=0` to disable, `?shift=6` to set max pixels, `?shift_ms=90000`.
    (() => {
      const enabled = params.get('burnin') !== '0';
      if (!enabled) return;

      const maxPx = Math.min(18, Math.max(2, parseInt(params.get('shift') || '6', 10) || 6));
      const intervalMs = Math.min(10 * 60_000, Math.max(10_000, parseInt(params.get('shift_ms') || '90000', 10) || 90_000));
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

    document.addEventListener('fullscreenchange', () => {
      if (document.fullscreenElement) hideHint();
    });
    document.addEventListener('click', () => { tryFullscreen(); tryWakeLock(); }, { once: true });
    document.addEventListener('touchstart', () => { tryFullscreen(); tryWakeLock(); }, { once: true, passive: true });
  </script>
</body>
</html>
