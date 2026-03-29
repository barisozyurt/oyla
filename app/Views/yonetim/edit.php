<?php
/**
 * Üye Düzenleme Formu
 *
 * Variables:
 *   $member  array  — Member data row
 */
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/yonetim" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
    <h1 class="h3 mb-0">
        <i class="bi bi-pencil-fill me-2 text-primary"></i>Üye Düzenle
    </h1>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <!-- Mevcut fotoğraf -->
                <?php if (!empty($member['photo_path'])): ?>
                <div class="mb-4 d-flex align-items-center gap-3">
                    <img
                        src="<?= e($member['photo_path']) ?>"
                        alt="Mevcut fotoğraf"
                        class="rounded-circle border"
                        width="80"
                        height="80"
                        style="object-fit:cover"
                    >
                    <div>
                        <p class="mb-0 text-muted small">Mevcut fotoğraf</p>
                        <p class="mb-0 fw-semibold"><?= e($member['name']) ?></p>
                    </div>
                </div>
                <?php else: ?>
                <div class="mb-4 d-flex align-items-center gap-3">
                    <svg class="rounded-circle border" viewBox="0 0 80 80" width="80" height="80">
                        <rect width="80" height="80" rx="40" fill="#E9ECEF"/>
                        <circle cx="40" cy="32" r="14" fill="#B4B2A9"/>
                        <path d="M12 76c0-15.4 12.6-24 28-24s28 8.6 28 24" fill="#B4B2A9"/>
                    </svg>
                    <div>
                        <p class="mb-0 text-muted small">Fotoğraf yüklenmemiş</p>
                        <p class="mb-0 fw-semibold"><?= e($member['name']) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <form
                    method="POST"
                    action="/yonetim/update/<?= (int) $member['id'] ?>"
                    enctype="multipart/form-data"
                    novalidate
                >
                    <?= csrf_field() ?>

                    <!-- Ad Soyad -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">
                            Ad Soyad <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            required
                            maxlength="100"
                            value="<?= e($member['name']) ?>"
                            autofocus
                        >
                    </div>

                    <div class="row g-3">
                        <!-- TC Kimlik -->
                        <div class="col-12 col-md-6">
                            <label for="tc_kimlik" class="form-label fw-semibold">TC Kimlik No</label>
                            <input
                                type="text"
                                class="form-control font-monospace"
                                id="tc_kimlik"
                                name="tc_kimlik"
                                maxlength="11"
                                pattern="[0-9]{11}"
                                inputmode="numeric"
                                value="<?= e($member['tc_kimlik'] ?? '') ?>"
                            >
                            <div class="form-text">11 haneli rakam</div>
                        </div>

                        <!-- Sicil No -->
                        <div class="col-12 col-md-6">
                            <label for="sicil_no" class="form-label fw-semibold">Sicil No</label>
                            <input
                                type="text"
                                class="form-control"
                                id="sicil_no"
                                name="sicil_no"
                                maxlength="20"
                                value="<?= e($member['sicil_no'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <!-- Telefon -->
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label fw-semibold">Telefon</label>
                            <input
                                type="tel"
                                class="form-control"
                                id="phone"
                                name="phone"
                                maxlength="20"
                                value="<?= e($member['phone'] ?? '') ?>"
                            >
                        </div>

                        <!-- E-posta -->
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold">E-posta</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                maxlength="100"
                                value="<?= e($member['email'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <!-- Rol -->
                    <div class="mb-3 mt-3">
                        <label for="role" class="form-label fw-semibold">Rol</label>
                        <select class="form-select" id="role" name="role">
                            <?php
                            $roles = [
                                'uye'             => 'Üye',
                                'yk_adayi'        => 'Yönetim Kurulu Adayı',
                                'denetleme_adayi' => 'Denetleme Kurulu Adayı',
                                'disiplin_adayi'  => 'Disiplin Kurulu Adayı',
                            ];
                            foreach ($roles as $value => $label):
                            ?>
                            <option value="<?= e($value) ?>" <?= $member['role'] === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fotoğraf güncelle -->
                    <div class="mb-4">
                        <label for="photo" class="form-label fw-semibold">Yeni Fotoğraf Yükle</label>
                        <input
                            type="file"
                            class="form-control"
                            id="photo"
                            name="photo"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >
                        <div class="form-text">
                            Boş bırakırsanız mevcut fotoğraf korunur.
                            İzin verilen formatlar: JPG, PNG, WEBP. Maksimum boyut: 5 MB.
                        </div>
                    </div>

                    <!-- Durum bilgisi (salt okunur) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-muted">Mevcut Durum</label>
                        <?php
                        $statusMap = [
                            'waiting' => ['label' => 'Bekliyor',     'class' => 'bg-secondary'],
                            'signed'  => ['label' => 'İmza Atıldı',  'class' => 'bg-warning text-dark'],
                            'done'    => ['label' => 'Tamamlandı',   'class' => 'bg-success'],
                        ];
                        $sm = $statusMap[$member['status']] ?? ['label' => e($member['status']), 'class' => 'bg-secondary'];
                        ?>
                        <div>
                            <span class="badge <?= $sm['class'] ?> fs-6"><?= $sm['label'] ?></span>
                            <?php if (!empty($member['signed_at'])): ?>
                            <small class="text-muted ms-2">
                                İmza: <?= e($member['signed_at']) ?>
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/yonetim" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i>İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
