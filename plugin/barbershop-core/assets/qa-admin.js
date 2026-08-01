(() => {
  'use strict';

  const form = document.querySelector('.bsc-dashboard-form');
  if (!form) return;

  const markDirty = () => {
    form.dataset.bscDirty = 'true';
  };

  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);

  form.addEventListener('submit', () => {
    form.dataset.bscDirty = 'false';
    const button = form.querySelector('.bsc-dashboard-save button[type="submit"]');
    if (button) {
      button.disabled = true;
      button.textContent = 'در حال ذخیره…';
    }
  });

  window.addEventListener('beforeunload', (event) => {
    if (form.dataset.bscDirty !== 'true') return;
    event.preventDefault();
    event.returnValue = '';
  });
})();
