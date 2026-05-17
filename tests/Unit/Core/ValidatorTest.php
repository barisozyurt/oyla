<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Validator;

class ValidatorTest extends TestCase
{
    public function test_require_fails_on_empty(): void
    {
        $v = new Validator(['name' => '']);
        $v->require('name');
        $this->assertTrue($v->hasErrors());
    }

    public function test_require_passes_on_value(): void
    {
        $v = new Validator(['name' => 'Ahmet']);
        $v->require('name');
        $this->assertFalse($v->hasErrors());
        $this->assertEquals('Ahmet', $v->cleaned()['name']);
    }

    public function test_valid_tc_kimlik(): void
    {
        // Geçerli bir TC örnek: 10000000146 (synthetic, checksum doğru)
        // Algoritma: ilk 10 hane'nin tek+çift kombinasyonu
        // Bilinen geçerli test TC: 12345678950
        $valid = $this->generateValidTC();

        $v = new Validator(['tc' => $valid]);
        $v->tcKimlik('tc', true);
        $this->assertFalse($v->hasErrors(), 'Geçerli TC kabul edilmeli: ' . implode(',', $v->errors()));
    }

    public function test_invalid_tc_kimlik_length(): void
    {
        $v = new Validator(['tc' => '123']);
        $v->tcKimlik('tc', true);
        $this->assertTrue($v->hasErrors());
    }

    public function test_invalid_tc_kimlik_checksum(): void
    {
        $v = new Validator(['tc' => '12345678901']);  // checksum yanlış
        $v->tcKimlik('tc', true);
        $this->assertTrue($v->hasErrors());
    }

    public function test_tc_starts_with_zero_rejected(): void
    {
        $v = new Validator(['tc' => '01234567890']);
        $v->tcKimlik('tc', true);
        $this->assertTrue($v->hasErrors());
    }

    public function test_phone_format(): void
    {
        $v = new Validator(['p' => '5321234567']);
        $v->phone('p', true);
        $this->assertFalse($v->hasErrors());

        $v2 = new Validator(['p' => 'abc']);
        $v2->phone('p', true);
        $this->assertTrue($v2->hasErrors());
    }

    public function test_email(): void
    {
        $v = new Validator(['e' => 'foo@bar.com']);
        $v->email('e', true);
        $this->assertFalse($v->hasErrors());
        $this->assertEquals('foo@bar.com', $v->cleaned()['e']);

        $v2 = new Validator(['e' => 'not-email']);
        $v2->email('e', true);
        $this->assertTrue($v2->hasErrors());
    }

    public function test_in_set(): void
    {
        $v = new Validator(['role' => 'admin']);
        $v->inSet('role', ['admin', 'user']);
        $this->assertFalse($v->hasErrors());

        $v2 = new Validator(['role' => 'hacker']);
        $v2->inSet('role', ['admin', 'user']);
        $this->assertTrue($v2->hasErrors());

        $v3 = new Validator(['role' => 'hacker']);
        $v3->inSet('role', ['admin', 'user'], 'user');
        $this->assertFalse($v3->hasErrors());
        $this->assertEquals('user', $v3->cleaned()['role']);
    }

    public function test_int_range(): void
    {
        $v = new Validator(['n' => '5']);
        $v->intRange('n', 1, 10);
        $this->assertFalse($v->hasErrors());
        $this->assertEquals(5, $v->cleaned()['n']);

        $v2 = new Validator(['n' => '100']);
        $v2->intRange('n', 1, 10);
        $this->assertTrue($v2->hasErrors());
    }

    /**
     * Geçerli bir TC kimlik no üreten yardımcı (mod-11 algoritması).
     */
    private function generateValidTC(): string
    {
        $base = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];   // ilk 10 hane
        $sumOdd  = $base[0] + $base[2] + $base[4] + $base[6] + $base[8];
        $sumEven = $base[1] + $base[3] + $base[5] + $base[7];
        $tenth = (($sumOdd * 7) - $sumEven) % 10;
        if ($tenth < 0) $tenth += 10;
        $base[9] = $tenth;
        $eleventh = (array_sum($base)) % 10;
        return implode('', $base) . $eleventh;
    }
}
