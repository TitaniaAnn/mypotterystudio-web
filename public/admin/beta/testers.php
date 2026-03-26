<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Beta Testers';
$message   = '';
$msgType   = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId) {
        if ($action === 'approve') {
            Database::execute("UPDATE beta_users SET approved = 1 WHERE id = ?", [$userId]);
            $message = 'Tester approved.';
        } elseif ($action === 'revoke') {
            Database::execute("UPDATE beta_users SET approved = 0 WHERE id = ?", [$userId]);
            $message = 'Access revoked.';
        } elseif ($action === 'delete') {
            Database::execute("DELETE FROM beta_users WHERE id = ?", [$userId]);
            $message = 'Tester deleted.';
        }
    }
}

$testers = Database::fetchAll(
    "SELECT *, (SELECT COUNT(*) FROM beta_feedback WHERE user_id = bu.id) as feedback_count
     FROM beta_users bu
     ORDER BY created_at DESC"
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

        <div class="admin-section">
            <div class="admin-section__header">
                <h2 class="admin-section__title">
                    Beta Testers
                    <span class="admin-badge admin-badge--gray"><?= count($testers) ?></span>
                </h2>
            </div>

            <?php if (empty($testers)): ?>
            <div class="admin-empty">No beta testers have registered yet.</div>
            <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Platform</th>
                            <th>Status</th>
                            <th>Feedback</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($testers as $tester): ?>
                        <tr>
                            <td><?= e($tester['name']) ?></td>
                            <td><?= e($tester['email']) ?></td>
                            <td><span class="admin-badge admin-badge--gray"><?= e(ucfirst($tester['platform'])) ?></span></td>
                            <td>
                                <?php if ($tester['approved']): ?>
                                <span class="admin-badge admin-badge--green">Approved</span>
                                <?php else: ?>
                                <span class="admin-badge admin-badge--yellow">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$tester['feedback_count'] ?></td>
                            <td><?= date('M j, Y', strtotime($tester['created_at'])) ?></td>
                            <td><?= $tester['last_login'] ? date('M j, Y', strtotime($tester['last_login'])) : '—' ?></td>
                            <td>
                                <div class="admin-row-actions">
                                    <?php if (!$tester['approved']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="user_id" value="<?= $tester['id'] ?>">
                                        <button type="submit" class="admin-btn-sm admin-btn-sm--green">Approve</button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="revoke">
                                        <input type="hidden" name="user_id" value="<?= $tester['id'] ?>">
                                        <button type="submit" class="admin-btn-sm admin-btn-sm--yellow">Revoke</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this tester? This cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $tester['id'] ?>">
                                        <button type="submit" class="admin-btn-sm admin-btn-sm--red">Delete</button>
                                    </form>
                                </div>
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
