<?php
$extraMessage = $extraMessage ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Bakım Modu &mdash; Oyla</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-code { font-size: 7rem; font-weight: 800; line-height: 1; color: #475569; opacity: 0.2; }
    </style>
</head>
<body>
<main class="container text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="error-code mb-0" aria-hidden="true">503</div>
            <i class="bi bi-tools display-1 text-secondary mb-3 d-block" aria-hidden="true"></i>
            <h1 class="h3 fw-bold mb-2">Sistem Bakımda</h1>
            <p class="text-muted mb-4">
                Şu anda sistem üzerinde planlı bakım çalışması yapılıyor. Lütfen birkaç dakika sonra tekrar deneyin.
                <?php if (!empty($extraMessage)) echo '<br><small>' . htmlspecialchars($extraMessage, ENT_QUOTES) . '</small>'; ?>
            </p>
            <a href="/" class="btn btn-primary"><i class="bi bi-arrow-clockwise me-2" aria-hidden="true"></i>Yeniden Dene</a>
        </div>
    </div>
</main>
</body>
</html>
