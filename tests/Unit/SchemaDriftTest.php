<?php
namespace MyPotteryStudio\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards against the prod (sql/schema.sql) and test (sqlite_schema.sql)
 * schemas drifting on column lists. This is a smoke test, not a full
 * structural diff — we don't compare column types because they
 * intentionally differ (TIMESTAMP vs TEXT, ENUM vs TEXT, INT vs INTEGER).
 *
 * If a CREATE TABLE statement appears in both files for the same table
 * name, both must declare the same set of column names.
 */
class SchemaDriftTest extends TestCase
{
    public function testSharedTablesHaveSameColumnNames(): void
    {
        $prod = $this->parseTables(__DIR__ . '/../../sql/schema.sql');
        $test = $this->parseTables(__DIR__ . '/../fixtures/sqlite_schema.sql');

        $shared = array_intersect(array_keys($prod), array_keys($test));
        $this->assertNotEmpty($shared, 'Expected at least one shared table');

        foreach ($shared as $table) {
            sort($prod[$table]);
            sort($test[$table]);
            $this->assertSame(
                $prod[$table],
                $test[$table],
                "Column list for `$table` differs between sql/schema.sql and tests/fixtures/sqlite_schema.sql. "
                . "Update whichever is stale."
            );
        }
    }

    public function testEverySharedTableIsActuallyShared(): void
    {
        $prod = $this->parseTables(__DIR__ . '/../../sql/schema.sql');
        $test = $this->parseTables(__DIR__ . '/../fixtures/sqlite_schema.sql');

        // Tables in prod but not in the test fixture — fine if the test
        // suite genuinely doesn't need them, but flag known omissions.
        $prodOnly  = array_diff(array_keys($prod), array_keys($test));
        $testOnly  = array_diff(array_keys($test), array_keys($prod));

        $this->assertSame(
            [],
            array_values($testOnly),
            'Tables in test fixture but not in production schema: ' . implode(', ', $testOnly)
        );
        // We assert prod-only is also empty: the test fixture should mirror
        // every production table so coverage stays honest.
        $this->assertSame(
            [],
            array_values($prodOnly),
            'Tables in production schema but missing from test fixture: ' . implode(', ', $prodOnly)
        );
    }

    /**
     * Returns map of table_name => list of column names. Column names are
     * the first identifier on each non-blank, non-keyword line inside the
     * CREATE TABLE body.
     */
    private function parseTables(string $path): array
    {
        $sql = file_get_contents($path);
        $this->assertNotFalse($sql, "Could not read $path");

        // Strip line comments to keep the parser simple.
        $sql = preg_replace('/--[^\n]*/', '', $sql);

        $tables = [];
        if (!preg_match_all(
            '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?([A-Za-z0-9_]+)[`"]?\s*\((.*?)\)\s*(?:ENGINE\s*=\s*\w+)?\s*;/is',
            $sql,
            $matches,
            PREG_SET_ORDER
        )) {
            return $tables;
        }

        foreach ($matches as $m) {
            $tableName = $m[1];
            $body      = $m[2];

            $columns = [];
            // Split body by *top-level* commas only — INDEX (a, b) and
            // FOREIGN KEY (col) REFERENCES t(col) have commas inside parens.
            foreach ($this->splitTopLevelCommas($body) as $line) {
                $line = trim($line);
                if ($line === '') continue;

                // Skip table-level constraints (PRIMARY KEY (...), FOREIGN KEY (...), etc.)
                $upper = strtoupper($line);
                $skip  = ['PRIMARY KEY', 'FOREIGN KEY', 'UNIQUE KEY', 'UNIQUE (', 'INDEX ', 'KEY ', 'CONSTRAINT ', 'CHECK '];
                $isConstraint = false;
                foreach ($skip as $prefix) {
                    if (str_starts_with($upper, $prefix)) { $isConstraint = true; break; }
                }
                if ($isConstraint) continue;

                // First identifier on the line is the column name.
                if (preg_match('/^[`"]?([A-Za-z0-9_]+)[`"]?/', $line, $cm)) {
                    $columns[] = $cm[1];
                }
            }
            $tables[$tableName] = $columns;
        }
        return $tables;
    }

    private function splitTopLevelCommas(string $body): array
    {
        $parts = [];
        $buf   = '';
        $depth = 0;
        $len   = strlen($body);
        for ($i = 0; $i < $len; $i++) {
            $c = $body[$i];
            if ($c === '(') $depth++;
            elseif ($c === ')') $depth--;
            elseif ($c === ',' && $depth === 0) {
                $parts[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $c;
        }
        if ($buf !== '') $parts[] = $buf;
        return $parts;
    }
}
