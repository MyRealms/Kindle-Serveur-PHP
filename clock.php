<?php
header('Content-Type: text/html; charset=utf-8');
@ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Europe/Istanbul');
require_once __DIR__ . '/auth.php';
kc2_auth_require_html();
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
  <title>Kindle Saat</title>
  <style>
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
      margin: 0;
      padding: 0;
      background: #000;
      color: #e6e6e6;
      font-family: Helvetica, Arial, sans-serif;
      text-align: center;
    }
    .frame {
      width: 100%;
      margin: 0;
      text-align: center;
      min-height: 100dvh;
      padding: 24px 12px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .digital {
      font-family: "Courier New", monospace;
      font-size: clamp(110px, 26vw, 220px);
      letter-spacing: clamp(2px, 1vw, 8px);
      font-weight: 900;
      margin: 0;
      line-height: 1;
      display: block;
      width: 100%;
    }
    .day-name {
      font-size: clamp(28px, 5.2vw, 42px);
      font-weight: 800;
      margin: 14px 0 6px 0;
      text-transform: capitalize;
    }
    .day-date {
      font-size: clamp(22px, 4.2vw, 34px);
      font-weight: 700;
      margin: 0;
    }
    .fs-hint {
      position: fixed;
      left: 50%;
      bottom: 10px;
      transform: translateX(-50%);
      background: #000;
      color: #e6e6e6;
      border: 2px solid #bdbdbd;
      padding: 8px 12px;
      font-size: 14px;
      font-weight: 800;
      border-radius: 8px;
    }
    .hud {
      position: fixed;
      top: 10px;
      right: 10px;
      font-size: 14px;
      font-weight: 800;
      padding: 6px 10px;
      border: 2px solid #bdbdbd;
      background: #000;
      color: #e6e6e6;
      border-radius: 8px;
      opacity: 0.9;
    }
  </style>
</head>
<body>
  <div class="frame">
    <div id="digitalClock" class="digital">00:00</div>
    <p id="dayName" class="day-name">Pazartesi</p>
    <p id="dayDate" class="day-date">1 Ocak 2024</p>
  </div>
  <div id="fsHint" class="fs-hint">Tam ekran için dokun</div>

  <div id="hudBattery" class="hud" style="display:none">Pil: --</div>
  <script>
    const timeEl = document.getElementById('digitalClock');
    const dayNameEl = document.getElementById('dayName');
    const dayDateEl = document.getElementById('dayDate');
    const fsHintEl = document.getElementById('fsHint');
    const hudBatteryEl = document.getElementById('hudBattery');
    const days = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];
    const months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    function pad2(n){ return n < 10 ? '0' + n : '' + n; }
    function formatTime(d){ return pad2(d.getHours()) + ':' + pad2(d.getMinutes()); }
    async function tryFullscreen(){
      if (document.fullscreenElement) return;
      const el = document.documentElement;
      if (!el.requestFullscreen) return;
      try { await el.requestFullscreen({ navigationUI: 'hide' }); } catch (e) {}
    }
    function hideHint(){ if (fsHintEl) fsHintEl.style.display = 'none'; }
    function tick(){
      const now = new Date();
      timeEl.textContent = formatTime(now);
      const wd = days[now.getDay() === 0 ? 6 : now.getDay()-1];
      dayNameEl.textContent = wd;
      dayDateEl.textContent = now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
    }
    tick();
    setInterval(tick, 1000);

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

    document.addEventListener('fullscreenchange', () => {
      if (document.fullscreenElement) hideHint();
    });
    document.addEventListener('click', tryFullscreen, { once: true });
    document.addEventListener('touchstart', tryFullscreen, { once: true, passive: true });
  </script>
</body>
</html>
