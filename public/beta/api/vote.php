<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
BetaAuth::requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input      = json_decode(file_get_contents('php://input'), true) ?? [];
$feedbackId = (int)($input['feedback_id'] ?? $_POST['feedback_id'] ?? 0);
$user       = BetaAuth::getUser();

if (!$feedbackId) {
    echo json_encode(['success' => false, 'error' => 'Invalid feedback ID']);
    exit;
}

$feedback = Database::fetchOne("SELECT id, votes FROM beta_feedback WHERE id = ?", [$feedbackId]);
if (!$feedback) {
    echo json_encode(['success' => false, 'error' => 'Feedback not found']);
    exit;
}

// Toggle vote
$existing = Database::fetchOne(
    "SELECT id FROM beta_votes WHERE feedback_id = ? AND user_id = ?",
    [$feedbackId, $user['id']]
);

if ($existing) {
    // Remove vote
    Database::execute("DELETE FROM beta_votes WHERE feedback_id = ? AND user_id = ?", [$feedbackId, $user['id']]);
    Database::execute("UPDATE beta_feedback SET votes = GREATEST(0, votes - 1) WHERE id = ?", [$feedbackId]);
    $voted = false;
} else {
    // Add vote
    try {
        Database::insert('beta_votes', ['feedback_id' => $feedbackId, 'user_id' => $user['id']]);
        Database::execute("UPDATE beta_feedback SET votes = votes + 1 WHERE id = ?", [$feedbackId]);
        $voted = true;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Could not register vote']);
        exit;
    }
}

$updated = Database::fetchOne("SELECT votes FROM beta_feedback WHERE id = ?", [$feedbackId]);

echo json_encode([
    'success' => true,
    'voted'   => $voted,
    'votes'   => (int)$updated['votes'],
]);
