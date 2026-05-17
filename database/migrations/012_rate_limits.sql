-- =============================================================================
-- Migration 012 — rate_limits tablosu (session-based rate limit'i değiştirir).
-- =============================================================================
-- Eski sistem $_SESSION'a yazıyordu — yeni cookie ile sıfırlanabiliyordu.
-- Yeni sistem (IP + endpoint + window) DB-backed; cookie değiştirmek
-- sınırı sıfırlamaz. Exponential backoff için failure_count tutulur.
-- =============================================================================

CREATE TABLE IF NOT EXISTS rate_limits (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bucket_key   VARCHAR(128) NOT NULL COMMENT 'ip:endpoint',
    attempt_at   DATETIME NOT NULL,
    success      TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_rate_bucket_time (bucket_key, attempt_at),
    INDEX idx_rate_attempt_at (attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limit_blocks (
    bucket_key   VARCHAR(128) PRIMARY KEY,
    blocked_until DATETIME NOT NULL,
    failure_count INT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_rate_blocked_until (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
