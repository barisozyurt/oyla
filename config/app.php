<?php
/**
 * Uygulama yapılandırması.
 *
 * Hassas alanlar (APP_SECRET) için Config::secret() kullanın — fallback değer
 * bilerek YOK. Eksik secret bootstrap aşamasında uygulamayı durdurur
 * (App\Core\Config::validateBoot()).
 */

return [
    'name'     => $_ENV['APP_NAME'] ?? 'Oyla',
    'env'      => strtolower($_ENV['APP_ENV'] ?? 'production'),
    'debug'    => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'url'      => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Europe/Istanbul',

    // APP_SECRET için fallback bilinçli olarak YOK.
    // Bu değer Config::secret('APP_SECRET') ile alınır, eksikse uygulama durur.
    'secret_key' => 'APP_SECRET',

    // Parola politikası
    'password_min_length' => 12,

    // Token (saat olarak HMAC URL'sinin geçerliliği)
    'token_expire_minutes' => (int) ($_ENV['TOKEN_EXPIRE_MINUTES'] ?? 120),

    // Anonimleştirme: seçim kapandıktan kaç gün sonra üye PII silinir (KVKK)
    'pii_retention_days' => (int) ($_ENV['PII_RETENTION_DAYS'] ?? 365),
];
