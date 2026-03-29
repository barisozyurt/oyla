/**
 * test_mode.js — Admin Test Modu Paneli
 *
 * Üç bağımsız işlem:
 *   1. Sistem kontrolleri  → POST /admin/test/checks
 *   2. Test simülasyonu    → POST /admin/test/simulate
 *   3. Test temizliği      → POST /admin/test/cleanup
 *
 * CSRF token: <meta name="csrf-token"> veya hidden input #simCsrf / #cleanupCsrf
 */
(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* Yardımcı Fonksiyonlar                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Sayfadaki güncel CSRF tokenını döndürür.
     * Her başarılı POST sonrası sunucu yeni bir token header'ı gönderebilir;
     * önce meta tag'ı, yoksa verilen input'u kullanır.
     */
    function getCsrf(inputId) {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        const input = document.getElementById(inputId);
        return input ? input.value : '';
    }

    /**
     * Butonu yükleniyor moduna al / geri al.
     * @param {HTMLButtonElement} btn
     * @param {boolean} loading
     * @param {string} [loadingText]
     */
    function setLoading(btn, loading, loadingText) {
        if (!btn) return;
        if (loading) {
            btn.dataset.originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText || 'İşleniyor…'}`;
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
        }
    }

    /**
     * CSRF tokenini header/meta üzerinde güncelle.
     */
    function updateCsrfInputs(newToken) {
        if (!newToken) return;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.content = newToken;
        ['simCsrf', 'cleanupCsrf'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.value = newToken;
        });
    }

    /**
     * Basit bir JSON POST yardımcısı.
     * @returns {Promise<{ok: boolean, data: any, status: number}>}
     */
    async function postJson(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams(body).toString(),
        });
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (_) {
            data = { error: text };
        }
        return { ok: response.ok, status: response.status, data };
    }

    /* ------------------------------------------------------------------ */
    /* Bölüm 1: Sistem Kontrolleri                                          */
    /* ------------------------------------------------------------------ */

    const btnRunChecks  = document.getElementById('btnRunChecks');
    const checksBody    = document.getElementById('checksBody');
    const checksResult  = document.getElementById('checksResult');
    const checksAllPass = document.getElementById('checksAllPass');
    const checksSomeFail = document.getElementById('checksSomeFail');

    /**
     * Sunucudan gelen durum string'ini Bootstrap badge HTML'ine çevirir.
     */
    function statusBadge(status) {
        const map = {
            pass: { cls: 'success', icon: 'bi-check-circle-fill',    text: 'Geçti'      },
            fail: { cls: 'danger',  icon: 'bi-x-circle-fill',        text: 'Başarısız'  },
            warn: { cls: 'warning', icon: 'bi-exclamation-triangle-fill', text: 'Uyarı' },
        };
        const s = map[status] || { cls: 'secondary', icon: 'bi-dash-circle', text: status };
        return `<span class="badge bg-${s.cls} text-${s.cls === 'warning' ? 'dark' : 'white'}">
                    <i class="bi ${s.icon} me-1"></i>${s.text}
                </span>`;
    }

    /**
     * Kontrol satırını tabloda bul ve güncelle.
     * Satır bulunamazsa yeni satır ekle.
     */
    function updateCheckRow(check, index) {
        const rows = checksBody.querySelectorAll('tr.check-row');
        let row = rows[index] || null;

        if (!row) {
            row = document.createElement('tr');
            row.className = 'check-row';
            checksBody.appendChild(row);
        }

        // Kısa gecikmeyle animasyon efekti
        setTimeout(function () {
            row.innerHTML = `
                <td class="ps-4">
                    <span class="fw-medium">${escHtml(check.name)}</span>
                </td>
                <td>${statusBadge(check.status)}</td>
                <td class="text-muted small">${escHtml(check.detail || '—')}</td>
            `;
            // Satırı vurgula
            row.classList.add('table-' + (check.status === 'pass' ? 'success' : check.status === 'fail' ? 'danger' : 'warning'));
        }, index * 120);
    }

    if (btnRunChecks) {
        btnRunChecks.addEventListener('click', async function () {
            // Satırları sıfırla
            checksBody.querySelectorAll('tr.check-row').forEach(function (row) {
                row.className = 'check-row';
                const cells = row.querySelectorAll('td');
                if (cells[1]) cells[1].innerHTML = '<span class="badge bg-light text-secondary border"><i class="bi bi-hourglass-split me-1"></i>Çalışıyor…</span>';
                if (cells[2]) cells[2].textContent = '—';
            });
            checksResult.classList.add('d-none');
            checksAllPass.classList.add('d-none');
            checksSomeFail.classList.add('d-none');

            setLoading(btnRunChecks, true, 'Kontroller çalışıyor…');

            try {
                const result = await postJson('/admin/test/checks', {
                    _csrf: getCsrf('simCsrf'),
                });

                if (!result.ok || !result.data.checks) {
                    throw new Error(result.data.error || 'Sunucu hatası.');
                }

                result.data.checks.forEach(function (check, i) {
                    updateCheckRow(check, i);
                });

                // Sonuç mesajı (animasyon gecikmesinden sonra göster)
                const delay = result.data.checks.length * 120 + 200;
                setTimeout(function () {
                    checksResult.classList.remove('d-none');
                    if (result.data.all_passed) {
                        checksAllPass.classList.remove('d-none');
                    } else {
                        checksSomeFail.classList.remove('d-none');
                    }
                }, delay);

            } catch (err) {
                checksResult.classList.remove('d-none');
                checksSomeFail.classList.remove('d-none');
                checksSomeFail.querySelector('strong').textContent = 'Hata: ' + err.message;
            } finally {
                setLoading(btnRunChecks, false);
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* Bölüm 2: Simülasyon                                                  */
    /* ------------------------------------------------------------------ */

    const simulationForm  = document.getElementById('simulationForm');
    const btnSimulate     = document.getElementById('btnSimulate');
    const simProgress     = document.getElementById('simProgress');
    const simProgressText = document.getElementById('simProgressText');
    const simResults      = document.getElementById('simResults');
    const simSuccessAlert = document.getElementById('simSuccessAlert');
    const simErrorAlert   = document.getElementById('simErrorAlert');
    const simStatsGrid    = document.getElementById('simStatsGrid');
    const simSummaryList  = document.getElementById('simSummaryList');
    const simErrorMsg     = document.getElementById('simErrorMsg');
    const testReportSection = document.getElementById('testReportSection');
    const testReportContent = document.getElementById('testReportContent');

    if (simulationForm) {
        simulationForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const memberCount = parseInt(document.getElementById('memberCount')?.value || '10', 10);

            // UI: başlat
            simResults.classList.add('d-none');
            simSuccessAlert.classList.remove('d-none');
            simErrorAlert.classList.add('d-none');
            simProgress.classList.remove('d-none');
            testReportSection.classList.add('d-none');
            setLoading(btnSimulate, true, 'Simülasyon çalışıyor…');

            const steps = [
                'Sanal üyeler oluşturuluyor…',
                'Token\'lar üretiliyor…',
                'Oylar kullanılıyor…',
                'Hash bütünlüğü doğrulanıyor…',
            ];
            let stepIndex = 0;
            const stepInterval = setInterval(function () {
                if (stepIndex < steps.length) {
                    simProgressText.textContent = steps[stepIndex++];
                }
            }, 600);

            try {
                const csrf = document.getElementById('simCsrf')?.value || getCsrf('simCsrf');
                const result = await postJson('/admin/test/simulate', {
                    _csrf: csrf,
                    member_count: memberCount,
                });

                clearInterval(stepInterval);
                simProgress.classList.add('d-none');
                simResults.classList.remove('d-none');

                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.error || 'Simülasyon başarısız.');
                }

                const d = result.data;

                // İstatistik kartları
                const stats = [
                    { label: 'Oluşturulan Üye',   value: d.members_created,     icon: 'bi-person-plus-fill',  cls: 'primary' },
                    { label: 'Üretilen Token',     value: d.tokens_generated,    icon: 'bi-key-fill',          cls: 'info'    },
                    { label: 'Kullanılan Oy',      value: d.votes_cast,          icon: 'bi-check2-square',     cls: 'success' },
                    { label: 'Çift Oy Engeli',     value: d.double_vote_blocked, icon: 'bi-shield-fill-check', cls: 'warning' },
                ];
                simStatsGrid.innerHTML = stats.map(function (s) {
                    return `<div class="col-6 col-md-3">
                        <div class="text-center">
                            <div class="fs-2 fw-bold text-${s.cls}">${s.value ?? '—'}</div>
                            <div class="small text-muted"><i class="bi ${s.icon} me-1"></i>${s.label}</div>
                        </div>
                    </div>`;
                }).join('');

                // Özet liste
                if (d.summary) {
                    simSummaryList.innerHTML = '<ul class="list-unstyled mb-0 small">' +
                        Object.values(d.summary).map(function (line) {
                            const icon = line.includes('✓') ? 'bi-check-circle-fill text-success' :
                                         line.includes('✗') ? 'bi-x-circle-fill text-danger' :
                                         'bi-info-circle text-muted';
                            return `<li class="mb-1"><i class="bi ${icon} me-2"></i>${escHtml(line)}</li>`;
                        }).join('') + '</ul>';
                }

                // Hash bütünlüğü uyarısı
                if (!d.hash_integrity) {
                    simSuccessAlert.classList.remove('alert-success');
                    simSuccessAlert.classList.add('alert-warning');
                }

                // Test raporu oluştur
                buildTestReport(d);
                testReportSection.classList.remove('d-none');

            } catch (err) {
                clearInterval(stepInterval);
                simProgress.classList.add('d-none');
                simResults.classList.remove('d-none');
                simSuccessAlert.classList.add('d-none');
                simErrorAlert.classList.remove('d-none');
                simErrorMsg.textContent = err.message;
            } finally {
                setLoading(btnSimulate, false);
            }
        });
    }

    /**
     * CLAUDE.md Bölüm 7 formatında metin raporu oluşturur.
     */
    function buildTestReport(d) {
        if (!testReportContent) return;
        const now = new Date();
        const dateStr = now.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric' }) +
                        ', ' + now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });

        const lines = [
            'Sistem Test Kaydı (Seçim Öncesi Doğrulama)',
            '-------------------------------------------',
            'Test tarihi/saati : ' + dateStr,
            'Sanal üye sayısı  : ' + (d.members_created ?? '—'),
            'Kullanılan oy     : ' + (d.votes_cast ?? '—'),
            'Test edilen kurul : ' + (d.ballots_tested ?? '—'),
            'Çift oy testi     : ' + (d.double_vote_blocked + '/' + d.tokens_generated) + ' engellendi',
            'Hash bütünlüğü    : ' + (d.hash_integrity ? 'Doğrulandı ✓' : 'Hata bulundu ✗ (' + d.hash_errors + ' hata)'),
            'Sonuç             : ' + (d.hash_integrity && d.double_vote_blocked === d.tokens_generated
                ? 'Sistem güvenli, seçim yapılabilir'
                : 'Kontrol edilmesi gereken sorunlar var'),
        ];
        testReportContent.textContent = lines.join('\n');
    }

    /* ------------------------------------------------------------------ */
    /* Bölüm 3: Temizlik                                                    */
    /* ------------------------------------------------------------------ */

    const btnCleanupConfirm = document.getElementById('btnCleanupConfirm');
    const cleanupResult     = document.getElementById('cleanupResult');
    const cleanupSuccess    = document.getElementById('cleanupSuccess');
    const cleanupError      = document.getElementById('cleanupError');
    const cleanupMsg        = document.getElementById('cleanupMsg');
    const cleanupErrMsg     = document.getElementById('cleanupErrMsg');

    if (btnCleanupConfirm) {
        btnCleanupConfirm.addEventListener('click', async function () {
            setLoading(btnCleanupConfirm, true, 'Siliniyor…');

            const csrf = document.getElementById('cleanupCsrf')?.value || getCsrf('cleanupCsrf');

            try {
                const result = await postJson('/admin/test/cleanup', { _csrf: csrf });

                // Modali kapat
                const modal = bootstrap.Modal.getInstance(document.getElementById('cleanupModal'));
                if (modal) modal.hide();

                cleanupResult.classList.remove('d-none');
                cleanupSuccess.classList.remove('d-none');
                cleanupError.classList.add('d-none');

                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.error || 'Temizlik başarısız.');
                }

                cleanupMsg.textContent = result.data.message || 'Test verileri silindi.';

                // Sayfayı 2 saniye sonra yenile (yeni durumu yansıtmak için)
                setTimeout(function () {
                    window.location.reload();
                }, 2000);

            } catch (err) {
                cleanupResult.classList.remove('d-none');
                cleanupSuccess.classList.add('d-none');
                cleanupError.classList.remove('d-none');
                cleanupErrMsg.textContent = err.message;
            } finally {
                setLoading(btnCleanupConfirm, false);
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* XSS koruması — HTML escape                                           */
    /* ------------------------------------------------------------------ */
    function escHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

})();
