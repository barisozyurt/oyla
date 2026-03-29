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
 * Return the URL for a public asset.
 * Usage: <?= asset('css/app.css') ?>
 */
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

/**
 * Check whether the current request URI matches a given path.
 * Useful for highlighting active nav items.
 */
function isUrl(string $path): bool
{
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    return $current === $path;
}

/**
 * Generate an HTML hidden input containing the CSRF token.
 * Intended for use directly inside view templates.
 */
function csrf_field(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="_csrf" value="' . e($_SESSION['csrf_token']) . '">';
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
 * Returns null if no message exists for the given key.
 */
function getFlash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}
