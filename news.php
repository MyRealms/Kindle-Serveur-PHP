<?php
header('Content-Type: text/html; charset=utf-8');
@ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Europe/Istanbul');
require_once __DIR__ . '/auth.php';
kc2_auth_require_html();

$rssUrl = 'https://www.aa.com.tr/tr/rss/default?cat=guncel';
$news = [];

function fetch_news_legacy_disabled(string $url, int $limit = 5): array {
    $xml = @file_get_contents($url);
    if ($xml === false) return [];
    $feed = @simplexml_load_string($xml);
    if ($feed === false || !isset($feed->channel->item)) return [];

    $months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    $items = [];
    foreach ($feed->channel->item as $item) {
        $title = trim((string)$item->title);
        $desc  = trim(strip_tags((string)$item->description));
        $link  = trim((string)$item->link);
        $date  = trim((string)$item->pubDate);
        $meta  = '';
        if ($date) {
            $ts = strtotime($date);
            if ($ts) {
                $meta = sprintf('%02d %s %04d · %02d:%02d',
                    (int)date('d', $ts),
                    $months[(int)date('n', $ts) - 1] ?? date('M', $ts),
                    (int)date('Y', $ts),
                    (int)date('H', $ts),
                    (int)date('i', $ts)
                );
            } else {
                $meta = $date;
            }
        }
        if ($title !== '') {
            $items[] = ['title' => $title, 'desc' => $desc, 'meta' => $meta, 'link' => $link];
        }
        if (count($items) >= $limit) break;
    }
    return $items;
}
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
  <title>Kindle Saat + Haber</title>
  <style>
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body { margin:0; padding:0; background:#000; color:#e6e6e6; font-family:Helvetica, Arial, sans-serif; text-align:center; }
    .frame { width:100%; margin:0; text-align:center; min-height:100dvh; padding:12px 10px 10px; display:flex; flex-direction:column; gap:10px; }
    .clock-panel { text-align:center; }
    .digital { font-family:"Courier New", monospace; font-size:clamp(110px, 26vw, 220px); letter-spacing:clamp(2px, 1vw, 8px); font-weight:900; margin:0; line-height:1; display:block; width:100%; }
    .day-name { font-size:clamp(28px, 5.2vw, 40px); font-weight:800; margin:12px 0 6px 0; text-transform:capitalize; }
    .day-date { font-size:clamp(22px, 4.2vw, 32px); font-weight:700; margin:0 0 10px 0; }
    .news { flex:1; overflow:auto; -webkit-overflow-scrolling: touch; margin-top:0; padding-top:10px; border-top:2px solid #bdbdbd; text-align:left; }
    .news-item { margin-bottom:14px; }
    .news-headline { width:100%; padding:0; text-align:left; border:0; background:transparent; color:inherit; cursor:pointer; font-size:26px; font-weight:900; margin:0 0 4px 0; }
    .news-meta { font-size:18px; font-weight:800; margin:0 0 6px 0; color:#e6e6e6; display:flex; justify-content:space-between; gap:8px; align-items:center; }
    .news-detail { border-left: 4px solid #bdbdbd; padding-left: 10px; margin: 2px 0 10px 0; }
    .news-desc { font-size: 18px; font-weight: 600; line-height: 1.4; margin: 0 0 8px 0; }
    .news-actions { display:flex; gap:10px; align-items:center; }
    .news-action { color:#e6e6e6; text-decoration:none; font-weight:900; border:2px solid #bdbdbd; padding:6px 10px; border-radius:8px; background:#000; }
    .faded { color:#e6e6e6; opacity: 0.75; }
    .fs-hint { position:fixed; left:50%; bottom:10px; transform:translateX(-50%); background:#000; color:#e6e6e6; border:2px solid #bdbdbd; padding:8px 12px; font-size:14px; font-weight:800; border-radius:8px; }
    .hud { position:fixed; top:10px; right:10px; font-size:14px; font-weight:800; padding:6px 10px; border:2px solid #bdbdbd; background:#000; color:#e6e6e6; border-radius:8px; opacity:0.9; }

    /* Layout: adapt by aspect ratio (set via JS: body[data-layout]). */
    body[data-layout="wide"] .frame {
      padding: 22px 24px;
      display: grid;
      grid-template-columns: minmax(320px, 0.85fr) 1.15fr;
      grid-template-rows: 1fr;
      gap: 22px;
      align-items: center;
      text-align: left;
    }
    body[data-layout="wide"] .clock-panel { text-align: left; }
    body[data-layout="wide"] .digital { font-size: clamp(110px, 12vmin, 190px); width: auto; }
    body[data-layout="wide"] .day-name { font-size: clamp(26px, 3.6vmin, 40px); margin: 14px 0 6px; }
    body[data-layout="wide"] .day-date { font-size: clamp(18px, 2.8vmin, 30px); margin: 0; }
    body[data-layout="wide"] .news {
      border-top: none;
      margin-top: 0;
      padding-top: 0;
      padding-left: 18px;
      border-left: 2px solid #bdbdbd;
      max-height: calc(100dvh - 44px);
    }

    body[data-layout="mid"] .frame { padding: 14px 12px; }

    body[data-layout="square"] .frame { padding: 10px 10px; }
    body[data-layout="square"] .digital { font-size: clamp(100px, 28vw, 210px); }

    body[data-layout="tall"] .frame { padding: 12px 10px; }
    body[data-layout="tall"] .digital { font-size: clamp(92px, 22vh, 170px); }
    body[data-layout="tall"] .day-name { font-size: clamp(22px, 3.6vh, 34px); }
    body[data-layout="tall"] .day-date { font-size: clamp(16px, 2.6vh, 26px); }
    body[data-layout="tall"] .news { padding-top: 10px; border-top: 2px solid #bdbdbd; }
  </style>
</head>
<body>
  <div class="frame">
    <div class="clock-panel">
      <div id="digitalClock" class="digital">00:00</div>
      <p id="dayName" class="day-name">Pazartesi</p>
      <p id="dayDate" class="day-date">1 Ocak 2024</p>
    </div>

    <div class="news" id="news">
      <?php include __DIR__ . '/news_list.php'; ?>
      <?php if (false): ?>
        <div class="news-item"><p class="news-headline faded">Haber alınamadı.</p></div>
      <?php elseif (false): ?>
        <?php foreach ($news as $item): ?>
          <?php
            $titleEnc = urlencode($item['title'] ?? '');
            $metaEnc  = urlencode($item['meta'] ?? '');
            $descEnc  = urlencode($item['desc'] ?? '');
            $linkEnc  = urlencode($item['link'] ?? '');
            $detailUrl = "detail.php?title={$titleEnc}&meta={$metaEnc}&desc={$descEnc}&link={$linkEnc}";
          ?>
          <div class="news-item">
            <a class="news-headline" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></a>
            <p class="news-meta">
              <span><?= htmlspecialchars($item['meta'], ENT_QUOTES, 'UTF-8') ?></span>
            </p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <div id="fsHint" class="fs-hint">Tam ekran için dokun</div>

  <div id="hudBattery" class="hud" style="display:none">Pil: --</div>
  <script>
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

    const timeEl = document.getElementById('digitalClock');
    const dayNameEl = document.getElementById('dayName');
    const dayDateEl = document.getElementById('dayDate');
    const fsHintEl = document.getElementById('fsHint');
    const hudBatteryEl = document.getElementById('hudBattery');
    const days = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];
    const months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    function pad2(n){return n<10?'0'+n:''+n;}
    function formatTime(d){return pad2(d.getHours())+':'+pad2(d.getMinutes());}
    async function tryFullscreen(){
      if (document.fullscreenElement) return;
      const el = document.documentElement;
      if (!el.requestFullscreen) return;
      try { await el.requestFullscreen({ navigationUI: 'hide' }); } catch (e) {}
    }
    function hideHint(){ if (fsHintEl) fsHintEl.style.display = 'none'; }
    function tick(){
      const now=new Date();
      timeEl.textContent=formatTime(now);
      const wd=days[now.getDay()===0?6:now.getDay()-1];
      dayNameEl.textContent=wd;
      dayDateEl.textContent=now.getDate()+' '+months[now.getMonth()]+' '+now.getFullYear();
    }
    tick();
    setInterval(tick,1000);

    async function initBattery(){
      if (!hudBatteryEl) return;
      if (!('getBattery' in navigator)) return;
      try {
        const b = await navigator.getBattery();
        function render(){
          const pct = Math.round((b.level || 0) * 100);
          const label = b.charging ? 'Sarj' : 'Pil';
          hudBatteryEl.textContent = label + ': ' + pct + '%';
          hudBatteryEl.style.display = 'inline-block';
        }
        render();
        b.addEventListener('levelchange', render);
        b.addEventListener('chargingchange', render);
      } catch (e) {}
    }
    initBattery();

    function escapeHtml(s){
      return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function bindNewsInteractions(){
      const root = document.getElementById('news');
      if (!root) return;
      root.addEventListener('click', (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('.news-headline') : null;
        if (!(btn instanceof HTMLElement)) return;
        if (btn.tagName !== 'BUTTON') return;

        const item = btn.closest('.news-item');
        if (!item) return;
        const detail = item.querySelector('.news-detail');
        if (!(detail instanceof HTMLElement)) return;

        const desc = btn.getAttribute('data-desc') || '';
        const link = btn.getAttribute('data-link') || '';

        const shouldShow = detail.hasAttribute('hidden');
        if (!shouldShow) {
          detail.setAttribute('hidden', '');
          detail.innerHTML = '';
          return;
        }

        const parts = [];
        if (desc) parts.push('<p class="news-desc">' + escapeHtml(desc) + '</p>');
        if (link) {
          parts.push('<div class="news-actions"><a class="news-action" target="_blank" rel="noopener" href="' + escapeHtml(link) + '">Kaynagi ac</a></div>');
        }
        if (parts.length === 0) parts.push('<p class="news-desc faded">Detay yok.</p>');
        detail.innerHTML = parts.join('');
        detail.removeAttribute('hidden');
      });
    }
    bindNewsInteractions();

    async function refreshNews(){
      const root = document.getElementById('news');
      if (!root) return;
      const scrollTop = root.scrollTop;
      try {
        const res = await fetch('news_list.php?ts=' + Date.now(), { cache: 'no-store' });
        if (!res.ok) return;
        const html = await res.text();
        root.innerHTML = html;
        root.scrollTop = scrollTop;
      } catch (e) {}
    }
    setInterval(refreshNews, 300000);
    document.addEventListener('fullscreenchange', () => { if (document.fullscreenElement) hideHint(); });
    document.addEventListener('click', tryFullscreen, { once: true });
    document.addEventListener('touchstart', tryFullscreen, { once: true, passive: true });
  </script>
</body>
</html>
