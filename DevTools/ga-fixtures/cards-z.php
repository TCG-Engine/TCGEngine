<?php
/**
 * Fixtures for cards starting with 'Z', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Zander, Prepared Scout: On Enter, Glimpse 2, put a preparation counter on itself ---
// Zander, Prepared Scout is level 1, one level above the default level-0 starting champion
// (Spirit of Fire), so no lineage/element patch is needed -- 0->1 is a legal level-up
// (CanChampionLevelUpIntoCard() in GameLogic.php requires targetLevel === currentLevel + 1;
// same shape as tonoris-lone-mercenary-on-enter-taunt). Its NORM element is always playable.
// Champion-swap materialization is only offered through the material-phase MZMAYCHOOSE at the
// start of a turn, so both players end their first turn (P1 -> P2) to reach that prompt on
// P1's next turn. Its printed cost is 1 memory, paid from a filler card seeded directly into
// myMemory. Choosing it completes the swap and its On Enter ability (GeneratedMacroCode.php
// enterAbilities["T3CIBknts0:0"]) both Glimpses 2 (resolved via the Top=/Bottom= deck-order
// decision) and puts a preparation counter on itself.
// Notes carried over from the original definition:
//   - filler card in memory to pay the 1-memory level-up cost
//   - Glimpse 2: keep deck order unchanged
gaScenario('zander-prepared-scout-enter-glimpse-prepare')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Zander, Prepared Scout
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'Top=n8wyfG9hbY,em6eEh9q8y;Bottom=')
    ->tested('Zander, Prepared Scout')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'CardID', 'T3CIBknts0')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'On Enter puts a preparation counter on itself')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-7', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p1Material-0', 'CardID', 'm31WVJ9F04')
    ->expectProperty('p1Material-1', 'CardID', '9gv4vm4kj3')
    ->expectProperty('p1Material-2', 'CardID', 'LeyUk5auEP')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'LEVELED_UP_THIS_TURN')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Zander, Blinding Steel: at your recollection phase, reveal memory; opponent puts hand cards into memory per luxem revealed ---
// The starting champion's CardID is patched directly to Zander, Blinding Steel so
// ChampionHasInLineage($turnPlayer, "UAF6Nr7GUE") is true (GameLogic.php ~line 9962) --
// testing only the passive recollection-phase trigger, not the real level-up flow. A LUXEM
// card is seeded directly into player 1's own memory as the revealed card -- specifically
// Corhazi Infiltrator (an ALLY), not a REGALIA card like Insignia of the Corhazi: AddMemory()'s
// MemoryAddReplacement() hook (GameLogic.php) silently redirects any REGALIA card added to
// memory into the Material zone instead (a real GA rule -- Regalia can't sit in memory), so a
// REGALIA seed here would silently land in Material and never be revealed (verified live: the
// seeded Insignia ended up counted in myMaterial, and GetMemory(1) read back empty). Both
// players end their first two turns (P1 -> P2) to reach player 1's OWN next turn, whose
// recollection phase reveals memory and, for each luxem card revealed (1 here), makes the
// opponent (player 2) put a card from their hand into their memory
// (ZanderBlindingSteelStep/ZanderBlindingSteelMemory in CardDQHandlers.php).
// Notes carried over from the original definition:
//   - Zander, Blinding Steel lineage
//   - Corhazi Infiltrator (LUXEM ALLY, not REGALIA) - revealed card
//   - ends turn 1
//   - ends turn 2, reaching player 1's recollection phase
//   - decline the material-phase champion swap offer
//   - opponent puts a hand card into memory
//   - decline Corhazi Infiltrator's own reveal-triggered offer (unrelated to Zander)
gaScenario('zander-blinding-steel-recollection-luxem-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Blinding Steel')
    ->seed('myMemory', 'Corhazi Infiltrator')
    ->endTurn()
    ->endTurn(2)->pass(1)->pay(1, 2)->answer(1, '-')
    ->tested('Zander, Blinding Steel')
    ->expectDqEmpty()
    ->expectProperty('p1Hand-7', 'CardID', 'VAFTR5taNG')
    ->expectProperty('p1Hand-8', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-3', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-5', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-6', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do', 'Recollection-phase reveal of a luxem card forces the opponent to put a hand card into memory')
    ->expectZoneCount('myHand', 9, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirMemory', 1, 1, 'Opponent\'s memory grows by exactly the number of luxem cards revealed (1)')
    ->register();

// --- Zephyr: suppress (banish, returns at next end phase) target ally or regalia ---
// Zephyr (idaRe7y3In, WIND -- native to the base Spirit of Wind champion, no patch needed)
// targets a Dungeon Guide (em6eEh9q8y, ALLY) seeded onto the opponent's field.
// cardActivatedAbilities["idaRe7y3In:0"] (GeneratedMacroCode.php) offers it as an MZCHOOSE
// target and calls SuppressAlly(), which banishes it immediately (and would return it to the
// field at the next end phase) -- observable here as the target vanishing from theirField.
gaScenario('zephyr-suppress-ally')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Zephyr')
    ->play('Zephyr')->pay(2)->passBoth()->target('theirField-1')
    ->expectDqEmpty()
    ->expectZoneCount('theirField', 1, 1, 'The targeted ally is removed from the opponent\'s field (suppressed).')
    ->register();

// --- Zander, Deft Executor: On Enter, put 2 preparation counters; may remove 1 to return an
// Assassin action/attack card from graveyard to hand ---
// Zander, Deft Executor's On Enter ability (enterAbilities["fc4ic5fmaa:0"] plus its
// fc4ic5fmaa:0:Enter-1/Enter-2 CUSTOM continuations in GeneratedMacroCode.php) only fires via
// a real level-up, not by seeding the champion directly onto the field -- the starting
// champion's CardID is patched to Zander, Prepared Scout (T3CIBknts0, level 1, NORM) to
// satisfy the level-1->2 gate and element/lineage match, 2 filler cards are seeded into
// memory to pay the 2-memory level-up cost, and the deck's Material section carries an extra
// "Zander, Deft Executor" entry so it's sitting in the material zone to level up into (the
// pregame starting-champion pick consumes Spirit of Fire from myMaterial-0, shifting Zander,
// Deft Executor down to myMaterial-0 for the real level-up click). An Assassin ATTACK card
// (Slice and Dice, 3jg01o26b4) is seeded into the graveyard as the only legal target for the
// "return an Assassin action or attack card from your graveyard to your hand" clause.
// Answering YES to "Remove_a_preparation_counter_from_Zander?" drops the preparation counters
// from 2 to 1 and searches the graveyard for ASSASSIN ACTION/ATTACK cards
// (fc4ic5fmaa:0:Enter-1's CardClasses/CardType filter), finding exactly the seeded card and
// moving it to hand once selected.
// Notes carried over from the original definition:
//   - Zander, Prepared Scout (level 1, NORM) - satisfies level 1->2 gate
//   - pays 2-memory level-up cost, card 1/2
//   - Slice and Dice (ASSASSIN ATTACK) - return-from-graveyard target
//   - level up to Zander, Deft Executor -- fires On Enter
//   - remove a preparation counter
//   - return Slice and Dice from graveyard to hand
gaScenario('zander-deft-executor-enter-prep-return-assassin')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Zander, Deft Executor
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(cardName: 'Zander, Prepared Scout')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myGraveyard', 'Slice and Dice')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'YES')->target('myGraveyard-0')
    ->tested('Zander, Deft Executor')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'CardID', 'fc4ic5fmaa')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'Zander, Deft Executor\'s On Enter ability (enterAbilities["fc4ic5fmaa:0"]) put 2 preparation counters on itself, then answering YES to the follow-up \'Remove a preparation counter from Zander?\' prompt (fc4ic5fmaa:0:Enter-1) removed 1, leaving exactly 1')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-7', 'CardID', '3jg01o26b4', 'fc4ic5fmaa:0:Enter-1\'s graveyard search (CardClasses contains ASSASSIN, CardType contains ACTION or ATTACK) found Slice and Dice as the sole legal target, and fc4ic5fmaa:0:Enter-2 moved it from the graveyard to hand once selected')
    ->expectProperty('p1Hand-8', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-1', 'Counters', '[]')
    ->expectProperty('p1Banish-1', 'TurnEffects', '[]')
    ->expectProperty('p1Material-0', 'CardID', 'm31WVJ9F04')
    ->expectProperty('p1Material-1', 'CardID', '9gv4vm4kj3')
    ->expectProperty('p1Material-2', 'CardID', 'LeyUk5auEP')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'LEVELED_UP_THIS_TURN')
    ->expectProperty('p2Hand-7', 'CardID', 'px60u5n1do')
    ->expectZoneCount('myHand', 9, 1)
    ->expectZoneCount('myGraveyard', 0, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Zephyr Assistant: On Leave resolves when the defender declines the 'Retaliate?' prompt (generic, not Slime-King-specific) ---
// Zephyr Assistant (XZFXOE9sEV, WIND/MAGE ally): "On Leave: Put an enlighten counter on your champion."
// A DIFFERENT defending unit with a LEAVE_FIELD trigger than Slime King, played for real from hand,
// then attacked by a 10-power Dungeon Guide; the defender declines 'Retaliate?' (PASS) so the unit
// dies while the defender's decision loop carries lastDecision == "PASS". The leave trigger must
// still resolve (champion gains the enlighten counter, EffectStack empty).
// Notes carried over from the original definition:
//   - MAGE champion: the [Class Bonus] is active (a bare class-bonus gate used to be always true)
//   - WIND lineage/element unlock
//   - Zephyr Assistant -> hand-7
//   - Dungeon Guide with 9 buff counters (10 power) -> p2 field-1
//   - play Zephyr Assistant
//   - its printed (2) reserve cost
//   - p1 passes the stack-response fast window so the card resolves
//   - end player 1 turn 1
//   - p1 passes the end-of-main fast window
//   - p1 passes the beginning-of-opponent-turn window
//   - player 2 attacks with the buffed Dungeon Guide
//   - Zephyr Assistant is the target
//   - p1 passes the before-damage fast window (a fast card is in hand)
//   - decline the 'Retaliate?' prompt: Zephyr Assistant dies, its On Leave must still resolve
gaScenario('zephyr-assistant-on-leave-resolves-when-retaliation-declined')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(classes: 'MAGE')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myHand', 'Zephyr Assistant')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 9]])
    ->play('Zephyr Assistant')->pay(2)->pass(1)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->pass(1)->pass(1)
    ->register();
