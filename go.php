<?php
require_once __DIR__ . '/auth.php';
kc2_auth_require_html();

header('Content-Type: text/html; charset=utf-8');
@ini_set('default_charset', 'UTF-8');

$raw = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if ($raw === '') {
    header('Location: index.php');
    exit;
}

// If it contains whitespace, treat as a search query.
if (preg_match('/\s/u', $raw)) {
    header('Location: browser.php?q=' . rawurlencode($raw));
    exit;
}

// Direct URL.
if (preg_match('#^https?://#i', $raw)) {
    header('Location: browser.php?u=' . rawurlencode($raw));
    exit;
}

// Looks like a domain or a path; default to https for server-side fetch.
if (preg_match('#^www\.#i', $raw) || strpos($raw, '.') !== false || strpos($raw, '/') !== false) {
    header('Location: browser.php?u=' . rawurlencode('https://' . $raw));
    exit;
}

// Fallback to search.
header('Location: browser.php?q=' . rawurlencode($raw));
exit;
?>
