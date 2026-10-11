# GrandArchiveSim semantic fixture contracts

Generated assertions and final-state snapshots protect against accidental output changes.
They do not prove that an ability implements the printed card text. A semantic fixture is
a small, hand-authored rules contract: it names the card and clause under test, and marks
the assertions that independently demonstrate the outcome.

## Metadata contract

Add this additive object to a fixture's `meta.json`:

```json
{
  "semanticCoverage": {
    "testedCards": ["card-id"],
    "mechanics": ["damage", "targeting"],
    "rulesClauses": [
      "On enter, deal 3 damage to target ally.",
      "A champion is not a legal target."
    ]
  }
}
```

`testedCards` should also be copied to the legacy top-level `testedCards` field while the
old tooling still reads it. `mechanics` uses concise, searchable names such as `cost`,
`targeting`, `damage`, `prevention`, `zone-movement`, `draw-discard`, `counter`, `status`,
`token`, `combat`, `trigger`, `condition`, or `limit`.

Mark every hand-authored proof with `"semantic": true` and a short `"label"`. Unknown
assertion fields are intentionally ignored by the runner, so this is backward compatible:

```json
{
  "step": 4,
  "type": "card_property_equals",
  "mzId": "theirField-0",
  "property": "Damage",
  "value": "3",
  "semantic": true,
  "label": "Target ally takes exactly 3 damage"
}
```

For a negative path, mark the deliberately illegal action itself. The runner requires the
engine to reject it, and that rejection counts as semantic evidence:

```json
{
  "playerID": 1,
  "mode": 100,
  "cardID": "theirField-0",
  "expectFailure": true,
  "semantic": true,
  "label": "Rejects a champion because the effect targets allies only"
}
```

Negative fixtures may reuse a happy fixture's initial game state with
`"baseFixture": "happy-fixture-slug"` in `meta.json`; only their own `actions.json`
and `assertions.json` are then required.

## Required coverage per ability

For every implemented ability, author a happy-path fixture and add a negative-path fixture
whenever the text has a cost, target restriction, condition, prevention/replacement effect,
or activation limit. Assert only observable rules outcomes: exact cost paid, legal target,
zone membership, damage, counter/status value, created token, or timing event.

Do not label generated card IDs or zone counts as semantic unless they are deliberately
selected to prove a printed clause. A `decision_queue_empty` assertion may verify that an
interaction finished, but it never completes a positive semantic contract by itself. Pair
it with an observable card, zone, counter, status, damage, or global-effect assertion.

## Writing fixtures with the scenario builder

New fixtures should be written as scenarios in `DevTools/ga-fixtures/*.php` (builder: `Scenario.php`)
instead of as raw `setup` / `actions` / `assertions` rows. A scenario describes the game in card names
and expands to exactly the array shape `create-prd-ability-fixtures.php` already consumes, so the
generator, the runner and the fixture files are unchanged.

```php
gaScenario('thunderclap-level-three-costs-two-less')
    ->standardDeck('Thunderclap')
    ->champion(level: 3)
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Thunderclap')
    ->play('Thunderclap')->pay(2)->passBoth()->target('theirField-1')
    ->expectZoneCount('myMemory', 2, 1, 'At level 3+ the card costs 2 less')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The ally was destroyed')
    ->expectDqEmpty()
    ->notes('...')
    ->covers(['cost', 'targeting', 'damage'], '[Level 3+] This card costs 2 less to activate.')
    ->register();
```

- **Names, not IDs or slots.** `seed('myHand', 'Name')` records which hand slot the card lands in, and
  `play('Name')` uses it. `champion(lineage:, level:, damage:, classes:)` patches the starting champion.
- **Actions.** `play`, `pay(n)`, `pass(p)`, `passBoth()`, `answer(p, mz)`, `target(mz)`, `endTurn()`,
  and `rejects(p, mz, label)` for a deliberately illegal answer (the negative path).
  `rawSetup()` / `rawAction()` are escape hatches for anything without a verb.
- **Expectations** default to the last action's step: `expectDamage`, `expectProperty`,
  `expectZoneCount`, `expectDqEmpty`. A label marks the assertion `"semantic": true`.
- **Meta.** `covers(mechanics, ...clauses)` writes `semanticCoverage` (with `testedCards` filled in from the
  cards played) and `notes()` writes the notes; both win over a kept `meta.json`.
- **A scenario's expectations replace `assertions.json`** when it declares any; otherwise the existing file
  is carried over as before.

Generate with `php DevTools/create-prd-ability-fixtures.php --fixture=<slug>`. Check the corpus with
`php DevTools/verify-ga-scenarios.php [slug ...]`: it regenerates each scenario fixture, compares every file
with HEAD by meaning (JSON decoded, CRLF ignored, `meta.json` `name` ignored) and restores the committed files,
so converting an inline definition to a scenario is provably behaviour-preserving. While a fixture is being
migrated, `register()` also refuses to run if the scenario differs from the inline definition of the same slug.

## Review and migration

Run:

```bash
php DevTools/lint-fixture-coverage.php --root=GrandArchiveSim
php DevTools/audit-ga-semantic-coverage.php
php DevTools/tdd-regression/test_ga_semantic_coverage_contract.php
```

The audit separates legacy fixtures from fixtures with incomplete or complete semantic
contracts. The focused contract test guards the classification rules themselves, including
queue-only false positives and explicit negative-path rejections. Export the authoritative implemented-card inventory from the GrandArchiveSim
database without exposing ability code:

```bash
php DevTools/export-ga-implemented-abilities.php \
  --output=Tests/Integration/GrandArchiveSim/implemented-cards.json
php DevTools/audit-ga-semantic-coverage.php \
  --implemented-cards-json=Tests/Integration/GrandArchiveSim/implemented-cards.json
```

Commit the generated inventory after reviewing its card count and diff. CI can then run
the second command with `--strict` once the semantic-fixture migration reaches its agreed
baseline. The exporter is designed for the GrandArchiveSim environment and never writes
ability source code, prereqs, or credentials into the artifact.

Build the authoring backlog from that inventory and the generated official card dictionary:

```bash
php DevTools/build-ga-semantic-backlog.php \
  --inventory=Tests/Integration/GrandArchiveSim/implemented-cards.json
```

This produces JSON for tooling plus a Markdown report with a prioritized first batch.
Mechanic tags are only triage hints inferred from printed effect text; fixture contracts
must still state the actual rule clause and expected outcome.
