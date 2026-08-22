import { Editor }      from 'https://esm.sh/@tiptap/core@2';
import StarterKit      from 'https://esm.sh/@tiptap/starter-kit@2';
import Underline       from 'https://esm.sh/@tiptap/extension-underline@2';
import Link            from 'https://esm.sh/@tiptap/extension-link@2';
import Placeholder     from 'https://esm.sh/@tiptap/extension-placeholder@2';

export function mkEditor(target, { placeholder = '', initialHTML = '', small = false } = {}) {
  const wrap    = document.createElement('div');
  wrap.className = small ? 'tt-wrap tt-small' : 'tt-wrap';

  const toolbar = document.createElement('div');
  toolbar.className = 'tt-toolbar';
  toolbar.innerHTML = buildToolbar(small);

  const content = document.createElement('div');
  const htmlView = document.createElement('textarea');
  htmlView.className = 'tt-html-view';
  htmlView.style.display = 'none';

  wrap.append(toolbar, content, htmlView);
  target.replaceWith(wrap);

  const editor = new Editor({
    element: content,
    extensions: [
      StarterKit.configure({ heading: { levels: [2, 3] } }),
      Underline,
      Link.configure({ openOnClick: false }),
      Placeholder.configure({ placeholder }),
    ],
    content: initialHTML || '',
  });

  let htmlMode = false;

  toolbar.addEventListener('mousedown', e => {
    const btn = e.target.closest('.tt-btn[data-cmd]');
    if (!btn) return;
    e.preventDefault();
    const cmd = btn.dataset.cmd;
    if (cmd === 'html') {
      htmlMode = !htmlMode;
      btn.classList.toggle('is-active', htmlMode);
      if (htmlMode) {
        htmlView.value = edHTML(editor);
        content.style.display = 'none';
        htmlView.style.display = '';
        toolbar.querySelectorAll('.tt-btn:not([data-cmd="html"]), .tt-heading-sel, .tt-sep:not(.tt-sep-html)')
          .forEach(el => el.style.visibility = 'hidden');
      } else {
        const newHTML = htmlView.value.trim();
        editor.commands.setContent(newHTML || '');
        htmlView.style.display = 'none';
        content.style.display = '';
        toolbar.querySelectorAll('.tt-btn, .tt-heading-sel, .tt-sep')
          .forEach(el => el.style.visibility = '');
      }
      return;
    }
    if (htmlMode) return;
    if      (cmd === 'bold')        editor.chain().focus().toggleBold().run();
    else if (cmd === 'italic')      editor.chain().focus().toggleItalic().run();
    else if (cmd === 'underline')   editor.chain().focus().toggleUnderline().run();
    else if (cmd === 'bulletList')  editor.chain().focus().toggleBulletList().run();
    else if (cmd === 'orderedList') editor.chain().focus().toggleOrderedList().run();
    else if (cmd === 'link') {
      if (editor.isActive('link')) editor.chain().focus().unsetLink().run();
      else { const url = prompt('URL:'); if (url) editor.chain().focus().setLink({ href: url }).run(); }
    }
    else if (cmd === 'clear') editor.chain().focus().clearNodes().unsetAllMarks().run();
    syncActive();
  });

  const sel = toolbar.querySelector('.tt-heading-sel');
  if (sel) {
    sel.addEventListener('change', () => {
      if (htmlMode) return;
      const v = parseInt(sel.value);
      if (v === 0) editor.chain().focus().setParagraph().run();
      else         editor.chain().focus().setHeading({ level: v }).run();
      syncActive();
    });
  }

  function syncActive() {
    toolbar.querySelectorAll('.tt-btn[data-cmd]').forEach(btn => {
      const c = btn.dataset.cmd;
      if (c === 'html') return;
      btn.classList.toggle('is-active',
        c === 'bold'        ? editor.isActive('bold') :
        c === 'italic'      ? editor.isActive('italic') :
        c === 'underline'   ? editor.isActive('underline') :
        c === 'bulletList'  ? editor.isActive('bulletList') :
        c === 'orderedList' ? editor.isActive('orderedList') :
        c === 'link'        ? editor.isActive('link') : false
      );
    });
    if (sel) sel.value = editor.isActive('heading', {level:2}) ? '2'
                       : editor.isActive('heading', {level:3}) ? '3' : '0';
  }

  editor.on('selectionUpdate', syncActive);
  editor.on('transaction',     syncActive);

  return editor;
}

export function edHTML(editor) {
  const h = editor.getHTML();
  return (h === '<p></p>' || h === '') ? '' : h;
}

function buildToolbar(small) {
  const fmt = `
    <button type="button" class="tt-btn" data-cmd="bold"      title="Fett"><b>B</b></button>
    <button type="button" class="tt-btn" data-cmd="italic"    title="Kursiv"><em>I</em></button>
    <button type="button" class="tt-btn" data-cmd="underline" title="Unterstrichen"><u>U</u></button>`;
  const htmlBtn = `
    <span class="tt-sep"></span>
    <button type="button" class="tt-btn" data-cmd="html" title="HTML-Quelltext">&lt;/&gt;</button>`;
  if (small) return `${fmt}
    <span class="tt-sep"></span>
    <button type="button" class="tt-btn" data-cmd="clear" title="Formatierung entfernen">✕</button>${htmlBtn}`;
  return `
    <select class="tt-heading-sel">
      <option value="0">Normal</option>
      <option value="2">H2</option>
      <option value="3">H3</option>
    </select>
    <span class="tt-sep"></span>
    ${fmt}
    <span class="tt-sep"></span>
    <button type="button" class="tt-btn" data-cmd="bulletList"  title="Aufzählung">&#8226;</button>
    <button type="button" class="tt-btn" data-cmd="orderedList" title="Nummerierung">1.</button>
    <span class="tt-sep"></span>
    <button type="button" class="tt-btn" data-cmd="link"  title="Link setzen / entfernen"><i class="fa-solid fa-link fa-xs"></i></button>
    <span class="tt-sep"></span>
    <button type="button" class="tt-btn" data-cmd="clear" title="Formatierung entfernen">✕</button>${htmlBtn}`;
}
