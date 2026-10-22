#!/usr/bin/env bash
#
# setup-box.sh - take a FRESH Ubuntu 24.04 / Debian 12 server to a running TCGEngine box.
#
# The native-LAMP successor to the XAMPP kit in this directory: Apache + mod_php + MariaDB from the
# distro, PHP from the ondrej (Ubuntu) / sury (Debian) package source. Same stack and speed as
# XAMPP, but a PHP security fix is an `apt upgrade` (or lands by itself, see UNATTENDED_UPGRADES)
# instead of a wait for a new XAMPP bundle — XAMPP's PHP 8.2.12 is what the 2026-10-21 PHP RCE hit.
#
# One config file (box.conf, see box.conf.example) describes the box. The run is split into
# phases; every phase is idempotent, so re-running the whole script, or one phase, is safe:
#
#   system    apt upgrade, base tools, timezone, swap on small boxes, unattended security upgrades
#   ssh       key-only login, only if a key is installed (never locks you out)
#   firewall  ufw: SSH from ADMIN_IPS + your current address; 80/443 from Cloudflare only
#   stack     Apache (prefork + mod_php), PHP + the extensions the app needs, MariaDB, sizing
#   app       clone/pull the repo, Composer deps, secret files from SECRETS_DIR
#   db        app user + one database per site: restore an EMPTY db from RESTORE_DIR, else schema
#   sites     TLS cert, deny-all catch-all vhost, one vhost per site (SetEnv), docroot .htaccess
#   fail2ban  harden-fail2ban.sh (STACK=native): real client IP behind Cloudflare + tuned jails
#   generate  GenerateSites all, zzGameCodeGenerator / zzCardCodeGenerator per root (as www-data)
#   backups   backup-db.sh nightly via cron, plus one run now
#   verify    end-to-end checks; prints PASS/FAIL per check
#
# Usage:
#   sudo ./setup-box.sh                         # every phase, with newhost/box.conf
#   sudo ./setup-box.sh --config /root/box.conf
#   sudo ./setup-box.sh --only "sites verify"   # just these phases
#   sudo ./setup-box.sh --skip generate         # all but these
#   sudo ./setup-box.sh --list                  # print the phases and exit
#   Add --yes to skip the confirmation prompt.
#
# A full transcript is written to /var/log/tcgengine-setup/.
#
# Safety rules this script keeps:
#   - It never drops or overwrites a database that has tables, and never overwrites a secret file.
#   - It never resets or stashes a git checkout with local changes; it stops and tells you.
#   - SSH hardening and the firewall always keep the address you are connected from allowed.
#   - Apache config is tested (apache2ctl -t) before every reload.
#
set -euo pipefail
# ⚠ pipefail + `cmd | grep -q`: grep -q exits at the first match, cmd then dies of SIGPIPE, and
# pipefail reports the whole pipe as FAILED. Pipes fed by a real command use `grep … >/dev/null`
# (reads everything) instead. Only a short printf/echo is safe with -q.

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PHASES=(system ssh firewall stack app db sites fail2ban generate backups verify)

# ---------------------------------------------------------------------------
# Flags
# ---------------------------------------------------------------------------
CONFIG="${BOX_CONF:-$SCRIPT_DIR/box.conf}"
ONLY=""; SKIP=""; ASSUME_YES=0; want=""
for arg in "$@"; do
  if [ -n "$want" ]; then
    case "$want" in config) CONFIG="$arg" ;; only) ONLY="$arg" ;; skip) SKIP="$arg" ;; esac
    want=""; continue
  fi
  case "$arg" in
    --config|--only|--skip) want="${arg#--}" ;;
    --yes|-y)  ASSUME_YES=1 ;;
    --list)    printf '%s\n' "${PHASES[@]}"; exit 0 ;;
    -h|--help) grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    *) echo "Unknown option: $arg (see --help)" >&2; exit 2 ;;
  esac
done
[ -z "$want" ] || { echo "--$want needs a value" >&2; exit 2; }
for p in $ONLY $SKIP; do
  printf '%s\n' "${PHASES[@]}" | grep -Fxq "$p" || { echo "unknown phase '$p' (see --list)" >&2; exit 2; }
done

# ---------------------------------------------------------------------------
# Output helpers
# ---------------------------------------------------------------------------
log()  { printf '\033[1;36m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ok\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !!\033[0m %s\n' "$*"; WARNINGS+=("$*"); }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }
phase(){ printf '\n\033[1;35m━━━ %s ━━━\033[0m\n' "$*"; }
WARNINGS=()

# ---------------------------------------------------------------------------
# Config: defaults, then box.conf
# ---------------------------------------------------------------------------
SITES=(); GAME_ROOTS=(); CARD_ROOTS=(); ADMIN_IPS=()
DB_APP_USER="tcgengine"; DB_APP_PASS=""; RESTORE_DIR=""; SECRETS_DIR=""
SSH_PORT=22; HARDEN_SSH="auto"; CF_ONLY_WEB=1; ORIGIN_CERT=""; ORIGIN_KEY=""
REPO_URL="https://github.com/TCG-Engine/TCGEngine.git"; REPO_BRANCH="main"; APP_PARENT="/var/www/html"
PHP_VERSION="8.2"; PHP_MEMORY_LIMIT="512M"; APCU_SHM="128M"; APACHE_MAX_WORKERS=""; MARIADB_BUFFER_POOL=""
SWAP_SIZE="2G"; TIMEZONE="UTC"; INSTALL_REDIS=0; UNATTENDED_UPGRADES=1; UPGRADE_TIME="09:00"
BACKUP_DIR="/var/backups/tcgengine"; BACKUP_KEEP_DAYS=14; BACKUP_TIME="08:30"; BACKUP_RCLONE_REMOTE=""

[ -f "$CONFIG" ] || die "config not found: $CONFIG  (cp $SCRIPT_DIR/box.conf.example $SCRIPT_DIR/box.conf and fill it in)"
# shellcheck disable=SC1090
. "$CONFIG"
APP_ROOT="$APP_PARENT/TCGEngine"
TS="$(date -u +%Y%m%d-%H%M%S)"
STATE_DIR="/var/lib/tcgengine-setup"
LOG_DIR="/var/log/tcgengine-setup"
WEB_USER="www-data"

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
[ "$(id -u)" -eq 0 ] || die "must run as root (sudo)."
[ -f /etc/os-release ] || die "/etc/os-release missing — this script targets Ubuntu / Debian."
# shellcheck disable=SC1091
. /etc/os-release
OS_ID="${ID:-}"; OS_CODENAME="${VERSION_CODENAME:-}"
case "$OS_ID" in
  ubuntu|debian) ;;
  *) die "unsupported OS '$OS_ID' (Ubuntu 24.04 or Debian 12 expected)." ;;
esac
[ -n "$OS_CODENAME" ] || die "could not read VERSION_CODENAME from /etc/os-release."

[ "${#SITES[@]}" -gt 0 ] || die "SITES is empty in $CONFIG."
SITE_DOMAINS=(); SITE_ROOTS=(); SITE_DBS=()
for entry in "${SITES[@]}"; do
  IFS='|' read -r d r b <<<"$entry"
  echo "$d" | grep -Eq '^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?\.[A-Za-z]{2,}$' || die "SITES: bad domain in '$entry'."
  echo "$r" | grep -Eq '^[A-Za-z0-9_]+$' || die "SITES: bad Root in '$entry'."
  echo "$b" | grep -Eq '^[A-Za-z0-9_]+$' || die "SITES: bad db in '$entry'."
  SITE_DOMAINS+=("$d"); SITE_ROOTS+=("$r"); SITE_DBS+=("$b")
done
[ "${#GAME_ROOTS[@]}" -gt 0 ] || GAME_ROOTS=("${SITE_ROOTS[@]}")
[ "${#CARD_ROOTS[@]}" -gt 0 ] || CARD_ROOTS=("${SITE_ROOTS[@]}")
mapfile -t UNIQUE_DBS < <(printf '%s\n' "${SITE_DBS[@]}" | awk '!seen[$0]++')

[ -n "$DB_APP_PASS" ] || die "DB_APP_PASS is empty in $CONFIG (try: openssl rand -hex 24)."
printf '%s' "$DB_APP_PASS" | grep -Eq "^[A-Za-z0-9._~@%^+=:,-]{16,}$" \
  || die "DB_APP_PASS must be 16+ chars of [A-Za-z0-9._~@%^+=:,-] (it goes into SQL and Apache SetEnv)."
echo "$DB_APP_USER" | grep -Eq '^[A-Za-z0-9_]{1,32}$' || die "DB_APP_USER must be [A-Za-z0-9_]."
echo "$PHP_VERSION" | grep -Eq '^[0-9]\.[0-9]$' || die "PHP_VERSION must look like 8.2."
for t in "$UPGRADE_TIME" "$BACKUP_TIME"; do
  echo "$t" | grep -Eq '^([01][0-9]|2[0-3]):[0-5][0-9]$' || die "times must be HH:MM (got '$t')."
done
[ -z "$ORIGIN_CERT$ORIGIN_KEY" ] || { [ -f "$ORIGIN_CERT" ] && [ -f "$ORIGIN_KEY" ]; } \
  || die "ORIGIN_CERT / ORIGIN_KEY must both point at existing files (or both be empty)."
[ -z "$RESTORE_DIR" ] || [ -d "$RESTORE_DIR" ] || die "RESTORE_DIR '$RESTORE_DIR' is not a directory."
[ -z "$SECRETS_DIR" ] || [ -d "$SECRETS_DIR" ] || die "SECRETS_DIR '$SECRETS_DIR' is not a directory."

want_phase() {
  local p="$1"
  if [ -n "$ONLY" ]; then printf '%s\n' $ONLY | grep -Fxq "$p" || return 1; fi
  if [ -n "$SKIP" ]; then printf '%s\n' $SKIP | grep -Fxq "$p" && return 1; fi
  return 0
}
RUN_PHASES=(); for p in "${PHASES[@]}"; do want_phase "$p" && RUN_PHASES+=("$p"); done
[ "${#RUN_PHASES[@]}" -gt 0 ] || die "no phases selected."

# Where am I connected from? Always kept reachable by the ssh + firewall phases.
CURRENT_SSH_IP="$(printf '%s' "${SSH_CONNECTION:-${SSH_CLIENT:-}}" | awk '{print $1}')"

RAM_MB="$(awk '/MemTotal/{print int($2/1024)}' /proc/meminfo)"
cat <<PLAN

TCGEngine box setup  ($(hostname), $PRETTY_NAME, ${RAM_MB} MB RAM)
  config   : $CONFIG
  phases   : ${RUN_PHASES[*]}
  sites    : $(for i in "${!SITE_DOMAINS[@]}"; do printf '%s->%s(%s) ' "${SITE_DOMAINS[$i]}" "${SITE_ROOTS[$i]}" "${SITE_DBS[$i]}"; done)
  app root : $APP_ROOT  ($REPO_URL @ $REPO_BRANCH)
  php      : $PHP_VERSION     db user: $DB_APP_USER
  restore  : ${RESTORE_DIR:-(none — empty databases get the fresh schema)}
  secrets  : ${SECRETS_DIR:-(none)}
  ssh from : ${ADMIN_IPS[*]:-(any)} ${CURRENT_SSH_IP:+ + current session $CURRENT_SSH_IP}
PLAN
if [ "$ASSUME_YES" -eq 0 ]; then
  read -r -p "Proceed? [y/N] " reply
  case "$reply" in y|Y|yes|YES) ;; *) echo "aborted."; exit 1 ;; esac
fi

mkdir -p "$STATE_DIR" "$LOG_DIR"
chmod 700 "$STATE_DIR" "$LOG_DIR"
exec > >(tee -a "$LOG_DIR/setup-$TS.log") 2>&1
log "transcript: $LOG_DIR/setup-$TS.log"

export DEBIAN_FRONTEND=noninteractive
# Retries: package mirrors and the PHP source can 503 under load (they did on 2026-10-21, the PHP
# RCE patch day) — a transient error should not abort a provisioning run.
APT_OPTS=(-y -o Acquire::Retries=8 -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold)
APT_TRIES=10; APT_WAIT=60   # up to ~15 min of a busy mirror before giving up
# apt_update [package] — `apt-get update` exits 0 even when a source's index failed to download (a
# 503 from the PHP source), which then shows up later as "Unable to locate package". With a package
# named, retry until that package actually has an install candidate.
apt_update() {
  local n
  for n in $(seq 1 "$APT_TRIES"); do
    apt-get update -y -o Acquire::Retries=8 || true
    if [ -z "${1:-}" ] || apt-cache policy "$1" 2>/dev/null | grep -E 'Candidate: [0-9]' >/dev/null; then return 0; fi
    log "package index for '$1' did not load (source busy?) — retry $n/$APT_TRIES in ${APT_WAIT}s"; sleep "$APT_WAIT"
  done
  die "could not load the package index for '$1' after $APT_TRIES tries — the source may be down; re-run later"
}
# Outer retry too: apt keeps what it already downloaded, so each attempt only fetches what failed.
apt_install() {
  local n
  for n in $(seq 1 "$APT_TRIES"); do
    apt-get install "${APT_OPTS[@]}" --no-install-recommends "$@" && return 0
    log "apt install attempt $n/$APT_TRIES failed (mirror busy?) — refreshing the index, retrying in ${APT_WAIT}s"; sleep "$APT_WAIT"
    apt-get update -y -o Acquire::Retries=8 >/dev/null 2>&1 || true
  done
  die "apt install failed $APT_TRIES times: $*"
}

# backup <file> -> $STATE_DIR/backups-<ts>/ (once per run)
backup() {
  local f="$1"; [ -f "$f" ] || return 0
  local dir="$STATE_DIR/backups-$TS"; mkdir -p "$dir"
  local dest="$dir/$(echo "$f" | sed 's#^/##; s#/#_#g')"
  [ -f "$dest" ] || cp -p "$f" "$dest"
}
# write_if_changed <dest> <mode>  (content on stdin). Returns 0 if the file changed.
write_if_changed() {
  local dest="$1" mode="$2" tmp; tmp="$(mktemp)"
  cat > "$tmp"
  if [ -f "$dest" ] && cmp -s "$tmp" "$dest"; then rm -f "$tmp"; return 1; fi
  backup "$dest"; mkdir -p "$(dirname "$dest")"
  install -m "$mode" "$tmp" "$dest"; rm -f "$tmp"; return 0
}
size_mb() {   # "128M" / "2G" / "512" -> MB
  local v="$1"
  case "$v" in *[Gg]) echo $(( ${v%[Gg]} * 1024 )) ;; *[Mm]) echo "${v%[Mm]}" ;; *) echo "$v" ;; esac
}
as_web() { runuser -u "$WEB_USER" -- "$@"; }
mysql_root() { mysql --protocol=socket -u root "$@"; }   # MariaDB root = unix_socket auth

# Cloudflare ranges: live list, else the cache harden-fail2ban.sh keeps, else its pinned list
# (read from that script so there is ONE pinned copy in the repo).
cf_ranges() {
  local fetched=""
  fetched="$( { curl -fsS --max-time 10 https://www.cloudflare.com/ips-v4; echo; \
               curl -fsS --max-time 10 https://www.cloudflare.com/ips-v6; } 2>/dev/null \
             | tr -d '\r' | sed '/^[[:space:]]*$/d' )" || fetched=""
  if [ -n "$fetched" ] && ! printf '%s\n' "$fetched" | grep -qvE '^[0-9a-fA-F:.]+/[0-9]{1,3}$'; then
    printf '%s\n' "$fetched"; return
  fi
  if [ -f /etc/fail2ban/cloudflare-ips.local ]; then
    sed '/^[[:space:]]*$/d; /^#/d' /etc/fail2ban/cloudflare-ips.local; return
  fi
  sed -n "/<<'PINNED'/,/^PINNED$/p" "$SCRIPT_DIR/harden-fail2ban.sh" | sed '1d;$d'
}

# =============================================================================
# Phase: system
# =============================================================================
phase_system() {
  phase "system"
  log "apt update + upgrade"
  apt_update
  apt-get upgrade "${APT_OPTS[@]}"
  # python3-systemd: fail2ban's journal backend (the sshd jail on boxes without /var/log/auth.log).
  apt_install ca-certificates curl gnupg git unzip rsync cron logrotate ufw fail2ban python3 python3-systemd \
              unattended-upgrades apt-listchanges ssl-cert openssl
  systemctl enable --now cron >/dev/null 2>&1 || true
  [ "$OS_ID" = ubuntu ] && apt_install software-properties-common
  ok "base packages installed"

  if [ "$(timedatectl show -p Timezone --value 2>/dev/null || true)" != "$TIMEZONE" ]; then
    timedatectl set-timezone "$TIMEZONE" && ok "timezone -> $TIMEZONE" || warn "could not set timezone"
  else
    ok "timezone already $TIMEZONE"
  fi

  # Swap: a 1 GB box running the generators (memory_limit=-1) and MariaDB needs a cushion.
  if [ "$RAM_MB" -lt 2048 ] && [ -z "$(swapon --noheadings 2>/dev/null)" ]; then
    log "RAM ${RAM_MB} MB and no swap: creating /swapfile ($SWAP_SIZE)"
    fallocate -l "$SWAP_SIZE" /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count="$(size_mb "$SWAP_SIZE")" status=none
    chmod 600 /swapfile; mkswap /swapfile >/dev/null; swapon /swapfile
    grep -q '^/swapfile ' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
    printf 'vm.swappiness=10\n' | write_if_changed /etc/sysctl.d/90-tcgengine-swap.conf 644 && sysctl -q --system || true
    ok "swap on"
  else
    ok "swap: $( [ -n "$(swapon --noheadings 2>/dev/null)" ] && echo present || echo "not needed (${RAM_MB} MB RAM)")"
  fi

  if [ "$UNATTENDED_UPGRADES" -eq 1 ]; then
    # Security updates from the distro AND from the PHP package source, so a PHP fix needs no one.
    local php_origin
    if [ "$OS_ID" = ubuntu ]; then php_origin='origin=LP-PPA-ondrej-php'; else php_origin='origin=deb.sury.org'; fi
    write_if_changed /etc/apt/apt.conf.d/52tcgengine-unattended 644 <<EOF || true
// Managed by newhost/setup-box.sh
Unattended-Upgrade::Origins-Pattern {
        "origin=\${distro_id},archive=\${distro_codename}-security";
        "origin=Debian,codename=\${distro_codename},label=Debian-Security";
        "$php_origin";
};
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Automatic-Reboot "false";
EOF
    write_if_changed /etc/apt/apt.conf.d/20auto-upgrades 644 <<'EOF' || true
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
EOF
    # A PHP upgrade restarts Apache, which clears APCu (live games' seat keys): run it at a quiet hour.
    if write_if_changed /etc/systemd/system/apt-daily-upgrade.timer.d/tcgengine.conf 644 <<EOF
[Timer]
OnCalendar=
OnCalendar=*-*-* $UPGRADE_TIME:00 UTC
RandomizedDelaySec=0
EOF
    then systemctl daemon-reload; fi
    systemctl enable --now apt-daily-upgrade.timer >/dev/null 2>&1 || true
    ok "unattended security upgrades on (distro + PHP source), daily at $UPGRADE_TIME UTC"
  else
    warn "UNATTENDED_UPGRADES=0 — PHP fixes will need a manual apt upgrade"
  fi
}

# =============================================================================
# Phase: ssh
# =============================================================================
phase_ssh() {
  phase "ssh"
  if [ "$HARDEN_SSH" = "0" ]; then warn "HARDEN_SSH=0 — sshd left as it is"; return; fi
  if ! grep -Eq '(^|[[:space:]])(ssh-(rsa|ed25519|dss)|ecdsa-sha2-[a-z0-9]+|sk-[a-z0-9@.-]+) AAAA' /root/.ssh/authorized_keys 2>/dev/null; then
    warn "no key in /root/.ssh/authorized_keys — NOT disabling password login (it would lock you out)."
    warn "  add your key (ssh-copy-id root@<box>) and re-run:  $0 --only ssh"
    return
  fi
  # A key on file is not proof YOU hold it (providers inject their own). Under 'auto', harden only
  # when this very session logged in with a key; HARDEN_SSH=1 skips the check.
  if [ "$HARDEN_SSH" = "auto" ]; then
    local cport method=""
    cport="$(printf '%s' "${SSH_CONNECTION:-}" | awk '{print $2}')"
    if [ -n "$CURRENT_SSH_IP" ] && [ -n "$cport" ]; then
      method="$( { journalctl -o cat _COMM=sshd 2>/dev/null; cat /var/log/auth.log 2>/dev/null; } \
                 | grep -E "Accepted [a-z-]+ for [^ ]+ from $CURRENT_SSH_IP port $cport( |$)" | tail -1 \
                 | sed -E 's/.*Accepted ([a-z-]+) .*/\1/' )"
    fi
    case "$method" in
      publickey) ok "this session logged in with a key — safe to go key-only" ;;
      "") warn "could not tell how this session logged in — NOT disabling passwords. Re-run with HARDEN_SSH=1 once a key login works."; return ;;
      *)  warn "this session logged in with '$method', not a key — NOT disabling passwords. Log in with your key, then: $0 --only ssh"; return ;;
    esac
  fi
  # 01- so it sorts BEFORE cloud-init's 50-cloud-init.conf: sshd keeps the FIRST value it reads.
  if write_if_changed /etc/ssh/sshd_config.d/01-tcgengine-hardening.conf 644 <<EOF
# Managed by newhost/setup-box.sh — key-only login.
Port $SSH_PORT
PermitRootLogin prohibit-password
PasswordAuthentication no
KbdInteractiveAuthentication no
PubkeyAuthentication yes
MaxAuthTries 4
X11Forwarding no
EOF
  then
    # Ubuntu 24.04 socket-activates sshd: until a daemon has started, /run/sshd does not exist and
    # `sshd -t` fails with "Missing privilege separation directory" — on a perfectly good config.
    install -d -m 755 /run/sshd
    if sshd -t; then
      # Socket-activated and idle: nothing to reload; the next connection starts sshd with the new config.
      if systemctl is-active --quiet ssh 2>/dev/null; then systemctl reload ssh
      elif systemctl is-active --quiet sshd 2>/dev/null; then systemctl reload sshd; fi
      ok "sshd: key-only login (port $SSH_PORT). Keep this session open and test a NEW login first."
    else
      rm -f /etc/ssh/sshd_config.d/01-tcgengine-hardening.conf
      die "sshd config test failed — hardening removed, sshd unchanged."
    fi
  else
    ok "sshd hardening already in place"
  fi
}

# =============================================================================
# Phase: firewall
# =============================================================================
phase_firewall() {
  phase "firewall (ufw)"
  local ssh_from=("${ADMIN_IPS[@]}")
  [ -n "$CURRENT_SSH_IP" ] && ssh_from+=("$CURRENT_SSH_IP")
  # Rebuilt from scratch every run so the rules always equal the config. ufw reset backs up the
  # old rules and leaves the firewall OFF until `enable`, so the SSH rule is in before it closes.
  ufw --force reset >/dev/null
  ufw default deny incoming >/dev/null
  ufw default allow outgoing >/dev/null
  if [ "${#ssh_from[@]}" -gt 0 ]; then
    for ip in $(printf '%s\n' "${ssh_from[@]}" | awk '!seen[$0]++'); do
      ufw allow from "$ip" to any port "$SSH_PORT" proto tcp comment 'ssh admin' >/dev/null
    done
    ok "ssh ($SSH_PORT) from: $(printf '%s ' "${ssh_from[@]}")"
  else
    ufw allow "$SSH_PORT"/tcp comment 'ssh (any)' >/dev/null
    warn "ssh open to the world — set ADMIN_IPS or restrict it in the provider's firewall"
  fi
  if [ "$CF_ONLY_WEB" -eq 1 ]; then
    local n=0
    while read -r cidr; do
      [ -n "$cidr" ] || continue
      ufw allow proto tcp from "$cidr" to any port 80,443 comment 'cloudflare' >/dev/null; n=$((n+1))
    done < <(cf_ranges)
    [ "$n" -gt 0 ] || die "no Cloudflare ranges resolved — refusing to close 80/443."
    ok "80/443 from Cloudflare only ($n ranges)"
  else
    ufw allow 80,443/tcp comment 'web' >/dev/null
    warn "80/443 open to everyone (CF_ONLY_WEB=0) — the origin can be hit around the CDN"
  fi
  ufw --force enable >/dev/null
  ok "ufw enabled"
  ufw status numbered | sed 's/^/    /'
}

# =============================================================================
# Phase: stack
# =============================================================================
phase_stack() {
  phase "stack (Apache + PHP $PHP_VERSION + MariaDB)"
  local V="$PHP_VERSION"
  if [ "$OS_ID" = ubuntu ]; then
    command -v add-apt-repository >/dev/null || apt_install software-properties-common
    if ! grep -rqs 'ondrej/php' /etc/apt/sources.list.d/; then
      log "adding ppa:ondrej/php"
      add-apt-repository -y ppa:ondrej/php
    fi
  else
    if [ ! -f /etc/apt/sources.list.d/php-sury.list ]; then
      log "adding packages.sury.org/php"
      curl -fsSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/deb.sury.org-php.gpg
      echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $OS_CODENAME main" \
        > /etc/apt/sources.list.d/php-sury.list
    fi
  fi
  apt_update "php$V-cli"   # the PHP source's index must have loaded, not just "update exited 0"
  # The extension set mirrors the repo's Dockerfile (imap, apcu, imagick, gd+webp, zip, mysqli,
  # pdo_mysql, shmop, bz2, opcache) plus the usual runtime ones. shmop ships in php-common.
  local pkgs=(apache2 mariadb-server mariadb-client imagemagick
              "php$V" "libapache2-mod-php$V" "php$V-cli" "php$V-common" "php$V-mysql" "php$V-gd"
              "php$V-imagick" "php$V-apcu" "php$V-zip" "php$V-bz2" "php$V-imap" "php$V-mbstring"
              "php$V-curl" "php$V-xml" "php$V-intl" "php$V-opcache" "php$V-readline")
  [ "$INSTALL_REDIS" -eq 1 ] && pkgs+=(redis-server "php$V-redis")
  # ImageMagick's WebP coder lives in the libmagickcore-*-extra package, a Recommends that
  # --no-install-recommends would skip — and the card-art pipeline writes WebP through Imagick.
  local extra
  extra="$(apt-cache pkgnames 2>/dev/null | grep -E '^libmagickcore-[67]\.q16-[0-9]+-extra$' | sort | tail -1 || true)"
  [ -n "$extra" ] && pkgs+=("$extra")
  apt_install "${pkgs[@]}"
  ok "packages installed ($(php -r 'echo PHP_VERSION;'))"

  # mod_php needs the prefork MPM; the package usually switches it, make sure.
  a2dismod -q mpm_event mpm_worker >/dev/null 2>&1 || true
  a2enmod -q mpm_prefork "php$V" rewrite ssl headers remoteip >/dev/null
  a2dissite -q 000-default default-ssl >/dev/null 2>&1 || true

  # Sizing from RAM (overridable). PHP workers ~45 MB each; leave room for MariaDB, APCu, OPcache.
  local apcu_mb; apcu_mb="$(size_mb "$APCU_SHM")"
  local workers="$APACHE_MAX_WORKERS"
  if [ -z "$workers" ]; then
    workers=$(( (RAM_MB - 350 - apcu_mb - 128) / 45 ))
    [ "$workers" -lt 8 ] && workers=8; [ "$workers" -gt 150 ] && workers=150
  fi
  local pool="$MARIADB_BUFFER_POOL"
  if [ -z "$pool" ]; then
    if [ "$RAM_MB" -lt 2048 ]; then pool=128M; elif [ "$RAM_MB" -lt 4096 ]; then pool=256M; else pool=512M; fi
  fi

  write_if_changed /etc/apache2/conf-available/tcgengine.conf 644 <<EOF || true
# Managed by newhost/setup-box.sh
ServerTokens Prod
ServerSignature Off
TraceEnable Off

# The checkout lives under $APP_PARENT. AllowOverride All: the repo's own .htaccess files carry
# load-bearing rules (Games/ storage is 404'd there, asset versioning) and the docroot .htaccess
# (harden-htaccess.sh) does the HTTPS + per-domain redirects.
<Directory "$APP_PARENT">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

<IfModule mpm_prefork_module>
    StartServers             2
    MinSpareServers          2
    MaxSpareServers          6
    MaxRequestWorkers        $workers
    MaxConnectionsPerChild   2000
</IfModule>

KeepAlive On
KeepAliveTimeout 3
EOF
  a2enconf -q tcgengine >/dev/null

  # PHP: one override file per SAPI, so distro php.ini updates never conflict with ours.
  local ini_common
  ini_common="$(cat <<EOF
; Managed by newhost/setup-box.sh
date.timezone = UTC
expose_php = Off
upload_max_filesize = 64M
post_max_size = 66M
max_execution_time = 120
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 1
opcache.revalidate_freq = 2
apc.enabled = 1
apc.shm_size = $APCU_SHM
; With ttl=0 a FULL APCu wipes EVERY entry, locking every live game (seat keys live here).
; A ttl lets it evict only entries idle that long instead.
apc.ttl = 7200
EOF
)"
  printf '%s\ndisplay_errors = Off\nlog_errors = On\nmemory_limit = %s\n' "$ini_common" "$PHP_MEMORY_LIMIT" \
    | write_if_changed "/etc/php/$V/apache2/conf.d/99-tcgengine.ini" 644 || true
  # CLI: the generators need unlimited memory; APCu on so CLI code paths behave like the web.
  printf '%s\nmemory_limit = -1\napc.enable_cli = 1\n' "$ini_common" \
    | write_if_changed "/etc/php/$V/cli/conf.d/99-tcgengine.ini" 644 || true

  # MariaDB: sized for a small box, local only.
  if write_if_changed /etc/mysql/mariadb.conf.d/90-tcgengine.cnf 644 <<EOF
# Managed by newhost/setup-box.sh
[mysqld]
bind-address            = 127.0.0.1
innodb_buffer_pool_size = $pool
performance_schema      = OFF
max_connections         = 100
character-set-server    = utf8mb4
collation-server        = utf8mb4_unicode_ci
EOF
  then systemctl restart mariadb; ok "MariaDB tuned (buffer pool $pool) and restarted"; else ok "MariaDB config unchanged"; fi
  systemctl enable --now mariadb >/dev/null 2>&1 || true
  # The usual mysql_secure_installation steps that matter (root already uses unix_socket auth).
  mysql_root -e "DELETE FROM mysql.global_priv WHERE User=''; DROP DATABASE IF EXISTS test;
                 DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%'; FLUSH PRIVILEGES;" 2>/dev/null \
    || mysql_root -e "DELETE FROM mysql.user WHERE User=''; DROP DATABASE IF EXISTS test; FLUSH PRIVILEGES;"
  ok "MariaDB secured (no anonymous users, no test db)"

  apache2ctl -t 2>/dev/null || { apache2ctl -t; die "apache config test failed"; }
  systemctl enable apache2 >/dev/null 2>&1 || true
  systemctl restart apache2
  ok "Apache up (prefork, MaxRequestWorkers $workers, APCu $APCU_SHM)"
}

# =============================================================================
# Phase: app
# =============================================================================
phase_app() {
  phase "app (code, Composer deps, secrets)"
  command -v git >/dev/null || die "git missing (run the system phase)."
  mkdir -p "$APP_PARENT"
  git config --system --get-all safe.directory 2>/dev/null | grep -Fx "$APP_ROOT" >/dev/null \
    || git config --system --add safe.directory "$APP_ROOT"
  if [ -d "$APP_ROOT/.git" ]; then
    log "updating $APP_ROOT"
    if [ -n "$(git -C "$APP_ROOT" status --porcelain --untracked-files=no)" ]; then
      warn "$APP_ROOT has local changes to tracked files — NOT pulling (resolve by hand):"
      git -C "$APP_ROOT" status --short --untracked-files=no | head -20 | sed 's/^/      /'
    else
      git -C "$APP_ROOT" fetch --quiet origin "$REPO_BRANCH"
      git -C "$APP_ROOT" checkout --quiet "$REPO_BRANCH"
      git -C "$APP_ROOT" merge --ff-only --quiet "origin/$REPO_BRANCH" \
        && ok "at $(git -C "$APP_ROOT" log -1 --format='%h %s')" \
        || warn "pull is not a fast-forward — left as it is"
    fi
  else
    log "cloning $REPO_URL ($REPO_BRANCH) -> $APP_ROOT"
    git clone --branch "$REPO_BRANCH" "$REPO_URL" "$APP_ROOT"
    ok "cloned $(git -C "$APP_ROOT" log -1 --format='%h %s')"
  fi

  # Distro "It works!" page: never leave it on a production docroot.
  if [ -f "$APP_PARENT/index.html" ] && grep -q 'Default Page' "$APP_PARENT/index.html"; then
    rm -f "$APP_PARENT/index.html"; ok "removed the distro default index.html"
  fi

  # Secret files: install from SECRETS_DIR only when ABSENT on the box. Never overwritten.
  local rel src
  for rel in APIKeys/APIKeys.php SWUDeck/SimulationConfig.local.php; do
    if [ -f "$APP_ROOT/$rel" ]; then
      ok "$rel present (left untouched)"
    elif [ -n "$SECRETS_DIR" ] && src="$SECRETS_DIR/$(basename "$rel")" && [ -f "$src" ]; then
      install -m 640 -o "$WEB_USER" -g "$WEB_USER" "$src" "$APP_ROOT/$rel"
      ok "$rel installed from SECRETS_DIR"
    else
      warn "$rel missing — copy it into SECRETS_DIR and re-run --only app (features that need it will fail)"
    fi
  done

  log "Composer deps"
  COMPOSER_ALLOW_SUPERUSER=1 APP_ROOT="$APP_ROOT" PHP_BIN="$(command -v php)" "$SCRIPT_DIR/install-php-deps.sh" \
    | sed 's/^/    /'

  # The web user writes Games/, GeneratedCode/, images. Generated files owned by anyone else
  # make the generators "hang" on a failed fopen (the chown trap from the XAMPP boxes).
  chown -R "$WEB_USER:$WEB_USER" "$APP_ROOT"
  ok "ownership -> $WEB_USER"
}

# =============================================================================
# Phase: db
# =============================================================================
# abandon_load <db> <what> — a load into a database that was EMPTY when this run found it failed
# part-way. Drop it: a half-loaded schema would read as "has tables, leave untouched" on the next
# run and never be fixed. Only ever called for a db verified empty above, so no data is lost.
abandon_load() {
  mysql_root -e "DROP DATABASE IF EXISTS \`$1\`;" || true
  die "loading '$1' failed ($2) — the partial database was dropped (it was empty before this run); fix the cause and re-run --only db"
}
phase_db() {
  phase "db"
  systemctl is-active --quiet mariadb || die "MariaDB is not running (run the stack phase)."
  local db n dump
  for db in "${UNIQUE_DBS[@]}"; do
    mysql_root -e "CREATE DATABASE IF NOT EXISTS \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    n="$(mysql_root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$db';")"
    if [ "$n" -gt 0 ]; then
      ok "'$db' has $n tables — left untouched"
      [ -n "$RESTORE_DIR" ] && { [ -f "$RESTORE_DIR/$db.sql" ] || [ -f "$RESTORE_DIR/$db.sql.gz" ]; } \
        && warn "a dump for '$db' is in RESTORE_DIR but '$db' is not empty — NOT restored over live data"
      continue
    fi
    dump=""
    if [ -n "$RESTORE_DIR" ]; then
      [ -f "$RESTORE_DIR/$db.sql.gz" ] && dump="$RESTORE_DIR/$db.sql.gz"
      [ -z "$dump" ] && [ -f "$RESTORE_DIR/$db.sql" ] && dump="$RESTORE_DIR/$db.sql"
    fi
    if [ -n "$dump" ]; then
      # A dump made with --databases carries its own `USE <name>`. If that name is not $db (the old
      # box's SWUStats db may be 'swuonline'), the import would land in the WRONG database.
      local used
      used="$( { case "$dump" in *.gz) gunzip -c "$dump" ;; *) cat "$dump" ;; esac; } 2>/dev/null \
               | grep -m1 -E '^USE `[^`]+`' | sed -E 's/^USE `([^`]+)`.*/\1/' || true)"
      if [ -n "$used" ] && [ "$used" != "$db" ]; then
        die "$dump switches to database '$used', not '$db'. Rename the site's db to '$used' in SITES, or strip the CREATE DATABASE/USE lines from the dump."
      fi
      log "restoring '$db' from $dump"
      if ! { case "$dump" in *.gz) gunzip -c "$dump" | mysql_root "$db" ;; *) mysql_root "$db" < "$dump" ;; esac; }; then
        abandon_load "$db" "restore from $dump"
      fi
      ok "'$db' restored ($(mysql_root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$db';") tables)"
      warn "'$db' came from a dump: apply any Database/migrations/*.sql newer than that box (see Database/migrations/README.md)"
    else
      log "loading the fresh schema into '$db'"
      # Same bootstrap files, same order, as docker-compose-files/*.yml. database.sql is complete:
      # migrations are for upgrading EXISTING databases only.
      local f
      for f in Database/database.sql Database/card_abilities_schema.sql Database/card_code_service_schema.sql; do
        [ -f "$APP_ROOT/$f" ] || die "$APP_ROOT/$f missing (run the app phase)."
        mysql_root "$db" < "$APP_ROOT/$f" || abandon_load "$db" "$f"
      done
      ok "'$db' schema loaded ($(mysql_root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$db';") tables)"
    fi
  done

  # App user: least privilege, only its own databases. ALTER keeps the password in sync with box.conf.
  mysql_root -e "CREATE USER IF NOT EXISTS '$DB_APP_USER'@'localhost' IDENTIFIED BY '$DB_APP_PASS';
                 ALTER USER '$DB_APP_USER'@'localhost' IDENTIFIED BY '$DB_APP_PASS';"
  for db in "${UNIQUE_DBS[@]}"; do
    mysql_root -e "GRANT ALL PRIVILEGES ON \`$db\`.* TO '$DB_APP_USER'@'localhost';"
  done
  mysql_root -e "FLUSH PRIVILEGES;"
  ok "user '$DB_APP_USER'@localhost granted on: ${UNIQUE_DBS[*]}"
}

# =============================================================================
# Phase: sites
# =============================================================================
phase_sites() {
  phase "sites (vhosts, TLS, .htaccess)"
  [ -d "$APP_ROOT" ] || die "$APP_ROOT missing (run the app phase)."
  local registry="$APP_ROOT/Database/SiteRegistry.php" i

  # TLS for the Cloudflare -> origin hop.
  local cert key
  if [ -n "$ORIGIN_CERT" ]; then
    install -d -m 700 /etc/ssl/tcgengine
    install -m 644 "$ORIGIN_CERT" /etc/ssl/tcgengine/origin.pem
    install -m 600 "$ORIGIN_KEY"  /etc/ssl/tcgengine/origin.key
    cert=/etc/ssl/tcgengine/origin.pem; key=/etc/ssl/tcgengine/origin.key
    ok "TLS: Cloudflare origin certificate installed (use SSL mode Full (strict))"
  else
    [ -f /etc/ssl/certs/ssl-cert-snakeoil.pem ] || make-ssl-cert generate-default-snakeoil --force-overwrite
    cert=/etc/ssl/certs/ssl-cert-snakeoil.pem; key=/etc/ssl/private/ssl-cert-snakeoil.key
    warn "TLS: self-signed cert — Cloudflare SSL mode must be 'Full' (not strict). Set ORIGIN_CERT/ORIGIN_KEY to fix."
  fi

  # Catch-all FIRST: an unmatched Host is denied before PHP runs, so it can never reach a database.
  write_if_changed /etc/apache2/sites-available/000-catchall.conf 644 <<EOF || true
# Managed by newhost/setup-box.sh — any Host that matches no site is denied before PHP runs.
# Its traffic (scanners hitting the bare IP) goes to access.log, which fail2ban watches.
<VirtualHost *:80>
    ServerName unmatched-host.invalid
    DocumentRoot "$APP_PARENT"
    CustomLog \${APACHE_LOG_DIR}/access.log combined
    <Location />
        Require all denied
    </Location>
</VirtualHost>
<VirtualHost *:443>
    ServerName unmatched-host.invalid
    DocumentRoot "$APP_PARENT"
    CustomLog \${APACHE_LOG_DIR}/access.log combined
    SSLEngine on
    SSLCertificateFile    "$cert"
    SSLCertificateKeyFile "$key"
    <Location />
        Require all denied
    </Location>
</VirtualHost>
EOF
  a2ensite -q 000-catchall >/dev/null

  local managed=()
  for i in "${!SITE_DOMAINS[@]}"; do
    local d="${SITE_DOMAINS[$i]}" r="${SITE_ROOTS[$i]}" db="${SITE_DBS[$i]}"
    local name; name="100-$(echo "$r" | tr '[:upper:]' '[:lower:]')-$(echo "$d" | tr '.' '_')"
    managed+=("$name")
    [ -d "$APP_ROOT/$r" ] || warn "$r is not a folder in the repo — check SITES"
    grep -Eq "'$r'[[:space:]]*=>[[:space:]]*\[[^]]*'db'[[:space:]]*=>[[:space:]]*'$db'" "$registry" 2>/dev/null \
      || grep -Eq "'$r'.*'aliases'[^]]*'$db'" "$registry" 2>/dev/null \
      || warn "SiteRegistry.php does not map $r -> '$db': ActiveSite.php will THROW on $d (site-wide 500)"

    # Extra per-site SetEnv from SECRETS_DIR/<db>.env (KEY=VALUE lines).
    local extra=""
    if [ -n "$SECRETS_DIR" ] && [ -f "$SECRETS_DIR/$db.env" ]; then
      local k v line
      while IFS= read -r line || [ -n "$line" ]; do
        case "$line" in ''|\#*) continue ;; esac
        k="${line%%=*}"; v="${line#*=}"
        echo "$k" | grep -Eq '^[A-Z][A-Z0-9_]*$' || { warn "$db.env: skipped bad key '$k'"; continue; }
        printf '%s' "$v" | grep -q '[[:space:]"\\]' && { warn "$db.env: skipped $k (whitespace/quote/backslash in value)"; continue; }
        case "$k" in DEVENV) warn "$db.env: refusing DEVENV (it flips dev gates on the live site)"; continue ;; esac
        extra+="    SetEnv $k $v"$'\n'
      done < "$SECRETS_DIR/$db.env"
    fi

    local body=""
    for port in 80 443; do
      body+="<VirtualHost *:$port>"$'\n'
      body+="    ServerName  $d"$'\n'
      body+="    ServerAlias www.$d"$'\n'
      body+="    DocumentRoot \"$APP_PARENT\""$'\n'
      body+="    SetEnv MYSQL_DATABASE_NAME    $db"$'\n'
      body+="    SetEnv MYSQL_SERVER_NAME      localhost"$'\n'
      body+="    SetEnv MYSQL_SERVER_USER_NAME $DB_APP_USER"$'\n'
      body+="    SetEnv MYSQL_ROOT_PASSWORD    $DB_APP_PASS"$'\n'
      body+="    SetEnv REDIS_HOST             127.0.0.1"$'\n'
      body+="    SetEnv REDIS_PORT             6379"$'\n'
      body+="$extra"
      if [ "$port" = 443 ]; then
        body+="    SSLEngine on"$'\n'
        body+="    SSLCertificateFile    \"$cert\""$'\n'
        body+="    SSLCertificateKeyFile \"$key\""$'\n'
      fi
      body+="    ErrorLog  \${APACHE_LOG_DIR}/${db}-error.log"$'\n'
      body+="    CustomLog \${APACHE_LOG_DIR}/${db}-access.log combined"$'\n'
      body+="</VirtualHost>"$'\n'
    done
    printf '# Managed by newhost/setup-box.sh — %s -> %s (db %s). Carries the DB password: mode 600.\n%s' \
      "$d" "$r" "$db" "$body" | write_if_changed "/etc/apache2/sites-available/$name.conf" 600 || true
    a2ensite -q "$name" >/dev/null
    ok "vhost $d (+www) -> $r / $db"
  done

  # Disable managed vhosts that are no longer in SITES.
  local f base
  for f in /etc/apache2/sites-enabled/100-*.conf; do
    [ -e "$f" ] || continue
    base="$(basename "$f" .conf)"
    printf '%s\n' "${managed[@]}" | grep -Fxq "$base" && continue
    grep -q 'Managed by newhost/setup-box.sh' "$f" || continue
    a2dissite -q "$base" >/dev/null && warn "disabled $base (no longer in SITES)"
  done

  # Docroot .htaccess: HTTPS, per-domain root redirects, no listings (owned by harden-htaccess.sh).
  HTDOCS="$APP_PARENT" "$SCRIPT_DIR/harden-htaccess.sh" --yes | grep -v 'lampp' | sed 's/^/    /'

  if ! apache2ctl -t 2>/dev/null; then
    apache2ctl -t || true
    die "apache config test failed — not reloading (the running config is unchanged)."
  fi
  systemctl reload apache2
  ok "Apache reloaded"
}

# =============================================================================
# Phase: fail2ban
# =============================================================================
phase_fail2ban() {
  phase "fail2ban"
  # A jail whose logpath does not exist yet stops fail2ban from starting at all.
  for f in /var/log/apache2/access.log /var/log/apache2/error.log; do
    [ -f "$f" ] || install -m 640 -o root -g adm /dev/null "$f"
  done
  STACK=native "$SCRIPT_DIR/harden-fail2ban.sh" --yes
}

# =============================================================================
# Phase: generate
# =============================================================================
# gen_run <log name> <cmd...> — run as the web user from the app root. The generators can print an
# error and still exit 0 (e.g. "You must be logged in"), so the output is checked too.
gen_run() {
  local name="$1"; shift
  local out="$LOG_DIR/gen-$name-$TS.log"
  # The CLI has no Apache SetEnv, and Database/ConnectionManager.php then falls back to root with an
  # empty password — which MariaDB's socket auth refuses for www-data. Hand the app's credentials in.
  if ( cd "$APP_ROOT" && as_web env MYSQL_SERVER_NAME=localhost MYSQL_SERVER_USER_NAME="$DB_APP_USER" \
         MYSQL_ROOT_PASSWORD="$DB_APP_PASS" ${GEN_DB:+MYSQL_DATABASE_NAME="$GEN_DB"} "$@" ) > "$out" 2>&1 \
     && ! grep -qiE 'must be logged in|Fatal error|Uncaught' "$out"; then
    ok "$name  ($(wc -l < "$out" | tr -d ' ') lines of output, log $out)"
  else
    tail -15 "$out" | sed 's/^/      /'
    warn "$name FAILED — full log: $out"
  fi
}
# root_db <Root> — the database a root uses: from SITES, else Database/SiteRegistry.php, else empty
# (the app then resolves it itself; the app user is only granted the SITES databases).
root_db() {
  local i
  for i in "${!SITE_ROOTS[@]}"; do [ "${SITE_ROOTS[$i]}" = "$1" ] && { echo "${SITE_DBS[$i]}"; return; }; done
  grep -E "^[[:space:]]*'$1'[[:space:]]*=>" "$APP_ROOT/Database/SiteRegistry.php" 2>/dev/null \
    | sed -nE "s/.*'db'[[:space:]]*=>[[:space:]]*'([A-Za-z0-9_]+)'.*/\1/p" | head -1
}
phase_generate() {
  phase "generate (sites, game code, card data)"
  [ -d "$APP_ROOT" ] || die "$APP_ROOT missing (run the app phase)."
  # DEVENV=true goes on each CLI command ONLY: the generators' login check no-ops under it.
  # Never put it in Apache's environment, where it also flips dev-only gates.
  GEN_DB="${SITE_DBS[0]}" gen_run sites env -C "$APP_ROOT/SharedUI/Render" php GenerateSites.php all
  local r
  for r in "${GAME_ROOTS[@]}"; do
    GEN_DB="$(root_db "$r")" gen_run "game-$r" env DEVENV=true php -d memory_limit=-1 zzGameCodeGenerator.php "rootName=$r"
  done
  for r in "${CARD_ROOTS[@]}"; do
    local args=("rootName=$r")
    # No cache yet (fresh box): fetch the card data from the API this once.
    [ -f "$APP_ROOT/$r/GeneratedCode/cardArrayCache.json" ] || args+=("withPreview=1")
    GEN_DB="$(root_db "$r")" gen_run "cards-$r" env DEVENV=true php -d memory_limit=-1 zzCardCodeGenerator.php "${args[@]}"
  done
  if printf '%s\n' "${CARD_ROOTS[@]}" | grep -Fxq SWUSim && [ -f "$APP_ROOT/SWUSim/DevTools/sync-preview-images.php" ]; then
    GEN_DB="$(root_db SWUSim)" gen_run "previews-SWUSim" php SWUSim/DevTools/sync-preview-images.php --quiet
  fi
  # Opcode cache may hold pre-generation bytecode; a reload clears it (APCu too — fine on a new box).
  systemctl reload apache2
}

# =============================================================================
# Phase: backups
# =============================================================================
phase_backups() {
  phase "backups"
  install -d -m 700 /etc/tcgengine "$BACKUP_DIR"
  write_if_changed /etc/tcgengine/backup.conf 600 <<EOF || true
# Managed by newhost/setup-box.sh — read by newhost/backup-db.sh
BACKUP_DBS="${UNIQUE_DBS[*]}"
BACKUP_DIR="$BACKUP_DIR"
BACKUP_KEEP_DAYS=$BACKUP_KEEP_DAYS
BACKUP_RCLONE_REMOTE="$BACKUP_RCLONE_REMOTE"
APP_ROOT="$APP_ROOT"
EOF
  install -m 750 "$SCRIPT_DIR/backup-db.sh" /usr/local/sbin/tcgengine-backup
  systemctl enable --now cron >/dev/null 2>&1 || warn "could not start cron — the nightly backup will not run"
  local hh="${BACKUP_TIME%%:*}" mm="${BACKUP_TIME##*:}"
  write_if_changed /etc/cron.d/tcgengine-backup 644 <<EOF || true
# Managed by newhost/setup-box.sh — nightly database + secrets backup (UTC)
CRON_TZ=UTC
$((10#$mm)) $((10#$hh)) * * * root /usr/local/sbin/tcgengine-backup >> /var/log/tcgengine-backup.log 2>&1
EOF
  write_if_changed /etc/logrotate.d/tcgengine-backup 644 <<'EOF' || true
/var/log/tcgengine-backup.log {
    weekly
    rotate 8
    compress
    missingok
    notifempty
}
EOF
  ok "nightly backup at $BACKUP_TIME UTC -> $BACKUP_DIR (keep $BACKUP_KEEP_DAYS days)"
  if [ -n "$BACKUP_RCLONE_REMOTE" ]; then
    command -v rclone >/dev/null || apt_install rclone
    rclone listremotes 2>/dev/null | grep -Fx "${BACKUP_RCLONE_REMOTE%%:*}:" >/dev/null \
      || warn "rclone remote '${BACKUP_RCLONE_REMOTE%%:*}' is not configured yet — run: rclone config"
  else
    warn "BACKUP_RCLONE_REMOTE is empty — backups stay on this box only"
  fi
  log "running one backup now"
  /usr/local/sbin/tcgengine-backup && ok "backup ran" || warn "first backup reported a failure (see above)"
}

# =============================================================================
# Phase: verify
# =============================================================================
PASSES=0; FAILS=0
check() {   # check "<label>" <command...>
  local label="$1"; shift
  if "$@" >/dev/null 2>&1; then printf '  \033[1;32mPASS\033[0m %s\n' "$label"; PASSES=$((PASSES+1))
  else printf '  \033[1;31mFAIL\033[0m %s\n' "$label"; FAILS=$((FAILS+1)); fi
}
http_code() {  # http_code <host> <path> [scheme] — through the local Apache, real SNI/Host
  local host="$1" path="$2" scheme="${3:-https}" port=443
  [ "$scheme" = http ] && port=80
  curl -sk -o /dev/null -w '%{http_code}' --max-time 20 --resolve "$host:$port:127.0.0.1" "$scheme://$host$path"
}
code_is()     { [[ "$(http_code "$2" "$3" "${4:-https}")" =~ ^($1)$ ]]; }   # code_is <regex> <host> <path> [scheme]
code_is_not() { ! code_is "$@"; }
php_is()      { php -r "exit(PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION==='$1'?0:1);"; }
has_ext()     { php -m | grep -ix "$1" >/dev/null; }
catchall_ok() { [ "$(curl -sk -o /dev/null -w '%{http_code}' --max-time 20 -H 'Host: nope.invalid' https://127.0.0.1/)" = 403 ]; }
db_ok()       { MYSQL_PWD="$DB_APP_PASS" mysql -u "$DB_APP_USER" -e "SELECT 1" "$1"; }
backup_ok()   { compgen -G "$BACKUP_DIR/20*/SHA256SUMS" >/dev/null; }
phase_verify() {
  phase "verify"
  local V="$PHP_VERSION" i
  check "php $V is the CLI php"               php_is "$V"
  for ext in mysqli pdo_mysql gd imagick apcu zip bz2 imap mbstring curl shmop; do
    check "php extension $ext"                has_ext "$ext"
  done
  check "OPcache loaded"                      has_ext "Zend OPcache"
  check "Imagick can write WebP"              php -r 'exit(in_array("WEBP",(new Imagick())->queryFormats())?0:1);'
  check "GD has WebP"                         php -r 'exit(function_exists("imagewebp")?0:1);'
  check "apache config valid"                 apache2ctl -t
  for s in apache2 mariadb fail2ban cron; do check "service $s active" systemctl is-active --quiet "$s"; done
  check "ufw active"                          bash -c "ufw status | grep -q 'Status: active'"
  check "catch-all denies unknown hosts (403)" catchall_ok
  for i in "${!SITE_DOMAINS[@]}"; do
    local d="${SITE_DOMAINS[$i]}" r="${SITE_ROOTS[$i]}" db="${SITE_DBS[$i]}"
    check "$d: db '$db' reachable as $DB_APP_USER" db_ok "$db"
    check "$d: MainMenu.php renders (200)"   code_is 200 "$d" /TCGEngine/SharedUI/MainMenu.php
    # At the site ROOT: under /TCGEngine/ the repo's own .htaccess owns rewriting (per-directory rules
    # do not inherit), exactly as on the XAMPP boxes — Cloudflare's "Always Use HTTPS" covers those.
    check "$d: http redirects to https"     code_is '301|302' "$d" / http
    check "$d: Games/ storage not served"   code_is_not 200 "$d" "/TCGEngine/$r/Games/GameIDCounter.txt"
    check "$d: generated card data present" test -f "$APP_ROOT/$r/GeneratedCode/GeneratedCardDictionaries.php"
  done
  check "APIKeys/APIKeys.php present"         test -f "$APP_ROOT/APIKeys/APIKeys.php"
  check "a backup exists"                     backup_ok
  check "unattended upgrades include PHP"     grep -qE 'ondrej-php|sury' /etc/apt/apt.conf.d/52tcgengine-unattended
  echo
  printf '  %s passed, %s failed\n' "$PASSES" "$FAILS"
}

# =============================================================================
# Run
# =============================================================================
for p in "${RUN_PHASES[@]}"; do
  "phase_$p"
  date -u +%FT%TZ > "$STATE_DIR/phase-$p.done"
done

echo
log "done: ${RUN_PHASES[*]}"
if [ "${#WARNINGS[@]}" -gt 0 ]; then
  printf '\n\033[1;33mWarnings to review:\033[0m\n'
  printf '  - %s\n' "${WARNINGS[@]}"
fi
cat <<NEXT

Next steps:
  1. Test a NEW ssh login (key) before closing this session.
  2. Cloudflare: point each site's DNS record at this box ($(curl -fsS --max-time 5 https://api.ipify.org 2>/dev/null || echo '<public ip>')),
     SSL/TLS mode $( [ -n "$ORIGIN_CERT" ] && echo '"Full (strict)"' || echo '"Full"' ).
  3. Load a site in the browser, then check the real client IP reaches the log (must be YOUR address,
     not a Cloudflare 104.x/172.6x edge):   tail -3 /var/log/apache2/${SITE_DBS[0]}-access.log
  4. Snapshot the box in the provider's panel: that is the golden image.
Transcript: $LOG_DIR/setup-$TS.log
NEXT
[ "$FAILS" -eq 0 ] || exit 1
