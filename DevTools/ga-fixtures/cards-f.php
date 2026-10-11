<?php
/**
 * Fixtures for cards starting with 'F', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Cascade: FlameTech Manual activates Cascade ---
// Cascade's activation prereq (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php,
// activateAbilityPrereqs["WZJxZMBAir:0"]) requires (a) a MAGE Class Bonus — no level 0
// starting champion has a class other than SPIRIT, so a MAGE champion is seeded directly
// onto the field — and (b) a FIRE card already in the graveyard, also seeded directly.
// Play FlameTech Manual (0 cost regalia; lands on myField-2 behind the two seeded objects),
// then activate its Cascade ability via mode 10001 CustomInput (field abilities are NOT
// reachable via a plain mode 10002 FSM click — that only covers materialize/attack).
// Notes carried over from the original definition:
//   - Rai, Spellcrafter (MAGE champion) - Class Bonus source
//   - Escharotomy (FIRE) - graveyard prereq
//   - Play FlameTech Manual (0 cost, goes to field)
//   - Pass fast actions
//   - Activate Cascade on FlameTech Manual (now at myField-2)
//   - pay the "banish a fire element card from your graveyard" cost
//   - Cascade deals 2 damage to a target champion — hit the opponent's.
//   - Pass remaining fast action opportunities
gaScenario('flametech-manual-cascade-activate')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 FlameTech Manual
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Escharotomy
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK
    )
    ->seed('myField', 'Rai, Spellcrafter')
    ->seed('myGraveyard', 'Escharotomy')
    ->playSlot(3)->passBoth()
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myGraveyard-0')->target('theirField-0')
    ->passBoth()
    ->tested('FlameTech Manual')
    ->register();

// --- Static Counter: Fulgurite Coordinator banished from graveyard adds a static
// counter to an arcane object you control ---
//
// NOTE on the fixture name/original premise: Fulgurite Coordinator (7aZwqrfbzO) IS ARCANE
// element and DOES have a real "enters the field with a static counter on itself" ability
// (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, $enterAbilities["7aZwqrfbzO:0"]),
// dispatched from FireEnterTriggeredAbility/QueueEnterTriggeredAbility in
// GrandArchiveSim/Custom/GameLogic.php (~line 7108) as part of the materialize effect-stack
// resolution path. The problem is reachability, not existence: ARCANE is an advanced element
// (GetAdvancedElementNames()) that no level-0 starting champion carries, so
// CanPlayerUseCardElement genuinely blocks materializing this card from hand for real in a
// fresh fixture — independent of the pregame bug. And BridgeAddToZone (the test-setup helper
// used for 'setup' below) adds objects via the raw MZAddZone/AddField/FieldAfterAdd path, which
// does NOT invoke FireEnterTriggeredAbility — so seeding the card directly onto myField would
// not exercise the enter ability either. Given that, this rebuild instead exercises the card's
// other real, implemented ability — the graveyard activation at the "Fulgurite Coordinator:
// Banish self from graveyard to add static counters." comment (~line 1365): banish this card
// from the graveyard (1 reserve) to add a static counter to an arcane object you control. It
// uses BridgeAddToZone test-setup helpers (the same primitive the MCP fixture tooling uses) to
// seed the precondition state — a second arcane ally already on the field (the target) and a
// copy of Fulgurite Coordinator already in the graveyard (the activation source) — then replays
// the real activate-from-graveyard decision sequence (FSM click -> choose target -> pay reserve
// -> pass) and confirms the target's Counters.static becomes 1. (Note: the sibling
// fulgurite-coordinator-static-counter fixture, created separately via MCP tooling, was fixed
// to exercise this same graveyard-activation ability — the two fixtures are intentionally
// similar since it's the only ability on this card reachable by current test tooling.)
// Test-setup preconditions applied via BridgeAddToZone (after pregame startup, before the
// initial gamestate is captured): an arcane ally already on the field to serve as the
// static-counter target, and a copy of Fulgurite Coordinator already in the graveyard to
// serve as the graveyard-activation source.
// Notes carried over from the original definition:
//   - the champion's element must match Fulgurite Coordinator's (ARCANE) for the Element Bonus
//   - Storm Slime (ARCANE ally) - counter target
//   - Fulgurite Coordinator - GY activation source
//   - Activate Fulgurite Coordinator from the graveyard (banish self, 1 reserve) — myGraveyard-0
//   - is the copy seeded by 'setup' above.
//   - Choose the static-counter target: myField-1 is the Storm Slime seeded by 'setup' above
//   - (myField-0 is the starting champion).
//   - Pay the 1-reserve activation cost by reserving a hand card.
//   - Pass fast action opportunities
gaScenario('fulgurite-enters-static-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Fulgurite Coordinator
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
    ->patch('myField-0', ['Counters' => ['_overrides' => ['element' => 'ARCANE']]])
    ->seed('myField', 'Storm Slime')
    ->seed('myGraveyard', 'Fulgurite Coordinator')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->target('myField-1')->pay(1)->passBoth()
    ->tested('Fulgurite Coordinator')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Counters', '{"static":1}')
    ->expectProperty('p1Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', '7aZwqrfbzO')
    ->expectProperty('p1Hand-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-5', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Banish-0', 'CardID', '7aZwqrfbzO')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myGraveyard', 0, 1)
    ->expectZoneCount('myMemory', 1, 1)
    ->register();

// --- Fortified Mana Shield: Taunt-based class bonus discount + prevent 4 non-combat damage ---
// Fortified Mana Shield's Class Bonus discount (activationCostModifierAbilities
// ["5lh23qu7d6:0"]) requires (a) a GUARDIAN Class Bonus — no level 0 starting champion has a
// class other than SPIRIT — and (b) a unit with taunt anywhere on the field, to reduce its
// 2-reserve activation cost to 0. Seed Ciel, Loyal Valet (a real GUARDIAN champion) for the
// class bonus, and give Dungeon Guide the TAUNT turn effect directly (no printed-Taunt ally
// is in this filler deck) to satisfy the taunt-unit condition.
// Play Fortified Mana Shield (myHand-0 with this deck/seed — verified live via DevTools
// discovery harness). With the discount active, activation costs 0 reserve: FSM play ->
// player's own fast-action response window -> opponent's response window -> target choice.
// Notes carried over from the original definition:
//   - Ciel, Loyal Valet (GUARDIAN champion) - Class Bonus source
//   - Dungeon Guide w/ Taunt - discount condition + effect target
//   - Target choice: prevent the next 4 non-combat damage to the taunt unit (myField-2).
gaScenario('fortified-mana-shield-taunt-discount')
    ->standardDeck('Fortified Mana Shield')
    ->seed('myField', 'Ciel, Loyal Valet')
    ->seed('myField', 'Dungeon Guide', properties: ['TurnEffects' => ['TAUNT']])
    ->playSlot(0)->passBoth()->target('myField-2')
    ->tested('Fortified Mana Shield')
    ->register();

// --- Empowering Tincture: On Enter draws into memory if brewed ---
// NOTE: Empowering Tincture was attempted first but abandoned for the same reason as
// Samaritan's Reach above -- its On Enter reads the "wasBrewed" decision-queue variable
// (normally set by the separate Brew minigame), and injecting it via dqVariables in setup
// doesn't survive to Enter resolution (verified live: reads back NULL from the final gamestate,
// and no card was drawn into memory). Fan of Seven Debts is a much simpler substitute with the
// same "draw a card" shape but no hidden variable dependency.
// Fan of Seven Debts' element is NORM, so no lineage patch is needed, and its memory cost (1)
// is NOT exercised for the same FSM-click-bypass reason as entrancing-filigree-enter-banish.
// Only the On Enter draw is covered; the [Kongming Bonus] "banish for Shifting Currents"
// ability is tied to a separate facing-state subsystem and is out of scope.
gaScenario('fan-of-seven-debts-enter-draw')
    ->standardDeck('Fan of Seven Debts')
    ->seed('myHand', 'Fan of Seven Debts')
    ->playSlot(4)
    ->tested('Fan of Seven Debts')
    ->register();

// --- Flute of Taming: [REST] your champion gets +1 level ---
// Flute of Taming's element is NORM, so no lineage patch is needed, and its memory cost is
// NOT exercised for the same FSM-click-bypass reason as entrancing-filigree-enter-banish.
// Ability 1 ([REST]: champion +1 level) is covered via the same AddGlobalEffects()-flag
// pattern used elsewhere; ability 0 (buff a target Animal/Beast ally) requires a subtyped
// ally target and is out of scope.
gaScenario('flute-of-taming-champion-level')
    ->standardDeck('Flute of Taming')
    ->seed('myHand', 'Flute of Taming')
    ->playSlot(5)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pass(1)
    ->tested('Flute of Taming')
    ->register();

// --- Forging Heat: put a durability counter and +1 power on target Sword weapon ---
// Forging Heat's element is FIRE (matching the starting champion), so no lineage patch is
// needed. Clarent, Sword of Peace is seeded onto the field as the required Sword weapon
// target. The Floating Memory clause is a passive/reusable-elsewhere property, not a
// triggered ability, and is out of scope.
// Notes carried over from the original definition:
//   - Clarent, Sword of Peace (WEAPON, SWORD) - buff target
gaScenario('forging-heat-sword-buff')
    ->standardDeck('Forging Heat')
    ->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myHand', 'Forging Heat')
    ->play('Forging Heat')->pay(2)->target('myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","tjmzM6t9R5_POWER"]', 'The targeted Sword weapon gains the +1 power turn effect')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'The targeted Sword weapon gains a durability counter')
    ->expectDqEmpty()
    ->register();

// --- Foraging Servant: On Enter Gather a random resource token ---
// Foraging Servant's element is NORM, so no lineage patch is needed. Only the base "Gather" On
// Enter is covered (which summons one of six possible tokens chosen at random); the [Class
// Bonus] Floating Memory clause is a passive/reusable-elsewhere property and is out of scope.
gaScenario('foraging-servant-enter-gather')
    ->standardDeck('Foraging Servant')
    ->seed('myHand', 'Foraging Servant')
    ->play('Foraging Servant')->pay(2)
    ->register();

// --- Fulminator, Rising Storm: [Lorraine Bonus] enters with LV-2 static counters ---
// Fulminator's element is ARCANE and its Lorraine Bonus (IsLorraineBonusActive) requires
// Lorraine, Wandering Warrior (DpHDGaX2Pn) specifically in lineage. The starting champion's
// CardID is patched directly to Lorraine, Arclight Saber (level 3, ARCANE -- unlocks the
// card's element) and its Subcards are patched with Lorraine, Wandering Warrior to satisfy the
// Lorraine Bonus lineage check. On Enter puts max(0, LV-2) static counters on itself
// (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, enterAbilities["F1JIgewvFI:0"]); at
// champion level 3 that's 1 static counter.
gaScenario('fulminator-rising-storm-lorraine-bonus')
    ->standardDeck('Fulminator, Rising Storm')
    ->champion(cardName: 'Lorraine, Arclight Saber', lineage: ['Lorraine, Wandering Warrior'])
    ->playSlot(0)->pay(3)->pass(1)
    ->tested('Fulminator, Rising Storm')
    ->expectProperty('myField-1', 'CardID', 'F1JIgewvFI', 'Fulminator, Rising Storm is on the field')
    ->expectProperty('myField-1', 'Counters', '{"durability":3,"static":1}', 'The Lorraine Bonus grants LV-2 (level 3 - 2 = 1) static counters on enter')
    ->expectDqEmpty()
    ->register();

// --- FlameTech BladeCore: Sword Weapon Link, entering the field linked to a target Sword weapon ---
// FlameTech BladeCore is EXALTED element -- CanPlayerMeetCardElementRequirements requires
// EXALTED to be explicitly enabled, which GetPlayerEnabledElements does automatically once any
// OTHER advanced element is enabled (GrandArchiveSim/Custom/GameLogic.php:19410-19419), so the
// same ARCANE Subcards lineage patch used by conductive-strike-static-counters unlocks it too.
// Its Weapon Link is generic engine machinery ($WeaponLink_Cards, GameLogic.php:914-916,
// 2239-2248): activating it with a valid Sword weapon on the field queues an MZCHOOSE to pick
// the link target, and CreateWeaponLink stores the link on BOTH sides -- the weapon's Subcards
// gains the linking card's CardID, and the linking object's Counters gets
// 'linkedToWeapon' => weapon's CardID (GameLogic.php:22718-22732) -- both are plain stored
// properties, directly assertable. The "+2POWER" and "put a durability counter on discard"
// clauses read that link at computed-value/listener-dispatch time with no additional stored
// state of their own, so they're out of scope here. [Lorraine Bonus] Link Shield is a distinct,
// narrower conditional (IsLorraineBonusActive) not exercised by this fixture.
// Notes carried over from the original definition:
//   - Lorraine, Arclight Saber (ARCANE) - unlocks EXALTED too
//   - Clarent, Sword of Peace (WEAPON, SWORD) - link target
gaScenario('flametech-bladecore-weapon-link')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myHand', 'FlameTech BladeCore')
    ->endTurn()
    ->endTurn(2)->answer(1, '-')
    ->play('FlameTech BladeCore')->target('myField-1')->pay(2)
    ->expectProperty('myField-2', 'CardID', 'aAJliPQT3F', 'FlameTech BladeCore enters the field')
    ->expectProperty('myField-2', 'Counters', '{"linkedToWeapon":"m31WVJ9F04"}', 'FlameTech BladeCore records its link target as Clarent, Sword of Peace')
    ->expectProperty('myField-1', 'Subcards', '["aAJliPQT3F"]', 'Clarent, Sword of Peace records FlameTech BladeCore as a linked Subcard')
    ->expectDqEmpty()
    ->register();

// --- Formidable Youxia: As long as Shifting Currents face East, +2 LIFE ---
// Purely computed at ObjectCurrentHP() read time (GrandArchiveSim/Custom/GameLogic.php:13538-
// 13542, ~13740), same shape as Banner Knight's power buff -- no stored flag to assert against,
// so a new generic computed_life_equals assertion type (Core/RegressionTestFramework.php,
// mirroring computed_power_equals) was added. Shifting Currents mastery is a myMastery zone card
// (CardID qh5mpkyl60) with a Direction property (established by
// kongming-wayward-maven-enter-shifting-currents), seeded directly facing EAST. Base LIFE 2 + 2
// = 4.
// Notes carried over from the original definition:
//   - Formidable Youxia
//   - pay 2-reserve cost, card 1/2
//   - pay 2-reserve cost, card 2/2
gaScenario('formidable-youxia-east-life-buff')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'EAST'])
    ->seed('myHand', 'Formidable Youxia')
    ->play('Formidable Youxia')->pay(2)
    ->register();

// --- Favorable Winds: Allies you control get +1 LIFE until end of turn ---
// WIND (basic element): Subcards lineage patch for element access, same technique as
// rising-tides/tera-sight. AddGlobalEffects('dsAqxMezGb') then applies to any ALLY-type object
// via $doesGlobalEffectApply['dsAqxMezGb'] (GameLogic.php:17660-17662), read back as +1 LIFE in
// the LIFE computation switch (~13680-13682).
// Notes carried over from the original definition:
//   - WIND element unlock (Spirit of Wind)
//   - Dungeon Guide (ALLY), base LIFE 3
//   - Favorable Winds
//   - pay 1-reserve cost
//   - Reserve-paid SPELL activations resolve via an EffectStack, unlike the direct-resolution
//   - memory-cost activations used elsewhere in this file; decline the "respond?" opportunity
//   - ("-") to let it resolve (discovered via a throwaway debug harness, DevTools/debug_winds.php).
gaScenario('favorable-winds-ally-life-buff')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Favorable Winds')
    ->play('Favorable Winds')->pay(1)->answer(1, '-')
    ->register();

// --- Force Load: choose a fire/norm 0-cost Bullet from material deck, load into target Gun ---
// Force Load reads the material zone directly (not the standard "myMaterial" section of this
// fixture's own deck list, which has no Bullet cards), so a real 0-memory-cost NORM Bullet
// (Plated Bullet) is seeded there via test-setup. Shadow's Twin is seeded unloaded onto the
// field as the target Gun -- LoadBulletIntoGun() is the same shared function used by Plated
// Bullet's own [REST] ability, so loading via Force Load also triggers Shadow's Twin's
// "whenever this becomes loaded, +2 POWER" trigger.
// Notes carried over from the original definition:
//   - Shadow's Twin, unloaded Gun - Force Load's target
//   - Plated Bullet - 0-memory NORM Bullet source
gaScenario('force-load-bullet-into-gun')
    ->standardDeck('Force Load')
    ->seed('myField', 'Shadow\'s Twin')
    ->seed('myMaterial', 'Plated Bullet')
    ->seed('myHand', 'Force Load')
    ->play('Force Load')->pay(2)->target('myMaterial-4')->target('myField-1')
    ->register();

// --- Flash Freeze: Negate target card activation, banish the negated card ---
// Notes carried over from the original definition:
//   - Nico, Rapture's Embrace -- unlocks WATER for Flash Freeze
//   - Scry the Skies -- the card whose activation gets negated
//   - Flash Freeze
//   - Fast-opportunity window offers Flash Freeze in response, before Scry the Skies resolves.
//   - Pay Flash Freeze's 4-reserve cost (no Class Bonus -- default champion isn't CLERIC).
//   - Choose Scry the Skies (EffectStack-0) as the activation to negate.
gaScenario('flash-freeze-negate-target-card-activation')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myHand', 'Scry the Skies')
    ->seed('myHand', 'Flash Freeze')
    ->play('Scry the Skies')->pay(1)->target('myHand-6')->pay(4)->answer(1, 'EffectStack-0')
    ->tested('Flash Freeze')
    ->register();

// --- Freezing Hail + Blue Slime: deal 2 damage, [Class Bonus] Blue Slime gets a buff counter ---
// Both cards are WATER -- the starting champion's CardID is patched directly to Silvie, With
// the Pack (TAMER, also satisfies Blue Slime's [Class Bonus] class-match) and its Subcards are
// ALSO patched with a real WATER champion (Nico, Rapture's Embrace) purely for the element/
// lineage unlock (Silvie's own champions are NORM/TERA, not WATER). Blue Slime (Pride 4) is
// seeded directly onto the field so its Pride doesn't need to be re-satisfied for a
// materialize (Pride only gates attacking, already covered by gray-wolf-pride-2-attack-
// rejected). Freezing Hail's dealDamageAbilities trigger for Blue Slime fires reactively off
// DealDamage regardless of source, so targeting Blue Slime with our own Freezing Hail is a
// valid, simple way to trigger both cards' effects in one fixture: Freezing Hail's own "deal 2
// damage, skip next wake up" and Blue Slime's own "[Class Bonus] whenever dealt damage, buff
// counter" trigger simultaneously.
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER) + Nico (WATER unlock)
//   - Blue Slime
//   - target Blue Slime
gaScenario('freezing-hail-blue-slime-class-bonus-buff')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack', lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myField', 'Blue Slime')
    ->seed('myHand', 'Freezing Hail')
    ->play('Freezing Hail')->pay(2)->target('myField-1')
    ->tested('Freezing Hail', 'Blue Slime')
    ->expectDamage('p1Field-1', 2, 'Freezing Hail deals 2 damage to Blue Slime')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DAMAGED_SINCE_LAST_TURN","SKIP_WAKEUP"]', 'Blue Slime won\'t wake up during the controller\'s next wake up phase')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '[Class Bonus] Blue Slime gets a buff counter when dealt damage')
    ->expectDqEmpty()
    ->register();

// --- Flame-Rune Swordsman: [Class Bonus] Floating Memory ---
// Flame-Rune Swordsman's only ability is [Class Bonus] Floating Memory (its own classes are
// MAGE,WARRIOR), same shape as dewdrop-hares-class-bonus-floating-memory: the champion is
// patched to Rai, Spellcrafter (level 1, MAGE) so leveling into Rai, Archmage (level 2, MAGE,
// 2-memory cost) is legal and the Class Bonus condition (checked on the CURRENT champion at
// payment time) is satisfied. Flame-Rune Swordsman pays 1 of the 2 memory via Floating Memory
// from the graveyard; the 2nd memory point is a filler card seeded directly into myMemory.
// Notes carried over from the original definition:
//   - Rai, Spellcrafter (MAGE, level 1) - Class Bonus precondition + legal level-up base
//   - Flame-Rune Swordsman
//   - filler memory card, 2nd point of Rai, Archmage's 2-memory level-up cost
//   - select Rai, Archmage as the level-up target
//   - pay 1 memory via Floating Memory
//   - pay the 2nd memory point
gaScenario('flame-rune-swordsman-class-bonus-floating-memory')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Rai, Spellcrafter')
    ->seed('myGraveyard', 'Flame-Rune Swordsman')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')->target('myMemory-0')
    ->tested('Flame-Rune Swordsman')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'CardID', 'zdIhSL5RhK')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Banish-0', 'CardID', 'VV6ADdMrr5')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Banish-1', 'Counters', '[]')
    ->expectProperty('p1Banish-1', 'TurnEffects', '[]')
    ->expectProperty('p1Material-0', 'CardID', 'm31WVJ9F04')
    ->expectProperty('p1Material-1', 'CardID', '9gv4vm4kj3')
    ->expectProperty('p1Material-2', 'CardID', 'LeyUk5auEP')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'LEVELED_UP_THIS_TURN')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('myGraveyard', 0, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Fire Resonance Bauble: Banish -- draw a card (if opponent can access Fire) ---
// Fire Resonance Bauble is REGALIA -- seeded directly onto myField (memory-cost REGALIA
// items are Material-zone-only cards when played for real, same as Clarent/Backup
// Charger/Purifying Thurible in this deck's own Material section; seeding directly bypasses
// that entirely to test only the [Activate] ability itself). Its prereq
// (activateAbilityPrereqs["LROrzTmh55:0"]) requires the OPPONENT to have Fire access --
// P2's default starting champion is literally Spirit of Fire, so no patch is needed. Same
// Scry the Skies warm-up + direct Activate:0 click as crystal-of-empowerment-banish-level.
gaScenario('fire-resonance-bauble-banish-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Fire Resonance Bauble')
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Fire Resonance Bauble')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'F9POfB5Nah')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-0', 'CardID', 'LROrzTmh55')
    ->expectProperty('p1Banish-0', 'Status', '2')
    ->expectDamage('p1Banish-0', 0)
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 7, 1)
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Find the Lost: Prepare 1, as long as prepared it has unblockable ---
// Find the Lost's element is NORM (same pattern as thieving-cut-prepare-onhit-draw), so no
// lineage patch is needed. ATTACK cards can't be activated by the game's first player on
// turn 1 (CanActivateAttackCardNow/IsFirstTurnAttackLocked in GameLogic.php), so player 1
// ends turn 1 first and player 2 plays Find the Lost on their own turn 1 instead. Player 2's
// champion is pre-seeded with 1 preparation counter directly (normally only reachable via a
// separate preparation-counter-granting effect) so its "Prepare 1" additional cost (remove 1
// preparation counter as you activate it) can actually be paid.
//
// This exercises the OnCardActivated()/GameLogic.php fix for the PREPARED-tagging bug: before
// the fix, OnCardActivated's ATTACK branch read the "wasPrepared" DecisionQueueController
// variable and tagged the just-created myIntent object synchronously, in the same function
// call that moves the card there -- before $cardActivatedAbilities["jTBNAEedbg:0"] (called
// later in that same function) even queues the "Pay Prepare 1?" YES/NO decision, let alone
// before the player answers it. So the PREPARED TurnEffect was never actually applied off a
// genuine payment. The fix instead queues a "GA_TagPreparedAttack" follow-up CUSTOM decision
// right after the card's own CardActivated macro runs, so it resolves strictly after the
// Prepare decision does and reads the correct, just-finalized wasPrepared value. Verified
// live: answering YES removes the preparation counter and (post-fix) tags myIntent-0 with
// "PREPARED" -- the same TurnEffect CombatLogic.php's AttackBypassesInterceptAndTaunt() reads
// to grant unblockable. The fixture stops right after that YES answer (before declaring the
// attack target) so the assertion can check the tag directly on the still-live intent object
// -- declaring the attack fully resolves combat in the same step (no interceptor on the
// default board to require a separate decision), which moves the card out of myIntent
// entirely and destroys the very state this fixture is about. Checking the TurnEffect
// directly (same precedent as e.g. crystalline-reality-merlin-bonus-prepare-choose-two's
// Counters assertion) is the semantic proof, not a further downstream combat simulation.
// Break/restore verified: reverting to the old synchronous read makes the TurnEffects
// assertion fail (myIntent-0 TurnEffects comes back "[]" instead of '["PREPARED"]');
// restoring the fix passes again.
// Notes carried over from the original definition:
//   - Prepare-ability cost fuel
//   - ends turn 1 (first-player attack lock)
gaScenario('find-the-lost-prepare-unblockable')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['preparation' => 1]], player: 2)
    ->seed('myHand', 'Find the Lost', player: 2)
    ->endTurn()
    ->play('Find the Lost', 2)->pay(2, 2)->answer(2, 'YES')
    ->register();

// --- Fractal of Mana: [Class Bonus] [REST]: Empower 1 ---
// Fractal of Mana's [Class Bonus][REST] ability (GeneratedMacroCode.php
// activateAbilityAbilities["szeb8zzj86:0"], prereq requires IsClassBonusActive(["CLERIC",
// "MAGE"])) calls Empower($player,1,"szeb8zzj86"), tagging the champion's TurnEffects with
// ["szeb8zzj86","EMPOWERED","EMPOWER_PLUS_1"] (GameLogic.php). Same two-object MAGE Class
// Bonus setup as vernal-talisman-preserve-draw: Subcards patched for lineage, plus a second
// physical MAGE champion object on the field for IsClassBonusActive's physical-field scan.
// Notes carried over from the original definition:
//   - lineage unlock
//   - Kongming, Fel Eidolon (MAGE CHAMPION), physically seeded for Class Bonus
//   - Fractal of Mana
gaScenario('fractal-of-mana-class-bonus-empower')
    ->deck(GA_KONGMING_BASE_DECK)
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myField', 'Kongming, Fel Eidolon')
    ->seed('myField', 'Fractal of Mana')
    ->patch('myField-2', ['Status' => 2])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')
    ->tested('Fractal of Mana')
    ->expectProperty('myField-0', 'TurnEffects', '["szeb8zzj86","EMPOWERED","EMPOWER_PLUS_1"]', '[Class Bonus][REST] Empower 1 tags the champion EMPOWERED/EMPOWER_PLUS_1')
    ->expectProperty('myField-2', 'Status', '1', 'Fractal of Mana rests as part of paying its REST cost')
    ->register();

// --- Fractal of Insight: On Enter -- may rest self; if you do, Glimpse 2 ---
// Fractal of Insight (rp5k1vt1cn:0, PHANTASIA/MAGE) is played from hand like a normal
// permanent (no EffectStack priority pass needed -- same as Tactful Sergeant's ALLY play).
// enterAbilities["rp5k1vt1cn:0"] queues a YESNO ("Rest_Fractal_of_Insight?"); answering YES
// exhausts it and calls Glimpse(2) (GameLogic.php), which queues an MZREARRANGE decision with
// param "Top=<cardIDs>;Bottom=". Submitting that same string verbatim (the no-op default) keeps
// the two glimpsed cards on top in their original order -- deck count is unaffected (glimpse
// only looks/reorders). Answering YES is the semantic proof: Fractal of Insight's own Status
// flips from 2 (awake) to 1 (rested) as the direct result of the "may rest self" choice, and a
// real MZREARRANGE decision (glimpse) is only queued because Glimpse(2) actually ran.
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress
//   - play Fractal of Insight
//   - pay reserve 1/2
//   - pay reserve 2/2
//   - choose to rest Fractal of Insight
//   - Glimpse 2 MZREARRANGE response: deliberately reorders the two glimpsed cards (bottom the one that was originally on top) rather than submitting the no-op default, so the resulting deck order is observably different from "no Glimpse happened" -- confirmed via direct probe against this deck/seed (top two cards were px60u5n1do, em6eEh9q8y before this decision)
gaScenario('fractal-of-insight-rest-glimpse-2')
    ->deckTemplate('diao-awakened-deacon', 'Fractal of Insight')
    ->champion(cardName: 'Diao Chan, Enchantress')
    ->seed('myHand', 'Fractal of Insight')
    ->play('Fractal of Insight')->pay(2)->answer(1, 'YES')->answer(1, 'Top=em6eEh9q8y;Bottom=px60u5n1do')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'rp5k1vt1cn')
    ->expectProperty('p1Field-1', 'Status', '1', 'Answering YES to \'Rest_Fractal_of_Insight?\' exhausted Fractal of Insight itself (Status 2 -> 1), the \'may rest CARDNAME\' half of the clause')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-0', 'CardID', 'x7u6wzh973')
    ->expectProperty('p1Hand-1', 'CardID', 'rp5k1vt1cn')
    ->expectProperty('p1Hand-2', 'CardID', 'c9p4lpnvx7')
    ->expectProperty('p1Hand-4', 'CardID', 'rp5k1vt1cn')
    ->expectProperty('p1Memory-0', 'CardID', 'rp5k1vt1cn')
    ->expectProperty('p1Memory-1', 'CardID', 'c9p4lpnvx7')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->expectProperty('p1Deck-0', 'CardID', 'em6eEh9q8y', 'Glimpse 2 looked at the deck\'s actual top two cards (px60u5n1do, em6eEh9q8y, confirmed via direct probe) and queued a real MZREARRANGE decision -- submitting a deliberate reorder (bottom px60u5n1do, keep em6eEh9q8y on top) rather than the no-op default proves Glimpse(2) really ran and the response was applied: the new top-of-deck card differs from the pre-glimpse top card, which would be impossible if the YES branch\'s Glimpse(2) call never executed')
    ->expectProperty('p1Deck-12', 'CardID', 'px60u5n1do', 'The card that was on top before the glimpse (px60u5n1do) was moved to the bottom of the deck by the MZREARRANGE response, confirming Glimpse(2)\'s rearrange actually moved the correct card')
    ->register();

// --- Fractal of Refreshment: On Enter -- reveal up to 3 water memory cards, bottom them, draw that many ---
// Fractal of Refreshment (cxqf8rr452, PHANTASIA/CLERIC) is played from hand like a normal
// permanent (no EffectStack priority pass needed). Paying its 3-reserve cost fires
// enterAbilities["cxqf8rr452:0"] (GeneratedMacroCode.php, comment "Memory Refresh"), which finds
// WATER-element cards in myMemory and repeatedly offers an MZMAYCHOOSE ("Reveal_and_bottom_a_
// water_card?") via customDQHandlers["FractalRefreshPick"] (Custom/GameLogic.php). Two water
// cards (x7u6wzh973, Frostbinder Apostle) are seeded into memory alongside one non-water filler
// (em6eEh9q8y, Dungeon Guide, NORM element) to prove only water cards are eligible. Choosing
// both water cards moves each to the bottom of the deck (MZMove to "myDeck") and increments
// fractalRefreshCount; after the 2nd pick, ZoneSearch("myMemory", ["WATER"]) is empty (both
// consumed), so the loop short-circuits straight to DrawIntoMemory($player, 2) without a third
// decision -- proving "up to three" correctly stops early rather than always asking exactly 3
// times. The non-water Dungeon Guide is never offered as a choice at all. Fractal of
// Refreshment's own element is WATER (unlike Fractal of Insight/Unstable Fractal, both NORM),
// so swapping the champion's CardID to Diao Chan alone is not enough -- Diao Chan's own element
// is NORM, so playing a WATER card would silently no-op (confirmed live) without also patching
// Subcards to include Spirit of Water (tafqldAGRF, WATER) for permanent element access, same
// technique as the water-barrier-prevent-tag precedent.
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress (CLERIC) + Spirit of Water lineage (WATER unlock)
//   - Frostbinder Apostle (WATER) #1
//   - Frostbinder Apostle (WATER) #2
//   - Dungeon Guide (NORM) -- not water, must not be offered
//   - play Fractal of Refreshment
//   - pay reserve 1/3
//   - pay reserve 2/3
//   - pay reserve 3/3
//   - reveal and bottom water card #1
//   - reveal and bottom water card #2
//   - decline the 3rd offer (reveal only 2 of the allowed "up to three") -- resolves FractalRefreshPick's remaining=1 and fires DrawIntoMemory(2)
gaScenario('fractal-of-refreshment-bottom-2-draw-2')
    ->deckTemplate('diao-awakened-deacon', 'Fractal of Refreshment')
    ->champion(cardName: 'Diao Chan, Enchantress', lineage: ['Spirit of Water'])
    ->seed('myMemory', 'Frostbinder Apostle')
    ->seed('myMemory', 'Frostbinder Apostle')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Fractal of Refreshment')
    ->play('Fractal of Refreshment')->pay(3)->target('myMemory-0')->target('myMemory-0')->answer(1, '-')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'cxqf8rr452')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-3', 'CardID', 'c9p4lpnvx7')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-2', 'CardID', 'cxqf8rr452')
    ->expectProperty('p1Memory-3', 'CardID', 'x7u6wzh973')
    ->expectProperty('p1Memory-4', 'CardID', 'cxqf8rr452', 'Declining the 3rd reveal offer (having already revealed exactly 2 water cards) fired DrawIntoMemory(2) -- this is the deck\'s actual pre-draw top card (confirmed via direct probe), now drawn into memory as the first of the 2 cards')
    ->expectProperty('p1Memory-5', 'CardID', 'x7u6wzh973', 'The 2nd card drawn into memory -- the deck\'s actual pre-draw 2nd-from-top card (confirmed via direct probe), proving \'draw that many cards into your memory\' drew exactly as many as were revealed-and-bottomed, not a fixed or different amount')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 6, 1)
    ->expectProperty('p1Deck-11', 'CardID', 'x7u6wzh973', 'The first revealed water card (a Frostbinder Apostle originally seeded in memory) was put on the bottom of the deck')
    ->expectProperty('p1Deck-12', 'CardID', 'x7u6wzh973', 'The second revealed water card was also put on the bottom of the deck (last position), confirming both selections landed on the bottom, not just discarded or left in place')
    ->expectZoneCount('myDeck', 13, 1)
    ->register();

// --- Frostlorn Caress: [Diao Chan Bonus] costs 3 less. Put 4 wither counters on target non-champion object ---
// activationCostModifierAbilities["4tqbok1g9w:0"] (GeneratedMacroCode.php ~8183) applies -3 to
// Frostlorn Caress's printed 5-reserve cost when IsDiaoChanBonus($player) is true. The starting
// champion (Spirit of Water) is CardID-patched to Diao Chan, Enchantress (00xbh8oc00) -- the
// deck's own level-1 champion -- with Subcards patched to keep Spirit of Water (tafqldAGRF) in
// the lineage so WATER stays unlocked (Diao Chan, Enchantress's own element is NORM). This
// satisfies IsDiaoChanBonus (ChampionHasInLineage checks "00xbh8oc00" directly) so the net cost
// is 5-3=2. Frostlorn Caress itself is seeded directly into hand (the shared deck's Main is
// filler-only, so the card under test would never otherwise appear in hand). Since our deck
// has zero FAST cards, EffectStackOpportunity's own "no playable fast cards for either player"
// fallback (OpportunityLogic.php ~1167, QueueEffectStackOpportunityRound returning false)
// resolves the stack immediately with no PASS actions needed -- cardActivatedAbilities
// ["4tqbok1g9w:0"] (~15648) fires as soon as the 2nd reserve payment completes, offering an
// MZCHOOSE of ALLY objects on either field (only the opponent's seeded Dungeon Guide qualifies);
// customDQHandlers["4tqbok1g9w:0:CardActivated-1"] (~32767) calls
// AddCounters($player, $chosen, "wither", 4) on whichever is picked.
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress + kept Spirit of Water lineage for WATER unlock
//   - Dungeon Guide ALLY, target
//   - play Frostlorn Caress
//   - pay reserve 1/2
//   - pay reserve 2/2 -- stack auto-resolves (no fast cards), ability fires immediately
//   - target the opponent's Dungeon Guide
gaScenario('frostlorn-caress-four-wither-counters')
    ->deck(GA_DIAOCHAN_WITHER_DECK)
    ->champion(cardName: 'Diao Chan, Enchantress', lineage: ['Spirit of Water'])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Frostlorn Caress')
    ->play('Frostlorn Caress')->pay(2)->target('theirField-1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', '4tqbok1g9w')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Field-1', 'Counters', '{"wither":4}', 'Frostlorn Caress put exactly 4 wither counters on the target non-champion object (the opponent\'s Dungeon Guide)')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1, 'Frostlorn Caress (an ACTION/SPELL/REACTION card) resolved and went to the graveyard, confirming the activation actually completed rather than fizzling with no legal target')
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Frostnip Pirouette: [Diao Chan Bonus] costs 2 less. Choose any amount of non-champion
// objects and put a wither counter on each -- proving selective multi-target application ---
// Same [Diao Chan Bonus] champion patch as frostlorn-caress-four-wither-counters (Diao Chan,
// Enchantress + kept Spirit of Water lineage). activationCostModifierAbilities["x79cuuw5vo:0"]
// (GeneratedMacroCode.php ~9076) applies -2 to the printed 4-reserve cost, net cost 2.
// Frostnip Pirouette is seeded directly into hand. cardActivatedAbilities["x79cuuw5vo:0"]
// (~23003) offers an MZMULTICHOOSE of every non-champion object on either field (min 0);
// customDQHandlers["FrostnipPirouetteWitherLoop"] (Custom/CardDQHandlers.php ~5636) puts a
// single wither counter on each object actually selected. Two non-champion objects are seeded
// on the opponent's field (Dungeon Guide, Fluffy Shopkeep) and only those two are chosen from
// the offered set of three (a third Dungeon Guide is seeded on the player's OWN field and left
// unchosen) -- proving the effect applies per-selection, not to every eligible object.
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress + kept Spirit of Water lineage for WATER unlock
//   - Dungeon Guide ALLY, chosen target #1
//   - Fluffy Shopkeep ALLY, chosen target #2
//   - Dungeon Guide ALLY, eligible but deliberately left UNCHOSEN
//   - play Frostnip Pirouette
//   - pay reserve 1/2
//   - pay reserve 2/2 -- stack auto-resolves (no fast cards), ability fires immediately
//   - MZMULTICHOOSE: choose only the two opponent objects, leaving myField-1 unchosen
gaScenario('frostnip-pirouette-selective-wither-counters')
    ->deck(GA_DIAOCHAN_WITHER_DECK)
    ->champion(cardName: 'Diao Chan, Enchantress', lineage: ['Spirit of Water'])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Fluffy Shopkeep', player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Frostnip Pirouette')
    ->play('Frostnip Pirouette')->pay(2)->answer(1, 'theirField-1&theirField-2')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'x79cuuw5vo')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Field-1', 'Counters', '{"wither":1}', 'Chosen opponent Dungeon Guide (theirField-1) received exactly 1 wither counter')
    ->expectProperty('p2Field-2', 'Counters', '{"wither":1}', 'Chosen opponent Fluffy Shopkeep (theirField-2) received exactly 1 wither counter')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The eligible-but-unchosen Dungeon Guide (myField-1) received NO wither counter -- proving the effect only applies to objects actually selected, not to every eligible non-champion object')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// Foraging Fox: On Enter, look at top 5, may reveal a Fatestone into memory, rest on bottom.
// Notes carried over from the original definition:
//   - Patch a hand slot into Foraging Fox and a top-of-deck slot into Craggy Fatestone (within
//   - the "top five") so the fixture doesn't depend on shuffle luck for either.
//   - MZMAYCHOOSE: reveal the Craggy Fatestone found among the top five (myTempZone-2).
//   - MZREARRANGE: put the remaining 4 Dungeon Guide on the bottom of the deck (order irrelevant).
gaScenario('foraging-fox-enter-look5-reveal-fatestone')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
20 Dungeon Guide
DECK
    )
    ->patch('myHand-0', ['CardID' => 'b0ssellm84'])
    ->patch('myDeck-2', ['CardID' => 'h8n1520m2d'])
    ->playSlot(0)->pay(2)->target('myTempZone-2')->answer(1, 'Bottom=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y')
    ->tested('Foraging Fox')
    ->register();

// Fated Keepsake: On Enter draw a card; [Guo Jia Bonus] prevent all but 6 of 7+ damage to your
// champion while you control 3+ Fatestone/Fatebound objects.
// Notes carried over from the original definition:
//   - Player 1: Guo Jia champion + 3 Fatestone items + Fated Keepsake in hand (to materialize
//   - for real, proving its own On Enter) + a memory filler to pay its 1-memory cost.
//   - Player 2: ARCANE lineage unlock + Arcane Blast (deals 11 damage to target champion) plus
//   - enough reserve fuel to cast it on their own turn (11-reserve cost).
//   - Player 1 materializes Fated Keepsake for real (fires its genuine On Enter draw), then
//   - ends their turn so player 2 gets a legal opportunity to cast a sorcery-speed spell.
//   - Player 2 casts Arcane Blast (11 damage to target champion) at player 1's champion.
gaScenario('fated-keepsake-enter-draw-and-prevent-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
20 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Craggy Fatestone')
    ->seed('myField', 'Craggy Fatestone')
    ->seed('myField', 'Craggy Fatestone')
    ->seed('myMemory', 'Dungeon Guide')
    ->patch('myHand-0', ['CardID' => 'vi1uyifw6s'])
    ->champion(lineage: ['Lorraine, Arclight Saber'], player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Arcane Blast', player: 2)
    ->playSlot(0)
    ->endTurn()
    ->play('Arcane Blast', 2)->pay(11, 2)->answer(2, 'theirField-0')
    ->tested('Fated Keepsake')
    ->expectZoneCount('myDeck', 12, 1, 'Fated Keepsake\'s On Enter draws a card, reducing the deck from 13 to 12', step: 1)
    ->expectDamage('myField-0', 6, '[Guo Jia Bonus] Arcane Blast\'s 11 damage to the champion is reduced to exactly 6')
    ->register();

// Fatestone of Heaven: On Enter destroy target non-champion object with memory cost 1 or less, or
// reserve cost 5 or less.
// Notes carried over from the original definition:
//   - Fatestone of Heaven is LUXEM; patch in Zander, Blinding Steel's lineage for element access.
//   - Opponent's Fluffy Shopkeep (reserve cost 3 <= 5) is the destroy target.
//   - MZCHOOSE: candidates are itself (myField-1, reserve 3) and the Fluffy Shopkeep
//   - (theirField-1, reserve 3) -- choose the opponent's ally.
gaScenario('fatestone-of-heaven-enter-destroy-cheap-object')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(lineage: ['Zander, Blinding Steel'])
    ->patch('myHand-0', ['CardID' => 'al6pqkmgmz'])
    ->seed('myField', 'Fluffy Shopkeep', player: 2)
    ->playSlot(0)->pay(3)->target('theirField-1')
    ->tested('Fatestone of Heaven')
    ->register();

// --- Fatestone of Heaven: destroying an ITEM/WEAPON target routes through AllyDestroyed(), firing
// "whenever destroyed" triggers (regression guard for the customDQHandlers["FatestoneOfHeavenDestroy"]
// fix in GrandArchiveSim/Custom/CardLogic.php) ---
// The fatestone-of-heaven-enter-destroy-cheap-object fixture only exercises the ALLY branch
// of customDQHandlers["FatestoneOfHeavenDestroy"] (Custom/CardLogic.php), which already
// called AllyDestroyed() correctly. Its non-ALLY/TOKEN branch instead did a raw
// OnLeaveField()+MZMove()-to-graveyard, silently skipping every "whenever a permanent is
// destroyed" trigger -- the same bypass bug already fixed for other cards via
// GrandArchiveSim/Custom/GeneratedAbilityOverrides.php. Diao Chan, Idyll Corsage is seeded
// onto the caster's own field as a canary: her "whenever a non-token object an opponent
// controls is destroyed, you may banish it; if you do, that opponent summons a Flowerbud
// token" ability only fires through DoAllyDestroyed()'s trigger dispatch, so her
// banish/Flowerbud payload firing directly proves the fix for an ITEM target (as opposed to
// just "it's gone from the field", which the old bypass would also achieve).
// Notes carried over from the original definition:
//   - Fatestone of Heaven is LUXEM; patch in Zander, Blinding Steel's lineage for element access.
//   - Diao Chan, Idyll Corsage - canary "whenever destroyed" trigger, on the destroyer's own
//   - side (she must be controlled by the opponent of the destroyed object's controller).
//   - Opponent's Craggy Fatestone (ITEM, reserve cost 4 <= 5) is the destroy target.
//   - MZCHOOSE: candidates are itself (myField-2, reserve 3) and the opponent's Craggy
//   - Fatestone (theirField-1, reserve 4) -- choose the opponent's item.
//   - Diao Chan's YESNO: banish the destroyed item and give the opponent a Flowerbud token.
gaScenario('fatestone-of-heaven-enter-destroy-item-fires-diaochan-trigger')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(lineage: ['Zander, Blinding Steel'])
    ->seed('myField', 'Diao Chan, Idyll Corsage')
    ->patch('myHand-0', ['CardID' => 'al6pqkmgmz'])
    ->seed('myField', 'Craggy Fatestone', player: 2)
    ->playSlot(0)->pay(3)->target('theirField-1')->answer(1, 'YES')
    ->tested('Fatestone of Heaven')
    ->register();

// Fatestone of Unrelenting: Hindered; On Enter: as a Spell, deal 1 damage to target unit.
// Notes carried over from the original definition:
//   - MZCHOOSE: deal the 1 damage to the opponent's champion.
gaScenario('fatestone-of-unrelenting-enter-damage-1')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(lineage: ['Spirit of Fire'])
    ->patch('myHand-0', ['CardID' => 'o37qtuvlxa'])
    ->playSlot(0)->pay(2)->target('theirField-0')
    ->tested('Fatestone of Unrelenting')
    ->expectProperty('myField-1', 'Status', '1', 'Hindered: Fatestone of Unrelenting enters the field rested')
    ->expectDamage('theirField-0', 1, 'On Enter deals 1 damage to the targeted champion')
    ->register();

// Fatestone of Revelations: On Enter, may reveal two Fatestone/Fatebound cards from hand/memory,
// draw a card if so.
// Notes carried over from the original definition:
//   - Two other Fatestone items in hand to reveal.
//   - MZMAYCHOOSE: reveal the first Craggy Fatestone (myHand-4).
//   - MZCHOOSE: reveal the second Craggy Fatestone (myHand-5).
gaScenario('fatestone-of-revelations-enter-reveal-two-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->seed('myHand', 'Craggy Fatestone')
    ->seed('myHand', 'Craggy Fatestone')
    ->patch('myHand-0', ['CardID' => 'xd4kv0akqr'])
    ->playSlot(0)->pay(2)->target('myHand-4')->target('myHand-5')
    ->tested('Fatestone of Revelations')
    ->expectZoneCount('myDeck', 2, 1, 'Revealing both Fatestone cards draws a card, reducing the deck from 3 to 2')
    ->register();

// Fabled Azurite Fatestone: [REST]: remove 10 quest counters from champion to wake up and
// transform.
// Notes carried over from the original definition:
//   - awake: the [REST] cost is paid by the activation (it used to be seeded rested because the cost was never paid)
gaScenario('fabled-azurite-fatestone-rest-transform-10-quest')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->patch('myField-0', ['CardID' => 'j6dkdoxyqt', 'Counters' => ['quest' => 10]])
    ->seed('myField', 'Fabled Azurite Fatestone')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'YES')
    ->tested('Fabled Azurite Fatestone')
    ->expectProperty('myField-0', 'Counters', '[]', 'The 10 quest counters are removed from the champion')
    ->expectProperty('myField-1', 'CardID', 'tf5f2n38g0', 'Fabled Azurite Fatestone transforms into Seiryuu, Azure Dragon')
    ->expectProperty('myField-1', 'Status', '2', 'The card wakes up as part of the transform')
    ->register();

// --- Forest Cake: On Enter: Draw a card ---
// Forest Cake's On Enter ability (enterAbilities['bjx6yo7mm5:0'], GeneratedMacroCode.php) is
// a plain Draw($player, 1). Its element is NORM, so no champion patch is needed. Played for
// real from hand via FSM (paying its 2 reserve cost from existing filler cards in hand) so
// the Enter trigger genuinely fires. Only the base Enter clause is covered; the separate
// [Class Bonus] "whenever an Animal/Beast ally enters, sacrifice for a buff counter" trigger
// is a different, reusable-elsewhere replacement-style effect and out of scope here.
gaScenario('forest-cake-on-enter-draw')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Forest Cake')
    ->play('Forest Cake')->pay(2)
    ->register();

// --- Furnace Drone: the three graveyard banishes are offered against the CURRENT (re-indexed) graveyard ---
// Furnace Drone (cbNF64gCsS): "As an additional cost to play CARDNAME, banish three fire element and/or
// Automaton cards from your graveyard." Played for real. The graveyard holds a non-eligible card in the
// middle so that, after the first banish, every later slot shifts down by one:
//   gy-0 Red Slime (FIRE), gy-1 Dungeon Guide (NORM, not eligible), gy-2 Corhazi Courier (FIRE), gy-3 Red Slime (FIRE).
// Banishing gy-0 leaves [Dungeon Guide, Corhazi Courier, Red Slime]. FurnaceDroneCostBanish used to build the
// next MZCHOOSE before the banished slot was spliced out, so it listed the stale 'myGraveyard-2&myGraveyard-3':
// the legal Corhazi Courier (now gy-1) was rejected and the nonexistent gy-3 was accepted as a candidate.
// Now CleanupRemovedCards() runs first, so the offered slots are the real ones.
// Notes carried over from the original definition:
//   - Red Slime (FIRE) -> gy-0
//   - Dungeon Guide (NORM, NOT eligible) -> gy-1
//   - Corhazi Courier (FIRE) -> gy-2
//   - second Red Slime (FIRE) -> gy-3
//   - Furnace Drone -> hand-7
//   - play Furnace Drone: the banish cost is declared first
//   - pick 1 of 3: Red Slime -- the graveyard is now [Dungeon Guide, Corhazi Courier, Red Slime]
//   - pick 2 of 3: Corhazi Courier at its REAL slot (rejected before the fix)
//   - pick 3 of 3: the last Red Slime, now the only eligible card (gy-1)
//   - the printed (3) reserve cost
//   - p1 passes the stack-response fast window so Furnace Drone resolves
gaScenario('furnace-drone-cost-banish-offers-reindexed-graveyard-slots')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Corhazi Courier')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Furnace Drone')
    ->play('Furnace Drone')
    ->rejects(1, 'myGraveyard-1', 'The Dungeon Guide (NORM, not fire/Automaton) is rejected as a banish choice')
    ->target('myGraveyard-0')
    ->rejects(1, 'myGraveyard-3', 'A stale pre-splice slot (gy-3, nonexistent after the first banish) is not a legal second choice')
    ->rejects(1, 'myGraveyard-0', 'The Dungeon Guide (now gy-0) is still rejected')->target('myGraveyard-1')
    ->target('myGraveyard-1')->pay(3)->pass(1)
    ->register();

// --- Frostsworn Paladin: On Enter, banishing a floating-memory card from the graveyard draws a card and puts a buff counter on it ---
// Frostsworn Paladin (rpOaAjgtue, WATER ALLY, reserve 3): "Intercept. On Enter: You may banish a
// card with floating memory from your graveyard. If you do, draw a card and put a buff counter on
// Frostsworn Paladin." The champion is patched to Mordred, Flawless Blade (level 2; its static
// ability gives attack cards in the graveyard floating memory) and a Striking Tides (an attack
// card) is seeded in the graveyard. The Paladin is played from hand for real (3 reserve payments),
// so enterAbilities["rpOaAjgtue:0"] fires: the graveyard attack card is offered, banished, a card
// is drawn and the Paladin gets exactly one buff counter.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2): attack cards in the graveyard have floating memory
//   - Striking Tides (attack card -> floating memory) -> p1 graveyard-0
//   - Frostsworn Paladin -> p1 myHand-7
//   - play Frostsworn Paladin
//   - On Enter: banish the floating-memory card from the graveyard
gaScenario('frostsworn-paladin-enter-banish-floating-draw-buff')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Striking Tides')
    ->seed('myHand', 'Frostsworn Paladin')
    ->play('Frostsworn Paladin')->pay(3)->target('myGraveyard-0')
    ->expectProperty('p1Field-1', 'CardID', 'rpOaAjgtue', 'The Frostsworn Paladin entered the field (played for real, so its On Enter fired)')
    ->expectProperty('p1Banish-0', 'CardID', 'qrxQGA1pc6', 'The graveyard attack card (floating memory via Mordred, Flawless Blade) was banished')
    ->expectZoneCount('myGraveyard', 0, 1, 'The banished card left the graveyard')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Exactly one buff counter was put on the Paladin')
    ->expectZoneCount('myHand', 5, 1, 'Hand 5 = 7 starting + 1 seeded - 1 played - 3 reserve payments + 1 card drawn by the On Enter')
    ->expectZoneCount('myMemory', 3, 1, 'Exactly the 3 reserve payments went to memory')
    ->expectDqEmpty()
    ->register();

// --- Frostsworn Paladin: On Enter is optional -- declining the banish draws nothing and adds no buff counter ---
// Frostsworn Paladin (rpOaAjgtue): "On Enter: You may banish a card with floating memory from
// your graveyard. If you do, draw a card and put a buff counter on CARDNAME." Same setup as
// frostsworn-paladin-enter-banish-floating-draw-buff, but the MZMAYCHOOSE is answered PASS: the
// graveyard card stays, no card is drawn and no buff counter is placed.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2)
//   - Striking Tides (floating memory)
//   - Frostsworn Paladin -> p1 myHand-7
//   - play Frostsworn Paladin
//   - decline to banish
gaScenario('frostsworn-paladin-enter-decline-banish')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Striking Tides')
    ->seed('myHand', 'Frostsworn Paladin')
    ->play('Frostsworn Paladin')->pay(3)->pass(1)
    ->expectProperty('p1Field-1', 'CardID', 'rpOaAjgtue', 'The Frostsworn Paladin entered the field')
    ->expectProperty('p1Graveyard-0', 'CardID', 'qrxQGA1pc6', 'The floating-memory card stays in the graveyard when the banish is declined')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No buff counter was put on the Paladin')
    ->expectZoneCount('myHand', 4, 1, 'Hand 4 = 7 + 1 seeded - 1 played - 3 reserve payments: no card was drawn')
    ->expectDqEmpty()
    ->register();

// --- Frostsworn Paladin: only a card WITH floating memory may be banished -- with none in the graveyard the On Enter has no effect ---
// Frostsworn Paladin (rpOaAjgtue): the On Enter only offers cards "with floating memory". The
// champion is the plain level-0 Spirit of Water (no floating-memory grants) and the graveyard
// holds only a Dungeon Guide (an ally with no floating memory): nothing is offered, so no card is
// drawn, nothing is banished and the Paladin gets no buff counter.
// Notes carried over from the original definition:
//   - Dungeon Guide (no floating memory) -> p1 graveyard-0
//   - Frostsworn Paladin -> p1 myHand-7
//   - play Frostsworn Paladin
gaScenario('frostsworn-paladin-enter-no-floating-memory-card')
    ->deck($mordredDeck)
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myHand', 'Frostsworn Paladin')
    ->play('Frostsworn Paladin')->pay(3)
    ->expectProperty('p1Field-1', 'CardID', 'rpOaAjgtue', 'The Frostsworn Paladin entered the field')
    ->expectProperty('p1Graveyard-0', 'CardID', 'em6eEh9q8y', 'The Dungeon Guide (no floating memory) stays in the graveyard')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No buff counter was put on the Paladin')
    ->expectZoneCount('myHand', 4, 1, 'Hand 4: no card was drawn')
    ->expectDqEmpty()
    ->register();

// --- Frostsworn Paladin: Intercept -- redirect an attack on your champion to the awake Paladin ---
// Frostsworn Paladin (rpOaAjgtue): "Intercept (Whenever your champion is attacked while this ally
// is awake, you may redirect that attack to this ally.)" (HasKeyword_Intercept ->
// GetAvailableInterceptRedirectTargets in Custom/CombatLogic.php). Player 1 has a Frostsworn Paladin
// on the field; player 2 (turn 2) attacks player 1's champion with a seeded Dungeon Guide (1
// power). The "Choose_an_interceptor" prompt offers the Paladin; redirecting moves the attack to
// it, so the Paladin takes the 1 damage and the champion takes none.
// Notes carried over from the original definition:
//   - Frostsworn Paladin (awake) -> p1 field-1
//   - Dungeon Guide (the attacker) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion
//   - player 1 intercepts with the Paladin
//   - decline retaliation
gaScenario('frostsworn-paladin-intercept-redirects-attack-on-champion')
    ->deck($mordredDeck)
    ->seed('myField', 'Frostsworn Paladin')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myField-1')->answer(1, '-')
    ->tested('Frostsworn Paladin')
    ->expectDamage('p1Field-0', 0, 'The champion takes no damage: the attack was redirected away from it')
    ->expectDamage('p1Field-1', 1, 'The Paladin takes the redirected attack\'s 1 damage (Dungeon Guide\'s printed power)')
    ->expectDqEmpty()
    ->register();

// --- Frostsworn Paladin: Intercept is not offered while the Paladin is rested (only an AWAKE ally may intercept) ---
// Frostsworn Paladin (rpOaAjgtue): the Intercept only works "while this ally is awake". The
// Paladin is seeded rested (Status 1); player 2's Dungeon Guide attacks player 1's champion and
// no interceptor prompt is offered: the champion takes the 1 damage itself.
// Notes carried over from the original definition:
//   - Frostsworn Paladin, RESTED -> p1 field-1
//   - Dungeon Guide (the attacker) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion
gaScenario('frostsworn-paladin-intercept-not-offered-while-rested')
    ->deck($mordredDeck)
    ->seed('myField', 'Frostsworn Paladin', properties: ['Status' => 1])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->tested('Frostsworn Paladin')
    ->expectDamage('p1Field-1', 0, 'The rested Paladin takes no damage: it was not offered as an interceptor')
    ->expectDamage('p1Field-0', 1, 'The champion itself takes the 1 damage')
    ->expectDqEmpty()
    ->register();

// Non-hand source (graveyard): Frost Shard (jnsl7ddcgw, WATER MAGE spell, reserve 2): "[Class Bonus] If you leveled up this
// turn, you may activate Frost Shard from your graveyard; banish it as it resolves." The champion is patched to a MAGE champion
// (with the Spirit of Water in its lineage for the WATER element) and the "leveled up this turn" flag is seeded. With an empty
// hand the reserve cost 2 cannot be paid: the click used to move the card out of the graveyard into hand, announce it and leave
// it stranded on the effect stack; it must now be refused with the card still in the graveyard.
// Notes carried over from the original definition:
//   - Nameless Champion (MAGE, TAMER) with Spirit of Water in its lineage
//   - "you leveled up this turn" (the real trigger is a level-up; seeded)
//   - empty hand (7 cards -> banishment)
//   - Frost Shard -> p1 graveyard-0
//   - 1: click Frost Shard in the graveyard with an empty hand: accepted as a no-op, refusal asserted on state
gaScenario('frost-shard-graveyard-activation-unaffordable-click-refused')
    ->deck($mordredDeck)
    ->champion(cardName: '#K7jYO9IibV', lineage: ['Spirit of Water'])
    ->globalEffect('LEVELED_UP_THIS_TURN')
    ->emptyZone('myHand', destZone: 'myBanish')
    ->seed('myGraveyard', 'Frost Shard')
    ->action(1, 10002, 'myGraveyard-0!FSM!')
    ->tested('Frost Shard')
    ->register();

// Positive pin for the same graveyard activation with two payers in hand: it is activated (2 payments) and resolves.
// Notes carried over from the original definition:
//   - Frost Shard -> p1 graveyard-0
//   - 1: activate Frost Shard from the graveyard
//   - 4: Frost Shard's target: the opposing champion
gaScenario('frost-shard-graveyard-activation-affordable-still-activates')
    ->deck($mordredDeck)
    ->champion(cardName: '#K7jYO9IibV', lineage: ['Spirit of Water'])
    ->globalEffect('LEVELED_UP_THIS_TURN')
    ->emptyZone('myHand', destZone: 'myBanish')
    ->seed('myGraveyard', 'Frost Shard')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2)->target('theirField-0')
    ->tested('Frost Shard')
    ->register();

// --- Fabled Emerald Fatestone: printed Spellshroud -- an opposing spell cannot target it ---
// Fabled Emerald Fatestone (jz7odeqku4, REGALIA,ITEM): "Immortality, Spellshroud" -- the Spellshroud is the
// card's own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords >
// Spellshroud 1: "This can't be targeted by spells"). It was never implemented: HasSpellshroud()
// (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID list and
// HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword,
// so function_exists is false); jz7odeqku4 was in neither, so FilterSpellshroudTargets() kept the card in the
// target list. Fixed: it is now in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities /
// NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Mirrordepth's Blade; player 1 plays Fracturize ("Target item or weapon
// becomes a Cleric Fractal phantasia ...", a Spell): the target prompt offers ONLY the Mirrordepth's Blade,
// answering with the holder is refused (expectFailure) and the legal answer then resolves.
// Notes carried over from the original definition:
//   - Fabled Emerald Fatestone -> p2 field-1
//   - Mirrordepth's Blade -> p2 field-2
//   - Fracturize -> p1 myHand-7
//   - player 1 plays Fracturize
//   - the Mirrordepth's Blade is the legal target
gaScenario('fabled-emerald-fatestone-printed-spellshroud-spell-cannot-target-it')
    ->deck($mordredDeck)
    ->seed('myField', 'Fabled Emerald Fatestone', player: 2)
    ->seed('myField', 'Mirrordepth\'s Blade', player: 2)
    ->seed('myHand', 'Fracturize')
    ->play('Fracturize')->pay(2)
    ->rejects(1, 'theirField-1', 'Fabled Emerald Fatestone (jz7odeqku4) has spellshroud: not a legal target for the opposing Fracturize')
    ->target('theirField-2')
    ->tested('Fabled Emerald Fatestone')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answers left Fabled Emerald Fatestone (jz7odeqku4) untouched: Spellshroud stops the opposing Fracturize from targeting it', step: 4)
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2}', 'The refused answers did not apply Fracturize to the Mirrordepth\'s Blade either (prompt still pending)', step: 4)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'Fabled Emerald Fatestone (jz7odeqku4) is unaffected by the resolved Fracturize')
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2,"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'The only legal target, Mirrordepth\'s Blade, became a Cleric Fractal phantasia (Fracturize override)')
    ->expectProperty('p1Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Fabled Ruby Fatestone: printed Spellshroud -- an opposing spell cannot target it ---
// Fabled Ruby Fatestone (mzf5dmpqbc, REGALIA,ITEM): "Immortality, Spellshroud" -- the Spellshroud is the
// card's own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords >
// Spellshroud 1: "This can't be targeted by spells"). It was never implemented: HasSpellshroud()
// (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID list and
// HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword,
// so function_exists is false); mzf5dmpqbc was in neither, so FilterSpellshroudTargets() kept the card in the
// target list. Fixed: it is now in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities /
// NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Mirrordepth's Blade; player 1 plays Fracturize ("Target item or weapon
// becomes a Cleric Fractal phantasia ...", a Spell): the target prompt offers ONLY the Mirrordepth's Blade,
// answering with the holder is refused (expectFailure) and the legal answer then resolves.
// Notes carried over from the original definition:
//   - Fabled Ruby Fatestone -> p2 field-1
//   - Mirrordepth's Blade -> p2 field-2
//   - Fracturize -> p1 myHand-7
//   - player 1 plays Fracturize
//   - the Mirrordepth's Blade is the legal target
gaScenario('fabled-ruby-fatestone-printed-spellshroud-spell-cannot-target-it')
    ->deck($mordredDeck)
    ->seed('myField', 'Fabled Ruby Fatestone', player: 2)
    ->seed('myField', 'Mirrordepth\'s Blade', player: 2)
    ->seed('myHand', 'Fracturize')
    ->play('Fracturize')->pay(2)
    ->rejects(1, 'theirField-1', 'Fabled Ruby Fatestone (mzf5dmpqbc) has spellshroud: not a legal target for the opposing Fracturize')
    ->target('theirField-2')
    ->tested('Fabled Ruby Fatestone')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answers left Fabled Ruby Fatestone (mzf5dmpqbc) untouched: Spellshroud stops the opposing Fracturize from targeting it', step: 4)
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2}', 'The refused answers did not apply Fracturize to the Mirrordepth\'s Blade either (prompt still pending)', step: 4)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'Fabled Ruby Fatestone (mzf5dmpqbc) is unaffected by the resolved Fracturize')
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2,"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'The only legal target, Mirrordepth\'s Blade, became a Cleric Fractal phantasia (Fracturize override)')
    ->expectProperty('p1Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Fabled Sapphire Fatestone: printed Spellshroud -- an opposing spell cannot target it ---
// Fabled Sapphire Fatestone (vzmnt0orxj, REGALIA,ITEM): "Immortality, Spellshroud" -- the Spellshroud is the
// card's own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords >
// Spellshroud 1: "This can't be targeted by spells"). It was never implemented: HasSpellshroud()
// (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID list and
// HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword,
// so function_exists is false); vzmnt0orxj was in neither, so FilterSpellshroudTargets() kept the card in the
// target list. Fixed: it is now in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities /
// NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Mirrordepth's Blade; player 1 plays Fracturize ("Target item or weapon
// becomes a Cleric Fractal phantasia ...", a Spell): the target prompt offers ONLY the Mirrordepth's Blade,
// answering with the holder is refused (expectFailure) and the legal answer then resolves.
// Notes carried over from the original definition:
//   - Fabled Sapphire Fatestone -> p2 field-1
//   - Mirrordepth's Blade -> p2 field-2
//   - Fracturize -> p1 myHand-7
//   - player 1 plays Fracturize
//   - the Mirrordepth's Blade is the legal target
gaScenario('fabled-sapphire-fatestone-printed-spellshroud-spell-cannot-target-it')
    ->deck($mordredDeck)
    ->seed('myField', 'Fabled Sapphire Fatestone', player: 2)
    ->seed('myField', 'Mirrordepth\'s Blade', player: 2)
    ->seed('myHand', 'Fracturize')
    ->play('Fracturize')->pay(2)
    ->rejects(1, 'theirField-1', 'Fabled Sapphire Fatestone (vzmnt0orxj) has spellshroud: not a legal target for the opposing Fracturize')
    ->target('theirField-2')
    ->tested('Fabled Sapphire Fatestone')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answers left Fabled Sapphire Fatestone (vzmnt0orxj) untouched: Spellshroud stops the opposing Fracturize from targeting it', step: 4)
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2}', 'The refused answers did not apply Fracturize to the Mirrordepth\'s Blade either (prompt still pending)', step: 4)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'Fabled Sapphire Fatestone (vzmnt0orxj) is unaffected by the resolved Fracturize')
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2,"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'The only legal target, Mirrordepth\'s Blade, became a Cleric Fractal phantasia (Fracturize override)')
    ->expectProperty('p1Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Fabled Azurite Fatestone: regression pin -- the pre-existing Spellshroud holder still blocks spells ---
// Fabled Azurite Fatestone (6ce5rzrjd9): "**Immortality**, **Spellshroud**" -- these are in HasSpellshroud()'s
// hard-coded ID list (Custom/GameLogic.php), unchanged by the printed-Spellshroud sweep fix; this fixture pins
// that their behaviour is identical:
// player 2 controls the holder(s) plus a Mirrordepth's Blade; player 1 plays Fracturize ("Target item or
// weapon becomes a Cleric Fractal phantasia ...", a Spell): the target prompt offers ONLY the Mirrordepth's
// Blade, each holder answer is refused (expectFailure) and the legal answer resolves.
// Notes carried over from the original definition:
//   - Fabled Azurite Fatestone -> p2 field-1
//   - Mirrordepth's Blade -> p2 field-2
//   - Fracturize -> p1 myHand-7
//   - player 1 plays Fracturize
//   - player 2 passes priority with Fracturize on the stack (Azurite's [REST] ability keeps a response window open)
//   - the Mirrordepth's Blade is the legal target
gaScenario('fabled-azurite-fatestone-existing-spellshroud-still-blocks-spells')
    ->deck($mordredDeck)
    ->seed('myField', 'Fabled Azurite Fatestone', player: 2)
    ->seed('myField', 'Mirrordepth\'s Blade', player: 2)
    ->seed('myHand', 'Fracturize')
    ->play('Fracturize')->pay(2)->pass(2)
    ->rejects(1, 'theirField-1', 'Fabled Azurite Fatestone (6ce5rzrjd9) has spellshroud: not a legal target for the opposing Fracturize')
    ->target('theirField-2')
    ->tested('Fabled Azurite Fatestone')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answers left Fabled Azurite Fatestone (6ce5rzrjd9) untouched: Spellshroud stops the opposing Fracturize from targeting it', step: 5)
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2}', 'The refused answers did not apply Fracturize to the Mirrordepth\'s Blade either (prompt still pending)', step: 5)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'Fabled Azurite Fatestone (6ce5rzrjd9) is unaffected by the resolved Fracturize')
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2,"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'The only legal target, Mirrordepth\'s Blade, became a Cleric Fractal phantasia (Fracturize override)')
    ->expectProperty('p1Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Flawless Spirit of Mordred: On Enter, draw seven cards, then glimpse 4 (player 2's starting-champion Enter) ---
// Flawless Spirit of Mordred (cXEI5vo6iG, CHAMPION level 0, WATER SPIRIT): "On Enter: Draw seven
// cards, then glimpse 4." Both players start with it as their champion, so the enter fires for each at
// game start. Player 1's Enter (draw 7 + glimpse prompt) is already resolved when the fixture starts;
// player 2's Enter fires FOR REAL during the replay once player 1 has answered its glimpse (the
// pregame hand-off in PREGAME_RESOLVE_STARTING_CHAMPION_ENTER): player 2 draws exactly seven cards and
// is asked to glimpse 4 (arrange the top four cards). Answering with two cards on top and two on the
// bottom (a Dungeon Guide and a Fairy Whispers on top, two Fairy Whispers at the bottom) reorders
// player 2's deck accordingly.
// Notes carried over from the original definition:
//   - player 1 keeps its glimpsed order -> player 2's champion enters: draw 7, glimpse 4
//   - player 2 keeps the Dungeon Guide and one Fairy Whispers on top and puts two Fairy Whispers on the bottom
gaScenario('flawless-spirit-of-mordred-enter-draw-seven-glimpse-four')
    ->deck($mordredSpiritDeck)
    ->answer(1, 'Top=px60u5n1do,px60u5n1do,em6eEh9q8y,n8wyfG9hbY;Bottom=')
    ->answer(2, 'Top=em6eEh9q8y,n8wyfG9hbY;Bottom=n8wyfG9hbY,n8wyfG9hbY')
    ->tested('Flawless Spirit of Mordred')
    ->expectZoneCount('myHand', 7, 2, 'Player 2\'s champion entered during the replay and drew exactly seven cards (hand 0 -> 7)')
    ->expectZoneCount('myDeck', 11, 2, 'Player 2\'s deck is 18 - 7 = 11 after the draw (the glimpse keeps all four cards in the deck)')
    ->expectProperty('p2Deck-0', 'CardID', 'em6eEh9q8y', 'Glimpse: the card chosen for the top of the deck is on top (a Dungeon Guide)')
    ->expectProperty('p2Deck-1', 'CardID', 'n8wyfG9hbY', 'Glimpse: the second card kept on top (a Fairy Whispers)')
    ->expectProperty('p2Deck-9', 'CardID', 'n8wyfG9hbY', 'Glimpse: a card chosen for the bottom is second to last (a Fairy Whispers)')
    ->expectProperty('p2Deck-10', 'CardID', 'n8wyfG9hbY', 'Glimpse: a card chosen for the bottom is the last card (a Fairy Whispers)')
    ->expectZoneCount('myHand', 7, 1, 'Player 1\'s hand holds its seven drawn cards')
    ->expectDqEmpty()
    ->register();

// --- Flawless Spirit of Mordred: can only level up into a "Mordred" champion (a non-Mordred champion is refused) ---
// Flawless Spirit of Mordred (cXEI5vo6iG): "CARDNAME can only level up into a 'Mordred' champion." The
// material deck holds Mordred, Burnished Avenger and Tristan, Underhanded (both level 1). After both
// glimpses are answered and the turns pass, in player 1's turn-3 materialize phase choosing
// Tristan, Underhanded is refused by the engine (DoMaterialize returns without leveling): the champion
// stays the Spirit, the material deck keeps both cards and the seeded memory card is not spent (it is
// returned to hand by recollection).
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - player 1 keeps its glimpsed order -> player 2's champion enters
//   - player 2 keeps its glimpsed order
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - try to level up into Tristan, Underhanded: the engine refuses it
gaScenario('flawless-spirit-of-mordred-cannot-level-up-into-non-mordred')
    ->deck($mordredSpiritDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->answer(1, 'Top=px60u5n1do,px60u5n1do,em6eEh9q8y,n8wyfG9hbY;Bottom=')
    ->answer(2, 'Top=n8wyfG9hbY,n8wyfG9hbY,n8wyfG9hbY,em6eEh9q8y;Bottom=')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-1')
    ->tested('Flawless Spirit of Mordred')
    ->expectProperty('p1Field-0', 'CardID', 'cXEI5vo6iG', 'The champion is still Flawless Spirit of Mordred: the level-up into Tristan, Underhanded was refused')
    ->expectZoneCount('myMaterial', 2, 1, 'The material deck keeps both level 1 champions')
    ->expectZoneCount('myBanish', 0, 1, 'The level-up memory cost was not spent (nothing banished)')
    ->expectZoneCount('myHand', 9, 1, 'Hand 9 = 7 + 1 seeded memory card returned by recollection + 1 turn draw: the memory card was never spent')
    ->expectProperty('p1Field-0', 'Status', '2', 'The champion is awake and unchanged')
    ->expectDqEmpty()
    ->register();

// --- Flawless Spirit of Mordred: it CAN level up into a "Mordred" champion ---
// Flawless Spirit of Mordred (cXEI5vo6iG): same setup as flawless-spirit-of-mordred-cannot-level-up-into-
// non-mordred, but the real level-up goes into Mordred, Burnished Avenger (memory cost 1), which the
// Spirit's restriction allows: the champion becomes Burnished Avenger with the Spirit in its lineage.
// Notes carried over from the original definition:
//   - level-up memory cost (1)
//   - player 1 keeps its glimpsed order -> player 2's champion enters
//   - player 2 keeps its glimpsed order
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - level up into Mordred, Burnished Avenger
gaScenario('flawless-spirit-of-mordred-levels-up-into-mordred')
    ->deck($mordredSpiritDeck)
    ->seed('myMemory', 'Fairy Whispers')
    ->answer(1, 'Top=px60u5n1do,px60u5n1do,em6eEh9q8y,n8wyfG9hbY;Bottom=')
    ->answer(2, 'Top=n8wyfG9hbY,n8wyfG9hbY,n8wyfG9hbY,em6eEh9q8y;Bottom=')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Flawless Spirit of Mordred')
    ->expectProperty('p1Field-0', 'CardID', 'OWCdWq3mXY', 'The level-up into Mordred, Burnished Avenger completed')
    ->expectProperty('p1Field-0', 'Subcards', '["cXEI5vo6iG"]', 'Flawless Spirit of Mordred is now in the lineage')
    ->expectZoneCount('myMaterial', 1, 1, 'One card left the material deck')
    ->expectZoneCount('myBanish', 1, 1, 'The memory cost (1) banished the seeded memory card')
    ->expectProperty('p2Field-0', 'CardID', 'cXEI5vo6iG', 'Player 2\'s champion is still its Flawless Spirit of Mordred (untouched)')
    ->expectDqEmpty()
    ->register();

// --- Foraging Fox: declining the Fatestone reveal still puts all five looked-at cards on the bottom (dontSkipOnPass regression) ---
// Foraging Fox (b0ssellm84): "On Enter: Look at the top five cards of your deck. You may reveal a Fatestone card from among them and put it into your memory. Put the rest on the bottom of your deck in any order." Declining the optional reveal must still put the rest -- here all five cards -- on the bottom of the deck.
// Regression: the generated enterAbilities closure queued its follow-up ('b0ssellm84:0:Enter-1') without dontSkipOnPass, so ExecuteStaticMethods() skipped it when the player answered PASS and ForagingFoxChooseBottom() never ran: the five cards stayed in the temp zone and the deck was 5 cards short (8 instead of 13). Fixed by overriding the generated closure in Custom/GeneratedAbilityOverrides.php with dontSkipOnPass:1 (the generated file is gitignored).
// Same setup as foraging-fox-enter-look5-reveal-fatestone (one Fatestone among the top five), but the reveal is declined and the rearrange prompt answered with the full ordering.
// Notes carried over from the original definition:
//   - Foraging Fox in hand slot 0
//   - top-5 slot 3: the ONE Fatestone
//   - reserve 1/2
//   - reserve 2/2
//   - decline revealing the Fatestone
//   - order the five cards onto the bottom
gaScenario('foraging-fox-decline-bottoms-all-five')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
20 Dungeon Guide
DECK
    )
    ->patch('myHand-0', ['CardID' => 'b0ssellm84'])
    ->patch('myDeck-2', ['CardID' => 'h8n1520m2d'])
    ->playSlot(0)->pay(2)->pass(1)->answer(1, 'Bottom=em6eEh9q8y,em6eEh9q8y,h8n1520m2d,em6eEh9q8y,em6eEh9q8y')
    ->tested('Foraging Fox')
    ->expectZoneCount('myDeck', 13, 1, 'All five looked-at cards go back on the bottom of the deck (8 remaining + 5 = 13)')
    ->expectDqEmpty()
    ->register();

// --- Fractal of Refreshment: declining a further reveal still draws into memory for the cards already bottomed (dontSkipOnPass regression) ---
// Fractal of Refreshment (cxqf8rr452): "On Enter: Reveal up to three water element cards from your memory and put them on the bottom of your deck. Draw that many cards into your memory." Stopping before three (declining the 'Reveal and bottom a water card?' prompt with PASS) must still draw one card into memory per card already bottomed.
// Regression: FractalRefreshPick's loop re-queue (Custom/GameLogic.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its decline branch (DrawIntoMemory for the bottomed count) never ran. (fractal-of-refreshment-bottom-2-draw-2 declines with '-', which is not 'PASS' and so bypassed the skip; the real client sends PASS.) The first prompt (queued by the generated ability) needs no flag: declining it with 0 bottomed draws nothing either way.
// Same setup as fractal-of-refreshment-bottom-2-draw-2 (two WATER Frostbinder Apostles in memory; the champion is patched to Diao Chan (CLERIC) with a Spirit of Water lineage); two cards are bottomed and the third offer is declined with PASS.
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress (CLERIC) + Spirit of Water lineage (WATER unlock)
//   - Frostbinder Apostle (WATER) #1
//   - Frostbinder Apostle (WATER) #2
//   - Dungeon Guide (NORM) -- not water, must not be offered
//   - pay reserve 1/3
//   - pay reserve 2/3
//   - pay reserve 3/3
//   - reveal and bottom water card #1
//   - reveal and bottom water card #2
//   - decline the 3rd offer
gaScenario('fractal-of-refreshment-decline-third-reveal-still-draws')
    ->deckTemplate('diao-awakened-deacon', 'Fractal of Refreshment')
    ->champion(cardName: 'Diao Chan, Enchantress', lineage: ['Spirit of Water'])
    ->seed('myMemory', 'Frostbinder Apostle')
    ->seed('myMemory', 'Frostbinder Apostle')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Fractal of Refreshment')
    ->play('Fractal of Refreshment')->pay(3)->target('myMemory-0')->target('myMemory-0')->pass(1)
    ->expectZoneCount('myMemory', 6, 1, 'Two water cards bottomed, then 2 cards drawn into memory even though the 3rd offer was declined (memory back to 6)')
    ->expectZoneCount('myDeck', 13, 1, 'Deck: 13 + 2 bottomed - 2 drawn = 13')
    ->expectDqEmpty()
    ->register();

// --- Found Power: declining the second discard still draws for the card already discarded (dontSkipOnPass regression) ---
// Found Power (8pIXnuI1Df): "Empower 3. If you control an object named Proto Key Crest, discard up to two cards, then draw that many cards." Stopping after one discard (declining the 'Discard a card? (Found Power 2/2)' prompt with PASS) must still draw one card for the card already discarded.
// Regression: FoundPowerDiscard1 queued its paired CUSTOM 'FoundPowerDiscard2' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its draw step (Draw for the discarded count) never ran: the first card was discarded and nothing was drawn.
// Proto Key Crest is seeded onto the field (it is only the condition, not the card under test); Found Power itself is activated from hand through real actions. One card is discarded, the second prompt is declined with PASS, one card is drawn.
// Notes carried over from the original definition:
//   - Proto Key Crest (condition for the discard/draw clause)
//   - pay reserve 1/1
//   - discard the first card (Found Power 1/2)
//   - decline the second discard (Found Power 2/2)
gaScenario('found-power-decline-second-discard-still-draws')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myField', 'Proto Key Crest')
    ->seed('myHand', 'Found Power')
    ->play('Found Power')->pay(2)->pass(1)
    ->expectZoneCount('myHand', 6, 1, 'Hand 8 - Found Power - 1 reserve payment - 1 discard + 1 draw = 6: one card drawn for the one card discarded even though the second discard was declined')
    ->expectZoneCount('myGraveyard', 2, 1, 'Graveyard holds Found Power and the one discarded card')
    ->expectDqEmpty()
    ->register();

// Notes carried over from the original definition:
//   - 16 + 5 = 21 damage counters after the hit
gaScenario('final-stroke-prepare-3-paid-20-damage-destroys-champion')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 3]], player: 2)
    ->champion(cardName: 'Ciel, Mirage\'s Grave', damage: 16)
    ->seed('myHand', 'Final Stroke', player: 2)
    ->endTurn()
    ->play('Final Stroke', 2)->pay(4, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->expectProperty('p2Field-0', 'Counters', '{"preparation":3}', 'Prepare 3 is only a pending YES/NO: all 3 counters still present', step: 6)
    ->expectProperty('p2Field-0', 'Counters', '[]', 'Prepare 3 paid: all 3 preparation counters removed', step: 7)
    ->expectProperty('p2Intent-0', 'TurnEffects', '["PREPARED"]', 'Final Stroke tagged PREPARED', step: 7)
    ->expectDamage('p1Field-0', 16, 'Player 1\'s champion has the seeded 16 damage before the hit', step: 7)
    ->expectZoneCount('myField', 0, 1, 'Prepared + 21 (>= 20) damage counters: player 1\'s champion (30 life) was destroyed and left the field')
    ->expectZoneCount('myGraveyard', 1, 1, 'The destroyed champion is in player 1\'s graveyard')
    ->expectProperty('p1Graveyard-0', 'CardID', 'zhh43i1eaa', 'It is Ciel, Mirage\'s Grave')
    ->expectProperty('p2Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":5}', '5 combat damage was dealt (printed power 5)')
    ->register();

// Notes carried over from the original definition:
//   - 14 + 5 = 19 damage counters: below the 20 threshold
gaScenario('final-stroke-prepare-3-paid-under-20-damage-champion-survives')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 3]], player: 2)
    ->champion(cardName: 'Ciel, Mirage\'s Grave', damage: 14)
    ->seed('myHand', 'Final Stroke', player: 2)
    ->endTurn()
    ->play('Final Stroke', 2)->pay(4, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

gaScenario('final-stroke-prepare-3-declined-20-damage-champion-survives')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 3]], player: 2)
    ->champion(cardName: 'Ciel, Mirage\'s Grave', damage: 16)
    ->seed('myHand', 'Final Stroke', player: 2)
    ->endTurn()
    ->play('Final Stroke', 2)->pay(4, 2)->answer(2, 'NO')->answer(2, 'theirField-0')
    ->register();

gaScenario('fishing-accident-prepare-2-paid-ally-to-bottom-of-deck')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'tafqldAGRF'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Fishing Accident', player: 2)
    ->endTurn()
    ->play('Fishing Accident', 2)->answer(2, 'myHand-7')->pay(1, 2)->pass(1)->answer(2, 'YES')
    ->answer(2, 'theirField-1')
    ->register();

gaScenario('fishing-accident-prepare-2-declined-ally-rested')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'tafqldAGRF'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Fishing Accident', player: 2)
    ->endTurn()
    ->play('Fishing Accident', 2)->answer(2, 'myHand-7')->pay(1, 2)->pass(1)->answer(2, 'NO')
    ->answer(2, 'theirField-1')
    ->register();

gaScenario('freezing-hail-refuses-spellshroud-unit-damages-ordinary-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Freezing Hail')
    ->seed('myField', 'Genbu, Black Tortoise', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->play('Freezing Hail')->pay(2)
    ->rejects(1, 'theirField-1', 'Genbu, Black Tortoise has spellshroud: not a legal target of the Spell Freezing Hail')
    ->target('theirField-2')
    ->register();

// Fiery Swing (ijkyboiopv): "[Class Bonus] On Attack: You may banish up to six fire element cards from your graveyard. For each card banished
// this way, CARDNAME gets +1 POWER." Banish one fire card, then decline the second prompt: the +1 POWER must still be applied
// (FierySwingBanish was queued without dontSkipOnPass, so the decline skipped it and the power bonus for the banished card was lost).
// Notes carried over from the original definition:
//   - Class Bonus active
//   - Cell Handler (fire) -> myGraveyard-0
//   - Cell Handler (fire) -> myGraveyard-1
//   - Fiery Swing -> myHand-7
gaScenario('fiery-swing-decline-second-banish-still-applies-power-bonus')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myGraveyard', 'Cell Handler')
    ->seed('myGraveyard', 'Cell Handler')
    ->seed('myHand', 'Fiery Swing')
    ->endTurn()
    ->endTurn(2)->answer(1, '-')
    ->play('Fiery Swing')->pay(6)->target('theirField-0')->target('myGraveyard-0')->pass(1)
    ->register();

// Facet Together (XmsEbk19Iu): "Sacrifice any amount of Memorite objects. Target weapon you control gets +X POWER until the end of your next turn, where X is
// the amount of objects sacrificed this way. Then put X sheen counters on your Fractured Memories." Sacrifice one Memorite, then decline the second offer: the
// weapon must still get +1 POWER (FacetTogetherSacrifice was queued without dontSkipOnPass, so the decline skipped the handler and the weapon never got its bonus).
// Notes carried over from the original definition:
//   - Lorraine, Wandering Warrior (WARRIOR) + Spirit of Wind lineage: class + WIND element
//   - Memorite Blade (weapon) -> myField-1
//   - Memorite Obelith -> myField-2
//   - Memorite Obelith -> myField-3
//   - Facet Together -> myHand-7
gaScenario('facet-together-decline-second-sacrifice-still-buffs-weapon')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Spirit of Wind'])
    ->seed('myField', 'Memorite Blade')
    ->seed('myField', 'Memorite Obelith')
    ->seed('myField', 'Memorite Obelith')
    ->seed('myHand', 'Facet Together')
    ->endTurn()->pass(1)->pass(1)
    ->playSlot(0, 2)->pay(3, 2)->target('myHand-7')->pay(1)->target('myField-2')->pass(1)->target('myField-1')
    ->answer(2, 'NO')
    ->tested('Facet Together')
    ->register();

// Freezing Gambit (fgBpQZe0js): "As an additional cost to activate this card, sacrifice a Chessman ally. Choose one or both-- Negate target card activation unless its controller pays (2); Target unit's attacks get -3 POWER until end of turn."
// Notes carried over from the original definition:
//   - CLERIC class
//   - Pawn Piece (CHESSMAN ally) -> myField-1
//   - Freezing Gambit -> myHand-7
gaScenario('freezing-gambit-sacrifices-a-chessman-and-debuffs-the-target')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'CLERIC')
    ->seed('myField', 'Pawn Piece')
    ->seed('myHand', 'Freezing Gambit')
    ->play('Freezing Gambit')->target('myField-1')->pay(2)->target('theirField-0')
    ->register();

// Found Power (8pIXnuI1Df): "discard up to two cards, then draw that many." Each of the two discard offers is its own optional prompt: declining the FIRST (PASS) must still offer the second.
// FoundPowerDiscard1 was queued by the generated body without dontSkipOnPass, so declining the first skipped it and the second offer never came.
// Notes carried over from the original definition:
//   - Proto Key Crest (the condition)
//   - Found Power -> myHand-7
gaScenario('found-power-decline-first-discard-still-offers-the-second')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Proto Key Crest')
    ->seed('myHand', 'Found Power')
    ->play('Found Power')->pay(1)->pass(1)->pay(1)
    ->expectZoneCount('myGraveyard', 2, 1, 'Found Power and the one discarded card')
    ->expectZoneCount('myDeck', 12, 1, 'One card was drawn for the one discard')
    ->expectZoneCount('myHand', 6, 1, 'Eight, minus Found Power, minus one paid, minus one discarded, plus one drawn')
    ->expectDqEmpty()
    ->register();

// Spells that build their target list without FilterSpellshroudTargets() are filtered once, centrally (GASpellshroudFilterNewDecisions). Opposing Aquamirage Whisper (printed spellshroud) must be refused as a target, a Dungeon Guide accepted.
// Notes carried over from the original definition:
//   - MAGE class (cost reduction)
//   - Aquamirage Whisper (printed spellshroud) -> theirField-1
//   - Dungeon Guide -> theirField-2
//   - Fireball -> myHand-7
//   - the Dungeon Guide is the legal target
gaScenario('fireball-spellshroud-unit-is-not-a-legal-target')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'MAGE')
    ->seed('myField', 'Aquamirage Whisper', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Fireball')
    ->play('Fireball')->pay(2)
    ->rejects(1, 'theirField-1', 'Aquamirage Whisper has spellshroud: not a legal target for Fireball')
    ->target('theirField-2')
    ->register();

// Notes carried over from the original definition:
//   - MAGE class (cost reduction) ; WATER element
//   - Aquamirage Whisper (printed spellshroud) -> theirField-1
//   - Dungeon Guide -> theirField-2
//   - Freezing Hail -> myHand-7
//   - the Dungeon Guide is the legal target
gaScenario('freezing-hail-spellshroud-unit-is-not-a-legal-target')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Spirit of Water'], classes: 'MAGE')
    ->seed('myField', 'Aquamirage Whisper', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Freezing Hail')
    ->play('Freezing Hail')->pay(2)
    ->rejects(1, 'theirField-1', 'Aquamirage Whisper has spellshroud: not a legal target for Freezing Hail')
    ->target('theirField-2')
    ->register();

// Notes carried over from the original definition:
//   - MAGE class (cost reduction)
//   - Aquamirage Whisper (printed spellshroud) -> theirField-1
//   - Dungeon Guide -> theirField-2
//   - Focused Flames -> myHand-7
//   - the Dungeon Guide is the legal target
gaScenario('focused-flames-spellshroud-unit-is-not-a-legal-target')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'MAGE')
    ->seed('myField', 'Aquamirage Whisper', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Focused Flames')
    ->play('Focused Flames')->pay(1)
    ->rejects(1, 'theirField-1', 'Aquamirage Whisper has spellshroud: not a legal target for Focused Flames')
    ->target('theirField-2')
    ->register();

// Flamme Sorcel (j6er6z99sv): "Draw a card, then discard a card. If the reserve cost of the discarded card is equal to the reserve cost of one of your omens, draw another card."
// Notes carried over from the original definition:
//   - an omen with reserve cost 3 (Dungeon Guide)
//   - Flamme Sorcel -> myHand-7
gaScenario('flamme-sorcel-discarding-a-card-with-an-omens-cost-draws-another')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Flamme Sorcel')
    ->play('Flamme Sorcel')->pay(3)
    ->expectZoneCount('myDeck', 11, 1, 'One card for the spell and another because the discarded card matched an omen\'s reserve cost')
    ->expectZoneCount('myGraveyard', 2, 1, 'Flamme Sorcel and the discarded card')
    ->expectDqEmpty()
    ->register();

// Foresight Lens (drnxdiltx3): "Banish Foresight Lens: Glimpse 2. If your champion is distant, glimpse 4 instead."
// Notes carried over from the original definition:
//   - Foresight Lens -> myField-1
gaScenario('foresight-lens-banish-glimpses-two')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Foresight Lens')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'Top=px60u5n1do;Bottom=em6eEh9q8y')
    ->tested('Foresight Lens')
    ->register();

// Notes carried over from the original definition:
//   - Foresight Lens -> myField-1
gaScenario('foresight-lens-banish-while-distant-glimpses-four')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Foresight Lens')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep'])
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->answer(1, 'Top=px60u5n1do;Bottom=em6eEh9q8y,px60u5n1do,em6eEh9q8y')
    ->tested('Foresight Lens')
    ->register();

// Notes carried over from the original definition:
//   - Sojourner's Hunt (a Bow) -> myField-1
//   - Frozen Quill -> myField-2
gaScenario('frozen-quill-rest-loads-itself-into-an-unloaded-bow')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Sojourner\'s Hunt')
    ->seed('myField', 'Frozen Quill')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->tested('Frozen Quill')
    ->register();

// Notes carried over from the original definition:
//   - Fabled Emerald Fatestone -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('fabled-emerald-fatestone-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'TAMER'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Fabled Emerald Fatestone')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)->target('myField-2')->pass(1)
    ->tested('Fabled Emerald Fatestone')
    ->register();

// Notes carried over from the original definition:
//   - Fabled Emerald Fatestone -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('fabled-emerald-fatestone-ability-2-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'TAMER'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Fabled Emerald Fatestone')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->answer(1, 'YES')->pass(1)
    ->tested('Fabled Emerald Fatestone')
    ->expectZoneCount('myHand', 13, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
    ->expectZoneCount('myMemory', 2, 1, 'Memory after the reserve payments / draws into memory')
    ->expectZoneCount('myBanish', 0, 1, 'Banishment after the printed banish costs')
    ->expectZoneCount('myGraveyard', 3, 1, 'Graveyard after the printed sacrifice / banish costs')
    ->expectZoneCount('myField', 7, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost')
    ->register();

// Notes carried over from the original definition:
//   - Fabled Azurite Fatestone -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('fabled-azurite-fatestone-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'TAMER'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Fabled Azurite Fatestone')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Fabled Azurite Fatestone')
    ->register();

// Notes carried over from the original definition:
//   - Fabled Sapphire Fatestone -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('fabled-sapphire-fatestone-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'TAMER'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Fabled Sapphire Fatestone')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Fabled Sapphire Fatestone')
    ->expectZoneCount('myHand', 13, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
    ->expectZoneCount('myMemory', 2, 1, 'Memory after the reserve payments / draws into memory')
    ->expectZoneCount('myBanish', 0, 1, 'Banishment after the printed banish costs')
    ->expectZoneCount('myGraveyard', 3, 1, 'Graveyard after the printed sacrifice / banish costs')
    ->expectZoneCount('myField', 7, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost')
    ->register();

// Notes carried over from the original definition:
//   - Fabled Ruby Fatestone -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('fabled-ruby-fatestone-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'TAMER'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Fabled Ruby Fatestone')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Fabled Ruby Fatestone')
    ->expectZoneCount('myHand', 13, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
    ->expectZoneCount('myMemory', 2, 1, 'Memory after the reserve payments / draws into memory')
    ->expectZoneCount('myBanish', 0, 1, 'Banishment after the printed banish costs')
    ->expectZoneCount('myGraveyard', 3, 1, 'Graveyard after the printed sacrifice / banish costs')
    ->expectZoneCount('myField', 7, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost')
    ->register();

// Notes carried over from the original definition:
//   - Focusing Round -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('focusing-round-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'RANGER'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'Focusing Round')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->pass(1)
    ->tested('Focusing Round')
    ->register();

// Notes carried over from the original definition:
//   - FlameTech Manual -> myField-1
//   - allies and a Powercell
//   - an opposing ally
//   - fire, floating-memory and water cards
//   - a Bow weapon and a non-Human Slime ally
//   - reserve fuel
gaScenario('flametech-manual-ability-1-pays-its-printed-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Counters' => ['_overrides' => ['classes' => 'MAGE'], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
    ->seed('myField', 'FlameTech Manual')
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
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myGraveyard-0')->target('myField-0')->pass(1)
    ->tested('FlameTech Manual')
    ->register();

// Fran, Carmine Spark (WRu4cuRHOS): "[Class Bonus] On Attack: Target player banishes a card from their graveyard. If a fire element card was banished this way, you empower 3." The banish moved the card for
// `$obj->Owner` (graveyard objects have no Owner: null), which flipped the zone perspective, so the wrong card moved. Player 2's Baby Red Slime (fire) is the only graveyard card.
// Notes carried over from the original definition:
//   - Mage champion: the Class Bonus applies
//   - Fran, Carmine Spark -> field-1
//   - Baby Red Slime (fire) -> theirGraveyard-0
gaScenario('fran-carmine-spark-on-attack-banishes-opponents-fire-graveyard-card-and-empowers')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'MAGE')
    ->seed('myField', 'Fran, Carmine Spark')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myGraveyard', 'Baby Red Slime', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')->target('theirGraveyard-0')
    ->tested('Fran, Carmine Spark', 'Baby Red Slime')
    ->register();
