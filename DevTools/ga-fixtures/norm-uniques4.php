<?php
/**
 * Phase 3d (Norm): unique allies and phantasias, fourth batch. 'quiet' deck.
 */

// ---- Bandit, Gaze Leader ----

gaScenario('bandit-gaze-leader-enter-scavenges-six-for-a-raccoon-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bandit, Gaze Leader')
    ->deckTop(['Dungeon Guide', 'Banner Raccoon'])
    ->seed('myHand', 'Bandit, Gaze Leader')
    ->play('Bandit, Gaze Leader')->pay(3)->answer(1, 'myTempZone-4')
    ->expectCardExists('myHand', 'Banner Raccoon', 'On Enter: the Raccoon ally within the top six was put into the hand')
    ->tested('Bandit, Gaze Leader')
    ->covers(['on-enter', 'scavenge'], '**On Enter:** **Scavenge 6** for a Raccoon ally card.')
    ->register();

// ---- Chance, Seven of Spades ----

gaScenario('chance-level-one-costs-three-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Chance, Seven of Spades', level: 1)
    ->seed('myHand', 'Chance, Seven of Spades')
    ->play('Chance, Seven of Spades')->pay(4)->answer(1, 'PASS')
    ->expectCardExists('myField', 'Chance, Seven of Spades', '[Level 1+] 7 - 3 = 4')
    ->tested('Chance, Seven of Spades')
    ->covers(['level-gate', 'cost-reduction'], '[Level 1+] This card costs 3 less to activate.')
    ->register();

// ---- Overlord Mk III ----

gaScenario('overlord-mk-iii-sacrificing-four-powercells-as-the-additional-cost-brings-in-a-5-5')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Overlord Mk III')
    ->seed('myField', 'Powercell')->seed('myField', 'Powercell')->seed('myField', 'Powercell')->seed('myField', 'Powercell')
    ->seed('myHand', 'Overlord Mk III')
    ->play('Overlord Mk III')->answer(1, 'myField-1')->answer(1, 'myField-1')->answer(1, 'myField-1')->answer(1, 'myField-1')->pay(4)
    ->expectZoneCount('myField', 2, 1, 'Four Powercells were sacrificed: the champion and the Overlord remain')
    ->expectPower('p1Field-1', 5, 'Printed 5 power')
    ->tested('Overlord Mk III')
    ->covers(['additional-cost', 'sacrifice'], 'As an additional cost to activate this card, sacrifice four Powercells.')
    ->register();

// ---- Queen's Gambit ----

gaBonus('queens-gambit-sacrificing-a-chessman-ally-draws-into-memory', 'Alice, Whim\'s Monarch')
    ->seed('myField', 'Weiss Bishop')
    ->seed('myHand', 'Queen\'s Gambit')
    ->play('Queen\'s Gambit')->answer(1, 'myField-1')->pay(2)->passBoth()
    ->expectCardExists('myGraveyard', 'Weiss Bishop', 'The Chessman ally was sacrificed as the additional cost')
    ->expectZoneCount('myMemory', 3, 1, 'Two paid plus one drawn into memory')
    ->tested('Queen\'s Gambit')
    ->covers(['additional-cost', 'sacrifice', 'draw-into-memory', 'alice-bonus'], 'As an additional cost to activate this card, sacrifice a Chessman ally.', '**On Enter:** Draw a card into your memory.')
    ->register();

gaBonus('queens-gambit-sacrificing-a-queen-summons-three-pawn-pieces', 'Alice, Whim\'s Monarch')
    ->seed('myField', 'Queen Piece')
    ->seed('myHand', 'Queen\'s Gambit')
    ->play('Queen\'s Gambit')->answer(1, 'myField-1')->pay(2)->passBoth()
    ->expectZoneCount('myField', 5, 1, 'The Queen was sacrificed: the champion, the Gambit phantasia and three Pawn Piece tokens')
    ->tested('Queen\'s Gambit')
    ->covers(['on-enter', 'summon', 'condition'], 'If the sacrificed ally was a Queen, **summon** three Pawn Piece tokens.')
    ->register();
