<?php
/**
 * Üye Oylama Ekranı — /oy/{token}
 *
 * Değişkenler:
 *   $token      string   — Ham token (URL'de kullanılan)
 *   $election   array    — Seçim bilgisi (id, title, …)
 *   $ballots    array    — Her biri: id, title, quota, yedek_quota, candidates[]
 *   $expires_at string   — Token bitiş zamanı (Y-m-d H:i:s)
 *   $csrf       string   — CSRF gizli input HTML
 */

$bodyClass = 'voting-mode';
?>
<style>
    /* Oylama ekranına özgü stiller */
    :root {
        --vote-primary:   #1D9E75;
        --vote-success:   #16a34a;
        --vote-danger:    #dc2626;
        --vote-surface:   #ffffff;
        --vote-border:    #e2e8f0;
        --vote-muted:     #64748b;
        --vote-text:      #1e293b;
        --vote-selected:  #e8f5ef;
        --vote-selected-border: #1D9E75;
        --vote-disabled-bg: #f1f5f9;
        --vote-disabled-text: #94a3b8;
    }

    /* ---- Genel kapsayıcı ---- */
    .vote-wrapper {
        max-width: 480px;
        margin: 0 auto;
        padding: 0 12px 80px;
        color: var(--vote-text);
        font-family: "Source Sans 3", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }

    /* ---- Üst başlık şeridi ---- */
    .vote-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: var(--vote-primary);
        color: #fff;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 8px rgba(0,0,0,.2);
    }
    .vote-header__title {
        font-size: .95rem;
        font-weight: 600;
        max-width: 200px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .vote-header__timer {
        font-size: .85rem;
        background: rgba(255,255,255,.15);
        border-radius: 4px;
        padding: 3px 8px;
        font-variant-numeric: tabular-nums;
    }
    .vote-header__timer.warning {
        background: #fbbf24;
        color: #1e293b;
        font-weight: 700;
    }

    /* ---- Adım göstergesi ---- */
    .step-bar {
        display: flex;
        gap: 4px;
        padding: 12px 0 0;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .step-bar::-webkit-scrollbar { display: none; }
    .step-dot {
        flex: 1;
        min-width: 32px;
        height: 4px;
        border-radius: 2px;
        background: var(--vote-border);
        transition: background .2s;
    }
    .step-dot.active  { background: var(--vote-primary); }
    .step-dot.done    { background: var(--vote-success); }

    /* ---- Kurul paneli ---- */
    .ballot-panel {
        display: none;
        margin-top: 16px;
    }
    .ballot-panel.active { display: block; }

    .ballot-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .ballot-meta {
        font-size: .85rem;
        color: var(--vote-muted);
        margin-bottom: 12px;
    }

    /* ---- Kota progress ---- */
    .quota-bar-wrap {
        background: var(--vote-border);
        border-radius: 6px;
        height: 8px;
        margin-bottom: 16px;
        overflow: hidden;
    }
    .quota-bar-fill {
        height: 100%;
        border-radius: 6px;
        background: var(--vote-primary);
        transition: width .2s;
    }
    .quota-bar-fill.full { background: var(--vote-success); }
    .quota-label {
        font-size: .8rem;
        color: var(--vote-muted);
        margin-bottom: 6px;
        display: flex;
        justify-content: space-between;
    }

    /* ---- Aday kartları ---- */
    .candidate-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .candidate-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--vote-surface);
        border: 2px solid var(--vote-border);
        border-radius: 10px;
        padding: 12px 14px;
        cursor: pointer;
        min-height: 64px;
        transition: border-color .15s, background .15s, opacity .15s;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
    }
    .candidate-card.selected {
        border-color: var(--vote-selected-border);
        background: var(--vote-selected);
    }
    .candidate-card.disabled {
        opacity: .45;
        cursor: not-allowed;
        background: var(--vote-disabled-bg);
    }
    .candidate-card input[type="checkbox"] {
        display: none;
    }
    .candidate-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        background: #e2e8f0;
    }
    .candidate-avatar-anon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #e2e8f0;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .candidate-avatar-anon svg {
        width: 32px;
        height: 32px;
    }
    .candidate-info {
        flex: 1;
        min-width: 0;
    }
    .candidate-name {
        font-weight: 600;
        font-size: .95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .candidate-title {
        font-size: .8rem;
        color: var(--vote-muted);
    }
    .candidate-no {
        font-size: .75rem;
        color: var(--vote-muted);
    }
    .candidate-check {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid var(--vote-border);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background .15s, border-color .15s;
    }
    .candidate-card.selected .candidate-check {
        background: var(--vote-primary);
        border-color: var(--vote-primary);
    }
    .candidate-check svg {
        display: none;
    }
    .candidate-card.selected .candidate-check svg {
        display: block;
    }

    /* ---- Navigasyon butonları ---- */
    .vote-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--vote-surface);
        border-top: 1px solid var(--vote-border);
        padding: 12px 16px;
        display: flex;
        gap: 10px;
        z-index: 99;
    }
    .btn-vote-prev,
    .btn-vote-next {
        flex: 1;
        padding: 14px 0;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: opacity .15s;
    }
    .btn-vote-prev {
        background: var(--vote-border);
        color: var(--vote-text);
    }
    .btn-vote-prev:disabled {
        opacity: .4;
        cursor: not-allowed;
    }
    .btn-vote-next {
        background: var(--vote-primary);
        color: #fff;
    }

    /* ---- Özet (son adım) ---- */
    .summary-panel {
        display: none;
        margin-top: 16px;
    }
    .summary-panel.active { display: block; }
    .summary-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 16px;
        text-align: center;
    }
    .summary-ballot {
        background: var(--vote-surface);
        border: 1px solid var(--vote-border);
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 12px;
    }
    .summary-ballot__title {
        font-weight: 600;
        font-size: .9rem;
        color: var(--vote-muted);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .summary-candidate-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 0;
        border-bottom: 1px solid var(--vote-border);
    }
    .summary-candidate-row:last-child { border-bottom: none; }
    .summary-empty {
        font-size: .85rem;
        color: var(--vote-muted);
        font-style: italic;
    }

    /* ---- Son gönder butonu ---- */
    .btn-submit-vote {
        width: 100%;
        background: var(--vote-danger);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 16px;
        font-size: 1.05rem;
        font-weight: 700;
        cursor: pointer;
        margin-top: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: opacity .15s;
    }
    .btn-submit-vote:disabled {
        opacity: .6;
        cursor: not-allowed;
    }
    .spinner-sm {
        width: 18px;
        height: 18px;
        border: 3px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .6s linear infinite;
        display: none;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .warning-box {
        background: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 8px;
        padding: 12px 14px;
        font-size: .85rem;
        color: #92400e;
        margin-bottom: 12px;
        display: flex;
        gap: 8px;
    }

    @media (min-width: 576px) {
        .vote-nav { max-width: 480px; left: 50%; transform: translateX(-50%); }
    }
</style>

<!-- Üst başlık -->
<div class="vote-header">
    <span class="vote-header__title"><?= e($election['title']) ?></span>
    <span class="vote-header__timer" id="countdown" aria-live="polite" aria-label="Kalan süre">--:--</span>
</div>

<div class="vote-wrapper">

    <!-- Adım göstergesi -->
    <div class="step-bar" id="step-bar">
        <?php foreach ($ballots as $i => $ballot): ?>
        <div class="step-dot <?= $i === 0 ? 'active' : '' ?>"
             id="step-dot-<?= $i ?>"></div>
        <?php endforeach; ?>
        <div class="step-dot" id="step-dot-summary"></div>
    </div>

    <!-- Ballot panelleri -->
    <?php foreach ($ballots as $i => $ballot): ?>
    <div class="ballot-panel <?= $i === 0 ? 'active' : '' ?>"
         id="ballot-panel-<?= $i ?>"
         data-ballot-index="<?= $i ?>"
         data-ballot-id="<?= (int) $ballot['id'] ?>"
         data-quota="<?= (int) $ballot['quota'] ?>">

        <div class="ballot-title"><?= e($ballot['title']) ?></div>
        <div class="ballot-meta">
            <?php if ($ballot['description'] ?? ''): ?>
            <?= e($ballot['description']) ?> &mdash;
            <?php endif; ?>
            En fazla <strong><?= (int) $ballot['quota'] ?></strong> aday seçebilirsiniz
        </div>

        <!-- Kota progress -->
        <div class="quota-label">
            <span>Seçilen:</span>
            <span><span id="count-<?= $i ?>">0</span> / <?= (int) $ballot['quota'] ?></span>
        </div>
        <div class="quota-bar-wrap">
            <div class="quota-bar-fill" id="bar-<?= $i ?>"
                 style="width: 0%"></div>
        </div>

        <!-- Adaylar -->
        <div class="candidate-list" id="list-<?= $i ?>">
            <?php foreach ($ballot['candidates'] as $candidate): ?>
            <?php $cid = (int) $candidate['id']; ?>
            <label class="candidate-card"
                   data-cid="<?= $cid ?>"
                   data-ballot-index="<?= $i ?>"
                   data-name="<?= e($candidate['name']) ?>"
                   id="card-<?= $i ?>-<?= $cid ?>">
                <input type="checkbox"
                       name="ballot_<?= (int) $ballot['id'] ?>[]"
                       value="<?= $cid ?>"
                       id="chk-<?= $i ?>-<?= $cid ?>">

                <?php if (!empty($candidate['photo_path']) && file_exists(dirname(__DIR__, 3) . '/public' . $candidate['photo_path'])): ?>
                <img class="candidate-avatar"
                     src="<?= e($candidate['photo_path']) ?>"
                     alt="<?= e($candidate['name']) ?>">
                <?php else: ?>
                <div class="candidate-avatar-anon" aria-hidden="true">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="20" cy="16" r="8" fill="#B4B2A9"/>
                        <path d="M4 38c0-8.8 7.2-14 16-14s16 5.2 16 14" fill="#B4B2A9"/>
                    </svg>
                </div>
                <?php endif; ?>

                <div class="candidate-info">
                    <div class="candidate-name"><?= e($candidate['name']) ?></div>
                    <?php if ($candidate['title'] ?? ''): ?>
                    <div class="candidate-title"><?= e($candidate['title']) ?></div>
                    <?php endif; ?>
                    <?php if ($candidate['candidate_no'] ?? ''): ?>
                    <div class="candidate-no">No: <?= e($candidate['candidate_no']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="candidate-check">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 7L5.5 10.5L12 3.5"
                              stroke="white" stroke-width="2.2"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </label>
            <?php endforeach; ?>
        </div>

        <?php if (empty($ballot['candidates'])): ?>
        <div class="summary-empty mt-3">Bu kurul için henüz aday eklenmemiş.</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Özet / Son adım -->
    <div class="summary-panel" id="summary-panel">
        <div class="summary-title">
            <i class="bi bi-clipboard-check text-primary me-2"></i>Seçimlerinizi Onaylayın
        </div>

        <div class="warning-box">
            <span>&#9888;</span>
            <span>Oyunuzu gönderdikten sonra değişiklik <strong>yapamazsınız</strong>. Lütfen kontrol edin.</span>
        </div>

        <div id="summary-content">
            <!-- JS tarafından doldurulur -->
        </div>

        <?php /* Form tüm oylama alanlarını kapsar */ ?>
        <form method="POST" action="/oy/<?= e($token) ?>" id="vote-form"
              onsubmit="return handleSubmit(event)">
            <?= $csrf ?>
            <!-- Gizli inputlar JS tarafından doldurulur -->
            <div id="hidden-inputs"></div>

            <button type="submit" class="btn-submit-vote" id="submit-btn">
                <div class="spinner-sm" id="submit-spinner"></div>
                <span id="submit-label">&#128274; Oyumu Gönder ve Kilitle</span>
            </button>
        </form>
    </div>

</div><!-- .vote-wrapper -->

<!-- Alt navigasyon -->
<div class="vote-nav">
    <button class="btn-vote-prev" id="btn-prev" disabled
            onclick="navigateBallot(-1)">&#8592; Önceki</button>
    <button class="btn-vote-next" id="btn-next"
            onclick="navigateBallot(1)">Sonraki Kurul &#8594;</button>
</div>

<!-- Veri köprüsü: PHP → JS -->
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
