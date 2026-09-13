CREATE TABLE IF NOT EXISTS veranstaltung_gruppe_mitglied (
    veranstaltung_id INT UNSIGNED NOT NULL,
    gruppe_id        INT UNSIGNED NOT NULL,
    mitglied_id      INT UNSIGNED NOT NULL,
    PRIMARY KEY (veranstaltung_id, gruppe_id, mitglied_id),
    FOREIGN KEY (veranstaltung_id) REFERENCES veranstaltung(id)  ON DELETE CASCADE,
    FOREIGN KEY (gruppe_id)        REFERENCES teilnehmer(id)      ON DELETE CASCADE,
    FOREIGN KEY (mitglied_id)      REFERENCES teilnehmer(id)      ON DELETE CASCADE
);
