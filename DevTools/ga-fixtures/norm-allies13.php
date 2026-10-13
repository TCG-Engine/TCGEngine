<?php
/**
 * Phase 3d (Norm): allies, thirteenth batch (activated abilities and leftovers). 'quiet' deck.
 */

// ---- Protector Raccoon ----

gaScenario('protector-raccoon-rest-makes-the-opponent-banish-a-graveyard-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Protector Raccoon')
    ->seed('myField', 'Protector Raccoon')
    ->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->answer(2, 'myGraveyard-0')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Raccoon rested')
    ->expectZoneCount('myBanish', 1, 2, 'The opponent banished their own graveyard card into their banishment')
    ->tested('Protector Raccoon')
    ->covers(['activated-ability', 'rest', 'banish'], '[REST]: Target opponent banishes a card from their graveyard.')
    ->register();

gaScenario('protector-raccoon-rest-prevents-the-next-two-damage-to-an-animal-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Protector Raccoon')
    ->seed('myField', 'Protector Raccoon')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->reachTurnThree(fastCardInHand: true)->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myField-2')
    ->attack('myField-2', 'theirField-1')->answer(2, 'myField-1')
    ->expectDamage('p1Field-2', 1, 'The Steed\'s 3 retaliation damage was reduced by 2')
    ->tested('Protector Raccoon')
    ->covers(['activated-ability', 'rest', 'prevention', 'targeting'], '[REST]: Prevent the next 2 damage that would be dealt to target Animal ally this turn.')
    ->register();

// ---- Steady Sharpshooter ----

gaScenario('steady-sharpshooter-on-attack-puts-an-aim-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Steady Sharpshooter')
    ->seed('myField', 'Steady Sharpshooter')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'NO')->pass(2)
    ->expectProperty('p1Field-1', 'Counters', '{"aim":1}', 'On Attack: an aim counter (kept: the removal was declined)')
    ->tested('Steady Sharpshooter')
    ->covers(['on-attack', 'counter', 'optional-choice'], '**On Attack**: Put an **aim** counter on Steady Sharpshooter. Then you may remove all **aim** counters from Steady Sharpshooter.')
    ->register();

// ---- Siege Mauler ----

gaScenario('siege-mauler-attacking-a-domain-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Siege Mauler')
    ->seed('myField', 'Siege Mauler')
    ->seed('myField', 'Epicurean Institute', player: 2, properties: ['Counters' => ['durability' => 4]])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectCardExists('myGraveyard', 'Epicurean Institute', 'Attacking a domain: 2 + 2 = 4 damage removed all four durability counters', 2)
    ->tested('Siege Mauler')
    ->covers(['condition', 'stat-change', 'combat'], 'As long as CARDNAME is attacking a domain, it gets +2 [POWER].')
    ->register();

gaScenario('siege-mauler-control-an-ordinary-ally-leaves-the-domain-standing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Epicurean Institute', player: 2, properties: ['Counters' => ['durability' => 4]])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectProperty('p2Field-1', 'Counters', '{"durability":3}', 'Control: a 1-power attacker removed one durability counter, so 4 damage from the Mauler is the +2')
    ->tested('Siege Mauler')
    ->covers(['condition', 'combat'], 'As long as CARDNAME is attacking a domain, it gets +2 [POWER].')
    ->register();

// ---- VelTech Gear Hoarder ----

gaScenario('veltech-gear-hoarder-gets-plus-one-power-and-life-for-each-linked-veltech-item')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('VelTech Gear Hoarder')
    ->seed('myField', 'VelTech Gear Hoarder')
    ->patch('myField-1', ['Subcards' => [GaScenario::id('CookTech Knife')]])
    ->seed('myField', 'CookTech Knife')
    ->patch('myField-2', ['Counters' => ['linkedToAlly' => GaScenario::id('VelTech Gear Hoarder')]])
    ->observe()
    ->expectPower('p1Field-1', 3, 'One linked VelTech item: 1 printed, +1 from the Hoarder\'s own text and +1 from the Knife\'s Ally Link')
    ->tested('VelTech Gear Hoarder')
    ->covers(['link', 'stat-change', 'static'], 'CARDNAME gets +1[POWER] and +1[LIFE] for each VelTech item linked to it.')
    ->register();

// ---- Trained Birdroid ----

gaScenario('trained-birdroid-automaton-allies-have-true-sight')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Trained Birdroid')
    ->seed('myField', 'Trained Birdroid')
    ->seed('myField', 'Delivery Droid')
    ->seed('myField', 'Haze Droid', player: 2)
    ->reachTurnThree()
    ->attack('myField-2', 'theirField-1')->pass(2)
    ->expectDamage('p2Field-1', 1, 'The Automaton ally had true sight: it attacked the stealth Haze Droid')
    ->tested('Trained Birdroid')
    ->covers(['static', 'true-sight', 'combat'], 'Automaton allies you control have **true sight**.')
    ->register();

// ---- Engineered Slime ----

gaScenario('engineered-slime-pride-two-does-not-obey-a-level-one-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Engineered Slime', level: 1)
    ->seed('myField', 'Engineered Slime')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectProperty('p1Field-1', 'Status', '2', 'Pride 2: a level 1 champion is not enough')
    ->tested('Engineered Slime')
    ->covers(['pride', 'keyword'], '**Pride 2**')
    ->register();

// ---- Academy Guide ----

gaScenario('academy-guide-champion-cards-you-materialize-cost-one-less')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(level: 0)
    ->seed('myField', 'Academy Guide')
    ->endTurn()->endTurn(2)->target('myMaterial-0')
    ->expectLevel('p1Field-1', 1, 'Lorraine\'s memory cost 1 was reduced to 0: the level up needed no payment')
    ->tested('Academy Guide')
    ->covers(['cost-reduction', 'materialize'], 'Champion cards you materialize cost 1 less to materialize.')
    ->register();

// ---- Innocuous Disposer ----

gaScenario('innocuous-disposer-attack-cannot-be-retaliated-by-a-human-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Innocuous Disposer')
    ->seed('myField', 'Innocuous Disposer')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectDamage('p1Field-1', 0, 'The Human Guide could not retaliate against the Disposer')
    ->tested('Innocuous Disposer')
    ->covers(['restriction', 'retaliate', 'combat'], 'Innocuous Disposer\'s attacks can\'t be retaliated by Human allies.')
    ->register();
