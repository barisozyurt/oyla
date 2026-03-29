<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Member;
use App\Services\ActivityLogService;

class BallotController extends Controller
{
    /**
     * List all ballots with their candidates for the current election.
     */
    public function index(): void
    {
        Middleware::requireAuth('admin');

        $electionId = $this->currentElectionId();
        $ballotModel = new Ballot();
        $ballots = [];

        if ($electionId) {
            $rawBallots = $ballotModel->byElection($electionId);
            foreach ($rawBallots as $ballot) {
                $ballots[] = $ballotModel->withCandidates((int) $ballot['id']);
            }
        }

        $memberModel = new Member();
        $members = $electionId ? $memberModel->byElection($electionId) : [];

        $this->layout('main', 'yonetim.ballots', [
            'pageTitle' => 'Kurul Yönetimi',
            'ballots'   => $ballots,
            'members'   => $members,
        ]);
    }

    /**
     * Create a new ballot for the current election.
     */
    public function store(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            flash('error', 'Aktif seçim bulunamadı.');
            $this->redirect('/yonetim/ballots');
            return;
        }

        $title       = trim($this->input('title', ''));
        $quota       = (int) $this->input('quota', 0);
        $yedekQuota  = (int) $this->input('yedek_quota', 0);

        if ($title === '') {
            flash('error', 'Kurul başlığı zorunludur.');
            $this->redirect('/yonetim/ballots');
            return;
        }
        if ($quota < 1) {
            flash('error', 'Kontenjan en az 1 olmalıdır.');
            $this->redirect('/yonetim/ballots');
            return;
        }

        $ballotModel = new Ballot();
        $ballotModel->create([
            'election_id'  => $electionId,
            'title'        => $title,
            'quota'        => $quota,
            'yedek_quota'  => $yedekQuota,
            'sort_order'   => 0,
        ]);

        ActivityLogService::log('ballot_create', "Kurul eklendi: {$title}", $electionId);
        flash('success', "Kurul '{$title}' oluşturuldu.");
        $this->redirect('/yonetim/ballots');
    }

    /**
     * Update an existing ballot.
     */
    public function update(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $ballotModel = new Ballot();
        $ballot = $ballotModel->find((int) $id);

        if (!$ballot) {
            flash('error', 'Kurul bulunamadı.');
            $this->redirect('/yonetim/ballots');
            return;
        }

        $title      = trim($this->input('title', ''));
        $quota      = (int) $this->input('quota', 0);
        $yedekQuota = (int) $this->input('yedek_quota', 0);

        if ($title === '' || $quota < 1) {
            flash('error', 'Kurul başlığı ve kontenjan zorunludur.');
            $this->redirect('/yonetim/ballots');
            return;
        }

        $ballotModel->update((int) $id, [
            'title'       => $title,
            'quota'       => $quota,
            'yedek_quota' => $yedekQuota,
        ]);

        ActivityLogService::log('ballot_update', "Kurul güncellendi: {$title}", $ballot['election_id']);
        flash('success', "Kurul '{$title}' güncellendi.");
        $this->redirect('/yonetim/ballots');
    }

    /**
     * Delete a ballot — only when election is in draft status.
     */
    public function destroy(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $ballotModel = new Ballot();
        $ballot = $ballotModel->find((int) $id);

        if (!$ballot) {
            flash('error', 'Kurul bulunamadı.');
            $this->redirect('/yonetim/ballots');
            return;
        }

        $electionModel = new Election();
        $election = $electionModel->find($ballot['election_id']);

        if (!$election || $election['status'] !== 'draft') {
            flash('error', 'Kurul silme işlemi yalnızca taslak seçimlerde yapılabilir.');
            $this->redirect('/yonetim/ballots');
            return;
        }

        $ballotModel->delete((int) $id);

        ActivityLogService::log('ballot_delete', "Kurul silindi: {$ballot['title']}", $ballot['election_id']);
        flash('success', "Kurul '{$ballot['title']}' silindi.");
        $this->redirect('/yonetim/ballots');
    }

    /**
     * Add a candidate to a ballot.
     */
    public function addCandidate(string $ballotId): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $ballotModel = new Ballot();
        $ballot = $ballotModel->find((int) $ballotId);

        if (!$ballot) {
            flash('error', 'Kurul bulunamadı.');
            $this->redirect('/yonetim/ballots');
            return;
        }

        $name        = trim($this->input('name', ''));
        $memberId    = $this->input('member_id', '');
        $candidateNo = trim($this->input('candidate_no', ''));

        if ($name === '') {
            flash('error', 'Aday adı zorunludur.');
            $this->redirect('/yonetim/ballots');
            return;
        }

        $candidateModel = new Candidate();
        $data = [
            'ballot_id'    => (int) $ballotId,
            'name'         => $name,
            'candidate_no' => $candidateNo ?: null,
            'sort_order'   => 0,
        ];

        if ($memberId !== '' && (int) $memberId > 0) {
            $data['member_id'] = (int) $memberId;
        }

        $candidateModel->create($data);

        ActivityLogService::log(
            'candidate_add',
            "Aday eklendi: {$name} → {$ballot['title']}",
            $ballot['election_id']
        );
        flash('success', "Aday '{$name}' eklendi.");
        $this->redirect('/yonetim/ballots');
    }

    /**
     * Remove a candidate from a ballot.
     */
    public function removeCandidate(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $candidateModel = new Candidate();
        $candidate = $candidateModel->find((int) $id);

        if (!$candidate) {
            flash('error', 'Aday bulunamadı.');
            $this->redirect('/yonetim/ballots');
            return;
        }

        $ballotModel = new Ballot();
        $ballot = $ballotModel->find($candidate['ballot_id']);

        $candidateModel->delete((int) $id);

        ActivityLogService::log(
            'candidate_remove',
            "Aday silindi: {$candidate['name']}",
            $ballot['election_id'] ?? null
        );
        flash('success', "Aday '{$candidate['name']}' silindi.");
        $this->redirect('/yonetim/ballots');
    }
}
