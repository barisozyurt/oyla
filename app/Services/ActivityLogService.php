<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

/**
 * Tamper-evident activity log (FAZ 1).
 *
 * Her satır önceki satırın `entry_hash`'ini içerir. Hash şu girdilerle hesaplanır:
 *   HMAC(APP_SECRET, prev_hash || action || description || ip || actor || timestamp)
 *
 * Bir satır silinir veya değiştirilirse, kendisinden sonraki TÜM hash'ler bozulur.
 * `AdminController::verifyLogIntegrity()` zincir bütünlüğünü doğrular.
 */
class ActivityLogService
{
    /**
     * Bir olayı activity_log tablosuna tamper-evident olarak yazar.
     */
    public static function log(string $action, string $description, ?int $electionId = null): void
    {
        $db = Database::getInstance();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $actor = $_SESSION['user']['username'] ?? null;

        // Önceki entry hash'ini al (zincirin başı için null)
        $stmt = $db->prepare("SELECT entry_hash FROM activity_log ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $prev = $stmt->fetch();
        $prevHash = $prev['entry_hash'] ?? null;

        $now = date('Y-m-d H:i:s');
        $entryHash = self::computeEntryHash($prevHash, $action, $description, $ip, $actor, $now);

        $stmt = $db->prepare(
            "INSERT INTO activity_log
             (election_id, action, description, ip_address, prev_hash, entry_hash, actor_username, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $electionId,
            $action,
            $description,
            $ip,
            $prevHash,
            $entryHash,
            $actor,
            $now,
        ]);
    }

    /**
     * Zincir bütünlüğünü doğrular.
     * Hatalı satırların ID'lerini ve hangi alanın bozulduğunu döndürür.
     *
     * @return array{ok:bool, total:int, broken:array<int,array{id:int,reason:string}>}
     */
    public static function verifyChain(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id, election_id, action, description, ip_address, prev_hash, entry_hash, actor_username, created_at
             FROM activity_log ORDER BY id ASC"
        );
        $rows = $stmt->fetchAll();

        $broken = [];
        $lastHash = null;

        foreach ($rows as $row) {
            $expectedPrev = $lastHash;
            if (($row['prev_hash'] ?? null) !== $expectedPrev) {
                $broken[] = ['id' => (int) $row['id'], 'reason' => 'prev_hash mismatch'];
            }
            $recomputed = self::computeEntryHash(
                $row['prev_hash'],
                $row['action'],
                (string) ($row['description'] ?? ''),
                (string) ($row['ip_address'] ?? ''),
                $row['actor_username'] ?? null,
                $row['created_at']
            );
            if (!hash_equals((string) $row['entry_hash'], $recomputed)) {
                $broken[] = ['id' => (int) $row['id'], 'reason' => 'entry_hash mismatch'];
            }
            $lastHash = $row['entry_hash'];
        }

        return [
            'ok'     => empty($broken),
            'total'  => count($rows),
            'broken' => $broken,
        ];
    }

    private static function computeEntryHash(
        ?string $prevHash,
        string $action,
        string $description,
        string $ip,
        ?string $actor,
        string $createdAt
    ): string {
        $secret = Config::secret('APP_SECRET', 32);
        $payload = implode('|', [
            $prevHash ?? '',
            $action,
            $description,
            $ip,
            $actor ?? '',
            $createdAt,
        ]);
        return hash_hmac('sha256', $payload, $secret);
    }
}
