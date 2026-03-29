CREATE TABLE IF NOT EXISTS receipts (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    election_id      INT UNSIGNED NOT NULL,
    public_code      VARCHAR(20) UNIQUE NOT NULL COMMENT 'Üyeye SMS ile gönderilen makbuz kodu',
    commitment_hash  VARCHAR(64) NOT NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
