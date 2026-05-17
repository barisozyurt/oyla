<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Kriptografi servisi — HMAC tabanlı commitment, KDF tabanlı anahtar türetimi.
 *
 * GÜVENLİK NOTU (FAZ 1):
 * - Eski şema: `sha256(choice . salt . token)` — basit concat, hash collision'a daha açık.
 * - Yeni şema (v1):
 *     k_commit = HKDF(APP_SECRET, salt=salt, info="commitment-v1-" . tokenPlain)
 *     commitment = HMAC-SHA256(k_commit, choiceJson)
 *   — Token plain artık doğrudan hash'in girdisi değil, KDF'in info parametresi.
 *   — Aynı choice + farklı salt → tamamen farklı k_commit → "salt rainbow" saldırısı engellenir.
 * - Eski v0 doğrulama backwards-compatibility için bırakılmıştır (legacy oylar için).
 */
final class CryptoService
{
    public const VERSION = 'v1';

    public static function generateSalt(): string
    {
        return bin2hex(random_bytes(16));  // 32-char hex
    }

    /**
     * Yeni commitment hash (v1).
     */
    public static function commitmentHash(string $choiceJson, string $salt, string $tokenPlain, string $version = self::VERSION): string
    {
        return match ($version) {
            'v1'    => self::commitV1($choiceJson, $salt, $tokenPlain),
            'v0'    => self::commitV0($choiceJson, $salt, $tokenPlain),
            default => throw new \InvalidArgumentException("Bilinmeyen crypto versiyonu: {$version}"),
        };
    }

    public static function verifyCommitment(string $hash, string $choiceJson, string $salt, string $tokenPlain, string $version = self::VERSION): bool
    {
        $expected = self::commitmentHash($choiceJson, $salt, $tokenPlain, $version);
        return hash_equals($hash, $expected);
    }

    /**
     * HKDF (RFC 5869) — PHP'in dahili hash_hkdf() fonksiyonu kullanılır.
     */
    public static function deriveKey(string $ikm, string $salt, string $info, int $length = 32): string
    {
        return hash_hkdf('sha256', $ikm, $length, $info, hex2bin($salt) ?: $salt);
    }

    /**
     * Combined commitment hash (multi-ballot makbuz kodu için).
     * Sıralı hash'leri tek bir HMAC ile birleştirir; receipt public_code'un kriptografik bağlantı noktası.
     */
    public static function combinedCommitment(array $individualHashes, string $tokenPlain): string
    {
        $secret = Config::secret('APP_SECRET', 32);
        sort($individualHashes); // sıralama bağımsız olsun
        return hash_hmac('sha256', implode('|', $individualHashes), $secret . ':' . $tokenPlain);
    }

    // -------------------------------------------------------------------------
    // İç implementasyonlar
    // -------------------------------------------------------------------------

    private static function commitV1(string $choiceJson, string $salt, string $tokenPlain): string
    {
        $appSecret = Config::secret('APP_SECRET', 32);
        $kCommit = hash_hkdf(
            'sha256',
            $appSecret,
            32,
            'commitment-v1-' . $tokenPlain,
            hex2bin($salt) ?: $salt
        );
        return hash_hmac('sha256', $choiceJson, $kCommit);
    }

    /** @deprecated Eski concat-tabanlı şema — sadece legacy verify için. */
    private static function commitV0(string $choiceJson, string $salt, string $tokenPlain): string
    {
        return hash('sha256', $choiceJson . $salt . $tokenPlain);
    }
}
