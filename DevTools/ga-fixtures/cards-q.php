<?php
/**
 * Fixtures for cards starting with 'Q', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Quickdraw Piercer: [Class Bonus] On Banish: Draw a card ---
// Quickdraw Piercer has no activated ability of its own -- its only ability is the generic
// OnLeaveField hook (fired whenever it leaves the field for any reason, not just a genuine
// rules "banish"). Blazing Throw's own mandatory "sacrifice a weapon" additional cost is the
// simplest real in-game path to remove it from the field, so it's played (FIRE element, needs
// the same lineage-patch pattern as UMBRA cards) targeting Quickdraw Piercer as the sacrifice.
// Notes carried over from the original definition:
//   - FIRE lineage/element unlock
//   - Diana, Keen Huntress (RANGER champion) - Class Bonus source
//   - Quickdraw Piercer - sacrifice target
gaScenario('quickdraw-piercer-class-bonus-on-banish-draw')
    ->standardDeck('Quickdraw Piercer')
    ->champion(lineage: ['Spirit of Fire'])
    ->seed('myField', 'Diana, Keen Huntress')
    ->seed('myField', 'Quickdraw Piercer')
    ->seed('myHand', 'Blazing Throw')
    ->play('Blazing Throw')->target('myField-2')->pay(1)->target('theirField-0')
    ->tested('Quickdraw Piercer')
    ->register();

// --- Quietus Blade: [Class Bonus] while material deck is empty, +4 POWER ---
// Quietus Blade's static [Class Bonus] power bonus (GameLogic.php ~13120, inside
// ObjectCurrentPower) adds +4 POWER to the weapon itself only while the controller's material
// deck is completely empty AND [Class Bonus] (WARRIOR) is active; GetTotalAttackPower
// (CombatLogic.php ~251-272) adds the selected weapon's ObjectCurrentPower into the attack
// total. The starting champion is patched to Lorraine, Wandering Warrior (WARRIOR) for the
// class bonus; Quietus Blade (printed 2 POWER) is seeded directly onto the field as an
// already-equipped weapon (same "seed weapon straight onto myField" shape as
// executioners-spear-jin-bonus-on-kill-durability); the remaining material deck (Clarent,
// Backup Charger, Purifying Thurible -- Spirit of Fire and Lorraine were already consumed/
// patched away) is emptied via the emptyZone primitive so the condition is met. Same real
// attack-declaration shape as executioners-spear-jin-bonus-on-kill-durability: P1 pass, P2
// pass, P1 declines the turn-3 MAT offer, then P1's champion (0 printed POWER) attacks,
// selecting Quietus Blade as the weapon (myField-1) and targeting the opposing champion
// (theirField-0); P2 declines the Retaliate? MZMAYCHOOSE. Total attack power is Quietus
// Blade's 2 printed POWER + 4 from the Class Bonus = 6, landing as Damage=6 on the defender
// (vs. Damage=2 if the Class Bonus bonus never applied).
// Notes carried over from the original definition:
//   - Lorraine, Wandering Warrior (WARRIOR) - Class Bonus source
//   - Quietus Blade, seeded as an already-equipped weapon
//   - empty material deck - satisfies the Class Bonus condition
//   - decline turn-3 MAT offer
//   - champion declares attack
//   - select Quietus Blade as the weapon
//   - target the opposing champion
//   - decline Retaliate?
gaScenario('quietus-blade-class-bonus-empty-material-power')
    ->deckTemplate('lorraine-fluffy-shopkeep-2', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Wandering Warrior')
    ->seed('myField', 'Quietus Blade')
    ->emptyZone('myMaterial', destZone: 'myBanish')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(2, '-')
    ->tested('Quietus Blade')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Status', '1')
    ->expectProperty('p1Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":6}')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}')
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p1Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectDamage('p2Field-0', 6, 'Quietus Blade\'s [Class Bonus] empty-material-deck static bonus (GameLogic.php ~13120, ObjectCurrentPower) adds +4 POWER to its printed 2, so GetTotalAttackPower (CombatLogic.php) lands 6 total attack power on the defending champion instead of 2')
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":6}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P1"]')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Quiet Refraction (4vZN8JlY2k): On Enter, draw a card into memory and put two sheen
// counters on Fractured Memories. Reserve cost 2 (NOT 3 -- see note below). Cast for real. ---
// NOTE: the card's printed/declared reserve cost is 2, confirmed here by the engine only
// queuing 2 ReserveCard decisions for this activation (see actions below: exactly two
// myHand-0 payments before the EffectStackOpportunity window). An earlier investigation of the
// "3 hand-payment" EffectStack bug listed Quiet Refraction among the cards that "hit" it needing
// 3 hand payments; that does not match this card's actual charged cost under a plain default
// activation (no cost-increase effects active) -- the discrepancy was very likely that earlier
// investigation running in a scenario with an active cost-increase effect (e.g. a champion
// ability that taxes activations by +1), not a genuine declared-vs-charged engine defect.
// Notes carried over from the original definition:
//   - Grant Fractured Memories mastery (normally gained via a Merlin-line mechanic) so the
//   - "put two sheen counters on your Fractured Memories" clause has somewhere to land and
//   - is independently observable.
gaScenario('quiet-refraction-enter-draw-sheen')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Quiet Refraction
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK
    )
    ->seed('myMastery', 'Fractured Memories')
    ->playSlot(4)->pay(2)->passBoth()
    ->tested('Quiet Refraction')
    ->expectProperty('myField-1', 'CardID', '4vZN8JlY2k', 'Quiet Refraction resolved off the EffectStack onto the field for real (reserve cost is 2, confirmed by only two ReserveCard payments being required)')
    ->expectZoneCount('myMemory', 3, 1, 'On Enter drew a card into memory')
    ->expectProperty('myMastery-0', 'Counters', '{"sheen":2}', 'On Enter put two sheen counters on Fractured Memories')
    ->register();

// Quicksilver Grail (cxyky280mt): "On Enter: Banish a non-champion card from your material deck face down. Banish CARDNAME: You may play the banished card. (You still pay for its costs.)" The activation never
// banished the Grail (the cost) so it could be used over and over.
gaScenario('quicksilver-grail-banishes-itself-as-the-cost-and-plays-the-banished-card')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Backup Charger
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Quicksilver Grail')
    ->target('myMaterial-0')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'YES')->pass(1)
    ->tested('Quicksilver Grail')
    ->register();
