#!/usr/bin/env python3
"""Validate storefront structure, privacy defaults, design tokens, and installer coverage."""
from __future__ import annotations

import json
import re
import struct
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "theme" / "persian-barbershop"
PLUGIN = ROOT / "plugin" / "barbershop-core"
errors: list[str] = []

required = [
    ROOT / ".env.example", ROOT / ".env.production.example", ROOT / "compose.yaml",
    ROOT / "compose.production.yaml", ROOT / "deploy" / "Caddyfile",
    ROOT / "requirements-test.txt", ROOT / "docs" / "QUALITY-STANDARDS.md",
    ROOT / "docs" / "SECURITY-HARDENING.fa.md", ROOT / "tests" / "test_backend.php",
    ROOT / "tests" / "test_ui.py", ROOT / "tests" / "fixtures" / "storefront.html",
    ROOT / "tools" / "install.sh", ROOT / "tools" / "deploy.sh", ROOT / "tools" / "build-release.sh",
    THEME / "style.css", THEME / "theme.json", THEME / "functions.php", THEME / "screenshot.png",
    THEME / "assets" / "js" / "site.js", THEME / "assets" / "css" / "woocommerce.css",
    THEME / "assets" / "images" / "brand-mark.svg", THEME / "assets" / "images" / "product-hero.svg",
    THEME / "parts" / "header.html", THEME / "templates" / "front-page.html",
    PLUGIN / "barbershop-core.php", PLUGIN / "includes" / "account.php",
    PLUGIN / "includes" / "categories.php", PLUGIN / "includes" / "cart.php",
    PLUGIN / "includes" / "setup.php", PLUGIN / "includes" / "security.php",
    PLUGIN / "includes" / "woocommerce.php", PLUGIN / "includes" / "products.php",
    PLUGIN / "assets" / "admin.css", PLUGIN / "assets" / "admin.js",
    PLUGIN / "assets" / "frontend.css", PLUGIN / "assets" / "frontend.js",
]
for path in required:
    if not path.is_file():
        errors.append(f"Missing required file: {path.relative_to(ROOT)}")

try:
    theme_json = json.loads((THEME / "theme.json").read_text(encoding="utf-8"))
except Exception as exc:  # noqa: BLE001
    theme_json = {}
    errors.append(f"Invalid theme.json: {exc}")
palette = {item.get("slug"): str(item.get("color", "")).upper() for item in theme_json.get("settings", {}).get("color", {}).get("palette", [])}
for slug, color in {"navy": "#071A3D", "emerald": "#0E8F6A", "white": "#FFFFFF"}.items():
    if palette.get(slug) != color:
        errors.append(f"Required design color {slug} must be {color}.")

text_files = [
    path for path in ROOT.rglob("*")
    if path.is_file() and path != Path(__file__).resolve()
    and path.suffix.lower() in {".php", ".html", ".css", ".js", ".json", ".md", ".txt", ".yaml", ".yml", ".py", ".pot", ".example", ".sh"}
]
all_text = "\n".join(path.read_text(encoding="utf-8", errors="ignore") for path in text_files)
for forbidden in ("MONTIX", "Alireza", "علیرضا", "Armin", "Mirsaeid", "Mirsaeed", "Ebrahimi", "Basalam", "basalam.com", "local-wordpress-password"):
    if forbidden.casefold() in all_text.casefold():
        errors.append(f"Private, trademarked, or source-specific term found: {forbidden}")

compose = (ROOT / "compose.yaml").read_text(encoding="utf-8")
for token in ("127.0.0.1:${WP_PORT:-8080}:80", "${DB_PASSWORD:?Set DB_PASSWORD in .env}", "wordpress:cli-php8.2", "./theme/persian-barbershop", "./plugin/barbershop-core"):
    if token not in compose:
        errors.append(f"Local Docker setup token missing: {token}")
production = (ROOT / "compose.production.yaml").read_text(encoding="utf-8")
for token in ("SITE_DOMAIN", "FORCE_SSL_ADMIN", "DISALLOW_FILE_EDIT", "WP_DEBUG_DISPLAY", "caddy:2.9-alpine", "80:80", "443:443"):
    if token not in production:
        errors.append(f"Production Docker setup token missing: {token}")

account = (PLUGIN / "includes" / "account.php").read_text(encoding="utf-8")
for token in ("woocommerce_register_form_start", "first_name", "last_name", "billing_phone", "woocommerce_created_customer", "edit-address", "woocommerce_account_menu_items", "bsc-registration-trap"):
    if token not in account:
        errors.append(f"Minimal account implementation token missing: {token}")
if "(اختیاری)" not in account:
    errors.append("Phone field is not marked optional.")

setup = (PLUGIN / "includes" / "setup.php").read_text(encoding="utf-8")
for label in ("حالت‌دهنده ریش و مو", "واکس و پماد مو", "تافت و اسپری مو", "مراقبت مو", "شامپو", "نرم‌کننده", "ماسک و ویتامینه مو", "مراقبت پوست", "اسکراب", "شوینده", "تونر", "اصلاح و ابزار حرفه‌ای"):
    if label not in setup:
        errors.append(f"Seeded product category missing: {label}")
for option in ("woocommerce_registration_generate_username", "woocommerce_registration_generate_password", "bsc_install_vazirmatn_font", "barbershop-fonts"):
    if option not in setup:
        errors.append(f"Store setup token missing: {option}")

categories = (PLUGIN / "includes" / "categories.php").read_text(encoding="utf-8")
for token in ("product_cat_add_form_fields", "product_cat_edit_form_fields", "_bsc_category_icon_id", "bsc_product_categories", "manage_product_terms", 'role="list"'):
    if token not in categories:
        errors.append(f"Editable category icon token missing: {token}")
frontend_css = (PLUGIN / "assets" / "frontend.css").read_text(encoding="utf-8")
for token in ("height:7rem", "width:7rem", "bsc-mobile-dock", "min-height:44px", "prefers-reduced-motion"):
    if token not in frontend_css:
        errors.append(f"Storefront CSS token missing: {token}")

header = (THEME / "parts" / "header.html").read_text(encoding="utf-8")
for token in ("wp:site-logo", "[bsc_cart_link]", "[bsc_account_link]", "#categories", "#products"):
    if token not in header:
        errors.append(f"Header navigation/access token missing: {token}")
theme_functions = (THEME / "functions.php").read_text(encoding="utf-8")
for token in ("pbs_default_site_logo", "brand-mark.svg", "render_block_core/site-logo", "Vazirmatn", "preload"):
    if token not in theme_functions:
        errors.append(f"Theme implementation token missing: {token}")
cart = (PLUGIN / "includes" / "cart.php").read_text(encoding="utf-8")
for token in ("wc_get_cart_url", "woocommerce_add_to_cart_fragments", "bsc-mobile-dock", "aria-label"):
    if token not in cart:
        errors.append(f"Persistent cart implementation token missing: {token}")
security = (PLUGIN / "includes" / "security.php").read_text(encoding="utf-8")
for token in ("X-Content-Type-Options", "X-Frame-Options", "rest_pre_dispatch", "nocache_headers", "xmlrpc_enabled"):
    if token not in security:
        errors.append(f"Security hardening token missing: {token}")

for script in (ROOT / "tools" / "install.sh", ROOT / "tools" / "deploy.sh"):
    content = script.read_text(encoding="utf-8")
    for token in ("install_plugin woocommerce", "install_plugin wordfence", "wp theme activate persian-barbershop", "wp plugin activate barbershop-core", "wp bsc setup", "wp bsc font install"):
        if token not in content:
            errors.append(f"{script.name} is missing installer step: {token}")

workflow = (ROOT / ".github" / "workflows" / "quality.yml").read_text(encoding="utf-8")
for token in ("playwright install", "tests/test_backend.php", "docker-smoke", "plugin is-active wordfence", "bsc font install"):
    if token not in workflow:
        errors.append(f"Quality workflow token missing: {token}")
if "Upload source snapshot" in workflow:
    errors.append("Temporary source artifact publishing must not remain in the final workflow.")


def png_size(path: Path) -> tuple[int, int] | None:
    data = path.read_bytes()[:24]
    if len(data) < 24 or data[:8] != b"\x89PNG\r\n\x1a\n":
        return None
    return struct.unpack(">II", data[16:24])

if png_size(THEME / "screenshot.png") != (1200, 900):
    errors.append("Theme screenshot must be a 1200x900 PNG.")

placeholder_count = len(re.findall(r"\[[^\]\n]{2,80}\]", all_text))
if placeholder_count < 12:
    errors.append("Expected generalized bracketed placeholders were not found.")

block_pattern = re.compile(r"<!--\s+(?P<close>/)?wp:(?P<name>[^\s>]+)(?:\s+(?P<attrs>\{.*?\}))?\s*(?P<self>/)?-->", re.DOTALL)
block_files = list((THEME / "templates").glob("*.html")) + list((THEME / "parts").glob("*.html")) + list((THEME / "inc" / "pattern-content").glob("*.php"))
for path in block_files:
    stack: list[str] = []
    text = path.read_text(encoding="utf-8")
    for match in block_pattern.finditer(text):
        name, attrs = match.group("name"), match.group("attrs")
        if attrs:
            try:
                json.loads(attrs)
            except json.JSONDecodeError as exc:
                errors.append(f"Invalid block JSON in {path.relative_to(ROOT)} ({name}): {exc}")
        if match.group("close"):
            if not stack or stack[-1] != name:
                errors.append(f"Unbalanced block close in {path.relative_to(ROOT)}: {name}")
            else:
                stack.pop()
        elif not match.group("self"):
            stack.append(name)
    if stack:
        errors.append(f"Unclosed blocks in {path.relative_to(ROOT)}: {', '.join(stack)}")

if errors:
    print("Validation failed:")
    for error in errors:
        print(f"- {error}")
    sys.exit(1)
print(f"Validation passed: design, accessibility, accounts, cart, categories, security, installers, CI, and {placeholder_count} placeholders verified.")
