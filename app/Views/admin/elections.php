<?php
/**
 * Admin — Seçim Yönetimi
 *
 * Değişkenler:
 *   $elections  array  — Tüm seçimler
 */

$statusMap = [
    'draft'  => ['label' => 'Taslak',  'class' => 'bg-secondary'],
    'test'   => ['label' => 'Test',     'class' => 'bg-warning text-dark'],
    'open'   => ['label' => 'Açık',     'class' => 'bg-success'],
    'closed' => ['label' => 'Kapalı',   'class' => 'bg-danger'],
];
?>

<!-- Başlık -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 mb-1 fw-bold">
            <i class="bi bi-calendar-event text-warning me-2"></i>Seçim Yönetimi
        </h1>
        <p class="text-muted mb-0"><?= count($elections) ?> seçim kayıtlı</p>
    </div>
    <a href="/admin" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
</div>

<!-- Yeni seçim formu -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-plus-circle me-2 text-primary"></i>Yeni Seçim Oluştur
        </h6>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/elections/store">
            <?= csrf_field() ?>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold small" for="title">
                        Seçim Başlığı <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        class="form-control"
                        id="title"
                        name="title"
                        required
                        placeholder="Örn: 2025 Yılı Olağan Genel Kurul Seçimi"
                        maxlength="255"
                    >
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold small" for="description">Açıklama</label>
                    <input
                        type="text"
                        class="form-control"
                        id="description"
                        name="description"
                        placeholder="İsteğe bağlı kısa açıklama"
                        maxlength="255"
                    >
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i>Oluştur
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Seçim listesi -->
<?php if (empty($elections)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle-fill me-2"></i>Henüz seçim oluşturulmamış.
</div>
<?php else: ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:50px">#</th>
                    <th>Başlık</th>
                    <th>Durum</th>
                    <th>Başlangıç</th>
                    <th>Bitiş</th>
                    <th class="text-end pe-3">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($elections as $election): ?>
                <?php $si = $statusMap[$election['status']] ?? ['label' => e($election['status']), 'class' => 'bg-secondary']; ?>
                <tr>
                    <td class="ps-3 text-muted small"><?= (int) $election['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($election['title']) ?></div>
                        <?php if ($election['description']): ?>
                        <div class="text-muted small"><?= e($election['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $si['class'] ?>"><?= $si['label'] ?></span>
                    </td>
                    <td class="small text-muted text-nowrap">
                        <?= $election['started_at'] ? e($election['started_at']) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="small text-muted text-nowrap">
                        <?= $election['closed_at'] ? e($election['closed_at']) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="text-end pe-3">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#overrideModal"
                            data-election-id="<?= (int) $election['id'] ?>"
                            data-election-title="<?= e($election['title']) ?>"
                            data-current-status="<?= e($election['status']) ?>"
                        >
                            <i class="bi bi-sliders me-1"></i>Override
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<!-- Override Modal -->
<div class="modal fade" id="overrideModal" tabindex="-1" aria-labelledby="overrideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="overrideModalLabel">
                    <i class="bi bi-sliders text-warning me-2"></i>Seçim Durumunu Değiştir
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form method="POST" action="/admin/override" id="overrideForm">
                <?= csrf_field() ?>
                <input type="hidden" name="election_id" id="override-election-id" value="">
                <div class="modal-body">
                    <p class="mb-3">
                        <strong id="override-election-title"></strong> seçimi için yeni durum seçin.
                    </p>
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Bu işlem seçimin durumunu zorla değiştirir. Dikkatli kullanın.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="override-status">Yeni Durum</label>
                        <select class="form-select" id="override-status" name="status" required>
                            <option value="draft">Taslak</option>
                            <option value="test">Test</option>
                            <option value="open">Açık</option>
                            <option value="closed">Kapalı</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-lg me-1"></i>Durumu Uygula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const overrideModal = document.getElementById('overrideModal');
    if (!overrideModal) return;

    overrideModal.addEventListener('show.bs.modal', function (event) {
        const btn      = event.relatedTarget;
        const id       = btn.getAttribute('data-election-id');
        const title    = btn.getAttribute('data-election-title');
        const status   = btn.getAttribute('data-current-status');

        document.getElementById('override-election-id').value    = id;
        document.getElementById('override-election-title').textContent = title;

        const sel = document.getElementById('override-status');
        if (sel) sel.value = status;
    });
}());
</script>
