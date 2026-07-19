# Updating a live app box (code pull) — runbook

For pulling **new code** into an already-running box (swustats.net, zendo.gg, …) without
breaking it. This is the everyday "deploy the latest" path — distinct from *standing up* a box,
which is `harden-*.sh` + `provision-app.sh` (see [README.md](README.md)).

> **Golden rule:** a code update is just **back up → pull → restart → verify**. Do NOT run any
> `newhost/` provisioning scripts for a code update — they're for host/app *setup*, and each can
> break a live box (`harden-htaccess.sh` rewrites `.htaccess`; `provision-app.sh --reset-db` wipes
> the DB). Code changes are almost always additive and site-scoped.

## Always use LAMPP's clients, not the system ones
The system `mysql`/`mysqldump` (from `mariadb-client`) default to `/var/run/mysqld/mysqld.sock`;
LAMPP's MySQL uses its own socket under `/opt/lampp`. Use the bundled binaries:
```
/opt/lampp/bin/mysql        /opt/lampp/bin/mysqldump
```
(A bare `mysqldump` gives `Can't connect … /var/run/mysqld/mysqld.sock`.)

## Steps

**1. Back up first** (you want a fallback — the one time there wasn't one, it hurt):
```bash
/opt/lampp/bin/mysqldump -u root -p'<pass>' <db> > ~/<db>-$(date +%F-%H%M).sql
ls -lh ~/<db>-*.sql                                        # confirm it's non-empty
cp /opt/lampp/htdocs/.htaccess ~/htaccess-$(date +%F).bak
grep -rn "MYSQL_DATABASE_NAME" /opt/lampp/etc/ > ~/env-before.txt
```

**2. Baseline — confirm it's healthy BEFORE changing anything:**
```bash
curl -sI https://<domain>/TCGEngine/SharedUI/MainMenu.php   # expect HTTP 200
```
If it's already broken, stop and fix that first (don't stack a deploy on a broken box).

**3. Pull the code** into `htdocs/TCGEngine` the way you normally deploy (git pull / rsync).
`vendor/` and generated files are gitignored, so a pull never disturbs them.

**4. New DB migrations?** If the pull added a `Database/migrations/NN_*.sql`, apply it to **this
box's** DB with LAMPP's client:
```bash
/opt/lampp/bin/mysql -u root -p'<pass>' <db> < /opt/lampp/htdocs/TCGEngine/Database/migrations/NN_name.sql
```
The migrations here are expand-first / additive (nullable columns, indexes) — safe on a live DB.
A destructive migration would need its own plan; don't run one blind.

**5. Restart LAMPP** (belt-and-suspenders — OPcache has `validate_timestamps=1` so it revalidates
pulled files on its own, but a restart guarantees fresh bytecode + re-read php.ini):
```bash
sudo /opt/lampp/lampp restart
```

**6. Verify:**
```bash
curl -sI https://<domain>/TCGEngine/SharedUI/MainMenu.php   # still 200
sudo tail -5 /opt/lampp/logs/error_log                      # no new fatals
```
Then load the site in a browser and confirm the **right app** renders (not a 500, not the wrong site).

## Do NOT (the things that broke swustats.net once)
- ❌ `harden-htaccess.sh` — rewrites the docroot `.htaccess`; wipes anything hand-added there.
- ❌ `provision-app.sh` — app/DB setup; `--reset-db` DROPs the database.
- ❌ system `mysql`/`mysqldump` — wrong socket.

## If it 500s — read the actual fatal, don't guess
```bash
sudo tail -30 /opt/lampp/logs/error_log
```
Common ones and their fix:
- **`ActiveSite: MYSQL_DATABASE_NAME is not set`** or the **wrong site renders** → env conf problem.
  See the provision-app section in [README.md](README.md) (env lives in `httpd.conf`, one site per box).
- **`Access denied` / `Unknown database`** → wrong DB name/user/password in the env conf.
- **`require … vendor/autoload.php` fatal** → `vendor/` missing; run `sudo ./install-php-deps.sh`.
- **`gd.so` warning** → not a MainMenu blocker, but image generation needs a real GD (see README).

That's it: **back up → check 200 → pull → (migrate) → restart → check 200.**
