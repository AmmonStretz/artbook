-- Migration 08: Sichtbarkeit von Programm und Teilnehmern pro Veranstaltung
ALTER TABLE veranstaltung
  ADD COLUMN programm_sichtbar    TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN teilnehmer_sichtbar  TINYINT(1) NOT NULL DEFAULT 1;
