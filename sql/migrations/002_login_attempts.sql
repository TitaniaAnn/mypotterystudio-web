-- Per-email login throttling for the beta portal.
CREATE TABLE IF NOT EXISTS login_attempts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255) NOT NULL,
    ip          VARCHAR(45),
    successful  TINYINT(1) DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempted_at),
    INDEX idx_ip_time    (ip, attempted_at)
) ENGINE=InnoDB;
