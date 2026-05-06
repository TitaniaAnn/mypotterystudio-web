<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
BetaAuth::requireLogin();

header('Content-Type: application/json');

if (!BETA_GITHUB_REPO) {
    echo json_encode(['success' => false, 'error' => 'GitHub repo not configured.', 'data' => []]);
    exit;
}

$requestedState = $_GET['state'] ?? 'all';
$state          = in_array($requestedState, ['open','closed','all'], true) ? $requestedState : 'all';
$issues         = GitHubAPI::getIssues(BETA_GITHUB_REPO, $state, BETA_GITHUB_TOKEN);

echo json_encode(['success' => true, 'data' => $issues]);
