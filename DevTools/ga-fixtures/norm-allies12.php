<?php
/**
 * Phase 3d (Norm): allies, twelfth batch (scavenge, choices and abilities that change the board). 'quiet' deck.
 */

// ---- Musical Curator ----

gaScenario('musical-curator-enter-choosing-harmony-scavenges-twelve-for-a-harmony-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Musical Curator')
    ->deckTop(['Dungeon Guide', 'Empowering Harmony'])
    ->seed('myHand', 'Musical Curator')
    ->play('Musical Curator')->pay(3)->answer(1, '0')->answer(1, 'myTempZone-9')
    ->expectCardExists('myHand', 'Empowering Harmony', 'Harmony chosen: the Harmony within the top twelve was put into the hand')
    ->tested('Musical Curator')
    ->covers(['on-enter', 'scavenge', 'optional-choice'], '**On Enter:** Choose Harmony or Melody. **Scavenge 12 **for a card of that subtype.')
    ->register();

gaScenario('musical-curator-enter-choosing-melody-scavenges-twelve-for-a-melody-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Musical Curator')
    ->deckTop(['Dungeon Guide', 'Steady Verse'])
    ->seed('myHand', 'Musical Curator')
    ->play('Musical Curator')->pay(3)->answer(1, '1')->answer(1, 'myTempZone-9')
    ->expectCardExists('myHand', 'Steady Verse', 'Melody chosen: the Melody within the top twelve was put into the hand')
    ->tested('Musical Curator')
    ->covers(['on-enter', 'scavenge', 'optional-choice'], '**On Enter:** Choose Harmony or Melody. **Scavenge 12 **for a card of that subtype.')
    ->register();

// ---- Ovation Guide ----

gaScenario('ovation-guide-enter-the-opponent-materializing-a-champion-draws-you-a-card-and-crowds-favor')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->readyFor('Ovation Guide')
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Ovation Guide')
    ->play('Ovation Guide')->pay(3)->answer(2, 'YES')->answer(2, 'myMaterial-0')
    ->expectZoneCount('myHand', 5, 1, 'Seven in hand, minus the Guide and three paid cards, plus the drawn card')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'gpmJdGYqoC', 'You gained the Crowd\'s Favor status')
    ->tested('Ovation Guide')
    ->covers(['on-enter', 'optional-choice', 'materialize', 'draw', 'status'], '**On Enter:** Target opponent may materialize a champion card from their material deck. If they do, you draw a card and gain the Crowd\'s Favor status.')
    ->register();

gaScenario('ovation-guide-enter-the-opponent-declining-gives-you-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ovation Guide')
    ->seed('myHand', 'Ovation Guide')
    ->play('Ovation Guide')->pay(3)->answer(2, 'NO')
    ->expectZoneCount('myHand', 4, 1, 'The opponent declined: no card drawn')
    ->expectZoneCount('myGlobalEffects', 0, 1, 'No status gained')
    ->tested('Ovation Guide')
    ->covers(['on-enter', 'optional-choice-decline'], '**On Enter:** Target opponent may materialize a champion card from their material deck.')
    ->register();

// ---- Capricious Lynx ----

gaPrideCase('capricious-lynx-pride-four-does-not-obey-a-level-three-champion', 'Capricious Lynx', 'Dungeon Guide', ['level' => 3],
    ['pride', 'keyword'], '**Pride 4**', false, 'Pride 4: a level 3 champion is not enough');

gaPrideCase('capricious-lynx-pride-four-obeys-a-level-four-champion', 'Capricious Lynx', 'Dungeon Guide', ['level' => 4],
    ['pride', 'keyword', 'level-gate'], '**Pride 4**', true, 'Pride 4 is satisfied at level 4: the Lynx attacked and rested');

gaScenario('capricious-lynx-class-bonus-enter-loses-all-abilities-so-it-obeys-at-level-zero')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Capricious Lynx', classBonus: true)
    ->seed('myHand', 'Capricious Lynx')
    ->reachTurnThree()
    ->play('Capricious Lynx')->pay(3)
    ->action(1, 10002, 'myField-1!FSM!')->answer(1, 'theirField-0')->pass(2)
    ->expectProperty('p1Field-1', 'Status', '1', '[Class Bonus] With its pride gone it obeyed the level 0 champion and attacked')
    ->tested('Capricious Lynx')
    ->covers(['class-bonus', 'on-enter', 'lose-abilities'], '[Class Bonus] **On Enter:** CARDNAME loses all abilities until end of turn.')
    ->register();

gaScenario('capricious-lynx-without-the-class-bonus-keeps-its-pride-and-does-not-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Capricious Lynx')
    ->seed('myHand', 'Capricious Lynx')
    ->reachTurnThree()
    ->play('Capricious Lynx')->pay(3)
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectProperty('p1Field-1', 'Status', '2', 'No class bonus: the pride stays and the Lynx never attacked')
    ->tested('Capricious Lynx')
    ->covers(['class-bonus', 'on-enter', 'condition'], '[Class Bonus] **On Enter:** CARDNAME loses all abilities until end of turn.')
    ->register();
