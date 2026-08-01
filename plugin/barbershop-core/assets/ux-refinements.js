(() => {
  'use strict';

  const root = document.documentElement;
  const body = document.body;
  const header = document.querySelector('.site-header-shell');
  let scheduled = false;

  const loginIdentity = document.querySelector(
    'body.woocommerce-account:not(.logged-in) form.login input[name="username"]'
  );
  if (loginIdentity) {
    loginIdentity.setAttribute('placeholder', 'شماره موبایل یا ایمیل');
    loginIdentity.setAttribute('autocomplete', 'username');
    loginIdentity.setAttribute('inputmode', 'email');
  }

  const syncNavigationState = () => {
    scheduled = false;
    const openMenu = document.querySelector(
      '.pbs-header-navigation .wp-block-navigation__responsive-container.is-menu-open'
    );
    const isOpen = Boolean(openMenu);

    root.classList.toggle('bsc-nav-open', isOpen);
    body.classList.toggle('bsc-nav-open', isOpen);
    if (header) header.classList.toggle('bsc-nav-open', isOpen);

    if (openMenu) {
      openMenu.setAttribute('aria-modal', 'true');
      openMenu.setAttribute('role', 'dialog');
    }
  };

  const scheduleSync = () => {
    if (scheduled) return;
    scheduled = true;
    window.requestAnimationFrame(syncNavigationState);
  };

  const observer = new MutationObserver(scheduleSync);
  observer.observe(body, {
    attributes: true,
    attributeFilter: ['class'],
    childList: true,
    subtree: true,
  });

  document.addEventListener('click', scheduleSync, true);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') scheduleSync();
  });
  window.addEventListener('resize', scheduleSync, { passive: true });
  syncNavigationState();
})();
