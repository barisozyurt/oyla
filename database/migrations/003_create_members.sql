CREATE TABLE IF NOT EXISTS members (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    election_id   INT UNSIGNED NOT NULL,
    sicil_no      VARCHAR(20),
    tc_kimlik     VARCHAR(11),
    name          VARCHAR(100) NOT NULL,
    phone         VARCHAR(20),
    email         VARCHAR(100),
    photo_path    VARCHAR(255),
    role          ENUM('uye','yk_adayi','denetleme_adayi','disiplin_adayi') DEFAULT 'uye',
    status        ENUM('waiting','signed','done') DEFAULT 'waiting',
    signed_at     DATETIME,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    INDEX idx_members_election_tc (election_id, tc_kimlik),
    INDEX idx_members_election_sicil (election_id, sicil_no),
    INDEX idx_members_election_status (election_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
