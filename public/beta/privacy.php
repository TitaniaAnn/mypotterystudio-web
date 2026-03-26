<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — My Pottery Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Caveat:wght@400;600&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-auth-page">
<div class="beta-auth-wrap">
    <div class="beta-auth-card beta-auth-card--wide">
        <a href="/" class="beta-auth-logo">
            <span class="beta-auth-logo__icon">🏺</span>
            <span class="beta-auth-logo__text">My Pottery Studio</span>
        </a>
        <h1 class="beta-auth-title">Privacy Policy</h1>
        <p class="beta-auth-sub">Last updated: <?= date('F j, Y') ?></p>

        <div class="beta-prose">
            <h2>1. Information We Collect</h2>
            <p>When you register for the My Pottery Studio Beta Program, we collect:</p>
            <ul>
                <li>Your name and email address</li>
                <li>Your device platform preference</li>
                <li>Feedback, bug reports, and feature requests you submit</li>
                <li>Usage data and crash reports from the Beta Software (if applicable)</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <p>We use the information we collect to:</p>
            <ul>
                <li>Manage your beta tester account and provide access to downloads</li>
                <li>Communicate with you about the beta program, updates, and the app launch</li>
                <li>Improve the My Pottery Studio application based on your feedback</li>
                <li>Track and resolve bugs and feature requests</li>
            </ul>

            <h2>3. Information Sharing</h2>
            <p>We do not sell, trade, or otherwise transfer your personal information to third parties. We may share information with service providers who assist us in operating this beta program, provided they agree to keep your information confidential.</p>
            <p>Bug reports and feature requests you submit may be tracked in GitHub Issues. Your name is not publicly associated with these issues unless you choose to include it in the report.</p>

            <h2>4. Data Security</h2>
            <p>We implement appropriate technical and organizational measures to protect your personal information. Passwords are hashed using industry-standard algorithms and are never stored in plain text.</p>

            <h2>5. Data Retention</h2>
            <p>We retain your account information for the duration of the beta program and for a reasonable period thereafter. You may request deletion of your account and associated data at any time by contacting us.</p>

            <h2>6. Your Rights</h2>
            <p>You have the right to access, correct, or delete your personal information. To exercise these rights, please contact us through the beta portal.</p>

            <h2>7. Cookies and Sessions</h2>
            <p>This website uses session cookies necessary for authentication. We do not use third-party tracking cookies or advertising cookies.</p>

            <h2>8. Children's Privacy</h2>
            <p>The Beta Program is intended for adults aged 18 and over. We do not knowingly collect information from children under 13.</p>

            <h2>9. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify registered beta testers of significant changes via email.</p>

            <h2>10. Contact</h2>
            <p>If you have questions or concerns about this Privacy Policy, please contact us through the beta portal.</p>
        </div>

        <div style="margin-top: 32px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="/" class="beta-btn beta-btn--primary">Return to Site</a>
            <a href="/beta/login.php" class="beta-btn beta-btn--outline">Beta Login</a>
        </div>
    </div>
</div>
</body>
</html>
