<?php
define('DB_HOST',     $_ENV['DB_HOST']);
define('DB_NAME',     $_ENV['DB_NAME']);
define('DB_USER',     $_ENV['DB_USER']);
define('DB_PASS',     $_ENV['DB_PASS']);
define('DB_CHARSET',  'utf8mb4');

define('SITE_URL',    rtrim($_ENV['SITE_URL'] ?? 'http://localhost', '/'));
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('UPLOAD_URL',  SITE_URL . '/uploads/');

define('GITHUB_CLIENT_ID',     $_ENV['GITHUB_CLIENT_ID'] ?? '');
define('GITHUB_CLIENT_SECRET', $_ENV['GITHUB_CLIENT_SECRET'] ?? '');
define('GITHUB_REDIRECT_URI',  SITE_URL . '/admin/auth/callback.php');
define('ALLOWED_GITHUB_USERS', $_ENV['ALLOWED_GITHUB_USERS'] ?? '');

define('BETA_GITHUB_REPO',  $_ENV['BETA_GITHUB_REPO']  ?? '');
define('BETA_GITHUB_TOKEN', $_ENV['BETA_GITHUB_TOKEN'] ?? '');
define('BETA_DOWNLOAD_IOS',     $_ENV['BETA_DOWNLOAD_IOS']     ?? getenv('BETA_DOWNLOAD_IOS')     ?: '');
define('BETA_DOWNLOAD_ANDROID', $_ENV['BETA_DOWNLOAD_ANDROID'] ?? getenv('BETA_DOWNLOAD_ANDROID') ?: '');

define('SESSION_NAME',     'mps_session');
define('SESSION_LIFETIME', 86400 * 7);
define('MAX_IMAGE_SIZE',   10 * 1024 * 1024);

define('MAIL_HOST',       $_ENV['MAIL_HOST']       ?? '');
define('MAIL_PORT',       (int)($_ENV['MAIL_PORT'] ?? 587));
define('MAIL_USER',       $_ENV['MAIL_USER']       ?? '');
define('MAIL_PASS',       $_ENV['MAIL_PASS']       ?? '');
define('MAIL_FROM',       $_ENV['MAIL_FROM']       ?? '');
define('MAIL_FROM_NAME',  $_ENV['MAIL_FROM_NAME']  ?? 'My Pottery Studio');
// 'tls' (STARTTLS, port 587) | 'ssl' (TLS-on-connect, port 465) | '' (none)
define('MAIL_ENCRYPTION', strtolower($_ENV['MAIL_ENCRYPTION'] ?? 'tls'));
define('MAIL_REPLY_TO',   $_ENV['MAIL_REPLY_TO']   ?? '');
