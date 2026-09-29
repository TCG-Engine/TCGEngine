#!/bin/bash
# Prune saved bot decision traces (SWUSim/DevTools/traces/, gitignored).
#
# Every self-play run saves one trace file per game (DevTools/SWUSimBotSelfPlayTest.php). That is the point —
# a run whose trace was never written cannot be reconstructed afterwards — but it is ~60 KB per game with the
# board snapshot, so a 2,000-game canary is ~120 MB and a 10x500 field run ~600 MB. This reclaims it.
#
#   bash SWUSim/DevTools/prune-bot-traces.sh                 # list every run dir, newest first
#   bash SWUSim/DevTools/prune-bot-traces.sh --days=14 --yes # delete run dirs untouched for 14+ days
#   bash SWUSim/DevTools/prune-bot-traces.sh --keep=5 --yes  # keep the 5 most recent run dirs, delete the rest
#
# DRY RUN BY DEFAULT: it prints and deletes nothing until --yes. A trace is the only record of what a finished
# run was doing, and unlike the run itself it cannot be regenerated without replaying the exact seed block,
# arm and deck pair — so deleting one is not obviously recoverable and is never the default.
#
# ⚠ Runs on the HOST as well as in the container (the traces dir is the mounted repo, same path both sides),
# and macOS ships bash 3.2 — so no mapfile/readarray, no `find -printf`, and `stat`/`date` flags are probed
# rather than assumed. The first draft used all four and died on line 33 of a host run.
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/traces"
DAYS=""; KEEP=""; YES=0
for a in "$@"; do
  case "$a" in
    --days=*) DAYS="${a#*=}" ;;
    --keep=*) KEEP="${a#*=}" ;;
    --yes|-y) YES=1 ;;
    --root=*) ROOT="${a#*=}" ;;
    *) echo "unknown argument: $a" >&2; exit 2 ;;
  esac
done
[ -n "$DAYS" ] && [ -n "$KEEP" ] && { echo "--days and --keep are alternatives, not a pair" >&2; exit 2; }
case "${DAYS}${KEEP}" in *[!0-9]*) echo "--days/--keep take a number" >&2; exit 2 ;; esac
[ -d "$ROOT" ] || { echo "no traces at $ROOT — nothing to prune"; exit 0; }

# BSD `stat -f %m` vs GNU `stat -c %Y`; BSD `date -r <epoch>` vs GNU `date -d @<epoch>`.
if stat -f %m "$ROOT" >/dev/null 2>&1; then
  mtime() { stat -f %m "$1"; }; fromepoch() { date -r "$1" +%Y-%m-%d; }
else
  mtime() { stat -c %Y "$1"; }; fromepoch() { date -d "@$1" +%Y-%m-%d; }
fi
# Newest file INSIDE a run dir, via one `ls -t` (POSIX, sorts by mtime) plus one stat — not a stat per game,
# which would be thousands of calls on a finished sweep. Falls back to the directory's own mtime when empty.
freshness() {
  local newest
  newest=$(ls -t "$1" 2>/dev/null | head -1)
  if [ -n "$newest" ] && [ -e "$1/$newest" ]; then mtime "$1/$newest"; else mtime "$1"; fi
}

TMP=$(mktemp -t bottraceprune.XXXXXX) || exit 2
trap 'rm -f "$TMP"' EXIT
ndirs=0
for d in "$ROOT"/*; do
  [ -d "$d" ] || continue
  ndirs=$((ndirs + 1))
  printf '%s\t%s\n' "$(freshness "$d")" "$d" >> "$TMP"
done
[ "$ndirs" -eq 0 ] && { echo "no run directories under $ROOT"; exit 0; }

# Newest first, so --keep=N keeps the head and --days drops the tail.
sorted=$(sort -rn "$TMP")
doomed=""; i=0
while IFS=$'\t' read -r t d; do
  [ -z "$d" ] && continue
  if [ -n "$KEEP" ]; then
    [ "$i" -ge "$KEEP" ] && doomed="$doomed$t\t$d\n"
  elif [ -n "$DAYS" ]; then
    [ "$t" -lt $(( $(date +%s) - DAYS * 86400 )) ] && doomed="$doomed$t\t$d\n"
  else
    doomed="$doomed$t\t$d\n"   # listing only
  fi
  i=$((i + 1))
done <<EOF
$sorted
EOF

echo "traces at $ROOT — $ndirs run dir(s), $(du -sh "$ROOT" 2>/dev/null | cut -f1) total"
[ -z "$doomed" ] && { echo "nothing matches"; exit 0; }
n=0
while IFS=$'\t' read -r t d; do
  [ -z "$d" ] && continue
  n=$((n + 1))
  printf '  %-8s %5s games  %s  %s\n' "$(du -sh "$d" 2>/dev/null | cut -f1)" \
    "$(find "$d" -name '*.jsonl' | wc -l | tr -d ' ')" "$(fromepoch "$t")" "$(basename "$d")"
done <<EOF
$(printf '%b' "$doomed")
EOF

if [ "$YES" != 1 ]; then
  if [ -z "$KEEP" ] && [ -z "$DAYS" ]; then
    echo "(listing only — pass --days=N or --keep=N to select, then --yes to delete)"
  else
    echo "(dry run — re-run with --yes to delete the $n dir(s) above)"
  fi
  exit 0
fi
while IFS=$'\t' read -r t d; do
  [ -n "$d" ] && [ -d "$d" ] && rm -rf "$d"
done <<EOF
$(printf '%b' "$doomed")
EOF
echo "deleted $n run dir(s) — $(du -sh "$ROOT" 2>/dev/null | cut -f1) remains"
