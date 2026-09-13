USE artbook;

-- 1. Einheitliche Mitglieder-Tabelle (n:m, alle Teilnehmer können Mitglieder haben)
CREATE TABLE teilnehmer_mitglied (
    gruppe_id   INT UNSIGNED NOT NULL,
    mitglied_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (gruppe_id, mitglied_id),
    CONSTRAINT fk_tm_gruppe   FOREIGN KEY (gruppe_id)   REFERENCES teilnehmer(id) ON DELETE CASCADE,
    CONSTRAINT fk_tm_mitglied FOREIGN KEY (mitglied_id) REFERENCES teilnehmer(id) ON DELETE CASCADE
);

-- 2. typ-Spalte entfernen (kein verlag_id vorhanden)
ALTER TABLE teilnehmer
    DROP INDEX idx_typ,
    DROP COLUMN typ;

-- 3. gruppe_typ → kategorie (NOT NULL)
--    NULL = Einzelperson/Künstler
UPDATE teilnehmer SET gruppe_typ = 'kuenstler' WHERE gruppe_typ IS NULL OR gruppe_typ = '';

ALTER TABLE teilnehmer
    CHANGE COLUMN gruppe_typ kategorie VARCHAR(50) NOT NULL DEFAULT 'kuenstler',
    ADD INDEX idx_kategorie (kategorie);
