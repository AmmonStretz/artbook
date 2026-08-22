import sharp from 'sharp';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT    = path.resolve(__dirname, '..');
const MESSEN  = path.join(ROOT, 'messen');
const OUT_DIR = path.join(ROOT, 'parsed', 'images');
const WIDTHS  = [1920, 1024, 768];

// ── normalisation ──────────────────────────────────────────────────────────────

function normalize(str) {
  return str
    .toLowerCase()
    .normalize('NFD').replace(/[̀-ͯ]/g, '')   // strip diacritics
    .replace(/ß/g, 'ss')
    .replace(/æ/g, 'ae')
    .replace(/ø/g, 'o')
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();
}

// tokens sorted so "Anno Van Der Heide" == "Van Der Heide Anno"
function tokenSet(str) {
  return normalize(str).split(/\s+/).sort().join(' ');
}

// ── build lookup: year → [ { name, image_local } ] ────────────────────────────

function loadYearLookup(year) {
  const p = path.join(MESSEN, String(year), 'teilnehmer.json');
  if (!fs.existsSync(p)) return [];
  return JSON.parse(fs.readFileSync(p, 'utf8'));
}

const yearCache = {};
function getYearEntries(year) {
  if (!yearCache[year]) yearCache[year] = loadYearLookup(year);
  return yearCache[year];
}

function findMatch(name, year) {
  const entries = getYearEntries(year);
  const needle  = tokenSet(name);
  for (const e of entries) {
    if (!e.image_local) continue;
    if (tokenSet(e.name) === needle) return e.image_local;
  }
  return null;
}

// ── image generation (same as process-images.mjs) ─────────────────────────────

let ts = Date.now();
const usedTs = new Set();

function nextTs() {
  while (usedTs.has(ts)) ts++;
  usedTs.add(ts);
  return ts++;
}

async function generateSizes(srcPath, t) {
  const names = [`IMG_${t}.jpg`, `IMG_${t}_1024.jpg`, `IMG_${t}_768.jpg`];
  for (let i = 0; i < WIDTHS.length; i++) {
    await sharp(srcPath)
      .rotate()
      .resize({ width: WIDTHS[i], withoutEnlargement: true })
      .jpeg({ quality: 88 })
      .toFile(path.join(OUT_DIR, names[i]));
  }
}

// ── repair + process ───────────────────────────────────────────────────────────

async function repairAndProcess(jsonPath, label) {
  const data = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));

  // seed usedTs from existing timestamps so we don't collide
  for (const e of data)
    for (const img of (e.images || []))
      if (img.timestamp) usedTs.add(img.timestamp);

  let repaired = 0, processed = 0, noMatch = 0;

  for (const entry of data) {
    if (!Array.isArray(entry.images)) continue;

    for (const img of entry.images) {
      // only handle broken urls (just "YEAR/")
      if (!/^\d{4}\/$/.test(img.url)) continue;

      const year = parseInt(img.url);
      const imageLocal = findMatch(entry.name, year);

      if (!imageLocal) {
        noMatch++;
        continue;
      }

      img.url = `${year}/${imageLocal}`;
      repaired++;
    }
  }

  // now process all images that still lack a timestamp
  for (const entry of data) {
    if (!Array.isArray(entry.images)) continue;

    for (const img of entry.images) {
      if (img.timestamp) continue;
      if (/^\d{4}\/$/.test(img.url)) continue; // still unresolved, skip

      const srcPath = path.join(MESSEN, img.url.replace(/\//g, path.sep));
      if (!fs.existsSync(srcPath)) {
        console.warn(`  MISSING file: ${img.url}`);
        continue;
      }

      const t = nextTs();
      img.timestamp = t;

      try {
        await generateSizes(srcPath, t);
        processed++;
      } catch (err) {
        console.warn(`  UNSUPPORTED: ${img.url} (${err.message})`);
        delete img.timestamp;
      }
    }
  }

  fs.writeFileSync(jsonPath, JSON.stringify(data, null, 2), 'utf8');
  console.log(`${label}: ${repaired} urls repaired, ${processed} new images processed, ${noMatch} still unresolvable`);
}

fs.mkdirSync(OUT_DIR, { recursive: true });

await repairAndProcess(path.join(ROOT, 'parsed', 'teilnehmer.json'), 'teilnehmer');
await repairAndProcess(path.join(ROOT, 'parsed', 'gruppen.json'),    'gruppen');
console.log('Done.');
