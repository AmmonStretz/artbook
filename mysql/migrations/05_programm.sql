-- ============================================================
-- Programm pro Veranstaltungstag
-- ============================================================

CREATE TABLE IF NOT EXISTS veranstaltung_programm (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    veranstaltung_id INT UNSIGNED NOT NULL,
    datum            DATE         NOT NULL,
    uhrzeit          TIME         NOT NULL,
    titel            VARCHAR(500) NOT NULL,
    beschreibung     TEXT         NULL,
    ort_name         VARCHAR(100) NULL,
    PRIMARY KEY (id),
    INDEX idx_vp_date (veranstaltung_id, datum, uhrzeit),
    FOREIGN KEY (veranstaltung_id) REFERENCES veranstaltung(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS programm_teilnehmer (
    programm_id   INT UNSIGNED NOT NULL,
    teilnehmer_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (programm_id, teilnehmer_id),
    FOREIGN KEY (programm_id)   REFERENCES veranstaltung_programm(id) ON DELETE CASCADE,
    FOREIGN KEY (teilnehmer_id) REFERENCES teilnehmer(id)             ON DELETE CASCADE
);
