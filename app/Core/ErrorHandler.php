<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Global exception & error handler.
 *
 * - Debug açıksa stack trace ekrana basar (sadece development/staging).
 * - Production'da kullanıcıya temiz 500 sayfası gösterir, detayları log'a yazar.
 * - InvalidConfigException özel durum: bootstrap aşamasında erken ve net hata.
 */
final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool
    {
        // Hata seviyesine göre exception'a dönüştür — fatal'leri yakalamak için
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function handleException(Throwable $e): void
    {
        Logger::error('Yakalanmamış istisna', [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, sprintf("[%s] %s\n%s\n", get_class($e), $e->getMessage(), $e->getTraceAsString()));
            exit(1);
        }

        if ($e instanceof InvalidConfigException) {
            self::renderConfigError($e);
            return;
        }

        self::renderServerError($e);
    }

    public static function handleShutdown(): void
    {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            Logger::error('Fatal shutdown', $err);
            if (PHP_SAPI !== 'cli' && !headers_sent()) {
                self::renderServerError(new \ErrorException(
                    $err['message'],
                    0,
                    $err['type'],
                    $err['file'],
                    $err['line']
                ));
            }
        }
    }

    private static function renderConfigError(InvalidConfigException $e): void
    {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        if (Config::isDebug()) {
            echo '<!doctype html><meta charset="utf-8"><title>Yapılandırma Hatası</title>';
            echo '<div style="font-family:system-ui;max-width:720px;margin:40px auto;padding:24px;border-left:4px solid #dc2626;background:#fef2f2;">';
            echo '<h1 style="color:#991b1b;margin:0 0 12px">Yapılandırma Hatası</h1>';
            echo '<pre style="white-space:pre-wrap">' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</pre>';
            echo '</div>';
            return;
        }

        // Production: detay vermeden bilgilendir.
        self::renderTemplate(500, 'Sistem yapılandırması eksik veya hatalı. Lütfen yöneticiyle iletişime geçin.');
    }

    private static function renderServerError(Throwable $e): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');

        if (Config::isDebug()) {
            echo '<!doctype html><meta charset="utf-8"><title>Hata</title>';
            echo '<div style="font-family:monospace;max-width:960px;margin:24px auto;padding:24px;border:1px solid #fca5a5;background:#fef2f2;">';
            echo '<h1 style="color:#991b1b">' . htmlspecialchars(get_class($e), ENT_QUOTES) . '</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</p>';
            echo '<p style="color:#64748b">' . htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES) . '</p>';
            echo '<pre style="white-space:pre-wrap;color:#475569">' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES) . '</pre>';
            echo '</div>';
            return;
        }

        self::renderTemplate(500);
    }

    public static function renderTemplate(int $status, ?string $message = null): void
    {
        $statusToTemplate = [
            403 => '403',
            404 => '404',
            500 => '500',
            503 => '503',
        ];
        $template = $statusToTemplate[$status] ?? '500';
        $file = dirname(__DIR__) . '/Views/errors/' . $template . '.php';

        http_response_code($status);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        if (file_exists($file)) {
            $extraMessage = $message;
            require $file;
            return;
        }

        // Son çare fallback
        echo "<h1>HTTP {$status}</h1>";
        if ($message) {
            echo '<p>' . htmlspecialchars($message, ENT_QUOTES) . '</p>';
        }
    }
}
