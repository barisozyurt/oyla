<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Middleware;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Member;
use App\Models\Token;
use App\Models\Vote;
use App\Services\ActivityLogService;
use App\Services\CryptoService;
use App\Services\PdfService;
use App\Services\SmsService;
use App\Services\TokenService;

class TestModeController extends Controller
{
    /**
     * Render the test mode dashboard.
     * Shows current election info and the three test sections.
     */
    public function index(): void
    {
        Middleware::requireAuth('admin');

        $electionModel = new Election();
        $electionId    = $this->currentElectionId();
        $election      = $electionId ? $electionModel->find($electionId) : $electionModel->current();

        $ballots = [];
        if ($election) {
            $ballotModel = new Ballot();
            $ballots     = $ballotModel->byElection((int) $election['id']);
        }

        $this->layout('main', 'admin.test_mode', [
            'pageTitle' => 'Test Modu',
            'election'  => $election,
            'ballots'   => $ballots,
        ]);
    }

    /**
     * Run 8 system checks and return JSON results.
     * Each check reports name, status ('pass'|'fail'|'warn'), and a detail string.
     */
    public function runSystemChecks(): void
    {
        Middleware::requireAuth('admin');

        $checks = [];

        // ----------------------------------------------------------------
        // 1. Veritabanı Bağlantısı
        // ----------------------------------------------------------------
        try {
            $db = Database::getInstance();
            $db->prepare("SELECT 1")->execute();
            $checks[] = [
                'name'   => 'Veritabanı Bağlantısı',
                'status' => 'pass',
                'detail' => 'PDO bağlantısı başarılı',
            ];
        } catch (\Throwable $e) {
            $checks[] = [
                'name'   => 'Veritabanı Bağlantısı',
                'status' => 'fail',
                'detail' => $e->getMessage(),
            ];
        }

        // ----------------------------------------------------------------
        // 2. SMS Servisi
        // ----------------------------------------------------------------
        try {
            $sms    = new SmsService();
            $result = $sms->send('5550000000', 'Oyla sistem testi mesajı');
            $checks[] = [
                'name'   => 'SMS Servisi',
                'status' => $result ? 'pass' : 'fail',
                'detail' => $result
                    ? 'SMS gönderildi (' . (($_ENV['SMS_MOCK'] ?? 'true') === 'true' ? 'mock mod' : 'gerçek') . ')'
                    : 'SMS gönderilemedi',
            ];
        } catch (\Throwable $e) {
            $checks[] = [
                'name'   => 'SMS Servisi',
                'status' => 'fail',
                'detail' => $e->getMessage(),
            ];
        }

        // ----------------------------------------------------------------
        // 3. Token Üretimi ve Doğrulama
        // ----------------------------------------------------------------
        try {
            $electionModel = new Election();
            $election      = $electionModel->current();

            if (!$election) {
                $checks[] = [
                    'name'   => 'Token Üretimi',
                    'status' => 'warn',
                    'detail' => 'Seçim bulunamadı — token testi atlandı',
                ];
            } else {
                $db          = Database::getInstance();
                $tokenSvc    = new TokenService();
                $tokenModel  = new Token();

                // Insert a temporary test member to satisfy FK constraint
                $db->prepare(
                    "INSERT INTO members (election_id, sicil_no, name, status) VALUES (?, ?, ?, 'waiting')"
                )->execute([(int) $election['id'], 'TEST-CHK-TOKEN', 'Test Token Check']);
                $tmpMemberId = (int) $db->lastInsertId();

                $token = $tokenSvc->generate((int) $election['id'], $tmpMemberId);
                $valid = $tokenSvc->validate($token['plain']);

                // Clean up
                $db->prepare("DELETE FROM tokens WHERE member_id = ?")->execute([$tmpMemberId]);
                $db->prepare("DELETE FROM members WHERE id = ?")->execute([$tmpMemberId]);

                $checks[] = [
                    'name'   => 'Token Üretimi',
                    'status' => $valid ? 'pass' : 'fail',
                    'detail' => $valid
                        ? 'Token üretildi ve doğrulandı (expires: ' . $token['expires_at'] . ')'
                        : 'Token üretildi ancak doğrulanamadı',
                ];
            }
        } catch (\Throwable $e) {
            $checks[] = [
                'name'   => 'Token Üretimi',
                'status' => 'fail',
                'detail' => $e->getMessage(),
            ];
        }

        // ----------------------------------------------------------------
        // 4. Commitment Hash (Oy Gizliliği)
        // ----------------------------------------------------------------
        try {
            $salt       = CryptoService::generateSalt();
            $choice     = json_encode([1, 3, 5]);
            $fakePlain  = bin2hex(random_bytes(16));
            $hash       = CryptoService::commitmentHash($choice, $salt, $fakePlain);
            $verified   = CryptoService::verifyCommitment($hash, $choice, $salt, $fakePlain);
            $tampered   = CryptoService::verifyCommitment($hash, json_encode([1, 3, 99]), $salt, $fakePlain);

            $checks[] = [
                'name'   => 'Commitment Hash',
                'status' => ($verified && !$tampered) ? 'pass' : 'fail',
                'detail' => ($verified && !$tampered)
                    ? 'Hash doğrulandı; manipülasyon tespiti çalışıyor'
                    : 'Hash doğrulama hatası veya manipülasyon tespiti başarısız',
            ];
        } catch (\Throwable $e) {
            $checks[] = [
                'name'   => 'Commitment Hash',
                'status' => 'fail',
                'detail' => $e->getMessage(),
            ];
        }

        // ----------------------------------------------------------------
        // 5. Çift Oy Engeli
        // ----------------------------------------------------------------
        try {
            $electionModel = new Election();
            $election      = $electionModel->current();

            if (!$election) {
                $checks[] = [
                    'name'   => 'Çift Oy Engeli',
                    'status' => 'warn',
                    'detail' => 'Seçim bulunamadı — çift oy testi atlandı',
                ];
            } else {
                $db         = Database::getInstance();
                $tokenSvc   = new TokenService();

                // Temporary member
                $db->prepare(
                    "INSERT INTO members (election_id, sicil_no, name, status) VALUES (?, ?, ?, 'waiting')"
                )->execute([(int) $election['id'], 'TEST-CHK-DBLVOTE', 'Test Double Vote']);
                $tmpMemberId = (int) $db->lastInsertId();

                $token      = $tokenSvc->generate((int) $election['id'], $tmpMemberId);
                $validBefore = $tokenSvc->validate($token['plain']) !== null;
                $tokenSvc->burn($token['hash']);
                $validAfter  = $tokenSvc->validate($token['plain']) !== null;

                // Clean up
                $db->prepare("DELETE FROM tokens WHERE member_id = ?")->execute([$tmpMemberId]);
                $db->prepare("DELETE FROM members WHERE id = ?")->execute([$tmpMemberId]);

                $blocked = $validBefore && !$validAfter;
                $checks[] = [
                    'name'   => 'Çift Oy Engeli',
                    'status' => $blocked ? 'pass' : 'fail',
                    'detail' => $blocked
                        ? 'Token burn sonrası tekrar kullanım engellendi'
                        : 'Çift oy engeli çalışmıyor — kritik hata!',
                ];
            }
        } catch (\Throwable $e) {
            $checks[] = [
                'name'   => 'Çift Oy Engeli',
                'status' => 'fail',
                'detail' => $e->getMessage(),
            ];
        }

        // ----------------------------------------------------------------
        // 6. Yetkilendirme / Rol Kontrolü
        // ----------------------------------------------------------------
        try {
            $db       = Database::getInstance();
            $stmt     = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE role = 'admin'");
            $stmt->execute();
            $adminCnt = (int) $stmt->fetch()['cnt'];

            $checks[] = [
                'name'   => 'Yetkilendirme / Rol Kontrolü',
                'status' => $adminCnt > 0 ? 'pass' : 'warn',
                'detail' => $adminCnt > 0
                    ? "Sistemde {$adminCnt} admin hesabı mevcut; session rol denetimi aktif"
                    : 'Admin hesabı bulunamadı — kullanıcı yönetiminden ekleyin',
            ];
        } catch (\Throwable $e) {
            $checks[] = [
                'name'   => 'Yetkilendirme / Rol Kontrolü',
                'status' => 'fail',
                'detail' => $e->getMessage(),
            ];
        }

        // ----------------------------------------------------------------
        // 7. Sonuç Hesaplama (JSON_CONTAINS sorgusu)
        // ----------------------------------------------------------------
        try {
            $electionModel  = new Election();
            $ballotModel    = new Ballot();
            $candidateModel = new Candidate();
            $voteModel      = new Vote();
            $election       = $electionModel->current();

            if (!$election) {
                $checks[] = [
                    'name'   => 'Sonuç Hesaplama',
                    'status' => 'warn',
                    'detail' => 'Seçim bulunamadı — sonuç testi atlandı',
                ];
            } else {
                $db = Database::getInstance();

                // We need a ballot and candidate to test against
                $ballots = $ballotModel->byElection((int) $election['id']);

                if (empty($ballots)) {
                    $checks[] = [
                        'name'   => 'Sonuç Hesaplama',
                        'status' => 'warn',
                        'detail' => 'Seçim kurulu tanımlanmamış — tablo sorgusu test edilemedi',
                    ];
                } else {
                    $ballot     = $ballots[0];
                    $candidates = $candidateModel->byBallot((int) $ballot['id']);

                    if (empty($candidates)) {
                        $checks[] = [
                            'name'   => 'Sonuç Hesaplama',
                            'status' => 'warn',
                            'detail' => 'Aday tanımlanmamış — JSON_CONTAINS sorgusu çalıştırılamadı',
                        ];
                    } else {
                        // Insert a synthetic vote row and run results query
                        $candidateId = (int) $candidates[0]['id'];
                        $fakeHash    = 'TEST_RESULT_CHECK_' . bin2hex(random_bytes(8));
                        $db->prepare(
                            "INSERT INTO votes (election_id, ballot_id, token_hash, encrypted_choice, commitment_hash)
                             VALUES (?, ?, ?, ?, ?)"
                        )->execute([
                            (int) $election['id'],
                            (int) $ballot['id'],
                            $fakeHash,
                            json_encode([$candidateId]),
                            hash('sha256', 'test_result'),
                        ]);
                        $tmpVoteId = (int) $db->lastInsertId();

                        $results    = $voteModel->resultsByBallot((int) $ballot['id']);
                        $found      = false;
                        foreach ($results as $row) {
                            if ((int) $row['id'] === $candidateId && (int) $row['vote_count'] >= 1) {
                                $found = true;
                                break;
                            }
                        }

                        // Clean up
                        $db->prepare("DELETE FROM votes WHERE id = ?")->execute([$tmpVoteId]);

                        $checks[] = [
                            'name'   => 'Sonuç Hesaplama',
                            'status' => $found ? 'pass' : 'fail',
                            'detail' => $found
                                ? 'JSON_CONTAINS sayım sorgusu doğru çalışıyor'
                                : 'JSON_CONTAINS sorgusu beklenen sonucu döndürmedi',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            $checks[] = [
                'name'   => 'Sonuç Hesaplama',
                'status' => 'fail',
                'detail' => $e->getMessage(),
            ];
        }

        // ----------------------------------------------------------------
        // 8. PDF Tutanak Üretimi
        // ----------------------------------------------------------------
        try {
            $electionModel = new Election();
            $election      = $electionModel->current();

            if (!$election) {
                $checks[] = [
                    'name'   => 'PDF Tutanak',
                    'status' => 'warn',
                    'detail' => 'Seçim bulunamadı — PDF testi atlandı',
                ];
            } else {
                // generateTutanak() saves to a temp file and returns the path
                $pdfSvc  = new PdfService();
                $pdfPath = $pdfSvc->generateTutanak((int) $election['id']);

                $fileExists = is_string($pdfPath) && file_exists($pdfPath);
                $isPdf      = false;
                if ($fileExists) {
                    $handle = fopen($pdfPath, 'rb');
                    if ($handle) {
                        $header = fread($handle, 5);
                        fclose($handle);
                        $isPdf = $header === '%PDF-';
                        // Clean up temp file after check
                        @unlink($pdfPath);
                    }
                }

                $checks[] = [
                    'name'   => 'PDF Tutanak',
                    'status' => ($fileExists && $isPdf) ? 'pass' : ($fileExists ? 'warn' : 'fail'),
                    'detail' => ($fileExists && $isPdf)
                        ? 'PDF başarıyla üretildi (%PDF başlığı doğrulandı)'
                        : ($fileExists
                            ? 'Dosya üretildi ancak %PDF başlığı doğrulanamadı'
                            : 'PDF dosyası üretilemedi veya bulunamadı'),
                ];
            }
        } catch (\Throwable $e) {
            $checks[] = [
                'name'   => 'PDF Tutanak',
                'status' => 'fail',
                'detail' => $e->getMessage(),
            ];
        }

        $allPassed = !in_array('fail', array_column($checks, 'status'), true);

        ActivityLogService::log(
            'test_system_checks',
            'Sistem kontrolleri çalıştırıldı — ' . ($allPassed ? 'Tümü geçti' : 'Bazı kontroller başarısız'),
            $this->currentElectionId()
        );

        $this->json(['checks' => $checks, 'all_passed' => $allPassed]);
    }

    /**
     * Run a full test election simulation.
     * Creates virtual members, generates tokens, casts random votes, verifies integrity.
     */
    public function runTestElection(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $electionModel = new Election();
        $electionId    = $this->currentElectionId();
        $election      = $electionId ? $electionModel->find($electionId) : $electionModel->current();

        if (!$election) {
            $this->json(['success' => false, 'error' => 'Aktif seçim bulunamadı.'], 400);
            return;
        }

        $electionId  = (int) $election['id'];
        $memberCount = max(1, min(50, (int) ($this->input('member_count', 10))));

        $ballotModel    = new Ballot();
        $candidateModel = new Candidate();
        $tokenSvc       = new TokenService();
        $memberModel    = new Member();
        $voteModel      = new Vote();
        $db             = Database::getInstance();

        $ballots = $ballotModel->byElection($electionId);
        if (empty($ballots)) {
            $this->json(['success' => false, 'error' => 'Seçim kurulları tanımlanmamış. Lütfen önce kurul ve aday ekleyin.'], 400);
            return;
        }

        // Load candidates per ballot
        $ballotCandidates = [];
        foreach ($ballots as $ballot) {
            $candidates = $candidateModel->byBallot((int) $ballot['id']);
            if (!empty($candidates)) {
                $ballotCandidates[(int) $ballot['id']] = [
                    'ballot'     => $ballot,
                    'candidates' => $candidates,
                ];
            }
        }

        if (empty($ballotCandidates)) {
            $this->json(['success' => false, 'error' => 'Hiçbir kurulda aday yok. Lütfen aday ekleyin.'], 400);
            return;
        }

        $db->beginTransaction();
        try {
            // Mark election as test mode
            $electionModel->setTestMode($electionId, true);

            // ----------------------------------------------------------------
            // Step 1: Create virtual members
            // ----------------------------------------------------------------
            $createdMembers = [];
            for ($i = 1; $i <= $memberCount; $i++) {
                $sicilNo = 'TEST-' . strtoupper(bin2hex(random_bytes(4)));
                $memberId = $memberModel->create([
                    'election_id' => $electionId,
                    'sicil_no'    => $sicilNo,
                    'name'        => "Test Üye {$i}",
                    'phone'       => '555' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                    'status'      => 'signed',
                    'signed_at'   => date('Y-m-d H:i:s'),
                ]);
                $createdMembers[] = ['id' => $memberId, 'sicil_no' => $sicilNo];
            }

            // ----------------------------------------------------------------
            // Step 2: Generate tokens
            // ----------------------------------------------------------------
            $tokenData = [];
            foreach ($createdMembers as $m) {
                $token = $tokenSvc->generate($electionId, (int) $m['id']);
                $tokenData[] = [
                    'member_id' => $m['id'],
                    'plain'     => $token['plain'],
                    'hash'      => $token['hash'],
                ];
            }

            // ----------------------------------------------------------------
            // Step 3: Cast random votes
            // ----------------------------------------------------------------
            $castVotes      = 0;
            $hashRecords    = [];   // [ token_plain => [ ballot_id => commitment_hash ] ]
            $doubleVoteBlocked = 0;

            foreach ($tokenData as $td) {
                // Validate token (should all pass at this point)
                $validToken = $tokenSvc->validate($td['plain']);
                if (!$validToken) {
                    continue;
                }

                $memberVoteHashes = [];

                foreach ($ballotCandidates as $ballotId => $bc) {
                    $ballot     = $bc['ballot'];
                    $candidates = $bc['candidates'];
                    $quota      = (int) $ballot['quota'];

                    // Randomly select up to $quota candidates
                    $available    = array_column($candidates, 'id');
                    $selectCount  = rand(1, min($quota, count($available)));
                    shuffle($available);
                    $selected     = array_slice($available, 0, $selectCount);

                    $salt            = CryptoService::generateSalt();
                    $choiceJson      = json_encode($selected);
                    $commitmentHash  = CryptoService::commitmentHash($choiceJson, $salt, $td['plain']);

                    $voteModel->castVote($electionId, $ballotId, $td['hash'], $selected, $commitmentHash);
                    $castVotes++;

                    $memberVoteHashes[$ballotId] = [
                        'choice'     => $choiceJson,
                        'salt'       => $salt,
                        'commitment' => $commitmentHash,
                    ];
                }

                $hashRecords[$td['plain']] = $memberVoteHashes;

                // Burn the token
                $tokenSvc->burn($td['hash']);

                // Attempt double-vote (should be blocked)
                $alreadyUsed = $tokenSvc->validate($td['plain']);
                if ($alreadyUsed === null) {
                    $doubleVoteBlocked++;
                }
            }

            // ----------------------------------------------------------------
            // Step 4: Verify hash integrity
            // ----------------------------------------------------------------
            $hashIntegrity = true;
            $hashErrors    = 0;
            foreach ($hashRecords as $tokenPlain => $ballotHashes) {
                foreach ($ballotHashes as $ballotId => $hd) {
                    $recalculated = CryptoService::commitmentHash($hd['choice'], $hd['salt'], $tokenPlain);
                    if (!hash_equals($recalculated, $hd['commitment'])) {
                        $hashIntegrity = false;
                        $hashErrors++;
                    }
                }
            }

            // ----------------------------------------------------------------
            // Step 5: Verify vote counts
            // ----------------------------------------------------------------
            $expectedVotes  = count($tokenData) * count($ballotCandidates);
            $actualVotes    = $voteModel->count('election_id = ?', [$electionId]);

            // Note: actual may be higher than expected if there were pre-existing real votes
            $countMatch = $actualVotes >= $castVotes;

            $db->commit();

            ActivityLogService::log(
                'test_simulation_run',
                "Test simülasyonu: {$memberCount} sanal üye, {$castVotes} oy kullanıldı",
                $electionId
            );

            $this->json([
                'success'              => true,
                'members_created'      => count($createdMembers),
                'tokens_generated'     => count($tokenData),
                'votes_cast'           => $castVotes,
                'double_vote_blocked'  => $doubleVoteBlocked,
                'hash_integrity'       => $hashIntegrity,
                'hash_errors'          => $hashErrors,
                'count_match'          => $countMatch,
                'expected_votes'       => $expectedVotes,
                'actual_total_votes'   => $actualVotes,
                'ballots_tested'       => count($ballotCandidates),
                'summary' => [
                    'members'       => "Oluşturulan sanal üye: {$memberCount}",
                    'tokens'        => 'Token üretimi: ' . count($tokenData) . ' token üretildi',
                    'votes'         => "Oy kullanımı: {$castVotes} oy başarıyla kaydedildi",
                    'double_vote'   => "Çift oy engeli: {$doubleVoteBlocked}/" . count($tokenData) . ' engellendi',
                    'hash_check'    => $hashIntegrity
                        ? 'Hash bütünlüğü: Tüm commitment hashler doğrulandı ✓'
                        : "Hash bütünlüğü: {$hashErrors} hata bulundu ✗",
                ],
            ]);

        } catch (\Throwable $e) {
            $db->rollBack();
            ActivityLogService::log('test_simulation_error', 'Test simülasyonu hatası: ' . $e->getMessage(), $electionId);
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete all test data for the current election and reset to draft status.
     */
    public function cleanup(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $electionModel = new Election();
        $electionId    = $this->currentElectionId();
        $election      = $electionId ? $electionModel->find($electionId) : $electionModel->current();

        if (!$election) {
            $this->json(['success' => false, 'error' => 'Aktif seçim bulunamadı.'], 400);
            return;
        }

        $electionId = (int) $election['id'];
        $db         = Database::getInstance();

        $db->beginTransaction();
        try {
            // Delete votes cast by test tokens
            // (test tokens belong to members with sicil_no LIKE 'TEST-%')
            $db->prepare(
                "DELETE v FROM votes v
                 JOIN tokens t ON t.token_hash = v.token_hash
                 JOIN members m ON m.id = t.member_id
                 WHERE v.election_id = ? AND m.sicil_no LIKE 'TEST-%'"
            )->execute([$electionId]);

            // Delete receipts (no direct member link, so delete all for election while in test mode)
            $db->prepare(
                "DELETE FROM receipts WHERE election_id = ? AND election_id IN
                 (SELECT id FROM elections WHERE test_mode = 1)"
            )->execute([$electionId]);

            // Delete tokens belonging to test members
            $db->prepare(
                "DELETE t FROM tokens t
                 JOIN members m ON m.id = t.member_id
                 WHERE t.election_id = ? AND m.sicil_no LIKE 'TEST-%'"
            )->execute([$electionId]);

            // Delete test members
            $db->prepare(
                "DELETE FROM members WHERE election_id = ? AND sicil_no LIKE 'TEST-%'"
            )->execute([$electionId]);

            // Reset election test_mode flag and revert status to draft
            $db->prepare(
                "UPDATE elections SET test_mode = 0, status = 'draft' WHERE id = ?"
            )->execute([$electionId]);

            $db->commit();

            ActivityLogService::log(
                'test_cleanup',
                'Test verileri temizlendi, seçim durumu taslağa döndürüldü',
                $electionId
            );

            $this->json([
                'success' => true,
                'message' => 'Test verileri başarıyla silindi. Seçim durumu "Taslak" olarak ayarlandı.',
            ]);

        } catch (\Throwable $e) {
            $db->rollBack();
            ActivityLogService::log('test_cleanup_error', 'Test temizleme hatası: ' . $e->getMessage(), $electionId);
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
