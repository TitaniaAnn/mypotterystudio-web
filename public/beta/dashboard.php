<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
BetaAuth::requireLogin();

$user     = BetaAuth::getUser();
$feedback = Database::fetchAll(
    "SELECT * FROM beta_feedback WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
    [$user['id']]
);

$hasDownloads = BETA_DOWNLOAD_IOS || BETA_DOWNLOAD_ANDROID;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — My Pottery Studio Beta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Caveat:wght@400;600&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-portal">

<!-- Sidebar -->
<aside class="beta-sidebar">
    <div class="beta-sidebar__header">
        <a href="/" class="beta-sidebar__logo">
            <span class="beta-sidebar__logo-icon">🏺</span>
            <span class="beta-sidebar__logo-text">My Pottery Studio</span>
        </a>
        <div class="beta-sidebar__badge">Beta Portal</div>
    </div>

    <nav class="beta-sidebar__nav">
        <a href="/beta/dashboard.php" class="beta-nav__item beta-nav__item--active">
            <span class="beta-nav__icon">🏠</span>
            Dashboard
        </a>
        <a href="/beta/submit.php" class="beta-nav__item">
            <span class="beta-nav__icon">📝</span>
            Submit Feedback
        </a>
        <a href="/beta/issues.php" class="beta-nav__item">
            <span class="beta-nav__icon">🐛</span>
            All Issues
        </a>
    </nav>

    <div class="beta-sidebar__footer">
        <div class="beta-sidebar__user">
            <div class="beta-sidebar__avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <div class="beta-sidebar__user-info">
                <span class="beta-sidebar__user-name"><?= e($user['name']) ?></span>
                <span class="beta-sidebar__user-email"><?= e($user['email']) ?></span>
            </div>
        </div>
        <a href="/beta/logout.php" class="beta-nav__item beta-nav__item--logout">
            <span class="beta-nav__icon">→</span>
            Sign Out
        </a>
    </div>
</aside>

<!-- Main content -->
<main class="beta-main">
    <header class="beta-topbar">
        <div class="beta-topbar__title">
            <span class="beta-topbar__script">Welcome back,</span>
            <h1><?= e($user['name']) ?></h1>
        </div>
        <div class="beta-topbar__actions">
            <a href="/beta/submit.php" class="beta-btn beta-btn--primary">
                + Submit Feedback
            </a>
        </div>
    </header>

    <div class="beta-content">
        <?php if ($flash): ?>
        <div class="beta-alert beta-alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
        <?php endif; ?>

        <!-- Downloads -->
        <?php if ($hasDownloads): ?>
        <section class="beta-section">
            <h2 class="beta-section__title">Downloads</h2>
            <div class="beta-downloads">
                <?php if (BETA_DOWNLOAD_IOS): ?>
                <a href="<?= e(BETA_DOWNLOAD_IOS) ?>" class="beta-download-card">
                    <span class="beta-download-card__icon"></span>
                    <span class="beta-download-card__label">iOS</span>
                    <span class="beta-download-card__sub">TestFlight / IPA</span>
                </a>
                <?php endif; ?>
                <?php if (BETA_DOWNLOAD_ANDROID): ?>
                <a href="<?= e(BETA_DOWNLOAD_ANDROID) ?>" class="beta-download-card">
                    <span class="beta-download-card__icon">🤖</span>
                    <span class="beta-download-card__label">Android</span>
                    <span class="beta-download-card__sub">APK</span>
                </a>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Quick Actions -->
        <section class="beta-section">
            <h2 class="beta-section__title">Quick Actions</h2>
            <div class="beta-quick-actions">
                <a href="/beta/submit.php?type=bug" class="beta-action-card">
                    <span class="beta-action-card__icon">🐛</span>
                    <span class="beta-action-card__label">Report a Bug</span>
                </a>
                <a href="/beta/submit.php?type=feature" class="beta-action-card">
                    <span class="beta-action-card__icon">💡</span>
                    <span class="beta-action-card__label">Request Feature</span>
                </a>
                <a href="/beta/eula.php" class="beta-action-card">
                    <span class="beta-action-card__icon">📄</span>
                    <span class="beta-action-card__label">View EULA</span>
                </a>
            </div>
        </section>

        <!-- Recent Feedback -->
        <section class="beta-section">
            <div class="beta-section__header">
                <h2 class="beta-section__title">Your Recent Feedback</h2>
                <a href="/beta/submit.php" class="beta-link">Submit new +</a>
            </div>
            <?php if (empty($feedback)): ?>
            <div class="beta-empty">
                <span class="beta-empty__icon">📋</span>
                <p>You haven't submitted any feedback yet.</p>
                <a href="/beta/submit.php" class="beta-btn beta-btn--primary">Submit Your First Feedback</a>
            </div>
            <?php else: ?>
            <div class="beta-feedback-list">
                <?php foreach ($feedback as $item): ?>
                <div class="beta-feedback-item">
                    <div class="beta-feedback-item__meta">
                        <span class="beta-badge beta-badge--<?= $item['type'] === 'bug' ? 'bug' : 'feature' ?>">
                            <?= $item['type'] === 'bug' ? '🐛 Bug' : '💡 Feature' ?>
                        </span>
                        <span class="beta-badge beta-badge--status beta-badge--<?= e($item['status']) ?>">
                            <?= e(str_replace('_', ' ', ucfirst($item['status']))) ?>
                        </span>
                    </div>
                    <div class="beta-feedback-item__title"><?= e($item['title']) ?></div>
                    <div class="beta-feedback-item__footer">
                        <span class="beta-feedback-item__votes">▲ <?= (int)$item['votes'] ?></span>
                        <?php if ($item['github_issue_url']): ?>
                        <a href="<?= e($item['github_issue_url']) ?>" target="_blank" class="beta-link beta-link--sm">View on GitHub</a>
                        <?php endif; ?>
                        <span class="beta-feedback-item__date"><?= date('M j, Y', strtotime($item['created_at'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script src="/beta/js/beta.js"></script>
</body>
</html>
