<?php
foreach([['silvershine', '0', 'Silvershine'], ['fraysia', '1', 'Fraysia']] as [$suffix, $choice, $token]) {
    gaScenario('enki-simultaneous-' . $suffix)
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Floral Arrangement')
        ->seed('myField', 'Greater Boon of Enki')->seed('myHand', 'Floral Arrangement')
        ->play('Floral Arrangement')->pay(3)->answer(1, $choice)
        ->expectZoneCount('myField', 5, 1, 'Both original tokens enter and exactly one token is copied')
        ->expectProperty('p1Field-4', 'CardID', GaScenario::id($token), 'The chosen member of the simultaneous summon is copied')
        ->expectDqEmpty()->tested('Greater Boon of Enki')->covers(['token', 'choice', 'simultaneous'], 'Choose either token from one simultaneous summon.')->register();
}

gaScenario('enki-simultaneous-tonoris-replaces-the-whole-summon')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: "Tonoris, Creation's Will", lineage: ['Spirit of Wind'])
    ->seed('myField', 'Greater Boon of Enki')->seed('myHand', 'Floral Arrangement')
    ->play('Floral Arrangement')->pay(3)->answer(1, 'YES')
    ->expectZoneCount('myField', 5, 1, 'Two replaced tokens and one Enki copy')
    ->expectProperty('p1Field-2', 'CardID', 'hkurfp66pv', 'The first token is replaced')
    ->expectProperty('p1Field-3', 'CardID', 'hkurfp66pv', 'The second token is replaced in the same event')
    ->expectProperty('p1Field-4', 'CardID', 'hkurfp66pv', 'Enki copies a member of the replaced summon')
    ->expectDqEmpty()->tested('Greater Boon of Enki')->covers(['token', 'simultaneous', 'replacement'], 'Tonoris replaces the whole simultaneous summon before Enki chooses a copy.')->register();
