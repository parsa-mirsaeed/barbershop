from __future__ import annotations
import re
import unittest
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "barbershop-core"


class StorefrontFeaturesContract(unittest.TestCase):
    def test_product_search_is_woo_only(self):
        source = (PLUGIN / "includes" / "product-search.php").read_text(encoding="utf-8")
        header = (ROOT / "theme" / "persian-barbershop" / "parts" / "header.html").read_text(encoding="utf-8")
        for item in ('name="s"', 'name="post_type" value="product"', 'pre_get_posts',
                     'woocommerce_product_loop_start', 'wc_get_template_part', 'paginate_links'):
            self.assertIn(item, source)
        self.assertIn("[bsc_product_search]", header)

    def test_visibility_defaults_and_filters(self):
        source = (PLUGIN / "includes" / "visibility.php").read_text(encoding="utf-8")
        for section in ("home", "categories", "products", "services", "gallery",
                        "articles", "reviews", "contact"):
            self.assertIn(f"'{section}' =>", source)
        self.assertIn("get_option( 'bsc_show_section_'", source)
        self.assertIn("render_block", source)
        self.assertIn("woocommerce_product_tabs", source)
        self.assertIn("comments_open", source)

    def test_farazsms_single_owner_and_failure_secure(self):
        source = (PLUGIN / "includes" / "farazsms-bridge.php").read_text(encoding="utf-8")
        plugin = (PLUGIN / "barbershop-core.php").read_text(encoding="utf-8")
        for item in ("bsc_faraz_ready", "FARAZSMS_MONTIX_SECURITY_PATCH",
                     "is_ssl()", "check_admin_referer", "get_transient",
                     "farazsms_montix_phone_claimable", "farazsms_login_form",
                     "admin_post_bsc_faraz_link_verify", "woocommerce_registration_errors",
                     "woocommerce_enable_signup_and_login_from_checkout"):
            self.assertIn(item, source)
        self.assertIn("includes/farazsms-bridge.php", plugin)
        self.assertNotIn("includes/otp-auth.php", plugin)
        self.assertFalse((PLUGIN / "includes" / "otp-auth.php").exists())


class ProductGalleryMobileBrowserTest(unittest.TestCase):
    def test_second_and_third_thumb_select_full_slide_not_small_thumbnail(self):
        import shutil
        with sync_playwright() as p:
            executable = shutil.which("chromium") or shutil.which("chromium-browser")
            args = {"headless": True}
            if executable:
                args["executable_path"] = executable
            browser = p.chromium.launch(**args)
            try:
                for width in (375, 412, 768):
                    context = browser.new_context(viewport={"width": width, "height": 812}, has_touch=True,
                                                  is_mobile=True)
                    page = context.new_page()
                    js = (PLUGIN / "assets" / "gallery-fix.js").read_text(encoding="utf-8")
                    css = (PLUGIN / "assets" / "storefront-features.css").read_text(encoding="utf-8")
                    html = """<html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head>
                    <body class="woocommerce"><main><div class="product">
                      <div class="woocommerce-product-gallery" style="width:100%;max-width:330px">
                        <div class="woocommerce-product-gallery__wrapper">
                          <div class="woocommerce-product-gallery__image" data-id="0"><img src="https://example.invalid/full-1.jpg" width="900" height="900"></div>
                          <div class="woocommerce-product-gallery__image" data-id="1"><img src="https://example.invalid/full-2.jpg" width="900" height="900"></div>
                          <div class="woocommerce-product-gallery__image" data-id="2"><img src="https://example.invalid/full-3.jpg" width="900" height="900"></div>
                        </div>
                        <ol class="flex-control-thumbs">
                          <li><img src="https://example.invalid/thumb-1.jpg" alt="اول"></li>
                          <li><img src="https://example.invalid/thumb-2.jpg" alt="دوم"></li>
                          <li><img src="https://example.invalid/thumb-3.jpg" alt="سوم"></li>
                        </ol>
                      </div>
                    </div></main></body></html>"""
                    page.set_content(html.replace("</head>", f"<style>{css}</style></head>").replace(
                        "</body>", f"<script>{js}</script></body>"), wait_until="domcontentloaded")
                    thumbs = page.locator(".flex-control-thumbs img")
                    for selected in (1, 2):
                        thumbs.nth(selected).click()
                        page.wait_for_timeout(100)
                        gallery = page.locator(".woocommerce-product-gallery")
                        self.assertIn("bsc-gallery-fallback", gallery.get_attribute("class"))
                        images = gallery.locator(".woocommerce-product-gallery__image")
                        self.assertEqual("block", images.nth(selected).evaluate("el=>getComputedStyle(el).display"))
                        self.assertEqual("none", images.nth(0).evaluate("el=>getComputedStyle(el).display"))
                        self.assertGreater(images.nth(selected).bounding_box()["width"], 250)
                        self.assertEqual("true", thumbs.nth(selected).get_attribute("aria-current"))
                    context.close()
            finally:
                browser.close()


if __name__ == "__main__":
    unittest.main()
