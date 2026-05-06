<?php
namespace MyPotteryStudio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use GitHubAPI;

/**
 * Only tests no-network paths. Methods that make real HTTP calls aren't
 * exercised here — they'd belong in an integration suite with curl mocking.
 */
class GitHubAPITest extends TestCase
{
    public function testGetIssuesReturnsEmptyWhenRepoMissing(): void
    {
        $this->assertSame([], GitHubAPI::getIssues(''));
        $this->assertSame([], GitHubAPI::getIssues('', 'all', 'token'));
    }

    public function testCreateIssueReturnsEmptyWhenRepoMissing(): void
    {
        $this->assertSame([], GitHubAPI::createIssue('', 'token', 'title', 'body'));
    }

    public function testCreateIssueReturnsEmptyWhenTokenMissing(): void
    {
        $this->assertSame([], GitHubAPI::createIssue('owner/repo', '', 'title', 'body'));
    }
}
