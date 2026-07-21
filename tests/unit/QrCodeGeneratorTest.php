<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\QrCodeGenerator;

/**
 * @internal
 */
final class QrCodeGeneratorTest extends CIUnitTestCase
{
    public function testGenerateTokenIsHex32(): void
    {
        $token = QrCodeGenerator::generateToken();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
    }

    public function testGenerateTokenIsUnique(): void
    {
        $tokens = [];
        for ($i = 0; $i < 100; $i++) $tokens[] = QrCodeGenerator::generateToken();
        $this->assertCount(100, array_unique($tokens), 'Random tokens should be unique');
    }

    public function testBuildVerificationUrlContainsToken(): void
    {
        $url = QrCodeGenerator::buildVerificationUrl('abcdef0123456789abcdef0123456789');
        $this->assertStringContainsString('/referral/verify/abcdef0123456789abcdef0123456789', $url);
    }

    public function testGenerateSvgIsValidSvg(): void
    {
        $svg = QrCodeGenerator::generateSvg('https://example.com');
        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('<rect', $svg, 'SVG should contain rendered modules');
    }

    public function testGenerateSvgRespectsSize(): void
    {
        $small = QrCodeGenerator::generateSvg('hello', 100);
        $large = QrCodeGenerator::generateSvg('hello', 300);
        $this->assertStringContainsString('width="100"', $small);
        $this->assertStringContainsString('width="300"', $large);
    }

    public function testAsDataUriReturnsBase64DataUri(): void
    {
        $uri = QrCodeGenerator::asDataUri('https://example.com');
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        $payload = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')));
        $this->assertStringStartsWith('<svg', $payload);
    }

    public function testIsValidTokenAcceptsValidHex32(): void
    {
        $this->assertTrue(QrCodeGenerator::isValidToken('abcdef0123456789abcdef0123456789'));
        $this->assertTrue(QrCodeGenerator::isValidToken(str_repeat('0', 32)));
    }

    public function testIsValidTokenRejectsBadFormat(): void
    {
        $this->assertFalse(QrCodeGenerator::isValidToken(''));
        $this->assertFalse(QrCodeGenerator::isValidToken('short'));
        $this->assertFalse(QrCodeGenerator::isValidToken(str_repeat('g', 32)), 'non-hex chars');
        $this->assertFalse(QrCodeGenerator::isValidToken(str_repeat('a', 31)), '31 chars');
        $this->assertFalse(QrCodeGenerator::isValidToken(str_repeat('a', 33)), '33 chars');
    }
}
