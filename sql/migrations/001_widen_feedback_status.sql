-- Widen beta_feedback.status to match the values the admin UI writes.
-- Without this, UPDATEs with 'paused' or 'testing' are silently coerced
-- to '' on default-mode MySQL.
ALTER TABLE beta_feedback
    MODIFY status ENUM('open','in_progress','paused','testing','closed') DEFAULT 'open';

-- Repair any rows that were previously truncated to ''.
UPDATE beta_feedback SET status = 'open' WHERE status = '';
