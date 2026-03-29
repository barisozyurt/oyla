<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    <meta name="description" content="Oyla — Dijital Seçim Yönetim Sistemi">
    <title><?= e($pageTitle ?? 'Oyla') ?></title>

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
        /* Fullscreen layout: no scrollbar flash, dark-capable background */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            background-color: var(--oyla-fs-bg, #111827);
            color: var(--oyla-fs-color, #f9fafb);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Perde (curtain) modu: büyük font, yüksek kontrast */
        body.curtain-mode {
            --oyla-fs-bg: #0a0e1a;
            --oyla-fs-color: #ffffff;
            font-size: 1.15rem;
        }
        /* Oylama modu: açık arka plan, mobil öncelikli */
        body.voting-mode {
            --oyla-fs-bg: #f8fafc;
            --oyla-fs-color: #1e293b;
        }
    </style>
</head>
<body class="<?= e($bodyClass ?? '') ?>">

<?= $_content ?? '' ?>

<!-- Bootstrap 5.3 JS Bundle -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmcalR2P1HPBXRHoQfHXlgcMb2bj"
    crossorigin="anonymous"
></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
