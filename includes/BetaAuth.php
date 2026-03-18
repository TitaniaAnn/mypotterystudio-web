<?php
class BetaAuth {
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

    public static function login(string $email, string $password): bool {
        $user = Database::fetchOne("SELECT * FROM beta_users WHERE email = ? AND approved = 1", [strtolower($email)]);
        if (!$user || !password_verify($password, $user['password_hash'])) return false;
        Database::execute("UPDATE beta_users SET last_login = NOW() WHERE id = ?", [$user['id']]);
        $_SESSION['beta_user_id'] = $user['id'];
        $_SESSION['beta_user']    = [
            'id' => $user['id'], 'name' => $user['name'],
            'email' => $user['email'], 'platform' => $user['platform'],
        ];
        return true;
    }

    public static function logout(): void {
        unset($_SESSION['beta_user_id'], $_SESSION['beta_user']);
    }
}
