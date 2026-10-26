#!/usr/bin/env bash
#
# override-new-box.sh - replace the new box's SWUStats data with the old box's (owner decision 2026-10-24: override, plus the
# carry-overs below). Run ON THE NEW BOX as root, after stage-on-new-box.sh has loaded the old data into swudeck__old and
# unpacked the deck folders under /var/backups/tcg-old/staged-files/.
#
#   sudo ./override-new-box.sh build      # backs up live, builds swudeck__new, verifies. Live is NOT touched.
#   sudo ./override-new-box.sh apply      # the switch: one atomic RENAME TABLE + a deck-folder mv, then apache reload
#   sudo ./override-new-box.sh rollback   # undo apply: the previous live tables and deck folders come back
#
# How a table is built: CREATE TABLE swudeck__new.T LIKE swudeck.T (the LIVE definition — the new box may carry
# migrations the old box never got), then INSERT the OLD rows over the columns both have. `apply` swaps every built
# table in ONE RENAME TABLE (atomic), moving the live ones to swudeck__prev, so rollback is a rename back.
#
# Kept from the NEW box (not replaced): card_abilities, card_code_* (code-maintained reference data, newer here),
# oauth_scopes, and any table the old box does not have (all empty at the time of writing; build stops if one has rows).
# Owner choices (2026-10-24):
#   - the 14 new-box-only accounts whose email AND username are free in the old data are carried over with new IDs
#     above the old range (accounts only — their decks/games from the last days are not); the mapping is logged.
#   - the new Karabast OAuth client registered on the new box (CARRY_CLIENT) is kept, re-owned to the old account with
#     the same username as its new-box owner. Every other OAuth client and every token comes from the old box: a
#     new-box token names a new-box user ID, which after the override is a different person's account.
# ⚠ build DROPS each swudeck__old table once it is copied (the disk holds one copy, not two); re-run stage-on-new-box.sh
#   --replace before building again.
#
set -euo pipefail

CMD="${1:-}"
LIVE="${LIVE_DB:-swudeck}"; OLD="${OLD_DB:-${LIVE}__old}"; NEW="${LIVE}__new"; PREV="${LIVE}__prev"
APP_ROOT="${APP_ROOT:-/var/www/html/TCGEngine}"
GAMES="$APP_ROOT/SWUDeck/Games"
STAGED_GAMES="${STAGED_GAMES:-/var/backups/tcg-old/staged-files/SWUDeck/Games}"
STATE=/var/backups/override-state           # remembers the backup dir of the last build for apply/rollback
KEEP_LIVE="card_abilities card_code_api_tokens card_code_checkpoints card_code_token_audit oauth_scopes"
CARRY_CLIENT="${CARRY_CLIENT:-cbcc2cb32f136f3311900e38}"
WEB_USER="${WEB_USER:-www-data}"

log() { printf '%s  %s\n' "$(date -u +%FT%TZ)" "$*" >&2; }
die() { log "ERROR: $*"; exit 1; }
[ "$(id -u)" -eq 0 ] || die "run as root"
MYSQL="$(command -v mariadb || command -v mysql)"; DUMP="$(command -v mariadb-dump || command -v mysqldump)"
sql() { "$MYSQL" -N -B -e "$1"; }
q()   { "$MYSQL" -N -B -e "$1" >/dev/null; }
has_db()    { [ "$(sql "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$1'")" = 1 ]; }
has_table() { [ "$(sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$1' AND table_name='$2'")" = 1 ]; }
tables_of() { sql "SELECT table_name FROM information_schema.tables WHERE table_schema='$1' AND table_type='BASE TABLE' ORDER BY table_name"; }
rows()      { sql "SELECT COUNT(*) FROM \`$1\`.\`$2\`"; }
kept()      { case " $KEEP_LIVE " in *" $1 "*) return 0 ;; esac; return 1; }
# The column list both versions of a table have, in the LIVE order, backquoted.
common_cols() { sql "SELECT GROUP_CONCAT(CONCAT('\`',c.column_name,'\`') ORDER BY c.ordinal_position) FROM information_schema.columns c
                     WHERE c.table_schema='$LIVE' AND c.table_name='$1' AND EXISTS (SELECT 1 FROM information_schema.columns o
                     WHERE o.table_schema='$OLD' AND o.table_name='$1' AND o.column_name=c.column_name)"; }

case "$CMD" in
# ──────────────────────────────────────────────────────────────────────────────────────────────── build
build)
  has_db "$OLD" || die "$OLD is not staged (run stage-on-new-box.sh first)"
  [ -d "$STAGED_GAMES" ] || die "no staged deck folders at $STAGED_GAMES"
  has_db "$PREV" && [ -n "$(tables_of "$PREV")" ] && die "$PREV has tables — an earlier apply was not cleaned up; inspect it first"
  # Tables only the new box has must be empty: their rows would point at new-box user IDs.
  for t in $(tables_of "$LIVE"); do
    kept "$t" && continue
    # (already in $NEW = built by an earlier run, whose $OLD copy was dropped — not a new-box-only table)
    if ! has_table "$OLD" "$t" && ! has_table "$NEW" "$t" && [ "$(rows "$LIVE" "$t")" != 0 ]; then die "$LIVE.$t exists only on the new box and has rows — decide what to do with it first"; fi
  done
  # A new-box account whose discordID is already on an old account is the SAME person (they signed in with Discord on the
  # new box and got a fresh account — obiswankenobi_ = ObiSwanKenobi, Raistlin = RaistlinMajere, Nixks = Nixks09 on
  # 2026-10-24): their old account comes back, so they are not carried (discordID is UNIQUE, so carrying would fail anyway).
  # The old accounts are in $OLD.users until users is built, then in $NEW.users (a resumed build).
  OLDU="$OLD"; has_table "$OLD" users || OLDU="$NEW"
  dup="$(sql "SELECT COUNT(*) FROM \`$LIVE\`.users l JOIN \`$OLDU\`.users o ON o.discordID = l.discordID AND l.discordID IS NOT NULL AND l.discordID <> ''
              WHERE NOT EXISTS (SELECT 1 FROM \`$OLDU\`.users x WHERE LOWER(x.usersEmail)=LOWER(l.usersEmail) OR LOWER(x.usersUid)=LOWER(l.usersUid))")"
  [ "$dup" = 0 ] || log "  $dup new-box account(s) share a discordID with an old account — same person, not carried"
  shaun_owner="$(sql "SELECT user_id FROM \`$LIVE\`.oauth_clients WHERE client_id='$CARRY_CLIENT'")"
  if [ -n "$shaun_owner" ]; then
    owner_name="$(sql "SELECT usersUid FROM \`$LIVE\`.users WHERE usersId=$shaun_owner")"
    n="$(sql "SELECT COUNT(*) FROM \`$OLDU\`.users WHERE LOWER(usersUid)=LOWER('$owner_name')")"
    [ "$n" = 1 ] || die "the carried client's owner '$owner_name' matches $n old accounts (need exactly 1)"
  fi

  TS="$(date -u +%Y%m%d-%H%M%S)"; BK="/var/backups/pre-override-$TS"
  umask 077; mkdir -p "$BK"
  log "backup live -> $BK"
  "$DUMP" --single-transaction --quick --hex-blob --routines --triggers --events --default-character-set=utf8mb4 \
    --databases "$LIVE" | gzip -6 > "$BK/$LIVE-live.sql.gz"
  gzip -t "$BK/$LIVE-live.sql.gz"
  tar -czf "$BK/SWUDeck-Games-live.tgz" -C "$APP_ROOT/SWUDeck" Games
  for t in $(tables_of "$LIVE"); do printf 'live\t%s\t%s\n' "$t" "$(rows "$LIVE" "$t")"; done > "$BK/live-rowcounts.tsv"
  mkdir -p "$STATE"; echo "$BK" > "$STATE/last"

  # RESUMABLE: a table is finished once it is in $NEW and its $OLD copy is gone (dropped only after its row count matched),
  # so a build stopped part-way continues instead of needing a fresh stage.
  if has_db "$NEW"; then log "resuming: $NEW exists"; else q "CREATE DATABASE \`$NEW\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"; fi
  for t in $(tables_of "$LIVE"); do
    kept "$t" && { log "  keep  $t (new box's own)"; continue; }
    if has_table "$NEW" "$t" && ! has_table "$OLD" "$t"; then log "  done  $t (earlier run, $(rows "$NEW" "$t") rows)"; continue; fi
    has_table "$OLD" "$t" || { log "  keep  $t (old box has no such table; empty here)"; continue; }
    cols="$(common_cols "$t")"; sel="$cols"; want="$(rows "$OLD" "$t")"
    if [ "$t" = users ] && case ",$cols," in *',`discordID`,'*) true ;; *) false ;; esac; then
      # The new box's users.discordID is UNIQUE (migration 11); the old box's was not, and 10 Discord accounts sit on two old
      # accounts each. The old login (AccountFiles/DiscordOAuth.php: "WHERE discordID = ? LIMIT 1", no index, no ORDER BY)
      # landed on the LOWEST usersId, so that account keeps the link and the others lose it (they still log in by password).
      sql "SELECT o.usersId, o.usersUid, o.discordID FROM \`$OLD\`.users o WHERE o.discordID IS NOT NULL AND o.discordID <> ''
           AND o.usersId > (SELECT MIN(x.usersId) FROM \`$OLD\`.users x WHERE x.discordID = o.discordID)" > "$BK/discord-unlinked.tsv"
      sel="$(echo "$cols" | sed "s/\`discordID\`/CASE WHEN discordID IS NULL OR discordID = '' OR usersId = (SELECT MIN(x.usersId) FROM \`$OLD\`.users x WHERE x.discordID = u.discordID) THEN discordID ELSE NULL END/")"
      log "  users: $(wc -l < "$BK/discord-unlinked.tsv") duplicate Discord links dropped from the higher-ID account ($BK/discord-unlinked.tsv)"
    fi
    q "DROP TABLE IF EXISTS \`$NEW\`.\`$t\`"
    q "CREATE TABLE \`$NEW\`.\`$t\` LIKE \`$LIVE\`.\`$t\`"
    q "INSERT INTO \`$NEW\`.\`$t\` ($cols) SELECT $sel FROM \`$OLD\`.\`$t\` u"
    got="$(rows "$NEW" "$t")"
    [ "$got" = "$want" ] || die "$t: copied $got of $want rows — stopping (live untouched)"
    q "DROP TABLE \`$OLD\`.\`$t\`"   # one copy on disk at a time
    log "  built $t  ($got rows)"
  done

  # Owner choice: carry the new-box-only accounts whose email AND username are free in the old data, with new IDs.
  ucols="$(sql "SELECT GROUP_CONCAT(CONCAT('\`',column_name,'\`') ORDER BY ordinal_position) FROM information_schema.columns
                WHERE table_schema='$LIVE' AND table_name='users' AND column_name NOT IN ('usersId','teamID')")"
  q "DROP TABLE IF EXISTS \`$NEW\`.carry_map"
  q "CREATE TABLE \`$NEW\`.carry_map (newBoxId INT PRIMARY KEY, mergedId INT, usersUid VARCHAR(128))"
  q "SET @n := (SELECT MAX(usersId) FROM \`$NEW\`.users);
     INSERT INTO \`$NEW\`.carry_map (newBoxId, mergedId, usersUid)
     SELECT l.usersId, (@n := @n + 1), l.usersUid FROM \`$LIVE\`.users l
     WHERE NOT EXISTS (SELECT 1 FROM \`$NEW\`.users o WHERE LOWER(o.usersEmail) = LOWER(l.usersEmail))
       AND NOT EXISTS (SELECT 1 FROM \`$NEW\`.users o WHERE LOWER(o.usersUid)   = LOWER(l.usersUid))
       AND NOT EXISTS (SELECT 1 FROM \`$NEW\`.users o WHERE o.discordID = l.discordID AND l.discordID IS NOT NULL AND l.discordID <> '')
     ORDER BY l.usersId"
  q "INSERT INTO \`$NEW\`.users (usersId, $ucols) SELECT m.mergedId, $(echo "$ucols" | sed 's/`/l.`/; s/,`/,l.`/g')
     FROM \`$NEW\`.carry_map m JOIN \`$LIVE\`.users l ON l.usersId = m.newBoxId"
  sql "SELECT newBoxId, mergedId, usersUid FROM \`$NEW\`.carry_map ORDER BY newBoxId" > "$BK/carried-accounts.tsv"
  log "  carried $(wc -l < "$BK/carried-accounts.tsv") new-box accounts (map: $BK/carried-accounts.tsv)"
  q "DROP TABLE \`$NEW\`.carry_map"

  # Owner choice: keep the new Karabast client, owned by the OLD account with its owner's username.
  if [ -n "$shaun_owner" ]; then
    ccols="$(sql "SELECT GROUP_CONCAT(CONCAT('\`',column_name,'\`') ORDER BY ordinal_position) FROM information_schema.columns
                  WHERE table_schema='$LIVE' AND table_name='oauth_clients' AND column_name<>'user_id'")"
    q "INSERT IGNORE INTO \`$NEW\`.oauth_clients ($ccols, user_id)
       SELECT $ccols, (SELECT usersId FROM \`$NEW\`.users WHERE LOWER(usersUid)=LOWER('$owner_name'))
       FROM \`$LIVE\`.oauth_clients WHERE client_id='$CARRY_CLIENT'"
    log "  kept OAuth client $CARRY_CLIENT, owner -> old '$owner_name' ($(sql "SELECT user_id FROM \`$NEW\`.oauth_clients WHERE client_id='$CARRY_CLIENT'"))"
  fi
  tables_of "$NEW" > "$BK/built-tables.txt"   # what apply swaps: every table built into $NEW (this run and any earlier one)
  log "built $(wc -l < "$BK/built-tables.txt") tables. Backup: $BK. Live is untouched. Next: $0 apply"
  ;;
# ──────────────────────────────────────────────────────────────────────────────────────────────── apply
apply)
  BK="$(cat "$STATE/last" 2>/dev/null)"; [ -d "$BK" ] || die "no build found — run build first"
  [ -s "$BK/built-tables.txt" ] || die "$BK/built-tables.txt is empty"
  [ -d "$STAGED_GAMES" ] || die "staged deck folders are gone ($STAGED_GAMES)"
  for t in $(cat "$BK/built-tables.txt"); do has_table "$NEW" "$t" || die "$NEW.$t is missing"; done
  q "CREATE DATABASE IF NOT EXISTS \`$PREV\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
  ren=""
  for t in $(cat "$BK/built-tables.txt"); do ren="$ren\`$LIVE\`.\`$t\` TO \`$PREV\`.\`$t\`, \`$NEW\`.\`$t\` TO \`$LIVE\`.\`$t\`, "; done
  log "switch $(wc -l < "$BK/built-tables.txt") tables in one RENAME TABLE"
  q "RENAME TABLE ${ren%, }"
  log "switch deck folders"
  mv "$GAMES" "$BK/Games-live"
  mv "$STAGED_GAMES" "$GAMES"
  maxid="$(find "$GAMES" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | grep -E '^[0-9]+$' | sort -n | tail -1)"
  ctr="$(cat "$GAMES/GameIDCounter.txt" 2>/dev/null || echo 0)"
  [ "$ctr" -gt "$maxid" ] 2>/dev/null || { echo $((maxid + 1)) > "$GAMES/GameIDCounter.txt"; log "  counter set to $((maxid + 1))"; }
  chown -R "$WEB_USER:$WEB_USER" "$GAMES"
  systemctl reload apache2 && log "  apache reloaded"
  log "applied. Live is now the old box's data (+ carry-overs). Previous live: database $PREV + $BK. Undo: $0 rollback"
  ;;
# ──────────────────────────────────────────────────────────────────────────────────────────────── rollback
rollback)
  BK="$(cat "$STATE/last" 2>/dev/null)"; [ -d "$BK/Games-live" ] || die "nothing to roll back (no $BK/Games-live)"
  ren=""
  for t in $(cat "$BK/built-tables.txt"); do ren="$ren\`$LIVE\`.\`$t\` TO \`$NEW\`.\`$t\`, \`$PREV\`.\`$t\` TO \`$LIVE\`.\`$t\`, "; done
  q "RENAME TABLE ${ren%, }"
  mv "$GAMES" "$STAGED_GAMES"; mv "$BK/Games-live" "$GAMES"
  systemctl reload apache2
  log "rolled back: live is the new box's own data again (the old data sits in $NEW and $STAGED_GAMES)"
  ;;
*) sed -n '6,9p' "$0" | sed 's/^# \{0,1\}//'; exit 2 ;;
esac
