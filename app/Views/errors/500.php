<?php $extraMessage = $extraMessage ?? null; ?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>500 · Sistem Hatası · Oyla</title>
    <link rel="stylesheet" href="/assets/css/design-system.css">
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
</head>
<body style="min-height:100vh;display:grid;place-items:center;padding:var(--s-5);">
<main style="max-width:600px;text-align:center;">
    <p style="font-family:var(--font-mono);font-size:var(--t-xs);letter-spacing:0.2em;text-transform:uppercase;color:var(--danger);margin:0 0 var(--s-3);">Hata 500</p>
    <h1 class="ds-font-serif" style="font-size:clamp(var(--t-3xl),5vw,var(--t-5xl));font-weight:700;color:var(--char-800);margin:0 0 var(--s-4);">Bir şey ters gitti.</h1>
    <p style="color:var(--char-500);font-size:var(--t-md);line-height:1.7;margin-bottom:var(--s-6);">
        Beklenmedik bir hata oluştu. Sistem yöneticisine bildirildi.
        Lütfen birkaç dakika sonra tekrar deneyin; sorun devam ederse yönetimle iletişime geçin.
        <?php if ($extraMessage): ?><br><small><?= htmlspecialchars($extraMessage, ENT_QUOTES) ?></small><?php endif; ?>
    </p>
    <div class="ds-flex ds-gap-3 ds-justify-center ds-flex-wrap">
        <a href="/" class="ds-btn ds-btn--primary"><i class="bi bi-arrow-left" aria-hidden="true"></i>Ana Sayfa</a>
        <button type="button" onclick="location.reload()" class="ds-btn ds-btn--secondary"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i>Yeniden Dene</button>
    </div>
</main>
</body>
</html>
