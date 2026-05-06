<?php
namespace MyPotteryStudio\Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testEEscapesHtmlAndQuotes(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
        $this->assertSame('a &amp; b', e('a & b'));
        $this->assertSame('&quot;hi&quot;', e('"hi"'));
        $this->assertSame('&#039;hi&#039;', e("'hi'"));
    }

    public function testEReturnsEmptyForEmpty(): void
    {
        $this->assertSame('', e(''));
    }

    public function testFlashRoundTrip(): void
    {
        flash('success', 'Saved.');
        $f = getFlash();
        $this->assertSame(['type' => 'success', 'msg' => 'Saved.'], $f);
    }

    public function testGetFlashIsOneShot(): void
    {
        flash('error', 'Boom.');
        $this->assertNotNull(getFlash());
        $this->assertNull(getFlash(), 'Second call should return null');
    }

    public function testGetFlashWithoutFlashReturnsNull(): void
    {
        $this->assertNull(getFlash());
    }

    public function testCsrfTokenIsStableWithinSession(): void
    {
        $a = csrf_token();
        $b = csrf_token();
        $this->assertSame($a, $b);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $a);
    }

    public function testCsrfTokenChangesAcrossSessions(): void
    {
        $a = csrf_token();
        $_SESSION = [];
        $b = csrf_token();
        $this->assertNotSame($a, $b);
    }

    public function testCsrfFieldRendersHiddenInput(): void
    {
        $token = csrf_token();
        $field = csrf_field();
        $this->assertStringContainsString('name="_csrf"', $field);
        $this->assertStringContainsString('value="' . $token . '"', $field);
        $this->assertStringContainsString('type="hidden"', $field);
    }

    public function testCsrfCheckAcceptsCorrectToken(): void
    {
        $token = csrf_token();
        $this->assertTrue(csrf_check($token));
    }

    public function testCsrfCheckRejectsWrongToken(): void
    {
        csrf_token();
        $this->assertFalse(csrf_check('wrong'));
        $this->assertFalse(csrf_check(null));
        $this->assertFalse(csrf_check(''));
    }

    public function testCsrfCheckRejectsWhenNoSessionToken(): void
    {
        $_SESSION = [];
        $this->assertFalse(csrf_check('anything'));
    }
}
