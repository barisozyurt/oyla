<?php
/**
 * Üye Oylama Ekranı — /oy/{token}
 *
 * Değişkenler:
 *   $token      string   — Ham token (URL'de kullanılan)
 *   $election   array    — Seçim bilgisi
 *   $ballots    array    — Her biri: id, title, quota, yedek_quota, candidates[]
 *   $expires_at string   — Token bitiş zamanı
 *   $csrf       string   — CSRF gizli input HTML
 */

$bodyClass = 'voting-mode';
?>
<link rel="stylesheet" href="<?= asset('css/voting.css') ?>">

<header class="vote-header" role="banner">
    <span class="vote-header__title"><?= e($election['title']) ?></span>
    <span class="vote-header__timer" id="countdown" role="timer" aria-live="polite" aria-label="Kalan oy verme süresi">--:--</span>
</header>

<main class="vote-wrapper" id="vote-main" role="main">

    <!-- Adım göstergesi -->
    <nav class="step-bar" id="step-bar" aria-label="İlerleme">
        <?php foreach ($ballots as $i => $ballot): ?>
        <div class="step-dot <?= $i === 0 ? 'active' : '' ?>"
             id="step-dot-<?= $i ?>"
             aria-hidden="true"></div>
        <?php endforeach; ?>
        <div class="step-dot" id="step-dot-summary" aria-hidden="true"></div>
    </nav>

    <!-- Ballot panelleri -->
    <?php foreach ($ballots as $i => $ballot): ?>
    <section class="ballot-panel <?= $i === 0 ? 'active' : '' ?>"
             id="ballot-panel-<?= $i ?>"
             role="region"
             aria-label="Kurul <?= $i + 1 ?> / <?= count($ballots) ?>: <?= e($ballot['title']) ?>"
             data-ballot-index="<?= $i ?>"
             data-ballot-id="<?= (int) $ballot['id'] ?>"
             data-quota="<?= (int) $ballot['quota'] ?>">

        <h2 class="ballot-title"><?= e($ballot['title']) ?></h2>
        <p class="ballot-meta">
            <?php if ($ballot['description'] ?? ''): ?>
            <?= e($ballot['description']) ?> &mdash;
            <?php endif; ?>
            En fazla <strong><?= (int) $ballot['quota'] ?></strong> aday seçebilirsiniz
        </p>

        <div class="quota-label" id="quota-status-<?= $i ?>" aria-live="polite">
            <span>Seçilen:</span>
            <span><span id="count-<?= $i ?>">0</span> / <?= (int) $ballot['quota'] ?></span>
        </div>
        <div class="quota-bar-wrap" aria-hidden="true">
            <div class="quota-bar-fill" id="bar-<?= $i ?>" style="width: 0%"></div>
        </div>

        <div class="candidate-list" id="list-<?= $i ?>" role="group" aria-label="Aday listesi">
            <?php foreach ($ballot['candidates'] as $candidate): ?>
            <?php $cid = (int) $candidate['id']; ?>
            <label class="candidate-card"
                   data-cid="<?= $cid ?>"
                   data-ballot-index="<?= $i ?>"
                   data-name="<?= e($candidate['name']) ?>"
                   id="card-<?= $i ?>-<?= $cid ?>"
                   tabindex="0">
                <input type="checkbox"
                       name="ballot_<?= (int) $ballot['id'] ?>[]"
                       value="<?= $cid ?>"
                       id="chk-<?= $i ?>-<?= $cid ?>"
                       aria-labelledby="cname-<?= $i ?>-<?= $cid ?>">

                <?php if (!empty($candidate['photo_path']) && file_exists(dirname(__DIR__, 3) . '/public' . $candidate['photo_path'])): ?>
                <img class="candidate-avatar"
                     src="<?= e($candidate['photo_path']) ?>"
                     alt="Aday: <?= e($candidate['name']) ?>"
                     loading="lazy">
                <?php else: ?>
                <div class="candidate-avatar-anon" aria-hidden="true">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="20" cy="16" r="8" fill="#B4B2A9"/>
                        <path d="M4 38c0-8.8 7.2-14 16-14s16 5.2 16 14" fill="#B4B2A9"/>
                    </svg>
                </div>
                <?php endif; ?>

                <div class="candidate-info">
                    <div class="candidate-name" id="cname-<?= $i ?>-<?= $cid ?>"><?= e($candidate['name']) ?></div>
                    <?php if ($candidate['title'] ?? ''): ?>
                    <div class="candidate-title"><?= e($candidate['title']) ?></div>
                    <?php endif; ?>
                    <?php if ($candidate['candidate_no'] ?? ''): ?>
                    <div class="candidate-no">No: <?= e($candidate['candidate_no']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="candidate-check" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 7L5.5 10.5L12 3.5"
                              stroke="white" stroke-width="2.2"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </label>
            <?php endforeach; ?>
        </div>

        <?php if (empty($ballot['candidates'])): ?>
        <p class="summary-empty mt-3">Bu kurul için henüz aday eklenmemiş.</p>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>

    <!-- Özet / Son adım -->
    <section class="summary-panel" id="summary-panel" role="region" aria-label="Onay">
        <h2 class="summary-title">
            <i class="bi bi-clipboard-check text-primary me-2" aria-hidden="true"></i>Seçimlerinizi Onaylayın
        </h2>

        <div class="warning-box" role="alert">
            <span aria-hidden="true">&#9888;</span>
            <span>Oyunuzu gönderdikten sonra değişiklik <strong>yapamazsınız</strong>. Lütfen kontrol edin.</span>
        </div>

        <div id="summary-content"></div>

        <form method="POST" action="/oy/<?= e($token) ?>" id="vote-form" novalidate>
            <?= $csrf ?>
            <div id="hidden-inputs"></div>

            <button type="submit" class="btn-submit-vote" id="submit-btn">
                <span class="spinner-sm" id="submit-spinner" aria-hidden="true"></span>
                <span id="submit-label">
                    <i class="bi bi-lock-fill me-1" aria-hidden="true"></i>
                    Oyumu Gönder ve Kilitle
                </span>
            </button>
        </form>
    </section>

</main>

<!-- Alt navigasyon -->
<nav class="vote-nav" role="navigation" aria-label="Kurul navigasyonu">
    <button type="button" class="btn-vote-prev" id="btn-prev" disabled
            aria-label="Önceki kurul">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Önceki
    </button>
    <button type="button" class="btn-vote-next" id="btn-next"
            aria-label="Sonraki kurul">
        Sonraki <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </button>
</nav>

<!-- Token süresi dolmak üzere modal'ı -->
<div class="token-expiry-modal" id="expiry-modal" role="alertdialog" aria-modal="true" aria-labelledby="expiry-title" aria-describedby="expiry-desc">
    <div class="token-expiry-card">
        <h2 id="expiry-title"><i class="bi bi-clock-history" aria-hidden="true"></i> Süre Bitiyor</h2>
        <p id="expiry-desc">Oy verme süresi dolmak üzere. Oyunuzu hemen tamamlayın, yoksa bu bağlantı geçersiz olacak.</p>
        <div class="countdown" id="expiry-countdown" aria-live="assertive">--:--</div>
        <button type="button" id="expiry-dismiss">Anladım, devam et</button>
    </div>
</div>

<!-- Veri köprüsü -->
<script>
    window.OYLAMA = {
        expiresAt : <?= json_encode($expires_at) ?>,
        totalSteps: <?= count($ballots) ?>,
        ballots   : <?= json_encode(array_values(array_map(fn($b) => [
            'id'    => (int) $b['id'],
            'title' => $b['title'],
            'quota' => (int) $b['quota'],
            'candidates' => array_values(array_map(fn($c) => [
                'id'   => (int) $c['id'],
                'name' => $c['name'],
            ], $b['candidates'])),
        ], $ballots))) ?>,
    };
</script>
<script src="<?= asset('js/oylama.js') ?>"></script>
