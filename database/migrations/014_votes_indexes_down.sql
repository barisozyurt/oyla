ALTER TABLE votes
    DROP INDEX idx_votes_election,
    DROP INDEX idx_votes_token_hash,
    DROP INDEX idx_votes_created_at;
ALTER TABLE activity_log DROP INDEX idx_activity_created_at;
ALTER TABLE members DROP INDEX idx_members_signed_at;
ALTER TABLE receipts DROP INDEX idx_receipts_election_created;
