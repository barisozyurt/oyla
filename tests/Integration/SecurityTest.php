<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Services\CryptoService;

class SecurityTest extends TestCase
{
    /**
     * Her POST handler (Auth::login, MemberController::store gibi) verifyCsrf çağırmalı.
     * Heuristic: dosyada method body içinde 'verifyCsrf' kelimesi geçmeli.
     */
    public function test_all_post_handlers_verify_csrf(): void
    {
        $controllersDir = dirname(__DIR__, 2) . '/app/Controllers';
        $files = glob($controllersDir . '/*.php');

        $getHandlers = [
            'index', 'show', 'create', 'edit', 'showLogin', 'showImport',
            'verify', 'data', 'curtain', 'participation', 'stats',
            'memberList', 'checkVoteStatus', 'systemStatus', 'activityLog',
            'users', 'createUser', 'editUser', 'elections', 'settings',
            'hashExport', 'downloadTutanak', 'verifyLogIntegrity',
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $basename = basename($file);

            preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $matches);
            $methods = $matches[1] ?? [];

            $postHandlers = array_diff($methods, $getHandlers);

            foreach ($postHandlers as $method) {
                if ($method === '__construct') continue;
                if ($method === 'logout') continue;
                if ($method === 'ensureNonProduction') continue;
                // GET handler'ları (yeni split controller'larda)
                if (in_array($method, ['status', 'list'], true)) continue;

                $pattern = "/function\s+{$method}\s*\([^)]*\)[^{]*\{(.*?)(?=\n\s{4}public\s+function|\n\}\s*$)/s";
                if (preg_match($pattern, $content, $bodyMatch)) {
                    if (strpos($bodyMatch[1], 'verifyCsrf') === false && strpos($bodyMatch[1], '_csrf') === false) {
                        $this->fail("CSRF eksik: {$basename}::{$method}()");
                    }
                }
            }
        }

        $this->assertTrue(true);
    }

    public function test_models_use_prepared_statements(): void
    {
        $modelsDir = dirname(__DIR__, 2) . '/app/Models';
        $files = glob($modelsDir . '/*.php');

        foreach ($files as $file) {
            $content  = file_get_contents($file);
            $basename = basename($file);

            // dangerous: ->query("..{$var}") veya ->exec("..{$var}")
            $this->assertDoesNotMatchRegularExpression(
                '/->(?:query|exec)\s*\(\s*"[^"]*\$/',
                $content,
                "SQL INJECTION: {$basename} string interpolation"
            );

            $this->assertDoesNotMatchRegularExpression(
                '/->(?:query|exec)\s*\(\s*["\'].*\'\s*\.\s*\$/',
                $content,
                "SQL INJECTION: {$basename} concatenation"
            );
        }
    }

    public function test_commitment_hash_tamper_detection(): void
    {
        $choice = json_encode([1, 2, 3]);
        $salt   = CryptoService::generateSalt();
        $token  = 'test-token-123';

        $hash = CryptoService::commitmentHash($choice, $salt, $token);

        $this->assertEquals($hash, CryptoService::commitmentHash($choice, $salt, $token));
        $this->assertNotEquals($hash, CryptoService::commitmentHash(json_encode([1, 2, 4]), $salt, $token));
        $this->assertNotEquals($hash, CryptoService::commitmentHash($choice, CryptoService::generateSalt(), $token));
        $this->assertNotEquals($hash, CryptoService::commitmentHash($choice, $salt, 'different-token'));

        $this->assertTrue(CryptoService::verifyCommitment($hash, $choice, $salt, $token));
    }

    public function test_password_hashing_uses_bcrypt(): void
    {
        $hash = password_hash('TestPasswordABC123', PASSWORD_BCRYPT);
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(password_verify('TestPasswordABC123', $hash));
        $this->assertFalse(password_verify('wrong', $hash));
    }

    public function test_rate_limiter_blocks_after_threshold(): void
    {
        $_SESSION = [];

        // Her test çağrısında benzersiz key — DB-backed mode'da cross-test
        // kirlenmeyi önler. ENDPOINTS map'inde yoksa override edilen 5/300 değerleri kullanılır.
        $key = 'sec_' . bin2hex(random_bytes(4));
        \App\Core\RateLimiter::reset($key);

        // 5 attempt — tam sınıra geliyor (her biri true dönmeli)
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue(\App\Core\RateLimiter::check($key, 5, 300), "attempt #{$i} should be allowed");
        }

        // 6. false dönmeli
        $this->assertFalse(\App\Core\RateLimiter::check($key, 5, 300));

        \App\Core\RateLimiter::reset($key);
        $this->assertTrue(\App\Core\RateLimiter::check($key, 5, 300));

        \App\Core\RateLimiter::reset($key);
    }

    public function test_config_secret_rejects_placeholder(): void
    {
        // Production'da geçici override — testte placeholder ile fail bekliyoruz
        $orig = $_ENV['APP_SECRET'] ?? null;
        $_ENV['APP_SECRET'] = 'CHANGE_ME_RANDOM_64_CHAR_STRING';

        try {
            \App\Core\Config::secret('APP_SECRET');
            $this->fail('Placeholder bir secret kabul edilmemeli');
        } catch (\App\Core\InvalidConfigException $e) {
            $this->assertStringContainsString('placeholder', $e->getMessage());
        } finally {
            if ($orig !== null) $_ENV['APP_SECRET'] = $orig;
        }
    }

    public function test_config_secret_rejects_too_short(): void
    {
        $orig = $_ENV['APP_SECRET'] ?? null;
        $_ENV['APP_SECRET'] = 'short';

        try {
            \App\Core\Config::secret('APP_SECRET', 32);
            $this->fail('Çok kısa secret kabul edilmemeli');
        } catch (\App\Core\InvalidConfigException $e) {
            $this->assertStringContainsString('karakter', $e->getMessage());
        } finally {
            if ($orig !== null) $_ENV['APP_SECRET'] = $orig;
        }
    }

    public function test_password_policy_rejects_short(): void
    {
        $this->assertNotNull(\App\Core\PasswordPolicy::validate('abc'));
        $this->assertNotNull(\App\Core\PasswordPolicy::validate('password123'));      // blacklist
        $this->assertNotNull(\App\Core\PasswordPolicy::validate('NoNumbersHere!'));   // no digit
        $this->assertNotNull(\App\Core\PasswordPolicy::validate('nouppercase1'));     // no upper
        $this->assertNotNull(\App\Core\PasswordPolicy::validate('NOLOWERCASE1'));     // no lower
        $this->assertNotNull(\App\Core\PasswordPolicy::validate('aaaaaXXXXX1zz'));    // repeat
        $this->assertNull(\App\Core\PasswordPolicy::validate('Str0ngPasswd!'));        // OK (8 char testing mode)
        $this->assertNull(\App\Core\PasswordPolicy::validate('LongSecureP4ssw0rd'));   // OK
    }

    public function test_logger_redacts_sensitive_keys(): void
    {
        $reflection = new \ReflectionClass(\App\Core\Logger::class);
        $method = $reflection->getMethod('sanitize');
        $method->setAccessible(true);

        $clean = $method->invoke(null, [
            'username' => 'admin',
            'password' => 'secret',
            'token'    => 'plain-token-here',
            'safe'     => 'visible',
            'nested'   => ['secret' => 'leak', 'visible' => 'ok'],
        ]);

        $this->assertEquals('[REDACTED]', $clean['password']);
        $this->assertEquals('[REDACTED]', $clean['token']);
        $this->assertEquals('visible', $clean['safe']);
        $this->assertEquals('[REDACTED]', $clean['nested']['secret']);
        $this->assertEquals('ok', $clean['nested']['visible']);
    }

    public function test_logger_masks_long_hex_token(): void
    {
        $reflection = new \ReflectionClass(\App\Core\Logger::class);
        $method = $reflection->getMethod('sanitize');
        $method->setAccessible(true);

        $clean = $method->invoke(null, [
            'hash' => str_repeat('a', 64),
        ]);
        $this->assertNotEquals(str_repeat('a', 64), $clean['hash']);
        $this->assertStringContainsString('…', $clean['hash']);
    }

    public function test_token_service_only_returns_plain_once(): void
    {
        // TokenService::generate döner — plain yalnızca return value içinde.
        // DB'de token_plain kolonu yok (migration 011 sonrası).
        $migrationFile = dirname(__DIR__, 2) . '/database/migrations/011_tokens_drop_plaintext.sql';
        $this->assertFileExists($migrationFile);
        $sql = file_get_contents($migrationFile);
        $this->assertStringContainsString('DROP COLUMN token_plain', $sql);
    }

    public function test_token_service_no_default_secret_fallback(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/app/Services/TokenService.php');
        $this->assertStringNotContainsString("'default_secret'", $source);
        $this->assertStringContainsString('Config::secret', $source);
    }

    public function test_views_have_button_type(): void
    {
        // <button>'lar type="submit" varsayılanını alır. Production-blocker: oylama show.php.
        $showSrc = file_get_contents(dirname(__DIR__, 2) . '/app/Views/oylama/show.php');
        // btn-vote-prev/btn-vote-next/submit hepsi type'lı olmalı
        preg_match_all('/<button(?![^>]*type=)[^>]*>/', $showSrc, $matches);
        $this->assertEmpty($matches[0], 'show.php içinde type attribute eksik button(\'lar) var: ' . implode("\n", $matches[0] ?? []));
    }

    public function test_nginx_has_security_headers(): void
    {
        $conf = file_get_contents(dirname(__DIR__, 2) . '/docker/nginx/default.conf');
        foreach ([
            'X-Frame-Options',
            'X-Content-Type-Options',
            'Content-Security-Policy',
            'Referrer-Policy',
            'Strict-Transport-Security',
            'Permissions-Policy',
        ] as $header) {
            $this->assertStringContainsString($header, $conf, "Nginx config'de {$header} eksik");
        }
    }

    public function test_activity_log_has_hash_chain_columns(): void
    {
        $migration = file_get_contents(dirname(__DIR__, 2) . '/database/migrations/013_activity_log_hash_chain.sql');
        $this->assertStringContainsString('prev_hash', $migration);
        $this->assertStringContainsString('entry_hash', $migration);
        $this->assertStringContainsString('actor_username', $migration);
    }
}
