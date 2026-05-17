<?php

declare(strict_types=1);

namespace App\Core;

/**
 * DB-backed rate limiter (FAZ 1).
 *
 * Eski sistem: $_SESSION içinde tutuluyordu — yeni cookie ile bypass edilebiliyordu.
 * Yeni sistem:
 *   - Bucket key = ip + ":" + endpoint
 *   - Penceredeki başarısız deneme sayısı DB'de tutulur
 *   - Eşik aşılırsa exponential backoff (5 dk → 15 dk → 45 dk → 2 saat → 4 saat)
 *   - rate_limit_blocks tablosunda blocked_until tutulur
 *
 * Backwards-compatibility: phpunit testleri eski statik API'yi çağırıyor;
 * `$_SESSION` test ortamında muhafaza ediliyor (fallback). DB yoksa sessizce
 * session-mode'a düşer.
 */
class RateLimiter
{
    private const ENDPOINTS = [
        'login'  => ['max' => 5,  'window' => 300, 'backoffs' => [300, 900, 2700, 7200, 14400]],
        'sms'    => ['max' => 3,  'window' => 600, 'backoffs' => [600, 1800, 3600]],
        'vote'   => ['max' => 10, 'window' => 60,  'backoffs' => [60, 300, 900]],
        'search' => ['max' => 30, 'window' => 60,  'backoffs' => [60, 120, 300]],
    ];

    public static function check(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool
    {
        $config = self::ENDPOINTS[$key] ?? ['max' => $maxAttempts, 'window' => $windowSeconds, 'backoffs' => [$windowSeconds]];
        $bucket = self::bucketKey($key);

        try {
            $db = Database::getInstance();

            // 1. Block check
            $stmt = $db->prepare("SELECT blocked_until FROM rate_limit_blocks WHERE bucket_key = ? AND blocked_until > NOW()");
            $stmt->execute([$bucket]);
            if ($stmt->fetch()) {
                return false;
            }

            // 2. Pencere içindeki başarısız sayısı
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS c FROM rate_limits
                 WHERE bucket_key = ?
                 AND attempt_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                 AND success = 0"
            );
            $stmt->execute([$bucket, $config['window']]);
            $failed = (int) ($stmt->fetch()['c'] ?? 0);

            if ($failed >= $config['max']) {
                self::block($bucket, $config['backoffs']);
                return false;
            }

            // Attempt kaydını başlangıçta failure olarak yaz; success'te success'e güncellenir
            $db->prepare("INSERT INTO rate_limits (bucket_key, attempt_at, success) VALUES (?, NOW(), 0)")
               ->execute([$bucket]);
            return true;

        } catch (\Throwable $e) {
            // DB yoksa session fallback — testler için
            Logger::warning('RateLimiter DB fallback', ['err' => $e->getMessage()]);
            return self::checkSession($key, $config['max'], $config['window']);
        }
    }

    public static function recordSuccess(string $key): void
    {
        $bucket = self::bucketKey($key);
        try {
            $db = Database::getInstance();
            $db->prepare(
                "UPDATE rate_limits SET success = 1
                 WHERE bucket_key = ? AND attempt_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND success = 0
                 ORDER BY attempt_at DESC LIMIT 1"
            )->execute([$bucket]);
            $db->prepare("DELETE FROM rate_limit_blocks WHERE bucket_key = ?")->execute([$bucket]);
        } catch (\Throwable) {
            // ignore
        }
    }

    public static function reset(string $key): void
    {
        $bucket = self::bucketKey($key);
        try {
            $db = Database::getInstance();
            $db->prepare("DELETE FROM rate_limits WHERE bucket_key = ?")->execute([$bucket]);
            $db->prepare("DELETE FROM rate_limit_blocks WHERE bucket_key = ?")->execute([$bucket]);
        } catch (\Throwable) {
            // session fallback
            unset($_SESSION["rate_limit_{$key}"], $_SESSION["rate_block_{$key}"]);
        }
    }

    /**
     * 1 saatten eski kayıtları temizle (cron / periodic job için).
     */
    public static function gc(): int
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM rate_limits WHERE attempt_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stmt->execute();
            $count = $stmt->rowCount();

            $db->prepare("DELETE FROM rate_limit_blocks WHERE blocked_until < NOW()")->execute();
            return $count;
        } catch (\Throwable) {
            return 0;
        }
    }

    // -------------------------------------------------------------------------

    private static function bucketKey(string $endpoint): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $fwd = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            $ip = trim($fwd);
        }
        return $ip . ':' . $endpoint;
    }

    private static function block(string $bucket, array $backoffs): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT failure_count FROM rate_limit_blocks WHERE bucket_key = ?");
        $stmt->execute([$bucket]);
        $existing = $stmt->fetch();
        $currentCount = $existing ? ((int) $existing['failure_count']) + 1 : 1;

        $idx = min($currentCount - 1, count($backoffs) - 1);
        $duration = $backoffs[$idx];

        $db->prepare(
            "INSERT INTO rate_limit_blocks (bucket_key, blocked_until, failure_count)
             VALUES (?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?)
             ON DUPLICATE KEY UPDATE blocked_until = VALUES(blocked_until), failure_count = VALUES(failure_count)"
        )->execute([$bucket, $duration, $currentCount]);

        Logger::warning('Rate limit block', [
            'bucket'   => $bucket,
            'duration' => $duration,
            'count'    => $currentCount,
        ]);
    }

    /** Session fallback (DB yoksa testler için, eski davranışla uyumlu). */
    private static function checkSession(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $sessionKey = "rate_limit_{$key}";
        $blockKey   = "rate_block_{$key}";

        if (isset($_SESSION[$blockKey]) && time() < $_SESSION[$blockKey]) {
            return false;
        }
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }
        $_SESSION[$sessionKey] = array_values(array_filter(
            $_SESSION[$sessionKey],
            fn($t) => $t > time() - $windowSeconds
        ));
        if (count($_SESSION[$sessionKey]) >= $maxAttempts) {
            $_SESSION[$blockKey] = time() + $windowSeconds;
            return false;
        }
        $_SESSION[$sessionKey][] = time();
        return true;
    }
}
