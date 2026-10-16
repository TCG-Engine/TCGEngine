<?php
/**
 * Nico, Whiplash Allure: "Whenever a card with floating memory is banished from your graveyard, put a lash counter on Nico."
 * One scenario per generated handler that banishes a floating-memory card from the graveyard; each used to skip the notification.
 * The paths that already notified Nico are covered in water-rest.php.
 */

const GA_NICO_LASH_TEXT = 'Whenever a card with **floating memory** is banished from your graveyard, put a **lash** counter on Nico.';

gaScenario('nico-lash-fractal-of-intrusion-enter-banish')
    ->standardDeck('Fractal of Intrusion')
    ->champion(cardName: 'Nico, Whiplash Allure', lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Fractal of Intrusion')
    ->play('Fractal of Intrusion')->pay(3)->answer(1, 'myGraveyard-0')->answer(1, 'myTempZone-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectProperty('p1Field-0', 'Counters', '{"lash":1}', 'Nico got a lash counter')
    ->tested('Nico, Whiplash Allure')
    ->covers(['trigger', 'floating-memory', 'lash'], GA_NICO_LASH_TEXT)
    ->register();

gaScenario('nico-lash-froglet-footman-enter-banish')
    ->standardDeck('Froglet Footman')
    ->champion(cardName: 'Nico, Whiplash Allure', lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Froglet Footman')
    ->castSpell('Froglet Footman', pay: 3, window: 'opp')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectProperty('p1Field-0', 'Counters', '{"lash":1}', 'Nico got a lash counter')
    ->tested('Nico, Whiplash Allure')
    ->covers(['trigger', 'floating-memory', 'lash'], GA_NICO_LASH_TEXT)
    ->register();

gaScenario('nico-lash-rainweaver-mage-enter-banish')
    ->standardDeck('Rainweaver Mage')
    ->champion(cardName: 'Nico, Whiplash Allure', lineage: ['Spirit of Water'], classes: 'MAGE')
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Rainweaver Mage')
    ->castSpell('Rainweaver Mage', pay: 3, window: 'opp')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"MAGE"},"lash":1}', 'Nico got a lash counter')
    ->tested('Nico, Whiplash Allure')
    ->covers(['trigger', 'floating-memory', 'lash'], GA_NICO_LASH_TEXT)
    ->register();

gaScenario('nico-lash-sudden-snow-banish')
    ->standardDeck('Sudden Snow')
    ->champion(cardName: 'Nico, Whiplash Allure', lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Evercurrent Raider')
    ->seed('myHand', 'Sudden Snow')
    ->seed('myHand', 'Croaking Webfoot')
    ->play('Sudden Snow')->pay(2)->answer(1, 'myGraveyard-0')->passBoth()
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectProperty('p1Field-0', 'Counters', '{"lash":1}', 'Nico got a lash counter')
    ->tested('Nico, Whiplash Allure')
    ->covers(['trigger', 'floating-memory', 'lash'], GA_NICO_LASH_TEXT)
    ->register();

gaScenario('nico-lash-refresh-chamber-banish')
    ->deck("# Material\n1 Spirit of Water\n1 Plated Bullet\n# Main\n4 Refresh Chamber\n10 Dungeon Guide")
    ->champion(cardName: 'Nico, Whiplash Allure', lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Refresh Chamber')
    ->play('Refresh Chamber')->pay(2)->answer(1, 'myMaterial-0')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectProperty('p1Field-0', 'Counters', '{"lash":1}', 'Nico got a lash counter')
    ->tested('Nico, Whiplash Allure')
    ->covers(['trigger', 'floating-memory', 'lash'], GA_NICO_LASH_TEXT)
    ->register();

gaScenario('nico-lash-cao-cao-on-attack-banish')
    ->standardDeck('Cao Cao, Aspirant of Chaos')
    ->champion(cardName: 'Nico, Whiplash Allure', lineage: ['Spirit of Water'], classes: 'MAGE')
    ->seed('myField', 'Cao Cao, Aspirant of Chaos')
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 1])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"MAGE"},"lash":1}', 'Nico got a lash counter')
    ->tested('Nico, Whiplash Allure')
    ->covers(['trigger', 'floating-memory', 'lash'], GA_NICO_LASH_TEXT)
    ->register();

gaScenario('nico-lash-cloak-of-stillwater-banish-cost')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Nico, Whiplash Allure', lineage: ['Spirit of Water'])
    ->seed('myField', 'Cloak of Stillwater')
    ->seed('myGraveyard', 'Fast Cure')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectProperty('p1Field-0', 'Counters', '{"lash":1}', 'Nico got a lash counter')
    ->tested('Nico, Whiplash Allure')
    ->covers(['trigger', 'floating-memory', 'lash'], GA_NICO_LASH_TEXT)
    ->register();

gaScenario('nico-lash-krustallan-archer-on-attack-banish')
    ->standardDeck('Krustallan Archer')
    ->champion(cardName: 'Nico, Whiplash Allure', lineage: ['Spirit of Water'], classes: 'RANGER')
    ->seed('myField', 'Krustallan Archer')
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 1])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"RANGER"},"lash":1}', 'Nico got a lash counter')
    ->tested('Nico, Whiplash Allure')
    ->covers(['trigger', 'floating-memory', 'lash'], GA_NICO_LASH_TEXT)
    ->register();
