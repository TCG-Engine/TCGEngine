<?php
/**
 * Phase 3c (Wind), the cards left over after the main wind files: Greater Boon of Astraeus, Veiled Dash, Stifling Aethercharge, Legion's Wingspan.
 */

// ---- Greater Boon of Astraeus ----

gaScenario('greater-boon-of-astraeus-costs-one-less-per-wind-lineage-card-and-draws-for-suppresses-this-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Astraeus', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Rearing Rebound')
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Greater Boon of Astraeus')
    ->castSpell('Rearing Rebound', pay: 3, window: 'both', target: 'myField-1')
    ->castSpell('Greater Boon of Astraeus', pay: 5)
    ->expectZoneCount('myMemory', 8, 1, 'Three for Rearing Rebound, five (6 - 1 for the Spirit of Wind in the lineage) for the boon')
    ->expectZoneCount('myHand', 3, 1, 'Seven in hand plus five seeds - two spells - eight paid + one drawn for the one suppress')
    ->tested('Greater Boon of Astraeus')
    ->covers(['cost-reduction', 'draw-discard', 'on-gain'], 'This boon costs **X**+**Y** less to bestow, where **X** is the amount of wind element boons in your pantheon, and **Y** is the amount of wind element cards in your champion’s lineage.', 'As you gain this boon, draw a card for each of up to two times you\'ve **suppressed** an object this turn.')
    ->register();

gaScenario('greater-boon-of-astraeus-draws-nothing-when-no-object-was-suppressed-this-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Astraeus', classBonus: true)
    ->seed('myHand', 'Greater Boon of Astraeus')
    ->castSpell('Greater Boon of Astraeus', pay: 5)
    ->expectZoneCount('myMemory', 5, 1, 'Six minus one for the Spirit of Wind in the lineage')
    ->expectZoneCount('myHand', 2, 1, 'Eight in hand - the boon - five paid, nothing drawn')
    ->tested('Greater Boon of Astraeus')
    ->covers(['draw-discard', 'condition'], 'As you gain this boon, draw a card for each of up to two times you\'ve **suppressed** an object this turn.')
    ->register();

// ---- Veiled Dash ----
// Reveal selections are explicit; prevention equals the selected Wind cards.

gaScenario('veiled-dash-makes-two-units-distant-and-prevents-damage-per-wind-card-in-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Veiled Dash', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myMemory', 'Young Peacekeeper')
    ->seed('myMemory', 'Young Peacekeeper')
    ->seed('myHand', 'Veiled Dash')
    ->play('Veiled Dash')->pay(2)->answer(1, 'myField-0&myField-1')->answer(1, 'myMemory-0&myMemory-1')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","DISTANT","PREVENT_ALL_2"]', 'The champion became distant and has two damage prevented')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT","PREVENT_ALL_2"]', 'So did the second target')
    ->tested('Veiled Dash')
    ->covers(['distant', 'damage-prevention', 'reveal', 'class-bonus'], 'Up to two target units become **distant**, then reveal any amount of wind element cards from your memory. Prevent the next **X** damage that would be dealt to each of those units this turn where **X** is the amount of wind element cards revealed this way.', '[Class Bonus] This card costs 2 less to activate.')
    ->register();

// ---- Legion's Wingspan ----

gaScenario('legions-wingspan-diana-bonus-on-attack-gives-an-ally-plus-one-power-per-wind-card-in-the-intent')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Cursebreaker', lineage: ['Spirit of Wind'], classes: 'RANGER')
    ->seed('myField', 'Legion\'s Wingspan', properties: ['Subcards' => [GaScenario::id('Stifling Aethercharge')]])
    ->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->answer(1, 'myField-2')
    ->expectPower('p1Field-2', 2, 'Dungeon Guide: 1 POWER plus 1 for the wind Aethercharge loaded into the Wingspan')
    ->tested('Legion\'s Wingspan')
    ->covers(['on-attack', 'diana-bonus', 'stat-change'], '[Diana Bonus] **On Attack**: For each wind element card in the attacker\'s intent, choose an ally you control. That ally gets +1[POWER] until end of turn.')
    ->register();

gaScenario('legions-wingspan-without-the-diana-bonus-gives-no-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'], classes: 'RANGER')
    ->seed('myField', 'Legion\'s Wingspan', properties: ['Subcards' => [GaScenario::id('Stifling Aethercharge')]])
    ->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')
    ->expectPower('p1Field-2', 1, 'No Diana Bonus: the Dungeon Guide keeps its 1 POWER')
    ->expectDqEmpty()
    ->tested('Legion\'s Wingspan')
    ->covers(['on-attack', 'diana-bonus', 'condition'], '[Diana Bonus] **On Attack**')
    ->register();

// ---- Stifling Aethercharge ----
// "Negate all on enter triggers from target ally" cannot be exercised: an ally's On Enter trigger is put on the effects stack and resolved in the same step, so no state offers a moment to
// respond to it (see ENGINE-FINDINGS.md). The load clause is covered here.

gaScenario('stifling-aethercharge-targets-an-ally-and-may-load-itself-into-an-aetherwing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'], classes: 'RANGER')
    ->seed('myField', 'Legion\'s Wingspan')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Stifling Aethercharge')
    ->play('Stifling Aethercharge')->pay(2)->target('theirField-1')->answer(1, 'YES')
    ->expectProperty('p1Field-1', 'Subcards', '["bYrLVjKSCL"]', 'Stifling Aethercharge is loaded into the Aetherwing')
    ->tested('Stifling Aethercharge')
    ->covers(['target', 'load'], 'You may load CARDNAME into an Aetherwing weapon you control.')
    ->register();
