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

### 2. Provision each new app (per clone)

```bash
sudo ./provision-app.sh swusim swusim          # <app> <db>, defaults to swusim swusim
```

Per-app, re-runnable:

- **Env vars** — writes `etc/extra/httpd-<app>-env.conf` with `SetEnv MYSQL_DATABASE_NAME <db>` (+ server/user/password/redis) and Includes it from `httpd.conf`. The app's `getenv()` in `Database/ConnectionManager.php` then resolves the right DB.
- **Set up DB** — DROPs the leftover `STALE_DB` (default `soulmastersdb` — what the restored box ships with), then (re)creates `<db>` fresh and loads the canonical schema (`SCHEMA_SQL`, default `../Database/database.sql`). Ends clean + empty, no `soulmastersdb`. No dump. Typed confirmation (lists what gets dropped) unless `--yes`.

Flags: `--skip-env`, `--skip-db`, `--yes`.
DB creds: `DB_USER`, `DB_PASS`, `MYSQL_HOST`, `REDIS_HOST`, `REDIS_PORT` env vars
(defaults: `root` / empty / `localhost` / `127.0.0.1` / `6379`).

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
