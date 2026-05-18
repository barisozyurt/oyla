<?php
/**
 * Ana yetkili layout (admin/divan/görevli için).
 * Klasik kurumsal hissiyat — solid beyaz navbar, ivory page, hairline border.
 */
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? null;
$uri  = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="theme-color" content="#faf8f3">
    <meta name="description" content="Oyla — dernekler için dijital seçim yönetim sistemi">
    <title><?= e($pageTitle ?? 'Oyla') ?> · Oyla</title>

    <link rel="icon" type="image/svg+xml" href="<?= asset('img/logo.svg') ?>">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
</head>
<body class="ds-page">

<header class="ds-nav">
    <div class="ds-nav__inner">
        <a class="ds-nav__brand" href="/" aria-label="Oyla ana sayfa">
            <img src="<?= asset('img/logo-icon.svg') ?>" alt="" width="36" height="36" class="ds-nav__brand__mark">
            <span>
                <span class="ds-nav__brand__name">Oyla</span>
                <span class="ds-nav__brand__tag">Dernek Seçim Sistemi</span>
            </span>
        </a>

        <button type="button" class="ds-nav__toggle" aria-label="Menü" data-toggle="ds-nav__links">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <ul class="ds-nav__links" id="ds-nav__links">
            <li>
                <a class="ds-nav__link <?= isUrl('/sonuc') || isUrl('/') ? 'ds-nav__link--active' : '' ?>" href="/sonuc">
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>Sonuçlar
                </a>
            </li>
            <?php if ($role === 'divan_baskani' || $role === 'admin'): ?>
            <li>
                <a class="ds-nav__link <?= isUrl('/divan') ? 'ds-nav__link--active' : '' ?>" href="/divan">
                    <i class="bi bi-person-vcard" aria-hidden="true"></i>Divan
                </a>
            </li>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
            <li class="ds-nav__group">
                <a class="ds-nav__link <?= str_starts_with($uri, '/yonetim') ? 'ds-nav__link--active' : '' ?>" href="#" aria-haspopup="true">
                    <i class="bi bi-folder2-open" aria-hidden="true"></i>Yönetim<i class="bi bi-chevron-down" aria-hidden="true" style="font-size: 10px"></i>
                </a>
                <ul class="ds-nav__menu" role="menu">
                    <li><a href="/yonetim"><i class="bi bi-people" aria-hidden="true"></i>Üyeler</a></li>
                    <li><a href="/yonetim/ballots"><i class="bi bi-ui-checks" aria-hidden="true"></i>Kurullar &amp; Adaylar</a></li>
                    <li><a href="/yonetim/settings"><i class="bi bi-sliders2" aria-hidden="true"></i>Seçim Ayarları</a></li>
                </ul>
            </li>
            <?php endif; ?>
            <?php if ($role === 'gorevli' || $role === 'admin'): ?>
            <li>
                <a class="ds-nav__link <?= isUrl('/gorevli') ? 'ds-nav__link--active' : '' ?>" href="/gorevli">
                    <i class="bi bi-clipboard2-check" aria-hidden="true"></i>Görevli Masası
                </a>
            </li>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
            <li class="ds-nav__group">
                <a class="ds-nav__link <?= str_starts_with($uri, '/admin') ? 'ds-nav__link--active' : '' ?>" href="#" aria-haspopup="true">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>Yönetici<i class="bi bi-chevron-down" aria-hidden="true" style="font-size: 10px"></i>
                </a>
                <ul class="ds-nav__menu" role="menu">
                    <li><a href="/admin"><i class="bi bi-grid-1x2" aria-hidden="true"></i>Genel Bakış</a></li>
                    <li><a href="/admin/elections"><i class="bi bi-calendar-event" aria-hidden="true"></i>Seçimler</a></li>
                    <li><a href="/admin/users"><i class="bi bi-people-fill" aria-hidden="true"></i>Kullanıcılar</a></li>
                    <li><a href="/admin/log"><i class="bi bi-journal-text" aria-hidden="true"></i>Aktivite Logu</a></li>
                    <li><a href="/admin/log/verify"><i class="bi bi-shield-lock" aria-hidden="true"></i>Log Bütünlüğü</a></li>
                    <li><a href="/admin/system"><i class="bi bi-hdd-network" aria-hidden="true"></i>Sistem Durumu</a></li>
                    <li><hr></li>
                    <?php if (!\App\Core\Config::isProduction()): ?>
                    <li><a href="/admin/test"><i class="bi bi-bug" aria-hidden="true"></i>Test Modu</a></li>
                    <?php endif; ?>
                    <li><a href="/admin/pdf"><i class="bi bi-file-earmark-text" aria-hidden="true"></i>Tutanak (PDF)</a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>

        <?php if ($user !== null): ?>
        <div class="ds-nav__group">
            <a class="ds-nav__user" href="#" aria-haspopup="true">
                <span class="ds-nav__user__chip" aria-hidden="true"><?= e(mb_substr($user['name'] ?? 'O', 0, 1, 'UTF-8')) ?></span>
                <span><?= e($user['name'] ?? 'Kullanıcı') ?></span>
                <svg width="10" height="10" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <ul class="ds-nav__menu" role="menu">
                <li>
                    <div style="padding:var(--s-3) var(--s-3); border-bottom:1px solid var(--line); margin-bottom:var(--s-2);">
                        <div style="font-weight:600;font-size:var(--t-sm);color:var(--char-800)"><?= e($user['name']) ?></div>
                        <div style="font-size:var(--t-xs);color:var(--char-400)">
                            <?= e(match($role) {
                                'admin'         => 'Sistem Yöneticisi',
                                'divan_baskani' => 'Divan Başkanı',
                                'gorevli'       => 'Kayıt Görevlisi',
                                default         => $role,
                            }) ?>
                        </div>
                    </div>
                </li>
                <li><a href="/auth/logout" class="ds-text-danger"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Çıkış Yap</a></li>
            </ul>
        </div>
        <?php else: ?>
        <a class="ds-btn ds-btn--ghost" href="/auth/login">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M10 2h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M6 5l3 3-3 3M9 8H2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Giriş
        </a>
        <?php endif; ?>
    </div>
</header>

<?php
$flashSuccess = getFlash('success');
$flashError   = getFlash('error');
$flashInfo    = getFlash('info');
?>
<?php if ($flashSuccess || $flashError || $flashInfo): ?>
<div class="ds-container ds-mt-4">
    <?php if ($flashSuccess): ?>
    <div class="ds-alert ds-alert--success" role="status">
        <i class="bi bi-check-circle ds-alert__icon" aria-hidden="true"></i>
        <div class="ds-alert__body"><p class="ds-alert__text"><?= e($flashSuccess) ?></p></div>
    </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
    <div class="ds-alert ds-alert--danger" role="alert">
        <i class="bi bi-exclamation-triangle ds-alert__icon" aria-hidden="true"></i>
        <div class="ds-alert__body"><p class="ds-alert__text"><?= e($flashError) ?></p></div>
    </div>
    <?php endif; ?>
    <?php if ($flashInfo): ?>
    <div class="ds-alert ds-alert--info" role="status">
        <i class="bi bi-info-circle ds-alert__icon" aria-hidden="true"></i>
        <div class="ds-alert__body"><p class="ds-alert__text"><?= e($flashInfo) ?></p></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<main class="ds-main" role="main">
    <div class="ds-container">
        <?= $_content ?? '' ?>
    </div>
</main>

<footer class="ds-footer">
    <div class="ds-footer__inner">
        <span>
            <a href="https://mirket.io" target="_blank" rel="noopener" class="ds-footer__mark" style="color:inherit;text-decoration:none;">mirket.io</a>
        </span>
        <span>Türk derneklerinin organ seçimleri için tasarlandı.</span>
    </div>
</footer>

<div class="ds-toast-stack" id="ds-toast-stack" aria-live="polite" aria-atomic="false"></div>

<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/nav.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (str_starts_with($uri, '/admin')): ?>
<script src="<?= asset('js/admin.js') ?>"></script>
<?php endif; ?>
</body>
</html>
