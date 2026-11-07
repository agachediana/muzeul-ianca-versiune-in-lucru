/*
   Scripturile temei Muzeul Ianca

   Aici sunt toate partile care se misca pe site: bara de accesibilitate,
   meniul, felinarul din banner, aparitiile la derulare, linia timpului,
   numaratoarea inversa, filtrele de pe pagina de evenimente, pozele marite
   si scenele facute cu GSAP ScrollTrigger.
*/
(function () {
  'use strict';

  var root = document.documentElement;
  root.classList.remove('no-js');

  // la refresh pagina revine sus, ca animatiile (titlul scris, aparitiile)
  // sa porneasca din nou de la inceput
  var nav = window.performance && performance.getEntriesByType ? performance.getEntriesByType('navigation')[0] : null;
  if (nav && nav.type === 'reload') {
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    if (location.hash) history.replaceState(null, '', location.pathname + location.search);
    var toTop = function () { window.scrollTo({ top: 0, left: 0, behavior: 'instant' }); };
    toTop();
    window.addEventListener('load', function () {
      toTop();
      if (window.ScrollTrigger) window.ScrollTrigger.refresh();
    });
  }

  var store = {
    get: function (k) { try { return localStorage.getItem('mi-' + k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem('mi-' + k, v); } catch (e) {} }
  };

  // miscarea redusa din sistem: raman felinarul si aparitiile prin estompare,
  // dispar doar paralaxa si scenele legate de derulare
  // "Opreste animatiile" din panoul de accesibilitate opreste si ce porneste din scripturi
  var reduceMotion = function () {
    return root.getAttribute('data-motion') === 'reduce' || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  };

  /*
     Panoul de accesibilitate
     marimea textului (3 trepte in jos si 3 in sus) si optiunile care se pornesc
     si se opresc: contrast, linkuri subliniate, font usor de citit, spatiere
     marita, fara animatii, cursor mare, ghid de citire, focus evidentiat,
     tonuri de gri si pornirea singura a ghidului audio. Fiecare alegere e un
     atribut pe <html> (data-contrast, data-links...) si ramane salvata in browser.
  */
  var SIZES = [0.8, 0.87, 0.93, 1, 1.1, 1.2, 1.3];
  var OPTIONS = { contrast: 'high', links: 'underline', readable: 'on', spacing: 'on', motion: 'reduce', cursor: 'on', guide: 'on', focus: 'on', gray: 'on' };

  function initA11y() {
    var step = SIZES.indexOf(parseFloat(store.get('fs')));
    if (step < 0) step = SIZES.indexOf(1);
    var label = document.querySelector('[data-a11y-size]');
    var dec = document.querySelector('[data-a11y="dec"]');
    var inc = document.querySelector('[data-a11y="inc"]');
    var panel = document.getElementById('a11y-panel');
    var opener = document.querySelector('[data-a11y-open]');
    var guide = document.querySelector('.reading-guide');

    var apply = function () {
      root.style.setProperty('--fs-scale', SIZES[step]);
      if (label) label.textContent = Math.round(SIZES[step] * 100) + '%';
      if (dec) dec.setAttribute('aria-disabled', step === 0 ? 'true' : 'false');
      if (inc) inc.setAttribute('aria-disabled', step === SIZES.length - 1 ? 'true' : 'false');
      Object.keys(OPTIONS).forEach(function (k) {
        var v = store.get(k);
        if (v) root.setAttribute('data-' + k, v); else root.removeAttribute('data-' + k);
        document.querySelectorAll('[data-a11y="' + k + '"]').forEach(function (b) { b.setAttribute('aria-pressed', v ? 'true' : 'false'); });
      });
      // ghidul audio porneste singur, daca nu a fost oprit din panou
      document.querySelectorAll('[data-a11y="autoread"]').forEach(function (b) { b.setAttribute('aria-pressed', store.get('autoread') === 'off' ? 'false' : 'true'); });
    };
    apply();

    // panoul se deschide din bara de sus si se inchide cu Esc, cu X sau cu clic in afara lui
    var open = function (on) {
      if (!panel || !opener) return;
      panel.hidden = !on;
      opener.setAttribute('aria-expanded', on ? 'true' : 'false');
      if (on) panel.querySelector('button').focus(); else opener.focus({ preventScroll: true });
    };
    if (opener) opener.addEventListener('click', function () { open(panel.hidden); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel && !panel.hidden) open(false); });
    document.addEventListener('pointerdown', function (e) {
      if (panel && !panel.hidden && !panel.contains(e.target) && !opener.contains(e.target)) { panel.hidden = true; opener.setAttribute('aria-expanded', 'false'); }
    });

    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-a11y-close]')) { open(false); return; }
      var btn = e.target.closest('[data-a11y]');
      if (!btn) return;
      var act = btn.getAttribute('data-a11y');
      if (act === 'inc') step = Math.min(SIZES.length - 1, step + 1);
      else if (act === 'dec') step = Math.max(0, step - 1);
      else if (act === 'autoread') store.set('autoread', store.get('autoread') === 'off' ? 'on' : 'off');
      else if (act === 'reset') {
        step = SIZES.indexOf(1);
        Object.keys(OPTIONS).forEach(function (k) { store.set(k, ''); });
        store.set('autoread', 'on');
      } else if (OPTIONS[act]) store.set(act, store.get(act) ? '' : OPTIONS[act]);
      store.set('fs', SIZES[step]);
      apply();
      // pozitiile animatiilor depind de inaltimea textului, deci se recalculeaza
      if (window.ScrollTrigger) window.ScrollTrigger.refresh();
    });

    // ghidul de citire: o banda luminoasa care urmareste mouse-ul sau degetul
    if (guide) {
      document.addEventListener('pointermove', function (e) {
        if (root.hasAttribute('data-guide')) guide.style.transform = 'translateY(' + (e.clientY - 22) + 'px)';
      }, { passive: true });
    }
  }

  /*
     Antetul se micsoreaza cand derulezi
  */
  function initHeader() {
    var header = document.querySelector('.header');
    var fab = document.querySelector('.ai-fab');
    if (!header) return;
    var ticking = false;
    var update = function () {
      var y = window.scrollY;
      header.classList.toggle('is-scrolled', y > 20);
      if (fab) fab.classList.toggle('is-visible', y > 400);
      ticking = false;
    };
    window.addEventListener('scroll', function () {
      if (!ticking) { requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    update();
  }

  /*
     Bara de sus ramane lipita sus cu antetul.
     --topbar-h e cat se vede din ea dupa ce derulezi (toata pe calculator,
     doar randul cu informatii pe telefon), --topbar-full e inaltimea intreaga.
     Pe telefon, daca programul, adresa si telefonul nu incap pe un rand,
     apar pe rand, la cateva secunde (se opreste cand atingi sau treci cu mouse-ul).
  */
  function initTopbar() {
    var top = document.querySelector('.site-top');
    var bar = top && top.querySelector('.topbar');
    var info = bar && bar.querySelector('.topbar__info');
    if (!bar) return;
    var root = document.documentElement;
    var items = info ? [].slice.call(info.children) : [];
    var cur = 0, timer = null, paused = false;

    var show = function (i) {
      items.forEach(function (s, k) {
        s.classList.toggle('is-on', k === i);
        s.classList.toggle('is-off', k === cur && k !== i);
      });
      cur = i;
    };
    var stop = function () { clearInterval(timer); timer = null; };
    var start = function () {
      stop();
      if (!info.classList.contains('is-ticker')) return;
      timer = setInterval(function () {
        if (!paused && !document.hidden) show((cur + 1) % items.length);
      }, reduceMotion() ? 6000 : 3500);
    };

    var measure = function () {
      var offset = parseFloat(getComputedStyle(top).top) || 0;
      root.style.setProperty('--topbar-full', bar.offsetHeight + 'px');
      root.style.setProperty('--topbar-h', (bar.offsetHeight + Math.min(0, offset)) + 'px');
      if (!info || items.length < 2) return;
      info.classList.remove('is-ticker');
      items.forEach(function (s) { s.classList.remove('is-on', 'is-off'); });
      if (window.matchMedia('(max-width: 1080px)').matches && info.scrollWidth > info.clientWidth + 1) {
        info.classList.add('is-ticker');
        show(cur);
      }
      start();
    };

    if (info) {
      info.addEventListener('pointerenter', function () { paused = true; });
      info.addEventListener('pointerleave', function () { paused = false; });
      // cu tastatura, linkul telefonului apare cand ajungi pe el
      info.addEventListener('focusin', function (ev) {
        var i = items.indexOf(ev.target.closest('span'));
        paused = true;
        if (i > -1 && info.classList.contains('is-ticker')) show(i);
      });
      info.addEventListener('focusout', function () { paused = false; });
    }
    measure();
    (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(measure);
    window.addEventListener('resize', measure);
  }

  /*
     Meniul
     pe calculator se deschide in jos la hover, cu o mica intarziere ca sa nu
     se deschida cand doar treci pe langa, si la click. Pe telefon e acordeon.
  */
  function initNav() {
    var body = document.body;
    var toggle = document.querySelector('.nav-toggle');
    var scrim = document.querySelector('.nav-scrim');
    var items = document.querySelectorAll('.nav__list > .nav__item.has-sub');
    var desktop = window.matchMedia('(min-width: 1081px)');

    var setOpen = function (item, open) {
      item.classList.toggle('is-open', open);
      if (open) item._openedAt = performance.now();
      var btn = item.querySelector(':scope > .nav__link');
      if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    var closeAll = function (except) {
      items.forEach(function (it) { if (it !== except) setOpen(it, false); });
    };

    items.forEach(function (item) {
      var btn = item.querySelector(':scope > .nav__link');
      var openTimer, closeTimer;

      item.addEventListener('pointerenter', function (e) {
        if (e.pointerType !== 'mouse' || !desktop.matches) return;
        clearTimeout(closeTimer);
        openTimer = setTimeout(function () { closeAll(item); setOpen(item, true); }, 120);
      });
      item.addEventListener('pointerleave', function (e) {
        if (e.pointerType !== 'mouse' || !desktop.matches) return;
        clearTimeout(openTimer);
        closeTimer = setTimeout(function () { setOpen(item, false); }, 280);
      });
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var isOpen = item.classList.contains('is-open');
        // daca abia s-a deschis la hover, click-ul nu il inchide imediat
        if (isOpen && performance.now() - (item._openedAt || 0) < 500) return;
        if (desktop.matches) closeAll(item);
        setOpen(item, !isOpen);
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      closeAll();
      if (body.classList.contains('nav-open')) { setMobile(false); toggle.focus(); }
    });
    document.addEventListener('click', function (e) {
      if (desktop.matches && !e.target.closest('.nav')) closeAll();
    });
    document.querySelectorAll('.nav__list > .nav__item.has-sub').forEach(function (item) {
      item.addEventListener('focusout', function (e) {
        if (desktop.matches && !item.contains(e.relatedTarget)) setOpen(item, false);
      });
    });

    var setMobile = function (open) {
      body.classList.toggle('nav-open', open);
      if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    if (toggle) toggle.addEventListener('click', function () { setMobile(!body.classList.contains('nav-open')); });
    if (scrim) scrim.addEventListener('click', function () { setMobile(false); });
    desktop.addEventListener('change', function () { setMobile(false); closeAll(); });
  }

  /*
     Felinarul din banner
     fotografia alb-negru se coloreaza in lumina felinarului, iar felinarul se vede tot timpul.
     - la inceput, si cand mouse-ul pleaca, felinarul se plimba singur cu lumina
     - pe calculator felinarul sta agatat de cursor si se leagana ca un pendul,
       lumina pleaca din flacara, iar click-urile o maresc putin
     - pe telefon felinarul merge acolo unde atingi si lumineaza putin locul
     Lumina e un cerc (.hero__spot) mutat doar cu transform, asa ca browserul
     nu redeseneaza fotografia la fiecare cadru.
  */
  function initHero() {
    var hero = document.querySelector('.hero');
    if (!hero) return;
    var media = hero.querySelector('.hero__media');
    var spot = hero.querySelector('.hero__spot');
    var spotImg = spot && spot.querySelector('img');
    var glow = hero.querySelector('.hero__glow');
    var lantern = hero.querySelector('.hero__lantern');
    if (lantern) hero.classList.add('has-lantern');
    if (!media || !spot || !spotImg) return;

    var ARM = 32;                            // distanta de la inel la flacara, in px
    var rect, mRect, mW = 0, mH = 0, mScale = 1;
    var mouse = { x: 0, y: 0 };
    var pos = { x: 0, y: 0 };                // unde e inelul felinarului
    var prevPos = { x: 0, y: 0 };
    var prevVx = 0;
    var angle = 0, spin = 0;                 // unghiul pendulului si viteza lui
    var light = { x: 0, y: 0, r: 0 };        // centrul si raza luminii, in px fata de banner
    var shown = { x: NaN, y: NaN, r: -1 };   // ce e deja desenat, ca sa nu scriem degeaba
    var hovering = false, visible = true;
    var boost = 0, lastClick = 0;            // cat s-a marit lumina din click-uri
    var tap = null;                          // atingerea pe telefon
    var t0 = performance.now();

    // dimensiunile bannerului si ale fotografiei (care se misca la derulare)
    var track = function () {
      rect = hero.getBoundingClientRect();
      mRect = media.getBoundingClientRect();
      mScale = mW ? mRect.width / mW : 1;
    };
    var measure = function () {
      mW = media.offsetWidth; mH = media.offsetHeight;
      spotImg.style.width = mW + 'px';
      spotImg.style.height = mH + 'px';
      track();
      shown.r = -1;
    };
    measure();
    // drumul pe care se plimba singur felinarul (punctul in care sta flacara)
    var drift = function (now) {
      var t = (now - t0) / 1000;
      return {
        x: rect.width * (0.5 + Math.sin(t * 0.23) * 0.3),
        y: rect.height * (0.45 + Math.sin(t * 0.31 + 1.2) * 0.18)
      };
    };
    var start = drift(t0);
    light.x = start.x; light.y = start.y;
    pos.x = prevPos.x = start.x; pos.y = prevPos.y = start.y - ARM;
    window.addEventListener('resize', measure);
    window.addEventListener('scroll', track, { passive: true });

    // raza de baza e mica, fiecare click o mareste putin pana la o limita
    var baseR = function () { return Math.max(105, Math.min(rect.width, rect.height) * 0.19); };

    hero.addEventListener('pointermove', function (e) {
      if (e.pointerType !== 'mouse') return;
      if (!hovering) {
        track();
        hovering = true;
        hero.classList.add('is-hover');
      }
      mouse.x = e.clientX - rect.left;
      mouse.y = e.clientY - rect.top;
      // peste butoane si putin in jurul lor felinarul dispare si revine cursorul normal
      hero.classList.toggle('over-ui', !!e.target.closest('a, button') || nearButton(e.clientX, e.clientY));
    });
    var buttons = hero.querySelectorAll('.hero__ctas a, .hero__ctas button');
    var nearButton = function (cx, cy) {
      for (var i = 0; i < buttons.length; i++) {
        var b = buttons[i].getBoundingClientRect();
        if (cx > b.left - 36 && cx < b.right + 36 && cy > b.top - 28 && cy < b.bottom + 28) return true;
      }
      return false;
    };
    // dublu sau triplu click nu mai selecteaza titlul
    hero.addEventListener('mousedown', function (e) {
      if (!e.target.closest('a, button') && e.detail > 1) e.preventDefault();
    });
    hero.addEventListener('pointerleave', function (e) {
      if (e.pointerType !== 'mouse') return;
      hovering = false;
      hero.classList.remove('is-hover', 'over-ui');
    });
    hero.addEventListener('pointerdown', function (e) {
      if (e.target.closest('a, button')) return;
      track();
      if (e.pointerType !== 'mouse') {
        // pe telefon felinarul merge unde ai atins, lumina se mareste putin, apoi isi reia plimbarea
        tap = { x: e.clientX - rect.left, y: e.clientY - rect.top, until: performance.now() + 2600 };
        hero.classList.add('is-raised');
        setTimeout(function () { hero.classList.remove('is-raised'); }, 400);
        return;
      }
      // cu mouse-ul fiecare click mareste putin lumina, fara click-uri se strange la loc
      var r0 = baseR();
      boost = Math.min(boost + r0 * 0.22, r0 * 1.4);
      lastClick = performance.now();
      hero.classList.add('is-raised');
      setTimeout(function () { hero.classList.remove('is-raised'); }, 400);
    });

    // deseneaza lumina: doar transform, iar marimea doar cand se schimba raza
    var draw = function () {
      var mx = (light.x + rect.left - mRect.left) / mScale;
      var my = (light.y + rect.top - mRect.top) / mScale;
      var r = Math.round(light.r / mScale);
      if (r !== shown.r) {
        spot.style.width = spot.style.height = (r * 2) + 'px';
        if (glow) glow.style.width = glow.style.height = Math.round(r * 2.3) + 'px';
        shown.r = r;
        shown.x = NaN;
      }
      if (Math.abs(mx - shown.x) < 0.1 && Math.abs(my - shown.y) < 0.1) return;
      shown.x = mx; shown.y = my;
      var sx = (mx - r).toFixed(1), sy = (my - r).toFixed(1);
      spot.style.transform = 'translate3d(' + sx + 'px,' + sy + 'px,0)';
      spotImg.style.transform = 'translate3d(' + (-sx) + 'px,' + (-sy) + 'px,0)';
      if (glow) glow.style.transform = 'translate3d(' + (mx - r * 1.15).toFixed(1) + 'px,' + (my - r * 1.15).toFixed(1) + 'px,0)';
    };

    var frame = function (now) {
      if (!visible) return;
      var gx, gy, tr, k;

      // unde trebuie sa ajunga inelul felinarului si cat de repede
      if (hovering) {
        // cu mouse-ul: inelul urmeaza cursorul, putin amortizat
        gx = mouse.x; gy = mouse.y; k = 0.35;
        // la o pauza dupa ultimul click lumina scade treptat
        if (now - lastClick > 700) boost *= 0.98;
        if (boost < 0.5) boost = 0;
        tr = baseR() + boost;
      } else if (tap && now < tap.until) {
        // pe telefon: felinarul merge cu flacara exact unde ai atins
        gx = tap.x; gy = tap.y - ARM; k = 0.12;
        tr = baseR() * 1.35;
      } else {
        // fara mouse sau atingere felinarul se plimba singur, incet
        tap = null; boost = 0;
        var d = drift(now);
        gx = d.x; gy = d.y - ARM; k = 0.03;
        tr = baseR();
      }
      pos.x += (gx - pos.x) * k;
      pos.y += (gy - pos.y) * k;

      // pendulul: cand felinarul se misca in lateral, ramane putin in urma si se leagana
      var vx = pos.x - prevPos.x;
      var ax = vx - prevVx;
      prevVx = vx; prevPos.x = pos.x; prevPos.y = pos.y;
      spin += -ax * 0.55 - vx * 0.06 - angle * 0.045;
      spin *= 0.9;
      angle = Math.max(-38, Math.min(38, angle + spin));

      // lumina pleaca din flacara, care e la capatul bratului rotit cu unghiul pendulului
      var rad = angle * Math.PI / 180;
      light.x += (pos.x - Math.sin(rad) * ARM - light.x) * 0.6;
      light.y += (pos.y + Math.cos(rad) * ARM - light.y) * 0.6;
      light.r += (tr - light.r) * 0.12;
      if (lantern) {
        lantern.style.transform = 'translate3d(' + pos.x.toFixed(1) + 'px,' + pos.y.toFixed(1) + 'px,0) rotate(' + angle.toFixed(2) + 'deg)';
      }
      draw();
      requestAnimationFrame(frame);
    };

    // bucla merge doar cat timp bannerul se vede pe ecran
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        var was = visible;
        visible = entries[0].isIntersecting;
        if (visible && !was) { track(); requestAnimationFrame(frame); }
      }).observe(hero);
    }
    requestAnimationFrame(frame);

    // fotografia se misca putin la derulare
    var onScroll = function () {
      if (reduceMotion()) { media.style.transform = ''; return; }
      var y = window.scrollY;
      if (y > rect.height + 200) return;
      media.style.transform = 'translate3d(0,' + (y * 0.22).toFixed(1) + 'px,0) scale(' + (1 + y * 0.00012).toFixed(4) + ')';
    };
    window.addEventListener('scroll', function () { requestAnimationFrame(onScroll); }, { passive: true });
  }

  /*
     Titlul din banner se scrie de la stanga la dreapta (animatia e in CSS),
     apoi apar randul de sub el si butoanele
  */
  function introHero() {
    var title = document.querySelector('.hero__title');
    if (!title) return;
    title.classList.add('is-writing');
    var gsap = window.gsap;
    if (!gsap) return;
    gsap.from('.hero__sub, .hero__ctas > *', { autoAlpha: 0, duration: 0.9, stagger: 0.12, ease: 'power1.out', delay: 1.5 });
  }

  /*
     Aparitii la derulare si numerele care cresc
  */
  function initReveal() {
    var els = Array.prototype.filter.call(
      document.querySelectorAll('.reveal, .reveal-img, .develop, .draw, .press, .split, .stitch-sm, .hand-note, [data-count]'),
      function (el) { return !el.classList.contains('is-in'); }
    );
    // la miscare redusa elementele apar tot la derulare, dar doar prin estompare (vezi CSS)
    if (!('IntersectionObserver' in window)) {
      els.forEach(function (el) {
        el.classList.add('is-in');
        if (el.hasAttribute('data-count')) el.textContent = el.getAttribute('data-count');
      });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target;
        el.classList.add('is-in');
        if (el.hasAttribute('data-count')) countUp(el);
        io.unobserve(el);
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 });
    els.forEach(function (el) { io.observe(el); });
  }

  function countUp(el) {
    var end = parseInt(el.getAttribute('data-count'), 10) || 0;
    // anii (data-plain) nu primesc punct de mii si pornesc de mai aproape, ca un calendar care se da inainte
    var plain = el.hasAttribute('data-plain');
    var start = plain ? end - 60 : 0;
    var dur = 2400, t0 = null;
    // numaratoarea nu misca nimic pe ecran, asa ca se opreste doar din butonul "Opreste animatiile"
    if (root.getAttribute('data-motion') === 'reduce') { el.textContent = plain ? end : end.toLocaleString('ro-RO'); return; }
    var fmt = function (n) { return plain ? String(n) : n.toLocaleString('ro-RO'); };
    var step = function (t) {
      if (!t0) t0 = t;
      var p = Math.min(1, (t - t0) / dur);
      var e = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt(Math.round(start + (end - start) * e));
      if (p < 1) requestAnimationFrame(step);
    };
    /*
       numaratoarea porneste de la zero si asteapta ca cifra sa apara pe ecran
       (cardul ei vine cu intarziere, vezi --d), ca sa se vada cum urca pana la numarul bun
    */
    el.textContent = fmt(start);
    var box = el.closest('.reveal');
    var d = box ? parseFloat(getComputedStyle(box).getPropertyValue('--d')) || 0 : 0;
    setTimeout(function () { requestAnimationFrame(step); }, box ? d * 90 + 350 : 0);
  }

  /*
     Linia timpului se deseneaza pe masura ce derulezi
  */
  function initTimeline() {
    var tl = document.querySelector('.timeline');
    if (!tl) return;
    var line = tl.querySelector('.timeline__line');
    var items = tl.querySelectorAll('.tl');
    var vertical = window.matchMedia('(max-width: 1000px)');
    var update = function () {
      var r = tl.getBoundingClientRect();
      var vh = window.innerHeight;
      var p = reduceMotion() ? 1 : Math.max(0, Math.min(1, (vh * 0.85 - r.top) / (r.height + vh * 0.35)));
      line.style.setProperty('--p', p.toFixed(3));
      items.forEach(function (it, i) {
        var on;
        if (vertical.matches) {
          on = it.getBoundingClientRect().top < vh * 0.8 || p >= 1;
        } else {
          on = p >= (i + 0.2) / items.length || p >= 1;
        }
        it.classList.toggle('is-on', on);
      });
    };
    window.addEventListener('scroll', function () { requestAnimationFrame(update); }, { passive: true });
    window.addEventListener('resize', update);
    update();
  }

  /*
     Comutatorul cu pastila (daca e pe pagina) si numaratoarea inversa
  */
  function initTabs() {
    document.querySelectorAll('[data-tabs]').forEach(function (wrap) {
      var btns = wrap.querySelectorAll('[role="tab"]');
      var pill = wrap.querySelector('.tabs__pill');
      var move = function (btn) {
        if (!pill) return;
        pill.style.width = btn.offsetWidth + 'px';
        pill.style.transform = 'translateX(' + (btn.offsetLeft - 4) + 'px)';
      };
      var select = function (btn, focus) {
        btns.forEach(function (b) {
          var on = b === btn;
          b.setAttribute('aria-selected', on ? 'true' : 'false');
          b.tabIndex = on ? 0 : -1;
          var panel = document.getElementById(b.getAttribute('aria-controls'));
          if (panel) panel.hidden = !on;
        });
        move(btn);
        if (focus) btn.focus();
        initReveal();
      };
      btns.forEach(function (b, i) {
        b.addEventListener('click', function () { select(b); });
        b.addEventListener('keydown', function (e) {
          var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
          if (d) { e.preventDefault(); select(btns[(i + d + btns.length) % btns.length], true); }
        });
      });
      var active = wrap.querySelector('[aria-selected="true"]') || btns[0];
      // pastila se pune la loc dupa ce se incarca fonturile
      (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(function () { move(active); });
      window.addEventListener('resize', function () { move(wrap.querySelector('[aria-selected="true"]')); });
    });
  }

  function initCountdown() {
    document.querySelectorAll('[data-countdown]').forEach(function (el) {
      var end = new Date(el.getAttribute('data-countdown')).getTime();
      if (isNaN(end)) { el.remove(); return; }
      var parts = {
        d: el.querySelector('[data-d]'), h: el.querySelector('[data-h]'),
        m: el.querySelector('[data-m]'), s: el.querySelector('[data-s]')
      };
      var pad = function (n) { return (n < 10 ? '0' : '') + n; };
      var tick = function () {
        var diff = Math.max(0, end - Date.now());
        var s = Math.floor(diff / 1000);
        parts.d.textContent = Math.floor(s / 86400);
        parts.h.textContent = pad(Math.floor(s % 86400 / 3600));
        parts.m.textContent = pad(Math.floor(s % 3600 / 60));
        parts.s.textContent = pad(s % 60);
        if (diff > 0) setTimeout(tick, 1000);
      };
      tick();
    });
  }

  /*
     Pagina Evenimente
     filtrele dupa stare (grupele se rearanjeaza cu GSAP Flip), anii din arhiva
     care se marcheaza pe masura ce derulezi si firul cusut care se umple
  */
  function initEvents() {
    var page = document.querySelector('[data-evp]');
    if (!page) return;
    var gsap = window.gsap, Flip = window.Flip, ST = window.ScrollTrigger;
    if (gsap && Flip) gsap.registerPlugin(Flip);
    if (gsap && ST) gsap.registerPlugin(ST);

    var bar = page.querySelector('[data-evp-bar]');
    var filter = page.querySelector('.evp-filter');
    var pill = filter.querySelector('.tabs__pill');
    var btns = [].slice.call(filter.querySelectorAll('[data-filter]'));
    var groups = [].slice.call(page.querySelectorAll('.evg'));
    var wrap = page.querySelector('.evp-groups');
    var note = page.querySelector('[data-evp-note]');

    // "Toate" arata tot, mai putin grupa "In desfasurare" cand e goala
    var shows = function (g, f) {
      return f === 'toate' ? !g.classList.contains('is-empty') : g.getAttribute('data-group') === f;
    };
    var movePill = function (btn) {
      pill.style.width = btn.offsetWidth + 'px';
      pill.style.height = btn.offsetHeight + 'px';
      pill.style.transform = 'translate(' + btn.offsetLeft + 'px,' + btn.offsetTop + 'px)';
    };
    var pressed = function () { return filter.querySelector('[aria-pressed="true"]'); };
    var barSize = function () { page.style.setProperty('--bar-h', bar.offsetHeight + 'px'); };
    // unde se lipeste bara de filtre: sub antetul micsorat (67px) si sub bara de sus
    var stickTop = function () {
      return 67 + (parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--topbar-h')) || 0);
    };

    var apply = function (btn) {
      var f = btn.getAttribute('data-filter');
      btns.forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
      movePill(btn);
      if (note) note.textContent = 'Se afișează: ' + btn.textContent.replace(/(\d+)$/, ' ($1)');

      var leaving = groups.filter(function (g) { return !g.hidden && !shows(g, f); });
      var entering = groups.filter(function (g) { return g.hidden && shows(g, f); });
      var staying = groups.filter(function (g) { return !g.hidden && shows(g, f); });
      var swap = function () {
        leaving.forEach(function (g) { g.hidden = true; });
        entering.forEach(function (g) { g.hidden = false; });
        initReveal();
        if (ST) ST.refresh();
      };

      // daca bara e lipita sus, ne intoarcem la inceputul listei
      var top = wrap.getBoundingClientRect().top + window.scrollY - stickTop() - bar.offsetHeight;
      if (window.scrollY > top + 4) window.scrollTo({ top: top, behavior: reduceMotion() ? 'auto' : 'smooth' });

      if (!gsap || !Flip) { swap(); return; }
      var tl = gsap.timeline();
      if (leaving.length) tl.to(leaving, { autoAlpha: 0, y: -14, duration: 0.22, ease: 'power1.in' });
      tl.add(function () {
        var state = Flip.getState(staying);
        swap();
        gsap.set(leaving, { clearProps: 'opacity,visibility,transform' });
        Flip.from(state, { duration: 0.6, ease: 'power3.inOut' });
        if (entering.length) {
          gsap.fromTo(entering, { autoAlpha: 0, y: 32 }, {
            autoAlpha: 1, y: 0, duration: 0.65, delay: 0.1, stagger: 0.08, ease: 'power3.out',
            clearProps: 'opacity,visibility,transform'
          });
        }
      });
    };

    btns.forEach(function (b) {
      b.addEventListener('click', function () { if (b !== pressed()) apply(b); });
    });
    (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(function () {
      movePill(pressed());
      barSize();
    });
    window.addEventListener('resize', function () { movePill(pressed()); barSize(); });

    if (!gsap || !ST) return;

    // bara de filtre primeste o umbra cand ramane lipita sus
    ST.create({
      trigger: bar, start: function () { return 'top ' + (stickTop() + 1) + 'px'; }, endTrigger: page, end: 'bottom top',
      toggleClass: { targets: bar, className: 'is-stuck' }
    });

    // anul la care ai ajuns se marcheaza in lista din stanga si cifra lui mare se umple
    var links = [].slice.call(page.querySelectorAll('[data-year-link]'));
    var years = [].slice.call(page.querySelectorAll('.evp-year'));
    var setYear = function (y) {
      links.forEach(function (l) {
        var on = l.getAttribute('data-year-link') === y;
        l.classList.toggle('is-active', on);
        if (on) l.setAttribute('aria-current', 'true'); else l.removeAttribute('aria-current');
      });
      years.forEach(function (s) { s.classList.toggle('is-active', s.getAttribute('data-year') === y); });
    };
    years.forEach(function (sec) {
      ST.create({
        trigger: sec, start: 'top 55%', end: 'bottom 55%',
        onToggle: function (self) { if (self.isActive) setYear(sec.getAttribute('data-year')); }
      });
    });

    // firul cusut creste pe masura ce derulezi prin arhiva
    var fill = page.querySelector('[data-evp-fill]');
    var list = page.querySelector('.evp-arch__list');
    if (fill && list) {
      gsap.fromTo(fill, { scaleY: 0 }, {
        scaleY: 1, ease: 'none',
        scrollTrigger: { trigger: list, start: 'top 55%', end: 'bottom 55%', scrub: 0.4 }
      });
    }
  }

  /*
     Pagina Vizita si bilete
     daca muzeul e deschis acum (dupa ora Romaniei, oricare ar fi ora vizitatorului),
     ziua de azi marcata in program si cotorul biletului care se rupe la clic
  */
  function initVisit() {
    // doar elementul cu programul (cardurile de evenimente au si ele data-status)
    var status = document.querySelector('[data-status][data-orar]');
    var days = document.querySelector('[data-days]');
    var ticket = document.querySelector('[data-ticket]');
    var orar = [];
    try { orar = JSON.parse(status ? status.getAttribute('data-orar') : '[]') || []; } catch (err) { orar = []; }

    // ziua (0 = luni) si minutul din zi, in ora Romaniei
    var acum = function () {
      var p = {};
      new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Bucharest', weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
        .formatToParts(new Date()).forEach(function (x) { p[x.type] = x.value; });
      return { zi: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].indexOf(p.weekday), min: +p.hour * 60 + +p.minute };
    };
    var minute = function (s) { var a = s.split(':'); return +a[0] * 60 + +a[1]; };

    var update = function () {
      if (!orar.length) return;
      var t = acum();
      var azi = orar[t.zi];
      if (status) {
        var text = status.querySelector('[data-status-text]');
        var deschis = azi && azi.de && t.min >= minute(azi.de) && t.min < minute(azi.la);
        var mesaj;
        if (deschis) {
          mesaj = 'Deschis acum · până la ' + azi.la;
        } else if (azi && azi.de && t.min < minute(azi.de)) {
          mesaj = 'Închis acum · deschidem azi la ' + azi.de;
        } else {
          // urmatoarea zi cu program
          for (var k = 1; k <= 7; k++) {
            var z = orar[(t.zi + k) % 7];
            if (z && z.de) { mesaj = 'Închis acum · deschidem ' + (k === 1 ? 'mâine' : z.zi.toLowerCase()) + ' la ' + z.de; break; }
          }
        }
        if (mesaj) text.textContent = mesaj;
        status.classList.toggle('is-open', !!deschis);
        status.classList.toggle('is-closed', !deschis);
      }
      if (days) {
        var rows = days.querySelectorAll('[data-day]');
        var row = rows[t.zi];
        rows.forEach(function (r) { r.classList.toggle('is-today', r === row); });
        if (row) {
          var mark = days.querySelector('.vz-days__mark');
          mark.style.height = row.offsetHeight + 'px';
          mark.style.setProperty('--y', row.offsetTop + 'px');
          days.classList.add('has-today');
        }
      }
    };
    update();
    setInterval(update, 60000);
    window.addEventListener('resize', update);

    if (ticket) {
      var link = ticket.querySelector('[data-ticket-link]');
      link.addEventListener('click', function () {
        ticket.classList.add('is-torn');
        setTimeout(function () { ticket.classList.remove('is-torn'); }, 2600);
      });
    }
  }

  /*
     Pagina Tur virtual
     fotografia salii se misca dupa mouse (pe calculator) sau cand o tragi cu
     degetul (pe telefon), ca privita printr-o fereastra. Turul 360 se incarca
     doar dupa clic pe "Intra in sala", ca pana atunci sa nu plece nicio cerere.
  */
  function initTour() {
    document.querySelectorAll('[data-tour-view]').forEach(function (view) {
      var set = function (x, y) {
        view.style.setProperty('--px', Math.max(-1, Math.min(1, x)).toFixed(3));
        view.style.setProperty('--py', Math.max(-1, Math.min(1, y)).toFixed(3));
      };
      var fine = window.matchMedia('(hover: hover) and (pointer: fine)');
      var drag = null;

      view.addEventListener('pointermove', function (e) {
        if (view.classList.contains('is-loaded')) return;
        var r = view.getBoundingClientRect();
        if (drag) {
          set(drag.x - (e.clientX - drag.sx) / r.width * 2, drag.y - (e.clientY - drag.sy) / r.height * 2);
        } else if (fine.matches && !reduceMotion()) {
          set((e.clientX - r.left) / r.width * 2 - 1, (e.clientY - r.top) / r.height * 2 - 1);
        }
      });
      view.addEventListener('pointerleave', function () { if (!drag) set(0, 0); });
      // tras cu degetul sau cu mouse-ul apasat
      view.addEventListener('pointerdown', function (e) {
        if (e.target.closest('button, a') || view.classList.contains('is-loaded')) return;
        drag = {
          sx: e.clientX, sy: e.clientY,
          x: parseFloat(view.style.getPropertyValue('--px')) || 0,
          y: parseFloat(view.style.getPropertyValue('--py')) || 0
        };
        view.classList.add('is-dragging');
      });
      var stop = function () { drag = null; view.classList.remove('is-dragging'); };
      window.addEventListener('pointerup', stop);
      window.addEventListener('pointercancel', stop);

      var go = view.querySelector('[data-tour-go]');
      if (!go) return;
      // turul se deschide mare, pe tot ecranul, crescand din fotografia salii
      go.addEventListener('click', function () {
        var box = view.closest('.tour, .corp');
        var t = box && box.querySelector('.tour__title');
        var corp = box && box.querySelector('.tour__corp');
        openViewer(view.getAttribute('data-tour'), (corp ? corp.textContent.trim() + ' · ' : '') + (t ? t.textContent.trim() : 'Tur virtual'), view, go);
      });
    });

    /*
       venind de pe Acasa (usile duc la #tour-0-acasa ... #tour-3-acasa), turul
       corpului ales porneste singur, iar la inchidere te intoarce pe Acasa,
       la usi, nu ramai pe pagina Tur virtual
    */
    var m = /^#tour-(\d)(-acasa)?$/.exec(location.hash);
    var target = m && document.getElementById('tour-' + m[1]);
    var btn = target && target.querySelector('[data-tour-go]');
    if (btn) {
      target.scrollIntoView({ block: 'center' });
      if (m[2]) {
        var acasa = document.querySelector('.header .brand');
        var fromHome = document.referrer && acasa && document.referrer.split('#')[0] === acasa.href.split('#')[0];
        viewerBack = function () {
          // inapoi in istoric pastreaza locul de pe Acasa; altfel deschidem Acasa la usi
          if (fromHome && history.length > 1) history.back();
          else if (acasa) location.href = acasa.href.split('#')[0] + '#tur-titlu';
        };
      }
      setTimeout(function () { btn.click(); }, reduceMotion() ? 0 : 500);
    }
  }

  /*
     Fereastra mare, pe tot ecranul, pentru turul virtual si obiectele 3D.
     Creste din elementul apasat (poza salii, miniatura obiectului) si se
     strange inapoi in el la inchidere (X, Esc sau clic pe "Inapoi la pagina").
     Continutul (iframe) se incarca doar acum si se scoate la inchidere,
     ca sa nu mai consume nimic cat timp fereastra e inchisa.
  */
  var viewer = null;
  // cand e setata, inchiderea ferestrei te duce inapoi pe pagina de unde ai venit (Acasa)
  var viewerBack = null;
  function openViewer(src, title, from, opener) {
    if (!window.HTMLDialogElement) { window.open(src, '_blank', 'noopener'); return; }
    var gsap = window.gsap;
    if (!viewer) {
      viewer = document.createElement('dialog');
      viewer.className = 'viewer';
      viewer.setAttribute('aria-labelledby', 'viewer-title');
      viewer.innerHTML = '<div class="viewer__bar"><p class="viewer__title" id="viewer-title"></p>'
        + '<span class="viewer__hint">Trage cu mouse-ul sau cu degetul ca să te uiți în jur</span>'
        + '<button type="button" class="viewer__close" aria-label="Închide și întoarce-te la pagină"><span>Închide</span><svg aria-hidden="true"><use href="#i-x"/></svg></button></div>'
        + '<div class="viewer__stage"><p class="viewer__load"><span class="viewer__spin" aria-hidden="true"></span>Se încarcă…</p></div>';
      document.body.appendChild(viewer);
      viewer.querySelector('.viewer__close').addEventListener('click', closeViewer);
      // Esc: inchidem tot noi, cu animatie
      viewer.addEventListener('cancel', function (e) { e.preventDefault(); closeViewer(); });
    }
    var stage = viewer.querySelector('.viewer__stage');
    var old = stage.querySelector('iframe');
    if (old) old.remove();
    viewer.classList.remove('is-loaded');
    viewer.querySelector('.viewer__title').textContent = title || '';
    viewer.querySelector('.viewer__close span').textContent = viewerBack ? 'Înapoi la Acasă' : 'Închide';
    viewer._from = from;
    viewer._opener = opener || from;

    var frame = document.createElement('iframe');
    frame.title = title || 'Fereastră mare';
    frame.setAttribute('allowfullscreen', '');
    frame.setAttribute('allow', 'autoplay; fullscreen; xr-spatial-tracking; gyroscope; accelerometer');
    var loaded = function () { viewer.classList.add('is-loaded'); };
    frame.addEventListener('load', loaded);
    setTimeout(loaded, 9000);
    frame.src = src;
    stage.appendChild(frame);

    document.documentElement.classList.add('has-viewer');
    viewer.showModal();
    viewer.querySelector('.viewer__close').focus({ preventScroll: true });
    if (gsap && from && !reduceMotion()) {
      gsap.fromTo(viewer, { clipPath: clipOf(from) }, { clipPath: 'inset(0px 0px 0px 0px round 0px)', duration: 0.8, ease: 'power3.inOut', clearProps: 'clipPath' });
      gsap.fromTo(viewer.querySelector('.viewer__bar'), { yPercent: -100, autoAlpha: 0 }, { yPercent: 0, autoAlpha: 1, duration: 0.6, delay: 0.45, ease: 'power3.out' });
    }
  }
  /*
     Obiectele scanate 3D de la Galerii foto: la clic se deschide doar modelul
     obiectului, in fereastra mare. Ca sa porneasca mai repede: legatura cu
     Sketchfab se pregateste cand mouse-ul ajunge pe card, modelul porneste
     singur, fara ecranul de start, iar texturile sunt mai mici pe telefon.
  */
  function initModels() {
    var cards = document.querySelectorAll('[data-model]');
    if (!cards.length) return;
    var warm = false;
    var preconnect = function () {
      if (warm) return;
      warm = true;
      ['https://sketchfab.com', 'https://static.sketchfab.com', 'https://media.sketchfab.com'].forEach(function (h) {
        var l = document.createElement('link');
        l.rel = 'preconnect';
        l.href = h;
        l.crossOrigin = '';
        document.head.appendChild(l);
      });
    };
    var small = window.matchMedia('(max-width: 760px)').matches;
    cards.forEach(function (card) {
      card.addEventListener('pointerenter', preconnect);
      card.addEventListener('focus', preconnect);
      card.addEventListener('click', function () {
        preconnect();
        var url = 'https://sketchfab.com/models/' + card.getAttribute('data-model') + '/embed'
          + '?autostart=1&dnt=1&ui_theme=dark&ui_infos=0&ui_watermark_link=0&ui_hint=2&ui_help=0&ui_settings=0&ui_vr=0'
          + '&max_texture_size=' + (small ? 1024 : 2048);
        openViewer(url, card.getAttribute('data-model-title') + ' · scanare 3D', card.querySelector('.gal-3d__img') || card, card);
      });
    });
  }

  // dreptunghiul unui element, ca decupaj pentru fereastra (de aici creste)
  function clipOf(el) {
    var r = el.getBoundingClientRect();
    var w = document.documentElement.clientWidth, h = window.innerHeight;
    return 'inset(' + Math.max(0, r.top) + 'px ' + Math.max(0, w - r.right) + 'px ' + Math.max(0, h - r.bottom) + 'px ' + Math.max(0, r.left) + 'px round 16px)';
  }
  function closeViewer() {
    if (!viewer || !viewer.open) return;
    if (viewerBack) {
      var back = viewerBack;
      viewerBack = null;
      var fr = viewer.querySelector('iframe');
      if (fr) fr.remove();
      back();
      return;
    }
    var gsap = window.gsap;
    var done = function () {
      var f = viewer.querySelector('iframe');
      if (f) f.remove();
      viewer.close();
      viewer.style.clipPath = '';
      document.documentElement.classList.remove('has-viewer');
      if (viewer._opener) viewer._opener.focus({ preventScroll: true });
    };
    var from = viewer._from;
    if (gsap && from && !reduceMotion() && from.getClientRects().length) {
      gsap.to(viewer, { clipPath: clipOf(from), duration: 0.6, ease: 'power3.inOut', onComplete: done });
    } else {
      done();
    }
  }

  /*
     Galeriile foto
     filtrele pe corpuri (sectiunile ramase se rearanjeaza animat, cu GSAP Flip),
     "Arata toate" la albumele mari si colectia 3D care se incarca la clic
  */
  function initGallery() {
    var page = document.querySelector('[data-gallery]');
    if (!page) return;
    var gsap = window.gsap, Flip = window.Flip;
    if (gsap && Flip) gsap.registerPlugin(Flip);

    page.querySelectorAll('[data-album-more]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var album = btn.closest('[data-album]');
        var more = [].slice.call(album.querySelectorAll('[data-more]'));
        more.forEach(function (li, k) { li.hidden = false; li.style.setProperty('--d', k % 6); });
        initReveal();
        if (gsap && !reduceMotion()) gsap.from(more, { autoAlpha: 0, y: 24, duration: 0.6, stagger: 0.04, ease: 'power3.out', clearProps: 'all' });
        btn.remove();
        if (more[0]) more[0].querySelector('a').focus({ preventScroll: true });
      });
    });

    var btns = [].slice.call(page.querySelectorAll('[data-gal-filter]'));
    var secs = [].slice.call(page.querySelectorAll('.gal-corp'));
    btns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var f = btn.getAttribute('data-gal-filter');
        btns.forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
        var state = gsap && Flip && !reduceMotion() ? Flip.getState(secs) : null;
        secs.forEach(function (s) { s.hidden = !(f === 'toate' || s.getAttribute('data-corp') === f); });
        initReveal();
        if (window.ScrollTrigger) window.ScrollTrigger.refresh();
        if (state) {
          Flip.from(state, {
            duration: 0.55, ease: 'power3.inOut',
            onEnter: function (els) { return gsap.fromTo(els, { autoAlpha: 0, y: 40 }, { autoAlpha: 1, y: 0, duration: 0.6, ease: 'power3.out' }); }
          });
        }
      });
    });
  }

  /*
     Continut de pe alte site-uri (colectia 3D, de exemplu) care se incarca doar la clic,
     la fel ca harta: pana atunci nu pleaca nicio cerere
  */
  function initEmbeds() {
    document.querySelectorAll('[data-embed]').forEach(function (box) {
      var btn = box.querySelector('[data-embed-load]');
      if (!btn) return;
      btn.addEventListener('click', function () {
        var label = btn.querySelector('[data-embed-label]');
        var frame = document.createElement('iframe');
        if (label) label.textContent = 'Se încarcă…';
        btn.disabled = true;
        frame.title = 'Colecția de scanări 3D a muzeului';
        frame.setAttribute('allowfullscreen', '');
        frame.setAttribute('allow', 'fullscreen; xr-spatial-tracking; gyroscope; accelerometer');
        frame.addEventListener('load', function () { box.classList.add('is-loaded'); });
        setTimeout(function () { box.classList.add('is-loaded'); }, 7000);
        frame.src = box.getAttribute('data-embed');
        box.appendChild(frame);
      });
    });
  }

  /*
     Titlurile cu clasa split urca pe cuvinte cand apar pe ecran,
     iar cardurile cu data-tilt se inclina putin dupa mouse
  */
  function initSplit() {
    document.querySelectorAll('.split').forEach(function (h) {
      if (h.querySelector('.w')) return;
      var words = h.textContent.trim().split(/\s+/);
      h.setAttribute('aria-label', h.textContent.trim());
      h.innerHTML = words.map(function (w, i) {
        return '<span class="w" aria-hidden="true"><span style="--i:' + i + '">' + w.replace(/[&<>]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]; }) + '</span></span>';
      }).join(' ');
    });
  }

  function initTilt() {
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    document.querySelectorAll('[data-tilt]').forEach(function (card) {
      card.addEventListener('pointermove', function (e) {
        if (reduceMotion()) return;
        var r = card.getBoundingClientRect();
        var x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
        card.style.setProperty('--rx', (-y * 7).toFixed(2) + 'deg');
        card.style.setProperty('--ry', (x * 9).toFixed(2) + 'deg');
        card.style.setProperty('--gx', ((x + 0.5) * 100).toFixed(1) + '%');
        card.style.setProperty('--gy', ((y + 0.5) * 100).toFixed(1) + '%');
      });
      card.addEventListener('pointerleave', function () {
        card.style.setProperty('--rx', '0deg');
        card.style.setProperty('--ry', '0deg');
      });
    });
  }

  /*
     Pagina Istoric: firul cusut al liniei timpului se umple pe masura ce
     derulezi, iar fiecare an se aprinde cand ajunge la mijlocul ecranului
  */
  function initChrono() {
    var box = document.querySelector('[data-chrono]');
    if (!box) return;
    var items = [].slice.call(box.querySelectorAll('.chrono__item'));
    var ticking = false;
    var update = function () {
      var r = box.getBoundingClientRect();
      var mid = window.innerHeight * 0.55;
      var p = Math.max(0, Math.min(1, (mid - r.top) / r.height));
      box.style.setProperty('--p', reduceMotion() ? 1 : p.toFixed(4));
      items.forEach(function (it) { it.classList.toggle('is-on', reduceMotion() || it.getBoundingClientRect().top < mid); });
      ticking = false;
    };
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    window.addEventListener('resize', update);
    update();
  }

  /*
     Pagina Evenimente, pe telefon si tableta: "Sari la an si luna".
     Alegi anul, apoi luna (cele fara evenimente sunt stinse), iar pagina
     coboara direct la primul eveniment din luna aleasa, care clipeste scurt.
  */
  function initJump() {
    var box = document.querySelector('[data-evp-jump]');
    if (!box) return;
    var toggle = box.querySelector('[data-jump-toggle]');
    var panel = box.querySelector('.evp-jump__panel');
    var open = function (on) {
      panel.hidden = !on;
      toggle.setAttribute('aria-expanded', on ? 'true' : 'false');
      if (window.ScrollTrigger) window.ScrollTrigger.refresh();
    };
    toggle.addEventListener('click', function () { open(panel.hidden); });
    box.querySelectorAll('[data-jump-year]').forEach(function (y) {
      y.addEventListener('click', function () {
        var an = y.getAttribute('data-jump-year');
        box.querySelectorAll('[data-jump-year]').forEach(function (b) { b.setAttribute('aria-pressed', b === y ? 'true' : 'false'); });
        box.querySelectorAll('[data-jump-months]').forEach(function (m) { m.hidden = m.getAttribute('data-jump-months') !== an; });
      });
    });
    box.querySelectorAll('[data-jump-to]').forEach(function (m) {
      m.addEventListener('click', function () {
        var ym = m.getAttribute('data-jump-to');
        // daca filtrul ascunde grupa evenimentului, se trece pe "Toate"
        var card = document.querySelector('.evc[data-ym="' + ym + '"]');
        if (card && card.closest('[hidden]')) {
          var all = document.querySelector('[data-filter="toate"]');
          if (all) all.click();
        }
        open(false);
        setTimeout(function () {
          card = [].slice.call(document.querySelectorAll('.evc[data-ym="' + ym + '"]')).filter(function (c) { return !c.closest('[hidden]'); })[0];
          if (!card) return;
          var bar = document.querySelector('[data-evp-bar]');
          var top = card.getBoundingClientRect().top + window.scrollY - 67 - (bar ? bar.offsetHeight : 0) - (parseFloat(getComputedStyle(root).getPropertyValue('--topbar-h')) || 0) - 16;
          window.scrollTo({ top: top, behavior: reduceMotion() ? 'auto' : 'smooth' });
          card.classList.add('is-in', 'is-flash');
          setTimeout(function () { card.classList.remove('is-flash'); }, 1800);
          var link = card.querySelector('a');
          if (link) link.focus({ preventScroll: true });
        }, 450);
      });
    });
  }

  /*
     Ghidul audio, ca un radio de epoca
     citeste in romana textul din continutul paginii (titluri, paragrafe, liste),
     cu vocea romaneasca a browserului, fara servicii externe. Fragmentul citit
     se evidentiaza, iar acul radioului arata cat s-a citit.
     Porneste singur dupa 30 de secunde fara activitate (se poate opri din
     panoul de accesibilitate), dar doar dupa ce vizitatorul a apasat macar
     o data ceva pe pagina, pentru ca browserele nu lasa sunetul sa porneasca altfel.
  */
  function initRadio() {
    var radio = document.querySelector('[data-radio]');
    var synth = window.speechSynthesis;
    var starts = document.querySelectorAll('[data-radio-start]');
    if (!radio || !synth || !window.SpeechSynthesisUtterance) {
      starts.forEach(function (b) { b.hidden = true; });
      return;
    }
    var now = radio.querySelector('[data-radio-now]');
    var toggle = radio.querySelector('[data-radio-toggle]');
    var IDLE = 30000;
    var voice = null, queue = [], pos = 0, said = 0, playing = false, paused = false, current = null, autoDone = false, activated = false, last = Date.now();

    var pickVoice = function () {
      var vs = synth.getVoices();
      voice = vs.filter(function (v) { return /^ro/i.test(v.lang); }).sort(function (a, b) { return /natural|online/i.test(b.name) - /natural|online/i.test(a.name); })[0] || null;
    };
    pickVoice();
    if (synth.addEventListener) synth.addEventListener('voiceschanged', pickVoice);

    /*
       Bucatile de citit: tot textul vizibil din continut, in ordine.
       Se iau toate bucatile de text (si cele din carduri, butoane-link, div-uri),
       se strang pe blocul in care stau (paragraf, titlu, card...) si se impart pe propozitii.
       Blocul e cel care se evidentiaza cand e citit.
    */
    var SKIP = '[aria-hidden="true"], [hidden], nav, .sr-only, dialog, .radio, .map-gate, .evp-jump, .gal-filter, script, style, noscript, svg, button, select, .skip-link';
    var blockOf = function (node) {
      var el = node.parentElement;
      while (el && el.id !== 'main') {
        var d = getComputedStyle(el).display;
        if (d !== 'inline' && d !== 'contents') return el;
        el = el.parentElement;
      }
      return el;
    };
    var collect = function () {
      var main = document.getElementById('main');
      var walker = document.createTreeWalker(main, NodeFilter.SHOW_TEXT, {
        acceptNode: function (n) {
          if (!/\S/.test(n.nodeValue)) return NodeFilter.FILTER_REJECT;
          var p = n.parentElement;
          if (!p || p.closest(SKIP) || !p.getClientRects().length) return NodeFilter.FILTER_REJECT;
          return NodeFilter.FILTER_ACCEPT;
        }
      });
      var groups = [], n, last = null;
      while ((n = walker.nextNode())) {
        var b = blockOf(n);
        if (last && last.el === b) last.text += n.nodeValue;
        else { last = { el: b, text: n.nodeValue }; groups.push(last); }
      }
      var out = [];
      groups.forEach(function (g) {
        var el = g.el;
        var t = g.text.replace(/\s+/g, ' ').trim();
        if (t.length < 2) return;
        (t.match(/[^.!?…]+[.!?…]*\s*/g) || [t]).reduce(function (acc, s) {
          if (acc.length && (acc[acc.length - 1] + s).length < 220) acc[acc.length - 1] += s; else acc.push(s);
          return acc;
        }, []).forEach(function (s) { out.push({ el: el, text: s.trim() }); });
      });
      return out;
    };

    var mark = function (el) {
      if (current && current !== el) current.classList.remove('is-reading');
      current = el;
      if (!el) return;
      el.classList.add('is-reading');
      // daca ce se citeste a iesit din ecran, pagina il aduce la mijloc
      var r = el.getBoundingClientRect();
      if (r.bottom < 90 || r.top > window.innerHeight - 40) el.scrollIntoView({ block: 'center', behavior: reduceMotion() ? 'auto' : 'smooth' });
    };
    var setNeedle = function () { radio.style.setProperty('--p', queue.length ? (pos / queue.length).toFixed(3) : 0); };
    var setPlaying = function (on) {
      playing = on;
      radio.classList.toggle('is-playing', on && !paused);
      toggle.setAttribute('aria-label', on && !paused ? 'Pauză' : 'Continuă');
      toggle.querySelector('use').setAttribute('href', on && !paused ? '#i-pause2' : '#i-play');
    };

    var speakNext = function () {
      if (!playing || paused) return;
      if (pos >= queue.length) { stop(false); now.textContent = 'Am terminat de citit pagina.'; return; }
      var bit = queue[pos];
      var u = new SpeechSynthesisUtterance(bit.text);
      // fiecare rostire are numarul ei, ca una oprita de sageti sa nu mute locul
      var id = ++said;
      u.lang = 'ro-RO';
      if (voice) u.voice = voice;
      u.rate = 0.95;
      u.onend = function () { if (id === said && playing && !paused) { pos++; setNeedle(); speakNext(); } };
      u.onerror = function (ev) { if (id === said && ev.error !== 'interrupted' && ev.error !== 'canceled') { pos++; speakNext(); } };
      mark(bit.el);
      now.textContent = bit.text.length > 90 ? bit.text.slice(0, 88) + '…' : bit.text;
      synth.speak(u);
    };

    // porneste de la primul fragment vizibil pe ecran, ca sa citeasca ce vezi
    var start = function (fromView) {
      synth.cancel();
      queue = collect();
      pos = 0;
      if (fromView) {
        for (var i = 0; i < queue.length; i++) {
          if (queue[i].el.getBoundingClientRect().bottom > 80) { pos = i; break; }
        }
      }
      radio.hidden = false;
      paused = false;
      setPlaying(true);
      setNeedle();
      if (!voice) now.textContent = 'Browserul nu are o voce românească instalată; citesc cu vocea lui implicită.';
      speakNext();
    };
    var stop = function (hide) {
      setPlaying(false);
      paused = false;
      synth.cancel();
      mark(null);
      if (hide) radio.hidden = true;
    };

    starts.forEach(function (b) {
      b.addEventListener('click', function () {
        var p = document.getElementById('a11y-panel');
        if (p && !p.hidden && p.contains(b)) document.querySelector('[data-a11y-close]').click();
        start(true);
      });
    });
    toggle.addEventListener('click', function () {
      if (!playing) { start(true); return; }
      paused = !paused;
      if (paused) { synth.cancel(); setPlaying(true); }
      else { setPlaying(true); speakNext(); }
    });
    radio.querySelector('[data-radio-stop]').addEventListener('click', function () { stop(true); });

    /*
       Sagetile: sar la blocul de dinainte (paragraf, titlu, card) sau la cel care
       urmeaza, si il citesc de la inceput.
    */
    var firstOf = function (i) {
      while (i > 0 && queue[i - 1].el === queue[i].el) i--;
      return i;
    };
    var jump = function (dir) {
      if (!queue.length) { start(true); return; }
      var i = Math.min(pos, queue.length - 1);
      var el = queue[i].el;
      if (dir > 0) {
        while (i < queue.length && queue[i].el === el) i++;
      } else {
        i = firstOf(i);
        if (i > 0) i = firstOf(i - 1);
      }
      pos = Math.max(0, Math.min(queue.length, i));
      synth.cancel();
      paused = false;
      setPlaying(true);
      setNeedle();
      speakNext();
    };
    radio.querySelector('[data-radio-prev]').addEventListener('click', function () { jump(-1); });
    radio.querySelector('[data-radio-next]').addEventListener('click', function () { jump(1); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && playing) stop(false); });
    window.addEventListener('pagehide', function () { synth.cancel(); });

    // pornirea singura dupa 30 de secunde fara activitate
    var activity = function (e) {
      last = Date.now();
      if (e && (e.type === 'pointerdown' || e.type === 'keydown')) activated = true;
    };
    ['pointerdown', 'keydown', 'pointermove', 'wheel', 'scroll', 'touchstart'].forEach(function (t) {
      window.addEventListener(t, activity, { passive: true });
    });
    setInterval(function () {
      if (autoDone || playing || !activated || document.hidden || store.get('autoread') === 'off') return;
      if (document.querySelector('dialog[open]') || !document.getElementById('a11y-panel').hidden) return;
      if (Date.now() - last >= IDLE) { autoDone = true; start(true); }
    }, 1000);
  }

  /*
     Videoclipurile de la Povesti ale locului. Pe pagina povestilor, clic pe poza
     deschide videoclipul intr-o fereastra; la inchidere se opreste.
     Pe pagina unei povesti, linkul "Vezi interviul" devine player chiar acolo,
     cu poza articolului drept coperta. Videoclipul se incarca abia la clic.
  */
  function initVideo() {
    var player = document.querySelector('[data-video-player]');
    if (player && player.showModal) {
      var video = player.querySelector('video');
      var title = player.querySelector('.pv-player__title');
      var opener = null;
      var close = function () { video.pause(); if (player.open) player.close(); };
      document.querySelectorAll('[data-video]').forEach(function (b) {
        b.addEventListener('click', function () {
          opener = b;
          title.textContent = b.getAttribute('data-video-title') || '';
          var img = b.querySelector('img');
          video.poster = img ? img.currentSrc || img.src : '';
          video.src = b.getAttribute('data-video');
          player.showModal();
          var p = video.play();
          if (p && p.catch) p.catch(function () {});
        });
      });
      player.querySelector('[data-video-close]').addEventListener('click', close);
      player.addEventListener('click', function (e) { if (e.target === player) close(); });
      player.addEventListener('close', function () {
        video.pause();
        video.removeAttribute('src');
        video.load();
        if (opener) opener.focus();
      });
    }

    document.querySelectorAll('.item-page a.video-link').forEach(function (a) {
      var page = a.closest('.item-page');
      var fig = page.querySelector('.item-image');
      var img = fig ? fig.querySelector('img') : null;
      var box = document.createElement('div');
      box.className = 'video-box';
      box.innerHTML = '<button type="button" class="video-box__play"><span class="pv-card__btn" aria-hidden="true"><svg width="26" height="26"><use href="#i-play"/></svg></span><span class="video-box__label"></span></button>';
      var btn = box.firstChild;
      btn.querySelector('.video-box__label').textContent = a.textContent;
      if (img) btn.insertBefore(img.cloneNode(), btn.firstChild);
      if (fig) fig.hidden = true;
      a.parentNode.replaceChild(box, a);
      btn.addEventListener('click', function () {
        var v = document.createElement('video');
        v.controls = true;
        v.playsInline = true;
        v.src = a.href;
        if (img) v.poster = img.src;
        box.replaceChild(v, btn);
        var p = v.play();
        if (p && p.catch) p.catch(function () {});
        v.focus();
      });
    });
  }

  /*
     Harta de pe Acasa se incarca doar cand vizitatorul apasa "Vreau sa vad locatia",
     ca pana atunci sa nu plece nicio cerere spre Google.
     Panoul ramane pana se incarca harta, apoi harta apare incet peste el.
  */
  function initMapGate() {
    document.querySelectorAll('[data-map]').forEach(function (box) {
      var btn = box.querySelector('[data-map-load]');
      if (!btn) return;
      btn.addEventListener('click', function () {
        var gate = box.querySelector('.map-gate');
        var label = btn.querySelector('[data-map-label]');
        var frame = document.createElement('iframe');
        var done = false;
        var reveal = function () {
          if (done) return;
          done = true;
          box.classList.add('is-loaded');
          frame.focus({ preventScroll: true });
          setTimeout(function () { if (gate) gate.remove(); }, 600);
        };
        btn.disabled = true;
        if (label) label.textContent = 'Se încarcă harta…';
        frame.title = 'Harta: Muzeul Ianca';
        frame.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
        frame.addEventListener('load', reveal);
        setTimeout(reveal, 6000); // daca nu vine raspuns, harta se arata oricum
        frame.src = box.getAttribute('data-map');
        box.insertBefore(frame, gate);
      });
    });
  }

  /*
     Pagina unui eveniment: daca pozele sunt mai inalte decat textul,
     un text scurt ramane langa ele cand derulezi (unul lung curge normal)
  */
  function initEventText() {
    var text = document.querySelector('.evd-body.has-photos .evd-text');
    var photos = document.querySelector('.evd-photos');
    if (!text || !photos) return;
    var check = function () {
      text.classList.toggle('is-sticky', text.offsetHeight < window.innerHeight - 160 && photos.offsetHeight > text.offsetHeight);
    };
    check();
    window.addEventListener('resize', check);
    window.addEventListener('load', check);
  }

  /*
     Pozele unui eveniment se deschid marite, cu sageti, tastatura
     (stanga, dreapta, Esc) si glisare pe telefon
  */
  function initLightbox() {
    var all = [].slice.call(document.querySelectorAll('[data-lightbox], .ev-galerie a'));
    if (!all.length || !window.HTMLDialogElement) return;
    var gsap = window.gsap;
    // la galerii, sagetile trec doar prin pozele lasate de filtru
    var links = all;
    var single = links.length < 2;

    var dlg = document.createElement('dialog');
    dlg.className = 'lightbox';
    dlg.setAttribute('aria-label', 'Fotografii');
    dlg.innerHTML =
      '<figure class="lightbox__fig"><img class="lightbox__img" alt="" draggable="false"><figcaption class="lightbox__count" aria-live="polite"></figcaption></figure>' +
      '<button type="button" class="lightbox__btn lightbox__close" aria-label="Închide"><svg><use href="#i-x"/></svg></button>' +
      '<button type="button" class="lightbox__btn lightbox__prev" aria-label="Fotografia anterioară"><svg><use href="#i-arrow"/></svg></button>' +
      '<button type="button" class="lightbox__btn lightbox__next" aria-label="Fotografia următoare"><svg><use href="#i-arrow"/></svg></button>';
    document.body.appendChild(dlg);
    var img = dlg.querySelector('.lightbox__img');
    var count = dlg.querySelector('.lightbox__count');
    var prev = dlg.querySelector('.lightbox__prev');
    var next = dlg.querySelector('.lightbox__next');
    prev.hidden = next.hidden = single;
    var i = 0;

    var preload = function (n) { new Image().src = links[(n + links.length) % links.length].getAttribute('href'); };
    var put = function () {
      var a = links[i];
      var inner = a.querySelector('img');
      img.src = a.getAttribute('href');
      img.alt = inner ? inner.alt : '';
      var cap = a.getAttribute('data-caption');
      count.textContent = (cap ? cap + (single ? '' : ' · ') : '') + (single ? '' : (i + 1) + ' / ' + links.length);
      return img.decode ? img.decode().catch(function () {}) : Promise.resolve();
    };
    var show = function (n, dir) {
      i = (n + links.length) % links.length;
      if (!single) { preload(i + 1); preload(i - 1); }
      if (!gsap || !dir) { put(); return; }
      gsap.to(img, {
        autoAlpha: 0, x: -28 * dir, duration: 0.16, ease: 'power1.in', overwrite: true,
        onComplete: function () {
          put().then(function () {
            gsap.fromTo(img, { autoAlpha: 0, x: 28 * dir }, { autoAlpha: 1, x: 0, duration: 0.34, ease: 'power3.out' });
          });
        }
      });
    };

    all.forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        links = all.filter(function (x) { return !x.closest('[hidden]'); });
        single = links.length < 2;
        prev.hidden = next.hidden = single;
        if (gsap) gsap.set(img, { clearProps: 'opacity,visibility,transform' });
        show(links.indexOf(a));
        root.classList.add('lb-open');
        dlg.showModal();
      });
    });
    dlg.addEventListener('close', function () {
      root.classList.remove('lb-open');
      links[i].focus({ preventScroll: true });
    });
    dlg.querySelector('.lightbox__close').addEventListener('click', function () { dlg.close(); });
    prev.addEventListener('click', function () { show(i - 1, -1); });
    next.addEventListener('click', function () { show(i + 1, 1); });
    dlg.addEventListener('keydown', function (e) {
      if (single) return;
      if (e.key === 'ArrowLeft') { e.preventDefault(); show(i - 1, -1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); show(i + 1, 1); }
    });
    // click pe fundalul inchis inchide poza
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    // glisare stanga sau dreapta pe telefon
    var sx = null;
    img.addEventListener('pointerdown', function (e) { sx = e.clientX; });
    img.addEventListener('pointerup', function (e) {
      if (sx === null || single) return;
      var dx = e.clientX - sx;
      sx = null;
      if (Math.abs(dx) > 40) show(i + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1);
    });
  }

  /*
     Asistentul muzeului
     deocamdata e doar fereastra, raspunsurile vor veni mai tarziu de la
     modulul AI facut cu informatiile muzeului
  */
  function initAi() {
    var fab = document.querySelector('.ai-fab');
    var panel = document.querySelector('.ai-panel');
    if (!fab || !panel) return;
    var close = panel.querySelector('.ai-panel__close');
    var set = function (open) {
      panel.classList.toggle('is-open', open);
      fab.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) { var inp = panel.querySelector('input'); if (inp) setTimeout(function () { inp.focus(); }, 200); }
    };
    fab.addEventListener('click', function () { set(!panel.classList.contains('is-open')); });
    if (close) close.addEventListener('click', function () { set(false); fab.focus(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('is-open')) { set(false); fab.focus(); } });
    var form = panel.querySelector('form');
    if (form) form.addEventListener('submit', function (e) {
      e.preventDefault();
      var inp = form.querySelector('input');
      var msg = panel.querySelector('.ai-panel__msg');
      if (!inp.value.trim()) { inp.focus(); return; }
      msg.textContent = 'Mulțumim! Asistentul virtual al muzeului va fi disponibil în curând. Până atunci, ne găsiți la 0723 609 033.';
      inp.value = '';
    });
  }

  /*
     Scene la derulare cu GSAP ScrollTrigger
     fara GSAP sau cu miscare redusa pagina ramane completa, doar statica
  */
  function initScrollScenes() {
    var gsap = window.gsap;
    var ST = window.ScrollTrigger;
    if (!gsap || !ST) return;
    gsap.registerPlugin(ST);
    root.classList.add('has-gsap');
    if (reduceMotion()) return;

    // banner: textul urca si se estompeaza cand derulezi mai departe
    if (document.querySelector('.hero')) {
      gsap.to('.hero__content', {
        yPercent: -14, opacity: 0.15, ease: 'none',
        scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true }
      });
    }

    // evenimentul mare de pe Acasa: poza se misca usor la derulare
    var leadImg = document.querySelector('.ev-lead__img img');
    if (leadImg) {
      gsap.fromTo(leadImg, { yPercent: -4 }, {
        yPercent: 4, ease: 'none',
        scrollTrigger: { trigger: '.ev-lead', start: 'top bottom', end: 'bottom top', scrub: true }
      });
    }

    // subsol: numele muzeului urca din margine la finalul paginii
    var mark = document.querySelector('.footer__wordmark');
    if (mark) {
      gsap.fromTo(mark, { yPercent: 70 }, {
        yPercent: 14, ease: 'none',
        scrollTrigger: { trigger: '.footer', start: 'top bottom', end: 'bottom bottom', scrub: true }
      });
    }

    // pozitiile se recalculeaza dupa ce se incarca fonturile si pozele
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { ST.refresh(); });
    window.addEventListener('load', function () { ST.refresh(); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initA11y();
    initSplit();
    introHero();
    initHeader();
    initTopbar();
    initNav();
    initHero();
    initScrollScenes();
    initReveal();
    initTimeline();
    initTabs();
    initCountdown();
    initEvents();
    initEventText();
    initVisit();
    initTour();
    initGallery();
    initModels();
    initEmbeds();
    initTilt();
    initJump();
    initChrono();
    initRadio();
    initVideo();
    initMapGate();
    initLightbox();
    initAi();
  });
})();
