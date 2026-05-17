-- =============================================================================
-- Migration 011 — token_plain kolonunu kaldır.
-- =============================================================================
-- Token plaintext'i artık DB'de saklanmıyor. Doğrulama HMAC üzerinden yapılır:
--   client gönderir: $tokenPlain
--   server hesaplar: HMAC(TOKEN_SECRET, $tokenPlain)
--   server arar    : WHERE token_hash = computed_hash
--
-- Eski sütun kalsa kötü amaçlı DB read'inde tokenları açığa çıkarırdı.
-- Bu migration backwards-incompatible'dır — eski tokenlar (kullanılmamışlar)
-- artık doğrulanamaz; yeni token üretilmelidir.
-- =============================================================================

ALTER TABLE tokens DROP INDEX idx_tokens_plain;
ALTER TABLE tokens DROP COLUMN token_plain;

-- Yeni: hash üzerinde benzersiz index (zaten UNIQUE'di ama hash-only modda
-- lookup pattern'inin doğrulaması için açıkça yorumla).
-- token_hash zaten UNIQUE NOT NULL — değişiklik yok.
