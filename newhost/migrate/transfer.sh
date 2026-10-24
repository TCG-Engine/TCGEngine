#!/usr/bin/env bash
#
# transfer.sh - move an export-old-box.sh directory: old box -> this machine -> new box, checking SHA256SUMS at each hop.
# Runs on YOUR machine (not a server), with SSH host aliases for both boxes (~/.ssh/config).
#
#   newhost/migrate/transfer.sh <old alias> <remote export dir> <new alias> [local dir]
#   newhost/migrate/transfer.sh swustats-prev /root/tcg-export-20261024 swustats
#
# The local copy is kept (default ~/tcg-migration/<export dir name>/): it is the only copy of the old box's data that is off
# both servers. The new box receives it at /var/backups/tcg-old/ (what stage-on-new-box.sh reads). rsync resumes an
# interrupted copy, so re-running after a dropped connection continues where it stopped.
#
set -euo pipefail

SRC="${1:-}"; SRC_DIR="${2:-}"; DST="${3:-}"
[ -n "$SRC" ] && [ -n "$SRC_DIR" ] && [ -n "$DST" ] || { sed -n '3,12p' "$0" | sed 's/^# \{0,1\}//'; exit 2; }
LOCAL="${4:-$HOME/tcg-migration/$(basename "$SRC_DIR")}"
DST_DIR="/var/backups/tcg-old"
log() { printf '%s  %s\n' "$(date -u +%FT%TZ)" "$*" >&2; }
die() { log "ERROR: $*"; exit 1; }
SHA="$(command -v sha256sum || echo 'shasum -a 256')"

ssh -o BatchMode=yes "$SRC" "test -f '$SRC_DIR/SHA256SUMS'" || die "$SRC:$SRC_DIR has no SHA256SUMS — is the export finished?"

log "pull  $SRC:$SRC_DIR -> $LOCAL"
mkdir -p "$LOCAL"; chmod 700 "$LOCAL"
rsync -a --partial -e 'ssh -o BatchMode=yes' "$SRC:$SRC_DIR/" "$LOCAL/"
log "verify the local copy"
( cd "$LOCAL" && $SHA -c SHA256SUMS --quiet ) || die "the local copy does not match SHA256SUMS — re-run to resume/repair"
log "  ok  local copy matches ($(du -sh "$LOCAL" | cut -f1))"

log "push  $LOCAL -> $DST:$DST_DIR"
ssh -o BatchMode=yes "$DST" "mkdir -p '$DST_DIR' && chmod 700 '$DST_DIR'"
rsync -a --partial -e 'ssh -o BatchMode=yes' "$LOCAL/" "$DST:$DST_DIR/"
log "verify the copy on $DST"
ssh -o BatchMode=yes "$DST" "cd '$DST_DIR' && sha256sum -c SHA256SUMS --quiet" || die "the copy on $DST does not match SHA256SUMS"
log "  ok  $DST:$DST_DIR matches. Next, on $DST: stage-on-new-box.sh $DST_DIR"
