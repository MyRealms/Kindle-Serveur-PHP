<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
kc2_auth_require_html();

header('Content-Type: text/html; charset=utf-8');
@ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Europe/Istanbul');

function h(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$endpoint = isset($_GET['data']) ? (string)$_GET['data'] : 'display_data.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 60;
if ($limit < 10) $limit = 10;
if ($limit > 150) $limit = 150;

$perFeed = isset($_GET['per_feed']) ? (int)$_GET['per_feed'] : 25;
if ($perFeed < 5) $perFeed = 5;
if ($perFeed > 60) $perFeed = 60;

$refreshMs = isset($_GET['refresh_ms']) ? (int)$_GET['refresh_ms'] : 300_000;
if ($refreshMs < 30_000) $refreshMs = 30_000;
if ($refreshMs > 30 * 60_000) $refreshMs = 30 * 60_000;

$minRotateMs = isset($_GET['min_rotate_ms']) ? (int)$_GET['min_rotate_ms'] : 30_000;
$maxRotateMs = isset($_GET['max_rotate_ms']) ? (int)$_GET['max_rotate_ms'] : 90_000;
if ($minRotateMs < 10_000) $minRotateMs = 10_000;
if ($maxRotateMs < $minRotateMs) $maxRotateMs = $minRotateMs;
if ($maxRotateMs > 10 * 60_000) $maxRotateMs = 10 * 60_000;

$now = new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul'));
$serverTime = $now->format('H:i');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex,nofollow,noarchive" />
  <meta name="description" content="Özel kullanıma kapalıdır; yalnızca yetkili giriş ile erişilir." />
  <meta name="theme-color" content="#000000" />
  <meta name="color-scheme" content="dark" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <link rel="manifest" href="manifest.json" />
  <link rel="icon" href="icon.svg" type="image/svg+xml" />
  <script defer src="app.js"></script>
  <title>Hogwarts Haberleri</title>
  <style>
    :root {
      --bg: #000;
      --panel: rgba(255,255,255,.05);
      --ink: #f2f2f2;
      --muted: rgba(242,242,242,.72);
      --line: rgba(242,242,242,.28);
      --line2: rgba(242,242,242,.14);
      --shadow: 0 22px 70px rgba(0,0,0,.65);
      --xfade: 1400ms;
      --pad: clamp(12px, 1.5vmin, 22px);
      --gap: clamp(12px, 1.6vmin, 22px);
    }

    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
      margin: 0;
      background: var(--bg);
      color: var(--ink);
      font-family: Georgia, "Times New Roman", Times, serif;
      overflow: hidden;
      user-select: none;
      cursor: default;
    }

    .wall {
      width: 100vw;
      height: 100vh;
      width: 100dvw;
      height: 100dvh;
      padding: var(--pad);
      display: flex;
      flex-direction: column;
      gap: var(--gap);
      background:
        radial-gradient(900px 520px at 25% 10%, rgba(255,255,255,.08), transparent 60%),
        radial-gradient(700px 520px at 80% 30%, rgba(255,255,255,.05), transparent 55%),
        linear-gradient(180deg, #000, #121212);
    }

    .paper {
      flex: 1;
      min-height: 0;
      border: 2px solid var(--line);
      box-shadow: var(--shadow);
      background:
        radial-gradient(900px 550px at 40% 10%, rgba(255,255,255,.07), transparent 60%),
        repeating-linear-gradient(0deg, rgba(255,255,255,.020), rgba(255,255,255,.020) 1px, transparent 1px, transparent 4px),
        linear-gradient(180deg, #0b0b0b, #000);
      padding: var(--pad);
      display: flex;
      flex-direction: column;
      gap: var(--gap);
      overflow: hidden;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: var(--gap);
      padding-bottom: clamp(10px, 1.2vmin, 14px);
      border-bottom: 2px solid var(--line);
      flex: 0 0 auto;
    }

    .brandTitle {
      font-family: Impact, Haettenschweiler, "Arial Black", Georgia, serif;
      font-weight: 900;
      font-size: clamp(44px, 6.4vmin, 92px);
      letter-spacing: .08em;
      text-transform: uppercase;
      line-height: 1;
      white-space: nowrap;
    }
    .brandDate {
      margin-top: 8px;
      font-size: clamp(16px, 1.9vmin, 22px);
      color: var(--muted);
      font-weight: 800;
      text-transform: capitalize;
    }

    .clock {
      text-align: right;
      flex: 0 0 auto;
    }
    .clockTime {
      font-family: "Courier New", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      font-weight: 900;
      letter-spacing: clamp(2px, 0.45vw, 10px);
      font-variant-numeric: tabular-nums;
      font-size: clamp(76px, 9.2vmin, 168px);
      line-height: 1;
      white-space: nowrap;
    }

    .grid {
      flex: 1;
      min-height: 0;
      display: grid;
      grid-template-columns: 1.15fr 1.85fr 1.15fr;
      grid-template-areas: "left main photo";
      gap: var(--gap);
      overflow: hidden;
    }

    .col {
      min-height: 0;
      border: 1px solid var(--line2);
      background: var(--panel);
      padding: clamp(10px, 1.2vmin, 14px);
      overflow: hidden;
      position: relative;
    }
    .col-left { grid-area: left; }
    .col-main { grid-area: main; }
    .col-photo { grid-area: photo; }

    .swap {
      position: relative;
      height: 100%;
      min-height: 0;
      overflow: hidden;
    }
    .layer {
      position: absolute;
      inset: 0;
      opacity: 0;
      transition: opacity var(--xfade) ease;
      overflow: hidden;
    }
    .layer.active { opacity: 1; }

    @media (prefers-reduced-motion: reduce) {
      .layer { transition: none; }
    }

    /* Left column (briefs) */
    .briefs {
      height: 100%;
      display: flex;
      flex-direction: column;
      gap: clamp(10px, 1.2vmin, 14px);
      overflow: hidden;
    }
    .brief {
      padding-bottom: clamp(10px, 1.1vmin, 12px);
      border-bottom: 1px solid var(--line2);
    }
    .brief:last-child { border-bottom: none; padding-bottom: 0; }
    .briefTitle {
      font-weight: 900;
      font-size: clamp(16px, 1.9vmin, 22px);
      line-height: 1.12;
      letter-spacing: .01em;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .briefMeta {
      margin-top: 6px;
      font-size: clamp(13px, 1.6vmin, 18px);
      color: var(--muted);
      line-height: 1.15;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Center column (main story) */
    .main {
      height: 100%;
      display: flex;
      flex-direction: column;
      gap: clamp(12px, 1.3vmin, 16px);
      overflow: hidden;
    }
    .mainHeadline {
      margin: 0;
      font-weight: 900;
      letter-spacing: .02em;
      text-transform: uppercase;
      font-size: clamp(26px, 3.4vmin, 46px);
      line-height: 1.03;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .mainMeta {
      font-size: clamp(14px, 1.6vmin, 19px);
      color: var(--muted);
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .08em;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      border-top: 1px solid var(--line2);
      padding-top: clamp(8px, 1.0vmin, 12px);
    }
    .mainDeck {
      margin: 0;
      font-size: clamp(16px, 1.9vmin, 22px);
      line-height: 1.18;
      color: rgba(242,242,242,.86);
      font-weight: 700;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .story {
      flex: 1;
      min-height: 0;
      overflow: hidden;
      font-size: clamp(14px, 1.6vmin, 19px);
      line-height: 1.22;
      column-count: 2;
      column-gap: clamp(18px, 2.0vmin, 26px);
      column-rule: 1px solid var(--line2);
    }
    .story p { margin: 0 0 0.8em; }
    .story p:last-child { margin-bottom: 0; }
    .dropcap:first-letter {
      float: left;
      font-size: 3.2em;
      line-height: .85;
      padding-right: 10px;
      font-weight: 900;
      color: var(--ink);
    }

    /* Right column (photo) */
    .photoWrap {
      height: 100%;
      display: grid;
      grid-template-rows: 1fr auto;
      gap: clamp(10px, 1.2vmin, 14px);
      overflow: hidden;
    }
    .photoFrame {
      position: relative;
      border: 1px solid var(--line);
      overflow: hidden;
      background: #0a0a0a;
      min-height: 0;
    }
    .photoStack { z-index: 1; }
    .photoPlaceholder {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 18px;
      text-align: center;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .14em;
      font-weight: 900;
      font-size: clamp(14px, 1.7vmin, 20px);
      background:
        radial-gradient(closest-side at 50% 40%, rgba(255,255,255,.05), transparent 60%),
        linear-gradient(180deg, rgba(255,255,255,.03), rgba(0,0,0,0));
      z-index: 0;
    }
    .photoStack {
      position: absolute;
      inset: 0;
    }
    .photoStack img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0;
      transition: opacity calc(var(--xfade) + 600ms) ease;
      filter: contrast(1.25) saturate(.85) brightness(.95);
      transform: translate(0,0) scale(1.08);
      will-change: transform, opacity, filter;
    }
    .photoStack img.active { opacity: 1; }
    @media (prefers-reduced-motion: reduce) {
      .photoStack img { transition: none; transform: none; }
    }

    .photoFrame::before {
      content: "";
      position: absolute;
      inset: 0;
      pointer-events: none;
      opacity: .18;
      mix-blend-mode: overlay;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='260' height='260'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='260' height='260' filter='url(%23n)' opacity='.35'/%3E%3C/svg%3E");
      background-size: 320px 320px;
      animation: grainMove 8s steps(6) infinite;
      z-index: 2;
    }
    .photoFrame::after {
      content: "";
      position: absolute;
      inset: -1px;
      pointer-events: none;
      background:
        radial-gradient(closest-side at 50% 40%, rgba(0,0,0,0) 0, rgba(0,0,0,.32) 75%, rgba(0,0,0,.55) 100%);
      opacity: .9;
      z-index: 2;
    }
    @keyframes grainMove {
      0% { transform: translate(0,0); }
      25% { transform: translate(-2%, 1%); }
      50% { transform: translate(1%, -2%); }
      75% { transform: translate(2%, 2%); }
      100% { transform: translate(0,0); }
    }

    .photoCaption { border-top: 1px solid var(--line2); padding-top: clamp(8px, 1.0vmin, 12px); }
    .capTitle {
      font-weight: 900;
      font-size: clamp(18px, 2.1vmin, 26px);
      line-height: 1.08;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .capDesc {
      margin-top: 8px;
      color: rgba(242,242,242,.85);
      font-size: clamp(14px, 1.6vmin, 19px);
      line-height: 1.18;
      display: -webkit-box;
      -webkit-line-clamp: 4;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* Layout modes (set by JS via body[data-layout]) */
    body[data-layout="mid"] .grid { grid-template-columns: 1.2fr 1.6fr 1.2fr; }
    body[data-layout="square"] .grid { grid-template-columns: 1fr 1.45fr 1fr; }
    body[data-layout="square"] .story { column-count: 1; }
    body[data-layout="tall"] .grid {
      grid-template-columns: 1fr;
      grid-template-rows: 1.25fr 1.05fr .9fr;
      grid-template-areas:
        "main"
        "photo"
        "left";
    }
    body[data-layout="tall"] .story { column-count: 1; }
  </style>
</head>
<body>
  <div
    class="wall"
    data-endpoint="<?php echo h($endpoint); ?>"
    data-limit="<?php echo (int)$limit; ?>"
    data-per-feed="<?php echo (int)$perFeed; ?>"
    data-refresh-ms="<?php echo (int)$refreshMs; ?>"
    data-min-rotate-ms="<?php echo (int)$minRotateMs; ?>"
    data-max-rotate-ms="<?php echo (int)$maxRotateMs; ?>"
  >
    <div class="paper">
      <header class="header">
        <div class="brand">
          <div class="brandTitle">HOGWARTS HABERLERİ</div>
          <div class="brandDate" id="liveDate">—</div>
        </div>
        <div class="clock" aria-label="Saat">
          <div class="clockTime" id="liveClock"><?php echo h($serverTime); ?></div>
        </div>
      </header>

      <main class="grid" role="main" aria-label="Canlı gazete">
        <section class="col col-left" aria-label="Kısa haberler">
          <div class="swap" id="leftSwap" aria-live="polite">
            <div class="layer active"></div>
            <div class="layer"></div>
          </div>
        </section>

        <section class="col col-main" aria-label="Ana haber">
          <div class="swap" id="mainSwap" aria-live="polite">
            <div class="layer active"></div>
            <div class="layer"></div>
          </div>
        </section>

        <section class="col col-photo" aria-label="Fotoğraf">
          <div class="photoWrap">
            <div class="photoFrame" id="photoFrame">
              <div class="photoStack" aria-hidden="true">
                <img id="photoA" alt="" />
                <img id="photoB" alt="" />
              </div>
              <div class="photoPlaceholder" id="photoPlaceholder">Fotoğraf bekleniyor…</div>
            </div>
            <div class="swap photoCaption" id="photoCaptionSwap" aria-live="polite">
              <div class="layer active"></div>
              <div class="layer"></div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <script>
    (function () {
      var root = document.querySelector('.wall');
      if (!root) return;

      var prefersReduced = false;
      try { prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}

      var endpoint = root.getAttribute('data-endpoint') || 'display_data.php';
      var limit = parseInt(root.getAttribute('data-limit') || '60', 10) || 60;
      var perFeed = parseInt(root.getAttribute('data-per-feed') || '25', 10) || 25;
      var refreshMs = parseInt(root.getAttribute('data-refresh-ms') || '300000', 10) || 300000;
      var minRotateMs = parseInt(root.getAttribute('data-min-rotate-ms') || '30000', 10) || 30000;
      var maxRotateMs = parseInt(root.getAttribute('data-max-rotate-ms') || '90000', 10) || 90000;

      var leftSwap = document.getElementById('leftSwap');
      var mainSwap = document.getElementById('mainSwap');
      var captionSwap = document.getElementById('photoCaptionSwap');
      var photoA = document.getElementById('photoA');
      var photoB = document.getElementById('photoB');
      var photoPlaceholder = document.getElementById('photoPlaceholder');
      var liveClock = document.getElementById('liveClock');
      var liveDate = document.getElementById('liveDate');

      var items = [];
      var order = [];
      var edition = 0;
      var activePhoto = 0; // 0 => A, 1 => B
      var photoAnim = null;
      var rotateTimer = 0;
      var refreshTimer = 0;

      function randInt(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
      }

      function shuffle(arr) {
        for (var i = arr.length - 1; i > 0; i--) {
          var j = Math.floor(Math.random() * (i + 1));
          var t = arr[i];
          arr[i] = arr[j];
          arr[j] = t;
        }
        return arr;
      }

      function computeLayoutMode() {
        var w = Math.max(1, window.innerWidth || 1);
        var h = Math.max(1, window.innerHeight || 1);
        var ar = w / h;
        var mode = ar >= 1.55 ? 'wide' : (ar >= 1.15 ? 'mid' : (ar >= 0.95 ? 'square' : 'tall'));
        document.body.setAttribute('data-layout', mode);
        return mode;
      }

      function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (ch) {
          return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[ch];
        });
      }

      function stripHtml(html) {
        var s = String(html || '');
        if (!s) return '';
        try {
          var d = document.createElement('div');
          d.innerHTML = s;
          return (d.textContent || '').replace(/\s+/g, ' ').trim();
        } catch (e) {
          return s.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        }
      }

      function pickText(v, fallback) {
        var t = String(v || '').replace(/\s+/g, ' ').trim();
        return t !== '' ? t : (fallback || '');
      }

      function truncate(s, max) {
        var t = String(s || '').replace(/\s+/g, ' ').trim();
        if (t.length <= max) return t;
        return t.slice(0, Math.max(0, max - 1)).trimEnd() + '…';
      }

      function formatMeta(it) {
        var parts = [];
        if (it.source) parts.push(it.source);
        if (it.meta) parts.push(it.meta);
        return parts.join(' • ');
      }

      function ensureOrder() {
        var n = items.length;
        if (!n) { order = []; return; }
        if (order.length !== n) {
          order = [];
          for (var i = 0; i < n; i++) order.push(i);
          shuffle(order);
          return;
        }
        if (Math.random() < 0.25) shuffle(order);
      }

      function countsForMode(mode) {
        if (mode === 'wide') return { briefs: 7, storyPars: 7 };
        if (mode === 'mid') return { briefs: 6, storyPars: 6 };
        if (mode === 'square') return { briefs: 5, storyPars: 5 };
        return { briefs: 4, storyPars: 4 }; // tall
      }

      function pickMainIndex() {
        if (!items.length) return -1;
        ensureOrder();
        var idx = order[edition % order.length];
        return (typeof idx === 'number') ? idx : 0;
      }

      function pickPhotoIndex(excludeIdx) {
        if (!items.length) return -1;
        ensureOrder();
        var best = -1;
        for (var i = 0; i < order.length; i++) {
          var idx = order[(edition + i) % order.length];
          if (idx === excludeIdx) continue;
          var it = items[idx];
          if (it && it.image) { best = idx; break; }
        }
        if (best !== -1) return best;
        for (var j = 0; j < order.length; j++) {
          var idx2 = order[(edition + j) % order.length];
          if (idx2 !== excludeIdx) return idx2;
        }
        return excludeIdx;
      }

      function pickBriefs(excludeA, excludeB, count) {
        var out = [];
        if (!items.length) return out;
        ensureOrder();
        for (var i = 0; i < order.length && out.length < count; i++) {
          var idx = order[(edition + 1 + i) % order.length];
          if (idx === excludeA || idx === excludeB) continue;
          out.push(items[idx]);
        }
        return out;
      }

      function swapHtml(swapEl, html) {
        if (!swapEl) return;
        var layers = swapEl.querySelectorAll('.layer');
        if (!layers || layers.length < 2) { swapEl.innerHTML = html; return; }
        var a = layers[0].classList.contains('active') ? layers[0] : layers[1];
        var b = (a === layers[0]) ? layers[1] : layers[0];
        b.innerHTML = html;
        if (prefersReduced) {
          a.classList.remove('active');
          b.classList.add('active');
          return;
        }
        requestAnimationFrame(function () {
          b.classList.add('active');
          a.classList.remove('active');
        });
      }

      function renderLeft(briefs) {
        if (!briefs || !briefs.length) {
          swapHtml(leftSwap, '<div class="briefs"><div class="brief"><div class="briefTitle">Haberler yükleniyor…</div><div class="briefMeta">—</div></div></div>');
          return;
        }
        var parts = ['<div class="briefs">'];
        for (var i = 0; i < briefs.length; i++) {
          var it = briefs[i] || {};
          parts.push(
            '<div class="brief">' +
              '<div class="briefTitle">' + escapeHtml(truncate(pickText(it.title, '-'), 92)) + '</div>' +
              '<div class="briefMeta">' + escapeHtml(truncate(formatMeta(it) || '—', 70)) + '</div>' +
            '</div>'
          );
        }
        parts.push('</div>');
        swapHtml(leftSwap, parts.join(''));
      }

      function renderMain(main, mode, storyPars) {
        if (!main) {
          swapHtml(mainSwap, '<div class="main"><h2 class="mainHeadline">Haberler yükleniyor…</h2><div class="mainMeta">—</div><p class="mainDeck">RSS haberleri bu alana yerleşir.</p><div class="story"><p class="dropcap">Birazdan güncel haberler görünecek.</p></div></div>');
          return;
        }

        var title = pickText(main.title, '-');
        var meta = formatMeta(main) || '—';
        var deck = stripHtml(main.desc || '');

        var deckMax = mode === 'wide' ? 260 : (mode === 'mid' ? 220 : 200);
        var titleMax = mode === 'wide' ? 120 : 95;
        var deckOut = truncate(deck || '—', deckMax);

        var paragraphs = [];
        var seed = [deckOut];
        for (var i = 0; i < items.length && seed.length < storyPars; i++) {
          var it = items[order[(edition + i) % Math.max(1, order.length)]];
          if (!it || it === main) continue;
          var d = stripHtml(it.desc || '');
          if (!d) continue;
          seed.push(truncate(d, mode === 'wide' ? 220 : 190));
        }

        for (var j = 0; j < seed.length && paragraphs.length < storyPars; j++) {
          var txt = pickText(seed[j], '');
          if (!txt) continue;
          if (!paragraphs.length) paragraphs.push('<p class="dropcap">' + escapeHtml(txt) + '</p>');
          else paragraphs.push('<p>' + escapeHtml(txt) + '</p>');
        }
        if (!paragraphs.length) paragraphs.push('<p class="dropcap">' + escapeHtml(deckOut) + '</p>');

        var html =
          '<div class="main">' +
            '<h2 class="mainHeadline">' + escapeHtml(truncate(title, titleMax)) + '</h2>' +
            '<div class="mainMeta">' + escapeHtml(meta) + '</div>' +
            '<p class="mainDeck">' + escapeHtml(deckOut) + '</p>' +
            '<div class="story">' + paragraphs.join('') + '</div>' +
          '</div>';

        swapHtml(mainSwap, html);
      }

      function stopPhotoMotion() {
        try { if (photoAnim) photoAnim.cancel(); } catch (e) {}
        photoAnim = null;
      }

      function startPhotoMotion(img) {
        if (prefersReduced || !img || !img.animate) return;
        stopPhotoMotion();

        var dur = randInt(6000, 12000);
        var s0 = (Math.random() * 0.10) + 1.08;
        var s1 = s0 + (Math.random() * 0.10) + 0.05;
        var x0 = (Math.random() * 4) - 2;
        var y0 = (Math.random() * 4) - 2;
        var x1 = (Math.random() * 10) - 5;
        var y1 = (Math.random() * 10) - 5;

        photoAnim = img.animate(
          [
            { transform: 'translate(' + x0 + '%, ' + y0 + '%) scale(' + s0 + ')', filter: 'contrast(1.25) saturate(.85) brightness(.95)' },
            { transform: 'translate(' + x1 + '%, ' + y1 + '%) scale(' + s1 + ')', filter: 'contrast(1.33) saturate(.80) brightness(.92)' }
          ],
          { duration: dur, easing: 'ease-in-out', fill: 'forwards' }
        );
        photoAnim.onfinish = function () {
          if (img.classList.contains('active')) startPhotoMotion(img);
        };
      }

      function setPhotoImage(url) {
        var nextImg = activePhoto === 0 ? photoB : photoA;
        var curImg = activePhoto === 0 ? photoA : photoB;
        if (!nextImg || !curImg) return;

        var nextUrl = String(url || '').trim();
        var curUrl = String(curImg.getAttribute('src') || '').trim();
        if (!nextUrl) return;
        if (nextUrl === curUrl && curImg.classList.contains('active')) return;

        var swapped = false;
        var timeout = 0;
        var cleanup = function () {
          clearTimeout(timeout);
          nextImg.removeEventListener('load', onLoad);
          nextImg.removeEventListener('error', onErr);
        };
        var doSwap = function () {
          if (swapped) return;
          swapped = true;
          cleanup();
          if (photoPlaceholder) photoPlaceholder.style.display = 'none';
          if (prefersReduced) {
            curImg.classList.remove('active');
            nextImg.classList.add('active');
          } else {
            requestAnimationFrame(function () {
              nextImg.classList.add('active');
              curImg.classList.remove('active');
            });
          }
          activePhoto = (activePhoto === 0) ? 1 : 0;
          startPhotoMotion(nextImg);
        };
        var onLoad = function () { doSwap(); };
        var onErr = function () { cleanup(); };

        nextImg.classList.remove('active');
        nextImg.alt = '';
        nextImg.addEventListener('load', onLoad);
        nextImg.addEventListener('error', onErr);
        nextImg.src = nextUrl;

        timeout = setTimeout(cleanup, 9000);
      }

      function renderPhotoCaption(it, mode) {
        if (!it) {
          swapHtml(captionSwap, '<div><div class="capTitle">—</div><div class="capDesc">Fotoğraf alanı RSS içinden bir görsel bulduğunda otomatik güncellenir.</div></div>');
          return;
        }
        var title = truncate(pickText(it.title, '-'), mode === 'wide' ? 90 : 80);
        var desc = truncate(stripHtml(it.desc || '') || '—', mode === 'wide' ? 260 : 220);
        swapHtml(captionSwap, '<div><div class="capTitle">' + escapeHtml(title) + '</div><div class="capDesc">' + escapeHtml(desc) + '</div></div>');
      }

      function renderEdition() {
        var mode = computeLayoutMode();
        var c = countsForMode(mode);

        if (!items.length) {
          renderLeft([]);
          renderMain(null, mode, c.storyPars);
          renderPhotoCaption(null, mode);
          return;
        }

        ensureOrder();
        var mainIdx = pickMainIndex();
        var photoIdx = pickPhotoIndex(mainIdx);

        var main = mainIdx >= 0 ? items[mainIdx] : null;
        var photoIt = photoIdx >= 0 ? items[photoIdx] : null;
        var briefs = pickBriefs(mainIdx, photoIdx, c.briefs);

        renderLeft(briefs);
        renderMain(main, mode, c.storyPars);
        renderPhotoCaption(photoIt, mode);

        var imgUrl = photoIt && photoIt.image ? photoIt.image : (main && main.image ? main.image : '');
        if (imgUrl) setPhotoImage(imgUrl);
      }

      async function loadItems() {
        var url = endpoint;
        try {
          var u = new URL(endpoint, location.href);
          if (!u.searchParams.has('limit')) u.searchParams.set('limit', String(limit));
          if (!u.searchParams.has('per_feed')) u.searchParams.set('per_feed', String(perFeed));
          u.searchParams.set('ts', String(Date.now()));
          url = u.toString();
        } catch (e) {}

        var controller = null;
        var timeout = 0;
        try {
          if (window.AbortController) {
            controller = new AbortController();
            timeout = setTimeout(function () { try { controller.abort(); } catch (e) {} }, 9000);
          }
          var res = await fetch(url, { cache: 'no-store', signal: controller ? controller.signal : undefined });
          if (!res.ok) throw new Error('HTTP ' + res.status);
          var json = await res.json();
          var list = (json && json.items) ? json.items : [];
          if (!Array.isArray(list)) list = [];

          items = list
            .filter(function (it) { return it && it.title; })
            .map(function (it) {
              return {
                title: pickText(it.title, ''),
                meta: pickText(it.meta, ''),
                desc: pickText(it.desc, ''),
                source: pickText(it.source, ''),
                image: pickText(it.image, ''),
                link: pickText(it.link, ''),
              };
            });

          order = [];
          for (var i = 0; i < items.length; i++) order.push(i);
          shuffle(order);
          edition = 0;
          renderEdition();
        } catch (e) {
          items = [];
          order = [];
          renderEdition();
        } finally {
          clearTimeout(timeout);
        }
      }

      function initClock() {
        var timeFormatter = null;
        var dateFormatter = null;
        try {
          timeFormatter = new Intl.DateTimeFormat('tr-TR', { timeZone: 'Europe/Istanbul', hour: '2-digit', minute: '2-digit' });
          dateFormatter = new Intl.DateTimeFormat('tr-TR', { timeZone: 'Europe/Istanbul', weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        } catch (e) {}

        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function formatHM(d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()); }
        function formatDate(d) {
          if (dateFormatter) return dateFormatter.format(d);
          return pad(d.getDate()) + '.' + pad(d.getMonth() + 1) + '.' + d.getFullYear();
        }

        function tick() {
          var d = new Date();
          if (liveClock) liveClock.textContent = timeFormatter ? timeFormatter.format(d) : formatHM(d);
          if (liveDate) liveDate.textContent = formatDate(d);
        }
        tick();
        setInterval(tick, 1000);
      }

      function scheduleNextEdition() {
        clearTimeout(rotateTimer);
        var delay = randInt(minRotateMs, maxRotateMs);
        rotateTimer = setTimeout(function () {
          edition++;
          renderEdition();
          scheduleNextEdition();
        }, delay);
      }

      function start() {
        computeLayoutMode();
        initClock();
        renderEdition();
        loadItems();
        scheduleNextEdition();

        clearInterval(refreshTimer);
        refreshTimer = setInterval(loadItems, refreshMs);
      }

      var resizeTimer = 0;
      window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
          computeLayoutMode();
          renderEdition();
        }, 160);
      });

      start();
    })();
  </script>
</body>
</html>
