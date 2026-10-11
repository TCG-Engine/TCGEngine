# HellbreakSim integration fixtures

A fixture is a folder: a starting gamestate, the actions to replay, the assertions to check, and a
snapshot of the end state. The runner is the shared one, so nothing here is Hellbreak-specific
except how fixtures are built.

```
php DevTools/RunIntegrationTests.php --root=HellbreakSim                 # every fixture
php DevTools/RunIntegrationTests.php --root=HellbreakSim --test=<slug>   # one fixture
php DevTools/RunIntegrationTests.php --root=HellbreakSim --test=<slug> --update-snapshots
```

## Building one

```
php DevTools/Hellbreak/make-fixture.php  --slug=<slug> [--deck=gama|fixture] [--auto=2|1,2|none]
php DevTools/Hellbreak/record-fixture.php --slug=<slug> --choices="#0,#0,#0,Play_Card,myHand-3"
php DevTools/RunIntegrationTests.php --root=HellbreakSim --test=<slug> --update-snapshots
```

1. **make-fixture** creates a deterministic game: no shuffle (deck order is the decklist), initiative
   always P1, and by default seat 2 answers its own prompts so only the seat under test decides.
   Automating both seats makes the game play itself — rounds roll past and the only prompt left is
   whatever the monster triggers.
2. **record-fixture** replays the starting state and drives it: it asks the engine what is legal,
   applies the choice you name, and writes `actions.json`. Each run starts from the beginning, so
   growing the `--choices` list one step at a time is the normal loop. With no `--choices` it just
   prints the current prompt, its options and the board.
   - A choice matches an option by `cardID` (`myHand-3`), by a modal's label (`Play_Card`), or by
     position (`#2`).
   - Modal answers are sent as indices, which is what the client sends; labels are for you.
3. **--update-snapshots** writes `expected_final_gamestate.txt`. Run the test again without the flag
   to confirm it passes on its own.

## Assertions

`assertions.json` is what makes a fixture a card test — the snapshot alone only catches change, not
correctness. Each entry names the step it runs after (1-based, matching `actions.json`).

```json
{ "step": 5, "type": "card_exists", "zone": "myAssets", "viewerPlayerID": 1, "cardID": "DOT_049",
  "label": "Playing Vampire's Coffin from hand puts it into play" }
```

Types: `card_exists`, `zone_count`, `phase_is`, `turn_player_is`, `card_property_equals`,
`decision_queue_empty`. Zones use the schema names with a seat prefix: `myHand`, `myAssets`,
`myCharacters`, `myCrypt`, `theirCharacters`.

Prove an assertion runs by breaking it on purpose once; a fixture whose assertions never execute
passes no matter what the engine does.

## Fixtures

| Slug | What it covers |
|---|---|
| `play-from-hand` | Setup (location, mulligan, bid) into the Horror phase, then playing an asset from hand: it enters play, leaves hand, and costs blood. |

## Gotchas

- **Card data is the real card.** The tutorial's simplified values (`HellbreakFixtureCards()`) apply
  only when the game is the tutorial, so fixtures feed printed resource bars: Dracula gives 2 blood
  and 2 draws on round 1.
- **Loyalty is enforced**, so a card is only offered when the vault provides its aspect. Dracula's
  vault is Cursed; a Revenant minion will not appear in "Choose a card to play".
- **Deck order is the decklist order.** The opening hand is the first cards of
  `HellbreakGamaDemoDeck()`, so changing that deck changes every fixture's hand.
