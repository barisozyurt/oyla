<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

class App
{
    private static Router $router;

    /**
     * Bootstrap the application: load config, start session, register routes, dispatch.
     */
    public static function boot(): void
    {
        $basePath = dirname(__DIR__, 2);

        // Load .env
        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->load();
        }

        // Timezone — Turkey is UTC+3
        date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Europe/Istanbul');

        // Session with secure defaults
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        // Error reporting based on debug flag
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        // Build and dispatch router
        self::$router = new Router();
        self::registerRoutes();
        self::$router->dispatch(
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );
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
        $r->get('/oy/{token}', 'VoteController', 'show');
        $r->post('/oy/{token}', 'VoteController', 'store');

        // Admin
        $r->get('/admin', 'AdminController', 'index');
        $r->get('/admin/log', 'AdminController', 'activityLog');
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

        // Test modu
        $r->get('/admin/test', 'TestModeController', 'index');
        $r->post('/admin/test/checks', 'TestModeController', 'runSystemChecks');
        $r->post('/admin/test/simulate', 'TestModeController', 'runTestElection');
        $r->post('/admin/test/cleanup', 'TestModeController', 'cleanup');

        // Ana sayfa → sonuç ekranı
        $r->get('/', 'ResultController', 'index');
    }
}
