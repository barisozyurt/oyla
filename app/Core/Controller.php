<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    protected function layout(string $layout, string $template, array $data = []): void
    {
        View::layout($layout, $template, $data);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function csrfField(): string
    {
        $token = $this->csrfToken();
        return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
    }

    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * CSRF doğrulama. Aynı zamanda double-submit pattern kontrolü yapar:
     * X-CSRF-Token header veya _csrf body alanı yeterli; ikisi de varsa eşleşmesi gerekir.
     *
     * Token başarılı doğrulama sonrasında REGENERATE edilmez — multi-tab uyumu için.
     * Yerine session_regenerate_id() login/logout'ta yapılır.
     */
    protected function verifyCsrf(): void
    {
        $sessToken = $_SESSION['csrf_token'] ?? '';
        if ($sessToken === '') {
            $this->csrfFail('Oturum CSRF token\'ı yok.');
        }

        $bodyToken   = (string) ($_POST['_csrf'] ?? '');
        $headerToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        // En az biri verilmeli
        if ($bodyToken === '' && $headerToken === '') {
            $this->csrfFail('CSRF token gönderilmedi.');
        }
        // Verilen token(lar) session ile eşleşmeli
        $candidates = array_filter([$bodyToken, $headerToken], fn($t) => $t !== '');
        foreach ($candidates as $t) {
            if (!hash_equals($sessToken, $t)) {
                $this->csrfFail('CSRF token uyuşmadı.');
            }
        }
    }

    private function csrfFail(string $reason): void
    {
        Logger::warning('CSRF doğrulama başarısız', [
            'reason' => $reason,
            'uri'    => $_SERVER['REQUEST_URI'] ?? null,
            'user'   => $_SESSION['user']['username'] ?? null,
        ]);
        http_response_code(403);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'CSRF doğrulanamadı']);
        } else {
            ErrorHandler::renderTemplate(403, 'CSRF doğrulanamadı. Sayfayı yenileyip tekrar deneyin.');
        }
        exit;
    }

    protected function session(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function currentElectionId(): ?int
    {
        return isset($_SESSION['election_id']) ? (int) $_SESSION['election_id'] : null;
    }
}
