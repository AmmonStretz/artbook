<?php
// Required vars: $bild_tid (int), $bilder (array)
$_ns = 'bs_' . $bild_tid; // unique namespace per page
?>
<div class="card" style="margin-top:1.25rem;max-width:var(--bild-max-width,860px)">
  <div class="card-header">
    <span class="card-title">
      Bilder
      <span id="<?= $_ns ?>_count" style="color:var(--text-muted);font-weight:400;font-size:.875rem">
        &nbsp;<?= count($bilder) ?>
      </span>
    </span>
    <label class="btn btn-primary btn-sm" style="cursor:pointer">
      <i class="fa-solid fa-upload"></i>
      Bilder hochladen
      <input type="file" id="<?= $_ns ?>_input" accept=".jpg,.jpeg,.png,.webp" multiple hidden>
    </label>
  </div>
  <div class="card-body">
    <p class="bild-hint">
      Mindestens <?= IMG_MIN_WIDTH ?> px Breite &nbsp;·&nbsp;
      wird automatisch auf <?= IMG_MAX_WIDTH ?> px skaliert &nbsp;·&nbsp;
      JPEG, PNG, WebP
    </p>
    <div id="<?= $_ns ?>_errors"></div>
    <div id="<?= $_ns ?>_grid" class="bild-grid">
      <?php foreach ($bilder as $b): ?>
        <?= bildCard($b) ?>
      <?php endforeach; ?>
      <?php if (!$bilder): ?>
        <p class="bild-empty" id="<?= $_ns ?>_empty">Noch keine Bilder hochgeladen.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Preview Dialog (einmal pro Seite) -->
<?php if (!defined('_BILD_PREVIEW_RENDERED')): define('_BILD_PREVIEW_RENDERED', true); ?>
<dialog id="bild-preview-dialog" class="bild-preview-dialog">
  <div class="bild-preview-header">
    <span id="bild-preview-label" style="font-size:.8125rem"></span>
    <button type="button" class="btn btn-ghost btn-sm" style="color:#e2e8f0"
            onclick="document.getElementById('bild-preview-dialog').close()">✕</button>
  </div>
  <img id="bild-preview-img" src="" alt="" style="display:block;max-width:88vw;max-height:80vh;object-fit:contain">
</dialog>
<script>
document.getElementById('bild-preview-dialog').addEventListener('click', function(e) {
  if (e.target === this) this.close();
});
function bildPreview(url, id, titel) {
  document.getElementById('bild-preview-img').src = url;
  document.getElementById('bild-preview-label').textContent = (titel || '') + (titel ? ' · ' : '') + '#' + id;
  document.getElementById('bild-preview-dialog').showModal();
}
async function bildSave(btn, id) {
  const card    = btn.closest('.bild-card');
  const fd      = new FormData();
  fd.append('bild_id',  id);
  fd.append('titel',    card.querySelector('.bild-titel').value.trim());
  fd.append('alt_text', card.querySelector('.bild-alt').value.trim());
  const datumEl = card.querySelector('.bild-datum');
  if (datumEl) fd.append('startdatum', datumEl.value);
  btn.disabled = true; btn.textContent = '…';
  try {
    const r = await fetch('/admin/bilder/update.php', { method: 'POST', body: fd });
    const d = await r.json();
    btn.textContent = d.ok ? '✓ Gespeichert' : 'Fehler';
    setTimeout(() => { btn.disabled = false; btn.textContent = 'Speichern'; }, 1800);
  } catch { btn.disabled = false; btn.textContent = 'Speichern'; }
}
async function bildDelete(btn, id) {
  if (!confirm('Dieses Bild unwiderruflich löschen?')) return;
  btn.disabled = true;
  const fd = new FormData();
  fd.append('bild_id', id);
  try {
    const r = await fetch('/admin/bilder/delete.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      const card = btn.closest('.bild-card');
      const grid = card.parentNode;
      card.style.transition = 'opacity .2s';
      card.style.opacity    = '0';
      setTimeout(() => {
        card.remove();
        const cntEl = document.querySelector('[id$="_count"]');
        if (cntEl) cntEl.textContent = ' ' + Math.max(0, parseInt(cntEl.textContent) - 1);
        if (!grid.querySelector('.bild-card')) {
          const p = document.createElement('p');
          p.className = 'bild-empty'; p.textContent = 'Noch keine Bilder hochgeladen.';
          grid.appendChild(p);
        }
      }, 220);
    } else { btn.disabled = false; }
  } catch { btn.disabled = false; }
}
</script>
<?php endif; ?>

<script>
(function() {
  const input   = document.getElementById('<?= $_ns ?>_input');
  const grid    = document.getElementById('<?= $_ns ?>_grid');
  const errWrap = document.getElementById('<?= $_ns ?>_errors');
  const cntEl   = document.getElementById('<?= $_ns ?>_count');
  const tid     = <?= (int)$bild_tid ?>;

  input.addEventListener('change', () => {
    Array.from(input.files).forEach(upload);
    input.value = '';
  });

  async function upload(file) {
    const fd = new FormData();
    fd.append('teilnehmer_id', tid);
    fd.append('bild', file);
    try {
      const r = await fetch('/admin/bilder/upload.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.error) { addErr(file.name + ': ' + d.error); return; }
      appendCard(d);
      cntEl.textContent = ' ' + (parseInt(cntEl.textContent) + 1);
      const empty = document.getElementById('<?= $_ns ?>_empty');
      if (empty) empty.remove();
    } catch { addErr(file.name + ': Upload fehlgeschlagen'); }
  }

  function addErr(msg) {
    const p = document.createElement('p');
    p.className = 'bild-error';
    p.textContent = msg;
    errWrap.appendChild(p);
    setTimeout(() => p.remove(), 6000);
  }

  function appendCard(d) {
    const div = document.createElement('div');
    div.innerHTML = <?= json_encode('') ?> + bildCardHtml(d.id, d.url, '', '', '');
    const card = div.firstElementChild;
    card.classList.add('bild-new');
    grid.appendChild(card);
  }
})();

function bildCardHtml(id, url, titel, alt, datum) {
  function e(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  return `<div class="bild-card" data-id="${id}">
    <div class="bild-thumb-wrap" onclick="bildPreview('${e(url)}',${id},'${e(titel)}')">
      <img class="bild-thumb" src="${e(url)}" alt="${e(alt)}" loading="lazy">
      <span class="bild-id">#${id}</span>
    </div>
    <div class="bild-meta">
      <input type="text" class="bild-titel" placeholder="Titel" value="${e(titel)}">
      <input type="text" class="bild-alt"   placeholder="Alternativtext" value="${e(alt)}">
      <input type="date" class="bild-datum" title="Startdatum" value="${e(datum)}">
    </div>
    <div class="bild-actions">
      <button type="button" class="btn btn-secondary btn-sm" style="flex:1" onclick="bildSave(this,${id})">Speichern</button>
      <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="bildDelete(this,${id})" title="Löschen">
        <i class="fa-solid fa-trash" aria-hidden="true"></i>
      </button>
    </div>
  </div>`;
}
</script>
<?php

function bildCard(array $b): string {
    $url   = htmlspecialchars(IMG_UPLOAD_URL . $b['dateiname']);
    $titel = htmlspecialchars($b['titel']    ?? '');
    $alt   = htmlspecialchars($b['alt_text'] ?? '');
    $datum = htmlspecialchars($b['startdatum'] ?? '');
    $id    = (int)$b['id'];
    return <<<HTML
    <div class="bild-card" data-id="{$id}">
      <div class="bild-thumb-wrap" onclick="bildPreview('{$url}',{$id},'{$titel}')">
        <img class="bild-thumb" src="{$url}" alt="{$alt}" loading="lazy">
        <span class="bild-id">#{$id}</span>
      </div>
      <div class="bild-meta">
        <input type="text" class="bild-titel" placeholder="Titel" value="{$titel}">
        <input type="text" class="bild-alt"   placeholder="Alternativtext" value="{$alt}">
        <input type="date" class="bild-datum" title="Startdatum" value="{$datum}">
      </div>
      <div class="bild-actions">
        <button type="button" class="btn btn-secondary btn-sm" style="flex:1" onclick="bildSave(this,{$id})">Speichern</button>
        <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="bildDelete(this,{$id})" title="Löschen">
          <i class="fa-solid fa-trash" aria-hidden="true"></i>
        </button>
      </div>
    </div>
    HTML;
}
