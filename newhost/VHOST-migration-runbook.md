# Converting a live box to a name-based vhost — runbook

For migrating an **already-running single-app box** from the server-level `SetEnv
MYSQL_DATABASE_NAME` that `provision-app.sh` writes, to a name-based `<VirtualHost>` — the
prerequisite for ever hosting a second sim on that box. Distinct from *standing up* a box
(`harden-*.sh` + `provision-app.sh`, see [README.md](README.md)) and from an everyday code
deploy ([UPDATE-runbook.md](UPDATE-runbook.md)).

> **Golden rule: phase 1 changes routing for NOBODY.** It introduces the vhost and stops there.
> It does **not** retire the server-level env conf, and it does **not** add the deny-everything
> catch-all. Both of those are phase-2 changes that only make sense once a second site exists.
> Doing them now would change two things at once, and the failure mode is silent.

> **The one that bites: TLS.** Server-level `SetEnv` is **inherited by every vhost**. XAMPP ships
> a live `<VirtualHost *:443>` in `etc/extra/httpd-ssl.conf`, Included from `httpd.conf` by
> default. Retire the server-level conf while creating a `:80`-only vhost and that 443 vhost
> loses `MYSQL_DATABASE_NAME` — `ActiveSite.php` throws and **every HTTPS request 500s** while a
> port-80 `curl` looks perfectly healthy. Behind Cloudflare **Full**/**Full (strict)** (CF talks
> to the origin over 443) that is the entire site. "It's behind Cloudflare" does **not** mean
> port 80 only — only **Flexible** mode does.

---

## Phase 0 — Discovery (read-only, do this first)

Nothing here changes anything. Every answer feeds a flag in step 5.

- [ ] **Is TLS terminated by Apache on this box?**
  ```bash
  grep -rnE "Listen 443|SSLEngine on|SSLCertificateFile" /opt/lampp/etc/ | grep -v '#'
  ```
  Ignore hits under `/opt/lampp/etc/original/` — that's XAMPP's pristine backup, not live config.

- [ ] **Is the SSL conf actually Included, and what is genuinely listening?**
  ```bash
  grep -n "httpd-ssl.conf" /opt/lampp/etc/httpd.conf     # commented out = inert
  ss -lntp | grep -E ':(80|443)\b'                       # ground truth, beats config archaeology
  ```

- [ ] **Does the box already have vhosts?** This changes default-host semantics — Apache serves
      the **first** vhost on a port to any unmatched `Host`.
  ```bash
  grep -rn "VirtualHost" /opt/lampp/etc/ | grep -v original
  ```
  For each one found, read its `ServerName` **and `DocumentRoot`**:
  ```bash
  sed -n '<start>,<start+30>p' /opt/lampp/etc/extra/httpd-ssl.conf
  ```
  If a docroot differs from `/opt/lampp/htdocs`, **stop** — adding our vhost would change what
  that hostname serves, and this runbook's zero-change guarantee no longer holds.

- [ ] **Confirm the server-level env conf and its value:**
  ```bash
  grep -rn "MYSQL_DATABASE_NAME" /opt/lampp/etc/
  ```
  Three outcomes, and they change the run:
  - **One conf, value in the `$dbToSite` map** — the normal migration. Use `--keep-server-env`.
  - **Several confs** — Guard 1 blocks; convert each app first.
  - **NOTHING (no match at all)** — the var was never set. Legitimate for an app that needs no
    database: gamestate lives in files under `Games/`, and `ActiveSite.php` never connects to
    MySQL, so a site can run happily off direct `/SharedUI/Sites/<Site>/…` URLs while the root
    pointer throws `MYSQL_DATABASE_NAME is not set`. Here the run is **purely additive**: drop
    `--keep-server-env` (it is a no-op that prints a misleading "keeping" warning for a file that
    does not exist), nothing is retired, and rollback is just removing the Includes. The vhost
    gives the box a DB env for the first time, so expect the root pointer to START working.

- [ ] **Which hostnames actually resolve here?** Every real one needs a `--server-alias`, or it
      stops resolving once a vhost exists. Don't forget `www.`, the bare IP, and monitoring.

- [ ] **How far behind is the box's checkout?**
  ```bash
  cd /opt/lampp/htdocs/TCGEngine && git log --oneline -1 && git status --porcelain
  ```
  If it's many commits behind, **pull and verify the app as its own separate step first**.
  A months-behind pull plus a vhost migration in one window is two variables again.

---

## Phase 1 — The migration

### 1. Back up
```bash
/opt/lampp/bin/mysqldump -u root -p'<pass>' <db> > ~/<db>-$(date +%F-%H%M).sql
ls -lh ~/<db>-*.sql                                  # confirm non-empty
cp -a /opt/lampp/etc/httpd.conf ~/httpd.conf-$(date +%F).bak
cp -a /opt/lampp/etc/extra ~/extra-$(date +%F).bak
grep -rn "MYSQL_DATABASE_NAME" /opt/lampp/etc/ > ~/env-before.txt
```
The script also writes its own backups to `newhost/newhost-backups-<timestamp>/`, but take
these anyway — they cover files the script never touches.

### 2. Capture the baseline — **this is what you compare against afterwards**

⚠ **Baseline the URL people actually use, which may not be the root pointer.** The root
`/SharedUI/MainMenu.php` is an `ActiveSite` dispatch, so on a box whose env var is unset or names
a db that is not in the `$dbToSite` map it **throws** — `ActiveSite` has no fallback on purpose.
Such a box can have a perfectly healthy site that users reach via the full
`/SharedUI/Sites/<Site>/MainMenu.php` path, which does **not** read `ActiveSite`. Baselining only
the pointer would record a 500 and tell you nothing about real users. Capture **both**:
```bash
# the path users actually hit — this one MUST NOT regress
curl -sI https://<domain>/TCGEngine/SharedUI/Sites/<Site>/MainMenu.php | head -1
# the root pointer — may be 500 today; expected to START WORKING after the migration
curl -sI https://<domain>/TCGEngine/SharedUI/MainMenu.php | head -1
curl -s  -o /dev/null -w 'http  unmatched: %{http_code}\n' -H 'Host: nope.invalid' http://127.0.0.1/
curl -sk -o /dev/null -w 'https unmatched: %{http_code}\n' -H 'Host: nope.invalid' https://127.0.0.1/
```
Giving the vhost a **mapped** `MYSQL_DATABASE_NAME` is what repairs the pointer — an intended
improvement, but state it up front so a changed response reads as success, not regression.
Write those three numbers down. "Unmatched Host still behaves the same" is the whole point of
`--default-site`, and you cannot verify it without a before-value.

- [ ] Also log in through a browser and confirm a session sticks. Session cookies are
      host-scoped, so an unexpected hostname shift shows up as a login that silently won't hold.
- [ ] If the site is already broken, **stop and fix that first.**

### 3. Get the script onto the box
```bash
cd /opt/lampp/htdocs/TCGEngine && git pull
ls -l newhost/provision-vhost.sh                    # exec bit is committed as 100755
```
Generated engine files are gitignored, so a pull leaves them alone. **Do not regen as part of
this** — the vhost work needs no regeneration, and a regen is an independent change with its
own risk (new code against a freshly-rebuilt dictionary).

### 4. Dry-run the config without restarting
Add `--skip-restart` to the step-5 command, then inspect before committing to it:
```bash
cat /opt/lampp/etc/extra/httpd-vhost-*.conf
/opt/lampp/bin/httpd -t                              # must print "Syntax OK"
```

### 5. Run it

Worked example — clarent.net / GrandArchiveSim, Apache terminating TLS with XAMPP's default
self-signed cert:
```bash
sudo DB_PASS='<real>' ./provision-vhost.sh grandarchivesim \
     --server-name clarent.net --server-alias www.clarent.net \
     --ports "80 443" \
     --ssl-cert /opt/lampp/etc/ssl.crt/server.crt \
     --ssl-key  /opt/lampp/etc/ssl.key/server.key \
     --default-site --keep-server-env
```

Flag-by-flag, and why each is load-bearing here:

| Flag | Why |
|---|---|
| `--default-site` | This app's vhost sorts first (`httpd-vhost-000-<app>.conf`) and is therefore the fallback for any unmatched `Host`. Preserves today's "any hostname works" behaviour exactly. Skips the deny catch-all. |
| `--keep-server-env` | Leaves the server-level `SetEnv` in place so **other** vhosts (XAMPP's stock `*:443`) keep inheriting the DB. On a single-site box both envs name the same DB, so keeping both is harmless. |
| `--ports "80 443"` + cert/key | Only if phase 0 showed Apache terminating TLS. Reuse the cert the box already serves — it demonstrably satisfies the current CF SSL mode. |
| `--server-alias` | One per additional real hostname from phase 0. |

The script refuses to restart on a bad config (`httpd -t` gate) and `die`s if the cert/key
paths don't exist, so a wrong path fails clean before anything is written.

### 6. Verify — compare against step 2, don't just look for 200s
```bash
curl -sI https://<domain>/TCGEngine/SharedUI/MainMenu.php | head -1
curl -s  -o /dev/null -w 'http  unmatched: %{http_code}\n' -H 'Host: nope.invalid' http://127.0.0.1/
curl -sk -o /dev/null -w 'https unmatched: %{http_code}\n' -H 'Host: nope.invalid' https://127.0.0.1/
sudo tail -30 /opt/lampp/logs/error_log                # no ActiveSite / DB fatals
```
- [ ] All three codes **match the baseline**.
- [ ] **Test HTTPS explicitly**, not just HTTP — that's where the failure mode hides.
- [ ] Browser: load the site, log in, confirm the session sticks.
- [ ] Play one real action (start or resume a game) so a DB write is exercised, not just a render.

### 7. Watch
Leave it an hour and re-check `error_log`. A wrong-env failure is loud (`ActiveSite` throws),
but a *cookie-scope* problem only shows as users being quietly logged out.

---

## Rollback

With `--keep-server-env` the server-level conf was never retired, so removing the two Includes
is a complete revert:
```bash
cd /opt/lampp/etc
sudo sed -i '/httpd-vhost-0\*\.conf/d; /httpd-vhost-1\*\.conf/d' httpd.conf
sudo /opt/lampp/bin/httpd -t && sudo /opt/lampp/lampp restart
```
If you ran **without** `--keep-server-env`, the env conf was renamed to
`httpd-<app>-env.conf.retired` and must be restored too:
```bash
sudo mv extra/httpd-<app>-env.conf.retired extra/httpd-<app>-env.conf
echo 'Include etc/extra/httpd-<app>-env.conf' | sudo tee -a httpd.conf
```
Full fallback: `~/extra-<date>.bak` and `~/httpd.conf-<date>.bak` from step 1.

---

## Deliberately left for phase 2

Do **not** do these while converting a single-site box — each is a separate change with its own
verification:

1. **Retire the server-level env conf** (drop `--keep-server-env`). Only safe once every live
   vhost has its own env. ⚠ Guard 3 reasons about **ports**, not individual vhosts: with 443 in
   `--ports` it considers the port covered and permits the retirement, even though XAMPP's stock
   `*:443` vhost would then lose its inherited env. Delete or repurpose that stock vhost first.
2. **Add the deny catch-all** (drop `--default-site`). Turns an unmatched `Host` from "served" to
   `403`, which is correct once a second site exists and wrong before then.
3. **Add the second site** — `provision-vhost.sh <app2> --server-name <fqdn2>`, its own DB, its
   own hostname and cert.
4. **`php SharedUI/Render/GenerateSites.php all`** — **mandatory** on a multi-site box, and the
   `all` is the load-bearing part. The generated root pointers (`SharedUI/MainMenu.php` etc.) are
   a *runtime* dispatch — `include __DIR__.'/Sites/'.(require __DIR__.'/ActiveSite.php').'/…'` —
   so ONE file serves every site, resolving per request from the Host-selected env. That is what
   makes two sites work off one checkout with no code change. But `GenerateSites.php` **defaults
   to generating only the ACTIVE site**, so a default run on a two-site box never builds
   `Sites/<Site2>/` — and the pointer then includes a file that doesn't exist and fatals for the
   second hostname only. Verify `SharedUI/Sites/<Site2>/` is populated after generating.

Only after 1–3 does the box actually serve two sims — phase 1 buys the mechanism, not the
capability.

---

# Phase 2 — adding a second site (worked example: northbeach.gg → HellbreakSim)

Phase 1 left clarent.net → GrandArchiveSim on a vhost, with the app itself acting as the
unmatched-`Host` fallback (`--default-site`). Phase 2 adds northbeach.gg → HellbreakSim on the
same box and **moves the fallback to the new site**.

## Target end state

| | |
|---|---|
| `clarent.net`, `www.clarent.net` | db `grandarchivesim` → `httpd-vhost-100-grandarchivesim.conf` |
| `northbeach.gg`, `www.northbeach.gg` | db `hellbreaksim` → `httpd-vhost-000-hellbreaksim.conf` |
| **unmatched `Host`** (bare IP, monitoring, stray hostnames, scanners) | **HellbreakSim** |
| deny-403 catch-all | **not used** — the fallback is a real site, by choice |
| TLS | XAMPP's self-signed cert, reused for both vhosts; Cloudflare stays on **Full**, never Full (strict) |

> **The fallback is a deliberate choice, and it changes behaviour for real traffic.** Anything
> that reaches the box today on the bare IP or on a hostname that is not `clarent.net` /
> `www.clarent.net` currently renders **GrandArchiveSim**; after this it renders **HellbreakSim**.
> That is the intent — but list what actually hits the box (monitoring, health checks, old
> hostnames, Discord embeds) before committing, because they all move.

## Why a rename, not a re-run, demotes the old default

`provision-vhost.sh` picks its output filename from `--default-site` and nothing else:
`httpd-vhost-000-<app>.conf` with the flag, `httpd-vhost-100-<app>.conf` without. **The file
contents are byte-identical either way.** So demoting GrandArchiveSim is a one-command `mv` —
faithful, and reversible by the reverse `mv`.

Re-running the script for `grandarchivesim` *without* `--default-site` is the wrong move here: it
would leave the stale `000-grandarchivesim.conf` on disk (two vhosts claiming `clarent.net`) **and**
write the deny-403 catch-all `httpd-vhost-000-default.conf`, which sorts before
`000-hellbreaksim.conf` and would steal the fallback we are trying to hand to Hellbreak.

## Step 2.0 — DNS first; it is the long pole

As of 2026-08-12 `northbeach.gg` has **no NS delegation at all** (`dig northbeach.gg NS` → empty).

**This does not block the Apache work.** Every routing check in step 2.9 uses
`curl -H 'Host: northbeach.gg' http://127.0.0.1/…`, which exercises exactly the vhost selection
Apache will do for real traffic, with no DNS involved. DNS is only needed for browsers and
Cloudflare — i.e. for the cookie-isolation check and for real users. Start it in parallel because
`.gg` delegation is slow:

1. Add `northbeach.gg` as a zone in Cloudflare (same account as clarent.net).
2. Set the registrar's nameservers to the two Cloudflare NS it gives you. `.gg` delegation can
   take a few hours.
3. In the zone: `A  @  <origin IP>` **proxied**, `A  www  <origin IP>` **proxied**.
4. SSL/TLS mode → **Full**. *Not* Full (strict): the origin serves XAMPP's self-signed cert, which
   matches neither hostname, and strict would hard-fail every request.
5. Confirm before touching Apache:
   ```bash
   dig +short northbeach.gg NS
   dig +short northbeach.gg A        # expect Cloudflare IPs, not the origin
   ```

## Step 2.1 — Discovery on the box (read-only; paste the output back before proceeding)

> **Findings from the 2026-08-12 run on `hwsrv-1266365` — four of them changed the plan:**
>
> 1. **XAMPP's stock SSL vhost is already gone.** `httpd-ssl.conf` *is* Included (`httpd.conf:508`)
>    but declares no live `<VirtualHost>` — `httpd -S` lists `clarent.net` as the only vhost on
>    **both** :80 and :443, from `httpd-vhost-000-grandarchivesim.conf`. So `--default-site` really
>    is the fallback on both ports today, and **step 2.8 is a no-op**. Leave `httpd-ssl.conf`
>    Included: it is where `Listen 443` comes from.
> 2. **No server-level `SetEnv` anywhere** — the only two `MYSQL_DATABASE_NAME` lines are inside
>    the GA vhost itself. Guard 1 and Guard 3 both pass, and there is nothing for step 4 of the
>    script to retire, so **drop `--keep-server-env`** (it would only print a misleading warning).
> 3. **`httpd.conf` needs no edit.** Lines 528/529 are already
>    `IncludeOptional etc/extra/httpd-vhost-0*.conf` then `…1*.conf`, in the right order. The
>    script will find its glob already present and leave the file alone.
> 4. **Skip the `git pull`.** The box is 18 commits behind (HEAD `85fcf3b8`, 2026-08-11) but
>    `SharedUI/Sites/HellbreakSim/` and the `hellbreaksim` → `HellbreakSim` map entry are **already
>    in that commit**. Phase 2 needs nothing from those 18 commits, and the runbook's own rule is
>    not to combine a pull with a routing change. Deploy separately, afterwards.
>    (Checked: none of the box's ~30 untracked leftovers collide with a tracked path in current
>    `main`, so the pull is safe whenever you do get to it. Unrelated, but `zzPHPInfo.php` is
>    sitting in the docroot — worth deleting.)

```bash
# ground truth on vhost ordering — which vhost is the default server per port
sudo /opt/lampp/bin/httpd -S

# what is on disk and what httpd.conf actually loads
ls -l /opt/lampp/etc/extra/httpd-vhost-*.conf
grep -n "httpd-vhost" /opt/lampp/etc/httpd.conf
grep -rn "MYSQL_DATABASE_NAME" /opt/lampp/etc/ | grep -v original

# XAMPP's stock SSL vhost — is it live, and what ServerName does it claim?
grep -n "httpd-ssl.conf" /opt/lampp/etc/httpd.conf /opt/lampp/etc/extra/httpd-xampp.conf
grep -n -E "VirtualHost|ServerName|DocumentRoot" /opt/lampp/etc/extra/httpd-ssl.conf | head

# does the second DB exist yet?
/opt/lampp/bin/mysql -u root -p'<real>' -e "SHOW DATABASES;"

# how far behind is the checkout? (HellbreakSim must be present)
cd /opt/lampp/htdocs/TCGEngine && git log --oneline -1 && git status --porcelain
```

**`httpd -S` is the step that decides step 2.5.** Our vhost `Include` lines are appended to the
*end* of `httpd.conf`, while XAMPP's `httpd-ssl.conf` is Included earlier — so its stock
`<VirtualHost _default_:443>` may well be registered **first on :443** and be the real HTTPS
fallback today, regardless of `--default-site`. Read `httpd -S`'s "default server" line for port
443 rather than reasoning about it.

## Step 2.2 — Back up

```bash
/opt/lampp/bin/mysqldump -u root -p'<real>' grandarchivesim > ~/grandarchivesim-$(date +%F-%H%M).sql
ls -lh ~/grandarchivesim-*.sql                       # confirm non-empty
cp -a /opt/lampp/etc/httpd.conf ~/httpd.conf-$(date +%F).bak
cp -a /opt/lampp/etc/extra     ~/extra-$(date +%F).bak
sudo /opt/lampp/bin/httpd -S > ~/httpd-S-before.txt 2>&1
```

## Step 2.3 — Baseline (the numbers you compare against in 2.9)

```bash
curl -sI https://clarent.net/TCGEngine/SharedUI/MainMenu.php | head -1
curl -sI https://clarent.net/TCGEngine/SharedUI/Sites/GrandArchiveSim/MainMenu.php | head -1
curl -s  -o /dev/null -w 'http  unmatched: %{http_code}\n' -H 'Host: nope.invalid' http://127.0.0.1/TCGEngine/SharedUI/MainMenu.php
curl -sk -o /dev/null -w 'https unmatched: %{http_code}\n' -H 'Host: nope.invalid' https://127.0.0.1/TCGEngine/SharedUI/MainMenu.php
```
The two unmatched numbers are expected to **change meaning** (they will start rendering
HellbreakSim) — that is the point of this phase. Record them anyway; the clarent.net numbers must
**not** move.

## Step 2.4 — Database (no `git pull` — see 2.1 finding 4)

```bash
cd /opt/lampp/htdocs/TCGEngine
ls -d SharedUI/Sites/HellbreakSim HellbreakSim Schemas/HellbreakSim   # all three must exist
grep -n hellbreaksim SharedUI/ActiveSite.php                          # the map entry must be there

# dry run first — this script NEVER drops, but read the plan anyway
sudo DB_PASS='<real>' ./newhost/ensure-db.sh hellbreaksim
sudo DB_PASS='<real>' ./newhost/ensure-db.sh hellbreaksim --apply
```
`ensure-db.sh` loads `Database/database.sql`, which is already complete — **do not** apply
`Database/migrations/*.sql` to a database it just created.

## Step 2.5 — Generate BOTH sites' root pointers

```bash
cd /opt/lampp/htdocs/TCGEngine
php SharedUI/Render/GenerateSites.php all
ls SharedUI/Sites/HellbreakSim/ | head          # must be populated
```
The `all` is load-bearing — a bare run generates only the *active* site, and northbeach.gg would
then fatal on a missing include while clarent.net looked perfectly healthy. Per-app engine files
are generated and gitignored; if the pull brought schema/generator changes, regenerate those too
(`php zzGameCodeGenerator.php rootName=HellbreakSim`) — a stale generated file is a blank board.

## Step 2.6 — Write the HellbreakSim vhost (config only, no restart yet)

Take the cert paths from the vhost that is already serving them, rather than guessing:
```bash
grep -n SSLCertificate /opt/lampp/etc/extra/httpd-vhost-000-grandarchivesim.conf
```
```bash
cd /opt/lampp/htdocs/TCGEngine
sudo DB_PASS='<real>' ./newhost/provision-vhost.sh hellbreaksim \
     --server-name northbeach.gg --server-alias www.northbeach.gg \
     --ports "80 443" \
     --ssl-cert /opt/lampp/etc/ssl.crt/server.crt \
     --ssl-key  /opt/lampp/etc/ssl.key/server.key \
     --default-site --skip-restart
```
`--default-site` writes `httpd-vhost-000-hellbreaksim.conf` — the **filename** is what makes it the
fallback. `--keep-server-env` is omitted deliberately: 2.1 found no server-level `SetEnv` on this
box, so there is nothing to retire and the flag would only print a misleading "keeping" warning.

⚠ Guard 2 (one hostname, one app) only scans `httpd-vhost-1*.conf`, and the closing summary only
lists `httpd-vhost-1*.conf` — so a `--default-site` vhost is invisible to both. HellbreakSim not
appearing in the summary is expected, not a failure.

## Step 2.7 — Demote GrandArchiveSim out of the fallback slot

```bash
sudo mv /opt/lampp/etc/extra/httpd-vhost-000-grandarchivesim.conf \
        /opt/lampp/etc/extra/httpd-vhost-100-grandarchivesim.conf
ls -l /opt/lampp/etc/extra/httpd-vhost-*.conf
```
Both `Include` lines already cover the result — `Include etc/extra/httpd-vhost-0*.conf` now matches
only Hellbreak, and `IncludeOptional etc/extra/httpd-vhost-1*.conf` picks up GrandArchive. Do not
reorder those lines.

## Step 2.8 — XAMPP's stock `*:443` vhost — **NOT NEEDED on this box** (2.1, finding 1)

`httpd -S` showed `clarent.net` as the only vhost on :443, so `httpd-ssl.conf` declares no live
`<VirtualHost>` and nothing is competing for the HTTPS fallback. **Leave it Included** — it is
where `Listen 443` comes from, and commenting it out would take HTTPS down entirely.

Kept below for the next box, where it may not be true. If `httpd -S` names `httpd-ssl.conf` as the
default server for port 443, HellbreakSim will not be the HTTPS fallback until that vhost is out
of the way. Comment out its `Include` and re-test:
```bash
grep -n "httpd-ssl.conf" /opt/lampp/etc/httpd.conf /opt/lampp/etc/extra/httpd-xampp.conf
sudo sed -i 's|^\([[:space:]]*Include .*httpd-ssl.conf\)|#\1|' <the file that includes it>
sudo /opt/lampp/bin/httpd -t
```
⚠ That conf also carries `Listen 443` in some XAMPP builds. If commenting it out drops the
`Listen`, **HTTPS stops entirely** — check `grep -n "^Listen" /opt/lampp/etc/httpd.conf
/opt/lampp/etc/extra/httpd-ssl.conf` first and move the `Listen 443` into `httpd.conf` if it lives
only in the file you are disabling.

## Step 2.9 — Validate, restart, verify

```bash
sudo /opt/lampp/bin/httpd -t                     # must print "Syntax OK"
sudo /opt/lampp/bin/httpd -S                     # diff against ~/httpd-S-before.txt
sudo /opt/lampp/lampp restart
```
Then, in order — **each of these has caught a different failure**:
```bash
# 1. the OLD site must not have moved
curl -sI https://clarent.net/TCGEngine/SharedUI/MainMenu.php | head -1
curl -s -H 'Host: clarent.net' http://127.0.0.1/TCGEngine/SharedUI/MainMenu.php | grep -io "grand archive\|hellbreak" | head -1

# 2. the NEW site renders, over both ports
curl -s  -H 'Host: northbeach.gg'  http://127.0.0.1/TCGEngine/SharedUI/MainMenu.php  | grep -io "hellbreak" | head -1
curl -sk -H 'Host: northbeach.gg' https://127.0.0.1/TCGEngine/SharedUI/MainMenu.php  | grep -io "hellbreak" | head -1

# 3. the fallback moved to Hellbreak — on BOTH ports (443 is the one that hides)
curl -s  -H 'Host: nope.invalid'  http://127.0.0.1/TCGEngine/SharedUI/MainMenu.php  | grep -io "hellbreak" | head -1
curl -sk -H 'Host: nope.invalid' https://127.0.0.1/TCGEngine/SharedUI/MainMenu.php  | grep -io "hellbreak" | head -1

sudo tail -40 /opt/lampp/logs/error_log          # no ActiveSite / DB fatals
```
- [ ] **Cookie isolation**, the one curl cannot prove: log in on clarent.net in a browser, then
      load northbeach.gg — you must be **logged out** there. Then log in on northbeach.gg and
      reload clarent.net — still logged in as the *first* account. If a login crosses over, stop:
      the two `users` tables are being confused.
- [ ] Play one real action on each site (start or resume a game) so a DB **write** is exercised on
      each database, not just a render.
- [ ] Re-check `error_log` an hour later. A wrong-env failure is loud; a cookie-scope failure only
      looks like users being quietly logged out.

## Rollback

Every step is individually reversible, newest first:
```bash
# 2.7 — hand the fallback back to GrandArchive
sudo mv /opt/lampp/etc/extra/httpd-vhost-100-grandarchivesim.conf \
        /opt/lampp/etc/extra/httpd-vhost-000-grandarchivesim.conf
# 2.6 — remove the Hellbreak vhost entirely
sudo rm /opt/lampp/etc/extra/httpd-vhost-000-hellbreaksim.conf
# 2.8 — un-comment the httpd-ssl.conf Include if it was disabled
sudo /opt/lampp/bin/httpd -t && sudo /opt/lampp/lampp restart
```
The `hellbreaksim` database and the generated `Sites/HellbreakSim/` files are inert once no vhost
selects them — leave them. Full fallback: `~/extra-<date>.bak` + `~/httpd.conf-<date>.bak`.

## Known gaps this phase does NOT close

- **Deny-403 catch-all** — deliberately not used; the fallback is HellbreakSim. Revisit only if
  serving a real site to arbitrary `Host` headers becomes a problem.
- **Server-level env retirement** — still `--keep-server-env`. Harmless while no server-level
  `SetEnv` exists; re-check with `grep -rn MYSQL_DATABASE_NAME /opt/lampp/etc/` after any
  `provision-app.sh` run (which must never be run on this box again).
- **Full (strict) TLS** — needs one cert covering both hostnames (Cloudflare Origin CA is the
  cheapest route). Until then Cloudflare must stay on **Full** for *both* zones.
- **No local Hellbreak container** — `docker-compose.yml` has no `hellbreaksim-web-server`, so
  HellbreakSim cannot be exercised locally the way SWUDeck (:3100) and SWUSim (:3400) are. The box
  is currently the first place it renders under a real hostname. Adding a container (see the
  `new-docker-app` skill) is the fix if this becomes routine.
