#!/usr/bin/env bash
#
# stage-on-new-box.sh - phase 2 of the old box -> new box migration (newhost/migrate/README.md).
# Run ON THE NEW BOX as root, on a directory written by export-old-box.sh and copied here.
#
# Touches NO live database and NO app file:
#   - verifies SHA256SUMS,
#   - loads each db/<db>.sql.gz into a STAGING database <live name>__old (the old box's 'swuonline' is
#     SWUStats, i.e. 'swudeck' here, so it lands in swudeck__old),
#   - refuses a dump that carries stored code (triggers, procedures, functions, events) and strips view
#     DEFINERs — the old box was compromised,
#   - checks every table's row count against manifest.tsv,
#   - unpacks files/*.tgz under <export dir>/staged-files/, never into the app folder.
# The merge (phase 4) reads from the __old databases and staged-files/.
#
# Usage:
#   sudo ./stage-on-new-box.sh /var/backups/tcg-old              # stage everything
#   sudo ./stage-on-new-box.sh /var/backups/tcg-old --replace    # drop and reload __old databases that exist
# Environment:
#   DB_MAP="swuonline=swudeck"   old name = live name, space-separated (default maps swuonline to swudeck)
#
set -euo pipefail

DIR="${1:-}"; shift || true
REPLACE=0
for arg in "$@"; do
  case "$arg" in
    --replace) REPLACE=1 ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done
log() { printf '%s  %s\n' "$(date -u +%FT%TZ)" "$*" >&2; }
die() { log "ERROR: $*"; exit 1; }
[ "$(id -u)" -eq 0 ] || die "run as root"
[ -n "$DIR" ] && [ -f "$DIR/manifest.tsv" ] || die "usage: $0 <export dir holding manifest.tsv>"

MYSQL="$(command -v mariadb || command -v mysql || true)"
[ -n "$MYSQL" ] || die "mariadb/mysql client not found"
sql() { "$MYSQL" -N -B -e "$1"; }        # root over the unix socket, as setup-box.sh does
sql 'SELECT 1' >/dev/null || die "cannot connect to MariaDB as root"

log "verify checksums"
( cd "$DIR" && sha256sum -c SHA256SUMS --quiet ) || die "SHA256SUMS mismatch — the copy is damaged or altered; copy it again"

DB_MAP="${DB_MAP:-swuonline=swudeck}"
live_name() { local m; for m in $DB_MAP; do [ "${m%%=*}" = "$1" ] && { echo "${m#*=}"; return; }; done; echo "$1"; }

FAILED=0
# Stored code must not come across (the export skips it; this is the check that it really is absent). Every dump is
# checked BEFORE any is loaded, so one bad dump stops the whole staging instead of leaving it half done.
for dump in "$DIR"/db/*.sql.gz; do
  [ -e "$dump" ] || die "no dumps under $DIR/db"
  if gzip -dc "$dump" | grep -E -i -q '^[[:space:]]*CREATE[[:space:]].*\b(TRIGGER|PROCEDURE|FUNCTION|EVENT)\b'; then
    die "$(basename "$dump" .sql.gz): the dump contains a CREATE TRIGGER/PROCEDURE/FUNCTION/EVENT — refusing to stage anything"
  fi
done
for dump in "$DIR"/db/*.sql.gz; do
  old="$(basename "$dump" .sql.gz)"
  stage="$(live_name "$old")__old"
  echo "$stage" | grep -Eq '^[A-Za-z0-9_]+$' || die "'$stage' is not a plain database name"
  exists="$(sql "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$stage'")"
  if [ "$exists" != "0" ]; then
    [ "$REPLACE" -eq 1 ] || die "$stage already exists — pass --replace to reload it (it is only staging)"
    sql "DROP DATABASE \`$stage\`"
  fi
  sql "CREATE DATABASE \`$stage\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
  log "load  $old -> $stage"
  # Views keep a DEFINER naming a user of the OLD server; strip it so the view runs as the invoker here.
  gzip -dc "$dump" | sed -E 's/DEFINER=`[^`]*`@`[^`]*`//g' | "$MYSQL" "$stage" || { log "FAIL: loading $old"; FAILED=$((FAILED+1)); continue; }
  # Row counts against the manifest.
  bad=0; n=0
  while IFS=$'\t' read -r kind db table rows; do
    [ "$kind" = "rows" ] && [ "$db" = "$old" ] || continue
    n=$((n+1))
    got="$(sql "SELECT COUNT(*) FROM \`$stage\`.\`$table\`" 2>/dev/null || echo MISSING)"
    if [ "$got" != "$rows" ]; then log "  MISMATCH $stage.$table: manifest $rows, loaded $got"; bad=$((bad+1)); fi
  done < "$DIR/manifest.tsv"
  if [ "$bad" -eq 0 ]; then log "  ok  $stage: $n tables, every row count matches"; else FAILED=$((FAILED+1)); fi
done

STAGED="$DIR/staged-files"
mkdir -p "$STAGED"
for t in "$DIR"/files/*.tgz; do
  [ -e "$t" ] || continue
  name="$(basename "$t" .tgz)"            # SWUDeck-Games, SWUSim-BugReports, …
  root="${name%%-*}"
  rm -rf "${STAGED:?}/$root/${name#*-}"
  mkdir -p "$STAGED/$root"
  # --no-same-owner / --no-same-permissions: files land owned by root with this box's umask, not the old box's ids.
  tar -xzf "$t" -C "$STAGED/$root" --no-same-owner --no-same-permissions
  log "  ok  files $name -> $STAGED/$root/${name#*-}"
done
while IFS=$'\t' read -r kind root detail value; do
  [ "$kind" = "decks" ] || continue
  if [ "$detail" = "folders" ]; then
    got="$(find "$STAGED/$root/Games" -mindepth 1 -maxdepth 1 -type d 2>/dev/null | wc -l)"
    [ "$got" = "$value" ] && log "  ok  $root decks: $got folders" || { log "  MISMATCH $root decks: manifest $value, staged $got"; FAILED=$((FAILED+1)); }
  fi
done < "$DIR/manifest.tsv"

[ "$FAILED" -eq 0 ] || die "$FAILED staging check(s) failed — do not merge from this copy"
log "staged. Live databases and app files were not touched. Next: the review (phase 3)."
