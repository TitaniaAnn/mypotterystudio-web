<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
BetaAuth::requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

verify_csrf(true);

$input      = json_decode(file_get_contents('php://input'), true) ?? [];
$feedbackId = (int)($input['feedback_id'] ?? $_POST['feedback_id'] ?? 0);
$user       = BetaAuth::getUser();

if (!$feedbackId) {
    echo json_encode(['success' => false, 'error' => 'Invalid feedback ID']);
    exit;
}

try {
    $result = Database::transaction(function () use ($feedbackId, $user) {
        $exists = Database::fetchOne("SELECT id FROM beta_feedback WHERE id = ?", [$feedbackId]);
        if (!$exists) return ['error' => 'Feedback not found'];

        $existingVote = Database::fetchOne(
            "SELECT id FROM beta_votes WHERE feedback_id = ? AND user_id = ?",
            [$feedbackId, $user['id']]
        );

        if ($existingVote) {
            Database::execute(
                "DELETE FROM beta_votes WHERE feedback_id = ? AND user_id = ?",
                [$feedbackId, $user['id']]
            );
            $voted = false;
        } else {
            Database::execute(
                "INSERT IGNORE INTO beta_votes (feedback_id, user_id) VALUES (?, ?)",
                [$feedbackId, $user['id']]
            );
            $voted = true;
        }

        $count = (int) Database::fetchOne(
            "SELECT COUNT(*) AS c FROM beta_votes WHERE feedback_id = ?",
            [$feedbackId]
        )['c'];
        Database::execute("UPDATE beta_feedback SET votes = ? WHERE id = ?", [$count, $feedbackId]);

        return ['voted' => $voted, 'votes' => $count];
    });
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not register vote']);
    exit;
}

if (isset($result['error'])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => $result['error']]);
    exit;
}

echo json_encode([
    'success' => true,
    'voted'   => $result['voted'],
    'votes'   => $result['votes'],
]);
