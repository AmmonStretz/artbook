<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$token = trim($_GET['token'] ?? '');

function renderError(string $msg): void { ?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Link ungültig</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/app.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg,#f8fafc)">
<div class="card" style="max-width:440px;width:100%;margin:2rem">
  <div class="card-body" style="text-align:center;padding:2rem">
    <i class="fa-solid fa-link-slash" style="font-size:2rem;color:var(--text-muted);margin-bottom:1rem"></i>
    <p style="color:var(--text-muted)"><?= htmlspecialchars($msg) ?></p>
  </div>
</div>
</body>
</html>
<?php exit; }

if (!$token) renderError('Kein Bearbeitungslink angegeben.');

$stmt = db()->prepare("
    SELECT tet.id AS token_id, tet.teilnehmer_id, tet.used_at,
           t.name, t.typ, t.beschreibung, t.link, t.gruppe_typ
    FROM teilnehmer_edit_token tet
    JOIN teilnehmer t ON t.id = tet.teilnehmer_id
    WHERE tet.token = ?
");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) renderError('Dieser Link ist ungültig oder wurde nicht gefunden.');
if ($row['used_at'] !== null) renderError('Dieser Link wurde bereits verwendet und ist nicht mehr gültig.');

$tid    = (int)$row['teilnehmer_id'];
$isGrup = $row['typ'] === 'gruppe';

$imgs = db()->prepare("SELECT id, dateiname, alt_text FROM teilnehmer_bild WHERE teilnehmer_id = ? ORDER BY uploaded_at");
$imgs->execute([$tid]);
$bilder = $imgs->fetchAll(PDO::FETCH_ASSOC);
$imgCount = count($bilder);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profil bearbeiten – <?= htmlspecialchars($row['name']) ?></title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/app.css">
<link rel="stylesheet" href="/assets/quill/quill.snow.css">
<style>
  body { background: var(--bg, #f8fafc); }
  .public-wrap { max-width: 700px; margin: 0 auto; padding: 2rem 1rem 4rem; }
  .public-header { margin-bottom: 2rem; font-size: .875rem; color: var(--text-muted); }
  .public-header strong { color: var(--text); font-size: 1.125rem; display: block; margin-bottom: .25rem; }
  #success-box { display: none; text-align: center; padding: 3rem 1rem; }
  #success-box i { font-size: 2.5rem; color: var(--success, #16a34a); margin-bottom: 1rem; }
</style>
</head>
<body>
<div class="public-wrap">

  <div class="public-header">
    <strong><?= htmlspecialchars($row['name']) ?></strong>
    Einmaliger Bearbeitungslink – nach dem Speichern ist dieser Link nicht mehr nutzbar.
  </div>

  <div id="form-wrap">
    <div id="form-flash" hidden style="margin-bottom:1rem"></div>

    <div class="card" style="margin-bottom:1.25rem">
      <div class="card-header">
        <span class="card-title">Profil bearbeiten</span>
      </div>
      <div class="card-body">
        <div class="form-grid">
          <div class="form-group span-2">
            <label for="pub-name">Name <span class="req">*</span></label>
            <input type="text" id="pub-name" value="<?= htmlspecialchars($row['name']) ?>" autocomplete="off">
          </div>
          <div class="form-group span-2">
            <label for="pub-link" style="font-weight:400;color:var(--text-muted)">
              Link <span style="font-size:.8125rem">(optional)</span>
            </label>
            <input type="text" id="pub-link" value="<?= htmlspecialchars($row['link'] ?? '') ?>"
                   placeholder="https://…" autocomplete="off">
          </div>
          <div class="form-group span-2">
            <label style="font-weight:400;color:var(--text-muted)">
              Beschreibung <span style="font-size:.8125rem">(optional)</span>
            </label>
            <div id="pub-quill"></div>
          </div>
        </div>
        <div class="form-actions" style="margin-top:1.25rem">
          <button type="button" id="pub-submit" class="btn btn-primary">Änderungen speichern</button>
        </div>
      </div>
    </div>

    <!-- Image section -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">
          Bilder
          <span id="pub-img-count" style="color:var(--text-muted);font-weight:400;font-size:.875rem">
            &nbsp;<?= $imgCount ?> / <?= IMG_MAX_COUNT ?>
          </span>
        </span>
        <label id="pub-upload-label" class="btn btn-primary btn-sm"
               style="cursor:pointer<?= $imgCount >= IMG_MAX_COUNT ? ';opacity:.45;pointer-events:none' : '' ?>">
          <i class="fa-solid fa-upload"></i>
          Bilder hochladen
          <input type="file" id="pub-upload-input" accept=".jpg,.jpeg,.png,.webp" multiple hidden
                 <?= $imgCount >= IMG_MAX_COUNT ? 'disabled' : '' ?>>
        </label>
      </div>
      <div class="card-body">
        <p class="bild-hint">
          Mindestens <?= IMG_MIN_WIDTH ?> px Breite &nbsp;·&nbsp;
          JPEG, PNG, WebP &nbsp;·&nbsp;
          max. <?= IMG_MAX_COUNT ?> Bilder
        </p>
        <div id="pub-img-errors"></div>
        <div id="pub-img-grid" class="bild-grid">
          <?php if (!$bilder): ?>
            <p class="bild-empty" id="pub-img-empty">Noch keine Bilder hochgeladen.</p>
          <?php else: foreach ($bilder as $b):
            $url = IMG_UPLOAD_URL . $b['dateiname'];
            $alt = htmlspecialchars($b['alt_text'] ?? '');
          ?>
            <div class="bild-card" data-id="<?= $b['id'] ?>">
              <div class="bild-thumb-wrap"
                   onclick="pubImgPreview('<?= addslashes($url) ?>',<?= $b['id'] ?>)">
                <img class="bild-thumb" src="<?= $url ?>" alt="<?= $alt ?>" loading="lazy">
                <span class="bild-id">#<?= $b['id'] ?></span>
              </div>
              <div class="bild-actions" style="margin-top:.5rem">
                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger);width:100%"
                        onclick="pubImgDelete(this,<?= $b['id'] ?>)" title="Löschen">
                  <i class="fa-solid fa-trash" aria-hidden="true"></i>
                  Löschen
                </button>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div id="success-box">
    <i class="fa-solid fa-circle-check"></i>
    <p style="font-size:1.125rem;font-weight:600;margin-bottom:.5rem">Vielen Dank!</p>
    <p style="color:var(--text-muted)">Ihre Änderungen wurden gespeichert.</p>
  </div>

</div>

<dialog id="pub-preview-dialog" class="bild-preview-dialog">
  <div class="bild-preview-header">
    <span></span>
    <button type="button" class="btn btn-ghost btn-sm" style="color:#e2e8f0"
            onclick="document.getElementById('pub-preview-dialog').close()">✕</button>
  </div>
  <img id="pub-preview-img" src="" alt=""
       style="display:block;max-width:88vw;max-height:80vh;object-fit:contain">
</dialog>

<script src="/assets/quill/quill.min.js"></script>
<script>
const pubToken   = <?= json_encode($token) ?>;
const pubMaxCount = <?= IMG_MAX_COUNT ?>;
let   pubCount   = <?= $imgCount ?>;

// Quill
const toolbarCfg = [
  [{ header: [2, 3, false] }],
  ['bold', 'italic', 'underline'],
  [{ list: 'ordered' }, { list: 'bullet' }],
  ['link'], ['clean']
];
const pubQuill = new Quill('#pub-quill', {
  theme: 'snow', placeholder: 'Beschreibung eingeben …', modules: { toolbar: toolbarCfg }
});
const existingDescr = <?= json_encode($row['beschreibung'] ?? '') ?>;
if (existingDescr) pubQuill.clipboard.dangerouslyPasteHTML(existingDescr);

function pubSyncLimit() {
  document.getElementById('pub-img-count').textContent = ' ' + pubCount + ' / ' + pubMaxCount;
  const full = pubCount >= pubMaxCount;
  const inp  = document.getElementById('pub-upload-input');
  const lbl  = document.getElementById('pub-upload-label');
  inp.disabled              = full;
  lbl.style.opacity         = full ? '.45' : '';
  lbl.style.pointerEvents   = full ? 'none' : '';
}

function pubFlash(type, msg) {
  const el = document.getElementById('form-flash');
  el.className   = 'alert alert-' + type;
  el.textContent = msg;
  el.hidden      = false;
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Save text form
document.getElementById('pub-submit').addEventListener('click', async () => {
  const name = document.getElementById('pub-name').value.trim();
  if (!name) { pubFlash('error', 'Name ist ein Pflichtfeld.'); return; }

  const html   = pubQuill.root.innerHTML;
  const beschr = (html === '<p><br></p>' || html === '') ? '' : html;

  const fd = new FormData();
  fd.append('token',        pubToken);
  fd.append('name',         name);
  fd.append('link',         document.getElementById('pub-link').value.trim());
  fd.append('beschreibung', beschr);

  const btn = document.getElementById('pub-submit');
  btn.disabled = true; btn.textContent = 'Speichern …';

  try {
    const r = await fetch('/bearbeiten/save.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      document.getElementById('form-wrap').style.display  = 'none';
      document.getElementById('success-box').style.display = '';
    } else {
      pubFlash('error', d.error || 'Fehler beim Speichern.');
      btn.disabled = false; btn.textContent = 'Änderungen speichern';
    }
  } catch {
    pubFlash('error', 'Fehler beim Speichern.');
    btn.disabled = false; btn.textContent = 'Änderungen speichern';
  }
});

// Image preview
document.getElementById('pub-preview-dialog').addEventListener('click', function(e) {
  if (e.target === this) this.close();
});
function pubImgPreview(url, id) {
  document.getElementById('pub-preview-img').src = url;
  document.getElementById('pub-preview-dialog').showModal();
}

// Image delete
async function pubImgDelete(btn, id) {
  if (!confirm('Dieses Bild unwiderruflich löschen?')) return;
  btn.disabled = true;
  const fd = new FormData();
  fd.append('token',  pubToken);
  fd.append('bild_id', id);
  try {
    const r = await fetch('/bearbeiten/bild_delete.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      const card = btn.closest('.bild-card');
      const grid = card.parentNode;
      card.remove();
      pubCount--;
      pubSyncLimit();
      if (!grid.querySelector('.bild-card')) {
        const p = document.createElement('p');
        p.className = 'bild-empty'; p.textContent = 'Noch keine Bilder hochgeladen.';
        grid.appendChild(p);
      }
    } else {
      btn.disabled = false;
      alert(d.error || 'Fehler beim Löschen.');
    }
  } catch { btn.disabled = false; }
}

// Image upload
(function() {
  const input   = document.getElementById('pub-upload-input');
  const grid    = document.getElementById('pub-img-grid');
  const errWrap = document.getElementById('pub-img-errors');

  input.addEventListener('change', () => {
    Array.from(input.files).forEach(upload);
    input.value = '';
  });

  async function upload(file) {
    if (pubCount >= pubMaxCount) {
      addErr('Maximale Bildanzahl (' + pubMaxCount + ') erreicht.');
      return;
    }
    const fd = new FormData();
    fd.append('token', pubToken);
    fd.append('bild',  file);
    try {
      const r = await fetch('/bearbeiten/bild_upload.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.error) { addErr(file.name + ': ' + d.error); return; }
      const empty = document.getElementById('pub-img-empty');
      if (empty) empty.remove();
      appendCard(d);
      pubCount++;
      pubSyncLimit();
    } catch { addErr(file.name + ': Upload fehlgeschlagen'); }
  }

  function addErr(msg) {
    const p = document.createElement('p');
    p.className = 'bild-error'; p.textContent = msg;
    errWrap.appendChild(p);
    setTimeout(() => p.remove(), 6000);
  }

  function appendCard(d) {
    function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    const div = document.createElement('div');
    div.innerHTML = `<div class="bild-card bild-new" data-id="${d.id}">
      <div class="bild-thumb-wrap" onclick="pubImgPreview('${esc(d.url)}',${d.id})">
        <img class="bild-thumb" src="${esc(d.url)}" alt="" loading="lazy">
        <span class="bild-id">#${d.id}</span>
      </div>
      <div class="bild-actions" style="margin-top:.5rem">
        <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger);width:100%"
                onclick="pubImgDelete(this,${d.id})" title="Löschen">
          <i class="fa-solid fa-trash" aria-hidden="true"></i>
          Löschen
        </button>
      </div>
    </div>`;
    grid.appendChild(div.firstElementChild);
  }
})();
</script>
</body>
</html>
