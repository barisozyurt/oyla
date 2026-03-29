<?php
/**
 * Sonuç Ekranı — Aktif Seçim Yok
 */
?>
<div class="text-center py-5">
    <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
    <h2 class="fw-bold mt-4 mb-2">Aktif Seçim Bulunamadı</h2>
    <p class="text-muted fs-5 mb-4">Henüz aktif bir seçim bulunmamaktadır.</p>
    <?php $user = $_SESSION['user'] ?? null; ?>
    <?php if (($user['role'] ?? null) === 'admin'): ?>
    <a href="/yonetim" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Seçim Oluştur
    </a>
    <?php endif; ?>
</div>
