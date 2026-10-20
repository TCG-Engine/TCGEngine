<?php
/**
 * Phase 3d (Norm): champion-bonus cards (Alice and Guo Jia). gaBonus() (helpers.php) patches the starting champion; the quiet deck.
 */

// ---- Alice, Distorted Queen ----

gaBonus('alice-distorted-queen-enter-gains-the-phantasmagoria-mastery-with-two-haunt-counters', 'Alice, Whim\'s Monarch')
    ->seed('myField', 'Alice, Distorted Queen')
    ->observe()
    ->expectCardExists('myMastery', 'Phantasmagoria', 'On Enter: the Phantasmagoria mastery was gained')
    ->expectProperty('p1Mastery-0', 'Counters', '{"haunt":2}', 'Two haunt counters on it')
    ->tested('Alice, Distorted Queen')
    ->covers(['on-enter', 'mastery', 'counter', 'champion'], '**On Enter:** You gain the Phantasmagoria mastery. Then put two **haunt** counters on it.')
    ->register();

// ---- Alice, Whim's Monarch ----

gaBonus('alice-whims-monarch-rest-summons-a-pawn-piece-when-you-control-no-pawn', 'Alice, Whim\'s Monarch')
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')
    ->expectCardExists('myField', 'Pawn Piece', '[REST]: with no Pawn ally a Pawn Piece token was summoned')
    ->tested('Alice, Whim\'s Monarch')
    ->covers(['activated-ability', 'rest', 'summon', 'champion'], '[REST]: If you don\'t control a Pawn ally, **summon** a Pawn Piece Token.')
    ->register();

// ---- Pawn Piece / Queen Piece ----

gaBonus('pawn-piece-alice-bonus-has-commanded-will-one', 'Alice, Distorted Queen')
    ->seed('myField', 'Pawn Piece')
    ->observe()
    ->expectPower('p1Field-1', 0, 'The printed 0 power (Commanded Will only applies while attacking with a Command card)')
    ->tested('Pawn Piece')
    ->covers(['alice-bonus', 'keyword'], '[Alice Bonus] **Commanded Will 1**')
    ->register();

gaBonus('queen-piece-alice-bonus-with-a-pawn-ally-prevents-all-damage-to-it', 'Alice, Distorted Queen')
    ->seed('myField', 'Queen Piece')
    ->seed('myField', 'Pawn Piece')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectDamage('p1Field-1', 0, '[Alice Bonus] A Pawn ally is controlled: all damage to the Queen was prevented')
    ->tested('Queen Piece')
    ->covers(['alice-bonus', 'prevention', 'condition'], '[Alice Bonus] As long as you control one or more Chessman Pawn allies, prevent all damage that would be dealt to CARDNAME.')
    ->register();

// ---- Fatestone of Progress (Guo Jia) ----

gaBonus('fatestone-of-progress-guo-jia-bonus-enter-puts-a-quest-counter-on-the-champion', 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Fatestone of Progress')
    ->observe()
    ->expectProperty('p1Field-0', 'Counters', '{"quest":1}', '[Guo Jia Bonus] On Enter: a quest counter on your champion')
    ->tested('Fatestone of Progress')
    ->covers(['guo-jia-bonus', 'on-enter', 'counter'], '[Guo Jia Bonus] **On Enter**: Put a **quest** counter on your champion.')
    ->register();

gaScenario('fatestone-of-progress-without-guo-jia-puts-no-quest-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Fatestone of Progress')
    ->observe()
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Not Guo Jia: no quest counter')
    ->tested('Fatestone of Progress')
    ->covers(['guo-jia-bonus', 'on-enter', 'condition'], '[Guo Jia Bonus] **On Enter**: Put a **quest** counter on your champion.')
    ->register();
