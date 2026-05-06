<?php
class Database {
    private static ?PDO $pdo = null;

    public static function connect(): PDO {
        if (self::$pdo === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function execute(string $sql, array $params = []): int {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $table, array $data): int {
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $stmt = self::connect()->prepare("INSERT INTO $table ($cols) VALUES ($phs)");
        $stmt->execute(array_values($data));
        return (int) self::connect()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $stmt = self::connect()->prepare("UPDATE $table SET $set WHERE $where");
        $stmt->execute(array_merge($data, $whereParams));
        return $stmt->rowCount();
    }

    public static function transaction(callable $fn) {
        $pdo = self::connect();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /** Test seam: inject a custom PDO (used by the test suite). */
    public static function setPdo(?PDO $pdo): void {
        self::$pdo = $pdo;
    }
}
