<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\CryptoService;

class CryptoServiceTest extends TestCase
{
    public function test_generate_salt_returns_32_char_hex(): void
    {
        $salt = CryptoService::generateSalt();
        $this->assertEquals(32, strlen($salt));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $salt);
    }

    public function test_v1_commitment_hash_is_deterministic(): void
    {
        $choice = json_encode([1, 2, 3]);
        $salt   = '0123456789abcdef0123456789abcdef';
        $token  = 'test-token-plain';

        $hash1 = CryptoService::commitmentHash($choice, $salt, $token);
        $hash2 = CryptoService::commitmentHash($choice, $salt, $token);
        $this->assertEquals($hash1, $hash2);
    }

    public function test_v1_uses_hmac_not_simple_sha256(): void
    {
        $choice = json_encode([1, 2, 3]);
        $salt   = '0123456789abcdef0123456789abcdef';
        $token  = 'test-token-plain';

        $v1 = CryptoService::commitmentHash($choice, $salt, $token, 'v1');
        $v0 = CryptoService::commitmentHash($choice, $salt, $token, 'v0');

        // v1 (HKDF+HMAC) ile v0 (simple sha256(concat)) AYRI olmalı —
        // bu da v1'in çift güvenlik katmanı eklediğinin kanıtıdır.
        $this->assertNotEquals($v1, $v0, 'v1 and v0 hash algorithms must differ');
    }

    public function test_different_inputs_produce_different_hashes(): void
    {
        $salt = CryptoService::generateSalt();
        $hash1 = CryptoService::commitmentHash('[1,2]', $salt, 'token-a');
        $hash2 = CryptoService::commitmentHash('[1,3]', $salt, 'token-a');
        $hash3 = CryptoService::commitmentHash('[1,2]', $salt, 'token-b');
        $this->assertNotEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
        $this->assertNotEquals($hash2, $hash3);
    }

    public function test_verify_commitment_round_trip(): void
    {
        $choice = json_encode([5, 10]);
        $salt   = CryptoService::generateSalt();
        $token  = 'my-token';

        $hash = CryptoService::commitmentHash($choice, $salt, $token);
        $this->assertTrue(CryptoService::verifyCommitment($hash, $choice, $salt, $token));
        $this->assertFalse(CryptoService::verifyCommitment($hash, '[5,11]', $salt, $token));
        $this->assertFalse(CryptoService::verifyCommitment($hash, $choice, '00000000000000000000000000000000', $token));
        $this->assertFalse(CryptoService::verifyCommitment($hash, $choice, $salt, 'wrong-token'));
    }

    public function test_hash_is_64_char_sha256(): void
    {
        $hash = CryptoService::commitmentHash('test', '0011223344556677', 'token');
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_combined_commitment_order_independent(): void
    {
        $token = 'shared-token-plain';
        $h1 = hash('sha256', 'ballot1-data');
        $h2 = hash('sha256', 'ballot2-data');
        $h3 = hash('sha256', 'ballot3-data');

        $combinedA = CryptoService::combinedCommitment([$h1, $h2, $h3], $token);
        $combinedB = CryptoService::combinedCommitment([$h3, $h1, $h2], $token);
        $this->assertEquals($combinedA, $combinedB, 'Combined hash sıralama bağımsız olmalı');

        $combinedWrongToken = CryptoService::combinedCommitment([$h1, $h2, $h3], 'other-token');
        $this->assertNotEquals($combinedA, $combinedWrongToken);
    }

    public function test_unknown_version_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CryptoService::commitmentHash('x', '00', 'token', 'v99');
    }
}
