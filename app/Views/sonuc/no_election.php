<?php
$user = $_SESSION['user'] ?? null;
?>
<div class="ds-empty" style="margin-top: var(--s-12);">
    <svg class="ds-empty__mark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <rect x="10" y="14" width="44" height="44" rx="3"/>
        <line x1="10" y1="24" x2="54" y2="24"/>
        <line x1="20" y1="10" x2="20" y2="18"/>
        <line x1="44" y1="10" x2="44" y2="18"/>
        <line x1="22" y1="36" x2="42" y2="42" stroke-linecap="round"/>
        <line x1="42" y1="36" x2="22" y2="42" stroke-linecap="round"/>
    </svg>
    <p class="ds-empty__title">Aktif Seçim Bulunamadı</p>
    <p class="ds-empty__text">Şu anda sistemde devam eden veya kapanmış bir seçim yok.</p>
    <?php if (($user['role'] ?? null) === 'admin'): ?>
    <a href="/admin/elections" class="ds-btn ds-btn--primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>Yeni Seçim Oluştur
    </a>
    <?php endif; ?>
</div>
