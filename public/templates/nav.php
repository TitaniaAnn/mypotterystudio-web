<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="nav" id="nav">
    <div class="nav__inner container">
        <a href="/" class="nav__logo">
            <span class="nav__logo-icon">🏺</span>
            <span>My Pottery Studio</span>
        </a>
        <div class="nav__links">
            <a href="/#features" class="nav__link">Features</a>
            <a href="/#screenshots" class="nav__link">Screenshots</a>
            <a href="/beta/register.php" class="nav__link nav__link--cta">Join Beta</a>
        </div>
    </div>
</nav>
