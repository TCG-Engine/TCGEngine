#!/bin/bash
# SWUSim full regression runner. See ../SKILL.md for why each rule exists.
#
# Suites:
#   unit        SWUSim/Tests/Cases/**.md   — 10054 schema sections, via the CLI helper
#   integration DevTools/tdd-regression/*.php + SWUSim/DevTools/tests/*.php — 157 PHP tests
#   render      SharedUI/Render/Tests/RunRenderTests.php
#
# Flags: --skip-unit --only-unit --only-integration --only-render --list --quiet --baseline <file>
set -uo pipefail

REPO="${REPO:-/Users/mariotorresjr/Documents/GitHub/Karabast-SWU/OTMTCGE}"
CONTAINER="${CONTAINER:-otmtcge-swusim-web-server-1}"
WEBROOT=/var/www/html/TCGEngine
SWUSIM_PORT="${SWUSIM_PORT:-3400}"
SWUDECK_PORT="${SWUDECK_PORT:-3100}"
OUT="${OUT:-${TMPDIR:-/tmp}/swusim-regression-$$}"

RUN_UNIT=1; RUN_INT=1; RUN_RENDER=1; QUIET=0; LIST=0
BASELINE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/known-red.txt"
while [ $# -gt 0 ]; do
  case "$1" in
    --skip-unit)        RUN_UNIT=0 ;;
    --only-unit)        RUN_INT=0; RUN_RENDER=0 ;;
    --only-integration) RUN_UNIT=0; RUN_RENDER=0 ;;
    --only-render)      RUN_UNIT=0; RUN_INT=0 ;;
    --list)             LIST=1 ;;
    --quiet)            QUIET=1 ;;
    --baseline)         shift; BASELINE="$1" ;;
    -h|--help)          sed -n '2,12p' "$0"; exit 0 ;;
    *) echo "unknown flag: $1" >&2; exit 2 ;;
  esac
  shift
done

cd "$REPO" || { echo "repo not found: $REPO" >&2; exit 2; }
mkdir -p "$OUT"
say() { [ "$QUIET" = 1 ] || echo "$@"; }

int_files() { ls DevTools/tdd-regression/*.php SWUSim/DevTools/tests/*.php 2>/dev/null; }

if [ "$LIST" = 1 ]; then
  echo "unit:        $(find SWUSim/Tests/Cases -name '*.md' | wc -l | tr -d ' ') .md files"
  echo "integration: $(int_files | wc -l | tr -d ' ') php files"
  echo "render:      1 runner"
  exit 0
fi

FAILED=(); NEWRED=(); KNOWN=(); PASSN=0

# ── unit ───────────────────────────────────────────────────────────────────────
if [ "$RUN_UNIT" = 1 ]; then
  say "── unit (schema) ──"
  docker exec -w "$WEBROOT" "$CONTAINER" php -d xdebug.mode=off \
    .claude/skills/swusim-debug-game/scripts/run-schema-tests.php >"$OUT/unit.out" 2>"$OUT/unit.err"
  line=$(sed 's/<[^>]*>//g' "$OUT/unit.out" | grep -E '[0-9]+ passed' | tail -1)
  say "  ${line:-NO SUMMARY LINE — the runner did not complete}"
  if [ -z "$line" ] || ! echo "$line" | grep -q ' 0 failed'; then
    FAILED+=("unit-suite")
    sed 's/<[^>]*>//g' "$OUT/unit.out" | grep '✗' | head -20
  fi
  n=$(grep -c 'ACTION-LEDGER' "$OUT/unit.err" 2>/dev/null || echo 0)
  # Closes the gate PREVENTED, not bugs remaining — see action-close-deferrals.md §4. A CHANGE in
  # this number is the signal; the number itself is not a countdown to zero.
  [ "$n" -gt 0 ] 2>/dev/null && say "  action-close ledger: $n double-closes PREVENTED (baseline 109)"
fi

# ── integration ────────────────────────────────────────────────────────────────
if [ "$RUN_INT" = 1 ]; then
  say "── integration (php) ──"
  # ONE docker exec for the whole CLI pass. Per-file `docker exec` costs 1-3s of container startup
  # each and pushed a 157-file run past ten minutes; batching brings it under two. The script is fed
  # on stdin rather than written into the repo (the repo is a bind mount — do not litter it).
  int_files > "$OUT/files.txt"
  # ⚠ The script AND the file list must both reach the container. $OUT is a HOST path the container
  # cannot see, so `docker exec sh "$OUT/batch.sh"` silently runs nothing — it produced zero @@FILE
  # markers, every test fell through to the HTTP retry, and two CLI-only tests were reported red.
  # Embed the list in the script and feed the whole thing on stdin.
  {
    echo 'for f in \'
    sed 's/^/  /; s/$/ \\/' "$OUT/files.txt"
    echo '  ; do'
    echo '  b=$(basename "$f" .php)'
    echo '  echo "@@FILE $b"'
    echo '  timeout 90 php -d xdebug.mode=off "$f" 2>&1 | grep -v Xdebug'
    echo 'done'
  } > "$OUT/batch.sh"
  docker exec -i -w "$WEBROOT" "$CONTAINER" sh -s < "$OUT/batch.sh" > "$OUT/batch.out" 2>&1 || true
  markers=$(grep -c '^@@FILE ' "$OUT/batch.out")
  # An empty/short batch means the CLI pass did not happen — say so instead of silently HTTP-ing all
  # 157, which looks like a slow-but-working run.
  [ "$markers" -lt 1 ] && say "  ⚠ CLI batch produced no output — every test will fall back to HTTP"
  awk -v out="$OUT" '/^@@FILE /{cur=out"/"$2".txt"; next} cur{print > cur}' "$OUT/batch.out"

  for f in $(cat "$OUT/files.txt"); do
    b=$(basename "$f" .php)
    # Transport: the file's own header comment declares its URL; fall back to CLI, then to the
    # app's port when CLI hits an environment signature (APCu / DB) rather than a real failure.
    port=$(grep -oE 'localhost:[0-9]+' "$f" 2>/dev/null | head -1 | cut -d: -f2)
    [ -z "$port" ] && case "$b" in test_swudeck*) port=$SWUDECK_PORT ;; *) port=$SWUSIM_PORT ;; esac

    via=CLI; code=0; hasexit=0
    # No exit code survives the batch, so the CLI verdict is the anchored last line only. That is
    # sufficient: every non-pass is retried over HTTP below, which is the canonical transport anyway.
    last=$(grep -v '^[[:space:]]*$' "$OUT/$b.txt" 2>/dev/null | tail -1)

    # ⚠ A file that DECLARES a localhost URL is HTTP-native — do not judge it on the CLI result at
    # all. Two reasons, both measured: it needs APCu or a site-bound DB that the CLI SAPI lacks, and
    # letting all 157 run under CLI first exhausts MySQL's connection pool, after which the HTTP
    # retries fail too. Nine swudeck tests reported red that way and passed one-by-one seconds later.
    if [ -n "$(grep -oE 'localhost:[0-9]+' "$f" 2>/dev/null | head -1)" ]; then
      last=""
    fi

    # ⚠ RETRY ANY NON-PASS OVER HTTP — not just fatals. Roughly a fifth of these tests need APCu or a
    # site-bound DB connection, neither of which the CLI SAPI has, and MOST OF THEM DEGRADE QUIETLY:
    # they print "FAIL: ..." rather than fataling. Triggering the fallback only on a fatal reported
    # five green tests as failures (measured 2026-08-29 — completedgame_format/_migration,
    # deckstats/metastats_format_columns, swusim_index_cleanup all pass over HTTP).
    cli_pass=0
    :
    if echo "$last" | grep -qE '^(PASS|OK|ALL PASS)'; then cli_pass=1; fi
    if [ "$cli_pass" = 0 ]; then
      for attempt in 1 2; do
        curl -s --max-time 90 "http://localhost:$port/TCGEngine/$f" | sed 's/<[^>]*>//g' >"$OUT/$b.txt" 2>&1
        code=0; via="HTTP:$port"; hasexit=0
        last=$(grep -v Xdebug "$OUT/$b.txt" | grep -v '^[[:space:]]*$' | tail -1)
        # A DB connect failure under load is transient, not a verdict — back off once and retry.
        grep -q 'mysqli_connect' "$OUT/$b.txt" || break
        [ "$attempt" = 1 ] && sleep 3
      done
    fi

    # Classify: exit code is authoritative WHEN THE FILE HAS ONE (33/36 SWUSim, 47/121 tdd).
    # Otherwise the LAST line, ANCHORED — never a bare grep for PASS/FAIL anywhere in the output,
    # because check *names* contain both words and that misclassifies wildly.
    if [ "$via" = CLI ] && [ "$hasexit" -gt 0 ]; then
      [ "$code" = 0 ] && st=PASS || st=FAIL
    elif echo "$last" | grep -qE '^(PASS|OK|ALL PASS)'; then st=PASS
    elif echo "$last" | grep -qE '^(FAIL|FAILED)'; then st=FAIL
    elif grep -qE 'Fatal error|Parse error|Uncaught' "$OUT/$b.txt"; then st=ERROR
    elif [ "$code" = 0 ]; then st=PASS
    else st=FAIL; fi

    if [ "$st" = PASS ]; then
      PASSN=$((PASSN+1))
    # The baseline carries a REASON after each name, so strip comments before matching —
    # a whole-line grep would match nothing and report every known-red as new.
    elif [ -f "$BASELINE" ] && sed 's/#.*//; s/[[:space:]]//g' "$BASELINE" | grep -qx "$b"; then
      KNOWN+=("$b")
    else
      NEWRED+=("$b ($via) ${last:0:70}")
    fi
  done
  say "  $PASSN passed · ${#KNOWN[@]} known-red · ${#NEWRED[@]} NEW"
  for r in "${NEWRED[@]:-}"; do [ -n "$r" ] && echo "  ✗ $r"; done
fi

# ── render ─────────────────────────────────────────────────────────────────────
if [ "$RUN_RENDER" = 1 ]; then
  say "── render ──"
  docker exec -w "$WEBROOT" "$CONTAINER" php -d xdebug.mode=off \
    SharedUI/Render/Tests/RunRenderTests.php >"$OUT/render.txt" 2>&1
  rc=$?
  say "  exit=$rc"
  [ $rc -ne 0 ] && { FAILED+=("render"); tail -5 "$OUT/render.txt"; }
fi

echo
if [ ${#FAILED[@]} -eq 0 ] && [ ${#NEWRED[@]} -eq 0 ]; then
  echo "GREEN (output: $OUT)"; exit 0
fi
echo "RED: ${FAILED[*]:-} ${#NEWRED[@]} new integration failure(s) (output: $OUT)"; exit 1
