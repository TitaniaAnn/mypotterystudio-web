<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (BetaAuth::isLoggedIn()) {
    redirect(SITE_URL . '/beta/dashboard.php');
} else {
    redirect(SITE_URL . '/beta/login.php');
}
