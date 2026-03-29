<?php
/**
 * Admin Paneli — Genel Bakış
 *
 * Değişkenler:
 *   $totalElections  int
 *   $totalUsers      int
 *   $totalMembers    int
 *   $totalVotes      int
 *   $currentElection array|null
 */

$statusMap = [
    'draft'  => ['label' => 'Taslak',  'class' => 'bg-secondary'],
    'test'   => ['label' => 'Test',     'class' => 'bg-warning text-dark'],
    'open'   => ['label' => 'Açık',     'class' => 'bg-success'],
    'closed' => ['label' => 'Kapalı',   'class' => 'bg-danger'],
];
?>

<!-- Sayfa başlığı -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 mb-1 fw-bold">
            <i class="bi bi-shield-lock-fill text-primary me-2"></i>Yönetim Paneli
        </h1>
        <p class="text-muted mb-0">Sistem yöneticisi genel bakışı</p>
    </div>
    <?php if ($currentElection): ?>
    <?php $si = $statusMap[$currentElection['status']] ?? ['label' => $currentElection['status'], 'class' => 'bg-secondary']; ?>
    <span class="badge <?= $si['class'] ?> fs-6 px-3 py-2">
        <i class="bi bi-circle-fill me-1" style="font-size:.6rem"></i><?= $si['label'] ?>
    </span>
    <?php endif; ?>
</div>

<!-- İstatistik kartları -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fs-1 fw-bold text-primary"><?= (int) $totalElections ?></div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-calendar-event me-1"></i>Seçimler
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fs-1 fw-bold text-success"><?= (int) $totalUsers ?></div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-person-gear me-1"></i>Kullanıcılar
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fs-1 fw-bold text-info"><?= (int) $totalMembers ?></div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-people me-1"></i>Üyeler
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fs-1 fw-bold text-warning"><?= (int) $totalVotes ?></div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-check2-square me-1"></i>Kullanılan Oy
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Aktif seçim bilgisi -->
<?php if ($currentElection): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-semibold">
            <i class="bi bi-calendar-check me-2 text-primary"></i>Aktif Seçim
        </h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h6 class="fw-bold mb-1"><?= e($currentElection['title']) ?></h6>
                <?php if ($currentElection['description']): ?>
                <p class="text-muted small mb-2"><?= e($currentElection['description']) ?></p>
                <?php endif; ?>
                <div class="d-flex gap-3 flex-wrap small text-muted">
                    <?php if ($currentElection['started_at']): ?>
                    <span><i class="bi bi-play-circle me-1"></i>Başladı: <?= e($currentElection['started_at']) ?></span>
                    <?php endif; ?>
                    <?php if ($currentElection['closed_at']): ?>
                    <span><i class="bi bi-stop-circle me-1"></i>Kapandı: <?= e($currentElection['closed_at']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <?php $si = $statusMap[$currentElection['status']] ?? ['label' => $currentElection['status'], 'class' => 'bg-secondary']; ?>
                <span class="badge <?= $si['class'] ?> fs-6 px-3 py-2"><?= $si['label'] ?></span>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Henüz seçim oluşturulmamış.
    <a href="/admin/elections" class="alert-link">Seçim oluşturun</a>.
</div>
<?php endif; ?>

<!-- Hızlı erişim bağlantıları -->
<h5 class="fw-semibold mb-3">Hızlı Erişim</h5>
<div class="row g-3">

    <div class="col-6 col-md-4 col-xl-3">
        <a href="/admin/log" class="card border-0 shadow-sm h-100 text-decoration-none text-body quick-link-card">
            <div class="card-body text-center py-4">
                <i class="bi bi-journal-text fs-2 text-info mb-2 d-block"></i>
                <div class="fw-semibold">Aktivite Logu</div>
                <small class="text-muted">Sistem işlem kayıtları</small>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
        <a href="/admin/users" class="card border-0 shadow-sm h-100 text-decoration-none text-body quick-link-card">
            <div class="card-body text-center py-4">
                <i class="bi bi-person-gear fs-2 text-primary mb-2 d-block"></i>
                <div class="fw-semibold">Kullanıcı Yönetimi</div>
                <small class="text-muted">Hesap ve rol yönetimi</small>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
        <a href="/admin/system" class="card border-0 shadow-sm h-100 text-decoration-none text-body quick-link-card">
            <div class="card-body text-center py-4">
                <i class="bi bi-hdd-network fs-2 text-success mb-2 d-block"></i>
                <div class="fw-semibold">Sistem Durumu</div>
                <small class="text-muted">Bağlantı ve servis kontrolü</small>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
        <a href="/admin/elections" class="card border-0 shadow-sm h-100 text-decoration-none text-body quick-link-card">
            <div class="card-body text-center py-4">
                <i class="bi bi-calendar-event fs-2 text-warning mb-2 d-block"></i>
                <div class="fw-semibold">Seçim Yönetimi</div>
                <small class="text-muted">Seçim oluştur ve yönet</small>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
        <a href="/admin/test" class="card border-0 shadow-sm h-100 text-decoration-none text-body quick-link-card">
            <div class="card-body text-center py-4">
                <i class="bi bi-bug fs-2 text-secondary mb-2 d-block"></i>
                <div class="fw-semibold">Test Modu</div>
                <small class="text-muted">Sistem doğrulama testleri</small>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
        <a href="/admin/pdf" class="card border-0 shadow-sm h-100 text-decoration-none text-body quick-link-card">
            <div class="card-body text-center py-4">
                <i class="bi bi-file-earmark-pdf fs-2 text-danger mb-2 d-block"></i>
                <div class="fw-semibold">PDF Tutanak</div>
                <small class="text-muted">Resmi seçim tutanağı</small>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
        <a href="/admin/hash-export" class="card border-0 shadow-sm h-100 text-decoration-none text-body quick-link-card">
            <div class="card-body text-center py-4">
                <i class="bi bi-filetype-csv fs-2 text-dark mb-2 d-block"></i>
                <div class="fw-semibold">Hash Export</div>
                <small class="text-muted">Commitment hash CSV dışa aktar</small>
            </div>
        </a>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
        <a href="/divan" class="card border-0 shadow-sm h-100 text-decoration-none text-body quick-link-card">
            <div class="card-body text-center py-4">
                <i class="bi bi-person-badge fs-2 mb-2 d-block" style="color: var(--oyla-secondary);"></i>
                <div class="fw-semibold">Divan Paneli</div>
                <small class="text-muted">Seçim yürütme ekranı</small>
            </div>
        </a>
    </div>

</div>

<style>
.quick-link-card {
    transition: transform .15s, box-shadow .15s;
}
.quick-link-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.12) !important;
}
</style>
