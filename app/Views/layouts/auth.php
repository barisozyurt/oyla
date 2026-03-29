<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    <meta name="description" content="Oyla — Dijital Seçim Yönetim Sistemi">
    <title><?= e($pageTitle ?? 'Giriş') ?> &mdash; Oyla</title>

    <!-- Bootstrap 5.3 -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >
    <!-- Uygulama stilleri -->
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">

    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            min-height: 100vh;
        }
        .auth-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.2);
        }
        .auth-logo {
            width: 56px;
            height: 56px;
        }
        .auth-logo-fallback {
            width: 56px;
            height: 56px;
            background: #0d6efd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">

<div class="w-100" style="max-width: 420px; padding: 0 1rem;">

    <!-- Logo & başlık -->
    <div class="text-center mb-4">
        <img
            src="<?= asset('img/logo.svg') ?>"
            alt="Oyla"
            class="auth-logo mb-3"
            onerror="this.replaceWith(document.querySelector('.auth-logo-fallback'))"
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
        &copy; <?= date('Y') ?> Oyla &mdash; 5253 sayılı Dernekler Kanunu
    </p>

</div>

<!-- Bootstrap 5.3 JS Bundle -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmcalR2P1HPBXRHoQfHXlgcMb2bj"
    crossorigin="anonymous"
></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
