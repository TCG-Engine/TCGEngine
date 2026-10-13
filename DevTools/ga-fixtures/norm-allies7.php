<?php
/**
 * Phase 3d (Norm): allies, seventh batch (influence, material deck, token and status allies). 'quiet' deck.
 */

// ---- Wicked Gildbreaker ----

gaScenario('wicked-gildbreaker-enter-against-influence-eight-gets-a-buff-counter')
    ->deckTemplate('quiet', 'Wicked Gildbreaker')
    ->readyFor('Wicked Gildbreaker')
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Wicked Gildbreaker')
    ->play('Wicked Gildbreaker')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The opponent has 8 cards in hand and memory: a buff counter')
    ->tested('Wicked Gildbreaker')
    ->covers(['on-enter', 'influence', 'counter', 'condition'], '**On Enter:** If an opponent has **influence** eight or more, put a **buff** counter on CARDNAME.')
    ->register();

gaScenario('wicked-gildbreaker-enter-against-influence-seven-gets-no-counter')
    ->deckTemplate('quiet', 'Wicked Gildbreaker')
    ->readyFor('Wicked Gildbreaker')
    ->seed('myHand', 'Wicked Gildbreaker')
    ->play('Wicked Gildbreaker')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The opponent has 7 cards: no buff counter')
    ->tested('Wicked Gildbreaker')
    ->covers(['on-enter', 'influence', 'condition'], '**On Enter:** If an opponent has **influence** eight or more')
    ->register();

gaScenario('wicked-gildbreaker-against-influence-nine-has-vigor')
    ->deckTemplate('quiet', 'Wicked Gildbreaker')
    ->readyFor('Wicked Gildbreaker')
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Wicked Gildbreaker', properties: ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', 'Influence 9: vigor woke it at the end phase')
    ->tested('Wicked Gildbreaker')
    ->covers(['influence', 'condition', 'stealth', 'vigor'], 'As long as an opponent has **influence** nine or more, CARDNAME has **stealth** and **vigor**.')
    ->register();

gaScenario('wicked-gildbreaker-against-influence-nine-has-stealth')
    ->deckTemplate('quiet', 'Wicked Gildbreaker')
    ->readyFor('Wicked Gildbreaker', player: 1)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Wicked Gildbreaker', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Influence 9: the Gildbreaker has stealth and cannot be targeted by attacks')
    ->tested('Wicked Gildbreaker')
    ->covers(['influence', 'condition', 'stealth'], 'As long as an opponent has **influence** nine or more, CARDNAME has **stealth** and **vigor**.')
    ->register();

gaScenario('wicked-gildbreaker-against-influence-seven-has-no-vigor')
    ->deckTemplate('quiet', 'Wicked Gildbreaker')
    ->readyFor('Wicked Gildbreaker')
    ->seed('myField', 'Wicked Gildbreaker', properties: ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '1', 'Influence 7: no vigor, still rested')
    ->tested('Wicked Gildbreaker')
    ->covers(['influence', 'condition', 'vigor'], 'As long as an opponent has **influence** nine or more')
    ->register();

// ---- Biding Endroid ----

gaScenario('biding-endroid-with-four-or-fewer-material-cards-each-gets-plus-three-power-and-life')
    ->deckTemplate('quiet', 'Biding Endroid')
    ->readyFor('Biding Endroid')
    ->seed('myField', 'Biding Endroid')
    ->observe()
    ->expectPower('p1Field-1', 4, 'Both players have four material cards: 1 + 3 power')
    ->expectLife('p1Field-1', 5, 'Both players have four material cards: 2 + 3 life')
    ->tested('Biding Endroid')
    ->covers(['condition', 'stat-change'], 'As long as each player has four or less cards in their material deck, CARDNAME gets +3[POWER] and +3[LIFE].')
    ->register();

gaScenario('biding-endroid-with-five-opposing-material-cards-has-its-printed-stats')
    ->deckTemplate('quiet', 'Biding Endroid')
    ->readyFor('Biding Endroid')
    ->seed('myField', 'Biding Endroid')
    ->seed('myMaterial', 'Spirit of Wind', player: 2)
    ->observe()
    ->expectPower('p1Field-1', 1, 'The opponent has five material cards: the printed 1 power')
    ->expectLife('p1Field-1', 2, 'The opponent has five material cards: the printed 2 life')
    ->tested('Biding Endroid')
    ->covers(['condition', 'stat-change'], 'As long as each player has four or less cards in their material deck')
    ->register();

// ---- Cheerful Slime ----

gaScenario('cheerful-slime-class-bonus-enter-gains-crowds-favor')
    ->deckTemplate('quiet', 'Cheerful Slime')
    ->readyFor('Cheerful Slime', classBonus: true)
    ->seed('myHand', 'Cheerful Slime')
    ->play('Cheerful Slime')->pay(3)
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'gpmJdGYqoC', '[Class Bonus] The Crowd\'s Favor status was gained')
    ->tested('Cheerful Slime')
    ->covers(['class-bonus', 'on-enter', 'status'], '[Class Bonus] **On Enter: **You gain the Crowd\'s Favor status.')
    ->register();

gaScenario('cheerful-slime-without-the-class-bonus-gains-nothing')
    ->deckTemplate('quiet', 'Cheerful Slime')
    ->readyFor('Cheerful Slime')
    ->seed('myHand', 'Cheerful Slime')
    ->play('Cheerful Slime')->pay(3)
    ->expectZoneCount('myGlobalEffects', 0, 1, 'No class bonus: no status')
    ->tested('Cheerful Slime')
    ->covers(['class-bonus', 'condition', 'status'], '[Class Bonus] **On Enter: **You gain the Crowd\'s Favor status.')
    ->register();

// ---- Automated Gardener ----

gaScenario('automated-gardener-class-bonus-enter-gathers-a-token')
    ->deckTemplate('quiet', 'Automated Gardener')
    ->readyFor('Automated Gardener', classBonus: true)
    ->seed('myHand', 'Automated Gardener')
    ->play('Automated Gardener')->pay(3)
    ->expectZoneCount('myField', 3, 1, '[Class Bonus] Gather: a random plant token joined the champion and the Gardener')
    ->tested('Automated Gardener')
    ->covers(['class-bonus', 'on-enter', 'gather', 'summon'], '[Class Bonus] **On Enter**: **Gather**.')
    ->register();

gaScenario('automated-gardener-without-the-class-bonus-gathers-nothing')
    ->deckTemplate('quiet', 'Automated Gardener')
    ->readyFor('Automated Gardener')
    ->seed('myHand', 'Automated Gardener')
    ->play('Automated Gardener')->pay(3)
    ->expectZoneCount('myField', 2, 1, 'No class bonus: only the champion and the Gardener')
    ->tested('Automated Gardener')
    ->covers(['class-bonus', 'condition', 'gather'], '[Class Bonus] **On Enter**: **Gather**.')
    ->register();

// ---- Allied Warpriestess ----

gaScenario('allied-warpriestess-class-bonus-gets-plus-one-power')
    ->deckTemplate('quiet', 'Allied Warpriestess')
    ->readyFor('Allied Warpriestess', classBonus: true)
    ->seed('myField', 'Allied Warpriestess')
    ->observe()
    ->expectPower('p1Field-1', 2, '[Class Bonus] 1 + 1 power')
    ->tested('Allied Warpriestess')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('allied-warpriestess-without-the-class-bonus-has-its-printed-power')
    ->deckTemplate('quiet', 'Allied Warpriestess')
    ->readyFor('Allied Warpriestess')
    ->seed('myField', 'Allied Warpriestess')
    ->observe()
    ->expectPower('p1Field-1', 1, 'No class bonus: the printed 1 power')
    ->tested('Allied Warpriestess')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('allied-warpriestess-memory-four-on-attack-recovers-two')
    ->deckTemplate('quiet', 'Allied Warpriestess')
    ->readyFor('Allied Warpriestess', damage: 5, player: 2)
    ->seed('myField', 'Allied Warpriestess', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p2Field-0', 3, '[Memory 4+] On Attack: recover 2 (5 - 2)')
    ->tested('Allied Warpriestess')
    ->covers(['memory-gate', 'on-attack', 'recover'], '[Memory 4+] **On Attack: Recover 2**.')
    ->register();

gaScenario('allied-warpriestess-memory-three-on-attack-does-not-recover')
    ->deckTemplate('quiet', 'Allied Warpriestess')
    ->readyFor('Allied Warpriestess', damage: 5, player: 2)
    ->seed('myField', 'Allied Warpriestess', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p2Field-0', 5, 'Only three memory cards: no recovery')
    ->tested('Allied Warpriestess')
    ->covers(['memory-gate', 'on-attack', 'condition'], '[Memory 4+] **On Attack: Recover 2**.')
    ->register();
