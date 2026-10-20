<?php
foreach ([0, 1, 3] as $x) {
    gaScenario('penumbral-waltz-pays-' . $x . '-before-response')
        ->standardDeck('Penumbral Waltz')->champion(cardName: 'Tristan, Shadowreaver')
        ->patch('myField-0', ['Counters' => ['preparation' => 3]])
        ->seed('myHand', 'Penumbral Waltz')->play('Penumbral Waltz')->answer(1, strval($x))
        ->expectProperty('p1Field-0', 'Counters', $x === 3 ? '[]' : '{"preparation":' . (3 - $x) . '}', 'Preparation is paid before priority', 2)
        ->expectZoneCount('myField', 1, 1, 'No tokens before resolution', 2)
        ->answer(1, '-')->answer(2, '-')
        ->expectZoneCount('myField', $x === 3 ? 3 : 1, 1, 'Tristan summons only when X reaches three')
        ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","PREVENT_CHAMP_' . ($x + 3) . '"]', 'Prevention uses the paid X')
        ->expectDqEmpty()->tested('Penumbral Waltz')
        ->covers(['additional-cost', 'prevention', 'tristan-bonus', 'summon'], 'Prevent the next **X+3** damage that would be dealt to your champion this turn. [Tristan Bonus] If **X** is 3 or greater, **summon** two Ominous Shadow tokens.')
        ->register();
}
