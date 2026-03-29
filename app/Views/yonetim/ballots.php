<?php
/**
 * Kurul Yönetimi — Seçim kurulları ve adaylar
 *
 * Variables:
 *   $ballots  array  — Each entry is ballot + candidates key
 *   $members  array  — All members of the election (for candidate member_id select)
 */
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-list-check me-2 text-primary"></i>Kurul Yönetimi
        </h1>
        <span class="badge bg-secondary fs-6"><?= count($ballots) ?> kurul</span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/yonetim" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-people me-1"></i>Üyeler
        </a>
        <a href="/yonetim/settings" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-sliders me-1"></i>Seçim Ayarları
        </a>
        <button
            class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#addBallotModal"
        >
            <i class="bi bi-plus-lg me-1"></i>Yeni Kurul Ekle
        </button>
    </div>
</div>

<!-- Kurul kartları -->
<?php if (empty($ballots)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Henüz kurul tanımlanmamış.
    <button class="btn btn-link p-0 alert-link" data-bs-toggle="modal" data-bs-target="#addBallotModal">
        Yeni kurul ekleyin.
    </button>
</div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($ballots as $ballot): ?>
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <!-- Kurul başlığı -->
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <div>
                    <h5 class="mb-0 fw-bold"><?= e($ballot['title']) ?></h5>
                    <small class="opacity-75">
                        Kontenjan: <?= (int) $ballot['quota'] ?> asil
                        <?php if ($ballot['yedek_quota'] > 0): ?>
                            + <?= (int) $ballot['yedek_quota'] ?> yedek
                        <?php endif; ?>
                    </small>
                </div>
                <div class="d-flex gap-1">
                    <!-- Kurul düzenle -->
                    <button
                        class="btn btn-sm btn-outline-light"
                        data-bs-toggle="modal"
                        data-bs-target="#editBallotModal-<?= (int) $ballot['id'] ?>"
                        title="Kurulu Düzenle"
                    >
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <!-- Kurul sil -->
                    <form
                        method="POST"
                        action="/yonetim/ballots/delete/<?= (int) $ballot['id'] ?>"
                        onsubmit="return confirm('<?= e($ballot['title']) ?> kurulunu silmek istediğinize emin misiniz? Bu işlem tüm adayları da siler.')"
                        class="d-inline"
                    >
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-light" title="Kurulu Sil">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-body p-3">
                <!-- Aday listesi -->
                <?php $candidates = $ballot['candidates'] ?? []; ?>
                <?php if (empty($candidates)): ?>
                <p class="text-muted small mb-3">
                    <i class="bi bi-person-x me-1"></i>Henüz aday eklenmemiş.
                </p>
                <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">No</th>
                                <th>Ad Soyad</th>
                                <th style="width:50px">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidates as $candidate): ?>
                            <tr>
                                <td class="text-muted small font-monospace">
                                    <?= e($candidate['candidate_no'] ?? '—') ?>
                                </td>
                                <td class="fw-semibold"><?= e($candidate['name']) ?></td>
                                <td>
                                    <form
                                        method="POST"
                                        action="/yonetim/candidates/delete/<?= (int) $candidate['id'] ?>"
                                        onsubmit="return confirm('<?= e($candidate['name']) ?> adayını listeden kaldırmak istediğinize emin misiniz?')"
                                    >
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Adayı Kaldır">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Aday ekle formu -->
                <div class="border rounded p-3 bg-light">
                    <p class="small fw-semibold mb-2">
                        <i class="bi bi-person-plus me-1 text-primary"></i>Aday Ekle
                    </p>
                    <form method="POST" action="/yonetim/ballots/<?= (int) $ballot['id'] ?>/candidates">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-12 col-sm-5">
                                <input
                                    type="text"
                                    class="form-control form-control-sm"
                                    name="name"
                                    placeholder="Ad Soyad *"
                                    required
                                    maxlength="100"
                                >
                            </div>
                            <div class="col-12 col-sm-4">
                                <select class="form-select form-select-sm" name="member_id">
                                    <option value="">— Üyeden seç (opsiyonel) —</option>
                                    <?php foreach ($members as $m): ?>
                                    <option value="<?= (int) $m['id'] ?>">
                                        <?= e($m['name']) ?>
                                        <?= !empty($m['sicil_no']) ? '(' . e($m['sicil_no']) . ')' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-sm-2">
                                <input
                                    type="text"
                                    class="form-control form-control-sm"
                                    name="candidate_no"
                                    placeholder="No"
                                    maxlength="10"
                                >
                            </div>
                            <div class="col-6 col-sm-1">
                                <button type="submit" class="btn btn-sm btn-primary w-100" title="Ekle">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Kapasite özeti -->
            <?php
            $candidateCount = count($candidates);
            $totalQuota     = (int) $ballot['quota'] + (int) $ballot['yedek_quota'];
            $pct            = $totalQuota > 0 ? min(100, (int) round($candidateCount / $totalQuota * 100)) : 0;
            ?>
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                    <span>
                        <i class="bi bi-people me-1"></i><?= $candidateCount ?> aday
                    </span>
                    <span>Hedef: <?= $totalQuota ?></span>
                </div>
                <div class="progress" style="height:6px">
                    <div
                        class="progress-bar <?= $candidateCount >= $totalQuota ? 'bg-success' : 'bg-primary' ?>"
                        style="width:<?= $pct ?>%"
                        role="progressbar"
                        aria-valuenow="<?= $pct ?>"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kurul Düzenle Modal -->
    <div class="modal fade" id="editBallotModal-<?= (int) $ballot['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kurulu Düzenle: <?= e($ballot['title']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form method="POST" action="/yonetim/ballots/update/<?= (int) $ballot['id'] ?>">
                    <?= csrf_field() ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kurul Adı <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                name="title"
                                required
                                value="<?= e($ballot['title']) ?>"
                            >
                        </div>
                        <div class="row g-3">
                            <div class="col">
                                <label class="form-label fw-semibold">Asil Kontenjan <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    class="form-control"
                                    name="quota"
                                    min="1"
                                    required
                                    value="<?= (int) $ballot['quota'] ?>"
                                >
                            </div>
                            <div class="col">
                                <label class="form-label fw-semibold">Yedek Kontenjan</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    name="yedek_quota"
                                    min="0"
                                    value="<?= (int) $ballot['yedek_quota'] ?>"
                                >
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Yeni Kurul Ekle Modal -->
<div class="modal fade" id="addBallotModal" tabindex="-1" aria-labelledby="addBallotModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBallotModalLabel">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Yeni Kurul Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form method="POST" action="/yonetim/ballots/store">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ballotTitle" class="form-label fw-semibold">
                            Kurul Adı <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="ballotTitle"
                            name="title"
                            required
                            maxlength="100"
                            placeholder="Yönetim Kurulu"
                            autofocus
                        >
                    </div>
                    <div class="row g-3">
                        <div class="col">
                            <label for="ballotQuota" class="form-label fw-semibold">
                                Asil Kontenjan <span class="text-danger">*</span>
                            </label>
                            <input
                                type="number"
                                class="form-control"
                                id="ballotQuota"
                                name="quota"
                                min="1"
                                required
                                value="7"
                            >
                        </div>
                        <div class="col">
                            <label for="ballotYedek" class="form-label fw-semibold">Yedek Kontenjan</label>
                            <input
                                type="number"
                                class="form-control"
                                id="ballotYedek"
                                name="yedek_quota"
                                min="0"
                                value="0"
                            >
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Kurul Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
