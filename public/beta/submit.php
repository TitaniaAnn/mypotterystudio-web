<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
BetaAuth::requireLogin();

$user    = BetaAuth::getUser();
$errors  = [];
$success = false;

$defaultType = in_array($_GET['type'] ?? '', ['bug','feature']) ? $_GET['type'] : 'bug';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type       = in_array($_POST['type'] ?? '', ['bug','feature']) ? $_POST['type'] : '';
    $title      = trim($_POST['title'] ?? '');
    $body       = trim($_POST['body'] ?? '');
    $platform   = trim($_POST['platform'] ?? '');
    $appVersion = trim($_POST['app_version'] ?? '');

    if (!$type)  $errors[] = 'Please select a feedback type.';
    if (!$title) $errors[] = 'Title is required.';
    if (!$body)  $errors[] = 'Description is required.';

    if (empty($errors)) {
        $githubIssueNumber = null;
        $githubIssueUrl    = null;

        if (BETA_GITHUB_REPO && BETA_GITHUB_TOKEN) {
            $labels = [$type === 'bug' ? 'bug' : 'enhancement', 'beta-feedback'];
            $issueBody = "**Submitted by beta tester** (platform: " . ($platform ?: 'unspecified') . ", version: " . ($appVersion ?: 'unspecified') . ")\n\n" . $body;
            $issue = GitHubAPI::createIssue(BETA_GITHUB_REPO, BETA_GITHUB_TOKEN, $title, $issueBody, $labels);
            if (!empty($issue['number'])) {
                $githubIssueNumber = $issue['number'];
                $githubIssueUrl    = $issue['html_url'] ?? null;
            }
        }

        $id = Database::insert('beta_feedback', [
            'user_id'             => $user['id'],
            'type'                => $type,
            'title'               => $title,
            'body'                => $body,
            'platform'            => $platform,
            'app_version'         => $appVersion,
            'github_issue_number' => $githubIssueNumber,
            'github_issue_url'    => $githubIssueUrl,
        ]);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback — My Pottery Studio Beta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Caveat:wght@400;600&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-portal">

<aside class="beta-sidebar">
    <div class="beta-sidebar__header">
        <a href="/" class="beta-sidebar__logo">
            <span class="beta-sidebar__logo-icon">🏺</span>
            <span class="beta-sidebar__logo-text">My Pottery Studio</span>
        </a>
        <div class="beta-sidebar__badge">Beta Portal</div>
    </div>
    <nav class="beta-sidebar__nav">
        <a href="/beta/dashboard.php" class="beta-nav__item">
            <span class="beta-nav__icon">🏠</span> Dashboard
        </a>
        <a href="/beta/submit.php" class="beta-nav__item beta-nav__item--active">
            <span class="beta-nav__icon">📝</span> Submit Feedback
        </a>
        <a href="/beta/issues.php" class="beta-nav__item">
            <span class="beta-nav__icon">🐛</span> All Issues
        </a>
    </nav>
    <div class="beta-sidebar__footer">
        <div class="beta-sidebar__user">
            <div class="beta-sidebar__avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <div class="beta-sidebar__user-info">
                <span class="beta-sidebar__user-name"><?= e($user['name']) ?></span>
            </div>
        </div>
        <form method="POST" action="/beta/logout.php" style="margin:0;">
            <?= csrf_field() ?>
            <button type="submit" class="beta-nav__item beta-nav__item--logout"
                    style="background:none;border:none;width:100%;text-align:left;cursor:pointer;font:inherit;color:inherit;">
                <span class="beta-nav__icon">→</span> Sign Out
            </button>
        </form>
    </div>
</aside>

<main class="beta-main">
    <header class="beta-topbar">
        <div class="beta-topbar__title">
            <a href="/beta/dashboard.php" class="beta-back-link">← Dashboard</a>
            <h1>Submit Feedback</h1>
        </div>
    </header>

    <div class="beta-content">
        <?php if ($success): ?>
        <div class="beta-alert beta-alert--success">
            <strong>Thank you!</strong> Your feedback has been submitted<?= $githubIssueUrl ? ' and a <a href="' . e($githubIssueUrl) . '" target="_blank" class="beta-link">GitHub issue</a> was created' : '' ?>.
        </div>
        <a href="/beta/dashboard.php" class="beta-btn beta-btn--outline">← Back to Dashboard</a>
        <a href="/beta/submit.php" class="beta-btn beta-btn--primary" style="margin-left:10px;">Submit Another</a>
        <?php else: ?>

        <?php if ($errors): ?>
        <div class="beta-alert beta-alert--error">
            <ul style="margin:0;padding-left:16px;">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="beta-card beta-card--form">
            <form method="POST" class="beta-form">
                <?= csrf_field() ?>
                <!-- Type tabs -->
                <div class="beta-form__group">
                    <label class="beta-form__label">Feedback Type</label>
                    <div class="beta-type-tabs">
                        <label class="beta-type-tab <?= (($_POST['type'] ?? $defaultType) === 'bug') ? 'beta-type-tab--active' : '' ?>">
                            <input type="radio" name="type" value="bug" <?= (($_POST['type'] ?? $defaultType) === 'bug') ? 'checked' : '' ?>>
                            🐛 Bug Report
                        </label>
                        <label class="beta-type-tab <?= (($_POST['type'] ?? $defaultType) === 'feature') ? 'beta-type-tab--active' : '' ?>">
                            <input type="radio" name="type" value="feature" <?= (($_POST['type'] ?? $defaultType) === 'feature') ? 'checked' : '' ?>>
                            💡 Feature Request
                        </label>
                    </div>
                </div>

                <div class="beta-form__group">
                    <label class="beta-form__label" for="title">Title <span class="beta-form__required">*</span></label>
                    <input class="beta-form__input" type="text" id="title" name="title"
                        value="<?= e($_POST['title'] ?? '') ?>" required
                        placeholder="Brief summary of the bug or feature">
                </div>

                <div class="beta-form__group">
                    <label class="beta-form__label" for="body">Description <span class="beta-form__required">*</span></label>
                    <textarea class="beta-form__input beta-form__textarea" id="body" name="body"
                        rows="7" required
                        placeholder="For bugs: steps to reproduce, expected vs actual behavior. For features: describe what you'd like and why."><?= e($_POST['body'] ?? '') ?></textarea>
                </div>

                <div class="beta-form__row">
                    <div class="beta-form__group">
                        <label class="beta-form__label" for="platform">Platform</label>
                        <select class="beta-form__input beta-form__select" id="platform" name="platform">
                            <option value="">Not specified</option>
                            <option value="iOS"     <?= (($_POST['platform'] ?? '') === 'iOS')     ? 'selected' : '' ?>>iOS</option>
                            <option value="Android" <?= (($_POST['platform'] ?? '') === 'Android') ? 'selected' : '' ?>>Android</option>
                            <option value="macOS"   <?= (($_POST['platform'] ?? '') === 'macOS')   ? 'selected' : '' ?>>macOS</option>
                            <option value="Windows" <?= (($_POST['platform'] ?? '') === 'Windows') ? 'selected' : '' ?>>Windows</option>
                        </select>
                    </div>
                    <div class="beta-form__group">
                        <label class="beta-form__label" for="app_version">App Version</label>
                        <input class="beta-form__input" type="text" id="app_version" name="app_version"
                            value="<?= e($_POST['app_version'] ?? '') ?>" placeholder="e.g. 1.0.2-beta">
                    </div>
                </div>

                <div class="beta-form__actions">
                    <a href="/beta/dashboard.php" class="beta-btn beta-btn--outline">Cancel</a>
                    <button type="submit" class="beta-btn beta-btn--primary">Submit Feedback</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</main>

<script src="/beta/js/beta.js"></script>
</body>
</html>
