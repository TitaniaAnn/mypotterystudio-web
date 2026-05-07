<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    redirect(SITE_URL . '/admin/dashboard.php');
}
verify_csrf();
Auth::logout();
flash('success', 'You have been signed out.');
redirect(SITE_URL . '/admin/login.php');
