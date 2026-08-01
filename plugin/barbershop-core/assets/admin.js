(() => {
  'use strict';

  const announce = (wrap, text) => {
    if (!wrap) return;
    wrap.setAttribute('aria-label', text);
  };

  const notifyChange = (input) => {
    if (!input) return;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  };

  document.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-bsc-media-remove]');
    if (removeButton) {
      event.preventDefault();
      const target = removeButton.dataset.bscMediaRemove;
      const input = document.getElementById(target);
      const preview = document.querySelector(`[data-bsc-preview="${target}"]`);
      const wrap = document.querySelector(`[data-bsc-preview-wrap="${target}"]`);
      if (input) {
        input.value = '';
        notifyChange(input);
      }
      if (preview) {
        preview.src = '';
        preview.hidden = true;
      }
      announce(wrap, 'تصویر حذف شد');
      removeButton.focus();
      return;
    }

    const button = event.target.closest('[data-bsc-media-target]');
    if (!button || typeof window.wp === 'undefined' || !window.wp.media) return;
    event.preventDefault();
    const target = button.dataset.bscMediaTarget;
    const input = document.getElementById(target);
    if (!input) return;
    const title = button.dataset.bscMediaTitle || 'انتخاب تصویر';
    const frame = window.wp.media({ title, button: { text: 'استفاده از این تصویر' }, multiple: false, library: { type: 'image' } });
    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      let preview = document.querySelector(`[data-bsc-preview="${target}"]`);
      const wrap = document.querySelector(`[data-bsc-preview-wrap="${target}"]`);
      if (!preview && wrap) {
        preview = document.createElement('img');
        preview.dataset.bscPreview = target;
        preview.alt = `پیش‌نمایش ${title}`;
        wrap.appendChild(preview);
      }
      input.value = String(attachment.id);
      notifyChange(input);
      if (preview) {
        preview.src = attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url;
        preview.hidden = false;
      }
      announce(wrap, 'تصویر جدید انتخاب شد');
      button.focus();
    });
    frame.open();
  });
})();
