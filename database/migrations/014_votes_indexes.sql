-- =============================================================================
-- Migration 014 — Vote query performansı için ek index'ler
-- =============================================================================

ALTER TABLE votes
    ADD INDEX idx_votes_election (election_id),
    ADD INDEX idx_votes_token_hash (token_hash),
    ADD INDEX idx_votes_created_at (created_at);

ALTER TABLE activity_log
    ADD INDEX idx_activity_created_at (created_at);

-- members tablosunda son sign-up takibi için
ALTER TABLE members
    ADD INDEX idx_members_signed_at (signed_at);

-- receipts public_code lookup zaten UNIQUE — created_at için chronological sort
ALTER TABLE receipts
    ADD INDEX idx_receipts_election_created (election_id, created_at);
