(function () {
    'use strict';

    let pollInterval = null;

    function startPolling() {
        pollInterval = setInterval(async () => {
            try {
                const res = await fetch('/divan/stats');
                if (!res.ok) return;
                const data = await res.json();
                updateUI(data);
            } catch (e) {
                console.error('Polling hatası:', e);
            }
        }, 5000);
    }

    function updateUI(data) {
        const el = (id) => document.getElementById(id);

        if (el('total-members')) el('total-members').textContent = data.total_members;
        if (el('signed-count'))  el('signed-count').textContent  = data.signed_count;
        if (el('voted-count'))   el('voted-count').textContent   = data.voted_count;
        if (el('participation-pct')) el('participation-pct').textContent = data.participation_pct + '%';

        // Progress bar
        const bar = el('progress-bar');
        if (bar) {
            const pct = data.total_members > 0
                ? Math.round(data.voted_count / data.total_members * 100)
                : 0;
            bar.style.width    = pct + '%';
            bar.textContent    = pct + '%';
            bar.setAttribute('aria-valuenow', pct);
        }

        // Stop polling and reload when election is closed
        if (data.election_status === 'closed' && pollInterval) {
            clearInterval(pollInterval);
            location.reload();
        }
    }

    document.addEventListener('DOMContentLoaded', startPolling);
})();
