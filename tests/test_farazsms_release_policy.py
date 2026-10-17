"""CI policy guard; full dynamic vendor tests require exact original ZIP off-repository."""
from pathlib import Path
import unittest
R = Path(__file__).resolve().parents[1]
class FarazSMSReleasePolicy(unittest.TestCase):
    def test_pinned_vendor(self):
        sh = (R/'tools/build-farazsms-secured.sh').read_text()
        self.assertIn('bad42bdb6497411f2fde7e9525efd43af112c5c5e171faf5c098d99203b03b9d', sh)
        self.assertIn('farazsms-3.22.273-montix.2.patch', sh)
    def test_vendor_scope(self):
        patch = (R/'vendor-patches/farazsms-3.22.273-montix.2.patch').read_text()
        self.assertEqual(7, patch.count('diff -ruN '))
        for fragment in ('farazsms_montix_verified_customer_by_mobile','montix_faraz_otp_hmac_v1','farazsms_montix_pinned_upgrade','farazsms_montix_pinned_auto_update','montix_faraz_reset'):
            self.assertIn(fragment, patch)
    def test_single_otp_engine(self):
        core = (R/'plugin/barbershop-core/barbershop-core.php').read_text()
        bridge = (R/'plugin/barbershop-core/includes/farazsms-bridge.php').read_text()
        self.assertIn("define( 'BSC_VERSION', '3.5.1' )", core)
        self.assertIn("require_once BSC_DIR . 'includes/farazsms-bridge.php';", core)
        self.assertNotIn("require_once BSC_DIR . 'includes/otp-auth.php';", core)
        self.assertIn("FARAZSMS_MONTIX_SECURITY_PATCH === '2026-10-17.2'", bridge)
        self.assertIn('return is_ssl()', bridge)
    def test_runner(self):
        for f in ('tools/run-farazsms-security-tests.sh','tests/security-reset.php','tests/security-otp.php','tests/security-identity.php','tests/security-updates.php'):
            self.assertTrue((R/f).is_file(),f)
if __name__ == '__main__': unittest.main()
