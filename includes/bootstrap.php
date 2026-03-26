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
