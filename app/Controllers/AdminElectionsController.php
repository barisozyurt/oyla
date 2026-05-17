<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\Election;
use App\Services\ActivityLogService;

/**
 * Admin → Seçim yönetimi (seçim CRUD + override + PDF).
 *
 * Eskiden AdminController içindeydi; FAZ 2.6 god-controller split kapsamında ayrıldı.
 */
class AdminElectionsController extends Controller
{
    public function index(): void
    {
        Middleware::requireAuth('admin');
        $elections = (new Election())->all('id DESC');
        $this->layout('main', 'admin.elections', [
            'pageTitle' => 'Seçim Yönetimi',
            'elections' => $elections,
        ]);
    }

    public function store(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $title       = trim((string) $this->input('title', ''));
        $description = trim((string) $this->input('description', ''));
        if ($title === '') {
            flash('error', 'Seçim başlığı zorunludur.');
            $this->redirect('/admin/elections');
            return;
        }

        $electionModel = new Election();
        $current = $this->currentUser();
        $newId = $electionModel->create([
            'title'       => $title,
            'description' => $description ?: null,
            'status'      => 'draft',
            'created_by'  => $current ? (int) $current['id'] : null,
        ]);
        $_SESSION['election_id'] = $newId;

        ActivityLogService::log('election_created', "Yeni seçim oluşturuldu: {$title}", $newId);
        flash('success', "Seçim \"{$title}\" oluşturuldu ve aktif seçim olarak ayarlandı.");
        $this->redirect('/admin/elections');
    }

    public function override(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $electionId = (int) $this->input('election_id', 0);
        $status     = (string) $this->input('status', '');
        if (!in_array($status, ['draft', 'test', 'open', 'closed'], true)) {
            flash('error', 'Geçersiz durum değeri.');
            $this->redirect('/admin/elections');
            return;
        }

        $electionModel = new Election();
        $election = $electionModel->find($electionId);
        if (!$election) {
            flash('error', 'Seçim bulunamadı.');
            $this->redirect('/admin/elections');
            return;
        }

        $update = ['status' => $status];
        if ($status === 'open' && empty($election['started_at']))   $update['started_at'] = date('Y-m-d H:i:s');
        if ($status === 'closed' && empty($election['closed_at'])) $update['closed_at']  = date('Y-m-d H:i:s');
        $electionModel->update($electionId, $update);

        ActivityLogService::log(
            'election_override',
            "Admin seçim durumunu değiştirdi: \"{$election['title']}\" → {$status}",
            $electionId
        );
        flash('success', "Seçim durumu \"{$status}\" olarak güncellendi.");
        $this->redirect('/admin/elections');
    }

    public function downloadTutanak(): void
    {
        Middleware::requireAuth('admin');
        $electionId = $this->currentElectionId();
        if (!$electionId) {
            $current = (new Election())->current();
            $electionId = $current ? (int) $current['id'] : null;
        }
        if (!$electionId) {
            flash('error', 'Aktif seçim bulunamadı. PDF oluşturulamadı.');
            $this->redirect('/admin');
            return;
        }
        // PdfService entegrasyonu Phase 11 ile birlikte tamamlanacak; şimdilik bilgi mesajı.
        flash('info', 'PDF tutanak özelliği yakında etkinleştirilecek.');
        $this->redirect('/admin');
    }
}
