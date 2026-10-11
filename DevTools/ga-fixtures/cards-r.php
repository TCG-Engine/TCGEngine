<?php
/**
 * Fixtures for cards starting with 'R', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Reclaim: return target friendly ally to hand ---
// Reclaim's element is WIND, so the starting champion's Subcards are patched with a real WIND
// champion (Spirit of Wind) to unlock element access, same as deployment-beacon-summon-drone.
// Dungeon Guide is seeded onto our own field as the "target ally you control" target. The
// Floating Memory clause (banishing this card from the graveyard to help pay a later memory
// cost) is a passive/reusable-elsewhere property, not a triggered ability, and is out of scope.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Dungeon Guide (ALLY) - return-to-hand target
gaScenario('reclaim-return-ally')
    ->standardDeck('Reclaim')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Reclaim')
    ->castSpell('Reclaim', pay: 2, window: 'both', target: 'myField-1')
    ->register();

// --- Ruinous Pillars of Qidao: Class Bonus On Enter Empower 2 + draw ---
// Ruinous Pillars of Qidao is a TERA (advanced element) DOMAIN card, so the starting
// champion's Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock
// element access, and that same champion is physically seeded onto the field so the MAGE
// Class Bonus (physical-presence check) is also satisfied. Only the On Enter Empower 2 + draw
// is covered; the Shifting-Currents-triggered sacrifice/destroy is tied to the separate facing
// subsystem and is out of scope.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Kongming, Fel Eidolon (MAGE CHAMPION) - Class Bonus source
gaScenario('ruinous-pillars-of-qidao-enter-empower')
    ->standardDeck('Ruinous Pillars of Qidao')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myField', 'Kongming, Fel Eidolon')
    ->seed('myHand', 'Ruinous Pillars of Qidao')
    ->castSpell('Ruinous Pillars of Qidao', pay: 3)
    ->expectProperty('p1Field-0', 'TurnEffects', '["pmx99jrukm","EMPOWERED","EMPOWER_PLUS_2"]', 'On Enter empowers the first champion by 2')
    ->expectZoneCount('myDeck', 8, 1, 'On Enter also draws a card, reducing the deck by one')
    ->expectDqEmpty()
    ->register();

// --- Rocket Jump: target unit becomes distant ---
// Rocket Jump's element is FIRE (matching the starting champion), so no lineage patch is
// needed. Only the base "becomes distant" status is covered; the [Class Bonus] discount isn't
// reached (full 5-reserve cost paid), and the "deal 4 damage to its attacker" clause is
// conditional on the target actually defending in combat, which is out of scope.
gaScenario('rocket-jump-distant')
    ->standardDeck('Rocket Jump')
    ->seed('myHand', 'Rocket Jump')
    ->castSpell('Rocket Jump', pay: 5, window: 'opp', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","DISTANT"]', 'The targeted champion becomes distant')
    ->expectDqEmpty()
    ->register();

// --- Rally the Peasants: look at top 6, may reveal a Human into hand ---
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
gaScenario('rally-the-peasants-search-human')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Rally the Peasants
8 Formidable Youxia
4 Dungeon Guide
4 Fairy Whispers
DECK
    )
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myHand', 'Rally the Peasants')
    ->castSpell('Rally the Peasants', pay: 3, window: 'both')
    ->answer(1, 'ToHand=acmde97dbu;Reveal=acmde97dbu,acmde97dbu,acmde97dbu;')
    ->register();

// --- Reposition: target unit becomes distant ---
// Notes carried over from the original definition:
//   - Dungeon Guide (ALLY) - target
gaScenario('reposition-distant')
    ->standardDeck('Reposition')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Reposition')
    ->castSpell('Reposition', pay: 1, window: 'both', target: 'theirField-1')
    ->register();

// --- Retold Fortune: discard up to one Spell, if you do draw a card ---
// Notes carried over from the original definition:
//   - Expel the Departed (SPELL) - discard fuel
gaScenario('retold-fortune-discard-draw')
    ->standardDeck('Retold Fortune')
    ->seed('myHand', 'Expel the Departed')
    ->seed('myHand', 'Retold Fortune')
    ->castSpell('Retold Fortune', pay: 2, window: 'both', target: 'myHand-5')
    ->register();

// --- Regenerate: draw a card into memory ---
gaScenario('regenerate-draw')
    ->standardDeck('Regenerate')
    ->seed('myHand', 'Regenerate')
    ->castSpell('Regenerate', pay: 2)
    ->register();

// --- Revitalizing Cleanse: recover X (water cards in memory), draw a card ---
// Notes carried over from the original definition:
//   - WATER lineage/element unlock + pre-damage so recovery is observable
//   - Water Barrier (WATER) - memory fuel 1/2
//   - Tsunami of Nanyue (WATER) - memory fuel 2/2
gaScenario('revitalizing-cleanse-recover-draw')
    ->standardDeck('Revitalizing Cleanse')
    ->champion(lineage: ['Spirit of Water'], damage: 5)
    ->seed('myMemory', 'Water Barrier')
    ->seed('myMemory', 'Tsunami of Nanyue')
    ->seed('myHand', 'Revitalizing Cleanse')
    ->castSpell('Revitalizing Cleanse', pay: 5)
    ->register();

// --- Rumble Coordinator: Vigor -- wakes up at the beginning of your end phase ---
// Rumble Coordinator's element is ARCANE, so the starting champion's Subcards are patched
// with a real ARCANE champion (Lorraine, Arclight Saber) to unlock element access. It is
// seeded directly onto the field, pre-rested (Status 1), then a second setup step patches its
// Status back down to confirm the rested precondition -- ending the turn once triggers Vigor's
// unconditional "wakes up at the beginning of your end phase" keyword, so it ends the turn
// already readied even though it's now the opponent's turn. Only the base Vigor keyword is
// covered; both [Class Bonus] clauses (+1 power per static counter, On Death draw) are out of
// scope for the default (non-matching-class) champion.
// Notes carried over from the original definition:
//   - ARCANE lineage/element unlock
//   - Rumble Coordinator
//   - pre-rest it so the wake-up is observable
gaScenario('rumble-coordinator-vigor-wake')
    ->standardDeck('Windslice')
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myField', 'Rumble Coordinator')
    ->patch('myField-1', ['Status' => 1])
    ->endTurn()
    ->tested('Rumble Coordinator')
    ->register();

// --- Return Stroke: Floating Memory pays a champion level-up's memory cost from graveyard ---
// Same Floating Memory keyword and same test shape as shieldroid-floating-memory /
// stalwart-shieldmate-floating-memory / honorable-vanguard-floating-memory. Return Stroke is an
// ATTACK card, but Floating Memory only cares about paying a memory cost from the graveyard --
// unrelated to its own reserve cost or to it ever being played as an attack -- so the same
// champion-level-up-from-graveyard shape applies unchanged. Its "[Class Bonus] +LVPOWER" static
// buff is a computed combat-time value with no independently stored counter, so it's out of
// scope here.
gaScenario('return-stroke-floating-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myGraveyard', 'Return Stroke')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')
    ->tested('Return Stroke')
    ->expectProperty('myField-0', 'CardID', 'DpHDGaX2Pn', 'The champion level-up completes -- Lorraine, Wandering Warrior takes the field')
    ->expectZoneCount('myGraveyard', 0, 1, 'Return Stroke leaves the graveyard, paying for the level-up\'s memory cost')
    ->expectZoneCount('myBanish', 1, 1, 'Floating Memory banishes Return Stroke rather than tapping a myMemory card')
    ->expectZoneCount('myMemory', 0, 1, 'No myMemory card was seeded or spent -- the cost was paid entirely from the graveyard')
    ->expectDqEmpty()
    ->register();

// --- Rising Tides: [Class Bonus][Level 3+] Draw a card into your memory ---
// IsClassBonusActive(["MAGE"]) && PlayerLevel($player) >= 3 (GrandArchiveSim/GeneratedCode/
// GeneratedMacroCode.php's cardActivatedAbilities["y6q4goxi8a:0"]) are independent checks: the
// level check reads the main champion object (patched to Lorraine, Arclight Saber, level 3,
// same as wisdoms-reprise-level3-draw-memory), and the class check scans ALL physical field
// objects for a MAGE (same physically-seeded Kongming, Fel Eidolon as
// devoted-bloomweaver-class-bonus-empower/vernal-talisman-preserve-draw) -- two separate
// objects. Unlike NORM (always enabled, GameLogic.php's CanPlayerMeetCardElementRequirements /
// IsNormOnlyElementProperty), Rising Tides' WATER element is NOT free: GetPlayerEnabledElements()
// scans GetChampionLineage() = [main champion CardID] + its Subcards, and neither
// x9sSpjpP3G (ARCANE) nor the physically-seeded Fel Eidolon (TERA, not in the main champion's
// Subcards anyway) grants WATER -- discovered empirically after the FSM click on Rising Tides
// silently no-opped (DoActivateCard's !CanPlayerUseCardElement(...) early-return,
// GameLogic.php ~line 2093). Fixed by ALSO patching the main champion's Subcards with Spirit of
// Water (a WATER-elemental champion), independent of the CardID patch that provides the level.
// Notes carried over from the original definition:
//   - Lorraine, Arclight Saber (level 3) + Spirit of Water (WATER element access) in lineage
//   - Kongming, Fel Eidolon (MAGE CHAMPION), physically seeded for Class Bonus
//   - Rising Tides
//   - pay 2-reserve cost, card 1/2
//   - pay 2-reserve cost, card 2/2
gaScenario('rising-tides-class-bonus-level3-draw-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Arclight Saber', lineage: ['Spirit of Water'])
    ->seed('myField', 'Kongming, Fel Eidolon')
    ->seed('myHand', 'Rising Tides')
    ->castSpell('Rising Tides', pay: 2)
    ->expectZoneCount('myMemory', 3, 1, '[Class Bonus][Level 3+] draws 1 card into memory (2 pregame-default memory cards + 1 from this ability)')
    ->expectDqEmpty()
    ->register();

// --- Rousing Slam: [Class Bonus] [Level 2+] On Attack: attacker gains vigor and taunt ---
// PlayerLevel($player) >= 2 is checked against the CHAMPION's own level, so patching the
// champion directly to Tonoris, Might of Humanity (level 2, GUARDIAN) satisfies both the
// [Class Bonus] and [Level 2+] gates in one step (same CardID-patch bypass used for the Diana
// champion-lineage fixtures -- CanChampionLevelUpIntoCard-style level requirements only check
// current state, not lineage). Rousing Slam is WIND (advanced element), so the champion's
// Subcards also carry a WIND lineage unlock. Rule 1.h applies to activating any ATTACK-type
// card, so it's played on player 2's turn (after player 1 ends turn 1), then the champion
// declares a real attack for OnAttack to fire.
// Notes carried over from the original definition:
//   - Tonoris, Might of Humanity (level 2, GUARDIAN) + WIND lineage unlock
//   - ends turn 1
//   - decline an ambient fast-opportunity window
//   - decline the active-response opportunity for the spell on the stack
//   - target player 1's champion -- Rousing Slam's own effect IS the attack
//   - decline a post-resolution opportunity window
gaScenario('rousing-slam-class-bonus-level-2-on-attack-vigor-taunt')
    ->standardDeck('Rousing Slam')
    ->champion(cardName: 'Tonoris, Might of Humanity', lineage: ['Spirit of Wind'], player: 2)
    ->seed('myHand', 'Rousing Slam', player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Rousing Slam', 2)->pay(4, 2)->pass(2)->answer(2, 'theirField-0')->pass(2)
    ->expectDqEmpty(step: 11)
    ->expectDamage('p1Field-0', 4, 'Rousing Slam\'s own 4 POWER resolves as a real attack', step: 11)
    ->expectProperty('p2Field-0', 'TurnEffects', '["VIGOR_NEXT_TURN","TAUNT_NEXT_TURN"]', '[Class Bonus][Level 2+] On Attack: the attacker gains vigor and taunt until the beginning of next turn', step: 11)
    ->register();

// --- Rebellious Bull: enters the field rested (Pride 3) ---
// Rebellious Bull is NORM. Its "enters the field rested" clause is directly observable via
// Status after a normal FSM materialize -- Pride 3 (a combat-only restriction, same mechanic
// as gray-wolf-pride-2-attack-rejected) is not separately re-tested here.
gaScenario('rebellious-bull-enters-rested')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Rebellious Bull')
    ->castSpell('Rebellious Bull', pay: 5)
    ->register();

// --- Rai, Archmage: Inherited Effect -- first Mage action card each turn, put an enlighten counter on champion ---
// Rai, Archmage's Inherited Effect (GameLogic.php ~5670-5680) checks ChampionHasInLineage,
// which reads the champion object's own CardID plus Subcards -- a direct CardID patch is
// sufficient (no natural level-up needed). Idle Thoughts (rWhFC8XBaH, NORM, MAGE, reserve 1,
// already used in idle-thoughts-glimpse-4 with this exact generic deck/seed) is the first MAGE
// ACTION card activated this turn, putting an enlighten counter on the champion and setting the
// RAI_ARCHMAGE_TRIGGERED once-per-turn flag.
// Notes carried over from the original definition:
//   - become Rai, Archmage
//   - pay 1-reserve cost
gaScenario('rai-archmage-inherited-first-mage-action-enlighten')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Rai, Archmage')
    ->seed('myHand', 'Idle Thoughts')
    ->play('Idle Thoughts')->pay(1)->answer(1, 'Top=n8wyfG9hbY,px60u5n1do,n8wyfG9hbY,n8wyfG9hbY;Bottom=')
    ->tested('Rai, Archmage')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"enlighten":1}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'rWhFC8XBaH')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'RAI_ARCHMAGE_TRIGGERED')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 1, 1)
    ->expectZoneCount('myDeck', 5, 1, 'Glimpse 4 only looks and reorders -- answering the rearrange with exactly the offered cards leaves the 5-card deck intact')
    ->register();

// --- Rai, Spellcrafter: On Enter: put two enlighten counters on CARDNAME ---
// Rai, Spellcrafter is level 1, one level above the default level-0 starting champion, so no
// lineage/level patch is needed -- 0+1 is already a legal level-up (same as arisanna-herbalist-
// prodigy-on-enter-gather-twice). Champion-swap materialization is only offered through the
// material-phase MZMAYCHOOSE at the start of a turn, so both players end their first turn
// (P1 -> P2) to reach that prompt; its 1-memory cost is paid from a filler card seeded into
// myMemory. Her On Enter puts 2 enlighten counters on herself (GeneratedMacroCode.php
// ~11447-11451).
// Notes carried over from the original definition:
//   - filler card in memory to pay the 1-memory level-up cost
gaScenario('rai-spellcrafter-on-enter-enlighten')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Spellcrafter
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
    ->tested('Rai, Spellcrafter')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'CardID', 'gPKTJKqvOI')
    ->expectProperty('p1Field-0', 'Counters', '{"enlighten":2}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Banish-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p1Material-0', 'CardID', 'm31WVJ9F04')
    ->expectProperty('p1Material-1', 'CardID', '9gv4vm4kj3')
    ->expectProperty('p1Material-2', 'CardID', 'LeyUk5auEP')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'LEVELED_UP_THIS_TURN')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Rai, Storm Seer: +1 level for each arcane element Mage Spell card in your banishment ---
// Rai, Storm Seer's level bonus (GameLogic.php ~13279-13288) is a continuous computed check
// (+1 level per qualifying banished card) with no stored counter to assert directly -- the
// champion is patched directly to Rai, Storm Seer (the check reads $obj->CardID literally, not
// lineage, so a direct patch is sufficient), and Shock Therapy (tyj2s3572j, an ARCANE MAGE
// SPELL action card) is seeded into myBanish. Same indirect Glimpse-LV proof technique as
// beastbond-ears-level-while-animal-beast: at the default level 0, Scry the Skies' Glimpse LV
// would be an invisible no-op, but with the +1 level bonus active (1 qualifying banished card),
// Glimpse LV surfaces a real 1-card MZREARRANGE decision that must be explicitly answered.
// Notes carried over from the original definition:
//   - Rai, Storm Seer
//   - Shock Therapy (ARCANE, MAGE, SPELL)
//   - MZREARRANGE response: this decision only exists because the effective level is 1, not the
//   - default 0 -- proof that Rai, Storm Seer's static bonus is applying.
gaScenario('rai-storm-seer-level-per-arcane-mage-spell-banished')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Storm Seer
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
    ->champion(cardName: 'Rai, Storm Seer')
    ->seed('myBanish', 'Shock Therapy')
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)->answer(1, 'Top=em6eEh9q8y,n8wyfG9hbY,em6eEh9q8y,px60u5n1do;Bottom=')
    ->tested('Rai, Storm Seer')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'F9POfB5Nah')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->expectZoneCount('myDeck', 4, 1, 'Scry the Skies\' Glimpse 4 only looks and reorders, then draws one card into memory -- answering the rearrange with exactly the offered cards leaves 5 - 1 = 4 cards in the deck')
    ->register();

// --- Red Slime stack-order choice: a generated closure's own synchronous multi-kill sweep
// (mttsvbgl6f:0 On Death, GeneratedCode/GeneratedMacroCode.php) chain-kills 2+ of its own
// controller's other allies with On Death triggers. ResolveTopOfEffectStack() (hand-written,
// OpportunityLogic.php) now wraps its whole trigger-resolution dispatch in
// BeginTriggeredAbilityBatch()/EndTriggeredAbilityBatch() so this case gets the same
// controller-chooses-stacking-order treatment as OnAttackTrigger/OnHitTrigger/OnKillTrigger.
// Killed via two casts of Charge the Soul (ra9950o14t, NORM, 1 reserve, "Deal 1 damage to
// target unit") targeting our own Red Slime, rather than combat -- no weapon/retaliate/
// combat-cleanup timing to fight, and (per the powered-defender-dealt-damage-summons-
// powercell precedent) a single cast resolves immediately with no extra PASS actions needed.
// Notes carried over from the original definition:
//   - Guo Jia, Chosen Disciple: plain single-class TAMER champion with no special-cased
//   - ObjectCurrentHP/FieldAfterAdd logic (unlike Silvie, Wilds Whisperer, whose "next
//   - Animal/Beast ally enters with a buff counter" passive silently buffed Red Slime's
//   - life from 2 to 3 -- Red Slime is subtyped BEAST -- and made it survive exactly-lethal
//   - damage; verified live via a temporary debug print in OnDealDamage).
//   - Guo Jia, Chosen Disciple -- TAMER champion, satisfies Red Slime's Class Bonus
//   - Red Slime, life 2
//   - Golden Pawn, life 1 -- On Death: Draw a card
//   - Heavenly Drake, life 3 -- On Death: Recover 3
//   - Charge the Soul #1 -- myHand-7
//   - Charge the Soul #2 -- myHand-8
//   - Cast #1: myHand-7, pay 1 reserve with myHand-0, target Red Slime at myField-2
//   - (champion=0, Silvie=1, Red Slime=2, Golden Pawn=3, Heavenly Drake=4).
//   - Decline the Effect Stack Opportunity's "play a fast card in response" offer
//   - (the second Charge the Soul copy, myHand-6) so the first cast resolves first.
//   - Target Red Slime (myField-2) with the first Charge the Soul.
//   - Cast #2: second Charge the Soul copy, now at myHand-6 after the first cast + its
//   - reserve-cost payment each removed one card ahead of it. First cast's target-response
//   - (mode=100, myHand-N) worked without FSM because it was answering the still-open
//   - OpportunityWindowFirstResponse MZMAYCHOOSE left over from the P1/P2 health-passes --
//   - once that resolves to a clean MAIN-phase state (decisionQueue=0), a fresh play needs
//   - the normal FSM free-play click instead.
//   - The fix under test: Red Slime's death sweep just killed both Golden Pawn and Heavenly
//   - Drake simultaneously, and the controller is now asked to choose their stacking order
//   - (MZCHOOSE:myGraveyard-3&myGraveyard-4 / TriggerOrderPickResolve) instead of them
//   - silently auto-resolving in a hardcoded order. Pick myGraveyard-3 (Golden Pawn) first.
gaScenario('redslime-ondeath-sweep-stack-order-choice')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Red Slime')
    ->seed('myField', 'Golden Pawn')
    ->seed('myField', '#loSCQzxqi1')
    ->seed('myHand', 'Charge the Soul')
    ->seed('myHand', 'Charge the Soul')
    ->endTurn()
    ->endTurn(2)->target('myHand-7')->pay(1)->pass(1)->target('myField-2')
    ->playSlot(6)->pay(1)->target('myField-2')->target('myGraveyard-3')
    ->tested('Red Slime', 'Golden Pawn', '#loSCQzxqi1')
    ->register();

// --- Rebounding Gust: move target ally to its controller's memory (9e0z7hb9id) ---
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - opponent's Dungeon Guide, target ally
gaScenario('rebounding-gust-target-to-memory')
    ->deck($fillerDeck)
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Rebounding Gust')
    ->play('Rebounding Gust')->pay(3)->answer(1, '-')->target('theirField-1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', '9e0z7hb9id')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-0', 'CardID', 'em6eEh9q8y', 'Playing Rebounding Gust fires its on-play trigger (playCardAbilities["9e0z7hb9id:0"]) and moves the target opposing ally into its controller\'s (player 2\'s) memory zone')
    ->expectProperty('p2Memory-0', 'Status', '2')
    ->expectDamage('p2Memory-0', 0)
    ->expectProperty('p2Memory-0', 'Counters', '[]')
    ->expectProperty('p2Memory-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->expectZoneCount('theirField', 1, 1)
    ->expectZoneCount('theirMemory', 1, 1)
    ->register();

// --- Rending Flames: [Class Bonus] On Attack, may banish 3 fire cards from graveyard for double damage ---
// Rending Flames's element is FIRE, but no Zander champion is itself FIRE (all are NORM or
// LUXEM, verified via CardElement()), so patching the champion's CardID alone to an ASSASSIN
// Zander champion for the Class Bonus condition would lose FIRE access entirely (verified
// live: with CardID patched to Zander, Deft Executor alone, the FSM activation silently
// no-oped). GetChampionLineage() (GameLogic.php) returns [CardID] merged with Subcards, so the
// champion's CardID is patched to Zander, Deft Executor (ASSASSIN) for Class Bonus AND its
// Subcards are separately patched to include Corhazi Courier (FIRE) for element access -- both
// conditions are independent properties on the same object. Rule 1.h blocks the game's first
// player from attacking on turn 1, so player 1 ends turn 1 and player 2 plays Rending Flames on
// their own turn 1 instead. Three copies of Corhazi Courier (FIRE element) are seeded directly
// into player 2's graveyard to satisfy the "banish three fire element cards from your
// graveyard" cost. Rending Flames's printed POWER is 3; doubled damage (6) on the champion is
// only possible if the ability's "deals double that damage instead" effect actually applied.
// Notes carried over from the original definition:
//   - Zander, Deft Executor (ASSASSIN) + FIRE lineage/element unlock
//   - Corhazi Courier (FIRE) #1
//   - Corhazi Courier (FIRE) #2
//   - Corhazi Courier (FIRE) #3
//   - ends turn 1 (first-player attack lock)
//   - target opponent's champion
//   - banish 3 fire cards for double damage
//   - select all 3 Corhazi Couriers
//   - decline Retaliate so CombatApplyAttackerDamage actually lands
gaScenario('rending-flames-onattack-banish-double-damage')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Deft Executor', lineage: ['Corhazi Courier'], player: 2)
    ->seed('myGraveyard', 'Corhazi Courier', player: 2)
    ->seed('myGraveyard', 'Corhazi Courier', player: 2)
    ->seed('myGraveyard', 'Corhazi Courier', player: 2)
    ->seed('myHand', 'Rending Flames', player: 2)
    ->endTurn()
    ->play('Rending Flames', 2)->pay(3, 2)->answer(2, 'theirField-0')->answer(2, 'YES')
    ->answer(2, 'myGraveyard-0&myGraveyard-1&myGraveyard-2')->answer(1, '-')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 6, 'Champion takes double damage (3 printed POWER x2 = 6) after banishing 3 fire cards')
    ->expectProperty('p1Field-0', 'Counters', '{"_champDamageThisTurn":6}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P2"]')
    ->expectProperty('p2Field-0', 'Status', '1')
    ->expectProperty('p2Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":6}')
    ->expectProperty('p2Hand-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Graveyard-0', 'CardID', 'soO3hjaVfN')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Banish-0', 'CardID', 'YqQsXwEvv5', 'Banishes the 3 fire cards from graveyard as the ability\'s cost')
    ->expectProperty('p2Banish-0', 'Counters', '[]')
    ->expectProperty('p2Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p2Banish-1', 'CardID', 'YqQsXwEvv5')
    ->expectProperty('p2Banish-1', 'Counters', '[]')
    ->expectProperty('p2Banish-1', 'TurnEffects', '[]')
    ->expectProperty('p2Banish-2', 'CardID', 'YqQsXwEvv5')
    ->expectProperty('p2Banish-2', 'Counters', '[]')
    ->expectProperty('p2Banish-2', 'TurnEffects', '[]')
    ->expectProperty('p2GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectZoneCount('theirHand', 5, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 3, 1)
    ->register();

// --- Roots of Tomorrow: reveal top of deck into material preserved, draw into memory ---
// Roots of Tomorrow (reserve 1, TERA) calls RootsOfTomorrowResolve() directly
// (GeneratedMacroCode.php cardActivatedAbilities["MkhP6iKyLX:0"]): moves the top deck card into
// myMaterial (preserved) then draws a separate card into myMemory. TERA-element access is
// unlocked the same way as the other Kongming fixtures (Subcards patched with Kongming, Fel
// Eidolon). Distinguishing evidence is the exact zone-count combination -- myDeck down 2 (1
// moved to material, 1 drawn to memory), myMaterial up 1, myMemory up 1 -- which nothing else
// in this bare fixture would produce.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
gaScenario('roots-of-tomorrow-preserve-draw')
    ->deck(GA_KONGMING_BASE_DECK)
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myHand', 'Roots of Tomorrow')
    ->castSpell('Roots of Tomorrow', pay: 1)
    ->register();

// --- Ritai Berserker: +1 POWER while Shifting Currents face North ---
// Ritai Berserker's static +1 POWER while facing North (GameLogic.php ~11947) is a pure
// computed-power check -- no action needed beyond seeding the mastery direction and the ally
// itself. 2 base POWER + 1 while facing North = 3.
// Notes carried over from the original definition:
//   - Shifting Currents facing North
//   - Ritai Berserker
gaScenario('ritai-berserker-north-power')
    ->deck(GA_KONGMING_BASE_DECK)
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Ritai Berserker')
    ->endTurn()
    ->tested('Ritai Berserker')
    ->register();

// --- Redirect Orbit: shuffle hand/memory cards into deck, then draw that many into memory ---
// Redirect Orbit (Tst4WbM6O8, ASTRA, reserve 2) calls RedirectOrbitChoose($player, 0)
// (cardActivatedAbilities["Tst4WbM6O8:0"], GeneratedMacroCode.php ->
// GrandArchiveSim/Custom/CardDQHandlers.php ~4622), which loops an MZMAYCHOOSE over
// hand+memory cards; choosing a card shuffles it into the deck and recurses with count+1,
// and passing ends the loop with ShuffleZone(myDeck) + DrawIntoMemory($player, count). ASTRA
// is an advanced element -- without unlocking it, playing the card silently no-ops, so the
// champion is CardID-patched to Arisanna, Astral Zenith (q3huqj5bba, CLERIC/ASTRA). One known
// hand card (Fluffy Shopkeep) is chosen, then the loop is passed -- proving both halves (a
// card actually leaves hand into the deck, and exactly that many are then drawn into memory,
// not hand) via zone counts.
// Notes carried over from the original definition:
//   - Arisanna, Astral Zenith - ASTRA unlock
gaScenario('redirect-orbit-shuffle-draw')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Arisanna, Astral Zenith')
    ->seed('myHand', 'Redirect Orbit')
    ->play('Redirect Orbit')->pay(3)->answer(1, '-')
    ->expectZoneCount('myHand', 5, 1, 'Before choosing a card, hand still holds its pre-shuffle count', step: 3)
    ->expectZoneCount('myDeck', 5, 1, 'Before choosing a card, deck still holds its original count', step: 3)
    ->expectZoneCount('myHand', 4, 1, 'Choosing a hand card removes it from hand', step: 4)
    ->expectZoneCount('myDeck', 6, 1, 'The chosen card is shuffled into the deck (deck grows by one)', step: 4)
    ->expectZoneCount('myDeck', 5, 1, 'Declining ends the loop: exactly one card (the count chosen) is drawn back out of the deck')
    ->expectZoneCount('myMemory', 3, 1, 'The drawn card lands in MEMORY, not hand -- hand count stays at 4 while memory grows by exactly one')
    ->expectZoneCount('myHand', 4, 1, 'Hand is unaffected by the final draw -- it goes to memory, not hand')
    ->register();

// --- Refracted Twilight: Banish self -- tag target Potion to copy its next activation twice ---
// Refracted Twilight (me0xxw0plq) is seeded directly onto the field (its own Banish ability
// doesn't care how it got there, and Brewing isn't the clause under test). Its
// activateAbilityAbilities["me0xxw0plq:0"] (GeneratedMacroCode.php) offers a choice among
// ITEM/POTION-subtype field objects; customDQHandlers["me0xxw0plq:0:ActivateAbility-1"] tags
// the chosen target with TurnEffect "me0xxw0plq_COPY2" (consumed elsewhere,
// GrandArchiveSim/Custom/GameLogic.php ~6873, when that potion is next activated). A second
// Potion (Potion of Healing) is seeded onto the field as the only legal target.
// Notes carried over from the original definition:
//   - Refracted Twilight - the activator
//   - Potion of Healing - only legal target
gaScenario('refracted-twilight-banish-copy-tag')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->seed('myField', 'Refracted Twilight')
    ->seed('myField', 'Potion of Healing')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-1')
    ->tested('Refracted Twilight')
    ->register();

// --- Reaping Legacy: [Class Bonus] Reaping Legacy gets +1POWER for each Sword regalia weapon
// card in your banishment. Hand-authored ability (see GrandArchiveSim/Custom/GameLogic.php,
// ObjectCurrentPower()'s "Self power modifiers" switch, case "XDVIiIfKZk" -- no CardEditor ability
// database row was reachable in this sandbox) ---
// A plain, minimal deck (mirroring shred-to-ribbons-class-bonus-attack-ally-5-life-power)
// rather than GA_LORRAINE_PANTHEON_DECK: that deck's richer starting hand leaves extra
// fast-speed material live, which turns the post-combat opportunity window into several extra
// undetermined decline rounds. Reaping Legacy itself is seeded directly into intent below, so
// it doesn't need to be in this deck at all.
// ATTACK cards can't be freely FSM-activated from hand outside of a real attack declaration
// (confirmed empirically -- the click is silently a no-op); Reaping Legacy is instead seeded
// directly into player 2's myIntent (Controller/Owner set explicitly, same shortcut as the
// shred-to-ribbons-class-bonus-attack-ally-5-life-power fixture), bypassing the hand-play/
// reserve flow entirely. Player 2's champion is patched to Lorraine, Wandering Warrior
// (DpHDGaX2Pn, WARRIOR) for the [Class Bonus], and two Sword regalia weapon cards (Clarent,
// Sword of Peace, m31WVJ9F04) are seeded into player 2's own banishment ("your banishment" =
// the attacking player's). Player 1 passes turn 1 (Rule 1.h forbids the true first player from
// attacking turn 1), then player 2's champion (0 printed power, bare-handed) declares a real
// attack -- same 4-action shape as the Shred to Ribbons precedent (Pass, FSM attack, target,
// decline Retaliate?) -- against a Dungeon Guide ALLY seeded onto player 1's field (LIFE
// overridden to 4), with Reaping Legacy already loaded in intent: total attack power = 0
// (champion) + 3 (printed) + 2 (Class Bonus, one per Sword regalia weapon in banishment) = 5,
// dealt as real, observable combat damage -- exactly lethal to the Dungeon Guide (which then
// dies and moves to the graveyard) vs. only 3 damage (and survival) if the Class Bonus never
// applied.
// Notes carried over from the original definition:
//   - Lorraine, Wandering Warrior (WARRIOR) -- Class Bonus source
//   - Dungeon Guide's printed LIFE (3) is overridden to 4 (same potion_animate_life trick as
//   - the Shred to Ribbons precedent) so it's lethal ONLY with the +2 Class Bonus applied
//   - (0 champion + 3 printed + 2 bonus = 5 >= 4) and would survive without it (0 + 3 = 3 < 4)
//   - -- isolating the bonus specifically, rather than a target any base attack would also kill.
//   - Dungeon Guide ALLY, LIFE overridden to 4 -- attack target
//   - Reaping Legacy, seeded directly into Intent
//   - Clarent, Sword of Peace (REGALIA/WEAPON, SWORD)
//   - Clarent, Sword of Peace (REGALIA/WEAPON, SWORD)
//   - ends turn 1
//   - champion declares the real attack
//   - target player 1's Dungeon Guide
//   - decline Retaliate?
gaScenario('reaping-legacy-class-bonus-sword-banishment')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Wandering Warrior', player: 2)
    ->seed('myField', 'Dungeon Guide', properties: ['Counters' => ['potion_animate_life' => 4]])
    ->seed('myIntent', 'Reaping Legacy', player: 2, properties: ['Controller' => 2, 'Owner' => 2])
    ->seed('myBanish', 'Clarent, Sword of Peace', player: 2)
    ->seed('myBanish', 'Clarent, Sword of Peace', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'theirField-1')->answer(1, '-')
    ->tested('Reaping Legacy')
    ->expectDqEmpty()
    ->expectProperty('p1Graveyard-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'Status', '2')
    ->expectDamage('p1Graveyard-0', 5, 'Reaping Legacy\'s [Class Bonus] (ObjectCurrentPower\'s per-CardID switch, case XDVIiIfKZk) adds +1 POWER per Sword regalia weapon in the attacker\'s banishment (2 here), landing 5 total damage -- exactly lethal to the LIFE-4 Dungeon Guide (which then dies and moves to the graveyard), vs. only 3 damage (and survival) if the Class Bonus never applied.')
    ->expectProperty('p1Graveyard-0', 'Counters', '{"potion_animate_life":4}')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectProperty('p2Field-0', 'Status', '1')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Graveyard-0', 'CardID', 'XDVIiIfKZk')
    ->expectProperty('p2Graveyard-0', 'TurnEffects', '[]')
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Ripples of Atrophy: [Class Bonus] Efficiency (costs LV less). Choose any amount of
// non-champion objects and put two wither counters on each ---
// The starting champion (Spirit of Water) is CardID-patched directly to Diao Chan, Idyll
// Corsage (d7l6i5thdy) -- the deck's own level-3 champion. This satisfies IsClassBonusActive
// (["CLERIC"]) for the printed "[Class Bonus] Efficiency" clause, and its own element (TERA)
// unlocks Ripples of Atrophy's TERA element requirement directly (GetChampionLineage includes
// the champion object's own CardID, so no Subcards patch is needed here, unlike the WATER
// cards which use a lower-level champion). NOTE (found, not fixed -- genuine engine bug):
// CalculateActivationReserveCost's Efficiency block (GrandArchiveSim/Custom/GameLogic.php
// ~26015, `if(isset($Efficiency_Cards[$cardID]))`) reduces the cost unconditionally whenever
// the card is merely listed in $Efficiency_Cards -- it never calls HasKeyword_Efficiency($obj)
// or otherwise checks the card's own 'conditions' (GeneratedKeywordCode.php ~2047-2062, which
// DOES gate u0yaub9dal's Efficiency on "Class Bonus"). So today Ripples of Atrophy's cost is
// reduced by the champion's level even when the champion is NOT CLERIC. This fixture uses a
// CLERIC champion regardless (matching the printed card and the real deck), so it doesn't
// depend on the bug, but the bug means this fixture cannot be used to prove the "[Class Bonus]"
// gating specifically -- only the wither-counter application (cardActivatedAbilities
// ["u0yaub9dal:0"], ~21971) is asserted as semantic here. With champion level 3, net cost is
// 6-3=3. Ripples of Atrophy is seeded directly into hand. RipplesOfAtrophyResolve
// (Custom/CardDQHandlers.php ~3896) offers an MZMAYCHOOSE loop (repeatedly choose one
// non-champion object or decline) rather than a single MZMULTICHOOSE; customDQHandlers
// ["RipplesOfAtrophyLoop"] (~3915) adds 2 wither counters per chosen object. One object is
// chosen (the opponent's Dungeon Guide) and a second eligible object (the player's own Fluffy
// Shopkeep) is deliberately left unchosen by declining the loop's next offer -- proving the
// wither counters land only on the object actually selected.
// Notes carried over from the original definition:
//   - Diao Chan, Idyll Corsage (CLERIC, level 3, TERA) -- Class Bonus + Efficiency + TERA unlock
//   - Dungeon Guide ALLY, chosen target
//   - Fluffy Shopkeep ALLY, eligible but deliberately left UNCHOSEN
//   - play Ripples of Atrophy
//   - pay reserve 1/3
//   - pay reserve 2/3
//   - pay reserve 3/3 -- stack auto-resolves (no fast cards), ability fires immediately
//   - MZMAYCHOOSE: choose the opponent's Dungeon Guide
//   - decline the loop's next offer (leaves myField-1 unchosen)
gaScenario('ripples-of-atrophy-two-wither-counters')
    ->deck(GA_DIAOCHAN_WITHER_DECK)
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Fluffy Shopkeep')
    ->seed('myHand', 'Ripples of Atrophy')
    ->play('Ripples of Atrophy')->pay(3)->target('theirField-1')->pass(1)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Graveyard-0', 'CardID', 'u0yaub9dal')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Field-1', 'Counters', '{"wither":2}', 'Chosen opponent Dungeon Guide (theirField-1) received exactly 2 wither counters')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The eligible-but-unchosen Fluffy Shopkeep (myField-1) received NO wither counters -- the MZMAYCHOOSE loop was declined for it, proving the effect only applies to objects actually chosen')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->register();

// --- Refracting Missile: Deal damage to target unit equal to the amount of
// Fractal objects you control plus 1. ---
// Refracting Missile (6ffqsuo6gb, WATER ACTION) reads
// count(ZoneSearch("myField", cardSubtypes: ["FRACTAL"])) + 1 as its damage amount
// (GeneratedMacroCode.php, customDQHandlers["6ffqsuo6gb:0:CardActivated-1"]:32970-32972).
// Hydrating Fractal (LAfJuHgUbm, WATER PHANTASIA, CLERIC/FRACTAL subtypes, no On Enter/
// passive text) is seeded directly onto the field as the single controlled Fractal object,
// so N=1 and expected damage = 2 -- deliberately non-trivial and distinguishable from a
// hypothetical always-1 stub. WATER access for playing Refracting Missile itself comes for
// free from the Spirit of Water starting champion (see shared setup notes above); no Subcards
// patch is needed since this card has no Class/Diao Chan Bonus gating.
// Notes carried over from the original definition:
//   - Hydrating Fractal -- the 1 controlled Fractal object
//   - reserve 1/3
//   - reserve 2/3
//   - reserve 3/3
//   - target opponent's champion
gaScenario('refracting-missile-fractal-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->seed('myField', 'Hydrating Fractal')
    ->seed('myHand', 'Refracting Missile')
    ->castSpell('Refracting Missile', pay: 3, target: 'theirField-0')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', '6ffqsuo6gb')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectDamage('p2Field-0', 2, 'Refracting Missile (customDQHandlers["6ffqsuo6gb:0:CardActivated-1"]) computed damage = count(Fractal objects controlled) + 1 = 1 + 1 = 2, not a flat/stub amount -- distinguishable from a hypothetical always-1 damage stub')
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":2}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->register();

// Rousing Rattle Drum: Banish this card: wake up target defending Animal or Beast ally.
// Notes carried over from the original definition:
//   - Cheerful Slime seeded BEFORE Rousing Rattle Drum so its own field index (myField-1) is
//   - stable across the Drum's own banish-as-cost (which removes a LATER index, not this one).
//   - Simulate the Slime being the current combat's defending unit (IsUnitDefending() reads
//   - these DecisionQueueController variables -- GrandArchiveSim/Custom/GameLogic.php ~21315).
//   - CombatTarget is stored from the ATTACKER's perspective, hence "theirField-1".
//   - Activate Rousing Rattle Drum's field ability (banishes itself as its own cost).
//   - MZCHOOSE: the only legal target is the defending Cheerful Slime.
gaScenario('rousing-rattle-drum-banish-wake-defender')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->seed('myField', 'Cheerful Slime', properties: ['Status' => 1])
    ->seed('myField', 'Rousing Rattle Drum')
    ->dqVariables(['CombatTarget' => 'theirField-1', 'CombatAttackerPlayer' => 2])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Rousing Rattle Drum')
    ->register();

// --- Return to the Archive (aIbBhTilEN): You may sacrifice a regalia. If you do, recover 2 and draw a card. ---
gaScenario('return-to-archive-sac-recover-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Poisoned Dagger
# Main
4 Return to the Archive
10 Dungeon Guide
DECK
    )
    ->champion(damage: 5)
    ->seed('myField', 'Poisoned Dagger')
    ->playSlot(2)->pay(2)->target('myField-1')->passBoth()
    ->tested('Return to the Archive')
    ->register();

// --- Rhongomiant, Grove's Spire: printed Spellshroud ("Objects with spellshroud can't be targeted by spells.") ---
// Rhongomiant, Grove's Spire (clS3E0HrZL, EXALTED/NORM WARRIOR REGALIA/WEAPON, memory cost 2):
// "Spellshroud (Objects with spellshroud can't be targeted by spells.)" followed by two
// [Mordred Bonus] clauses -- the Spellshroud is the card's first, UNCONDITIONAL keyword. It used to
// be unimplemented: HasSpellshroud() (Custom/GameLogic.php) only recognised printed Spellshroud for
// a hard-coded list of card IDs and through HasKeyword_Spellshroud(), which is never generated
// (function_exists is false), and clS3E0HrZL was in neither, so FilterSpellshroudTargets() let a
// spell target it (fixed: HasSpellshroud() now returns true for clS3E0HrZL; the formerly pinned
// fixture rhongomiant-spellshroud-spell-can-still-target-it is now ...-opponent-spell-finds-no-target).
// Rules wording (.claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords > Spellshroud): "This
// can't be targeted by spells" -- ANY spell, the holder's controller's own included (the rule has no
// "opponent's" qualifier); non-targeting effects are unaffected.
//
// 1) Player 2 plays Fracturize ("Target item or weapon becomes a Cleric Fractal phantasia ...") on
// its own turn while Rhongomiant is the only item/weapon on the board: the spell finds no legal
// target, resolves without a prompt and Rhongomiant is untouched.
// Notes carried over from the original definition:
//   - Rhongomiant, Grove's Spire -> p1 field-1
//   - Fracturize (WATER spell) -> p2 myHand-7
//   - end player 1 turn 1
//   - player 2 passes the end-of-turn window (its fast Fracturize keeps windows open)
//   - player 2 passes the beginning-of-turn window
//   - player 2 plays Fracturize
gaScenario('rhongomiant-spellshroud-opponent-spell-finds-no-target')
    ->deck($mordredDeck)
    ->seed('myField', 'Rhongomiant, Grove\'s Spire')
    ->seed('myHand', 'Fracturize', player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Fracturize', 2)->pay(2, 2)
    ->tested('Rhongomiant, Grove\'s Spire')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'Rhongomiant (printed Spellshroud) was not targetable by the opposing Fracturize: no override was applied, it keeps its weapon identity and durability')
    ->expectProperty('p1Field-1', 'CardID', 'clS3E0HrZL', 'Rhongomiant is still on player 1\'s field')
    ->expectProperty('p2Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize still resolved (into player 2\'s graveyard) -- it simply found no legal target')
    ->expectDqEmpty()
    ->register();

// 2) Same, but player 1 also controls a Mirrordepth's Blade (another weapon). Fracturize's target
// prompt offers ONLY the Blade (theirField-2); answering with Rhongomiant (theirField-1) is refused
// (expectFailure), and the legal Blade answer then resolves: the Blade becomes a phantasia while
// Rhongomiant keeps its card type and abilities.
// Notes carried over from the original definition:
//   - Rhongomiant, Grove's Spire -> p1 field-1
//   - Mirrordepth's Blade (the only legal Fracturize target) -> p1 field-2
//   - Fracturize -> p2 myHand-7
//   - end player 1 turn 1
//   - end-of-turn window
//   - beginning-of-turn window
//   - player 2 plays Fracturize
//   - the Mirrordepth's Blade is the legal target
gaScenario('rhongomiant-spellshroud-opponent-spell-offered-only-other-weapon')
    ->deck($mordredDeck)
    ->seed('myField', 'Rhongomiant, Grove\'s Spire')
    ->seed('myField', 'Mirrordepth\'s Blade')
    ->seed('myHand', 'Fracturize', player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Fracturize', 2)->pay(2, 2)
    ->rejects(2, 'theirField-1', 'Rhongomiant (Spellshroud) is not a legal target for the opposing Fracturize')
    ->answer(2, 'theirField-2')
    ->tested('Rhongomiant, Grove\'s Spire')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'The refused answer left Rhongomiant untouched (Spellshroud: the opposing spell cannot target it)', step: 7)
    ->expectProperty('p1Field-2', 'Counters', '{"durability":2}', 'The refused answer did not apply Fracturize to the Blade either (prompt still pending)', step: 7)
    ->expectProperty('p1Field-2', 'Counters', '{"durability":2,"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'The only legal target, Mirrordepth\'s Blade, became a Cleric Fractal phantasia that lost its abilities')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'Rhongomiant is unaffected by the resolved Fracturize')
    ->expectProperty('p2Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 2\'s graveyard')
    ->expectDqEmpty()
    ->register();

// 3) Own spells are blocked too (literal rules reading: Spellshroud stops ALL spells). Player 1 plays
// its own Fracturize on its own turn with Rhongomiant and a Mirrordepth's Blade on its field: only
// the Blade is offered, targeting Rhongomiant (myField-1) is refused.
// Notes carried over from the original definition:
//   - Rhongomiant -> p1 field-1
//   - Mirrordepth's Blade -> p1 field-2
//   - Fracturize -> p1 myHand-7
//   - player 1 plays its own Fracturize
//   - the Mirrordepth's Blade is the legal target
gaScenario('rhongomiant-spellshroud-own-spell-cannot-target-it')
    ->deck($mordredDeck)
    ->seed('myField', 'Rhongomiant, Grove\'s Spire')
    ->seed('myField', 'Mirrordepth\'s Blade')
    ->seed('myHand', 'Fracturize')
    ->castSpell('Fracturize', pay: 2)
    ->rejects(1, 'myField-1', 'Spellshroud also stops the controller\'s own spell from targeting Rhongomiant')
    ->target('myField-2')
    ->tested('Rhongomiant, Grove\'s Spire')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'The refused answer left Rhongomiant untouched: Spellshroud stops the controller\'s own spell too (literal rules reading)', step: 4)
    ->expectProperty('p1Field-2', 'Counters', '{"durability":2}', 'The refused answer did not apply Fracturize to the Blade either (prompt still pending)', step: 4)
    ->expectProperty('p1Field-2', 'Counters', '{"durability":2,"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'The only legal target, Mirrordepth\'s Blade, became a Cleric Fractal phantasia')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'Rhongomiant is unaffected by player 1\'s own resolved Fracturize')
    ->expectProperty('p1Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// 4) A NON-targeting spell still affects the holder. Player 2's champion is patched with a TERA
// lineage (Kongming, Fel Eidolon) so Heartsong Reclamation (TERA action: "Destroy all regalia you
// don't control.") can be played for real; it never targets, so Rhongomiant is destroyed into its
// owner's graveyard despite Spellshroud.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock for player 2
//   - Rhongomiant -> p1 field-1
//   - Heartsong Reclamation -> p2 myHand-7
//   - end player 1 turn 1
//   - end-of-turn window
//   - beginning-of-turn window
//   - player 2 plays Heartsong Reclamation
gaScenario('rhongomiant-spellshroud-non-targeting-spell-still-destroys-it')
    ->deck($mordredDeck)
    ->champion(lineage: ['Kongming, Fel Eidolon'], player: 2)
    ->seed('myField', 'Rhongomiant, Grove\'s Spire')
    ->seed('myHand', 'Heartsong Reclamation', player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Heartsong Reclamation', 2)->pay(8, 2)
    ->tested('Rhongomiant, Grove\'s Spire')
    ->register();

// 5) Boundary: the Spellshroud is NOT gated by the [Mordred Bonus]. Player 1's champion is patched to
// Tristan, Shadowdancer (not a Mordred), so both Mordred clauses are off, yet the opposing
// Fracturize still finds no legal target.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (UMBRA, not a Mordred)
//   - Rhongomiant -> p1 field-1
//   - Fracturize -> p2 myHand-7
//   - end player 1 turn 1
//   - end-of-turn window
//   - beginning-of-turn window
//   - player 2 plays Fracturize
gaScenario('rhongomiant-spellshroud-holds-without-mordred-champion')
    ->deck($mordredDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Water'])
    ->seed('myField', 'Rhongomiant, Grove\'s Spire')
    ->seed('myHand', 'Fracturize', player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Fracturize', 2)->pay(2, 2)
    ->tested('Rhongomiant, Grove\'s Spire')
    ->expectProperty('p1Field-0', 'CardID', 'he6kd7hocc', 'Precondition: player 1\'s champion is Tristan, Shadowdancer (not a Mordred), so both [Mordred Bonus] clauses are off')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'Rhongomiant is still untargetable by the opposing Fracturize: the printed Spellshroud is not gated by the Mordred Bonus')
    ->expectProperty('p2Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved without a target')
    ->expectDqEmpty()
    ->register();

// 6) Boundary: Spellshroud is an ability, so a Rhongomiant that has lost all abilities (the
// NO_ABILITIES override, seeded here as setup state) loses it and is targetable again: the opposing
// Fracturize's prompt offers theirField-1 and choosing it succeeds.
// Notes carried over from the original definition:
//   - Rhongomiant that has lost all abilities -> p1 field-1
//   - Fracturize -> p2 myHand-7
//   - end player 1 turn 1
//   - end-of-turn window
//   - beginning-of-turn window
//   - player 2 plays Fracturize
//   - target the ability-less Rhongomiant
gaScenario('rhongomiant-spellshroud-lost-abilities-is-targetable')
    ->deck($mordredDeck)
    ->seed('myField', 'Rhongomiant, Grove\'s Spire', properties: ['Counters' => ['durability' => 3, '_overrides' => ['NO_ABILITIES' => true]]])
    ->seed('myHand', 'Fracturize', player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Fracturize', 2)->pay(2, 2)->answer(2, 'theirField-1')
    ->tested('Rhongomiant, Grove\'s Spire')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3,"_overrides":{"NO_ABILITIES":true,"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'BOUNDARY: a Rhongomiant that has lost all abilities (NO_ABILITIES seeded in setup) no longer has Spellshroud: the opposing Fracturize offered it and the choice succeeded')
    ->expectProperty('p2Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 2\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Radiant Origin of Assassin/Cleric/Guardian/Mage/Ranger/Tamer/Warrior: printed Omnishroud -- an opposing
// spell cannot target it ---
// Radiant Origin of Assassin/Cleric/Guardian/Mage/Ranger/Tamer/Warrior (T5ZEoIRdZr, XSgcay9ZB7, yT32RI6pqt,
// dOPqsWYMCQ, tp7eVOsAHU, zS0TJ97QSV, lqILsIDHNc, PHANTASIA): "Omnishroud". Omnishroud means "This can't be
// targeted by activations, materializations, or triggered abilities" (comprehensive rules, Keywords >
// Omnishroud 1); a spell is a card activation, so an Omnishroud object can't be targeted by spells --
// Spellshroud and Omnishroud are redundant with each other (Omnishroud 3a). The engine already applied that to
// the OMNISHROUD turn effect and Argus, All-Seeing Giant, but printed Omnishroud on these cards was never
// recognised: HasSpellshroud() only knew a hard-coded card-ID list.
// Player 2 controls the holders plus a Ominous Shadow; player 1 plays Disenchant ("Destroy target phantasia",
// a Spell): the target prompt offers ONLY the Ominous Shadow, answering with each holder is refused
// (expectFailure) and the legal answer then resolves.
// Notes carried over from the original definition:
//   - Radiant Origin of Assassin -> p2 field-1
//   - Radiant Origin of Cleric -> p2 field-2
//   - Radiant Origin of Guardian -> p2 field-3
//   - Radiant Origin of Mage -> p2 field-4
//   - Radiant Origin of Ranger -> p2 field-5
//   - Radiant Origin of Tamer -> p2 field-6
//   - Radiant Origin of Warrior -> p2 field-7
//   - Ominous Shadow -> p2 field-8
//   - Disenchant -> p1 myHand-7
//   - player 1 plays Disenchant
//   - the Ominous Shadow is the legal target
gaScenario('radiant-origin-trials-printed-omnishroud-spell-cannot-target-them')
    ->deck($mordredDeck)
    ->seed('myField', 'Radiant Origin of Assassin', player: 2)
    ->seed('myField', 'Radiant Origin of Cleric', player: 2)
    ->seed('myField', 'Radiant Origin of Guardian', player: 2)
    ->seed('myField', 'Radiant Origin of Mage', player: 2)
    ->seed('myField', 'Radiant Origin of Ranger', player: 2)
    ->seed('myField', 'Radiant Origin of Tamer', player: 2)
    ->seed('myField', 'Radiant Origin of Warrior', player: 2)
    ->seed('myField', 'Ominous Shadow', player: 2)
    ->seed('myHand', 'Disenchant')
    ->castSpell('Disenchant', pay: 2)
    ->rejects(1, 'theirField-1', 'Radiant Origin of Assassin (T5ZEoIRdZr) has spellshroud: not a legal target for the opposing Disenchant')
    ->rejects(1, 'theirField-2', 'Radiant Origin of Cleric (XSgcay9ZB7) has spellshroud: not a legal target for the opposing Disenchant')
    ->rejects(1, 'theirField-3', 'Radiant Origin of Guardian (yT32RI6pqt) has spellshroud: not a legal target for the opposing Disenchant')
    ->rejects(1, 'theirField-4', 'Radiant Origin of Mage (dOPqsWYMCQ) has spellshroud: not a legal target for the opposing Disenchant')
    ->rejects(1, 'theirField-5', 'Radiant Origin of Ranger (tp7eVOsAHU) has spellshroud: not a legal target for the opposing Disenchant')
    ->rejects(1, 'theirField-6', 'Radiant Origin of Tamer (zS0TJ97QSV) has spellshroud: not a legal target for the opposing Disenchant')
    ->rejects(1, 'theirField-7', 'Radiant Origin of Warrior (lqILsIDHNc) has spellshroud: not a legal target for the opposing Disenchant')
    ->target('theirField-8')
    ->tested('Radiant Origin of Assassin', 'Radiant Origin of Cleric', 'Radiant Origin of Guardian', 'Radiant Origin of Mage', 'Radiant Origin of Ranger', 'Radiant Origin of Tamer', 'Radiant Origin of Warrior')
    ->register();

// --- Rhongomiant, Grove's Spire: [Mordred Bonus] +4 POWER while an opponent has influence eight or more ---
// Rhongomiant, Grove's Spire (clS3E0HrZL, REGALIA/WEAPON, printed power 2): "[Mordred Bonus] As long
// as an opponent has influence eight or more, CARDNAME gets +4 POWER. (A player's influence is
// equal to the total amount of cards in their hand and memory.)" Player 1's champion is Mordred,
// Burnished Avenger and Rhongomiant is materialized for real (2 memory cards) in the turn-3
// materialize phase; player 2 holds 8 cards in hand (7 + its turn draw), so the weapon's computed
// power is 2 + 4 = 6.
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary (level 3, EXALTED): Mordred Bonus active and the Exalted element enabled (Rhongomiant is an Exalted card)
//   - materialize memory cost 1/2
//   - materialize memory cost 2/2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Rhongomiant, Grove's Spire (memory cost 2)
gaScenario('rhongomiant-mordred-bonus-plus-four-power-at-influence-eight')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-6')
    ->tested('Rhongomiant, Grove\'s Spire')
    ->register();

// --- Rhongomiant, Grove's Spire: no +4 POWER with only seven cards of influence (boundary) ---
// Rhongomiant (clS3E0HrZL): the bonus needs influence EIGHT or more. Player 2's hand is emptied and
// refilled with six cards, so after its turn-2 draw its influence (hand + memory) is exactly 7:
// the Mordred Bonus is active but the weapon keeps its printed power 2.
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary (level 3, EXALTED): Mordred Bonus active and the Exalted element enabled (Rhongomiant is an Exalted card)
//   - materialize memory cost 1/2
//   - materialize memory cost 2/2
//   - player 2's hand emptied...
//   - ...and refilled with six cards (6 + 1 turn draw = influence 7)
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Rhongomiant, Grove's Spire (memory cost 2)
gaScenario('rhongomiant-no-power-bonus-at-influence-seven')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->emptyZone('myHand', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->endTurn(2)->target('myMaterial-6')
    ->tested('Rhongomiant, Grove\'s Spire')
    ->register();

// --- Rhongomiant, Grove's Spire: the +4 POWER needs the Mordred Bonus (champion must be Mordred) ---
// Rhongomiant (clS3E0HrZL): both clauses are "[Mordred Bonus]". With a non-Mordred champion
// (Tristan, Shadowdancer, whose UMBRA lineage also enables the Exalted element) the weapon (materialized for real, memory cost 2) keeps its printed power 2 even though
// player 2 holds 8 cards (influence 8).
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (UMBRA, not a Mordred): an advanced element is enabled, so the Exalted element is too
//   - materialize memory cost 1/2
//   - materialize memory cost 2/2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Rhongomiant, Grove's Spire (memory cost 2)
gaScenario('rhongomiant-no-power-bonus-without-mordred-champion')
    ->deck($mordredDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-6')
    ->tested('Rhongomiant, Grove\'s Spire')
    ->register();

// --- Rhongomiant, Grove's Spire: [Mordred Bonus] whenever a durability counter is removed, you may return a card from memory to hand ---
// Rhongomiant, Grove's Spire (clS3E0HrZL, durability 3): "[Mordred Bonus] Whenever a durability
// counter is removed from CARDNAME, you may return a card from your memory to your hand." Player
// 1's champion is Mordred, Fated Luminary; Rhongomiant is materialized for real (2 seeded memory
// cards), then a Dungeon Guide is played (its 3 reserve payments put three cards into memory --
// the memory zone is otherwise emptied by the recollection phase) and the champion attacks player
// 2's champion with Rhongomiant (weapon attack): in the damage step a durability counter is
// removed (3 -> 2) and the trigger offers the memory cards; answering with one returns it to
// player 1's hand.
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary: Mordred Bonus + Exalted
//   - materialize memory cost 1/2
//   - materialize memory cost 2/2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Rhongomiant, Grove's Spire (memory cost 2)
//   - decline the Dungeon Guide's On Enter
//   - the champion attacks
//   - with Rhongomiant as the weapon
//   - targeting player 2's champion
//   - durability counter removed: return this memory card to hand
gaScenario('rhongomiant-durability-removed-return-memory-card-to-hand')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-6')
    ->playSlot(0)->pay(3)->answer(1, 'NO')
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->target('myMemory-0')
    ->tested('Rhongomiant, Grove\'s Spire')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'The weapon attack removed exactly one durability counter (3 -> 2)')
    ->expectDamage('p2Field-0', 6, 'Rhongomiant hit for 2 + 4 (influence 8) = 6')
    ->expectZoneCount('myMemory', 2, 1, 'One of the three memory cards was returned to hand')
    ->expectZoneCount('myHand', 5, 1, 'Hand 5 = 7 + draw - Dungeon Guide - 3 payments + 1 card returned from memory')
    ->expectDqEmpty()
    ->register();

// --- Rhongomiant, Grove's Spire: returning a card from memory on durability loss is optional (declined) ---
// Rhongomiant (clS3E0HrZL): "you MAY return a card from your memory to your hand". Same flow as
// rhongomiant-durability-removed-return-memory-card-to-hand, but the optional choice is answered PASS: the
// durability counter is still removed (3 -> 2) while all three memory cards stay in memory.
// Notes carried over from the original definition:
//   - Mordred, Fated Luminary: Mordred Bonus + Exalted
//   - materialize memory cost 1/2
//   - materialize memory cost 2/2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Rhongomiant, Grove's Spire (memory cost 2)
//   - decline the Dungeon Guide's On Enter
//   - the champion attacks
//   - with Rhongomiant as the weapon
//   - targeting player 2's champion
//   - decline to return a memory card
gaScenario('rhongomiant-durability-removed-decline-memory-return')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Fated Luminary', lineage: ['Mordred, Flawless Blade', 'Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-6')
    ->playSlot(0)->pay(3)->answer(1, 'NO')
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->pass(1)
    ->tested('Rhongomiant, Grove\'s Spire')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'The durability counter was still removed (3 -> 2)')
    ->expectZoneCount('myMemory', 3, 1, 'All three memory cards stayed in memory')
    ->expectZoneCount('myHand', 4, 1, 'Hand 4 = 7 + draw - Dungeon Guide - 3 payments: nothing was returned')
    ->expectDqEmpty()
    ->register();

// --- Rhongomiant, Grove's Spire: the durability trigger needs the Mordred Bonus ---
// Rhongomiant (clS3E0HrZL): the memory-return clause is "[Mordred Bonus]". With a non-Mordred
// champion (Tristan, Shadowdancer, whose UMBRA lineage enables the Exalted element) the weapon
// still loses its durability counter in the attack (3 -> 2) but no memory card is offered: all
// three memory cards stay in memory and the attack finishes with nothing pending.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (UMBRA, not a Mordred)
//   - materialize memory cost 1/2
//   - materialize memory cost 2/2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Rhongomiant, Grove's Spire (memory cost 2)
//   - decline the Dungeon Guide's On Enter
//   - the champion attacks
//   - with Rhongomiant as the weapon
//   - targeting player 2's champion
gaScenario('rhongomiant-durability-removed-no-return-without-mordred-champion')
    ->deck($mordredDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-6')
    ->playSlot(0)->pay(3)->answer(1, 'NO')
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->tested('Rhongomiant, Grove\'s Spire')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'The durability counter was removed (3 -> 2) as part of the attack')
    ->expectDamage('p2Field-0', 2, 'Without the Mordred Bonus Rhongomiant hits for its printed 2')
    ->expectZoneCount('myMemory', 3, 1, 'No memory card was offered or returned')
    ->expectZoneCount('myHand', 4, 1, 'Hand 4 = 7 + draw - Dungeon Guide - 3 payments')
    ->expectDqEmpty()
    ->register();

// --- Redirect Orbit: declining a further shuffle still draws into memory for the cards already shuffled (dontSkipOnPass regression) ---
// Redirect Orbit (Tst4WbM6O8): "Shuffle any amount of cards from your hand and/or memory into your deck. Then draw that many cards into your memory." Stopping early (declining the 'Shuffle a card from hand or memory into deck?' prompt) must still shuffle the deck and draw one card into memory per card already shuffled in.
// Regression: RedirectOrbitChoose() queued its paired CUSTOM 'RedirectOrbitStep|N' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its PASS branch (ShuffleZone + DrawIntoMemory for the shuffled count) never ran: a card was shuffled away and nothing was drawn. (redirect-orbit-shuffle-draw answers the prompt with '-', which is not 'PASS' and so bypassed the skip; the real client sends PASS.)
// Same setup as redirect-orbit-shuffle-draw (Arisanna, Astral Zenith unlocks ASTRA); one card is shuffled in, then the next offer is declined with PASS.
// Notes carried over from the original definition:
//   - Arisanna, Astral Zenith - ASTRA unlock
//   - reserve 1/2
//   - reserve 2/2
//   - shuffle one card from hand into the deck
//   - decline shuffling another card
gaScenario('redirect-orbit-decline-still-draws-into-memory')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Astral Zenith')
    ->seed('myHand', 'Redirect Orbit')
    ->castSpell('Redirect Orbit', pay: 3, window: 'me')
    ->expectZoneCount('myMemory', 3, 1, 'One card drawn into memory for the one card shuffled in (memory 2 + 1)')
    ->expectZoneCount('myHand', 4, 1, 'Hand shrank by the one card shuffled away')
    ->expectZoneCount('myDeck', 5, 1, 'Deck: 5 + 1 shuffled in - 1 drawn into memory = 5')
    ->expectDqEmpty()
    ->register();

// top five, top to bottom: Fairy Whispers, Dungeon Guide (ally), Fluffy Shopkeep (ally), Fairy Whispers, Dungeon Guide (ally)
// Notes carried over from the original definition:
//   - reveal the 5th card (Dungeon Guide) into hand
gaScenario('recruitment-officer-foster-look-five-reveal-deep-ally-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Tonoris, Lone Mercenary')
    ->seed('myField', 'Recruitment Officer')
    ->deckTop(['Fairy Whispers', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide'])
    ->endTurn()
    ->endTurn(2)->target('myTempZone-4')
    ->tested('Recruitment Officer')
    ->register();

gaScenario('recruitment-officer-foster-look-five-decline-bottoms-all-five')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Tonoris, Lone Mercenary')
    ->seed('myField', 'Recruitment Officer')
    ->deckTop(['Fairy Whispers', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide'])
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Recruitment Officer')
    ->register();

// no ally among the top five: no prompt, all five go to the bottom
gaScenario('recruitment-officer-foster-look-five-no-ally-all-five-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Tonoris, Lone Mercenary')
    ->seed('myField', 'Recruitment Officer')
    ->deckTop(['Fairy Whispers', 'Fairy Whispers', 'Fairy Whispers', 'Fairy Whispers', 'Fairy Whispers'])
    ->endTurn()
    ->endTurn(2)
    ->tested('Recruitment Officer')
    ->register();

// PLAYER 2 seating (perspective check): player 2's first turn has no Foster processing; its SECOND recollection phase runs
// the prompt. The stack sits under player 2's first-turn draw (a filler Dungeon Guide); its second draw happens after
// recollection.
gaScenario('recruitment-officer-player-two-foster-look-five-reveal-deep-ally-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Tonoris, Lone Mercenary', player: 2)
    ->seed('myField', 'Recruitment Officer', player: 2)
    ->deckTop(['Dungeon Guide', 'Fairy Whispers', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Dungeon Guide'], player: 2)
    ->endTurn()
    ->endTurn(2)
    ->endTurn()->answer(2, 'myTempZone-4')
    ->tested('Recruitment Officer')
    ->register();

// hand: four payers + Embers; the four payers go to memory, so influence = 0 hand + 4 memory = 4 -> draw a card into memory
gaScenario('restoring-embers-influence-four-recovers-and-draws-into-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(damage: 6)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Restoring Embers')
    ->playSlot(4)->pay(4)
    ->tested('Restoring Embers')
    ->register();

// one extra card left in hand: influence = 1 hand + 4 memory = 5 -> recover only
gaScenario('restoring-embers-influence-five-recovers-without-drawing')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(damage: 6)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Restoring Embers')
    ->playSlot(5)->pay(4)
    ->tested('Restoring Embers')
    ->register();

// graveyard: Season's End (reserve 10) and Argus, All-Seeing Giant (reserve 10): total 20 >= 15
gaScenario('rolling-chorus-banish-two-total-reserve-twenty-draws-into-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myGraveyard', 'Season\'s End')
    ->seed('myGraveyard', 'Argus, All-Seeing Giant')
    ->seed('myHand', 'Rolling Chorus')
    ->play('Rolling Chorus')->pay(1)->answer(1, 'A')->answer(1, 'myGraveyard-0&myGraveyard-1')
    ->expectZoneCount('myBanish', 2, 1, 'Both chosen graveyard cards were banished')
    ->expectZoneCount('myGraveyard', 1, 1, 'Only Rolling Chorus itself remains in the graveyard')
    ->expectZoneCount('myMemory', 2, 1, 'One reserve payment plus the card drawn into memory (total reserve cost 20 >= 15)')
    ->expectZoneCount('myDeck', 12, 1, 'One card left the deck')
    ->expectDqEmpty()
    ->register();

// graveyard: Dungeon Guide (3) and Fluffy Shopkeep (3): total 6 < 15
gaScenario('rolling-chorus-banish-two-total-reserve-six-does-not-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Fluffy Shopkeep')
    ->seed('myHand', 'Rolling Chorus')
    ->play('Rolling Chorus')->pay(1)->answer(1, 'A')->answer(1, 'myGraveyard-0&myGraveyard-1')
    ->expectZoneCount('myBanish', 2, 1, 'Both chosen graveyard cards were banished')
    ->expectZoneCount('myGraveyard', 1, 1, 'Only Rolling Chorus itself remains in the graveyard')
    ->expectZoneCount('myMemory', 1, 1, 'Only the reserve payment: total reserve cost 6 < 15, no draw')
    ->expectZoneCount('myDeck', 13, 1, 'No card was drawn')
    ->expectDqEmpty()
    ->register();

gaScenario('reduce-to-ash-refuses-memory-only-item-destroys-low-reserve-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Reduce to Ash')
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->castSpell('Reduce to Ash', pay: 3)
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->target('theirField-2')
    ->register();

// both opponent items were seeded this turn ("entered the field this turn" holds); only Forest Cake (reserve 2 <= 3) is legal
gaScenario('rapid-combustion-refuses-memory-only-item-destroys-low-reserve-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Rapid Combustion')
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->castSpell('Rapid Combustion', pay: 4)
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->target('theirField-2')
    ->register();

// Reveal the Hidden (rHccTUUWou): "[Level 1+] Draw a card."
// Notes carried over from the original definition:
//   - RANGER class, level 1
//   - Reveal the Hidden -> myHand-7
gaScenario('reveal-the-hidden-level-1-draws-a-card')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'RANGER'], 'level' => 1]])
    ->seed('myHand', 'Reveal the Hidden')
    ->castSpell('Reveal the Hidden', pay: 2)
    ->expectZoneCount('myDeck', 12, 1, 'The [Level 1+] draw happened')
    ->expectZoneCount('myHand', 6, 1, 'Eight cards, minus Reveal the Hidden, minus two paid, plus one drawn')
    ->expectDqEmpty()
    ->register();

// Reckless Conversion (gJ2dsgywEs): "Draw two cards into your memory then banish four cards at random from it." The generated body picked the cards with PHP's rand(), which no replay or undo can
// reproduce (the same action from the same state banished different cards each time); native randomness is now seeded from the game state before every action, like EngineRandomInt().
// Notes carried over from the original definition:
//   - Rai, Storm Seer (ARCANE Mage): the spell's element and class match (the [Class Bonus] returns the surviving memory to hand)
//   - Dungeon Guide -> myMemory-0
//   - Fluffy Shopkeep -> myMemory-1
//   - Baby Red Slime -> myMemory-2
//   - Cell Handler -> myMemory-3
//   - Fast Cure -> myMemory-4
//   - Reckless Conversion -> myHand-7
gaScenario('reckless-conversion-banishes-four-random-memory-cards-reproducibly')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Rai, Storm Seer')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myMemory', 'Baby Red Slime')
    ->seed('myMemory', 'Cell Handler')
    ->seed('myMemory', 'Fast Cure')
    ->seed('myHand', 'Reckless Conversion')
    ->play('Reckless Conversion')
    ->expectZoneCount('myBanish', 4, 1, 'Four of the seven memory cards were banished')
    ->expectZoneCount('myMemory', 0, 1, 'The [Class Bonus] returned the three survivors to hand')
    ->expectZoneCount('myHand', 10, 1, 'Eight cards, minus Reckless Conversion, plus the three survivors')
    ->expectZoneCount('myDeck', 11, 1, 'Two cards were drawn into memory')
    ->expectDqEmpty()
    ->register();

// Regal Inquisition (KVbuQJyWsU): "Look at target opponent's hand and memory. Discard any amount of cards from among them. For each card discarded this way, that opponent reveals the top card of their deck and puts it into
// their hand." The opponent's cards are staged in the temp zone to choose from; declining (PASS) skipped RegalInquisitionDiscard, which clears them, so copies of the opponent's hand and memory stayed in the temp zone.
// Notes carried over from the original definition:
//   - CLERIC class; Mordred, Fated Luminary in the lineage unlocks EXALTED
//   - Regal Inquisition -> myHand-7
gaScenario('regal-inquisition-declined-discard-clears-the-staged-copies')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Mordred, Fated Luminary'], classes: 'CLERIC')
    ->seed('myHand', 'Regal Inquisition')
    ->castSpell('Regal Inquisition', pay: 2, window: 'me')
    ->expectZoneCount('myTempZone', 0, 1, 'The staged copies of the opponent\'s hand and memory were cleared')
    ->expectZoneCount('theirHand', 7, 1, 'Nothing was discarded from the opponent\'s hand')
    ->expectDqEmpty()
    ->register();

// Ranger Boots (fbs9qzo3f6): "[Class Bonus] [REST], Banish Ranger Boots: Your champion becomes distant."
// Notes carried over from the original definition:
//   - Ranger Boots -> myField-1 (Hindered: enters rested)
gaScenario('ranger-boots-banish-makes-your-champion-distant')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'RANGER')
    ->seed('myField', 'Ranger Boots')
    ->patch('myField-1', ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Ranger Boots')
    ->register();

// Refluxal Ribbon (vm4xg2hedp): "(2), Banish Refluxal Ribbon: Load target Aethercharge card from your graveyard into an Aetherwing weapon you control."
// Notes carried over from the original definition:
//   - Salamander's Breath (Aetherwing weapon) -> myField-1
//   - Refluxal Ribbon -> myField-2
//   - Aether's Embrace (Aethercharge) -> myGraveyard-0
gaScenario('refluxal-ribbon-pays-two-banishes-itself-and-loads-an-aethercharge-from-the-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myField', 'Refluxal Ribbon')
    ->seed('myGraveyard', 'Aether\'s Embrace')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->pay(2)->target('myGraveyard-0')
    ->tested('Refluxal Ribbon')
    ->register();

// Reluctant Breath (xfheZavYZm): "[REST], Banish Reluctant Breath: As a Spell, return target ally you don't control to its owner's memory. Activate this ability only if an opponent controls at least two more allies than you."
// Notes carried over from the original definition:
//   - Reluctant Breath -> myField-1
//   - Hindered: enters rested, so wake it
//   - theirField-1
//   - theirField-2
gaScenario('reluctant-breath-returns-an-opposing-ally-to-its-owners-memory-when-outnumbered')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Reluctant Breath')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Fluffy Shopkeep', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->tested('Reluctant Breath')
    ->register();

// Notes carried over from the original definition:
//   - Reluctant Breath -> myField-1
//   - Hindered: enters rested, so wake it
//   - only one opposing ally: not two more than you
gaScenario('reluctant-breath-cannot-activate-unless-outnumbered-by-two')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Reluctant Breath')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Reluctant Breath')
    ->expectZoneCount('myField', 2, 1, 'Reluctant Breath stays on the field: the activation is refused')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->expectZoneCount('theirField', 2, 1, 'The opposing ally is untouched')
    ->expectDqEmpty()
    ->register();

// Razor Broadhead (si9ux3ak6o) / Frozen Quill (iqcknwa2vl): "[REST]: Load CARDNAME into target unloaded Bow weapon you control."
// Notes carried over from the original definition:
//   - Sojourner's Hunt (a Bow) -> myField-1
//   - Razor Broadhead -> myField-2
gaScenario('razor-broadhead-rest-loads-itself-into-an-unloaded-bow')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Sojourner\'s Hunt')
    ->seed('myField', 'Razor Broadhead')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Razor Broadhead')
    ->register();

gaAbilitySandbox('resonantech-module-ability-1-pays-its-printed-cost', 'ResonanTech Module', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'Top=em6eEh9q8y;Bottom=')->pass(1)
    ->tested('ResonanTech Module')
    ->register();

gaAbilitySandbox('revoker-bell-ability-1-pays-its-printed-cost', 'Revoker Bell', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myGraveyard-0')->pass(1)
    ->tested('Revoker Bell')
    ->register();

gaAbilitySandbox('revitalizer-x-ultra-ability-1-pays-its-printed-cost', 'Revitalizer X Ultra', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Revitalizer X Ultra')
    ->register();

gaAbilitySandbox('rapid-deployment-nexus-ability-1-pays-its-printed-cost', 'Rapid Deployment Nexus', classes: 'GUARDIAN', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Rapid Deployment Nexus')
    ->register();

gaAbilitySandbox('revealing-mesmer-ability-1-pays-its-printed-cost', 'Revealing Mesmer', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->pass(1)
    ->tested('Revealing Mesmer')
    ->register();

gaAbilitySandbox('revealing-mesmer-ability-2-pays-its-printed-cost', 'Revealing Mesmer', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pay(2)->pass(1)
    ->tested('Revealing Mesmer')
    ->register();

gaAbilitySandbox('radiant-origin-of-ranger-ability-1-pays-its-printed-cost', 'Radiant Origin of Ranger', classes: 'RANGER', cardPatch: ['Status' => 2, 'Counters' => ['training' => 6]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Radiant Origin of Ranger')
    ->expectCostFootprint(hand: 10, memory: 5, banish: 0, graveyard: 4, field: 6)
    ->register();

gaAbilitySandbox('radiant-origin-of-guardian-ability-1-pays-its-printed-cost', 'Radiant Origin of Guardian', classes: 'GUARDIAN', cardPatch: ['Status' => 2, 'Counters' => ['training' => 5]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Radiant Origin of Guardian')
    ->expectCostFootprint(hand: 10, memory: 5, banish: 0, graveyard: 4, field: 6)
    ->register();

gaAbilitySandbox('radiant-origin-of-mage-ability-1-pays-its-printed-cost', 'Radiant Origin of Mage', classes: 'MAGE', cardPatch: ['Status' => 2, 'Counters' => ['training' => 6]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)->pass(1)
    ->tested('Radiant Origin of Mage')
    ->expectCostFootprint(hand: 9, memory: 6, banish: 0, graveyard: 5, field: 5)
    ->register();

gaAbilitySandbox('radiant-origin-of-tamer-ability-1-pays-its-printed-cost', 'Radiant Origin of Tamer', classes: 'TAMER', cardPatch: ['Status' => 2, 'Counters' => ['training' => 7]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Radiant Origin of Tamer')
    ->expectCostFootprint(hand: 10, memory: 5, banish: 0, graveyard: 4, field: 6)
    ->register();

// Reverse Affliction (1bxh5xz2uz): "Banish a Curse card from your champion's lineage. If you own that card, put an omen counter on it."
gaScenario('reverse-affliction-banishes-a-curse-from-the-lineage-as-an-omen')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Ciel, Mirage\'s Grave', 'Sinistre Stab'], classes: 'GUARDIAN')
    ->seed('myHand', 'Reverse Affliction')
    ->castSpell('Reverse Affliction', pay: 1, target: 'myTempZone-0')
    ->register();

// Rhesus Eradication (KgjL9uCqm6): "Deal LV+X damage to target unit, where X is two times the amount of Elysian objects you control." The handler read $damageAmount (undefined): 0 damage.
// Dante, Hemomancer (level 3) controls one Elysian Orphan: 3 + 2 = 5 damage.
// Notes carried over from the original definition:
//   - Dante, Hemomancer (level 3 EXIA Mage) as the champion
//   - Elysian Orphan -> field-1
gaScenario('rhesus-eradication-deals-level-plus-two-per-elysian-object')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Rhesus Eradication')
    ->champion(cardName: 'Dante, Hemomancer')
    ->seed('myField', 'Elysian Orphan')
    ->castSpell('Rhesus Eradication', pay: 2, window: 'me', target: 'theirField-0')
    ->register();

// Recurring Aethercharge (MG8QoeZBXY): "[Class Bonus] (3): Load this card from your graveyard into an Aetherwing weapon you control." Salamander's Breath is the Aetherwing.
// Notes carried over from the original definition:
//   - Salamander's Breath (Aetherwing) -> myField-1
gaScenario('recurring-aethercharge-graveyard-ability-pays-three-and-loads-into-the-aetherwing')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'RANGER')
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myGraveyard', 'Recurring Aethercharge')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(3)
    ->tested('Recurring Aethercharge', 'Salamander\'s Breath')
    ->register();

// Rosewinged Hollow (6S1LLrBfBU): "[Alice Bonus] [Element Bonus] (2), Banish this card from your graveyard: Put a haunt counter on your Phantasmagoria. Then if there are six or more haunt counters on it, choose a Specter ally
// you control and it gets +2 POWER until end of turn." Phantasmagoria starts with 5 haunt counters, Vengeful Paramour is the Specter ally.
// Notes carried over from the original definition:
//   - Alice lineage, FIRE
//   - Vengeful Paramour (Specter) -> myField-1
gaScenario('rosewinged-hollow-graveyard-ability-pays-two-adds-haunt-and-buffs-a-specter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'nllCALIXDT', 'Subcards' => ['LMyKyVC2O9', 'daip7s9ztd'], 'Counters' => ['_overrides' => ['element' => 'FIRE']]])
    ->seed('myMastery', 'Phantasmagoria', properties: ['Counters' => ['haunt' => 5]])
    ->seed('myField', 'Vengeful Paramour')
    ->seed('myGraveyard', 'Rosewinged Hollow')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->target('myField-1')->pay(2)
    ->tested('Rosewinged Hollow', 'Vengeful Paramour')
    ->register();

// Multi-pick abilities whose next pick was offered right after MZMove() and before the zone was compacted: the later candidate lists were off by the cards already moved (a real card skipped, an index past the end).
// The writer now remaps pending choice lists (EngineRemapZoneForSave) and the purge re-indexes them (GameOnZoneElementSpliced, all zones). Each fixture takes the LAST card each time, the case that used to fail.
// Restorative Flame (ek7r2d7uz4): "Banish up to three fire element cards from your graveyard. For each card banished this way, recover 2." Cleric/Mage champion, FIRE.
// Notes carried over from the original definition:
//   - fire, non-fire, fire, fire -> myGraveyard-0..3
gaScenario('restorative-flame-banishes-three-fire-cards-one-by-one-and-recovers-six')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Damage' => 10, 'Counters' => ['_overrides' => ['classes' => 'CLERIC']]])
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Restorative Flame')
    ->play('Restorative Flame')->pay(2)->target('myGraveyard-3')->target('myGraveyard-2')->target('myGraveyard-0')
    ->pass(1)
    ->tested('Restorative Flame', 'Red Slime')
    ->register();
