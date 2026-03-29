<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Token extends Model
{
    protected string $table = 'tokens';

    public function findByPlain(string $tokenPlain): ?array
    {
        return $this->findWhere('token_plain', $tokenPlain);
    }

    public function findByHash(string $tokenHash): ?array
    {
        return $this->findWhere('token_hash', $tokenHash);
    }

    public function isValid(string $tokenPlain): bool
    {
        $token = $this->findByPlain($tokenPlain);
        if (!$token) return false;
        if ($token['used']) return false;
        if (strtotime($token['expires_at']) < time()) return false;
        return true;
    }

    public function burn(string $tokenHash): void
    {
        $stmt = $this->db->prepare(
            "UPDATE tokens SET used = 1, used_at = NOW() WHERE token_hash = ?"
        );
        $stmt->execute([$tokenHash]);
    }

    public function byMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tokens WHERE member_id = ? ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$memberId]);
        return $stmt->fetch() ?: null;
    }
}
