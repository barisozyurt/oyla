<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\ActivityLog;
use App\Models\Ballot;
use App\Models\Election;
use App\Models\Member;
use App\Models\User;
use App\Models\Vote;
use App\Services\ActivityLogService;

class AdminController extends Controller
{
    /**
     * Admin paneli — genel bakış.
     * İstatistik kartları ve hızlı erişim bağlantıları gösterir.
     */
    public function index(): void
    {
        Middleware::requireAuth('admin');

        $electionModel = new Election();
        $userModel     = new User();
        $memberModel   = new Member();
        $voteModel     = new Vote();

        $allElections   = $electionModel->all('id DESC');
        $totalElections = count($allElections);
        $totalUsers     = count($userModel->all());

        $electionId     = $this->currentElectionId();
        $currentElection = null;
        $totalMembers   = 0;
        $totalVotes     = 0;

        if ($electionId) {
            $currentElection = $electionModel->find($electionId);
            $totalMembers    = $memberModel->count('election_id = ?', [$electionId]);
            $totalVotes      = $voteModel->count('election_id = ?', [$electionId]);
        } else {
            // Fall back to the most recent election
            $currentElection = $electionModel->current();
            if ($currentElection) {
                $eid          = (int) $currentElection['id'];
                $totalMembers = $memberModel->count('election_id = ?', [$eid]);
                $totalVotes   = $voteModel->count('election_id = ?', [$eid]);
            }
        }

        $this->layout('main', 'admin.index', [
            'pageTitle'       => 'Admin Paneli',
            'totalElections'  => $totalElections,
            'totalUsers'      => $totalUsers,
            'totalMembers'    => $totalMembers,
            'totalVotes'      => $totalVotes,
            'currentElection' => $currentElection,
        ]);
    }

    /**
     * Aktivite logu — son 200 kayıt, isteğe bağlı seçim filtresi.
     */
    public function activityLog(): void
    {
        Middleware::requireAuth('admin');

        $logModel      = new ActivityLog();
        $electionModel = new Election();

        $filterElectionId = isset($_GET['election_id']) && $_GET['election_id'] !== ''
            ? (int) $_GET['election_id']
            : null;

        if ($filterElectionId) {
            $logs = $logModel->byElection($filterElectionId, 200);
        } else {
            $logs = $logModel->recent(200);
        }

        $allElections = $electionModel->all('id DESC');

        $this->layout('main', 'admin.activity_log', [
            'pageTitle'        => 'Aktivite Logu',
            'logs'             => $logs,
            'allElections'     => $allElections,
            'filterElectionId' => $filterElectionId,
        ]);
    }

    /**
     * Kullanıcı listesi.
     */
    public function users(): void
    {
        Middleware::requireAuth('admin');

        $userModel = new User();
        $users     = $userModel->all('name ASC');

        $this->layout('main', 'admin.users', [
            'pageTitle' => 'Kullanıcı Yönetimi',
            'users'     => $users,
        ]);
    }

    /**
     * Yeni kullanıcı formu.
     */
    public function createUser(): void
    {
        Middleware::requireAuth('admin');

        $this->layout('main', 'admin.user_form', [
            'pageTitle' => 'Yeni Kullanıcı',
            'user'      => null,
            'errors'    => [],
        ]);
    }

    /**
     * Yeni kullanıcı kaydet.
     */
    public function storeUser(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $userModel = new User();

        $name     = trim($this->input('name', ''));
        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');
        $role     = $this->input('role', '');
        $deskNo   = $this->input('desk_no', null);

        $errors = [];

        if ($name === '') {
            $errors[] = 'Ad alanı zorunludur.';
        }
        if ($username === '') {
            $errors[] = 'Kullanıcı adı zorunludur.';
        } elseif ($userModel->findByUsername($username) !== null) {
            $errors[] = 'Bu kullanıcı adı zaten kullanılıyor.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Şifre en az 6 karakter olmalıdır.';
        }
        if (!in_array($role, ['admin', 'divan_baskani', 'gorevli'], true)) {
            $errors[] = 'Geçerli bir rol seçiniz.';
        }

        if (!empty($errors)) {
            $this->layout('main', 'admin.user_form', [
                'pageTitle' => 'Yeni Kullanıcı',
                'user'      => null,
                'errors'    => $errors,
            ]);
            return;
        }

        $data = [
            'name'      => $name,
            'username'  => $username,
            'password'  => password_hash($password, PASSWORD_BCRYPT),
            'role'      => $role,
            'is_active' => 1,
        ];

        if ($role === 'gorevli' && $deskNo !== null && $deskNo !== '') {
            $data['desk_no'] = (int) $deskNo;
        }

        $userModel->create($data);

        ActivityLogService::log('user_created', "Yeni kullanıcı oluşturuldu: {$username} ({$role})");

        flash('success', "Kullanıcı \"{$name}\" başarıyla oluşturuldu.");
        $this->redirect('/admin/users');
    }

    /**
     * Kullanıcı düzenleme formu.
     */
    public function editUser(string $id): void
    {
        Middleware::requireAuth('admin');

        $userModel = new User();
        $user      = $userModel->find((int) $id);

        if (!$user) {
            flash('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users');
            return;
        }

        $this->layout('main', 'admin.user_form', [
            'pageTitle' => 'Kullanıcı Düzenle',
            'user'      => $user,
            'errors'    => [],
        ]);
    }

    /**
     * Kullanıcı güncelle.
     */
    public function updateUser(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $userModel = new User();
        $user      = $userModel->find((int) $id);

        if (!$user) {
            flash('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users');
            return;
        }

        $name     = trim($this->input('name', ''));
        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');
        $role     = $this->input('role', '');
        $deskNo   = $this->input('desk_no', null);

        $errors = [];

        if ($name === '') {
            $errors[] = 'Ad alanı zorunludur.';
        }
        if ($username === '') {
            $errors[] = 'Kullanıcı adı zorunludur.';
        } else {
            $existing = $userModel->findByUsername($username);
            if ($existing && (int) $existing['id'] !== (int) $id) {
                $errors[] = 'Bu kullanıcı adı zaten kullanılıyor.';
            }
        }
        if ($password !== '' && strlen($password) < 6) {
            $errors[] = 'Şifre en az 6 karakter olmalıdır.';
        }
        if (!in_array($role, ['admin', 'divan_baskani', 'gorevli'], true)) {
            $errors[] = 'Geçerli bir rol seçiniz.';
        }

        if (!empty($errors)) {
            $this->layout('main', 'admin.user_form', [
                'pageTitle' => 'Kullanıcı Düzenle',
                'user'      => $user,
                'errors'    => $errors,
            ]);
            return;
        }

        $data = [
            'name'     => $name,
            'username' => $username,
            'role'     => $role,
        ];

        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        if ($role === 'gorevli' && $deskNo !== null && $deskNo !== '') {
            $data['desk_no'] = (int) $deskNo;
        } else {
            $data['desk_no'] = null;
        }

        $userModel->update((int) $id, $data);

        ActivityLogService::log('user_updated', "Kullanıcı güncellendi: {$username}");

        flash('success', "Kullanıcı \"{$name}\" güncellendi.");
        $this->redirect('/admin/users');
    }

    /**
     * Kullanıcıyı pasife al (soft delete).
     */
    public function deleteUser(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $userModel = new User();
        $user      = $userModel->find((int) $id);

        if (!$user) {
            flash('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users');
            return;
        }

        // Prevent self-deletion
        $currentUser = $this->currentUser();
        if ($currentUser && (int) $currentUser['id'] === (int) $id) {
            flash('error', 'Kendi hesabınızı silemezsiniz.');
            $this->redirect('/admin/users');
            return;
        }

        $userModel->update((int) $id, ['is_active' => 0]);

        ActivityLogService::log('user_deactivated', "Kullanıcı pasife alındı: {$user['username']}");

        flash('success', "Kullanıcı \"{$user['name']}\" pasife alındı.");
        $this->redirect('/admin/users');
    }

    /**
     * Sistem durumu — JSON yanıt.
     */
    public function systemStatus(): void
    {
        Middleware::requireAuth('admin');

        // DB connectivity check
        $dbConnected = false;
        try {
            $db   = \App\Core\Database::getInstance();
            $stmt = $db->prepare('SELECT 1');
            $stmt->execute();
            $dbConnected = true;
        } catch (\Throwable) {
            $dbConnected = false;
        }

        $electionModel   = new Election();
        $currentElection = $electionModel->current();

        $uploadPath = defined('PUBLIC_PATH')
            ? PUBLIC_PATH . '/uploads'
            : ($_SERVER['DOCUMENT_ROOT'] . '/uploads');

        $diskFree = @disk_free_space($uploadPath);
        $diskFreeHuman = $diskFree !== false
            ? round($diskFree / 1_073_741_824, 2) . ' GB'
            : 'N/A';

        $this->json([
            'db_connected'     => $dbConnected,
            'php_version'      => PHP_VERSION,
            'sms_mock'         => (bool) ($_ENV['SMS_MOCK'] ?? false),
            'disk_free'        => $diskFreeHuman,
            'election_status'  => $currentElection['status'] ?? 'none',
            'election_title'   => $currentElection['title'] ?? null,
        ]);
    }

    /**
     * Seçim durumunu zorla değiştir (override).
     */
    public function overrideElection(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $electionId = (int) $this->input('election_id', 0);
        $status     = $this->input('status', '');

        if (!in_array($status, ['draft', 'test', 'open', 'closed'], true)) {
            flash('error', 'Geçersiz durum değeri.');
            $this->redirect('/admin/elections');
            return;
        }

        $electionModel = new Election();
        $election      = $electionModel->find($electionId);

        if (!$election) {
            flash('error', 'Seçim bulunamadı.');
            $this->redirect('/admin/elections');
            return;
        }

        $updateData = ['status' => $status];
        if ($status === 'open' && empty($election['started_at'])) {
            $updateData['started_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'closed' && empty($election['closed_at'])) {
            $updateData['closed_at'] = date('Y-m-d H:i:s');
        }

        $electionModel->update($electionId, $updateData);

        ActivityLogService::log(
            'election_override',
            "Admin seçim durumunu değiştirdi: \"{$election['title']}\" → {$status}",
            $electionId
        );

        flash('success', "Seçim durumu \"{$status}\" olarak güncellendi.");
        $this->redirect('/admin/elections');
    }

    /**
     * Commitment hash'lerini CSV olarak dışa aktar.
     */
    public function hashExport(): void
    {
        Middleware::requireAuth('admin');

        $electionId = $this->currentElectionId();

        if (!$electionId) {
            $electionModel   = new Election();
            $currentElection = $electionModel->current();
            $electionId      = $currentElection ? (int) $currentElection['id'] : null;
        }

        $hashes = [];
        if ($electionId) {
            $voteModel = new Vote();
            $hashes    = $voteModel->allHashes($electionId);
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="hash_export_' . date('Ymd_His') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        // BOM for UTF-8 Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Kurul', 'Commitment Hash', 'Oy Zamanı']);

        foreach ($hashes as $row) {
            fputcsv($out, [
                $row['ballot_title'],
                $row['commitment_hash'],
                $row['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * Seçim listesi.
     */
    public function elections(): void
    {
        Middleware::requireAuth('admin');

        $electionModel = new Election();
        $elections     = $electionModel->all('id DESC');

        $this->layout('main', 'admin.elections', [
            'pageTitle' => 'Seçim Yönetimi',
            'elections' => $elections,
        ]);
    }

    /**
     * Yeni seçim oluştur.
     */
    public function storeElection(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $title       = trim($this->input('title', ''));
        $description = trim($this->input('description', ''));

        if ($title === '') {
            flash('error', 'Seçim başlığı zorunludur.');
            $this->redirect('/admin/elections');
            return;
        }

        $electionModel = new Election();
        $currentUser   = $this->currentUser();

        $newId = $electionModel->create([
            'title'       => $title,
            'description' => $description ?: null,
            'status'      => 'draft',
            'created_by'  => $currentUser ? (int) $currentUser['id'] : null,
        ]);

        // Set as current election in session
        $_SESSION['election_id'] = $newId;

        ActivityLogService::log('election_created', "Yeni seçim oluşturuldu: {$title}", $newId);

        flash('success', "Seçim \"{$title}\" oluşturuldu ve aktif seçim olarak ayarlandı.");
        $this->redirect('/admin/elections');
    }

    /**
     * PDF tutanak indir.
     * PdfService Phase 11'de oluşturulacak; şimdilik yer tutucu.
     */
    public function downloadTutanak(): void
    {
        Middleware::requireAuth('admin');

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            $electionModel   = new Election();
            $currentElection = $electionModel->current();
            $electionId      = $currentElection ? (int) $currentElection['id'] : null;
        }

        if (!$electionId) {
            flash('error', 'Aktif seçim bulunamadı. PDF oluşturulamadı.');
            $this->redirect('/admin');
            return;
        }

        // PdfService will be injected in Phase 11
        // $pdfService = new \App\Services\PdfService();
        // $pdfService->generateTutanak($electionId);

        // Temporary placeholder until Phase 11
        flash('info', 'PDF tutanak özelliği Phase 11\'de tamamlanacak.');
        $this->redirect('/admin');
    }
}
