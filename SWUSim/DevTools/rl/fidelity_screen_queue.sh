#!/bin/bash
# Fidelity screen queue: one sweep_fixtures.sh SCREEN arm per VARIANT, sequentially, each in its own outdir.
#   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 bash SWUSim/DevTools/rl/fidelity_screen_queue.sh <seeds> <arm>...
# Arms are VARIANT strings (w-<probe>, try-<proposal>, no-<feature>, no-guide:<name>). Outdir /tmp/fid_screen_<arm>.
set -u
cd /var/www/html/TCGEngine
SEEDS=$1; shift
for arm in "$@"; do
  out=/tmp/fid_screen_$(echo "$arm" | tr ':@/' '___')
  PAIRS_FILE=SWUSim/DevTools/rl/fidelity_pairs_ash.txt VARIANT="$arm" bash SWUSim/DevTools/rl/sweep_fixtures.sh "$SEEDS" 10 "$out"
  echo "[queue] $arm ok=$(grep -c '"fail":0' "$out/results.tsv")/$(wc -l < "$out/jobs.txt")"
done
echo "[queue] done"
