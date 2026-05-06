<?php
class Auth {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'httponly' => true,
                'secure'   => isset($_SERVER['HTTPS']),
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function isLoggedIn(): bool {
        self::start();
        return !empty($_SESSION['admin_id']);
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . SITE_URL . '/admin/login.php');
            exit;
        }
    }

    public static function getUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return $_SESSION['admin_user'] ?? null;
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }
        session_destroy();
    }

    public static function getGitHubAuthUrl(): string {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        return 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id'    => GITHUB_CLIENT_ID,
            'redirect_uri' => GITHUB_REDIRECT_URI,
            'scope'        => 'read:user',
            'state'        => $state,
        ]);
    }

    /**
     * Handles the GitHub OAuth callback. Returns one of:
     *   'ok'              — login successful
     *   'state_mismatch'  — CSRF state did not match
     *   'not_allowed'     — user is not in the GitHub allowlist
     *   'auth_failed'     — token exchange or user fetch failed
     */
    public static function handleGitHubCallback(string $code, string $state): string {
        if ($state !== ($_SESSION['oauth_state'] ?? '')) return 'state_mismatch';
        unset($_SESSION['oauth_state']);

        $tokenData = self::httpPost('https://github.com/login/oauth/access_token', [
            'client_id'     => GITHUB_CLIENT_ID,
            'client_secret' => GITHUB_CLIENT_SECRET,
            'code'          => $code,
            'redirect_uri'  => GITHUB_REDIRECT_URI,
        ], ['Accept: application/json']);

        if (empty($tokenData['access_token'])) return 'auth_failed';

        $githubUser = self::httpGet('https://api.github.com/user', $tokenData['access_token']);
        if (empty($githubUser['login'])) return 'auth_failed';

        return self::loginUser($githubUser) ? 'ok' : 'not_allowed';
    }

    private static function loginUser(array $githubUser): bool {
        $allowed = array_filter(array_map('trim', explode(',', ALLOWED_GITHUB_USERS)));
        if (!in_array($githubUser['login'], $allowed, true)) return false;

        $existing = Database::fetchOne("SELECT id FROM admin_users WHERE github_id = ?", [$githubUser['id']]);
        $name   = $githubUser['name'] ?? $githubUser['login'];
        $avatar = $githubUser['avatar_url'] ?? null;
        $email  = $githubUser['email'] ?? ($githubUser['login'] . '@github');

        if ($existing) {
            Database::update('admin_users', [
                'name' => $name, 'avatar_url' => $avatar, 'last_login' => date('Y-m-d H:i:s'),
            ], 'github_id = :github_id', ['github_id' => $githubUser['id']]);
            $userId = $existing['id'];
        } else {
            $userId = Database::insert('admin_users', [
                'github_id' => $githubUser['id'], 'email' => $email,
                'name' => $name, 'avatar_url' => $avatar,
            ]);
        }

        self::start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['admin_id']   = $userId;
        $_SESSION['admin_user'] = [
            'id' => $userId, 'login' => $githubUser['login'],
            'name' => $name, 'email' => $email, 'avatar' => $avatar,
        ];
        return true;
    }

    private static function httpPost(string $url, array $data, array $extraHeaders = []): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_merge(
                ['Content-Type: application/x-www-form-urlencoded', 'User-Agent: MyPotteryStudio/1.0'],
                $extraHeaders
            ),
        ]);
        $response = curl_exec($ch); curl_close($ch);
        return json_decode($response, true) ?? [];
    }

    private static function httpGet(string $url, string $token): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token", 'User-Agent: MyPotteryStudio/1.0', 'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch); curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
