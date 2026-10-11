<?php
/**
 * Phase 3a (Fire): damage and single-effect spells. Each fixture seeds the card in hand, pays its real cost and checks the
 * printed effect; a negative twin covers the card's condition where it has one.
 */

// --- Aenean Pointed Flare: [Level 3+] costs 2 less; deal 4 damage to target champion ---
gaScenario('aenean-pointed-flare-deals-four-damage-to-a-champion')
    ->standardDeck('Aenean Pointed Flare')
    ->readyFor('Aenean Pointed Flare')
    ->seed('myHand', 'Aenean Pointed Flare')
    ->castSpell('Aenean Pointed Flare', pay: 4, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 4, 'The opposing champion took 4 damage')
    ->expectZoneCount('myMemory', 4, 1, 'Below level 3 the full 4 reserve is paid')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting', 'cost'], 'Deal 4 damage to target champion.')
    ->register();

gaScenario('aenean-pointed-flare-level-three-costs-two-less')
    ->standardDeck('Aenean Pointed Flare')
    ->readyFor('Aenean Pointed Flare', level: 3)
    ->seed('myHand', 'Aenean Pointed Flare')
    ->castSpell('Aenean Pointed Flare', pay: 2, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 4, 'The discounted spell still deals 4 damage')
    ->expectZoneCount('myMemory', 2, 1, '[Level 3+] Only 2 reserve were paid')
    ->expectDqEmpty()
    ->covers(['damage', 'cost-modification', 'level-gate'], '[Level 3+] This card costs 2 less to activate.')
    ->register();

// --- Blaze Alight: 3 unpreventable damage to target unit; [Class Bonus] 5 instead ---
gaScenario('blaze-alight-deals-three-unpreventable-damage')
    ->standardDeck('Blaze Alight')
    ->readyFor('Blaze Alight')
    ->seed('myHand', 'Blaze Alight')
    ->castSpell('Blaze Alight', pay: 2, window: 'both', target: 'theirField-0')
    ->expectDamage('p2Field-0', 3, 'Without the Class Bonus the target takes 3')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting', 'unpreventable'], 'Deal 3 unpreventable damage to target unit.')
    ->register();

gaScenario('blaze-alight-class-bonus-deals-five-instead')
    ->standardDeck('Blaze Alight')
    ->readyFor('Blaze Alight', classBonus: true)
    ->seed('myHand', 'Blaze Alight')
    ->castSpell('Blaze Alight', pay: 2, window: 'both', target: 'theirField-0')
    ->expectDamage('p2Field-0', 5, '[Class Bonus] The target takes 5 instead')
    ->expectDqEmpty()
    ->covers(['damage', 'class-bonus', 'unpreventable'], 'Class Bonus: Deal 5 unpreventable damage to that unit instead.')
    ->register();

// --- Aenean Scorching Comet: 2 damage; [Class Bonus][Level 3+] 4; [Class Bonus][Level 6+] 6 ---
gaScenario('aenean-scorching-comet-deals-two-damage')
    ->standardDeck('Aenean Scorching Comet')
    ->readyFor('Aenean Scorching Comet')
    ->seed('myHand', 'Aenean Scorching Comet')
    ->castSpell('Aenean Scorching Comet', pay: 2, target: 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'Base 2 damage')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting'], 'Deal 2 damage to target unit.')
    ->register();

gaScenario('aenean-scorching-comet-class-bonus-level-three-deals-four')
    ->standardDeck('Aenean Scorching Comet')
    ->readyFor('Aenean Scorching Comet', classBonus: true, level: 3)
    ->seed('myHand', 'Aenean Scorching Comet')
    ->castSpell('Aenean Scorching Comet', pay: 2, target: 'theirField-0')
    ->expectDamage('p2Field-0', 4, '[Class Bonus][Level 3+] 4 damage instead')
    ->expectDqEmpty()
    ->covers(['damage', 'class-bonus', 'level-gate'], '[Class Bonus] [Level 3+] Deal 4 damage to that unit instead.')
    ->register();

gaScenario('aenean-scorching-comet-class-bonus-level-six-deals-six')
    ->standardDeck('Aenean Scorching Comet')
    ->readyFor('Aenean Scorching Comet', classBonus: true, level: 6)
    ->seed('myHand', 'Aenean Scorching Comet')
    ->castSpell('Aenean Scorching Comet', pay: 2, target: 'theirField-0')
    ->expectDamage('p2Field-0', 6, '[Class Bonus][Level 6+] 6 damage instead')
    ->expectDqEmpty()
    ->covers(['damage', 'class-bonus', 'level-gate'], '[Class Bonus] [Level 6+] Deal 6 damage to that unit instead.')
    ->register();

gaScenario('aenean-scorching-comet-level-three-without-the-class-bonus-deals-two')
    ->standardDeck('Aenean Scorching Comet')
    ->readyFor('Aenean Scorching Comet', level: 3)
    ->seed('myHand', 'Aenean Scorching Comet')
    ->castSpell('Aenean Scorching Comet', pay: 2, target: 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'Without the Class Bonus level 3 changes nothing: 2 damage')
    ->expectDqEmpty()
    ->covers(['damage', 'class-bonus', 'condition'], '[Class Bonus] [Level 3+] Deal 4 damage to that unit instead.')
    ->register();

// --- Aenean Spark Alight: 2 unpreventable; [Class Bonus][Level 3+] 3 unpreventable ---
gaScenario('aenean-spark-alight-deals-two-unpreventable-damage')
    ->standardDeck('Aenean Spark Alight')
    ->readyFor('Aenean Spark Alight')
    ->seed('myHand', 'Aenean Spark Alight')
    ->castSpell('Aenean Spark Alight', pay: 2, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 2, 'Base 2 unpreventable damage')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting', 'unpreventable'], 'Deal 2 unpreventable damage to target unit.')
    ->register();

gaScenario('aenean-spark-alight-class-bonus-level-three-deals-three')
    ->standardDeck('Aenean Spark Alight')
    ->readyFor('Aenean Spark Alight', classBonus: true, level: 3)
    ->seed('myHand', 'Aenean Spark Alight')
    ->castSpell('Aenean Spark Alight', pay: 2, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 3, '[Class Bonus][Level 3+] 3 unpreventable damage instead')
    ->expectDqEmpty()
    ->covers(['damage', 'class-bonus', 'level-gate'], '[Class Bonus][Level 3+] Deal 3 unpreventable damage to that unit instead.')
    ->register();

// --- Aenean Flurry of Fire: 1 damage twice; [Class Bonus][Level 5+] two more times ---
gaScenario('aenean-flurry-of-fire-deals-one-damage-twice')
    ->standardDeck('Aenean Flurry of Fire')
    ->readyFor('Aenean Flurry of Fire')
    ->seed('myHand', 'Aenean Flurry of Fire')
    ->castSpell('Aenean Flurry of Fire', pay: 2, target: 'theirField-0')
    ->expectDamage('p2Field-0', 2, '1 damage twice = 2')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting'], 'Deal 1 damage to target unit twice.')
    ->register();

gaScenario('aenean-flurry-of-fire-class-bonus-level-five-deals-four')
    ->standardDeck('Aenean Flurry of Fire')
    ->readyFor('Aenean Flurry of Fire', classBonus: true, level: 5)
    ->seed('myHand', 'Aenean Flurry of Fire')
    ->castSpell('Aenean Flurry of Fire', pay: 2, target: 'theirField-0')
    ->expectDamage('p2Field-0', 4, '[Class Bonus][Level 5+] two more times = 4')
    ->expectDqEmpty()
    ->covers(['damage', 'class-bonus', 'level-gate'], '[Class Bonus] [Level 5+] Deal 1 damage to that unit two more times.')
    ->register();

// --- Equanimity's Ashes: [Level 3+] costs 3 less; 3 damage to a champion; [Class Bonus] +4 if it has five or less damage ---
gaScenario('equanimitys-ashes-deals-three-damage-to-a-champion')
    ->standardDeck('Equanimity\'s Ashes')
    ->readyFor('Equanimity\'s Ashes')
    ->seed('myHand', 'Equanimity\'s Ashes')
    ->castSpell('Equanimity\'s Ashes', pay: 5, target: 'theirField-0')
    ->expectDamage('p2Field-0', 3, 'Without the Class Bonus: 3 damage')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting'], 'Deal 3 damage to target champion.')
    ->register();

gaScenario('equanimitys-ashes-class-bonus-deals-four-more-to-a-lightly-damaged-champion')
    ->standardDeck('Equanimity\'s Ashes')
    ->readyFor('Equanimity\'s Ashes', classBonus: true)
    ->seed('myHand', 'Equanimity\'s Ashes')
    ->castSpell('Equanimity\'s Ashes', pay: 5, target: 'theirField-0')
    ->expectDamage('p2Field-0', 7, '[Class Bonus] The champion had 0 damage (five or less), so 3 + 4 = 7')
    ->expectDqEmpty()
    ->covers(['damage', 'class-bonus', 'condition'], '[Class Bonus] Then if that champion has five or less damage counters on them, deal an additional 4 damage to them.')
    ->register();

gaScenario('equanimitys-ashes-level-three-costs-three-less')
    ->standardDeck('Equanimity\'s Ashes')
    ->readyFor('Equanimity\'s Ashes', level: 3)
    ->seed('myHand', 'Equanimity\'s Ashes')
    ->castSpell('Equanimity\'s Ashes', pay: 2, target: 'theirField-0')
    ->expectZoneCount('myMemory', 2, 1, '[Level 3+] The 5 reserve cost took only 2 payments')
    ->expectDamage('p2Field-0', 3, 'The discounted spell deals its 3 damage')
    ->expectDqEmpty()
    ->covers(['cost-modification', 'level-gate'], '[Level 3+] This card costs 3 less to activate.')
    ->register();

// --- Pyroclastic Flow / Ignite Fate: damage to every unit / each champion ---
gaScenario('pyroclastic-flow-deals-two-damage-to-all-units')
    ->standardDeck('Pyroclastic Flow')
    ->readyFor('Pyroclastic Flow')
    ->seed('myField', 'Dungeon Guide')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Pyroclastic Flow')
    ->castSpell('Pyroclastic Flow', pay: 4)
    ->expectDamage('p1Field-0', 2, 'Your own champion takes 2 too')
    ->expectDamage('p2Field-0', 2, 'The opposing champion takes 2')
    ->expectDamage('p1Field-1', 2, 'Your Dungeon Guide takes 2')
    ->expectDamage('p2Field-1', 2, 'The opposing Dungeon Guide takes 2')
    ->expectDqEmpty()
    ->covers(['damage', 'sweep'], 'Deal 2 damage to all units.')
    ->register();

gaScenario('ignite-fate-deals-two-damage-to-each-champion')
    ->standardDeck('Ignite Fate')
    ->readyFor('Ignite Fate')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Ignite Fate')
    ->castSpell('Ignite Fate', pay: 3)
    ->expectDamage('p1Field-0', 2, 'Your champion takes 2')
    ->expectDamage('p2Field-0', 2, 'The opposing champion takes 2')
    ->expectDamage('p1Field-1', 0, 'Allies are not hit')
    ->expectDqEmpty()
    ->covers(['damage', 'sweep'], 'Deal 2 damage to each champion.')
    ->register();

// --- Gilded Pyre: 2 damage; 4 instead if the unit has a buff counter ---
gaScenario('gilded-pyre-deals-two-damage')
    ->standardDeck('Gilded Pyre')
    ->readyFor('Gilded Pyre')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Gilded Pyre')
    ->castSpell('Gilded Pyre', pay: 2, target: 'theirField-1', window: 'both')
    ->expectDamage('p2Field-1', 2, 'No buff counter: 2 damage')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting'], 'Deal 2 damage to target unit.')
    ->register();

gaScenario('gilded-pyre-deals-four-damage-to-a-unit-with-a-buff-counter')
    ->standardDeck('Gilded Pyre')
    ->readyFor('Gilded Pyre')
    ->seed('theirField', 'Dungeon Guide', properties: ['Counters' => ['buff' => 1]])
    ->seed('myHand', 'Gilded Pyre')
    ->castSpell('Gilded Pyre', pay: 2, target: 'theirField-1', window: 'both')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'A buff counter makes it 4 damage: the buffed Dungeon Guide (4 life) is destroyed')
    ->expectDqEmpty()
    ->covers(['damage', 'condition', 'counter'], 'If that unit has a buff counter on it, deal 4 damage to it instead.')
    ->register();

// --- Smoke Out: 1 damage; 4 instead if the unit has stealth ---
gaScenario('smoke-out-deals-one-damage')
    ->standardDeck('Smoke Out')
    ->readyFor('Smoke Out')
    ->seed('myHand', 'Smoke Out')
    ->castSpell('Smoke Out', pay: 1, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 1, 'No stealth: 1 damage')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting'], 'Deal 1 damage to target unit.')
    ->register();

gaScenario('smoke-out-deals-four-damage-to-a-unit-with-stealth')
    ->standardDeck('Smoke Out')
    ->readyFor('Smoke Out')
    ->seed('theirField', 'Gildas, Faesworn Monarch')
    ->seed('myHand', 'Smoke Out')
    ->castSpell('Smoke Out', pay: 1, target: 'theirField-1', window: 'both')
    ->expectProperty('p2Graveyard-0', 'CardID', 'g99PIuhU0O', 'Stealth: 4 damage instead of 1 destroyed the 3-life Gildas (1 damage would not have)')
    ->expectDqEmpty()
    ->covers(['damage', 'condition', 'stealth'], 'If that unit has stealth, deal 4 damage to it instead.')
    ->register();

// --- Fiery Interference: 2 damage; a champion's controller can't recover this turn ---
gaScenario('fiery-interference-stops-a-damaged-champion-recovering')
    ->standardDeck('Fiery Interference')
    ->readyFor('Fiery Interference')
    ->seed('myHand', 'Fiery Interference')
    ->castSpell('Fiery Interference', pay: 2, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 2, 'The champion took 2 damage')
    ->expectProperty('p2GlobalEffects-0', 'CardID', 'CANT_RECOVER', 'Its controller can\'t recover until end of turn')
    ->expectDqEmpty()
    ->covers(['damage', 'condition', 'recover'], 'If that unit is a champion, its controller can\'t recover until end of turn.')
    ->register();

gaScenario('fiery-interference-on-an-ally-does-not-stop-recovering')
    ->standardDeck('Fiery Interference')
    ->readyFor('Fiery Interference')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Fiery Interference')
    ->castSpell('Fiery Interference', pay: 2, target: 'theirField-1', window: 'both')
    ->expectDamage('p2Field-1', 2, 'The ally took 2 damage')
    ->expectZoneCount('theirGlobalEffects', 0, 1, 'No can\'t-recover effect is applied to an ally\'s controller')
    ->expectDqEmpty()
    ->covers(['damage', 'condition'], 'If that unit is a champion, its controller can\'t recover until end of turn.')
    ->register();

// --- Excoriate: destroy target ally with reserve cost 4 or less ---
gaScenario('excoriate-destroys-an-ally-with-reserve-cost-four-or-less')
    ->standardDeck('Excoriate')
    ->readyFor('Excoriate')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Excoriate')
    ->castSpell('Excoriate', pay: 4, target: 'theirField-1')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The Dungeon Guide (reserve cost 3) was destroyed')
    ->expectDqEmpty()
    ->covers(['destroy', 'targeting', 'cost-filter'], 'Destroy target ally with reserve cost 4 or less.')
    ->register();

gaScenario('excoriate-level-two-costs-one-less')
    ->standardDeck('Excoriate')
    ->readyFor('Excoriate', level: 2)
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Excoriate')
    ->castSpell('Excoriate', pay: 3, target: 'theirField-1')
    ->expectZoneCount('myMemory', 3, 1, '[Level 2+] The 4 reserve cost took 3 payments')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The ally was destroyed')
    ->expectDqEmpty()
    ->covers(['cost-modification', 'level-gate'], '[Level 2+] This card costs 1 less to activate.')
    ->register();

// --- Lavastorm: destroy all allies (Exalted: needs another advanced element) ---
gaScenario('lavastorm-destroys-all-allies')
    ->standardDeck('Lavastorm')
    ->readyFor('Lavastorm')
    ->seed('myField', 'Dungeon Guide')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Lavastorm')
    ->castSpell('Lavastorm', pay: 4)
    ->expectProperty('p1Graveyard-1', 'CardID', 'em6eEh9q8y', 'Your ally was destroyed too (after Lavastorm itself in the graveyard)')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The opposing ally was destroyed')
    ->expectProperty('p1Graveyard-0', 'CardID', '7K9pWqEc20', 'Lavastorm resolved into the graveyard')
    ->expectZoneCount('myField', 1, 1, 'Only your champion remains')
    ->expectZoneCount('theirField', 1, 1, 'Only the opposing champion remains')
    ->expectDqEmpty()
    ->covers(['destroy', 'sweep'], 'Destroy all allies.')
    ->register();
