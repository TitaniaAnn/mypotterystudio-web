<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Screenshots & Banner';
$message   = '';
$msgType   = 'success';

// ── Helpers ────────────────────────────────────────────────────────────────

function handleImageUpload(array $file, string $subdir): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $codes = [1=>'File too large (php.ini)',2=>'File too large (form)',3=>'Partial upload',4=>'No file sent'];
        return ['ok' => false, 'error' => $codes[$file['error']] ?? 'Upload error ' . $file['error']];
    }
    if ($file['size'] > MAX_IMAGE_SIZE) {
        return ['ok' => false, 'error' => 'File too large. Max ' . round(MAX_IMAGE_SIZE / 1048576) . ' MB.'];
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Invalid file type. JPG, PNG, WebP, or GIF only.'];
    }
    $ext  = $allowed[$mime];
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $dir  = UPLOAD_PATH . $subdir . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        return ['ok' => false, 'error' => 'Could not save file to disk.'];
    }
    return ['ok' => true, 'path' => $subdir . '/' . $name];
}

function deleteUploadedFile(string $relativePath): void {
    $abs = UPLOAD_PATH . ltrim($relativePath, '/');
    if ($relativePath && file_exists($abs)) {
        unlink($abs);
    }
}

// ── Handle POST ─────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Upload / replace banner
    if ($action === 'upload_banner' && !empty($_FILES['banner_image']['name'])) {
        $result = handleImageUpload($_FILES['banner_image'], 'banner');
        if ($result['ok']) {
            deleteUploadedFile(setting('hero_banner', ''));
            Database::execute(
                "INSERT INTO settings (setting_key, setting_value) VALUES ('hero_banner', ?)
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$result['path'], $result['path']]
            );
            $message = 'Banner uploaded.';
        } else {
            $message = $result['error'];
            $msgType = 'error';
        }
    }

    // Remove banner
    elseif ($action === 'remove_banner') {
        deleteUploadedFile(setting('hero_banner', ''));
        Database::execute("DELETE FROM settings WHERE setting_key = 'hero_banner'");
        $message = 'Banner removed.';
    }

    // Add screenshot
    elseif ($action === 'add_screenshot' && !empty($_FILES['screenshot_image']['name'])) {
        $result = handleImageUpload($_FILES['screenshot_image'], 'screenshots');
        if ($result['ok']) {
            $caption  = trim($_POST['caption'] ?? '');
            $platform = in_array($_POST['platform'] ?? '', ['ios','android','all']) ? $_POST['platform'] : 'all';
            $maxOrder = Database::fetchOne("SELECT COALESCE(MAX(sort_order),0) AS m FROM screenshots")['m'];
            Database::execute(
                "INSERT INTO screenshots (caption, image_path, platform, sort_order, active)
                 VALUES (?, ?, ?, ?, 1)",
                [$caption, $result['path'], $platform, (int)$maxOrder + 1]
            );
            $message = 'Screenshot added.';
        } else {
            $message = $result['error'];
            $msgType = 'error';
        }
    }

    // Toggle screenshot active state
    elseif ($action === 'toggle' && isset($_POST['id'])) {
        Database::execute("UPDATE screenshots SET active = NOT active WHERE id = ?", [(int)$_POST['id']]);
        $message = 'Screenshot updated.';
    }

    // Delete screenshot
    elseif ($action === 'delete' && isset($_POST['id'])) {
        $row = Database::fetchOne("SELECT image_path FROM screenshots WHERE id = ?", [(int)$_POST['id']]);
        if ($row) {
            deleteUploadedFile($row['image_path']);
            Database::execute("DELETE FROM screenshots WHERE id = ?", [(int)$_POST['id']]);
        }
        $message = 'Screenshot deleted.';
    }

    // Reorder (AJAX)
    elseif ($action === 'reorder' && isset($_POST['order'])) {
        foreach ((array)$_POST['order'] as $pos => $id) {
            Database::execute(
                "UPDATE screenshots SET sort_order = ? WHERE id = ?",
                [(int)$pos + 1, (int)$id]
            );
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}

// ── Load data ───────────────────────────────────────────────────────────────

$heroBanner  = setting('hero_banner', '');
$screenshots = Database::fetchAll("SELECT * FROM screenshots ORDER BY sort_order ASC, id ASC");
$flash       = getFlash();
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
        .sc-banner-preview {
            width: 100%;
            max-width: 600px;
            border-radius: var(--radius);
            border: 2px solid var(--admin-border);
            display: block;
            margin-bottom: 12px;
        }
        .sc-upload-zone {
            border: 2px dashed var(--admin-border);
            border-radius: var(--radius-lg);
            padding: 32px 24px;
            text-align: center;
            background: var(--admin-bg);
            transition: border-color .2s, background .2s;
            cursor: pointer;
        }
        .sc-upload-zone:hover,
        .sc-upload-zone.drag-over {
            border-color: var(--admin-gold);
            background: #fdf9ee;
        }
        .sc-upload-zone input[type=file] { display: none; }
        .sc-upload-zone__icon { font-size: 32px; display: block; margin-bottom: 8px; }
        .sc-upload-zone__text { color: var(--admin-text-lt); font-size: 13px; }
        .sc-upload-zone__label {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 20px;
            background: var(--admin-gold);
            color: #fff;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
        }
        .sc-upload-zone__filename {
            margin-top: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--admin-text);
        }

        /* Screenshots list */
        .sc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .sc-item {
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            position: relative;
        }
        .sc-item.inactive { opacity: .5; }
        .sc-item__img {
            width: 100%;
            aspect-ratio: 9/16;
            object-fit: cover;
            display: block;
            cursor: grab;
        }
        .sc-item__body { padding: 10px 12px; }
        .sc-item__caption {
            font-size: 12px;
            font-weight: 600;
            color: var(--admin-text);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sc-item__meta {
            font-size: 11px;
            color: var(--admin-text-lt);
            margin-bottom: 8px;
        }
        .sc-item__actions { display: flex; gap: 6px; }
        .sc-item__btn {
            flex: 1;
            padding: 5px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid var(--admin-border);
            background: #fff;
            border-radius: var(--radius);
            cursor: pointer;
            color: var(--admin-text);
            text-align: center;
        }
        .sc-item__btn:hover { background: var(--admin-bg); }
        .sc-item__btn--danger { color: #c0392b; border-color: #f5c6c2; }
        .sc-item__btn--danger:hover { background: #fff5f4; }
        .sc-drag-hint {
            font-size: 12px;
            color: var(--admin-text-lt);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }
        .sc-empty {
            text-align: center;
            color: var(--admin-text-lt);
            padding: 40px 0;
            font-size: 14px;
        }
        .sc-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            background: var(--admin-gold);
            color: #fff;
        }
        .sc-badge--off { background: var(--admin-border); color: var(--admin-text-lt); }
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

        <!-- ── Hero Banner ─────────────────────────────────────── -->
        <div class="admin-section">
            <h2 class="admin-section__title">Hero Banner</h2>
            <p style="color:var(--admin-text-lt);font-size:13px;margin-bottom:16px;">
                This image appears in the hero section of the home page (replaces the phone mockup).
                Recommended: landscape, min 900px wide, JPG or PNG.
            </p>
            <div class="admin-card">
                <?php if ($heroBanner): ?>
                <img src="<?= e(UPLOAD_URL . $heroBanner) ?>" alt="Current banner" class="sc-banner-preview">
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
                    <span class="sc-badge">Active</span>
                    <span style="font-size:12px;color:var(--admin-text-lt);align-self:center;"><?= e($heroBanner) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="bannerForm">
                    <input type="hidden" name="action" value="upload_banner">
                    <div class="sc-upload-zone" id="bannerZone">
                        <span class="sc-upload-zone__icon">🖼️</span>
                        <p class="sc-upload-zone__text">
                            <?= $heroBanner ? 'Upload a new banner to replace the current one' : 'Upload your hero banner image' ?>
                        </p>
                        <label class="sc-upload-zone__label" for="banner_image">Choose Image</label>
                        <input type="file" name="banner_image" id="banner_image"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <p class="sc-upload-zone__filename" id="bannerFilename"></p>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:14px;align-items:center;">
                        <button type="submit" class="admin-btn admin-btn--primary" id="bannerSubmit" disabled>
                            Upload Banner
                        </button>
                        <?php if ($heroBanner): ?>
                        <button type="submit" form="removeBannerForm" class="admin-btn"
                                onclick="return confirm('Remove the current banner?')">
                            Remove Banner
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if ($heroBanner): ?>
                <form method="POST" id="removeBannerForm">
                    <input type="hidden" name="action" value="remove_banner">
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── App Screenshots ─────────────────────────────────── -->
        <div class="admin-section">
            <h2 class="admin-section__title">App Screenshots</h2>
            <p style="color:var(--admin-text-lt);font-size:13px;margin-bottom:16px;">
                Screenshots appear in the "See it in action" section of the home page.
                Portrait orientation (9:16) works best. Drag to reorder.
            </p>

            <!-- Add screenshot -->
            <div class="admin-card" style="margin-bottom:20px;">
                <h3 style="font-size:14px;font-weight:700;margin-bottom:14px;">Add Screenshot</h3>
                <form method="POST" enctype="multipart/form-data" id="screenshotForm"
                      style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;">
                    <input type="hidden" name="action" value="add_screenshot">

                    <div>
                        <div class="sc-upload-zone" id="shotZone">
                            <span class="sc-upload-zone__icon">📱</span>
                            <p class="sc-upload-zone__text">Select screenshot image</p>
                            <label class="sc-upload-zone__label" for="screenshot_image">Choose Image</label>
                            <input type="file" name="screenshot_image" id="screenshot_image"
                                   accept="image/jpeg,image/png,image/webp,image/gif">
                            <p class="sc-upload-zone__filename" id="shotFilename"></p>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div class="admin-form__group" style="margin:0;">
                            <label class="admin-form__label" for="caption">Caption <span style="font-weight:400;color:var(--admin-text-lt)">(optional)</span></label>
                            <input class="admin-form__input" type="text" name="caption" id="caption"
                                   placeholder="e.g. Dashboard view" maxlength="255">
                        </div>
                        <div class="admin-form__group" style="margin:0;">
                            <label class="admin-form__label" for="platform">Platform</label>
                            <select class="admin-form__input admin-form__select" name="platform" id="platform">
                                <option value="all">All</option>
                                <option value="ios">iOS</option>
                                <option value="android">Android</option>
                            </select>
                        </div>
                        <button type="submit" class="admin-btn admin-btn--primary" id="shotSubmit" disabled>
                            Add Screenshot
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing screenshots -->
            <div class="admin-card">
                <?php if (empty($screenshots)): ?>
                <div class="sc-empty">
                    <p>📱 No screenshots yet. Add your first one above.</p>
                </div>
                <?php else: ?>
                <div class="sc-drag-hint">
                    <span>⠿</span> Drag screenshots to reorder them
                </div>
                <div class="sc-grid" id="scGrid">
                    <?php foreach ($screenshots as $shot): ?>
                    <div class="sc-item <?= $shot['active'] ? '' : 'inactive' ?>" data-id="<?= (int)$shot['id'] ?>">
                        <img src="<?= e(UPLOAD_URL . $shot['image_path']) ?>"
                             alt="<?= e($shot['caption'] ?? '') ?>"
                             class="sc-item__img" draggable="false">
                        <div class="sc-item__body">
                            <div class="sc-item__caption">
                                <?= $shot['caption'] ? e($shot['caption']) : '<em style="color:var(--admin-text-lt)">No caption</em>' ?>
                            </div>
                            <div class="sc-item__meta">
                                <?= e(ucfirst($shot['platform'])) ?> &middot;
                                <span class="sc-badge <?= $shot['active'] ? '' : 'sc-badge--off' ?>">
                                    <?= $shot['active'] ? 'Visible' : 'Hidden' ?>
                                </span>
                            </div>
                            <div class="sc-item__actions">
                                <form method="POST" style="flex:1">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$shot['id'] ?>">
                                    <button type="submit" class="sc-item__btn" style="width:100%">
                                        <?= $shot['active'] ? 'Hide' : 'Show' ?>
                                    </button>
                                </form>
                                <form method="POST" style="flex:1">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$shot['id'] ?>">
                                    <button type="submit" class="sc-item__btn sc-item__btn--danger"
                                            style="width:100%"
                                            onclick="return confirm('Delete this screenshot?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="/admin/js/admin.js"></script>
<script>
// ── Upload zone file-name feedback ──────────────────────────────────────────
function wireUploadZone(inputId, filenameId, submitId) {
    const input    = document.getElementById(inputId);
    const filename = document.getElementById(filenameId);
    const submit   = document.getElementById(submitId);
    if (!input) return;
    input.addEventListener('change', () => {
        if (input.files.length) {
            filename.textContent = input.files[0].name;
            if (submit) submit.disabled = false;
        } else {
            filename.textContent = '';
            if (submit) submit.disabled = true;
        }
    });
}
wireUploadZone('banner_image',     'bannerFilename', 'bannerSubmit');
wireUploadZone('screenshot_image', 'shotFilename',   'shotSubmit');

// ── Drag-over styling on upload zones ───────────────────────────────────────
['bannerZone','shotZone'].forEach(zoneId => {
    const zone = document.getElementById(zoneId);
    if (!zone) return;
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const input = zone.querySelector('input[type=file]');
        if (input && e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        }
    });
});

// ── Screenshot drag-to-reorder ───────────────────────────────────────────────
(function () {
    const grid = document.getElementById('scGrid');
    if (!grid) return;

    let dragged = null;

    grid.addEventListener('mousedown', e => {
        const item = e.target.closest('.sc-item');
        if (!item || e.target.closest('button') || e.target.closest('form')) return;
        item.style.cursor = 'grabbing';
        dragged = item;
    });

    grid.addEventListener('dragstart', e => {
        const item = e.target.closest('.sc-item');
        if (!item) return;
        dragged = item;
        setTimeout(() => item.classList.add('dragging'), 0);
    });

    grid.addEventListener('dragover', e => {
        e.preventDefault();
        const target = e.target.closest('.sc-item');
        if (!target || target === dragged) return;
        const rect   = target.getBoundingClientRect();
        const midX   = rect.left + rect.width / 2;
        target.parentNode.insertBefore(dragged, e.clientX < midX ? target : target.nextSibling);
    });

    grid.addEventListener('dragend', e => {
        const item = e.target.closest('.sc-item');
        if (item) { item.classList.remove('dragging'); item.style.cursor = ''; }
        saveOrder();
    });

    // Make items draggable
    grid.querySelectorAll('.sc-item').forEach(item => {
        item.setAttribute('draggable', 'true');
    });

    function saveOrder() {
        const ids = [...grid.querySelectorAll('.sc-item')].map(el => el.dataset.id);
        const body = new URLSearchParams();
        body.append('action', 'reorder');
        ids.forEach((id, i) => body.append('order[' + i + ']', id));
        fetch('', { method: 'POST', body });
    }
})();
</script>
</body>
</html>
