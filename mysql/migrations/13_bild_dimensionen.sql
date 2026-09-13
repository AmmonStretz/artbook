-- Migration 13: Bildmaße in teilnehmer_bild speichern

ALTER TABLE teilnehmer_bild
  ADD COLUMN breite SMALLINT UNSIGNED NULL AFTER groesse_bytes,
  ADD COLUMN hoehe  SMALLINT UNSIGNED NULL AFTER breite;
