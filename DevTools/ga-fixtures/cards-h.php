<?php
/**
 * Fixtures for cards starting with 'H', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Harvest Herbs: Gather (summon a random herb token) ---
gaScenario('harvest-herbs-gather')
    ->standardDeck('Harvest Herbs')
    ->seed('myHand', 'Harvest Herbs')
    ->castSpell('Harvest Herbs', pay: 1)
    ->register();

// --- Harmonious Mantra: Recover 3 ---
// Notes carried over from the original definition:
//   - pre-damage champion so Recover 3 is observable
gaScenario('harmonious-mantra-recover')
    ->standardDeck('Harmonious Mantra')
    ->champion(damage: 5)
    ->seed('myHand', 'Harmonious Mantra')
    ->castSpell('Harmonious Mantra', pay: 2)
    ->register();

// --- Heighten Spellcraft: Empower 3 ---
gaScenario('heighten-spellcraft-empower')
    ->standardDeck('Heighten Spellcraft')
    ->seed('myHand', 'Heighten Spellcraft')
    ->castSpell('Heighten Spellcraft', pay: 1, window: 'both')
    ->register();

// --- Honorable Vanguard: Floating Memory pays a champion level-up's memory cost from graveyard ---
// Honorable Vanguard's only ability is the unconditional Floating Memory keyword (while
// paying a memory cost, you may banish this card from your graveyard to pay for 1 of that
// cost). This is tested by paying Lorraine, Wandering Warrior's 1-memory champion level-up
// cost entirely from a Honorable Vanguard seeded directly into the graveyard, with no myMemory
// filler seeded -- QueueMaterializeFloatingPaymentChoice
// (GrandArchiveSim/Custom/MaterializeLogic.php) offers it as the sole payment source.
gaScenario('honorable-vanguard-floating-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myGraveyard', 'Honorable Vanguard')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')
    ->tested('Honorable Vanguard')
    ->expectProperty('myField-0', 'CardID', 'DpHDGaX2Pn', 'The champion levels up into Lorraine, Wandering Warrior, proving the 1-memory cost was paid')
    ->expectZoneCount('myGraveyard', 0, 1, 'Honorable Vanguard is banished from the graveyard as the floating memory payment, with no other memory source seeded')
    ->expectDqEmpty()
    ->register();

// --- Hydroguard Retainer: Shifting Currents North->West transition draws a card ---
// Direction-CHANGE triggers ($shiftingCurrentsTransitions in GameLogic.php, keyed "FROM->TO")
// only fire through ChangeShiftingCurrents(), which is reached in a controlled way by activating
// any Spell card while Kongming, Fel Eidolon is in the champion's lineage (its own Inherited
// ability queues an ICONCHOICE "adjacent direction" decision on every Spell activation, GameLogic.php
// ~5703-5708) -- same Subcards lineage-patch technique as tera-sight-preserve-draw, reused here
// purely as a trigger mechanism rather than for its own sake. ICONCHOICE's response is just the
// chosen direction string verbatim (goldfish default picks options[0], GameLogic.php:261-264).
// Shifting Currents seeded at NORTH; Tera Sight (0 reserve TERA Spell) is activated to reach the
// choice, which is answered "WEST" (adjacent to NORTH) to fire the NORTH->WEST transition.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock + Fel Eidolon Inherited trigger
//   - Shifting Currents facing North
//   - Hydroguard Retainer
//   - Tera Sight, to trigger the Inherited SC choice on activation
//   - choose adjacent direction WEST
gaScenario('hydroguard-retainer-north-west-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Hydroguard Retainer')
    ->seed('myHand', 'Tera Sight')
    ->playSlot(8)->answer(1, 'WEST')
    ->tested('Hydroguard Retainer')
    ->expectProperty('myMastery-0', 'Direction', 'WEST', 'Shifting Currents changes from North to West')
    ->expectZoneCount('myHand', 9, 1, 'Tera Sight\'s own Draw a card resolves independently of the transition (played 1, drew 1)', step: 1)
    ->expectZoneCount('myHand', 10, 1, 'North->West transition draws an additional card once the direction actually changes')
    ->expectDqEmpty()
    ->register();

// --- Hemorrhaging Rend: [Damage 20+] Cleave, attack all units a chosen opponent controls ---
// Hemorrhaging Rend is an ATTACK card, played as an intent card during a real attack rather
// than having its own cardActivatedAbilities entry (no per-card macro exists for it at all --
// confirmed by grep). Seeding it directly into myIntent via setup does NOT survive to the
// attack: the P1/P2 turn-1 pass needed to clear the turn-1 attack lock runs through EndPhase(),
// which calls ClearIntent() and wipes any pre-seeded intent card before turn 2 begins
// (confirmed via a throwaway debug harness, now deleted, showing GetIntentCards(1) === [] and
// BeginCombatPhase failing with "0 or less power" right when the attack was attempted). So
// instead it's played for real from hand during turn 2's MAIN phase, same FSM-click + reserve-
// payment shape as any other hand card -- ActionMap's myHand case has no special handling for
// ATTACK-type cards, it just calls the same ActivateCard() as a materialize/spell, whose result
// is entering myIntent instead of the field. Hemorrhaging Rend's element is EXIA, not unlocked
// by the default Spirit of Fire lineage. Patching the champion's own CardID to an EXIA champion
// (Dante, Hemomancer) was tried first and rejected: GetPlayerEnabledElements reads
// GetChampionLineage (the current champion's own elements plus its Subcards lineage), so a full
// CardID patch works for the element unlock but ALSO inherits Dante's own printed abilities --
// an Empower fast-action Opportunity window opened at the very first decision point and
// silently absorbed the turn-1 pass action meant to decline the turn-2 MAT-phase materialize
// offer, leaving turn 2 never properly reached (confirmed via a throwaway debug harness, now
// deleted: BeginCombatPhase kept failing with "0 or less power" and GetIntentCards(1) stayed
// empty even after the FSM click + reserve payments "succeeded"). Fix: unlock EXIA via Subcards
// lineage only (same technique as Lorraine Arclight Saber's WIND unlock via
// Subcards=[pNiyaGlIe7]) -- Dante's CardID goes into Subcards while Spirit of Fire (no
// interfering abilities) stays the active champion. Damage is patched to 20 for the
// [Damage 20+] condition. AttackerHasCleave (CombatLogic.php:433-443) grants Cleave once the
// champion has 20+ damage counters and Hemorrhaging Rend is in intent -- Cleave with no weapon
// available bypasses the normal target-choice MZCHOOSE entirely and queues a CleaveAttack
// decision directly (CombatLogic.php:1187-1188/1575-1578), making ALL of the opponent's units
// (their champion AND Dungeon Guide, seeded onto their own field) simultaneous defenders.
// Notes carried over from the original definition:
//   - Dante, Hemomancer in Subcards for EXIA unlock only
//   - Hemorrhaging Rend
//   - Dungeon Guide, second Cleave defender
gaScenario('hemorrhaging-rend-damage20-cleave')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Dante, Hemomancer'], damage: 20)
    ->seed('myHand', 'Hemorrhaging Rend')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Hemorrhaging Rend', pay: 4)
    ->action(1, 10002, 'myField-0!FSM!')
    ->expectDamage('theirField-0', 4, 'Cleave hits the opponent\'s champion for Hemorrhaging Rend\'s 4 power')
    ->expectZoneCount('myField', 1, 2, 'Cleave simultaneously hits Dungeon Guide (3 life) for the same 4 damage, killing it -- proving the attack hit ALL of the opponent\'s units, not just the one chosen target')
    ->register();

// --- Heavy Swing: [Class Bonus] costs 2 less to activate ---
// Heavy Swing is an ATTACK card (its printed 6 POWER becomes the intent-loaded attack bonus,
// out of scope here) with a straightforward [Class Bonus] reserve discount: 6 - 2 = 4.
// CanActivateAttackCardNow (GameLogic.php ~1246) applies Rule 1.h to activating ANY ATTACK-
// type card, not just declaring a real attack, so playing it also needs player 2's turn (Rule
// 1.h only locks the opening player's own turn 1); the discount is confirmed by needing only
// 4 reps of the myHand-0 reserve-payment decision, not 6.
// Notes carried over from the original definition:
//   - Tonoris, Lone Mercenary (GUARDIAN champion) - Class Bonus source
//   - ends turn 1
gaScenario('heavy-swing-class-bonus-discount')
    ->standardDeck('Heavy Swing')
    ->seed('myField', 'Tonoris, Lone Mercenary', player: 2)
    ->seed('myHand', 'Heavy Swing', player: 2)
    ->endTurn()
    ->play('Heavy Swing', 2)->pay(4, 2)->answer(2, 'theirField-0')
    ->expectDqEmpty()
    ->expectProperty('p2Intent-0', 'CardID', 'kvoqk1l75t', '[Class Bonus] Heavy Swing costs 2 less to activate (4 reps of reserve, not 6)')
    ->register();

// --- Hypothermia: target rested ally gets -4 LIFE until end of turn ---
// Regression fixture for Database/migrations/14_grand_archive_hypothermia_rested_target_fix.sql:
// Hypothermia's stored ability_code filtered its ally target list on $obj->Status == 2 (AWAKE,
// GrandArchiveSim/Custom/GameLogic.php:10156) instead of == 1 (RESTED), the opposite of its
// printed "Target rested ally gets -4 [LIFE] until end of turn." text -- a rested-only field
// would silently no-op. Hypothermia's element is WATER (unlike the FIRE-aligned starting
// champion used by most fixtures in this file), so the same WATER lineage/element-unlock patch
// used by tsunami-of-nanyue-damage is reused. Dungeon Guide is seeded onto the opponent's field
// and explicitly rested (Status=1) as the target; after the fix it's the only legal target.
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
//   - rested Dungeon Guide - the only legal target after the fix
gaScenario('hypothermia-target-rested-ally-life')
    ->standardDeck('Hypothermia')
    ->readyFor('Hypothermia')
    ->seed('theirField', 'Dungeon Guide', properties: ['Status' => 1])
    ->seed('myHand', 'Hypothermia')
    ->castSpell('Hypothermia', pay: 2, target: 'theirField-1')
    ->register();

// --- Hurricane Sweep: [Class Bonus] Efficiency + Cleave ---
// Hurricane Sweep is WIND element with [Class Bonus] Efficiency (reduce reserve cost by
// champion's current level) and printed Cleave (already proven generically by the
// pre-existing hemorrhaging-rend-damage20-cleave fixture, so not re-asserted here). Champion
// CardID-patched directly to Lorraine, Blademaster (WARRIOR, level 2) with Spirit of Wind
// added via Subcards for WIND access -- same technique as wind-cutter-class-bonus-power-attack,
// including dropping Fairy Whispers from the Main list to avoid the WIND-unlock
// Opportunity-window cascade that card triggers once WIND is enabled. With level 2 active,
// Efficiency reduces the printed 5 reserve cost to 3 (verified: CalculateActivationReserveCost
// applies the Efficiency registry's reduction unconditionally once IsGA's $Efficiency_Cards
// registry contains the card, regardless of the printed "[Class Bonus]" qualifier -- an
// engine-behavior detail worth noting, though moot here since a real WARRIOR Class Bonus is
// also active).
gaScenario('hurricane-sweep-class-bonus-power-attack')
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
    ->champion(cardName: 'Lorraine, Blademaster', lineage: ['Spirit of Wind'])
    ->seed('myHand', 'Hurricane Sweep')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Hurricane Sweep', pay: 3)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Status', '1')
    ->expectProperty('p1Graveyard-0', 'CardID', '4V6qKuM7xs')
    ->expectDamage('p2Field-0', 1)
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":1}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Hasty Messenger: On Attack, you may discard a card to draw a card ---
// Hasty Messenger's element is FIRE, matching the starting champion, so no lineage patch is
// needed. Rule 1.h blocks the game's first player from attacking on turn 1, so player 1 ends
// turn 1 and player 2 attacks with Hasty Messenger on their own turn 1 instead.
// Notes carried over from the original definition:
//   - Hasty Messenger
//   - awake, can attack
//   - ends turn 1 (first-player attack lock)
//   - declare attack with Hasty Messenger
//   - target opponent's champion
//   - discard a card
gaScenario('hasty-messenger-on-attack-discard-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Hasty Messenger', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->pay(1, 2)
    ->tested('Hasty Messenger')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 1)
    ->expectProperty('p1Field-0', 'Counters', '{"_champDamageThisTurn":1}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN"]')
    ->expectProperty('p2Field-1', 'Status', '1')
    ->expectProperty('p2Field-1', 'TurnEffects', '[]')
    ->expectProperty('p2Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-3', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-5', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-6', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-7', 'CardID', 'n8wyfG9hbY', 'Draws a new card after discarding')
    ->expectProperty('p2Graveyard-0', 'CardID', 'px60u5n1do', 'On Attack: discards a card')
    ->expectZoneCount('theirHand', 8, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Halcyon Animus: materialize a regalia card from material deck or banishment ---
// Halcyon Animus (uvopjFSUj0, CRUX) needs CRUX -- champion CardID-patched to Lorraine, Spirit
// Ruler (n2TKqNaODR) as above. cardActivatedAbilities["uvopjFSUj0:0"] (GeneratedMacroCode.php)
// offers every REGALIA in myMaterial/myBanish as an MZCHOOSE target and routes the choice
// through the shared "MATERIALIZE" custom handler; Safeguard Amulet (yj2rJBREH8, 0-memory-cost
// REGALIA already in this deck's Material zone) is chosen so no further cost payment is needed,
// landing it directly on the field.
gaScenario('halcyon-animus-materialize-regalia')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(cardName: 'Lorraine, Spirit Ruler')
    ->seed('myHand', 'Halcyon Animus')
    ->castSpell('Halcyon Animus', pay: 3, window: 'both', target: 'myMaterial-6')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'yj2rJBREH8', 'The chosen material-deck regalia lands on the field.')
    ->register();

// --- Hairpin of Transience: [Class Bonus] Banish Hairpin of Transience: Put three wither
// counters on target non-champion object ---
// Same [Class Bonus] champion patch as frostlorn-caress-four-wither-counters (Diao Chan,
// Enchantress + kept Spirit of Water lineage for WATER unlock -- Hairpin's own element is
// WATER) -- satisfies activateAbilityPrereqs["xl3tzqhlt1:0"]'s IsClassBonusActive(["CLERIC"])
// check (GeneratedMacroCode.php ~7386). Hairpin of Transience is seeded directly onto the
// field (it's a REGALIA/ITEM, activated from the field, not cast from hand). Activating it
// (mode 10001 CustomInput Activate:0) first pays its cost -- ActivatedAbilityCost's "banish
// self" case for xl3tzqhlt1 (Custom/GameLogic.php ~6316) immediately moves it to myBanish --
// THEN activateAbilityAbilities["xl3tzqhlt1:0"] (~7368) offers an MZCHOOSE of every
// non-champion object on either field (only the opponent's seeded Dungeon Guide qualifies);
// customDQHandlers["xl3tzqhlt1:0:ActivateAbility-1"] (~29015) calls
// AddCounters($player, $chosen, "wither", 3) on the chosen target. Asserts both the wither
// counters on the target AND that Hairpin itself left the field for the banishment zone
// (banished, not merely destroyed/discarded).
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress + kept Spirit of Water lineage for WATER unlock (Class Bonus CLERIC source)
//   - Hairpin of Transience, seeded directly onto the field
//   - Dungeon Guide ALLY, target
//   - activate Hairpin's Class Bonus ability -- banishes itself as the cost
//   - target the opponent's Dungeon Guide
gaScenario('hairpin-of-transience-banish-three-wither-counters')
    ->deck(GA_DIAOCHAN_WITHER_DECK)
    ->champion(cardName: 'Diao Chan, Enchantress', lineage: ['Spirit of Water'])
    ->seed('myField', 'Hairpin of Transience')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->tested('Hairpin of Transience')
    ->expectDqEmpty()
    ->expectProperty('p1Banish-0', 'CardID', 'xl3tzqhlt1', 'Hairpin of Transience itself was banished (moved to the banishment zone) as its own activation cost, not merely destroyed or discarded')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p2Field-1', 'Counters', '{"wither":3}', 'Hairpin of Transience\'s [Class Bonus] ability put exactly 3 wither counters on the target non-champion object')
    ->expectZoneCount('myField', 1, 1)
    ->register();

// --- Haunting Demise: [Class Bonus] On Champion Hit, put it on the bottom of the hit champion's lineage; Inherited Effect: 1 unpreventable damage each recollection phase ---
// Haunting Demise (v0buu5y0ub, UMBRA ATTACK/CURSE, reserve 1, power 3): "[Class Bonus] On Champion
// Hit: Put CARDNAME on the bottom of the hit champion's lineage. Inherited Effect: At the
// beginning of your recollection phase, deal 1 unpreventable damage to this object." Player 2's
// champion is patched to Tristan, Shadowdancer (ASSASSIN, UMBRA); Haunting Demise is played for
// real and hits player 1's champion for 3: onHitAbilities['v0buu5y0ub:0'] AddToChampionLineage()s
// it onto player 1's champion (the Subcards list gains 'v0buu5y0ub'; the physical intent copy is
// banished by ClearIntent() because of the CURSE_TO_LINEAGE marker, an intentional engine
// bookkeeping choice per its code comment). When player 1's next turn reaches its recollection
// phase the inherited curse deals 1 unpreventable damage to player 1's champion
// (Custom/GameLogic.php ChampionHasInLineage(...'v0buu5y0ub')), taking Damage from 3 to 4.
// Notes carried over from the original definition:
//   - player 2 champion Tristan, Shadowdancer (ASSASSIN, UMBRA)
//   - Haunting Demise -> p2 myHand-7
//   - end player 1 turn 1
//   - player 2 plays Haunting Demise
//   - attack player 1's champion
//   - end player 2 turn
//   - decline the materialize offer at the start of player 1's turn
gaScenario('haunting-demise-champion-hit-lineage-curse-recollection-damage')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Wind'], player: 2)
    ->seed('myHand', 'Haunting Demise', player: 2)
    ->endTurn()
    ->play('Haunting Demise', 2)->pay(1, 2)->answer(2, 'theirField-0')
    ->endTurn(2)->pass(1)
    ->register();

// --- Heavenly Drake: printed Spellshroud -- an opposing spell cannot target it ---
// Heavenly Drake (jej8y91lkg, loSCQzxqi1, ALLY): "Spellshroud (Units with spellshroud can't be targeted by
// Spells.)" -- the Spellshroud is the card's own, UNCONDITIONAL keyword (rules:
// .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords > Spellshroud 1: "This can't be targeted by
// spells"). It was never implemented: HasSpellshroud() (Custom/GameLogic.php) recognised printed Spellshroud
// only through a hard-coded card-ID list and HasKeyword_Spellshroud(), which is never generated
// (Data/ProcessKeywordsGA.php has no Spellshroud keyword, so function_exists is false); these IDs were in
// neither, so FilterSpellshroudTargets() kept the card in the target list. Fixed: they are now in
// HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities / NO_SPELLSHROUD gates).
// Player 2 controls the holders plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff
// counter on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, answering with
// each holder is refused (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('heavenly-drake-printed-spellshroud-spell-cannot-target-it', ['Heavenly Drake', '#loSCQzxqi1'], 'Liturgy of Corruption')
    ->register();

// --- Hoarfrost Hold: declining a further banish still puts a frost counter per Suited Spell already banished (dontSkipOnPass regression) ---
// Hoarfrost Hold (DNe5dvCNA1): "[Class Bonus] On Enter: Banish any amount of Suited Spell cards from your hand and/or memory. For each card banished this way, put a frost counter on CARDNAME." Stopping early (declining the 'Banish another Suited Spell?' prompt) must still put one frost counter per card already banished.
// Regression: HoarfrostHoldChoose's loop re-queue (Custom/GameLogic.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its PASS branch (AddCounters 'frost' for the banished count) never ran: a Suited Spell was banished and Hoarfrost Hold got no frost counter. The first prompt (queued by HoarfrostHoldEnter) needs no flag: declining it with 0 banished adds 0 counters either way.
// Two Ashen Riffle (MAGE/SUITED/SPELL) copies are seeded in hand; the champion is patched to Arisanna, Master Alchemist (CLERIC) for the Class Bonus with a Spirit of Water Subcards lineage for WATER element access; one Suited Spell is banished, the next offer declined with PASS.
// Notes carried over from the original definition:
//   - Arisanna, Master Alchemist (CLERIC) for the Class Bonus + Spirit of Water lineage for WATER access
//   - Ashen Riffle (Suited Spell) #1 -> myHand-7
//   - Ashen Riffle (Suited Spell) #2 -> myHand-8
//   - reserve 1/2
//   - reserve 2/2
//   - banish a Suited Spell from hand
//   - decline banishing another Suited Spell
gaScenario('hoarfrost-hold-decline-further-banish-keeps-frost-counters')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Master Alchemist', lineage: ['Spirit of Water'])
    ->seed('myHand', 'Ashen Riffle')
    ->seed('myHand', 'Ashen Riffle')
    ->seed('myHand', 'Hoarfrost Hold')
    ->play('Hoarfrost Hold')->pay(2)->target('myHand-5')->pass(1)
    ->expectProperty('p1Field-1', 'Counters', '{"frost":1}', 'One Suited Spell banished then declined: Hoarfrost Hold gets exactly 1 frost counter')
    ->expectDqEmpty()
    ->register();

// --- Huang Zhong, Unerring Aim (XikXt8WyNp): "[Class Bonus] (3), Return CARDNAME to its owner's memory: Ranger units you control become distant." The generated activation queued
// HuangZhongDistant, whose handler called GetController(), which does not exist (fatal after the cost was paid). ---
// Notes carried over from the original definition:
//   - Diana, Deadly Duelist (level 2 RANGER): Class Bonus + a Ranger champion
//   - Huang Zhong -> field-1
//   - Hidden Longbowman (RANGER ally) -> field-2
//   - Dungeon Guide (not a Ranger) -> field-3
gaScenario('huang-zhong-return-to-memory-ranger-units-become-distant')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Deadly Duelist', lineage: ['Spirit of Water'])
    ->seed('myField', 'Huang Zhong, Unerring Aim')
    ->seed('myField', 'Hidden Longbowman')
    ->seed('myField', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)
    ->tested('Huang Zhong, Unerring Aim')
    ->register();

// Hulao Gate, Sun's Ascent (snke7lneo4): "Upkeep - At the beginning of your recollection phase, you may banish a fire element card from your graveyard.
// If you don't, sacrifice Hulao Gate." Declining (PASS) must sacrifice the domain (HulaoGateUpkeep was queued without dontSkipOnPass, so the decline
// skipped the sacrifice and the Gate stayed forever).
// Notes carried over from the original definition:
//   - Hulao Gate, Sun's Ascent
//   - Baby Red Slime (fire): makes the banish offer available
gaScenario('hulao-gate-upkeep-declined-banish-sacrifices-the-domain')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Hulao Gate, Sun\'s Ascent')
    ->seed('myGraveyard', 'Baby Red Slime')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Hulao Gate, Sun\'s Ascent')
    ->expectZoneCount('myField', 1, 1, 'Hulao Gate was sacrificed: only the champion is left on the field')
    ->expectZoneCount('myGraveyard', 2, 1, 'The sacrificed Gate joined the fire card in the graveyard (the declined banish took nothing)')
    ->expectDqEmpty()
    ->register();

// Heart of the Frost (7NlaXYtNM6): "[REST], Banish Heart of the Frost: As a Spell, deal 2 damage to target ally if it's rested. Otherwise, rest it."
// Notes carried over from the original definition:
//   - Heart of the Frost -> myField-1
//   - Hindered: enters rested, so wake it
//   - Dungeon Guide -> theirField-1 (awake)
gaScenario('heart-of-the-frost-rests-an-awake-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Heart of the Frost')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->tested('Heart of the Frost')
    ->register();

// Notes carried over from the original definition:
//   - Heart of the Frost -> myField-1
//   - Hindered: enters rested, so wake it
//   - Dungeon Guide -> theirField-1
gaScenario('heart-of-the-frost-deals-two-damage-to-a-rested-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Heart of the Frost')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 1], player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->tested('Heart of the Frost')
    ->register();

// Heirloom of Materia (sZlDgmVTD7): "Banish CARDNAME: Choose one -- Remove all damage counters from target champion you don't control. Return those counters onto that champion at the beginning of your next turn."
// Notes carried over from the original definition:
//   - Heirloom of Materia -> myField-1
gaScenario('heirloom-of-materia-removes-an-opposing-champions-damage-and-returns-it-next-turn')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Heirloom of Materia')
    ->champion(damage: 5, player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, '0')
    ->endTurn()
    ->endTurn(2)
    ->tested('Heirloom of Materia')
    ->register();

// "-- For each opponent, for every two token objects they control, they sacrifice one."
// Notes carried over from the original definition:
//   - Heirloom of Materia -> myField-1
//   - five Powercell tokens: two are sacrificed
gaScenario('heirloom-of-materia-opponent-sacrifices-one-token-per-two-they-control')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Heirloom of Materia')
    ->seed('myField', 'Powercell', player: 2)
    ->seed('myField', 'Powercell', player: 2)
    ->seed('myField', 'Powercell', player: 2)
    ->seed('myField', 'Powercell', player: 2)
    ->seed('myField', 'Powercell', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, '1')->answer(2, 'myField-1')
    ->answer(2, 'myField-1')
    ->tested('Heirloom of Materia')
    ->expectZoneCount('theirField', 4, 1, 'Five tokens: two sacrificed, leaving the champion and three tokens')
    ->expectZoneCount('theirGraveyard', 0, 1, 'Tokens do not stay in the graveyard')
    ->expectZoneCount('myBanish', 1, 1, 'Heirloom of Materia banished itself as a cost')
    ->expectDqEmpty()
    ->register();

// "(3), Banish CARDNAME: Draw a card into your memory."
// Notes carried over from the original definition:
//   - Heirloom of Materia -> myField-1
gaScenario('heirloom-of-materia-draw-ability-pays-three-and-banishes-itself')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Heirloom of Materia')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pay(3)
    ->tested('Heirloom of Materia')
    ->expectZoneCount('myMemory', 4, 1, 'Three reserved cards plus the card drawn into memory')
    ->expectZoneCount('myHand', 4, 1, 'Three hand cards were paid')
    ->expectZoneCount('myBanish', 1, 1, 'Heirloom of Materia banished itself as a cost')
    ->expectDqEmpty()
    ->register();

// --- Field items whose printed activated ability used to run free and repeatable (misfiled in the play-time table) ---
// Halcyon Prism (997vxajn2q): "Banish Halcyon Prism: Glimpse 3."
// Notes carried over from the original definition:
//   - Halcyon Prism -> myField-1
gaScenario('halcyon-prism-banish-glimpses-three')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Halcyon Prism')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'Top=px60u5n1do;Bottom=em6eEh9q8y,em6eEh9q8y')
    ->tested('Halcyon Prism')
    ->register();

gaAbilitySandbox('humpty-dumpty-fate-s-fall-ability-1-pays-its-printed-cost', 'Humpty Dumpty, Fate\'s Fall', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'Top=em6eEh9q8y;Bottom=')->pass(1)
    ->tested('Humpty Dumpty, Fate\'s Fall')
    ->register();

gaAbilitySandbox('humpty-dumpty-fate-s-fall-ability-2-pays-its-printed-cost', 'Humpty Dumpty, Fate\'s Fall', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pay(2)->pass(1)
    ->tested('Humpty Dumpty, Fate\'s Fall')
    ->register();
