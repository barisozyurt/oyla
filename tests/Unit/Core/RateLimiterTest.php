<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\RateLimiter;

class RateLimiterTest extends TestCase
{
    private string $key;

    protected function setUp(): void
    {
        // Her test için benzersiz bucket key — DB-backed mode'da testler arası kirlenmeyi önler.
        // ENDPOINTS map'inde yer ALMAYAN bir prefix kullanmak gerekir ki override kalsın.
        $this->key = 'unit_' . bin2hex(random_bytes(4));
        $_SESSION = [];
        RateLimiter::reset($this->key);  // her ihtimale karşı temizle
    }

    protected function tearDown(): void
    {
        RateLimiter::reset($this->key);
    }

    public function test_allows_under_limit(): void
    {
        $this->assertTrue(RateLimiter::check($this->key, 5, 300));
        $this->assertTrue(RateLimiter::check($this->key, 5, 300));
    }

    public function test_blocks_at_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::check($this->key, 5, 300);
        }
        $this->assertFalse(RateLimiter::check($this->key, 5, 300));
    }

    public function test_reset_clears_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::check($this->key, 5, 300);
        }
        RateLimiter::reset($this->key);
        $this->assertTrue(RateLimiter::check($this->key, 5, 300));
    }
}
