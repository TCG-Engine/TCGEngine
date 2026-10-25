#!/bin/bash
# Meta-baseline bot run (skill `swusim-ash-meta-bot-run`): replay EVERY real match of a frozen tournament set with both players' actual
# lists, sideboards folded in (SUPERSET=1 — a general test, not a realistic game 1), once from each seat (sweep_fixtures plays both orders),
# on the seed block the baseline used, so each game pairs with the baseline's.  Runs INSIDE the SWUSim container:
#   docker exec -d -w /var/www/html/TCGEngine -e PHP_INI_SCAN_DIR=":/tmp/swusim-ini" otmtcge-swusim-web-server-1 \
#     bash SWUSim/DevTools/rl/meta_baseline/run.sh <meta dir> <out dir> [variant]
# variant = a stack variant for BOTH seats ('' = the full stack; try-<proposal>, no-<feature>, …). LIMIT=<n> (env) plays only the first n
# matches — a smoke test. Resumable (sweep_fixtures skips finished games). Writes <out>/DONE when finished.
set -u
cd /var/www/html/TCGEngine
META=$1; OUT=$2; VARIANT=${3:-}
mkdir -p "$OUT" /tmp/swusim-ini /tmp/swusim-opcache
[ -f /tmp/swusim-ini/zz-filecache.ini ] || echo "opcache.file_cache=/tmp/swusim-opcache" > /tmp/swusim-ini/zz-filecache.ini
[ -d "$OUT/fx" ] || tar -xzf "$META/fixtures.tar.gz" -C "$OUT"
# Pairs = the real matches (rematches repeat a pair; with deterministic bots that replays one game, so they are deduplicated).
gzip -dc "$META/matches.tsv.gz" | awk -F'\t' '{print $1" "$2}' | awk '!seen[$0]++' > "$OUT/pairs.all"
if [ -n "${LIMIT:-}" ]; then head -n "$LIMIT" "$OUT/pairs.all" > "$OUT/pairs.txt"; else cp "$OUT/pairs.all" "$OUT/pairs.txt"; fi
echo "[meta-run] $(wc -l < "$OUT/pairs.txt") pairs x 2 seats, variant='${VARIANT}', meta=$META — $(date -u +%FT%TZ)" > "$OUT/run.log"
SUPERSET=1 PAIRS_FILE="$OUT/pairs.txt" SEED_PREFIX=tb VARIANT="$VARIANT" \
  bash SWUSim/DevTools/rl/sweep_fixtures.sh 1 10 "$OUT/sweep" "$OUT/fx" >> "$OUT/run.log" 2>&1
ln -sfn sweep/results.tsv "$OUT/results.tsv"
echo "[meta-run] done — $(wc -l < "$OUT/sweep/results.tsv") games — $(date -u +%FT%TZ)" >> "$OUT/run.log"
echo DONE > "$OUT/DONE"
