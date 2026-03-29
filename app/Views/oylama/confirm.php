<?php
/**
 * Oy Onay Ekranı — Oylama tamamlandıktan sonra gösterilir
 *
 * Değişkenler:
 *   $public_code  string  — Makbuz kodu (SMS ile gönderilen)
 */

$bodyClass = 'voting-mode';
?>
<style>
    .confirm-wrapper {
        max-width: 440px;
        margin: 0 auto;
        padding: 48px 20px 40px;
        text-align: center;
        color: #1e293b;
    }

    .confirm-icon {
        width: 96px;
        height: 96px;
        background: #dcfce7;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 28px;
    }

    .confirm-icon svg {
        width: 52px;
        height: 52px;
    }

    .confirm-heading {
        font-size: 1.5rem;
        font-weight: 800;
        color: #15803d;
        margin-bottom: 8px;
    }

    .confirm-subtext {
        font-size: 1rem;
        color: #475569;
        margin-bottom: 36px;
    }

    .receipt-box {
        background: #f8fafc;
        border: 2px dashed #16a34a;
        border-radius: 12px;
        padding: 24px 20px;
        margin-bottom: 28px;
    }

    .receipt-label {
        font-size: .8rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #64748b;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .receipt-code {
        font-size: 2.2rem;
        font-weight: 900;
        letter-spacing: .15em;
        color: #15803d;
        font-variant-numeric: tabular-nums;
        word-break: break-all;
    }

    .receipt-note {
        font-size: .82rem;
        color: #475569;
        margin-top: 14px;
        line-height: 1.5;
    }

    .verify-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #1d4ed8;
        font-weight: 600;
        font-size: .9rem;
        text-decoration: none;
        padding: 10px 20px;
        border: 2px solid #dbeafe;
        border-radius: 8px;
        background: #eff6ff;
        transition: background .15s;
    }
    .verify-link:hover { background: #dbeafe; text-decoration: none; }

    .sms-notice {
        font-size: .82rem;
        color: #64748b;
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
</style>

<div class="confirm-wrapper">

    <!-- Büyük onay ikonu -->
    <div class="confirm-icon">
        <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="26" cy="26" r="26" fill="#16a34a"/>
            <path d="M14 26.5L21.5 34L38 18"
                  stroke="white" stroke-width="3.5"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

    <h1 class="confirm-heading">Oyunuz başarıyla kullanıldı</h1>
    <p class="confirm-subtext">
        Oyunuz güvenli biçimde kaydedildi ve makbuz kodunuz<br>
        SMS ile telefonunuza gönderildi.
    </p>

    <!-- Makbuz kodu kutusu -->
    <div class="receipt-box">
        <div class="receipt-label">Makbuz Kodunuz</div>
        <div class="receipt-code"><?= e($public_code) ?></div>
        <div class="receipt-note">
            Bu kodu saklayınız.<br>
            Oy doğrulama için kullanabilirsiniz.
        </div>
    </div>

    <!-- Doğrulama bağlantısı -->
    <a href="/oy/verify" class="verify-link">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
             xmlns="http://www.w3.org/2000/svg">
            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944
                     a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591
                     3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042
                     -.133-2.052-.382-3.016z"
                  stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Makbuz Kodunu Doğrula
    </a>

    <div class="sms-notice">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24"
             xmlns="http://www.w3.org/2000/svg">
            <path d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0
                     00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"
                  stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Makbuz kodu SMS ile gönderildi
    </div>

</div>
