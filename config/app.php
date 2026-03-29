<?php
return [
    'name' => $_ENV['APP_NAME'] ?? 'Oyla',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Europe/Istanbul',
    'secret' => $_ENV['APP_SECRET'] ?? '',
];
