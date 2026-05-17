<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\PasswordPolicy;

class PasswordPolicyTest extends TestCase
{
    public function test_minimum_length_in_testing_mode(): void
    {
        // testing mode → min 8
        $this->assertEquals(8, PasswordPolicy::minLength());
    }

    public function test_too_short_rejected(): void
    {
        $this->assertNotNull(PasswordPolicy::validate('abc'));
    }

    public function test_missing_uppercase_rejected(): void
    {
        $this->assertNotNull(PasswordPolicy::validate('nouppercaseword1'));
    }

    public function test_missing_lowercase_rejected(): void
    {
        $this->assertNotNull(PasswordPolicy::validate('NOLOWERCASEWORD1'));
    }

    public function test_missing_digit_rejected(): void
    {
        $this->assertNotNull(PasswordPolicy::validate('NoDigitsHereOK!'));
    }

    public function test_repetition_rejected(): void
    {
        $this->assertNotNull(PasswordPolicy::validate('Aaaaa11111Bbbbb'));
    }

    public function test_blacklist_words_rejected(): void
    {
        $this->assertNotNull(PasswordPolicy::validate('MyPassword123'));
        $this->assertNotNull(PasswordPolicy::validate('Qwerty123XYZ'));
        $this->assertNotNull(PasswordPolicy::validate('Admin12345XY'));
    }

    public function test_good_password_accepted(): void
    {
        $this->assertNull(PasswordPolicy::validate('Tg7!nLm3xK9p'));
    }

    public function test_hash_and_verify(): void
    {
        $hash = PasswordPolicy::hash('Tg7!nLm3xK9p');
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(PasswordPolicy::verify('Tg7!nLm3xK9p', $hash));
        $this->assertFalse(PasswordPolicy::verify('wrong', $hash));
    }
}
