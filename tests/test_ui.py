from __future__ import annotations

import base64
import os
import shutil
import unittest
from pathlib import Path

from playwright.sync_api import Browser, Page, sync_playwright

ROOT = Path(__file__).resolve().parents[1]
FIXTURE = ROOT / "tests" / "fixtures" / "storefront.html"
VIEWPORTS = (
    ("wcag-minimum", 320, 800),
    ("small-android", 360, 800),
    ("common-iphone", 390, 844),
    ("large-phone", 412, 915),
    ("tablet-portrait", 768, 1024),
    ("tablet-landscape", 1024, 768),
    ("short-laptop", 1280, 720),
    ("desktop", 1440, 900),
)
MAJOR_SECTIONS = ("#home", "#categories", "#products", "#services", "#gallery", "#articles", "#reviews", "#contact")


def complete_section_fixture(html: str) -> str:
    """Add representative production sections missing from the original fixture."""
    image = "../../theme/persian-barbershop/assets/images/product-hero.svg"
    additions: list[str] = []
    if 'id="services"' not in html:
        additions.append("""
        <section id="services" class="pbs-section"><div class="pbs-section-heading alignwide"><div><p class="pbs-kicker">خدمات</p><h2>جزئیات کوچک، تفاوت بزرگ</h2></div><p>مدت، قیمت و توضیحات خدمات.</p></div><div class="wp-block-columns pbs-card-grid alignwide">
        <div class="wp-block-column"><article class="pbs-card"><span class="pbs-number">01</span><h3>اصلاح مو</h3><p>توضیحات خدمت اول</p></article></div><div class="wp-block-column"><article class="pbs-card"><span class="pbs-number">02</span><h3>طراحی ریش</h3><p>توضیحات خدمت دوم</p></article></div><div class="wp-block-column"><article class="pbs-card"><span class="pbs-number">03</span><h3>مشاوره استایل</h3><p>توضیحات خدمت سوم</p></article></div></div></section>
        """)
    if 'id="gallery"' not in html:
        additions.append(f"""
        <section id="gallery" class="pbs-section"><div class="pbs-section-heading alignwide"><div><p class="pbs-kicker">نمونه‌کارها</p><h2>قبل و بعد، با اجازه انتشار</h2></div></div><div class="wp-block-columns pbs-gallery-grid alignwide">
        <div class="wp-block-column"><figure class="pbs-gallery-item"><img src="{image}" alt="نمونه یک"><figcaption>نمونه یک</figcaption></figure></div><div class="wp-block-column"><figure class="pbs-gallery-item"><img src="{image}" alt="نمونه دو"><figcaption>نمونه دو</figcaption></figure></div><div class="wp-block-column"><figure class="pbs-gallery-item"><img src="{image}" alt="نمونه سه"><figcaption>نمونه سه</figcaption></figure></div></div></section>
        """)
    if 'id="articles"' not in html:
        additions.append("""
        <section id="articles" class="pbs-section"><div class="pbs-section-heading alignwide"><div><p class="pbs-kicker">مقاله‌ها</p><h2>راهنمای نگهداری و استایل</h2></div></div><ul class="wp-block-post-template is-layout-grid alignwide">
        <li><h3>راهنمای مراقبت روزانه مو</h3><p>متن نمونه مقاله برای بررسی شکست خطوط فارسی.</p></li><li><h3>انتخاب محصول مناسب ریش</h3><p>متن نمونه مقاله برای بررسی کارت‌ها.</p></li><li><h3>نکات نگهداری ابزار</h3><p>متن نمونه مقاله برای بررسی عرض‌های مختلف.</p></li></ul></section>
        """)
    if 'id="reviews"' not in html:
        additions.append("""
        <section id="reviews" class="pbs-section"><div class="pbs-section-heading alignwide"><div><p class="pbs-kicker">نظر مشتریان</p><h2>اعتماد، از تجربه واقعی می‌آید</h2></div></div><div class="wp-block-columns pbs-card-grid alignwide">
        <div class="wp-block-column"><blockquote class="pbs-card pbs-review-card"><p>نظر نمونه مشتری اول</p><cite>مشتری اول</cite></blockquote></div><div class="wp-block-column"><blockquote class="pbs-card pbs-review-card"><p>نظر نمونه مشتری دوم</p><cite>مشتری دوم</cite></blockquote></div><div class="wp-block-column"><blockquote class="pbs-card pbs-review-card"><p>نظر نمونه مشتری سوم</p><cite>مشتری سوم</cite></blockquote></div></div></section>
        """)
    if 'id="contact"' not in html:
        additions.append(f"""
        <section id="contact" class="pbs-section"><div class="pbs-section-heading alignwide"><div><p class="pbs-kicker">تماس</p><h2>برای زمان مناسب تماس بگیرید</h2></div></div><div class="wp-block-columns pbs-contact-panel alignwide">
        <div class="wp-block-column"><div class="pbs-contact-details"><h3>ارتباط مستقیم با فروشگاه</h3><ul class="pbs-contact-list"><li>تلفن: ۰۰۰۰۰۰۰۰۰۰</li><li>ساعت پاسخ‌گویی: ۹ تا ۱۸</li><li>نشانی: نشانی نمونه</li></ul><div class="wp-block-button"><a class="wp-block-button__link" href="tel:+000000000000">تماس تلفنی</a></div></div></div><div class="wp-block-column"><figure><img src="{image}" alt="تصویر بخش تماس"></figure></div></div></section>
        """)
    return html.replace("</main>", "".join(additions) + "</main>") if additions else html


def rendered_fixture_html() -> str:
    html = complete_section_fixture(FIXTURE.read_text(encoding="utf-8"))
    for href, path in {
        "../../theme/persian-barbershop/style.css": ROOT / "theme" / "persian-barbershop" / "style.css",
        "../../theme/persian-barbershop/assets/css/woocommerce.css": ROOT / "theme" / "persian-barbershop" / "assets" / "css" / "woocommerce.css",
        "../../plugin/barbershop-core/assets/frontend.css": ROOT / "plugin" / "barbershop-core" / "assets" / "frontend.css",
    }.items():
        html = html.replace(f'<link rel="stylesheet" href="{href}">', f'<style>{path.read_text(encoding="utf-8")}</style>')
    runtime_css = (ROOT / "plugin" / "barbershop-core" / "assets" / "runtime-storefront.css").read_text(encoding="utf-8")
    touch_css = (ROOT / "plugin" / "barbershop-core" / "assets" / "touch-targets.css").read_text(encoding="utf-8")
    html = html.replace("</head>", f"<style>{runtime_css}\n{touch_css}</style></head>")
    for src, path in {
        "../../theme/persian-barbershop/assets/js/site.js": ROOT / "theme" / "persian-barbershop" / "assets" / "js" / "site.js",
        "../../plugin/barbershop-core/assets/frontend.js": ROOT / "plugin" / "barbershop-core" / "assets" / "frontend.js",
    }.items():
        html = html.replace(f'<script src="{src}"></script>', f'<script>{path.read_text(encoding="utf-8")}</script>')
    for src, path in {
        "../../theme/persian-barbershop/assets/images/brand-mark.svg": ROOT / "theme" / "persian-barbershop" / "assets" / "images" / "brand-mark.svg",
        "../../theme/persian-barbershop/assets/images/product-hero.svg": ROOT / "theme" / "persian-barbershop" / "assets" / "images" / "product-hero.svg",
    }.items():
        html = html.replace(src, f"data:image/svg+xml;base64,{base64.b64encode(path.read_bytes()).decode('ascii')}")
    return html


class StorefrontUITests(unittest.TestCase):
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

    def open_page(self, width: int, height: int, reduced_motion: str = "no-preference") -> tuple[Page, object]:
        mobile = width <= 781
        context = self.browser.new_context(viewport={"width": width, "height": height}, screen={"width": width, "height": height}, locale="fa-IR", reduced_motion=reduced_motion, is_mobile=mobile, has_touch=mobile)
        page = context.new_page()
        page.set_content(rendered_fixture_html(), wait_until="load")
        return page, context

    def assert_no_page_overflow(self, page: Page, name: str) -> None:
        metrics = page.evaluate("() => ({viewport: document.documentElement.clientWidth, document: document.documentElement.scrollWidth, body: document.body.scrollWidth})")
        self.assertLessEqual(metrics["document"], metrics["viewport"] + 1, f"document overflow at {name}: {metrics}")
        self.assertLessEqual(metrics["body"], metrics["viewport"] + 1, f"body overflow at {name}: {metrics}")

    def assert_major_sections_fit(self, page: Page, width: int, name: str) -> None:
        for selector in MAJOR_SECTIONS:
            locator = page.locator(selector)
            self.assertEqual(1, locator.count(), f"missing {selector} at {name}")
            box = locator.bounding_box()
            self.assertIsNotNone(box)
            self.assertGreater(box["width"], 0)
            self.assertGreaterEqual(box["x"], -1.5, f"{selector} starts outside viewport at {name}: {box}")
            self.assertLessEqual(box["x"] + box["width"], width + 1.5, f"{selector} exceeds viewport at {name}: {box}")

    @staticmethod
    def grid_column_count(page: Page, selector: str) -> int:
        tracks = page.locator(selector).evaluate("el => getComputedStyle(el).gridTemplateColumns")
        return len([track for track in tracks.split(" ") if track and track != "none"])

    def test_desktop_visual_structure_and_accessibility(self) -> None:
        page, context = self.open_page(1200, 900)
        try:
            self.assertEqual("rtl", page.locator("html").get_attribute("dir"))
            self.assertIn("Vazirmatn", page.locator("body").evaluate("el => getComputedStyle(el).fontFamily"))
            self.assertEqual(1, page.locator("h1").count())
            self.assertGreaterEqual(page.locator(".bsc-category-card").count(), 4)
            self.assertGreaterEqual(page.locator(".woocommerce li.product").count(), 4)
            self.assert_no_page_overflow(page, "desktop-1200")
            for selector in (".wp-block-button__link", ".bsc-cart-link", ".bsc-account-link"):
                box = page.locator(selector).first.bounding_box()
                self.assertIsNotNone(box)
                self.assertGreaterEqual(box["height"], 44)
            page.locator(".wp-block-button__link").first.focus()
            self.assertNotEqual("none", page.locator(".wp-block-button__link").first.evaluate("el => getComputedStyle(el).outlineStyle"))
            self.assertEqual("none", page.locator(".bsc-mobile-dock").evaluate("el => getComputedStyle(el).display"))
        finally:
            context.close()

    def test_complete_responsive_viewport_matrix(self) -> None:
        for name, width, height in VIEWPORTS:
            with self.subTest(viewport=name):
                page, context = self.open_page(width, height)
                try:
                    self.assert_no_page_overflow(page, name)
                    self.assert_major_sections_fit(page, width, name)
                    display = page.locator(".bsc-mobile-dock").evaluate("el => getComputedStyle(el).display")
                    self.assertNotEqual("none", display) if width <= 781 else self.assertEqual("none", display)
                finally:
                    context.close()

    def test_mobile_navigation_targets_and_grid_reflow(self) -> None:
        page, context = self.open_page(390, 844)
        try:
            self.assert_no_page_overflow(page, "mobile-390")
            self.assertEqual(4, page.locator(".bsc-mobile-dock > a").count())
            for index in range(4):
                box = page.locator(".bsc-mobile-dock > a").nth(index).bounding_box()
                self.assertIsNotNone(box)
                self.assertGreaterEqual(box["height"], 44)
                self.assertGreaterEqual(box["width"], 44)
            self.assertEqual("none", page.locator(".pbs-header-actions").evaluate("el => getComputedStyle(el).display"))
            self.assertEqual(1, self.grid_column_count(page, ".bsc-category-grid"))
            self.assertEqual(1, self.grid_column_count(page, ".woocommerce ul.products"))
            for selector in (".bsc-category-card > a", ".woocommerce li.product .button"):
                for index in range(min(4, page.locator(selector).count())):
                    self.assertGreaterEqual(page.locator(selector).nth(index).bounding_box()["height"], 44)
        finally:
            context.close()

    def test_tablet_uses_readable_two_column_discovery(self) -> None:
        page, context = self.open_page(768, 1024)
        try:
            self.assert_no_page_overflow(page, "tablet-768")
            self.assertEqual(2, self.grid_column_count(page, ".bsc-category-grid"))
            self.assertEqual(2, self.grid_column_count(page, ".woocommerce ul.products"))
            self.assertEqual("column", page.locator(".pbs-contact-panel").evaluate("el => getComputedStyle(el).flexDirection"))
        finally:
            context.close()

    def test_wcag_320_reflow_and_text_spacing_override(self) -> None:
        page, context = self.open_page(320, 800)
        try:
            page.add_style_tag(content="p,li,label,input,button,a{line-height:1.5!important;letter-spacing:.12em!important;word-spacing:.16em!important}p{margin-bottom:2em!important}")
            self.assert_no_page_overflow(page, "wcag-320-text-spacing")
            self.assert_major_sections_fit(page, 320, "wcag-320-text-spacing")
            self.assertEqual(1, self.grid_column_count(page, ".bsc-category-grid"))
            self.assertEqual(1, self.grid_column_count(page, ".woocommerce ul.products"))
        finally:
            context.close()

    def test_portrait_and_landscape_orientations(self) -> None:
        for name, width, height in (("portrait", 390, 844), ("landscape", 844, 390)):
            with self.subTest(orientation=name):
                page, context = self.open_page(width, height)
                try:
                    self.assert_no_page_overflow(page, name)
                    self.assertEqual(0, page.locator("style, link").evaluate_all("els => els.filter(el => (el.textContent || '').includes('orientation: portrait') || (el.textContent || '').includes('orientation: landscape')).length"))
                finally:
                    context.close()

    def test_mobile_focus_is_not_obscured_by_bottom_dock(self) -> None:
        page, context = self.open_page(390, 844)
        try:
            target = page.locator("#contact .wp-block-button__link").first
            page.evaluate("document.documentElement.style.scrollBehavior = 'auto'")
            target.scroll_into_view_if_needed()
            target.focus()
            page.wait_for_timeout(50)
            target_box = target.bounding_box()
            dock_box = page.locator(".bsc-mobile-dock").bounding_box()
            self.assertIsNotNone(target_box)
            self.assertIsNotNone(dock_box)
            self.assertGreater(target_box["y"] + target_box["height"], 0)
            self.assertLess(target_box["y"], dock_box["y"], "focused action is completely hidden by the dock")
            self.assertTrue(target.evaluate("el => document.activeElement === el"))
        finally:
            context.close()

    def test_reduced_motion_is_respected(self) -> None:
        page, context = self.open_page(1200, 900, reduced_motion="reduce")
        try:
            duration = page.locator(".bsc-category-card > a").first.evaluate("el => Math.max(...getComputedStyle(el).transitionDuration.split(',').map(v => parseFloat(v) || 0))")
            self.assertLessEqual(duration, 0.01)
        finally:
            context.close()


if __name__ == "__main__":
    unittest.main()
