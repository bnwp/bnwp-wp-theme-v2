/* BNWP WikiConnect v2 — progressive enhancement only.
   Everything here is optional: the page is fully usable with JS disabled. */
(function () {
  'use strict';

  var root = document.documentElement;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var digits = (window.BNWP && window.BNWP.digits) || '0123456789';

  /* --- colour mode ------------------------------------------------------ */

  function setTheme(mode) {
    root.setAttribute('data-theme', mode);
    try { localStorage.setItem('bnwp-theme', mode); } catch (e) {}
    var btn = document.querySelector('[data-theme-toggle]');
    if (btn) {
      btn.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
    }
  }

  document.addEventListener('click', function (e) {
    var el = e.target instanceof Element ? e.target : null;
    var toggle = el && el.closest('[data-theme-toggle]');
    if (!toggle) return;
    setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
  });

  // follow the OS only while the visitor has not chosen for themselves
  var osDark = window.matchMedia('(prefers-color-scheme: dark)');
  if (osDark.addEventListener) {
    osDark.addEventListener('change', function (e) {
      var stored = null;
      try { stored = localStorage.getItem('bnwp-theme'); } catch (err) {}
      if (!stored) root.setAttribute('data-theme', e.matches ? 'dark' : 'light');
    });
  }

  /* --- mobile navigation ------------------------------------------------ */

  var navToggle = document.querySelector('[data-nav-toggle]');
  var nav = document.getElementById('site-nav');
  var desktop = window.matchMedia('(min-width: 900px)');

  function syncNav() {
    if (!nav || !navToggle) return;
    if (desktop.matches) {
      nav.hidden = false;
      navToggle.setAttribute('aria-expanded', 'false');
    } else {
      nav.hidden = navToggle.getAttribute('aria-expanded') !== 'true';
    }
  }

  if (navToggle && nav) {
    navToggle.addEventListener('click', function () {
      var open = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
      syncNav();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !desktop.matches && navToggle.getAttribute('aria-expanded') === 'true') {
        navToggle.setAttribute('aria-expanded', 'false');
        syncNav();
        navToggle.focus();
      }
    });
    if (desktop.addEventListener) desktop.addEventListener('change', syncNav);
    syncNav();
  }

  /* --- sticky header shadow --------------------------------------------- */

  var header = document.querySelector('.site-header');
  if (header) {
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(function () {
        header.classList.toggle('is-stuck', window.scrollY > 8);
        ticking = false;
      });
    }, { passive: true });
  }

  /* --- scroll reveal ---------------------------------------------------- */

  var revealables = document.querySelectorAll('.reveal');

  if (reduced || !('IntersectionObserver' in window)) {
    Array.prototype.forEach.call(revealables, function (el) { el.classList.add('is-in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-in');
        io.unobserve(entry.target);
        if (entry.target.hasAttribute('data-count')) countUp(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });

    Array.prototype.forEach.call(revealables, function (el) { io.observe(el); });

    // stagger children of any [data-stagger] container
    Array.prototype.forEach.call(document.querySelectorAll('[data-stagger]'), function (group) {
      Array.prototype.forEach.call(group.children, function (child, i) {
        var target = child.classList.contains('reveal') ? child : child.querySelector('.reveal');
        if (target) target.style.setProperty('--reveal-delay', Math.min(i * 70, 420) + 'ms');
      });
    });
  }

  /* --- number count-up -------------------------------------------------- */

  function localise(n) {
    return String(n).replace(/[0-9]/g, function (d) { return digits[+d]; });
  }

  function countUp(el) {
    var target = parseInt(el.getAttribute('data-count'), 10);
    if (isNaN(target) || reduced) return;

    var suffix = el.getAttribute('data-suffix') || '';
    var start = performance.now();
    var duration = 1100;

    function frame(now) {
      var p = Math.min((now - start) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = localise(Math.round(target * eased).toLocaleString('en-US')) + suffix;
      if (p < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  // counters that are not .reveal (e.g. above the fold) still run
  Array.prototype.forEach.call(document.querySelectorAll('[data-count]'), function (el) {
    if (!el.classList.contains('reveal')) countUp(el);
  });
})();
