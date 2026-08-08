import hashlib
import json
from pathlib import Path
import re
import unittest
import zipfile

ROOT = Path(__file__).resolve().parents[1]


class SharedHostingStaticTests(unittest.TestCase):
    def test_required_shared_hosting_files_exist(self):
        required = [
            "docs/SHARED-HOSTING-PRODUCTION.md",
            "hosting/.user.ini.recommended",
            "hosting/wp-config.production.snippet.txt",
            "hosting/mu-plugins/barbershop-hosting-guard.php",
            "tools/build-hosting-release.sh",
            "tools/install-shared-hosting.sh",
            "tools/shared-hosting-audit.sh",
            "plugin/barbershop-core/includes/hosting.php",
        ]
        for path in required:
            self.assertTrue((ROOT / path).is_file(), path)

    def test_hosting_audit_has_critical_runtime_requirements(self):
        php = (ROOT / "plugin/barbershop-core/includes/hosting.php").read_text(encoding="utf-8")
        for needle in [
            "pdo_mysql",
            "mysqli",
            "curl",
            "mbstring",
            "openssl",
            "fileinfo",
            "FORCE_SSL_ADMIN",
            "DISALLOW_FILE_EDIT",
            "WP_DEBUG",
            "WP_ENVIRONMENT_TYPE",
            "disk_free_space",
            "wp_next_scheduled",
            "bsc hosting audit",
        ]:
            self.assertIn(needle, php)
        self.assertNotIn("DB_PASSWORD", php)
        self.assertNotIn("DB_USER", php)

    def test_gateland_guard_loads_before_normal_plugins_and_never_stores_secrets(self):
        guard = (ROOT / "hosting/mu-plugins/barbershop-hosting-guard.php").read_text(encoding="utf-8")
        self.assertIn("option_active_plugins", guard)
        self.assertIn("site_option_active_sitewide_plugins", guard)
        self.assertIn("pdo_mysql", guard)
        self.assertIn("gateland/gateland.php", guard)
        self.assertNotIn("DB_PASSWORD", guard)
        self.assertNotIn("update_option( 'active_plugins'", guard)

    def test_shared_host_installer_is_rootless_and_orders_guard_before_gateland(self):
        script = (ROOT / "tools/install-shared-hosting.sh").read_text(encoding="utf-8")
        self.assertIn("wp --path=", script)
        self.assertIn("barbershop-hosting-guard.php", script)
        self.assertIn("pdo_mysql", script)
        self.assertIn("install_plugin gateland", script)
        self.assertLess(script.index("barbershop-hosting-guard.php"), script.index("install_plugin gateland"))
        for forbidden in ["sudo ", "apt-get", "docker ", "DB_PASSWORD=", "WP_ADMIN_PASSWORD="]:
            self.assertNotIn(forbidden, script)

    def test_production_snippet_does_not_disable_updates(self):
        snippet = (ROOT / "hosting/wp-config.production.snippet.txt").read_text(encoding="utf-8")
        self.assertIn("FORCE_SSL_ADMIN", snippet)
        self.assertIn("DISALLOW_FILE_EDIT", snippet)
        self.assertIn("WP_DEBUG', false", snippet)
        self.assertNotRegex(snippet, r"define\(\s*'DISALLOW_FILE_MODS'\s*,\s*true")
        self.assertIn("ONLY after a real", snippet)

    def test_docs_cover_backup_cache_cron_payment_and_rollback(self):
        doc = (ROOT / "docs/SHARED-HOSTING-PRODUCTION.md").read_text(encoding="utf-8").lower()
        for needle in [
            "backups",
            "restore",
            "cache",
            "cron",
            "gateland",
            "pdo",
            "callback",
            "rollback",
            "2fa",
            "shared hosting",
        ]:
            self.assertIn(needle, doc)
        self.assertIn("do not require root", doc)
        self.assertIn("do not recursively set 777", doc)

    def test_release_builder_excludes_secrets_and_bundles_only_project_artifacts(self):
        script = (ROOT / "tools/build-hosting-release.sh").read_text(encoding="utf-8")
        self.assertIn("scan-secrets.py", script)
        self.assertIn("SHA256SUMS", script)
        self.assertIn("third_party_plugins_bundled", script)
        self.assertIn("False", script)
        self.assertIn("install-shared-hosting.sh", script)
        self.assertIn("shared-hosting-audit.sh", script)
        self.assertNotIn(".env.production", script)

    def test_makefile_keeps_docker_and_shared_hosting_as_separate_targets(self):
        makefile = (ROOT / "Makefile").read_text(encoding="utf-8")
        self.assertIn("deploy:\n\t./tools/deploy.sh", makefile)
        self.assertIn("hosting-release:", makefile)
        self.assertIn("hosting-test:", makefile)
        self.assertIn("hosting-audit:", makefile)


class SharedHostingBuiltArtifactTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.out = ROOT / "dist/shared-hosting"

    def test_built_bundle_when_present(self):
        if not self.out.exists():
            self.skipTest("shared-hosting release has not been built in this test phase")

        expected = [
            "persian-barbershop-theme.zip",
            "barbershop-core-plugin.zip",
            "mu-plugins/barbershop-hosting-guard.php",
            ".user.ini.recommended",
            "wp-config.production.snippet.txt",
            "README-SHARED-HOSTING.md",
            "install-shared-hosting.sh",
            "shared-hosting-audit.sh",
            "manifest.json",
            "SHA256SUMS",
        ]
        for relative in expected:
            self.assertTrue((self.out / relative).is_file(), relative)

        manifest = json.loads((self.out / "manifest.json").read_text(encoding="utf-8"))
        self.assertEqual("shared-hosting", manifest["deployment"])
        self.assertFalse(manifest["third_party_plugins_bundled"])
        self.assertIn("pdo_mysql", manifest["requires"]["critical_extensions"])

        checksums = {}
        for line in (self.out / "SHA256SUMS").read_text(encoding="utf-8").splitlines():
            digest, name = line.split(None, 1)
            checksums[name.strip()] = digest
        for name, expected_digest in checksums.items():
            data = (self.out / name).read_bytes()
            self.assertEqual(expected_digest, hashlib.sha256(data).hexdigest(), name)

        with zipfile.ZipFile(self.out / "barbershop-core-plugin.zip") as archive:
            names = archive.namelist()
            self.assertIn("barbershop-core/barbershop-core.php", names)
            self.assertIn("barbershop-core/includes/hosting.php", names)
            self.assertFalse(any(".env" in name or "/.git/" in name or name.endswith("debug.log") for name in names))

        with zipfile.ZipFile(self.out / "persian-barbershop-theme.zip") as archive:
            names = archive.namelist()
            self.assertIn("persian-barbershop/style.css", names)
            self.assertFalse(any(".env" in name or "/.git/" in name or name.endswith("debug.log") for name in names))

        bundle_candidates = list((ROOT / "dist").glob("barbershop-shared-hosting-*.zip"))
        self.assertEqual(1, len(bundle_candidates))
        with zipfile.ZipFile(bundle_candidates[0]) as archive:
            names = archive.namelist()
            self.assertIn("manifest.json", names)
            self.assertIn("mu-plugins/barbershop-hosting-guard.php", names)
            self.assertFalse(any(re.search(r"(^|/)(\.env|wp-config\.php|debug\.log)$", name) for name in names))


if __name__ == "__main__":
    unittest.main()
