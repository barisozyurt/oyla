<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\Ballot;
use App\Models\Election;
use App\Models\Member;
use App\Models\Vote;

class ResultController extends Controller
{
    /**
     * Sonuç ekranı — herkese açık, giriş gerektirmez.
     */
    public function index(): void
    {
        $electionModel = new Election();
        $election      = $electionModel->current();

        if (!$election) {
            $this->layout('main', 'sonuc/no_election', ['pageTitle' => 'Sonuçlar']);
            return;
        }

        $ballotModel = new Ballot();
        $voteModel   = new Vote();
        $memberModel = new Member();

        $ballots = $ballotModel->byElection((int) $election['id']);
        $results = [];
        foreach ($ballots as $ballot) {
            $results[] = [
                'ballot'      => $ballot,
                'candidates'  => $voteModel->resultsByBallot((int) $ballot['id']),
                'total_votes' => $voteModel->countByBallot((int) $ballot['id']),
            ];
        }

        $totalMembers  = $memberModel->count('election_id = ?', [(int) $election['id']]);
        $votedMembers  = $memberModel->countByStatus((int) $election['id'], 'done');
        $participationPct = $totalMembers > 0
            ? round($votedMembers / $totalMembers * 100, 1)
            : 0.0;

        $this->layout('main', 'sonuc/index', [
            'pageTitle'        => 'Sonuçlar — ' . $election['title'],
            'election'         => $election,
            'results'          => $results,
            'totalMembers'     => $totalMembers,
            'votedMembers'     => $votedMembers,
            'participationPct' => $participationPct,
        ]);
    }

    /**
     * JSON endpoint for polling — returns live results data.
     */
    public function data(): void
    {
        $electionModel = new Election();
        $election      = $electionModel->current();

        if (!$election) {
            $this->json(['error' => 'Aktif seçim yok'], 404);
            return;
        }

        $ballotModel = new Ballot();
        $voteModel   = new Vote();
        $memberModel = new Member();

        $ballots = $ballotModel->byElection((int) $election['id']);
        $results = [];
        foreach ($ballots as $ballot) {
            $results[] = [
                'ballot'      => $ballot,
                'candidates'  => $voteModel->resultsByBallot((int) $ballot['id']),
                'total_votes' => $voteModel->countByBallot((int) $ballot['id']),
            ];
        }

        $total  = $memberModel->count('election_id = ?', [(int) $election['id']]);
        $voted  = $memberModel->countByStatus((int) $election['id'], 'done');

        $this->json([
            'election' => [
                'id'     => $election['id'],
                'title'  => $election['title'],
                'status' => $election['status'],
            ],
            'participation' => [
                'total'      => $total,
                'voted'      => $voted,
                'percentage' => $total > 0 ? round($voted / $total * 100, 1) : 0.0,
            ],
            'results' => $results,
        ]);
    }

    /**
     * Perde / salon ekranı — fullscreen, dark, auto-rotating.
     */
    public function curtain(): void
    {
        $electionModel = new Election();
        $election      = $electionModel->current();

        if (!$election) {
            View::layout('fullscreen', 'sonuc/no_election', [
                'pageTitle' => 'Sonuçlar',
                'bodyClass' => 'curtain-mode',
            ]);
            return;
        }

        $ballotModel = new Ballot();
        $voteModel   = new Vote();
        $memberModel = new Member();

        $ballots = $ballotModel->byElection((int) $election['id']);
        $results = [];
        foreach ($ballots as $ballot) {
            $results[] = [
                'ballot'      => $ballot,
                'candidates'  => $voteModel->resultsByBallot((int) $ballot['id']),
                'total_votes' => $voteModel->countByBallot((int) $ballot['id']),
            ];
        }

        $totalMembers = $memberModel->count('election_id = ?', [(int) $election['id']]);
        $votedMembers = $memberModel->countByStatus((int) $election['id'], 'done');

        View::layout('fullscreen', 'sonuc/curtain', [
            'pageTitle'    => 'Perde Modu — ' . $election['title'],
            'bodyClass'    => 'curtain-mode',
            'election'     => $election,
            'results'      => $results,
            'totalMembers' => $totalMembers,
            'votedMembers' => $votedMembers,
        ]);
    }

    /**
     * JSON endpoint for participation stats only (lightweight poll).
     */
    public function participation(): void
    {
        $electionModel = new Election();
        $election      = $electionModel->current();

        if (!$election) {
            $this->json(['error' => 'Aktif seçim yok'], 404);
            return;
        }

        $memberModel = new Member();
        $total = $memberModel->count('election_id = ?', [(int) $election['id']]);
        $voted = $memberModel->countByStatus((int) $election['id'], 'done');

        $this->json([
            'total'      => $total,
            'voted'      => $voted,
            'percentage' => $total > 0 ? round($voted / $total * 100, 1) : 0.0,
        ]);
    }
}
