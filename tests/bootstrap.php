<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// phpunit.xml içindeki <env> tanımları $_SERVER'a yazılır ama bizim Config
// $_ENV bekliyor. Aşağıda manuel kopya ediyoruz.
foreach ($_SERVER as $k => $v) {
    if (is_string($v) && !isset($_ENV[$k])) {
        $_ENV[$k] = $v;
    }
}

// PUBLIC_PATH sabiti (helpers.php asset() için gerekli)
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', dirname(__DIR__) . '/public');
}

// Session başlat (RateLimiter fallback'i için)
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
