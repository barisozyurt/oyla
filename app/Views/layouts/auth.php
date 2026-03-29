<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    <meta name="description" content="Oyla — Dijital Seçim Yönetim Sistemi">
    <title><?= e($pageTitle ?? 'Giriş') ?> &mdash; Oyla</title>

    <!-- Google Fonts: Source Sans 3 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap"
    >
    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >
    <!-- Uygulama stilleri -->
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-page d-flex align-items-center justify-content-center py-5">

<div class="auth-page__container w-100">

    <!-- Logo & başlık -->
    <div class="text-center mb-4">
        <img
            src="<?= asset('img/logo.svg') ?>"
            alt="Oyla"
            class="auth-logo mb-3"
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
        >
        <div class="auth-logo-fallback" style="display:none">O</div>
        <h1 class="text-white fw-bold fs-3 mb-1">Oyla</h1>
        <p class="text-white-50 small mb-0">Dijital Seçim Yönetim Sistemi</p>
    </div>

    <!-- Flash mesajları -->
    <?php
    $flashError   = getFlash('error');
    $flashSuccess = getFlash('success');
    ?>
    <?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
    <?php endif; ?>
    <?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= e($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
    <?php endif; ?>

    <!-- Kart -->
    <div class="card auth-card">
        <div class="card-body p-4 p-md-5">
            <?= $_content ?? '' ?>
        </div>
    </div>

    <!-- Alt not -->
    <p class="text-center text-white-50 small mt-4 mb-0">
        &copy; <?= date('Y') ?> Oyla &mdash; Dernekler için dijital seçim sistemi
    </p>

</div>

<!-- Bootstrap 5.3 JS Bundle -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
