CREATE TABLE IF NOT EXISTS tokens (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    election_id   INT UNSIGNED NOT NULL,
    member_id     INT UNSIGNED NOT NULL,
    token_hash    VARCHAR(64) UNIQUE NOT NULL COMMENT 'HMAC-SHA256 hash',
    token_plain   VARCHAR(64) NOT NULL COMMENT 'QR/SMS için UUID token',
    used          TINYINT(1) DEFAULT 0,
    used_at       DATETIME,
    expires_at    DATETIME NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_tokens_plain (token_plain),
    INDEX idx_tokens_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
