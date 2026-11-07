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
  var reduceMotion = function () {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  };

  /*
     Bara de accesibilitate
     marimea textului (3 trepte in jos si 3 in sus), contrast ridicat si linkuri
     subliniate. Alegerile raman salvate in browser. Se mareste doar textul din
     continut, nu si meniul sau titlul mare.
  */
  var SIZES = [0.8, 0.87, 0.93, 1, 1.1, 1.2, 1.3];

  function initA11y() {
    var step = SIZES.indexOf(parseFloat(store.get('fs')));
    if (step < 0) step = SIZES.indexOf(1);
    var label = document.querySelector('[data-a11y-size]');
    var dec = document.querySelector('[data-a11y="dec"]');
    var inc = document.querySelector('[data-a11y="inc"]');

    var apply = function () {
      root.style.setProperty('--fs-scale', SIZES[step]);
      if (label) label.textContent = Math.round(SIZES[step] * 100) + '%';
      if (dec) dec.setAttribute('aria-disabled', step === 0 ? 'true' : 'false');
      if (inc) inc.setAttribute('aria-disabled', step === SIZES.length - 1 ? 'true' : 'false');
      ['contrast', 'links'].forEach(function (k) {
        var v = store.get(k);
        if (v) root.setAttribute('data-' + k, v); else root.removeAttribute('data-' + k);
        var btn = document.querySelector('[data-a11y="' + k + '"]');
        if (btn) btn.setAttribute('aria-pressed', v ? 'true' : 'false');
      });
    };
    apply();

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-a11y]');
      if (!btn) return;
      var act = btn.getAttribute('data-a11y');
      if (act === 'inc') step = Math.min(SIZES.length - 1, step + 1);
      else if (act === 'dec') step = Math.max(0, step - 1);
      else {
        var on = { contrast: 'high', links: 'underline' }[act];
        store.set(act, store.get(act) ? '' : on);
      }
      store.set('fs', SIZES[step]);
      apply();
      // pozitiile animatiilor depind de inaltimea textului, deci se recalculeaza
      if (window.ScrollTrigger) window.ScrollTrigger.refresh();
    });
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
      document.querySelectorAll('.reveal, .reveal-img, .develop, .draw, .press, [data-count]'),
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
    var dur = 1800, t0 = null;
    var fmt = function (n) { return n.toLocaleString('ro-RO'); };
    var step = function (t) {
      if (!t0) t0 = t;
      var p = Math.min(1, (t - t0) / dur);
      var e = 1 - Math.pow(1 - p, 4);
      el.textContent = fmt(Math.round(end * e));
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
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
    var status = document.querySelector('[data-status]');
    var days = document.querySelector('[data-days]');
    var ticket = document.querySelector('[data-ticket]');
    var orar = [];
    try { orar = JSON.parse(status ? status.getAttribute('data-orar') : '[]'); } catch (err) { orar = []; }

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
      go.addEventListener('click', function () {
        var label = go.querySelector('[data-tour-label]');
        var frame = document.createElement('iframe');
        var done = false;
        var reveal = function () { if (!done) { done = true; view.classList.add('is-loaded'); } };
        if (label) label.textContent = 'Se încarcă sala…';
        go.disabled = true;
        frame.title = 'Tur virtual: ' + (view.parentNode.querySelector('.tour__title') || {}).textContent;
        frame.setAttribute('allowfullscreen', '');
        frame.setAttribute('allow', 'fullscreen; xr-spatial-tracking; gyroscope; accelerometer');
        frame.addEventListener('load', reveal);
        setTimeout(reveal, 8000);
        frame.src = view.getAttribute('data-tour');
        view.appendChild(frame);
      });
    });
  }

  /*
     Galeriile foto: filtrele de pe pagina cu toate pozele.
     Pozele ramase se rearanjeaza animat (GSAP Flip), iar numarul se schimba.
  */
  function initGallery() {
    var page = document.querySelector('[data-gallery]');
    if (!page) return;
    var btns = [].slice.call(page.querySelectorAll('[data-gal-filter]'));
    var items = [].slice.call(page.querySelectorAll('.gal-item'));
    var count = page.querySelector('[data-gal-count]');
    var empty = page.querySelector('[data-gal-empty]');
    if (!btns.length) return;
    var gsap = window.gsap, Flip = window.Flip;
    if (gsap && Flip) gsap.registerPlugin(Flip);

    btns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var f = btn.getAttribute('data-gal-filter');
        btns.forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
        var state = gsap && Flip && !reduceMotion() ? Flip.getState(items) : null;
        var n = 0;
        items.forEach(function (it) {
          var on = f === 'toate' || it.getAttribute('data-galerie') === f;
          it.hidden = !on;
          if (on) n++;
        });
        if (count) count.textContent = n === 1 ? '1 fotografie' : n + (n % 100 >= 20 || (n % 100 === 0 && n) ? ' de' : '') + ' fotografii';
        if (empty) empty.hidden = n > 0;
        initReveal();
        if (state) {
          Flip.from(state, {
            duration: 0.6, ease: 'power3.inOut', absolute: true,
            onEnter: function (els) { return gsap.fromTo(els, { autoAlpha: 0, scale: 0.92 }, { autoAlpha: 1, scale: 1, duration: 0.5, ease: 'power2.out' }); },
            onLeave: function (els) { return gsap.to(els, { autoAlpha: 0, scale: 0.92, duration: 0.3 }); }
          });
        }
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
    initMapGate();
    initLightbox();
    initAi();
  });
})();
