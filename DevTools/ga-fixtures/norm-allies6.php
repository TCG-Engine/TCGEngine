<?php
/**
 * Phase 3d (Norm): allies, sixth batch (foster, pride and counter allies). 'quiet' deck.
 */

// ---- Imperial Panzer ----

gaScenario('imperial-panzer-foster-becomes-fostered-at-the-recollection-phase')
    ->deckTemplate('quiet', 'Imperial Panzer')
    ->readyFor('Imperial Panzer')
    ->seed('myField', 'Imperial Panzer')
    ->reachTurnThree()
    ->expectProperty('p1Field-1', 'TurnEffects', '["FOSTERED"]', 'Not dealt damage since the end of the previous turn: it became fostered')
    ->tested('Imperial Panzer')
    ->covers(['keyword', 'foster'], '**Foster** *(At the beginning of your recollection phase, if this ally hasn’t been dealt damage since the end of your previous turn, it becomes **fostered**.)*')
    ->register();

gaScenario('imperial-panzer-class-bonus-fostered-gets-plus-one-power-plus-two-life-and-vigor')
    ->deckTemplate('quiet', 'Imperial Panzer')
    ->readyFor('Imperial Panzer', classBonus: true)
    ->seed('myField', 'Imperial Panzer', properties: ['TurnEffects' => ['FOSTERED'], 'Status' => 1])
    ->observe()
    ->expectPower('p1Field-1', 3, '[Class Bonus] Fostered: 2 + 1 power')
    ->expectLife('p1Field-1', 4, '[Class Bonus] Fostered: 2 + 2 life')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', '[Class Bonus] Fostered: vigor woke it at the end phase')
    ->tested('Imperial Panzer')
    ->covers(['class-bonus', 'foster', 'stat-change', 'vigor'], '[Class Bonus] As long as CARDNAME is **fostered**, it gets +1 [POWER] and +2 [LIFE], and has **vigor**.')
    ->register();

gaScenario('imperial-panzer-without-the-class-bonus-fostered-has-its-printed-stats')
    ->deckTemplate('quiet', 'Imperial Panzer')
    ->readyFor('Imperial Panzer')
    ->seed('myField', 'Imperial Panzer', properties: ['TurnEffects' => ['FOSTERED']])
    ->observe()
    ->expectPower('p1Field-1', 2, 'No class bonus: the printed 2 power')
    ->expectLife('p1Field-1', 2, 'No class bonus: the printed 2 life')
    ->tested('Imperial Panzer')
    ->covers(['class-bonus', 'foster', 'condition'], '[Class Bonus] As long as CARDNAME is **fostered**, it gets +1 [POWER] and +2 [LIFE], and has **vigor**.')
    ->register();

// ---- City Protector ----

gaScenario('city-protector-on-foster-summons-an-automaton-drone')
    ->deckTemplate('quiet', 'City Protector')
    ->readyFor('City Protector')
    ->seed('myField', 'City Protector')
    ->reachTurnThree()
    ->expectCardExists('myField', 'Automaton Drone', 'On Foster: an Automaton Drone token was summoned')
    ->tested('City Protector')
    ->covers(['keyword', 'foster', 'on-foster', 'summon'], '**On Foster:** **Summon** an Automaton Drone token.')
    ->register();

// ---- Seasoned Shieldmaster ----

gaScenario('seasoned-shieldmaster-an-ally-becoming-fostered-draws-into-memory')
    ->deckTemplate('quiet', 'Seasoned Shieldmaster')
    ->readyFor('Seasoned Shieldmaster')
    ->seed('myField', 'Seasoned Shieldmaster')
    ->seed('myField', 'Imperial Panzer')
    ->reachTurnThree()
    ->expectZoneCount('myHand', 9, 1, 'The Panzer became fostered (the Shieldmaster has no foster): one card drawn into memory, returned to hand by the recollection phase, plus the 7 and the turn draw')
    ->tested('Seasoned Shieldmaster')
    ->covers(['trigger', 'foster', 'draw-into-memory'], 'Whenever an ally you control becomes **fostered**, draw a card into your memory.')
    ->register();

gaScenario('seasoned-shieldmaster-class-bonus-fostered-allies-get-plus-one-power-and-life')
    ->deckTemplate('quiet', 'Seasoned Shieldmaster')
    ->readyFor('Seasoned Shieldmaster', classBonus: true)
    ->seed('myField', 'Seasoned Shieldmaster')
    ->seed('myField', 'Dungeon Guide', properties: ['TurnEffects' => ['FOSTERED']])
    ->seed('myField', 'Dungeon Guide')
    ->observe()
    ->expectPower('p1Field-2', 2, '[Class Bonus] The fostered Guide: 1 + 1 power')
    ->expectLife('p1Field-2', 4, '[Class Bonus] The fostered Guide: 3 + 1 life')
    ->expectPower('p1Field-3', 1, 'The unfostered Guide is unchanged')
    ->tested('Seasoned Shieldmaster')
    ->covers(['class-bonus', 'foster', 'stat-change'], '[Class Bonus] **Fostered** allies you control get +1 [POWER] and +1 [LIFE].')
    ->register();

// ---- Accompanying Guard ----

gaScenario('accompanying-guard-class-bonus-enter-makes-another-ally-fostered')
    ->deckTemplate('quiet', 'Accompanying Guard')
    ->readyFor('Accompanying Guard', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Accompanying Guard')
    ->play('Accompanying Guard')->pay(2)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","FOSTERED"]', '[Class Bonus] The target ally became fostered')
    ->tested('Accompanying Guard')
    ->covers(['class-bonus', 'on-enter', 'foster', 'targeting'], '[Class Bonus] **On Enter**: Another target ally you control becomes **fostered**.')
    ->register();

// ---- Apostle of the Woods ----

gaPrideCase('apostle-of-the-woods-pride-three-does-not-obey-a-level-two-champion', 'Apostle of the Woods', 'Apostle of the Woods', ['level' => 2],
    ['pride', 'keyword'], '**Pride 3**', false, 'Pride 3: a level 2 champion is not enough, the Apostle never attacked');

gaPrideCase('apostle-of-the-woods-pride-three-obeys-a-level-three-champion', 'Apostle of the Woods', 'Apostle of the Woods', ['level' => 3],
    ['pride', 'keyword', 'level-gate'], '**Pride 3**', true, 'Pride 3 is satisfied at level 3: the Apostle attacked and rested');

gaScenario('apostle-of-the-woods-class-bonus-enter-loses-pride-and-gets-a-buff-counter-against-three-units')
    ->deckTemplate('quiet', 'Apostle of the Woods')
    ->readyFor('Apostle of the Woods', classBonus: true)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Apostle of the Woods')
    ->play('Apostle of the Woods')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '[Class Bonus] The opponent controls three units (champion and two allies): a buff counter')
    ->tested('Apostle of the Woods')
    ->covers(['class-bonus', 'on-enter', 'pride', 'counter', 'condition'], '[Class Bonus] **On Enter: **CARDNAME loses **pride** until end of turn. Then if an opponent controls three or more units, put a **buff** counter on CARDNAME.')
    ->register();

gaScenario('apostle-of-the-woods-class-bonus-enter-against-two-units-gets-no-counter')
    ->deckTemplate('quiet', 'Apostle of the Woods')
    ->readyFor('Apostle of the Woods', classBonus: true)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Apostle of the Woods')
    ->play('Apostle of the Woods')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Only two opposing units (champion and one ally): no counter')
    ->tested('Apostle of the Woods')
    ->covers(['class-bonus', 'on-enter', 'condition'], 'Then if an opponent controls three or more units')
    ->register();

// ---- Machined Monstrosity ----

gaOnDeathCase('machined-monstrosity-class-bonus-on-death-summons-a-rested-powercell', 'Machined Monstrosity', 'Giant Tortoise', ['classBonus' => true], ['Counters' => ['buff' => 0]], ['Counters' => ['buff' => 3]])
    ->expectCardExists('myGraveyard', 'Machined Monstrosity', 'The Monstrosity died')
    ->expectCardExists('myField', 'Powercell', '[Class Bonus] On Death: a Powercell was summoned')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Powercell entered rested')
    ->tested('Machined Monstrosity')
    ->covers(['class-bonus', 'on-death', 'summon'], '[Class Bonus] **On Death:** **Summon** a Powercell token rested.')
    ->register();
