CREATE TABLE IF NOT EXISTS candidates (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ballot_id     INT UNSIGNED NOT NULL,
    member_id     INT UNSIGNED,
    name          VARCHAR(100) NOT NULL,
    title         VARCHAR(100),
    photo_path    VARCHAR(255),
    candidate_no  VARCHAR(10),
    sort_order    INT DEFAULT 0,
    FOREIGN KEY (ballot_id) REFERENCES ballots(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
