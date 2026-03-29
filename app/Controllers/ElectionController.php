<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\Election;
use App\Services\ActivityLogService;

class ElectionController extends Controller
{
    /**
     * Show the election settings form.
     */
    public function settings(): void
    {
        Middleware::requireAuth('admin');

        $electionId = $this->currentElectionId();
        $electionModel = new Election();
        $election = $electionId ? $electionModel->find($electionId) : null;

        $this->layout('main', 'yonetim.settings', [
            'pageTitle' => 'Seçim Ayarları',
            'election'  => $election,
        ]);
    }

    /**
     * Validate and update the current election's title and description.
     */
    public function updateSettings(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            flash('error', 'Aktif seçim bulunamadı.');
            $this->redirect('/yonetim/settings');
            return;
        }

        $title       = trim($this->input('title', ''));
        $description = trim($this->input('description', ''));

        if ($title === '') {
            flash('error', 'Seçim başlığı zorunludur.');
            $this->redirect('/yonetim/settings');
            return;
        }

        $electionModel = new Election();
        $electionModel->update($electionId, [
            'title'       => $title,
            'description' => $description ?: null,
        ]);

        ActivityLogService::log('election_settings_update', "Seçim ayarları güncellendi: {$title}", $electionId);
        flash('success', 'Seçim ayarları kaydedildi.');
        $this->redirect('/yonetim/settings');
    }
}
