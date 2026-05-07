<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'App Settings';
$message   = '';
$msgType   = 'success';

// All editable settings
$settingFields = [
    'Site Identity' => [
        'site_name'   => ['label' => 'Site Name',   'type' => 'text'],
        'tagline'     => ['label' => 'Tagline',      'type' => 'text'],
    ],
    'Hero Section' => [
        'hero_title'    => ['label' => 'Hero Title',    'type' => 'text'],
        'hero_subtitle' => ['label' => 'Hero Subtitle', 'type' => 'text'],
    ],
    'About Section' => [
        'about_text' => ['label' => 'About Text', 'type' => 'textarea'],
    ],
    'App Status' => [
        'app_status' => ['label' => 'App Status', 'type' => 'select',
            'options' => ['beta' => 'Beta', 'live' => 'Live', 'coming_soon' => 'Coming Soon']],
        'beta_open'  => ['label' => 'Beta Open', 'type' => 'select',
            'options' => ['1' => 'Yes — accepting new testers', '0' => 'No — closed']],
    ],
    'App Store Links' => [
        'ios_url'     => ['label' => 'iOS App Store URL',     'type' => 'url'],
        'android_url' => ['label' => 'Android Play Store URL', 'type' => 'url'],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $allowedKeys = array_keys(array_merge(...array_values($settingFields)));
    foreach ($_POST['settings'] ?? [] as $key => $value) {
        if (!in_array($key, $allowedKeys, true)) continue;
        $value = is_string($value) ? trim($value) : '';
        Database::execute(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = ?",
            [$key, $value, $value]
        );
    }
    $message = 'Settings saved successfully.';
}

// Load current settings
$settings = [];
foreach (Database::fetchAll("SELECT setting_key, setting_value FROM settings") as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Admin — My Pottery Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Caveat:wght@400;600&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body class="admin-panel">
<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">

        <?php if ($flash): ?>
        <div class="admin-alert admin-alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
        <div class="admin-alert admin-alert--<?= e($msgType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="POST" class="admin-form admin-settings-form">
            <?= csrf_field() ?>
            <?php foreach ($settingFields as $groupName => $fields): ?>
            <div class="admin-section">
                <h2 class="admin-section__title"><?= e($groupName) ?></h2>
                <div class="admin-card">
                    <?php foreach ($fields as $key => $field): ?>
                    <div class="admin-form__group">
                        <label class="admin-form__label" for="setting_<?= e($key) ?>">
                            <?= e($field['label']) ?>
                        </label>
                        <?php $val = $settings[$key] ?? ''; ?>
                        <?php if ($field['type'] === 'textarea'): ?>
                        <textarea class="admin-form__input admin-form__textarea"
                            id="setting_<?= e($key) ?>"
                            name="settings[<?= e($key) ?>]"
                            rows="4"><?= e($val) ?></textarea>
                        <?php elseif ($field['type'] === 'select'): ?>
                        <select class="admin-form__input admin-form__select"
                            id="setting_<?= e($key) ?>"
                            name="settings[<?= e($key) ?>]">
                            <?php foreach ($field['options'] as $optVal => $optLabel): ?>
                            <option value="<?= e($optVal) ?>" <?= $val === (string)$optVal ? 'selected' : '' ?>>
                                <?= e($optLabel) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input class="admin-form__input"
                            type="<?= e($field['type']) ?>"
                            id="setting_<?= e($key) ?>"
                            name="settings[<?= e($key) ?>]"
                            value="<?= e($val) ?>">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="admin-form__sticky-footer">
                <button type="submit" class="admin-btn admin-btn--primary">Save All Settings</button>
            </div>
        </form>
    </div>
</div>

<script src="/admin/js/admin.js"></script>
</body>
</html>
