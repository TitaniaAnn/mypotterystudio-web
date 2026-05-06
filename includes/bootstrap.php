<?php
define('ROOT_PATH', dirname(__DIR__));

if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    die("Run: composer install");
}
require_once ROOT_PATH . '/vendor/autoload.php';

if (file_exists(ROOT_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/Database.php';
require_once ROOT_PATH . '/includes/Auth.php';
require_once ROOT_PATH . '/includes/BetaAuth.php';
require_once ROOT_PATH . '/includes/GitHubAPI.php';
require_once ROOT_PATH . '/includes/Migrations.php';

Auth::start();

function setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        try {
            $rows  = Database::fetchAll("SELECT setting_key, setting_value FROM settings");
            $cache = array_column($rows, 'setting_value', 'setting_key');
        } catch (Exception $e) { $cache = []; }
    }
    return $cache[$key] ?? $default;
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header("Location: $url"); exit; }
function flash(string $type, string $msg): void { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

/* ─────────── CSRF ───────────
 * One token per session. Render via csrf_field() on every state-changing form.
 * Validate via verify_csrf() in every POST handler. JSON endpoints can pass
 * the token in the X-CSRF-Token header.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(?string $token): bool {
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF on the current request. Reads from $_POST['_csrf'] first,
 * falling back to the X-CSRF-Token header (for JSON APIs).
 *
 * On failure: emits 403, an error response (JSON or HTML based on Accept),
 * and exits. Pass $jsonResponse=true on JSON endpoints.
 */
function verify_csrf(bool $jsonResponse = false): void {
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (csrf_check($token)) return;

    http_response_code(403);
    if ($jsonResponse) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
    } else {
        echo 'CSRF token invalid. Please reload the page and try again.';
    }
    exit;
}
