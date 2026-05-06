<?php
/**
 * Test bootstrap.
 *
 * We deliberately do NOT load includes/bootstrap.php here, because that
 * file calls session_start() and reads .env. Instead we set the required
 * constants in $_ENV before pulling in vendor + the project's classes.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Use UTC across the suite so PHP's strtotime() matches SQLite's
// CURRENT_TIMESTAMP (which is always UTC).
date_default_timezone_set('UTC');

// Stand-in env values so config.php's defines() succeed.
$_ENV['DB_HOST']                = 'test';
$_ENV['DB_NAME']                = 'test';
$_ENV['DB_USER']                = 'test';
$_ENV['DB_PASS']                = 'test';
$_ENV['SITE_URL']               = 'https://test.local';
$_ENV['GITHUB_CLIENT_ID']       = 'test_client_id';
$_ENV['GITHUB_CLIENT_SECRET']   = 'test_client_secret';
$_ENV['ALLOWED_GITHUB_USERS']   = 'alice,bob';
$_ENV['BETA_GITHUB_REPO']       = '';
$_ENV['BETA_GITHUB_TOKEN']      = '';
$_ENV['BETA_DOWNLOAD_IOS']      = '';
$_ENV['BETA_DOWNLOAD_ANDROID']  = '';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/BetaAuth.php';
require_once __DIR__ . '/../includes/GitHubAPI.php';
require_once __DIR__ . '/../includes/Migrations.php';

// Pull in just the helpers from bootstrap.php without the side effects.
// We re-declare them here to avoid Auth::start()/session_start at load time.
if (!function_exists('e')) {
    function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('redirect')) {
    function redirect(string $url): void { header("Location: $url"); }
}
if (!function_exists('flash')) {
    function flash(string $type, string $msg): void { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
}
if (!function_exists('getFlash')) {
    function getFlash(): ?array {
        if (!empty($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
        return null;
    }
}
if (!function_exists('setting')) {
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
}
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}
if (!function_exists('csrf_check')) {
    function csrf_check(?string $token): bool {
        return !empty($_SESSION['csrf_token'])
            && is_string($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
