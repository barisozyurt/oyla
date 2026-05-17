<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\ActivityLog;
use App\Models\Election;
use App\Models\Member;
use App\Models\User;
use App\Models\Vote;

/**
 * Admin → Genel Bakış + Aktivite Log görüntüleyici.
 *
 * Eskiden 600+ satırlık god-controller'dı. FAZ 2.6 ile bölündü:
 *  - AdminUsersController      (kullanıcı CRUD)
 *  - AdminElectionsController  (seçim CRUD + override + PDF)
 *  - AdminSystemController     (status + log integrity + hash export + anonymize)
 */
class AdminController extends Controller
{
    public function index(): void
    {
        Middleware::requireAuth('admin');

        $electionModel = new Election();
        $userModel     = new User();
        $memberModel   = new Member();
        $voteModel     = new Vote();

        $allElections   = $electionModel->all('id DESC');
        $totalElections = count($allElections);
        $totalUsers     = count($userModel->all());

        $electionId      = $this->currentElectionId();
        $currentElection = null;
        $totalMembers    = 0;
        $totalVotes      = 0;

        if ($electionId) {
            $currentElection = $electionModel->find($electionId);
            $totalMembers    = $memberModel->count('election_id = ?', [$electionId]);
            $totalVotes      = $voteModel->count('election_id = ?', [$electionId]);
        } else {
            $currentElection = $electionModel->current();
            if ($currentElection) {
                $eid          = (int) $currentElection['id'];
                $totalMembers = $memberModel->count('election_id = ?', [$eid]);
                $totalVotes   = $voteModel->count('election_id = ?', [$eid]);
            }
        }

        $this->layout('main', 'admin.index', [
            'pageTitle'       => 'Admin Paneli',
            'totalElections'  => $totalElections,
            'totalUsers'      => $totalUsers,
            'totalMembers'    => $totalMembers,
            'totalVotes'      => $totalVotes,
            'currentElection' => $currentElection,
        ]);
    }

    public function activityLog(): void
    {
        Middleware::requireAuth('admin');

        $logModel      = new ActivityLog();
        $electionModel = new Election();

        $filterElectionId = isset($_GET['election_id']) && $_GET['election_id'] !== ''
            ? (int) $_GET['election_id']
            : null;

        $logs = $filterElectionId
            ? $logModel->byElection($filterElectionId, 200)
            : $logModel->recent(200);

        $allElections = $electionModel->all('id DESC');

        $this->layout('main', 'admin.activity_log', [
            'pageTitle'        => 'Aktivite Logu',
            'logs'             => $logs,
            'allElections'     => $allElections,
            'filterElectionId' => $filterElectionId,
        ]);
    }
}
