<?php
// Viridian taxes Water membership only while an active copy controls the turn.
gaScenario('viridian-elements-dual')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Eminent Lethargy', player: 2)
    ->seed('myField', 'Viridian Protective Trinket')
    ->seed('myHand', 'Eminent Lethargy', player: 2)->endTurn()->answer(2, 'myHand-7')
    ->pay(2, 2)->expectZoneCount('myMemory', 2, 2, 'The printed cost is paid but the surcharge remains', step: 4)
    ->pay(2, 2)
    ->expectZoneCount('myMemory', 4, 2, 'The exact reserve cost was paid')
    ->expectCardExists('myGraveyard', 'Eminent Lethargy', 'The paid activation resolves', viewer: 2)
    ->tested('Viridian Protective Trinket')->covers(['cost', 'element', 'suppression'], 'During your turn, Water element cards opponents activate cost two more.')->register();

gaScenario('viridian-elements-ordinary')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Dissuading Halt', player: 2)
    ->seed('myField', 'Viridian Protective Trinket')
    ->seed('myHand', 'Dissuading Halt', player: 2)->endTurn()->answer(2, 'myHand-7')
    ->pay(2, 2)->expectZoneCount('myMemory', 2, 2, 'The printed cost is paid but the surcharge remains', step: 4)
    ->pay(2, 2)
    ->target('theirField-0', 2)
    ->expectZoneCount('myMemory', 4, 2, 'The exact reserve cost was paid')
    ->expectCardExists('myGraveyard', 'Dissuading Halt', 'The paid activation resolves', viewer: 2)
    ->tested('Viridian Protective Trinket')->covers(['cost', 'element', 'suppression'], 'During your turn, Water element cards opponents activate cost two more.')->register();

gaScenario('viridian-elements-suppressed')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Dissuading Halt', player: 2)
    ->seed('myField', 'Viridian Protective Trinket', properties: ['TurnEffects' => ['NO_ABILITIES']])
    ->seed('myHand', 'Dissuading Halt', player: 2)->endTurn()->answer(2, 'myHand-7')
    ->pay(2, 2)
    ->target('theirField-0', 2)
    ->expectZoneCount('myMemory', 2, 2, 'The exact reserve cost was paid')
    ->expectCardExists('myGraveyard', 'Dissuading Halt', 'The paid activation resolves', viewer: 2)
    ->tested('Viridian Protective Trinket')->covers(['cost', 'element', 'suppression'], 'During your turn, Water element cards opponents activate cost two more.')->register();

gaScenario('viridian-elements-removed')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Dissuading Halt', player: 2)
    ->seed('myField', 'Viridian Protective Trinket', properties: ['removed' => true])
    ->seed('myHand', 'Dissuading Halt', player: 2)->endTurn()->answer(2, 'myHand-7')
    ->pay(2, 2)
    ->target('theirField-0', 2)
    ->expectZoneCount('myMemory', 2, 2, 'The exact reserve cost was paid')
    ->expectCardExists('myGraveyard', 'Dissuading Halt', 'The paid activation resolves', viewer: 2)
    ->tested('Viridian Protective Trinket')->covers(['cost', 'element', 'suppression'], 'During your turn, Water element cards opponents activate cost two more.')->register();

gaScenario('viridian-elements-suppressed-first')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Eminent Lethargy', player: 2)
    ->seed('myField', 'Viridian Protective Trinket', properties: ['TurnEffects' => ['NO_ABILITIES']])
    ->seed('myField', 'Viridian Protective Trinket')
    ->seed('myHand', 'Eminent Lethargy', player: 2)->endTurn()->answer(2, 'myHand-7')
    ->pay(2, 2)->expectZoneCount('myMemory', 2, 2, 'The printed cost is paid but the surcharge remains', step: 4)
    ->pay(2, 2)
    ->expectZoneCount('myMemory', 4, 2, 'The exact reserve cost was paid')
    ->expectCardExists('myGraveyard', 'Eminent Lethargy', 'The paid activation resolves', viewer: 2)
    ->tested('Viridian Protective Trinket')->covers(['cost', 'element', 'suppression'], 'During your turn, Water element cards opponents activate cost two more.')->register();

gaScenario('viridian-elements-own-activation')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Eminent Lethargy')
    ->seed('myField', 'Viridian Protective Trinket')->seed('myHand', 'Eminent Lethargy')
    ->play('Eminent Lethargy')->pay(2)
    ->expectZoneCount('myMemory', 2, 1, 'Own activation has no surcharge')
    ->expectCardExists('myGraveyard', 'Eminent Lethargy', 'Own card resolves')
    ->tested('Viridian Protective Trinket')->covers(['cost', 'controller'], 'Only opponents pay the surcharge.')->register();

gaScenario('viridian-elements-player-two-controller')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Eminent Lethargy')
    ->seed('myField', 'Viridian Protective Trinket', player: 2)->seed('myHand', 'Eminent Lethargy')
    ->endTurn()->pass(1)->pass(1)->endTurn(2)->answer(1, 'myHand-7')->pay(4)
    ->expectZoneCount('myMemory', 4, 1, 'Player two taxes the opponent during player two turn')
    ->expectCardExists('myGraveyard', 'Eminent Lethargy', 'The taxed activation resolves')
    ->tested('Viridian Protective Trinket')->covers(['cost', 'controller'], 'Opponents pay two more during your turn.')->register();

gaScenario('viridian-elements-non-water')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Viridian Protective Trinket', player: 2)
    ->seed('myHand', 'Dungeon Guide')->play('Dungeon Guide')->pay(3)->pass(2)
    ->expectZoneCount('myMemory', 3, 1, 'Norm card pays only printed reserve')
    ->expectCardExists('myField', 'Dungeon Guide', 'The non-Water card resolves')
    ->tested('Viridian Protective Trinket')->covers(['cost', 'element'], 'Only Water cards pay the surcharge.')->register();

gaScenario('viridian-elements-insufficient-reserve')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Eminent Lethargy', player: 2)
    ->seed('myField', 'Viridian Protective Trinket')->emptyZone('myHand', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)->seed('myHand', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Eminent Lethargy', player: 2)
    ->endTurn()->answer(2, 'myHand-2')
    ->expectZoneCount('myMemory', 0, 2, 'Unaffordable activation never begins payment')
    ->expectZoneCount('myHand', 4, 2, 'The card and reserve cards remain in hand')
    ->expectCardExists('myHand', 'Eminent Lethargy', 'Three reserve cards cannot afford the taxed card', viewer: 2)
    ->tested('Viridian Protective Trinket')->covers(['cost', 'affordability'], 'The surcharge participates in affordability checks.')->register();
