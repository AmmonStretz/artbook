-- ============================================================
-- Fake / Demo Data
-- Einspielen mit: mysql --default-character-set=utf8mb4
-- ============================================================

-- ── Bestehende Künstler aktualisieren ─────────────────────

UPDATE teilnehmer SET
  name        = 'Anna Berger',
  beschreibung = '<p>Anna Berger ist Malerin aus Berlin. Ihre Werke verbinden klassische Ölmaltechnik mit modernen Bildsprachen und beschäftigen sich mit Themen wie Identität und urbaner Wahrnehmung. Seit 2018 stellt sie regelmäßig auf nationalen und internationalen Kunstmessen aus.</p>',
  link        = 'https://www.annaberger-kunst.de'
WHERE id = 4;

UPDATE teilnehmer SET
  name        = 'Max Schneider',
  beschreibung = '<p>Max Schneider arbeitet als Fotograf in Hamburg. Sein Fokus liegt auf Dokumentarfotografie und konzeptionellen Bildserien, die das Alltägliche aus ungewohnten Perspektiven beleuchten. Seine Arbeiten wurden in der Hamburger Kunsthalle sowie in Berlin und Kopenhagen gezeigt.</p>'
WHERE id = 5;

UPDATE teilnehmer SET
  name        = 'Lisa Hoffmann',
  beschreibung = '<p>Lisa Hoffmann ist Bildhauerin in München. Sie schafft großformatige Skulpturen aus Bronze und Carrara-Marmor, die sich mit dem Verhältnis von menschlichem Körper und architektonischem Raum auseinandersetzen. Ihre Arbeit ist im öffentlichen Raum mehrerer Städte vertreten.</p>'
WHERE id = 6;

UPDATE teilnehmer SET
  name        = 'Thomas Wagner',
  beschreibung = '<p>Thomas Wagner ist Aquarellmaler aus Köln. Seine transparenten, lichtdurchfluteten Arbeiten zeigen Stadtlandschaften und Naturmotive, bei denen Atmosphäre und Augenblick im Vordergrund stehen. Er unterrichtet außerdem an der Kölner Kunstschule.</p>'
WHERE id = 7;

UPDATE teilnehmer SET
  name        = 'Verlag für Zeitgenössische Kunst',
  beschreibung = '<p>Der Verlag für Zeitgenössische Kunst verlegt seit 2010 Künstlerbücher, Kataloge und limitierte Editionen. Er begleitet Ausstellungsprojekte von der Konzeption bis zur Publikation und fördert die dokumentarische Auseinandersetzung mit zeitgenössischen künstlerischen Positionen.</p>'
WHERE id = 8;

-- ── Neue Künstler ──────────────────────────────────────────

INSERT INTO teilnehmer (typ, name, beschreibung, link) VALUES
('kuenstler', 'Sarah Müller',
 '<p>Sarah Müller arbeitet an der Schnittstelle von Skulptur, Installation und Performance. Ihre raumgreifenden Objekte entstehen oft aus Alltagsmaterialien und hinterfragen die Grenzen zwischen Kunst und Alltag. Sie lebt und arbeitet in Frankfurt am Main.</p>',
 NULL),

('kuenstler', 'Felix Braun',
 '<p>Felix Braun ist Druckgrafiker aus Leipzig. Er verbindet traditionelle Hochdruck- und Siebdrucktechniken mit digitalen Kompositionsverfahren und schafft so mehrschichtige, vibrante Bildwelten. Seine Editionen erscheinen in Auflagen von 10 bis 30 Stück.</p>',
 NULL),

('kuenstler', 'Maria Koch',
 '<p>Maria Koch arbeitet mit Textil, Stickerei und Weberei als künstlerische Ausdrucksformen. Ihre Werke bewegen sich zwischen Gebrauchsgegenstand und autonomem Kunstwerk und hinterfragen Traditionen des weiblichen Kunsthandwerks. Sie lebt in Stuttgart.</p>',
 NULL),

('kuenstler', 'Stefan Zimmermann',
 '<p>Stefan Zimmermann entwickelt generative Algorithmen und interaktive Installationen, bei denen Betrachter durch ihre Bewegung in das Werk eingreifen. Er zählt zu den Pionieren digitaler Bildender Kunst im deutschsprachigen Raum und lebt in Berlin.</p>',
 'https://stefanzimmermann.art'),

('kuenstler', 'Emma Richter',
 '<p>Emma Richter ist Keramikerin in Dresden. Ihre handgetöpferten Porzellanobjekte kombinieren traditionelle Glasurtechniken mit abstrakter Formensprache. Jedes Stück ist ein Unikat und entsteht in einem meditativen, handwerklichen Prozess.</p>',
 NULL),

('kuenstler', 'Jonas Fischer',
 '<p>Jonas Fischer ist Bildhauer aus Düsseldorf. Er arbeitet vorrangig mit Holz aus nachhaltiger Forstwirtschaft und erschafft organische Großskulpturen, die an geologische Formationen oder pflanzliche Strukturen erinnern. Seine Arbeiten finden sich in mehreren deutschen Museen.</p>',
 NULL);

-- ── Neue Gruppe ────────────────────────────────────────────

INSERT INTO teilnehmer (typ, name, beschreibung, gruppe_typ) VALUES
('gruppe', 'Edition Schwarzwald',
 '<p>Die Edition Schwarzwald ist ein Zusammenschluss von Grafikern, Zeichnern und Druckkünstlern aus Südbaden. Gemeinsam entwickeln und verlegen sie limitierte Grafikeditionen in Handarbeit. Die Gruppe wurde 2019 gegründet und zeigt ihre Arbeit auf Kunstmessen und in Galerien.</p>',
 'edition');

-- ── Veranstaltungen aktualisieren ─────────────────────────

-- Kunstmesse München 2024 (vergangene Veranstaltung)
UPDATE veranstaltung SET
  name        = 'Kunstmesse München 2024',
  sichtbar    = 1,
  beschreibung = '<p>Die Kunstmesse München ist eine der bedeutendsten regionalen Kunstmessen Bayerns und bringt seit über zwanzig Jahren Künstler, Galerien und Sammler zusammen. Über drei Tage präsentieren mehr als 80 Aussteller ihre aktuellen Arbeiten auf über 3.000 Quadratmetern Ausstellungsfläche.</p><p>Das Programm umfasst Führungen, Künstlergespräche und eine Vernissage am Eröffnungsabend. Besucher erleben ein breites Spektrum zeitgenössischer Positionen – von klassischer Malerei über Fotografie und Skulptur bis hin zu digitaler Kunst und Textil.</p>',
  strasse     = 'Am Messegelände 1',
  plz         = '81823',
  ort         = 'München',
  ort_name    = 'Messe München',
  ort_url     = 'https://www.messe-muenchen.de',
  lat         = 48.128639,
  lng         = 11.686924
WHERE id = 3;

DELETE FROM veranstaltung_tag WHERE veranstaltung_id = 3;
INSERT INTO veranstaltung_tag (veranstaltung_id, datum, startzeit, endzeit) VALUES
(3, '2024-11-08', '10:00:00', '18:00:00'),
(3, '2024-11-09', '10:00:00', '18:00:00'),
(3, '2024-11-10', '10:00:00', '16:00:00');

-- Galerie Nord – Jahresausstellung 2025
UPDATE veranstaltung SET
  name        = 'Galerie Nord – Jahresausstellung 2025',
  sichtbar    = 1,
  beschreibung = '<p>Die Jahresausstellung der Galerie Nord präsentiert die neuen Arbeiten ihrer Stammkünstler sowie ausgewählter Gastkünstler. Die Schau steht in diesem Jahr unter dem Motto <em>„Grenzräume"</em> – Positionen an den Rändern von Material, Form und Bedeutung.</p><p>Alle ausgestellten Werke entstammen dem Jahr 2024 und wurden eigens für diese Ausstellung produziert oder konzipiert.</p>',
  strasse     = 'Lange Reihe 82',
  plz         = '20099',
  ort         = 'Hamburg',
  ort_name    = 'Galerie Nord Hamburg',
  ort_url     = NULL,
  lat         = 53.554073,
  lng         = 10.017679
WHERE id = 4;

DELETE FROM veranstaltung_tag WHERE veranstaltung_id = 4;
INSERT INTO veranstaltung_tag (veranstaltung_id, datum, startzeit, endzeit) VALUES
(4, '2025-03-15', '11:00:00', '19:00:00'),
(4, '2025-03-16', '11:00:00', '17:00:00'),
(4, '2025-03-21', '11:00:00', '19:00:00'),
(4, '2025-03-22', '11:00:00', '19:00:00'),
(4, '2025-03-27', '11:00:00', '19:00:00'),
(4, '2025-03-28', '11:00:00', '18:00:00'),
(4, '2025-03-29', '14:00:00', '18:00:00');

-- Neue Veranstaltung: Open Air Kunst Sommer 2025
INSERT INTO veranstaltung (name, sichtbar, beschreibung, strasse, plz, ort, ort_name, lat, lng) VALUES
('Open Air Kunst Sommer 2025', 1,
'<p>Der Open Air Kunst Sommer findet erstmalig im Münchner Stadtpark statt und bringt Kunst in den öffentlichen Raum. Lokale und überregionale Künstler zeigen Malerei, Skulptur, Fotografie und Installationen unter freiem Himmel – im Dialog mit Natur und Stadtlandschaft.</p><p>Der Eintritt ist frei. An allen Tagen gibt es ein Begleitprogramm mit Workshops, Performances und Gesprächen mit den Künstlern.</p>',
'Am Stadtpark 1', '80804', 'München', 'Stadtpark München', 48.162028, 11.574088);

SET @v5 = LAST_INSERT_ID();

INSERT INTO veranstaltung_tag (veranstaltung_id, datum, startzeit, endzeit) VALUES
(@v5, '2025-08-09', '11:00:00', '20:00:00'),
(@v5, '2025-08-10', '11:00:00', '20:00:00'),
(@v5, '2025-08-16', '11:00:00', '18:00:00'),
(@v5, '2025-08-17', '11:00:00', '18:00:00');

-- ── Teilnahmen aufräumen und neu belegen ───────────────────

-- Event 3 (Kunstmesse München 2024)
DELETE FROM veranstaltung_teilnahme WHERE veranstaltung_id = 3;
INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id, tischnummer)
SELECT 3, id, CASE
  WHEN name = 'Anna Berger'         THEN 'A-12'
  WHEN name = 'Max Schneider'       THEN 'B-07'
  WHEN name = 'Sarah Müller'        THEN 'A-05'
  WHEN name = 'Felix Braun'         THEN 'C-03'
  WHEN name = 'Stefan Zimmermann'   THEN 'C-11'
  WHEN name = 'Verlag für Zeitgenössische Kunst' THEN 'D-01'
  WHEN name = 'Edition Schwarzwald' THEN 'D-04'
  ELSE NULL
END
FROM teilnehmer
WHERE name IN ('Anna Berger','Max Schneider','Sarah Müller','Felix Braun',
               'Stefan Zimmermann','Verlag für Zeitgenössische Kunst','Edition Schwarzwald');

-- Event 4 (Galerie Nord)
DELETE FROM veranstaltung_teilnahme WHERE veranstaltung_id = 4;
INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id)
SELECT 4, id FROM teilnehmer
WHERE name IN ('Anna Berger','Lisa Hoffmann','Thomas Wagner',
               'Emma Richter','Jonas Fischer','Verlag für Zeitgenössische Kunst');

-- Event 5 (Open Air)
INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id)
SELECT @v5, id FROM teilnehmer
WHERE name IN ('Anna Berger','Thomas Wagner','Sarah Müller',
               'Felix Braun','Lisa Hoffmann','Maria Koch','Edition Schwarzwald');

-- ── Gruppenmitglieder ─────────────────────────────────────

-- Verlag für Zeitgenössische Kunst: Anna Berger, Max Schneider, Lisa Hoffmann
DELETE FROM gruppe_kuenstler WHERE gruppe_id = 8;
INSERT IGNORE INTO gruppe_kuenstler (gruppe_id, kuenstler_id)
SELECT 8, id FROM teilnehmer
WHERE name IN ('Anna Berger','Max Schneider','Lisa Hoffmann') AND typ = 'kuenstler';

-- Edition Schwarzwald: Felix Braun, Stefan Zimmermann, Jonas Fischer
INSERT IGNORE INTO gruppe_kuenstler (gruppe_id, kuenstler_id)
SELECT g.id, k.id
FROM teilnehmer g
CROSS JOIN teilnehmer k
WHERE g.name = 'Edition Schwarzwald'
  AND k.name IN ('Felix Braun','Stefan Zimmermann','Jonas Fischer')
  AND k.typ = 'kuenstler';
