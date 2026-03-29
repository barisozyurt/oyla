<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Core\RateLimiter;
use App\Models\User;
use App\Models\Election;
use App\Services\ActivityLogService;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        Middleware::guest();
        $this->layout('auth', 'auth/login', [
            'csrf'  => $this->csrfField(),
            'error' => getFlash('error'),
        ]);
    }

    public function login(): void
    {
        $this->verifyCsrf();

        if (!RateLimiter::check('login')) {
            flash('error', 'Çok fazla deneme. Lütfen 5 dakika bekleyin.');
            $this->redirect('/auth/login');
            return;
        }

        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');

        if ($username === '' || $password === '') {
            flash('error', 'Kullanıcı adı ve şifre gereklidir.');
            $this->redirect('/auth/login');
            return;
        }

        $userModel = new User();
        $user      = $userModel->findByUsername($username);

        if (!$user || !$user['is_active'] || !$userModel->verifyPassword($password, $user['password_hash'])) {
            flash('error', 'Geçersiz kullanıcı adı veya şifre.');
            $this->redirect('/auth/login');
            return;
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Store user in session
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'name'     => $user['name'],
            'role'     => $user['role'],
            'desk_no'  => $user['desk_no'],
        ];

        // Set current election context
        $electionModel = new Election();
        $election      = $electionModel->current();
        if ($election) {
            $_SESSION['election_id'] = $election['id'];
        }

        RateLimiter::reset('login');

        ActivityLogService::log(
            'login',
            "Giriş: {$user['username']} ({$user['role']})",
            $election['id'] ?? null
        );

        // Redirect based on role
        $redirect = match ($user['role']) {
            'admin'         => '/admin',
            'divan_baskani' => '/divan',
            'gorevli'       => '/gorevli',
            default         => '/',
        };
        $this->redirect($redirect);
    }

    public function logout(): void
    {
        $user       = $_SESSION['user'] ?? null;
        $electionId = $_SESSION['election_id'] ?? null;

        if ($user) {
            ActivityLogService::log(
                'logout',
                "Çıkış: {$user['username']}",
                $electionId
            );
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        $this->redirect('/auth/login');
    }
}
