# Barbershop 3.4.0 — shared-hosting upgrade guide

This release updates **Barbershop Core** and the **Persian Barbershop** theme; it does **not** ship or overwrite WooCommerce, the existing FarazSMS plugin, payment/shipping plugins, uploads, customers, orders, or customer/order data. Existing installations retain their catalog and merchant settings except for the two checkout options noted below.

## Changes
- Accessible product search in the header. Search results show WooCommerce product cards and pagination; other posts are not included in product search.
- Phone-only customer signup/login: 11-digit Iranian mobile, SMS verification code, and legal checkbox. No email, name, shipping address, or password at signup. Purchase name/address/receipt contact information remains WooCommerce checkout data.
- Homepage section switches (including before/after, reviews/testimonials, articles, and services) in **WooCommerce → Settings → General → تنظیمات آرایشگاه**. Product review visibility is independently configurable. Hidden sections retain their data.
- WooCommerce gallery thumbnails select corresponding full-size product images on mobile, using existing FlexSlider when available.

## Existing-shop checkout option migration
On the first upgrade to 3.4.0, Barbershop Core **enables guest checkout** and **disables WooCommerce account creation from checkout**. This prevents unverified new accounts and allows orders even if SMS service is temporarily unavailable. It does not remove order fields, existing users, products or merchant-created content. The operator can review these options in WooCommerce → Settings → Accounts & Privacy.

## Security and compatibility
- OTP requires HTTPS. Codes are generated with random_int, salted WP password hashes, 5-minute lifetime, 5 guesses maximum, 60-second resend cooldown, per-phone daily limit and per-IP limits. A challenge is stored in a transient and tied to an HttpOnly, Secure, SameSite=Lax cookie.
- SMS transport **fails closed**. There is no hard-coded API key and no simulated “successful delivery.” On provider failure, no challenge is issued.
- The repository does **not** contain the host's third-party FarazSMS plugin or its API interface. A real plugin can supply transport by implementing the PHP filter `bsc_otp_send_sms` returning literal `true` only after confirmed acceptance by the provider. The tested alternative is the documented direct FarazSMS pattern endpoint. Do not blindly guess plugin functions.
- To configure direct FarazSMS, put these lines in `wp-config.php` **above** “That's all, stop editing”. Never commit these values into Git:
  ```php
  define( 'BSC_FARAZSMS_API_KEY', 'YOUR_PRIVATE_API_KEY' );
  define( 'BSC_FARAZSMS_PATTERN', 'YOUR_APPROVED_PATTERN_UID' );
  define( 'BSC_FARAZSMS_CODE_VARIABLE', 'code' ); // matches pattern variable
  // define( 'BSC_FARAZSMS_LINE', 'YOUR_APPROVED_SENDER_LINE' ); // only if required
  ```
  The direct adapter sends to `https://api.iranpayamak.com/ws/v1/sms/pattern` and only accepts an HTTP 201 JSON `status=success`. FarazSMS panel must have an approved service/OTP pattern with variable `code`, an approved sender line where needed, API access, and SMS credit. The endpoint's 201 response confirms provider acceptance, **not** successful delivery to the handset.
- Old accounts have an **unverified** `billing_phone` and cannot be silently taken over based on shipping/contact data. Existing customers must first log in using their previous password, visit My Account, and verify the phone through the migration form. Admin/shop-manager accounts never receive OTP login.
- New accounts use an opaque login identifier, no fabricated email, a non-disclosed random password, and the `customer` role. Verified identity is stored separately as `_bsc_verified_phone` and does not change when an order recipient's `billing_phone` changes.
- Keep staff/admin password + 2FA via `/wp-login.php` and Wordfence. Do not allow wholesale OTP role escalation.

## Before upload
1. Create a **full encrypted backup of both database and wp-content**. Verify recovery access and plan rollback; take a second snapshot immediately before activation.
2. Confirm host supports PHP 8.2+, `pdo_mysql`, `mysqli`, cURL, mbstring, HTTPS and a valid WordPress SSL/reverse-proxy configuration (`is_ssl()` must be true).
3. On a staging copy, verify existing account sign-in and password migration, FarazSMS SMS delivery, checkout/payment/shipping callback, product search and thumbnail image switching.
4. In live host, take a backup of existing `wp-content/plugins/barbershop-core/` and `wp-content/themes/persian-barbershop/`.

## Install on cPanel/DirectAdmin/managed WordPress
The build gives `barbershop-shared-hosting-3.4.0.zip` containing the two standard WordPress installable ZIPs, an MU-plugin guard, SHA256SUMS, and documentation.

1. Download the CI **barbershop-shared-hosting** artifact from the successful Actions run and verify SHA256SUMS locally.
2. Extract the outer ZIP on your computer. In WordPress Admin, upload **barbershop-core-plugin.zip** through Plugins → Add New → Upload Plugin; choose **Replace current with uploaded** (or replace only `wp-content/plugins/barbershop-core/` by File Manager/FTP with files preserved). Then upload **persian-barbershop-theme.zip** through Appearance → Themes → Add New → Upload Theme. Existing activations should remain.
3. Keep the existing `wp-content/mu-plugins/barbershop-hosting-guard.php`, or update it from this package, especially when Gateland is installed. Do **not** delete other MU plugins.
4. Never upload the outer ZIP as a plugin. Never wipe `wp-content/uploads`, `wp-config.php`, `wp-content/plugins/woocommerce`, FarazSMS, payment, shipping, or the database.
5. Enter FarazSMS credentials in `wp-config.php` via secure hosting controls. Use approved pattern variable, HTTPS, and test sending a real code. If unavailable, **phone signup will be unavailable by design**.
6. In the Site Editor, inspect Header and Search templates if they were previously customised: database-saved templates may override updated theme files. Insert `[bsc_product_search]` into the active header if needed.
7. Set visibility in WooCommerce → Settings → General → تنظیمات آرایشگاه → نمایش بخش‌های سایت. Leave product sections enabled for commerce.
8. Purge LiteSpeed/cache/CDN and hard-refresh the phone browser. Exclude My Account, checkout, cart and `/wp-admin/admin-post.php` from all public caches.
9. Perform mobile QA: tap the second/third product thumbnail and confirm that a **large main image**, not the thumbnail resolution, appears. Test OTP expiry, multiple invalid codes, cooldown and legacy accounts.
10. Monitor WordPress/WooCommerce logs and transactions. Run the bundled hosting audit and restore from backups if checkout, SMS or account migration fails.

## Verification / rollback
Check the installed Barbershop Core version is **3.4.0**, asset requests include `storefront-features.css?ver=3.4.0`, and `gallery-fix.js?ver=3.4.0` on product pages. Confirm no forbidden sample content and no browser-console errors.

Rollback = restore the previous **plugin and theme directories together**, undo only new `wp-config.php` FarazSMS constants if needed, and purge all caches. Do not delete customer/order records; preserve the database backup for emergency recovery. New verified customer accounts remain WordPress users and should not be deleted by a code rollback.

## Final live acceptance needed
Automated CI cannot prove delivery to an Iranian handset, the host's proprietary plugin adapter, gateway charges/callbacks, or the phone's production gallery without production credentials and a real-device session. Complete these checks before calling the release live-ready.
