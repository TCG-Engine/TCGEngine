<?php
/**
 * Fixtures for cards starting with 'G', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Guerrilla Advantage: put two preparation counters on your champion ---
// Guerrilla Advantage's element is NORM, so no lineage patch is needed. Only the base
// always-available effect is covered; the discount condition (an opponent controlling 3+
// units) isn't reached, so the full 4-reserve cost is paid.
gaScenario('guerrilla-advantage-prep-counters')
    ->standardDeck('Guerrilla Advantage')
    ->seed('myHand', 'Guerrilla Advantage')
    ->castSpell('Guerrilla Advantage', pay: 4)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'Guerrilla Advantage puts 2 preparation counters on the champion')
    ->expectZoneCount('myMemory', 4, 1, 'Without the discount condition, the full 4-reserve cost is paid')
    ->expectDqEmpty()
    ->register();

// --- Gencode Womb: Dante Bonus (3), banish self: summon a token (no Elysian controlled) ---
// Gencode Womb's element is NORM, so no lineage patch is needed, and its memory cost (0)
// needs no floating-payment setup. Its ability requires IsDanteBonusActive() (a champion-name
// check, not a class/lineage check), so Dante, Hemomancer (reused from
// mend-flesh-damage25-recover/exia-sight-draw) is seeded directly onto the field. The
// ActivatedAbilityCost() switch (GameLogic.php) auto-queues 3 reserve payments and banishes
// Gencode Womb itself as the ability's own cost. We control no Elysian object, so the "else"
// branch (summon an Elysian Test Subject token) resolves rather than the draw-into-memory
// branch.
// Notes carried over from the original definition:
//   - Dante, Hemomancer (CHAMPION) - Dante Bonus source
//   - Gencode Womb, seeded straight onto the field (this fixture is about its activated ability, not materialize)
gaScenario('gencode-womb-banish-summon')
    ->standardDeck('Gencode Womb')
    ->seed('myField', 'Dante, Hemomancer')
    ->seed('myField', 'Gencode Womb')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Gencode Womb')
    ->register();

// --- Gentle Respite: if target opponent's influence is greater than yours, draw into memory ---
// Notes carried over from the original definition:
//   - extra opponent hand fuel 1/2, pushes their influence above ours
//   - extra opponent hand fuel 2/2
gaScenario('gentle-respite-draw')
    ->standardDeck('Gentle Respite')
    ->seed('theirHand', 'Dungeon Guide')
    ->seed('theirHand', 'Dungeon Guide')
    ->seed('myHand', 'Gentle Respite')
    ->castSpell('Gentle Respite', pay: 3)
    ->register();

// --- Giant Tortoise: vanilla stat check ---
// Giant Tortoise is WATER and vanilla (no printed ability). The starting champion's Subcards
// are patched with a real WATER champion (Nico, Rapture's Embrace) to unlock element access.
// Notes carried over from the original definition:
//   - Nico, Rapture's Embrace -- unlocks WATER
gaScenario('giant-tortoise-vanilla-stats')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myHand', 'Giant Tortoise')
    ->castSpell('Giant Tortoise', pay: 4)
    ->register();

// --- Gray Wolf: Pride 2 (won't attack unless champion is level 2+) ---
// Gray Wolf is NORM, vanilla besides Pride 2. Pride is enforced only in combat
// (CombatLogic.php ~619/2836, GameLogic.php ~16695: PlayerLevel($controller) < PrideAmount($obj)),
// there is no side-effect-free "can attack" predicate -- BeginCombatPhase() is simultaneously the
// check and the action. Seeded directly onto the field (already awake, so no materialize/summoning
// sickness concern). At the default champion level (0), attempting to declare it as an attacker is
// rejected outright (expectFailure) since 0 < 2.
// Notes carried over from the original definition:
//   - Gray Wolf
gaScenario('gray-wolf-pride-2-attack-rejected')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Gray Wolf')
    ->action(1, 10002, 'myField-1!FSM!')
    ->tested('Gray Wolf')
    ->expectProperty('p1Field-1', 'Status', '2', 'Gray Wolf stays awake (never attacks) -- Pride 2 blocks it while the champion is below level 2')
    ->expectDqEmpty()
    ->register();

// --- Gaia's Songbird: [Class Bonus] On Enter: reveal until a Beast ally is found, put it in hand ---
// Gaia's Songbird is TERA -- the starting champion's Subcards are patched with a real TERA
// champion (Silvie, Loved by All) to unlock element access. Its Class Bonus is checked via
// IsClassBonusActive($player) with NO $classes argument, which (per this session's established
// finding) degrades to "does any champion exist" -- always true -- so no class-match patch is
// needed. The deck's top card (myDeck-0) is patched directly to Gray Wolf (a BEAST ally) so the
// reveal finds it on the very first card, confirmed by Gray Wolf ending up in hand rather than
// in the deck.
// Notes carried over from the original definition:
//   - TAMER champion: the [Class Bonus] is active (a bare class-bonus gate used to be always true)
//   - Silvie, Loved by All -- unlocks TERA
//   - Gray Wolf on top of deck
gaScenario('gaias-songbird-class-bonus-enter-reveal-beast')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(classes: 'TAMER')
    ->champion(lineage: ['Silvie, Loved by All'])
    ->patch('myDeck-0', ['CardID' => 'hJ2xh9lNMR'])
    ->seed('myHand', 'Gaia\'s Songbird')
    ->castSpell('Gaia\'s Songbird', pay: 1)
    ->register();

// --- Greater Boon of Shou: As gained, draw a card (Zw0T2GmowK) ---
gaScenario('greater-boon-of-shou-draw-on-play')
    ->deck($fillerDeck)
    ->seed('myHand', 'Greater Boon of Shou')
    ->play('Greater Boon of Shou')->pay(2)->passBoth()->passBoth()
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'Zw0T2GmowK')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 6, 1, 'Playing Greater Boon of Shou fires its on-play trigger (playCardAbilities["Zw0T2GmowK:0"], only reachable via QueuePlayCardTriggeredAbility() from OnCardActivated()): after paying its 2-reserve cost, hand would sit at 5, but the on-play Draw($player,1) brings it back to 6')
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Gem of Searing Flame: North->West Shifting Currents transition deals 2 damage to target champion ---
// Gem of Searing Flame's North->West transition callback ($shiftingCurrentsTransitions,
// GameLogic.php ~20968) queues a YES/NO "deal 2 to your champion? (No=opponent)" choice.
// Answering NO deals it to the opponent's champion. Same Fel Eidolon Subcards patch as
// taiji-crystal-strategems-south-east-rest-damage/hydroguard-retainer-north-west-draw: it
// unlocks TERA (for playing Tera Sight) and grants the champion's own Inherited "may change SC
// to an adjacent direction on Spell activation" ability, which fires the transition.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock + Fel Eidolon Inherited trigger
//   - Shifting Currents facing North
//   - Gem of Searing Flame
//   - Tera Sight, to trigger the Inherited SC choice on activation
//   - choose adjacent direction WEST
//   - deal the 2 damage to the opponent's champion
gaScenario('gem-of-searing-flame-north-west-damage')
    ->deck(GA_KONGMING_BASE_DECK)
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Gem of Searing Flame')
    ->seed('myHand', 'Tera Sight')
    ->play('Tera Sight')->answer(1, 'WEST')->answer(1, 'NO')
    ->tested('Gem of Searing Flame')
    ->expectDamage('theirField-0', 2, 'North->West transition deals 2 damage to the opponent\'s champion (answering NO)')
    ->expectDamage('myField-0', 0, 'Player 1\'s own champion takes no damage since NO was chosen')
    ->expectProperty('myMastery-0', 'Direction', 'WEST', 'Shifting Currents actually changed to West')
    ->register();

// --- Glimmering Refusal: Negate target card activation unless controller pays (X phantasias) ---
// cardActivatedAbilities["fp773yotth:0"] counts $player's own PHANTASIA cards on myField
// and calls QueueNegateActivation($player, [], "banish", count($phantasias)) -- no
// excludeController filter, but player 2 (Glimmering Refusal's controller) still targets
// player 1's activation for a real cross-player test. Two PHANTASIA cards (Scorching
// Imperilment, aj7pz79wsp) are seeded directly onto player 2's own field so X = 2.
// The bait is Mastermind Scheme (9lbewemius, ACTION, NORM, reserve 6 -- no complex
// targeting of its own) instead of the usual Escharotomy specifically so player 1's hand
// is down to 1 card (7 starting + 1 seeded - 1 played - 6 reserve) by the time Glimmering
// Refusal's negate resolves -- CountAvailableReservePayments(player 1) = 1 < 2, so
// NegateActivationResolve (OpportunityLogic.php ~388) takes its SYNCHRONOUS
// "if(CountAvailableReservePayments($controller) < $payAmount)" branch and calls
// NegateCardActivation(..., "banish") immediately, in the same action as the MZCHOOSE
// target selection -- no deferred YES/NO round-trip. (A deferred "can afford, then
// declines/pays" branch exists too, but it loses a real engine race against
// PostResolutionCheck's immediate stack continuation -- see the note above
// Glimmering Refusal's fixture for the same issue confirmed on Acquiescing Rejection; the
// insufficient-funds branch exercised here is unaffected because it calls
// NegateCardActivation synchronously, before that race can occur.)
// Notes carried over from the original definition:
//   - Mastermind Scheme, the target activation (NORM, reserve 6, no lineage patch needed)
//   - Spirit of Water - unlocks WATER for Glimmering Refusal
//   - Scorching Imperilment (PHANTASIA) #1 -- X=2 source
//   - Scorching Imperilment (PHANTASIA) #2 -- X=2 source
//   - Glimmering Refusal, the negator
//   - play Mastermind Scheme
//   - pay reserve 1/6
//   - pay reserve 2/6
//   - pay reserve 3/6
//   - pay reserve 4/6
//   - pay reserve 5/6
//   - pay reserve 6/6 -- only 1 hand card left afterward
//   - player 1 passes their own opportunity
//   - player 2 takes a fast action instead of passing: Glimmering Refusal
//   - pay reserve 1/2 (no Class Bonus cost reduction on this card)
//   - pay reserve 2/2
//   - choose Mastermind Scheme's activation to negate -- player 1 can't afford to pay 2, synchronous negate+banish
gaScenario('glimmering-refusal-negate-banish')
    ->deck($diaoChanDeck)
    ->seed('myHand', 'Mastermind Scheme')
    ->champion(lineage: ['Spirit of Water'], player: 2)
    ->seed('myField', 'Scorching Imperilment', player: 2)
    ->seed('myField', 'Scorching Imperilment', player: 2)
    ->seed('myHand', 'Glimmering Refusal', player: 2)
    ->play('Mastermind Scheme')->pay(6)->pass(1)->answer(2, 'myHand-7')->pay(2, 2)->answer(2, 'EffectStack-0')
    ->tested('Glimmering Refusal')
    ->expectDqEmpty()
    ->expectProperty('p1Hand-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Banish-0', 'CardID', '9lbewemius', 'Mastermind Scheme\'s activation is negated straight to Banish (not Graveyard) because its controller (player 1, with only 1 payable card left) could not afford Glimmering Refusal\'s pay-2 (X=2 phantasias controlled by the caster) demand')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p2Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p2Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Graveyard-0', 'CardID', 'fp773yotth')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 1, 1, 'Player 1 has only 1 payable card left (below Glimmering Refusal\'s X=2 demand), confirming the negate took the synchronous insufficient-funds branch rather than a pay-to-prevent choice')
    ->expectZoneCount('myMemory', 6, 1)
    ->expectZoneCount('theirHand', 5, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 2, 1)
    ->register();

// --- Glimmer Essence Amulet: whenever a phantasia you control is destroyed on an opponent's turn, banish -> draw ---
// Glimmer Essence Amulet's trigger (GameLogic.php ~7816-7832, inside the shared DoAllyDestroyed()
// destroy-event dispatcher) fires when PropertyContains(CardType($destroyedObj->CardID),
// "PHANTASIA") and GetTurnPlayer() != $controller (i.e. destroyed while it's not the
// controller's own turn). A real combat kill is used to reach this rather than a removal
// spell: Maiden of Shimmering Air (3n4l6aoj4t, PHANTASIA+ALLY, printed LIFE 2) is seeded onto
// player 1's own field with its LIFE overridden to 1 (potion_animate_life, same technique as
// the Reaping Legacy/Shred to Ribbons precedents) alongside Glimmer Essence Amulet. Player 1
// passes turn 1 (Rule 1.h forbids the true first player from attacking turn 1); on player 2's
// own turn 1, their Dungeon Guide ALLY (printed POWER 1, seeded awake) declares a real attack
// against player 1's Maiden, dealing exactly 1 damage -- lethal against the overridden 1 LIFE
// -- which destroys it via DoAllyDestroyed() DURING PLAYER 2's turn, satisfying "while it's not
// your turn" from player 1's perspective. That fires customDQHandlers["GlimmerEssenceAmuletChoice"]
// (CardDQHandlers.php ~8255): answering YES banishes Glimmer Essence Amulet and draws a card.
// Notes carried over from the original definition:
//   - Maiden of Shimmering Air (PHANTASIA,ALLY), LIFE overridden to 1
//   - Glimmer Essence Amulet
//   - Dungeon Guide ALLY, seeded awake (BridgeAddToZone defaults Status=2) -- attacker
//   - ends turn 1 (first-player attack lock)
//   - Dungeon Guide declares a real attack, during player 2's own turn
//   - target player 1's Maiden of Shimmering Air
//   - decline Retaliate?
//   - banish Glimmer Essence Amulet to draw a card
gaScenario('glimmer-essence-amulet-opponent-turn-phantasia-destroyed-draw')
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
    ->seed('myField', 'Maiden of Shimmering Air', properties: ['Counters' => ['potion_animate_life' => 1]])
    ->seed('myField', 'Glimmer Essence Amulet')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->answer(1, '-')->answer(1, 'YES')
    ->tested('Glimmer Essence Amulet')
    ->expectProperty('p1Banish-0', 'CardID', 'dy4urpjbjm', 'Glimmer Essence Amulet was banished (moved off the field) when its controller chose YES')
    ->expectZoneCount('myHand', 8, 1, 'Player 1\'s hand grew from 7 to 8 -- the extra card drawn by Glimmer Essence Amulet\'s trigger after their own phantasia was destroyed on player 2\'s turn')
    ->register();

// Guo Jia, Chosen Disciple: On Enter, if you don't control a Fatestone regalia, reveal your
// material deck and put a Fatestone regalia with the lowest memory cost onto the field.
// Notes carried over from the original definition:
//   - Filler card in memory to pay Guo Jia, Chosen Disciple's 1-memory level-up cost.
//   - Real champion level-up (0 -> 1): both players end turn 1 to reach P1's material-phase
//   - MZMAYCHOOSE, then choose the only legal target (Guo Jia, Chosen Disciple).
//   - Decline the resulting fast-action opportunity window to reach a clean resolved state.
gaScenario('guo-jia-chosen-disciple-enter-material-fatestone')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Guo Jia, Chosen Disciple
1 Fabled Azurite Fatestone
# Main
10 Dungeon Guide
DECK
    )
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->pass(1)
    ->tested('Guo Jia, Chosen Disciple')
    ->expectProperty('myField-1', 'CardID', '6ce5rzrjd9', 'Fabled Azurite Fatestone (the only Fatestone regalia in the material deck) is put onto the field', step: 3)
    ->register();

// Guo Jia, Blessed Scion: On Enter, you may put two quest counters on CARDNAME; if you don't,
// draw a card.
// Notes carried over from the original definition:
//   - Champion is patched directly to Guo Jia, Chosen Disciple (level 1) -- representing
//   - already having leveled up once, WITHOUT re-testing that card's own On Enter here -- so
//   - a single real level-up (1 -> 2) reaches Guo Jia, Blessed Scion and fires ITS genuine
//   - On Enter. Two filler cards are seeded into memory to pay its 2-memory level-up cost
//   - (memory returns to hand at turn end, so it must be paid on the same turn it's used).
//   - "Put 2 quest counters on Guo Jia?" -- answer YES.
gaScenario('guo-jia-blessed-scion-enter-quest-or-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Guo Jia, Blessed Scion
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'YES')
    ->tested('Guo Jia, Blessed Scion')
    ->expectProperty('myField-0', 'Counters', '{"quest":2}', 'Choosing to put quest counters gives Guo Jia, Blessed Scion two quest counters')
    ->register();

// Guo Jia, Heaven's Favored: On Enter, you may put three quest counters on CARDNAME; if you
// don't, recover 3.
// Notes carried over from the original definition:
//   - Champion patched directly to Guo Jia, Blessed Scion (level 2) with 5 pre-existing
//   - damage -- representing already having leveled up twice, WITHOUT re-testing that card's
//   - own On Enter -- so a single real level-up (2 -> 3) reaches Guo Jia, Heaven's Favored
//   - and fires ITS genuine On Enter, and the pre-existing damage makes "recover 3" observable.
//   - "Put 3 quest counters on Guo Jia?" -- answer NO to take the "recover 3" branch instead.
gaScenario('guo-jia-heavens-favored-enter-recover-3')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Guo Jia, Heaven's Favored
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Blessed Scion', damage: 5)
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->answer(1, 'NO')
    ->tested('Guo Jia, Heaven\'s Favored')
    ->expectDamage('myField-0', 2, 'Declining quest counters recovers 3 damage from the champion (5 -> 2)')
    ->register();

// --- Green Slime: On Enter: put two buff counters on Green Slime ---
// Green Slime's On Enter ability (enterAbilities['zgcxyky280:0'], GeneratedMacroCode.php) is
// a plain AddCounters($player, $mzID, 'buff', 2) -- Green Slime is played for real from hand
// via FSM (paying its 3 reserve cost from existing filler cards in hand) so the Enter trigger
// genuinely fires, per the project's silent-fake-pass rule. Green Slime's element is WIND,
// not native to the "Spirit of Fire" starting champion, so (same technique as
// deployment-beacon-summon-drone) the champion's Subcards are patched with a real WIND
// champion (Spirit of Wind) purely to unlock element access. Only the base Enter clause is
// covered; the separate [Class Bonus] On Leave transfer clause is covered by its own fixture,
// green-slime-class-bonus-on-leave-transfer-buff.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
gaScenario('green-slime-on-enter-two-buff-counters')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myHand', 'Green Slime')
    ->castSpell('Green Slime', pay: 3)
    ->register();

// --- Green Slime: [Class Bonus] On Leave: transfer its buff counters to target ally ---
// UPDATE -- FIXED: the bug described below was found, and its diagnosis here is OUTDATED. The real
// cause was a stale target mzID (Green Slime's closure offers Red Slime as 'myField-2' while Green
// Slime still occupies myField-1; the slot is spliced out before the MZCHOOSE is validated). It is
// fixed engine-side by GameOnZoneElementSpliced() (GrandArchiveSim/Custom/GameLogic.php, called from
// Core CleanupRemovedCards). The fixture now asserts the CORRECT behavior: the On Leave prompt is
// presented and the final action picks Red Slime (now myField-1), receiving all 3 buff counters.
// See meta.json for the full write-up; the historical text below is kept for context only.
// ENGINE BUG (found, not fixed): this fixture documents Green Slime's Class Bonus On Leave
// ability (leaveFieldAbilities['zgcxyky280:0'], GeneratedMacroCode.php) CURRENTLY silently
// failing to transfer its buff counters, even with Class Bonus active and buff counters
// present. Root cause (traced live via a standalone debug harness replaying this exact
// sequence): QueueLeaveFieldTriggeredAbility() (Custom/GameLogic.php ~7797) captures the
// departing object's mzID into the queued trigger's OWN context
// (NormalizeMzIDForController($mzID, $controller)), deferring actual resolution to later
// (QueueTriggeredAbility -> EffectStack -> OpportunityLogic.php's "LEAVE_FIELD" branch ~1640,
// which calls FireLeaveFieldTriggeredAbility($cardOwner, $cardID)). But
// FireLeaveFieldTriggeredAbility (Custom/GameLogic.php ~7808) -- unlike its sibling
// FireEnterTriggeredAbility (~7559), which explicitly does
// `DecisionQueueController::StoreVariable("mzID", $sourceMzID)` before invoking the ability --
// never restores that captured mzID into the ambient "mzID" DecisionQueueController variable
// before calling `$leaveFieldAbilities[$cardID.':0']($controller)`. The closure then reads
// whatever STALE value the ambient "mzID" variable happens to hold at that later point (left
// over from OnLeaveField()'s own cleanup, which explicitly restores the PREVIOUS ambient mzID
// right after queuing -- see its last few lines), not Green Slime's own mzID, so
// `GetZoneObject($mzID)` resolves to the wrong object (or null) and the closure's
// `GetCounterCount($obj, "buff") <= 0` early-return silently fires -- this is NOT special to
// Green Slime; every $leaveFieldAbilities closure that reads the ambient "mzID" to find
// itself is equally affected. Confirmed live with a standalone debug script replaying this
// sequence step by step with FIXTURE_DEBUG-style zone dumps: IsClassBonusActive(1,["TAMER"])
// reads true and Green Slime's own Counters read {"buff":3} immediately before the destroy
// resolves, yet after destruction (and after both players explicitly pass, ruling out a
// merely-deferred-but-still-pending decision) Red Slime's Counters stay empty and no
// MZCHOOSE/MZMAYCHOOSE transfer-target decision is ever queued -- the ability silently never
// fires. This fixture pins the CURRENT (buggy) behavior as a regression baseline so a future
// fix to FireLeaveFieldTriggeredAbility's missing mzID-restore is easy to spot (the
// assertions below will need updating once that's fixed). Green Slime's OWN semantic
// coverage requirement is already satisfied by the separate, unaffected Enter-trigger
// fixture green-slime-on-enter-two-buff-counters; this fixture exists purely to document the
// bug for later triage, not to provide Green Slime's primary coverage.
//
// The champion's CardID is patched directly to Silvie, With the Pack (TAMER, itself base
// level 2) for the Class Bonus precondition; Green Slime is seeded directly onto the field
// with 3 buff counters already set (setProperties) -- its own Enter clause is covered
// separately, so seeding it here for this DIFFERENT (Leave) trigger doesn't violate the
// silent-fake-pass rule. The actual leave event is real: Excoriate (ls6g7xgwve, FIRE,
// 'Destroy target ally with reserve cost 4 or less') is played for real from hand and targets
// Green Slime, genuinely destroying it and dispatching leaveFieldAbilities. Red Slime
// (mttsvbgl6f) is seeded onto the field as the only other ally, so it would be the sole legal
// transfer target if the ability fired. Swapping the champion's CardID to Silvie, With the
// Pack drops the FIRE lineage "Spirit of Fire" provided (confirmed live: without a fix,
// CanPlayerUseCardElement rejects Excoriate with flash message "Element not enabled: FIRE"),
// so Spirit of Fire (LMyKyVC2O9) is ALSO patched into the champion's Subcards purely to keep
// FIRE enabled -- same "CardID for class, Subcards for element" layering already used for
// freezing-hail-blue-slime-class-bonus-buff (there for WATER via Nico). Silvie, With the
// Pack's own base level 2 satisfies Excoriate's "[Level 2+] costs 1 less" discount, so only 3
// reserve-payment clicks (not the printed 4) follow the FSM play.
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER, Lv2) + Spirit of Fire (FIRE unlock)
//   - Green Slime, pre-set to 3 buff counters -> myField-1
//   - Red Slime, the only other ally -> myField-2
//   - target Green Slime for destruction
//   - On Leave: choose Red Slime -- now myField-1, since Green Slime's slot was spliced out -- as the transfer target
gaScenario('green-slime-class-bonus-on-leave-transfer-buff')
    ->deckTemplate('silvie-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Green Slime', properties: ['Counters' => ['buff' => 3]])
    ->seed('myField', 'Red Slime')
    ->seed('myHand', 'Excoriate')
    ->play('Excoriate')->pay(3)->target('myField-1')->target('myField-1')
    ->tested('Green Slime', 'Excoriate')
    ->register();

// --- Gather Slimes: Reveal top 5, recover X Slime allies revealed, put one into hand ---
// Gather Slimes' ability (cardActivatedAbilities['1dfhbt3yna:0'] ->
// customDQHandlers['1dfhbt3yna:0:CardActivated-1'], GeneratedMacroCode.php) moves the top 5
// deck cards to myTempZone, counts how many are Slime allies, RecoverChampion()s that many,
// offers an MZCHOOSE among them if more than one, moves the chosen one to hand, then queues
// an MZREARRANGE to put the rest on the bottom. The entire Main deck here is Slime allies
// (Red Slime, Green Slime, Baby Blue Slime, Baby Gray Slime) so all 5 revealed cards are
// GUARANTEED Slime-ally candidates regardless of the deterministic shuffle's exact order --
// this avoids needing to predict which specific card lands where. Gather Slimes' own element
// is WATER, not native to the 'Spirit of Fire' starting champion, so the champion's Subcards
// are patched with a real WATER champion (Nico, Rapture's Embrace) purely to unlock element
// access. The champion is pre-damaged by 5 so Recover 5 is observable (distinguishing
// "recovered" from "already at full life"). Gather Slimes is played for real from hand via
// FSM so its effect genuinely fires; the MZCHOOSE (pick a card for hand) and MZREARRANGE
// (bottom-deck order) responses were determined live via FIXTURE_DEBUG and are echoed back
// verbatim (same technique as cosmic-astroscope-rest-glimpse's Top=/Bottom= response).
// Notes carried over from the original definition:
//   - WATER lineage/element unlock + pre-damage so Recover is observable
//   - choose the first revealed card for hand
//   - MZREARRANGE response: submit the same "Bottom=..." param verbatim to keep original
//   - order (same no-op default used elsewhere, e.g. cosmic-astroscope-rest-glimpse) --
//   - confirmed via FIXTURE_DEBUG against this exact deck/seed, since the revealed card IDs
//   - depend on the deterministic shuffle.
gaScenario('gather-slimes-reveal-five-recover-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
4 Red Slime
4 Green Slime
4 Baby Blue Slime
4 Baby Gray Slime
DECK
    )
    ->champion(lineage: ['Spirit of Water'], damage: 5)
    ->seed('myHand', 'Gather Slimes')
    ->castSpell('Gather Slimes', pay: 2, target: 'myTempZone-0')
    ->answer(1, 'Bottom=0hsncz1fz2,zgcxyky280,zgcxyky280,9ggfiy38t2')
    ->register();

// --- Golden Bishop: "Remove a charge counter from Golden Bishop: Prevent the next 2 damage ..." has no [REST], so Golden Bishop stays awake ---
// Golden Bishop (s4oelWMRJE, ALLY, Chessman): "Remove a charge counter from CARDNAME: Prevent the
// next 2 damage that would be dealt to target Chessman unit you control this turn." The printed
// cost is only the counter. Same implicit-REST bug as Tristan, Shadowdancer's Redirect (FIXED:
// DoActivatedAbility() in Custom/GameLogic.php set Status = 1 for every static ally ability whose
// card id was not in $skipAutoRest; s4oelWMRJE is now in that list). Golden Bishop is seeded awake
// on the field with one charge counter; the ability is activated for real from the main phase, the
// only legal Chessman target (Golden Bishop itself) is chosen, the counter is spent, the
// GOLDEN_BISHOP_PREVENT_2 turn effect lands on the target and the Bishop stays awake (Status 2).
// Notes carried over from the original definition:
//   - Golden Bishop with one charge counter (the ability cost) -> p1 field-1
//   - activate the charge-counter ability
//   - target: Golden Bishop (the only Chessman unit)
gaScenario('golden-bishop-charge-counter-prevent-does-not-rest')
    ->deck($tristanDeck)
    ->seed('myField', 'Golden Bishop', properties: ['Counters' => ['charge' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-1')
    ->tested('Golden Bishop')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The charge counter was removed as the cost of the ability', step: 1)
    ->expectProperty('p1Field-1', 'Status', '2', 'The cost has no [REST]: Golden Bishop is still awake right after paying it', step: 1)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","GOLDEN_BISHOP_PREVENT_2"]', 'The chosen Chessman unit (Golden Bishop) now has the prevent-the-next-2-damage effect')
    ->expectProperty('p1Field-1', 'Status', '2', 'Golden Bishop is still awake after the ability resolved')
    ->expectDqEmpty()
    ->register();

// --- Gearstride Gloves: On Enter puts a preparation counter on your champion; [Class Bonus][Level 2+] Banish: the next Reaction costs 1 less ---
// Gearstride Gloves (lcb6jhxctx, REGALIA/ITEM): "On Enter: Put a preparation counter on your
// champion. [Class Bonus][Level 2+] Banish CARDNAME: The next Reaction card you activate this turn
// costs 1 less to activate." The champion is patched to Tristan, Hired Blade (level 2, ASSASSIN)
// so both the Class Bonus and the Level 2+ prereq hold (activateAbilityPrereqs["lcb6jhxctx:0"]).
// Gloves is materialized for real in the materialize phase (mem 0), firing
// enterAbilities["lcb6jhxctx:0"] -> AddPrepCounter(); its banish ability then adds the one-shot
// global effect lcb6jhxctx_REACTION_DISCOUNT, which CalculateActivationReserveCost() consumes for
// the next REACTION-subtype card. Stifling Trap (a WIND Reaction, printed reserve 2) is activated
// right after; exactly ONE reserve-payment click is required (a control run without Gloves needs
// two) and the effect is removed once used. Because player 1 holds a fast card, every phase change
// opens a priority window, hence the PASS actions.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (level 2, ASSASSIN) -> Class Bonus and Level 2+ satisfied
//   - Stifling Trap (WIND Reaction, printed reserve 2) -> p1 myHand-7
//   - Dungeon Guide, the Stifling Trap target -> p2 field-1
//   - end player 1 turn 1
//   - p1 passes the end-of-main fast window (Stifling Trap in hand keeps windows open)
//   - p1 passes the beginning-of-opponent-turn window
//   - end player 2 turn 2
//   - p1 passes a window
//   - p1 passes a window -> materialize phase
//   - materialize Gearstride Gloves from the material deck (real materialize, so its On Enter fires)
//   - banish Gloves: the next Reaction costs 1 less
//   - activate Stifling Trap from the opportunity window
//   - Stifling Trap target
gaScenario('gearstride-gloves-materialize-prep-banish-reaction-discount')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myHand', 'Stifling Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->endTurn(2)->pass(1)->pass(1)->target('myMaterial-3')
    ->answer(1, 'myField-1@Activate-0@Banish-_next_Reaction_costs_1_less')->target('myHand-7')->pay(1)
    ->target('theirField-1')
    ->tested('Gearstride Gloves')
    ->expectProperty('p1Field-1', 'CardID', 'lcb6jhxctx', 'Gearstride Gloves entered the field via a real materialize', step: 7)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'On Enter put a preparation counter on the champion', step: 7)
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'lcb6jhxctx_REACTION_DISCOUNT', 'Banishing Gloves creates the one-shot Reaction discount effect', step: 8)
    ->expectProperty('p1Banish-0', 'CardID', 'lcb6jhxctx', 'Gloves was banished as the cost of its ability')
    ->expectZoneCount('myMemory', 1, 1, 'Stifling Trap needed only ONE reserve payment (one card in memory right after the single click): printed 2 minus 1', step: 10)
    ->expectProperty('p1Graveyard-0', 'CardID', 'z5exbwdp7q', 'The discounted Reaction (Stifling Trap) resolved')
    ->expectProperty('p1GlobalEffects-0', 'CardID', '', 'The one-shot discount was consumed by that activation')
    ->expectDamage('p2Field-1', 2, 'Stifling Trap dealt its 2 damage, proving the discounted Reaction fully resolved')
    ->expectDqEmpty()
    ->register();

// --- Gearstride Gloves: the Banish ability is not offered below Level 2 ---
// Gearstride Gloves (lcb6jhxctx): the Banish ability reads "[Class Bonus] [Level 2+]";
// activateAbilityPrereqs["lcb6jhxctx:0"] requires IsClassBonusActive(ASSASSIN) && PlayerLevel >=
// 2. Here the champion is Tristan, Underhanded (level 1, ASSASSIN): Gloves still enters and puts
// its preparation counter, but the opportunity window offers only the Stifling Trap in hand, and
// answering with the Banish ability string is rejected by the engine. No global discount effect
// exists afterwards and Gloves stays on the field.
// Notes carried over from the original definition:
//   - Tristan, Underhanded (level 1, ASSASSIN): Class Bonus true but Level 2+ false
//   - Stifling Trap (WIND Reaction) -> p1 myHand-7; keeps priority windows open
//   - Dungeon Guide -> p2 field-1
//   - end player 1 turn 1
//   - p1 passes the end-of-main fast window (Stifling Trap in hand keeps windows open)
//   - p1 passes the beginning-of-opponent-turn window
//   - end player 2 turn 2
//   - p1 passes a window
//   - p1 passes a window -> materialize phase
//   - materialize Gearstride Gloves
//   - decline the window
gaScenario('gearstride-gloves-banish-requires-level-two')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Underhanded', lineage: ['Spirit of Wind'])
    ->seed('myHand', 'Stifling Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->endTurn(2)->pass(1)->pass(1)->target('myMaterial-3')
    ->rejects(1, 'myField-1@Activate-0@Banish-_next_Reaction_costs_1_less', 'The level-gated Banish ability is rejected at Level 1 (Level 2+ required)')
    ->pass(1)
    ->tested('Gearstride Gloves')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'On Enter still puts a preparation counter on the champion at Level 1', step: 7)
    ->expectProperty('p1Field-1', 'CardID', 'lcb6jhxctx', 'Gloves stays on the field: its Banish ability could not be activated')
    ->expectProperty('p1GlobalEffects-0', 'CardID', '', 'No Reaction-discount effect was created')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->register();

// --- Gloamspire Headhunter: [Class Bonus] On Ally Hit, the controller declines to pay (3) -- the hit ally is destroyed ---
// Gloamspire Headhunter (r278evrcdz, UMBRA ALLY, ASSASSIN/AUTOMATON, Stealth): "[Class Bonus] On
// Ally Hit: Destroy the hit ally unless its controller pays (3)." Player 2's champion is patched
// to Tristan, Hired Blade (ASSASSIN) for the Class Bonus; a Headhunter is seeded on its field
// (already on the field, as Stealth and the on-hit trigger need no enter event) and attacks player
// 1's Dungeon Guide on player 2's turn (player 1 declines retaliation).
// onHitAbilities['r278evrcdz:0'] -> GloamspireHeadhunterOnHit() asks the HIT ally's controller
// (player 1, who has 7+ cards) whether to pay (3). Answering NO destroys the hit Dungeon Guide:
// the combat target is stored attacker-relative ('theirField-1'), so GloamspireHeadhunterOnHit()
// re-expresses it relative to the hit ally's controller (NormalizeMzIDForController) before the
// handler resolves it inside that player's action. (Previously it resolved to the Headhunter
// itself, so the attacker died and the Dungeon Guide survived -- fixed.)
// Notes carried over from the original definition:
//   - player 2 champion Tristan, Hired Blade (ASSASSIN) -> Class Bonus active
//   - Gloamspire Headhunter (attacker, power 1) -> p2 field-1
//   - Dungeon Guide (the ally that gets hit, life 3) -> p1 field-1
//   - end player 1 turn 1
//   - the Headhunter attacks
//   - targeting player 1's Dungeon Guide
//   - player 1 declines to retaliate
//   - player 1 declines to pay (3)
gaScenario('gloamspire-headhunter-on-ally-hit-destroys-unless-paid-declined')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Wind'], player: 2)
    ->seed('myField', 'Gloamspire Headhunter', player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->pass(1)->answer(1, 'NO')
    ->tested('Gloamspire Headhunter')
    ->expectZoneCount('myField', 1, 1, 'The hit Dungeon Guide left player 1\'s field (only their champion remains) after the controller declined to pay (3)')
    ->expectProperty('p1Graveyard-0', 'CardID', 'em6eEh9q8y', 'The HIT Dungeon Guide was destroyed (it is in its controller\'s graveyard)')
    ->expectProperty('p2Field-1', 'CardID', 'r278evrcdz', 'The attacking Headhunter survives (it must not destroy itself)')
    ->expectZoneCount('myGraveyard', 0, 2, 'Nothing of the attacker\'s controller was destroyed')
    ->expectDqEmpty()
    ->register();

// --- Gloamspire Headhunter: [Class Bonus] On Ally Hit, the controller pays (3) and the hit ally survives ---
// Gloamspire Headhunter (r278evrcdz, UMBRA ALLY, ASSASSIN/AUTOMATON, Stealth): "[Class Bonus] On
// Ally Hit: Destroy the hit ally unless its controller pays (3)." Player 2's champion is patched
// to Tristan, Hired Blade (ASSASSIN) for the Class Bonus; a Headhunter is seeded on its field
// (already on the field, as Stealth and the on-hit trigger need no enter event) and attacks player
// 1's Dungeon Guide on player 2's turn (player 1 declines retaliation).
// onHitAbilities['r278evrcdz:0'] -> GloamspireHeadhunterOnHit() asks the HIT ally's controller
// (player 1, who has 7+ cards) whether to pay (3). Answering YES queues three ReserveCard payments
// from player 1's hand (cards move to memory) and the hit Dungeon Guide is NOT destroyed.
// Notes carried over from the original definition:
//   - player 2 champion Tristan, Hired Blade (ASSASSIN) -> Class Bonus active
//   - Gloamspire Headhunter (attacker, power 1) -> p2 field-1
//   - Dungeon Guide (the ally that gets hit, life 3) -> p1 field-1
//   - end player 1 turn 1
//   - the Headhunter attacks
//   - targeting player 1's Dungeon Guide
//   - player 1 declines to retaliate
//   - player 1 pays (3)
//   - payment 1/3
//   - payment 2/3
//   - payment 3/3
gaScenario('gloamspire-headhunter-on-ally-hit-pay-three-saves-ally')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Wind'], player: 2)
    ->seed('myField', 'Gloamspire Headhunter', player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->pass(1)->answer(1, 'YES')->pay(3)
    ->tested('Gloamspire Headhunter')
    ->expectProperty('p1Field-1', 'CardID', 'em6eEh9q8y', 'The hit Dungeon Guide survives because its controller paid (3)')
    ->expectDamage('p1Field-1', 1, 'It took the Headhunter\'s 1 POWER of combat damage')
    ->expectZoneCount('myMemory', 3, 1, 'Exactly three hand cards were paid into memory')
    ->expectProperty('p2Field-1', 'CardID', 'r278evrcdz', 'The Headhunter was not destroyed')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing was destroyed')
    ->expectDqEmpty()
    ->register();

// --- Grim Foreboding: [Tristan Bonus] summon an Ominous Shadow; phantasia allies you control get +1 POWER until end of turn; you gain agility 3 ---
// Grim Foreboding (4hnf1yyx1q, UMBRA fast action, reserve 3): "[Tristan Bonus] Summon an Ominous
// Shadow token. Phantasia allies you control get +1 POWER until end of turn. You gain agility 3
// for this turn." Tristan, Shadowdancer is patched in (UMBRA + Tristan lineage); an Ominous Shadow
// (a phantasia, printed power 1) and a vanilla Dungeon Guide (printed power 1) are seeded. Played
// for real: the ability summons a second Ominous Shadow, adds the 4hnf1yyx1q global effect (which
// doesAffect phantasia allies only: both Shadows, including the one summoned a moment ago, read 2
// POWER while the Dungeon Guide stays at 1) and adds AGILITY_3.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (UMBRA, Tristan lineage)
//   - Ominous Shadow (phantasia, printed power 1) -> p1 field-1
//   - Dungeon Guide (non-phantasia control, printed power 1) -> p1 field-2
//   - Grim Foreboding -> p1 myHand-7
//   - play Grim Foreboding
gaScenario('grim-foreboding-tristan-bonus-shadow-phantasia-power-agility')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myField', 'Ominous Shadow')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Grim Foreboding')
    ->castSpell('Grim Foreboding', pay: 3)
    ->register();

// --- Gildas, Faesworn Monarch: [Mordred Bonus] (2), [REST]: prevent the next 4 damage to target unit this turn ---
// Gildas, Faesworn Monarch (g99PIuhU0O, UNIQUE ALLY, EXALTED/NORM, Stealth, Vigor): "[Mordred Bonus]
// (2), [REST]: Prevent the next 4 damage that would be dealt to target unit this turn. As long as
// you control another Fairy ally, this ability costs (2) less to activate. (Activate this ability
// only if your champion is Mordred.)" Player 1's champion is Mordred, Burnished Avenger (the
// Mordred Bonus holds) and a Gildas stands on its field. Player 2 attacks player 1's champion with
// a Dungeon Guide carrying five buff counters (6 power); in the attack's priority window player 1
// activates Gildas for real: two reserve payments, the champion is the target, and the activated
// ability resolves into an IMPERIAL_COUNTERMEASURE_4 effect. The 6 combat damage is reduced by
// exactly the prevented 4, so the champion takes 2, and Gildas is rested by its [REST] cost.
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger: Mordred Bonus active
//   - Gildas, Faesworn Monarch (awake) -> p1 field-1
//   - Dungeon Guide with 5 buff counters (6 power) -> p2 field-1
//   - end player 1 turn 1
//   - p1 passes the end-of-main fast window (Gildas' ability keeps windows open)
//   - p1 passes the beginning-of-opponent-turn window
//   - player 2 attacks with the buffed Dungeon Guide
//   - targeting player 1's champion
//   - player 1 activates Gildas in the priority window
//   - the (2) cost
//   - target: the champion
//   - p1 passes the first window after the ability
//   - p1 passes the second window
gaScenario('gildas-mordred-bonus-prevent-four-damage-pays-two')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myField', 'Gildas, Faesworn Monarch')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 5]])
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(1, 'myField-1@Activate-0@Prevent')->pay(2)
    ->target('myField-0')->pass(1)->pass(1)
    ->tested('Gildas, Faesworn Monarch')
    ->register();

// --- Gildas, Faesworn Monarch: with another Fairy ally the ability costs (2) less, i.e. nothing ---
// Gildas (g99PIuhU0O): "As long as you control another Fairy ally, this ability costs (2) less to
// activate." A Warrior of the Fae Realm (a Fairy ally) stands beside Gildas, so activating the
// ability in the attack's priority window needs NO reserve payment at all: the target prompt
// comes up immediately, the hand and memory are untouched, and the champion (1 damage attacker,
// fully absorbed by the prevention) takes no damage.
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger: Mordred Bonus active
//   - Gildas -> p1 field-1
//   - Warrior of the Fae Realm (a Fairy ally) -> p1 field-2
//   - Dungeon Guide (1 power) -> p2 field-1
//   - end player 1 turn 1
//   - p1 passes the end-of-main fast window
//   - p1 passes the beginning-of-opponent-turn window
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion
//   - player 1 activates Gildas -- no payment is requested
//   - target: the champion
//   - p1 passes the window after the ability
//   - p1 passes the second window
gaScenario('gildas-another-fairy-ally-makes-ability-free')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myField', 'Gildas, Faesworn Monarch')
    ->seed('myField', 'Warrior of the Fae Realm')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(1, 'myField-1@Activate-0@Prevent')
    ->target('myField-0')->pass(1)->pass(1)
    ->tested('Gildas, Faesworn Monarch')
    ->expectZoneCount('myMemory', 0, 1, 'No reserve card was paid: the (2) cost is reduced to nothing by the other Fairy ally')
    ->expectZoneCount('myHand', 7, 1, 'The hand is untouched (no payment)')
    ->expectProperty('p1Field-1', 'Status', '1', 'Gildas is rested by the [REST] cost even though the (2) was free')
    ->expectDamage('p1Field-0', 0, 'The champion takes no damage: the 1-damage attack was prevented')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","IMPERIAL_COUNTERMEASURE_3"]', 'The prevention shield started at 4 and absorbed exactly 1 damage (3 left)')
    ->expectDqEmpty()
    ->register();

// --- Gildas, Faesworn Monarch: the ability needs the Mordred Bonus (champion must be Mordred) ---
// Gildas (g99PIuhU0O): "[Mordred Bonus] ... (Activate this ability only if your champion is
// Mordred.)" (activateAbilityPrereqs["g99PIuhU0O:0"] = IsMordredBonusActive). With the plain
// Spirit of Water as champion the activation attempt does nothing: no payment is requested, no
// target prompt appears, Gildas stays awake and no prevention effect is created.
// Notes carried over from the original definition:
//   - Gildas -> p1 field-1; the champion is the plain Spirit of Water (no Mordred lineage)
//   - try to activate Gildas' ability: nothing happens without the Mordred Bonus
gaScenario('gildas-ability-requires-mordred-champion')
    ->deck($mordredDeck)
    ->seed('myField', 'Gildas, Faesworn Monarch')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Gildas, Faesworn Monarch')
    ->expectProperty('p1Field-1', 'Status', '2', 'Gildas is still awake: the ability was not activated')
    ->expectZoneCount('myHand', 7, 1, 'No payment was requested')
    ->expectProperty('p1Field-0', 'TurnEffects', '[]', 'No prevention effect was created')
    ->expectDqEmpty()
    ->register();

// --- Gildas, Faesworn Monarch: Vigor -- a rested Gildas wakes itself up at the end of its controller's turn ---
// Gildas (g99PIuhU0O): "Stealth, Vigor". Vigor (HasVigor -> end-phase wake up in Custom/GameLogic.php)
// makes the unit ready itself at the end of its controller's turn. Player 1's champion is Mordred,
// Burnished Avenger; Gildas' [REST] ability is used in the main phase (two reserve payments, the
// champion is the target) which leaves Gildas rested, then player 1 ends the turn and Gildas is
// found awake again.
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger: Mordred Bonus active
//   - Gildas -> p1 field-1
//   - activate Gildas' ability in the main phase
//   - the (2) cost
//   - target: the champion
//   - p1 passes the window after the ability
//   - p1 passes the second window
//   - end player 1 turn 1 -> Vigor wakes Gildas up
//   - p1 passes the end-of-main fast window
//   - p1 passes the beginning-of-opponent-turn window
gaScenario('gildas-vigor-wakes-up-at-end-of-turn')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myField', 'Gildas, Faesworn Monarch')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myField-0')->pass(1)->pass(1)
    ->endTurn()->pass(1)->pass(1)
    ->tested('Gildas, Faesworn Monarch')
    ->expectProperty('p1Field-1', 'Status', '1', 'After using its [REST] ability Gildas is rested', step: 4)
    ->expectZoneCount('myMemory', 2, 1, 'The (2) cost was paid', step: 4)
    ->expectProperty('p1Field-1', 'Status', '2', 'Vigor woke Gildas up when player 1\'s turn ended')
    ->expectProperty('p1Field-1', 'CardID', 'g99PIuhU0O', 'Gildas is still on the field')
    ->expectProperty('p1Field-1', 'TurnEffects', '[]', 'No turn effects linger on Gildas')
    ->expectDqEmpty()
    ->register();

// --- Gildas, Faesworn Monarch: Stealth -- an attack cannot target it ---
// Gildas (g99PIuhU0O): "Stealth (This unit can't be targeted by attacks...)". Player 2 attacks
// with a Dungeon Guide; the attack-target prompt lists only player 1's champion, and answering
// with Gildas is rejected by the engine, so the attack is then aimed at the champion.
// Notes carried over from the original definition:
//   - Gildas (Stealth) -> p1 field-1; the champion is the plain Spirit of Water
//   - Dungeon Guide (the attacker) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 attacks with Dungeon Guide
//   - the only legal target: player 1's champion
gaScenario('gildas-stealth-cannot-be-attacked')
    ->deck($mordredDeck)
    ->seed('myField', 'Gildas, Faesworn Monarch')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'A Stealth ally (Gildas) is rejected as an attack target')->answer(2, 'theirField-0')
    ->tested('Gildas, Faesworn Monarch')
    ->expectDamage('p1Field-1', 0, 'Gildas took no damage: it was never a legal attack target')
    ->expectDamage('p1Field-0', 1, 'The attack was aimed at the only legal target, the champion (1 damage)')
    ->expectProperty('p1Field-1', 'Status', '2', 'Gildas is still awake and unharmed')
    ->expectDqEmpty()
    ->register();

// --- Gildas, Faesworn Monarch: a RESTED Gildas cannot be activated again (the [REST] cost needs an awake unit) ---
// Gildas (g99PIuhU0O): "[Mordred Bonus] (2), [REST]: Prevent the next 4 damage ..." -- a [REST]
// cost can only be paid by an awake unit. FIXED (was: the generated prereq only checked
// IsMordredBonusActive() and DoActivatedAbility() merely SET Status = 1 as an implicit REST, so the
// priority window after the first activation still offered 'myField-1@Activate-0@Prevent' and a
// second activation of the already rested Gildas was accepted, paying another (2)). The override
// $activateAbilityPrereqs['g99PIuhU0O:0'] (Custom/GeneratedAbilityOverrides.php) now also requires
// Gildas to be awake, so the rested Gildas is neither offered in the window nor activatable from
// the main phase. The first activation works and rests it (2 reserve cards paid, once); both later
// attempts are no-ops (no payment prompt, no target prompt, hand/memory unchanged).
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger: Mordred Bonus active
//   - Gildas -> p1 field-1
//   - first activation in the main phase
//   - the (2) cost
//   - target: the champion
//   - second activation attempt through the priority-window encoding while Gildas is RESTED: ignored, no (2) is charged
//   - third attempt from the main phase while Gildas is still rested: ignored too
gaScenario('gildas-rested-ability-cannot-be-reactivated')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myField', 'Gildas, Faesworn Monarch')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myField-0')
    ->answer(1, 'myField-1@Activate-0@Prevent')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Gildas, Faesworn Monarch')
    ->expectProperty('p1Field-1', 'Status', '1', 'After its first activation Gildas is rested', step: 4)
    ->expectZoneCount('myMemory', 2, 1, 'The first activation paid (2)', step: 4)
    ->expectZoneCount('myHand', 5, 1, 'Hand 5 = 7 - 2 reserve payments', step: 4)
    ->expectProperty('p1Field-1', 'Status', '1', 'Gildas is still rested after the second activation attempt', step: 5)
    ->expectZoneCount('myMemory', 2, 1, 'The rested Gildas could not be activated again from the priority window: no second (2) was paid', step: 5)
    ->expectZoneCount('myHand', 5, 1, 'Hand unchanged: no second payment prompt', step: 5)
    ->expectDqEmpty(step: 5)
    ->expectProperty('p1Field-1', 'Status', '1', 'Gildas is still rested after the third (main phase) activation attempt')
    ->expectZoneCount('myMemory', 2, 1, 'The rested Gildas could not be activated again from the main phase either: still only the one (2) paid')
    ->expectZoneCount('myHand', 5, 1, 'Hand unchanged: still 5')
    ->expectDqEmpty()
    ->register();

// --- Gildas, Faesworn Monarch: once awake again (Vigor) the ability can be activated a second time ---
// Gildas (g99PIuhU0O): "Stealth, Vigor" + "[Mordred Bonus] (2), [REST]: Prevent the next 4 damage
// ...". Control for the rested-reactivation fix: the awake requirement must not lock the ability
// out for good. Gildas is used in the main phase (two reserve cards paid, Gildas rested), player 1
// ends the turn and Vigor wakes Gildas up; player 2 then attacks the champion and in the attack's
// priority window the AWAKE Gildas is offered and activated again (a second (2) is paid, Gildas
// rests again).
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger: Mordred Bonus active
//   - Gildas -> p1 field-1
//   - Dungeon Guide with 5 buff counters (6 power) -> p2 field-1
//   - first activation in the main phase
//   - the (2) cost
//   - target: the champion
//   - p1 passes the window after the ability
//   - p1 passes the second window
//   - end player 1 turn 1 -> Vigor wakes Gildas up
//   - p1 passes the end-of-main fast window
//   - p1 passes the beginning-of-opponent-turn window
//   - player 2 attacks with the buffed Dungeon Guide
//   - targeting player 1's champion
//   - the awake Gildas is offered and activated a second time
//   - the second (2) cost
//   - target: the champion
//   - p1 passes the first window after the ability
//   - p1 passes the second window
gaScenario('gildas-ability-usable-again-after-waking-up')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myField', 'Gildas, Faesworn Monarch')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 5]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myField-0')->pass(1)->pass(1)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(1, 'myField-1@Activate-0@Prevent')->pay(2)
    ->target('myField-0')->pass(1)->pass(1)
    ->tested('Gildas, Faesworn Monarch')
    ->expectProperty('p1Field-1', 'Status', '1', 'After its first activation Gildas is rested', step: 4)
    ->expectZoneCount('myMemory', 2, 1, 'The first activation paid (2)', step: 4)
    ->expectProperty('p1Field-1', 'Status', '2', 'Vigor woke Gildas up when player 1\'s turn ended', step: 8)
    ->expectProperty('p1Field-1', 'Status', '2', 'Control: Gildas is awake when the opponent\'s attack opens the priority window', step: 11)
    ->expectZoneCount('myMemory', 2, 1, 'Control: nothing has been paid for the second activation yet', step: 11)
    ->expectProperty('p1Field-1', 'Status', '1', 'The awake Gildas could be activated a second time and is rested again', step: 16)
    ->expectZoneCount('myMemory', 4, 1, 'The second activation paid a second (2): four reserve cards in total', step: 16)
    ->expectZoneCount('myHand', 3, 1, 'Hand 3 = 7 - 4 reserve payments (two legitimate activations)', step: 16)
    ->expectDamage('p1Field-0', 2, 'The prevention worked: the champion took only 2 of the 6 combat damage', step: 16)
    ->expectDqEmpty(step: 16)
    ->register();

// Non-hand source: Gaia's Blessing (ymhDYTPfi1): "you may activate the top card of your deck if it is an Animal or Beast
// ally." The top card is a Fluffy Shopkeep (reserve 3) and the hand is empty: the click used to move the card from the
// deck into hand, announce it and leave it stranded on the effect stack; it must now be refused with the card still on
// top of the deck.
// Notes carried over from the original definition:
//   - Gaia's Blessing on the field -> p1 myField-1
//   - Fluffy Shopkeep (Animal ally, reserve 3) on top of the deck
//   - 1: click the top card of the deck with an empty hand: accepted as a no-op, refusal asserted on state
gaScenario('gaias-blessing-deck-top-ally-unaffordable-click-refused')
    ->deck($mordredDeck)
    ->seed('myField', 'Gaia\'s Blessing')
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->deckTop(['Fluffy Shopkeep'])
    ->action(1, 10002, 'myDeck-0!FSM!')
    ->tested('Gaia\'s Blessing')
    ->register();

// --- Genbu, Black Tortoise (both printings) and Seiryuu, Azure Dragon (tf5f2n38g0): regression pin -- the
// pre-existing Spellshroud holders still block spells ---
// Genbu, Black Tortoise (both printings) and Seiryuu, Azure Dragon (tf5f2n38g0) (k8bwlx70qj, u73yv2nbvj,
// tf5f2n38g0): "**Spellshroud**, **Taunt**" -- these are in HasSpellshroud()'s hard-coded ID list
// (Custom/GameLogic.php), unchanged by the printed-Spellshroud sweep fix; this fixture pins that their
// behaviour is identical:
// player 2 controls the holder(s) plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff
// counter on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, each holder
// answer is refused (expectFailure) and the legal answer resolves.
// Notes carried over from the original definition:
//   - Genbu, Black Tortoise -> p2 field-1
//   - Genbu, Black Tortoise -> p2 field-2
//   - Seiryuu, Azure Dragon -> p2 field-3
//   - Dungeon Guide -> p2 field-4
//   - Liturgy of Corruption -> p1 myHand-7
//   - player 1 plays Liturgy of Corruption
//   - the Dungeon Guide is the legal target
gaScenario('genbu-and-seiryuu-existing-spellshroud-holders-still-block-spells')
    ->deck($mordredDeck)
    ->seed('myField', 'Genbu, Black Tortoise', player: 2)
    ->seed('myField', '#u73yv2nbvj', player: 2)
    ->seed('myField', 'Seiryuu, Azure Dragon', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Liturgy of Corruption')
    ->castSpell('Liturgy of Corruption', pay: 2)
    ->rejects(1, 'theirField-1', 'Genbu, Black Tortoise (k8bwlx70qj) has spellshroud: not a legal target for the opposing Liturgy of Corruption')
    ->rejects(1, 'theirField-2', 'Genbu, Black Tortoise (u73yv2nbvj) has spellshroud: not a legal target for the opposing Liturgy of Corruption')
    ->rejects(1, 'theirField-3', 'Seiryuu, Azure Dragon (tf5f2n38g0) has spellshroud: not a legal target for the opposing Liturgy of Corruption')
    ->target('theirField-4')
    ->tested('Genbu, Black Tortoise', '#u73yv2nbvj', 'Seiryuu, Azure Dragon')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answers left Genbu, Black Tortoise (k8bwlx70qj) untouched: Spellshroud stops the opposing Liturgy of Corruption from targeting it', step: 6)
    ->expectProperty('p2Field-2', 'Counters', '[]', 'The refused answers left Genbu, Black Tortoise (u73yv2nbvj) untouched: Spellshroud stops the opposing Liturgy of Corruption from targeting it', step: 6)
    ->expectProperty('p2Field-3', 'Counters', '[]', 'The refused answers left Seiryuu, Azure Dragon (tf5f2n38g0) untouched: Spellshroud stops the opposing Liturgy of Corruption from targeting it', step: 6)
    ->expectProperty('p2Field-4', 'Counters', '[]', 'The refused answers did not apply Liturgy of Corruption to the Dungeon Guide either (prompt still pending)', step: 6)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'Genbu, Black Tortoise (k8bwlx70qj) is unaffected by the resolved Liturgy of Corruption')
    ->expectProperty('p2Field-2', 'Counters', '[]', 'Genbu, Black Tortoise (u73yv2nbvj) is unaffected by the resolved Liturgy of Corruption')
    ->expectProperty('p2Field-3', 'Counters', '[]', 'Seiryuu, Azure Dragon (tf5f2n38g0) is unaffected by the resolved Liturgy of Corruption')
    ->expectProperty('p2Field-4', 'Counters', '{"buff":1}', 'The only legal target, Dungeon Guide, received the buff counter')
    ->expectProperty('p1Graveyard-0', 'CardID', 'BZMKfFZ22T', 'Liturgy of Corruption resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// top six, top to bottom: Dungeon Guide, Fluffy Shopkeep, Fairy Whispers (SPELL), Dungeon Guide, Refracting Missile (SPELL), Fluffy Shopkeep
// Notes carried over from the original definition:
//   - reveal Fairy Whispers (the first spell, 3rd of the six) into memory
gaScenario('galestream-insight-look-six-reveal-spell-rest-bottom')
    ->deck($gaGalestreamDeck)
    ->seed('myHand', 'Galestream Insight')
    ->patch('myDeck-0', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-1', ['CardID' => 'px60u5n1do'])
    ->patch('myDeck-2', ['CardID' => 'n8wyfG9hbY'])
    ->patch('myDeck-3', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-4', ['CardID' => '6ffqsuo6gb'])
    ->patch('myDeck-5', ['CardID' => 'px60u5n1do'])
    ->castSpell('Galestream Insight', pay: 4, target: 'myTempZone-2')
    ->register();

// the ONLY spell is the sixth card from the top (Refracting Missile): it can only be offered if all six were looked at
// Notes carried over from the original definition:
//   - reveal Refracting Missile (6th card) into memory
gaScenario('galestream-insight-look-six-deep-spell-offered')
    ->deck($gaGalestreamDeck)
    ->seed('myHand', 'Galestream Insight')
    ->patch('myDeck-0', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-1', ['CardID' => 'px60u5n1do'])
    ->patch('myDeck-2', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-3', ['CardID' => 'px60u5n1do'])
    ->patch('myDeck-4', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-5', ['CardID' => '6ffqsuo6gb'])
    ->castSpell('Galestream Insight', pay: 4, target: 'myTempZone-5')
    ->register();

// Notes carried over from the original definition:
//   - decline revealing a spell
gaScenario('galestream-insight-look-six-decline-bottoms-all-six')
    ->deck($gaGalestreamDeck)
    ->seed('myHand', 'Galestream Insight')
    ->patch('myDeck-0', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-1', ['CardID' => 'px60u5n1do'])
    ->patch('myDeck-2', ['CardID' => 'n8wyfG9hbY'])
    ->patch('myDeck-3', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-4', ['CardID' => '6ffqsuo6gb'])
    ->patch('myDeck-5', ['CardID' => 'px60u5n1do'])
    ->castSpell('Galestream Insight', pay: 4, window: 'me')
    ->register();

// PLAYER 2 seating (perspective check): the same top-six stack sits under player 2's turn-start draw (deck slots 1..6)
// Notes carried over from the original definition:
//   - reveal Fairy Whispers into player 2's memory
gaScenario('galestream-insight-player-two-look-six-reveal-spell-rest-bottom')
    ->deck($gaGalestreamDeck)
    ->seed('myHand', 'Galestream Insight', player: 2)
    ->patch('myDeck-1', ['CardID' => 'em6eEh9q8y'], player: 2)
    ->patch('myDeck-2', ['CardID' => 'px60u5n1do'], player: 2)
    ->patch('myDeck-3', ['CardID' => 'n8wyfG9hbY'], player: 2)
    ->patch('myDeck-4', ['CardID' => 'em6eEh9q8y'], player: 2)
    ->patch('myDeck-5', ['CardID' => '6ffqsuo6gb'], player: 2)
    ->patch('myDeck-6', ['CardID' => 'px60u5n1do'], player: 2)
    ->endTurn()
    ->play('Galestream Insight', 2)->pay(4, 2)->answer(2, 'myTempZone-2')
    ->register();

gaScenario('galestream-insight-look-six-no-spell-bottoms-all-six')
    ->deck($gaGalestreamDeck)
    ->seed('myHand', 'Galestream Insight')
    ->patch('myDeck-0', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-1', ['CardID' => 'px60u5n1do'])
    ->patch('myDeck-2', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-3', ['CardID' => 'px60u5n1do'])
    ->patch('myDeck-4', ['CardID' => 'em6eEh9q8y'])
    ->patch('myDeck-5', ['CardID' => 'px60u5n1do'])
    ->castSpell('Galestream Insight', pay: 4)
    ->register();

gaScenario('genuflecting-execution-destroys-two-rested-allies')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->champion(lineage: ['Tristan, Shadowdancer'])
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Genuflecting Execution')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Fluffy Shopkeep', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 1], player: 2)
    ->patch('myField-2', ['Status' => 1], player: 2)
    ->playSlot(7)->pay(7)->target('theirField-1')->target('theirField-1')
    ->tested('Genuflecting Execution')
    ->register();

gaScenario('genuflecting-execution-destroys-one-rested-ally-declines-second')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->champion(lineage: ['Tristan, Shadowdancer'])
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Genuflecting Execution')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Fluffy Shopkeep', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 1], player: 2)
    ->patch('myField-2', ['Status' => 1], player: 2)
    ->playSlot(7)->pay(7)->target('theirField-1')->pass(1)
    ->tested('Genuflecting Execution')
    ->register();

// --- Gaia's Songbird (sHzSmygjWY): "[Class Bonus] On Enter: Reveal cards from the top of your deck until you reveal a Beast ally card. Put that card into your hand and the rest on the bottom of your deck."
// The generated body put the revealed-but-not-taken cards on the bottom with a fixed-index MZMove loop (only one of them moved). The existing gaias-songbird fixture only covers a Beast on top. ---
// Notes carried over from the original definition:
//   - TAMER champion: the [Class Bonus] is active (a bare class-bonus gate used to be always true)
//   - Silvie, Loved by All -- unlocks TERA
//   - Gaia's Songbird -> myHand-7
//   - reveal until Gray Wolf (BEAST ally, 4th): the three before it go to the bottom; then a marker
gaScenario('gaias-songbird-enter-reveals-until-deep-beast-rest-to-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'TAMER')
    ->champion(lineage: ['Silvie, Loved by All'])
    ->seed('myHand', 'Gaia\'s Songbird')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Gray Wolf', 'Charge the Soul'])
    ->castSpell('Gaia\'s Songbird', pay: 1)
    ->register();

gaScenario('ghastly-corrosion-refuses-memory-only-item-destroys-low-reserve-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ghastly Corrosion')
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->castSpell('Ghastly Corrosion', pay: 4)
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->target('theirField-2')
    ->register();

// Gleaming Smolder (THjSE7caau): "Draw a card and discard a card."
// Notes carried over from the original definition:
//   - ASSASSIN class
//   - Gleaming Smolder -> myHand-7
gaScenario('gleaming-smolder-draws-then-discards')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'ASSASSIN')
    ->seed('myHand', 'Gleaming Smolder')
    ->castSpell('Gleaming Smolder', pay: 3)
    ->expectZoneCount('myHand', 5, 1, 'Eight cards, minus Smolder, minus two paid, plus one drawn, minus one discarded')
    ->expectZoneCount('myGraveyard', 2, 1, 'Gleaming Smolder and the discarded card')
    ->expectZoneCount('myDeck', 12, 1, 'One card was drawn')
    ->expectDqEmpty()
    ->register();

// Negative twin of 'gaias-songbird-class-bonus-enter-reveal-beast': the champion's class does not match, so the [Class Bonus] must NOT apply.
// Notes carried over from the original definition:
//   - Silvie, Loved by All -- unlocks TERA
//   - Gray Wolf on top of deck
gaScenario('gaias-songbird-without-the-class-bonus-does-not-reveal')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Silvie, Loved by All'])
    ->patch('myDeck-0', ['CardID' => 'hJ2xh9lNMR'])
    ->seed('myHand', 'Gaia\'s Songbird')
    ->castSpell('Gaia\'s Songbird', pay: 1)
    ->register();

// Grave Gateau (FQigf17dCr): "[REST], Sacrifice CARDNAME: Put a buff counter on target Specter ally."
// Notes carried over from the original definition:
//   - Mourning Veilbound (SPECTER ally) -> myField-1
//   - Grave Gateau -> myField-2
//   - Hindered: enters rested, so wake it
gaScenario('grave-gateau-rest-and-sacrifice-puts-a-buff-counter-on-a-specter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Mourning Veilbound')
    ->seed('myField', 'Grave Gateau')
    ->patch('myField-2', ['Status' => 2])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Grave Gateau')
    ->register();

// Notes carried over from the original definition:
//   - Gossamer Staff -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('gossamer-staff-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'CLERIC,MAGE'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Gossamer Staff')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)->pass(1)
    ->tested('Gossamer Staff')
    ->register();

// Notes carried over from the original definition:
//   - Golden Measure Patisserie -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('golden-measure-patisserie-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'CLERIC'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Golden Measure Patisserie')
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
    ->tested('Golden Measure Patisserie')
    ->register();

// Golden Measure Patisserie (Bq2kynKJvx): "On Enter: Put a buff counter on target ally." With no ally on either field the enter ability queued an MZCHOOSE with an empty option list and the engine died with
// "count(): Argument #1 must be of type Countable|array, null given" (MZZoneCount('')). Core/DecisionQueueController now treats an empty option list as zero choices.
// Notes carried over from the original definition:
//   - Golden Measure Patisserie (unique domain) -> myHand-7
gaScenario('golden-measure-patisserie-enter-with-no-allies-does-not-crash')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Golden Measure Patisserie')
    ->castSpell('Golden Measure Patisserie', pay: 3)
    ->register();

// Grande Aiguille (6ihv6hbvye): "[Ciel Bonus] As long as you have two or more ally omens, Grande Aiguille gets +1 POWER."
gaScenario('grande-aiguille-ciel-bonus-gets-plus-one-power-with-two-ally-omens')
    ->deck($gaCielDeck)
    ->champion(cardName: 'Ciel, Loyal Valet')
    ->seed('myField', 'Grande Aiguille')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->target('myField-0')
    ->tested('Grande Aiguille')
    ->register();

gaScenario('grande-aiguille-with-only-one-ally-omen-has-base-power')
    ->deck($gaCielDeck)
    ->champion(cardName: 'Ciel, Loyal Valet')
    ->seed('myField', 'Grande Aiguille')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->target('myField-0')
    ->tested('Grande Aiguille')
    ->register();

gaScenario('grande-aiguille-without-the-ciel-bonus-has-base-power')
    ->deck($gaCielDeck)
    ->seed('myField', 'Grande Aiguille')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->target('myField-0')
    ->tested('Grande Aiguille')
    ->register();

// Grande Sonnerie (s4b2mkh1xm): "[Ciel Bonus] As long as the total reserve cost of your omens is 10 or greater, Grande Sonnerie gets +2 POWER."
gaScenario('grande-sonnerie-ciel-bonus-gets-plus-two-power-at-ten-omen-reserve-cost')
    ->deck($gaCielDeck)
    ->champion(cardName: 'Ciel, Loyal Valet')
    ->seed('myField', 'Grande Sonnerie')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->target('myField-0')
    ->tested('Grande Sonnerie')
    ->register();

gaScenario('grande-sonnerie-below-ten-omen-reserve-cost-has-base-power')
    ->deck($gaCielDeck)
    ->champion(cardName: 'Ciel, Loyal Valet')
    ->seed('myField', 'Grande Sonnerie')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->target('myField-0')
    ->tested('Grande Sonnerie')
    ->register();

// Guided Starlight (b0iz7wm7ow): "Your champion's next attack this turn gains unblockable unless an opponent pays (3). You may load Guided Starlight into an Aetherwing weapon you control."
gaScenario('guided-starlight-grants-the-next-attack-unblockable-then-loads')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Arisanna, Astral Zenith'], classes: 'RANGER')
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myHand', 'Guided Starlight')
    ->play('Guided Starlight')->pay(2)->answer(1, 'YES')
    ->register();

// Guarded Dissipation (r5zs29xxoo): "Prevent the next X damage that would be dealt to target unit this turn, where X is the greatest power among Sword weapons you control. [Level 1+] Draw a card." The
// handler read $maxPower (undefined): the effect was named PREVENT_ALL_ with no amount. A Cheap Sword (power 1) is the only Sword.
// Notes carried over from the original definition:
//   - Cheap Sword token (power 1) -> field-1
gaScenario('guarded-dissipation-prevents-damage-equal-to-greatest-sword-power-and-draws')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Guarded Dissipation')
    ->seed('myField', 'Cheap Sword')
    ->castSpell('Guarded Dissipation', pay: 2, target: 'myField-0')
    ->tested('Guarded Dissipation', 'Cheap Sword')
    ->register();

// Grim Pastiche (akmv2ssjhu): "Copy target non-Skill action omen you have with reserve cost 5 or less. If you do, you may activate that copy without paying its reserve cost." The second handler read
// $copyCardID (undefined): saying yes activated nothing. The omen is a Convergent Beam (1 damage to target unit).
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (UMBRA)
//   - Convergent Beam as an omen
gaScenario('grim-pastiche-copies-an-action-omen-and-activates-the-copy-free')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Grim Pastiche')
    ->champion(cardName: 'Tristan, Shadowdancer')
    ->seed('myBanish', 'Convergent Beam', properties: ['Counters' => ['omen' => 1]])
    ->play('Grim Pastiche')->pay(2)->target('myBanish-0')->answer(1, 'YES')->target('theirField-0')
    ->tested('Grim Pastiche', 'Convergent Beam')
    ->register();

// Glassgale Flock (KRNYwHCOVM): "[Merlin Bonus] [Sheen 6+] (2), [REST]: If you don't control an ally named Memorite Shardwing, summon a Memorite Shardwing token."
// Notes carried over from the original definition:
//   - Merlin, Memorite Vassal
//   - Glassgale Flock -> myField-1 (placing it ran its On Enter, which summoned a Shardwing -> myField-2)
//   - ... turned into a Dungeon Guide so the ability's own summon is what is tested
gaScenario('glassgale-flock-pays-two-rests-and-summons-a-memorite-shardwing')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->seed('myMastery', 'Fractured Memories', properties: ['Counters' => ['sheen' => 6]])
    ->seed('myField', 'Glassgale Flock')
    ->patch('myField-2', ['CardID' => 'em6eEh9q8y'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)
    ->tested('Glassgale Flock')
    ->register();

// With a Shardwing already on the field the ability may still be activated (the condition is part of the effect): the (2) and [REST] are paid and nothing is summoned.
// Notes carried over from the original definition:
//   - its On Enter summoned the Shardwing -> myField-2
gaScenario('glassgale-flock-with-a-shardwing-already-pays-but-summons-nothing')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->seed('myMastery', 'Fractured Memories', properties: ['Counters' => ['sheen' => 6]])
    ->seed('myField', 'Glassgale Flock')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)
    ->tested('Glassgale Flock')
    ->register();
