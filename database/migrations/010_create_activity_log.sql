CREATE TABLE IF NOT EXISTS activity_log (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    election_id   INT UNSIGNED,
    action        VARCHAR(100) NOT NULL,
    description   TEXT,
    ip_address    VARCHAR(45),
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_election (election_id),
    INDEX idx_activity_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
