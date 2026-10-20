<?php
/**
 * Fixtures for cards starting with 'T', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Buff Counters: Tindered Soldier gets buff counter on discard ---
// Play Tindered Soldier (myHand-1 with this deck/seed — verified live, see DevTools notes
// below), then Scars of Old (myHand-0 after Tindered Soldier leaves hand). Reserve costs are
// "choose a card from myHand" MZCHOOSEs, not myField picks — paying Scars of Old's second
// reserve point with the only FIRE card left in hand (myHand-0) is what actually discards a
// FIRE card and fires Tindered Soldier's discard-triggered buff counter
// (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, discardCardAbilities["KEhmWGivJp:0"]
// requires CardElement($discardedCardID) === "FIRE"). Verified: final Counters.buff = 1.
// Notes carried over from the original definition:
//   - Play Tindered Soldier (2 reserve)
//   - Pass fast actions
//   - hand, which is what actually triggers Tindered Soldier's buff counter.
//   - Pass remaining prompts
//   - Scars of Old's own draw+discard: discard from the (now NORM-only) remaining hand.
gaScenario('tindered-soldier-discard-buff')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Tindered Soldier
4 Scars of Old
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
    ->playSlot(1)->pay(1)->target('myHand-3')->passBoth()
    ->playSlot(0)->target('myHand-2')->pay(1)->pass(2)->pay(1)
    ->tested('Tindered Soldier')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED","vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'CardID', 'KEhmWGivJp')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Discarding Scars of Old, a Fire card, gives Tindered Soldier exactly one buff counter')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-0', 'CardID', 'lD0sK81PZT', 'The discarded Fire card is placed in the graveyard, triggering Tindered Soldier')
    ->expectProperty('p1Graveyard-0', 'CardID', 'lD0sK81PZT')
    ->expectProperty('p1Graveyard-1', 'CardID', 'KEhmWGivJp')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-3', 'CardID', 'KEhmWGivJp')
    ->expectZoneCount('myHand', 1, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myGraveyard', 2, 1)
    ->expectZoneCount('myMemory', 4, 1)
    ->register();

// --- Tasershot: load into an unloaded Gun weapon ---
// Tasershot's element is NORM, so no lineage patch is needed, and its memory cost is 0 so
// materializing via a direct FSM click is free. Framework Sidearm (a REGALIA,WEAPON with the
// GUN subtype and no Subcards, i.e. unloaded) is seeded onto the field as the [REST] Load
// ability's target, same pattern as penetrator-round-load-gun/anathemas-end-load-gun/
// incendiary-shot-load-gun. Only the always-available Load half is covered; the [Class Bonus]
// On Champion Hit level-up-punish trigger requires a real combat hit and is out of scope.
// Notes carried over from the original definition:
//   - Framework Sidearm (unloaded GUN weapon) - Load target
gaScenario('tasershot-load-gun')
    ->standardDeck('Tasershot')
    ->seed('myField', 'Framework Sidearm')
    ->seed('myHand', 'Tasershot')
    ->playSlot(1)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Tasershot')
    ->expectProperty('p1Field-1', 'Subcards', '["4x7e22tk3i"]', 'Loading appends the bullet\'s CardID to the target Gun weapon\'s Subcards')
    ->expectZoneCount('myField', 2, 1, 'The bullet is removed from the field entirely, not moved to another zone, once loaded')
    ->expectDqEmpty()
    ->register();

// --- Tome of Sorcery: Class Bonus + Level 2+ On Enter draw into memory, REST Empower 1 ---
// Tome of Sorcery's element is NORM, so no lineage patch is needed, but its On Enter requires
// both a MAGE Class Bonus (physical presence, so Kongming, Fel Eidolon is seeded onto the
// field) AND champion Level 2+ (the starting champion's Counters are patched with 2 level
// counters, same technique as meltdown-level2-destroy-item). Its memory cost is NOT exercised
// for the same FSM-click-bypass reason as entrancing-filigree-enter-banish. Both the On Enter
// draw and the [REST] Empower 1 activated ability are covered in sequence.
// Notes carried over from the original definition:
//   - Kongming, Fel Eidolon (MAGE CHAMPION) - Class Bonus source
//   - Level 2+ condition
gaScenario('tome-of-sorcery-enter-draw-empower')
    ->standardDeck('Tome of Sorcery')
    ->seed('myField', 'Kongming, Fel Eidolon')
    ->champion(level: 2)
    ->seed('myHand', 'Tome of Sorcery')
    ->playSlot(3)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')
    ->tested('Tome of Sorcery')
    ->expectZoneCount('myMemory', 1, 1, 'On Enter draws a card into memory', step: 1)
    ->expectZoneCount('myDeck', 8, 1, 'The On Enter draw reduces the deck by one', step: 1)
    ->expectProperty('p1Field-0', 'TurnEffects', '["sq0ou8vas3","EMPOWERED","EMPOWER_PLUS_1"]', 'Activating the REST ability empowers the first champion by 1')
    ->expectProperty('p1Field-2', 'Status', '1', 'Activating the ability rests Tome of Sorcery as its cost')
    ->expectDqEmpty()
    ->register();

// --- Tsunami of Nanyue: deal 2 damage to all rested allies (default Shifting Currents) ---
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
//   - rested Dungeon Guide - damage target
gaScenario('tsunami-of-nanyue-damage')
    ->standardDeck('Tsunami of Nanyue')
    ->readyFor('Tsunami of Nanyue')
    ->seed('theirField', 'Dungeon Guide', properties: ['Status' => 1])
    ->seed('myHand', 'Tsunami of Nanyue')
    ->castSpell('Tsunami of Nanyue', pay: 5, window: 'both')
    ->register();

// --- Take Cover: target unit you control gains stealth and becomes distant ---
// Notes carried over from the original definition:
//   - Dungeon Guide (ALLY) - target
gaScenario('take-cover-stealth-distant')
    ->standardDeck('Take Cover')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Take Cover')
    ->castSpell('Take Cover', pay: 5, window: 'opp', target: 'myField-1')
    ->register();

// --- Tariff Ring: Banish to tax attack declarations, activated during opponent's recollection ---
// Was previously abandoned after discovering that activateAbilityPrereqs["xnrw8qq1uw:0"]
// (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php) compared GetCurrentPhase() against
// the literal "RECOLLECTION", a string the engine's real phase codes (GrandArchiveSim/
// TurnStates.php: WU->MAT->BREC->REC->DRAW->MAIN) never produce -- the REC_START opportunity
// window (where this ability is actually offered) opens while the phase code is "BREC"
// (GrandArchiveSim/Custom/GameLogic.php's BeforeRecollectionPhase()), not "REC" and never
// "RECOLLECTION". Root-caused to 4 card_abilities DB rows authored with the wrong literal
// (Database/migrations/12_grand_archive_recollection_phase_fix.sql) plus 3 matching
// hand-authored checks in GrandArchiveSim/Custom/{GameLogic,OpportunityLogic}.php. Now that
// both are fixed and GeneratedMacroCode.php regenerated, Tariff Ring's ability is reachable:
// it's seeded directly onto P1's field (an Item ability, not a hand play), P1 passes through
// turn 1's Main/BeforeEnd/BeforeEndOpportunity windows, and turn auto-advances into P2's turn
// 2 BeforeRecollection phase, where GetPlayableOpportunityChoices offers Tariff Ring to P1
// (the non-turn-player) as a MZMAYCHOOSE fast option. Selecting it resolves the "Banish"
// macro: AddGlobalEffects($player, "xnrw8qq1uw") and Tariff Ring moves from field to banish
// (its own activation cost, "Banish CARDNAME:"). The resulting attack-tax computation
// (GrandArchiveSim/Custom/CombatLogic.php's $tariffRingTax) is a combat-declaration-time
// computed cost with no independently stored counter beyond the global effect itself, so it's
// out of scope here -- the global effect's presence is the assertable, semantic proof that the
// ability actually fired.
gaScenario('tariff-ring-attack-tax')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Tariff Ring')
    ->endTurn()
    ->endTurn(2)->answer(1, '-')
    ->endTurn()->answer(2, '-')
    ->endTurn(2)->answer(1, 'myField-1@Activate-0@Banish')
    ->tested('Tariff Ring')
    ->expectZoneCount('myField', 1, 1, 'Tariff Ring leaves the field -- only Spirit of Fire remains')
    ->expectZoneCount('myBanish', 1, 1, 'Banishing Tariff Ring is the activation\'s own cost ("Banish CARDNAME:")')
    ->expectZoneCount('myGlobalEffects', 1, 1, 'The attack-tax global effect is added, proving the ability actually resolved')
    ->expectDqEmpty()
    ->register();

// --- Tera Sight: Preserve. Draw a card. ---
// TERA (advanced element), 0 reserve cost. Same Subcards element-access patch as
// vernal-talisman-preserve-draw/devoted-bloomweaver-class-bonus-empower (Kongming, Fel Eidolon),
// no physical champion needed this time since there's no Class Bonus gate. Preserve (put this
// card into its owner's material deck preserved as it resolves, instead of the graveyard) is a
// generic keyword layered on top of the card's own "Draw($player, 1)" macro.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Tera Sight
gaScenario('tera-sight-preserve-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myHand', 'Tera Sight')
    ->play('Tera Sight')
    ->register();

// --- Taiji of Crystal Strategems: South->East transition, may rest to deal 3 damage to opponent's champion ---
// South->East is adjacent (IsAdjacentDirection: SOUTH<->EAST/WEST), so this reuses the same
// Fel Eidolon Inherited "adjacent direction on Spell activation" trigger mechanism as
// hydroguard-retainer-north-west-draw, just starting from SOUTH and answering "EAST". The
// transition callback (GameLogic.php ~20630-20636) then queues a YES/NO "rest to deal 3
// damage?" decision, answered YES.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock + Fel Eidolon Inherited trigger
//   - Shifting Currents facing South
//   - Taiji of Crystal Strategems
//   - Tera Sight, to trigger the Inherited SC choice on activation
//   - choose adjacent direction EAST
//   - rest Taiji to deal 3 damage
gaScenario('taiji-crystal-strategems-south-east-rest-damage')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'SOUTH'])
    ->seed('myField', 'Taiji of Crystal Strategems')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myHand', 'Tera Sight')
    ->play('Tera Sight')->answer(1, 'EAST')->answer(1, 'YES')
    ->tested('Taiji of Crystal Strategems')
    ->register();

// --- Trusty Steed: On Enter, target ally you control gets +2 POWER until end of turn ---
// NORM (no advanced element) ALLY, so no Subcards lineage patch is needed. Dungeon Guide is
// seeded on the field first as the only other ally, so ZoneSearch("myField", ["ALLY"]) with the
// "pop self off the end" trick (GeneratedMacroCode.php:11162) leaves exactly one legal target.
// Notes carried over from the original definition:
//   - Dungeon Guide, buff target
//   - Trusty Steed
//   - Unlike a SPELL activation (favorable-winds-ally-life-buff), an ALLY's On Enter target
//   - (confirmed via a throwaway debug harness, DevTools/debug_steed.php, now deleted; an extra
//   - decline here gets consumed AS the target choice with chosen="-", silently no-opping the buff).
//   - choose Dungeon Guide
gaScenario('trusty-steed-enter-ally-power-buff')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Trusty Steed')
    ->castSpell('Trusty Steed', pay: 4, target: 'myField-1')
    ->register();

// --- Trained Sharpshooter: [Class Bonus] Ranged 2 ---
// Same turn-cycle approach as automaton-bomber-ranged-attack (Rule 1.h blocks player 1's own
// turn-1 attack), but Trained Sharpshooter's Ranged 2 needs a RANGER Class Bonus
// (IsClassBonusActive scans the whole field for any champion-type object of the right class),
// so also seed a real RANGER champion (Diana, Keen Huntress) onto player 2's field.
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (RANGER champion) - Class Bonus source
//   - Trained Sharpshooter, awake and Distant
//   - ends turn 1
gaScenario('trained-sharpshooter-class-bonus-ranged-attack')
    ->standardDeck('Trained Sharpshooter')
    ->seed('myField', 'Diana, Keen Huntress', player: 2)
    ->seed('myField', 'Trained Sharpshooter', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->action(2, 10002, 'myField-2!FSM!')->answer(2, 'theirField-0')->pass(2)->pass(1)
    ->tested('Trained Sharpshooter')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 4, 'Trained Sharpshooter deals 2 base + 2 [Class Bonus] Ranged POWER while distant')
    ->register();

// --- Take Aim: target unit's next attack gets +2 POWER; [Class Bonus] also gains Ranged 2 ---
// Same turn-cycle approach as automaton-bomber-ranged-attack (Rule 1.h blocks player 1's own
// turn-1 attack): player 1 ends turn 1 with a single CustomInput Pass, then player 2 plays
// Take Aim targeting their own ally (already Distant and awake) with a RANGER Class Bonus
// active, then attacks. AddTurnEffect("vnta6qsesw"/"RANGED_2") only turns into actual POWER
// once the target's attack is declared (CombatLogic.php converts vnta6qsesw ->
// vnta6qsesw_POWER at that point), and the CB-granted "RANGED_2" only contributes via
// GetRangedValue while the unit is Distant on its controller's turn.
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (RANGER champion) - Class Bonus source
//   - Dungeon Guide, awake and Distant - Take Aim's target
//   - ends turn 1
gaScenario('take-aim-class-bonus-ranged-attack')
    ->standardDeck('Take Aim')
    ->seed('myField', 'Diana, Keen Huntress', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->seed('myHand', 'Take Aim', player: 2)
    ->endTurn()
    ->play('Take Aim', 2)->pay(2, 2)->answer(2, 'myField-2')
    ->action(2, 10002, 'myField-2!FSM!')->answer(2, 'theirField-0')->pass(2)->pass(1)
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 5, '1 base + 2 Take Aim + 2 [Class Bonus] Ranged 2 (target is already Distant)')
    ->register();

// --- Take Point: your champion gains taunt until the beginning of your next turn ---
// Take Point applies "TAUNT_NEXT_TURN" (a delayed grant, converted to real TAUNT at the
// beginning of the caster's next turn), not TAUNT directly, so it's tested at the point the
// tag is applied rather than trying to advance all the way to the conversion.
gaScenario('take-point-champion-taunt-next-turn')
    ->standardDeck('Take Point')
    ->seed('myHand', 'Take Point')
    ->castSpell('Take Point', pay: 2)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["TAUNT_NEXT_TURN","vz4kc558yx-ACTION_ACTIVATED"]', 'Champion gains taunt until the beginning of the caster\'s next turn')
    ->register();

// --- Tonoris, Lone Mercenary: On Enter, gain taunt until beginning of next turn ---
// Tonoris, Lone Mercenary is level 1, one level above the default level-0 starting champion
// (Spirit of Fire), so no lineage/element patch is needed -- 0+1 is already a legal level-up
// (same shape as dante-prodigal-swain-summon-token/lorraine-wandering-warrior-levelup). Its
// NORM element is always playable. Champion-swap materialization is only offered through the
// material-phase MZMAYCHOOSE at the start of a turn, so both players end their first turn
// (P1 -> P2) to reach that prompt on P1's next turn. Its printed cost is 1 memory, paid from a
// filler card seeded directly into myMemory. Choosing it completes the swap and its On Enter
// ability (GeneratedMacroCode.php enterAbilities["zb14m4c8lj:0"]) calls
// AddTurnEffect($mzID, "TAUNT_NEXT_TURN") on the champion itself.
// Notes carried over from the original definition:
//   - filler card in memory to pay Tonoris, Lone Mercenary's 1-memory level-up cost
gaScenario('tonoris-lone-mercenary-on-enter-taunt')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Tonoris, Lone Mercenary
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
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Tonoris, Lone Mercenary')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN","TAUNT_NEXT_TURN"]', 'On Enter: Tonoris gains taunt until the beginning of your next turn')
    ->register();

// --- Tonoris, Might of Humanity: On Enter, next attack this turn gets +3 POWER ---
// Tonoris, Might of Humanity is level 2 (Tonoris Lineage), but CanChampionLevelUpIntoCard only
// checks $targetLevel === $currentLevel + 1 (GameLogic.php ~19487-19501) -- lineage text is not
// enforced by the level-up gate itself (same bypass used for rousing-slam-class-bonus and the
// Diana champion-lineage fixtures this session). The starting champion is patched directly to
// Tonoris, Lone Mercenary (zb14m4c8lj, level 1) so a REAL level-up into Might of Humanity is
// legal; patching the CardID (rather than leveling up twice) is fine here since we don't care
// about Lone Mercenary's own On Enter firing, only Might of Humanity's. NORM element needs no
// lineage/Subcards patch to be playable. Champion-swap materialization is only offered through
// the material-phase MZMAYCHOOSE at the start of a turn, so both players end their first turn
// (P1 -> P2) to reach that prompt on P1's next turn; its 2-memory cost is paid from two filler
// cards seeded directly into myMemory. Choosing it completes the swap and its On Enter ability
// (GeneratedMacroCode.php enterAbilities["yevpmu6gvn:0"]) calls
// AddTurnEffect($mzID, "yevpmu6gvn") on the champion itself -- CombatLogic.php ~1688 reads and
// consumes that exact marker to grant +3 POWER on the champion's next attack, so asserting the
// marker is present directly confirms the On Enter ability fired.
// Notes carried over from the original definition:
//   - Tonoris, Lone Mercenary (level 1) - level-up precondition
//   - filler card 1/2 in memory to pay Might of Humanity's 2-memory level-up cost
//   - filler card 2/2
gaScenario('tonoris-might-of-humanity-on-enter-next-attack-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Tonoris, Might of Humanity
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
    ->champion(cardName: 'Tonoris, Lone Mercenary')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Tonoris, Might of Humanity')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN","yevpmu6gvn"]', 'On Enter: Tonoris\' next attack this turn gets +3 POWER (marker consumed by CombatLogic.php on the next attack)')
    ->register();

// --- Tonoris, Genesis Aegis: at recollection, choose an Obelisk token not yet chosen ---
// Genesis Aegis's recollection trigger is a plain CardID switch-case inside
// ResolveBeforeRecollectionPhaseStart (GameLogic.php ~9089, same dispatcher used for Foster
// processing) rather than a materialized-Enter/activateAbility macro, so the starting champion
// is patched directly to ta6qsesw2u -- no real level-up/lineage/element unlock needed, since
// this switch only reads the already-on-field object's CardID, not how it got there (same
// reasoning as the Foster class-bonus champion patches this session). Reaching player 1's own
// recollection phase just needs the established 3-action shortcut: player 1 ends turn 1,
// player 2 ends their turn (still global turn 1), then player 1 declines their own MAT-phase
// materialize offer, auto-advancing into player 1's own BREC. TonorisRecollection()
// (CardDQHandlers.php ~2350) finds no obelisks in Counters['tonoris_chosen'] yet, so it queues
// an MZCHOOSE over all three myTempZone Obelisk choices; choosing myTempZone-0 (Obelisk of
// Armaments, wk0pw0y6is) summons it onto the field via TonorisChooseObelisk and records the
// choice.
// Notes carried over from the original definition:
//   - Tonoris, Genesis Aegis
//   - ends turn 1
//   - ends player 2's turn (still global turn 1)
//   - decline player 1's own MAT-phase materialize offer -> reaches BREC/Tonoris recollection processing
//   - choose Obelisk of Armaments
gaScenario('tonoris-genesis-aegis-recollection-obelisk')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Tonoris, Genesis Aegis')
    ->endTurn()
    ->endTurn(2)->pass(1)->target('myTempZone-0')
    ->tested('Tonoris, Genesis Aegis')
    ->expectZoneCount('myField', 2, 1, 'choosing an Obelisk summons it onto the field')
    ->expectProperty('p1Field-1', 'CardID', 'wk0pw0y6is', 'At beginning of recollection phase, choosing Obelisk of Armaments summons it')
    ->register();

// --- Tome of Knowledge: Banish -- draw a card ---
// Tome of Knowledge is REGALIA -- seeded directly onto myField. Same Scry-the-Skies warm-up +
// direct Activate:0 click as crystal-of-empowerment-banish-level.
// Notes carried over from the original definition:
//   - Tome of Knowledge
//   - Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
//   - pay Scry the Skies' 1 reserve
gaScenario('tome-of-knowledge-banish-draw')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Tome of Knowledge')
    ->seed('myHand', 'Scry the Skies')
    ->castSpell('Scry the Skies', pay: 1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Tome of Knowledge')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'F9POfB5Nah')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-0', 'CardID', 'yDARN8eV6B', 'Tome of Knowledge banished itself as the cost')
    ->expectProperty('p1Banish-0', 'Status', '2')
    ->expectDamage('p1Banish-0', 0)
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 7, 1)
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Training Session: target ally gets a buff counter ---
// Training Session (TAMER,WARRIOR, NORM, no Class Bonus gate on the macro itself) targets an
// ally on the field and puts a buff counter on it. Dungeon Guide seeded as the target.
// Notes carried over from the original definition:
//   - Dungeon Guide, the target
gaScenario('training-session-buff-counter')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Training Session')
    ->castSpell('Training Session', pay: 2, target: 'myField-1')
    ->expectDqEmpty(step: 4)
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]', step: 4)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'A buff counter was put on the targeted ally', step: 4)
    ->expectProperty('p1Graveyard-0', 'CardID', 'G42RDwb3Ko', step: 4)
    ->expectZoneCount('myHand', 5, 1, step: 4)
    ->expectZoneCount('myGraveyard', 1, 1, step: 4)
    ->expectZoneCount('myMemory', 2, 1, step: 4)
    ->register();

// --- Tempest Downfall: deal 3 damage to target ally/champion (4etkr73opc) ---
// The lineage-unlocked activation opens a real Opportunity window (other WIND-eligible filler
// copies remain in hand), unlike the "nothing left to offer" fixtures elsewhere in this file --
// decline it ('-') so 4etkr73opc itself resolves and its on-play target MZCHOOSE is queued,
// then answer that immediately (see astral-seal-negate-activation-banish's note).
// Notes carried over from the original definition:
//   - A permanent lineage unlock, not PRISMATIC_CODEX_IGNORE_ELEMENT: that self-consuming
//   - bypass is checked (and consumed) TWICE for a single activation -- once by
//   - CanActivateCard()'s own element gate, again by DoActivateCard()'s redundant
//   - CanPlayerUseCardElement($player,$cardID,true,true) call -- so a 1-stack budget is
//   - silently exhausted by the first check and the second always fails, aborting the whole
//   - activation with no decision ever queued and no error.
//   - WIND lineage/element unlock
//   - Dungeon Guide, target ally
gaScenario('tempest-downfall-target-damage')
    ->deck($fillerDeck)
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide', properties: ['TurnEffects' => []])
    ->seed('myHand', 'Tempest Downfall')
    ->play('Tempest Downfall')->pay(3)->answer(1, '-')->target('myField-1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', '4etkr73opc')
    ->expectProperty('p1Graveyard-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-1', 'Status', '2')
    ->expectDamage('p1Graveyard-1', 3, 'Playing Tempest Downfall fires its on-play trigger (playCardAbilities["4etkr73opc:0"]) and deals exactly 3 damage to the target ally (which dies to it and lands in the graveyard carrying that Damage value)')
    ->expectProperty('p1Graveyard-1', 'Counters', '[]')
    ->expectProperty('p1Graveyard-1', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 2, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->register();

// --- Thieving Cut: Prepare 1, On Hit draw a card if it was prepared ---
// Thieving Cut's element is NORM, so no lineage patch is needed. ATTACK cards can't be
// activated by the game's first player on turn 1 (CanActivateAttackCardNow/
// IsFirstTurnAttackLocked in GameLogic.php), so player 1 ends turn 1 first and player 2 plays
// Thieving Cut on their own turn 1 instead (same turn-cycle shape as
// bulwark-sword-class-bonus-attack-cost). Player 2's champion is pre-seeded with 1 preparation
// counter directly (normally only reachable via a separate preparation-counter-granting
// effect) so its "Prepare 1" additional cost (remove 1 preparation counter as you activate it,
// CardActivated macro 7t9m4muq2r:0 in GeneratedMacroCode.php) can actually be paid. Answering
// YES stores wasPrepared=YES via DecisionQueueController; the On Hit macro (7t9m4muq2r:0
// onHitAbilities) reads that variable back and draws a card only when it is "YES" -- a card
// that was never marked prepared would not draw.
// Notes carried over from the original definition:
//   - Prepare-ability cost fuel
//   - ends turn 1 (first-player attack lock)
gaScenario('thieving-cut-prepare-onhit-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['preparation' => 1]], player: 2)
    ->seed('myHand', 'Thieving Cut', player: 2)
    ->endTurn()
    ->play('Thieving Cut', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 3)
    ->expectProperty('p1Field-0', 'Counters', '{"_champDamageThisTurn":3}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P2"]')
    ->expectProperty('p2Field-0', 'Status', '1')
    ->expectProperty('p2Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":3}', 'Prepare 1\'s cost consumed the champion\'s preparation counter (no longer present)')
    ->expectProperty('p2Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-6', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Graveyard-0', 'CardID', '7t9m4muq2r')
    ->expectProperty('p2Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectZoneCount('theirHand', 7, 1, 'On Hit draws a card because the attack was prepared (6 after paying costs, +1 draw = 7)')
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 2, 1)
    ->register();

// --- Tenderheart Guard: On Enter, each player may discard to draw into memory; gain Crowd's Favor ---
// Tenderheart Guard's element is FIRE, matching the starting champion, so no lineage patch is
// needed. Both players answer YES to the discard-to-draw-into-memory offer so both halves of
// the trigger (TenderheartGuardEnter -> TenderheartGuardDiscard -> TenderheartGuardDrawMemory,
// CardDQHandlers.php) are exercised, plus the unconditional GainCrowdsFavor() call.
gaScenario('tenderheart-guard-enter-discard-draw-crowds-favor')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Tenderheart Guard')
    ->play('Tenderheart Guard')->pay(2)->answer(1, 'YES')->pay(1)->answer(2, 'YES')->pay(1, 2)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'CardID', '0ZWcrEsFHA')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'px60u5n1do', 'Own player discards a card')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'em6eEh9q8y', 'Own player draws the discarded-for card into memory')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'gpmJdGYqoC', 'Gains the Crowd\'s Favor status')
    ->expectProperty('p2Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-3', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-5', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-0', 'CardID', 'px60u5n1do', 'Opponent also discards a card')
    ->expectProperty('p2Memory-0', 'CardID', 'em6eEh9q8y', 'Opponent draws the discarded-for card into memory')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->expectZoneCount('theirHand', 6, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 1, 1)
    ->register();

// --- Templar of the Eternal: (2), Return a regalia -> buff counter + spellshroud ---
// Templar of the Eternal (peyG8Hfgqt, WARRIOR [Class Bonus]) and a Backup Charger (9gv4vm4kj3,
// REGALIA) are seeded directly onto the field, with the champion patched to Lorraine, Wandering
// Warrior (DpHDGaX2Pn) for the class bonus. Its activated ability is triggered via the
// CustomInput "Activate:0" click (ActivateAbility -> DoActivatedAbility ->
// activateAbilityAbilities["peyG8Hfgqt:0"], GeneratedMacroCode.php), which pays a 2-reserve
// cost (Custom/GameLogic.php's ActivatedAbilityCost, case "peyG8Hfgqt") from hand, then calls
// TemplarEternalAbility() (Custom/GameLogic.php), offering the Backup Charger as an MZCHOOSE
// target; choosing it returns it to material, and adds a buff counter plus SPELLSHROUD to
// Templar of the Eternal itself.
gaScenario('templar-of-the-eternal-return-regalia')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(cardName: 'Lorraine, Wandering Warrior')
    ->seed('myField', 'Templar of the Eternal')
    ->seed('myField', 'Backup Charger')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myField-2')
    ->tested('Templar of the Eternal')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Templar of the Eternal gains a buff counter after returning the chosen regalia.')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","SPELLSHROUD"]', 'Templar of the Eternal also gains SPELLSHROUD after returning the chosen regalia.')
    ->expectProperty('p1Field-1', 'Status', '2', 'Templar of the Eternal\'s printed cost has no [REST] ([Class Bonus] (2), Return a regalia you control to its owner\'s material deck): it is still awake after the activation')
    ->register();

// --- Trine Recursion: put target graveyard card into owner's deck third from top ---
// Trine Recursion (dwZvL9K0Ke, ASTRA, reserve 1) targets any graveyard card
// (cardActivatedAbilities["dwZvL9K0Ke:0"], GeneratedMacroCode.php) and its
// customDQHandlers["dwZvL9K0Ke:0:CardActivated-1"] removes the target and splices it into the
// OWNER's deck at index min(2, count) -- "third from the top". ASTRA is an advanced element
// (CanPlayerMeetCardElementRequirements/GetPlayerEnabledElements, GameLogic.php ~19784) --
// without unlocking it the FSM play silently no-ops (success:true, no decision queued, no
// state change at all), so the champion is CardID-patched to Arisanna, Astral Zenith
// (q3huqj5bba, CLERIC/ASTRA) to unlock ASTRA access (this also satisfies the Arisanna/CLERIC
// Class Bonus other Arisanna fixtures need). A single distinguishable card (Fluffy Shopkeep)
// is seeded into player 1's own graveyard as the target.
// Notes carried over from the original definition:
//   - Arisanna, Astral Zenith - ASTRA/CLERIC/Arisanna-Bonus unlock
//   - Fluffy Shopkeep - graveyard target
gaScenario('trine-recursion-graveyard-to-deck-third')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Arisanna, Astral Zenith')
    ->seed('myGraveyard', 'Fairy Whispers')
    ->seed('myHand', 'Trine Recursion')
    ->castSpell('Trine Recursion', pay: 1, target: 'myGraveyard-0')
    ->expectProperty('p1Deck-2', 'CardID', 'n8wyfG9hbY', 'Targeted graveyard card (Fluffy Shopkeep) lands exactly third from the top of its owner\'s deck')
    ->expectZoneCount('myDeck', 6, 1, 'Deck grows by exactly the one card moved out of the graveyard')
    ->expectProperty('p1Graveyard-0', 'CardID', 'dwZvL9K0Ke', 'The targeted card actually leaves the graveyard -- only the spell itself (which resolves there after activating) remains')
    ->register();

// --- Tonic of Remembrance: [CB] Banish self -- return up to 3 cards from memory to hand ---
// Tonic of Remembrance is seeded directly onto the field (Brewing isn't the clause under
// test). activateAbilityPrereqs["uqrptjej4m:0"] (GeneratedMacroCode.php) requires
// IsClassBonusActive($player, ["CLERIC"]) -- the champion is CardID-patched to Arisanna,
// Herbalist Prodigy (b31x97n2jn, CLERIC) to satisfy it. Two known cards are seeded into
// memory; activateAbilityAbilities["uqrptjej4m:0"] offers an MZMULTICHOOSE of up to 3 memory
// cards, and customDQHandlers["uqrptjej4m:0:ActivateAbility-1"] moves the chosen cards to
// hand.
// Notes carried over from the original definition:
//   - Arisanna, Herbalist Prodigy - CLERIC Class Bonus unlock
//   - Tonic of Remembrance - the activator
//   - Fluffy Shopkeep - memory card 1
//   - memory card 2
gaScenario('tonic-of-remembrance-banish-return-memory')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Arisanna, Herbalist Prodigy')
    ->seed('myField', 'Tonic of Remembrance')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myMemory-0&myMemory-1')
    ->tested('Tonic of Remembrance')
    ->register();

// --- Twinstar Tonic: Sacrifice self -- for the rest of the game, may copy starcalled activations ---
// Twinstar Tonic is seeded directly onto the field (Brewing isn't the clause under test).
// activateAbilityAbilities["yBDxSHkT1s:0"] (GeneratedMacroCode.php) just calls
// AddGlobalEffects($player, "yBDxSHkT1s") -- a permanent, foreverEffects=true marker
// ($foreverEffects["yBDxSHkT1s"]=true, GameLogic.php ~18266). The "for the rest of the game,
// may copy that starcalled activation" half (GameLogic.php ~16552, checked every time a
// player starcalls) is a downstream consumer of this same flag and is out of scope for this
// fixture -- the flag itself, set once and meant to persist permanently, is what this card's
// own ability is responsible for, and that's what's asserted.
// Notes carried over from the original definition:
//   - Twinstar Tonic - the activator
gaScenario('twinstar-tonic-sacrifice-starcall-copy')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->seed('myField', 'Twinstar Tonic')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Twinstar Tonic')
    ->register();

// --- Tactful Sergeant: On Enter -- if your champion has attacked this turn, draw into memory ---
// Tactful Sergeant's On Enter (enterAbilities["7UXGwC7lSO:0"], GeneratedMacroCode.php) checks
// OnAttackCallCount($player) > 0 -- a per-player-per-turn attack counter incremented by any
// real attack declaration this turn (OnAttack(), ZoneAccessors.php), not specifically a
// champion attack despite the printed text. Tactful Sergeant's own element is WIND, so
// by8145w2u2_WIND is added as a global effect (Imperial Seal's basic-element unlock mechanic)
// to unlock playing it without a real WIND-lineage champion. Same real attack-declaration
// shape as blistering-insurgent-attack-buff: P1 pass, P2 pass, P1 declines the turn-3 MAT
// offer, then a Dungeon Guide ALLY (seeded awake onto P1's field) declares a real attack
// against the opposing champion, P2 declines Retaliate?, and only then is Tactful Sergeant
// played from hand (its own 4-reserve cost) -- proving materializing a card after an already-
// resolved attack, later in the same turn, is legal in this engine.
// Notes carried over from the original definition:
//   - Spirit of Wind (WIND) lineage patch - unlocks WIND element access permanently (AddGlobalEffects' by8145w2u2_WIND is only "until end of turn" and would expire before Tactful Sergeant is played, several turns later)
//   - Dungeon Guide ALLY, seeded awake (BridgeAddToZone defaults Status=2)
//   - decline turn-3 MAT offer
//   - Dungeon Guide declares a real attack
//   - target the opposing champion
//   - decline Retaliate?
//   - play Tactful Sergeant after the attack -- fires On Enter
//   - pay reserve 1/4
//   - pay reserve 2/4
//   - pay reserve 3/4
//   - pay reserve 4/4
gaScenario('tactful-sergeant-enter-attacked-draw-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
5 Fluffy Shopkeep
DECK
    )
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Tactful Sergeant')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')->answer(2, '-')
    ->castSpell('Tactful Sergeant', pay: 4)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'Status', '1')
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p1Field-2', 'CardID', '7UXGwC7lSO')
    ->expectProperty('p1Field-2', 'Status', '2')
    ->expectDamage('p1Field-2', 0)
    ->expectProperty('p1Field-2', 'Counters', '[]')
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-4', 'CardID', 'px60u5n1do', 'Tactful Sergeant\'s On Enter (enterAbilities["7UXGwC7lSO:0"]) checked OnAttackCallCount(1) > 0 (true, since the Dungeon Guide ally attacked earlier this turn) and drew the last remaining deck card into memory via DrawIntoMemory -- the 5th memory card beyond the 4 hand cards moved there to pay Tactful Sergeant\'s own 4-reserve activation cost')
    ->expectDamage('p2Field-0', 1)
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":1}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN"]')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myField', 3, 1)
    ->expectZoneCount('myMemory', 5, 1, '5 cards in memory: 4 from paying Tactful Sergeant\'s reserve cost plus 1 more from its On Enter draw-into-memory (would be 4, not 5, if the ability\'s OnAttackCallCount check or DrawIntoMemory call never ran)')
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Torpid Fractal: On Enter rest target ally (<=2 POWER); stays rested while you control it ---
// NOTE: this fixture intentionally spans a full extra round (P1 t1 -> P2 t1 -> P1 t2) so player 2's
// OWN wake-up phase genuinely executes with GetTurnNumber()==2. An earlier version of this fixture
// stopped right after P1's turn-1 pass, where GetTurnPlayer()==2 but GetTurnNumber() is STILL 1 --
// EndPhase() (Custom/GameLogic.php ~line 666-669) only increments TurnNumber when turnPlayer wraps
// back to the first player, so "Turn 1" spans BOTH players' opening turns, and WakeUpPhase()'s own
// guard ("if($currentTurn === 1) return;", ~line 8088-8089) skips wake-up processing for player 2's
// first turn too, regardless of Torpid Fractal. That made the old fixture a silent fake-pass: the
// ally stayed rested for a reason unrelated to Torpid Fractal (nothing ever tries to wake anyone
// during TurnNumber 1). Confirmed via instrumentation (log line inside the FROZEN_BY_TORPID check
// never fired under the old sequence) and via break/restore (forcing the opponent-Torpid-Fractal
// detection loop to always report false still left the OLD fixture passing). This version declines
// P1's turn-2 MAT-phase materialize offer (mode 100, cardID 'PASS' -- the mid-turn 'myHealth' Pass
// button refuses while a MZMAYCHOOSE decision is pending, same technique as
// samaritan-reach-attacking-ally-damage) before ending turn 2, so the wake-up phase immediately
// after genuinely belongs to player 2 with TurnNumber==2 -- re-instrumented and confirmed the
// FROZEN_BY_TORPID check now actually runs (currentTurn=2, turnPlayer=2), and re-verified
// break/restore on THIS version: forcing the detection loop false now correctly fails the fixture
// (ally wakes to Status=2, TurnEffects clears), and restoring passes again.
// Notes carried over from the original definition:
//   - end P1 turn 1
//   - end P2 turn 1
//   - decline P1's turn-2 MAT-phase materialize offer
//   - end P1 turn 2 -- should trigger P2's REAL 2nd wake-up phase
gaScenario('torpid-fractal-enter-rest-stay-frozen')
    ->deckTemplate('diao-awakened-deacon', 'Torpid Fractal')
    ->champion(cardName: 'Diao Chan, Enchantress', lineage: ['Spirit of Water'])
    ->seed('theirField', 'Dungeon Guide', properties: ['Status' => 2])
    ->seed('myHand', 'Torpid Fractal')
    ->castSpell('Torpid Fractal', pay: 4, target: 'theirField-1')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()
    ->register();

// --- Turm, Schwartz Rook: [Alice Bonus] On Leave: put its buff counters on a Pawn ally you control ---
// Second On Leave card (besides Green Slime) that offers ANOTHER field object as the target via a
// positional mzID computed while the departing object still occupies its slot; proves the
// GameOnZoneElementSpliced() re-index fix (GrandArchiveSim/Custom/GameLogic.php) is class-wide and
// not specific to Green Slime's closure.
// Turm, Schwartz Rook (rYyOEGB3tD, GUARDIAN ROOK ally): "[Alice Bonus] On Leave: Put the buff
// counters that were on CARDNAME on a Pawn ally you control." Its leaveFieldAbilities closure
// (GeneratedMacroCode.php) queues an MZCHOOSE over every Pawn ally by positional mzID while Turm
// itself still occupies myField-1; the Pawn Piece token (Rpr6yCQKU6) at myField-2 is spliced
// down to myField-1 once Turm leaves. Same class of bug as Green Slime's fixture
// (green-slime-class-bonus-on-leave-transfer-buff).
// The champion's CardID is patched to Silvie, With the Pack (base level 2 -> Excoriate costs 1
// less) with Spirit of Fire (FIRE unlock) AND Alice, Golden Queen (daip7s9ztd) layered into
// Subcards so IsAliceBonusActive() (ChampionHasInLineage) holds -- same "CardID/Subcards
// layering" the Green Slime fixture uses. Turm is seeded directly with 3 buff counters (its
// own attack trigger that earns them is a separate clause); the leave event is real: Excoriate
// (reserve cost 4 or less) is played for real from hand and destroys Turm (reserve cost 3).
// Notes carried over from the original definition:
//   - Turm -> myField-1
//   - Pawn Piece token, the only Pawn ally -> myField-2
//   - decline to respond: Alice's champion has an activatable ability (Shield Chessman allies), so the Opportunity window is offered
//   - target Turm for destruction
//   - On Leave: choose the Pawn (now myField-1 after Turm's slot is spliced out)
gaScenario('turm-schwartz-rook-alice-bonus-on-leave-buff-transfer')
    ->deckTemplate('silvie-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack', lineage: ['Spirit of Fire', 'Alice, Golden Queen'])
    ->seed('myField', 'Turm, Schwartz Rook', properties: ['Counters' => ['buff' => 3]])
    ->seed('myField', 'Pawn Piece')
    ->seed('myHand', 'Excoriate')
    ->play('Excoriate')->pay(3)->pass(1)->target('myField-1')->target('myField-1')
    ->tested('Turm, Schwartz Rook', 'Excoriate')
    ->register();

// --- Tristan, Underhanded: On Enter, may put a preparation counter on Tristan (YES branch) ---
// Tristan, Underhanded (bjlwabipl6, CHAMPION, level 1): "On Enter: You may put a preparation
// counter on Tristan. If you don't, you gain agility 3 for this turn." The starting champion is
// the Material-section level-0 Spirit of Wind (WIND, so no lineage patch is needed); player 1 ends
// turn 1, player 2 ends turn 2, and in player 1's materialize phase the real level-up into
// Tristan, Underhanded is chosen (its 1-point memory cost is paid from one seeded memory card), so
// enterAbilities["bjlwabipl6:0"] fires for real. It queues a YES/NO; answering YES runs
// AddPrepCounter() and puts one preparation counter on the new champion, and (unlike the NO
// branch, see tristan-underhanded-levelup-enter-agility) no AGILITY_3 global effect appears.
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up Spirit of Wind into Tristan, Underhanded (real level-up, so its On Enter genuinely fires)
//   - put a preparation counter on Tristan
gaScenario('tristan-underhanded-levelup-enter-prep-counter')
    ->deck($tristanDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'YES')
    ->tested('Tristan, Underhanded')
    ->expectProperty('p1Field-0', 'CardID', 'bjlwabipl6', 'The real level-up into Tristan, Underhanded completed')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'Answering YES puts exactly one preparation counter on Tristan')
    ->expectProperty('p1GlobalEffects-1', 'CardID', '', 'No AGILITY_3 global effect exists (only the LEVELED_UP_THIS_TURN bookkeeping effect at index 0): the "if you do not" branch was not taken')
    ->expectDqEmpty()
    ->register();

// --- Tristan, Underhanded: On Enter, declining the counter grants agility 3 for the turn (NO branch) ---
// Tristan, Underhanded (bjlwabipl6): "On Enter: You may put a preparation counter on Tristan. If
// you don't, you gain agility 3 for this turn." Same real level-up as tristan-underhanded-levelup-
// enter-prep-counter, but the YES/NO is answered NO: GainAgility($player, 3) adds the AGILITY_3
// global effect (the beginning-of-end-phase memory-return itself is the shared agility mechanism,
// already covered by nimble-court-assassin-enter-agility) and no preparation counter is put on
// Tristan.
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up Spirit of Wind into Tristan, Underhanded
//   - decline the preparation counter -> gain agility 3
gaScenario('tristan-underhanded-levelup-enter-agility')
    ->deck($tristanDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'NO')
    ->tested('Tristan, Underhanded')
    ->expectProperty('p1Field-0', 'CardID', 'bjlwabipl6', 'The real level-up into Tristan, Underhanded completed')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Declining puts no preparation counter on Tristan')
    ->expectProperty('p1GlobalEffects-1', 'CardID', 'AGILITY_3', 'Declining grants agility 3 for this turn (global effect AGILITY_3)')
    ->expectDqEmpty()
    ->register();

// --- Tristan, Hired Blade: On Enter with exactly two preparation counters draws one card ---
// Tristan, Hired Blade (gt7lh9v221, CHAMPION, level 2): "On Enter: If Hired Blade has two or more
// preparation counters on her, draw a card. Then if she has four or more preparation counters on
// her, draw an additional card." The starting champion is patched directly to Tristan, Underhanded
// (level 1, a legal predecessor; CanChampionLevelUpIntoCard only checks printed level) with 2
// preparation counters, then player 1 performs a real level-up in the materialize phase, so
// enterAbilities["gt7lh9v221:0"] fires for real and counts the counters on the champion object
// that is now Hired Blade. Player 1's hand is 7 at the start of the materialize phase; the normal
// turn draw adds 1 and the On Enter draw adds the rest, so the final hand size is 9 (compare the
// 8-card hand after the draw-less Tristan, Underhanded level-up in tristan-underhanded-levelup-
// enter-prep-counter): two preparation counters satisfy only the first threshold (>= 2): exactly
// one card is drawn.
// Notes carried over from the original definition:
//   - Tristan, Underhanded (level 1) already carrying 2 preparation counters (CardID patch; counters survive the real level-up)
//   - level-up memory cost 1/2
//   - level-up memory cost 2/2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Tristan, Hired Blade (real level-up, so its On Enter fires)
gaScenario('tristan-hired-blade-levelup-enter-draw-one')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'bjlwabipl6', 'Subcards' => ['pNiyaGlIe7'], 'Counters' => ['preparation' => 2]])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-1')
    ->tested('Tristan, Hired Blade')
    ->expectProperty('p1Field-0', 'CardID', 'gt7lh9v221', 'The real level-up into Tristan, Hired Blade completed')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'The preparation counters carry over and are not consumed by the On Enter')
    ->expectZoneCount('myHand', 9, 1, 'Hand size 9 = 7 starting cards + 1 normal turn draw + 1 card(s) from the On Enter')
    ->expectDqEmpty()
    ->register();

// --- Tristan, Hired Blade: On Enter with four or more preparation counters draws two cards ---
// Tristan, Hired Blade (gt7lh9v221, CHAMPION, level 2): "On Enter: If Hired Blade has two or more
// preparation counters on her, draw a card. Then if she has four or more preparation counters on
// her, draw an additional card." The starting champion is patched directly to Tristan, Underhanded
// (level 1, a legal predecessor; CanChampionLevelUpIntoCard only checks printed level) with 4
// preparation counters, then player 1 performs a real level-up in the materialize phase, so
// enterAbilities["gt7lh9v221:0"] fires for real and counts the counters on the champion object
// that is now Hired Blade. Player 1's hand is 7 at the start of the materialize phase; the normal
// turn draw adds 1 and the On Enter draw adds the rest, so the final hand size is 10 (compare the
// 8-card hand after the draw-less Tristan, Underhanded level-up in tristan-underhanded-levelup-
// enter-prep-counter): four preparation counters satisfy both thresholds (>= 2 and >= 4): exactly
// two cards are drawn.
// Notes carried over from the original definition:
//   - Tristan, Underhanded (level 1) already carrying 4 preparation counters (CardID patch; counters survive the real level-up)
//   - level-up memory cost 1/2
//   - level-up memory cost 2/2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Tristan, Hired Blade (real level-up, so its On Enter fires)
gaScenario('tristan-hired-blade-levelup-enter-draw-two')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'bjlwabipl6', 'Subcards' => ['pNiyaGlIe7'], 'Counters' => ['preparation' => 4]])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-1')
    ->tested('Tristan, Hired Blade')
    ->expectProperty('p1Field-0', 'CardID', 'gt7lh9v221', 'The real level-up into Tristan, Hired Blade completed')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":4}', 'The preparation counters carry over and are not consumed by the On Enter')
    ->expectZoneCount('myHand', 10, 1, 'Hand size 10 = 7 starting cards + 1 normal turn draw + 2 card(s) from the On Enter')
    ->expectDqEmpty()
    ->register();

// --- Tristan, Shadowdancer: On Enter, summon two Ominous Shadow tokens and put a preparation counter on Tristan ---
// Tristan, Shadowdancer (he6kd7hocc, CHAMPION, level 3): "On Enter: Summon two Ominous Shadow
// tokens and put a preparation counter on Tristan." The starting champion is patched to Tristan,
// Hired Blade (level 2, the required predecessor), then a real level-up in the materialize phase
// (3 memory cards paid) fires enterAbilities["he6kd7hocc:0"], which runs MZAddZone() twice for the
// Ominous Shadow token (gveirpdm44, a phantasia ally) and AddCounters(preparation, 1) on the new
// champion.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (level 2) -> legal predecessor for the level-3 Shadowdancer
//   - level-up memory cost 1/3
//   - level-up memory cost 2/3
//   - level-up memory cost 3/3
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Tristan, Shadowdancer (real level-up, so its On Enter fires)
gaScenario('tristan-shadowdancer-levelup-enter-summon-shadows-prep')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-2')
    ->tested('Tristan, Shadowdancer')
    ->expectProperty('p1Field-0', 'CardID', 'he6kd7hocc', 'The real level-up into Tristan, Shadowdancer completed')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'Exactly one preparation counter is put on Tristan')
    ->expectProperty('p1Field-1', 'CardID', 'gveirpdm44', 'First Ominous Shadow token summoned')
    ->expectProperty('p1Field-2', 'CardID', 'gveirpdm44', 'Second Ominous Shadow token summoned')
    ->expectZoneCount('myField', 3, 1, 'Exactly three field objects: Tristan plus the two summoned tokens')
    ->expectDqEmpty()
    ->register();

// --- Tristan, Shadowdancer: remove two preparation counters to change the target of an attack on Tristan to a phantasia ally; Redirect does NOT rest Tristan ---
// Tristan, Shadowdancer (he6kd7hocc): "Remove two preparation counters from Tristan: Change the
// target of an attack that targets Tristan to a phantasia ally you control." The printed cost is
// ONLY "remove two preparation counters" -- there is no [REST] in it. Redirect lives in
// $activateAbilityAbilities["he6kd7hocc:0"] with a prereq requiring 2+ preparation counters on
// Tristan, a live combat (CombatTarget/CombatAttackerPlayer) and a phantasia ally. Player 1's
// champion is patched to Shadowdancer with exactly 2 preparation counters and an Ominous Shadow is
// seeded; player 2 declares a real attack with a Dungeon Guide on Tristan, player 1 is offered
// "myField-0@Activate-0@Redirect" in the opportunity window, and the handler
// (customDQHandlers["he6kd7hocc:0:ActivateAbility-1"]) removes the two counters and calls
// MarkCombatTarget() on the Shadow, so the attack finishes against the Shadow and Tristan takes no
// damage (Dungeon Guide has 1 POWER, so an unredirected attack would leave Damage 1).
// ENGINE BUG (FIXED; this fixture used to be named
// tristan-shadowdancer-redirect-attack-to-phantasia and its REGRESSION GUARD assertion pinned
// Tristan's Status at 1): DoActivatedAbility() (Custom/GameLogic.php) sets Status = 1 as the
// implicit [REST] cost of every static ability of an ALLY/CHAMPION/PHANTASIA unless the card id is
// in the hard-coded $skipAutoRest list, and he6kd7hocc was not in that list, so Redirect also
// rested Tristan (trace: cardID=he6kd7hocc idx=0 static=1 skip=0 before=2). he6kd7hocc is now in
// the list; Tristan stays awake (Status 2) after the redirect.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer with exactly two preparation counters (the ability cost)
//   - Ominous Shadow (phantasia ally) -> p1 field-1
//   - Dungeon Guide (power 1 attacker) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion, Tristan
//   - player 1 answers the opportunity window by activating Redirect
//   - redirect the attack to the Ominous Shadow
//   - decline retaliation
gaScenario('tristan-shadowdancer-redirect-does-not-rest-champion')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 2]])
    ->seed('myField', 'Ominous Shadow')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(1, 'myField-0@Activate-0@Redirect')
    ->target('myField-1')->pass(1)
    ->tested('Tristan, Shadowdancer')
    ->expectProperty('p1Field-0', 'Status', '2', 'Before the activation Tristan is awake (Status 2) and Redirect is offered', step: 3)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'Before the activation Tristan has exactly the two preparation counters the cost needs', step: 3)
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The two preparation counters were removed as the cost of Redirect', step: 5)
    ->expectProperty('p1Field-0', 'Status', '2', 'Redirect has no [REST] in its cost: Tristan is still awake after the activation', step: 5)
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED"]', 'Tristan is no longer the marked combat target (no COMBAT_TARGET_MARKER)', step: 5)
    ->expectProperty('p1Field-1', 'TurnEffects', '["COMBAT_TARGET_MARKER"]', 'The Ominous Shadow is now the marked combat target of the attack', step: 5)
    ->expectProperty('p1Field-0', 'Status', '2', 'Tristan is still awake after the attack resolved')
    ->expectDamage('p1Field-0', 0, 'Tristan takes no damage: the 1-POWER attack was redirected (it would deal 1 otherwise)')
    ->expectZoneCount('myField', 2, 1, 'The Ominous Shadow is still on the field after absorbing the attack')
    ->expectDqEmpty()
    ->register();

// --- Tristan, Shadowdancer: Redirect has no [REST] cost, so an already RESTED Tristan can still redirect (and stays rested) ---
// Tristan, Shadowdancer (he6kd7hocc): the printed cost is only "remove two preparation counters".
// Companion of tristan-shadowdancer-redirect-does-not-rest-champion: here Tristan is patched to
// Status 1 (rested) with exactly two preparation counters. Because Redirect has no [REST] in its
// cost, the opportunity window still offers it and the redirect resolves; Tristan is neither woken
// nor re-rested (Status stays 1). The Dungeon Guide (1 POWER) hits the Ominous Shadow instead.
// Notes carried over from the original definition:
//   - RESTED Tristan, Shadowdancer with exactly two preparation counters
//   - Ominous Shadow (phantasia ally) -> p1 field-1
//   - Dungeon Guide (power 1 attacker) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion, Tristan
//   - the rested Tristan is still offered Redirect
//   - redirect the attack to the Ominous Shadow
//   - decline retaliation
gaScenario('tristan-shadowdancer-redirect-usable-while-rested')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 2], 'Status' => 1])
    ->seed('myField', 'Ominous Shadow')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(1, 'myField-0@Activate-0@Redirect')
    ->target('myField-1')->pass(1)
    ->tested('Tristan, Shadowdancer')
    ->expectProperty('p1Field-0', 'Status', '1', 'Tristan is rested when the opportunity window offers Redirect', step: 3)
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The rested Tristan still paid the two preparation counters and redirected', step: 5)
    ->expectProperty('p1Field-1', 'TurnEffects', '["COMBAT_TARGET_MARKER"]', 'The Ominous Shadow is the marked combat target after the rested Tristan redirected', step: 5)
    ->expectProperty('p1Field-0', 'Status', '1', 'Redirect neither woke nor re-rested Tristan: still rested')
    ->expectDamage('p1Field-0', 0, 'Tristan takes no damage: the attack was redirected')
    ->expectDqEmpty()
    ->register();

// --- Tristan, Shadowdancer: Redirect is refused with only one preparation counter (cost not payable) ---
// Tristan, Shadowdancer (he6kd7hocc): "Remove two preparation counters from Tristan: ..." --
// activateAbilityPrereqs["he6kd7hocc:0"] requires GetPrepCounterCount() >= 2. With a single
// counter Redirect is not offered at all: no priority window opens for player 1 after the attack
// target is chosen (with TWO counters, see the sibling fixtures, the window opens with
// 'myField-0@Activate-0@Redirect'), so the attack resolves immediately against Tristan (1 damage),
// the single counter stays, and the Ominous Shadow is untouched.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer with only ONE preparation counter
//   - Ominous Shadow (phantasia ally) -> p1 field-1
//   - Dungeon Guide (power 1 attacker) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion, Tristan
gaScenario('tristan-shadowdancer-redirect-refused-with-one-preparation-counter')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 1]])
    ->seed('myField', 'Ominous Shadow')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->tested('Tristan, Shadowdancer')
    ->expectDamage('p1Field-0', 1, 'Redirect was not offered: the attack resolved against Tristan (1 damage)')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1,"_champDamageThisTurn":1}', 'The single preparation counter is untouched (the other key is the engine\'s champion-damage bookkeeping)')
    ->expectDamage('p1Field-1', 0, 'The Ominous Shadow was not hit')
    ->expectDqEmpty()
    ->register();

// Turbo Charge (cnqsm3n9yv, CLERIC skill, reserve 3): "As an additional cost, sacrifice a Powercell." No Powercell on the
// field. This one is ALREADY refused before the card moves (its generated activate prerequisite is consulted by the hand
// click) -- the fixture pins that the Powercell cards were never stranded by the dead post-announce check.
// Notes carried over from the original definition:
//   - Turbo Charge -> p1 myHand-0
//   - 1: click Turbo Charge with no Powercell to sacrifice: accepted as a no-op, refusal asserted on state
gaScenario('turbo-charge-no-powercell-click-refused')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Turbo Charge')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)
    ->tested('Turbo Charge')
    ->register();

// --- The Looking Glass: printed Omnishroud -- an opposing spell cannot target it ---
// The Looking Glass (fln04uv297, REGALIA,ITEM): "Divine Relic, Omnishroud". Omnishroud means "This can't be
// targeted by activations, materializations, or triggered abilities" (comprehensive rules, Keywords >
// Omnishroud 1); a spell is a card activation, so an Omnishroud object can't be targeted by spells --
// Spellshroud and Omnishroud are redundant with each other (Omnishroud 3a). The engine already applied that to
// the OMNISHROUD turn effect and Argus, All-Seeing Giant, but printed Omnishroud on these cards was never
// recognised: HasSpellshroud() only knew a hard-coded card-ID list.
// Player 2 controls the holder plus a Mirrordepth's Blade; player 1 plays Fracturize ("Target item or weapon
// becomes a Cleric Fractal phantasia ...", a Spell): the target prompt offers ONLY the Mirrordepth's Blade,
// answering with the holder is refused (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('the-looking-glass-printed-omnishroud-spell-cannot-target-it', ['The Looking Glass'], 'Fracturize', word: 'Omnishroud')
    ->register();

// --- Tome of Abyssal Heaven: "Other objects you control have spellshroud" -- shrouds the other weapon, not
// itself ---
// Tome of Abyssal Heaven (igmC01eEZn, REGALIA/ITEM): "Other objects you control have spellshroud." -- an
// unconditional static grant; it was not implemented anywhere (no HasSpellshroud() clause). Fixed:
// HasSpellshroud() returns true for any field object whose controller controls an ability-bearing Tome (other
// than the Tome itself).
// Player 2 controls the Tome and a Mirrordepth's Blade; player 1 plays Fracturize: the target prompt offers
// ONLY the Tome (theirField-1) -- the Blade (theirField-2) is shrouded and answering with it is refused -- and
// the Tome, which only grants spellshroud to OTHER objects, is a legal target and becomes a phantasia.
// Notes carried over from the original definition:
//   - Tome of Abyssal Heaven -> p2 field-1
//   - Mirrordepth's Blade -> p2 field-2
//   - Fracturize -> p1 myHand-7
//   - player 1 plays Fracturize
//   - the Tome itself is a legal target
gaScenario('tome-of-abyssal-heaven-other-weapon-has-spellshroud-tome-itself-targetable')
    ->deck($mordredDeck)
    ->seed('myField', 'Tome of Abyssal Heaven', player: 2)
    ->seed('myField', 'Mirrordepth\'s Blade', player: 2)
    ->seed('myHand', 'Fracturize')
    ->castSpell('Fracturize', pay: 2)
    ->rejects(1, 'theirField-2', 'Mirrordepth\'s Blade is shrouded by the Tome (other objects you control have spellshroud)')
    ->target('theirField-1')
    ->tested('Tome of Abyssal Heaven')
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2}', 'The refused answer left Mirrordepth\'s Blade untouched: the Tome gives it spellshroud', step: 4)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answer did not touch the Tome either (prompt still pending)', step: 4)
    ->expectProperty('p2Field-1', 'Counters', '{"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'The Tome itself is a legal target (it grants spellshroud to OTHER objects only) and became a Cleric Fractal phantasia')
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2}', 'Mirrordepth\'s Blade is still unaffected')
    ->expectProperty('p1Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Tome of Abyssal Heaven: other allies you control have spellshroud (a spell finds no target) ---
// Tome of Abyssal Heaven (igmC01eEZn): "Other objects you control have spellshroud." Player 2 controls the
// Tome and a Dungeon Guide (an ordinary ally); player 1 plays Liturgy of Corruption ("Put a buff counter on
// target ally ..."). The Dungeon Guide is shrouded by the Tome, so the spell finds no legal target, resolves
// without a prompt, and the Guide gets no buff counter (without the Tome the same spell would prompt for a
// target).
// Notes carried over from the original definition:
//   - Tome of Abyssal Heaven -> p2 field-1
//   - Dungeon Guide -> p2 field-2
//   - Liturgy of Corruption -> p1 myHand-7
//   - player 1 plays Liturgy of Corruption
gaScenario('tome-of-abyssal-heaven-other-ally-has-spellshroud-spell-finds-no-target')
    ->deck($mordredDeck)
    ->seed('myField', 'Tome of Abyssal Heaven', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Liturgy of Corruption')
    ->castSpell('Liturgy of Corruption', pay: 2)
    ->tested('Tome of Abyssal Heaven')
    ->expectProperty('p2Field-2', 'Counters', '[]', 'The Dungeon Guide is shrouded by the Tome: Liturgy of Corruption found no legal ally and put no buff counter on it')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The Tome itself is unaffected')
    ->expectProperty('p1Graveyard-0', 'CardID', 'BZMKfFZ22T', 'Liturgy of Corruption resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Tome of Abyssal Heaven: a Tome that lost all abilities grants no spellshroud ---
// Tome of Abyssal Heaven (igmC01eEZn): "Other objects you control have spellshroud." Boundary: the static
// grant is an ability, so a Tome that has lost all abilities (NO_ABILITIES override, seeded as setup state --
// only indirect proof of the real loses-all-abilities path) grants nothing: player 1's Fracturize offers the
// Mirrordepth's Blade and choosing it succeeds.
// Notes carried over from the original definition:
//   - Tome of Abyssal Heaven (lost all abilities) -> p2 field-1
//   - Mirrordepth's Blade -> p2 field-2
//   - Fracturize -> p1 myHand-7
//   - player 1 plays Fracturize
//   - the Blade is targetable: the ability-less Tome grants nothing
gaScenario('tome-of-abyssal-heaven-lost-abilities-grants-nothing')
    ->deck($mordredDeck)
    ->seed('myField', 'Tome of Abyssal Heaven', player: 2, properties: ['Counters' => ['_overrides' => ['NO_ABILITIES' => true]]])
    ->seed('myField', 'Mirrordepth\'s Blade', player: 2)
    ->seed('myHand', 'Fracturize')
    ->castSpell('Fracturize', pay: 2, target: 'theirField-2')
    ->tested('Tome of Abyssal Heaven')
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2,"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'BOUNDARY: a Tome that lost all abilities grants nothing -- the Blade was offered and became a Cleric Fractal phantasia')
    ->expectProperty('p2Field-1', 'Counters', '{"_overrides":{"NO_ABILITIES":true}}', 'The ability-less Tome itself was not the chosen target')
    ->expectProperty('p1Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// Notes carried over from the original definition:
//   - Spirit of Fire + LUXEM lineage (unlocks the LUXEM card)
gaScenario('thousand-refractions-prepare-1-paid-non-assassin-no-wake-no-return')
    ->deck($gaNonAssassinDeck)
    ->patch('myField-0', ['Subcards' => ['UAF6Nr7GUE'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myHand', 'Thousand Refractions', player: 2)
    ->endTurn()
    ->play('Thousand Refractions', 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

// --- Thousand Refractions (XLbCBxla8K): Prepare 1; [Class Bonus] On Hit: if prepared, wake up your champion and return it to hand ---
gaScenario('thousand-refractions-prepare-1-paid-wakes-champion-returns-to-hand')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myHand', 'Thousand Refractions', player: 2)
    ->endTurn()
    ->play('Thousand Refractions', 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

gaScenario('thousand-refractions-prepare-1-declined-champion-stays-rested')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myHand', 'Thousand Refractions', player: 2)
    ->endTurn()
    ->play('Thousand Refractions', 2)->answer(2, 'NO')->answer(2, 'theirField-0')
    ->register();

// --- Tide Diviner (zrBBvgIvt6): "On Enter: Look at the top 1+LV cards of your deck. Put one of those cards into your hand and the rest into your graveyard." The generated body looked with a
// fixed-index MZMove loop: only the top card was ever looked at (the champion is level 2 here, so three cards must be looked at). ---
// Notes carried over from the original definition:
//   - level-2 champion with a Spirit of Water lineage (water element)
//   - Tide Diviner -> myHand-7
//   - top three looked at (take the 3rd), then a Charge the Soul marker
gaScenario('tide-diviner-enter-level-two-looks-three-takes-deep-card-rest-to-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Erudite Strategist', lineage: ['Spirit of Water'])
    ->seed('myHand', 'Tide Diviner')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Tide Diviner', pay: 4, target: 'myTempZone-2')
    ->register();

// --- Tribute Singer (ne6qXGbr4c): "[Level 1+] On Enter: Reveal the top five cards of your deck. You may activate a non-advanced element Harmony or Melody card from among the revealed cards or your memory. That card costs (3) less to
// activate this way. Put the rest of the revealed cards on the bottom of your deck in any order." Declining the activation PASSes the MZMAYCHOOSE; TributeSingerChoose was queued without dontSkipOnPass, so its "put the rest on the bottom"
// branch never ran and all five revealed cards stayed in the temp zone. Belted Tune (ko5PJRsy25, a NORM Melody) is the only legal pick. ---
// Notes carried over from the original definition:
//   - Tribute Singer -> myHand-7
gaScenario('tribute-singer-reveal-five-decline-activation-all-five-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(level: 1)
    ->seed('myHand', 'Tribute Singer')
    ->deckTop(['Belted Tune', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep', 'Charge the Soul'])
    ->castSpell('Tribute Singer', pay: 3, window: 'me')
    ->answer(1, 'Bottom=ko5PJRsy25,em6eEh9q8y,px60u5n1do,em6eEh9q8y,px60u5n1do')
    ->register();

// --- Topsy Decree (Byx6iokcT4): "Choose one — Up to one target opponent discards a card from their hand or memory ..." The targeted opponent has no choice about discarding: the flow offered the opponent an MZMAYCHOOSE, so they could
// PASS and keep every card (and TopsyDecreeDiscard was queued without dontSkipOnPass, so a PASS also dropped the rest of the chosen modes). Player 2 holds a single card; passing the discard prompt must be refused. ---
// Notes carried over from the original definition:
//   - Topsy Decree -> myHand-7
//   - opponent's only hand card
//   - decline the Imbue, pay 3, choose the discard mode
gaScenario('topsy-decree-opponent-must-discard-cannot-decline')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Topsy Decree')
    ->emptyZone('myHand', player: 2, destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->play('Topsy Decree')->answer(1, 'NO')->pay(3)->answer(1, '1')
    ->rejects(2, 'PASS', 'The targeted opponent cannot decline the discard')->pay(1, 2)
    ->expectZoneCount('myHand', 0, 2, 'The opponent discarded their only card')
    ->expectZoneCount('myGraveyard', 8, 2, 'It went to their graveyard (7 seeded + 1)')
    ->expectDqEmpty()
    ->register();

// Thieving Cut (7t9m4muq2r): "Prepare 1. On Hit: If CARDNAME was prepared, draw a card." The hit trigger read the shared wasPrepared variable, which any later activation overwrites: here the
// opponent answers the attack by activating Fishing Accident (not prepared), and the prepared Thieving Cut then no longer drew its card. Whether an attack card was prepared now comes from the
// PREPARED tag on its own card in the intent.
// Notes carried over from the original definition:
//   - fuel for Thieving Cut's Prepare 1
//   - Thieving Cut -> p2 myHand-7
//   - ASSASSIN champion with a water lineage: can activate Fishing Accident
//   - Fishing Accident -> p1 myHand-7
//   - Dungeon Guide (ALLY) -> p2 myField-1: Fishing Accident's target
gaScenario('thieving-cut-prepared-still-draws-after-opponent-activates-an-unprepared-card')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['Counters' => ['preparation' => 1]], player: 2)
    ->seed('myHand', 'Thieving Cut', player: 2)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Wind', 'Spirit of Water'])
    ->seed('myHand', 'Fishing Accident')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->play('Thieving Cut', 2)->pay(2, 2)->pass(1)->answer(2, 'YES')->answer(2, 'theirField-0')->target('myHand-7')
    ->pay(1)->target('theirField-1')
    ->tested('Thieving Cut', 'Fishing Accident')
    ->expectZoneCount('myHand', 7, 2, 'Player 2 drew a card from the prepared Thieving Cut\'s On Hit: 6 + 1')
    ->expectZoneCount('myDeck', 9, 2, 'One card left the deck')
    ->expectZoneCount('myGraveyard', 1, 1, 'Fishing Accident resolved and went to player 1\'s graveyard')
    ->expectDqEmpty()
    ->covers(['prepare', 'on-hit', 'stale-flag'], 'Prepare 1', 'On Hit: If CARDNAME was prepared, draw a card.')
    ->register();

// Tribute Singer (ne6qXGbr4c): "On Enter: Reveal the top five cards of your deck. You may activate a non-advanced element Harmony or Melody card from among the revealed cards or your memory. That card costs (3) less to activate
// this way." A Melody whose discounted cost cannot be paid (empty hand after paying Tribute Singer) used to be offered anyway and, once chosen, was moved into the hand for free. It is no longer offered.
// Notes carried over from the original definition:
//   - Level 1+; TAMER class for Modulating Cadence
//   - three payers -> myHand-0..2 (the hand is empty once Tribute Singer is paid for)
//   - Tribute Singer -> myHand-3
//   - Modulating Cadence (Melody, reserve cost 4: 1 after the discount) among the top five
gaScenario('tribute-singer-does-not-offer-a-melody-it-cannot-pay-for')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->champion(level: 1, classes: 'TAMER')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Tribute Singer')
    ->deckTop(['Modulating Cadence', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep', 'Charge the Soul'])
    ->playSlot(3)->pay(3)->answer(1, 'Bottom=p5p0azskw4,em6eEh9q8y,px60u5n1do,em6eEh9q8y,px60u5n1do')
    ->tested('Tribute Singer')
    ->expectZoneCount('myHand', 0, 1, 'No card was taken into the hand for free')
    ->expectZoneCount('myTempZone', 0, 1, 'The five revealed cards left the temp zone')
    ->expectZoneCount('myDeck', 19, 1, 'Five cards back on the bottom of the deck')
    ->expectDqEmpty()
    ->covers(['activate-from-zone', 'cost-validation', 'tribute-singer'], '[Level 1+] On Enter: Reveal the top five cards of your deck. You may activate a non-advanced element Harmony or Melody card from among the revealed cards or your memory. That card costs (3) less to activate this way. Put the rest of the revealed cards on the bottom of your deck in any order.')
    ->register();

// Tempered Steel (vyRjDql0TR): "Put a durability counter on target weapon you control."
// Notes carried over from the original definition:
//   - Clarent, Sword of Peace -> myField-1
//   - Tempered Steel -> myHand-7
gaScenario('tempered-steel-puts-a-durability-counter-on-your-weapon')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myHand', 'Tempered Steel')
    ->castSpell('Tempered Steel', pay: 1, target: 'myField-1')
    ->covers(['counter', 'targeting'], 'Put a durability counter on target weapon you control.')
    ->register();

// Treasure of the Depths (4Kxe6pSt6C): "(3), [REST]: Draw a card into your memory. Activate this ability only if there are three or more refinement counters on CARDNAME."
// Notes carried over from the original definition:
//   - Treasure of the Depths -> myField-1
gaScenario('treasure-of-the-depths-with-three-refinement-counters-pays-three-and-draws-into-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Treasure of the Depths')
    ->patch('myField-1', ['Counters' => ['refinement' => 3]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)
    ->tested('Treasure of the Depths')
    ->register();

// Notes carried over from the original definition:
//   - Treasure of the Depths -> myField-1
gaScenario('treasure-of-the-depths-with-two-refinement-counters-cannot-activate')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Treasure of the Depths')
    ->patch('myField-1', ['Counters' => ['refinement' => 2]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Treasure of the Depths')
    ->register();

// Deluge 3: at the beginning of your end phase, with three or more water element cards in your graveyard and fewer than three refinement counters, put a refinement counter on it.
// Notes carried over from the original definition:
//   - Treasure of the Depths -> myField-1
gaScenario('treasure-of-the-depths-end-phase-with-three-water-cards-adds-a-refinement-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Treasure of the Depths')
    ->seed('myGraveyard', 'Baby Blue Slime')
    ->seed('myGraveyard', 'Baby Blue Slime')
    ->seed('myGraveyard', 'Baby Blue Slime')
    ->endTurn()
    ->tested('Treasure of the Depths')
    ->register();

// Notes carried over from the original definition:
//   - Treasure of the Depths -> myField-1
gaScenario('treasure-of-the-depths-end-phase-with-two-water-cards-adds-nothing')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Treasure of the Depths')
    ->seed('myGraveyard', 'Baby Blue Slime')
    ->seed('myGraveyard', 'Baby Blue Slime')
    ->endTurn()
    ->tested('Treasure of the Depths')
    ->register();

// Transcendental Rite (tAiiMGZJXp): "Banish Transcendental Rite: Your champion becomes an Ascendant in addition to its other types. All basic elements are enabled for you until end of turn."
// Spirit of Fire only enables FIRE: the WATER Baby Blue Slime in hand is unplayable until the Rite is banished.
// Notes carried over from the original definition:
//   - Transcendental Rite -> myField-1
//   - Baby Blue Slime (WATER) -> myHand-7
gaScenario('transcendental-rite-banish-makes-champion-ascendant-and-enables-basic-elements')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Transcendental Rite')
    ->seed('myHand', 'Baby Blue Slime')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->castSpell('Baby Blue Slime', pay: 2)
    ->tested('Transcendental Rite')
    ->register();

// Notes carried over from the original definition:
//   - Baby Blue Slime (WATER) -> myHand-7
gaScenario('transcendental-rite-without-it-a-water-card-is-not-playable')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Baby Blue Slime')
    ->play('Baby Blue Slime')
    ->tested('Transcendental Rite')
    ->expectZoneCount('myHand', 8, 1, 'The WATER card cannot be played with only a FIRE champion: it stays in hand')
    ->expectZoneCount('myField', 1, 1, 'Only the champion is on the field')
    ->expectDqEmpty()
    ->register();

// Torch Marshal (izgiu216l2): "[Class Bonus] Torch Marshal gets +1 POWER. As an additional cost to declare an attack with this ally, pay (2)."
// Notes carried over from the original definition:
//   - Torch Marshal (power 3) -> myField-1
gaScenario('torch-marshal-class-bonus-gets-plus-one-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myField', 'Torch Marshal')
    ->target('myField-0')
    ->tested('Torch Marshal')
    ->register();

// Notes carried over from the original definition:
//   - Torch Marshal -> myField-1
gaScenario('torch-marshal-without-the-class-bonus-has-base-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Torch Marshal')
    ->target('myField-0')
    ->tested('Torch Marshal')
    ->register();

// Notes carried over from the original definition:
//   - Torch Marshal -> theirField-1 (attacks on player 2's turn)
gaScenario('torch-marshal-attack-costs-an-additional-two')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Torch Marshal', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->pay(2, 2)->answer(2, 'theirField-0')
    ->tested('Torch Marshal')
    ->register();

// Teardrop Diadem (K15jWbHAMY): "Banish Teardrop Diadem: Draw three cards into your memory."
// Notes carried over from the original definition:
//   - Teardrop Diadem -> myField-1
gaScenario('teardrop-diadem-banish-draws-three-into-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Teardrop Diadem')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Teardrop Diadem')
    ->expectZoneCount('myMemory', 3, 1, 'Three cards drawn into memory')
    ->expectZoneCount('myBanish', 1, 1, 'Teardrop Diadem banished itself as the cost')
    ->expectZoneCount('myField', 1, 1, 'The Diadem left the field')
    ->expectZoneCount('myDeck', 10, 1, 'Three cards left the deck')
    ->expectZoneCount('myHand', 7, 1, 'Hand unchanged')
    ->expectDqEmpty()
    ->register();

gaAbilitySandbox('the-duchess-s-thornes-ability-1-pays-its-printed-cost', 'The Duchess\'s Thornes', classes: 'MAGE', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('The Duchess\'s Thornes')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 1, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('throne-sentinel-ability-1-pays-its-printed-cost', 'Throne Sentinel', classes: 'GUARDIAN', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->pass(1)
    ->tested('Throne Sentinel')
    ->register();

// --- Thermal Break (QJHpGP4kpe): "Destroy target item or weapon with memory cost 0 or reserve cost 3 or less." The generated target filter called CardMemoryCost($cardID) and the nonexistent
// CardReserveCost(): casting it was a fatal as soon as any item was on the field. Only Forest Cake (reserve cost 2) is legal; Scepter of Lumina (memory cost 1, no reserve cost) is not. ---
gaScenario('thermal-break-refuses-memory-only-item-destroys-low-reserve-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Thermal Break')
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->castSpell('Thermal Break', pay: 3)
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->target('theirField-2')
    ->register();

// Tabula of Salvage (9cy4wipw4k): "Banish: Choose up to five cards from your graveyard and put them on the bottom of your deck in any order." Takes the last graveyard card first.
gaScenario('tabula-of-salvage-puts-chosen-graveyard-cards-on-the-bottom-of-the-deck')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Tabula of Salvage')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Fluffy Shopkeep')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myGraveyard-2')->target('myGraveyard-1')
    ->target('myGraveyard-0')->pass(1)
    ->tested('Tabula of Salvage')
    ->expectZoneCount('myDeck', 16, 1, 'The three chosen graveyard cards went to the deck (13 + 3)')
    ->expectZoneCount('myGraveyard', 0, 1, 'The graveyard is empty')
    ->expectZoneCount('myBanish', 1, 1, 'Tabula of Salvage banished itself as the cost')
    ->covers(['activated-ability', 'zone-movement', 'banish-self'], 'Banish Tabula of Salvage: Choose up to five cards from your graveyard and put them on the bottom of your deck in any order.')
    ->register();
