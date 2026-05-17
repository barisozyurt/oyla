<?php
/**
 * Divan Paneli — Ana Sayfa
 */

$statusMeta = [
    'draft'  => ['label' => 'Taslak',   'class' => 'ds-badge--neutral'],
    'test'   => ['label' => 'Test',     'class' => 'ds-badge--warn'],
    'open'   => ['label' => 'Açık',     'class' => 'ds-badge--ink ds-badge--live'],
    'closed' => ['label' => 'Kapandı',  'class' => 'ds-badge--brass'],
];
$badge = $election
    ? ($statusMeta[$election['status']] ?? ['label' => $election['status'], 'class' => 'ds-badge--neutral'])
    : null;

$progressPct = $stats['total_members'] > 0
    ? (int) round($stats['voted_count'] / $stats['total_members'] * 100)
    : 0;
$canEdit = !$election || !in_array($election['status'], ['open', 'closed'], true);
?>

<header class="ds-page-header">
    <div class="ds-page-header__row">
        <div>
            <p class="ds-page-header__eyebrow">Divan</p>
            <h1 class="ds-page-header__title">Divan Paneli</h1>
            <?php if ($election): ?>
            <p class="ds-page-header__lead"><?= e($election['title']) ?></p>
            <?php else: ?>
            <p class="ds-page-header__lead">Aktif seçim bulunamadı.</p>
            <?php endif; ?>
        </div>
        <?php if ($badge): ?>
        <span class="ds-badge <?= $badge['class'] ?>"><?= e($badge['label']) ?></span>
        <?php endif; ?>
    </div>
</header>

<?php if (!$election): ?>
<div class="ds-alert ds-alert--warn">
    <i class="bi bi-exclamation-triangle ds-alert__icon" aria-hidden="true"></i>
    <div class="ds-alert__body">
        <p class="ds-alert__text">Henüz seçim oluşturulmamış. <a href="/yonetim">Yönetim Paneli</a>'nden seçim oluşturun.</p>
    </div>
</div>
<?php return; ?>
<?php endif; ?>

<section class="ds-grid ds-grid-cols-4 ds-grid-cols-md-2 ds-grid-cols-sm-2 ds-gap-4 ds-mb-8" aria-label="Sayısal özet">
    <article class="ds-stat ds-stat--char">
        <p class="ds-stat__label">Toplam Üye</p>
        <p class="ds-stat__value" id="total-members"><?= (int) $stats['total_members'] ?></p>
    </article>
    <article class="ds-stat ds-stat--brass">
        <p class="ds-stat__label">İmza Atan</p>
        <p class="ds-stat__value" id="signed-count"><?= (int) $stats['signed_count'] ?></p>
    </article>
    <article class="ds-stat ds-stat--ink">
        <p class="ds-stat__label">Oy Kullanan</p>
        <p class="ds-stat__value" id="voted-count"><?= (int) $stats['voted_count'] ?></p>
    </article>
    <article class="ds-stat ds-stat--ink">
        <p class="ds-stat__label">Katılım</p>
        <p class="ds-stat__value" id="participation-pct"><?= $stats['participation_pct'] ?><span class="ds-text-2xl ds-text-muted ds-font-serif" style="font-weight:400">%</span></p>
    </article>
</section>

<section class="ds-card ds-mb-8" aria-labelledby="progress-h">
    <header class="ds-card__header">
        <div>
            <h2 id="progress-h" class="ds-card__title">Oylama İlerlemesi</h2>
            <p class="ds-card__subtitle">Oy kullanan üye sayısı</p>
        </div>
        <span class="ds-font-mono ds-tabular ds-text-sm ds-text-body">
            <strong id="progress-numerator"><?= (int) $stats['voted_count'] ?></strong>
            <span class="ds-text-muted"> / </span>
            <strong id="progress-denominator"><?= (int) $stats['total_members'] ?></strong> üye
        </span>
    </header>
    <div class="ds-progress ds-progress--lg" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $progressPct ?>">
        <div class="ds-progress__bar" id="progress-bar" style="width: <?= $progressPct ?>%"></div>
    </div>
</section>

<div class="ds-grid ds-grid-cols-12 ds-gap-6 ds-grid-cols-md-1">

    <section class="ds-card" style="grid-column: span 5;" aria-labelledby="divan-h">
        <header class="ds-card__header">
            <div>
                <h2 id="divan-h" class="ds-card__title">Divan Kurulu</h2>
                <p class="ds-card__subtitle">Başkan, üyeler ve kâtip</p>
            </div>
        </header>

        <?php if (empty($divanMembers)): ?>
        <div class="ds-empty" style="padding: var(--s-8) var(--s-5)">
            <svg class="ds-empty__mark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="32" cy="22" r="10"/>
                <path d="M12 56c0-11 9-18 20-18s20 7 20 18"/>
            </svg>
            <p class="ds-empty__title">Henüz divan üyesi yok</p>
            <p class="ds-empty__text">Aşağıdan başkan, üye ve kâtip ekleyerek başlayın.</p>
        </div>
        <?php else: ?>
        <ul style="list-style:none;padding:0;margin:0;border-top:1px solid var(--line);">
            <?php foreach ($divanMembers as $dm):
                $roleLabel = match($dm['role']) {
                    'baskan' => 'Başkan',
                    'katip'  => 'Kâtip',
                    default  => 'Üye',
                };
                $roleBadge = match($dm['role']) {
                    'baskan' => 'ds-badge--ink',
                    'katip'  => 'ds-badge--brass',
                    default  => 'ds-badge--neutral',
                };
            ?>
            <li class="ds-flex ds-items-center ds-justify-between ds-gap-3" style="padding: var(--s-3) 0; border-bottom: 1px solid var(--line);">
                <div class="ds-flex ds-items-center ds-gap-3">
                    <span class="ds-avatar ds-avatar--ink"><?= e(mb_substr($dm['name'],0,1,'UTF-8')) ?></span>
                    <div>
                        <div class="ds-font-semi" style="color:var(--char-800)"><?= e($dm['name']) ?></div>
                        <span class="ds-badge <?= $roleBadge ?>"><?= e($roleLabel) ?></span>
                    </div>
                </div>
                <?php if ($canEdit): ?>
                <form method="POST" action="/divan/divan-remove/<?= (int) $dm['id'] ?>" onsubmit="return confirm('<?= e($dm['name']) ?>’ı divan kurulundan çıkarmak istediğinizden emin misiniz?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="ds-btn ds-btn--ghost ds-btn--sm" aria-label="Sil">
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                    </button>
                </form>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if ($canEdit): ?>
        <form method="POST" action="/divan/divan-store" class="ds-mt-6" style="border-top:1px dashed var(--line);padding-top:var(--s-5);">
            <?= csrf_field() ?>
            <div class="ds-grid ds-grid-cols-2 ds-gap-3 ds-mb-3">
                <div class="ds-field" style="margin-bottom:0">
                    <label for="dv-role" class="ds-field__label ds-field__label--required">Görev</label>
                    <select id="dv-role" name="role" class="ds-select" required>
                        <option value="">Seçiniz…</option>
                        <option value="baskan">Başkan</option>
                        <option value="uye">Üye</option>
                        <option value="katip">Kâtip</option>
                    </select>
                </div>
                <div class="ds-field" style="margin-bottom:0">
                    <label for="dv-name" class="ds-field__label ds-field__label--required">Ad Soyad</label>
                    <input id="dv-name" type="text" name="name" class="ds-input" placeholder="Örn: Ali Yılmaz" required maxlength="100">
                </div>
            </div>
            <button type="submit" class="ds-btn ds-btn--primary ds-w-full">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>Divan Üyesi Ekle
            </button>
        </form>
        <?php endif; ?>
    </section>

    <section class="ds-card" style="grid-column: span 7;" aria-labelledby="ballots-h">
        <header class="ds-card__header">
            <div>
                <h2 id="ballots-h" class="ds-card__title">Kurul Özeti</h2>
                <p class="ds-card__subtitle">Aday kotaları ve hazırlık durumu</p>
            </div>
        </header>

        <?php if (empty($ballots)): ?>
        <div class="ds-empty" style="padding: var(--s-8) var(--s-5)">
            <svg class="ds-empty__mark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="10" y="14" width="44" height="40" rx="3"/>
                <line x1="18" y1="26" x2="46" y2="26"/>
                <line x1="18" y1="36" x2="46" y2="36"/>
                <line x1="18" y1="46" x2="36" y2="46"/>
            </svg>
            <p class="ds-empty__title">Henüz seçim kurulu tanımlı değil</p>
            <p class="ds-empty__text">Yönetim panelinden Yönetim Kurulu, Denetleme Kurulu vb. ekleyin.</p>
            <a href="/yonetim/ballots" class="ds-btn ds-btn--secondary">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>Kurul Ekle
            </a>
        </div>
        <?php else: ?>
        <div class="ds-grid ds-grid-cols-2 ds-grid-cols-sm-1 ds-gap-3">
            <?php foreach ($ballots as $ballot):
                $candidateCount = (int) $ballot['candidate_count'];
                $quota          = (int) $ballot['quota'];
                $hasEnough      = $candidateCount >= $quota;
            ?>
            <article class="ds-card" style="padding:var(--s-5); border-color: <?= $hasEnough ? 'var(--ink-200)' : 'var(--line)' ?>;">
                <header class="ds-flex ds-items-start ds-justify-between ds-gap-3 ds-mb-3">
                    <div>
                        <h3 class="ds-font-serif ds-font-bold" style="font-size:var(--t-lg);margin:0;color:var(--char-800);"><?= e($ballot['title']) ?></h3>
                        <?php if (!empty($ballot['description'])): ?>
                        <p class="ds-text-xs ds-text-muted" style="margin:var(--s-1) 0 0;"><?= e($ballot['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($hasEnough): ?>
                    <i class="bi bi-check-circle" style="color:var(--ink-600);font-size:var(--t-lg);" title="Hazır" aria-hidden="true"></i>
                    <?php else: ?>
                    <i class="bi bi-exclamation-circle" style="color:var(--warn);font-size:var(--t-lg);" title="Eksik" aria-hidden="true"></i>
                    <?php endif; ?>
                </header>

                <dl class="ds-flex ds-gap-4 ds-text-xs ds-tabular" style="margin:0;color:var(--char-500);">
                    <div>
                        <dt style="text-transform:uppercase;letter-spacing:0.1em;color:var(--char-400);font-size:10px;">Kota</dt>
                        <dd class="ds-font-mono ds-font-semi ds-tabular" style="margin:0;color:var(--char-800);font-size:var(--t-md);"><?= $quota ?></dd>
                    </div>
                    <div>
                        <dt style="text-transform:uppercase;letter-spacing:0.1em;color:var(--char-400);font-size:10px;">Aday</dt>
                        <dd class="ds-font-mono ds-font-semi ds-tabular" style="margin:0;color:var(--char-800);font-size:var(--t-md);"><?= $candidateCount ?></dd>
                    </div>
                    <?php if ($ballot['yedek_quota'] > 0): ?>
                    <div>
                        <dt style="text-transform:uppercase;letter-spacing:0.1em;color:var(--char-400);font-size:10px;">Yedek</dt>
                        <dd class="ds-font-mono ds-font-semi ds-tabular" style="margin:0;color:var(--char-800);font-size:var(--t-md);"><?= (int) $ballot['yedek_quota'] ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>

                <?php if (!$hasEnough): ?>
                <p class="ds-text-xs ds-mt-3" style="margin:var(--s-3) 0 0;color:var(--warn);">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    <?= $quota - $candidateCount ?> aday daha gerekli
                </p>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<section class="ds-card ds-mt-8" aria-labelledby="control-h">
    <header class="ds-card__header">
        <div>
            <h2 id="control-h" class="ds-card__title">Seçim Kontrolü</h2>
            <p class="ds-card__subtitle">Açma, kapatma ve tutanak işlemleri</p>
        </div>
    </header>

    <?php if ($election['status'] === 'draft' || $election['status'] === 'test'): ?>
        <?php if (!$hasBaskan): ?>
        <div class="ds-alert ds-alert--warn">
            <i class="bi bi-exclamation-triangle ds-alert__icon" aria-hidden="true"></i>
            <div class="ds-alert__body"><p class="ds-alert__text">Divan başkanı atanmadı.</p></div>
        </div>
        <?php endif; ?>
        <?php if (!$hasBallots): ?>
        <div class="ds-alert ds-alert--warn">
            <i class="bi bi-exclamation-triangle ds-alert__icon" aria-hidden="true"></i>
            <div class="ds-alert__body"><p class="ds-alert__text">Hiç seçim kurulu tanımlanmamış.</p></div>
        </div>
        <?php endif; ?>
        <?php if ($hasBallots && !$allBallotsHaveQuota): ?>
        <div class="ds-alert ds-alert--warn">
            <i class="bi bi-exclamation-triangle ds-alert__icon" aria-hidden="true"></i>
            <div class="ds-alert__body"><p class="ds-alert__text">Bazı kurullarda yeterli aday yok.</p></div>
        </div>
        <?php endif; ?>

        <div class="ds-flex ds-gap-3 ds-mt-4 ds-flex-wrap">
            <form method="POST" action="/divan/start" onsubmit="return confirm('Seçimi başlatmak istediğinizden emin misiniz?');">
                <?= csrf_field() ?>
                <button type="submit" class="ds-btn ds-btn--primary ds-btn--lg" <?= $canStart ? '' : 'disabled' ?>>
                    <i class="bi bi-play-fill" aria-hidden="true"></i>Seçimi Başlat
                </button>
            </form>
            <a href="/sonuc" class="ds-btn ds-btn--secondary ds-btn--lg" target="_blank" rel="noopener">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>Sonuçları Görüntüle
            </a>
        </div>
    <?php elseif ($election['status'] === 'open'): ?>
        <div class="ds-flex ds-gap-3 ds-flex-wrap">
            <form method="POST" action="/divan/stop" onsubmit="return confirm('Seçimi kapatmak istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                <?= csrf_field() ?>
                <button type="submit" class="ds-btn ds-btn--danger ds-btn--lg">
                    <i class="bi bi-stop-fill" aria-hidden="true"></i>Seçimi Kapat
                </button>
            </form>
            <a href="/sonuc" class="ds-btn ds-btn--secondary ds-btn--lg" target="_blank" rel="noopener">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>Sonuçları Görüntüle
            </a>
        </div>
    <?php elseif ($election['status'] === 'closed'): ?>
        <div class="ds-alert ds-alert--success ds-mb-4">
            <i class="bi bi-check-circle ds-alert__icon" aria-hidden="true"></i>
            <div class="ds-alert__body">
                <p class="ds-alert__title">Seçim tamamlandı</p>
                <p class="ds-alert__text">Resmi tutanağı PDF olarak indirebilirsiniz.</p>
            </div>
        </div>
        <div class="ds-flex ds-gap-3 ds-flex-wrap">
            <a href="/admin/pdf" class="ds-btn ds-btn--brass ds-btn--lg">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>PDF Tutanak İndir
            </a>
            <a href="/sonuc" class="ds-btn ds-btn--secondary ds-btn--lg" target="_blank" rel="noopener">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>Resmi Sonuçlar
            </a>
        </div>
    <?php endif; ?>
</section>

<script src="<?= asset('js/divan.js') ?>"></script>
