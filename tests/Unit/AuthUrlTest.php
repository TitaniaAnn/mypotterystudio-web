<?php
namespace MyPotteryStudio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Auth;

class AuthUrlTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testGetGitHubAuthUrlReturnsCorrectEndpoint(): void
    {
        $url = Auth::getGitHubAuthUrl();
        $this->assertStringStartsWith('https://github.com/login/oauth/authorize?', $url);
    }

    public function testGetGitHubAuthUrlIncludesClientId(): void
    {
        $url = Auth::getGitHubAuthUrl();
        $params = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $this->assertSame('test_client_id', $params['client_id']);
        $this->assertSame('read:user', $params['scope']);
        $this->assertSame('https://test.local/admin/auth/callback.php', $params['redirect_uri']);
    }

    public function testGetGitHubAuthUrlGeneratesUniqueState(): void
    {
        $a = Auth::getGitHubAuthUrl();
        $_SESSION = [];
        $b = Auth::getGitHubAuthUrl();

        $aState = $this->stateOf($a);
        $bState = $this->stateOf($b);
        $this->assertNotSame($aState, $bState);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $aState);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $bState);
    }

    public function testGetGitHubAuthUrlStoresStateInSession(): void
    {
        $url = Auth::getGitHubAuthUrl();
        $this->assertSame($this->stateOf($url), $_SESSION['oauth_state']);
    }

    private function stateOf(string $url): string
    {
        $params = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        return $params['state'];
    }
}
