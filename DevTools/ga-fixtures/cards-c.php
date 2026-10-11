<?php
/**
 * Fixtures for cards starting with 'C', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Corhazi Arsonist: remove a preparation counter for stealth ---
// Corhazi Arsonist's element is FIRE (matching the starting champion), so no lineage patch is
// needed. The starting champion is pre-seeded with 1 preparation counter directly (normally
// only reachable via a separate preparation-counter-granting effect) so the "Prepare"
// activated ability's cost (remove 1 preparation counter from your champion, resolved by the
// ActivatedAbilityCost() switch in GameLogic.php) can actually be paid. Only the always-
// available "gain stealth" half is covered; the onHit "banish instead of die" replacement
// requires a real combat hit and is out of scope.
// Notes carried over from the original definition:
//   - Prepare-ability cost fuel
gaScenario('corhazi-arsonist-prepare-stealth')
    ->standardDeck('Corhazi Arsonist')
    ->patch('myField-0', ['Counters' => ['preparation' => 1]])
    ->seed('myHand', 'Corhazi Arsonist')
    ->castSpell('Corhazi Arsonist', pay: 3)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->passBoth()
    ->expectProperty('p1Field-1', 'Status', '2', 'Corhazi Arsonist is awake before its ability is activated', step: 4)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","STEALTH"]', 'Corhazi Arsonist gains stealth until end of turn')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The champion\'s preparation counter is spent as the ability\'s cost')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Status', '2', 'Corhazi Arsonist\'s printed cost has no [REST] (Remove a preparation counter from your champion): it is still awake after the activation')
    ->register();

// --- Coronal of Rejuvenation: On Enter banish Spell cards from graveyard ---
// Coronal of Rejuvenation is a TERA (advanced element) REGALIA,ITEM card, so the starting
// champion's Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock
// element access. Like entrancing-filigree-enter-banish, its additional materialize cost
// ("banish a preserved card from your material deck") is NOT exercised here for the same
// FSM-click-bypass reason. Luminous Surge (a SPELL card) is seeded into the graveyard as the
// On Enter banish target. Only the On Enter banish is covered; the [REST] "play a card
// banished by CARDNAME" ability is out of scope.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Luminous Surge (SPELL) - On Enter banish target
gaScenario('coronal-of-rejuvenation-banish-spell')
    ->standardDeck('Coronal of Rejuvenation')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myGraveyard', 'Luminous Surge')
    ->seed('myHand', 'Coronal of Rejuvenation')
    ->playSlot(6)->target('myGraveyard-0')
    ->tested('Coronal of Rejuvenation')
    ->register();

// --- Coriolis Ward: prevent the next 1+level damage to target unit ---
// Coriolis Ward's element is WATER, so the starting champion's Subcards are patched with a
// real WATER champion (Spirit of Water) to unlock element access. The champion is level 0 by
// default, so the prevention amount resolves to the base 1+0=1. The "Shifting Currents face
// West" bonus draw is tied to a separate facing-state subsystem and is out of scope.
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
gaScenario('coriolis-ward-prevent-level')
    ->standardDeck('Coriolis Ward')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myHand', 'Coriolis Ward')
    ->castSpell('Coriolis Ward', pay: 1, window: 'both', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","PREVENT_ALL_1"]', 'With champion level 0, the prevention amount is the base 1+0=1')
    ->expectDqEmpty()
    ->register();

// --- Clarent, Sword of Peace: Class Bonus + remove durability counter to prevent noncombat damage ---
// Clarent, Sword of Peace's own activated ability requires a WARRIOR Class Bonus (physical
// presence, so Lorraine, Wandering Warrior is seeded directly onto the field) and a durability
// counter on Clarent itself (seeded directly, since it's normally only present via the card's
// own materialize-time durability allotment). Clarent is seeded straight onto the field rather
// than played from hand since this fixture is about its activated ability, not its
// materialize flow.
// Notes carried over from the original definition:
//   - Lorraine, Wandering Warrior (WARRIOR CHAMPION) - Class Bonus source
//   - Clarent, Sword of Peace - ability cost fuel
gaScenario('clarent-sword-of-peace-prevent')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Lorraine, Wandering Warrior')
    ->seed('myField', 'Clarent, Sword of Peace', properties: ['Counters' => ['durability' => 1]])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->passBoth()
    ->tested('Clarent, Sword of Peace')
    ->expectProperty('p1Field-0', 'TurnEffects', '["PREVENT_NONCOMBAT_1"]', 'The champion is protected by the noncombat-damage shield')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_NONCOMBAT_1"]', 'Lorraine (the Class Bonus source) is also protected, since the effect applies to every unit we control')
    ->expectProperty('p1Field-2', 'Counters', '[]', 'The durability counter is spent as the ability\'s cost')
    ->expectDqEmpty()
    ->register();

// NOTE: Creeping Torment was attempted first but abandoned -- its On Enter handler
// (customDQHandlers["zrplywc08c:0:Enter-1"], GeneratedMacroCode.php) calls MZRemove(), a function
// that doesn't exist anywhere in the codebase (verified: 7 call sites across CardDQHandlers.php
// and GeneratedMacroCode.php, zero definitions), causing a PHP fatal "Call to undefined function"
// error rather than a clean no-op. Flagged separately as task_bdcead88. Swapped for Charged
// Manaplate, which has no such dependency.
// --- Charged Manaplate: Class Bonus banish for a draw, gated on this-turn champion damage ---
// Charged Manaplate's element is NORM, so no lineage patch is needed, and its memory cost (0)
// needs no floating-payment setup. The ability requires both a GUARDIAN Class Bonus (physical
// presence, so Ciel, Loyal Valet is seeded directly onto the field) and the champion having
// taken 4+ damage this turn, which GetChampionDamageTakenThisTurn() reads directly from a
// Counters["_champDamageThisTurn"] entry on the champion object -- patched in directly rather
// than scripting a real combat/damage sequence.
// Notes carried over from the original definition:
//   - Ciel, Loyal Valet (GUARDIAN CHAMPION) - Class Bonus source
//   - This-turn damage prereq
gaScenario('charged-manaplate-banish-draw')
    ->standardDeck('Charged Manaplate')
    ->seed('myField', 'Ciel, Loyal Valet')
    ->patch('myField-0', ['Counters' => ['_champDamageThisTurn' => 4]])
    ->seed('myHand', 'Charged Manaplate')
    ->playSlot(4)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')
    ->tested('Charged Manaplate')
    ->register();

// --- Crux Sight: draw a card (additional cost not exercised) ---
// Crux Sight is a CRUX (advanced element) ACTION card, so the starting champion's Subcards
// are patched with a real CRUX champion (Lorraine, Crux Knight). Its reserve cost is 0, so no
// reserve payment decisions are needed. Only the always-available "Draw a card" is covered;
// the optional (2) additional cost (self-banish + return a crux card from graveyard) isn't
// paid.
// Notes carried over from the original definition:
//   - CRUX lineage/element unlock
gaScenario('crux-sight-draw')
    ->standardDeck('Crux Sight')
    ->champion(lineage: ['Lorraine, Crux Knight'])
    ->seed('myHand', 'Crux Sight')
    ->play('Crux Sight')->answer(1, 'NO')->passBoth()
    ->register();

// --- Creative Shock: draw two cards, then discard one ---
// Creative Shock's element is FIRE (matching the starting champion), so no lineage patch is
// needed. Only the base "draw 2, discard 1" is covered; the [Class Bonus] fire-discard damage
// clause is out of scope (no class bonus set up, and the discarded card isn't guaranteed FIRE).
gaScenario('creative-shock-draw-discard')
    ->standardDeck('Creative Shock')
    ->seed('myHand', 'Creative Shock')
    ->play('Creative Shock')->pay(3)->passBoth()->pay(1)
    ->expectZoneCount('myDeck', 7, 1, 'Drawing 2 cards reduces the deck by two')
    ->expectZoneCount('myGraveyard', 2, 1, 'Creative Shock\'s own resolution plus the discarded card leave 2 cards in the graveyard')
    ->expectDqEmpty()
    ->register();

// --- Creative Tinder: draw two cards, then discard a card ---
// Creative Tinder's element is FIRE, matched by the default starting champion (Spirit of
// Fire), so no lineage patch is needed. Without a [Class Bonus] discount, the full 3-reserve
// cost is paid; completing the 3rd reserve payment cascades directly into the ability itself
// (no separate opportunity-window PASS is needed for this card), which draws two cards and
// immediately opens its own MZCHOOSE asking which one to discard.
gaScenario('creative-tinder-draw-discard')
    ->standardDeck('Creative Tinder')
    ->seed('myHand', 'Creative Tinder')
    ->castSpell('Creative Tinder', pay: 4)
    ->expectZoneCount('myHand', 5, 1, 'Net hand change of +1 after drawing two and discarding one')
    ->expectZoneCount('myGraveyard', 2, 1, 'Creative Tinder and the discarded card are both in the graveyard')
    ->expectDqEmpty()
    ->register();

// --- Crimson Vein: whenever you recover, put a blood counter on it ---
// Crimson Vein's element is EXIA, so the starting champion's Subcards are patched with a real
// EXIA champion (Dante, Hemomancer) to unlock element access. It is seeded directly onto the
// field (REGALIA convention) with 0 blood counters; without the [Class Bonus] discount it does
// not enter with any. The champion is pre-damaged so Harmonious Mantra's Recover 3 (NORM,
// castable regardless of champion identity) is observable. Only the unconditional "whenever
// you recover, put a blood counter on CARDNAME" trigger is covered by asserting Counters; the
// resulting +X[LIFE] champion buff is a dynamically computed value with no stored property to
// assert against, so it is not independently proven here.
// Notes carried over from the original definition:
//   - EXIA lineage/element unlock, pre-damaged so recover is observable
//   - Crimson Vein (REGALIA)
gaScenario('crimson-vein-recover-counter')
    ->standardDeck('Harmonious Mantra')
    ->champion(lineage: ['Dante, Hemomancer'], damage: 5)
    ->seed('myField', 'Crimson Vein')
    ->seed('myHand', 'Harmonious Mantra')
    ->castSpell('Harmonious Mantra', pay: 2)
    ->tested('Crimson Vein')
    ->register();

// --- Creeping Torment: On Enter, put itself on the bottom of target champion's lineage ---
// Creeping Torment's element is UMBRA, so the starting champion's Subcards are patched with a
// real UMBRA champion (Tristan, Shadowreaver) to unlock element access. Without a [Class
// Bonus] discount, the full 1-reserve cost is paid, then both players decline the
// EffectStackActiveResponse opportunity window before the ability's own MZCHOOSE (which
// champion to add to the lineage) resolves, targeting our own champion.
// Notes carried over from the original definition:
//   - UMBRA lineage/element unlock
gaScenario('creeping-torment-lineage')
    ->standardDeck('Creeping Torment')
    ->champion(lineage: ['Tristan, Shadowreaver'])
    ->seed('myHand', 'Creeping Torment')
    ->castSpell('Creeping Torment', pay: 1, target: 'myField-0')
    ->register();

// --- Cryogenic Ritual: sacrifice an ally to summon a Core Fractal token ---
// Regression test for a fixed bug: activating Cryogenic Ritual never actually sacrificed the
// ally its own additional cost requires (see GrandArchiveSim/Custom/GameLogic.php --
// DoActivateCard now declares this cost the same way every other sacrifice-cost card does).
// Cryogenic Ritual's element is WATER, so the starting champion's Subcards are patched with a
// real WATER champion (Spirit of Water) to unlock element access. A Dungeon Guide (ALLY) is
// seeded onto the field to serve as the sacrifice target. Materializing it opens a real
// MZCHOOSE for the sacrifice target BEFORE reserve payment; choosing the Dungeon Guide pays
// the full 2-reserve cost, then the ability itself (unaffected by this fix) summons a Core
// Fractal token.
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
//   - Dungeon Guide (ALLY) - sacrifice fuel
gaScenario('cryogenic-ritual-summon')
    ->standardDeck('Cryogenic Ritual')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Cryogenic Ritual')
    ->play('Cryogenic Ritual')->target('myField-1')->pay(2)
    ->register();

// --- Conductive Strike: Class Bonus On Hit, pay 2 to put static counters on arcane objects ---
// Notes carried over from the original definition:
//   - Same WARRIOR class-bonus override as emberslash-discard-draw. Conductive Strike is also
//   - ARCANE element, which CanPlayerMeetCardElementRequirements checks separately via
//   - GetChampionLineage() (walks Subcards, not the Counters override) -- same technique as
//   - luxem-sight-draw -- so patch Subcards to include an ARCANE champion in the lineage. Use
//   - Lorraine, Arclight Saber (x9sSpjpP3G): WARRIOR class AND ARCANE element, the actual
//   - champion this starter deck is themed around.
//   - Storm Slime (ARCANE ally) - static counter target
gaScenario('conductive-strike-static-counters')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'WARRIOR']], 'Subcards' => ['x9sSpjpP3G']])
    ->seed('myField', 'Storm Slime')
    ->seed('myHand', 'Conductive Strike')
    ->endTurn()
    ->endTurn(2)->answer(1, '-')
    ->play('Conductive Strike')->pay(2)->target('theirField-0')->answer(1, 'YES')->pay(2)
    ->expectDamage('theirField-0', 1, 'Conductive Strike\'s attack lands for its printed 1 power')
    ->expectProperty('myField-1', 'Counters', '{"static":1}', 'Paying 2 on hit puts a static counter on Storm Slime, the only arcane element object controlled')
    ->expectZoneCount('myGraveyard', 1, 1, 'Conductive Strike completes the attack and moves to the graveyard')
    ->expectDqEmpty()
    ->register();

// --- Crest of the Alliance: whenever a fostered ally dies, may banish this to draw ---
// Combat damage strips FOSTERED from the target immediately, in the same DealDamage call that
// applies the lethal hit (CombatLogic.php ~4964-4968: "Foster tracking: mark that this unit
// received damage and remove fostered state"), so by the time DoAllyDestroyed's IsFostered()
// check runs, a combat-killed ally is never still fostered. Undeniable Truth's own "mandatory
// sacrifice of an ally" additional cost (GameLogic.php's DoActivateCard cost-declaration
// switch, $hasUndeniableTruthCost) removes the ally via DoSacrificeFighter -> DoAllyDestroyed
// directly, with no intervening damage, so the FOSTERED tag is still present when the "does a
// fostered ally you control die" check runs.
// Notes carried over from the original definition:
//   - Crest of the Alliance (added first, so sacrificing the ally below it doesn't shift its own index)
//   - Dungeon Guide, fostered
gaScenario('crest-of-the-alliance-fostered-ally-dies-draw')
    ->standardDeck('Crest of the Alliance')
    ->seed('myField', 'Crest of the Alliance')
    ->seed('myField', 'Dungeon Guide', properties: ['TurnEffects' => ['FOSTERED']])
    ->seed('myHand', 'Undeniable Truth')
    ->play('Undeniable Truth')->target('myField-2')->answer(1, 'YES')->pay(1)->pass(1)
    ->tested('Crest of the Alliance')
    ->register();

// --- Cosmic Astroscope: REST: Glimpse 3 ---
// Cosmic Astroscope's REST ability is a plain, free (memory cost 0) Glimpse 3 -- no reserve
// payment queued. Only the base Glimpse is covered; the [Class Bonus] "opponent glimpses 3
// instead" replacement effect is a passive/reusable-elsewhere property and out of scope. Field
// items with an activated ability are not clickable via a plain myField-N!FSM! action -- their
// ability is offered as a fast-action MZMAYCHOOSE opportunity once the turn player attempts to
// pass, answered with the encoded "{mzID}@Activate-{abilityIndex}@{label}" choice string.
// Notes carried over from the original definition:
//   - Cosmic Astroscope
//   - attempt to pass -> offers the fast-action opportunity
//   - MZREARRANGE response: submit the same "Top=...;Bottom=" param verbatim to keep original
//   - order (same no-op default GoldfishChooseAction uses for this decision type) -- confirmed
//   - via direct probe against this exact deck/seed since the glimpsed card IDs depend on the
//   - deterministic shuffle.
gaScenario('cosmic-astroscope-rest-glimpse')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Cosmic Astroscope')
    ->endTurn()->answer(1, '-')->answer(1, 'myField-1@Activate-0@Glimpse')->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->tested('Cosmic Astroscope')
    ->expectDqEmpty(step: 4)
    ->register();

// --- Condensed Supernova: Brew (Silvershine + 2 Adjuvants + 2 Catalysts); Sacrifice: LV damage to all, Glimpse 4 ---
// Condensed Supernova's element is ASTRA (advanced), so the starting champion's Subcards are
// patched with a real ASTRA champion (Arisanna, Astral Zenith) for element access -- her
// patch also conveniently sets Counters.level = 3 so LV damage is directly observable (0 would
// be invisible). Brewing sacrifices a specific Silvershine plus two more ADJUVANT-subtype and
// two more CATALYST-subtype objects instead of paying reserve; Silvershine itself is also
// CATALYST but the brew cost lists it as a separate named-card requirement from the generic
// "2 Catalysts," so two additional Blightroot (CATALYST) plus two Manaroot (ADJUVANT) are
// seeded alongside it (5 ingredients total). Sacrifice hits every non-ASTRA-element unit on
// both fields automatically (no target choice) for LV damage, then Glimpses 4 (resolved
// keeping original order, same no-op default used in idle-thoughts-glimpse-4).
// Notes carried over from the original definition:
//   - ASTRA lineage/element unlock (Arisanna, Astral Zenith) + LV damage
//   - Silvershine - named brew ingredient
//   - Manaroot (ADJUVANT) #1
//   - Manaroot (ADJUVANT) #2
//   - Blightroot (CATALYST) #1
//   - Blightroot (CATALYST) #2
//   - MZREARRANGE response: keep original order (same no-op default as idle-thoughts-glimpse-4).
gaScenario('condensed-supernova-brew-sacrifice-damage-glimpse')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Arisanna, Astral Zenith'], level: 3)
    ->seed('myField', 'Silvershine')
    ->seed('myField', 'Manaroot')
    ->seed('myField', 'Manaroot')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Condensed Supernova')
    ->play('Condensed Supernova')->answer(1, 'YES')->target('myField-1')->target('myField-2')->target('myField-3')
    ->target('myField-4')->target('myField-5')->passBoth()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom=')
    ->register();

// --- Caretaker Drone: [Class Bonus] On Death: Glimpse 4 ---
// Caretaker Drone is NORM (no lineage patch needed). Its [Class Bonus] On Death is coded as
// IsClassBonusActive($player) with NO class argument (GameLogic.php's IsClassBonusActive
// treats a null $classes as "any champion present," GameLogic.php ~18079-18104), so it fires
// unconditionally as long as a champion exists -- no champion patch needed at all. Only Intercept
// (a shared static ability tested elsewhere) is out of scope. Undeniable Truth's own "mandatory
// sacrifice of an ally" additional cost (GameLogic.php's DoActivateCard cost-declaration switch,
// $hasUndeniableTruthCost) is borrowed to kill Caretaker Drone via a non-combat removal path
// (same technique as crest-of-the-alliance-fostered-ally-dies-draw), which triggers On Death
// reliably.
// Notes carried over from the original definition:
//   - CLERIC champion: the [Class Bonus] is active (a bare class-bonus gate used to be always true)
//   - Caretaker Drone
//   - sacrifice Caretaker Drone as the mandatory cost
//   - MZREARRANGE response (Glimpse 4 On Death): keep original order, answered with exactly the
//   - offered cards (same no-op default as idle-thoughts-glimpse-4). Undeniable Truth's own
gaScenario('caretaker-drone-class-bonus-death-glimpse')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(classes: 'CLERIC')
    ->seed('myField', 'Caretaker Drone')
    ->seed('myHand', 'Undeniable Truth')
    ->castSpell('Undeniable Truth', target: 'myField-1')
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom=')->pay(1)
    ->tested('Caretaker Drone')
    ->register();

// --- Careful Study: put five enlighten counters on your champion ---
// Careful Study is NORM, no lineage patch needed. Its 8-reserve cost needs 1 extra card beyond
// a natural 7-card hand (2 extra fillers seeded for margin). The "Efficiency" keyword is a cost
// discount (out of scope); the ability itself unconditionally puts 5 enlighten counters on the
// champion (GeneratedMacroCode.php ~15634-15640).
// Notes carried over from the original definition:
//   - Extra reserve-payment fuel 1/2
//   - Extra reserve-payment fuel 2/2
gaScenario('careful-study-enlighten-counters')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Careful Study')
    ->castSpell('Careful Study', pay: 8)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"enlighten":5}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', '4NkVdSx9ed')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectZoneCount('myHand', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 8, 1)
    ->register();

// --- Cremation Ritual: additional cost sacrifice an ally, draw 2 ---
// Cremation Ritual is NORM, no lineage patch needed. A Dungeon Guide is seeded onto the field
// as sacrifice fodder for the ability's own MZCHOOSE (GeneratedMacroCode.php ~20677-20684:
// choose an ally, CUSTOM handler DoSacrificeFighter then Draw 2).
// Notes carried over from the original definition:
//   - Dungeon Guide (ALLY) - sacrifice fodder
//   - sacrifice Dungeon Guide
gaScenario('cremation-ritual-sacrifice-draw')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Cremation Ritual')
    ->castSpell('Cremation Ritual', pay: 3, target: 'myField-1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Graveyard-0', 'CardID', 'Pr48kXnasw')
    ->expectProperty('p1Graveyard-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-1', 'Status', '2')
    ->expectDamage('p1Graveyard-1', 0)
    ->expectProperty('p1Graveyard-1', 'Counters', '[]')
    ->expectProperty('p1Graveyard-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 2, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->register();

// --- Crystal of Empowerment: Banish -- champion gets +2 level until end of turn ---
// Crystal of Empowerment is REGALIA -- seeded directly onto myField (hand-add redirect). Its
// "until end of turn" TurnEffect is at risk of the same "attempt to pass ends the turn" bug
// documented for Beastbond Boots, so Scry the Skies is played first (a same-turn warm-up action)
// and the ability is reached via a direct Activate:0 click instead of the pass-trick (same fix
// as beastbond-boots-banish-spellshroud). At the default champion level (0), Scry's own Glimpse
// LV is an invisible no-op, so no further decision is needed after activating.
// Notes carried over from the original definition:
//   - Crystal of Empowerment
//   - Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
//   - pay Scry the Skies' 1 reserve
gaScenario('crystal-of-empowerment-banish-level')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Crystal of Empowerment')
    ->seed('myHand', 'Scry the Skies')
    ->castSpell('Scry the Skies', pay: 1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Crystal of Empowerment')
    ->register();

// --- Crusader of Aesa: enters the field rested ---
// Crusader of Aesa's "enters the field rested" is unconditional card text (GameLogic.php
// ~7909: if($added->CardID == "2Q60hBYO3i") { $added->Status = 1; }), applied whenever the
// card is added to a field via the generic field-add hook -- not gated on Class Bonus (that
// gate only applies to its SEPARATE [Class Bonus] Intercept ability, tested separately by
// esteemed-knight-class-bonus-intercept since both cards share the same Intercept mechanic).
// Played from hand (myHand-7, verified via DevTools/probe-hand.php) paying its 3 reserve.
gaScenario('crusader-of-aesa-enters-rested')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Crusader of Aesa')
    ->castSpell('Crusader of Aesa', pay: 3, window: 'both')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'CardID', '2Q60hBYO3i')
    ->expectProperty('p1Field-1', 'Status', '1')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->register();

// --- Cavalier Rescue: target ally gains a turn effect (+3 LIFE until EOT) (75uhspxqme) ---
// The target MZCHOOSE fires immediately once reserve is paid (negating/targeting something
// still mid-resolution doesn't open a fresh Opportunity window) -- see the note on
// astral-seal-negate-activation-banish. Answer it as the very next action; a PASS submitted
// first would be misapplied against that pending MZCHOOSE and the effect would silently never
// land, even though replay still "verifies" against the (also-silently-wrong) recorded state.
// Notes carried over from the original definition:
//   - Dungeon Guide, target ally
gaScenario('cavalier-rescue-target-turn-effect')
    ->deck($fillerDeck)
    ->seed('myField', 'Dungeon Guide', properties: ['TurnEffects' => []])
    ->seed('myHand', 'Cavalier Rescue')
    ->castSpell('Cavalier Rescue', pay: 3, target: 'myField-1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["75uhspxqme"]', 'Playing Cavalier Rescue fires its on-play trigger (playCardAbilities["75uhspxqme:0"], only reachable via QueuePlayCardTriggeredAbility() from OnCardActivated()) and tags the target ally with the +3 LIFE turn effect')
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', '75uhspxqme')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'px60u5n1do')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->register();

// --- Cunning Broker: [REST], remove two preparation counters from your champion: Draw a card ---
// Cunning Broker is seeded directly onto the field (materialize flow out of scope). The
// starting champion is pre-seeded with 2 preparation counters so the activated ability's cost
// (remove 2 preparation counters from your champion, gated by ActivatedAbilityCost() in
// GameLogic.php requiring >= 2) can actually be paid.
// Notes carried over from the original definition:
//   - Ability cost fuel
//   - Cunning Broker, seeded straight onto the field
gaScenario('cunning-broker-rest-remove-prep-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['preparation' => 2]])
    ->seed('myField', 'Cunning Broker')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Cunning Broker')
    ->register();

// --- Curved Dagger: [Class Bonus] as long as your champion is attacking an ally, +1 POWER ---
// Curved Dagger's element is NORM. The starting champion's CardID is patched directly to
// Zander, Deft Executor (ASSASSIN) for the Class Bonus condition. Rule 1.h blocks the game's
// first player from attacking on turn 1, so player 1 ends turn 1 and player 2's champion
// attacks (equipped with Curved Dagger) targeting a Dungeon Guide ally on their turn 1
// instead. Curved Dagger's printed POWER is 1, and the champion's own printed POWER is blank
// (-1, so it contributes nothing) -- since the +1 POWER is only active transiently while
// CombatAttacker/CombatTarget are set (cleared again once combat fully resolves within the
// same action, so a computed_power_equals snapshot after the fact can't observe it, verified
// live), it is instead proven indirectly via the ally taking 2 damage, not just 1. The
// defender's "Retaliate?" MZMAYCHOOSE (Dungeon Guide itself, being the target with positive
// POWER, is eligible) must be explicitly declined so CombatApplyAttackerDamage actually lands.
// Notes carried over from the original definition:
//   - Zander, Deft Executor (ASSASSIN)
//   - Curved Dagger, usable
//   - Dungeon Guide (ALLY) - attack target
//   - ends turn 1 (first-player attack lock)
//   - declare attack with champion
//   - choose Curved Dagger as the weapon
//   - target Dungeon Guide
//   - decline Retaliate so CombatApplyAttackerDamage actually lands
gaScenario('curved-dagger-class-bonus-attack-ally-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Deft Executor', player: 2)
    ->seed('myField', 'Curved Dagger', player: 2, properties: ['Counters' => ['durability' => 1]])
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-1')->answer(1, '-')
    ->tested('Curved Dagger')
    ->expectDqEmpty()
    ->expectDamage('p1Field-1', 2, 'Target ally takes 2 damage (1 printed weapon POWER + 1 Class Bonus while attacking an ally), not just 1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P2"]')
    ->expectProperty('p2Field-0', 'Status', '1')
    ->expectProperty('p2Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":2}')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Banish-0', 'CardID', 'Q2ugqVm04E')
    ->expectProperty('p2Banish-0', 'Counters', '[]')
    ->expectProperty('p2Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p2GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectZoneCount('theirHand', 8, 1)
    ->expectZoneCount('theirField', 1, 1)
    ->register();

// --- Corhazi Infiltrator: Whenever you reveal it from memory, may put a copy from memory to field ---
// Corhazi Infiltrator's element is LUXEM; the starting champion's CardID is patched directly
// to Zander, Blinding Steel (LUXEM, ASSASSIN) for both its own element requirement and the
// Class Bonus condition (Element Bonus is unconditionally true -- IsElementBonusActive() is a
// stubbed TODO returning true always, verified via GameLogic.php). Two copies of Corhazi
// Infiltrator are seeded into memory; Uncover the Plot (targeting yourself) reveals all of
// memory, firing the reveal-triggered ability once per copy revealed (QueueRevealTriggeredAbility
// in GameLogic.php). Answering the resulting MZMayChoose moves the OTHER copy from memory onto
// the field.
// Notes carried over from the original definition:
//   - Zander, Blinding Steel (LUXEM, ASSASSIN)
//   - Corhazi Infiltrator copy #1
//   - Corhazi Infiltrator copy #2
//   - target yourself
//   - move a copy from memory to field
//   - decline the second copy's own reveal-triggered offer
gaScenario('corhazi-infiltrator-reveal-memory-to-field')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Blinding Steel')
    ->seed('myMemory', 'Corhazi Infiltrator')
    ->seed('myMemory', 'Corhazi Infiltrator')
    ->seed('myHand', 'Uncover the Plot')
    ->play('Uncover the Plot')->pay(2)->answer(1, 'YES')->target('myMemory-0')->answer(1, '-')
    ->tested('Corhazi Infiltrator')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'CardID', 'VAFTR5taNG', 'Reveal-triggered ability moves a copy of itself from memory onto the field')
    ->expectProperty('p1Field-1', 'Status', '2')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', '4zkTRt8qXn')
    ->expectProperty('p1Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-2', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1)
    ->register();

// --- Covert Manipulator: [Class Bonus] On Enter, reveal, optionally give opponent Crowd's Favor draw ---
// Covert Manipulator's element is NORM; the starting champion's CardID is patched directly to
// Zander, Deft Executor (ASSASSIN) for the Class Bonus condition.
// Notes carried over from the original definition:
//   - Zander, Deft Executor (ASSASSIN)
//   - choose an opponent for Crowd's Favor
gaScenario('covert-manipulator-enter-reveal-crowds-favor')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Deft Executor')
    ->seed('myHand', 'Covert Manipulator')
    ->play('Covert Manipulator')->pay(3)->answer(1, 'YES')
    ->register();

// --- Corhazi Courier: Stealth; [Class Bonus] On Hit, draw+discard, deal 1 damage if fire discarded ---
// Corhazi Courier's [Class Bonus] On Hit ability calls IsClassBonusActive($player) with no
// class filter, which is unconditionally true as long as any champion is on the field
// (GameLogic.php: $requiredClasses stays null, so the check short-circuits true) -- no class
// patch is needed. Rule 1.h blocks the game's first player from attacking on turn 1, so player
// 1 ends turn 1 and player 2 attacks with Corhazi Courier on their own turn 1 instead. A second
// copy of Corhazi Courier (FIRE element) is seeded into hand as discard fodder so the
// "if a fire element card was discarded" branch is reachable, dealing 1 damage to the chosen
// unit (the opponent's champion).
// Notes carried over from the original definition:
//   - ASSASSIN champion: the [Class Bonus] is active (a bare class-bonus gate used to be always true)
//   - Corhazi Courier
//   - awake, can attack
//   - ends turn 1 (first-player attack lock)
//   - declare attack with Corhazi Courier
//   - target opponent's champion
//   - discard the second (FIRE) copy
//   - choose the opponent's champion to deal 1 damage to
gaScenario('corhazi-courier-onhit-draw-discard-damage')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(classes: 'ASSASSIN', player: 2)
    ->seed('myField', 'Corhazi Courier', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->seed('myHand', 'Corhazi Courier', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(2, 'myHand-7')->answer(2, 'theirField-0')
    ->tested('Corhazi Courier')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 2, 'Champion takes 1 combat damage (Corhazi Courier\'s printed POWER) plus 1 more from the fire-discard On Hit clause = 2')
    ->expectProperty('p1Field-0', 'Counters', '{"_champDamageThisTurn":2}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN"]')
    ->expectProperty('p2Field-1', 'Status', '1')
    ->expectProperty('p2Field-1', 'TurnEffects', '[]')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-8', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Graveyard-0', 'CardID', 'YqQsXwEvv5', 'On Hit discards the fire card')
    ->expectZoneCount('theirHand', 9, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Celestial Navigation: Glimpse 5 ---
// Celestial Navigation (QAU8WVUZM0, ASTRA, reserve 2) calls Glimpse($player, 5)
// (cardActivatedAbilities["QAU8WVUZM0:0"], GeneratedMacroCode.php). ASTRA is an advanced
// element (GameLogic.php ~593/19784) -- without unlocking it, playing the card silently
// no-ops (success:true, nothing queued), so the champion is CardID-patched to Arisanna,
// Astral Zenith (q3huqj5bba, CLERIC/ASTRA). Glimpse queues an MZREARRANGE decision with the
// exact param format 'Top=<cardIDs>;Bottom=<cardIDs>'; the response submitted here is that
// same string verbatim (a no-op reorder), which keeps the 5 glimpsed cards on top in their
// original order and proves Glimpse 5 actually ran (vs. a no-op or wrong count) via the exact
// card-ID sequence. Card IDs are deck-shuffle-dependent for this deck/seed and were confirmed
// via a direct probe before being hardcoded.
// Notes carried over from the original definition:
//   - Arisanna, Astral Zenith - ASTRA unlock
gaScenario('celestial-navigation-glimpse-5')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Arisanna, Astral Zenith')
    ->seed('myHand', 'Celestial Navigation')
    ->castSpell('Celestial Navigation', pay: 2)
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do;Bottom=')
    ->expectZoneCount('myDeck', 5, 1, 'Glimpse 5 only looks and reorders the top of the deck -- deck count is unchanged')
    ->expectProperty('p1Deck-0', 'CardID', 'em6eEh9q8y', 'Reordered response is honored -- deck top slot matches the submitted Top= order')
    ->expectProperty('p1Deck-3', 'CardID', 'n8wyfG9hbY', 'Reordered response is honored -- fourth deck slot matches the submitted Top= order')
    ->expectProperty('p1Graveyard-0', 'CardID', 'QAU8WVUZM0', 'Celestial Navigation itself resolves to the graveyard as a normal Action card')
    ->register();

// --- Convalescent Tonic: Sacrifice self -- bottom up to 2 hand cards, draw that many, Recover 3 ---
// Convalescent Tonic is seeded directly onto the field (its own Sacrifice ability doesn't
// care how it got there, and Brewing isn't the clause under test).
// activateAbilityAbilities["l8ao8bls6g:0"] (GeneratedMacroCode.php) offers an MZMULTICHOOSE
// of up to 2 hand cards; customDQHandlers["l8ao8bls6g:0:ActivateAbility-1"] moves the chosen
// cards to the bottom of the deck, draws that many back, then RecoverChampion($player, 3).
// The champion is pre-damaged by 5 so Recover 3 leaves 2 damage remaining, distinguishing
// "recovered" from "already at full life"; 2 known hand cards are chosen via the "&"-joined
// MZMULTICHOOSE response format (same shape as essence-crucible-razorgale-calling).
// Notes carried over from the original definition:
//   - Convalescent Tonic - the activator
//   - pre-damage so Recover 3 is observable
gaScenario('convalescent-tonic-sacrifice-bottom-draw-recover')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->seed('myField', 'Convalescent Tonic')
    ->champion(damage: 5)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myHand-0&myHand-1')
    ->tested('Convalescent Tonic')
    ->register();

// --- Cleansing Reunion: target player banishes three cards from their graveyard (six if imbued) ---
// Bug fix under test: Cleansing Reunion is an ACTION card whose ability closure was generated
// into $enterAbilities (the ENTER-trigger table, for permanents entering the field) instead of
// $cardActivatedAbilities (the table OnCardActivated() dispatches ACTION-card resolution
// through). Since it is never a permanent entering the field, its closure was unreachable --
// playing it normally was silently a no-op. Cleansing Reunion is WIND element and isn't native
// to the "Spirit of Fire" starting champion -- like Deployment Beacon above, the champion's
// Subcards are patched with a real WIND champion (Spirit of Wind, pNiyaGlIe7) to unlock element
// access via CanPlayerUseCardElement(), or DoActivateCard silently no-ops before ever reaching
// reserve payment. The ability's own "target player" is hardcoded to the caster's opponent (no
// real MZCHOOSE target choice), so 4 graveyard cards are seeded for player 2 so the 3-card
// banish is observable (only 3 of the 4 get banished, proving the banish count and that the
// ability now actually runs). This card also carries the Imbue 2 keyword, which inserts a
// YESNO "reveal reserved cards?" decision before the ordinary per-card reserve-payment
// MZCHOOSE loop; answering NO declines Imbue and pays the plain 2-reserve cost.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Dungeon Guide
//   - Dungeon Guide
//   - Dungeon Guide
//   - Dungeon Guide
//   - Cleansing Reunion
//   - Opportunity window: lets the effect stack resolve and reach OnCardActivated's dispatch,
//   - where the now-correctly-classified $cardActivatedAbilities["xpnjvt9y59:0"] closure fires.
//   - First MZCHOOSE pick, written in player 2's own frame (it was 'theirGraveyard-N', which player 2 resolved against the CASTER's graveyard, banishing Cleansing Reunion itself).
//   - The chained re-queue (CleansingReunionBanish) offers "myGraveyard-N" relative to player 2 for the remaining picks.
gaScenario('cleansing-reunion-banish-graveyard')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Cleansing Reunion')
    ->play('Cleansing Reunion')->answer(1, 'NO')->pay(2)->pass(1)->answer(2, 'myGraveyard-0')
    ->answer(2, 'myGraveyard-0')->answer(2, 'myGraveyard-0')
    ->register();

// --- Corhazi Lightblade: [Class Bonus] On Attack: reveal a card from memory at random; if LUXEM, gain Critical 1 ---
// Corhazi Lightblade's On Attack ability (onAttackAbilities["2Ch1Gp3jEL:0"],
// GeneratedMacroCode.php) reveals a random card from memory and, if it's LUXEM, tags the
// attacker's mzID with the CRITICAL_1 TurnEffect. It's controlled by player 2 and declares
// its attack on player 2's own first turn (global turn 2) instead of player 1's -- Rule 1.h
// only blocks the game's FIRST player from attacking on turn 1 (CombatLogic.php
// "$currentTurn == 1 && $turnPlayer == $firstPlayer"), so player 2 can attack immediately
// after player 1 ends turn 1, with no recollection phase in between to clear the seeded
// memory (RecollectionPhase()/BeforeRecollectionPhase() both early-return while
// GetTurnNumber() === 1, and player 2's memory-return-to-hand step only runs at the start of
// player 2's OWN turn 2, which is later than this fixture goes). Its memory is seeded with
// exactly one card -- Bathe in Light (d9zax2g20h, LUXEM) -- so rand(0, count-1) always reveals
// index 0 deterministically, with no dependency on the seeded RNG stream. The generated
// "[Class Bonus]" gate here is IsClassBonusActive($player) called with NO required-classes
// list (unlike most other Class Bonus macros, which pass e.g. ["TAMER"]), so it only checks
// "does this player control any CHAMPION at all" and is always true -- verified live
// (classBonus=YES with the deck's default FIRE/NORM starting champion, no ASSASSIN needed).
// That looks like a real code-gen gap against the printed "[Class Bonus]" text, but it's a
// pre-existing condition-classification issue orthogonal to the reveal/critical logic this
// fixture targets, so it's left uncovered/unfixed here.
// Notes carried over from the original definition:
//   - ASSASSIN champion: the [Class Bonus] is active (a bare class-bonus gate used to be always true)
//   - Corhazi Lightblade
//   - awake, can attack
//   - Bathe in Light (LUXEM) - sole memory card, deterministic reveal
//   - end player 1's turn 1 (first-player attack lock)
//   - player 2 declines their own materialize offer
//   - Corhazi Lightblade declares an attack
//   - target player 1's champion
gaScenario('corhazi-lightblade-reveal-luxem-critical')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(classes: 'ASSASSIN', player: 2)
    ->seed('myField', 'Corhazi Lightblade', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->seed('myMemory', 'Bathe in Light', player: 2)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->tested('Corhazi Lightblade')
    ->register();

// --- Charm of Anticipation: Banish CARDNAME: Draw a card. Activate only if you have Crowd's
// Favor. Hand-authored ability (see GrandArchiveSim/Custom/GameLogic.php,
// activateAbilityAbilities["vkL2RFh0yM:0"] -- no CardEditor ability database row was reachable in
// this sandbox) ---
// Charm of Anticipation is seeded directly onto player 1's field (myField-1) and player 1 is
// granted the Crowd's Favor status (global effect gpmJdGYqoC) via the 'globalEffect' setup
// primitive, satisfying the ability's "Activate this ability only if you have the Crowd's
// Favor status" prereq. A single CustomInput "Activate:0" click drives
// ActivateAbility -> DoActivatedAbility -> activateAbilityAbilities["vkL2RFh0yM:0"], which
// banishes Charm of Anticipation and draws a card -- mirroring the Templar of the Eternal
// fixture's activation shape exactly.
// Notes carried over from the original definition:
//   - Crowd's Favor
//   - Charm of Anticipation
gaScenario('charm-of-anticipation-banish-draw')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->globalEffect('gpmJdGYqoC')
    ->seed('myField', 'Charm of Anticipation')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Charm of Anticipation')
    ->register();

// --- Chill to the Bone: attacking ally gets -2/-2, or -4/-4 with 2+ phantasias controlled ---
// (branch A: caster controls 0 phantasias -> -2 POWER / -2 LIFE)
// ChillToTheBoneResolve (GameLogic.php ~18765) reads the DecisionQueueController
// "CombatAttacker" variable and does GetZoneObject($attackerMZ) directly on the RAW
// "myField-N"/"theirField-N" string that was captured (from the ATTACKER's own perspective)
// by StoreCombatAttackerState when the attack was declared -- it never re-expresses that
// string relative to whichever player's perspective is ambient when the ability closure
// itself runs (unlike e.g. AtmosShieldRedirect/InterceptTargetChosen, which explicitly call
// ConvertMzToPlayerPerspective for exactly this "captured from one player's perspective, used
// from another's" situation). Confirmed via direct instrumentation: when player 2 casts Chill
// to the Bone in response to player 1's attack, $attackerMZ = 'myField-1' is read correctly,
// but GetZoneObject('myField-1') then resolves against player 2's OWN field (empty at that
// index) because "myField" is only meaningful relative to the ambient $playerID at the
// moment it's resolved -- so GetZoneObject returns null and the ability is a silent no-op.
// This is a genuine engine bug (GrandArchiveSim/Custom/GameLogic.php ~18765-18770) affecting
// Chill to the Bone's primary/intended use case (the DEFENDING player debuffing the
// opponent's attacking ally) -- flagged in the final report, NOT fixed here. To still exercise
// the card without touching engine code, this fixture instead has the ATTACKER (player 1)
// hold and cast Chill to the Bone on their OWN attacking ally: GrantOpportunityWindow grants
// the turn player (the attacker) first priority in the post-declaration fast-opportunity
// window, so player 1 can respond to their own attack, and $playerID stays 1 throughout,
// so "myField-1" resolves correctly. This still fully proves the card's actual debuff math
// (0-1 vs 2+ phantasias -> -2/-2 vs -4/-4, consumed unconditionally by the POWER/LIFE switch
// statements in GameLogic.php ~12809-12814/~14257-14261) even though it can't exercise the
// "opponent responds" framing. Player 1's Dungeon Guide (printed POWER 1 / LIFE 3, seeded
// awake) declares a real attack; player 1 controls 0 phantasias here, so the branch under
// test is -2 POWER / -2 LIFE -> computed 1-2=-1 / 3-2=1.
//
// Because player 1 holds a FAST card (Chill to the Bone) for the whole game, EVERY phase
// transition (ending turn 1, ending turn 2, the turn-3 materialize offer) independently
// grants player 1 a "Take_a_fast_action?" fast-opportunity window with CombatAttacker not
// yet live -- each occurrence must be explicitly declined ('-') or it silently swallows the
// next scripted action (confirmed empirically: the SAME "myHand-7" choice reappears
// identically after every such transition until CombatAttacker is genuinely set). Only the
// window that appears immediately after choosing the real attack target (with
// CombatAttacker == 'myField-1' confirmed live via direct instrumentation) is the one this
// fixture actually takes.
// Notes carried over from the original definition:
//   - Dungeon Guide ALLY, seeded awake -- the attacker and the debuff target
//   - Spirit of Water - unlocks WATER for Chill to the Bone
//   - Chill to the Bone
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
//   - player 1 takes the fast-opportunity action: Chill to the Bone (on their own attacker)
//   - pay reserve 1/2
//   - pay reserve 2/2 -- ChillToTheBoneResolve fires immediately, no further target step
gaScenario('chill-to-the-bone-low-phantasia-debuff')
    ->deck($diaoChanDeck)
    ->seed('myField', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myHand', 'Chill to the Bone')
    ->endTurn()->answer(1, '-')->answer(1, '-')
    ->endTurn(2)->answer(1, '-')->answer(1, '-')->answer(1, '-')->answer(1, '-')
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')->target('myHand-7')->pay(2)
    ->tested('Chill to the Bone')
    ->register();

// --- Chill to the Bone: branch B -- caster controls 2+ phantasias -> -4 POWER / -4 LIFE instead ---
// Same shape as chill-to-the-bone-low-phantasia-debuff (see its comment for the full
// perspective-bug writeup and why player 1 -- the attacker -- holds and casts Chill to the
// Bone on their own attacker here too), except player 1 also controls 2 Scorching
// Imperilment (PHANTASIA) cards, so the branch under test is -4 POWER / -4 LIFE instead:
// Dungeon Guide's printed POWER 1 / LIFE 3 becomes 1-4=-3 / 3-4=-1.
// Notes carried over from the original definition:
//   - Dungeon Guide ALLY, seeded awake -- the attacker and the debuff target
//   - Spirit of Water - unlocks WATER for Chill to the Bone
//   - Core Fractal (8hqHAU0Xj6, TOKEN,PHANTASIA) rather than Scorching Imperilment
//   - (aj7pz79wsp) specifically because Scorching Imperilment has its own "End phase: may
//   - discard to draw" activated ability (GeneratedMacroCode.php "aj7pz79wsp:0") that fires
//   - during the turn-1-end phase transition this fixture needs to walk through, adding an
//   - extra MZMAYCHOOSE that desyncs the rest of the scripted sequence. Core Fractal has zero
//   - dispatch entries of its own.
//   - Core Fractal (PHANTASIA) #1 -- 2+ phantasia branch (caster is player 1 in this fixture)
//   - Core Fractal (PHANTASIA) #2 -- 2+ phantasia branch
//   - Chill to the Bone
//   - ends turn 1 (first-player attack lock)
//   - decline the stray fast-opportunity window from ending turn 1 (1/2)
//   - decline the stray fast-opportunity window from ending turn 1 (2/2)
//   - ends turn 2
//   - decline the stray fast-opportunity window from ending turn 2 (1/2)
//   - decline the stray fast-opportunity window from ending turn 2 (2/2) -- lands on the turn-3 MAT offer
//   - decline turn-3 MAT offer
//   - decline the stray fast-opportunity window opened by declining the MAT offer
//   - Dungeon Guide declares a real attack (seeded first, so myField-1)
//   - target the opposing champion -- CombatAttacker='myField-1' is now genuinely live
//   - player 1 takes the fast-opportunity action: Chill to the Bone
//   - pay reserve 1/2
//   - pay reserve 2/2 -- ChillToTheBoneResolve fires immediately
gaScenario('chill-to-the-bone-two-phantasia-debuff')
    ->deck($diaoChanDeck)
    ->seed('myField', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myField', 'Core Fractal')
    ->seed('myField', 'Core Fractal')
    ->seed('myHand', 'Chill to the Bone')
    ->endTurn()->answer(1, '-')->answer(1, '-')
    ->endTurn(2)->answer(1, '-')->answer(1, '-')->answer(1, '-')->answer(1, '-')
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')->target('myHand-7')->pay(2)
    ->tested('Chill to the Bone')
    ->register();

// --- Crystalline Mirror: whenever a phantasia enters under your control, glimpse 1;
// [Class Bonus] Banish CARDNAME: destroy a cheap item (only if you control 3+ phantasias). ---
// Crystalline Mirror's "whenever a phantasia enters the field under your control, glimpse 1"
// trigger lives directly in FieldAfterAdd (Custom/GameLogic.php ~8613-8623) -- it fires
// synchronously for ANY field-add (setup or real play) as soon as a PHANTASIA-type card lands
// on the controller's field while Crystalline Mirror is already there, no Class Bonus needed.
// Its "[Class Bonus] Banish CARDNAME: destroy target item..." half is a REST-style
// activateAbilityAbilities["9agwj4f15j:0"] gated by activateAbilityPrereqs["9agwj4f15j:0"]
// (IsClassBonusActive(CLERIC) && count(phantasias) >= 3); its own "Banish self" cost
// (ActivatedAbilityCost's "9agwj4f15j" case, GameLogic.php ~6290) runs BEFORE the ability body,
// so Crystalline Mirror is already off the field by the time its own myField ITEM search runs
// (can't self-target). myField-0 is patched directly to Diao Chan, Enchantress (00xbh8oc00,
// CLERIC) with Subcards=[Spirit of Water] so IsClassBonusActive(CLERIC) is satisfied by a
// literal on-field champion match while WATER element access is preserved via lineage
// (patchMzId never triggers Enter(), so Diao Chan's own "On Enter: put 2 glimmer counters"
// never fires and can't leave a stray decision behind). Two Hydrating Fractals (LAfJuHgUbm,
// WATER PHANTASIA, no On Enter/passive text) are seeded onto the field BEFORE Crystalline
// Mirror so their own entry doesn't trigger the glimpse (CM isn't present yet); a third is
// played from hand AFTER CM is on the field, which is the one real, observable "phantasia
// enters while CM is out" event this fixture proves, bringing total controlled phantasias to
// 3 -- satisfying the banish ability's own "control 3+ phantasias" activation gate. Beastbond
// Ears (JPcFmCpdiF, REGALIA/ITEM, memory cost 0, purely passive "+1 level" text, no activated
// ability of its own) is seeded onto the opponent's field as the qualifying cheap-item destroy
// target (search covers both myField and theirField items) -- deliberately chosen over an
// item with its OWN activated ability (e.g. Grand Crusader's Ring's "Banish: Draw a card"),
// since seeding one of those onto the opponent's field opens a real "Take a fast action?"
// AbilityOpportunity choice for player 2 (confirmed empirically) that would otherwise need an
// extra explicit decline action.
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress (CLERIC) + Spirit of Water lineage (WATER access)
//   - Hydrating Fractal #1 (pre-existing phantasia, before CM)
//   - Hydrating Fractal #2 (pre-existing phantasia, before CM)
//   - Crystalline Mirror (seeded awake, after the 2 phantasias)
//   - Hydrating Fractal #3, to be played from hand -- the real glimpse-triggering entry
//   - Beastbond Ears (Mem=0 ITEM, no activated ability) -- banish-ability destroy target
//   - reserve 1/1 for Hydrating Fractal #3
//   - Fractal #3 entering the field while Crystalline Mirror is out triggers the "whenever a
//   - phantasia enters" Glimpse 1 synchronously -- respond to its MZREARRANGE (submitting the
//   - same "Top=...;Bottom=" param verbatim, same technique as idle-thoughts-glimpse-4/
//   - wisdoms-reprise-level3-draw-memory) before anything else can proceed.
//   - Glimpse 1 response -- send the glimpsed card to the BOTTOM (observably distinct from a no-op) to prove the trigger actually engaged the deck
//   - Banish Crystalline Mirror to activate (now controls 3 phantasias: myField-1, myField-2, myField-4)
//   - target Beastbond Ears to destroy
gaScenario('crystalline-mirror-glimpse-and-banish-destroy')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->champion(cardName: 'Diao Chan, Enchantress', lineage: ['Spirit of Water'])
    ->seed('myField', 'Hydrating Fractal')
    ->seed('myField', 'Hydrating Fractal')
    ->seed('myField', 'Crystalline Mirror')
    ->seed('myHand', 'Hydrating Fractal')
    ->seed('myField', 'Beastbond Ears', player: 2)
    ->play('Hydrating Fractal')->pay(1)->answer(1, 'Top=;Bottom=em6eEh9q8y')
    ->action(1, 10001, 'myField-3!CustomInput!Activate:0')->target('theirField-1')
    ->tested('Crystalline Mirror')
    ->expectDqEmpty()
    ->expectProperty('p1Field-3', 'CardID', 'LAfJuHgUbm', 'The third Hydrating Fractal (played from hand) successfully entered the field while Crystalline Mirror was out, bringing controlled phantasias to 3 -- satisfying the banish ability\'s own \'control 3+ phantasias\' activation gate')
    ->expectProperty('myDeck-0', 'CardID', 'px60u5n1do', 'Crystalline Mirror\'s \'whenever a phantasia enters, glimpse 1\' fired: the deck\'s original top card (em6eEh9q8y) is no longer on top -- it was pulled into the Glimpse and sent to the bottom per this fixture\'s MZREARRANGE response, proving the trigger actually engaged the deck rather than being a no-op')
    ->expectProperty('myDeck-12', 'CardID', 'em6eEh9q8y', 'The glimpsed card was moved to the bottom of the deck (last of 13 slots), confirming Glimpse(1) really moved a real card through myTempZone and back via the MZREARRANGE response rather than the deck staying untouched')
    ->expectProperty('p1Hand-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Banish-0', 'CardID', '9agwj4f15j', 'Crystalline Mirror was banished (its own activation cost) to activate the [Class Bonus] ability -- it is no longer on the field')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p2Banish-0', 'CardID', 'JPcFmCpdiF', 'Beastbond Ears (memory cost 0 item, a legal target since memory cost 0 or reserve cost <=3) was destroyed by Crystalline Mirror\'s [Class Bonus] Banish ability -- moved off the opponent\'s field into their banish zone')
    ->expectProperty('p2Banish-0', 'Status', '2')
    ->expectDamage('p2Banish-0', 0)
    ->expectProperty('p2Banish-0', 'Counters', '[]')
    ->expectProperty('p2Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myMemory', 1, 1)
    ->expectZoneCount('theirField', 1, 1)
    ->register();

// Companion Fatestone: On Enter put a buff counter on itself or a Fatebound ally you control.
// Notes carried over from the original definition:
//   - MZCHOOSE: only legal choice is Companion Fatestone itself (no Fatebound allies present).
gaScenario('companion-fatestone-enter-buff-self')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
4 Companion Fatestone
10 Dungeon Guide
DECK
    )
    ->playSlot(0)->pay(3)->target('myField-1')
    ->tested('Companion Fatestone')
    ->expectProperty('myField-1', 'Counters', '{"buff":1}', 'Companion Fatestone puts a buff counter on itself when no Fatebound ally is present')
    ->register();

// Craggy Fatestone: [Guo Jia Bonus] [REST]: Transform, only if 2+ buff counters.
// Notes carried over from the original definition:
//   - Seed the required 2 buff counters directly (normally accumulated from the card's OWN
//   - separate "whenever an opponent materializes a memory-cost-0 card" trigger, which this
//   - fixture does not exercise -- see the notes in this fixture's meta.json about that
//   - trigger's dispatch).
//   - Activate the [REST] transform ability. Prereq: IsGuoJiaBonus + 2+ buff counters.
gaScenario('craggy-fatestone-rest-transform-two-buff')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Craggy Fatestone', properties: ['Counters' => ['buff' => 2]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Craggy Fatestone')
    ->expectProperty('myField-1', 'CardID', '40oe1wf79p', 'Craggy Fatestone transforms into Obstinate Cragback')
    ->expectProperty('myField-1', 'Status', '1', 'The [REST] cost was paid: the transformed card is rested')
    ->register();

// Craggy Fatestone (h8n1520m2d): regression fixture for the FIXED first clause -- "Whenever an
// opponent materializes a card with memory cost 0, put a buff counter on Craggy Fatestone." This
// clause carries NO Guo Jia Bonus restriction on the card's own printed text (only the separate
// second [REST] transform clause above is tagged [Guo Jia Bonus]); it was previously wrongly
// gated behind IsGuoJiaBonus($opponent) AND only wired into DoMaterialize() (MaterializeLogic.php),
// which covers exclusively the rare from-material-zone/champion-lineage materialize path -- dead
// for the overwhelmingly common case of an opponent materializing something from HAND. The fix
// (a) drops the IsGuoJiaBonus gate on this clause and (b) also calls the same
// CraggyFatestoneMaterializeTrigger() helper from MoveEffectStackCardToField() (GameLogic.php),
// the real from-hand-via-effect-stack chokepoint. This fixture proves the from-hand case, with NO
// Guo Jia Bonus active for either player.
//
// Fauna Friend (japulzj7gv, REGALIA,ITEM, reserve=-1/memory=0) is the memory-cost-0 vehicle card.
// Regalia can never legitimately reach hand via AddHand() (HandAddReplacement redirects any
// freshly-"added" Regalia CardID straight into the Material zone instead -- confirmed live), so it
// is seeded to a known hand slot via patchMzId, same convention used throughout this file for
// hand-seeded test cards; only the vehicle's presence in hand is synthetic, not Craggy Fatestone's
// buff counter (which is produced by the real ability code, not setProperties).
//
// Verified live: P1 declines their material-phase level-up prompt (ends their empty turn 1,
// landing on P2's own turn 1 MAIN phase with no other pending decisions in this filler deck), then
// P2 plays Fauna Friend from myHand-0 via ordinary FSM. Memory cost 0 needs no MZCHOOSE/reserve
// decisions -- it resolves in one action, moving straight to P2's field. Craggy Fatestone's buff
// counter goes 0 -> 1 as a direct, unconditional result (no Guo Jia Bonus on P1 or P2's champion).
// Notes carried over from the original definition:
//   - Craggy Fatestone on P1's field -- the card under test. Its buff counter is left at the
//   - natural starting value (0); nothing about its own state is seeded.
//   - Fauna Friend seeded to P2's myHand-0 (see file-level comment above for why patchMzId,
//   - not a zone-add, is required for a Regalia card to sit in hand).
//   - P1 declines the start-of-turn material-phase level-up prompt. This filler deck has no
//   - other legal action for P1, so the rest of P1's turn auto-resolves and priority lands on
//   - P2's own turn 1 MAIN phase (verified live via RunnerPendingDecisionSummary: turn player
//   - becomes 2, phase MAIN, no pending decisions).
//   - P2 materializes Fauna Friend from hand (myHand-0, still index 0 -- P2's own turn-1 draw
//   - appends to the END of hand). Memory cost 0 resolves in this single FSM action with no
//   - further decisions.
gaScenario('craggy-fatestone-buff-counter-from-hand-materialize')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->seed('myField', 'Craggy Fatestone')
    ->patch('myHand-0', ['CardID' => 'japulzj7gv'], player: 2)
    ->endTurn()
    ->playSlot(0, 2)
    ->tested('Craggy Fatestone')
    ->expectProperty('myField-1', 'CardID', 'h8n1520m2d', 'Craggy Fatestone is still in P1\'s field slot after P2\'s materialize')
    ->expectProperty('myField-1', 'Counters', '{"buff":1}', 'Craggy Fatestone\'s buff counter goes 0 -> 1 from the opponent\'s real from-hand materialize of a memory-cost-0 card, with no Guo Jia Bonus active for either player')
    ->register();

// --- Crystalline Reality (iPpwkMxDt5): [Merlin Bonus] Prepare 1. Choose one. If prepared, choose two
// instead -- Summon a Memorite Blade / true sight / Draw a card into memory. ---
// Notes carried over from the original definition:
//   - Champion patched directly to Merlin, Memorite Vassal (Merlin Bonus) with a preparation counter
//   - already on them, so the Prepare 1 cost is payable.
//   - "Pay Prepare 1?" -- YES.
//   - MZMODAL choose 2 of {A: Summon Memorite Blade, B: true sight, C: Draw into memory} -> pick A and C (indices 0,2).
gaScenario('crystalline-reality-merlin-bonus-prepare-choose-two')
    ->deckTemplate('spirit-dungeon-guide', 'Crystalline Reality')
    ->patch('myField-0', ['CardID' => '6R8XmWoKLn', 'Counters' => ['preparation' => 1]])
    ->playSlot(5)->pay(2)->answer(1, 'YES')->answer(1, '0,2')->passBoth()
    ->tested('Crystalline Reality')
    ->register();

// --- Converge Reflections (TBVLLRPiwP): As an additional cost, sacrifice a non-token item or
// weapon. Destroy target item or weapon with memory cost 0 or reserve cost 4 or less. If that
// object was a Distortion, draw a card into your memory. ---
// NOTE (engine gap, flagged not fixed): activateCardPrereqs["TBVLLRPiwP:0"] checks for a
// sacrificeable non-token item/weapon as a LEGALITY precondition, but no code anywhere in
// cardActivatedAbilities["TBVLLRPiwP:0"] or its customDQHandler ever actually performs the
// sacrifice -- the "as an additional cost, sacrifice..." clause is checked but never paid. This
// fixture documents the card's actual (destroy + conditional draw) behavior; the sac-fodder item
// seeded in setup is deliberately left on the field at the end, matching real engine behavior.
// Notes carried over from the original definition:
//   - Sac-fodder (non-token WEAPON) on our own field -- the additional cost's mandatory
//   - sacrifice target (see fix note below: now actually consumed as part of activation).
//   - Destroy target: a DISTORTION item (memory cost 0) on the opponent's field.
//   - Additional cost: sacrifice the seeded Shardforged Blade (myField-1). With only one
//   - legal non-token item/weapon on the field this is a trivial choice, but the engine
//   - still issues a real MZCHOOSE decision (matching Broken Promises' shape) that must be
//   - answered explicitly.
//   - MZCHOOSE the destroy target: the opponent's Enfeebled Dagger.
gaScenario('converge-reflections-destroy-distortion-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Shardforged Blade
1 Enfeebled Dagger
# Main
4 Converge Reflections
10 Dungeon Guide
DECK
    )
    ->seed('myField', 'Shardforged Blade')
    ->seed('theirField', 'Enfeebled Dagger')
    ->playSlot(0)->target('myField-1')->pay(2)->pass(2)->target('theirField-1')
    ->tested('Converge Reflections')
    ->register();

// --- Corhazi Outlook: "[Class Bonus] Remove a preparation counter from your champion: ..." has no [REST], so Corhazi Outlook stays awake ---
// Corhazi Outlook (rw8qq1uwq8, ALLY): "[Class Bonus] Remove a preparation counter from your
// champion: Opponents can't activate cards this turn. Glimpse 1. Activate this ability only during
// your turn." The printed cost is only the counter. Same implicit-REST bug as Tristan,
// Shadowdancer's Redirect (FIXED: DoActivatedAbility() in Custom/GameLogic.php set Status = 1 for
// every static ally ability whose card id was not in $skipAutoRest; rw8qq1uwq8 is now in that
// list). The champion is patched to Tristan, Hired Blade (ASSASSIN, one preparation counter) so the
// Class Bonus and the cost are satisfied; Corhazi Outlook is seeded awake on the field and the
// ability is activated for real from the main phase. The counter is spent, the Glimpse 1 prompt is
// answered (the looked-at card stays on top) and Corhazi Outlook stays awake (Status 2).
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (ASSASSIN) with one preparation counter: Class Bonus active, cost payable
//   - Corhazi Outlook -> p1 field-1
//   - activate Corhazi Outlook from the main phase
//   - Glimpse 1: keep the looked-at card on top
gaScenario('corhazi-outlook-prep-counter-glimpse-does-not-rest')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 1]])
    ->seed('myField', 'Corhazi Outlook')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'Top=em6eEh9q8y;Bottom=')
    ->tested('Corhazi Outlook')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'One preparation counter was removed as the cost (2 -> 1: the patched 1 plus the 1 Corhazi Outlook\'s own On Enter added when it was seeded)')
    ->expectProperty('p1Field-1', 'Status', '2', 'The cost has no [REST]: Corhazi Outlook is still awake right after paying it', step: 1)
    ->expectProperty('p1Field-1', 'Status', '2', 'Corhazi Outlook is still awake after the Glimpse resolved')
    ->expectZoneCount('myDeck', 11, 1, 'The glimpsed card stayed on top: deck size unchanged')
    ->register();

// --- Cloaked Executioner: [Class Bonus] Fast Activation lets this ally be activated on the opponent's turn ---
// Cloaked Executioner (itwys9kf4r, WIND ALLY, reserve 2, power 2 / life 1): "[Class Bonus] Fast
// Activation. Ambush." An ally is normally slow; with an ASSASSIN champion it is listed in
// $cbFastActivationCards (Custom/OpportunityLogic.php), so player 1 is offered it in the priority
// window after player 2 plays a Dungeon Guide on its own turn and plays it for real (2 reserve
// payments): it enters player 1's field while it is player 2's turn. Ambush is covered by cloaked-
// executioner-ambush-retaliates-for-another-ally.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (ASSASSIN) -> Class Bonus active
//   - Cloaked Executioner -> p1 myHand-7
//   - end player 1 turn
//   - p1 passes a window
//   - p1 passes a window
//   - player 2 plays Dungeon Guide (opens a window for player 1)
//   - reserve 1/3
//   - reserve 2/3
//   - reserve 3/3
//   - player 1 activates Cloaked Executioner at fast speed on the opponent's turn
//   - player 2 declines Dungeon Guide's On Enter
gaScenario('cloaked-executioner-class-bonus-fast-activation-ally')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myHand', 'Cloaked Executioner')
    ->endTurn()->pass(1)->pass(1)
    ->playSlot(0, 2)->pay(3, 2)->target('myHand-7')->pay(2)->answer(2, 'NO')
    ->tested('Cloaked Executioner')
    ->register();

// --- Cloaked Executioner: without the Class Bonus it is a slow ally and cannot be activated on the opponent's turn ---
// Cloaked Executioner (itwys9kf4r): Fast Activation is a [Class Bonus] clause. With the starting
// champion Spirit of Wind (no Assassin) it is a plain slow ally, so playing it from hand during
// player 2's main phase does nothing: it stays in hand and no ally enters.
// Notes carried over from the original definition:
//   - Cloaked Executioner -> p1 myHand-7 (champion Spirit of Wind is not an Assassin)
//   - end player 1 turn (no fast cards, so no priority windows)
//   - player 2 plays Dungeon Guide
//   - reserve 1/3
//   - reserve 2/3
//   - reserve 3/3
//   - player 2 declines Dungeon Guide's On Enter
//   - player 1 tries to activate the slow Cloaked Executioner during the opponent's turn -- nothing happens
gaScenario('cloaked-executioner-slow-without-class-bonus')
    ->deck($tristanDeck)
    ->seed('myHand', 'Cloaked Executioner')
    ->endTurn()
    ->playSlot(0, 2)->pay(3, 2)->answer(2, 'NO')
    ->play('Cloaked Executioner')
    ->register();

// --- Cloaked Executioner: Ambush lets it retaliate against an attacker while it is not the defender ---
// Cloaked Executioner (itwys9kf4r): "Ambush (This ally may retaliate against attackers while not
// defending.)" Handled by HasAmbush()/HasKeyword_Ambush() (Custom/CombatLogic.php). Player 1
// controls a Dungeon Guide (the actual target) and a Cloaked Executioner; player 2 attacks the
// Dungeon Guide with its own Dungeon Guide (1 POWER). The Retaliate prompt offers the Executioner
// as well as the defender; choosing the Executioner (2 POWER) rests it and deals its own 2 POWER
// to the attacker, while the defender still takes the attacker's 1 damage. The Executioner takes
// no damage because it is not the defender.
// Notes carried over from the original definition:
//   - Dungeon Guide (the actual defender) -> p1 field-1
//   - Cloaked Executioner (Ambush retaliator, power 2) -> p1 field-2
//   - Dungeon Guide (attacker, power 1) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's Dungeon Guide
//   - player 1 retaliates with Cloaked Executioner (via Ambush) instead of the defender
gaScenario('cloaked-executioner-ambush-retaliates-for-another-ally')
    ->deck($tristanDeck)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Cloaked Executioner')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->target('myField-2')
    ->tested('Cloaked Executioner')
    ->expectDamage('p2Field-1', 2, 'The attacker took the Executioner\'s own 2 POWER (not the defender\'s 1)')
    ->expectProperty('p1Field-2', 'Status', '1', 'The Executioner rested as the cost of retaliating via Ambush')
    ->expectDamage('p1Field-2', 0, 'The Executioner took no damage: it was never the defender')
    ->expectDamage('p1Field-1', 1, 'The actual defender still took the attacker\'s 1 damage')
    ->expectDqEmpty()
    ->register();

// --- Coup de Grace (5qWWpkgQLl): Prepare 4; [Class Bonus] On Attack: if prepared, critical 4 ---
// Notes carried over from the original definition:
//   - Coup de Grace -> p2 myHand-7
//   - pay Prepare 4
//   - attack player 1's champion
//   - player 1 is offered "Discard 4 to prevent critical?" (critical 4 only exists because Coup de Grace was prepared) and declines
gaScenario('coup-de-grace-prepare-4-paid-critical-4-doubles-damage')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 4]], player: 2)
    ->seed('myHand', 'Coup de Grace', player: 2)
    ->endTurn()
    ->play('Coup de Grace', 2)->pay(4, 2)->answer(2, 'YES')->answer(2, 'theirField-0')->answer(1, 'NO')
    ->register();

// Notes carried over from the original definition:
//   - decline Prepare 4
gaScenario('coup-de-grace-prepare-4-declined-no-critical')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 4]], player: 2)
    ->seed('myHand', 'Coup de Grace', player: 2)
    ->endTurn()
    ->play('Coup de Grace', 2)->pay(4, 2)->answer(2, 'NO')->answer(2, 'theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - no Prepare prompt: 3 counters < 4
gaScenario('coup-de-grace-three-counters-cannot-pay-prepare-4')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 3]], player: 2)
    ->seed('myHand', 'Coup de Grace', player: 2)
    ->endTurn()
    ->play('Coup de Grace', 2)->pay(4, 2)->answer(2, 'theirField-0')
    ->register();

gaScenario('coup-de-grace-prepare-4-paid-non-assassin-no-critical')
    ->deck($gaNonAssassinDeck)
    ->patch('myField-0', ['Counters' => ['preparation' => 4]], player: 2)
    ->seed('myHand', 'Coup de Grace', player: 2)
    ->endTurn()
    ->play('Coup de Grace', 2)->pay(4, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - banish Return to the Archive
gaScenario('condemning-evisceration-prepare-1-paid-banish-floating-deals-4-more')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'tafqldAGRF', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myGraveyard', 'Return to the Archive', player: 2)
    ->seed('myHand', 'Condemning Evisceration', player: 2)
    ->endTurn()
    ->play('Condemning Evisceration', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->answer(2, 'myGraveyard-0')
    ->register();

// Notes carried over from the original definition:
//   - decline the optional banish
gaScenario('condemning-evisceration-prepare-1-paid-skip-banish-no-extra-damage')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'tafqldAGRF', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myGraveyard', 'Return to the Archive', player: 2)
    ->seed('myHand', 'Condemning Evisceration', player: 2)
    ->endTurn()
    ->play('Condemning Evisceration', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, 'theirField-0')->pass(2)
    ->register();

gaScenario('condemning-evisceration-prepare-1-declined-no-banish-offer')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'tafqldAGRF', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myGraveyard', 'Return to the Archive', player: 2)
    ->seed('myHand', 'Condemning Evisceration', player: 2)
    ->endTurn()
    ->play('Condemning Evisceration', 2)->pay(2, 2)->answer(2, 'NO')->answer(2, 'theirField-0')
    ->register();

// "[Class Bonus] Prepare 1" is only available while the class bonus is active: player 2 keeps the
// deck's default Spirit of Fire champion (class SPIRIT) re-lineaged with WATER + LUXEM (unlocking the
// EXALTED/WATER card) and holds a preparation counter, yet is NEVER offered Prepare 1.
// Notes carried over from the original definition:
//   - Return to the Archive (Floating Memory)
//   - no Prepare prompt: the class bonus is inactive
gaScenario('condemning-evisceration-non-assassin-class-bonus-prepare-not-offered')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Subcards' => ['tafqldAGRF', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myGraveyard', 'Return to the Archive', player: 2)
    ->seed('myHand', 'Condemning Evisceration', player: 2)
    ->endTurn()
    ->play('Condemning Evisceration', 2)->pay(2, 2)->answer(2, 'theirField-0')
    ->register();

// top six, top to bottom: DG, FS, Fairy Whispers (wind but a spell), FS, Baby Green Slime (WIND ally, 5th), DG; then Charge the Soul (marker)
gaScenario('captain-archer-imbued-look-six-put-deep-wind-ally-distant-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Deadly Duelist', lineage: ['Spirit of Water', 'Spirit of Wind'])
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Captain Archer')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Fluffy Shopkeep', 'Baby Green Slime', 'Dungeon Guide', 'Charge the Soul'])
    ->playSlot(4)->answer(1, 'YES')->pay(4)->target('myTempZone-4')
    ->tested('Captain Archer')
    ->register();

gaScenario('captain-archer-imbued-look-six-decline-all-six-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Deadly Duelist', lineage: ['Spirit of Water', 'Spirit of Wind'])
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Captain Archer')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Fluffy Shopkeep', 'Baby Green Slime', 'Dungeon Guide', 'Charge the Soul'])
    ->playSlot(4)->answer(1, 'YES')->pay(4)->pass(1)
    ->tested('Captain Archer')
    ->register();

// no wind ally among the top six (Fairy Whispers is a wind SPELL): no prompt, all six go to the bottom
gaScenario('captain-archer-imbued-look-six-no-wind-ally-all-six-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Deadly Duelist', lineage: ['Spirit of Water', 'Spirit of Wind'])
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Captain Archer')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fairy Whispers', 'Charge the Soul'])
    ->playSlot(4)->answer(1, 'YES')->pay(4)
    ->tested('Captain Archer')
    ->register();

// Notes carried over from the original definition:
//   - Converge Reflections -> myHand-7
//   - sacrifice fodder (non-token weapon) -> myField-1
//   - Scepter of Lumina -> theirField-1
//   - Forest Cake -> theirField-2
gaScenario('converge-reflections-refuses-memory-only-item-destroys-low-reserve-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Converge Reflections')
    ->seed('myField', 'Shardforged Blade')
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->play('Converge Reflections')->target('myField-1')->pay(2)
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->target('theirField-2')
    ->register();

// Cone of Frost (i7sbjy86ep): "[Level 1+] Deal 2 damage to up to one target unit. [Level 3+] Deal 2 damage to up to one target unit." Each level is its own
// "up to one" effect. At level 3, decline the first target (PASS) and pick one for the second: the damage must land (ConeOfFrostStep was queued without
// dontSkipOnPass, so declining the first target skipped the whole chain and the level-3 offer never came).
// Notes carried over from the original definition:
//   - WATER lineage/element unlock, level 3
//   - Cone of Frost -> myHand-7
gaScenario('cone-of-frost-level3-decline-first-target-still-offers-second')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Spirit of Water'], level: 3)
    ->seed('myHand', 'Cone of Frost')
    ->castSpell('Cone of Frost', pay: 3, window: 'me', target: 'theirField-0')
    ->register();

// Clarent, Reimagined (kINobk9KQA): "[Lorraine Bonus] While paying for this card's memory cost, you may banish a card named Clarent, Sword of Peace and up
// to one other Sword Regalia card from your material deck. Each card banished this way pays for 1 of that cost." Decline the banish offer (PASS): the
// card must still be materialized and its memory cost paid normally (ClarentReimaginedMatCost was queued without dontSkipOnPass, so the decline skipped it
// and the materialization silently never happened).
gaScenario('clarent-reimagined-declined-material-banish-still-materializes')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Clarent, Reimagined
1 Clarent, Sword of Peace
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Spirit of Water'])
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-0')->pass(1)
    ->tested('Clarent, Reimagined')
    ->register();

// Crystal Accretion (HDeEE7YPTl): "Put a sheen counter on each unit with a sheen counter on it."
// Notes carried over from the original definition:
//   - WATER element unlock, ASSASSIN class
//   - Dungeon Guide with sheen -> myField-1
//   - Fluffy Shopkeep without sheen -> myField-2 (stays without)
//   - Crystal Accretion -> myHand-7
gaScenario('crystal-accretion-adds-sheen-to-units-with-sheen')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Spirit of Water'], classes: 'ASSASSIN')
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-1', ['Counters' => ['sheen' => 1]])
    ->seed('myField', 'Fluffy Shopkeep')
    ->seed('myHand', 'Crystal Accretion')
    ->castSpell('Crystal Accretion', pay: 2)
    ->register();

// Crystallized Anthem (XfAJlQt9hH): "Prevent the next 2 damage that would be dealt to each unit you control this turn. ..."
// Notes carried over from the original definition:
//   - ASSASSIN class; Spirit of Wind supplies WIND, Mordred, Fated Luminary in the lineage supplies EXALTED
//   - Dungeon Guide -> myField-1
//   - Crystallized Anthem -> myHand-7
gaScenario('crystallized-anthem-marks-each-unit-you-control')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Mordred, Fated Luminary'], classes: 'ASSASSIN')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Crystallized Anthem')
    ->castSpell('Crystallized Anthem', pay: 2)
    ->register();

// Castling (tFOpmUdi2W): "Until end of turn, up to two target Chessman allies gain spellshroud and taunt, and get +2 LIFE." Declining the first target (PASS) used to skip CastlingFirstTarget, so the second target was never offered.
// Notes carried over from the original definition:
//   - GUARDIAN class
//   - Pawn Piece -> myField-1
//   - Pawn Piece -> myField-2
//   - Castling -> myHand-7
gaScenario('castling-decline-first-target-still-offers-the-second')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myField', 'Pawn Piece')
    ->seed('myField', 'Pawn Piece')
    ->seed('myHand', 'Castling')
    ->castSpell('Castling', pay: 4, window: 'me', target: 'myField-2')
    ->register();

// Notes carried over from the original definition:
//   - there is no pending choice: the unaffordable banished Dungeon Guide cannot be taken into the hand for free
gaScenario('clockwork-musicbox-unaffordable-banished-card-is-not-activatable-and-stays-banished')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myField', 'Clockwork Musicbox')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['_musicbox' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myBanish-0')
    ->tested('Clockwork Musicbox')
    ->register();

gaScenario('clockwork-musicbox-activates-an-affordable-banished-card')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Clockwork Musicbox')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['_musicbox' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myBanish-0')->pay(3)->answer(1, 'NO')
    ->tested('Clockwork Musicbox')
    ->register();

gaScenario('coronal-of-rejuvenation-unaffordable-banished-card-is-not-playable-and-stays-banished')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myField', 'Coronal of Rejuvenation')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['_coronal' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myBanish-0')
    ->tested('Coronal of Rejuvenation')
    ->register();

gaScenario('coronal-of-rejuvenation-plays-an-affordable-banished-card')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Coronal of Rejuvenation')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['_coronal' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myBanish-0')->pay(3)->answer(1, 'NO')
    ->tested('Coronal of Rejuvenation')
    ->register();

// Cell Forging (pufooz13xf): "Choose one -- Put two durability counters on target weapon. / Summon a Powercell token." Not choosing a weapon (PASS) is the Powercell mode: it used to do nothing at all.
// Notes carried over from the original definition:
//   - GUARDIAN class
//   - Clarent, Sword of Peace (a weapon) -> myField-1
//   - Cell Forging -> myHand-7
gaScenario('cell-forging-declined-weapon-summons-a-powercell')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myHand', 'Cell Forging')
    ->castSpell('Cell Forging', pay: 3, window: 'me')
    ->register();

// Conflagrant Sentinel (puyzn48srd): "On Enter: You may discard a card. If you do, put a buff counter on it."
// Notes carried over from the original definition:
//   - Conflagrant Sentinel -> myHand-7
gaScenario('conflagrant-sentinel-enter-discard-a-card-puts-a-buff-counter-on-it')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myHand', 'Conflagrant Sentinel')
    ->castSpell('Conflagrant Sentinel', pay: 4)
    ->register();

gaScenario('conflagrant-sentinel-enter-declined-discard-gets-no-buff-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myHand', 'Conflagrant Sentinel')
    ->castSpell('Conflagrant Sentinel', pay: 3, window: 'me')
    ->register();

// Charge the Soul (ra9950o14t): "Deal 1 damage to target unit. Then you may load it into an Aetherwing weapon you control."
// Notes carried over from the original definition:
//   - Charge the Soul -> myHand-7
gaScenario('charge-the-soul-deals-one-damage-to-target-unit')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'RANGER')
    ->seed('myHand', 'Charge the Soul')
    ->castSpell('Charge the Soul', pay: 1, target: 'theirField-0')
    ->register();

// Coy Bouclier (vo1qr9bkme): "[Level 2+] This card costs 2 less to activate." (5 -> 3 reserve payments)
// Notes carried over from the original definition:
//   - Coy Bouclier -> myHand-7
gaScenario('coy-bouclier-level-two-costs-two-less')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(level: 2, classes: 'GUARDIAN')
    ->seed('myHand', 'Coy Bouclier')
    ->castSpell('Coy Bouclier', pay: 3)
    ->register();

// Corsair Captain (4e1gqwah01): "Ranged 2 (as long as this unit is distant, its attacks get +2 POWER). [Class Bonus] Floating Memory."
// Notes carried over from the original definition:
//   - Corsair Captain (power 1), distant -> myField-1
gaScenario('corsair-captain-ranged-two-gets-plus-two-power-while-distant')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Corsair Captain', properties: ['TurnEffects' => ['DISTANT']])
    ->target('myField-0')
    ->tested('Corsair Captain')
    ->register();

// Notes carried over from the original definition:
//   - Corsair Captain -> myField-1
gaScenario('corsair-captain-not-distant-has-base-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Corsair Captain')
    ->target('myField-0')
    ->tested('Corsair Captain')
    ->register();

// Negative twin of 'corhazi-lightblade-reveal-luxem-critical': the champion's class does not match, so the [Class Bonus] must NOT apply.
// Notes carried over from the original definition:
//   - Corhazi Lightblade
//   - awake, can attack
//   - Bathe in Light (LUXEM) - sole memory card, deterministic reveal
//   - end player 1's turn 1 (first-player attack lock)
//   - player 2 declines their own materialize offer
//   - Corhazi Lightblade declares an attack
//   - target player 1's champion
gaScenario('corhazi-lightblade-without-the-class-bonus-does-not-reveal-or-gain-critical')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Corhazi Lightblade', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->seed('myMemory', 'Bathe in Light', player: 2)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->tested('Corhazi Lightblade')
    ->register();

// Negative twin of 'corhazi-courier-onhit-draw-discard-damage': the champion's class does not match, so the [Class Bonus] must NOT apply.
// Notes carried over from the original definition:
//   - Corhazi Courier
//   - awake, can attack
//   - ends turn 1 (first-player attack lock)
//   - declare attack with Corhazi Courier
//   - target opponent's champion
//   - discard the second (FIRE) copy
//   - choose the opponent's champion to deal 1 damage to
gaScenario('corhazi-courier-without-the-class-bonus-on-hit-does-not-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Corhazi Courier', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->seed('myHand', 'Corhazi Courier', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(2, 'myHand-7')->answer(2, 'theirField-0')
    ->tested('Corhazi Courier')
    ->register();

// Negative twin of 'caretaker-drone-class-bonus-death-glimpse': the champion's class does not match, so the [Class Bonus] must NOT apply.
// Notes carried over from the original definition:
//   - Caretaker Drone
//   - sacrifice Caretaker Drone as the mandatory cost
gaScenario('caretaker-drone-without-the-class-bonus-on-death-does-not-glimpse')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Caretaker Drone')
    ->seed('myHand', 'Undeniable Truth')
    ->play('Undeniable Truth')->target('myField-1')->pay(1)
    ->tested('Caretaker Drone')
    ->expectZoneCount('myGraveyard', 2, 1, 'Caretaker Drone and Undeniable Truth are in the graveyard')
    ->expectDqEmpty()
    ->register();

// Condemned Trinket (21oy1nd4nw): "(3), Banish Condemned Trinket: Banish a card from your graveyard and put an omen counter on it."
// Notes carried over from the original definition:
//   - Condemned Trinket -> myField-1
//   - Fluffy Shopkeep -> myGraveyard-0
gaScenario('condemned-trinket-pays-three-banishes-itself-and-omens-a-graveyard-card')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Condemned Trinket')
    ->seed('myGraveyard', 'Fluffy Shopkeep')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->target('myGraveyard-0')
    ->tested('Condemned Trinket')
    ->register();

// Cloak of Stillwater (2ha4dk88zq): "[REST], Banish a card with floating memory from your graveyard: Prevent the next 3 damage that would be dealt to your champion this turn."
// Notes carried over from the original definition:
//   - Cloak of Stillwater -> myField-1
//   - Fast Cure (floating memory) -> myGraveyard-0
gaScenario('cloak-of-stillwater-rest-and-banish-a-floating-memory-card-prevents-three-champion-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Cloak of Stillwater')
    ->seed('myGraveyard', 'Fast Cure')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myGraveyard-0')
    ->tested('Cloak of Stillwater')
    ->register();

gaAbilitySandbox('chime-of-endless-dreams-ability-1-pays-its-printed-cost', 'Chime of Endless Dreams', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Chime of Endless Dreams')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 1, graveyard: 0, field: 6)
    ->register();

gaAbilitySandbox('channeltech-charm-s-ability-1-pays-its-printed-cost', 'ChannelTech Charm S', classes: 'CLERIC,MAGE', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('ChannelTech Charm S')
    ->expectCostFootprint(hand: 13, memory: 3, banish: 0, graveyard: 4, field: 6)
    ->register();

gaAbilitySandbox('cooktech-mixer-ability-1-pays-its-printed-cost', 'CookTech Mixer', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('CookTech Mixer')
    ->register();

gaAbilitySandbox('cooktech-mixer-ability-2-pays-its-printed-cost', 'CookTech Mixer', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->target('myField-3')->pass(1)
    ->tested('CookTech Mixer')
    ->expectCostFootprint(hand: 13, memory: 3, banish: 0, graveyard: 4, field: 5)
    ->register();

gaAbilitySandbox('cellwarden-droid-ability-1-pays-its-printed-cost', 'Cellwarden Droid', classes: 'TAMER', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)->pass(1)
    ->tested('Cellwarden Droid')
    ->register();

// Constellation's Blessing (nypwwnirjk): "[Class Bonus] Draw a card. Then you may load Constellation's Blessing into an Aetherwing weapon you control."
gaScenario('constellations-blessing-class-bonus-draws-then-loads')
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
    ->seed('myHand', 'Constellation\'s Blessing')
    ->play('Constellation\'s Blessing')->pay(1)->answer(1, 'YES')
    ->register();

gaScenario('constellations-blessing-without-the-class-bonus-does-not-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Arisanna, Astral Zenith'])
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myHand', 'Constellation\'s Blessing')
    ->play('Constellation\'s Blessing')->pay(1)->answer(1, 'YES')
    ->register();

gaScenario('ciel-loyal-valet-end-phase-banishes-a-hand-card-as-an-omen')
    ->deck($gaCielDeckMat)
    ->champion(cardName: 'Ciel, Loyal Valet')
    ->endTurn()->pay(1)
    ->tested('Ciel, Loyal Valet')
    ->register();

gaScenario('ciel-loyal-valet-end-phase-decline-adds-no-omen')
    ->deck($gaCielDeckMat)
    ->champion(cardName: 'Ciel, Loyal Valet')
    ->endTurn()->pass(1)
    ->tested('Ciel, Loyal Valet')
    ->expectZoneCount('myBanish', 0, 1, 'Declined: no omen')
    ->expectZoneCount('myHand', 7, 1, 'Hand unchanged')
    ->expectDqEmpty()
    ->register();

// Ciel, Omenbringer (o69ogocemo): "On Enter: For each omen you have, discard a card from your hand or memory and draw a card into your memory."
gaScenario('ciel-omenbringer-enter-discards-and-draws-once-per-omen')
    ->deck($gaCielDeckMat)
    ->champion(cardName: 'Ciel, Loyal Valet')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Fluffy Shopkeep', properties: ['Counters' => ['omen' => 1]])
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()->pass(1)
    ->endTurn(2)->target('myMaterial-0')->pay(2)
    ->tested('Ciel, Omenbringer')
    ->register();

gaScenario('ciel-mirages-grave-omen-counter-deals-two-unpreventable-damage-to-a-unit')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Ciel, Mirage\'s Grave')
    ->seed('myField', 'Manxome Armoire')
    ->seed('myBanish', 'Fluffy Shopkeep', properties: ['Counters' => ['omen' => 1]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myBanish-0')->pay(1)->target('theirField-1')
    ->tested('Ciel, Mirage\'s Grave')
    ->register();

gaScenario('ciel-mirages-grave-omen-counter-trigger-declined-deals-no-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Ciel, Mirage\'s Grave')
    ->seed('myField', 'Manxome Armoire')
    ->seed('myBanish', 'Fluffy Shopkeep', properties: ['Counters' => ['omen' => 1]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myBanish-0')->pay(1)->pass(1)
    ->tested('Ciel, Mirage\'s Grave')
    ->register();

// --- Convergent Beam (hPwHRfUpN2): "Deal 1 damage to target unit. If you control a Fractal object, deal 3 damage to that unit instead." The Fractal flag was computed in the activation body and read
// (undefined) by the resolution handler, so the 3-damage branch could never apply. Without a Fractal: 1 damage; with a Core Fractal: 3. ---
gaScenario('convergent-beam-without-fractal-deals-one-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Convergent Beam')
    ->castSpell('Convergent Beam', pay: 2, target: 'theirField-0')
    ->register();

gaScenario('convergent-beam-with-a-fractal-deals-three-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Convergent Beam')
    ->seed('myField', 'Core Fractal')
    ->castSpell('Convergent Beam', pay: 2, target: 'theirField-0')
    ->tested('Convergent Beam', 'Core Fractal')
    ->register();

// Corhazi Arsonist (0ejcyuvuxn): "If a unit hit by CARDNAME this turn would die, banish it instead." The On Hit read $target (undefined), so the hit unit was never marked and went to the graveyard.
// Notes carried over from the original definition:
//   - Corhazi Arsonist (2 power) -> field-1
//   - Dungeon Guide (3 life, 1 power) -> theirField-1
//   - Ignite the Soul (1 damage to target unit): finishes the hit Dungeon Guide later in the same turn -> myHand-7
gaScenario('corhazi-arsonist-hit-unit-that-dies-is-banished-instead')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Corhazi Arsonist')
    ->patch('myField-1', ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Ignite the Soul')
    ->endTurn()->pass(1)->pass(1)
    ->endTurn(2)->pass(1)->pass(1)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-1')->passBoth()
    ->castSpell('Ignite the Soul', pay: 1, window: 'me', target: 'theirField-1')
    ->tested('Corhazi Arsonist', 'Ignite the Soul')
    ->register();

// Carpsong Coda (3omh6h3a4y): "Deal an amount of damage to target unit equal to the highest life stat among water element Animal and Beast ally cards in your graveyard." The resolution handler read
// $highestLife (undefined): 0 damage. Graveyard: Baby Blue Slime (life 2) and Jueying, Shadowmare (BEAST, life 3): 3 damage to the opponent's champion.
// Notes carried over from the original definition:
//   - Baby Blue Slime (ANIMAL, life 2)
//   - Jueying, Shadowmare (BEAST, life 3)
gaScenario('carpsong-coda-deals-damage-equal-to-highest-life-water-animal-beast-in-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Carpsong Coda')
    ->seed('myGraveyard', 'Baby Blue Slime')
    ->seed('myGraveyard', 'Jueying, Shadowmare')
    ->castSpell('Carpsong Coda', pay: 2, target: 'theirField-0')
    ->register();

// Convoking Slime (b1w1mvu68a): "[Class Bonus] At the beginning of your recollection phase, summon a copy of CARDNAME rested." The recollection-phase loop re-read count($field) every iteration, so each new copy
// triggered in turn and summoned another one -- an unbounded loop (thousands of slimes, then a timeout). Exactly one copy must be summoned.
// Notes carried over from the original definition:
//   - Tamer champion: the Class Bonus applies
//   - Convoking Slime -> field-1
gaScenario('convoking-slime-recollection-summons-exactly-one-rested-copy')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'TAMER')
    ->seed('myField', 'Convoking Slime')
    ->endTurn()
    ->endTurn(2)
    ->tested('Convoking Slime')
    ->register();

// Notes carried over from the original definition:
//   - player 1 activates Collapsing Trap FROM MEMORY (alternate cost: remove one preparation counter)
//   - both pass: Collapsing Trap resolves, then the Dungeon Guide enters
gaScenario('collapsing-trap-memory-alt-cost-removes-one-counter-and-allies-enter-rested')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'tafqldAGRF'], 'Counters' => ['preparation' => 1]])
    ->seed('myMemory', 'Collapsing Trap')
    ->endTurn()
    ->playSlot(0, 2)->pay(3, 2)->target('myMemory-0')->passBoth()
    ->tested('Collapsing Trap')
    ->register();

// Collapsing Trap in memory is NOT offered without the preparation counter: the Dungeon Guide enters awake.
// Notes carried over from the original definition:
//   - no window is offered to player 1 (no preparation counter): the Dungeon Guide enters awake and player 2 declines its On Enter
gaScenario('collapsing-trap-memory-alt-cost-without-a-counter-is-not-offered')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Water'])
    ->seed('myMemory', 'Collapsing Trap')
    ->endTurn()
    ->playSlot(0, 2)->pay(3, 2)->answer(2, 'NO')
    ->tested('Collapsing Trap')
    ->register();

// Cone of Frost (i7sbjy86ep) when the caster's champion has an activatable ability: the Opportunity window that opens after payment is answered with PASS. Passing the window must NOT cancel the spell's own effect.
gaScenario('cone-of-frost-effect-still-resolves-after-passing-the-opportunity-window')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['CardID' => 'enxi6tshtu', 'Subcards' => ['tafqldAGRF'], 'Counters' => ['level' => 7, 'enlighten' => 3, '_overrides' => ['classes' => 'MAGE', 'element' => 'WATER']]])
    ->seed('myHand', 'Cone of Frost')
    ->castSpell('Cone of Frost', pay: 2, window: 'me', target: 'theirField-0')
    ->register();

// An Action card sits in the graveyard only once it has finished resolving; while it resolves its own effect cannot see it. Cyclical Breeze (3cmrkv3y16): "Return target wind element Spell card from your graveyard
// to your hand." It is itself a wind Spell, so with no other card in the graveyard it used to offer (and return) itself; now there is no legal target, the effect does nothing and the card stays in the graveyard.
gaScenario('cyclical-breeze-cannot-return-itself-while-resolving')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Cyclical Breeze')
    ->castSpell('Cyclical Breeze', pay: 4)
    ->expectZoneCount('myGraveyard', 1, 1, 'Cyclical Breeze finished resolving and is in the graveyard; it was not offered (or returned) as its own target')
    ->expectZoneCount('myHand', 3, 1, 'Nothing came back to the hand (8 cards - Breeze - 4 paid)')
    ->expectDqEmpty()
    ->register();

// Crossroads Specter (r3i9nmxhnb): "On Champion Hit: That opponent banishes a card from their material deck." The choice is the OPPONENT's, written in player 2's own frame ("myMaterial-N").
// Notes carried over from the original definition:
//   - Crossroads Specter -> p1 field-1
gaScenario('crossroads-specter-champion-hit-opponent-banishes-a-material-card')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Backup Charger
1 Purifying Thurible
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Crossroads Specter')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')->answer(2, 'myMaterial-1')
    ->tested('Crossroads Specter')
    ->register();

// Notes carried over from the original definition:
//   - Guo Jia lineage
//   - Craggy Fatestone with ONE buff counter -> myField-1
//   - refused: the transform needs two buff counters (the click is a silent no-op)
gaScenario('craggy-fatestone-transform-is-refused-with-only-one-buff-counter')
    ->deck($gaCraggyDeck)
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Craggy Fatestone', properties: ['Counters' => ['buff' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Craggy Fatestone')
    ->register();

// Notes carried over from the original definition:
//   - Guo Jia lineage
//   - Craggy Fatestone already RESTED, two buff counters
//   - refused: a rested Craggy Fatestone cannot pay the [REST] cost (the click is a silent no-op)
gaScenario('craggy-fatestone-transform-needs-an-awake-fatestone')
    ->deck($gaCraggyDeck)
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Craggy Fatestone', properties: ['Counters' => ['buff' => 2], 'Status' => 1])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Craggy Fatestone')
    ->register();

gaScenario('charge-the-soul-cannot-be-activated-from-the-graveyard-without-manabolt-convergence')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myGraveyard', 'Charge the Soul')
    ->action(1, 10002, 'myGraveyard-0!FSM!')
    ->tested('Charge the Soul')
    ->register();

gaScenario('capacitance-x-psycho-activates-from-the-material-deck-in-the-main-phase-entering-rested')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Lorraine, Blademaster', 'Lorraine, Arclight Saber'])
    ->seed('myMaterial', 'Capacitance X Psycho')
    ->seed('myBanish', 'Arcane Elemental')
    ->seed('myBanish', 'Arcane Elemental')
    ->seed('myBanish', 'Arcane Elemental')
    ->seed('myBanish', 'Arcane Elemental')
    ->seed('myBanish', 'Arcane Elemental')
    ->seed('myBanish', 'Arcane Elemental')
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Capacitance X Psycho')
    ->register();

gaScenario('capacitance-x-psycho-is-not-activated-from-the-material-deck-with-only-five-arcane-cards-banished')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Lorraine, Blademaster', 'Lorraine, Arclight Saber'])
    ->seed('myMaterial', 'Capacitance X Psycho')
    ->seed('myBanish', 'Arcane Elemental')
    ->seed('myBanish', 'Arcane Elemental')
    ->seed('myBanish', 'Arcane Elemental')
    ->seed('myBanish', 'Arcane Elemental')
    ->seed('myBanish', 'Arcane Elemental')
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Capacitance X Psycho')
    ->register();
