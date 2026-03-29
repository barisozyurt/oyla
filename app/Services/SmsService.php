<?php
declare(strict_types=1);

namespace App\Services;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        if (($_ENV['SMS_MOCK'] ?? 'true') === 'true') {
            return $this->mockSend($phone, $message);
        }
        return $this->sendNetgsm($phone, $message);
    }

    private function mockSend(string $phone, string $message): bool
    {
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $line = sprintf("[%s] TO: %s | MSG: %s\n", date('Y-m-d H:i:s'), $phone, $message);
        file_put_contents($logDir . '/sms.log', $line, FILE_APPEND | LOCK_EX);
        return true;
    }

    private function sendNetgsm(string $phone, string $message): bool
    {
        $params = http_build_query([
            'usercode' => $_ENV['NETGSM_USERNAME'] ?? '',
            'password' => $_ENV['NETGSM_PASSWORD'] ?? '',
            'gsmno' => $phone,
            'message' => $message,
            'msgheader' => $_ENV['NETGSM_FROM'] ?? 'OYLA',
        ]);

        $ch = curl_init('https://api.netgsm.com.tr/sms/send/get?' . $params);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200 && str_starts_with((string) $response, '00');
    }
}
