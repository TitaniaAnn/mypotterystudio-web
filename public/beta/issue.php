<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
BetaAuth::requireLogin();

$user = BetaAuth::getUser();
$id   = (int)($_GET['id'] ?? 0);
$item = $id ? Database::fetchOne(
    "SELECT bf.*, bu.name as submitter_name,
        (SELECT 1 FROM beta_votes bv WHERE bv.feedback_id = bf.id AND bv.user_id = ?) as user_voted
     FROM beta_feedback bf
     JOIN beta_users bu ON bf.user_id = bu.id
     WHERE bf.id = ?",
    [$user['id'], $id]
) : null;

if (!$item) {
    header('Location: /beta/issues.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($item['title']) ?> — My Pottery Studio Beta</title>
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
        <a href="/beta/dashboard.php" class="beta-nav__item">
            <span class="beta-nav__icon">🏠</span>
            Dashboard
        </a>
        <a href="/beta/submit.php" class="beta-nav__item">
            <span class="beta-nav__icon">📝</span>
            Submit Feedback
        </a>
        <a href="/beta/issues.php" class="beta-nav__item beta-nav__item--active">
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
            <span class="beta-topbar__script">
                <a href="/beta/issues.php" class="beta-link" style="font-size:13px;">← All Issues</a>
            </span>
            <h1><?= e($item['title']) ?></h1>
        </div>
    </header>

    <div class="beta-content">
        <section class="beta-section">
            <div class="beta-feedback-item" style="cursor:default;">
                <div class="beta-feedback-item__meta">
                    <span class="beta-badge beta-badge--<?= $item['type'] === 'bug' ? 'bug' : 'feature' ?>">
                        <?= $item['type'] === 'bug' ? '🐛 Bug' : '💡 Feature' ?>
                    </span>
                    <span class="beta-badge beta-badge--status beta-badge--<?= e($item['status']) ?>">
                        <?= e(str_replace('_', ' ', ucfirst($item['status']))) ?>
                    </span>
                </div>

                <div style="margin:16px 0;font-size:14px;line-height:1.7;white-space:pre-wrap;"><?= e($item['body']) ?></div>

                <div class="beta-feedback-item__footer">
                    <button class="beta-vote-btn <?= $item['user_voted'] ? 'beta-vote-btn--voted' : '' ?>"
                            data-vote="<?= (int)$item['id'] ?>">
                        ▲ <span class="vote-count" data-vote-count><?= (int)$item['votes'] ?></span>
                    </button>
                    <span class="beta-feedback-item__author">by <?= e($item['submitter_name']) ?></span>
                    <?php if ($item['github_issue_url']): ?>
                    <a href="<?= e($item['github_issue_url']) ?>" target="_blank" class="beta-link beta-link--sm">View on GitHub</a>
                    <?php endif; ?>
                    <span class="beta-feedback-item__date"><?= date('M j, Y', strtotime($item['created_at'])) ?></span>
                </div>
            </div>
        </section>
    </div>
</main>

<script src="/beta/js/beta.js"></script>
</body>
</html>
