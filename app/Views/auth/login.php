<?php
/**
 * Giriş formu — auth layout içinde render edilir.
 * Değişkenler: $csrf (string), $error (string|null)
 */
?>
<h2 class="card-title text-center fw-bold mb-1">Sisteme Giriş</h2>
<p class="text-center text-muted small mb-4">Lütfen bilgilerinizi girin</p>

<?php if (!empty($error)): ?>
<div class="alert alert-danger d-flex align-items-center" role="alert">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
    <div><?= e($error) ?></div>
</div>
<?php endif; ?>

<form method="POST" action="/auth/login" novalidate>
    <?= $csrf ?>

    <div class="mb-3">
        <label for="username" class="form-label fw-medium">
            <i class="bi bi-person me-1"></i>Kullanıcı Adı
        </label>
        <input
            type="text"
            id="username"
            name="username"
            class="form-control form-control-lg"
            placeholder="kullanici_adi"
            autocomplete="username"
            autofocus
            required
        >
    </div>

    <div class="mb-4">
        <label for="password" class="form-label fw-medium">
            <i class="bi bi-lock me-1"></i>Şifre
        </label>
        <div class="input-group">
            <input
                type="password"
                id="password"
                name="password"
                class="form-control form-control-lg"
                placeholder="••••••••"
                autocomplete="current-password"
                required
            >
            <button
                class="btn btn-outline-secondary"
                type="button"
                id="togglePassword"
                tabindex="-1"
                aria-label="Şifreyi göster/gizle"
            >
                <i class="bi bi-eye" id="toggleIcon"></i>
            </button>
        </div>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg fw-semibold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Yap
        </button>
    </div>
</form>

<hr class="my-4">

<p class="text-center text-muted small mb-0">
    Oyla &mdash; Dijital Seçim Yönetim Sistemi
</p>

<script>
(function () {
    var btn  = document.getElementById('togglePassword');
    var pwd  = document.getElementById('password');
    var icon = document.getElementById('toggleIcon');
    if (btn && pwd && icon) {
        btn.addEventListener('click', function () {
            var isPassword = pwd.type === 'password';
            pwd.type  = isPassword ? 'text' : 'password';
            icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }
}());
</script>
