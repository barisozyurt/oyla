<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

class App
{
    private static Router $router;

    /**
     * Bootstrap the application.
     */
    public static function boot(): void
    {
        $basePath = dirname(__DIR__, 2);

        // .env yükle (varsa) — test ortamında phpunit kendi env'lerini set eder.
        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->safeLoad();
        }

        // Global error/exception handler — Config validate çağrısından ÖNCE kayıt et
        // ki erken bootstrap hataları da yakalanabilsin.
        ErrorHandler::register();

        // Zorunlu yapılandırmaları doğrula. Eksik/placeholder secret'ta erken durdur.
        Config::validateBoot();

        // Timezone
        date_default_timezone_set(Config::get('APP_TIMEZONE', 'Europe/Istanbul'));

        // Error reporting: debug açıkken ekrana, kapalıyken sadece log'a.
        if (Config::isDebug()) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        }

        // Session — HttpOnly + SameSite=Lax + HTTPS varsa Secure
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = self::isHttps();
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            // Üretimde HTTPS yoksa erken uyar.
            if (Config::isProduction() && !$isHttps) {
                Logger::warning('Production HTTP üzerinde çalışıyor — session cookie Secure flag yok', [
                    'env' => Config::env(),
                ]);
            }
            session_name('OYLA_SID');
            session_start();
        }

        // Router
        self::$router = new Router();
        self::registerRoutes();
        self::$router->dispatch(
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );
    }

    private static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (($_SERVER['SERVER_PORT'] ?? null) == 443) {
            return true;
        }
        // Reverse proxy / load balancer arkasında
        if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return true;
        }
        return false;
    }

    private static function registerRoutes(): void
    {
        $r = self::$router;

        // Auth
        $r->get('/auth/login', 'AuthController', 'showLogin');
        $r->post('/auth/login', 'AuthController', 'login');
        $r->get('/auth/logout', 'AuthController', 'logout');

        // Divan
        $r->get('/divan', 'DivanController', 'index');
        $r->post('/divan/divan-store', 'DivanController', 'storeDivan');
        $r->post('/divan/divan-remove/{id}', 'DivanController', 'removeDivan');
        $r->post('/divan/start', 'DivanController', 'startElection');
        $r->post('/divan/stop', 'DivanController', 'stopElection');
        $r->get('/divan/stats', 'DivanController', 'stats');

        // Yönetim — Üye
        $r->get('/yonetim', 'MemberController', 'index');
        $r->get('/yonetim/create', 'MemberController', 'create');
        $r->post('/yonetim/store', 'MemberController', 'store');
        $r->get('/yonetim/edit/{id}', 'MemberController', 'edit');
        $r->post('/yonetim/update/{id}', 'MemberController', 'update');
        $r->post('/yonetim/delete/{id}', 'MemberController', 'destroy');
        $r->get('/yonetim/import', 'MemberController', 'showImport');
        $r->post('/yonetim/import', 'MemberController', 'importCsv');
        $r->post('/yonetim/photo/{id}', 'MemberController', 'uploadPhoto');
        $r->post('/yonetim/sms-test', 'MemberController', 'sendTestSms');

        // Yönetim — Kurul/Aday
        $r->get('/yonetim/ballots', 'BallotController', 'index');
        $r->post('/yonetim/ballots/store', 'BallotController', 'store');
        $r->post('/yonetim/ballots/update/{id}', 'BallotController', 'update');
        $r->post('/yonetim/ballots/delete/{id}', 'BallotController', 'destroy');
        $r->post('/yonetim/ballots/{ballotId}/candidates', 'BallotController', 'addCandidate');
        $r->post('/yonetim/candidates/delete/{id}', 'BallotController', 'removeCandidate');

        // Yönetim — Seçim ayarları
        $r->get('/yonetim/settings', 'ElectionController', 'settings');
        $r->post('/yonetim/settings', 'ElectionController', 'updateSettings');

        // Görevli
        $r->get('/gorevli', 'GorevliController', 'index');
        $r->post('/gorevli/search', 'GorevliController', 'search');
        $r->post('/gorevli/sign1/{id}', 'GorevliController', 'firstSign');
        $r->post('/gorevli/token/{id}', 'GorevliController', 'generateToken');
        $r->get('/gorevli/vote-status/{id}', 'GorevliController', 'checkVoteStatus');
        $r->post('/gorevli/sign2/{id}', 'GorevliController', 'secondSign');
        $r->get('/gorevli/members', 'GorevliController', 'memberList');

        // Sonuç
        $r->get('/sonuc', 'ResultController', 'index');
        $r->get('/sonuc/data', 'ResultController', 'data');
        $r->get('/sonuc/curtain', 'ResultController', 'curtain');
        $r->get('/sonuc/participation', 'ResultController', 'participation');

        // Oylama
        $r->get('/oy/verify', 'VoteController', 'verify');
        $r->post('/oy/verify', 'VoteController', 'verifyCheck');
        $r->get('/oy/dogrula', 'ReceiptController', 'show');
        $r->post('/oy/dogrula', 'ReceiptController', 'check');
        $r->get('/oy/{token}', 'VoteController', 'show');
        $r->post('/oy/{token}', 'VoteController', 'store');

        // Admin
        $r->get('/admin', 'AdminController', 'index');
        $r->get('/admin/log', 'AdminController', 'activityLog');
        $r->get('/admin/log/verify', 'AdminController', 'verifyLogIntegrity');
        $r->get('/admin/users', 'AdminController', 'users');
        $r->get('/admin/users/create', 'AdminController', 'createUser');
        $r->post('/admin/users/store', 'AdminController', 'storeUser');
        $r->get('/admin/users/edit/{id}', 'AdminController', 'editUser');
        $r->post('/admin/users/update/{id}', 'AdminController', 'updateUser');
        $r->post('/admin/users/delete/{id}', 'AdminController', 'deleteUser');
        $r->get('/admin/system', 'AdminController', 'systemStatus');
        $r->post('/admin/override', 'AdminController', 'overrideElection');
        $r->get('/admin/hash-export', 'AdminController', 'hashExport');
        $r->get('/admin/elections', 'AdminController', 'elections');
        $r->post('/admin/elections/store', 'AdminController', 'storeElection');
        $r->get('/admin/pdf', 'AdminController', 'downloadTutanak');
        $r->post('/admin/data/anonymize', 'AdminController', 'anonymizeOldData');

        // Test modu — sadece non-production'da kayıt edilir.
        if (!Config::isProduction()) {
            $r->get('/admin/test', 'TestModeController', 'index');
            $r->post('/admin/test/checks', 'TestModeController', 'runSystemChecks');
            $r->post('/admin/test/simulate', 'TestModeController', 'runTestElection');
            $r->post('/admin/test/cleanup', 'TestModeController', 'cleanup');
        }

        // Ana sayfa
        $r->get('/', 'ResultController', 'index');
    }
}
