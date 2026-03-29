<?php
declare(strict_types=1);

namespace App\Services;

class CryptoService
{
    public static function generateSalt(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function commitmentHash(string $choiceJson, string $salt, string $tokenPlain): string
    {
        return hash('sha256', $choiceJson . $salt . $tokenPlain);
    }

    public static function verifyCommitment(string $hash, string $choiceJson, string $salt, string $tokenPlain): bool
    {
        return hash_equals($hash, self::commitmentHash($choiceJson, $salt, $tokenPlain));
    }
}
