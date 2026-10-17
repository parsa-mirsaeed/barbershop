/* Keep WooCommerce's own FlexSlider as primary; show full-size gallery slides when it is absent. */
(function () {
  'use strict';
  function slides(gallery) {
    return Array.from(gallery.querySelectorAll('.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image'));
  }
  function selectThumb(img) {
    var gallery = img.closest('.woocommerce-product-gallery');
    if (!gallery) return;
    var thumbs = Array.from(gallery.querySelectorAll('.flex-control-thumbs > li > img'));
    var index = thumbs.indexOf(img);
    if (index < 0) return;
    var jq = window.jQuery;
    if (jq && jq.fn && jq.fn.flexslider && jq(gallery).data('flexslider')) {
      jq(gallery).flexslider(index);
      return;
    }
    var items = slides(gallery);
    if (index >= items.length) return;
    gallery.classList.add('bsc-gallery-fallback');
    items.forEach(function (item, i) {
      item.style.display = i === index ? 'block' : 'none';
      item.style.width = '100%';
      item.style.float = 'none';
    });
    thumbs.forEach(function (thumb, i) {
      thumb.classList.toggle('flex-active', i === index);
      thumb.setAttribute('aria-current', i === index ? 'true' : 'false');
    });
  }
  document.addEventListener('click', function (event) {
    var thumb = event.target.closest('.woocommerce-product-gallery .flex-control-thumbs > li > img');
    if (!thumb) return;
    // Allow WooCommerce's own thumbnail handler to run first.
    window.setTimeout(function () { selectThumb(thumb); }, 0);
  });
  document.addEventListener('keydown', function (event) {
    var thumb = event.target.closest('.woocommerce-product-gallery .flex-control-thumbs > li > img');
    if (thumb && (event.key === 'Enter' || event.key === ' ')) {
      event.preventDefault();
      thumb.click();
    }
  });
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.woocommerce-product-gallery .flex-control-thumbs > li > img').forEach(function (img) {
      img.tabIndex = 0;
      img.setAttribute('role', 'button');
      img.setAttribute('aria-label', img.alt || 'نمایش تصویر محصول');
    });
  });
}());
