<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Parola politikası — bin/install ile aynı kuralları paylaşır.
 *
 * Kurallar:
 *   - Minimum 12 karakter
 *   - En az 1 büyük harf, 1 küçük harf, 1 rakam
 *   - Yaygın kelime/parola blacklist'i (oyla, password, 123456, qwerty, admin, sifre, parola)
 *   - Aynı karakterin 5 kez ardışık tekrarına izin verme (örn. "aaaaaXXX1")
 *
 * Test ortamında min uzunluk Config'ten override edilebilir.
 */
final class PasswordPolicy
{
    private const MIN_LENGTH = 12;

    private const BLACKLIST = [
        'password', 'passw0rd', '123456', '12345678', 'qwerty', 'qwertyuiop',
        'admin', 'oyla', 'sifre', 'parola', 'iloveyou', 'letmein',
        'welcome', 'monkey', 'dragon', 'master', 'azerty',
    ];

    /**
     * Geçerse null döner, geçmezse kullanıcıya gösterilebilir hata mesajı.
     */
    public static function validate(string $password): ?string
    {
        $min = self::minLength();
        if (strlen($password) < $min) {
            return "Parola en az {$min} karakter olmalıdır.";
        }
        if (strlen($password) > 256) {
            return 'Parola çok uzun (max 256 karakter).';
        }
        if (!preg_match('/[A-Z]/u', $password)) {
            return 'Parolada en az 1 büyük harf bulunmalıdır.';
        }
        if (!preg_match('/[a-z]/u', $password)) {
            return 'Parolada en az 1 küçük harf bulunmalıdır.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Parolada en az 1 rakam bulunmalıdır.';
        }
        if (preg_match('/(.)\1{4,}/u', $password)) {
            return 'Parola aynı karakterin 5 veya daha fazla tekrarını içeremez.';
        }
        $lower = mb_strtolower($password);
        foreach (self::BLACKLIST as $bad) {
            if (str_contains($lower, $bad)) {
                return "Parola yaygın bir kelime içeremez (\"{$bad}\").";
            }
        }
        return null;
    }

    public static function minLength(): int
    {
        // testing ortamında 8 yeterli — fixture parolaları için
        return Config::isTesting() ? 8 : self::MIN_LENGTH;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
