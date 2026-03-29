<?php
/**
 * Token Geçersiz / Seçim Hatası Ekranı
 *
 * Değişkenler:
 *   $reason  string  — Hata kodu: invalid_token | election_not_open |
 *                      quota_exceeded | invalid_candidate | system_error
 */

$messages = [
    'invalid_token'      => 'Bu bağlantı geçersiz veya süresi dolmuş.',
    'election_not_open'  => 'Seçim henüz başlamamış veya kapanmış.',
    'quota_exceeded'     => 'Kota aşıldı. Lütfen tekrar deneyin.',
    'invalid_candidate'  => 'Geçersiz aday seçimi.',
    'system_error'       => 'Bir hata oluştu. Lütfen görevliye başvurun.',
];

$message = $messages[$reason ?? ''] ?? 'Bilinmeyen bir hata oluştu.';

$bodyClass = 'voting-mode';
?>
<style>
    .expired-wrapper {
        max-width: 420px;
        margin: 0 auto;
        padding: 64px 20px 40px;
        text-align: center;
        color: #1e293b;
    }

    .expired-icon {
        width: 88px;
        height: 88px;
        background: #fef2f2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 28px;
    }

    .expired-icon svg {
        width: 48px;
        height: 48px;
    }

    .expired-heading {
        font-size: 1.35rem;
        font-weight: 700;
        color: #dc2626;
        margin-bottom: 12px;
    }

    .expired-message {
        font-size: 1rem;
        color: #475569;
        line-height: 1.6;
        margin-bottom: 36px;
    }

    .btn-home {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #1d4ed8;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 13px 28px;
        font-size: .95rem;
        font-weight: 600;
        text-decoration: none;
        transition: opacity .15s;
    }
    .btn-home:hover { opacity: .9; text-decoration: none; color: #fff; }

    .error-code {
        display: inline-block;
        margin-top: 28px;
        font-size: .75rem;
        color: #94a3b8;
        font-family: monospace;
        background: #f1f5f9;
        padding: 4px 10px;
        border-radius: 4px;
    }
</style>

<div class="expired-wrapper">

    <!-- Uyarı ikonu -->
    <div class="expired-icon">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M24 4L44 40H4L24 4Z"
                  fill="#fecaca" stroke="#dc2626" stroke-width="2"
                  stroke-linejoin="round"/>
            <path d="M24 18V26" stroke="#dc2626" stroke-width="2.5"
                  stroke-linecap="round"/>
            <circle cx="24" cy="33" r="1.5" fill="#dc2626"/>
        </svg>
    </div>

    <?php if (($reason ?? '') === 'system_error'): ?>
    <h1 class="expired-heading">Sistem Hatası</h1>
    <?php elseif (($reason ?? '') === 'election_not_open'): ?>
    <h1 class="expired-heading">Seçim Aktif Değil</h1>
    <?php else: ?>
    <h1 class="expired-heading">Geçersiz Bağlantı</h1>
    <?php endif; ?>

    <p class="expired-message"><?= e($message) ?></p>

    <a href="/" class="btn-home">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
             xmlns="http://www.w3.org/2000/svg">
            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1
                     1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1
                     1 0 001 1m-6 0h6"
                  stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Ana Sayfaya Dön
    </a>

    <?php if ($reason ?? ''): ?>
    <div class="error-code">kod: <?= e($reason) ?></div>
    <?php endif; ?>

</div>
