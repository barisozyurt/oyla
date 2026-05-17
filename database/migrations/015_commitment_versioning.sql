-- =============================================================================
-- Migration 015 — Commitment hash algoritma versiyonu + receipt salt
-- =============================================================================
-- CryptoService HMAC + KDF tabanlı yeni şemaya geçti. Versiyon kolonu
-- gelecekteki algoritma yükseltmelerinde hangi sürümle hesaplandığını işaretler.
--
-- NOT: MariaDB column_definition'da COMMENT, AFTER positioning'den ÖNCE gelmek
-- zorunda. Aksi halde "syntax error near 'COMMENT'" alınır.
-- =============================================================================

ALTER TABLE votes
    ADD COLUMN salt VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'Doğrulama için per-vote salt' AFTER commitment_hash,
    ADD COLUMN crypto_version VARCHAR(10) NOT NULL DEFAULT 'v1' COMMENT 'Crypto şema versiyonu' AFTER salt;

ALTER TABLE receipts
    ADD COLUMN crypto_version VARCHAR(10) NOT NULL DEFAULT 'v1' COMMENT 'Crypto şema versiyonu' AFTER commitment_hash;
