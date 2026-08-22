-- Migration 04: sichtbar-Flag für Veranstaltungen
--              + tischnummer falls Migration 03 noch nicht ausgeführt wurde

ALTER TABLE veranstaltung_teilnahme
  ADD COLUMN IF NOT EXISTS tischnummer VARCHAR(50) NULL AFTER teilnehmer_id;

ALTER TABLE veranstaltung
  ADD COLUMN sichtbar TINYINT(1) NOT NULL DEFAULT 1 AFTER name;
