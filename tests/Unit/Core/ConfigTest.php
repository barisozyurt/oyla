<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Config;
use App\Core\InvalidConfigException;

class ConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::reset();
    }

    public function test_env_returns_lowercase_string(): void
    {
        $this->assertEquals('testing', Config::env());
    }

    public function test_is_testing_true_in_testing(): void
    {
        $this->assertTrue(Config::isTesting());
        $this->assertFalse(Config::isProduction());
    }

    public function test_secret_returns_value_in_test(): void
    {
        $v = Config::secret('APP_SECRET');
        $this->assertNotEmpty($v);
        $this->assertGreaterThanOrEqual(32, strlen($v));
    }

    public function test_secret_throws_on_placeholder(): void
    {
        $orig = $_ENV['APP_SECRET'];
        $_ENV['APP_SECRET'] = 'CHANGE_ME_RANDOM_64_CHAR_STRING';
        try {
            $this->expectException(InvalidConfigException::class);
            Config::secret('APP_SECRET');
        } finally {
            $_ENV['APP_SECRET'] = $orig;
        }
    }

    public function test_secret_throws_on_missing(): void
    {
        unset($_ENV['NONEXISTENT_SECRET']);
        $this->expectException(InvalidConfigException::class);
        Config::secret('NONEXISTENT_SECRET');
    }

    public function test_secret_throws_on_too_short(): void
    {
        $_ENV['SHORT_SECRET'] = 'abc';
        $this->expectException(InvalidConfigException::class);
        Config::secret('SHORT_SECRET', 32);
    }

    public function test_get_returns_default_when_missing(): void
    {
        unset($_ENV['MISSING_KEY']);
        $this->assertEquals('fallback', Config::get('MISSING_KEY', 'fallback'));
    }
}
