<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Core\PasswordPolicy;
use App\Models\User;
use App\Services\ActivityLogService;

/**
 * Admin → Kullanıcı yönetimi.
 *
 * Eskiden AdminController içindeydi; FAZ 2.6 god-controller split kapsamında ayrıldı.
 */
class AdminUsersController extends Controller
{
    public function index(): void
    {
        Middleware::requireAuth('admin');
        $users = (new User())->all('name ASC');
        $this->layout('main', 'admin.users', [
            'pageTitle' => 'Kullanıcı Yönetimi',
            'users'     => $users,
        ]);
    }

    public function create(): void
    {
        Middleware::requireAuth('admin');
        $this->layout('main', 'admin.user_form', [
            'pageTitle' => 'Yeni Kullanıcı',
            'user'      => null,
            'errors'    => [],
        ]);
    }

    public function store(): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $userModel = new User();
        $name     = trim((string) $this->input('name', ''));
        $username = trim((string) $this->input('username', ''));
        $password = (string) $this->input('password', '');
        $role     = (string) $this->input('role', '');
        $deskNo   = $this->input('desk_no', null);

        $errors = [];
        if ($name === '')                               $errors[] = 'Ad alanı zorunludur.';
        if ($username === '')                           $errors[] = 'Kullanıcı adı zorunludur.';
        elseif ($userModel->findByUsername($username))  $errors[] = 'Bu kullanıcı adı zaten kullanılıyor.';
        $pwError = PasswordPolicy::validate($password);
        if ($pwError !== null)                          $errors[] = $pwError;
        if (!in_array($role, ['admin','divan_baskani','gorevli'], true)) $errors[] = 'Geçerli bir rol seçiniz.';

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
            'password'  => PasswordPolicy::hash($password),
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

    public function edit(string $id): void
    {
        Middleware::requireAuth('admin');
        $user = (new User())->find((int) $id);
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

    public function update(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $userModel = new User();
        $user = $userModel->find((int) $id);
        if (!$user) {
            flash('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users');
            return;
        }

        $name     = trim((string) $this->input('name', ''));
        $username = trim((string) $this->input('username', ''));
        $password = (string) $this->input('password', '');
        $role     = (string) $this->input('role', '');
        $deskNo   = $this->input('desk_no', null);

        $errors = [];
        if ($name === '')                               $errors[] = 'Ad alanı zorunludur.';
        if ($username === '')                           $errors[] = 'Kullanıcı adı zorunludur.';
        else {
            $existing = $userModel->findByUsername($username);
            if ($existing && (int) $existing['id'] !== (int) $id) $errors[] = 'Bu kullanıcı adı zaten kullanılıyor.';
        }
        if ($password !== '') {
            $pwError = PasswordPolicy::validate($password);
            if ($pwError !== null) $errors[] = $pwError;
        }
        if (!in_array($role, ['admin','divan_baskani','gorevli'], true)) $errors[] = 'Geçerli bir rol seçiniz.';

        if (!empty($errors)) {
            $this->layout('main', 'admin.user_form', [
                'pageTitle' => 'Kullanıcı Düzenle',
                'user'      => $user,
                'errors'    => $errors,
            ]);
            return;
        }

        $data = ['name' => $name, 'username' => $username, 'role' => $role];
        if ($password !== '')      $data['password'] = PasswordPolicy::hash($password);
        if ($role === 'gorevli' && $deskNo !== null && $deskNo !== '')
            $data['desk_no'] = (int) $deskNo;
        else $data['desk_no'] = null;

        $userModel->update((int) $id, $data);
        ActivityLogService::log('user_updated', "Kullanıcı güncellendi: {$username}");
        flash('success', "Kullanıcı \"{$name}\" güncellendi.");
        $this->redirect('/admin/users');
    }

    public function destroy(string $id): void
    {
        Middleware::requireAuth('admin');
        $this->verifyCsrf();

        $userModel = new User();
        $user = $userModel->find((int) $id);
        if (!$user) {
            flash('error', 'Kullanıcı bulunamadı.');
            $this->redirect('/admin/users');
            return;
        }

        $current = $this->currentUser();
        if ($current && (int) $current['id'] === (int) $id) {
            flash('error', 'Kendi hesabınızı silemezsiniz.');
            $this->redirect('/admin/users');
            return;
        }

        $userModel->update((int) $id, ['is_active' => 0]);
        ActivityLogService::log('user_deactivated', "Kullanıcı pasife alındı: {$user['username']}");
        flash('success', "Kullanıcı \"{$user['name']}\" pasife alındı.");
        $this->redirect('/admin/users');
    }
}
