<?php
require_once __DIR__ . '/auth.php';
kc2_auth_require_html();

header('Content-Type: text/html; charset=utf-8');
@ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Europe/Istanbul');
$title = isset($_GET['title']) ? trim($_GET['title']) : '';
$meta  = isset($_GET['meta']) ? trim($_GET['meta']) : '';
$desc  = isset($_GET['desc']) ? trim($_GET['desc']) : '';
$link  = isset($_GET['link']) ? trim($_GET['link']) : '';
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
  <title>Haber Detayı</title>
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
    .frame { width: 100%; margin: 0; min-height: 100dvh; padding: 14px 10px; text-align: center; }
    .headline { font-size: 26px; font-weight: 900; margin: 0 0 10px 0; }
    .meta { font-size: 16px; font-weight: 800; margin: 0 0 10px 0; color: #e6e6e6; opacity: 0.8; }
    .desc { font-size: 18px; font-weight: 600; margin: 0 0 14px 0; line-height: 1.4; text-align: left; }
    .back { margin-top: 10px; }
    .btn {
      display: inline-block;
      padding: 8px 12px;
      border: 2px solid #bdbdbd;
      background: #000;
      color: #e6e6e6;
      font-weight: 900;
      text-decoration: none;
      border-radius: 6px;
      margin: 0 4px;
    }
  </style>
</head>
<body>
  <div class="frame">
    <p class="headline"><?= htmlspecialchars($title ?: 'Haber başlığı yok', ENT_QUOTES, 'UTF-8') ?></p>
    <?php if ($meta): ?><p class="meta"><?= htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($desc): ?><p class="desc"><?= nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
    <div class="back">
      <a class="btn" href="javascript:history.back()">Geri</a>
      <?php if ($link): ?>
        <a class="btn" href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Kaynağı aç</a>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
