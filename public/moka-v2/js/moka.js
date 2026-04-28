/**
 * MOKA v2 — Core JavaScript
 * Handles: header scroll, mobile drawer, scroll reveal,
 *          auth modal tabs, bedroom counter, form UX
 */

(function () {
  'use strict';

  /* ── Utilities ─────────────────────────────────────────── */
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

  /* ── 1. Header Scroll Behaviour ───────────────────────── */
  const header = $('.moka-header');
  if (header) {
    const isTransparent = header.classList.contains('transparent');

    const onScroll = () => {
      if (window.scrollY > 10) {
        header.classList.add('scrolled');
      } else {
        if (isTransparent) header.classList.remove('scrolled');
      }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // run once on load
  }

  /* ── 2. Mobile Drawer ──────────────────────────────────── */
  const hamburger = $('.hamburger');
  const drawer    = $('.mobile-drawer');

  if (hamburger && drawer) {
    const toggleDrawer = () => {
      const isOpen = drawer.classList.toggle('open');
      hamburger.classList.toggle('open', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    };

    hamburger.addEventListener('click', toggleDrawer);

    // Close on overlay click (outside drawer)
    document.addEventListener('click', (e) => {
      if (drawer.classList.contains('open') &&
          !drawer.contains(e.target) &&
          !hamburger.contains(e.target)) {
        toggleDrawer();
      }
    });

    // Close on escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && drawer.classList.contains('open')) {
        toggleDrawer();
      }
    });
  }

  /* ── 3. Scroll Reveal ──────────────────────────────────── */
  const revealEls = $$('[data-reveal], .reveal');

  if (revealEls.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const delay = entry.target.dataset.delay || 0;
          setTimeout(() => {
            entry.target.classList.add('revealed');
          }, Number(delay));
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach((el) => observer.observe(el));
  }

  /* ── 4. Auth Modal ─────────────────────────────────────── */
  const authModal   = $('#authModal');
  const authOverlay = $('.modal-overlay[data-modal="auth"]');

  const openModal = (overlay) => {
    if (!overlay) return;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = (overlay) => {
    if (!overlay) return;
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  };

  // Open triggers
  $$('[data-open-modal="auth"]').forEach((el) => {
    el.addEventListener('click', () => openModal(authOverlay));
  });

  // Close triggers
  if (authOverlay) {
    // Click outside modal box
    authOverlay.addEventListener('click', (e) => {
      if (e.target === authOverlay) closeModal(authOverlay);
    });

    // Close button
    const closeBtn = authOverlay.querySelector('.modal-close');
    if (closeBtn) closeBtn.addEventListener('click', () => closeModal(authOverlay));

    // Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal(authOverlay);
    });

    // Auth tab switching
    const tabs   = $$('.auth-tab', authOverlay);
    const panels = $$('.auth-panel', authOverlay);

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        tabs.forEach(t => t.classList.remove('active'));
        panels.forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        const targetPanel = authOverlay.querySelector(`.auth-panel[data-panel="${target}"]`);
        if (targetPanel) targetPanel.classList.add('active');
      });
    });

    // Switch between login/signup from footer text
    $$('[data-switch-tab]', authOverlay).forEach((el) => {
      el.addEventListener('click', () => {
        const targetTab = el.dataset.switchTab;
        const tab = authOverlay.querySelector(`.auth-tab[data-tab="${targetTab}"]`);
        if (tab) tab.click();
      });
    });
  }

  /* ── 5. Bedroom Counter ────────────────────────────────── */
  $$('.bedroom-counter').forEach((counter) => {
    const minusBtn  = counter.querySelector('.bedroom-counter-btn[data-action="minus"]');
    const plusBtn   = counter.querySelector('.bedroom-counter-btn[data-action="plus"]');
    const display   = counter.querySelector('.bedroom-counter-value');
    const hiddenInput = counter.closest('form')?.querySelector('input[name="bedroom"]');

    let count = parseInt(hiddenInput?.value || 1);

    const update = () => {
      if (display)     display.textContent = `${count} Bedroom${count !== 1 ? 's' : ''}`;
      if (hiddenInput) hiddenInput.value = count;
      if (minusBtn)    minusBtn.disabled = count <= 1;
    };

    update();

    if (minusBtn) {
      minusBtn.addEventListener('click', () => {
        if (count > 1) { count--; update(); }
      });
    }

    if (plusBtn) {
      plusBtn.addEventListener('click', () => {
        if (count < 10) { count++; update(); }
      });
    }
  });

  /* ── 6. Date Picker (uses native date input) ───────────── */
  $$('.date-field-input').forEach((input) => {
    // Ensure min date is today
    const today = new Date().toISOString().split('T')[0];
    input.setAttribute('min', today);

    // Link check-in to check-out min date
    if (input.name === 'check_in') {
      input.addEventListener('change', () => {
        const checkOut = document.querySelector('input[name="check_out"]');
        if (checkOut) checkOut.setAttribute('min', input.value);
      });
    }
  });

  /* ── 7. Booking Widget Price Calculation ───────────────── */
  const bookingWidget = $('.booking-widget');
  if (bookingWidget) {
    const checkIn   = bookingWidget.querySelector('input[name="check_in"]');
    const checkOut  = bookingWidget.querySelector('input[name="check_out"]');
    const nightsRow = bookingWidget.querySelector('.nights-row');
    const totalEl   = bookingWidget.querySelector('.price-total-value');
    const pricePerNight = parseFloat(bookingWidget.dataset.pricePerNight || 0);

    const updateTotal = () => {
      if (!checkIn?.value || !checkOut?.value) return;
      const d1 = new Date(checkIn.value);
      const d2 = new Date(checkOut.value);
      const nights = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
      if (nights <= 0) return;
      const subtotal = nights * pricePerNight;
      if (nightsRow) nightsRow.textContent = `RM${subtotal.toLocaleString()}`;
      if (totalEl)   totalEl.textContent   = `RM${subtotal.toLocaleString()}`;
    };

    checkIn?.addEventListener('change', updateTotal);
    checkOut?.addEventListener('change', updateTotal);
  }

  /* ── 8. Gallery – show all photos overlay ──────────────── */
  const showPhotosBtn = $('.show-all-photos');
  if (showPhotosBtn) {
    showPhotosBtn.addEventListener('click', () => {
      const overlay = $('#galleryOverlay');
      if (overlay) overlay.classList.add('open');
    });
  }

  const galleryOverlay = $('#galleryOverlay');
  if (galleryOverlay) {
    galleryOverlay.addEventListener('click', (e) => {
      if (e.target === galleryOverlay || e.target.classList.contains('gallery-close')) {
        galleryOverlay.classList.remove('open');
      }
    });
  }

  /* ── 9. Active nav link ────────────────────────────────── */
  const currentPath = window.location.pathname;
  $$('.nav-link, .mobile-nav-link').forEach((link) => {
    if (link.href && new URL(link.href).pathname === currentPath) {
      link.classList.add('active');
    }
  });

  /* ── 10. Form UX — prevent double submit ───────────────── */
  $$('form').forEach((form) => {
    form.addEventListener('submit', (e) => {
      const submitBtn = form.querySelector('[type="submit"]');
      if (!submitBtn) return;
      if (submitBtn.dataset.submitting) {
        e.preventDefault();
        return;
      }
      submitBtn.dataset.submitting = 'true';
      const originalText = submitBtn.value || submitBtn.textContent;
      if (submitBtn.tagName === 'INPUT') {
        submitBtn.value = 'Please wait…';
      } else {
        submitBtn.textContent = 'Please wait…';
      }
      submitBtn.style.opacity = '0.7';
      submitBtn.style.pointerEvents = 'none';

      // Safety reset after 10s
      setTimeout(() => {
        delete submitBtn.dataset.submitting;
        if (submitBtn.tagName === 'INPUT') submitBtn.value = originalText;
        else submitBtn.textContent = originalText;
        submitBtn.style.opacity = '';
        submitBtn.style.pointerEvents = '';
      }, 10000);
    });
  });

  /* ── 11. Smooth scroll for anchor links ────────────────── */
  $$('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (e) => {
      const target = document.getElementById(anchor.getAttribute('href').slice(1));
      if (target) {
        e.preventDefault();
        const headerOffset = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h')) || 80;
        const y = target.getBoundingClientRect().top + window.scrollY - headerOffset - 16;
        window.scrollTo({ top: y, behavior: 'smooth' });
      }
    });
  });

  /* ── 12. Toastr init (if available) ────────────────────── */
  if (typeof toastr !== 'undefined') {
    toastr.options = {
      timeOut: 5000,
      closeButton: true,
      progressBar: true,
      positionClass: 'toast-top-right',
      preventDuplicates: true,
    };
  }

})();
