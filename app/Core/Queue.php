<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Basit dosya tabanlı kuyruk.
 *
 * - Her job storage/queue/pending/{ts}-{uuid}.json olarak yazılır.
 * - Worker pending dizinini tarar, sırayla işler, başarılıları done/, hataları failed/'a taşır.
 * - Atomik dosya taşıma race condition'a karşı koruma sağlar.
 *
 * Production'da Redis/SQS önerilir; bu küçük dernekler için yeterli.
 */
final class Queue
{
    private static string $baseDir = '';

    private static function dir(): string
    {
        if (self::$baseDir === '') {
            self::$baseDir = dirname(__DIR__, 2) . '/storage/queue';
        }
        foreach (['pending', 'processing', 'done', 'failed'] as $sub) {
            $d = self::$baseDir . '/' . $sub;
            if (!is_dir($d)) {
                @mkdir($d, 0750, true);
            }
        }
        return self::$baseDir;
    }

    /**
     * Job sıraya ekle.
     *
     * @param string $type    Job tipi (örn. 'send_sms', 'generate_pdf')
     * @param array  $payload Job için gereken veriler
     */
    public static function push(string $type, array $payload): string
    {
        $dir = self::dir();
        $id = date('YmdHis') . '-' . bin2hex(random_bytes(6));
        $file = $dir . '/pending/' . $id . '.json';
        $data = [
            'id'         => $id,
            'type'       => $type,
            'payload'    => $payload,
            'created_at' => date('c'),
            'attempts'   => 0,
        ];
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $id;
    }

    /**
     * Bir job al ve processing'e taşı. Race-safe.
     */
    public static function reserve(): ?array
    {
        $dir = self::dir();
        $files = glob($dir . '/pending/*.json') ?: [];
        sort($files);
        foreach ($files as $file) {
            $basename = basename($file);
            $processing = $dir . '/processing/' . $basename;
            // Atomic rename — başka worker aynı dosyayı alamaz
            if (@rename($file, $processing)) {
                $raw = @file_get_contents($processing);
                if ($raw === false) {
                    @unlink($processing);
                    continue;
                }
                $job = json_decode($raw, true);
                if (!is_array($job)) {
                    @rename($processing, $dir . '/failed/' . $basename);
                    continue;
                }
                $job['_file'] = $processing;
                return $job;
            }
        }
        return null;
    }

    public static function complete(array $job): void
    {
        $file = $job['_file'] ?? null;
        if (!$file) return;
        $done = self::dir() . '/done/' . basename($file);
        @rename($file, $done);
    }

    public static function fail(array $job, string $error): void
    {
        $file = $job['_file'] ?? null;
        if (!$file) return;
        $job['error'] = $error;
        $job['failed_at'] = date('c');
        unset($job['_file']);
        $failed = self::dir() . '/failed/' . basename($file);
        @file_put_contents($failed, json_encode($job, JSON_UNESCAPED_UNICODE), LOCK_EX);
        @unlink($file);
    }

    public static function stats(): array
    {
        $dir = self::dir();
        return [
            'pending'    => count(glob($dir . '/pending/*.json')    ?: []),
            'processing' => count(glob($dir . '/processing/*.json') ?: []),
            'done'       => count(glob($dir . '/done/*.json')       ?: []),
            'failed'     => count(glob($dir . '/failed/*.json')     ?: []),
        ];
    }
}
