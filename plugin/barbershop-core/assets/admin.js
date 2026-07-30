(() => {
  'use strict';

  const announce = (wrap, text) => {
    if (!wrap) return;
    wrap.setAttribute('aria-label', text);
  };

  document.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-bsc-media-remove]');
    if (removeButton) {
      event.preventDefault();
      const target = removeButton.dataset.bscMediaRemove;
      const input = document.getElementById(target);
      const preview = document.querySelector(`[data-bsc-preview="${target}"]`);
      const wrap = document.querySelector(`[data-bsc-preview-wrap="${target}"]`);
      if (input) input.value = '';
      if (preview) {
        preview.src = '';
        preview.hidden = true;
      }
      announce(wrap, 'آیکون حذف شد');
      removeButton.focus();
      return;
    }

    const button = event.target.closest('[data-bsc-media-target]');
    if (!button || typeof window.wp === 'undefined' || !window.wp.media) return;
    event.preventDefault();
    const target = button.dataset.bscMediaTarget;
    const input = document.getElementById(target);
    if (!input) return;
    const frame = window.wp.media({ title: 'انتخاب آیکون دسته‌بندی', button: { text: 'استفاده از این تصویر' }, multiple: false, library: { type: 'image' } });
    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      let preview = document.querySelector(`[data-bsc-preview="${target}"]`);
      const wrap = document.querySelector(`[data-bsc-preview-wrap="${target}"]`);
      if (!preview && wrap) {
        preview = document.createElement('img');
        preview.dataset.bscPreview = target;
        preview.alt = 'پیش‌نمایش آیکون دسته‌بندی';
        wrap.appendChild(preview);
      }
      input.value = String(attachment.id);
      if (preview) {
        preview.src = attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url;
        preview.hidden = false;
      }
      announce(wrap, 'آیکون جدید انتخاب شد');
      button.focus();
    });
    frame.open();
  });
})();
