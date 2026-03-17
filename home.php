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
  <title>Başlangıç</title>
  <style>
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body { margin:0; padding:0; background:#000; color:#e6e6e6; font-family:Helvetica, Arial, sans-serif; text-align:center; }
    .frame { width:100%; margin:0; min-height:100dvh; padding:14px 10px; display:flex; flex-direction:column; gap:14px; }
    h1 { margin:0; font-size:28px; font-weight:900; }
    .search-box { display:flex; gap:8px; justify-content:center; }
    .search-box input { flex:1; max-width:420px; padding:10px; font-size:18px; border:2px solid #bdbdbd; background:#000; color:#e6e6e6; }
    .search-box button { padding:10px 16px; font-size:18px; font-weight:900; border:2px solid #bdbdbd; background:#000; cursor:pointer; color:#e6e6e6; }
    .nav { display:flex; flex-direction:column; gap:10px; }
    .nav a { display:block; padding:14px; border:2px solid #bdbdbd; background:#000; color:#e6e6e6; font-size:20px; font-weight:900; text-decoration:none; }
  </style>
</head>
<body>
  <div class="frame">
    <h1>Başlangıç</h1>
    <form class="search-box" action="https://duckduckgo.com/" method="get">
      <input type="text" name="q" placeholder="Ara..." aria-label="Ara" />
      <button type="submit">Ara</button>
    </form>
    <div class="nav">
      <a href="display.php">Ekran (1080p)</a>
      <a href="harry.php">Harry Potter Gazetesi</a>
      <a href="experimental.php">Deneysel</a>
      <a href="index.php">Saat + Haberler</a>
      <a href="clock.php">Saat</a>
    </div>
  </div>
</body>
</html>
