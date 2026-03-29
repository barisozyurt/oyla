<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Divan;
use App\Models\Election;
use App\Models\Member;
use App\Services\ActivityLogService;

class DivanController extends Controller
{
    /**
     * Divan paneli ana sayfası.
     * Seçim, divan kurulu, kurullar ve istatistikleri yükler.
     */
    public function index(): void
    {
        Middleware::requireAuth('admin', 'divan_baskani');

        $electionId = $this->currentElectionId();
        $election   = null;

        if ($electionId) {
            $electionModel = new Election();
            $election      = $electionModel->find($electionId);
        }

        $divanMembers = [];
        $ballots      = [];
        $stats        = [
            'total_members'     => 0,
            'signed_count'      => 0,
            'voted_count'       => 0,
            'participation_pct' => 0.0,
        ];

        if ($election) {
            $divanModel   = new Divan();
            $ballotModel  = new Ballot();
            $candidateModel = new Candidate();
            $memberModel  = new Member();

            $divanMembers = $divanModel->byElection($electionId);

            $rawBallots = $ballotModel->byElection($electionId);
            foreach ($rawBallots as &$ballot) {
                $ballot['candidates'] = $candidateModel->byBallot((int) $ballot['id']);
                $ballot['candidate_count'] = count($ballot['candidates']);
            }
            unset($ballot);
            $ballots = $rawBallots;

            $total  = $memberModel->count('election_id = ?', [$electionId]);
            $signed = $memberModel->countByStatus($electionId, 'signed');
            $done   = $memberModel->countByStatus($electionId, 'done');

            $signedCount = $signed + $done;
            $pct = $total > 0 ? round($done / $total * 100, 1) : 0.0;

            $stats = [
                'total_members'     => $total,
                'signed_count'      => $signedCount,
                'voted_count'       => $done,
                'participation_pct' => $pct,
            ];

            // Prerequisite checks for the start button
            $hasBaskan          = $divanModel->hasBaskan($electionId);
            $hasBallots         = count($ballots) > 0;
            $allBallotsHaveQuota = true;
            foreach ($ballots as $ballot) {
                if ($ballot['candidate_count'] < (int) $ballot['quota']) {
                    $allBallotsHaveQuota = false;
                    break;
                }
            }
            $canStart = $hasBaskan
                && $hasBallots
                && $allBallotsHaveQuota
                && in_array($election['status'], ['draft', 'test'], true);
        } else {
            $hasBaskan          = false;
            $hasBallots         = false;
            $allBallotsHaveQuota = true;
            $canStart           = false;
        }

        $this->layout('main', 'divan.index', [
            'pageTitle'          => 'Divan Paneli',
            'election'           => $election,
            'divanMembers'       => $divanMembers,
            'ballots'            => $ballots,
            'stats'              => $stats,
            'canStart'           => $canStart,
            'hasBaskan'          => $hasBaskan ?? false,
            'hasBallots'         => $hasBallots ?? false,
            'allBallotsHaveQuota' => $allBallotsHaveQuota ?? true,
        ]);
    }

    /**
     * Divan kuruluna yeni üye ekler.
     */
    public function storeDivan(): void
    {
        Middleware::requireAuth('admin', 'divan_baskani');
        $this->verifyCsrf();

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            flash('error', 'Aktif seçim bulunamadı.');
            $this->redirect('/divan');
            return;
        }

        $role = $this->input('role', '');
        $name = trim($this->input('name', ''));

        if (!in_array($role, ['baskan', 'uye', 'katip'], true) || $name === '') {
            flash('error', 'Lütfen geçerli bir rol ve isim girin.');
            $this->redirect('/divan');
            return;
        }

        // Only one baskan allowed
        if ($role === 'baskan') {
            $divanModel = new Divan();
            if ($divanModel->hasBaskan($electionId)) {
                flash('error', 'Divan kurulunda zaten bir başkan var.');
                $this->redirect('/divan');
                return;
            }
        }

        $divanModel = new Divan();
        $divanModel->create([
            'election_id' => $electionId,
            'role'        => $role,
            'name'        => $name,
        ]);

        $roleLabel = match($role) {
            'baskan' => 'Başkan',
            'katip'  => 'Kâtip',
            default  => 'Üye',
        };

        ActivityLogService::log(
            'divan_member_added',
            "Divan kuruluna eklendi: {$name} ({$roleLabel})",
            $electionId
        );

        flash('success', "Divan kuruluna \"{$name}\" eklendi.");
        $this->redirect('/divan');
    }

    /**
     * Divan kurulundan üye siler.
     */
    public function removeDivan(string $id): void
    {
        Middleware::requireAuth('admin', 'divan_baskani');
        $this->verifyCsrf();

        $electionId = $this->currentElectionId();
        $divanModel = new Divan();
        $member     = $divanModel->find((int) $id);

        if (!$member || (int) $member['election_id'] !== $electionId) {
            flash('error', 'Divan üyesi bulunamadı.');
            $this->redirect('/divan');
            return;
        }

        $divanModel->delete((int) $id);

        ActivityLogService::log(
            'divan_member_removed',
            "Divan kurulundan çıkarıldı: {$member['name']}",
            $electionId
        );

        flash('success', "\"{$member['name']}\" divan kurulundan çıkarıldı.");
        $this->redirect('/divan');
    }

    /**
     * Seçimi başlatır. Tüm ön koşulları doğrular.
     */
    public function startElection(): void
    {
        Middleware::requireAuth('admin', 'divan_baskani');
        $this->verifyCsrf();

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            flash('error', 'Aktif seçim bulunamadı.');
            $this->redirect('/divan');
            return;
        }

        $electionModel  = new Election();
        $divanModel     = new Divan();
        $ballotModel    = new Ballot();
        $candidateModel = new Candidate();

        $election = $electionModel->find($electionId);
        if (!$election) {
            flash('error', 'Seçim bulunamadı.');
            $this->redirect('/divan');
            return;
        }

        // 1. Divan başkanı zorunlu
        if (!$divanModel->hasBaskan($electionId)) {
            flash('error', 'Seçimi başlatmak için divan kurulunda bir başkan olmalıdır.');
            $this->redirect('/divan');
            return;
        }

        // 2. En az bir kurul (ballot) tanımlı olmalı
        $ballots = $ballotModel->byElection($electionId);
        if (empty($ballots)) {
            flash('error', 'Seçimi başlatmak için en az bir seçim kurulu tanımlanmalıdır.');
            $this->redirect('/divan');
            return;
        }

        // 3. Her kurulda yeterli aday sayısı (>= quota)
        foreach ($ballots as $ballot) {
            $candidateCount = (new Candidate())->count('ballot_id = ?', [(int) $ballot['id']]);
            if ($candidateCount < (int) $ballot['quota']) {
                $missing = (int) $ballot['quota'] - $candidateCount;
                flash('error', "\"{$ballot['title']}\" kurulu için yeterli aday yok. {$missing} aday daha gereklidir.");
                $this->redirect('/divan');
                return;
            }
        }

        // 4. Seçim taslak veya test modunda olmalı
        if (!in_array($election['status'], ['draft', 'test'], true)) {
            flash('error', 'Seçim yalnızca taslak veya test durumundayken başlatılabilir.');
            $this->redirect('/divan');
            return;
        }

        $electionModel->start($electionId);

        ActivityLogService::log(
            'election_started',
            "Seçim başlatıldı: {$election['title']}",
            $electionId
        );

        flash('success', 'Seçim başarıyla başlatıldı!');
        $this->redirect('/divan');
    }

    /**
     * Seçimi kapatır.
     */
    public function stopElection(): void
    {
        Middleware::requireAuth('admin', 'divan_baskani');
        $this->verifyCsrf();

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            flash('error', 'Aktif seçim bulunamadı.');
            $this->redirect('/divan');
            return;
        }

        $electionModel = new Election();
        $election      = $electionModel->find($electionId);

        if (!$election) {
            flash('error', 'Seçim bulunamadı.');
            $this->redirect('/divan');
            return;
        }

        $electionModel->close($electionId);

        ActivityLogService::log(
            'election_closed',
            "Seçim kapatıldı: {$election['title']}",
            $electionId
        );

        flash('success', 'Seçim kapatıldı. Sonuçlar artık görüntülenebilir.');
        $this->redirect('/divan');
    }

    /**
     * Anlık istatistikleri JSON olarak döndürür (polling endpoint).
     */
    public function stats(): void
    {
        Middleware::requireAuth('admin', 'divan_baskani');

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            $this->json([
                'total_members'     => 0,
                'signed_count'      => 0,
                'voted_count'       => 0,
                'participation_pct' => 0.0,
                'election_status'   => 'draft',
            ]);
            return;
        }

        $memberModel   = new Member();
        $electionModel = new Election();
        $election      = $electionModel->find($electionId);

        $total  = $memberModel->count('election_id = ?', [$electionId]);
        $signed = $memberModel->countByStatus($electionId, 'signed');
        $done   = $memberModel->countByStatus($electionId, 'done');

        $signedCount = $signed + $done;
        $pct = $total > 0 ? round($done / $total * 100, 1) : 0.0;

        $this->json([
            'total_members'     => $total,
            'signed_count'      => $signedCount,
            'voted_count'       => $done,
            'participation_pct' => $pct,
            'election_status'   => $election['status'] ?? 'draft',
        ]);
    }
}
