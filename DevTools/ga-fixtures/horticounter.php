<?php
foreach (['YES', 'NO'] as $answer) {
    $s = gaScenario('horticounter-zero-payment-' . strtolower($answer))
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Horticounter', player: 2)
        ->seed('myHand', 'Gray Wolf')->seed('myHand', 'Horticounter', player: 2)
        ->play('Gray Wolf')->pay(2)
        ->answer(2, 'myHand-7')->answer(2, '0')->answer(2, 'EffectStack-0')->answer(1, $answer);
    if ($answer === 'NO') {
        $s->answer(2, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')->expectCardExists('myBanish', 'Gray Wolf', 'Declining zero negates and banishes the activation');
    } else {
        $s->expectCardExists('myField', 'Gray Wolf', 'Accepting zero lets the activation resolve');
    }
    $s->expectDqEmpty()->tested('Horticounter')->covers(['negate', 'payment', 'banish', 'glimpse'], 'As an additional cost to activate this card, sacrifice **Y** Herbs.', '**Negate** target card activation unless its controller pays (X+Y). Banish the card that had its activation **negated** this way. When an activation is negated this way, **glimpse 3**.')->register();
}

foreach ([[1, 0, 'YES'], [1, 1, 'NO'], [0, 2, 'YES'], [6, 0, 'INSUFFICIENT']] as [$x, $y, $choice]) {
    $s = gaScenario('horticounter-x' . $x . '-y' . $y . '-' . strtolower($choice))
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Horticounter', player: 2)
        ->seed('myHand', 'Gray Wolf')->seed('myHand', 'Horticounter', player: 2);
    for ($i = 0; $i < $y; ++$i) $s->seed('myField', 'Manaroot', player: 2);
    $s->play('Gray Wolf')->pay(2)->answer(2, 'myHand-7')->answer(2, strval($x));
    if ($y) $s->answer(2, $y === 1 ? 'myField-1' : 'myField-1&myField-2');
    $s->expectZoneCount('myField', 1, 2, 'Herbs are sacrificed before reserve payment or response', 5 + ($y ? 1 : 0));
    if ($x) $s->pay($x, 2);
    $s->expectZoneCount('myMemory', $x, 2, 'X is paid into memory before resolving the negate', 5 + ($y ? 1 : 0) + $x)
        ->answer(2, 'EffectStack-0');
    if ($choice !== 'INSUFFICIENT') $s->answer(1, $choice);
    if ($choice === 'YES') {
        $payment = implode('&', array_map(fn($i) => 'myHand-' . $i, range(0, $x + $y - 1)));
        $s->answer(1, $payment)->expectCardExists('myField', 'Gray Wolf', 'Paying X plus Y preserves the activation')
            ->expectZoneCount('myMemory', 2 + $x + $y, 1, 'The controller pays the full X plus Y');
    } else {
        $s->expectCardExists('myBanish', 'Gray Wolf', 'An unpaid activation is banished')
            ->answer(2, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=');
    }
    $s->expectDqEmpty()->tested('Horticounter')
        ->covers(['additional-cost', 'sacrifice', 'negate', 'payment', 'banish', 'glimpse'], 'As an additional cost to activate this card, sacrifice **Y** Herbs.', '**Negate** target card activation unless its controller pays (X+Y). Banish the card that had its activation **negated** this way. When an activation is negated this way, **glimpse 3**.')->register();
}

gaScenario('horticounter-cannot-negate-protected-activation')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Horticounter', player: 2)
    ->globalEffect('f6lxizyuml_NEXT_CANT_BE_NEGATED')->seed('myHand', 'Gray Wolf')
    ->seed('myHand', 'Horticounter', player: 2)
    ->play('Gray Wolf')->pay(2)
    ->expectCardExists('myField', 'Gray Wolf', 'The protected activation resolves')
    ->expectCardExists('myHand', 'Horticounter', 'No legal negate target: Horticounter stays in hand', 2)
    ->expectDqEmpty()->tested('Horticounter')->covers(['negate', 'targeting'], 'As an additional cost to activate this card, sacrifice **Y** Herbs.', '**Negate** target card activation unless its controller pays (X+Y). Banish the card that had its activation **negated** this way. When an activation is negated this way, **glimpse 3**.')->register();

gaScenario('horticounter-discount-keeps-declared-x')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Horticounter', player: 2)
    ->globalEffect('1fy8l4pxs9_COST', 2)
    ->seed('myHand', 'Gray Wolf')->seed('myHand', 'Horticounter', player: 2)
    ->play('Gray Wolf')->pay(2)->answer(2, 'myHand-7')->answer(2, '8')->pay(7, 2)
    ->expectZoneCount('myMemory', 7, 2, 'A one-reserve discount allows declared X eight for seven cards', 12)
    ->answer(2, 'EffectStack-0')
    ->expectCardExists('myBanish', 'Gray Wolf', 'The demand uses declared X, exceeding the controller resources')
    ->answer(2, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')->expectDqEmpty()
    ->tested('Horticounter')->covers(['payment', 'negate', 'cost-reduction'], '**Negate** target card activation unless its controller pays (X+Y).')->register();
