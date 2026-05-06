<?php
class BetaAuth {
    /** Max failed attempts per email within LOGIN_WINDOW seconds. */
    const LOGIN_MAX_ATTEMPTS = 5;
    const LOGIN_WINDOW       = 900; // 15 minutes

    public static function isLoggedIn(): bool {
        return !empty($_SESSION['beta_user_id']);
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . SITE_URL . '/beta/login.php');
            exit;
        }
    }

    public static function getUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return $_SESSION['beta_user'] ?? null;
    }

    /**
     * Returns one of:
     *   'ok'             — login succeeded
     *   'invalid'        — wrong credentials or unapproved
     *   'rate_limited'   — too many recent failures
     */
    public static function attemptLogin(string $email, string $password, ?string $ip = null): string {
        $email = strtolower(trim($email));
        if (self::isRateLimited($email)) return 'rate_limited';

        $user = Database::fetchOne(
            "SELECT * FROM beta_users WHERE email = ? AND approved = 1",
            [$email]
        );
        $ok = $user && password_verify($password, $user['password_hash']);
        self::recordAttempt($email, $ip, $ok);
        if (!$ok) return 'invalid';

        Database::execute(
            "UPDATE beta_users SET last_login = ? WHERE id = ?",
            [date('Y-m-d H:i:s'), $user['id']]
        );
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['beta_user_id'] = $user['id'];
        $_SESSION['beta_user']    = [
            'id' => $user['id'], 'name' => $user['name'],
            'email' => $user['email'], 'platform' => $user['platform'],
        ];
        return 'ok';
    }

    /** Backwards-compatible boolean wrapper (no rate-limit signal). */
    public static function login(string $email, string $password): bool {
        return self::attemptLogin($email, $password) === 'ok';
    }

    public static function isRateLimited(string $email): bool {
        // Portable check: fetch the most recent N failed attempts; if the
        // oldest of those is within LOGIN_WINDOW seconds, we're rate-limited.
        // strtotime() interprets DB strings in the PHP timezone, which
        // matches MySQL's session timezone on default shared hosting and
        // matches SQLite's UTC when tests set date_default_timezone_set('UTC').
        $rows = Database::fetchAll(
            "SELECT attempted_at FROM login_attempts
             WHERE email = ? AND successful = 0
             ORDER BY id DESC LIMIT " . (int)self::LOGIN_MAX_ATTEMPTS,
            [strtolower($email)]
        );
        if (count($rows) < self::LOGIN_MAX_ATTEMPTS) return false;
        $oldest = strtotime(end($rows)['attempted_at']);
        return $oldest !== false && (time() - $oldest) < self::LOGIN_WINDOW;
    }

    private static function recordAttempt(string $email, ?string $ip, bool $successful): void {
        Database::insert('login_attempts', [
            'email'      => $email,
            'ip'         => $ip,
            'successful' => $successful ? 1 : 0,
        ]);
    }

    public static function logout(): void {
        unset($_SESSION['beta_user_id'], $_SESSION['beta_user']);
    }
}
