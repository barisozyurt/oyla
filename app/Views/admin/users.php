<?php
/**
 * Admin — Kullanıcı Listesi
 *
 * Değişkenler:
 *   $users  array  — Tüm kullanıcı kayıtları
 */

$roleLabels = [
    'admin'         => ['label' => 'Sistem Yöneticisi', 'class' => 'bg-danger'],
    'divan_baskani' => ['label' => 'Divan Başkanı',      'class' => 'bg-primary'],
    'gorevli'       => ['label' => 'Görevli',            'class' => 'bg-secondary'],
];
?>

<!-- Başlık -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 mb-1 fw-bold">
            <i class="bi bi-person-gear text-primary me-2"></i>Kullanıcı Yönetimi
        </h1>
        <p class="text-muted mb-0"><?= count($users) ?> kullanıcı kayıtlı</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/users/create" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i>Yeni Kullanıcı
        </a>
        <a href="/admin" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Geri
        </a>
    </div>
</div>

<?php if (empty($users)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle-fill me-2"></i>Henüz kullanıcı oluşturulmamış.
    <a href="/admin/users/create" class="alert-link">Kullanıcı ekleyin</a>.
</div>
<?php else: ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Ad</th>
                    <th>Kullanıcı Adı</th>
                    <th>Rol</th>
                    <th>Masa No</th>
                    <th>Durum</th>
                    <th class="text-end pe-3">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <?php $roleInfo = $roleLabels[$user['role']] ?? ['label' => e($user['role']), 'class' => 'bg-secondary']; ?>
                <tr>
                    <td class="ps-3 fw-semibold"><?= e($user['name']) ?></td>
                    <td class="font-monospace text-muted small"><?= e($user['username']) ?></td>
                    <td>
                        <span class="badge <?= $roleInfo['class'] ?>">
                            <?= $roleInfo['label'] ?>
                        </span>
                    </td>
                    <td class="text-muted small">
                        <?= isset($user['desk_no']) && $user['desk_no'] !== null
                            ? 'Masa ' . (int) $user['desk_no']
                            : '<span class="text-muted">—</span>'
                        ?>
                    </td>
                    <td>
                        <?php if ((int) ($user['is_active'] ?? 1) === 1): ?>
                        <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Pasif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <a href="/admin/users/edit/<?= (int) $user['id'] ?>"
                           class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <?php if ((int) ($user['is_active'] ?? 1) === 1): ?>
                        <form method="POST" action="/admin/users/delete/<?= (int) $user['id'] ?>"
                              class="d-inline"
                              onsubmit="return confirm('\"<?= e(addslashes($user['name'])) ?>\" kullanıcısını pasife almak istediğinizden emin misiniz?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-person-x-fill"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" disabled title="Zaten pasif">
                            <i class="bi bi-person-x"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
