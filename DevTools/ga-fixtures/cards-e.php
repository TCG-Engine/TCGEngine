<?php
/**
 * Fixtures for cards starting with 'E', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Recover: Escharotomy prevents target player from recovering ---
// Play Escharotomy (myHand-1 with this deck/seed — verified live, see DevTools notes below)
// and choose "B: Opponent" on its modal target, which sets a CANT_RECOVER global effect on
// the opponent (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php,
// CIU4gT14EE:0:CardActivated-1).
// Notes carried over from the original definition:
//   - Free play: play Escharotomy (mode 10002 FSM)
//   - Pay reserve cost: this is a "choose a card from myHand" MZCHOOSE, not a myField pick.
//   - Pass fast action opportunities
//   - Modal target choice: "A: Yourself" / "B: Opponent" — pick B (opponent) so the
//   - CANT_RECOVER global effect lands on the opponent, matching the fixture's premise.
gaScenario('escharotomy-prevents-recover')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Escharotomy
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
    ->playSlot(1)->pay(1)->passBoth()->answer(1, 'B')
    ->tested('Escharotomy')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Graveyard-0', 'CardID', 'CIU4gT14EE')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2GlobalEffects-0', 'CardID', 'CANT_RECOVER')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 1, 1)
    ->register();

// --- Elysian Aura: Elysian Aspirant enters, passive should be active ---
// Elysian Aspirant's element is EXIA (an advanced element, GrandArchiveSim/Custom/GameLogic.php
// GetAdvancedElementNames()), and no level 0 starting champion has an advanced element (only
// NORM/FIRE/WATER/WIND "Spirit of X" cards exist at level 0) — so it can never legally
// materialize from hand at game start regardless of the pregame fix. Seed it directly onto
// the field instead (the same BridgeAddToZone test-setup primitive used for the fulgurite
// fixtures) so the fixture actually tests what the card does once in play: its passive
// "Elysian Aura" (GrandArchiveSim/Custom/GameLogic.php, HasElysianAura/
// PlayerControlsElysianAura — grants +1 damage from Aenean Spell sources) is a pure presence
// check with no per-turn setup, so having it on the field IS the effect being active.
// Notes carried over from the original definition:
//   - Elysian Aspirant - Elysian Aura source
//   - Pass fast action opportunities so the fixture demonstrates a normal turn continuing
//   - with the passive active (no separate activation is needed for a static aura).
gaScenario('elysian-aspirant-aura-passive')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Elysian Aspirant
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
    ->seed('myField', 'Elysian Aspirant')
    ->passBoth()
    ->tested('Elysian Aspirant')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'HHtlkEeyQR')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->register();

// --- Entrancing Filigree: On Enter banish target non-champion opponent object ---
// Entrancing Filigree is a TERA (advanced element) REGALIA,ITEM card, so the starting
// champion's Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock
// element access. Its memory cost (2) is NOT exercised here: materializing via a direct mode
// 10002 FSM click on the hand card resolves straight through DoMaterialize() without ever
// routing through the MATERIALIZE decision/QueueMaterializePayment cost flow (verified live —
// myMemory contents are unchanged after materializing), so this fixture covers only the
// targeting/zone-movement/On-Enter half, not the memory-cost payment mechanic. Dungeon Guide
// is seeded onto the opponent's field as the On Enter banish target. The On Leave "return it
// rested" trigger requires removing this card from the field afterward and is out of scope.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Dungeon Guide (ALLY) - On Enter banish target
gaScenario('entrancing-filigree-enter-banish')
    ->standardDeck('Entrancing Filigree')
    ->readyFor('Entrancing Filigree')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Entrancing Filigree')
    ->playSlot(2)->target('theirField-1')
    ->tested('Entrancing Filigree')
    ->register();

// --- Elucidate Plans: put two preparation counters on your champion ---
// Elucidate Plans is a LUXEM (advanced element) ACTION card, so the starting champion's
// Subcards are patched with a real LUXEM champion (Zander, Blinding Steel) to unlock element
// access. Only the base always-available effect is covered; the [Class Bonus][Element Bonus]
// memory-reveal trigger is out of scope (tied to the separate memory-reveal subsystem,
// already documented as out of scope in luxem-sight-draw).
// Notes carried over from the original definition:
//   - LUXEM lineage/element unlock
gaScenario('elucidate-plans-prep-counters')
    ->standardDeck('Elucidate Plans')
    ->readyFor('Elucidate Plans')
    ->seed('myHand', 'Elucidate Plans')
    ->castSpell('Elucidate Plans', pay: 2, window: 'both')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'Elucidate Plans puts 2 preparation counters on the champion')
    ->expectDqEmpty()
    ->register();

// --- Exploit Vulnerability: draw a card (Prepare 1 optional cost not exercised) ---
// Exploit Vulnerability's element is NORM, so no lineage patch is needed. The always-available
// "Draw a card" is covered regardless of the optional Prepare 1 cost; the Assassin On Ally Hit
// buff (gated behind actually paying that optional cost) is out of scope.
gaScenario('exploit-vulnerability-draw')
    ->standardDeck('Exploit Vulnerability')
    ->seed('myHand', 'Exploit Vulnerability')
    ->castSpell('Exploit Vulnerability', pay: 2, window: 'both')
    ->expectZoneCount('myDeck', 8, 1, 'Activating draws a card, reducing the deck by one')
    ->expectDqEmpty()
    ->register();

// --- Essence Crucible: On Enter draws a card ---
// Essence Crucible's element is NORM, so no lineage patch is needed, and its memory cost (1)
// is NOT exercised for the same FSM-click-bypass reason as entrancing-filigree-enter-banish.
// Only the On Enter draw is covered; the [Arisanna Bonus] refinement-counter trigger and the
// Spell-damage-boost static ability are out of scope.
gaScenario('essence-crucible-enter-draw')
    ->standardDeck('Essence Crucible')
    ->seed('myHand', 'Essence Crucible')
    ->playSlot(3)
    ->tested('Essence Crucible')
    ->register();

// --- Evasive Maneuvers: prevent the next 2 damage to target unit ---
// Evasive Maneuvers' element is NORM, so no lineage patch is needed. Only the base prevention
// is covered; the target (our own champion) isn't a Ranger, so the "becomes distant" branch
// isn't reached.
gaScenario('evasive-maneuvers-prevent-2')
    ->standardDeck('Evasive Maneuvers')
    ->seed('myHand', 'Evasive Maneuvers')
    ->castSpell('Evasive Maneuvers', pay: 1, window: 'both', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","EVASIVE_MANEUVERS_2"]', 'The targeted champion is protected by an EVASIVE_MANEUVERS_2 shield')
    ->expectDqEmpty()
    ->register();

// --- Equinox Hour: banish self to draw a card ---
// Equinox Hour's element is NORM, so no lineage patch is needed, and its memory cost (0)
// needs no floating-payment setup. Only the "banish self, draw a card" half is covered; the
// opponent's optional follow-up (may materialize a card from their material deck) is declined
// via a trailing NO from player 2.
gaScenario('equinox-hour-banish-draw')
    ->standardDeck('Equinox Hour')
    ->seed('myHand', 'Equinox Hour')
    ->playSlot(6)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(2, 'NO')->pass(1)
    ->tested('Equinox Hour')
    ->register();

// --- Exia Sight: draw a card ---
// Exia Sight is an EXIA (advanced element) ACTION card, so the starting champion's Subcards
// are patched with a real EXIA champion (Dante, Hemomancer, reused from
// mend-flesh-damage25-recover). Its reserve cost is 0, so no reserve payment decisions are
// needed. Only the base "Draw a card" is covered; the [Damage 20+] discount-for-next-card
// clause isn't reached (champion Damage is 0).
// Notes carried over from the original definition:
//   - EXIA lineage/element unlock
gaScenario('exia-sight-draw')
    ->standardDeck('Exia Sight')
    ->readyFor('Exia Sight')
    ->seed('myHand', 'Exia Sight')
    ->play('Exia Sight')
    ->register();

// --- Empowering Tincture: Brew (Manaroot + Herb), On Enter draws into memory since brewed ---
// Empowering Tincture's element is NORM, so no lineage patch is needed. Brewing is an
// alternate cost (sacrifice herbs instead of paying reserve): its recipe requires exactly one
// Manaroot plus one other Herb, so both tokens are seeded onto the field. Only the On Enter
// "if brewed, draw into memory" half is covered; the unconditional Sacrifice: +2 level effect
// is out of scope.
// Notes carried over from the original definition:
//   - Manaroot (HERB token) - required brew ingredient
//   - Blightroot (HERB token) - second brew ingredient
gaScenario('empowering-tincture-brew-enter-draw')
    ->standardDeck('Empowering Tincture')
    ->seed('myField', 'Manaroot')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Empowering Tincture')
    ->play('Empowering Tincture')->answer(1, 'YES')->target('myField-1')->target('myField-2')->passBoth()
    ->register();

// --- Everlonging Thorns: put 2 debuff counters on target ally ---
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Dungeon Guide (ALLY) - target
gaScenario('everlonging-thorns-debuff')
    ->standardDeck('Everlonging Thorns')
    ->champion(lineage: ['Silvie, Slime Sovereign'])
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Everlonging Thorns')
    ->castSpell('Everlonging Thorns', pay: 1, window: 'both', target: 'theirField-1')
    ->register();

// --- Emberslash: On Attack, may discard a card to draw a card ---
// Notes carried over from the original definition:
//   - Emberslash's on-attack effect is gated behind [Class Bonus] (IsClassBonusActive), which
//   - reads EffectiveCardClasses() -- CardClasses($obj->CardID) unless a Counters['_overrides']
//   - ['classes'] override is present (GrandArchiveSim/Custom/GameLogic.php:21116-21121). The
//   - fresh level-0 starting champion (Spirit of Fire) is class SPIRIT, not WARRIOR, so patch
//   - the override directly (same mechanism a real in-game class-change effect would use)
//   - rather than scripting a full champion level-up.
gaScenario('emberslash-discard-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(classes: 'WARRIOR')
    ->seed('myHand', 'Emberslash')
    ->endTurn()
    ->endTurn(2)->answer(1, '-')
    ->play('Emberslash')->pay(1)->target('theirField-0')->answer(1, 'YES')->pay(1)
    ->expectDamage('theirField-0', 2, 'Emberslash\'s attack lands for its printed 2 power')
    ->expectZoneCount('myGraveyard', 2, 1, 'Emberslash and the discarded card both land in the graveyard')
    ->expectZoneCount('myDeck', 3, 1, 'Discarding a card drew a replacement, thinning the deck by one')
    ->expectZoneCount('myHand', 7, 1, 'Hand size nets to unchanged -- one card discarded, one drawn')
    ->expectDqEmpty()
    ->register();

// --- Eminent Commander: [Class Bonus] costs 3 less to activate as long as your champion has
// dealt 3+ combat damage this turn ---
// CountChampionCombatDamageDealtThisTurn (GameLogic.php:23297) reads the champion object's own
// Counters['_champCombatDamageDealtThisTurn'], directly seedable via patchMzId (no real combat
// needed). [Class Bonus] WARRIOR is satisfied by a physically-seeded WARRIOR champion (Jin,
// Fate Defiant), independent of the main champion. WIND element access via the established
// Subcards lineage patch (Spirit of Wind). Base reserve cost 5, -3 discount = 2: the FSM click
// plus 2 "myHand-0" reserve payments (each pick removes the new top-of-hand card, same as
// summon-sentinels-drone-tokens' 4x myHand-0 pattern) pays the full discounted cost.
// Notes carried over from the original definition:
//   - Jin, Fate Defiant (WARRIOR), for Class Bonus
//   - Eminent Commander
//   - After the discounted 2-reserve cost is fully paid, a standard post-materialize opportunity
//   - window offers remaining hand fast-actions; decline it to reach a clean end state.
gaScenario('eminent-commander-class-bonus-combat-damage-discount')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Subcards' => ['pNiyaGlIe7'], 'Counters' => ['_champCombatDamageDealtThisTurn' => 3]])
    ->seed('myField', 'Jin, Fate Defiant')
    ->seed('myHand', 'Eminent Commander')
    ->castSpell('Eminent Commander', pay: 2, window: 'me')
    ->register();

// --- Executioner's Spear: [Jin Bonus] On Kill, put a durability counter on itself ---
// Executioner's Spear's On Kill (GeneratedMacroCode.php ~26540-26547, onKillAbilities) only
// fires when IsJinBonus($player) (GameLogic.php:25095-25100, champion name starts with "Jin")
// is true, so the starting champion's CardID is patched directly to Jin, Fate Defiant. On Kill
// dispatch (CombatLogic.php:2541-2578, OnKillTrigger) only fires for a real combat-damage kill,
// so this needs a full real attack sequence (same P1 pass / P2 pass / P1 decline-MAT-PASS
// shape as jin-fate-defiant-polearm-attack-ally-buff to clear the turn-1 attack lock and reach
// turn 2 MAIN). A setup step targeting the OPPONENT's own field must use
// {'player'=>2,'zone'=>'myField'}, NOT 'theirField' -- 'zone' names in a setup step are
// relative to the acting player, so 'theirField' with player=2 seeds player 1's field instead
// (hit and fixed live: Baby Gray Slime first landed on the attacker's own field). After
// choosing the attack target, a "Retaliate?" MZMAYCHOOSE decision is queued for the DEFENDER
// (player 2) that must be explicitly declined ('-') before CombatApplyAttackerDamage actually
// lands the damage -- omitting it leaves the kill (and the durability counter) from ever
// happening (confirmed via a throwaway debug harness, now deleted). Jin, Fate Defiant has no
// printed base POWER (0), and Executioner's Spear has no Class Bonus power boost (unlike Steel
// Halberd), so total attack power is exactly the weapon's printed 1 POWER -- Baby Gray Slime
// (0hsncz1fz2, 1 life) is the kill target. Executioner's Spear enters the field with 2
// durability counters; combat damage removes 1 (per its own reminder text), then On Kill adds 1
// back, netting durability=2.
// Notes carried over from the original definition:
//   - Jin, Fate Defiant (WARRIOR) - Jin Bonus precondition
//   - Executioner's Spear
//   - Baby Gray Slime (1 life) - kill target, on player 2's OWN field
//   - choose Executioner's Spear as the weapon
//   - target Baby Gray Slime
//   - decline Retaliate so CombatApplyAttackerDamage actually lands
gaScenario('executioners-spear-jin-bonus-on-kill-durability')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Jin, Fate Defiant
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
    ->champion(cardName: 'Jin, Fate Defiant')
    ->seed('myField', 'Executioner\'s Spear')
    ->seed('myField', 'Baby Gray Slime', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-1')->answer(2, '-')
    ->tested('Executioner\'s Spear')
    ->expectProperty('myField-1', 'CardID', 'zv6yp6q7zw', 'probe')
    ->expectProperty('myField-1', 'Counters', '{"durability":2}', 'probe-durability-2')
    ->expectZoneCount('myField', 1, 2, 'Baby Gray Slime dies to the lethal combat damage, leaving only the champion')
    ->expectDqEmpty()
    ->register();

// --- Essence of Blizzards: Brew (One Adjuvant, One Catalyst); Sacrifice: deal 1 damage ---
// Essence of Blizzards' element is WATER, so the starting champion's Subcards are patched with
// a real WATER champion (Nico, Rapture's Embrace) for element access. Brewing sacrifices one
// ADJUVANT-subtype and one CATALYST-subtype object instead of paying reserve; Manaroot
// (ADJUVANT) and Blightroot (CATALYST) are seeded to pay it. Unlike the HERB-count brews, its
// own self-move-to-graveyard is baked directly into the ability macro (CardDQHandlers-style
// handler, GeneratedMacroCode.php) rather than the generic ActivatedAbilityCost switch, so no
// engine-gap risk there. Only the base "deal 1 damage" case against an awake target is covered;
// the "if rested, deal 1+LV instead" and "allies enter rested until EOT" clauses are out of
// scope.
// Notes carried over from the original definition:
//   - WATER lineage/element unlock (Nico, Rapture's Embrace)
//   - Manaroot (ADJUVANT) - brew ingredient
//   - Blightroot (CATALYST) - brew ingredient
//   - target opponent's champion
gaScenario('essence-of-blizzards-brew-sacrifice-damage')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myField', 'Manaroot')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Essence of Blizzards')
    ->play('Essence of Blizzards')->answer(1, 'YES')->target('myField-1')->target('myField-2')->passBoth()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-0')
    ->register();

// --- Empowering Harmony: champion +2 level until end of turn ---
// Empowering Harmony is NORM, no lineage patch needed. The +2 level is unconditional (no Class
// Bonus gate); it is applied via AddGlobalEffects("Kc5Bktw0yK") (GeneratedMacroCode.php ~19347-
// 19352), checked in the level-computation switch (GameLogic.php ~12964) -- directly observable
// via card_exists in myGlobalEffects, without needing the indirect Glimpse-LV proof technique
// used for the continuous "+1 level while Animal/Beast ally" static clauses elsewhere this
// session. [Class Bonus] Harmonize -- draw a card (gated on both TAMER class match AND having
// activated a Melody card this turn) is a separate, out-of-scope mechanic.
gaScenario('empowering-harmony-level')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Empowering Harmony')
    ->castSpell('Empowering Harmony', pay: 2)
    ->register();

// --- Endura, Scepter of Ignition: [REST] remove an enlighten counter, deal 1 damage to target unit ---
// Endura is REGALIA -- seeded directly onto myField. Its prereq requires the champion to
// already have an enlighten counter (GeneratedMacroCode.php ~6147-6172), patched directly via
// Counters. Same Scry-the-Skies warm-up + direct Activate:0 click as crystal-of-empowerment-
// banish-level (this ability's own effect isn't "until end of turn", but the same "field item
// isn't clickable as the literal first action of a fresh turn via a plain FSM click" constraint
// applies, so the same warm-up shape is reused for safety). A Dungeon Guide on the opponent's
// field is the damage target.
// Notes carried over from the original definition:
//   - ability cost fuel
//   - Endura, Scepter of Ignition
//   - Dungeon Guide (ALLY) - damage target
//   - Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
//   - pay Scry the Skies' 1 reserve
//   - target Dungeon Guide
gaScenario('endura-scepter-rest-damage')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['enlighten' => 1]])
    ->seed('myField', 'Endura, Scepter of Ignition')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Scry the Skies')
    ->castSpell('Scry the Skies', pay: 1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->tested('Endura, Scepter of Ignition')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '[]')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'F9POfB5Nah')
    ->expectDamage('p2Field-1', 1)
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DAMAGED_SINCE_LAST_TURN"]')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Esteemed Knight: [Class Bonus] Intercept ---
// Esteemed Knight's only ability is [Class Bonus] Intercept (HasKeyword_Intercept,
// GeneratedKeywordCode.php, gated on IsClassBonusActive($player, ["WARRIOR","HUMAN"])) --
// unlike Swift Recruit's unconditional Intercept (swift-recruit-intercept-redirect), this
// needs a real WARRIOR-or-HUMAN-class champion, so the starting champion is CardID-patched
// directly to Lorraine, Blademaster (WARRIOR). Same real-attack/redirect sequence as
// swift-recruit-intercept-redirect: P2's default champion (0 printed POWER) needs
// Executioner's Spear seeded on its own field to have a legal attack; P1 ends turn 1 with
// nothing to do, P2 attacks P1's champion directly, and P1 redirects to Esteemed Knight
// (GetAvailableInterceptRedirectTargets only offers a redirect when the attack target is a
// CHAMPION).
// Notes carried over from the original definition:
//   - Esteemed Knight, awake
//   - Executioner's Spear (1 POWER) for a legal P2 attack
gaScenario('esteemed-knight-class-bonus-intercept')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Blademaster')
    ->seed('myField', 'Esteemed Knight')
    ->seed('myField', 'Executioner\'s Spear', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-0')->target('myField-1')
    ->answer(1, '-')
    ->tested('Esteemed Knight')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED"]')
    ->expectDamage('p1Field-1', 1)
    ->expectProperty('p1Field-1', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P2"]')
    ->expectProperty('p2Field-0', 'Status', '1')
    ->expectProperty('p2Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":1}')
    ->expectProperty('p2Field-1', 'Counters', '{"durability":1}')
    ->expectProperty('p2Field-1', 'TurnEffects', '[]')
    ->expectProperty('p2GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('theirHand', 9, 1)
    ->register();

// --- Enervating Decay: destroy target opposing ally, recover champion its HP (jh9s424gjr) ---
// Notes carried over from the original definition:
//   - TERA lineage/element unlock + pre-existing damage for the recover assertion
//   - opponent's Dungeon Guide, target ally
gaScenario('enervating-decay-target-destroy-recover')
    ->deck($fillerDeck)
    ->champion(lineage: ['Kongming, Fel Eidolon'], damage: 5)
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Enervating Decay')
    ->castSpell('Enervating Decay', pay: 4, target: 'theirField-1')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 2, 'Playing Enervating Decay fires its on-play trigger (playCardAbilities["jh9s424gjr:0"]): the champion\'s pre-existing 5 damage drops to 2, i.e. RecoverChampion() healed exactly the destroyed ally\'s HP')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'jh9s424gjr')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-3', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The targeted opposing ally is destroyed into its controller\'s graveyard as part of the same trigger')
    ->expectProperty('p2Graveyard-0', 'Status', '2')
    ->expectDamage('p2Graveyard-0', 0)
    ->expectProperty('p2Graveyard-0', 'Counters', '[]')
    ->expectProperty('p2Graveyard-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 3, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 4, 1)
    ->expectZoneCount('theirField', 1, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Elyan, Lustre Loyalty: [Class Bonus] whenever you recover, +X POWER (X = amount recovered) ---
// Elyan's element is LUXEM; the starting champion's CardID is patched directly to Zander,
// Blinding Steel (LUXEM, ASSASSIN) both for its own element-requirement precondition (Bathe in
// Light, used to trigger a real recover event, is also LUXEM) and Elyan's own [Class Bonus]
// condition. Elyan is seeded directly onto the field. The champion's Damage is pre-set to 10
// so Bathe in Light's Recover 4 is a real, observable recover event that fires the recover
// listener in GameLogic.php (~line 18437), which both tags Elyan with the
// "2jgiM0p4dt_RECOVER_4" TurnEffects entry (+4 POWER, since 4 >= 4 also grants UNBLOCKABLE)
// and -- because the recovered amount is >= 4 -- grants unblockable, both asserted below.
// Elyan's printed POWER is 2 (verified via CardPower()); computed_power_equals of 6 is only
// possible if the +X-per-recover-amount static case actually applied with X=4.
// Notes carried over from the original definition:
//   - Zander, Blinding Steel (LUXEM, ASSASSIN) + damage precondition
//   - Elyan, Lustre Loyalty
gaScenario('elyan-lustre-loyalty-recover-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Blinding Steel', damage: 10)
    ->seed('myField', 'Elyan, Lustre Loyalty')
    ->seed('myHand', 'Bathe in Light')
    ->castSpell('Bathe in Light', pay: 2)
    ->tested('Elyan, Lustre Loyalty')
    ->register();

// --- Epochal Conqueror: as long as it's attacking a domain, it gets +3 POWER ---
// Epochal Conqueror's element is NORM, so no lineage patch is needed. Rule 1.h blocks the
// game's first player from attacking on turn 1, so player 1 ends turn 1 and player 2 attacks
// with Epochal Conqueror on their own turn 1 instead. Baidi, Oathsworn Palace (a plain DOMAIN
// card with only a static ability and no activated ability of its own -- Bedlam Borough's own
// "(2), REST: Cascade" activated ability was tried first but kept re-offering itself as a
// perpetual Opportunity choice that never actually declined, verified live) is seeded onto the
// opponent's field as the attack target; IsSiegeable() recognizes it via its SIEGEABLE
// subtype, the actual condition GameLogic.php's power-modifier switch checks (not the DOMAIN
// card type itself). Since the +3 POWER only applies transiently while CombatAttacker is set
// (cleared again once combat fully resolves within the same action), it is proven indirectly:
// the domain's durability drops by 4 (1 printed POWER + 3), not just 1.
// Notes carried over from the original definition:
//   - Epochal Conqueror
//   - awake, can attack
//   - Baidi, Oathsworn Palace (DOMAIN/SIEGEABLE)
//   - ends turn 1 (first-player attack lock)
//   - declare attack with Epochal Conqueror
//   - target Baidi, Oathsworn Palace
gaScenario('epochal-conqueror-attack-domain-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Epochal Conqueror', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->seed('myField', 'Baidi, Oathsworn Palace', properties: ['Counters' => ['durability' => 5]])
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')
    ->tested('Epochal Conqueror')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Counters', '{"durability":1}', 'Domain loses 4 durability (1 printed POWER + 3 from attacking a domain), not just 1')
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p2Field-1', 'Status', '1')
    ->expectProperty('p2Field-1', 'TurnEffects', '[]')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Extraction Incision: True Sight; [Class Bonus] On Kill, put a preparation counter on your champion ---
// Extraction Incision's element is NORM. The starting champion's CardID is patched directly to
// Zander, Deft Executor (ASSASSIN) for the Class Bonus condition. Rule 1.h blocks the game's
// first player from attacking on turn 1, so player 1 ends turn 1 and player 2 plays Extraction
// Incision on their own turn 1 instead. Wandering Glaivier (1 Life) is the kill target --
// Extraction Incision's printed 3 POWER is lethal on a single fresh hit with no precondition
// needed. The defender's "Retaliate?" MZMAYCHOOSE must be explicitly declined so
// CombatApplyAttackerDamage actually lands and kills it, firing the Class Bonus On Kill
// trigger.
// Notes carried over from the original definition:
//   - Zander, Deft Executor (ASSASSIN)
//   - Wandering Glaivier (1 Life) - kill target
//   - ends turn 1 (first-player attack lock)
//   - target Wandering Glaivier
//   - decline Retaliate so CombatApplyAttackerDamage actually lands the kill
gaScenario('extraction-incision-truesight-onkill-prepare')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Deft Executor', player: 2)
    ->seed('myField', 'Wandering Glaivier')
    ->seed('myHand', 'Extraction Incision', player: 2)
    ->endTurn()
    ->play('Extraction Incision', 2)->pay(2, 2)->answer(2, 'theirField-1')->answer(1, '-')
    ->expectDqEmpty()
    ->expectProperty('p1Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-8', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'p6120p3f5d')
    ->expectProperty('p1Graveyard-0', 'Status', '2')
    ->expectDamage('p1Graveyard-0', 3)
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectProperty('p2Field-0', 'Status', '1')
    ->expectProperty('p2Field-0', 'Counters', '{"preparation":1}', 'Class Bonus On Kill puts a preparation counter on the champion')
    ->expectProperty('p2Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-6', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-7', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Graveyard-0', 'CardID', 'zthwm68lgo')
    ->expectProperty('p2Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-1', 'CardID', 'px60u5n1do')
    ->expectZoneCount('myHand', 9, 1)
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 2, 1)
    ->register();

// --- Eventide Lure: On Enter -- look at top 5, reveal a phantasia into memory,
// put the rest on the bottom in original relative order. ---
// Eventide Lure's On Enter (enterAbilities["eg771cn2q1:0"], GeneratedMacroCode.php:10992)
// delegates to hand-authored EventideLureEnter() (Custom/CardDQHandlers.php:8178): moves the
// top 5 deck cards to myTempZone, offers an MZMAYCHOOSE over just the PHANTASIA candidates
// among them, reveals+moves the chosen one to memory (EventideLureReveal), then
// EventideLurePutRestOnBottom() moves every remaining myTempZone card back to myDeck (append
// = bottom) in their myTempZone iteration order. The top 5 are stacked deterministically via
// 'patchMzId' directly on myDeck-0..4 (already-real deck objects post-shuffle/opening-hand-draw,
// patched before any other draw can disturb them) to exactly ONE known phantasia -- Hydrating
// Fractal (LAfJuHgUbm, WATER PHANTASIA, no On Enter/passive text, so it does nothing extra when
// it later leaves the temp zone) -- at position 2, sandwiched between 4 non-phantasia filler
// cards (em6eEh9q8y/px60u5n1do). This proves both halves of the clause: the phantasia specifically
// ends in memory, and the 4 non-phantasia cards land on the bottom in their original order.
// Notes carried over from the original definition:
//   - top-5 slot 1: filler
//   - top-5 slot 2: filler
//   - top-5 slot 3: the ONE phantasia
//   - top-5 slot 4: filler
//   - top-5 slot 5: filler
//   - reserve 1/2
//   - reserve 2/2
//   - reveal the phantasia (Hydrating Fractal) into memory
gaScenario('eventide-lure-look-five-reveal-phantasia')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->seed('myHand', 'Eventide Lure')
    ->patch('myDeck-0', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-1', ['CardID' => 'px60u5n1do'])
    ->patch('myDeck-2', ['CardID' => 'LAfJuHgUbm'])
    ->patch('myDeck-3', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-4', ['CardID' => 'px60u5n1do'])
    ->castSpell('Eventide Lure', pay: 2, target: 'myTempZone-2')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'eg771cn2q1')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-2', 'CardID', 'LAfJuHgUbm', 'The one phantasia among the stacked top 5 (Hydrating Fractal, LAfJuHgUbm, at original top-5 slot 3) was revealed and moved into memory by EventideLureEnter/EventideLureReveal, not left in the deck')
    ->expectProperty('myDeck-8', 'CardID', 'em6eEh9q8y', 'Bottom-of-deck slot 1 of 4: the first non-phantasia top-5 filler card (original slot 1) landed on the bottom')
    ->expectProperty('myDeck-9', 'CardID', 'px60u5n1do', 'Bottom-of-deck slot 2 of 4: the second non-phantasia top-5 filler card (original slot 2) landed on the bottom, preserving relative order')
    ->expectProperty('myDeck-10', 'CardID', 'em6eEh9q8y', 'Bottom-of-deck slot 3 of 4: the third non-phantasia top-5 filler card (original slot 4, after the removed phantasia) landed on the bottom, preserving relative order')
    ->expectProperty('myDeck-11', 'CardID', 'px60u5n1do', 'Bottom-of-deck slot 4 of 4: the fourth non-phantasia top-5 filler card (original slot 5) landed on the bottom, preserving relative order -- the deck\'s remaining composition (12 cards) is exactly the original 13 minus the one revealed phantasia')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->expectZoneCount('myDeck', 12, 1, 'Deck shrank by exactly 1 (13 -> 12): only the revealed phantasia left the deck permanently; the other 4 looked-at cards were returned to the bottom')
    ->register();

// Expel the Departed: destroy up to one target phantasia; if you control 2+ Fatestone/Fatebound
// objects, draw a card.
// Notes carried over from the original definition:
//   - Opponent's phantasia target.
//   - Two Fatestone items to satisfy the "control 2+ Fatestone/Fatebound objects" draw condition.
//   - Pay the 3-reserve cost (not leveled up, so no [Level 2+] discount).
//   - MZMAYCHOOSE: destroy the opponent's Unstable Fractal.
gaScenario('expel-the-departed-destroy-phantasia-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
4 Expel the Departed
6 Dungeon Guide
DECK
    )
    ->seed('myField', 'Unstable Fractal', player: 2)
    ->seed('myField', 'Craggy Fatestone')
    ->seed('myField', 'Craggy Fatestone')
    ->playSlot(0)->pay(3)->passBoth()->target('theirField-1')
    ->tested('Expel the Departed')
    ->register();

// --- Ethereal Slime: [Class Bonus] On Enter, may banish a Tamer card from material for draw+buff ---
// Ethereal Slime's [Class Bonus] On Enter ability (enterAbilities['n06zlhihka:0'] ->
// customDQHandlers['n06zlhihka:0:Enter-1'], GeneratedCode/GeneratedMacroCode.php) is gated
// behind IsClassBonusActive($player, ['TAMER']); it offers an MZMAYCHOOSE among TAMER-subtype
// cards in the controller's own Material deck, and banishing one draws a card and puts a buff
// counter on Ethereal Slime. The champion's CardID is patched directly to Silvie, With the
// Pack (TAMER) for the Class Bonus; its element is CRUX, not native to the starting champion,
// so the champion's Subcards are ALSO patched with a real CRUX champion (Lorraine, Crux
// Knight) purely to unlock element access -- same "CardID for class, Subcards for element"
// layering used elsewhere (e.g. green-slime-class-bonus-on-leave-transfer-buff). Fated
// Keepsake (vi1uyifw6s, REGALIA/ITEM, TAMER subtype) is seeded directly into myMaterial as the
// banish target (its own ability is not exercised here). Ethereal Slime is played for real
// from hand via FSM so its Enter trigger genuinely fires. The separate [Level 5+] "prevent all
// non-combat damage" static clause is a computed-effect clause with no stored flag to assert
// and is out of scope here.
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER Class Bonus) + Lorraine, Crux Knight (CRUX unlock)
//   - Fated Keepsake (TAMER) -- banish target
//   - banish the seeded Fated Keepsake from material
gaScenario('ethereal-slime-class-bonus-enter-banish-tamer-draw-buff')
    ->deckTemplate('silvie-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack', lineage: ['Lorraine, Crux Knight'])
    ->seed('myMaterial', 'Fated Keepsake')
    ->seed('myHand', 'Ethereal Slime')
    ->castSpell('Ethereal Slime', pay: 2, target: 'myMaterial-4')
    ->register();

// --- Echoic Guard: [Class Bonus] makes it cost exactly 2 (printed 3 - 1); prevents the next 2 damage to the target ally ---
// Echoic Guard (gn1b2sbrq9, GUARDIAN ACTION, printed reserve 3): "[Class Bonus] This card costs 1
// less to activate. Prevent the next 2 damage that would be dealt to target ally this turn. Then you
// may pay (X) where X is that ally's reserve cost. If you do, summon a token copy of that ally."
// Same double-Class-Bonus-discount engine bug as Shifting Mirage (generated
// activationCostModifierAbilities['gn1b2sbrq9:0'] AND the ClassBonusActivateCostReduction() table).
// The champion is Tonoris, Creation's Will (NEOS + GUARDIAN); a Dungeon Guide (reserve 3) is the target.
// Exactly TWO reserve payments pay for the card, the target gets the PREVENT_ALL_2 effect, and the
// optional (X) copy payment is declined (no token copy, no further memory growth).
// Notes carried over from the original definition:
//   - Tonoris, Creation's Will (NEOS/GUARDIAN, Tonoris lineage): enables the NEOS element and the Class Bonus
//   - Dungeon Guide (reserve 3 ally, the target) -> p1 field-1
//   - Echoic Guard -> p1 myHand-7
//   - play Echoic Guard from hand
//   - target: the Dungeon Guide
//   - decline paying (X) for the token copy
gaScenario('echoic-guard-class-bonus-costs-two-prevents-two-decline-copy')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tonoris, Creation\'s Will', lineage: ['Tonoris, Might of Humanity', 'Tonoris, Lone Mercenary', 'Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Echoic Guard')
    ->play('Echoic Guard')->pay(2)->target('myField-1')->answer(1, 'NO')
    ->expectZoneCount('myGraveyard', 0, 1, 'After ONE payment Echoic Guard has NOT resolved: printed 3 minus a single Class Bonus discount is 2, not 1', step: 2)
    ->expectZoneCount('myMemory', 2, 1, 'Exactly TWO reserve cards were paid: printed 3 minus the one [Class Bonus] discount', step: 3)
    ->expectProperty('p1Graveyard-0', 'CardID', 'gn1b2sbrq9', 'Echoic Guard resolved only after the second payment (the target prompt follows)', step: 3)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_2"]', 'The target ally gets the \'prevent the next 2 damage\' effect', step: 4)
    ->expectZoneCount('myField', 2, 1, 'Declining the (X) payment summons no token copy (champion + the Dungeon Guide only)')
    ->expectZoneCount('myMemory', 2, 1, 'Declining the (X) payment leaves memory at exactly 2')
    ->expectDqEmpty()
    ->register();

// --- Echoic Guard: negative case -- WITHOUT the Class Bonus it costs its full printed 3 ---
// Echoic Guard (gn1b2sbrq9, printed reserve 3): the champion is the level-0 Spirit of Wind (class
// SPIRIT, not GUARDIAN) carrying a Tonoris, Creation's Will lineage card, so the NEOS card is
// playable but the Class Bonus is inactive and no discount applies: all THREE reserve cards are paid before the target
// prompt; the Dungeon Guide still gets the PREVENT_ALL_2 effect and the copy payment is declined.
// Notes carried over from the original definition:
//   - Spirit of Wind (class SPIRIT, NOT GUARDIAN) with a Tonoris, Creation's Will lineage card: NEOS is enabled (card playable) but the Class Bonus is inactive
//   - Dungeon Guide (reserve 3 ally, the target) -> p1 field-1
//   - Echoic Guard -> p1 myHand-7
//   - play Echoic Guard from hand
//   - target: the Dungeon Guide
//   - decline paying (X) for the token copy
gaScenario('echoic-guard-no-class-bonus-costs-printed-three')
    ->deck($tristanDeck)
    ->champion(cardName: 'Spirit of Wind', lineage: ['Tonoris, Creation\'s Will'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Echoic Guard')
    ->play('Echoic Guard')->pay(3)->target('myField-1')->answer(1, 'NO')
    ->expectZoneCount('myMemory', 2, 1, 'After TWO payments the card still has not resolved without the Class Bonus (cost stays at the printed 3)', step: 3)
    ->expectZoneCount('myGraveyard', 0, 1, 'Echoic Guard is still unresolved after two payments (no discount without the Class Bonus)', step: 3)
    ->expectZoneCount('myMemory', 3, 1, 'Exactly THREE reserve cards were paid: the full printed cost with the Class Bonus inactive', step: 4)
    ->expectProperty('p1Graveyard-0', 'CardID', 'gn1b2sbrq9', 'Echoic Guard resolved after the third payment', step: 4)
    ->expectZoneCount('myHand', 4, 1, 'Hand 8 - the played card - 3 reserve payments = 4', step: 5)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_2"]', 'The target ally gets the \'prevent the next 2 damage\' effect', step: 5)
    ->expectZoneCount('myField', 2, 1, 'Declining the (X) payment summons no token copy')
    ->expectDqEmpty()
    ->register();

// --- Excalibur, Reflected Edge: printed Spellshroud -- an opposing spell cannot target it ---
// Excalibur, Reflected Edge (u1a1s4ys44, UNIQUE,WEAPON): "True Sight, Spellshroud" -- the Spellshroud is the
// card's own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords >
// Spellshroud 1: "This can't be targeted by spells"). It was never implemented: HasSpellshroud()
// (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID list and
// HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword,
// so function_exists is false); u1a1s4ys44 was in neither, so FilterSpellshroudTargets() kept the card in the
// target list. Fixed: it is now in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities /
// NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Mirrordepth's Blade; player 1 plays Fracturize ("Target item or weapon
// becomes a Cleric Fractal phantasia ...", a Spell): the target prompt offers ONLY the Mirrordepth's Blade,
// answering with the holder is refused (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('excalibur-reflected-edge-printed-spellshroud-spell-cannot-target-it', ['Excalibur, Reflected Edge'], 'Fracturize', holderCounters: ['{"durability":2}'])
    ->register();

// --- Eventide Lure: declining the reveal still puts the looked-at cards on the bottom of the deck (dontSkipOnPass regression) ---
// Eventide Lure (eg771cn2q1): "On Enter: Look at the top five cards of your deck. You may reveal a phantasia card from among them and put it into your memory. Put the rest on the bottom of your deck in any order." Declining the optional reveal must not strand the five looked-at cards: they all go to the bottom of the deck.
// Regression: EventideLureReveal's paired AddDecision (Custom/CardDQHandlers.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS to 'Reveal a phantasia card?' and EventideLurePutRestOnBottom() never ran: the five cards stayed in the temp zone and the deck was left 5 cards short (deck 8 instead of 13).
// Same setup as eventide-lure-look-five-reveal-phantasia (one phantasia among the top five), but the reveal is declined.
// Notes carried over from the original definition:
//   - Eventide Lure -> myHand-7
//   - top-5 slot 1: filler
//   - top-5 slot 2: filler
//   - top-5 slot 3: the ONE phantasia
//   - top-5 slot 4: filler
//   - top-5 slot 5: filler
//   - reserve 1/2
//   - reserve 2/2
//   - decline revealing the phantasia
gaScenario('eventide-lure-decline-bottoms-all-five')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->seed('myHand', 'Eventide Lure')
    ->patch('myDeck-0', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-1', ['CardID' => 'px60u5n1do'])
    ->patch('myDeck-2', ['CardID' => 'LAfJuHgUbm'])
    ->patch('myDeck-3', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-4', ['CardID' => 'px60u5n1do'])
    ->castSpell('Eventide Lure', pay: 2, window: 'me')
    ->expectZoneCount('myDeck', 13, 1, 'All five looked-at cards go back on the bottom of the deck (8 remaining + 5 = 13)')
    ->expectZoneCount('myMemory', 2, 1, 'Declining the reveal puts nothing into memory')
    ->expectDqEmpty()
    ->register();

gaScenario('exploit-vulnerability-prepare-1-paid-draw-and-assassin-on-ally-hit')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myHand', 'Exploit Vulnerability', player: 2)
    ->endTurn()
    ->play('Exploit Vulnerability', 2)->answer(2, 'myHand-7')->pay(2, 2)->pass(1)->answer(2, 'YES')
    ->answer(2, 'myField-0')
    ->register();

gaScenario('exploit-vulnerability-prepare-1-declined-draw-only')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myHand', 'Exploit Vulnerability', player: 2)
    ->endTurn()
    ->play('Exploit Vulnerability', 2)->answer(2, 'myHand-7')->pay(2, 2)->pass(1)->answer(2, 'NO')
    ->register();

// top three, top to bottom: DG, FS, Fairy Whispers (WIND, 3rd); then Charge the Soul (marker)
gaScenario('enhance-hearing-look-three-reveal-deep-wind-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Enhance Hearing')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Enhance Hearing', pay: 3, target: 'myTempZone-2')
    ->register();

gaScenario('enhance-hearing-look-three-decline-all-three-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Enhance Hearing')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Enhance Hearing', pay: 3, window: 'me')
    ->register();

// Erupting Rhapsody (dBAdWMoPEz): "Banish any amount of fire element cards from your graveyard. Your champion gets +1 level until
// end of turn for each card banished this way." Banish one, then decline the second prompt: the level bonus must still be applied
// (EruptingRhapsodyPick was queued without dontSkipOnPass, so the decline skipped EruptingRhapsodyFinalize).
// Notes carried over from the original definition:
//   - Baby Red Slime (fire) -> myGraveyard-0
//   - Baby Red Slime (fire) -> myGraveyard-1
//   - Erupting Rhapsody -> myHand-7
gaScenario('erupting-rhapsody-decline-second-banish-still-applies-level-bonus')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myGraveyard', 'Baby Red Slime')
    ->seed('myGraveyard', 'Baby Red Slime')
    ->seed('myHand', 'Erupting Rhapsody')
    ->play('Erupting Rhapsody')->pay(2)->target('myGraveyard-0')->pass(1)
    ->register();

// Enthralling Visage (ycwz9gv4vm): "The next time damage would be dealt to target unit this turn, prevent 2 of that damage. When damage is prevented this way, banish target card in a graveyard."
// Notes carried over from the original definition:
//   - GUARDIAN class
//   - Baby Red Slime -> myGraveyard-0 (the resolving Visage is not a legal target: it only reaches the graveyard once it has finished resolving)
//   - Enthralling Visage -> myHand-7
gaScenario('enthralling-visage-shields-the-target-unit')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myGraveyard', 'Baby Red Slime')
    ->seed('myHand', 'Enthralling Visage')
    ->play('Enthralling Visage')->pay(2)->target('myField-0')->target('myGraveyard-0')
    ->register();

gaAbilitySandbox('exquisite-dessert-ability-1-pays-its-printed-cost', 'Exquisite Dessert', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Exquisite Dessert')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 0, graveyard: 4, field: 6)
    ->register();

gaAbilitySandbox('exquisite-dessert-ability-2-pays-its-printed-cost', 'Exquisite Dessert', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pay(1)->target('myField-1')->pass(1)
    ->tested('Exquisite Dessert')
    ->expectCostFootprint(hand: 12, memory: 3, banish: 0, graveyard: 4, field: 6)
    ->register();

gaAbilitySandbox('equinox-hour-ability-1-pays-its-printed-cost', 'Equinox Hour', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)->answer(2, 'YES')
    ->tested('Equinox Hour')
    ->expectCostFootprint(hand: 14, memory: 2, banish: 1, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('extinguishing-synchron-ability-1-pays-its-printed-cost', 'Extinguishing Synchron', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Extinguishing Synchron')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 1, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('endura-scepter-of-ignition-ability-1-pays-its-printed-cost', 'Endura, Scepter of Ignition', classes: 'MAGE', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-0')->pass(1)
    ->tested('Endura, Scepter of Ignition')
    ->register();

// Eventide Spear / Varuckan Soulknife / Capacitance X Psycho: the materialize phase offers their "activate from your material deck" clause only when the player takes part in the ordinary materialize; a player
// who declines can still click the card in the material deck during the main phase.
// Notes carried over from the original definition:
//   - Eventide Spear -> p1 myMaterial-0
//   - rested Dungeon Guide
//   - rested Dungeon Guide
gaScenario('eventide-spear-activates-from-the-material-deck-in-the-main-phase-against-two-rested-units')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'WARRIOR')
    ->seed('myMaterial', 'Eventide Spear')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 1], player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-2', ['Status' => 1], player: 2)
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Eventide Spear')
    ->register();

// Notes carried over from the original definition:
//   - an awake Dungeon Guide
gaScenario('eventide-spear-is-not-activated-from-the-material-deck-against-only-one-rested-unit')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'WARRIOR')
    ->seed('myMaterial', 'Eventide Spear')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 1], player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Eventide Spear')
    ->register();
