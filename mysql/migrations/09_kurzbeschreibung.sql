-- Migration 09: Kurzbeschreibung für Veranstaltungen
ALTER TABLE veranstaltung
  ADD COLUMN kurzbeschreibung TEXT NULL AFTER beschreibung;
