<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Beta Feedback';
$message   = '';

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action     = $_POST['action'] ?? '';
    $feedbackId = (int)($_POST['feedback_id'] ?? 0);
    if ($feedbackId && $action === 'status') {
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, ['open','in_progress','paused','testing','closed'])) {
            Database::execute("UPDATE beta_feedback SET status = ? WHERE id = ?", [$newStatus, $feedbackId]);
            $message = 'Status updated.';
        }
    }
}

// Filters
$typeFilter   = in_array($_GET['type'] ?? '', ['bug','feature']) ? $_GET['type'] : '';
$statusFilter = in_array($_GET['status'] ?? '', ['open','in_progress','paused','testing','closed']) ? $_GET['status'] : '';

$sql    = "SELECT bf.*, bu.name as submitter_name FROM beta_feedback bf LEFT JOIN beta_users bu ON bf.user_id = bu.id WHERE 1=1";
$params = [];
if ($typeFilter)   { $sql .= " AND bf.type = ?";   $params[] = $typeFilter; }
if ($statusFilter) { $sql .= " AND bf.status = ?"; $params[] = $statusFilter; }
$sql .= " ORDER BY bf.votes DESC, bf.created_at DESC";

$items = Database::fetchAll($sql, $params);
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

        <!-- Filters -->
        <form method="GET" class="admin-filters">
            <div class="admin-filter-group">
                <label class="admin-filter-label">Type</label>
                <select name="type" class="admin-filter-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="bug"     <?= $typeFilter   === 'bug'     ? 'selected' : '' ?>>Bug Reports</option>
                    <option value="feature" <?= $typeFilter   === 'feature' ? 'selected' : '' ?>>Feature Requests</option>
                </select>
            </div>
            <div class="admin-filter-group">
                <label class="admin-filter-label">Status</label>
                <select name="status" class="admin-filter-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="open"        <?= $statusFilter === 'open'        ? 'selected' : '' ?>>Open</option>
                    <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="paused"      <?= $statusFilter === 'paused'      ? 'selected' : '' ?>>Paused</option>
                    <option value="testing"     <?= $statusFilter === 'testing'     ? 'selected' : '' ?>>Testing</option>
                    <option value="closed"      <?= $statusFilter === 'closed'      ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            <?php if ($typeFilter || $statusFilter): ?>
            <a href="/admin/beta/feedback.php" class="admin-btn-sm">Clear Filters</a>
            <?php endif; ?>
        </form>

        <div class="admin-section">
            <div class="admin-section__header">
                <h2 class="admin-section__title">
                    Feedback
                    <span class="admin-badge admin-badge--gray"><?= count($items) ?></span>
                </h2>
            </div>

            <?php if (empty($items)): ?>
            <div class="admin-empty">No feedback found matching your filters.</div>
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
                            <th>GitHub</th>
                            <th>Date</th>
                            <th>Change Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="admin-table__primary">
                                <a href="/admin/beta/feedback_detail.php?id=<?= (int)$item['id'] ?>" class="admin-link"><?= e($item['title']) ?></a>
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
                            <td><?= $item['submitter_name'] !== null ? e($item['submitter_name']) : '<em class="admin-text-muted">Deleted tester</em>' ?></td>
                            <td><?= (int)$item['votes'] ?></td>
                            <td>
                                <?php if ($item['github_issue_url']): ?>
                                <a href="<?= e($item['github_issue_url']) ?>" target="_blank" class="admin-link admin-link--sm">
                                    #<?= (int)$item['github_issue_number'] ?>
                                </a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($item['created_at'])) ?></td>
                            <td>
                                <form method="POST" class="admin-inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="feedback_id" value="<?= $item['id'] ?>">
                                    <select name="new_status" class="admin-filter-select admin-filter-select--sm"
                                        onchange="this.form.submit()">
                                        <option value="open"        <?= $item['status'] === 'open'        ? 'selected' : '' ?>>Open</option>
                                        <option value="in_progress" <?= $item['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="paused"      <?= $item['status'] === 'paused'      ? 'selected' : '' ?>>Paused</option>
                                        <option value="testing"     <?= $item['status'] === 'testing'     ? 'selected' : '' ?>>Testing</option>
                                        <option value="closed"      <?= $item['status'] === 'closed'      ? 'selected' : '' ?>>Closed</option>
                                    </select>
                                </form>
                            </td>
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
