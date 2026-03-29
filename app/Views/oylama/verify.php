<?php
/**
 * Oy Doğrulama Ekranı — /oy/verify
 *
 * Değişkenler:
 *   $csrf      string   — CSRF gizli input HTML
 *   $searched  bool     — Form gönderildi mi?
 *   $code      string   — Aranan kod (searched=true ise)
 *   $found     bool     — Kod bulundu mu? (searched=true ise)
 */

$bodyClass = 'voting-mode';
?>
<style>
    .verify-wrapper {
        max-width: 420px;
        margin: 0 auto;
        padding: 48px 20px 40px;
        color: #1e293b;
    }

    .verify-header {
        text-align: center;
        margin-bottom: 32px;
    }

    .verify-icon {
        width: 72px;
        height: 72px;
        background: #eff6ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
    }

    .verify-icon svg {
        width: 38px;
        height: 38px;
    }

    .verify-heading {
        font-size: 1.4rem;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .verify-subtext {
        font-size: .88rem;
        color: #64748b;
    }

    /* Form */
    .verify-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px 20px;
        margin-bottom: 20px;
    }

    .verify-label {
        display: block;
        font-size: .85rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .verify-input {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #1e293b;
        outline: none;
        transition: border-color .15s;
        box-sizing: border-box;
    }
    .verify-input:focus { border-color: #1d4ed8; }
    .verify-input::placeholder { letter-spacing: normal; font-weight: 400; color: #94a3b8; }

    .btn-verify {
        width: 100%;
        background: #1d4ed8;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 14px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        margin-top: 16px;
        transition: opacity .15s;
    }
    .btn-verify:hover { opacity: .9; }

    /* Sonuç alertleri */
    .result-alert {
        border-radius: 10px;
        padding: 18px 20px;
        display: flex;
        gap: 14px;
        align-items: flex-start;
        font-size: .95rem;
        line-height: 1.5;
    }
    .result-alert.success {
        background: #f0fdf4;
        border: 1px solid #86efac;
        color: #15803d;
    }
    .result-alert.error {
        background: #fef2f2;
        border: 1px solid #fca5a5;
        color: #dc2626;
    }
    .result-alert-icon {
        flex-shrink: 0;
        margin-top: 1px;
    }
    .result-code-display {
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: .12em;
        margin-top: 4px;
    }

    /* Bilgi notu */
    .info-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
        font-size: .82rem;
        color: #475569;
        line-height: 1.6;
    }
    .info-box strong { color: #1e293b; }

    .vote-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: .85rem;
        text-decoration: none;
        margin-top: 20px;
    }
    .vote-link:hover { color: #1d4ed8; text-decoration: underline; }
</style>

<div class="verify-wrapper">

    <!-- Başlık -->
    <div class="verify-header">
        <div class="verify-icon">
            <svg viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 3L35 31H3L19 3Z" fill="#dbeafe" stroke="#1d4ed8"
                      stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M15 13l4 4 8-8" stroke="#1d4ed8" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="30" cy="28" r="7" fill="#16a34a"/>
                <path d="M27.5 28l2 2 3-3" stroke="white" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1 class="verify-heading">Oy Doğrulama</h1>
        <p class="verify-subtext">
            Makbuz kodunuzu girerek oyunuzun sayıldığını doğrulayın
        </p>
    </div>

    <!-- Arama sonucu -->
    <?php if ($searched ?? false): ?>
    <?php if ($found ?? false): ?>
    <div class="result-alert success mb-4">
        <div class="result-alert-icon">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24"
                 xmlns="http://www.w3.org/2000/svg">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                      stroke="currentColor" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div>
            <strong>Oyunuz sayılmıştır ✓</strong>
            <div class="result-code-display"><?= e($code ?? '') ?></div>
            <div style="font-size:.82rem; margin-top:4px; opacity:.8;">
                Bu makbuz kodu sistemimizde kayıtlıdır.
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="result-alert error mb-4">
        <div class="result-alert-icon">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24"
                 xmlns="http://www.w3.org/2000/svg">
                <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9
                         0 11-18 0 9 9 0 0118 0z"
                      stroke="currentColor" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div>
            <strong>Bu kod bulunamadı</strong>
            <?php if ($code ?? ''): ?>
            <div class="result-code-display"><?= e($code) ?></div>
            <?php endif; ?>
            <div style="font-size:.82rem; margin-top:4px; opacity:.8;">
                Lütfen kodu kontrol edip tekrar deneyin.
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Form -->
    <div class="verify-card">
        <form method="POST" action="/oy/verify" autocomplete="off">
            <?= $csrf ?>
            <label class="verify-label" for="verify-code">Makbuz Kodu</label>
            <input
                type="text"
                id="verify-code"
                name="code"
                class="verify-input"
                placeholder="Örn: A1B2C3D4"
                value="<?= e($code ?? '') ?>"
                maxlength="10"
                inputmode="text"
                autocorrect="off"
                autocapitalize="characters"
                spellcheck="false"
                required
            >
            <button type="submit" class="btn-verify">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                     style="vertical-align:middle; margin-right:6px"
                     xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                          stroke="currentColor" stroke-width="2"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Doğrula
            </button>
        </form>
    </div>

    <!-- Bilgi notu -->
    <div class="info-box">
        <strong>Makbuz kodu nedir?</strong><br>
        Oyunuzu kullandıktan sonra SMS ile gönderilen 8 karakterlik koddur.
        Bu kod, oyunuzun sisteme kaydedildiğini kanıtlar. Kimliğinizi veya
        hangi adaya oy verdiğinizi açığa çıkarmaz.
    </div>

    <div style="text-align:center">
        <a href="/" class="vote-link">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24"
                 xmlns="http://www.w3.org/2000/svg">
                <path d="M10 19l-7-7m0 0l7-7m-7 7h18"
                      stroke="currentColor" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Ana Sayfaya Dön
        </a>
    </div>

</div>

<script>
    // Girilen kodu otomatik büyük harfe çevir
    document.getElementById('verify-code').addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        this.setSelectionRange(pos, pos);
    });
</script>
