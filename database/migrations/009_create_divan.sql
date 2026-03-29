CREATE TABLE IF NOT EXISTS divan (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    election_id   INT UNSIGNED NOT NULL,
    role          ENUM('baskan','uye','katip') NOT NULL,
    name          VARCHAR(100) NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
