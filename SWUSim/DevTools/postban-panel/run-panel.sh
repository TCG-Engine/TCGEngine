#!/bin/bash
# Post-ban TEST PANEL v1 (owner 2026-11-12) — one lever screen, ONE-SIDED.
# usage (inside the container, from /var/www/html/TCGEngine):
#   bash SWUSim/DevTools/postban-panel/run-panel.sh <run-name> <our-fixture-path> <our-style> "<control variant>" "<on variant>"
#   e.g. ... shipwait SWUSim/BotFixtures/postban-panel-v1/p4_boba_blue_burn.txt midrange "no-shipwait" ""
#        ... spaceanswer <fx> midrange "" "try-spaceanswer"
# The variant goes on OUR seat only; every panel deck plays plain. Jobs: the 8 panel decks (SWUSim/BotFixtures/postban-panel-v1/) + a
# MIRROR (our list, plain) x 50 seeds (pv001-pv050, fixed) x both seats = 900 games per arm. When our list IS a panel deck (same cards),
# the mirror would replay that slot's games exactly, so it is skipped (800 games). Results go to .claude/tmp/postban-panel/runs/<run-name>/
# (gitignored); read with: python3 SWUSim/DevTools/postban-panel/paired.py .claude/tmp/postban-panel/runs/<run-name>
cd /var/www/html/TCGEngine
PANEL=SWUSim/BotFixtures/postban-panel-v1
NAME=$1 OURFX=$2 OURSTYLE=$3 CTL=$4 ON=$5
O=.claude/tmp/postban-panel/runs/$NAME
L=$O/progress.log
mkdir -p $O/fx /tmp/swusim-opcache
cp $PANEL/p*.txt $O/fx/
cards() { grep -E '^[0-9]+ [A-Z0-9]+_[0-9]+' "$1" | sort; }
cp "$OURFX" $O/fx/ours.txt
inpanel=""; for p in $PANEL/p*.txt; do [ "$(cards "$p")" = "$(cards "$OURFX")" ] && inpanel=$(basename $p .txt); done
[ -z "$inpanel" ] && cp "$OURFX" $O/fx/mirror.txt
sed -i "s/^# Style:.*/# Style: $OURSTYLE/" $O/fx/ours.txt $( [ -z "$inpanel" ] && echo $O/fx/mirror.txt )
printf 'name=%s\nours=%s\nstyle=%s\ncontrol=%s\non=%s\npanel=v1\nmirror_of=%s\n' "$NAME" "$OURFX" "$OURSTYLE" "$CTL" "$ON" "${inpanel:-mirror}" > $O/meta.txt
arm() {
  local OUT=$O/$1 VAR=$2
  echo "$(date -u +%FT%TZ) start $1 [$VAR]" >> $L
  mkdir -p $OUT/res
  : > $OUT/jobs.txt
  for o in $(cd $O/fx && ls p*.txt mirror.txt 2>/dev/null | sed 's/\.txt$//'); do
    for i in $(seq -w 1 50); do echo "ours $o pv0$i" >> $OUT/jobs.txt; echo "$o ours pv0$i" >> $OUT/jobs.txt; done
  done
  run_one() {
    local a=$1 b=$2 s=$3 key="$1.$2.$3"
    [ -s "$OUT/res/$key.txt" ] && return 0
    local sa="" sb=""; [ -n "$VAR" ] && { [ "$a" = ours ] && sa="@$VAR"; [ "$b" = ours ] && sb="@$VAR"; }
    local ca=$(grep -m1 '^# Style:' "$O/fx/$a.txt" | awk '{print $3}') cb=$(grep -m1 '^# Style:' "$O/fx/$b.txt" | awk '{print $3}')
    local out=$(timeout 300 php -d apc.enable_cli=1 -d xdebug.mode=off -d memory_limit=1G -d opcache.file_cache=/tmp/swusim-opcache \
        DevTools/SWUSimBotSelfPlayTest.php --games=1 --seed="$s" --first-player=1 --verbose \
        --chooser="heuristic-$ca$sa" --chooser2="heuristic-$cb$sb" --deck="$O/fx/$a.txt" --deck2="$O/fx/$b.txt" 2>/dev/null)
    local g=$(printf '%s\n' "$out" | grep -m1 'game created:' | awk '{print $NF}')
    local m=$(printf '%s\n' "$out" | grep -m1 '^SWUBOT_METRICS ')
    printf '%s\t%s\t%s\t%s\t%s\n' "$a" "$b" "$s" "$g" "$m" > "$OUT/res/$key.txt"
    [[ "$g" =~ ^[0-9]+$ ]] && [ -n "$m" ] && rm -rf "SWUSim/Games/$g"
  }
  export -f run_one; export OUT VAR O
  xargs -P 10 -L 1 bash -c 'run_one "$@"' _ < $OUT/jobs.txt
  cat $OUT/res/*.txt > $OUT/results.tsv
  echo DONE > $OUT/DONE
  echo "$(date -u +%FT%TZ) done $1 $(wc -l < $OUT/results.tsv)" >> $L
}
arm arm-control "$CTL"
arm arm-on "$ON"
echo "$(date -u +%FT%TZ) ALL DONE" >> $L
