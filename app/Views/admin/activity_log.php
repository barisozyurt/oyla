<?php
/**
 * Admin — Aktivite Logu
 *
 * Değişkenler:
 *   $logs              array   — activity_log kayıtları
 *   $allElections      array   — Tüm seçimler (filtre için)
 *   $filterElectionId  int|null
 */

/**
 * Aksiyon tipine göre Bootstrap renk sınıfı döndürür.
 */
function logRowClass(string $action): string {
    return match(true) {
        str_starts_with($action, 'login')          => 'table-info',
        str_starts_with($action, 'vote_cast')       => 'table-success',
        str_starts_with($action, 'election_started') => 'table-primary',
        str_starts_with($action, 'election_closed')  => 'table-secondary',
        str_starts_with($action, 'election_override') => 'table-warning',
        str_contains($action, 'error')
            || str_contains($action, 'fail')
            || str_contains($action, 'invalid')    => 'table-danger',
        default                                     => '',
    };
}

function logBadgeClass(string $action): string {
    return match(true) {
        str_starts_with($action, 'login')           => 'bg-info text-dark',
        str_starts_with($action, 'vote_cast')        => 'bg-success',
        str_starts_with($action, 'election_started') => 'bg-primary',
        str_starts_with($action, 'election_closed')  => 'bg-secondary',
        str_starts_with($action, 'election_override') => 'bg-warning text-dark',
        str_contains($action, 'error')
            || str_contains($action, 'fail')
            || str_contains($action, 'invalid')     => 'bg-danger',
        default                                      => 'bg-light text-dark border',
    };
}
?>

<!-- Başlık -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 mb-1 fw-bold">
            <i class="bi bi-journal-text text-info me-2"></i>Aktivite Logu
        </h1>
        <p class="text-muted mb-0">Son <?= count($logs) ?> kayıt gösteriliyor</p>
    </div>
    <a href="/admin" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
</div>

<!-- Filtre -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="/admin/log" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold mb-1" for="election_id">Seçime Göre Filtrele</label>
                <select name="election_id" id="election_id" class="form-select form-select-sm">
                    <option value="">Tüm seçimler</option>
                    <?php foreach ($allElections as $el): ?>
                    <option value="<?= (int) $el['id'] ?>"
                        <?= (int) ($filterElectionId ?? 0) === (int) $el['id'] ? 'selected' : '' ?>>
                        <?= e($el['title']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filtrele
                </button>
                <?php if ($filterElectionId): ?>
                <a href="/admin/log" class="btn btn-outline-secondary btn-sm ms-1">
                    <i class="bi bi-x me-1"></i>Temizle
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (empty($logs)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle-fill me-2"></i>Kayıt bulunamadı.
</div>
<?php else: ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small text-muted">Son 200 kayıt gösteriliyor</span>
        <span class="badge bg-secondary"><?= count($logs) ?> kayıt</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:160px">Tarih</th>
                    <th style="width:180px">İşlem</th>
                    <th>Açıklama</th>
                    <th style="width:130px">IP Adresi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr class="<?= logRowClass($log['action']) ?>">
                    <td class="ps-3 text-nowrap small text-muted">
                        <?= e($log['created_at']) ?>
                    </td>
                    <td>
                        <span class="badge <?= logBadgeClass($log['action']) ?> font-monospace small">
                            <?= e($log['action']) ?>
                        </span>
                    </td>
                    <td class="small"><?= e($log['description'] ?? '') ?></td>
                    <td class="small text-muted font-monospace"><?= e($log['ip_address'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
