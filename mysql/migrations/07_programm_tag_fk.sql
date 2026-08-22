-- Migration 07: Programmpunkte an Veranstaltungstage koppeln
--   1. Unique Key auf veranstaltung_tag(veranstaltung_id, datum) als FK-Ziel
--   2. FK von veranstaltung_programm(veranstaltung_id, datum) → veranstaltung_tag
--      mit ON DELETE CASCADE: gelöschter Tag löscht automatisch seine Programmpunkte

ALTER TABLE veranstaltung_tag
  ADD UNIQUE KEY uq_vtag_vid_datum (veranstaltung_id, datum);

ALTER TABLE veranstaltung_programm
  ADD CONSTRAINT fk_prog_tag
  FOREIGN KEY (veranstaltung_id, datum)
  REFERENCES veranstaltung_tag(veranstaltung_id, datum)
  ON DELETE CASCADE;
