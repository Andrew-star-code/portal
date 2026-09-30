(() => {
  // ---------- слайдер (только на главной) ----------
  const slides = [...document.querySelectorAll('.slide')];
  if (slides.length) {
    const gauge = '<svg class="gauge" viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="46" fill="none" stroke="#fff" stroke-width="2"/>' +
      Array.from({length: 21}, (_, i) => {
        const a = (-225 + i * 13.5) * Math.PI / 180, r1 = i % 5 ? 38 : 34;
        return `<line x1="${50 + r1 * Math.cos(a)}" y1="${50 + r1 * Math.sin(a)}" x2="${50 + 42 * Math.cos(a)}" y2="${50 + 42 * Math.sin(a)}" stroke="#fff" stroke-width="${i % 5 ? 1 : 2}"/>`;
      }).join('') +
      '<line x1="50" y1="50" x2="74" y2="30" stroke="#fff" stroke-width="2.5"/><circle cx="50" cy="50" r="4" fill="#fff"/></svg>';

    // треугольная сетка в нижних углах
    let m = '';
    for (let row = 0; row < 3; row++) {
      const cols = [1, 3, 4][row], y = 260 - (row + 1) * 86;
      for (let c = 0; c < cols; c++) {
        const x = c * 66;
        m += `<rect x="${x}" y="${y}" width="66" height="86" fill="none" stroke="#fff" stroke-width="1.2"/><line x1="${x}" y1="${y + 86}" x2="${x + 66}" y2="${y}" stroke="#fff" stroke-width="1.2"/>`;
      }
    }
    const mesh = `<svg class="mesh l" viewBox="0 0 300 260" aria-hidden="true">${m}</svg><svg class="mesh r" viewBox="0 0 300 260" aria-hidden="true">${m}</svg>`;
    slides.forEach(s => s.insertAdjacentHTML('beforeend', gauge + mesh));

    const dots = document.querySelector('.dots');
    let cur = 0, timer;
    if (slides.length > 1) {
      slides.forEach((s, i) => {
        const b = document.createElement('button');
        b.setAttribute('aria-label', s.querySelector('h2').textContent.trim());
        b.onclick = () => { go(i); restart(); };
        dots.appendChild(b);
      });
    }

    // следующий слайд въезжает справа, текущий уезжает влево
    function go(i, dir) {
      const n = (i + slides.length) % slides.length;
      if (n !== cur && slides[cur].classList.contains('active')) {
        dir = dir || (n > cur ? 1 : -1);
        const inS = slides[n], outS = slides[cur];
        inS.style.transition = 'none'; inS.style.transform = `translateX(${dir * 100}%)`; inS.offsetWidth;
        inS.style.transition = ''; inS.style.transform = 'translateX(0)';
        outS.style.transform = `translateX(${-dir * 100}%)`;
        outS.classList.remove('active'); inS.classList.add('active');
      } else {
        slides.forEach((s, k) => { s.style.transform = k === n ? 'translateX(0)' : 'translateX(100%)'; s.classList.toggle('active', k === n); });
      }
      cur = n;
      slides.forEach((s, k) => s.inert = k !== cur);
      [...dots.children].forEach((d, k) => d.setAttribute('aria-current', k === cur));
    }
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    function restart() { clearInterval(timer); if (!reduce && slides.length > 1) timer = setInterval(() => go(cur + 1, 1), 5000); }
    go(0); restart();

    const sl = document.querySelector('.slider');
    sl.addEventListener('mouseenter', () => clearInterval(timer));
    sl.addEventListener('mouseleave', restart);
    let x0 = null;
    sl.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, {passive: true});
    sl.addEventListener('touchend', e => {
      if (x0 === null) return;
      const dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40 && slides.length > 1) { go(cur + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1); restart(); }
    });
  }

  // ---------- выпадающее меню ----------
  const btns = [...document.querySelectorAll('button.nav-btn')];
  const close = except => btns.forEach(b => {
    if (b !== except) { b.setAttribute('aria-expanded', 'false'); b.nextElementSibling.classList.remove('show'); }
  });
  btns.forEach(b => b.addEventListener('click', e => {
    e.stopPropagation();
    const open = b.getAttribute('aria-expanded') === 'true';
    close(b);
    b.setAttribute('aria-expanded', !open);
    b.nextElementSibling.classList.toggle('show', !open);
  }));
  document.addEventListener('click', () => close());
  document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

  const burger = document.getElementById('burger'), nav = document.getElementById('nav');
  burger.onclick = e => {
    e.stopPropagation();
    const o = nav.classList.toggle('open');
    burger.setAttribute('aria-expanded', o);
  };
})();
