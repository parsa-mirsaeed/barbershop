from __future__ import annotations

import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "barbershop-core"


class LiveRoundTwoRegressionTests(unittest.TestCase):
    def test_dashboard_hook_has_one_runtime_callback(self) -> None:
        source = (PLUGIN / "includes" / "runtime-repairs.php").read_text()
        self.assertIn("bsc_runtime_dedupe_dashboard_callbacks", source)
        self.assertIn("remove_action( $hook, 'bsc_render_setup_page' )", source)
        self.assertIn("remove_action( $hook, 'bsc_render_barber_dashboard' )", source)
        self.assertIn("remove_action( $hook, 'bsc_render_complete_barber_dashboard' )", source)
        self.assertIn("add_action( $hook, 'bsc_render_complete_barber_dashboard' )", source)

    def test_dashboard_submit_does_not_trigger_leave_warning(self) -> None:
        script = (PLUGIN / "assets" / "qa-admin.js").read_text()
        media = (PLUGIN / "assets" / "admin.js").read_text()
        self.assertIn("querySelectorAll('.bsc-dashboard-form')", script)
        self.assertIn("let isSubmitting = false", script)
        self.assertIn("isSubmitting = true", script)
        self.assertIn("if (isSubmitting || !hasDirtyForm) return", script)
        self.assertIn("dispatchEvent(new Event('change'", media)

    def test_save_action_is_sticky_inside_its_form(self) -> None:
        css = (PLUGIN / "assets" / "runtime-admin.css").read_text()
        self.assertIn("position: sticky !important", css)
        self.assertIn("inset: auto !important", css)
        self.assertNotIn("position: fixed !important", css)

    def test_frontend_navigation_css_is_enqueued_before_render(self) -> None:
        source = (PLUGIN / "includes" / "runtime-repairs.php").read_text()
        css = (PLUGIN / "assets" / "runtime-storefront.css").read_text()
        self.assertIn("bsc_runtime_enqueue_frontend_navigation_assets", source)
        self.assertIn("'wp_enqueue_scripts'", source)
        self.assertIn(".bsc-mobile-dock", css)
        self.assertIn("display: none !important", css)
        self.assertIn("height: 1.4rem !important", css)

    def test_featured_product_cards_cannot_fill_the_page(self) -> None:
        css = (PLUGIN / "assets" / "runtime-storefront.css").read_text()
        self.assertIn("repeat(auto-fill, minmax(15rem, 18rem))", css)
        self.assertIn("max-width: 18rem", css)
        self.assertIn(".pbs-products-section", css)

    def test_gateland_missing_tables_run_vendor_schema_creator(self) -> None:
        source = (PLUGIN / "includes" / "runtime-repairs.php").read_text()
        for table in ("gateland_gateways", "gateland_transactions", "gateland_logs"):
            self.assertIn(table, source)
        self.assertIn("SHOW TABLES LIKE", source)
        self.assertIn("\\Nabik\\Gateland\\Install::create_tables()", source)
        self.assertIn("bsc_runtime_gateland_schema_ready", source)
        self.assertIn("bsc gateland repair", source)
        self.assertIn("bsc_gateland_schema_error", source)
        self.assertNotIn("do_action( 'activate_' . $plugin", source)

    def test_install_and_deploy_force_gateland_schema_repair(self) -> None:
        for path in (ROOT / "tools" / "install.sh", ROOT / "tools" / "deploy.sh"):
            source = path.read_text()
            self.assertIn("wp bsc gateland repair", source)

    def test_checkout_privacy_text_has_a_persian_fallback(self) -> None:
        source = (PLUGIN / "includes" / "runtime-repairs.php").read_text()
        self.assertIn("Your personal data will be used to process your order", source)
        self.assertIn("اطلاعات شخصی شما برای پردازش سفارش", source)

    def test_runtime_repairs_are_loaded_and_cache_busted(self) -> None:
        core = (PLUGIN / "barbershop-core.php").read_text()
        self.assertIn("Version: 3.3.0", core)
        self.assertIn("define( 'BSC_VERSION', '3.3.0' )", core)
        self.assertIn("includes/runtime-repairs.php", core)


if __name__ == "__main__":
    unittest.main()
