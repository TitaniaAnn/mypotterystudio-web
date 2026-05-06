<?php
namespace MyPotteryStudio\Tests\Integration;

use MyPotteryStudio\Tests\Support\DatabaseTestCase;
use Database;

/**
 * Tests the vote-toggle business logic the same way /beta/api/vote.php
 * runs it. Replicating the closure here keeps the test focused on logic
 * without dragging in HTTP plumbing.
 */
class VoteTest extends DatabaseTestCase
{
    private int $userId;
    private int $feedbackId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = Database::insert('beta_users', [
            'email'         => 'voter@example.com',
            'password_hash' => 'x',
            'name'          => 'Voter',
            'platform'      => 'ios',
            'approved'      => 1,
        ]);

        $authorId = Database::insert('beta_users', [
            'email'         => 'author@example.com',
            'password_hash' => 'x',
            'name'          => 'Author',
            'platform'      => 'ios',
            'approved'      => 1,
        ]);

        $this->feedbackId = Database::insert('beta_feedback', [
            'user_id' => $authorId,
            'type'    => 'feature',
            'title'   => 'Add a thing',
            'body'    => 'Please add the thing.',
        ]);
    }

    private function toggle(): array
    {
        return Database::transaction(function () {
            $existingVote = Database::fetchOne(
                "SELECT id FROM beta_votes WHERE feedback_id = ? AND user_id = ?",
                [$this->feedbackId, $this->userId]
            );

            if ($existingVote) {
                Database::execute(
                    "DELETE FROM beta_votes WHERE feedback_id = ? AND user_id = ?",
                    [$this->feedbackId, $this->userId]
                );
                $voted = false;
            } else {
                Database::execute(
                    "INSERT OR IGNORE INTO beta_votes (feedback_id, user_id) VALUES (?, ?)",
                    [$this->feedbackId, $this->userId]
                );
                $voted = true;
            }

            $count = (int) Database::fetchOne(
                "SELECT COUNT(*) AS c FROM beta_votes WHERE feedback_id = ?",
                [$this->feedbackId]
            )['c'];
            Database::execute("UPDATE beta_feedback SET votes = ? WHERE id = ?", [$count, $this->feedbackId]);

            return ['voted' => $voted, 'votes' => $count];
        });
    }

    public function testFirstVoteIncrements(): void
    {
        $r = $this->toggle();
        $this->assertTrue($r['voted']);
        $this->assertSame(1, $r['votes']);

        $stored = Database::fetchOne("SELECT votes FROM beta_feedback WHERE id = ?", [$this->feedbackId]);
        $this->assertSame(1, (int)$stored['votes']);
    }

    public function testSecondToggleRemovesVote(): void
    {
        $this->toggle();
        $r = $this->toggle();
        $this->assertFalse($r['voted']);
        $this->assertSame(0, $r['votes']);
    }

    public function testVoteCountStaysConsistentAcrossManyToggles(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->toggle();
        }
        // 10 toggles = back to 0 (even count)
        $stored = Database::fetchOne("SELECT votes FROM beta_feedback WHERE id = ?", [$this->feedbackId]);
        $this->assertSame(0, (int)$stored['votes']);
    }

    public function testCannotDoubleVoteSameUser(): void
    {
        $this->toggle(); // creates the vote row
        // Manually try a second insert; should be ignored thanks to UNIQUE.
        Database::execute(
            "INSERT OR IGNORE INTO beta_votes (feedback_id, user_id) VALUES (?, ?)",
            [$this->feedbackId, $this->userId]
        );
        $rows = Database::fetchAll(
            "SELECT * FROM beta_votes WHERE feedback_id = ? AND user_id = ?",
            [$this->feedbackId, $this->userId]
        );
        $this->assertCount(1, $rows);
    }
}
