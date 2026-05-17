<?php
/**
 * Halka açık makbuz doğrulama — /oy/dogrula (ReceiptController)
 * Değişkenler: $csrf, $searched, $code, $found, $hash_prefix, $created_at, $malformed, $rate_limited
 */
$bodyClass = 'voting-mode';
?>
<main style="max-width:480px;margin:0 auto;padding:var(--s-12) var(--s-5) var(--s-16);">

    <header class="ds-text-center ds-mb-8">
        <div style="width:64px;height:64px;background:var(--brass-100);border-radius:50%;display:grid;place-items:center;margin:0 auto var(--s-4);">
            <i class="bi bi-patch-check" style="font-size:32px;color:var(--brass-700);" aria-hidden="true"></i>
        </div>
        <p style="font-family:var(--font-mono);font-size:var(--t-xs);text-transform:uppercase;letter-spacing:0.2em;color:var(--char-400);margin:0 0 var(--s-2);">Halka Açık</p>
        <h1 class="ds-font-serif" style="font-size:var(--t-2xl);font-weight:700;color:var(--char-800);margin:0 0 var(--s-2);">Oy Makbuzu Doğrulama</h1>
        <p class="ds-text-sm ds-text-muted" style="margin:0;">8 karakterlik makbuz kodunu girin; oyun sistemde kayıtlı olduğunu doğrulayın.</p>
    </header>

    <?php if (!empty($searched)): ?>
        <?php if (!empty($rate_limited)): ?>
        <div class="ds-alert ds-alert--warn ds-mb-5" role="alert">
            <i class="bi bi-hourglass-split ds-alert__icon" aria-hidden="true"></i>
            <div class="ds-alert__body">
                <p class="ds-alert__text">Çok fazla deneme yaptınız. Lütfen birkaç dakika sonra tekrar deneyin.</p>
            </div>
        </div>
        <?php elseif (!empty($malformed)): ?>
        <div class="ds-alert ds-alert--danger ds-mb-5" role="alert">
            <i class="bi bi-x-circle ds-alert__icon" aria-hidden="true"></i>
            <div class="ds-alert__body">
                <p class="ds-alert__text">Makbuz kodu 8 karakter olmalıdır (sadece harf ve rakam).</p>
            </div>
        </div>
        <?php elseif (!empty($found)): ?>
        <div class="ds-card ds-card--certificate ds-mb-5">
            <div class="ds-card__inner ds-text-center">
                <i class="bi bi-shield-check" style="font-size:36px;color:var(--ink-700);" aria-hidden="true"></i>
                <h2 class="ds-font-serif ds-mt-3" style="font-size:var(--t-xl);font-weight:600;color:var(--char-800);">Makbuz Doğrulandı</h2>
                <p class="ds-text-sm ds-text-muted" style="margin:var(--s-2) 0 var(--s-5);">Oyunuz sistemde mevcut ve bütünlüğü korunmuş.</p>

                <dl style="text-align:left;margin:0;font-size:var(--t-sm);">
                    <dt class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Makbuz kodu</dt>
                    <dd class="ds-font-mono ds-tabular ds-text-body" style="background:var(--paper-soft);padding:8px 12px;border-radius:var(--r-sm);margin:0 0 var(--s-3);font-size:var(--t-md);font-weight:600;"><?= e((string) ($code ?? '')) ?></dd>

                    <dt class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Commitment hash (önek)</dt>
                    <dd class="ds-font-mono ds-text-body" style="background:var(--paper-soft);padding:8px 12px;border-radius:var(--r-sm);margin:0 0 var(--s-3);font-size:var(--t-xs);word-break:break-all;"><?= e((string) ($hash_prefix ?? '')) ?>…</dd>

                    <?php if (!empty($created_at)): ?>
                    <dt class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Kaydedildi</dt>
                    <dd class="ds-font-mono ds-tabular ds-text-body" style="background:var(--paper-soft);padding:8px 12px;border-radius:var(--r-sm);margin:0;font-size:var(--t-sm);"><?= e((string) $created_at) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        <?php else: ?>
        <div class="ds-alert ds-alert--warn ds-mb-5" role="status">
            <i class="bi bi-question-circle ds-alert__icon" aria-hidden="true"></i>
            <div class="ds-alert__body">
                <p class="ds-alert__title">Bu kodla eşleşen makbuz bulunamadı</p>
                <p class="ds-alert__text">Lütfen kodu kontrol edip tekrar deneyin.</p>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="ds-card">
        <form method="POST" action="/oy/dogrula" autocomplete="off" novalidate>
            <?= $csrf ?>
            <div class="ds-field">
                <label for="code" class="ds-field__label ds-field__label--required">Makbuz kodu</label>
                <input type="text"
                       id="code"
                       name="code"
                       class="ds-input ds-input--lg ds-input--mono"
                       placeholder="A1B2C3D4"
                       value="<?= e((string) ($code ?? '')) ?>"
                       maxlength="8"
                       minlength="8"
                       inputmode="text"
                       autocorrect="off"
                       autocapitalize="characters"
                       spellcheck="false"
                       pattern="[A-Za-z0-9]{8}"
                       required>
                <p class="ds-field__hint">SMS ile gelen 8 karakterlik kod (harf+rakam).</p>
            </div>
            <button type="submit" class="ds-btn ds-btn--primary ds-btn--lg ds-w-full">
                <i class="bi bi-search" aria-hidden="true"></i>Doğrula
            </button>
        </form>
    </div>

    <p class="ds-text-xs ds-text-muted ds-text-center ds-mt-5" style="line-height:1.6;">
        Bu sayfa anonimliği bozmaz: hangi adaylara oy verdiğiniz <strong>asla</strong> gösterilmez.
    </p>
</main>

<script>
    document.getElementById('code').addEventListener('input', function () {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
    });
</script>
