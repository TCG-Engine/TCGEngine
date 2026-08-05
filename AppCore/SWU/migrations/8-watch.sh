#!/bin/bash
# Step 8 — the first hour after restoring service.
#
#   ./8-watch.sh --db=swudeck [--minutes=60] [--interval=300]
#
# Not idle curiosity: this is the window where rollback is still a RENAME. The *_old tables are
# intact, so a problem caught here costs minutes. Caught after §2.10 drops them, it costs a restore
# from dump. Watch it properly.
#
# Three signals, because they fail differently:
#   submissions   are stats still arriving? A flat line means ingress is rejecting everything.
#   unresolvable  is ingress DROPPING rows? Rising = the map disagrees with what clients send.
#   errors        the log, filtered to our own paths.
set -uo pipefail
DB=""; MINUTES=60; INTERVAL=300; LOG="${SWU_ERROR_LOG:-}"
for arg in "$@"; do
  case "$arg" in
    --db=*)       DB="${arg#--db=}" ;;
    --minutes=*)  MINUTES="${arg#--minutes=}" ;;
    --interval=*) INTERVAL="${arg#--interval=}" ;;
    --log=*)      LOG="${arg#--log=}" ;;
    *) echo "unknown argument: $arg" >&2; exit 2 ;;
  esac
done
[ -n "$DB" ] || { echo "FATAL: --db=<database> is required." >&2; exit 2; }
MYCNF="${MYCNF:-}"; MY=("${MYSQL_BIN:-${MYSQL:-mysql}}"); [ -n "$MYCNF" ] && MY+=("$MYCNF")
sql() { "${MY[@]}" -N -B "$DB" -e "$1" 2>/dev/null; }

if [ -z "$LOG" ]; then
  for c in /opt/lampp/logs/error_log /opt/lampp/logs/php_error_log /var/log/apache2/error.log; do
    [ -f "$c" ] && { LOG="$c"; break; }
  done
fi
[ -n "$LOG" ] && echo "watching log: $LOG" || echo "watching log: (none found — pass --log=)"

[ "$(sql 'SELECT 1;')" = "1" ] || { echo "FATAL: cannot query '$DB'. Set MYCNF." >&2; exit 1; }

OLDN=$(sql "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME LIKE '%\\_old';")
echo "rollback still available: $OLDN *_old table(s)"
[ "${OLDN:-0}" -ge 7 ] || echo "  ⚠ fewer than expected — is a RENAME rollback still possible?"

PREV=$(sql "SELECT COUNT(*) FROM completedgame;")
PREV_LOG=0; [ -n "$LOG" ] && [ -f "$LOG" ] && PREV_LOG=$(wc -l < "$LOG")
echo "baseline: completedgame = $PREV rows"
printf "\n%-9s %10s %8s %14s %8s\n" "time" "games" "+new" "unresolvable" "errors"

END=$(( $(date +%s) + MINUTES*60 ))
while [ "$(date +%s)" -lt "$END" ]; do
  sleep "$INTERVAL"
  NOW=$(sql "SELECT COUNT(*) FROM completedgame;")
  DELTA=$(( ${NOW:-0} - ${PREV:-0} ))
  # Anything unresolvable that ingress let through would show up as a non-SET_NNN hero.
  BAD=$(sql "SELECT COUNT(*) FROM completedgame WHERE WinningHero REGEXP '^[0-9]{10}\$' OR LosingHero REGEXP '^[0-9]{10}\$';")
  ERRS=0
  if [ -n "$LOG" ] && [ -f "$LOG" ]; then
    CUR=$(wc -l < "$LOG")
    ERRS=$(tail -n +$((PREV_LOG+1)) "$LOG" 2>/dev/null | grep -ciE "SWU|SubmitGameResult|CardIdentity|maintenance" || true)
    PREV_LOG=$CUR
  fi
  printf "%-9s %10s %8s %14s %8s%s\n" "$(date +%H:%M:%S)" "${NOW:-?}" "+$DELTA" "${BAD:-?}" "$ERRS" \
    "$([ "$DELTA" -eq 0 ] && echo '   <- NO submissions this interval' || true)"
  [ "${BAD:-0}" -gt 0 ] && echo "   ⚠ UUID-shaped heroes are being written — ingress is not translating. Investigate NOW."
  PREV=$NOW
done

cat <<'NEXT'

Window over. If it stayed quiet:
  * leave every *_old table and both backups in place until NEXT weekend (runbook §2.10)
  * dropping them is the true point of no return
NEXT
