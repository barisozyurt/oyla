/**
 * Oyla — Shared JavaScript Utilities
 *
 * Loaded on every page. Exposes the global `window.Oyla` namespace.
 * No dependencies — pure ES2017+, no jQuery, no build step.
 */
(function () {
    'use strict';

    // ============================================================
    // CSRF Token Helper
    // ============================================================
    /**
     * Reads the CSRF token from a <meta name="csrf-token"> tag or a
     * hidden input named "_csrf", whichever is present first.
     *
     * @returns {string} CSRF token or empty string if not found.
     */
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.getAttribute('content') || '';

        const input = document.querySelector('input[name="_csrf"]');
        if (input) return input.value;

        return '';
    }

    // ============================================================
    // Fetch with CSRF
    // ============================================================
    /**
     * Thin fetch wrapper that:
     *  - Sets the correct Content-Type for form-encoded bodies
     *  - Automatically appends the CSRF token to POST bodies
     *  - Throws on non-2xx responses
     *  - Returns parsed JSON
     *
     * @param {string} url
     * @param {RequestInit} [options={}]
     * @returns {Promise<any>}
     */
    async function fetchJson(url, options = {}) {
        const isPost = options.method === 'POST' || options.body !== undefined;

        const defaultHeaders = {};
        if (isPost && typeof options.body === 'string') {
            defaultHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        // Inject CSRF into URL-encoded bodies that do not already carry it
        if (isPost && typeof options.body === 'string' && !options.body.includes('_csrf')) {
            const token = getCsrfToken();
            if (token) {
                options.body += (options.body.length ? '&' : '') +
                    '_csrf=' + encodeURIComponent(token);
            }
        }

        const mergedOptions = {
            ...options,
            headers: {
                ...defaultHeaders,
                ...(options.headers || {}),
            },
        };

        const res = await fetch(url, mergedOptions);

        if (!res.ok) {
            const msg = `HTTP ${res.status} ${res.statusText} — ${url}`;
            throw new Error(msg);
        }

        return res.json();
    }

    // ============================================================
    // Generic Poller
    // ============================================================
    /**
     * Fetches `url` via fetchJson immediately and then every `intervalMs`
     * milliseconds, calling `callback(data)` on each successful response.
     * Errors are logged but do not stop polling.
     *
     * @param {string}   url
     * @param {number}   intervalMs   Polling interval in milliseconds.
     * @param {Function} callback     Called with the parsed JSON response.
     * @returns {number} Interval ID — pass to clearInterval() to stop.
     */
    function startPolling(url, intervalMs, callback) {
        const poll = async () => {
            try {
                const data = await fetchJson(url);
                callback(data);
            } catch (e) {
                console.error('[Oyla] Polling error:', e.message);
            }
        };

        poll(); // immediate first call — no waiting for first tick
        return setInterval(poll, intervalMs);
    }

    // ============================================================
    // Toast Notifications
    // ============================================================
    /**
     * Shows a Bootstrap 5 toast in the top-right corner.
     * Creates the container on first call.
     *
     * @param {string} message   HTML allowed (will be inserted as innerHTML).
     * @param {'success'|'danger'|'warning'|'info'|'primary'|'secondary'|'dark'} [type='info']
     * @param {number} [durationMs=4000]  Milliseconds before the toast fades out.
     */
    function showToast(message, type = 'info', durationMs = 4000) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1090';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 show`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button"
                        class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"
                        aria-label="Kapat"></button>
            </div>`;

        container.appendChild(toast);

        // Wire up Bootstrap dismiss button
        const closeBtn = toast.querySelector('[data-bs-dismiss="toast"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => removeToast(toast));
        }

        // Auto-dismiss
        const timer = setTimeout(() => removeToast(toast), durationMs);

        // Cancel timer if manually closed
        toast.addEventListener('click', (e) => {
            if (e.target === closeBtn) clearTimeout(timer);
        });
    }

    function removeToast(toast) {
        toast.style.transition = 'opacity 0.3s';
        toast.style.opacity = '0';
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 320);
    }

    // ============================================================
    // Countdown Timer
    // ============================================================
    /**
     * Renders a live HH:MM:SS / MM:SS countdown into `element`.
     * Changes text color to warning (<10 min) and danger (<5 min).
     * Calls `onExpire()` when the countdown reaches zero.
     *
     * @param {number}      targetTimestamp  Unix timestamp in *milliseconds*.
     * @param {HTMLElement} element          DOM node to write the time into.
     * @param {Function}    [onExpire]       Optional callback on expiry.
     * @returns {number} Interval ID — pass to clearInterval() to stop.
     */
    function startCountdown(targetTimestamp, element, onExpire) {
        const update = () => {
            const remaining = targetTimestamp - Date.now();

            if (remaining <= 0) {
                element.textContent = 'Süre doldu';
                element.classList.remove('text-warning');
                element.classList.add('text-danger', 'fw-bold');
                clearInterval(interval);
                if (typeof onExpire === 'function') onExpire();
                return;
            }

            const totalSecs = Math.floor(remaining / 1000);
            const hours = Math.floor(totalSecs / 3600);
            const mins  = Math.floor((totalSecs % 3600) / 60);
            const secs  = totalSecs % 60;

            if (hours > 0) {
                element.textContent =
                    `${hours}:${pad2(mins)}:${pad2(secs)}`;
            } else {
                element.textContent = `${mins}:${pad2(secs)}`;
            }

            // Color warnings
            if (remaining < 300_000) {                  // < 5 min
                element.classList.remove('text-warning');
                element.classList.add('text-danger', 'fw-bold');
            } else if (remaining < 600_000) {            // < 10 min
                element.classList.remove('text-danger', 'fw-bold');
                element.classList.add('text-warning');
            } else {
                element.classList.remove('text-danger', 'text-warning', 'fw-bold');
            }
        };

        update();
        const interval = setInterval(update, 1000);
        return interval;
    }

    /** Zero-pads a number to 2 digits. */
    function pad2(n) {
        return n.toString().padStart(2, '0');
    }

    // ============================================================
    // Confirm Modal Helper
    // ============================================================
    /**
     * Shows the browser's native confirm dialog. If the user accepts,
     * calls `onConfirm()`. Prefer this lightweight helper for simple
     * destructive-action confirmations.
     *
     * For richer UI, use Bootstrap's modal directly in the view.
     *
     * @param {string}   message
     * @param {Function} onConfirm
     */
    function confirmAction(message, onConfirm) {
        if (window.confirm(message)) {
            onConfirm();
        }
    }

    // ============================================================
    // Flash Message Auto-dismiss
    // ============================================================
    /**
     * Wires up `.flash-message` elements so they fade out after 5 seconds.
     * Called automatically on DOMContentLoaded.
     */
    function initFlashMessages() {
        document.querySelectorAll('.flash-message').forEach((el) => {
            const delay = parseInt(el.dataset.autoDismiss || '5000', 10);
            setTimeout(() => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => {
                    if (el.parentNode) el.parentNode.removeChild(el);
                }, 520);
            }, delay);
        });
    }

    // ============================================================
    // Table Search Filter
    // ============================================================
    /**
     * Wires a text input to filter table rows in real time.
     * Matching is case-insensitive, full text of each <tr>.
     *
     * @param {string} inputId  id of the <input> element.
     * @param {string} tableId  id of the <table> element.
     */
    function initTableSearch(inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);
        if (!input || !table) return;

        // Update "X sonuç" label if present
        const countLabel = document.getElementById(inputId + '-count');

        input.addEventListener('input', () => {
            const filter = input.value.trim().toLowerCase();
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            let visible = 0;

            rows.forEach((row) => {
                const match = row.textContent.toLowerCase().includes(filter);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });

            if (countLabel) {
                countLabel.textContent = filter
                    ? `${visible} / ${rows.length} kayıt`
                    : `${rows.length} kayıt`;
            }
        });

        // Clear on Escape
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                input.value = '';
                input.dispatchEvent(new Event('input'));
            }
        });
    }

    // ============================================================
    // Status Filter Tabs
    // ============================================================
    /**
     * Wires `.status-filter-tab` buttons to show/hide table rows
     * matching a `data-status` attribute.
     *
     * Expected markup:
     *   <button class="status-filter-tab active" data-filter="all">Tümü</button>
     *   <button class="status-filter-tab" data-filter="waiting">Bekliyor</button>
     *   ...
     *   <tr data-status="waiting">...</tr>
     *
     * @param {string} containerId  id of the tab container element.
     * @param {string} tableId      id of the <table> element.
     */
    function initStatusFilter(containerId, tableId) {
        const container = document.getElementById(containerId);
        const table = document.getElementById(tableId);
        if (!container || !table) return;

        const tabs = container.querySelectorAll('.status-filter-tab');
        const rows = table.querySelectorAll('tbody tr[data-status]');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                // Update active tab
                tabs.forEach((t) => t.classList.remove('active'));
                tab.classList.add('active');

                const filter = tab.dataset.filter || 'all';

                rows.forEach((row) => {
                    if (filter === 'all' || row.dataset.status === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    }

    // ============================================================
    // Animated Bar Width Setter
    // ============================================================
    /**
     * Sets the width of a `.result-bar` element via JS so the CSS
     * transition plays. The percentage must be between 0 and 100.
     *
     * @param {HTMLElement} barEl   The bar element.
     * @param {number}      pct     Percentage width (0–100).
     * @param {number}      [delay] Optional delay in ms before animating.
     */
    function animateBar(barEl, pct, delay = 0) {
        if (!barEl) return;
        const clamp = Math.max(0, Math.min(100, pct));

        if (delay > 0) {
            setTimeout(() => { barEl.style.width = clamp + '%'; }, delay);
        } else {
            // Double rAF ensures layout has been painted with width:0 first
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    barEl.style.width = clamp + '%';
                });
            });
        }
    }

    // ============================================================
    // HTML Escape
    // ============================================================
    /**
     * Escapes a string for safe insertion into HTML.
     * Uses a temporary DOM element — no regex heuristics.
     *
     * @param {string} str  Raw user input.
     * @returns {string}    HTML-escaped string.
     */
    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    // ============================================================
    // Format helpers
    // ============================================================
    /**
     * Formats a number with thousands separator (Turkish locale).
     * Example: 1234 → "1.234"
     *
     * @param {number} n
     * @returns {string}
     */
    function formatNumber(n) {
        return Number(n).toLocaleString('tr-TR');
    }

    /**
     * Returns a Turkish-formatted date string.
     * Example: "29 Mart 2025, 14:30"
     *
     * @param {string|number|Date} date
     * @returns {string}
     */
    function formatDate(date) {
        return new Date(date).toLocaleString('tr-TR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    // ============================================================
    // Live counter updater
    // ============================================================
    /**
     * Smoothly animates a `.stat-number` counter from its current
     * rendered value to `target` over `durationMs`.
     *
     * @param {HTMLElement} el
     * @param {number}      target
     * @param {number}      [durationMs=600]
     */
    function animateCounter(el, target, durationMs = 600) {
        if (!el) return;
        const start = parseInt(el.textContent.replace(/\D/g, ''), 10) || 0;
        if (start === target) return;

        const startTime = performance.now();
        const step = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / durationMs, 1);
            // Ease-out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(start + (target - start) * eased);
            el.textContent = formatNumber(current);
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    }

    // ============================================================
    // Clipboard copy
    // ============================================================
    /**
     * Copies `text` to the clipboard and optionally shows a toast.
     *
     * @param {string}  text
     * @param {string}  [successMsg='Kopyalandı!']
     */
    async function copyToClipboard(text, successMsg = 'Kopyalandı!') {
        try {
            await navigator.clipboard.writeText(text);
            showToast(escHtml(successMsg), 'success', 2000);
        } catch (e) {
            // Fallback for older browsers / insecure contexts
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;opacity:0;pointer-events:none';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showToast(escHtml(successMsg), 'success', 2000);
        }
    }

    // ============================================================
    // Form serializer (URLSearchParams)
    // ============================================================
    /**
     * Serializes a <form> element to a URL-encoded string, ready to
     * use as a `fetch` body. Adds the CSRF token automatically.
     *
     * @param {HTMLFormElement} form
     * @returns {string}
     */
    function serializeForm(form) {
        const params = new URLSearchParams(new FormData(form));
        const token = getCsrfToken();
        if (token && !params.has('_csrf')) {
            params.set('_csrf', token);
        }
        return params.toString();
    }

    // ============================================================
    // Debounce
    // ============================================================
    /**
     * Returns a debounced version of `fn` that delays invocation by
     * `waitMs` after the last call.
     *
     * @param {Function} fn
     * @param {number}   waitMs
     * @returns {Function}
     */
    function debounce(fn, waitMs) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), waitMs);
        };
    }

    // ============================================================
    // Fullscreen toggle (perde modu)
    // ============================================================
    /**
     * Requests or exits fullscreen on `element` (defaults to
     * document.documentElement). Toggles the `.curtain-mode` class
     * on `<body>` to switch the dark overlay styles.
     *
     * @param {HTMLElement} [el=document.documentElement]
     */
    function toggleFullscreen(el) {
        const target = el || document.documentElement;

        if (!document.fullscreenElement) {
            target.requestFullscreen().then(() => {
                document.body.classList.add('curtain-mode');
            }).catch((err) => {
                console.warn('[Oyla] Fullscreen request failed:', err.message);
            });
        } else {
            document.exitFullscreen().then(() => {
                document.body.classList.remove('curtain-mode');
            });
        }
    }

    // Sync curtain-mode class when user presses Esc to exit fullscreen
    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
            document.body.classList.remove('curtain-mode');
        }
    });

    // ============================================================
    // Bootstrap tooltip auto-init
    // ============================================================
    /**
     * Initialises all Bootstrap tooltips on the page.
     * Requires Bootstrap 5 JS to be loaded before this file.
     * Safe to call multiple times — already-initialised tooltips are skipped.
     */
    function initTooltips() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el);
            }
        });
    }

    // ============================================================
    // Data attribute wiring — [data-confirm]
    // ============================================================
    /**
     * Any element with `data-confirm="message"` that triggers a form
     * submission or navigation will show a confirmation dialog.
     * Delegated to document so it works with dynamically added elements.
     */
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-confirm]');
        if (!btn) return;
        const msg = btn.dataset.confirm || 'Emin misiniz?';
        if (!window.confirm(msg)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // ============================================================
    // Data attribute wiring — [data-copy]
    // ============================================================
    /**
     * Any element with `data-copy="text to copy"` will copy that
     * text to the clipboard on click.
     */
    document.addEventListener('click', (e) => {
        const el = e.target.closest('[data-copy]');
        if (!el) return;
        copyToClipboard(el.dataset.copy);
    });

    // ============================================================
    // Initialize on DOM ready
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        initFlashMessages();
        initTooltips();
    });

    // ============================================================
    // Public API
    // ============================================================
    window.Oyla = {
        // Core
        getCsrfToken,
        fetchJson,
        startPolling,

        // Notifications
        showToast,

        // Time
        startCountdown,
        formatDate,

        // UI helpers
        confirmAction,
        animateBar,
        animateCounter,
        toggleFullscreen,

        // Table/list utilities
        initFlashMessages,
        initTableSearch,
        initStatusFilter,
        initTooltips,

        // Data utilities
        escHtml,
        formatNumber,
        serializeForm,
        copyToClipboard,
        debounce,
    };
})();
