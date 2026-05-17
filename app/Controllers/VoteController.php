<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\RateLimiter;
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
        // FAZ 2: eager-load — tek query'de tüm ballot+candidate'ler
        $ballots = $this->loadBallotsWithCandidates($ballotModel, $candidateModel, (int) $election['id']);

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

        // Oy submit rate limit (IP başına 10/dk)
        if (!RateLimiter::check('vote')) {
            View::layout('fullscreen', 'oylama/expired', ['reason' => 'rate_limited']);
            return;
        }

        $tokenService = new TokenService();
        $tokenData = $tokenService->validate($token);

        if (!$tokenData) {
            View::layout('fullscreen', 'oylama/expired', ['reason' => 'invalid_token']);
            return;
        }

        $electionId = (int) $tokenData['election_id'];
        $tokenHash  = $tokenData['token_hash'];

        $ballotModel    = new Ballot();
        $candidateModel = new Candidate();
        $ballots        = $this->loadBallotsWithCandidates($ballotModel, $candidateModel, $electionId);
        $allSelections  = [];

        foreach ($ballots as $ballot) {
            $key      = 'ballot_' . $ballot['id'];
            $selected = $_POST[$key] ?? [];
            if (!is_array($selected)) {
                $selected = [];
            }
            $selected = array_map('intval', $selected);

            // Kota
            if (count($selected) > (int) $ballot['quota']) {
                View::layout('fullscreen', 'oylama/expired', ['reason' => 'quota_exceeded']);
                return;
            }

            // Aday geçerliliği
            $validIds = array_map(fn($c) => (int) $c['id'], $ballot['candidates']);
            foreach ($selected as $candidateId) {
                if (!in_array($candidateId, $validIds, true)) {
                    View::layout('fullscreen', 'oylama/expired', ['reason' => 'invalid_candidate']);
                    return;
                }
            }

            // Aynı adayın 2 kez seçilmesini engelle
            if (count($selected) !== count(array_unique($selected))) {
                View::layout('fullscreen', 'oylama/expired', ['reason' => 'invalid_candidate']);
                return;
            }

            $allSelections[$ballot['id']] = $selected;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Token burn atomic — race condition'a karşı önce burn dene
            if (!$tokenService->burnAtomic($tokenHash)) {
                $db->rollBack();
                View::layout('fullscreen', 'oylama/expired', ['reason' => 'already_voted']);
                return;
            }

            $voteModel            = new Vote();
            $allCommitmentHashes  = [];

            foreach ($allSelections as $ballotId => $candidateIds) {
                $choiceJson     = json_encode($candidateIds);
                $salt           = CryptoService::generateSalt();
                $commitmentHash = CryptoService::commitmentHash($choiceJson, $salt, $token);

                // INSERT INTO votes — NO member_id!
                $voteModel->castVote($electionId, (int) $ballotId, $tokenHash, $candidateIds, $commitmentHash, $salt, CryptoService::VERSION);
                $allCommitmentHashes[] = $commitmentHash;
            }

            // Makbuz kodu + combined HMAC bağlantısı
            $publicCode  = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $combinedHash = CryptoService::combinedCommitment($allCommitmentHashes, $token);

            $receiptModel = new Receipt();
            $receiptModel->create([
                'election_id'     => $electionId,
                'public_code'     => $publicCode,
                'commitment_hash' => $combinedHash,
                'crypto_version'  => CryptoService::VERSION,
            ]);

            $db->commit();
            RateLimiter::recordSuccess('vote');

            // Makbuz SMS — telefon lookup yalnızca tokens üzerinden, votes'a dokunmadan
            $memberModel = new Member();
            $member      = $memberModel->find($tokenData['member_id']);
            if ($member && !empty($member['phone'])) {
                $sms = new SmsService();
                $sms->send(
                    $member['phone'],
                    "Oyla makbuz kodunuz: {$publicCode} — Doğrulama: /oy/dogrula"
                );
            }

            ActivityLogService::log('vote_cast', "Oy kullanıldı. Makbuz: {$publicCode}", $electionId);

            View::layout('fullscreen', 'oylama/confirm', [
                'public_code' => $publicCode,
            ]);

        } catch (\Throwable $e) {
            $db->rollBack();
            Logger::error('Oy verme hatası', ['err' => $e->getMessage(), 'token_hash' => Logger::maskToken($tokenHash)]);
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
        $code = strtoupper(trim((string) $this->input('code', '')));

        $receiptModel = new Receipt();
        $receipt      = $receiptModel->findByCode($code);

        View::layout('fullscreen', 'oylama/verify', [
            'csrf'     => $this->csrfField(),
            'code'     => $code,
            'found'    => $receipt !== null,
            'searched' => true,
        ]);
    }

    /**
     * Eager-load ballot+candidate'ler — eskiden ballot başına ayrı query (N+1).
     * Şimdi tek query + memory'de grupla.
     */
    private function loadBallotsWithCandidates(Ballot $ballotModel, Candidate $candidateModel, int $electionId): array
    {
        $ballots = $ballotModel->byElection($electionId);
        if (!$ballots) return [];

        $ballotIds = array_map(fn($b) => (int) $b['id'], $ballots);
        $placeholders = implode(',', array_fill(0, count($ballotIds), '?'));
        $stmt = $candidateModel->db()->prepare(
            "SELECT * FROM candidates WHERE ballot_id IN ({$placeholders}) ORDER BY ballot_id, sort_order, id"
        );
        $stmt->execute($ballotIds);
        $allCandidates = $stmt->fetchAll();

        $byBallot = [];
        foreach ($allCandidates as $c) {
            $byBallot[(int) $c['ballot_id']][] = $c;
        }
        foreach ($ballots as &$b) {
            $b['candidates'] = $byBallot[(int) $b['id']] ?? [];
        }
        return $ballots;
    }
}
