<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class AnonymityTest extends TestCase
{
    /**
     * Scan ALL PHP files in app/ for any SQL query that JOINs tokens and votes.
     * This is a static analysis test — no DB needed.
     */
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

            // Check for tokens JOIN votes
            $this->assertDoesNotMatchRegularExpression(
                '/tokens\s+(INNER|LEFT|RIGHT|CROSS|NATURAL|FULL)?\s*JOIN\s+votes/i',
                $content,
                "ANONYMITY VIOLATION: {$relativePath} contains a JOIN between tokens and votes!"
            );

            // Check for votes JOIN tokens
            $this->assertDoesNotMatchRegularExpression(
                '/votes\s+(INNER|LEFT|RIGHT|CROSS|NATURAL|FULL)?\s*JOIN\s+tokens/i',
                $content,
                "ANONYMITY VIOLATION: {$relativePath} contains a JOIN between votes and tokens!"
            );
        }
    }

    /**
     * Verify the Vote model's castVote method does NOT accept member_id.
     */
    public function test_vote_cast_vote_has_no_member_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(\App\Models\Vote::class, 'castVote');
        $params = $reflection->getParameters();
        $paramNames = array_map(fn($p) => $p->getName(), $params);

        $this->assertNotContains('memberId', $paramNames);
        $this->assertNotContains('member_id', $paramNames);
    }

    /**
     * Verify the votes migration SQL file does not contain member_id as a column.
     */
    public function test_votes_migration_has_no_member_id_column(): void
    {
        $migrationFile = dirname(__DIR__, 2) . '/database/migrations/007_create_votes.sql';
        $this->assertFileExists($migrationFile);

        $content = file_get_contents($migrationFile);

        // Should NOT match member_id as a column definition (but may appear in comments)
        // A column definition would look like: member_id INT or member_id VARCHAR etc
        $this->assertDoesNotMatchRegularExpression(
            '/^\s+member_id\s+(INT|VARCHAR|TINYINT|BIGINT)/mi',
            $content,
            "ANONYMITY VIOLATION: votes migration contains a member_id column!"
        );
    }

    /**
     * Verify Vote model source code has no member_id in create/insert operations.
     */
    public function test_vote_model_never_inserts_member_id(): void
    {
        $voteModelFile = dirname(__DIR__, 2) . '/app/Models/Vote.php';
        $content = file_get_contents($voteModelFile);

        // Check that no array key 'member_id' is used in create() calls
        $this->assertDoesNotMatchRegularExpression(
            "/['\"]member_id['\"]\s*=>/",
            $content,
            "ANONYMITY VIOLATION: Vote model contains member_id in data array!"
        );
    }

    /**
     * Verify VoteController does not insert member_id into votes.
     */
    public function test_vote_controller_never_passes_member_id_to_votes(): void
    {
        $controllerFile = dirname(__DIR__, 2) . '/app/Controllers/VoteController.php';
        $content = file_get_contents($controllerFile);

        // castVote call should not include member_id
        $this->assertDoesNotMatchRegularExpression(
            "/castVote\([^)]*member_id/i",
            $content,
            "ANONYMITY VIOLATION: VoteController passes member_id to castVote!"
        );
    }
}
