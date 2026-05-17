<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Vote extends Model
{
    protected string $table = 'votes';

    /**
     * Cast a vote. NO member_id parameter — anonymity guarantee.
     *
     * Salt ve crypto_version artık DB'de saklanır — ileride yeniden doğrulama yapılabilsin.
     */
    public function castVote(int $electionId, int $ballotId, string $tokenHash, array $candidateIds, string $commitmentHash, string $salt = '', string $cryptoVersion = 'v1'): int
    {
        return $this->create([
            'election_id'      => $electionId,
            'ballot_id'        => $ballotId,
            'token_hash'       => $tokenHash,
            'encrypted_choice' => json_encode($candidateIds),
            'commitment_hash'  => $commitmentHash,
            'salt'             => $salt,
            'crypto_version'   => $cryptoVersion,
        ]);
    }

    public function countByBallot(int $ballotId): int
    {
        return $this->count('ballot_id = ?', [$ballotId]);
    }

    public function resultsByBallot(int $ballotId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.name, c.photo_path, c.candidate_no, c.sort_order,
                    COUNT(v.id) as vote_count
             FROM candidates c
             LEFT JOIN votes v ON v.ballot_id = c.ballot_id
                  AND JSON_CONTAINS(v.encrypted_choice, CONCAT('', c.id))
             WHERE c.ballot_id = ?
             GROUP BY c.id, c.name, c.photo_path, c.candidate_no, c.sort_order
             ORDER BY vote_count DESC, c.sort_order ASC"
        );
        $stmt->execute([$ballotId]);
        return $stmt->fetchAll();
    }

    public function allHashes(int $electionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT v.commitment_hash, v.created_at, b.title as ballot_title
             FROM votes v
             JOIN ballots b ON b.id = v.ballot_id
             WHERE v.election_id = ?
             ORDER BY b.sort_order, v.created_at"
        );
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }
}
