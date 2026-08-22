import sharp from 'sharp';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT      = path.resolve(__dirname, '..');
const MESSEN    = path.join(ROOT, 'messen');
const OUT_DIR   = path.join(ROOT, 'parsed', 'images');
const WIDTHS    = [1920, 1024, 768];

fs.mkdirSync(OUT_DIR, { recursive: true });

let ts = Date.now();
const usedTs = new Set();

function nextTs() {
  while (usedTs.has(ts)) ts++;
  usedTs.add(ts);
  return ts++;
}

async function processImage(imgObj) {
  if (imgObj.timestamp) {
    usedTs.add(imgObj.timestamp);
    return false; // already processed
  }

  const srcPath = path.join(MESSEN, imgObj.url.replace(/\//g, path.sep));
  if (!fs.existsSync(srcPath)) {
    console.warn(`  MISSING: ${srcPath}`);
    return false;
  }

  const t = nextTs();
  imgObj.timestamp = t;

  const names = [
    `IMG_${t}.jpg`,
    `IMG_${t}_1024.jpg`,
    `IMG_${t}_768.jpg`,
  ];

  try {
    for (let i = 0; i < WIDTHS.length; i++) {
      const dest = path.join(OUT_DIR, names[i]);
      await sharp(srcPath)
        .rotate()                          // auto-orient via EXIF
        .resize({ width: WIDTHS[i], withoutEnlargement: true })
        .jpeg({ quality: 88 })
        .toFile(dest);
    }
  } catch (err) {
    console.warn(`  UNSUPPORTED format, skipped: ${imgObj.url} (${err.message})`);
    delete imgObj.timestamp;
    return false;
  }

  return true;
}

async function processFile(jsonPath, label) {
  const data    = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
  let processed = 0;
  let skipped   = 0;

  for (const entry of data) {
    if (!Array.isArray(entry.images)) continue;
    for (const img of entry.images) {
      const done = await processImage(img);
      done ? processed++ : skipped++;
    }
  }

  fs.writeFileSync(jsonPath, JSON.stringify(data, null, 2), 'utf8');
  console.log(`${label}: ${processed} processed, ${skipped} skipped`);
}

await processFile(path.join(ROOT, 'parsed', 'teilnehmer.json'), 'teilnehmer');
await processFile(path.join(ROOT, 'parsed', 'gruppen.json'),    'gruppen');
console.log('Done. Output:', OUT_DIR);
