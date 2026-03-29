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

    public function test_mock_send_writes_to_log(): void
    {
        $sms = new SmsService();
        $sms->send('5551234567', 'Test mesajı');

        $this->assertFileExists($this->logFile);
        $content = file_get_contents($this->logFile);
        $this->assertStringContainsString('5551234567', $content);
        $this->assertStringContainsString('Test mesajı', $content);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }
}
