<?php
/**
 * Fixtures for cards starting with 'M', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Meltdown: Level 2+ activation discount + destroy target domain/item/weapon ---
// Meltdown's element is FIRE (matching the "Spirit of Fire" starting champion), so no
// lineage patch is needed. The starting champion's Counters are patched directly with 2
// "level" counters (ObjectCurrentLevel() = CardLevel + level-counter count) to reach the
// [Level 2+] discount condition without scripting a real level-up sequence. Clarent, Sword of
// Peace is seeded onto the opponent's field as the destroy target (a legal WEAPON).
// Notes carried over from the original definition:
//   - Clarent, Sword of Peace (WEAPON) - destroy target
//   - Level 2+ discount condition
gaScenario('meltdown-level2-destroy-item')
    ->standardDeck('Meltdown')
    ->seed('theirField', 'Clarent, Sword of Peace')
    ->champion(level: 2)
    ->seed('myHand', 'Meltdown')
    ->castSpell('Meltdown', pay: 3, target: 'theirField-1')
    ->register();

// --- Mnemonic Charm: On Enter draws a card into memory ---
// Mnemonic Charm's element is NORM, so no lineage patch is needed. Only the always-available
// On Enter draw-into-memory is covered; the [Class Bonus] Sacrifice-for-Empower ability is
// out of scope.
gaScenario('mnemonic-charm-enter-draw-memory')
    ->standardDeck('Mnemonic Charm')
    ->seed('myHand', 'Mnemonic Charm')
    ->castSpell('Mnemonic Charm', pay: 2)
    ->expectZoneCount('myMemory', 3, 1, '2 reserve payments plus the On Enter draw-into-memory leave 3 cards in memory')
    ->expectZoneCount('myDeck', 8, 1, 'The On Enter draw reduces the deck by one')
    ->expectDqEmpty()
    ->register();

// --- Mindbreak Bullet: load into an unloaded Gun weapon ---
// Mindbreak Bullet is an UMBRA (advanced element) ITEM card, so the starting champion's
// Subcards are patched with a real UMBRA champion (Tristan, Shadowdancer), same as
// anathemas-end-load-gun. Framework Sidearm is seeded onto the field as the [REST] Load
// ability's target. Only the always-available Load half is covered; the [Class Bonus] On
// Champion Hit memory-discard trigger requires a real combat hit and is out of scope.
// Notes carried over from the original definition:
//   - UMBRA lineage/element unlock
//   - Framework Sidearm (unloaded GUN weapon) - Load target
gaScenario('mindbreak-bullet-load-gun')
    ->standardDeck('Mindbreak Bullet')
    ->champion(lineage: ['Tristan, Shadowdancer'])
    ->seed('myField', 'Framework Sidearm')
    ->seed('myHand', 'Mindbreak Bullet')
    ->castSpell('Mindbreak Bullet', pay: 2)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->expectProperty('p1Field-1', 'Subcards', '["9htu9agwj4"]', 'Loading appends the bullet\'s CardID to the target Gun weapon\'s Subcards')
    ->expectZoneCount('myField', 2, 1, 'The bullet is removed from the field entirely, not moved to another zone, once loaded')
    ->expectDqEmpty()
    ->register();

// --- Martial Guard: prevent the next 2 damage to target unit ---
// Martial Guard's element is NORM, so no lineage patch is needed. Only the base always-
// available prevention is covered; the [Class Bonus][Level 2+] Floating Memory clause is a
// passive/reusable-elsewhere property, not a triggered ability, and is out of scope.
gaScenario('martial-guard-prevent-2')
    ->standardDeck('Martial Guard')
    ->seed('myHand', 'Martial Guard')
    ->castSpell('Martial Guard', pay: 2, window: 'both', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","EVASIVE_MANEUVERS_2"]', 'The targeted champion is protected by the shared prevent-2 shield tag')
    ->expectDqEmpty()
    ->register();

// --- Meteor Strike: not starcalled, destroy target non-champion object ---
// Notes carried over from the original definition:
//   - ASTRA lineage/element unlock
//   - Dungeon Guide (ALLY) - destroy target
gaScenario('meteor-strike-destroy')
    ->standardDeck('Meteor Strike')
    ->champion(lineage: ['Arisanna, Astral Zenith'])
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Meteor Strike')
    ->castSpell('Meteor Strike', pay: 3, target: 'theirField-1')
    ->register();

// --- Memory Invocation: banish a floating memory card from graveyard, draw two cards ---
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
//   - Gentle Respite (unconditional Floating Memory) - cost fuel
gaScenario('memory-invocation-draw')
    ->standardDeck('Memory Invocation')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Gentle Respite')
    ->seed('myHand', 'Memory Invocation')
    ->play('Memory Invocation')->target('myGraveyard-0')->pay(3)->passBoth()
    ->register();

// --- Materialize Polearm: materialize a Polearm card from your material deck ---
// WIND element access via the established Subcards lineage patch (Spirit of Wind). Steel
// Halberd (WARRIOR/POLEARM) is seeded directly into the Material zone (on top of the 5 default
// material cards) as the choosable target; the CUSTOM handler resolves via DoMaterialize
// (GeneratedMacroCode.php:38036), bypassing the Polearm's own cost.
// Notes carried over from the original definition:
//   - WIND element unlock (Spirit of Wind)
//   - Steel Halberd (WARRIOR/POLEARM)
//   - Materialize Polearm
//   - Reserve-paid activated abilities resolve through an EffectStack opportunity, same as
//   - favorable-winds-ally-life-buff; decline it ('-') to let the target choice appear.
//   - choose Steel Halberd
gaScenario('materialize-polearm-from-material-deck')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myMaterial', 'Steel Halberd')
    ->seed('myHand', 'Materialize Polearm')
    ->play('Materialize Polearm')->pay(4)->answer(1, '-')->target('myMaterial-4')
    ->register();

// --- Materialize Munitions: [Class Bonus] discount; materialize a Bullet from material deck ---
// Seeds a RANGER champion for the Class Bonus (discounting the 3-reserve activation cost to
// 2) and a real 0-memory-cost Bullet (Plated Bullet) into the material zone -- this fixture's
// own deck's Material section has no Bullet cards. Materializing the chosen bullet still
// routes through the normal CUSTOM "MATERIALIZE" handler and pays its own (0) memory cost.
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (RANGER champion) - Class Bonus source
//   - Plated Bullet - 0-memory Bullet source
gaScenario('materialize-munitions-class-bonus-discount')
    ->standardDeck('Materialize Munitions')
    ->seed('myField', 'Diana, Keen Huntress')
    ->seed('myMaterial', 'Plated Bullet')
    ->seed('myHand', 'Materialize Munitions')
    ->castSpell('Materialize Munitions', pay: 2, target: 'myMaterial-4')
    ->register();

// --- Mist Resonance: allies +1 LIFE until end of turn ---
// Mist Resonance is WATER -- the starting champion's Subcards are patched with a real WATER
// champion (Nico, Rapture's Embrace) to unlock element access. Only the base ability (allies +1
// LIFE) is covered; the [Class Bonus] Harmonize clause (allies assign damage with life instead
// of power) is confirmed NOT IMPLEMENTED at all by the engine's own code comment
// (GeneratedMacroCode.php: "Note: Harmonize class bonus ... is not implemented") and is out of
// scope. Gray Wolf is seeded onto the field as the ally that receives the buff. Confirmed via
// computed_life_equals (base 2 + 1 = 3).
// Notes carried over from the original definition:
//   - Nico, Rapture's Embrace -- unlocks WATER
//   - Gray Wolf -- the ally that receives the buff
gaScenario('mist-resonance-allies-life')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Mist Resonance')
    ->castSpell('Mist Resonance', pay: 5)
    ->register();

// --- Meadowbloom Dryad: [Class Bonus] whenever an ally enters, buff counter on target ally ---
// Meadowbloom Dryad is TERA. The starting champion's CardID is patched directly to Silvie,
// Loved by All -- both TAMER (satisfies the Class Bonus) and TERA (unlocks element access) at
// once. The trigger (FieldAfterAdd, GameLogic.php ~8126) fires for ANY ally entering, including
// Meadowbloom Dryad itself: when it resolves as the very first ally on either field, it is the
// only entry in the target-ally search, so the engine auto-applies the buff counter without a
// choice decision (count($allyTargets) === 1 skips the MZCHOOSE branch) -- a simpler and
// equally valid way to exercise the trigger than materializing a second ally afterward. Only
// the Class Bonus trigger is covered; Preserve (returning from the material deck on
// materialize) is a separate, out-of-scope mechanic.
// Notes carried over from the original definition:
//   - Silvie, Loved by All (TAMER + TERA)
gaScenario('meadowbloom-dryad-class-bonus-enter-buff')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, Loved by All')
    ->seed('myHand', 'Meadowbloom Dryad')
    ->castSpell('Meadowbloom Dryad', pay: 2)
    ->register();

// --- Melodious Flute: champion +1 level as long as you control an Animal or Beast ally ---
// Melodious Flute is NORM and REGALIA -- adding it to 'myHand' via setup gets silently
// redirected to the Material zone instead (HandAddReplacement), matching every other REGALIA
// item fixture this session, so it is seeded directly onto myField. Its static +1 level is
// unconditional; [Class Bonus] Banish: next Harmony action is a Melody is a separate,
// out-of-scope ability. Same indirect Glimpse-LV proof technique as beastbond-ears-level-
// while-animal-beast: Gray Wolf (a BEAST ally) is seeded alongside it, then Scry the Skies'
// Glimpse LV surfaces a real MZREARRANGE decision (invisible at the default level 0) proving
// the effective level is 1.
// Notes carried over from the original definition:
//   - Melodious Flute
//   - Gray Wolf (BEAST)
//   - Decline the fast-opportunity offer of Melodious Flute's own [Class Bonus] Banish ability
//   - (unrelated -- out of scope here) so Scry the Skies proceeds to its own resolution.
//   - MZREARRANGE response: keep original order -- only exists because the effective level is 1.
gaScenario('melodious-flute-level-while-animal-beast')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Melodious Flute')
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)->pass(1)->answer(1, 'Top=em6eEh9q8y;Bottom=')
    ->tested('Melodious Flute')
    ->expectDqEmpty()
    ->register();

// --- Magus Disciple: [Class Bonus] On Death: draw a card ---
// Magus Disciple's [Class Bonus] On Death is IsClassBonusActive($player) with NO class argument
// (degrades to "any champion present," same established finding as caretaker-drone-class-bonus-
// death-glimpse and gaias-songbird-class-bonus-enter-reveal-beast), so it fires unconditionally.
// Its separate unconditional static "+1 level while on the field" clause (GameLogic.php ~13185,
// a continuous computed-level check with no stored counter to assert) is out of scope here.
// Same Undeniable Truth sacrifice-kill technique as library-witch-on-death-draw (its cost
// handler directly sacrifices and queues its own 1-reserve cost -- no YES/NO or Glimpse step).
// Notes carried over from the original definition:
//   - CLERIC champion: the [Class Bonus] is active (a bare class-bonus gate used to be always true)
//   - Magus Disciple
//   - sacrifice Magus Disciple as the mandatory cost
//   - pay Undeniable Truth's 1-reserve cost
gaScenario('magus-disciple-class-bonus-on-death-draw')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->champion(classes: 'CLERIC')
    ->seed('myField', 'Magus Disciple')
    ->seed('myHand', 'Undeniable Truth')
    ->play('Undeniable Truth')->target('myField-1')->pay(1)
    ->tested('Magus Disciple')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"CLERIC"},"preparation":1}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'pnDhApDNvR')
    ->expectProperty('p1Graveyard-1', 'CardID', 'UaUfw7yFTW')
    ->expectProperty('p1Graveyard-1', 'Counters', '[]')
    ->expectProperty('p1Graveyard-1', 'TurnEffects', '[]')
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 2, 1)
    ->expectZoneCount('myMemory', 1, 1)
    ->register();

// --- Mana Limiter: Activate: draw a card ---
// Mana Limiter is REGALIA -- seeded directly onto myField. Same Scry-the-Skies warm-up + direct
// Activate:0 click as crystal-of-empowerment-banish-level (field items aren't reliably
// clickable as the literal first action of a fresh turn).
// Notes carried over from the original definition:
//   - Mana Limiter
//   - Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
//   - pay Scry the Skies' 1 reserve
gaScenario('mana-limiter-activate-draw')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Mana Limiter')
    ->seed('myHand', 'Scry the Skies')
    ->castSpell('Scry the Skies', pay: 1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Mana Limiter')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'F9POfB5Nah')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-0', 'CardID', 'IC3OU6vCnF')
    ->expectProperty('p1Banish-0', 'Status', '2')
    ->expectDamage('p1Banish-0', 0)
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 7, 1)
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Minister of Ceremony: [REST] As a Spell, deal 3 damage to target unit, only while facing East ---
// Minister of Ceremony's [REST] ability (GeneratedMacroCode.php
// activateAbilityAbilities["7gz0j8p4sx:0"], prereq requires GetShiftingCurrents(player) ===
// "EAST" and !HasOpportunity($player) -- i.e. only at slow speed on your own main phase) deals
// a flat 3 damage to a chosen unit. Targets its own controller's champion directly.
// Notes carried over from the original definition:
//   - Shifting Currents facing East
//   - Minister of Ceremony
gaScenario('minister-of-ceremony-east-rest-damage')
    ->deck(GA_KONGMING_BASE_DECK)
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'EAST'])
    ->seed('myField', 'Minister of Ceremony')
    ->patch('myField-1', ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-0')
    ->tested('Minister of Ceremony')
    ->expectDamage('myField-0', 3, '[REST] deals 3 damage to the chosen target, only allowed while facing East')
    ->expectProperty('myField-1', 'Status', '1', 'Minister of Ceremony rests to pay its own activation cost')
    ->register();

// --- Meiren of Verdancy: [Kongming Bonus] East->South Shifting Currents transition, recover 4 ---
// Meiren of Verdancy's East->South transition callback ($shiftingCurrentsTransitions,
// GameLogic.php ~20952) recovers 4 LIFE for the champion, but only when IsKongmingBonus() is
// true -- the same Fel Eidolon Subcards patch that unlocks the adjacent-direction Inherited
// trigger also makes the champion a Kongming lineage member, satisfying both gates at once.
// The champion is pre-damaged for 6 so the recovery is observable (not capped by full health).
// Notes carried over from the original definition:
//   - TERA unlock + Kongming Bonus + pre-damage
//   - Shifting Currents facing East
//   - Meiren of Verdancy
//   - Tera Sight, to trigger the Inherited SC choice on activation
//   - choose adjacent direction SOUTH
gaScenario('meiren-of-verdancy-east-south-recover')
    ->deck(GA_KONGMING_BASE_DECK)
    ->champion(lineage: ['Kongming, Fel Eidolon'], damage: 6)
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'EAST'])
    ->seed('myField', 'Meiren of Verdancy')
    ->seed('myHand', 'Tera Sight')
    ->play('Tera Sight')->answer(1, 'SOUTH')
    ->tested('Meiren of Verdancy')
    ->expectDamage('myField-0', 2, 'East->South transition recovers 4 LIFE for the [Kongming Bonus] champion, from 6 pre-existing damage down to 2')
    ->register();

// --- Mendcall Mercy: target player recovers 3x champion base level; opponent target gains Crowd's Favor ---
// Mendcall Mercy (2RKjpzEFV6, NORM, reserve 3) is played from hand;
// cardActivatedAbilities["2RKjpzEFV6:0"] (GeneratedMacroCode.php) asks YESNO "Target
// yourself?" -- answering NO targets the opponent. customDQHandlers
// ["2RKjpzEFV6:0:CardActivated-1"] computes amount = 3 * CardLevel(targetChampion->CardID)
// and calls RecoverChampion(targetPlayer, amount), then GainCrowdsFavor($player) only when
// the target was the opponent. The opponent's champion is CardID-patched to a level-2
// champion (Arisanna, Master Alchemist, ltv5klryvf) and pre-damaged by 8, so amount = 3*2 = 6
// is directly observable (Damage 8 -> 2), distinguishing the level-scaled amount from a flat
// "always recover some fixed amount" bug.
// Notes carried over from the original definition:
//   - opponent champion -> level 2, pre-damaged
gaScenario('mendcall-mercy-recover-opponent-crowds-favor')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Arisanna, Master Alchemist', damage: 8, player: 2)
    ->seed('myHand', 'Mendcall Mercy')
    ->play('Mendcall Mercy')->pay(3)->answer(1, 'NO')
    ->expectDamage('p2Field-0', 2, 'Opponent\'s level-2 champion recovers 3*2=6, from 8 damage down to 2')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'gpmJdGYqoC', 'Targeting the opponent grants the activator the Crowd\'s Favor status')
    ->register();

// --- Merlin, Memorite Vassal (6R8XmWoKLn): On Enter, you gain the Fractured Memories mastery.
// Then you may pay (2). If you do, summon a Memorite Blade token. ---
// Notes carried over from the original definition:
//   - Real level-up (0 -> 1): the pregame Spirit of Water champion (level 0, unpatched) levels
//   - for real into Merlin, Memorite Vassal, firing ITS genuine On Enter. One card seeded to
//   - memory pays the 1-memory level-up cost.
//   - "Pay (2) to summon Memorite Blade?" -- YES (the token is summoned immediately; the two
//   - ReserveCard prompts that follow pay for it from hand).
gaScenario('merlin-memorite-vassal-enter-mastery-summon-blade')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Merlin, Memorite Vassal
# Main
10 Dungeon Guide
DECK
    )
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'YES')->pay(2)
    ->tested('Merlin, Memorite Vassal')
    ->register();

// --- Merlin, Amethyst's Glow (dPP9I4nVn0): On Enter, put two sheen counters on target unit. ---
// Notes carried over from the original definition:
//   - Champion patched directly to Merlin, Memorite Vassal (level 1) -- representing already
//   - having leveled up once, WITHOUT re-testing that card's own On Enter here -- so a single
//   - real level-up (1 -> 2) reaches Merlin, Amethyst's Glow and fires ITS genuine On Enter.
//   - MZCHOOSE target for the 2 sheen counters -- the opponent's champion.
gaScenario('merlin-amethysts-glow-enter-sheen-target')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Merlin, Amethyst's Glow
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('theirField-0')
    ->tested('Merlin, Amethyst\'s Glow')
    ->expectProperty('myField-0', 'CardID', 'dPP9I4nVn0', 'Champion leveled up for real into Merlin, Amethyst\'s Glow')
    ->expectProperty('theirField-0', 'Counters', '{"sheen":2}', 'On Enter put two sheen counters on the targeted opposing champion')
    ->register();

// --- Merlin, Brilliant Vestige (2TCyILvBYa): [Sheen 8+] On Enter, you may banish a regalia card
// from your material deck. If you do, draw a card and put a preparation counter on your champion.
// ---
// Notes carried over from the original definition:
//   - Champion patched directly to Merlin, Amethyst's Glow (level 2) -- already-leveled
//   - precondition, not retesting dPP9I4nVn0's own Enter here -- plus 8 sheen counters on
//   - Fractured Memories (supporting precondition for the [Sheen 8+] clause; reaching 8 sheen
//   - for real would need many unrelated sheen-granting effects, out of scope for this fixture).
//   - End turn 1 (P1, then P2). Because the level-2 champion (Merlin, Amethyst's Glow) has its
//   - own activatable ability (discounted by sheen, affordable here), ending turn opens a
//   - "Take a fast action?" Opportunity window that must be explicitly declined (twice: an
//   - Active-Response round for P1, then a Beginning-of-Opportunity round) before the turn
//   - actually passes to P2, and again (twice more) before P2's own turn ends back to P1's
//   - turn-2 material phase.
//   - Real level-up (2 -> 3): P1's turn-2 material phase MZMAYCHOOSE offers the level-up target.
//   - "Banish a regalia card from your material deck?" (MZMAYCHOOSE) -- banish Prismspire Scepter.
gaScenario('merlin-brilliant-vestige-sheen8-enter-banish-draw-prep')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Merlin, Brilliant Vestige
1 Prismspire Scepter
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Merlin, Amethyst\'s Glow')
    ->seed('myMastery', 'Fractured Memories', properties: ['Counters' => ['sheen' => 8]])
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->pass(1)->pass(1)
    ->endTurn(2)->pass(1)->pass(1)->target('myMaterial-0')->target('myMaterial-0')
    ->tested('Merlin, Brilliant Vestige')
    ->expectProperty('myField-0', 'CardID', '2TCyILvBYa', 'Champion leveled up for real into Merlin, Brilliant Vestige')
    ->expectZoneCount('myMaterial', 0, 1, 'Prismspire Scepter was banished from the material deck')
    ->expectProperty('myField-0', 'Counters', '{"preparation":1}', 'Banishing the regalia put a preparation counter on the champion')
    ->register();

// --- Malignant Athame: [Class Bonus] On Champion Hit, swap the opponent's hand and memory; with four or more cards in their memory, deal 2 unpreventable damage ---
// Malignant Athame (0dr40tfllk, UMBRA REGALIA/WEAPON, power 1, durability 2): "[Class Bonus] On
// Champion Hit: You may have that opponent swap the cards in their hand and memory. Then if there
// are four or more cards in their memory, deal 2 unpreventable damage to the hit champion." The
// champion is patched to Tristan, Shadowdancer (ASSASSIN, UMBRA); the Athame is materialized for
// real in the materialize phase, then the champion attacks with it (champion attack -> choose the
// Athame as the weapon -> target the opposing champion), so onHitAbilities['0dr40tfllk:0'] ->
// MalignantAthameOnHit() fires for real and asks YES/NO. The opponent holds 8 cards in hand and 0
// in memory when hit. Answering YES swaps the opponent's 8-card hand with their empty memory (hand
// 0, memory 8), and because their memory now holds four or more cards the hit champion takes 2
// unpreventable damage on top of the 1 combat damage (total Damage 3).
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (ASSASSIN, UMBRA) -> Class Bonus active
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Malignant Athame
//   - the champion attacks
//   - choose the Athame as the weapon
//   - target the opposing champion
//   - have the opponent swap hand and memory
gaScenario('malignant-athame-on-champion-hit-swap-hand-memory')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Wind'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-5')
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(1, 'YES')
    ->tested('Malignant Athame')
    ->expectProperty('p1Field-1', 'CardID', '0dr40tfllk', 'Malignant Athame entered the field via a real materialize', step: 3)
    ->expectZoneCount('theirHand', 0, 1, 'The opponent\'s hand was swapped into memory: 0 cards in hand')
    ->expectZoneCount('theirMemory', 8, 1, 'The opponent\'s 8 hand cards are now in memory')
    ->expectDamage('p2Field-0', 3, '1 combat damage (Athame power 1) + 2 unpreventable damage because memory now has 4+ cards')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":1}', 'The Athame lost one durability counter from the attack')
    ->expectDqEmpty()
    ->register();

// --- Malignant Athame: declining the swap leaves the opponent's hand and memory alone and deals no extra damage ---
// Malignant Athame (0dr40tfllk, UMBRA REGALIA/WEAPON, power 1, durability 2): "[Class Bonus] On
// Champion Hit: You may have that opponent swap the cards in their hand and memory. Then if there
// are four or more cards in their memory, deal 2 unpreventable damage to the hit champion." The
// champion is patched to Tristan, Shadowdancer (ASSASSIN, UMBRA); the Athame is materialized for
// real in the materialize phase, then the champion attacks with it (champion attack -> choose the
// Athame as the weapon -> target the opposing champion), so onHitAbilities['0dr40tfllk:0'] ->
// MalignantAthameOnHit() fires for real and asks YES/NO. The opponent holds 8 cards in hand and 0
// in memory when hit. Answering NO keeps the opponent's 8 cards in hand and 0 in memory, and since
// memory has fewer than four cards the follow-up 2 unpreventable damage does not happen: only the
// 1 combat damage lands.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (ASSASSIN, UMBRA) -> Class Bonus active
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Malignant Athame
//   - the champion attacks
//   - choose the Athame as the weapon
//   - target the opposing champion
//   - decline the swap
gaScenario('malignant-athame-on-champion-hit-decline-swap')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Wind'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-5')
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(1, 'NO')
    ->tested('Malignant Athame')
    ->expectZoneCount('theirHand', 8, 1, 'The opponent keeps all 8 cards in hand')
    ->expectZoneCount('theirMemory', 0, 1, 'The opponent\'s memory stays empty')
    ->expectDamage('p2Field-0', 1, 'Only the 1 combat damage: memory has fewer than four cards, so no unpreventable damage')
    ->expectDqEmpty()
    ->register();

// --- Mastermind Scheme: [Class Bonus] Efficiency; doubling to eight or more preparation counters also grants agility 3 ---
// Mastermind Scheme (9lbewemius, NORM slow action, printed reserve 6): "[Class Bonus] Efficiency
// (This card costs LV less to activate.) Double the amount of preparation counters on your
// champion. Then if there are eight or more preparation counters on your champion, you gain
// agility 3 for this turn." The champion is patched to Tristan, Hired Blade (level 2, ASSASSIN)
// with 5 preparation counters. Efficiency makes it cost 6 - 2 = 4 reserve payments (4 cards in
// memory); the ability doubles the counters to 10 and, being eight or more, adds the AGILITY_3
// global effect.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (level 2, ASSASSIN) with 5 preparation counters
//   - Mastermind Scheme (printed reserve 6) -> p1 myHand-7
//   - play Mastermind Scheme
gaScenario('mastermind-scheme-efficiency-double-preparation-agility-at-eight')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 5]])
    ->seed('myHand', 'Mastermind Scheme')
    ->castSpell('Mastermind Scheme', pay: 4)
    ->expectZoneCount('myMemory', 4, 1, 'Exactly four reserve payments: printed 6 minus Efficiency LV 2')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":10}', 'The champion\'s 5 preparation counters were doubled to 10')
    ->expectProperty('p1Graveyard-0', 'CardID', '9lbewemius', 'Mastermind Scheme resolved')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'AGILITY_3', 'Eight or more counters after doubling grants agility 3')
    ->expectDqEmpty()
    ->register();

// --- Mastermind Scheme: doubling to fewer than eight preparation counters grants no agility ---
// Mastermind Scheme (9lbewemius, NORM slow action, printed reserve 6): "[Class Bonus] Efficiency
// (This card costs LV less to activate.) Double the amount of preparation counters on your
// champion. Then if there are eight or more preparation counters on your champion, you gain
// agility 3 for this turn." The champion is patched to Tristan, Hired Blade (level 2, ASSASSIN)
// with 3 preparation counters. Efficiency makes it cost 6 - 2 = 4 reserve payments (4 cards in
// memory); the ability doubles the counters to 6, which is below eight, so no agility.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (level 2, ASSASSIN) with 3 preparation counters
//   - Mastermind Scheme (printed reserve 6) -> p1 myHand-7
//   - play Mastermind Scheme
gaScenario('mastermind-scheme-double-preparation-no-agility-below-eight')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 3]])
    ->seed('myHand', 'Mastermind Scheme')
    ->castSpell('Mastermind Scheme', pay: 4)
    ->expectZoneCount('myMemory', 4, 1, 'Exactly four reserve payments: printed 6 minus Efficiency LV 2')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":6}', 'The champion\'s 3 preparation counters were doubled to 6')
    ->expectProperty('p1Graveyard-0', 'CardID', '9lbewemius', 'Mastermind Scheme resolved')
    ->expectProperty('p1GlobalEffects-0', 'CardID', '', 'Fewer than eight counters after doubling: no agility global effect')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Burnished Avenger: On Enter, look at the top card; an attack card may be banished ---
// Mordred, Burnished Avenger (OWCdWq3mXY, CHAMPION level 1, WATER WARRIOR): "On Enter: Look at the
// top card of your deck. If it's an attack card, you may banish it. If you do, you may activate
// that card this turn." The starting champion (Spirit of Water, level 0) really levels up in the
// materialize phase of turn 3 (1 memory card paid), so enterAbilities["OWCdWq3mXY:0"] fires: the
// seeded top-of-deck Striking Tides (an attack card) is revealed, the YES/NO is answered YES and
// the card moves from the deck to the banishment zone, tagged with the _mordredBurnished turn
// effect that lets it be activated from banishment this turn.
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - Striking Tides (attack card) is the top card of player 1's deck
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up Spirit of Water into Mordred, Burnished Avenger (real level-up -> On Enter fires)
//   - banish the revealed attack card
gaScenario('mordred-burnished-avenger-levelup-enter-banish-top-attack')
    ->deck($mordredDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->deckTop(['Striking Tides'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'YES')
    ->tested('Mordred, Burnished Avenger')
    ->expectProperty('p1Field-0', 'CardID', 'OWCdWq3mXY', 'The real level-up into Mordred, Burnished Avenger completed')
    ->expectProperty('p1Banish-1', 'CardID', 'qrxQGA1pc6', 'The revealed top attack card (Striking Tides) was banished from the deck')
    ->expectProperty('p1Banish-1', 'TurnEffects', '["_mordredBurnished"]', 'The banished card carries the this-turn activation permission')
    ->expectZoneCount('myHand', 8, 1, 'Hand 8 = 7 + the turn draw: the banished card was not the one drawn')
    ->expectProperty('p1Field-0', 'Status', '2', 'The freshly leveled champion is awake')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Burnished Avenger: the banished attack card may be activated this turn (from banishment) ---
// Mordred, Burnished Avenger (OWCdWq3mXY): "If you do, you may activate that card this turn." After
// the real level-up and the YES banish (see mordred-burnished-avenger-levelup-enter-banish-top-
// attack), player 1 activates the banished Striking Tides straight from banishment (the
// ActionMap "myBanish" case recognises the _mordredBurnished tag): it is paid (the first attack card
// of a turn in which Mordred leveled up costs 2 less, so 1 reserve payment instead of 3), aimed at
// player 2's champion and deals its printed 4 damage; the card ends in the graveyard.
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - Striking Tides is the top card of player 1's deck
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Mordred, Burnished Avenger
//   - banish the revealed attack card
//   - activate the banished Striking Tides this turn (banish-0 is the memory-cost card)
//   - attack player 2's champion
gaScenario('mordred-burnished-avenger-activate-banished-attack-this-turn')
    ->deck($mordredDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->deckTop(['Striking Tides'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'YES')
    ->action(1, 10002, 'myBanish-1!FSM!')->pay(1)->target('theirField-0')
    ->tested('Mordred, Burnished Avenger')
    ->expectDamage('p2Field-0', 4, 'The banished Striking Tides was activated from banishment and dealt its printed 4 damage to player 2\'s champion')
    ->expectProperty('p1Graveyard-0', 'CardID', 'qrxQGA1pc6', 'The activated attack card ended in the graveyard')
    ->expectZoneCount('myBanish', 1, 1, 'Only the level-up memory-cost card remains in banishment: the attack card left it')
    ->expectZoneCount('myMemory', 1, 1, 'Exactly ONE reserve payment: the first attack card of a level-up turn costs 2 less (3 - 2)')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Burnished Avenger: On Enter does nothing when the top card is not an attack card ---
// Mordred, Burnished Avenger (OWCdWq3mXY): "If it's an attack card, you may banish it." The top
// card of the deck is a Dungeon Guide (an ally): the real level-up reveals it but no banish prompt
// appears, nothing is banished and the card is simply drawn in the draw step.
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - Dungeon Guide (an ally, not an attack card) is the top card
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Mordred, Burnished Avenger (real level-up -> On Enter fires)
gaScenario('mordred-burnished-avenger-levelup-enter-top-card-not-attack')
    ->deck($mordredDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->deckTop(['Dungeon Guide'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Mordred, Burnished Avenger')
    ->expectProperty('p1Field-0', 'CardID', 'OWCdWq3mXY', 'The real level-up into Mordred, Burnished Avenger completed')
    ->expectZoneCount('myBanish', 1, 1, 'Nothing but the level-up memory-cost card was banished')
    ->expectZoneCount('myHand', 8, 1, 'Hand 8 = 7 + the turn draw (the Dungeon Guide top card was simply drawn)')
    ->expectProperty('p1Field-0', 'Status', '2', 'The freshly leveled champion is awake')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Burnished Avenger: banishing the revealed attack card is optional (declined) ---
// Mordred, Burnished Avenger (OWCdWq3mXY): "you MAY banish it". The YES/NO is answered NO: the
// revealed Striking Tides is not banished (so it carries no activation permission) and ends up in
// player 1's hand through the normal draw step.
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - Striking Tides is the top card
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Mordred, Burnished Avenger
//   - decline to banish the attack card
gaScenario('mordred-burnished-avenger-levelup-enter-decline-banish')
    ->deck($mordredDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->deckTop(['Striking Tides'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'NO')
    ->tested('Mordred, Burnished Avenger')
    ->register();

// --- Mordred, Burnished Avenger: the permission to activate the banished attack lasts only THIS turn ---
// Mordred, Burnished Avenger (OWCdWq3mXY): "you may activate that card this turn." Player 1
// banishes the revealed Striking Tides but does not activate it; after both players pass a full
// turn, trying to activate it from banishment on player 1's next turn is rejected by the engine
// (the _mordredBurnished tag is a turn effect that expired).
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - Striking Tides is the top card
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Mordred, Burnished Avenger
//   - banish the revealed attack card; it is NOT activated this turn
//   - end player 1 turn 3
//   - player 2 declines its materialize offer at the start of turn 4
//   - end player 2 turn 4 -> player 1 turn 5 materialize phase
//   - decline the materialize offer
//   - try to activate the banished attack card on a later turn: nothing happens
gaScenario('mordred-burnished-avenger-banished-attack-not-activatable-next-turn')
    ->deck($mordredDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->deckTop(['Striking Tides'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'YES')
    ->endTurn()->pass(2)
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myBanish-1!FSM!')
    ->tested('Mordred, Burnished Avenger')
    ->expectProperty('p1Banish-1', 'CardID', 'qrxQGA1pc6', 'The banished Striking Tides is still in banishment')
    ->expectProperty('p1Banish-1', 'TurnEffects', '[]', 'The this-turn activation permission expired')
    ->expectZoneCount('myBanish', 2, 1, 'Nothing left banishment: the activation attempt did nothing')
    ->expectZoneCount('myMemory', 0, 1, 'No payment was requested')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Burnished Avenger: inherited effect -- after Mordred leveled up this turn the FIRST attack card costs 2 less ---
// Mordred, Burnished Avenger (OWCdWq3mXY): "[Mordred Bonus] Inherited Effect -- As long as Mordred
// has leveled up this turn, the first attack card you activate this turn costs 2 less to
// activate." (CalculateActivationReserveCost: ChampionHasInLineage(OWCdWq3mXY) + the
// LEVELED_UP_THIS_TURN global effect + no attack card activated yet.) The champion is patched to
// Burnished Avenger (level 1) and performs a REAL level-up into Mordred, Flawless Blade (2 memory
// cards): the effect is inherited because Burnished Avenger is in the lineage. Striking Tides
// (printed reserve 3) is then activated and needs exactly ONE reserve payment.
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger (level 1)
//   - level-up memory cost 1/2
//   - level-up memory cost 2/2
//   - Striking Tides (attack, printed reserve 3) -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - real level-up into Mordred, Flawless Blade (the discount is inherited from Burnished Avenger)
//   - activate Striking Tides
//   - attack player 2's champion
gaScenario('mordred-burnished-avenger-inherited-first-attack-costs-two-less')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-1')
    ->castSpell('Striking Tides', pay: 1, target: 'theirField-0')
    ->tested('Mordred, Burnished Avenger')
    ->expectProperty('p1Field-0', 'CardID', 'WI2owxIw0z', 'Mordred leveled up this turn (Burnished Avenger -> Flawless Blade); the effect is inherited from the lineage')
    ->expectZoneCount('myMemory', 1, 1, 'Exactly ONE reserve payment: printed 3 - 2 (the two level-up memory cards were banished, not paid into memory)')
    ->expectDamage('p2Field-0', 4, 'Striking Tides resolved and dealt its 4 damage')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Burnished Avenger: the first-attack discount needs Mordred to have leveled up THIS turn ---
// Mordred, Burnished Avenger (OWCdWq3mXY): the inherited effect only applies "as long as Mordred
// has leveled up this turn". Same lineage as mordred-burnished-avenger-inherited-first-attack-
// costs-two-less but the materialize offer is declined (no level-up this turn): Striking Tides
// (printed reserve 3) costs its full THREE reserve payments.
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger (level 1)
//   - Striking Tides -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the level-up offer: Mordred does NOT level up this turn
//   - activate Striking Tides
//   - attack player 2's champion
gaScenario('mordred-burnished-avenger-no-discount-without-levelup-this-turn')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Striking Tides', pay: 3, target: 'theirField-0')
    ->tested('Mordred, Burnished Avenger')
    ->expectProperty('p1Field-0', 'CardID', 'OWCdWq3mXY', 'Mordred did not level up this turn')
    ->expectZoneCount('myMemory', 3, 1, 'THREE reserve payments: no discount without a level-up this turn')
    ->expectDamage('p2Field-0', 4, 'Striking Tides resolved and dealt its 4 damage')
    ->expectProperty('p1Field-0', 'Status', '1', 'The champion attacked and is rested')
    ->expectDqEmpty()
    ->register();

// --- Banishment activation: a click blocked by the element requirement is refused without consuming the permission ---
// Mordred, Burnished Avenger (OWCdWq3mXY): "you may activate that card this turn." ENGINE BUG (fixed):
// the "myBanish" branch moved the clicked card into hand before the activation's own legality gates
// ran, so a banished attack card the champion cannot use (here the FIRE Emberslash, while Mordred
// only enables WATER) was silently converted into a hand card and lost its permission. Real level-up
// banishes the Emberslash from the top of the deck; the click must be refused (card stays in
// banishment tagged _mordredBurnished, hand/memory/stack unchanged).
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - Emberslash (a FIRE Warrior attack card) is the top card of player 1's deck
//   - 1: end player 1 turn 1
//   - 2: end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - 3: level up into Mordred, Burnished Avenger (On Enter fires)
//   - 4: banish the revealed Emberslash (tagged _mordredBurnished)
//   - 5: click the banished FIRE attack card (banish-0 is the memory-cost card): accepted as a no-op, refusal asserted on state
gaScenario('mordred-burnished-avenger-banished-off-element-attack-click-refused')
    ->deck($mordredDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->deckTop(['Emberslash'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'YES')
    ->action(1, 10002, 'myBanish-1!FSM!')
    ->tested('Mordred, Burnished Avenger')
    ->register();

// Mandatory additional cost whose legality was only checked AFTER the card was announced. Memory Invocation (io7maIjC4u,
// WATER spell, reserve 3): "As an additional cost, banish a card with floating memory from your graveyard." With no such
// card in the graveyard the old code announced the card (moved it onto the effect stack), then refused and returned,
// stranding it. Three payers are in hand so the reserve cost itself is affordable.
// Notes carried over from the original definition:
//   - empty hand (7 cards -> banishment, so the graveyard holds no floating-memory card)
//   - Memory Invocation -> p1 myHand-0
//   - 1: click Memory Invocation with no floating-memory card in the graveyard: accepted as a no-op, refusal asserted on state
gaScenario('memory-invocation-no-floating-memory-click-refused')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myBanish')
    ->seed('myHand', 'Memory Invocation')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->playSlot(0)
    ->tested('Memory Invocation')
    ->register();

// --- Mordred, Aurelian Regent: On Enter, Mordred's next attack gets +3 POWER and "On Hit: Recover 3" ---
// Mordred, Aurelian Regent (XPl2UAO9se, CHAMPION level 3, LUXEM): "On Enter: Mordred's next attack
// this turn gets +3 POWER and gains 'On Hit: Recover 3.' Whenever you activate a luxem element
// card, delevel Mordred." The champion is patched to Mordred, Flawless Blade (level 2, carrying 5
// damage) and performs a REAL level-up into Aurelian Regent (3 memory cards), so its On Enter
// fires. Striking Tides (WATER, not luxem: no delevel) is then activated (1 reserve payment: the
// first-attack discount inherited from Burnished Avenger after a level-up) and hits player 2's
// champion for 4 + 3 = 7 while the On Hit recovers 3 of Mordred's damage (5 -> 2).
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2) with 5 damage
//   - level-up memory cost 1/3
//   - level-up memory cost 2/3
//   - level-up memory cost 3/3
//   - Striking Tides (WATER attack, power 4) -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - real level-up into Mordred, Aurelian Regent (On Enter fires)
//   - activate Striking Tides
//   - attack player 2's champion
gaScenario('mordred-aurelian-regent-levelup-enter-next-attack-plus-three-and-recover')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'], damage: 5)
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')
    ->castSpell('Striking Tides', pay: 1, target: 'theirField-0')
    ->tested('Mordred, Aurelian Regent')
    ->expectProperty('p1Field-0', 'CardID', 'XPl2UAO9se', 'The real level-up into Mordred, Aurelian Regent completed and a non-luxem card does not delevel it')
    ->expectDamage('p2Field-0', 7, 'Striking Tides hit for printed 4 + 3 (the Regent\'s On Enter bonus) = 7')
    ->expectDamage('p1Field-0', 2, 'The granted On Hit recovered 3 of Mordred\'s 5 damage (5 -> 2)')
    ->expectZoneCount('myMemory', 1, 1, 'One reserve payment: printed 3 - 2 (first attack after a level-up, inherited from Burnished Avenger)')
    ->expectProperty('p1Field-0', 'Status', '1', 'The champion attacked and is rested')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Aurelian Regent: the bonus is for MORDRED's next attack -- an ally's attack does not use it up ---
// Mordred, Aurelian Regent (XPl2UAO9se): "Mordred's next attack this turn gets +3 POWER". After the
// real level-up (as in mordred-aurelian-regent-levelup-enter-next-attack-plus-three-and-recover) an
// awake Dungeon Guide attacks first: it deals only its printed 1 and does not consume the bonus.
// Mordred's own Striking Tides attack afterwards still gets +3 POWER (7 damage) and "On Hit:
// Recover 3": player 2's champion has taken 1 + 7 = 8 in total and Mordred is down to 2 damage.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2) with 5 damage
//   - Dungeon Guide (awake ally, 1 power) -> p1 field-1
//   - level-up memory cost 1/3
//   - level-up memory cost 2/3
//   - level-up memory cost 3/3
//   - Striking Tides -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - real level-up into Mordred, Aurelian Regent (On Enter fires)
//   - the Dungeon Guide attacks first (the level-up moved the champion behind it, to myField-1)
//   - targeting player 2's champion
//   - then Mordred activates Striking Tides
//   - attack player 2's champion
gaScenario('mordred-aurelian-regent-ally-attack-does-not-use-up-next-attack-bonus')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'], damage: 5)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')
    ->castSpell('Striking Tides', pay: 1, target: 'theirField-0')
    ->tested('Mordred, Aurelian Regent')
    ->expectProperty('p1Field-1', 'CardID', 'XPl2UAO9se', 'Mordred leveled up into Aurelian Regent this turn (the level-up moved the champion behind the Dungeon Guide)')
    ->expectProperty('p1Field-0', 'Status', '1', 'The Dungeon Guide attacked first (it is rested)')
    ->expectDamage('p2Field-0', 8, '1 (Dungeon Guide, no bonus) + 7 (Mordred: printed 4 + 3) = 8 damage in total')
    ->expectDamage('p1Field-1', 2, 'Mordred\'s own attack still had \'On Hit: Recover 3\' (5 -> 2)')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Fated Luminary: Mordred can level up into a champion of the SAME base level and draws two cards when he does ---
// Mordred, Fated Luminary (KqBosnU7pU, CHAMPION level 3, EXALTED/NORM): "Mordred can level up into
// champions of the same base level. When he does, draw two cards." The champion is patched to Fated
// Luminary (level 3) and performs a REAL same-level level-up into Mordred, Aurelian Regent (level
// 3, memory cost 3): the engine offers it (ChampionCanSameLevelUp) and draws two cards -- the
// hand ends at 10 = 7 + 1 turn draw + 2.
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary (level 3)
//   - level-up memory cost 1/3
//   - level-up memory cost 2/3
//   - level-up memory cost 3/3
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - same-level level-up into Mordred, Aurelian Regent
gaScenario('mordred-fated-luminary-same-level-levelup-draws-two')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')
    ->tested('Mordred, Fated Luminary')
    ->expectProperty('p1Field-0', 'CardID', 'XPl2UAO9se', 'The same-level level-up Fated Luminary -> Aurelian Regent (both level 3) was accepted')
    ->expectProperty('p1Field-0', 'Subcards', '["KqBosnU7pU","WI2owxIw0z","OWCdWq3mXY","tafqldAGRF"]', 'Fated Luminary moved into the lineage')
    ->expectZoneCount('myHand', 10, 1, 'Hand 10 = 7 + 1 turn draw + 2 cards drawn by the same-level level-up')
    ->expectZoneCount('myBanish', 3, 1, 'The 3 level-up memory cards were banished')
    ->expectProperty('p1Field-0', 'Status', '2', 'The freshly leveled champion is awake')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Fated Luminary: without Fated Luminary in the lineage a level 3 champion cannot level up into another level 3 champion ---
// Mordred, Fated Luminary (KqBosnU7pU): the same-level level-up exists only "for Mordred" with this
// lineage card. The champion is Aurelian Regent whose lineage does NOT contain Fated Luminary:
// choosing Fated Luminary (another level 3 champion) from the material deck is rejected by the
// engine, the champion stays Aurelian Regent and no card is drawn.
// Notes carried over from the original definition:
//   - Mordred, Aurelian Regent (level 3, no Fated Luminary in lineage)
//   - memory 1/3
//   - memory 2/3
//   - memory 3/3
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
gaScenario('mordred-fated-luminary-same-level-levelup-needs-luminary-in-lineage')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Aurelian Regent', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)
    ->rejects(1, 'myMaterial-2', 'A same-level level-up into Fated Luminary is rejected: Fated Luminary is not in the lineage')
    ->pass(1)
    ->tested('Mordred, Fated Luminary')
    ->expectProperty('p1Field-0', 'CardID', 'XPl2UAO9se', 'The champion is still Aurelian Regent: the same-level level-up into Fated Luminary was rejected')
    ->expectZoneCount('myMaterial', 7, 1, 'The material deck is untouched (7 cards)')
    ->expectZoneCount('myHand', 11, 1, 'Hand 11 = 7 + 1 turn draw + 3 memory cards returned by recollection: NO cards drawn by a level-up')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Fated Luminary: a NORMAL level-up INTO Fated Luminary draws nothing (engine bug, FIXED) ---
// Mordred, Fated Luminary (KqBosnU7pU): "Mordred can level up into champions of the same base
// level. When he does, draw two cards." -- the cards are drawn only when Mordred uses that same
// base level level-up. FIXED (was: DoMaterialize() in Custom/MaterializeLogic.php drew two cards
// after EVERY champion level-up whose resulting lineage contained Fated Luminary, without checking
// that the level-up was a same base level one -- so an ordinary level 2 -> level 3 level-up of
// Mordred, Flawless Blade INTO Fated Luminary drew two cards too). Now the draw needs the old and
// new champion to share a base level. The hand is 8 = 7 + 1 turn draw. History: before the fix this
// fixture was named ...-draws-two and its REGRESSION GUARD assertions pinned the buggy hand size 10.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2)
//   - level-up memory cost 1/3
//   - level-up memory cost 2/3
//   - level-up memory cost 3/3
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - ordinary level 2 -> level 3 level-up into Mordred, Fated Luminary
gaScenario('mordred-fated-luminary-normal-levelup-into-luminary-draws-nothing')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-2')
    ->tested('Mordred, Fated Luminary')
    ->expectProperty('p1Field-0', 'CardID', 'KqBosnU7pU', 'Flawless Blade leveled up (an ordinary level 2 -> 3 level-up) into Fated Luminary')
    ->expectZoneCount('myHand', 8, 1, 'No cards were drawn: hand 8 = 7 + 1 turn draw (this was not a same-base-level level-up)')
    ->expectProperty('p1Field-0', 'Status', '2', 'The freshly leveled champion is awake')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Fated Luminary: attack cards in the graveyard have floating memory (paying part of a champion level-up) (engine bug, FIXED) ---
// Mordred, Fated Luminary (KqBosnU7pU): "Attack cards in your graveyard have floating memory and
// 'Ephemerate -- (X)'." (While paying a memory cost you may banish a card with floating memory from
// your graveyard to pay for 1 of that cost.) FIXED (was: HasFloatingMemory() in Custom/GameLogic.php
// granted the Fated Luminary floating memory only when isset($obj->Controller) -- but a graveyard
// object has no persisted Controller (the Graveyard zone class serializes only CardID; it carries
// just PlayerID), so every graveyard card parsed back from the game state failed the check). The
// champion is Fated Luminary, the graveyard holds a Striking Tides (an attack card) and the two seeded
// memory cards are one short of the memory cost 3 of the same-level level-up into Aurelian Regent: the
// REAL level-up is offered the floating payment, the Striking Tides is banished for 1 point and the
// level-up completes. History: before the fix this fixture was named
// ...-never-floats-for-memory-cost and its REGRESSION GUARD assertions pinned the refusal ("Cannot
// pay costs for the selected material card. Action undone.").
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary (level 3)
//   - Striking Tides (attack card -> should have floating memory) -> p1 graveyard-0
//   - memory 1/2
//   - memory 2/2 (one short of the cost 3)
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - start the same-level level-up into Mordred, Aurelian Regent (memory cost 3)
//   - float the graveyard Striking Tides for 1 of the cost
gaScenario('mordred-fated-luminary-graveyard-attack-card-floats-for-memory-cost')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Striking Tides')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')->target('myGraveyard-0')
    ->tested('Mordred, Fated Luminary')
    ->expectProperty('p1Field-0', 'CardID', 'XPl2UAO9se', 'The same-level level-up into Mordred, Aurelian Regent (memory cost 3, only 2 memory cards) completed')
    ->expectProperty('p1Banish-0', 'CardID', 'qrxQGA1pc6', 'The graveyard attack card was banished as the floating payment')
    ->expectZoneCount('myGraveyard', 0, 1, 'The graveyard is empty (the attack card floated away)')
    ->expectZoneCount('myBanish', 3, 1, '3 banished = the floated attack card + the 2 memory cards')
    ->expectZoneCount('myMemory', 0, 1, 'Memory is empty')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Fated Luminary: a graveyard card that is not an attack card does not float ---
// Mordred, Fated Luminary (KqBosnU7pU): only ATTACK cards in the graveyard have floating memory. The
// champion is Fated Luminary and the graveyard holds only a Dungeon Guide (an ally): the same-level
// level-up into Aurelian Regent (memory cost 3) is NOT payable by floating -- with only two memory
// cards the payment is refused ("Cannot pay costs ... Action undone"), the champion stays Fated
// Luminary and the Dungeon Guide stays in the graveyard.
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary (level 3)
//   - Dungeon Guide (no floating memory) -> p1 graveyard-0
//   - memory 1/2
//   - memory 2/2 (one short of the cost 3)
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - same-level level-up into Mordred, Aurelian Regent (memory cost 3): refused, only 2 payable
gaScenario('mordred-fated-luminary-non-attack-graveyard-card-does-not-float')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')
    ->tested('Mordred, Fated Luminary')
    ->register();

// --- Mordred, Fated Luminary: Ephemerate -- an attack card in the graveyard can be activated by paying its reserve cost ---
// Mordred, Fated Luminary (KqBosnU7pU): attack cards in the graveyard have "Ephemerate -- (X)" where
// X is the card's reserve cost. The graveyard holds a Striking Tides (reserve 3): on turn 3 player 1
// activates it FROM THE GRAVEYARD (generic Ephemerate path), pays X = 3 and aims it at player
// 2's champion, which takes its printed 4 damage. (What happens to the card afterwards is pinned in
// mordred-fated-luminary-ephemerated-attack-card-is-not-banished.)
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary (level 3)
//   - Striking Tides in the graveyard -> p1 graveyard-0
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Striking Tides from the graveyard via Ephemerate
//   - the Ephemerate cost (X = reserve cost 3)
//   - attack player 2's champion
gaScenario('mordred-fated-luminary-ephemerate-attack-card-from-graveyard')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(3)->target('theirField-0')
    ->tested('Mordred, Fated Luminary')
    ->expectDamage('p2Field-0', 4, 'The attack card activated FROM THE GRAVEYARD dealt its printed 4 damage')
    ->expectZoneCount('myMemory', 3, 1, 'Exactly X = 3 reserve payments (Striking Tides\' reserve cost)')
    ->expectProperty('p1Field-0', 'Status', '1', 'The champion attacked (rested)')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Fated Luminary: an attack card activated via Ephemerate is banished after combat (engine bug, FIXED) ---
// Mordred, Fated Luminary (KqBosnU7pU): "(You may activate cards with ephemerate from your graveyard by
// paying that cost. Attack cards played this way become ephemeral in the intent.)" -- an ephemeral
// object is banished whenever it would leave, so the attack is banished at the end of combat. FIXED
// (was: the ATTACK branch of the activation resolution in Custom/GameLogic.php only tagged non-ACTION,
// non-ATTACK field objects as ephemeral, and ClearIntent() in Custom/CombatLogic.php sent every
// intent card to the graveyard, so the Striking Tides came back to the GRAVEYARD afterwards and could
// be ephemerated again for free value). Same actions as
// mordred-fated-luminary-ephemerate-attack-card-from-graveyard: the Striking Tides is now in the
// BANISH zone and the graveyard is empty. History: before the fix this fixture was named
// ...-is-not-banished and its REGRESSION GUARD assertions pinned the graveyard outcome.
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary (level 3)
//   - Striking Tides in the graveyard -> p1 graveyard-0
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Striking Tides from the graveyard via Ephemerate
//   - the Ephemerate cost (X = reserve cost 3)
//   - attack player 2's champion
gaScenario('mordred-fated-luminary-ephemerated-attack-card-is-banished-after-combat')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(3)->target('theirField-0')
    ->tested('Mordred, Fated Luminary')
    ->expectProperty('p1Banish-0', 'CardID', 'qrxQGA1pc6', 'The ephemerated Striking Tides was banished after combat')
    ->expectZoneCount('myGraveyard', 0, 1, 'The graveyard is empty again: the ephemeral attack did not return to it')
    ->expectZoneCount('myBanish', 1, 1, 'Exactly the ephemerated attack card is banished')
    ->expectDamage('p2Field-0', 4, 'The attack itself resolved for 4')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Fated Luminary: an attack card played normally from HAND still goes to the graveyard ---
// Mordred, Fated Luminary (KqBosnU7pU): only attack cards played via EPHEMERATE (from the graveyard)
// become ephemeral. Guard for the ephemeral-intent tagging: a Striking Tides played from the HAND
// (paying its printed reserve cost 3, hitting player 2's champion for 4) goes to the graveyard after
// combat -- it is not banished.
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary (level 3)
//   - Striking Tides -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Striking Tides from HAND
//   - printed reserve cost 3
//   - attack player 2's champion
gaScenario('mordred-fated-luminary-attack-card-played-from-hand-goes-to-graveyard')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Striking Tides', pay: 3, target: 'theirField-0')
    ->tested('Mordred, Fated Luminary')
    ->expectProperty('p1Graveyard-0', 'CardID', 'qrxQGA1pc6', 'The Striking Tides played from hand is in the graveyard after combat')
    ->expectZoneCount('myGraveyard', 1, 1, 'Graveyard holds exactly the attack card')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished: the attack was not ephemeral')
    ->expectDamage('p2Field-0', 4, 'The attack itself resolved for 4')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Fated Luminary: only ATTACK cards in the graveyard gain Ephemerate ---
// Mordred, Fated Luminary (KqBosnU7pU): "Attack cards in your graveyard have ... Ephemerate". A
// Dungeon Guide (an ally) in the graveyard is not an attack card: clicking it does nothing (no
// payment is requested, it stays in the graveyard).
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary (level 3)
//   - Dungeon Guide (not an attack card) in the graveyard -> p1 graveyard-0
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - try to activate the Dungeon Guide from the graveyard: nothing happens
gaScenario('mordred-fated-luminary-non-attack-card-in-graveyard-cannot-ephemerate')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myGraveyard-0!FSM!')
    ->tested('Mordred, Fated Luminary')
    ->expectProperty('p1Graveyard-0', 'CardID', 'em6eEh9q8y', 'The Dungeon Guide stays in the graveyard')
    ->expectZoneCount('myHand', 8, 1, 'No card was moved to hand / activated')
    ->expectZoneCount('myMemory', 0, 1, 'No payment was requested')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Flawless Blade: attack cards in your graveyard have floating memory (paying part of a champion level-up) ---
// Mordred, Flawless Blade (WI2owxIw0z, CHAMPION level 2, NORM): "Attack cards in your graveyard have
// floating memory. (While paying for a memory cost, you may banish a card with floating memory from
// your graveyard to pay for 1 of that cost.)" The champion IS Mordred, Flawless Blade (the static
// ability works while it is on the field); the graveyard holds a Dungeon Guide (an ally) and a
// Striking Tides (an attack card). The REAL level-up into Mordred, Aurelian Regent (memory cost 3, two
// seeded memory cards) is one point short, which the floating payment makes up: only the attack card
// is offered (the MZMULTICHOOSE candidates are 'myGraveyard-1'); the Dungeon Guide
// is not offered, Striking Tides is banished for 1 point and the two memory cards pay the rest.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2)
//   - Dungeon Guide (no floating memory) -> p1 graveyard-0
//   - Striking Tides (attack card) -> p1 graveyard-1
//   - memory 1/2
//   - memory 2/2 (one short of the cost 3)
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - start the level-up into Mordred, Aurelian Regent (memory cost 3)
//   - float the Striking Tides for 1 of the cost
gaScenario('mordred-flawless-blade-attack-card-in-graveyard-floats-for-levelup')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Striking Tides')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')->target('myGraveyard-1')
    ->tested('Mordred, Flawless Blade')
    ->expectProperty('p1Field-0', 'CardID', 'XPl2UAO9se', 'The level-up into Mordred, Aurelian Regent (memory cost 3, only 2 memory cards) completed')
    ->expectProperty('p1Banish-0', 'CardID', 'qrxQGA1pc6', 'The graveyard attack card was banished as the floating payment')
    ->expectProperty('p1Graveyard-0', 'CardID', 'em6eEh9q8y', 'The Dungeon Guide (not an attack card) stayed in the graveyard')
    ->expectZoneCount('myBanish', 3, 1, '3 banished = the floated attack card + the 2 memory cards')
    ->expectZoneCount('myMemory', 0, 1, 'Memory is empty')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Flawless Blade: a graveyard card that is not an attack card does not float ---
// Mordred, Flawless Blade (WI2owxIw0z): only ATTACK cards in the graveyard have floating memory. The
// champion is Flawless Blade and the graveyard holds only a Dungeon Guide (an ally): the level-up
// into Aurelian Regent (memory cost 3) is NOT payable by floating -- with only two memory cards
// the payment is refused ("Cannot pay costs ... Action undone"), the champion stays Flawless Blade
// and the Dungeon Guide stays in the graveyard.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2)
//   - Dungeon Guide (no floating memory) -> p1 graveyard-0
//   - memory 1/2
//   - memory 2/2 (one short of the cost 3)
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Mordred, Aurelian Regent (memory cost 3): refused, only 2 payable
gaScenario('mordred-flawless-blade-non-attack-card-in-graveyard-does-not-float')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')
    ->tested('Mordred, Flawless Blade')
    ->register();

// --- Mordred, Flawless Blade: an OPPONENT's Flawless Blade does NOT make MY graveyard attack cards float (engine bug, FIXED) ---
// Mordred, Flawless Blade (WI2owxIw0z): "Attack cards in YOUR graveyard have floating memory." FIXED
// (was: HasFloatingMemory() in Custom/GameLogic.php looped over BOTH players' fields and granted
// floating memory to every attack card in every graveyard as soon as ANY Flawless Blade was on the
// field, without checking that the Blade's controller is the graveyard's owner). Player 2's champion
// is Mordred, Flawless Blade; player 1 (plain Spirit of Water, no Flawless Blade) holds a Striking
// Tides in its graveyard. Player 1's real level-up into Mordred, Burnished Avenger (memory cost 1) is
// NOT offered a floating payment: the seeded memory card is banished for the cost and the Striking
// Tides stays in player 1's graveyard. History: before the fix this fixture was named
// ...-makes-my-graveyard-attack-cards-float and its REGRESSION GUARD assertions pinned the Striking
// Tides being offered and banished.
// Notes carried over from the original definition:
//   - PLAYER 2's champion: Mordred, Flawless Blade
//   - Striking Tides in PLAYER 1's graveyard -> p1 graveyard-0
//   - memory (cost 1 level-up)
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - player 1 levels up into Mordred, Burnished Avenger (memory cost 1): no floating payment is offered, the memory card pays
gaScenario('mordred-flawless-blade-opponent-blade-does-not-float-my-graveyard')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'], player: 2)
    ->seed('myGraveyard', 'Striking Tides')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Mordred, Flawless Blade')
    ->expectProperty('p1Field-0', 'CardID', 'OWCdWq3mXY', 'Player 1\'s level-up into Mordred, Burnished Avenger completed')
    ->expectProperty('p1Banish-0', 'CardID', 'n8wyfG9hbY', 'The seeded memory card paid the cost (it was banished): no floating payment was offered')
    ->expectProperty('p1Graveyard-0', 'CardID', 'qrxQGA1pc6', 'Player 1\'s Striking Tides stays in the graveyard (the opponent\'s Flawless Blade does not float it)')
    ->expectZoneCount('myGraveyard', 1, 1, 'Player 1\'s graveyard still holds the attack card')
    ->expectZoneCount('myBanish', 1, 1, 'Only the memory card was banished')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Flawless Blade: PLAYER 2's own Flawless Blade floats PLAYER 2's graveyard attack cards ---
// Mordred, Flawless Blade (WI2owxIw0z): "Attack cards in your graveyard have floating memory." The
// owner check must work from player 2's seat too (graveyard owner resolved through PlayerID, never
// a perspective-relative field). Player 2's champion is Mordred, Flawless Blade; player 2's graveyard
// holds a Striking Tides and its two seeded memory cards are one short of the memory cost 3 of the
// level-up into Aurelian Regent in player 2's turn-4 materialize phase (its first materialize offer): the floating payment is
// offered, Striking Tides is banished for 1 point and the level-up completes.
// Notes carried over from the original definition:
//   - PLAYER 2's champion: Mordred, Flawless Blade (level 2)
//   - Striking Tides in PLAYER 2's graveyard -> p2 graveyard-0
//   - memory 1/2
//   - memory 2/2 (one short of the cost 3)
//   - end player 1 turn 1
//   - end player 2 turn 2 (player 2's first turn has no materialize offer)
//   - player 1 declines the materialize offer of its second turn
//   - end player 1's turn 3 -> player 2 turn 4 materialize phase
//   - player 2 starts the level-up into Mordred, Aurelian Regent (memory cost 3)
//   - float player 2's Striking Tides for 1 of the cost
gaScenario('mordred-flawless-blade-player-two-blade-floats-own-graveyard')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'], player: 2)
    ->seed('myGraveyard', 'Striking Tides', player: 2)
    ->seed('myMemory', 'Fairy Whispers', player: 2)
    ->seed('myMemory', 'Fairy Whispers', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->answer(2, 'myMaterial-3')->answer(2, 'myGraveyard-0')
    ->tested('Mordred, Flawless Blade')
    ->expectProperty('p2Field-0', 'CardID', 'XPl2UAO9se', 'Player 2\'s level-up into Mordred, Aurelian Regent (memory cost 3, only 2 memory cards) completed')
    ->expectProperty('p2Banish-0', 'CardID', 'qrxQGA1pc6', 'Player 2\'s graveyard attack card was banished as the floating payment')
    ->expectZoneCount('myGraveyard', 0, 2, 'Player 2\'s graveyard is empty')
    ->expectZoneCount('myBanish', 3, 2, '3 banished = the floated attack card + the 2 memory cards')
    ->expectDqEmpty()
    ->register();

// --- Mordred, Flawless Blade: the floating-payment choice rejects a card that has NO floating memory (engine bug, FIXED) ---
// Mordred, Flawless Blade (WI2owxIw0z): only attack cards in the graveyard have floating memory, and
// the floating payment prompt offers just those. The prompt is an MZMULTICHOOSE ("0|1|myGraveyard-1").
// ENGINE BUG (found and FIXED): GameValidateDecisionAnswer() (Custom/GameLogic.php) used to validate only
// MZCHOOSE/MZMAYCHOOSE answers against the candidate pool, so an answer that was never offered was
// passed straight to the PAYFLOATING handler. Same setup as
// mordred-flawless-blade-attack-card-in-graveyard-floats-for-levelup, but the first answer is the
// Dungeon Guide (myGraveyard-0), which was NOT offered and has no floating memory: it is now rejected
// (nothing banished, level-up not completed) and the prompt stays pending, so the follow-up legal
// answer (Striking Tides, myGraveyard-1) completes the level-up.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2)
//   - Dungeon Guide (no floating memory, NOT offered) -> p1 graveyard-0
//   - Striking Tides (attack card, the only offered choice) -> p1 graveyard-1
//   - memory 1/2
//   - memory 2/2 (one short of the cost 3)
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - start the level-up into Mordred, Aurelian Regent (memory cost 3)
//   - the prompt is still pending: the legal floating card (Striking Tides) completes the level-up
gaScenario('mordred-flawless-blade-floating-payment-rejects-non-floating-card')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Striking Tides')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')
    ->rejects(1, 'myGraveyard-0', 'The Dungeon Guide was never offered (and has no floating memory): answering the floating-payment MZMULTICHOOSE with it is rejected')
    ->target('myGraveyard-1')
    ->tested('Mordred, Flawless Blade')
    ->register();

// --- Mirrordepth's Blade: On Banish, look at the top card of your deck; you may put it into your graveyard ---
// Mirrordepth's Blade (efTHWeXscP, WATER WARRIOR REGALIA/WEAPON SWORD, memory cost 0, power 1, durability
// 2): "On Banish: Look at the top card of your deck. You may put it into your graveyard." The weapon is
// materialized for real in the turn-3 materialize phase and used for two weapon attacks: one on
// turn 3 (durability 2 -> 1, nothing triggers) and one on turn 5 (durability 1 -> 0): a weapon with no
// durability counters is destroyed, and a regalia card that would go to the graveyard is banished
// instead, so the On Banish trigger fires: the top card of the deck is offered and answering with it
// puts that card into the graveyard.
// Notes carried over from the original definition:
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Mirrordepth's Blade (memory cost 0)
//   - the champion attacks
//   - with Mirrordepth's Blade as the weapon
//   - targeting player 2's champion (durability 2 -> 1)
//   - end player 1 turn 3
//   - player 2 declines its materialize offer
//   - end player 2 turn 4 -> player 1 turn 5
//   - player 1 declines the materialize offer
//   - the champion attacks again
//   - with Mirrordepth's Blade (its last durability counter)
//   - targeting player 2's champion (durability 1 -> 0: destroyed -> banished)
//   - On Banish: put the top card of the deck into the graveyard
gaScenario('mirrordepths-blade-on-banish-put-top-card-into-graveyard')
    ->deck($mordredDeck)
    ->endTurn()
    ->endTurn(2)->target('myMaterial-4')
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->endTurn()->pass(2)
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->target('myDeck-0')
    ->tested('Mirrordepth\'s Blade')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":1}', 'After the first attack the Blade has lost one durability counter', step: 6)
    ->expectZoneCount('myBanish', 0, 1, 'Nothing is banished yet: no On Banish prompt', step: 6)
    ->expectDqEmpty(step: 6)
    ->expectProperty('p1Banish-0', 'CardID', 'efTHWeXscP', 'With its last durability counter gone the Blade was banished')
    ->expectZoneCount('myField', 1, 1, 'The Blade left the field (only the champion remains)')
    ->expectZoneCount('myGraveyard', 1, 1, 'The revealed top card was put into the graveyard')
    ->expectZoneCount('myDeck', 8, 1, 'The deck lost exactly that card (9 -> 8)')
    ->expectDqEmpty()
    ->register();

// --- Mirrordepth's Blade: putting the revealed top card into the graveyard is optional (declined) ---
// Mirrordepth's Blade (efTHWeXscP): "You MAY put it into your graveyard." Same flow as
// mirrordepths-blade-on-banish-put-top-card-into-graveyard, but the choice is answered PASS: the top
// card stays on top of the deck and the graveyard stays empty.
// Notes carried over from the original definition:
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Mirrordepth's Blade (memory cost 0)
//   - the champion attacks
//   - with Mirrordepth's Blade as the weapon
//   - targeting player 2's champion (durability 2 -> 1)
//   - end player 1 turn 3
//   - player 2 declines its materialize offer
//   - end player 2 turn 4 -> player 1 turn 5
//   - player 1 declines the materialize offer
//   - the champion attacks again
//   - with Mirrordepth's Blade (its last durability counter)
//   - targeting player 2's champion (durability 1 -> 0: destroyed -> banished)
//   - On Banish: decline to put the top card into the graveyard
gaScenario('mirrordepths-blade-on-banish-decline-keeps-top-card')
    ->deck($mordredDeck)
    ->endTurn()
    ->endTurn(2)->target('myMaterial-4')
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->endTurn()->pass(2)
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->pass(1)
    ->tested('Mirrordepth\'s Blade')
    ->expectProperty('p1Banish-0', 'CardID', 'efTHWeXscP', 'The Blade was banished with its last durability counter')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing was put into the graveyard')
    ->expectZoneCount('myDeck', 9, 1, 'The deck is untouched (9 cards)')
    ->expectDqEmpty()
    ->register();

// --- Malevolent Vow: discarding zero cards (declining the first prompt) still recovers 3 and puts the Vow on the lineage (dontSkipOnPass regression) ---
// Malevolent Vow (up6fw61vf1): "Discard up to three cards. Recover 3+X, where X is three times amount of cards discarded this way. Put Malevolent Vow on the bottom of your champion's lineage." Stopping early at any of the three optional discard prompts (declining with PASS) must still finish the spell: recover 3+3N for the N cards already discarded and put the Vow on the bottom of the champion's lineage.
// Regression: the discard prompts' paired CUSTOM decisions ('MalevolentVow1' queued by the generated cardActivatedAbilities closure, 'MalevolentVow2'/'MalevolentVow3' queued by the previous handler in Custom/CardDQHandlers.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and MalevolentVowFinish() never ran. The generated closure is overridden in Custom/GeneratedAbilityOverrides.php (the generated file is gitignored).
// The champion is given a Diana, Cursebreaker (UMBRA) Subcards lineage for UMBRA element access and 10 damage. Here the very first prompt is declined (zero cards discarded): Recover 3 and the Vow goes to the lineage.
// Notes carried over from the original definition:
//   - Diana, Cursebreaker (UMBRA) lineage for UMBRA access; champion has 10 damage
//   - decline discarding any card
gaScenario('malevolent-vow-decline-first-discard-still-recovers-and-lineages')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Diana, Cursebreaker'], damage: 10)
    ->seed('myHand', 'Malevolent Vow')
    ->castSpell('Malevolent Vow', window: 'me')
    ->expectDamage('p1Field-0', 7, 'Zero cards discarded: recover 3+0 = 3 (damage 10 -> 7)')
    ->expectProperty('p1Field-0', 'Subcards', '["o0qtb31x97","up6fw61vf1"]', 'Malevolent Vow is put on the bottom of the champion\'s lineage')
    ->expectZoneCount('myGraveyard', 0, 1, 'Only the 0 discarded card(s) are in the graveyard: the Vow itself left it for the lineage')
    ->expectDqEmpty()
    ->register();

// --- Malevolent Vow: declining the second discard still recovers 3+3N and puts the Vow on the lineage (dontSkipOnPass regression) ---
// Malevolent Vow (up6fw61vf1): "Discard up to three cards. Recover 3+X, where X is three times amount of cards discarded this way. Put Malevolent Vow on the bottom of your champion's lineage." Stopping early at any of the three optional discard prompts (declining with PASS) must still finish the spell: recover 3+3N for the N cards already discarded and put the Vow on the bottom of the champion's lineage.
// Regression: the discard prompts' paired CUSTOM decisions ('MalevolentVow1' queued by the generated cardActivatedAbilities closure, 'MalevolentVow2'/'MalevolentVow3' queued by the previous handler in Custom/CardDQHandlers.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and MalevolentVowFinish() never ran. The generated closure is overridden in Custom/GeneratedAbilityOverrides.php (the generated file is gitignored).
// The champion is given a Diana, Cursebreaker (UMBRA) Subcards lineage for UMBRA element access and 10 damage. Here one card is discarded and the second prompt ('Discard a card (Malevolent Vow 2/3)') is declined: Recover 3+3 = 6.
// Notes carried over from the original definition:
//   - Diana, Cursebreaker (UMBRA) lineage for UMBRA access; champion has 10 damage
//   - discard one card
//   - decline discarding a second card
gaScenario('malevolent-vow-decline-second-discard-still-recovers')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Diana, Cursebreaker'], damage: 10)
    ->seed('myHand', 'Malevolent Vow')
    ->castSpell('Malevolent Vow', pay: 1, window: 'me')
    ->expectDamage('p1Field-0', 4, 'One card discarded, second declined: recover 3+3 = 6 (damage 10 -> 4)')
    ->expectProperty('p1Field-0', 'Subcards', '["o0qtb31x97","up6fw61vf1"]', 'Malevolent Vow is put on the bottom of the champion\'s lineage')
    ->expectZoneCount('myGraveyard', 1, 1, 'Only the 1 discarded card(s) are in the graveyard: the Vow itself left it for the lineage')
    ->expectDqEmpty()
    ->register();

// --- Malevolent Vow: declining the third discard still recovers 3+3N and puts the Vow on the lineage (dontSkipOnPass regression) ---
// Malevolent Vow (up6fw61vf1): "Discard up to three cards. Recover 3+X, where X is three times amount of cards discarded this way. Put Malevolent Vow on the bottom of your champion's lineage." Stopping early at any of the three optional discard prompts (declining with PASS) must still finish the spell: recover 3+3N for the N cards already discarded and put the Vow on the bottom of the champion's lineage.
// Regression: the discard prompts' paired CUSTOM decisions ('MalevolentVow1' queued by the generated cardActivatedAbilities closure, 'MalevolentVow2'/'MalevolentVow3' queued by the previous handler in Custom/CardDQHandlers.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and MalevolentVowFinish() never ran. The generated closure is overridden in Custom/GeneratedAbilityOverrides.php (the generated file is gitignored).
// The champion is given a Diana, Cursebreaker (UMBRA) Subcards lineage for UMBRA element access and 10 damage. Here two cards are discarded and the third prompt ('Discard a card (Malevolent Vow 3/3)') is declined: Recover 3+6 = 9.
// Notes carried over from the original definition:
//   - Diana, Cursebreaker (UMBRA) lineage for UMBRA access; champion has 10 damage
//   - discard the first card
//   - discard the second card
//   - decline discarding a third card
gaScenario('malevolent-vow-decline-third-discard-still-recovers')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Diana, Cursebreaker'], damage: 10)
    ->seed('myHand', 'Malevolent Vow')
    ->castSpell('Malevolent Vow', pay: 2, window: 'me')
    ->expectDamage('p1Field-0', 1, 'Two cards discarded, third declined: recover 3+6 = 9 (damage 10 -> 1)')
    ->expectProperty('p1Field-0', 'Subcards', '["o0qtb31x97","up6fw61vf1"]', 'Malevolent Vow is put on the bottom of the champion\'s lineage')
    ->expectZoneCount('myGraveyard', 2, 1, 'Only the 2 discarded card(s) are in the graveyard: the Vow itself left it for the lineage')
    ->expectDqEmpty()
    ->register();

// Mirrored Confrontation (zHbIiP3knE): "Each player may reveal a champion card with base level 3 from their material deck. Each player that does draws a card into their memory and recovers 2."
// Notes carried over from the original definition:
//   - RANGER class; 3 damage to recover from
//   - Mirrored Confrontation -> myHand-7
gaScenario('mirrored-confrontation-reveal-level-3-champion-draws-into-memory-and-recovers')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Crux Knight
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Damage' => 3, 'Counters' => ['_overrides' => ['classes' => 'RANGER']]])
    ->seed('myHand', 'Mirrored Confrontation')
    ->play('Mirrored Confrontation')->pay(2)->answer(1, 'YES')->answer(2, 'NO')
    ->register();

gaScenario('flawless-blade-with-abilities-gives-attack-cards-floating-memory-to-pay-materialize')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Arondight, Azure Blade
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Emberslash')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')
    ->tested('Mordred, Flawless Blade', 'Arondight, Azure Blade')
    ->expectZoneCount('myField', 2, 1, 'Arondight was materialized: the champion and the weapon')
    ->expectZoneCount('myGraveyard', 0, 1, 'The Emberslash was banished as floating memory')
    ->expectZoneCount('myBanish', 1, 1, 'The banished Emberslash')
    ->expectDqEmpty()
    ->register();

gaScenario('flawless-blade-without-abilities-attack-cards-have-no-floating-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Arondight, Azure Blade
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'WI2owxIw0z', 'Subcards' => ['tafqldAGRF'], 'Counters' => ['_overrides' => ['NO_ABILITIES' => 1]]])
    ->seed('myGraveyard', 'Emberslash')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-0')
    ->rejects(1, 'myGraveyard-0', 'With the Blade\'s abilities removed the Emberslash is not offered as floating memory, so it cannot be chosen')
    ->tested('Mordred, Flawless Blade', 'Arondight, Azure Blade')
    ->expectZoneCount('myField', 1, 1, 'Arondight was not materialized: nothing could pay its memory cost', step: 8)
    ->expectZoneCount('myMaterial', 1, 1, 'Arondight is still in the material deck', step: 8)
    ->expectZoneCount('myGraveyard', 1, 1, 'The Emberslash stayed in the graveyard', step: 8)
    ->register();

// Modulating Cadence (p5p0azskw4): "Look at the top eight cards of your deck. You may reveal a Harmony or Melody card from among them and put it into your hand. Put the rest on the bottom of your deck in any order."
// Declining the reveal (PASS) used to skip ModulatingCadenceReveal, so the eight looked-at cards were never put on the bottom of the deck and stayed in the temp zone.
// Notes carried over from the original definition:
//   - TAMER class (Spirit of Wind supplies WIND)
//   - a Melody card (another Modulating Cadence) among the top eight
//   - Modulating Cadence -> myHand-7
gaScenario('modulating-cadence-declined-reveal-still-puts-the-rest-on-the-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'TAMER')
    ->deckTop(['Modulating Cadence', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide'])
    ->seed('myHand', 'Modulating Cadence')
    ->castSpell('Modulating Cadence', pay: 4, window: 'me')
    ->answer(1, 'Bottom=p5p0azskw4,em6eEh9q8y,px60u5n1do,em6eEh9q8y,px60u5n1do,em6eEh9q8y,px60u5n1do,em6eEh9q8y')
    ->expectZoneCount('myTempZone', 0, 1, 'The eight looked-at cards left the temp zone')
    ->expectZoneCount('myDeck', 21, 1, 'All eight cards are back in the deck (on the bottom)')
    ->expectZoneCount('myHand', 3, 1, 'Nothing was put into hand: eight, minus Modulating Cadence, minus four paid')
    ->expectDqEmpty()
    ->register();

// Magnificent Banquet (TQoTD8eGQH): "Recover 5. Then put CARDNAME into its owner's material deck preserved."
// Notes carried over from the original definition:
//   - TERA lineage, CLERIC class, 8 damage
//   - Magnificent Banquet -> myHand-7
gaScenario('magnificent-banquet-recovers-five-and-returns-to-the-material-deck-preserved')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Damage' => 8, 'Subcards' => ['7x2v4tdop1'], 'Counters' => ['_overrides' => ['classes' => 'CLERIC']]])
    ->seed('myHand', 'Magnificent Banquet')
    ->castSpell('Magnificent Banquet', pay: 2)
    ->register();

// Manxome Armoire (fm894uc4ij): "[Ciel Bonus] Banish Manxome Armoire: Return one of your omens to your hand. If you do, banish a card from your hand and put an omen counter on it."
// Notes carried over from the original definition:
//   - Manxome Armoire -> myField-1
//   - a Fluffy Shopkeep omen -> myBanish-0
gaScenario('manxome-armoire-ciel-bonus-banish-swaps-an-omen-for-a-card-from-hand')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Ciel, Loyal Valet')
    ->seed('myField', 'Manxome Armoire')
    ->seed('myBanish', 'Fluffy Shopkeep', properties: ['Counters' => ['omen' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myBanish-0')->pay(1)
    ->tested('Manxome Armoire')
    ->register();

// Notes carried over from the original definition:
//   - Manxome Armoire -> myField-1 (the champion is not named Ciel)
gaScenario('manxome-armoire-without-the-ciel-bonus-cannot-activate')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Manxome Armoire')
    ->seed('myBanish', 'Fluffy Shopkeep', properties: ['Counters' => ['omen' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Manxome Armoire')
    ->expectZoneCount('myField', 2, 1, 'The Armoire stays on the field: the Ciel Bonus activation is refused')
    ->expectZoneCount('myBanish', 1, 1, 'Only the omen is in the banishment; nothing was paid')
    ->expectDqEmpty()
    ->register();

// Majestic Spirit's Crest (Tx6iJQNSA6): "[Class Bonus] Banish Majestic Spirit's Crest: Your champion gains 'On Attack: Draw a card' until end of turn."
// Notes carried over from the original definition:
//   - Majestic Spirit's Crest -> myField-1
gaScenario('majestic-spirits-crest-class-bonus-banish-grants-the-champion-an-on-attack-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'WARRIOR')
    ->seed('myField', 'Majestic Spirit\'s Crest')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Majestic Spirit\'s Crest')
    ->register();

// Notes carried over from the original definition:
//   - Majestic Spirit's Crest -> myField-1 (the champion has no class)
gaScenario('majestic-spirits-crest-without-the-class-bonus-cannot-activate')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Majestic Spirit\'s Crest')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Majestic Spirit\'s Crest')
    ->expectZoneCount('myField', 2, 1, 'The Crest stays on the field: the activation is refused without the Warrior class bonus')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->expectDqEmpty()
    ->register();

// Melodious Flute (WAFNy2lY5t): "[Class Bonus] Banish Melodious Flute: The next Harmony action card you activate this turn is a Melody in addition to its other types."
// Notes carried over from the original definition:
//   - Melodious Flute -> myField-1
gaScenario('melodious-flute-class-bonus-banish-marks-the-next-harmony-action-as-a-melody')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'TAMER')
    ->seed('myField', 'Melodious Flute')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Melodious Flute')
    ->register();

// Notes carried over from the original definition:
//   - Melodious Flute -> myField-1 (the champion has no class)
gaScenario('melodious-flute-without-the-class-bonus-cannot-activate')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Melodious Flute')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Melodious Flute')
    ->expectZoneCount('myField', 2, 1, 'The Flute stays on the field: the activation is refused without the Tamer class bonus')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->expectDqEmpty()
    ->register();

// Negative twin of 'magus-disciple-class-bonus-on-death-draw': the champion's class does not match, so the [Class Bonus] must NOT apply.
// Notes carried over from the original definition:
//   - Magus Disciple
//   - sacrifice Magus Disciple as the mandatory cost
//   - pay Undeniable Truth's 1-reserve cost
gaScenario('magus-disciple-without-the-class-bonus-on-death-does-not-draw')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Magus Disciple')
    ->seed('myHand', 'Undeniable Truth')
    ->play('Undeniable Truth')->target('myField-1')->pay(1)
    ->tested('Magus Disciple')
    ->expectZoneCount('myHand', 7, 1, 'No card was drawn: the On Death needs a Cleric/Mage champion')
    ->expectZoneCount('myDeck', 4, 1, 'Deck untouched by the On Death')
    ->expectDqEmpty()
    ->register();

// Notes carried over from the original definition:
//   - Myopic Lens -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('myopic-lens-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'MAGE'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Myopic Lens')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-0')->pass(1)
    ->tested('Myopic Lens')
    ->register();

gaScenario('meteoric-volley-copies-each-aethercharge-in-the-attackers-intent')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Arisanna, Astral Zenith'], classes: 'RANGER', player: 2)
    ->seed('myField', 'Salamander\'s Breath', player: 2, properties: ['Subcards' => ['wd7nuab7f3']])
    ->seed('myHand', 'Meteoric Volley', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-0')->answer(2, 'myHand-7')
    ->pay(3, 2)
    ->tested('Meteoric Volley')
    ->register();

gaScenario('meteoric-volley-cannot-be-activated-when-your-champion-is-not-attacking')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Arisanna, Astral Zenith'], classes: 'RANGER')
    ->seed('myHand', 'Meteoric Volley')
    ->play('Meteoric Volley')
    ->expectZoneCount('myHand', 8, 1, 'Refused: no attack is in progress')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->expectDqEmpty()
    ->register();

// Molten Arrow (mvfcd0ukk6): "Banish three other fire element cards from your graveyard: Load the card from your graveyard into target unloaded Bow weapon you control." The arrow is the LAST graveyard card, so
// its index shifts as the three fire cards below it are banished.
// Notes carried over from the original definition:
//   - Intricate Longbow (unloaded Bow) -> myField-1
//   - three Red Slimes (FIRE)
//   - Molten Arrow -> myGraveyard-3
gaScenario('molten-arrow-graveyard-ability-banishes-three-fire-cards-and-loads-into-the-bow')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Intricate Longbow')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Molten Arrow')
    ->action(1, 10002, 'myGraveyard-3!FSM!')->target('myGraveyard-0')->target('myGraveyard-0')
    ->target('myGraveyard-0')
    ->tested('Molten Arrow', 'Intricate Longbow', 'Red Slime')
    ->register();

gaScenario('mercenarys-blade-activates-from-the-material-deck-by-removing-a-preparation-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Subcards' => ['he6kd7hocc'], 'Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'preparation' => 2]])
    ->seed('myMaterial', 'Mercenary\'s Blade')
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Mercenary\'s Blade')
    ->register();

gaScenario('mercenarys-blade-is-not-activated-from-the-material-deck-without-a-preparation-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Subcards' => ['he6kd7hocc'], 'Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'preparation' => 0]])
    ->seed('myMaterial', 'Mercenary\'s Blade')
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Mercenary\'s Blade')
    ->register();

gaScenario('mercenarys-blade-is-not-activated-from-the-material-deck-without-the-class-bonus')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Counters' => ['preparation' => 2]])
    ->seed('myMaterial', 'Mercenary\'s Blade')
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Mercenary\'s Blade')
    ->register();

// Notes carried over from the original definition:
//   - Dungeon Guide (power 1) -> p1 field-1
//   - Steady Verse (a Melody) -> p1 myHand-7
//   - Mist Resonance -> p1 myHand-8
gaScenario('mist-resonance-harmonize-allies-assign-damage-with-life')
    ->deck($gaMistDeck)
    ->champion(lineage: ['Nico, Rapture\'s Embrace'], classes: 'TAMER')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Steady Verse')
    ->seed('myHand', 'Mist Resonance')
    ->endTurn()->pass(1)->pass(1)
    ->endTurn(2)->pass(1)->pass(1)->pass(1)
    ->castSpell('Steady Verse', pay: 2, window: 'me')
    ->playSlot(5)->pay(5)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')
    ->tested('Mist Resonance', 'Steady Verse')
    ->register();

// Notes carried over from the original definition:
//   - Dungeon Guide (power 1) -> p1 field-1
//   - Mist Resonance -> p1 myHand-7
gaScenario('mist-resonance-without-a-melody-this-turn-allies-still-assign-damage-with-power')
    ->deck($gaMistDeck)
    ->champion(lineage: ['Nico, Rapture\'s Embrace'], classes: 'TAMER')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Mist Resonance')
    ->endTurn()->pass(1)->pass(1)
    ->endTurn(2)->pass(1)->pass(1)->pass(1)
    ->castSpell('Mist Resonance', pay: 5, window: 'me')
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - Nico (WATER lineage) but NOT a Tamer champion
//   - Dungeon Guide (power 1) -> p1 field-1
//   - Steady Verse (a Melody) -> p1 myHand-7
//   - Mist Resonance -> p1 myHand-8
gaScenario('mist-resonance-harmonize-needs-the-class-bonus')
    ->deck($gaMistDeck)
    ->champion(lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Steady Verse')
    ->seed('myHand', 'Mist Resonance')
    ->endTurn()->pass(1)->pass(1)
    ->endTurn(2)->pass(1)->pass(1)->pass(1)
    ->castSpell('Steady Verse', pay: 2, window: 'me')
    ->playSlot(5)->pay(5)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')
    ->tested('Mist Resonance', 'Steady Verse')
    ->register();

gaScenario('manabolt-convergence-lets-the-targeted-aethercharge-be-activated-from-the-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'RANGER')
    ->seed('myGraveyard', 'Charge the Soul')
    ->seed('myHand', 'Manabolt Convergence')
    ->castSpell('Manabolt Convergence', pay: 2, target: 'myGraveyard-0')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(1)->target('theirField-0')
    ->action(1, 10002, 'myGraveyard-1!FSM!')
    ->tested('Manabolt Convergence', 'Charge the Soul')
    ->register();
