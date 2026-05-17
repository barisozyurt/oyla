<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Member extends Model
{
    protected string $table = 'members';

    protected array $sortable = ['id', 'name', 'sicil_no', 'tc_kimlik', 'status', 'created_at', 'signed_at', 'anonymized_at'];

    public function byElection(int $electionId, string $orderBy = 'name ASC'): array
    {
        $order = $this->safeOrderBy($orderBy);
        $stmt = $this->db->prepare(
            "SELECT * FROM members WHERE election_id = ? ORDER BY {$order}"
        );
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function findByTc(int $electionId, string $tc): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM members WHERE election_id = ? AND tc_kimlik = ? LIMIT 1"
        );
        $stmt->execute([$electionId, $tc]);
        return $stmt->fetch() ?: null;
    }

    public function findBySicil(int $electionId, string $sicilNo): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM members WHERE election_id = ? AND sicil_no = ? LIMIT 1"
        );
        $stmt->execute([$electionId, $sicilNo]);
        return $stmt->fetch() ?: null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $data = ['status' => $status];
        if ($status === 'signed') {
            $data['signed_at'] = date('Y-m-d H:i:s');
        }
        $this->update($id, $data);
    }

    public function countByStatus(int $electionId, string $status): int
    {
        return $this->count('election_id = ? AND status = ?', [$electionId, $status]);
    }

    public function search(int $electionId, string $query): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM members WHERE election_id = ? AND (
                tc_kimlik LIKE ? OR sicil_no LIKE ? OR name LIKE ?
            ) ORDER BY name LIMIT 20"
        );
        $like = "%{$query}%";
        $stmt->execute([$electionId, $like, $like, $like]);
        return $stmt->fetchAll();
    }

    /**
     * KVKK uyumu: kapanmış ve retention günleri geçmiş seçimlerin üye PII'larını anonymize et.
     *  - tc_kimlik, phone, email NULL'a düşürülür
     *  - name "Anonim Üye #{id}" olur
     *  - anonymized_at timestamp set edilir
     *
     * Bu işlem GERİ DÖNDÜRÜLEMEZ.
     *
     * @return int Anonymize edilen üye sayısı
     */
    public function anonymizeForElection(int $electionId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE members
             SET tc_kimlik = NULL,
                 phone = NULL,
                 email = NULL,
                 name = CONCAT('Anonim Üye #', id),
                 anonymized_at = NOW()
             WHERE election_id = ?
               AND anonymized_at IS NULL"
        );
        $stmt->execute([$electionId]);
        return $stmt->rowCount();
    }

    public function getAvatarHtml(array $member): string
    {
        if (!empty($member['photo_path']) && defined('PUBLIC_PATH') && file_exists(PUBLIC_PATH . $member['photo_path'])) {
            return '<img src="' . htmlspecialchars($member['photo_path']) . '" class="avatar rounded-circle" width="40" height="40" alt="">';
        }
        return '<svg class="avatar-anon rounded-circle" viewBox="0 0 40 40" width="40" height="40" aria-hidden="true">
            <rect width="40" height="40" rx="20" fill="#E9ECEF"/>
            <circle cx="20" cy="16" r="7" fill="#B4B2A9"/>
            <path d="M6 38c0-7.7 6.3-12 14-12s14 4.3 14 12" fill="#B4B2A9"/>
        </svg>';
    }
}
