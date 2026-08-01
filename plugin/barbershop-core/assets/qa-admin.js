(() => {
  'use strict';

  const forms = Array.from(document.querySelectorAll('.bsc-dashboard-form'));
  if (!forms.length) return;

  let isSubmitting = false;

  const markDirty = (event) => {
    const form = event.currentTarget;
    form.dataset.bscDirty = 'true';
  };

  forms.forEach((form) => {
    form.dataset.bscDirty = 'false';
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    form.addEventListener('submit', () => {
      isSubmitting = true;
      form.dataset.bscDirty = 'false';
      const button = form.querySelector('button[type="submit"]');
      if (button) {
        button.disabled = true;
        button.dataset.bscOriginalText = button.textContent;
        button.textContent = 'در حال ذخیره…';
      }
    }, true);
  });

  window.addEventListener('beforeunload', (event) => {
    const hasDirtyForm = forms.some((form) => form.dataset.bscDirty === 'true');
    if (isSubmitting || !hasDirtyForm) return;
    event.preventDefault();
    event.returnValue = '';
  });
})();
