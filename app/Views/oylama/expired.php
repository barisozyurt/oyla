<?php
/**
 * Token Geçersiz / Seçim Hatası Ekranı
 *
 * Değişkenler:
 *   $reason  string  — Hata kodu
 */

$reasonMeta = [
    'invalid_token' => [
        'title'   => 'Bağlantı Süresi Doldu',
        'message' => 'Bu bağlantının geçerlilik süresi dolmuş ya da daha önce kullanılmış. Lütfen masadaki görevliye yeni bir bağlantı talep edin.',
        'icon'    => 'clock-history',
        'color'   => '#dc2626',
    ],
    'election_not_open' => [
        'title'   => 'Seçim Henüz Aktif Değil',
        'message' => 'Seçim henüz başlatılmamış veya kapatılmış. Lütfen divan başkanlığı ile iletişime geçin.',
        'icon'    => 'pause-circle',
        'color'   => '#ea580c',
    ],
    'quota_exceeded' => [
        'title'   => 'Çok Fazla Aday Seçildi',
        'message' => 'Bir kurulda kotadan fazla aday seçtiniz. Lütfen önceki sayfaya dönüp seçimlerinizi azaltın.',
        'icon'    => 'exclamation-triangle',
        'color'   => '#ea580c',
    ],
    'invalid_candidate' => [
        'title'   => 'Geçersiz Aday Seçimi',
        'message' => 'Form üzerinde geçersiz aday verisi tespit edildi. Lütfen sayfayı yenileyip tekrar deneyin.',
        'icon'    => 'shield-exclamation',
        'color'   => '#dc2626',
    ],
    'already_voted' => [
        'title'   => 'Bu Bağlantıyla Oy Kullanılmış',
        'message' => 'Bu oy bağlantısı zaten kullanılmış. Yeni bir oy kullanılamaz.',
        'icon'    => 'check2-circle',
        'color'   => '#1D9E75',
    ],
    'rate_limited' => [
        'title'   => 'Çok Fazla Deneme',
        'message' => 'Çok kısa sürede çok sayıda istek yaptınız. Lütfen birkaç dakika bekleyip tekrar deneyin.',
        'icon'    => 'hourglass-split',
        'color'   => '#ea580c',
    ],
    'system_error' => [
        'title'   => 'Sistem Hatası',
        'message' => 'Beklenmedik bir hata oluştu. Lütfen masadaki görevliye başvurun.',
        'icon'    => 'cone-striped',
        'color'   => '#dc2626',
    ],
];

$meta = $reasonMeta[$reason ?? ''] ?? [
    'title'   => 'Bilinmeyen Hata',
    'message' => 'Bilinmeyen bir hata oluştu.',
    'icon'    => 'question-circle',
    'color'   => '#64748b',
];

$bodyClass = 'voting-mode';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .expired-wrapper { max-width: 460px; margin: 0 auto; padding: 64px 20px 40px; text-align: center; color: #1e293b; }
    .expired-icon    { width: 96px; height: 96px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 44px; color: <?= htmlspecialchars($meta['color'], ENT_QUOTES) ?>; }
    .expired-heading { font-size: 1.5rem; font-weight: 700; color: <?= htmlspecialchars($meta['color'], ENT_QUOTES) ?>; margin-bottom: 12px; }
    .expired-message { font-size: 1rem; color: #475569; line-height: 1.6; margin-bottom: 32px; }
    .btn-home        { display: inline-flex; align-items: center; gap: 8px; background: #1D9E75; color: #fff; border: none; border-radius: 8px; padding: 14px 32px; font-size: 1rem; font-weight: 600; text-decoration: none; transition: opacity .15s; }
    .btn-home:hover  { opacity: .9; color: #fff; }
    .error-code      { display: inline-block; margin-top: 24px; font-size: .75rem; color: #94a3b8; font-family: monospace; background: #f1f5f9; padding: 4px 10px; border-radius: 4px; }
</style>

<main class="expired-wrapper" role="main">
    <div class="expired-icon" aria-hidden="true">
        <i class="bi bi-<?= htmlspecialchars($meta['icon'], ENT_QUOTES) ?>"></i>
    </div>

    <h1 class="expired-heading"><?= htmlspecialchars($meta['title'], ENT_QUOTES) ?></h1>
    <p class="expired-message"><?= htmlspecialchars($meta['message'], ENT_QUOTES) ?></p>

    <a href="/" class="btn-home">
        <i class="bi bi-house-fill" aria-hidden="true"></i>
        Ana Sayfaya Dön
    </a>

    <?php if (!empty($reason)): ?>
    <div class="error-code">kod: <?= htmlspecialchars((string) $reason, ENT_QUOTES) ?></div>
    <?php endif; ?>
</main>
