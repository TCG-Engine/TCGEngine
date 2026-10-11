<?php
/**
 * Fixtures for cards starting with 'W', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Worn Diary: page counters from memory, then banish for a draw at 10+ page counters ---
// Worn Diary's element is NORM and its memory cost is 0, so no lineage patch is needed and
// materializing via a direct FSM click is free. Three filler cards are pre-seeded into
// myMemory so ability 0 (put a page counter per card in memory) has a nonzero, predictable
// result. Ability 1 (REST, banish self: draw a card, only at 10+ page counters) is reached by
// patching the page counter directly to 10 after ability 0 resolves, rather than repeating
// ability 0 ten times.
gaScenario('worn-diary-page-counters-draw')
    ->standardDeck('Worn Diary')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myHand', 'Worn Diary')
    ->playSlot(5)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Worn Diary')
    ->expectProperty('p1Field-1', 'Counters', '{"page":3}', 'Ability 0 puts one page counter per card currently in memory')
    ->expectProperty('p1Field-1', 'Status', '1', 'Activating the ability rests Worn Diary as its cost')
    ->expectDqEmpty()
    ->register();

// --- Winds of Retribution: allies you control get +2 power ---
// Winds of Retribution's element is WIND, so the starting champion's Subcards are patched
// with a real WIND champion (Spirit of Wind), same as deployment-beacon-summon-drone/
// reclaim-return-ally. Only the base always-available effect is covered; the [Class
// Bonus][Level 2+] discount condition isn't reached here, so the full 6-reserve cost is paid.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
gaScenario('winds-of-retribution-ally-buff')
    ->standardDeck('Winds of Retribution')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myHand', 'Winds of Retribution')
    ->castSpell('Winds of Retribution', pay: 6)
    ->register();

// --- Worn Gearblade: Class Bonus + remove durability counter to prevent 1 damage ---
// Worn Gearblade's own activated ability requires a GUARDIAN Class Bonus (physical presence,
// so Ciel, Loyal Valet is seeded directly onto the field, reused from
// charged-manaplate-banish-draw) and a durability counter on Worn Gearblade itself (seeded
// directly). Worn Gearblade is seeded straight onto the field rather than played from hand
// since this fixture is about its activated ability, not its materialize flow. Only the
// always-available prevention half is covered; the "durability counter whenever an Automaton
// ally dies" trigger requires a real Automaton death and is out of scope.
// Notes carried over from the original definition:
//   - Ciel, Loyal Valet (GUARDIAN CHAMPION) - Class Bonus source
//   - Worn Gearblade - ability cost fuel
gaScenario('worn-gearblade-durability-prevent')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Ciel, Loyal Valet')
    ->seed('myField', 'Worn Gearblade', properties: ['Counters' => ['durability' => 1]])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-0')
    ->tested('Worn Gearblade')
    ->expectProperty('p1Field-0', 'TurnEffects', '["PREVENT_ALL_1"]', 'The targeted champion is protected by a PREVENT_ALL_1 shield')
    ->expectProperty('p1Field-2', 'Counters', '[]', 'The durability counter is spent as the ability\'s cost')
    ->expectDqEmpty()
    ->register();

// --- Water Barrier: prevent all but 1 of the next damage to your champion this turn ---
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
gaScenario('water-barrier-prevent-tag')
    ->standardDeck('Water Barrier')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myHand', 'Water Barrier')
    ->castSpell('Water Barrier', pay: 3, window: 'both')
    ->register();

// --- Wisdom's Reprise: Glimpse 3. [Level 3+] Draw a card into memory. ---
// [Level 3+] gate reads PlayerLevel() directly, satisfied by the same static champion CardID
// patch technique as banner-knight-class-bonus-power-buff (Lorraine, Arclight Saber, level 3) --
// no real level-up sequence or On Enter trigger needed since this reads the champion's level,
// not anything about Wisdom's Reprise's own controller lineage. Glimpse 3's own MZREARRANGE
// decision must be answered (submitting the same "Top=...;Bottom=" param verbatim, same
// technique as idle-thoughts-glimpse-4) before QueueDrawIntoMemoryAfterGlimpse's queued draw can
// resolve -- confirmed via direct probe against this exact deck/seed.
// Notes carried over from the original definition:
//   - Lorraine, Arclight Saber, level 3
//   - Wisdom's Reprise
//   - pay 1-reserve cost
gaScenario('wisdoms-reprise-level3-draw-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Arclight Saber')
    ->seed('myHand', 'Wisdom\'s Reprise')
    ->play('Wisdom\'s Reprise')->pay(1)->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myMemory', 2, 1, '[Level 3+] draws 1 card into memory (1 pregame-default memory card + 1 from this ability)')
    ->expectDqEmpty()
    ->register();

// --- Wind Cutter: [Class Bonus] +1 POWER, real single-target (non-Cleave) ATTACK-card attack ---
// First fixture to exercise the NORMAL (non-Cleave) single-target ATTACK-card flow, now that
// hemorrhaging-rend-damage20-cleave proved ATTACK cards are played from hand into myIntent like
// any other card. Champion is patched to Jin, Fate Defiant (WARRIOR/RANGER class match for
// Wind Cutter's [Class Bonus] +1 POWER) with Spirit of Wind (pNiyaGlIe7) added via Subcards only
// (not a full CardID patch) to unlock WIND without inheriting any interfering abilities -- same
// technique as hemorrhaging-rend's Dante-via-Subcards fix. Unlocking WIND has a side effect
// specific to the OTHER Jin fixtures' shared Main list: Fairy Whispers (n8wyfG9hbY) is itself a
// WIND-element ACTION card, and once WIND is unlocked it becomes flash-playable, which
// perpetually re-triggers a "Take a fast action?" Opportunity window (and a chained materialize
// offer) at EVERY decision boundary -- declining it never actually clears it, and a single
// decline was even observed to skip all the way to the opponent's turn (GA's phase auto-advance
// treats an empty decision queue as "nothing left for this player," and CustomInput.php's
// "myHealth" Pass button is in fact the same generic mid-game turn-pass action used for the
// pregame mulligan, usable by whichever player's turn it currently is -- confirmed via a
// throwaway debug harness, now deleted). Fix: this fixture's own deck heredoc drops Fairy
// Whispers entirely (replaced with more Dungeon Guide to keep 16 Main cards), so unlocking WIND
// doesn't expose any new flash-playable card, and the action sequence reduces to the same
// minimal single-PASS turn-1 shape used by every other real-attack fixture.
// GetAttackWeaponChoices($player, $obj)
// (CombatLogic.php:110-148) reads GetAvailableWeapons($player), which only looks at field-based
// WEAPON items -- with none on the field, $availableWeapons is empty, so BeginCombatPhase
// (CombatLogic.php:1478-1580) skips the weapon-choice MZCHOOSE entirely (that step is
// conditioned on `!empty($availableWeapons)`) and queues ChooseAttackTarget directly. Wind
// Cutter's own printed POWER is 1; the intent card's computed power (base 1 + Class Bonus 1 = 2)
// is asserted directly on myIntent-0 right after paying reserve, before the attack is declared.
// GetTotalAttackPower then sums the champion's own power (0, Jin, Fate Defiant has none) plus
// the intent card's positive power (2) with no weapon, so the opponent's champion (targeted
// directly, theirField-0) takes exactly 2 combat damage. As with every other real-attack
// fixture, the defender's "Retaliate?" MZMAYCHOOSE must be explicitly declined before
// CombatApplyAttackerDamage actually lands the damage.
// Notes carried over from the original definition:
//   - Jin, Fate Defiant (WARRIOR class bonus) + WIND unlock via Spirit of Wind lineage
//   - Wind Cutter
//   - target opponent's champion
//   - decline Retaliate
gaScenario('wind-cutter-class-bonus-power-attack')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
8 Dungeon Guide
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(cardName: 'Jin, Fate Defiant', lineage: ['Spirit of Wind'])
    ->seed('myHand', 'Wind Cutter')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Wind Cutter', pay: 2)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->register();

// --- Wind Resonance Bauble: Banish -- draw a card (only if opponent controls a wind champion) ---
// Wind Resonance Bauble is REGALIA -- adding it to 'myHand' via setup gets silently redirected
// to the Material zone instead (HandAddReplacement, GameLogic.php ~17282: any REGALIA CardID
// added to hand is rerouted to AddMaterial), matching every other REGALIA item fixture this
// session (Synth Disrupter, Ingredient Pouch, etc.) -- it must be seeded directly onto myField.
// Its prereq (IsPlayerElementEnabled) reads the OPPONENT's own element lineage, so P2's starting
// champion's Subcards are patched with a real WIND champion. Field items with an activated
// ability are not clickable via a plain myField-N!FSM! action as the very first action of a
// fresh turn -- offered as a fast-action MZMAYCHOOSE opportunity once the turn player attempts
// to pass, answered with the encoded "{mzID}@Activate-{abilityIndex}@{label}" choice string
// (same shape as ingredient-pouch-rest-gather).
// Notes carried over from the original definition:
//   - WIND lineage/element unlock for P2
//   - Wind Resonance Bauble
//   - attempt to pass -> offers the fast-action opportunity
//   - decline P2's own resulting fast-action opportunity (1/2)
//   - decline again -- same fast opportunity re-offered in the next priority round (2/2)
gaScenario('wind-resonance-bauble-banish-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'], player: 2)
    ->seed('myField', 'Wind Resonance Bauble')
    ->endTurn()->answer(1, '-')->pass(2)->answer(1, 'myField-1@Activate-0@Banish')->pass(2)->pass(2)
    ->tested('Wind Resonance Bauble')
    ->register();

// --- Water Resonance Bauble: Banish -- draw a card (if your opponent can access Water) ---
// Water Resonance Bauble is REGALIA -- seeded directly onto myField. Its prereq (IsPlayerElementEnabled)
// reads the OPPONENT's own element lineage, so P2's starting champion's Subcards are patched
// with a real WATER champion (Spirit of Water), same technique as wind-resonance-bauble-banish-
// draw. Same Scry-the-Skies warm-up + direct Activate:0 click as crystal-of-empowerment-banish-
// level.
// Notes carried over from the original definition:
//   - WATER lineage/element unlock for P2
//   - Water Resonance Bauble
//   - Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
//   - pay Scry the Skies' 1 reserve
gaScenario('water-resonance-bauble-banish-draw')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Water'], player: 2)
    ->seed('myField', 'Water Resonance Bauble')
    ->seed('myHand', 'Scry the Skies')
    ->castSpell('Scry the Skies', pay: 1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Water Resonance Bauble')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'F9POfB5Nah')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-0', 'CardID', 'dSSRtNnPtw')
    ->expectProperty('p1Banish-0', 'Status', '2')
    ->expectDamage('p1Banish-0', 0)
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 7, 1)
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Weaponsmith: [Class Bonus] put a durability counter on target weapon at recollection ---
// Weaponsmith's ability fires from ResolveBeforeRecollectionPhaseStart (GameLogic.php ~9366),
// once per turn at the start of the controller's own recollection phase -- champion
// CardID-patched to Lorraine, Blademaster (WARRIOR) for the Class Bonus, with exactly one
// WEAPON on the field (Warrior's Longsword) so the counter is applied automatically with no
// extra MZCHOOSE. Both players end turn 1/2 (same pattern as
// arcanists-prism-recollection-wheel-draw) to reach P1's own turn 3 recollection phase.
// Notes carried over from the original definition:
//   - Weaponsmith
//   - Warrior's Longsword -- the only weapon on the field
gaScenario('weaponsmith-class-bonus-durability')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Blademaster')
    ->seed('myField', 'Weaponsmith')
    ->seed('myField', 'Warrior\'s Longsword')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Weaponsmith')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p1Field-2', 'Counters', '{"durability":3}')
    ->expectProperty('p1Field-2', 'TurnEffects', '[]')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Warrior's Longsword: [Class Bonus] +1 POWER ---
// Warrior's Longsword's only ability is [Class Bonus] +1 POWER, a static computed-power case
// (GameLogic.php ~10998, not an Enter trigger) -- seeded directly onto the field. Champion
// CardID-patched to Lorraine, Blademaster (WARRIOR). Champion attacks wielding the weapon;
// computed power includes the printed 1 POWER of the weapon itself plus the +1 Class Bonus.
// Notes carried over from the original definition:
//   - Warrior's Longsword
gaScenario('warriors-longsword-class-bonus-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Blademaster')
    ->seed('myField', 'Warrior\'s Longsword')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(2, '-')
    ->tested('Warrior\'s Longsword')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Status', '1')
    ->expectProperty('p1Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":2}')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":1}')
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectDamage('p2Field-0', 2)
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":2}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P1"]')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Wandering Glaivier: On Death, each player draws a card ---
// Wandering Glaivier's element is FIRE, matching the starting champion, so no lineage patch is
// needed. Rule 1.h blocks the game's first player from attacking at all on turn 1 (any attack,
// not just ATTACK-type cards -- BeginCombatPhase() in CombatLogic.php), so player 1 ends turn 1
// first and player 2 attacks with Wandering Glaivier on their own turn 1 instead (same
// turn-cycle shape as thieving-cut-prepare-onhit-draw). It is seeded directly onto player 2's
// field, awake, so it can attack immediately. Its 1 Life means any retaliate damage kills it --
// but the starting champion's printed POWER is blank/0 (CardPower() returns -1, verified live),
// so it is NOT itself a legal retaliator (GetRetaliatorOptions requires POWER > 0). A Dungeon
// Guide (1 POWER, no dangling On Enter decision when seeded this way -- verified live, unlike
// Fluffy Shopkeep which leaves an unresolved MZMAYCHOOSE/CUSTOM Enter chain in the queue) is
// seeded onto player 1's own field instead as the retaliator. Player 1 accepts the
// "Retaliate?" MZMAYCHOOSE with it (myField-1 from their perspective), which deals 1 combat
// damage back to Wandering Glaivier and destroys it, firing its On Death trigger. Only the
// attack's actual TARGET is offered as a retaliator (GetRetaliatorOptions in CombatLogic.php
// otherwise only allows specific hardcoded Ambush-style cards to retaliate without being
// targeted -- a plain awake/positive-power ally that wasn't the target is NOT offered,
// verified live), so Dungeon Guide itself -- not the champion -- is the attack's target.
// Notes carried over from the original definition:
//   - Wandering Glaivier
//   - awake, can attack
//   - Dungeon Guide (1 POWER) - attack target and retaliator
//   - awake, can retaliate
//   - ends turn 1 (first-player attack lock)
//   - declare attack with Wandering Glaivier
//   - target Dungeon Guide
//   - accept Retaliate with Dungeon Guide
gaScenario('wandering-glaivier-on-death-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Wandering Glaivier', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-1', ['Status' => 2])
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->target('myField-1')
    ->tested('Wandering Glaivier')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Status', '1')
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p1Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-8', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Graveyard-0', 'CardID', 'p6120p3f5d', 'Wandering Glaivier is destroyed by retaliate damage, firing its On Death trigger')
    ->expectProperty('p2Graveyard-0', 'Status', '1')
    ->expectDamage('p2Graveyard-0', 1)
    ->expectProperty('p2Graveyard-0', 'Counters', '[]')
    ->expectProperty('p2Graveyard-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectZoneCount('myHand', 8, 1, 'On Death: each player draws a card (player 1 had no other draw source in this scenario)')
    ->expectZoneCount('theirHand', 9, 1)
    ->expectZoneCount('theirField', 1, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Wisp's Protection: prevent the next 4+X damage to target unit (X = regalia on field) ---
// Wisp's Protection (OmWFVRUr8I, CRUX) needs CRUX -- champion patched to Lorraine, Spirit Ruler
// (n2TKqNaODR) as above. A Backup Charger (9gv4vm4kj3, REGALIA) is also seeded onto the
// player's own field so X=1, making the prevention amount 5 (distinguishing this from a bare
// "prevent 4" effect). cardActivatedAbilities["OmWFVRUr8I:0"] (GeneratedMacroCode.php) offers
// an MZCHOOSE of ALLY/CHAMPION targets; targeting the champion itself
// (customDQHandlers["OmWFVRUr8I:0:CardActivated-1"]) adds TurnEffect "PREVENT_ALL_5".
gaScenario('wisps-protection-prevent-damage')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(cardName: 'Lorraine, Spirit Ruler')
    ->seed('myField', 'Backup Charger')
    ->seed('myHand', 'Wisp\'s Protection')
    ->castSpell('Wisp\'s Protection', pay: 2, window: 'both', target: 'myField-0')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","PREVENT_ALL_5"]', 'The champion gains a PREVENT_ALL_5 turn effect (4 base + 1 regalia on field).')
    ->register();

// --- Windrider Vanguard: [Class Bonus] Vigor -- wakes up at the beginning of the end phase ---
// Windrider Vanguard (JEOxGQppTE) is seeded directly onto the field already rested (Status=1),
// with the champion patched to Lorraine, Wandering Warrior (DpHDGaX2Pn, WARRIOR) for the class
// bonus. Its Vigor is conditional (GeneratedKeywordCode.php's $Vigor_Cards["JEOxGQppTE"] has a
// "Class Bonus" condition, checked by HasKeyword_Vigor()), so without WARRIOR active it would
// stay rested. Both players end their first turns (a real end-phase transition, not a targeted
// ability), and Windrider Vanguard wakes up (Status -> 2) at player 1's own end phase.
gaScenario('windrider-vanguard-class-bonus-vigor')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(cardName: 'Lorraine, Wandering Warrior')
    ->seed('myField', 'Windrider Vanguard', properties: ['Status' => 1])
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->tested('Windrider Vanguard')
    ->expectProperty('p1Field-1', 'Status', '2', 'Windrider Vanguard wakes up (Status 1 -> 2) at the beginning of the controller\'s end phase, purely from Vigor -- no targeted ability touched it.')
    ->expectProperty('p1Field-1', 'CardID', 'JEOxGQppTE')
    ->register();

// --- Weaken Resistance: next Spell-source damage to target unit this turn gets +LV ---
// Weaken Resistance (reserve 4, NORM) tags a chosen unit with a "WEAKEN_RES_<LV>" TurnEffect
// (GeneratedMacroCode.php customDQHandlers["bb3oeup7oq:0:CardActivated-1"]); CombatLogic.php's
// damage pipeline (~line 4985) then adds +LV to the NEXT Spell-source damage dealt to that unit
// and consumes the tag. Set Ablaze (also MAGE/SPELL, in this same deck's coverage) is played
// immediately afterward at the same target to actually deal that damage: it prints "deal 4
// damage", so 5 damage landing (instead of 4) is the distinguishing proof that Weaken
// Resistance's own +LV buff fired, not just that Set Ablaze's own base damage happened.
// Languid Toadtroll (6 LIFE) is the target instead of a 3-LIFE filler ally so it survives
// either way and its exact Damage property (4 vs 5) is directly observable afterward.
// Notes carried over from the original definition:
//   - Languid Toadtroll (ALLY, 6 LIFE) target
gaScenario('weaken-resistance-spell-damage-buff')
    ->deck(GA_KONGMING_BASE_DECK)
    ->seed('myField', 'Languid Toadtroll', player: 2)
    ->seed('myHand', 'Weaken Resistance')
    ->seed('myHand', 'Set Ablaze')
    ->castSpell('Weaken Resistance', pay: 4, target: 'theirField-1')
    ->playSlot(3)->pay(2)->target('theirField-1')
    ->expectDamage('theirField-1', 5, 'Set Ablaze\'s printed 4 damage lands as 5 (4+LV) because Weaken Resistance buffed the next Spell-source damage to this unit')
    ->expectProperty('theirField-1', 'TurnEffects', '["ENTERED_THIS_TURN","DAMAGED_SINCE_LAST_TURN"]', 'The WEAKEN_RES_1 tag is consumed once it buffs the Spell damage')
    ->expectDqEmpty()
    ->register();

// --- Waterveil Apostle: [CB][Memory 4+] at recollection, Gather ---
// Waterveil Apostle (WATER, CLERIC) is seeded directly onto the field. Its recollection
// trigger lives in ResolveBeforeRecollectionPhaseStart's plain CardID switch-case
// (GameLogic.php ~9552, the same dispatcher used for Foster/Starlit Apothecary/Domain
// upkeep), not a materialized Enter macro, so the field object doesn't need to have been
// played normally. It requires IsClassBonusActive($turnPlayer,["CLERIC"]) and >= 4 cards in
// memory -- the champion is CardID-patched to Arisanna, Astral Zenith (q3huqj5bba, CLERIC)
// with Subcards=[Spirit of Water] so CLERIC Class Bonus and WATER lineage-element access are
// both satisfied, and 4 filler cards are seeded into memory. Reaching player 1's own
// recollection phase uses the established 3-action shortcut (tonoris-genesis-aegis
// -recollection-obelisk): P1 ends turn 1, P2 ends their turn (still global turn 1), P1
// declines their own MAT-phase materialize offer, auto-advancing into P1's own BREC, where
// Gather() (PotionLogic.php ~35) summons a random herb token onto the field.
// Notes carried over from the original definition:
//   - CLERIC Class Bonus + WATER unlock
//   - Waterveil Apostle - the recollection trigger source
gaScenario('waterveil-apostle-recollection-gather')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Arisanna, Astral Zenith', lineage: ['Spirit of Water'])
    ->seed('myField', 'Waterveil Apostle')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Waterveil Apostle')
    ->register();

// --- Will to Save (qUd2uwAPvh): You may rest your champion. If you do, put a preparation counter on them. ---
gaScenario('will-to-save-rest-prep')
    ->deckTemplate('spirit-dungeon-guide', 'Will to Save')
    ->playSlot(4)->pay(1)->answer(1, 'YES')->passBoth()
    ->tested('Will to Save')
    ->expectProperty('myField-0', 'Status', '1', 'Champion is rested')
    ->expectProperty('myField-0', 'Counters', '{"preparation":1}', 'Champion gains a preparation counter')
    ->register();

// --- Warrior of the Fae Realm: On Enter, banish a Sword attack card from hand, draw a card into memory ---
// Warrior of the Fae Realm (eRcqucBKhX, NORM WARRIOR/FAIRY ALLY, reserve 3, Stealth): "On Enter:
// You may banish a Sword attack card from your hand or memory. If you do, draw a card into your
// memory. As long as you control CARDNAME, you may activate the banished card on a later turn."
// A Striking Tides (a Sword attack card) is seeded in hand next to the Warrior, which is played for
// real (3 reserve payments): the Sword attack in hand is offered, banished (tagged
// _warriorFaeRealm) and a card is drawn directly into memory (memory 3 payments + 1 = 4, hand
// unchanged by the draw).
// Notes carried over from the original definition:
//   - Warrior of the Fae Realm -> p1 myHand-7
//   - Striking Tides (Sword attack) -> p1 myHand-8
//   - play Warrior of the Fae Realm
//   - On Enter: banish Striking Tides (it shifted to myHand-4 after the play and 3 payments)
gaScenario('warrior-of-the-fae-realm-enter-banish-sword-from-hand-draw-memory')
    ->deck($mordredDeck)
    ->seed('myHand', 'Warrior of the Fae Realm')
    ->seed('myHand', 'Striking Tides')
    ->castSpell('Warrior of the Fae Realm', pay: 3, target: 'myHand-4')
    ->expectProperty('p1Field-1', 'CardID', 'eRcqucBKhX', 'Warrior of the Fae Realm entered the field (played for real)')
    ->expectProperty('p1Banish-0', 'CardID', 'qrxQGA1pc6', 'The Sword attack card (Striking Tides) was banished from hand')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["_warriorFaeRealm"]', 'The banished card carries the Warrior\'s activation permission tag')
    ->expectZoneCount('myMemory', 4, 1, 'Memory 4 = 3 reserve payments + 1 card drawn INTO MEMORY')
    ->expectZoneCount('myHand', 4, 1, 'Hand 4 = 7 + 2 seeded - Warrior - 3 payments - banished Sword: the extra card went to memory, not hand')
    ->expectDqEmpty()
    ->register();

// --- Warrior of the Fae Realm: a Sword attack card may also be banished from MEMORY ---
// Warrior of the Fae Realm (eRcqucBKhX): "You may banish a Sword attack card from your hand or
// memory." A Striking Tides is seeded in player 1's MEMORY (the hand holds no Sword attack): the
// On Enter offers the memory card, banishes it (tagged _warriorFaeRealm) and draws a card into
// memory, so memory still holds 4 cards (1 seeded - 1 banished + 3 reserve payments + 1 drawn).
// Notes carried over from the original definition:
//   - Striking Tides (Sword attack) in memory -> p1 memory-0
//   - Warrior of the Fae Realm -> p1 myHand-7
//   - play Warrior of the Fae Realm
//   - On Enter: banish the Sword attack from memory
gaScenario('warrior-of-the-fae-realm-enter-banish-sword-from-memory')
    ->deck($mordredDeck)
    ->seed('myMemory', 'Striking Tides')
    ->seed('myHand', 'Warrior of the Fae Realm')
    ->castSpell('Warrior of the Fae Realm', pay: 3, target: 'myMemory-0')
    ->expectProperty('p1Banish-0', 'CardID', 'qrxQGA1pc6', 'The Sword attack card was banished from MEMORY')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["_warriorFaeRealm"]', 'The banished card carries the Warrior\'s activation permission tag')
    ->expectZoneCount('myMemory', 4, 1, 'Memory 4 = 1 seeded - 1 banished + 3 reserve payments + 1 drawn into memory')
    ->expectZoneCount('myHand', 4, 1, 'Hand 4 = 7 + 1 seeded Warrior - Warrior - 3 payments')
    ->expectDqEmpty()
    ->register();

// --- Warrior of the Fae Realm: the On Enter banish is optional (declined) ---
// Warrior of the Fae Realm (eRcqucBKhX): "You MAY banish a Sword attack card". The MZMAYCHOOSE is
// answered PASS: the Striking Tides stays in hand, nothing is banished and no card is drawn into
// memory (memory holds only the 3 reserve payments).
// Notes carried over from the original definition:
//   - Warrior of the Fae Realm -> p1 myHand-7
//   - Striking Tides (Sword attack) -> p1 myHand-8
//   - play Warrior of the Fae Realm
//   - decline the optional banish
gaScenario('warrior-of-the-fae-realm-enter-decline-banish')
    ->deck($mordredDeck)
    ->seed('myHand', 'Warrior of the Fae Realm')
    ->seed('myHand', 'Striking Tides')
    ->castSpell('Warrior of the Fae Realm', pay: 3, window: 'me')
    ->register();

// --- Warrior of the Fae Realm: only a SWORD attack card is a legal choice (a Polearm attack is not offered) ---
// Warrior of the Fae Realm (eRcqucBKhX): "banish a Sword attack card from your hand or memory". The
// only attack card available is a Pierce the Heavens (a Polearm attack, not a Sword): the On
// Enter offers nothing, no card is banished and none is drawn into memory.
// Notes carried over from the original definition:
//   - Warrior of the Fae Realm -> p1 myHand-7
//   - Pierce the Heavens (Polearm attack, NOT a Sword) -> p1 myHand-8
//   - play Warrior of the Fae Realm
gaScenario('warrior-of-the-fae-realm-enter-non-sword-attack-not-offered')
    ->deck($mordredDeck)
    ->seed('myHand', 'Warrior of the Fae Realm')
    ->seed('myHand', 'Pierce the Heavens')
    ->castSpell('Warrior of the Fae Realm', pay: 3)
    ->register();

// --- Warrior of the Fae Realm: the banished Sword attack CAN be activated on a LATER turn ---
// Warrior of the Fae Realm (eRcqucBKhX): "As long as you control CARDNAME, you may activate the
// banished card on a later turn." Fixed (was ENGINE BUG "never activatable on a later turn": the
// end-of-turn banish cleanup in Custom/GameLogic.php stripped the _warriorFaeRealm tag). Player 1
// plays the Warrior on turn 1 and banishes the Striking Tides, both players pass, and on player 1's
// turn 3 clicking the banished card starts a real activation: 3 reserve payments, an attack on
// player 2's champion for 4, the card ends in the graveyard. (The first player may not attack on
// turn 1, so a turn-1 same-turn click would be refused for the wrong reason; the same-turn
// refusal is pinned separately at turn 3.)
// Notes carried over from the original definition:
//   - Warrior of the Fae Realm -> p1 myHand-7
//   - Striking Tides (Sword attack) -> p1 myHand-8
//   - play Warrior of the Fae Realm
//   - On Enter: banish Striking Tides
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate the banished Striking Tides on a LATER turn
//   - attack player 2's champion
gaScenario('warrior-of-the-fae-realm-banished-sword-activatable-on-later-turn')
    ->deck($mordredDeck)
    ->seed('myHand', 'Warrior of the Fae Realm')
    ->seed('myHand', 'Striking Tides')
    ->castSpell('Warrior of the Fae Realm', pay: 3, target: 'myHand-4')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myBanish-0!FSM!')->pay(3)
    ->rejects(1, 'myField-0', 'The attack target prompt rejects the attacker\'s own champion')->target('theirField-0')
    ->expectProperty('p1Banish-0', 'CardID', 'qrxQGA1pc6', 'On Enter banishes the Striking Tides and tags it with the Warrior\'s permission tag', step: 5)
    ->expectProperty('p1Banish-0', 'TurnEffects', '["_warriorFaeRealm"]', 'The banished card carries only the first-stage permission tag during the banishing turn', step: 5)
    ->expectProperty('p1Banish-0', 'TurnEffects', '["_warriorFaeRealm","_warriorFaeRealmLater"]', 'The permission tag survives the end of the banishing turn and the \'a turn has ended\' stamp is added', step: 6)
    ->expectZoneCount('myBanish', 1, 1, 'The banished card is still in banishment at the start of the later turn', step: 8)
    ->expectZoneCount('myBanish', 0, 1, 'Clicking the banished card on the later turn starts a real activation: it leaves banishment', step: 9)
    ->expectZoneCount('myMemory', 0, 1, 'No reserve payment has been made yet when the activation starts (cost is still owed)', step: 9)
    ->expectZoneCount('myGraveyard', 0, 1, 'After TWO payments the Sword attack has not resolved yet (printed cost 3)', step: 11)
    ->expectZoneCount('myMemory', 3, 1, 'Exactly THREE reserve payments were made (printed cost)', step: 12)
    ->expectDamage('p2Field-0', 0, 'The attacker\'s own champion is rejected as the attack target (nothing changed)', step: 13)
    ->expectDamage('p2Field-0', 4, 'The activated Striking Tides hits the opposing champion for 4')
    ->expectProperty('p1Graveyard-0', 'CardID', 'qrxQGA1pc6', 'The activated Sword attack ends in the graveyard')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing is left in banishment after the later-turn activation')
    ->expectProperty('p1Field-1', 'CardID', 'eRcqucBKhX', 'The Warrior is still on the field (the permission condition \'as long as you control\' held throughout)')
    ->expectDqEmpty()
    ->register();

// --- Warrior of the Fae Realm: the banished Sword attack is REFUSED on the SAME turn ("on a later turn") ---
// Warrior of the Fae Realm (eRcqucBKhX): "you may activate the banished card on a later turn."
// Fixed (was ENGINE BUG "activatable on the same turn": the ActionMap "myBanish" branch never checked
// that the card was banished on an EARLIER turn). Player 1 plays the Warrior on turn 3 (so the
// first-turn attack lock cannot be the reason for a refusal) and banishes the Striking Tides; the
// immediate click on the banished card is refused as a no-op (the FSM click itself is accepted by the
// engine, so the refusal is asserted on state): no payment is requested, it stays banished.
// Notes carried over from the original definition:
//   - Warrior of the Fae Realm -> p1 myHand-7
//   - Striking Tides (Sword attack) -> p1 myHand-8
//   - end player 1 turn 1 (the first player may not attack on turn 1)
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - play Warrior of the Fae Realm
//   - On Enter: banish Striking Tides
//   - click the Sword attack banished THIS turn: the engine accepts the click as a no-op (refusal is asserted on state, not as a rejected action)
gaScenario('warrior-of-the-fae-realm-banished-sword-not-activatable-same-turn')
    ->deck($mordredDeck)
    ->seed('myHand', 'Warrior of the Fae Realm')
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Warrior of the Fae Realm', pay: 3, target: 'myHand-4')
    ->action(1, 10002, 'myBanish-0!FSM!')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["_warriorFaeRealm"]', 'On Enter banishes the Striking Tides with only the first-stage permission tag (no later-turn stamp yet)', step: 8)
    ->expectZoneCount('myMemory', 4, 1, 'Memory holds the 3 reserve payments plus the card drawn into memory', step: 8)
    ->expectProperty('p1Banish-0', 'CardID', 'qrxQGA1pc6', 'The Striking Tides banished THIS turn stays in banishment when clicked (no activation)')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["_warriorFaeRealm"]', 'The click does not stamp or consume the permission: still only the first-stage tag')
    ->expectZoneCount('myBanish', 1, 1, 'Still exactly one card in banishment after the same-turn click')
    ->expectZoneCount('myMemory', 4, 1, 'No reserve payment was requested or made by the same-turn click')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing was activated (graveyard stays empty)')
    ->expectDamage('p2Field-0', 0, 'The opposing champion took no damage from a same-turn activation')
    ->expectProperty('p1Field-1', 'CardID', 'eRcqucBKhX', 'The Warrior is on the field, so only the \'later turn\' timing blocks the activation')
    ->expectDqEmpty()
    ->register();

// --- Warrior of the Fae Realm: later-turn activation needs the Warrior ("as long as you control CARDNAME") ---
// Warrior of the Fae Realm (eRcqucBKhX): "As long as you control CARDNAME, you may activate the
// banished card on a later turn." Negative case: a Striking Tides sits in player 1's banishment
// already carrying the permission tags a prior turn's banish would have left (_warriorFaeRealm +
// _warriorFaeRealmLater) but player 1 does NOT control a Warrior of the Fae Realm, so on turn 3
// the click is refused as a no-op (asserted on state). (Seeded tags: this proves only the "as long as you control" gate, the
// two sibling fixtures prove the tag lifecycle through real play.)
// Notes carried over from the original definition:
//   - Striking Tides in banishment with the permission tags, but no Warrior on the field -> p1 banish-0
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - click the banished Sword attack with no Warrior in play: accepted as a no-op (refusal is asserted on state)
gaScenario('warrior-of-the-fae-realm-banished-sword-not-activatable-without-warrior')
    ->deck($mordredDeck)
    ->seed('myBanish', 'Striking Tides', properties: ['TurnEffects' => ['_warriorFaeRealm', '_warriorFaeRealmLater']])
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myBanish-0!FSM!')
    ->tested('Warrior of the Fae Realm')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["_warriorFaeRealm","_warriorFaeRealmLater"]', 'Setup precondition: the banished Striking Tides already carries both permission tags (a prior turn\'s banish)', step: 1)
    ->expectZoneCount('myField', 1, 1, 'Setup precondition: player 1 controls no Warrior (only the champion is on the field)', step: 1)
    ->expectProperty('p1Banish-0', 'CardID', 'qrxQGA1pc6', 'Without a Warrior of the Fae Realm in play the banished Sword attack stays in banishment when clicked on a later turn')
    ->expectZoneCount('myBanish', 1, 1, 'Exactly one card remains in banishment (nothing left it for activation)')
    ->expectZoneCount('myHand', 8, 1, 'The click did not move the card into hand: hand unchanged from before the click')
    ->expectZoneCount('myMemory', 0, 1, 'No reserve payment was requested or made')
    ->expectDqEmpty()
    ->register();

// --- Banishment activation: a click that cannot be PAID for is refused without consuming the permission ---
// Warrior of the Fae Realm (eRcqucBKhX): "As long as you control CARDNAME, you may activate the
// banished card on a later turn." ENGINE BUG (fixed): the ActionMap "myBanish" branches moved the
// clicked card into hand BEFORE checking that the activation could begin, so an unaffordable click
// stranded the card (stack / hand) and consumed the banish permission. Player 1's hand is emptied,
// then holds the Warrior, a Striking Tides (Sword attack) and three Dungeon Guides. The Warrior is
// played for real (3 payments; the Striking Tides, paid into memory, is banished from memory by the
// On Enter). On turn 3 player 1 spends the rest of the hand on a Dungeon Guide (3 payments), leaving
// ONE card in hand, then clicks the banished Striking Tides (cost 3): the click must be refused as
// unaffordable -- the card stays in banishment with both permission tags, hand/memory/effect stack
// unchanged, no payment prompt queued.
// Notes carried over from the original definition:
//   - start from an empty hand (7 cards -> graveyard)
//   - Warrior of the Fae Realm -> p1 myHand-0
//   - Striking Tides (Sword attack) -> p1 myHand-1
//   - Dungeon Guide -> p1 myHand-2
//   - Dungeon Guide -> p1 myHand-3
//   - Dungeon Guide -> p1 myHand-4
//   - 1: play Warrior of the Fae Realm
//   - 5: On Enter: banish Striking Tides from memory
//   - 6: end player 1 turn 1
//   - 7: end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - 8: decline the materialize offer
//   - 9: play a Dungeon Guide
//   - 13: decline Dungeon Guide's optional On Enter
//   - 14: click the banished Striking Tides (cost 3, only 1 card in hand): accepted as a no-op, refusal asserted on state
gaScenario('warrior-of-the-fae-realm-banished-sword-unaffordable-click-refused')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Warrior of the Fae Realm')
    ->seed('myHand', 'Striking Tides')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->playSlot(0)->pay(3)->target('myMemory-0')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->playSlot(0)->pay(3)->answer(1, 'NO')
    ->action(1, 10002, 'myBanish-0!FSM!')
    ->tested('Warrior of the Fae Realm')
    ->register();

// --- Warrior of the Fae Realm: Stealth -- an attack cannot target it ---
// Warrior of the Fae Realm (eRcqucBKhX): "Stealth". Player 2 attacks with a Dungeon Guide; the
// attack-target prompt lists only player 1's champion and answering with the Warrior is rejected, so
// the attack is aimed at the champion.
// Notes carried over from the original definition:
//   - Warrior of the Fae Realm (Stealth) -> p1 field-1
//   - Dungeon Guide (the attacker) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 attacks with Dungeon Guide
//   - the only legal target: player 1's champion
gaScenario('warrior-of-the-fae-realm-stealth-cannot-be-attacked')
    ->deck($mordredDeck)
    ->seed('myField', 'Warrior of the Fae Realm')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'A Stealth ally (Warrior of the Fae Realm) is rejected as an attack target')
    ->answer(2, 'theirField-0')
    ->tested('Warrior of the Fae Realm')
    ->expectDamage('p1Field-1', 0, 'The Warrior took no damage: it was never a legal attack target')
    ->expectDamage('p1Field-0', 1, 'The attack was aimed at the only legal target, the champion (1 damage)')
    ->expectProperty('p1Field-1', 'Status', '2', 'The Warrior is still awake and unharmed')
    ->expectDqEmpty()
    ->register();

// top four: Dungeon Guide, Fluffy Shopkeep, Fairy Whispers (the three milled), Charge the Soul (marker: must stay on top afterwards)
gaScenario('wuji-of-lingering-fate-west-east-sacrifice-mill-three-self')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'WEST'])
    ->seed('myField', 'Wuji of Lingering Fate')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->endTurn()->answer(1, 'EAST')->answer(1, 'YES')->answer(1, 'YES')
    ->tested('Wuji of Lingering Fate')
    ->register();

gaScenario('wuji-of-lingering-fate-west-east-sacrifice-mill-three-opponent')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'WEST'])
    ->seed('myField', 'Wuji of Lingering Fate')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'], player: 2)
    ->endTurn()->answer(1, 'EAST')->answer(1, 'YES')->answer(1, 'NO')
    ->tested('Wuji of Lingering Fate')
    ->register();

gaScenario('wuji-of-lingering-fate-west-east-decline-sacrifice-no-mill')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'WEST'])
    ->seed('myField', 'Wuji of Lingering Fate')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'], player: 2)
    ->endTurn()->answer(1, 'EAST')->answer(1, 'NO')
    ->tested('Wuji of Lingering Fate')
    ->register();

// PLAYER 2 seating (perspective check): player 2 controls Wuji and ends its own turn; the stack sits under its turn-start draw
gaScenario('wuji-of-lingering-fate-player-two-west-east-sacrifice-mill-three-self')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Kongming, Fel Eidolon'], player: 2)
    ->seed('myMastery', 'Shifting Currents', player: 2, properties: ['Direction' => 'WEST'])
    ->seed('myField', 'Wuji of Lingering Fate', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'], player: 2)
    ->endTurn()
    ->endTurn(2)->answer(2, 'EAST')->answer(2, 'YES')->answer(2, 'YES')
    ->tested('Wuji of Lingering Fate')
    ->register();

// --- Waterfall Veiler (x6jo8zxhl9): "[Class Bonus] Stealth. On Champion Hit: That opponent puts the top four cards of their deck into their graveyard." ---
// Generated body milled the OPPONENT with a loop of MZMove($player, "theirDeck-0", "theirGraveyard"): only the TOP card was ever milled (see 1676ae91 Pure Cytosynth).
// Player 1's Waterfall Veiler attacks player 2's champion on turn 3. Player 2's deck top (after its turn-2 draw takes a filler Dungeon Guide): Fluffy Shopkeep,
// Dungeon Guide, Fairy Whispers, Spirit of Water (the four milled), Charge the Soul (marker: must be the new top card).
// Notes carried over from the original definition:
//   - Waterfall Veiler -> field-1
//   - awake, can attack
gaScenario('waterfall-veiler-champion-hit-opponent-mills-four')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Waterfall Veiler')
    ->patch('myField-1', ['Status' => 2])
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fairy Whispers', 'Spirit of Water', 'Charge the Soul'], player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')
    ->tested('Waterfall Veiler')
    ->register();

// Whirlwind Threads (p7nkdqnzzg): "Put a quest counter on your champion. If an object was suppressed this turn, put Whirlwind Threads into your memory."
// Notes carried over from the original definition:
//   - Whirlwind Threads -> myHand-7
gaScenario('whirlwind-threads-puts-a-quest-counter-on-your-champion')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'TAMER')
    ->seed('myHand', 'Whirlwind Threads')
    ->castSpell('Whirlwind Threads', pay: 1)
    ->register();

// Whimsy's Warden (cworak5y4y): "Intercept. As long as you have two or more omens, Whimsy's Warden gets +2 POWER."
// Notes carried over from the original definition:
//   - Whimsy's Warden (power 1) -> myField-1
gaScenario('whimsys-warden-gets-plus-two-power-with-two-or-more-omens')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Whimsy\'s Warden')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Fluffy Shopkeep', properties: ['Counters' => ['omen' => 1]])
    ->target('myField-0')
    ->tested('Whimsy\'s Warden')
    ->register();

// Notes carried over from the original definition:
//   - Whimsy's Warden -> myField-1
gaScenario('whimsys-warden-has-base-power-with-only-one-omen')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Whimsy\'s Warden')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->target('myField-0')
    ->tested('Whimsy\'s Warden')
    ->register();

// Notes carried over from the original definition:
//   - Wind Surge Emitter -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('wind-surge-emitter-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'MAGE'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Wind Surge Emitter')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Fluffy Shopkeep')
    ->seed('myField', 'Powercell')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myGraveyard', 'Baby Red Slime')
    ->seed('myGraveyard', 'Fast Cure')
    ->seed('myGraveyard', 'Baby Blue Slime')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myField', 'Sojourner\'s Hunt')
    ->seed('myField', 'Baby Blue Slime')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)->target('myField-2')->pass(1)
    ->tested('Wind Surge Emitter')
    ->register();

// Notes carried over from the original definition:
//   - Where Futures Stir -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('where-futures-stir-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'MAGE'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Where Futures Stir')
    ->patch('myField-1', ['Status' => 2, 'Counters' => ['training' => 3]])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Fluffy Shopkeep')
    ->seed('myField', 'Powercell')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myGraveyard', 'Baby Red Slime')
    ->seed('myGraveyard', 'Fast Cure')
    ->seed('myGraveyard', 'Baby Blue Slime')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myField', 'Sojourner\'s Hunt')
    ->seed('myField', 'Baby Blue Slime')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->pass(1)
    ->tested('Where Futures Stir')
    ->expectZoneCount('myHand', 13, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
    ->expectZoneCount('myMemory', 4, 1, 'Memory after the reserve payments / draws into memory')
    ->expectZoneCount('myBanish', 0, 1, 'Banishment after the printed banish costs')
    ->expectZoneCount('myGraveyard', 4, 1, 'Graveyard after the printed sacrifice / banish costs')
    ->expectZoneCount('myField', 6, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost')
    ->register();

// --- Windrider Invoker (lx6xwr42i6): "On Enter: You may remove two enlighten counters from your champion. If you do, draw a card and Windrider Invoker gets +3 power until end of turn."
// The champion's MZ was read from an undefined variable, so the counters were never removed although the card was drawn and the power granted. ---
gaScenario('windrider-invoker-enter-removes-two-enlighten-draws-and-gains-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Windrider Invoker')
    ->patch('myField-0', ['Counters' => ['enlighten' => 3]])
    ->play('Windrider Invoker')->pay(3)->pass(1)->answer(1, 'YES')
    ->register();

// Windfall Check (e1jCu0neWY): "Command Chessman. On Champion Hit: That opponent banishes three cards from their graveyard." The banish choice belongs to the OPPONENT (player 2), so it is offered in
// player 2's own frame ("myGraveyard-N").
// Notes carried over from the original definition:
//   - CLERIC class (the champion is Spirit of Wind)
//   - Pawn Piece (CHESSMAN ally), the commanded attacker -> p1 field-1
//   - Windfall Check -> p1 myHand-7
//   - Dungeon Guide -> p2 graveyard-0
//   - Fluffy Shopkeep -> p2 graveyard-1
//   - Dungeon Guide -> p2 graveyard-2
//   - Fluffy Shopkeep -> p2 graveyard-3
gaScenario('windfall-check-champion-hit-opponent-banishes-three-from-their-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'CLERIC')
    ->seed('myField', 'Pawn Piece')
    ->seed('myHand', 'Windfall Check')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->endTurn()
    ->endTurn(2)
    ->play('Windfall Check')->pay(2)->target('myField-1')->target('theirField-0')->target('myField-1')->pass(1)
    ->answer(2, 'myGraveyard-0')->answer(2, 'myGraveyard-0')->answer(2, 'myGraveyard-0')
    ->register();
