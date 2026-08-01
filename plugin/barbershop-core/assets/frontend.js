(() => {
  'use strict';
  document.querySelectorAll('[data-bsc-before-after]').forEach((component) => {
    const range = component.querySelector('input[type="range"]');
    const after = component.querySelector('.bsc-ba__after');
    if (!range || !after) return;
    const update = () => {
      const value = Number(range.value);
      after.style.clipPath = `inset(0 ${100 - value}% 0 0)`;
      range.setAttribute('aria-valuetext', `${value} درصد تصویر بعد`);
    };
    range.addEventListener('input', update, { passive: true });
    update();
  });

  const dock = document.querySelector('.bsc-mobile-dock');
  if (dock) {
    const currentPath = window.location.pathname.replace(/\/$/, '');
    dock.querySelectorAll('a[href]').forEach((link) => {
      try {
        const path = new URL(link.href, window.location.origin).pathname.replace(/\/$/, '');
        if (path && path === currentPath) link.setAttribute('aria-current', 'page');
      } catch (error) {
        // Ignore malformed third-party URLs instead of breaking the navigation.
      }
    });
  }
})();
