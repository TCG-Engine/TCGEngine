<?php
/**
 * Damage-spell fixtures, expressed with the scenario builder (see Scenario.php).
 * Each one replaces the inline definition of the same slug that used to live in
 * create-prd-ability-fixtures.php; the explanatory comments moved with it.
 */

// --- Dwarf Star's Glow: deal 2 damage to target unit ---
// Dwarf Star's Glow is an ASTRA (advanced element) ACTION card, so the starting champion's
// Subcards are patched with a real ASTRA champion (Arisanna, Astral Zenith) to unlock element
// access. Targets the opponent's champion directly for a straightforward damage assertion.
// The Starcalling alternate-cost clause and the "if starcalled, put into memory" clause are
// tied to the separate glimpse subsystem and are out of scope (this fixture pays the normal
// reserve cost).
gaScenario('dwarf-stars-glow-damage')
    ->standardDeck('Dwarf Star\'s Glow')
    ->readyFor('Dwarf Star\'s Glow')
    ->seed('myHand', 'Dwarf Star\'s Glow')
    ->castSpell('Dwarf Star\'s Glow', pay: 2, target: 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'The targeted opponent champion takes 2 damage')
    ->expectDqEmpty()
    ->register();

// --- Cosmic Bolt: deal 4 damage to target unit ---
// Cosmic Bolt is an ASTRA (advanced element) ACTION card, so the starting champion's Subcards
// are patched with a real ASTRA champion (Arisanna, Astral Zenith), same as
// dwarf-stars-glow-damage. Targets the opponent's champion directly. With no other copies of
// Cosmic Bolt in the graveyard/banishment, the damage resolves to the base 4 (no +2 bonus).
// The Starcalling alternate-cost clause is tied to the glimpse subsystem and is out of scope.
gaScenario('cosmic-bolt-damage')
    ->standardDeck('Cosmic Bolt')
    ->readyFor('Cosmic Bolt')
    ->seed('myHand', 'Cosmic Bolt')
    ->castSpell('Cosmic Bolt', pay: 3, target: 'theirField-0')
    ->register();

// --- Ignite the Soul: deal 1 damage to target unit ---
// Ignite the Soul's element is FIRE (matching the starting champion), so no lineage patch is
// needed. Its target pool is any object on the opponent's field (not filtered to ALLY/
// CHAMPION), so it targets the opponent's champion directly. The [Class Bonus] Floating
// Memory clause is a passive/reusable-elsewhere property, not a triggered ability, and is out
// of scope.
gaScenario('ignite-the-soul-damage')
    ->standardDeck('Ignite the Soul')
    ->seed('myHand', 'Ignite the Soul')
    ->castSpell('Ignite the Soul', pay: 1, window: 'both', target: 'theirField-0')
    ->expectDamage('p2Field-0', 1, 'The targeted opponent champion takes 1 damage')
    ->expectDqEmpty()
    ->register();

// --- Entrenched Fortress: On Enter deals 3 damage to target unit ---
// Entrenched Fortress is a TERA (advanced element) DOMAIN card, so the starting champion's
// Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock element
// access. Like other DOMAIN cards (see stocked-outpost-enter-draw-memory), it materializes
// onto the field like any other permanent -- there's no separate domain zone in this schema.
// Only the On Enter damage is covered; Taunt is a combat-targeting-priority passive that
// requires a real attack declaration and is out of scope.
gaScenario('entrenched-fortress-enter-damage')
    ->standardDeck('Entrenched Fortress')
    ->readyFor('Entrenched Fortress')
    ->seed('myHand', 'Entrenched Fortress')
    ->castSpell('Entrenched Fortress', pay: 3, target: 'theirField-0')
    ->register();

// --- Cometfall: deal 3 damage to all non-Astra units ---
// Cometfall is an ASTRA (advanced element) ACTION card, so the starting champion's Subcards
// are patched with a real ASTRA champion (Arisanna, Astral Zenith) to unlock element access --
// note this only grants element ACCESS, it doesn't change the champion's own element (both
// starting champions remain FIRE, so both take the sweep damage). No targeting decision is
// needed since the effect hits all non-Astra units unconditionally. Without the [Class Bonus]
// (CLERIC), the damage is the base 3, not the boosted 4. The Starcalling alternate-cost clause
// is tied to the glimpse subsystem and is out of scope.
gaScenario('cometfall-sweep-damage')
    ->standardDeck('Cometfall')
    ->readyFor('Cometfall')
    ->seed('myHand', 'Cometfall')
    ->castSpell('Cometfall', pay: 5)
    ->expectDamage('p1Field-0', 3, 'Our own champion takes the 3-damage sweep too, since it\'s non-Astra')
    ->expectDamage('p2Field-0', 3, 'The opponent\'s champion takes the 3-damage sweep')
    ->expectDqEmpty()
    ->register();

// --- Aenean Frostlance: deal 2 damage to target unit ---
// Aenean Frostlance's element is WATER, so the starting champion's Subcards are patched with
// a real WATER champion (Spirit of Water, reused from coriolis-ward-prevent-level/
// perfect-repulsion-prevent-x). Only the base 2-damage clause is covered; the [Class Bonus]
// [Level 3+]/[Level 6+] scaled-damage-to-a-rested-unit branches aren't reached.
gaScenario('aenean-frostlance-damage')
    ->standardDeck('Aenean Frostlance')
    ->readyFor('Aenean Frostlance')
    ->seed('myHand', 'Aenean Frostlance')
    ->castSpell('Aenean Frostlance', pay: 3, target: 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'The targeted opponent champion takes 2 damage')
    ->expectDqEmpty()
    ->register();

// --- Focused Flames: deal 4 damage to target ally ---
// Focused Flames' element is FIRE (matching the starting champion), so no lineage patch is
// needed. Dungeon Guide is seeded onto the opponent's field as the required ally target
// (unlike most other damage cards in this backlog, this one can't target a champion). Without
// the [Class Bonus] discount, the full 2-reserve cost is paid.
gaScenario('focused-flames-damage')
    ->standardDeck('Focused Flames')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Focused Flames')
    ->castSpell('Focused Flames', pay: 2, target: 'theirField-1')
    ->register();

// --- Juggle Knives: deal 1 damage to target champion ---
gaScenario('juggle-knives-damage-champion')
    ->standardDeck('Juggle Knives')
    ->seed('myHand', 'Juggle Knives')
    ->castSpell('Juggle Knives', pay: 2, window: 'both', target: 'theirField-0')
    ->register();

// --- Lightweaver's Assault: reveal memory, split damage equal to cards revealed ---
gaScenario('lightweavers-assault-split-damage')
    ->standardDeck('Lightweaver\'s Assault')
    ->readyFor('Lightweaver\'s Assault')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myHand', 'Lightweaver\'s Assault')
    ->castSpell('Lightweaver\'s Assault', pay: 4, target: 'theirField-0:6')
    ->register();

// --- Mark the Target: deal 1 damage to target unit ---
gaScenario('mark-the-target-damage')
    ->standardDeck('Mark the Target')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Mark the Target')
    ->castSpell('Mark the Target', pay: 1, window: 'both', target: 'theirField-1')
    ->register();

// --- Nascent Blast: deal 3 damage to target unit ---
gaScenario('nascent-blast-damage')
    ->standardDeck('Nascent Blast')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Nascent Blast')
    ->castSpell('Nascent Blast', pay: 3, window: 'both', target: 'theirField-1')
    ->register();

// --- Planted Explosive: deal 2 damage to target unit (not prepared) ---
gaScenario('planted-explosive-damage')
    ->standardDeck('Planted Explosive')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Planted Explosive')
    ->castSpell('Planted Explosive', pay: 2, window: 'both', target: 'theirField-1')
    ->register();

// --- Clumsy Apprentice: On Enter, deal 2 damage to your champion, draw a card ---
gaScenario('clumsy-apprentice-enter-damage-draw')
    ->standardDeck('Clumsy Apprentice')
    ->seed('myHand', 'Clumsy Apprentice')
    ->castSpell('Clumsy Apprentice', pay: 2)
    ->register();

// --- Solar Pinnacle: deal 2 damage to target unit, Empower 2 ---
gaScenario('solar-pinnacle-damage-empower')
    ->standardDeck('Solar Pinnacle')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Solar Pinnacle')
    ->castSpell('Solar Pinnacle', pay: 2, target: 'theirField-1')
    ->register();

// --- Umbra Sight: Draw a card; may draw into memory + curse lineage for damage ---
// Umbra Sight is UMBRA (advanced element), so the starting champion's Subcards are patched
// with a real UMBRA champion (Tristan, Shadowdancer) to unlock element access. Its reserve
// cost is a real printed 0 (not the "-1 = no reserve type" sentinel), so no reserve-payment
// decision is queued at all -- straight into the unconditional draw, then a YESNO for the
// optional draw-into-memory + curse-lineage effect. Choosing YES adds it to the champion's
// lineage and deals 2 unpreventable damage per curse in lineage (2, counting itself).
gaScenario('umbra-sight-curse-lineage-damage')
    ->standardDeck('Umbra Sight')
    ->readyFor('Umbra Sight')
    ->seed('myHand', 'Umbra Sight')
    ->castSpell('Umbra Sight', window: 'me', target: 'YES')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Subcards', '["he6kd7hocc","f15joh300z"]', 'Umbra Sight is put on the bottom of the champion\'s lineage')
    ->expectDamage('p1Field-0', 2, '2 unpreventable damage per curse in lineage (1 curse x 2)')
    ->register();

// --- Umbral Tithe: each player draws 2 into memory, then damage to high-memory champions ---
// Umbral Tithe is UMBRA (advanced element); same lineage patch as Umbra Sight. Its 5-reserve
// cost is discounted 1 per Curse in either champion's lineage -- with none seeded here the
// full 5 reps of the myHand-0 reserve-payment decision are needed. Player 1's memory is
// pre-seeded with 4 cards so the two drawn by this effect push them to 6+, triggering the
// 4-damage clause; player 2 starts at 0 and stays under the threshold.
gaScenario('umbral-tithe-memory-damage')
    ->standardDeck('Umbral Tithe')
    ->readyFor('Umbral Tithe')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Umbral Tithe')
    ->castSpell('Umbral Tithe', pay: 5)
    ->register();

// Negative twin of 'purge-in-flames-damage': the champion's class does not match, so the [Class Bonus] must NOT apply.
gaScenario('purge-in-flames-without-the-class-bonus-deals-two-damage')
    ->standardDeck('Purge in Flames')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Purge in Flames')
    ->castSpell('Purge in Flames', pay: 8)
    ->register();
// --- Mend Flesh: Damage 25+ discount + Recover 8 ---
// Mend Flesh is an EXIA (advanced element) ACTION card, so the starting champion's Subcards
// are patched with a real EXIA champion (Dante, Hemomancer) to unlock element access. The
// starting champion's Damage is pre-set to 25 to both reach the [Damage 25+] discount
// condition (activationCostModifierAbilities, GeneratedMacroCode.php) and make the
// unconditional Recover 8 observable as a Damage decrease.
gaScenario('mend-flesh-damage25-recover')
    ->standardDeck('Mend Flesh')
    ->champion(lineage: ['Dante, Hemomancer'], damage: 25)
    ->seed('myHand', 'Mend Flesh')
    ->play('Mend Flesh')
    ->register();

// --- Leeching Bolt: deal champion-level damage to target unit + Recover 2 ---
// Leeching Bolt is a TERA (advanced element) ACTION card, so the starting champion's Subcards
// are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock element access. The
// champion's Counters are also patched with 2 level counters (so "deal LV damage" resolves to
// a nonzero, observable 2, instead of the default level-0 no-op) and its Damage is pre-set to
// 5 (so Recover 2 is observable as a decrease). The damage targets the opponent's champion
// while Recover 2 heals our own, so the two effects are independently assertable. The [Class
// Bonus] empowered/preserved clause is out of scope.
gaScenario('leeching-bolt-damage-recover')
    ->standardDeck('Leeching Bolt')
    ->champion(lineage: ['Kongming, Fel Eidolon'], level: 2, damage: 5)
    ->seed('myHand', 'Leeching Bolt')
    ->castSpell('Leeching Bolt', pay: 2, window: 'both', target: 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'The opponent\'s champion takes damage equal to our champion\'s level (2)')
    ->expectDamage('p1Field-0', 3, 'Recover 2 reduces our champion\'s pre-existing 5 damage down to 3')
    ->expectDqEmpty()
    ->register();

// --- Fireball: deal 1+level damage to target unit ---
// Fireball's element is FIRE (matching the starting champion), so no lineage patch is needed.
// The starting champion's Counters are patched with 2 level counters so "deal 1+LV damage"
// resolves to a nonzero-interesting 3, instead of the default level-0 base of 1. Without the
// [Class Bonus] discount, the full 4-reserve cost is paid.
gaScenario('fireball-scaling-damage')
    ->standardDeck('Fireball')
    ->champion(level: 2)
    ->seed('myHand', 'Fireball')
    ->castSpell('Fireball', pay: 4, window: 'opp', target: 'theirField-0')
    ->expectDamage('p2Field-0', 3, 'Base 1 damage plus our champion\'s 2 level counters = 3')
    ->expectDqEmpty()
    ->register();

// --- Give Bath: remove all temporary damage from target ally ---
// Give Bath's element is WATER, so the starting champion's Subcards are patched with a real
// WATER champion (Spirit of Water, reused from coriolis-ward-prevent-level/
// perfect-repulsion-prevent-x/aenean-frostlance-damage). Dungeon Guide is seeded onto the
// opponent's field with 1 pre-existing Damage so the "remove all damage" effect is observable
// as a decrease to 0. The [Class Bonus] Floating Memory clause is out of scope.
gaScenario('give-bath-remove-damage')
    ->standardDeck('Give Bath')
    ->readyFor('Give Bath')
    ->seed('theirField', 'Dungeon Guide', properties: ['Damage' => 2])
    ->seed('myHand', 'Give Bath')
    ->castSpell('Give Bath', pay: 2, target: 'theirField-1')
    ->expectDamage('p2Field-1', 0, 'All 2 pre-existing damage is removed from the targeted ally')
    ->expectDqEmpty()
    ->register();

// --- Flourishing Qi: deal LV (+charge) damage to target unit ---
gaScenario('flourishing-qi-damage')
    ->standardDeck('Flourishing Qi')
    ->champion(lineage: ['Silvie, Slime Sovereign'], level: 2)
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Flourishing Qi')
    ->castSpell('Flourishing Qi', pay: 4, target: 'theirField-1')
    ->register();

// --- Purge in Flames: deal 2 damage to all units except your champion ---
gaScenario('purge-in-flames-damage')
    ->standardDeck('Purge in Flames')
    ->readyFor('Purge in Flames', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Purge in Flames')
    ->castSpell('Purge in Flames', pay: 8)
    ->register();

// --- Cone of Frost: level-conditional multi-target damage (Level 1+ step) ---
// Regression test for a fixed engine crash: activating Cone of Frost at champion level 1+
// called DecisionQueueController::AddDecision(..., lastDecision:"-"), a named argument that
// didn't exist on that method's signature, which fatally crashed every PHP 8 request that hit
// it. AddDecision() now accepts (and ignores) that argument. Cone of Frost's element is WATER,
// so the starting champion's Subcards are patched with a real WATER champion (Spirit of Water)
// to unlock element access, and its Counters are patched with a level-1 counter so the
// [Level 1+] clause is active (only the base level-1 step is exercised here; [Level 3+] and
// [Level 5+] add further MZMAYCHOOSE rounds out of scope for this fixture). Without a [Class
// Bonus] discount, the full 3-reserve cost is paid; completing the 3rd reserve payment
// cascades directly into the level-1 MZMAYCHOOSE (no separate opportunity-window PASS needed
// for this card), which offers our own champion as an optional damage target.
gaScenario('cone-of-frost-level1-damage')
    ->standardDeck('Cone of Frost')
    ->champion(lineage: ['Spirit of Water'], level: 1)
    ->seed('myHand', 'Cone of Frost')
    ->castSpell('Cone of Frost', pay: 3, target: 'myField-0')
    ->register();

// --- Relentless Outburst: for every 6 damage counters on your champion, deal 1 damage to all other units ---
// Regression test for a fixed engine crash: activating Relentless Outburst called the
// previously-undefined GetChampionDamage() function (see GrandArchiveSim/Custom/GameLogic.php),
// which fatally crashed the game. GetChampionDamage() is now an alias for the existing
// ChampionDamageCounters() helper, matching every other "[Damage N+]" card's convention.
// Relentless Outburst's element is EXIA, so the starting champion's Subcards are patched with
// a real EXIA champion (Dante, Hemomancer) to unlock element access, and the champion's
// Damage is pre-patched to 6 so intdiv(6, 6) = exactly 1 damage burst. A Dungeon Guide (ALLY)
// is seeded onto our own field so the "all other units" clause has a same-side target to
// distinguish from our own excluded champion. Without a [Class Bonus] discount, the full
// 3-reserve cost is paid; completing the 3rd reserve payment cascades directly into the
// ability's own MZCHOOSE (which champion the [Class Bonus][Damage 35+] retaliation-buff
// clause would apply to, out of scope here since Class Bonus is inactive) with no separate
// opportunity-window PASS needed for this card.
gaScenario('relentless-outburst-damage-burst')
    ->standardDeck('Relentless Outburst')
    ->champion(lineage: ['Dante, Hemomancer'], damage: 6)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Relentless Outburst')
    ->castSpell('Relentless Outburst', pay: 3, target: 'theirField-0')
    ->register();

