<?php
/**
 * Görevli Masası — Ana Sayfa
 *
 * Değişkenler:
 *   $election  array|null
 *   $members   array
 *   $stats     array  (total, waiting, signed, done)
 *   $user      array
 *   $csrf      string  (hidden input HTML)
 */
?>

<!-- Başlık -->
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="fw-bold mb-1" style="font-size: 1.9rem;">
            <i class="bi bi-clipboard-check-fill text-primary me-2"></i>Görevli Masası
        </h1>
        <?php if ($election): ?>
        <p class="text-muted mb-0 fs-5"><?= e($election['title']) ?></p>
        <?php else: ?>
        <p class="text-muted mb-0">Aktif seçim bulunamadı.</p>
        <?php endif; ?>
    </div>

    <?php if ($election): ?>
    <?php
        $statusMap = [
            'draft'  => ['label' => 'Taslak',  'class' => 'bg-secondary'],
            'test'   => ['label' => 'Test',     'class' => 'bg-warning text-dark'],
            'open'   => ['label' => 'Açık',     'class' => 'bg-success'],
            'closed' => ['label' => 'Kapalı',   'class' => 'bg-danger'],
        ];
        $si = $statusMap[$election['status']] ?? ['label' => $election['status'], 'class' => 'bg-secondary'];
    ?>
    <span class="badge <?= $si['class'] ?> fs-5 px-3 py-2 align-self-start">
        <?= $si['label'] ?>
    </span>
    <?php endif; ?>
</div>

<?php if (!$election): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Henüz aktif bir seçim yok. Lütfen <a href="/yonetim" class="alert-link">Yönetim Paneli</a>'nden seçim oluşturun.
</div>
<?php return; ?>
<?php endif; ?>

<?php if ($election['status'] !== 'open'): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle-fill me-2"></i>
    Seçim henüz başlamadı veya kapandı. Görevli masası yalnızca seçim <strong>açık</strong> durumdayken aktiftir.
</div>
<?php endif; ?>

<!-- CSRF meta etiketi (JS için) -->
<meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">

<div class="row g-4">

    <!-- ===== SOL / ANA PANEL ===== -->
    <div class="col-lg-8">

        <!-- Arama Kartı -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom fw-bold fs-5 py-3">
                <i class="bi bi-search text-primary me-2"></i>Üye Ara
            </div>
            <div class="card-body py-4">
                <div class="input-group input-group-lg">
                    <input
                        type="text"
                        id="search-input"
                        class="form-control"
                        placeholder="TC Kimlik No veya Sicil No girin…"
                        autocomplete="off"
                        maxlength="20"
                    >
                    <button class="btn btn-primary px-4" id="search-btn" type="button">
                        <i class="bi bi-search me-1"></i>Ara
                    </button>
                    <button class="btn btn-outline-secondary" id="reset-btn" type="button" style="display:none;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div id="search-error" class="text-danger small mt-2" style="display:none;"></div>
            </div>
        </div>

        <!-- Üye Kartı (başlangıçta gizli) -->
        <div id="member-card" class="card border-0 shadow-sm mb-4" style="display:none;">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <span class="fw-bold fs-5">
                    <i class="bi bi-person-fill text-primary me-2"></i>Üye Bilgileri
                </span>
                <span id="member-status-badge" class="badge bg-secondary fs-6 px-3 py-2"></span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-4">
                    <!-- Avatar -->
                    <div id="member-avatar" class="flex-shrink-0">
                        <svg class="rounded-circle" viewBox="0 0 64 64" width="64" height="64">
                            <rect width="64" height="64" rx="32" fill="#E9ECEF"/>
                            <circle cx="32" cy="24" r="11" fill="#B4B2A9"/>
                            <path d="M8 60c0-13 10.7-20 24-20s24 7 24 20" fill="#B4B2A9"/>
                        </svg>
                    </div>
                    <!-- Bilgiler -->
                    <div class="flex-grow-1">
                        <div class="fw-bold fs-4 mb-1" id="member-name">—</div>
                        <div class="text-muted small d-flex flex-wrap gap-3" id="member-details">
                            <span id="member-tc"></span>
                            <span id="member-sicil"></span>
                            <span id="member-phone"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adım Sihirbazı (başlangıçta gizli) -->
        <div id="wizard-card" class="card border-0 shadow-sm mb-4" style="display:none;">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-bold fs-5">
                    <i class="bi bi-list-ol text-primary me-2"></i>İşlem Adımları
                </span>
            </div>
            <div class="card-body pt-4 pb-3">

                <!-- Yatay Adımlar -->
                <div class="d-flex align-items-start justify-content-between mb-4 position-relative" id="step-bar">
                    <!-- Bağlantı çizgisi -->
                    <div class="position-absolute top-0 start-0 end-0"
                         style="height:3px; background:#dee2e6; top:18px; margin:0 10%; z-index:0;"></div>

                    <?php
                    $steps = [
                        ['id' => 'step-verify',   'icon' => 'bi-person-check', 'label' => 'Kimlik<br>Doğrula'],
                        ['id' => 'step-sign1',    'icon' => 'bi-pen',          'label' => '1. İmza'],
                        ['id' => 'step-token',    'icon' => 'bi-qr-code',      'label' => 'Token<br>Üret'],
                        ['id' => 'step-vote-wait','icon' => 'bi-hourglass-split','label' => 'Oy<br>Bekleniyor'],
                        ['id' => 'step-sign2',    'icon' => 'bi-pen-fill',     'label' => '2. İmza'],
                    ];
                    foreach ($steps as $i => $step):
                    ?>
                    <div class="text-center flex-fill position-relative" id="<?= $step['id'] ?>" style="z-index:1;">
                        <div class="step-circle mx-auto mb-1 d-flex align-items-center justify-content-center rounded-circle border border-2 border-secondary bg-white"
                             style="width:38px;height:38px;font-size:1rem;transition:all .25s;">
                            <i class="bi <?= $step['icon'] ?> text-secondary"></i>
                        </div>
                        <div class="step-label small text-muted lh-sm" style="font-size:.72rem;">
                            <?= $step['label'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Aksiyon Alanı -->
                <div id="action-area" class="text-center py-2">
                    <!-- JS tarafından doldurulur -->
                </div>

                <!-- QR Kod Alanı -->
                <div id="qr-area" class="text-center py-3 border-top mt-3" style="display:none;">
                    <p class="text-muted small mb-2">
                        <i class="bi bi-qr-code me-1"></i>Aşağıdaki QR kodu üyeye gösterin veya SMS gönderildi.
                    </p>
                    <img id="qr-image" src="" alt="QR Kod" class="img-fluid rounded shadow-sm" style="max-width:220px;">
                    <div class="mt-2">
                        <a id="vote-url-link" href="#" target="_blank" class="small text-break text-primary"></a>
                    </div>
                    <div class="mt-1 text-muted small">
                        <i class="bi bi-clock me-1"></i>Son geçerlilik: <span id="token-expires"></span>
                    </div>
                </div>

                <!-- Oy bekleme göstergesi -->
                <div id="vote-waiting-area" class="text-center py-3 border-top mt-3" style="display:none;">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
                        <span class="fw-semibold text-primary fs-5">Üyenin oy kullanması bekleniyor…</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Oy kullanıldığında bu ekran otomatik güncellenecek.</p>
                </div>

                <!-- Tamamlandı mesajı -->
                <div id="done-area" class="text-center py-3 border-top mt-3" style="display:none;">
                    <i class="bi bi-check-circle-fill text-success d-block mb-2" style="font-size:2.5rem;"></i>
                    <div class="fw-bold fs-5 text-success">İşlem Tamamlandı</div>
                    <p class="text-muted small mt-1">Üye oy kullandı ve işlem kayıt altına alındı.</p>
                </div>
            </div>
        </div>

    </div><!-- /col-lg-8 -->

    <!-- ===== SAĞ PANEL: STATS + ÜYE LİSTESİ ===== -->
    <div class="col-lg-4">

        <!-- Mini İstatistikler -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 px-4">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-secondary" id="stat-waiting">
                            <?= (int) $stats['waiting'] ?>
                        </div>
                        <div class="text-muted" style="font-size:.75rem;">Bekliyor</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-warning" id="stat-signed">
                            <?= (int) $stats['signed'] ?>
                        </div>
                        <div class="text-muted" style="font-size:.75rem;">İmza Atıldı</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-success" id="stat-done">
                            <?= (int) $stats['done'] ?>
                        </div>
                        <div class="text-muted" style="font-size:.75rem;">Tamamlandı</div>
                    </div>
                </div>
                <!-- İlerleme çubuğu -->
                <div class="progress mt-3" style="height:8px; border-radius:4px;">
                    <?php
                        $donePct    = $stats['total'] > 0 ? round($stats['done']   / $stats['total'] * 100) : 0;
                        $signedPct  = $stats['total'] > 0 ? round($stats['signed'] / $stats['total'] * 100) : 0;
                    ?>
                    <div class="progress-bar bg-success" id="bar-done"
                         style="width:<?= $donePct ?>%;" title="Tamamlandı"></div>
                    <div class="progress-bar bg-warning" id="bar-signed"
                         style="width:<?= $signedPct ?>%;" title="İmza Atıldı"></div>
                </div>
                <div class="text-muted text-center mt-1" style="font-size:.72rem;">
                    Toplam: <strong id="stat-total"><?= (int) $stats['total'] ?></strong> üye
                </div>
            </div>
        </div>

        <!-- Filtre Sekmeleri -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs nav-fill border-0" id="member-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-2 px-1 small fw-semibold"
                                data-filter="" type="button">Tümü</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 px-1 small"
                                data-filter="waiting" type="button">
                            <span class="text-secondary">○</span> Bekliyor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 px-1 small"
                                data-filter="signed" type="button">
                            <span class="text-warning">◑</span> İmza
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 px-1 small"
                                data-filter="done" type="button">
                            <span class="text-success">●</span> Tamam
                        </button>
                    </li>
                </ul>
            </div>
            <!-- Anlık arama filtresi -->
            <div class="px-2 pt-2 pb-1 bg-white border-bottom">
                <input type="text" id="list-filter-input" class="form-control form-control-sm"
                       placeholder="İsimle filtrele…" autocomplete="off">
            </div>
            <!-- Liste -->
            <div class="card-body p-0">
                <div id="member-list"
                     class="list-group list-group-flush"
                     style="max-height: 420px; overflow-y: auto;">
                    <?php foreach ($members as $m): ?>
                    <?php
                        $icon  = match($m['status']) {
                            'done'   => '<span class="text-success me-1">●</span>',
                            'signed' => '<span class="text-warning me-1">◑</span>',
                            default  => '<span class="text-secondary me-1">○</span>',
                        };
                        $textClass = match($m['status']) {
                            'done'   => 'text-muted',
                            default  => '',
                        };
                    ?>
                    <button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3 member-list-item <?= $textClass ?>"
                            data-member-id="<?= (int) $m['id'] ?>"
                            data-name="<?= e(mb_strtolower($m['name'])) ?>"
                            data-status="<?= e($m['status']) ?>">
                        <?= $icon ?>
                        <span class="small flex-grow-1 text-truncate"><?= e($m['name']) ?></span>
                        <?php if ($m['sicil_no']): ?>
                        <span class="text-muted" style="font-size:.68rem;"><?= e($m['sicil_no']) ?></span>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                    <?php if (empty($members)): ?>
                    <div class="text-center text-muted py-4 small" id="empty-list-msg">
                        <i class="bi bi-people d-block mb-1 fs-4"></i>Kayıtlı üye bulunmuyor.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /col-lg-4 -->

</div><!-- /row -->

<script src="/assets/js/gorevli.js"></script>
