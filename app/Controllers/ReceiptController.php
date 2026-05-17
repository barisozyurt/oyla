<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\View;
use App\Models\Receipt;
use App\Services\ActivityLogService;

/**
 * Halka açık makbuz doğrulama endpoint'i (/oy/dogrula).
 *
 * Üye SMS'le aldığı 8-karakterli makbuz kodunu girer; sistem sadece
 * "var/yok" ve commitment_hash'in ilk 12 karakterini gösterir.
 * Hangi seçim, hangi seçmen vs. asla ifşa edilmez — anonimlik korunur.
 */
class ReceiptController extends Controller
{
    public function show(): void
    {
        View::layout('fullscreen', 'oylama/dogrula', [
            'csrf'     => $this->csrfField(),
            'searched' => false,
        ]);
    }

    public function check(): void
    {
        $this->verifyCsrf();

        // Brute-force koruması: IP başına 30/dk
        if (!RateLimiter::check('search')) {
            View::layout('fullscreen', 'oylama/dogrula', [
                'csrf'         => $this->csrfField(),
                'searched'     => true,
                'rate_limited' => true,
            ]);
            return;
        }

        $code = strtoupper(trim((string) $this->input('code', '')));
        // Sadece beklenen format: 8 karakter A-Z0-9
        if (!preg_match('/^[A-Z0-9]{8}$/', $code)) {
            View::layout('fullscreen', 'oylama/dogrula', [
                'csrf'      => $this->csrfField(),
                'code'      => $code,
                'searched'  => true,
                'found'     => false,
                'malformed' => true,
            ]);
            return;
        }

        $receiptModel = new Receipt();
        $receipt      = $receiptModel->findByCode($code);

        if ($receipt) {
            ActivityLogService::log(
                'receipt_verified_public',
                "Makbuz public lookup: {$code}",
                (int) $receipt['election_id']
            );
        }

        View::layout('fullscreen', 'oylama/dogrula', [
            'csrf'        => $this->csrfField(),
            'code'        => $code,
            'searched'    => true,
            'found'       => $receipt !== null,
            // Hash'in tamamı verilmez; sadece ilk 12 karakter doğrulama amaçlı
            'hash_prefix' => $receipt ? substr((string) $receipt['commitment_hash'], 0, 12) : null,
            'created_at'  => $receipt['created_at'] ?? null,
        ]);
    }
}
