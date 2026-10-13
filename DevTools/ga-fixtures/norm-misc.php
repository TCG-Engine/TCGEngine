<?php
/**
 * Phase 3d (Norm): leftovers (floating memory attack, command chessman attack, element-enabling domain). 'quiet' deck unless noted.
 */

gaFloatingPays('savage-smash-class-bonus-floating-memory-pays-a-memory-cost', 'Savage Smash', '[Class Bonus] **Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost. Apply this effect only if your champion’s class matches this card’s class.)*', 'GUARDIAN');
gaFloatingPays('savage-smash-without-the-class-bonus-cannot-pay-a-memory-cost', 'Savage Smash', '[Class Bonus] **Floating Memory**', 'WARRIOR', false);

// ---- Classical Opening (Command Chessman) ----

gaScenario('classical-opening-a-chessman-ally-performs-the-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Classical Opening')
    ->seed('myField', 'Weiss Bishop')
    ->seed('myHand', 'Classical Opening')
    ->reachTurnThree()
    ->play('Classical Opening')->pay(2)->target('myField-1')->target('theirField-0')->pass(2)
    ->expectDamage('p2Field-0', 3, 'The Chessman ally performed the attack: 1 power + the card\'s 2')
    ->tested('Classical Opening')
    ->covers(['command-chessman', 'combat'], '**Command Chessman** *(A Chessman ally you control performs this attack.)*')
    ->register();

// ---- Prismatic Sanctuary ----

gaScenario('prismatic-sanctuary-enables-fire-water-and-wind-for-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Prismatic Sanctuary')
    ->seed('myField', 'Prismatic Sanctuary')
    ->seed('myHand', 'Weiss Bishop')
    ->play('Weiss Bishop')->pay(2)
    ->expectCardExists('myField', 'Weiss Bishop', 'Water is enabled by the domain even though the champion is a Spirit of Fire')
    ->tested('Prismatic Sanctuary')
    ->covers(['static', 'element'], 'Fire, water, and wind elements are enabled for you.')
    ->register();

gaScenario('without-prismatic-sanctuary-a-water-card-cannot-be-played-by-a-fire-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Prismatic Sanctuary')
    ->seed('myHand', 'Weiss Bishop')
    ->play('Weiss Bishop')->pay(2)
    ->expectZoneCount('myField', 1, 1, 'Only the champion is on the field: the water ally was not played')
    ->tested('Prismatic Sanctuary')
    ->covers(['static', 'element'], 'Control case for the element-enabling domain.')
    ->register();
