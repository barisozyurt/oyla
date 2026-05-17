<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Queue;
use App\Services\ActivityLogService;

/**
 * /health endpoint — public, monitoring/uptime probe için.
 * Kısa JSON döndürür; hassas detay vermez.
 *
 * Detaylı sistem durumu için /admin/system (auth gerekir) kullanılır.
 */
class HealthController extends Controller
{
    public function index(): void
    {
        $checks = [];
        $ok = true;

        // 1. Database
        try {
            $db = Database::getInstance();
            $db->prepare('SELECT 1')->execute();
            $checks['db'] = 'ok';
        } catch (\Throwable $e) {
            $checks['db'] = 'fail';
            $ok = false;
        }

        // 2. Disk (uploads writable?)
        $uploadsDir = defined('PUBLIC_PATH')
            ? PUBLIC_PATH . '/uploads'
            : (dirname(__DIR__, 2) . '/public/uploads');
        $checks['disk'] = is_writable($uploadsDir) ? 'ok' : 'fail';
        if ($checks['disk'] === 'fail') $ok = false;

        // 3. Queue
        $checks['queue'] = 'ok';
        try {
            $stats = Queue::stats();
            if ($stats['pending'] > 1000) {
                $checks['queue'] = 'warn';
            }
        } catch (\Throwable) {
            $checks['queue'] = 'fail';
        }

        // 4. Audit log present
        try {
            $auditDb = Database::getInstance();
            $stmt = $auditDb->prepare('SELECT COUNT(*) c FROM activity_log');
            $stmt->execute();
            $checks['audit_log_present'] = ((int) $stmt->fetch()['c'] >= 0) ? 'ok' : 'fail';
        } catch (\Throwable) {
            $checks['audit_log_present'] = 'fail';
        }

        $response = [
            'status'    => $ok ? 'ok' : 'degraded',
            'env'       => Config::env(),
            'version'   => 'v0.2',
            'ts'        => date('c'),
            'checks'    => $checks,
        ];

        $this->json($response, $ok ? 200 : 503);
    }
}
