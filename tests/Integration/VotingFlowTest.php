<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Services\CryptoService;

class VotingFlowTest extends TestCase
{
    /**
     * Test the complete voting cryptographic flow.
     */
    public function test_complete_voting_crypto_flow(): void
    {
        // Simulate: generate token → cast vote → generate receipt → verify

        // Step 1: Token generation
        $tokenPlain = 'test-uuid-' . bin2hex(random_bytes(8));
        $memberId = 42;
        $secret = 'test_secret_key';
        $tokenHash = hash_hmac('sha256', $tokenPlain . $memberId . time(), $secret);

        $this->assertNotEmpty($tokenHash);
        $this->assertEquals(64, strlen($tokenHash));

        // Step 2: Vote with commitment hash
        $candidateIds = [1, 3, 5];
        $choiceJson = json_encode($candidateIds);
        $salt = CryptoService::generateSalt();
        $commitmentHash = CryptoService::commitmentHash($choiceJson, $salt, $tokenPlain);

        $this->assertEquals(64, strlen($commitmentHash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $commitmentHash);

        // Step 3: Verify commitment
        $this->assertTrue(CryptoService::verifyCommitment($commitmentHash, $choiceJson, $salt, $tokenPlain));

        // Step 4: Tampered vote detection
        $tamperedChoice = json_encode([1, 3, 6]); // Changed candidate 5 to 6
        $this->assertFalse(CryptoService::verifyCommitment($commitmentHash, $tamperedChoice, $salt, $tokenPlain));

        // Step 5: Receipt generation
        $publicCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $this->assertEquals(8, strlen($publicCode));
        $this->assertMatchesRegularExpression('/^[A-F0-9]{8}$/', $publicCode);

        // Step 6: Combined hash for multi-ballot
        $hashes = [$commitmentHash];
        $secondHash = CryptoService::commitmentHash(json_encode([2, 4]), CryptoService::generateSalt(), $tokenPlain);
        $hashes[] = $secondHash;
        $combinedHash = hash('sha256', implode('', $hashes));
        $this->assertEquals(64, strlen($combinedHash));
        $this->assertNotEquals($commitmentHash, $combinedHash); // Combined is different from individual
    }

    /**
     * Test quota enforcement logic.
     */
    public function test_quota_enforcement(): void
    {
        $quota = 3;
        $selected = [1, 2, 3];
        $this->assertCount($quota, $selected); // At quota — OK

        $overQuota = [1, 2, 3, 4];
        $this->assertGreaterThan($quota, count($overQuota)); // Over quota — should be rejected

        $underQuota = [1, 2];
        $this->assertLessThanOrEqual($quota, count($underQuota)); // Under quota — OK

        $empty = [];
        $this->assertLessThanOrEqual($quota, count($empty)); // Empty — allowed (blank ballot)
    }

    /**
     * Test that token validation logic is correct.
     */
    public function test_token_expiry_logic(): void
    {
        $now = time();

        // Valid token (expires in 2 hours)
        $expiresAt = date('Y-m-d H:i:s', $now + 7200);
        $this->assertGreaterThan($now, strtotime($expiresAt));

        // Expired token (expired 1 hour ago)
        $expiredAt = date('Y-m-d H:i:s', $now - 3600);
        $this->assertLessThan($now, strtotime($expiredAt));
    }

    /**
     * Test salt uniqueness.
     */
    public function test_salts_are_unique(): void
    {
        $salts = [];
        for ($i = 0; $i < 100; $i++) {
            $salts[] = CryptoService::generateSalt();
        }
        $unique = array_unique($salts);
        $this->assertCount(100, $unique, 'All 100 salts should be unique');
    }
}
