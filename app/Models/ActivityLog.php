<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ActivityLog extends Model
{
    protected string $table = 'activity_log';

    public function log(string $action, string $description, ?int $electionId = null): void
    {
        $this->create([
            'election_id' => $electionId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    }

    public function byElection(int $electionId, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM activity_log WHERE election_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$electionId, $limit]);
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
