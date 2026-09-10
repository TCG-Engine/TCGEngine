#!/usr/bin/env bash
#
# Selectively bring up one (or more) of the per-game docker stacks.
#
#   ./docker-start.sh swusim
#   ./docker-start.sh azukisim
#   ./docker-start.sh swudeck
#   ./docker-start.sh swusim swudeck        # several at once
#   ./docker-start.sh all                   # everything
#
# Each game lives in its own file under docker-compose-files/ and its own compose
# PROJECT (otmtcge-<app>), so starting one never touches, rebuilds, or reports
# orphans for the others. This replaces the single root docker-compose.yml, where
# disabling a game meant commenting out a service block -- and commenting the
# service KEY while leaving its body behind silently folded those keys into the
# previous service, producing a duplicate-key YAML error.
#
# Layout:
#   docker-compose-files/<app>.yml       base stack (web, mysql, phpmyadmin, redis)
#   docker-compose-files/<app>.dev.yml   xdebug overlay, applied unless --prod
#
# The dev overlay carries MORE than xdebug (DEVENV=true, verbose error reporting), so
# --prod is the wrong way to get a fast dev box: it drops DEVENV too, and every tool
# that shells out to a generator then fails its auth check. --no-xdebug keeps the
# overlay and just sets XDEBUG_MODE=off in the container (Xdebug 3 gives that env var
# precedence over xdebug.ini, which is why editing the ini alone does not stick).
#
set -euo pipefail

REPO_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
COMPOSE_DIR="$REPO_ROOT/docker-compose-files"

# Docker Desktop's user-local CLI install is not on PATH for every shell.
if ! command -v docker >/dev/null 2>&1 && [ -x "$HOME/.docker/bin/docker" ]; then
    PATH="$PATH:$HOME/.docker/bin"
fi

# Passed through to `user:` in the compose files. Empty = image default (root),
# which is what the stacks have always run as; the entrypoint chowns to www-data.
export DOCKER_USER="${DOCKER_USER:-}"

die() { printf 'error: %s\n' "$1" >&2; exit 1; }

known_apps() {
    local f
    for f in "$COMPOSE_DIR"/*.yml; do
        [ -e "$f" ] || continue
        case "$f" in *.dev.yml) continue ;; esac
        basename "$f" .yml
    done
}

usage() {
    cat <<EOF
Usage: ./docker-start.sh [options] <app>... | all

Apps:
$(known_apps | sed 's/^/  /')

Options:
  --prod           Skip the .dev.yml overlay entirely (mirrors the live config:
                   no xdebug, but also NO DEVENV).
  --no-xdebug      Keep the dev overlay (DEVENV etc.) but turn xdebug off.
                   Changing it later needs --recreate to take effect.
                   ⚠ Switching to/from --prod also needs --build: both targets share
                   one image tag, so a prod-built image (no xdebug extension) is
                   otherwise reused under the dev overlay.
  --build          Force an image rebuild before starting.
  --recreate       Force-recreate containers even if config is unchanged.
  --down           Stop and remove the app's containers (keeps its volumes).
  --down-volumes   Stop and remove containers AND volumes (DESTROYS the database).
  --stop           Stop containers without removing them.
  --restart        Restart containers.
  --ps             Show status.
  --logs           Follow logs (Ctrl-C to detach).
  --list           List known apps with their ports, then exit.
  -h, --help       This message.
EOF
}

# --- parse args -------------------------------------------------------------
APPS=()
ACTION="up"
USE_DEV=1
BUILD=0
RECREATE=0
# Read by the .dev.yml overlays as XDEBUG_MODE: "${DEV_XDEBUG_MODE:-debug}". A dedicated
# name, so an XDEBUG_MODE exported in your shell for something else can't leak in.
export DEV_XDEBUG_MODE="debug"

while [ $# -gt 0 ]; do
    case "$1" in
        --prod)         USE_DEV=0 ;;
        --no-xdebug)    DEV_XDEBUG_MODE="off" ;;
        --build)        BUILD=1 ;;
        --recreate)     RECREATE=1 ;;
        --down)         ACTION="down" ;;
        --down-volumes) ACTION="down-volumes" ;;
        --stop)         ACTION="stop" ;;
        --restart)      ACTION="restart" ;;
        --ps)           ACTION="ps" ;;
        --logs)         ACTION="logs" ;;
        --list)         ACTION="list" ;;
        -h|--help)      usage; exit 0 ;;
        -*)             die "unknown option: $1 (try --help)" ;;
        all)            while IFS= read -r a; do APPS+=("$a"); done < <(known_apps) ;;
        *)              APPS+=("$1") ;;
    esac
    shift
done

[ -d "$COMPOSE_DIR" ] || die "missing $COMPOSE_DIR"

if [ "$ACTION" = "list" ]; then
    printf '%-18s %-9s %-12s %s\n' APP WEB PHPMYADMIN REDIS
    while IFS= read -r app; do
        # web-server's "<port>:80" comes first in the file, phpmyadmin's last.
        host_ports=$(grep -oE '"[0-9]+:80"' "$COMPOSE_DIR/$app.yml" | tr -d '"' | cut -d: -f1)
        web=$(printf '%s\n' "$host_ports" | head -1)
        pma=$(printf '%s\n' "$host_ports" | tail -1)
        rds=$(grep -oE '"[0-9]+:6379"' "$COMPOSE_DIR/$app.yml" | tr -d '"' | cut -d: -f1)
        printf '%-18s %-9s %-12s %s\n' "$app" "${web:-?}" "${pma:-?}" "${rds:-?}"
    done < <(known_apps)
    exit 0
fi

# macOS ships bash 3.2, where ${#APPS[@]} on an empty array trips `set -u`.
if [ -z "${APPS[*]:-}" ]; then
    usage
    die "no app specified"
fi

command -v docker >/dev/null 2>&1 || die "docker not found on PATH"
docker info >/dev/null 2>&1 || die "cannot reach the Docker daemon -- is Docker Desktop running?"

# --- act on each app --------------------------------------------------------
for app in "${APPS[@]}"; do
    base="$COMPOSE_DIR/$app.yml"
    if [ ! -f "$base" ]; then
        printf 'error: unknown app "%s". Known apps:\n' "$app" >&2
        known_apps | sed 's/^/  /' >&2
        exit 1
    fi

    files=(-f "$base")
    mode="dev"
    if [ "$USE_DEV" -eq 1 ] && [ -f "$COMPOSE_DIR/$app.dev.yml" ]; then
        files+=(-f "$COMPOSE_DIR/$app.dev.yml")
        [ "$DEV_XDEBUG_MODE" = "off" ] && mode="dev, xdebug off"
    else
        mode="prod"
    fi

    compose() { docker compose "${files[@]}" "$@"; }

    case "$ACTION" in
        up)
            up_args=(-d)
            [ "$BUILD" -eq 1 ] && up_args+=(--build)
            [ "$RECREATE" -eq 1 ] && up_args+=(--force-recreate)
            printf '\n==> starting %s (%s)\n' "$app" "$mode"
            compose up "${up_args[@]}"
            web=$(compose port web-server 80 2>/dev/null | sed 's/^0\.0\.0\.0/localhost/;s/^\[::\]/localhost/' || true)
            pma=$(compose port phpmyadmin 80 2>/dev/null | sed 's/^0\.0\.0\.0/localhost/;s/^\[::\]/localhost/' || true)
            [ -n "$web" ] && printf '    %-11s http://%s\n' "$app" "$web"
            [ -n "$pma" ] && printf '    %-11s http://%s  (root / secret)\n' phpmyadmin "$pma"
            ;;
        down)         printf '\n==> stopping + removing %s\n' "$app"; compose down ;;
        down-volumes)
            printf '\n==> removing %s INCLUDING its database volume\n' "$app"
            read -r -p "    This destroys the $app database. Type 'yes' to continue: " reply
            [ "$reply" = "yes" ] || die "aborted"
            compose down -v
            ;;
        stop)         printf '\n==> stopping %s\n' "$app";   compose stop ;;
        restart)      printf '\n==> restarting %s\n' "$app"; compose restart ;;
        ps)           printf '\n==> %s\n' "$app";            compose ps ;;
        logs)         printf '\n==> logs for %s\n' "$app";   compose logs -f ;;
    esac
done
