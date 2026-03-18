<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
BetaAuth::logout();
flash('success', 'You have been signed out.');
redirect(SITE_URL . '/beta/login.php');
