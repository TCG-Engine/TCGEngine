#!/bin/bash
# Emit one TSV row per BASE card of a Hellbreak set, with its implementation bucket.
#
#   .claude/skills/hellbreaksim-generate-set-doc/inventory.sh [SET]      # default DOT
#
# Columns: id · type · bucket · name · text
#
# Buckets: IMPL (a compiled macro key exists) · VANILLA (blank text) ·
#          KEYWORD-ONLY (text is nothing but implemented keywords) · NEEDS-WORK (everything else).
#
# Variants (borderless base+200, posters/alt art 4xx) carry a non-empty `baseCard` and are dropped:
# they play as their base card and never hold abilities of their own.
#
# ⚠ KEYWORD-ONLY and NEEDS-WORK are a heuristic — the keyword strip cannot tell reminder text from
# a real rider. Read every row's text before trusting its bucket.
#
# ⚠ Regenerate GeneratedMacroCode.php first; IMPL is read from it and it is built from local DB rows.

set -euo pipefail

SET="${1:-DOT}"
cd "$(dirname "$0")/../../.."

DICT=HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php
MACROS=HellbreakSim/GeneratedCode/GeneratedMacroCode.php

for f in "$DICT" "$MACROS"; do
  [ -f "$f" ] || { echo "Missing $f — run the generators first." >&2; exit 1; }
done

# Space-separated: macOS awk rejects a newline inside a -v assignment.
IMPL=$(grep -oE "\"${SET}_[0-9]+:[0-9]+\"" "$MACROS" | grep -oE "${SET}_[0-9]+" | sort -u | tr '\n' ' ')

awk -v set="$SET" -v impl="$IMPL" '
  BEGIN {
    n = split(impl, done, " ")
    for (i = 1; i <= n; i++) if (done[i] != "") isDone[done[i]] = 1
    # Keywords the engine already resolves by regexing card text (HellbreakKeywordValue).
    keywords = "Bloodlust|Fearsome|Fierce|First Strike|Guardian|Malicious|Overkill|Stealth|Terrify"
  }
  # Track which dictionary array we are inside; each block is "  $<name>Data = array (".
  /^  \$[a-zA-Z]+Data = array/ {
    blk = substr($0, 4)
    sub(/Data = array.*/, "", blk)
    next
  }
  $0 ~ "^  '\''" set "_[0-9]+'\'' =>" {
    id = substr($0, 4)
    sub(/'\''.*/, "", id)
    val = $0
    sub(/^[^=]*=> /, "", val)
    sub(/,$/, "", val)
    sub(/^'\''/, "", val)
    sub(/'\''$/, "", val)
    v[blk "|" id] = val
    if (blk == "id") ids[++count] = id
  }
  END {
    for (i = 1; i <= count; i++) {
      id = ids[i]
      if (v["baseCard|" id] != "") continue          # variant printing
      text = v["text|" id]
      if (isDone[id]) bucket = "IMPL"
      else if (text == "") bucket = "VANILLA"
      else {
        stripped = text
        gsub(keywords, "", stripped)
        gsub(/[0-9.,[:space:]]/, "", stripped)
        bucket = (stripped == "") ? "KEYWORD-ONLY" : "NEEDS-WORK"
      }
      printf "%s\t%s\t%s\t%s\t%s\n", id, v["type|" id], bucket, v["name|" id], text
    }
  }' "$DICT"
