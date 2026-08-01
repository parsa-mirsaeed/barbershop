(() => {
  'use strict';

  document.documentElement.classList.add('pbs-js');

  const header = document.querySelector('.site-header-shell');
  const syncHeader = () => {
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 20);
  };
  syncHeader();
  window.addEventListener('scroll', syncHeader, { passive: true });

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
