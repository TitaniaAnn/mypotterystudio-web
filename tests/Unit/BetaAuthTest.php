<?php
namespace MyPotteryStudio\Tests\Unit;

use MyPotteryStudio\Tests\Support\DatabaseTestCase;
use BetaAuth;
use Database;

class BetaAuthTest extends DatabaseTestCase
{
    private function createUser(array $overrides = []): int
    {
        $defaults = [
            'email'         => 'tester@example.com',
            'password_hash' => password_hash('correct-horse', PASSWORD_DEFAULT),
            'name'          => 'Tester',
            'platform'      => 'ios',
            'approved'      => 1,
        ];
        return Database::insert('beta_users', array_merge($defaults, $overrides));
    }

    public function testAttemptLoginSucceedsForApprovedUser(): void
    {
        $this->createUser();
        $result = BetaAuth::attemptLogin('tester@example.com', 'correct-horse', '127.0.0.1');
        $this->assertSame('ok', $result);
        $this->assertNotEmpty($_SESSION['beta_user_id']);
        $this->assertSame('tester@example.com', $_SESSION['beta_user']['email']);
    }

    public function testAttemptLoginRejectsWrongPassword(): void
    {
        $this->createUser();
        $result = BetaAuth::attemptLogin('tester@example.com', 'wrong-password');
        $this->assertSame('invalid', $result);
        $this->assertEmpty($_SESSION['beta_user_id'] ?? null);
    }

    public function testAttemptLoginRejectsUnapprovedUser(): void
    {
        $this->createUser(['approved' => 0]);
        $result = BetaAuth::attemptLogin('tester@example.com', 'correct-horse');
        $this->assertSame('invalid', $result);
    }

    public function testAttemptLoginRejectsUnknownEmail(): void
    {
        $result = BetaAuth::attemptLogin('nobody@example.com', 'whatever');
        $this->assertSame('invalid', $result);
    }

    public function testAttemptLoginNormalizesEmailCase(): void
    {
        $this->createUser(['email' => 'tester@example.com']);
        $result = BetaAuth::attemptLogin('TESTER@example.com', 'correct-horse');
        $this->assertSame('ok', $result);
    }

    public function testAttemptLoginRecordsFailureToAttemptsTable(): void
    {
        $this->createUser();
        BetaAuth::attemptLogin('tester@example.com', 'wrong');

        $row = Database::fetchOne(
            "SELECT * FROM login_attempts WHERE email = ? ORDER BY id DESC LIMIT 1",
            ['tester@example.com']
        );
        $this->assertNotNull($row);
        $this->assertSame(0, (int)$row['successful']);
    }

    public function testAttemptLoginRecordsSuccess(): void
    {
        $this->createUser();
        BetaAuth::attemptLogin('tester@example.com', 'correct-horse');

        $row = Database::fetchOne(
            "SELECT * FROM login_attempts WHERE email = ? ORDER BY id DESC LIMIT 1",
            ['tester@example.com']
        );
        $this->assertSame(1, (int)$row['successful']);
    }

    public function testRateLimitTriggersAfterTooManyFailures(): void
    {
        $this->createUser();
        for ($i = 0; $i < BetaAuth::LOGIN_MAX_ATTEMPTS; $i++) {
            BetaAuth::attemptLogin('tester@example.com', 'wrong');
        }
        // Even the correct password should be locked out now.
        $result = BetaAuth::attemptLogin('tester@example.com', 'correct-horse');
        $this->assertSame('rate_limited', $result);
    }

    public function testRateLimitDoesNotAffectOtherEmails(): void
    {
        $this->createUser(['email' => 'a@example.com']);
        $this->createUser(['email' => 'b@example.com', 'password_hash' => password_hash('pw', PASSWORD_DEFAULT)]);

        for ($i = 0; $i < BetaAuth::LOGIN_MAX_ATTEMPTS; $i++) {
            BetaAuth::attemptLogin('a@example.com', 'wrong');
        }

        $result = BetaAuth::attemptLogin('b@example.com', 'pw');
        $this->assertSame('ok', $result);
    }

    public function testIsLoggedInTrueAfterLogin(): void
    {
        $this->createUser();
        BetaAuth::attemptLogin('tester@example.com', 'correct-horse');
        $this->assertTrue(BetaAuth::isLoggedIn());
    }

    public function testLogoutClearsSessionUser(): void
    {
        $this->createUser();
        BetaAuth::attemptLogin('tester@example.com', 'correct-horse');
        BetaAuth::logout();
        $this->assertFalse(BetaAuth::isLoggedIn());
    }
}
