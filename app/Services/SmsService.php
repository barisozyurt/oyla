<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;

/**
 * SMS servisi.
 *
 * GÜVENLİK NOTU (FAZ 1):
 * - Netgsm credential artık URL query string yerine POST body'de.
 * - Mock log'a yazılırken token URL'leri ve telefonun gövdesi maskelenir.
 * - Mock production'da çalışırsa Logger uyarısı düşer.
 */
class SmsService
{
    public function send(string $phone, string $message): bool
    {
        if (($_ENV['SMS_MOCK'] ?? 'true') === 'true') {
            if (Config::isProduction()) {
                Logger::warning('SMS_MOCK production ortamında aktif', [
                    'phone' => Logger::maskPhone($phone),
                ]);
            }
            return $this->mockSend($phone, $message);
        }
        return $this->sendNetgsm($phone, $message);
    }

    private function mockSend(string $phone, string $message): bool
    {
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0750, true);
        }

        $safePhone   = Logger::maskPhone($phone);
        $safeMessage = $this->maskSensitiveContent($message);

        $line = sprintf("[%s] TO: %s | MSG: %s\n", date('c'), $safePhone, $safeMessage);
        @file_put_contents($logDir . '/sms.log', $line, FILE_APPEND | LOCK_EX);
        return true;
    }

    private function sendNetgsm(string $phone, string $message): bool
    {
        $apiUrl = 'https://api.netgsm.com.tr/sms/send/get';
        $payload = [
            'usercode'  => $_ENV['NETGSM_USERNAME'] ?? '',
            'password'  => $_ENV['NETGSM_PASSWORD'] ?? '',
            'gsmno'     => $phone,
            'message'   => $message,
            'msgheader' => $_ENV['NETGSM_FROM'] ?? 'OYLA',
        ];

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Oyla/1.0',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $ok = $httpCode === 200 && str_starts_with((string) $response, '00');

        Logger::info('SMS gönderim sonucu', [
            'phone'    => Logger::maskPhone($phone),
            'http'     => $httpCode,
            'ok'       => $ok,
            'curl_err' => $err ?: null,
            // response ilk 12 karakter — credential leak'i önler
            'response_prefix' => is_string($response) ? substr($response, 0, 12) : null,
        ]);

        return $ok;
    }

    /**
     * Mesaj içindeki token URL'lerini ve makbuz kodlarını mask'le.
     */
    private function maskSensitiveContent(string $message): string
    {
        // /oy/<uuid> URL'ini maskele
        $message = preg_replace_callback(
            '#(/oy/)([a-f0-9-]{36})#i',
            fn($m) => $m[1] . Logger::maskToken($m[2]),
            $message
        );
        // 8 karakter A-F0-9 makbuz kodu
        $message = preg_replace_callback(
            '/\b([A-F0-9]{8})\b/',
            fn($m) => substr($m[1], 0, 2) . '***' . substr($m[1], -1),
            $message
        );
        return $message;
    }
}
