/* =====================================================================
   بروشور الکترونیک کنسرت — اسکریپت صفحه‌ی عمومی
   (بدون وابستگی خارجی)
   ===================================================================== */
(function () {
  'use strict';

  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const faDigits = (s) => String(s).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
  const fmt = (sec) => {
    if (!isFinite(sec)) return '۰۰:۰۰';
    const m = Math.floor(sec / 60), s = Math.floor(sec % 60);
    return faDigits(String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0'));
  };

  /* ---------- ظاهر شدن تدریجی ---------- */
  const reveals = $$('.reveal');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    reveals.forEach((el) => io.observe(el));
  } else {
    reveals.forEach((el) => el.classList.add('is-visible'));
  }

  /* ---------- هایلایت منو بر اساس اسکرول ---------- */
  const navLinks = $$('.nav a[href^="#"]');
  const sections = navLinks.map((a) => $(a.getAttribute('href'))).filter(Boolean);
  if (sections.length && 'IntersectionObserver' in window) {
    const sio = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) {
          navLinks.forEach((a) => a.classList.toggle('is-active', a.getAttribute('href') === '#' + en.target.id));
        }
      });
    }, { rootMargin: '-40% 0px -55% 0px' });
    sections.forEach((s) => sio.observe(s));
  }

  /* ---------- دکمه‌ی بازگشت به بالا ---------- */
  const toTop = $('.to-top');
  const onScroll = () => toTop && toTop.classList.toggle('is-visible', window.scrollY > 600);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- پخش‌کننده‌ی قطعات (یک صدا در هر لحظه) ---------- */
  const audio = new Audio();
  audio.preload = 'none';
  let current = null; // article.track

  const setPlaying = (track, playing) => {
    track.classList.toggle('is-playing', playing);
    const btn = $('.track__play', track);
    if (btn) btn.setAttribute('aria-pressed', playing ? 'true' : 'false');
  };

  $$('.track[data-audio]').forEach((track) => {
    const btn  = $('.track__play', track);
    const seek = $('.player__seek', track);
    const cur  = $('.player__time--cur', track);
    const dur  = $('.player__time--dur', track);

    btn.addEventListener('click', () => {
      if (current === track) {
        if (audio.paused) { audio.play(); } else { audio.pause(); }
        return;
      }
      if (current) { setPlaying(current, false); }
      current = track;
      audio.src = track.dataset.audio;
      audio.play().catch(() => {});
    });

    seek.addEventListener('input', () => {
      if (current !== track || !isFinite(audio.duration)) return;
      audio.currentTime = (seek.value / 1000) * audio.duration;
    });

    track._ui = { seek, cur, dur };
  });

  audio.addEventListener('play',  () => current && setPlaying(current, true));
  audio.addEventListener('pause', () => current && setPlaying(current, false));
  audio.addEventListener('ended', () => {
    if (!current) return;
    setPlaying(current, false);
    // پخش قطعه‌ی بعدی (اگر صوت داشته باشد)
    const all = $$('.track[data-audio]');
    const next = all[all.indexOf(current) + 1];
    if (next) { $('.track__play', next).click(); }
  });
  audio.addEventListener('loadedmetadata', () => { if (current) current._ui.dur.textContent = fmt(audio.duration); });
  audio.addEventListener('timeupdate', () => {
    if (!current) return;
    const { seek, cur } = current._ui;
    const p = isFinite(audio.duration) && audio.duration ? audio.currentTime / audio.duration : 0;
    seek.value = Math.round(p * 1000);
    seek.style.setProperty('--p', (p * 100) + '%');
    cur.textContent = fmt(audio.currentTime);
  });

  /* ---------- باز/بسته کردن متن قطعات ---------- */
  $$('.track__toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      const panel = document.getElementById(btn.getAttribute('aria-controls'));
      const open = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      panel.hidden = open;
    });
  });

  /* ---------- گالری: تکرار آیتم‌ها برای حرکت بی‌وقفه ---------- */
  $$('[data-marquee]').forEach((wrap) => {
    const track = $('.marquee__track', wrap);
    const items = Array.from(track.children);
    if (!items.length) return;
    // حداقل دو برابر عرض صفحه پر شود
    let width = track.scrollWidth;
    while (width < window.innerWidth * 2 && track.children.length < 60) {
      items.forEach((it) => track.appendChild(it.cloneNode(true)));
      width = track.scrollWidth;
    }
    // نیمه‌ی دوم به‌عنوان تکرار (برای translateX(-50%))
    Array.from(track.children).forEach((it) => track.appendChild(it.cloneNode(true)));
    track.style.setProperty('--marquee-duration', Math.max(30, track.scrollWidth / 60) + 's');
  });

  /* ---------- لایت‌باکس ---------- */
  const lb = $('#lightbox');
  if (lb) {
    const img = $('img', lb), cap = $('figcaption', lb);
    let list = [], idx = 0;
    const show = (i) => {
      idx = (i + list.length) % list.length;
      img.src = list[idx].href;
      img.alt = cap.textContent = list[idx].dataset.caption || '';
    };
    const open = (link) => {
      // فقط آیتم‌های یکتا (بدون تکرارهای marquee)
      const seen = new Set();
      list = $$('[data-lightbox]').filter((a) => { if (seen.has(a.href)) return false; seen.add(a.href); return true; });
      show(list.findIndex((a) => a.href === link.href));
      lb.hidden = false;
      document.body.style.overflow = 'hidden';
      $$('[data-marquee]').forEach((m) => m.classList.add('is-paused'));
    };
    const close = () => {
      lb.hidden = true;
      document.body.style.overflow = '';
      $$('[data-marquee]').forEach((m) => m.classList.remove('is-paused'));
    };
    document.addEventListener('click', (e) => {
      const a = e.target.closest('[data-lightbox]');
      if (a) { e.preventDefault(); open(a); }
    });
    $('.lightbox__close', lb).addEventListener('click', close);
    $('.lightbox__nav--prev', lb).addEventListener('click', () => show(idx + 1)); // RTL: راست = قبلی
    $('.lightbox__nav--next', lb).addEventListener('click', () => show(idx - 1));
    lb.addEventListener('click', (e) => { if (e.target === lb) close(); });
    document.addEventListener('keydown', (e) => {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') show(idx + 1);
      if (e.key === 'ArrowRight') show(idx - 1);
    });
    // کشیدن انگشت روی موبایل
    let sx = 0;
    lb.addEventListener('touchstart', (e) => { sx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', (e) => {
      const dx = e.changedTouches[0].clientX - sx;
      if (Math.abs(dx) > 50) show(dx < 0 ? idx + 1 : idx - 1);
    });
  }
})();
