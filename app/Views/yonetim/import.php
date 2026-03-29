<?php
/**
 * CSV İçe Aktarma Formu
 *
 * Variables:
 *   $result  array|null  — Import summary after processing:
 *                           ['imported' => int, 'skipped' => int, 'rowErrors' => string[]]
 */
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/yonetim" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Geri
    </a>
    <h1 class="h3 mb-0">
        <i class="bi bi-filetype-csv me-2 text-primary"></i>CSV İçe Aktarma
    </h1>
</div>

<!-- İçe aktarma sonucu -->
<?php if ($result !== null): ?>
<div class="alert <?= $result['imported'] > 0 ? 'alert-success' : 'alert-warning' ?> alert-dismissible fade show mb-4" role="alert">
    <strong>
        <i class="bi bi-<?= $result['imported'] > 0 ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-1"></i>
        İçe Aktarma Tamamlandı
    </strong>
    <ul class="mb-0 mt-2">
        <li><strong><?= (int) $result['imported'] ?></strong> üye başarıyla içe aktarıldı.</li>
        <li><strong><?= (int) $result['skipped'] ?></strong> satır atlandı.</li>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
</div>

<?php if (!empty($result['rowErrors'])): ?>
<div class="card border-warning mb-4">
    <div class="card-header text-bg-warning fw-semibold">
        <i class="bi bi-exclamation-triangle me-1"></i>Atlanan Satırlar / Hatalar
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <?php foreach ($result['rowErrors'] as $err): ?>
            <li class="list-group-item list-group-item-warning py-2 small"><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="row">
    <!-- Yükleme formu -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white fw-semibold">
                <i class="bi bi-upload me-1"></i>CSV Dosyası Yükle
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/yonetim/import" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="csv" class="form-label fw-semibold">
                            CSV Dosyası <span class="text-danger">*</span>
                        </label>
                        <input
                            type="file"
                            class="form-control"
                            id="csv"
                            name="csv"
                            accept=".csv,text/csv"
                            required
                        >
                        <div class="form-text">Yalnızca .csv uzantılı dosyalar kabul edilir.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-cloud-upload me-1"></i>İçe Aktar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Format açıklaması -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">
                <i class="bi bi-info-circle me-1"></i>Beklenen CSV Formatı
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Dosyanın ilk satırı başlık satırı olmalıdır.
                    Aşağıdaki sütun adlarını kullanın (sıralama önemli değil):
                </p>
                <table class="table table-sm table-bordered small">
                    <thead class="table-light">
                        <tr>
                            <th>Sütun Adı</th>
                            <th>Açıklama</th>
                            <th>Zorunlu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-monospace">ad_soyad</td>
                            <td>Üyenin tam adı</td>
                            <td><span class="badge bg-danger">Evet</span></td>
                        </tr>
                        <tr>
                            <td class="font-monospace">sicil_no</td>
                            <td>Sicil / üye numarası</td>
                            <td><span class="badge bg-secondary">Hayır</span></td>
                        </tr>
                        <tr>
                            <td class="font-monospace">tc_kimlik</td>
                            <td>11 haneli TC kimlik no</td>
                            <td><span class="badge bg-secondary">Hayır</span></td>
                        </tr>
                        <tr>
                            <td class="font-monospace">telefon</td>
                            <td>Cep telefonu (SMS için)</td>
                            <td><span class="badge bg-secondary">Hayır</span></td>
                        </tr>
                        <tr>
                            <td class="font-monospace">email</td>
                            <td>E-posta adresi</td>
                            <td><span class="badge bg-secondary">Hayır</span></td>
                        </tr>
                    </tbody>
                </table>

                <p class="small text-muted mb-1 mt-3"><strong>Örnek içerik:</strong></p>
                <pre class="bg-light border rounded p-2 small mb-0" style="white-space:pre-wrap;word-break:break-all">sicil_no,tc_kimlik,ad_soyad,telefon,email
2024-001,12345678901,Ahmet Yılmaz,05551234567,ahmet@ornek.com
2024-002,,Ayşe Demir,05559876543,ayse@ornek.com</pre>

                <div class="alert alert-info small mt-3 mb-0 py-2">
                    <i class="bi bi-lightbulb me-1"></i>
                    Aynı seçim içinde aynı sicil numarasına sahip üyeler tekrar eklenmez.
                </div>
            </div>
        </div>
    </div>
</div>
