<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Ballot extends Model
{
    protected string $table = 'ballots';

    public function byElection(int $electionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM ballots WHERE election_id = ? ORDER BY sort_order, id"
        );
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function withCandidates(int $ballotId): array
    {
        $ballot = $this->find($ballotId);
        if (!$ballot) return [];

        $stmt = $this->db->prepare(
            "SELECT * FROM candidates WHERE ballot_id = ? ORDER BY sort_order, id"
        );
        $stmt->execute([$ballotId]);
        $ballot['candidates'] = $stmt->fetchAll();
        return $ballot;
    }
}
