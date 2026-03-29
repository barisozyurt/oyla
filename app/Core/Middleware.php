<?php
declare(strict_types=1);

namespace App\Core;

class Middleware
{
    public static function requireAuth(string ...$roles): array
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /auth/login');
            exit;
        }
        if (!empty($roles) && !in_array($_SESSION['user']['role'], $roles, true)) {
            http_response_code(403);
            echo '403 - Yetkisiz erişim.';
            exit;
        }
        return $_SESSION['user'];
    }

    public static function guest(): void
    {
        if (isset($_SESSION['user'])) {
            $role = $_SESSION['user']['role'];
            $redirect = match ($role) {
                'admin' => '/admin',
                'divan_baskani' => '/divan',
                'gorevli' => '/gorevli',
                default => '/',
            };
            header('Location: ' . $redirect);
            exit;
        }
    }
}
