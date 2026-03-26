<?php
$adminUser = Auth::getUser();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<header class="admin-topbar">
    <div class="admin-topbar__left">
        <button class="admin-topbar__menu-btn" id="sidebarToggle" aria-label="Toggle sidebar">
            <span></span><span></span><span></span>
        </button>
        <h1 class="admin-topbar__title"><?= e($pageTitle) ?></h1>
    </div>
    <div class="admin-topbar__right">
        <a href="/" target="_blank" class="admin-topbar__link">View Site ↗</a>
        <?php if ($adminUser): ?>
        <div class="admin-topbar__user">
            <?php if (!empty($adminUser['avatar'])): ?>
            <img src="<?= e($adminUser['avatar']) ?>" alt="avatar" class="admin-topbar__avatar">
            <?php else: ?>
            <div class="admin-topbar__avatar admin-topbar__avatar--initials">
                <?= strtoupper(substr($adminUser['name'] ?? 'A', 0, 1)) ?>
            </div>
            <?php endif; ?>
            <span class="admin-topbar__user-name"><?= e($adminUser['name'] ?? '') ?></span>
        </div>
        <?php endif; ?>
    </div>
</header>
