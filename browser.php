<?php
header('Content-Type: text/html; charset=utf-8');
@ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Europe/Istanbul');
require_once __DIR__ . '/auth.php';
kc2_auth_require_html();

$query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$urlParam = isset($_GET['u']) ? trim((string)$_GET['u']) : '';

if ($query === '' && $urlParam === '') {
    header('Location: index.php');
    exit;
}

function is_private_ip(string $ip): bool {
    $long = ip2long($ip);
    if ($long === false) return false;
    $ranges = [
        ['0.0.0.0', '0.255.255.255'],
        ['10.0.0.0', '10.255.255.255'],
        ['127.0.0.0', '127.255.255.255'],
        ['169.254.0.0', '169.254.255.255'],
        ['172.16.0.0', '172.31.255.255'],
        ['192.168.0.0', '192.168.255.255'],
    ];
    foreach ($ranges as $r) {
        $min = ip2long($r[0]);
        $max = ip2long($r[1]);
        if ($min !== false && $max !== false && $long >= $min && $long <= $max) return true;
    }
    return false;
}

function validate_target_url(string $u): ?string {
    $parts = @parse_url($u);
    if (!$parts) return null;
    $scheme = strtolower($parts['scheme'] ?? '');
    if ($scheme !== 'http' && $scheme !== 'https') return null;
    $host = $parts['host'] ?? '';
    if ($host === '') return null;
    if (strcasecmp($host, 'localhost') === 0) return null;

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        if (is_private_ip($host)) return null;
    } else {
        $ip = @gethostbyname($host);
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP) && is_private_ip($ip)) return null;
    }

    return $u;
}

function http_get(string $u, int $timeoutSec = 10): array {
    $body = '';
    $contentType = '';
    $status = 0;
    $effectiveUrl = $u;
    $err = '';

    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: tr-TR,tr;q=0.9,en;q=0.5',
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $u);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSec);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KindleServeur/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $resp = curl_exec($ch);
        if ($resp !== false) {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE) ?: 0;
            $rawHeaders = substr($resp, 0, $headerSize);
            $body = substr($resp, $headerSize);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            if ($contentType === '' && preg_match('/^Content-Type:\s*([^\r\n]+)/mi', $rawHeaders, $m)) {
                $contentType = trim($m[1]);
            }
        } else {
            $err = curl_error($ch) ?: 'curl error';
        }
        curl_close($ch);
    } else {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeoutSec,
                'header' => "User-Agent: KindleServeur/1.0\r\n" . implode("\r\n", $headers) . "\r\n",
            ],
            'https' => [
                'timeout' => $timeoutSec,
            ]
        ]);
        $body = @file_get_contents($u, false, $ctx);
        if ($body === false) $body = '';
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) {
                if ($status === 0 && preg_match('#^HTTP/\S+\s+(\d{3})#i', $h, $m)) {
                    $status = (int) $m[1];
                }
                if (stripos($h, 'Content-Type:') === 0) {
                    $contentType = trim(substr($h, 13));
                }
            }
        }
    }

    if (strlen($body) > 1024 * 1024) {
        $body = substr($body, 0, 1024 * 1024);
    }

    return [
        'body' => $body,
        'contentType' => $contentType,
        'status' => $status,
        'effectiveUrl' => $effectiveUrl,
        'error' => $err,
    ];
}

function detect_charset(string $html, string $contentType): string {
    if (preg_match('/charset=([a-zA-Z0-9\-]+)/i', $contentType, $m)) return strtoupper($m[1]);
    if (preg_match('/<meta[^>]+charset\s*=\s*["\']?\s*([a-zA-Z0-9\-]+)/i', $html, $m)) return strtoupper($m[1]);
    return 'UTF-8';
}

function to_utf8(string $html, string $charset): string {
    if ($charset === '' || strtoupper($charset) === 'UTF-8') return $html;
    if (function_exists('mb_convert_encoding')) {
        $converted = @mb_convert_encoding($html, 'UTF-8', $charset);
        if (is_string($converted) && $converted !== '') return $converted;
    }
    return $html;
}

function resolve_url(string $base, string $rel): string {
    if (preg_match('#^https?://#i', $rel)) return $rel;
    if (strpos($rel, '//') === 0) {
        $p = parse_url($base);
        $scheme = $p['scheme'] ?? 'https';
        return $scheme . ':' . $rel;
    }
    $p = parse_url($base);
    $scheme = $p['scheme'] ?? 'https';
    $host = $p['host'] ?? '';
    $port = isset($p['port']) ? ':' . $p['port'] : '';
    $path = $p['path'] ?? '/';
    $dir = preg_replace('#/[^/]*$#', '/', $path);
    if (strpos($rel, '/') === 0) $dir = '/';
    $abs = $scheme . '://' . $host . $port . $dir . $rel;

    $abs = preg_replace('#/\./#', '/', $abs);
    while (preg_match('#/[^/]+/\.\./#', $abs)) {
        $abs = preg_replace('#/[^/]+/\.\./#', '/', $abs);
    }

    return $abs;
}

function unwrap_ddg_redirect(string $url): string {
    $p = @parse_url($url);
    if (!$p) return $url;
    $host = strtolower($p['host'] ?? '');
    $path = $p['path'] ?? '';
    if (strpos($host, 'duckduckgo.com') === false) return $url;
    if (strpos($path, '/l/') !== 0) return $url;
    $q = [];
    parse_str($p['query'] ?? '', $q);
    if (!empty($q['uddg'])) {
        $out = (string) $q['uddg'];
        if (stripos($out, 'http://') === 0) {
            $out = 'https://' . substr($out, 7);
        }
        return $out;
    }
    return $url;
}

function sanitize_and_rewrite(string $html, string $baseUrl): string {
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $loaded = @$doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    if (!$loaded) {
        return '<pre>' . htmlspecialchars($html, ENT_QUOTES, 'UTF-8') . '</pre>';
    }

    $removeTags = ['script', 'style', 'noscript', 'iframe', 'img', 'svg', 'video', 'source', 'form'];
    foreach ($removeTags as $tag) {
        $nodes = $doc->getElementsByTagName($tag);
        for ($i = $nodes->length - 1; $i >= 0; $i--) {
            $n = $nodes->item($i);
            if ($n && $n->parentNode) $n->parentNode->removeChild($n);
        }
    }

    $all = $doc->getElementsByTagName('*');
    for ($i = 0; $i < $all->length; $i++) {
        $el = $all->item($i);
        if ($el instanceof DOMElement) {
            $el->removeAttribute('style');
            $el->removeAttribute('onclick');
            $el->removeAttribute('onload');
        }
    }

    $links = $doc->getElementsByTagName('a');
    for ($i = 0; $i < $links->length; $i++) {
        $a = $links->item($i);
        if (!($a instanceof DOMElement)) continue;
        $href = trim($a->getAttribute('href'));
        if ($href === '' || strpos($href, '#') === 0) continue;

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $href) && !preg_match('#^https?://#i', $href)) {
            $a->removeAttribute('href');
            continue;
        }
        if (stripos($href, 'javascript:') === 0) {
            $a->removeAttribute('href');
            continue;
        }

        $abs = resolve_url($baseUrl, $href);
        $abs = unwrap_ddg_redirect($abs);
        $a->setAttribute('href', 'browser.php?u=' . rawurlencode($abs));
    }

    $body = $doc->getElementsByTagName('body')->item(0);
    if ($body) {
        $inner = '';
        foreach ($body->childNodes as $child) {
            $inner .= $doc->saveHTML($child);
        }
        return $inner;
    }

    return $doc->saveHTML();
}

$displayValue = $query !== '' ? $query : $urlParam;
$mode = $query !== '' ? 'search' : 'browse';

$requestedUrl = '';
if ($mode === 'search') {
    $requestedUrl = 'https://lite.duckduckgo.com/lite/?q=' . rawurlencode($query) . '&kl=tr-tr';
} else {
    $requestedUrl = $urlParam;
}

$targetUrl = validate_target_url($requestedUrl);
$error = '';
$contentHtml = '';
$pageTitle = '';
$addrText = $mode === 'search' ? ('arama: ' . $query) : $requestedUrl;

if ($targetUrl === null) {
    $error = 'Adres engellendi veya geçersiz.';
} else {
    $tryUrls = [$targetUrl];
    $parts = @parse_url($targetUrl);
    if ($parts) {
        $scheme = strtolower($parts['scheme'] ?? '');
        $altScheme = $scheme === 'http' ? 'https' : ($scheme === 'https' ? 'http' : '');
        if ($altScheme !== '') {
            $alt = $altScheme . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '') . ($parts['path'] ?? '/');
            if (!empty($parts['query'])) $alt .= '?' . $parts['query'];
            if (!empty($parts['fragment'])) $alt .= '#' . $parts['fragment'];
            if ($scheme === 'http') {
                array_unshift($tryUrls, $alt); // prefer https
            } else {
                $tryUrls[] = $alt; // fallback to http
            }
        }
    }

    $lastStatus = 0;
    $lastErr = '';
    foreach ($tryUrls as $u) {
        $resp = http_get($u);
        $lastStatus = (int) ($resp['status'] ?? 0);
        $lastErr = (string) ($resp['error'] ?? '');
        $body = (string) ($resp['body'] ?? '');
        $ct = (string) ($resp['contentType'] ?? '');
        $eff = (string) ($resp['effectiveUrl'] ?? $u);
        if ($body === '') {
            continue;
        }

        $charset = detect_charset($body, $ct);
        $body = to_utf8($body, $charset);
        if (preg_match('/<title>(.*?)<\\/title>/si', $body, $m)) {
            $pageTitle = trim(strip_tags($m[1]));
        }
        $addrText = $mode === 'search' ? ('arama: ' . $query) : $eff;
        $contentHtml = sanitize_and_rewrite($body, $eff);
        break;
    }

    if ($contentHtml === '') {
        $error = 'Sayfa alınamadı.';
        if ($lastStatus) $error .= ' (HTTP ' . $lastStatus . ')';
        if ($lastErr) $error .= ' (' . $lastErr . ')';
    }
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
  <title><?= htmlspecialchars($pageTitle ?: 'Kindle Serveur', ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body { margin:0; padding:0; background:#000; color:#e6e6e6; font-family:Helvetica, Arial, sans-serif; }
    .container { width: 100%; margin: 0; padding: 14px 10px; }

    .chrome { border: 2px solid #bdbdbd; background: #000; }
    .addr-top { padding: 8px 10px; border-bottom: 2px solid #bdbdbd; font-size: 18px; font-weight: 800; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .topbar { padding: 10px; }

    .bar { width: 100%; border-collapse: collapse; }
    .brand-cell { width: 185px; font-size: 20px; font-weight: 900; text-align: left; white-space: nowrap; padding-right: 10px; }
    .input-cell { width: auto; }
    .btn-cell { width: 90px; padding-left: 8px; }
    .search-input { width: 100%; padding: 10px; font-size: 17px; border: 2px solid #bdbdbd; background: #000; color: #e6e6e6; }
    .search-btn { width: 100%; padding: 10px 0; font-size: 17px; font-weight: 900; border: 2px solid #bdbdbd; background: #000; cursor: pointer; color: #e6e6e6; }

    .content { margin-top: 12px; border: 2px solid #bdbdbd; background: #000; padding: 10px; min-height: calc(100dvh - 190px); font-size: 18px; line-height: 1.35; overflow: auto; -webkit-overflow-scrolling: touch; }
    .content h1 { font-size: 22px; }
    .content h2 { font-size: 20px; }
    .content h3 { font-size: 18px; }
    .content ul { padding-left: 18px; }

    a { color: #e6e6e6; text-decoration: none; font-weight: 900; }
    a:visited { color: #e6e6e6; text-decoration: none; }
  </style>
</head>
<body>
  <div class="container">
    <div class="chrome">
      <div class="addr-top"><?= htmlspecialchars($addrText, ENT_QUOTES, 'UTF-8') ?></div>
      <div class="topbar">
        <form action="go.php" method="get">
          <table class="bar">
            <tr>
              <td class="brand-cell">Kindle Serveur</td>
              <td class="input-cell"><input class="search-input" type="text" name="q" value="<?= htmlspecialchars($displayValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Adres veya arama..." /></td>
              <td class="btn-cell"><button class="search-btn" type="submit">Ara</button></td>
            </tr>
          </table>
        </form>
      </div>
    </div>

    <div class="content">
      <?php if ($error): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <p><a href="index.php">Anasayfa</a></p>
      <?php else: ?>
        <?= $contentHtml ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
