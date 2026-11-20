# Bot deck knowledge

New games record each player's submitted main-deck multiset before the opening draw. The record contains card counts sorted by ID, never shuffle order. Own material-deck cards remain available through the existing materialization policy. The competitive bot's runtime knowledge model reads deck **size only**.

`GABotDeckKnowledge` subtracts known cards in its own hand, memory, graveyard, face-up banishment, intent, and temporary zone, plus its owned public field cards and attachments. This updates after opening hands, draws, discards, milling, and known returns. Opponent hands, memory, and deck identities are never consulted. Face-down banished identities are excluded even though server code could technically access them.

`GABotDrawOdds` computes the chance of at least one wanted card in the next N draws using sampling without replacement. It returns lower/upper bounds when unobserved removals or unaccounted-for cards make deck composition uncertain. It does not assume an opaque removed card was randomly selected. Older saves lacking the submitted list return unknown odds instead of reconstructing that list from the hidden deck.

The count model assumes ordinary physical cards from the submitted list. It is not a full event-history/provenance tracker: transformations, copied cards, ownership transfers, and unusual effects can make inferred counts incomplete. An `exact` result means the inferred count totals balance with deck size; probability calculations assume unknown, randomized order. It does not claim certainty about an independently known top or bottom segment.

Current uses:

- Grand Crusader's Ring draw priority increases with the conservative chance of drawing Evasive Maneuvers to survive an incoming attack, subject to element and reserve availability. A guaranteed lifesaving response still takes precedence. Routine draw valuation uses the chance of an accessible ally.
- Card-name guesses use known card frequencies. The prior goldfish fallback could name the actual top card; competitive bots no longer call it.
- During an authorized `GlimpseApply` rearrangement, the bot ranks only the cards shown in that prompt, keeps usable cards on top, and puts inaccessible elements on the bottom. It preserves duplicates and the engine validates the resulting permutation.

Glimpse information is used within the active prompt. Persistent knowledge of chosen top/bottom segments through later draws, shuffles, and other deck manipulation is not implemented; the bot does not reread the live deck to recreate it. Further policies should consume the knowledge API or explicitly authorized revealed-card inputs, never `GetDeck(...)[...]->CardID`.

Tests:

```sh
php -d xdebug.mode=off DevTools/GABotKnowledgeTest.php
php -d xdebug.mode=off DevTools/GABotDecisionTest.php
```

The knowledge tests use objects that throw if a hidden identity is read, exercise both seats, reverse actual deck order, check probability math after an opening hand, cover opaque removals and known returns, test name-card guesses, and resolve real glimpse decisions through the engine.
