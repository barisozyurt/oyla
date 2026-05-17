<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Basit structured-log yazıcı.
 *
 * PSR-3 imzasına uyumlu (log seviyeleri ve mesaj/context yapısı).
 * Çıktı: JSON satırları, /logs/app-YYYY-MM-DD.log dosyasına append.
 * Hassas alanlar (password, token, secret, _csrf) otomatik [REDACTED] ile maskelenir.
 */
final class Logger
{
    private const REDACTED = '[REDACTED]';

    private const SENSITIVE_KEYS = [
        'password', 'pwd', 'pass',
        'token', 'token_plain', 'token_hash',
        'secret', 'app_secret', 'token_secret',
        '_csrf', 'csrf', 'csrf_token',
        'authorization', 'cookie',
        'tc_kimlik', 'tc',
        'phone', 'gsmno',
        'netgsm_password',
    ];

    public static function emergency(string $message, array $context = []): void { self::log('emergency', $message, $context); }
    public static function alert(string $message, array $context = []): void { self::log('alert', $message, $context); }
    public static function critical(string $message, array $context = []): void { self::log('critical', $message, $context); }
    public static function error(string $message, array $context = []): void { self::log('error', $message, $context); }
    public static function warning(string $message, array $context = []): void { self::log('warning', $message, $context); }
    public static function notice(string $message, array $context = []): void { self::log('notice', $message, $context); }
    public static function info(string $message, array $context = []): void { self::log('info', $message, $context); }
    public static function debug(string $message, array $context = []): void { self::log('debug', $message, $context); }

    public static function log(string $level, string $message, array $context = []): void
    {
        $entry = [
            'ts'      => date('c'),
            'level'   => $level,
            'msg'     => $message,
            'context' => self::sanitize($context),
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            'uri'     => $_SERVER['REQUEST_URI'] ?? null,
            'pid'     => getmypid(),
        ];

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        $dir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $file = $dir . '/app-' . date('Y-m-d') . '.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

        // Test ortamında ya da CLI'da stderr'e de yaz.
        if (PHP_SAPI === 'cli' && in_array($level, ['emergency', 'alert', 'critical', 'error'], true)) {
            fwrite(STDERR, $line);
        }
    }

    /**
     * Hassas anahtarları recursive olarak [REDACTED] ile değiştir.
     */
    private static function sanitize(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            $lower = strtolower((string) $key);
            if (in_array($lower, self::SENSITIVE_KEYS, true)) {
                $clean[$key] = self::REDACTED;
                continue;
            }
            if (is_array($value)) {
                $clean[$key] = self::sanitize($value);
                continue;
            }
            // Pattern: 64-char hex (token hash) gibi şüpheli değerleri kısalt.
            if (is_string($value) && strlen($value) >= 32 && preg_match('/^[a-f0-9]{32,}$/i', $value)) {
                $clean[$key] = substr($value, 0, 6) . '…' . substr($value, -4);
                continue;
            }
            $clean[$key] = $value;
        }
        return $clean;
    }

    /**
     * Telefon numarasını maskele: 5321234567 → 532***4567.
     */
    public static function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return $phone;
        }
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) < 7) {
            return '***';
        }
        return substr($digits, 0, 3) . '***' . substr($digits, -4);
    }

    /**
     * Tek kullanımlık token / sırlı string maskele: ilk 4 + son 2.
     */
    public static function maskToken(string $token): string
    {
        $len = strlen($token);
        if ($len < 8) {
            return '***';
        }
        return substr($token, 0, 4) . '…' . substr($token, -2);
    }
}
