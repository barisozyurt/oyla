<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Merkezi, doğrulamalı yapılandırma erişimi.
 *
 * .env içinde tanımsız ya da güvensiz değerler (örn. default secret, "CHANGE_ME"
 * kalıntıları) çağrıldıklarında InvalidConfigException fırlatır. Böylece
 * fallback'lerden gelen sessiz güvenlik açıkları engellenir.
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $cache = [];

    private static bool $bootValidated = false;

    public static function env(): string
    {
        $value = self::get('APP_ENV', 'production');
        return is_string($value) ? strtolower($value) : 'production';
    }

    public static function isProduction(): bool
    {
        return self::env() === 'production';
    }

    public static function isTesting(): bool
    {
        return self::env() === 'testing';
    }

    public static function isDebug(): bool
    {
        $debug = self::get('APP_DEBUG', 'false');
        return $debug === true || $debug === 'true' || $debug === '1';
    }

    /**
     * Çevre değişkenini opsiyonel default ile döndürür.
     * Production'ta hassas anahtarlar için bu metodu kullanmayın — secret() / require() çağırın.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        self::$cache[$key] = $value;
        return $value;
    }

    /**
     * Production'da boş olamaz, "CHANGE_ME" gibi placeholder içermez,
     * minimum uzunluk şartını sağlamalıdır.
     */
    public static function secret(string $key, int $minLength = 32): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        if (!is_string($value) || $value === '') {
            throw new InvalidConfigException(
                "Yapılandırma hatası: {$key} tanımsız. .env dosyasını kontrol edin."
            );
        }

        // Tam-değer placeholder'lar (case-insensitive). Substring match yapmıyoruz —
        // aksi halde "my_app_secret_key" gibi gerçek değerler de yakalanır.
        $forbiddenExact = [
            'default_secret',
            'changeme',
            'password',
            'secret',
            '12345',
        ];
        foreach ($forbiddenExact as $bad) {
            if (strcasecmp($value, $bad) === 0) {
                throw new InvalidConfigException(
                    "Yapılandırma hatası: {$key} \"{$bad}\" değerini taşıyor. " .
                    "Lütfen güvenli rastgele bir değerle değiştirin: " .
                    'php -r "echo bin2hex(random_bytes(32));"'
                );
            }
        }
        // CHANGE_ME prefix'i yine yakalanır (kullanıcının .env.example'dan kopyalayıp
        // değiştirmediği yaygın durum)
        if (stripos($value, 'CHANGE_ME') !== false) {
            throw new InvalidConfigException(
                "Yapılandırma hatası: {$key} \"CHANGE_ME\" placeholder içeriyor. " .
                "Lütfen güvenli rastgele bir değerle değiştirin: " .
                'php -r "echo bin2hex(random_bytes(32));"'
            );
        }

        if (strlen($value) < $minLength) {
            throw new InvalidConfigException(
                "Yapılandırma hatası: {$key} en az {$minLength} karakter olmalı. " .
                'Tavsiye: bin2hex(random_bytes(32))'
            );
        }

        return $value;
    }

    /**
     * Bootstrap aşamasında zorunlu yapılandırmaları doğrular.
     * Eksik veya placeholder içeren değerlerde uygulamayı erken durdurur.
     */
    public static function validateBoot(): void
    {
        if (self::$bootValidated) {
            return;
        }

        // Test ortamında secret validation gevşetilir (phpunit.xml içinden gelir).
        if (!self::isTesting()) {
            self::secret('APP_SECRET', 32);
            self::secret('TOKEN_SECRET', 32);
        }

        // Production guards
        if (self::isProduction()) {
            if (self::isDebug()) {
                throw new InvalidConfigException(
                    'APP_DEBUG production ortamında "true" olamaz. ' .
                    'Stack trace ifşası kullanıcılara gider.'
                );
            }

            $dbPass = self::get('DB_PASS', '');
            if (!is_string($dbPass) || strlen($dbPass) < 12) {
                throw new InvalidConfigException(
                    'DB_PASS production ortamında en az 12 karakter olmalı.'
                );
            }

            if (self::get('SMS_MOCK', 'true') === 'true') {
                error_log('[Oyla] UYARI: Production ortamında SMS_MOCK=true. Token\'ler dosyaya plaintext yazılıyor.');
            }
        }

        self::$bootValidated = true;
    }

    /** Test/dev için cache'i temizle. */
    public static function reset(): void
    {
        self::$cache = [];
        self::$bootValidated = false;
    }
}
