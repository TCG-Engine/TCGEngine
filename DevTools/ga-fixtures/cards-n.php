<?php
/**
 * Fixtures for cards starting with 'N', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Necklace of Foresight: Banish self, Glimpse 4 ---
// Necklace of Foresight's element is NORM, so no lineage patch is needed. As a REGALIA,ITEM
// (a Material-deck card type), it is seeded directly onto the field rather than played from
// hand, same pattern as worn-gearblade-durability-prevent -- this fixture is about its
// activated ability, not its materialize flow, so the memory-cost payment mechanic is out of
// scope.
// Notes carried over from the original definition:
//   - Necklace of Foresight, seeded straight onto the field
gaScenario('necklace-of-foresight-banish-glimpse')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Necklace of Foresight')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom=')->pass(1)
    ->tested('Necklace of Foresight')
    ->register();

// --- Nascent Barrier: [Level 3+] Glimpse 3 ---
// Notes carried over from the original definition:
//   - level 3 for the Level 3+ Glimpse clause
gaScenario('nascent-barrier-glimpse')
    ->standardDeck('Nascent Barrier')
    ->champion(level: 3)
    ->seed('myHand', 'Nascent Barrier')
    ->play('Nascent Barrier')->pay(2)->passBoth()->answer(1, 'Top=em6eEh9q8y,6bc3ogf0o8,em6eEh9q8y;Bottom=')
    ->register();

// --- Novice Mechanist: Foster; On Foster: Summon an Automaton Drone token ---
// Foster is checked at the beginning of the controller's recollection phase: "if this ally
// hasn't been dealt damage since the end of your previous turn, it becomes fostered"
// (GameLogic.php ~9501, inside ResolveBeforeRecollectionPhaseStart). A freshly-seeded object
// has no DAMAGED_SINCE_LAST_TURN tag, so it qualifies immediately. Reaching player 1's own
// recollection phase just needs: player 1 ends turn 1, player 2 ends their turn (still global
// turn 1), then player 1 declines their own MAT-phase materialize offer -- that single Pass
// auto-advances into player 1's own BREC, which is where Foster processing (and this card's
// On Foster trigger) runs.
// Notes carried over from the original definition:
//   - Novice Mechanist
//   - ends turn 1
//   - ends player 2's turn (still global turn 1)
//   - decline player 1's own MAT-phase materialize offer -> reaches BREC/Foster processing
gaScenario('novice-mechanist-on-foster-summon-drone')
    ->standardDeck('Novice Mechanist')
    ->seed('myField', 'Novice Mechanist')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Novice Mechanist')
    ->register();

// --- Neos Sight: Draw a card; if you control 8+ objects, draw into memory too ---
// Neos Sight is NEOS (advanced element), so the starting champion's Subcards are patched
// with a real NEOS champion (Tonoris, Creation's Will) to unlock element access. Its reserve
// cost is a real printed 0, so no reserve-payment decision is ever queued. Seven allies are
// seeded onto the field alongside the champion (8 objects total) to satisfy the "eight or
// more objects" clause.
// Notes carried over from the original definition:
//   - NEOS lineage/element unlock
gaScenario('neos-sight-eight-objects-memory-draw')
    ->standardDeck('Neos Sight')
    ->champion(lineage: ['Tonoris, Creation\'s Will'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Neos Sight')
    ->castSpell('Neos Sight', window: 'me')
    ->expectDqEmpty()
    ->expectZoneCount('myMemory', 1, 1, 'controlling 8+ objects also draws a card into memory')
    ->register();

// --- Nimble Court Assassin: Ambush, Vigor; On Enter you gain agility 3 for this turn ---
// Nimble Court Assassin's element is EXALTED (an advanced element enabled only while another
// advanced element is enabled), so the starting champion's Subcards are patched with a real
// TERA champion to unlock element access generically the same way other advanced-element
// fixtures do (Exalted enables off ANY other advanced element being enabled).
// Notes carried over from the original definition:
//   - TERA lineage/element unlock (enables Exalted too)
gaScenario('nimble-court-assassin-enter-agility')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myHand', 'Nimble Court Assassin')
    ->castSpell('Nimble Court Assassin', pay: 5)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'CardID', 'i2vPUpbPEl')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-3', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-4', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'AGILITY_3', 'On Enter grants agility 3 for this turn')
    ->expectZoneCount('myHand', 2, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 5, 1)
    ->register();

// top two: Dungeon Guide, Fluffy Shopkeep
// Notes carried over from the original definition:
//   - put Fluffy Shopkeep (the 2nd card) into the graveyard; Dungeon Guide goes to the bottom
gaScenario('nico-raptures-embrace-enter-look-two-graveyard-one-bottom-other')
    ->deck($gaNicoDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myTempZone-1')
    ->tested('Nico, Rapture\'s Embrace')
    ->register();

// Notes carried over from the original definition:
//   - put Dungeon Guide (the top card) into the graveyard; Fluffy Shopkeep goes to the bottom
gaScenario('nico-raptures-embrace-enter-look-two-graveyard-first-bottom-second')
    ->deck($gaNicoDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myTempZone-0')
    ->tested('Nico, Rapture\'s Embrace')
    ->register();

// PLAYER 2 seating (perspective check): player 2's second turn (global turn 4) offers the level-up; the two looked-at cards sit
// under player 2's turn-2 draw (a filler Dungeon Guide). Player 1 declines its own turn-3 materialize offer first.
// Notes carried over from the original definition:
//   - Fluffy Shopkeep into player 2's graveyard; Dungeon Guide to the bottom of player 2's deck
gaScenario('nico-raptures-embrace-player-two-enter-look-two-graveyard-one-bottom-other')
    ->deck($gaNicoDeck)
    ->seed('myMemory', 'Fairy Whispers', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Fluffy Shopkeep'], player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->answer(2, 'myMaterial-0')->answer(2, 'myTempZone-1')
    ->tested('Nico, Rapture\'s Embrace')
    ->register();

// top three, top to bottom: DG, FS, Hidden Longbowman (the one banished, 3rd); then Charge the Soul (marker: must be the new top card)
gaScenario('naia-enter-reveals-three-banishes-deep-ally-rest-to-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon', lineage: ['Spirit of Water'])
    ->seed('myHand', 'Naia, Diviner of Fortunes')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Hidden Longbowman', 'Charge the Soul'])
    ->castSpell('Naia, Diviner of Fortunes', pay: 3, target: 'myTempZone-2')
    ->register();

// top three: DG, FS, Fairy Whispers (a Spell, 3rd) -> banished and tagged so it can be activated while Naia is controlled
gaScenario('naia-enter-banishes-deep-spell-tags-it-and-rest-to-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon', lineage: ['Spirit of Water'])
    ->seed('myHand', 'Naia, Diviner of Fortunes')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Naia, Diviner of Fortunes', pay: 3, target: 'myTempZone-2')
    ->register();

// --- Nature's Appeal (oj0oh7pjoq): "Reveal the top LV cards of your deck. Put one of those cards into your hand and another into your material deck preserved. Put the rest on the bottom of
// your deck in any order." The generated body revealed with a fixed-index MZMove loop: only the top card was ever revealed. The champion is level 3 (Kongming, Fel Eidolon, TERA). ---
// Notes carried over from the original definition:
//   - Kongming, Fel Eidolon (level 3, TERA)
//   - Nature's Appeal -> myHand-7
//   - top three revealed (Fairy Whispers -> hand, Fluffy Shopkeep -> material, Dungeon Guide -> bottom), then a marker
gaScenario('natures-appeal-level-three-reveals-three-hand-material-and-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon')
    ->seed('myHand', 'Nature\'s Appeal')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Nature\'s Appeal', pay: 2, target: 'myTempZone-2')
    ->rejects(1, 'myTempZone-2', 'the card already put into hand is no longer offered for the material deck')
    ->target('myTempZone-1')
    ->register();

// --- Nature's Insight (3bS1Y9OQrF): "Reveal a card from your memory and put it into your material deck preserved. X is that card's reserve cost. Then reveal the top X cards of your deck and put
// them into your material deck preserved." The generated handler moved the deck cards with a fixed-index loop (read $deck[0] / "myDeck-0"): only ONE card ever reached the material deck. ---
// Notes carried over from the original definition:
//   - Kongming, Fel Eidolon (level 3, TERA)
//   - memory-0: Dungeon Guide (reserve cost 3 -> X = 3)
//   - Nature's Insight -> myHand-7
//   - top three go to the material deck, then a Charge the Soul marker
gaScenario('natures-insight-memory-card-cost-three-puts-top-three-into-material')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Nature\'s Insight')
    ->deckTop(['Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Charge the Soul'])
    ->castSpell('Nature\'s Insight', pay: 3, target: 'myMemory-0')
    ->register();

// --- Nameless Champion (9tmr8iel1m): "This champion can't level up. (6): Draw a card and put a level counter on Nameless Champion. Activate this ability only once." The cost is six reserve (no rest) and the ability works exactly
// once. Player 1's champion is patched to Nameless Champion and pays six hand cards. ---
// Notes carried over from the original definition:
//   - Nameless Champion
//   - six more hand cards: a second activation is affordable, so only "once" can refuse it
gaScenario('nameless-champion-pay-six-draw-and-level-counter-only-once')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Nameless Champion')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->pay(6)
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')
    ->tested('Nameless Champion')
    ->register();

// --- Nameless Champion, a printing with no generated ability rows (LahboNoSRx): the same (6) ability must work. ---
// Notes carried over from the original definition:
//   - Nameless Champion (ASSASSIN/WARRIOR printing)
//   - six more hand cards: a second activation is affordable, so only "once" can refuse it
gaScenario('nameless-champion-printing-without-ability-rows-pay-six-draw-and-level-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: '#LahboNoSRx')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->pay(6)
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')
    ->tested('#LahboNoSRx')
    ->register();

// --- Nameless Champion (9tmr8iel1m): "This champion can't level up." Only one printing (0794z3ffck) enforced it. A level-1 Nameless Champion with a Kongming, Erudite Strategist in the material deck and the 2-memory level-up cost
// pre-loaded must stay as it is when the level-up is chosen. ---
// Notes carried over from the original definition:
//   - Nameless Champion (level 1)
//   - pays the 2-memory level-up cost, card 1/2
gaScenario('nameless-champion-cannot-level-up')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Kongming, Erudite Strategist
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Nameless Champion')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()->pass(1)->pass(1)
    ->endTurn(2)->pass(1)->pass(1)
    ->rejects(1, 'myMaterial-0', 'Nameless Champion cannot level up: the level-up is not offered')
    ->tested('Nameless Champion')
    ->register();

// Nocturne's Oblivion (1a5zdqgydt): "Destroy target non-champion object. Banish Nocturne's Oblivion."
// Notes carried over from the original definition:
//   - UMBRA lineage
//   - Dungeon Guide -> theirField-1
//   - Nocturne's Oblivion -> myHand-7
gaScenario('nocturnes-oblivion-destroys-a-non-champion-object-and-banishes-itself')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Tristan, Shadowdancer'], classes: 'GUARDIAN')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Nocturne\'s Oblivion')
    ->castSpell('Nocturne\'s Oblivion', pay: 3, target: 'theirField-1')
    ->expectZoneCount('theirField', 1, 1, 'Player 2 only has their champion left')
    ->expectZoneCount('myBanish', 1, 1, 'Nocturne\'s Oblivion banished itself')
    ->expectZoneCount('myGraveyard', 0, 1, 'It is not left in the graveyard')
    ->expectDqEmpty()
    ->register();

// Nullifying Mirror (pol1nz0j1n): "[REST]: Until end of turn, cards in target opponent's memory are norm element."
// Notes carried over from the original definition:
//   - Nullifying Mirror -> myField-1
gaScenario('nullifying-mirror-rest-adds-the-norm-memory-effect-until-end-of-turn')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Nullifying Mirror')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Nullifying Mirror')
    ->register();

gaAbilitySandbox('nefarious-timepiece-ability-1-pays-its-printed-cost', 'Nefarious Timepiece', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->target('myField-1')->answer(1, 'YES')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(5)->pass(1)
    ->tested('Nefarious Timepiece')
    ->expectCostFootprint(hand: 8, memory: 6, banish: 3, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('necklace-of-hindsight-ability-1-pays-its-printed-cost', 'Necklace of Hindsight', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,px60u5n1do,px60u5n1do;Bottom=')->pass(1)
    ->tested('Necklace of Hindsight')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 1, graveyard: 3, field: 6)
    ->register();
