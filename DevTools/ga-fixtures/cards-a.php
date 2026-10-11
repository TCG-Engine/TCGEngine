<?php
/**
 * Fixtures for cards starting with 'A', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Anathema's End: load into an unloaded Gun weapon ---
// Anathema's End is an UMBRA (advanced element) ITEM card, so the starting champion's
// Subcards are patched with a real UMBRA champion (Tristan, Shadowdancer) to unlock element
// access. Framework Sidearm (a REGALIA,WEAPON with the GUN subtype and no Subcards, i.e.
// unloaded) is seeded onto the field as the [REST] Load ability's target, same pattern as
// penetrator-round-load-gun. Only the always-available Load half is covered; the [Class
// Bonus] On Champion Hit curse-banishing trigger requires a real combat hit and is out of
// scope.
// Notes carried over from the original definition:
//   - UMBRA lineage/element unlock
//   - Framework Sidearm (unloaded GUN weapon) - Load target
gaScenario('anathemas-end-load-gun')
    ->standardDeck('Anathema\'s End')
    ->champion(lineage: ['Tristan, Shadowdancer'])
    ->seed('myField', 'Framework Sidearm')
    ->seed('myHand', 'Anathema\'s End')
    ->castSpell('Anathema\'s End', pay: 3)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->expectProperty('p1Field-1', 'Subcards', '["ii17fzcyfr"]', 'Loading appends the bullet\'s CardID to the target Gun weapon\'s Subcards')
    ->expectZoneCount('myField', 2, 1, 'The bullet is removed from the field entirely, not moved to another zone, once loaded')
    ->expectDqEmpty()
    ->register();

// --- Aenean Ward: prevent the next 2 damage to target unit ---
// Aenean Ward's element is NORM, so no lineage patch is needed. Only the base always-available
// prevention is covered; the [Class Bonus][Level 3+] bonus draw isn't reached (no class bonus
// or level condition set up), and targets our own champion directly.
gaScenario('aenean-ward-prevent-2')
    ->standardDeck('Aenean Ward')
    ->seed('myHand', 'Aenean Ward')
    ->castSpell('Aenean Ward', pay: 1, window: 'both', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","PREVENT_ALL_2"]', 'The targeted champion is protected by a PREVENT_ALL_2 shield')
    ->expectDqEmpty()
    ->register();

// --- Aesan Protector: On Enter return target friendly ally to hand ---
// Aesan Protector's element is WIND, so the starting champion's Subcards are patched with a
// real WIND champion (Spirit of Wind), same as deployment-beacon-summon-drone/
// reclaim-return-ally/winds-of-retribution-ally-buff. A second Dungeon Guide is seeded onto
// our own field as the On Enter return-to-hand target (so it's a different object from Aesan
// Protector itself, which is also a legal-looking ally once it enters). Only the On Enter is
// covered; Intercept is a combat-redirect passive that requires a real attack and is out of
// scope.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Dungeon Guide (ALLY) - On Enter return target
gaScenario('aesan-protector-return-ally')
    ->standardDeck('Aesan Protector')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Aesan Protector')
    ->castSpell('Aesan Protector', pay: 4, window: 'both', target: 'myField-1')
    ->register();

// --- Airship Engineer: On Enter draws into memory if you control a distant unit ---
// Airship Engineer's element is FIRE (matching the starting champion), so no lineage patch is
// needed. IsDistant() (CardLogic.php) reads the "DISTANT" TurnEffect directly, so the starting
// champion's TurnEffects are patched with it directly (same tag rocket-jump-distant confirmed
// is applied by a real "becomes distant" effect) rather than scripting one. Only the On Enter
// draw-into-memory is covered; the [Class Bonus] Ranged 2 static combat bonus is out of scope.
// Notes carried over from the original definition:
//   - On Enter "control a distant unit" precondition
gaScenario('airship-engineer-enter-draw-memory')
    ->standardDeck('Airship Engineer')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->seed('myHand', 'Airship Engineer')
    ->castSpell('Airship Engineer', pay: 2)
    ->expectZoneCount('myMemory', 3, 1, '2 reserve payments plus the On Enter draw-into-memory leave 3 cards in memory')
    ->expectDqEmpty()
    ->register();

// --- Astra Sight: Glimpse 1, then draw a card ---
// Astra Sight is an ASTRA (advanced element) ACTION card, so the starting champion's Subcards
// are patched with a real ASTRA champion (Arisanna, Astral Zenith), same as
// dwarf-stars-glow-damage/cosmic-bolt-damage/cometfall-sweep-damage, to unlock element access.
// Its reserve cost is 0, so no reserve payment is needed. Only the base Glimpse 1 + draw
// effect is covered; the Starcalling (0) alternate-timing clause is out of scope.
// Notes carried over from the original definition:
//   - ASTRA lineage/element unlock
gaScenario('astra-sight-glimpse-draw')
    ->standardDeck('Astra Sight')
    ->champion(lineage: ['Arisanna, Astral Zenith'])
    ->seed('myHand', 'Astra Sight')
    ->play('Astra Sight')->pass(1)->answer(1, 'Top=px60u5n1do;Bottom=')
    ->register();

// --- Alpha Philterbeast: Brew - Three Herbs, ages if brewed ---
// Notes carried over from the original definition:
//   - EXALTED lineage/element unlock
//   - Blightroot herb 1/3
//   - Manaroot herb 2/3
//   - Fraysia herb 3/3
gaScenario('alpha-philterbeast-brew-age-counters')
    ->standardDeck('Alpha Philterbeast')
    ->champion(lineage: ['Mordred, Fated Luminary'])
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Manaroot')
    ->seed('myField', 'Fraysia')
    ->seed('myHand', 'Alpha Philterbeast')
    ->play('Alpha Philterbeast')->answer(1, 'YES')->target('myField-1')->target('myField-2')->target('myField-3')
    ->passBoth()
    ->register();

// --- Attune with the Winds: put a buff counter on each ally you control ---
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Dungeon Guide (ALLY) - buff recipient
gaScenario('attune-with-the-winds-buff')
    ->standardDeck('Attune with the Winds')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Attune with the Winds')
    ->castSpell('Attune with the Winds', pay: 3, window: 'both')
    ->register();

// --- Arcane Sight: champion gets +1 level until end of turn, draw a card ---
// Arcane Sight's element is ARCANE, so the starting champion's Subcards are patched with a
// real ARCANE champion (Lorraine, Arclight Saber, this deck's own champion) to unlock element
// access. Its reserve cost is 0, so no reserve payment step is needed. The ability tags the
// champion's TurnEffects with its own card ID and draws a card.
// Notes carried over from the original definition:
//   - ARCANE lineage/element unlock
gaScenario('arcane-sight-level-draw')
    ->standardDeck('Arcane Sight')
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myHand', 'Arcane Sight')
    ->castSpell('Arcane Sight', window: 'both')
    ->register();

// --- Aenean Frozen Shunt: end the combat phase as a REACTION during the COMBAT_DAMAGE window ---
// Emberslash is played with no class-bonus override this time (only MAGE is granted), so its
// own on-attack trigger stays inert and doesn't add an extra decision to the sequence -- it's
// purely a vehicle to get combat active. Declaring the attack fires OnAttack, then
// FinalizeAttackDeclaration grants the COMBAT_DAMAGE opportunity window to the turn player
// (GrandArchiveSim/Custom/CombatLogic.php:2902) before damage resolves; Aenean Frozen Shunt
// (REACTION speed) is offered there as a fast hand card and, once activated, ends combat --
// asserted by damage never landing on the defending champion.
// Notes carried over from the original definition:
//   - Aenean Frozen Shunt is WATER element (CanPlayerMeetCardElementRequirements ->
//   - GetChampionLineage() -> Subcards, same mechanism as conductive-strike-static-counters'
//   - ARCANE patch) and its [Class Bonus][Level 4+] draw needs both a CLERIC/MAGE class match
//   - (Counters['_overrides']['classes'], same as the WARRIOR overrides elsewhere) and
//   - PlayerLevel() >= 4, which ObjectCurrentLevel computes as CardLevel + a "level" counter
//   - (GrandArchiveSim/Custom/GameLogic.php:12933-12936) -- so patch a level counter directly
//   - instead of scripting real champion level-ups.
gaScenario('aenean-frozen-shunt-end-combat')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'MAGE'], 'level' => 4], 'Subcards' => ['tafqldAGRF']])
    ->seed('myHand', 'Emberslash')
    ->seed('myHand', 'Aenean Frozen Shunt')
    ->endTurn()->answer(1, '-')->answer(1, '-')
    ->endTurn(2)->answer(1, '-')->answer(1, '-')->answer(1, '-')->answer(1, '-')
    ->play('Emberslash')->pay(1)->answer(1, '-')->target('theirField-0')->target('myHand-6')->pay(2)
    ->tested('Aenean Frozen Shunt')
    ->expectDamage('theirField-0', 0, 'Combat ends before damage resolves -- the defending champion takes none of Emberslash\'s power', step: 15)
    ->expectZoneCount('myGraveyard', 2, 1, 'Both Emberslash and Aenean Frozen Shunt resolve and land in the graveyard', step: 15)
    ->expectZoneCount('myMemory', 4, 1, '3 reserve payments (1 for Emberslash, 2 for Aenean Frozen Shunt) plus 1 Class Bonus/Level 4+ memory draw', step: 15)
    ->expectDqEmpty(step: 15)
    ->register();

// Real attack-declaration sequence, same shape as blistering-insurgent-attack-buff: the 2-pass
// cycle (P1 pass, P2 pass) already reaches P1's global turn 3 (their own 2nd turn), past Rule
// 1.h's turn-1 attack lock. Ardent Cloudstriker is seeded directly onto the field (bypassing its
// 5-reserve cost) with Status patched awake (2) so it can declare an attack immediately.
// Notes carried over from the original definition:
//   - Shifting Currents facing West
//   - Ardent Cloudstriker
//   - awake, can attack
//   - decline materialize offer
//   - declare attack with Ardent Cloudstriker
//   - target opponent's champion
gaScenario('ardent-cloudstriker-west-attack-champion-buff')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'WEST'])
    ->seed('myField', 'Ardent Cloudstriker')
    ->patch('myField-1', ['Status' => 2])
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')
    ->tested('Ardent Cloudstriker')
    ->register();

// --- Automaton Bomber: Ranged 4 (unconditional -- no Class Bonus needed) ---
// Seed Automaton Bomber directly onto player 2's field (awake, Distant) so player 2 can
// legally attack on turn 2 (Rule 1.h only blocks the opening player's own turn 1). DISTANT
// is on the persistent-turn-effects allowlist and is only cleared in EndPhase for the
// *controller's own* ending turn, so seeding it before turn 1 ends (player 1's turn, not
// player 2's) survives into player 2's turn untouched. Player 1 ends turn 1 with a single
// CustomInput Pass (there's no pending decision to decline here, so this action itself ends
// the turn -- confirmed by directly instrumenting the phase machine this session), then
// player 2 attacks with Ranged 4 active (1 base + 4 = 5 POWER).
// Notes carried over from the original definition:
//   - Automaton Bomber, awake and Distant
//   - ends turn 1 (no pending decision to decline)
gaScenario('automaton-bomber-ranged-attack')
    ->standardDeck('Automaton Bomber')
    ->seed('myField', 'Automaton Bomber', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->pass(2)->pass(1)
    ->tested('Automaton Bomber')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 5, 'Automaton Bomber deals 1 base + 4 Ranged POWER while distant')
    ->expectProperty('p2Field-1', 'TurnEffects', '["DISTANT"]', 'Automaton Bomber is still Distant (expires at end of its controller\'s turn, not on attack)')
    ->register();

// --- Archon Broadsword: additional attack cost pay (2); [Class Bonus] +1 POWER per token ---
// Same turn-cycle/durability-counter approach as bulwark-sword-class-bonus-attack-cost.
// Two Automaton Drone tokens are seeded onto player 2's field (alongside a GUARDIAN Class
// Bonus champion) so the "+1 POWER for each token you control" clause has something to count.
// Notes carried over from the original definition:
//   - Tonoris, Lone Mercenary (GUARDIAN champion) - Class Bonus source
//   - Automaton Drone token #1
//   - Automaton Drone token #2
//   - Archon Broadsword, usable
//   - ends turn 1
gaScenario('archon-broadsword-class-bonus-token-power')
    ->standardDeck('Archon Broadsword')
    ->seed('myField', 'Tonoris, Lone Mercenary', player: 2)
    ->seed('myField', 'Automaton Drone', player: 2)
    ->seed('myField', 'Automaton Drone', player: 2)
    ->seed('myField', 'Archon Broadsword', player: 2, properties: ['Counters' => ['durability' => 1]])
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-4')->answer(2, 'theirField-0')->pay(2, 2)->pass(2)
    ->pass(1)
    ->tested('Archon Broadsword')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 4, '2 base + 1 [Class Bonus] POWER per token (2 tokens)')
    ->register();

// --- Assemble the Ancients: sacrifice domains, summon that many buffed Automaton Drone tokens ---
// Assemble the Ancients is NEOS (advanced element), so the starting champion's Subcards are
// patched with a real NEOS champion (Tonoris, Creation's Will) to unlock element access. Two
// Palatial Concourse domains (a plain DOMAIN with only an unrelated recollection-phase
// trigger, chosen to avoid any On Enter interference from the summoned tokens) are seeded onto
// the field so AssembleAncientsSacrifice (CardDQHandlers.php ~2026) has something to offer.
// Its own MZMAYCHOOSE loop is repeated twice (sacrificing both domains via DoSacrificeFighter)
// then declined, which calls AssembleAncientsFinalize(player, 2): summons 2 Automaton Drone
// tokens, each entering rested (Status=1) with 2 buff counters and a VIGOR_EOT turn effect.
// Its printed cost is 3 reserve, paid from three more hand cards.
// Notes carried over from the original definition:
//   - NEOS lineage/element unlock (Tonoris, Creation's Will)
//   - Palatial Concourse (DOMAIN) #1
//   - Palatial Concourse (DOMAIN) #2
//   - sacrifice domain #1
//   - sacrifice domain #2 (reindexed after #1's removal)
gaScenario('assemble-the-ancients-domain-sacrifice-tokens')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Tonoris, Creation\'s Will'])
    ->seed('myField', 'Palatial Concourse')
    ->seed('myField', 'Palatial Concourse')
    ->seed('myHand', 'Assemble the Ancients')
    ->play('Assemble the Ancients')->pay(3)->target('myField-1')->target('myField-1')
    ->expectZoneCount('myField', 3, 1, 'both domains sacrificed, two Automaton Drone tokens summoned (champion + 2 tokens)')
    ->expectProperty('p1Field-1', 'CardID', 'mu6gvnta6q', 'summons Automaton Drone tokens')
    ->expectProperty('p1Field-1', 'Status', '1', 'tokens enter the field rested')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'buff counters equal to the amount of domains sacrificed (2)')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","VIGOR_EOT"]', 'tokens gain vigor until end of turn')
    ->register();

// --- Academy Attendant: [Class Bonus][Memory 4+] +1 POWER ---
// Academy Attendant's static +1 POWER (GameLogic.php ~11255) is a plain
// IsClassBonusActive(["CLERIC"]) + "4+ cards in memory" check with no turn-cycle needed at all
// -- the champion is patched directly to Arisanna, Master Alchemist (CLERIC) and 4 filler cards
// are seeded into myMemory. Verified via computed_power_equals (base 2 + 1 = 3) rather than a
// real combat sequence.
// Notes carried over from the original definition:
//   - Arisanna, Master Alchemist (CLERIC)
//   - Academy Attendant
//   - harmless no-op click
gaScenario('academy-attendant-class-bonus-memory-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Master Alchemist')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myField', 'Academy Attendant')
    ->target('myField-0')
    ->tested('Academy Attendant')
    ->register();

// --- Arisanna, Astral Zenith: Once per turn, pay (0) rather than a card's starcalling costs ---
// The starting champion's CardID is patched directly to Arisanna, Astral Zenith (q3huqj5bba) --
// ChampionHasInLineage() checks [champion's own CardID, ...Subcards], so this trivially
// satisfies "Arisanna Lineage" and also unlocks ASTRA (its own element) for Cometfall's
// Starcalling. Its level is patched to 1 so Scry the Skies' "Glimpse LV" (0 by default) reveals
// exactly one card. The deck's top card (myDeck-0) is patched directly to Cometfall
// (4d5vettczb, Starcalling -- (2)) so that single glimpsed card is a starcalling candidate.
// GetStarcallingCost() internally calls EnsureArisannaFreeStarcallingEligibility() on every
// call (including the candidate pre-check during Glimpse), so the cost is already forced to 0
// before the player is even offered the choice -- no reserve payment is queued when starcalled.
// Confirmed via Cometfall's own effect resolving (3 damage to all non-astra units) with zero
// reserve spent, and the once-per-turn "ArisannaFreeStarcallingUsed" flag being set afterward
// (GameLogic.php's MarkArisannaFreeStarcallingUsed/HasUsedArisannaFreeStarcalling) -- not
// independently re-testable within one fixture, so only the single use is exercised.
// Notes carried over from the original definition:
//   - Cometfall on top of deck
//   - Starcall Cometfall from the glimpse popup instead of returning it to the deck.
//   - Scry the Skies' own "draw a card into memory" clause then runs its own glimpse-driven
//   - draw (the same multi-card multiplier documented in stream-of-consciousness-draw-into-
//   - memory / scry-the-skies-glimpse-lv-draw); accept the offered bottom order verbatim.
gaScenario('arisanna-astral-zenith-free-starcalling')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Astral Zenith', level: 1)
    ->patch('myDeck-0', ['CardID' => '4d5vettczb'])
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)->target('myTempZone-0')->answer(1, 'Bottom=em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY')
    ->tested('Arisanna, Astral Zenith')
    ->expectDamage('p2Field-0', 4, 'Cometfall\'s Class Bonus (4 damage) resolved despite paying (0) instead of its printed Starcalling (2) cost')
    ->expectDamage('p1Field-0', 0, 'The Astral Zenith champion itself is ASTRA and exempt from Cometfall\'s non-astra damage')
    ->expectDqEmpty()
    ->register();

// --- Arisanna, Herbalist Prodigy: On Enter, Gather twice ---
// Arisanna, Herbalist Prodigy is level 1, one level above the default level-0 starting
// champion (Spirit of Fire), so no lineage/element patch is needed -- 0+1 is already a legal
// level-up (same shape as dante-prodigal-swain-summon-token). Champion-swap materialization is
// only offered through the material-phase MZMAYCHOOSE at the start of a turn, so both players
// end their first turn (P1 -> P2) to reach that prompt on P1's next turn; its 1-memory cost is
// paid from a filler card seeded into myMemory. Its On Enter ability Gathers twice (resolves
// to Blightroot with this fixture's seed, same as foraging-servant-enter-gather).
// Notes carried over from the original definition:
//   - filler card in memory to pay the 1-memory level-up cost
//   - Decline the ambient opportunity offering the two gathered Herb tokens' own fast
//   - Sacrifice abilities (a different mechanic than Gather itself).
gaScenario('arisanna-herbalist-prodigy-on-enter-gather-twice')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Arisanna, Herbalist Prodigy
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
    ->endTurn(2)->target('myMaterial-0')->pass(1)
    ->tested('Arisanna, Herbalist Prodigy')
    ->expectZoneCount('myField', 3, 1, 'The field now holds the champion and two gathered tokens')
    ->expectDqEmpty()
    ->register();

// --- Arisanna, Master Alchemist: On Enter, Gather twice ---
// Arisanna, Master Alchemist is level 2 (Arisanna Lineage), but CanChampionLevelUpIntoCard only
// checks targetLevel === currentLevel + 1 (GameLogic.php ~19487-19501) -- lineage text is not
// enforced by the level-up gate itself. The starting champion is patched directly to Arisanna,
// Herbalist Prodigy (b31x97n2jn, level 1) so a real level-up is legal; patching the CardID is
// fine here since only Master Alchemist's own On Enter is under test. Champion-swap
// materialization is only offered through the material-phase MZMAYCHOOSE at the start of a
// turn, so both players end their first turn (P1 -> P2) to reach that prompt; its 2-memory cost
// is paid from two filler cards seeded into myMemory. Its On Enter ability Gathers twice
// (resolves to Silvershine and Blightroot with this fixture's seed, same as arisanna-herbalist-
// prodigy-on-enter-gather-twice). Only the base On Enter is covered; the "Inherited Effect" end-
// phase sacrifice-two-Herbs-draw clause is out of scope.
// Notes carried over from the original definition:
//   - Arisanna, Herbalist Prodigy (level 1) - level-up precondition
//   - filler card 1/2 in memory to pay Master Alchemist's 2-memory level-up cost
//   - filler card 2/2
//   - Decline the ambient opportunity offering the two gathered Herb tokens' own fast
//   - Sacrifice abilities (a different mechanic than Gather itself).
gaScenario('arisanna-master-alchemist-on-enter-gather-twice')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Arisanna, Master Alchemist
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
    ->champion(cardName: 'Arisanna, Herbalist Prodigy')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->pass(1)
    ->tested('Arisanna, Master Alchemist')
    ->expectZoneCount('myField', 3, 1, 'The field now holds the champion and two gathered tokens')
    ->expectDqEmpty()
    ->register();

// --- Anger the Skies: deal 3 (4 with [Class Bonus]) damage to all allies ---
// Anger the Skies is ARCANE, so the starting champion's Subcards are patched with a real ARCANE
// champion (Lorraine, Arclight Saber) to unlock element access. Unconditional base deals 3
// damage to all allies both sides (GeneratedMacroCode.php ~22858-22865); the [Class Bonus] bump
// to 4 is out of scope. A Dungeon Guide is seeded onto the opponent's field as the only ally
// target present.
// Notes carried over from the original definition:
//   - ARCANE lineage/element unlock
//   - Dungeon Guide (ALLY) - damage target
//   - The Lorraine, Arclight Saber Subcards patch grants a lineage-inherited "Enlighten"
//   - ability, offered as a fast-action opportunity to both players once reserve is paid
//   - (same side effect as arcane-sight-level-draw) -- both decline.
gaScenario('anger-the-skies-damage-all-allies')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Anger the Skies')
    ->castSpell('Anger the Skies', pay: 3, window: 'both')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'wOKw0q4SZR')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->expectZoneCount('theirField', 1, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Arcane Blast: deal 11 damage to target champion ---
// Arcane Blast is ARCANE, so the starting champion's Subcards are patched with a real ARCANE
// champion (Lorraine, Arclight Saber) to unlock element access. Its 11-reserve cost needs more
// fuel than a natural 7-card hand provides (same shortfall pattern as disintegrate-destroy,
// which needed 2 extra fillers for an 8-reserve cost); 5 extra filler cards are seeded into hand.
// Unlike the smaller-reserve-cost ARCANE fixtures in this batch, paying all 11 reserve leaves
// only 1 hand card -- too little to afford the Lorraine, Arclight Saber lineage-inherited
// "Enlighten" ability, so GetPlayableOpportunityChoices offers nothing and EffectStackOpportunity
// auto-resolves with no MZMAYCHOOSE at all (confirmed via direct probe: sending a stray PASS here
// instead answers the ability's OWN target-choice MZCHOOSE with "PASS", which ArcaneBlastTarget's
// handler treats as a decline and the 11 damage is never dealt) -- so no PASS step is needed
// between paying reserve and choosing the damage target.
// Notes carried over from the original definition:
//   - ARCANE lineage/element unlock
//   - Extra reserve-payment fuel 1/5
//   - Extra reserve-payment fuel 2/5
//   - Extra reserve-payment fuel 3/5
//   - Extra reserve-payment fuel 4/5
//   - Extra reserve-payment fuel 5/5
//   - target opponent's champion
gaScenario('arcane-blast-damage-target-champion')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Arcane Blast')
    ->castSpell('Arcane Blast', pay: 11, target: 'theirField-0')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'pn9gQjV3Rb')
    ->expectDamage('p2Field-0', 11)
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":11}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectZoneCount('myHand', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 11, 1)
    ->register();

// --- Arcane Disposition: draw 2 (3 with [Class Bonus]), discard hand at next end phase ---
// Arcane Disposition is ARCANE, so the starting champion's Subcards are patched with a real
// ARCANE champion (Lorraine, Arclight Saber) to unlock element access. Draws 2 (the [Class
// Bonus] 3rd draw is out of scope), then sets a delayed-discard flag
// (blq7qXGvWH_DISCARD_NEXT_END, GameLogic.php ~10454-10463) that fires at the next end phase --
// directly observable via card_exists in myGlobalEffects without needing to reach that end phase.
// Notes carried over from the original definition:
//   - ARCANE lineage/element unlock
//   - The Lorraine, Arclight Saber Subcards patch grants a lineage-inherited "Enlighten"
//   - ability, offered as a fast-action opportunity to both players once reserve is paid
//   - (same side effect as arcane-sight-level-draw) -- both decline.
gaScenario('arcane-disposition-draw-discard-flag')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myHand', 'Arcane Disposition')
    ->castSpell('Arcane Disposition', pay: 3, window: 'both')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'blq7qXGvWH')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'blq7qXGvWH_DISCARD_NEXT_END')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->register();

// --- Arcanist's Prism: at the beginning of recollection phase, wheel memory into deck, draw that many ---
// Arcanist's Prism is REGALIA -- adding it to 'myHand' via setup gets silently redirected to the
// Material zone instead (HandAddReplacement, GameLogic.php ~17282), matching every other REGALIA
// item fixture this session -- it must be seeded directly onto myField. Its recollection-phase
// trigger (GameLogic.php ~9044-9054, same unconditional per-field-card switch as Berserker
// Plate) puts all cards from memory on the bottom of the deck, then draws that many -- a filler
// card is seeded into myMemory so the effect has something to wheel. Same P1->P2->P1 cycle as
// berserker-plate-recollection-damage-draw to reach player 1's own recollection phase.
// Notes carried over from the original definition:
//   - Arcanist's Prism
//   - filler card to wheel into the deck
gaScenario('arcanists-prism-recollection-wheel-draw')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Arcanist\'s Prism')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Arcanist\'s Prism')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectZoneCount('myHand', 9, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Astral Seal: negate target activation, banish it (e3aebjvwbc) ---
// Play Dungeon Guide (materialize, 3 reserve), then in its Opportunity window the engine offers
// every fast-eligible hand card at once (MZMAYCHOOSE lists them "&"-joined) -- pick Astral Seal
// specifically among the offered candidates. Once Astral Seal's own 3 reserve is paid, its
// on-play trigger fires *immediately* off the same call (no further Opportunity-window passes
// needed -- negating something still mid-resolution doesn't open a new window) and queues the
// MZCHOOSE target choice right there, so it must be answered as the very next action.
// Notes carried over from the original definition:
//   - A real lineage-based unlock (not the self-consuming PRISMATIC_CODEX_IGNORE_ELEMENT
//   - bypass) -- that bypass is consumed once per non-NORM card *considered* while the engine
//   - assembles the opportunity-window candidate list, not just once per card actually played,
//   - so it silently starves out before reaching the real activation in a multi-candidate
//   - window. A permanent lineage unlock has no such budget.
//   - ASTRA lineage/element unlock
//   - Dungeon Guide (NORM ALLY, no element unlock needed), bait activation to negate
//   - Choose EffectStack-0 (Dungeon Guide) to negate/banish, not EffectStack-1 (Astral Seal's
//   - own still-resolving trigger entry, also offered as a technically-legal but wrong target).
gaScenario('astral-seal-negate-activation-banish')
    ->deck($fillerDeck)
    ->champion(lineage: ['Arisanna, Astral Zenith'])
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Astral Seal')
    ->play('Dungeon Guide')->pay(3)->target('myHand-4')->pay(3)->answer(1, 'EffectStack-0')
    ->tested('Astral Seal')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'e3aebjvwbc')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-3', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-4', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-5', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Banish-0', 'CardID', 'em6eEh9q8y', 'Playing Astral Seal fires its on-play trigger (playCardAbilities["e3aebjvwbc:0"]) and negates the targeted still-resolving activation, banishing the negated card instead of letting it resolve')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectZoneCount('myHand', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 6, 1)
    ->register();

// --- Annul Spell: negate target SPELL activation unless controller pays 3 (u817uqlk1j) ---
// Play Charge the Soul (materialize/activate, 1 reserve), then in its Opportunity window respond
// with Annul Spell (myHand-6 at this point). Once Annul Spell's own 3 reserve is paid, its
// on-play trigger fires immediately (no further Opportunity-window passes) and queues the
// MZCHOOSE target choice, which must be answered as the very next action. Since self-controller
// still has exactly 3 reserve-payable cards left (>= payAmount 3), the engine then asks Charge
// the Soul's controller (also player 1) whether to pay 3 to prevent the negate -- answer NO so
// the negate actually fires.
// Notes carried over from the original definition:
//   - Both u817uqlk1j (NORM) and its bait (Charge the Soul, also NORM) need no element unlock.
//   - Charge the Soul (NORM SPELL), bait activation to negate
gaScenario('annul-spell-negate-spell-activation')
    ->deck($fillerDeck)
    ->seed('myHand', 'Charge the Soul')
    ->seed('myHand', 'Annul Spell')
    ->play('Charge the Soul')->pay(1)->target('myHand-6')->pay(3)->answer(1, 'EffectStack-0')->answer(1, 'NO')
    ->tested('Annul Spell')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'u817uqlk1j')
    ->expectProperty('p1Graveyard-1', 'CardID', 'ra9950o14t', 'Playing Annul Spell fires its on-play trigger (playCardAbilities["u817uqlk1j:0"]); the target\'s controller declines to pay 3, so the targeted SPELL activation is negated into the graveyard instead of resolving')
    ->expectProperty('p1Graveyard-1', 'Counters', '[]')
    ->expectProperty('p1Graveyard-1', 'TurnEffects', '[]')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-3', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 3, 1)
    ->expectZoneCount('myGraveyard', 2, 1)
    ->expectZoneCount('myMemory', 4, 1)
    ->register();

// --- Accepted Contract: Put three preparation counters on your champion ---
// Accepted Contract's element is NORM, so no lineage patch is needed.
gaScenario('accepted-contract-prepare-three')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Accepted Contract')
    ->castSpell('Accepted Contract', pay: 5)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":3}', 'Puts three preparation counters on the champion')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'uZCyXDNJ6I')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-3', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-4', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 2, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 5, 1)
    ->register();

// --- Aqua Vitae: Sacrifice self -- draw a card, plus an extra if it had 3+ age counters ---
// Aqua Vitae is seeded directly onto the field with 3 age counters already on it (its own
// Sacrifice ability doesn't care how it got there). ActivatedAbilityCost's "sacrifice self"
// dispatch (GameLogic.php ~6454) stores the object's 'age' Counters value into the
// "ageCounters" decision-queue variable BEFORE removing it; activateAbilityAbilities
// ["y5ttkat9hr:0"] (GeneratedMacroCode.php) reads that variable and draws a second card only
// if it was >= 3. Verified live (see the sibling no-bonus fixture) that with 0 age counters
// only 1 card is drawn -- proving the age-counter condition actually gates the second draw,
// not just "always draws 2".
// Notes carried over from the original definition:
//   - Aqua Vitae - the activator
gaScenario('aqua-vitae-sacrifice-age-counters-double-draw')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->seed('myField', 'Aqua Vitae')
    ->patch('myField-1', ['Counters' => ['age' => 3]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Aqua Vitae')
    ->register();

// --- Aqua Vitae (negative path): Sacrifice with 0 age counters only draws 1 ---
// Notes carried over from the original definition:
//   - Aqua Vitae - the activator, 0 age counters (fresh)
gaScenario('aqua-vitae-no-age-bonus-single-draw')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->seed('myField', 'Aqua Vitae')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Aqua Vitae')
    ->expectZoneCount('myHand', 8, 1, 'With 0 age counters, Sacrifice draws only ONE card (7 -> 8), not two')
    ->register();

// --- Astromech Attendant: whenever you brew a Potion, draw a card into memory ---
// Astromech Attendant is seeded directly onto the field (its own On Enter Gather-3x isn't
// the clause under test here -- OnBrew's "whenever you brew" trigger is). The champion is
// CardID-patched to Arisanna, Astral Zenith (q3huqj5bba, CLERIC) so
// IsClassBonusActive($player,["CLERIC"]) is satisfied (OnBrew, PotionLogic.php ~247).
// Distilled Water (a NORM Potion, Brew -- One Herb) is seeded to hand along with its Herb
// ingredient (Blightroot) on the field; declaring Brew at materialize time (YES + choosing
// the herb) calls OnBrew($player), which -- because Astromech Attendant is on the field with
// its Class Bonus active -- draws a card into memory (same setup shape as
// distilled-water-brew-sacrifice-draw, but observing the OTHER field object's trigger
// instead of Distilled Water's own ability).
// Notes carried over from the original definition:
//   - Arisanna, Astral Zenith - CLERIC Class Bonus unlock
//   - Astromech Attendant - the trigger source
//   - Blightroot (HERB token) - brew ingredient
//   - Astromech Attendant's own presence offers a fast-action Sacrifice opportunity on the
//   - other herb tokens still on the field -- decline it so Distilled Water fully resolves
//   - off the effect stack and onto the field (otherwise the interaction is left mid-resolution).
gaScenario('astromech-attendant-brew-trigger-draw')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Arisanna, Astral Zenith')
    ->seed('myField', 'Astromech Attendant')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Distilled Water')
    ->play('Distilled Water')->answer(1, 'YES')->target('myField-2')->answer(1, '-')->passBoth()
    ->tested('Astromech Attendant')
    ->expectZoneCount('myMemory', 1, 1, 'Brewing a Potion with Astromech Attendant in play (Class Bonus active) draws a card into memory')
    ->expectProperty('p1Field-5', 'CardID', 'O1OU62Zx2Y', 'Distilled Water fully resolves onto the field after being brewed')
    ->expectDqEmpty()
    ->register();

// --- Altruistic Blacksmith: [Class Bonus] On Enter, each player summons a Cheap Sword token,
// you gain Crowd's Favor ---
// Bug fix under test: Altruistic Blacksmith's On Enter closure (enterAbilities["Pd4hj3sveV:0"],
// GeneratedCode/GeneratedMacroCode.php) calls SummonCheapSwordToken() for both players
// (GrandArchiveSim/Custom/GameLogic.php ~20210), which previously (a) seeded a placeholder
// token ID ("gfq3j98h8d") that does not correspond to any real card, and (b) called
// OnWeaponEntered(), a function that was never defined anywhere in the codebase -- triggering
// this ability fataled the engine with "Call to undefined function OnWeaponEntered()".
// Notes carried over from the original definition:
//   - Altruistic Blacksmith
gaScenario('altruistic-blacksmith-summon-cheap-sword')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Altruistic Blacksmith')
    ->castSpell('Altruistic Blacksmith', pay: 3)
    ->register();

// --- Acquiescing Rejection: Negate opponent activation unless they let you draw two ---
// (happy path -- controller lets the caster draw two, activation NOT negated)
// cardActivatedAbilities["qwtprd5b5r:0"] (GeneratedMacroCode.php) calls
// QueueNegateActivation($player, ["excludeController" => $player], "banish", -1,
// "NegateActivationDrawChoice") -- the excludeController filter means Acquiescing
// Rejection can only ever target an activation it does NOT control, so player 1
// activates Escharotomy (CIU4gT14EE, FIRE, reserve 1) as the bait; player 1's own
// champion Subcards is patched with Spirit of Fire (LMyKyVC2O9) to unlock FIRE for it.
// Player 2 controls Acquiescing Rejection and responds during the resulting fast-
// opportunity window (same "take a fast action instead of passing" shape as
// incapacitate-negate-action-activation); player 2's champion Subcards is patched with
// Spirit of Water (tafqldAGRF) to unlock WATER. Acquiescing Rejection's own reserve
// cost is 4, reduced to 3 by its [Class Bonus] (ClassBonusActivateCostReduction,
// GameLogic.php ~18626, keyed off IsClassBonusActive($player, ["CLERIC"]) which is true
// since Diao Chan, Enchantress is CLERIC). customDQHandlers["NegateActivationDrawChoice"]
// (OpportunityLogic.php ~538) asks Escharotomy's controller (player 1) "Let opponent
// draw two to prevent negate?" -- answering YES here means Draw($negatingPlayer=2, 2)
// runs and NegateCardActivation is skipped entirely, so Escharotomy stays on the effect
// stack and resolves normally afterward (its own "Target player cannot recover" MZMODAL
// fires, answered the same way as escharotomy-prevents-recover).
// Notes carried over from the original definition:
//   - Spirit of Fire - unlocks FIRE for Escharotomy (the bait)
//   - Escharotomy, the target activation
//   - Spirit of Water - unlocks WATER for Acquiescing Rejection
//   - The engine's starting-champion selection always picks a level-0 "Spirit" card from
//   - Material (GetStartingChampionChoices, GameLogic.php ~1042) over any level-1 named
//   - champion, so Diao Chan, Enchantress never lands on myField-0 by itself. Diao Chan,
//   - Enchantress (CLERIC) is instead seeded directly onto the field as a second,
//   - independent CHAMPION-type object purely as an IsClassBonusActive($player, ["CLERIC"])
//   - source (same "extra champion seeded onto the field for Class Bonus" technique as the
//   - Nameless Champion/Ciel/Kongming precedents elsewhere in this file) -- it lands at
//   - myField-1 and never interferes with myField-0's own lineage/element resolution.
//   - Diao Chan, Enchantress (CLERIC) - Class Bonus source
//   - Acquiescing Rejection, the negator
//   - play Escharotomy
//   - pay 1 reserve
//   - player 1 passes their own opportunity
//   - player 2 takes a fast action instead of passing: Acquiescing Rejection
//   - pay reserve 1/3 (Class Bonus discount already applied)
//   - pay reserve 2/3
//   - pay reserve 3/3
//   - choose Escharotomy's activation to negate
//   - Escharotomy's controller lets player 2 draw two instead of being negated
//   - Escharotomy resolves normally: "Choose_a_player" modal, pick B (opponent)
gaScenario('acquiescing-rejection-draw-two-not-negated')
    ->deck($diaoChanDeck)
    ->champion(lineage: ['Spirit of Fire'])
    ->seed('myHand', 'Escharotomy')
    ->champion(lineage: ['Spirit of Water'], player: 2)
    ->seed('myField', 'Diao Chan, Enchantress', player: 2)
    ->seed('myHand', 'Acquiescing Rejection', player: 2)
    ->play('Escharotomy')->pay(1)->pass(1)->answer(2, 'myHand-7')->pay(3, 2)->answer(2, 'EffectStack-0')
    ->answer(1, 'YES')->answer(1, 'B')
    ->tested('Acquiescing Rejection')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-5', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Graveyard-0', 'CardID', 'CIU4gT14EE', 'Escharotomy (the negation target) is NOT negated -- it resolves to its controller\'s ordinary Graveyard (not Banish), because its controller chose to let the caster draw two instead')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p2Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-0', 'CardID', 'qwtprd5b5r')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Memory-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2GlobalEffects-0', 'CardID', 'CANT_RECOVER', 'Escharotomy\'s own \'target player cannot recover\' ability actually fired (CANT_RECOVER global effect landed on player 2, the chosen \'B: Opponent\' target) -- proof it was never negated off the effect stack')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 1, 1)
    ->expectZoneCount('theirHand', 6, 1, 'Acquiescing Rejection\'s caster (player 2) drew exactly two cards (hand went from 4 to 6 cards) as the \'controller lets you draw two\' alternative to negating')
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 3, 1)
    ->register();

// Advent of the Shenju: [Class Bonus][Element Bonus] whenever you reveal it from memory, may
// banish all cards in memory and draw a card into memory for each one banished.
// Notes carried over from the original definition:
//   - the champion's element must match the card's (LUXEM) for the Element Bonus
//   - Uncover the Plot targeting yourself reveals the whole memory zone.
//   - Advent of the Shenju's own reveal trigger: "Banish all memory and draw into memory?"
gaScenario('advent-of-the-shenju-reveal-banish-memory-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->patch('myField-0', ['CardID' => 'j6dkdoxyqt', 'Subcards' => ['UAF6Nr7GUE'], 'Counters' => ['_overrides' => ['element' => 'LUXEM']]])
    ->seed('myMemory', 'Advent of the Shenju')
    ->seed('myMemory', 'Dungeon Guide')
    ->patch('myHand-0', ['CardID' => '4zkTRt8qXn'])
    ->playSlot(0)->pay(2)->answer(1, 'YES')->answer(1, 'YES')
    ->tested('Advent of the Shenju')
    ->register();

// Advent of the Shenju: "[Guo Jia Bonus] Put target Fatestone card from your banishment onto the
// field. If that card is a regalia, put five quest counters on your champion." (Hand-authored
// GrandArchiveSim/Custom/GameLogic.php cardActivatedAbilities["c53tomoaw3:0"] plus
// customDQHandlers["c53tomoaw3:0:GuoJiaBonus-1"] -- no CardEditor ability database row was
// reachable in this sandbox; see the block comment there for the full explanation.) This fixture
// covers the non-regalia branch: Craggy Fatestone (h8n1520m2d, plain ITEM) moves from banishment
// to the field and NO quest counters are added.
// Notes carried over from the original definition:
//   - Craggy Fatestone (ITEM, TAMER/FATESTONE, NOT regalia) seeded straight into banishment.
//   - Play Advent of the Shenju (4 reserve, ACTION card, resolves via cardActivatedAbilities).
//   - pay reserve 1/4
//   - pay reserve 2/4
//   - pay reserve 3/4
//   - pay reserve 4/4
//   - MZCHOOSE: target Craggy Fatestone in banishment (the only legal Fatestone target). No
//   - fast-action pass window appears here (this filler deck has no fast-speed responses), so
gaScenario('advent-of-the-shenju-guo-jia-bonus-fatestone-recursion')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple', lineage: ['Zander, Blinding Steel'])
    ->seed('myBanish', 'Craggy Fatestone')
    ->patch('myHand-0', ['CardID' => 'c53tomoaw3'])
    ->playSlot(0)->pay(4)->target('myBanish-0')
    ->tested('Advent of the Shenju')
    ->register();

// Advent of the Shenju: same clause, regalia branch. Fabled Azurite Fatestone (6ce5rzrjd9,
// REGALIA,ITEM) moves from banishment to the field AND five quest counters land on the champion.
// Notes carried over from the original definition:
//   - Fabled Azurite Fatestone (REGALIA,ITEM, TAMER/FATESTONE) seeded straight into banishment.
//   - pay reserve 1/4
//   - pay reserve 2/4
//   - pay reserve 3/4
//   - pay reserve 4/4
//   - MZCHOOSE: target Fabled Azurite Fatestone in banishment (the only legal Fatestone
//   - target). No fast-action pass window appears here (this filler deck has no fast-speed
//   - responses), so the ability's own targeting decision resolves immediately after payment.
gaScenario('advent-of-the-shenju-guo-jia-bonus-regalia-quest-counters')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple', lineage: ['Zander, Blinding Steel'])
    ->seed('myBanish', 'Fabled Azurite Fatestone')
    ->patch('myHand-0', ['CardID' => 'c53tomoaw3'])
    ->playSlot(0)->pay(4)->target('myBanish-0')
    ->tested('Advent of the Shenju')
    ->register();

// --- Another Beginning (Akf4kIBApN): Materialize a regalia card with memory cost 0 from your material deck ---
gaScenario('another-beginning-materialize-free-regalia')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Poisoned Dagger
# Main
4 Another Beginning
10 Dungeon Guide
DECK
    )
    ->playSlot(0)->pay(3)->target('myMaterial-0')->passBoth()
    ->tested('Another Beginning')
    ->register();

// --- Arrow Trap: return target attacking ally to its owner's hand (unprepared, no preparation counter to pay) ---
// Arrow Trap (uoQGe5xGDQ, WIND action, REACTION, reserve 2): "Prepare 1. Return target attacking
// ally to its owner's hand. Class Bonus: If Arrow Trap was prepared, destroy that ally instead."
// Player 2 attacks player 1's champion (Tristan, Hired Blade, an ASSASSIN, no preparation
// counters so Prepare 1 is not offered) with a seeded Dungeon Guide; player 1 answers the attack's
// priority window by playing Arrow Trap from hand (two reserve payments). The ability resolves
// ArrowTrapResolve -> GetCombatAttackerMZ() (CombatAttacker is stored relative to the ATTACKER,
// 'myField-1' for player 2, and re-localized to the defender through CombatAttackerPlayer) and
// returns the attacking Dungeon Guide to its OWNER's (player 2's) hand; the attack is over, so the
// champion takes no damage. (Previously the raw 'myField-1' was resolved from the defender's
// perspective, found nothing, and the attacker was never returned -- fixed.)
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (ASSASSIN), no preparation counters
//   - Arrow Trap -> p1 myHand-7
//   - Dungeon Guide (attacker) -> p2 field-1
//   - end player 1 turn
//   - p1 passes a window
//   - p1 passes a window
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion
//   - player 1 plays Arrow Trap from the priority window
gaScenario('arrow-trap-return-attacking-ally-unprepared')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myHand', 'Arrow Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myHand-7')->pay(2)
    ->tested('Arrow Trap')
    ->register();

// --- Arrow Trap: Prepare 1 paid -> [Class Bonus] destroy the attacking ally instead of returning it ---
// Arrow Trap (uoQGe5xGDQ, WIND action, REACTION, reserve 2): "Prepare 1. Return target attacking
// ally to its owner's hand. Class Bonus: If Arrow Trap was prepared, destroy that ally instead."
// Same attack as arrow-trap-return-attacking-ally-unprepared, but the champion (Tristan, Hired
// Blade, an ASSASSIN so the Class Bonus is active) holds exactly one preparation counter. The
// ability now queues the optional 'Pay Prepare 1?' YES/NO (generic DeclarePrepareCost) before
// resolving; player 1 answers YES, the counter is removed, and the attacking Dungeon Guide is
// DESTROYED (goes to its owner's graveyard) rather than returned to hand. (Previously the Prepare
// 1 prompt was never queued and the attacker was never affected -- both fixed.)
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (ASSASSIN) with ONE preparation counter (enough to Prepare 1)
//   - Arrow Trap -> p1 myHand-7
//   - Dungeon Guide (attacker) -> p2 field-1
//   - end player 1 turn
//   - p1 passes a window
//   - p1 passes a window
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion
//   - player 1 plays Arrow Trap from the priority window
//   - pay Prepare 1 (remove the preparation counter)
gaScenario('arrow-trap-prepared-destroy-attacking-ally')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 1]])
    ->seed('myHand', 'Arrow Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myHand-7')->pay(2)->answer(1, 'YES')
    ->tested('Arrow Trap')
    ->expectProperty('p1Graveyard-0', 'CardID', 'uoQGe5xGDQ', 'Arrow Trap was activated and paid for (it reached the graveyard)')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Prepare 1 was paid: the champion\'s preparation counter was removed')
    ->expectZoneCount('theirField', 1, 1, '[Class Bonus] The prepared Arrow Trap destroyed the attacking Dungeon Guide (opponent field is just their champion)')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The destroyed attacker is in its OWNER\'s graveyard')
    ->expectZoneCount('theirHand', 8, 1, 'It was destroyed instead of returned: the opponent\'s hand did not grow (8)')
    ->expectDamage('p1Field-0', 0, 'The attack was cancelled: player 1\'s champion took no damage')
    ->expectDqEmpty()
    ->register();

// --- Arrow Trap: Prepare 1 offered but declined -> the attacking ally is returned to hand, not destroyed ---
// Arrow Trap (uoQGe5xGDQ): same setup as arrow-trap-prepared-destroy-attacking-ally (champion has
// one preparation counter, Class Bonus active), but player 1 answers NO to 'Pay Prepare 1?'. Arrow
// Trap is then NOT prepared, so the Class Bonus "destroy instead" does not apply: the attacking
// Dungeon Guide returns to its owner's hand and the preparation counter is kept.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (ASSASSIN) with ONE preparation counter
//   - Arrow Trap -> p1 myHand-7
//   - Dungeon Guide (attacker) -> p2 field-1
//   - end player 1 turn
//   - p1 passes a window
//   - p1 passes a window
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion
//   - player 1 plays Arrow Trap from the priority window
//   - decline Prepare 1
gaScenario('arrow-trap-prepare-declined-returns-attacking-ally')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 1]])
    ->seed('myHand', 'Arrow Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myHand-7')->pay(2)->answer(1, 'NO')
    ->tested('Arrow Trap')
    ->expectProperty('p1Graveyard-0', 'CardID', 'uoQGe5xGDQ', 'Arrow Trap was activated and paid for (it reached the graveyard)')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'Prepare 1 was declined: the champion keeps its preparation counter')
    ->expectZoneCount('theirField', 1, 1, 'Not prepared, so the attacking Dungeon Guide left the opponent\'s field (only their champion remains)')
    ->expectZoneCount('theirHand', 9, 1, '[Class Bonus] does not apply when not prepared: the ally was RETURNED to its owner\'s hand (8 + 1)')
    ->expectZoneCount('theirGraveyard', 0, 1, 'It was not destroyed: the opponent\'s graveyard is empty')
    ->expectDqEmpty()
    ->register();

// --- Aethercloak Sentinel: printed Spellshroud -- an opposing spell cannot target it ---
// Aethercloak Sentinel (1mvv1f83ls, ALLY): "Ranged 4, Spellshroud, Taunt" -- the Spellshroud is the card's
// own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords >
// Spellshroud 1: "This can't be targeted by spells"). It was never implemented: HasSpellshroud()
// (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID list and
// HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword,
// so function_exists is false); 1mvv1f83ls was in neither, so FilterSpellshroudTargets() kept the card in the
// target list. Fixed: it is now in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities /
// NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff counter
// on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, answering with the
// holder is refused (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('aethercloak-sentinel-printed-spellshroud-spell-cannot-target-it', ['Aethercloak Sentinel'], 'Liturgy of Corruption')
    ->register();

// --- Atmos Armor Type-Hermes: printed Spellshroud -- an opposing spell cannot target it ---
// Atmos Armor Type-Hermes (dlx7mdk0xh, UNIQUE,ALLY): "Spellshroud (Units with spellshroud can't be targeted by
// Spells.)" -- the Spellshroud is the card's own, UNCONDITIONAL keyword (rules:
// .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords > Spellshroud 1: "This can't be targeted by
// spells"). It was never implemented: HasSpellshroud() (Custom/GameLogic.php) recognised printed Spellshroud
// only through a hard-coded card-ID list and HasKeyword_Spellshroud(), which is never generated
// (Data/ProcessKeywordsGA.php has no Spellshroud keyword, so function_exists is false); dlx7mdk0xh was in
// neither, so FilterSpellshroudTargets() kept the card in the target list. Fixed: it is now in
// HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities / NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff counter
// on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, answering with the
// holder is refused (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('atmos-armor-type-hermes-printed-spellshroud-spell-cannot-target-it', ['Atmos Armor Type-Hermes'], 'Liturgy of Corruption')
    ->register();

// --- Aquamirage Whisper: printed Spellshroud -- an opposing spell cannot target it ---
// Aquamirage Whisper (3n5x9fbkn0, REGALIA,WEAPON): "Spellshroud (This object can't be targeted by Spells.)" --
// the Spellshroud is the card's own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-
// rules.md, Keywords > Spellshroud 1: "This can't be targeted by spells"). It was never implemented:
// HasSpellshroud() (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID
// list and HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud
// keyword, so function_exists is false); 3n5x9fbkn0 was in neither, so FilterSpellshroudTargets() kept the
// card in the target list. Fixed: it is now in HasSpellshroud()'s printed-Spellshroud table (after the
// HasNoAbilities / NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Mirrordepth's Blade; player 1 plays Fracturize ("Target item or weapon
// becomes a Cleric Fractal phantasia ...", a Spell): the target prompt offers ONLY the Mirrordepth's Blade,
// answering with the holder is refused (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('aquamirage-whisper-printed-spellshroud-spell-cannot-target-it', ['Aquamirage Whisper'], 'Fracturize', holderCounters: ['{"durability":3}'])
    ->register();

// --- Ashen Riffle: declining the optional banish still puts ALL FOUR revealed cards back on the bottom of the deck (dontSkipOnPass regression;
// renamed from ashen-riffle-decline-returns-revealed-card, whose snapshot pinned the single-card reveal of the fixed-index loop bug) ---
// Ashen Riffle (fjpimrl974): "Reveal the top four cards of your deck. Banish up to two Suited non-action cards revealed this way and put the rest on the bottom of your deck in any order. ..." Declining the optional banish must still put the revealed card(s) on the bottom of the deck instead of stranding them.
// Regression: AshenRiffleChoose() queued its paired CUSTOM 'AshenRiffleChoose|N' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and AshenRiffleCleanup() never ran: the revealed card stayed in the temp zone and the deck stayed one card short (4 instead of 5).
// A Suited non-action card (Noire, Ace of Spades, an ALLY) is seeded at the top of the deck so the optional banish prompt is offered; Ashen Riffle (FIRE, native to Spirit of Fire) is played from hand and the banish is declined. The deck holds only five cards, so the four revealed cards go to the bottom BEHIND the one unrevealed card (which stays on top).
// Notes carried over from the original definition:
//   - Ashen Riffle -> myHand-7
//   - top card: Suited non-action ALLY
//   - second card: Suited non-action ALLY
//   - reserve 1/2
//   - reserve 2/2
//   - decline banishing a Suited card
gaScenario('ashen-riffle-small-deck-decline-returns-all-four-revealed-cards')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Ashen Riffle')
    ->patch('myDeck-0', ['CardID' => 'wbjc9t8ycp'])
    ->patch('myDeck-1', ['CardID' => 'wbjc9t8ycp'])
    ->castSpell('Ashen Riffle', pay: 2, window: 'me')
    ->register();

// --- Assemble the Ancients: declining a further domain sacrifice still summons the Drones (dontSkipOnPass regression) ---
// Assemble the Ancients (moi0a5uhjx): "Sacrifice any number of domains then summon that many Automaton Drone tokens. Each of those tokens enters the field rested with an amount of buff counters on it equal to the amount of domains sacrificed this way. Then they gain vigor until end of turn." Sacrificing fewer than all domains (declining the 'Sacrifice a domain?' prompt) must still summon one Drone per domain sacrificed.
// Regression: AssembleAncientsSacrifice() queued its paired CUSTOM 'AssembleAncientsSacChoice|N' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its PASS branch (AssembleAncientsFinalize: summon the tokens) never ran: one domain was sacrificed and then the Drone was never summoned.
// Two domains (Palatial Concourse) are seeded; one is sacrificed, the second declined with PASS. NEOS element access via a Subcards patch (same as assemble-the-ancients-domain-sacrifice-tokens).
// Notes carried over from the original definition:
//   - NEOS lineage/element unlock (Tonoris, Creation's Will)
//   - Palatial Concourse (DOMAIN) #1 -> myField-1
//   - Palatial Concourse (DOMAIN) #2 -> myField-2
//   - reserve 1/3
//   - reserve 2/3
//   - reserve 3/3
//   - sacrifice domain #1
//   - decline sacrificing the second domain
gaScenario('assemble-the-ancients-decline-second-domain-still-summons')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Tonoris, Creation\'s Will'])
    ->seed('myField', 'Palatial Concourse')
    ->seed('myField', 'Palatial Concourse')
    ->seed('myHand', 'Assemble the Ancients')
    ->play('Assemble the Ancients')->pay(3)->target('myField-1')->pass(1)
    ->expectZoneCount('myField', 3, 1, 'Champion + the unsacrificed domain + 1 Automaton Drone token summoned for the 1 domain sacrificed')
    ->expectProperty('p1Field-2', 'CardID', 'mu6gvnta6q', 'The summoned token is an Automaton Drone')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'The Drone enters with 1 buff counter (1 domain sacrificed)')
    ->expectProperty('p1Field-2', 'Status', '1', 'The Drone enters rested')
    ->expectDqEmpty()
    ->register();

// --- Argus, All-Seeing Giant: declining the material-deck banish still pays the full reserve cost (dontSkipOnPass regression) ---
// Argus, All-Seeing Giant (4GFKcHg9NU): "While paying for this card's reserve cost, you may banish one or more cards named Crystal of Argus or Eye of Argus from your material deck. Each card banished this way pays for 3 of that cost." Declining the optional banish means the whole reserve cost (10) is paid the normal way.
// Regression: ArgusReserveChoice queued its paired CUSTOM 'ArgusReserveApply' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and the 10 ReserveCard payments + EffectStackOpportunity were never queued: Argus sat on the effect stack with an empty decision queue and could never be paid for (a soft-lock). Fixing the flag alone was not enough: the decline branch queues unflagged CUSTOM reserve payments that would be skipped while lastDecision is still 'PASS', so it now queues a PASSPARAMETER '-' first (the idiom KindleProcess already uses).
// Eye of Argus is added to the material deck so the optional banish is offered; the champion gets a LUXEM Subcards lineage so the Exalted element is enabled; four filler cards are added to the hand so all 10 reserve payments can be made from it. The banish is declined and the 10 payments are made.
// Notes carried over from the original definition:
//   - LUXEM lineage -> enables EXALTED for Argus (EXALTED/NORM)
//   - Argus, All-Seeing Giant -> myHand-7
//   - decline banishing Eye of Argus from the material deck to pay 3
//   - pay reserve 1/10
//   - pay reserve 2/10
//   - pay reserve 3/10
//   - pay reserve 4/10
//   - pay reserve 5/10
//   - pay reserve 6/10
//   - pay reserve 7/10
//   - pay reserve 8/10
//   - pay reserve 9/10
//   - pay reserve 10/10
gaScenario('argus-decline-material-banish-pays-full-reserve-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
1 Eye of Argus
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(lineage: ['Zander, Blinding Steel'])
    ->seed('myHand', 'Argus, All-Seeing Giant')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Argus, All-Seeing Giant')->pass(1)->pay(10)
    ->expectProperty('p1Field-1', 'CardID', '4GFKcHg9NU', 'Argus resolved and entered the field after the full 10 reserve cost was paid by hand cards')
    ->expectZoneCount('myMemory', 10, 1, 'All 10 reserve payments were made (10 cards in memory) -- the Eye of Argus was not banished')
    ->expectZoneCount('myMaterial', 4, 1, 'The declined Eye of Argus is still in the material deck (4 left after the starting champion is on the field)')
    ->expectDqEmpty()
    ->register();

// top four, top to bottom: Dungeon Guide, Two of Spades (eligible), Suited Trickery (suited ACTION: not eligible), Three of Spades (eligible)
// Notes carried over from the original definition:
//   - banish Two of Spades
//   - banish Three of Spades (now the 3rd card: Dungeon Guide, Suited Trickery, Three of Spades)
gaScenario('ashen-riffle-reveal-four-banish-two-suited-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ashen Riffle')
    ->deckTop(['Dungeon Guide', 'Two of Spades', 'Suited Trickery', 'Three of Spades'])
    ->play('Ashen Riffle')->pay(2)->target('myTempZone-1')->target('myTempZone-2')
    ->register();

// the ONLY eligible card is the fourth from the top: it can only be offered if all four were revealed
// Notes carried over from the original definition:
//   - banish Four of Spades (4th card)
gaScenario('ashen-riffle-reveal-four-deep-suited-offered')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ashen Riffle')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Four of Spades'])
    ->castSpell('Ashen Riffle', pay: 2, target: 'myTempZone-3')
    ->register();

gaScenario('ashen-riffle-reveal-four-decline-bottoms-all-four')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ashen Riffle')
    ->deckTop(['Dungeon Guide', 'Two of Spades', 'Suited Trickery', 'Three of Spades'])
    ->castSpell('Ashen Riffle', pay: 2, window: 'me')
    ->register();

// Notes carried over from the original definition:
//   - banish Two of Spades
//   - decline the second banish: Three of Spades goes to the bottom with the rest
gaScenario('ashen-riffle-reveal-four-banish-one-then-decline-second')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ashen Riffle')
    ->deckTop(['Dungeon Guide', 'Two of Spades', 'Suited Trickery', 'Three of Spades'])
    ->play('Ashen Riffle')->pay(2)->target('myTempZone-1')->pass(1)
    ->register();

// three eligible cards (slots 0, 1, 2): "up to two" -> the third (Four of Spades) is never offered and goes to the bottom
// Notes carried over from the original definition:
//   - banish Two of Spades
//   - banish Three of Spades (now the top of the three left)
gaScenario('ashen-riffle-reveal-four-three-eligible-banish-capped-at-two')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ashen Riffle')
    ->deckTop(['Two of Spades', 'Three of Spades', 'Four of Spades', 'Dungeon Guide'])
    ->play('Ashen Riffle')->pay(2)->target('myTempZone-0')->target('myTempZone-0')
    ->register();

// PLAYER 2 seating (perspective check): the stack sits under player 2's turn-start draw (a filler Dungeon Guide); player 2 first
// declines the two opportunity windows player 1's pass opens
gaScenario('ashen-riffle-player-two-reveal-four-banish-two-suited-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ashen Riffle', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Two of Spades', 'Suited Trickery', 'Three of Spades'], player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Ashen Riffle', 2)->pay(2, 2)->answer(2, 'myTempZone-1')->answer(2, 'myTempZone-2')
    ->register();

// --- Augustine, Votary of Yore (KdlhoQ1evn): "[REST]: Cascade -- 1: Up to one target object loses spellshroud and stealth until the end of your next turn. Glimpse 2. 2: As a Spell,
// destroy target item or weapon with memory cost 0 or reserve cost 4 or less. If you do, its controller summons a Core Fractal token." The generated await frame's step 2 called
// AugustineStepTwoTargets(), which used CardReserveCost() (does not exist: fatal) and CardMemoryCost(<CardID string>) (the function takes the zone object). The cascade counter is
// seeded to 1 so the next activation is step 2. The opponent controls Scepter of Lumina (memory cost 1, no reserve cost), Explosive Concoction (reserve 7, no memory cost) and
// Forest Cake (reserve 2): only Forest Cake is a legal target. ---
// Notes carried over from the original definition:
//   - Augustine -> field-1
//   - the next activation is cascade step 2
//   - Scepter of Lumina (memory cost 1, no reserve cost) -> theirField-1
//   - Explosive Concoction (reserve 7, no memory cost) -> theirField-2
//   - Forest Cake (reserve 2) -> theirField-3
//   - Forest Cake: reserve cost 2
//   - the ability-resolution response window
gaScenario('augustine-cascade-two-destroys-low-cost-item-opponent-summons-core-fractal')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Augustine, Votary of Yore')
    ->patch('myField-1', ['Counters' => ['cascade' => 1]])
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Explosive Concoction', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->rejects(1, 'theirField-2', 'Explosive Concoction has reserve cost 7 and no memory cost: not a legal target')
    ->target('theirField-3')->pass(1)
    ->tested('Augustine, Votary of Yore')
    ->register();

// top three: Rolling Chorus (ARCANE), Dungeon Guide, Rolling Chorus (ARCANE); then a Charge the Soul marker
gaScenario('advent-of-the-stormcaller-reveals-three-banishes-two-arcane-damages-two-units-rearranges-rest')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon', lineage: ['Lorraine, Arclight Saber'])
    ->emptyZone('myHand', destZone: 'myGraveyard')
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
    ->seed('myHand', 'Advent of the Stormcaller')
    ->deckTop(['Rolling Chorus', 'Dungeon Guide', 'Rolling Chorus', 'Charge the Soul'])
    ->playSlot(12)->pay(12)->target('myTempZone-0')->target('myTempZone-1')->target('theirField-0')
    ->target('theirField-0')->answer(1, 'Top=;Bottom=em6eEh9q8y')
    ->tested('Advent of the Stormcaller')
    ->register();

// "You may banish any amount": decline; the three revealed cards are rearranged (all three back on top in the same order)
gaScenario('advent-of-the-stormcaller-decline-banish-rearranges-all-three-no-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon', lineage: ['Lorraine, Arclight Saber'])
    ->emptyZone('myHand', destZone: 'myGraveyard')
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
    ->seed('myHand', 'Advent of the Stormcaller')
    ->deckTop(['Rolling Chorus', 'Dungeon Guide', 'Rolling Chorus', 'Charge the Soul'])
    ->playSlot(12)->pay(12)->pass(1)->answer(1, 'Top=IZVXDYjcSL,em6eEh9q8y,IZVXDYjcSL;Bottom=')
    ->tested('Advent of the Stormcaller')
    ->register();

// Notes carried over from the original definition:
//   - Fast Cure (floating memory) -> myGraveyard-0
//   - Fast Cure (floating memory) -> myGraveyard-1
gaScenario('arondight-azure-blade-enter-banish-one-floating-then-decline-keeps-one-refinement')
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
    ->seed('myGraveyard', 'Fast Cure')
    ->seed('myGraveyard', 'Fast Cure')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-0')->answer(1, '-')->target('myGraveyard-0')->pass(1)
    ->tested('Arondight, Azure Blade')
    ->register();

// Alkahest (xfpk9xycwz): "[Level 4+] Banish CARDNAME: Destroy target item or weapon with memory cost 0 or reserve cost 4 or less." At level 4 the ability banishes Alkahest as its cost; the memory-only Scepter of Lumina
// (memory cost 1, no reserve cost) is not a legal target, the Forest Cake (reserve cost 2) is.
// Notes carried over from the original definition:
//   - Alkahest -> myField-1
//   - Scepter of Lumina (memory cost 1, no reserve cost) -> theirField-1
//   - Forest Cake (reserve cost 2) -> theirField-2
//   - Forest Cake is destroyed
gaScenario('alkahest-level-four-banishes-itself-to-destroy-a-low-cost-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(level: 4)
    ->seed('myField', 'Alkahest')
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->target('theirField-2')
    ->tested('Alkahest')
    ->expectZoneCount('myBanish', 1, 1, 'Alkahest was banished as the cost')
    ->expectZoneCount('myField', 1, 1, 'Only the champion is left on player 1\'s field')
    ->expectZoneCount('theirField', 2, 1, 'Player 2\'s champion and the memory-only Scepter remain: the Forest Cake (reserve cost 2) was destroyed')
    ->register();

// Advantageous Perch (oLzsAj9mKl): "Your champion becomes distant. Then return up to one target Ranger action card from your graveyard to your hand. Banish CARDNAME."
// Notes carried over from the original definition:
//   - RANGER class; another advanced element (EXALTED) for Exalted
//   - Charge the Soul (Ranger action) -> myGraveyard-0
//   - Advantageous Perch -> myHand-7
gaScenario('advantageous-perch-distant-returns-a-ranger-action-and-banishes-itself')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Mordred, Fated Luminary'], classes: 'RANGER')
    ->seed('myGraveyard', 'Charge the Soul')
    ->seed('myHand', 'Advantageous Perch')
    ->castSpell('Advantageous Perch', pay: 2, target: 'myGraveyard-0')
    ->register();

// Agni's Signet (yIozXMrdqr): "(2), [REST], Banish Agni's Signet: As a Spell, destroy target damaged ally."
// Notes carried over from the original definition:
//   - Agni's Signet -> myField-1
//   - Hindered: enters rested, so wake it
//   - theirField-1 (damaged below)
//   - undamaged ally: not a legal target
gaScenario('agnis-signet-pays-two-and-destroys-a-damaged-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Agni\'s Signet')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Damage' => 1], player: 2)
    ->seed('myField', 'Fluffy Shopkeep', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('theirField-1')
    ->tested('Agni\'s Signet')
    ->register();

gaAbilitySandbox('aquatech-blade-x-ability-1-pays-its-printed-cost', 'AquaTech Blade X', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myGraveyard-1')->pass(1)
    ->tested('AquaTech Blade X')
    ->register();

gaAbilitySandbox('avatar-of-suzaku-ability-1-pays-its-printed-cost', 'Avatar of Suzaku', classes: 'MAGE', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->pass(1)
    ->tested('Avatar of Suzaku')
    ->expectCostFootprint(hand: 11, memory: 4, banish: 0, graveyard: 4, field: 6)
    ->register();

gaAbilitySandbox('avatar-of-genbu-ability-1-pays-its-printed-cost', 'Avatar of Genbu', classes: 'GUARDIAN', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->pass(1)
    ->tested('Avatar of Genbu')
    ->expectCostFootprint(hand: 11, memory: 4, banish: 0, graveyard: 4, field: 6)
    ->register();

// Aetheric Calibration (7l9th23niu): "Glimpse 4. Then reveal the top card of your deck. If that card is an Aethercharge card, put it into your memory. You may load Aetheric Calibration into an Aetherwing weapon you control."
gaScenario('aetheric-calibration-glimpse-four-then-top-aethercharge-goes-to-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myHand', 'Aetheric Calibration')
    ->deckTop(['Aether\'s Embrace', 'Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'])
    ->play('Aetheric Calibration')->pay(2)->answer(1, 'Top=wd7nuab7f3,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->answer(1, 'YES')
    ->register();

// Aenean Swelling Gusts (nbznVwdylT): "Deal X damage to target unit, where X is the amount of wind element objects you control." The handler read $windCount (undefined): 0 damage. Spirit of Wind and a
// Windrider Invoker are the two wind objects: 2 damage.
// Notes carried over from the original definition:
//   - Windrider Invoker (wind) -> field-1
gaScenario('aenean-swelling-gusts-deals-damage-equal-to-wind-objects-you-control')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Aenean Swelling Gusts')
    ->seed('myField', 'Windrider Invoker')
    ->castSpell('Aenean Swelling Gusts', pay: 4, target: 'theirField-0')
    ->register();

// Arrest Lightning (9e3B8EHQak): "[Class Bonus] [Element Bonus] (1), Banish this card from your graveyard: Put a static counter on target arcane element object you control and each object linked to it." With the Warrior class
// bonus and an ARCANE champion (Lorraine, Arclight Saber) the graveyard ability costs one reserve and banishes itself.
// Notes carried over from the original definition:
//   - a Warrior champion whose element matches Arrest Lightning's (ARCANE)
//   - Storm Slime (ARCANE ally) -> myField-1
//   - Arrest Lightning -> myGraveyard-0
gaScenario('arrest-lightning-graveyard-static-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'WARRIOR', 'element' => 'ARCANE']]])
    ->seed('myField', 'Storm Slime')
    ->seed('myGraveyard', 'Arrest Lightning')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(1)->target('myField-1')
    ->tested('Arrest Lightning', 'Storm Slime')
    ->register();

// Arrest Lightning: without a matching champion element the graveyard ability is refused (the Element Bonus gate).
// Notes carried over from the original definition:
//   - class matches, element (FIRE) does not
gaScenario('arrest-lightning-graveyard-ability-refused-without-matching-element')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'WARRIOR')
    ->seed('myField', 'Fulgurite Coordinator')
    ->seed('myGraveyard', 'Arrest Lightning')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->target('myField-0')
    ->tested('Arrest Lightning', 'Fulgurite Coordinator')
    ->expectZoneCount('myGraveyard', 1, 1, 'Arrest Lightning stays in the graveyard: the champion is FIRE, not ARCANE')
    ->expectZoneCount('myBanish', 0, 1, 'nothing was banished')
    ->expectZoneCount('myMemory', 0, 1, 'nothing was paid')
    ->expectDqEmpty()
    ->register();
