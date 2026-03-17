<?php
require_once __DIR__ . '/auth.php';
kc2_auth_require_fragment();

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
@ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Europe/Istanbul');

require_once __DIR__ . '/news_lib.php';

$rssUrl = 'https://www.aa.com.tr/tr/rss/default?cat=guncel';
$news = fetch_news($rssUrl, 8);
?>
<?php if (empty($news)): ?>
  <div class="news-item"><p class="news-headline faded">Haber alınamadı.</p></div>
<?php else: ?>
  <?php foreach ($news as $item): ?>
    <?php
      $title = (string)($item['title'] ?? '');
      $meta  = (string)($item['meta'] ?? '');
      $desc  = (string)($item['desc'] ?? '');
      $link  = (string)($item['link'] ?? '');
    ?>
    <div class="news-item">
      <button
        type="button"
        class="news-headline"
        data-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
        data-meta="<?= htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') ?>"
        data-desc="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>"
        data-link="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>"
      ><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></button>
      <p class="news-meta"><span><?= htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') ?></span></p>
      <div class="news-detail" hidden></div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
