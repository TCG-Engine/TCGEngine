#!/bin/bash
# Per-deck screen (fidelity plan Task 6): one deck's pairs file, several VARIANT arms, sequentially. Inside the container.
#   bash SWUSim/DevTools/rl/deck_screen.sh <pairs file> <tag> <seeds> <arm>...   ('base' = no variant)
set -u
cd /var/www/html/TCGEngine
PF=$1; TAG=$2; SEEDS=$3; shift 3
for arm in "$@"; do
  out=/tmp/deck_${TAG}_$(echo "$arm" | tr ':@/' '___')
  if [ "$arm" = base ]; then PAIRS_FILE=$PF bash SWUSim/DevTools/rl/sweep_fixtures.sh "$SEEDS" 10 "$out" >/dev/null 2>&1
  else PAIRS_FILE=$PF VARIANT="$arm" bash SWUSim/DevTools/rl/sweep_fixtures.sh "$SEEDS" 10 "$out" >/dev/null 2>&1; fi
  echo "[deck] $arm ok=$(grep -c '"fail":0' "$out/results.tsv")/$(wc -l < "$out/jobs.txt")"
done
echo "[deck] done"
