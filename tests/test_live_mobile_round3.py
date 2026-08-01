from __future__ import annotations

import os
import re
import shutil
import unittest
from pathlib import Path

from playwright.sync_api import Browser, Page, sync_playwright

from test_ui import rendered_fixture_html

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "barbershop-core"
LIVE_CSS = PLUGIN / "assets" / "mobile-live-fixes.css"


def live_fixture_html() -> str:
    html = rendered_fixture_html()
    css = LIVE_CSS.read_text(encoding="utf-8")
    return html.replace("</head>", f"<style>{css}</style></head>")


def account_fixture_html() -> str:
    html = live_fixture_html().replace(
        '<body class="pbs-vazirmatn-ready">',
        '<body class="pbs-vazirmatn-ready woocommerce-page woocommerce-account">',
    )
    account = """
    <main id="main-content" class="wp-block-group pbs-content-shell">
      <div class="wp-block-post-content">
        <div class="woocommerce">
          <h1>حساب کاربری</h1>
          <div id="customer_login" class="u-columns col2-set">
            <div class="u-column1 col-1">
              <h2>ورود</h2>
              <form class="woocommerce-form woocommerce-form-login login">
                <p class="form-row form-row-wide"><label>نام کاربری یا نشانی ایمیل</label><input class="input-text" type="email"></p>
                <p class="form-row form-row-wide"><label>رمز عبور</label><input class="input-text" type="password"></p>
                <p class="form-row"><button class="button" type="button">ورود</button></p>
              </form>
            </div>
            <div class="u-column2 col-2">
              <h2>ساخت حساب</h2>
              <form class="woocommerce-form woocommerce-form-register register">
                <p class="bsc-register-note">اطلاعات لازم برای ورود و پیگیری سفارش دریافت می‌شود.</p>
                <p class="form-row form-row-wide"><label>نام</label><input class="input-text" type="text"></p>
                <p class="form-row form-row-wide"><label>نام خانوادگی</label><input class="input-text" type="text"></p>
                <p class="form-row form-row-wide"><label>نشانی ایمیل</label><input class="input-text" type="email"></p>
                <p class="form-row form-row-wide"><label>رمز عبور</label><input class="input-text" type="password"></p>
                <p class="form-row"><button class="button" type="button">ساخت حساب</button></p>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
    """
    return re.sub(r'<main id="main-content".*?</main>', account, html, count=1, flags=re.S)


class LiveMobileRoundThreeTests(unittest.TestCase):
    browser: Browser
    playwright = None

    @classmethod
    def setUpClass(cls) -> None:
        cls.playwright = sync_playwright().start()
        executable = os.environ.get("PLAYWRIGHT_CHROMIUM_PATH") or shutil.which("chromium") or shutil.which("chromium-browser")
        kwargs = {"headless": True}
        if executable:
            kwargs["executable_path"] = executable
        cls.browser = cls.playwright.chromium.launch(**kwargs)

    @classmethod
    def tearDownClass(cls) -> None:
        cls.browser.close()
        cls.playwright.stop()

    def open_page(self, html: str, width: int, height: int) -> tuple[Page, object]:
        context = self.browser.new_context(
            viewport={"width": width, "height": height},
            screen={"width": width, "height": height},
            locale="fa-IR",
            is_mobile=True,
            has_touch=True,
        )
        page = context.new_page()
        page.set_content(html, wait_until="load")
        return page, context

    def assert_no_horizontal_overflow(self, page: Page, width: int) -> None:
        metrics = page.evaluate(
            "() => ({inner: innerWidth, root: document.documentElement.scrollWidth, body: document.body.scrollWidth})"
        )
        self.assertEqual(width, metrics["inner"])
        self.assertLessEqual(metrics["root"], width + 1, metrics)
        self.assertLessEqual(metrics["body"], width + 1, metrics)

    def test_live_runtime_outputs_the_missing_viewport_contract(self) -> None:
        source = (PLUGIN / "includes" / "mobile-live-fixes.php").read_text(encoding="utf-8")
        core = (PLUGIN / "barbershop-core.php").read_text(encoding="utf-8")
        css = LIVE_CSS.read_text(encoding="utf-8")

        self.assertIn('name="viewport"', source)
        self.assertIn("width=device-width", source)
        self.assertIn("viewport-fit=cover", source)
        self.assertIn("add_action( 'wp_head', 'bsc_mobile_live_viewport_meta', 0 )", source)
        self.assertIn("barbershop-core-runtime-storefront", source)
        self.assertIn("includes/mobile-live-fixes.php", core)
        self.assertIn("Version: 3.3.1", core)
        self.assertIn("max-width: 781px", css)
        self.assertIn("max-height: 520px", css)

    def test_portrait_412_stacks_hero_and_contains_every_major_surface(self) -> None:
        page, context = self.open_page(live_fixture_html(), 412, 915)
        try:
            self.assert_no_horizontal_overflow(page, 412)
            self.assertEqual("none", page.locator(".pbs-header-actions").evaluate("el => getComputedStyle(el).display"))
            self.assertEqual("column", page.locator(".pbs-hero .wp-block-columns").evaluate("el => getComputedStyle(el).flexDirection"))

            for selector in ("#home", "#categories", "#products", "#services", "#gallery", "#articles", "#reviews", "#contact", ".pbs-footer"):
                box = page.locator(selector).bounding_box()
                self.assertIsNotNone(box, selector)
                self.assertGreaterEqual(box["x"], -1.5, (selector, box))
                self.assertLessEqual(box["x"] + box["width"], 413.5, (selector, box))

            heading = page.locator(".pbs-hero h1").bounding_box()
            self.assertIsNotNone(heading)
            self.assertGreaterEqual(heading["x"], 0)
            self.assertLessEqual(heading["x"] + heading["width"], 412)

            dock = page.locator(".bsc-mobile-dock").bounding_box()
            self.assertIsNotNone(dock)
            self.assertGreaterEqual(dock["x"], 0)
            self.assertLessEqual(dock["x"] + dock["width"], 412)
            self.assertEqual(4, page.locator(".bsc-mobile-dock > a").count())
            widths = [page.locator(".bsc-mobile-dock > a").nth(index).bounding_box()["width"] for index in range(4)]
            self.assertLess(max(widths) - min(widths), 2)
            self.assertEqual(
                "rgba(0, 0, 0, 0)",
                page.locator(".bsc-mobile-dock > a.bsc-cart-link").evaluate("el => getComputedStyle(el).backgroundColor"),
            )
            self.assertEqual("column", page.locator(".pbs-footer .wp-block-columns").evaluate("el => getComputedStyle(el).flexDirection"))
        finally:
            context.close()

    def test_account_login_and_registration_fit_one_column_at_412(self) -> None:
        page, context = self.open_page(account_fixture_html(), 412, 915)
        try:
            self.assert_no_horizontal_overflow(page, 412)
            tracks = page.locator("#customer_login").evaluate("el => getComputedStyle(el).gridTemplateColumns")
            self.assertEqual(1, len([track for track in tracks.split(" ") if track and track != "none"]))

            for selector in ("#customer_login", "form.login", "form.register", "form.login input", "form.register input"):
                for index in range(page.locator(selector).count()):
                    box = page.locator(selector).nth(index).bounding_box()
                    self.assertIsNotNone(box, selector)
                    self.assertGreaterEqual(box["x"], -1, (selector, box))
                    self.assertLessEqual(box["x"] + box["width"], 413, (selector, box))

            final_button = page.locator("form.register button")
            final_button.scroll_into_view_if_needed()
            button_box = final_button.bounding_box()
            dock_box = page.locator(".bsc-mobile-dock").bounding_box()
            self.assertIsNotNone(button_box)
            self.assertIsNotNone(dock_box)
            self.assertLess(button_box["y"], dock_box["y"])
        finally:
            context.close()

    def test_short_landscape_915_keeps_header_compact_and_media_side_by_side(self) -> None:
        page, context = self.open_page(live_fixture_html(), 915, 412)
        try:
            self.assert_no_horizontal_overflow(page, 915)
            header = page.locator(".site-header-shell").bounding_box()
            self.assertIsNotNone(header)
            self.assertLessEqual(header["height"], 80)
            self.assertEqual("row", page.locator(".pbs-hero .wp-block-columns").evaluate("el => getComputedStyle(el).flexDirection"))
            self.assertEqual("row", page.locator(".pbs-contact-panel").evaluate("el => getComputedStyle(el).flexDirection"))
            self.assertNotEqual("none", page.locator(".pbs-header-actions").evaluate("el => getComputedStyle(el).display"))
            self.assertEqual("none", page.locator(".pbs-header-actions .bsc-account-link > span:last-child").evaluate("el => getComputedStyle(el).display"))
            for selector in (".pbs-header-actions .bsc-account-link", ".pbs-header-actions .bsc-cart-link"):
                box = page.locator(selector).bounding_box()
                self.assertIsNotNone(box)
                self.assertLessEqual(box["width"], 46)
        finally:
            context.close()

    def test_empty_sections_cannot_expand_to_a_viewport_height(self) -> None:
        page, context = self.open_page(live_fixture_html(), 412, 915)
        try:
            for selector in ("#gallery", "#articles", "#reviews", "#contact"):
                minimum = page.locator(selector).evaluate("el => parseFloat(getComputedStyle(el).minHeight) || 0")
                self.assertLessEqual(minimum, 1, (selector, minimum))
        finally:
            context.close()


if __name__ == "__main__":
    unittest.main()
