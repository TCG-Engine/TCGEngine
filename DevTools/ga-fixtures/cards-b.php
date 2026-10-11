<?php
/**
 * Fixtures for cards starting with 'B', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Battlefield Benediction: Class Bonus discount + Empower scaled by opponent's board ---
// Battlefield Benediction's element is NORM, so no lineage patch is needed, but its discount
// requires a CLERIC or MAGE Class Bonus, which (like samaritans-reach's abandoned attempt but
// Class-Bonus checks scan physical field objects) needs a physically-seeded champion, so
// Kongming, Fel Eidolon (a MAGE CHAMPION) is seeded directly onto the field. Three Dungeon
// Guide allies are seeded onto the opponent's field so "an opponent controls three or more
// units" is true, reaching the empower-4 branch instead of the base empower-2.
// Notes carried over from the original definition:
//   - Kongming, Fel Eidolon (MAGE CHAMPION) - Class Bonus source
//   - Opponent ally 1/3
//   - Opponent ally 2/3
//   - Opponent ally 3/3
gaScenario('battlefield-benediction-empower4')
    ->standardDeck('Battlefield Benediction')
    ->seed('myField', 'Kongming, Fel Eidolon')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Battlefield Benediction')
    ->play('Battlefield Benediction')->pay(1)->passBoth()
    ->expectProperty('p1Field-0', 'TurnEffects', '["HcR3O8vDps","EMPOWERED","EMPOWER_PLUS_4","vz4kc558yx-ACTION_ACTIVATED"]', 'With 3+ opposing units, the champion is empowered by 4 instead of the base 2')
    ->expectZoneCount('myMemory', 1, 1, 'The Class Bonus discount reduces the 2-reserve cost to 1, so only 1 reserve payment is made')
    ->expectDqEmpty()
    ->register();

// --- Barrier Servant: remove 2 enlighten counters to prevent the next damage to itself ---
// Barrier Servant's element is NORM, so no lineage patch is needed. The starting champion's
// Counters are patched with 2 enlighten counters directly (normally only reachable via a
// separate enlighten-granting effect) so the ability's cost (remove 2 enlighten counters from
// your champion) can actually be paid. Only the always-available "prevent next damage to
// self" half is covered; Intercept is a combat-redirect passive and is out of scope.
// Notes carried over from the original definition:
//   - Ability cost fuel
gaScenario('barrier-servant-enlighten-prevent')
    ->standardDeck('Barrier Servant')
    ->patch('myField-0', ['Counters' => ['enlighten' => 2]])
    ->seed('myHand', 'Barrier Servant')
    ->play('Barrier Servant')->pay(3)->passBoth()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->passBoth()
    ->expectProperty('p1Field-1', 'Status', '2', 'Barrier Servant is awake before its ability is activated', step: 6)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","BARRIER_PREVENT_DAMAGE"]', 'Barrier Servant is protected by a BARRIER_PREVENT_DAMAGE shield')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The champion\'s 2 enlighten counters are spent as the ability\'s cost')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Status', '2', 'Barrier Servant\'s printed cost has no [REST] (Remove two enlighten counters from your champion): it is still awake after the activation')
    ->register();

// --- Break Apart: destroy target non-regalia item or weapon ---
// Break Apart's element is NORM, so no lineage patch is needed. Forest Cake (a plain ITEM,
// not REGALIA) is seeded onto the opponent's field as the target, avoiding the "costs 2 more
// if it targets a regalia" branch (which isn't exercised here). Destroy resolves the same way
// as meltdown-level2-destroy-item: the target is banished, not moved to its graveyard.
// Notes carried over from the original definition:
//   - Forest Cake (plain ITEM) - destroy target
gaScenario('break-apart-destroy')
    ->standardDeck('Break Apart')
    ->seed('theirField', 'Forest Cake')
    ->seed('myHand', 'Break Apart')
    ->play('Break Apart')->pay(3)->target('theirField-1')
    ->register();

// --- Beastbond Paws: banish self to buff a target Animal/Beast ally ---
// Beastbond Paws' element is NORM, so no lineage patch is needed, and its memory cost (0)
// needs no floating-payment setup. Cheerful Slime (an ALLY, TAMER,ANIMAL,SLIME) is seeded
// onto the field as the required Animal/Beast target.
// Notes carried over from the original definition:
//   - Cheerful Slime (ALLY, ANIMAL) - buff target
gaScenario('beastbond-paws-buff-truesight')
    ->standardDeck('Beastbond Paws')
    ->seed('myField', 'Cheerful Slime')
    ->seed('myHand', 'Beastbond Paws')
    ->playSlot(4)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Beastbond Paws')
    ->register();

// --- Backstep: target unit becomes distant, then draw a card ---
// Notes carried over from the original definition:
//   - Dungeon Guide (ALLY) - target
gaScenario('backstep-distant-draw')
    ->standardDeck('Backstep')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Backstep')
    ->play('Backstep')->pay(2)->pass(2)->target('theirField-1')
    ->register();

// --- Bauble of Abundance: banish self, each player draws a card ---
// Bauble of Abundance's element is NORM. Seeded directly onto the field (REGALIA
// convention), its ability is banish-self at zero reserve cost: one CustomInput Activate
// click draws a card for both the activating player and the opponent, then moves the
// bauble to myBanish.
// Notes carried over from the original definition:
//   - Bauble of Abundance (REGALIA)
gaScenario('bauble-of-abundance-draw')
    ->standardDeck('Windslice')
    ->seed('myField', 'Bauble of Abundance')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Bauble of Abundance')
    ->register();

// --- Blood Surge: until end of turn, you can't draw cards ---
// Blood Surge's element is EXIA, so the starting champion's Subcards are patched with a real
// EXIA champion (Dante, Hemomancer) to unlock element access. Without the [Class Bonus]
// discount and with the champion undamaged (below the [Level 5+][Damage 10+] threshold),
// Blood Surge draws 0 cards itself but unconditionally applies a "can't draw cards until end
// of turn" global effect (ri955ygd5v_NO_DRAW) to the activating player. Bauble of Abundance
// (NORM, seeded directly onto the field per REGALIA convention) is then activated as a second,
// independent draw source: its "each player draws a card" effect is blocked for the
// NO_DRAW'd activating player but still resolves normally for the opponent, proving the
// prevention is real (not just an absence of draw sources) via the asymmetric outcome.
// Notes carried over from the original definition:
//   - EXIA lineage/element unlock
//   - Bauble of Abundance (REGALIA) - second draw source
gaScenario('blood-surge-no-draw')
    ->standardDeck('Blood Surge')
    ->champion(lineage: ['Dante, Hemomancer'])
    ->seed('myField', 'Bauble of Abundance')
    ->seed('myHand', 'Blood Surge')
    ->play('Blood Surge')->pay(2)->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->register();

// --- Blistering Insurgent: whenever your champion attacks for the first time each turn, +1 POWER ---
// Notes carried over from the original definition:
//   - give the champion positive power so it can attack without a weapon
//   - Rule 1.h: the first player can't attack on turn 1, so end turn twice (P1->P2->P1) to
//   - reach turn 3 before declaring an attack.
gaScenario('blistering-insurgent-attack-buff')
    ->standardDeck('Blistering Insurgent')
    ->patch('myField-0', ['Counters' => ['potion_animate_power' => 3]])
    ->seed('myHand', 'Blistering Insurgent')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->play('Blistering Insurgent')->pay(2)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->passBoth()
    ->register();

// --- Brooch X Ultra: Ally Link, entering the field linked to a target ally ---
// Same ARCANE Subcards lineage patch as conductive-strike-static-counters. Ally Link is
// generic engine machinery ($AllyLink_Cards, a plain boolean set at
// GrandArchiveSim/Custom/GameLogic.php:864) mirroring Weapon Link's shape
// (flametech-bladecore-weapon-link): activating with a valid ally on the field queues an
// MZCHOOSE to pick the link target, and CreateAllyLink stores the link on both sides as plain
// properties (GameLogic.php:22572-22587) -- the ally's Subcards gains the linking card's
// CardID, and the linking object's Counters gets 'linkedToAlly' => the ally's CardID. The
// "+2POWER" and "On Attack: banish a random memory card to draw" clauses read that link at
// computed-value/listener-dispatch time with no additional stored state, so they're out of
// scope here.
// Notes carried over from the original definition:
//   - Lorraine, Arclight Saber (ARCANE)
//   - Dungeon Guide (ALLY) - link target
gaScenario('brooch-x-ultra-ally-link')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Brooch X Ultra')
    ->endTurn()
    ->endTurn(2)->answer(1, '-')
    ->play('Brooch X Ultra')->target('myField-1')->pay(2)
    ->expectProperty('myField-2', 'CardID', '3Gx9ByIl9t', 'Brooch X Ultra enters the field')
    ->expectProperty('myField-2', 'Counters', '{"linkedToAlly":"em6eEh9q8y"}', 'Brooch X Ultra records its link target as Dungeon Guide')
    ->expectProperty('myField-1', 'Subcards', '["3Gx9ByIl9t"]', 'Dungeon Guide records Brooch X Ultra as a linked Subcard')
    ->expectDqEmpty()
    ->register();

// --- Banner Knight: [Class Bonus][Level 2+] Other allies and weapons get +1 POWER ---
// Banner Knight's field-presence buff (GameLogic.php:11856-11871) is purely computed at
// ObjectCurrentPower() read time -- no flag or counter is ever stamped on the buffed object, so
// card_property_equals has nothing to assert against. Proven instead via a new
// computed_power_equals assertion type (Core/RegressionTestFramework.php) that calls
// ObjectCurrentPower() directly, same as the engine's own combat/render code paths do. [Class
// Bonus][Level 2+] requires a WARRIOR champion at level 2+; the champion's CardID is patched
// directly to Lorraine, Arclight Saber (level 3, WARRIOR) -- the same technique used by
// surged-coordinator-class-bonus-counters. Dungeon Guide (base POWER 1) is seeded onto the field
// as the buffed ally; expected computed power is 1 + 1 = 2.
// Notes carried over from the original definition:
//   - Banner Knight
//   - Dungeon Guide, buffed ally
gaScenario('banner-knight-class-bonus-power-buff')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Arclight Saber')
    ->seed('myField', 'Banner Knight')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->tested('Banner Knight')
    ->register();

// --- Bloodbond Bladesworn: [Class Bonus] +1 POWER per 10 damage counters on champion ---
// Notes carried over from the original definition:
//   - Jin, Fate Defiant (WARRIOR), for Class Bonus
//   - 25 damage on champion
//   - Bloodbond Bladesworn
gaScenario('bloodbond-bladesworn-class-bonus-champion-damage-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Jin, Fate Defiant')
    ->champion(damage: 25)
    ->seed('myField', 'Bloodbond Bladesworn')
    ->endTurn()
    ->tested('Bloodbond Bladesworn')
    ->register();

// --- Berserker Plate: recollection phase - deal 3 unpreventable to your champion, then draw ---
// Berserker Plate's recollection-phase trigger (GameLogic.php ~9012-9022, inside the
// unconditional per-field-card switch in ResolveBeforeRecollectionPhaseStart) deals 3
// unpreventable damage to the turn player's champion, then draws a card. Reaching player 1's
// own recollection phase from the initial gamestate needs the same P1->P2->P1 cycle as
// planar-abyss-delayed-destroy-south-damage: P1 pass, P2 pass reaches P1's turn 2 MAT phase
// (BeforeRecollectionPhase's own currentTurn===1 early-return requires turn>1), and declining
// the MAT-phase materialize offer ('PASS') lets the phase engine auto-advance through
// BREC->REC (firing this trigger)->DRAW->MAIN. The [Class Bonus] +7 LIFE clause is a computed
// stat buff with no stored counter to assert and is out of scope here (consistent with the
// established rule for computed buffs); the recollection trigger is unconditional regardless of
// class bonus, so no champion class patch is needed.
gaScenario('berserker-plate-recollection-damage-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Berserker Plate')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Berserker Plate')
    ->expectDamage('myField-0', 3, 'Berserker Plate deals 3 unpreventable damage to the champion during recollection')
    ->expectZoneCount('myHand', 9, 1, 'starting hand 7 + Berserker Plate\'s recollection draw 1 + normal draw-phase draw 1 = 9')
    ->expectDqEmpty()
    ->register();

// --- Blastshot Pump: [Class Bonus] redirect combat damage to an additional unit ---
// Weapons need a "durability" counter to even be considered available to attack with
// (GetAvailableWeapons in CombatLogic.php), and functional weapons (GUN/BOW/AETHERWING) must
// already be loaded (non-empty Subcards). Same turn-cycle approach as automaton-bomber-
// ranged-attack (Rule 1.h blocks player 1's own turn-1 attack): player 1 ends turn 1, then
// player 2's champion attacks using Blastshot Pump (with a RANGER Class Bonus champion also
// on their field) against one of player 1's two units, redirecting some of the hit damage to
// the other.
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (RANGER champion) - Class Bonus source
//   - Blastshot Pump, loaded and usable
//   - Dungeon Guide - primary attack target
//   - ends turn 1
//   - (The opportunity windows that used to be answered with two PASSes were offering a bogus activation of the loaded Plated Bullet from the intent: it is no longer offered, so the Pump's choice follows directly.)
gaScenario('blastshot-pump-class-bonus-redirect-damage')
    ->standardDeck('Blastshot Pump')
    ->seed('myField', 'Diana, Keen Huntress', player: 2)
    ->seed('myField', 'Blastshot Pump', player: 2, properties: ['Counters' => ['durability' => 1], 'Subcards' => ['l75tlzsmw3']])
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-2')->answer(2, 'theirField-0')->answer(2, 'theirField-1')
    ->tested('Blastshot Pump')
    ->expectDqEmpty(step: 7)
    ->expectDamage('p1Field-0', 3, 'primary combat target (champion) takes the full 3 damage', step: 7)
    ->expectProperty('p1Graveyard-0', 'CardID', 'em6eEh9q8y', '[Class Bonus] the same damage is also dealt to an additional unit the attacker doesn\'t control -- Dungeon Guide\'s 3 life means this redirect damage is exactly lethal, so it dies and moves to the graveyard rather than staying on the field', step: 7)
    ->expectDamage('p1Graveyard-0', 3, 'the graveyard-bound unit still shows the 3 damage that killed it', step: 7)
    ->register();

// --- Bulwark Sword: [Class Bonus] +1 POWER; additional attack cost pay (2) ---
// Weapons need a "durability" counter to be considered available to attack with
// (GetAvailableWeapons in CombatLogic.php); SWORD weapons (unlike GUN/BOW/AETHERWING) don't
// need to be "loaded". Same turn-cycle approach as automaton-bomber-ranged-attack (Rule 1.h
// blocks player 1's own turn-1 attack): player 1 ends turn 1, then player 2's champion
// attacks using Bulwark Sword (with a GUARDIAN Class Bonus champion also on their field),
// paying the weapon's own additional 2-reserve attack cost.
// Notes carried over from the original definition:
//   - Tonoris, Lone Mercenary (GUARDIAN champion) - Class Bonus source
//   - Bulwark Sword, usable
//   - ends turn 1
gaScenario('bulwark-sword-class-bonus-attack-cost')
    ->standardDeck('Bulwark Sword')
    ->seed('myField', 'Tonoris, Lone Mercenary', player: 2)
    ->seed('myField', 'Bulwark Sword', player: 2, properties: ['Counters' => ['durability' => 1]])
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-2')->answer(2, 'theirField-0')->pay(2, 2)->pass(2)
    ->pass(1)
    ->tested('Bulwark Sword')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 3, '2 base + 1 [Class Bonus] POWER')
    ->register();

// --- Barter Herbs: sacrifice up to two Herbs, summon that many chosen replacement Herb tokens ---
// Barter Herbs (NORM, reserve 1) sacrifices up to two Herb-subtype objects, choosing a
// replacement Herb token for each one sacrificed (BarterHerbsSacrificeLoop, PotionLogic.php
// ~664). Two Blightroot HERB tokens are seeded onto the field to sacrifice; each is replaced
// with Manaroot (chosen from the six-option temp-zone menu). Only the base sacrifice/summon
// loop is covered; the [Class Bonus] Floating Memory clause is a passive/reusable-elsewhere
// property and out of scope.
// Notes carried over from the original definition:
//   - Blightroot (HERB) #1
//   - Blightroot (HERB) #2
//   - pay reserve 1
//   - Decline the ambient opportunity offering the seeded Blightroots' OWN fast Sacrifice
//   - ability (a different mechanic than Barter Herbs' own sacrifice loop below).
//   - sacrifice Blightroot #1
//   - choose Manaroot as replacement
//   - sacrifice Blightroot #2
//   - choose Manaroot as replacement
gaScenario('barter-herbs-sacrifice-summon')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Barter Herbs')
    ->play('Barter Herbs')->pay(1)->pass(1)->target('myField-1')->target('myTempZone-1')->target('myField-1')
    ->target('myTempZone-1')
    ->expectZoneCount('myField', 3, 1, 'champion plus two summoned Herb tokens (two sacrifice/summon cycles)')
    ->expectProperty('p1Field-1', 'CardID', '5joh300z2s', 'probe replacement herb #1')
    ->expectProperty('p1Field-2', 'CardID', '5joh300z2s', 'probe replacement herb #2')
    ->register();

// --- Beastbond Boots: Banish -- champion gains spellshroud (only if you control an Animal/Beast ally) ---
// Beastbond Boots is REGALIA -- adding it to 'myHand' via setup gets silently redirected to the
// Material zone instead (HandAddReplacement, GameLogic.php ~17282), matching every other
// REGALIA item fixture this session -- it must be seeded directly onto myField. Gray Wolf (a
// BEAST ally, also from this deck) is seeded alongside it so the prereq (control an Animal or
// Beast ally) is already satisfied.
// ENGINE BEHAVIOR CONFIRMED (traced via temporary error_log instrumentation, since reverted):
// using the "attempt to pass" trick as the ONLY action to reach a field-resident item's
// ability is NOT safe when the granted effect is "until end of turn" -- if nothing else
// responds after the ability resolves, the original pass attempt itself completes and the turn
// actually ends, running end-of-turn cleanup that clears the just-granted SPELLSHROUD before it
// can ever be observed (confirmed: AddTurnEffect correctly applies it, and it is still present
// through the entire AbilityOpportunity/GrantOpportunityWindow resolution chain, but is gone by
// the time the action's final gamestate is written). This is the same class of issue documented
// in synth-disrupter-banish-automaton-rested's notes ("attempt to pass ends the turn if nothing
// else responds -- cannot be used mid-sequence"). Fixed the same way: play a real card first
// (Scry the Skies, NORM, reserve 1) so a same-turn action has already happened, then reach the
// Boots' ability via a direct Activate:0 click instead of the pass-trick.
// Notes carried over from the original definition:
//   - Gray Wolf (BEAST) -- satisfies "control an Animal or Beast ally"
//   - Beastbond Boots
//   - Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
//   - pay Scry the Skies' 1 reserve
gaScenario('beastbond-boots-banish-spellshroud')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Gray Wolf')
    ->seed('myField', 'Beastbond Boots')
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')
    ->tested('Beastbond Boots')
    ->register();

// --- Beastbond Ears: champion +1 level as long as you control an Animal or Beast ally ---
// Beastbond Ears is NORM and unconditional (no Class Bonus gate). No "computed level" assertion
// type exists in this harness (only computed_power_equals/computed_life_equals -- champion
// level bonuses cannot be directly asserted), so the +1 level is confirmed indirectly: at the
// default champion level (0), Scry the Skies' "Glimpse LV" would be an invisible Glimpse 0
// no-op, but with Beastbond Ears' static bonus active (Gray Wolf, a BEAST ally, is seeded
// alongside it), the effective level is 1, so Glimpse LV surfaces a real 1-card MZREARRANGE
// decision that must be explicitly answered -- the fixture's own action sequence only completes
// if that decision genuinely appears, which is itself the proof.
// Notes carried over from the original definition:
//   - Beastbond Ears
//   - Gray Wolf (BEAST)
//   - MZREARRANGE response: keep original order (same no-op default as idle-thoughts-glimpse-4).
//   - This decision only exists because the effective level is 1, not the default 0 -- proof
//   - that Beastbond Ears' static bonus is applying.
gaScenario('beastbond-ears-level-while-animal-beast')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Beastbond Ears')
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)->answer(1, 'Top=em6eEh9q8y;Bottom=')
    ->tested('Beastbond Ears')
    ->expectDqEmpty()
    ->register();

// --- Blissful Calling: look at top 5, may reveal an Animal or Beast card to hand ---
// Blissful Calling is NORM, no lineage patch needed. The deck's top card (myDeck-0) is patched
// directly to Gray Wolf (a BEAST ally) so it is among the top 5 looked at and offered as the
// sole qualifying candidate.
// Notes carried over from the original definition:
//   - Gray Wolf on top of deck
//   - choose Gray Wolf
gaScenario('blissful-calling-look-5-reveal-animal-beast')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myDeck-0', ['CardID' => 'hJ2xh9lNMR'])
    ->seed('myHand', 'Blissful Calling')
    ->play('Blissful Calling')->pay(1)->target('myTempZone-0')
    ->register();

// --- Blitz Mage: vanilla stats (3 POWER / 1 LIFE, no ability) ---
// Blitz Mage is FIRE and vanilla (no printed ability). The default starting champion (Spirit of
// Fire) is already FIRE, so no lineage/Subcards patch is needed to unlock element access.
gaScenario('blitz-mage-vanilla-stats')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Blitz Mage')
    ->play('Blitz Mage')->pay(3)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'CardID', 'u8m6LuUSSu')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->register();

// --- Bathe in Light: Recover 4; at the beginning of your next recollection phase, Recover 4 ---
// Bathe in Light's element is LUXEM; the starting champion's CardID is patched directly to
// Zander, Blinding Steel (LUXEM) so it can be legally activated, and its Damage is pre-set to
// 10 so the unconditional "Recover 4" is observable as a Damage decrease. The delayed "at your
// next recollection phase, Recover 4" half is out of scope (would require advancing a full
// turn cycle); the immediate Recover 4 and the BATHE_IN_LIGHT_RECOVER global effect it
// schedules (GameLogic.php CardActivated macro) are both directly asserted.
// Notes carried over from the original definition:
//   - Zander, Blinding Steel (LUXEM) + damage precondition
gaScenario('bathe-in-light-recover-delayed')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Blinding Steel', damage: 10)
    ->seed('myHand', 'Bathe in Light')
    ->play('Bathe in Light')->pay(2)
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 6, 'Recover 4 reduces champion Damage from 10 to 6')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'd9zax2g20h')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'BATHE_IN_LIGHT_RECOVER', 'Schedules the delayed Recover 4 at the next recollection phase')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Bolstering Tempest: target Human ally gets +3 POWER until end of turn ---
// Bolstering Tempest (PwHub76Fw4, EXALTED) needs the EXALTED element, which auto-unlocks once
// any OTHER advanced element is enabled (GameLogic.php, GetPlayerEnabledElements) -- the
// champion is CardID-patched to Lorraine, Spirit Ruler (n2TKqNaODR, CRUX) *with* its Subcards
// set to the base Spirit of Wind (pNiyaGlIe7) so the real champion-lineage element union
// (GetChampionLineage/GetPlayerEnabledElements) grants both CRUX (-> EXALTED) and WIND, rather
// than losing WIND the way a bare CardID patch would. A Dungeon Guide (em6eEh9q8y, HUMAN ALLY)
// is seeded onto the player's own field as the only legal target.
// cardActivatedAbilities["PwHub76Fw4:0"] (GeneratedMacroCode.php) offers HUMAN allies as an
// MZMAYCHOOSE target; its handler adds TurnEffect "PwHub76Fw4-POWER-3".
gaScenario('bolstering-tempest-human-ally-power')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(cardName: 'Lorraine, Spirit Ruler', lineage: ['Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Bolstering Tempest')
    ->play('Bolstering Tempest')->pay(2)->passBoth()->target('myField-1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PwHub76Fw4-POWER-3"]', 'The targeted Human ally gains the PwHub76Fw4-POWER-3 turn effect.')
    ->register();

// --- Beacon Knight: whenever regalia enter the field under your control, buff counter ---
// Beacon Knight (sucwQ9or0n, WARRIOR [Class Bonus]) is seeded directly onto the field alongside
// a champion patched to Lorraine, Wandering Warrior (DpHDGaX2Pn) for the class bonus. Both
// players then end their first turns (reaching player 1's second turn, when a real Materialize
// opportunity is offered) and player 1 materializes Safeguard Amulet (yj2rJBREH8, 0-memory-cost
// REGALIA already in this deck's Material zone). The generic field-add hook in GameLogic.php
// (~line 8329, "Beacon Knight: whenever one or more regalia enter the field...") then adds a
// buff counter to Beacon Knight.
gaScenario('beacon-knight-regalia-enter-buff')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(cardName: 'Lorraine, Wandering Warrior')
    ->seed('myField', 'Beacon Knight')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-6')
    ->tested('Beacon Knight')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Beacon Knight gains a buff counter the instant a regalia is materialized under its controller.')
    ->register();

// --- Buoyant Driftguard: On Enter, opponent may gain control; if so, you gain Crowd's Favor ---
// Buoyant Driftguard (TrK2lroxkz, WATER, reserve 3) is played from hand. WATER is a basic
// element that still needs unlocking via champion lineage (GetPlayerEnabledElements only
// auto-enables NORM) -- the champion is CardID-patched to Spirit of Water (tafqldAGRF,
// WATER, level 0) to unlock it. enterAbilities["TrK2lroxkz:0"] (GeneratedMacroCode.php)
// queues a YESNO "Have target opponent gain control of CARDNAME?"; customDQHandlers
// ["TrK2lroxkz:0:Enter-1"] answers YES by setting $obj->Controller to the opponent and
// calling GainCrowdsFavor($player) for the original (still-owning) controller.
// Notes carried over from the original definition:
//   - Spirit of Water - WATER unlock
gaScenario('buoyant-driftguard-enter-control-transfer-crowds-favor')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Spirit of Water')
    ->seed('myHand', 'Buoyant Driftguard')
    ->play('Buoyant Driftguard')->pay(3)->answer(1, 'YES')
    ->expectProperty('p1Field-1', 'CardID', 'TrK2lroxkz', 'Buoyant Driftguard is still physically in player 1\'s field storage')
    ->expectProperty('p1Field-1', 'Controller', '2', 'Answering YES flips control of Buoyant Driftguard to the opponent')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'gpmJdGYqoC', 'The original controller gains the Crowd\'s Favor status for giving up control')
    ->register();

// --- Besieged Slash: Costs 2 less vs 3+ opposing units [Class Bonus] ---
// Besieged Slash (activationCostModifierAbilities["Dkq7QnrGJI:0"], GeneratedMacroCode.php) costs
// 2 less (3 -> 1 reserve) when [Class Bonus] is active (player's champion is WARRIOR) AND the
// opponent controls 3+ ALLY/CHAMPION units. Player 2 is patched to a WARRIOR champion (Lorraine,
// Wandering Warrior) for the class bonus; player 1 (the opponent, from P2's perspective) gets 2
// extra Dungeon Guide allies seeded onto their field, which combined with their own already-
// present champion brings their unit count to exactly 3. Same real attack-card-activation shape
// as heavy-swing-class-bonus-discount: P1 passes turn 1 (Rule 1.h forbids the opening player from
// activating ATTACK cards turn 1), then P2 activates Besieged Slash on their own turn 1, paying
// only 1 rep of the reserve decision (not 3) before the mandatory attack-card target selection.
// Notes carried over from the original definition:
//   - Lorraine, Wandering Warrior (WARRIOR) - Class Bonus source
//   - Dungeon Guide ALLY 1/2 (plus P1's own champion = 3 units total)
//   - Dungeon Guide ALLY 2/2
//   - ends turn 1
//   - pay reserve 1/1 (discounted from 3)
//   - target P1's champion
gaScenario('besieged-slash-class-bonus-3-units-discount')
    ->standardDeck('Besieged Slash')
    ->champion(cardName: 'Lorraine, Wandering Warrior', player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Besieged Slash', player: 2)
    ->endTurn()
    ->play('Besieged Slash', 2)->pay(1, 2)->answer(2, 'theirField-0')
    ->expectDqEmpty()
    ->expectProperty('p2Intent-0', 'CardID', 'Dkq7QnrGJI', '[Class Bonus] Besieged Slash costs 2 less to activate when the opponent controls 3+ units (activationCostModifierAbilities["Dkq7QnrGJI:0"]) -- only 1 rep of the reserve decision was needed (not 3) before the mandatory attack-card target selection succeeded')
    ->register();

// --- Blossoming Denial: [Class Bonus] cost reduction, unconditional token summon, real negate ---
// activationCostModifierAbilities["1nnpbddblx:0"] (GeneratedMacroCode.php ~8125) returns -3
// when IsClassBonusActive($player, ["CLERIC"]) (true: Diao Chan, Enchantress is CLERIC)
// AND count(GetMemory($opponent)) >= 5 -- player 1's myMemory is seeded with 5 cards so
// Blossoming Denial's printed 3-reserve cost drops to 0, observable because NO reserve-
// payment actions are needed between playing it and its own MZMAYCHOOSE appearing.
// cardActivatedAbilities["1nnpbddblx:0"] (~14982) offers an OPTIONAL MZMAYCHOOSE negate
// target via "NegateActivationResolve|default|3", then UNCONDITIONALLY queues
// "BlossomingDenialFinal" (block 2, after the negate's block 1) --
// customDQHandlers["BlossomingDenialFinal"] (OpportunityLogic.php ~586) always summons two
// Flowerbud tokens (yn78t73w1p) onto the opponent's (player 1's) field regardless of the
// negate outcome. The bait is Mastermind Scheme (9lbewemius, ACTION, NORM, reserve 6, no
// targeting of its own) rather than the usual Escharotomy specifically so its controller
// (player 1) is down to only 1 payable hand card -- CountAvailableReservePayments(player 1)
// = 1 < 3, so NegateActivationResolve (OpportunityLogic.php ~388) takes its SYNCHRONOUS
// "insufficient funds" branch and calls NegateCardActivation(..., "default") immediately, in
// the same action as the MZMAYCHOOSE target selection -- no deferred YES/NO round-trip (see
// the note above Glimmering Refusal's fixture for why the deferred branch is unusable here:
// it loses a real engine race against PostResolutionCheck's immediate stack continuation).
// This run therefore proves BOTH the real negate (Mastermind Scheme banished/graveyarded
// without its own ability ever firing) and the unconditional token summon in one pass.
// Notes carried over from the original definition:
//   - Mastermind Scheme, the target activation (NORM, reserve 6, no lineage patch needed)
//   - memory card 1/5 (cost-reduction condition: opponent has 5+ cards in memory)
//   - memory card 2/5
//   - memory card 3/5
//   - memory card 4/5
//   - memory card 5/5
//   - Diao Chan, Idyll Corsage (TERA) - unlocks TERA for Blossoming Denial
//   - Diao Chan, Enchantress (CLERIC) - Class Bonus source (starting-champion selection always picks the level-0 Spirit, so this is seeded as a second field object)
//   - Blossoming Denial
//   - play Mastermind Scheme
//   - pay reserve 1/6
//   - pay reserve 2/6
//   - pay reserve 3/6
//   - pay reserve 4/6
//   - pay reserve 5/6
//   - pay reserve 6/6 -- only 1 hand card left afterward
//   - player 1 passes their own opportunity
//   - player 2 takes a fast action instead of passing: Blossoming Denial
//   - No reserve-payment actions here: cost is 0 (3 - 3 Class Bonus discount), straight to
//   - its own MZMAYCHOOSE.
//   - choose Mastermind Scheme's activation to negate (optional MZMAYCHOOSE) -- player 1 can't afford to pay 3, synchronous negate
gaScenario('blossoming-denial-cost-reduction-token-negate')
    ->deck($diaoChanDeck)
    ->seed('myHand', 'Mastermind Scheme')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->champion(lineage: ['Diao Chan, Idyll Corsage'], player: 2)
    ->seed('myField', 'Diao Chan, Enchantress', player: 2)
    ->seed('myHand', 'Blossoming Denial', player: 2)
    ->play('Mastermind Scheme')->pay(6)->pass(1)->answer(2, 'myHand-7')->answer(2, 'EffectStack-0')
    ->tested('Blossoming Denial')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'yn78t73w1p', 'customDQHandlers["BlossomingDenialFinal"] unconditionally summoned a Flowerbud token (1 of 2) onto the opponent\'s field, in the SAME resolution as the real negate below -- proving the token clause fires regardless of the negate outcome')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Field-2', 'CardID', 'yn78t73w1p', 'Second Flowerbud token (2 of 2) -- \'Each opponent summons two Flowerbud tokens\' delivered exactly two, unconditionally')
    ->expectProperty('p1Field-2', 'Status', '2')
    ->expectDamage('p1Field-2', 0)
    ->expectProperty('p1Field-2', 'Counters', '[]')
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Graveyard-0', 'CardID', '9lbewemius', 'Mastermind Scheme\'s activation is genuinely negated to its default destination (Graveyard) because its controller (player 1, down to 1 payable card) could not afford Blossoming Denial\'s pay-3 demand -- its own ability never got a chance to run')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Memory-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-6', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-7', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-8', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-9', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-10', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p2Graveyard-0', 'CardID', '1nnpbddblx')
    ->expectZoneCount('myHand', 1, 1)
    ->expectZoneCount('myField', 3, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 11, 1)
    ->expectZoneCount('theirHand', 7, 1, 'Player 2\'s hand only lost the 1 card for playing Blossoming Denial itself (8 -> 7), with zero reserve-payment actions in between -- activationCostModifierAbilities["1nnpbddblx:0"]\'s -3 [Class Bonus] discount (gated on the opponent having 5+ memory cards) reduced the printed 3-reserve cost all the way to 0')
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Bloom: Summer's Glow: [Class Bonus] each opponent sacrifices all Flowerbuds; controller
// summons chosen Lycoria/Baihua split. ---
// Bloom: Summer's Glow is a TERA (advanced element) ACTION card whose entire printed effect is
// Class Bonus-gated: cardActivatedAbilities["a708z5ethq:0"] (GeneratedMacroCode.php:16686)
// returns immediately if !IsClassBonusActive($player, ["CLERIC"]) with no other baseline
// effect, matching the printed text exactly. myField-0 is patched directly to Diao Chan,
// Enchantress (00xbh8oc00, CLERIC) with Subcards=[Diao Chan, Idyll Corsage (d7l6i5thdy, TERA)]
// -- this makes myField-0 itself a literal on-field CLERIC champion (satisfying
// IsClassBonusActive, which scans physical field objects) while granting TERA element access
// via lineage; patchMzId never triggers Enter(), so neither champion's own On Enter fires.
// Two Flowerbud tokens (yn78t73w1p, CLERIC/FLOWERBUD subtype) are seeded onto the OPPONENT's
// field ("each opponent sacrifices all Flowerbuds"). After playing the card and paying its
// 2-reserve cost, the ability queues a TWOSIDEDSLIDER (0 to flowerbudCount) plus
// customDQHandlers["BloomSummerResolve"] (Custom/CardDQHandlers.php): answering the slider with
// "1" (a non-uniform, non-degenerate split -- distinguishable from an always-Lycoria or
// always-Baihua stub) sacrifices both Flowerbuds and summons exactly 1 Lycoria + 1 Baihua onto
// the opponent's field.
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress (CLERIC, literal field champion for Class Bonus) + Idyll Corsage lineage (TERA access)
//   - Flowerbud token #1 on the opponent's field
//   - Flowerbud token #2 on the opponent's field
//   - reserve 1/2
//   - reserve 2/2
//   - TWOSIDEDSLIDER response: 1 Lycoria, 1 Baihua (of 2 total Flowerbuds)
gaScenario('bloom-summers-glow-class-bonus-flowerbud-split')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->champion(cardName: 'Diao Chan, Enchantress', lineage: ['Diao Chan, Idyll Corsage'])
    ->seed('myField', 'Flowerbud', player: 2)
    ->seed('myField', 'Flowerbud', player: 2)
    ->seed('myHand', 'Bloom: Summer\'s Glow')
    ->play('Bloom: Summer\'s Glow')->pay(2)->answer(1, '1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'a708z5ethq')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Field-1', 'CardID', '89nl1vcn33', 'One of the opponent\'s 2 sacrificed Flowerbuds was replaced by a Lycoria token (the chosen split of 1), per the \'1\' TWOSIDEDSLIDER response')
    ->expectProperty('p2Field-2', 'CardID', 'i59eamoov0', 'The other sacrificed Flowerbud was replaced by a Baihua token -- a real, non-degenerate 1 Lycoria / 1 Baihua split, not an always-one-type stub')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->expectZoneCount('theirField', 3, 1, 'Opponent\'s field has exactly champion + Lycoria + Baihua (3 total) -- both Flowerbuds are gone (sacrificed to theirGraveyard) and replaced 1-for-1 by the chosen token types, no leftover Flowerbuds and no extra tokens')
    ->register();

// --- Bloom: Winter's Chill: same mechanic as Bloom: Summer's Glow, with Nightshade/Floodbloom
// tokens instead of Lycoria/Baihua. ---
// Same shape as bloom-summers-glow-class-bonus-flowerbud-split: cardActivatedAbilities
// ["b4jvyh23y1:0"] (GeneratedMacroCode.php:16933) is Class Bonus-gated
// (IsClassBonusActive(CLERIC)) with no baseline effect, and customDQHandlers
// ["BloomWinterResolve"] (Custom/CardDQHandlers.php) replaces sacrificed Flowerbuds with a
// chosen Nightshade/Floodbloom split instead of Lycoria/Baihua. myField-0 is patched the same
// way (Diao Chan, Enchantress + Idyll Corsage lineage). Two Flowerbud tokens are seeded onto
// the opponent's field, and the TWOSIDEDSLIDER response of "1" again proves a real,
// non-degenerate 1/1 split rather than an always-one-type stub.
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress (CLERIC, literal field champion for Class Bonus) + Idyll Corsage lineage (TERA access)
//   - Flowerbud token #1 on the opponent's field
//   - Flowerbud token #2 on the opponent's field
//   - reserve 1/3
//   - reserve 2/3
//   - reserve 3/3
//   - TWOSIDEDSLIDER response: 1 Nightshade, 1 Floodbloom (of 2 total Flowerbuds)
gaScenario('bloom-winters-chill-class-bonus-flowerbud-split')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->champion(cardName: 'Diao Chan, Enchantress', lineage: ['Diao Chan, Idyll Corsage'])
    ->seed('myField', 'Flowerbud', player: 2)
    ->seed('myField', 'Flowerbud', player: 2)
    ->seed('myHand', 'Bloom: Winter\'s Chill')
    ->play('Bloom: Winter\'s Chill')->pay(3)->answer(1, '1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'b4jvyh23y1')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Field-1', 'CardID', 'rzk3mjblse', 'One of the opponent\'s 2 sacrificed Flowerbuds was replaced by a Nightshade token, per the \'1\' TWOSIDEDSLIDER response')
    ->expectProperty('p2Field-2', 'CardID', '4s1kmjeaks', 'The other sacrificed Flowerbud was replaced by a Floodbloom token -- a real, non-degenerate 1 Nightshade / 1 Floodbloom split, not an always-one-type stub')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->expectZoneCount('theirField', 3, 1, 'Opponent\'s field has exactly champion + Nightshade + Floodbloom (3 total) -- both Flowerbuds are gone and replaced 1-for-1 by the chosen token types')
    ->register();

// Broken Promises: additional cost sacrifice a Fatestone item/Fatebound ally, draw into memory,
// [Guo Jia Bonus] put a quest counter on your champion.
// Notes carried over from the original definition:
//   - Patch champion to Guo Jia, Chosen Disciple for [Guo Jia Bonus], keeping Spirit of Fire
//   - in Subcards so CanPlayerUseCardElement() still grants FIRE access for this FIRE card
//   - (a real level-up would preserve Spirit of Fire in the lineage the same way).
//   - Seed a Fatestone item (Craggy Fatestone) onto the field as the mandatory sacrifice fodder.
//   - Additional cost: sacrifice the seeded Craggy Fatestone.
//   - Pay the 1-reserve cost with a Dungeon Guide from hand.
gaScenario('broken-promises-sacrifice-draw-quest')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
4 Broken Promises
6 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Craggy Fatestone')
    ->playSlot(5)->target('myField-1')->pay(1)->passBoth()
    ->tested('Broken Promises')
    ->register();

// Band of Burning Verdict: On Enter, draw a card. (Fixed: see
// band-of-burning-verdict-play-without-tamer-bonus and band-of-burning-verdict-rest-buff-requires-
// activation for the two defects that used to live here -- the illegal play-block is gone and the
// [REST] buff no longer auto-fires, so this fixture now needs only the FSM play action.)
// Notes carried over from the original definition:
//   - Tamer champion + an Animal/Beast ally are kept here (though no longer required just to
//   - play the card -- see the fix) so this fixture still also covers the On Enter draw in the
//   - presence of a legal REST-ability target, same as before.
gaScenario('band-of-burning-verdict-enter-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Cheerful Slime')
    ->patch('myHand-0', ['CardID' => '7mmve2l328'])
    ->playSlot(0)
    ->tested('Band of Burning Verdict')
    ->expectZoneCount('myDeck', 2, 1, 'Band of Burning Verdict\'s On Enter draws a card, reducing the deck from 3 to 2')
    ->expectProperty('myField-2', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'The [Class Bonus][REST] buff does NOT auto-fire on Enter even with a legal Tamer champion and Animal/Beast ally present -- Band of Burning Verdict itself carries no buff-related TurnEffects')
    ->expectProperty('myField-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'The Cheerful Slime (Animal ally) is untouched -- no power-buff or TRUE_SIGHT TurnEffect from an auto-fired REST ability')
    ->register();

// Band of Burning Verdict: playing this ally from hand has no printed restriction -- proves the
// fixed activateCardPrereqs["7mmve2l328:0"] no longer requires the Tamer class bonus or an
// Animal/Beast ally on the field just to materialize the card. Deliberately NO Tamer champion and
// NO Animal/Beast ally anywhere in this fixture's setup.
gaScenario('band-of-burning-verdict-play-without-tamer-bonus')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->patch('myHand-0', ['CardID' => '7mmve2l328'])
    ->playSlot(0)
    ->tested('Band of Burning Verdict')
    ->expectZoneCount('myDeck', 2, 1, 'Band of Burning Verdict is legally played from hand with no Tamer champion and no Animal/Beast ally anywhere -- its On Enter draws a card, reducing the deck from 3 to 2')
    ->expectProperty('myField-1', 'CardID', '7mmve2l328', 'Band of Burning Verdict actually materializes onto the field -- the fixed activateCardPrereqs no longer blocks the play')
    ->expectZoneCount('myHand', 7, 1, 'Band of Burning Verdict leaves the hand, dropping the hand from 8 to 7')
    ->register();

// Band of Burning Verdict: the printed "[Class Bonus] [REST]: Target Animal or Beast ally you
// control gets +1 POWER and gains true sight until end of turn" must NOT auto-fire on Enter --
// it only applies after an explicit later activation (paying the REST cost, i.e. tapping the
// card). Same Tamer-champion + Cheerful Slime setup as band-of-burning-verdict-enter-draw so the
// ability is legally activatable, but here it is actually activated via the same direct
// myField!CustomInput!Activate:N click GrandArchiveSim/Custom/CustomInput.php offers for any
// field permanent's own activated ability when the decision queue is empty (same mechanism used
// by the charm-of-anticipation-banish-draw fixture for vkL2RFh0yM's activateAbilityAbilities
// entry).
// Notes carried over from the original definition:
//   - Materialize Band of Burning Verdict (myField-2). On Enter draws a card; the REST buff
//   - does NOT fire here -- that's the point of this fixture.
//   - Explicitly activate Band of Burning Verdict's own ability index 0 (the [REST] buff).
//   - MZCHOOSE: target the Cheerful Slime (Animal ally) with the buff.
gaScenario('band-of-burning-verdict-rest-buff-requires-activation')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Cheerful Slime')
    ->patch('myHand-0', ['CardID' => '7mmve2l328'])
    ->playSlot(0)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Band of Burning Verdict')
    ->register();

// --- Bauble of Mending: Banish: Draw a card. [Class Bonus] non-Human ally +1 LIFE ---
// Bauble of Mending's ability is a real, correctly-categorized $activateAbilityAbilities
// entry ("hLHpI5rHIK:0", GeneratedMacroCode.php) -- the free/repeatable field-ability
// dictionary offered through the fast-opportunity MZMAYCHOOSE flow (same mechanism as
// cosmic-astroscope-rest-glimpse). ActivatedAbilityCost() (Custom/GameLogic.php) pays the
// "Banish CARDNAME" cost (moving it to myBanish) BEFORE the ability closure runs, so the
// closure's Draw($player, 1) and class-bonus MZMAYCHOOSE both genuinely fire from a real
// activation. The champion's CardID is patched directly to Silvie, With the Pack (TAMER) to
// satisfy the Class Bonus; Red Slime (TAMER/BEAST/SLIME, non-Human) is seeded onto the field
// as the +1 LIFE target.
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER) - Class Bonus precondition
//   - Bauble of Mending -> myField-1
//   - Red Slime (non-Human target) -> myField-2
//   - Bauble of Mending's own banish-cost removal from myField-1 shifts Red Slime down to
//   - myField-1 (confirmed live via FIXTURE_DEBUG zone dump).
gaScenario('bauble-of-mending-banish-draw-class-bonus-life')
    ->deckTemplate('silvie-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack')
    ->seed('myField', 'Bauble of Mending')
    ->seed('myField', 'Red Slime')
    ->endTurn()->answer(1, 'myField-1@Activate-0@Banish')->target('myField-1')
    ->tested('Bauble of Mending')
    ->register();

// --- Baby Gray Slime: On Enter: may reveal two Slime cards from hand/memory to draw a card ---
// Baby Gray Slime's On Enter ability (enterAbilities['0hsncz1fz2:0'] ->
// customDQHandlers['0hsncz1fz2:0:Enter-1'], GeneratedMacroCode.php) offers an MZMULTICHOOSE
// of exactly 2 Slime-subtype cards from hand/memory; revealing 2 draws a card. Its element is
// NORM, so no champion patch is needed. Two Red Slime copies (mttsvbgl6f, SLIME subtype) are
// seeded directly into hand as reveal fodder (they are never played, so no element/reserve
// concerns for them). Baby Gray Slime itself is played for real from hand via FSM so its
// Enter trigger genuinely fires; the 2 reserve-payment clicks consume the lowest-index
// (filler) hand cards, leaving the 2 seeded Red Slime copies intact and selectable for the
// MZMULTICHOOSE response (verified live via FIXTURE_DEBUG hand-index tracking).
// Notes carried over from the original definition:
//   - Red Slime fodder #1
//   - Red Slime fodder #2
gaScenario('baby-gray-slime-on-enter-reveal-two-slime-draw')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Red Slime')
    ->seed('myHand', 'Red Slime')
    ->seed('myHand', 'Baby Gray Slime')
    ->play('Baby Gray Slime')->pay(2)->answer(1, 'myHand-5&myHand-6')
    ->register();

// --- Baby Green Slime: On Enter: Suppress target item or weapon ---
// Baby Green Slime's On Enter ability (enterAbilities['cqadnk9iz0:0'] ->
// customDQHandlers['cqadnk9iz0:0:Enter-1'], GeneratedMacroCode.php) targets any ITEM/WEAPON
// on either field and calls SuppressAlly($player, $target) (Custom/GameLogic.php ~10561) --
// despite its name, this is the shared suppress helper for any object type, not ally-only --
// which banishes the target and tags it TurnEffects=['SUPPRESSED'] (it would return to the
// field under its owner's control at the next end phase, which this fixture doesn't need to
// play out to observe the suppression itself). Baby Green Slime's element is WIND, not
// native to the 'Spirit of Fire' starting champion, so the champion's Subcards are patched
// with a real WIND champion (Spirit of Wind) purely to unlock element access (same technique
// as deployment-beacon-summon-drone / green-slime-on-enter-two-buff-counters). Bauble of
// Mending (hLHpI5rHIK, ITEM) is seeded onto the field as the suppression target -- only its
// presence as an ITEM matters here, not its own ability. Baby Green Slime is played for real
// from hand via FSM so its Enter trigger genuinely fires.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Bauble of Mending (ITEM target) -> myField-1
//   - Decline the ambient opportunity offered mid-resolution (a hand-card response or Bauble
//   - of Mending's own Banish ability) before Baby Green Slime's Enter trigger offers its
//   - own target choice (same technique as barter-herbs-sacrifice-summon).
//   - target Bauble of Mending for suppression
gaScenario('baby-green-slime-on-enter-suppress-item')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Bauble of Mending')
    ->seed('myHand', 'Baby Green Slime')
    ->play('Baby Green Slime')->pay(2)->pass(1)->target('myField-1')
    ->register();

// --- Baby Red Slime: [Class Bonus] On Attack: discard a fire card to draw into memory and buff power ---
// Baby Red Slime's ENTIRE ability is a Class Bonus On Attack trigger (onAttackAbilities
// ['r7oifozaog:0'] -> customDQHandlers['r7oifozaog:0:OnAttack-1'], GeneratedMacroCode.php):
// gated behind IsClassBonusActive($player, ['TAMER']), it offers an MZMAYCHOOSE to discard a
// hand card; if one is discarded, DrawIntoMemory + if the discarded card's element was FIRE,
// AddTurnEffect(..., POWER_BUFF-style) +1 POWER until end of turn. The champion's CardID is
// patched directly to Silvie, With the Pack (TAMER) for the Class Bonus. Baby Red Slime
// (r7oifozaog) is seeded directly onto the field -- this is fine for proving its OWN On
// Attack trigger (a different trigger than Enter, which this fixture doesn't test). Red
// Slime (mttsvbgl6f, FIRE) is seeded into hand purely as discard fodder to satisfy the
// "if the discarded card was a fire element card" sub-clause. Baby Red Slime then declares a
// real attack (via FSM) against player 2's champion so the On Attack trigger genuinely
// fires.
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER) - Class Bonus precondition
//   - Baby Red Slime -> myField-1
//   - Red Slime (FIRE), discard fodder -> myHand-7
//   - CombatLogic.php's BeginCombatPhase() enforces "only the player who took the opening
//   - turn can't attack on turn 1" -- so the attacker must be player 2, after player 1's
//   - turn 1 ends (same "P1 passes turn 1" shape as samaritan-reach-attacking-ally-damage).
//   - end player 1's turn 1
//   - Baby Red Slime declares an attack
//   - target player 1's champion
//   - discard Red Slime (FIRE) for the On Attack bonus
gaScenario('baby-red-slime-class-bonus-on-attack-discard-fire-power')
    ->deckTemplate('silvie-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack', player: 2)
    ->seed('myField', 'Baby Red Slime', player: 2)
    ->seed('myHand', 'Red Slime', player: 2)
    ->endTurn()->passBoth()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(2, 'myHand-7')
    ->tested('Baby Red Slime')
    ->register();

// Core combat use: Baby Blue Slime is activated in player 2's attack priority window and the prevention absorbs exactly 2 of the 3 combat damage.
// Notes carried over from the original definition:
//   - Nico lineage: WATER element unlock
//   - Red Slime (LIFE 2, the protected Slime) -> p1 field-1
//   - Baby Blue Slime -> myHand-7
//   - Dungeon Guide with 2 buff counters (POWER 3) -> p2 field-1
//   - play Baby Blue Slime
//   - reserve cost 2
//   - end player 1's turn
//   - end-of-main window (Baby Blue Slime's ability is listed)
//   - beginning-of-opponent-turn window
//   - player 2 attacks with the 3-power Dungeon Guide
//   - ... targeting player 1's Red Slime
//   - player 1 activates Baby Blue Slime in the priority window
//   - target: Red Slime
//   - decline Red Slime's Retaliate
gaScenario('baby-blue-slime-rest-prevents-next-2-combat-damage-to-other-slime')
    ->deck($kspDeck)
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myField', 'Red Slime')
    ->seed('myHand', 'Baby Blue Slime')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 2]])
    ->play('Baby Blue Slime')->pay(2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->answer(1, 'myField-2@Activate-0@Prevent')
    ->target('myField-1')->pass(1)
    ->register();

// Main-phase activation + target restriction: only ANOTHER Slime ally YOU control is a legal target.
// Notes carried over from the original definition:
//   - WATER unlock
//   - Red Slime (the only legal target) -> p1 field-1
//   - Dungeon Guide (an ally, but not a Slime) -> p1 field-2
//   - Baby Blue Slime -> myHand-7, enters at p1 field-3
//   - opponent's Red Slime (a Slime ally, but not yours) -> p2 field-1
//   - activate from the main phase
//   - the legal target: Red Slime
gaScenario('baby-blue-slime-rest-main-phase-targets-only-other-slime-allies-you-control')
    ->deck($kspDeck)
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myField', 'Red Slime')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Baby Blue Slime')
    ->seed('myField', 'Red Slime', player: 2)
    ->play('Baby Blue Slime')->pay(2)
    ->action(1, 10001, 'myField-3!CustomInput!Activate:0')
    ->rejects(1, 'myField-3', 'Rejects Baby Blue Slime itself: the target must be ANOTHER Slime ally')
    ->rejects(1, 'myField-2', 'Rejects a non-Slime ally')
    ->rejects(1, 'myField-0', 'Rejects the champion (not a Slime ally)')
    ->rejects(1, 'theirField-1', 'Rejects the opponent\'s Slime ally: the target must be one YOU control')
    ->target('myField-1')
    ->register();

// Negative (condition): with no OTHER Slime ally under your control the ability cannot be activated at all, so [REST] is not paid.
// Notes carried over from the original definition:
//   - WATER unlock
//   - Dungeon Guide: an ally, but not a Slime -> p1 field-1
//   - Baby Blue Slime -> myHand-7, enters at p1 field-2
//   - opponent's Slime: never a legal target
//   - try to activate with no other Slime ally of mine
gaScenario('baby-blue-slime-rest-needs-another-slime-ally-to-activate')
    ->deck($kspDeck)
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Baby Blue Slime')
    ->seed('myField', 'Red Slime', player: 2)
    ->play('Baby Blue Slime')->pay(2)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')
    ->register();

// Negative (cost): [REST] needs an awake ally -- once rested, Baby Blue Slime cannot activate again this turn.
// Notes carried over from the original definition:
//   - WATER unlock
//   - Red Slime -> p1 field-1
//   - second Red Slime -> p1 field-2 (a second legal target exists)
//   - Baby Blue Slime -> myHand-7, enters at p1 field-3
//   - first activation
//   - protect the first Red Slime
//   - second attempt while rested: nothing happens
gaScenario('baby-blue-slime-rested-cannot-activate-again')
    ->deck($kspDeck)
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myField', 'Red Slime')
    ->seed('myField', 'Red Slime')
    ->seed('myHand', 'Baby Blue Slime')
    ->play('Baby Blue Slime')->pay(2)
    ->action(1, 10001, 'myField-3!CustomInput!Activate:0')->target('myField-1')
    ->action(1, 10001, 'myField-3!CustomInput!Activate:0')
    ->register();

// Static: [Class Bonus] +1 LIFE applies with a TAMER champion ...
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER) + Nico (WATER unlock)
//   - Baby Blue Slime -> myHand-7, enters at p1 field-1
//   - Dungeon Guide with 1 buff counter (POWER 2) -> p2 field-1
//   - player 2 attacks with the 2-power Dungeon Guide
//   - ... targeting Baby Blue Slime (LIFE 3 with the Class Bonus)
//   - decline Baby Blue Slime's Retaliate
gaScenario('baby-blue-slime-class-bonus-tamer-champion-plus-one-life')
    ->deck($kspDeck)
    ->champion(cardName: 'Silvie, With the Pack', lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myHand', 'Baby Blue Slime')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 1]])
    ->play('Baby Blue Slime')->pay(2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->pass(1)
    ->register();

// ... and does NOT apply when the champion's class does not match (negative).
// Notes carried over from the original definition:
//   - Spirit of Fire (SPIRIT champion, not TAMER) + Nico lineage for WATER
//   - Baby Blue Slime -> myHand-7, enters at p1 field-1
//   - Dungeon Guide with 1 buff counter (POWER 2) -> p2 field-1
//   - player 2 attacks with the 2-power Dungeon Guide
//   - ... targeting Baby Blue Slime (printed LIFE 2, no Class Bonus)
//   - decline Baby Blue Slime's Retaliate
gaScenario('baby-blue-slime-no-class-bonus-with-non-tamer-champion')
    ->deck($kspDeck)
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myHand', 'Baby Blue Slime')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 1]])
    ->play('Baby Blue Slime')->pay(2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->pass(1)
    ->register();

// --- Bolster Ranks: [Class Bonus] makes it cost exactly 2 (printed 3 - 1); summons a Drone with a buff counter ---
// Bolster Ranks (n0esog2898, GUARDIAN ACTION, printed reserve 3): "[Class Bonus] This card costs 1
// less to activate. Choose one -- Summon an Automaton Drone token with a buff counter on it; or put
// a buff counter on each ally you control." Same double-Class-Bonus-discount engine bug as Shifting
// Mirage (generated activationCostModifierAbilities['n0esog2898:0'] AND the hand-maintained
// ClassBonusActivateCostReduction() table both applied it, so the card cost 1 instead of 2). The
// champion is patched to Tonoris, Creation's Will (NEOS + GUARDIAN) so the NEOS element and the Class Bonus are active; the card
// is played for real and needs exactly TWO reserve payments, then the YES branch summons a Drone
// with exactly one buff counter.
// Notes carried over from the original definition:
//   - Tonoris, Creation's Will (NEOS/GUARDIAN, Tonoris lineage): enables the NEOS element and the Class Bonus
//   - Bolster Ranks -> p1 myHand-7
//   - play Bolster Ranks from hand
//   - mode: summon an Automaton Drone token with a buff counter
//   - decline Tonoris, Creation's Will's own optional Drone replacement prompt
gaScenario('bolster-ranks-class-bonus-costs-two-summons-buffed-drone')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tonoris, Creation\'s Will', lineage: ['Tonoris, Might of Humanity', 'Tonoris, Lone Mercenary', 'Spirit of Wind'])
    ->seed('myHand', 'Bolster Ranks')
    ->play('Bolster Ranks')->pay(2)->answer(1, 'YES')->answer(1, 'NO')
    ->expectZoneCount('myGraveyard', 0, 1, 'After ONE payment Bolster Ranks has NOT resolved: printed 3 minus a single Class Bonus discount is 2, not 1', step: 2)
    ->expectZoneCount('myMemory', 2, 1, 'Exactly TWO reserve cards were paid: printed 3 minus the one [Class Bonus] discount', step: 3)
    ->expectProperty('p1Graveyard-0', 'CardID', 'n0esog2898', 'Bolster Ranks resolved only after the second payment', step: 3)
    ->expectZoneCount('myHand', 5, 1, 'Hand 8 - the played card - 2 reserve payments = 5')
    ->expectProperty('p1Field-1', 'CardID', 'mu6gvnta6q', 'Summoned an Automaton Drone token')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Drone has exactly one buff counter')
    ->expectDqEmpty()
    ->register();

// --- Bolster Ranks: negative case -- WITHOUT the Class Bonus it costs its full printed 3; buffs each ally ---
// Bolster Ranks (n0esog2898, printed reserve 3): the champion is the level-0 Spirit of Wind (class
// SPIRIT, not GUARDIAN) carrying a Tonoris, Creation's Will lineage card, so the NEOS card is
// playable but the Class Bonus is inactive and no discount applies: all THREE reserve cards are paid. A Dungeon Guide is
// seeded as the only ally and the NO mode puts exactly one buff counter on it (no Drone is summoned).
// Notes carried over from the original definition:
//   - Spirit of Wind (class SPIRIT, NOT GUARDIAN) with a Tonoris, Creation's Will lineage card: NEOS is enabled (card playable) but the Class Bonus is inactive
//   - Dungeon Guide (ally) -> p1 field-1
//   - Bolster Ranks -> p1 myHand-7
//   - play Bolster Ranks from hand
//   - mode: put a buff counter on each ally you control
gaScenario('bolster-ranks-no-class-bonus-costs-printed-three-buffs-allies')
    ->deck($tristanDeck)
    ->champion(cardName: 'Spirit of Wind', lineage: ['Tonoris, Creation\'s Will'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Bolster Ranks')
    ->play('Bolster Ranks')->pay(3)->answer(1, 'NO')
    ->expectZoneCount('myMemory', 2, 1, 'After TWO payments the card still has not resolved without the Class Bonus (cost stays at the printed 3)', step: 3)
    ->expectZoneCount('myGraveyard', 0, 1, 'Bolster Ranks is still unresolved after two payments (no discount without the Class Bonus)', step: 3)
    ->expectZoneCount('myMemory', 3, 1, 'Exactly THREE reserve cards were paid: the full printed cost with the Class Bonus inactive', step: 4)
    ->expectProperty('p1Graveyard-0', 'CardID', 'n0esog2898', 'Bolster Ranks resolved after the third payment', step: 4)
    ->expectZoneCount('myHand', 4, 1, 'Hand 8 - the played card - 3 reserve payments = 4')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Dungeon Guide ally gets exactly one buff counter')
    ->expectZoneCount('myField', 2, 1, 'No Automaton Drone was summoned (champion + the Dungeon Guide only)')
    ->expectDqEmpty()
    ->register();

// --- Betraying Blade: On Attack, may pay (2) to put a preparation counter on your champion (paid) ---
// Betraying Blade (qwxvzfkpaj, WIND ATTACK, reserve 2, power 2): "On Attack: You may pay (2). If
// you do, put a preparation counter on your champion." Player 1 ends turn 1 (first-player attack
// lock), then player 2 plays Betraying Blade from hand for real (two reserve payments), picks
// player 1's champion as the target, and the On Attack trigger (onAttackAbilities['qwxvzfkpaj:0']
// -> customDQHandlers['qwxvzfkpaj:0:OnAttack-1']) asks player 2 whether to pay (2); player 2 holds
// 7+ cards. Player 2's champion is the Material-section Spirit of Wind, so WIND needs no lineage
// patch. Answering YES queues two ReserveCard payments (each moves a hand card to memory) and adds
// the preparation counter to the attacker's champion; the combat damage (2) still lands on player
// 1's champion.
// Notes carried over from the original definition:
//   - Betraying Blade (WIND attack) -> p2 myHand-7
//   - end player 1 turn 1
//   - player 2 plays Betraying Blade
//   - attack player 1's champion
//   - pay (2) for the On Attack
//   - On Attack payment 1/2
//   - On Attack payment 2/2
gaScenario('betraying-blade-on-attack-pay-two-prep-counter')
    ->deck($tristanDeck)
    ->seed('myHand', 'Betraying Blade', player: 2)
    ->endTurn()
    ->play('Betraying Blade', 2)->pay(2, 2)->answer(2, 'theirField-0')->answer(2, 'YES')->pay(2, 2)
    ->expectProperty('p2Field-0', 'Counters', '{"preparation":1,"_champCombatDamageDealtThisTurn":2}', 'Paying (2) put exactly one preparation counter on the attacking champion (the other counter is the engine\'s combat-damage bookkeeping)')
    ->expectZoneCount('theirMemory', 4, 1, 'Four hand cards reached memory: 2 for the reserve cost of Betraying Blade plus the 2 paid for the On Attack')
    ->expectProperty('p2Graveyard-0', 'CardID', 'qwxvzfkpaj', 'Betraying Blade resolved and went to the graveyard')
    ->expectDamage('p1Field-0', 2, 'The 2-POWER attack still hit player 1\'s champion')
    ->expectDqEmpty()
    ->register();

// --- Betraying Blade: the On Attack payment is optional (declined) ---
// Betraying Blade (qwxvzfkpaj, WIND ATTACK, reserve 2, power 2): "On Attack: You may pay (2). If
// you do, put a preparation counter on your champion." Player 1 ends turn 1 (first-player attack
// lock), then player 2 plays Betraying Blade from hand for real (two reserve payments), picks
// player 1's champion as the target, and the On Attack trigger (onAttackAbilities['qwxvzfkpaj:0']
// -> customDQHandlers['qwxvzfkpaj:0:OnAttack-1']) asks player 2 whether to pay (2); player 2 holds
// 7+ cards. Player 2's champion is the Material-section Spirit of Wind, so WIND needs no lineage
// patch. Answering NO pays nothing and puts no preparation counter on the champion.
// Notes carried over from the original definition:
//   - Betraying Blade (WIND attack) -> p2 myHand-7
//   - end player 1 turn 1
//   - player 2 plays Betraying Blade
//   - attack player 1's champion
//   - decline to pay (2)
gaScenario('betraying-blade-on-attack-decline-no-counter')
    ->deck($tristanDeck)
    ->seed('myHand', 'Betraying Blade', player: 2)
    ->endTurn()
    ->play('Betraying Blade', 2)->pay(2, 2)->answer(2, 'theirField-0')->answer(2, 'NO')
    ->expectProperty('p2Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":2}', 'Declining puts no preparation counter (only the combat-damage bookkeeping counter exists)')
    ->expectZoneCount('theirMemory', 2, 1, 'Only the 2 reserve payments for Betraying Blade itself reached memory: nothing extra was paid')
    ->expectDamage('p1Field-0', 2, 'The 2-POWER attack still hit player 1\'s champion')
    ->expectDqEmpty()
    ->register();

// --- Betraying Blade: [Class Bonus] Floating Memory pays 1 of a champion level-up's memory cost from the graveyard ---
// Betraying Blade (qwxvzfkpaj): "[Class Bonus] Floating Memory (While paying for a memory cost,
// you may banish this card from your graveyard to pay for 1 of that cost.)" The champion is
// patched to Tristan, Underhanded (ASSASSIN) so the Class Bonus holds; Betraying Blade is seeded
// in the graveyard with one real memory card. The real level-up into Tristan, Hired Blade (memory
// cost 2) offers the floating payment (QueueMaterializeFloatingPaymentChoice): picking the
// graveyard card banishes it for 1 and the remaining point is paid by banishing the memory card.
// Notes carried over from the original definition:
//   - Tristan, Underhanded (ASSASSIN) -> Class Bonus active; level-up into Hired Blade has memory cost 2
//   - Betraying Blade in the graveyard (floating memory source)
//   - one real memory card; the second point is floated
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - start the level-up into Tristan, Hired Blade (memory cost 2)
//   - float Betraying Blade from the graveyard for 1 of the cost
gaScenario('betraying-blade-class-bonus-floating-memory-levelup')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Underhanded', lineage: ['Spirit of Wind'])
    ->seed('myGraveyard', 'Betraying Blade')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-1')->target('myGraveyard-0')
    ->tested('Betraying Blade')
    ->expectProperty('p1Banish-0', 'CardID', 'qwxvzfkpaj', 'Betraying Blade was banished from the graveyard as a floating-memory payment')
    ->expectProperty('p1Banish-1', 'CardID', 'n8wyfG9hbY', 'The remaining memory point was paid with the real memory card')
    ->expectZoneCount('myGraveyard', 0, 1, 'The graveyard is empty: Betraying Blade left it')
    ->expectProperty('p1Field-0', 'CardID', 'gt7lh9v221', 'The level-up into Tristan, Hired Blade completed with a 1+1 payment')
    ->register();

// --- Betraying Blade: Floating Memory is not offered without the Class Bonus ---
// Betraying Blade (qwxvzfkpaj): Floating Memory is a [Class Bonus] clause. With the starting
// champion Spirit of Wind (no Assassin class) the 1-point level-up into Tristan, Underhanded is
// paid directly from memory without ever offering the graveyard Betraying Blade: the Blade stays
// in the graveyard and the memory card is the one banished. (The same level-up with an Assassin
// champion offers the floating payment: see betraying-blade-class-bonus-floating-memory-levelup.)
// Notes carried over from the original definition:
//   - Betraying Blade in the graveyard; the champion (Spirit of Wind) is NOT an Assassin
//   - memory card
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up Spirit of Wind into Tristan, Underhanded (memory cost 1): paid straight from memory
//   - answer Underhanded's On Enter (counter)
gaScenario('betraying-blade-floating-memory-requires-class-bonus')
    ->deck($tristanDeck)
    ->seed('myGraveyard', 'Betraying Blade')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'YES')
    ->tested('Betraying Blade')
    ->expectProperty('p1Graveyard-0', 'CardID', 'qwxvzfkpaj', 'Betraying Blade stays in the graveyard: no floating payment was offered without the Class Bonus')
    ->expectZoneCount('myGraveyard', 1, 1, 'Exactly one card remains in the graveyard')
    ->expectProperty('p1Banish-0', 'CardID', 'n8wyfG9hbY', 'The 1-point memory cost was paid with the real memory card')
    ->expectZoneCount('myBanish', 1, 1, 'Only the memory card was banished')
    ->expectProperty('p1Field-0', 'CardID', 'bjlwabipl6', 'The level-up into Tristan, Underhanded completed')
    ->register();

// ignoreCost activation: the affordability gate is skipped. Bertha, Spry Howitzer (ki6fxxgmue): "[Class Bonus][Level 2+]
// On Enter: look at the top 5 cards of your deck; you may activate a Ranger action with reserve cost 2 or less from among
// them without paying its costs." Bertha is played for real (3 payments empty the hand), then Backstep (reserve 2) is
// activated for free with ZERO cards left in hand -- a plain activation would be unaffordable.
// Notes carried over from the original definition:
//   - Diana, Deadly Duelist (level 2 RANGER): Class Bonus + Level 2+
//   - Backstep (Ranger action, reserve 2) on top of the deck
//   - Bertha, Spry Howitzer -> p1 myHand-0
//   - 1: play Bertha
//   - 5: On Enter: activate Backstep (the former top card of the deck) for free
//   - 6: keep the default order of the other four cards on the bottom of the deck; Backstep is then activated without paying its cost
//   - 7: Backstep's target: your champion
gaScenario('ignore-cost-free-activation-with-empty-hand-is-not-refused')
    ->deck($mordredDeck)
    ->champion(cardName: 'Diana, Deadly Duelist', lineage: ['Spirit of Water'])
    ->deckTop(['Backstep'])
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Bertha, Spry Howitzer')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)->pay(3)->target('myTempZone-4')->answer(1, 'Bottom=px60u5n1do,px60u5n1do,px60u5n1do,em6eEh9q8y')
    ->target('myField-0')
    ->tested('Bertha, Spry Howitzer', 'Backstep')
    ->register();

// Broken Promises (re911j7fo4, FIRE skill, reserve 1): "As an additional cost, sacrifice a Fatestone item or Fatebound ally."
// Nothing of the kind is on the field. The champion is patched to a FIRE champion so the element is usable.
// Notes carried over from the original definition:
//   - Polkhawk, Boisterous Riot (FIRE)
//   - Broken Promises -> p1 myHand-0
//   - 1: click Broken Promises with no Fatestone item / Fatebound ally to sacrifice: accepted as a no-op, refusal asserted on state
gaScenario('broken-promises-no-fatestone-or-fatebound-click-refused')
    ->deck($mordredDeck)
    ->champion(cardName: 'Polkhawk, Boisterous Riot', lineage: ['Spirit of Water'])
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Broken Promises')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)
    ->tested('Broken Promises')
    ->register();

// --- Briar, Schwartz King: printed Spellshroud -- an opposing spell cannot target it ---
// Briar, Schwartz King (r1zd9ys1qc, UNIQUE,ALLY): "Hindered, Spellshroud, Stealth" -- the Spellshroud is the
// card's own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords >
// Spellshroud 1: "This can't be targeted by spells"). It was never implemented: HasSpellshroud()
// (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID list and
// HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword,
// so function_exists is false); r1zd9ys1qc was in neither, so FilterSpellshroudTargets() kept the card in the
// target list. Fixed: it is now in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities /
// NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff counter
// on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, answering with the
// holder is refused (expectFailure) and the legal answer then resolves.
// Notes carried over from the original definition:
//   - Briar, Schwartz King -> p2 field-1
//   - Dungeon Guide -> p2 field-2
//   - Liturgy of Corruption -> p1 myHand-7
//   - player 1 plays Liturgy of Corruption
//   - the Dungeon Guide is the legal target
gaScenario('briar-schwartz-king-printed-spellshroud-spell-cannot-target-it')
    ->deck($mordredDeck)
    ->seed('myField', 'Briar, Schwartz King', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Liturgy of Corruption')
    ->play('Liturgy of Corruption')->pay(2)
    ->rejects(1, 'theirField-1', 'Briar, Schwartz King (r1zd9ys1qc) has spellshroud: not a legal target for the opposing Liturgy of Corruption')
    ->target('theirField-2')
    ->tested('Briar, Schwartz King')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answers left Briar, Schwartz King (r1zd9ys1qc) untouched: Spellshroud stops the opposing Liturgy of Corruption from targeting it', step: 4)
    ->expectProperty('p2Field-2', 'Counters', '[]', 'The refused answers did not apply Liturgy of Corruption to the Dungeon Guide either (prompt still pending)', step: 4)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'Briar, Schwartz King (r1zd9ys1qc) is unaffected by the resolved Liturgy of Corruption')
    ->expectProperty('p2Field-2', 'Counters', '{"buff":1}', 'The only legal target, Dungeon Guide, received the buff counter')
    ->expectProperty('p1Graveyard-0', 'CardID', 'BZMKfFZ22T', 'Liturgy of Corruption resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Byakko, White Tiger: printed Spellshroud -- an opposing spell cannot target it ---
// Byakko, White Tiger (kkkcxq93ul, mhilppwfgx, ALLY): "Spellshroud, Vigor" -- the Spellshroud is the card's
// own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords >
// Spellshroud 1: "This can't be targeted by spells"). It was never implemented: HasSpellshroud()
// (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID list and
// HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword,
// so function_exists is false); these IDs were in neither, so FilterSpellshroudTargets() kept the card in the
// target list. Fixed: they are now in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities /
// NO_SPELLSHROUD gates).
// Player 2 controls the holders plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff
// counter on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, answering with
// each holder is refused (expectFailure) and the legal answer then resolves.
// Notes carried over from the original definition:
//   - Byakko, White Tiger -> p2 field-1
//   - Byakko, White Tiger -> p2 field-2
//   - Dungeon Guide -> p2 field-3
//   - Liturgy of Corruption -> p1 myHand-7
//   - player 1 plays Liturgy of Corruption
//   - the Dungeon Guide is the legal target
gaScenario('byakko-white-tiger-printed-spellshroud-spell-cannot-target-it')
    ->deck($mordredDeck)
    ->seed('myField', 'Byakko, White Tiger', player: 2)
    ->seed('myField', '#mhilppwfgx', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Liturgy of Corruption')
    ->play('Liturgy of Corruption')->pay(2)
    ->rejects(1, 'theirField-1', 'Byakko, White Tiger (kkkcxq93ul) has spellshroud: not a legal target for the opposing Liturgy of Corruption')
    ->rejects(1, 'theirField-2', 'Byakko, White Tiger (mhilppwfgx) has spellshroud: not a legal target for the opposing Liturgy of Corruption')
    ->target('theirField-3')
    ->tested('Byakko, White Tiger', '#mhilppwfgx')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answers left Byakko, White Tiger (kkkcxq93ul) untouched: Spellshroud stops the opposing Liturgy of Corruption from targeting it', step: 5)
    ->expectProperty('p2Field-2', 'Counters', '[]', 'The refused answers left Byakko, White Tiger (mhilppwfgx) untouched: Spellshroud stops the opposing Liturgy of Corruption from targeting it', step: 5)
    ->expectProperty('p2Field-3', 'Counters', '[]', 'The refused answers did not apply Liturgy of Corruption to the Dungeon Guide either (prompt still pending)', step: 5)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'Byakko, White Tiger (kkkcxq93ul) is unaffected by the resolved Liturgy of Corruption')
    ->expectProperty('p2Field-2', 'Counters', '[]', 'Byakko, White Tiger (mhilppwfgx) is unaffected by the resolved Liturgy of Corruption')
    ->expectProperty('p2Field-3', 'Counters', '{"buff":1}', 'The only legal target, Dungeon Guide, received the buff counter')
    ->expectProperty('p1Graveyard-0', 'CardID', 'BZMKfFZ22T', 'Liturgy of Corruption resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// Ownership: the controller controls an ally OWNED by the opponent. "Returns it to its OWNER's memory": it goes to the OPPONENT's memory.
// Notes carried over from the original definition:
//   - Dungeon Guide controlled by player 1 but OWNED by player 2 -> p1 field-1
//   - Bygone Days -> p1 myHand-7
//   - player 1 chooses the ally it controls (owned by player 2)
gaScenario('bygone-days-p1-controlled-returns-opponent-owned-ally-to-owner-memory')
    ->deck($gaBygoneDeck)
    ->seed('myField', 'Dungeon Guide', properties: ['Owner' => 2])
    ->seed('myHand', 'Bygone Days')
    ->play('Bygone Days')->pay(2)
    ->endTurn()
    ->endTurn(2)
    ->endTurn()
    ->endTurn(2)
    ->endTurn()
    ->endTurn(2)->target('myField-1')
    ->register();

// Slot order: the controller's ally sits AFTER Bygone Days on the field, so sacrificing Bygone Days shifts it down a slot.
// Notes carried over from the original definition:
//   - Fluffy Shopkeep -> p1 myHand-7
//   - Bygone Days -> p1 myHand-8
//   - the Fluffy Shopkeep entered AFTER Bygone Days (field-2); Bygone Days is sacrificed first so it ends up at field-1
gaScenario('bygone-days-p1-controlled-ally-after-bygone-days-slot-shifts-on-sacrifice')
    ->deck($gaBygoneDeck)
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Bygone Days')
    ->play('Bygone Days')->pay(2)
    ->playSlot(5)->pay(3)
    ->endTurn()
    ->endTurn(2)
    ->endTurn()
    ->endTurn(2)
    ->endTurn()
    ->endTurn(2)->target('myField-2')
    ->register();

// opponent: 7 cards in hand + 2 in memory = influence 9
gaScenario('blinding-lapse-opponent-influence-nine-hand-to-memory-banishes-three')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Zander, Blinding Steel')
    ->seed('myHand', 'Blinding Lapse')
    ->emptyZone('myHand', player: 2, destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->play('Blinding Lapse')->pay(3)
    ->expectZoneCount('theirHand', 0, 1, 'The opponent\'s whole hand was put into their memory')
    ->expectZoneCount('theirMemory', 6, 1, '7 hand cards + 2 memory cards = 9, minus three banished at random')
    ->expectZoneCount('theirBanish', 3, 1, 'Exactly three cards were banished from the opponent\'s memory')
    ->expectZoneCount('myMemory', 3, 1, 'The three reserve payments')
    ->expectDqEmpty()
    ->register();

// opponent: 7 cards in hand + 1 in memory = influence 8 (< 9)
gaScenario('blinding-lapse-opponent-influence-eight-does-nothing')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Zander, Blinding Steel')
    ->seed('myHand', 'Blinding Lapse')
    ->emptyZone('myHand', player: 2, destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->play('Blinding Lapse')->pay(3)
    ->expectZoneCount('theirHand', 7, 1, 'Hand untouched: the opponent\'s influence (8) is below nine')
    ->expectZoneCount('theirMemory', 1, 1, 'Memory untouched')
    ->expectZoneCount('theirBanish', 0, 1, 'Nothing banished')
    ->expectDqEmpty()
    ->register();

gaScenario('bertha-look-five-decline-free-activation-all-five-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Deadly Duelist', level: 2)
    ->seed('myHand', 'Bertha, Spry Howitzer')
    ->deckTop(['Hightail', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep', 'Charge the Soul'])
    ->play('Bertha, Spry Howitzer')->pay(3)->pass(1)
    ->answer(1, 'Bottom=Xy8loj55gJ,em6eEh9q8y,px60u5n1do,em6eEh9q8y,px60u5n1do')
    ->register();

// Notes carried over from the original definition:
//   - Brewing Kit -> myField-1
//   - Herb -> myField-2
//   - Herb -> myField-3
//   - Herb -> myField-4
//   - sacrifice the three herbs (the field shifts down after each)
//   - "You may reveal a Potion": decline
//   - nobody responds to the resolved ability
gaScenario('brewing-kit-look-six-decline-potion-all-six-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Brewing Kit')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Blightroot')
    ->deckTop(['Distilled Atrophy', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Charge the Soul'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-2')->target('myField-2')
    ->target('myField-2')->pass(1)->passBoth()
    ->tested('Brewing Kit')
    ->register();

// Burning Aethercharge (vrK16VZ2zU): "Deal 2 damage to target champion. Then you may load CARDNAME into an Aetherwing weapon you control."
// Notes carried over from the original definition:
//   - RANGER class
//   - Burning Aethercharge -> myHand-7
gaScenario('burning-aethercharge-deals-two-damage-to-target-champion')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'RANGER')
    ->seed('myHand', 'Burning Aethercharge')
    ->play('Burning Aethercharge')->pay(2)->target('theirField-0')
    ->register();

// Bellona's Runestone (clgolelsra): "Banish Bellona's Runestone: Target weapon you control gets +2 POWER until end of turn. Put a durability counter on that weapon."
// Notes carried over from the original definition:
//   - Bellona's Runestone -> myField-1
//   - Sojourner's Hunt (a weapon) -> myField-2
gaScenario('bellonas-runestone-banish-buffs-a-weapon-and-adds-a-durability-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Bellona\'s Runestone')
    ->seed('myField', 'Sojourner\'s Hunt')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-1')
    ->tested('Bellona\'s Runestone')
    ->register();

// Notes carried over from the original definition:
//   - Battery Core X -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('battery-core-x-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'WARRIOR'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Battery Core X')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-1')->pass(1)
    ->tested('Battery Core X')
    ->expectZoneCount('myHand', 13, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
    ->expectZoneCount('myMemory', 2, 1, 'Memory after the reserve payments / draws into memory')
    ->expectZoneCount('myBanish', 1, 1, 'Banishment after the printed banish costs')
    ->expectZoneCount('myGraveyard', 3, 1, 'Graveyard after the printed sacrifice / banish costs')
    ->expectZoneCount('myField', 6, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost')
    ->register();

// Notes carried over from the original definition:
//   - Bauble of Scarcity -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('bauble-of-scarcity-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'CLERIC'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Bauble of Scarcity')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)->pass(1)->pay(1, 2)
    ->tested('Bauble of Scarcity')
    ->expectZoneCount('myHand', 12, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
    ->expectZoneCount('myMemory', 2, 1, 'Memory after the reserve payments / draws into memory')
    ->expectZoneCount('myBanish', 1, 1, 'Banishment after the printed banish costs')
    ->expectZoneCount('myGraveyard', 4, 1, 'Graveyard after the printed sacrifice / banish costs')
    ->expectZoneCount('myField', 6, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost')
    ->register();

// Notes carried over from the original definition:
//   - Beastbond Claws -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('beastbond-claws-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'TAMER'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Beastbond Claws')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-2')->pass(1)
    ->tested('Beastbond Claws')
    ->expectZoneCount('myHand', 13, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
    ->expectZoneCount('myMemory', 2, 1, 'Memory after the reserve payments / draws into memory')
    ->expectZoneCount('myBanish', 1, 1, 'Banishment after the printed banish costs')
    ->expectZoneCount('myGraveyard', 3, 1, 'Graveyard after the printed sacrifice / banish costs')
    ->expectZoneCount('myField', 6, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost')
    ->register();

// Notes carried over from the original definition:
//   - Band of Burning Verdict -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('band-of-burning-verdict-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'TAMER'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Band of Burning Verdict')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-3')->pass(1)
    ->tested('Band of Burning Verdict')
    ->register();

// Notes carried over from the original definition:
//   - Bise Blade -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('bise-blade-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'nn48ne8a05', 'Counters' => ['_overrides' => ['classes' => 'GUARDIAN'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Bise Blade')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(6)->pass(1)
    ->tested('Bise Blade')
    ->expectZoneCount('myHand', 7, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
    ->expectZoneCount('myMemory', 8, 1, 'Memory after the reserve payments / draws into memory')
    ->expectZoneCount('myBanish', 1, 1, 'Banishment after the printed banish costs')
    ->expectZoneCount('myGraveyard', 3, 1, 'Graveyard after the printed sacrifice / banish costs')
    ->expectZoneCount('myField', 7, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost')
    ->register();

// Notes carried over from the original definition:
//   - Bifurcating Fractal -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('bifurcating-fractal-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'CLERIC,MAGE'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Bifurcating Fractal')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Bifurcating Fractal')
    ->expectZoneCount('myHand', 10, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
    ->expectZoneCount('myMemory', 5, 1, 'Memory after the reserve payments / draws into memory')
    ->expectZoneCount('myBanish', 0, 1, 'Banishment after the printed banish costs')
    ->expectZoneCount('myGraveyard', 4, 1, 'Graveyard after the printed sacrifice / banish costs')
    ->expectZoneCount('myField', 8, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost')
    ->register();

// --- Blinding Orb (qYH9PJP7uM): "Banish CARDNAME: Each opponent puts two cards from their hand into their memory. Class Bonus: Draw a card." The second handler moved the opponent's cards for an undefined
// player, so nothing reached the opponent's memory. The champion is an Assassin here (class override) for the Class Bonus draw. ---
// Notes carried over from the original definition:
//   - Blinding Orb -> myField-1
gaScenario('blinding-orb-opponent-puts-two-cards-into-memory-class-bonus-draws')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'ASSASSIN')
    ->seed('myField', 'Blinding Orb')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2, 2)
    ->tested('Blinding Orb')
    ->expectZoneCount('theirMemory', 2, 1, 'The opponent put two cards from their hand into their memory')
    ->expectZoneCount('theirHand', 5, 1, 'The opponent\'s hand shrank by two')
    ->expectZoneCount('myHand', 8, 1, 'Class Bonus (Assassin champion): the activating player drew a card (7 + 1)')
    ->expectZoneCount('myField', 1, 1, 'Blinding Orb banished itself as the cost (only the champion remains)')
    ->expectDqEmpty()
    ->register();

// Bauble of Scarcity (24ansclpqc): "Banish CARDNAME: Each player discards a card." The opponent's choice list was built from the ACTIVATING player's hand, so the opponent's discard prompt offered the wrong hand.
gaScenario('bauble-of-scarcity-makes-each-player-discard-a-card')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Bauble of Scarcity')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myHand-3')->answer(2, 'myHand-6')->pass(1)
    ->tested('Bauble of Scarcity')
    ->register();
