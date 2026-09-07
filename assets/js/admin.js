/* =====================================================================
   پنل مدیریت — اسکریپت (تأیید حذف، پیش‌نمایش تصویر، مرتب‌سازی با کشیدن)
   ===================================================================== */
(function () {
  'use strict';

  /* تأیید قبل از حذف */
  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) {
      e.preventDefault();
    }
  });

  /* پیش‌نمایش تصویر انتخاب‌شده */
  document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
      const img = document.querySelector(input.dataset.preview);
      const file = input.files && input.files[0];
      if (!img || !file) return;
      img.src = URL.createObjectURL(file);
      img.hidden = false;
    });
  });

  /* علامت‌گذاری دکمه‌ی فایل بعد از انتخاب */
  document.querySelectorAll('.file-btn input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => input.closest('.file-btn').classList.toggle('has-file', input.files.length > 0));
  });

  /* بزرگ شدن خودکار textarea */
  document.querySelectorAll('textarea[data-autogrow]').forEach((ta) => {
    const grow = () => { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 200) + 'px'; };
    ta.addEventListener('input', grow);
    // Enter = خط جدید، Ctrl+Enter = ارسال
    ta.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); ta.form.requestSubmit(); }
    });
  });

  /* مرتب‌سازی با کشیدن و رها کردن */
  if (typeof Sortable === 'undefined') return;

  const csrf = (document.querySelector('input[name="_csrf"]') || {}).value || '';

  const save = (type, ids) => {
    const body = new URLSearchParams({ action: 'reorder', type, ids: ids.join(','), _csrf: csrf });
    const kindEl = document.querySelector('[data-kind]');
    if (kindEl) body.set('kind', kindEl.dataset.kind);
    return fetch(window.location.pathname + window.location.search, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body,
    }).then((r) => r.ok ? r.json() : Promise.reject(r)).then(() => toast('ترتیب ذخیره شد')).catch(() => toast('خطا در ذخیره‌ی ترتیب', true));
  };

  const idsOf = (container) => Array.from(container.children).filter((el) => el.dataset.id).map((el) => el.dataset.id);

  document.querySelectorAll('[data-sortable]').forEach((container) => {
    const type = container.dataset.sortable;
    new Sortable(container, {
      handle: '.drag-handle',
      animation: 160,
      draggable: '[data-id]',
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      onEnd: () => save(type, idsOf(container)),
    });
  });

  /* پیام کوتاه */
  let toastEl;
  function toast(msg, isError) {
    if (!toastEl) {
      toastEl = document.createElement('div');
      Object.assign(toastEl.style, {
        position: 'fixed', bottom: '1.2rem', left: '50%', transform: 'translateX(-50%)', padding: '.5rem 1.1rem',
        borderRadius: '10px', fontSize: '.85rem', zIndex: 99, transition: 'opacity .3s', pointerEvents: 'none',
      });
      document.body.appendChild(toastEl);
    }
    toastEl.textContent = msg;
    toastEl.style.background = isError ? '#ff6b6b' : '#3ccf91';
    toastEl.style.color = '#0b1430';
    toastEl.style.opacity = '1';
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(() => { toastEl.style.opacity = '0'; }, 1800);
  }
})();
