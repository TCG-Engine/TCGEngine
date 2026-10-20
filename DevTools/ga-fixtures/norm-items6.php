<?php
/**
 * Phase 3d (Norm): regalia, sixth batch (weapons and taxes). 'quiet' deck.
 */

// ---- Excalibur, Cursed Sword ----

gaScenario('excalibur-cursed-sword-class-bonus-enter-giving-it-to-the-opponent-changes-its-controller')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Excalibur, Cursed Sword', classBonus: true)
    ->seed('myField', 'Excalibur, Cursed Sword')
    ->answer(1, 'YES')
    ->expectProperty('p1Field-1', 'Controller', '2', '[Class Bonus] The opponent gained control of Excalibur')
    ->tested('Excalibur, Cursed Sword')
    ->covers(['class-bonus', 'on-enter', 'control-change'], '[Class Bonus] **On Enter:** Choose a player. That player gains control of CARDNAME.')
    ->register();

gaScenario('excalibur-cursed-sword-class-bonus-enter-keeping-it-leaves-the-controller')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Excalibur, Cursed Sword', classBonus: true)
    ->seed('myField', 'Excalibur, Cursed Sword')
    ->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'Controller', '1', 'You kept control')
    ->tested('Excalibur, Cursed Sword')
    ->covers(['class-bonus', 'on-enter', 'condition'], '[Class Bonus] **On Enter:** Choose a player.')
    ->register();

// ---- Primal Whip ----

gaScenario('primal-whip-class-bonus-level-two-on-attack-buffs-up-to-two-non-human-allies')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Primal Whip', classBonus: true, level: 2)
    ->seed('myField', 'Primal Whip')
    ->seed('myField', 'Gray Wolf')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->answer(1, 'myField-2')->answer(1, '-')->pass(2)
    ->expectPower('p1Field-2', 3, '[Class Bonus] [Level 2+] On Attack: the non-Human ally got +1 power, 2 + 1')
    ->tested('Primal Whip')
    ->covers(['class-bonus', 'level-gate', 'on-attack', 'stat-change', 'targeting'], '[Class Bonus] [Level 2+] **On Attack:** Up to two target non-Human allies get +1 [POWER] until end of turn.')
    ->register();

// ---- Lagomorph Piece ----

gaNormItem('lagomorph-piece-pay-three-and-banish-returns-a-chessman-command-card-to-memory', 'Lagomorph Piece', 0, 0,
    fn($s) => $s->seed('myGraveyard', 'Classical Opening'),
    fn($s) => $s->answer(1, 'myGraveyard-0')->pay(3)->expectCardExists('myMemory', 'Classical Opening', 'The Chessman Command card returned to memory'),
    ['activated-ability', 'zone-movement', 'targeting', 'banish-self'], '(3), **Banish CARDNAME: **Return target Chessman Command card from your graveyard to your memory.');
