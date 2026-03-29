<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Models\{Token, Vote, Ballot, Candidate, Receipt, Election, Member};
use App\Services\{CryptoService, TokenService, SmsService, ActivityLogService};

class VoteController extends Controller
{
    public function show(string $token): void
    {
        $tokenService = new TokenService();
        $tokenData = $tokenService->validate($token);

        if (!$tokenData) {
            View::layout('fullscreen', 'oylama/expired', ['reason' => 'invalid_token']);
            return;
        }

        $electionModel = new Election();
        $election = $electionModel->find($tokenData['election_id']);
        if (!$election || $election['status'] !== 'open') {
            View::layout('fullscreen', 'oylama/expired', ['reason' => 'election_not_open']);
            return;
        }

        $ballotModel = new Ballot();
        $candidateModel = new Candidate();
        $ballots = $ballotModel->byElection($election['id']);
        foreach ($ballots as &$ballot) {
            $ballot['candidates'] = $candidateModel->byBallot($ballot['id']);
        }

        View::layout('fullscreen', 'oylama/show', [
            'token'      => $token,
            'election'   => $election,
            'ballots'    => $ballots,
            'expires_at' => $tokenData['expires_at'],
            'csrf'       => $this->csrfField(),
        ]);
    }

    public function store(string $token): void
    {
        $this->verifyCsrf();

        $tokenService = new TokenService();
        $tokenData = $tokenService->validate($token);

        if (!$tokenData) {
            View::layout('fullscreen', 'oylama/expired', ['reason' => 'invalid_token']);
            return;
        }

        $electionId = $tokenData['election_id'];
        $tokenHash  = $tokenData['token_hash'];

        // Validate selections per ballot
        $ballotModel    = new Ballot();
        $candidateModel = new Candidate();
        $ballots        = $ballotModel->byElection($electionId);
        $allSelections  = [];

        foreach ($ballots as $ballot) {
            $key      = 'ballot_' . $ballot['id'];
            $selected = $_POST[$key] ?? [];
            if (!is_array($selected)) {
                $selected = [];
            }
            $selected = array_map('intval', $selected);

            // Quota check
            if (count($selected) > (int) $ballot['quota']) {
                View::layout('fullscreen', 'oylama/expired', ['reason' => 'quota_exceeded']);
                return;
            }

            // Validate candidate IDs belong to this ballot
            $validCandidates = $candidateModel->byBallot($ballot['id']);
            $validIds        = array_map(fn($c) => (int) $c['id'], $validCandidates);
            foreach ($selected as $candidateId) {
                if (!in_array($candidateId, $validIds, true)) {
                    View::layout('fullscreen', 'oylama/expired', ['reason' => 'invalid_candidate']);
                    return;
                }
            }

            $allSelections[$ballot['id']] = $selected;
        }

        // ATOMIC: vote insert + token burn in same transaction
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $voteModel            = new Vote();
            $allCommitmentHashes  = [];

            foreach ($allSelections as $ballotId => $candidateIds) {
                $choiceJson     = json_encode($candidateIds);
                $salt           = CryptoService::generateSalt();
                $commitmentHash = CryptoService::commitmentHash($choiceJson, $salt, $token);

                // INSERT INTO votes — NO member_id!
                $voteModel->castVote($electionId, $ballotId, $tokenHash, $candidateIds, $commitmentHash);
                $allCommitmentHashes[] = $commitmentHash;
            }

            // Burn token
            $tokenService->burn($tokenHash);

            // Generate receipt with combined commitment hash
            $publicCode   = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $combinedHash = hash('sha256', implode('', $allCommitmentHashes));

            $receiptModel = new Receipt();
            $receiptModel->create([
                'election_id'     => $electionId,
                'public_code'     => $publicCode,
                'commitment_hash' => $combinedHash,
            ]);

            $db->commit();

            // Send receipt SMS (read member phone via token — this is acceptable;
            // we read tokens table for phone lookup, NOT joining with votes)
            $memberModel = new Member();
            $member      = $memberModel->find($tokenData['member_id']);
            if ($member && $member['phone']) {
                $sms = new SmsService();
                $sms->send($member['phone'], "Oyla makbuz kodunuz: {$publicCode}");
            }

            ActivityLogService::log('vote_cast', "Oy kullanıldı. Makbuz: {$publicCode}", $electionId);

            View::layout('fullscreen', 'oylama/confirm', [
                'public_code' => $publicCode,
            ]);

        } catch (\Throwable $e) {
            $db->rollBack();
            ActivityLogService::log('vote_error', $e->getMessage(), $electionId);
            View::layout('fullscreen', 'oylama/expired', ['reason' => 'system_error']);
        }
    }

    public function verify(): void
    {
        View::layout('fullscreen', 'oylama/verify', [
            'csrf'     => $this->csrfField(),
            'searched' => false,
        ]);
    }

    public function verifyCheck(): void
    {
        $this->verifyCsrf();
        $code = strtoupper(trim($this->input('code', '')));

        $receiptModel = new Receipt();
        $receipt      = $receiptModel->findByCode($code);

        View::layout('fullscreen', 'oylama/verify', [
            'csrf'     => $this->csrfField(),
            'code'     => $code,
            'found'    => $receipt !== null,
            'searched' => true,
        ]);
    }
}
