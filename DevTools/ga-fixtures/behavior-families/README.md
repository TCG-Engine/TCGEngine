# Grand Archive behavior families

`cases.php` is the Taunt/Recover card table. The other shared suites remain in
`helpers.php` and `Scenario.php`, with their existing card rows and callers in
`ga-fixtures/*.php`. `families.json` is their reviewed slug-to-family discovery
index; it contains no setup, actions, or expected snapshots. `suites.php` expands it into independent, named
scenarios using the existing builder. The integration runner discovers these
cases alongside saved fixtures, including the Recover boundary cases that have
no directory. Other games use their existing fixture path.

Run every family case:

```sh
php DevTools/create-prd-ability-fixtures.php --families
```

Run one through the normal integration entry point:

```sh
php DevTools/RunIntegrationTests.php --root=GrandArchiveSim --test=refreshing-charge-recovers-three-clamps-at-zero
```

Run harness checks:

```sh
php DevTools/tests/ga-behavior-families-test.php
```

## Adding cards

Add an explicitly reviewed row to `cases.php`. Taunt rows specify the card,
covered rules clause, and whether class bonus is enabled. Recover rows specify
the card, printed recovery amount, reserve payment, and priority window. Each
Recover row runs from 5 damage, 1 damage, and no damage. The expected remaining
damage is calculated from those declared values, never from game implementation
functions. Cards with extra costs, conditional recovery, or other resolution
branches need separate scenarios or a separate family.

Keep slugs stable when migrating existing cases. Verify the old fixtures first,
then run the family suite and remove their four expanded data files. Retain their
`meta.json` records for catalog/history consumers and mark `behaviorFamily: true`.
The semantic coverage audit and coverage lint read current family definitions,
including automatically expanded boundary cases. Existing handwritten
interaction and bug reproduction scenarios remain saved fixtures.

## Execution and tradeoff

The GA generator's existing setup path constructs a disposable game. Family runs
check explicit assertions after each action, enforce expected action rejection,
and delete the temporary game. They never record or derive an expected final
snapshot from the engine. A full generator run also runs these cases without
recreating the deleted files. `--update-snapshots` has no effect on family cases.

Taunt checks the refused champion target, the exact remaining legal target,
and advancement after choosing that target. Recover checks initial damage,
remaining damage, the resolved card in the graveyard, and an empty decision
queue. These replace incidental whole-game snapshot comparisons; unrelated
state changes are no longer regression assertions for these cases.

Saved-fixture debugging tools that directly read `initial_gamestate.txt` cannot
replay these cases. Use the integration runner with `--verbose`, or the family
command, to inspect setup and action logs. Snapshot-based bespoke tests remain
available when the whole recorded state is important.

## Shared keyword suites

The additional index covers 108 Floating Memory, 26 Ranged, 12 Retort,
5 Intercept, 5 Stealth, 3 True Sight, and 20 Spellshroud/Omnishroud cases.
All 255 family cases (including the original Taunt/Recover cases) execute through
the same runner. Some indexed Floating Memory rows previously had no saved
fixture; those now run through normal discovery too.

Existing helper calls are the parameter table: keep adding reviewed cards there,
then add their stable slugs to `families.json`. The generator refuses unindexed
shared cases and mismatched family names. Inventory tools get current definitions
through the read-only `--export-shared-families` generator option, which exits
before creating games or writing fixtures. Historical metadata is retained.

Floating Memory checks actual level-up, the emptied graveyard, and the payment
card in banish; its negative cases keep the card and material deck unchanged.
Combat suites retain their damage assertions. Stealth additionally checks the
exact legal target prompt and advancement after the champion is chosen.
Spellshroud checks rejected targets, unchanged protected objects, and successful
resolution on a legal decoy. Handwritten Disenchant assertions are incorporated
in the shared suite, including checks before resolution. Ominous Shadow is a
token, so destruction is checked by its disappearance rather than a graveyard entry.

The class-mismatch Spellshroud controls and other bespoke interactions remain
saved fixtures. Similar text alone is not sufficient for adding a card to a suite.

## Simple status and class-stat suites

A further 28 existing scenarios are compact helper calls: 13 Vigor, 10 Pride,
3 hand-played Hindered, and 2 class-stat cases. Each row retains its original
slug, deck, champion requirements, rules clause, and explicit expected result.
The shared helper owns setup, actions, and assertions; expectations are supplied
by the row, never inferred from the engine or card text. Both enabled and disabled
conditions remain independent tests with stable names.

Use `gaVigorCase` for a rested unit across its end phase, `gaPrideCase` for
attack obedience at a declared champion level, `gaHinderedCase` for a paid ally
entering rested, and `gaClassStatsCase` for explicit power/life expectations.
Use the materialized-Hindered suite below for materialization. Keep additional
triggered effects and multi-object interactions in bespoke scenarios. Add each reviewed slug to `families.json` as usual.

## On-enter recovery matrices

`gaEnterRecover` handles simple hand-played allies with class-gated on-enter
recovery. Supply the payment, rules clause, and a table keyed by stable case
slug. Each row declares class bonus, initial damage, expected remaining damage,
and an assertion label. Expected damage is explicit, not read from game logic.
Novice Healer retains its enabled/disabled cases and adds recovery from one and
zero damage. Every row also checks that the ally entered and the decision queue
is empty. Register every slug under `enter-recover` in `families.json`.
Cards with additional costs or interactive triggers need separate scenarios.

## Condition matrices, materialization, single stats, and Glimpse

`gaVigorMatrix` shares the card, deck, and coverage across condition rows. Each
row supplies champion requirements, expected status, and label; optional coverage
and clause overrides preserve historical metadata. Brash Defender, Sturdy Droid,
Unrelenting Warden, and Winbless Arbalest use these matrices. Rows still expand
into independently named tests, including class mismatch and level boundaries.

`gaSingleStatMatrix` does the same for either `power` or `life`, with explicit
expected values. Eight existing cases cover Determined Spearman, Allied
Warpriestess, Sword of Shadows, Inert Sword, and Lancelot. Two additional Lancelot
controls isolate class mismatch at level two and class bonus below level two.
These helpers are for static, single-object checks; interacting objects and
additional triggered effects stay in bespoke scenarios.

`gaMaterializedHindered` shares material-deck seeding and materialization timing
for Focusing Gem, Sigil of Budding Embers, and Ebbing Tide. It retains lineage and
element setup and checks both identity and rested status after entry. It does not
require an empty decision queue: Focusing Gem legitimately offers a fast action.

`gaEnterGlimpse` covers simple class-gated, hand-played allies. It builds the
rearrangement answer from card names and uses a controlled deck containing the
revealed cards plus a Fluffy Shopkeep sentinel. Each row explicitly declares top,
bottom, and expected final order. Every deck position, deck size, ally entry, and
completed resolution is checked. Owl Familiar covers split piles, reversing both
cards on top, and placing both on the bottom. The original fixture name and entry
assertion are retained. This suite does not cover follow-up draws or other effects.

Together these additions migrate 12 saved fixtures and add four boundary/order
cases, bringing the total to 239. Novice Healer's four on-enter Recover cases
remain in the shared matrix with the zero-damage boundaries described above.

## Gather, Advanced Imbue, Efficiency, and On Death

A further 16 saved cases use shared setup, bringing discovery to 255 cases:

- `gaEnterGatherCase`: Automated Gardener and Imperial Alchemist, each with
  class bonus enabled and disabled. Rows declare payment and final field count;
  the random Herb identity is not fixed by the test.
- `gaAdvancedImbueCase`: Angel Attendant and Angelic Vanguard, each with enough
  and insufficient advanced cards. Callers declare the revealed hand cards and
  payment count, then assert exact turn effects. Payment is not inferred from
  engine card data. Additional on-enter effects such as Nuriel's counters remain
  in their existing bespoke fixtures.
- `gaEfficiencyCase`: Summon Gale and Rapid Reload at level two, with class bonus
  enabled and disabled. A setup callback supplies the target or material deck;
  rows supply actual payment and retain each card's resolution and memory-count
  assertions. The helper does not derive expected discounts from the engine.
- `gaOnDeathCase`: Angel Attendant, Delivery Droid, Dreamy Unicorn, and Machined
  Monstrosity. Shared combat setup causes actual destruction; callers retain
  explicit graveyard checks and their draw, summoned token, rested status, or
  preparation-counter checks. Attacker and counter setup are explicit parameters.

These helpers return the scenario builder so card-specific assertions and coverage
remain next to each card. All original assertions and stable names are preserved.
Combined or interactive effects, revival, alternate death replacements, and
special payment restrictions remain bespoke. As with the other families, these
cases replace incidental whole-state snapshots with explicit behavior checks.
