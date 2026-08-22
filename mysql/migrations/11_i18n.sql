-- Migration 11: Zweisprachigkeit – englische Felder für dynamische Inhalte

ALTER TABLE veranstaltung
  ADD COLUMN name_en             VARCHAR(255) NULL AFTER name,
  ADD COLUMN kurzbeschreibung_en TEXT         NULL AFTER kurzbeschreibung,
  ADD COLUMN beschreibung_en     TEXT         NULL AFTER beschreibung;

ALTER TABLE teilnehmer
  ADD COLUMN beschreibung_en TEXT NULL AFTER beschreibung;

ALTER TABLE veranstaltung_programm
  ADD COLUMN titel_en        VARCHAR(500) NULL AFTER titel,
  ADD COLUMN beschreibung_en TEXT         NULL AFTER beschreibung;

ALTER TABLE meta
  ADD COLUMN einladung_en    TEXT NULL AFTER einladung,
  ADD COLUMN impressum_en    TEXT NULL AFTER impressum,
  ADD COLUMN nachher_text_en TEXT NULL AFTER nachher_text;
