<?php
foreach ([0, 1, 2] as $n) {
    $s = gaScenario('eight-of-hearts-sacrifice-payment-' . $n)
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Eight of Hearts')
        ->seed('myField', 'Two of Hearts')->seed('myField', 'Three of Hearts')
        ->seed('myHand', 'Eight of Hearts')->seed('myHand', 'Dungeon Guide')
        ->play('Eight of Hearts')->answer(1, ['-', 'myField-1', 'myField-1&myField-2'][$n])
        ->expectZoneCount('myGraveyard', $n, 1, 'Sacrifices happen before reserve payment', 2)
        ->pay(8 - 3 * $n);
    if ($n < 2) $s->pass(1);
    $s
        ->expectZoneCount('myMemory', 8 - 3 * $n, 1, 'Each sacrificed ally pays three reserve')
        ->expectCardExists('myField', 'Eight of Hearts', 'The ally resolves after payment')
        ->expectDqEmpty()->tested('Eight of Hearts')
        ->covers(['cost', 'sacrifice'], 'While paying for this card\'s reserve cost, you may sacrifice up to two Suited allies with reserve cost 4 or less. Each ally sacrificed this way pays for 3 of that cost.')->register();
}

foreach ([1, 2] as $payments) {
    $s = gaScenario('eight-of-hearts-combined-affordability-' . $payments)
        ->deckTemplate('quiet', 'Dungeon Guide')->emptyZone('myHand')
        ->seed('myField', 'Two of Hearts')->seed('myField', 'Three of Hearts')
        ->seed('myHand', 'Eight of Hearts');
    for ($i = 0; $i < $payments; ++$i) $s->seed('myHand', 'Dungeon Guide');
    $s->action(1, 10002, 'myHand-0!FSM!');
    if ($payments === 2) {
        $s->answer(1, 'myField-1&myField-2')->pay(2)
            ->expectCardExists('myField', 'Eight of Hearts', 'Six sacrifice credit plus two reserve pays eight')
            ->expectZoneCount('myMemory', 2, 1, 'Only two reserve cards are required');
    } else {
        $s->expectCardExists('myHand', 'Eight of Hearts', 'Seven combined resources cannot pay eight')
            ->expectZoneCount('myField', 3, 1, 'Unaffordable activation sacrifices nothing');
    }
    $s->tested('Eight of Hearts')->covers(['cost', 'sacrifice'], 'Each ally sacrificed this way pays for 3 of that cost.')->register();
}

gaScenario('eight-of-hearts-sacrifice-cost-four-boundary')
    ->deckTemplate('quiet', 'Dungeon Guide')->emptyZone('myHand')
    ->seed('myField', 'Four of Hearts')->seed('myField', 'Two of Hearts')
    ->seed('myHand', 'Eight of Hearts')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->action(1, 10002, 'myHand-0!FSM!')->answer(1, 'myField-1&myField-2')->pay(2)
    ->expectCardExists('myField', 'Eight of Hearts', 'Reserve cost four is eligible')
    ->expectCardExists('myGraveyard', 'Four of Hearts', 'The cost-four ally is sacrificed')
    ->tested('Eight of Hearts')->covers(['cost', 'sacrifice'], 'Suited allies with reserve cost 4 or less.')->register();

foreach (['cost-five', 'non-suited', 'non-ally', 'opponent'] as $case) {
    $s = gaScenario('eight-of-hearts-excludes-' . $case)
        ->deckTemplate('quiet', 'Dungeon Guide')->emptyZone('myHand')
        ->seed('myField', 'Two of Hearts');
    if ($case === 'cost-five') $s->seed('myField', 'Five of Hearts');
    if ($case === 'non-suited') $s->seed('myField', 'Gray Wolf');
    if ($case === 'non-ally') $s->seed('myField', 'Three of Hearts', properties: ['Counters' => ['_overrides' => ['type' => 'ITEM']]]);
    if ($case === 'opponent') $s->seed('myField', 'Three of Hearts', player: 2);
    $s->seed('myHand', 'Eight of Hearts')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
        ->action(1, 10002, 'myHand-0!FSM!')
        ->expectCardExists('myHand', 'Eight of Hearts', 'An ineligible second object cannot fund the activation')
        ->expectZoneCount('myGraveyard', 0, 1, 'No sacrifices occur when the total cannot pay')
        ->tested('Eight of Hearts')->covers(['cost', 'sacrifice'], 'Suited allies with reserve cost 4 or less.')->register();
}
