<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (BetaAuth::isLoggedIn()) {
    redirect(SITE_URL . '/beta/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? null;
    switch (BetaAuth::attemptLogin($email, $password, $ip)) {
        case 'ok':
            redirect(SITE_URL . '/beta/dashboard.php');
        case 'rate_limited':
            $error = 'Too many failed attempts. Please try again in 15 minutes.';
            break;
        default:
            $error = 'Invalid email or password, or your account has not been approved.';
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beta Login — My Pottery Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Caveat:wght@400;600&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-auth-page">
<div class="beta-auth-wrap">
    <div class="beta-auth-card">
        <a href="/" class="beta-auth-logo">
            <span class="beta-auth-logo__icon">🏺</span>
            <span class="beta-auth-logo__text">My Pottery Studio</span>
        </a>
        <h1 class="beta-auth-title">Beta Portal</h1>
        <p class="beta-auth-sub">Sign in to access the tester dashboard</p>

        <?php if ($flash): ?>
        <div class="beta-alert beta-alert--<?= e($flash['type']) ?>">
            <?= e($flash['msg']) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="beta-alert beta-alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="beta-form">
            <?= csrf_field() ?>
            <div class="beta-form__group">
                <label class="beta-form__label" for="email">Email Address</label>
                <input class="beta-form__input" type="email" id="email" name="email"
                    value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
            </div>
            <div class="beta-form__group">
                <label class="beta-form__label" for="password">Password</label>
                <input class="beta-form__input" type="password" id="password" name="password"
                    required autocomplete="current-password">
            </div>
            <button type="submit" class="beta-btn beta-btn--primary beta-btn--block">Sign In</button>
        </form>

        <div class="beta-auth-footer">
            <p>Not registered? <a href="/beta/register.php" class="beta-link">Apply for beta access</a></p>
        </div>
    </div>
</div>
</body>
</html>
