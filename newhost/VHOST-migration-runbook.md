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

- [ ] **Confirm exactly one server-level env conf, and its value:**
  ```bash
  grep -rn "MYSQL_DATABASE_NAME" /opt/lampp/etc/
  ```

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
```bash
curl -sI https://<domain>/TCGEngine/SharedUI/MainMenu.php | head -1
curl -s  -o /dev/null -w 'http  unmatched: %{http_code}\n' -H 'Host: nope.invalid' http://127.0.0.1/
curl -sk -o /dev/null -w 'https unmatched: %{http_code}\n' -H 'Host: nope.invalid' https://127.0.0.1/
```
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
