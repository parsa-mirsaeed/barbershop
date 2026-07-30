from __future__ import annotations

import json
import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "theme" / "persian-barbershop"
PLUGIN = ROOT / "plugin" / "barbershop-core"


class StorefrontRepositoryTests(unittest.TestCase):
    def test_exact_reference_palette_and_true_white(self) -> None:
        data = json.loads((THEME / "theme.json").read_text())
        palette = {item["slug"]: item["color"].upper() for item in data["settings"]["color"]["palette"]}
        self.assertEqual("#071A3D", palette["navy"])
        self.assertEqual("#0E8F6A", palette["emerald"])
        self.assertEqual("#FFFFFF", palette["white"])
        self.assertIn("--pbs-mist: #f6f9fc", (THEME / "style.css").read_text())

    def test_vazirmatn_is_self_hosted_and_installed(self) -> None:
        functions = (THEME / "functions.php").read_text()
        setup = (PLUGIN / "includes" / "setup.php").read_text()
        installer = (ROOT / "tools" / "install.sh").read_text()
        self.assertIn('font-family:"Vazirmatn"', functions)
        self.assertIn("barbershop-fonts/Vazirmatn-Variable.woff2", functions)
        self.assertIn("bsc_install_vazirmatn_font", setup)
        self.assertIn("wp bsc font install", installer)
        self.assertNotIn("fonts.googleapis.com", "\n".join(p.read_text(errors="ignore") for base in (THEME, PLUGIN) for p in base.rglob("*") if p.is_file()))

    def test_registration_is_short_and_phone_optional(self) -> None:
        source = (PLUGIN / "includes" / "account.php").read_text()
        self.assertIn("نام خانوادگی", source)
        self.assertIn("(اختیاری)", source)
        self.assertIn("unset( $items['edit-address'] )", source)
        self.assertIn("bsc-registration-trap", source)
        setup = (PLUGIN / "includes" / "setup.php").read_text()
        self.assertIn("woocommerce_registration_generate_username", setup)
        self.assertIn("woocommerce_registration_generate_password", setup)

    def test_cart_is_obvious_on_desktop_and_mobile(self) -> None:
        header = (THEME / "parts" / "header.html").read_text()
        cart = (PLUGIN / "includes" / "cart.php").read_text()
        css = (PLUGIN / "assets" / "frontend.css").read_text()
        self.assertIn("[bsc_cart_link]", header)
        self.assertIn("woocommerce_add_to_cart_fragments", cart)
        self.assertIn("bsc-mobile-dock", cart)
        self.assertRegex(css, re.compile(r"@media\(max-width:781px\).*bsc-mobile-dock", re.S))

    def test_requested_product_categories_are_seeded_and_editable(self) -> None:
        setup = (PLUGIN / "includes" / "setup.php").read_text()
        for name in (
            "حالت‌دهنده ریش و مو", "واکس و پماد مو", "تافت و اسپری مو",
            "مراقبت مو", "شامپو", "نرم‌کننده", "ماسک و ویتامینه مو",
            "مراقبت پوست", "اسکراب", "شوینده", "تونر",
            "اصلاح و ابزار حرفه‌ای", "ماشین اصلاح", "شانه و برس",
        ):
            self.assertIn(name, setup)

    def test_category_icons_are_editable_large_and_accessible(self) -> None:
        php = (PLUGIN / "includes" / "categories.php").read_text()
        css = (PLUGIN / "assets" / "frontend.css").read_text()
        self.assertIn("product_cat_add_form_fields", php)
        self.assertIn("product_cat_edit_form_fields", php)
        self.assertIn("_bsc_category_icon_id", php)
        self.assertIn("manage_product_terms", php)
        self.assertIn('role="list"', php)
        self.assertIn("height:7rem", css)
        self.assertIn("width:7rem", css)

    def test_logo_is_present_and_replaceable(self) -> None:
        self.assertTrue((THEME / "assets" / "images" / "brand-mark.svg").is_file())
        header = (THEME / "parts" / "header.html").read_text()
        functions = (THEME / "functions.php").read_text()
        self.assertIn("wp:site-logo", header)
        self.assertIn("pbs_default_site_logo", functions)
        self.assertIn("render_block_core/site-logo", functions)

    def test_wordfence_and_layered_hardening_are_installed(self) -> None:
        install = (ROOT / "tools" / "install.sh").read_text()
        deploy = (ROOT / "tools" / "deploy.sh").read_text()
        security = (PLUGIN / "includes" / "security.php").read_text()
        caddy = (ROOT / "deploy" / "Caddyfile").read_text()
        for script in (install, deploy):
            self.assertIn("install_plugin wordfence", script)
            self.assertIn("wp plugin auto-updates enable woocommerce wordfence", script)
        for token in ("X-Content-Type-Options", "X-Frame-Options", "Referrer-Policy", "Permissions-Policy", "rest_pre_dispatch", "nocache_headers"):
            self.assertIn(token, security)
        self.assertIn("Strict-Transport-Security", caddy)
        self.assertIn("X-Permitted-Cross-Domain-Policies", caddy)

    def test_woocommerce_ui_is_farsi_and_touch_friendly(self) -> None:
        php = (PLUGIN / "includes" / "woocommerce.php").read_text()
        css = (THEME / "assets" / "css" / "woocommerce.css").read_text()
        for label in ("افزودن به سبد خرید", "ثبت سفارش و پرداخت", "اطلاعات سفارش‌دهنده", "دیدگاه خریداران"):
            self.assertIn(label, php)
        self.assertIn("min-height: 3rem", css)
        self.assertIn("min-height: 3.2rem", css)
        self.assertIn("woocommerce-checkout", css)
        self.assertIn("woocommerce-MyAccount", css)

    def test_admin_dashboard_and_product_editor_are_improved(self) -> None:
        settings = (PLUGIN / "includes" / "settings.php").read_text()
        products = (PLUGIN / "includes" / "products.php").read_text()
        admin_css = (PLUGIN / "assets" / "admin.css").read_text()
        for label in ("مرکز کنترل فروشگاه", "Wordfence", "محصولات و موجودی", "سفارش‌ها", "تنظیمات سفارش"):
            self.assertIn(label, settings)
        self.assertIn("چک‌لیست انتشار حرفه‌ای", products)
        self.assertIn(".bsc-stat-grid", admin_css)
        self.assertIn("min-height:42px", admin_css)

    def test_accessibility_and_motion_tokens_exist(self) -> None:
        css = (THEME / "style.css").read_text()
        frontend = (PLUGIN / "assets" / "frontend.css").read_text()
        self.assertIn("min-height: 44px", css)
        self.assertIn(":focus-visible", css)
        self.assertIn("prefers-reduced-motion", css)
        self.assertIn("min-height:44px", frontend)
        self.assertIn("aria-label", (PLUGIN / "includes" / "cart.php").read_text())

    def test_docker_defaults_do_not_publish_database(self) -> None:
        compose = (ROOT / "compose.yaml").read_text()
        database_section = compose.split("  wordpress:", 1)[0]
        self.assertNotIn("ports:", database_section)
        self.assertIn("127.0.0.1:${WP_PORT:-8080}:80", compose)
        self.assertIn("${DB_PASSWORD:?", compose)
        production = (ROOT / "compose.production.yaml").read_text()
        self.assertIn("DISALLOW_FILE_EDIT", production)
        self.assertIn("FORCE_SSL_ADMIN", production)
        self.assertIn("WP_DEBUG_DISPLAY", production)

    def test_local_installer_handles_occupied_ports(self) -> None:
        installer = (ROOT / "tools" / "install.sh").read_text()
        self.assertIn("Port $requested_port is already occupied", installer)
        self.assertIn("Trying local development port $fallback_port instead", installer)
        self.assertIn("set_env_value WP_PORT", installer)
        self.assertIn("Production does not use this fallback", installer)

    def test_quality_workflow_runs_ui_backend_and_runtime_checks(self) -> None:
        workflow = (ROOT / ".github" / "workflows" / "quality.yml").read_text()
        for token in ("playwright install", "tests/test_backend.php", "python3 -m unittest discover", "docker-smoke", "plugin is-active wordfence", "bsc font install"):
            self.assertIn(token, workflow)
        self.assertNotIn("Upload source snapshot", workflow)

    def test_documented_quality_standards_and_security_limits(self) -> None:
        quality = (ROOT / "docs" / "QUALITY-STANDARDS.md").read_text()
        security = (ROOT / "docs" / "SECURITY-HARDENING.fa.md").read_text()
        for token in ("WCAG 2.2", "OWASP ASVS 5.0.0", "Core Web Vitals", "WordPress Coding Standards"):
            self.assertIn(token, quality)
        self.assertIn("امنیت مطلق", security)
        self.assertIn("احراز هویت دومرحله‌ای", security)

    def test_no_source_brand_or_person_names(self) -> None:
        text = "\n".join(
            p.read_text(errors="ignore")
            for p in ROOT.rglob("*")
            if p.is_file() and p.suffix.lower() in {".php", ".html", ".css", ".json", ".md", ".txt", ".yaml", ".yml", ".svg"}
        )
        for term in ("MON" + "TIX", "Ali" + "reza", "علی" + "رضا", "Ar" + "min", "Mir" + "saeid", "Ebra" + "himi"):
            self.assertNotIn(term.casefold(), text.casefold())


if __name__ == "__main__":
    unittest.main()
