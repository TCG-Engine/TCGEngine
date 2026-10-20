<?php
foreach([['specter', 'YES', 'Ghastly Slime', 0, 2], ['ordinary', 'NO', 'Gray Wolf', 1, 6]] as [$suffix, $mode, $targetName, $targetIndex, $cost]) {
    gaScenario('spectral-target-cost-' . $suffix)
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Spectral Haunting')
        ->champion(cardName: 'Alice, Phantom Monarch')
        ->seed('myGraveyard', 'Ghastly Slime')->seed('myGraveyard', 'Gray Wolf')
        ->seed('myHand', 'Spectral Haunting')
        ->play('Spectral Haunting')->answer(1, $mode)->pay($cost)->target('myGraveyard-' . $targetIndex)
        ->expectProperty('p1DecisionQueue-0', 'Param', $mode === 'YES' ? 'myGraveyard-0' : 'myGraveyard-0&myGraveyard-1', 'Discounted resolution offers only Specters', step: 2 + $cost)
        ->expectZoneCount('myMemory', $cost, 1, 'The committed target category determines payment')
        ->expectCardExists('myField', $targetName, 'The chosen ally returns')
        ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL"]', 'The returned ally is ephemeral')
        ->tested('Spectral Haunting')->covers(['cost', 'targeting', 'alice-bonus'], 'Only the Specter-only activation receives the Alice discount.')->register();
}
