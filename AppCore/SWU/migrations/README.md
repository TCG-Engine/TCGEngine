# SWU migrations

SWU-specific data and schema migrations, applied in numeric order **within this directory**.

This file doubles as the **runbook for the `SET_NNN` identity cutover**. Design:
`docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md`.

> **Status: tooling complete, not yet rehearsed on prod.** Every command below is real and has been
> exercised against the local prod-clone — the DB migration on a seeded scratch database, the
> deck-file rewrite on a copy of the deck tree. **None of it has run on prod**, so every step still
> has to be rehearsed against the clone during the dry run before it is trusted there.

# DEPLOY DAY

The three things you need in front of you. Lettered A/B/C so a `§n` reference always means the
numbered runbook further down, never this section. Detail for every step is in the numbered runbook below —
read §2 through once beforehand; do not meet a step for the first time here.

**LAMPP is stopped BEFORE the push** (steps 1–7). That is deliberate and it is the safest order:
the new code never serves a live request, so there is no deploy gap at all. `.htaccess` is read
per-request and could be edited live, but stopping first guarantees nothing slips through and no
PHP process is mid-write. The cost is that MySQL is down too, which is why `0-env.sh` is sourced
twice — `--check` while down (it needs no database), then fully once the restart brings MySQL back.

**ONE push containing everything**, including ingress and `StatsBaseRegistry`. Because the server is
already stopped when it lands, there is no window in which the new code serves live traffic.
(Should one ever occur — an aborted stop, a restart mid-window — it is survivable: a submission
written by the new ingress is `SET_NNN`, and the aggregating insert merges it with its UUID
history. Verified: a `2579145458` row of 7 plus a gap-written `SOR_005` row of 1 came out as a
single `SOR_005` row of **8**.)

---

## A. The path

Each step gates the next. **Do not proceed past a failed check.**

| # | do | gate before moving on |
|---|---|---|
| 1 | **Stop LAMPP.** Before the push, so the new code never serves a single live request — no deploy gap to reason about, and nothing is mid-write when you snapshot. ⚠ users get a connection error, not a 503, until step 5. | `lampp stop` returns |
| 2 | **Push everything to the server.** | deploy reports clean |
| 3 | **`. ./0-env.sh --check`** — read-only; validates paths, binaries, deck tree, free space. Needs no database, so it works with LAMPP down. | prints **CHECK PASSED** |
| 4 | **`1-maintenance.sh on --ip=<your public IP>`** — pure file write, needs neither Apache nor MySQL | `.htaccess` written |
| 5 | **Start LAMPP.** | `lampp start` returns |
| 6 | **Verify the block FROM A PHONE on mobile data.** You could not test it while the server was down, and a `curl` from the box proves nothing — localhost is not your public IP. A 200 here means `AllowOverride` is off and the file is being IGNORED. | MainMenu **503**, `/TCGEngine/zz*` **200** |
| 7 | **`. ./0-env.sh`** (no `--check`) — MySQL is up now, so it takes the password and resolves `$DB` | prints **Environment ready** |
| 8 | **Regenerate card dictionaries** — web UI, mod login (`zzCardCodeGenerator.php?rootName=SWUDeck`, CLI fails on prod) | census shows **27+ tokens**; `2007868442` → `SOR_T01` |
| 9 | Flip `zzMaintenanceMode.php` to **"Full write freeze"** — the second layer under the `.htaccess`. Set it to full now, not staged: the site is already blocked, and this is what protects the deck rewrite at step 20. | `test_swu_maintenance_guards.php` PASS; a stats POST returns **503**; a meta page still **200** |
| 10 | **`2-snapshot.sh --backup-dir=$BACKUP`** (deck tree; `4-migrate.sh` takes the DB dump itself) | `gunzip -t` passes **and** every target table appears in the dump |
| 11 | **`3-capture-egress.sh before --backup-dir=$BACKUP --deck=<legacy deckID>`** | all three files non-empty |
| 12 | **Re-run the census** (§2.3) | class 3 contains nothing new; anything recently dated is **test residue**, not data |
| 13 | **Capture before-totals** (§2.4) | file written |
| 14 | **Re-run the ENGINE generator** — its output is gitignored, and stale generated files render a blank board | `zzGameCodeGenerator.php rootName=SWUDeck` exits 0 |
| 15 | **Build the art corpus** on both boxes, then retire the old trees (§2.5a) | corpus test PASS; sweep finds no live old-tree reader; Discord shim **200** for a legacy UUID, **404** for a bogus one |
| 16 | **Generate `01`/`02`/`03`** (§2.6) — `02`/`03` need mysqli, so build them on a schema-identical box and copy over | `materialize-id-map.php --summary` shows ~4,800 rows, 20+ tokens, 156 asset hashes |
| 17 | **`4-migrate.sh --db=$DB --backup-dir=$BACKUP`** (dry run) | exits **0** (~4.5 min at 2.5 GB — see §4) |
| 18 | **`4-migrate.sh … --apply`** | swap completes; every `*_old` present. ⚠ **crosses rollback rung 2** |
| 19 | **`5-rewrite-decks.sh`** (dry run) | **0 unmapped**, or a documented `--allow-unmapped` decision (see §2.7) |
| 20 | **CANARY — one deck before committing to 105k.** `5-rewrite-decks.sh --deck=<id> --verbose` to audit the translations, then `--deck=<id> --apply`. Open it in the browser. It saves `Gamestate.txt.precanary` first, so reverting is one `mv`. | the deck renders: leader, base, main deck and **sideboard**, art loads. Then `rm` the `.precanary` before step 21 — a stray one is confusing later, and `9-rollback.sh` does not know about it. |
| 21 | **`5-rewrite-decks.sh --apply`** (**~40 min** measured: 26 min apply + ~10 min idempotence re-check) | second dry run reports **0 files changing**; no `.migtmp` left; file count matches step 10. ⚠ **crosses rung 3** |
| 22 | **`6-verify.sh --db=$DB --backup-dir=$BACKUP`**, then `3-capture-egress.sh after` + `diff`, then part B's manual list | all green |
| 23 | **`7-restore.sh --db=$DB --backup-dir=$BACKUP`** — refuses unless `6-verify.sh` passes | a stats POST is no longer 503; the flag file is gone |
| 24 | **`8-watch.sh --db=$DB`** for the first hour | submissions keep arriving; unresolvable count stays **0**; no error spike |

---

## B. Verification

Run all of it. Grouped by what it protects.

**Automated — these either pass or they don't**

- [ ] `DevTools/tdd-regression/test_swu_stats_ingress.php` — ingress translates, drops at the right granularity
- [ ] `DevTools/tdd-regression/test_swu_maintenance_guards.php` — every writer is behind the gate
- [ ] `DevTools/tdd-regression/test_swudeck_setnnn_dictionary.php` — no SET_NNN collision, tokens in dictionary and **not** in the catalog
- [ ] `DevTools/tdd-regression/test_swudeck_art_paths_resolve.php` — every image path resolves to a real file
- [ ] `DevTools/tdd-regression/test_swu_card_art_corpus.php` — canonical canvas; no per-app art tree reappeared
- [ ] Round-trip: `UUIDLookup(CardIDLookup($uuid)) === $uuid` for every card (was 2338/2338, 0 mismatches)

**The contract — this is the promise to Karabast**

- [ ] `diff egress-before/loaddeck-default.json egress-after/…` → **empty**
- [ ] `diff egress-before/cardmetastats.json egress-after/…` → **empty**
- [ ] `LoadDeck` with `setId=true` differs only where a reprint folded
- [ ] One real submission in Karabast's exact UUID payload shape writes `SET_NNN` rows and returns the same HTTP response as before
- [ ] A submission carrying `opposingBaseColor` and no `opposingBase` still writes the colour bucket **verbatim**

**The data**

- [ ] Counter totals unchanged per table — asserted inside `02_rekey_stats.sql`, which aborts on mismatch
- [ ] Row counts DROPPED (rows merged, class-3 not selected) — a drop is correct, an increase is not
- [ ] Palpatine: `ad86d54e97` and `0026166404` collapsed into one `TWI_017` row with summed counters
- [ ] Base colours survive byte-identical and are absent from the class-3 list
- [ ] A Shield token submission writes a row keyed `SOR_T02`

**The user-facing surface** — browser, and per the repo rule check Chromium, Firefox **and** Safari

- [ ] A legacy deck opens, renders, exports both ways, re-saves — **and the sideboard survives** (the Leader2 regression)
- [ ] Import a deck from a SWUDB link; import from JSON
- [ ] An HMW/preview deck imports, is legal in `open`, illegal in `premier`/`eternal`
- [ ] Deck browse panes populate; **no token appears in deck search**
- [ ] A meta page and a card-stats page render, art included
- [ ] Tournament pages resolve leader/base art
- [ ] `/card` in Discord returns an image; a legacy Discord embed URL still 200s

**Sanity**

- [ ] `find $ROOT/SWUDeck/Games -name Gamestate.txt | wc -l` matches the pre-migration count
- [ ] No `*.migtmp` anywhere under `Games/`
- [ ] Every `*_old` table still present (that is your rollback)

---

## C. Worst case — rollback

`9-rollback.sh --db=$DB --backup-dir=$BACKUP` is dry-run by default and **detects
state**: it inspects each table and rolls back only what actually swapped. That matters most in the
ugly case — a `03_swap.sql` that aborted midway leaves some tables swapped and some not, and
blindly renaming everything would push the untouched ones *forward* into a broken state.

**Find your rung, then act.**

| you are… | do | cost | lost |
|---|---|---|---|
| **before step 12** (nothing swapped) | maintenance OFF, redeploy previous commit | seconds | nothing |
| **after 12, before 15** | `rollback…sh --db-only --apply` | seconds | nothing |
| **after 15** (decks rewritten) | `rollback…sh --apply` — decks first, then DB | minutes | nothing |
| **service restored, problem found later** | maintenance ON first, *then* `rollback…sh --apply` | minutes | stats submitted since restore |
| **after `*_old` dropped** (§2.10) | restore the mysqldump + the deck archive | hours | everything since the backup |

**Order is not optional.** Deck files first, then the database — the reverse leaves SET_NNN deck
files being read against UUID-keyed tables. And **redeploy the previous commit BEFORE turning
maintenance off**, or new code runs against restored old-shape data.

```bash
# See exactly what it would undo. Changes nothing.
./AppCore/SWU/migrations/9-rollback.sh --db="$DB" --backup-dir="$BACKUP"

# Do it.
./AppCore/SWU/migrations/9-rollback.sh --db="$DB" --backup-dir="$BACKUP" --apply
```

It never drops anything: the migrated copy is renamed aside (`_new`, or `_rolledback_HHMMSS`), and
the deck archive extracts *over* the tree so `DeckImage.jpg` survives. Verified against a real
migrated database — original UUIDs came back exactly, including the class-3 row the migration had
dropped and both Palpatine identities un-merged.

**If rollback itself fails**, stop touching it. Every `*_old` table and both backups are still
there; a hand-run `RENAME TABLE x TO x_broken, x_old TO x;` per table is the whole recovery.

**The point of no return is §2.10**, when `*_old` is dropped. Do not do that on deploy day. Wait a
full week of clean logs.

---


## What belongs here

| goes in | scope | examples |
|---|---|---|
| `AppCore/SWU/migrations/` (here) | SWU-specific data and schema | the `SET_NNN` re-key, the `format` column widening |
| `Database/migrations/` | cross-app engine, account, shared tables | `11_discord_oauth_users.sql` (shared `users`), `09_match_history.sql` |

**Ordering is within a directory, not across them.** Cross-directory dependencies are stated
explicitly. There is one today:

> The `SET_NNN` re-key **requires `Database/migrations/01–03`** to be applied first. `01` converted
> `completedgame.format` (int → varchar, backfilled `premier`); `02` and `03` added `format` to the
> deck-stat and meta-stat **primary keys**. Every `create → insert → swap` here must reproduce the
> post-`03` key shape.

⚠ **Prefer plain `.sql`.** LAMPP's CLI PHP has **no `mysqli`** — `GetLocalMySQLConnection()` fatals
outside the Apache SAPI. Anything here needing a database connection is either a `.sql` file applied
with the `mysql` client, or is invoked through a `zz` page. PHP belongs here only for what SQL
cannot do: the **deck-file rewrite** (filesystem, ~102,825 decks) and **materialising the id map**
into a `.sql` file ahead of the window.

## Index

**Scripts are numbered in the order you run them.** Every one is dry-run/read-only by default.

| # | script | what it does |
|---|---|---|
| 0 | `0-env.sh` (**source it**) | Sets/validates all paths, credentials and `$DB`. Everything else assumes it ran. |
| 1 | `1-maintenance.sh on\|off\|status` | Apache-level block via root `.htaccess`. No LAMPP restart — `.htaccess` is read per request, and stopping LAMPP would take MySQL down with it. |
| 2 | `2-snapshot.sh` | Archives the deck tree: a slim gamestates-only tarball (~72 MB) and the full one. Verifies both. |
| 3 | `3-capture-egress.sh before\|after\|diff` | The Karabast contract check. **`before` MUST run while the tables are still UUID-keyed** — the baseline cannot be reconstructed later. |
| 4 | `4-migrate.sh` | Backs up the database, then applies `01`→`02`→`03`. Dry run by default. |
| 5 | `5-rewrite-decks.sh` | Deck files → SET_NNN (~28 min at 105k). Enforces the post-checks: idempotent second pass, no `.migtmp`, file count unchanged. |
| 6 | `6-verify.sh` | Runs every automatable check from part B. Prints what remains manual. |
| 7 | `7-restore.sh` | Lifts BOTH maintenance layers, in order, **gated on 6-verify passing**. This is the last moment rollback is cheap. |
| 8 | `8-watch.sh` | The first hour: submission rate, unresolvable-identifier count, error log. The window where a problem still costs minutes. |
| 9 | `9-rollback.sh` | Emergency. Detects state per table and undoes only what actually swapped. |

Supporting pieces, not run directly in sequence:

| file | role |
|---|---|
| `lib/IdentifierMap.php` | Migration-side map builder. Delegates to `AppCore/SWU/CardIdentity.php`, which is **permanent runtime code** — stats ingress depends on it, so deleting this directory after the window must not break it. |
| `tools/materialize-id-map.php` | Emits `01_id_map.sql` from the dictionaries. No DB access, so LAMPP's mysqli-less CLI is fine. Refuses to emit a degraded map. |
| `tools/build-rekey-sql.php` | Emits `02`/`03` from the LIVE schema. Needs mysqli — run it on a dev box and copy the output over. |
| `tools/rewrite-deck-files.php` | The deck rewriter itself; `5-rewrite-decks.sh` wraps it. |
| `01`–`03.sql` | GENERATED per box. Not committed. |

`01`–`03` are generated artefacts and are **not** committed: they encode the schema and dictionary of
one particular box at one moment. Generate them on the box you are migrating, during §1.

---

# Runbook

## 0. Environment

**Source `0-env.sh` — do not hand-roll this.** It sets every variable the numbered scripts read,
validates each one, and fails NOW rather than halfway through a migration.

```bash
cd $ROOT/AppCore/SWU/migrations
. ./0-env.sh
```

It sets `LAMPP ROOT PHP_BIN MYSQL_BIN MYSQLDUMP_BIN BACKUP MYCNF DB`, exporting `MYSQL`/`MYSQLDUMP`
as aliases so older prose still works.

Why it exists rather than a block of `export` lines:

- **The binaries are not on PATH.** LAMPP keeps `mysql` and `php` under `/opt/lampp/bin`, so a bare
  `mysql` is *command not found*. An earlier version of this runbook exported `$MYSQL` while the
  scripts read `$MYSQL_BIN` — following it exactly still produced a failure at step 4.
- **`$DB` is resolved, not assumed.** `ConnectionManager` defaults to `swuonline`; the clone is
  `swudeck`. It asks `information_schema` which schema actually holds `carddeckstats`.
- **The password is written once**, to a 0600 defaults file. Never `-p` on a command line — these
  scripts run in loops and under `time`, and an inline password is visible in `ps` to every user on
  the box. Delete that file in §2.10.
- **It refuses a backup dir inside `$ROOT`**, which would be publicly downloadable.

Override anything it guesses:

```bash
. ./0-env.sh --lampp=/opt/lampp --root=/opt/lampp/htdocs/TCGEngine --backup=/var/backups/swustats-manual
```

**Never put credentials in this file or any tracked file**, and never read them out of `APIKeys/`.

## 1. Before the window

Reversible; do it days early. **None of this belongs inside the maintenance window.**

- [ ] Ship the shared card universe — see
      `docs/superpowers/specs/2026-08-04-swu-shared-card-universe-design.md`. Card identity in
      *code* is `SET_NNN` before the window opens.

- [ ] **Deploy the SAFE half of the migration code.** The deploy splits in two, and getting this
      backwards either breaks §2.1 or corrupts data before the migration even starts.

      **Deploy NOW** — inert, or needed before the window can begin:
      | file | why it is safe early |
      |---|---|
      | `zzMaintenanceMode.php`, `AppCore/SWU/Maintenance.php`, the write gates | ⚠ **§2.1 cannot run until this is on prod** — you cannot enable maintenance mode with code that is not deployed. Inert until toggled. |
      | `AppCore/SWU/migrations/**` | Never executes unless invoked. |
      | `zzCardIdentifierCensus.php`, `AppCore/SWU/CardIdentity.php` | Read-only / pure library. |
      | `Stats/CardMetaStatsAPI.php` (egress) | Verified identical pre-migration: `ToWire('2579145458')` returns it unchanged, and maps correctly after. Works in both worlds. |
      | `zzCardCodeGenerator.php` (token inclusion) | Takes effect only when you regenerate, which is the next item. |

      **HOLD until §2.5, inside the window** — these assume SET_NNN-keyed tables:
      | file | what happens if it lands early |
      |---|---|
      | `AppCore/SWU/StatsIngress.php` + its `APIs/SubmitGameResult.php` wiring | Every new submission writes SET_NNN rows into UUID-keyed tables. That is the fragmentation this migration exists to remove, arriving early. |
      | `Core/StatsBaseRegistry.php` | `NormalizeBaseID('0119018087')` now returns `'LOF_020'`, and `SaveDeckStats` writes that value — so every new meta row splits from its history until the migration catches up. |

      If splitting a deploy is impractical, deploy **everything** at §2.5 instead — but then
      maintenance mode is unavailable for §2.1, so bring the site down another way first.

- [ ] **Regenerate card dictionaries on both boxes.**
      ⚠ `zzCardCodeGenerator.php` calls `CheckLoggedInUserMod()` *outside* its CLI guard
      (line 29), so **CLI fails on prod**. Use the web UI, logged in as a mod:
      ```
      https://swustats.net/TCGEngine/zzCardCodeGenerator.php?rootName=SWUDeck
      https://petranaki.net/TCGEngine/zzCardCodeGenerator.php?rootName=SWUSim
      ```
      The engine generator *does* guard its auth check (line 25), so that one is CLI-safe:
      ```bash
      cd $ROOT && $LAMPP/bin/php zzGameCodeGenerator.php rootName=SWUDeck
      ```

- [ ] **Census against prod** (read-only, mod login):
      ```
      https://swustats.net/TCGEngine/zzCardIdentifierCensus.php?showUnmapped=40
      ```
      Paste the output into `census-prod-<date>.txt` beside this file. The allowlist is only as
      current as its last run — **re-run it immediately before the window regardless.**

- [ ] **Dry run everything against the clone**, and record the timings in §4 (Sizing reference). Both are dry-run by
      default and write nothing; the elapsed times are what size the window:
      ```bash
      cd $ROOT
      time ./AppCore/SWU/migrations/4-migrate.sh --db="$DB" --backup-dir="$BACKUP"
      time $LAMPP/bin/php AppCore/SWU/migrations/tools/rewrite-deck-files.php
      ```
      Both exit non-zero if anything is unresolvable. Do not proceed past a non-zero exit.

- [ ] **Capture the egress baseline.** `LoadDeck` (default `setId=false`) and `CardMetaStatsAPI`
      must return byte-identical output after the migration — that is the whole promise to Karabast
      and to anyone reading `cardUid`. The "before" side cannot be reconstructed afterwards:
      ```bash
      mkdir -p "$BACKUP/egress-before"
      export DECK=12345      # ← a real deckID that predates the migration
      curl -s "https://swustats.net/TCGEngine/APIs/LoadDeck.php?deckID=$DECK&format=json" \
        > "$BACKUP/egress-before/loaddeck-default.json"
      curl -s "https://swustats.net/TCGEngine/APIs/LoadDeck.php?deckID=$DECK&format=json&setId=true" \
        > "$BACKUP/egress-before/loaddeck-setid.json"
      curl -s "https://swustats.net/TCGEngine/Stats/CardMetaStatsAPI.php" \
        > "$BACKUP/egress-before/cardmetastats.json"
      wc -c "$BACKUP/egress-before/"*      # none may be empty
      ```
      Re-run the identical commands into `egress-after/` in §2.8 and `diff` them. The DEFAULT
      LoadDeck response and `cardUid` must be **identical**; only `setId=true` may differ, and only
      where a reprint folded.

- [ ] **Rehearse the rollback on the clone** — rename the `_old` tables back, restore the deck
      archive, confirm the site works. An unexercised rollback is not a rollback.

- [ ] **Confirm disk space.** Needs **~8 GB free**, and the dominant cost is not the backups —
      it is the migration itself. Measured against prod 2026-08-06:

      | item | size | notes |
      |---|---|---|
      | database dump, gzipped | ~1 GB | from 2.9 GB of tables |
      | both deck archives | ~0.2 GB | 105k gamestates compress to 72 MB; only 282 decks have an image (60 MB total) |
      | `*_new` tables during `02` | **~3 GB** | the real cost — and it PERSISTS as `*_old` until §2.10 |
      | shared art corpus | ~0.6 GB | downloaded at the art step, not a backup |

      `0-env.sh` checks this and fails if short. Everything is typically on one filesystem, so the
      backups compete with MySQL's own working space — running out mid-`INSERT` on a 2.6M-row table
      is an ugly failure. `2-snapshot.sh --slim-only` exists if disk is desperate, but at 0.2 GB for
      both archives there is no reason to use it.

**Cutover gate:** class 3 must be empty. Class 1 (already `SET_NNN`) and class 2 (base colours,
sentinels) are expected and do **not** block. A non-empty class 3 stops the cutover.

## 2. The window

Each step gates the next. Do not proceed past a failed check.

### 2.1 Maintenance mode

Open **`zzMaintenanceMode.php?rootName=SWUDeck`** and select **"Stats writes paused"**. Mod login;
no shell, no `.htaccess`.

```
https://swustats.net/TCGEngine/zzMaintenanceMode.php?rootName=SWUDeck
```

**Why writes and not the whole site.** Nothing here needs reads stopped: under `ROW` binlog +
`REPEATABLE-READ` the aggregating rebuild takes a consistent snapshot and no shared locks, so users
browsing meta and deck pages neither block the migration nor are blocked by it. What *must* stop is
writes — the rebuild reads a snapshot and then `RENAME`s the original away, so any submission
between the build and the swap lands in the table that becomes `_old` and is **silently discarded**.

Leaving reads up also means the site looks alive rather than dead, and it avoids the previous
`.htaccess` approach entirely: that depended on `AllowOverride`, could 500 the whole site if a
directive was unsupported (`mod_headers` is not always loaded), and could lock you out of the `zz`
tools you need — which have to stay reachable, because CLI PHP has no `mysqli`.

Before trusting it, confirm nothing writes around it:

```bash
$LAMPP/bin/php $ROOT/DevTools/tdd-regression/test_swu_maintenance_guards.php   # expect PASS
```

That test re-derives the writer list from the source on every run — SQL writers *and* deck-file
writers — so an endpoint added since this was written is caught rather than silently slipping
through. A failure lists the ungated file; either gate it or add it to the test's allow-list with a
reason.

Verify it took, from off the box:

```bash
# Stats writes -> 503 with a Retry-After. This is what Karabast will see.
curl -s -o /dev/null -w "%{http_code}\n" -X POST -d '{}' \
  https://swustats.net/TCGEngine/APIs/SubmitManualGameResult.php     # expect 503

# Reads keep working.
curl -s -o /dev/null -w "%{http_code}\n" https://swustats.net/TCGEngine/Stats/DeckMetaStats.php  # expect 200
```

⚠ **Karabast loses any game it does not retry.** A 503 + `Retry-After` is the correct signal and a
well-behaved client will retry, but we do not control their retry policy. Window length has a real
data cost, not just an inconvenience one — which is why §2.6's dry run measures it first.

Before §2.7 (the deck-file rewrite) switch the same page to **"Full write freeze"**, which also
blocks deck saves. That step needs it: autosave-on-open racing a format change is what caused the
Leader2 sideboard data loss.

Restoring service is the same page, **"Turn maintenance OFF"** (§2.9). The state is a file, so if
the page is ever unreachable, `rm $ROOT/SWUDeck/maintenance.json` — its absence means "off".

### 2.2 Back up — everything after this depends on it

```bash
$MYSQLDUMP $MYCNF --single-transaction --routines --triggers "$DB" \
  | gzip > "$BACKUP/$DB.sql.gz"

tar -czf "$BACKUP/swudeck-games.tar.gz" -C "$ROOT/SWUDeck" Games
```

**Verify both — a dump that silently failed is still a file:**

```bash
ls -lh "$BACKUP"
gunzip -t "$BACKUP/$DB.sql.gz" && echo "dump gzip OK"
zgrep -c 'CREATE TABLE `carddeckstats`' "$BACKUP/$DB.sql.gz"     # expect 1
zgrep -c 'CREATE TABLE `completedgame`' "$BACKUP/$DB.sql.gz"     # expect 1
tar -tzf "$BACKUP/swudeck-games.tar.gz" | grep -c 'Gamestate.txt$'
find "$ROOT/SWUDeck/Games" -name Gamestate.txt | wc -l           # must match the line above
```

### 2.3 Re-run the census, confirm the gate

```
https://swustats.net/TCGEngine/zzCardIdentifierCensus.php?showUnmapped=40
```

Class 3 must be empty. If it is not: **stop**, restore nothing (you have changed nothing), lift
maintenance, and investigate.

### 2.4 Capture the before-totals

Row counts will legitimately **drop** (reprints merge). Counter **sums must not change** — this is
the only check that catches a `SUM`-less migration, which fails silently.

Build the SUM list per table rather than hand-typing columns — and **sum only the counters**.
`COLUMN_KEY <> 'PRI'` is load-bearing: `deckID`, `source`, `version` and `week` are int columns *in
the primary key*, and merging two rows into one legitimately changes their sums. Including them
makes every table look like a failure.

```bash
totals() {   # usage: totals <outfile>
  for T in carddeckstats cardmetastats deckmetastats deckmetamatchupstats \
           opponentdeckstats opponentnamedbasestats; do
    COLS=$($MYSQL $MYCNF -N -e "
      SELECT GROUP_CONCAT(CONCAT('SUM(\`',COLUMN_NAME,'\`)') SEPARATOR ', ')
        FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME='$T'
         AND DATA_TYPE='int' AND COLUMN_KEY <> 'PRI';")
    [ -z "$COLS" ] || [ "$COLS" = "NULL" ] && { echo "== $T  NO COUNTER COLUMNS — CHECK THIS"; continue; }
    echo "== $T"
    $MYSQL $MYCNF -N -e "SELECT COUNT(*), $COLS FROM \`$DB\`.\`$T\`;"
  done | tee "$1"
}
totals "$BACKUP/totals-before.txt"
```

### 2.5 Deploy the code

Ingress/egress translation, `Core/StatsBaseRegistry.php`, remaining app changes. Then **re-run the
engine generator** — generated files are gitignored, so a deploy alone leaves them stale, and stale
here means a blank board:

```bash
cd $ROOT && git log --oneline -1 && $LAMPP/bin/php zzGameCodeGenerator.php rootName=SWUDeck
```

### 2.5a Build the shared art corpus, then retire the per-app trees

Card art moved to `AppCore/SWU/Images/{WebpImages,concat,crops}` keyed by SET_NNN. **Both boxes** need
the corpus; neither reads its own old tree any more. The corpus is built by the card generator, which
only downloads art on the API-fetch path — `overwriteImages=1` alone regenerates nothing:

```bash
# On EACH box. ~30 min per box; it re-downloads and re-canvases every card.
cd $ROOT && $LAMPP/bin/php zzCardCodeGenerator.php rootName=SWUDeck withPreview=1 overwriteImages=1
# petranaki:  ... rootName=SWUSim  withPreview=1 overwriteImages=1
```

Gate on the corpus test before touching the old trees — it asserts every file sits on its
deterministic canvas (628x450 for Base/Leader, 450x628 otherwise):

```bash
$LAMPP/bin/php $ROOT/DevTools/tdd-regression/test_swu_card_art_corpus.php       # expect PASS
$LAMPP/bin/php $ROOT/DevTools/tdd-regression/test_swudeck_art_paths_resolve.php # expect PASS
```

Confirm nothing still reads the old trees, then **move, do not delete** — deletion is the following
weekend's job, after a week of clean logs:

```bash
cd $ROOT && git grep -nE "SWUDeck/(concat|crops|WebpImages)|SWUSim/(concat|crops|WebpImages)" \
  -- '*.php' '*.js' | grep -v '^\.gitignore' | grep -v 'AppCore/SWU/migrations/'
# Expect ONLY comment lines. Any live path here means STOP and convert it first.

mkdir -p "$BACKUP/old-art"
for D in SWUDeck/concat SWUDeck/crops SWUSim/concat SWUSim/crops SWUSim/WebpImages; do
  [ -d "$ROOT/$D" ] && mv "$ROOT/$D" "$BACKUP/old-art/$(echo $D | tr '/' '-')"
done
```

`SWUDeck/WebpImages/` **must survive as a directory** — it now holds the Discord legacy-URL shim
(`index.php` + `.htaccess`). Move only the art out of it, then prove the shim works. Discord embed
history is pinned to that URL shape permanently, so this is the one path that can never be retired:

```bash
mkdir -p "$BACKUP/old-art/SWUDeck-WebpImages"
find "$ROOT/SWUDeck/WebpImages" -maxdepth 1 -name '*.webp' -exec mv {} "$BACKUP/old-art/SWUDeck-WebpImages/" \;
ls -A "$ROOT/SWUDeck/WebpImages"    # expect exactly: .htaccess  index.php

# The shim only fires on a MISSING file, so it is untestable until the art above is moved.
curl -s -o /dev/null -w "%{http_code} %{content_type} %{size_download}\n" \
  "https://swustats.net/TCGEngine/SWUDeck/WebpImages/2579145458.webp"   # expect: 200 image/webp ~40000
curl -s -o /dev/null -w "%{http_code}\n" \
  "https://swustats.net/TCGEngine/SWUDeck/WebpImages/9999999999.webp"   # expect: 404
```

If the shim returns 404 for the first URL, Apache is not honouring the directory's `.htaccess`.
Check `AllowOverride All` covers the docroot, and that `mod_rewrite` is loaded:

```bash
grep -rn "AllowOverride" $LAMPP/etc/httpd.conf $LAMPP/etc/extra/ 2>/dev/null | grep -v '^\s*#'
$LAMPP/bin/httpd -M 2>/dev/null | grep rewrite      # expect: rewrite_module
```

### 2.6 Run the migration

`4-migrate.sh` takes the backup and applies `01`–`03` in order. It is **dry run by
default**: it builds every `_new` table and runs every assertion against real data, then drops them
and reports the elapsed time. Nothing is swapped without `--apply`.

Generate the three SQL files first — they are not committed, because they encode one box's schema
and dictionary at one moment:

```bash
cd $ROOT
# 01: no database needed, so LAMPP's mysqli-less CLI PHP is fine.
$LAMPP/bin/php AppCore/SWU/migrations/tools/materialize-id-map.php --summary   # eyeball it
$LAMPP/bin/php AppCore/SWU/migrations/tools/materialize-id-map.php > AppCore/SWU/migrations/01_id_map.sql

# 02/03 need mysqli, which LAMPP's CLI does not have. Generate them on the DEV box against a
# schema-identical clone and copy them over, or invoke through a zz page.
php AppCore/SWU/migrations/tools/build-rekey-sql.php --out=AppCore/SWU/migrations
```

`materialize-id-map.php` refuses to emit anything if the map is degraded — an empty leader-unit
asset map, or fewer than 10 tokens. Both of those have happened, and both would have migrated
cleanly while silently dropping tens of thousands of rows.

Dry run, and **record the elapsed time** — that is what §4 sizes the window from:

```bash
time ./AppCore/SWU/migrations/4-migrate.sh --db="$DB" --backup-dir="$BACKUP"
```

Only when that is clean:

```bash
./AppCore/SWU/migrations/4-migrate.sh --db="$DB" --backup-dir="$BACKUP" --apply
```

Verification is **inside** the SQL, not a manual diff. `02_rekey_stats.sql` aborts via `SIGNAL` if
any table's counter totals change, or if the schema differs from the box the file was generated
against. Row counts legitimately DROP (rows merge, class-3 rows are not selected), which is why the
assertion compares counter SUMs rather than row counts.

⚠ Do not "fix" an assertion by rewriting it as `IF(cond,'ok',1/0)`. MySQL evaluates `1/0` in a
SELECT to NULL, so that style of check prints `NULL` and carries straight on — verified on this
stack, after an earlier version of this migration shipped exactly that bug.

Then diff the totals captured in §2.4 for an independent second opinion:

```bash
totals "$BACKUP/totals-after.txt"
diff "$BACKUP/totals-before.txt" "$BACKUP/totals-after.txt"
```

Spot-check the Palpatine merge:

```bash
$MYSQL $MYCNF -N -e "
  SELECT cardID, SUM(timesIncluded) FROM \`$DB\`.carddeckstats
   WHERE cardID IN ('TWI_017','ad86d54e97','0026166404') GROUP BY cardID;"
# expect: one TWI_017 row, and NO rows for the other two
```

### 2.7 Rewrite the deck files

Switch `zzMaintenanceMode.php` to **"Full write freeze"** first — this step needs deck saves stopped,
not just stats. Autosave-on-open racing a format change is what caused the Leader2 data loss.

Dry run first; it writes nothing and **exits non-zero if any identifier does not map**:

```bash
cd $ROOT
$LAMPP/bin/php AppCore/SWU/migrations/tools/rewrite-deck-files.php
```

Read the two report lines. `unmapped` must be **0** — a non-empty list means the dictionaries are
stale, so regenerate and re-run rather than overriding. To audit exactly what it would change:

```bash
$LAMPP/bin/php AppCore/SWU/migrations/tools/rewrite-deck-files.php --verbose --limit=200
```

Rehearse against a COPY before touching the live tree, and confirm the app can still read the
result — this is the check that actually matters:

```bash
cp -a "$ROOT/SWUDeck/Games" /tmp/GamesRehearsal
$LAMPP/bin/php AppCore/SWU/migrations/tools/rewrite-deck-files.php --games-dir=/tmp/GamesRehearsal --apply
# idempotent? a second pass must report 0 files changing
$LAMPP/bin/php AppCore/SWU/migrations/tools/rewrite-deck-files.php --games-dir=/tmp/GamesRehearsal
# structure preserved? line counts must be identical, every diff identifier-shaped
for f in /tmp/GamesRehearsal/*/Gamestate.txt; do
  o="$ROOT/SWUDeck/Games/$(basename $(dirname $f))/Gamestate.txt"
  [ -f "$o" ] || continue
  [ "$(tr -d '\r' < "$o" | wc -l)" = "$(tr -d '\r' < "$f" | wc -l)" ] || echo "LINECOUNT $f"
done
```

Then for real:

```bash
time $LAMPP/bin/php AppCore/SWU/migrations/tools/rewrite-deck-files.php --apply
$LAMPP/bin/php AppCore/SWU/migrations/tools/rewrite-deck-files.php   # must report 0 files changing
find "$ROOT/SWUDeck/Games" -name '*.migtmp' | wc -l                  # must be 0
find "$ROOT/SWUDeck/Games" -name Gamestate.txt | wc -l               # must match §2.2
```

⚠ **The tool is deliberately structure-agnostic** — it does not walk the zone layout. That tree
holds at least three zone layouts (26 / 27 / 30 zones) and two version-blob delimiter generations
(`<s0>` versus `<v0>`/`<v1>`), so a zone table built from today's `WriteGamestate()` mislabels older
files. It instead replaces only fields that are an exact id-map hit, which leaves counts, flags and
sort modes untouched and never upgrades a file's layout. Do not "improve" it into a positional
parser; that is the Leader2 failure mode.

Rollback for this step is a restore of the deck archive from §2.2:

```bash
rm -rf "$ROOT/SWUDeck/Games" && tar -xzf "$BACKUP/swudeck-games.tar.gz" -C "$ROOT/SWUDeck"
```

### 2.8 Smoke test

```bash
# A legacy deck still loads, in both id shapes. Use a deck that predates the migration.
export DECK=12345        # ← a real deckID
curl -s "https://swustats.net/TCGEngine/APIs/LoadDeck.php?deckID=$DECK&format=json" | head -c 400
curl -s "https://swustats.net/TCGEngine/APIs/LoadDeck.php?deckID=$DECK&format=json&setId=true" | head -c 400
```

Then, in a browser as the operator (maintenance still up):

- [ ] Import a deck from a SWUDB link; import a deck from JSON.
- [ ] Open a **legacy deck with a sideboard and a second leader** — render, export both ways, re-save,
      reopen. Confirm the sideboard survived. *(This is the Leader2 regression.)*
- [ ] Load a meta page and a card-stats page; confirm art renders and a UUID-keyed stats URL resolves.
- [ ] POST one synthetic `SubmitGameResult` in Karabast's exact UUID payload shape **against a test
      table**, never the live stats tables.

**Egress byte-identity** — the contract check. Compare against the baseline captured in §1:

```bash
mkdir -p "$BACKUP/egress-after"
curl -s "https://swustats.net/TCGEngine/APIs/LoadDeck.php?deckID=$DECK&format=json" \
  > "$BACKUP/egress-after/loaddeck-default.json"
curl -s "https://swustats.net/TCGEngine/Stats/CardMetaStatsAPI.php" \
  > "$BACKUP/egress-after/cardmetastats.json"
diff "$BACKUP/egress-before/loaddeck-default.json" "$BACKUP/egress-after/loaddeck-default.json"
diff "$BACKUP/egress-before/cardmetastats.json"    "$BACKUP/egress-after/cardmetastats.json"
```

Both diffs must be **empty**. A difference here means a consumer's contract just broke — most
likely `cardUid` emitting SET_NNN because the outbound mapping was lost.

Art-specific, since §2.5a removed the trees these pages used to read. The stats pages hold FFG UIDs
and no card dictionary, so they resolve art client-side via `SWUCardArtScript()` — a page that emits
no `swuCardArtUrl` is missing its include and every tile on it will 404:

```bash
for P in Stats/Decks.php Stats/DeckMetaStats.php Stats/MeleeTournamentAggregate.php; do
  printf "%-42s " "$P"
  curl -s "https://swustats.net/TCGEngine/$P" \
    | grep -c 'swuCardArtUrl\|AppCore/SWU/Images'
done   # every line must be > 0
```

Then walk each page's image URLs and confirm none 404 (`$PAGES` = the list above plus any meta page):

```bash
for P in $PAGES; do
  curl -s "https://swustats.net/TCGEngine/$P" \
    | grep -oE "src=['\"][^'\"]*\.(webp|png)['\"]" | sed -E "s/^src=['\"]//; s/['\"]$//" | sort -u \
    | while read -r U; do
        case "$U" in http*|data:*) continue ;; esac
        C=$(curl -s -o /dev/null -w "%{http_code}" "https://swustats.net${U#.}")
        [ "$C" = "200" ] || echo "  BROKEN $C $U"
      done
done
```

A handful of 404s whose stem is a bare 10-digit FFG UID are **pre-existing**, not migration damage:
they are stats rows referencing cards no longer in the dictionary, and they had no art in the old
tree either. Verify that claim before waving one through:

```bash
grep -c . /dev/null; ls "$BACKUP/old-art/SWUDeck-concat/<uuid>.webp"   # expect: No such file
```

Any 404 whose stem IS a SET_NNN is real — the corpus is incomplete; re-run §2.5a.

- [ ] `/discord` card lookup in Discord returns an image (this exercises `APIs/DiscordBot.php`,
      which now emits the corpus path rather than the legacy one).

### 2.9 Restore service

Turn maintenance **OFF** in `zzMaintenanceMode.php?rootName=SWUDeck`, then confirm writes flow:

```bash
curl -s -o /dev/null -w "%{http_code}\n" -X POST -d '{}' \
  https://swustats.net/TCGEngine/APIs/SubmitManualGameResult.php     # expect NOT 503
# Belt and braces — the flag file must be gone. Its absence IS "off".
ls "$ROOT/SWUDeck/maintenance.json" 2>&1    # expect: No such file

curl -s -o /dev/null -w "%{http_code}\n" https://swustats.net/TCGEngine/SharedUI/MainMenu.php  # expect 200
ls $LAMPP/logs/            # LAMPP names vary: error_log / php_error_log
tail -f $LAMPP/logs/error_log
```

Watch the error log and the submission rate for the first hour. Karabast is the largest consumer —
a spike in `SubmitGameResult` errors is the first sign something is wrong.

### 2.10 After the window

Keep `*_old` tables and both backups until the **following** weekend, then:

```bash
$MYSQL $MYCNF -N -e "
  SELECT CONCAT('DROP TABLE \`',TABLE_NAME,'\`;') FROM information_schema.TABLES
   WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME LIKE '%_old';"
```

Review that list by eye before running any of it.

**Destroy the credentials file** as soon as the window closes — do not leave it for the follow-up
week:

```bash
shred -u "$BACKUP/my.cnf" 2>/dev/null || rm -f "$BACKUP/my.cnf"
unset MYCNF
```

## 3. Rollback

### 3.0 Art corpus

Cheapest thing to undo, and independent of the DB rollback below — the old trees were **moved**, not
deleted, so putting them back is one loop. Do this only if you are also reverting the code; the new
code reads nothing but the corpus:

```bash
for D in SWUDeck-concat SWUDeck-crops SWUSim-concat SWUSim-crops SWUSim-WebpImages; do
  [ -d "$BACKUP/old-art/$D" ] && mv "$BACKUP/old-art/$D" "$ROOT/$(echo $D | sed 's/-/\//')"
done
mv "$BACKUP/old-art/SWUDeck-WebpImages/"*.webp "$ROOT/SWUDeck/WebpImages/" 2>/dev/null
```

Leave `SWUDeck/WebpImages/index.php` and `.htaccess` in place either way — the shim is inert while
the real files are present (the rewrite only fires on a missing file).


**Before §2.7 (deck files untouched):**

```bash
$MYSQL $MYCNF "$DB" -e "
  RENAME TABLE carddeckstats TO carddeckstats_failed, carddeckstats_old TO carddeckstats;"
# ... repeat per migrated table, then redeploy the previous commit:
export PREV=abc1234      # ← the sha deployed before step 2.5
cd $ROOT && git checkout "$PREV" && $LAMPP/bin/php zzGameCodeGenerator.php rootName=SWUDeck
```

**After §2.7:** the above, plus

```bash
rm -rf $ROOT/SWUDeck/Games.broken && mv $ROOT/SWUDeck/Games $ROOT/SWUDeck/Games.broken
tar -xzf "$BACKUP/swudeck-games.tar.gz" -C "$ROOT/SWUDeck"
find "$ROOT/SWUDeck/Games" -name Gamestate.txt | wc -l   # must match §2.2
```

**Last resort — full restore:**

```bash
gunzip -c "$BACKUP/$DB.sql.gz" | $MYSQL $MYCNF "$DB"
```

Rows whose identifier does not map are **left in `_old`**, never dropped in place, so nothing is
irrecoverable while the window is open.

## 4. Sizing reference

**Measured 2026-08-06** against a full clone of prod: 2.5 GB / 5.17M rows in the database, and the
real 105,067-file deck corpus. Not extrapolated.

| step | time | notes |
|---|---|---|
| clone the database (rehearsal only) | 253 s | not part of the window |
| `4-migrate.sh` **dry run** | **271 s** | includes the mysqldump; every assertion passed |
| `4-migrate.sh --apply` | **280 s** | the swap itself is instant; the aggregating INSERTs dominate |
| mysqldump alone | ~100 s | 2.5 GB of tables → **223 MB** gzipped |
| `5-rewrite-decks.sh --apply` | **1,563 s (26 min)** | analysis pass + write pass, 105,067 files, memory flat at 65 MB |
| ...plus its idempotence re-check | ~10 min | the wrapper runs a third pass and requires 0 files changing |
| `9-rollback.sh --db-only --apply` | **1 s** | `RENAME` does not scale with data size |

⚠ **`--apply` runs a full analysis pass BEFORE writing**, because "a non-empty unmapped list blocks
the cutover" is worthless if the files are already rewritten by the time that list prints. Both
passes together measured 1,563 s; the wrapper's idempotence re-check adds ~10 min. **Budget ~40
minutes for step 21.**

Earlier drafts said 28 and then 56 minutes. Both came from extrapolating `--limit` runs, where PHP
startup and building the 4,805-row map were amortised over too few files. The figure above is
measured over the real corpus — prefer it, and re-measure rather than extrapolate.

**Rollback is effectively free** — 1 second at full scale, because it is nine `RENAME`s. That is
the strongest argument for keeping every `*_old` table until the following weekend: the cheap path
stays open for a week, and it costs only ~3 GB of disk.

### Where the window actually goes

The migration itself is ~10 minutes of the total. The expensive steps are the two regenerations:

| | |
|---|---|
| card dictionaries (live API re-fetch) | ~30 min |
| art corpus, per box | ~30 min |
| deck rewrite | ~40 min |
| everything else | ~20 min |

**Move both regenerations before the window if you can.** They are additive and reversible — §1
already lists the dictionary regen as a pre-window task. Doing both early turns a ~2.5 hour window
into roughly **1.5 hours**, and neither one needs the site down.

## 5. Notes

- **It is an aggregation, not a rename.** The UUID → `SET_NNN` map is many-to-one (reprints fold via
  `CardIDOverride`), so two rows can collide on one new primary key. Every counter is `SUM`ed.
  26 keys merge across all columns.
- **Base columns are polymorphic.** `baseID` / `opponentBaseID` hold either a card id **or** a colour
  (`Green`, `Red`, `Blue`, `Yellow`, `colorless` — 258,367 rows). These are correct data. Never
  translate them, never drop them, never count them toward class 3.
- **Tokens are in scope.** Including `SET_T##` in SWUDeck's universe is what takes class 3 from
  57,422 rows to ~3,100 — it is what makes the gate passable, not a nice-to-have. Tokens must be
  excluded from deck search, validation and legality.
- **`ad86d54e97 → TWI_017`** merges Chancellor Palpatine's split stats. Sweep `LeaderUnitLegacyIDByCardID`
  for *every* unresolved hex-shaped identifier, not just this one.
- **Discarded junk rows are real games.** The `zzzzzzz###` / `abcdefgMTL` / sentinel rows hold valid
  turns, health, format and timestamps — only the hero identifier is junk. Dropping them was a
  deliberate call, not an oversight.
- Nothing here runs against prod without a backup taken in the same session.
