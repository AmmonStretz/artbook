-- Migration 06: Danke-Text für die Nachher-Phase der aktiven Veranstaltung
ALTER TABLE meta ADD COLUMN nachher_text TEXT NULL;
