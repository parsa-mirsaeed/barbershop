<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!-- wp:group {"anchor":"articles","align":"full","className":"pbs-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<div id="articles" class="wp-block-group alignfull pbs-section" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
  <!-- wp:group {"align":"wide","className":"pbs-section-heading"} --><div class="wp-block-group alignwide pbs-section-heading"><!-- wp:paragraph {"className":"pbs-kicker"} --><p class="pbs-kicker">مقاله‌ها</p><!-- /wp:paragraph --><!-- wp:heading {"fontSize":"heading-2"} --><h2 class="wp-block-heading has-heading-2-font-size">راهنمای نگهداری و استایل</h2><!-- /wp:heading --></div><!-- /wp:group -->
  <!-- wp:query {"queryId":1,"query":{"perPage":3,"postType":"post","order":"desc","orderBy":"date","inherit":false},"align":"wide"} -->
  <div class="wp-block-query alignwide">
    <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} --><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3"} /--><!-- wp:post-title {"isLink":true,"fontSize":"heading-3"} /--><!-- wp:post-excerpt {"moreText":"ادامه مطلب"} /--><!-- /wp:post-template -->
    <!-- wp:query-no-results --><!-- wp:paragraph {"align":"center","textColor":"muted"} --><p class="has-text-align-center has-muted-color has-text-color">هنوز مقاله‌ای منتشر نشده است.</p><!-- /wp:paragraph --><!-- /wp:query-no-results -->
  </div><!-- /wp:query -->
</div><!-- /wp:group -->
