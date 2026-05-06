# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Marketing site + beta-testing portal for the **My Pottery Studio** mobile app. Plain PHP 8 / MySQL / vanilla JS — no framework, no build step. Deployed on shared Apache hosting (Bluehost). The mobile app itself lives in a separate repo (`my_pottery_studio`).

## Setup & common commands

There is no test suite, build step, or linter wired up. Workflow is edit → upload → reload.

```bash
composer install           # one-time: pulls phpmailer + phpdotenv into vendor/
cp .env.example .env       # then fill in DB + GitHub OAuth + SMTP creds
mysql -u USER -p DB < sql/schema.sql   # provision schema (idempotent — uses IF NOT EXISTS / ON DUPLICATE KEY)
php -S localhost:8000 -t public        # local dev (bypasses .htaccess; routes work because public/ is the docroot)
```

For production-like local testing of the `.htaccess` rewrite (root → `public/index.php`), use Apache rather than `php -S`.

## Architecture

### Request lifecycle

1. Apache `.htaccess` at the repo root rewrites `/` → `public/index.php` and `/<anything>` → `public/<anything>`. The `public/` segment is hidden from URLs.
2. Every PHP entry point begins with `require_once __DIR__ . '/../includes/bootstrap.php';` (or `'/../../includes/bootstrap.php'` from deeper paths). **Always include bootstrap first** — it loads composer's autoloader, parses `.env`, defines all constants in [config/config.php](config/config.php), pulls in the four core classes, starts the session, and exposes the `setting()`, `e()`, `redirect()`, `flash()`, `getFlash()` helpers.
3. Pages are self-contained: PHP at the top reads/writes the DB, then HTML is interleaved below using `<?= e($var) ?>`. There is no router, no MVC, no template engine — `include __DIR__ . '/templates/...'` is the extent of view composition.

### Two parallel auth systems

These are independent — different sessions keys, different tables, different login pages. Don't conflate them.

| | Admin (CMS) | Beta tester portal |
|---|---|---|
| Class | [`Auth`](includes/Auth.php) | [`BetaAuth`](includes/BetaAuth.php) |
| Mechanism | GitHub OAuth, allowlist via `ALLOWED_GITHUB_USERS` env var | Email + `password_verify` against `beta_users.password_hash` |
| Table | `admin_users` | `beta_users` (with `approved` flag) |
| Session keys | `admin_id`, `admin_user` | `beta_user_id`, `beta_user` |
| Entry | [public/admin/login.php](public/admin/login.php) → [public/admin/auth/callback.php](public/admin/auth/callback.php) | [public/beta/login.php](public/beta/login.php) |
| Guard | `Auth::requireLogin()` | `BetaAuth::requireLogin()` |

Both share the single PHP session (`SESSION_NAME = mps_session`), so a user can be logged in to both portals simultaneously.

### Two web "portals" under public/

- **`public/`** (root) — public marketing site. [index.php](public/index.php) renders the landing page from the `settings`, `app_features`, and `screenshots` tables. Shared chrome lives in [public/templates/](public/templates/).
- **`public/admin/*`** — CMS for the marketing site + beta program management. Manages `settings`, `screenshots`, `beta_users` (approve/revoke), and views `beta_feedback`. Each page renders its own `<head>` and includes `partials/sidebar.php` + `partials/topbar.php`.
- **`public/beta/*`** — portal for approved beta testers. Submit feedback, browse issues, vote. Each page renders its own sidebar inline (no shared partial — the markup is duplicated across pages, so changes to nav/footer have to be applied in every file).

### Database access

[`Database`](includes/Database.php) is a thin static wrapper around a singleton PDO. Five methods: `fetchAll`, `fetchOne`, `execute`, `insert`, `update`. **Always use parameter binding** — `Database::insert`/`update` build SQL from `$data` array keys, so those keys must be trusted column names (never user input). Raw `fetchAll($sql, $params)` accepts both `?` positional and `:name` named placeholders.

### settings() and the `settings` table

[`bootstrap.php`](includes/bootstrap.php) defines `setting(string $key, string $default = ''): string` which lazy-loads the entire `settings` table on first call and caches it in a static. Mutations to settings during a request will not be reflected by subsequent `setting()` calls in the same request. Admin pages that update settings then redirect, so this is fine — but be aware before adding code that updates and re-reads in one pass.

### GitHub integration (beta feedback ↔ GitHub Issues)

When a beta tester submits feedback via [public/beta/submit.php](public/beta/submit.php), it's optionally cross-posted to the configured `BETA_GITHUB_REPO` as a GitHub issue (using `BETA_GITHUB_TOKEN`), and the resulting issue number/URL is stored on the `beta_feedback` row. The "All Issues" page in the beta portal pulls live issues from the GitHub API via [`GitHubAPI::getIssues`](includes/GitHubAPI.php).

**Footgun:** there are TWO `GitHubAPI` classes — [includes/GitHubAPI.php](includes/GitHubAPI.php) (the one bootstrap loads) and [config/GitHubAPI.php](config/GitHubAPI.php) (a richer copy with `getIssue`/`updateIssue` and `CURLOPT_SSL_VERIFYPEER => false`, NOT autoloaded). When adding methods, edit the `includes/` version and remove or merge the orphan in `config/` rather than adding a third copy.

### Schema

Single source of truth: [sql/schema.sql](sql/schema.sql). Tables: `admin_users`, `settings` (key/value), `app_features`, `screenshots`, `beta_users`, `beta_feedback`, `beta_votes` (unique on `(feedback_id, user_id)`), `beta_emails`. There are no migrations — schema changes are applied by editing this file and re-running it (the seed inserts use `ON DUPLICATE KEY UPDATE` so re-runs are safe).

Note: [public/admin/dashboard.php:143](public/admin/dashboard.php#L143) renders status badges for `paused` and `testing` values that the schema's `ENUM('open','in_progress','closed')` does not allow. Either the enum needs widening or the dashboard code is dead — verify before relying on either.

## Conventions worth knowing

- **HTML escape everything user-derived** with `e($string)`. The `setting()` helper returns raw DB values, so escape on output too.
- **CSRF is not implemented.** Form posts rely on session auth alone. Don't add admin actions that mutate state via GET.
- **Uploads** go to [public/uploads/](public/uploads/) with the URL prefix `UPLOAD_URL` (= `SITE_URL . '/uploads/'`); `MAX_IMAGE_SIZE` = 10 MB.
- **No frontend tooling.** [public/css/style.css](public/css/style.css), [public/admin/css/admin.css](public/admin/css/admin.css), [public/beta/css/beta.css](public/beta/css/beta.css) are hand-written. JS in [public/js/main.js](public/js/main.js) and [public/beta/js/beta.js](public/beta/js/beta.js) is vanilla, no bundler.
- **Flash messages** are one-shot session entries: write with `flash('success', 'Saved.')`, read+consume with `getFlash()`. Always pair a write with a redirect, otherwise the flash sits in the session until the next page load.
- **`.env` is gitignored**, but so is `.htaccess` — be careful not to lose `.htaccess` changes to git when working locally.
