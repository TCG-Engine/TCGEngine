#!/bin/bash
# ga-fixture-run.sh [--play] slug...
#   Regenerate each fixture from its scenario and run it: prints generator warnings (including refused scenario steps),
#   then the integration result (and the first failing assertion).
#   --play  also replays it with DevTools/ga-autoplay.php and prints the prompt order and the final state.
cd "$(dirname "$0")/.." || exit 1
play=0
if [ "$1" = "--play" ]; then play=1; shift; fi
for s in "$@"; do
  echo "######## $s"
  php DevTools/create-prd-ability-fixtures.php --fixture="$s" 2>&1 | grep -E "WARN|dropp|REJECT|FAIL|Fatal|ERROR|Unknown|Failed: [1-9]|not found" | head -5
  php DevTools/RunIntegrationTests.php --root=GrandArchiveSim --test="$s" 2>&1 | grep -E "PASS|FAIL|expected|actual|Expected|Actual|mismatch" | head -6
  if [ "$play" = 1 ]; then
    php DevTools/ga-autoplay.php --test="$s" 2>&1 | sed -n '/^P[12] M/p;/final state/,$p' | cut -c1-230
  fi
done
