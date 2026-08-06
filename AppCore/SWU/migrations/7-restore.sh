#!/bin/bash
# Step 7 — restore service. Lifts BOTH maintenance layers, in the right order, with a gate.
#
#   ./7-restore.sh --db=swudeck --backup-dir=/var/backups/... [--root=...] [--force]
#
# ⚠ THIS IS THE LAST MOMENT ROLLBACK IS CHEAP. Once real traffic resumes, every stat submitted
# after this point is lost if you later roll back — the *_old tables are a snapshot from before the
# migration and know nothing about them. So this refuses to run unless 6-verify.sh passes.
#
# Order: verify -> lift the Apache block -> lift the write freeze -> confirm. Lifting the write
# freeze first would let writes through while the .htaccess still 503s reads, which is the one
# combination that serves nobody.
set -uo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="${ROOT:-/opt/lampp/htdocs/TCGEngine}"
DB=""; BACKUP_DIR=""; FORCE=0; BASE="${SWU_BASE_URL:-https://swustats.net}"
for arg in "$@"; do
  case "$arg" in
    --db=*)         DB="${arg#--db=}" ;;
    --backup-dir=*) BACKUP_DIR="${arg#--backup-dir=}" ;;
    --root=*)       ROOT_DIR="${arg#--root=}" ;;
    --base=*)       BASE="${arg#--base=}" ;;
    --force)        FORCE=1 ;;
    *) echo "unknown argument: $arg" >&2; exit 2 ;;
  esac
done
[ -n "$DB" ] || { echo "FATAL: --db=<database> is required." >&2; exit 2; }

echo "== gate: 6-verify.sh must pass first"
if bash "$HERE/6-verify.sh" --db="$DB" ${BACKUP_DIR:+--backup-dir="$BACKUP_DIR"} >/tmp/verify.$$ 2>&1; then
  echo "   ok   verification passed"
else
  echo "   VERIFICATION FAILED:"
  grep -E "FAIL" /tmp/verify.$$ | head -12 | sed 's/^/     /'
  if [ "$FORCE" -eq 0 ]; then
    echo
    echo "   Refusing to restore service. Fix it, or roll back with 9-rollback.sh while that is"
    echo "   still cheap. --force overrides, and you own what follows."
    rm -f /tmp/verify.$$; exit 1
  fi
  echo "   --force given; continuing anyway."
fi
rm -f /tmp/verify.$$

echo "== 1/2  lifting the Apache block"
bash "$HERE/1-maintenance.sh" off --root="$ROOT_DIR"

echo "== 2/2  lifting the write freeze"
FLAG="$ROOT_DIR/SWUDeck/maintenance.json"
if [ -e "$FLAG" ]; then rm -f "$FLAG" && echo "   removed $FLAG (its absence IS 'off')"
else echo "   no flag file — write freeze was already off"; fi

echo "== confirm"
for u in "/TCGEngine/SharedUI/MainMenu.php" "/TCGEngine/Stats/DeckMetaStats.php"; do
  printf "   %-42s " "$u"; curl -s -o /dev/null -w "%{http_code}\n" --max-time 20 "$BASE$u"
done
printf "   %-42s " "stats POST (must NOT be 503)"
curl -s -o /dev/null -w "%{http_code}\n" --max-time 20 -X POST -d '{}' "$BASE/TCGEngine/APIs/SubmitManualGameResult.php"

cat <<'NEXT'

   SERVICE IS LIVE. Now run 8-watch.sh for the first hour — that is the window in which a problem
   is still cheap to undo, because every *_old table is still there.
NEXT
