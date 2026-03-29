<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    <meta name="description" content="Oyla — Dijital Seçim Yönetim Sistemi">
    <title><?= e($pageTitle ?? 'Oyla') ?></title>

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

    <style>
        /* Fullscreen layout: no scrollbar flash, dark-capable background */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        body {
            --oyla-fs-bg:    #111827;
            --oyla-fs-color: #f9fafb;
            background-color: var(--oyla-fs-bg);
            color: var(--oyla-fs-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: "Source Sans 3", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        /* Perde (curtain) modu: büyük font, yüksek kontrast */
        body.curtain-mode {
            --oyla-fs-bg:    #0a0e1a;
            --oyla-fs-color: #ffffff;
            --oyla-primary:  #2dbe8c;
            --oyla-primary-dark: #1D9E75;
            font-size: 1.15rem;
        }
        /* Oylama modu: açık arka plan, mobil öncelikli */
        body.voting-mode {
            --oyla-fs-bg:    #f8f9fa;
            --oyla-fs-color: #1e293b;
            overflow: auto;
        }
        /* Reduce motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="<?= e($bodyClass ?? '') ?>">

<?= $_content ?? '' ?>

<!-- Bootstrap 5.3 JS Bundle -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
