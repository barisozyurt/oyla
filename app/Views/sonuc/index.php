<?php
/**
 * Sonuç Ekranı — Herkese Açık
 */
$isClosed = ($election['status'] === 'closed');
$isOpen   = ($election['status'] === 'open');

$statusMeta = [
    'draft'  => ['label' => 'Taslak',        'class' => 'ds-badge--neutral'],
    'test'   => ['label' => 'Test',          'class' => 'ds-badge--warn'],
    'open'   => ['label' => 'Devam Ediyor',  'class' => 'ds-badge--ink ds-badge--live'],
    'closed' => ['label' => 'Kapandı',       'class' => 'ds-badge--brass'],
];
$badge = $statusMeta[$election['status']] ?? ['label' => $election['status'], 'class' => 'ds-badge--neutral'];
?>

<?php if ($isClosed): ?>
<div class="ds-card ds-card--certificate ds-mb-6">
    <div class="ds-card__inner ds-flex ds-items-center ds-gap-4">
        <i class="bi bi-patch-check" style="font-size:var(--t-4xl);color:var(--brass-600);" aria-hidden="true"></i>
        <div class="ds-flex-1">
            <p class="ds-font-serif ds-font-bold ds-text-xl" style="margin:0;color:var(--char-800);letter-spacing:0.04em;">RESMÎ SONUÇLAR</p>
            <p class="ds-text-sm ds-text-muted" style="margin:0;">Seçim tamamlanmıştır. Aşağıdaki sonuçlar kesinleşmiştir.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<header class="ds-page-header">
    <div class="ds-page-header__row">
        <div>
            <p class="ds-page-header__eyebrow">Canlı Sonuç</p>
            <h1 class="ds-page-header__title"><?= e($election['title']) ?></h1>
            <div class="ds-flex ds-items-center ds-gap-3 ds-mt-3 ds-flex-wrap">
                <span class="ds-badge <?= $badge['class'] ?>"><?= e($badge['label']) ?></span>
                <?php if ($isOpen): ?>
                <span class="ds-text-xs ds-text-muted" id="refresh-indicator">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                    Her 5 saniyede güncelleniyor
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="ds-flex ds-gap-2 ds-flex-wrap">
            <a href="/sonuc/curtain" class="ds-btn ds-btn--secondary" target="_blank" rel="noopener">
                <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i>Perde Modu
            </a>
            <a href="/oy/dogrula" class="ds-btn ds-btn--secondary">
                <i class="bi bi-shield-check" aria-hidden="true"></i>Makbuz Doğrula
            </a>
        </div>
    </div>
</header>

<?php
$participationPct = ($totalMembers ?? 0) > 0 ? round(($votedMembers ?? 0) / $totalMembers * 100, 1) : 0;
?>
<section class="ds-card ds-mb-6" aria-labelledby="part-h">
    <header class="ds-card__header">
        <div>
            <h2 id="part-h" class="ds-card__title">Katılım</h2>
            <p class="ds-card__subtitle">Oy kullanan üye sayısı</p>
        </div>
        <span class="ds-font-mono ds-tabular ds-text-body">
            <strong id="voted-count"><?= (int) ($votedMembers ?? 0) ?></strong>
            <span class="ds-text-muted"> / </span>
            <strong id="total-count"><?= (int) ($totalMembers ?? 0) ?></strong>
            <span class="ds-text-muted">(%<span id="participation-pct"><?= $participationPct ?></span>)</span>
        </span>
    </header>
    <div class="ds-progress ds-progress--lg" role="progressbar" aria-valuenow="<?= $participationPct ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="ds-progress__bar" id="participation-bar" style="width:<?= $participationPct ?>%"></div>
    </div>
</section>

<?php if (empty($results)): ?>
<div class="ds-empty">
    <svg class="ds-empty__mark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <rect x="10" y="14" width="44" height="40" rx="3"/>
        <line x1="18" y1="26" x2="46" y2="26"/>
    </svg>
    <p class="ds-empty__title">Henüz seçim kurulu tanımlanmamış</p>
</div>
<?php else: ?>

<?php if (count($results) > 1): ?>
<div class="ds-flex ds-gap-1 ds-mb-5 ds-flex-wrap" id="ballot-tabs" role="tablist" style="border-bottom: 1px solid var(--line);">
    <?php foreach ($results as $i => $r): ?>
    <button class="ballot-tab-btn"
            data-ballot-index="<?= $i ?>"
            role="tab"
            aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
            style="background:transparent;border:0;padding:var(--s-3) var(--s-5);font-family:var(--font-sans);font-size:var(--t-sm);font-weight:500;color:<?= $i === 0 ? 'var(--ink-700)' : 'var(--char-500)' ?>;cursor:pointer;border-bottom:2px solid <?= $i === 0 ? 'var(--ink-600)' : 'transparent' ?>;margin-bottom:-1px;">
        <?= e($r['ballot']['title']) ?>
    </button>
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
        if ((int) $c['vote_count'] > $maxVotes) $maxVotes = (int) $c['vote_count'];
    }
?>
<section class="ballot-section"
         id="ballot-section-<?= $i ?>"
         data-ballot-id="<?= (int) $ballot['id'] ?>"
         data-quota="<?= $quota ?>"
         data-yedek-quota="<?= $yedekQuota ?>"
         style="<?= $i > 0 ? 'display:none;' : '' ?>">

    <header class="ds-flex ds-justify-between ds-items-end ds-mb-4 ds-flex-wrap ds-gap-3">
        <div>
            <h2 class="ds-font-serif ds-font-bold ds-text-2xl" style="color:var(--char-800);margin:0 0 var(--s-1);">
                <?= e($ballot['title']) ?>
            </h2>
            <?php if ($ballot['description']): ?>
            <p class="ds-text-sm ds-text-muted" style="margin:0;"><?= e($ballot['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="ds-flex ds-gap-2 ds-flex-wrap ds-items-center">
            <span class="ds-badge ds-badge--ink"><i class="bi bi-trophy" aria-hidden="true"></i> <?= $quota ?> Asıl</span>
            <?php if ($yedekQuota > 0): ?>
            <span class="ds-badge ds-badge--brass"><i class="bi bi-bookmark" aria-hidden="true"></i> <?= $yedekQuota ?> Yedek</span>
            <?php endif; ?>
            <span class="ds-text-xs ds-text-muted ds-tabular" id="total-votes-<?= $i ?>">Toplam oy: <strong class="ds-text-body"><?= $totalVotes ?></strong></span>
        </div>
    </header>

    <div class="ds-card" style="padding: var(--s-4);">
        <div class="result-chart" id="result-chart-<?= $i ?>">
        <?php foreach ($candidates as $rank => $candidate):
            $voteCount = (int) $candidate['vote_count'];
            $barWidth  = $maxVotes > 0 ? round($voteCount / $maxVotes * 100) : 0;
            $isWinner  = ($rank + 1) <= $quota;
            $isYedek   = !$isWinner && ($rank + 1) <= ($quota + $yedekQuota);

            $barStyle = $isWinner
                ? 'background: var(--ink-600);'
                : ($isYedek ? 'background: var(--brass-500);' : 'background: var(--char-300);');

            $rowStyle = $isClosed && $isWinner
                ? 'background: var(--ink-50); border: 1px solid var(--ink-200); border-left: 3px solid var(--ink-600);'
                : 'border: 1px solid var(--line);';
        ?>
        <article class="result-row" style="padding: var(--s-3); border-radius: var(--r-sm); margin-bottom: var(--s-2); <?= $rowStyle ?>">
            <div class="ds-flex ds-items-center ds-gap-3 ds-mb-2">
                <span class="ds-text-xs ds-text-muted ds-tabular" style="min-width: 24px;"><?= $rank + 1 ?>.</span>
                <?php if (!empty($candidate['photo_path']) && file_exists(PUBLIC_PATH . $candidate['photo_path'])): ?>
                <img src="<?= e($candidate['photo_path']) ?>" class="ds-avatar" alt="" loading="lazy" style="width:36px;height:36px;">
                <?php else: ?>
                <span class="ds-avatar" aria-hidden="true">
                    <i class="bi bi-person"></i>
                </span>
                <?php endif; ?>
                <div class="ds-flex-1" style="min-width: 0;">
                    <p class="<?= $isWinner ? 'ds-font-semi' : '' ?> ds-text-body" style="margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= e($candidate['name']) ?>
                        <?php if ($isClosed && $isWinner): ?>
                        <i class="bi bi-trophy" style="color:var(--brass-600);font-size:13px;" aria-hidden="true" title="Asıl üye"></i>
                        <?php elseif ($isClosed && $isYedek): ?>
                        <i class="bi bi-bookmark" style="color:var(--brass-500);font-size:13px;" aria-hidden="true" title="Yedek"></i>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($candidate['candidate_no'])): ?>
                    <p class="ds-text-xs ds-text-muted" style="margin:0;">Aday no: <?= e($candidate['candidate_no']) ?></p>
                    <?php endif; ?>
                </div>
                <span class="ds-font-mono ds-tabular ds-font-semi ds-text-body" style="min-width: 40px; text-align: right;" data-vote-count="<?= $voteCount ?>"><?= $voteCount ?></span>
            </div>
            <div class="ds-progress" style="height:6px;margin-left:60px;">
                <div class="result-bar" style="height:100%;border-radius:2px;width:<?= $barWidth ?>%;<?= $barStyle ?>transition:width 450ms var(--ease);" data-max-votes="<?= $maxVotes ?>"></div>
            </div>
        </article>
        <?php endforeach; ?>
        </div>
    </div>

</section>
<?php endforeach; ?>
<?php endif; ?>

<script src="<?= asset('js/sonuc.js') ?>"></script>
