/**
 * oylama.js — Üye Oylama Ekranı Mantığı (FAZ 3 a11y + recovery).
 *
 * Bağımlılık: window.OYLAMA (show.php'den aktarılır)
 *   - expiresAt   : string (Y-m-d H:i:s)
 *   - totalSteps  : number
 *   - ballots     : [{id, title, quota, candidates: [{id, name}]}]
 */
(function () {
    'use strict';

    const BALLOTS     = window.OYLAMA?.ballots    || [];
    const TOTAL_STEPS = window.OYLAMA?.totalSteps || 0;
    const EXPIRES_AT  = window.OYLAMA?.expiresAt  || null;

    let currentStep = 0;
    let countdownTimer = null;
    let expiryModalShownAt = null;

    // ------------------------------------------------------------------
    // DOM helpers
    // ------------------------------------------------------------------

    function $(sel)  { return document.querySelector(sel); }
    function $$(sel) { return Array.from(document.querySelectorAll(sel)); }
    function byId(id) { return document.getElementById(id); }

    function getCheckedInPanel(stepIndex) {
        const panel = byId('ballot-panel-' + stepIndex);
        if (!panel) return [];
        return Array.from(panel.querySelectorAll('input[type="checkbox"]:checked'));
    }

    // ------------------------------------------------------------------
    // Kota yönetimi
    // ------------------------------------------------------------------

    function updateQuota(stepIndex) {
        const ballot = BALLOTS[stepIndex];
        if (!ballot) return;

        const panel    = byId('ballot-panel-' + stepIndex);
        const checked  = getCheckedInPanel(stepIndex);
        const count    = checked.length;
        const quota    = ballot.quota;
        const countEl  = byId('count-' + stepIndex);
        const barEl    = byId('bar-' + stepIndex);

        if (countEl) countEl.textContent = count;

        if (barEl) {
            const pct = quota > 0 ? Math.round((count / quota) * 100) : 0;
            barEl.style.width = pct + '%';
            barEl.classList.toggle('full', count >= quota);
        }

        // Kotaya ulaşıldıysa seçilmemiş kartları pointer-events'siz hale getir
        const allCards = panel ? panel.querySelectorAll('.candidate-card') : [];
        allCards.forEach(card => {
            const chk = card.querySelector('input[type="checkbox"]');
            if (!chk) return;
            const disable = count >= quota && !chk.checked;
            card.classList.toggle('disabled', disable);
            card.setAttribute('aria-disabled', disable ? 'true' : 'false');
        });
    }

    // ------------------------------------------------------------------
    // Kart tıklama / klavye etkileşimi
    // ------------------------------------------------------------------

    function toggleCard(card) {
        if (card.classList.contains('disabled')) return;
        const chk = card.querySelector('input[type="checkbox"]');
        if (!chk) return;

        const stepIndex   = parseInt(card.dataset.ballotIndex, 10);
        const ballot      = BALLOTS[stepIndex];
        const quota       = ballot ? ballot.quota : Infinity;
        const checkedCount = getCheckedInPanel(stepIndex).length;

        if (!chk.checked && checkedCount >= quota) return;

        chk.checked = !chk.checked;
        card.classList.toggle('selected', chk.checked);
        chk.setAttribute('aria-checked', chk.checked ? 'true' : 'false');
        updateQuota(stepIndex);
    }

    function handleCardClick(e) {
        // <label> içinde input checkbox da var — browser kendi toggle'ı tetiklenmesin
        e.preventDefault();
        toggleCard(e.currentTarget);
    }

    function handleCardKey(e) {
        if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            toggleCard(e.currentTarget);
        }
    }

    // ------------------------------------------------------------------
    // Adım göstergesi
    // ------------------------------------------------------------------

    function updateStepBar(step) {
        for (let i = 0; i < TOTAL_STEPS; i++) {
            const dot = byId('step-dot-' + i);
            if (!dot) continue;
            dot.classList.remove('active', 'done');
            if (i < step)        dot.classList.add('done');
            else if (i === step) dot.classList.add('active');
        }
        const summaryDot = byId('step-dot-summary');
        if (summaryDot) {
            summaryDot.classList.remove('active', 'done');
            if (step === TOTAL_STEPS) summaryDot.classList.add('active');
        }
    }

    function showStep(step) {
        for (let i = 0; i < TOTAL_STEPS; i++) {
            const panel = byId('ballot-panel-' + i);
            if (panel) panel.classList.remove('active');
        }
        const summaryPanel = byId('summary-panel');
        if (summaryPanel) summaryPanel.classList.remove('active');

        if (step < TOTAL_STEPS) {
            const panel = byId('ballot-panel-' + step);
            if (panel) panel.classList.add('active');
        } else {
            buildSummary();
            if (summaryPanel) summaryPanel.classList.add('active');
        }

        updateStepBar(step);
        updateNavButtons(step);

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateNavButtons(step) {
        const prevBtn = byId('btn-prev');
        const nextBtn = byId('btn-next');

        if (prevBtn) prevBtn.disabled = (step === 0);

        if (nextBtn) {
            if (step >= TOTAL_STEPS) {
                nextBtn.style.display = 'none';
            } else {
                nextBtn.style.display = '';
                const isLast = (step === TOTAL_STEPS - 1);
                nextBtn.innerHTML = isLast
                    ? 'Özet ve Onay <i class="bi bi-check2-circle" aria-hidden="true"></i>'
                    : 'Sonraki <i class="bi bi-arrow-right" aria-hidden="true"></i>';
            }
        }
    }

    function navigateBallot(direction) {
        const newStep = currentStep + direction;
        if (newStep < 0 || newStep > TOTAL_STEPS) return;
        currentStep = newStep;
        showStep(currentStep);
    }

    // ------------------------------------------------------------------
    // Özet
    // ------------------------------------------------------------------

    function buildSummary() {
        const container   = byId('summary-content');
        const hiddenWrap  = byId('hidden-inputs');
        if (!container || !hiddenWrap) return;

        container.innerHTML  = '';
        hiddenWrap.innerHTML = '';

        BALLOTS.forEach((ballot, idx) => {
            const checked  = getCheckedInPanel(idx);
            const block    = document.createElement('div');
            block.className = 'summary-ballot';

            const title = document.createElement('div');
            title.className   = 'summary-ballot__title';
            title.textContent = ballot.title;
            block.appendChild(title);

            if (checked.length === 0) {
                const empty = document.createElement('div');
                empty.className   = 'summary-empty';
                empty.textContent = 'Bu kurul için seçim yapılmadı (boş oy).';
                block.appendChild(empty);
            } else {
                checked.forEach(chk => {
                    const card = chk.closest('.candidate-card');
                    const name = card ? card.dataset.name : chk.value;

                    const row = document.createElement('div');
                    row.className = 'summary-candidate-row';

                    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    icon.setAttribute('width', '16');
                    icon.setAttribute('height', '16');
                    icon.setAttribute('viewBox', '0 0 24 24');
                    icon.setAttribute('fill', 'none');
                    icon.setAttribute('aria-hidden', 'true');
                    icon.innerHTML = '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#15803d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';

                    const txt = document.createElement('span');
                    txt.style.cssText = 'font-size:.95rem;font-weight:500';
                    txt.textContent = name;  // DOM textContent → XSS-safe

                    row.appendChild(icon);
                    row.appendChild(txt);
                    block.appendChild(row);

                    // Gizli input
                    const hidden = document.createElement('input');
                    hidden.type  = 'hidden';
                    hidden.name  = 'ballot_' + ballot.id + '[]';
                    hidden.value = chk.value;
                    hiddenWrap.appendChild(hidden);
                });
            }

            container.appendChild(block);
        });
    }

    // ------------------------------------------------------------------
    // Form submit — recovery + double-click engeli
    // ------------------------------------------------------------------

    function handleSubmit(e) {
        const form    = byId('vote-form');
        const btn     = byId('submit-btn');
        const spinner = byId('submit-spinner');
        const label   = byId('submit-label');

        if (btn && btn.disabled) {
            e.preventDefault();
            return;
        }

        if (btn) btn.disabled = true;
        if (spinner) spinner.style.display = 'inline-block';
        if (label) label.textContent = 'Gönderiliyor…';
        if (form) form.style.pointerEvents = 'none';

        // 15 saniye sonra hâlâ buradayız demektir — recovery
        setTimeout(() => {
            if (!document.body.classList.contains('submitted-ok')) {
                if (btn) btn.disabled = false;
                if (spinner) spinner.style.display = 'none';
                if (label) label.innerHTML = '<i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Yeniden Dene';
                if (form) form.style.pointerEvents = '';
                if (window.Oyla?.showToast) {
                    window.Oyla.showToast('Sunucu cevap vermedi. Lütfen tekrar deneyin.', 'danger', 0);
                }
            }
        }, 15000);
    }

    // ------------------------------------------------------------------
    // Token süre takibi
    // ------------------------------------------------------------------

    function startCountdown() {
        if (!EXPIRES_AT) return;

        const expiry = new Date(EXPIRES_AT.replace(' ', 'T')).getTime();
        const timerEl = byId('countdown');
        if (!timerEl) return;

        function tick() {
            const diff = expiry - Date.now();

            if (diff <= 0) {
                timerEl.textContent = '00:00';
                timerEl.classList.add('critical');
                window.location.href = '/oy/verify?expired=1';
                return;
            }

            const totalSec = Math.floor(diff / 1000);
            const mins = Math.floor(totalSec / 60);
            const secs = totalSec % 60;
            timerEl.textContent =
                String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');

            // Eşik renkleri
            timerEl.classList.remove('warning', 'critical');
            if (totalSec <= 60) {
                timerEl.classList.add('critical');
            } else if (totalSec <= 300) {
                timerEl.classList.add('warning');
            }

            // 5 dakika ve 1 dakika modal'ı (her biri tek sefer)
            if (totalSec <= 60 && expiryModalShownAt !== 60) {
                showExpiryModal(secs > 0 ? mins * 60 + secs : 60, 'Son 1 dakika');
                expiryModalShownAt = 60;
            } else if (totalSec <= 300 && totalSec > 60 && expiryModalShownAt === null) {
                showExpiryModal(300, '5 dakika kaldı');
                expiryModalShownAt = 300;
            }
        }

        tick();
        countdownTimer = setInterval(tick, 1000);
    }

    function showExpiryModal(secondsLeft, title) {
        const modal = byId('expiry-modal');
        const desc  = modal?.querySelector('h2');
        const cd    = byId('expiry-countdown');
        if (!modal || !cd) return;

        if (desc) desc.innerHTML = '<i class="bi bi-clock-history" aria-hidden="true"></i> ' + title;
        cd.textContent = formatTime(secondsLeft);
        modal.classList.add('active');

        // Modal kendi countdown'ı
        let remaining = secondsLeft;
        const modalTimer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(modalTimer);
                return;
            }
            cd.textContent = formatTime(remaining);
        }, 1000);

        // Dismiss
        const dismiss = byId('expiry-dismiss');
        if (dismiss) {
            dismiss.addEventListener('click', () => {
                modal.classList.remove('active');
                clearInterval(modalTimer);
            }, { once: true });
        }

        // Sesli sinyal — yalnızca user-gesture sonrası tarayıcı izin verir
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.value = 880;
            gain.gain.value = 0.05;
            osc.start();
            osc.stop(audioCtx.currentTime + 0.18);
        } catch (e) {
            // sessiz başarısızlık — ses kritik değil
        }
    }

    function formatTime(secs) {
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    // ------------------------------------------------------------------
    // Cleanup
    // ------------------------------------------------------------------
    window.addEventListener('beforeunload', () => {
        if (countdownTimer) clearInterval(countdownTimer);
    });

    // ------------------------------------------------------------------
    // Başlatma
    // ------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.candidate-card').forEach(card => {
            card.addEventListener('click', handleCardClick);
            card.addEventListener('keydown', handleCardKey);
            card.setAttribute('role', 'checkbox');
            card.setAttribute('aria-checked', 'false');
        });

        for (let i = 0; i < TOTAL_STEPS; i++) {
            updateQuota(i);
        }

        const prev = byId('btn-prev');
        const next = byId('btn-next');
        if (prev) prev.addEventListener('click', () => navigateBallot(-1));
        if (next) next.addEventListener('click', () => navigateBallot(1));

        const form = byId('vote-form');
        if (form) form.addEventListener('submit', handleSubmit);

        showStep(0);
        startCountdown();
    });

})();
