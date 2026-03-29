CREATE TABLE IF NOT EXISTS ballots (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    election_id   INT UNSIGNED NOT NULL,
    title         VARCHAR(100) NOT NULL,
    description   VARCHAR(255),
    quota         INT UNSIGNED NOT NULL COMMENT 'Seçilecek kişi sayısı',
    yedek_quota   INT UNSIGNED DEFAULT 0,
    sort_order    INT DEFAULT 0,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
