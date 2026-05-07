<?php
namespace MyPotteryStudio\Tests\Integration;

use MyPotteryStudio\Tests\Support\DatabaseTestCase;
use Database;

/**
 * Verifies the design intent of #9: deleting a tester nulls out their
 * feedback's user_id (preserving the row) and cascades their votes away.
 */
class TesterDeleteTest extends DatabaseTestCase
{
    public function testDeletingTesterPreservesTheirFeedbackAsNull(): void
    {
        $userId = Database::insert('beta_users', [
            'email'         => 'doomed@example.com',
            'password_hash' => 'x',
            'name'          => 'Doomed',
            'platform'      => 'ios',
            'approved'      => 1,
        ]);
        $feedbackId = Database::insert('beta_feedback', [
            'user_id' => $userId,
            'type'    => 'bug',
            'title'   => 'something is broken',
            'body'    => 'really broken',
        ]);

        Database::execute("DELETE FROM beta_users WHERE id = ?", [$userId]);

        $row = Database::fetchOne("SELECT * FROM beta_feedback WHERE id = ?", [$feedbackId]);
        $this->assertNotNull($row, 'Feedback row should still exist after tester delete');
        $this->assertNull($row['user_id'], 'user_id should be NULL after ON DELETE SET NULL');
        $this->assertSame('something is broken', $row['title']);
    }

    public function testDeletingTesterStillCascadesTheirVotes(): void
    {
        $authorId = Database::insert('beta_users', [
            'email' => 'author@example.com', 'password_hash' => 'x',
            'name' => 'Author', 'platform' => 'ios', 'approved' => 1,
        ]);
        $voterId = Database::insert('beta_users', [
            'email' => 'voter@example.com', 'password_hash' => 'x',
            'name' => 'Voter', 'platform' => 'ios', 'approved' => 1,
        ]);
        $feedbackId = Database::insert('beta_feedback', [
            'user_id' => $authorId, 'type' => 'feature',
            'title' => 't', 'body' => 'b',
        ]);
        Database::insert('beta_votes', ['feedback_id' => $feedbackId, 'user_id' => $voterId]);

        Database::execute("DELETE FROM beta_users WHERE id = ?", [$voterId]);

        $vote = Database::fetchOne(
            "SELECT * FROM beta_votes WHERE feedback_id = ? AND user_id = ?",
            [$feedbackId, $voterId]
        );
        $this->assertNull($vote, 'Votes should cascade-delete with the voter');
    }
}
