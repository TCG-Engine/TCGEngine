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
    ->castSpell('Thunderclap', pay: 2, window: 'both', target: 'theirField-1')
    ->expectZoneCount('myMemory', 2, 1, 'At level 3+ the card costs 2 less')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The ally was destroyed')
    ->expectDqEmpty()
    ->notes('...')
    ->covers(['cost', 'targeting', 'damage'], '[Level 3+] This card costs 2 less to activate.')
    ->register();
```

- **Names, not IDs or slots.** `seed('myHand', 'Name')` records which hand slot the card lands in, and
  `play('Name')` uses it. `champion(lineage:, level:, damage:, classes:)` patches the starting champion.
- **Actions.** `castSpell(card, pay:, window:, target:)` is `play` + `pay` + the priority window (`'both'`,
  `'me'`, `'opp'` or none) + the target answer in one statement; use the pieces (`play`, `pay(n)`, `pass(p)`, `passBoth()`, `answer(p, mz)`, `target(mz)`, `endTurn()`) when a fixture needs steps in between,
  and `rejects(p, mz, label)` for a deliberately illegal answer (the negative path).
  `rawSetup()` / `rawAction()` are escape hatches for anything without a verb.
- **Card-derived setup.** `readyFor('Card', classBonus: true, level: n, damage: n)` patches the champion so the card's
  element is unlocked (one lineage card per advanced element) and, with `classBonus`, its class applies. Look a card up with
  `php DevTools/ga-card-info.php "Card Name"` (id, element, classes, costs, stats, effect text).
- **Families.** Repeated shapes have helpers in `Scenario.php`: `gaAbilitySandbox` + `expectCostFootprint` (printed
  activation costs), `gaSpellshroudCase` (spell cannot target a shrouded unit).
- **Expectations** default to the last action's step: `expectDamage`, `expectProperty`,
  `expectZoneCount`, `expectDqEmpty`. A label marks the assertion `"semantic": true`.
- **Meta.** `covers(mechanics, ...clauses)` writes `semanticCoverage` (with `testedCards` filled in from the
  cards played) and `notes()` writes the notes; both win over a kept `meta.json`.
- **A scenario's expectations replace `assertions.json`** when it declares any; otherwise the existing file
  is carried over as before.
- **Decks.** `standardDeck('Card')` and `deckTemplate('name', 'Card')` use the named templates in
  `ga-fixtures/decks.php` (`{MAIN}` is the card under test); `deck($text)` takes any deck text, including the
  shared `$gaSweepDeck`-style variables defined in the generator. Add a template when several fixtures share a deck.
- **More setup.** `patch(mz, props)`, `deckTop([...])`, `emptyZone(zone)`, `globalEffect(name)`, `dqVariables([...])`.
  A card whose name is ambiguous in the card data is written `'#<cardID>'`. `playSlot(n)` clicks a hand slot
  directly (opening-hand cards); `action(player, mode, card)` is any other input row. `tested(...)` overrides the
  tested cards when they are not just the cards played.

Where things live: `ga-fixtures/cards-<letter>.php` holds the migrated fixtures grouped by the first letter of
the tested card, `damage*.php` the damage family and the newer backlog work, and `families.php` the fixtures
built in loops (Shackled Theurgist, Bygone Days, the Heirloom pair, Lamentation's Toll, Seeker's Aetherwing).
`base('slug')` derives a fixture from another one's recorded state (written as `baseFixture` in `meta.json`).
`create-prd-ability-fixtures.php` no longer contains fixture definitions of its own, only helpers, shared deck
variables and the loader.

Generate with `php DevTools/create-prd-ability-fixtures.php --fixture=<slug>` (from the repository root: run from
another directory and the generator clears the fixture folder without recording a new one). Check the corpus with
`php DevTools/verify-ga-scenarios.php [slug ...]`: it regenerates each scenario fixture, compares every file
with HEAD by meaning (JSON decoded, CRLF ignored, `meta.json` `name` ignored) and restores the committed files,
so converting an inline definition to a scenario is provably behaviour-preserving.
If you correct a committed fixture by hand (an extra answer, a response-window pass), make the same change in its
scenario: regeneration replays the scenario, and a stale scenario silently drops the answer again. The verifier flags
any scenario whose regenerated actions, assertions or tested cards differ from the committed fixture. While a fixture is being
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

## Checking that assertions can fail

`php DevTools/mutation-spot-check.php --sample=25 --seed=1` changes one expected value in each sampled fixture's semantic
assertions, replays the fixture and requires it to fail; the file is restored afterwards. A surviving mutant means the
assertion is not checking what its label claims (wrong step, wrong zone, unreachable value). Run it after writing a batch.

### Priority windows and the `quiet` deck

A player who holds a fast card (the standard deck's four Fairy Whispers are fast) is offered a priority window at every phase change, so
turn handovers need extra `pass()` steps and a seat that is not expected to act still has to pass. Write new scenarios on
`deckTemplate('quiet', 'Card')` (Fairy Whispers swapped for Dungeon Guide): `endTurn()` / `reachTurnThree()` then work as written and an
attack is `play(card, seat)->pay(n, seat)->target(...)`. Use `reachTurnThree(fastCardInHand: true)` only when a seeded fast card forces
the windows. Player 2 attacks on turn 2 with no recollection before it, which is the way to test a rested seeded ally.

### Silently ignored steps: `--audit-noops`

Mutation checks only prove that an *assertion* is evaluated; they cannot see a scenario that never did what it says. The engine
accepts a click on a card that is not there (a hand slot that shifted after an earlier play or payment) without complaint, and every
later step then does nothing while the assertions (`expectDamage 0`, "the effect is still on the champion") stay true. Luster's
Shroud's first draft passed this way: the Nascent Blast was never cast.

`php DevTools/RunIntegrationTests.php --root=GrandArchiveSim [--test=<slug>] --audit-noops` prints `[NOOP] <slug>: <step>:<answer> ...`
for every accepted action that left the saved game state unchanged. Trailing `PASS` steps are normal; a no-op click or answer that
is followed by further real actions means the scenario stopped doing anything at that step. A deliberate refusal fixture ("cannot be
activated") ends in a no-op by design. The builder now tracks hand slots itself (`play()` clicks the card's current slot after earlier
plays and `pay()`s; playing a name that is not in the modelled hand is a builder error), but draws and discards are not modelled, so
run the audit on any scenario that plays a second card.

Triage of the existing suite (Oct 2026): 588 fixtures show a no-op, most of them a trailing `PASS` or an `observe()`. Of the ~20 with a
non-trailing click that does nothing, every one is a redundant click that a pending prompt swallowed (a play click while a fast-action
window or target prompt is open; the next `answer()` then does the real work) and the fixtures' positive assertions still prove the
effect. Only a *negative* assertion (`expectDamage 0`, "unchanged") can hide a dead step, which is where the audit matters.

## Pitfalls found while upgrading old fixtures

- A `pass(1)` after paying is only a priority pass when a window actually opens. When a prompt (for example Glimpse) is already
  pending, the pass answers it, so the effect never runs while the fixture still reports success. Check the step with
  `php DevTools/dump-fixture-state.php --root=GrandArchiveSim --test=<slug>`.
- A decision-queue-empty check proves nothing about an effect; pair it with the card that moved, the counter that appeared or the
  damage that was dealt. The audit counts any non-queue assertion as observable evidence.
- Seeded weapons enter with their printed durability counters.

## Starting a fixture: `ga-scaffold.php`

`php DevTools/ga-scaffold.php "Card Name"` prints a scenario skeleton from card data: the deck, `readyFor()` (element access,
`[Class Bonus]`, `[Level N+]`), the seed, the play with the printed cost (reduced by a printed "costs N less" when the bonus is on),
a guessed target, `tested()` and `covers()` with the mechanics and the printed clauses prefilled. Above it, comments list the twins the
text implies (no-bonus, below-the-level gate, refusal at the reduced cost, optional branch declined), mechanic hints, and exemplar
fixtures of covered cards that share a mechanic (same element and type first), so a proven prompt order can be copied.

- `--element=WIND --type=ACTION --limit=10` scaffolds the next uncovered cards of a shape; `--list --element=WIND` groups all uncovered cards
  of an element by type and mechanic (the batching view); `--exalted` selects the Exalted cards instead.
- `--probe` builds a throwaway fixture of just the play and payment, runs `ga-autoplay.php` on it and prints the real prompt order and
  final board, then deletes the throwaway files. Use it before writing the `window:` / `answer()` steps.
- `--write=DevTools/ga-fixtures/<file>.php` appends the skeletons to a fixture file.
- Every skeleton slug starts with `todo-`, and `register()` refuses it: an unfinished skeleton cannot be committed. Rename the slug to
  describe the behaviour when the scenario is finished. The skeleton does NOT know the window passes, the real prompt order or the
  observable result; those, the negative twins and a mutation check are still the author's work.

### New effects: `UNMATCHED` and `ga-fixtures/mechanics.php`

The mechanics the scaffold recognises live in ONE table, `DevTools/ga-fixtures/mechanics.php` (regex, `covers()` tag, hint, whether to list
exemplars, whether `--list` groups by it). A printed clause that matches no row is a NEW EFFECT: the skeleton prints
`// UNMATCHED (new effect, no mechanic row): <clause>` and has no hint or exemplar for it, so write that part by hand.
`php DevTools/ga-scaffold.php --unmatched --element=WIND` lists the unmatched clauses grouped by shape (numbers and brackets blanked).
**Promotion rule:** the first one or two cards with a new clause are hand-written; when a shape repeats on a third card (the report says
"N repeat on 3+ cards"), add a row to `mechanics.php` and, if the assertions repeat too, a Layer 2 helper in `Scenario.php`.
(`BacklogMechanics()` in `build-ga-semantic-backlog.php` is a separate, coarser list that only ranks the backlog; it is not this table.)

`DevTools/ga-fixture-run.sh [--play] slug...` regenerates and runs scenarios (add `--play` for the autoplay prompt order and final state).

## Guard rails: `DevTools/ga-verify-all.sh`

`DevTools/ga-verify-all.sh` (add `--full` to also regenerate every scenario and compare it with HEAD) runs, and reports on, every stage:
the integration suite, GABot self-play, `audit-ga-semantic-coverage.php --strict` (every semantic contract needs an observable, labelled
assertion), `create-prd-ability-fixtures.php --audit-actions` (a saved fixture with fewer actions than its definition was silently
truncated) and `check-ga-coverage-baseline.php` (the cards without a semantic fixture must be exactly those in
`ga-fixtures/uncovered-baseline.txt`, explained in `UNFIXABLE.md`). The generator also refuses inline fixture definitions
(`ga-fixtures/inline-allowlist.txt` is empty) and fails a scenario whose `rejects()` step the engine accepted.

More pitfalls found while closing the last gaps:

- A card seeded onto the field runs its On Enter at once, so its prompt is pending before the first step; an `endTurn()` or a click
  before answering it is swallowed (Burnished Obelith, Nia and Merlin, Surreal Figment were dead tests for that reason).
- `rejects()` expects the engine to refuse an *answer*. A click on a card (`!FSM!`, `!CustomInput!Activate:N`) that is not allowed is
  refused with a flash message and counts as accepted: assert the unchanged state instead (a zone count, a status, a damage total).
- Seeded hand cards sit at the *front* of the hand, ahead of the opening seven, and `pay()` takes `myHand-0` each time: a seeded card that
  is not meant to pay gets used up unless junk is seeded in front of it. A fast or reaction card left in hand makes the engine open a
  priority window after paying a spell, so pass it (`castSpell(..., window: 'both')`).
- Delete exactly one slug directory before a re-run (`rm -rf Tests/Integration/GrandArchiveSim/<slug>`); a prefix glob also removes the
  tracked fixtures of other cards with the same prefix.

## Finding the real prompt order

Do not guess it. Write the scenario as just `->seed('myHand', 'Card')->play('Card')`, generate it with
`php DevTools/create-prd-ability-fixtures.php --fixture=<slug>` and run `php DevTools/ga-autoplay.php --test=<slug>`: it replays the
recorded actions, then answers every further prompt (first option, YES for YESNO, PASS for priority windows; `--activate` takes
the first offered ability) and prints each prompt, the answer and the final board. `python3 DevTools/ga-fixture-try.py
DevTools/ga-fixtures/<file>.php <slug>...` regenerates scenarios and picks the castSpell priority window that the generator
accepts. `dump-fixture-state.php` shows the decision queue after every recorded action when an assertion fails.
