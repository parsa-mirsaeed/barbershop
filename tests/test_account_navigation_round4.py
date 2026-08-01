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
UX_JS = PLUGIN / "assets" / "ux-refinements.js"


def refined_fixture_html() -> str:
    html = rendered_fixture_html()
    styles = LIVE_CSS.read_text(encoding="utf-8") + "\n" + UX_CSS.read_text(encoding="utf-8")
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
          <div id="customer_login" class="u-columns col2-set">
            <div class="u-column1 col-1">
              <h2>ورود</h2>
              <form class="woocommerce-form woocommerce-form-login login">
                <p class="form-row form-row-wide"><label>شماره موبایل یا ایمیل</label><input class="input-text" name="username" type="text"></p>
                <p class="form-row form-row-wide"><label>رمز عبور</label><input class="input-text" type="password"></p>
                <p class="form-row"><button class="button woocommerce-form-login__submit" type="button">ورود</button></p>
                <p class="woocommerce-LostPassword"><a href="#">رمز عبور را فراموش کرده‌اید؟</a></p>
              </form>
            </div>
            <div class="u-column2 col-2">
              <h2>ساخت حساب</h2>
              <form class="woocommerce-form woocommerce-form-register register">
                <div class="bsc-register-intro"><strong>ساخت حساب در کمتر از یک دقیقه</strong><span>فقط اطلاعات لازم دریافت می‌شود.</span></div>
                <p class="form-row form-row-wide"><label>نام و نام خانوادگی</label><input class="input-text" name="full_name" type="text"></p>
                <p class="form-row form-row-wide"><label>نشانی ایمیل</label><input class="input-text" name="email" type="email"></p>
                <p class="form-row form-row-wide"><label>رمز عبور</label><input class="input-text" name="password" type="password"></p>
                <p class="form-row form-row-wide"><label>شماره موبایل</label><input class="input-text" name="billing_phone" type="tel"></p>
                <p class="bsc-register-note">برای ورود از شماره موبایل یا ایمیل همراه رمز عبور استفاده کنید.</p>
                <p class="bsc-registration-consent"><input id="consent" type="checkbox"><label for="consent">شرایط استفاده و سیاست حریم خصوصی را می‌پذیرم.</label></p>
                <p class="form-row"><button class="button woocommerce-form-register__submit" type="button">ساخت حساب</button></p>
              </form>
            </div>
          </div>
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
              <div class="bsc-account-welcome"><span class="bsc-account-avatar">پ</span><div><h2>سلام پارسا</h2><p>سفارش‌ها و اطلاعات حساب خود را مدیریت کنید.</p></div><div class="bsc-account-identifiers"><span class="bsc-account-chip">test@example.test</span><span class="bsc-account-chip">09121234567</span></div></div>
              <div class="bsc-account-metrics"><div class="bsc-account-metric"><strong>۳</strong><span>سفارش ثبت‌شده</span></div><div class="bsc-account-metric"><strong>۲</strong><span>روش ورود</span></div></div>
              <nav class="bsc-account-actions"><a href="#">مشاهده سفارش‌ها</a><a href="#">ویرایش اطلاعات</a><a href="#">رفتن به فروشگاه</a></nav>
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

    def test_registration_is_two_balanced_cards_on_desktop_and_one_column_on_phone(self) -> None:
        for width, height, expected_columns in ((1200, 900, 2), (412, 915, 1)):
            with self.subTest(width=width):
                page, context = self.open_page(logged_out_account_fixture_html(), width, height)
                try:
                    self.assert_no_overflow(page, width)
                    tracks = page.locator("#customer_login").evaluate("el => getComputedStyle(el).gridTemplateColumns")
                    columns = len([track for track in tracks.split(" ") if track and track != "none"])
                    self.assertEqual(expected_columns, columns)
                    self.assertEqual(1, page.locator('form.register input[name="full_name"]').count())
                    self.assertEqual(0, page.locator('form.register input[name="website"]').count())
                    self.assertEqual(1, page.locator(".bsc-registration-consent").count())
                    for selector in ("#customer_login", "form.login", "form.register", "form input", "form button"):
                        for index in range(page.locator(selector).count()):
                            box = page.locator(selector).nth(index).bounding_box()
                            self.assertIsNotNone(box, selector)
                            self.assertGreaterEqual(box["x"], -1, (selector, box))
                            self.assertLessEqual(box["x"] + box["width"], width + 1, (selector, box))
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
            self.assertGreater(nav_box["width"], 220)
            self.assertGreater(content_box["width"], 600)
            self.assertGreater(content_box["x"], nav_box["x"] + nav_box["width"] - 2)
            self.assertEqual("none", page.locator(".woocommerce-MyAccount-content > p:first-of-type").evaluate("el => getComputedStyle(el).display"))
            self.assertEqual(3, page.locator(".bsc-account-actions > a").count())
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
