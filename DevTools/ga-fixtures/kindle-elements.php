<?php
// Exalted Fire membership across Kindle affordability and payment.
gaScenario('kindle-elements-mixed')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Silent Firebrand', classBonus: true, player: 1)->emptyZone('myHand', destZone: 'myDeck')
    ->seed('myGraveyard', 'Scorched Conquest', player: 1)
    ->seed('myGraveyard', 'Red Slime', player: 1)->seed('myHand', 'Silent Firebrand', player: 1)->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->action(1, 10002, 'myHand-0!FSM!')->answer(1, 'myGraveyard-0')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 2, 1, 'Both banishes precede reserve payment and responses', step: 3)->expectZoneCount('myMemory', 0, 1, 'No reserve paid before the banishes', step: 3)
    ->pay(2, player: 1)->expectZoneCount('myMemory', 2, 1, 'Only the reserve remainder is paid')
    ->expectZoneCount('myBanish', 2, 1, 'Only accepted fire payments are banished')
    ->expectCardExists('myField', 'Silent Firebrand', 'The paid activation resolves', viewer: 1)
    ->tested('Silent Firebrand')->covers(['kindle', 'cost', 'element'], 'Kindle accepts fire element graveyard cards, including Exalted Fire; payment is optional.')->register();

gaScenario('kindle-elements-dual')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Silent Firebrand', classBonus: true, player: 1)
    ->seed('myGraveyard', 'Scorched Conquest', player: 1)
    ->seed('myGraveyard', 'Scorched Conquest', player: 1)->seed('myHand', 'Silent Firebrand', player: 1)
    ->play('Silent Firebrand', player: 1)->answer(1, 'myGraveyard-0')->answer(1, 'myGraveyard-0')
    ->pay(2, player: 1)->expectZoneCount('myMemory', 2, 1, 'Only the reserve remainder is paid')
    ->expectZoneCount('myBanish', 2, 1, 'Only accepted fire payments are banished')
    ->expectCardExists('myField', 'Silent Firebrand', 'The paid activation resolves', viewer: 1)
    ->tested('Silent Firebrand')->covers(['kindle', 'cost', 'element'], 'Kindle accepts fire element graveyard cards, including Exalted Fire; payment is optional.')->register();

gaScenario('kindle-elements-water')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Silent Firebrand', classBonus: true, player: 1)
    ->seed('myGraveyard', 'Eminent Lethargy', player: 1)->seed('myHand', 'Silent Firebrand', player: 1)
    ->play('Silent Firebrand', player: 1)
    ->pay(4, player: 1)->expectZoneCount('myMemory', 4, 1, 'Only the reserve remainder is paid')
    ->expectZoneCount('myBanish', 0, 1, 'Only accepted fire payments are banished')
    ->expectCardExists('myGraveyard', 'Eminent Lethargy', 'The unpaid card remains', viewer: 1)
    ->expectCardExists('myField', 'Silent Firebrand', 'The paid activation resolves', viewer: 1)
    ->tested('Silent Firebrand')->covers(['kindle', 'cost', 'element'], 'Kindle accepts fire element graveyard cards, including Exalted Fire; payment is optional.')->register();

gaScenario('kindle-elements-decline')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Silent Firebrand', classBonus: true, player: 1)
    ->seed('myGraveyard', 'Scorched Conquest', player: 1)->seed('myHand', 'Silent Firebrand', player: 1)
    ->play('Silent Firebrand', player: 1)->answer(1, 'PASS')
    ->pay(4, player: 1)->expectZoneCount('myMemory', 4, 1, 'Only the reserve remainder is paid')
    ->expectZoneCount('myBanish', 0, 1, 'Only accepted fire payments are banished')
    ->expectCardExists('myGraveyard', 'Scorched Conquest', 'The unpaid card remains', viewer: 1)
    ->expectCardExists('myField', 'Silent Firebrand', 'The paid activation resolves', viewer: 1)
    ->tested('Silent Firebrand')->covers(['kindle', 'cost', 'element'], 'Kindle accepts fire element graveyard cards, including Exalted Fire; payment is optional.')->register();

gaScenario('kindle-elements-stablemaster')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Ritai Stablemaster')
    ->seed('myGraveyard', 'Scorched Conquest', player: 1)->seed('myHand', 'Lumbering Steed', player: 1)
    ->play('Lumbering Steed', player: 1)->answer(1, 'myGraveyard-0')
    ->pay(3, player: 1)->expectZoneCount('myMemory', 3, 1, 'Only the reserve remainder is paid')
    ->expectZoneCount('myBanish', 1, 1, 'Only accepted fire payments are banished')
    ->expectCardExists('myField', 'Lumbering Steed', 'The paid activation resolves', viewer: 1)
    ->tested('Ritai Stablemaster')->covers(['kindle', 'cost', 'element'], 'Kindle accepts fire element graveyard cards, including Exalted Fire; payment is optional.')->register();

gaScenario('kindle-elements-player-two')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Silent Firebrand', classBonus: true, player: 2)
    ->seed('myGraveyard', 'Scorched Conquest', player: 2)->seed('myHand', 'Silent Firebrand', player: 2)->endTurn()
    ->play('Silent Firebrand', player: 2)->answer(2, 'myGraveyard-0')
    ->pay(3, player: 2)->expectZoneCount('myMemory', 3, 2, 'Only the reserve remainder is paid')
    ->expectZoneCount('myBanish', 1, 2, 'Only accepted fire payments are banished')
    ->expectCardExists('myField', 'Silent Firebrand', 'The paid activation resolves', viewer: 2)
    ->tested('Silent Firebrand')->covers(['kindle', 'cost', 'element'], 'Kindle accepts fire element graveyard cards, including Exalted Fire; payment is optional.')->register();

