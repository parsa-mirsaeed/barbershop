from __future__ import annotations

import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "barbershop-core"


class ReportedRegressionTests(unittest.TestCase):
    def test_payment_runtime_builds_pdo_mysql_for_web_and_cli(self) -> None:
        dockerfile = (ROOT / "docker" / "wordpress" / "Dockerfile").read_text()
        local = (ROOT / "compose.yaml").read_text()
        production = (ROOT / "compose.production.yaml").read_text()
        installer = (ROOT / "tools" / "install.sh").read_text()
        deploy = (ROOT / "tools" / "deploy.sh").read_text()
        self.assertIn("docker-php-ext-install pdo_mysql", dockerfile)
        self.assertIn("WORDPRESS_IMAGE: wordpress:php8.2-apache", local)
        self.assertIn("WORDPRESS_IMAGE: wordpress:cli-php8.2", local)
        self.assertIn("docker/wordpress/Dockerfile", production)
        self.assertIn("docker compose build wordpress wpcli", installer)
        self.assertIn('"${COMPOSE[@]}" build wordpress wpcli', deploy)

    def test_zarinpal_and_gateland_are_provisioned_and_runtime_tested(self) -> None:
        installer = (ROOT / "tools" / "install.sh").read_text()
        deploy = (ROOT / "tools" / "deploy.sh").read_text()
        workflow = (ROOT / ".github" / "workflows" / "quality.yml").read_text()
        for script in (installer, deploy):
            self.assertIn("install_plugin zarinpal-woocommerce-payment-gateway", script)
            self.assertIn("install_plugin gateland", script)
        self.assertIn("plugin is-active zarinpal-woocommerce-payment-gateway", workflow)
        self.assertIn("plugin is-active gateland", workflow)
        self.assertIn('extension_loaded("pdo_mysql")', workflow)
        self.assertIn("Uncaught PDOException", workflow)

    def test_media_preview_fatal_is_prevented(self) -> None:
        core = (PLUGIN / "barbershop-core.php").read_text()
        workflow = (ROOT / ".github" / "workflows" / "quality.yml").read_text()
        self.assertIn("function hidden(", core)
        self.assertIn("Call to undefined function hidden", workflow)
        self.assertIn("bsc_render_media_control", workflow)

    def test_barber_save_action_is_always_visible(self) -> None:
        barber = (PLUGIN / "includes" / "barber.php").read_text()
        css = (PLUGIN / "assets" / "qa-admin.css").read_text()
        js = (PLUGIN / "assets" / "qa-admin.js").read_text()
        self.assertIn("ذخیره همه تغییرات", barber)
        self.assertIn("position: fixed !important", css)
        self.assertIn("تغییرات ذخیره‌نشده دارید", css)
        self.assertIn("beforeunload", js)

    def test_login_register_layout_targets_customer_login_wrapper(self) -> None:
        css = (PLUGIN / "assets" / "qa-woocommerce.css").read_text()
        self.assertIn("#customer_login", css)
        self.assertIn("grid-template-columns: repeat(2, minmax(0, 1fr))", css)
        self.assertIn("float: none !important", css)
        self.assertIn("width: 100% !important", css)
        self.assertIn("@media (max-width: 900px)", css)

    def test_orders_cart_checkout_are_readable_rtl_and_vazirmatn(self) -> None:
        css = (PLUGIN / "assets" / "qa-woocommerce.css").read_text()
        admin_css = (PLUGIN / "assets" / "qa-admin.css").read_text()
        for token in (
            "font-family: Vazirmatn",
            "direction: rtl",
            "woocommerce-orders-table__cell-order-actions",
            "#payment ul.payment_methods",
            "bsc-cart-payment-methods",
        ):
            self.assertIn(token, css)
        self.assertIn("woocommerce_page_wc-orders", admin_css)
        self.assertIn("post-type-shop_order", admin_css)

    def test_english_fallbacks_and_duplicate_empty_cart_are_removed(self) -> None:
        fixes = (PLUGIN / "includes" / "qa-fixes.php").read_text()
        for source, persian in (
            ("Your cart is currently empty!", "سبد خرید شما خالی است."),
            ("New in store", "تازه‌های فروشگاه"),
            ("Order received", "سفارش دریافت شد"),
            ("Payment method", "روش پرداخت"),
        ):
            self.assertIn(source, fixes)
            self.assertIn(persian, fixes)
        self.assertIn("remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message'", fixes)
        self.assertIn("gettext_with_context", fixes)

    def test_classic_checkout_is_kept_for_gateway_compatibility(self) -> None:
        fixes = (PLUGIN / "includes" / "qa-fixes.php").read_text()
        workflow = (ROOT / ".github" / "workflows" / "quality.yml").read_text()
        self.assertIn("[woocommerce_cart]", fixes)
        self.assertIn("[woocommerce_checkout]", fixes)
        self.assertIn("has_block", fixes)
        self.assertIn("woocommerce_cart_page_id", workflow)
        self.assertIn("woocommerce_checkout_page_id", workflow)


if __name__ == "__main__":
    unittest.main()
