(() => {
  'use strict';
  document.querySelectorAll('[data-bsc-before-after]').forEach((component) => {
    const range = component.querySelector('input[type="range"]');
    const after = component.querySelector('.bsc-ba__after');
    if (!range || !after) return;
    const update = () => {
      const value = Number(range.value);
      after.style.clipPath = `inset(0 ${100 - value}% 0 0)`;
      range.setAttribute('aria-valuetext', `${value}% after image`);
    };
    range.addEventListener('input', update, { passive: true });
    update();
  });
})();
