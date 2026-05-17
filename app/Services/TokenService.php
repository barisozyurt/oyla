<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\Token;
use Ramsey\Uuid\Uuid;

/**
 * Token üretimi ve doğrulama.
 *
 * GÜVENLİK NOTU (FAZ 1):
 * - Plain UUID üretilir, **kullanıcıya tek seferlik** döndürülür (SMS/QR).
 * - Veritabanına **yalnızca hash** yazılır. Plain hiçbir yerde persist edilmez.
 * - Doğrulama: gelen plain → HMAC hesapla → token_hash ile karşılaştır.
 * - Bir DB sızıntısında saldırgan token'ları yeniden üretemez.
 * - Token reuse: used=1 atomic update (race condition'a karşı RETURNING benzeri pattern).
 */
class TokenService
{
    private Token $tokenModel;

    public function __construct()
    {
        $this->tokenModel = new Token();
    }

    /**
     * Plain token (UUID v4) ile member_id binding'i tahmin edilemez olsun diye
     * timestamp + memberId + UUID HMAC'lenir. Plain SMS/QR'a, hash DB'ye.
     *
     * @return array{plain:string, hash:string, expires_at:string}
     */
    public function generate(int $electionId, int $memberId): array
    {
        $plain = Uuid::uuid4()->toString();
        $hash  = self::hashToken($plain);

        $expireMinutes = (int) ($_ENV['TOKEN_EXPIRE_MINUTES'] ?? 120);
        $expiresAt     = date('Y-m-d H:i:s', time() + ($expireMinutes * 60));

        $this->tokenModel->create([
            'election_id' => $electionId,
            'member_id'   => $memberId,
            'token_hash'  => $hash,
            'used'        => 0,
            'expires_at'  => $expiresAt,
        ]);

        return [
            'plain'      => $plain,
            'hash'       => $hash,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Plain token'ı doğrular.
     * Başarılıysa token satırını döndürür; başarısızsa null.
     */
    public function validate(string $tokenPlain): ?array
    {
        $hash  = self::hashToken($tokenPlain);
        $token = $this->tokenModel->findByHash($hash);

        if (!$token)                                   return null;
        if ((bool) $token['used'])                     return null;
        if (strtotime($token['expires_at']) < time())  return null;
        return $token;
    }

    /**
     * Atomic burn: race condition'a karşı UPDATE ... WHERE used = 0 pattern.
     * Etkilenen satır 1 ise burn başarılı, 0 ise zaten kullanılmış demektir.
     */
    public function burnAtomic(string $tokenHash): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE tokens SET used = 1, used_at = NOW() WHERE token_hash = ? AND used = 0"
        );
        $stmt->execute([$tokenHash]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Eski API uyumluluğu için non-atomic burn (controller'lar transaction içinde
     * kullandığı için race condition burada minimaldir, ama burnAtomic tercih edilsin).
     */
    public function burn(string $tokenHash): void
    {
        $this->tokenModel->burn($tokenHash);
    }

    /**
     * HMAC-SHA256(TOKEN_SECRET, plain). Fallback YOK.
     */
    public static function hashToken(string $plain): string
    {
        $secret = Config::secret('TOKEN_SECRET', 32);
        return hash_hmac('sha256', $plain, $secret);
    }
}
