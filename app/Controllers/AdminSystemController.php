<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Middleware;
use App\Core\Queue;
use App\Models\Election;
use App\Models\Member;
use App\Models\Vote;
use App\Services\ActivityLogService;

/**
 * Admin → Sistem işlemleri (status / log integrity / hash export / KVKK anonymize).
 *
 * Eskiden AdminController içindeydi; FAZ 2.6 god-controller split kapsamında ayrıldı.
 */
class AdminSystemController extends Controller
{
    public function status(): void
    {
        Middleware::requireAuth('admin');

        $dbConnected = false;
        try {
            $db = Database::getInstance();
            $db->prepare('SELECT 1')->execute();
            $dbConnected = true;
        } catch (\Throwable) { /* sessiz */ }

        $electionModel = new Election();
        $currentElection = $electionModel->current();

        $uploadPath = defined('PUBLIC_PATH')
            ? PUBLIC_PATH . '/uploads'
            : ($_SERVER['DOCUMENT_ROOT'] . '/uploads');
        $diskFree = @disk_free_space($uploadPath);
        $diskFreeHuman = $diskFree !== false ? round($diskFree / 1_073_741_824, 2) . ' GB' : 'N/A';

        $queueStats = [];
        try { $queueStats = Queue::stats(); } catch (\Throwable) { /* sessiz */ }

        $this->json([
            'db_connected'    => $dbConnected,
            'php_version'     => PHP_VERSION,
            'sms_mock'        => (bool) ($_ENV['SMS_MOCK'] ?? false),
            'disk_free'       => $diskFreeHuman,
            'election_status' => $currentElection['status'] ?? 'none',
            'election_title'  => $currentElection['title']  ?? null,
            'queue'           => $queueStats,
        ]);
    }

    public function verifyLogIntegrity(): void
    {
        Middleware::requireAuth('admin');
        $result = ActivityLogService::verifyChain();
        if (($this->input('format') ?? '') === 'json') {
            $this->json($result);
            return;
        }
        $this->layout('main', 'admin.log_integrity', [
            'pageTitle' => 'Audit Log Bütünlük Denetimi',
            'result'    => $result,
        ]);
    }

    public function anonymizeOldData(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $retentionDays = (int) ($_ENV['PII_RETENTION_DAYS'] ?? 365);
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, title FROM elections
             WHERE status = 'closed' AND closed_at IS NOT NULL
               AND closed_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$retentionDays]);
        $electionsToAnonymize = $stmt->fetchAll();

        $memberModel = new Member();
        $totalAnonymized = 0;
        $details = [];

        foreach ($electionsToAnonymize as $el) {
            $count = $memberModel->anonymizeForElection((int) $el['id']);
            $totalAnonymized += $count;
            if ($count > 0) {
                $details[] = ['id' => $el['id'], 'title' => $el['title'], 'count' => $count];
                ActivityLogService::log(
                    'pii_anonymized',
                    "KVKK retention: {$count} üye anonimleştirildi (seçim #{$el['id']})",
                    (int) $el['id']
                );
            }
        }

        $this->json([
            'success'             => true,
            'retention_days'      => $retentionDays,
            'elections_processed' => count($electionsToAnonymize),
            'members_anonymized'  => $totalAnonymized,
            'details'             => $details,
        ]);
    }

    public function hashExport(): void
    {
        Middleware::requireAuth('admin');

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            $current = (new Election())->current();
            $electionId = $current ? (int) $current['id'] : null;
        }

        $hashes = [];
        if ($electionId) {
            $hashes = (new Vote())->allHashes($electionId);
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="hash_export_' . date('Ymd_His') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel uyumu)
        fputcsv($out, ['Kurul', 'Commitment Hash', 'Oy Zamanı']);
        foreach ($hashes as $row) {
            fputcsv($out, [$row['ballot_title'], $row['commitment_hash'], $row['created_at']]);
        }
        fclose($out);
        exit;
    }
}
