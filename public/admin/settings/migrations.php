<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Database Migrations';
$message   = '';
$msgType   = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action   = $_POST['action']   ?? '';
    $filename = $_POST['filename'] ?? '';
    try {
        if ($action === 'apply') {
            $count   = Migrations::apply($filename);
            $message = "Applied $filename — $count statement(s) executed.";
        } elseif ($action === 'mark') {
            Migrations::markApplied($filename);
            $message = "Marked $filename as applied (no SQL run).";
        } elseif ($action === 'apply_all') {
            $applied = [];
            foreach (Migrations::pending() as $f) {
                Migrations::apply($f);
                $applied[] = $f;
            }
            $message = $applied
                ? 'Applied: ' . implode(', ', $applied)
                : 'No pending migrations.';
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $msgType = 'error';
    }
}

// Reload state after any POST.
Migrations::ensureTable();
$applied   = Migrations::applied();
$available = Migrations::available();
$flash     = getFlash();
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
    <style>
        .mig-row { display:grid; grid-template-columns:1fr auto auto; gap:12px; align-items:center; padding:12px 14px; border:1px solid var(--admin-border); border-radius:var(--radius); margin-bottom:8px; background:#fff; }
        .mig-row__name { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:13px; color:var(--admin-text); }
        .mig-row__meta { font-size:12px; color:var(--admin-text-lt); }
        .mig-row--applied { background:var(--admin-bg); }
        .mig-row__actions { display:flex; gap:6px; }
        .mig-help { font-size:13px; color:var(--admin-text-lt); margin-bottom:16px; line-height:1.6; }
        .mig-help code { background:var(--admin-bg); padding:1px 6px; border-radius:3px; font-size:12px; }
    </style>
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
                    Pending
                    <span class="admin-badge admin-badge--gray"><?= count($available) - count($applied) ?></span>
                </h2>
                <?php if (count($available) > count($applied)): ?>
                <form method="POST" style="margin:0;" onsubmit="return confirm('Apply ALL pending migrations in order?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="apply_all">
                    <button type="submit" class="admin-btn admin-btn--primary">Apply All</button>
                </form>
                <?php endif; ?>
            </div>
            <p class="mig-help">
                Migration files live in <code>sql/migrations/</code> and are applied in alphabetical order.
                Each successful run is recorded in <code>schema_migrations</code> so it won't run again.
                If you've already applied a file manually (via the mysql CLI), use <strong>Mark applied</strong>
                to record it without re-running the SQL.
            </p>

            <?php
            $pending = array_values(array_filter($available, fn($f) => !isset($applied[$f])));
            if (empty($pending)): ?>
            <div class="admin-empty">All migrations applied. ✓</div>
            <?php else: ?>
                <?php foreach ($pending as $filename): ?>
                <div class="mig-row">
                    <div>
                        <div class="mig-row__name"><?= e($filename) ?></div>
                        <div class="mig-row__meta">Pending</div>
                    </div>
                    <form method="POST" style="margin:0;"
                          onsubmit="return confirm('Mark <?= e($filename) ?> as applied without running its SQL?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action"   value="mark">
                        <input type="hidden" name="filename" value="<?= e($filename) ?>">
                        <button type="submit" class="admin-btn-sm">Mark applied</button>
                    </form>
                    <form method="POST" style="margin:0;"
                          onsubmit="return confirm('Run <?= e($filename) ?> against the database now?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action"   value="apply">
                        <input type="hidden" name="filename" value="<?= e($filename) ?>">
                        <button type="submit" class="admin-btn admin-btn--primary">Apply</button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="admin-section">
            <div class="admin-section__header">
                <h2 class="admin-section__title">
                    Applied
                    <span class="admin-badge admin-badge--gray"><?= count($applied) ?></span>
                </h2>
            </div>
            <?php if (empty($applied)): ?>
            <div class="admin-empty">No migrations recorded yet.</div>
            <?php else:
                // Show in same order they're listed on disk so the audit trail is sequential.
                $appliedOrdered = array_values(array_filter($available, fn($f) => isset($applied[$f])));
                // Plus any rows recorded for files that are no longer on disk.
                $orphans = array_diff(array_keys($applied), $available);
                foreach (array_merge($appliedOrdered, array_values($orphans)) as $filename):
                    $when = $applied[$filename] ?? null;
                    $orphan = !in_array($filename, $available, true);
            ?>
                <div class="mig-row mig-row--applied">
                    <div>
                        <div class="mig-row__name"><?= e($filename) ?> <?= $orphan ? '<span class="admin-badge admin-badge--yellow">file missing</span>' : '' ?></div>
                        <div class="mig-row__meta">Applied <?= $when ? e(date('M j, Y g:i a', strtotime($when))) : '—' ?></div>
                    </div>
                    <div></div>
                    <div></div>
                </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<script src="/admin/js/admin.js"></script>
</body>
</html>
