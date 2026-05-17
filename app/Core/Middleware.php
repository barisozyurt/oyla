<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Middleware — auth + role guard.
 *
 * `requireAuth()` artık çağrı yerine sadece bağlamı döndürmekle kalmıyor;
 * controller-level wiring için `Router::dispatch()` öncesi tetiklenebilen
 * `before()` callback olarak da kullanılabilir.
 */
class Middleware
{
    public static function requireAuth(string ...$roles): array
    {
        if (!isset($_SESSION['user'])) {
            // AJAX ise 401 JSON, değilse login redirect
            if (self::isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Yetkisiz']);
                exit;
            }
            header('Location: /auth/login');
            exit;
        }

        if (!empty($roles) && !in_array($_SESSION['user']['role'], $roles, true)) {
            Logger::warning('Yetkisiz erişim denemesi', [
                'user'     => $_SESSION['user']['username'] ?? null,
                'role'     => $_SESSION['user']['role']     ?? null,
                'required' => $roles,
                'uri'      => $_SERVER['REQUEST_URI']       ?? null,
            ]);
            ErrorHandler::renderTemplate(403);
            exit;
        }

        return $_SESSION['user'];
    }

    public static function guest(): void
    {
        if (isset($_SESSION['user'])) {
            $role = $_SESSION['user']['role'];
            $redirect = match ($role) {
                'admin'         => '/admin',
                'divan_baskani' => '/divan',
                'gorevli'       => '/gorevli',
                default         => '/',
            };
            header('Location: ' . $redirect);
            exit;
        }
    }

    private static function isAjax(): bool
    {
        return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}
