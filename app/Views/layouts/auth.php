<?php
/**
 * Auth layout — login için.
 * Klasik "belge masası" hissi: sol panel marka tanıtımı, sağ panel form.
 */
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="theme-color" content="#faf8f3">
    <title><?= e($pageTitle ?? 'Giriş') ?> · Oyla</title>

    <link rel="icon" type="image/svg+xml" href="<?= asset('img/logo.svg') ?>">
    <link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">

    <style>
        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--paper);
        }
        @media (max-width: 880px) {
            .auth-shell { grid-template-columns: 1fr; }
            .auth-shell__left { display: none; }
        }

        /* Sol panel — kurumsal tanıtım, klasik baskı kompozisyonu */
        .auth-shell__left {
            background: var(--char-800);
            color: var(--paper);
            padding: var(--s-12) var(--s-10);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        .auth-shell__left::before {
            content: '';
            position: absolute;
            inset: var(--s-5);
            border: 1px solid rgba(184, 153, 104, 0.35);
            border-radius: var(--r-sm);
            pointer-events: none;
        }
        .auth-shell__brand {
            display: flex;
            align-items: center;
            gap: var(--s-3);
            position: relative;
            z-index: 1;
        }
        .auth-shell__brand svg { color: var(--brass-300); }
        .auth-shell__brand__name {
            font-family: var(--font-serif);
            font-weight: 700;
            font-size: var(--t-xl);
            color: var(--paper);
        }
        .auth-shell__brand__tag {
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--brass-300);
            margin-top: 2px;
            display: block;
        }
        .auth-shell__lead {
            margin-top: auto;
            position: relative;
            z-index: 1;
        }
        .auth-shell__lead h1 {
            font-family: var(--font-serif);
            font-weight: 600;
            font-size: clamp(var(--t-2xl), 3vw, var(--t-4xl));
            color: var(--paper);
            line-height: 1.2;
            margin: 0 0 var(--s-4);
        }
        .auth-shell__lead p {
            color: rgba(250, 248, 243, 0.7);
            font-size: var(--t-md);
            line-height: 1.7;
            max-width: 420px;
        }
        .auth-shell__meta {
            display: flex;
            gap: var(--s-8);
            margin-top: var(--s-10);
            border-top: 1px solid rgba(184, 153, 104, 0.25);
            padding-top: var(--s-5);
            position: relative;
            z-index: 1;
            font-size: var(--t-xs);
            color: var(--brass-300);
        }
        .auth-shell__meta dt {
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: rgba(184, 153, 104, 0.7);
            margin-bottom: var(--s-1);
        }
        .auth-shell__meta dd {
            margin: 0;
            color: var(--paper);
            font-family: var(--font-serif);
            font-weight: 600;
            font-size: var(--t-lg);
        }

        /* Sağ panel — form */
        .auth-shell__right {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--s-12) var(--s-6);
        }
        .auth-form {
            width: 100%;
            max-width: 400px;
        }
        .auth-form__brand-mobile {
            display: none;
            align-items: center;
            gap: var(--s-3);
            margin-bottom: var(--s-6);
        }
        @media (max-width: 880px) {
            .auth-form__brand-mobile { display: flex; }
        }
        .auth-form__title {
            font-family: var(--font-serif);
            font-size: var(--t-3xl);
            font-weight: 700;
            margin: 0 0 var(--s-2);
            color: var(--char-800);
        }
        .auth-form__sub {
            font-size: var(--t-sm);
            color: var(--char-500);
            margin-bottom: var(--s-8);
        }
    </style>
</head>
<body>

<div class="auth-shell">

    <aside class="auth-shell__left" aria-hidden="true">
        <div class="auth-shell__brand">
            <img src="<?= asset('img/logo-dark.svg') ?>" alt="" height="48" style="display:block">
        </div>

        <div class="auth-shell__lead">
            <h1>Genel kurullarda <em style="font-weight:400">kağıt pusula</em> dönemini kapatıyoruz.</h1>
            <p>
                5253 sayılı Dernekler Kanunu’na uygun, kriptografik doğrulamalı, anonimliği
                tasarım seviyesinde korunan dijital seçim altyapısı. Her oy bir hash, her
                makbuz bir mühür.
            </p>

            <dl class="auth-shell__meta">
                <div>
                    <dt>Sürüm</dt>
                    <dd>v0.2</dd>
                </div>
                <div>
                    <dt>Sistem</dt>
                    <dd>Hazır</dd>
                </div>
                <div>
                    <dt>Yıl</dt>
                    <dd><?= date('Y') ?></dd>
                </div>
            </dl>
        </div>
    </aside>

    <main class="auth-shell__right" role="main">
        <div class="auth-form">
            <div class="auth-form__brand-mobile">
                <img src="<?= asset('img/logo.svg') ?>" alt="Oyla" height="40">
            </div>

            <?php
            $flashError   = getFlash('error');
            $flashSuccess = getFlash('success');
            ?>
            <?php if ($flashError): ?>
            <div class="ds-alert ds-alert--danger" role="alert">
                <i class="bi bi-exclamation-triangle ds-alert__icon" aria-hidden="true"></i>
                <div class="ds-alert__body"><p class="ds-alert__text"><?= e($flashError) ?></p></div>
            </div>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
            <div class="ds-alert ds-alert--success" role="status">
                <i class="bi bi-check-circle ds-alert__icon" aria-hidden="true"></i>
                <div class="ds-alert__body"><p class="ds-alert__text"><?= e($flashSuccess) ?></p></div>
            </div>
            <?php endif; ?>

            <?= $_content ?? '' ?>
        </div>
    </main>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
