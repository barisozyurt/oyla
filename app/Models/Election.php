<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Election extends Model
{
    protected string $table = 'elections';

    public function current(): ?array
    {
        // Return the most recent non-draft election, or the latest election
        $stmt = $this->db->prepare(
            "SELECT * FROM elections WHERE status IN ('open','closed','test') ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result) return $result;

        // Fallback: latest election of any status
        $stmt = $this->db->prepare("SELECT * FROM elections ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function isOpen(array $election): bool
    {
        return $election['status'] === 'open';
    }

    public function isClosed(array $election): bool
    {
        return $election['status'] === 'closed';
    }

    public function isDraft(array $election): bool
    {
        return $election['status'] === 'draft';
    }

    public function start(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE elections SET status = 'open', started_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public function close(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE elections SET status = 'closed', closed_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public function setTestMode(int $id, bool $enabled): void
    {
        $stmt = $this->db->prepare(
            "UPDATE elections SET test_mode = ?, status = ? WHERE id = ?"
        );
        $stmt->execute([$enabled ? 1 : 0, $enabled ? 'test' : 'draft', $id]);
    }
}
