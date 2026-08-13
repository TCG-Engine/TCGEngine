#!/usr/bin/env bash
#
# harden-fail2ban.sh - fail2ban tuning for a CDN-fronted XAMPP/LAMPP game box.
#
# THE PROBLEM THIS FIXES (observed in prod, 2026-08-11 03:08-03:10 UTC):
#   The original harden-host.sh jail counted EVERY http request from one IP and
#   banned at 300 requests / 60s. Behind Cloudflare with no mod_remoteip, "one IP"
#   is not one player -- it is an entire Cloudflare EDGE carrying every player on
#   the box. At peak, six CF edges crossed 5 req/s within 107 seconds, iptables
#   dropped them, and the site went dark (CF error 521, "Web server is down").
#   The stock [recidive] jail then stood ready to escalate a repeat to a ONE WEEK ban.
#
# So there are two independent defects, and both are fixed here:
#   A. fail2ban could not see who the real client was  -> mod_remoteip + LogFormat %a
#   B. the thresholds were sized for a single browser, not a polling game client,
#      and Cloudflare was never whitelisted  -> lenient jail + ignoreip
#
# What it does (all idempotent, all backed up first):
#   1. Install fail2ban if missing
#   2. Resolve Cloudflare's published IP ranges (live fetch, pinned fallback)
#   3. Apache: enable mod_remoteip w/ CF-Connecting-IP + log the REAL client (%a)
#   4. Rewrite the xampp-dos filter + jail.local: whitelist CF, lenient rates,
#      short bans, de-escalated [recidive], and watch per-vhost access logs too
#   5. Unban anything currently held that the new whitelist covers
#
# Safe to run on an ALREADY-PROVISIONED box -- that is the point. It owns
# /etc/fail2ban/jail.local and /etc/fail2ban/filter.d/xampp-dos.conf outright and
# rewrites them from this script every run, exactly like harden-htaccess.sh owns
# the docroot .htaccess.
#
# Assumes: XAMPP at /opt/lampp, Debian/Ubuntu (apt), Apache via mod_php, run as root.
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Config (override via environment)
# ---------------------------------------------------------------------------
LAMPP_ROOT="${LAMPP_ROOT:-/opt/lampp}"
HTTPD_CONF="${HTTPD_CONF:-$LAMPP_ROOT/etc/httpd.conf}"
ACCESS_LOG="${ACCESS_LOG:-$LAMPP_ROOT/logs/access_log}"
ERROR_LOG="${ERROR_LOG:-$LAMPP_ROOT/logs/error_log}"
REMOTEIP_CONF="${REMOTEIP_CONF:-$LAMPP_ROOT/etc/extra/httpd-remoteip.conf}"
CF_IPS_CACHE="${CF_IPS_CACHE:-/etc/fail2ban/cloudflare-ips.local}"

# The real-client-IP header. Cloudflare sends CF-Connecting-IP. If you sit behind a
# different CDN/proxy, set REAL_IP_HEADER=X-Forwarded-For and TRUSTED_PROXIES to its
# ranges -- everything else in this script is CDN-agnostic.
REAL_IP_HEADER="${REAL_IP_HEADER:-CF-Connecting-IP}"

# --- Jail tunables -----------------------------------------------------------
# xampp-dos still counts EVERY request, but the ceiling is now sized for a real
# client IP running a polling game client (and for a household/CGNAT address
# carrying several players), not for a Cloudflare edge carrying all of them.
# 1200/60s = 20 req/s sustained for a full minute from ONE address. A player
# polling a few times a second plus asset loads is an order of magnitude under
# that; a genuine flood is an order of magnitude over it.
BANTIME="${BANTIME:-600}"      # 10 min -- a false positive self-heals fast
FINDTIME="${FINDTIME:-60}"
MAXRETRY="${MAXRETRY:-1200}"

# [recidive] stock is 5 bans/day -> 1 WEEK. That turns one bad evening into a
# week-long outage. De-escalated to 10 bans/day -> 1 day.
RECIDIVE_MAXRETRY="${RECIDIVE_MAXRETRY:-10}"
RECIDIVE_FINDTIME="${RECIDIVE_FINDTIME:-1d}"
RECIDIVE_BANTIME="${RECIDIVE_BANTIME:-86400}"

# Always-ignored, independent of Cloudflare. Note 172.16.0.0/12 (private) does NOT
# overlap Cloudflare's 172.64.0.0/13 -- they are different halves of 172/8.
LOCAL_IGNORE="127.0.0.1/8 ::1 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16 169.254.0.0/16 fc00::/7"

# ---------------------------------------------------------------------------
# Flags
# ---------------------------------------------------------------------------
SKIP_APACHE=0; SKIP_RESTART=0; NO_UNBAN=0; OFFLINE=0
for arg in "$@"; do
  case "$arg" in
    --skip-apache)  SKIP_APACHE=1 ;;   # jail-only: no mod_remoteip / LogFormat change
    --skip-restart) SKIP_RESTART=1 ;;  # apply config but do not restart LAMPP
    --no-unban)     NO_UNBAN=1 ;;      # leave currently-held bans in place
    --offline)      OFFLINE=1 ;;       # do not fetch cloudflare.com; use pinned list
    --yes|-y)       : ;;               # accepted for symmetry with the other scripts
    -h|--help)
      grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done

TS="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$(cd "$(dirname "$0")" && pwd)/newhost-backups-$TS"

log()  { printf '\033[1;36m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ok\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !!\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

backup() {
  local f="$1"
  [ -f "$f" ] || return 0
  mkdir -p "$BACKUP_DIR"
  local dest="$BACKUP_DIR/$(echo "$f" | sed 's#^/##; s#/#_#g')"
  [ -f "$dest" ] || cp -p "$f" "$dest"
}

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
[ "$(id -u)" -eq 0 ] || die "must run as root (sudo)."
[ -d "$LAMPP_ROOT" ] || die "LAMPP not found at $LAMPP_ROOT (set LAMPP_ROOT=...)."
command -v python3 >/dev/null 2>&1 || die "python3 not found (fail2ban itself needs it)."
log "Tuning fail2ban for $LAMPP_ROOT  (backups -> $BACKUP_DIR)"

# ---------------------------------------------------------------------------
# 1. fail2ban present
# ---------------------------------------------------------------------------
if ! command -v fail2ban-client >/dev/null 2>&1; then
  command -v apt-get >/dev/null 2>&1 || die "fail2ban missing and apt-get not found; install it manually."
  log "Installing fail2ban"
  DEBIAN_FRONTEND=noninteractive apt-get update -y
  DEBIAN_FRONTEND=noninteractive apt-get install -y fail2ban
  ok "fail2ban installed"
else
  ok "fail2ban already installed"
fi

# ---------------------------------------------------------------------------
# 2. Cloudflare ranges
# ---------------------------------------------------------------------------
# Pinned fallback: Cloudflare's published ranges as of 2026-08. These change rarely
# (the v4 list has been stable for years), but the live fetch is preferred so a box
# provisioned a year from now is not trusting a stale table. The cached copy in
# $CF_IPS_CACHE is the third fallback, for a re-run with no network.
read -r -d '' CF_PINNED <<'PINNED' || true
173.245.48.0/20
103.21.244.0/22
103.22.200.0/22
103.31.4.0/22
141.101.64.0/18
108.162.192.0/18
190.93.240.0/20
188.114.96.0/20
197.234.240.0/22
198.41.128.0/17
162.158.0.0/15
104.16.0.0/13
104.24.0.0/14
172.64.0.0/13
131.0.72.0/22
2400:cb00::/32
2606:4700::/32
2803:f800::/32
2405:b500::/32
2405:8100::/32
2a06:98c0::/29
2c0f:f248::/32
PINNED

# looks_like_cidrs <text> - every non-empty line must be a bare CIDR. Guards against
# a captive portal / error page being written into RemoteIPTrustedProxy, which would
# fail the Apache config test at best and trust the wrong network at worst.
looks_like_cidrs() {
  local t="$1"
  [ -n "$t" ] || return 1
  printf '%s\n' "$t" | grep -qE '^[0-9a-fA-F:.]+/[0-9]{1,3}$' || return 1
  ! printf '%s\n' "$t" | grep -qvE '^[[:space:]]*$|^[0-9a-fA-F:.]+/[0-9]{1,3}$'
}

CF_IPS=""
CF_SOURCE="pinned"
if [ "$OFFLINE" -eq 0 ] && command -v curl >/dev/null 2>&1; then
  fetched="$( { curl -fsS --max-time 10 https://www.cloudflare.com/ips-v4; echo; \
                curl -fsS --max-time 10 https://www.cloudflare.com/ips-v6; } 2>/dev/null \
              | tr -d '\r' | sed '/^[[:space:]]*$/d' )" || fetched=""
  if looks_like_cidrs "$fetched"; then
    CF_IPS="$fetched"; CF_SOURCE="cloudflare.com (live)"
  else
    warn "live Cloudflare IP fetch failed or returned junk; falling back"
  fi
fi
if [ -z "$CF_IPS" ] && [ -f "$CF_IPS_CACHE" ]; then
  cached="$(sed '/^[[:space:]]*$/d; /^#/d' "$CF_IPS_CACHE")"
  if looks_like_cidrs "$cached"; then CF_IPS="$cached"; CF_SOURCE="cached $CF_IPS_CACHE"; fi
fi
[ -n "$CF_IPS" ] || CF_IPS="$CF_PINNED"
mkdir -p "$(dirname "$CF_IPS_CACHE")"
{ printf '# Cloudflare ranges, source: %s, written %s by newhost/harden-fail2ban.sh\n' "$CF_SOURCE" "$TS"
  printf '%s\n' "$CF_IPS"; } > "$CF_IPS_CACHE"
ok "Cloudflare ranges: $(printf '%s\n' "$CF_IPS" | wc -l | tr -d ' ') entries from $CF_SOURCE"

IGNORE_LIST="$LOCAL_IGNORE $(printf '%s' "$CF_IPS" | tr '\n' ' ')"

# ---------------------------------------------------------------------------
# 3. Apache: real client IP
# ---------------------------------------------------------------------------
# Without this, EVERY log line carries a Cloudflare edge address, so every jail on
# the box is aiming at the CDN. The ignoreip in step 4 stops that from taking the
# site down, but it also means the jails protect nothing -- an actual attacker
# proxied through Cloudflare is indistinguishable from a player. mod_remoteip is
# what makes the jails meaningful again.
APACHE_CHANGED=0
HTTPD_CONF_MODIFIED=0
if [ "$SKIP_APACHE" -eq 0 ]; then
  log "Configuring real-client-IP logging (mod_remoteip, header $REAL_IP_HEADER)"
  if [ ! -f "$LAMPP_ROOT/modules/mod_remoteip.so" ]; then
    warn "mod_remoteip.so not present in $LAMPP_ROOT/modules — SKIPPING the Apache half."
    warn "  Bans will keep targeting CDN edges; the ignoreip whitelist below is then"
    warn "  the ONLY thing protecting the site. Fix by installing a build with mod_remoteip."
  else
    # LoadModule: XAMPP ships the line commented. Uncomment in place if present so we
    # never end up with two LoadModule lines for one module.
    NEED_LOADMODULE=1
    if grep -Eq '^[[:space:]]*LoadModule[[:space:]]+remoteip_module' "$HTTPD_CONF"; then
      NEED_LOADMODULE=0
      ok "LoadModule remoteip_module already active in httpd.conf"
    elif grep -Eq '^[[:space:]]*#[[:space:]]*LoadModule[[:space:]]+remoteip_module' "$HTTPD_CONF"; then
      backup "$HTTPD_CONF"; HTTPD_CONF_MODIFIED=1; APACHE_CHANGED=1
      sed -ri 's|^[[:space:]]*#[[:space:]]*(LoadModule[[:space:]]+remoteip_module.*)$|\1|' "$HTTPD_CONF"
      NEED_LOADMODULE=0
      ok "uncommented LoadModule remoteip_module in httpd.conf"
    fi

    backup "$REMOTEIP_CONF"
    {
      printf '# Managed by newhost/harden-fail2ban.sh — regenerated every run, do not hand-edit.\n'
      printf '# Restores the real client IP from the CDN so logs, fail2ban and the app all see\n'
      printf '# the player rather than the edge that relayed them.\n'
      [ "$NEED_LOADMODULE" -eq 1 ] && printf 'LoadModule remoteip_module modules/mod_remoteip.so\n' || true
      printf '<IfModule remoteip_module>\n'
      printf '    RemoteIPHeader %s\n' "$REAL_IP_HEADER"
      printf '    # Only these networks are allowed to assert a client IP. A request arriving\n'
      printf '    # DIRECTLY at the origin cannot spoof the header, because its own address is\n'
      printf '    # not on this list and the header is ignored.\n'
      printf '%s\n' "$CF_IPS" | sed 's|^|    RemoteIPTrustedProxy |'
      printf '</IfModule>\n'
    } > "$REMOTEIP_CONF"
    ok "wrote $REMOTEIP_CONF"

    if grep -Fq 'etc/extra/httpd-remoteip.conf' "$HTTPD_CONF"; then
      ok "httpd.conf already includes httpd-remoteip.conf"
    else
      backup "$HTTPD_CONF"; HTTPD_CONF_MODIFIED=1; APACHE_CHANGED=1
      printf '\n# real client IP behind the CDN (newhost/harden-fail2ban.sh)\nInclude etc/extra/httpd-remoteip.conf\n' >> "$HTTPD_CONF"
      ok "added Include for httpd-remoteip.conf"
    fi

    # LogFormat: %h logs the CONNECTING address. %a is documented to carry the
    # useragent (real client) address once mod_remoteip has rewritten it, so switch
    # the nickname definitions rather than every CustomLog line -- the per-vhost
    # `CustomLog "logs/<app>-access_log" combined` written by provision-vhost.sh
    # inherits the fix for free.
    # This is an in-place edit of httpd.conf, NOT an appended Include, because a
    # LogFormat nickname is resolved where the CustomLog is parsed: an appended
    # redefinition would come too late for httpd.conf's own CustomLog.
    if grep -Eq '^[[:space:]]*LogFormat[[:space:]]+"%h ' "$HTTPD_CONF"; then
      backup "$HTTPD_CONF"; HTTPD_CONF_MODIFIED=1; APACHE_CHANGED=1
      sed -ri 's|^([[:space:]]*LogFormat[[:space:]]+")%h |\1%a |' "$HTTPD_CONF"
      ok "LogFormat: %h -> %a (log the real client, not the edge)"
    else
      ok "LogFormat already logs %a (or is defined elsewhere)"
    fi
    # Anything else on the box still defining its own %h format will keep logging
    # edge addresses; name it rather than silently editing files this script does not own.
    other_fmt="$(grep -rlE '^[[:space:]]*LogFormat[[:space:]]+"%h ' "$LAMPP_ROOT/etc/extra/" 2>/dev/null || true)"
    if [ -n "$other_fmt" ]; then
      warn "these also define a %h LogFormat — review by hand: $(echo $other_fmt | tr '\n' ' ')"
    fi
  fi
else
  warn "skipping Apache changes (--skip-apache) — jails will still see CDN edge IPs"
fi

# ---------------------------------------------------------------------------
# 4. fail2ban filter + jails
# ---------------------------------------------------------------------------
log "Writing fail2ban filter + jail.local"

backup /etc/fail2ban/filter.d/xampp-dos.conf
cat > /etc/fail2ban/filter.d/xampp-dos.conf <<'FILTER'
# xampp-dos — counts every HTTP request per client IP (combined access log).
# Managed by newhost/harden-fail2ban.sh.
#
# The regex deliberately stayed broad: a game client's traffic is almost entirely
# dynamic PHP polling, so filtering to "suspicious" paths would exempt exactly what
# an attacker would flood. Volume is the only honest signal here, which is why the
# THRESHOLD (jail.local maxretry) is what was retuned, not this pattern.
[Definition]
failregex = ^<HOST> -.*"(GET|POST|HEAD|PUT|DELETE|OPTIONS|PATCH).*"
ignoreregex =
FILTER
ok "wrote filter.d/xampp-dos.conf"

# logpath: a box converted by provision-vhost.sh sends each site's traffic to its own
# `logs/<app>-access_log` via a per-vhost CustomLog, so the shared access_log alone can
# be nearly empty there. Watch both. The glob is only emitted when it currently matches
# something -- fail2ban complains about a logpath that resolves to nothing.
ACCESS_PATHS="$ACCESS_LOG"
if compgen -G "$LAMPP_ROOT/logs/*-access_log" >/dev/null 2>&1; then
  ACCESS_PATHS="$ACCESS_LOG
           $LAMPP_ROOT/logs/*-access_log"
  ok "per-vhost access logs detected — added logs/*-access_log to the watch list"
fi

backup /etc/fail2ban/jail.local
cat > /etc/fail2ban/jail.local <<JAIL
# Managed by newhost/harden-fail2ban.sh — regenerated every run, do not hand-edit.
[DEFAULT]
bantime  = $BANTIME
findtime = $FINDTIME
maxretry = $MAXRETRY
backend  = auto
banaction = iptables-multiport

# Never ban the CDN. With mod_remoteip working these addresses should not appear in
# the logs at all -- this list is the safety net for the window where it is not
# (module missing, a vhost with its own %h LogFormat, a config revert). Banning one
# edge takes out every player routed through it, which is how the site went dark on
# 2026-08-11. Source: $CF_SOURCE
ignoreip = $IGNORE_LIST

[xampp-dos]
enabled  = true
port     = http,https
filter   = xampp-dos
logpath  = $ACCESS_PATHS
maxretry = $MAXRETRY
findtime = $FINDTIME
bantime  = $BANTIME

[apache-auth]
enabled  = true
port     = http,https
logpath  = $ERROR_LOG

[apache-badbots]
enabled  = true
port     = http,https
logpath  = $ACCESS_PATHS

[apache-overflows]
enabled  = true
port     = http,https
logpath  = $ERROR_LOG

# Stock recidive is 5 bans/day -> 1 week, which compounds a single bad evening into a
# week-long outage. Repeat offenders still escalate, just not off a cliff.
[recidive]
enabled  = true
maxretry = $RECIDIVE_MAXRETRY
findtime = $RECIDIVE_FINDTIME
bantime  = $RECIDIVE_BANTIME
JAIL
ok "wrote jail.local (maxretry=$MAXRETRY/$FINDTIME s, bantime=$BANTIME s)"

# ---------------------------------------------------------------------------
# 5. Apply the jails FIRST
# ---------------------------------------------------------------------------
# Deliberately ahead of the Apache validation below, which can `die`. The jail
# leniency + whitelist are what stop the box going dark; the mod_remoteip half only
# makes the jails *useful*. A run that fails on Apache must still leave the site
# protected from itself, not half-applied with the old 300-req ceiling still live.
log "Restarting fail2ban"
systemctl enable fail2ban >/dev/null 2>&1 || true
if ! systemctl restart fail2ban 2>/dev/null; then
  service fail2ban restart 2>/dev/null || warn "could not restart fail2ban; start it manually"
fi
sleep 2
fail2ban-client ping >/dev/null 2>&1 && ok "fail2ban is up" || warn "fail2ban did not answer a ping"

# ---------------------------------------------------------------------------
# 6. Release bans the new whitelist covers
# ---------------------------------------------------------------------------
# A box that already ate this outage is still holding CF edges in iptables (up to a
# week, via recidive). Reconfiguring does not release an existing ban, so clear the
# ones that are now whitelisted. Anything NOT on the whitelist is left alone -- real
# scanners stay banned.
if [ "$NO_UNBAN" -eq 0 ]; then
  log "Releasing currently-held bans that the whitelist now covers"
  jails="$(fail2ban-client status 2>/dev/null | sed -n 's/.*Jail list:[[:space:]]*//p' | tr ',' ' ' || true)"
  released=0; kept=0
  for jail in $jails; do
    banned="$(fail2ban-client status "$jail" 2>/dev/null | sed -n 's/.*Banned IP list:[[:space:]]*//p' || true)"
    for ip in $banned; do
      if IGN="$IGNORE_LIST" python3 - "$ip" <<'PY'
import ipaddress, os, sys
try:
    addr = ipaddress.ip_address(sys.argv[1])
except ValueError:
    sys.exit(1)
for net in os.environ["IGN"].split():
    try:
        if addr in ipaddress.ip_network(net, strict=False):
            sys.exit(0)
    except ValueError:
        continue
sys.exit(1)
PY
      then
        fail2ban-client set "$jail" unbanip "$ip" >/dev/null 2>&1 \
          && { ok "unbanned $ip from $jail"; released=$((released+1)); } \
          || warn "could not unban $ip from $jail"
      else
        kept=$((kept+1))
      fi
    done
  done
  ok "released $released whitelisted ban(s); left $kept non-whitelisted ban(s) in place"
else
  warn "skipping unban (--no-unban)"
fi

# ---------------------------------------------------------------------------
# 7. Validate the Apache edits, then apply them
# ---------------------------------------------------------------------------
if [ "$APACHE_CHANGED" -eq 1 ]; then
  if [ -x "$LAMPP_ROOT/bin/httpd" ] && ! "$LAMPP_ROOT/bin/httpd" -t >/dev/null 2>&1; then
    warn "apache config test FAILED — output follows:"
    "$LAMPP_ROOT/bin/httpd" -t || true
    # Same reasoning as provision-vhost.sh: Apache keeps serving from its in-memory
    # config, so a broken httpd.conf is invisible until some unrelated restart hours
    # later — a reboot, a cron job, an unrelated deploy — and then the box goes down
    # with no obvious cause. Put it back so a failed run cannot take the box down.
    if [ "$HTTPD_CONF_MODIFIED" -eq 1 ]; then
      restored="$BACKUP_DIR/$(echo "$HTTPD_CONF" | sed 's#^/##; s#/#_#g')"
      if [ -f "$restored" ] && cp -p "$restored" "$HTTPD_CONF"; then
        ok "reverted $HTTPD_CONF to its pre-run state — Apache will still start"
      else
        warn "could not revert $HTTPD_CONF — restore by hand from $BACKUP_DIR BEFORE any restart"
      fi
    fi
    rm -f "$REMOTEIP_CONF"
    warn "the fail2ban half IS applied and live (lenient jails + CDN whitelist)."
    die "only the real-client-IP half failed. Fix the config above (originals in $BACKUP_DIR), then re-run."
  fi
  ok "apache config test passed"
  if [ "$SKIP_RESTART" -eq 0 ]; then
    log "Restarting LAMPP to apply the Apache changes"
    "$LAMPP_ROOT/lampp" restart || warn "LAMPP restart failed; restart it manually."
  else
    warn "skipping LAMPP restart (--skip-restart) — real-client-IP logging is NOT live yet"
  fi
else
  ok "no Apache changes needed; LAMPP not restarted"
fi

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
cat <<SUMMARY

fail2ban hardening complete.  Backups: $BACKUP_DIR

  rate limit  : $MAXRETRY requests / ${FINDTIME}s  ->  ${BANTIME}s ban   (was 300/60s -> 3600s)
  recidive    : $RECIDIVE_MAXRETRY bans / $RECIDIVE_FINDTIME -> ${RECIDIVE_BANTIME}s   (was 5/1d -> 1 week)
  whitelist   : loopback + RFC1918 + Cloudflare ($CF_SOURCE)
  real client : $( [ "$APACHE_CHANGED" -eq 1 ] && echo "mod_remoteip via $REAL_IP_HEADER, LogFormat %a" || echo "unchanged this run — see warnings above" )

Verify:
  fail2ban-client status
  fail2ban-client status xampp-dos          # 'Banned IP list' should not contain a CDN edge
  fail2ban-client get xampp-dos ignoreip | tr ' ' '\n' | grep -c .

  # The load-bearing check: after a page load from a browser, the LAST access-log line
  # must show YOUR public address, not a 104.x/172.6x/172.7x Cloudflare edge.
  tail -3 $ACCESS_LOG

  # And confirm no jail is still holding a CDN address:
  iptables -S | grep -E '104\.1[6-9]|104\.2[0-7]|172\.6[4-9]|172\.7[01]|162\.158' || echo "clean"

If the tail still shows edge IPs, mod_remoteip is not active — re-run and read the
warnings, or check that $REMOTEIP_CONF is Included from $HTTPD_CONF.
SUMMARY
