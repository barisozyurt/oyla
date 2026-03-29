<?php
/**
 * Admin — Test Modu Paneli
 *
 * Değişkenler:
 *   $election  array|null  — Aktif seçim satırı
 *   $ballots   array       — Seçime ait kurullar
 */

$statusMap = [
    'draft'  => ['label' => 'Taslak',    'class' => 'secondary', 'icon' => 'bi-file-earmark'],
    'test'   => ['label' => 'Test Modu', 'class' => 'warning',   'icon' => 'bi-bug-fill'],
    'open'   => ['label' => 'Açık',      'class' => 'success',   'icon' => 'bi-unlock-fill'],
    'closed' => ['label' => 'Kapalı',    'class' => 'danger',    'icon' => 'bi-lock-fill'],
];
?>

<!-- Sayfa başlığı -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold">
            <i class="bi bi-bug-fill text-warning me-2"></i>Sistem Test Modu
        </h1>
        <p class="text-muted mb-0">Seçim başlamadan önce sistem bileşenlerini doğrulayın</p>
    </div>
    <a href="/admin" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Admin Paneli
    </a>
</div>

<!-- Aktif seçim bilgi kartı -->
<?php if ($election): ?>
<?php $si = $statusMap[$election['status']] ?? ['label' => $election['status'], 'class' => 'secondary', 'icon' => 'bi-circle']; ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="flex-grow-1">
                <h6 class="mb-1 text-muted small text-uppercase fw-semibold">Aktif Seçim</h6>
                <h5 class="mb-1 fw-bold"><?= e($election['title']) ?></h5>
                <?php if ($election['description']): ?>
                <p class="mb-0 text-muted small"><?= e($election['description']) ?></p>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="badge bg-<?= $si['class'] ?> text-dark fs-6 px-3 py-2">
                    <i class="bi <?= $si['icon'] ?> me-1"></i><?= $si['label'] ?>
                </span>
                <?php if ($election['test_mode']): ?>
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Test verileri mevcut
                </span>
                <?php endif; ?>
                <div class="text-muted small">
                    <i class="bi bi-layers me-1"></i><?= count($ballots) ?> kurul
                </div>
            </div>
        </div>
        <?php if (!empty($ballots)): ?>
        <div class="mt-3 pt-3 border-top d-flex flex-wrap gap-2">
            <?php foreach ($ballots as $b): ?>
            <span class="badge bg-light text-dark border">
                <i class="bi bi-people me-1"></i><?= e($b['title']) ?>
                <span class="text-muted">(kota: <?= (int) $b['quota'] ?>)</span>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Aktif seçim bulunamadı. Lütfen <a href="/admin/elections" class="alert-link">seçim oluşturun</a> ve
    <a href="/admin" class="alert-link">oturum açın</a>.
</div>
<?php endif; ?>

<!-- ================================================================== -->
<!-- BÖLÜM 1: Sistem Kontrolleri                                         -->
<!-- ================================================================== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-clipboard2-check-fill text-primary me-2"></i>
                Bölüm 1 — Sistem Kontrolleri
            </h5>
            <button id="btnRunChecks" class="btn btn-primary" <?= !$election ? 'disabled' : '' ?>>
                <i class="bi bi-play-circle-fill me-1"></i>
                Kontrolleri Çalıştır
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="checksTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width:40%">Kontrol Adı</th>
                        <th style="width:20%">Durum</th>
                        <th>Detay</th>
                    </tr>
                </thead>
                <tbody id="checksBody">
                    <?php
                    $checkNames = [
                        'Veritabanı Bağlantısı',
                        'SMS Servisi',
                        'Token Üretimi',
                        'Commitment Hash',
                        'Çift Oy Engeli',
                        'Yetkilendirme / Rol Kontrolü',
                        'Sonuç Hesaplama',
                        'PDF Tutanak',
                    ];
                    foreach ($checkNames as $cn): ?>
                    <tr class="check-row" data-name="<?= e($cn) ?>">
                        <td class="ps-4">
                            <span class="fw-medium"><?= e($cn) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border">
                                <i class="bi bi-dash-circle me-1"></i>Bekleniyor
                            </span>
                        </td>
                        <td class="text-muted small">—</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Genel sonuç -->
        <div id="checksResult" class="d-none p-4 border-top">
            <div id="checksAllPass" class="d-none alert alert-success mb-0">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Tüm kontroller geçti.</strong> Sistem seçim için hazır.
            </div>
            <div id="checksSomeFail" class="d-none alert alert-danger mb-0">
                <i class="bi bi-x-circle-fill me-2"></i>
                <strong>Bazı kontroller başarısız.</strong> Devam etmeden önce hataları giderin.
            </div>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!-- BÖLÜM 2: Test Seçimi Simülasyonu                                    -->
<!-- ================================================================== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-robot text-success me-2"></i>
            Bölüm 2 — Test Seçimi Simülasyonu
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">
            Sanal üyeler oluşturulur, token üretilir ve rastgele oylar kullanılır.
            Gerçek seçim başlatılmadan sistemin uçtan uca çalıştığı doğrulanır.
        </p>

        <?php if ($election && $election['test_mode']): ?>
        <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Sistemde mevcut test verisi var. Yeni simülasyon başlatmadan önce <strong>Bölüm 3</strong>'ten temizlik yapın.
        </div>
        <?php endif; ?>

        <?php if (empty($ballots)): ?>
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle-fill me-2"></i>
            Simülasyon için en az bir seçim kurulu ve adayı tanımlı olması gerekir.
            <a href="/yonetim/ballots" class="alert-link">Kurulları yönet</a>
        </div>
        <?php endif; ?>

        <!-- Konfigürasyon formu -->
        <form id="simulationForm" class="row g-3 align-items-end mb-4">
            <input type="hidden" name="_csrf" id="simCsrf" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
            <div class="col-12 col-sm-6 col-md-4">
                <label for="memberCount" class="form-label fw-medium">Sanal Üye Sayısı</label>
                <input
                    type="number"
                    class="form-control"
                    id="memberCount"
                    name="member_count"
                    min="1"
                    max="50"
                    value="10"
                    <?= !$election || empty($ballots) ? 'disabled' : '' ?>
                >
                <div class="form-text">1–50 arası sanal üye</div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <button
                    type="submit"
                    id="btnSimulate"
                    class="btn btn-success w-100"
                    <?= !$election || empty($ballots) ? 'disabled' : '' ?>
                >
                    <i class="bi bi-play-fill me-1"></i>
                    Simülasyonu Başlat
                </button>
            </div>
        </form>

        <!-- Simülasyon ilerleme çubuğu -->
        <div id="simProgress" class="d-none mb-4">
            <div class="d-flex align-items-center gap-2 mb-2 text-muted small">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                <span id="simProgressText">Simülasyon çalışıyor…</span>
            </div>
            <div class="progress" style="height:8px">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                     style="width: 100%" role="progressbar"></div>
            </div>
        </div>

        <!-- Simülasyon sonuç özeti -->
        <div id="simResults" class="d-none">
            <div class="alert alert-success d-flex align-items-start gap-3 mb-0" id="simSuccessAlert">
                <i class="bi bi-check-circle-fill fs-4 flex-shrink-0 mt-1"></i>
                <div class="w-100">
                    <h6 class="mb-3 fw-bold">Simülasyon Tamamlandı</h6>
                    <div class="row g-3" id="simStatsGrid"></div>
                    <div id="simSummaryList" class="mt-3"></div>
                </div>
            </div>
            <div class="alert alert-danger d-none mb-0" id="simErrorAlert">
                <i class="bi bi-x-circle-fill me-2"></i>
                <span id="simErrorMsg">Simülasyon hatası.</span>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!-- BÖLÜM 3: Temizlik                                                   -->
<!-- ================================================================== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-trash3-fill text-danger me-2"></i>
            Bölüm 3 — Test Verilerini Temizle
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <strong>Dikkat!</strong> Bu işlem aşağıdakileri kalıcı olarak siler:
                    <ul class="mb-0 mt-1 small">
                        <li>TEST- önekli tüm sanal üyeler</li>
                        <li>Bu üyelere ait tokenlar</li>
                        <li>Bu tokenlarla kullanılmış oylar</li>
                        <li>Test modunda oluşturulan makbuzlar</li>
                    </ul>
                    Seçim durumu <strong>"Taslak"</strong> olarak sıfırlanır.
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <button
                id="btnCleanup"
                class="btn btn-danger"
                <?= !$election ? 'disabled' : '' ?>
                data-bs-toggle="modal"
                data-bs-target="#cleanupModal"
            >
                <i class="bi bi-trash3-fill me-1"></i>
                Test Verilerini Temizle
            </button>
            <?php if ($election && !$election['test_mode']): ?>
            <span class="text-muted small">
                <i class="bi bi-info-circle me-1"></i>Sistemde test verisi bulunmuyor.
            </span>
            <?php endif; ?>
        </div>

        <!-- Temizlik sonuç alanı -->
        <div id="cleanupResult" class="mt-3 d-none">
            <div class="alert alert-success mb-0" id="cleanupSuccess">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span id="cleanupMsg">Test verileri silindi.</span>
            </div>
            <div class="alert alert-danger d-none mb-0" id="cleanupError">
                <i class="bi bi-x-circle-fill me-2"></i>
                <span id="cleanupErrMsg">Hata oluştu.</span>
            </div>
        </div>
    </div>
</div>

<!-- Test raporu (CLAUDE.md Bölüm 7 formatı) -->
<div id="testReportSection" class="d-none">
    <div class="card border-0 shadow-sm mb-4 border-start border-4 border-success">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-file-text-fill text-success me-2"></i>
                Sistem Test Kaydı
            </h5>
            <small class="text-muted">Tutanağa eklenebilir</small>
        </div>
        <div class="card-body">
            <pre id="testReportContent" class="bg-light rounded p-3 small mb-0" style="font-family: monospace; white-space: pre-wrap;"></pre>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!-- Onay Modal: Temizlik                                                 -->
<!-- ================================================================== -->
<div class="modal fade" id="cleanupModal" tabindex="-1" aria-labelledby="cleanupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cleanupModalLabel">
                    <i class="bi bi-trash3-fill me-2"></i>Test Verilerini Sil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bu işlem geri alınamaz. Tüm test verilerini ve <strong>"Taslak"</strong> durumuna sıfırlamayı onaylıyor musunuz?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form id="cleanupForm" method="post">
                    <input type="hidden" name="_csrf" id="cleanupCsrf" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <button type="button" id="btnCleanupConfirm" class="btn btn-danger">
                        <i class="bi bi-trash3-fill me-1"></i>Evet, Temizle
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('js/test_mode.js') ?>"></script>
