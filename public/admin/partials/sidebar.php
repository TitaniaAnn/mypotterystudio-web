<?php
$adminUser   = Auth::getUser();
$currentFile = $_SERVER['PHP_SELF'];
function adminNavActive(string $path): string {
    global $currentFile;
    return (strpos($currentFile, $path) !== false) ? 'admin-nav__item--active' : '';
}
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar__header">
        <a href="/admin/dashboard.php" class="admin-sidebar__logo">
            <span class="admin-sidebar__logo-icon">🏺</span>
            <span class="admin-sidebar__logo-text">My Pottery Studio</span>
        </a>
        <div class="admin-sidebar__label">Admin Panel</div>
    </div>

    <nav class="admin-sidebar__nav">
        <a href="/admin/dashboard.php"
            class="admin-nav__item <?= adminNavActive('/admin/dashboard') ?>">
            <span class="admin-nav__icon">📊</span>
            Dashboard
        </a>

        <div class="admin-nav__section">Beta Program</div>

        <a href="/admin/beta/testers.php"
            class="admin-nav__item <?= adminNavActive('/admin/beta/testers') ?>">
            <span class="admin-nav__icon">👥</span>
            Testers
        </a>
        <a href="/admin/beta/feedback.php"
            class="admin-nav__item <?= adminNavActive('/admin/beta/feedback') ?>">
            <span class="admin-nav__icon">💬</span>
            Feedback
        </a>
        <a href="/admin/beta/email.php"
            class="admin-nav__item <?= adminNavActive('/admin/beta/email') ?>">
            <span class="admin-nav__icon">✉️</span>
            Send Email
        </a>

        <div class="admin-nav__section">Site</div>

        <a href="/admin/settings/index.php"
            class="admin-nav__item <?= adminNavActive('/admin/settings/index') ?>">
            <span class="admin-nav__icon">⚙️</span>
            App Settings
        </a>
        <a href="/admin/settings/screenshots.php"
            class="admin-nav__item <?= adminNavActive('/admin/settings/screenshots') ?>">
            <span class="admin-nav__icon">📸</span>
            Screenshots
        </a>
        <a href="/admin/settings/migrations.php"
            class="admin-nav__item <?= adminNavActive('/admin/settings/migrations') ?>">
            <span class="admin-nav__icon">🗄️</span>
            Migrations
        </a>
        <a href="/" target="_blank" class="admin-nav__item">
            <span class="admin-nav__icon">↗</span>
            View Site
        </a>
    </nav>

    <div class="admin-sidebar__footer">
        <?php if ($adminUser): ?>
        <div class="admin-sidebar__user">
            <?php if (!empty($adminUser['avatar'])): ?>
            <img src="<?= e($adminUser['avatar']) ?>" alt="avatar" class="admin-sidebar__avatar">
            <?php else: ?>
            <div class="admin-sidebar__avatar admin-sidebar__avatar--initials">
                <?= strtoupper(substr($adminUser['name'] ?? 'A', 0, 1)) ?>
            </div>
            <?php endif; ?>
            <div class="admin-sidebar__user-info">
                <span class="admin-sidebar__user-name"><?= e($adminUser['name'] ?? '') ?></span>
                <span class="admin-sidebar__user-login">@<?= e($adminUser['login'] ?? '') ?></span>
            </div>
        </div>
        <?php endif; ?>
        <form method="POST" action="/admin/logout.php" class="admin-nav__logout-form" style="margin:0;">
            <?= csrf_field() ?>
            <button type="submit" class="admin-nav__item admin-nav__item--logout"
                    style="background:none;border:none;width:100%;text-align:left;cursor:pointer;font:inherit;color:inherit;">
                <span class="admin-nav__icon">→</span>
                Sign Out
            </button>
        </form>
    </div>
</aside>
