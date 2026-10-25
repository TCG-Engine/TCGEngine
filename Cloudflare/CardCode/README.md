# Grand Archive hosted Card Code

Cloudflare Worker + D1 backend for the existing CardEditor/PHP/MCP remote connection.
This hosts authored abilities, prerequisites, listener zones, ability names, and implementation
flags. Card metadata and images continue to use the existing Grand Archive importer.
Only `GrandArchiveSim` is accepted. Generated PHP/JS stays in each developer checkout.

Deployment: https://tcgengine-grandarchive-card-code.fanofin-match-ingestion-worker.workers.dev

On October 19, 2026, 2,964 ability records covering 2,550 cards were migrated and checked
field-for-field against local MySQL. The snapshot revision was
`1baa11a707e1f4a81902b83751290af25118839c65fe77a567a264ec571b791f`.
Checkpoint 1 holds that initial snapshot. The local developer token expires January 17, 2027;
rotate it before then. The temporary migration maintainer token has been revoked.

### Card source corrections, October 19, 2026

Seven reviewed migrations now update 31 cards in the hosted workspace:

- `DevTools/card-code-migrations/20261019-destruction.json`: Disenchant, Ghastly Corrosion,
  Reduce to Ash, Crumbling Reign, Rapid Combustion, Expel the Departed, Converge Reflections,
  and Shatter the Brittle. Destruction uses the shared dispatcher; relevant target filters
  exclude missing-cost sentinel values. Converge Reflections reads effective subtypes.
- `DevTools/card-code-migrations/20261019-low-cost-targets.json`: Unstable Fractal, Alkahest,
  Swooping Talons, and Smashing Force use the same corrected cost filtering.
- `DevTools/card-code-migrations/20261019-choice-continuations.json`: Luminous Quartz
  reacquires its champion before removing preparation; Turm preserves its departing buff
  count across the target choice. Their three redundant override functions were removed.
- `DevTools/card-code-migrations/20261019-assassin-class-bonus.json`: Coup de Grace,
  Ignited Stab, and Thousand Refractions explicitly require an Assassin champion for their
  class-bonus riders. The three dedicated wrappers and their unused helper were removed;
  the separate preparation-cost workarounds remain.
- `DevTools/card-code-migrations/20261019-milling.json`: Pure Cytosynth, Overflow the Barrow,
  Icebound Slam, and Waterfall Veiler use `MillCards` instead of repeatedly moving deck slot
  zero. Their four override bodies were removed. Five focused fixtures pass, including both
  sides of Icebound Slam's water-card threshold; runtime inspection confirms generated bodies.

- `DevTools/card-code-migrations/20261019-deck-movement.json`: Paired Minds, Kindred Souls
  and Storm Tyrant's Eye move distinct deck cards using `MZMoveTopOfZone`; Dormant Sacrificial
  Altar uses `MillCards` after its sacrifices. Their three overrides were removed. All six
  focused fixtures pass, and runtime inspection confirms the generated handlers are active.

- `DevTools/card-code-migrations/20261019-optional-deck-and-helpers.json`: SignalTech X Ultra
  and Lucia preserve the looked-at count across their optional choice. Lena, Enhance Hearing,
  and Captain Archer move distinct top cards; Enhance Hearing uses effective element/subtypes,
  and Captain Archer uses the reserve-cost helper and puts remaining cards on the bottom.
  Restoring Embers calls `DrawIntoMemory`; Genuflecting Execution calls `DoAllyDestroyed`.
  Eleven redundant override closures were removed. Five entries in Grand Archive's existing
  decline-aware handler hook keep optional deck cleanup running on PASS; this runtime support
  is still required because the generator does not mark those continuations to run on decline.
  All 20 focused fixtures pass, including declines, no-candidate cases, influence thresholds,
  and both-player coverage. Runtime inspection confirms all 14 dispatch entries use generated
  source. A repeat migration preview reports zero pending cards.

From the repository root, preview with
`php DevTools/ApplyCardCodeMigration.php DevTools/card-code-migrations/<file>.json`;
add `--apply` to save. The runner requires a remote connection, validates all expected source
before writing, preserves other abilities and their metadata, and sends each card's loaded
revision. Re-running an applied migration makes no writes. A changed source or revision
conflict stops the run; reconcile before continuing. Cards already saved in a partial run
remain saved. The checked-in before/after sources also provide a reviewable reversal if needed.

The corresponding local overrides were removed. Older database copies need these source
updates and regeneration before using this checkout. Validation: 65 focused integration
fixtures passed, and runtime inspection confirmed the migrated entry points and eight
destruction continuations resolve to generated source rather than the override file.
The two choice-continuation fixtures also passed after regeneration and override removal;
their three dispatch entries were confirmed to use generated source.
All ten Assassin-card fixtures passed (paid, declined, insufficient preparation, and
non-Assassin cases); reflection through shared wrappers confirmed the corrected generated
bodies. These fixtures emitted an undefined `Intent::$Status` warning from unchanged
`GAParvatiRegaliaAbilityActive` logic. The generator also retains its earlier line-3944 warning.
The full regression suite was not completed for these batches.

The earlier recollection-phase, Perfect Repulsion, Hypothermia, and Cleansing Reunion
corrections were verified in Cloudflare. Red Slime's simultaneous-trigger ordering is already
handled by the engine and its regression passes; it needs no database migration.
Other fixes in `GrandArchiveSim/Custom/GeneratedAbilityOverrides.php` still need review and
migration; this batch does not retire that file or migrate its field-ability/cost wrappers.

## Operations

Run commands from this directory. Install with `npm ci`. `npm test` uses Node 22.13+ (SQLite)
and PHP to check revision compatibility, authorization, concurrency, backups, and restore.
`npx wrangler deploy --dry-run` validates the Worker bundle.

The database ID in `wrangler.jsonc` identifies the deployed database. Normal updates:

```sh
npx wrangler d1 migrations apply DB --remote
npx wrangler deploy
```

The API accepts both `Authorization: Bearer ...` (MCP) and `X-Card-Code-Token` (PHP).
It implements `card`, `cards`, `snapshot`, `save`, `ensure-cards`, `checkpoints`,
`checkpoint`, and `restore`. Saves require the last loaded `baseRevision`.
A 409 requires reloading and reconciling; never blindly retry a save.
The workspace version guard, pre-write checkpoint, and mutation share an atomic D1 batch.

Checkpoints are immutable per UTC date and skip unchanged content. A Worker cron runs at
00:00 UTC; mutations also checkpoint first. Snapshots are stored as individual ability rows
so a large workspace does not exceed D1's single-value size limit. Restore requires a
maintainer token. This backend currently assumes Grand Archive's identity card IDs; it does
not implement variant mapping for other games.

## Initial migration

On the source machine, export the **local** MySQL abilities. Set the normal MySQL environment
variables for that machine. Keep exports outside a publicly accessible directory.

```sh
php export-local.php > /tmp/grandarchive-card-code-export.json
mkdir -p local
node prepare-import.mjs /tmp/grandarchive-card-code-export.json local/import.sql
npx wrangler d1 execute DB --remote --file local/import.sql
```

Import refuses a nonempty destination. It preserves all authored fields, IDs, timestamps,
and empty implementation markers. Verify the remote snapshot's revision and record count
against the export before enabling the developer connection. Do not re-import after remote
editing begins. The local MySQL data remains available as the pre-migration copy.

## Developer connection

In Generator Workspace, select GrandArchiveSim and configure **Developer Card Code connection**:
use the deployed Worker URL, workspace `GrandArchiveSim`, and a developer token. Test and save.
The shared protected `DevTools/local/card-code-connections.php` then directs both PHP and MCP
to Cloudflare. Use the loopback PHP development server when Docker port forwarding prevents
the connection panel's strict-loopback check. Disconnecting restores local MySQL use but does
not synchronize subsequent cloud edits back to MySQL.

## Tokens

Cloudflare account administrators manage this backend's tokens with Wrangler. The existing
**Hosted Card Code access** panel administers the PHP/MySQL host, not this Worker.
Do not issue tokens there expecting them to authorize Cloudflare.

```sh
node prepare-token.mjs 'Developer name' developer 90 local/new-developer
npx wrangler d1 execute DB --remote --file local/new-developer.sql
```

The plaintext token is saved only in `local/new-developer.php` (0600, PHP download guard,
gitignored). Transfer it securely to the developer. Roles: reader (reads), developer (reads
and edits), maintainer (also checkpoint and restore). All tokens expire within 365 days.
To list tokens without secrets:

```sh
npx wrangler d1 execute DB --remote --command "SELECT token_name, role, expires_at, revoked FROM tokens"
```

To revoke, use a bound administrative operation or a correctly quoted SQL update matching
the intended token hash. Rotate by issuing a replacement, switching the developer connection,
and revoking the old hash. Cloudflare account permissions control these administrative writes.
No Cloudflare account credential is sent to a developer client.

Cloudflare references: [D1 transactional batches](https://developers.cloudflare.com/d1/worker-api/d1-database/)
and [imports](https://developers.cloudflare.com/d1/best-practices/import-export-data/).
