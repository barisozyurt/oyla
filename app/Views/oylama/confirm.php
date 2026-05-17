<?php
/**
 * Oy Onay Ekranı
 * $public_code  string  — Makbuz kodu
 */
$bodyClass = 'voting-mode';
?>
<main style="max-width:480px;margin:0 auto;padding:var(--s-12) var(--s-5);text-align:center;color:var(--char-700);">

    <div style="width:88px;height:88px;background:var(--ink-50);border-radius:50%;display:grid;place-items:center;margin:0 auto var(--s-6);border:2px solid var(--ink-200);">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="var(--ink-700)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12.5L10 17.5L20 7"/>
        </svg>
    </div>

    <p style="font-family:var(--font-mono);font-size:var(--t-xs);text-transform:uppercase;letter-spacing:0.2em;color:var(--ink-700);margin:0 0 var(--s-3);">
        Tamamlandı
    </p>
    <h1 class="ds-font-serif" style="font-size:var(--t-3xl);font-weight:700;color:var(--char-800);margin:0 0 var(--s-3);">
        Oyunuz kullanıldı
    </h1>
    <p class="ds-text-muted ds-mb-8">
        Oyunuz kriptografik olarak güvence altında kaydedildi. Makbuz kodunuz SMS ile telefonunuza gönderildi.
    </p>

    <div class="ds-card ds-card--certificate ds-mb-6">
        <div class="ds-card__inner">
            <p class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.15em;margin:0 0 var(--s-3);">Makbuz Kodunuz</p>
            <p class="ds-font-mono ds-tabular" style="font-size:var(--t-3xl);font-weight:700;color:var(--ink-700);letter-spacing:0.15em;margin:0;word-break:break-all;">
                <?= e($public_code) ?>
            </p>
            <p class="ds-text-xs ds-text-muted ds-mt-4" style="line-height:1.6;">
                Bu kodu saklayın. Oy doğrulama sayfasında oyunuzun kayıt altına alındığını kontrol edebilirsiniz.
            </p>
        </div>
    </div>

    <a href="/oy/dogrula" class="ds-btn ds-btn--secondary ds-w-full ds-mb-4">
        <i class="bi bi-shield-check" aria-hidden="true"></i>Makbuz Kodunu Doğrula
    </a>

    <p class="ds-text-xs ds-text-muted ds-mt-6">
        <i class="bi bi-phone" aria-hidden="true"></i> Makbuz kodu SMS olarak telefonunuza gönderildi.
    </p>
</main>
