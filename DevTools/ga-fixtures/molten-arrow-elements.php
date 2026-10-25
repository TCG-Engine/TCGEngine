<?php
foreach ([['dual', ['Scorched Conquest', 'Scorched Conquest', 'Scorched Conquest']], ['mixed', ['Red Slime', 'Scorched Conquest', 'Red Slime']]] as [$suffix, $payments]) {
    $scenario = gaScenario('molten-arrow-elements-' . $suffix)
        ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Intricate Longbow');
    foreach ($payments as $payment) $scenario->seed('myGraveyard', $payment);
    $scenario->seed('myGraveyard', 'Molten Arrow')
        ->action(1, 10002, 'myGraveyard-3!FSM!')
        ->target('myGraveyard-0')->target('myGraveyard-0')->target('myGraveyard-0')
        ->expectProperty('p1Field-1', 'Subcards', '["mvfcd0ukk6"]', 'The paid arrow loads after all three Fire cards are banished')
        ->expectZoneCount('myBanish', 3, 1, 'Exalted Fire pays the Fire cost through each continuation')
        ->expectZoneCount('myGraveyard', 0, 1, 'Payments and arrow leave the graveyard')
        ->tested('Molten Arrow')->covers(['activated-ability', 'cost', 'element', 'load'], 'Fire membership includes Exalted Fire at each sequential cost choice.')->register();
}
gaScenario('molten-arrow-elements-insufficient')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Intricate Longbow')
    ->seed('myGraveyard', 'Scorched Conquest')->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Eminent Lethargy')->seed('myGraveyard', 'Molten Arrow')
    ->action(1, 10002, 'myGraveyard-3!FSM!')
    ->expectZoneCount('myBanish', 0, 1, 'Neither a non-Fire card nor the arrow itself pays the missing third cost')
    ->expectZoneCount('myGraveyard', 4, 1, 'An unaffordable activation moves no cards')
    ->expectProperty('p1Field-1', 'Subcards', '[]', 'An unpaid arrow cannot load')
    ->tested('Molten Arrow')->covers(['activated-ability', 'cost', 'affordability', 'element'], 'Require three other Fire cards before beginning payment.')->register();
