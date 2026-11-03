(() => {
  'use strict';

  document.documentElement.classList.add('pbs-js');

  const header = document.querySelector('.site-header-shell');
  const syncHeader = () => {
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 20);
  };
  syncHeader();
  window.addEventListener('scroll', syncHeader, { passive: true });

  // Toggle the one existing product-search form; never move, clone or rewrite it.
  // Core retains ownership of account/cart links and the mobile purchase dock.
  const toggle = header?.querySelector('.pbs-search-toggle');
  const panel = header?.querySelector('#pbs-header-search-panel');
  if (toggle && panel) {
    const mobile = window.matchMedia('(max-width: 781px)');
    const setSearchOpen = (open, restoreFocus = false) => {
      panel.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute(
        'aria-label',
        open ? 'بستن جست‌وجوی محصولات' : 'باز کردن جست‌وجوی محصولات'
      );
      if (open) panel.querySelector('input[type="search"]')?.focus({ preventScroll: true });
      if (!open && restoreFocus) toggle.focus({ preventScroll: true });
    };

    toggle.addEventListener('click', () => {
      setSearchOpen(!panel.classList.contains('is-open'));
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && panel.classList.contains('is-open')) {
        setSearchOpen(false, true);
      }
    });
    document.addEventListener('pointerdown', (event) => {
      if (panel.classList.contains('is-open') && !header.contains(event.target)) {
        setSearchOpen(false);
      }
    });
    mobile.addEventListener('change', () => setSearchOpen(false));
  }

  const motionAllowed = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const candidates = document.querySelectorAll(
    '.pbs-hero-copy, .pbs-hero-visual, .pbs-trust-item, .bsc-category-card, .woocommerce ul.products li.product, .pbs-card, .pbs-gallery-item, .pbs-contact-panel'
  );

  if (!motionAllowed || !('IntersectionObserver' in window)) {
    candidates.forEach((node) => node.classList.add('is-visible'));
    return;
  }

  candidates.forEach((node) => node.classList.add('pbs-reveal'));
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
  );
  candidates.forEach((node) => observer.observe(node));
})();
