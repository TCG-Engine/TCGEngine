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
```

### 1. Harden the golden box (once)

```bash
sudo ./harden-host.sh
```

Host-wide, app-agnostic — persists in the snapshot so every future app inherits it:

- **OPcache** enabled in `php.ini`
- **phpMyAdmin removed** entirely (Alias commented + `/opt/lampp/phpmyadmin` deleted) so it can't be reached
- **fail2ban** installed with an `xampp-dos` ip-rate-limit jail + standard Apache jails, pointed at `/opt/lampp/logs/`

Then snapshot the box.

Flags: `--skip-opcache`, `--skip-phpmyadmin`, `--skip-fail2ban`, `--yes`.
Ban tuning: `BANTIME`, `FINDTIME`, `MAXRETRY` env vars (default: >300 req/60s → 1h ban).

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
# fail2ban up
fail2ban-client status && fail2ban-client status xampp-dos
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
  `apache-overflows`) plus the custom `xampp-dos` filter shipped in this kit.
