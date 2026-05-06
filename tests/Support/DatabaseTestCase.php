<?php
namespace MyPotteryStudio\Tests\Support;

use PHPUnit\Framework\TestCase;
use PDO;

/**
 * Base for tests that need a working Database. Spins up an in-memory SQLite
 * connection and injects it into Database::setPdo() for the duration of the
 * test. If pdo_sqlite isn't loaded, every test in the subclass is skipped.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected ?PDO $pdo = null;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped(
                'pdo_sqlite is not enabled. Add `extension=pdo_sqlite` to your php.ini to run DB tests.'
            );
        }

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        $schema = file_get_contents(__DIR__ . '/../fixtures/sqlite_schema.sql');
        $this->pdo->exec($schema);

        \Database::setPdo($this->pdo);

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        \Database::setPdo(null);
        $this->pdo = null;
        $_SESSION = [];
    }
}
