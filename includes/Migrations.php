<?php
/**
 * Migrations runner. Files live in sql/migrations/ and are applied in
 * lexicographic order. Each file's filename is recorded in
 * schema_migrations after a successful apply.
 *
 * Constraints on migration SQL:
 *   - Statements separated by `;`
 *   - No `;` inside string literals (DDL doesn't need them; data migrations
 *     should use parameterised inserts via a different path)
 *   - `--` line comments and / * block comments * / are stripped before splitting
 *   - DDL is not transactional in MySQL — partial failure leaves the schema
 *     in a half-applied state and the file is NOT marked applied. Fix the
 *     SQL and retry.
 */
class Migrations {
    const TABLE = 'schema_migrations';

    public static function migrationsDir(): string {
        return dirname(__DIR__) . '/sql/migrations';
    }

    /** Returns sorted list of all .sql filenames in sql/migrations/. */
    public static function available(): array {
        $dir = self::migrationsDir();
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/*.sql') ?: [];
        $names = array_map('basename', $files);
        sort($names, SORT_STRING);
        return $names;
    }

    /** Returns map of filename => applied_at for migrations already recorded. */
    public static function applied(): array {
        self::ensureTable();
        $rows = Database::fetchAll('SELECT filename, applied_at FROM ' . self::TABLE);
        return array_column($rows, 'applied_at', 'filename');
    }

    /** Returns sorted list of available migrations not yet applied. */
    public static function pending(): array {
        $applied = self::applied();
        return array_values(array_filter(
            self::available(),
            fn($name) => !isset($applied[$name])
        ));
    }

    /**
     * Run a migration file end-to-end and record it on success. Throws on
     * failure (with the offending statement in the message).
     * Returns the number of statements executed.
     */
    public static function apply(string $filename): int {
        self::assertSafeFilename($filename);
        $path = self::migrationsDir() . '/' . $filename;
        if (!is_file($path)) {
            throw new RuntimeException("Migration file not found: $filename");
        }
        if (isset(self::applied()[$filename])) {
            throw new RuntimeException("Migration already applied: $filename");
        }

        $sql        = file_get_contents($path);
        $statements = self::splitStatements($sql);
        if (empty($statements)) {
            throw new RuntimeException("Migration is empty: $filename");
        }

        $pdo = Database::connect();
        $i   = 0;
        foreach ($statements as $statement) {
            $i++;
            try {
                $pdo->exec($statement);
            } catch (Throwable $e) {
                throw new RuntimeException(
                    "Migration $filename failed at statement $i of " . count($statements)
                    . ": " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        Database::insert(self::TABLE, ['filename' => $filename]);
        return $i;
    }

    /**
     * Record a migration as applied without running its SQL. Use when the
     * file was applied manually (e.g. via the mysql CLI) before the runner
     * existed.
     */
    public static function markApplied(string $filename): void {
        self::assertSafeFilename($filename);
        $path = self::migrationsDir() . '/' . $filename;
        if (!is_file($path)) {
            throw new RuntimeException("Migration file not found: $filename");
        }
        self::ensureTable();
        if (isset(self::applied()[$filename])) {
            throw new RuntimeException("Migration already marked applied: $filename");
        }
        Database::insert(self::TABLE, ['filename' => $filename]);
    }

    /**
     * Strips comments and splits on `;`. Strings containing `;` are not
     * supported — see class doc.
     */
    public static function splitStatements(string $sql): array {
        $stripped = preg_replace('!/\*.*?\*/!s', '', $sql);                 // /* block */
        $stripped = preg_replace('/(^|\s)--[^\n]*/', '$1', $stripped ?? ''); // -- line
        $parts    = array_map('trim', explode(';', $stripped ?? ''));
        return array_values(array_filter($parts, fn($s) => $s !== ''));
    }

    public static function ensureTable(): void {
        // Cross-driver IF NOT EXISTS — MySQL InnoDB and SQLite both accept this.
        Database::connect()->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' ('
            . ' filename VARCHAR(255) PRIMARY KEY,'
            . ' applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
            . ')'
        );
    }

    /** Defence-in-depth: filename must look like our migration files. */
    private static function assertSafeFilename(string $filename): void {
        if (!preg_match('/^[A-Za-z0-9_.-]+\.sql$/', $filename) || str_contains($filename, '..')) {
            throw new RuntimeException("Invalid migration filename: $filename");
        }
    }
}
