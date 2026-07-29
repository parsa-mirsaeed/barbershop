<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!-- wp:group {"anchor":"home","align":"full","className":"pbs-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<div id="home" class="wp-block-group alignfull pbs-hero" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
  <!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"}}}} -->
  <div class="wp-block-columns alignwide are-vertically-aligned-center">
    <!-- wp:column {"verticalAlignment":"center","width":"56%"} --><div class="wp-block-column is-vertically-aligned-center pbs-hero-copy" style="flex-basis:56%">
      <!-- wp:paragraph {"className":"pbs-kicker","textColor":"muted-gold"} --><p class="pbs-kicker has-muted-gold-color has-text-color">[نام کسب‌وکار]</p><!-- /wp:paragraph -->
      <!-- wp:heading {"level":1,"fontSize":"display"} --><h1 class="wp-block-heading has-display-font-size">استایل دقیق، تجربه‌ای آرام</h1><!-- /wp:heading -->
      <!-- wp:paragraph {"fontSize":"lead","textColor":"sand"} --><p class="has-sand-color has-text-color has-lead-font-size">من [نام آرایشگر] هستم؛ اینجا فضایی برای اصلاح حرفه‌ای، مشاوره شخصی و نتیجه‌ای متناسب با چهره شماست.</p><!-- /wp:paragraph -->
      <!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#contact">هماهنگی و تماس</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#gallery">دیدن نمونه‌کارها</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
    </div><!-- /wp:column -->
    <!-- wp:column {"verticalAlignment":"center","width":"44%"} --><div class="wp-block-column is-vertically-aligned-center" style="flex-basis:44%"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"pbs-hero-visual"} --><figure class="wp-block-image size-full pbs-hero-visual"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/hero-barber.svg' ) ); ?>" alt="تصویر گرافیکی عمومی برای معرفی آرایشگاه" width="1200" height="1500"/></figure><!-- /wp:image --></div><!-- /wp:column -->
  </div><!-- /wp:columns -->
</div><!-- /wp:group -->
