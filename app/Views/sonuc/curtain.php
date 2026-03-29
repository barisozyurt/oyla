<?php
/**
 * Perde / Salon Ekranı — Fullscreen (fullscreen layout)
 *
 * Değişkenler:
 *   $election      array   (id, title, status)
 *   $results       array   [ { ballot, candidates[], total_votes } ]
 *   $totalMembers  int
 *   $votedMembers  int
 */

$isClosed = ($election['status'] === 'closed');
$isOpen   = ($election['status'] === 'open');
$participationPct = $totalMembers > 0
    ? round($votedMembers / $totalMembers * 100, 1)
    : 0.0;
?>
<div class="d-flex flex-column min-vh-100" style="padding: 0; overflow-x:hidden;">

    <!-- ===== HEADER ===== -->
    <header class="text-center py-4 border-bottom border-secondary" style="background: rgba(255,255,255,0.04);">
        <div class="d-flex justify-content-center align-items-center gap-3 mb-2">
            <img src="/assets/img/logo.svg" alt="Oyla" height="48"
                 onerror="this.style.display='none'" class="opacity-75">
            <span class="fw-bold" style="font-size: 2rem; letter-spacing:.03em; opacity:.85;">Oyla</span>
        </div>
        <h1 class="fw-bold mb-1" style="font-size: clamp(1.4rem, 3vw, 2.4rem);">
            <?= e($election['title']) ?>
        </h1>
        <?php if ($isClosed): ?>
        <div class="d-inline-block mt-2 px-4 py-1 border rounded-pill"
             style="border-color: #ffd700 !important; color: #ffd700; font-size:1.1rem; font-weight:700; letter-spacing:.05em;">
            RESMİ SONUÇLAR
        </div>
        <?php elseif ($isOpen): ?>
        <div class="badge bg-success fs-6 mt-2 px-3 py-2">
            <span class="me-2" style="display:inline-block; width:10px; height:10px; background:#fff; border-radius:50%; animation: blink 1.2s infinite;"></span>
            DEVAM EDİYOR
        </div>
        <?php endif; ?>
    </header>

    <!-- ===== BALLOT SECTIONS ===== -->
    <main class="flex-grow-1 container-fluid px-4 py-4" style="max-width: 1400px; margin: 0 auto;">

        <?php if (empty($results)): ?>
        <div class="text-center opacity-75 mt-5">
            <i class="bi bi-hourglass-split fs-1"></i>
            <p class="mt-3 fs-5">Seçim kurulları henüz tanımlanmamış.</p>
        </div>
        <?php else: ?>

        <!-- Ballot index indicator -->
        <?php if (count($results) > 1): ?>
        <div class="d-flex justify-content-center gap-2 mb-4" id="curtain-dots">
            <?php foreach ($results as $i => $_): ?>
            <div class="curtain-dot rounded-circle <?= $i === 0 ? 'active' : '' ?>"
                 data-index="<?= $i ?>"
                 style="width:12px; height:12px; background: <?= $i === 0 ? '#fff' : 'rgba(255,255,255,0.3)' ?>; cursor:pointer; transition: background .3s;">
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php foreach ($results as $i => $r):
            $ballot     = $r['ballot'];
            $candidates = $r['candidates'];
            $totalVotes = (int) $r['total_votes'];
            $quota      = (int) $ballot['quota'];
            $yedekQuota = (int) ($ballot['yedek_quota'] ?? 0);

            $maxVotes = 0;
            foreach ($candidates as $c) {
                if ((int) $c['vote_count'] > $maxVotes) {
                    $maxVotes = (int) $c['vote_count'];
                }
            }
        ?>
        <div class="ballot-section" id="ballot-section-<?= $i ?>"
             data-ballot-id="<?= (int) $ballot['id'] ?>"
             data-quota="<?= $quota ?>"
             data-yedek-quota="<?= $yedekQuota ?>"
             style="<?= $i > 0 ? 'display:none;' : '' ?>">

            <!-- Kurul başlığı -->
            <div class="text-center mb-4">
                <h2 class="fw-bold" style="font-size: clamp(1.4rem, 2.5vw, 2rem);">
                    <?= e($ballot['title']) ?>
                </h2>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <span style="color: rgba(255,255,255,.6); font-size:.95rem;">
                        <i class="bi bi-trophy-fill me-1" style="color:#ffd700;"></i>
                        <?= $quota ?> asıl
                        <?php if ($yedekQuota > 0): ?> &bull; <?= $yedekQuota ?> yedek<?php endif; ?>
                        seçilecek
                    </span>
                    <span style="color: rgba(255,255,255,.6); font-size:.95rem;" id="curtain-total-votes-<?= $i ?>">
                        Toplam oy: <strong style="color:#fff;"><?= $totalVotes ?></strong>
                    </span>
                </div>
            </div>

            <!-- Bar chart -->
            <div class="row g-2" id="curtain-chart-<?= $i ?>">
            <?php foreach ($candidates as $rank => $candidate):
                $voteCount = (int) $candidate['vote_count'];
                $barWidth  = $maxVotes > 0 ? round($voteCount / $maxVotes * 100) : 0;
                $isWinner  = ($rank + 1) <= $quota;
                $isYedek   = !$isWinner && ($rank + 1) <= ($quota + $yedekQuota);

                if ($isWinner) {
                    $barColor = '#22c55e';
                    $nameColor = '#86efac';
                } elseif ($isYedek) {
                    $barColor = '#38bdf8';
                    $nameColor = '#bae6fd';
                } else {
                    $barColor = '#6b7280';
                    $nameColor = 'rgba(255,255,255,.7)';
                }
            ?>
            <div class="col-12 col-xl-6">
                <div class="d-flex align-items-center gap-3 rounded-3 px-3 py-2
                     <?= $isClosed && $isWinner ? 'winner-curtain-row' : '' ?>"
                     style="<?= $isClosed && $isWinner ? "background: rgba(34,197,94,.12); border: 2px solid rgba(34,197,94,.4);" : "background: rgba(255,255,255,.05);" ?>">

                    <!-- Sıra -->
                    <span style="color: rgba(255,255,255,.4); font-size:.9rem; min-width:1.4rem;"><?= $rank + 1 ?>.</span>

                    <!-- Avatar -->
                    <?php if (!empty($candidate['photo_path']) && file_exists(PUBLIC_PATH . $candidate['photo_path'])): ?>
                    <img src="<?= e($candidate['photo_path']) ?>" class="rounded-circle flex-shrink-0"
                         width="44" height="44" alt="" loading="lazy" style="object-fit:cover; border: 2px solid <?= $isWinner ? '#22c55e' : 'rgba(255,255,255,.15)' ?>;">
                    <?php else: ?>
                    <svg viewBox="0 0 44 44" width="44" height="44" class="rounded-circle flex-shrink-0">
                        <rect width="44" height="44" rx="22" fill="rgba(255,255,255,.08)"/>
                        <circle cx="22" cy="17" r="7" fill="rgba(255,255,255,.3)"/>
                        <path d="M5 42c0-8.3 7.6-13 17-13s17 4.7 17 13" fill="rgba(255,255,255,.3)"/>
                    </svg>
                    <?php endif; ?>

                    <!-- İsim + bar -->
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-truncate fw-<?= $isWinner ? 'bold' : 'normal' ?>"
                                  style="color: <?= $nameColor ?>; font-size: clamp(.9rem, 1.4vw, 1.2rem);">
                                <?= e($candidate['name']) ?>
                                <?php if ($isClosed && $isWinner): ?>
                                <i class="bi bi-trophy-fill ms-1" style="color:#ffd700;"></i>
                                <?php endif; ?>
                            </span>
                            <span class="fw-bold flex-shrink-0 ms-2" style="color:#fff; font-size: clamp(.9rem, 1.4vw, 1.2rem);"
                                  data-vote-count="<?= $voteCount ?>">
                                <?= $voteCount ?>
                            </span>
                        </div>
                        <div class="rounded-pill overflow-hidden" style="height:8px; background: rgba(255,255,255,.1);">
                            <div class="result-bar rounded-pill"
                                 style="height:100%; width:<?= $barWidth ?>%; background: <?= $barColor ?>; transition: width .6s ease;"
                                 data-max-votes="<?= $maxVotes ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div><!-- /row -->

        </div><!-- /ballot-section -->
        <?php endforeach; ?>
        <?php endif; ?>

    </main>

    <!-- ===== FOOTER — KATILIM ===== -->
    <footer class="py-3 px-4 border-top border-secondary" style="background: rgba(255,255,255,.04);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <span style="color: rgba(255,255,255,.6); font-size:.95rem;">
                    <i class="bi bi-people-fill me-1"></i>Katılım:
                </span>
                <span class="fw-bold" style="font-size:1.1rem;">
                    <span id="curtain-voted"><?= (int) $votedMembers ?></span>
                    / <span id="curtain-total"><?= (int) $totalMembers ?></span>
                    <span style="color: rgba(255,255,255,.5);">
                        (%<span id="curtain-pct"><?= $participationPct ?></span>)
                    </span>
                </span>
            </div>
            <div style="flex: 1; min-width: 200px; max-width: 400px;">
                <div class="rounded-pill overflow-hidden" style="height:10px; background: rgba(255,255,255,.12);">
                    <div id="curtain-participation-bar" class="rounded-pill"
                         style="height:100%; width:<?= $participationPct ?>%; background: #22c55e; transition: width .6s ease;">
                    </div>
                </div>
            </div>
            <?php if ($isOpen): ?>
            <span style="color: rgba(255,255,255,.4); font-size:.85rem;">
                <i class="bi bi-arrow-repeat me-1"></i>Canlı güncelleniyor
            </span>
            <?php endif; ?>
        </div>
    </footer>

</div>

<style>
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.2; }
}
.curtain-dot:hover {
    background: rgba(255,255,255,.7) !important;
}
</style>

<script src="/assets/js/sonuc.js"></script>
