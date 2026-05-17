<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>404 Sayfa Bulunamadı &mdash; Oyla</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-code { font-size: 7rem; font-weight: 800; line-height: 1; color: #1D9E75; opacity: 0.15; }
    </style>
</head>
<body>
<main class="container text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="error-code mb-0" aria-hidden="true">404</div>
            <i class="bi bi-compass display-1 text-primary mb-3 d-block" aria-hidden="true"></i>
            <h1 class="h3 fw-bold mb-2">Sayfa Bulunamadı</h1>
            <p class="text-muted mb-4">Aradığınız sayfa taşınmış, silinmiş ya da hiç var olmamış olabilir.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/" class="btn btn-primary"><i class="bi bi-house-fill me-2" aria-hidden="true"></i>Ana Sayfaya Dön</a>
                <button type="button" onclick="history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Geri Git</button>
            </div>
            <hr class="my-5">
            <p class="text-muted small mb-0">Oyla &mdash; Dijital Seçim Yönetim Sistemi</p>
        </div>
    </div>
</main>
</body>
</html>
