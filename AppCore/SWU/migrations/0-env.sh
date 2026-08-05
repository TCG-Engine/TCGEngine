#!/bin/bash
# Step 0 — SOURCE this, do not execute it.  . ./0-env.sh
#
# Sets and VALIDATES everything the numbered scripts read. Doing it here once means no script has
# to guess, and a wrong path fails now — with a clear message — instead of halfway through a
# migration at 2am.
#
# It exists because the paths are not where a shell expects them: LAMPP keeps mysql/php under
# /opt/lampp/bin, which is NOT on PATH, so a bare `mysql` is "command not found". Every script
# accepts an explicit binary for exactly that reason.
#
#   . ./0-env.sh                       # autodetect
#   . ./0-env.sh --lampp=/opt/lampp --backup=/var/backups/swustats-manual
#   . ./0-env.sh --check               # validate ONLY — see below
#
# --check is READ-ONLY: it verifies paths, binaries, deck tree and free space, and skips the two
# things the normal run writes — it does not create $BACKUP and does not prompt for or store the
# database password. Safe to run on a live box any time, including right now. Without it, a run
# today leaves a 0600 file containing the MySQL root password sitting in a timestamped directory
# until the §2.10 cleanup, which is a longer exposure than the window needs.
#
# Sets: LAMPP ROOT PHP_BIN MYSQL_BIN MYSQLDUMP_BIN BACKUP MYCNF DB
#       MYSQL / MYSQLDUMP are exported too, as aliases for the older runbook prose.

_swu_env_fail=0
_swu_ok()   { printf "   \033[32mok\033[0m   %s\n" "$*"; }
_swu_bad()  { printf "   \033[31mFAIL\033[0m %s\n" "$*"; _swu_env_fail=1; }

_swu_check=0
for _a in "$@"; do
  case "$_a" in
    --check)    _swu_check=1 ;;
    --lampp=*)  LAMPP="${_a#--lampp=}" ;;
    --root=*)   ROOT="${_a#--root=}" ;;
    --backup=*) BACKUP="${_a#--backup=}" ;;
    --db=*)     DB="${_a#--db=}" ;;
  esac
done

export LAMPP="${LAMPP:-/opt/lampp}"
export ROOT="${ROOT:-$LAMPP/htdocs/TCGEngine}"

# Prefer LAMPP's binaries; fall back to PATH so this also works on a dev box.
_pick() { [ -x "$1" ] && echo "$1" || command -v "$2" 2>/dev/null; }
export MYSQL_BIN="${MYSQL_BIN:-$(_pick "$LAMPP/bin/mysql" mysql)}"
export MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-$(_pick "$LAMPP/bin/mysqldump" mysqldump)}"
export PHP_BIN="${PHP_BIN:-$(_pick "$LAMPP/bin/php" php)}"
# Aliases, so older prose that says $MYSQL keeps working.
export MYSQL="$MYSQL_BIN"; export MYSQLDUMP="$MYSQLDUMP_BIN"

export STAMP="${STAMP:-$(date +%Y%m%d-%H%M)}"
export BACKUP="${BACKUP:-/var/backups/swustats-$STAMP}"

echo "== paths"
[ -d "$ROOT" ]        && _swu_ok "ROOT           $ROOT" || _swu_bad "ROOT does not exist: $ROOT"
[ -x "$MYSQL_BIN" ]   && _swu_ok "MYSQL_BIN      $MYSQL_BIN" || _swu_bad "no mysql client (set MYSQL_BIN)"
[ -x "$MYSQLDUMP_BIN" ] && _swu_ok "MYSQLDUMP_BIN  $MYSQLDUMP_BIN" || _swu_bad "no mysqldump (set MYSQLDUMP_BIN)"
[ -x "$PHP_BIN" ]     && _swu_ok "PHP_BIN        $PHP_BIN" || _swu_bad "no php (set PHP_BIN)"
[ -d "$ROOT/SWUDeck/Games" ] && _swu_ok "deck tree      $(find "$ROOT/SWUDeck/Games" -name Gamestate.txt 2>/dev/null | wc -l) files" \
  || _swu_bad "no deck tree at $ROOT/SWUDeck/Games"

echo "== backup dir"
if [ "$_swu_check" -eq 1 ]; then
  # Report on the nearest existing ancestor rather than creating anything.
  _probe="$BACKUP"; while [ ! -d "$_probe" ] && [ "$_probe" != "/" ]; do _probe=$(dirname "$_probe"); done
  _free=$(df -Pm "$_probe" 2>/dev/null | awk 'NR==2{print $4+0}')
  if [ "${_free:-0}" -ge 8000 ]; then _swu_ok "space          ${_free} MB free on $_probe (need ~8000)"
  else _swu_bad "only ${_free:-?} MB free on $_probe; need ~8000"; fi
  case "$BACKUP" in "$ROOT"*) _swu_bad "BACKUP would be INSIDE the web root — publicly downloadable" ;; esac
  _swu_ok "would use      $BACKUP  (not created — --check)"
  unset _probe _free
elif mkdir -p "$BACKUP" 2>/dev/null; then
  _free=$(df -Pm "$BACKUP" 2>/dev/null | awk 'NR==2{print $4+0}')
  # Measured against prod 2026-08-06, not extrapolated (an earlier guess of 20 GB was 140x too
  # high — it assumed dev-box deck images, which prod barely has):
  #   database dump, gzipped        ~1 GB   (2.9 GB of tables)
  #   both deck archives           ~0.2 GB  (105k gamestates compress to 72 MB; 282 images = 60 MB)
  #   *_new tables during 02       ~3 GB    <- the real cost, and it PERSISTS as *_old until §2.10
  #   shared art corpus at step 15 ~0.6 GB
  # 8 GB leaves MySQL room to work. Running out mid-INSERT on a 2.6M-row table is an ugly failure.
  _need=8000
  if [ "${_free:-0}" -ge "$_need" ]; then
    _swu_ok "BACKUP         $BACKUP  (${_free} MB free)"
  else
    _swu_bad "BACKUP $BACKUP has only ${_free:-?} MB free; want ~${_need} MB"
    echo "          ~1 GB dump + ~0.2 GB deck archives + ~3 GB of *_new tables that persist"
    echo "          until §2.10. Point --backup= at a bigger volume, or free space first."
  fi
  case "$BACKUP" in "$ROOT"*) _swu_bad "BACKUP is INSIDE the web root — it would be publicly downloadable" ;; esac
else
  _swu_bad "cannot create $BACKUP — wrong user, or the parent does not exist"
fi
unset _free _need

echo "== database credentials"
if [ "$_swu_check" -eq 1 ] && [ -z "${MYCNF:-}" ]; then
  echo "   (skipped — --check will not prompt for or store a password)"
  echo "   Connection and DB resolution are therefore UNVERIFIED. Re-run without --check on the day."
else
# Written once, 0600, and deleted in §2.10. Never -p on the command line: that exposes the password
# in `ps` to every user on the box, and these scripts run in loops and under `time`.
if [ -n "${MYCNF:-}" ] && [ -f "${MYCNF#--defaults-extra-file=}" ]; then
  _swu_ok "MYCNF          reusing $MYCNF"
else
  umask 077
  # No TTY means `read` returns empty and we would silently write a credentials file with a blank
  # password — which then fails at connect time with a confusing error rather than here.
  if [ ! -t 0 ]; then
    _swu_bad "no terminal to prompt for the password. Set MYCNF yourself:"
    echo "          printf '[client]\\nuser=root\\npassword=***\\n' > \$BACKUP/my.cnf && chmod 600 \$BACKUP/my.cnf"
    echo "          export MYCNF=--defaults-extra-file=\$BACKUP/my.cnf"
    return 1 2>/dev/null || exit 1
  fi
  read -rsp "   MySQL root password: " _pw; echo
  [ -n "$_pw" ] || { _swu_bad "empty password — refusing to write a credentials file"; unset _pw; return 1 2>/dev/null || exit 1; }
  printf '[client]\nuser=root\npassword=%s\n' "$_pw" > "$BACKUP/my.cnf"
  unset _pw
  export MYCNF="--defaults-extra-file=$BACKUP/my.cnf"
  _swu_ok "MYCNF          $BACKUP/my.cnf (0600 — delete it in §2.10)"
fi

if [ -x "$MYSQL_BIN" ]; then
  if "$MYSQL_BIN" $MYCNF -N -B -e "SELECT 1" >/dev/null 2>&1; then
    _swu_ok "connection     works"
    # Ask the schema which database holds the stats, rather than assuming. ConnectionManager
    # defaults to `swuonline`; the clone is `swudeck`. Guessing wrong migrates nothing, loudly.
    if [ -z "${DB:-}" ]; then
      DB=$("$MYSQL_BIN" $MYCNF -N -B -e "SELECT TABLE_SCHEMA FROM information_schema.TABLES WHERE TABLE_NAME='carddeckstats' LIMIT 1;" 2>/dev/null)
    fi
    export DB
    [ -n "$DB" ] && _swu_ok "DB             $DB  (resolved from information_schema)" \
                 || _swu_bad "could not find a schema containing carddeckstats — set DB by hand"
  else
    _swu_bad "cannot connect with those credentials"
  fi
fi

fi
echo
if [ "$_swu_check" -eq 1 ]; then
  if [ "$_swu_env_fail" -eq 0 ]; then
    printf "\033[32mCHECK PASSED — nothing was created or written.\033[0m Re-run without --check on the day.\n"
  else
    printf "\033[31mCHECK FAILED — fix the above before deploy day.\033[0m\n"
  fi
elif [ "$_swu_env_fail" -eq 0 ]; then
  printf "\033[32mEnvironment ready.\033[0m Next: 1-maintenance.sh on --ip=\$(curl -s ifconfig.me)\n"
else
  printf "\033[31mEnvironment NOT ready — fix the FAILs above before running anything.\033[0m\n"
fi
unset _swu_env_fail _swu_check _a
