<?php
require_once __DIR__ . '/auth.php';
kc2_auth_require_json();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
@ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Europe/Istanbul');

require_once __DIR__ . '/news_lib.php';
require_once __DIR__ . '/feeds.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 40;
if ($limit < 1) $limit = 1;
if ($limit > 150) $limit = 150;

$perFeed = isset($_GET['per_feed']) ? (int)$_GET['per_feed'] : 20;
if ($perFeed < 1) $perFeed = 1;
if ($perFeed > 60) $perFeed = 60;

$urls = [];
if (isset($RSS_URLS) && is_array($RSS_URLS)) {
    foreach ($RSS_URLS as $u) {
        if (!is_string($u)) continue;
        $u = trim($u);
        if ($u === '') continue;
        $urls[] = $u;
    }
}

// Grupları çek (her feed için ayrı liste) -> round-robin birleştir.
$groups = [];
foreach ($urls as $u) {
    $items = fetch_news($u, $perFeed);
    $host = (string) (parse_url($u, PHP_URL_HOST) ?: '');
    if ($host !== '') {
        foreach ($items as &$it) {
            if (is_array($it)) $it['source'] = $host;
        }
        unset($it);
    }
    $groups[] = $items;
}

$out = [];
$seen = [];
while (count($out) < $limit) {
    $progress = false;
    foreach ($groups as $gi => &$list) {
        if (empty($list)) continue;
        $raw = array_shift($list);
        $progress = true;
        if (!is_array($raw)) continue;

        $title = (string)($raw['title'] ?? '');
        $link  = (string)($raw['link'] ?? '');
        $key = strtolower(trim($link !== '' ? $link : $title));
        if ($key === '' || isset($seen[$key])) continue;
        $seen[$key] = true;

        $out[] = [
            'title' => $title,
            'meta' => (string)($raw['meta'] ?? ''),
            'desc' => (string)($raw['desc'] ?? ''),
            'link' => $link,
            'source' => (string)($raw['source'] ?? ''),
            'image' => (string)($raw['image'] ?? ''),
        ];
        if (count($out) >= $limit) break 2;
    }
    unset($list);
    if (!$progress) break;
}

echo json_encode(
    ['items' => $out, 'ts' => time()],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
