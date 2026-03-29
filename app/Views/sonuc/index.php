<?php
/**
 * Sonuç Ekranı — Herkese Açık
 *
 * Değişkenler:
 *   $election         array   (id, title, status, ...)
 *   $results          array   [ { ballot, candidates[], total_votes } ]
 *   $totalMembers     int
 *   $votedMembers     int
 *   $participationPct float
 */

$isClosed = ($election['status'] === 'closed');
$isOpen   = ($election['status'] === 'open');

$statusMap = [
    'draft'  => ['label' => 'Taslak',         'class' => 'bg-secondary'],
    'test'   => ['label' => 'Test Modu',       'class' => 'bg-warning text-dark'],
    'open'   => ['label' => 'Devam Ediyor',    'class' => 'bg-success'],
    'closed' => ['label' => 'Kapandı',         'class' => 'bg-danger'],
];
$statusInfo = $statusMap[$election['status']] ?? ['label' => $election['status'], 'class' => 'bg-secondary'];
?>

<?php if ($isClosed): ?>
<!-- ===== RESMİ SONUÇLAR BANNER ===== -->
<div class="alert alert-success border-2 border-success d-flex align-items-center gap-3 mb-4 shadow-sm" id="official-banner" role="alert" style="border-width:3px!important;">
    <i class="bi bi-patch-check-fill fs-2 text-success"></i>
    <div>
        <div class="fw-bold fs-4">RESMİ SONUÇLAR</div>
        <div class="text-muted small">Seçim tamamlanmıştır. Aşağıdaki sonuçlar kesinleşmiştir.</div>
    </div>
</div>
<?php endif; ?>

<!-- ===== BAŞLIK + DURUM ===== -->
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="fw-bold mb-1" style="font-size:1.9rem;">
            <i class="bi bi-bar-chart-fill text-primary me-2"></i><?= e($election['title']) ?>
        </h1>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge <?= $statusInfo['class'] ?> fs-6 px-3 py-2">
                <?= $statusInfo['label'] ?>
            </span>
            <?php if ($isOpen): ?>
            <span class="text-muted small" id="refresh-indicator">
                <i class="bi bi-arrow-repeat me-1"></i>Her 5 saniyede güncelleniyor
            </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="/sonuc/curtain" class="btn btn-outline-dark btn-sm" target="_blank">
            <i class="bi bi-fullscreen me-1"></i>Perde Modu
        </a>
        <a href="/oy/verify" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-shield-check me-1"></i>Oy Doğrula
        </a>
    </div>
</div>

<!-- ===== KATILIM KARTI ===== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <span class="fw-semibold fs-5">
                <i class="bi bi-people-fill text-primary me-2"></i>Katılım
            </span>
            <span class="fs-5" id="participation-label">
                <strong id="voted-count"><?= (int) $votedMembers ?></strong>
                /
                <strong id="total-count"><?= (int) $totalMembers ?></strong>
                <span class="text-muted ms-1">(%<span id="participation-pct"><?= $participationPct ?></span>)</span>
            </span>
        </div>
        <div class="progress" style="height:16px;" role="progressbar"
             aria-valuenow="<?= $participationPct ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar bg-primary progress-bar-striped <?= $isOpen ? 'progress-bar-animated' : '' ?>"
                 id="participation-bar"
                 style="width:<?= $participationPct ?>%">
            </div>
        </div>
    </div>
</div>

<?php if (empty($results)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle-fill me-2"></i>Henüz seçim kurulu tanımlanmamış.
</div>
<?php else: ?>

<!-- ===== KURUL SEKMELERİ ===== -->
<?php if (count($results) > 1): ?>
<ul class="nav nav-pills mb-4 flex-wrap" id="ballot-tabs" role="tablist">
    <?php foreach ($results as $i => $r): ?>
    <li class="nav-item" role="presentation">
        <button
            class="nav-link <?= $i === 0 ? 'active' : '' ?> ballot-tab-btn"
            data-ballot-index="<?= $i ?>"
            type="button"
            role="tab"
        >
            <?= e($r['ballot']['title']) ?>
        </button>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<!-- ===== KURUL SONUÇ BÖLÜMÜ ===== -->
<?php foreach ($results as $i => $r):
    $ballot     = $r['ballot'];
    $candidates = $r['candidates'];
    $totalVotes = (int) $r['total_votes'];
    $quota      = (int) $ballot['quota'];
    $yedekQuota = (int) ($ballot['yedek_quota'] ?? 0);

    // Max vote count to calculate bar widths
    $maxVotes = 0;
    foreach ($candidates as $c) {
        if ((int) $c['vote_count'] > $maxVotes) {
            $maxVotes = (int) $c['vote_count'];
        }
    }
?>
<div class="ballot-section" id="ballot-section-<?= $i ?>" style="<?= $i > 0 ? 'display:none;' : '' ?>"
     data-ballot-id="<?= (int) $ballot['id'] ?>"
     data-quota="<?= $quota ?>"
     data-yedek-quota="<?= $yedekQuota ?>">

    <!-- Kurul başlığı -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="fw-bold mb-0 fs-4"><?= e($ballot['title']) ?></h2>
            <?php if ($ballot['description']): ?>
            <p class="text-muted small mb-0"><?= e($ballot['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2">
                <i class="bi bi-trophy-fill me-1"></i><?= $quota ?> asıl seçilecek
            </span>
            <?php if ($yedekQuota > 0): ?>
            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2">
                <i class="bi bi-bookmark-fill me-1"></i><?= $yedekQuota ?> yedek seçilecek
            </span>
            <?php endif; ?>
            <span class="text-muted small" id="total-votes-<?= $i ?>">Toplam oy: <strong><?= $totalVotes ?></strong></span>
        </div>
    </div>

    <!-- Bar chart -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="result-chart" id="result-chart-<?= $i ?>">
            <?php foreach ($candidates as $rank => $candidate):
                $voteCount = (int) $candidate['vote_count'];
                $barWidth  = $maxVotes > 0 ? round($voteCount / $maxVotes * 100) : 0;
                $isWinner  = ($rank + 1) <= $quota;
                $isYedek   = !$isWinner && ($rank + 1) <= ($quota + $yedekQuota);

                if ($isWinner) {
                    $barClass = 'bg-success';
                } elseif ($isYedek) {
                    $barClass = 'bg-info';
                } else {
                    $barClass = 'bg-secondary';
                }

                $rowClass = '';
                if ($isClosed && $isWinner) {
                    $rowClass = 'winner-row';
                }
            ?>
            <div class="result-row <?= $rowClass ?> mb-3 <?= $isClosed && $isWinner ? 'rounded-3 p-2 border border-success border-2' : '' ?>">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <!-- Sıra -->
                    <span class="fw-bold text-muted" style="min-width:1.6rem; font-size:.9rem;"><?= $rank + 1 ?>.</span>

                    <!-- Avatar -->
                    <?php if (!empty($candidate['photo_path']) && file_exists(PUBLIC_PATH . $candidate['photo_path'])): ?>
                    <img src="<?= e($candidate['photo_path']) ?>" class="rounded-circle" width="36" height="36" alt="" loading="lazy" style="object-fit:cover;">
                    <?php else: ?>
                    <svg viewBox="0 0 36 36" width="36" height="36" class="rounded-circle flex-shrink-0">
                        <rect width="36" height="36" rx="18" fill="#E9ECEF"/>
                        <circle cx="18" cy="14" r="6" fill="#B4B2A9"/>
                        <path d="M4 34c0-7 6.3-11 14-11s14 4 14 11" fill="#B4B2A9"/>
                    </svg>
                    <?php endif; ?>

                    <!-- İsim + rozet -->
                    <div class="flex-grow-1 min-w-0">
                        <span class="<?= $isWinner ? 'fw-bold' : '' ?> text-truncate d-block">
                            <?= e($candidate['name']) ?>
                            <?php if ($isClosed && $isWinner): ?>
                            <i class="bi bi-trophy-fill text-success ms-1" title="Kazanan"></i>
                            <?php elseif ($isClosed && $isYedek): ?>
                            <i class="bi bi-bookmark-fill text-info ms-1" title="Yedek"></i>
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($candidate['candidate_no'])): ?>
                        <span class="text-muted" style="font-size:.8rem;">No: <?= e($candidate['candidate_no']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Oy sayısı -->
                    <span class="fw-semibold text-end flex-shrink-0" style="min-width:3rem;" data-vote-count="<?= $voteCount ?>">
                        <?= $voteCount ?>
                    </span>
                </div>

                <!-- Bar -->
                <div class="d-flex align-items-center gap-2">
                    <div style="width:1.6rem;"></div><!-- sıra sütunu hizası -->
                    <div style="width:36px;"></div><!-- avatar hizası -->
                    <div class="flex-grow-1">
                        <div class="progress" style="height:10px;" role="progressbar"
                             aria-valuenow="<?= $barWidth ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar <?= $barClass ?> result-bar"
                                 style="width:<?= $barWidth ?>%; transition: width .5s ease;"
                                 data-max-votes="<?= $maxVotes ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div><!-- /result-chart -->
        </div>
    </div>

</div><!-- /ballot-section -->
<?php endforeach; ?>
<?php endif; ?>

<script src="/assets/js/sonuc.js"></script>
