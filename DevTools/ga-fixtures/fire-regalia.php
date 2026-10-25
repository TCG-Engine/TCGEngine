<?php
/**
 * Phase 3a (Fire): weapons and regalia items. Weapons are seeded on the field (seeding a card with an On Enter ability runs it);
 * the champion attacks with them the same way warriors-longsword-class-bonus-power does.
 */

gaScenario('firetongue-class-bonus-on-attack-banishing-a-fire-card-adds-a-durability-counter')
    ->standardDeck('Firetongue')
    ->readyFor('Firetongue', classBonus: true)
    ->seed('myField', 'Firetongue')
    ->seed('myGraveyard', 'Red Slime')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The fire card was banished from the graveyard')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":1}', 'A durability counter went on Firetongue')
    ->tested('Firetongue')
    ->covers(['on-attack', 'banish', 'counter', 'class-bonus'], '[Class Bonus] On Attack: You may banish a fire element card from your graveyard. If you do, put a durability counter on CARDNAME.')
    ->register();

gaScenario('impact-hammer-class-bonus-power-and-the-attacking-unit-takes-three-damage')
    ->standardDeck('Impact Hammer')
    ->readyFor('Impact Hammer', classBonus: true)
    ->seed('myField', 'Impact Hammer')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->expectDamage('p1Field-0', 3, 'The attacking unit took 3 damage')
    ->expectDamage('p2Field-0', 3, '[Class Bonus] 2 + 1 = 3 damage to the defender')
    ->tested('Impact Hammer')
    ->covers(['static-ability', 'triggered-ability', 'damage', 'class-bonus'], 'Whenever a unit uses this weapon for an attack, deal 3 damage to it. [Class Bonus] CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('forgelight-blade-enter-deals-four-damage-to-your-champion-and-class-bonus-adds-power')
    ->standardDeck('Forgelight Blade')
    ->readyFor('Forgelight Blade', classBonus: true)
    ->seed('myField', 'Forgelight Blade')
    ->observe()
    ->expectDamage('p1Field-0', 4, 'On Enter: dealt 4 damage to your champion')
    ->expectPower('p1Field-1', 2, '[Class Bonus] The blade gets +1 POWER')
    ->tested('Forgelight Blade')
    ->covers(['on-enter', 'damage', 'static-ability', 'class-bonus'], 'On Enter: Deal 4 damage to your champion. [Class Bonus] CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('dragons-dawn-class-bonus-on-attack-discards-a-fire-card-for-power-and-damages-your-champion')
    ->standardDeck('Dragon\'s Dawn')
    ->readyFor('Dragon\'s Dawn', classBonus: true)
    ->seed('myField', 'Dragon\'s Dawn')
    ->seed('myHand', 'Red Slime')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->answer(1, 'YES')->answer(1, 'myHand-7')
    ->expectProperty('p1Graveyard-0', 'CardID', 'mttsvbgl6f', 'The fire card was discarded')
    ->expectDamage('p1Field-0', 2, 'Dragon\'s Dawn dealt 2 unpreventable damage to your champion')
    ->expectDamage('p2Field-0', 4, 'The attack got +2 POWER: 2 + 2 = 4 damage')
    ->expectZoneCount('myHand', 9, 1, 'Discarded one and drew one from On Champion Hit')
    ->tested('Dragon\'s Dawn')
    ->covers(['on-attack', 'discard', 'damage', 'stat-change', 'class-bonus'], '[Class Bonus] On Attack: You may discard a fire element card and have CARDNAME deal 2 unpreventable damage to your champion. If you do, this attack gets +2 [POWER] and gains "On Champion Hit: Draw a card."')
    ->register();

gaScenario('heatwave-generator-recollection-gives-an-ally-plus-one-power')
    ->standardDeck('Heatwave Generator')
    ->readyFor('Heatwave Generator')
    ->seed('myField', 'Heatwave Generator')
    ->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()
    ->answer(1, 'myField-2')
    ->expectPower('p1Field-2', 2, 'Recollection phase: the ally got +1 POWER until end of turn')
    ->tested('Heatwave Generator')
    ->covers(['recollection-phase', 'stat-change'], 'At the beginning of your recollection phase, target ally you control gets +1 [POWER] until end of turn.')
    ->register();

gaScenario('forgelight-scepter-odd-opponent-memory-deals-two-unpreventable-damage-at-their-end-phase')
    ->standardDeck('Forgelight Scepter')
    ->readyFor('Forgelight Scepter')
    ->seed('myField', 'Forgelight Scepter')
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->endTurn(2)
    ->expectDamage('p2Field-0', 2, 'Odd memory (1): 2 unpreventable damage to their champion')
    ->tested('Forgelight Scepter')
    ->covers(['end-phase', 'damage', 'condition'], 'At the beginning of each opponent\'s end phase, if that player has an odd amount of cards in their memory, deal 2 unpreventable damage to their champion.')
    ->register();

gaScenario('forgelight-scepter-even-opponent-memory-deals-no-damage-at-their-end-phase')
    ->standardDeck('Forgelight Scepter')
    ->readyFor('Forgelight Scepter')
    ->seed('myField', 'Forgelight Scepter')
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->endTurn(2)
    ->expectDamage('p2Field-0', 0, 'Even memory (2): no damage')
    ->tested('Forgelight Scepter')
    ->covers(['end-phase', 'damage', 'condition'], 'At the beginning of each opponent\'s end phase, if that player has an odd amount of cards in their memory, deal 2 unpreventable damage to their champion.')
    ->register();

gaScenario('everflame-staff-banish-with-three-refinement-counters-deals-four-damage-to-a-champion')
    ->standardDeck('Everflame Staff')
    ->readyFor('Everflame Staff', classBonus: true)
    ->seed('myField', 'Everflame Staff', properties: ['Counters' => ['refinement' => 3]])
    ->endTurn()->answer(1, 'myField-1@Activate-0@Banish')
    ->answer(1, 'theirField-0')
    ->expectDamage('p2Field-0', 4, 'Deals 4 damage to the targeted champion')
    ->tested('Everflame Staff')
    ->covers(['activated-ability', 'banish-self', 'damage', 'class-bonus'], '[Class Bonus] Banish CARDNAME: As a Spell, deal 4 damage to target champion. Activate this ability only if there are three or more refinement counters on CARDNAME.')
    ->register();
