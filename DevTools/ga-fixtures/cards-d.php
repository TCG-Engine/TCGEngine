<?php
/**
 * Fixtures for cards starting with 'D', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Deflecting Edge: Sword-control activation discount + prevent 3 combat damage ---
// Deflecting Edge's discount (activationCostModifierAbilities["g7uDOmUf2u:0"] in
// GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php) requires a Sword weapon on the field
// to reduce its 1-reserve activation cost to 0 — seed Clarent, Sword of Peace (a real
// WEAPON,SWORD card) directly onto the field since no level 0 starting champion carries a
// weapon at game start.
// Play Deflecting Edge (myHand-4 with this deck/seed — verified live via
// DevTools discovery harness). With the Sword discount active, the activation costs 0
// reserve, so play goes straight from the FSM click to the opponent's fast-action response
// window (no reserve MZCHOOSE appears) and then to the target choice.
// Notes carried over from the original definition:
//   - Clarent, Sword of Peace (WEAPON,SWORD) - discount source
//   - Target choice: prevent the next 3 combat damage to your own champion (myField-0).
gaScenario('deflecting-edge-sword-discount')
    ->standardDeck('Deflecting Edge')
    ->seed('myField', 'Clarent, Sword of Peace')
    ->playSlot(4)->pass(2)->target('myField-0')
    ->tested('Deflecting Edge')
    ->register();

// --- Deployment Beacon: On Enter summons an Automaton Drone token ---
// NOTE: Samaritan's Reach was attempted first but abandoned here — its effect body
// (SamaritanReachResolve) reads the CombatAttacker/CombatAttackerPlayer/CombatTarget
// decision-queue variables, but by the time the ACTION card's effect stack finishes resolving
// (multiple EffectStackOpportunity/EffectStackActiveResponse/EffectStackOpponentResponse
// windows deep), those dqVariables-injected values had already been cleared (verified live: all
// three read back NULL from the final gamestate, and the target's Damage stayed 0), so the
// ability silently no-opped. Setting them via the setup primitive only works for effects that
// read the variable immediately upon resolution, not ones buried behind several priority
// windows — a real attack sequence was needed to test this card properly, and one was later
// added for the Kongming Pantheon Starter deck coverage batch: see the
// samaritan-reach-attacking-ally-damage fixture further down this file, which scripts a genuine
// mid-combat attack instead of injecting dqVariables directly. Deployment
// Beacon's WIND element also isn't native to the "Spirit of Fire" starting champion, so — same
// technique as the advanced-element cards above — the champion's Subcards are patched with a
// real WIND champion (Spirit of Wind) to unlock element access, even though WIND isn't in
// GetAdvancedElementNames(); CanPlayerMeetCardElementRequirements() gates any non-NORM element
// the same way, not just the nine "advanced" ones.
// Only the On Enter summon is covered; the [Class Bonus] On Leave second summon requires
// removing this card from the field afterward and is out of scope.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
gaScenario('deployment-beacon-summon-drone')
    ->standardDeck('Deployment Beacon')
    ->readyFor('Deployment Beacon')
    ->seed('myHand', 'Deployment Beacon')
    ->playSlot(6)->passBoth()
    ->tested('Deployment Beacon')
    ->register();

// --- Dream Fairy: On Enter return target opposing ally to memory ---
// Dream Fairy's element is WIND, so the starting champion's Subcards are patched with a real
// WIND champion (Spirit of Wind), same as deployment-beacon-summon-drone/reclaim-return-ally/
// aesan-protector-return-ally. Dungeon Guide is seeded onto the opponent's field as the On
// Enter target -- unlike aesan-protector-return-ally (return to hand), this sends the target
// to the OPPONENT's own memory zone, not banish or hand. The "opponents can't activate cards
// with that ally's name" name-lock and the passive Stealth keyword are not independently
// asserted here.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Dungeon Guide (ALLY) - On Enter return-to-memory target
gaScenario('dream-fairy-return-to-memory')
    ->standardDeck('Dream Fairy')
    ->readyFor('Dream Fairy')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Dream Fairy')
    ->castSpell('Dream Fairy', pay: 3, target: 'theirField-1')
    ->register();

// --- Disenchant: destroying a phantasia routes through AllyDestroyed(), firing "whenever
// destroyed" triggers (regression guard for the GeneratedAbilityOverrides.php fix) ---
// Disenchant's authored ability body (GeneratedCode/GeneratedMacroCode.php,
// zd83net7x0:0:CardActivated-1) used to manually OnLeaveField()+MZMove() the target to its
// graveyard instead of calling AllyDestroyed(), which silently skipped every "whenever a
// permanent is destroyed" trigger. GrandArchiveSim/Custom/GeneratedAbilityOverrides.php now
// overrides that handler to call AllyDestroyed() (see that file for why the override lives
// there and not in GameLogic.php). Diao Chan, Idyll Corsage is seeded onto the opponent's
// field as a canary: her "whenever a non-token object an opponent controls is destroyed, you
// may banish it; if you do, that opponent summons a Flowerbud token" ability only fires
// through DoAllyDestroyed()'s trigger dispatch, so it directly proves the fix. Disenchant's
// element is NORM, so no lineage patch is needed; a plain phantasia (Unstable Fractal) is
// seeded onto the caster's own field as the (unrestricted) "target phantasia".
// Notes carried over from the original definition:
//   - Diao Chan, Idyll Corsage - canary "whenever destroyed" trigger, opponent's side
//   - Unstable Fractal (PHANTASIA) - destroy target, caster's own side
gaScenario('disenchant-destroy-fires-diaochan-trigger')
    ->standardDeck('Disenchant')
    ->seed('theirField', 'Diao Chan, Idyll Corsage')
    ->seed('myField', 'Unstable Fractal')
    ->seed('myHand', 'Disenchant')
    ->play('Disenchant')->pay(2)->target('myField-1')->answer(2, 'YES')
    ->register();

// --- Disintegrate: destroy target ally or regalia ---
// Disintegrate's element is FIRE (matching the starting champion), so no lineage patch is
// needed. Dungeon Guide (an ALLY) is a legal target already present via natural deck draw on
// the opponent's side of a fresh game -- no additional target is seeded. Without the [Class
// Bonus] Efficiency discount, the full 8-reserve cost is paid, which needs more fuel than a
// natural hand provides, so 2 extra filler cards are seeded into hand alongside Disintegrate.
// Notes carried over from the original definition:
//   - Dungeon Guide (ALLY) - destroy target
//   - Extra reserve-payment fuel 1/2
//   - Extra reserve-payment fuel 2/2
gaScenario('disintegrate-destroy')
    ->standardDeck('Disintegrate')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Disintegrate')
    ->castSpell('Disintegrate', pay: 8, target: 'theirField-1')
    ->register();

// --- Distilled Atrophy: sacrifice for a delevel-and-damage effect ---
// Distilled Atrophy's element is NORM, so no lineage patch is needed. It's seeded directly
// onto the field with 1 age counter already present (normally only reachable via a real
// recollection-phase trigger) so the sacrifice ability's -X-level/damage-if-level<=0 effect is
// observable. The opponent's champion is level 0 by default, so -1 level brings it to -1
// (<=0), triggering 1 unpreventable damage too.
// Notes carried over from the original definition:
//   - Distilled Atrophy - ability scaling fuel
gaScenario('distilled-atrophy-delevel-damage')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Distilled Atrophy', properties: ['Counters' => ['age' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-0')
    ->tested('Distilled Atrophy')
    ->register();

// --- Disorienting Winds: return target ally to hand, draw a card ---
// Disorienting Winds' element is WIND, so the starting champion's Subcards are patched with a
// real WIND champion (Spirit of Wind), same as deployment-beacon-summon-drone/
// reclaim-return-ally. Dungeon Guide is seeded onto our own field as the return-to-hand
// target. Without the Efficiency discount (champion level 0), the full 5-reserve cost is paid.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Dungeon Guide (ALLY) - return-to-hand target
gaScenario('disorienting-winds-return-draw')
    ->standardDeck('Disorienting Winds')
    ->readyFor('Disorienting Winds')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Disorienting Winds')
    ->castSpell('Disorienting Winds', pay: 5, target: 'myField-1')
    ->register();

// --- Distilled Water: Brew (1 Herb), then Sacrifice draws a card since it was brewed ---
// Distilled Water's element is NORM, so no lineage patch is needed. Brewing is an alternate
// cost (sacrifice herbs instead of paying reserve), declared via a YESNO decision at
// materialize time. A single Herb-subtype token (Blightroot) is seeded onto the field to pay
// the Brew - One Herb cost. Sacrificing Distilled Water afterward checks the
// O1OU62Zx2Y_wasBrewed decision-queue variable set during that declare-brew flow.
// Notes carried over from the original definition:
//   - Blightroot (HERB token) - brew ingredient
gaScenario('distilled-water-brew-sacrifice-draw')
    ->standardDeck('Distilled Water')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Distilled Water')
    ->play('Distilled Water')->answer(1, 'YES')->target('myField-1')->passBoth()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->register();

// --- Dante, Hemomancer: (X), [REST]: deal X unpreventable damage to Dante and empower X ---
// Dante, Hemomancer's own activated ability is champion-identity-gated (a card-ID switch
// case, not the generic element-lineage system), so the starting champion object's CardID
// itself is patched directly to Dante, Hemomancer rather than only patching Subcards.
// Activating it opens a NUMBERCHOOSE for X (1 to CountAvailableReservePayments), then pays X
// reserve, then the ability deals X unpreventable damage to Dante and empowers him by X.
// Notes carried over from the original definition:
//   - become Dante, Hemomancer
gaScenario('dante-hemomancer-empower')
    ->standardDeck('Windslice')
    ->champion(cardName: 'Dante, Hemomancer')
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->answer(1, '2')->pay(2)->pass(1)
    ->tested('Dante, Hemomancer')
    ->register();

// --- Dante, Prodigal Swain: On Enter, summon an Elysian Test Subject token ---
// Dante, Prodigal Swain's element is NORM (always playable, no lineage patch needed) and its
// level (1) is exactly one above the default level-0 starting champion (Spirit of Fire), so
// it's a legal level-up target with no element patching. Champion-swap materialization is only
// offered through the material-phase MZMAYCHOOSE at the start of a turn, so both players end
// their first turn (P1 -> P2) to reach that prompt on P1's next turn. Its printed cost is
// 1 memory, so a filler card is seeded directly into myMemory as real cost fuel -- without it,
// QueueMaterializePayment's own affordability check silently rolls the whole attempt back via
// AutoUndoMaterializeCostFailure() (LoadVersion()), which looks like the choice was a no-op.
// Choosing Dante, Prodigal Swain completes the swap and its On Enter ability
// (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, enterAbilities["apVtyt48u3:0"])
// unconditionally summons an Elysian Test Subject token (3DCP7WmBpx) onto the field.
// Notes carried over from the original definition:
//   - filler card in memory to pay Dante, Prodigal Swain's 1-memory level-up cost
gaScenario('dante-prodigal-swain-summon-token')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Dante, Prodigal Swain
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
    ->tested('Dante, Prodigal Swain')
    ->expectProperty('myField-0', 'CardID', 'apVtyt48u3', 'The champion levels up into Dante, Prodigal Swain')
    ->expectProperty('myField-0', 'Subcards', '["LMyKyVC2O9"]', 'The previous champion (Spirit of Fire) is preserved in the new champion\'s lineage')
    ->expectProperty('myField-1', 'CardID', '3DCP7WmBpx', 'On Enter summons an Elysian Test Subject token onto the field')
    ->expectZoneCount('myMemory', 0, 1, 'The seeded memory card pays the 1-memory level-up cost')
    ->expectDqEmpty()
    ->register();

// --- Devoted Bloomweaver: [Class Bonus] On Enter, Empower 2 ---
// Devoted Bloomweaver is a TERA (advanced element) ALLY with a MAGE Class Bonus On Enter, same
// split as vernal-talisman-preserve-draw: the starting champion's Subcards are patched with a
// real TERA champion (Kongming, Fel Eidolon) for element access, and that same champion is ALSO
// physically seeded onto the field so IsClassBonusActive(["MAGE"]) is satisfied.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Kongming, Fel Eidolon (MAGE CHAMPION), physically seeded for Class Bonus
//   - pay 2-reserve cost, card 1/2
//   - pay 2-reserve cost, card 2/2
gaScenario('devoted-bloomweaver-class-bonus-empower')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myField', 'Kongming, Fel Eidolon')
    ->seed('myHand', 'Devoted Bloomweaver')
    ->castSpell('Devoted Bloomweaver', pay: 2)
    ->register();

// --- Diana, Keen Huntress: Lineage Release -- materialize a Gun from material deck ---
// Diana, Keen Huntress's Lineage Release ("LR: Materialize Gun") only becomes an activatable
// dynamic ability when she sits as a subcard of the CURRENT champion's inner lineage (not
// just present on the field), patched directly via the champion's Subcards. A real 0-memory
// NORM Gun card (Framework Sidearm) is seeded into the material zone as the LR effect's
// target -- MaterializeLogic.php's generic MATERIALIZE handler element-checks the chosen
// card (CanPlayerUseCardElement) before placing it, so an UMBRA Gun like Shadow's Twin would
// need its own lineage patch too; NORM needs none. Activating a champion's dynamic ability
// uses the same CustomInput Activate:N pattern as a field object's own activated ability,
// with N = staticAbilityCount (0, since the starting champion has no static abilities) for
// the first eligible LR subcard.
// Notes carried over from the original definition:
//   - Diana, Keen Huntress in the champion's inner lineage
//   - Framework Sidearm (NORM Gun, 0 memory) - LR materialize target
gaScenario('diana-keen-huntress-lineage-release-materialize-gun')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Diana, Keen Huntress'])
    ->seed('myMaterial', 'Framework Sidearm')
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->target('myMaterial-4')
    ->tested('Diana, Keen Huntress')
    ->register();

// --- Diana, Deadly Duelist: On Enter -- materialize a Bullet from material deck (level-up) ---
// Diana, Deadly Duelist requires Diana Lineage (leveled from a level-1 Diana champion), but
// CanChampionLevelUpIntoCard only checks the CURRENT champion's own printed CardLevel, not
// lineage (same pattern already used for lorraine-arclight-saber-static-counters), so the
// starting champion's CardID is patched directly to Diana, Keen Huntress (level 1) as the
// level-up precondition. Her On Enter ability filters the material zone specifically for the
// BULLET subtype (GeneratedMacroCode.php enterAbilities["7ozuj68m69:0"]), so a real Bullet
// (Plated Bullet, NORM/0-memory) -- not a Gun -- is seeded into the material zone as her
// materialize target.
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (level 1) - level-up precondition
//   - Diana, Deadly Duelist (level 2) - level-up target
//   - Plated Bullet (NORM Bullet, 0 memory) - On Enter materialize target
gaScenario('diana-deadly-duelist-enter-materialize-bullet')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Keen Huntress')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMaterial', 'Diana, Deadly Duelist')
    ->seed('myMaterial', 'Plated Bullet')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-4')->target('myMaterial-4')->pass(1)->pass(1)
    ->tested('Diana, Deadly Duelist')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'CardID', '7ozuj68m69', 'champion levels up into Diana, Deadly Duelist')
    ->expectProperty('p1Field-0', 'Subcards', '["e3z4pyx8bd"]', 'Diana, Keen Huntress is left behind in the champion\'s inner lineage')
    ->expectProperty('p1Field-1', 'CardID', 'l75tlzsmw3', 'On Enter: Plated Bullet materializes from the material deck')
    ->register();

// --- Diana, Duskstalker: On Enter -- becomes distant (level-up) ---
// Same level-up-bypass approach as diana-deadly-duelist-enter-materialize-bullet:
// CanChampionLevelUpIntoCard only checks the CURRENT champion's own printed CardLevel, not
// lineage, so the starting champion's CardID is patched directly to Diana, Deadly Duelist
// (level 2) as the level-up precondition, then a real level-up (via the standard
// MaterializeChoice flow) into Diana, Duskstalker (level 3) fires her On Enter naturally.
// Notes carried over from the original definition:
//   - Diana, Deadly Duelist (level 2) - level-up precondition
//   - Diana, Duskstalker (level 3) - level-up target
gaScenario('diana-duskstalker-enter-distant')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Deadly Duelist')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMaterial', 'Diana, Duskstalker')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-4')
    ->tested('Diana, Duskstalker')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'CardID', 'iq4d5vettc', 'champion levels up into Diana, Duskstalker')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'On Enter: Diana becomes distant')
    ->register();

// --- Deep Sea Beastbonder: champion +1 level as long as you control an Animal or Beast ally ---
// Deep Sea Beastbonder is WATER -- the starting champion's Subcards are patched with a real
// WATER champion (Nico, Rapture's Embrace) to unlock element access. Its static +1 level is
// unconditional (no Class Bonus gate); only [Class Bonus] Floating Memory is out of scope. Same
// indirect Glimpse-LV proof technique as beastbond-ears-level-while-animal-beast: Gray Wolf (a
// BEAST ally) is seeded onto the field so the condition is already satisfied once Deep Sea
// Beastbonder resolves, then Scry the Skies' Glimpse LV surfaces a real MZREARRANGE decision
// (invisible at the default level 0) proving the effective level is 1.
// Notes carried over from the original definition:
//   - Nico, Rapture's Embrace -- unlocks WATER
//   - Gray Wolf (BEAST)
//   - MZREARRANGE response: keep original order -- only exists because the effective level is 1.
gaScenario('deep-sea-beastbonder-level-while-animal-beast')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Deep Sea Beastbonder')
    ->seed('myHand', 'Scry the Skies')
    ->castSpell('Deep Sea Beastbonder', pay: 4)
    ->playSlot(3)->pay(1)->answer(1, 'Top=em6eEh9q8y;Bottom=')
    ->expectDqEmpty()
    ->register();

// --- Dewdrop Hares: [Class Bonus] Floating Memory ---
// Dewdrop Hares' only ability is [Class Bonus] Floating Memory (its own class is TAMER),
// same shape as savage-swing-class-bonus-floating-memory: Floating Memory only offers itself
// as a payment source for a MEMORY cost (a champion level-up), and patching the champion
// directly to the level-2 target would make leveling illegal (level-up requires strictly +1),
// so the champion is patched to Silvie, Wilds Whisperer (level 1, TAMER) instead and leveled up
// into Silvie, With the Pack (level 2, TAMER, 2-memory cost) -- the Class Bonus condition
// checks the CURRENT champion (still TAMER) at payment time, before the level-up completes.
// Dewdrop Hares pays 1 of the 2 memory via Floating Memory from the graveyard; the 2nd memory
// point is a filler card seeded directly into myMemory.
// Notes carried over from the original definition:
//   - Silvie, Wilds Whisperer (TAMER, level 1) - Class Bonus precondition + legal level-up base
//   - Dewdrop Hares
//   - filler memory card, 2nd point of Silvie, With the Pack's 2-memory level-up cost
//   - select Silvie, With the Pack as the level-up target
//   - pay 1 memory via Floating Memory
//   - pay the 2nd memory point
gaScenario('dewdrop-hares-class-bonus-floating-memory')
    ->deckTemplate('silvie-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, Wilds Whisperer')
    ->seed('myGraveyard', 'Dewdrop Hares')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')->target('myMemory-0')
    ->tested('Dewdrop Hares')
    ->expectProperty('p1Field-0', 'CardID', 'nllCALIXDT', 'The champion successfully levels up, partly paid by Dewdrop Hares\' Floating Memory')
    ->expectZoneCount('myGraveyard', 0, 1, 'Dewdrop Hares is banished from the graveyard to pay 1 of the memory cost')
    ->expectDqEmpty()
    ->register();

// --- Disenchant: Destroy target phantasia ---
// Disenchant's element is NORM, so no lineage patch is needed. Scorching Imperilment
// (aj7pz79wsp, a PHANTASIA card) is seeded onto the opponent's field as the destroy target.
// Notes carried over from the original definition:
//   - Scorching Imperilment (PHANTASIA), destroy target
gaScenario('disenchant-destroy-phantasia')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('theirField', 'Scorching Imperilment')
    ->seed('myHand', 'Disenchant')
    ->castSpell('Disenchant', pay: 2, target: 'theirField-1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'zd83net7x0')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Graveyard-0', 'CardID', 'aj7pz79wsp', 'Target phantasia is destroyed, moving to its owner\'s graveyard')
    ->expectProperty('p2Graveyard-0', 'Status', '2')
    ->expectDamage('p2Graveyard-0', 0)
    ->expectProperty('p2Graveyard-0', 'Counters', '[]')
    ->expectProperty('p2Graveyard-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->expectZoneCount('theirField', 1, 1, 'The phantasia leaves the opponent\'s field (down from 2)')
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Dematerialize: Return target regalia to its owner's material deck ---
// Dematerialize (b1k0zi5h8a, WIND) is played straight from hand (seeded at myHand-7, the
// starting-hand size for this deck/seed) against a Backup Charger (9gv4vm4kj3, REGALIA)
// seeded onto the opponent's field. Its reserve cost (3) is paid from hand
// (cardActivatedAbilities["b1k0zi5h8a:0"], GeneratedMacroCode.php), then both players decline
// the fast-opportunity response window before its own MZCHOOSE targets the regalia and moves
// it to theirMaterial (customDQHandlers["b1k0zi5h8a:0:CardActivated-1"]).
gaScenario('dematerialize-return-regalia')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->seed('myField', 'Backup Charger', player: 2)
    ->seed('myHand', 'Dematerialize')
    ->castSpell('Dematerialize', pay: 3, window: 'both', target: 'theirField-1')
    ->expectDqEmpty()
    ->expectZoneCount('theirField', 1, 1)
    ->expectZoneCount('myMaterial', 12, 2)
    ->expectProperty('p2Material-11', 'CardID', '9gv4vm4kj3', 'The targeted regalia moves from the opponent\'s field back to their material deck.')
    ->register();

// --- Drawn Blade: [Class Bonus] On Enter: Draw a card ---
// Drawn Blade (eSAIP7mx9z, WARRIOR [Class Bonus], 1-memory REGALIA already in this deck's
// Material zone at myMaterial-4) is materialized for real: the champion is patched to
// Lorraine, Wandering Warrior (DpHDGaX2Pn) for the class bonus, a filler card is seeded into
// memory to pay its 1-memory cost, and both players end their first turns to reach a real
// Materialize opportunity on player 1's second turn. enterAbilities["eSAIP7mx9z:0"]
// (GeneratedMacroCode.php) then draws a card, observable as the hand gaining a card beyond
// what materializing alone would produce.
gaScenario('drawn-blade-class-bonus-draw')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(cardName: 'Lorraine, Wandering Warrior')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-4')
    ->tested('Drawn Blade')
    ->expectZoneCount('myHand', 8, 1, 'Player 1\'s hand ends at 8 cards -- 7 (materialize draws none) + the extra card from Drawn Blade\'s own On Enter draw.')
    ->expectProperty('p1Field-1', 'CardID', 'eSAIP7mx9z')
    ->register();

// --- Dazzling Courtesan: Kindle 3 -- may banish up to 3 fire cards from graveyard to pay its cost ---
// Dazzling Courtesan is registered in $Kindle_Cards["znk6g5o8ys"] = 3 (GrandArchiveSim/Custom/
// GameLogic.php) -- its reserve-3 cost is paid entirely by banishing up to 3 FIRE cards from
// the graveyard instead of reserving hand cards (the KindleChoose/KindleProcess CUSTOM DQ
// handlers in GameLogic.php). Three copies of Escharotomy (CIU4gT14EE, FIRE) are seeded into
// the graveyard so all 3 Kindle payments are available; choosing all three at the repeated
// "Kindle: banish fire card from GY to reduce cost?" MZMAYCHOOSE fully pays the cost, so no
// ReserveCard (hand-card) payment decisions appear at all and the hand count never drops
// below its starting size (only the played card itself leaves hand, via the normal FSM play,
// not any reserve payment).
// Notes carried over from the original definition:
//   - Escharotomy (FIRE) 1/3
//   - Dazzling Courtesan
//   - play Dazzling Courtesan
//   - Kindle: banish fire card 1/3
//   - Kindle: banish fire card 2/3
//   - Kindle: banish fire card 3/3 -- cost fully paid
gaScenario('dazzling-courtesan-kindle-banish-fire-gy')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myGraveyard', 'Escharotomy')
    ->seed('myGraveyard', 'Escharotomy')
    ->seed('myGraveyard', 'Escharotomy')
    ->seed('myHand', 'Dazzling Courtesan')
    ->play('Dazzling Courtesan')->target('myGraveyard-0')->target('myGraveyard-1')->target('myGraveyard-0')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'CardID', 'znk6g5o8ys')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Banish-0', 'CardID', 'CIU4gT14EE')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-1', 'CardID', 'CIU4gT14EE')
    ->expectProperty('p1Banish-1', 'Counters', '[]')
    ->expectProperty('p1Banish-1', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-2', 'CardID', 'CIU4gT14EE')
    ->expectProperty('p1Banish-2', 'Counters', '[]')
    ->expectProperty('p1Banish-2', 'TurnEffects', '[]')
    ->expectZoneCount('myHand', 7, 1, 'Hand size stayed at its starting count of 7 (only the played Dazzling Courtesan itself left hand) -- the KindleChoose/KindleProcess handlers (GameLogic.php) paid the entire reserve-3 cost by banishing 3 FIRE graveyard cards instead of queuing any ReserveCard (hand-card) payment decisions')
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myGraveyard', 0, 1, 'All 3 seeded FIRE graveyard cards were consumed by Kindle 3\'s banish-to-pay-cost loop (moved to Banish, not left in the graveyard)')
    ->register();

// --- Dissuading Halt: target unit's attacks get -3 POWER until end of turn ---
// cardActivatedAbilities["y7wbtbasch:0"] calls DissuadingHaltStart (CardDQHandlers.php ~32),
// which offers an MZCHOOSE of ALLY/CHAMPION/PHANTASIA units on either field and tags the
// chosen one with TurnEffect "y7wbtbasch-debuff" (customDQHandlers["DissuadingHaltApply"]).
// That TurnEffect is only consumed by the POWER switch in GameLogic.php (~13024) when the
// object IS the current "CombatAttacker" -- so Dissuading Halt is played as a FAST response
// during player 1's Dungeon Guide's own real attack (same fast-opportunity shape as
// Chill to the Bone), targeting that same attacking Dungeon Guide directly (legal since
// it's currently on the field, not yet removed).
//
// Because player 2 holds a FAST card (Dissuading Halt) for the whole game, every phase
// transition (ending turn 1, ending turn 2, the turn-3 materialize offer) independently
// grants player 2 a "Take_a_fast_action?" window with CombatAttacker not yet live -- each
// must be explicitly declined ('-') the same way as chill-to-the-bone-low-phantasia-debuff
// (see its comment for the full writeup); confirmed via direct instrumentation that only the
// window appearing right after player 1's real attack target is chosen (CombatAttacker
// confirmed live) is the real one this fixture takes.
// Notes carried over from the original definition:
//   - Dungeon Guide ALLY, seeded awake -- the attacker and the debuff target
//   - Spirit of Water - unlocks WATER for Dissuading Halt
//   - Dissuading Halt
//   - ends turn 1 (first-player attack lock)
//   - decline the stray fast-opportunity window from ending turn 1 (1/2)
//   - decline the stray fast-opportunity window from ending turn 1 (2/2)
//   - ends turn 2
//   - decline the stray fast-opportunity window from ending turn 2 (1/2)
//   - decline the stray fast-opportunity window from ending turn 2 (2/2) -- lands on the turn-3 MAT offer
//   - decline turn-3 MAT offer
//   - decline the stray fast-opportunity window opened by declining the MAT offer
//   - Dungeon Guide declares a real attack
//   - target the opposing champion -- CombatAttacker='myField-1' is now genuinely live
//   - THIS is the real combat-damage-step fast-opportunity window (CombatAttacker confirmed
//   - live via direct instrumentation at this exact point) -- take it for real.
//   - player 2 takes the fast-opportunity action: Dissuading Halt
//   - pay reserve 1/2
//   - pay reserve 2/2
//   - choose the attacking Dungeon Guide (player 1's myField-1) as the debuff target
gaScenario('dissuading-halt-attack-power-reduction')
    ->deck($diaoChanDeck)
    ->seed('myField', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Water'], player: 2)
    ->seed('myHand', 'Dissuading Halt', player: 2)
    ->endTurn()->answer(2, '-')->answer(2, '-')
    ->endTurn(2)->answer(2, '-')->answer(2, '-')->answer(1, '-')->answer(2, '-')
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')->answer(2, 'myHand-7')->pay(2, 2)
    ->answer(2, 'theirField-1')
    ->tested('Dissuading Halt')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Status', '1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["y7wbtbasch-debuff"]', 'The attacking Dungeon Guide is tagged with the Dissuading Halt debuff')
    ->expectDamage('p2Field-0', 0, 'Dungeon Guide\'s attack (printed POWER 1) dealt exactly 0 damage to the defending champion instead of 1 -- Dissuading Halt\'s -3 POWER debuff (1-3=-2, clamped to 0) actually reduced real combat damage, not just tagged a cosmetic flag. Baseline: an undebuffed Dungeon Guide attack deals exactly 1 damage (see tactful-sergeant-enter-attacked-draw-memory\'s p2Field-0 Damage=1 assertion for the same attacker/target shape without this card in play)')
    ->expectProperty('p1Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p2Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-5', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-0', 'CardID', 'y7wbtbasch')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('theirHand', 6, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 2, 1)
    ->register();

// --- Diao Chan, Dreaming Wish: Inherited -- at end phase, glimmer counters catch up to phantasia count ---
// Diao Chan, Dreaming Wish's Inherited Effect (GrandArchiveSim/Custom/GameLogic.php ~11015-
// 11030, inside the shared beginning-of-end-phase hook) checks
// ChampionHasInLineage($turnPlayer, "pknaxnn0xo"), counts PHANTASIA-type objects the turn
// player controls on their own field, and -- only if that count is GREATER than the champion's
// current "glimmer" counter count -- calls AddCounters($turnPlayer, $champMZ, "glimmer",
// $phantasiaCount - $glimmerCount), i.e. it adds the difference rather than overwriting the
// total. The champion's CardID is patched directly to pknaxnn0xo (same patchMzId technique as
// the Reaping Legacy/Kongming fixtures, bypassing a scripted real level-up sequence) with an
// existing "glimmer" counter of 1 pre-seeded, and 3 non-champion PHANTASIA objects (Unstable
// Fractal, NORM/CLERIC) are seeded directly onto the champion's own field. Ending player 1's
// turn 1 (myHealth-0!CustomInput!Pass) passes through player 1's own end phase, where the
// Inherited Effect fires: phantasiaCount(3) > glimmerCount(1), so 3-1=2 glimmer counters are
// added, leaving a final count of 3 (1+2) -- distinguishing "add the difference" from a
// hypothetical "set counters to the phantasia count" implementation only insofar as the
// starting count was already nonzero and the final total is the sum, not a fresh assignment.
// Notes carried over from the original definition:
//   - Diao Chan, Dreaming Wish, pre-seeded with 1 glimmer counter
//   - Unstable Fractal (PHANTASIA) #1
//   - Unstable Fractal (PHANTASIA) #2
//   - Unstable Fractal (PHANTASIA) #3
//   - ends turn 1, passing through player 1's own end phase
gaScenario('diao-chan-dreaming-wish-end-phase-glimmer-catchup')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fluffy Shopkeep
DECK
    )
    ->patch('myField-0', ['CardID' => 'pknaxnn0xo', 'Counters' => ['glimmer' => 1]])
    ->seed('myField', 'Unstable Fractal')
    ->seed('myField', 'Unstable Fractal')
    ->seed('myField', 'Unstable Fractal')
    ->endTurn()
    ->tested('Diao Chan, Dreaming Wish')
    ->expectProperty('p1Field-0', 'CardID', 'pknaxnn0xo')
    ->expectProperty('p1Field-1', 'CardID', '2o82fwl22v')
    ->expectProperty('p1Field-2', 'CardID', '2o82fwl22v')
    ->expectProperty('p1Field-3', 'CardID', '2o82fwl22v')
    ->expectProperty('p1Field-0', 'Counters', '{"glimmer":3}', 'Diao Chan, Dreaming Wish\'s Inherited Effect added the difference (3 phantasias - 1 existing glimmer = 2) at end phase, leaving a final total of 3 rather than staying at 1')
    ->register();

// --- Diao Chan, Idyll Corsage: On Enter wither choice + destroyed-opponent-object banish/Flowerbud ---
// Idyll Corsage's own On Enter (enterAbilities["d7l6i5thdy:0"], GeneratedMacroCode.php ~10759 +
// customDQHandlers["d7l6i5thdy:0:Enter-1"] ~29923) is reached via a REAL level-up (same
// patchMzId + myMemory-seed technique as kongming-ascetic-vice-enter-empower-and-inherited-draw):
// the champion's CardID is patched to Diao Chan, Dreaming Wish (pknaxnn0xo, level 2), which
// satisfies CanChampionLevelUpIntoCard's targetLevel===currentLevel+1 gate (GameLogic.php
// ~20009-20023, no lineage-name check, just level+1), 3 filler cards are seeded into myMemory to
// pay Idyll Corsage's own 3-memory level-up cost, and myMaterial-1 (Idyll Corsage, present in
// this deck's own Material list) is chosen as a real main-phase action, actually materializing
// her and firing her real On Enter. Player 2 keeps their default Level 0 champion (Spirit of
// Fire) and has an Unstable Fractal (PHANTASIA, non-token, non-champion) seeded directly onto
// their own field as the On Enter's wither target -- the MZMULTICHOOSE decision (built by
// filtering out CHAMPION-type objects from both fields) is answered by selecting it. (An
// earlier version of this fixture also seeded a same-turn ally onto player 1's own side to
// exercise "any amount" with two targets, but that additionally put a wither counter on our own
// permanent, which immediately triggers the engine's separate, unrelated general Wither Upkeep
// mechanic -- CardDQHandlers.php's WitherUpkeep()/WitherUpkeepProcess -- an unrelated
// decision-queue detour with no bearing on Idyll Corsage's own ability. Testing a single-object
// choice avoids that entirely while still proving the choose-and-wither clause.)
//
// For the "whenever a non-token object an opponent controls is destroyed" clause, a SEPARATE
// plain item (ChannelTech Charm S, rR2j9dQRDH, NORM ITEM, non-regalia so Break Apart's "+2 if
// it targets a regalia" surcharge doesn't apply; its own On Enter just auto-draws a card into
// memory with no interactive decision, unlike Business Card, which was tried first and found to
// queue a blocking NAMECARD decision for player 2 the instant it's seeded onto the field via
// this fixture-authoring tool's raw zone-seed, silently stalling every subsequent action) is
// seeded onto player 2's field and destroyed via
// Break Apart (4ns2jbt4hq, NORM ACTION, "Destroy target item or weapon") played from player 1's
// hand -- a real non-attack removal spell, sidestepping Rule 1.h's first-player attack lock
// entirely. Break Apart's own generated handler calls AllyDestroyed() -> DoAllyDestroyed()
// (GeneratedMacroCode.php customDQHandlers["4ns2jbt4hq:0:CardActivated-1"], ZoneAccessors.php
// AllyDestroyed()), the shared destroy-event dispatcher that Idyll Corsage's own trigger lives
// inside (GameLogic.php ~7976-7996) -- this is deliberately NOT Disenchant ("Destroy target
// phantasia"), which was tried first and found to bypass that dispatcher entirely (see the
// ENGINE BUG note in this fixture's meta.json notes). Destroying ChannelTech Charm S this way
// correctly fires the trigger: player 1 (Idyll Corsage's controller) is offered a YESNO to
// banish the destroyed card and give its controller (player 2) a Flowerbud token; answering YES
// resolves customDQHandlers["DiaoChanIdyllBanish"] (CardDQHandlers.php ~5386), moving the item
// to player 2's banishment and summoning a Flowerbud token (yn78t73w1p, TOKEN,PHANTASIA) onto
// player 2's own field.
// Notes carried over from the original definition:
//   - Diao Chan, Dreaming Wish (level 2), satisfies the level-2->3 gate
//   - pays the 3-memory level-up cost, card 1/3
//   - Unstable Fractal (PHANTASIA), player 2's own -- On Enter wither target
//   - ChannelTech Charm S (plain, non-regalia ITEM), player 2's own -- Break Apart destroy target
//   - ends turn 1
//   - ends turn 2
//   - level up into Diao Chan, Idyll Corsage (Material reindexes to 0 once Spirit of Fire is moved to Field by pregame setup), fires On Enter wither choice
//   - choose player 2's Unstable Fractal to wither
//   - play Break Apart
//   - pay 3-reserve cost, card 1/3
//   - pay 3-reserve cost, card 2/3
//   - pay 3-reserve cost, card 3/3
//   - target player 2's ChannelTech Charm S
//   - banish the destroyed item and give player 2 a Flowerbud token
gaScenario('diao-chan-idyll-corsage-enter-wither-and-flowerbud')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Diao Chan, Idyll Corsage
# Main
4 Dungeon Guide
4 Fluffy Shopkeep
DECK
    )
    ->champion(cardName: 'Diao Chan, Dreaming Wish')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myField', 'Unstable Fractal', player: 2)
    ->seed('myField', 'ChannelTech Charm S', player: 2)
    ->seed('myHand', 'Break Apart')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('theirField-1')
    ->play('Break Apart')->pay(3)->target('theirField-2')->answer(1, 'YES')
    ->tested('Diao Chan, Idyll Corsage')
    ->expectProperty('p1Field-0', 'CardID', 'd7l6i5thdy')
    ->expectProperty('p2Field-1', 'CardID', '2o82fwl22v')
    ->expectProperty('p2Field-1', 'Counters', '{"wither":1}', 'Idyll Corsage\'s On Enter put a wither counter on the chosen non-champion object (player 2\'s Unstable Fractal)')
    ->expectProperty('p2Field-2', 'CardID', 'yn78t73w1p', 'Destroying a non-token object player 2 controls (ChannelTech Charm S) and choosing to banish it summoned a Flowerbud token onto player 2\'s own field, per Idyll Corsage\'s second clause')
    ->expectProperty('p2Banish-0', 'CardID', 'rR2j9dQRDH', 'The destroyed ChannelTech Charm S was banished (not left in the graveyard), matching \'you may banish it\'')
    ->register();

// --- Diao Chan, Enchantress: On Enter put 2 glimmer counters, via genuine mid-game level-up ---
// NOTE: this fixture starts from the deck's real level-0 champion (Spirit of Water) and levels up
// into Diao Chan, Enchantress (level 1) during replay via the material-phase MZMAYCHOOSE, same
// pattern as arisanna-herbalist-prodigy-on-enter-gather-twice. An earlier version of this fixture
// seeded Enchantress directly as the ONLY Material card, which -- since she has no printed level-0
// predecessor -- made GetStartingChampionChoices() fall back to offering her directly as the
// pregame starting champion. create-prd-ability-fixtures.php's own step 4a resolves that pregame
// choice (and the real Enter() call it triggers) BEFORE the fixture's initial gamestate is even
// captured, so her On Enter glimmer counters were already baked into initial_gamestate.txt as
// static data -- confirmed via instrumentation and break/restore: disabling
// enterAbilities["00xbh8oc00:0"]'s AddCounters call and re-running the OLD fixture still passed,
// proving the assertion never actually re-exercised the closure. This version instead lets Spirit
// of Water resolve normally as the pregame champion, then genuinely levels up into Enchantress
// as a replayed action -- re-verified: disabling the same AddCounters call now correctly fails
// this fixture (Counters stay empty), and restoring it passes again.
// Notes carried over from the original definition:
//   - filler card in memory to pay the 1-memory level-up cost
//   - level up into Diao Chan, Enchantress -- fires her real On Enter
gaScenario('diao-chan-enchantress-enter-glimmer')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Diao Chan, Enchantress
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Diao Chan, Enchantress')
    ->expectProperty('p1Field-0', 'CardID', '00xbh8oc00', 'The level-up into Diao Chan, Enchantress completed', step: 3)
    ->expectProperty('p1Field-0', 'Counters', '{"glimmer":2}', "Diao Chan, Enchantress's On Enter (enterAbilities[\"00xbh8oc00:0\"]) put exactly two glimmer counters on herself when she levelled up into the champion slot", step: 3)
    ->register();

// --- Dormouse Informant (aWjXOw4mVK): On Enter, if your champion has no preparation counters, put one on them. ---
gaScenario('dormouse-informant-enter-prep')
    ->deckTemplate('spirit-dungeon-guide', 'Dormouse Informant')
    ->playSlot(0)->pay(3)->passBoth()
    ->tested('Dormouse Informant')
    ->register();

// --- Dusksoul Stone: as an additional cost to materialize it, banish two ally cards from a single graveyard ---
// Dusksoul Stone (u25fuv184p, UMBRA REGALIA/BAUBLE, memory 0): "As an additional cost to
// materialize this card, banish two ally cards from a single graveyard." MaterializeLogic.php's
// MATERIALIZE decision queues an MZCHOOSE over ally cards in a graveyard that holds at least two
// of them (here player 1's own graveyard, or player 2's), then a second pick restricted to the
// same graveyard (DusksoulStoneMaterializeCost / ...Finish). The Stone is materialized for real in
// the materialize phase (champion Subcards patched with Tristan, Shadowdancer for UMBRA); a non-
// ally graveyard card (Fairy Whispers) is rejected as a choice, two Dungeon Guide ally cards are
// banished, and the Stone enters.
// Notes carried over from the original definition:
//   - UMBRA lineage unlock (Tristan, Shadowdancer)
//   - Dungeon Guide (ally card) -> p1 gy-0
//   - Dungeon Guide (ally card) -> p1 gy-1
//   - Fairy Whispers (NOT an ally card) -> p1 gy-2
//   - Fairy Whispers -> p2 gy-0
//   - Fluffy Shopkeep -> p2 gy-1
//   - Dungeon Guide -> p2 gy-2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - start materializing Dusksoul Stone (memory cost 0, additional banish cost)
//   - banish ally card 1/2 from my graveyard
//   - banish ally card 2/2 from the SAME graveyard
//   - pass the window offering the Stone's own ability
gaScenario('dusksoul-stone-materialize-additional-cost-banish-two-allies')
    ->deck($tristanDeck)
    ->champion(lineage: ['Tristan, Shadowdancer', 'Spirit of Wind'])
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Fairy Whispers')
    ->seed('myGraveyard', 'Fairy Whispers', player: 2)
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->endTurn(2)->target('myMaterial-4')
    ->rejects(1, 'myGraveyard-2', 'Fairy Whispers is not an ally card, so it is not a legal Dusksoul Stone materialize-cost choice')
    ->target('myGraveyard-0')->target('myGraveyard-0')->pass(1)
    ->tested('Dusksoul Stone')
    ->expectProperty('p1Field-1', 'CardID', 'u25fuv184p', 'Dusksoul Stone entered the field via a real materialize')
    ->expectProperty('p1Banish-0', 'CardID', 'em6eEh9q8y', 'First ally card banished as the additional cost')
    ->expectProperty('p1Banish-1', 'CardID', 'em6eEh9q8y', 'Second ally card banished as the additional cost')
    ->expectZoneCount('myBanish', 2, 1, 'Exactly two cards banished')
    ->expectProperty('p1Graveyard-0', 'CardID', 'n8wyfG9hbY', 'The non-ally Fairy Whispers stays in the graveyard (it could not be chosen)')
    ->expectZoneCount('theirGraveyard', 3, 1, 'The opponent\'s graveyard was untouched: both cost cards came from a single graveyard')
    ->expectDqEmpty()
    ->register();

// --- Dusksoul Stone: Banish CARDNAME: banish up to two cards from a single graveyard (the Banish cost IS paid) ---
// Dusksoul Stone (u25fuv184p): "Banish CARDNAME: Banish up to two cards from a single graveyard."
// The Stone is seeded on the field (the REGALIA is seeded directly because only its ability, not
// its materialize cost, is under test; see dusksoul-stone-materialize-additional-cost-banish-two-
// allies for the cost). The opportunity window offers 'myField-1@Activate-0@Banish';
// DusksoulStoneActivated() asks for a first card from either graveyard (an MZMAYCHOOSE) and then a
// second one restricted to the same graveyard (DusksoulStoneBanishStart/Finish): picking the
// opponent's Fluffy Shopkeep first makes player 1's own graveyard card an invalid second choice
// (rejected), and a second opposing card is banished. FIXED (was: the 'Banish CARDNAME' cost was
// never paid -- the Stone itself stayed on the field and stayed offered in the window):
// ActivatedAbilityCost()'s 'banish self' switch now has a u25fuv184p entry, so the Stone leaves the
// field for the banishment zone at activation (it can no longer be offered or activated again).
// Notes carried over from the original definition:
//   - UMBRA lineage unlock (Tristan, Shadowdancer)
//   - Dusksoul Stone -> p1 field-1
//   - Fairy Whispers -> p1 gy-0
//   - Fairy Whispers -> p2 gy-0
//   - Fluffy Shopkeep -> p2 gy-1
//   - Dungeon Guide -> p2 gy-2
//   - end player 1 turn 1
//   - activate Dusksoul Stone's Banish ability from the priority window
//   - first banish: the opponent's Fluffy Shopkeep
//   - second banish: the opponent's Dungeon Guide (same graveyard)
gaScenario('dusksoul-stone-banish-ability-banishes-itself-and-two-graveyard-cards')
    ->deck($tristanDeck)
    ->champion(lineage: ['Tristan, Shadowdancer', 'Spirit of Wind'])
    ->seed('myField', 'Dusksoul Stone')
    ->seed('myGraveyard', 'Fairy Whispers')
    ->seed('myGraveyard', 'Fairy Whispers', player: 2)
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->endTurn()->answer(1, 'myField-1@Activate-0@Banish')->target('theirGraveyard-1')
    ->rejects(1, 'myGraveyard-0', 'The second card must come from the SAME graveyard as the first (the opponent\'s), so my own graveyard card is rejected')
    ->rejects(1, 'theirGraveyard-2', 'Only two opposing graveyard cards remain (slots 0 and 1): the stale third slot of the pre-banish graveyard is not a legal pick')
    ->target('theirGraveyard-1')
    ->tested('Dusksoul Stone')
    ->register();

// --- Dusksoul Stone: declining both graveyard picks still pays the Banish CARDNAME cost ---
// Dusksoul Stone (u25fuv184p): "Banish CARDNAME: Banish up to two cards from a single graveyard."
// "Up to two" includes zero: the cost (banishing the Stone) is paid when the ability is activated,
// before any graveyard card is chosen, so declining the first optional pick (PASS) leaves every
// graveyard untouched but the Stone is already in the banishment zone.
// Notes carried over from the original definition:
//   - UMBRA lineage unlock (Tristan, Shadowdancer)
//   - Dusksoul Stone -> p1 field-1
//   - Fairy Whispers -> p1 gy-0
//   - Fluffy Shopkeep -> p2 gy-0
//   - end player 1 turn 1
//   - activate Dusksoul Stone's Banish ability
//   - decline to banish any graveyard card
gaScenario('dusksoul-stone-banish-ability-declined-picks-still-pays-cost')
    ->deck($tristanDeck)
    ->champion(lineage: ['Tristan, Shadowdancer', 'Spirit of Wind'])
    ->seed('myField', 'Dusksoul Stone')
    ->seed('myGraveyard', 'Fairy Whispers')
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->endTurn()->answer(1, 'myField-1@Activate-0@Banish')->pass(1)
    ->tested('Dusksoul Stone')
    ->register();

// --- Dusksoul Stone: phantasias you control have spellshroud (a Spell cannot target Ominous Shadow) ---
// Dusksoul Stone (u25fuv184p): "Phantasias you control have spellshroud. (Objects with spellshroud
// can't be targeted by Spells.)" Player 1 controls the Stone and an Ominous Shadow (a phantasia
// ally); player 2 plays Disenchant ("Destroy target phantasia", a Spell) for real. Disenchant's
// target list goes through FilterSpellshroudTargets(), which consults HasSpellshroud() (the
// Stone's static clause in Custom/GameLogic.php), so the Shadow is not a legal target, the ability
// finds no target and resolves without a prompt: the Shadow survives and the decision queue is
// empty (without the Stone the same Disenchant would prompt for a target).
// Notes carried over from the original definition:
//   - UMBRA lineage unlock (Tristan, Shadowdancer)
//   - Dusksoul Stone -> p1 field-1
//   - Ominous Shadow (phantasia) -> p1 field-2
//   - Disenchant (Destroy target phantasia) -> p2 myHand-7
//   - end player 1 turn 1
//   - p1 passes a window (the Stone's ability keeps windows open)
//   - p1 passes a window
//   - player 2 plays Disenchant
//   - p1 passes priority with Disenchant on the stack
gaScenario('dusksoul-stone-phantasias-have-spellshroud')
    ->deck($tristanDeck)
    ->champion(lineage: ['Tristan, Shadowdancer', 'Spirit of Wind'])
    ->seed('myField', 'Dusksoul Stone')
    ->seed('myField', 'Ominous Shadow')
    ->seed('myHand', 'Disenchant', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->play('Disenchant', 2)->pay(2, 2)->pass(1)
    ->tested('Dusksoul Stone')
    ->expectProperty('p1Field-2', 'CardID', 'gveirpdm44', 'The Ominous Shadow survived: it could not be targeted by the Disenchant Spell')
    ->expectZoneCount('myField', 3, 1, 'Champion, Stone and Shadow all remain')
    ->expectProperty('p2Graveyard-0', 'CardID', 'zd83net7x0', 'Disenchant fully resolved (it is in the graveyard) without ever prompting for a target')
    ->expectDqEmpty()
    ->register();

// Dungeon Guide (em6eEh9q8y, NORM ally, reserve 3) alone in an otherwise empty hand: zero payers.
// Notes carried over from the original definition:
//   - start from an empty hand (7 cards -> graveyard)
//   - Dungeon Guide (reserve 3) -> p1 myHand-0
//   - 1: click the Dungeon Guide with nothing to pay with: accepted as a no-op, refusal asserted on state
gaScenario('hand-activation-unaffordable-no-payers-click-refused')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->playSlot(0)
    ->tested('Dungeon Guide')
    ->register();

// Dungeon Guide with two other hand cards (reserve 3) and THREE memory cards: one payer short, and the
// memory cards must not count as payers.
// Notes carried over from the original definition:
//   - Dungeon Guide (reserve 3) -> p1 myHand-0
//   - Fluffy Shopkeep -> p1 myHand-1
//   - Fluffy Shopkeep -> p1 myHand-2
//   - three memory cards: NOT a payment source
//   - 1: click the Dungeon Guide (2 payers for reserve 3): accepted as a no-op, refusal asserted on state
gaScenario('hand-activation-unaffordable-one-payer-short-click-refused')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->playSlot(0)
    ->tested('Dungeon Guide')
    ->register();

// The reported "pays a few reserve cards, then stuck" repro, driven with real actions: a Dungeon Guide
// is played for real (3 payments; the 4 seeded Guides/Fluffy leave ONE card in hand) and the leftover
// card, whose reserve cost 3 can no longer be paid, is clicked. The refused click must also leave the
// undo snapshot taken when the Dungeon Guide was played untouched.
// Notes carried over from the original definition:
//   - Dungeon Guide -> p1 myHand-0
//   - Fluffy Shopkeep (the leftover) -> p1 myHand-4
//   - 1: play a Dungeon Guide
//   - 5: decline the Dungeon Guide's optional On Enter
//   - 6: click the Fluffy Shopkeep (reserve 3, no payer left): accepted as a no-op, refusal asserted on state
//   - 7: click it again (the engine re-reads the saved state between actions, so the undo snapshot is observable here)
gaScenario('hand-activation-unaffordable-leftover-after-real-play-click-refused')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)->pay(3)->answer(1, 'NO')
    ->playSlot(0)
    ->playSlot(0)
    ->tested('Dungeon Guide', 'Fluffy Shopkeep')
    ->register();

// Positive pin: the same card with EXACTLY enough payers (3 other hand cards for reserve 3) still activates.
// Notes carried over from the original definition:
//   - Dungeon Guide (reserve 3) -> p1 myHand-0
//   - 1: play the Dungeon Guide
//   - 5: decline the Dungeon Guide's optional On Enter
gaScenario('hand-activation-exactly-affordable-still-activates')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)->pay(3)->answer(1, 'NO')
    ->tested('Dungeon Guide')
    ->register();

// Devotion's Price (ri955ygd5v, UMBRA spell, reserve 2): "As an additional cost, discard two cards." With one other card in
// hand the additional cost cannot be paid. The champion is patched to an UMBRA champion so the element is usable.
// Notes carried over from the original definition:
//   - Diana, Duskstalker (UMBRA)
//   - Devotion's Price -> p1 myHand-0
//   - the only other card
//   - 1: click Devotion's Price with one other card in hand: accepted as a no-op, refusal asserted on state
gaScenario('devotions-price-cannot-discard-two-click-refused')
    ->deck($mordredDeck)
    ->champion(cardName: 'Diana, Duskstalker', lineage: ['Spirit of Water'])
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Devotion\'s Price')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)
    ->tested('Devotion\'s Price')
    ->register();

// --- Dredging Streams: banish target card from a graveyard (your own) ---
// Dredging Streams (wmt0x5zado, WATER CLERIC SPELL action, reserve 2): "Banish target card from a
// graveyard." A Dungeon Guide is seeded in each player's graveyard and Dredging Streams is played
// for real (2 reserve payments); the target prompt (cardActivatedAbilities["wmt0x5zado:0"]) lists
// the cards of both graveyards, player 1's own is chosen and moves to player 1's banishment.
// Notes carried over from the original definition:
//   - Dungeon Guide -> p1 graveyard-0
//   - Fluffy Shopkeep -> p2 graveyard-0
//   - Dredging Streams -> p1 myHand-7
//   - play Dredging Streams
//   - target: player 1's own Dungeon Guide
gaScenario('dredging-streams-banish-card-from-own-graveyard')
    ->deck($mordredDeck)
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->seed('myHand', 'Dredging Streams')
    ->castSpell('Dredging Streams', pay: 2, target: 'myGraveyard-0')
    ->expectProperty('p1Banish-0', 'CardID', 'em6eEh9q8y', 'The targeted card from player 1\'s own graveyard was banished')
    ->expectProperty('p1Graveyard-0', 'CardID', 'wmt0x5zado', 'Dredging Streams itself resolved into the graveyard (the target left it)')
    ->expectZoneCount('myGraveyard', 1, 1, 'Player 1\'s graveyard holds only the resolved Dredging Streams')
    ->expectZoneCount('myGraveyard', 1, 2, 'The opponent\'s graveyard card was not touched')
    ->expectZoneCount('myMemory', 2, 1, 'Exactly the 2 reserve payments went to memory')
    ->expectDqEmpty()
    ->register();

// --- Dredging Streams: banish target card from the OPPONENT's graveyard ---
// Dredging Streams (wmt0x5zado): "Banish target card from a graveyard." -- any graveyard. Same
// setup as dredging-streams-banish-card-from-own-graveyard, but the opponent's Fluffy Shopkeep
// is chosen: it is banished into the OPPONENT's banishment (the handler redirects to the
// graveyard owner's banish zone) and player 1's own graveyard is untouched.
// Notes carried over from the original definition:
//   - Dungeon Guide -> p1 graveyard-0
//   - Fluffy Shopkeep -> p2 graveyard-0
//   - Dredging Streams -> p1 myHand-7
//   - play Dredging Streams
//   - target: player 2's Fluffy Shopkeep
gaScenario('dredging-streams-banish-card-from-opponent-graveyard')
    ->deck($mordredDeck)
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->seed('myHand', 'Dredging Streams')
    ->castSpell('Dredging Streams', pay: 2, target: 'theirGraveyard-0')
    ->expectProperty('p2Banish-0', 'CardID', 'px60u5n1do', 'The opponent\'s graveyard card was banished (into the opponent\'s banishment)')
    ->expectZoneCount('myGraveyard', 0, 2, 'The opponent\'s graveyard is now empty')
    ->expectZoneCount('myGraveyard', 2, 1, 'Player 1\'s own graveyard is untouched (Dungeon Guide + the resolved Dredging Streams)')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing entered player 1\'s banishment')
    ->expectDqEmpty()
    ->register();

// --- Dredging Streams: [Level 2+] Floating Memory pays 1 of a champion level-up's memory cost from the graveyard ---
// Dredging Streams (wmt0x5zado): "[Level 2+] Floating Memory (While paying for a memory cost, you
// may banish this card from your graveyard to pay for 1 of that cost. Apply this effect only if
// your champion is level 2 or higher.)" The champion is patched to Mordred, Flawless Blade
// (level 2) and Dredging Streams is seeded in the graveyard. The real level-up into Mordred,
// Aurelian Regent (memory cost 3) offers the floating payment (QueueMaterializeFloatingPayment
// Choice): picking Dredging Streams banishes it for 1 and the remaining 2 are paid from the two
// seeded memory cards.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2)
//   - Dredging Streams in the graveyard (floating memory source) -> p1 graveyard-0
//   - memory 1/2
//   - memory 2/2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - start the level-up into Mordred, Aurelian Regent (memory cost 3)
//   - float Dredging Streams from the graveyard for 1 of the cost
gaScenario('dredging-streams-level-two-floating-memory-pays-levelup')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Dredging Streams')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')->target('myGraveyard-0')
    ->tested('Dredging Streams')
    ->expectProperty('p1Field-0', 'CardID', 'XPl2UAO9se', 'The level-up into Mordred, Aurelian Regent (memory cost 3) completed')
    ->expectProperty('p1Banish-0', 'CardID', 'wmt0x5zado', 'Dredging Streams was banished from the graveyard as 1 point of the memory cost')
    ->expectZoneCount('myGraveyard', 0, 1, 'The graveyard is empty: Dredging Streams was the floating payment')
    ->expectZoneCount('myMemory', 0, 1, 'Both memory cards were consumed for the other 2 points of the cost')
    ->expectZoneCount('myBanish', 3, 1, '3 banished cards = the floated Dredging Streams + the 2 memory cards')
    ->expectDqEmpty()
    ->register();

// --- Dredging Streams: Floating Memory is NOT offered below Level 2 ---
// Dredging Streams (wmt0x5zado): the Floating Memory clause is "[Level 2+]". The champion is
// Mordred, Burnished Avenger (level 1): the level-up into Mordred, Flawless Blade (memory cost 2)
// never offers the graveyard Dredging Streams -- both seeded memory cards are banished instead and
// Dredging Streams stays in the graveyard.
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger (level 1)
//   - Dredging Streams in the graveyard -> p1 graveyard-0
//   - memory 1/2
//   - memory 2/2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Mordred, Flawless Blade (memory cost 2): no floating prompt appears
gaScenario('dredging-streams-floating-memory-not-offered-below-level-two')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Dredging Streams')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-1')
    ->tested('Dredging Streams')
    ->expectProperty('p1Field-0', 'CardID', 'WI2owxIw0z', 'The level-up into Mordred, Flawless Blade (memory cost 2) completed')
    ->expectProperty('p1Graveyard-0', 'CardID', 'wmt0x5zado', 'Dredging Streams stayed in the graveyard: it was not offered as a floating payment below level 2')
    ->expectZoneCount('myBanish', 2, 1, 'Both memory cards were banished to pay the full cost 2')
    ->expectZoneCount('myMemory', 0, 1, 'Memory is empty')
    ->expectDqEmpty()
    ->register();

// --- Dummy Trainer: (3), Banish: draw a card into your memory, then the target opponent summons a Training Dummy ---
// Dummy Trainer (QCUld5Xidm, NORM WARRIOR REGALIA/ITEM, memory cost 0): "(3), Banish CARDNAME: Draw
// a card into your memory. Then target opponent summons a Training Dummy token." It is
// materialized for real from the material deck in the turn-3 materialize phase, then its ability
// is activated for real in the main phase (CustomInput Activate:0): the (3) cost is three reserve
// payments and the card banishes itself as part of the cost; the effect draws one card into player
// 1's memory and puts a Training Dummy token (a 0 power Warrior Construct ally) on the OPPONENT's
// field.
// Notes carried over from the original definition:
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Dummy Trainer (memory cost 0)
//   - pass the beginning-of-recollection window: the game moves on to the main phase
//   - activate its ability
//   - the (3) cost
gaScenario('dummy-trainer-banish-draw-memory-opponent-summons-training-dummy')
    ->deck($mordredDeck)
    ->endTurn()
    ->endTurn(2)->target('myMaterial-5')->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)
    ->tested('Dummy Trainer')
    ->expectProperty('p1Banish-0', 'CardID', 'QCUld5Xidm', 'Dummy Trainer banished itself as part of the cost')
    ->expectZoneCount('myField', 1, 1, 'Dummy Trainer left player 1\'s field (only the champion remains)')
    ->expectProperty('p2Field-1', 'CardID', 'EeFXEYMmF3', 'The OPPONENT summoned a Training Dummy token')
    ->expectZoneCount('myField', 2, 2, 'The opponent\'s field holds the champion plus exactly one Training Dummy')
    ->expectZoneCount('myMemory', 4, 1, 'Memory 4 = 3 reserve payments + 1 card drawn INTO MEMORY')
    ->expectZoneCount('myHand', 5, 1, 'Hand 5 = 7 + the turn draw - 3 reserve payments (the extra card went to memory, not hand)')
    ->expectDqEmpty()
    ->register();

// --- Dauntless Assault: [Mordred Bonus] On Attack: wake up the attacker ---
// Dauntless Assault (ixIY36Ck37, EXALTED/NORM WARRIOR/SWORD attack, reserve 3, power 4): "[Mordred
// Bonus] On Attack: Wake up the attacker." Player 1's champion is Mordred, Fated Luminary (the
// Mordred Bonus holds and its EXALTED element enables this Exalted card). On turn 3 (the first
// player cannot attack on turn 1) Dauntless Assault is played for real, paid (3) and aimed at
// player 2's champion: the champion, rested by attacking, is woken up by the On Attack trigger and
// the attack still deals its printed 4 damage.
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary: Mordred Bonus + Exalted
//   - Dauntless Assault -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Dauntless Assault
//   - attack player 2's champion
gaScenario('dauntless-assault-mordred-bonus-wakes-up-attacker')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myHand', 'Dauntless Assault')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Dauntless Assault', pay: 3, target: 'theirField-0')
    ->expectProperty('p1Field-0', 'Status', '2', 'The champion attacked (and was rested) but the On Attack woke it up again')
    ->expectDamage('p2Field-0', 4, 'Dauntless Assault dealt its printed 4 damage to player 2\'s champion')
    ->expectProperty('p1Graveyard-0', 'CardID', 'ixIY36Ck37', 'Dauntless Assault resolved into the graveyard')
    ->expectZoneCount('myMemory', 3, 1, 'Exactly 3 reserve payments (printed cost 3)')
    ->expectDqEmpty()
    ->register();

// --- Dauntless Assault: without the Mordred Bonus the attacker stays rested ---
// Dauntless Assault (ixIY36Ck37): the wake up is a "[Mordred Bonus]" On Attack. With a non-Mordred
// champion (Tristan, Shadowdancer, whose UMBRA lineage enables the Exalted element) the same
// attack deals its 4 damage but the champion remains rested.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (UMBRA): Exalted enabled, no Mordred Bonus
//   - Dauntless Assault -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Dauntless Assault
//   - attack player 2's champion
gaScenario('dauntless-assault-no-wake-up-without-mordred-champion')
    ->deck($mordredDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Water'])
    ->seed('myHand', 'Dauntless Assault')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Dauntless Assault', pay: 3, target: 'theirField-0')
    ->expectProperty('p1Field-0', 'Status', '1', 'The champion stays rested: no Mordred Bonus, no wake up')
    ->expectDamage('p2Field-0', 4, 'Dauntless Assault still dealt its printed 4 damage')
    ->expectZoneCount('myMemory', 3, 1, 'Exactly 3 reserve payments (printed cost 3)')
    ->expectDqEmpty()
    ->register();

// --- Dauntless Assault: its Exalted element needs another advanced element to be enabled ---
// Dauntless Assault (ixIY36Ck37, EXALTED/NORM): "(Exalted -- This element is enabled for you as long as you
// have another advanced element enabled.)" The champion is the plain Spirit of Water (no advanced
// element in the lineage): activating Dauntless Assault from hand does nothing -- no payment is
// requested and the card stays in hand.
// Notes carried over from the original definition:
//   - Dauntless Assault -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - try to activate Dauntless Assault: Exalted is not enabled
gaScenario('dauntless-assault-exalted-needs-another-advanced-element')
    ->deck($mordredDeck)
    ->seed('myHand', 'Dauntless Assault')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->play('Dauntless Assault')
    ->register();

// --- Drenching Finish: [Class Bonus] On Kill, target player puts the top three cards of their deck into their graveyard (opponent) ---
// Drenching Finish (en3DD836cp, WATER WARRIOR/SWORD attack, reserve 2, power 2): "[Class Bonus] On
// Kill: Target player puts the top three cards of their deck into their graveyard." Player 1's
// champion is Mordred, Burnished Avenger (a WARRIOR, so the Class Bonus holds). On turn 3 Drenching
// Finish is played for real and aimed at player 2's Training Dummy (2 life): the 2 damage kills it,
// the On Kill asks for a target player (the champions are offered) and player 2's champion is
// chosen: exactly three cards move from the top of player 2's deck to player 2's graveyard.
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger (WARRIOR): Class Bonus active
//   - Training Dummy (2 life) -> p2 field-1
//   - Drenching Finish -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Drenching Finish
//   - attack the Training Dummy
//   - On Kill: target player = player 2 (its champion)
gaScenario('drenching-finish-class-bonus-on-kill-opponent-mills-three')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myField', 'Training Dummy', player: 2)
    ->seed('myHand', 'Drenching Finish')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->play('Drenching Finish')->pay(2)->target('theirField-1')->target('theirField-0')
    ->expectZoneCount('myField', 1, 2, 'The Training Dummy was killed (only player 2\'s champion remains)')
    ->expectZoneCount('myGraveyard', 3, 2, 'Exactly three cards were put into the targeted player\'s (the opponent\'s) graveyard')
    ->expectZoneCount('myDeck', 7, 2, 'The opponent\'s deck lost exactly those three cards (10 -> 7)')
    ->expectZoneCount('myDeck', 10, 1, 'Player 1\'s own deck is untouched')
    ->expectZoneCount('myGraveyard', 1, 1, 'Player 1\'s graveyard holds only the resolved Drenching Finish')
    ->expectDqEmpty()
    ->register();

// --- Drenching Finish: the On Kill target player can be yourself ---
// Drenching Finish (en3DD836cp): "Target player puts the top three cards of THEIR deck into their
// graveyard." Same flow as drenching-finish-class-bonus-on-kill-opponent-mills-three but player 1's
// own champion is chosen: three cards leave the top of player 1's deck (player 2's deck is
// untouched).
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger (WARRIOR): Class Bonus active
//   - Training Dummy (2 life) -> p2 field-1
//   - Drenching Finish -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Drenching Finish
//   - attack the Training Dummy
//   - On Kill: target player = player 1 (its own champion)
gaScenario('drenching-finish-class-bonus-on-kill-self-mills-three')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myField', 'Training Dummy', player: 2)
    ->seed('myHand', 'Drenching Finish')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->play('Drenching Finish')->pay(2)->target('theirField-1')->target('myField-0')
    ->expectZoneCount('myField', 1, 2, 'The Training Dummy was killed')
    ->expectZoneCount('myGraveyard', 4, 1, 'Player 1\'s graveyard = Drenching Finish + the three milled cards')
    ->expectZoneCount('myDeck', 7, 1, 'Player 1\'s deck lost exactly three cards (10 -> 7)')
    ->expectZoneCount('myGraveyard', 0, 2, 'The opponent\'s graveyard is untouched')
    ->expectZoneCount('myDeck', 10, 2, 'The opponent\'s deck is untouched')
    ->expectDqEmpty()
    ->register();

// --- Drenching Finish: no On Kill prompt without the Class Bonus ---
// Drenching Finish (en3DD836cp): the On Kill is a "[Class Bonus]". With the plain Spirit of Water
// (class SPIRIT, not WARRIOR) as champion the Training Dummy still dies but no player is asked
// for and no card is milled.
// Notes carried over from the original definition:
//   - Training Dummy (2 life) -> p2 field-1
//   - Drenching Finish -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Drenching Finish
//   - attack the Training Dummy
gaScenario('drenching-finish-on-kill-requires-class-bonus')
    ->deck($mordredDeck)
    ->seed('myField', 'Training Dummy', player: 2)
    ->seed('myHand', 'Drenching Finish')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Drenching Finish', pay: 2, target: 'theirField-1')
    ->expectZoneCount('myField', 1, 2, 'The Training Dummy was still killed')
    ->expectZoneCount('myDeck', 10, 2, 'The opponent\'s deck is untouched')
    ->expectZoneCount('myDeck', 10, 1, 'Player 1\'s deck is untouched')
    ->expectZoneCount('myGraveyard', 0, 2, 'The opponent\'s graveyard is empty: nothing was milled')
    ->expectZoneCount('myGraveyard', 1, 1, 'Player 1\'s graveyard holds only the resolved Drenching Finish')
    ->expectDqEmpty()
    ->register();

// --- Drenching Finish: the On Kill needs a KILL -- an attack that leaves the unit alive mills nothing ---
// Drenching Finish (en3DD836cp): "On Kill". With the Class Bonus active the attack targets a
// Dungeon Guide (3 life): the 2 damage does not destroy it, so no prompt appears and no card is
// milled.
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger (WARRIOR): Class Bonus active
//   - Dungeon Guide (3 life) -> p2 field-1
//   - Drenching Finish -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Drenching Finish
//   - attack the Dungeon Guide (survives with 2 damage)
//   - player 2 declines to retaliate
gaScenario('drenching-finish-on-kill-needs-a-kill')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Drenching Finish')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->play('Drenching Finish')->pay(2)->target('theirField-1')->answer(2, '-')
    ->expectProperty('p2Field-1', 'CardID', 'em6eEh9q8y', 'The 3-life Dungeon Guide survived the 2 damage')
    ->expectDamage('p2Field-1', 2, 'The survivor carries the 2 damage')
    ->expectZoneCount('myDeck', 10, 2, 'No mill: the opponent\'s deck is untouched')
    ->expectZoneCount('myDeck', 10, 1, 'No mill: player 1\'s deck is untouched')
    ->expectDqEmpty()
    ->register();

// top three, top to bottom: Dungeon Guide, Fluffy Shopkeep, Fairy Whispers
// Notes carried over from the original definition:
//   - Fairy Whispers (3rd) into the graveyard
//   - Fluffy Shopkeep onto the top of the deck (Dungeon Guide is then left for the bottom)
//   - the last card goes to the bottom
gaScenario('divining-streams-look-three-graveyard-top-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Divining Streams')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers'])
    ->play('Divining Streams')->pay(3)->target('myTempZone-2')->target('myTempZone-1')
    ->answer(1, 'Top=;Bottom=em6eEh9q8y')
    ->register();

// Notes carried over from the original definition:
//   - Dungeon Guide (the top card) into the graveyard
//   - Fairy Whispers (now the 2nd of the two left) onto the top of the deck; Fluffy Shopkeep is left for the bottom
gaScenario('divining-streams-look-three-first-to-graveyard-last-on-top')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Divining Streams')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers'])
    ->play('Divining Streams')->pay(3)->target('myTempZone-0')->target('myTempZone-1')
    ->answer(1, 'Top=;Bottom=px60u5n1do')
    ->register();

// PLAYER 2 seating (perspective check): the stack sits under player 2's turn-start draw (a filler Dungeon Guide); player 2 first
// declines the two opportunity windows player 1's pass opens
gaScenario('divining-streams-player-two-look-three-graveyard-top-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Divining Streams', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers'], player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Divining Streams', 2)->pay(3, 2)->answer(2, 'myTempZone-2')->answer(2, 'myTempZone-1')
    ->answer(2, 'Top=;Bottom=em6eEh9q8y')
    ->register();

// --- Dormant Sacrificial Altar (px8jypwc8t): "Sacrifice an Automaton ally and a Human ally: Put the top two cards of your deck into your graveyard." ---
// Generated body milled with a loop of MZMove($player, "myDeck-0", "myGraveyard"): only the TOP card was ever milled (see 1676ae91 Pure Cytosynth). The Altar,
// Shieldroid (Automaton) and Coy Bouclier (Human) are seeded on the field; top of the deck: Dungeon Guide, Fluffy Shopkeep (the two milled), Charge the Soul (marker).
// Notes carried over from the original definition:
//   - Dormant Sacrificial Altar -> field-1
//   - Shieldroid (Automaton) -> field-2
//   - Coy Bouclier (Human) -> field-3
//   - sacrifice Shieldroid (the Automaton)
//   - sacrifice Coy Bouclier (the Human; now at index 2 once Shieldroid is gone)
gaScenario('dormant-sacrificial-altar-sacrifice-automaton-and-human-mills-two')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Dormant Sacrificial Altar')
    ->seed('myField', 'Shieldroid')
    ->seed('myField', 'Coy Bouclier')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Charge the Soul'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-2')->target('myField-2')
    ->tested('Dormant Sacrificial Altar')
    ->register();

// --- Desperate Cavalier (slmer06rku): "[Class Bonus] On Attack: If your influence is four or less, banish the top two cards of your deck. As long as they're banished you may activate them. ..." The
// generated body banished with a fixed-index MZMove loop: only ONE card was ever banished. Player 2 attacks (the opening player cannot attack on turn 1) with a WARRIOR champion and an empty hand
// (influence 1 after the turn draw). ---
// Notes carried over from the original definition:
//   - Lorraine, Ascendant Wings (WARRIOR): Class Bonus
//   - Desperate Cavalier -> p2 myField-1
//   - influence: only the turn-start draw is left (1)
//   - top two get banished; the third is the turn-start draw...
//   - end player 1's turn 1
//   - Desperate Cavalier declares an attack
//   - target player 1's champion
gaScenario('desperate-cavalier-on-attack-banishes-top-two-cards-low-influence')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Lorraine, Ascendant Wings', player: 2)
    ->seed('myField', 'Desperate Cavalier', player: 2)
    ->emptyZone('myHand', player: 2, destZone: 'myGraveyard')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'], player: 2)
    ->endTurn()->passBoth()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->tested('Desperate Cavalier')
    ->register();

// --- Dante, Hematic Overdrive (MG4zUMPqIC): "For the rest of the game, Dante has 'At the beginning of your end phase, sacrifice an Elysian object or banish an Elysian card from your hand or memory. If you do neither, destroy Dante.'" Declining
// the optional pick (PASS) must destroy Dante; DanteHematicUpkeep was queued without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler and the champion survived. The DANTE_HEMATIC_UPKEEP global effect is seeded directly
// (it is what Dante's On Enter adds) and an Elysian Orphan in hand is the only legal pick. ---
// Notes carried over from the original definition:
//   - Dante, Hematic Overdrive
//   - Elysian Orphan (the only Elysian card) -> myHand-7
gaScenario('dante-hematic-overdrive-end-phase-decline-destroys-dante')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Dante, Hematic Overdrive')
    ->globalEffect('DANTE_HEMATIC_UPKEEP')
    ->seed('myHand', 'Elysian Orphan')
    ->endTurn()->pass(1)
    ->tested('Dante, Hematic Overdrive')
    ->register();

// --- Diana, Judgment's Arrow (wiztyu6o24): "On Enter: Load up to two Aethercharge cards from your hand and/or memory into an Aetherwing weapon you control. For each card loaded this way, draw a card into your memory." Declining the second
// load (PASS) must still draw one card into memory for the card already loaded; DianaL2LoadChoice's second-prompt queue site was missing dontSkipOnPass, so the handler and its draw-into-memory were skipped. Diana, Keen Huntress (level 1) is the
// level-up base, Salamander's Breath (an Aetherwing) is on the field, two Aether's Embrace (Aethercharge cards) are in hand, and two memory cards pay the 2-memory level-up cost. ---
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (level 1): the level-up base
//   - Salamander's Breath (Aetherwing weapon) -> myField-1
//   - level-up memory payment 1/2
//   - level-up memory payment 2/2
//   - Aether's Embrace (Aethercharge) -> myHand-7
//   - Aether's Embrace (Aethercharge) -> myHand-8
gaScenario('diana-judgments-arrow-enter-load-one-aethercharge-then-decline-draws-one-into-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Diana, Judgment's Arrow
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Keen Huntress')
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myHand', 'Aether\'s Embrace')
    ->seed('myHand', 'Aether\'s Embrace')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myHand-7')->pass(1)
    ->tested('Diana, Judgment\'s Arrow')
    ->expectZoneCount('myMemory', 1, 1, 'One Aethercharge loaded then declined: exactly one card is drawn into memory')
    ->expectZoneCount('myHand', 8, 1, 'Hand: 9 seeded - 1 loaded; the memory draw does not touch the hand')
    ->expectZoneCount('myDeck', 12, 1, 'The memory draw came from the deck (13 -> 12)')
    ->register();

// Undo snapshot: a REFUSED graveyard / material-deck click must not overwrite the undo snapshot (same contract as the
// banishment fixture above). Dungeon Guide is played for real (the hand click saves the snapshot); then a plain card in the
// graveyard (no ephemerate, no graveyard ability) is clicked twice. The ActionMap 'myGraveyard' case used to save an undo
// snapshot at the top of every click, so the refused click replaced the pre-play snapshot with the post-play state.
// Notes carried over from the original definition:
//   - 7 plain cards -> p1 graveyard
//   - Dungeon Guide -> p1 myHand-0
//   - 1: play the Dungeon Guide (saves the undo snapshot)
//   - 5: decline the optional On Enter
//   - 6: click a plain graveyard card: nothing to activate
//   - 7: click it again (the engine re-reads the saved state between actions, so the undo snapshot is observable here)
gaScenario('graveyard-refused-click-keeps-undo-snapshot')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)->pay(3)->answer(1, 'NO')
    ->action(1, 10002, 'myGraveyard-0!FSM!')
    ->action(1, 10002, 'myGraveyard-0!FSM!')
    ->tested('Dungeon Guide')
    ->expectProperty('myVersions-0', 'Version', '1<v0>MAIN<v0>1<v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v0>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v0><v0>0<v0><v0><v0>OWCdWq3mXY<v1>WI2owxIw0z<v1>KqBosnU7pU<v1>XPl2UAO9se<v1>efTHWeXscP<v1>QCUld5Xidm<v1>clS3E0HrZL<v0>tafqldAGRF<v2>2<v2>1<v2>0<v2>1<v2>-<v2>-<v2>-<v2>1<v0><v0><v0><v0>px60u5n1do<v1>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v0><v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v0><v0>0<v0><v0><v0>OWCdWq3mXY<v1>WI2owxIw0z<v1>KqBosnU7pU<v1>XPl2UAO9se<v1>efTHWeXscP<v1>QCUld5Xidm<v1>clS3E0HrZL<v0>tafqldAGRF<v2>2<v2>2<v2>0<v2>2<v2>-<v2>-<v2>-<v2>2<v0><v0><v0><v0>{"mzID":"myField-0","isImbued":"NO","amount":7}<v0><v0>{}<v0>2<v0>{}<v0>76<v0>{"cards":{"1":{"em6eEh9q8y":{"drawn":5,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0},"px60u5n1do":{"drawn":2,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0}},"2":{"px60u5n1do":{"drawn":6,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0},"em6eEh9q8y":{"drawn":1,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0}}},"turns":[],"cur":[],"combatEvents":[]}', step: 5)
    ->expectProperty('myVersions-0', 'Version', '1<v0>MAIN<v0>1<v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v0>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v0><v0>0<v0><v0><v0>OWCdWq3mXY<v1>WI2owxIw0z<v1>KqBosnU7pU<v1>XPl2UAO9se<v1>efTHWeXscP<v1>QCUld5Xidm<v1>clS3E0HrZL<v0>tafqldAGRF<v2>2<v2>1<v2>0<v2>1<v2>-<v2>-<v2>-<v2>1<v0><v0><v0><v0>px60u5n1do<v1>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v0><v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v0><v0>0<v0><v0><v0>OWCdWq3mXY<v1>WI2owxIw0z<v1>KqBosnU7pU<v1>XPl2UAO9se<v1>efTHWeXscP<v1>QCUld5Xidm<v1>clS3E0HrZL<v0>tafqldAGRF<v2>2<v2>2<v2>0<v2>2<v2>-<v2>-<v2>-<v2>2<v0><v0><v0><v0>{"mzID":"myField-0","isImbued":"NO","amount":7}<v0><v0>{}<v0>2<v0>{}<v0>76<v0>{"cards":{"1":{"em6eEh9q8y":{"drawn":5,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0},"px60u5n1do":{"drawn":2,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0}},"2":{"px60u5n1do":{"drawn":6,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0},"em6eEh9q8y":{"drawn":1,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0}}},"turns":[],"cur":[],"combatEvents":[]}', 'A refused graveyard click does not overwrite the undo snapshot saved by the previous real action (read after the engine re-loads the state)')
    ->expectZoneCount('myHand', 0, 1, 'Nothing moved: the hand is still empty')
    ->expectZoneCount('myField', 2, 1, 'Champion + Dungeon Guide only: the refused click did nothing')
    ->expectDqEmpty()
    ->register();

// Same contract for the material deck: clicking a material card that has no special activation permission is refused and
// must leave the previous real action's undo snapshot untouched.
// Notes carried over from the original definition:
//   - Dungeon Guide -> p1 myHand-0
//   - 1: play the Dungeon Guide (saves the undo snapshot)
//   - 5: decline the optional On Enter
//   - 6: click a plain material card: nothing to activate
//   - 7: click it again (observability, as above)
gaScenario('material-refused-click-keeps-undo-snapshot')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)->pay(3)->answer(1, 'NO')
    ->action(1, 10002, 'myMaterial-1!FSM!')
    ->action(1, 10002, 'myMaterial-1!FSM!')
    ->tested('Dungeon Guide')
    ->expectProperty('myVersions-0', 'Version', '1<v0>MAIN<v0>1<v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v0>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v0><v0>0<v0><v0><v0>OWCdWq3mXY<v1>WI2owxIw0z<v1>KqBosnU7pU<v1>XPl2UAO9se<v1>efTHWeXscP<v1>QCUld5Xidm<v1>clS3E0HrZL<v0>tafqldAGRF<v2>2<v2>1<v2>0<v2>1<v2>-<v2>-<v2>-<v2>1<v0><v0><v0><v0>px60u5n1do<v1>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v0><v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v0><v0>0<v0><v0><v0>OWCdWq3mXY<v1>WI2owxIw0z<v1>KqBosnU7pU<v1>XPl2UAO9se<v1>efTHWeXscP<v1>QCUld5Xidm<v1>clS3E0HrZL<v0>tafqldAGRF<v2>2<v2>2<v2>0<v2>2<v2>-<v2>-<v2>-<v2>2<v0><v0><v0><v0>{"mzID":"myField-0","isImbued":"NO","amount":7}<v0><v0>{}<v0>2<v0>{}<v0>76<v0>{"cards":{"1":{"em6eEh9q8y":{"drawn":5,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0},"px60u5n1do":{"drawn":2,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0}},"2":{"px60u5n1do":{"drawn":6,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0},"em6eEh9q8y":{"drawn":1,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0}}},"turns":[],"cur":[],"combatEvents":[]}', step: 5)
    ->expectProperty('myVersions-0', 'Version', '1<v0>MAIN<v0>1<v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v0>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v0><v0>0<v0><v0><v0>OWCdWq3mXY<v1>WI2owxIw0z<v1>KqBosnU7pU<v1>XPl2UAO9se<v1>efTHWeXscP<v1>QCUld5Xidm<v1>clS3E0HrZL<v0>tafqldAGRF<v2>2<v2>1<v2>0<v2>1<v2>-<v2>-<v2>-<v2>1<v0><v0><v0><v0>px60u5n1do<v1>em6eEh9q8y<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v1>px60u5n1do<v0><v0><v0>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>px60u5n1do<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v1>em6eEh9q8y<v0><v0>0<v0><v0><v0>OWCdWq3mXY<v1>WI2owxIw0z<v1>KqBosnU7pU<v1>XPl2UAO9se<v1>efTHWeXscP<v1>QCUld5Xidm<v1>clS3E0HrZL<v0>tafqldAGRF<v2>2<v2>2<v2>0<v2>2<v2>-<v2>-<v2>-<v2>2<v0><v0><v0><v0>{"mzID":"myField-0","isImbued":"NO","amount":7}<v0><v0>{}<v0>2<v0>{}<v0>76<v0>{"cards":{"1":{"em6eEh9q8y":{"drawn":5,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0},"px60u5n1do":{"drawn":2,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0}},"2":{"px60u5n1do":{"drawn":6,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0},"em6eEh9q8y":{"drawn":1,"drawnToMemory":0,"materialized":0,"reserved":0,"discarded":0,"activated":0}}},"turns":[],"cur":[],"combatEvents":[]}', 'A refused material-deck click does not overwrite the undo snapshot saved by the previous real action (read after the engine re-loads the state)')
    ->expectZoneCount('myHand', 0, 1, 'Nothing moved: the hand is still empty')
    ->expectZoneCount('myField', 2, 1, 'Champion + Dungeon Guide only: the refused click did nothing')
    ->expectDqEmpty()
    ->register();

// Dichroic Scorch (TlhsnnRhGK): "As an additional cost, discard a fire element card. Remove all sheen counters from all units on the field. Then deal X damage to each unit except for your
// champion, where X is the amount of counters removed this way."
// Notes carried over from the original definition:
//   - ASSASSIN class
//   - Dungeon Guide with 2 sheen -> myField-1
//   - opposing Fluffy Shopkeep with 1 sheen -> theirField-1 (3 sheen removed in total: X = 3)
//   - Baby Red Slime (fire): the additional discard cost -> myHand-7
//   - Dichroic Scorch -> myHand-8
gaScenario('dichroic-scorch-removes-sheen-and-deals-that-much-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'ASSASSIN')
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-1', ['Counters' => ['sheen' => 2]])
    ->seed('myField', 'Fluffy Shopkeep', player: 2)
    ->patch('myField-1', ['Counters' => ['sheen' => 1]], player: 2)
    ->seed('myHand', 'Baby Red Slime')
    ->seed('myHand', 'Dichroic Scorch')
    ->play('Dichroic Scorch')->target('myHand-7')->pay(3)
    ->register();

// Drown in Aether (gnfbp3g8iw): "Target rested ally gets -3 LIFE until end of turn."
// Notes carried over from the original definition:
//   - Dungeon Guide -> theirField-1
//   - Drown in Aether -> myHand-7
gaScenario('drown-in-aether-gives-a-rested-ally-minus-three-life')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Spirit of Water'], classes: 'RANGER')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 1], player: 2)
    ->seed('myHand', 'Drown in Aether')
    ->castSpell('Drown in Aether', pay: 2, target: 'theirField-1')
    ->register();

// Discordia, Harp of Malice (5LoOprBJay): "[REST]: Target champion gets -X level and your champion gets +X level until end of turn, where X is the amount of music counters on CARDNAME. At the beginning of the next end phase, banish CARDNAME."
// Notes carried over from the original definition:
//   - Discordia -> myField-1
gaScenario('discordia-rest-swaps-levels-by-its-music-counters-and-is-banished-at-end-phase')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Discordia, Harp of Malice')
    ->patch('myField-1', ['Counters' => ['music' => 2]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-0')
    ->tested('Discordia, Harp of Malice')
    ->register();

gaAbilitySandbox('delicious-pastry-ability-1-pays-its-printed-cost', 'Delicious Pastry', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Delicious Pastry')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 0, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('delicious-pastry-ability-2-pays-its-printed-cost', 'Delicious Pastry', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pay(2)->target('myField-1')->pass(1)
    ->tested('Delicious Pastry')
    ->expectCostFootprint(hand: 11, memory: 4, banish: 0, graveyard: 3, field: 6)
    ->register();

// Dissuading Aether (bx25s7kiln): "Target unit's attacks get -3 POWER until end of turn. Then you may load Dissuading Aether into an Aetherwing weapon you control."
gaScenario('dissuading-aether-gives-a-unit-minus-three-attack-power-then-loads')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Spirit of Water'], classes: 'RANGER')
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dissuading Aether')
    ->play('Dissuading Aether')->pay(2)->target('theirField-1')->answer(1, 'YES')
    ->register();

gaScenario('diana-moonpiercer-becoming-distant-glimpses-two')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Moonpiercer')
    ->seed('myField', 'Ranger Boots')
    ->patch('myField-1', ['Status' => 2])
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'NO')
    ->answer(1, 'Top=px60u5n1do;Bottom=em6eEh9q8y')
    ->tested('Diana, Moonpiercer')
    ->register();

gaScenario('diana-aether-dilettante-enter-while-not-distant-materializes-nothing')
    ->deck($gaDilettanteDeck)
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()->pass(1)
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Diana, Aether Dilettante')
    ->register();

// Discordia, Harp of Malice (5LoOprBJay): "[REST]: Target champion gets -X level and your champion gets +X level until end of turn, where X is the amount of music counters on CARDNAME." The handler
// read $musicCount (undefined): the level effects were named DISCORDIA_MINUS_ / DISCORDIA_PLUS_ with no X.
// Notes carried over from the original definition:
//   - Discordia with two music counters -> field-1
gaScenario('discordia-harp-of-malice-rest-applies-level-swap-with-music-counter-x')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Discordia, Harp of Malice', properties: ['Counters' => ['music' => 2]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-0')
    ->tested('Discordia, Harp of Malice')
    ->register();

// Decaying Reproach (qXIKFip2t4): "Deal 3+X damage to target unit, where X is twice the amount of wither counters removed (up to four from objects you don't control)." The handler read $removed
// (undefined): always 3. The opponent's Dungeon Guide has three wither counters: 3 + 6 = 9 damage to their champion.
// Notes carried over from the original definition:
//   - Diao Chan, Idyll Corsage (level 3 TERA Cleric) as the champion
//   - Dungeon Guide with 3 wither counters -> theirField-1
gaScenario('decaying-reproach-removes-wither-counters-for-extra-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Decaying Reproach')
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['wither' => 3]])
    ->castSpell('Decaying Reproach', pay: 2, target: 'theirField-0')
    ->register();

// Discover the Divine (YJacXvwTiX): "You may remove four enlighten counters from your champion. If you do, level up your champion." The handler read $champMZ (undefined): the counters were never removed
// but the champion still levelled up (free level-ups).
gaScenario('discover-the-divine-removes-four-enlighten-counters-to-level-up')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
1 Vanitas, Obliviate Schemer
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Discover the Divine')
    ->patch('myField-0', ['Counters' => ['enlighten' => 4]])
    ->play('Discover the Divine')->pay(3)->pass(1)->answer(1, 'YES')
    ->answer(1, 'Top=px60u5n1do,px60u5n1do,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->register();

// Discharger (wAq6lxxwBA): "Banish CARDNAME: Remove four static counters from target object." and Legendary Saddle (AXE6sCzjZU): "Banish CARDNAME: Draw a card into your memory. Activate this ability only if you control
// three or more Horse allies." Neither banished itself: both abilities were free and repeatable.
// Notes carried over from the original definition:
//   - Discharger -> myField-1
//   - a Dungeon Guide with 5 static counters -> myField-2
gaScenario('discharger-banishes-itself-and-removes-four-static-counters')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Discharger')
    ->seed('myField', 'Dungeon Guide', properties: ['Counters' => ['static' => 5]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-1')->pass(1)
    ->tested('Discharger')
    ->register();

// Diana, Moonpiercer (v3vfjtwm7g): "Whenever Diana becomes distant, choose one-- Negate each card activation that targets Diana unless its controller pays (2). Then if Diana is defending, end the combat
// phase unless the attacking player pays (2). / Glimpse 2." Negate mode, Diana defending: player 2 attacks Diana with a Dungeon Guide and player 1 answers by making her distant (Ranger Boots).
// Notes carried over from the original definition:
//   - Ranger Boots -> p1 field-1 (makes the champion distant)
//   - Dungeon Guide, the attacker -> p2 field-1
//   - end player 1 turn
//   - player 1 passes the two end-of-turn windows
//   - player 2 attacks with the Dungeon Guide
//   - ... targeting Diana
//   - player 1 answers with Ranger Boots: Diana becomes distant
//   - Diana's trigger: choose the negate mode
//   - player 2 (attacking) declines to pay (2): the combat phase ends
gaScenario('diana-moonpiercer-negate-mode-defending-attacker-declines-to-pay-combat-ends')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Moonpiercer')
    ->seed('myField', 'Ranger Boots')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(1, 'myField-1@Activate-0@Distant')
    ->answer(1, 'YES')->answer(2, 'NO')
    ->tested('Diana, Moonpiercer')
    ->register();

// ... and when the attacking player does pay (2), combat carries on and Diana takes the hit.
// Notes carried over from the original definition:
//   - Ranger Boots -> p1 field-1 (makes the champion distant)
//   - Dungeon Guide, the attacker -> p2 field-1
//   - end player 1 turn
//   - player 1 passes the two end-of-turn windows
//   - player 2 attacks with the Dungeon Guide
//   - ... targeting Diana
//   - player 1 answers with Ranger Boots: Diana becomes distant
//   - Diana's trigger: choose the negate mode
//   - player 2 (attacking) pays (2) to continue combat
gaScenario('diana-moonpiercer-negate-mode-defending-attacker-pays-two-combat-continues')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Moonpiercer')
    ->seed('myField', 'Ranger Boots')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(1, 'myField-1@Activate-0@Distant')
    ->answer(1, 'YES')->answer(2, 'YES')->pay(2, 2)
    ->tested('Diana, Moonpiercer')
    ->register();
