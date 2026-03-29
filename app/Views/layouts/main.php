<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    <meta name="description" content="Oyla — Dijital Seçim Yönetim Sistemi">
    <title><?= e($pageTitle ?? 'Oyla') ?> &mdash; Dijital Seçim</title>

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
    <!-- Google Fonts: Source Sans 3 + JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,300;0,400;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <!-- Uygulama stilleri -->
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <!-- Logo & Marka -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/">
            <img
                src="<?= asset('img/logo.svg') ?>"
                alt="Oyla"
                width="30"
                height="30"
                onerror="this.style.display='none'"
            >
            Oyla
        </a>

        <!-- Hamburger toggle -->
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNav"
            aria-controls="mainNav"
            aria-expanded="false"
            aria-label="Menüyü aç/kapat"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav links -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <?php $user = $_SESSION['user'] ?? null; ?>
                <?php $role = $user['role'] ?? null; ?>

                <!-- Sonuç — herkese açık -->
                <li class="nav-item">
                    <a
                        class="nav-link <?= isUrl('/sonuc') ? 'active' : '' ?>"
                        href="/sonuc"
                    >
                        <i class="bi bi-bar-chart-fill me-1"></i>Sonuçlar
                    </a>
                </li>

                <?php if ($role === 'divan_baskani' || $role === 'admin'): ?>
                <li class="nav-item">
                    <a
                        class="nav-link <?= isUrl('/divan') ? 'active' : '' ?>"
                        href="/divan"
                    >
                        <i class="bi bi-person-badge me-1"></i>Divan
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/yonetim') ? 'active' : '' ?>"
                        href="#"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i class="bi bi-gear-fill me-1"></i>Yönetim
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/yonetim">
                                <i class="bi bi-people me-1"></i>Üyeler
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/yonetim/ballots">
                                <i class="bi bi-list-check me-1"></i>Kurullar &amp; Adaylar
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/yonetim/settings">
                                <i class="bi bi-sliders me-1"></i>Seçim Ayarları
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if ($role === 'gorevli' || $role === 'admin'): ?>
                <li class="nav-item">
                    <a
                        class="nav-link <?= isUrl('/gorevli') ? 'active' : '' ?>"
                        href="/gorevli"
                    >
                        <i class="bi bi-clipboard-check me-1"></i>Görevli Masası
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin') ? 'active' : '' ?>"
                        href="#"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i class="bi bi-shield-lock-fill me-1"></i>Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/admin">
                                <i class="bi bi-speedometer2 me-1"></i>Genel Bakış
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/elections">
                                <i class="bi bi-calendar-event me-1"></i>Seçimler
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/users">
                                <i class="bi bi-person-gear me-1"></i>Kullanıcılar
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/log">
                                <i class="bi bi-journal-text me-1"></i>İşlem Logu
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/system">
                                <i class="bi bi-hdd-network me-1"></i>Sistem Durumu
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/admin/test">
                                <i class="bi bi-bug me-1"></i>Test Modu
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/pdf">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Tutanak PDF
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

            </ul>

            <!-- Sağ taraf: oturum bilgisi -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if ($user !== null): ?>
                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle d-flex align-items-center gap-1"
                        href="#"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <i class="bi bi-person-circle"></i>
                        <?= e($user['name'] ?? 'Kullanıcı') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text text-muted small">
                                <?= e(match($role) {
                                    'admin'          => 'Sistem Yöneticisi',
                                    'divan_baskani'  => 'Divan Başkanı',
                                    'gorevli'        => 'Kayıt Görevlisi',
                                    default          => ucfirst($role ?? '')
                                }) ?>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/auth/logout">
                                <i class="bi bi-box-arrow-right me-1"></i>Çıkış Yap
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="/auth/login">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Giriş Yap
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash mesajları -->
<?php
$flashSuccess = getFlash('success');
$flashError   = getFlash('error');
$flashInfo    = getFlash('info');
?>
<?php if ($flashSuccess): ?>
<div class="container-fluid pt-3">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= e($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="container-fluid pt-3">
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
</div>
<?php endif; ?>
<?php if ($flashInfo): ?>
<div class="container-fluid pt-3">
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i><?= e($flashInfo) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
</div>
<?php endif; ?>

<!-- Ana içerik -->
<main class="flex-grow-1 py-4">
    <div class="container">
        <?= $_content ?? '' ?>
    </div>
</main>

<!-- Footer -->
<footer class="bg-white border-top py-3 mt-auto">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <span class="text-muted small">
            &copy; <?= date('Y') ?> Oyla &mdash; Dijital Seçim Yönetim Sistemi
        </span>
        <span class="text-muted small">
            Dernekler için dijital seçim sistemi
        </span>
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmcalR2P1HPBXRHoQfHXlgcMb2bj"
    crossorigin="anonymous"
></script>
<!-- Uygulama scripti -->
<script src="<?= asset('js/app.js') ?>"></script>
<?php
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (str_starts_with($uri, '/admin')):
?>
<script src="<?= asset('js/admin.js') ?>"></script>
<?php endif; ?>
</body>
</html>
