<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Send Email';
$message   = '';
$msgType   = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject  = trim($_POST['subject'] ?? '');
    $body     = trim($_POST['body'] ?? '');
    $platform = $_POST['platform'] ?? 'all';

    if (!$subject || !$body) {
        $message = 'Subject and body are required.';
        $msgType = 'error';
    } else {
        // Count recipients
        if ($platform === 'all') {
            $recipients = Database::fetchAll("SELECT email, name FROM beta_users WHERE approved = 1");
        } else {
            $recipients = Database::fetchAll("SELECT email, name FROM beta_users WHERE approved = 1 AND platform = ?", [$platform]);
        }

        // Record the email send attempt
        Database::insert('beta_emails', [
            'subject' => $subject,
            'body'    => $body,
            'sent_to' => count($recipients),
        ]);

        // TODO: Implement actual email sending via PHPMailer
        // Example integration:
        // foreach ($recipients as $recipient) {
        //     $mail = new PHPMailer\PHPMailer\PHPMailer();
        //     $mail->isSMTP();
        //     $mail->Host       = MAIL_HOST;
        //     $mail->SMTPAuth   = true;
        //     $mail->Username   = MAIL_USER;
        //     $mail->Password   = MAIL_PASS;
        //     $mail->Port       = MAIL_PORT;
        //     $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        //     $mail->addAddress($recipient['email'], $recipient['name']);
        //     $mail->Subject    = $subject;
        //     $mail->Body       = $body;
        //     $mail->send();
        // }

        $message = 'Email recorded for ' . count($recipients) . ' recipient(s). Note: Actual sending requires PHPMailer configuration — see code comments.';
        $msgType = 'success';
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
                    <div class="admin-info-note">
                        ℹ️ Emails are logged in the database. Actual sending requires configuring SMTP settings in your <code>.env</code> file and uncommenting the PHPMailer code in this file.
                    </div>
                    <form method="POST" class="admin-form">
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
