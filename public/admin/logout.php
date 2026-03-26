<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::logout();
flash('success', 'You have been signed out.');
redirect(SITE_URL . '/admin/login.php');
