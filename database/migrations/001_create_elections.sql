CREATE TABLE IF NOT EXISTS elections (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(255) NOT NULL,
    description   TEXT,
    status        ENUM('draft','test','open','closed') DEFAULT 'draft',
    test_mode     TINYINT(1) DEFAULT 0,
    test_log      JSON,
    started_at    DATETIME,
    closed_at     DATETIME,
    created_by    INT UNSIGNED,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
