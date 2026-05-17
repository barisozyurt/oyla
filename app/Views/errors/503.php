<?php $extraMessage = $extraMessage ?? null; ?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Bakım Modunda · Oyla</title>
    <link rel="stylesheet" href="/assets/css/design-system.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body style="min-height:100vh;display:grid;place-items:center;padding:var(--s-5);">
<main style="max-width:560px;text-align:center;">
    <p style="font-family:var(--font-mono);font-size:var(--t-xs);letter-spacing:0.2em;text-transform:uppercase;color:var(--char-500);margin:0 0 var(--s-3);">Hata 503</p>
    <h1 class="ds-font-serif" style="font-size:clamp(var(--t-3xl),5vw,var(--t-5xl));font-weight:700;color:var(--char-800);margin:0 0 var(--s-4);">Sistem geçici olarak hizmet dışında.</h1>
    <p style="color:var(--char-500);font-size:var(--t-md);line-height:1.7;margin-bottom:var(--s-6);">
        Planlı bakım veya yoğunluk nedeniyle hizmet kısa süreliğine durakladı. Lütfen birkaç dakika sonra tekrar deneyin.
        <?php if ($extraMessage): ?><br><small><?= htmlspecialchars($extraMessage, ENT_QUOTES) ?></small><?php endif; ?>
    </p>
    <a href="/" class="ds-btn ds-btn--primary"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i>Yeniden Dene</a>
</main>
</body>
</html>
