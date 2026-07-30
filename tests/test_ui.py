from __future__ import annotations

import base64
import os
import shutil
import unittest
from pathlib import Path

from playwright.sync_api import Browser, Page, sync_playwright

ROOT = Path(__file__).resolve().parents[1]
FIXTURE = ROOT / "tests" / "fixtures" / "storefront.html"


def rendered_fixture_html() -> str:
    html = FIXTURE.read_text(encoding="utf-8")
    assets = {
        "../../theme/persian-barbershop/style.css": ROOT / "theme" / "persian-barbershop" / "style.css",
        "../../theme/persian-barbershop/assets/css/woocommerce.css": ROOT / "theme" / "persian-barbershop" / "assets" / "css" / "woocommerce.css",
        "../../plugin/barbershop-core/assets/frontend.css": ROOT / "plugin" / "barbershop-core" / "assets" / "frontend.css",
    }
    for href, path in assets.items():
        html = html.replace(f'<link rel="stylesheet" href="{href}">', f'<style>{path.read_text(encoding="utf-8")}</style>')
    scripts = {
        "../../theme/persian-barbershop/assets/js/site.js": ROOT / "theme" / "persian-barbershop" / "assets" / "js" / "site.js",
        "../../plugin/barbershop-core/assets/frontend.js": ROOT / "plugin" / "barbershop-core" / "assets" / "frontend.js",
    }
    for src, path in scripts.items():
        html = html.replace(f'<script src="{src}"></script>', f'<script>{path.read_text(encoding="utf-8")}</script>')
    for src, path in {
        "../../theme/persian-barbershop/assets/images/brand-mark.svg": ROOT / "theme" / "persian-barbershop" / "assets" / "images" / "brand-mark.svg",
        "../../theme/persian-barbershop/assets/images/product-hero.svg": ROOT / "theme" / "persian-barbershop" / "assets" / "images" / "product-hero.svg",
    }.items():
        encoded = base64.b64encode(path.read_bytes()).decode("ascii")
        html = html.replace(src, f"data:image/svg+xml;base64,{encoded}")
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
        context = self.browser.new_context(viewport={"width": width, "height": height}, locale="fa-IR", reduced_motion=reduced_motion)
        page = context.new_page()
        page.set_content(rendered_fixture_html(), wait_until="load")
        return page, context

    def test_desktop_visual_structure_and_accessibility(self) -> None:
        page, context = self.open_page(1200, 900)
        try:
            self.assertEqual("rtl", page.locator("html").get_attribute("dir"))
            self.assertIn("Vazirmatn", page.locator("body").evaluate("el => getComputedStyle(el).fontFamily"))
            self.assertEqual(1, page.locator("h1").count())
            self.assertGreaterEqual(page.locator(".bsc-category-card").count(), 4)
            self.assertGreaterEqual(page.locator(".woocommerce li.product").count(), 4)
            self.assertFalse(page.evaluate("document.documentElement.scrollWidth > document.documentElement.clientWidth + 1"))
            for selector in (".wp-block-button__link", ".bsc-cart-link", ".bsc-account-link"):
                box = page.locator(selector).first.bounding_box()
                self.assertIsNotNone(box)
                self.assertGreaterEqual(box["height"], 44)
            page.locator(".wp-block-button__link").first.focus()
            outline = page.locator(".wp-block-button__link").first.evaluate("el => getComputedStyle(el).outlineStyle")
            self.assertNotEqual("none", outline)
            self.assertEqual("none", page.locator(".bsc-mobile-dock").evaluate("el => getComputedStyle(el).display"))
        finally:
            context.close()

    def test_mobile_navigation_and_no_overflow(self) -> None:
        page, context = self.open_page(390, 844)
        try:
            self.assertFalse(page.evaluate("document.documentElement.scrollWidth > document.documentElement.clientWidth + 1"))
            self.assertNotEqual("none", page.locator(".bsc-mobile-dock").evaluate("el => getComputedStyle(el).display"))
            self.assertEqual(4, page.locator(".bsc-mobile-dock > a").count())
            for index in range(4):
                box = page.locator(".bsc-mobile-dock > a").nth(index).bounding_box()
                self.assertIsNotNone(box)
                self.assertGreaterEqual(box["height"], 44)
            self.assertEqual("none", page.locator(".pbs-header-actions").evaluate("el => getComputedStyle(el).display"))
            hero_box = page.locator(".pbs-hero").bounding_box()
            self.assertIsNotNone(hero_box)
            self.assertGreater(hero_box["height"], 700)
        finally:
            context.close()

    def test_reduced_motion_is_respected(self) -> None:
        page, context = self.open_page(1200, 900, reduced_motion="reduce")
        try:
            duration = page.locator(".bsc-category-card > a").first.evaluate(
                "el => Math.max(...getComputedStyle(el).transitionDuration.split(',').map(v => parseFloat(v) || 0))"
            )
            self.assertLessEqual(duration, 0.01)
        finally:
            context.close()


if __name__ == "__main__":
    unittest.main()
