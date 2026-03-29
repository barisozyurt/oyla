<?php
/**
 * Yeni Üye Ekle Formu
 */
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/yonetim" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
    <h1 class="h3 mb-0">
        <i class="bi bi-person-plus-fill me-2 text-primary"></i>Yeni Üye Ekle
    </h1>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form
                    method="POST"
                    action="/yonetim/store"
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
                            placeholder="Ahmet Yılmaz"
                            value="<?= e($_POST['name'] ?? '') ?>"
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
                                placeholder="12345678901"
                                value="<?= e($_POST['tc_kimlik'] ?? '') ?>"
                                inputmode="numeric"
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
                                placeholder="2024-001"
                                value="<?= e($_POST['sicil_no'] ?? '') ?>"
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
                                placeholder="05XX XXX XX XX"
                                value="<?= e($_POST['phone'] ?? '') ?>"
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
                                placeholder="ornek@eposta.com"
                                value="<?= e($_POST['email'] ?? '') ?>"
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
                            $selectedRole = $_POST['role'] ?? 'uye';
                            foreach ($roles as $value => $label):
                            ?>
                            <option value="<?= e($value) ?>" <?= $selectedRole === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fotoğraf -->
                    <div class="mb-4">
                        <label for="photo" class="form-label fw-semibold">Fotoğraf</label>
                        <input
                            type="file"
                            class="form-control"
                            id="photo"
                            name="photo"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >
                        <div class="form-text">İzin verilen formatlar: JPG, PNG, WEBP. Maksimum boyut: 5 MB.</div>
                    </div>

                    <!-- Butonlar -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/yonetim" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i>İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
