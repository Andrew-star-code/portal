(() => {
  const csrf = document.querySelector('meta[name="csrf"]')?.content || '';
  const uploadUrl = document.querySelector('meta[name="upload-url"]')?.content || '';

  // Подтверждение удаления
  document.querySelectorAll('form[data-confirm]').forEach(f =>
    f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

  // Предупреждение о несохранённых изменениях
  document.querySelectorAll('form.form').forEach(form => {
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });

  // Поля, видимые только при выбранном значении списка той же формы:
  // <div data-show-if="type:catalog link"> — показать, если select[name=type] = catalog или link.
  document.querySelectorAll('form').forEach(form => {
    const deps = [...form.querySelectorAll('[data-show-if]')];
    if (!deps.length) return;
    const apply = () => deps.forEach(el => {
      const [name, values] = el.dataset.showIf.split(':');
      const sel = form.elements[name];
      el.hidden = !sel || !values.split(' ').includes(sel.value);
    });
    form.addEventListener('change', e => { if (e.target.tagName === 'SELECT') apply(); });
    apply();
  });

  // ---------- предпросмотр логотипа в настройках ----------
  document.querySelectorAll('[data-logo-preview]').forEach(box => {
    const form = box.closest('form'), out = box.querySelector('.lp-logo'), f = form.elements;
    const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    // адрес картинки: только что выбранный файл → сохранённый → нет (если отмечено «Удалить»)
    const picked = {};
    const src = (name, saved) => {
      const inp = f[name];
      if (inp && inp.files.length) {
        if (!picked[name] || picked[name].file !== inp.files[0]) {
          if (picked[name]) URL.revokeObjectURL(picked[name].url);
          picked[name] = {file: inp.files[0], url: URL.createObjectURL(inp.files[0])};
        }
        return picked[name].url;
      }
      return f['remove_' + name]?.checked ? '' : saved;
    };
    const render = () => {
      let html;
      if (f.logo_mode.value === 'image') {
        const img = src('logo_image', box.dataset.imageUrl);
        html = img ? `<img class="lp-image" src="${esc(img)}" alt="">` : '<span class="lp-empty">загрузите картинку логотипа</span>';
      } else {
        const mode = f.logo_icon_mode.value, iconUrl = src('logo_icon', box.dataset.iconUrl);
        const icon = mode === 'none' ? '' : mode === 'image' && iconUrl ? `<img class="lp-icon" src="${esc(iconUrl)}" alt="">` : box.dataset.diamond;
        const left = f.logo_left.value.trim(), right = f.logo_right.value.trim();
        html = [left && `<span>${esc(left)}</span>`, icon, right && `<span>${esc(right)}</span>`].filter(Boolean).join('')
          || '<span class="lp-empty">логотип пустой</span>';
      }
      out.innerHTML = html;
    };
    form.addEventListener('input', render);
    form.addEventListener('change', render);
    render();
  });

  // ---------- визуальный редактор ----------
  const TOOLS = [
    ['P', 'Абзац', () => exec('formatBlock', '<p>')],
    ['H2', 'Заголовок', () => exec('formatBlock', '<h2>')],
    ['H3', 'Подзаголовок', () => exec('formatBlock', '<h3>')],
    '|',
    ['<b>Ж</b>', 'Жирный (Ctrl+B)', () => exec('bold')],
    ['<i>К</i>', 'Курсив (Ctrl+I)', () => exec('italic')],
    ['<u>Ч</u>', 'Подчёркнутый', () => exec('underline')],
    '|',
    ['• —', 'Маркированный список', () => exec('insertUnorderedList')],
    ['1.', 'Нумерованный список', () => exec('insertOrderedList')],
    ['❝', 'Цитата', () => exec('formatBlock', '<blockquote>')],
    '|',
    ['🔗', 'Ссылка', () => {
      const href = prompt('Адрес ссылки (https://… или /раздел/страница):', 'https://');
      if (href) exec('createLink', href);
    }],
    ['⛓̸', 'Убрать ссылку', () => exec('unlink')],
    ['🖼', 'Вставить изображение', ed => pickImage(ed)],
    ['⊞', 'Вставить таблицу', () => {
      const r = Math.min(20, +prompt('Сколько строк?', '3') || 0), c = Math.min(10, +prompt('Сколько столбцов?', '3') || 0);
      if (r > 0 && c > 0) exec('insertHTML', '<table><tbody>' + ('<tr>' + '<td>&nbsp;</td>'.repeat(c) + '</tr>').repeat(r) + '</tbody></table><p><br></p>');
    }],
    '|',
    ['⌫', 'Очистить форматирование', () => exec('removeFormat')],
    ['&lt;/&gt;', 'HTML-код', (ed, btn) => toggleSource(ed, btn)],
  ];

  function exec(cmd, val = null) { document.execCommand(cmd, false, val); }

  document.querySelectorAll('[data-editor]').forEach(wrap => {
    const ta = wrap.querySelector('textarea');
    const bar = document.createElement('div');
    bar.className = 'ed-bar';
    const area = document.createElement('div');
    area.className = 'ed-area';
    area.contentEditable = 'true';
    area.innerHTML = ta.value || '<p><br></p>';
    ta.hidden = true;
    const ed = {ta, area, source: false};

    TOOLS.forEach(t => {
      if (t === '|') { bar.insertAdjacentHTML('beforeend', '<span class="sep"></span>'); return; }
      const [html, title, fn] = t;
      const b = document.createElement('button');
      b.type = 'button'; b.innerHTML = html; b.title = title; b.setAttribute('aria-label', title);
      b.addEventListener('mousedown', e => e.preventDefault()); // не терять выделение
      b.addEventListener('click', () => {
        if (ed.source && fn.length < 2) return;
        if (!ed.source) area.focus();
        fn(ed, b);
        sync(ed);
      });
      bar.appendChild(b);
    });

    wrap.prepend(bar);
    wrap.insertBefore(area, ta);
    exec('defaultParagraphSeparator', 'p');

    area.addEventListener('input', () => { sync(ed); ta.dispatchEvent(new Event('input', {bubbles: true})); });
    // вставка: только текст с сохранением абзацев (мусор из Word/браузеров не тащим)
    area.addEventListener('paste', e => {
      const html = e.clipboardData.getData('text/html');
      const text = e.clipboardData.getData('text/plain');
      if (!html && !text) return;
      e.preventDefault();
      if (html) {
        exec('insertHTML', cleanPaste(html));
      } else {
        const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        exec('insertHTML', text.split(/\n{2,}/).map(p => '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>').join(''));
      }
    });
    // перетаскивание картинок
    area.addEventListener('drop', e => {
      const f = [...(e.dataTransfer?.files || [])].find(f => f.type.startsWith('image/'));
      if (f) { e.preventDefault(); upload(f).then(url => { area.focus(); exec('insertImage', url); sync(ed); }); }
    });
    ta.form.addEventListener('submit', () => { if (!ed.source) sync(ed); });
  });

  function sync(ed) { if (!ed.source) ed.ta.value = ed.area.innerHTML.trim() === '<p><br></p>' ? '' : ed.area.innerHTML; }

  function toggleSource(ed, btn) {
    ed.source = !ed.source;
    if (ed.source) { sync(ed); ed.ta.hidden = false; ed.area.hidden = true; }
    else { ed.area.innerHTML = ed.ta.value || '<p><br></p>'; ed.ta.hidden = true; ed.area.hidden = false; }
    btn.setAttribute('aria-pressed', ed.source);
  }

  function cleanPaste(html) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const keep = {P:1, BR:1, H2:1, H3:1, H4:1, UL:1, OL:1, LI:1, STRONG:1, B:1, EM:1, I:1, A:1, TABLE:1, THEAD:1, TBODY:1, TR:1, TD:1, TH:1, BLOCKQUOTE:1};
    const walk = node => {
      [...node.children].forEach(el => {
        walk(el);
        if (/^(SCRIPT|STYLE|META|LINK|TITLE|O:P)$/.test(el.tagName)) { el.remove(); return; }
        if (el.tagName === 'H1') { const h = doc.createElement('h2'); h.append(...el.childNodes); el.replaceWith(h); return; }
        if (!keep[el.tagName]) { el.replaceWith(...el.childNodes); return; }
        [...el.attributes].forEach(a => { if (!(el.tagName === 'A' && a.name === 'href')) el.removeAttribute(a.name); });
      });
    };
    walk(doc.body);
    return doc.body.innerHTML;
  }

  function pickImage(ed) {
    const inp = document.createElement('input');
    inp.type = 'file'; inp.accept = 'image/jpeg,image/png,image/webp,image/gif';
    const range = saveRange();
    inp.onchange = () => inp.files[0] && upload(inp.files[0]).then(url => {
      ed.area.focus(); restoreRange(range);
      const alt = prompt('Подпись к изображению (alt), можно оставить пустой:', '') || '';
      exec('insertHTML', `<img src="${url}" alt="${alt.replace(/"/g, '&quot;')}">`);
      sync(ed);
    });
    inp.click();
  }

  function saveRange() { const s = getSelection(); return s.rangeCount ? s.getRangeAt(0).cloneRange() : null; }
  function restoreRange(r) { if (!r) return; const s = getSelection(); s.removeAllRanges(); s.addRange(r); }

  async function upload(file) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('_csrf', csrf);
    const res = await fetch(uploadUrl, {method: 'POST', body: fd, credentials: 'same-origin'});
    const data = await res.json().catch(() => ({error: 'Ошибка загрузки'}));
    if (!res.ok || !data.url) { alert(data.error || 'Ошибка загрузки'); throw new Error(data.error); }
    return data.url;
  }

  // ---------- отправка форм с файлами: полоса прогресса ----------
  // Регистрируется последним, чтобы редакторы успели записать текст в textarea.
  document.querySelectorAll('form.form[enctype="multipart/form-data"]').forEach(form => {
    form.addEventListener('submit', e => {
      const hasFile = [...form.querySelectorAll('input[type=file]')].some(i => i.files.length);
      if (!hasFile || e.defaultPrevented) return;
      e.preventDefault();

      let box = form.querySelector('.progress');
      if (!box) {
        form.querySelector('.actions').insertAdjacentHTML('beforebegin', '<div class="progress"><div class="progress-bar"></div><span class="progress-text"></span></div>');
        box = form.querySelector('.progress');
      }
      const bar = box.querySelector('.progress-bar'), text = box.querySelector('.progress-text');
      const btn = form.querySelector('button.btn--primary');
      box.hidden = false; btn.disabled = true;

      const xhr = new XMLHttpRequest();
      xhr.open('POST', form.action);
      xhr.upload.onprogress = ev => {
        if (!ev.lengthComputable) return;
        const p = Math.round(ev.loaded / ev.total * 100);
        bar.style.width = p + '%';
        text.textContent = p < 100 ? `Загрузка… ${p}% (${(ev.loaded / 1048576).toFixed(0)} из ${(ev.total / 1048576).toFixed(0)} МБ)` : 'Обработка на сервере…';
      };
      xhr.onload = () => {
        if (xhr.responseURL && xhr.responseURL !== form.action) {
          location.href = xhr.responseURL; // успешное сохранение → редирект на страницу записи
        } else {
          document.open(); document.write(xhr.responseText); document.close(); // ошибки формы
        }
      };
      xhr.onerror = () => {
        btn.disabled = false; box.hidden = true;
        alert('Не удалось отправить файл: обрыв соединения или превышен лимит сервера.');
      };
      xhr.send(new FormData(form));
    });
  });
})();
