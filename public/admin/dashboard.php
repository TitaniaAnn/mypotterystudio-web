<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Dashboard';

// Stats
$totalTesters  = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM beta_users")['c'] ?? 0);
$pendingCount  = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM beta_users WHERE approved = 0")['c'] ?? 0);
$totalFeedback = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM beta_feedback")['c'] ?? 0);
$openBugs      = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM beta_feedback WHERE type='bug' AND status='open'")['c'] ?? 0);
$openFeatures  = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM beta_feedback WHERE type='feature' AND status='open'")['c'] ?? 0);
$newFeedback   = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM beta_feedback WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c'] ?? 0);

$recentFeedback = Database::fetchAll(
    "SELECT bf.*, bu.name as submitter_name
     FROM beta_feedback bf
     JOIN beta_users bu ON bf.user_id = bu.id
     ORDER BY bf.created_at DESC
     LIMIT 10"
);

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
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
        <?php if ($flash): ?>
        <div class="admin-alert admin-alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--blue">👥</div>
                <div class="admin-stat-card__body">
                    <div class="admin-stat-card__num"><?= $totalTesters ?></div>
                    <div class="admin-stat-card__label">Beta Testers</div>
                </div>
            </div>
            <?php if ($pendingCount > 0): ?>
            <div class="admin-stat-card admin-stat-card--warning">
                <div class="admin-stat-card__icon">⏳</div>
                <div class="admin-stat-card__body">
                    <div class="admin-stat-card__num"><?= $pendingCount ?></div>
                    <div class="admin-stat-card__label">Pending Approval</div>
                </div>
            </div>
            <?php endif; ?>
            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--green">💬</div>
                <div class="admin-stat-card__body">
                    <div class="admin-stat-card__num"><?= $totalFeedback ?></div>
                    <div class="admin-stat-card__label">Total Feedback</div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--red">🐛</div>
                <div class="admin-stat-card__body">
                    <div class="admin-stat-card__num"><?= $openBugs ?></div>
                    <div class="admin-stat-card__label">Open Bugs</div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--purple">💡</div>
                <div class="admin-stat-card__body">
                    <div class="admin-stat-card__num"><?= $openFeatures ?></div>
                    <div class="admin-stat-card__label">Feature Requests</div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--gold">🆕</div>
                <div class="admin-stat-card__body">
                    <div class="admin-stat-card__num"><?= $newFeedback ?></div>
                    <div class="admin-stat-card__label">New (7 days)</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-section">
            <h2 class="admin-section__title">Quick Actions</h2>
            <div class="admin-quick-actions">
                <a href="/admin/beta/testers.php"  class="admin-action-btn">👥 Manage Testers</a>
                <a href="/admin/beta/feedback.php" class="admin-action-btn">💬 View Feedback</a>
                <a href="/admin/beta/email.php"    class="admin-action-btn">✉️ Send Email</a>
                <a href="/admin/settings/index.php" class="admin-action-btn">⚙️ App Settings</a>
                <a href="/" target="_blank"        class="admin-action-btn admin-action-btn--outline">↗ View Site</a>
            </div>
        </div>

        <!-- Recent Feedback -->
        <div class="admin-section">
            <div class="admin-section__header">
                <h2 class="admin-section__title">Recent Feedback</h2>
                <a href="/admin/beta/feedback.php" class="admin-link">View all →</a>
            </div>
            <?php if (empty($recentFeedback)): ?>
            <div class="admin-empty">No feedback submitted yet.</div>
            <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Submitter</th>
                            <th>Votes</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentFeedback as $item): ?>
                        <tr>
                            <td>
                                <?= e($item['title']) ?>
                                <?php if ($item['github_issue_url']): ?>
                                <a href="<?= e($item['github_issue_url']) ?>" target="_blank" class="admin-link admin-link--sm" title="View on GitHub">GH</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge--<?= $item['type'] === 'bug' ? 'red' : 'purple' ?>">
                                    <?= $item['type'] === 'bug' ? '🐛 Bug' : '💡 Feature' ?>
                                </span>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge--<?= $item['status'] === 'open' ? 'green' : ($item['status'] === 'in_progress' ? 'yellow' : ($item['status'] === 'paused' ? 'orange' : ($item['status'] === 'testing' ? 'blue' : 'gray'))) ?>">
                                    <?= e(ucfirst(str_replace('_',' ',$item['status']))) ?>
                                </span>
                            </td>
                            <td><?= e($item['submitter_name']) ?></td>
                            <td><?= (int)$item['votes'] ?></td>
                            <td><?= date('M j, Y', strtotime($item['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/admin/js/admin.js"></script>
</body>
</html>
