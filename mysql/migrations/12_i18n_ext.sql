-- Migration 12: Englische Felder für Programmort und Bildmetadaten

ALTER TABLE veranstaltung_programm
  ADD COLUMN ort_name_en VARCHAR(255) NULL AFTER ort_name;

ALTER TABLE teilnehmer_bild
  ADD COLUMN titel_en    VARCHAR(500) NULL AFTER titel,
  ADD COLUMN alt_text_en VARCHAR(500) NULL AFTER alt_text;
