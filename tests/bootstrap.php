<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * PHPUnit'in <env> tag'leri varsayılan olarak yalnızca putenv() çağırır;
 * $_ENV/$_SERVER'a yazmaz. PHP'nin default variables_order=GPCS olduğu için
 * $_ENV başlangıçta boş kalır. Bu yüzden hem $_SERVER'dan hem getenv'ten
 * alabileceğimiz tüm key'leri $_ENV'a kopyalıyoruz — Database.php ve
 * Config.php $_ENV bekliyor.
 */
$envKeys = [
    'APP_ENV', 'APP_DEBUG', 'APP_SECRET', 'TOKEN_SECRET',
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS',
    'SMS_MOCK', 'TOKEN_EXPIRE_MINUTES',
    'NETGSM_USERNAME', 'NETGSM_PASSWORD', 'NETGSM_FROM',
    'APP_URL', 'APP_TIMEZONE', 'UPLOAD_PATH',
    'PII_RETENTION_DAYS',
];
foreach ($envKeys as $k) {
    if (!isset($_ENV[$k]) || $_ENV[$k] === '') {
        $val = $_SERVER[$k] ?? getenv($k);
        if ($val !== false && $val !== null && $val !== '') {
            $_ENV[$k] = (string) $val;
        }
    }
}
// Geri kalan $_SERVER key'leri için de fallback (PHPUnit force=true durumu)
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
