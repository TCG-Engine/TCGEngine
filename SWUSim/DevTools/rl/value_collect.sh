#!/bin/bash
# Value-model COLLECTION (spec docs/superpowers/specs/2026-09-19-swusim-value-model-design.md §6).
#   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
#     bash SWUSim/DevTools/rl/value_collect.sh <pairs.tsv> <outdir> [workers=8] [rate=1]
# pairs: deckA-path  styleA  deckB-path  styleB  seed   (value_pairs.py)
# Each game writes $OUT/pos/<A>.<B>.<seed>.jsonl through SWU_VALUE_LOG (SwuValueFeatures.php), then this appends one
# {"result": SWUBOT_METRICS, …} line. Resumable: a file whose last line is a result is skipped. The game folder is
# deleted once read (as ab_profile_test.sh does) so collection never leaks SWUSim/Games directories.
set -u
cd /var/www/html/TCGEngine
PAIRS=${1:?pairs file}; OUT=${2:?outdir}; WORKERS=${3:-8}; RATE=${4:-1}
mkdir -p "$OUT/pos"
export OUT RATE

run_one() {
  local da=$1 sa=$2 db=$3 sb=$4 s=$5
  local na nb f out m g
  na=$(basename "$da" .txt); nb=$(basename "$db" .txt)
  f="$OUT/pos/$na.$nb.$s.jsonl"
  [ -s "$f" ] && tail -1 "$f" | grep -q '"result"' && return 0
  rm -f "$f"
  out=$(SWU_VALUE_LOG="$f" SWU_VALUE_SEED="$na.$nb.$s" SWU_VALUE_LOG_RATE="$RATE" \
        timeout "${GAME_TIMEOUT:-90}" php -d apc.enable_cli=1 -d xdebug.mode=off -d memory_limit=1G \
        DevTools/SWUSimBotSelfPlayTest.php --games=1 --seed="$s" --first-player=1 --verbose \
        --chooser="heuristic-$sa" --chooser2="heuristic-$sb" --deck="$da" --deck2="$db" 2>/dev/null)
  g=$(printf '%s\n' "$out" | grep -m1 'game created:' | awk '{print $NF}')
  m=$(printf '%s\n' "$out" | grep -m1 '^SWUBOT_METRICS ' | cut -c16-)
  [ -z "$m" ] && m='null'
  printf '{"result":%s,"deckA":"%s","deckB":"%s","styleA":"%s","styleB":"%s","seed":"%s"}\n' \
         "$m" "$na" "$nb" "$sa" "$sb" "$s" >> "$f"
  [[ "$g" =~ ^[0-9]+$ ]] && rm -rf "SWUSim/Games/$g"
}
export -f run_one

echo "[value] $(wc -l < "$PAIRS") games, $WORKERS workers, rate $RATE, out $OUT — $(date -u +%H:%M:%S)"
tr '\t' ' ' < "$PAIRS" | xargs -P "$WORKERS" -L1 bash -c 'run_one "$@"' _
echo "[value] done — $(ls "$OUT/pos" | wc -l) files — $(date -u +%H:%M:%S)"
