<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
BetaAuth::requireLogin();

header('Content-Type: application/json');

$user = BetaAuth::getUser();

$feedback = Database::fetchAll(
    "SELECT bf.*, bu.name as submitter_name,
        (SELECT 1 FROM beta_votes bv WHERE bv.feedback_id = bf.id AND bv.user_id = ?) as user_voted
     FROM beta_feedback bf
     LEFT JOIN beta_users bu ON bf.user_id = bu.id
     ORDER BY bf.votes DESC, bf.created_at DESC",
    [$user['id']]
);

echo json_encode(['success' => true, 'data' => $feedback]);
