# Stats database migrations

Migrations 01–03 apply to the **SWUStats stats database** (local docker DB: `swudeck`; prod: the
SWUStats DB). Migrations 04–08 apply to the application database used by AzukiSim and AzukiDeck;
migration 09 adds the shared match-history capability for any simulator root. They are **not**
needed for a fresh install — `Database/database.sql` already contains the final definitions.

`completedgame` and the deck-stat / meta-stat tables are **not** the shared cross-app `ownership`
table, so these run only against stats databases, not every app DB.

Migration 11 is the exception to that: `users` is the shared **account** table, so 11 must be
applied to **every** app database that serves logins (swustats.net, petranaki.net, zendo.gg, …)
before Discord sign-in works on that box.

## Run order

Apply in numeric order:

| # | File | Adds `format` to | From |
|---|------|------------------|------|
| 01 | `01_completedgame_format.sql` | `completedgame` (int→varchar, backfill premier) | Phase 1 |
| 02 | `02_deckstats_format.sql` | `deckstats`, `carddeckstats`, `opponentdeckstats`, `opponentnamedbasestats` (PK) | Phase 2 |
| 03 | `03_metastats_format.sql` | `deckmetastats`, `cardmetastats`, `deckmetamatchupstats` (PK) | Phase 3 |
| 04 | `04_azuki_deck_card_stats.sql` | Creates isolated `azukicarddeckstats` | AzukiSim/AzukiDeck bridge |
| 05 | `05_azuki_card_event_stats.sql` | Adds draw, attack, and attack-target counters | Azuki card analytics |
| 06 | `06_azuki_card_play_turn_stats.sql` | Adds play/win counters for full-turn cycles 1-9 and 10+ | Azuki turn analytics |
| 07 | `07_auto_versioning.sql` | Adds a separate automatic-version graph and Azuki per-version W/L aggregates | Shared version graph + Azuki opt-in |
| 08 | `08_engine_asset_versioning.sql` | Adds engine-level version aggregates and backfills existing Azuki W/L rows | Engine capability extraction |
| 09 | `09_match_history.sql` | Creates private per-user match history with generic key-card slots | Shared simulator capability |
| 11 | `11_discord_oauth_users.sql` | Makes `users.usersPwd` nullable + `users.discordID` unique | Discord sign-in |
| 12 | `12_grand_archive_recollection_phase_fix.sql` | Fixes 4 `card_abilities` rows comparing a phase to the literal `"RECOLLECTION"` (never matches; real code is `"BREC"`) | GrandArchiveSim engine bug fix |

The first three are **independent** of each other (disjoint tables) — the numbering is the phase order they
were designed and tested in, and is a safe, canonical sequence. There is no cross-file dependency.
Migrations 04–06 create and extend the independent Azuki card-stat aggregation table. They do not
alter the SWU stats tables.

## Notes

- Migration 07 deliberately does not import manual snapshots embedded in AzukiDeck or SWUDeck
  gamestate files. It enables database-backed automatic versions for Azuki only; SWU keeps its
  existing manual workflow. The prior `assetversions` draft table remains untouched.
- Migration 08 preserves all `assetautoversions` rows and version IDs. It idempotently copies
  Azuki's existing aggregate rows into the shared `assetversionstats` table.
- Migration 09 is additive and idempotent. Rows are isolated by `rootName`; each simulator may use
  up to three key-card slots and leave the rest empty. The runtime also creates the table when match
  history is first recorded or viewed.
- Migration 12 is a **data-content** fix, not a schema change (no `ALTER TABLE`) -- it corrects
  `card_abilities.prereq_code` text for 4 rows in the GrandArchiveSim database only. Idempotent via
  `REPLACE()` + a `LIKE` guard; safe to re-run. After applying, regenerate GrandArchiveSim's engine
  code so the fix reaches `GeneratedMacroCode.php`: `php zzGameCodeGenerator.php rootName=GrandArchiveSim`.
- Migration 11 is idempotent and converges from any starting state (no `discordID` index, a
  non-unique one, or already-unique). It replaces the former standalone
  `Database/discord_oauth_migration.sql`, which lived outside this directory and so was invisible
  to the [update runbook](../../newhost/UPDATE-runbook.md)'s migration check — a box could take the
  Discord code without the schema and fail signup with `Column 'usersPwd' cannot be null`.
  **Pre-check for duplicates before applying** (the unique key aborts on them):
  `SELECT discordID, COUNT(*) c FROM users WHERE discordID IS NOT NULL AND discordID <> '' GROUP BY discordID HAVING c > 1;`

- **Expand-first / safe before the code push.** Migrations 01–03 backfill existing rows to `premier`
  (via a `DEFAULT 'premier'` column) and every reader defaults to premier, so the old code keeps
  working against the new schema. Run them shortly before (or with) the code deploy.
- **Locking table-copy rewrites.** The `DROP/ADD PRIMARY KEY` steps (02, 03) and the `int→varchar`
  retype (01) rebuild large tables and block writes for the duration — apply in a **low-traffic
  window**. Each took minutes locally against the prod-data copy.
- Apply e.g. with:
  `docker exec -i <mysql-container> mysql -u root -p<pw> <db> < Database/migrations/01_completedgame_format.sql`

## Apply log

Record where each has been applied (date / environment) as they roll out:

- `swudeck` local docker: 01, 02, 03 applied 2026-07-18.
- prod SWUStats DB: _pending_.
- `swusim` local docker: 11 applied 2026-08-03.
- petranaki.net (SWUSim): 11 _pending_ — this is what blocks Discord signup there.
- `grandarchivesim` local docker: 12 applied 2026-08-30.
- prod GrandArchiveSim DB: 12 _pending_.
