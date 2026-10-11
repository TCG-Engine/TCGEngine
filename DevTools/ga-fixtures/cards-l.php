<?php
/**
 * Fixtures for cards starting with 'L', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Luxem Sight: Draw a card (LUXEM element access + free activation) ---
// Luxem Sight is a LUXEM (advanced element) card, and no level 0 starting champion has an
// advanced element (CanPlayerMeetCardElementRequirements checks GetChampionLineage(), which
// walks the on-field champion's Subcards) — so it's illegal to play from hand on a fresh
// board. Patch the starting champion's Subcards directly to include a real LUXEM champion
// (Zander, Blinding Steel), which is what a genuine level-up into that lineage would leave
// behind, without scripting the full level-up sequence. This also grants access to its
// "[Element Bonus] whenever you reveal this card from your memory, recover 3" reveal trigger,
// but that trigger is not exercised here — reveals-from-memory are tied to the separate Imbue
// system and are out of scope for this fixture, which only covers the base "Draw a card"
// effect.
// Play Luxem Sight (myHand-7 with this deck/seed — verified live via DevTools discovery
// harness). Reserve cost is 0 and the effect needs no target, so it resolves immediately: no
// reserve MZCHOOSE and no opponent response window appear.
// Notes carried over from the original definition:
//   - Zander, Blinding Steel (LUXEM CHAMPION) - lineage/element unlock
gaScenario('luxem-sight-draw')
    ->standardDeck('Luxem Sight')
    ->readyFor('Luxem Sight')
    ->seed('myHand', 'Luxem Sight')
    ->play('Luxem Sight')
    ->register();

// --- Luminous Surge: buff target unit's next attack, recover 3 champion damage ---
// Luminous Surge is a LUXEM (advanced element) ACTION card, so — like Luxem Sight and Sabela
// above — the starting champion's Subcards are patched with a real LUXEM champion (Zander,
// Blinding Steel) to unlock element access. The starting champion is also pre-damaged (5) so
// the unconditional "Recover 3" half of the ability is observable as a Damage decrease, not
// just a no-op against 0 damage. Only the base always-available effect is covered; the
// [Class Bonus][Element Bonus] memory-reveal trigger is out of scope (tied to the separate
// memory-reveal subsystem, already documented as out of scope in luxem-sight-draw).
// Notes carried over from the original definition:
//   - LUXEM lineage/element unlock + pre-existing damage for the recover assertion
gaScenario('luminous-surge-buff-recover')
    ->standardDeck('Luminous Surge')
    ->champion(lineage: ['Zander, Blinding Steel'], damage: 5)
    ->seed('myHand', 'Luminous Surge')
    ->castSpell('Luminous Surge', pay: 2, target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","KOqdA7G6by_NEXT_3"]', 'Target champion\'s next attack this turn gets +3 power')
    ->expectDamage('p1Field-0', 2, 'Recover 3 reduces the champion\'s pre-existing 5 damage down to 2')
    ->expectDqEmpty()
    ->register();

// --- Life Essence Amulet: whenever an ally you control dies while it's not your turn, may banish to draw ---
// Regression test for a fixed bug: Life Essence Amulet's on-death offer was correctly queued
// (DoAllyDestroyed adds a LifeEssenceAmuletOffer CUSTOM decision) but was silently popped and
// discarded without ever running its handler. The decision is typically queued mid-cascade,
// after the player has already answered an unrelated "PASS" earlier in the same combat-damage
// resolution chain (e.g. declining the pre-damage Retaliate prompt); without dontSkipOnPass,
// the decision queue's stale $lastDecision=="PASS" from that earlier, unrelated answer caused
// this decision to be skipped too (see GrandArchiveSim/Custom/GameLogic.php -- the
// AddDecision call for LifeEssenceAmuletOffer now passes dontSkipOnPass:1).
// Life Essence Amulet's element is NORM, so no lineage patch is needed; it is seeded directly
// onto P1's field (REGALIA convention) along with a Dungeon Guide (ALLY, 3 life) that will die
// to trigger it. P2's champion Counters are patched with a 'potion_animate_power' override
// (5, exceeding the ally's 3 life) so it can deal lethal combat damage without needing to
// equip a weapon. P1 ends turn 1 so it becomes P2's turn (the "not your turn" condition holds
// for P1); P2 declines the material-phase prompt, then declares an attack with their champion
// against P1's Dungeon Guide (theirField-2 from P2's perspective) instead of P1's champion.
// P1 declines the pre-damage Retaliate window; the lethal hit destroys the ally during P2's
// turn, correctly triggering Life Essence Amulet's on-death offer for P1 (the non-active
// player) despite the stale PASS earlier in the same cascade. Answering YES banishes the
// amulet and draws a card.
// Notes carried over from the original definition:
//   - Life Essence Amulet (REGALIA)
//   - Dungeon Guide (ALLY, 3 life) - death fodder
//   - give P2's champion lethal power
gaScenario('life-essence-amulet-death-draw')
    ->standardDeck('Windslice')
    ->seed('myField', 'Life Essence Amulet')
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['potion_animate_power' => 5]], player: 2)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'theirField-2')->pass(1)->answer(1, 'YES')
    ->tested('Life Essence Amulet')
    ->expectZoneCount('myField', 1, 1, 'The amulet leaves the field (banished) after the ally dies on the opponent\'s turn')
    ->expectZoneCount('myHand', 8, 1, 'Banishing the amulet draws a card')
    ->expectDqEmpty()
    ->register();

// --- Lorraine, Wandering Warrior: champion level-up debug probe ---
// Investigated as a suspected engine bug (a prior session found champion level-up
// materialization getting stuck in a loop, the champion left in a broken state) but turned
// out to be correct behavior: that prior fixture never seeded any way to pay Lorraine's
// 1-memory level-up cost (no memory-zone card, no floating-memory graveyard card), so
// QueueMaterializePayment's own affordability check correctly failed and called
// AutoUndoMaterializeCostFailure(), which calls LoadVersion() to roll the whole attempt back
// to the pre-FSM snapshot -- not a bug, just an unpaid cost undoing the action, which then
// looks like a stuck loop when the same now-still-legal material choice is attempted again
// from the restored state. This fixture seeds a filler card directly into the memory zone as
// real cost fuel. Lorraine, Wandering Warrior is level 1, one level above the default level-0
// starting champion (Spirit of Fire), so she is a legal level-up target; champion-swap
// materialization is only offered through the material-phase MZMAYCHOOSE at the start of a
// turn, not via a generic FSM click at arbitrary times, so both players end their first turn
// (P1 -> P2) to reach that prompt for P1's turn 3. Choosing her pays the 1-memory cost from
// the seeded memory card and completes the swap in one step, preserving Spirit of Fire in her
// lineage (Subcards).
// Notes carried over from the original definition:
//   - filler card in memory to pay Lorraine's 1-memory level-up cost
gaScenario('lorraine-wandering-warrior-levelup')
    ->standardDeck('Windslice')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Lorraine, Wandering Warrior')
    ->expectProperty('myField-0', 'CardID', 'DpHDGaX2Pn', 'The champion levels up into Lorraine, Wandering Warrior')
    ->expectProperty('myField-0', 'Subcards', '["LMyKyVC2O9"]', 'The previous champion (Spirit of Fire) is preserved in the new champion\'s lineage')
    ->expectZoneCount('myMemory', 0, 1, 'The seeded memory card pays the 1-memory level-up cost')
    ->expectDqEmpty()
    ->register();

// --- Lorraine, Arclight Saber: On Enter LV static counters + 1 per banished arcane card ---
// Lorraine, Arclight Saber is level 3, one level above a level-2 champion. Rather than
// grinding out two real prior level-ups, the starting champion's CardID is patched directly
// to Lorraine, Blademaster (TJTeWcZnsQ, level 2) -- CanChampionLevelUpIntoCard only checks the
// CURRENT champion's own printed CardLevel, not lineage, so this satisfies the "current+1"
// legality gate for one real level-up into Lorraine, Arclight Saber. Her own On Enter ability
// (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, enterAbilities["x9sSpjpP3G:0"]) reads
// PlayerLevel($player) *after* she is already the field champion, so it correctly returns her
// own level (3) regardless of how she got there. Two ARCANE cards are seeded directly into
// banishment so the ability's "for each of up to seven arcane element cards in your
// banishment" clause adds 2 more, for 3+2=5 total static counters. Her 3-memory level-up cost
// is paid from 3 filler memory-zone cards.
// Notes carried over from the original definition:
//   - Lorraine, Blademaster (level 2, WARRIOR) - level-up precondition
//   - Fulminator, Rising Storm (ARCANE) - banished arcane card #1
//   - Surged Coordinator (ARCANE) - banished arcane card #2
gaScenario('lorraine-arclight-saber-enter-counters')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Arclight Saber
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
    ->champion(cardName: 'Lorraine, Blademaster')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myBanish', 'Fulminator, Rising Storm')
    ->seed('myBanish', 'Surged Coordinator')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Lorraine, Arclight Saber')
    ->expectProperty('myField-0', 'CardID', 'x9sSpjpP3G', 'The champion levels up into Lorraine, Arclight Saber')
    ->expectProperty('myField-0', 'Counters', '{"static":5}', 'On Enter puts LV (3) static counters, plus 1 per banished arcane card (2 seeded), for 5 total')
    ->expectZoneCount('myBanish', 5, 1, 'The 2 seeded arcane cards remain banished (only counted, not consumed) alongside the 3 memory-fuel cards banished to pay the level-up\'s memory cost')
    ->expectDqEmpty()
    ->register();

// --- Lorraine, Honed Operative: On Enter, banish up to 3 random memory cards and draw for each ---
// Previously abandoned: reaching her requires two sequential real champion level-ups (0 -> 1 -> 2),
// and investigating an "unexplained memory/hand discrepancy" after the first level-up was misread
// as cross-tool RNG nondeterminism. The real mechanism (GrandArchiveSim/Custom/GameLogic.php,
// RecollectionPhase(), comment "Must run BEFORE memory is returned to hand") is core game design:
// at the start of EVERY turn but each player's own turn 1, Recollection returns the ENTIRE memory
// zone to hand, before that turn's own Main phase. A one-time memory seed at pregame setup survives
// to fund the first level-up (turn 2's Materialize phase, which runs BEFORE turn 2's Recollection),
// but is fully returned to hand by turn 3's Recollection before the second level-up's Materialize
// phase can spend it -- memory has to be rebuilt during turn 2's Main phase (after turn 2's own
// Recollection already happened) to still be present for turn 3's Materialize phase. Harness Mana
// (G2XFRE8rFX, 0 reserve cost, "Put any amount of cards from your hand into your memory") is used
// as that refill: 0 reserve keeps hand math simple, and its MZMAYCHOOSE-loop shape (CardDQHandlers.php,
// customDQHandlers["HarnessManaLoop"]) is answered by repeating a myHand-0 choice, then declining.
// Separately, her own ability handler (CardDQHandlers.php,
// customDQHandlers["LorraineHonedOperativeBanishMemory"]) really was using plain PHP shuffle()
// instead of the engine's EngineShuffle() wrapper, bypassing deterministic RNG entirely -- fixed
// directly in that tracked, hand-maintained file (it is NOT one of the generated/gitignored
// per-app engine files) as part of landing this fixture.
// 1 filler card seeded directly into myMemory pays Wandering Warrior's 1-memory level-up cost
// on turn 2 (before turn 2's own Recollection has run). Harness Mana is seeded to a known hand
// slot so it's available without depending on the shuffle: played during turn 2's Main phase, it
// converts 3 more hand cards into memory, which -- unlike the pregame seed -- survive turn 3's
// Recollection (already passed for turn 2) to fund Honed Operative's 2-memory level-up cost on
// turn 3, with 1 left over for her own On Enter to actually banish and draw.
// Notes carried over from the original definition:
//   - Harness Mana
//   - level up to Wandering Warrior
//   - activate Harness Mana
//   - convert hand card 1/3 to memory
//   - convert hand card 2/3 to memory
//   - convert hand card 3/3 to memory
//   - stop the Harness Mana loop
//   - P2 declines their own turn-2 materialize choice
//   - level up to Honed Operative
//   - banish 1 (all that's available) from memory
gaScenario('lorraine-honed-operative-banish-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Lorraine, Honed Operative
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
    ->seed('myHand', 'Harness Mana')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->play('Harness Mana')->pay(3)->answer(1, '-')
    ->endTurn()->answer(2, '-')
    ->endTurn(2)->target('myMaterial-0')->answer(1, '1')
    ->tested('Lorraine, Honed Operative')
    ->register();

// --- Lakeside Serpent: [Class Bonus] +1 POWER per water card in graveyard (Pride 6) ---
// Lakeside Serpent is WATER -- the starting champion's CardID is patched directly to Silvie,
// With the Pack (TAMER, satisfies the Class Bonus) and its Subcards are ALSO patched with a
// real WATER champion (Nico, Rapture's Embrace) for element/lineage unlock. Two Freezing Hail
// copies (WATER) are seeded directly into the graveyard. Pride 6 (a combat-only restriction,
// same mechanic already covered generically in gray-wolf-pride-2-attack-rejected) is not
// separately re-tested here; Lakeside Serpent is seeded directly onto the field.
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER) + Nico (WATER unlock)
//   - Freezing Hail (WATER) #1
//   - Freezing Hail (WATER) #2
//   - Lakeside Serpent
//   - harmless no-op click
gaScenario('lakeside-serpent-class-bonus-water-graveyard-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack', lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myGraveyard', 'Freezing Hail')
    ->seed('myGraveyard', 'Freezing Hail')
    ->seed('myField', 'Lakeside Serpent')
    ->target('myField-0')
    ->tested('Lakeside Serpent')
    ->register();

// --- Library Witch: Intercept, On Death: draw a card ---
// Library Witch is NORM. Only the unconditional On Death: draw a card is covered here (Intercept
// is a shared static ability tested elsewhere, out of scope). Undeniable Truth's mandatory
// sacrifice-an-ally additional cost is reused as the kill trigger: choosing the sac target
// (GameLogic.php ~3218-3232, UndeniableTruthCost handler) directly sacrifices and queues its
// 1-reserve cost -- no YES/NO or Glimpse decision of its own (confirmed via direct probe; the
// extra YES/Glimpse steps in caretaker-drone-class-bonus-death-glimpse belong to THAT card's own
// On Death "Glimpse 4" effect, not to Undeniable Truth's generic cost flow).
// Notes carried over from the original definition:
//   - Library Witch
//   - sacrifice Library Witch as the mandatory cost
//   - pay Undeniable Truth's 1-reserve cost
gaScenario('library-witch-on-death-draw')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Library Witch')
    ->seed('myHand', 'Undeniable Truth')
    ->play('Undeniable Truth')->target('myField-1')->pay(1)
    ->tested('Library Witch')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'iD8qbpA8z5')
    ->expectProperty('p1Graveyard-1', 'CardID', 'UaUfw7yFTW')
    ->expectProperty('p1Graveyard-1', 'Counters', '[]')
    ->expectProperty('p1Graveyard-1', 'TurnEffects', '[]')
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 2, 1)
    ->expectZoneCount('myMemory', 1, 1)
    ->expectZoneCount('myHand', 8, 1, "On Death: Library Witch drew a card. Hand: 8 seeded cards - Undeniable Truth - 1 reserve + 1 (Undeniable Truth's draw) + 1 (Library Witch) = 8; without the trigger it would be 7")
    ->register();

// --- Lorraine, Blademaster: On Enter, attacks +2 POWER and gain On Kill: Draw a card ---
// Lorraine, Blademaster's ability is an On Enter trigger (enterAbilities["TJTeWcZnsQ:0"]),
// so it must actually be leveled into via a real level-up event, not a direct CardID patch --
// but CanChampionLevelUpIntoCard only allows target level == current level + 1, so a direct
// 0->2 jump is illegal. The starting champion is CardID-patched to Lorraine, Wandering
// Warrior (level 1) first, then leveled up for real (level 1 -> 2) on P1's turn 3 material
// phase, paying the 2-memory cost from 2 seeded myMemory filler cards. The On Enter ability
// tags the champion with the TJTeWcZnsQ TurnEffect, which CombatLogic.php reads for both the
// +2 POWER on attacks and the On Kill: Draw grant -- asserted directly via TurnEffects
// rather than scripting a full attack, matching the minimal-assertion philosophy established
// for other "until end of turn" grants.
gaScenario('lorraine-blademaster-enter-attack-buff')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Lorraine, Blademaster
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
    ->champion(cardName: 'Lorraine, Wandering Warrior')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-1')
    ->tested('Lorraine, Blademaster')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'CardID', 'TJTeWcZnsQ')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN","TJTeWcZnsQ"]', 'On Enter tagged the champion: its attacks get +2 POWER and On Kill: draw a card this turn')
    ->expectProperty('p1Banish-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Banish-1', 'Counters', '[]')
    ->expectProperty('p1Banish-1', 'TurnEffects', '[]')
    ->expectProperty('p1Material-1', 'CardID', 'm31WVJ9F04')
    ->expectProperty('p1Material-2', 'CardID', '9gv4vm4kj3')
    ->expectProperty('p1Material-3', 'CardID', 'LeyUk5auEP')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'LEVELED_UP_THIS_TURN')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Lorraine, Crux Knight: attacks +1 POWER per regalia weapon in banishment ---
// Lorraine, Crux Knight's ability is a static computed-power case (GameLogic.php ~11019,
// not an Enter trigger), so the champion is CardID-patched directly to it. NfbZ0nouSQ has no
// printed POWER (CardPower returns -1) -- a champion attacking with 0 total power never
// reaches the damage step at all (confirmed via a temporary error_log instrumentation of the
// ObjectCurrentPower switch case, since reverted: with no weapon, the pre-declaration "does
// this attacker have positive power" check runs while CombatAttacker is still unset, so the
// bonus can't count toward it, and the attack silently resolves with no damage step ever
// running -- the same "reports success but nothing happens" class of issue as the Rai-deck
// Anger the Skies investigation). Rather than fight that pre-check by adding a real weapon
// (which risks conflating the measurement with the weapon's own abilities, as a first draft
// using Warrior's Longsword discovered -- its own [Class Bonus] +1 POWER stacked with this
// card's bonus and killed the target outright), the CombatAttacker DecisionQueueController
// variable is set directly via the 'dqVariables' setup primitive (same technique as
// Samaritan's Reach's existing fixture) so ObjectCurrentPower's NfbZ0nouSQ case evaluates
// exactly as it would mid-combat, with zero scripted combat actions. A REGALIA weapon
// (Warrior's Longsword) is seeded into myBanish; computed_power_equals on the champion's own
// mzId directly asserts the resulting +1 POWER.
// A single harmless mode=100 PASS with no pending decision is included purely so the test
// runner (RunIntegrationTests.php) loads the root runtime at all -- it only does so as a
// side effect of replaying at least one EngineRunAction, and a zero-action fixture crashes
// its own step-0 assertion check with "Call to undefined function GetZoneObject()" before
// ever reaching the replay loop. Confirmed via direct probing that this PASS does not touch
// the injected CombatAttacker variable or the champion's computed power.
// Notes carried over from the original definition:
//   - Warrior's Longsword (REGALIA,WEAPON) in banish -- the +1 POWER bonus source
gaScenario('lorraine-crux-knight-banished-weapon-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Crux Knight')
    ->seed('myBanish', 'Warrior\'s Longsword')
    ->dqVariables(['CombatAttacker' => 'myField-0'])
    ->pass(1)
    ->tested('Lorraine, Crux Knight')
    ->register();

// --- Lesser Boon of Shou: As gained, put an enlighten counter on your champion (rSIXf50oBc) ---
gaScenario('lesser-boon-of-shou-enlighten-on-play')
    ->deck($fillerDeck)
    ->seed('myHand', 'Lesser Boon of Shou')
    ->play('Lesser Boon of Shou')->answer(1, '-')->pass(2)->passBoth()
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"enlighten":1}', 'Playing Lesser Boon of Shou fires its on-play trigger (playCardAbilities["rSIXf50oBc:0"], only reachable via QueuePlayCardTriggeredAbility() from OnCardActivated()) and puts an enlighten counter on the champion')
    ->expectProperty('p1Field-1', 'CardID', 'rSIXf50oBc')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 7, 1)
    ->expectZoneCount('myField', 2, 1)
    ->register();

// --- Lesser Boon of Kanaloa: both players discard 3 (P8sbt2gXkn) ---
// The on-play trigger (both players discard 3) fires immediately once reserve is paid -- no
// Opportunity-window passes needed or possible here. A PASS submitted against the pending
// discard MZCHOOSE is treated by DiscardChosenCard as "discard nothing" (it explicitly no-ops
// on "-"/""/"PASS"), silently eating one of the six discard rounds per stray PASS -- see the
// note on astral-seal-negate-activation-banish for the general pattern.
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
gaScenario('lesser-boon-of-kanaloa-both-discard-3')
    ->deck($fillerDeck)
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myHand', 'Lesser Boon of Kanaloa')
    ->play('Lesser Boon of Kanaloa')->pay(6)->pay(3, 2)
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'P8sbt2gXkn')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Graveyard-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Graveyard-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-2', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 1, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myGraveyard', 3, 1, 'Playing Lesser Boon of Kanaloa fires its on-play trigger (playCardAbilities["P8sbt2gXkn:0"]) and discards exactly 3 cards from the controller\'s own hand')
    ->expectZoneCount('myMemory', 3, 1)
    ->expectZoneCount('theirHand', 4, 1)
    ->expectZoneCount('theirGraveyard', 3, 1, 'The same trigger also discards exactly 3 cards from the opponent\'s hand')
    ->register();

// --- Lesser Boon of Territories: scavenge 10 for a Domain card (ZpM7gliLxm) ---
// The scavenge choice fires immediately once reserve is paid (see the note on
// astral-seal-negate-activation-banish); a PASS against that pending MZCHOOSE is treated by
// ScavengeChoose as "decline" and silently skips the scavenge instead of answering it.
gaScenario('lesser-boon-of-territories-scavenge-domain')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Stocked Outpost
4 Stocked Outpost
4 Stocked Outpost
4 Stocked Outpost
DECK
    )
    ->seed('myHand', 'Lesser Boon of Territories')
    ->castSpell('Lesser Boon of Territories', pay: 3, target: 'myTempZone-0')
    ->register();

// --- Lightveil Agent: whenever you recover, put a buff counter on CARDNAME ---
// Lightveil Agent's element is LUXEM; the starting champion's CardID is patched directly to
// Zander, Blinding Steel (LUXEM) so Bathe in Light (also LUXEM) can be legally activated. The
// champion's Damage is pre-set to 4 so Bathe in Light's Recover 4 is a real, observable
// recover event that fires the unconditional (no Class Bonus needed) recover listener in
// GameLogic.php (~line 18447), which puts a buff counter on Lightveil Agent.
// Notes carried over from the original definition:
//   - Zander, Blinding Steel (LUXEM) + damage precondition
//   - Lightveil Agent
gaScenario('lightveil-agent-recover-buff-counter')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Blinding Steel', damage: 4)
    ->seed('myField', 'Lightveil Agent')
    ->seed('myHand', 'Bathe in Light')
    ->castSpell('Bathe in Light', pay: 2)
    ->tested('Lightveil Agent')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 0)
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Recovering puts a buff counter on Lightveil Agent')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'd9zax2g20h')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'BATHE_IN_LIGHT_RECOVER')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Lurking Assailant: has stealth as long as it's awake ---
// Lurking Assailant's element is NORM, so no lineage patch is needed. Rule 1.h blocks the
// game's first player from attacking on turn 1, so player 1 ends turn 1 and player 2 attacks
// with a Dungeon Guide (1 POWER) on their own turn 1 instead. Lurking Assailant is seeded onto
// player 1's field, awake (Status 2) -- its unconditional stealth-while-awake (HasStealth() in
// GameLogic.php) is proven negatively: the attacker cannot target it directly (only the
// champion is a legal target, since HasTrueSight is false), even though it would otherwise be
// a legal attack target like any other awake unit.
// Notes carried over from the original definition:
//   - Lurking Assailant (stealth while awake)
//   - Dungeon Guide (1 POWER) - actual attacker
//   - awake, can attack
//   - ends turn 1 (first-player attack lock)
//   - declare attack with Dungeon Guide
//   - legal: target the champion instead
gaScenario('lurking-assailant-stealth-while-awake')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Lurking Assailant')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Cannot target a stealthed, awake Lurking Assailant without True Sight')
    ->answer(2, 'theirField-0')->answer(1, '-')
    ->tested('Lurking Assailant')
    ->expectDqEmpty(step: 5)
    ->expectDamage('p1Field-0', 1, 'Combat lands on the champion instead, since the stealthed Lurking Assailant can\'t be targeted', step: 5)
    ->register();

// --- Lunar Seer: [CB] REST: Glimpse 2 ---
// Lunar Seer is seeded directly onto the field (its own On Enter isn't the clause under
// test -- the [Class Bonus] REST: Glimpse 2 ability is). The champion is CardID-patched to
// Arisanna, Astral Zenith (q3huqj5bba, CLERIC) so IsClassBonusActive($player,["CLERIC"]) is
// satisfied (activateAbilityPrereqs["qjt0ooffy4:0"], GeneratedMacroCode.php). Field items
// with an activated ability are not clickable via a plain myField-N!FSM! action -- the
// ability is offered as a fast-action MZMAYCHOOSE opportunity once the turn player attempts
// to pass, answered with the encoded '{mzID}@Activate-{abilityIndex}@{label}' choice string
// (same shape as cosmic-astroscope-rest-glimpse). The MZREARRANGE response keeps the
// original deck order (a no-op reorder), confirming Glimpse 2 fired and resolved cleanly.
// Notes carried over from the original definition:
//   - Arisanna, Astral Zenith - CLERIC Class Bonus unlock
//   - Lunar Seer - the activator
gaScenario('lunar-seer-rest-glimpse-cb')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Arisanna, Astral Zenith')
    ->seed('myField', 'Lunar Seer')
    ->endTurn()->answer(1, '-')->answer(1, 'myField-1@Activate-0@Rest')
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->tested('Lunar Seer')
    ->expectProperty('p1Field-1', 'Status', '1', 'Activating Rest exhausts Lunar Seer as its cost', step: 3)
    ->expectZoneCount('myDeck', 5, 1, 'Glimpse only looks and reorders the top of the deck -- deck count is unchanged')
    ->register();

// --- Lorraine, Spirit Ruler: On Enter -- put a cheap Sword regalia from banishment onto field w/ +3 durability ---
// Lorraine, Spirit Ruler's On Enter (enterAbilities["n2TKqNaODR:0"] plus its n2TKqNaODR:0:Enter-1
// CUSTOM continuation in GeneratedMacroCode.php) searches the banishment for a Sword regalia card
// with memory cost <=1 and puts it onto the field with 3 additional durability counters. The
// level-up gate (CanChampionLevelUpIntoCard, GameLogic.php) only checks level number, not the
// "Lorraine Lineage" flavor text, so the starting champion is patched directly to Lorraine,
// Blademaster (TJTeWcZnsQ, level 2) to satisfy the level 2->3 gate, 3 filler cards are seeded into
// memory to pay the 3-memory level-up cost, and the deck's Material section carries an extra
// "Lorraine, Spirit Ruler" entry so it's sitting in the material zone to level up into (the
// pregame starting-champion pick consumes Spirit of Fire from myMaterial-0, shifting Lorraine,
// Spirit Ruler down to myMaterial-0 for the real level-up click). Clarent, Sword of Peace
// (m31WVJ9F04, REGALIA/WEAPON, subtypes WARRIOR,SWORD, memory cost 1) is seeded into the
// banishment as the sole legal target for the MZMAYCHOOSE.
// Notes carried over from the original definition:
//   - Lorraine, Blademaster (level 2) - satisfies level 2->3 gate
//   - pays 3-memory level-up cost, card 1/3
//   - Clarent, Sword of Peace (Sword regalia, memory cost 1) - the sole legal On Enter target
//   - level up to Lorraine, Spirit Ruler -- fires On Enter
//   - choose Clarent from banishment
//   - decline Clarent's own [Class Bonus] prevent-damage fast action window
gaScenario('lorraine-spirit-ruler-enter-banish-sword-durability')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Spirit Ruler
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK
    )
    ->champion(cardName: 'Lorraine, Blademaster')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myBanish', 'Clarent, Sword of Peace')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myBanish-0')->pass(1)
    ->tested('Lorraine, Spirit Ruler')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'CardID', 'n2TKqNaODR')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Field-1', 'CardID', 'm31WVJ9F04', 'Lorraine, Spirit Ruler\'s On Enter (enterAbilities["n2TKqNaODR:0"] plus its n2TKqNaODR:0:Enter-1 CUSTOM continuation) found Clarent, Sword of Peace as the sole legal Sword-regalia/cost<=1 target in the banishment and moved it onto the field once selected')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '{"durability":5}', 'Clarent enters the field with its printed 2 durability counters plus the 3 additional durability counters n2TKqNaODR:0:Enter-1\'s AddCounters($player, ..., "durability", 3) call grants, totaling 5')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Banish-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-1', 'Counters', '[]')
    ->expectProperty('p1Banish-1', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-2', 'Counters', '[]')
    ->expectProperty('p1Banish-2', 'TurnEffects', '[]')
    ->expectProperty('p1Material-0', 'CardID', 'm31WVJ9F04')
    ->expectProperty('p1Material-1', 'CardID', '9gv4vm4kj3')
    ->expectProperty('p1Material-2', 'CardID', 'LeyUk5auEP')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'LEVELED_UP_THIS_TURN')
    ->expectProperty('p2Hand-7', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// Lavaplume Fatestone: On Enter, as a Spell, deal X unpreventable damage to target unit, X = other
// Fatestone/Fatebound objects controlled.
// Notes carried over from the original definition:
//   - Two OTHER Fatestone items so X = 2.
//   - MZCHOOSE: deal X (=2) damage to the opponent's champion.
gaScenario('lavaplume-fatestone-enter-x-unpreventable-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(lineage: ['Spirit of Fire'])
    ->seed('myField', 'Craggy Fatestone')
    ->seed('myField', 'Craggy Fatestone')
    ->patch('myHand-0', ['CardID' => '0w5xyjuczy'])
    ->playSlot(0)->pay(2)->target('theirField-0')
    ->tested('Lavaplume Fatestone')
    ->expectDamage('theirField-0', 2, 'X (=2 other Fatestone objects) unpreventable damage dealt to the targeted champion')
    ->register();

// Light the Hunt: Put two buff counters on target Animal or Beast ally you control.
// Notes carried over from the original definition:
//   - Light the Hunt is LUXEM; patch in Zander, Blinding Steel's lineage for element access.
//   - MZCHOOSE: target the Cheerful Slime (Animal ally).
gaScenario('light-the-hunt-buff-two-counters')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(lineage: ['Zander, Blinding Steel'])
    ->seed('myField', 'Cheerful Slime')
    ->patch('myHand-0', ['CardID' => 'edjgscy50x'])
    ->playSlot(0)->pay(2)->target('myField-1')
    ->tested('Light the Hunt')
    ->expectProperty('myField-1', 'Counters', '{"buff":2}', 'Light the Hunt puts two buff counters on the targeted Animal ally')
    ->register();

// --- Luminous Quartz (40lgjj1yS3): [Sheen 12+] REST, Remove a preparation counter from your
// champion: As a Spell, deal 1+X damage to target unit, where X is the amount of sheen counters
// on it. Activate this ability only at slow speed. ---
// NOTE (engine bug, fixed): customDQHandlers["40lgjj1yS3:0:ActivateAbility-1"]
// (GeneratedCode/GeneratedMacroCode.php ~27053) called RemoveCounters($player, $champMZ, ...) but
// never assigned $champMZ in that handler (it was only computed locally inside the separate
// $activateAbilityAbilities/$activateAbilityPrereqs closures) -- a genuine "Undefined variable
// $champMZ" warning fired and the preparation-counter cost was silently never paid, even though
// the damage always resolved correctly. Fixed via an override of the same dispatch key in
// GrandArchiveSim/Custom/GeneratedAbilityOverrides.php that computes $champMZ with
// FindChampionMZ($player) before calling RemoveCounters(). The champion is seeded with a
// preparation counter below (patchMzId myField-0) so this fixture can assert the counter is
// actually removed.
// Notes carried over from the original definition:
//   - Target ally with 3 sheen counters, so the resulting 1+3=4 damage is distinctive.
gaScenario('luminous-quartz-sheen12-rest-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Luminous Quartz
# Main
10 Dungeon Guide
DECK
    )
    ->patch('myField-0', ['Counters' => ['preparation' => 1]])
    ->seed('myMastery', 'Fractured Memories', properties: ['Counters' => ['sheen' => 12]])
    ->seed('myField', 'Luminous Quartz', properties: ['Status' => 2])
    ->seed('theirField', 'Dungeon Guide', properties: ['Counters' => ['sheen' => 3]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->tested('Luminous Quartz')
    ->register();

// --- Lustrous Slime: On Enter, reveal Slime cards from memory for buff counters ---
// Lustrous Slime's On Enter ability (enterAbilities['ejvddohjdu:0'] -> LustrousSlimeEnter() ->
// LustrousSlimeRevealLoop()/LustrousSlimeApplyReveals(), Custom/CardDQHandlers.php) repeatedly
// offers an MZMAYCHOOSE to reveal one not-yet-chosen Slime-subtype card from memory; once no
// more eligible candidates remain it applies AddCounters(..., 'buff', revealCount) in one shot.
// One Red Slime (mttsvbgl6f, SLIME subtype) is seeded directly into myMemory as the sole reveal
// candidate, so after it's chosen the loop's next pass finds zero remaining targets and applies
// exactly 1 buff counter with no further decisions. Lustrous Slime's element is LUXEM, not
// native to the 'Spirit of Fire' starting champion, so the champion's Subcards are patched with
// a real LUXEM champion (Zander, Blinding Steel) purely to unlock element access. Lustrous
// Slime is played for real from hand via FSM so its Enter trigger genuinely fires. Only the
// Enter clause is covered; the separate On Death "Recover X equal to buff counters" clause
// (allyDestroyedAbilities['ejvddohjdu:0']) is a different trigger and out of scope here.
// Notes carried over from the original definition:
//   - LUXEM lineage/element unlock
//   - Red Slime, the sole reveal candidate
//   - reveal the seeded Red Slime from memory
gaScenario('lustrous-slime-enter-reveal-buff')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Zander, Blinding Steel'])
    ->seed('myMemory', 'Red Slime')
    ->seed('myHand', 'Lustrous Slime')
    ->castSpell('Lustrous Slime', pay: 2, target: 'myMemory-0')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Lustrous Slime\'s On Enter puts exactly 1 buff counter on itself for the 1 Slime card revealed from memory')
    ->expectDqEmpty()
    ->register();

// --- Limitless Slime: [Class Bonus][Level 3+] On Attack, put a buff counter on itself ---
// Limitless Slime's ENTIRE testable ability is its [Class Bonus][Level 3+] On Attack trigger
// (onAttackAbilities['s4vxfy51ec:0'], GeneratedCode/GeneratedMacroCode.php): gated behind
// IsClassBonusActive($player, ['TAMER']) && PlayerLevel($player) >= 3, it puts a buff counter
// on itself. The champion's CardID is patched directly to Silvie, Slime Sovereign (mdwbkuhtjm,
// level 3, TAMER) to satisfy BOTH the Class Bonus and the level gate in one step; its element
// is TERA but Limitless Slime's own element is NORM, so no Subcards lineage patch is needed.
// Limitless Slime (s4vxfy51ec) is seeded directly onto the field -- this is fine for proving
// its OWN On Attack trigger (a different trigger than Enter, which this card has no printed
// clause for at all). It then declares a real attack (via FSM) against player 1's champion so
// the On Attack trigger genuinely fires; player 1 ends turn 1 first since ATTACK-adjacent rule
// 1.h blocks the first turn's player from attacking (same shape as
// baby-red-slime-class-bonus-on-attack-discard-fire-power). The separate [Class Bonus][Level
// 1+] Floating Memory and [Class Bonus][Level 2+] +1 POWER/+2 LIFE clauses are static/
// computed-effect clauses with no stored counter or flag to assert and are out of scope here.
// Notes carried over from the original definition:
//   - Silvie, Slime Sovereign (level 3, TAMER) - Class Bonus + level precondition
//   - Limitless Slime -> myField-1
//   - end player 1's turn 1
//   - Limitless Slime declares an attack
//   - target player 1's champion
gaScenario('limitless-slime-class-bonus-level3-on-attack-buff')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Silvie, Slime Sovereign
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
    ->champion(cardName: 'Silvie, Slime Sovereign', player: 2)
    ->seed('myField', 'Limitless Slime', player: 2)
    ->endTurn()->passBoth()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->tested('Limitless Slime')
    ->expectProperty('p2Field-1', 'Counters', '{"buff":1}', '[Class Bonus][Level 3+] On Attack puts a buff counter on Limitless Slime itself')
    ->expectDqEmpty()
    ->register();

// Cost modifiers: the affordability check uses the ACTUAL calculated reserve cost, not the printed one.
// Lead with Force (yhu0djqlp8, TAMER skill, printed reserve 3, [Class Bonus] costs 1 less). The champion is
// patched to a MAGE/TAMER Nameless Champion so the Class Bonus is active: with only TWO other hand cards
// the printed cost 3 is unaffordable but the discounted cost 2 is exactly affordable.
// Notes carried over from the original definition:
//   - Nameless Champion (MAGE, TAMER): Class Bonus active for TAMER cards
//   - Lead with Force (printed reserve 3, discounted 2) -> p1 myHand-0
//   - 1: play Lead with Force (discounted reserve 2, two payers)
//   - 4: pass the fast-action opportunity
gaScenario('hand-activation-class-bonus-discount-affordable-at-discounted-cost')
    ->deck($mordredDeck)
    ->champion(cardName: '#K7jYO9IibV', lineage: ['Spirit of Water'])
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Lead with Force')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)->pay(2)->pass(1)
    ->tested('Lead with Force')
    ->register();

// The same hand WITHOUT the Class Bonus: the printed cost 3 applies and is not affordable -> refused.
// Notes carried over from the original definition:
//   - Lead with Force (printed reserve 3; the Spirit of Water champion has no TAMER Class Bonus) -> p1 myHand-0
//   - 1: click Lead with Force (reserve 3, two payers): accepted as a no-op, refusal asserted on state
gaScenario('hand-activation-class-bonus-inactive-printed-cost-unaffordable-click-refused')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Lead with Force')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)
    ->tested('Lead with Force')
    ->register();

// --- Luminescent Slash (and Mordred, Aurelian Regent): a lone Luminescent Slash costs 5 and deals 6; activating a luxem card deleveles Mordred ---
// Luminescent Slash (y8BNOi4rwD, LUXEM WARRIOR/SWORD attack, reserve 5, power 6): "[Mordred Bonus]
// This card costs 2 less to activate for each other attack card you've activated this turn.
// [Mordred Bonus] Luminescent Slash gets +2 POWER for each other attack card you've activated
// this turn." Mordred, Aurelian Regent (XPl2UAO9se, level 3, LUXEM): "Whenever you activate a luxem
// element card, delevel Mordred." The champion is patched to Aurelian Regent (lineage Flawless
// Blade, Burnished Avenger, Spirit of Water). With no other attack card activated this turn
// Luminescent Slash costs its full FIVE reserve payments and deals 6; activating it (a luxem card)
// deleveles the champion: Mordred, Aurelian Regent goes back to the material deck and the champion
// becomes Mordred, Flawless Blade.
// Notes carried over from the original definition:
//   - Mordred, Aurelian Regent (level 3, LUXEM)
//   - Luminescent Slash -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Luminescent Slash
//   - attack player 2's champion
gaScenario('luminescent-slash-lone-attack-full-cost-and-regent-deleveled')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Aurelian Regent', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myHand', 'Luminescent Slash')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Luminescent Slash', pay: 5, target: 'theirField-0')
    ->tested('Luminescent Slash', 'Mordred, Aurelian Regent')
    ->expectZoneCount('myMemory', 5, 1, 'Exactly FIVE reserve payments: no other attack card was activated, so no discount')
    ->expectDamage('p2Field-0', 6, 'Luminescent Slash dealt its printed 6 (no +2 without another attack card)')
    ->expectProperty('p1Field-0', 'CardID', 'WI2owxIw0z', 'Activating the luxem Luminescent Slash deleveled Mordred: Aurelian Regent -> Flawless Blade')
    ->expectProperty('p1Field-0', 'Subcards', '["OWCdWq3mXY","tafqldAGRF"]', 'The lineage shifted down one level')
    ->expectZoneCount('myMaterial', 8, 1, 'The demoted Mordred, Aurelian Regent went back to the material deck (7 -> 8)')
    ->expectDqEmpty()
    ->register();

// --- Luminescent Slash: after another attack card it costs 2 less and gets +2 POWER ---
// Luminescent Slash (y8BNOi4rwD): "[Mordred Bonus] This card costs 2 less to activate for each other
// attack card you've activated this turn. [Mordred Bonus] +2 POWER for each other attack card you've
// activated this turn." The champion is Aurelian Regent. Dauntless Assault (its [Mordred Bonus] On
// Attack wakes the champion up) is activated first (3 reserve, 4 damage); then Luminescent Slash
// costs 5 - 2 = THREE reserve payments and deals 6 + 2 = 8: player 2's champion has taken 4 + 8 =
// 12 damage in total.
// Notes carried over from the original definition:
//   - Mordred, Aurelian Regent (level 3, LUXEM -> Exalted enabled)
//   - Dauntless Assault -> p1 myHand-7
//   - Luminescent Slash -> p1 myHand-8
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Dauntless Assault first
//   - attack player 2's champion (the On Attack wakes the champion up)
//   - activate Luminescent Slash (it shifted to myHand-4)
//   - attack player 2's champion again
gaScenario('luminescent-slash-after-another-attack-costs-two-less-and-gets-plus-two')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Aurelian Regent', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myHand', 'Dauntless Assault')
    ->seed('myHand', 'Luminescent Slash')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Dauntless Assault', pay: 3, target: 'theirField-0')
    ->playSlot(4)->pay(3)->target('theirField-0')
    ->tested('Luminescent Slash')
    ->expectZoneCount('myMemory', 6, 1, '6 reserve payments in total = 3 for Dauntless Assault + THREE for Luminescent Slash (5 - 2)')
    ->expectDamage('p2Field-0', 12, '4 (Dauntless Assault) + 8 (Luminescent Slash: printed 6 + 2) damage')
    ->expectProperty('p1Graveyard-1', 'CardID', 'y8BNOi4rwD', 'Luminescent Slash resolved into the graveyard')
    ->expectDqEmpty()
    ->register();

// --- Lost in Thought: declining a further banish still draws for the cards already banished (dontSkipOnPass regression) ---
// Lost in Thought (egbscxwjbq): "You may banish up to X cards with floating memory from your graveyard. Draw a card for each card banished this way." Stopping early (declining the 'Banish another floating memory card?' prompt) must still draw one card per card already banished.
// Regression: LostInThoughtBanish's loop re-queue (Custom/CardDQHandlers.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler on the PASS answer and its PASS branch (Draw for the banished count) never ran. The first prompt (queued by the generated ability) needs no flag: declining with 0 banished draws nothing either way.
// Two Lost in Thought copies (floating memory) are seeded in the graveyard (the third floating card in the graveyard is the played copy itself); the champion gets Spirit of Water lineage for WATER element access. Lost in Thought is played from hand, one graveyard copy is banished, then the further offer is declined.
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
//   - floating memory card #1 -> myGraveyard-0
//   - floating memory card #2 -> myGraveyard-1
//   - banish the first floating memory card
//   - decline banishing another
gaScenario('lost-in-thought-decline-still-draws')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Lost in Thought')
    ->seed('myGraveyard', 'Lost in Thought')
    ->seed('myHand', 'Lost in Thought')
    ->play('Lost in Thought')->target('myGraveyard-0')->pass(1)
    ->register();

// --- Lustrous Slime: declining a further reveal still puts a buff counter per Slime already revealed (dontSkipOnPass regression) ---
// Lustrous Slime (ejvddohjdu): "On Enter: Reveal any amount of Slime cards from your memory. For each card revealed this way, put a buff counter on CARDNAME." Stopping early (declining the next 'Reveal a Slime card from memory?' prompt) must still put one buff counter per Slime already revealed.
// Regression: LustrousSlimeRevealLoop() queued its paired CUSTOM 'LustrousSlimeReveal|mz' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its PASS branch (LustrousSlimeApplyReveals -> the buff counters) never ran: revealing one Slime then declining another left Lustrous Slime with no counters. (With only one Slime in memory the loop applies the counters itself once no candidate remains, which is why lustrous-slime-enter-reveal-buff never saw this.)
// Two Red Slimes are seeded in memory; one is revealed, the second declined with PASS. LUXEM element access via a Subcards patch (same as lustrous-slime-enter-reveal-buff).
// Notes carried over from the original definition:
//   - LUXEM lineage/element unlock
//   - Red Slime #1 (reveal candidate) -> myMemory-0
//   - Red Slime #2 (reveal candidate)
//   - reserve 1/2
//   - reserve 2/2
//   - reveal the first Red Slime from memory
//   - decline revealing the second Slime
gaScenario('lustrous-slime-decline-further-reveal-keeps-counters')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Zander, Blinding Steel'])
    ->seed('myMemory', 'Red Slime')
    ->seed('myMemory', 'Red Slime')
    ->seed('myHand', 'Lustrous Slime')
    ->play('Lustrous Slime')->pay(2)->target('myMemory-0')->pass(1)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '1 Slime revealed, further reveal declined: Lustrous Slime still gets exactly 1 buff counter')
    ->expectDqEmpty()
    ->register();

// top four, top to bottom: Dungeon Guide, Mourning Veilbound (SPECTER), Fluffy Shopkeep, Night Barker (SPECTER)
gaScenario('lure-the-abyss-reveal-four-specters-to-graveyard-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Lure the Abyss')
    ->deckTop(['Dungeon Guide', 'Mourning Veilbound', 'Fluffy Shopkeep', 'Night Barker'])
    ->castSpell('Lure the Abyss', pay: 2)
    ->register();

// none of the top four is a Specter: all four go to the bottom (before the fix only the top card moved)
gaScenario('lure-the-abyss-reveal-four-no-specter-all-four-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Lure the Abyss')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep'])
    ->castSpell('Lure the Abyss', pay: 2)
    ->register();

gaScenario('lure-the-abyss-reveal-four-all-specters-all-to-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Lure the Abyss')
    ->deckTop(['Mourning Veilbound', 'Night Barker', 'Mourning Veilbound', 'Night Barker'])
    ->castSpell('Lure the Abyss', pay: 2)
    ->register();

// PLAYER 2 seating (perspective check): the stack sits under player 2's turn-start draw (a filler Dungeon Guide)
// player 2 holds a castable spell when player 1 passes, so two opportunity windows (end of main, beginning of end phase)
// prompt it before its own turn starts: decline both
gaScenario('lure-the-abyss-player-two-reveal-four-specters-to-graveyard-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Lure the Abyss', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Mourning Veilbound', 'Fluffy Shopkeep', 'Night Barker'], player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Lure the Abyss', 2)->pay(2, 2)
    ->register();

// top six: DG, FS, Fairy Whispers (SPELL), DG, Fairy Whispers (SPELL), FS; then Charge the Soul (marker)
// Notes carried over from the original definition:
//   - reveal the 5th card (Fairy Whispers) into memory
gaScenario('lucia-reclaimed-blight-enter-look-six-reveal-deep-spell-to-memory-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon')
    ->seed('myHand', 'Lucia, Reclaimed Blight')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Fairy Whispers', 'Fluffy Shopkeep', 'Charge the Soul'])
    ->castSpell('Lucia, Reclaimed Blight', pay: 2, target: 'myDeck-4')
    ->register();

gaScenario('lucia-reclaimed-blight-enter-look-six-decline-all-six-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon')
    ->seed('myHand', 'Lucia, Reclaimed Blight')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Fairy Whispers', 'Fluffy Shopkeep', 'Charge the Soul'])
    ->castSpell('Lucia, Reclaimed Blight', pay: 2, window: 'me')
    ->register();

// no Spell card among the top six: no prompt, all six to the bottom
gaScenario('lucia-reclaimed-blight-enter-look-six-no-spell-all-six-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon')
    ->seed('myHand', 'Lucia, Reclaimed Blight')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep', 'Charge the Soul'])
    ->castSpell('Lucia, Reclaimed Blight', pay: 2)
    ->register();

// PLAYER 2 seating (perspective check): the stack sits under player 2's turn-start draw (a filler Dungeon Guide)
gaScenario('lucia-reclaimed-blight-player-two-enter-look-six-reveal-deep-spell-to-memory-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon', player: 2)
    ->seed('myHand', 'Lucia, Reclaimed Blight', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Fairy Whispers', 'Fluffy Shopkeep', 'Charge the Soul'], player: 2)
    ->endTurn()
    ->play('Lucia, Reclaimed Blight', 2)->pay(2, 2)->answer(2, 'myDeck-4')
    ->register();

// top four, top to bottom: DG, FS, FW, Hidden Longbowman (RANGER, 4th); then Charge the Soul (marker: must be the new top card)
gaScenario('lena-rest-look-four-reveal-deep-ranger-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Deadly Duelist', lineage: ['Spirit of Water'])
    ->seed('myField', 'Lena, Dorumegia\'s Herald')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Hidden Longbowman', 'Charge the Soul'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myTempZone-3')->pay(4)
    ->tested('Lena, Dorumegia\'s Herald')
    ->register();

gaScenario('lena-rest-look-four-decline-all-four-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Deadly Duelist', lineage: ['Spirit of Water'])
    ->seed('myField', 'Lena, Dorumegia\'s Herald')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Hidden Longbowman', 'Charge the Soul'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)->pay(4)
    ->tested('Lena, Dorumegia\'s Herald')
    ->register();

gaScenario('lena-rest-look-four-no-ranger-all-four-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Deadly Duelist', lineage: ['Spirit of Water'])
    ->seed('myField', 'Lena, Dorumegia\'s Herald')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Fairy Whispers', 'Charge the Soul'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)
    ->tested('Lena, Dorumegia\'s Herald')
    ->register();

// Lucenia's Reign (zrvvwz3ww9): "(2), Discard a Chessman Command card: Target Chessman ally you control gets +1 LIFE until end of turn. Draw a card into your memory." The discard and the (2) are costs paid before the
// opponent gets priority; the phantasia is not rested (no [REST] in the cost).
// Notes carried over from the original definition:
//   - Lucenia's Reign -> myField-1
//   - Pawn Piece (CHESSMAN ally) -> myField-2
//   - Sacrifice Play (a Chessman Command card) -> myHand-7
gaScenario('lucenia-reign-pays-two-and-discards-a-chessman-command-as-the-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Lucenia\'s Reign')
    ->seed('myField', 'Pawn Piece')
    ->seed('myHand', 'Sacrifice Play')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myHand-7')->pay(2)->target('myField-2')
    ->tested('Lucenia\'s Reign')
    ->covers(['activated-ability', 'additional-cost-discard', 'auto-rest'], '(2), Discard a Chessman Command card: Target Chessman ally you control gets +1 [LIFE] until end of turn. Draw a card into your memory.', 'An activated ability only rests its source when its cost includes [REST].')
    ->register();

// Leporine Masque (pgysz2zfji): "(6), Banish Leporine Masque: Draw a card into your memory. This ability costs (X) less where X is the amount of omens you have."
// Notes carried over from the original definition:
//   - Leporine Masque -> myField-1
//   - two omens: the ability costs (4)
gaScenario('leporine-masque-costs-one-less-per-omen')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Leporine Masque')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Fluffy Shopkeep', properties: ['Counters' => ['omen' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)
    ->tested('Leporine Masque')
    ->expectZoneCount('myMemory', 5, 1, 'Four reserve payments (6 - 2 omens) plus the card drawn into memory')
    ->expectZoneCount('myBanish', 3, 1, 'Two omens and Leporine Masque')
    ->expectZoneCount('myHand', 3, 1, 'Seven cards minus four payments')
    ->expectDqEmpty()
    ->covers(['activated-ability', 'cost', 'omen'], '(6), Banish Leporine Masque: Draw a card into your memory. This ability costs (X) less to activate, where X is the amount of omens you have.')
    ->register();

gaScenario('lurid-dreaming-at-the-opponents-end-phase-wakes-the-champion-and-draws-into-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Status' => 1, 'Counters' => ['_overrides' => ['classes' => 'CLERIC']]])
    ->seed('myHand', 'Lurid Dreaming')
    ->endTurn()
    ->endTurn(2)->target('myHand-7')->pay(2)
    ->tested('Lurid Dreaming')
    ->register();

gaScenario('lurid-dreaming-is-refused-during-your-own-main-phase')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Status' => 1, 'Counters' => ['_overrides' => ['classes' => 'CLERIC']]])
    ->seed('myHand', 'Lurid Dreaming')
    ->play('Lurid Dreaming')
    ->register();

// Lesser Boon of Revelry (AOFRjoIHVe): "(3): Target player that controls exactly one ally puts a buff counter on an ally they control without a buff counter on it." Choosing the player read
// $myAllies/$theirAllies (undefined) in the handler: no counter was ever placed. Each player controls exactly one Dungeon Guide.
// Notes carried over from the original definition:
//   - Lesser Boon of Revelry -> field-1
//   - Dungeon Guide -> field-2
//   - Dungeon Guide -> theirField-1
gaScenario('lesser-boon-of-revelry-targeting-yourself-puts-a-buff-counter-on-your-only-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Lesser Boon of Revelry')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->answer(1, 'YES')->pass(1)
    ->tested('Lesser Boon of Revelry')
    ->register();

// Notes carried over from the original definition:
//   - Lesser Boon of Revelry -> field-1
//   - Dungeon Guide -> field-2
//   - Dungeon Guide -> theirField-1
gaScenario('lesser-boon-of-revelry-targeting-the-opponent-puts-a-buff-counter-on-their-only-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Lesser Boon of Revelry')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->answer(1, 'NO')->pass(1)
    ->tested('Lesser Boon of Revelry')
    ->register();

// Notes carried over from the original definition:
//   - three Caretaker Horses -> myField-1..3
//   - Legendary Saddle -> myField-4
gaScenario('legendary-saddle-banishes-itself-and-draws-into-memory-with-three-horses')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Caretaker Horse')
    ->seed('myField', 'Caretaker Horse')
    ->seed('myField', 'Caretaker Horse')
    ->seed('myField', 'Legendary Saddle')
    ->action(1, 10001, 'myField-4!CustomInput!Activate:0')->pass(1)
    ->tested('Legendary Saddle', 'Caretaker Horse')
    ->expectZoneCount('myBanish', 1, 1, 'Legendary Saddle banished itself')
    ->expectZoneCount('myMemory', 1, 1, 'A card was drawn into memory')
    ->expectZoneCount('myField', 4, 1, 'champion + three Horses')
    ->expectDqEmpty()
    ->register();

// Lesser Boon of Zerusa (UC9byG4aD5): "(2): Put a preparation counter on your champion. This ability costs (1) more to activate for each time you've activated it this game." Second activation costs (3).
gaScenario('lesser-boon-of-zerusa-costs-one-more-each-time-it-is-activated')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Lesser Boon of Zerusa')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Lesser Boon of Zerusa')
    ->register();

// Lesser Boon of Bullets (UI0lAtGQBb): "(2): Materialize a Bullet card from your material deck. This ability costs (1) more to activate for each time you've activated it this game." Steel Slug is the Bullet.
gaScenario('lesser-boon-of-bullets-pays-two-and-materializes-a-bullet')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Steel Slug
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Lesser Boon of Bullets')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myMaterial-0')->pass(1)
    ->tested('Lesser Boon of Bullets', 'Steel Slug')
    ->register();

// Lesser Boon of Rosen (fJJBJ9M4c4): "(3): Summon a Powercell token rested. Activate this ability only once." The second activation is refused.
gaScenario('lesser-boon-of-rosen-pays-three-summons-a-powercell-once-only')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Lesser Boon of Rosen')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Lesser Boon of Rosen')
    ->register();

// Lesser Boon of Rakko (V8aPGgLyh5): "(3): Activate this ability only twice. Depending on D6+D6 ..." Two activations are paid, the third is refused.
gaScenario('lesser-boon-of-rakko-pays-three-each-time-and-only-twice')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Lesser Boon of Rakko')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->target('myHand-1')->pay(1, 2)->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Lesser Boon of Rakko')
    ->register();

// Lost Promises (gN8uFKSip0): "[Alice Bonus] Deal 3 damage to target attacking unit, then end the combat phase." Same defender-frame defect as Visceral Inversion / Scorching Trap.
// Notes carried over from the original definition:
//   - UMBRA + Alice lineage, CLERIC
//   - Lost Promises -> p1 myHand-7
//   - Dungeon Guide, the attacker -> p2 field-1
//   - end player 1 turn
//   - player 1 passes the two end-of-turn windows
//   - player 2 attacks with the Dungeon Guide
//   - ... targeting player 1's champion
//   - player 1 answers from the priority window with Lost Promises
//   - reserve 2, then pass the two windows so it resolves
gaScenario('lost-promises-defender-deals-three-to-the-attacker-and-ends-combat')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Tristan, Shadowdancer', 'Alice, Golden Queen'], classes: 'CLERIC')
    ->seed('myHand', 'Lost Promises')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myHand-7')->pay(2)->pass(1)->pass(1)
    ->tested('Lost Promises')
    ->register();
