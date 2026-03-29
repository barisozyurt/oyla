<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * Render a view template with data.
     */
    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    /**
     * Render a view wrapped in a layout.
     */
    protected function layout(string $layout, string $template, array $data = []): void
    {
        View::layout($layout, $template, $data);
    }

    /**
     * Redirect to a URL.
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Return a JSON response.
     */
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Generate an HTML hidden input with the CSRF token.
     */
    protected function csrfField(): string
    {
        $token = $this->csrfToken();
        return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
    }

    /**
     * Return the current CSRF token, generating one if absent.
     */
    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify the submitted CSRF token and abort on mismatch.
     */
    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            echo 'CSRF token doğrulanamadı.';
            exit;
        }
        // Regenerate after use to prevent replay
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Read a value from the session.
     */
    protected function session(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Return true when the current request is a POST.
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Read input from POST then GET, with an optional default.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Return the currently authenticated user array from the session.
     */
    protected function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Return the currently active election ID from the session.
     */
    protected function currentElectionId(): ?int
    {
        return isset($_SESSION['election_id']) ? (int) $_SESSION['election_id'] : null;
    }
}
