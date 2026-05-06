<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$id   = (int)($_GET['id'] ?? 0);
$item = $id ? Database::fetchOne(
    "SELECT bf.*, bu.name as submitter_name, bu.email as submitter_email
     FROM beta_feedback bf
     JOIN beta_users bu ON bf.user_id = bu.id
     WHERE bf.id = ?",
    [$id]
) : null;

if (!$item) {
    header('Location: /admin/beta/feedback.php');
    exit;
}

$message = '';

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action    = $_POST['action'] ?? '';
    $newStatus = $_POST['new_status'] ?? '';
    if ($action === 'status' && in_array($newStatus, ['open','in_progress','paused','testing','closed'])) {
        Database::execute("UPDATE beta_feedback SET status = ? WHERE id = ?", [$newStatus, $id]);
        $item['status'] = $newStatus;
        $message = 'Status updated.';
    }
}

$pageTitle = 'Feedback: ' . $item['title'];
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

        <?php if ($message): ?>
        <div class="admin-alert admin-alert--success"><?= e($message) ?></div>
        <?php endif; ?>

        <div style="margin-bottom:16px;">
            <a href="/admin/beta/feedback.php" class="admin-link" style="font-size:13px;">← Back to Feedback</a>
        </div>

        <div class="admin-section">
            <div class="admin-section__header">
                <h2 class="admin-section__title"><?= e($item['title']) ?></h2>
            </div>

            <div class="admin-card">
                <div style="display:flex;gap:8px;align-items:center;margin-bottom:20px;flex-wrap:wrap;">
                    <span class="admin-badge admin-badge--<?= $item['type'] === 'bug' ? 'red' : 'purple' ?>">
                        <?= $item['type'] === 'bug' ? '🐛 Bug' : '💡 Feature' ?>
                    </span>
                    <span class="admin-badge admin-badge--<?= $item['status'] === 'open' ? 'green' : ($item['status'] === 'in_progress' ? 'yellow' : ($item['status'] === 'paused' ? 'orange' : ($item['status'] === 'testing' ? 'blue' : 'gray'))) ?>">
                        <?= e(ucfirst(str_replace('_', ' ', $item['status']))) ?>
                    </span>
                    <span style="font-size:13px;color:var(--admin-text-lt);">
                        <?= (int)$item['votes'] ?> votes &middot; <?= date('M j, Y g:ia', strtotime($item['created_at'])) ?>
                    </span>
                </div>

                <div style="margin-bottom:20px;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--admin-text-lt);margin-bottom:8px;">Description</div>
                    <div style="font-size:14px;line-height:1.7;white-space:pre-wrap;background:var(--admin-bg);border:1px solid var(--admin-border);border-radius:var(--radius);padding:16px 18px;"><?= e($item['body']) ?></div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                    <div>
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--admin-text-lt);margin-bottom:4px;">Submitted by</div>
                        <div style="font-size:14px;"><?= e($item['submitter_name']) ?></div>
                        <div style="font-size:12px;color:var(--admin-text-lt);"><?= e($item['submitter_email']) ?></div>
                    </div>
                    <?php if ($item['github_issue_url']): ?>
                    <div>
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--admin-text-lt);margin-bottom:4px;">GitHub Issue</div>
                        <a href="<?= e($item['github_issue_url']) ?>" target="_blank" class="admin-link">
                            #<?= (int)$item['github_issue_number'] ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div>
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--admin-text-lt);margin-bottom:8px;">Change Status</div>
                    <form method="POST" style="display:flex;gap:10px;align-items:center;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="status">
                        <select name="new_status" class="admin-filter-select">
                            <option value="open"        <?= $item['status'] === 'open'        ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= $item['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="paused"      <?= $item['status'] === 'paused'      ? 'selected' : '' ?>>Paused</option>
                            <option value="testing"     <?= $item['status'] === 'testing'     ? 'selected' : '' ?>>Testing</option>
                            <option value="closed"      <?= $item['status'] === 'closed'      ? 'selected' : '' ?>>Closed</option>
                        </select>
                        <button type="submit" class="admin-btn admin-btn--primary">Update</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="/admin/js/admin.js"></script>
</body>
</html>
