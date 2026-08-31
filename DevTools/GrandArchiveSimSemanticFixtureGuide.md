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
