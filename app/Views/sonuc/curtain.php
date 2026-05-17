<?php
/**
 * Perde / Salon Ekranı — Fullscreen (fullscreen layout)
 * Karanlık tema (data-theme="dark" body'de zaten)
 */

$isClosed = ($election['status'] === 'closed');
$isOpen   = ($election['status'] === 'open');
$participationPct = $totalMembers > 0 ? round($votedMembers / $totalMembers * 100, 1) : 0.0;
?>
<style>
    /* Perde modu — dark, klasik posta sergileme */
    .curtain {
        min-height: 100vh;
        background: #0c1217;
        color: #e9e7e2;
        font-family: var(--font-sans);
        display: flex;
        flex-direction: column;
    }
    .curtain__hd {
        text-align: center;
        padding: var(--s-8) var(--s-5) var(--s-6);
        border-bottom: 1px solid rgba(184,153,104,0.25);
        background: linear-gradient(180deg, rgba(184,153,104,0.06) 0%, transparent 100%);
    }
    .curtain__brand {
        display: inline-flex;
        align-items: center;
        gap: var(--s-3);
        margin-bottom: var(--s-5);
    }
    .curtain__brand svg { color: var(--brass-300); }
    .curtain__brand-name {
        font-family: var(--font-serif);
        font-weight: 700;
        font-size: var(--t-2xl);
        letter-spacing: -0.01em;
        color: #faf8f3;
    }
    .curtain__title {
        font-family: var(--font-serif);
        font-weight: 700;
        font-size: clamp(var(--t-2xl), 3.5vw, var(--t-5xl));
        margin: 0;
        color: #faf8f3;
        line-height: 1.15;
    }
    .curtain__chip {
        display: inline-flex;
        align-items: center;
        gap: var(--s-2);
        margin-top: var(--s-4);
        padding: var(--s-2) var(--s-5);
        border: 1.5px solid var(--brass-500);
        color: var(--brass-300);
        font-family: var(--font-sans);
        font-weight: 600;
        font-size: var(--t-sm);
        letter-spacing: 0.18em;
        text-transform: uppercase;
        border-radius: 2px;
    }
    .curtain__chip--live { border-color: var(--ink-300); color: var(--ink-200); }
    .curtain__chip--live::before {
        content: '';
        width: 8px; height: 8px;
        background: var(--ink-300);
        border-radius: 50%;
        animation: pulse-dot 1.5s infinite;
    }
    @keyframes pulse-dot { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

    .curtain__main {
        flex: 1;
        max-width: 1400px;
        margin: 0 auto;
        padding: var(--s-8) var(--s-5);
        width: 100%;
    }
    .curtain__dots {
        display: flex;
        gap: var(--s-2);
        justify-content: center;
        margin-bottom: var(--s-6);
    }
    .curtain-dot {
        width: 32px; height: 4px;
        background: rgba(255,255,255,0.15);
        border: 0;
        cursor: pointer;
        border-radius: 2px;
        transition: background 200ms ease;
    }
    .curtain-dot.active { background: var(--brass-300); }
    .curtain-dot:hover  { background: rgba(255,255,255,0.4); }

    .curtain__ballot-title {
        text-align: center;
        font-family: var(--font-serif);
        font-weight: 600;
        font-size: clamp(var(--t-xl), 2.5vw, var(--t-3xl));
        color: #faf8f3;
        margin: 0;
    }
    .curtain__ballot-meta {
        text-align: center;
        font-size: var(--t-sm);
        color: rgba(255,255,255,0.55);
        margin: var(--s-2) 0 var(--s-6);
        font-variant-numeric: tabular-nums;
    }

    .curtain__row {
        display: flex;
        align-items: center;
        gap: var(--s-3);
        padding: var(--s-3) var(--s-4);
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: var(--r-sm);
        margin-bottom: var(--s-2);
    }
    .curtain__row--win {
        background: rgba(29,158,117,0.10);
        border-color: rgba(29,158,117,0.4);
        border-left: 3px solid var(--ink-400);
    }
    .curtain__rank {
        font-family: var(--font-serif);
        font-weight: 600;
        color: rgba(255,255,255,0.4);
        font-size: var(--t-md);
        min-width: 28px;
        font-variant-numeric: tabular-nums;
    }
    .curtain__avatar {
        width: 44px; height: 44px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.15);
        flex-shrink: 0;
        overflow: hidden;
        display: grid; place-items: center;
        color: rgba(255,255,255,0.4);
    }
    .curtain__avatar img { width:100%;height:100%;object-fit:cover; }
    .curtain__row--win .curtain__avatar { border-color: var(--ink-400); }
    .curtain__name {
        flex: 1; min-width: 0;
    }
    .curtain__name-text {
        display: block;
        font-size: clamp(var(--t-md), 1.4vw, var(--t-xl));
        color: #faf8f3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .curtain__row--win .curtain__name-text { font-weight: 600; }
    .curtain__bar-wrap {
        height: 6px;
        background: rgba(255,255,255,0.08);
        border-radius: 2px;
        overflow: hidden;
        margin-top: var(--s-1);
    }
    .curtain__bar {
        height: 100%;
        background: var(--char-400);
        transition: width 600ms ease;
    }
    .curtain__bar--win   { background: var(--ink-400); }
    .curtain__bar--yedek { background: var(--brass-400, var(--brass-300)); }
    .curtain__count {
        font-family: var(--font-serif);
        font-weight: 600;
        font-size: clamp(var(--t-md), 1.4vw, var(--t-xl));
        color: #faf8f3;
        font-variant-numeric: tabular-nums;
        min-width: 60px;
        text-align: right;
    }

    .curtain__ft {
        border-top: 1px solid rgba(184,153,104,0.25);
        padding: var(--s-5);
        display: flex;
        align-items: center;
        gap: var(--s-5);
        flex-wrap: wrap;
        background: rgba(0,0,0,0.2);
    }
    .curtain__ft-label {
        color: rgba(255,255,255,0.5);
        font-size: var(--t-sm);
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .curtain__ft-value {
        font-family: var(--font-serif);
        font-weight: 600;
        font-size: var(--t-lg);
        color: #faf8f3;
        font-variant-numeric: tabular-nums;
    }
    .curtain__ft-bar {
        flex: 1; min-width: 200px;
        height: 8px;
        background: rgba(255,255,255,0.1);
        border-radius: 2px;
        overflow: hidden;
    }
    .curtain__ft-fill { height: 100%; background: var(--ink-400); transition: width 600ms ease; }
</style>

<div class="curtain">

    <header class="curtain__hd">
        <div class="curtain__brand">
            <span aria-hidden="true"><?php @readfile(PUBLIC_PATH . '/assets/img/logo-mono.svg'); ?></span>
            <span class="curtain__brand-name">Oyla</span>
        </div>
        <h1 class="curtain__title"><?= e($election['title']) ?></h1>
        <?php if ($isClosed): ?>
        <div class="curtain__chip">Resmî Sonuçlar</div>
        <?php elseif ($isOpen): ?>
        <div class="curtain__chip curtain__chip--live">Devam Ediyor</div>
        <?php endif; ?>
    </header>

    <main class="curtain__main">

        <?php if (empty($results)): ?>
        <div style="text-align:center;color:rgba(255,255,255,0.5);padding:var(--s-16);">
            <p style="font-family:var(--font-serif);font-size:var(--t-xl);">Seçim kurulları henüz tanımlanmamış.</p>
        </div>
        <?php else: ?>

        <?php if (count($results) > 1): ?>
        <div class="curtain__dots" id="curtain-dots">
            <?php foreach ($results as $i => $_): ?>
            <button class="curtain-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>" aria-label="Kurul <?= $i+1 ?>"></button>
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

            <h2 class="curtain__ballot-title"><?= e($ballot['title']) ?></h2>
            <p class="curtain__ballot-meta">
                <?= $quota ?> asıl<?php if ($yedekQuota > 0): ?> · <?= $yedekQuota ?> yedek<?php endif; ?>
                · Toplam oy: <span id="curtain-total-votes-<?= $i ?>"><?= $totalVotes ?></span>
            </p>

            <div class="ds-grid ds-grid-cols-2 ds-grid-cols-sm-1 ds-gap-3" id="curtain-chart-<?= $i ?>">
            <?php foreach ($candidates as $rank => $candidate):
                $voteCount = (int) $candidate['vote_count'];
                $barWidth  = $maxVotes > 0 ? round($voteCount / $maxVotes * 100) : 0;
                $isWinner  = ($rank + 1) <= $quota;
                $isYedek   = !$isWinner && ($rank + 1) <= ($quota + $yedekQuota);
                $barClass = $isWinner ? 'curtain__bar--win' : ($isYedek ? 'curtain__bar--yedek' : '');
            ?>
            <article class="curtain__row <?= $isWinner ? 'curtain__row--win' : '' ?>">
                <span class="curtain__rank"><?= $rank + 1 ?></span>
                <?php if (!empty($candidate['photo_path']) && file_exists(PUBLIC_PATH . $candidate['photo_path'])): ?>
                <span class="curtain__avatar"><img src="<?= e($candidate['photo_path']) ?>" alt="" loading="lazy"></span>
                <?php else: ?>
                <span class="curtain__avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
                <?php endif; ?>
                <div class="curtain__name">
                    <span class="curtain__name-text">
                        <?= e($candidate['name']) ?>
                        <?php if ($isClosed && $isWinner): ?>
                        <i class="bi bi-trophy" style="color:var(--brass-300);font-size:0.85em;" aria-hidden="true"></i>
                        <?php endif; ?>
                    </span>
                    <div class="curtain__bar-wrap">
                        <div class="result-bar curtain__bar <?= $barClass ?>" style="width:<?= $barWidth ?>%" data-max-votes="<?= $maxVotes ?>"></div>
                    </div>
                </div>
                <span class="curtain__count" data-vote-count="<?= $voteCount ?>"><?= $voteCount ?></span>
            </article>
            <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
        <?php endif; ?>

    </main>

    <footer class="curtain__ft">
        <span class="curtain__ft-label"><i class="bi bi-people" aria-hidden="true"></i> Katılım</span>
        <span class="curtain__ft-value">
            <span id="curtain-voted"><?= (int) $votedMembers ?></span>
            <span style="color:rgba(255,255,255,0.4)">/</span>
            <span id="curtain-total"><?= (int) $totalMembers ?></span>
            <span style="color:rgba(255,255,255,0.5);font-size:var(--t-sm);font-weight:400;">(%<span id="curtain-pct"><?= $participationPct ?></span>)</span>
        </span>
        <div class="curtain__ft-bar">
            <div class="curtain__ft-fill" id="curtain-participation-bar" style="width:<?= $participationPct ?>%"></div>
        </div>
        <?php if ($isOpen): ?>
        <span class="curtain__ft-label" style="font-size:var(--t-xs);"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Canlı</span>
        <?php endif; ?>
    </footer>
</div>

<script src="<?= asset('js/sonuc.js') ?>"></script>
