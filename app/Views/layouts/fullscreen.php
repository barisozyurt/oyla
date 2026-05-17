<?php
/**
 * Fullscreen layout — oylama, perde, hata sayfaları için.
 * Navbar/footer YOK. data-theme body sınıfından okunur.
 */
$bodyClass = $bodyClass ?? '';
$theme = str_contains($bodyClass, 'voting-mode') ? 'light' : 'dark';
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="theme-color" content="<?= $theme === 'dark' ? '#0c1217' : '#faf8f3' ?>">
    <title><?= e($pageTitle ?? 'Oyla') ?> · Oyla</title>

    <link rel="icon" type="image/svg+xml" href="<?= asset('img/logo.svg') ?>">
    <link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">

    <style>
        html, body { margin: 0; padding: 0; }
        body { min-height: 100vh; }
        /* Voting mode = light scroll OK */
        body.voting-mode { overflow-x: hidden; }
    </style>
</head>
<body class="<?= e($bodyClass) ?>">

<?= $_content ?? '' ?>

<div class="ds-toast-stack" id="ds-toast-stack" aria-live="polite" aria-atomic="false"></div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
