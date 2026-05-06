# Tests

Run with:

```bash
composer test           # equivalent to: ./vendor/bin/phpunit
./vendor/bin/phpunit --testsuite unit
./vendor/bin/phpunit --testsuite integration
./vendor/bin/phpunit tests/Unit/HelpersTest.php   # single file
./vendor/bin/phpunit --filter testCsrfTokenIsStableWithinSession
```

## Enabling pdo_sqlite (for full coverage)

The DB-touching tests (`BetaAuthTest`, `DatabaseTest`, `VoteTest`) use an
in-memory SQLite database via `pdo_sqlite`. Without it loaded, those tests
auto-skip and you'll see "skipped" in the PHPUnit summary.

To enable it on this scoop-installed PHP, add one line to:

```
C:\Users\cynth\scoop\apps\php84\current\cli\conf.d\pdo_sqlite.ini
```

with contents:

```ini
extension=pdo_sqlite
```

Verify with `php -m | grep -i sqlite` — you should see `pdo_sqlite` and
`PDO` (alongside the existing extensions).

## Layout

- `tests/bootstrap.php` — sets stub `$_ENV` values, loads project classes,
  redeclares `bootstrap.php` helpers (without `session_start`).
- `tests/fixtures/sqlite_schema.sql` — SQLite-compatible mirror of
  `sql/schema.sql`. Keep these in sync when changing the real schema.
- `tests/Support/DatabaseTestCase.php` — base class for DB tests; spins up
  in-memory SQLite, injects via `Database::setPdo()`, resets `$_SESSION`.
- `tests/Unit/` — fast unit tests (mostly no DB).
- `tests/Integration/` — exercises end-to-end business logic against the
  in-memory DB.

## Why SQLite instead of MySQL

Avoids a hard dependency on a running MySQL instance for unit tests.
Trade-off: the test schema can't validate MySQL-specific behaviour like
ENUM truncation. The migration in `sql/migrations/001_widen_feedback_status.sql`
exists precisely because of one of those traps.
