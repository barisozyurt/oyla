/**
 * oylama.js — Üye Oylama Ekranı Mantığı
 *
 * Bağımlılık: window.OYLAMA (show.php'den aktarılır)
 *   - expiresAt   : string (Y-m-d H:i:s)
 *   - totalSteps  : number
 *   - ballots     : [{id, title, quota, candidates: [{id, name}]}]
 */
(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* Durum                                                                 */
    /* ------------------------------------------------------------------ */

    const BALLOTS     = window.OYLAMA?.ballots    || [];
    const TOTAL_STEPS = window.OYLAMA?.totalSteps || 0;
    const EXPIRES_AT  = window.OYLAMA?.expiresAt  || null;

    let currentStep = 0;  // 0 … TOTAL_STEPS-1 → ballot panels; TOTAL_STEPS → summary

    /* ------------------------------------------------------------------ */
    /* Yardımcı: belirli bir ballot panelindeki seçili checkbox'ları döndür */
    /* ------------------------------------------------------------------ */

    function getCheckedInPanel(stepIndex) {
        const panel = document.getElementById('ballot-panel-' + stepIndex);
        if (!panel) return [];
        return Array.from(panel.querySelectorAll('input[type="checkbox"]:checked'));
    }

    /* ------------------------------------------------------------------ */
    /* Kota yönetimi                                                         */
    /* ------------------------------------------------------------------ */

    function updateQuota(stepIndex) {
        const ballot = BALLOTS[stepIndex];
        if (!ballot) return;

        const panel    = document.getElementById('ballot-panel-' + stepIndex);
        const checked  = getCheckedInPanel(stepIndex);
        const count    = checked.length;
        const quota    = ballot.quota;
        const countEl  = document.getElementById('count-' + stepIndex);
        const barEl    = document.getElementById('bar-' + stepIndex);

        if (countEl) countEl.textContent = count;

        if (barEl) {
            const pct = quota > 0 ? Math.round((count / quota) * 100) : 0;
            barEl.style.width = pct + '%';
            barEl.classList.toggle('full', count >= quota);
        }

        // Kotaya ulaşıldıysa seçilmemiş kartları devre dışı bırak
        const allCards = panel ? panel.querySelectorAll('.candidate-card') : [];
        allCards.forEach(card => {
            const chk = card.querySelector('input[type="checkbox"]');
            if (!chk) return;
            if (count >= quota && !chk.checked) {
                card.classList.add('disabled');
            } else {
                card.classList.remove('disabled');
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* Kart tıklama: seç / seçimi kaldır                                    */
    /* ------------------------------------------------------------------ */

    function handleCardClick(e) {
        const card = e.currentTarget;
        if (card.classList.contains('disabled')) {
            e.preventDefault();
            return;
        }

        const chk         = card.querySelector('input[type="checkbox"]');
        const stepIndex   = parseInt(card.dataset.ballotIndex, 10);
        const ballot      = BALLOTS[stepIndex];
        const quota       = ballot ? ballot.quota : Infinity;

        // Seçilmemişse ve kota doluysa engelle
        const checkedCount = getCheckedInPanel(stepIndex).length;
        if (!chk.checked && checkedCount >= quota) {
            e.preventDefault();
            return;
        }

        // Checkbox durumunu değiştir (label içinde zaten değişiyor, ama biz
        // label onclick ile yönettiğimiz için manuel toggle)
        chk.checked = !chk.checked;
        card.classList.toggle('selected', chk.checked);

        updateQuota(stepIndex);
    }

    /* ------------------------------------------------------------------ */
    /* Adım göstergesi güncelleme                                           */
    /* ------------------------------------------------------------------ */

    function updateStepBar(step) {
        for (let i = 0; i < TOTAL_STEPS; i++) {
            const dot = document.getElementById('step-dot-' + i);
            if (!dot) continue;
            dot.classList.remove('active', 'done');
            if (i < step)       dot.classList.add('done');
            else if (i === step) dot.classList.add('active');
        }
        const summaryDot = document.getElementById('step-dot-summary');
        if (summaryDot) {
            summaryDot.classList.remove('active', 'done');
            if (step === TOTAL_STEPS) summaryDot.classList.add('active');
        }
    }

    /* ------------------------------------------------------------------ */
    /* Navigasyon: panel göster/gizle                                       */
    /* ------------------------------------------------------------------ */

    function showStep(step) {
        // Ballot panellerini gizle
        for (let i = 0; i < TOTAL_STEPS; i++) {
            const panel = document.getElementById('ballot-panel-' + i);
            if (panel) panel.classList.remove('active');
        }
        const summaryPanel = document.getElementById('summary-panel');
        if (summaryPanel) summaryPanel.classList.remove('active');

        if (step < TOTAL_STEPS) {
            const panel = document.getElementById('ballot-panel-' + step);
            if (panel) panel.classList.add('active');
        } else {
            buildSummary();
            if (summaryPanel) summaryPanel.classList.add('active');
        }

        updateStepBar(step);
        updateNavButtons(step);

        // Yukarı kaydır
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ------------------------------------------------------------------ */
    /* Navigasyon butonları                                                  */
    /* ------------------------------------------------------------------ */

    function updateNavButtons(step) {
        const prevBtn = document.getElementById('btn-prev');
        const nextBtn = document.getElementById('btn-next');

        if (prevBtn) prevBtn.disabled = (step === 0);

        if (nextBtn) {
            if (step >= TOTAL_STEPS) {
                // Özet ekranında "Sonraki" gizle
                nextBtn.style.display = 'none';
            } else {
                nextBtn.style.display = '';
                const isLast = (step === TOTAL_STEPS - 1);
                nextBtn.textContent = isLast ? 'Özet ve Onay →' : 'Sonraki Kurul →';
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Dışa açık navigasyon fonksiyonu (show.php'nin onclick'i çağırır)    */
    /* ------------------------------------------------------------------ */

    window.navigateBallot = function (direction) {
        const newStep = currentStep + direction;
        if (newStep < 0 || newStep > TOTAL_STEPS) return;
        currentStep = newStep;
        showStep(currentStep);
    };

    /* ------------------------------------------------------------------ */
    /* Özet oluştur                                                          */
    /* ------------------------------------------------------------------ */

    function buildSummary() {
        const container   = document.getElementById('summary-content');
        const hiddenWrap  = document.getElementById('hidden-inputs');
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
                empty.textContent = 'Bu kurul için seçim yapılmadı (boş oy)';
                block.appendChild(empty);
            } else {
                checked.forEach(chk => {
                    const card     = chk.closest('.candidate-card');
                    const name     = card ? card.dataset.name : chk.value;
                    const row      = document.createElement('div');
                    row.className  = 'summary-candidate-row';
                    row.innerHTML  =
                        '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" ' +
                        'style="flex-shrink:0" xmlns="http://www.w3.org/2000/svg">' +
                        '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" ' +
                        'stroke="#16a34a" stroke-width="2" stroke-linecap="round" ' +
                        'stroke-linejoin="round"/></svg>' +
                        '<span style="font-size:.9rem; font-weight:500">' +
                        escapeHtml(name) + '</span>';
                    block.appendChild(row);

                    // Gizli input
                    const hidden  = document.createElement('input');
                    hidden.type   = 'hidden';
                    hidden.name   = 'ballot_' + ballot.id + '[]';
                    hidden.value  = chk.value;
                    hiddenWrap.appendChild(hidden);
                });
            }

            container.appendChild(block);
        });
    }

    /* ------------------------------------------------------------------ */
    /* XSS güvenli HTML escape                                               */
    /* ------------------------------------------------------------------ */

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g,  '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#039;');
    }

    /* ------------------------------------------------------------------ */
    /* Form gönderme: çift tıklama engeli + spinner                         */
    /* ------------------------------------------------------------------ */

    window.handleSubmit = function (e) {
        const btn     = document.getElementById('submit-btn');
        const spinner = document.getElementById('submit-spinner');
        const label   = document.getElementById('submit-label');

        if (btn && btn.disabled) {
            e.preventDefault();
            return false;
        }

        if (btn)     btn.disabled        = true;
        if (spinner) spinner.style.display = 'block';
        if (label)   label.textContent   = 'Gönderiliyor…';

        return true;
    };

    /* ------------------------------------------------------------------ */
    /* Geri sayım sayacı                                                     */
    /* ------------------------------------------------------------------ */

    function startCountdown() {
        if (!EXPIRES_AT) return;

        const expiry    = new Date(EXPIRES_AT.replace(' ', 'T')).getTime();
        const timerEl   = document.getElementById('countdown');
        if (!timerEl) return;

        function tick() {
            const diff = expiry - Date.now();

            if (diff <= 0) {
                timerEl.textContent = '00:00';
                timerEl.classList.add('warning');
                // Token süresi doldu — geçersiz token sayfasına yönlendir
                window.location.href = '/oy/verify?expired=1';
                return;
            }

            const totalSec = Math.floor(diff / 1000);
            const mins     = Math.floor(totalSec / 60);
            const secs     = totalSec % 60;
            timerEl.textContent = String(mins).padStart(2, '0') + ':' +
                                  String(secs).padStart(2, '0');

            // Son 5 dakikada uyarı rengi
            if (totalSec <= 300) {
                timerEl.classList.add('warning');
            }

            setTimeout(tick, 1000);
        }

        tick();
    }

    /* ------------------------------------------------------------------ */
    /* Başlatma                                                              */
    /* ------------------------------------------------------------------ */

    document.addEventListener('DOMContentLoaded', function () {

        // Aday kartlarına tıklama dinleyicisi ekle
        document.querySelectorAll('.candidate-card').forEach(card => {
            // Varsayılan label davranışını iptal ediyoruz — biz yönetiyoruz
            card.addEventListener('click', function (e) {
                e.preventDefault();
                handleCardClick(e);
            });
        });

        // İlk adım için kota durumunu sıfırla
        for (let i = 0; i < TOTAL_STEPS; i++) {
            updateQuota(i);
        }

        // İlk adımı göster
        showStep(0);

        // Geri sayımı başlat
        startCountdown();
    });

})();
