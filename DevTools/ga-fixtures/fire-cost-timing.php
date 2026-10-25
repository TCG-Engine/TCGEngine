<?php
// Additional costs must be paid before reserve payment and the response window.
foreach (['Molten Impact' => "Warrior's Longsword", 'Cremation Ritual' => 'Dungeon Guide'] as $spell => $fodder) {
    $slug = strtolower(str_replace(' ', '-', $spell));
    $s = gaScenario($slug . '-sacrifices-before-reserve-payment')
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor($spell)
        ->seed('myField', $fodder)->seed('myHand', $spell)
        ->play($spell)->answer(1, 'myField-1')
        ->expectZoneCount('myField', 1, 1, 'The additional sacrifice is paid before reserve payment', 2)
        ->expectZoneCount('myMemory', 0, 1, 'Reserve payment has not begun', 2)
        ->pay($spell === 'Molten Impact' ? 2 : 3);
    if ($spell === 'Molten Impact') $s->target('theirField-0')->expectDamage('p2Field-0', 4, 'Damage remembers the sacrificed weapons two durability counters');
    else $s->expectZoneCount('myHand', 6, 1, 'Draw two after paying three reserve');
    $s->expectDqEmpty()->tested($spell)->covers(['additional-cost', 'sacrifice'], 'As an additional cost to activate this card, sacrifice ' . ($spell === 'Molten Impact' ? 'a weapon.' : 'an ally.'))->register();

    gaScenario($slug . '-refused-without-sacrifice-fodder')
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor($spell)
        ->seed('myHand', $spell)->play($spell)
        ->expectZoneCount('myHand', 8, 1, 'Without sacrifice fodder the spell stays in hand')
        ->expectZoneCount('myMemory', 0, 1, 'No reserve is paid for an illegal activation')
        ->expectDqEmpty()->tested($spell)->covers(['additional-cost', 'condition'], 'As an additional cost to activate this card, sacrifice ' . ($spell === 'Molten Impact' ? 'a weapon.' : 'an ally.'))->register();
}

gaScenario('combustible-potion-brews-with-two-herbs')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Combustible Potion')
    ->seed('myField', 'Blightroot')->seed('myField', 'Springleaf')
    ->seed('myHand', 'Combustible Potion')
    ->play('Combustible Potion')->answer(1, 'YES')->answer(1, 'myField-1')->answer(1, 'myField-2')
    ->answer(1, 'myHand-0')
    ->expectCardExists('myField', 'Combustible Potion', 'The brewed potion enters')
    ->expectZoneCount('myField', 2, 1, 'Both Herbs were sacrificed')
    ->expectZoneCount('myMemory', 0, 1, 'Brew replaces the four reserve cost')
    ->expectZoneCount('myHand', 8, 1, 'Brew draws two and discards one')
    ->expectDqEmpty()->tested('Combustible Potion')
    ->covers(['brew', 'additional-cost', 'draw-discard'], 'Brew — Two Herbs', 'On Enter: If CARDNAME was brewed, draw two cards and discard a card.')->register();
