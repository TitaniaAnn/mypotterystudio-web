<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$siteName    = setting('site_name', 'My Pottery Studio');
$heroTitle   = setting('hero_title', 'My Pottery Studio');
$heroSub     = setting('hero_subtitle', 'Track your clay, glazes, kilns, and creations — all in one beautiful app.');
$aboutText   = setting('about_text', 'My Pottery Studio is designed by a potter, for potters.');
$iosUrl      = setting('ios_url', '');
$androidUrl  = setting('android_url', '');
$betaOpen    = setting('beta_open', '1');

$heroBanner  = setting('hero_banner', '');
$features    = Database::fetchAll("SELECT * FROM app_features WHERE active = 1 ORDER BY sort_order ASC");
$screenshots = Database::fetchAll("SELECT * FROM screenshots WHERE active = 1 ORDER BY sort_order ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteName) ?> — The Studio App for Potters</title>
    <meta name="description" content="<?= e($heroSub) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Caveat:wght@400;600&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<?php include __DIR__ . '/templates/nav.php'; ?>

<!-- Hero -->
<section class="hero <?= $heroBanner ? 'hero--has-banner' : '' ?>" id="home">
    <div class="hero__bg-pattern"></div>
    <div class="container">
        <div class="hero__content">
            <div class="hero__badge">
                <span class="hero__badge-dot"></span>
                Now in Beta
            </div>
            <h1 class="hero__title"><?= e($heroTitle) ?></h1>
            <p class="hero__subtitle"><?= e($heroSub) ?></p>
            <div class="hero__ctas">
                <?php if ($iosUrl): ?>
                    <a href="<?= e($iosUrl) ?>" class="btn btn--primary btn--large">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                        Download on App Store
                    </a>
                <?php else: ?>
                    <a href="#" class="btn btn--primary btn--large btn--disabled" aria-disabled="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                        App Store — Coming Soon
                    </a>
                <?php endif; ?>
                <?php if ($betaOpen): ?>
                    <a href="/beta/register.php" class="btn btn--outline btn--large">
                        Join the Beta
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                <?php endif; ?>
            </div>
            <div class="hero__scroll-hint">
                <span>Explore features</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
            </div>
        </div>
        <div class="hero__mockup">
        <?php if ($heroBanner): ?>
        <img src="<?= e(UPLOAD_URL . $heroBanner) ?>"
             alt="<?= e($siteName) ?> app preview"
             class="hero__banner-img">
        <?php else: ?>
        <div class="hero__phone">
            <div class="hero__phone-screen">
                <div class="hero__phone-ui">
                    <div class="phone-ui-header">
                        <span class="phone-ui-title">My Pottery Studio</span>
                        <span class="phone-ui-icon">🏺</span>
                    </div>
                    <div class="phone-ui-stats">
                        <div class="phone-stat">
                            <span class="phone-stat__num">24</span>
                            <span class="phone-stat__label">Pieces</span>
                        </div>
                        <div class="phone-stat">
                            <span class="phone-stat__num">8</span>
                            <span class="phone-stat__label">Firings</span>
                        </div>
                        <div class="phone-stat">
                            <span class="phone-stat__num">12</span>
                            <span class="phone-stat__label">Glazes</span>
                        </div>
                    </div>
                    <div class="phone-ui-items">
                        <div class="phone-item">
                            <span class="phone-item__dot phone-item__dot--gold"></span>
                            <span>Yunomi — In Glaze Firing</span>
                        </div>
                        <div class="phone-item">
                            <span class="phone-item__dot phone-item__dot--green"></span>
                            <span>Bowl Set — Complete</span>
                        </div>
                        <div class="phone-item">
                            <span class="phone-item__dot phone-item__dot--blue"></span>
                            <span>Vase — Bisque Stage</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        </div><!-- /.hero__mockup -->
    </div><!-- /.container -->
</section>

<!-- Features -->
<section class="section features" id="features">
    <div class="container">
        <div class="section__header">
            <span class="section__eyebrow">Everything you need</span>
            <h2 class="section__title">Features Built for the Studio</h2>
            <p class="section__subtitle">Every tool you need to manage your pottery practice, from raw clay to finished piece.</p>
        </div>
        <div class="features-grid">
            <?php foreach ($features as $feature): ?>
            <div class="feature-card">
                <div class="feature-card__icon"><?= e($feature['icon']) ?></div>
                <h3 class="feature-card__title"><?= e($feature['title']) ?></h3>
                <p class="feature-card__desc"><?= e($feature['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- About Strip -->
<section class="about-strip">
    <div class="container about-strip__inner">
        <div class="about-strip__text">
            <span class="about-strip__script">Built for potters,</span>
            <h2 class="about-strip__title">by a potter.</h2>
            <p class="about-strip__body"><?= e($aboutText) ?></p>
            <a href="/beta/register.php" class="btn btn--dark">Get Early Access</a>
        </div>
        <div class="about-strip__visual">
            <div class="about-pottery-icon">🏺</div>
            <div class="about-detail about-detail--1">🌡️</div>
            <div class="about-detail about-detail--2">🎨</div>
            <div class="about-detail about-detail--3">📸</div>
        </div>
    </div>
</section>

<!-- Screenshots -->
<section class="section screenshots" id="screenshots">
    <div class="container">
        <div class="section__header">
            <span class="section__eyebrow">See it in action</span>
            <h2 class="section__title">Screenshots</h2>
        </div>
        <div class="screenshots-row">
            <?php if (empty($screenshots)): ?>
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="screenshot-placeholder">
                    <div class="screenshot-placeholder__inner">
                        <span class="screenshot-placeholder__icon">📱</span>
                        <span class="screenshot-placeholder__text">Screenshot Coming Soon</span>
                    </div>
                </div>
                <?php endfor; ?>
            <?php else: ?>
                <?php foreach ($screenshots as $shot): ?>
                <div class="screenshot-item">
                    <img src="<?= e(UPLOAD_URL . $shot['image_path']) ?>" alt="<?= e($shot['caption'] ?? '') ?>" loading="lazy">
                    <?php if ($shot['caption']): ?>
                    <p class="screenshot-item__caption"><?= e($shot['caption']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Beta CTA -->
<?php if ($betaOpen): ?>
<section class="section beta-cta">
    <div class="container">
        <div class="beta-cta__box">
            <span class="beta-cta__badge">Limited spots available</span>
            <h2 class="beta-cta__title">Join the Beta</h2>
            <p class="beta-cta__text">Be among the first potters to use My Pottery Studio. Help shape the app with your feedback and get free access when we launch.</p>
            <a href="/beta/register.php" class="btn btn--primary btn--large">Apply for Beta Access</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/templates/footer.php'; ?>

<script src="/js/main.js"></script>
</body>
</html>
