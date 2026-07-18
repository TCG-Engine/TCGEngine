# Stats database migrations

Migrations 01–03 apply to the **SWUStats stats database** (local docker DB: `swudeck`; prod: the
SWUStats DB). Migrations 04–05 apply to the application database shared by AzukiSim and AzukiDeck.
They are **not** needed for a fresh install — `Database/database.sql` already contains the final
definitions.

`completedgame` and the deck-stat / meta-stat tables are **not** the shared cross-app `ownership`
table, so these run only against stats databases, not every app DB.

## Run order

Apply in numeric order:

| # | File | Adds `format` to | From |
|---|------|------------------|------|
| 01 | `01_completedgame_format.sql` | `completedgame` (int→varchar, backfill premier) | Phase 1 |
| 02 | `02_deckstats_format.sql` | `deckstats`, `carddeckstats`, `opponentdeckstats`, `opponentnamedbasestats` (PK) | Phase 2 |
| 03 | `03_metastats_format.sql` | `deckmetastats`, `cardmetastats`, `deckmetamatchupstats` (PK) | Phase 3 |
| 04 | `04_azuki_deck_card_stats.sql` | Creates isolated `azukicarddeckstats` | AzukiSim/AzukiDeck bridge |
| 05 | `05_azuki_card_event_stats.sql` | Adds draw, attack, and attack-target counters | Azuki card analytics |

The first three are **independent** of each other (disjoint tables) — the numbering is the phase order they
were designed and tested in, and is a safe, canonical sequence. There is no cross-file dependency.
Migrations 04–05 create and extend the independent Azuki card-stat aggregation table. They do not
alter the SWU stats tables.

## Notes

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
