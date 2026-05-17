-- =============================================================================
-- Migration 013 — activity_log: tamper-evident hash chain + HMAC imza
-- =============================================================================
-- Her kayıt önceki kaydın hash'ini içerir (block-chain mantığı). Bir kayıt
-- silindiğinde ya da değiştirildiğinde sonraki tüm hash'ler bozulur.
-- entry_hash = HMAC(APP_SECRET, prev_hash || action || description || ip || ts)
-- =============================================================================

ALTER TABLE activity_log
    ADD COLUMN prev_hash CHAR(64) DEFAULT NULL AFTER ip_address,
    ADD COLUMN entry_hash CHAR(64) DEFAULT NULL AFTER prev_hash,
    ADD COLUMN actor_username VARCHAR(50) DEFAULT NULL AFTER entry_hash,
    ADD INDEX idx_activity_entry_hash (entry_hash);
