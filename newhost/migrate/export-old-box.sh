#!/usr/bin/env bash
#
# export-old-box.sh - phase 1 of the old box -> new box migration (newhost/migrate/README.md).
# Run ON THE OLD BOX as root. READ-ONLY: it never writes to a database or to the app folder.
#
# Writes $OUT_DIR (default /root/tcg-export-<UTC timestamp>/):
#   db/<db>.sql.gz         table definitions + data. NO triggers, routines or events, no CREATE DATABASE —
#                          the box was compromised, so no stored code comes across, and staging loads each
#                          dump under another name (<db>__old).
#   files/<Root>-Games.tgz each deck builder's deck folders (<Root>/Games/<id>/ + GameIDCounter.txt)
#   files/SWUSim-BugReports.tgz, files/SWUSim-BotData.tgz   when present
#   manifest.tsv           kind, name, detail, value — exact row count per table, deck folder counts and
#                          counters, sizes. stage-on-new-box.sh checks the load against it.
#   SHA256SUMS
# NOT copied, on purpose: PHP files, configs, APIKeys, vhost secrets. Treat every secret on this box as
# stolen and rotate it.
#
# Usage:
#   bash export-old-box.sh                 # every non-system database + every deck folder
#   bash export-old-box.sh --check         # list what would be exported, sizes and free space; writes nothing
# Environment:
#   APP_ROOT=/opt/lampp/htdocs/TCGEngine   OUT_DIR=/root/tcg-export-<ts>
#   DBS="swuonline swusim"                 # only these databases (default: all non-system ones)
#   MYSQL_BIN_DIR=/opt/lampp/bin           # where mysql + mysqldump live (default: XAMPP's, else PATH)
#   DB_CNF=/root/old-db.cnf                # a [client] file with user=/password= (default: root over the
#                                          # socket; if that is refused you are asked for root's password)
#
set -euo pipefail

CHECK=0
for arg in "$@"; do
  case "$arg" in
    --check) CHECK=1 ;;
    -h|--help) grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done

APP_ROOT="${APP_ROOT:-/opt/lampp/htdocs/TCGEngine}"
TS="$(date -u +%Y%m%d-%H%M%S)"
OUT_DIR="${OUT_DIR:-/root/tcg-export-$TS}"
log()  { printf '%s  %s\n' "$(date -u +%FT%TZ)" "$*" >&2; }
die()  { log "ERROR: $*"; exit 1; }

[ "$(id -u)" -eq 0 ] || die "run as root (sudo bash $0)"
[ -d "$APP_ROOT" ] || die "APP_ROOT '$APP_ROOT' does not exist (set APP_ROOT=…)"

# ── MySQL / MariaDB client ────────────────────────────────────────────────────────────────────────
BIN="${MYSQL_BIN_DIR:-}"
[ -z "$BIN" ] && [ -x /opt/lampp/bin/mysqldump ] && BIN=/opt/lampp/bin
if [ -n "$BIN" ]; then MYSQL="$BIN/mysql"; DUMP="$BIN/mysqldump"
else MYSQL="$(command -v mariadb || command -v mysql || true)"; DUMP="$(command -v mariadb-dump || command -v mysqldump || true)"; fi
[ -x "$MYSQL" ] && [ -x "$DUMP" ] || die "mysql/mysqldump not found (set MYSQL_BIN_DIR)"

TMP_CNF=""
cleanup() { if [ -n "$TMP_CNF" ]; then rm -f "$TMP_CNF"; fi; }   # an if, so an empty TMP_CNF is not the exit status
trap cleanup EXIT
CONN=()
[ -S /opt/lampp/var/mysql/mysql.sock ] && CONN+=(--socket=/opt/lampp/var/mysql/mysql.sock)
if [ -n "${DB_CNF:-}" ]; then
  [ -r "$DB_CNF" ] || die "DB_CNF '$DB_CNF' is not readable"
  AUTH=(--defaults-extra-file="$DB_CNF")
else
  AUTH=(-uroot)
  if ! "$MYSQL" "${AUTH[@]}" "${CONN[@]}" -N -e 'SELECT 1' >/dev/null 2>&1; then
    # Not reachable as root over the socket. Is the server even up? (XAMPP: /opt/lampp/lampp status)
    err="$("$MYSQL" "${AUTH[@]}" "${CONN[@]}" -N -e 'SELECT 1' 2>&1 >/dev/null | head -1)"
    case "$err" in *"Can't connect"*|*"No such file"*) die "the database server is not running: $err" ;; esac
    # No terminal to ask on (ssh with a command, a pipe): say so instead of dying silently on read.
    [ -t 0 ] || die "root over the socket was refused ($err) and there is no terminal to ask for its password — set DB_CNF"
    # The password goes into a 0600 temp file, never onto a command line (ps would show it).
    read -r -s -p "MariaDB root password: " pw; echo >&2
    TMP_CNF="$(mktemp)"; chmod 600 "$TMP_CNF"
    printf '[client]\nuser=root\npassword=%s\n' "$pw" > "$TMP_CNF"; unset pw
    AUTH=(--defaults-extra-file="$TMP_CNF")
  fi
fi
# --defaults-extra-file must come FIRST on the command line, so AUTH leads.
sql() { "$MYSQL" "${AUTH[@]}" "${CONN[@]}" -N -B -e "$1"; }
sql 'SELECT 1' >/dev/null || die "cannot connect to the database server"

# ── What to export ────────────────────────────────────────────────────────────────────────────────
if [ -n "${DBS:-}" ]; then
  dbs="$DBS"
else
  dbs="$(sql "SHOW DATABASES" | grep -v -E '^(information_schema|performance_schema|mysql|sys|phpmyadmin|test)$' || true)"
fi
[ -n "$dbs" ] || die "no databases to export"
for db in $dbs; do echo "$db" | grep -Eq '^[A-Za-z0-9_]+$' || die "'$db' is not a plain database name"; done

# Deck builders keep each deck as a folder <Root>/Games/<id>/. Live games of the SIMS (<Sim>/Games) are throwaway
# and are not exported; SWUDeck's Games folder is the deck store.
deck_roots=()
for d in "$APP_ROOT"/*Deck; do [ -d "$d/Games" ] && deck_roots+=("$(basename "$d")"); done
extra_dirs=()
for rel in SWUSim/BugReports SWUSim/BotData; do [ -d "$APP_ROOT/$rel" ] && extra_dirs+=("$rel"); done

db_bytes() { sql "SELECT COALESCE(SUM(data_length+index_length),0) FROM information_schema.tables WHERE table_schema='$1'"; }
log "server: $("$MYSQL" "${AUTH[@]}" "${CONN[@]}" -N -e 'SELECT VERSION()')   app: $APP_ROOT"
total=0
for db in $dbs; do b="$(db_bytes "$db")"; total=$((total + b)); log "  db    $db  $((b / 1048576)) MB on disk, $(sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$db'") tables"; done
for r in "${deck_roots[@]}"; do
  log "  decks $r/Games  $(find "$APP_ROOT/$r/Games" -mindepth 1 -maxdepth 1 -type d | wc -l) folders, counter $(cat "$APP_ROOT/$r/Games/GameIDCounter.txt" 2>/dev/null || echo '-'), $(du -sh "$APP_ROOT/$r/Games" | cut -f1)"
done
for rel in "${extra_dirs[@]}"; do log "  files $rel  $(du -sh "$APP_ROOT/$rel" | cut -f1)"; done
parent="$(dirname "$OUT_DIR")"; mkdir -p "$parent"
log "free space at $parent: $(df -h "$parent" | awk 'NR==2{print $4}')   (dumps compress to roughly 15-25% of $((total / 1048576)) MB)"
[ "$CHECK" -eq 1 ] && { log "--check: nothing written"; exit 0; }

# ── Export ────────────────────────────────────────────────────────────────────────────────────────
umask 077
mkdir -p "$OUT_DIR/db" "$OUT_DIR/files"
M="$OUT_DIR/manifest.tsv"
printf 'kind\tname\tdetail\tvalue\n' > "$M"
printf 'meta\thost\t-\t%s\nmeta\texported_at\t-\t%s\nmeta\tserver\t-\t%s\n' \
  "$(hostname)" "$(date -u +%FT%TZ)" "$("$MYSQL" "${AUTH[@]}" "${CONN[@]}" -N -e 'SELECT VERSION()')" >> "$M"

for db in $dbs; do
  out="$OUT_DIR/db/$db.sql.gz"
  log "dump  $db"
  # --single-transaction: a consistent snapshot of InnoDB tables without locking them (MyISAM tables are not
  # snapshotted; the old site is offline, so nothing is writing). --hex-blob keeps binary columns intact.
  "$DUMP" "${AUTH[@]}" "${CONN[@]}" --single-transaction --quick --hex-blob \
    --skip-triggers --skip-routines --skip-events --no-tablespaces \
    --default-character-set=utf8mb4 "$db" 2>"$OUT_DIR/db/$db.err" | gzip -6 > "$out"
  gzip -t "$out" || die "$db: dump is not a valid gzip"
  [ -s "$OUT_DIR/db/$db.err" ] && log "  note: $(head -c 300 "$OUT_DIR/db/$db.err")"
  rm -f "$OUT_DIR/db/$db.err"
  # Exact row counts (information_schema's TABLE_ROWS is an estimate for InnoDB).
  for t in $(sql "SELECT table_name FROM information_schema.tables WHERE table_schema='$db' AND table_type='BASE TABLE' ORDER BY table_name"); do
    printf 'rows\t%s\t%s\t%s\n' "$db" "$t" "$(sql "SELECT COUNT(*) FROM \`$db\`.\`$t\`")" >> "$M"
  done
  printf 'dump\t%s\tbytes\t%s\n' "$db" "$(stat -c %s "$out")" >> "$M"
done

for r in "${deck_roots[@]}"; do
  log "tar   $r/Games"
  tar -czf "$OUT_DIR/files/$r-Games.tgz" -C "$APP_ROOT/$r" Games
  printf 'decks\t%s\tfolders\t%s\n' "$r" "$(find "$APP_ROOT/$r/Games" -mindepth 1 -maxdepth 1 -type d | wc -l)" >> "$M"
  printf 'decks\t%s\tmax_id\t%s\n' "$r" "$(find "$APP_ROOT/$r/Games" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | grep -E '^[0-9]+$' | sort -n | tail -1)" >> "$M"
  printf 'decks\t%s\tcounter\t%s\n' "$r" "$(cat "$APP_ROOT/$r/Games/GameIDCounter.txt" 2>/dev/null || echo '')" >> "$M"
done
for rel in "${extra_dirs[@]}"; do
  name="$(echo "$rel" | tr '/' '-')"
  log "tar   $rel"
  tar -czf "$OUT_DIR/files/$name.tgz" -C "$APP_ROOT/$(dirname "$rel")" "$(basename "$rel")"
  printf 'files\t%s\tentries\t%s\n' "$rel" "$(find "$APP_ROOT/$rel" | wc -l)" >> "$M"
done

( cd "$OUT_DIR" && find . -type f ! -name SHA256SUMS -print0 | sort -z | xargs -0 sha256sum > SHA256SUMS )
log "done: $OUT_DIR  ($(du -sh "$OUT_DIR" | cut -f1))"
log "next, ON THE NEW BOX:  rsync -av root@$(hostname):$OUT_DIR/ /var/backups/tcg-old/"
echo "$OUT_DIR"
