ALTER TABLE activity_log
    DROP INDEX idx_activity_entry_hash,
    DROP COLUMN actor_username,
    DROP COLUMN entry_hash,
    DROP COLUMN prev_hash;
