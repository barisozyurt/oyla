<?php
/**
 * Yönetim Paneli — Üye Listesi
 */
?>

<header class="ds-page-header">
    <div class="ds-page-header__row">
        <div>
            <p class="ds-page-header__eyebrow">Yönetim</p>
            <h1 class="ds-page-header__title">Üye Yönetimi</h1>
            <p class="ds-page-header__lead">
                <span class="ds-badge ds-badge--neutral"><?= count($members) ?> üye</span>
                seçimde kayıtlı
            </p>
        </div>
        <div class="ds-flex ds-gap-2 ds-flex-wrap">
            <a href="/yonetim/create" class="ds-btn ds-btn--primary"><i class="bi bi-person-plus" aria-hidden="true"></i>Üye Ekle</a>
            <a href="/yonetim/import" class="ds-btn ds-btn--secondary"><i class="bi bi-upload" aria-hidden="true"></i>CSV İçe Aktar</a>
            <a href="/yonetim/ballots" class="ds-btn ds-btn--secondary"><i class="bi bi-list-check" aria-hidden="true"></i>Kurullar</a>
            <a href="/yonetim/settings" class="ds-btn ds-btn--ghost"><i class="bi bi-sliders" aria-hidden="true"></i>Ayarlar</a>
        </div>
    </div>
</header>

<?php if (empty($members)): ?>
<div class="ds-empty">
    <svg class="ds-empty__mark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="32" cy="24" r="10"/>
        <path d="M12 56c0-11 9-18 20-18s20 7 20 18"/>
    </svg>
    <p class="ds-empty__title">Henüz üye eklenmemiş</p>
    <p class="ds-empty__text">Tek tek ekleyebilir veya CSV ile toplu içe aktarabilirsiniz.</p>
    <div class="ds-flex ds-gap-3 ds-justify-center">
        <a href="/yonetim/create" class="ds-btn ds-btn--primary"><i class="bi bi-person-plus" aria-hidden="true"></i>Üye Ekle</a>
        <a href="/yonetim/import" class="ds-btn ds-btn--secondary"><i class="bi bi-upload" aria-hidden="true"></i>CSV İçe Aktar</a>
    </div>
</div>
<?php else: ?>

<div class="ds-card ds-mb-5">
    <div class="ds-flex ds-gap-4 ds-flex-wrap ds-items-end">
        <div class="ds-flex-1 ds-field" style="margin:0;min-width:240px;">
            <label for="memberSearch" class="ds-field__label">Arama</label>
            <input type="search"
                   id="memberSearch"
                   class="ds-input"
                   placeholder="Ad, TC veya sicil no…"
                   autocomplete="off">
        </div>
        <div class="ds-flex ds-gap-1 ds-flex-wrap">
            <button type="button" class="ds-btn ds-btn--secondary ds-btn--sm status-filter is-active" data-status="all">
                Tümü <span class="ds-badge ds-badge--neutral" style="margin-left:6px;"><?= count($members) ?></span>
            </button>
            <button type="button" class="ds-btn ds-btn--ghost ds-btn--sm status-filter" data-status="waiting">Bekliyor</button>
            <button type="button" class="ds-btn ds-btn--ghost ds-btn--sm status-filter" data-status="signed">İmzalı</button>
            <button type="button" class="ds-btn ds-btn--ghost ds-btn--sm status-filter" data-status="done">Tamamlandı</button>
        </div>
    </div>
</div>

<div class="ds-table-wrap">
    <table class="ds-table" id="memberTable">
        <thead>
            <tr>
                <th style="width:48px"></th>
                <th>Ad Soyad</th>
                <th>TC Kimlik</th>
                <th>Sicil</th>
                <th>Telefon</th>
                <th>Rol</th>
                <th>Durum</th>
                <th style="width:110px;text-align:right;">İşlem</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($members as $member):
            $roleMap = [
                'uye'              => ['label' => 'Üye',         'class' => 'ds-badge--neutral'],
                'yk_adayi'         => ['label' => 'YK Adayı',    'class' => 'ds-badge--ink'],
                'denetleme_adayi'  => ['label' => 'Den. Adayı',  'class' => 'ds-badge--brass'],
                'disiplin_adayi'   => ['label' => 'Dis. Adayı',  'class' => 'ds-badge--info'],
            ];
            $r = $roleMap[$member['role']] ?? ['label' => e($member['role']), 'class' => 'ds-badge--neutral'];

            $statusMap = [
                'waiting' => ['label' => 'Bekliyor',     'class' => 'ds-badge--neutral'],
                'signed'  => ['label' => 'İmza Atıldı',  'class' => 'ds-badge--warn'],
                'done'    => ['label' => 'Tamamlandı',   'class' => 'ds-badge--ink'],
            ];
            $s = $statusMap[$member['status']] ?? ['label' => e($member['status']), 'class' => 'ds-badge--neutral'];

            $hasPhoto = !empty($member['photo_path']) && file_exists(PUBLIC_PATH . $member['photo_path']);
        ?>
        <tr data-status="<?= e($member['status']) ?>"
            data-name="<?= e(mb_strtolower($member['name'])) ?>"
            data-tc="<?= e($member['tc_kimlik'] ?? '') ?>"
            data-sicil="<?= e(mb_strtolower($member['sicil_no'] ?? '')) ?>">
            <td>
                <?php if ($hasPhoto): ?>
                <img src="<?= e($member['photo_path']) ?>" class="ds-avatar" alt="" loading="lazy">
                <?php else: ?>
                <span class="ds-avatar" aria-hidden="true"><?= e(mb_substr($member['name'], 0, 1, 'UTF-8')) ?></span>
                <?php endif; ?>
            </td>
            <td class="ds-font-semi" style="color:var(--char-800);"><?= e($member['name']) ?></td>
            <td class="ds-font-mono ds-tabular ds-text-muted"><?= e($member['tc_kimlik'] ?? '—') ?></td>
            <td class="ds-text-muted"><?= e($member['sicil_no'] ?? '—') ?></td>
            <td class="ds-font-mono ds-text-muted"><?= e($member['phone'] ?? '—') ?></td>
            <td><span class="ds-badge <?= $r['class'] ?>"><?= e($r['label']) ?></span></td>
            <td><span class="ds-badge <?= $s['class'] ?>"><?= e($s['label']) ?></span></td>
            <td style="text-align:right;">
                <div class="ds-flex ds-gap-1 ds-justify-end">
                    <a href="/yonetim/edit/<?= (int) $member['id'] ?>" class="ds-btn ds-btn--ghost ds-btn--sm" aria-label="Düzenle">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <form method="POST" action="/yonetim/delete/<?= (int) $member['id'] ?>" onsubmit="return confirm('<?= e($member['name']) ?> üyesini silmek istediğinize emin misiniz?');" style="display:inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="ds-btn ds-btn--ghost ds-btn--sm" aria-label="Sil" style="color:var(--danger);">
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="ds-text-xs ds-text-muted ds-mt-3" id="resultCount"></p>

<div class="ds-flex ds-justify-end ds-mt-4">
    <button id="smsTestBtn" class="ds-btn ds-btn--secondary">
        <i class="bi bi-send" aria-hidden="true"></i>Test SMS Gönder
    </button>
</div>

<?php endif; ?>

<style>
    .status-filter.is-active {
        background: var(--ink-50);
        color: var(--ink-800);
        border-color: var(--ink-200);
    }
</style>

<script>
(function () {
    'use strict';
    const searchInput = document.getElementById('memberSearch');
    const filterBtns  = document.querySelectorAll('.status-filter');
    const tbody       = document.querySelector('#memberTable tbody');
    const resultCount = document.getElementById('resultCount');
    let activeStatus  = 'all';

    function applyFilters() {
        if (!tbody) return;
        const query = (searchInput?.value ?? '').toLowerCase().trim();
        let visible = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            const matchSearch = query === ''
                || (row.dataset.name ?? '').includes(query)
                || (row.dataset.tc   ?? '').includes(query)
                || (row.dataset.sicil ?? '').includes(query);
            const matchStatus = activeStatus === 'all' || row.dataset.status === activeStatus;
            const match = matchSearch && matchStatus;
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (resultCount) resultCount.textContent = visible + ' kayıt gösteriliyor.';
    }

    searchInput?.addEventListener('input', applyFilters);
    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterBtns.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            activeStatus = btn.dataset.status ?? 'all';
            applyFilters();
        });
    });

    const smsBtn = document.getElementById('smsTestBtn');
    if (smsBtn) {
        smsBtn.addEventListener('click', function () {
            smsBtn.disabled = true;
            const old = smsBtn.innerHTML;
            smsBtn.innerHTML = '<span class="ds-spinner" aria-hidden="true"></span>Gönderiliyor…';
            fetch('/yonetim/sms-test', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: '_csrf=' + encodeURIComponent(document.querySelector('meta[name="csrf-token"]')?.content ?? ''),
            })
            .then(function (r) { return r.json(); })
            .then(function (d) { alert(d.message ?? 'SMS gönderildi.'); })
            .catch(function () { alert('SMS gönderilemedi.'); })
            .finally(function () { smsBtn.disabled = false; smsBtn.innerHTML = old; });
        });
    }
    applyFilters();
}());
</script>
