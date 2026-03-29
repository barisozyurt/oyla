(function () {
    'use strict';

    // ----------------------------------------------------------------
    // 1. Sistem durumu otomatik yükleme
    //    /admin/system sayfasında yükleme tetiklenir (sayfa kendi
    //    içinde de çalıştırır, burada ek polling hook'u)
    // ----------------------------------------------------------------
    function initSystemStatus() {
        const cards = document.getElementById('status-cards');
        if (!cards) return; // Sadece sistem durumu sayfasında çalış

        // Polling her 30 saniyede bir
        setInterval(async function () {
            try {
                const res  = await fetch('/admin/system', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();

                updateStatusCard('db',       data.db_connected ? 'ok' : 'error',
                    data.db_connected ? 'Bağlantı başarılı' : 'Bağlantı kurulamadı');

                updateStatusCard('php',      'ok', 'PHP ' + (data.php_version || '?'));

                updateStatusCard('sms',
                    data.sms_mock ? 'warning' : 'ok',
                    data.sms_mock ? 'Mock Modu' : 'Canlı Mod — Netgsm aktif');

                updateStatusCard('disk',     'info', data.disk_free || 'Bilinmiyor');

                const elStatusLabels = {
                    draft: 'Taslak', test: 'Test', open: 'Açık', closed: 'Kapalı', none: 'Seçim yok'
                };
                const elColors = {
                    draft: 'warning', test: 'warning', open: 'ok', closed: 'error', none: 'info'
                };
                updateStatusCard('election',
                    elColors[data.election_status] || 'info',
                    (elStatusLabels[data.election_status] || data.election_status)
                    + (data.election_title ? ` — "${data.election_title}"` : ''));

                const lastUpd = document.getElementById('last-updated');
                if (lastUpd) {
                    lastUpd.classList.remove('d-none');
                    lastUpd.textContent = 'Son güncelleme: ' + new Date().toLocaleTimeString('tr-TR');
                }
            } catch (e) {
                console.warn('Admin sistem durumu polling hatası:', e);
            }
        }, 30000);
    }

    function updateStatusCard(name, state, text) {
        const colorMap = {
            ok:      '#198754',
            warning: '#ffc107',
            error:   '#dc3545',
            info:    '#0d6efd',
        };

        const indicator = document.getElementById('indicator-' + name);
        const valEl     = document.getElementById('val-' + name);

        if (indicator) indicator.style.background = colorMap[state] || '#adb5bd';
        if (valEl)     valEl.textContent = text;
    }

    // ----------------------------------------------------------------
    // 2. Kullanıcı formu — masa numarası alanı görünürlük toggle
    // ----------------------------------------------------------------
    function initRoleToggle() {
        const roleSelect  = document.getElementById('role');
        const deskNoGroup = document.getElementById('desk-no-group');

        if (!roleSelect || !deskNoGroup) return;

        function toggle() {
            deskNoGroup.style.display = roleSelect.value === 'gorevli' ? '' : 'none';
        }

        roleSelect.addEventListener('change', toggle);
        toggle(); // İlk yükleme
    }

    // ----------------------------------------------------------------
    // 3. Silme onayı handler'ları
    //    Sadece data-confirm niteliği olan delete form'larına uygulanır
    // ----------------------------------------------------------------
    function initDeleteConfirm() {
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.matches('form[data-confirm]')) return;
            const msg = form.getAttribute('data-confirm') || 'Bu işlemi gerçekleştirmek istediğinizden emin misiniz?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    }

    // ----------------------------------------------------------------
    // 4. Override modal — mevcut durumu seçili göster
    // ----------------------------------------------------------------
    function initOverrideModal() {
        const modal = document.getElementById('overrideModal');
        if (!modal) return;

        modal.addEventListener('show.bs.modal', function (event) {
            const btn    = event.relatedTarget;
            if (!btn) return;

            const id     = btn.getAttribute('data-election-id') || '';
            const title  = btn.getAttribute('data-election-title') || '';
            const status = btn.getAttribute('data-current-status') || 'draft';

            const idInput  = document.getElementById('override-election-id');
            const titleEl  = document.getElementById('override-election-title');
            const statusEl = document.getElementById('override-status');

            if (idInput)  idInput.value            = id;
            if (titleEl)  titleEl.textContent       = title;
            if (statusEl) statusEl.value            = status;
        });
    }

    // ----------------------------------------------------------------
    // Başlat
    // ----------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', function () {
        initSystemStatus();
        initRoleToggle();
        initDeleteConfirm();
        initOverrideModal();
    });

}());
