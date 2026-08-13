# newhost/ — XAMPP/LAMPP provisioning kit

Scripts for standing up apps on a **native XAMPP/LAMPP** Linux host (`/opt/lampp`),
the kind you get when you restore a LAMPP server from another box. The work has two
lifecycles, so it's two scripts.

> Target is native XAMPP (`/opt/lampp`), **not** the repo's `docker-compose` stack.
> Both scripts run as **root** and assume Debian/Ubuntu. Every file they change is
> backed up to `newhost-backups-<timestamp>/` first, and both are idempotent.

## Workflow

```
                         ┌─ once, then bake into image
  golden box ── harden-host.sh ──►  snapshot  ──►  clone per app
                                                     │
                                                     └─ provision-app.sh <app> <db>  (each clone)

  already-running box ── harden-fail2ban.sh ──► re-tune fail2ban in place (retrofit)
```

> **Retrofit note:** every box provisioned before 2026-08-12 is running the old fail2ban
> config that took the site down on 2026-08-11. Run `sudo ./harden-fail2ban.sh` on each
> one — it is idempotent, live-safe, and does not touch OPcache, phpMyAdmin, or the DB.

### 1. Harden the golden box (once)

```bash
sudo ./harden-host.sh
```

Host-wide, app-agnostic — persists in the snapshot so every future app inherits it:

- **OPcache** enabled in `php.ini`
- **phpMyAdmin removed** entirely (Alias commented + `/opt/lampp/phpmyadmin` deleted) so it can't be reached
- **fail2ban** installed + configured, by delegating to `harden-fail2ban.sh` (below)

Then snapshot the box.

Flags: `--skip-opcache`, `--skip-phpmyadmin`, `--skip-fail2ban`, `--yes`.

#### 1a. fail2ban + real client IP — `harden-fail2ban.sh` ⚠ retrofit every existing box

**Every box provisioned before 2026-08-12 needs this run.** It is the fix for the
Petranaki outage of 2026-08-11 03:08–03:10 UTC, where the site went dark behind a
Cloudflare 521 for ~20 minutes.

What went wrong, exactly: the old inline jail counted **every** HTTP request and banned at
**300 requests / 60s → 1 hour**. Apache had no `mod_remoteip`, so every log line carried a
**Cloudflare edge** address rather than a player's — meaning "one IP" was *all players routed
through that edge*. At peak, six edges crossed 5 req/s inside 107 seconds and iptables
dropped them, taking the origin off the internet for everyone behind those edges. Stock
`[recidive]` (5 bans/day → **1 week**) was one repeat away from turning that into a week.

```bash
sudo ./harden-fail2ban.sh
```

Idempotent, safe on a live box, backs up everything first. It:

1. **Restores the real client IP** — enables `mod_remoteip` with `RemoteIPHeader CF-Connecting-IP`
   and `RemoteIPTrustedProxy` limited to Cloudflare's ranges, then rewrites the `LogFormat`
   nicknames from `%h` to `%a` **in place in `httpd.conf`** (a nickname is resolved where the
   `CustomLog` is parsed, so an appended `Include` would come too late for httpd.conf's own
   `CustomLog`). Per-vhost `CustomLog … combined` lines inherit the fix for free.
   A request arriving *directly* at the origin can't spoof the header — its own address isn't a
   trusted proxy, so the header is ignored.
2. **Whitelists the CDN** — `ignoreip` = loopback + RFC1918 + Cloudflare, fetched live from
   `cloudflare.com/ips-v4`+`v6` with a pinned fallback and an `/etc/fail2ban/cloudflare-ips.local`
   cache. This is the safety net for any window where step 1 isn't in effect.
3. **Retunes the jail** — `1200 req/60s → 10 min ban` (was 300/60s → 1h). That's 20 req/s
   sustained from one *real* address, which a polling game client — even several players on one
   household/CGNAT address — is an order of magnitude under. The filter regex is unchanged on
   purpose: a game's traffic is nearly all dynamic PHP, so filtering to "suspicious" paths would
   exempt exactly what an attacker would flood. **Volume is the only honest signal; the threshold
   was the bug.**
4. **De-escalates `[recidive]`** — 10 bans/day → 1 day (was 5/day → 1 week).
5. **Watches per-vhost logs** — a box converted by `provision-vhost.sh` writes each site to
   `logs/<app>-access_log`, so the shared `access_log` alone can be nearly empty there.
6. **Releases held bans the new whitelist covers**, and leaves every other ban in place — a box
   that already ate this outage may still be holding CF edges for up to a week.

Flags: `--skip-apache` (jails only), `--skip-restart`, `--no-unban`, `--offline` (skip the live
fetch), `--yes`. Tuning via env: `BANTIME`, `FINDTIME`, `MAXRETRY`, `RECIDIVE_*`, `REAL_IP_HEADER`
(set to `X-Forwarded-For` for a non-Cloudflare CDN — everything else is CDN-agnostic).

**Caveat:** if `mod_remoteip.so` isn't in `/opt/lampp/modules/`, the script warns and skips step 1.
The whitelist still prevents an outage, but the jails then protect nothing — an attacker proxied
through Cloudflare is indistinguishable from a player. The load-bearing check after a run is
`tail -3 /opt/lampp/logs/access_log`: it must show **your** public address, not a `104.x` / `172.6x`
/ `172.7x` edge.

#### 1b. WebP support — `harden-webp.sh` (if the box converts images)

XAMPP 8.2.12's bundled GD has **no WebP** (`imagewebp()` undefined), so image conversion
fatals. The app converts via **Imagick** instead, which a fresh box lacks. Install it:

```bash
sudo ./harden-webp.sh
```

Repeatable, always-latest: installs ImageMagick (+ webp delegate) and a build toolchain,
then **builds the latest stable `imagick` from source against XAMPP's own PHP** (its bundled
`phpize`/`php-config`), so the `.so` is ABI-correct by construction — no hand-copied
`imagick.so`, no ABI-twin-box dependency. Enables `extension=imagick` and verifies Imagick
loads with WebP. Safe to re-run (rebuilds current each time). Flags: `--skip-apt`, `--yes`.
Override the source with `IMAGICK_URL=…`.

The app's asset code calls **Imagick directly** (`zzImageConverter.php`, `zzCropTester.php`,
`SWUSim/Mod/CosmeticsImage.php`), so no `imagewebp()` polyfill is needed. If an earlier run
of this script installed the old polyfill (`auto_prepend_file`), re-running retires it
automatically. (`SWUDeck/CreateImage.php` is JPEG-only and unaffected.)

#### 1c. Docroot routing + directory hardening — `harden-htaccess.sh`

Apache serves the repo at `htdocs/TCGEngine/` with directory listings ON, so hitting a
directory with no index file (e.g. `/TCGEngine/SharedUI/`) shows an "Index of …" of the
filesystem. This script is the **single source of truth** for `htdocs/.htaccess`: every run
**overwrites** the whole file from the `APP_DOMAINS` table declared at the top of the script.

```bash
sudo ./harden-htaccess.sh
```

Generates, in order:

1. **Force HTTPS.**
2. **Per-domain root redirects** — one rule per `APP_DOMAINS` entry (`<domain>|<target menu URL>`),
   sending `/` and `/index.php` to that app's main menu.
3. **`/TCGEngine` and `/TCGEngine/SharedUI/`** → the active site's `SharedUI/MainMenu.php`.
4. **`Options -Indexes`** — turns every directory listing into a 403.

Deterministic (same output every run) and backs up the existing `.htaccess` first. **Because it
overwrites, every live domain must be present in `APP_DOMAINS` or its redirect is dropped** — add
apps by editing that one table, then re-run. Apply with `sudo /opt/lampp/lampp reload`. Flags: `--yes`.

#### 1d. PHP Composer deps — `install-php-deps.sh`

`vendor/` is **gitignored**, so a fresh checkout has none — but `SWUDeck/CreateImage.php`
`require`s `vendor/autoload.php` and **fatals** without it (this is why deck-image copy currently
fails: the endpoint returns a PHP error, the browser gets a non-image blob, and the clipboard write
throws "Failed to copy image!"). This script installs the Composer binary if missing and runs
`composer install` in the app root to materialize `vendor/` (`tcpdf` today; `endroid/qr-code` once
the QR feature ships).

```bash
sudo ./install-php-deps.sh
```

Idempotent (safe to re-run; a no-op when deps are already current). Run it on the golden box before
snapshotting so every clone inherits `vendor/`, or per clone. Config via env: `APP_ROOT`
(default: repo root above `newhost/`), `COMPOSER_BIN` (default `/usr/local/bin/composer`),
`PHP_BIN`.

### 2. Provision each new app (per clone)

```bash
sudo ./provision-app.sh swusim swusim          # <app> <db>, defaults to swusim swusim
```

Per-app, re-runnable:

- **Env vars (default, always)** — writes `etc/extra/httpd-<app>-env.conf` with `SetEnv MYSQL_DATABASE_NAME <db>` (+ server/user/password/redis) and Includes it from `httpd.conf`. The app's `getenv()` in `Database/ConnectionManager.php` then resolves the right DB. **This is all a bare re-run does — the DB is never touched.**
- **DB reset (opt-in, `--reset-db` ONLY)** — DROPs the leftover `STALE_DB` (default `soulmastersdb`), then DROP/recreates `<db>` and loads the canonical schema (`SCHEMA_SQL`, default `../Database/database.sql`). **DESTRUCTIVE — wipes all data in `<db>`.** Requires `--reset-db` **and** a typed confirmation (unless `--yes`). Use only for a brand-new/empty DB.

Flags: `--skip-env`, `--reset-db` (destructive DB wipe+load), `--yes`.
Requirements/creds: **`DB_PASS` is required** (no passwordless DB). `DB_USER`, `MYSQL_HOST`, `REDIS_HOST`, `REDIS_PORT` env vars default to `root` / `localhost` / `127.0.0.1` / `6379`. `DB_NAME` defaults to the app name.
Preflight guards (fail before any change): DB connectivity, DB exists (unless `--reset-db`), and no *other* `httpd-*-env.conf` setting a different `MYSQL_DATABASE_NAME`.

### 2b. More than one site on a box — `provision-vhost.sh`

`provision-app.sh` sets `MYSQL_DATABASE_NAME` at **server level**, which is structurally why one box =
one DB. For a box that must serve several sims, use `provision-vhost.sh` instead — it writes the same
`SetEnv` lines inside a **name-based `<VirtualHost>`**, so the DB is chosen per request from the `Host`
header.

```bash
sudo DB_PASS='<real>' ./provision-vhost.sh swusim       --server-name swusim.example.com
sudo DB_PASS='<real>' ./provision-vhost.sh hellbreaksim --server-name hellbreak.example.com
# TLS:
sudo DB_PASS='<real>' ./provision-vhost.sh grandarchivesim --server-name ga.example.com \
     --ports "80 443" --ssl-cert /etc/letsencrypt/live/ga/fullchain.pem \
                      --ssl-key  /etc/letsencrypt/live/ga/privkey.pem
```

**No PHP changes are needed.** `Database/ConnectionManager.php` still calls `getenv()`, and
`SharedUI/ActiveSite.php` still resolves the rendered site *from* the DB name — so the "the site and the
connected DB can never disagree" guarantee survives. Every vhost may share ONE `DocumentRoot`: one
checkout, one deploy.

**Why hostnames and not URL paths.** The app contains ~450 hardcoded `/TCGEngine/...` absolute paths
(script/CSS refs, fetch targets). Under a path prefix like `/hellbreak/`, those would escape the prefix
and pick up the wrong site's env — intermittently, not loudly. Hostname separation also gives per-site
**session cookies** for free (browsers scope cookies by host), which is what stops a login on one sim
from resolving `$_SESSION['userid']` against a *different sim's* `users` table. That matters the moment
two sims on a box both have logins.

Behaviour: idempotent; **never touches the database** (the DB must already exist); writes
`etc/extra/httpd-vhost-100-<app>.conf` + a `000-default` catch-all; adds two ordered `Include` lines;
runs `httpd -t` and refuses to restart on a bad config; `chmod 600` on the confs (they carry `DB_PASS`).
Flags: `--server-name` (required), `--server-alias`, `--ports`, `--ssl-cert`, `--ssl-key`, `--skip-restart`.

**Migration.** Running it for an app currently provisioned the old way converts that app: its
`httpd-<app>-env.conf` is backed up, renamed `.retired`, and its `Include` removed. Convert *every* app
on the box — the script refuses to proceed while another app still sets `MYSQL_DATABASE_NAME` at server
level, because that value would silently serve a real database to any unmatched `Host`.

**The two scripts are mutually exclusive per box**, enforced both ways: `provision-app.sh` now refuses to
run when `httpd-vhost-1*.conf` files exist, and `provision-vhost.sh` refuses while a server-level env
conf is still live. Exactly one mechanism decides the DB.

**Catch-all ordering is load-bearing.** Apache serves the *first* vhost on a port to any request whose
`Host` matches none, so `httpd-vhost-000-default.conf` (which denies before PHP runs) is Included by its
own explicit line *before* the `httpd-vhost-1*.conf` glob. Don't reorder those two lines.

Known gap: `docker-compose.yml` stays one container per sim with its own `MYSQL_DATABASE_NAME`, so
**local dev does not exercise vhost routing, cookie scoping, or the catch-all** — first real test is on a
box. Verify with the `curl -H 'Host: ...'` checks the script prints.

### Bringing up a site — runbook + the traps

`ActiveSite.php` resolves the rendered site from `MYSQL_DATABASE_NAME`, and
`Database/ConnectionManager.php` connects to a **DB named exactly that** — so for AzukiSim the
env must be `MYSQL_DATABASE_NAME=azukisim` **and** a DB named `azukisim`. Mapping lives in
`SharedUI/ActiveSite.php` (`swudeck→SWUDeck`, `azukisim→AzukiSim`, `swusim→SWUSim`, `grandarchivesim→GrandArchiveSim`).

Per host, in order:

1. `sudo ./harden-host.sh` → `sudo ./install-php-deps.sh` → `sudo ./harden-htaccess.sh`.
2. **`sudo DB_PASS='<real>' ./provision-app.sh <app> <db>`** — writes the Apache `SetEnv` env conf
   (in `httpd.conf`, NOT `.htaccess`). Bare run is **env-only and never touches the DB** — safe to re-run.
   (Only for a brand-new empty DB do you add `--reset-db` to load the schema.)
3. Verify **before** loading the site (the connectivity preflight in step 2 now also does this):
   ```bash
   grep -rn "MYSQL_" /opt/lampp/etc/extra/httpd-*-env.conf      # exactly ONE, correct values
   /opt/lampp/bin/mysql -u root -p'<real>' -e "SELECT COUNT(*) FROM <db>.ownership;"
   ```
4. `sudo /opt/lampp/lampp restart`, then load the domain.

**Traps that will bite you (all hit in prod once):**

- **`DB_NAME` defaults to the app name** (`azukisim` → db `azukisim`). Pass a 2nd positional to override.
  (It used to hardcode `swusim`, which silently served the wrong site → "Petranaki Arena".)
- **`--reset-db` is DESTRUCTIVE** — it drops+recreates `<db>` and wipes all data. A bare run never
  touches the DB, so re-running on a live box is safe. Only pass `--reset-db` for a brand-new/empty DB.
- **`DB_PASS` is required** — the script fails fast without it. Pass the real password (and
  `DB_USER`/`MYSQL_HOST` if not `root`/`localhost`).
- **Env must NOT live in the docroot `.htaccess`.** `harden-htaccess.sh` overwrites that file, so any
  `SetEnv MYSQL_DATABASE_NAME` there is wiped on its next run → site-wide 500. `provision-app.sh` keeps
  env in `httpd.conf`, which `harden-htaccess.sh` never touches.
- **Only ONE `httpd-*-env.conf` should set `MYSQL_DATABASE_NAME`.** `provision-app.sh` only *appends*
  Includes, so a mistaken earlier run leaves a stale conf that can win. Delete the stale file **and** its
  `Include` line in `httpd.conf`.
- **GD**: `harden-host.sh` adds `extension=gd` when LAMPP's PHP lacks GD; if there's no `gd.so`, image
  generation (`SWUDeck/CreateImage.php`) fatals. Provide a real GD for LAMPP 8.2 (or comment the line if
  the box relies on Imagick). Not a MainMenu blocker, but breaks deck images.

## Verify

```bash
# OPcache on
/opt/lampp/bin/php -r 'var_dump(opcache_get_status()!==false);'
# phpMyAdmin gone (expect 404)
curl -s -o /dev/null -w '%{http_code}\n' http://localhost/phpmyadmin
# fail2ban up, and NOT holding a CDN edge
fail2ban-client status && fail2ban-client status xampp-dos
iptables -S | grep -E '104\.1[6-9]|104\.2[0-7]|172\.6[4-9]|172\.7[01]|162\.158' || echo "clean"
# real client IP is reaching the log (must be YOUR address, not a Cloudflare edge)
tail -3 /opt/lampp/logs/access_log
# WebP via Imagick
/opt/lampp/bin/php -r 'var_dump(class_exists("Imagick"));'
/opt/lampp/bin/php -r '$i=new Imagick(); var_dump(in_array("WEBP",$i->queryFormats()));'
# Composer deps present (vendor/autoload resolves; CreateImage.php won't fatal)
/opt/lampp/bin/php -r 'var_dump(file_exists(__DIR__."/../vendor/autoload.php"));'
# DB rebuilt fresh, soulmastersdb gone
/opt/lampp/bin/mysql -u root -e "SHOW TABLES FROM swusim;"
/opt/lampp/bin/mysql -u root -e "SHOW DATABASES LIKE 'soulmastersdb';"   # expect empty
```

## Notes / assumptions

- Apache serves PHP via **mod_php** (XAMPP default), which exposes `SetEnv` values to
  `getenv()`. If you switch to PHP-FPM, deliver env vars differently.
- `provision-app.sh` sets up the DB **fresh**: it drops `STALE_DB` (`soulmastersdb`),
  then drop-if-exists + create `<db>` and loads `SCHEMA_SQL`. `soulmastersdb` is removed,
  never repurposed. Idempotent — re-running rebuilds `<db>` from the schema.
- fail2ban uses the system package's Apache filters (`apache-auth`, `apache-badbots`,
  `apache-overflows`) plus the custom `xampp-dos` filter. **`harden-fail2ban.sh` owns
  `/etc/fail2ban/jail.local` and `filter.d/xampp-dos.conf` outright** and rewrites both from
  the script on every run (same contract as `harden-htaccess.sh` and the docroot `.htaccess`) —
  hand-edits there are lost. `harden-host.sh` only delegates; it holds no jail config of its own,
  deliberately, so a stale default in one file can't override the other.
