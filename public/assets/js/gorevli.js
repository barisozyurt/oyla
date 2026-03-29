/**
 * gorevli.js — Görevli Masası State Machine
 *
 * States: IDLE → VERIFY → SIGN1 → TOKEN → VOTE_WAIT → SIGN2 → DONE
 *
 * KURAL: Görevli hiçbir zaman oy içeriğini göremez.
 * Yalnızca üye durumu (waiting/signed/done) ve token used durumu sorgulanır.
 */
(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* State                                                                */
    /* ------------------------------------------------------------------ */

    const Gorevli = {
        state: 'IDLE',
        memberId: null,
        csrf: null,
        voteCheckInterval: null,
        memberListInterval: null,
        activeStatusFilter: '',
        nameFilter: '',

        /* ---------------------------------------------------------------- */
        /* Init                                                              */
        /* ---------------------------------------------------------------- */

        init() {
            this.csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            this.bindEvents();
            this.startMemberListPolling();
        },

        /* ---------------------------------------------------------------- */
        /* Event Bindings                                                    */
        /* ---------------------------------------------------------------- */

        bindEvents() {
            // Search
            const searchBtn   = document.getElementById('search-btn');
            const searchInput = document.getElementById('search-input');
            const resetBtn    = document.getElementById('reset-btn');

            if (searchBtn) {
                searchBtn.addEventListener('click', () => {
                    const q = searchInput?.value?.trim();
                    if (q) this.search(q);
                });
            }

            if (searchInput) {
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        const q = searchInput.value.trim();
                        if (q) this.search(q);
                    }
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', () => this.reset());
            }

            // Tab filters (sidebar)
            const tabs = document.querySelectorAll('#member-tabs button[data-filter]');
            tabs.forEach((btn) => {
                btn.addEventListener('click', () => {
                    tabs.forEach((t) => t.classList.remove('active'));
                    btn.classList.add('active');
                    this.activeStatusFilter = btn.dataset.filter;
                    this.applyListFilter();
                });
            });

            // Inline name filter
            const listFilterInput = document.getElementById('list-filter-input');
            if (listFilterInput) {
                listFilterInput.addEventListener('input', () => {
                    this.nameFilter = listFilterInput.value.toLowerCase().trim();
                    this.applyListFilter();
                });
            }
        },

        /* ---------------------------------------------------------------- */
        /* Search                                                            */
        /* ---------------------------------------------------------------- */

        async search(query) {
            this.showSearchError('');
            this.setSearchLoading(true);

            try {
                const res = await this.post('/gorevli/search', { query });
                const data = await res.json();

                if (!res.ok || data.error) {
                    this.showSearchError(data.error || 'Arama sırasında hata oluştu.');
                    return;
                }

                if (!data.found) {
                    this.showSearchError('Üye bulunamadı. TC kimlik veya sicil numarasını kontrol edin.');
                    return;
                }

                this.memberId = data.member.id;
                this.showMemberCard(data.member);
                this.showWizard();
                this.determineStep(data.member);
                this.showResetBtn(true);
            } catch (err) {
                this.showSearchError('Bağlantı hatası. Lütfen tekrar deneyin.');
                console.error('Search error:', err);
            } finally {
                this.setSearchLoading(false);
            }
        },

        /* ---------------------------------------------------------------- */
        /* Step Logic                                                        */
        /* ---------------------------------------------------------------- */

        determineStep(member) {
            if (member.status === 'done') {
                this.setState('DONE');
            } else if (member.status === 'signed' && member.has_active_token) {
                this.setState('VOTE_WAIT');
            } else if (member.status === 'signed' && !member.has_active_token) {
                this.setState('TOKEN');
            } else {
                // waiting
                this.setState('VERIFY');
            }
        },

        setState(newState) {
            this.state = newState;
            this.updateStepUI();
            this.updateActionButton();

            if (newState === 'VOTE_WAIT') {
                this.showVoteWaiting(true);
                this.startVotePolling();
            } else {
                this.showVoteWaiting(false);
            }

            if (newState !== 'VOTE_WAIT' && this.voteCheckInterval) {
                clearInterval(this.voteCheckInterval);
                this.voteCheckInterval = null;
            }

            if (newState === 'DONE') {
                this.showDoneArea(true);
            } else {
                this.showDoneArea(false);
            }
        },

        /* ---------------------------------------------------------------- */
        /* Step Actions                                                      */
        /* ---------------------------------------------------------------- */

        async firstSign() {
            if (!this.memberId || this.state !== 'SIGN1') return;

            const btn = document.getElementById('action-btn');
            this.setButtonLoading(btn, true, 'İmza kaydediliyor…');

            try {
                const res  = await this.post('/gorevli/sign1/' + this.memberId, {});
                const data = await res.json();

                if (!res.ok || data.error) {
                    this.showActionError(data.error || '1. imza kaydedilemedi.');
                    return;
                }

                this.setState('TOKEN');
            } catch (err) {
                this.showActionError('Bağlantı hatası.');
                console.error('firstSign error:', err);
            } finally {
                const b = document.getElementById('action-btn');
                if (b) this.setButtonLoading(b, false);
            }
        },

        async generateToken() {
            if (!this.memberId || this.state !== 'TOKEN') return;

            const btn = document.getElementById('action-btn');
            this.setButtonLoading(btn, true, 'Token üretiliyor…');

            try {
                const res  = await this.post('/gorevli/token/' + this.memberId, {});
                const data = await res.json();

                if (!res.ok || data.error) {
                    this.showActionError(data.error || 'Token üretilemedi.');
                    return;
                }

                this.showQrCode(data.qr_data_uri, data.vote_url, data.expires_at);
                this.setState('VOTE_WAIT');
            } catch (err) {
                this.showActionError('Bağlantı hatası.');
                console.error('generateToken error:', err);
            } finally {
                const b = document.getElementById('action-btn');
                if (b) this.setButtonLoading(b, false);
            }
        },

        async secondSign() {
            if (!this.memberId || this.state !== 'SIGN2') return;

            const btn = document.getElementById('action-btn');
            this.setButtonLoading(btn, true, '2. imza kaydediliyor…');

            try {
                const res  = await this.post('/gorevli/sign2/' + this.memberId, {});
                const data = await res.json();

                if (!res.ok || data.error) {
                    this.showActionError(data.error || '2. imza kaydedilemedi.');
                    return;
                }

                this.setState('DONE');
                // 3 saniye sonra sıfırla
                setTimeout(() => this.reset(), 3000);
            } catch (err) {
                this.showActionError('Bağlantı hatası.');
                console.error('secondSign error:', err);
            } finally {
                const b = document.getElementById('action-btn');
                if (b) this.setButtonLoading(b, false);
            }
        },

        /* ---------------------------------------------------------------- */
        /* Vote Polling                                                      */
        /* ---------------------------------------------------------------- */

        startVotePolling() {
            if (this.voteCheckInterval) {
                clearInterval(this.voteCheckInterval);
            }

            const check = async () => {
                if (!this.memberId || this.state !== 'VOTE_WAIT') return;

                try {
                    const res  = await fetch('/gorevli/vote-status/' + this.memberId);
                    if (!res.ok) return;
                    const data = await res.json();

                    if (data.voted) {
                        clearInterval(this.voteCheckInterval);
                        this.voteCheckInterval = null;
                        this.setState('SIGN2');
                    }
                } catch (err) {
                    console.error('Vote poll error:', err);
                }
            };

            check();
            this.voteCheckInterval = setInterval(check, 3000);
        },

        /* ---------------------------------------------------------------- */
        /* Member List Polling (sidebar)                                    */
        /* ---------------------------------------------------------------- */

        startMemberListPolling() {
            const refresh = async () => {
                try {
                    const url = '/gorevli/members';
                    const res  = await fetch(url);
                    if (!res.ok) return;
                    const data = await res.json();
                    this.renderMemberList(data.members || []);
                    this.updateStats(data.members || []);
                } catch (err) {
                    console.error('Member list poll error:', err);
                }
            };

            refresh();
            this.memberListInterval = setInterval(refresh, 5000);
        },

        renderMemberList(members) {
            const container = document.getElementById('member-list');
            if (!container) return;

            if (!members || members.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4 small" id="empty-list-msg">'
                    + '<i class="bi bi-people d-block mb-1 fs-4"></i>Kayıtlı üye bulunmuyor.</div>';
                return;
            }

            const fragment = document.createDocumentFragment();

            members.forEach((m) => {
                const btn = document.createElement('button');
                btn.type = 'button';

                const iconHtml = m.status === 'done'
                    ? '<span class="text-success me-1">●</span>'
                    : m.status === 'signed'
                        ? '<span class="text-warning me-1">◑</span>'
                        : '<span class="text-secondary me-1">○</span>';

                const textClass = m.status === 'done' ? ' text-muted' : '';

                btn.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3 member-list-item' + textClass;
                btn.dataset.memberId = m.id;
                btn.dataset.name     = (m.name || '').toLowerCase();
                btn.dataset.status   = m.status;

                const sicilSpan = m.sicil_no
                    ? `<span class="text-muted" style="font-size:.68rem;">${this.escHtml(m.sicil_no)}</span>`
                    : '';

                btn.innerHTML = iconHtml
                    + `<span class="small flex-grow-1 text-truncate">${this.escHtml(m.name)}</span>`
                    + sicilSpan;

                // Click a list item to quickly search by member id
                btn.addEventListener('click', () => {
                    const nameInput = document.getElementById('search-input');
                    if (nameInput) {
                        nameInput.value = m.sicil_no || m.name;
                    }
                    this.quickLoadMember(m.id);
                });

                fragment.appendChild(btn);
            });

            container.innerHTML = '';
            container.appendChild(fragment);

            this.applyListFilter();
        },

        applyListFilter() {
            const items = document.querySelectorAll('#member-list .member-list-item');
            items.forEach((item) => {
                const statusMatch = !this.activeStatusFilter || item.dataset.status === this.activeStatusFilter;
                const nameMatch   = !this.nameFilter || (item.dataset.name || '').includes(this.nameFilter);
                item.style.display = (statusMatch && nameMatch) ? '' : 'none';
            });
        },

        updateStats(members) {
            const waiting = members.filter(m => m.status === 'waiting').length;
            const signed  = members.filter(m => m.status === 'signed').length;
            const done    = members.filter(m => m.status === 'done').length;
            const total   = members.length;

            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };

            set('stat-waiting', waiting);
            set('stat-signed',  signed);
            set('stat-done',    done);
            set('stat-total',   total);

            const barDone   = document.getElementById('bar-done');
            const barSigned = document.getElementById('bar-signed');
            if (barDone && total > 0) {
                barDone.style.width   = Math.round(done   / total * 100) + '%';
                barSigned.style.width = Math.round(signed / total * 100) + '%';
            }
        },

        /* ---------------------------------------------------------------- */
        /* Quick Load from List                                              */
        /* ---------------------------------------------------------------- */

        async quickLoadMember(memberId) {
            // Re-search via member ID route — use search endpoint with a fresh query
            // We trigger the same flow; since we have no "load by id" endpoint,
            // we use the name-search fallback with the current member's name from the DOM.
            // The proper way: just do a status check and determine state.
            const items = document.querySelectorAll('#member-list .member-list-item');
            let memberName = null;
            items.forEach((item) => {
                if (parseInt(item.dataset.memberId, 10) === memberId) {
                    memberName = item.querySelector('.text-truncate')?.textContent?.trim();
                }
            });

            if (memberName) {
                const input = document.getElementById('search-input');
                if (input) input.value = memberName;
                await this.search(memberName);
            }
        },

        /* ---------------------------------------------------------------- */
        /* UI Updates                                                        */
        /* ---------------------------------------------------------------- */

        updateStepUI() {
            const stepOrder = ['VERIFY', 'SIGN1', 'TOKEN', 'VOTE_WAIT', 'SIGN2', 'DONE'];
            const stepIds   = ['step-verify', 'step-sign1', 'step-token', 'step-vote-wait', 'step-sign2'];
            const stateMap  = {
                VERIFY:    0,
                SIGN1:     1,
                TOKEN:     2,
                VOTE_WAIT: 3,
                SIGN2:     4,
                DONE:      4,
            };

            const currentIdx = stateMap[this.state] ?? 0;

            stepIds.forEach((id, idx) => {
                const el    = document.getElementById(id);
                if (!el) return;
                const circle = el.querySelector('.step-circle');
                const icon   = el.querySelector('i');
                const label  = el.querySelector('.step-label');

                if (idx < currentIdx) {
                    // Completed
                    circle.classList.remove('border-secondary', 'border-primary', 'bg-white');
                    circle.classList.add('border-success', 'bg-success');
                    if (icon) { icon.className = 'bi bi-check-lg text-white'; }
                    if (label) label.classList.replace('text-muted', 'text-success');
                } else if (idx === currentIdx) {
                    // Active
                    circle.classList.remove('border-secondary', 'bg-white', 'border-success', 'bg-success');
                    circle.classList.add('border-primary', 'bg-primary');
                    if (icon) {
                        icon.classList.remove('text-secondary', 'text-white', 'text-success');
                        icon.classList.add('text-white');
                    }
                    if (label) {
                        label.classList.remove('text-muted', 'text-success');
                        label.classList.add('text-primary', 'fw-semibold');
                    }
                } else {
                    // Pending
                    circle.classList.remove('border-primary', 'bg-primary', 'border-success', 'bg-success');
                    circle.classList.add('border-secondary', 'bg-white');
                    if (icon) {
                        icon.classList.remove('text-white', 'text-primary', 'text-success');
                        icon.classList.add('text-secondary');
                    }
                    if (label) {
                        label.classList.remove('text-primary', 'fw-semibold', 'text-success');
                        label.classList.add('text-muted');
                    }
                }
            });
        },

        updateActionButton() {
            const area = document.getElementById('action-area');
            if (!area) return;

            // Clear previous error
            this.showActionError('');

            const configs = {
                VERIFY: {
                    html: `<p class="text-muted mb-3">Üyenin kimliğini fiziksel olarak doğrulayın ve ardından 1. imzayı kaydedin.</p>
                           <button id="action-btn" type="button" class="btn btn-warning btn-lg fw-bold px-5 py-3"
                                   onclick="Gorevli.goToSign1()">
                               <i class="bi bi-person-check-fill me-2"></i>Kimlik Doğrulandı, 1. İmzaya Geç
                           </button>`,
                },
                SIGN1: {
                    html: `<p class="text-muted mb-3">Üye kimliği doğrulandı. 1. imzayı kaydedin.</p>
                           <button id="action-btn" type="button" class="btn btn-warning btn-lg fw-bold px-5 py-3"
                                   onclick="Gorevli.firstSign()">
                               <i class="bi bi-pen-fill me-2"></i>1. İmzayı Kaydet
                           </button>`,
                },
                TOKEN: {
                    html: `<p class="text-muted mb-3">1. imza alındı. Üye için oy bağlantısı (token) üretin.</p>
                           <button id="action-btn" type="button" class="btn btn-primary btn-lg fw-bold px-5 py-3"
                                   onclick="Gorevli.generateToken()">
                               <i class="bi bi-qr-code-scan me-2"></i>Token Üret &amp; SMS Gönder
                           </button>`,
                },
                VOTE_WAIT: {
                    html: `<p class="text-success fw-semibold mb-2">
                               <i class="bi bi-check-circle me-1"></i>Token üretildi, SMS gönderildi.
                           </p>
                           <p class="text-muted small mb-0">Üye masadan ayrılmadan kendi telefonunda oy kullanmalıdır.</p>`,
                },
                SIGN2: {
                    html: `<p class="text-muted mb-3">Üye oyunu kullandı. 2. imzayı kaydedip işlemi tamamlayın.</p>
                           <button id="action-btn" type="button" class="btn btn-success btn-lg fw-bold px-5 py-3"
                                   onclick="Gorevli.secondSign()">
                               <i class="bi bi-pen-fill me-2"></i>2. İmzayı Kaydet &amp; Tamamla
                           </button>`,
                },
                DONE: {
                    html: `<p class="text-success fw-semibold mb-0">
                               <i class="bi bi-check2-all me-1"></i>İşlem tamamlandı. Ekran sıfırlanıyor…
                           </p>`,
                },
            };

            const cfg = configs[this.state];
            area.innerHTML = cfg ? cfg.html : '';
        },

        /* ---------------------------------------------------------------- */
        /* Transition: VERIFY → SIGN1                                       */
        /* ---------------------------------------------------------------- */

        goToSign1() {
            if (this.state !== 'VERIFY') return;
            this.setState('SIGN1');
        },

        /* ---------------------------------------------------------------- */
        /* Show / Hide helpers                                               */
        /* ---------------------------------------------------------------- */

        showMemberCard(member) {
            const card = document.getElementById('member-card');
            if (!card) return;

            card.style.display = '';

            const nameEl   = document.getElementById('member-name');
            const tcEl     = document.getElementById('member-tc');
            const sicilEl  = document.getElementById('member-sicil');
            const phoneEl  = document.getElementById('member-phone');
            const badgeEl  = document.getElementById('member-status-badge');
            const avatarEl = document.getElementById('member-avatar');

            if (nameEl)  nameEl.textContent  = member.name || '—';

            if (tcEl) {
                tcEl.innerHTML = member.tc_kimlik
                    ? `<i class="bi bi-person-vcard me-1"></i>TC: <strong>${this.escHtml(member.tc_kimlik)}</strong>`
                    : '';
            }

            if (sicilEl) {
                sicilEl.innerHTML = member.sicil_no
                    ? `<i class="bi bi-hash me-1"></i>Sicil: <strong>${this.escHtml(member.sicil_no)}</strong>`
                    : '';
            }

            if (phoneEl) {
                phoneEl.innerHTML = member.phone
                    ? `<i class="bi bi-phone me-1"></i>${this.escHtml(member.phone)}`
                    : '';
            }

            if (badgeEl) {
                const statusLabels = {
                    waiting: ['Bekliyor', 'bg-secondary'],
                    signed:  ['İmza Atıldı', 'bg-warning text-dark'],
                    done:    ['Tamamlandı', 'bg-success'],
                };
                const [label, cls] = statusLabels[member.status] || ['—', 'bg-secondary'];
                badgeEl.className  = 'badge fs-6 px-3 py-2 ' + cls;
                badgeEl.textContent = label;
            }

            // Avatar
            if (avatarEl) {
                if (member.photo_path) {
                    avatarEl.innerHTML = `<img src="${this.escHtml(member.photo_path)}"
                        alt="" class="rounded-circle" width="64" height="64"
                        style="object-fit:cover;"
                        onerror="this.outerHTML='${this.defaultAvatarSvg()}'">`;
                } else {
                    avatarEl.innerHTML = this.defaultAvatarSvg(64);
                }
            }
        },

        defaultAvatarSvg(size = 64) {
            return `<svg class="rounded-circle" viewBox="0 0 64 64" width="${size}" height="${size}">
                <rect width="64" height="64" rx="32" fill="#E9ECEF"/>
                <circle cx="32" cy="24" r="11" fill="#B4B2A9"/>
                <path d="M8 60c0-13 10.7-20 24-20s24 7 24 20" fill="#B4B2A9"/>
            </svg>`;
        },

        showWizard() {
            const card = document.getElementById('wizard-card');
            if (card) card.style.display = '';
        },

        hideWizard() {
            const card = document.getElementById('wizard-card');
            if (card) card.style.display = 'none';
        },

        showQrCode(dataUri, url, expiresAt) {
            const area = document.getElementById('qr-area');
            const img  = document.getElementById('qr-image');
            const link = document.getElementById('vote-url-link');
            const exp  = document.getElementById('token-expires');

            if (!area) return;
            area.style.display = '';

            if (img)  img.src = dataUri;
            if (link) {
                link.href        = url;
                link.textContent = url;
            }
            if (exp && expiresAt) {
                // Format datetime to Turkish locale
                try {
                    const d = new Date(expiresAt.replace(' ', 'T'));
                    exp.textContent = d.toLocaleString('tr-TR');
                } catch {
                    exp.textContent = expiresAt;
                }
            }
        },

        hideQrCode() {
            const area = document.getElementById('qr-area');
            if (area) area.style.display = 'none';
        },

        showVoteWaiting(show) {
            const area = document.getElementById('vote-waiting-area');
            if (area) area.style.display = show ? '' : 'none';
        },

        showDoneArea(show) {
            const area = document.getElementById('done-area');
            if (area) area.style.display = show ? '' : 'none';
        },

        showResetBtn(show) {
            const btn = document.getElementById('reset-btn');
            if (btn) btn.style.display = show ? '' : 'none';
        },

        showSearchError(msg) {
            const el = document.getElementById('search-error');
            if (!el) return;
            el.textContent  = msg;
            el.style.display = msg ? '' : 'none';
        },

        showActionError(msg) {
            let el = document.getElementById('action-error');
            if (!el) {
                el = document.createElement('div');
                el.id = 'action-error';
                el.className = 'text-danger small mt-2';
                const area = document.getElementById('action-area');
                if (area) area.after(el);
            }
            el.textContent  = msg;
            el.style.display = msg ? '' : 'none';
        },

        setSearchLoading(loading) {
            const btn   = document.getElementById('search-btn');
            const input = document.getElementById('search-input');
            if (btn) {
                btn.disabled = loading;
                btn.innerHTML = loading
                    ? '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Aranıyor…'
                    : '<i class="bi bi-search me-1"></i>Ara';
            }
            if (input) input.disabled = loading;
        },

        setButtonLoading(btn, loading, loadingText = 'İşleniyor…') {
            if (!btn) return;
            btn.disabled = loading;
            if (loading) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
            } else {
                btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
            }
        },

        /* ---------------------------------------------------------------- */
        /* Reset                                                             */
        /* ---------------------------------------------------------------- */

        reset() {
            // Stop any active polling
            if (this.voteCheckInterval) {
                clearInterval(this.voteCheckInterval);
                this.voteCheckInterval = null;
            }

            this.state    = 'IDLE';
            this.memberId = null;

            // UI cleanup
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.value    = '';
                searchInput.disabled = false;
                searchInput.focus();
            }

            const memberCard = document.getElementById('member-card');
            if (memberCard) memberCard.style.display = 'none';

            this.hideWizard();
            this.hideQrCode();
            this.showVoteWaiting(false);
            this.showDoneArea(false);
            this.showSearchError('');
            this.showActionError('');
            this.showResetBtn(false);

            const actionArea = document.getElementById('action-area');
            if (actionArea) actionArea.innerHTML = '';

            // Reset step circles to default
            const stepIds = ['step-verify', 'step-sign1', 'step-token', 'step-vote-wait', 'step-sign2'];
            const defaultIcons = [
                'bi-person-check',
                'bi-pen',
                'bi-qr-code',
                'bi-hourglass-split',
                'bi-pen-fill',
            ];
            stepIds.forEach((id, idx) => {
                const el     = document.getElementById(id);
                if (!el) return;
                const circle = el.querySelector('.step-circle');
                const icon   = el.querySelector('i');
                const label  = el.querySelector('.step-label');

                if (circle) {
                    circle.className = 'step-circle mx-auto mb-1 d-flex align-items-center justify-content-center rounded-circle border border-2 border-secondary bg-white';
                    circle.style.cssText = 'width:38px;height:38px;font-size:1rem;transition:all .25s;';
                }
                if (icon)  icon.className  = `bi ${defaultIcons[idx]} text-secondary`;
                if (label) {
                    label.classList.remove('text-primary', 'fw-semibold', 'text-success');
                    label.classList.add('text-muted');
                }
            });
        },

        /* ---------------------------------------------------------------- */
        /* HTTP helpers                                                      */
        /* ---------------------------------------------------------------- */

        async post(url, extra = {}) {
            const params = new URLSearchParams({ _csrf: this.csrf, ...extra });
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString(),
            });
        },

        /* ---------------------------------------------------------------- */
        /* Utility                                                           */
        /* ---------------------------------------------------------------- */

        escHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },
    };

    // Expose globally so inline onclick handlers work
    window.Gorevli = Gorevli;

    document.addEventListener('DOMContentLoaded', () => Gorevli.init());

})();
