#!/usr/bin/env python3
"""Capture the tested storefront fixture as the WordPress theme screenshot."""
from __future__ import annotations
import base64
import os
import shutil
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
fixture = ROOT / "tests" / "fixtures" / "storefront.html"
output = ROOT / "theme" / "persian-barbershop" / "screenshot.png"
html = fixture.read_text(encoding="utf-8")
for href, path in {
    "../../theme/persian-barbershop/style.css": ROOT / "theme" / "persian-barbershop" / "style.css",
    "../../theme/persian-barbershop/assets/css/woocommerce.css": ROOT / "theme" / "persian-barbershop" / "assets" / "css" / "woocommerce.css",
    "../../plugin/barbershop-core/assets/frontend.css": ROOT / "plugin" / "barbershop-core" / "assets" / "frontend.css",
}.items():
    html = html.replace(f'<link rel="stylesheet" href="{href}">', f'<style>{path.read_text(encoding="utf-8")}</style>')
for src, path in {
    "../../theme/persian-barbershop/assets/js/site.js": ROOT / "theme" / "persian-barbershop" / "assets" / "js" / "site.js",
    "../../plugin/barbershop-core/assets/frontend.js": ROOT / "plugin" / "barbershop-core" / "assets" / "frontend.js",
}.items():
    html = html.replace(f'<script src="{src}"></script>', f'<script>{path.read_text(encoding="utf-8")}</script>')
for src, path in {
    "../../theme/persian-barbershop/assets/images/brand-mark.svg": ROOT / "theme" / "persian-barbershop" / "assets" / "images" / "brand-mark.svg",
    "../../theme/persian-barbershop/assets/images/product-hero.svg": ROOT / "theme" / "persian-barbershop" / "assets" / "images" / "product-hero.svg",
}.items():
    html = html.replace(src, "data:image/svg+xml;base64," + base64.b64encode(path.read_bytes()).decode("ascii"))
with sync_playwright() as playwright:
    executable = os.environ.get("PLAYWRIGHT_CHROMIUM_PATH") or shutil.which("chromium") or shutil.which("chromium-browser")
    kwargs = {"headless": True}
    if executable:
        kwargs["executable_path"] = executable
    browser = playwright.chromium.launch(**kwargs)
    page = browser.new_page(viewport={"width": 1200, "height": 900}, device_scale_factor=1, locale="fa-IR")
    page.set_content(html, wait_until="load")
    page.screenshot(path=str(output), full_page=False)
    browser.close()
print(output)
