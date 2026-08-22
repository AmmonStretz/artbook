USE artbook;

-- Veranstaltungen (Buchmessen etc.)
CREATE TABLE IF NOT EXISTS veranstaltung (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    beschreibung TEXT,
    -- Adresse
    strasse     VARCHAR(255),
    plz         VARCHAR(20),
    ort         VARCHAR(255),
    land        VARCHAR(100) DEFAULT 'Deutschland',
    -- Bilder: gespeicherte Dateinamen unterhalb uploads/veranstaltungen/
    bild_klein  VARCHAR(255),
    bild_gross  VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tage einer Veranstaltung (mindestens 1, erzwungen per App-Logik)
CREATE TABLE IF NOT EXISTS veranstaltung_tag (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    veranstaltung_id INT UNSIGNED NOT NULL,
    datum           DATE NOT NULL,
    startzeit       TIME NOT NULL,
    endzeit         TIME NOT NULL,
    CONSTRAINT fk_vtag_veranstaltung FOREIGN KEY (veranstaltung_id)
        REFERENCES veranstaltung(id) ON DELETE CASCADE,
    INDEX idx_vtag_datum (datum)
);

-- Bereiche (Stockwerk, Ausstellungsraum …) — optional pro Veranstaltung
CREATE TABLE IF NOT EXISTS bereich (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    veranstaltung_id INT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    sortierung      TINYINT UNSIGNED DEFAULT 0,
    CONSTRAINT fk_bereich_veranstaltung FOREIGN KEY (veranstaltung_id)
        REFERENCES veranstaltung(id) ON DELETE CASCADE
);

-- Tische — gehören zu einer Veranstaltung, optional einem Bereich zugeordnet
CREATE TABLE IF NOT EXISTS tisch (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    veranstaltung_id INT UNSIGNED NOT NULL,
    bereich_id      INT UNSIGNED NULL,
    nummer          VARCHAR(20) NOT NULL,
    beschreibung    TEXT,
    CONSTRAINT fk_tisch_veranstaltung FOREIGN KEY (veranstaltung_id)
        REFERENCES veranstaltung(id) ON DELETE CASCADE,
    CONSTRAINT fk_tisch_bereich FOREIGN KEY (bereich_id)
        REFERENCES bereich(id) ON DELETE SET NULL,
    -- Tischnummer eindeutig pro Veranstaltung
    UNIQUE KEY uq_tisch_nr (veranstaltung_id, nummer)
);

-- Teilnehmer: Künstler oder Verlage
-- Künstler können optional einem Verlag angehören (verlag_id → id vom Typ 'verlag')
CREATE TABLE IF NOT EXISTS teilnehmer (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    typ         ENUM('kuenstler', 'verlag') NOT NULL,
    name        VARCHAR(255) NOT NULL,
    beschreibung TEXT,
    verlag_id   INT UNSIGNED NULL COMMENT 'Nur für Künstler: zugehöriger Verlag',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_teilnehmer_verlag FOREIGN KEY (verlag_id)
        REFERENCES teilnehmer(id) ON DELETE SET NULL,
    INDEX idx_typ (typ),
    INDEX idx_verlag (verlag_id)
);

-- Teilnahme: welcher Teilnehmer bei welcher Veranstaltung, an welchem Tisch
-- Mehrere Teilnehmer können am selben Tisch sein (tisch_id ist keine UNIQUE-Einschränkung)
CREATE TABLE IF NOT EXISTS veranstaltung_teilnahme (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    veranstaltung_id INT UNSIGNED NOT NULL,
    teilnehmer_id   INT UNSIGNED NOT NULL,
    tisch_id        INT UNSIGNED NULL,
    -- Jeder Teilnehmer nur einmal pro Veranstaltung
    UNIQUE KEY uq_teilnahme (veranstaltung_id, teilnehmer_id),
    CONSTRAINT fk_vt_veranstaltung FOREIGN KEY (veranstaltung_id)
        REFERENCES veranstaltung(id) ON DELETE CASCADE,
    CONSTRAINT fk_vt_teilnehmer FOREIGN KEY (teilnehmer_id)
        REFERENCES teilnehmer(id) ON DELETE CASCADE,
    CONSTRAINT fk_vt_tisch FOREIGN KEY (tisch_id)
        REFERENCES tisch(id) ON DELETE SET NULL
);

-- Bilder für Teilnehmer (Upload-Pfade relativ zu uploads/teilnehmer/)
CREATE TABLE IF NOT EXISTS teilnehmer_bild (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teilnehmer_id       INT UNSIGNED NOT NULL,
    dateiname           VARCHAR(255) NOT NULL COMMENT 'Gespeicherter Dateiname auf Disk',
    dateiname_original  VARCHAR(255)          COMMENT 'Originaler Upload-Dateiname',
    mime_type           VARCHAR(100),
    groesse_bytes       INT UNSIGNED,
    sortierung          TINYINT UNSIGNED DEFAULT 0,
    alt_text            VARCHAR(255),
    uploaded_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bild_teilnehmer FOREIGN KEY (teilnehmer_id)
        REFERENCES teilnehmer(id) ON DELETE CASCADE,
    INDEX idx_bild_teilnehmer (teilnehmer_id)
);
