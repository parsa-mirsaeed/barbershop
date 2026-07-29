<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!-- wp:group {"anchor":"gallery","align":"full","className":"pbs-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<div id="gallery" class="wp-block-group alignfull pbs-section" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
  <div class="wp-block-group alignwide pbs-section-heading"><p class="pbs-kicker has-muted-gold-color has-text-color">نمونه‌کارها</p><h2 class="wp-block-heading has-heading-2-font-size">قبل و بعد، با اجازه انتشار</h2><p class="has-sand-color has-text-color">تصاویر زیر جای‌نگهدار هستند. آن‌ها را فقط با عکس‌های مجاز جایگزین کنید.</p></div>
  <!-- wp:shortcode -->[barbershop_before_after_gallery limit="6"]<!-- /wp:shortcode -->
  <!-- wp:columns {"align":"wide","className":"pbs-gallery-grid"} --><div class="wp-block-columns alignwide pbs-gallery-grid">
    <!-- wp:column --><div class="wp-block-column"><figure class="wp-block-image pbs-gallery-item"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/gallery-1.svg' ) ); ?>" alt="جای‌نگهدار نمونه‌کار شماره یک" width="1200" height="900"/><figcaption>[عنوان نمونه‌کار]</figcaption></figure></div><!-- /wp:column -->
    <!-- wp:column --><div class="wp-block-column"><figure class="wp-block-image pbs-gallery-item"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/gallery-2.svg' ) ); ?>" alt="جای‌نگهدار نمونه‌کار شماره دو" width="1200" height="900"/><figcaption>[عنوان نمونه‌کار]</figcaption></figure></div><!-- /wp:column -->
    <!-- wp:column --><div class="wp-block-column"><figure class="wp-block-image pbs-gallery-item"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/gallery-3.svg' ) ); ?>" alt="جای‌نگهدار نمونه‌کار شماره سه" width="1200" height="900"/><figcaption>[عنوان نمونه‌کار]</figcaption></figure></div><!-- /wp:column -->
  </div><!-- /wp:columns -->
</div><!-- /wp:group -->
