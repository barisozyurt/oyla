<?php
/**
 * Giriş formu — auth layout içinde render edilir.
 * Değişkenler: $csrf (string), $error (string|null)
 */
?>
<header style="margin-bottom: var(--s-8);">
    <p class="ds-text-xs ds-text-muted" style="text-transform:uppercase; letter-spacing:0.18em; margin: 0 0 var(--s-2);">Yönetim Girişi</p>
    <h1 class="auth-form__title">Sisteme giriş</h1>
    <p class="auth-form__sub">Hesabınızla devam edin. Kullanıcı adı ve parola, dernek yöneticiniz tarafından oluşturulur.</p>
</header>

<?php if (!empty($error)): ?>
<div class="ds-alert ds-alert--danger" role="alert">
    <i class="bi bi-shield-exclamation ds-alert__icon" aria-hidden="true"></i>
    <div class="ds-alert__body"><p class="ds-alert__text"><?= e($error) ?></p></div>
</div>
<?php endif; ?>

<form method="POST" action="/auth/login" novalidate>
    <?= $csrf ?>

    <div class="ds-field">
        <label for="username" class="ds-field__label ds-field__label--required">Kullanıcı adı</label>
        <input type="text"
               id="username"
               name="username"
               class="ds-input ds-input--lg"
               placeholder="ornegin: divan_baskani"
               autocomplete="username"
               autofocus
               required>
    </div>

    <div class="ds-field">
        <label for="password" class="ds-field__label ds-field__label--required">Parola</label>
        <div style="position:relative">
            <input type="password"
                   id="password"
                   name="password"
                   class="ds-input ds-input--lg"
                   placeholder="••••••••••••"
                   autocomplete="current-password"
                   style="padding-right: 48px"
                   required>
            <button type="button"
                    id="togglePassword"
                    tabindex="-1"
                    aria-label="Parolayı göster/gizle"
                    style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:transparent;border:0;width:40px;height:40px;color:var(--char-400);cursor:pointer;border-radius:var(--r-sm)">
                <i class="bi bi-eye" id="toggleIcon" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="ds-btn ds-btn--primary ds-btn--lg ds-btn--block">
        <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
        Giriş Yap
    </button>
</form>

<p class="ds-text-xs ds-text-muted ds-mt-6" style="text-align:center; line-height: 1.6;">
    Erişim sorunu mu yaşıyorsunuz? Dernek yöneticinize başvurun.<br>
    Bu sistem yalnızca yetkili kullanıcılar içindir.
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
