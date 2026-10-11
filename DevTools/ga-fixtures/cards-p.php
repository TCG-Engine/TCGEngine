<?php
/**
 * Fixtures for cards starting with 'P', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Penetrator Round: load into an unloaded Gun weapon ---
// Penetrator Round's element is FIRE (matching the starting champion), so no lineage patch is
// needed, and its memory cost is 0 so materializing via a direct FSM click is free. Framework
// Sidearm (a REGALIA,WEAPON with the GUN subtype and no Subcards, i.e. unloaded) is seeded
// onto the field as the [REST] Load ability's target. Only the always-available Load half is
// covered; the [Class Bonus][Level 2+] On Attack "unpreventable" trigger requires a real
// attack and is out of scope.
// Notes carried over from the original definition:
//   - Framework Sidearm (unloaded GUN weapon) - Load target
//   - the Round materialized to myField-2 (the Sidearm is myField-1)
gaScenario('penetrator-round-load-gun')
    ->standardDeck('Penetrator Round')
    ->seed('myField', 'Framework Sidearm')
    ->seed('myHand', 'Penetrator Round')
    ->playSlot(6)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Penetrator Round')
    ->expectProperty('p1Field-1', 'Subcards', '["97n2jnltv5"]', 'Loading appends the bullet\'s CardID to the target Gun weapon\'s Subcards')
    ->expectZoneCount('myField', 2, 1, 'The bullet is removed from the field entirely, not moved to another zone, once loaded')
    ->expectDqEmpty()
    ->register();

// --- Potion Infusion: Animate: Arisanna Bonus discount + turn a Potion into an ally ---
// Potion Infusion: Animate's element is NORM, so no lineage patch is needed, but its discount
// requires the Arisanna Bonus, which (unlike the physical-presence Class Bonus checks above)
// is lineage-based (ChampionHasInLineage), so the starting champion's Subcards are patched
// with a real Arisanna champion (Arisanna, Herbalist Prodigy). Distilled Water (an ITEM,
// CLERIC,POTION with reserve cost 0) is seeded onto the field as the target; animating it sets
// Counters potion_animate/potion_animate_power/potion_animate_life (all 0, since Distilled
// Water's own reserve cost is 0) and adds ALLY to its effective type via
// ApplyPersistentOverride.
// Notes carried over from the original definition:
//   - Arisanna Bonus lineage unlock
//   - Distilled Water (ITEM, CLERIC,POTION) - Animate target
gaScenario('potion-infusion-animate-turn-ally')
    ->standardDeck('Potion Infusion: Animate')
    ->champion(lineage: ['Arisanna, Herbalist Prodigy'])
    ->seed('myField', 'Distilled Water')
    ->seed('myHand', 'Potion Infusion: Animate')
    ->castSpell('Potion Infusion: Animate', pay: 2, window: 'both', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'Animating rests the targeted Potion')
    ->expectProperty('p1Field-1', 'Counters', '{"_overrides":{"type":"ITEM,ALLY"},"potion_animate":1,"potion_animate_power":0,"potion_animate_life":0}', 'Animating adds ALLY to the Potion\'s effective type and sets its power/life counters equal to its 0 reserve cost')
    ->expectZoneCount('myMemory', 2, 1, 'The Arisanna Bonus discount reduces the 4-reserve cost to 2, so only 2 reserve payments are made')
    ->expectDqEmpty()
    ->register();

// --- Pendant of Accrual: REST, remove 2 debt counters: draw into memory ---
// Pendant of Accrual's element is NORM, so no lineage patch is needed, and its memory cost is
// NOT exercised for the same FSM-click-bypass reason as entrancing-filigree-enter-banish. It
// is seeded directly onto the field with 2 debt counters already present (normally only
// reachable via the opponent declining to pay at their recollection phase) so the ability's
// cost can actually be paid.
// Notes carried over from the original definition:
//   - Ability cost fuel
gaScenario('pendant-of-accrual-debt-draw')
    ->standardDeck('Pendant of Accrual')
    ->seed('myField', 'Pendant of Accrual', properties: ['Counters' => ['debt' => 2]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->passBoth()
    ->tested('Pendant of Accrual')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The 2 debt counters are spent as the ability\'s cost')
    ->expectZoneCount('myMemory', 1, 1, 'Activating draws a card into memory')
    ->expectDqEmpty()
    ->register();

// --- Perfect Repulsion: prevent the next exact-X damage to a friendly unit ---
// Perfect Repulsion's element is WATER, so the starting champion's Subcards are patched with
// a real WATER champion (Spirit of Water, reused from coriolis-ward-prevent-level). X is the
// number of cards in memory at resolution time, which after 2 reserve payments is 2, so the
// shield prevents exactly 2 damage. Only the shield's creation is covered; the "draw a card if
// damage was prevented this way" clause is a separate deferred trigger that requires an actual
// damage event afterward and is out of scope.
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
gaScenario('perfect-repulsion-prevent-x')
    ->standardDeck('Perfect Repulsion')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myHand', 'Perfect Repulsion')
    ->castSpell('Perfect Repulsion', pay: 2, window: 'both', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","PREVENT_EXACT_2"]', 'The shield snapshots the two cards in memory as it resolves')
    ->expectDqEmpty()
    ->register();

// --- Potion Infusion: Volatility: rest target Potion and grant a Sacrifice trigger ---
// Potion Infusion: Volatility's element is NORM, so no lineage patch is needed. Distilled
// Water (an ITEM, CLERIC,POTION) is seeded onto the field as the target. Without the
// [Arisanna Bonus] Efficiency discount, the full 7-reserve cost is paid. Only the immediate
// "rest the target and grant it the On Sacrifice trigger" half is covered; the eventual
// 4+D6 damage roll requires actually sacrificing the target and is out of scope.
// Notes carried over from the original definition:
//   - Distilled Water (ITEM, CLERIC,POTION) - rest target
gaScenario('potion-infusion-volatility-rest')
    ->standardDeck('Potion Infusion: Volatility')
    ->seed('myField', 'Distilled Water')
    ->seed('myHand', 'Potion Infusion: Volatility')
    ->castSpell('Potion Infusion: Volatility', pay: 7, window: 'both', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The targeted Potion is rested')
    ->expectDqEmpty()
    ->register();

// --- Potion Infusion: Seal: rest target Potion you control ---
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
//   - Distilled Water (POTION) - rest target
gaScenario('potion-infusion-seal-rest')
    ->standardDeck('Potion Infusion: Seal')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myField', 'Distilled Water')
    ->seed('myHand', 'Potion Infusion: Seal')
    ->castSpell('Potion Infusion: Seal', pay: 2, window: 'both', target: 'myField-1')
    ->register();

// --- Potion Infusion: Frostbite: rest target Potion you control ---
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
//   - Distilled Water (POTION) - rest target
gaScenario('potion-infusion-frostbite-rest')
    ->standardDeck('Potion Infusion: Frostbite')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myField', 'Distilled Water')
    ->seed('myHand', 'Potion Infusion: Frostbite')
    ->castSpell('Potion Infusion: Frostbite', pay: 1, window: 'both', target: 'myField-1')
    ->register();

// --- Package Courier: On Enter, may discard a card to draw a card ---
gaScenario('package-courier-discard-draw')
    ->standardDeck('Package Courier')
    ->seed('myHand', 'Package Courier')
    ->castSpell('Package Courier', pay: 3)
    ->expectZoneCount('myGraveyard', 1, 1, 'Package Courier\'s optional discard puts a card in the graveyard')
    ->expectZoneCount('myHand', 5, 1, 'Discarding then drawing keeps hand size unchanged')
    ->expectDqEmpty()
    ->register();

// --- Ardent Cloudstriker: +3 POWER while facing West and attacking a champion ---
// --- Planar Abyss: Delayed - at beginning of next recollection phase, destroy all non-champion
// objects, then if SC South, deal 10 damage to each opponent champion ---
// Planar Abyss's own activation just sets a delayed PLANAR_ABYSS_PENDING global effect
// (cardActivatedAbilities['qexcwmx2ug:0'], GeneratedMacroCode.php ~20854-20860); the actual
// destroy-all + conditional-damage resolution happens inside RecollectionPhase() (GameLogic.php
// ~9676-9699) the next time this player's recollection phase begins. Setting the flag directly
// via the 'globalEffect' setup step (same technique as blistering-insurgent's LEVELED_UP_THIS_TURN)
// avoids scripting the card's own 12-reserve activation, which is out of scope for this fixture.
// Dungeon Guide (an ALLY) is seeded onto both fields as the "non-champion object" target; SC
// faces South so the champion-damage half is also exercised in the same fixture.
// Notes carried over from the original definition:
//   - Shifting Currents facing South
//   - Dungeon Guide (ALLY), P1's own non-champion object to be destroyed
//   - Dungeon Guide (ALLY), P2's non-champion object to be destroyed
//   - decline materialize offer, continue phase advance through recollection
gaScenario('planar-abyss-delayed-destroy-south-damage')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->globalEffect('PLANAR_ABYSS_PENDING')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'SOUTH'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Planar Abyss')
    ->register();

// --- Pierce the Heavens: [Jin Bonus] leveled up this turn -> +2 POWER, unblockable ---
// Pierce the Heavens' onAttackAbilities (CombatLogic.php:592) checks
// strpos(CardName($champ->CardID), "Jin") === 0 AND GlobalEffectCount($player,
// "LEVELED_UP_THIS_TURN") > 0, adding +2 POWER and UNBLOCKABLE TurnEffects. Rather than
// patching the champion directly (which would need a SEPARATE later level-up to set the
// LEVELED_UP_THIS_TURN flag, and patching+leveling in the same turn risks the "can't level to a
// card of equal/lower level" issue hit in savage-swing-class-bonus-floating-memory), the
// champion is kept as the default Spirit of Fire (level 0) and leveled up NATURALLY into Jin,
// Fate Defiant (level 1, 1-memory cost) during turn 1's MAIN phase -- this single real level-up
// both satisfies the "champion is Jin" precondition AND sets LEVELED_UP_THIS_TURN, in the same
// turn Pierce the Heavens is played and attacked with. Pierce the Heavens is NORM element
// (always castable regardless of champion element, unlike Wind Cutter's WIND -- no Fairy
// Whispers Opportunity-window cascade to work around here). Total attack power = champion's own
// power (0, Jin Fate Defiant has none) + intent card's boosted power (base 3 + 2 = 5, no weapon).
// Notes carried over from the original definition:
//   - filler memory card, Jin Fate Defiant's 1-memory level-up cost
//   - Pierce the Heavens
//   - level up into Jin, Fate Defiant
//   - pay the 1 memory
//   - target opponent's champion
//   - decline Retaliate
gaScenario('pierce-the-heavens-jin-bonus-leveled-up-power-unblockable')
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
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Pierce the Heavens')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myMemory-0')
    ->castSpell('Pierce the Heavens', pay: 3)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectProperty('myField-0', 'CardID', 'zd8l14052j', 'leveled up into Jin, Fate Defiant', step: 4)
    ->expectDamage('theirField-0', 5, '[Jin Bonus] Pierce the Heavens base POWER 3 + 2 (leveled up this turn) = 5')
    ->expectDqEmpty()
    ->register();

// Notes carried over from the original definition:
//   - Shadow's Twin, unloaded Gun, myField-1
//   - Plated Bullet, awake, myField-2
//   - decline own MAT-phase materialize offer
//   - Plated Bullet's [REST] ability
//   - choose Shadow's Twin as the unloaded Gun to load into
gaScenario('plated-bullet-rest-load-shadows-twin-power')
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
    ->seed('myField', 'Shadow\'s Twin')
    ->seed('myField', 'Plated Bullet')
    ->endTurn()
    ->endTurn(2)->pass(1)->pass(1)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Plated Bullet', 'Shadow\'s Twin')
    ->register();

// --- Prototype Pistol: [Class Bonus] On Enter: +1 POWER until end of turn ---
// Prototype Pistol is a REGALIA card. HandAddReplacement (GameLogic.php) silently redirects
// a REGALIA card into the material zone whenever it's added to hand via the BridgeAddToZone
// test-setup primitive -- but that hook only fires for that kind of explicit single-card
// add, not the normal bulk initial deal, so a REGALIA copy drawn into a real opening hand
// stays there normally. Player 2's natural 7-card opening hand happens to include one
// (confirmed at theirHand-0 from player 1's perspective, i.e. player 2's own myHand-0), so it
// can be played for real via the ordinary hand FSM flow once player 2 reaches their turn.
// Rule 1.h only blocks the opening player's own turn 1, so player 1 ends turn 1 with a single
// CustomInput Pass (no decision is pending, so this action itself ends the turn) and player 2
// plays it on turn 2, with a RANGER Class Bonus champion already seeded onto their field.
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (RANGER champion) - Class Bonus source
//   - ends turn 1
gaScenario('prototype-pistol-class-bonus-enter-power')
    ->standardDeck('Prototype Pistol')
    ->seed('myField', 'Diana, Keen Huntress', player: 2)
    ->endTurn()
    ->playSlot(0, 2)
    ->tested('Prototype Pistol')
    ->register();

// --- Powercharged Shield: Banish this -- target unit with taunt gains vigor ---
// Powercharged Shield's own activated ability requires a unit with taunt already on the
// field (activateAbilityPrereqs["rrii17fzcy:0"]), so a Dungeon Guide is patched with TAUNT
// directly via test-setup rather than scripting a real taunt-granting effect.
// Notes carried over from the original definition:
//   - Dungeon Guide w/ Taunt - ability target
//   - Powercharged Shield
gaScenario('powercharged-shield-banish-taunt-vigor')
    ->standardDeck('Powercharged Shield')
    ->seed('myField', 'Dungeon Guide', properties: ['TurnEffects' => ['TAUNT']])
    ->seed('myField', 'Powercharged Shield')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Powercharged Shield')
    ->register();

// --- Potion of Healing: Brew (Two Herbs); Sacrifice: Recover 5 ---
// Potion of Healing's element is NORM, so no lineage patch is needed. Brewing is an alternate
// cost (sacrifice two Herb-subtype objects instead of paying reserve); two Blightroot tokens
// are seeded onto the field to pay it. Sacrifice: Recover 5 is unconditional (no "if brewed"
// gate, unlike Distilled Water), so it's testable via a direct follow-up Activate:0 click once
// the brewed Potion has resolved onto the field (same shape as distilled-water-brew-sacrifice-
// draw's own trailing step). The champion is pre-damaged by 6 so Recover 5 leaves 1 damage
// remaining, distinguishing "recovered" from "already at full life."
// Notes carried over from the original definition:
//   - pre-damage champion
//   - Blightroot (HERB) #1 - brew ingredient
//   - Blightroot (HERB) #2 - brew ingredient
gaScenario('potion-of-healing-brew-sacrifice-recover')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(damage: 6)
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Potion of Healing')
    ->play('Potion of Healing')->answer(1, 'YES')->target('myField-1')->target('myField-2')->passBoth()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->register();

// --- Potion Infusion: Clarity: Rest target Potion, grant "On Sacrifice: Draw two cards" ---
// Notes carried over from the original definition:
//   - Nico, Rapture's Embrace -- unlocks WATER for Potion Infusion: Clarity
//   - pre-damage champion so Recover 5 (Potion of Healing's own Sacrifice) is observable
//   - Blightroot (HERB) #1 - brew ingredient
//   - Blightroot (HERB) #2 - brew ingredient
//   - Potion Infusion: Clarity
//   - Brew Potion of Healing (sacrifice both Blightroot tokens instead of paying reserve).
//   - Play Potion Infusion: Clarity, paying its full 7 reserve (no Class Bonus).
//   - Decline the standing fast-opportunity to Sacrifice the brewed Potion early -- Infusion
//   - must resolve first so the granted "On Sacrifice: Draw two" bonus is in place.
//   - Target the brewed Potion of Healing (myField-1) to rest it and grant the bonus.
//   - Activate the Potion's own Sacrifice ability -- triggers the granted "draw two" before it.
gaScenario('potion-infusion-clarity-rest-grant-draw-two')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Nico, Rapture\'s Embrace'])
    ->champion(damage: 6)
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Potion of Healing')
    ->seed('myHand', 'Potion Infusion: Clarity')
    ->play('Potion of Healing')->answer(1, 'YES')->target('myField-1')->target('myField-2')->passBoth()
    ->castSpell('Potion of Healing', pay: 7, window: 'me', target: 'myField-1')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Potion Infusion: Clarity')
    ->expectZoneCount('myField', 1, 1, 'Only the champion remains -- the Potion was sacrificed')
    ->expectDamage('p1Field-0', 1, 'Potion of Healing\'s own Sacrifice: Recover 5 still resolves (6 damage - 5 = 1 remaining)')
    ->expectZoneCount('myHand', 2, 1, 'The granted On Sacrifice bonus (Draw two cards) resolved before the Potion\'s own ability')
    ->expectDqEmpty()
    ->register();

// --- Potion Infusion: Starlight: Rest target Potion, grant "On Sacrifice: champion +4 level" ---
// Only the base ability (Rest target Potion, grant "On Sacrifice: champion +4 level") is
// covered; [Class Bonus] Starcalling -- (1) needs a full starcalling reveal-and-choose flow
// (a mechanic not otherwise scripted this session) and is out of scope. No "computed level"
// assertion type exists in this harness (only computed_power_equals/computed_life_equals), so
// the grant is confirmed via the champion's own TurnEffects list carrying "INFUSION_STARLIGHT"
// after the Potion's Sacrifice ability resolves, rather than a numeric level readout.
// Notes carried over from the original definition:
//   - Arisanna, Astral Zenith -- unlocks ASTRA for Potion Infusion: Starlight
//   - Blightroot (HERB) #1 - brew ingredient
//   - Blightroot (HERB) #2 - brew ingredient
//   - Potion Infusion: Starlight
//   - Brew Potion of Healing (sacrifice both Blightroot tokens instead of paying reserve).
//   - Play Potion Infusion: Starlight, paying its full 3 reserve (no Class Bonus).
//   - Decline the standing fast-opportunity to Sacrifice the brewed Potion early -- Infusion
//   - must resolve first so the granted "On Sacrifice: champion +4 level" bonus is in place.
//   - Target the brewed Potion of Healing (myField-1) to rest it and grant the bonus.
//   - Activate the Potion's own Sacrifice ability -- triggers the granted "+4 level" before it.
gaScenario('potion-infusion-starlight-rest-grant-champion-level')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Arisanna, Astral Zenith'])
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Potion of Healing')
    ->seed('myHand', 'Potion Infusion: Starlight')
    ->play('Potion of Healing')->answer(1, 'YES')->target('myField-1')->target('myField-2')->passBoth()
    ->castSpell('Potion of Healing', pay: 3, window: 'me', target: 'myField-1')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Potion Infusion: Starlight')
    ->expectZoneCount('myField', 1, 1, 'Only the champion remains -- the Potion was sacrificed')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","INFUSION_STARLIGHT"]', 'The champion carries the granted On Sacrifice bonus (+4 level until end of turn)')
    ->expectDqEmpty()
    ->register();

// --- Prototype Staff: [Level 4+] REST: put a hand card on bottom of deck, draw into memory ---
// Only the base REST ability (put a hand card on the deck's bottom, then draw into memory) is
// covered; the separate [Class Bonus][Memory 4+] +1 level static clause needs an "effective
// champion level" assertion type this harness doesn't have and is out of scope. The starting
// champion's level is patched to 4 to match the ability's printed [Level 4+] gate, though the
// ability's own macro body (GeneratedMacroCode.php) has no level check at all -- the gate
// appears to be enforced only client-side/by schema metadata, not by game logic, so this
// fixture exercises the effect unconditionally regardless. Field items with an activated
// ability are not clickable via a plain myField-N!FSM! action -- offered as a fast-action
// MZMAYCHOOSE opportunity once the turn player attempts to pass (same shape as sweet-ambrosia-
// banish-recover).
// Notes carried over from the original definition:
//   - matches the printed [Level 4+] gate
//   - Prototype Staff
//   - attempt to pass -> offers the fast-action opportunity
//   - put a hand card on the bottom of the deck
gaScenario('prototype-staff-rest-bottom-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(level: 4)
    ->seed('myField', 'Prototype Staff')
    ->endTurn()->answer(1, '-')->answer(1, 'myField-1@Activate-0@8c9htu9agw')->pay(1)
    ->tested('Prototype Staff')
    ->expectZoneCount('myMemory', 1, 1, 'Putting a hand card on the bottom of the deck draws a card into memory', step: 4)
    ->register();

// --- Piper's Lullaby: champion +1 level while Animal/Beast ally, [Class Bonus] rest target ally ---
// Piper's Lullaby is WATER -- the starting champion's CardID is patched directly to Silvie,
// With the Pack (TAMER, satisfies the Class Bonus) and its Subcards are ALSO patched with a
// real WATER champion (Nico, Rapture's Embrace) for element/lineage unlock. Unlike the "+1
// level while Animal/Beast ally" STATIC clauses tested indirectly via Glimpse LV elsewhere this
// session, this one is a one-shot AddTurnEffect("raG5r85ieO") applied when the card resolves
// (GeneratedMacroCode.php ~21186-21203), so it is directly observable via TurnEffects. Gray
// Wolf is seeded onto the field, satisfying the Animal/Beast condition and also serving as the
// Class Bonus's rest target, confirmed via Status.
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER) + Nico (WATER unlock)
//   - Gray Wolf (BEAST) -- satisfies the level condition and is the rest target
//   - [Class Bonus] rest Gray Wolf
gaScenario('pipers-lullaby-class-bonus-level-rest')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack', lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Piper\'s Lullaby')
    ->castSpell('Piper\'s Lullaby', pay: 1, target: 'myField-1')
    ->expectProperty('p1Field-0', 'TurnEffects', '["raG5r85ieO","vz4kc558yx-ACTION_ACTIVATED"]', 'The champion gets +1 level until end of turn (Animal/Beast ally present)')
    ->expectProperty('p1Field-1', 'Status', '1', '[Class Bonus] Gray Wolf is rested')
    ->expectDqEmpty()
    ->register();

// --- Peer into Mana: put 2+LV enlighten counters on your champion ---
// Peer into Mana is NORM, no lineage patch needed. At the default champion level (0), the
// amount is 2 + 0 = 2 enlighten counters (GeneratedMacroCode.php ~16481-16491).
gaScenario('peer-into-mana-enlighten-counters')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Peer into Mana')
    ->castSpell('Peer into Mana', pay: 4)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"enlighten":2}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', '914hZjxDL0')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectZoneCount('myHand', 3, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 4, 1)
    ->register();

// --- Power Overwhelming: remove X enlighten counters from your champion, it gets +X level until end of turn ---
// Power Overwhelming is ARCANE (reserve cost 0), so the starting champion's Subcards are also
// patched with a real ARCANE champion (Lorraine, Arclight Saber) to unlock element access,
// alongside 3 enlighten counters so the NUMBERCHOOSE (0 to current enlighten count,
// GeneratedMacroCode.php ~16844-16853) has real fuel; 2 is chosen. The resulting TurnEffect key
// is dynamically suffixed with the removed amount ("AnEPyfFfHj-2", GameLogic.php ~13084-13087,
// +N level per counter removed) and is directly observable in TurnEffects without needing the
// indirect Glimpse-LV proof technique.
// Notes carried over from the original definition:
//   - ARCANE lineage/element unlock + ability cost fuel
//   - The Lorraine, Arclight Saber Subcards patch grants a lineage-inherited "Enlighten"
//   - ability, offered as a fast-action opportunity as soon as the card enters the effect
//   - remove 2 enlighten counters
gaScenario('power-overwhelming-remove-enlighten-level')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Subcards' => ['x9sSpjpP3G'], 'Counters' => ['enlighten' => 3]])
    ->seed('myHand', 'Power Overwhelming')
    ->play('Power Overwhelming')->passBoth()->answer(1, '2')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"enlighten":1}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","AnEPyfFfHj-2"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'AnEPyfFfHj')
    ->expectZoneCount('myHand', 7, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->register();

// --- Phalanx Captain: reserve cost reduced by 1 per HUMAN ally (Class Bonus) ---
// Phalanx Captain is WIND element with a self-referential activationCostModifierAbilities
// entry (rPpLwLPGaL:0) that reduces its own reserve cost by 1 per HUMAN ally controlled,
// gated on WARRIOR Class Bonus. Champion CardID-patched to Lorraine, Blademaster (WARRIOR)
// with Spirit of Wind added via Subcards for WIND access; Dungeon Guide (a HUMAN ally) is
// seeded onto the field. With 1 HUMAN ally, the printed 5 reserve cost is reduced to 4.
// Notes carried over from the original definition:
//   - Dungeon Guide (HUMAN)
gaScenario('phalanx-captain-class-bonus-human-discount')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Blademaster', lineage: ['Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Phalanx Captain')
    ->castSpell('Phalanx Captain', pay: 4, window: 'me')
    ->expectDqEmpty(step: 6)
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED"]', step: 6)
    ->expectProperty('p1Field-2', 'CardID', 'rPpLwLPGaL', step: 6)
    ->expectProperty('p1Field-2', 'Status', '2', step: 6)
    ->expectDamage('p1Field-2', 0, step: 6)
    ->expectProperty('p1Field-2', 'Counters', '[]', step: 6)
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN"]', step: 6)
    ->expectZoneCount('myHand', 3, 1, step: 6)
    ->expectZoneCount('myField', 3, 1, step: 6)
    ->expectZoneCount('myMemory', 4, 1, step: 6)
    ->register();

// --- Prismatic Edge: [Class Bonus] On Enter, reveal memory: Fire deals 3 damage ---
// Prismatic Edge is CRUX element (verified via CardElement(), not NORM) with a 2-memory
// On Enter ability requiring [Class Bonus] WARRIOR -- champion CardID-patched to Lorraine,
// Crux Knight (WARRIOR + CRUX unlock). It must actually materialize (memory-cost REGALIA are
// Material-zone-only cards, like this deck's own Clarent/Backup Charger/Purifying Thurible)
// for its enterAbilities to fire, so the deck's Material section is just the champion slot
// plus Prismatic Edge itself, giving it myMaterial-0. 2 filler cards are seeded into myMemory
// to pay the 2-memory cost -- FINISHPAYMATERIALIZE banishes the FIRST N memory cards as
// payment, so a real FIRE-element card (Spirit of Fire itself) is seeded into the OPPONENT's
// memory instead, untouched by payment, to trigger the "reveal memory for Fire" branch
// (revealing either player's memory counts) without index-ordering risk. The Fire branch
// deals 3 damage to a chosen unit (PrismaticEdgeFire, CardDQHandlers.php) -- P2's Dungeon
// Guide is the target.
// Notes carried over from the original definition:
//   - Spirit of Fire (FIRE element), untouched by payment
//   - Dungeon Guide, damage target
gaScenario('prismatic-edge-fire-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Prismatic Edge
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(cardName: 'Lorraine, Crux Knight')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myMemory', 'Spirit of Fire', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('theirField-1')
    ->tested('Prismatic Edge')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'FxYwR2azTt')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '{"durability":1}')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Banish-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Banish-1', 'Counters', '[]')
    ->expectProperty('p1Banish-1', 'TurnEffects', '[]')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Graveyard-0', 'Status', '2')
    ->expectDamage('p2Graveyard-0', 3)
    ->expectProperty('p2Graveyard-0', 'Counters', '[]')
    ->expectProperty('p2Graveyard-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->expectZoneCount('theirField', 1, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Powered Defender: dealDamageAbilities dispatch coverage (z07nau5sw9) ---
// Effects Stack fixture-coverage gap fill: dealDamageAbilities["z07nau5sw9:0"] summons a
// Powercell token (qzzadf9q1v) whenever Powered Defender is dealt damage. No prior fixture
// deals real (combat or non-combat) damage to this card. WATER element ally, cost 4 reserve,
// class bonus (Taunt) not needed for this test. Uses a cheap real damage spell -- Charge the
// Soul (ra9950o14t, NORM element, 1 reserve, "Deal 1 damage to target unit") -- targeting our
// own ally, which is the simplest real damage-dealing event available (no combat/attack
// sequence needed, since the dealDamageAbilities dispatch doesn't check isCombat for this
// card -- unlike pal7cpvn96/Intrepid Spearman's replacement effect).
// Powered Defender is seeded directly onto the field (myField-1, awake by default) rather
// than played from hand -- setup field-seeding bypasses cost/element checks entirely, so no
// WATER element access is needed. Charge the Soul is seeded into hand (myHand-7), then
// played by resolving the OpportunityWindowFirstResponse MZMAYCHOOSE with its own mzID
// (mode=10002 FSM free-play is a no-op here -- verified live: reports success but never
// removes the card from hand). Its 1-reserve cost is
// paid with myHand-0, then the resulting MZCHOOSE:myField-0&myField-1&theirField-0 targets
// myField-1 (Powered Defender itself). Verified live: this leaves Powered Defender with 1
// damage and a genuine qzzadf9q1v Powercell token freshly created at myField-2 -- no
// additional PASS actions are needed, the Effects Stack resolves immediately.
gaScenario('powered-defender-dealt-damage-summons-powercell')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Powered Defender')
    ->seed('myHand', 'Charge the Soul')
    ->endTurn()
    ->endTurn(2)->target('myHand-7')->pay(1)->target('myField-1')
    ->tested('Powered Defender')
    ->expectDamage('p1Field-1', 1, 'Powered Defender takes the 1 real damage from Charge the Soul')
    ->expectProperty('p1Field-2', 'CardID', 'qzzadf9q1v', 'Whenever Powered Defender is dealt damage, summon a Powercell token (dealDamageAbilities["z07nau5sw9:0"] dispatch fires and creates the token)')
    ->expectZoneCount('myField', 3, 1, 'Champion + Powered Defender + the newly summoned Powercell token')
    ->register();

// --- Photic Blade: gets +1 POWER for each refinement counter on it ---
// Photic Blade's element is LUXEM, but it is seeded directly onto the field (materialize flow
// out of scope, same pattern as necklace-of-foresight-banish-glimpse), so no lineage patch is
// needed for this always-on static clause. It is pre-seeded with 2 refinement counters
// directly (normally only reachable via its own [Class Bonus] recover-triggered listener,
// which is out of scope here). Photic Blade's printed POWER is 3 (verified via CardPower());
// the computed_power_equals assertion of 5 is only possible if the +1-per-refinement-counter
// static case in GameLogic.php's power-modifier switch actually applied. A single harmless
// "end turn 1" action is included so the assertion runs against step 1 (this fixture's own
// freshly-parsed state) rather than step 0, which RunIntegrationTests.php evaluates before
// ever calling ParseGamestate() for this fixture (verified live -- see
// sable-remnant-class-bonus-power's note for the same gotcha).
// Notes carried over from the original definition:
//   - Photic Blade, 2 refinement counters
//   - harmless: ends turn 1
gaScenario('photic-blade-refinement-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Photic Blade', properties: ['Counters' => ['refinement' => 2]])
    ->endTurn()
    ->tested('Photic Blade')
    ->register();

// --- Piquant Shieldbearer: Taunt forces attackers to target it first while awake ---
// Piquant Shieldbearer's element is NORM, so no lineage patch is needed. Rule 1.h blocks the
// game's first player from attacking on turn 1, so player 1 ends turn 1 and player 2 attacks
// on their own turn 1 instead. The starting champion's printed POWER is blank/0
// (CardPower() returns -1, verified live), so BeginCombatPhase() silently refuses to let it
// attack at all with no weapon equipped -- a Dungeon Guide (1 POWER) is seeded onto player 2's
// field as the actual attacker instead. Piquant Shieldbearer is seeded onto player 1's field,
// awake, so its printed Taunt (parsed generically by HasKeyword_Taunt from the card text,
// GeneratedCode/GeneratedKeywordCode.php) applies. GetLegalAttackTargets()/the Taunt filter in
// CombatLogic.php (~line 512-525) restricts targeting to awake Taunt units when any exist, so
// attempting to target the champion directly is an illegal selection -- rejected here as
// negative-path semantic evidence -- and only the Taunt unit itself may then be legally
// targeted.
// Notes carried over from the original definition:
//   - Piquant Shieldbearer (Taunt), awake
//   - Dungeon Guide (1 POWER) - actual attacker
//   - awake, can attack
//   - ends turn 1 (first-player attack lock)
//   - declare attack with Dungeon Guide
//   - legal: target the Taunt unit itself
gaScenario('piquant-shieldbearer-taunt-forces-target')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Piquant Shieldbearer')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Cannot target the champion directly while an awake Taunt unit is present')
    ->answer(2, 'theirField-1')->answer(1, '-')
    ->tested('Piquant Shieldbearer')
    ->expectDqEmpty(step: 5)
    ->expectDamage('p1Field-1', 1, 'Combat lands on the Taunt unit itself once a legal target is chosen', step: 5)
    ->register();

// --- Petalfall Embrace: each player recovers 8+LV ---
// Petalfall Embrace (reserve 2, TERA) calls RecoverChampion(1, 8+PlayerLevel(1)) and
// RecoverChampion(2, 8+PlayerLevel(2)) directly (GeneratedMacroCode.php
// cardActivatedAbilities["uDWTjGarSL:0"]). Both champions are pre-damaged for 9 so the
// recovery (8 + level-1 = 9) is fully observable via their post-action Damage property instead
// of being capped by a full-health no-op.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock + pre-damage
//   - pre-damage opponent's champion
gaScenario('petalfall-embrace-recover-both')
    ->deck(GA_KONGMING_BASE_DECK)
    ->champion(lineage: ['Kongming, Fel Eidolon'], damage: 9)
    ->champion(damage: 9, player: 2)
    ->seed('myHand', 'Petalfall Embrace')
    ->castSpell('Petalfall Embrace', pay: 2)
    ->expectDamage('myField-0', 1, 'Player 1\'s champion recovers 8+LV(0)=8 from 9 pre-existing damage, leaving 1')
    ->expectDamage('theirField-0', 1, 'Player 2\'s champion also recovers 8+LV(0)=8 from 9 pre-existing damage, leaving 1')
    ->expectDqEmpty()
    ->register();

// --- Pyretic Prognosis: draw three cards, then discard two cards ---
// Pyretic Prognosis (reserve 3, EXALTED -- auto-enabled once another advanced element is
// enabled, same Fel Eidolon Subcards patch as the other EXALTED-cost Kongming fixtures) calls
// DoDrawCard($player,3) then DiscardCards($player,2) directly (GeneratedMacroCode.php
// cardActivatedAbilities["Q5HV9nWS5r:0"]). Net hand change after paying the 3-reserve cost and
// drawing 3/discarding 2 is +3-2=+1 relative to the post-cost hand, i.e. starting 7 + seeded 1
// - played 1 - 3 reserve + 3 drawn - 2 discarded = 5.
// Notes carried over from the original definition:
//   - EXALTED unlock via TERA lineage
gaScenario('pyretic-prognosis-draw-discard')
    ->deck(GA_KONGMING_BASE_DECK)
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myHand', 'Pyretic Prognosis')
    ->castSpell('Pyretic Prognosis', pay: 5)
    ->expectZoneCount('myDeck', 2, 1, 'Draw three cards empties 3 more cards from the deck (5 -> 2)')
    ->expectZoneCount('myGraveyard', 3, 1, 'Discard two cards, plus the resolved Pyretic Prognosis itself, land in the graveyard')
    ->expectZoneCount('myHand', 5, 1, 'Net hand size after playing, paying 3 reserve, drawing 3, and discarding 2')
    ->expectDqEmpty()
    ->register();

// --- Protective Fractal: [REST]: Prevent the next 1 damage to target champion this turn ---
// Protective Fractal (1lw9n0wpbh, PHANTASIA/CLERIC) is seeded directly onto PLAYER 2's field,
// already awake -- its activated [REST] ability has no Class Bonus or element-access gate
// (unlike Unstable Fractal/Fractal of Refreshment), so no champion patch is needed at all.
// Player 2 (not player 1) is the activating/attacking player throughout, so everything happens
// on player 2's own turn -- avoiding Rule 1.h's restriction on the true first player (player 1)
// attacking on turn 1 -- and keeping the activation and the damage in the SAME turn, since
// "this turn" is the duration of the printed clause (TurnEffects are not persistent across
// turn boundaries here, unlike Torpid Fractal's explicitly-registered FROZEN_BY_TORPID).
// Any awake Reservable/PHANTASIA card generates a generic "Take_a_fast_action?" MZMAYCHOOSE
// opportunity offer for its controller every time priority would otherwise pass (confirmed
// live, same family as Fairy Whispers' recurring Opportunity prompt) -- declining it ('-') is
// required at each such prompt, and this specific setup needs exactly two declines from player
// 2 to let player 1's turn-1 pass fully resolve into player 2's own turn. Once on player 2's
// turn, activating Protective Fractal via CustomInput "Activate:0" (DoActivatedAbility ->
// activateAbilityAbilities["1lw9n0wpbh:0"], GeneratedMacroCode.php) auto-rests it as its [REST]
// cost and queues an MZCHOOSE over all champions; targeting player 1's champion runs
// customDQHandlers["1lw9n0wpbh:0:ActivateAbility-1"], which AddTurnEffect(target,
// "1lw9n0wpbh"). A Dungeon Guide ALLY on player 2's field has its Counters overridden with
// potion_animate_power=3 (ObjectCurrentPower's override, GameLogic.php -- the same technique as
// the shred-to-ribbons/reaping-legacy precedents) so its real declared attack against player
// 1's champion deals a controlled, non-trivial 3 power before prevention -- the attack needs an
// explicit "Choose_attack_target" confirmation step even though the champion is the only legal
// target. CombatLogic.php's OnDealDamage (~line 4506-4512) then applies
// GetProtectiveFractalPrevention: exactly 1 of the 3 damage is prevented (amount -= 1) and the
// "1lw9n0wpbh" TurnEffect is consumed, leaving the champion's final Damage at 2 -- neither the
// un-prevented 3 (proving prevention did fire) nor 0 (proving it prevents only 1 point, not the
// entire instance). Combat resolved without an explicit Retaliate prompt (this champion has no
// Retaliate keyword), so declining the recurring fast-action offer is what actually triggers
// damage resolution here, not a separate Retaliate decision.
// Notes carried over from the original definition:
//   - Protective Fractal, seeded awake onto player 2's field
//   - Dungeon Guide ALLY on player 2's field, POWER overridden to 3 -- the attacker
//   - ends turn 1 (true-first-player attack lock)
//   - decline fast-action re-offer 1/2 so turn 1 finishes resolving
//   - decline fast-action re-offer 2/2 -- now player 2's own turn begins
//   - activate Protective Fractal
//   - target player 1's champion
//   - decline the fast-action re-offer of the now-rested Protective Fractal
//   - Dungeon Guide (3 POWER) declares a real attack
//   - confirm the attack target (player 1's champion, the only legal target)
//   - decline the fast-action re-offer again -- this is what actually resolves combat damage
gaScenario('protective-fractal-rest-prevent-1-damage')
    ->deckTemplate('diao-awakened-deacon', 'Protective Fractal')
    ->seed('theirField', 'Protective Fractal', properties: ['Status' => 2])
    ->seed('theirField', 'Dungeon Guide', properties: ['Status' => 2, 'Counters' => ['potion_animate_power' => 3]])
    ->endTurn()->answer(2, '-')->answer(2, '-')
    ->action(2, 10001, 'myField-1!CustomInput!Activate:0')->answer(2, 'theirField-0')->answer(2, '-')
    ->action(2, 10002, 'myField-2!FSM!')->answer(2, 'theirField-0')->answer(2, '-')
    ->tested('Protective Fractal')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 2, 'The champion took only 2 of the attacker\'s 3 declared power as damage -- 1 point was prevented by Protective Fractal, proving the clause fired (not 3, unprevented) and prevents only 1 point (not 0, i.e. not the whole instance)')
    ->expectProperty('p1Field-0', 'Counters', '{"_champDamageThisTurn":2}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN"]', 'The champion\'s TurnEffects no longer contains "1lw9n0wpbh" -- the prevention tag was consumed by CombatLogic.php\'s OnDealDamage exactly once, matching the single-use nature of \'prevent the next 1 damage\'')
    ->expectProperty('p2Field-1', 'Status', '1', 'Protective Fractal itself is rested (Status 1), paying the printed [REST] cost of its activated ability')
    ->expectProperty('p2Field-1', 'TurnEffects', '[]')
    ->expectProperty('p2Field-2', 'Status', '1')
    ->expectProperty('p2Field-2', 'TurnEffects', '[]')
    ->expectProperty('p2Hand-7', 'CardID', 'c9p4lpnvx7')
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// Portentous Tanggu: [Guo Jia Bonus] On Enter: put a quest counter on your champion.
gaScenario('portentous-tanggu-enter-quest-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->patch('myHand-0', ['CardID' => 'mb3iqw3kc6'])
    ->playSlot(0)->pay(3)->passBoth()
    ->tested('Portentous Tanggu')
    ->expectProperty('myField-0', 'Counters', '{"quest":1}', '[Guo Jia Bonus] Portentous Tanggu\'s On Enter puts a quest counter on the champion')
    ->register();

// Peacock of Prosperity: [Class Bonus][Element Bonus] whenever you reveal it from memory, may put
// a copy from memory onto the field, then draw a card into memory.
// Notes carried over from the original definition:
//   - Guo Jia (TAMER, matching Peacock's own class) + LUXEM lineage for Uncover the Plot.
//   - the champion's element must match the card's (LUXEM) for the Element Bonus
//   - Two copies of Peacock of Prosperity in memory.
//   - Uncover the Plot targeting yourself reveals your whole memory, firing Peacock of
//   - Prosperity's reveal-triggered ability once per copy revealed.
//   - First copy's reveal-triggered MZMAYCHOOSE: move the OTHER copy (myMemory-1) to the field.
//   - Second copy also gets revealed and offers the same choice; decline it.
gaScenario('peacock-of-prosperity-reveal-memory-to-field')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->patch('myField-0', ['CardID' => 'j6dkdoxyqt', 'Subcards' => ['UAF6Nr7GUE'], 'Counters' => ['_overrides' => ['element' => 'LUXEM']]])
    ->seed('myMemory', 'Peacock of Prosperity')
    ->seed('myMemory', 'Peacock of Prosperity')
    ->patch('myHand-0', ['CardID' => '4zkTRt8qXn'])
    ->playSlot(0)->pay(2)->answer(1, 'YES')->target('myMemory-1')->answer(1, '-')
    ->tested('Peacock of Prosperity')
    ->expectProperty('myField-1', 'CardID', 'cl1mvb9q96', 'A copy of Peacock of Prosperity is moved from memory onto the field', step: 5)
    ->register();

// --- Poisoned Dagger (0D6AfZyKXh): [REST], Banish: Deal 1 damage to target unit. Class Bonus: Until
// end of turn, if that unit were to take damage, it takes that much damage plus 1 instead. ---
// Notes carried over from the original definition:
//   - Champion patched directly to Merlin, Memorite Vassal (ASSASSIN class) so IsClassBonusActive(["ASSASSIN"]) is true.
//   - Poisoned Dagger's own default template enters with Status=1 (rested), matching its "enters
//   - the field rested" text; explicitly override to Status=2 (ready) so the [REST] cost is payable.
gaScenario('poisoned-dagger-class-bonus-curse')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Poisoned Dagger
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->seed('myField', 'Poisoned Dagger', properties: ['Status' => 2])
    ->seed('theirField', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-0')->passBoth()
    ->tested('Poisoned Dagger')
    ->expectDamage('theirField-0', 1, 'Poisoned Dagger deals 1 damage to the target champion')
    ->register();

// --- Prismspire Scepter (mgesApvmwS): [Merlin Bonus] On Enter, as a Spell, put two sheen counters
// on target champion you don't control. ---
// Notes carried over from the original definition:
//   - Champion patched to Merlin, Memorite Vassal so IsMerlinBonusActive() is true (supporting
//   - precondition -- not retesting 6R8XmWoKLn's own Enter here).
//   - MZCHOOSE target -- the only legal target is the opponent's champion (memory cost 0, no
gaScenario('prismspire-scepter-merlin-bonus-enter-sheen-opponent')
    ->deckTemplate('spirit-dungeon-guide', 'Prismspire Scepter')
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->playSlot(0)->target('theirField-0')->passBoth()
    ->tested('Prismspire Scepter')
    ->register();

// --- Proto Key Crest (k5wrAxBbF9): On Charge 3, return Proto Key Crest to its owner's material
// deck and recover 3. ---
// Notes carried over from the original definition:
//   - Pre-existing damage so "recover 3" is observable.
//   - Seeded directly onto the field with 2 of 3 charge counters already on it -- its own
//   - ability triggers from the generic "On Charge N" recollection-phase system (GameLogic.php
//   - ~9998-10041), not from its own Enter, so raw zone-seeding is fine here (see the fixture
//   - file's pitfall notes: raw seeding is only unsafe for a card's OWN Enter-triggered ability).
//   - End turn 1 (P1, then P2) to reach P1's turn-2 recollection phase, where the 3rd charge
//   - counter is added and the on-charge-3 trigger fires for real.
gaScenario('proto-key-crest-charge3-recover')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Proto Key Crest
# Main
10 Dungeon Guide
DECK
    )
    ->champion(damage: 5)
    ->seed('myField', 'Proto Key Crest', properties: ['Counters' => ['charge' => 2]])
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Proto Key Crest')
    ->register();

// --- Protect Her At All Costs (OzNHncAfFJ): [Merlin Bonus] costs 2 less to activate. Summon a
// Memorite Obelith token with two sheen counters. Base reserve cost 5, discounted to 3 while
// Merlin Bonus is active -- cast for real with a Merlin champion on the field to exercise the
// real 3-hand-payment path. ---
// Notes carried over from the original definition:
//   - Champion patched directly to Merlin, Memorite Vassal (level 1) -- already-leveled
//   - precondition (Merlin's own Enter is not what this fixture tests), so Merlin Bonus
//   - (-2 activation cost) is active for Protect Her At All Costs.
//   - Guarantee Protect Her At All Costs is in hand to play for real (the opening hand draw
//   - is not guaranteed to include it with only 4 copies in the deck).
gaScenario('protect-her-at-all-costs-merlin-bonus-summon-token')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Merlin, Memorite Vassal
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Protect Her At All Costs
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
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->seed('myHand', 'Protect Her At All Costs')
    ->castSpell('Protect Her At All Costs', pay: 3, window: 'both')
    ->expectZoneCount('myGraveyard', 1, 1, 'Protect Her At All Costs resolved off the EffectStack into the graveyard')
    ->expectProperty('myGraveyard-0', 'CardID', 'OzNHncAfFJ', 'The graveyard card is Protect Her At All Costs itself, not a stranded/unrelated entry')
    ->expectZoneCount('myField', 2, 1, 'A Memorite Obelith token was summoned (activation succeeded with only 3 hand payments thanks to the Merlin Bonus -2 discount on the base cost of 5)')
    ->expectProperty('myField-1', 'CardID', 'fdnlbJm3hr', 'The summoned token is a Memorite Obelith')
    ->expectProperty('myField-1', 'Counters', '{"sheen":2}', 'The token has two sheen counters, per the card\'s printed effect')
    ->register();

// --- Possessed Reaping (oqDz2jIBZI): On Ally Kill: Return the killed ally from its owner's
// graveyard to the field under your control rested. It becomes a Spirit in addition to its other
// types. ---
// Possessed Reaping's element is CRUX, so the champion is patched directly to Merlin,
// Brilliant Vestige (2TCyILvBYa, itself CRUX) -- same precondition technique as
// spirit-blade-terminus-onhit-double-sheen-memory -- to satisfy the element requirement. A
// Dungeon Guide (1 POWER / 3 LIFE, no counters) is seeded onto the opponent's (player 1's) own
// field as a genuinely killable defender: Possessed Reaping's printed POWER (4) exceeds its 3
// LIFE, so attacking it for real (not just declaring the attack) actually kills it during
// replay -- the "On Ally Kill" trigger (onKillAbilities["oqDz2jIBZI:0"] ->
// PossessedReapingOnKill() in GameLogic.php) is dispatched from OnKillTrigger() in
// CombatLogic.php only off a genuine combat kill, not merely a declared or blocked attack.
// ATTACK cards can't be activated by the game's first player on turn 1
// (CanActivateAttackCardNow/IsFirstTurnAttackLocked in GameLogic.php), so player 1 ends turn 1
// first and player 2 plays Possessed Reaping on their own turn 1 instead (same shape as
// thieving-cut-prepare-onhit-draw). PossessedReapingOnKill() moves the killed Dungeon Guide from
// its owner's (player 1's) graveyard onto Possessed Reaping's controller's (player 2's) field,
// rested (Status 1), tagged with the SPIRIT subtype in addition to its printed MAGE/HUMAN
// subtypes.
// Notes carried over from the original definition:
//   - Declaring the attack on a non-champion ally target opens a priority window before combat
//   - damage resolves (unlike attacking a champion directly, which resolves synchronously) --
//   - both players must explicitly pass it: player 1 declines the MZMAYCHOOSE prompt offered
//   - over their own about-to-be-attacked Dungeon Guide, then player 2 answers the follow-up
//   - YESNO the same way, after which the decision queue actually empties and combat resolves
//   - (verified live via RunnerPendingDecisionSummary -- confirmed via direct probe against this
//   - exact deck/seed).
gaScenario('possessed-reaping-onallykill-return-as-spirit')
    ->deckTemplate('spirit-dungeon-guide', 'Possessed Reaping')
    ->champion(cardName: 'Merlin, Brilliant Vestige', player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Possessed Reaping', player: 2)
    ->endTurn()
    ->play('Possessed Reaping', 2)->pay(3, 2)->answer(2, 'theirField-1')->passBoth()
    ->register();

// --- Pantheon Barrier: printed Omnishroud -- an opposing spell cannot target it ---
// Pantheon Barrier (WyNvyDHFdB, TOKEN,PHANTASIA,DOMAIN): "Omnishroud". Omnishroud means "This can't be
// targeted by activations, materializations, or triggered abilities" (comprehensive rules, Keywords >
// Omnishroud 1); a spell is a card activation, so an Omnishroud object can't be targeted by spells --
// Spellshroud and Omnishroud are redundant with each other (Omnishroud 3a). The engine already applied that to
// the OMNISHROUD turn effect and Argus, All-Seeing Giant, but printed Omnishroud on these cards was never
// recognised: HasSpellshroud() only knew a hard-coded card-ID list.
// Player 2 controls the holder plus a Ominous Shadow; player 1 plays Disenchant ("Destroy target phantasia", a
// Spell): the target prompt offers ONLY the Ominous Shadow, answering with the holder is refused
// (expectFailure) and the legal answer then resolves.
// Notes carried over from the original definition:
//   - Pantheon Barrier -> p2 field-1
//   - Ominous Shadow -> p2 field-2
//   - Disenchant -> p1 myHand-7
//   - player 1 plays Disenchant
//   - the Ominous Shadow is the legal target
gaScenario('pantheon-barrier-printed-omnishroud-spell-cannot-target-it')
    ->deck($mordredDeck)
    ->seed('myField', 'Pantheon Barrier', player: 2)
    ->seed('myField', 'Ominous Shadow', player: 2)
    ->seed('myHand', 'Disenchant')
    ->castSpell('Disenchant', pay: 2)
    ->rejects(1, 'theirField-1', 'Pantheon Barrier (WyNvyDHFdB) has spellshroud: not a legal target for the opposing Disenchant')
    ->target('theirField-2')
    ->tested('Pantheon Barrier')
    ->register();

// --- Piccarda, Night Rider: [Class Bonus] Spellshroud -- already implemented, pinned (Class Bonus met: player
// 2's champion is an Assassin (Tristan, Underhanded); Piccarda is Assassin/Warrior) ---
// Piccarda, Night Rider (ooGvrzxTmr, UNIQUE,ALLY): "[Class Bonus] Spellshroud, Stealth" (conditional printed
// Spellshroud). Already handled before the printed-Spellshroud sweep: HasCardKeywordOverride()'s ooGvrzxTmr
// entry ("ClassBonusSpellshroud") plus the [Class Bonus] check in HasSpellshroud() -- the sweep fix leaves it
// untouched, this fixture pins it (it passes on HEAD without the fix).
// Condition: Class Bonus met: player 2's champion is an Assassin (Tristan, Underhanded); Piccarda is
// Assassin/Warrior. Player 2 controls the holder plus a Dungeon Guide; player 1 plays Liturgy of Corruption
// ("Put a buff counter on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide,
// the holder answer is refused (expectFailure) and the legal answer resolves.
// Notes carried over from the original definition:
//   - player 2 champion -> Tristan, Underhanded (level 1 ASSASSIN)
//   - Piccarda, Night Rider -> p2 field-1
//   - Dungeon Guide -> p2 field-2
//   - Liturgy of Corruption -> p1 myHand-7
//   - player 1 plays Liturgy of Corruption
//   - the Dungeon Guide is the legal target
gaScenario('piccarda-night-rider-class-bonus-spellshroud-assassin-champion-spell-cannot-target-her')
    ->deck($mordredDeck)
    ->champion(cardName: 'Tristan, Underhanded', player: 2)
    ->seed('myField', 'Piccarda, Night Rider', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Liturgy of Corruption')
    ->castSpell('Liturgy of Corruption', pay: 2)
    ->rejects(1, 'theirField-1', 'Piccarda, Night Rider (ooGvrzxTmr) has spellshroud: not a legal target for the opposing Liturgy of Corruption')
    ->target('theirField-2')
    ->tested('Piccarda, Night Rider')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answers left Piccarda, Night Rider (ooGvrzxTmr) untouched: Spellshroud stops the opposing Liturgy of Corruption from targeting it', step: 4)
    ->expectProperty('p2Field-2', 'Counters', '[]', 'The refused answers did not apply Liturgy of Corruption to the Dungeon Guide either (prompt still pending)', step: 4)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'Piccarda, Night Rider (ooGvrzxTmr) is unaffected by the resolved Liturgy of Corruption')
    ->expectProperty('p2Field-2', 'Counters', '{"buff":1}', 'The only legal target, Dungeon Guide, received the buff counter')
    ->expectProperty('p1Graveyard-0', 'CardID', 'BZMKfFZ22T', 'Liturgy of Corruption resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Piccarda, Night Rider: [Class Bonus] Spellshroud -- negative control (Class Bonus NOT met: player 2's
// champion is a Ranger (Diana, Keen Huntress); Piccarda is Assassin/Warrior) ---
// Piccarda, Night Rider (ooGvrzxTmr): "[Class Bonus] Spellshroud, Stealth". Negative control for the Class
// Bonus gate: Class Bonus NOT met: player 2's champion is a Ranger (Diana, Keen Huntress); Piccarda is
// Assassin/Warrior, so the holder has NO Spellshroud and the opposing Liturgy of Corruption offers it: both
// the holder and the Dungeon Guide are legal targets and answering with the holder resolves on it.
// Notes carried over from the original definition:
//   - player 2 champion -> Diana, Keen Huntress (level 1 RANGER)
//   - Piccarda, Night Rider -> p2 field-1
//   - Dungeon Guide -> p2 field-2
//   - Liturgy of Corruption -> p1 myHand-7
//   - player 1 plays Liturgy of Corruption
//   - the holder is a legal target while the Class Bonus is off
gaScenario('piccarda-night-rider-class-bonus-spellshroud-ranger-champion-spell-can-target-her')
    ->deck($mordredDeck)
    ->champion(cardName: 'Diana, Keen Huntress', player: 2)
    ->seed('myField', 'Piccarda, Night Rider', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Liturgy of Corruption')
    ->castSpell('Liturgy of Corruption', pay: 2, target: 'theirField-1')
    ->tested('Piccarda, Night Rider')
    ->expectProperty('p2Field-1', 'Counters', '{"buff":1}', 'NEGATIVE CONTROL: Piccarda, Night Rider (ooGvrzxTmr) has no Spellshroud while the Class Bonus is off, so it was offered and received the buff counter')
    ->expectProperty('p2Field-2', 'Counters', '[]', 'Dungeon Guide was not the chosen target and is untouched')
    ->expectProperty('p1Graveyard-0', 'CardID', 'BZMKfFZ22T', 'Liturgy of Corruption resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Perilous Mend: declining a further Curse still recovers for the Curses already put on the lineage (dontSkipOnPass regression) ---
// Perilous Mend (A5jpuUlwDd): "[Class Bonus] Put any amount of Curse cards from your hand and/or memory on the bottom of your champion's lineage in any order. Recover X, where X is three times the amount of cards put this way. [Class Bonus] If your champion is distant, draw a card." Stopping early (declining the 'Put Curse on lineage?' prompt) must still recover 3 per Curse already put on the lineage.
// Regression: PerilousMendChooseCurse() queued its paired CUSTOM 'PerilousMendChooseCurse' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its PASS branch (PerilousMendFinalize: Recover 3*count, draw if distant) never ran: Curses were put on the lineage and the champion was never healed.
// The champion is patched to Diana, Keen Huntress (RANGER, for the Class Bonus) with a Diana, Cursebreaker (UMBRA) Subcards lineage for UMBRA element access and 6 damage; two Malevolent Vow (CURSE) cards are seeded in hand; one is put on the lineage, the next offer declined with PASS -> recover 3 (damage 6 -> 3). (Incidental, NOT fixed here: the lineage ends up holding the mzID string 'myHand-6' rather than the Curse card's ID.)
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (RANGER) for the Class Bonus + Diana, Cursebreaker (UMBRA) lineage; champion has 6 damage
//   - Malevolent Vow (CURSE) #1 -> myHand-7
//   - Malevolent Vow (CURSE) #2 -> myHand-8
//   - reserve 1/1
//   - put the first Curse on the lineage
//   - decline putting another Curse on the lineage
gaScenario('perilous-mend-decline-further-curse-still-recovers')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Keen Huntress', lineage: ['Diana, Cursebreaker'], damage: 6)
    ->seed('myHand', 'Malevolent Vow')
    ->seed('myHand', 'Malevolent Vow')
    ->seed('myHand', 'Perilous Mend')
    ->play('Perilous Mend')->pay(1)->target('myHand-6')->pass(1)
    ->expectDamage('p1Field-0', 3, 'Recover 3*X with X = 1 Curse put on the lineage: champion damage 6 -> 3, even though the second offer was declined')
    ->expectDqEmpty()
    ->register();

// --- Piccarda: declining a further static-counter removal pays the rest of the reserve cost (dontSkipOnPass regression) ---
// Piccarda, Night Rider (ooGvrzxTmr): "While paying for this card's reserve cost, you may remove up to four static counters from among objects you control. Each counter removed this way pays for 1 of that cost." Declining a 'Remove a static counter to pay 1?' prompt ends the optional removal and the rest of the reserve cost is paid the normal way.
// Regression: PiccardaStaticCost's queue sites (ActivateCard in Custom/GameLogic.php and the handler's own re-queue in Custom/CardDQHandlers.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and the remaining ReserveCard payments + EffectStackOpportunity were never queued: Piccarda sat on the effect stack with an empty decision queue and could never be paid for (a soft-lock). Fixing the flags alone was not enough: the handler has no explicit decline branch -- on PASS it fell through and RE-OFFERED the same prompt forever, and the unflagged ReserveCard CUSTOMs it queues would be skipped while lastDecision is still 'PASS' -- so the handler now treats PASS/-/empty as 'done removing' (no re-offer) and queues a PASSPARAMETER '-' before the reserve payments.
// The champion is given an ARCANE Subcards lineage (Piccarda is ARCANE) and 2 static counters; seven filler-able hand cards pay the cost. Here one static counter is removed (paying 1), the next offer is declined with PASS, and the remaining 6 of the 7 reserve cost is paid from hand.
// Notes carried over from the original definition:
//   - Lorraine, Arclight Saber (ARCANE) lineage for ARCANE access; champion carries 2 static counters
//   - remove one static counter (pays 1 of 7)
//   - decline removing another static counter
//   - pay reserve 1/6
//   - pay reserve 2/6
//   - pay reserve 3/6
//   - pay reserve 4/6
//   - pay reserve 5/6
//   - pay reserve 6/6
gaScenario('piccarda-decline-further-static-removal-pays-rest-of-cost')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Subcards' => ['x9sSpjpP3G'], 'Counters' => ['static' => 2]])
    ->seed('myHand', 'Piccarda, Night Rider')
    ->play('Piccarda, Night Rider')->target('myField-0')->pass(1)->pay(6)
    ->expectProperty('p1Field-1', 'CardID', 'ooGvrzxTmr', 'Piccarda was paid for and entered the field')
    ->expectProperty('p1Field-0', 'Counters', '{"static":1}', 'Static counters left on the champion: 1')
    ->expectDqEmpty()
    ->register();

// --- Piccarda: declining every static-counter removal pays the full reserve cost (dontSkipOnPass regression) ---
// Piccarda, Night Rider (ooGvrzxTmr): "While paying for this card's reserve cost, you may remove up to four static counters from among objects you control. Each counter removed this way pays for 1 of that cost." Declining a 'Remove a static counter to pay 1?' prompt ends the optional removal and the rest of the reserve cost is paid the normal way.
// Regression: PiccardaStaticCost's queue sites (ActivateCard in Custom/GameLogic.php and the handler's own re-queue in Custom/CardDQHandlers.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and the remaining ReserveCard payments + EffectStackOpportunity were never queued: Piccarda sat on the effect stack with an empty decision queue and could never be paid for (a soft-lock). Fixing the flags alone was not enough: the handler has no explicit decline branch -- on PASS it fell through and RE-OFFERED the same prompt forever, and the unflagged ReserveCard CUSTOMs it queues would be skipped while lastDecision is still 'PASS' -- so the handler now treats PASS/-/empty as 'done removing' (no re-offer) and queues a PASSPARAMETER '-' before the reserve payments.
// The champion is given an ARCANE Subcards lineage (Piccarda is ARCANE) and 2 static counters; seven filler-able hand cards pay the cost. Here the very first prompt is declined with PASS, so the whole 7 reserve cost is paid from hand and both static counters stay on the champion.
// Notes carried over from the original definition:
//   - Lorraine, Arclight Saber (ARCANE) lineage for ARCANE access; champion carries 2 static counters
//   - decline removing any static counter
//   - pay reserve 1/7
//   - pay reserve 2/7
//   - pay reserve 3/7
//   - pay reserve 4/7
//   - pay reserve 5/7
//   - pay reserve 6/7
//   - pay reserve 7/7
gaScenario('piccarda-decline-all-static-removal-pays-full-cost')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Subcards' => ['x9sSpjpP3G'], 'Counters' => ['static' => 2]])
    ->seed('myHand', 'Piccarda, Night Rider')
    ->play('Piccarda, Night Rider')->pass(1)->pay(7)
    ->expectProperty('p1Field-1', 'CardID', 'ooGvrzxTmr', 'Piccarda was paid for and entered the field')
    ->expectProperty('p1Field-0', 'Counters', '{"static":2}', 'Static counters left on the champion: 2')
    ->expectDqEmpty()
    ->register();

// opponent's top four, top to bottom: Dungeon Guide, Fluffy Shopkeep, Fairy Whispers, Charge the Soul
// (player 2's turn-2 draw takes a filler Dungeon Guide off the top first)
// player 1's recollection phase opens a response window: activate Peer the Depths from it, pay 2 reserve
// Notes carried over from the original definition:
//   - banish Fairy Whispers (3rd of the opponent's top four)
//   - the other three onto the bottom of THEIR deck, chosen order
gaScenario('peer-the-depths-look-four-opponent-banish-one-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Peer the Depths')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'], player: 2)
    ->endTurn()
    ->endTurn(2)->target('myHand-7')->pay(2)->target('myTempZone-2')
    ->answer(1, 'Top=;Bottom=ra9950o14t,px60u5n1do,em6eEh9q8y')
    ->tested('Peer the Depths')
    ->register();

// Notes carried over from the original definition:
//   - banish Dungeon Guide (the opponent's top card)
//   - rest to the bottom in the original order
gaScenario('peer-the-depths-banish-top-card-keeps-order-of-the-rest')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Peer the Depths')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'], player: 2)
    ->endTurn()
    ->endTurn(2)->target('myHand-7')->pay(2)->target('myTempZone-0')
    ->answer(1, 'Top=;Bottom=px60u5n1do,n8wyfG9hbY,ra9950o14t')
    ->tested('Peer the Depths')
    ->register();

// PLAYER 2 seating (perspective check): player 2 casts it in ITS recollection phase against player 1's deck; the four cards sit
// under player 1's turn-3 draw (a filler Dungeon Guide)
// player 1's turn-3 recollection window also prompts player 2 (second response): decline it; then player 1 passes and player 2's OWN
// turn-4 recollection window opens
// Notes carried over from the original definition:
//   - banish Fairy Whispers
gaScenario('peer-the-depths-player-two-look-four-opponent-banish-one-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Peer the Depths', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->endTurn()
    ->endTurn(2)->pass(2)
    ->endTurn()->answer(2, 'myHand-7')->pay(2, 2)->answer(2, 'myTempZone-2')
    ->answer(2, 'Top=;Bottom=ra9950o14t,px60u5n1do,em6eEh9q8y')
    ->tested('Peer the Depths')
    ->register();

// top ten, top to bottom: DG, FS, FW, DG, FS, FW, Ordinary Horse (7th), DG, FS, FW; then Charge the Soul (marker)
// Notes carried over from the original definition:
//   - reveal Ordinary Horse (7th of the ten) into hand
gaScenario('paired-minds-look-ten-reveal-deep-horse-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Paired Minds, Kindred Souls')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Ordinary Horse', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Paired Minds, Kindred Souls', pay: 3, target: 'myTempZone-6')
    ->register();

// two Horse allies: Caretaker Horse (3rd) and Ordinary Horse (8th); the player reveals the 8th, the 3rd goes to the bottom
// Notes carried over from the original definition:
//   - reveal Ordinary Horse (8th) into hand
gaScenario('paired-minds-look-ten-two-horses-reveal-second-first-goes-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Paired Minds, Kindred Souls')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Caretaker Horse', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Ordinary Horse', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Paired Minds, Kindred Souls', pay: 3, target: 'myTempZone-7')
    ->register();

// no Horse ally among the top ten: no prompt, all ten go to the bottom in order
gaScenario('paired-minds-look-ten-no-horse-all-ten-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Paired Minds, Kindred Souls')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Charge the Soul'])
    ->castSpell('Paired Minds, Kindred Souls', pay: 3)
    ->register();

// PLAYER 2 seating (perspective check): the stack sits under player 2's turn-start draw (a filler Dungeon Guide); player 2 first
// declines the two opportunity windows player 1's pass opens
gaScenario('paired-minds-player-two-look-ten-reveal-deep-horse-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Paired Minds, Kindred Souls', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Ordinary Horse', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'], player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Paired Minds, Kindred Souls', 2)->pay(3, 2)->answer(2, 'myTempZone-6')
    ->register();

// --- Pure Cytosynth (172utOanGk): "[Dante Bonus] On Enter: Put the top three cards of your deck into your graveyard. Then empower X, where X is the
// amount of water element cards in your graveyard." Dante Bonus: the champion is patched to Dante, Hemomancer with Spirit of Water in its lineage (unlocks
// EXALTED + WATER). The generated body milled with a loop of MZMove($player, "myDeck-0", "myGraveyard"); Remove() only flags a slot removed, so only the TOP
// card was ever milled (see c5ee9fd7 Wuji, 86027696 Galestream). Top four: Dungeon Guide, Spirit of Water (WATER), Fairy Whispers (the three milled), Charge the Soul
// (marker: must be the new top card). A second Spirit of Water is seeded in the graveyard, so empower X = 2 only if the MILLED water card counts.
// Notes carried over from the original definition:
//   - Spirit of Water (WATER)
//   - the second milled card is a WATER card (Spirit of Water): empower X counts it
gaScenario('pure-cytosynth-dante-bonus-mills-three-then-empowers')
    ->standardDeck('Pure Cytosynth')
    ->champion(cardName: 'Dante, Hemomancer', lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Spirit of Water')
    ->seed('myHand', 'Pure Cytosynth')
    ->deckTop(['Dungeon Guide', 'Spirit of Water', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Pure Cytosynth', pay: 3, window: 'me')
    ->register();

// --- Peer the Depths (6JMwc6cpRm): "Activate this card only during your recollection phase." The generated prereq only checked the phase (BREC), so the NON-turn player was offered it in the turn
// player's recollection response window. Both players hold a copy; in player 1's recollection phase (turn 3) player 1 passes its window and player 2 must NOT be offered Peer the Depths. ---
gaScenario('peer-the-depths-not-offered-to-non-turn-player-in-opponents-recollection')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Peer the Depths')
    ->seed('myHand', 'Peer the Depths', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Peer the Depths')
    ->register();

// Polishing Flourish (D79VKF2uOg): "Remove all sheen counters from units you control."
// Notes carried over from the original definition:
//   - CLERIC class
//   - Dungeon Guide -> myField-1
//   - opposing Dungeon Guide -> theirField-1 (its sheen is not removed)
//   - Polishing Flourish -> myHand-7
gaScenario('polishing-flourish-removes-sheen-from-your-units')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'CLERIC')
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-1', ['Counters' => ['sheen' => 3]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Counters' => ['sheen' => 2]], player: 2)
    ->seed('myHand', 'Polishing Flourish')
    ->castSpell('Polishing Flourish', pay: 2)
    ->register();

// Phantasmagoria (D3rexaXCBo): "Non-Specter cards in your graveyard lose all abilities." A graveyard card's Floating Memory is one of those abilities: with Phantasmagoria, Fast Cure in your graveyard cannot be banished to pay for
// a memory cost. (HasFloatingMemory() asked about suppression for the graveyard object's Controller, which graveyard objects do not carry, so the suppression never applied.)
// Notes carried over from the original definition:
//   - Phantasmagoria
//   - Fast Cure (floating memory) -> myGraveyard-0
//   - memory cards that pay Arondight's 2 memory directly
gaScenario('phantasmagoria-non-specter-graveyard-card-cannot-pay-memory-with-floating-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Arondight, Azure Blade
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Spirit of Water'])
    ->seed('myMastery', 'Phantasmagoria')
    ->seed('myGraveyard', 'Fast Cure')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Phantasmagoria', 'Arondight, Azure Blade')
    ->expectZoneCount('myField', 2, 1, 'Arondight was materialized with the two memory cards')
    ->expectZoneCount('myGraveyard', 1, 1, 'The Fast Cure stayed in the graveyard: it lost its Floating Memory')
    ->expectDqEmpty()
    ->register();

// Portside Pirate (6p3p5iqigc): "[Class Bonus] Banish a card with floating memory from your graveyard: Put a buff counter on CARDNAME and it gains stealth until end of turn." The printed cost has no [REST], but every
// activated ally ability implicitly rested the ally unless its id was on DoActivatedAbility()'s skipAutoRest list, so using the ability exhausted the Pirate.
// Notes carried over from the original definition:
//   - ASSASSIN class: the Class Bonus is active
//   - Portside Pirate -> myField-1
//   - Fast Cure (floating memory) -> myGraveyard-0
gaScenario('portside-pirate-ability-does-not-rest-the-pirate')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'ASSASSIN')
    ->seed('myField', 'Portside Pirate')
    ->seed('myGraveyard', 'Fast Cure')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myGraveyard-0')
    ->tested('Portside Pirate')
    ->register();

// Purification (k8ao8bki6f): "Recover 2. Then choose up to two Curse cards from your champion's lineage and discard them. For each card discarded this way, recover 2." The Curse cards are staged in the temp zone to be picked from;
// declining the second pick (PASS) skipped PurificationCurse2 (queued without dontSkipOnPass), which is what clears the staged copies, so a copy of the remaining Curse stayed in the temp zone.
// Notes carried over from the original definition:
//   - CLERIC class, two Umbra Sight (Curse) cards in the lineage, 6 damage to recover
//   - Purification -> myHand-7
gaScenario('purification-decline-second-curse-clears-the-staged-copies')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Damage' => 6, 'Subcards' => ['f15joh300z', 'f15joh300z'], 'Counters' => ['_overrides' => ['classes' => 'CLERIC']]])
    ->seed('myHand', 'Purification')
    ->play('Purification')->pay(1)->target('myTempZone-0')->pass(1)
    ->register();

// Phantom Veil (fviga4cmti): "Ally Link. (2): Linked ally gains stealth until end of turn."
// Notes carried over from the original definition:
//   - Dungeon Guide -> myField-1
//   - Phantom Veil -> myField-2
gaScenario('phantom-veil-pays-two-and-grants-the-linked-ally-stealth')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-1', ['Subcards' => ['fviga4cmti']])
    ->seed('myField', 'Phantom Veil')
    ->patch('myField-2', ['Counters' => ['linkedToAlly' => 'em6eEh9q8y']])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->pay(2)->pass(1)
    ->tested('Phantom Veil')
    ->register();

// Pride of Demiourgos (bupi7VU4of): "[Level 2+] (2), [REST]: Ignore the exalted elemental requirement for the next card you play this turn."
// Royal Oathguard is an EXALTED + NORM ally: a champion with no advanced element cannot play it until the Pride's effect is active.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (level 2)
//   - Pride of Demiourgos -> myField-1
//   - Royal Oathguard (EXALTED, NORM) -> myHand-7
gaScenario('pride-of-demiourgos-lets-the-next-exalted-card-be-played')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Tristan, Hired Blade')
    ->seed('myField', 'Pride of Demiourgos')
    ->seed('myHand', 'Royal Oathguard')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)
    ->playSlot(5)->pay(3)
    ->tested('Pride of Demiourgos')
    ->register();

// Notes carried over from the original definition:
//   - Royal Oathguard (EXALTED, NORM) -> myHand-7
gaScenario('exalted-card-is-not-playable-without-an-advanced-element')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Royal Oathguard')
    ->play('Royal Oathguard')
    ->tested('Pride of Demiourgos')
    ->expectZoneCount('myHand', 8, 1, 'Royal Oathguard (Exalted) is refused: the champion has no advanced element')
    ->expectZoneCount('myField', 1, 1, 'Nothing entered the field')
    ->expectDqEmpty()
    ->register();

gaAbilitySandbox('plated-bullet-ability-1-pays-its-printed-cost', 'Plated Bullet', classes: 'RANGER', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Plated Bullet')
    ->register();

gaAbilitySandbox('prototype-staff-ability-1-pays-its-printed-cost', 'Prototype Staff', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)->pass(1)
    ->tested('Prototype Staff')
    ->register();

// Prudent Nock (0w5bp5nuae): "Draw a card into your memory. Then you may load Prudent Nock into an Aetherwing weapon you control."
gaScenario('prudent-nock-draws-into-memory-then-loads-into-an-aetherwing')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myHand', 'Prudent Nock')
    ->play('Prudent Nock')->pay(2)->answer(1, 'YES')
    ->register();

gaScenario('pleiades-class-bonus-enter-glimpses-three')
    ->deck($gaPleiadesDeck)
    ->champion(lineage: ['Arisanna, Astral Zenith'], classes: 'RANGER')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide'])
    ->endTurn()->pass(1)
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'Top=px60u5n1do;Bottom=em6eEh9q8y,em6eEh9q8y')
    ->tested('Pleiades, Celestial Genesis')
    ->register();

gaScenario('pleiades-without-the-class-bonus-enter-does-not-glimpse')
    ->deck($gaPleiadesDeck)
    ->champion(lineage: ['Arisanna, Astral Zenith'])
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide'])
    ->endTurn()->pass(1)
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Pleiades, Celestial Genesis')
    ->register();

// Proof of Life (mes4idoihs): "[Damage 40+] (2), Banish this card from your graveyard: Wake up your champion." Its generated cost modifier also set the HAND activation cost to (2) (printed reserve cost 1).
// Notes carried over from the original definition:
//   - a rested champion with 40 damage
//   - Proof of Life -> myGraveyard-0
gaScenario('proof-of-life-graveyard-ability-wakes-the-champion')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Status' => 1, 'Damage' => 40])
    ->seed('myGraveyard', 'Proof of Life')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2)
    ->tested('Proof of Life')
    ->register();

gaScenario('proof-of-life-from-hand-costs-its-printed-one')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Proof of Life')
    ->champion(cardName: 'Jin, Undying Resolve')
    ->castSpell('Proof of Life', pay: 1)
    ->register();

// PlasmaTech Blaster (PAymR7JsNp): "Ally Link. Linked ally has ranged 2 and true sight. (3), [REST]: If linked ally is a Ranger, it becomes distant." Gawain, Chivalrous Thief (Ranger ally) is the linked ally.
// Notes carried over from the original definition:
//   - Gawain (Ranger ally) -> myField-1
//   - PlasmaTech Blaster -> myField-2
gaScenario('plasmatech-blaster-pays-three-rests-and-makes-the-linked-ranger-distant')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Gawain, Chivalrous Thief')
    ->patch('myField-1', ['Subcards' => ['PAymR7JsNp']])
    ->seed('myField', 'PlasmaTech Blaster')
    ->patch('myField-2', ['Counters' => ['linkedToAlly' => 'du50pcescf']])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('PlasmaTech Blaster', 'Gawain, Chivalrous Thief')
    ->register();

// Pouvoir Absolu (OylAWd6Tew): "[Ciel Bonus] Banish the top ten cards of your deck and put an omen counter on each of them. For the rest of the game, you may activate your omens." The generated body called a
// method that does not exist on a zone (fatal error) and never granted the omen activation. Ciel, Loyal Valet (UMBRA by override) is the champion; thirteen hand cards pay the (13); afterwards the first omen (a deck card) is played.
// Notes carried over from the original definition:
//   - -> myHand-7
gaScenario('pouvoir-absolu-banishes-ten-cards-with-omen-counters-and-lets-you-activate-them')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'nn48ne8a05', 'Subcards' => ['he6kd7hocc'], 'Counters' => ['_overrides' => ['element' => 'UMBRA']]])
    ->seed('myHand', 'Pouvoir Absolu')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->castSpell('Pouvoir Absolu', pay: 13, window: 'both')
    ->action(1, 10002, 'myBanish-0!FSM!')->pay(3)->answer(1, 'NO')
    ->tested('Pouvoir Absolu', 'Ciel, Loyal Valet')
    ->register();
