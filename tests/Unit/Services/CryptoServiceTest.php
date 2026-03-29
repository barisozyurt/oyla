<?php
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

    public function test_commitment_hash_is_deterministic(): void
    {
        $choice = json_encode([1, 2, 3]);
        $salt = 'abcdef1234567890abcdef1234567890';
        $token = 'test-token-plain';
        $hash1 = CryptoService::commitmentHash($choice, $salt, $token);
        $hash2 = CryptoService::commitmentHash($choice, $salt, $token);
        $this->assertEquals($hash1, $hash2);
    }

    public function test_different_inputs_produce_different_hashes(): void
    {
        $salt = CryptoService::generateSalt();
        $hash1 = CryptoService::commitmentHash('[1,2]', $salt, 'token-a');
        $hash2 = CryptoService::commitmentHash('[1,3]', $salt, 'token-a');
        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_verify_commitment_round_trip(): void
    {
        $choice = json_encode([5, 10]);
        $salt = CryptoService::generateSalt();
        $token = 'my-token';
        $hash = CryptoService::commitmentHash($choice, $salt, $token);
        $this->assertTrue(CryptoService::verifyCommitment($hash, $choice, $salt, $token));
        $this->assertFalse(CryptoService::verifyCommitment($hash, '[5,11]', $salt, $token));
    }

    public function test_hash_is_64_char_sha256(): void
    {
        $hash = CryptoService::commitmentHash('test', 'salt', 'token');
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }
}
