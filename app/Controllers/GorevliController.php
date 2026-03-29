<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\Election;
use App\Models\Member;
use App\Models\Token;
use App\Services\ActivityLogService;
use App\Services\QrService;
use App\Services\SmsService;
use App\Services\TokenService;

/**
 * GorevliController — Kayıt görevlisi masası.
 *
 * KURAL: Görevli, üyenin oyunun içeriğini HİÇBİR ZAMAN göremez.
 * Yalnızca üye durumu (waiting/signed/done) ve token kullanım bilgisi görünür.
 */
class GorevliController extends Controller
{
    /**
     * Görevli masası ana sayfası.
     */
    public function index(): void
    {
        $user = Middleware::requireAuth('admin', 'gorevli');

        $electionId = $this->currentElectionId();
        $election   = $electionId ? (new Election())->find($electionId) : null;

        $memberModel = new Member();
        $members     = $electionId ? $memberModel->byElection($electionId) : [];

        $stats = [
            'total'  => count($members),
            'waiting' => $electionId ? $memberModel->countByStatus($electionId, 'waiting') : 0,
            'signed'  => $electionId ? $memberModel->countByStatus($electionId, 'signed')  : 0,
            'done'    => $electionId ? $memberModel->countByStatus($electionId, 'done')     : 0,
        ];

        $this->layout('main', 'gorevli.index', [
            'pageTitle' => 'Görevli Masası',
            'election'  => $election,
            'members'   => $members,
            'stats'     => $stats,
            'user'      => $user,
            'csrf'      => $this->csrfField(),
        ]);
    }

    /**
     * TC kimlik / sicil no / isim araması.
     * Dönen üye verisinde hassas alanlar maskelenir.
     */
    public function search(): void
    {
        Middleware::requireAuth('admin', 'gorevli');
        $this->verifyCsrf();

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            $this->json(['error' => 'Aktif seçim bulunamadı'], 400);
            return;
        }

        $query = trim($this->input('query', ''));
        if ($query === '') {
            $this->json(['error' => 'Arama terimi gerekli'], 400);
            return;
        }

        $memberModel = new Member();

        // TC tam eşleşme → sicil tam eşleşme → isim arama
        $member = $memberModel->findByTc($electionId, $query)
            ?? $memberModel->findBySicil($electionId, $query);

        if (!$member) {
            $results = $memberModel->search($electionId, $query);
            $member  = $results[0] ?? null;
        }

        if (!$member) {
            $this->json(['found' => false, 'message' => 'Üye bulunamadı']);
            return;
        }

        // Token durumu — sadece used/expires bilgisi, oy içeriği değil
        $tokenModel     = new Token();
        $token          = $tokenModel->byMember((int) $member['id']);
        $hasActiveToken = $token
            && !(bool) $token['used']
            && strtotime($token['expires_at']) > time();

        // TC maskeleme: 12345678901 → 123****01
        $tcMasked = null;
        if (!empty($member['tc_kimlik'])) {
            $tc = $member['tc_kimlik'];
            $tcMasked = substr($tc, 0, 3) . '****' . substr($tc, -2);
        }

        $this->json([
            'found'  => true,
            'member' => [
                'id'              => $member['id'],
                'name'            => $member['name'],
                'tc_kimlik'       => $tcMasked,
                'sicil_no'        => $member['sicil_no'],
                'phone'           => $member['phone']
                    ? preg_replace('/(\d{4})\d{3}(\d{4})/', '$1***$2', $member['phone'])
                    : null,
                'photo_path'      => $member['photo_path'],
                'status'          => $member['status'],
                'has_active_token' => $hasActiveToken,
            ],
        ]);
    }

    /**
     * 1. imza: kimlik doğrulandı, üye status → signed.
     */
    public function firstSign(string $id): void
    {
        Middleware::requireAuth('admin', 'gorevli');
        $this->verifyCsrf();

        $electionId  = $this->currentElectionId();
        $memberModel = new Member();
        $member      = $memberModel->find((int) $id);

        if (!$member || (int) $member['election_id'] !== $electionId) {
            $this->json(['error' => 'Üye bulunamadı'], 404);
            return;
        }

        if ($member['status'] !== 'waiting') {
            $this->json(['error' => 'Bu üye zaten işlem görmüş (durum: ' . $member['status'] . ')'], 400);
            return;
        }

        $memberModel->updateStatus((int) $id, 'signed');

        $user = $this->currentUser();
        ActivityLogService::log(
            'first_sign',
            "1. imza alındı: {$member['name']} (Görevli: {$user['name']})",
            $electionId
        );

        $this->json(['success' => true, 'status' => 'signed']);
    }

    /**
     * Token üret + SMS gönder + QR kod döndür.
     */
    public function generateToken(string $id): void
    {
        Middleware::requireAuth('admin', 'gorevli');
        $this->verifyCsrf();

        $electionId  = $this->currentElectionId();
        $memberModel = new Member();
        $member      = $memberModel->find((int) $id);

        if (!$member || (int) $member['election_id'] !== $electionId) {
            $this->json(['error' => 'Üye bulunamadı'], 404);
            return;
        }

        if ($member['status'] !== 'signed') {
            $this->json(['error' => 'Önce 1. imza alınmalıdır (mevcut durum: ' . $member['status'] . ')'], 400);
            return;
        }

        $election = (new Election())->find($electionId);
        if (!$election || $election['status'] !== 'open') {
            $this->json(['error' => 'Seçim şu anda aktif değil'], 400);
            return;
        }

        // Daha önce geçerli token varsa yenisini üretme
        $tokenModel     = new Token();
        $existingToken  = $tokenModel->byMember((int) $id);
        if ($existingToken && !(bool) $existingToken['used'] && strtotime($existingToken['expires_at']) > time()) {
            // Mevcut token hâlâ geçerli — aynı URL'yi döndür
            $appUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
            $voteUrl = $appUrl . '/oy/' . $existingToken['token_plain'];
            $qrDataUri = QrService::generate($voteUrl);

            $this->json([
                'success'    => true,
                'vote_url'   => $voteUrl,
                'qr_data_uri' => $qrDataUri,
                'expires_at' => $existingToken['expires_at'],
                'reused'     => true,
            ]);
            return;
        }

        // Yeni token üret
        $tokenService = new TokenService();
        $tokenData    = $tokenService->generate($electionId, (int) $id);

        $appUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
        $voteUrl = $appUrl . '/oy/' . $tokenData['plain'];

        // QR kod (data URI)
        $qrDataUri = QrService::generate($voteUrl);

        // SMS gönder (telefon varsa)
        if (!empty($member['phone'])) {
            $sms = new SmsService();
            $sms->send(
                $member['phone'],
                "Oyla oy kullanma bağlantınız: {$voteUrl} — Geçerlilik: 2 saat"
            );
        }

        $user = $this->currentUser();
        ActivityLogService::log(
            'token_generated',
            "Token üretildi: {$member['name']} (Görevli: {$user['name']})",
            $electionId
        );

        $this->json([
            'success'     => true,
            'vote_url'    => $voteUrl,
            'qr_data_uri' => $qrDataUri,
            'expires_at'  => $tokenData['expires_at'],
            'reused'      => false,
        ]);
    }

    /**
     * Oy kullanım durumu sorgula (polling endpoint).
     * KURAL: Sadece token.used kontrolü — oy içeriği asla görünmez.
     */
    public function checkVoteStatus(string $id): void
    {
        Middleware::requireAuth('admin', 'gorevli');

        $memberModel = new Member();
        $member      = $memberModel->find((int) $id);

        if (!$member) {
            $this->json(['error' => 'Üye bulunamadı'], 404);
            return;
        }

        // Sadece token kullanım durumu — oy içeriği HİÇ okunmaz
        $tokenModel = new Token();
        $token      = $tokenModel->byMember((int) $id);
        $voted      = $token && (bool) $token['used'];

        $this->json([
            'voted'         => $voted,
            'member_status' => $member['status'],
        ]);
    }

    /**
     * 2. imza: oy kullanıldı, işlem tamamlandı → status → done.
     */
    public function secondSign(string $id): void
    {
        Middleware::requireAuth('admin', 'gorevli');
        $this->verifyCsrf();

        $electionId  = $this->currentElectionId();
        $memberModel = new Member();
        $member      = $memberModel->find((int) $id);

        if (!$member || (int) $member['election_id'] !== $electionId) {
            $this->json(['error' => 'Üye bulunamadı'], 404);
            return;
        }

        if ($member['status'] === 'done') {
            $this->json(['success' => true, 'status' => 'done', 'already' => true]);
            return;
        }

        $memberModel->updateStatus((int) $id, 'done');

        $user = $this->currentUser();
        ActivityLogService::log(
            'second_sign',
            "2. imza alındı, işlem tamamlandı: {$member['name']} (Görevli: {$user['name']})",
            $electionId
        );

        $this->json(['success' => true, 'status' => 'done']);
    }

    /**
     * Üye listesi (sidebar polling için JSON endpoint).
     * Yalnızca id, isim, sicil ve status döner — oy bilgisi yok.
     */
    public function memberList(): void
    {
        Middleware::requireAuth('admin', 'gorevli');

        $electionId  = $this->currentElectionId();
        $statusFilter = $this->input('status');

        $memberModel = new Member();
        $allMembers  = $memberModel->byElection($electionId);

        if ($statusFilter && in_array($statusFilter, ['waiting', 'signed', 'done'], true)) {
            $allMembers = array_values(
                array_filter($allMembers, fn($m) => $m['status'] === $statusFilter)
            );
        }

        // Return only the fields the desk UI needs — no vote content
        $rows = array_map(fn($m) => [
            'id'       => $m['id'],
            'name'     => $m['name'],
            'sicil_no' => $m['sicil_no'],
            'status'   => $m['status'],
        ], $allMembers);

        $this->json(['members' => $rows]);
    }
}
