<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Divan extends Model
{
    protected string $table = 'divan';

    public function byElection(int $electionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM divan WHERE election_id = ? ORDER BY FIELD(role, 'baskan', 'uye', 'katip'), id"
        );
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function hasBaskan(int $electionId): bool
    {
        return $this->count("election_id = ? AND role = 'baskan'", [$electionId]) > 0;
    }
}
