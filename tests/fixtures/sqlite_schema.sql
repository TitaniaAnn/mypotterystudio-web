-- SQLite-compatible mirror of sql/schema.sql for unit tests.
-- Drop MySQL-specific bits (ENGINE, ENUM constraints, ON DUPLICATE, defaults
-- on TIMESTAMP) and replace with SQLite equivalents. Tests must not rely on
-- MySQL-specific behaviour.

CREATE TABLE admin_users (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    github_id  TEXT UNIQUE NOT NULL,
    email      TEXT,
    name       TEXT,
    avatar_url TEXT,
    last_login TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key   TEXT UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at    TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE app_features (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    icon        TEXT DEFAULT '',
    title       TEXT NOT NULL,
    description TEXT,
    sort_order  INTEGER DEFAULT 0,
    active      INTEGER DEFAULT 1
);

CREATE TABLE screenshots (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    caption    TEXT,
    image_path TEXT NOT NULL,
    platform   TEXT DEFAULT 'all',
    sort_order INTEGER DEFAULT 0,
    active     INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE beta_users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    email         TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    name          TEXT NOT NULL,
    platform      TEXT NOT NULL DEFAULT 'other',
    approved      INTEGER DEFAULT 0,
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
    last_login    TEXT
);

CREATE TABLE beta_feedback (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id             INTEGER,
    type                TEXT NOT NULL,
    title               TEXT NOT NULL,
    body                TEXT NOT NULL,
    platform            TEXT,
    app_version         TEXT,
    github_issue_number INTEGER,
    github_issue_url    TEXT,
    status              TEXT DEFAULT 'open',
    votes               INTEGER DEFAULT 0,
    created_at          TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES beta_users(id) ON DELETE SET NULL
);

CREATE TABLE beta_votes (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    feedback_id INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    created_at  TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (feedback_id, user_id),
    FOREIGN KEY (feedback_id) REFERENCES beta_feedback(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES beta_users(id) ON DELETE CASCADE
);

CREATE TABLE beta_emails (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    subject    TEXT NOT NULL,
    body       TEXT NOT NULL,
    sent_to    INTEGER DEFAULT 0,
    sent_at    TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE schema_migrations (
    filename   TEXT PRIMARY KEY,
    applied_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    email       TEXT NOT NULL,
    ip          TEXT,
    successful  INTEGER DEFAULT 0,
    attempted_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_email_time ON login_attempts (email, attempted_at);
CREATE INDEX idx_ip_time    ON login_attempts (ip, attempted_at);
