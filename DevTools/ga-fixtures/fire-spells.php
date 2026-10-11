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

// =====================================================================================================================
// Batch 2
// =====================================================================================================================

// --- Cinder Geyser: 4 damage to target unit; [Class Bonus] costs 2 less while an opponent has four or more cards in memory ---
gaScenario('cinder-geyser-deals-four-damage')
    ->standardDeck('Cinder Geyser')
    ->readyFor('Cinder Geyser')
    ->seed('myHand', 'Cinder Geyser')
    ->castSpell('Cinder Geyser', pay: 4, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 4, '4 damage to the target unit')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting'], 'Deal 4 damage to target unit.')
    ->register();

gaScenario('cinder-geyser-class-bonus-costs-two-less-with-four-opposing-memory-cards')
    ->standardDeck('Cinder Geyser')
    ->readyFor('Cinder Geyser', classBonus: true)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Cinder Geyser')
    ->castSpell('Cinder Geyser', pay: 2, target: 'theirField-0', window: 'both')
    ->expectZoneCount('myMemory', 2, 1, '[Class Bonus] With four cards in the opponent\'s memory only 2 reserve were paid')
    ->expectDamage('p2Field-0', 4, 'The discounted spell deals its 4 damage')
    ->expectDqEmpty()
    ->covers(['cost-modification', 'class-bonus', 'condition'], '[Class Bonus] As long as an opponent has four or more cards in their memory, this card costs 2 less to activate.')
    ->register();

gaScenario('cinder-geyser-class-bonus-with-three-opposing-memory-cards-pays-full')
    ->standardDeck('Cinder Geyser')
    ->readyFor('Cinder Geyser', classBonus: true)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Cinder Geyser')
    ->castSpell('Cinder Geyser', pay: 4, target: 'theirField-0', window: 'both')
    ->expectZoneCount('myMemory', 4, 1, 'Three cards in the opponent\'s memory is below the threshold: the full 4 reserve is paid')
    ->expectDamage('p2Field-0', 4, '4 damage')
    ->expectDqEmpty()
    ->covers(['cost-modification', 'class-bonus', 'condition'], '[Class Bonus] As long as an opponent has four or more cards in their memory, this card costs 2 less to activate.')
    ->register();

// --- Demolition: 3 damage to target unit or Siegeable domain; [Class Bonus] costs 1 less ---
gaScenario('demolition-deals-three-damage-to-a-unit')
    ->standardDeck('Demolition')
    ->readyFor('Demolition')
    ->seed('myHand', 'Demolition')
    ->castSpell('Demolition', pay: 3, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 3, '3 damage to the unit')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting'], 'Deal 3 damage to target unit or Siegeable domain.')
    ->register();

gaScenario('demolition-class-bonus-costs-one-less')
    ->standardDeck('Demolition')
    ->readyFor('Demolition', classBonus: true)
    ->seed('myHand', 'Demolition')
    ->castSpell('Demolition', pay: 2, target: 'theirField-0', window: 'both')
    ->expectZoneCount('myMemory', 2, 1, '[Class Bonus] 2 reserve instead of 3')
    ->expectDamage('p2Field-0', 3, 'Still 3 damage')
    ->expectDqEmpty()
    ->covers(['cost-modification', 'class-bonus'], '[Class Bonus] This card costs 1 less to activate.')
    ->register();

// --- Embercrypt Burn: banish target card from a graveyard, then 2 damage to each champion ---
gaScenario('embercrypt-burn-banishes-a-graveyard-card-and-damages-each-champion')
    ->standardDeck('Embercrypt Burn')
    ->readyFor('Embercrypt Burn')
    ->seed('theirGraveyard', 'Dungeon Guide')
    ->seed('myHand', 'Embercrypt Burn')
    ->castSpell('Embercrypt Burn', pay: 2, target: 'theirGraveyard-0', window: 'both')
    ->expectZoneCount('theirGraveyard', 0, 1, 'The targeted graveyard card left the graveyard')
    ->expectZoneCount('theirBanish', 1, 1, 'It was banished')
    ->expectDamage('p1Field-0', 2, 'Your champion takes 2')
    ->expectDamage('p2Field-0', 2, 'The opposing champion takes 2')
    ->expectDqEmpty()
    ->covers(['banish', 'damage', 'targeting'], 'Banish target card from a graveyard. Then deal 2 damage to each champion.')
    ->register();

// --- Searing Truth: 2 damage to target unit; [Guo Jia Bonus] quest counter on your champion ---
gaScenario('searing-truth-deals-two-damage')
    ->standardDeck('Searing Truth')
    ->readyFor('Searing Truth')
    ->seed('myHand', 'Searing Truth')
    ->castSpell('Searing Truth', pay: 2, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 2, '2 damage')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'No quest counter without Guo Jia')
    ->expectDqEmpty()
    ->covers(['damage', 'targeting'], 'Deal 2 damage to target unit.')
    ->register();

gaScenario('searing-truth-guo-jia-bonus-puts-a-quest-counter-on-the-champion')
    ->standardDeck('Searing Truth')
    ->champion(cardName: 'Guo Jia, Chosen Disciple', lineage: ['Spirit of Fire'])
    ->seed('myHand', 'Searing Truth')
    ->castSpell('Searing Truth', pay: 2, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 2, '2 damage')
    ->expectProperty('p1Field-0', 'Counters', '{"quest":1}', '[Guo Jia Bonus] A quest counter was put on the champion')
    ->expectDqEmpty()
    ->covers(['damage', 'counter', 'champion-bonus'], '[Guo Jia Bonus] Put a quest counter on your champion.')
    ->register();

// --- Distant: Flickering Cinder, Singeing Leap ---
gaScenario('flickering-cinder-deals-one-damage-and-makes-your-champion-distant')
    ->standardDeck('Flickering Cinder')
    ->readyFor('Flickering Cinder')
    ->seed('myHand', 'Flickering Cinder')
    ->castSpell('Flickering Cinder', pay: 1, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 1, '1 damage to the target unit')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","DISTANT"]', 'Your champion became distant')
    ->expectDqEmpty()
    ->covers(['damage', 'distant'], 'Deal 1 damage to target unit. Your champion becomes distant.')
    ->register();

gaScenario('singeing-leap-damages-your-champion-and-makes-it-distant')
    ->standardDeck('Singeing Leap')
    ->readyFor('Singeing Leap')
    ->seed('myHand', 'Singeing Leap')
    ->castSpell('Singeing Leap', pay: 1, window: 'both')
    ->expectDamage('p1Field-0', 1, 'Your champion took 1 damage')
    ->expectProperty('p1Field-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN","DISTANT","vz4kc558yx-ACTION_ACTIVATED"]', 'Then it became distant (the damage is recorded too)')
    ->expectDqEmpty()
    ->covers(['damage', 'distant'], 'Deal 1 damage to your champion. Then your champion becomes distant.')
    ->register();

gaScenario('infernal-manastreak-deals-two-damage')
    ->standardDeck('Infernal Manastreak')
    ->readyFor('Infernal Manastreak')
    ->seed('myHand', 'Infernal Manastreak')
    ->castSpell('Infernal Manastreak', pay: 2, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 2, 'Not distant: 2 damage')
    ->expectDqEmpty()
    ->covers(['damage', 'condition', 'distant'], 'Deal 2 damage to target unit. If your champion is distant, deal 3 damage to that unit instead.')
    ->register();

gaScenario('infernal-manastreak-deals-three-damage-while-distant')
    ->standardDeck('Infernal Manastreak')
    ->readyFor('Infernal Manastreak')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->seed('myHand', 'Infernal Manastreak')
    ->castSpell('Infernal Manastreak', pay: 2, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 3, 'Your champion is distant: 3 damage instead')
    ->expectDqEmpty()
    ->covers(['damage', 'condition', 'distant'], 'If your champion is distant, deal 3 damage to that unit instead.')
    ->register();

gaScenario('manaflare-barrage-deals-two-damage-to-all-other-units')
    ->standardDeck('Manaflare Barrage')
    ->readyFor('Manaflare Barrage')
    ->seed('myField', 'Dungeon Guide')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Manaflare Barrage')
    ->castSpell('Manaflare Barrage', pay: 5, window: 'both')
    ->expectDamage('p1Field-0', 0, 'Your champion is excluded')
    ->expectDamage('p2Field-0', 2, 'The opposing champion takes 2')
    ->expectDamage('p1Field-1', 2, 'Your ally takes 2')
    ->expectDamage('p2Field-1', 2, 'The opposing ally takes 2')
    ->expectDqEmpty()
    ->covers(['damage', 'sweep', 'condition'], 'Deal 2 damage to all units except for your champion.')
    ->register();

gaScenario('manaflare-barrage-deals-three-damage-while-distant')
    ->standardDeck('Manaflare Barrage')
    ->readyFor('Manaflare Barrage')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Manaflare Barrage')
    ->castSpell('Manaflare Barrage', pay: 5, window: 'both')
    ->expectDamage('p2Field-0', 3, 'Distant: the opposing champion takes 3')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The 3-life Dungeon Guide takes 3 and dies')
    ->expectDqEmpty()
    ->covers(['damage', 'sweep', 'condition', 'distant'], 'If your champion is distant, deal 3 damage to those units instead.')
    ->register();

// --- Weapons: Hone by Fire, Temper in Flames ---
gaScenario('hone-by-fire-gives-a-sword-plus-two-power')
    ->standardDeck('Hone by Fire')
    ->readyFor('Hone by Fire')
    ->seed('myField', 'Warrior\'s Longsword')
    ->seed('myHand', 'Hone by Fire')
    ->castSpell('Hone by Fire', pay: 2, target: 'myField-1', window: 'both')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'Without the Class Bonus no durability counter is added')
    ->expectDqEmpty()
    ->covers(['stat-change', 'targeting'], 'Target Sword weapon you control gets +2 [POWER] until end of turn.')
    ->register();

gaScenario('hone-by-fire-class-bonus-adds-a-durability-counter')
    ->standardDeck('Hone by Fire')
    ->readyFor('Hone by Fire', classBonus: true)
    ->seed('myField', 'Warrior\'s Longsword')
    ->seed('myHand', 'Hone by Fire')
    ->castSpell('Hone by Fire', pay: 2, target: 'myField-1', window: 'both')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', '[Class Bonus] A durability counter was put on the weapon (2 -> 3)')
    ->expectDqEmpty()
    ->covers(['counter', 'class-bonus'], 'Class Bonus: Put a durability counter on it.')
    ->register();

gaScenario('temper-in-flames-puts-a-durability-counter-on-a-weapon')
    ->standardDeck('Temper in Flames')
    ->readyFor('Temper in Flames')
    ->seed('myField', 'Warrior\'s Longsword')
    ->seed('myHand', 'Temper in Flames')
    ->castSpell('Temper in Flames', pay: 1, target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'A durability counter was put on the weapon (2 -> 3)')
    ->expectDqEmpty()
    ->covers(['counter', 'stat-change', 'targeting'], 'Target weapon gets +1 [POWER] until end of turn. Put a durability counter on it.')
    ->register();

// --- Sacrifice-cost damage spells ---
gaScenario('blazing-throw-sacrifices-a-weapon-and-deals-four-damage')
    ->standardDeck('Blazing Throw')
    ->readyFor('Blazing Throw')
    ->seed('myField', 'Warrior\'s Longsword')
    ->seed('myHand', 'Blazing Throw')
    ->play('Blazing Throw')->target('myField-1')->pay(1)->target('theirField-0')
    ->expectDamage('p2Field-0', 4, '4 damage to the target unit')
    ->expectZoneCount('myField', 1, 1, 'The weapon was sacrificed as the additional cost')
    ->expectDqEmpty()
    ->covers(['damage', 'additional-cost-sacrifice', 'targeting'], 'As an additional cost to activate this card, sacrifice a weapon.', 'Deal 4 damage to target unit.')
    ->register();

gaScenario('fling-food-sacrifices-a-food-and-deals-four-damage')
    ->standardDeck('Fling Food')
    ->readyFor('Fling Food')
    ->seed('myField', 'Forest Cake')
    ->seed('myHand', 'Fling Food')
    ->play('Fling Food')->target('myField-1')->pay(1)->target('theirField-0')
    ->expectDamage('p2Field-0', 4, '4 damage to the target unit')
    ->expectZoneCount('myField', 1, 1, 'The Food item was sacrificed as the additional cost')
    ->expectDqEmpty()
    ->notes('Regression: the Food sacrifice printed as an additional cost was never paid, so Fling Food dealt its 4 damage for free and kept the item.')
    ->covers(['damage', 'additional-cost-sacrifice', 'targeting'], 'As an additional cost to activate this card, sacrifice a Food item.', 'Deal 4 damage to target unit.')
    ->register();

gaScenario('shield-fragmentation-sacrifices-a-shield-and-deals-four-damage')
    ->standardDeck('Shield Fragmentation')
    ->readyFor('Shield Fragmentation')
    ->seed('myField', 'Prototype Shield')
    ->seed('myHand', 'Shield Fragmentation')
    ->play('Shield Fragmentation')->target('myField-1')->pay(2)->target('theirField-0')
    ->expectDamage('p2Field-0', 4, '4 damage to the target unit')
    ->expectZoneCount('myField', 1, 1, 'The Shield was sacrificed as the additional cost')
    ->expectDqEmpty()
    ->notes('Regression: the Shield sacrifice printed as an additional cost was never paid, so Shield Fragmentation dealt its 4 damage for free and kept the Shield.')
    ->covers(['damage', 'additional-cost-sacrifice', 'targeting'], 'As an additional cost to activate this card, sacrifice a Shield.', 'Deal 4 damage to target unit.')
    ->register();

gaScenario('molten-impact-deals-two-plus-the-sacrificed-weapons-durability')
    ->standardDeck('Molten Impact')
    ->readyFor('Molten Impact')
    ->seed('myField', 'Warrior\'s Longsword')
    ->seed('myHand', 'Molten Impact')
    ->play('Molten Impact')->pay(2)->target('myField-1')->target('theirField-0')
    ->expectDamage('p2Field-0', 4, '2 + X where X = the 2 durability counters on the sacrificed Longsword')
    ->expectZoneCount('myField', 1, 1, 'The weapon was sacrificed')
    ->expectDqEmpty()
    ->covers(['damage', 'additional-cost-sacrifice', 'scaling'], 'Deal 2+X damage to target unit, where X is the amount of durability counters that was on the sacrificed weapon.')
    ->register();

// --- Other single effects ---
gaScenario('spark-link-each-player-draws-a-card-into-memory')
    ->standardDeck('Spark Link')
    ->readyFor('Spark Link')
    ->seed('myHand', 'Spark Link')
    ->castSpell('Spark Link', pay: 2, window: 'both')
    ->expectZoneCount('myMemory', 3, 1, 'You drew a card into memory (2 reserve paid + 1 drawn)')
    ->expectZoneCount('theirMemory', 1, 1, 'The opponent drew a card into their memory')
    ->expectDqEmpty()
    ->covers(['draw-discard', 'zone-movement'], 'Each player draws a card into their memory.')
    ->register();

gaScenario('ordained-charisma-gains-control-of-a-weak-ally')
    ->standardDeck('Ordained Charisma')
    ->readyFor('Ordained Charisma')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Ordained Charisma')
    ->castSpell('Ordained Charisma', pay: 3, target: 'theirField-1')
    ->expectProperty('p2Field-1', 'Controller', '1', 'The ally with 1 POWER is now controlled by player 1')
    ->expectDqEmpty()
    ->covers(['gain-control', 'targeting', 'condition'], 'Gain control of target ally with 2 [POWER] or less until end of turn. Wake up that ally.')
    ->register();

gaScenario('crumbling-reign-destroys-an-item-or-weapon')
    ->standardDeck('Crumbling Reign')
    ->readyFor('Crumbling Reign')
    ->seed('theirField', 'Warrior\'s Longsword')
    ->seed('myHand', 'Crumbling Reign')
    ->castSpell('Crumbling Reign', pay: 2, target: 'theirField-1')
    ->expectProperty('p2Banish-0', 'CardID', 'jF1VuIR7a6', 'The weapon was destroyed (a destroyed regalia is banished instead of going to the graveyard)')
    ->expectZoneCount('theirField', 1, 1, 'Only the opposing champion remains')
    ->expectDqEmpty()
    ->covers(['destroy', 'targeting'], 'Destroy target item or weapon.')
    ->register();

gaScenario('dire-requiem-summons-a-direwolf')
    ->standardDeck('Dire Requiem')
    ->readyFor('Dire Requiem')
    ->seed('myHand', 'Dire Requiem')
    ->castSpell('Dire Requiem', pay: 2)
    ->expectZoneCount('myField', 2, 1, 'A Direwolf token was summoned (champion + token)')
    ->expectDqEmpty()
    ->covers(['summon', 'token'], 'Summon a Direwolf token.')
    ->register();

gaScenario('shout-at-your-pets-gives-level-with-an-animal')
    ->standardDeck('Shout at Your Pets')
    ->readyFor('Shout at Your Pets')
    ->seed('myField', 'Baby Gray Slime')
    ->seed('myHand', 'Shout at Your Pets')
    ->castSpell('Shout at Your Pets', pay: 1, window: 'both')
    ->expectProperty('p1Field-0', 'TurnEffects', '["gvXQa57cxe","vz4kc558yx-ACTION_ACTIVATED"]', 'With an Animal ally the champion gets +1 level until end of turn (the card\'s own effect tag)')
    ->expectDqEmpty()
    ->covers(['stat-change', 'condition'], 'If you control an Animal or Beast ally, your champion gets +1 level until end of turn.')
    ->register();

gaScenario('fling-food-is-refused-without-a-food-item')
    ->standardDeck('Fling Food')
    ->readyFor('Fling Food')
    ->seed('myHand', 'Fling Food')
    ->play('Fling Food')
    ->tested('Fling Food')
    ->expectZoneCount('myHand', 8, 1, 'Fling Food stays in hand: it cannot be activated without a Food item to sacrifice')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->expectDqEmpty()
    ->covers(['additional-cost-sacrifice', 'cost-validation'], 'As an additional cost to activate this card, sacrifice a Food item.')
    ->register();
