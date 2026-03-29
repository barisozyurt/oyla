<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class ActivityLogService
{
    /**
     * Bir olayı activity_log tablosuna yazar.
     *
     * @param string   $action      Kısa eylem kodu (ör. 'login', 'vote_cast')
     * @param string   $description İnsan tarafından okunabilir açıklama
     * @param int|null $electionId  İlgili seçim ID'si (varsa)
     */
    public static function log(string $action, string $description, ?int $electionId = null): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO activity_log (election_id, action, description, ip_address, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $electionId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    }
}
