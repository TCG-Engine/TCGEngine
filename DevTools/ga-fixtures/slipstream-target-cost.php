<?php
foreach([['ordinary', 'Dungeon Guide', 2, true], ['unique', 'Tweedledee, Contrarian Poet', 1, true], ['no-class', 'Tweedledee, Contrarian Poet', 2, false]] as [$suffix, $targetName, $cost, $bonus]) {
    gaScenario('slipstream-target-cost-' . $suffix)
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Slipstream Vault', classBonus: $bonus)
        ->seed('myField', $targetName)
        ->seed('myField', $targetName === 'Dungeon Guide' ? 'Tweedledee, Contrarian Poet' : 'Dungeon Guide')
        ->seed('myHand', 'Slipstream Vault')
        ->play('Slipstream Vault')->target('myField-1')->pay($cost)->pass(2)
        ->expectZoneCount('myMemory', $cost, 1, 'Only the declared target determines the reserve cost')
        ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The declared ally becomes distant')
        ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'The other ally remains unchanged')
        ->tested('Slipstream Vault')->covers(['cost', 'targeting', 'class-bonus'], 'The class discount requires the actual target to be unique.')->register();
}

gaScenario('slipstream-target-cost-insufficient')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Slipstream Vault', classBonus: true)
    ->seed('myField', 'Dungeon Guide')->seed('myField', 'Tweedledee, Contrarian Poet')
    ->emptyZone('myHand')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Slipstream Vault')
    ->play('Slipstream Vault')->target('myField-1')
    ->expectZoneCount('myMemory', 0, 1, 'A non-unique target cannot borrow the other ally discount')
    ->expectCardExists('myHand', 'Slipstream Vault', 'The unaffordable card remains in hand')
    ->tested('Slipstream Vault')->covers(['cost', 'affordability'], 'The declared target determines affordability.')->register();
