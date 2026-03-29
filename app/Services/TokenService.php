<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Token;
use Ramsey\Uuid\Uuid;

class TokenService
{
    private Token $tokenModel;

    public function __construct()
    {
        $this->tokenModel = new Token();
    }

    public function generate(int $electionId, int $memberId): array
    {
        $uuid = Uuid::uuid4()->toString();
        $secret = $_ENV['TOKEN_SECRET'] ?? 'default_secret';
        $hash = hash_hmac('sha256', $uuid . $memberId . time(), $secret);
        $expireMinutes = (int) ($_ENV['TOKEN_EXPIRE_MINUTES'] ?? 120);
        $expiresAt = date('Y-m-d H:i:s', time() + ($expireMinutes * 60));

        $this->tokenModel->create([
            'election_id' => $electionId,
            'member_id' => $memberId,
            'token_hash' => $hash,
            'token_plain' => $uuid,
            'used' => 0,
            'expires_at' => $expiresAt,
        ]);

        return [
            'plain' => $uuid,
            'hash' => $hash,
            'expires_at' => $expiresAt,
        ];
    }

    public function validate(string $tokenPlain): ?array
    {
        $token = $this->tokenModel->findByPlain($tokenPlain);
        if (!$token) return null;
        if ($token['used']) return null;
        if (strtotime($token['expires_at']) < time()) return null;
        return $token;
    }

    public function burn(string $tokenHash): void
    {
        $this->tokenModel->burn($tokenHash);
    }
}
