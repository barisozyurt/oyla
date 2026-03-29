<?php
declare(strict_types=1);

namespace App\Core;

class RateLimiter
{
    public static function check(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool
    {
        $sessionKey = "rate_limit_{$key}";
        $blockKey   = "rate_block_{$key}";

        if (isset($_SESSION[$blockKey]) && time() < $_SESSION[$blockKey]) {
            return false;
        }

        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }

        $_SESSION[$sessionKey] = array_values(array_filter(
            $_SESSION[$sessionKey],
            fn($t) => $t > time() - $windowSeconds
        ));

        if (count($_SESSION[$sessionKey]) >= $maxAttempts) {
            $_SESSION[$blockKey] = time() + $windowSeconds;
            return false;
        }

        $_SESSION[$sessionKey][] = time();
        return true;
    }

    public static function reset(string $key): void
    {
        unset($_SESSION["rate_limit_{$key}"], $_SESSION["rate_block_{$key}"]);
    }
}
