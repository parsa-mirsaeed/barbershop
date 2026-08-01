<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!-- wp:group {"anchor":"categories","align":"full","className":"pbs-section pbs-category-section","backgroundColor":"navy-deep","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<div id="categories" class="wp-block-group alignfull pbs-section pbs-category-section has-navy-deep-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
  <!-- wp:group {"align":"wide","className":"pbs-section-heading","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
  <div class="wp-block-group alignwide pbs-section-heading">
    <!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph {"className":"pbs-kicker"} --><p class="pbs-kicker">دسته‌بندی محصولات</p><!-- /wp:paragraph --><!-- wp:heading {"fontSize":"heading-2"} --><h2 class="wp-block-heading has-heading-2-font-size">سریع به انتخاب درست برسید</h2><!-- /wp:heading --></div><!-- /wp:group -->
    <!-- wp:paragraph {"textColor":"muted"} --><p class="has-muted-color has-text-color">نام، زیرگروه، ترتیب و آیکون هر دسته از پنل فارسی فروشگاه، بدون ویرایش کد تغییر می‌کند.</p><!-- /wp:paragraph -->
  </div><!-- /wp:group -->
  <!-- wp:group {"align":"wide"} --><div class="wp-block-group alignwide"><!-- wp:shortcode -->[bsc_product_categories limit="6"]<!-- /wp:shortcode --></div><!-- /wp:group -->
</div><!-- /wp:group -->

<!-- wp:group {"anchor":"products","align":"full","className":"pbs-section pbs-products-section","backgroundColor":"mist","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<div id="products" class="wp-block-group alignfull pbs-section pbs-products-section has-mist-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
  <!-- wp:group {"align":"wide","className":"pbs-section-heading","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
  <div class="wp-block-group alignwide pbs-section-heading">
    <!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph {"className":"pbs-kicker"} --><p class="pbs-kicker">فروشگاه</p><!-- /wp:paragraph --><!-- wp:heading {"fontSize":"heading-2"} --><h2 class="wp-block-heading has-heading-2-font-size">محصولات تازه و منتخب</h2><!-- /wp:heading --><!-- wp:paragraph --><p>کارت‌های محصول برای فارسی، موبایل و لمس آسان بازطراحی شده‌اند.</p><!-- /wp:paragraph --></div><!-- /wp:group -->
    <!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/">همه محصولات</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
  </div><!-- /wp:group -->
  <!-- wp:group {"align":"wide"} --><div class="wp-block-group alignwide"><!-- wp:shortcode -->[products limit="8" columns="4" orderby="date" order="DESC" visibility="visible"]<!-- /wp:shortcode --></div><!-- /wp:group -->
</div><!-- /wp:group -->
