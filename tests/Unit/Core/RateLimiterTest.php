<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\RateLimiter;

class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function test_allows_under_limit(): void
    {
        $this->assertTrue(RateLimiter::check('test', 5, 300));
        $this->assertTrue(RateLimiter::check('test', 5, 300));
    }

    public function test_blocks_at_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::check('test', 5, 300);
        }
        $this->assertFalse(RateLimiter::check('test', 5, 300));
    }

    public function test_reset_clears_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::check('test', 5, 300);
        }
        RateLimiter::reset('test');
        $this->assertTrue(RateLimiter::check('test', 5, 300));
    }
}
