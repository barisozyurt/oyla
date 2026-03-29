(function () {
    'use strict';

    const isCurtain = document.body.classList.contains('curtain-mode');

    let pollInterval     = null;
    let rotateInterval   = null;
    let currentBallotIdx = 0;
    let electionClosed   = false;

    // ------------------------------------------------------------------ //
    // Helpers
    // ------------------------------------------------------------------ //

    function el(id) {
        return document.getElementById(id);
    }

    function allBallotSections() {
        return Array.from(document.querySelectorAll('.ballot-section'));
    }

    // ------------------------------------------------------------------ //
    // Ballot switching
    // ------------------------------------------------------------------ //

    /**
     * Show the ballot section at the given index, hide others.
     * Also updates tab pills (normal mode) and dot indicators (curtain mode).
     */
    function showBallot(index) {
        const sections = allBallotSections();
        if (!sections.length) return;

        index = ((index % sections.length) + sections.length) % sections.length;
        currentBallotIdx = index;

        sections.forEach((sec, i) => {
            sec.style.display = i === index ? '' : 'none';
        });

        // Update tab pills (normal mode)
        document.querySelectorAll('.ballot-tab-btn').forEach((btn) => {
            const idx = parseInt(btn.dataset.ballotIndex, 10);
            btn.classList.toggle('active', idx === index);
        });

        // Update dot indicators (curtain mode)
        document.querySelectorAll('.curtain-dot').forEach((dot) => {
            const idx = parseInt(dot.dataset.index, 10);
            dot.style.background = idx === index ? '#fff' : 'rgba(255,255,255,0.3)';
        });
    }

    // ------------------------------------------------------------------ //
    // Result data rendering
    // ------------------------------------------------------------------ //

    /**
     * Rebuild the bar chart rows inside a section element from fresh candidate data.
     */
    function renderChart(sectionEl, candidates, quota, yedekQuota, closed) {
        const chartEl = sectionEl.querySelector('[id^="result-chart-"], [id^="curtain-chart-"]');
        if (!chartEl) return;

        // Determine max votes for scaling
        let maxVotes = 0;
        candidates.forEach((c) => {
            if (parseInt(c.vote_count, 10) > maxVotes) {
                maxVotes = parseInt(c.vote_count, 10);
            }
        });

        // Update each bar in place (avoid full re-render to preserve layout stability)
        const rows = chartEl.querySelectorAll('[data-vote-count]');
        rows.forEach((countEl, rank) => {
            const candidate = candidates[rank];
            if (!candidate) return;

            const voteCount = parseInt(candidate.vote_count, 10);
            const barWidth  = maxVotes > 0 ? Math.round(voteCount / maxVotes * 100) : 0;
            const isWinner  = (rank + 1) <= quota;
            const isYedek   = !isWinner && (rank + 1) <= (quota + yedekQuota);

            // Update count text
            countEl.textContent = voteCount;
            countEl.dataset.voteCount = voteCount;

            // Update bar width
            const barEl = countEl.closest('.d-flex')?.closest('[class*="col-"], .result-row')?.querySelector('.result-bar')
                       ?? countEl.closest('[class*="col-"], .result-row')?.querySelector('.result-bar');
            if (barEl) {
                barEl.style.width = barWidth + '%';
                barEl.dataset.maxVotes = maxVotes;
            }
        });
    }

    // ------------------------------------------------------------------ //
    // Participation update
    // ------------------------------------------------------------------ //

    function updateParticipation(p) {
        const total = p.total;
        const voted = p.voted;
        const pct   = p.percentage;

        if (isCurtain) {
            const vEl  = el('curtain-voted');
            const tEl  = el('curtain-total');
            const pEl  = el('curtain-pct');
            const bar  = el('curtain-participation-bar');
            if (vEl) vEl.textContent = voted;
            if (tEl) tEl.textContent = total;
            if (pEl) pEl.textContent = pct;
            if (bar) bar.style.width = pct + '%';
        } else {
            const vEl  = el('voted-count');
            const tEl  = el('total-count');
            const pEl  = el('participation-pct');
            const bar  = el('participation-bar');
            if (vEl) vEl.textContent = voted;
            if (tEl) tEl.textContent = total;
            if (pEl) pEl.textContent = pct;
            if (bar) {
                bar.style.width = pct + '%';
                bar.setAttribute('aria-valuenow', pct);
            }
        }
    }

    // ------------------------------------------------------------------ //
    // Official results banner
    // ------------------------------------------------------------------ //

    function showOfficialBanner() {
        if (electionClosed) return;
        electionClosed = true;

        // Normal mode: show banner, remove refresh indicator
        const banner    = el('official-banner');
        const indicator = el('refresh-indicator');

        if (!banner) {
            // Inject banner dynamically if page was loaded while open
            const header = document.querySelector('h1');
            if (header) {
                const div = document.createElement('div');
                div.id = 'official-banner';
                div.className = 'alert alert-success border-2 border-success d-flex align-items-center gap-3 mb-4 shadow-sm';
                div.style.borderWidth = '3px';
                div.setAttribute('role', 'alert');
                div.innerHTML = '<i class="bi bi-patch-check-fill fs-2 text-success"></i>'
                    + '<div><div class="fw-bold fs-4">RESMİ SONUÇLAR</div>'
                    + '<div class="text-muted small">Seçim tamamlanmıştır. Aşağıdaki sonuçlar kesinleşmiştir.</div></div>';
                header.closest('.container, main')?.prepend(div)
                    ?? document.body.prepend(div);
            }
        }

        if (indicator) indicator.remove();

        // Stop animated progress bar
        const bar = el('participation-bar');
        if (bar) {
            bar.classList.remove('progress-bar-animated');
        }

        // Stop auto-rotation in curtain mode; curtain handles its own closed display
        if (isCurtain && rotateInterval) {
            // Keep rotating so audience can see all boards
        }

        // Show official label in curtain header if needed
        const curtainHeader = document.querySelector('header');
        if (isCurtain && curtainHeader && !curtainHeader.querySelector('.official-curtain-label')) {
            const lbl = document.createElement('div');
            lbl.className = 'official-curtain-label d-inline-block mt-2 px-4 py-1 border rounded-pill';
            lbl.style.cssText = 'border-color:#ffd700!important; color:#ffd700; font-size:1.1rem; font-weight:700; letter-spacing:.05em;';
            lbl.textContent = 'RESMİ SONUÇLAR';
            curtainHeader.appendChild(lbl);
        }
    }

    // ------------------------------------------------------------------ //
    // Main poll handler
    // ------------------------------------------------------------------ //

    function handlePollData(data) {
        const status = data.election?.status;

        // Update participation
        if (data.participation) {
            updateParticipation(data.participation);
        }

        // Update charts
        if (Array.isArray(data.results)) {
            const sections = allBallotSections();
            data.results.forEach((r, i) => {
                const sec = sections[i];
                if (!sec) return;

                const quota      = parseInt(sec.dataset.quota || '0', 10);
                const yedekQuota = parseInt(sec.dataset.yedekQuota || '0', 10);
                const closed     = status === 'closed';

                renderChart(sec, r.candidates, quota, yedekQuota, closed);

                // Update total votes label
                const tvEl = el(`total-votes-${i}`) || el(`curtain-total-votes-${i}`);
                if (tvEl && r.total_votes !== undefined) {
                    tvEl.querySelector('strong').textContent = r.total_votes;
                }
            });
        }

        // Handle election closure
        if (status === 'closed') {
            showOfficialBanner();
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }
    }

    // ------------------------------------------------------------------ //
    // Polling
    // ------------------------------------------------------------------ //

    async function doPoll() {
        try {
            const res = await fetch('/sonuc/data', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            handlePollData(data);
        } catch (e) {
            console.error('Sonuç polling hatası:', e);
        }
    }

    function startPolling() {
        // Immediate first poll
        doPoll();
        pollInterval = setInterval(doPoll, 5000);
    }

    // ------------------------------------------------------------------ //
    // Curtain mode: auto-rotation
    // ------------------------------------------------------------------ //

    function startRotation() {
        rotateInterval = setInterval(() => {
            const sections = allBallotSections();
            if (sections.length > 1) {
                showBallot(currentBallotIdx + 1);
            }
        }, 15000);
    }

    // ------------------------------------------------------------------ //
    // Event bindings
    // ------------------------------------------------------------------ //

    function bindTabButtons() {
        document.querySelectorAll('.ballot-tab-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.ballotIndex, 10);
                showBallot(idx);
                // Pause auto-rotation briefly on manual interaction
                if (rotateInterval) {
                    clearInterval(rotateInterval);
                    rotateInterval = setTimeout(() => {
                        rotateInterval = setInterval(() => {
                            const secs = allBallotSections();
                            if (secs.length > 1) showBallot(currentBallotIdx + 1);
                        }, 15000);
                    }, 30000); // restart after 30 s idle
                }
            });
        });
    }

    function bindCurtainDots() {
        document.querySelectorAll('.curtain-dot').forEach((dot) => {
            dot.addEventListener('click', () => {
                showBallot(parseInt(dot.dataset.index, 10));
            });
        });
    }

    // ------------------------------------------------------------------ //
    // Init
    // ------------------------------------------------------------------ //

    document.addEventListener('DOMContentLoaded', () => {
        bindTabButtons();
        bindCurtainDots();
        startPolling();
        if (isCurtain && allBallotSections().length > 1) {
            startRotation();
        }
    });

})();
