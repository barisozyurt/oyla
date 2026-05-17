-- =============================================================================
-- Migration 015 — Commitment hash algoritma versiyonu + receipt salt
-- =============================================================================
-- CryptoService HMAC + KDF tabanlı yeni şemaya geçti. Versiyon kolonu
-- gelecekteki algoritma yükseltmelerinde hangi sürümle hesaplandığını işaretler.
-- =============================================================================

ALTER TABLE votes
    ADD COLUMN salt VARCHAR(64) NOT NULL DEFAULT '' AFTER commitment_hash COMMENT 'Doğrulama için per-vote salt',
    ADD COLUMN crypto_version VARCHAR(10) NOT NULL DEFAULT 'v1' AFTER salt;

ALTER TABLE receipts
    ADD COLUMN crypto_version VARCHAR(10) NOT NULL DEFAULT 'v1' AFTER commitment_hash;
