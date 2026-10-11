<?php
/**
 * Fixtures for cards starting with 'K', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Keen Tidebinder: Class Bonus, first empower each turn gets +2 POWER until end of turn ---
// Empower() (GrandArchiveSim/Custom/GameLogic.php:18438) is a shared engine primitive many
// different cards' own abilities call into -- it's not a base action every deck can trigger,
// so this borrows Dante, Hemomancer's own innate activated ability ("(X), REST: deal X
// unpreventable damage to Dante and empower X") as the trigger, by patching the starting
// champion's CardID directly to Dante (4FtNBFaOJp) rather than the Subcards-lineage trick used
// elsewhere -- GetChampionLineage() always includes the champion object's own CardID first
// (GameLogic.php:19351-19358), so a direct CardID swap satisfies element/class checks for
// whatever the champion's OWN card needs, with no separate override required. Keen Tidebinder
// only needs to be present on the field when Empower resolves (its trigger reads the field
// directly, GameLogic.php:18478-18483, no card_activated macro of its own), so it's seeded
// directly rather than played from hand -- sidestepping its own WATER element / reserve cost
// requirements entirely. The ZmBQAOb9gj_POWER TurnEffect Empower() stamps is the assertable,
// semantic proof; the "[Class Bonus] Floating Memory" clause is the same graveyard-payment
// mechanic already proven repeatedly elsewhere (shieldroid-floating-memory etc.) and is out of
// scope here to avoid duplicating that coverage.
// Dante's ability has no phase restriction, so it's offered as a fast option at every
// opportunity window (matching aenean-frozen-shunt-end-combat's discovery) -- it's declined at
// turn 1's end-step windows and activated instead during P1's own turn-2 BeforeRecollection
// window, so that after it resolves, auto-advance continues to a stable Main phase (which never
// auto-advances without an explicit PASS) rather than cascading straight to end of turn, where
// Keen Tidebinder's "until end of turn" TurnEffect would already have expired before the
// fixture's final snapshot is captured.
// Notes carried over from the original definition:
//   - Dante, Hemomancer
//   - Keen Tidebinder (ALLY) - empower trigger target
gaScenario('keen-tidebinder-first-empower')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Dante, Hemomancer')
    ->seed('myField', 'Keen Tidebinder')
    ->endTurn()->answer(1, '-')->answer(1, '-')
    ->endTurn(2)->answer(1, '-')->answer(1, '-')->answer(1, '-')->answer(1, 'myField-0@Activate-0@Empower')->answer(1, '1')->pay(1)
    ->tested('Keen Tidebinder')
    ->expectDamage('myField-0', 1, 'Dante takes the unpreventable damage from his own activated ability (X=1)', step: 10)
    ->expectProperty('myField-1', 'TurnEffects', '["ZmBQAOb9gj_POWER"]', 'Keen Tidebinder gets its first-empower-of-the-turn +2 POWER flag', step: 10)
    ->expectDqEmpty(step: 10)
    ->register();

// --- Kongming, Fel Eidolon: On Enter, Recover X (X = TERA cards in banishment) ---
// A real level-up event is required to fire a champion's own On Enter (a static CardID patch
// does not fire triggers, per kongming-wayward-maven-enter-shifting-currents). Fel Eidolon is
// level 3 (3-memory cost), so the starting champion is patched to a level-2 champion first
// (satisfies CanChampionLevelUpIntoCard's targetLevel===currentLevel+1 gate) with Fel Eidolon's
// own CardID also placed in Subcards for TERA element access (GetLegalMaterializeChoices also
// gates champion targets on CanPlayerUseCardElement, same technique as tera-sight-preserve-draw).
// Pre-damaging the champion object lets Recover X be observed as a Damage reduction once the
// level-up completes on the same field object.
// Notes carried over from the original definition:
//   - pays the 3-memory level-up cost, card 1/3
//   - TERA element card #1 in banishment
//   - TERA element card #2 in banishment
//   - level up to Fel Eidolon (probed below)
gaScenario('kongming-fel-eidolon-enter-recover-tera-banished')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Kongming, Fel Eidolon
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
    ->champion(cardName: 'Dante, Aenean Initiate', lineage: ['Kongming, Fel Eidolon'], damage: 5)
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myBanish', 'Kaleidoscope Barrette')
    ->seed('myBanish', 'Slime Totem')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Kongming, Fel Eidolon')
    ->expectProperty('myField-0', 'CardID', '7x2v4tdop1', 'Real level-up resolves into Kongming, Fel Eidolon')
    ->expectDamage('myField-0', 3, 'On Enter: Recover X (X = 2 TERA cards in banishment) reduces Damage 5 -> 3')
    ->expectDqEmpty()
    ->register();

// --- Kongming, Ascetic Vice: On Enter Empower 3 + Inherited N->S draw (Kongming Bonus, at end phase) ---
// Notes carried over from the original definition:
//   - level-1 champion (Dante, Prodigal Swain), satisfies the level-1->2 gate
//   - pays the 2-memory level-up cost, card 1/2
//   - Shifting Currents facing North
//   - level up to Ascetic Vice, fires On Enter Empower 3
//   - reach BeforeEndPhase -> Kongming Bonus SC "any direction" offer
//   - choose SOUTH to fire the Inherited N->S transition (probed below)
gaScenario('kongming-ascetic-vice-enter-empower-and-inherited-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Kongming, Ascetic Vice
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
    ->champion(cardName: 'Dante, Prodigal Swain')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->endTurn()->answer(1, 'SOUTH')
    ->tested('Kongming, Ascetic Vice')
    ->register();

// --- Kongming, Wayward Maven: On Enter, gain the Shifting Currents mastery ---
// Notes carried over from the original definition:
//   - pays the 1-memory level-up cost
//   - level up to Wayward Maven
gaScenario('kongming-wayward-maven-enter-shifting-currents')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Kongming, Wayward Maven
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
    ->tested('Kongming, Wayward Maven')
    ->expectProperty('myField-0', 'CardID', '346vgwz3y4', 'Real level-up resolves into Kongming, Wayward Maven')
    ->expectProperty('myMastery-0', 'CardID', 'qh5mpkyl60', 'On Enter grants the Shifting Currents mastery')
    ->expectProperty('myMastery-0', 'Direction', 'NORTH', 'Shifting Currents starts facing North')
    ->expectDqEmpty()
    ->register();

// --- Krustallan Distiller: [Class Bonus] On Enter, if brewed a Potion this turn, draw into memory ---
// Krustallan Distiller is WATER, so the starting champion's Subcards are patched with a real
// WATER champion (Nico, Rapture's Embrace) for element access; that same champion's CardID is
// ALSO patched directly onto the field so IsClassBonusActive(["CLERIC"]) is NOT satisfied by
// it -- Nico is GUARDIAN, so instead the champion is patched to Arisanna, Master Alchemist
// (CLERIC) directly for the Class Bonus, with Subcards left carrying the WATER unlock. The
// "brewed a Potion this turn" condition is reached via the BREWED_POTION global effect
// (normally set by the real Brew-declaration flow) seeded directly at setup, matching the
// harness's documented technique for reaching an otherwise-unreachable-at-game-start
// precondition without scripting a full brew sequence.
// Notes carried over from the original definition:
//   - Arisanna, Master Alchemist (CLERIC) + WATER lineage unlock
gaScenario('krustallan-distiller-class-bonus-brewed-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Master Alchemist', lineage: ['Nico, Rapture\'s Embrace'])
    ->globalEffect('BREWED_POTION')
    ->seed('myHand', 'Krustallan Distiller')
    ->castSpell('Krustallan Distiller', pay: 3)
    ->expectZoneCount('myMemory', 4, 1, '[Class Bonus] On Enter draws into memory since a Potion was brewed this turn')
    ->register();

// --- Kaleidoscope Barrette: On Enter draw + at recollection phase, empower X = phantasias controlled ---
// NOTE: an earlier version of this fixture seeded the Barrette directly onto the field via 'zone',
// which suffers the exact same never-calls-Enter() silent-fake-pass issue as Scepter of Fascination
// above (confirmed via the same break/restore method: disabling enterAbilities["qktid6zlyt:0"]'s
// Draw() call and re-running the OLD fixture still passed). The recollection-phase empower half of
// the OLD fixture was independently confirmed genuine (its own break/restore on the "qktid6zlyt"
// case in Custom/GameLogic.php correctly fails) -- only the On-Enter draw needed fixing, same
// patchMzId-into-an-existing-hand-slot technique as Scepter of Fascination.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Kaleidoscope Barrette, overwriting the last opening-hand slot (bypasses HandAddReplacement's REGALIA->myMaterial redirect)
//   - Unstable Fractal (PHANTASIA) #1
//   - Unstable Fractal (PHANTASIA) #2
//   - play Kaleidoscope Barrette from hand -- fires its real On Enter draw via DoMaterialize()
//   - ends turn 1
//   - ends turn 2, advancing into player 1's turn 3 -- recollection phase (and its empower trigger) resolves automatically here
//   - decline the turn-3 MAT offer, advancing into MAIN where the recollection phase's EMPOWERED TurnEffects become observable
gaScenario('kaleidoscope-barrette-enter-draw-and-recollection-empower')
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
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->patch('myHand-6', ['CardID' => 'qktid6zlyt'])
    ->seed('myField', 'Unstable Fractal')
    ->seed('myField', 'Unstable Fractal')
    ->playSlot(6)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Kaleidoscope Barrette')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["qktid6zlyt","EMPOWERED","EMPOWER_PLUS_2"]', 'At the beginning of player 1\'s own recollection phase (their real turn 3), the champion gained EMPOWERED/EMPOWER_PLUS_2 -- proving the recollection-phase trigger (Custom/GameLogic.php ~9491-9499, case \'qktid6zlyt\') ran and Empower()\'d by the phantasia count (2, from the two seeded Unstable Fractal). Independently confirmed by disabling that case body and observing this fixture then fails with TurnEffects staying empty.')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p1Field-2', 'Status', '2')
    ->expectProperty('p1Field-2', 'TurnEffects', '[]')
    ->expectProperty('p1Field-3', 'CardID', 'qktid6zlyt')
    ->expectProperty('p1Field-3', 'Status', '2')
    ->expectDamage('p1Field-3', 0)
    ->expectProperty('p1Field-3', 'Counters', '[]')
    ->expectProperty('p1Field-3', 'TurnEffects', '[]')
    ->expectProperty('p1Hand-6', 'CardID', 'px60u5n1do', 'A genuinely NEW card now occupies the hand slot Kaleidoscope Barrette itself was overwritten into and then left (by being played) -- proving its \'On Enter: Draw a card\' fired as a real replayed action (enterAbilities[\'qktid6zlyt:0\'], Draw($player,1)), not merely baked into a pre-captured snapshot. Independently confirmed by disabling that closure and observing this fixture then fails with the slot staying empty.')
    ->expectProperty('p2Hand-7', 'CardID', 'px60u5n1do')
    ->expectZoneCount('myField', 4, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// Happy path: after activating in the main phase, a Slime ally that enters gets +1 buff counter.
// Notes carried over from the original definition:
//   - Key Slime Pudding -> materialized for real below
//   - Red Slime (plain Slime ally, reserve 3) -> myHand-7
//   - end player 1's turn 1
//   - end player 2's turn 2 -> player 1's turn 3 material phase
//   - materialize Key Slime Pudding (memory cost 0)
//   - decline the BREC opportunity window -> main phase
//   - activate "Banish CARDNAME" from the main phase
//   - play Red Slime
//   - its reserve cost
gaScenario('key-slime-pudding-banish-slime-ally-enters-boosted')
    ->deck($kspDeck)
    ->seed('myMaterial', 'Key Slime Pudding')
    ->seed('myHand', 'Red Slime')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->castSpell('Red Slime', pay: 3)
    ->tested('Key Slime Pudding')
    ->register();

// Stacking: "an ADDITIONAL buff counter" -- Green Slime's own On Enter (two buff counters) still happens.
// Notes carried over from the original definition:
//   - Spirit of Wind lineage: WIND element unlock for Green Slime
//   - Green Slime (WIND Slime: On Enter two buff counters) -> myHand-7
gaScenario('key-slime-pudding-banish-extra-counter-adds-to-green-slime-enter-counters')
    ->deck($kspDeck)
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myMaterial', 'Key Slime Pudding')
    ->seed('myHand', 'Green Slime')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->castSpell('Green Slime', pay: 3)
    ->tested('Key Slime Pudding', 'Green Slime')
    ->register();

// Negative: a non-Slime ally entering after the activation gets no extra counter.
// Notes carried over from the original definition:
//   - Dungeon Guide (Human Mage ally, reserve 3) -> myHand-7
//   - decline Dungeon Guide's "you may banish two cards from memory" On Enter
gaScenario('key-slime-pudding-banish-non-slime-ally-enters-unboosted')
    ->deck($kspDeck)
    ->seed('myMaterial', 'Key Slime Pudding')
    ->seed('myHand', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->play('Dungeon Guide')->pay(3)->answer(1, 'NO')
    ->tested('Key Slime Pudding')
    ->register();

// Opportunity-window path: the ability is offered in the opportunity window (the exact "@Activate-0@Banish"
// choice that used to be missing from GetPlayableOpportunityChoices()) and resolves from there.
// Notes carried over from the original definition:
//   - materialize Key Slime Pudding -> BREC opportunity window lists its Banish ability
//   - choose the listed ability in the window
gaScenario('key-slime-pudding-banish-offered-in-opportunity-window')
    ->deck($kspDeck)
    ->seed('myMaterial', 'Key Slime Pudding')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')->answer(1, 'myField-1@Activate-0@Banish')
    ->tested('Key Slime Pudding')
    ->register();

// "Until end of turn": the global effect is gone once the turn ends.
// Notes carried over from the original definition:
//   - end player 1's turn 3
gaScenario('key-slime-pudding-banish-effect-ends-at-end-of-turn')
    ->deck($kspDeck)
    ->seed('myMaterial', 'Key Slime Pudding')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-3')->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->endTurn()
    ->tested('Key Slime Pudding')
    ->register();

// --- Kraal, Stonescale Tyrant: printed Spellshroud -- an opposing spell cannot target it ---
// Kraal, Stonescale Tyrant (572j3oda2h, UNIQUE,ALLY): "Intercept, Spellshroud, True Sight, Vigor" -- the
// Spellshroud is the card's own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-
// rules.md, Keywords > Spellshroud 1: "This can't be targeted by spells"). It was never implemented:
// HasSpellshroud() (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID
// list and HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud
// keyword, so function_exists is false); 572j3oda2h was in neither, so FilterSpellshroudTargets() kept the
// card in the target list. Fixed: it is now in HasSpellshroud()'s printed-Spellshroud table (after the
// HasNoAbilities / NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff counter
// on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, answering with the
// holder is refused (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('kraal-stonescale-tyrant-printed-spellshroud-spell-cannot-target-it', ['Kraal, Stonescale Tyrant'], 'Liturgy of Corruption')
    ->register();

// --- Kindling Flare: declining to sacrifice another Herb still pays the reserve cost and resolves (dontSkipOnPass regression) ---
// Kindling Flare (dcgw05qzza): "As an additional cost to activate this card, sacrifice any amount of Herbs. Deal 1+X damage split among any amount of target units where X is the amount of Herbs sacrificed." Sacrificing fewer Herbs than are available (declining the 'Sacrifice another Herb?' prompt) ends the additional cost: the reserve cost is then paid and the spell resolves with X = herbs sacrificed.
// Regression: KindlingFlareSacHerb's re-queue (and the initial queue in ActivateCard) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its PASS branch (queue the reserve payments + EffectStackOpportunity) never ran: the card sat on the effect stack with an empty decision queue and the activation could never finish (a soft-lock with the cost unpaid). Fixing the flag alone was not enough: the PASS branch then queues unflagged CUSTOM reserve-payment decisions that were themselves skipped while lastDecision was still 'PASS', so the handler's decline branch now queues a PASSPARAMETER '-' first to reset it.
// Two Herb tokens (Blightroot, Fraysia) are seeded on the field; one is sacrificed, the second declined with PASS, the 2 reserve cost is paid, and the 2 damage (1 + X, X = 1) is assigned to the opposing Dungeon Guide.
// Notes carried over from the original definition:
//   - Blightroot (HERB) -> myField-1
//   - Fraysia (HERB) -> myField-2
//   - opposing Dungeon Guide -> theirField-1
//   - sacrifice the first Herb
//   - decline sacrificing another Herb
//   - pay reserve 1/2
//   - pay reserve 2/2
//   - pass the fast-action window so the spell resolves
//   - assign all 2 damage (1 + X, X = 1) to the opposing Dungeon Guide
gaScenario('kindling-flare-decline-further-herb-pays-reserve-cost')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Fraysia')
    ->seed('myHand', 'Kindling Flare')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->play('Kindling Flare')->target('myField-1')->pass(1)->pay(2)->pass(1)->answer(1, 'theirField-1:2')
    ->expectDamage('p2Field-1', 2, 'Kindling Flare resolved with X = 1 Herb sacrificed: 1 + 1 = 2 damage dealt to the chosen unit')
    ->expectZoneCount('myMemory', 2, 1, 'The 2 reserve cost was paid (two cards moved to memory)')
    ->expectZoneCount('myField', 2, 1, 'Exactly one Herb was sacrificed (champion + the declined second Herb remain)')
    ->expectDqEmpty()
    ->register();

// --- Kindling Flare: declining to sacrifice any Herb still pays the reserve cost and resolves (dontSkipOnPass regression) ---
// Kindling Flare (dcgw05qzza): "As an additional cost to activate this card, sacrifice any amount of Herbs. Deal 1+X damage split among any amount of target units where X is the amount of Herbs sacrificed." "Any amount" includes zero: declining the very first 'Sacrifice an Herb?' prompt (X = 0) must still go on to pay the reserve cost and resolve for 1 damage.
// Regression: ActivateCard() (Custom/GameLogic.php) queued the first paired CUSTOM 'KindlingFlareSacHerb|N' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler on PASS, the reserve payments + EffectStackOpportunity were never queued, and the card was stuck on the effect stack with an empty decision queue (soft-lock, cost unpaid). See kindling-flare-decline-further-herb-pays-reserve-cost for the PASSPARAMETER reset the decline branch additionally needs.
// Two Herb tokens are seeded on the field but none is sacrificed; the 2 reserve cost is paid and the 1 damage (1 + X, X = 0) is assigned to the opposing Dungeon Guide.
// Notes carried over from the original definition:
//   - Blightroot (HERB) -> myField-1
//   - Fraysia (HERB) -> myField-2
//   - opposing Dungeon Guide -> theirField-1
//   - decline sacrificing any Herb (X = 0)
//   - pay reserve 1/2
//   - pay reserve 2/2
//   - pass the fast-action window so the spell resolves
//   - assign the 1 damage (1 + X, X = 0) to the opposing Dungeon Guide
gaScenario('kindling-flare-decline-all-herbs-pays-reserve-cost')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Fraysia')
    ->seed('myHand', 'Kindling Flare')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->play('Kindling Flare')->pass(1)->pay(2)->pass(1)->answer(1, 'theirField-1:1')
    ->expectDamage('p2Field-1', 1, 'Kindling Flare resolved with X = 0 Herbs sacrificed: 1 damage dealt to the chosen unit')
    ->expectZoneCount('myMemory', 2, 1, 'The 2 reserve cost was paid (two cards moved to memory)')
    ->expectZoneCount('myField', 3, 1, 'No Herb was sacrificed (champion + both Herbs remain)')
    ->expectDqEmpty()
    ->register();

// top five, top to bottom: DG, FS, Fairy Whispers, FS, Baby Green Slime (ANIMAL ally, 5th); then Charge the Soul (marker)
gaScenario('kind-beastcaller-enter-look-five-reveal-deep-animal-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myHand', 'Kind Beastcaller')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Fluffy Shopkeep', 'Baby Green Slime', 'Charge the Soul'])
    ->castSpell('Kind Beastcaller', pay: 3, target: 'myTempZone-4')
    ->register();

gaScenario('kind-beastcaller-enter-look-five-decline-all-five-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myHand', 'Kind Beastcaller')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Fluffy Shopkeep', 'Baby Green Slime', 'Charge the Soul'])
    ->castSpell('Kind Beastcaller', pay: 3, window: 'me')
    ->register();

// Fluffy Shopkeep is an Animal ally, so only Dungeon Guide (not an Animal/Beast) and Fairy Whispers (a spell) are used here
gaScenario('kind-beastcaller-enter-look-five-no-animal-all-five-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myHand', 'Kind Beastcaller')
    ->deckTop(['Dungeon Guide', 'Fairy Whispers', 'Dungeon Guide', 'Fairy Whispers', 'Dungeon Guide', 'Charge the Soul'])
    ->castSpell('Kind Beastcaller', pay: 3)
    ->register();

// --- Kongming, Erudite Strategist (0i139x5eub): "On Enter: Banish the top card of your deck. Until the beginning of your next turn, you may play it as long as your Shifting Currents face North.
// Repeat this process for East, South, and West." The generated body banished with a fixed-index MZMove loop: only the North card was ever banished. The champion levels up (level 1 -> 2) from
// Dante, Prodigal Swain; the 2-memory level-up cost is pre-loaded. ---
// Notes carried over from the original definition:
//   - Dante, Prodigal Swain (level 1, MAGE) satisfies the level-1 -> 2 gate
//   - pays the 2-memory level-up cost, card 1/2
//   - Shifting Currents facing North
//   - the four to banish (top first), a Fluffy Shopkeep (the Kongming lineage's inherited draw takes it), then a Charge the Soul marker
//   - level up to Erudite Strategist: On Enter
gaScenario('kongming-erudite-strategist-enter-banishes-four-top-cards')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Kongming, Erudite Strategist
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->champion(cardName: 'Dante, Prodigal Swain')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Hidden Longbowman', 'Fluffy Shopkeep', 'Charge the Soul'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Kongming, Erudite Strategist')
    ->register();

// --- Kingdom's Divide (qy34r8gffr): "Choose a card name. Until the beginning of your next turn, cards with the chosen name cost 2 more to activate." The generated opening body was registered only in the
// dead $activateCardAbilities table, so playing it never asked for a name and nothing was taxed. ---
// Notes carried over from the original definition:
//   - Kingdom's Divide -> myHand-1
gaScenario('kingdoms-divide-choose-card-name-taxes-it-two-more')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Kingdom\'s Divide')
    ->playSlot(1)->pay(1)->target('myMemory-0')
    ->tested('Kingdom\'s Divide')
    ->register();

// Kingdom's Divide (qy34r8gffr): "Choose a card name. Until the beginning of your next turn, cards with the chosen name cost 2 more to activate." Naming Dungeon Guide (reserve cost 3) makes the next Dungeon Guide cost 5.
// Notes carried over from the original definition:
//   - six payers (and the card named): myHand-0..5
//   - Kingdom's Divide -> myHand-6
//   - the Dungeon Guide to activate afterwards -> myHand-7 (myHand-5 once Kingdom's Divide has left)
gaScenario('kingdoms-divide-named-card-costs-two-more-reserve-to-activate')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Kingdom\'s Divide')
    ->seed('myHand', 'Dungeon Guide')
    ->playSlot(6)->pay(2)
    ->playSlot(5)->pay(5)->answer(1, 'NO')
    ->tested('Kingdom\'s Divide')
    ->expectZoneCount('myMemory', 6, 1, 'One reserve payment for Kingdom\'s Divide and five (3 + 2) for the taxed Dungeon Guide')
    ->expectZoneCount('myField', 2, 1, 'The Dungeon Guide was activated and entered the field')
    ->expectZoneCount('myHand', 0, 1, 'Every hand card was spent')
    ->expectDqEmpty()
    ->register();
