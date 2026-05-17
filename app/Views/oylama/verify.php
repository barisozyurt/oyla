<?php
/**
 * Oy Doğrulama Ekranı — /oy/verify (eski endpoint)
 * Yeni alternatif endpoint: /oy/dogrula (ReceiptController)
 */
$bodyClass = 'voting-mode';
?>
<main style="max-width:460px;margin:0 auto;padding:var(--s-12) var(--s-5) var(--s-16);">

    <header class="ds-text-center ds-mb-8">
        <div style="width:64px;height:64px;background:var(--brass-100);border-radius:50%;display:grid;place-items:center;margin:0 auto var(--s-4);">
            <i class="bi bi-shield-check" style="font-size:32px;color:var(--brass-700);" aria-hidden="true"></i>
        </div>
        <p style="font-family:var(--font-mono);font-size:var(--t-xs);text-transform:uppercase;letter-spacing:0.2em;color:var(--char-400);margin:0 0 var(--s-2);">Doğrulama</p>
        <h1 class="ds-font-serif" style="font-size:var(--t-2xl);font-weight:700;color:var(--char-800);margin:0 0 var(--s-2);">Makbuz Kodu Doğrulama</h1>
        <p class="ds-text-sm ds-text-muted" style="margin:0;">SMS ile gelen kodu girerek oyunuzun kayıt altına alındığını doğrulayın.</p>
    </header>

    <?php if ($searched ?? false): ?>
        <?php if ($found ?? false): ?>
        <div class="ds-alert ds-alert--success ds-mb-5" role="status">
            <i class="bi bi-check-circle ds-alert__icon" aria-hidden="true"></i>
            <div class="ds-alert__body">
                <p class="ds-alert__title">Makbuz doğrulandı</p>
                <p class="ds-alert__text">Bu kod sistemimizde kayıtlıdır.</p>
                <p class="ds-font-mono ds-tabular" style="font-size:var(--t-lg);font-weight:600;color:var(--ink-700);letter-spacing:0.1em;margin:var(--s-2) 0 0;"><?= e($code ?? '') ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="ds-alert ds-alert--danger ds-mb-5" role="alert">
            <i class="bi bi-x-circle ds-alert__icon" aria-hidden="true"></i>
            <div class="ds-alert__body">
                <p class="ds-alert__title">Bu kodla eşleşen makbuz bulunamadı</p>
                <p class="ds-alert__text">Lütfen kodu kontrol edip tekrar deneyin.</p>
                <?php if ($code ?? ''): ?>
                <p class="ds-font-mono ds-tabular" style="font-size:var(--t-md);font-weight:600;color:var(--danger);letter-spacing:0.1em;margin:var(--s-2) 0 0;"><?= e($code) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="ds-card">
        <form method="POST" action="/oy/verify" autocomplete="off" novalidate>
            <?= $csrf ?>
            <div class="ds-field">
                <label for="verify-code" class="ds-field__label ds-field__label--required">Makbuz kodu</label>
                <input type="text"
                       id="verify-code"
                       name="code"
                       class="ds-input ds-input--lg ds-input--mono"
                       placeholder="A1B2C3D4"
                       value="<?= e($code ?? '') ?>"
                       maxlength="10"
                       inputmode="text"
                       autocorrect="off"
                       autocapitalize="characters"
                       spellcheck="false"
                       required>
                <p class="ds-field__hint">SMS ile gönderilen 8 karakterlik koddur (harf+rakam).</p>
            </div>
            <button type="submit" class="ds-btn ds-btn--primary ds-btn--lg ds-w-full">
                <i class="bi bi-search" aria-hidden="true"></i>Doğrula
            </button>
        </form>
    </div>

    <div class="ds-card ds-mt-5" style="background:var(--paper-soft);border-style:dashed;">
        <p class="ds-text-sm ds-text-body" style="margin:0;">
            <strong>Makbuz kodu nedir?</strong> Oy kullandıktan sonra SMS ile gelen 8 karakterlik koddur.
            Sisteme oyunuzun ulaştığını kanıtlar; kimliğinizi veya hangi adaya oy verdiğinizi açığa çıkarmaz.
        </p>
    </div>

    <p class="ds-text-center ds-mt-6">
        <a href="/" class="ds-text-muted ds-text-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Ana sayfaya dön
        </a>
    </p>
</main>

<script>
    document.getElementById('verify-code').addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        this.setSelectionRange(pos, pos);
    });
</script>
