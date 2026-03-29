<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Candidate extends Model
{
    protected string $table = 'candidates';

    public function byBallot(int $ballotId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM candidates WHERE ballot_id = ? ORDER BY sort_order, id"
        );
        $stmt->execute([$ballotId]);
        return $stmt->fetchAll();
    }
}
