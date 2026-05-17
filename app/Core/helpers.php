<?php

declare(strict_types=1);

/**
 * HTML escape helper.
 * Usage: <?= e($variable) ?>
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Return the URL for a public asset with cache-busting query param.
 * Usage: <?= asset('css/app.css') ?>
 *
 * Eğer dosya disk'te varsa filemtime ile versiyon hash'i eklenir.
 * Yoksa sadece path döner (test ortamı vs.).
 */
function asset(string $path): string
{
    $rel = '/assets/' . ltrim($path, '/');
    $publicDir = defined('PUBLIC_PATH') ? PUBLIC_PATH : dirname(__DIR__, 2) . '/public';
    $full = $publicDir . $rel;
    if (is_file($full)) {
        $mtime = filemtime($full);
        if ($mtime !== false) {
            return $rel . '?v=' . dechex($mtime);
        }
    }
    return $rel;
}

/**
 * Check whether the current request URI matches a given path.
 */
function isUrl(string $path): bool
{
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    return $current === $path;
}

/**
 * Generate an HTML hidden input containing the CSRF token.
 */
function csrf_field(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="_csrf" value="' . e($_SESSION['csrf_token']) . '">';
}

/**
 * Current CSRF token (for meta tag injection).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Store a one-time flash message in the session.
 */
function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

/**
 * Retrieve and remove a flash message from the session.
 */
function getFlash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Translate helper. __('vote.submit') veya __('Hello %s', $name).
 * Bilinmeyen anahtar key olarak döner — i18n geçişi kademeli olur.
 */
function __(string $key, array $args = []): string
{
    return \App\Core\I18n::translate($key, $args);
}
