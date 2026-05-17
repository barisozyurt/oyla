-- Rollback: token_plain kolonunu geri ekle (boş olarak — eski plaintext kaybolmuştur).
ALTER TABLE tokens ADD COLUMN token_plain VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'DEPRECATED — yeni kayıtlar boş';
ALTER TABLE tokens ADD INDEX idx_tokens_plain (token_plain);
