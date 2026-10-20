<?php
foreach (['NO', 'YES', 'NO_ALICE', 'NEXT_TURN'] as $case) {
    $s = gaScenario('abnegation-ephemerate-' . strtolower(str_replace('_', '-', $case)))
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Horticounter', player: 2);
    if ($case === 'NO_ALICE') $s->readyFor('Abnegation', damage: 8);
    else $s->champion(cardName: 'Alice, Golden Queen', lineage: ['Spirit of Water', 'Tristan, Shadowdancer'], damage: 8);
    $s->seed('myGraveyard', 'Abnegation')->seed('myHand', 'Gray Wolf')
        ->seed('myHand', 'Horticounter', player: 2)
        ->play('Gray Wolf')->pay(2)->answer(2, 'myHand-7')->answer(2, '0')
        ->answer(2, 'EffectStack-0')->answer(1, $case === 'YES' ? 'YES' : 'NO');
    if ($case !== 'YES') $s->answer(2, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=');
    if ($case === 'NEXT_TURN') $s->endTurn();
    $s->action(1, 10002, 'myGraveyard-0!FSM!');
    if ($case === 'NO') {
        $s->pay(2)->expectDamage('myField-0', 0, 'Recover eight after the controlled activation was negated')
            ->expectZoneCount('myGraveyard', 0, 1, 'Ephemeral Abnegation leaves the game')
            ->expectZoneCount('myMemory', 4, 1, 'Ephemerate pays two reserve');
    } else {
        $s->expectCardExists('myGraveyard', 'Abnegation', 'The conditional Ephemerate is unavailable')
            ->expectDamage('myField-0', 8, 'No recovery without the condition');
    }
    $s->tested('Abnegation')->covers(['alice-bonus', 'ephemerate', 'negate'], '[Alice Bonus] If a card activation you controlled was negated this turn, this card in your graveyard has ephemerate (2).')->register();
}

foreach ([false, true] as $protected) {
    $s = gaScenario('abnegation-ephemerate-' . ($protected ? 'protected-activation' : 'no-negation'))
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(cardName: 'Alice, Golden Queen', lineage: ['Tristan, Shadowdancer'], damage: 8)
        ->seed('myGraveyard', 'Abnegation');
    if ($protected) {
        $s->readyFor('Horticounter', player: 2)->globalEffect('f6lxizyuml_NEXT_CANT_BE_NEGATED')
            ->seed('myHand', 'Gray Wolf')->seed('myHand', 'Horticounter', player: 2)
            ->play('Gray Wolf')->pay(2)
            ->expectCardExists('myField', 'Gray Wolf', 'A protected activation resolves without a negation');
    }
    $s->action(1, 10002, 'myGraveyard-0!FSM!')
        ->expectCardExists('myGraveyard', 'Abnegation', 'No successful negation grants Ephemerate')
        ->expectDamage('myField-0', 8, 'No recovery occurs')->tested('Abnegation')
        ->covers(['alice-bonus', 'ephemerate', 'negate'], 'If a card activation you controlled was negated this turn.')->register();
}
