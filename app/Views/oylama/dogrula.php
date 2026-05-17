<?php
/**
 * Halka açık makbuz doğrulama sayfası — /oy/dogrula
 *
 * Değişkenler:
 *   $csrf        string
 *   $searched    bool
 *   $code        string (opsiyonel)
 *   $found       bool   (opsiyonel)
 *   $hash_prefix string (opsiyonel) — bulunduysa commitment hash'in ilk 12 karakteri
 *   $created_at  string (opsiyonel)
 *   $malformed   bool   (opsiyonel)
 *   $rate_limited bool  (opsiyonel)
 */
?>
<style>
    .receipt-page { max-width: 480px; margin: 48px auto; padding: 0 16px; }
    .receipt-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
    .receipt-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
    .receipt-icon.ok      { background: #dcfce7; color: #15803d; }
    .receipt-icon.notfound{ background: #fef3c7; color: #b45309; }
    .receipt-input { font-family: 'JetBrains Mono', ui-monospace, monospace; letter-spacing: .12em; text-transform: uppercase; font-size: 1.4rem; text-align: center; min-height: 48px; }
    .hash-prefix  { font-family: ui-monospace, monospace; word-break: break-all; background: #f1f5f9; padding: 8px 12px; border-radius: 6px; font-size: .85rem; }
</style>

<main class="receipt-page" role="main">
    <h1 class="h4 fw-bold text-center mb-4">Oy Makbuzu Doğrulama</h1>

    <div class="receipt-card">

        <?php if (!empty($searched) && !empty($rate_limited)): ?>
            <div class="alert alert-warning" role="alert">
                Çok fazla deneme yaptınız. Lütfen birkaç dakika sonra tekrar deneyin.
            </div>
        <?php elseif (!empty($searched) && !empty($malformed)): ?>
            <div class="alert alert-danger" role="alert">
                Makbuz kodu 8 karakter olmalıdır (sadece harf ve rakam).
            </div>
        <?php elseif (!empty($searched) && !empty($found)): ?>
            <div class="receipt-icon ok" aria-hidden="true">
                <i class="bi bi-shield-check" style="font-size:32px"></i>
            </div>
            <h2 class="h5 text-success text-center fw-bold">Makbuz Doğrulandı</h2>
            <p class="text-muted text-center small mt-2 mb-3">
                Oyunuz sistemde mevcut ve bütünlüğü korunmuş.
            </p>
            <dl class="mb-0">
                <dt class="small text-muted">Makbuz kodu</dt>
                <dd class="hash-prefix"><?= htmlspecialchars((string) ($code ?? ''), ENT_QUOTES) ?></dd>
                <dt class="small text-muted mt-3">Commitment hash (önek)</dt>
                <dd class="hash-prefix"><?= htmlspecialchars((string) ($hash_prefix ?? ''), ENT_QUOTES) ?>…</dd>
                <?php if (!empty($created_at)): ?>
                <dt class="small text-muted mt-3">Kaydedildi</dt>
                <dd class="hash-prefix"><?= htmlspecialchars((string) $created_at, ENT_QUOTES) ?></dd>
                <?php endif; ?>
            </dl>
        <?php elseif (!empty($searched) && empty($found)): ?>
            <div class="receipt-icon notfound" aria-hidden="true">
                <i class="bi bi-question-circle" style="font-size:32px"></i>
            </div>
            <h2 class="h5 text-warning text-center fw-bold">Bu kodla eşleşen makbuz bulunamadı</h2>
            <p class="text-muted text-center small mt-2">
                Lütfen kodu kontrol edip tekrar deneyin.
            </p>
        <?php endif; ?>

        <form method="POST" action="/oy/dogrula" class="mt-3" novalidate>
            <?= $csrf ?>
            <label for="code" class="form-label">8 karakterlik makbuz kodu</label>
            <input type="text"
                   id="code"
                   name="code"
                   value="<?= htmlspecialchars((string) ($code ?? ''), ENT_QUOTES) ?>"
                   class="form-control receipt-input"
                   autocomplete="off"
                   inputmode="text"
                   pattern="[A-Za-z0-9]{8}"
                   maxlength="8"
                   minlength="8"
                   required
                   aria-describedby="codeHelp">
            <div id="codeHelp" class="form-text">SMS ile gönderilen, harf ve rakamlardan oluşan kod.</div>
            <button type="submit" class="btn btn-primary w-100 mt-3">
                <i class="bi bi-search me-2" aria-hidden="true"></i>Doğrula
            </button>
        </form>
    </div>

    <p class="text-center text-muted small mt-3">
        Bu sayfa anonimliği bozmaz — hangi adaylara oy verdiğiniz ASLA gösterilmez.
    </p>
</main>
<script>
    document.getElementById('code').addEventListener('input', function (e) {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
    });
</script>
