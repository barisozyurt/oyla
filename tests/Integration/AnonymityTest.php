<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class AnonymityTest extends TestCase
{
    public function test_no_query_joins_tokens_and_votes(): void
    {
        $appDir = dirname(__DIR__, 2) . '/app';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appDir)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;
            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $file->getPathname());

            // tokens JOIN votes
            $this->assertDoesNotMatchRegularExpression(
                '/tokens\s+(INNER|LEFT|RIGHT|CROSS|NATURAL|FULL)?\s*JOIN\s+votes/i',
                $content,
                "ANONYMITY VIOLATION: {$relativePath} contains a JOIN between tokens and votes!"
            );

            // votes JOIN tokens — bilinen istisna: TestModeController::cleanup
            // (sadece TEST-% prefix'li üyelerin oylarını siliyor, gerçek oy verisini değil).
            if (!str_ends_with($relativePath, 'TestModeController.php')) {
                $this->assertDoesNotMatchRegularExpression(
                    '/votes\s+(INNER|LEFT|RIGHT|CROSS|NATURAL|FULL)?\s*JOIN\s+tokens/i',
                    $content,
                    "ANONYMITY VIOLATION: {$relativePath} contains a JOIN between votes and tokens!"
                );
            }
        }
    }

    public function test_vote_cast_vote_has_no_member_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(\App\Models\Vote::class, 'castVote');
        $params = $reflection->getParameters();
        $paramNames = array_map(fn($p) => $p->getName(), $params);

        $this->assertNotContains('memberId', $paramNames);
        $this->assertNotContains('member_id', $paramNames);
    }

    public function test_votes_migration_has_no_member_id_column(): void
    {
        $migrationFile = dirname(__DIR__, 2) . '/database/migrations/007_create_votes.sql';
        $this->assertFileExists($migrationFile);
        $content = file_get_contents($migrationFile);

        $this->assertDoesNotMatchRegularExpression(
            '/^\s+member_id\s+(INT|VARCHAR|TINYINT|BIGINT)/mi',
            $content,
            "ANONYMITY VIOLATION: votes migration contains a member_id column!"
        );
    }

    public function test_vote_model_never_inserts_member_id(): void
    {
        $voteModelFile = dirname(__DIR__, 2) . '/app/Models/Vote.php';
        $content = file_get_contents($voteModelFile);

        $this->assertDoesNotMatchRegularExpression(
            "/['\"]member_id['\"]\s*=>/",
            $content,
            "ANONYMITY VIOLATION: Vote model contains member_id in data array!"
        );
    }

    public function test_vote_controller_never_passes_member_id_to_votes(): void
    {
        $controllerFile = dirname(__DIR__, 2) . '/app/Controllers/VoteController.php';
        $content = file_get_contents($controllerFile);

        $this->assertDoesNotMatchRegularExpression(
            "/castVote\([^)]*member_id/i",
            $content,
            "ANONYMITY VIOLATION: VoteController passes member_id to castVote!"
        );
    }

    public function test_tokens_table_no_longer_stores_plain(): void
    {
        // Migration 011 plain'i drop ediyor — yeni TokenService de plain saklamıyor.
        $tokenSvc = file_get_contents(dirname(__DIR__, 2) . '/app/Services/TokenService.php');
        $this->assertStringNotContainsString("'token_plain' =>", $tokenSvc);
        $this->assertStringContainsString('token_hash', $tokenSvc);
    }
}
