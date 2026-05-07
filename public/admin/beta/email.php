<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$pageTitle = 'Send Email';
$message   = '';
$msgType   = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $subject  = trim($_POST['subject'] ?? '');
    $body     = trim($_POST['body'] ?? '');
    $platform = $_POST['platform'] ?? 'all';

    if (!$subject || !$body) {
        $message = 'Subject and body are required.';
        $msgType = 'error';
    } elseif (!MAIL_HOST || !MAIL_USER || !MAIL_FROM) {
        $message = 'Email is not configured. Set MAIL_HOST / MAIL_USER / MAIL_FROM in .env first.';
        $msgType = 'error';
    } else {
        if ($platform === 'all') {
            $recipients = Database::fetchAll("SELECT email, name FROM beta_users WHERE approved = 1");
        } else {
            $recipients = Database::fetchAll(
                "SELECT email, name FROM beta_users WHERE approved = 1 AND platform = ?",
                [$platform]
            );
        }

        $sent     = 0;
        $failed   = 0;
        $firstErr = null;

        if ($recipients) {
            // One PHPMailer instance, kept alive across the loop so we don't
            // reconnect to SMTP for every recipient. Each addAddress is
            // cleared between sends so testers only see their own address.
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host          = MAIL_HOST;
                $mail->Port          = MAIL_PORT;
                $mail->SMTPAuth      = true;
                $mail->Username      = MAIL_USER;
                $mail->Password      = MAIL_PASS;
                if (MAIL_ENCRYPTION === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif (MAIL_ENCRYPTION === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }
                $mail->SMTPKeepAlive = true;
                $mail->CharSet       = 'UTF-8';
                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                if (MAIL_REPLY_TO) {
                    $mail->addReplyTo(MAIL_REPLY_TO);
                }
                $mail->isHTML(false);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                foreach ($recipients as $r) {
                    try {
                        $mail->clearAddresses();
                        $mail->addAddress($r['email'], $r['name']);
                        $mail->send();
                        $sent++;
                    } catch (MailException $e) {
                        $failed++;
                        if ($firstErr === null) $firstErr = $e->getMessage();
                        error_log("Email to {$r['email']} failed: " . $e->getMessage());
                    }
                }
            } catch (MailException $e) {
                // SMTP setup itself failed — every remaining recipient counts as failed.
                $failed   = count($recipients) - $sent;
                $firstErr = $e->getMessage();
                error_log("Email batch setup failed: " . $e->getMessage());
            } finally {
                try { $mail->smtpClose(); } catch (\Throwable $e) { /* ignore */ }
            }
        }

        Database::insert('beta_emails', [
            'subject' => $subject,
            'body'    => $body,
            'sent_to' => $sent,
        ]);

        if ($failed === 0 && $sent > 0) {
            $message = "Sent to $sent recipient(s).";
            $msgType = 'success';
        } elseif ($sent > 0) {
            $message = "Sent to $sent recipient(s); $failed failed. First error: " . $firstErr;
            $msgType = 'error';
        } elseif ($recipients) {
            $message = "All $failed send(s) failed. Check the PHP error log. First error: " . $firstErr;
            $msgType = 'error';
        } else {
            $message = 'No recipients matched the selected filter.';
            $msgType = 'error';
        }
    }
}

// Load recent sent emails
$sentEmails = Database::fetchAll("SELECT * FROM beta_emails ORDER BY sent_at DESC LIMIT 10");

// Platform counts
$platformCounts = Database::fetchAll(
    "SELECT platform, COUNT(*) as cnt FROM beta_users WHERE approved = 1 GROUP BY platform"
);
$totalApproved = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM beta_users WHERE approved = 1")['c'] ?? 0);
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
        <div class="admin-alert admin-alert--<?= e($msgType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <div class="admin-two-col">
            <!-- Compose Form -->
            <div class="admin-section">
                <h2 class="admin-section__title">Compose Email</h2>
                <div class="admin-card">
                    <?php if (!MAIL_HOST || MAIL_HOST === 'smtp.example.com'): ?>
                    <div class="admin-info-note">
                        ⚠️ <strong>SMTP is not configured.</strong> Set <code>MAIL_HOST</code>, <code>MAIL_USER</code>, <code>MAIL_PASS</code>, and <code>MAIL_FROM</code> in your <code>.env</code> file.
                    </div>
                    <?php endif; ?>
                    <form method="POST" class="admin-form">
                        <?= csrf_field() ?>
                        <div class="admin-form__group">
                            <label class="admin-form__label">Send To</label>
                            <select name="platform" class="admin-form__input admin-form__select">
                                <option value="all">All Approved Testers (<?= $totalApproved ?>)</option>
                                <?php foreach ($platformCounts as $pc): ?>
                                <option value="<?= e($pc['platform']) ?>">
                                    <?= e(ucfirst($pc['platform'])) ?> only (<?= $pc['cnt'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="subject">Subject</label>
                            <input class="admin-form__input" type="text" id="subject" name="subject"
                                value="<?= e($_POST['subject'] ?? '') ?>" required
                                placeholder="Beta Update: My Pottery Studio v1.0.1">
                        </div>
                        <div class="admin-form__group">
                            <label class="admin-form__label" for="body">Message Body</label>
                            <textarea class="admin-form__input admin-form__textarea" id="body" name="body"
                                rows="10" required
                                placeholder="Write your email message here..."><?= e($_POST['body'] ?? '') ?></textarea>
                        </div>
                        <div class="admin-form__actions">
                            <button type="submit" class="admin-btn admin-btn--primary"
                                onclick="return confirm('Send this email to the selected testers?')">
                                Send Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Email History -->
            <div class="admin-section">
                <h2 class="admin-section__title">Email History</h2>
                <?php if (empty($sentEmails)): ?>
                <div class="admin-empty">No emails sent yet.</div>
                <?php else: ?>
                <div class="admin-email-history">
                    <?php foreach ($sentEmails as $email): ?>
                    <div class="admin-email-item">
                        <div class="admin-email-item__subject"><?= e($email['subject']) ?></div>
                        <div class="admin-email-item__meta">
                            Sent to <?= (int)$email['sent_to'] ?> recipient(s) &mdash;
                            <?= date('M j, Y g:i a', strtotime($email['sent_at'])) ?>
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
</body>
</html>
