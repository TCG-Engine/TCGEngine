# Session Retro Log

Running log of process/development lessons surfaced at the end of each session via the
swudeck-session-close retro question. Newest entries at the bottom. This is for
session-to-session process lessons — durable codebase/feedback facts still go through the
memory system (see `MEMORY.md`), not just here.

## 2026-07-17 — SWUDeck format tracking + identity banner
- **Verify visual/CSS work in the user's actual browser engine, not just Chromium.** The
  base-inset and oversized-leader bugs only reproduced in Firefox/WebKit — `height:100%`
  falls back to natural aspect when the parent's height comes from flex-stretch (indefinite)
  rather than an explicit height. I insisted "it's fine" for several rounds off Chromium
  renders while the user (Firefox) saw a real bug. Playwright ships `firefox` and `webkit`;
  set up a 3-engine screenshot+measure harness FIRST for any cross-browser visual task.
- **Reproduce before blaming cache/environment.** I concluded "stale cache" twice because I
  couldn't reproduce in Chromium. That was wrong and reads as dismissive. If you can't
  reproduce, you're probably testing the wrong engine/state — go find it, don't hand-wave.
- **DOM rect measurements can lie; combine signals.** An image measured 96px in Chromium
  while its span was laid out anomalously. Cross-check `getBoundingClientRect` + computed
  styles + the real engine + an actual pixel/screenshot look before concluding.
- **Fix at the source, not with CSS band-aids.** The base zoom cycled through fragile
  `transform: scale/translate` hacks (one exposed a background gap) before the right fix: a
  dedicated Base crop branch in the image generator + a deterministic cover-resize. Reach
  for the generator/pipeline over runtime CSS trickery when the asset itself is wrong.
- **Test mutations on throwaway data, and check the real schema before improvising.** I
  briefly failed to find the `Drixx` test user (queried `userID` instead of `usersUid`) and
  spun up a throwaway account. Confirm the schema; exercise swap/remove flows on scratch
  decks, never the user's real ones.
- **Scale the harness to the problem early.** This banner work ran many more rounds than it
  should have. The multi-engine + pixel-analysis setup that finally cracked it would have
  short-circuited most of the loop if built at the first "looks wrong to me / looks fine to
  me" divergence.

## 2026-07-17 — SWUDeck leader swap + live validation (continuation)
- **A CSS class toggle isn't the same as a visible change — verify the computed property.**
  The validation badge's "click to expand" did nothing because the base rule
  `#swuDeckBoard #swuValidationIssues` (two IDs) out-specified `.is-open` (one ID + one
  class), so `display` stayed `none` even though the click DID add the class. I'd "verified"
  the expand earlier by checking only that the class flipped. Confirm the toggle with
  `getComputedStyle` + a real screenshot, and remember a plain `.state` selector loses to a
  two-ID base rule (the badge itself worked only because its display is an inline style).
- **Shared AppCore/SWU validators assume SET_NNN ids; SWUDeck is UUID-keyed — reprint/legality
  logic fails silently.** `SWUReprintGroup` inverts `CardIDOverride` over `$titleData`'s keys
  (SET_NNN in SWUSim, UUIDs in SWUDeck), so every card grouped only to itself and reprints
  were invisible → false "not legal in premier" for cards whose only legal printing is a
  reprint. Bridge the id scheme (publish a SET_NNN universe) whenever reusing those validators
  in SWUDeck. See [[swudeck-setnnn-vs-uuid-validation]].
- **Derived CSS must match the engine's ACTUAL computed size, not the theoretical formula.**
  Making the leader/base panes 3-per-row (like Cards) needed `var(--swu-deck-card-size) - 8px`
  because the CSS `100vw/13` var comes out ~5px wider than the engine's JS card size — enough
  to wrap to 2 per row. Measure the real target element, don't assume the formula matches.
- **Empirical sweep beats aspect-ratio math for visual tuning.** Base zoom (crop window), base
  opacity (mask solid%), and pane sizing all converged fast by injecting candidate values live
  + screenshotting (or Imagick cover-fit previews) and letting the user pick — far quicker than
  reasoning it out.
- **Keep cross-app/consumer extensions additive.** getDeckLeaderBase.php gained
  `leaderID2`/`leaderName2` (empty when absent, existing fields byte-identical); the
  EngineActionRunner source-mzid global and the `SWUReprintGroup` universe override are opt-in
  and leave other apps unchanged. Per the "public APIs are a contract" rule.

## 2026-07-18 — Per-format stats (3-phase: completedgame → deck stats → public meta)
- **Adding a dimension to consumer-read tables: pin EVERY existing reader to the old default, or
  they silently blend the new data.** Phase 3's load-bearing rule — once eternal/twinsuns meta rows
  exist, any reader without `format = 'premier'` starts merging them. Enforced at two independent
  points (4 public APIs default premier + 2 Discord bots hardcoded premier), plus a contract test
  that fails today (premier+eternal blend to 14 plays) and passes only once the default filter lands.
  Byte-identical default responses = additive `?format=` param defaulting to premier.
- **Expand-first migrations decouple the DB change from the code deploy.** Every phase added the
  column with `DEFAULT 'premier'` and backfilled, so old code (which omits the column on insert and
  reads without a format filter) keeps working against the new schema. This let the user run the
  migration shortly *before* the push with zero breakage — confirm the old-code/new-schema pairing
  is clean and say so explicitly.
- **Check whether the thing already exists before designing it.** The very first ask ("make submitgame
  accept a format param") was already implemented end-to-end (endpoint commit `147d68ba` + SWUSim
  StatsSubmit + forceteki's premier-only gate). Reading the actual consumer source
  (`../../SWU-Karabast-forceteki`) up front saved building a no-op. Verify current state first.
- **tdd-regression tests that POST to a local endpoint MUST run via CLI (`docker exec … php`), not
  over HTTP** — the apache-loopback stalls the worker pool on docker-for-mac (hangs past 120s though
  a single POST is ~0.03s). For big tables (prod-data copy, unindexed `WinningHero`) snapshot
  `MAX(GameID)` and only inspect rows past it. Also: just after a Write, the container mount can
  briefly serve a partial file (a `php -l` parse error that vanishes on retry) — `sleep 2-3` first.
  (Saved to memory: [[tdd-regression-loopback-cli]].)
- **Tab-indented multi-line mysqli binds: `cat -te` to see the real tabs, then match minimal unique
  anchors** (the bind type string, a full SQL substring) rather than whole whitespace-fragile blocks.
  Several block-level Edits failed on space-vs-tab until switching to per-line anchors.
- **NEVER re-add/overwrite `APIKeys.php`** (real/prod secrets); guard the missing key in test code
  with `isset(...) ? ... : ''` instead. (Saved to memory: [[never-readd-apikeys-php]].)

## 2026-07-18 — Prod deck-corruption incident (Leader1/Leader2 format break) + migration ops
_(Resolved — affected players re-entered sideboards; future reports handled by the user via DM. Lessons below stand as durable guidance.)_
- **A schema change to a positionally-serialized zone format is a BREAKING format change, and
  opening a deck AUTOSAVES — so a misread destroys data on open.** The Twin Suns commit inserted
  `Leader1`/`Leader2` browse-pool zones into `GameSchema.txt`; the generated `GamestateParser` reads
  zones positionally, so old (single-leader) `Gamestate.txt` files misalign after the pools and the
  sideboard is read as garbage. The deck editor autosaves on open (`WriteGamestate`), writing the
  misread back → sideboard gone. Read-only pages (DeckStats) don't autosave and are safe. Verified by
  reproduction: pools 1→3 + sideboard 5→0 purely from loading the editor. (Saved: [[swudeck-leader2-format-break]].)
- **Gitignored generated files don't deploy — the generation step must run on the server.** Prod's
  blank board (`#theirStuff` null) was a stale generated `InitialLayout.php` (gitignored). "Works
  local, broken prod, and it's generator-related" almost always = generated files out of sync with a
  deployed schema/generator change; re-run the generators (and clear PHP opcache) on the box.
- **Live-table backfill migrations RACE with concurrent inserts — set the DEFAULT in the widen step.**
  The 3-step `int→varchar` (widen NULL → backfill → NOT NULL) let ~7 games insert between the backfill
  and the lockdown as NULL, so the final `NOT NULL` ALTER hit `ERROR 1265`. Fix: put
  `DEFAULT 'premier'` on the FIRST (widen) statement so mid-migration inserts default correctly. Also
  seen: `ERROR 1206` = tiny `innodb_buffer_pool_size` (16 MB) can't hold the COPY-rebuild's locks —
  bump it; and `int→varchar` forces `ALGORITHM=COPY`.
- **A detector built on the CURRENT (broken) parser conflates "corrupted" with "intact-but-old".**
  `LoadDeck.php?…&setId=true` returns `sideboard id:null` for BOTH a genuinely-corrupted file AND an
  intact old-format file the parser can't read. Use a FORMAT-AWARE file-walk (reads old files
  correctly) to separate lost-data from still-migratable, then LoadDeck to confirm. Migrate-first also
  makes a plain LoadDeck scan precise.
- **Prod scale + Cloudflare = paginate or go CLI, but LAMPP's PHP CLI has no `mysqli`.** A full scan
  of ~102k decks blows Cloudflare's ~100s (524); page with `?offset/?limit`, or run a standalone CLI
  script on the box — which must avoid the DB (no `mysqli` in CLI) by enumerating `SWUDeck/Games/*`
  folders and using the pure-PHP card dictionary (`CardType`, no DB).
- **No backups + expand-first is a gamble — snapshot BEFORE running anything.** The user had no deck
  backups when the corruption hit; recovery now leans entirely on the in-DB `assetversions` snapshots.
  Take a `Games/` tar + DB dump before any migration/mass-op, even (especially) when you "didn't have
  a backup before."
## 2026-07-18 — Deck-image redesign + friendly links + newhost prod-provisioning ordeal
- **Reproduce the failure before theorizing — read the ACTUAL fatal first.** A prod "MainMenu 500"
  cost many round-trips because I hypothesized from `gd.so` startup *warnings* (noise). One
  `sudo tail -30 /opt/lampp/logs/error_log` named it in one line (`ActiveSite: MYSQL_DATABASE_NAME
  is not set`). Ask for the Apache error_log's `PHP Fatal error:` line up front; warnings ≠ the 500.
- **Deploying code ≠ provisioning a host — never run setup scripts as part of an update.** The whole
  swustats outage was `harden-htaccess.sh` (a *setup* script) run on a live box: it regenerates the
  docroot `.htaccess`, wiping the `SetEnv MYSQL_DATABASE_NAME` that lived there → site-wide 500. A code
  update is just back up → pull → `lampp restart` → verify. Wrote `newhost/UPDATE-runbook.md` to enforce.
- **A prod box runs TWO stacks; always use LAMPP's binaries.** LAMPP PHP 8.2 serves the site; the
  system PHP is 7.4 (composer failed on it: missing `ext-curl`), and system `mysqldump` points at the
  wrong socket. Use `/opt/lampp/bin/{php,mysql,mysqldump}`. Fixed `install-php-deps.sh` to run composer
  under LAMPP's PHP + `--ignore-platform-reqs` (CLI ext-set ≠ web SAPI).
- **One box = one site; env belongs in `httpd.conf`, not `.htaccess`.** `ActiveSite.php` maps the single
  `MYSQL_DATABASE_NAME` to a site, so conflicting/stale `httpd-*-env.conf` Includes silently serve the
  wrong app (swusim conf → "Petranaki Arena"). `harden-htaccess.sh` overwrites `.htaccess`, so env must
  live in `httpd.conf` (provision-app's job).
- **Provisioning scripts must be safe-by-default and fail loud BEFORE changing anything.** `provision-app.sh`
  had lethal footguns: DB step DROPped by default (only `--skip-db` opted out), DB_NAME defaulted to
  `swusim` (single-positional run → wrong DB/site), passwordless default. Hardened: flipped to env-only
  by default with `--reset-db` as the explicit destructive opt-in; `DB_PASS` now required; DB-name defaults
  to the app; added a connectivity preflight + a conflicting-env-conf guard.
- **CreateImage.php `require vendor/autoload.php` fatals when vendor/ is unprovisioned** — and vendor is
  gitignored, so a fresh box has none. That (not the clipboard-timing theory) was the primary "Failed to
  copy image!" cause; provisioning vendor (or removing the dead require) fixes it. Also: the endpoint
  refused private decks (`assetVisibility == 0`, and `NULL == 0` in PHP) — most decks are private, so most
  copies failed. Removed that gate per the owner.
- **Deck-image layout: full `WebpImages` beats the concat/jpg cache; fixed width = uniform cards.** The
  jpg cache (`SWUDeck/jpg/*`) was never generated locally; the full-card `WebpImages/` cache was present.
  Switching to it + a fixed-column grid (no arenas) made shareable images uniform. Iterated screenshot-first.
- **Karabast import needs `?gameName=` in the URL; make LoadDeck accept the code additively.** forceteki's
  regex extracts `gameName=` and forwards it to `LoadDeck.php?deckID=`, so LoadDeck resolving a 12-letter
  code (numeric stays byte-identical) means friendly links import with zero Karabast changes.
