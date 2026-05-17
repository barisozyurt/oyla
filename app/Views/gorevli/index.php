<?php
/**
 * Görevli Masası — Ana Sayfa
 */

$statusMeta = [
    'draft'  => ['label' => 'Taslak',   'class' => 'ds-badge--neutral'],
    'test'   => ['label' => 'Test',     'class' => 'ds-badge--warn'],
    'open'   => ['label' => 'Açık',     'class' => 'ds-badge--ink ds-badge--live'],
    'closed' => ['label' => 'Kapandı',  'class' => 'ds-badge--brass'],
];
$badge = $election ? ($statusMeta[$election['status']] ?? ['label' => $election['status'], 'class' => 'ds-badge--neutral']) : null;

$donePct   = $stats['total'] > 0 ? round($stats['done']   / $stats['total'] * 100) : 0;
$signedPct = $stats['total'] > 0 ? round($stats['signed'] / $stats['total'] * 100) : 0;
?>

<header class="ds-page-header">
    <div class="ds-page-header__row">
        <div>
            <p class="ds-page-header__eyebrow">Kayıt Masası</p>
            <h1 class="ds-page-header__title">Görevli Masası</h1>
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
    <div class="ds-alert__body"><p class="ds-alert__text">Henüz aktif bir seçim yok. <a href="/yonetim">Yönetim Paneli</a>'nden seçim oluşturun.</p></div>
</div>
<?php return; ?>
<?php endif; ?>

<?php if ($election['status'] !== 'open'): ?>
<div class="ds-alert ds-alert--info">
    <i class="bi bi-info-circle ds-alert__icon" aria-hidden="true"></i>
    <div class="ds-alert__body"><p class="ds-alert__text">Seçim henüz başlamadı veya kapandı. Görevli masası yalnızca seçim <strong>açık</strong> durumdayken aktiftir.</p></div>
</div>
<?php endif; ?>

<meta name="csrf-token" content="<?= e(csrf_token()) ?>">

<div class="ds-grid ds-grid-cols-12 ds-gap-6 ds-grid-cols-md-1">

    <div style="grid-column: span 8;">

        <section class="ds-card ds-mb-6" aria-labelledby="search-h">
            <header class="ds-card__header">
                <div>
                    <h2 id="search-h" class="ds-card__title">Üye Ara</h2>
                    <p class="ds-card__subtitle">TC kimlik veya sicil numarası ile</p>
                </div>
            </header>
            <div class="ds-flex ds-gap-3 ds-items-end ds-flex-wrap">
                <div class="ds-flex-1 ds-field" style="margin-bottom:0;min-width:240px;">
                    <label for="search-input" class="ds-field__label">TC Kimlik veya Sicil No</label>
                    <input id="search-input"
                           type="text"
                           class="ds-input ds-input--lg ds-input--mono"
                           placeholder="ör. 12345678901"
                           autocomplete="off"
                           inputmode="numeric"
                           maxlength="20">
                </div>
                <button class="ds-btn ds-btn--primary ds-btn--lg" id="search-btn" type="button">
                    <i class="bi bi-search" aria-hidden="true"></i>Ara
                </button>
                <button class="ds-btn ds-btn--ghost ds-btn--lg" id="reset-btn" type="button" style="display:none">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <p id="search-error" class="ds-text-sm ds-text-danger ds-mt-3" style="display:none;"></p>
        </section>

        <section id="member-card" class="ds-card ds-mb-6" style="display:none;" aria-labelledby="member-h">
            <header class="ds-card__header">
                <h2 id="member-h" class="ds-card__title">Üye Bilgileri</h2>
                <span id="member-status-badge" class="ds-badge ds-badge--neutral"></span>
            </header>
            <div class="ds-flex ds-items-center ds-gap-4">
                <div id="member-avatar" style="flex-shrink:0;">
                    <span class="ds-avatar ds-avatar--lg"><i class="bi bi-person" aria-hidden="true"></i></span>
                </div>
                <div class="ds-flex-1">
                    <h3 id="member-name" class="ds-font-serif ds-font-bold ds-text-xl" style="margin:0 0 var(--s-2);color:var(--char-800);">—</h3>
                    <div class="ds-flex ds-gap-4 ds-flex-wrap ds-text-sm ds-text-muted" id="member-details">
                        <span id="member-tc"></span>
                        <span id="member-sicil"></span>
                        <span id="member-phone"></span>
                    </div>
                </div>
            </div>
        </section>

        <section id="wizard-card" class="ds-card ds-mb-6" style="display:none;" aria-labelledby="wiz-h">
            <header class="ds-card__header">
                <div>
                    <h2 id="wiz-h" class="ds-card__title">İşlem Adımları</h2>
                    <p class="ds-card__subtitle">5 adımlı check-in akışı</p>
                </div>
            </header>

            <ol id="step-bar" class="ds-flex ds-justify-between ds-gap-3" style="list-style:none;margin:0 0 var(--s-6);padding:0;position:relative;">
                <div style="position:absolute;top:18px;left:8%;right:8%;height:1px;background:var(--line);z-index:0;"></div>
                <?php
                $steps = [
                    ['id' => 'step-verify',   'icon' => 'bi-person-check', 'label' => 'Kimlik'],
                    ['id' => 'step-sign1',    'icon' => 'bi-pen',          'label' => '1. İmza'],
                    ['id' => 'step-token',    'icon' => 'bi-qr-code',      'label' => 'Token'],
                    ['id' => 'step-vote-wait','icon' => 'bi-hourglass-split','label' => 'Oy'],
                    ['id' => 'step-sign2',    'icon' => 'bi-pen-fill',     'label' => '2. İmza'],
                ];
                foreach ($steps as $step):
                ?>
                <li id="<?= $step['id'] ?>" class="ds-text-center ds-flex-1" style="position:relative;z-index:1;">
                    <span class="step-circle" style="width:36px;height:36px;border:1.5px solid var(--line-strong);background:var(--paper-white);border-radius:50%;display:grid;place-items:center;margin:0 auto var(--s-2);color:var(--char-400);transition: all 180ms var(--ease);">
                        <i class="bi <?= $step['icon'] ?>" aria-hidden="true"></i>
                    </span>
                    <span class="ds-text-xs ds-text-muted" style="letter-spacing:0.04em;"><?= $step['label'] ?></span>
                </li>
                <?php endforeach; ?>
            </ol>

            <div id="action-area" class="ds-text-center" style="padding:var(--s-3) 0;">
                <!-- JS doldurur -->
            </div>

            <div id="qr-area" style="display:none;border-top:1px solid var(--line);padding-top:var(--s-5);margin-top:var(--s-5);text-align:center;">
                <p class="ds-text-sm ds-text-muted ds-mb-3">
                    <i class="bi bi-qr-code" aria-hidden="true"></i> Üyeye gösterin veya SMS gönderildi.
                </p>
                <img id="qr-image" src="" alt="QR Kod" style="max-width:200px;border:1px solid var(--line);border-radius:var(--r-sm);padding:var(--s-2);background:var(--paper-white);">
                <p class="ds-mt-3 ds-text-xs ds-text-muted">
                    <a id="vote-url-link" href="#" target="_blank" rel="noopener" class="ds-font-mono"></a>
                </p>
                <p class="ds-mt-2 ds-text-xs ds-text-muted">
                    <i class="bi bi-clock" aria-hidden="true"></i> Geçerlilik: <span id="token-expires" class="ds-tabular"></span>
                </p>
            </div>

            <div id="vote-waiting-area" style="display:none;border-top:1px solid var(--line);padding-top:var(--s-5);margin-top:var(--s-5);text-align:center;">
                <span class="ds-spinner" style="color:var(--ink-600);" aria-hidden="true"></span>
                <p class="ds-text-body ds-mt-3 ds-font-semi">Üyenin oy kullanması bekleniyor…</p>
                <p class="ds-text-xs ds-text-muted">Oy kullanıldığında ekran otomatik güncellenecek.</p>
            </div>

            <div id="done-area" style="display:none;border-top:1px solid var(--line);padding-top:var(--s-5);margin-top:var(--s-5);text-align:center;">
                <i class="bi bi-check-circle" style="font-size:var(--t-4xl);color:var(--ink-600);" aria-hidden="true"></i>
                <p class="ds-font-serif ds-font-bold ds-text-xl ds-mt-3" style="color:var(--ink-700);margin-bottom:var(--s-1);">İşlem Tamamlandı</p>
                <p class="ds-text-sm ds-text-muted">Üye oy kullandı ve işlem kayıt altına alındı.</p>
            </div>
        </section>

    </div>

    <aside style="grid-column: span 4;">

        <section class="ds-card ds-mb-4">
            <div class="ds-flex ds-justify-between ds-gap-3 ds-mb-3">
                <div class="ds-flex-1 ds-text-center">
                    <p class="ds-font-serif ds-font-bold ds-text-2xl ds-tabular" style="color:var(--char-500);margin:0;" id="stat-waiting"><?= (int) $stats['waiting'] ?></p>
                    <p class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.1em;margin:0;">Bekliyor</p>
                </div>
                <div class="ds-flex-1 ds-text-center">
                    <p class="ds-font-serif ds-font-bold ds-text-2xl ds-tabular" style="color:var(--brass-600);margin:0;" id="stat-signed"><?= (int) $stats['signed'] ?></p>
                    <p class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.1em;margin:0;">İmzalı</p>
                </div>
                <div class="ds-flex-1 ds-text-center">
                    <p class="ds-font-serif ds-font-bold ds-text-2xl ds-tabular" style="color:var(--ink-700);margin:0;" id="stat-done"><?= (int) $stats['done'] ?></p>
                    <p class="ds-text-xs ds-text-muted" style="text-transform:uppercase;letter-spacing:0.1em;margin:0;">Tamam</p>
                </div>
            </div>
            <div class="ds-progress" style="display:flex; height: 8px;">
                <div class="ds-progress__bar" id="bar-done" style="width:<?= $donePct ?>%; flex:0 0 <?= $donePct ?>%;"></div>
                <div class="ds-progress__bar ds-progress__bar--brass" id="bar-signed" style="width:<?= $signedPct ?>%; flex:0 0 <?= $signedPct ?>%;"></div>
            </div>
            <p class="ds-text-xs ds-text-muted ds-text-center ds-mt-2">Toplam: <strong class="ds-text-body ds-tabular" id="stat-total"><?= (int) $stats['total'] ?></strong> üye</p>
        </section>

        <section class="ds-card" style="padding: 0;" aria-labelledby="member-list-h">
            <header style="padding: var(--s-4) var(--s-5); border-bottom: 1px solid var(--line);">
                <h2 id="member-list-h" class="ds-card__title" style="margin:0;">Üye Listesi</h2>
            </header>
            <div class="ds-btn-group" style="padding: var(--s-3); border-bottom: 1px solid var(--line); display:flex;gap:4px;">
                <button class="ds-btn ds-btn--ghost ds-btn--sm ds-flex-1 member-filter is-active" data-filter="">Tümü</button>
                <button class="ds-btn ds-btn--ghost ds-btn--sm ds-flex-1 member-filter" data-filter="waiting">Bekliyor</button>
                <button class="ds-btn ds-btn--ghost ds-btn--sm ds-flex-1 member-filter" data-filter="signed">İmza</button>
                <button class="ds-btn ds-btn--ghost ds-btn--sm ds-flex-1 member-filter" data-filter="done">Tamam</button>
            </div>
            <div style="padding: var(--s-3); border-bottom: 1px solid var(--line);">
                <input type="text" id="list-filter-input" class="ds-input ds-input--mono" style="font-family:var(--font-sans);letter-spacing:normal;font-size:var(--t-sm);" placeholder="İsme göre filtrele…" autocomplete="off">
            </div>
            <ul id="member-list" style="list-style:none;margin:0;padding:0;max-height:380px;overflow-y:auto;">
                <?php foreach ($members as $m):
                    $dot = match($m['status']) {
                        'done'   => 'background:var(--ink-600);',
                        'signed' => 'background:var(--brass-600);',
                        default  => 'background:var(--char-300);',
                    };
                ?>
                <li>
                    <button type="button"
                            class="member-list-item ds-flex ds-items-center ds-gap-3"
                            data-member-id="<?= (int) $m['id'] ?>"
                            data-name="<?= e(mb_strtolower($m['name'])) ?>"
                            data-status="<?= e($m['status']) ?>"
                            style="width:100%;text-align:left;background:transparent;border:0;padding:var(--s-3) var(--s-4);border-bottom:1px solid var(--line);font-family:var(--font-sans);color:var(--char-700);cursor:pointer;transition:background var(--t-fast) var(--ease);">
                        <span style="width:8px;height:8px;border-radius:50%;<?= $dot ?>;flex-shrink:0;" aria-hidden="true"></span>
                        <span class="ds-flex-1 ds-text-sm" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($m['name']) ?></span>
                        <?php if ($m['sicil_no']): ?>
                        <span class="ds-text-xs ds-text-muted ds-font-mono"><?= e($m['sicil_no']) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <?php endforeach; ?>
                <?php if (empty($members)): ?>
                <li>
                    <div class="ds-empty" style="border:0;padding:var(--s-8) var(--s-5);">
                        <p class="ds-empty__text">Kayıtlı üye bulunmuyor.</p>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </section>
    </aside>
</div>

<style>
    .member-list-item:hover { background: var(--paper-soft); }
    .member-list-item:focus-visible { outline: 2px solid var(--ink-600); outline-offset: -2px; }
    .member-filter { letter-spacing: 0.02em; }
    .member-filter.is-active { background: var(--ink-50); color: var(--ink-800); border: 1px solid var(--ink-200); }

    /* Wizard adım state'leri — gorevli.js eski class'ları kullanacak ama biz DS uyumlu görüntü sağlıyoruz */
    .step-active .step-circle { border-color: var(--ink-600); color: var(--ink-700); background: var(--ink-50); }
    .step-done .step-circle   { border-color: var(--ink-600); color: var(--paper-white); background: var(--ink-600); }
</style>

<script src="<?= asset('js/gorevli.js') ?>"></script>
