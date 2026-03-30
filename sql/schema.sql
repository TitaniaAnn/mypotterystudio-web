-- My Pottery Studio — App Promo Site Schema

CREATE TABLE IF NOT EXISTS admin_users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    github_id  VARCHAR(64) UNIQUE NOT NULL,
    email      VARCHAR(255),
    name       VARCHAR(255),
    avatar_url TEXT,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
    ('site_name',       'My Pottery Studio'),
    ('tagline',         'The studio management app for potters'),
    ('hero_title',      'My Pottery Studio'),
    ('hero_subtitle',   'Track your clay, glazes, kilns, and creations — all in one beautiful app.'),
    ('about_text',      'My Pottery Studio is designed by a potter, for potters. Manage your inventory, log your firings, track your pieces, and share your work.'),
    ('app_status',      'beta'),
    ('ios_url',         ''),
    ('android_url',     ''),
    ('beta_open',       '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

CREATE TABLE IF NOT EXISTS app_features (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    icon        VARCHAR(10) DEFAULT '🏺',
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    sort_order  INT DEFAULT 0,
    active      TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO app_features (icon, title, description, sort_order) VALUES
    ('🏺', 'Piece Tracker', 'Log every piece from raw clay through bisque, glaze, and final firing. Add photos at each stage.', 1),
    ('🌡️', 'Firing Log', 'Record kiln temperatures, schedules, and outcomes. Never lose track of a firing again.', 2),
    ('🎨', 'Glaze Library', 'Build your personal glaze recipe database with photos and notes.', 3),
    ('📦', 'Inventory', 'Track clay bodies, glazes, tools, and studio supplies.', 4),
    ('📸', 'Portfolio Gallery', 'Showcase your finished work in a beautiful, shareable gallery.', 5),
    ('📊', 'Studio Insights', 'See your productivity, popular techniques, and studio usage over time.', 6)
ON DUPLICATE KEY UPDATE title = title;

CREATE TABLE IF NOT EXISTS screenshots (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    caption    VARCHAR(255),
    image_path VARCHAR(500) NOT NULL,
    platform   ENUM('ios','android','all') DEFAULT 'all',
    sort_order INT DEFAULT 0,
    active     TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS beta_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(255) NOT NULL,
    platform      ENUM('android','ios','both','other') NOT NULL DEFAULT 'other',
    approved      TINYINT(1) DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS beta_feedback (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    type                ENUM('bug','feature') NOT NULL,
    title               VARCHAR(255) NOT NULL,
    body                TEXT NOT NULL,
    platform            VARCHAR(50),
    app_version         VARCHAR(50),
    github_issue_number INT NULL,
    github_issue_url    TEXT NULL,
    status              ENUM('open','in_progress','paused','testing','closed') DEFAULT 'open',
    votes               INT DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES beta_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS beta_votes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    user_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (feedback_id, user_id),
    FOREIGN KEY (feedback_id) REFERENCES beta_feedback(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES beta_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS beta_emails (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    subject    VARCHAR(255) NOT NULL,
    body       TEXT NOT NULL,
    sent_to    INT DEFAULT 0,
    sent_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
