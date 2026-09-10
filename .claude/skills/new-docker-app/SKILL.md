---
name: new-docker-app
description: Use when adding a new TCGEngine root application to the docker-compose environment (e.g. a new game). Walks through duplicating the web/mysql/phpmyadmin/redis services, assigning unique ports and env vars, and validating the new app comes up.
---

# New Docker App

This skill provides instructions on how to add a new TCGEngine root application to the local
Docker environment.

Each app owns two files under `docker-compose-files/` and is its own compose project, so
adding one never touches the existing stacks. There is no root `docker-compose.yml`.

## Workflow to add a new app (e.g., `NewGame`)

1. **Create `docker-compose-files/newgame.yml`** — copy an existing app's file (e.g.
   `swusim.yml`) and change only these:
   - `name: otmtcge-newgame` (the compose project name; this is what isolates the stack).
   - **Ports**: assign unique host ports — web `3600:80`, phpmyadmin `5106:80`,
     redis `6487:6379`. Check `./docker-start.sh --list` for what is already taken.
   - **Environment**: `MYSQL_DATABASE` and `MYSQL_DATABASE_NAME` set to the new database
     name (`MYSQL_DATABASE_NAME` is what `SharedUI/ActiveSite.php` maps to the site).
   - **Redis container name**: `container_name: newgame_app_redis`.
   - Leave the service names as `web-server`, `mysql-server`, `phpmyadmin`, `redis`, and
     leave `MYSQL_SERVER_NAME: "mysql-server"` / `REDIS_HOST: "redis"` alone — they are
     in-network hostnames, and each project has its own network, so they never collide.
     Do NOT prefix service names with the app name; the project name already does that,
     giving `otmtcge-newgame-web-server-1`.
   - Keep the volume as `mysql-data` (it becomes `otmtcge-newgame_mysql-data`).
   - Paths are relative to `docker-compose-files/`, so the build context stays `..`.

2. **Create `docker-compose-files/newgame.dev.yml`** — copy another app's `.dev.yml`
   verbatim; it only overrides the `web-server` service (xdebug + `build: target: dev`).
   Add `DEVENV: "true"` if the app should bypass local auth gates.

3. **No script changes are needed.** `./docker-start.sh` discovers apps by globbing
   `docker-compose-files/*.yml`, so the new app is immediately available.

4. **Validation**:
   - `./docker-start.sh --list` — the new app should appear with its ports.
   - `docker compose -f docker-compose-files/newgame.yml -f docker-compose-files/newgame.dev.yml config`
     — must parse cleanly.
   - `./docker-start.sh newgame` — then hit the printed URLs.
