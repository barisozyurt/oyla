<?php
$extraMessage = $extraMessage ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>403 Yetkisiz Erişim &mdash; Oyla</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-code { font-size: 7rem; font-weight: 800; line-height: 1; color: #ea580c; opacity: 0.18; }
    </style>
</head>
<body>
<main class="container text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="error-code mb-0" aria-hidden="true">403</div>
            <i class="bi bi-shield-exclamation display-1 text-warning mb-3 d-block" aria-hidden="true"></i>
            <h1 class="h3 fw-bold mb-2">Yetkisiz Erişim</h1>
            <p class="text-muted mb-4">
                Bu sayfayı görüntülemek için yeterli yetkiniz yok.
                <?php if (!empty($extraMessage)) echo '<br><small>' . htmlspecialchars($extraMessage, ENT_QUOTES) . '</small>'; ?>
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/" class="btn btn-primary"><i class="bi bi-house-fill me-2" aria-hidden="true"></i>Ana Sayfa</a>
                <a href="/auth/login" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Giriş Yap</a>
            </div>
        </div>
    </div>
</main>
</body>
</html>
