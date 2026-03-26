<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
BetaAuth::requireLogin();

header('Content-Type: application/json');

if (!BETA_GITHUB_REPO) {
    echo json_encode(['success' => false, 'error' => 'GitHub repo not configured.', 'data' => []]);
    exit;
}

$state  = in_array($_GET['state'] ?? 'all', ['open','closed','all']) ? $_GET['state'] : 'all';
$issues = GitHubAPI::getIssues(BETA_GITHUB_REPO, $state, BETA_GITHUB_TOKEN);

echo json_encode(['success' => true, 'data' => $issues]);
