USE artbook;

CREATE TABLE IF NOT EXISTS teilnehmer_edit_token (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teilnehmer_id   INT UNSIGNED NOT NULL,
    token           VARCHAR(96)  NOT NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    used_at         TIMESTAMP    NULL DEFAULT NULL,
    UNIQUE KEY uq_token       (token),
    UNIQUE KEY uq_teilnehmer  (teilnehmer_id),
    CONSTRAINT fk_token_teilnehmer FOREIGN KEY (teilnehmer_id)
        REFERENCES teilnehmer(id) ON DELETE CASCADE
);
