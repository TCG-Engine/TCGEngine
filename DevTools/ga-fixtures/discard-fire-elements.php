<?php
foreach ([['dual', 'Scorched Conquest', 2], ['ordinary', 'Red Slime', 2], ['water', 'Eminent Lethargy', 0]] as [$suffix, $discard, $damage]) {
    $s = gaScenario('creative-shock-fire-elements-' . $suffix)
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Creative Shock', classBonus: true)
        ->seed('myHand', $discard)->seed('myHand', 'Creative Shock')
        ->play('Creative Shock')->pay(3)->answer(1, 'myHand-4');
    if ($damage) $s->answer(1, 'theirField-0');
    $s->expectDamage('p2Field-0', $damage, 'Only a Fire discard enables the class-bonus damage')
        ->expectCardExists('myGraveyard', $discard, 'The chosen card is actually discarded')
        ->tested('Creative Shock')->covers(['discard', 'class-bonus', 'element', 'damage'], 'Fire membership includes Exalted Fire.')->register();
}

gaScenario('firetuned-automaton-discards-exalted-fire-as-additional-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Firetuned Automaton', classBonus: true)
    ->seed('myHand', 'Scorched Conquest')->seed('myHand', 'Firetuned Automaton')
    ->play('Firetuned Automaton')->answer(1, 'myHand-7')->pay(2)
    ->expectCardExists('myGraveyard', 'Scorched Conquest', 'Exalted Fire pays the Fire discard cost')
    ->expectCardExists('myField', 'Firetuned Automaton', 'The paid ally resolves')
    ->expectZoneCount('myMemory', 2, 1, 'Reserve cost remains separate from the discard')
    ->tested('Firetuned Automaton')->covers(['additional-cost', 'discard', 'element'], 'Preview and payment both accept Exalted Fire.')->register();
