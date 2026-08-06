#!/bin/bash
# Apache-level maintenance block, via a root .htaccess.
#
#   ./1-maintenance.sh on  --ip=203.0.113.7 [--root=/opt/lampp/htdocs/TCGEngine]
#   ./1-maintenance.sh off [--root=...]
#   ./1-maintenance.sh status
#
# No Apache restart is needed either way: .htaccess is read per request, so `on` takes effect on the
# next hit and `off` likewise. Do NOT stop LAMPP for this — it would take MySQL down with it, and
# the migration needs the database.
#
# ── Why use this AS WELL AS zzMaintenanceMode.php ────────────────────────────
# They protect against different failures, so run both:
#
#   zzMaintenanceMode.php  surgical — blocks WRITES only, leaves the site readable, cannot lock you
#                          out, no AllowOverride dependency. But it only blocks the writers we
#                          FOUND. The guard test proves the 14 known ones are gated; it cannot
#                          prove the list is complete.
#   this .htaccess         total — blocks every request at the web server, including any writer we
#                          missed and anything static. But it is all-or-nothing, it depends on
#                          AllowOverride, and a bad directive can 500 the whole site.
#
# Belt and braces for a one-shot migration. If they disagree, the .htaccess wins, which is the safe
# direction.
#
# ⚠ Two ways this fails SILENTLY, both checked below:
#   1. AllowOverride is not enabled for the docroot -> the file is ignored and the site stays open.
#   2. Your allowlisted IP is wrong -> you lock yourself out of the very tools you need.
# Neither is visible from a curl on the server itself (localhost is not your public IP). VERIFY
# FROM A PHONE ON MOBILE DATA before you trust it.
set -uo pipefail

MODE="${1:-}"; shift 2>/dev/null || true
IP=""; ROOT_DIR="${ROOT:-/opt/lampp/htdocs/TCGEngine}"
for arg in "$@"; do
  case "$arg" in
    --ip=*)   IP="${arg#--ip=}" ;;
    --root=*) ROOT_DIR="${arg#--root=}" ;;
    *) echo "unknown argument: $arg" >&2; exit 2 ;;
  esac
done
HT="$ROOT_DIR/.htaccess"
SAVED="$ROOT_DIR/.htaccess.premigration"

case "$MODE" in
  on)
    [ -n "$IP" ] || { echo "FATAL: --ip=<your public IP> is required. Get it with: curl -s ifconfig.me" >&2; exit 2; }
    # Preserve anything already there — prod may carry an untracked .htaccess that is load-bearing.
    # But NEVER preserve our OWN maintenance file: running `on` twice would otherwise save the
    # maintenance config as the "pre-migration" one, and `off` would then restore the site straight
    # back INTO maintenance while reporting success. Detect our marker and skip.
    if [ -e "$HT" ] && [ ! -e "$SAVED" ]; then
      if grep -q "SET_NNN migration maintenance" "$HT" 2>/dev/null; then
        echo "   .htaccess is already ours — not saving it as pre-migration (maintenance is on already)"
      else
        cp -a "$HT" "$SAVED" && echo "   saved existing .htaccess -> $SAVED"
      fi
    fi
    cat > "$HT" <<EOF
# TEMPORARY — SET_NNN migration maintenance. Remove with: 1-maintenance.sh off
RewriteEngine On

# The operator gets through, and the zz* mod tooling stays reachable for EVERYONE on this IP.
# That exemption is load-bearing: the census and the card generator are web-invoked because
# LAMPP's CLI PHP has no mysqli, so blocking /TCGEngine/zz would block the migration itself.
RewriteCond %{REMOTE_ADDR} !=$IP
RewriteCond %{REQUEST_URI} !^/TCGEngine/zz
RewriteRule ^ - [R=503,L]

ErrorDocument 503 "SWU Stats is down for scheduled maintenance. Back shortly."

# mod_headers is not always loaded; an unguarded Header directive 500s the WHOLE site.
<IfModule mod_headers.c>
Header always set Retry-After "7200"
</IfModule>
EOF
    echo "   wrote $HT (allowing $IP, exempting /TCGEngine/zz*)"
    echo
    echo "   ⚠ VERIFY FROM OFF-NETWORK — a phone on mobile data, not this box:"
    echo "       https://swustats.net/TCGEngine/SharedUI/MainMenu.php        expect 503"
    echo "       https://swustats.net/TCGEngine/zzMaintenanceMode.php        expect 200/302"
    echo "     A 200 on the first URL means AllowOverride is off and this file is being IGNORED."
    ;;
  off)
    # Belt and braces: if the saved file is our own maintenance config (from an older buggy run),
    # restoring it would put the site back into maintenance. Discard it instead.
    if [ -e "$SAVED" ] && grep -q "SET_NNN migration maintenance" "$SAVED" 2>/dev/null; then
      rm -f "$SAVED"
      echo "   discarded $SAVED — it was a copy of the maintenance file, not a real original"
    fi
    if [ -e "$SAVED" ]; then
      mv "$SAVED" "$HT" && echo "   restored the pre-migration .htaccess"
    else
      rm -f "$HT" && echo "   removed $HT (there was nothing here before)"
    fi
    echo "   verify: curl -s -o /dev/null -w '%{http_code}\\n' https://swustats.net/TCGEngine/SharedUI/MainMenu.php   # expect 200"
    ;;
  status)
    if [ -e "$HT" ] && grep -q "SET_NNN migration maintenance" "$HT" 2>/dev/null; then
      echo "   MAINTENANCE IS ON"
      grep -E "REMOTE_ADDR" "$HT" | sed 's/^/     /'
    else
      echo "   maintenance .htaccess is NOT in place"
    fi
    [ -e "$SAVED" ] && echo "   (a pre-migration .htaccess is saved at $SAVED)"
    ;;
  *)
    sed -n '2,10p' "${BASH_SOURCE[0]}"; exit 2 ;;
esac
