<?php
/**
 * Yönetim Paneli — Üye Listesi
 *
 * Variables:
 *   $members      array   — List of member rows
 *   $memberModel  Member  — For avatar HTML generation
 */
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-people-fill me-2 text-primary"></i>Üye Yönetimi
        </h1>
        <span class="badge bg-secondary fs-6"><?= count($members) ?> üye</span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/yonetim/create" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i>Üye Ekle
        </a>
        <a href="/yonetim/import" class="btn btn-outline-secondary">
            <i class="bi bi-filetype-csv me-1"></i>CSV İçe Aktar
        </a>
        <a href="/yonetim/ballots" class="btn btn-outline-secondary">
            <i class="bi bi-list-check me-1"></i>Kurul Yönetimi
        </a>
        <a href="/yonetim/settings" class="btn btn-outline-secondary">
            <i class="bi bi-sliders me-1"></i>Seçim Ayarları
        </a>
    </div>
</div>

<?php if (empty($members)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Henüz üye eklenmemiş.
    <a href="/yonetim/create" class="alert-link">Üye ekleyin</a> veya
    <a href="/yonetim/import" class="alert-link">CSV ile içe aktarın</a>.
</div>
<?php else: ?>

<!-- Arama ve filtreleme -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input
                        type="search"
                        id="memberSearch"
                        class="form-control border-start-0"
                        placeholder="Ad, TC veya sicil no ara..."
                        autocomplete="off"
                    >
                </div>
            </div>
            <div class="col-12 col-md-7">
                <div class="btn-group" role="group" aria-label="Durum filtresi">
                    <button type="button" class="btn btn-outline-secondary btn-sm status-filter active" data-status="all">
                        Tümü
                        <span class="badge bg-secondary ms-1"><?= count($members) ?></span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm status-filter" data-status="waiting">
                        Bekliyor
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm status-filter" data-status="signed">
                        İmza Atıldı
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm status-filter" data-status="done">
                        Tamamlandı
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Üye tablosu -->
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="memberTable">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">Fotoğraf</th>
                    <th>Ad Soyad</th>
                    <th>TC Kimlik</th>
                    <th>Sicil No</th>
                    <th>Telefon</th>
                    <th>Rol</th>
                    <th>Durum</th>
                    <th style="width:100px">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                <tr
                    data-status="<?= e($member['status']) ?>"
                    data-name="<?= e(mb_strtolower($member['name'])) ?>"
                    data-tc="<?= e($member['tc_kimlik'] ?? '') ?>"
                    data-sicil="<?= e(mb_strtolower($member['sicil_no'] ?? '')) ?>"
                >
                    <td>
                        <?= $memberModel->getAvatarHtml($member) ?>
                    </td>
                    <td class="fw-semibold"><?= e($member['name']) ?></td>
                    <td class="font-monospace text-muted small">
                        <?= e($member['tc_kimlik'] ?? '—') ?>
                    </td>
                    <td class="text-muted small"><?= e($member['sicil_no'] ?? '—') ?></td>
                    <td class="text-muted small"><?= e($member['phone'] ?? '—') ?></td>
                    <td>
                        <?php
                        $roleLabels = [
                            'uye'              => ['label' => 'Üye',         'class' => 'bg-secondary'],
                            'yk_adayi'         => ['label' => 'YK Adayı',    'class' => 'bg-primary'],
                            'denetleme_adayi'  => ['label' => 'Den. Adayı',  'class' => 'bg-info text-dark'],
                            'disiplin_adayi'   => ['label' => 'Dis. Adayı',  'class' => 'bg-dark'],
                        ];
                        $rl = $roleLabels[$member['role']] ?? ['label' => e($member['role']), 'class' => 'bg-secondary'];
                        ?>
                        <span class="badge <?= $rl['class'] ?>"><?= $rl['label'] ?></span>
                    </td>
                    <td>
                        <?php
                        $statusMap = [
                            'waiting' => ['label' => 'Bekliyor',     'class' => 'bg-secondary'],
                            'signed'  => ['label' => 'İmza Atıldı',  'class' => 'bg-warning text-dark'],
                            'done'    => ['label' => 'Tamamlandı',   'class' => 'bg-success'],
                        ];
                        $sm = $statusMap[$member['status']] ?? ['label' => e($member['status']), 'class' => 'bg-secondary'];
                        ?>
                        <span class="badge <?= $sm['class'] ?>"><?= $sm['label'] ?></span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a
                                href="/yonetim/edit/<?= (int) $member['id'] ?>"
                                class="btn btn-sm btn-outline-primary"
                                title="Düzenle"
                            >
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <form
                                method="POST"
                                action="/yonetim/delete/<?= (int) $member['id'] ?>"
                                onsubmit="return confirm('<?= e($member['name']) ?> adlı üyeyi silmek istediğinize emin misiniz?')"
                            >
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Sonuç sayacı -->
<p class="text-muted small mt-2" id="resultCount"></p>

<!-- SMS Test Butonu -->
<div class="mt-4 d-flex justify-content-end">
    <button id="smsTestBtn" class="btn btn-outline-info">
        <i class="bi bi-send-fill me-1"></i>Test SMS Gönder
    </button>
</div>

<?php endif; ?>

<script>
(function () {
    'use strict';

    const searchInput   = document.getElementById('memberSearch');
    const filterBtns    = document.querySelectorAll('.status-filter');
    const tbody         = document.querySelector('#memberTable tbody');
    const resultCount   = document.getElementById('resultCount');
    let activeStatus    = 'all';

    function applyFilters() {
        if (!tbody) return;
        const query = (searchInput?.value ?? '').toLowerCase().trim();
        let visible = 0;

        tbody.querySelectorAll('tr').forEach(function (row) {
            const name  = row.dataset.name  ?? '';
            const tc    = row.dataset.tc    ?? '';
            const sicil = row.dataset.sicil ?? '';
            const status = row.dataset.status ?? '';

            const matchSearch = query === ''
                || name.includes(query)
                || tc.includes(query)
                || sicil.includes(query);

            const matchStatus = activeStatus === 'all' || status === activeStatus;

            if (matchSearch && matchStatus) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        if (resultCount) {
            resultCount.textContent = visible + ' kayıt gösteriliyor.';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            activeStatus = btn.dataset.status ?? 'all';
            applyFilters();
        });
    });

    // SMS Test
    const smsTestBtn = document.getElementById('smsTestBtn');
    if (smsTestBtn) {
        smsTestBtn.addEventListener('click', function () {
            smsTestBtn.disabled = true;
            smsTestBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Gönderiliyor...';

            fetch('/yonetim/sms-test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '_csrf=' + encodeURIComponent(
                    document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                )
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                alert(data.message ?? 'SMS işlemi tamamlandı.');
            })
            .catch(function () {
                alert('SMS gönderilemedi. Lütfen tekrar deneyin.');
            })
            .finally(function () {
                smsTestBtn.disabled = false;
                smsTestBtn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Test SMS Gönder';
            });
        });
    }

    // Initial count
    applyFilters();
}());
</script>
