-- ============================================================
-- Programm-Dummy-Daten
-- ============================================================

-- IDs per Name nachschlagen (robust bei unterschiedlichen Auto-Increment-Werten)
SET @vid_muenchen  = (SELECT id FROM veranstaltung WHERE name = 'Kunstmesse München 2024'        LIMIT 1);
SET @vid_galerie   = (SELECT id FROM veranstaltung WHERE name = 'Galerie Nord – Jahresausstellung 2025' LIMIT 1);
SET @vid_open_air  = (SELECT id FROM veranstaltung WHERE name = 'Open Air Kunst Sommer 2025'     LIMIT 1);

-- ── Kunstmesse München 2024 ────────────────────────────────
-- Tag 1: 2024-11-08

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_muenchen, '2024-11-08', '10:00:00',
  '<p><strong>Eröffnung &amp; Vernissage</strong></p>',
  '<p>Offizieller Eröffnungsrundgang mit Begrüßung durch die Veranstalter. Im Anschluss Sekt-Empfang und freier Ausstellungsbesuch.</p>',
  'Eingangshalle');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name IN ('Anna Berger', 'Felix Braun', 'Stefan Zimmermann');

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_muenchen, '2024-11-08', '13:00:00',
  '<p>Kuratorenführung</p>',
  '<p>Geführter Rundgang durch alle Hallen mit Erläuterungen zu ausgewählten Positionen der diesjährigen Messe.</p>',
  'Alle Hallen');
SET @p = LAST_INSERT_ID();
-- no participants for this entry

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_muenchen, '2024-11-08', '17:00:00',
  '<p>Künstlergespräch: <em>Zwischen Algorithmus und Handwerk</em></p>',
  '<p>Stefan Zimmermann und Sarah Müller im Gespräch über die Spannung zwischen digitaler Generativität und handwerklicher Kunstpraxis.</p>',
  'Vortragssaal');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name IN ('Stefan Zimmermann', 'Sarah Müller');

-- Tag 2: 2024-11-09

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_muenchen, '2024-11-09', '11:00:00',
  '<p>Workshop: <em>Druckgrafik – Techniken im Vergleich</em></p>',
  '<p>Felix Braun und die Edition Schwarzwald zeigen Hoch- und Siebdruckverfahren und laden zur aktiven Teilnahme ein. Materialbeitrag 15 €, Anmeldung vor Ort.</p>',
  'Workshop-Raum B');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name IN ('Felix Braun', 'Edition Schwarzwald');

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_muenchen, '2024-11-09', '15:00:00',
  '<p>Buchvorstellung &amp; Lesung</p>',
  '<p>Der Verlag für Zeitgenössische Kunst präsentiert seine aktuelle Publikation zur zeitgenössischen Druckgrafik im deutschsprachigen Raum.</p>',
  'Lounge D');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name = 'Verlag für Zeitgenössische Kunst';

-- Tag 3: 2024-11-10

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_muenchen, '2024-11-10', '10:00:00',
  '<p>Finissage</p>',
  '<p>Abschlussveranstaltung mit kurzen Statements der Aussteller und Rückblick auf drei intensive Messetage.</p>',
  'Haupthalle');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name IN ('Anna Berger', 'Max Schneider', 'Stefan Zimmermann');

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_muenchen, '2024-11-10', '14:00:00',
  '<p>Letzter Einlass &amp; Abbau</p>',
  NULL,
  'Alle Hallen');

-- ── Galerie Nord – Jahresausstellung 2025 (Event 4) ───────
-- Eröffnungstag: 2025-03-15

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_galerie, '2025-03-15', '11:00:00',
  '<p><strong>Vernissage: <em>Grenzräume</em></strong></p>',
  '<p>Eröffnung der Jahresausstellung mit allen beteiligten Künstlerinnen und Künstlern. Einführende Worte von Galeristin Dr. Irene Kraft, anschließend freier Rundgang.</p>',
  'Galerie Nord');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name IN ('Anna Berger', 'Lisa Hoffmann', 'Thomas Wagner', 'Emma Richter', 'Jonas Fischer');

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_galerie, '2025-03-15', '17:00:00',
  '<p>Gespräch mit der Galeristin</p>',
  '<p>Dr. Irene Kraft erläutert das kuratorische Konzept der Ausstellung und die Auswahl der Positionen zum Thema <em>Grenzräume</em>.</p>',
  'Galerieraum 1');

-- Woche 2: 2025-03-22

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_galerie, '2025-03-22', '14:00:00',
  '<p>Öffentliche Führung</p>',
  '<p>Kostenloser Führungsrundgang durch alle Werke der Ausstellung. Keine Anmeldung erforderlich, Treffpunkt Eingang.</p>',
  'Galerie Nord');

-- Woche 3: 2025-03-27

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_galerie, '2025-03-27', '18:00:00',
  '<p>Artist Talk: <em>Material als Grenze</em></p>',
  '<p>Lisa Hoffmann und Jonas Fischer sprechen über ihre skulpturalen Arbeiten, den Umgang mit Material als konzeptuelles Werkzeug und die Idee des Grenzraums in der Bildhauerei.</p>',
  'Galerieraum 2');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name IN ('Lisa Hoffmann', 'Jonas Fischer');

-- Finissage: 2025-03-29

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_galerie, '2025-03-29', '15:00:00',
  '<p>Finissage</p>',
  '<p>Abschluss der Ausstellung mit allen Künstlern. Letzter gemeinsamer Rundgang und Dankesworte.</p>',
  'Galerie Nord');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name IN ('Anna Berger', 'Lisa Hoffmann', 'Thomas Wagner', 'Emma Richter', 'Jonas Fischer');

-- ── Open Air Kunst Sommer 2025 (Event 5) ──────────────────
-- Tag 1: 2025-08-09

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_open_air, '2025-08-09', '11:00:00',
  '<p><strong>Eröffnung Open Air</strong></p>',
  '<p>Festliche Eröffnung im Grünen: Kurzstatements der Künstlerinnen und Künstler, Live-Musik und freier Rundgang durch alle Ausstellungsbereiche.</p>',
  'Hauptbühne');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name IN ('Anna Berger', 'Thomas Wagner', 'Lisa Hoffmann');

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_open_air, '2025-08-09', '14:00:00',
  '<p>Mitmach-Workshop: <em>Aquarell im Freien</em></p>',
  '<p>Thomas Wagner lädt ein zum Aquarellieren unter freiem Himmel. Materialien werden gestellt, alle Niveaus willkommen.</p>',
  'Wiese Süd');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name = 'Thomas Wagner';

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_open_air, '2025-08-09', '17:00:00',
  '<p>Performance: <em>Stoff &amp; Bewegung</em></p>',
  '<p>Maria Koch präsentiert eine ortsspezifische Textil-Performance, die die Grenze zwischen Gebrauchsgegenstand und Kunst auslöst.</p>',
  'Freigelände Ost');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name = 'Maria Koch';

-- Tag 2: 2025-08-10

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_open_air, '2025-08-10', '11:00:00',
  '<p>Familientag: Kunst für alle</p>',
  '<p>Ganztägiges Programm für Kinder und Familien: Mal-Stationen, Mitmachinstallationen und Führungen auf Augenhöhe.</p>',
  'Gesamtes Gelände');

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_open_air, '2025-08-10', '15:00:00',
  '<p>Gespräch: <em>Skulptur im öffentlichen Raum</em></p>',
  '<p>Sarah Müller, Felix Braun und die Edition Schwarzwald sprechen über die besonderen Herausforderungen und Chancen von Kunst im Freien.</p>',
  'Pavillon');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name IN ('Sarah Müller', 'Felix Braun', 'Edition Schwarzwald');

-- Tag 3: 2025-08-16

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_open_air, '2025-08-16', '13:00:00',
  '<p>Öffentliche Führung</p>',
  NULL,
  'Treffpunkt Eingang');

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_open_air, '2025-08-16', '16:00:00',
  '<p>Edition Schwarzwald – Druckvorführung</p>',
  '<p>Live-Demonstration von Handdrucktechniken: Die Gruppe zeigt, wie limitierte Grafikeditionen von Hand entstehen.</p>',
  'Druckstation');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer WHERE name = 'Edition Schwarzwald';

-- Tag 4: 2025-08-17

INSERT INTO veranstaltung_programm (veranstaltung_id, datum, uhrzeit, titel, beschreibung, ort_name)
VALUES (@vid_open_air, '2025-08-17', '11:00:00',
  '<p>Finissage &amp; Abschlussfest</p>',
  '<p>Gemeinsamer Abschluss des ersten Open-Air-Kunstsommers mit allen Beteiligten. Tombola, Musik und Rückblick.</p>',
  'Hauptbühne');
SET @p = LAST_INSERT_ID();
INSERT INTO programm_teilnehmer (programm_id, teilnehmer_id)
SELECT @p, id FROM teilnehmer
WHERE name IN ('Anna Berger','Thomas Wagner','Sarah Müller','Felix Braun','Lisa Hoffmann','Maria Koch','Edition Schwarzwald');
