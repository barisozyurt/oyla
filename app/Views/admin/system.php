<?php
/**
 * Admin — Sistem Durumu
 *
 * Durum verileri /admin/system JSON endpoint'inden AJAX ile yüklenir.
 */
?>

<!-- Başlık -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 mb-1 fw-bold">
            <i class="bi bi-hdd-network text-success me-2"></i>Sistem Durumu
        </h1>
        <p class="text-muted mb-0">Anlık sistem bileşen durumları</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <button id="btn-refresh" class="btn btn-outline-success btn-sm">
            <i class="bi bi-arrow-clockwise me-1"></i>Yenile
        </button>
        <a href="/admin" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Geri
        </a>
    </div>
</div>

<!-- Yükleniyor -->
<div id="status-loading" class="text-center py-5 text-muted">
    <div class="spinner-border text-primary mb-3" role="status">
        <span class="visually-hidden">Yükleniyor...</span>
    </div>
    <div>Sistem durumu kontrol ediliyor...</div>
</div>

<!-- Hata -->
<div id="status-error" class="alert alert-danger d-none">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Sistem durumu alınamadı. Lütfen sayfayı yenileyin.
</div>

<!-- Durum kartları -->
<div id="status-cards" class="row g-3 d-none">

    <!-- Veritabanı Bağlantısı -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <div id="indicator-db" class="status-indicator rounded-circle" style="width:16px;height:16px;background:#ccc;flex-shrink:0"></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">Veritabanı Bağlantısı</div>
                    <div id="val-db" class="text-muted small">—</div>
                </div>
                <i class="bi bi-database-fill fs-3 text-muted"></i>
            </div>
        </div>
    </div>

    <!-- PHP Sürümü -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <div id="indicator-php" class="status-indicator rounded-circle" style="width:16px;height:16px;background:#ccc;flex-shrink:0"></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">PHP Sürümü</div>
                    <div id="val-php" class="text-muted small font-monospace">—</div>
                </div>
                <i class="bi bi-code-slash fs-3 text-muted"></i>
            </div>
        </div>
    </div>

    <!-- SMS Modu -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <div id="indicator-sms" class="status-indicator rounded-circle" style="width:16px;height:16px;background:#ccc;flex-shrink:0"></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">SMS Modu</div>
                    <div id="val-sms" class="text-muted small">—</div>
                </div>
                <i class="bi bi-chat-text-fill fs-3 text-muted"></i>
            </div>
        </div>
    </div>

    <!-- Disk Alanı -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <div id="indicator-disk" class="status-indicator rounded-circle" style="width:16px;height:16px;background:#1D9E75;flex-shrink:0"></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">Boş Disk Alanı</div>
                    <div id="val-disk" class="text-muted small">—</div>
                </div>
                <i class="bi bi-hdd-fill fs-3 text-muted"></i>
            </div>
        </div>
    </div>

    <!-- Seçim Durumu -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <div id="indicator-election" class="status-indicator rounded-circle" style="width:16px;height:16px;background:#ccc;flex-shrink:0"></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">Seçim Durumu</div>
                    <div id="val-election" class="text-muted small">—</div>
                </div>
                <i class="bi bi-calendar-event-fill fs-3 text-muted"></i>
            </div>
        </div>
    </div>

</div>

<!-- Son güncelleme -->
<p id="last-updated" class="text-muted small text-end mt-3 d-none"></p>

<script>
(function () {
    'use strict';

    const loading   = document.getElementById('status-loading');
    const errorBox  = document.getElementById('status-error');
    const cards     = document.getElementById('status-cards');
    const lastUpd   = document.getElementById('last-updated');
    const btnRefresh = document.getElementById('btn-refresh');

    const statusColors = {
        ok:      '#198754',
        warning: '#ffc107',
        error:   '#dc3545',
        info:    '#1D9E75',
    };

    function setIndicator(id, color) {
        const el = document.getElementById('indicator-' + id);
        if (el) el.style.background = color;
    }

    function setText(id, text) {
        const el = document.getElementById('val-' + id);
        if (el) el.textContent = text;
    }

    async function loadStatus() {
        loading.classList.remove('d-none');
        errorBox.classList.add('d-none');
        cards.classList.add('d-none');
        lastUpd.classList.add('d-none');

        try {
            const res = await fetch('/admin/system', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            // DB
            setIndicator('db', data.db_connected ? statusColors.ok : statusColors.error);
            setText('db', data.db_connected ? 'Bağlantı başarılı' : 'Bağlantı kurulamadı');

            // PHP
            const phpOk = data.php_version && data.php_version >= '8.2';
            setIndicator('php', phpOk ? statusColors.ok : statusColors.warning);
            setText('php', 'PHP ' + (data.php_version || '?'));

            // SMS
            if (data.sms_mock) {
                setIndicator('sms', statusColors.warning);
                setText('sms', 'Mock Modu — gerçek SMS gönderilmiyor');
            } else {
                setIndicator('sms', statusColors.ok);
                setText('sms', 'Canlı Mod — Netgsm aktif');
            }

            // Disk
            setIndicator('disk', statusColors.info);
            setText('disk', data.disk_free || 'Bilinmiyor');

            // Election
            const elStatusMap = {
                'draft'  : ['Taslak',  statusColors.warning],
                'test'   : ['Test',    statusColors.warning],
                'open'   : ['Açık',    statusColors.ok],
                'closed' : ['Kapalı',  statusColors.error],
                'none'   : ['Seçim yok', '#adb5bd'],
            };
            const [elLabel, elColor] = elStatusMap[data.election_status] || [data.election_status, '#adb5bd'];
            setIndicator('election', elColor);
            const elTitle = data.election_title ? ` — "${data.election_title}"` : '';
            setText('election', elLabel + elTitle);

            loading.classList.add('d-none');
            cards.classList.remove('d-none');
            lastUpd.classList.remove('d-none');
            lastUpd.textContent = 'Son güncelleme: ' + new Date().toLocaleTimeString('tr-TR');

        } catch (err) {
            loading.classList.add('d-none');
            errorBox.classList.remove('d-none');
            console.error('Sistem durumu alınamadı:', err);
        }
    }

    btnRefresh.addEventListener('click', loadStatus);
    loadStatus();
}());
</script>

<style>
.status-indicator {
    transition: background-color .4s ease;
}
</style>
