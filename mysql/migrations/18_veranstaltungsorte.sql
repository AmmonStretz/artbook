-- Veranstaltungsorte: wiederverwendbare Orte mit Adresse und Koordinaten
CREATE TABLE IF NOT EXISTS veranstaltungsort (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    strasse     VARCHAR(255),
    plz         VARCHAR(20),
    ort         VARCHAR(255),
    land        VARCHAR(100) DEFAULT 'Deutschland',
    ort_url     TEXT,
    lat         DECIMAL(10, 7),
    lng         DECIMAL(10, 7),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Referenz auf Veranstaltungsort in Veranstaltungen (optional, Adressfelder bleiben erhalten)
ALTER TABLE veranstaltung
    ADD COLUMN veranstaltungsort_id INT UNSIGNED NULL AFTER ort_url,
    ADD CONSTRAINT fk_veranstaltung_ort
        FOREIGN KEY (veranstaltungsort_id)
        REFERENCES veranstaltungsort(id)
        ON DELETE SET NULL;
