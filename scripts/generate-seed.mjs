import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');

const teilnehmer = JSON.parse(fs.readFileSync(path.join(ROOT, 'parsed/teilnehmer.json'), 'utf8'));
const gruppen    = JSON.parse(fs.readFileSync(path.join(ROOT, 'parsed/gruppen.json'),    'utf8'));
const events     = JSON.parse(fs.readFileSync(path.join(ROOT, 'parsed/events.json'),     'utf8'));

const GRUPPEN_OFFSET = 1000;

// ── helpers ────────────────────────────────────────────────────────────────────

function esc(v) {
  if (v === null || v === undefined) return 'NULL';
  return "'" + String(v).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";
}

// "01.07.2012" → "2012-07-01"
function parseDate(d) {
  const [dd, mm, yyyy] = d.split('.');
  return `${yyyy}-${mm}-${dd}`;
}

const eventByYear = Object.fromEntries(events.map(e => [e.year, e]));

// img.year is "01.07.YYYY" — extract year number
function yearFromImgYear(imgYear) {
  return parseInt(imgYear.split('.')[2]);
}

// ── build SQL ──────────────────────────────────────────────────────────────────

const lines = [];

lines.push('SET NAMES utf8mb4;');
lines.push('SET FOREIGN_KEY_CHECKS = 0;');
lines.push('');

// ── veranstaltungen ────────────────────────────────────────────────────────────

lines.push('-- ── Veranstaltungen ──────────────────────────────────────────────');
for (const e of events) {
  lines.push(
    `INSERT INTO veranstaltung (id, name, sichtbar) VALUES (${e.id}, ${esc(e.name)}, 1);`
  );
}
lines.push('');

// ── teilnehmer: künstler ───────────────────────────────────────────────────────

lines.push('-- ── Künstler ─────────────────────────────────────────────────────');
for (const t of teilnehmer) {
  lines.push(
    `INSERT INTO teilnehmer (id, typ, name, link) VALUES (${t.id}, 'kuenstler', ${esc(t.name)}, ${esc(t.url || null)});`
  );
}
lines.push('');

// ── teilnehmer: gruppen ────────────────────────────────────────────────────────

lines.push('-- ── Gruppen ──────────────────────────────────────────────────────');
for (const g of gruppen) {
  const dbId = g.id + GRUPPEN_OFFSET;
  lines.push(
    `INSERT INTO teilnehmer (id, typ, name, link) VALUES (${dbId}, 'gruppe', ${esc(g.name)}, ${esc(g.url || null)});`
  );
}
lines.push('');

// ── gruppe_kuenstler ───────────────────────────────────────────────────────────

lines.push('-- ── Gruppe ↔ Künstler ────────────────────────────────────────────');
for (const t of teilnehmer) {
  if (!t.groups?.length) continue;
  for (const gid of t.groups) {
    lines.push(
      `INSERT IGNORE INTO gruppe_kuenstler (gruppe_id, kuenstler_id) VALUES (${gid + GRUPPEN_OFFSET}, ${t.id});`
    );
  }
}
lines.push('');

// ── veranstaltung_teilnahme: künstler ─────────────────────────────────────────

lines.push('-- ── Teilnahmen Künstler ──────────────────────────────────────────');
for (const t of teilnehmer) {
  if (!t.years?.length) continue;
  for (const year of t.years) {
    const ev = eventByYear[year];
    if (!ev) continue;
    lines.push(
      `INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id) VALUES (${ev.id}, ${t.id});`
    );
  }
}
lines.push('');

// ── veranstaltung_teilnahme: gruppen ──────────────────────────────────────────

lines.push('-- ── Teilnahmen Gruppen ───────────────────────────────────────────');
for (const g of gruppen) {
  if (!g.years?.length) continue;
  const dbId = g.id + GRUPPEN_OFFSET;
  for (const year of g.years) {
    const ev = eventByYear[year];
    if (!ev) continue;
    lines.push(
      `INSERT IGNORE INTO veranstaltung_teilnahme (veranstaltung_id, teilnehmer_id) VALUES (${ev.id}, ${dbId});`
    );
  }
}
lines.push('');

// ── teilnehmer_bild ───────────────────────────────────────────────────────────

lines.push('-- ── Bilder Künstler ──────────────────────────────────────────────');
for (const t of teilnehmer) {
  if (!t.images?.length) continue;
  for (const img of t.images) {
    if (!img.timestamp) continue;
    const year   = yearFromImgYear(img.year);
    const ev     = eventByYear[year];
    const date   = ev ? parseDate(ev.img_release_date) : parseDate(img.year);
    const fname  = `IMG_${img.timestamp}.jpg`;
    lines.push(
      `INSERT INTO teilnehmer_bild (teilnehmer_id, dateiname, uploaded_at) VALUES (${t.id}, ${esc(fname)}, ${esc(date)});`
    );
  }
}
lines.push('');

lines.push('-- ── Bilder Gruppen ───────────────────────────────────────────────');
for (const g of gruppen) {
  if (!g.images?.length) continue;
  const dbId = g.id + GRUPPEN_OFFSET;
  for (const img of g.images) {
    if (!img.timestamp) continue;
    const year   = yearFromImgYear(img.year);
    const ev     = eventByYear[year];
    const date   = ev ? parseDate(ev.img_release_date) : parseDate(img.year);
    const fname  = `IMG_${img.timestamp}.jpg`;
    lines.push(
      `INSERT INTO teilnehmer_bild (teilnehmer_id, dateiname, uploaded_at) VALUES (${dbId}, ${esc(fname)}, ${esc(date)});`
    );
  }
}
lines.push('');

lines.push('SET FOREIGN_KEY_CHECKS = 1;');

// ── write output ───────────────────────────────────────────────────────────────

const out = path.join(ROOT, 'mysql', 'seeds', 'legacy_data.sql');
fs.writeFileSync(out, lines.join('\n'), 'utf8');

console.log(`Geschrieben: ${out}`);
console.log(`  ${events.length} Veranstaltungen`);
console.log(`  ${teilnehmer.length} Künstler`);
console.log(`  ${gruppen.length} Gruppen`);
console.log(`  ${teilnehmer.filter(t=>t.groups?.length).reduce((n,t)=>n+t.groups.length,0)} gruppe_kuenstler-Einträge`);
const tTeil = teilnehmer.reduce((n,t)=>n+(t.years?.length||0),0);
const gTeil = gruppen.reduce((n,g)=>n+(g.years?.length||0),0);
console.log(`  ${tTeil + gTeil} Teilnahmen`);
const tBild = teilnehmer.reduce((n,t)=>n+(t.images||[]).filter(i=>i.timestamp).length,0);
const gBild = gruppen.reduce((n,g)=>n+(g.images||[]).filter(i=>i.timestamp).length,0);
console.log(`  ${tBild + gBild} Bilder`);
