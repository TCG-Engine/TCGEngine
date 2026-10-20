<?php
foreach ([['zero', 'PASS', ''], ['one', 'myMemory-0', ',"PREVENT_ALL_1"'], ['two', 'myMemory-0&myMemory-1', ',"PREVENT_ALL_2"']] as [$suffix, $selection, $effect]) {
    gaScenario('veiled-dash-reveal-' . $suffix)
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Veiled Dash', classBonus: true)
        ->seed('myField', 'Dungeon Guide')
        ->seed('myMemory', 'Windmill Engineer')->seed('myMemory', 'Young Peacekeeper')
        ->seed('myHand', 'Veiled Dash')->play('Veiled Dash')->pay(2)
        ->answer(1, 'myField-0&myField-1')->answer(1, $selection)
        ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"' . $effect . ']', 'Prevention equals the explicitly revealed count')
        ->expectZoneCount('myMemory', 4, 1, 'Revealing leaves memory cards in place')
        ->expectDqEmpty()->tested('Veiled Dash')->covers(['reveal', 'choice', 'damage-prevention'], 'Choose zero, one or all eligible Wind cards.')->register();
}
