<?php
/**
 * Seçim Ayarları
 *
 * Variables:
 *   $election  array|null  — Current election row
 */
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/yonetim" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
    <h1 class="h3 mb-0">
        <i class="bi bi-sliders me-2 text-primary"></i>Seçim Ayarları
    </h1>
</div>

<?php if (!$election): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Aktif seçim bulunamadı. Lütfen önce bir seçim oluşturun.
</div>
<?php else: ?>

<!-- Durum özeti -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <?php
            $statusMap = [
                'draft'  => ['label' => 'Taslak',    'class' => 'text-secondary', 'icon' => 'bi-file-earmark'],
                'test'   => ['label' => 'Test Modu', 'class' => 'text-info',      'icon' => 'bi-bug'],
                'open'   => ['label' => 'Açık',      'class' => 'text-success',   'icon' => 'bi-unlock-fill'],
                'closed' => ['label' => 'Kapalı',    'class' => 'text-danger',    'icon' => 'bi-lock-fill'],
            ];
            $sm = $statusMap[$election['status']] ?? ['label' => e($election['status']), 'class' => 'text-secondary', 'icon' => 'bi-circle'];
            ?>
            <div class="fs-2 <?= $sm['class'] ?>">
                <i class="bi <?= $sm['icon'] ?>"></i>
            </div>
            <div class="small text-muted">Durum</div>
            <div class="fw-bold <?= $sm['class'] ?>"><?= $sm['label'] ?></div>
        </div>
    </div>
    <?php if (!empty($election['started_at'])): ?>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 text-success"><i class="bi bi-calendar-check"></i></div>
            <div class="small text-muted">Başlangıç</div>
            <div class="fw-bold"><?= e($election['started_at']) ?></div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($election['closed_at'])): ?>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 text-danger"><i class="bi bi-calendar-x"></i></div>
            <div class="small text-muted">Kapanış</div>
            <div class="fw-bold"><?= e($election['closed_at']) ?></div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 text-primary"><i class="bi bi-hash"></i></div>
            <div class="small text-muted">Seçim ID</div>
            <div class="fw-bold font-monospace">#<?= (int) $election['id'] ?></div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <i class="bi bi-pencil-square me-1"></i>Seçim Bilgilerini Düzenle
            </div>
            <div class="card-body p-4">
                <?php if ($election['status'] !== 'draft'): ?>
                <div class="alert alert-warning py-2 mb-3">
                    <i class="bi bi-lock me-1"></i>
                    Seçim taslak durumunda değil. Ayarlar salt okunur modda görüntülenmektedir.
                    Değişiklik yapabilmek için seçimi taslak durumuna almanız gerekir.
                </div>
                <?php endif; ?>

                <form method="POST" action="/yonetim/settings">
                    <?= csrf_field() ?>

                    <!-- Başlık -->
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">
                            Seçim Başlığı <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="title"
                            name="title"
                            required
                            maxlength="255"
                            value="<?= e($election['title']) ?>"
                            <?= $election['status'] !== 'draft' ? 'readonly' : '' ?>
                            placeholder="Olağan Genel Kurul 2025 — Yönetim Kurulu Seçimi"
                        >
                    </div>

                    <!-- Açıklama -->
                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">Açıklama</label>
                        <textarea
                            class="form-control"
                            id="description"
                            name="description"
                            rows="4"
                            <?= $election['status'] !== 'draft' ? 'readonly' : '' ?>
                            placeholder="Seçim hakkında ek bilgi (isteğe bağlı)..."
                        ><?= e($election['description'] ?? '') ?></textarea>
                    </div>

                    <?php if ($election['status'] === 'draft'): ?>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/yonetim" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i>İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Ayarları Kaydet
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Hızlı bağlantılar -->
        <div class="card shadow-sm mt-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-grid me-1"></i>Hızlı Bağlantılar
            </div>
            <div class="list-group list-group-flush">
                <a href="/yonetim" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="bi bi-people text-primary"></i>
                    <span>Üye Yönetimi</span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
                <a href="/yonetim/ballots" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="bi bi-list-check text-primary"></i>
                    <span>Kurul &amp; Aday Yönetimi</span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
                <a href="/yonetim/import" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="bi bi-filetype-csv text-primary"></i>
                    <span>CSV İçe Aktarma</span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
                <a href="/admin" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-primary"></i>
                    <span>Admin Paneli</span>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
