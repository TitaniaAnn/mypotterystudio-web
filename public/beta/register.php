<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (BetaAuth::isLoggedIn()) {
    redirect(SITE_URL . '/beta/dashboard.php');
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $platform = $_POST['platform'] ?? '';
    $eula     = !empty($_POST['eula']);

    if (!$name)      $errors[] = 'Name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!in_array($platform, ['ios','android','both','other'])) $errors[] = 'Please select a platform.';
    if (!$eula) $errors[] = 'You must agree to the Beta EULA.';

    if (empty($errors)) {
        $existing = Database::fetchOne("SELECT id FROM beta_users WHERE email = ?", [$email]);
        if ($existing) {
            $errors[] = 'That email address is already registered.';
        } else {
            Database::insert('beta_users', [
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'name'          => $name,
                'platform'      => $platform,
                'approved'      => 0,
            ]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join the Beta — My Pottery Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Caveat:wght@400;600&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-auth-page">
<div class="beta-auth-wrap">
    <div class="beta-auth-card beta-auth-card--wide">
        <a href="/" class="beta-auth-logo">
            <span class="beta-auth-logo__icon">🏺</span>
            <span class="beta-auth-logo__text">My Pottery Studio</span>
        </a>
        <h1 class="beta-auth-title">Join the Beta</h1>
        <p class="beta-auth-sub">Help shape the future of pottery studio management</p>

        <?php if ($success): ?>
        <div class="beta-alert beta-alert--success">
            <strong>Application received.</strong> Your account is pending approval.
            You'll be able to sign in once an admin reviews and approves it.
        </div>
        <?php else: ?>

        <?php if ($errors): ?>
        <div class="beta-alert beta-alert--error">
            <ul style="margin:0;padding-left:16px;">
                <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" class="beta-form">
            <?= csrf_field() ?>
            <div class="beta-form__row">
                <div class="beta-form__group">
                    <label class="beta-form__label" for="name">Full Name</label>
                    <input class="beta-form__input" type="text" id="name" name="name"
                        value="<?= e($_POST['name'] ?? '') ?>" required autocomplete="name">
                </div>
                <div class="beta-form__group">
                    <label class="beta-form__label" for="email">Email Address</label>
                    <input class="beta-form__input" type="email" id="email" name="email"
                        value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
            </div>
            <div class="beta-form__row">
                <div class="beta-form__group">
                    <label class="beta-form__label" for="password">Password</label>
                    <input class="beta-form__input" type="password" id="password" name="password"
                        required autocomplete="new-password" minlength="8">
                    <span class="beta-form__hint">Minimum 8 characters</span>
                </div>
                <div class="beta-form__group">
                    <label class="beta-form__label" for="confirm_password">Confirm Password</label>
                    <input class="beta-form__input" type="password" id="confirm_password" name="confirm_password"
                        required autocomplete="new-password">
                </div>
            </div>
            <div class="beta-form__group">
                <label class="beta-form__label" for="platform">Primary Platform</label>
                <select class="beta-form__input beta-form__select" id="platform" name="platform" required>
                    <option value="">Select a platform…</option>
                    <option value="ios"     <?= (($_POST['platform'] ?? '') === 'ios')     ? 'selected' : '' ?>>iOS (iPhone / iPad)</option>
                    <option value="android" <?= (($_POST['platform'] ?? '') === 'android') ? 'selected' : '' ?>>Android</option>
                    <option value="both"    <?= (($_POST['platform'] ?? '') === 'both')    ? 'selected' : '' ?>>Both iOS and Android</option>
                    <option value="other"   <?= (($_POST['platform'] ?? '') === 'other')   ? 'selected' : '' ?>>Other / Desktop</option>
                </select>
            </div>
            <div class="beta-form__group">
                <label class="beta-form__checkbox">
                    <input type="checkbox" name="eula" <?= !empty($_POST['eula']) ? 'checked' : '' ?> required>
                    <span>I agree to the <a href="/beta/eula.php" target="_blank" class="beta-link">Beta EULA</a> and understand this is pre-release software.</span>
                </label>
            </div>
            <button type="submit" class="beta-btn beta-btn--primary beta-btn--block">Create Beta Account</button>
        </form>

        <div class="beta-auth-footer">
            <p>Already registered? <a href="/beta/login.php" class="beta-link">Sign in</a></p>
        </div>

        <?php endif; ?>
    </div>
</div>
</body>
</html>
