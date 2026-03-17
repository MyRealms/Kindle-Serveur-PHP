<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_config.php';

function kc2_auth_enabled(): bool {
  return defined('KC2_AUTH_PASSWORD') && is_string(KC2_AUTH_PASSWORD) && KC2_AUTH_PASSWORD !== '';
}

function kc2_auth_boot(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;

  $secure = false;
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') $secure = true;
  if (!empty($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443') $secure = true;

  if (kc2_auth_enabled() && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive');
  }

  session_name('kc2');
  if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'secure' => $secure,
      'httponly' => true,
      'samesite' => 'Strict',
    ]);
  } else {
    session_set_cookie_params(0, '/; samesite=Strict', '', $secure, true);
  }
  @ini_set('session.use_strict_mode', '1');
  session_start();
}

function kc2_auth_logged_in(): bool {
  kc2_auth_boot();
  return !empty($_SESSION['kc2_auth_ok']) && $_SESSION['kc2_auth_ok'] === true;
}

function kc2_auth_login(string $password): bool {
  kc2_auth_boot();
  $fails = (int)($_SESSION['kc2_auth_fail'] ?? 0);
  if ($fails >= 5) usleep(400_000);
  $ok = hash_equals((string)KC2_AUTH_PASSWORD, $password);
  if ($ok) {
    session_regenerate_id(true);
    $_SESSION['kc2_auth_ok'] = true;
    unset($_SESSION['kc2_auth_fail']);
    return true;
  }
  $_SESSION['kc2_auth_fail'] = (int)($_SESSION['kc2_auth_fail'] ?? 0) + 1;
  return false;
}

function kc2_auth_logout(): void {
  kc2_auth_boot();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'] ?? '/', '', !empty($p['secure']), !empty($p['httponly']));
  }
  session_destroy();
}

function kc2_auth_require_html(): void {
  if (!kc2_auth_enabled()) return;
  if (kc2_auth_logged_in()) return;

  $next = $_SERVER['REQUEST_URI'] ?? '/';
  header('Location: index.php?next=' . rawurlencode($next));
  exit;
}

function kc2_auth_require_json(): void {
  if (!kc2_auth_enabled()) return;
  if (kc2_auth_logged_in()) return;

  http_response_code(401);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function kc2_auth_require_fragment(): void {
  if (!kc2_auth_enabled()) return;
  if (kc2_auth_logged_in()) return;

  http_response_code(401);
  header('Content-Type: text/plain; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  echo 'unauthorized';
  exit;
}
