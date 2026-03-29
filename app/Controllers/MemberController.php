<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\Member;
use App\Models\Election;
use App\Services\SmsService;
use App\Services\ActivityLogService;
use App\Core\Database;
use Ramsey\Uuid\Uuid;

class MemberController extends Controller
{
    /**
     * List all members for the current election.
     */
    public function index(): void
    {
        Middleware::requireAuth('admin');

        $electionId = $this->currentElectionId();
        $memberModel = new Member();
        $members = $electionId ? $memberModel->byElection($electionId) : [];

        $this->layout('main', 'yonetim.index', [
            'pageTitle'  => 'Üye Yönetimi',
            'members'    => $members,
            'memberModel' => $memberModel,
        ]);
    }

    /**
     * Show the create member form.
     */
    public function create(): void
    {
        Middleware::requireAuth('admin');

        $this->layout('main', 'yonetim.create', [
            'pageTitle' => 'Yeni Üye Ekle',
        ]);
    }

    /**
     * Validate and persist a new member.
     */
    public function store(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            flash('error', 'Aktif seçim bulunamadı.');
            $this->redirect('/yonetim/create');
            return;
        }

        $name  = trim($this->input('name', ''));
        $tc    = trim($this->input('tc_kimlik', ''));
        $sicil = trim($this->input('sicil_no', ''));
        $phone = trim($this->input('phone', ''));
        $email = trim($this->input('email', ''));
        $role  = $this->input('role', 'uye');

        // Validation
        $errors = [];
        if ($name === '') {
            $errors[] = 'Ad Soyad zorunludur.';
        }
        if ($tc !== '' && (!ctype_digit($tc) || strlen($tc) !== 11)) {
            $errors[] = 'TC Kimlik numarası 11 haneli rakam olmalıdır.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $errors[] = 'Geçersiz telefon numarası formatı.';
        }
        $allowedRoles = ['uye', 'yk_adayi', 'denetleme_adayi', 'disiplin_adayi'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'uye';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/yonetim/create');
            return;
        }

        // Handle photo upload
        $photoPath = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photoPath = $this->processPhotoUpload($_FILES['photo']);
            if ($photoPath === false) {
                flash('error', 'Fotoğraf yüklenirken hata oluştu. Dosya türü veya boyutu geçersiz.');
                $this->redirect('/yonetim/create');
                return;
            }
        }

        $data = [
            'election_id' => $electionId,
            'name'        => $name,
            'tc_kimlik'   => $tc ?: null,
            'sicil_no'    => $sicil ?: null,
            'phone'       => $phone ?: null,
            'email'       => $email ?: null,
            'role'        => $role,
            'status'      => 'waiting',
        ];
        if ($photoPath) {
            $data['photo_path'] = $photoPath;
        }

        $memberModel = new Member();
        $memberModel->create($data);

        ActivityLogService::log('member_create', "Yeni üye eklendi: {$name}", $electionId);
        flash('success', "Üye '{$name}' başarıyla eklendi.");
        $this->redirect('/yonetim');
    }

    /**
     * Show the edit form for a member.
     */
    public function edit(string $id): void
    {
        Middleware::requireAuth('admin');

        $memberModel = new Member();
        $member = $memberModel->find((int) $id);

        if (!$member) {
            flash('error', 'Üye bulunamadı.');
            $this->redirect('/yonetim');
            return;
        }

        $this->layout('main', 'yonetim.edit', [
            'pageTitle' => 'Üye Düzenle',
            'member'    => $member,
        ]);
    }

    /**
     * Validate and update an existing member.
     */
    public function update(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $memberModel = new Member();
        $member = $memberModel->find((int) $id);

        if (!$member) {
            flash('error', 'Üye bulunamadı.');
            $this->redirect('/yonetim');
            return;
        }

        $name  = trim($this->input('name', ''));
        $tc    = trim($this->input('tc_kimlik', ''));
        $sicil = trim($this->input('sicil_no', ''));
        $phone = trim($this->input('phone', ''));
        $email = trim($this->input('email', ''));
        $role  = $this->input('role', 'uye');

        $errors = [];
        if ($name === '') {
            $errors[] = 'Ad Soyad zorunludur.';
        }
        if ($tc !== '' && (!ctype_digit($tc) || strlen($tc) !== 11)) {
            $errors[] = 'TC Kimlik numarası 11 haneli rakam olmalıdır.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $errors[] = 'Geçersiz telefon numarası formatı.';
        }
        $allowedRoles = ['uye', 'yk_adayi', 'denetleme_adayi', 'disiplin_adayi'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'uye';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/yonetim/edit/' . $id);
            return;
        }

        // Handle optional new photo upload
        $data = [
            'name'      => $name,
            'tc_kimlik' => $tc ?: null,
            'sicil_no'  => $sicil ?: null,
            'phone'     => $phone ?: null,
            'email'     => $email ?: null,
            'role'      => $role,
        ];

        if (!empty($_FILES['photo']['tmp_name'])) {
            $photoPath = $this->processPhotoUpload($_FILES['photo']);
            if ($photoPath === false) {
                flash('error', 'Fotoğraf yüklenirken hata oluştu. Dosya türü veya boyutu geçersiz.');
                $this->redirect('/yonetim/edit/' . $id);
                return;
            }
            $data['photo_path'] = $photoPath;
        }

        $memberModel->update((int) $id, $data);

        ActivityLogService::log('member_update', "Üye güncellendi: {$name}", $this->currentElectionId());
        flash('success', "Üye '{$name}' başarıyla güncellendi.");
        $this->redirect('/yonetim');
    }

    /**
     * Delete a member — only allowed when election is in draft status.
     */
    public function destroy(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $memberModel = new Member();
        $member = $memberModel->find((int) $id);

        if (!$member) {
            flash('error', 'Üye bulunamadı.');
            $this->redirect('/yonetim');
            return;
        }

        $electionModel = new Election();
        $election = $electionModel->find($member['election_id']);

        if (!$election || $election['status'] !== 'draft') {
            flash('error', 'Üye silme işlemi yalnızca taslak durumdaki seçimlerde yapılabilir.');
            $this->redirect('/yonetim');
            return;
        }

        $memberModel->delete((int) $id);

        ActivityLogService::log('member_delete', "Üye silindi: {$member['name']}", $member['election_id']);
        flash('success', "Üye '{$member['name']}' silindi.");
        $this->redirect('/yonetim');
    }

    /**
     * Show the CSV import form.
     */
    public function showImport(): void
    {
        Middleware::requireAuth('admin');

        $this->layout('main', 'yonetim.import', [
            'pageTitle' => 'CSV İçe Aktarma',
            'result'    => null,
        ]);
    }

    /**
     * Parse and import members from an uploaded CSV file.
     * Expected columns: sicil_no, tc_kimlik, ad_soyad, telefon, email
     */
    public function importCsv(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            flash('error', 'Aktif seçim bulunamadı.');
            $this->redirect('/yonetim/import');
            return;
        }

        if (empty($_FILES['csv']['tmp_name']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'CSV dosyası yüklenemedi.');
            $this->redirect('/yonetim/import');
            return;
        }

        $file = $_FILES['csv']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            flash('error', 'CSV dosyası okunamadı.');
            $this->redirect('/yonetim/import');
            return;
        }

        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            flash('error', 'CSV dosyası boş veya geçersiz.');
            $this->redirect('/yonetim/import');
            return;
        }

        // Normalize header names
        $header = array_map(fn($h) => strtolower(trim($h)), $header);
        $requiredCols = ['ad_soyad'];
        foreach ($requiredCols as $col) {
            if (!in_array($col, $header, true)) {
                fclose($handle);
                flash('error', "CSV başlıkları geçersiz. En azından 'ad_soyad' sütunu gereklidir.");
                $this->redirect('/yonetim/import');
                return;
            }
        }

        $memberModel = new Member();
        $db = Database::getInstance();

        $imported = 0;
        $skipped  = 0;
        $rowErrors = [];
        $rowNum   = 1;

        $db->beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count($row) !== count($header)) {
                    $rowErrors[] = "Satır {$rowNum}: Sütun sayısı hatalı, atlandı.";
                    $skipped++;
                    continue;
                }

                $data = array_combine($header, $row);
                $name  = trim($data['ad_soyad'] ?? '');
                $sicil = trim($data['sicil_no'] ?? '');
                $tc    = trim($data['tc_kimlik'] ?? '');
                $phone = trim($data['telefon'] ?? '');
                $email = trim($data['email'] ?? '');

                if ($name === '') {
                    $rowErrors[] = "Satır {$rowNum}: Ad Soyad boş, atlandı.";
                    $skipped++;
                    continue;
                }

                if ($tc !== '' && (!ctype_digit($tc) || strlen($tc) !== 11)) {
                    $rowErrors[] = "Satır {$rowNum}: Geçersiz TC ({$tc}), atlandı.";
                    $skipped++;
                    continue;
                }

                // Skip duplicates by sicil_no within the same election
                if ($sicil !== '') {
                    $existing = $memberModel->findBySicil($electionId, $sicil);
                    if ($existing) {
                        $rowErrors[] = "Satır {$rowNum}: Sicil no '{$sicil}' zaten mevcut, atlandı.";
                        $skipped++;
                        continue;
                    }
                }

                $memberModel->create([
                    'election_id' => $electionId,
                    'name'        => $name,
                    'sicil_no'    => $sicil ?: null,
                    'tc_kimlik'   => $tc ?: null,
                    'phone'       => $phone ?: null,
                    'email'       => $email ?: null,
                    'role'        => 'uye',
                    'status'      => 'waiting',
                ]);
                $imported++;
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            fclose($handle);
            flash('error', 'İçe aktarma sırasında veritabanı hatası oluştu: ' . $e->getMessage());
            $this->redirect('/yonetim/import');
            return;
        }

        fclose($handle);

        ActivityLogService::log(
            'member_import',
            "CSV içe aktarma: {$imported} eklendi, {$skipped} atlandı.",
            $electionId
        );

        $result = [
            'imported'  => $imported,
            'skipped'   => $skipped,
            'rowErrors' => $rowErrors,
        ];

        $this->layout('main', 'yonetim.import', [
            'pageTitle' => 'CSV İçe Aktarma',
            'result'    => $result,
        ]);
    }

    /**
     * Upload and attach a photo to a member.
     */
    public function uploadPhoto(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $memberModel = new Member();
        $member = $memberModel->find((int) $id);

        if (!$member) {
            flash('error', 'Üye bulunamadı.');
            $this->redirect('/yonetim');
            return;
        }

        if (empty($_FILES['photo']['tmp_name']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Fotoğraf yüklenemedi.');
            $this->redirect('/yonetim/edit/' . $id);
            return;
        }

        $photoPath = $this->processPhotoUpload($_FILES['photo']);
        if ($photoPath === false) {
            flash('error', 'Geçersiz dosya türü veya boyutu (max 5MB, jpg/jpeg/png/webp).');
            $this->redirect('/yonetim/edit/' . $id);
            return;
        }

        $memberModel->update((int) $id, ['photo_path' => $photoPath]);

        flash('success', 'Fotoğraf başarıyla yüklendi.');
        $this->redirect('/yonetim/edit/' . $id);
    }

    /**
     * Send test SMS to all members with a phone number and return JSON summary.
     */
    public function sendTestSms(): void
    {
        Middleware::requireAuth('admin');

        $electionId = $this->currentElectionId();
        if (!$electionId) {
            $this->json(['success' => false, 'message' => 'Aktif seçim bulunamadı.'], 400);
            return;
        }

        $memberModel = new Member();
        $members = $memberModel->byElection($electionId);

        $sms  = new SmsService();
        $sent = 0;
        $skip = 0;

        foreach ($members as $member) {
            if (empty($member['phone'])) {
                $skip++;
                continue;
            }
            $message = "Oyla test mesajı: Sayın {$member['name']}, sistem test SMS'i başarıyla gönderildi.";
            $sms->send($member['phone'], $message);
            $sent++;
        }

        ActivityLogService::log(
            'sms_test',
            "Test SMS gönderildi: {$sent} üyeye, {$skip} üyenin telefonu yok.",
            $electionId
        );

        $this->json([
            'success' => true,
            'sent'    => $sent,
            'skipped' => $skip,
            'message' => "{$sent} üyeye test SMS'i gönderildi. {$skip} üyenin telefon numarası yok.",
        ]);
    }

    /**
     * Validate and move an uploaded photo file; return the relative web path or false on failure.
     *
     * @param array $file  Single entry from $_FILES
     * @return string|false
     */
    private function processPhotoUpload(array $file): string|false
    {
        $maxSize = (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 5242880); // 5 MB default
        if ($file['size'] > $maxSize) {
            return false;
        }

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!isset($allowedMimes[$mime])) {
            return false;
        }

        $ext      = $allowedMimes[$mime];
        $filename = Uuid::uuid4()->toString() . '.' . $ext;
        $uploadDir = $_ENV['UPLOAD_PATH'] ?? (dirname(__DIR__, 2) . '/public/uploads/photos');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destPath = rtrim($uploadDir, '/') . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return false;
        }

        return '/uploads/photos/' . $filename;
    }
}
