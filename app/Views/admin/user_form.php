<?php
/**
 * Admin — Kullanıcı Formu (Oluştur / Düzenle)
 *
 * Değişkenler:
 *   $user    array|null   — Mevcut kullanıcı (null ise yeni kayıt)
 *   $errors  array        — Doğrulama hataları
 */

$isEdit = $user !== null;
$title  = $isEdit ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı';
?>

<!-- Başlık -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 mb-1 fw-bold">
            <i class="bi bi-person-<?= $isEdit ? 'fill' : 'plus-fill' ?> text-primary me-2"></i><?= $title ?>
        </h1>
        <?php if ($isEdit): ?>
        <p class="text-muted mb-0">Kullanıcı: <strong><?= e($user['username']) ?></strong></p>
        <?php endif; ?>
    </div>
    <a href="/admin/users" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Listeye Dön
    </a>
</div>

<!-- Hata mesajları -->
<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Lütfen aşağıdaki hataları düzeltin:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST"
                      action="<?= $isEdit ? '/admin/users/update/' . (int) $user['id'] : '/admin/users/store' ?>">
                    <?= csrf_field() ?>

                    <!-- Ad -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="name">
                            Ad Soyad <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            value="<?= e($user['name'] ?? '') ?>"
                            required
                            autocomplete="name"
                            placeholder="Örn: Ahmet Yılmaz"
                        >
                    </div>

                    <!-- Kullanıcı Adı -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="username">
                            Kullanıcı Adı <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control font-monospace"
                            id="username"
                            name="username"
                            value="<?= e($user['username'] ?? '') ?>"
                            required
                            autocomplete="username"
                            placeholder="Örn: ahmet.yilmaz"
                        >
                    </div>

                    <!-- Şifre -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="password">
                            Şifre
                            <?php if (!$isEdit): ?>
                            <span class="text-danger">*</span>
                            <?php else: ?>
                            <span class="text-muted fw-normal small">(boş bırakılırsa değişmez)</span>
                            <?php endif; ?>
                        </label>
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            <?= !$isEdit ? 'required' : '' ?>
                            minlength="6"
                            autocomplete="new-password"
                            placeholder="En az 6 karakter"
                        >
                    </div>

                    <!-- Rol -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="role">
                            Rol <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">-- Rol seçin --</option>
                            <option value="admin"
                                <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                Sistem Yöneticisi
                            </option>
                            <option value="divan_baskani"
                                <?= ($user['role'] ?? '') === 'divan_baskani' ? 'selected' : '' ?>>
                                Divan Başkanı
                            </option>
                            <option value="gorevli"
                                <?= ($user['role'] ?? '') === 'gorevli' ? 'selected' : '' ?>>
                                Kayıt Görevlisi
                            </option>
                        </select>
                    </div>

                    <!-- Masa No (yalnızca gorevli rolü için) -->
                    <div class="mb-4" id="desk-no-group"
                         style="<?= ($user['role'] ?? '') !== 'gorevli' ? 'display:none' : '' ?>">
                        <label class="form-label fw-semibold" for="desk_no">Masa Numarası</label>
                        <input
                            type="number"
                            class="form-control"
                            id="desk_no"
                            name="desk_no"
                            value="<?= isset($user['desk_no']) ? (int) $user['desk_no'] : '' ?>"
                            min="1"
                            max="99"
                            placeholder="Örn: 1"
                        >
                        <div class="form-text">Kayıt görevlisinin çalışacağı masa numarası.</div>
                    </div>

                    <!-- Butonlar -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/admin/users" class="btn btn-outline-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?= $isEdit ? 'check-lg' : 'person-plus-fill' ?> me-1"></i>
                            <?= $isEdit ? 'Güncelle' : 'Kullanıcı Oluştur' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    const roleSelect   = document.getElementById('role');
    const deskNoGroup  = document.getElementById('desk-no-group');

    function toggleDeskNo() {
        if (roleSelect.value === 'gorevli') {
            deskNoGroup.style.display = '';
        } else {
            deskNoGroup.style.display = 'none';
        }
    }

    roleSelect.addEventListener('change', toggleDeskNo);
    toggleDeskNo();
}());
</script>
