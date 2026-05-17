<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Çok dilli desteğin basit altyapısı.
 *
 * - Varsayılan dil: tr (Türkçe). İngilizce (en) opsiyonel.
 * - Çeviriler /resources/lang/{locale}.php içinde key=>value dizileri.
 * - Anahtar bulunamazsa anahtar olduğu gibi döner — fallback semantiği.
 * - Sprintf placeholder'ları desteklenir: __('Hello %s', $name).
 */
final class I18n
{
    private static string $locale = 'tr';
    private static array $messages = [];
    private static array $loaded = [];

    public static function setLocale(string $locale): void
    {
        if (!in_array($locale, ['tr', 'en'], true)) {
            $locale = 'tr';
        }
        self::$locale = $locale;
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function translate(string $key, array $args = [], ?string $locale = null): string
    {
        $locale = $locale ?: self::$locale;
        self::loadLocale($locale);

        $msg = self::$messages[$locale][$key] ?? $key;
        if ($args) {
            return vsprintf($msg, $args);
        }
        return $msg;
    }

    private static function loadLocale(string $locale): void
    {
        if (isset(self::$loaded[$locale])) return;
        $file = dirname(__DIR__, 2) . '/resources/lang/' . $locale . '.php';
        if (file_exists($file)) {
            self::$messages[$locale] = require $file;
        } else {
            self::$messages[$locale] = [];
        }
        self::$loaded[$locale] = true;
    }
}
