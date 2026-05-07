-- Make beta_feedback.user_id nullable + ON DELETE SET NULL so that
-- deleting a tester preserves their feedback (rendered as "Deleted
-- tester" in the UI) instead of cascading the rows away.
--
-- Step 1: drop the existing FK. MySQL auto-named it `beta_feedback_ibfk_1`
-- when it was the first FK declared inline in CREATE TABLE. If your
-- install has a different name, run:
--     SHOW CREATE TABLE beta_feedback;
-- and replace the name below before applying.

ALTER TABLE beta_feedback DROP FOREIGN KEY beta_feedback_ibfk_1;

-- Step 2: relax the NOT NULL.

ALTER TABLE beta_feedback MODIFY user_id INT NULL;

-- Step 3: add the new FK with the desired ON DELETE behaviour.

ALTER TABLE beta_feedback
    ADD CONSTRAINT fk_beta_feedback_user
    FOREIGN KEY (user_id) REFERENCES beta_users(id) ON DELETE SET NULL;
