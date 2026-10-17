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
UX_CSS = PLUGIN / "assets" / "ux-refinements.css"
FEATURE_CSS = PLUGIN / "assets" / "storefront-features.css"
UX_JS = PLUGIN / "assets" / "ux-refinements.js"


def refined_fixture_html() -> str:
    html = rendered_fixture_html()
    styles = LIVE_CSS.read_text(encoding="utf-8") + "\n" + UX_CSS.read_text(encoding="utf-8") + "\n" + FEATURE_CSS.read_text(encoding="utf-8")
    script = UX_JS.read_text(encoding="utf-8")
    return html.replace("</head>", f"<style>{styles}</style></head>").replace("</body>", f"<script>{script}</script></body>")


def navigation_fixture_html() -> str:
    html = refined_fixture_html()
    navigation = """
    <nav class="pbs-header-navigation wp-block-navigation" aria-label="منوی اصلی">
      <button class="wp-block-navigation__responsive-container-open" type="button" aria-label="بازکردن منو">☰</button>
      <div class="wp-block-navigation__responsive-container">
        <div class="wp-block-navigation__responsive-close">
          <div class="wp-block-navigation__responsive-dialog">
            <button class="wp-block-navigation__responsive-container-close" type="button" aria-label="بستن منو">×</button>
            <div class="wp-block-navigation__responsive-container-content">
              <ul class="wp-block-navigation__container">
                <li class="wp-block-navigation-item"><a class="wp-block-navigation-item__content" href="#home">خانه</a></li>
                <li class="wp-block-navigation-item"><a class="wp-block-navigation-item__content" href="#categories">دسته‌بندی‌ها</a></li>
                <li class="wp-block-navigation-item"><a class="wp-block-navigation-item__content" href="#products">محصولات</a></li>
                <li class="wp-block-navigation-item"><a class="wp-block-navigation-item__content" href="#contact">تماس</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </nav>
    """
    html = re.sub(r'<nav class="pbs-header-navigation".*?</nav>', navigation, html, count=1, flags=re.S)
    fixture_contract = """
    <style>
      @media(max-width:781px){
        .pbs-header-navigation{display:block!important}
        .wp-block-navigation__responsive-container{display:none}
        .wp-block-navigation__responsive-container.is-menu-open{display:flex!important}
        .wp-block-navigation__responsive-close,.wp-block-navigation__responsive-dialog{inline-size:100%;min-block-size:100%}
      }
    </style>
    """
    return html.replace("</head>", fixture_contract + "</head>")


def logged_out_account_fixture_html() -> str:
    html = refined_fixture_html().replace(
        '<body class="pbs-vazirmatn-ready">',
        '<body class="pbs-vazirmatn-ready woocommerce-page woocommerce-account">',
    )
    account = """
    <main id="main-content" class="wp-block-group pbs-content-shell">
      <div class="wp-block-post-content">
        <h1 class="wp-block-post-title">حساب کاربری</h1>
        <div class="woocommerce">
          <section class="bsc-otp" dir="rtl">
            <h2>ورود و ثبت‌نام با شماره موبایل</h2>
            <p>مشخصات تحویل فقط هنگام خرید ثبت می‌شود.</p>
            <form>
              <label for="bsc_otp_phone">شماره موبایل</label>
              <input id="bsc_otp_phone" name="phone" type="tel" required>
              <label class="bsc-otp-consent"><input type="checkbox" name="bsc_legal_consent" required> شرایط استفاده</label>
              <button type="button">ارسال کد تأیید</button>
            </form>
          </section>
        </div>
      </div>
    </main>
    """
    return re.sub(r'<main id="main-content".*?</main>', account, html, count=1, flags=re.S)


def logged_in_account_fixture_html() -> str:
    html = refined_fixture_html().replace(
        '<body class="pbs-vazirmatn-ready">',
        '<body class="pbs-vazirmatn-ready logged-in woocommerce-page woocommerce-account bsc-account-dashboard">',
    )
    account = """
    <main id="main-content" class="wp-block-group pbs-content-shell">
      <div class="wp-block-post-content">
        <h1 class="wp-block-post-title">حساب کاربری</h1>
        <div class="woocommerce">
          <nav class="woocommerce-MyAccount-navigation">
            <ul>
              <li class="woocommerce-MyAccount-navigation-link is-active"><a href="#">پیشخوان حساب</a></li>
              <li class="woocommerce-MyAccount-navigation-link"><a href="#">سفارش‌های من</a></li>
              <li class="woocommerce-MyAccount-navigation-link"><a href="#">اطلاعات حساب</a></li>
              <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--customer-logout"><a href="#">خروج امن</a></li>
            </ul>
          </nav>
          <div class="woocommerce-MyAccount-content">
            <p>سلام کاربر</p><p>متن عمومی قدیمی ووکامرس</p>
            <section class="bsc-account-overview">
              <div class="bsc-account-welcome"><span class="bsc-account-avatar">پ</span><div><h2>سلام کاربر</h2><p>سفارش‌ها و اطلاعات حساب خود را مدیریت کنید.</p></div><div class="bsc-account-identifiers"><span class="bsc-account-chip">test@example.test</span><span class="bsc-account-chip">09121234567</span></div></div>
              <div class="bsc-account-metrics"><div class="bsc-account-metric"><strong>۳</strong><span>سفارش ثبت‌شده</span></div><div class="bsc-account-metric"><strong>۲</strong><span>روش ورود</span></div></div>
              <nav class="bsc-account-actions"><a href="#">مشاهده سفارش‌ها</a><a href="#">رفتن به فروشگاه</a></nav>
            </section>
          </div>
        </div>
      </div>
    </main>
    """
    return re.sub(r'<main id="main-content".*?</main>', account, html, count=1, flags=re.S)


class AccountAndNavigationRoundFourTests(unittest.TestCase):
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
            is_mobile=width <= 781,
            has_touch=width <= 781,
        )
        page = context.new_page()
        page.set_content(html, wait_until="load")
        return page, context

    def assert_no_overflow(self, page: Page, width: int) -> None:
        metrics = page.evaluate("() => ({root: document.documentElement.scrollWidth, body: document.body.scrollWidth})")
        self.assertLessEqual(metrics["root"], width + 1, metrics)
        self.assertLessEqual(metrics["body"], width + 1, metrics)

    def test_mobile_navigation_opens_as_a_real_viewport_dialog(self) -> None:
        page, context = self.open_page(navigation_fixture_html(), 412, 915)
        try:
            page.locator(".wp-block-navigation__responsive-container").evaluate("el => el.classList.add('is-menu-open')")
            page.wait_for_function("() => document.body.classList.contains('bsc-nav-open')")
            menu = page.locator(".wp-block-navigation__responsive-container.is-menu-open")
            box = menu.bounding_box()
            self.assertIsNotNone(box)
            self.assertLessEqual(abs(box["x"]), 1)
            self.assertLessEqual(abs(box["y"]), 1)
            self.assertGreaterEqual(box["width"], 411)
            self.assertGreaterEqual(box["height"], 914)
            self.assertEqual("fixed", menu.evaluate("el => getComputedStyle(el).position"))
            self.assertEqual("hidden", page.locator("body").evaluate("el => getComputedStyle(el).overflow"))
            self.assertEqual("none", page.locator(".site-header-shell").evaluate("el => getComputedStyle(el).backdropFilter"))
            self.assertEqual(4, menu.locator(".wp-block-navigation-item__content").count())
            for index in range(4):
                self.assertGreaterEqual(menu.locator(".wp-block-navigation-item__content").nth(index).bounding_box()["height"], 48)
        finally:
            context.close()

    def test_phone_only_registration_is_responsive(self) -> None:
        for width, height in ((1200, 900), (412, 915)):
            with self.subTest(width=width):
                page, context = self.open_page(logged_out_account_fixture_html(), width, height)
                try:
                    self.assert_no_overflow(page, width)
                    self.assertEqual(1, page.locator('form input[name="phone"]').count())
                    self.assertEqual(1, page.locator('form input[name="bsc_legal_consent"]').count())
                    for forbidden in ('full_name', 'email', 'password'):
                        self.assertEqual(0, page.locator(f'form input[name="{forbidden}"]').count())
                    box = page.locator('.bsc-otp').bounding_box()
                    self.assertIsNotNone(box)
                    self.assertGreaterEqual(box["x"], -1)
                    self.assertLessEqual(box["x"] + box["width"], width + 1)
                    self.assertGreaterEqual(page.locator('.bsc-otp button').bounding_box()["height"], 44)
                finally:
                    context.close()

    def test_logged_in_profile_has_aligned_navigation_and_content(self) -> None:
        page, context = self.open_page(logged_in_account_fixture_html(), 1200, 900)
        try:
            self.assert_no_overflow(page, 1200)
            tracks = page.locator("body.logged-in .woocommerce").evaluate("el => getComputedStyle(el).gridTemplateColumns")
            self.assertEqual(2, len([track for track in tracks.split(" ") if track and track != "none"]))
            self.assertEqual("none", page.locator(".woocommerce-MyAccount-navigation").evaluate("el => getComputedStyle(el).float"))
            self.assertEqual("none", page.locator(".woocommerce-MyAccount-content").evaluate("el => getComputedStyle(el).float"))
            nav_box = page.locator(".woocommerce-MyAccount-navigation").bounding_box()
            content_box = page.locator(".woocommerce-MyAccount-content").bounding_box()
            self.assertIsNotNone(nav_box)
            self.assertIsNotNone(content_box)
            self.assertGreater(nav_box["width"], 220)
            self.assertGreater(content_box["width"], 600)
            content_right = content_box["x"] + content_box["width"]
            nav_right = nav_box["x"] + nav_box["width"]
            self.assertTrue(
                content_right <= nav_box["x"] + 2 or nav_right <= content_box["x"] + 2,
                (nav_box, content_box),
            )
            self.assertGreater(nav_box["x"], content_box["x"])
            self.assertEqual("none", page.locator(".woocommerce-MyAccount-content > p:first-of-type").evaluate("el => getComputedStyle(el).display"))
            self.assertEqual(2, page.locator(".bsc-account-actions > a").count())
        finally:
            context.close()

    def test_logged_in_profile_reflows_without_horizontal_scrolling(self) -> None:
        page, context = self.open_page(logged_in_account_fixture_html(), 412, 915)
        try:
            self.assert_no_overflow(page, 412)
            tracks = page.locator("body.logged-in .woocommerce").evaluate("el => getComputedStyle(el).gridTemplateColumns")
            self.assertEqual(1, len([track for track in tracks.split(" ") if track and track != "none"]))
            nav_tracks = page.locator(".woocommerce-MyAccount-navigation ul").evaluate("el => getComputedStyle(el).gridTemplateColumns")
            self.assertEqual(2, len([track for track in nav_tracks.split(" ") if track and track != "none"]))
            self.assertEqual(1, len([track for track in page.locator(".bsc-account-actions").evaluate("el => getComputedStyle(el).gridTemplateColumns").split(" ") if track and track != "none"]))
        finally:
            context.close()


if __name__ == "__main__":
    unittest.main()
