<?php
foreach ([1, 3] as $x) {
    gaScenario('sparkling-adornment-pays-' . $x . '-before-response')
        ->standardDeck('Sparkling Adornment')->readyFor('Sparkling Adornment')
        ->seed('myField', 'Dungeon Guide', properties: ['Counters' => ['sheen' => 3]])
        ->seed('myHand', 'Sparkling Adornment')->play('Sparkling Adornment')
        ->answer(1, 'myField-1')->answer(1, strval($x))
        ->expectProperty('p1Field-1', 'Counters', $x === 3 ? '[]' : '{"sheen":2}', 'Sheen is paid before reserve and responses', 3)
        ->expectProperty('p2Field-0', 'Counters', '[]', 'No sheen granted before resolution', 3)
        ->pay(2)->target('theirField-0')
        ->expectProperty('p2Field-0', 'Counters', '{"sheen":' . $x . '}', 'Resolution uses the paid amount')
        ->expectDqEmpty()->tested('Sparkling Adornment')->covers(['additional-cost', 'sheen'], 'As an additional cost to activate this card, remove one or more **sheen** counters from an object on the field.')->register();
}
gaScenario('sparkling-adornment-refused-without-sheen')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Sparkling Adornment')
    ->seed('myHand', 'Sparkling Adornment')->play('Sparkling Adornment')
    ->expectZoneCount('myHand', 8, 1, 'Without sheen the card stays in hand')
    ->expectZoneCount('myMemory', 0, 1, 'No reserve paid without sheen')
    ->expectDqEmpty()->tested('Sparkling Adornment')->covers(['additional-cost', 'condition'], 'As an additional cost to activate this card, remove one or more **sheen** counters from an object on the field.')->register();
