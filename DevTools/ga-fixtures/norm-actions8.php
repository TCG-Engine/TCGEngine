<?php
/**
 * Phase 3d (Norm): actions, eighth batch (Aetherwing support and reactions). 'quiet' deck.
 */

gaScenario('aetherwings-ward-gives-an-aetherwing-weapon-spellshroud')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Aetherwing\'s Ward')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Aetherwing\'s Ward')
    ->castSpell('Aetherwing\'s Ward', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","SPELLSHROUD"]', 'The Aetherwing weapon gained spellshroud')
    ->expectZoneCount('myHand', 5, 1, 'The champion is not distant: no card drawn (8 - 1 - 2)')
    ->tested('Aetherwing\'s Ward')
    ->covers(['spellshroud', 'condition', 'targeting'], 'Target Aetherwing weapon you control gains **spellshroud** until end of turn.')
    ->register();

gaScenario('aetherwings-ward-a-distant-champion-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Aetherwing\'s Ward')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Aetherwing\'s Ward')
    ->castSpell('Aetherwing\'s Ward', pay: 2, window: '', target: 'myField-1')
    ->expectZoneCount('myHand', 6, 1, 'Your champion is distant: a card was drawn (8 - 1 - 2 + 1)')
    ->tested('Aetherwing\'s Ward')
    ->covers(['distant', 'draw', 'condition'], 'If your champion is **distant**, draw a card.')
    ->register();

gaScenario('fortifying-manashot-puts-two-durability-counters-on-an-aetherwing-weapon')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fortifying Manashot')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Fortifying Manashot')
    ->castSpell('Fortifying Manashot', pay: 3, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":5}', 'Two durability counters were added to the weapon\'s three')
    ->tested('Fortifying Manashot')
    ->covers(['counter', 'targeting'], 'Put two **durability** counters on target Aetherwing weapon you control.')
    ->register();

gaScenario('aetheric-reforging-banishes-up-to-one-target-card-in-a-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Aetheric Reforging')
    ->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Aetheric Reforging')
    ->castSpell('Aetheric Reforging', pay: 2, window: '', target: 'theirGraveyard-0')
    ->expectZoneCount('theirGraveyard', 0, 1, 'The graveyard card was banished')
    ->expectZoneCount('theirBanish', 1, 1, 'Into its owner\'s banishment')
    ->tested('Aetheric Reforging')
    ->covers(['banish', 'targeting'], 'Banish up to one target card in a graveyard.')
    ->register();
