<?php
class GitHubAPI {
    private static function get(string $url, string $token = ''): array {
        $headers = ['User-Agent: MyPotteryStudio/1.0', 'Accept: application/vnd.github+json'];
        if ($token) $headers[] = "Authorization: Bearer $token";
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 10]);
        $response = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($status !== 200 || !$response) return [];
        return json_decode($response, true) ?? [];
    }

    private static function post(string $url, string $token, array $data): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'User-Agent: MyPotteryStudio/1.0', 'Accept: application/vnd.github+json',
                "Authorization: Bearer $token", 'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch); curl_close($ch);
        return json_decode($response, true) ?? [];
    }

    public static function getIssues(string $repo, string $state = 'all', string $token = ''): array {
        if (!$repo) return [];
        $url = "https://api.github.com/repos/{$repo}/issues?" . http_build_query(['state' => $state, 'per_page' => 100, 'sort' => 'created', 'direction' => 'desc']);
        $issues = self::get($url, $token);
        return array_values(array_filter($issues, fn($i) => empty($i['pull_request'])));
    }

    public static function createIssue(string $repo, string $token, string $title, string $body, array $labels = []): array {
        if (!$repo || !$token) return [];
        return self::post("https://api.github.com/repos/{$repo}/issues", $token, compact('title', 'body', 'labels'));
    }
}
