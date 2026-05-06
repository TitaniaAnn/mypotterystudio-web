<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

if (Auth::isLoggedIn()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (isset($_GET['error'])) {
    redirect(SITE_URL . '/admin/login.php?error=access_denied');
}

if (!$code || !$state) {
    redirect(SITE_URL . '/admin/login.php?error=auth_failed');
}

$result = Auth::handleGitHubCallback($code, $state);
if ($result !== 'ok') {
    redirect(SITE_URL . '/admin/login.php?error=' . $result);
}

redirect(SITE_URL . '/admin/dashboard.php');
