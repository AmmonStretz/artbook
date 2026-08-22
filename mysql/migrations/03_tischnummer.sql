-- Migration 03: Tischnummer-Freitextfeld für Veranstaltungsteilnahmen
--
-- Statt der FK-basierten tisch_id wird eine einfache Freitextspalte
-- verwendet, da Tischnummern bei verschiedenen Teilnehmern doppelt
-- vorkommen dürfen.

ALTER TABLE veranstaltung_teilnahme
  ADD COLUMN tischnummer VARCHAR(50) NULL AFTER teilnehmer_id;
