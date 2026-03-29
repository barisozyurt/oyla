<?php
/**
 * Divan Paneli — Ana Sayfa
 *
 * Değişkenler:
 *   $election           array|null
 *   $divanMembers       array
 *   $ballots            array  (her biri: id, title, quota, candidate_count, candidates[])
 *   $stats              array  (total_members, signed_count, voted_count, participation_pct)
 *   $canStart           bool
 *   $hasBaskan          bool
 *   $hasBallots         bool
 *   $allBallotsHaveQuota bool
 */
?>

<!-- Başlık satırı + durum rozeti -->
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="fw-bold mb-1" style="font-size: 2rem;">
            <i class="bi bi-person-badge-fill text-primary me-2"></i>Divan Paneli
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
        $statusInfo = $statusMap[$election['status']] ?? ['label' => $election['status'], 'class' => 'bg-secondary'];
    ?>
    <span class="badge <?= $statusInfo['class'] ?> fs-5 px-3 py-2 align-self-start">
        <?= $statusInfo['label'] ?>
    </span>
    <?php endif; ?>
</div>

<?php if (!$election): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Henüz bir seçim oluşturulmamış. Lütfen önce <a href="/yonetim" class="alert-link">Yönetim Paneli</a>'nden seçim oluşturun.
</div>
<?php return; ?>
<?php endif; ?>

<!-- ===== İSTATİSTİK KARTLARI ===== -->
<div class="row g-3 mb-4">
    <!-- Toplam Üye -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-body py-4">
                <div class="fs-1 fw-bold text-primary" id="total-members">
                    <?= (int) $stats['total_members'] ?>
                </div>
                <div class="text-muted mt-1 fs-5">Toplam Üye</div>
            </div>
        </div>
    </div>

    <!-- İmza Atan -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-body py-4">
                <div class="fs-1 fw-bold text-warning" id="signed-count">
                    <?= (int) $stats['signed_count'] ?>
                </div>
                <div class="text-muted mt-1 fs-5">İmza Atan</div>
            </div>
        </div>
    </div>

    <!-- Oy Kullanan -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-body py-4">
                <div class="fs-1 fw-bold text-success" id="voted-count">
                    <?= (int) $stats['voted_count'] ?>
                </div>
                <div class="text-muted mt-1 fs-5">Oy Kullanan</div>
            </div>
        </div>
    </div>

    <!-- Katılım Oranı -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-body py-4">
                <div class="fs-1 fw-bold" id="participation-pct"
                     style="color: var(--oyla-primary);">
                    <?= $stats['participation_pct'] ?>%
                </div>
                <div class="text-muted mt-1 fs-5">Katılım Oranı</div>
            </div>
        </div>
    </div>
</div>

<!-- ===== İLERLEME ÇUBUĞU ===== -->
<?php
    $progressPct = $stats['total_members'] > 0
        ? (int) round($stats['voted_count'] / $stats['total_members'] * 100)
        : 0;
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold fs-5">
                <i class="bi bi-bar-chart-steps me-2 text-primary"></i>Oylama İlerlemesi
            </span>
            <span class="text-muted">
                <?= (int) $stats['voted_count'] ?> / <?= (int) $stats['total_members'] ?> üye
            </span>
        </div>
        <div class="progress" style="height: 28px; border-radius: 8px;">
            <div
                class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-semibold fs-6"
                role="progressbar"
                id="progress-bar"
                style="width: <?= $progressPct ?>%;"
                aria-valuenow="<?= $progressPct ?>"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <?= $progressPct ?>%
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- ===== SOL SÜTUN: DİVAN KURULU + AKSİYON BUTONLARI ===== -->
    <div class="col-lg-5">

        <!-- Divan Kurulu -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom fw-bold fs-5 py-3">
                <i class="bi bi-people-fill text-primary me-2"></i>Divan Kurulu
            </div>
            <div class="card-body p-0">
                <?php if (empty($divanMembers)): ?>
                <div class="text-muted text-center py-4">
                    <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                    Henüz divan üyesi eklenmedi.
                </div>
                <?php else: ?>
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Görev</th>
                            <th>Ad Soyad</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($divanMembers as $dm): ?>
                        <?php
                            $roleLabel = match($dm['role']) {
                                'baskan' => 'Başkan',
                                'katip'  => 'Kâtip',
                                default  => 'Üye',
                            };
                            $roleBadge = match($dm['role']) {
                                'baskan' => 'bg-primary',
                                'katip'  => 'bg-info text-dark',
                                default  => 'bg-secondary',
                            };
                        ?>
                        <tr>
                            <td>
                                <span class="badge <?= $roleBadge ?>"><?= e($roleLabel) ?></span>
                            </td>
                            <td class="fw-semibold"><?= e($dm['name']) ?></td>
                            <td class="text-end">
                                <?php if (!$election || !in_array($election['status'], ['open', 'closed'], true)): ?>
                                <form method="POST"
                                      action="/divan/divan-remove/<?= (int) $dm['id'] ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Bu üyeyi divan kurulundan çıkarmak istediğinizden emin misiniz?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Divan Üyesi Ekleme Formu -->
            <?php if (!in_array($election['status'], ['open', 'closed'], true)): ?>
            <div class="card-footer bg-light">
                <form method="POST" action="/divan/divan-store" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <div class="col-auto">
                        <label class="form-label small mb-1">Görev</label>
                        <select name="role" class="form-select form-select-sm" required>
                            <option value="">Seç…</option>
                            <option value="baskan">Başkan</option>
                            <option value="uye">Üye</option>
                            <option value="katip">Kâtip</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label small mb-1">Ad Soyad</label>
                        <input type="text" name="name" class="form-control form-control-sm"
                               placeholder="Ad Soyad" required maxlength="100">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i>Ekle
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Aksiyon Butonları -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom fw-bold fs-5 py-3">
                <i class="bi bi-play-circle-fill text-success me-2"></i>Seçim Kontrolü
            </div>
            <div class="card-body d-grid gap-3 py-4">

                <?php if ($election['status'] === 'draft' || $election['status'] === 'test'): ?>
                    <!-- Ön koşul uyarıları -->
                    <?php if (!$hasBaskan): ?>
                    <div class="alert alert-warning py-2 mb-0 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Divan başkanı atanmadı.
                    </div>
                    <?php endif; ?>
                    <?php if (!$hasBallots): ?>
                    <div class="alert alert-warning py-2 mb-0 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Hiç seçim kurulu tanımlanmadı.
                    </div>
                    <?php endif; ?>
                    <?php if ($hasBallots && !$allBallotsHaveQuota): ?>
                    <div class="alert alert-warning py-2 mb-0 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Bazı kurullarda yeterli aday yok.
                    </div>
                    <?php endif; ?>

                    <!-- Seçimi Başlat -->
                    <button
                        type="button"
                        class="btn btn-success btn-lg fw-bold py-3"
                        <?= $canStart ? '' : 'disabled' ?>
                        <?= $canStart ? 'data-bs-toggle="modal" data-bs-target="#startModal"' : '' ?>
                    >
                        <i class="bi bi-play-fill me-2"></i>Seçimi Başlat
                    </button>
                <?php endif; ?>

                <?php if ($election['status'] === 'open'): ?>
                    <!-- Seçimi Kapat -->
                    <button
                        type="button"
                        class="btn btn-danger btn-lg fw-bold py-3"
                        data-bs-toggle="modal"
                        data-bs-target="#stopModal"
                    >
                        <i class="bi bi-stop-fill me-2"></i>Seçimi Kapat
                    </button>
                <?php endif; ?>

                <?php if ($election['status'] === 'closed'): ?>
                    <!-- PDF İndir -->
                    <a href="/admin/pdf" class="btn btn-outline-secondary btn-lg fw-semibold py-3">
                        <i class="bi bi-file-earmark-pdf-fill me-2 text-danger"></i>PDF Tutanak İndir
                    </a>
                    <div class="alert alert-success py-2 mb-0 text-center small">
                        <i class="bi bi-check-circle-fill me-1"></i>Seçim tamamlandı.
                    </div>
                <?php endif; ?>

                <!-- Sonuçlar linki -->
                <a href="/sonuc" class="btn btn-outline-primary btn-lg fw-semibold py-3" target="_blank">
                    <i class="bi bi-bar-chart-fill me-2"></i>Sonuçları Görüntüle
                </a>
            </div>
        </div>
    </div>

    <!-- ===== SAĞ SÜTUN: KURUL ÖZETİ ===== -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom fw-bold fs-5 py-3">
                <i class="bi bi-list-check text-primary me-2"></i>Kurul Özeti
            </div>
            <div class="card-body">
                <?php if (empty($ballots)): ?>
                <div class="text-muted text-center py-5">
                    <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                    Henüz seçim kurulu tanımlanmadı.
                    <br>
                    <a href="/yonetim/ballots" class="btn btn-sm btn-outline-primary mt-3">
                        <i class="bi bi-plus-circle me-1"></i>Kurul Ekle
                    </a>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($ballots as $ballot): ?>
                    <?php
                        $candidateCount  = (int) $ballot['candidate_count'];
                        $quota           = (int) $ballot['quota'];
                        $hasEnough       = $candidateCount >= $quota;
                    ?>
                    <div class="col-12 col-md-6">
                        <div class="card border <?= $hasEnough ? 'border-success' : 'border-warning' ?> h-100">
                            <div class="card-body py-3 px-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold fs-5"><?= e($ballot['title']) ?></span>
                                    <?php if ($hasEnough): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <?php else: ?>
                                    <i class="bi bi-exclamation-circle-fill text-warning fs-5"></i>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($ballot['description'])): ?>
                                <p class="text-muted small mb-2"><?= e($ballot['description']) ?></p>
                                <?php endif; ?>

                                <div class="d-flex gap-3 small">
                                    <span>
                                        <i class="bi bi-trophy me-1 text-primary"></i>
                                        Kota: <strong><?= $quota ?></strong>
                                    </span>
                                    <span>
                                        <i class="bi bi-people me-1 text-secondary"></i>
                                        Aday: <strong><?= $candidateCount ?></strong>
                                    </span>
                                    <?php if ($ballot['yedek_quota'] > 0): ?>
                                    <span>
                                        <i class="bi bi-person-plus me-1 text-info"></i>
                                        Yedek: <strong><?= (int) $ballot['yedek_quota'] ?></strong>
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$hasEnough): ?>
                                <div class="mt-2 text-warning small fw-semibold">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <?= $quota - $candidateCount ?> aday daha gerekli
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /row -->

<!-- ===== MODAL: SEÇİMİ BAŞLAT ===== -->
<div class="modal fade" id="startModal" tabindex="-1" aria-labelledby="startModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="startModalLabel">
                    <i class="bi bi-play-fill text-success me-2"></i>Seçimi Başlat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body fs-5 py-4 text-center">
                <i class="bi bi-question-circle-fill text-warning d-block mb-3" style="font-size: 3rem;"></i>
                Seçimi başlatmak istediğinizden <strong>emin misiniz?</strong>
                <p class="text-muted small mt-2">
                    Başladıktan sonra üyeler oy kullanmaya başlayacaktır.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    İptal
                </button>
                <form method="POST" action="/divan/start">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-play-fill me-1"></i>Evet, Başlat
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: SEÇİMİ KAPAT ===== -->
<div class="modal fade" id="stopModal" tabindex="-1" aria-labelledby="stopModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="stopModalLabel">
                    <i class="bi bi-stop-fill text-danger me-2"></i>Seçimi Kapat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body fs-5 py-4 text-center">
                <i class="bi bi-exclamation-triangle-fill text-danger d-block mb-3" style="font-size: 3rem;"></i>
                Seçimi kapatmak istediğinizden <strong>emin misiniz?</strong>
                <p class="text-muted small mt-2">
                    Seçim kapatıldıktan sonra yeniden açılamaz ve oy kullanılamaz.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    İptal
                </button>
                <form method="POST" action="/divan/stop">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">
                        <i class="bi bi-stop-fill me-1"></i>Evet, Kapat
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/divan.js"></script>
