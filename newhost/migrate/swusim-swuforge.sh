#!/usr/bin/env bash
#
# swusim-swuforge.sh - install the cron job that sends finished Petranaki games to SWU Forge.
# Run ON THE NATIVE LAMP BOX as root, once (safe to re-run: it rewrites one file only when it changed).
#
# A finished game only QUEUES its delivery (a row in swuforge_deliveries, SWUSim/SWUForgeDelivery.php). The
# worker page SWUSim/SWUForgeDeliverWorker.php sends what is due; without this job nothing ever leaves the queue.
# The worker is an HTTP page, not a CLI script, because the database credentials reach PHP only through the
# vhost's Apache SetEnv. It answers only the box itself (127.0.0.1), so cron curls it over loopback.
#
# Writes /etc/cron.d/petranaki-swuforge: one curl every INTERVAL seconds (cron's finest step is a minute, so
# the extra runs in each minute start after a `sleep`), run as www-data. Overlapping runs are safe: the worker
# claims each row before sending it. Until the box has $swuforgeBaseURL and $swuforgeOAuthKey in
# APIKeys/APIKeys.php the worker sends nothing and answers {"attempted":0}.
#
# Usage:
#   sudo bash swusim-swuforge.sh                 # install (every 20 s), then call the worker once to prove it answers
#   sudo bash swusim-swuforge.sh --interval 30   # 10, 12, 15, 20, 30 or 60 seconds
#   bash swusim-swuforge.sh --check              # print the cron file it would write; changes nothing
#   sudo bash swusim-swuforge.sh --remove        # delete the cron file (queued games then wait)
# Environment:
#   SITE_HOST=petranaki.net   APP_ROOT=/var/www/html/TCGEngine   WEB_USER=www-data
#
set -euo pipefail

INTERVAL=20; CHECK=0; REMOVE=0
while [ $# -gt 0 ]; do
  case "$1" in
    --interval) INTERVAL="${2:-}"; shift ;;
    --check)    CHECK=1 ;;
    --remove)   REMOVE=1 ;;
    -h|--help)  grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    *) echo "Unknown option: $1" >&2; exit 2 ;;
  esac
  shift
done

SITE_HOST="${SITE_HOST:-petranaki.net}"
APP_ROOT="${APP_ROOT:-/var/www/html/TCGEngine}"
WEB_USER="${WEB_USER:-www-data}"
CRON_FILE=/etc/cron.d/petranaki-swuforge   # cron.d ignores names with a dot
WORKER_URL="https://$SITE_HOST/TCGEngine/SWUSim/SWUForgeDeliverWorker.php"
# -k: the request never leaves the box (--resolve pins it to 127.0.0.1), and the origin's certificate (Cloudflare
# Origin CA or self-signed) is not one curl can verify. --max-time: the worker's own limit is 600 s.
CURL="curl -sk --max-time 590 --resolve $SITE_HOST:443:127.0.0.1 -o /dev/null $WORKER_URL"

log()  { printf '\033[1;36m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ok\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !!\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

case "$INTERVAL" in 10|12|15|20|30|60) ;; *) die "--interval must divide a minute: 10, 12, 15, 20, 30 or 60 (got '$INTERVAL')" ;; esac

cron_body() {
  echo "# Managed by newhost/migrate/swusim-swuforge.sh — Petranaki → SWU Forge game delivery, every ${INTERVAL}s"
  echo "SHELL=/bin/sh"
  local s
  for ((s = 0; s < 60; s += INTERVAL)); do
    if [ "$s" -eq 0 ]; then echo "* * * * * $WEB_USER $CURL"
    else echo "* * * * * $WEB_USER sleep $s; $CURL"; fi
  done
}

if [ "$CHECK" = 1 ]; then
  log "$CRON_FILE would contain:"
  cron_body
  exit 0
fi

[ "$(id -u)" = 0 ] || die "run as root (sudo bash $0)"

if [ "$REMOVE" = 1 ]; then
  if [ -f "$CRON_FILE" ]; then rm -f "$CRON_FILE"; ok "removed $CRON_FILE — queued games now wait in swuforge_deliveries"
  else ok "$CRON_FILE was not installed"; fi
  exit 0
fi

[ -f "$APP_ROOT/SWUSim/SWUForgeDeliverWorker.php" ] || die "no worker at $APP_ROOT/SWUSim/SWUForgeDeliverWorker.php — pull the code first (or set APP_ROOT)"
id "$WEB_USER" >/dev/null 2>&1 || die "no user '$WEB_USER' (set WEB_USER)"
command -v curl >/dev/null || die "curl is not installed (apt-get install -y curl)"
command -v cron >/dev/null || [ -x /usr/sbin/cron ] || die "cron is not installed (apt-get install -y cron)"
systemctl enable --now cron >/dev/null 2>&1 || warn "could not start the cron service — the job will not run until it is"

log "Installing $CRON_FILE (every ${INTERVAL}s, as $WEB_USER)"
tmp="$(mktemp)"; cron_body > "$tmp"
if [ -f "$CRON_FILE" ] && cmp -s "$tmp" "$CRON_FILE"; then rm -f "$tmp"; ok "already current"
else install -m 644 "$tmp" "$CRON_FILE"; rm -f "$tmp"; ok "written — cron picks it up within a minute"; fi

# One call now, the way cron will make it, to prove the vhost, TLS and the loopback-only check all let it through.
log "Calling the worker once as $WEB_USER"
reply="$(runuser -u "$WEB_USER" -- curl -sk --max-time 590 --resolve "$SITE_HOST:443:127.0.0.1" "$WORKER_URL" || true)"
case "$reply" in
  *'"attempted"'*) ok "worker answered: $reply" ;;
  *'local only'*)  die "the worker refused the call as not local: $reply — Apache does not see 127.0.0.1 as the client (check mod_remoteip's trusted proxies)" ;;
  '')              die "no answer from $WORKER_URL over 127.0.0.1 — is Apache serving $SITE_HOST on 443?" ;;
  *)               die "unexpected answer from the worker: $(printf '%s' "$reply" | head -c 300)" ;;
esac
ok "until APIKeys/APIKeys.php has \$swuforgeBaseURL and \$swuforgeOAuthKey, every run answers {\"attempted\":0} and sends nothing"
