# Montix 3.5 — use full FarazSMS for customer authentication + all other SMS features

**Status: staging candidate, not approved for live production.** Prepared from the exact user-supplied `faraz-sms-3.22.273.zip`. No access to production WordPress, database, FarazSMS API credentials, real handsets, or payment callback was used. This is a scoped security patch; it does not constitute a comprehensive audit of every third-party FarazSMS module. Keep the staging site behind a password, and involve FarazSMS maintainers before long-term deployment.

## ZIPs

* `faraz-sms-secured-montix.1.zip` — **full FarazSMS plugin** (all supplied modules) with authentication hardening, `3.22.273-montix.1`.
* `barbershop-core-3.5.0.zip` — Montix storefront plugin: **removes its custom OTP hooks entirely** and integrates FarazSMS. Retains search, sections, gallery, cart, WooCommerce, etc.
* `persian-barbershop-theme.zip` — the *unchanged* theme from merged Montix 3.4 release.
* `farazsms-3.22.273-montix.1.patch` — auditable vendor source patch; rebuilding via `build-farazsms-secured.sh` in the bundle (`tools/build-farazsms-secured.sh` in GitHub) requires the original input ZIP with exact SHA-256 `bad42bdb6497411f2fde7e9525efd43af112c5c5e171faf5c098d99203b03b9d`.

## Login and registration

1. FarazSMS handles mobile OTP sending, verification, registration, password login/reset and all other plugin features. The Montix Core plugin **does not load `includes/otp-auth.php`**, so no second OTP send/login endpoints exist.
2. After the secured full plugin is active **and its bundled login/registration module has been enabled**, open wp-admin as an administrator: Montix creates `/login-register/` containing `[farazsms_login_form]` **if the slug does not already exist**. If it exists, edit the page to add the shortcode manually. Default name collection is set to off **only if the merchant has not already configured that setting**.
3. Storefront account button sends guests to `/login-register/`; signed-in customers access WooCommerce My Account and orders. WooCommerce's direct registration is blocked; account creation from checkout is disabled while guest checkout remains available. Administrative WP login remains `/wp-login.php`.
4. Full FarazSMS plugin must be installed with its unchanged settings for API key, approved login pattern with variable `code`, approved sender, SMS credits and notifications. **Do not configure `BSC_FARAZSMS_*` constants:** Montix custom SMS transport was removed.

## Security changes to supplied plugin

* OTP account lookup uses **only** `_bsc_verified_phone` from the previous Montix flow or `_farazsms_verified_phone` from a successful FarazSMS verification. `billing_phone`, `mobile_number` and `user_login` are explicitly **not** SMS-login proof. Unique customer-only role/capability enforcement excludes staff/admin accounts even if their phones appear in metadata.
* New users are explicitly created with WooCommerce `customer` role and an opaque WordPress login. Their verified mobile is stored in `_farazsms_verified_phone` after SMS proof. No signup email/password is required.
* Password-reset authorization is bound to a high-entropy 10-minute Secure/HttpOnly/SameSite browser cookie, user ID and normalized phone; single-use tokens have an atomic DB claim and a cleanup event. A code entered in browser A does **not** authorize browser B.
* OTP is stored as HMAC-SHA256, not plaintext. The login verification DB column is expanded from `VARCHAR(10)` to `VARCHAR(100)` on first use; prior unused OTP rows are invalidated on migration. 6-digit minimum across SMS generator and UI. Store failure blocks SMS sending.
* If the **unpatched** FarazSMS plugin is installed while the updated Montix Core is active, public FarazSMS login/reset AJAX actions and the login shortcode are disabled with a WordPress admin warning. Auth is **fail-closed** rather than silently running vulnerable stock endpoints. Automatic replacement of the secured Faraz plugin is disabled, but **manual vendor updates can still overwrite patches**; review and rebase with vendor on every update.

## Existing customer migration

* Accounts that already have `_bsc_verified_phone` remain eligible for FarazSMS OTP login (after role and collision checks). They keep existing WordPress IDs, orders, addresses and history; no accounts are automatically merged or deleted.
* Older customers whose only phone field is `billing_phone` or `mobile_number` **do not automatically gain SMS login**, even if that value is unique. This prevents account takeover via stale/editable checkout contact data. They sign in **with their previous username/email and password at `/wp-login.php`**, open WooCommerce My Account, and use the `تأیید موبایل حساب قدیمی` form. This form uses the *FarazSMS SMS sender and OTP class*, and on success writes `_farazsms_verified_phone`. A duplicate/conflicting phone fails closed and requires manual support review.
* Do NOT mass-copy `billing_phone` into `_farazsms_verified_phone`, or auto-merge duplicates. For a verified-phone customer changing their phone, reverify; the new identity replaces the previous `_bsc_verified_phone` marker.

## Recommended staging procedure (do not skip)

1. Back up **database + wp-content + existing plugin ZIPs + settings**, record active version and PHP/WooCommerce versions. Use HTTPS with WordPress `is_ssl()===true`. Clone the store to staging (preserving order IDs and accounts).
2. Enable maintenance on staging, upload `faraz-sms-secured-montix.1.zip` to replace only existing `faraz-sms` plugin, then replace `barbershop-core` with `barbershop-core-3.5.0.zip`. Keep WooCommerce/payment/shipping plugins and theme installed. **Deactivate any separate/standalone `farazsms-login` plugin: the reviewed bundled module must be the only SMS login handler.** Do not delete FarazSMS WordPress options, wallet tables, order tables or history.
3. Confirm `Plugins` shows **FarazSMS 3.22.273-montix.1** and **Barbershop Core 3.5.0**. In FarazSMS settings, first **enable its bundled login module** (not any standalone login plugin), then revisit wp-admin to provision `/login-register/` (or edit existing page). In FarazSMS login settings enable SMS login, approve pattern, verify `code_length` generates **at least 6**, set merchant's API key and sender. Disable or audit automatic plugin upgrades and configure SMS rate limits.
4. Test **at least**: new customer OTP signup, failed/expired/replayed OTP, second browser, SMS timeout and definitive failure, existing `_bsc_verified_phone`, customer with *only* `billing_phone` (must require password + linking), admin and shop-manager phone (must not log in via OTP), password-reset cross-browser attempts, legacy account linking, shortcode, mobile 375/412px, lost network, checkout/guest checkout, shipping, and actual payment callback. Verify SMS arrives on a real Iranian handset, not just provider HTTP acceptance.
5. Inspect WordPress PHP logs, FarazSMS send diagnostics, account role, verification metadata, and WooCommerce order ownership. Only consider production deployment after all tests pass. Clear LiteSpeed/CDN cache and exclude login, my-account, checkout and other personalized pages from page caching.

## Rollback

On failure keep login in maintenance and restore **both** previous plugin directories from backup, appropriate database snapshot (especially if reverting the verification-table schema), and store settings. Do not enable the *unpatched* FarazSMS stock login handler together with Montix v3.4 OTP. If reverting the vendor and keeping database, OTP rows created by hashed verifier will not work in the stock plaintext verifier; do not attempt to repair by bypassing verification. Restore a consistent matched set and smoke-test before reopening.

## Verification performed off-site

* ZIP provenance: exact user-supplied SHA-256 checked, patch reproducibly applies to the original package.
* All PHP files in both assembled plugins passed `php -l` using PHP 8.4.
* Isolated PHP regression `tests/security-reset.php`: verified customer owner, role denylist, unverified billing phone exclusion, cross-browser reset isolation, token one-time use.
* Isolated PHP regression `tests/security-otp.php`: schema expansion, HMAC storage, correct/incorrect/expired codes, replay rejection, plaintext rejection.
* No live WordPress integration environment, FarazSMS API credentials, live handset, or checkout/payment integration was accessible. Dynamic stub tests **do not replace** WordPress staging end-to-end verification, and no claim is made that the entire third-party plugin was penetration-tested.

