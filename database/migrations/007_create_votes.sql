-- KRİTİK: Bu tabloda member_id YOKTUR.
-- tokens ve votes tabloları ASLA JOIN yapılmaz.
-- Bu, sistemin temel anonimlik garantisidir.
CREATE TABLE IF NOT EXISTS votes (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    election_id      INT UNSIGNED NOT NULL,
    ballot_id        INT UNSIGNED NOT NULL,
    token_hash       VARCHAR(64) NOT NULL COMMENT 'tokens.token_hash ile eşleşir ama JOIN yapılmaz',
    encrypted_choice JSON NOT NULL COMMENT 'Seçilen aday ID listesi (plaintext JSON — isim spec kaynaklı)',
    commitment_hash  VARCHAR(64) NOT NULL COMMENT 'SHA256(choice+salt+token)',
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id),
    FOREIGN KEY (ballot_id) REFERENCES ballots(id),
    INDEX idx_votes_election_ballot (election_id, ballot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
