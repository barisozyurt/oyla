<?php
/**
 * Activity Log Bütünlük Denetimi sayfası.
 *
 * $result = ['ok' => bool, 'total' => int, 'broken' => [['id'=>int,'reason'=>string],...]]
 */
?>
<h1 class="h4 fw-bold mb-3">
    <i class="bi bi-shield-check me-2" aria-hidden="true"></i>Audit Log Bütünlük Denetimi
</h1>

<p class="text-muted">
    Activity log satırları HMAC tabanlı hash chain ile bağlıdır. Bir satır silinir veya
    değiştirilirse sonraki tüm hash'ler bozulur — bu sayfa zincir bütünlüğünü doğrular.
</p>

<?php if ($result['ok']): ?>
<div class="alert alert-success" role="alert">
    <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
    <strong>Bütünlük korunmuş.</strong>
    <?= (int) $result['total'] ?> kayıt doğrulandı, hash chain sağlam.
</div>
<?php else: ?>
<div class="alert alert-danger" role="alert">
    <i class="bi bi-exclamation-octagon-fill me-2" aria-hidden="true"></i>
    <strong>TAMPERING TESPİT EDİLDİ.</strong>
    <?= count($result['broken']) ?> / <?= (int) $result['total'] ?> kayıtta bozulma var.
</div>

<table class="table table-sm table-bordered mt-3">
    <thead class="table-light">
        <tr>
            <th>Kayıt ID</th>
            <th>Hata</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($result['broken'] as $row): ?>
        <tr>
            <td><code>#<?= (int) $row['id'] ?></code></td>
            <td><?= e($row['reason']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<div class="mt-3">
    <a href="/admin/log" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Loga Dön
    </a>
    <a href="/admin/log/verify?format=json" class="btn btn-link">JSON çıktısı</a>
</div>
