-- =============================================================================
-- Migration 016 — KVKK uyumu: anonymized_at kolonu
-- =============================================================================
-- Seçim kapandıktan X gün sonra (PII_RETENTION_DAYS) tc_kimlik/phone/email
-- alanları silinir, name "[Anonim]" olur. anonymized_at o anın timestamp'ini taşır.
-- =============================================================================

ALTER TABLE members
    ADD COLUMN anonymized_at DATETIME DEFAULT NULL AFTER created_at,
    ADD INDEX idx_members_anonymized (anonymized_at);
