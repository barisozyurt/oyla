<?php
/**
 * Admin Paneli — Genel Bakış
 */

$statusMeta = [
    'draft'  => ['label' => 'Taslak',         'class' => 'ds-badge--neutral'],
    'test'   => ['label' => 'Test',           'class' => 'ds-badge--warn'],
    'open'   => ['label' => 'Açık',           'class' => 'ds-badge--ink ds-badge--live'],
    'closed' => ['label' => 'Kapandı',        'class' => 'ds-badge--brass'],
];
$badge = $currentElection
    ? ($statusMeta[$currentElection['status']] ?? ['label' => $currentElection['status'], 'class' => 'ds-badge--neutral'])
    : null;
?>

<header class="ds-page-header">
    <div class="ds-page-header__row">
        <div>
            <p class="ds-page-header__eyebrow">Genel Bakış</p>
            <h1 class="ds-page-header__title">Yönetim Paneli</h1>
            <p class="ds-page-header__lead">Sistem yöneticisi olarak tüm seçimleri, kullanıcıları ve sistem sağlığını buradan izleyin.</p>
        </div>
        <?php if ($currentElection && $badge): ?>
        <div class="ds-flex ds-gap-3 ds-items-center">
            <span class="ds-badge <?= $badge['class'] ?>"><?= e($badge['label']) ?></span>
        </div>
        <?php endif; ?>
    </div>
</header>

<section class="ds-grid ds-grid-cols-4 ds-grid-cols-md-2 ds-grid-cols-sm-2 ds-gap-4 ds-mb-8" aria-label="Sayısal özet">
    <article class="ds-stat ds-stat--ink">
        <p class="ds-stat__label">Seçim</p>
        <p class="ds-stat__value"><?= (int) $totalElections ?></p>
        <p class="ds-stat__sub">Sistemde tanımlı seçim sayısı</p>
    </article>
    <article class="ds-stat ds-stat--char">
        <p class="ds-stat__label">Kullanıcı</p>
        <p class="ds-stat__value"><?= (int) $totalUsers ?></p>
        <p class="ds-stat__sub">Admin · Divan · Görevli</p>
    </article>
    <article class="ds-stat ds-stat--brass">
        <p class="ds-stat__label">Üye</p>
        <p class="ds-stat__value"><?= (int) $totalMembers ?></p>
        <p class="ds-stat__sub">Aktif seçimde kayıtlı üye</p>
    </article>
    <article class="ds-stat ds-stat--ink">
        <p class="ds-stat__label">Kullanılan Oy</p>
        <p class="ds-stat__value"><?= (int) $totalVotes ?></p>
        <p class="ds-stat__sub">Toplam atılan oy sayısı</p>
    </article>
</section>

<?php if ($currentElection): ?>
<section class="ds-card ds-mb-8" aria-labelledby="active-election">
    <header class="ds-card__header">
        <div>
            <h2 id="active-election" class="ds-card__title">Aktif Seçim</h2>
            <p class="ds-card__subtitle">Sistemde varsayılan olarak işlem gören seçim</p>
        </div>
        <span class="ds-badge <?= $badge['class'] ?>"><?= e($badge['label']) ?></span>
    </header>

    <h3 class="ds-text-2xl ds-font-serif ds-font-bold" style="margin: 0 0 var(--s-2); color: var(--char-800);"><?= e($currentElection['title']) ?></h3>
    <?php if (!empty($currentElection['description'])): ?>
    <p class="ds-text-muted ds-mb-4"><?= e($currentElection['description']) ?></p>
    <?php endif; ?>

    <dl class="ds-grid ds-grid-cols-3 ds-grid-cols-md-1 ds-gap-4 ds-mt-6" style="font-size: var(--t-sm);">
        <div>
            <dt class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Durum</dt>
            <dd class="ds-font-semi" style="margin:0; color:var(--char-700);"><?= e($badge['label']) ?></dd>
        </div>
        <?php if (!empty($currentElection['started_at'])): ?>
        <div>
            <dt class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Başlangıç</dt>
            <dd class="ds-font-mono ds-tabular" style="margin:0;color:var(--char-700);"><?= e($currentElection['started_at']) ?></dd>
        </div>
        <?php endif; ?>
        <?php if (!empty($currentElection['closed_at'])): ?>
        <div>
            <dt class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Bitiş</dt>
            <dd class="ds-font-mono ds-tabular" style="margin:0;color:var(--char-700);"><?= e($currentElection['closed_at']) ?></dd>
        </div>
        <?php endif; ?>
    </dl>
</section>
<?php else: ?>
<div class="ds-alert ds-alert--warn ds-mb-8" role="alert">
    <i class="bi bi-exclamation-triangle ds-alert__icon" aria-hidden="true"></i>
    <div class="ds-alert__body">
        <p class="ds-alert__text">Henüz seçim oluşturulmamış. <a href="/admin/elections">Seçim oluşturun</a> ya da yönetim panelinden başlayın.</p>
    </div>
</div>
<?php endif; ?>

<section aria-labelledby="quick-access">
    <header class="ds-mb-4">
        <h2 id="quick-access" class="ds-font-serif ds-text-xl ds-font-bold" style="margin:0 0 var(--s-1);color:var(--char-800);">Hızlı Erişim</h2>
        <p class="ds-text-sm ds-text-muted" style="margin:0;">Sık kullanılan yönetim ekranları</p>
    </header>

    <div class="ds-grid ds-grid-cols-4 ds-grid-cols-md-2 ds-grid-cols-sm-1 ds-gap-4">
        <?php
        $tiles = [
            ['url' => '/admin/log',         'icon' => 'bi-journal-text',    'label' => 'Aktivite Logu',     'desc' => 'Tüm sistem işlem kayıtları'],
            ['url' => '/admin/log/verify',  'icon' => 'bi-shield-lock',     'label' => 'Log Bütünlüğü',     'desc' => 'Hash chain doğrulaması'],
            ['url' => '/admin/users',       'icon' => 'bi-person-gear',     'label' => 'Kullanıcı Yönetimi','desc' => 'Hesap ve rol yönetimi'],
            ['url' => '/admin/elections',   'icon' => 'bi-calendar-event',  'label' => 'Seçim Yönetimi',    'desc' => 'Seçim oluştur ve yönet'],
            ['url' => '/admin/system',      'icon' => 'bi-hdd-network',     'label' => 'Sistem Durumu',     'desc' => 'Bağlantı ve servis kontrolü'],
            ['url' => '/admin/hash-export', 'icon' => 'bi-filetype-csv',    'label' => 'Hash Dışa Aktar',   'desc' => 'Commitment CSV indirme'],
            ['url' => '/admin/pdf',         'icon' => 'bi-file-earmark-text','label'=> 'Tutanak (PDF)',     'desc' => 'Resmi seçim tutanağı'],
            ['url' => '/divan',             'icon' => 'bi-person-vcard',    'label' => 'Divan Paneli',      'desc' => 'Seçim yürütme ekranı'],
        ];
        if (!\App\Core\Config::isProduction()) {
            $tiles[] = ['url' => '/admin/test', 'icon' => 'bi-bug', 'label' => 'Test Modu', 'desc' => 'Yalnızca development'];
        }
        foreach ($tiles as $t):
        ?>
        <a href="<?= e($t['url']) ?>" class="ds-card ds-card--interactive">
            <div class="ds-flex ds-items-start ds-gap-3" style="margin-bottom: var(--s-4);">
                <span aria-hidden="true" style="width:36px;height:36px;display:grid;place-items:center;background:var(--paper-soft);border:1px solid var(--line);border-radius:var(--r-sm);color:var(--ink-700);font-size:var(--t-lg);">
                    <i class="bi <?= e($t['icon']) ?>"></i>
                </span>
            </div>
            <h3 class="ds-font-serif ds-font-bold" style="font-size:var(--t-lg);margin:0 0 var(--s-1);color:var(--char-800);"><?= e($t['label']) ?></h3>
            <p class="ds-text-sm ds-text-muted" style="margin:0;"><?= e($t['desc']) ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</section>
