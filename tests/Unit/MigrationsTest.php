<?php
namespace MyPotteryStudio\Tests\Unit;

use MyPotteryStudio\Tests\Support\DatabaseTestCase;
use Database;
use Migrations;
use RuntimeException;

class MigrationsTest extends DatabaseTestCase
{
    /**
     * The runner reads from the real sql/migrations/ directory. To avoid
     * touching production migrations or mocking the class, each test
     * writes uniquely-prefixed `zzz_test_*.sql` files into that directory
     * and removes them in tearDown.
     */
    private array $createdRealFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdRealFiles as $path) {
            if (file_exists($path)) unlink($path);
        }
        $this->createdRealFiles = [];
        parent::tearDown();
    }

    private function makeMigration(string $filename, string $sql): string
    {
        $path = Migrations::migrationsDir() . '/' . $filename;
        file_put_contents($path, $sql);
        $this->createdRealFiles[] = $path;
        return $filename;
    }

    private function cleanupRealFiles(): void
    {
        foreach ($this->createdRealFiles as $path) {
            if (file_exists($path)) unlink($path);
        }
        $this->createdRealFiles = [];
    }

    // ── splitStatements ──────────────────────────────────────────────────

    public function testSplitStatementsSplitsOnSemicolons(): void
    {
        $sql = "CREATE TABLE a (id INT); CREATE TABLE b (id INT);";
        $this->assertSame(
            ['CREATE TABLE a (id INT)', 'CREATE TABLE b (id INT)'],
            Migrations::splitStatements($sql)
        );
    }

    public function testSplitStatementsStripsLineComments(): void
    {
        $sql = "-- this is a comment with ; in it\nCREATE TABLE a (id INT);";
        $this->assertSame(['CREATE TABLE a (id INT)'], Migrations::splitStatements($sql));
    }

    public function testSplitStatementsStripsBlockComments(): void
    {
        $sql = "/* multi\nline; with semis */\nCREATE TABLE a (id INT);";
        $this->assertSame(['CREATE TABLE a (id INT)'], Migrations::splitStatements($sql));
    }

    public function testSplitStatementsIgnoresEmptyTrailing(): void
    {
        $this->assertSame(['SELECT 1'], Migrations::splitStatements("SELECT 1; ; \n"));
    }

    public function testSplitStatementsHandlesEmptyInput(): void
    {
        $this->assertSame([], Migrations::splitStatements(''));
        $this->assertSame([], Migrations::splitStatements("-- only a comment\n"));
    }

    // ── ensureTable & applied ─────────────────────────────────────────────

    public function testEnsureTableCreatesTrackingTable(): void
    {
        // Drop it first if it exists from sqlite_schema.sql
        $this->pdo->exec('DROP TABLE IF EXISTS schema_migrations');
        Migrations::ensureTable();
        // Should not throw — table exists.
        $this->assertSame([], Migrations::applied());
    }

    public function testAppliedReturnsRecordedFiles(): void
    {
        Migrations::ensureTable();
        Database::insert('schema_migrations', ['filename' => '999_x.sql']);
        $applied = Migrations::applied();
        $this->assertArrayHasKey('999_x.sql', $applied);
    }

    // ── apply ─────────────────────────────────────────────────────────────

    public function testApplyRunsSqlAndRecordsFile(): void
    {
        $f = $this->makeMigration(
            'zzz_test_create_widget.sql',
            "CREATE TABLE widget (id INTEGER PRIMARY KEY, name TEXT);\nINSERT INTO widget (name) VALUES ('hello');"
        );
        try {
            $count = Migrations::apply($f);
            $this->assertSame(2, $count);

            $row = Database::fetchOne("SELECT name FROM widget LIMIT 1");
            $this->assertSame('hello', $row['name']);

            $this->assertArrayHasKey($f, Migrations::applied());
        } finally {
            $this->pdo->exec('DROP TABLE IF EXISTS widget');
            $this->cleanupRealFiles();
        }
    }

    public function testApplyRejectsAlreadyAppliedFile(): void
    {
        $f = $this->makeMigration('zzz_test_dup.sql', 'SELECT 1;');
        try {
            Migrations::apply($f);
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('already applied');
            Migrations::apply($f);
        } finally {
            $this->cleanupRealFiles();
        }
    }

    public function testApplyRejectsMissingFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not found');
        Migrations::apply('zzz_does_not_exist.sql');
    }

    public function testApplyRejectsUnsafeFilename(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid migration filename');
        Migrations::apply('../etc/passwd');
    }

    public function testApplyDoesNotRecordOnFailure(): void
    {
        $f = $this->makeMigration(
            'zzz_test_bad.sql',
            "CREATE TABLE good_table (id INTEGER PRIMARY KEY); THIS IS NOT VALID SQL;"
        );
        try {
            try {
                Migrations::apply($f);
                $this->fail('Expected RuntimeException');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('failed at statement 2', $e->getMessage());
            }
            $this->assertArrayNotHasKey($f, Migrations::applied(),
                'Failed migration must not be recorded');
        } finally {
            $this->pdo->exec('DROP TABLE IF EXISTS good_table');
            $this->cleanupRealFiles();
        }
    }

    public function testApplyRejectsEmptyFile(): void
    {
        $f = $this->makeMigration('zzz_test_empty.sql', "-- nothing here\n");
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('empty');
            Migrations::apply($f);
        } finally {
            $this->cleanupRealFiles();
        }
    }

    // ── markApplied ───────────────────────────────────────────────────────

    public function testMarkAppliedRecordsWithoutRunning(): void
    {
        $f = $this->makeMigration(
            'zzz_test_mark.sql',
            "CREATE TABLE should_not_be_created (id INTEGER PRIMARY KEY);"
        );
        try {
            Migrations::markApplied($f);
            $this->assertArrayHasKey($f, Migrations::applied());

            // Verify the table was NOT actually created.
            $row = $this->pdo->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name='should_not_be_created'"
            )->fetch();
            $this->assertFalse($row, 'markApplied must not run the SQL');
        } finally {
            $this->cleanupRealFiles();
        }
    }

    public function testMarkAppliedRejectsAlreadyMarked(): void
    {
        $f = $this->makeMigration('zzz_test_dup_mark.sql', 'SELECT 1;');
        try {
            Migrations::markApplied($f);
            $this->expectException(RuntimeException::class);
            Migrations::markApplied($f);
        } finally {
            $this->cleanupRealFiles();
        }
    }

    // ── pending ───────────────────────────────────────────────────────────

    public function testPendingExcludesApplied(): void
    {
        $a = $this->makeMigration('zzz_test_pa.sql', 'SELECT 1;');
        $b = $this->makeMigration('zzz_test_pb.sql', 'SELECT 1;');
        try {
            Migrations::markApplied($a);
            $pending = Migrations::pending();
            $this->assertContains($b, $pending);
            $this->assertNotContains($a, $pending);
        } finally {
            $this->cleanupRealFiles();
        }
    }
}
