#!/usr/bin/env bash
#
# inventory.sh - phases 0, 3 and 5 of the old box -> new box migration (newhost/migrate/README.md).
# READ-ONLY on either box (XAMPP or native LAMP): it only runs SELECTs and lists folders.
#
#   sudo ./inventory.sh                 # this box: rows per table, ID ranges, deck folders + counters
#   sudo ./inventory.sh --compare       # NEW box after staging: what the merge has to resolve —
#                                       # live vs <db>__old ID collisions, shared emails/usernames,
#                                       # moderator accounts on both sides, overlapping deck folders,
#                                       # and old rows holding <?php / <script (the box was compromised)
# Environment:
#   APP_ROOT   (default /opt/lampp/htdocs/TCGEngine on XAMPP, else /var/www/html/TCGEngine)
#   STAGED=/var/backups/tcg-old/staged-files      where stage-on-new-box.sh unpacked the old files
#   DBS="swudeck swusim"                          only these live databases
#   DB_CNF=/path/client.cnf                       credentials (default: root over the socket)
#
set -uo pipefail

COMPARE=0
for arg in "$@"; do
  case "$arg" in
    --compare) COMPARE=1 ;;
    -h|--help) grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done

if [ -x /opt/lampp/bin/mysql ]; then MYSQL=/opt/lampp/bin/mysql; APP_ROOT="${APP_ROOT:-/opt/lampp/htdocs/TCGEngine}"
else MYSQL="$(command -v mariadb || command -v mysql)"; APP_ROOT="${APP_ROOT:-/var/www/html/TCGEngine}"; fi
STAGED="${STAGED:-/var/backups/tcg-old/staged-files}"
CONN=(); [ -S /opt/lampp/var/mysql/mysql.sock ] && CONN+=(--socket=/opt/lampp/var/mysql/mysql.sock)
AUTH=(); [ -n "${DB_CNF:-}" ] && AUTH=(--defaults-extra-file="$DB_CNF")
sql() { "$MYSQL" "${AUTH[@]}" "${CONN[@]}" -N -B -e "$1" 2>/dev/null; }
sql 'SELECT 1' >/dev/null || { echo "cannot connect to the database server (set DB_CNF)" >&2; exit 1; }
has_table() { [ "$(sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$1' AND table_name='$2'")" = "1" ]; }
has_col()   { [ "$(sql "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$1' AND table_name='$2' AND column_name='$3'")" = "1" ]; }
MODS="'ninin','OotTheMonk'"   # AccountFiles/AccountSessionAPI.php ApprovedModeratorUserNames() — usernames grant mod

# The ID columns the merge renumbers (README "What carries an ID").
ID_COLS="users.usersId ownership.assetIdentifier team.teamID matchhistory.matchHistoryID glicko_results.id assetautoversions.versionID"

if [ -n "${DBS:-}" ]; then live="$DBS"
else live="$(sql "SHOW DATABASES" | grep -v -E '^(information_schema|performance_schema|mysql|sys|phpmyadmin|test)$' | grep -v '__old$')"; fi

echo "# inventory of $(hostname) at $(date -u +%FT%TZ)   server $(sql 'SELECT VERSION()')   app $APP_ROOT"

if [ "$COMPARE" -eq 0 ]; then
  for db in $live; do
    echo; echo "## database $db"
    for t in $(sql "SELECT table_name FROM information_schema.tables WHERE table_schema='$db' AND table_type='BASE TABLE' ORDER BY table_name"); do
      printf '  %-34s %10s rows\n' "$t" "$(sql "SELECT COUNT(*) FROM \`$db\`.\`$t\`")"
    done
    for tc in $ID_COLS; do
      t="${tc%%.*}"; c="${tc#*.}"
      has_table "$db" "$t" && has_col "$db" "$t" "$c" || continue
      printf '  id range %-26s %s\n' "$tc" "$(sql "SELECT CONCAT(COALESCE(MIN(\`$c\`),'-'),' .. ',COALESCE(MAX(\`$c\`),'-')) FROM \`$db\`.\`$t\`")"
    done
    if has_table "$db" users; then
      echo "  moderator-name accounts: $(sql "SELECT GROUP_CONCAT(CONCAT(usersId,':',usersUid) SEPARATOR ', ') FROM \`$db\`.users WHERE usersUid IN ($MODS)")"
    fi
  done
  echo; echo "## deck folders"
  for d in "$APP_ROOT"/*Deck; do
    [ -d "$d/Games" ] || continue
    ids="$(find "$d/Games" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | grep -E '^[0-9]+$' | sort -n)"
    printf '  %-16s %6s folders  ids %s .. %s  counter %s\n' "$(basename "$d")" "$(echo "$ids" | grep -c .)" \
      "$(echo "$ids" | head -1)" "$(echo "$ids" | tail -1)" "$(cat "$d/Games/GameIDCounter.txt" 2>/dev/null || echo '-')"
  done
  exit 0
fi

# ── --compare: live vs staged old ────────────────────────────────────────────────────────────────
for db in $live; do
  old="${db}__old"
  [ "$(sql "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$old'")" = "1" ] || { echo; echo "## $db: no $old staged — skipped"; continue; }
  echo; echo "## $db  vs  $old"
  for t in $(sql "SELECT table_name FROM information_schema.tables WHERE table_schema='$old' AND table_type='BASE TABLE' ORDER BY table_name"); do
    newn="-"; has_table "$db" "$t" && newn="$(sql "SELECT COUNT(*) FROM \`$db\`.\`$t\`")"
    printf '  %-34s old %10s   new %10s\n' "$t" "$(sql "SELECT COUNT(*) FROM \`$old\`.\`$t\`")" "$newn"
  done
  for tc in $ID_COLS; do
    t="${tc%%.*}"; c="${tc#*.}"
    has_table "$old" "$t" && has_table "$db" "$t" && has_col "$db" "$t" "$c" || continue
    printf '  ids %-30s old max %s, new %s, new ids that collide with old: %s\n' "$tc" \
      "$(sql "SELECT COALESCE(MAX(\`$c\`),0) FROM \`$old\`.\`$t\`")" \
      "$(sql "SELECT CONCAT(COALESCE(MIN(\`$c\`),'-'),' .. ',COALESCE(MAX(\`$c\`),'-')) FROM \`$db\`.\`$t\`")" \
      "$(sql "SELECT COUNT(*) FROM \`$db\`.\`$t\` n JOIN \`$old\`.\`$t\` o ON o.\`$c\` = n.\`$c\`")"
  done
  if has_table "$old" users && has_table "$db" users; then
    echo "  accounts: same email on both boxes (likely the same person): $(sql "SELECT COUNT(*) FROM \`$db\`.users n JOIN \`$old\`.users o ON LOWER(o.usersEmail)=LOWER(n.usersEmail) AND n.usersEmail<>''")"
    echo "  accounts: same username, DIFFERENT email (needs a rename rule): $(sql "SELECT COUNT(*) FROM \`$db\`.users n JOIN \`$old\`.users o ON LOWER(o.usersUid)=LOWER(n.usersUid) AND LOWER(o.usersEmail)<>LOWER(n.usersEmail)")"
    echo "  accounts: new-box only (no email or username match): $(sql "SELECT COUNT(*) FROM \`$db\`.users n WHERE NOT EXISTS (SELECT 1 FROM \`$old\`.users o WHERE LOWER(o.usersEmail)=LOWER(n.usersEmail) OR LOWER(o.usersUid)=LOWER(n.usersUid))")"
    echo "  ⚠ moderator-name accounts (a username grants mod — confirm each is yours):"
    sql "SELECT 'old', usersId, usersUid, usersEmail FROM \`$old\`.users WHERE usersUid IN ($MODS)
         UNION ALL SELECT 'new', usersId, usersUid, usersEmail FROM \`$db\`.users WHERE usersUid IN ($MODS)" | sed 's/^/      /'
  fi
  # Content that should never be in these columns — planted scripts on the compromised box.
  for tc in users.usersUid users.usersEmail ownership.assetName favoritedeck.name team.TeamName matchhistory.deckName matchhistory.opponentName lastdeck.deckName; do
    t="${tc%%.*}"; c="${tc#*.}"
    has_table "$old" "$t" && has_col "$old" "$t" "$c" || continue
    n="$(sql "SELECT COUNT(*) FROM \`$old\`.\`$t\` WHERE \`$c\` LIKE '%<?php%' OR \`$c\` LIKE '%<script%' OR \`$c\` LIKE '%javascript:%' OR \`$c\` LIKE '%onerror=%'")"
    [ "$n" != "0" ] && echo "  ⚠ suspicious content: $old.$tc has $n row(s) with <?php / <script / javascript: / onerror="
  done
done

echo; echo "## deck folders: live vs staged old"
for d in "$APP_ROOT"/*Deck; do
  [ -d "$d/Games" ] || continue
  r="$(basename "$d")"
  newids="$(find "$d/Games" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | grep -E '^[0-9]+$' | sort -n)"
  if [ ! -d "$STAGED/$r/Games" ]; then echo "  $r: nothing staged"; continue; fi
  oldids="$(find "$STAGED/$r/Games" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | grep -E '^[0-9]+$' | sort -n)"
  both="$(comm -12 <(echo "$newids" | LC_ALL=C sort) <(echo "$oldids" | LC_ALL=C sort) | grep -c .)"   # comm needs LEXICAL order
  printf '  %-16s new %6s folders (%s .. %s)   old %6s folders (max %s, counter %s)   same id on both: %s\n' "$r" \
    "$(echo "$newids" | grep -c .)" "$(echo "$newids" | head -1)" "$(echo "$newids" | tail -1)" \
    "$(echo "$oldids" | grep -c .)" "$(echo "$oldids" | tail -1)" "$(cat "$STAGED/$r/Games/GameIDCounter.txt" 2>/dev/null || echo '-')" "$both"
done
