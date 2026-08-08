#!/usr/bin/env bash
set -Eeuo pipefail

URL="${1:-${URL:-}}"
[[ -n "$URL" ]] || { echo "Usage: $0 https://shop.example.com" >&2; exit 2; }
command -v curl >/dev/null 2>&1 || { echo "curl is required." >&2; exit 2; }

URL="${URL%/}"
case "$URL" in
  https://*) ;;
  *) echo "CRITICAL: production audit URL must start with https://" >&2; exit 1 ;;
esac

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
FAIL=0
WARN=0
PASS=0

pass() { PASS=$((PASS+1)); printf 'PASS  %s\n' "$*"; }
warn() { WARN=$((WARN+1)); printf 'WARN  %s\n' "$*"; }
fail() { FAIL=$((FAIL+1)); printf 'FAIL  %s\n' "$*"; }

fetch_headers() {
  local target="$1" outfile="$2"
  curl --silent --show-error --location --max-time 20 --connect-timeout 8 --dump-header "$outfile" --output /dev/null "$target" || return 1
}

HOME_HEADERS="$TMP/home.headers"
if fetch_headers "$URL/" "$HOME_HEADERS"; then
  FINAL_URL="$(curl --silent --show-error --location --max-time 20 --connect-timeout 8 --output /dev/null --write-out '%{url_effective}' "$URL/")"
  case "$FINAL_URL" in
    https://*) pass "canonical request remains on HTTPS ($FINAL_URL)" ;;
    *) fail "request downgraded or redirected away from HTTPS ($FINAL_URL)" ;;
  esac
else
  fail "homepage is not reachable over HTTPS"
fi

header_value() {
  local file="$1" name="$2"
  awk -v key="$(printf '%s' "$name" | tr '[:upper:]' '[:lower:]')" '
    BEGIN { IGNORECASE=1 }
    {
      line=$0; sub(/\r$/, "", line)
      split(line, p, ":")
      h=tolower(p[1])
      if (h==key) {
        sub(/^[^:]+:[[:space:]]*/, "", line)
        value=line
      }
    }
    END { print value }
  ' "$file"
}

XCTO="$(header_value "$HOME_HEADERS" 'x-content-type-options')"
[[ "${XCTO,,}" == *nosniff* ]] && pass "X-Content-Type-Options nosniff is present" || warn "X-Content-Type-Options nosniff is missing"

FRAME="$(header_value "$HOME_HEADERS" 'x-frame-options')"
CSP="$(header_value "$HOME_HEADERS" 'content-security-policy')"
if [[ "${FRAME,,}" == *sameorigin* || "${FRAME,,}" == *deny* || "${CSP,,}" == *frame-ancestors* ]]; then
  pass "clickjacking protection is present"
else
  warn "X-Frame-Options/CSP frame-ancestors was not observed"
fi

REF="$(header_value "$HOME_HEADERS" 'referrer-policy')"
[[ -n "$REF" ]] && pass "Referrer-Policy is present ($REF)" || warn "Referrer-Policy is missing"

HSTS="$(header_value "$HOME_HEADERS" 'strict-transport-security')"
[[ -n "$HSTS" ]] && pass "HSTS is present" || warn "HSTS was not observed; consider enabling it only after HTTPS is stable on all required hostnames"

USERS_BODY="$TMP/users.body"
USERS_CODE="$(curl --silent --show-error --max-time 20 --connect-timeout 8 --output "$USERS_BODY" --write-out '%{http_code}' "$URL/wp-json/wp/v2/users?per_page=1" || true)"
case "$USERS_CODE" in
  401|403) pass "unauthenticated REST user enumeration is blocked ($USERS_CODE)" ;;
  200) fail "unauthenticated REST user enumeration returned HTTP 200" ;;
  *) warn "REST user-enumeration probe returned HTTP ${USERS_CODE:-unknown}; inspect manually" ;;
esac

ACCOUNT_HEADERS="$TMP/account.headers"
if fetch_headers "$URL/my-account/" "$ACCOUNT_HEADERS"; then
  CACHE="$(header_value "$ACCOUNT_HEADERS" 'cache-control')"
  if [[ "${CACHE,,}" == *no-cache* || "${CACHE,,}" == *no-store* || "${CACHE,,}" == *private* ]]; then
    pass "account page sends private/no-cache semantics"
  else
    warn "account page did not expose obvious private/no-cache Cache-Control; verify LiteSpeed/CDN exclusions"
  fi
else
  warn "could not inspect /my-account/ cache headers"
fi

DEBUG_BODY="$TMP/debug.body"
DEBUG_CODE="$(curl --silent --show-error --max-time 15 --connect-timeout 8 --output "$DEBUG_BODY" --write-out '%{http_code}' "$URL/wp-content/debug.log" || true)"
if [[ "$DEBUG_CODE" == "200" ]] && grep -Eqi '(PHP (Fatal|Warning|Notice|Parse)|Stack trace|Uncaught (Error|Exception)|DB_PASSWORD|AUTH_KEY)' "$DEBUG_BODY"; then
  fail "wp-content/debug.log appears publicly readable and contains sensitive diagnostic data"
elif [[ "$DEBUG_CODE" == "200" ]]; then
  warn "wp-content/debug.log returned HTTP 200; confirm this is not an exposed debug log"
else
  pass "no public diagnostic debug.log was detected"
fi

LOGIN_CODE="$(curl --silent --show-error --location --max-time 20 --connect-timeout 8 --output /dev/null --write-out '%{http_code}' "$URL/wp-login.php" || true)"
if [[ "$LOGIN_CODE" == "200" ]]; then
  pass "WordPress login endpoint is reachable"
else
  warn "wp-login.php returned HTTP ${LOGIN_CODE:-unknown}; verify admin access and any WAF policy"
fi

printf '\nSummary: pass=%d warning=%d fail=%d\n' "$PASS" "$WARN" "$FAIL"
if (( FAIL > 0 )); then
  echo "Production audit FAILED. Fix critical findings before accepting live orders." >&2
  exit 1
fi

echo "No externally observable critical failures were detected. Warnings still require review, and this does not replace the in-WordPress Hosting Health audit or backup/payment tests."
