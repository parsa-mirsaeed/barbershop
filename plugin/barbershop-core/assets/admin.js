(() => {
  'use strict';
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-bsc-media-target]');
    if (!button || typeof window.wp === 'undefined' || !window.wp.media) return;
    event.preventDefault();
    const target = button.dataset.bscMediaTarget;
    const input = document.getElementById(target);
    const preview = document.querySelector(`[data-bsc-preview="${target}"]`);
    if (!input) return;
    const frame = window.wp.media({ title: 'Choose image', multiple: false, library: { type: 'image' } });
    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      input.value = String(attachment.id);
      if (preview) {
        preview.src = attachment.sizes?.medium?.url || attachment.url;
        preview.hidden = false;
      }
    });
    frame.open();
  });
})();
