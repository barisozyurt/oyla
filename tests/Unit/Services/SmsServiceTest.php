<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\SmsService;

class SmsServiceTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $_ENV['SMS_MOCK'] = 'true';
        $this->logFile = dirname(__DIR__, 3) . '/logs/sms.log';
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function test_mock_send_returns_true(): void
    {
        $sms = new SmsService();
        $result = $sms->send('5551234567', 'Test mesajı');
        $this->assertTrue($result);
    }

    public function test_mock_send_writes_masked_phone_to_log(): void
    {
        $sms = new SmsService();
        $sms->send('5551234567', 'Test mesajı');

        $this->assertFileExists($this->logFile);
        $content = file_get_contents($this->logFile);
        // Telefon ham olarak loglanmamalı — maskelenmiş olmalı (555***4567)
        $this->assertStringNotContainsString('5551234567', $content, 'Telefon ham loglanmamalı (gizlilik ihlali)');
        $this->assertStringContainsString('555***4567', $content, 'Telefon maskelenerek loglanmalı');
        $this->assertStringContainsString('Test mesajı', $content);
    }

    public function test_mock_send_masks_token_url_in_log(): void
    {
        $sms = new SmsService();
        $token = 'abcdef12-3456-7890-abcd-ef1234567890';
        $sms->send('5551234567', "Oyla bağlantınız: http://localhost/oy/{$token}");

        $content = file_get_contents($this->logFile);
        $this->assertStringNotContainsString($token, $content, 'Token plaintext olarak loglanmamalı');
    }

    public function test_mock_send_masks_receipt_code_in_log(): void
    {
        $sms = new SmsService();
        $sms->send('5551234567', 'Makbuz kodunuz: ABCD1234');

        $content = file_get_contents($this->logFile);
        $this->assertStringNotContainsString('ABCD1234', $content, 'Makbuz kodu ham loglanmamalı');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }
}
