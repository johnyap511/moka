/**
 * MOKA v2 — Core JavaScript
 * Apple-grade interactions: header, drawer, reveal, parallax,
 * counter animation, modal, gallery, FAQ, forms, booking widget
 */

(function () {
  'use strict';

  /* ── Utilities ─────────────────────────────────────────── */
  const qs  = (sel, ctx = document) => ctx.querySelector(sel);
  const qsa = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ── 1. Header Scroll ──────────────────────────────────── */
  const header = qs('.moka-header');
  if (header) {
    const isTransparent = header.classList.contains('is-transparent');
    let ticking = false;

    const onScroll = () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          const scrolled = window.scrollY > 60;
          header.classList.toggle('scrolled', scrolled);
          if (isTransparent && !scrolled) header.classList.remove('scrolled');
          ticking = false;
        });
        ticking = true;
      }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── 2. Mobile Drawer ──────────────────────────────────── */
  const hamburger    = qs('#hamburger');
  const drawer       = qs('#mobileDrawer');
  const drawerClose  = qs('#drawerClose');
  const drawerOverlay = qs('#drawerOverlay');

  if (hamburger && drawer) {
    const openDrawer = () => {
      drawer.classList.add('open');
      drawerOverlay?.classList.add('open');
      hamburger.classList.add('open');
      hamburger.setAttribute('aria-expanded', 'true');
      drawer.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    };

    const closeDrawer = () => {
      drawer.classList.remove('open');
      drawerOverlay?.classList.remove('open');
      hamburger.classList.remove('open');
      hamburger.setAttribute('aria-expanded', 'false');
      drawer.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    };

    hamburger.addEventListener('click', () => {
      drawer.classList.contains('open') ? closeDrawer() : openDrawer();
    });

    drawerClose?.addEventListener('click', closeDrawer);
    drawerOverlay?.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && drawer.classList.contains('open')) closeDrawer();
    });
  }

  /* ── 3. Scroll Reveal ──────────────────────────────────── */
  const revealEls = qsa('[data-reveal], .reveal');

  if (revealEls.length && 'IntersectionObserver' in window && !prefersReducedMotion) {
    revealEls.forEach(el => {
      const dir = el.dataset.reveal;
      if (dir === 'left')  { el.style.opacity = '0'; el.style.transform = 'translateX(-32px)'; }
      else if (dir === 'right') { el.style.opacity = '0'; el.style.transform = 'translateX(32px)'; }
      else if (dir === 'scale') { el.style.opacity = '0'; el.style.transform = 'scale(0.94)'; }
      else { el.style.opacity = '0'; el.style.transform = 'translateY(24px)'; }
      el.style.transition = 'opacity 0.7s cubic-bezier(0.4,0,0.2,1), transform 0.7s cubic-bezier(0.4,0,0.2,1)';
    });

    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const delay = entry.target.dataset.delay || 0;
          setTimeout(() => {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'none';
            entry.target.classList.add('revealed');
          }, Number(delay));
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(el => revealObserver.observe(el));

    // Stagger children
    qsa('[data-reveal-stagger]').forEach(parent => {
      qsa('[data-reveal]', parent).forEach((child, i) => {
        child.dataset.delay = String(i * 100);
      });
    });
  } else {
    revealEls.forEach(el => { el.style.opacity = '1'; el.classList.add('revealed'); });
  }

  /* ── 4. Counter Animation ──────────────────────────────── */
  if (!prefersReducedMotion) {
    const counters = qsa('.stat-number[data-target]');

    const animateCounter = (el) => {
      const target = parseFloat(el.dataset.target);
      const suffix = el.dataset.suffix || '';
      const prefix = el.dataset.prefix || '';
      const duration = 1800;
      const start = performance.now();

      const tick = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = target * eased;
        el.textContent = prefix + (Number.isInteger(target) ? Math.round(current) : current.toFixed(1)) + suffix;
        if (progress < 1) requestAnimationFrame(tick);
      };

      requestAnimationFrame(tick);
    };

    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(el => counterObserver.observe(el));
  }

  /* ── 5. Parallax Hero ──────────────────────────────────── */
  if (!prefersReducedMotion) {
    const hero = qs('.hero');
    if (hero) {
      window.addEventListener('scroll', () => {
        requestAnimationFrame(() => {
          const y = window.scrollY;
          hero.style.setProperty('--parallax-y', `${y * 0.25}px`);
        });
      }, { passive: true });
    }
  }

  /* ── 6. Auth Modal ─────────────────────────────────────── */
  const authOverlay = qs('.modal-overlay[data-modal="auth"]');

  const openModal  = () => { if (!authOverlay) return; authOverlay.classList.add('open'); document.body.style.overflow = 'hidden'; };
  const closeModal = () => { if (!authOverlay) return; authOverlay.classList.remove('open'); document.body.style.overflow = ''; };

  qsa('[data-open-modal="auth"]').forEach(el => el.addEventListener('click', openModal));

  if (authOverlay) {
    authOverlay.addEventListener('click', e => { if (e.target === authOverlay) closeModal(); });
    qs('.modal-close', authOverlay)?.addEventListener('click', closeModal);

    // Tab switching
    const tabs   = qsa('.auth-tab', authOverlay);
    const panels = qsa('.auth-panel', authOverlay);

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t   => t.classList.remove('active'));
        panels.forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        const panel = qs(`.auth-panel[data-panel="${tab.dataset.tab}"]`, authOverlay);
        panel?.classList.add('active');
      });
    });

    // Switch tab links
    qsa('[data-switch-tab]', authOverlay).forEach(el => {
      el.addEventListener('click', () => {
        qs(`.auth-tab[data-tab="${el.dataset.switchTab}"]`, authOverlay)?.click();
      });
    });
  }

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  /* ── 7. Bedroom Counter ────────────────────────────────── */
  qsa('.bedroom-counter').forEach(counter => {
    const minus  = qs('[data-action="minus"]', counter);
    const plus   = qs('[data-action="plus"]', counter);
    const display = qs('.bedroom-counter-value', counter);
    const hidden  = counter.closest('form')?.querySelector('input[name="bedroom"]');
    let count = parseInt(hidden?.value || 1);

    const update = () => {
      if (display) display.textContent = `${count} Bedroom${count !== 1 ? 's' : ''}`;
      if (hidden)  hidden.value = count;
      if (minus)   minus.disabled = count <= 1;
      if (plus)    plus.disabled  = count >= 10;
    };

    update();
    minus?.addEventListener('click', () => { if (count > 1)  { count--; update(); } });
    plus?.addEventListener('click',  () => { if (count < 10) { count++; update(); } });
  });

  /* ── 8. Date Inputs ────────────────────────────────────── */
  const today = new Date().toISOString().split('T')[0];
  qsa('.date-field-input').forEach(input => {
    input.setAttribute('min', today);
    if (input.name === 'check_in') {
      input.addEventListener('change', () => {
        const out = document.querySelector('input[name="check_out"]');
        if (out) out.setAttribute('min', input.value);
      });
    }
  });

  /* ── 9. Booking Widget ─────────────────────────────────── */
  const widget = qs('.booking-widget');
  if (widget) {
    const checkIn  = qs('input[name="check_in"]', widget);
    const checkOut = qs('input[name="check_out"]', widget);
    const nightsRow = qs('.nights-row', widget);
    const totalEl   = qs('.price-total-value', widget);
    const pricePerNight = parseFloat(widget.dataset.pricePerNight || 0);

    const fmt = n => 'RM ' + n.toLocaleString('en-MY');

    const updateTotal = () => {
      if (!checkIn?.value || !checkOut?.value) return;
      const nights = Math.round((new Date(checkOut.value) - new Date(checkIn.value)) / 86400000);
      if (nights <= 0) return;
      const subtotal = nights * pricePerNight;
      if (nightsRow) nightsRow.textContent = fmt(subtotal);
      if (totalEl)   totalEl.textContent   = fmt(subtotal);
    };

    checkIn?.addEventListener('change', updateTotal);
    checkOut?.addEventListener('change', updateTotal);
  }

  /* ── 10. Gallery Overlay ───────────────────────────────── */
  const galleryOverlay = qs('#galleryOverlay');
  qs('.show-all-photos')?.addEventListener('click', () => galleryOverlay?.classList.add('open'));

  if (galleryOverlay) {
    galleryOverlay.addEventListener('click', e => {
      if (e.target === galleryOverlay || e.target.classList.contains('gallery-close')) {
        galleryOverlay.classList.remove('open');
      }
    });

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') galleryOverlay.classList.remove('open');
    });
  }

  /* ── 11. FAQ Accordion ─────────────────────────────────── */
  qsa('.faq-item').forEach(item => {
    const btn    = qs('.faq-question', item);
    const answer = qs('.faq-answer', item);
    const inner  = qs('.faq-answer-inner', item);

    btn?.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');

      // Close all others
      qsa('.faq-item.open').forEach(other => {
        if (other !== item) {
          other.classList.remove('open');
          qs('.faq-answer', other).style.maxHeight = '0';
        }
      });

      item.classList.toggle('open', !isOpen);
      answer.style.maxHeight = isOpen ? '0' : inner.scrollHeight + 'px';
    });
  });

  /* ── 12. Multi-step Estimate Form ──────────────────────── */
  const estimateForm = qs('.estimate-form-steps');
  if (estimateForm) {
    const steps = qsa('.estimate-step', estimateForm);
    const dots  = qsa('.progress-step-dot');
    const fills = qsa('.progress-line-fill');
    let current = 0;

    const showStep = (n) => {
      steps.forEach((s, i) => s.classList.toggle('active', i === n));
      dots.forEach((d, i) => {
        d.closest('.progress-step')?.classList.toggle('active',   i === n);
        d.closest('.progress-step')?.classList.toggle('complete', i < n);
      });
      fills.forEach((f, i) => {
        f.style.width = i < n ? '100%' : '0%';
      });
      current = n;
    };

    qsa('[data-next-step]', estimateForm).forEach(btn => {
      btn.addEventListener('click', () => {
        if (current < steps.length - 1) showStep(current + 1);
      });
    });

    qsa('[data-prev-step]', estimateForm).forEach(btn => {
      btn.addEventListener('click', () => {
        if (current > 0) showStep(current - 1);
      });
    });
  }

  /* ── 13. Read More Toggle ──────────────────────────────── */
  qsa('[data-read-more]').forEach(container => {
    const full  = qs('[data-read-more-full]', container);
    const btn   = qs('.read-more-btn', container);
    if (!full || !btn) return;

    full.style.display = 'none';
    btn.addEventListener('click', () => {
      const isVisible = full.style.display !== 'none';
      full.style.display = isVisible ? 'none' : 'block';
      btn.textContent = isVisible ? 'Read more ↓' : 'Show less ↑';
    });
  });

  /* ── 14. Active nav link ───────────────────────────────── */
  const currentPath = window.location.pathname;
  qsa('.nav-link, .mobile-nav-link').forEach(link => {
    try {
      if (link.href && new URL(link.href).pathname === currentPath) {
        link.classList.add('active', 'nav-link--active');
      }
    } catch (_) {}
  });

  /* ── 15. Form double-submit prevention ─────────────────── */
  qsa('form').forEach(form => {
    form.addEventListener('submit', e => {
      const btn = form.querySelector('[type="submit"]');
      if (!btn) return;
      if (btn.dataset.submitting) { e.preventDefault(); return; }
      btn.dataset.submitting = 'true';
      const orig = btn.tagName === 'INPUT' ? btn.value : btn.textContent;
      btn.tagName === 'INPUT' ? (btn.value = 'Please wait…') : (btn.textContent = 'Please wait…');
      btn.style.opacity = '0.7';
      btn.style.pointerEvents = 'none';
      setTimeout(() => {
        delete btn.dataset.submitting;
        btn.tagName === 'INPUT' ? (btn.value = orig) : (btn.textContent = orig);
        btn.style.opacity = btn.style.pointerEvents = '';
      }, 10000);
    });
  });

  /* ── 16. Smooth scroll anchors ──────────────────────────── */
  qsa('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.getElementById(a.getAttribute('href').slice(1));
      if (!target) return;
      e.preventDefault();
      const offset = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h')) || 80;
      window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - offset - 16, behavior: 'smooth' });
    });
  });

  /* ── 17. Toastr config ──────────────────────────────────── */
  if (typeof toastr !== 'undefined') {
    toastr.options = { timeOut: 5000, closeButton: true, progressBar: true, positionClass: 'toast-top-right', preventDuplicates: true };
  }

})();
