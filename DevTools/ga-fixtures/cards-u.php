<?php
/**
 * Fixtures for cards starting with 'U', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Uncover the Plot: target player reveals memory, draw; Class Bonus +2 preparation ---
// Uncover the Plot's element is LUXEM, so the starting champion's CardID is patched directly
// to Zander, Blinding Steel (LUXEM, ASSASSIN) -- both the element-requirement precondition to
// activate it at all and the Class Bonus preparation-counter clause. A filler card is seeded
// into the OPPONENT's memory ('theirMemory' with player=>1, i.e. player 2's memory -- setup
// zone names are relative to the acting player) so the "target player reveals all cards in
// their memory" half has something to reveal, observable via the "REVEAL:<cardID>" flash
// message DoRevealCard() sets (GameLogic.php).
// Notes carried over from the original definition:
//   - Zander, Blinding Steel (LUXEM, ASSASSIN)
//   - Dungeon Guide, seeded into the opponent's (player 2's) memory
gaScenario('uncover-the-plot-reveal-draw-prepare')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Blinding Steel')
    ->seed('theirMemory', 'Dungeon Guide')
    ->seed('myHand', 'Uncover the Plot')
    ->play('Uncover the Plot')->pay(2)->answer(1, 'NO')
    ->register();

// --- Unity's Gale: Target ally gets +3LIFE until end of turn. At the beginning of the next end
// phase, if that ally is damaged and you don't control it, you gain the Crowd's Favor status.
// Hand-authored ability (see GrandArchiveSim/Custom/GameLogic.php,
// cardActivatedAbilities["uUWsgLmyTk:0"] plus the EndPhase()/ObjectCurrentHP hooks -- no
// CardEditor ability database row was reachable in this sandbox) ---
// Unity's Gale is WIND element, so the starting champion's Subcards are patched with a real
// WIND champion (Spirit of Wind, pNiyaGlIe7) to unlock element access, matching the Cleansing
// Reunion/Tactful Sergeant precedent. Player 2's Dungeon Guide is seeded onto their own field
// pre-damaged (Damage=2) -- the delayed clause only checks "is damaged" as a state, not how or
// when the damage was dealt, so seeding it directly is equivalent to and far simpler than
// scripting a real combat step. Player 1 plays Unity's Gale from hand (its own 2-reserve
// cost), both players decline the fast-opportunity window, then targets player 2's Dungeon
// Guide -- an ally player 1 does not control, satisfying "you don't control it". Player 1 then
// ends their turn (a single Pass reaches EndPhase(), per the Rumble Coordinator precedent),
// where the hand-authored EndPhase() block finds the marker, confirms Damage>0 and
// Controller!=caster, and calls GainCrowdsFavor(1).
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Dungeon Guide, pre-damaged
//   - Unity's Gale
//   - pay reserve 1/2
//   - pay reserve 2/2
//   - target player 2's Dungeon Guide
//   - P1 requests main-phase pass
//   - P1 declines their own fast-action offer
//   - P2 declines too -> phase actually advances -> EndPhase()
gaScenario('unitys-gale-delayed-crowds-favor')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Damage' => 2])
    ->seed('myHand', 'Unity\'s Gale')
    ->castSpell('Unity\'s Gale', pay: 2, window: 'both', target: 'theirField-1')
    ->endTurn()->passBoth()->passBoth()->passBoth()
    ->expectDqEmpty()
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-3', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'uUWsgLmyTk')
    ->expectProperty('p1Memory-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'gpmJdGYqoC', 'Player 1 gains the Crowd\'s Favor status at the next end phase, since the targeted Dungeon Guide (player 2\'s, pre-damaged) was still damaged and player 1 didn\'t control it.')
    ->expectDamage('p2Field-1', 0)
    ->expectProperty('p2Field-1', 'TurnEffects', '[]', 'The +3 LIFE-until-end-of-turn buff and the one-shot delayed-check marker are both gone after the end phase that consumed/expired them -- proving the delayed check fired exactly once rather than leaking a permanent marker.')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Unstable Fractal: [Class Bonus] (3), REST, Sacrifice self -- destroy target cheap item ---
// Unstable Fractal (2o82fwl22v, PHANTASIA/CLERIC) is seeded directly onto the field, already
// awake (Status=2) -- its "Hindered" enter-rested flavor is irrelevant to the activated ability
// under test, so seeding bypasses it (same shortcut as windrider-vanguard-class-bonus-vigor
// seeding pre-rested). The champion is patched to Diao Chan, Enchantress (00xbh8oc00, CLERIC)
// so IsClassBonusActive($player, ["CLERIC"]) is true. A Backup Charger (9gv4vm4kj3, REGALIA/
// ITEM, memory cost 0) is seeded onto the opponent's field as the "target item with memory
// cost 0 or reserve cost 5 or less". Activating via CustomInput "Activate:0"
// (DoActivatedAbility -> activateAbilityAbilities["2o82fwl22v:0"]) auto-rests Unstable Fractal
// as its [REST] cost (PHANTASIA static-ability auto-rest, GameLogic.php ~line 6982), then
// ActivatedAbilityCost's case "2o82fwl22v" (GameLogic.php) further rests it again (no-op,
// already rested) and sacrifices it via DoSacrificeFighter -- paying the [REST] + Sacrifice
// CARDNAME cost of the printed clause. The "(3)" Class Bonus reserve cost turned out not to be
// separately prompted by this activation path (verified live -- no reserve-payment MZCHOOSE is
// queued between Activate:0 and the destroy target choice); the hand-count>=3 check lives only
// in activateAbilityPrereqs as a legality gate, not as an interactive payment step here.
// customDQHandlers["2o82fwl22v:0:ActivateAbility-1"] then destroys the chosen target. Because
// the target is a REGALIA card, GraveyardAddReplacement (GameLogic.php) transparently redirects
// the MZMove-to-graveyard into the target's controller's Banish zone instead (a real, generic
// "Regalia leaves play to Banish, not the Graveyard" engine rule -- confirmed by reading
// GraveyardAddReplacement -- not a bug in this ability).
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress (CLERIC) -- Class Bonus source
//   - Unstable Fractal, seeded awake
//   - Backup Charger (REGALIA/ITEM, memory cost 0) -- destroy target
//   - activate Unstable Fractal's ability
//   - target the opponent's Backup Charger
gaScenario('unstable-fractal-class-bonus-destroy-item')
    ->deckTemplate('diao-awakened-deacon', 'Unstable Fractal')
    ->champion(cardName: 'Diao Chan, Enchantress')
    ->seed('myField', 'Unstable Fractal', properties: ['Status' => 2])
    ->seed('theirField', 'Backup Charger')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->tested('Unstable Fractal')
    ->expectDqEmpty()
    ->expectProperty('p1Graveyard-0', 'CardID', '2o82fwl22v', 'Unstable Fractal itself was sacrificed to its own graveyard, paying the \'[REST], Sacrifice CARDNAME\' cost of the printed clause')
    ->expectProperty('p2Banish-0', 'CardID', '9gv4vm4kj3', 'The targeted Backup Charger (memory cost 0, a legal target) was destroyed and left play -- landing in the opponent\'s Banish rather than Graveyard because REGALIA cards are redirected there by GraveyardAddReplacement, a real generic engine rule, not a bug in this ability')
    ->expectProperty('p2Banish-0', 'Status', '2')
    ->expectDamage('p2Banish-0', 0)
    ->expectProperty('p2Banish-0', 'Counters', '[]')
    ->expectProperty('p2Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('theirField', 1, 1)
    ->register();

// --- Unstable Fractal: without Class Bonus (non-CLERIC champion), activation is illegal ---
// Negative-path companion to unstable-fractal-class-bonus-destroy-item. The champion is left
// as the deck's only Lv 0 Material champion (Spirit of Water, tafqldAGRF, class SPIRIT) instead
// of being patched to Diao Chan -- so IsClassBonusActive($player, ["CLERIC"]) is false.
// activateAbilityAbilities["2o82fwl22v:0"] itself (GeneratedMacroCode.php) opens with
// "if(!IsClassBonusActive($player, ["CLERIC"])) return;" -- the CustomInput click is legal (the
// engine accepts the button press; confirmed live that this is NOT rejected as an illegal
// action, unlike Piquant Shieldbearer's Taunt-target case), but the ability body returns
// immediately, so no MZCHOOSE target decision is ever queued and nothing on the board changes.
// Notes carried over from the original definition:
//   - Unstable Fractal, seeded awake
//   - Backup Charger -- a legal target exists, isolating Class Bonus as the only blocker
//   - attempt to activate without Class Bonus -- silently no-ops
gaScenario('unstable-fractal-no-class-bonus-blocked')
    ->deckTemplate('diao-awakened-deacon', 'Unstable Fractal')
    ->seed('myField', 'Unstable Fractal', properties: ['Status' => 2])
    ->seed('theirField', 'Backup Charger')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Unstable Fractal')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', '2o82fwl22v', 'Unstable Fractal is still on the field (not sacrificed) after the attempted activation with no Class Bonus')
    ->expectProperty('p1Field-1', 'Status', '2', 'Unstable Fractal is still awake (Status 2) -- the [REST] cost was never paid because the ability body returned before doing anything, confirming IsClassBonusActive gated the whole effect, not just the destroy')
    ->expectProperty('p2Field-1', 'CardID', '9gv4vm4kj3', 'The Backup Charger target is untouched -- still on the opponent\'s field, not moved to Banish, because no Class Bonus meant no MZCHOOSE target decision was ever queued')
    ->expectZoneCount('myGraveyard', 0, 1, 'Unstable Fractal\'s own graveyard-via-sacrifice never happened')
    ->register();

// --- Undeniable Truth (UaUfw7yFTW): As an additional cost, sacrifice an ally. Draw a card, then put
// a preparation counter on your champion. ---
gaScenario('undeniable-truth-sac-draw-prep')
    ->deckTemplate('spirit-dungeon-guide', 'Undeniable Truth')
    ->seed('myField', 'Dungeon Guide')
    ->playSlot(5)->target('myField-1')->pay(1)->passBoth()
    ->tested('Undeniable Truth')
    ->register();

// Notes carried over from the original definition:
//   - Diao Chan, Enchantress (CLERIC): Class Bonus
//   - Unstable Fractal, seeded awake -> myField-1
//   - Scepter of Lumina (memory cost 1, no reserve cost) -> theirField-1
//   - Forest Cake (reserve cost 2) -> theirField-2
gaScenario('unstable-fractal-refuses-memory-only-item-destroys-low-reserve-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diao Chan, Enchantress')
    ->seed('myField', 'Unstable Fractal', properties: ['Status' => 2])
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->target('theirField-2')
    ->tested('Unstable Fractal')
    ->register();

// --- Undying Dreams (y5koddlyv8, a Spell): "Target ally gets +1 POWER and +1 LIFE until end of turn." The generated target list never applied FilterSpellshroudTargets(): an ally with spellshroud was a
// legal target. Player 2 controls Genbu, Black Tortoise (printed Spellshroud) and an ordinary Dungeon Guide. ---
// Notes carried over from the original definition:
//   - Undying Dreams -> myHand-7
//   - Genbu, Black Tortoise (spellshroud) -> theirField-1
//   - Dungeon Guide -> theirField-2
gaScenario('undying-dreams-refuses-spellshroud-ally-targets-ordinary-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Undying Dreams')
    ->seed('myField', 'Genbu, Black Tortoise', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->castSpell('Undying Dreams', pay: 2)
    ->rejects(1, 'theirField-1', 'Genbu, Black Tortoise has spellshroud: not a legal target of the Spell Undying Dreams')
    ->target('theirField-2')
    ->register();

// Undercurrent Vantage (xicxo661ly): "Glimpse 3. Your champion becomes distant."
// Notes carried over from the original definition:
//   - WATER element, RANGER class
//   - Undercurrent Vantage -> myHand-7
gaScenario('undercurrent-vantage-glimpse-three-and-distant')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Spirit of Water'], classes: 'RANGER')
    ->seed('myHand', 'Undercurrent Vantage')
    ->play('Undercurrent Vantage')->pay(2)->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,px60u5n1do;Bottom=')
    ->register();

// Unbridled Flare (hXERTZPM0w): "[REST]: As a Spell, deal 1 damage to target champion."
// Notes carried over from the original definition:
//   - Unbridled Flare -> myField-1
gaScenario('unbridled-flare-rests-and-deals-one-damage-to-target-champion')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Unbridled Flare')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-0')
    ->tested('Unbridled Flare')
    ->register();

// Unbridled Flare, second ability: "[REST], Banish two fire element cards from your graveyard: As a Spell, deal 2 damage to target champion."
// Notes carried over from the original definition:
//   - Unbridled Flare -> myField-1
//   - Baby Red Slime (fire) -> myGraveyard-0
//   - Baby Red Slime (fire) -> myGraveyard-1
//   - Fluffy Shopkeep (not fire): stays
gaScenario('unbridled-flare-banishes-two-fire-cards-and-deals-two-damage-to-target-champion')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Unbridled Flare')
    ->seed('myGraveyard', 'Baby Red Slime')
    ->seed('myGraveyard', 'Baby Red Slime')
    ->seed('myGraveyard', 'Fluffy Shopkeep')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->target('myGraveyard-0')->target('myGraveyard-0')
    ->target('theirField-0')
    ->tested('Unbridled Flare')
    ->register();

// --- Unbroken Droid (POD9hwHYy0): "On Enter: Destroy up to one target item or weapon with memory cost 0 or reserve cost 3 or less." Same generated filter fatal as Thermal Break. ---
gaScenario('unbroken-droid-enter-destroys-low-reserve-item-refuses-memory-only-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Unbroken Droid')
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->castSpell('Unbroken Droid', pay: 4)
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->target('theirField-2')
    ->register();
