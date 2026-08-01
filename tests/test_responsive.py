from __future__ import annotations

import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "barbershop-core"
THEME = ROOT / "theme" / "persian-barbershop"


class ResponsiveContractTests(unittest.TestCase):
    def test_plan_covers_standards_surfaces_and_viewports(self) -> None:
        plan = (ROOT / "docs" / "RESPONSIVE-PLAN.md").read_text(encoding="utf-8")
        for token in (
            "WCAG 2.2",
            "320 CSS px",
            "44 by 44 CSS px",
            "Resize Text",
            "Text Spacing",
            "Orientation",
            "Focus Not Obscured",
            "Product archive and featured products",
            "Single product",
            "Cart",
            "Checkout and gateways",
            "Account, authentication, orders, and addresses",
            "Barber dashboard and WooCommerce administration",
            "320 × 800",
            "768 × 1024",
            "1024 × 768",
            "1440 × 900",
        ):
            self.assertIn(token, plan)

    def test_public_responsive_layer_covers_every_purchase_surface(self) -> None:
        css = (PLUGIN / "assets" / "runtime-storefront.css").read_text(encoding="utf-8")
        for token in (
            "@media (max-width: 960px)",
            "@media (max-width: 781px)",
            "@media (max-width: 480px)",
            "@media (max-width: 900px) and (max-height: 520px)",
            "@media (pointer: coarse)",
            "env(safe-area-inset-bottom)",
            "min-height: 44px",
            ".pbs-header-navigation",
            ".pbs-hero",
            ".bsc-category-grid",
            ".pbs-card-grid",
            ".pbs-gallery-grid",
            ".wp-block-post-template",
            ".pbs-contact-panel",
            ".woocommerce ul.products",
            ".woocommerce div.product",
            "body.woocommerce-cart",
            "body.woocommerce-checkout",
            "body.woocommerce-account",
            ".wc-block-cart",
            ".wc-block-checkout",
            ".bsc-mobile-dock",
        ):
            self.assertIn(token, css)

    def test_admin_responsive_layer_covers_dashboard_and_operations(self) -> None:
        css = (PLUGIN / "assets" / "runtime-admin.css").read_text(encoding="utf-8")
        for token in (
            "@media (max-width: 1280px)",
            "@media (max-width: 960px)",
            "@media (max-width: 782px)",
            "@media (max-width: 520px)",
            "@media (max-width: 360px)",
            "env(safe-area-inset-bottom)",
            "min-height: 44px",
            ".bsc-admin-hero",
            ".bsc-stat-grid",
            ".bsc-status-grid",
            ".bsc-setup-grid",
            ".bsc-dashboard-field-grid",
            ".bsc-dashboard-media-grid",
            ".bsc-dashboard-category-grid",
            ".bsc-dashboard-save",
            ".woocommerce_page_wc-orders",
            "overflow-x: auto",
        ):
            self.assertIn(token, css)

    def test_lan_mobile_mode_is_opt_in_and_reversible(self) -> None:
        compose = (ROOT / "compose.yaml").read_text(encoding="utf-8")
        env = (ROOT / ".env.example").read_text(encoding="utf-8")
        script = (ROOT / "tools" / "mobile-test.sh").read_text(encoding="utf-8")
        self.assertIn("${WP_BIND_ADDRESS:-127.0.0.1}:${WP_PORT:-8080}:80", compose)
        self.assertIn("WP_BIND_ADDRESS=127.0.0.1", env)
        for token in (
            "WP_BIND_ADDRESS=0.0.0.0",
            "WP_BIND_ADDRESS=127.0.0.1",
            "--local",
            "--status",
            "option update home",
            "option update siteurl",
            "private_ipv4",
            "Do not enable router port forwarding",
        ):
            self.assertIn(token, script)

    def test_browser_matrix_includes_mobile_tablet_landscape_and_desktop(self) -> None:
        source = (ROOT / "tests" / "test_ui.py").read_text(encoding="utf-8")
        for token in (
            '("wcag-minimum", 320, 800)',
            '("small-android", 360, 800)',
            '("common-iphone", 390, 844)',
            '("large-phone", 412, 915)',
            '("tablet-portrait", 768, 1024)',
            '("tablet-landscape", 1024, 768)',
            '("short-laptop", 1280, 720)',
            '("desktop", 1440, 900)',
            "test_wcag_320_reflow_and_text_spacing_override",
            "test_portrait_and_landscape_orientations",
            "test_mobile_focus_is_not_obscured_by_bottom_dock",
        ):
            self.assertIn(token, source)

    def test_responsive_release_cache_busts_plugin_assets(self) -> None:
        core = (PLUGIN / "barbershop-core.php").read_text(encoding="utf-8")
        self.assertIn("Version: 3.3.0", core)
        self.assertIn("define( 'BSC_VERSION', '3.3.0' )", core)


if __name__ == "__main__":
    unittest.main()
