<?php
header('Content-Type: text/html; charset=utf-8');
@ini_set('default_charset', 'UTF-8');
require_once __DIR__ . '/auth.php';

if (kc2_auth_enabled() && isset($_GET['logout'])) {
  kc2_auth_logout();
  header('Location: index.php');
  exit;
}

$error = '';
if (kc2_auth_enabled() && !kc2_auth_logged_in() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
  if (!kc2_auth_login($password)) {
    $error = 'Parola hatalı.';
  } else {
    $next = isset($_POST['next']) ? (string)$_POST['next'] : '';
    $next = $next !== '' ? $next : 'index.php';
    header('Location: ' . $next);
    exit;
  }
}

if (kc2_auth_enabled() && !kc2_auth_logged_in()) {
  $next = isset($_GET['next']) ? (string)$_GET['next'] : 'index.php';
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  ?>
  <!doctype html>
  <html lang="tr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex,nofollow,noarchive" />
    <meta name="description" content="Bu site özel kullanıma kapalıdır; yalnızca yetkili giriş ile erişilir." />
    <meta name="theme-color" content="#000000" />
    <meta name="color-scheme" content="dark" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="manifest" href="manifest.json" />
    <link rel="icon" href="icon.svg" type="image/svg+xml" />
    <script defer src="app.js"></script>
    <title>Giriş</title>
    <style>
      * { box-sizing: border-box; }
      html, body { height: 100%; }
      body { margin:0; background:#000; color:#e6e6e6; font-family:system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; }
      .wrap { min-height:100dvh; display:flex; align-items:center; justify-content:center; padding:20px; }
      .card { width:min(560px, 92vw); border:2px solid #2e2e2e; border-radius:16px; padding:18px; background:rgba(0,0,0,.72); }
      h1 { margin:0 0 10px; font-size:26px; font-weight:900; letter-spacing:.02em; }
      p { margin:0 0 14px; opacity:.86; font-weight:700; }
      label { display:block; font-size:14px; font-weight:900; margin-bottom:6px; }
      input { width:100%; padding:12px; border:2px solid #2e2e2e; border-radius:12px; background:#000; color:#e6e6e6; font-size:18px; }
      .row { display:flex; gap:10px; margin-top:12px; align-items:center; }
      button { flex:1; padding:12px; border:2px solid #e6e6e6; border-radius:12px; background:#000; color:#e6e6e6; font-size:18px; font-weight:900; cursor:pointer; }
      .err { margin-top:10px; color:#fff; border:2px solid #5a1a1a; background:rgba(120,0,0,.25); padding:10px 12px; border-radius:12px; font-weight:900; }
      code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    </style>
  </head>
  <body>
    <div class="wrap">
      <form class="card" method="post" action="index.php">
        <h1>Giriş</h1>
        <p>Bu sistem özel kullanıma kapalıdır. Sadece yetkili kullanıcı girebilir.</p>
        <label for="password">Parola</label>
        <input id="password" name="password" type="password" inputmode="text" autocomplete="current-password" autofocus />
        <input type="hidden" name="next" value="<?php echo htmlspecialchars($next, ENT_QUOTES, 'UTF-8'); ?>" />
        <div class="row">
          <button type="submit">Giriş yap</button>
        </div>
        <?php if ($error !== ''): ?>
          <div class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
      </form>
    </div>
  </body>
  </html>
  <?php
  exit;
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex,nofollow,noarchive" />
  <meta name="description" content="Özel kullanım ekranı. Giriş gereklidir." />
  <meta name="theme-color" content="#000000" />
  <meta name="color-scheme" content="dark" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <link rel="manifest" href="manifest.json" />
  <link rel="icon" href="icon.svg" type="image/svg+xml" />
  <script defer src="app.js"></script>
  <title>Kindle Serveur</title>
  <style>
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
      margin: 0;
      padding: 0;
      background: #000;
      color: #e6e6e6;
      font-family: Helvetica, Arial, sans-serif;
    }
    .container { width: 100%; margin: 0; padding: 14px 10px; }

    .chrome { border: 2px solid #bdbdbd; background: #000; }
    .addr-top {
      padding: 8px 10px;
      border-bottom: 2px solid #bdbdbd;
      text-align: center;
      font-size: 18px;
      font-weight: 800;
    }
    .topbar { padding: 10px; }
    .bar { width: 100%; border-collapse: collapse; }
    .brand-cell {
      width: 185px;
      font-size: 22px;
      font-weight: 900;
      text-align: left;
      white-space: nowrap;
      padding-right: 10px;
    }
    .input-cell { width: auto; }
    .btn-cell { width: 90px; padding-left: 8px; }
    .search-input {
      width: 100%;
      padding: 10px;
      font-size: 17px;
      border: 2px solid #bdbdbd;
      background: #000;
      color: #e6e6e6;
    }
    .search-btn {
      width: 100%;
      padding: 10px 0;
      font-size: 17px;
      font-weight: 800;
      border: 2px solid #bdbdbd;
      background: #000;
      color: #e6e6e6;
      cursor: pointer;
    }

    .nav { padding-top: 16px; text-align: center; }
    .nav a {
      display: block;
      width: 100%;
      margin: 0 0 12px;
      padding: 16px;
      border: 2px solid #bdbdbd;
      background: #000;
      color: #e6e6e6;
      font-size: 22px;
      font-weight: 900;
      text-decoration: none;
      text-align: center;
      box-shadow: 0 3px 0 #bdbdbd;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="chrome">
      <div class="addr-top">adres kutusu</div>
      <div class="topbar">
        <form action="go.php" method="get">
          <table class="bar">
            <tr>
              <td class="brand-cell">Kindle Serveur</td>
              <td class="input-cell"><input class="search-input" type="text" name="q" placeholder="Adres veya arama..." aria-label="Adres" /></td>
              <td class="btn-cell"><button class="search-btn" type="submit">Ara</button></td>
            </tr>
          </table>
        </form>
      </div>
    </div>

    <div class="nav">
      <a href="display.php">Ekran (1080p)</a>
      <a href="harry.php">Harry Potter Gazetesi</a>
      <a href="experimental.php">Deneysel</a>
      <a href="news.php">Saat + Haberler</a>
      <a href="clock.php">Sadece Saat</a>
      <?php if (kc2_auth_enabled()): ?>
        <a href="index.php?logout=1">Çıkış</a>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
